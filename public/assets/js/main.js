/* ============================================================
   MAIN.JS — Bootstrap, orchestration, gắn sự kiện
   ============================================================ */

import { CONFIG } from './config.js';
import { getCurrentPosition } from './geo.js';
import { fetchCategories, fetchRestaurants, fetchItinerary } from './api.js';
import { initMap, setUserMarker, renderMarkers, flyTo, highlightMarker, setTheme as setMapTheme, invalidateSize, getDirectionsUrl, clearItineraryRoute } from './map.js';
import { renderList, renderSkeleton, renderError, updateCount, highlightRow, setRowClickHandler } from './list.js';
import { initFilters, setFilterChangeHandler, getActiveFilters, renderCategoryChips } from './filters.js';
import { rollRandom, showRandomCard, hideRandomCard } from './random.js';
import { initSheet, setState as setSheetState } from './sheet.js';
import { icon } from './icons.js';
import { initItinerarySetup, deactivateItineraryMode, activateItineraryMode } from './itinerary-setup.js';
import { setExitItineraryHandler } from './itinerary-view.js';

// ══════════════ APP STATE ══════════════
const state = {
  user:     { lat: CONFIG.FALLBACK_LAT, lng: CONFIG.FALLBACK_LNG, source: 'fallback' },
  filters:  { type: 'all', radius: CONFIG.DEFAULT_RADIUS || 3, categoryId: null, priceLevel: null, minRating: null, openNow: false, q: '', sort: 'distance' },
  results:  [],
  selectedId: null,
  sheet:    'peek',
  loading:  false,
  error:    null,
};


// ── DOM refs ──
const sidebarList  = document.getElementById('sidebar-list');
const sheetList    = document.getElementById('sheet-list');
const sidebarCount = document.getElementById('sidebar-count');
const sheetCount   = document.getElementById('sheet-count');
const btnRandom    = document.getElementById('btn-random');
const btnTheme     = document.getElementById('btn-theme');
const toastContainer = document.getElementById('toast-container');

