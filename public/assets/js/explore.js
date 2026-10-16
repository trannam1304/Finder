/* ============================================================
   EXPLORE.JS — Danh sách quán dạng lưới
   ============================================================ */

import { MOCK_RESTAURANTS, MOCK_CATEGORIES } from './mock-data.js';
import { CONFIG } from './config.js';
import { fetchCategories, fetchRestaurants } from './api.js';
import { escapeText, formatPlaceType, formatTicketPrice, formatDuration } from './format.js';
import { icon, categoryIcon, renderStars } from './icons.js';

let restaurants = [...MOCK_RESTAURANTS];
let categories = [...MOCK_CATEGORIES];

// ── Theme ────────────────────────────────────────────────────
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

// ── State ────────────────────────────────────────────────────
let state = {
  search: '',
  type: 'all',
  categories: new Set(),
  prices: new Set(),
  minRating: 0,
  openNow: false,
  sort: 'rating',
};


// ── DOM refs ─────────────────────────────────────────────────
const grid        = document.getElementById('resto-grid');
const countEl     = document.getElementById('explore-count');
const searchInput = document.getElementById('explore-search');
const sortSelect  = document.getElementById('explore-sort');
const openNowCb   = document.getElementById('filter-open-now');
const resetBtn    = document.getElementById('btn-reset-filters');
const filterToggle = document.getElementById('btn-filter-toggle');
const filterPanel  = document.getElementById('explore-filters');

// ── Helper: is restaurant open now ───────────────────────────
function isOpenNow(r) {
  if (r.is_24h) return true;
  const now = new Date();
  const hhmm = (s) => {
    const [h, m] = s.split(':').map(Number);
    return h * 60 + m;
  };
  const cur   = now.getHours() * 60 + now.getMinutes();
  const open  = hhmm(r.open_time);
  const close = hhmm(r.close_time);
  return close < open ? (cur >= open || cur < close) : (cur >= open && cur < close);
}

// ── Helper: price label ───────────────────────────────────────
function priceLabel(level) {
  return ['', 'Bình dân', 'Trung bình', 'Cao cấp'][level] || '';
}

// ── Filter & sort restaurants ─────────────────────────────────
function filterAndSort() {
  let list = [...restaurants];

  // FE-01: Filter by Type Tab
  if (state.type && state.type !== 'all') {
    list = list.filter(r => (r.place_type || 'food') === state.type);
  }

  if (state.search) {
    const q = state.search.toLowerCase();
    list = list.filter(r =>
      r.name.toLowerCase().includes(q) ||
      r.address.toLowerCase().includes(q)
    );
  }

  if (state.categories.size > 0) {
    list = list.filter(r => state.categories.has(r.category.slug));
  }

  if (state.prices.size > 0) {
    list = list.filter(r => state.prices.has(String(r.price_level)));
  }

  if (state.minRating > 0) {
    list = list.filter(r => r.rating >= state.minRating);
  }

  if (state.openNow) {
    list = list.filter(r => isOpenNow(r));
  }

  switch (state.sort) {
    case 'rating':    list.sort((a, b) => b.rating - a.rating); break;
    case 'name':      list.sort((a, b) => a.name.localeCompare(b.name, 'vi')); break;
    case 'price_asc': list.sort((a, b) => a.price_min - b.price_min); break;
    case 'price_desc':list.sort((a, b) => b.price_min - a.price_min); break;
    case 'reviews':   list.sort((a, b) => b.rating_count - a.rating_count); break;
  }

  return list;
}

