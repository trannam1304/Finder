/* ============================================================
   ITINERARY-VIEW.JS — Hiển thị Timeline Lộ trình Thông minh (FE-05)
   Tích hợp Tuyến đường Polyline (FE-06) & Lưu/Chia Sẻ (FE-07)
   ============================================================ */

import { rerollItineraryStop } from './api.js';
import { formatDistance, formatPrice, formatTime, formatPlaceType, formatTicketPrice, formatDuration, getMultiStopDirectionsUrl, getDirectionsUrl, escapeText } from './format.js';
import { renderItineraryRoute, clearItineraryRoute, flyTo } from './map.js';
import { icon, categoryIcon } from './icons.js';

const STORAGE_KEY = 'food_finder_saved_itineraries';

let currentItinerary = null;
let currentUserPos = null;
let activeExcludeIds = [];
let onExitItineraryCallback = null;

/**
 * Đăng ký callback khi người dùng đóng lộ trình quay lại bản đồ thường
 */
export function setExitItineraryHandler(callback) {
  onExitItineraryCallback = callback;
}

/**
 * Lấy danh sách lộ trình đã lưu trong LocalStorage
 */
export function getSavedItineraries() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (_) {
    return [];
  }
}

/**
 * Lưu lộ trình hiện tại vào LocalStorage
 */
export function saveCurrentItinerary() {
  if (!currentItinerary) return false;
  try {
    const saved = getSavedItineraries();
    const id = 'itin_' + Date.now();
    const durationTitles = {
      half_day: 'Lộ trình Nửa ngày',
      full_day: 'Lộ trình Cả ngày',
      evening: 'Lộ trình Buổi tối',
    };
    const vibeTitles = {
      chill: 'Chill thư thái',
      date: 'Hẹn hò lãng mạn',
      adventure: 'Khám phá năng động',
      foodie: 'Ăn sập Sài Gòn',
    };

    const entry = {
      id,
      saved_at: new Date().toISOString(),
      title: `${durationTitles[currentItinerary.duration] || 'Lộ trình'} • ${vibeTitles[currentItinerary.vibe] || ''}`,
      duration: currentItinerary.duration,
      vibe: currentItinerary.vibe,
      total_stops: currentItinerary.total_stops || currentItinerary.stops.length,
      total_distance_km: currentItinerary.total_distance_km,
      stops: currentItinerary.stops,
      user_pos: currentUserPos,
    };

    saved.unshift(entry);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(saved.slice(0, 30)));
    return true;
  } catch (e) {
    console.error('Lỗi khi lưu lộ trình:', e);
    return false;
  }
}

/**
 * Xóa một lộ trình đã lưu
 */
export function deleteSavedItinerary(id) {
  try {
    const saved = getSavedItineraries().filter(item => item.id !== id);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
    return true;
  } catch (_) {
    return false;
  }
}

/**
 * Sao chép tóm tắt lộ trình vào Clipboard
 */
export async function copyItinerarySummary(itinerary, userPos) {
  if (!itinerary || !itinerary.stops) return false;
  const esc = escapeText;
  const stops = itinerary.stops;
  const lastStop = stops[stops.length - 1]?.place;
  const gmapsUrl = getMultiStopDirectionsUrl(userPos, stops.map(s => s.place));

  const durationMap = { half_day: 'Nửa ngày (~4 giờ)', full_day: 'Cả ngày (~8 giờ)', evening: 'Buổi tối (~4.5 giờ)' };
  const vibeMap = { chill: '🌿 Chill thư thái', date: '💖 Hẹn hò lãng mạn', adventure: '⚡ Vui chơi trải nghiệm', foodie: '🍜 Ăn sập Sài Gòn' };

  let text = `🗺️ LỘ TRÌNH ĐI CHƠI — FOOD & TRIP FINDER\n`;
  text += `⏱️ Thời lượng: ${durationMap[itinerary.duration] || itinerary.duration}\n`;
  text += `🎯 Phong cách: ${vibeMap[itinerary.vibe] || itinerary.vibe}\n`;
  text += `🚗 Tổng quãng đường: ${itinerary.total_distance_km || 0} km (${stops.length} chặng dừng)\n`;
  text += `───────────────────────\n`;

  stops.forEach((s, idx) => {
    const p = s.place;
    const time = s.suggested_time || s.time_slot || '';
    text += `${idx + 1}. [${time}] ${p.name}\n`;
    text += `   📍 ${p.address}\n`;
    if (s.leg_distance_km) text += `   🚗 Cự ly: +${s.leg_distance_km} km\n`;
    if (s.activity_note) text += `   💡 ${s.activity_note}\n`;
  });

  text += `───────────────────────\n`;
  text += `👉 Mở toàn bộ đường đi trên Google Maps:\n${gmapsUrl}\n`;

  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(text);
      return true;
    }
    // Fallback
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
    return true;
  } catch (_) {
    return false;
  }
}