// ══════════════ DARK MODE ══════════════
function isDark() {
  const saved = localStorage.getItem('food-finder-theme');
  if (saved === 'dark') return true;
  if (saved === 'light') return false;
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(dark) {
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  localStorage.setItem('food-finder-theme', dark ? 'dark' : 'light');
  if (btnTheme) btnTheme.innerHTML = icon(dark ? 'sun' : 'moon');
  setMapTheme(dark);
}

// ══════════════ TOAST ══════════════
function showToast(message) {
  if (!toastContainer) return;
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.setAttribute('role', 'status');
  toast.textContent = message;
  toastContainer.appendChild(toast);
  setTimeout(() => toast.remove(), 4200);
}

// ══════════════ LOAD RESTAURANTS ══════════════
let loadVersion = 0;

async function loadRestaurants() {
  const version = ++loadVersion;
  state.loading = true;
  state.error = null;

  // Show skeleton after delay (avoid flash on fast networks)
  const skeletonTimer = setTimeout(() => {
    if (version !== loadVersion) return;
    renderSkeleton(sidebarList, 5);
    renderSkeleton(sheetList, 5);
  }, CONFIG.SKELETON_DELAY);

  updateCount(sidebarCount, null);
  updateCount(sheetCount, null);

  try {
    const filters = getActiveFilters(state);
    const result = await fetchRestaurants(filters);

    clearTimeout(skeletonTimer);
    if (version !== loadVersion) return;

    if (result.success) {
      state.results = result.data;
      state.selectedId = null;

      // Render list in both sidebar and sheet
      renderList(sidebarList, state.results, state.selectedId);
      renderList(sheetList, state.results, state.selectedId);
      updateCount(sidebarCount, result.meta?.count ?? state.results.length);
      updateCount(sheetCount, result.meta?.count ?? state.results.length);

      // Render markers
      renderMarkers(state.results, state.selectedId);
    } else {
      throw { message: result.error?.message || 'Lỗi không xác định' };
    }
  } catch (err) {
    clearTimeout(skeletonTimer);

    if (err.name === 'AbortError') {
      return; // Request was cancelled by a newer one
    }
    if (version !== loadVersion) return;

    state.error = err.message || 'Không tải được danh sách. Kiểm tra kết nối rồi thử lại.';
    renderError(sidebarList, state.error);
    renderError(sheetList, state.error);
    updateCount(sidebarCount, 0);
    updateCount(sheetCount, 0);
  } finally {
    if (version === loadVersion) state.loading = false;
  }
}

// ══════════════ SELECT RESTAURANT ══════════════
function selectRestaurant(restaurant) {
  state.selectedId = restaurant.id;

  // Highlight row in lists
  highlightRow(sidebarList, restaurant.id);
  highlightRow(sheetList, restaurant.id);

  // Highlight marker and fly to
  highlightMarker(restaurant.id);
  flyTo(restaurant.lat, restaurant.lng);

  // On mobile, go to peek to see map
  if (window.innerWidth < 768) {
    setSheetState('peek');
  }
}

// ══════════════ RANDOMIZER ══════════════
async function handleRandom() {
  const filters = getActiveFilters(state);
  btnRandom.disabled = true;
  btnRandom.style.opacity = '0.6';

  try {
    const restaurant = await rollRandom(filters);
    if (restaurant) {
      showRandomCard(restaurant, {
        getDirectionsUrl,
        onReroll: handleRandom,
        onClose: () => {},
      });
      // Focus on the restaurant on the map
      flyTo(restaurant.lat, restaurant.lng);
      highlightMarker(restaurant.id);
    } else {
      showToast('Không tìm được quán nào phù hợp. Nới bán kính hoặc bỏ bớt bộ lọc.');
    }
  } catch (err) {
    showToast('Lỗi khi chọn quán. Thử lại sau.');
  }

  btnRandom.disabled = false;
  btnRandom.style.opacity = '';
}

// ══════════════ BOOTSTRAP ══════════════
async function bootstrap() {
  // 1. Apply saved theme
  applyTheme(isDark());

  // 2. Show skeleton state
  renderSkeleton(sidebarList, 5);
  renderSkeleton(sheetList, 5);

  // 3. Load categories (with localStorage cache)
  let categories = [];
  try {
    const cachedCats = localStorage.getItem('food-finder-categories');
    const cachedTime = localStorage.getItem('food-finder-categories-ts');
    if (cachedCats && cachedTime && (Date.now() - parseInt(cachedTime)) < 3600000) {
      categories = JSON.parse(cachedCats);
    } else {
      const catResult = await fetchCategories();
      if (catResult.success) {
        categories = catResult.data;
        localStorage.setItem('food-finder-categories', JSON.stringify(categories));
        localStorage.setItem('food-finder-categories-ts', String(Date.now()));
      }
    }
  } catch {
    // Non-fatal, chips just won't render
  }

  // Render category chips
  renderCategoryChips(categories, state);

  // 4. Init map at fallback coords
  const dark = isDark();
  initMap('map', {
    lat: CONFIG.FALLBACK_LAT,
    lng: CONFIG.FALLBACK_LNG,
    zoom: CONFIG.DEFAULT_ZOOM,
    isDark: dark,
    onMarkerSelect: (id) => {
      const r = state.results.find(r => r.id === id);
      if (r) selectRestaurant(r);
    },
    onUserPositionChange: (lat, lng) => {
      state.user.lat = lat;
      state.user.lng = lng;
      state.user.source = 'manual';
      showToast('Vị trí đã được cập nhật');
      loadRestaurants();
    },
  });

  // Đặt marker người dùng ban đầu tại vị trí mặc định (ĐH Tôn Đức Thắng)
  setUserMarker(CONFIG.FALLBACK_LAT, CONFIG.FALLBACK_LNG);

  // 5. Init filters & interactions
  initFilters(state);
  setFilterChangeHandler(() => loadRestaurants());
  setRowClickHandler((r) => selectRestaurant(r));

  // 6. Tải quán ăn NGAY TỨC THÌ (không để bản đồ bị trống trong lúc chờ GPS)
  await loadRestaurants();

  // 7. Init bottom sheet (mobile)
  initSheet({
    onStateChange: (s) => {
      state.sheet = s;
      invalidateSize();
    },
  });

  // First-time: slide sheet from peek to half
  if (window.innerWidth < 768) {
    setTimeout(() => setSheetState('half'), 800);
  }

  // 8. Bind events
  bindEvents();

  // 9. FE-04 -> FE-07: Khởi tạo Tính năng Lộ trình Đi chơi Thông minh
  initItinerarySetup(state, showToast);
  setExitItineraryHandler(() => {
    deactivateItineraryMode();
    renderMarkers(state.results, state.selectedId);
  });

  // Kiểm tra liên kết chia sẻ lộ trình từ URL (FE-07)
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('itinerary') === '1') {
    const duration = urlParams.get('duration') || 'half_day';
    const vibe = urlParams.get('vibe') || 'chill';
    fetchItinerary({ lat: state.user.lat, lng: state.user.lng, duration, vibe, radius: 10 })
      .then(res => {
        if (res.success && res.data) {
          activateItineraryMode(res.data, state.user);
          showToast('🔗 Đang mở lộ trình được chia sẻ!');
        }
      })
      .catch(() => {});
  }

  // 10. Xác định GPS của người dùng trong background (không chặn UI)
  resolveUserLocation();
}