// ── Render grid ───────────────────────────────────────────────
function renderCard(r) {
  const open = isOpenNow(r);
  const placeType = r.place_type || 'food';
  const fmtPrice = r.price_min >= 1000
    ? `${(r.price_min / 1000).toFixed(0)}k–${(r.price_max / 1000).toFixed(0)}k`
    : `${r.price_min}–${r.price_max}`;

  const priceText = (placeType !== 'food' && r.ticket_price !== undefined)
    ? formatTicketPrice(r.ticket_price)
    : `${fmtPrice}đ`;

  const durBadge = r.estimated_duration
    ? `<span class="badge badge--duration">${formatDuration(r.estimated_duration)}</span>`
    : '';

  return `
    <a href="restaurant.html?id=${r.id}" class="resto-card" role="listitem" id="card-${r.id}" aria-label="${escapeText(r.name)}">
      <div class="resto-card__img-wrap">
        <img
          class="resto-card__img"
          src="${escapeText(r.image_url)}"
          alt="${escapeText(r.name)}"
          loading="lazy"
          width="320" height="240"
          onerror="this.src='assets/img/placeholder.webp'"
        >
        <div class="resto-card__badges">
          <span class="badge badge--type-${placeType}">${formatPlaceType(placeType)}</span>
          <span class="badge ${open ? 'badge--open' : 'badge--closed'}">
            <span class="badge__dot" aria-hidden="true"></span>${open ? 'Đang mở' : 'Đã đóng'}
          </span>
          <span class="badge badge--category">${categoryIcon(r.category)}<span>${escapeText(r.category.name)}</span></span>
          ${durBadge}
        </div>
        <button
          class="resto-card__fav"
          id="fav-${r.id}"
          aria-label="Lưu ${r.name}"
          aria-pressed="false"
          onclick="event.preventDefault(); toggleFav(${r.id}, this)"
        >${icon('heart')}</button>
      </div>
      <div class="resto-card__body">
        <div class="resto-card__name">${escapeText(r.name)}</div>
        <div class="resto-card__address">${icon('pin')}<span>${escapeText(r.address)}</span></div>
        <div class="resto-card__footer">
          <span class="stars" aria-label="Đánh giá ${r.rating}">
            <span class="stars__group">${renderStars(r.rating)}</span>
            <span class="stars__value">${r.rating}</span>
            <span class="stars__count">(${r.rating_count})</span>
          </span>
          <span class="resto-card__price">${priceText}</span>
        </div>
      </div>
    </a>`;
}


function renderSkeletons(count = 6) {
  return Array.from({ length: count }, () => `
    <div class="skeleton-card" role="listitem">
      <div class="skeleton skeleton-card__img"></div>
      <div class="skeleton-card__body">
        <div class="skeleton skeleton-card__line skeleton-card__line--w80"></div>
        <div class="skeleton skeleton-card__line skeleton-card__line--w50"></div>
        <div class="skeleton skeleton-card__line skeleton-card__line--w30"></div>
      </div>
    </div>`).join('');
}

function renderEmpty() {
  return `<div class="empty-state" style="grid-column:1/-1">
    <div class="empty-state__icon">${icon('empty')}</div>
    <div class="empty-state__title">Không tìm thấy quán nào</div>
    <div class="empty-state__desc">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</div>
  </div>`;
}

function render() {
  const list = filterAndSort();
  countEl.textContent = `${list.length} quán`;
  updatePriceCounts();

  if (list.length === 0) {
    grid.innerHTML = renderEmpty();
    return;
  }

  grid.innerHTML = list.map(renderCard).join('');
}

// ── Price counts ──────────────────────────────────────────────
function updatePriceCounts() {
  [1, 2, 3].forEach(p => {
    const el = document.getElementById(`count-price-${p}`);
    if (el) el.textContent = restaurants.filter(r => r.price_level === p).length;
  });
}

// ── Favourites ────────────────────────────────────────────────
const FAVS_KEY = 'ff-favs';
let favs = new Set(JSON.parse(localStorage.getItem(FAVS_KEY) || '[]'));

window.toggleFav = function(id, btn) {
  if (favs.has(id)) {
    favs.delete(id);
    btn.innerHTML = icon('heart');
    btn.setAttribute('aria-pressed', 'false');
  } else {
    favs.add(id);
    btn.innerHTML = icon('heart');
    btn.setAttribute('aria-pressed', 'true');
    btn.classList.add('resto-card__fav--active');
  }
  localStorage.setItem(FAVS_KEY, JSON.stringify([...favs]));
};

