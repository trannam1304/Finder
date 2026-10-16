/* ============================================================
   ITINERARY-SETUP.JS — Modal Thiết lập & Khởi tạo Lộ trình (FE-04)
   ============================================================ */

import { fetchItinerary } from './api.js';
import { displayItineraryTimeline, getSavedItineraries, deleteSavedItinerary } from './itinerary-view.js';
import { icon } from './icons.js';

let appState = null;
let showToastFn = null;

const formState = {
  duration: 'half_day',
  vibe: 'chill',
  radius: 8.0,
};

/**
 * Khởi tạo Modal Thiết lập Lộ trình & Các sự kiện liên quan
 */
export function initItinerarySetup(state, showToast) {
  appState = state;
  showToastFn = showToast;

  const backdrop = document.getElementById('itinerary-modal-backdrop');
  const btnOpen = document.getElementById('btn-open-itinerary');
  const btnClose = document.getElementById('btn-close-itinerary-modal');
  const btnSubmit = document.getElementById('btn-submit-itinerary');

  if (!backdrop) return;

  // Mở modal
  const openModal = () => {
    backdrop.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  };

  // Đóng modal
  const closeModal = () => {
    backdrop.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  if (btnOpen) {
    btnOpen.addEventListener('click', openModal);
  }

  if (btnClose) {
    btnClose.addEventListener('click', closeModal);
  }

  // Đóng khi click ngoài backdrop
  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) closeModal();
  });

  // Phím Esc
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
      closeModal();
    }
  });

  // ── Xử lý chọn Thời lượng (Duration) ──
  const durationCards = backdrop.querySelectorAll('.duration-card');
  durationCards.forEach(card => {
    card.addEventListener('click', () => {
      durationCards.forEach(c => c.classList.remove('is-active'));
      card.classList.add('is-active');
      formState.duration = card.dataset.duration || 'half_day';
    });
  });

  // ── Xử lý chọn Vibe / Phong cách ──
  const vibeCards = backdrop.querySelectorAll('.vibe-card');
  vibeCards.forEach(card => {
    card.addEventListener('click', () => {
      vibeCards.forEach(c => c.classList.remove('is-active'));
      card.classList.add('is-active');
      formState.vibe = card.dataset.vibe || 'chill';
    });
  });

  // ── Xử lý chọn Bán kính ──
  const radiusPills = backdrop.querySelectorAll('.radius-pill');
  radiusPills.forEach(pill => {
    pill.addEventListener('click', () => {
      radiusPills.forEach(p => p.classList.remove('is-active'));
      pill.classList.add('is-active');
      formState.radius = parseFloat(pill.dataset.radius) || 8.0;
    });
  });

  // ── Submit tạo lộ trình ──
  if (btnSubmit) {
    btnSubmit.addEventListener('click', async () => {
      await handleGenerateItinerary(btnSubmit, closeModal);
    });
  }

  // Khởi tạo Modal Lộ trình đã lưu (FE-07)
  initSavedItinerariesModal();
}

/**
 * Gọi API sinh lộ trình & chuyển đổi giao diện sang Timeline
 */
async function handleGenerateItinerary(submitBtn, closeModal) {
  const origText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = `
    <svg class="ui-icon is-spinning" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
    <span>Đang tính toán tối ưu đường đi...</span>
  `;

  try {
    const lat = appState?.user?.lat || 10.7326;
    const lng = appState?.user?.lng || 106.6996;

    const res = await fetchItinerary({
      lat,
      lng,
      duration: formState.duration,
      vibe: formState.vibe,
      radius: formState.radius,
    });

    if (res.success && res.data && res.data.stops && res.data.stops.length > 0) {
      closeModal();

      // Hiển thị giao diện Timeline
      activateItineraryMode(res.data, { lat, lng });
      showToastFn?.('🎉 Đã lập lộ trình đi chơi tối ưu quanh vị trí của bạn!');
    } else {
      showToastFn?.(res.error?.message || 'Không tìm thấy địa điểm nào phù hợp với tiêu chí đã chọn. Hãy tăng bán kính.');
    }
  } catch (err) {
    console.error('Lỗi khi tạo lộ trình:', err);
    showToastFn?.('Không thể kết nối máy chủ tạo lộ trình. Đang dùng dữ liệu mô phỏng.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = origText;
  }
}

/**
 * Kích hoạt giao diện Timeline trong Sidebar và Sheet
 */
export function activateItineraryMode(itineraryData, userPos) {
  const sidebarListWrap = document.getElementById('sidebar-list-wrap');
  const sidebarItinWrap = document.getElementById('sidebar-itinerary-view');
  const sheetListWrap = document.getElementById('sheet-list-wrap');
  const sheetItinWrap = document.getElementById('sheet-itinerary-view');

  if (sidebarListWrap && sidebarItinWrap) {
    sidebarListWrap.style.display = 'none';
    sidebarItinWrap.style.display = 'block';
    displayItineraryTimeline(sidebarItinWrap, itineraryData, userPos, showToastFn);
  }

  if (sheetListWrap && sheetItinWrap) {
    sheetListWrap.style.display = 'none';
    sheetItinWrap.style.display = 'block';
    displayItineraryTimeline(sheetItinWrap, itineraryData, userPos, showToastFn);
  }

  // Tự động mở sheet trên mobile lên nấc half để nhìn rõ lộ trình
  if (window.innerWidth < 768) {
    const sheet = document.getElementById('sheet');
    if (sheet) {
      sheet.className = 'sheet sheet--half';
    }
  }
}

/**
 * Thoát chế độ xem lộ trình, phục hồi danh sách địa điểm bình thường
 */
export function deactivateItineraryMode() {
  const sidebarListWrap = document.getElementById('sidebar-list-wrap');
  const sidebarItinWrap = document.getElementById('sidebar-itinerary-view');
  const sheetListWrap = document.getElementById('sheet-list-wrap');
  const sheetItinWrap = document.getElementById('sheet-itinerary-view');

  if (sidebarListWrap && sidebarItinWrap) {
    sidebarListWrap.style.display = '';
    sidebarItinWrap.style.display = 'none';
  }

  if (sheetListWrap && sheetItinWrap) {
    sheetListWrap.style.display = '';
    sheetItinWrap.style.display = 'none';
  }
}

/**
 * Khởi tạo modal xem các lộ trình đã lưu (FE-07)
 */
function initSavedItinerariesModal() {
  const modal = document.getElementById('saved-itineraries-modal');
  if (!modal) return;

  const btnClose = modal.querySelector('.modal-close');
  if (btnClose) {
    btnClose.addEventListener('click', () => {
      modal.classList.remove('is-open');
    });
  }

  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.classList.remove('is-open');
  });
}
