/* ============================================================
   FILTERS.JS — Trạng thái bộ lọc + debounce
   ============================================================ */

import { CONFIG } from './config.js';
import { categoryIcon } from './icons.js';

let onChange = null;
let debounceTimer = null;

/**
 * Đặt callback khi bộ lọc thay đổi
 */
export function setFilterChangeHandler(handler) {
  onChange = handler;
}

/**
 * Lấy các giá trị bộ lọc hiện tại từ state
 */
export function getActiveFilters(state) {
  const f = {};
  f.lat = state.user.lat;
  f.lng = state.user.lng;
  f.radius = state.filters.radius;

  if (state.filters.type && state.filters.type !== 'all') f.type = state.filters.type;
  if (state.filters.categoryId) f.category_id = state.filters.categoryId;
  if (state.filters.priceLevel) f.price_level = state.filters.priceLevel;
  if (state.filters.minRating) f.min_rating = state.filters.minRating;
  if (state.filters.openNow) f.open_now = 1;
  if (state.filters.q) f.q = state.filters.q;
  f.sort = state.filters.sort || 'distance';
  f.limit = CONFIG.DEFAULT_LIMIT;

  return f;
}

let allCategoriesList = [];

/**
 * Cập nhật trạng thái bật/tắt (disabled/opacity) của 2 nút mũi tên cuộn danh mục
 */
export function updateFilterScrollArrows() {
  const container = document.getElementById('header-filters');
  const btnLeft = document.getElementById('btn-scroll-filter-left');
  const btnRight = document.getElementById('btn-scroll-filter-right');
  if (!container || !btnLeft || !btnRight) return;

  const maxScroll = Math.max(0, container.scrollWidth - container.clientWidth);

  if (maxScroll <= 4) {
    btnLeft.disabled = true;
    btnRight.disabled = true;
    btnLeft.classList.add('is-disabled');
    btnRight.classList.add('is-disabled');
    return;
  }

  // Ở sát đầu bên trái
  if (container.scrollLeft <= 4) {
    btnLeft.disabled = true;
    btnLeft.classList.add('is-disabled');
  } else {
    btnLeft.disabled = false;
    btnLeft.classList.remove('is-disabled');
  }

  // Ở sát cuối bên phải
  if (container.scrollLeft >= maxScroll - 4) {
    btnRight.disabled = true;
    btnRight.classList.add('is-disabled');
  } else {
    btnRight.disabled = false;
    btnRight.classList.remove('is-disabled');
  }
}

/**
 * Tự động dịch chuyển thanh danh mục khi click vào chip:
 * - Khi click vào ô gần cuối, nếu sau nó còn ô thì tự động dịch sang 1 ô để tránh bị lấp.
 * - Tương tự khi quay lại về phía bên trái (back lại), nếu trước nó còn ô thì tự động dịch sang trái 1 ô.
 */
