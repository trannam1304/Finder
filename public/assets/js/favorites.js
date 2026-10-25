/* ============================================================
   FAVORITES.JS — Quản lý địa điểm yêu thích đã lưu (Đa loại hình)
   Hoạt động độc lập bằng localStorage trên Web tĩnh & Backend API
   ============================================================ */

import { MOCK_RESTAURANTS, MOCK_CATEGORIES } from './mock-data.js';
import { fetchRestaurants, fetchCategories } from './api.js';
import { CONFIG } from './config.js';
import { escapeText, formatTicketOrPrice, formatDuration, getPlaceTypeInfo, getDirectionsUrl } from './format.js';
import { icon, categoryIcon } from './icons.js';

const STORAGE_KEY = 'food_finder_favorites';

// ── Lấy danh sách ID đã lưu ──
export function getFavoriteIds() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [1, 19, 35]; // Mặc định gợi ý 3 địa điểm (Ẩm thực, Du lịch, Vui chơi) khi mới mở
  } catch {
    return [1, 19, 35];
  }
}

// ── Lưu danh sách ID ──
export function saveFavoriteIds(ids) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
  } catch (e) {
    console.error('Không thể lưu vào localStorage', e);
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
  return saved;
}

// ── Khởi tạo trang favorites.html ──
async function initFavoritesPage() {
  const listEl = document.getElementById('favorites-list');
  const emptyEl = document.getElementById('favorites-empty');
  const countEl = document.getElementById('favorites-count');
  const filterCatEl = document.getElementById('filter-category');
  const btnClearAll = document.getElementById('btn-clear-all');
  const searchInput = document.getElementById('search-favorites');
  const typeTabsContainer = document.getElementById('favorites-type-tabs');

  if (!listEl) return;

  let activePlaceType = 'all';

  // Theme toggle
  const btnTheme = document.getElementById('btn-theme');
  const root = document.documentElement;
  function applyTheme(theme) {
    root.dataset.theme = theme;
    if (btnTheme) btnTheme.innerHTML = icon(theme === 'dark' ? 'sun' : 'moon');
    localStorage.setItem('ff-theme', theme);
  }
  const savedTheme = localStorage.getItem('ff-theme') ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  applyTheme(savedTheme);
  if (btnTheme) btnTheme.addEventListener('click', () => {
    applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
  });

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

  function render() {
    const favoriteIds = getFavoriteIds();
    const query = (searchInput?.value || '').trim().toLowerCase();
    const selectedCat = filterCatEl?.value ? Number(filterCatEl.value) : null;

    // Lọc các địa điểm có trong danh sách yêu thích
    let items = allRestaurants.filter(r => favoriteIds.includes(r.id));

    // Lọc theo nhóm loại hình cấp 1 (Task FE-01)
    if (activePlaceType !== 'all') {
      items = items.filter(r => (r.place_type || r.category?.type_group || 'food') === activePlaceType);
    }

    // Lọc theo danh mục
    if (selectedCat) {
      items = items.filter(r => r.category && r.category.id === selectedCat);
    }

    // Lọc theo từ khóa tìm kiếm
    if (query) {
      items = items.filter(r =>
        r.name.toLowerCase().includes(query) ||
        r.address.toLowerCase().includes(query)
      );
    }

    if (countEl) {
      countEl.textContent = `${items.length} địa điểm`;
    }

    if (items.length === 0) {
      listEl.innerHTML = '';
      emptyEl.style.display = 'block';
      if (btnClearAll) btnClearAll.style.display = favoriteIds.length > 0 ? 'inline-flex' : 'none';
      return;
    }

    emptyEl.style.display = 'none';
    if (btnClearAll) btnClearAll.style.display = 'inline-flex';

    listEl.innerHTML = items.map(r => {
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

    // Gắn sự kiện xóa từng địa điểm
    listEl.querySelectorAll('.fav-card__btn-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = Number(e.currentTarget.dataset.id);
        toggleFavorite(id);
        render();
      });
    });
  }

  // Sự kiện Tabs phân loại địa điểm cấp 1 (Task FE-01)
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
        render();
      });
    });
  }

  // Sự kiện tìm kiếm & lọc
  searchInput?.addEventListener('input', () => render());
  filterCatEl?.addEventListener('change', () => render());

  // Xóa toàn bộ
  btnClearAll?.addEventListener('click', () => {
    if (confirm('Bạn có chắc muốn xóa tất cả các địa điểm đã lưu không?')) {
      saveFavoriteIds([]);
      render();
    }
  });

  render();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFavoritesPage);
} else {
  initFavoritesPage();
}
