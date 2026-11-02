/* ============================================================
   RESTAURANT.JS — Trang chi tiết quán ăn
   ============================================================ */

import { MOCK_RESTAURANTS } from './mock-data.js';
import { fetchRestaurant, fetchReviews, submitReview } from './api.js';
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

// ── TÍNH NĂNG ĐÁNH GIÁ HAI CHIỀU (USER REVIEW) ─────────────────
const btnToggleReview = document.getElementById('btn-toggle-review-form');
const reviewFormWrap = document.getElementById('review-form-wrap');
if (btnToggleReview && reviewFormWrap) {
  btnToggleReview.addEventListener('click', () => {
    const isHidden = reviewFormWrap.style.display === 'none';
    reviewFormWrap.style.display = isHidden ? 'block' : 'none';
    btnToggleReview.textContent = isHidden ? '✕ Đóng form' : '✍️ Viết đánh giá';
  });
}

// Xử lý chọn số sao đánh giá (Interactive Star Rating)
const starRatingInput = document.getElementById('rev-rating');
const starRatingLabel = document.getElementById('star-rating-label');
const starButtons = document.querySelectorAll('#star-btns span');
const starLabels = {
  5: 'Tuyệt vời (5/5)',
  4: 'Rất tốt (4/5)',
  3: 'Bình thường (3/5)',
  2: 'Kém (2/5)',
  1: 'Rất tệ (1/5)',
};

function setStarRating(ratingVal) {
  if (starRatingInput) starRatingInput.value = String(ratingVal);
  if (starRatingLabel) starRatingLabel.textContent = starLabels[ratingVal] || `${ratingVal} sao`;
  starButtons.forEach(btn => {
    const s = parseInt(btn.dataset.star, 10);
    btn.style.color = s <= ratingVal ? '#f59e0b' : '#cbd5e1';
  });
}

starButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const val = parseInt(btn.dataset.star, 10);
    setStarRating(val);
  });
});

// Render danh sách reviews và tổng hợp phân bố sao từ API
function renderReviewUI(summaryData, reviewList) {
  const currentRating = summaryData?.average_rating || r.rating || 5.0;
  const currentTotal = summaryData?.total_reviews ?? r.rating_count ?? 0;
  const distObj = summaryData?.distribution || { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };

  const ratingSummaryEl = document.getElementById('rating-summary');
  if (ratingSummaryEl) {
    ratingSummaryEl.innerHTML = `
      <div class="rating-big">
        <div class="rating-big__score">${currentRating.toFixed(1)}</div>
        <div class="rating-big__stars">${starsHtml(currentRating)}</div>
        <div class="rating-big__count">${currentTotal} đánh giá</div>
      </div>
      <div class="rating-bars">
        ${[5, 4, 3, 2, 1].map(star => {
          const count = distObj[star] || 0;
          const pct = currentTotal > 0 ? Math.round(count / currentTotal * 100) : 0;
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
  }

  const reviewListEl = document.getElementById('review-list');
  if (reviewListEl) {
    if (!reviewList || reviewList.length === 0) {
      reviewListEl.innerHTML = `
        <div style="padding:var(--sp-5); text-align:center; color:var(--c-ink-soft); font-size:var(--fs-sm); border:1px dashed var(--c-border); border-radius:var(--r-md); margin-top:var(--sp-4);">
          Chưa có đánh giá nào cho địa điểm này. Hãy là người đầu tiên chia sẻ trải nghiệm nhé!
        </div>
      `;
    } else {
      reviewListEl.innerHTML = reviewList.map(review => {
        const author = escapeText(review.author_name || 'Khách');
        const firstLetter = author.charAt(0).toUpperCase();
        const dateStr = escapeText(review.time_ago || review.created_at || 'Gần đây');
        const commentStr = escapeText(review.comment || 'Không có nhận xét chi tiết.');
        const rVal = review.rating || 5;

        return `
          <div class="review-card" style="margin-top:var(--sp-3);">
            <div class="review-card__header">
              <div class="review-card__avatar">${firstLetter}</div>
              <div>
                <div class="review-card__name">${author}</div>
                <div class="review-card__stars">${renderStars(rVal)}</div>
              </div>
              <span class="review-card__date">${dateStr}</span>
            </div>
            <div class="review-card__text">${commentStr}</div>
          </div>
        `;
      }).join('');
    }
  }
}

// Tải dữ liệu review thật từ API
async function loadReviews() {
  try {
    const res = await fetchReviews(r.id);
    if (res.success && res.data) {
      renderReviewUI(res.data.summary, res.data.reviews);
    } else {
      renderReviewUI(null, []);
    }
  } catch (err) {
    console.warn('Lỗi tải đánh giá từ API:', err);
    renderReviewUI(null, []);
  }
}

loadReviews();

// Gửi form đánh giá
const formAddReview = document.getElementById('form-add-review');
if (formAddReview) {
  formAddReview.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btnSubmit = document.getElementById('btn-submit-review');
    const authorVal = (document.getElementById('rev-author')?.value || '').trim();
    const ratingVal = parseInt(document.getElementById('rev-rating')?.value || '5', 10);
    const commentVal = (document.getElementById('rev-comment')?.value || '').trim();

    if (!authorVal) {
      showToast('Vui lòng nhập tên của bạn');
      return;
    }

    const origText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = 'Đang gửi...';

    try {
      const result = await submitReview({
        restaurant_id: r.id,
        author_name: authorVal,
        rating: ratingVal,
        comment: commentVal,
      });

      if (result.success) {
        showToast('🎉 ' + (result.data?.message || 'Đã gửi đánh giá thành công!'));
        formAddReview.reset();
        setStarRating(5);
        if (reviewFormWrap) reviewFormWrap.style.display = 'none';
        if (btnToggleReview) btnToggleReview.textContent = '✍️ Viết đánh giá';
        // Tải lại danh sách review và cập nhật số điểm
        await loadReviews();
      } else {
        showToast('⚠️ ' + (result.error?.message || 'Không thể gửi đánh giá lúc này.'));
      }
    } catch (err) {
      showToast('⚠️ Không thể gửi đánh giá. Vui lòng thử lại sau.');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = origText;
    }
  });
}

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