export function scrollChipIntoView(chipEl) {
  const container = document.getElementById('header-filters');
  if (!container || !chipEl) return;

  // Lấy tất cả các chip đang hiển thị trong container theo thứ tự từ trái sang phải
  const allChips = Array.from(container.querySelectorAll('.chip')).filter(el => {
    return el.offsetParent !== null && window.getComputedStyle(el).display !== 'none';
  });

  const currentIndex = allChips.indexOf(chipEl);
  if (currentIndex === -1) return;

  const containerRect = container.getBoundingClientRect();
  const chipRect = chipEl.getBoundingClientRect();

  // Vùng hiển thị an toàn của container (đệm 8px hai bên mép để tránh chạm dính)
  const safeLeft = containerRect.left + 8;
  const safeRight = containerRect.right - 8;

  const hasFollowing = currentIndex < allChips.length - 1;
  const hasPrevious = currentIndex > 0;

  // 1. Chiều tiến sang phải:
  // Nếu bấm vào ô gần cuối và sau nó còn ô khác, tự động dịch sang phải 1 ô để ô kế tiếp hiển thị trọn vẹn
  if (hasFollowing) {
    const nextChip = allChips[currentIndex + 1];
    const nextRect = nextChip.getBoundingClientRect();

    // Nếu ô kế tiếp bị tràn mép phải HOẶC ô hiện tại nằm sát mép phải (trong khoảng 40px)
    if (nextRect.right > safeRight || chipRect.right > (safeRight - 40)) {
      const shiftRight = nextRect.right - safeRight + 12;
      if (shiftRight > 0) {
        container.scrollBy({
          left: shiftRight,
          behavior: 'smooth'
        });
        return;
      }
    }
  } else {
    // Là ô cuối cùng, nếu bị lấp bên phải thì cuộn vào
    if (chipRect.right > safeRight) {
      container.scrollBy({
        left: chipRect.right - safeRight + 12,
        behavior: 'smooth'
      });
      return;
    }
  }

  // 2. Chiều lùi sang trái (khi back lại):
  // Nếu bấm vào ô gần đầu bên trái và trước nó còn ô khác, tự động dịch sang trái 1 ô để ô phía trước hiển thị trọn vẹn
  if (hasPrevious) {
    const prevChip = allChips[currentIndex - 1];
    const prevRect = prevChip.getBoundingClientRect();

    // Nếu ô phía trước bị khuất mép trái HOẶC ô hiện tại nằm sát mép trái (trong khoảng 40px)
    if (prevRect.left < safeLeft || chipRect.left < (safeLeft + 40)) {
      const shiftLeft = prevRect.left - safeLeft - 12; // giá trị âm
      if (shiftLeft < 0) {
        container.scrollBy({
          left: shiftLeft,
          behavior: 'smooth'
        });
        return;
      }
    }
  } else {
    // Là ô đầu tiên, nếu bị lấp bên trái thì cuộn vào
    if (chipRect.left < safeLeft) {
      container.scrollBy({
        left: chipRect.left - safeLeft - 12,
        behavior: 'smooth'
      });
      return;
    }
  }
}

/**
 * Hỗ trợ cuộn ngang thanh filter bằng con lăn chuột (mouse wheel) trên PC
 */
export function enableHeaderFiltersWheelScroll() {
  const container = document.getElementById('header-filters');
  const wrapper = document.getElementById('header-scroll-wrapper') || container;
  if (!container || !wrapper) return;

  wrapper.addEventListener('wheel', (e) => {
    if (Math.abs(e.deltaY) > Math.abs(e.deltaX) && container.scrollWidth > container.clientWidth) {
      e.preventDefault();
      container.scrollBy({
        left: e.deltaY,
        behavior: 'auto'
      });
    }
  }, { passive: false });
}

/**
 * Khởi tạo nút mũi tên điều hướng cuộn danh mục
 */
export function initFilterScrollNavigation() {
  const container = document.getElementById('header-filters');
  const btnLeft = document.getElementById('btn-scroll-filter-left');
  const btnRight = document.getElementById('btn-scroll-filter-right');
  if (!container) return;

  if (btnLeft) {
    btnLeft.addEventListener('click', () => {
      const step = Math.max(160, Math.floor(container.clientWidth * 0.45));
      container.scrollBy({ left: -step, behavior: 'smooth' });
    });
  }

  if (btnRight) {
    btnRight.addEventListener('click', () => {
      const step = Math.max(160, Math.floor(container.clientWidth * 0.45));
      container.scrollBy({ left: step, behavior: 'smooth' });
    });
  }

  container.addEventListener('scroll', updateFilterScrollArrows, { passive: true });
  window.addEventListener('resize', updateFilterScrollArrows, { passive: true });

  setTimeout(updateFilterScrollArrows, 60);
}

/**
 * Khởi tạo sự kiện cho bộ lọc
 */