/**
 * Chia sẻ lộ trình qua Web Share API hoặc Link
 */
export async function shareItinerary(itinerary, userPos, showToast) {
  if (!itinerary || !itinerary.stops) return;
  const stopIds = itinerary.stops.map(s => s.place.id).join(',');
  const shareUrl = new URL(window.location.origin + window.location.pathname);
  shareUrl.searchParams.set('itinerary', '1');
  shareUrl.searchParams.set('duration', itinerary.duration);
  shareUrl.searchParams.set('vibe', itinerary.vibe);
  shareUrl.searchParams.set('stops', stopIds);

  const title = `Lộ trình ${itinerary.duration === 'evening' ? 'Buổi tối' : 'Đi chơi'} ${itinerary.stops.length} chặng — Food Finder`;
  const text = `Khám phá lộ trình ${itinerary.stops.length} điểm (${itinerary.total_distance_km} km) được tạo tự động bởi Food Finder!`;

  if (navigator.share) {
    try {
      await navigator.share({
        title,
        text,
        url: shareUrl.toString(),
      });
      return;
    } catch (_) {
      // User cancelled or share aborted
    }
  }

  // Fallback: Copy link
  try {
    await navigator.clipboard.writeText(shareUrl.toString());
    showToast?.('🔗 Đã sao chép liên kết chia sẻ lộ trình vào bộ nhớ tạm!');
  } catch (_) {
    showToast?.('Không thể tự động sao chép. Vui lòng thử lại.');
  }
}

/**
 * Hiển thị Timeline Lịch trình Thông minh vào DOM container
 * @param {HTMLElement} container - Nơi render danh sách timeline
 * @param {Object} itinerary - Dữ liệu trả về từ API /api/itinerary.php
 * @param {Object} userPos - { lat, lng }
 * @param {Function} showToast - Toast notification helper
 */
