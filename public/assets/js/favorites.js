/* ============================================================
   FAVORITES.JS — Quản lý Mục Đã Lưu (Địa điểm & Lộ trình)
   - Phân tách độc lập theo từng tài khoản đăng nhập (User Scoped)
   - Khách vãng lai (Guest): Xóa sạch dữ liệu lưu sau mỗi lần reload trang web
   ============================================================ */

import { MOCK_RESTAURANTS, MOCK_CATEGORIES } from './mock-data.js';
import { fetchRestaurants, fetchCategories } from './api.js';
import { CONFIG } from './config.js';
import { escapeText, formatTicketOrPrice, formatDuration, getPlaceTypeInfo, getDirectionsUrl, getMultiStopDirectionsUrl } from './format.js';
import { icon, categoryIcon } from './icons.js';
import { auth } from './auth.js';

// ── XÓA DỮ LIỆU CỦA KHÁCH SAU MỖI LẦN RELOAD TRANG WEB (Theo yêu cầu) ──
// Dữ liệu khách chỉ lưu tạm trong sessionStorage của tab hiện tại, xóa sạch khi reload/mở lại
(function clearGuestDataOnReload() {
  try {
    sessionStorage.removeItem('food_finder_guest_favorites');
    sessionStorage.removeItem('food_finder_guest_itineraries');
    // Xóa dữ liệu cũ toàn cục nếu có để tránh lẫn lộn với tài khoản
    localStorage.removeItem('food_finder_favorites');
    localStorage.removeItem('food_finder_saved_itineraries');
  } catch (_) {}
})();

/**
 * Lấy storage key tương ứng với trạng thái đăng nhập
 */
function getStorageKeys() {
  const user = auth.getUser();
  if (user && user.id) {
    return {
      isGuest: false,
      placesKey: `food_finder_fav_user_${user.id}`,
      itinsKey: `food_finder_itin_user_${user.id}`,
    };
  }
  return {
    isGuest: true,
    placesKey: 'food_finder_guest_favorites',
    itinsKey: 'food_finder_guest_itineraries',
  };
}