function haversineDistanceKm(lat1, lng1, lat2, lng2) {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2
          + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
          * Math.sin(dLng / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(a));
}

async function resolveUserLocation() {
  const pos = await getCurrentPosition();
  if (pos.source === 'gps') {
    const distToTDTU = haversineDistanceKm(pos.lat, pos.lng, CONFIG.FALLBACK_LAT, CONFIG.FALLBACK_LNG);
    if (distToTDTU <= 10) {
      // Người dùng ở gần khu vực Quận 7 / lân cận (<= 10km) -> cập nhật vị trí thật
      state.user.lat = pos.lat;
      state.user.lng = pos.lng;
      state.user.source = 'gps';
      setUserMarker(pos.lat, pos.lng);
      flyTo(pos.lat, pos.lng, CONFIG.FOCUS_ZOOM);
      showToast('Đã xác định vị trí của bạn');
      await loadRestaurants();
    } else {
      // Người dùng ở tỉnh thành khác hoặc quá xa (> 10km) -> giữ trung tâm Q.7
      showToast('Vị trí của bạn ở xa khu vực trải nghiệm. Đang hiển thị các quán ăn quanh ĐH Tôn Đức Thắng (Q.7)');
    }
  } else if (pos.error) {
    console.info('GPS fallback:', pos.error);
  }
}

// ══════════════ BIND EVENTS ══════════════
function bindEvents() {
  // Dark mode toggle
  if (btnTheme) {
    btnTheme.addEventListener('click', () => {
      applyTheme(!isDark());
    });
  }

  // Randomizer
  if (btnRandom) {
    btnRandom.addEventListener('click', handleRandom);
  }

  // Reset / Recenter location to TDTU District 7
  window.addEventListener('food-finder:reset-location', () => {
    state.user.lat = CONFIG.FALLBACK_LAT;
    state.user.lng = CONFIG.FALLBACK_LNG;
    state.user.source = 'fallback';
    state.filters.radius = CONFIG.DEFAULT_RADIUS || 3;
    const radiusSelect = document.getElementById('filter-radius');
    if (radiusSelect) radiusSelect.value = String(state.filters.radius);
    setUserMarker(CONFIG.FALLBACK_LAT, CONFIG.FALLBACK_LNG);
    flyTo(CONFIG.FALLBACK_LAT, CONFIG.FALLBACK_LNG, CONFIG.FOCUS_ZOOM);
    showToast('Đã chuyển về khu vực ĐH Tôn Đức Thắng (Quận 7)');
    loadRestaurants();
  });

  const recenterBtn = document.getElementById('btn-recenter-tdtu');
  if (recenterBtn) {
    recenterBtn.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('food-finder:reset-location'));
    });
  }

  // Retry (from error state)
  window.addEventListener('food-finder:retry', () => loadRestaurants());

  // Esc key — close popup/overlay
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      hideRandomCard();
    }
  });

  // Resize — invalidate map
  window.addEventListener('resize', () => {
    invalidateSize();
  });

  // Orientation change
  window.addEventListener('orientationchange', () => {
    setTimeout(() => invalidateSize(), 200);
  });
}

// ══════════════ START ══════════════
bootstrap().catch(err => {
  console.error('[FoodFinder] Lỗi khởi động ứng dụng:', err);
});