export function displayItineraryTimeline(container, itinerary, userPos, showToast) {
  if (!container || !itinerary) return;

  currentItinerary = itinerary;
  currentUserPos = userPos;
  activeExcludeIds = (itinerary.stops || []).map(s => s.place.id);

  // 1. Vẽ tuyến đường Polyline & Marker số lên bản đồ (FE-06)
  renderItineraryRoute(itinerary.stops, userPos);

  const duration = itinerary.summary?.duration || itinerary.duration || 'half_day';
  const vibe = itinerary.summary?.vibe || itinerary.vibe || 'chill';
  const totalStops = itinerary.stops.length;
  const totalKm = itinerary.summary?.total_distance_km ?? itinerary.total_distance_km ?? 0;
  const estHours = itinerary.summary?.total_time_minutes
    ? (itinerary.summary.total_time_minutes / 60).toFixed(1)
    : (itinerary.estimated_hours || 4);

  const durationMap = { half_day: 'Nửa ngày', full_day: 'Cả ngày', evening: 'Buổi tối' };
  const vibeMap = { chill: '🌿 Chill thư thái', date: '💖 Hẹn hò lãng mạn', adventure: '⚡ Vui chơi trải nghiệm', foodie: '🍜 Ăn sập Sài Gòn' };

  const gmapsAllUrl = itinerary.summary?.google_maps_url || getMultiStopDirectionsUrl(userPos, itinerary.stops.map(s => s.place));

  container.innerHTML = `
    <div class="itinerary-view-wrap">
      <!-- ── Summary Top Card ── -->
      <div class="itinerary-summary-card">
        <div class="itinerary-summary__header">
          <div class="itinerary-summary__title">
            ✨ Lộ trình ${durationMap[duration] || 'Đi chơi'}
          </div>
          <button type="button" class="btn btn--ghost btn--sm" id="btn-exit-itinerary" title="Thoát chế độ lộ trình" style="padding:4px 8px;font-size:11px">
            ✕ Đóng
          </button>
        </div>

        <div class="itinerary-summary__stats">
          <span class="stat-chip">📍 ${totalStops} chặng</span>
          <span class="stat-chip">🚗 ~${totalKm} km</span>
          <span class="stat-chip">⏱️ ~${estHours} giờ</span>
          <span class="stat-chip">${vibeMap[vibe] || vibe}</span>
        </div>

        <div class="itinerary-summary__actions">
          <a class="btn btn--primary btn--sm" id="btn-open-gmaps-route" href="${gmapsAllUrl}" target="_blank" rel="noopener noreferrer" title="Mở lộ trình điều hướng nhiều chặng trên Google Maps">

            <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 5c0 2-2 4-4 4s-4-2-4-4 2-3 4-3 4 1 4 3ZM8 19c0 2-2 3-4 3s-4-1-4-3 2-4 4-4 4 2 4 4Z"/><path d="M4 15c0-7 12 2 12-6"/></svg>
            Google Maps
          </a>
          <button type="button" class="btn btn--ghost btn--sm" id="btn-save-itin" title="Lưu vào danh sách cá nhân">
            ${icon('star')} Lưu
          </button>
          <button type="button" class="btn btn--ghost btn--sm" id="btn-share-itin" title="Chia sẻ lộ trình">
            ${icon('share')} Chia sẻ
          </button>
          <button type="button" class="btn btn--ghost btn--sm" id="btn-copy-summary" title="Sao chép tóm tắt dạng văn bản">
            ${icon('copy')} Tóm tắt
          </button>
        </div>
      </div>

      <!-- ── Vertical Timeline ── -->
      <ol class="timeline-list" role="list">
        ${itinerary.stops.map((stop, index) => renderTimelineItem(stop, index)).join('')}
      </ol>
    </div>
  `;

  // Gắn sự kiện cho các nút hành động đầu trang
  const btnExit = container.querySelector('#btn-exit-itinerary');
  if (btnExit) {
    btnExit.addEventListener('click', () => {
      clearItineraryRoute();
      if (onExitItineraryCallback) onExitItineraryCallback();
    });
  }

  const btnSave = container.querySelector('#btn-save-itin');
  if (btnSave) {
    btnSave.addEventListener('click', () => {
      const ok = saveCurrentItinerary();
      if (ok) {
        btnSave.innerHTML = `${icon('check')} Đã lưu`;
        btnSave.style.color = '#059669';
        showToast?.('⭐ Đã lưu lộ trình vào danh sách cá nhân!');
      } else {
        showToast?.('Lộ trình này đã được lưu trước đó.');
      }
    });
  }

  const btnShare = container.querySelector('#btn-share-itin');
  if (btnShare) {
    btnShare.addEventListener('click', () => {
      shareItinerary(currentItinerary, currentUserPos, showToast);
    });
  }

  const btnCopy = container.querySelector('#btn-copy-summary');
  if (btnCopy) {
    btnCopy.addEventListener('click', async () => {
      const ok = await copyItinerarySummary(currentItinerary, currentUserPos);
      if (ok) {
        showToast?.('📋 Đã sao chép tóm tắt lộ trình vào bộ nhớ tạm!');
      }
    });
  }

  // Gắn sự kiện cho nút REROLL (FE-05 Đổi điểm khác) ở từng chặng
  container.querySelectorAll('.btn-reroll').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const stepIdx = parseInt(btn.dataset.stepIndex, 10);
      await handleRerollStop(stepIdx, btn, container, showToast);
    });
  });

  // Gắn sự kiện click vào thẻ chặng để bay tới marker trên bản đồ
  container.querySelectorAll('.timeline-card').forEach(card => {
    card.addEventListener('click', () => {
      const lat = parseFloat(card.dataset.lat);
      const lng = parseFloat(card.dataset.lng);
      if (!isNaN(lat) && !isNaN(lng)) {
        flyTo(lat, lng, 16);
      }
    });
  });
}

/**
 * Render HTML cho một chặng Timeline
 */
