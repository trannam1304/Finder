/* ============================================================
   RESTAURANT.JS — Trang chi tiết quán ăn
   ============================================================ */

import { MOCK_RESTAURANTS } from './mock-data.js';
import { fetchRestaurant } from './api.js';
import { CONFIG } from './config.js';
import { escapeText, getDirectionsUrl, getGoogleMapsPinUrl, getPlaceTypeInfo, formatTicketOrPrice, formatDuration, formatBestTimeSlot, formatPrice } from './format.js';
import { icon, categoryIcon, renderStars } from './icons.js';
import { isFavorite, toggleFavorite } from './favorites.js';

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

async function bootstrapRestaurant() {
// ── Load restaurant from URL param ────────────────────────────
const params = new URLSearchParams(window.location.search);
const id = parseInt(params.get('id'), 10);
let restaurant = MOCK_RESTAURANTS.find(r => r.id === id) || MOCK_RESTAURANTS[0];
if (!CONFIG.USE_MOCK && Number.isInteger(id)) {
  try {
    const result = await fetchRestaurant(id);
    if (result.success && result.data) restaurant = result.data;
  } catch {
    showToast('Không tải được dữ liệu mới nhất. Đang hiển thị dữ liệu xem trước.');
  }
}

// ── Helpers ───────────────────────────────────────────────────
function isOpenNow(r) {
  if (r.is_24h) return true;
  if (!r.open_time || !r.close_time) return true;
  const hhmm = (s) => { const [h, m] = s.split(':').map(Number); return h * 60 + m; };
  const now = new Date();
  const cur = now.getHours() * 60 + now.getMinutes();
  const open = hhmm(r.open_time);
  const close = hhmm(r.close_time);
  return close < open ? (cur >= open || cur < close) : (cur >= open && cur < close);
}

function fmtTime(t) {
  return (t || '').slice(0, 5);
}

function priceLabel(level) {
  return ['', 'Bình dân', 'Trung bình', 'Cao cấp'][level] || '';
}

function starsHtml(rating) {
  return `<span class="stars__group">${renderStars(rating)}</span>`;
}

// ── Populate page ─────────────────────────────────────────────
const r = restaurant;
const open = isOpenNow(r);
const esc = escapeText;
const placeType = r.place_type || r.category?.type_group || 'food';
const typeInfo = getPlaceTypeInfo(placeType);
const durationText = formatDuration(r.estimated_duration || 60);
const ticketOrPriceText = formatTicketOrPrice(r);
const timeSlotText = formatBestTimeSlot(r.best_time_slot);

// Page title & meta
document.title = `${r.name} — Food Finder`;
document.getElementById('page-title').textContent = `${r.name} — Food Finder`;

// Hero
document.getElementById('hero-img').src = r.image_url;
document.getElementById('hero-img').alt = r.name;
document.getElementById('hero-name').textContent = r.name;
document.getElementById('hero-badges').innerHTML = `
  <span class="badge ${typeInfo.badgeClass}">
    ${esc(typeInfo.fullLabel)}
  </span>
  <span class="badge ${open ? 'badge--open' : 'badge--closed'}">
    <span class="badge__dot" aria-hidden="true"></span>${open ? 'Đang mở' : 'Đã đóng'}
  </span>
  <span class="badge badge--category">${categoryIcon(r.category)}<span>${esc(r.category.name)}</span></span>
  ${durationText ? `<span class="badge badge--duration">⏱ ${esc(durationText)}</span>` : ''}
  <span class="badge" style="background:var(--c-surface);color:var(--c-ink-soft)">
    ${priceLabel(r.price_level)}
  </span>
`;

// Action buttons
document.getElementById('btn-directions').href = getDirectionsUrl(r);
document.getElementById('btn-call').href = `tel:${encodeURIComponent(r.phone || '')}`;

const btnFav = document.getElementById('btn-fav');
const favIcon = document.getElementById('fav-icon');
const favText = document.getElementById('fav-text');

function updateFavState() {
  const saved = isFavorite(r.id);
  if (favIcon) {
    favIcon.setAttribute('fill', saved ? 'var(--c-primary)' : 'none');
    favIcon.setAttribute('stroke', saved ? 'var(--c-primary)' : 'currentColor');
  }
  if (favText) {
    favText.textContent = saved ? 'Đã lưu' : 'Lưu địa điểm';
  }
  if (btnFav) {
    btnFav.style.color = saved ? 'var(--c-primary)' : '';
  }
}
updateFavState();

if (btnFav) {
  btnFav.addEventListener('click', () => {
    const isNowSaved = toggleFavorite(r.id);
    updateFavState();
    showToast(isNowSaved ? '❤️ Đã lưu vào danh sách yêu thích!' : 'Đã bỏ lưu.');
  });
}

document.getElementById('btn-share').addEventListener('click', async () => {
  const url = window.location.href;
  if (navigator.share) {
    try {
      await navigator.share({ title: r.name, url });
    } catch (_) {}
  } else {
    await navigator.clipboard.writeText(url);
    showToast('Đã sao chép đường dẫn!');
  }
});

// Info rows (Task FE-03: Loại hình, Giá vé / Khoảng giá, Thời lượng đề xuất, Khung giờ lý tưởng)
const ticketDetailHtml = placeType !== 'food'
  ? (Number(r.ticket_price) > 0
      ? `<strong>Có phí: ${Number(r.ticket_price).toLocaleString('vi-VN')}đ / vé</strong>${r.price_max > r.ticket_price ? ` (Chi phí dự kiến: ${formatPrice(r.price_min, r.price_max)})` : ''}`
      : `<strong>Vé miễn phí (Vào cổng tự do)</strong>${r.price_max > 0 ? ` — Dịch vụ/chi tiêu thêm: ${formatPrice(r.price_min, r.price_max)}` : ''}`)
  : `<strong>${esc(ticketOrPriceText)} / người</strong> (${esc(priceLabel(r.price_level))})`;

document.getElementById('info-rows').innerHTML = `
  <div class="info-row">
    <div class="info-row__icon">${icon('pin')}</div>
    <div>
      <div class="info-row__label">Địa chỉ</div>
      <div class="info-row__value">${esc(r.address)}</div>
    </div>
  </div>
  <div class="info-row">
    <div class="info-row__icon">${categoryIcon(r.category)}</div>
    <div>
      <div class="info-row__label">Phân loại & Danh mục</div>
      <div class="info-row__value">
        <span class="badge ${typeInfo.badgeClass}" style="margin-right:6px">${esc(typeInfo.fullLabel)}</span>
        <span>${esc(r.category.name)}</span>
      </div>
    </div>
  </div>
  <div class="info-row">
    <div class="info-row__icon">${icon(placeType === 'food' ? 'cash' : 'ticket')}</div>
    <div>
      <div class="info-row__label">${placeType === 'food' ? 'Khoảng giá tham khảo' : 'Giá vé vào cổng & Chi phí'}</div>
      <div class="info-row__value">${ticketDetailHtml}</div>
    </div>
  </div>
  <div class="info-row">
    <div class="info-row__icon">${icon('timer')}</div>
    <div>
      <div class="info-row__label">Thời lượng trải nghiệm & Thời điểm lý tưởng</div>
      <div class="info-row__value">
        <strong>${esc(durationText || 'Gợi ý: 60 phút')}</strong> &bull; Lý tưởng: ${esc(timeSlotText)}
      </div>
    </div>
  </div>
  <div class="info-row">
    <div class="info-row__icon">${icon('clock')}</div>
    <div>
      <div class="info-row__label">Giờ mở cửa</div>
      <div class="info-row__value">
        ${r.is_24h ? '24/7 — Luôn mở cửa' : `${fmtTime(r.open_time)} – ${fmtTime(r.close_time)}`}
        <span class="badge ${open ? 'badge--open' : 'badge--closed'}" style="margin-left:8px">
          ${open ? 'Đang mở' : 'Đã đóng'}
        </span>
      </div>
    </div>
  </div>
  <div class="info-row">
    <div class="info-row__icon">${icon('phone')}</div>
    <div>
      <div class="info-row__label">Điện thoại liên hệ</div>
      <div class="info-row__value"><a href="tel:${encodeURIComponent(r.phone || '')}">${esc(r.phone || 'Chưa cập nhật')}</a></div>
    </div>
  </div>
  ${r.note ? `
  <div class="info-row">
    <div class="info-row__icon">${icon('plate')}</div>
    <div>
      <div class="info-row__label">Điểm nổi bật</div>
      <div class="info-row__value">${esc(r.note)}</div>
    </div>
  </div>` : ''}
`;

// Gallery — use different images from the same folder
const galleryImages = [
  r.image_url,
  'assets/img/com_tam.jpg',
  'assets/img/pho_bo.jpg',
  'assets/img/banh_mi.jpg',
  'assets/img/lau_bo.jpg',
  'assets/img/mi_quang.jpg',
  'assets/img/bun_bo_hue.jpg',
  'assets/img/highlands.jpg',
].filter((v, i, a) => a.indexOf(v) === i).slice(0, 8);

document.getElementById('gallery').innerHTML = galleryImages.map((src, i) => `
  <div class="gallery__item" id="gallery-item-${i}" role="button" tabindex="0" aria-label="Xem ảnh ${i + 1}">
    <img src="${esc(src)}" alt="Ảnh quán ${i + 1}" loading="lazy" width="120" height="120"
      onerror="this.src='assets/img/placeholder.webp'">
  </div>
`).join('');

// Rating summary (mock distribution)
const totalReviews = r.rating_count;
const dist = [
  Math.floor(totalReviews * 0.60),
  Math.floor(totalReviews * 0.25),
  Math.floor(totalReviews * 0.08),
  Math.floor(totalReviews * 0.05),
  Math.floor(totalReviews * 0.02),
];

document.getElementById('rating-summary').innerHTML = `
  <div class="rating-big">
    <div class="rating-big__score">${r.rating}</div>
    <div class="rating-big__stars">${starsHtml(r.rating)}</div>
    <div class="rating-big__count">${r.rating_count} đánh giá</div>
  </div>
  <div class="rating-bars">
    ${[5,4,3,2,1].map((star, i) => {
      const count = dist[i];
      const pct   = totalReviews > 0 ? Math.round(count / totalReviews * 100) : 0;
      return `
      <div class="rating-bar">
        <span class="rating-bar__label">${star} ${icon('star', 'ui-icon--star-filled')}</span>
        <div class="rating-bar__track">
          <div class="rating-bar__fill" style="width:${pct}%"></div>
        </div>
        <span style="font-size:var(--fs-xs);color:var(--c-ink-faint);width:28px">${pct}%</span>
      </div>`;
    }).join('')}
  </div>
`;

// Mock reviews
const MOCK_REVIEWS = [
  { name: 'Nguyễn Minh Tuấn', rating: 5, date: '2 ngày trước', text: 'Quán ngon lắm! Thức ăn tươi, nhân viên phục vụ nhiệt tình. Mình đã ghé nhiều lần và lần nào cũng hài lòng.' },
  { name: 'Trần Thị Lan', rating: 4, date: '1 tuần trước', text: 'Đồ ăn vừa miệng, giá cả hợp lý. Không gian khá ổn tuy nhiên hơi ồn vào buổi trưa.' },
  { name: 'Lê Văn Bình', rating: 5, date: '2 tuần trước', text: 'Đây là quán yêu thích của tôi ở khu vực này. Phục vụ nhanh, đồ ăn nóng hổi.' },
  { name: 'Phạm Thị Hoa', rating: 4, date: '3 tuần trước', text: 'Hương vị chuẩn vị, đặc biệt phần nước dùng rất ngon. Sẽ quay lại!' },
];

document.getElementById('review-list').innerHTML = MOCK_REVIEWS.map(review => `
  <div class="review-card">
    <div class="review-card__header">
      <div class="review-card__avatar">${review.name.charAt(0)}</div>
      <div>
        <div class="review-card__name">${review.name}</div>
        <div class="review-card__stars">${renderStars(review.rating)}</div>
      </div>
      <span class="review-card__date">${review.date}</span>
    </div>
    <div class="review-card__text">${review.text}</div>
  </div>
`).join('');

// Nearby restaurants
const nearby = MOCK_RESTAURANTS
  .filter(n => n.id !== r.id)
  .slice(0, 4);

document.getElementById('nearby-list').innerHTML = nearby.map(n => `
  <a href="restaurant.html?id=${n.id}" style="display:flex;align-items:center;gap:var(--sp-3);padding:var(--sp-3) 0;border-bottom:1px solid var(--c-border);text-decoration:none;color:inherit;transition:background var(--ease-out)">
    <img src="${esc(n.image_url)}" alt="${esc(n.name)}" width="48" height="48"
      style="width:48px;height:48px;border-radius:var(--r-md);object-fit:cover;flex-shrink:0"
      onerror="this.src='assets/img/placeholder.webp'">
    <div style="flex:1;min-width:0">
      <div style="font-size:var(--fs-sm);font-weight:600;color:var(--c-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(n.name)}</div>
      <div style="font-size:var(--fs-xs);color:var(--c-ink-faint)">${esc(n.category.name)}</div>
    </div>
    <div style="font-size:var(--fs-xs);font-weight:700;color:var(--c-ink);display:inline-flex;align-items:center;gap:3px">${icon('star', 'ui-icon--star-filled')} <span>${n.rating}</span></div>
  </a>
`).join('');

// ── Mini Leaflet map ──────────────────────────────────────────
if (window.L) {
  const map = L.map('mini-map', {
    center: [r.lat, r.lng],
    zoom: 16,
    zoomControl: false,
    scrollWheelZoom: false,
    dragging: false,
    attributionControl: false,
  });

  const tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);
  tileLayer.on('load', () => { window.__tilesLoaded = true; });

  const markerColor = placeType === 'tourism' ? '#0284c7' : (placeType === 'entertainment' ? '#f59e0b' : 'var(--c-primary)');
  const icon = L.divIcon({
    className: '',
    html: `<div style="
      width:36px;height:36px;
      background:${markerColor};
      border:3px solid #121212;
      border-radius:50% 50% 50% 0;
      transform:rotate(-45deg);
      box-shadow:0 2px 8px rgba(0,0,0,.3);
    "></div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
  });

  const popup = document.createElement('div');
  const popupName = document.createElement('strong'); popupName.textContent = r.name;
  const popupAddress = document.createElement('div'); popupAddress.textContent = r.address;
  popupAddress.style.cssText = 'font-size:12px;color:var(--c-ink-soft);margin-top:2px;';
  const popupAction = document.createElement('div');
  popupAction.style.marginTop = '8px';
  popupAction.style.display = 'flex';
  popupAction.style.gap = '6px';
  const popupDir = document.createElement('a');
  popupDir.className = 'btn btn--primary btn--sm';
  popupDir.textContent = 'Chỉ đường';
  popupDir.href = getDirectionsUrl(r);
  popupDir.target = '_blank';
  popupDir.rel = 'noopener noreferrer';
  popupAction.appendChild(popupDir);
  const popupGmap = document.createElement('a');
  popupGmap.className = 'btn btn--ghost btn--sm';
  popupGmap.textContent = 'Google Maps';
  popupGmap.href = getGoogleMapsPinUrl(r);
  popupGmap.target = '_blank';
  popupGmap.rel = 'noopener noreferrer';
  popupGmap.title = 'Xem vị trí trên Google Maps';
  popupAction.appendChild(popupGmap);
  popup.append(popupName, popupAddress, popupAction);
  L.marker([r.lat, r.lng], { icon }).addTo(map).bindPopup(popup).openPopup();
} else {
  document.getElementById('mini-map').innerHTML =
    `<div style="height:100%;display:flex;align-items:center;justify-content:center;gap:8px;color:var(--c-ink-faint);font-size:var(--fs-sm)">${icon('pin')} ${esc(r.address)}</div>`;
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.style.cssText = `
    background:var(--c-surface);color:var(--c-ink);
    border:1px solid var(--c-border);border-left:4px solid var(--c-primary);
    padding:var(--sp-3) var(--sp-4);border-radius:var(--r-md);
    box-shadow:var(--sh-2);font-size:var(--fs-sm);
  `;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

}

bootstrapRestaurant();
