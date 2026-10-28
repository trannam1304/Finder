/* ============================================================
   LIST.JS — Render danh sách quán, skeleton, empty/error state
   ============================================================ */

import { formatDistance, formatPrice, isOpenNow, renderStars, getPlaceTypeInfo, formatTicketOrPrice, formatDuration } from './format.js';
import { CONFIG } from './config.js';
import { icon, categoryIcon } from './icons.js';

let onRowClick = null;

/**
 * Đặt callback khi click vào hàng quán
 */
export function setRowClickHandler(handler) {
  onRowClick = handler;
}

/**
 * Render danh sách quán vào container
 * @param {HTMLElement} listEl — <ul> element
 * @param {Array} restaurants — mảng quán ăn / địa điểm
 * @param {number|null} selectedId — id quán đang chọn
 */
export function renderList(listEl, restaurants, selectedId = null) {
  listEl.innerHTML = '';

  if (!restaurants.length) {
    renderEmpty(listEl);
    return;
  }

  restaurants.forEach(r => {
    const li = document.createElement('li');

    const btn = document.createElement('button');
    btn.className = `resto-row${r.id === selectedId ? ' resto-row--active' : ''}`;
    btn.type = 'button';
    btn.setAttribute('data-id', r.id);
    btn.setAttribute('aria-label', `${r.name}, ${formatDistance(r.distance_km)}`);

    // Image
    const img = document.createElement('img');
    img.className = 'resto-row__img';
    img.src = r.image_url || CONFIG.PLACEHOLDER_IMG;
    img.alt = r.name;
    img.width = 72;
    img.height = 72;
    img.loading = 'lazy';
    img.decoding = 'async';
    img.addEventListener('error', () => { img.src = CONFIG.PLACEHOLDER_IMG; }, { once: true });

    // Body
    const body = document.createElement('div');
    body.className = 'resto-row__body';

    // Name
    const nameEl = document.createElement('div');
    nameEl.className = 'resto-row__name';
    nameEl.textContent = r.name;

    // Info line: place_type badge + category chip + ticket/price
    const info = document.createElement('div');
    info.className = 'resto-row__info';

    const placeType = r.place_type || r.category?.type_group || 'food';
    const typeInfo = getPlaceTypeInfo(placeType);

    const typeBadge = document.createElement('span');
    typeBadge.className = `badge ${typeInfo.badgeClass}`;
    typeBadge.textContent = `${typeInfo.emoji} ${typeInfo.label}`;
    info.appendChild(typeBadge);

    const catBadge = document.createElement('span');
    catBadge.className = 'badge badge--category';
    catBadge.innerHTML = `${categoryIcon(r.category)}<span>${r.category?.name || ''}</span>`;
    info.appendChild(catBadge);

    const priceText = formatTicketOrPrice(r);
    if (priceText) {
      const priceEl = document.createElement('span');
      priceEl.className = 'resto-row__price';
      priceEl.textContent = priceText;
      info.appendChild(priceEl);
    }

    // Meta line: open status + duration + rating
    const meta = document.createElement('div');
    meta.className = 'resto-row__meta';
    meta.style.flexWrap = 'wrap';

    const openStatus = isOpenNow(r.open_time, r.close_time, r.is_24h);
    if (openStatus !== null) {
      const statusBadge = document.createElement('span');
      statusBadge.className = `badge ${openStatus ? 'badge--open' : 'badge--closed'}`;
      statusBadge.innerHTML = `<span class="badge__dot" aria-hidden="true"></span>${openStatus ? 'Đang mở' : 'Đã đóng'}`;
      meta.appendChild(statusBadge);
    }

    const durationText = formatDuration(r.estimated_duration);
    if (durationText) {
      const durBadge = document.createElement('span');
      durBadge.className = 'badge badge--duration';
      durBadge.textContent = `⏱ ${durationText}`;
      meta.appendChild(durBadge);
    }

    if (r.rating > 0) {
      const starsEl = document.createElement('span');
      starsEl.className = 'stars';
      starsEl.innerHTML = `<span class="stars__group">${renderStars(r.rating)}</span>`;
      const valSpan = document.createElement('span');
      valSpan.className = 'stars__value';
      valSpan.textContent = r.rating.toFixed(1);
      starsEl.appendChild(valSpan);

      if (r.rating_count) {
        const countSpan = document.createElement('span');
        countSpan.className = 'stars__count';
        countSpan.textContent = `(${r.rating_count})`;
        starsEl.appendChild(countSpan);
      }
      meta.appendChild(starsEl);
    }

    // Distance (aligned right)
    const distEl = document.createElement('div');
    distEl.className = 'resto-row__distance';
    distEl.textContent = r.distance_km !== undefined ? formatDistance(r.distance_km) : '';

    body.appendChild(nameEl);
    body.appendChild(info);
    body.appendChild(meta);

    btn.appendChild(img);
    btn.appendChild(body);
    btn.appendChild(distEl);

    // Click handler
    btn.addEventListener('click', () => {
      if (onRowClick) onRowClick(r);
    });

    li.appendChild(btn);
    listEl.appendChild(li);
  });
}