// ── Category filter render ────────────────────────────────────
function renderCategoryFilters() {
  const container = document.getElementById('filter-categories');
  container.innerHTML = categories.map(cat => `
    <label class="filter-option">
      <input type="checkbox" name="category" value="${cat.slug}" id="cat-${cat.slug}">
      <span>${categoryIcon(cat)} ${escapeText(cat.name)}</span>
      <span class="filter-option__count">
        ${restaurants.filter(r => r.category.slug === cat.slug).length}
      </span>
    </label>
  `).join('');

  container.querySelectorAll('input[name="category"]').forEach(cb => {
    cb.addEventListener('change', () => {
      if (cb.checked) state.categories.add(cb.value);
      else state.categories.delete(cb.value);
      render();
    });
  });
}

// ── Wire up controls ──────────────────────────────────────────
// Search with debounce
let searchTimer;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = searchInput.value;
    render();
  }, 250);
});

// Sort
sortSelect.addEventListener('change', () => {
  state.sort = sortSelect.value;
  render();
});

// Open now
openNowCb.addEventListener('change', () => {
  state.openNow = openNowCb.checked;
  render();
});

// Price checkboxes
document.querySelectorAll('input[name="price"]').forEach(cb => {
  cb.addEventListener('change', () => {
    if (cb.checked) state.prices.add(cb.value);
    else state.prices.delete(cb.value);
    render();
  });
});

// FE-01: Type Tabs in Explore
document.querySelectorAll('#explore-type-tabs [data-type]').forEach(tab => {
  tab.addEventListener('click', () => {
    const typeVal = tab.dataset.type || 'all';
    state.type = typeVal;

    document.querySelectorAll('#explore-type-tabs [data-type]').forEach(t => {
      const isSel = (t.dataset.type || 'all') === typeVal;
      t.classList.toggle('type-tab--active', isSel);
      t.setAttribute('aria-selected', isSel ? 'true' : 'false');
    });

    render();
  });
});

// Rating radios

document.querySelectorAll('input[name="rating"]').forEach(rb => {
  rb.addEventListener('change', () => {
    state.minRating = parseFloat(rb.value);
    render();
  });
});

// Reset
resetBtn.addEventListener('click', () => {
  state = { search: '', categories: new Set(), prices: new Set(), minRating: 0, openNow: false, sort: 'rating' };
  searchInput.value = '';
  sortSelect.value = 'rating';
  openNowCb.checked = false;
  document.querySelectorAll('#explore-filters input[type="checkbox"], #explore-filters input[type="radio"]').forEach(el => {
    el.checked = el.value === '0' && el.name === 'rating';
  });
  render();
});

// Mobile filter toggle
filterToggle.addEventListener('click', () => {
  const open = filterPanel.classList.toggle('explore-filters--open');
  filterToggle.setAttribute('aria-expanded', String(open));
  filterToggle.innerHTML = `${icon(open ? 'close' : 'filter')} ${open ? 'Đóng bộ lọc' : 'Bộ lọc'}`;
});

// ── Initial render with skeleton ─────────────────────────────
grid.innerHTML = renderSkeletons();

async function loadExploreData() {
  if (!CONFIG.USE_MOCK) {
    try {
      const [restaurantResult, categoryResult] = await Promise.all([
        fetchRestaurants({ lat: CONFIG.FALLBACK_LAT, lng: CONFIG.FALLBACK_LNG, radius: 5, sort: 'rating', limit: CONFIG.MAX_LIMIT }),
        fetchCategories(),
      ]);
      if (restaurantResult.success) restaurants = restaurantResult.data;
      if (categoryResult.success) categories = categoryResult.data;
    } catch {
      // Keep the bundled preview data available if the API is temporarily unavailable.
    }
  }
  renderCategoryFilters();
  render();
}

setTimeout(loadExploreData, 250);
