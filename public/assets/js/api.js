/* ============================================================
   API.JS — Lớp fetch + AbortController + mock mode
   ============================================================ */

import { CONFIG } from './config.js';
import { MOCK_CATEGORIES, MOCK_RESTAURANTS } from './mock-data.js';

// ── Haversine client-side (cho mock mode) ──
function haversineKm(lat1, lng1, lat2, lng2) {
  const R = 6371.0088;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2
          + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
          * Math.sin(dLng / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(a));
}

// ── Active AbortControllers, one per endpoint ───────────────
const controllers = new Map();

/**
 * Fetch wrapper với AbortController và xử lý lỗi
 */
async function apiFetch(endpoint, params = {}) {
  controllers.get(endpoint)?.abort();
  const controller = new AbortController();
  controllers.set(endpoint, controller);

  const url = new URL(`${CONFIG.API_BASE}/${endpoint}`, window.location.origin);
  Object.entries(params).forEach(([k, v]) => {
    if (v !== null && v !== undefined && v !== '') {
      url.searchParams.set(k, v);
    }
  });

  try {
    const res = await fetch(url.toString(), { signal: controller.signal });

    if (!res.ok) {
      const data = await res.json().catch(() => null);
      throw {
        status: res.status,
        code: data?.error?.code || 'SERVER_ERROR',
        message: data?.error?.message || `Lỗi server (${res.status})`,
      };
    }

    return await res.json();
  } finally {
    if (controllers.get(endpoint) === controller) controllers.delete(endpoint);
  }
}

// ── Mock implementations ──

function mockCategories() {
  return {
    success: true,
    data: [...MOCK_CATEGORIES],
  };
}

function mockRestaurants({ lat, lng, radius = 3, type = 'all', category_id, price_level, min_rating, open_now, q, sort = 'distance', limit = 50, offset = 0 } = {}) {
  const currentLat = (lat !== undefined && lat !== null && !isNaN(Number(lat))) ? Number(lat) : CONFIG.FALLBACK_LAT;
  const currentLng = (lng !== undefined && lng !== null && !isNaN(Number(lng))) ? Number(lng) : CONFIG.FALLBACK_LNG;
  let results = MOCK_RESTAURANTS.map(r => ({
    ...r,
    place_type: r.place_type || r.category?.type_group || 'food',
    ticket_price: r.ticket_price ?? 0,
    estimated_duration: r.estimated_duration ?? 60,
    best_time_slot: r.best_time_slot || 'any',
    distance_km: haversineKm(currentLat, currentLng, r.lat, r.lng),
    is_open_now: isOpenNowMock(r.open_time, r.close_time, r.is_24h),
  }));

  // Filter by radius
  results = results.filter(r => r.distance_km <= radius);

  // Filter by place_type (food, tourism, entertainment)
  if (type && type !== 'all') {
    results = results.filter(r => (r.place_type || r.category?.type_group || 'food') === type);
  }

  // Filter by category
  if (category_id) {
    results = results.filter(r => r.category.id === Number(category_id));
  }

  // Filter by price level
  if (price_level) {
    results = results.filter(r => r.price_level === Number(price_level));
  }

  // Filter by min rating
  if (min_rating) {
    results = results.filter(r => r.rating >= Number(min_rating));
  }

  // Filter open now
  if (open_now) {
    results = results.filter(r => r.is_open_now === true);
  }

  // Filter by search query
  if (q) {
    const kw = q.toLowerCase();
    results = results.filter(r =>
      r.name.toLowerCase().includes(kw) || r.address.toLowerCase().includes(kw));
  }

  // Sort
  switch (sort) {
    case 'rating':
      results.sort((a, b) => b.rating - a.rating);
      break;
    case 'price':
      results.sort((a, b) => a.price_min - b.price_min);
      break;
    default:
      results.sort((a, b) => a.distance_km - b.distance_km);
  }

  const total = results.length;
  results = results.slice(offset, offset + limit);

  return {
    success: true,
    data: results,
    meta: { count: total, radius_km: radius, limit, offset },
  };
}

function isOpenNowMock(openTime, closeTime, is24h) {
  if (is24h) return true;
  if (!openTime || !closeTime) return null;
  const now = new Date();
  const cur = now.getHours() * 60 + now.getMinutes();
  const parse = (t) => { const p = t.split(':'); return parseInt(p[0]) * 60 + parseInt(p[1]); };
  const o = parse(openTime), c = parse(closeTime);
  if (c <= o) return cur >= o || cur < c;
  return cur >= o && cur < c;
}

function mockRestaurantById(id) {
  const restaurant = MOCK_RESTAURANTS.find(r => r.id === Number(id));
  return restaurant
    ? { success: true, data: { ...restaurant, images: [restaurant.image_url] } }
    : { success: false, error: { code: 'NOT_FOUND', message: 'Không tìm thấy quán ăn' } };
}

function mockRandomRestaurant(params = {}) {
  const result = mockRestaurants({ ...params, limit: 100 });
  if (!result.data.length) {
    return { success: true, data: null, meta: { reason: 'no_candidates' } };
  }
  // Weighted random — ưu tiên gần + rating cao
  const pool = result.data.filter(r => !(params.exclude || []).includes(r.id));
  const candidates = pool.length ? pool : result.data;
  const weights = candidates.map(r =>
    (0.30 + r.rating / 5) * Math.exp(-r.distance_km / 1.5));
  const total = weights.reduce((s, w) => s + w, 0);
  let t = Math.random() * total;
  for (let i = 0; i < candidates.length; i++) {
    t -= weights[i];
    if (t <= 0) return { success: true, data: candidates[i] };
  }
  return { success: true, data: candidates[candidates.length - 1] };
}

// ── Public API ──

export async function fetchCategories() {
  if (CONFIG.USE_MOCK) {
    // Simulate network delay
    await new Promise(r => setTimeout(r, 150));
    return mockCategories();
  }
  try {
    return await apiFetch('categories.php');
  } catch (err) {
    if (err?.name === 'AbortError') throw err;
    console.warn('[FoodFinder] API categories gặp sự cố, tự động dùng mock data:', err);
    return mockCategories();
  }
}

export async function fetchRestaurants(params = {}) {
  if (CONFIG.USE_MOCK) {
    await new Promise(r => setTimeout(r, 300));
    return mockRestaurants(params);
  }
  try {
    return await apiFetch('restaurants.php', params);
  } catch (err) {
    if (err?.name === 'AbortError') throw err;
    console.warn('[FoodFinder] API restaurants gặp sự cố hoặc CSDL chưa chạy, tự động dùng mock data:', err);
    return mockRestaurants(params);
  }
}

export async function fetchRandom(params = {}) {
  if (CONFIG.USE_MOCK) {
    await new Promise(r => setTimeout(r, 200));
    return mockRandomRestaurant(params);
  }
  try {
    return await apiFetch('random.php', params);
  } catch (err) {
    if (err?.name === 'AbortError') throw err;
    console.warn('[FoodFinder] API random gặp sự cố, tự động dùng mock data:', err);
    return mockRandomRestaurant(params);
  }
}

export async function fetchRestaurant(id) {
  if (CONFIG.USE_MOCK) {
    return mockRestaurantById(id);
  }
  try {
    return await apiFetch('restaurant.php', { id });
  } catch (err) {
    if (err?.name === 'AbortError') throw err;
    console.warn('[FoodFinder] API restaurant gặp sự cố, tự động dùng mock data:', err);
    return mockRestaurantById(id);
  }
}

export function abortPending() {
  controllers.forEach(controller => controller.abort());
  controllers.clear();
}