// ── Lấy danh sách ID địa điểm đã lưu ──
export function getFavoriteIds() {
  const { isGuest, placesKey } = getStorageKeys();

  if (isGuest) {
    try {
      const raw = sessionStorage.getItem(placesKey);
      return raw ? JSON.parse(raw) : []; // Khách mặc định luôn là rỗng [] (không có dữ liệu mẫu)
    } catch {
      return [];
    }
  }

  // Tài khoản đã đăng nhập
  try {
    const raw = localStorage.getItem(placesKey);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

// ── Lưu danh sách ID địa điểm ──
export function saveFavoriteIds(ids) {
  const { isGuest, placesKey } = getStorageKeys();

  if (isGuest) {
    try {
      sessionStorage.setItem(placesKey, JSON.stringify(ids));
    } catch (e) {
      console.warn('Lỗi lưu favorite khách:', e);
    }
    return;
  }

  try {
    localStorage.setItem(placesKey, JSON.stringify(ids));
  } catch (e) {
    console.error('Lỗi lưu địa điểm vào localStorage:', e);
  }
}

// ── Kiểm tra địa điểm đã lưu chưa ──
export function isFavorite(id) {
  return getFavoriteIds().includes(Number(id));
}

// ── Bật/tắt lưu địa điểm ──
export function toggleFavorite(id) {
  const numId = Number(id);
  const ids = getFavoriteIds();
  const index = ids.indexOf(numId);
  let saved = false;

  if (index >= 0) {
    ids.splice(index, 1);
    saved = false;
  } else {
    ids.unshift(numId);
    saved = true;
  }

  saveFavoriteIds(ids);

  // Đồng bộ với Backend API nếu đã đăng nhập
  if (auth.isLoggedIn()) {
    fetch('/api/favorites.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ restaurant_id: numId, action: saved ? 'add' : 'remove' }),
    }).catch(err => console.warn('Lỗi đồng bộ favorite lên server:', err));
  }

  // Phát event thông báo để các view khác tự cập nhật (map markers, list, ...)
  window.dispatchEvent(new CustomEvent('favorites:changed', { detail: { id: numId, saved, ids } }));

  return saved;
}

// ── Lấy danh sách Lộ trình đã lưu ──
export function getSavedItineraries() {
  const { isGuest, itinsKey } = getStorageKeys();

  if (isGuest) {
    try {
      const raw = sessionStorage.getItem(itinsKey);
      return raw ? JSON.parse(raw) : []; // Khách mặc định luôn là rỗng []
    } catch {
      return [];
    }
  }

  try {
    const raw = localStorage.getItem(itinsKey);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

// ── Lưu toàn bộ danh sách Lộ trình ──
export function saveItinerariesList(list) {
  const { isGuest, itinsKey } = getStorageKeys();

  if (isGuest) {
    try {
      sessionStorage.setItem(itinsKey, JSON.stringify(list));
    } catch (e) {
      console.warn('Lỗi lưu lộ trình khách:', e);
    }
    return;
  }

  try {
    localStorage.setItem(itinsKey, JSON.stringify(list));
  } catch (e) {
    console.error('Lỗi lưu lộ trình vào localStorage:', e);
  }
}

// ── Xóa một lộ trình đã lưu ──
export function deleteSavedItinerary(id) {
  const list = getSavedItineraries().filter(item => item.id !== id);
  saveItinerariesList(list);

  // Xóa trên Server nếu đã đăng nhập
  if (auth.isLoggedIn()) {
    fetch(`/api/user_itineraries.php?id=${encodeURIComponent(id)}`, {
      method: 'DELETE',
      credentials: 'same-origin',
    }).catch(err => console.warn('Lỗi xóa lộ trình trên server:', err));
  }

  window.dispatchEvent(new CustomEvent('itineraries:changed', { detail: { id, list } }));
}

// ── Format thời gian lưu ──
function formatSavedDate(isoString) {
  if (!isoString) return 'Gần đây';
  try {
    const d = new Date(isoString);
    if (isNaN(d.getTime())) return 'Gần đây';
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${hours}:${minutes} • ${day}/${month}/${year}`;
  } catch {
    return 'Gần đây';
  }
}

// ── Format Vibe Badge ──
function getVibeBadge(vibe) {
  const map = {
    foodie: { text: '🍜 Ăn sập Sài Gòn', class: 'badge--food' },
    date: { text: '💖 Hẹn hò lãng mạn', class: 'badge--tourism' },
    chill: { text: '🌿 Chill thư thái', class: 'badge--category' },
    adventure: { text: '⚡ Vui chơi năng động', class: 'badge--entertainment' },
  };
  return map[vibe] || { text: '✨ Khám phá', class: 'badge--category' };
}

// ── Đồng bộ dữ liệu yêu thích và lộ trình từ Server về Client khi đăng nhập ──
export async function syncUserDataFromServer() {
  if (!auth.isLoggedIn()) return;
  const user = auth.getUser();
  if (!user || !user.id) return;

  const placesKey = `food_finder_fav_user_${user.id}`;
  const itinsKey = `food_finder_itin_user_${user.id}`;

  try {
    // 1. Tải danh sách Địa điểm yêu thích từ MySQL
    const resFav = await fetch('/api/favorites.php', { credentials: 'same-origin' });
    if (resFav.ok) {
      const dataFav = await resFav.json();
      if (dataFav.success && Array.isArray(dataFav.data?.ids)) {
        localStorage.setItem(placesKey, JSON.stringify(dataFav.data.ids));
      }
    }

    // 2. Tải danh sách Lộ trình từ MySQL
    const resItin = await fetch('/api/user_itineraries.php', { credentials: 'same-origin' });
    if (resItin.ok) {
      const dataItin = await resItin.json();
      if (dataItin.success && Array.isArray(dataItin.data?.list)) {
        localStorage.setItem(itinsKey, JSON.stringify(dataItin.data.list));
      }
    }
  } catch (err) {
    console.warn('Không thể đồng bộ dữ liệu từ server:', err);
  }
}

// ── KHỞI TẠO TRANG FAVORITES.HTML ──
async function initFavoritesPage() {
  const listPlacesEl = document.getElementById('favorites-list');
  const listItinEl = document.getElementById('itineraries-list');
  if (!listPlacesEl || !listItinEl) return;

  // Đảm bảo auth đã khởi tạo xong
  await auth.init();

  // Nếu là thành viên đã đăng nhập: Đồng bộ dữ liệu mới nhất từ CSDL
  if (auth.isLoggedIn()) {
    await syncUserDataFromServer();
  }

  // Elements chung
  const countBadgePlaces = document.getElementById('count-badge-places');
  const countBadgeItin = document.getElementById('count-badge-itineraries');
  const mainHeading = document.getElementById('page-main-heading');
  const mainDesc = document.getElementById('page-main-desc');
  const countEl = document.getElementById('favorites-count');
  const btnClearAll = document.getElementById('btn-clear-all');

  // Tabs chính
  const tabBtnPlaces = document.getElementById('tab-btn-places');
  const tabBtnItin = document.getElementById('tab-btn-itineraries');
  const viewPlaces = document.getElementById('view-places');
  const viewItin = document.getElementById('view-itineraries');

  // Elements của phần Địa điểm
  const emptyPlacesEl = document.getElementById('favorites-empty');
  const filterCatEl = document.getElementById('filter-category');
  const searchInput = document.getElementById('search-favorites');
  const typeTabsContainer = document.getElementById('favorites-type-tabs');

  // Elements của phần Lộ trình
  const emptyItinEl = document.getElementById('itineraries-empty');

  // Trạng thái tab hiện tại
  const urlParams = new URLSearchParams(window.location.search);
  let activeMainTab = (urlParams.get('tab') === 'itinerary' || urlParams.get('tab') === 'itineraries')
    ? 'itineraries'
    : 'places';

  let activePlaceType = 'all';

  // Hiển thị banner trạng thái Người dùng / Khách
  function renderUserBanner() {
    let banner = document.getElementById('fav-user-status-banner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'fav-user-status-banner';
      banner.style.cssText = 'padding: 12px 16px; margin-bottom: 20px; border: 2px solid var(--c-border); font-size: 13.5px; font-weight: 600;';
      const pageHeader = document.querySelector('.page-header');
      if (pageHeader && pageHeader.parentNode) {
        pageHeader.parentNode.insertBefore(banner, pageHeader);
      }
    }

    if (auth.isLoggedIn()) {
      const u = auth.getUser();
      banner.style.background = 'var(--c-surface)';
      banner.style.borderColor = 'var(--c-border)';
      banner.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
          <div>
            👤 Đang xem mục đã lưu của tài khoản: <strong>${escapeText(u.full_name || u.username)}</strong> (${escapeText(u.email)})
          </div>
          <span class="badge" style="background:var(--c-accent); color:#181818; border:1px solid #181818; font-weight:800;">ĐÃ ĐỒNG BỘ CSDL</span>
        </div>
      `;
    } else {
      banner.style.background = '#fef3c7';
      banner.style.borderColor = '#d97706';
      banner.innerHTML = `
        <div style="color: #92400e;">
          ⚠️ <strong>Bạn đang duyệt với tư cách Khách:</strong> Mọi địa điểm và lộ trình lưu tạm sẽ <strong>tự động bị xóa sạch sau mỗi lần tải lại trang (reload)</strong>.
          <a href="login.html" style="font-weight: 800; color: #b45309; text-decoration: underline; margin-left: 6px;">Đăng nhập</a> hoặc
          <a href="login.html?tab=register" style="font-weight: 800; color: #b45309; text-decoration: underline; margin-left: 4px;">Đăng ký ngay</a> để lưu vĩnh viễn vào tài khoản của bạn!
        </div>
      `;
    }
  }

  renderUserBanner();

  // Tải danh sách địa điểm
  let allRestaurants = [...MOCK_RESTAURANTS];
  let allCategories = [...MOCK_CATEGORIES];

  if (!CONFIG.USE_MOCK) {
    try {
      const [resR, resC] = await Promise.all([
        fetchRestaurants({ lat: CONFIG.FALLBACK_LAT, lng: CONFIG.FALLBACK_LNG, radius: 25, type: 'all', limit: 100 }),
        fetchCategories()
      ]);
      if (resR.success && resR.data) allRestaurants = resR.data;
      if (resC.success && resC.data) allCategories = resC.data;
    } catch {
      console.log('Dùng dữ liệu cục bộ cho favorites');
    }
  }

  function populateCategories() {
    if (!filterCatEl) return;
    const visibleCats = activePlaceType === 'all'
      ? allCategories
      : allCategories.filter(c => (c.type_group || 'food') === activePlaceType);
    const prevVal = filterCatEl.value;
    filterCatEl.innerHTML = `<option value="">Tất cả danh mục (${visibleCats.length})</option>` +
      visibleCats.map(c => `<option value="${c.id}">${escapeText(c.name)}</option>`).join('');
    if (visibleCats.some(c => String(c.id) === String(prevVal))) {
      filterCatEl.value = prevVal;
    } else {
      filterCatEl.value = '';
    }
  }

  populateCategories();

  // ── Render danh sách Địa điểm đã lưu ──
  function renderPlaces() {
    const favoriteIds = getFavoriteIds();
    if (countBadgePlaces) countBadgePlaces.textContent = favoriteIds.length;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const selectedCat = filterCatEl?.value ? Number(filterCatEl.value) : null;

    let items = allRestaurants.filter(r => favoriteIds.includes(r.id));

    if (activePlaceType !== 'all') {
      items = items.filter(r => (r.place_type || r.category?.type_group || 'food') === activePlaceType);
    }

    if (selectedCat) {
      items = items.filter(r => r.category && r.category.id === selectedCat);
    }

    if (query) {
      items = items.filter(r =>
        r.name.toLowerCase().includes(query) ||
        r.address.toLowerCase().includes(query)
      );
    }

    if (activeMainTab === 'places' && countEl) {
      countEl.textContent = `${items.length} địa điểm`;
    }

    if (items.length === 0) {
      listPlacesEl.innerHTML = '';
      emptyPlacesEl.style.display = 'block';
      if (activeMainTab === 'places' && btnClearAll) {
        btnClearAll.style.display = favoriteIds.length > 0 ? 'inline-flex' : 'none';
      }
      return;
    }

    emptyPlacesEl.style.display = 'none';
    if (activeMainTab === 'places' && btnClearAll) {
      btnClearAll.style.display = 'inline-flex';
    }

    listPlacesEl.innerHTML = items.map(r => {
      const placeType = r.place_type || r.category?.type_group || 'food';
      const typeInfo = getPlaceTypeInfo(placeType);
      const durationStr = formatDuration(r.estimated_duration);

      return `
      <article class="fav-card" id="fav-card-${r.id}">
        <div class="fav-card__media">
          <img src="${escapeText(r.image_url)}" alt="${escapeText(r.name)}"
            loading="lazy" width="300" height="200"
            onerror="this.src='assets/img/placeholder.webp'">
          <button class="fav-card__btn-remove" data-id="${r.id}" title="Xóa khỏi yêu thích" aria-label="Xóa ${escapeText(r.name)} khỏi danh sách đã lưu">
            <svg class="ui-icon ui-icon--heart-filled" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          </button>
          <span class="fav-card__badge-cat">${categoryIcon(r.category)} <span>${escapeText(r.category?.name || 'Địa điểm')}</span></span>
        </div>
        <div class="fav-card__body">
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
            <span class="badge ${typeInfo.badgeClass}">${typeInfo.emoji} ${escapeText(typeInfo.label)}</span>
            ${durationStr ? `<span class="badge badge--duration">${icon('timer')} ${escapeText(durationStr)}</span>` : ''}
          </div>
          <div class="fav-card__header">
            <h3 class="fav-card__title"><a href="restaurant.html?id=${r.id}">${escapeText(r.name)}</a></h3>
            <div class="fav-card__rating">
              ${icon('star', 'ui-icon--star-filled')}
              <span>${r.rating}</span>
              <small>(${r.rating_count})</small>
            </div>
          </div>
          <p class="fav-card__address">
            ${icon('pin')} ${escapeText(r.address)}
          </p>
          <div class="fav-card__meta">
            <span class="fav-card__price">${escapeText(formatTicketOrPrice(r))}</span>
            <span class="fav-card__hours">${icon('clock')} ${r.is_24h ? 'Mở cả ngày 24/7' : `${r.open_time?.slice(0,5)} – ${r.close_time?.slice(0,5)}`}</span>
          </div>
          <div class="fav-card__actions">
            <a href="restaurant.html?id=${r.id}" class="btn btn--secondary btn--sm">Chi tiết</a>
            <a href="${getDirectionsUrl(r)}"
              target="_blank" rel="noopener noreferrer" class="btn btn--primary btn--sm">
              ${icon('direction')} Chỉ đường
            </a>
          </div>
        </div>
      </article>
    `;
    }).join('');

    // Sự kiện xóa từng địa điểm
    listPlacesEl.querySelectorAll('.fav-card__btn-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = Number(e.currentTarget.dataset.id);
        toggleFavorite(id);
        renderPlaces();
      });
    });
  }

  // ── Render danh sách Lộ trình đã lưu ──
  function renderItineraries() {
    const savedItins = getSavedItineraries();
    if (countBadgeItin) countBadgeItin.textContent = savedItins.length;

    if (activeMainTab === 'itineraries' && countEl) {
      countEl.textContent = `${savedItins.length} lộ trình`;
    }

    if (savedItins.length === 0) {
      listItinEl.innerHTML = '';
      emptyItinEl.style.display = 'block';
      if (activeMainTab === 'itineraries' && btnClearAll) {
        btnClearAll.style.display = 'none';
      }
      return;
    }

    emptyItinEl.style.display = 'none';
    if (activeMainTab === 'itineraries' && btnClearAll) {
      btnClearAll.style.display = 'inline-flex';
    }

    listItinEl.innerHTML = savedItins.map(item => {
      const vibeBadge = getVibeBadge(item.vibe);
      const totalKm = item.total_distance_km ?? item.summary?.total_distance_km ?? 0;
      const totalStops = item.total_stops || item.stops?.length || 0;
      const estHours = item.total_time_minutes
        ? (item.total_time_minutes / 60).toFixed(1)
        : (item.summary?.total_time_minutes ? (item.summary.total_time_minutes / 60).toFixed(1) : 4);
      const dateStr = formatSavedDate(item.saved_at);
      const gmapsUrl = item.google_maps_url || item.summary?.google_maps_url || getMultiStopDirectionsUrl(item.user_pos, (item.stops || []).map(s => s.place));

      return `
        <article class="saved-itin-card" id="saved-itin-${item.id}">
          <div class="saved-itin-card__header">
            <div>
              <h3 class="saved-itin-card__title">✨ ${escapeText(item.title || 'Lộ trình khám phá')}</h3>
              <div class="saved-itin-card__date">
                <span>⏰ Đã lưu: ${escapeText(dateStr)}</span>
              </div>
            </div>
            <span class="badge ${vibeBadge.class}">${vibeBadge.text}</span>
          </div>

          <div class="saved-itin-card__body">
            <div class="saved-itin-card__stats">
              <span class="stat-chip">📍 ${totalStops} chặng</span>
              <span class="stat-chip">🚗 ~${totalKm} km</span>
              <span class="stat-chip">⏱️ ~${estHours} giờ</span>
            </div>

            <!-- Danh sách chặng tóm tắt -->
            <div class="saved-itin-card__timeline">
              ${(item.stops || []).slice(0, 5).map((stop, sIdx) => {
                const p = stop.place || {};
                const stepNum = stop.step || stop.step_order || (sIdx + 1);
                return `
                  <div class="saved-itin-stop">
                    <span class="saved-itin-stop__badge">${stepNum}</span>
                    <span class="saved-itin-stop__name" title="${escapeText(p.name || '')}">${escapeText(p.name || 'Điểm ghé thăm')}</span>
                    <span class="saved-itin-stop__time">${escapeText(stop.time_slot || stop.suggested_time || '')}</span>
                  </div>
                `;
              }).join('')}
              ${(item.stops?.length > 5) ? `<div style="font-size:11px;color:var(--c-ink-faint);text-align:center;">+ ${item.stops.length - 5} chặng nữa...</div>` : ''}
            </div>

            <!-- Hành động -->
            <div class="saved-itin-card__actions">
              <a href="index.html?saved_itin=${item.id}" class="btn btn--primary btn--sm" title="Mở và vẽ tuyến đường trên Bản đồ">
                🗺️ Xem trên Bản đồ
              </a>
              <a href="${gmapsUrl}" target="_blank" rel="noopener noreferrer" class="btn btn--secondary btn--sm" title="Mở toàn bộ lộ trình trên ứng dụng Google Maps">
                🚗 Google Maps
              </a>
              <button type="button" class="btn btn--ghost btn--sm btn-copy-itin" data-id="${item.id}" title="Sao chép tóm tắt lộ trình">
                📋 Sao chép
              </button>
              <button type="button" class="btn btn--ghost btn--sm btn-delete-itin" data-id="${item.id}" style="color:var(--c-primary)" title="Xóa lộ trình này">
                🗑️ Xóa
              </button>
            </div>
          </div>
        </article>
      `;
    }).join('');

    // Gắn sự kiện Xóa từng lộ trình
    listItinEl.querySelectorAll('.btn-delete-itin').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.currentTarget.dataset.id;
        if (confirm('Bạn có chắc muốn xóa lộ trình này khỏi danh sách đã lưu?')) {
          deleteSavedItinerary(id);
          renderItineraries();
        }
      });
    });

    // Gắn sự kiện Sao chép tóm tắt lộ trình
    listItinEl.querySelectorAll('.btn-copy-itin').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.currentTarget.dataset.id;
        const savedList = getSavedItineraries();
        const found = savedList.find(it => it.id === id);
        if (found) {
          const lines = [
            `✨ ${found.title || 'Lộ trình khám phá Food & Trip Finder'}`,
            `📍 Quy mô: ${found.total_stops || found.stops?.length} chặng (~${found.total_distance_km} km)`,
            `⏱️ Thời lượng: ~${found.total_time_minutes ? (found.total_time_minutes/60).toFixed(1) : 4} giờ`,
            `-----------------------------`,
          ];
          (found.stops || []).forEach((st, idx) => {
            const p = st.place || {};
            lines.push(`${idx + 1}. [${st.time_slot || ''}] ${p.name || ''} — ${p.address || ''}`);
          });
          lines.push(`-----------------------------`);
          if (found.google_maps_url) {
            lines.push(`🚗 Điều hướng Google Maps: ${found.google_maps_url}`);
          }
          try {
            await navigator.clipboard.writeText(lines.join('\n'));
            alert('📋 Đã sao chép tóm tắt lộ trình vào bộ nhớ tạm!');
          } catch {
            alert('Không thể sao chép tự động.');
          }
        }
      });
    });
  }

  // ── Chuyển đổi Tab Chính (Địa điểm vs Lộ trình) ──
  function switchMainTab(tab) {
    activeMainTab = tab;
    if (tab === 'places') {
      tabBtnPlaces?.classList.add('fav-main-tab--active');
      tabBtnPlaces?.setAttribute('aria-selected', 'true');
      tabBtnItin?.classList.remove('fav-main-tab--active');
      tabBtnItin?.setAttribute('aria-selected', 'false');

      viewPlaces.style.display = 'block';
      viewItin.style.display = 'none';

      if (mainHeading) mainHeading.textContent = 'Địa Điểm Đã Lưu';
      if (mainDesc) mainDesc.textContent = 'Lưu lại những quán ăn, điểm du lịch và khu vui chơi yêu thích để dễ dàng xem lại, gọi điện và chỉ đường.';

      renderPlaces();
    } else {
      tabBtnItin?.classList.add('fav-main-tab--active');
      tabBtnItin?.setAttribute('aria-selected', 'true');
      tabBtnPlaces?.classList.remove('fav-main-tab--active');
      tabBtnPlaces?.setAttribute('aria-selected', 'false');

      viewPlaces.style.display = 'none';
      viewItin.style.display = 'block';

      if (mainHeading) mainHeading.textContent = 'Lộ Trình Đã Lưu';
      if (mainDesc) mainDesc.textContent = 'Các hành trình đi chơi & ẩm thực thông minh quanh TP.HCM bạn đã lập kế hoạch và lưu lại.';

      renderItineraries();
    }
  }

  tabBtnPlaces?.addEventListener('click', () => switchMainTab('places'));
  tabBtnItin?.addEventListener('click', () => switchMainTab('itineraries'));

  // Sự kiện Tabs phân loại địa điểm cấp 1
  if (typeTabsContainer) {
    typeTabsContainer.querySelectorAll('.place-type-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        typeTabsContainer.querySelectorAll('.place-type-tab').forEach(t => {
          t.classList.remove('place-type-tab--active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('place-type-tab--active');
        tab.setAttribute('aria-selected', 'true');
        activePlaceType = tab.dataset.type || 'all';
        populateCategories();
        renderPlaces();
      });
    });
  }

  // Sự kiện tìm kiếm & lọc địa điểm
  searchInput?.addEventListener('input', () => renderPlaces());
  filterCatEl?.addEventListener('change', () => renderPlaces());

  // Xóa toàn bộ
  btnClearAll?.addEventListener('click', async () => {
    if (activeMainTab === 'places') {
      if (confirm('Bạn có chắc muốn xóa tất cả các địa điểm đã lưu không?')) {
        saveFavoriteIds([]);
        if (auth.isLoggedIn()) {
          fetch('/api/favorites.php', { method: 'DELETE', credentials: 'same-origin' }).catch(() => {});
        }
        renderPlaces();
      }
    } else {
      if (confirm('Bạn có chắc muốn xóa tất cả các lộ trình đã lưu không?')) {
        saveItinerariesList([]);
        if (auth.isLoggedIn()) {
          fetch('/api/user_itineraries.php', { method: 'DELETE', credentials: 'same-origin' }).catch(() => {});
        }
        renderItineraries();
      }
    }
  });

  // Lắng nghe khi có sự thay đổi đăng nhập/đăng xuất để tự động nạp lại đúng tài khoản
  window.addEventListener('auth:change', async () => {
    renderUserBanner();
    if (auth.isLoggedIn()) {
      await syncUserDataFromServer();
    }
    renderPlaces();
    renderItineraries();
  });

  // Render khởi tạo ban đầu cho cả hai
  renderPlaces();
  renderItineraries();

  // Áp dụng tab active
  switchMainTab(activeMainTab);
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFavoritesPage);
  } else {
    initFavoritesPage();
  }
}