function renderTimelineItem(stop, index) {
  const p = stop.place || {};
  const pType = p.place_type || 'food';
  const stepNum = stop.step || stop.step_order || (index + 1);
  const timeStr = stop.time_slot || stop.suggested_time || '';
  
  let legDist = 'Điểm xuất phát';
  if (stop.distance_from_prev_km !== undefined && stop.distance_from_prev_km !== null) {
    legDist = `+${stop.distance_from_prev_km} km`;
  } else if (stop.leg_distance_km !== undefined && stop.leg_distance_km !== null) {
    legDist = `+${stop.leg_distance_km} km`;
  } else if (index > 0) {
    legDist = '~1-2 km';
  }

  const durStr = p.estimated_duration ? formatDuration(p.estimated_duration) : 'Gợi ý ~60 phút';
  const noteStr = stop.action_title || stop.activity_note || '';

  let costBadge = '';
  if (pType !== 'food' && p.ticket_price !== undefined) {
    costBadge = `<span class="badge badge--ticket">${formatTicketPrice(p.ticket_price)}</span>`;
  } else if (p.price_min || p.price_max) {
    costBadge = `<span class="badge">${formatPrice(p.price_min, p.price_max)}</span>`;
  }

  return `
    <li class="timeline-item timeline-item--${pType}" id="timeline-stop-${index}">
      <div class="timeline-step-badge" aria-label="Chặng ${stepNum}">
        ${stepNum}
      </div>

      <div class="timeline-card" data-step-index="${index}" data-lat="${p.lat || 0}" data-lng="${p.lng || 0}">
        <div class="timeline-card__top">
          <span class="timeline-time-badge">⏰ ${escapeText(timeStr)}</span>
          <span class="timeline-leg-dist">🚗 ${legDist}</span>
        </div>

        <div class="timeline-card__main">
          <img class="timeline-card__thumb" src="${p.image_url || 'assets/img/highlands.jpg'}" alt="${escapeText(p.name || '')}" loading="lazy">
          <div class="timeline-card__info">
            <h3 class="timeline-card__name" title="${escapeText(p.name || '')}">${escapeText(p.name || '')}</h3>
            <div class="timeline-card__meta">
              <span class="badge badge--type-${pType}">${formatPlaceType(pType)}</span>
              ${costBadge}
              <span class="badge badge--duration">${durStr}</span>
            </div>
            <div class="timeline-card__address" title="${escapeText(p.address || '')}">📍 ${escapeText(p.address || '')}</div>
          </div>
        </div>

        ${noteStr ? `<div class="timeline-card__note">💡 ${escapeText(noteStr)}</div>` : ''}

        <div class="timeline-card__actions">
          <button type="button" class="btn-reroll" data-step-index="${index}" title="Đổi sang địa điểm phù hợp khác gần đây">
            <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
            <span>Đổi điểm khác</span>
          </button>
          <a class="btn btn--ghost btn--sm" href="${getDirectionsUrl(p)}" target="_blank" rel="noopener noreferrer" style="font-size:11px;padding:3px 8px">
            Chỉ đường
          </a>
        </div>
      </div>
    </li>
  `;
}

/**
 * Xử lý đổi địa điểm đơn lẻ (FE-05 REROLL)
 */
async function handleRerollStop(stepIndex, btnEl, container, showToast) {
  if (!currentItinerary || !currentItinerary.stops[stepIndex]) return;

  const currentStop = currentItinerary.stops[stepIndex];
  const currentPlace = currentStop.place;

  // Lấy vị trí tham chiếu từ chặng liền trước (hoặc vị trí user nếu là chặng 1)
  let refLat = currentUserPos?.lat;
  let refLng = currentUserPos?.lng;
  if (stepIndex > 0 && currentItinerary.stops[stepIndex - 1]?.place) {
    refLat = currentItinerary.stops[stepIndex - 1].place.lat;
    refLng = currentItinerary.stops[stepIndex - 1].place.lng;
  }

  btnEl.classList.add('is-spinning');
  btnEl.disabled = true;

  try {
    const res = await rerollItineraryStop({
      current_id: currentPlace.id,
      exclude_ids: activeExcludeIds.join(','),
      from_lat: refLat,
      from_lng: refLng,
      place_type: currentPlace.place_type || 'food',
      vibe: currentItinerary.vibe || 'chill',
      radius: 12.0,
    });

    if (res.success && res.data && res.data.place) {
      const newPlace = res.data.place;

      // Cập nhật state
      activeExcludeIds.push(newPlace.id);
      currentStop.place = newPlace;

      // Vẽ lại tuyến đường trên bản đồ
      renderItineraryRoute(currentItinerary.stops, currentUserPos);

      // Render lại giao diện
      displayItineraryTimeline(container, currentItinerary, currentUserPos, showToast);
      showToast?.(`🔄 Đã đổi sang "${newPlace.name}"!`);
      flyTo(newPlace.lat, newPlace.lng, 16);
    } else {
      showToast?.(res.error?.message || 'Không tìm thấy địa điểm khác phù hợp trong khu vực này.');
    }
  } catch (err) {
    showToast?.('Không thể đổi điểm lúc này. Vui lòng thử lại sau.');
  } finally {
    btnEl.classList.remove('is-spinning');
    btnEl.disabled = false;
  }
}