export function initFilters(state) {
  // Hỗ trợ cuộn chuột ngang trên desktop
  enableHeaderFiltersWheelScroll();

  // Khởi tạo nút mũi tên cuộn danh mục
  initFilterScrollNavigation();

  // ── FE-01: Type Tabs (Tất cả / Ẩm thực / Du lịch / Vui chơi) CỐ ĐỊNH ──
  const typeTabs = document.querySelectorAll('[data-type]');
  typeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const typeVal = tab.dataset.type || 'all';
      state.filters.type = typeVal;

      typeTabs.forEach(t => {
        const isSelected = (t.dataset.type || 'all') === typeVal;
        t.classList.toggle('type-tab--active', isSelected);
        t.setAttribute('aria-selected', isSelected ? 'true' : 'false');
      });

      // Cuộn thanh category về đầu (vị trí món Cơm)
      const filterContainer = document.getElementById('header-filters');
      if (filterContainer) {
        filterContainer.scrollTo({ left: 0, behavior: 'smooth' });
      }

      // Cập nhật lại danh sách category chips theo nhóm loại hình đang chọn
      if (allCategoriesList.length) {
        state.filters.categoryId = null;
        renderCategoryChips(allCategoriesList, state);
      }

      triggerChange();
    });
  });

  // ── Radius selector ──
  const radiusSelect = document.getElementById('filter-radius');

  if (radiusSelect) {
    radiusSelect.value = state.filters.radius;
    radiusSelect.addEventListener('change', () => {
      state.filters.radius = parseFloat(radiusSelect.value);
      triggerChange();
    });
  }

  // ── Price chips ──
  document.querySelectorAll('[data-filter="price"]').forEach(chip => {
    chip.addEventListener('click', () => {
      const val = parseInt(chip.dataset.value);
      if (state.filters.priceLevel === val) {
        state.filters.priceLevel = null;
        chip.classList.remove('chip--active');
      } else {
        // Deactivate other price chips
        document.querySelectorAll('[data-filter="price"]').forEach(c => c.classList.remove('chip--active'));
        state.filters.priceLevel = val;
        chip.classList.add('chip--active');
      }
      scrollChipIntoView(chip);
      triggerChange();
    });
  });

  // ── Open now chip ──
  const openChip = document.getElementById('filter-open');
  if (openChip) {
    openChip.addEventListener('click', () => {
      state.filters.openNow = !state.filters.openNow;
      openChip.classList.toggle('chip--active', state.filters.openNow);
      scrollChipIntoView(openChip);
      triggerChange();
    });
  }

  // ── Search inputs (debounced) ──
  const searchInputs = [
    document.getElementById('sidebar-search'),
    document.getElementById('sheet-search'),
  ];

  searchInputs.forEach(input => {
    if (!input) return;
    input.addEventListener('input', () => {
      state.filters.q = input.value.trim();
      // Sync both inputs
      searchInputs.forEach(other => {
        if (other && other !== input) other.value = input.value;
      });
      debounceChange();
    });
  });

  // ── Keyboard shortcut: / to focus search ──
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !isInputFocused()) {
      e.preventDefault();
      const visibleInput = window.innerWidth >= 1024
        ? document.getElementById('sidebar-search')
        : document.getElementById('sheet-search');
      if (visibleInput) visibleInput.focus();
    }
  });
}

/**
 * Render category chips
 */
export function renderCategoryChips(categories, state) {
  if (categories && categories.length) {
    allCategoriesList = categories;
  }

  const container = document.getElementById('category-chips');
  if (!container) return;

  const currentType = state.filters.type || 'all';
  // Lọc danh mục theo tab Cấp 1 đang chọn nếu có
  const displayCats = (currentType === 'all')
    ? allCategoriesList
    : allCategoriesList.filter(c => !c.type_group || c.type_group === currentType);

  container.innerHTML = '';
  displayCats.forEach(cat => {
    const chip = document.createElement('button');
    chip.className = 'chip';
    chip.type = 'button';
    chip.setAttribute('data-category-id', cat.id);
    if (state.filters.categoryId === cat.id) {
      chip.classList.add('chip--active');
    }
    chip.innerHTML = `${categoryIcon(cat)}<span>${cat.name}</span>`;

    chip.addEventListener('click', () => {
      if (state.filters.categoryId === cat.id) {
        state.filters.categoryId = null;
        chip.classList.remove('chip--active');
      } else {
        container.querySelectorAll('.chip').forEach(c => c.classList.remove('chip--active'));
        state.filters.categoryId = cat.id;
        chip.classList.add('chip--active');
      }

      // Tự động cuộn nếu còn ô phía sau bị che
      scrollChipIntoView(chip);
      triggerChange();
    });

    container.appendChild(chip);
  });

  // Cập nhật trạng thái hiển thị mũi tên cuộn
  setTimeout(updateFilterScrollArrows, 60);
}

function triggerChange() {
  if (onChange) onChange();
}

function debounceChange() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    triggerChange();
  }, CONFIG.DEBOUNCE_MS);
}

function isInputFocused() {
  const el = document.activeElement;
  return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
}