/**
 * Render trạng thái rỗng
 */
function renderEmpty(listEl) {
  const div = document.createElement('div');
  div.className = 'empty-state';
  div.innerHTML = `
    <div class="empty-state__icon">${icon('empty')}</div>
    <div class="empty-state__title">Không có quán nào</div>
    <div class="empty-state__desc">Không có quán nào trong bán kính này. Nới bán kính hoặc quay về khu vực trải nghiệm.</div>
    <button class="btn btn--primary btn--sm" type="button" data-action="reset-location" style="margin-top:12px;cursor:pointer">
      📍 Về ĐH Tôn Đức Thắng (Quận 7)
    </button>
  `;
  const resetBtn = div.querySelector('[data-action="reset-location"]');
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('food-finder:reset-location'));
    });
  }
  listEl.appendChild(div);
}

/**
 * Render trạng thái lỗi
 */
export function renderError(listEl, message) {
  listEl.innerHTML = '';
  const div = document.createElement('div');
  div.className = 'error-state';

  const icon = document.createElement('div');
  icon.className = 'error-state__icon';
  icon.innerHTML = iconSvg('warning');

  const msg = document.createElement('div');
  msg.className = 'error-state__msg';
  msg.textContent = message || 'Không tải được danh sách. Kiểm tra kết nối rồi thử lại.';

  const retryBtn = document.createElement('button');
  retryBtn.className = 'btn btn--primary btn--sm';
  retryBtn.textContent = 'Tải lại';
  retryBtn.addEventListener('click', () => {
    // Dispatch custom event for main.js to handle
    window.dispatchEvent(new CustomEvent('food-finder:retry'));
  });

  div.appendChild(icon);
  div.appendChild(msg);
  div.appendChild(retryBtn);
  listEl.appendChild(div);
}

function iconSvg(name) { return icon(name); }

/**
 * Render skeleton loading
 */
export function renderSkeleton(listEl, count = 5) {
  listEl.innerHTML = '';
  for (let i = 0; i < count; i++) {
    const row = document.createElement('div');
    row.className = 'skeleton-row';
    row.innerHTML = `
      <div class="skeleton skeleton-row__img"></div>
      <div class="skeleton-row__lines">
        <div class="skeleton skeleton-row__line skeleton-row__line--w70"></div>
        <div class="skeleton skeleton-row__line skeleton-row__line--w50"></div>
        <div class="skeleton skeleton-row__line skeleton-row__line--w30"></div>
      </div>
    `;
    listEl.appendChild(row);
  }
}

/**
 * Cập nhật dòng đếm kết quả
 */
export function updateCount(countEl, count) {
  if (!countEl) return;
  if (count === null) {
    countEl.textContent = 'Đang tìm địa điểm...';
  } else if (count === 0) {
    countEl.textContent = 'Không tìm thấy địa điểm nào';
  } else {
    countEl.textContent = `${count} địa điểm quanh bạn`;
  }
}

/**
 * Highlight hàng đang chọn & cuộn vào tầm nhìn
 */
export function highlightRow(listEl, id) {
  listEl.querySelectorAll('.resto-row').forEach(row => {
    const rowId = Number(row.dataset.id);
    row.classList.toggle('resto-row--active', rowId === id);
    if (rowId === id) {
      row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  });
}
