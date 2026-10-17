/* ============================================================
   MAP.JS — Khởi tạo Leaflet, markers, flyTo, cluster
   ============================================================ */

import { CONFIG } from './config.js';
import { formatDistance, formatPrice, escapeText, isOpenNow, formatTime, getDirectionsUrl, getGoogleMapsPinUrl } from './format.js';
import { categoryIcon } from './icons.js';

let map = null;
let tileLayer = null;
let userMarker = null;
let markerGroup = null;
let markers = new Map(); // id → L.marker
let markerTimers = []; // Keep track of pending drop animation timeouts

// Callbacks
let onMarkerClick = null;
let onUserDrag = null;

/**
 * Khởi tạo bản đồ Leaflet
 */
export function initMap(containerId, { lat, lng, zoom, isDark, onMarkerSelect, onUserPositionChange }) {
  onMarkerClick = onMarkerSelect;
  onUserDrag = onUserPositionChange;

  if (typeof L === 'undefined') {
    console.error('[FoodFinder] Thư viện Leaflet chưa được tải.');
    return null;
  }

  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`[FoodFinder] Không tìm thấy phần tử #${containerId}`);
    return null;
  }

  if (map) {
    try {
      map.remove();
    } catch (e) {
      console.warn('Map cleanup error:', e);
    }
    markerTimers.forEach((timerId) => clearTimeout(timerId));
    markerTimers = [];
    map = null;
    userMarker = null;
    markerGroup = null;
    markers.clear();
  }

  map = L.map(containerId, {
    center: [lat, lng],
    zoom: zoom || CONFIG.DEFAULT_ZOOM,
    zoomControl: true,
    attributionControl: true,
  });

  // Tile layer
  const tileUrl = isDark ? CONFIG.TILE_DARK : CONFIG.TILE_LIGHT;
  const tileOpts = {
    attribution: CONFIG.TILE_ATTR,
    maxZoom: 19,
  };
  if (CONFIG.TILE_SUBDOMAINS) {
    tileOpts.subdomains = CONFIG.TILE_SUBDOMAINS;
  }
  tileLayer = L.tileLayer(tileUrl, tileOpts).addTo(map);
  window._leafletMap = map;
  window._leafletTileLayer = tileLayer;
  tileLayer.on('load', () => { window.__tilesLoaded = true; });

  // Multi-pass invalidate: sidebar CSS transitions take ~300ms to settle
  setTimeout(() => map.invalidateSize({ animate: false }), 50);
  setTimeout(() => map.invalidateSize({ animate: false }), 300);
  setTimeout(() => map.invalidateSize({ animate: false }), 600);

  // Marker cluster group
  if (typeof L.markerClusterGroup === 'function') {
    markerGroup = L.markerClusterGroup({
      maxClusterRadius: 45,
      showCoverageOnHover: false,
      iconCreateFunction: (cluster) => {
        const count = cluster.getChildCount();
        const size = count > 20 ? 'large' : '';
        return L.divIcon({
          html: `<div class="marker-cluster${size ? ' marker-cluster--large' : ''}">
                   <div class="marker-cluster__circle">${count}</div>
                 </div>`,
          className: '',
          iconSize: [40, 40],
        });
      },
    });
  } else {
    markerGroup = L.layerGroup();
  }
  markerGroup.addTo(map);

  return map;
}

/**
 * Đặt/cập nhật marker người dùng (kéo thả được)
 */
export function setUserMarker(lat, lng) {
  if (!map || typeof L === 'undefined') return;
  const numLat = Number(lat);
  const numLng = Number(lng);
  if (isNaN(numLat) || isNaN(numLng) || !isFinite(numLat) || !isFinite(numLng)) return;

  const icon = L.divIcon({
    className: '',
    html: '<div class="marker-user"></div>',
    iconSize: [16, 16],
    iconAnchor: [8, 8],
  });

  if (userMarker) {
    userMarker.setLatLng([numLat, numLng]);
  } else {
    userMarker = L.marker([numLat, numLng], {
      icon,
      draggable: true,
      zIndexOffset: 2000,
      title: 'Vị trí của bạn — kéo để đổi',
    }).addTo(map);

    userMarker.on('dragend', () => {
      const pos = userMarker.getLatLng();
      if (onUserDrag) onUserDrag(pos.lat, pos.lng);
    });
  }
}

/**
 * Render markers cho danh sách quán
 */
export function renderMarkers(restaurants, selectedId = null) {
  if (!map || !markerGroup || typeof L === 'undefined') return;

  // Clear toàn bộ setTimeout đang chờ từ lần render trước
  markerTimers.forEach((timerId) => clearTimeout(timerId));
  markerTimers = [];

  markerGroup.clearLayers();
  markers.clear();

  if (!Array.isArray(restaurants)) return;

  restaurants.forEach((r, index) => {
    if (!r) return;
    const lat = Number(r.lat ?? r.latitude);
    const lng = Number(r.lng ?? r.longitude);

    if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) {
      return;
    }

    // Đảm bảo r.lat và r.lng luôn mang kiểu số hợp lệ cho các hàm downstream
    r.lat = lat;
    r.lng = lng;

    const isSelected = String(r.id) === String(selectedId);
    const icon = createRestaurantIcon(r, isSelected);
    const marker = L.marker([lat, lng], {
      icon,
      zIndexOffset: isSelected ? 1000 : 0,
      title: r.name,
    });

    // Gắn popup trực tiếp vào marker (DOM-based, an toàn XSS)
    marker.bindPopup(() => createPopupContent(r), {
      maxWidth: 280,
      minWidth: 200,
      autoPan: true,
      autoPanPadding: [20, 20],
    });

    // Khi click marker: kích hoạt callback chọn quán
    marker.on('click', () => {
      if (onMarkerClick) onMarkerClick(r.id);
    });

    // Thêm marker ngay vào layer group để marker luôn tồn tại và tra cứu được
    marker.addTo(markerGroup);

    // Staggered drop animation mượt mà qua CSS transition delay
    const shouldAnimate = index < CONFIG.MAX_ANIMATED_MARKERS;
    if (shouldAnimate) {
      const el = marker.getElement();
      if (el) {
        const pin = el.querySelector('.marker-pin');
        if (pin) {
          pin.style.animationDelay = `${index * CONFIG.MARKER_STAGGER_MS}ms`;
          pin.classList.add('marker-pin--animate');
        }
      }
    }

    markers.set(r.id, marker);

    if (isSelected) {
      setTimeout(() => {
        if (markerGroup && typeof markerGroup.zoomToShowLayer === 'function') {
          markerGroup.zoomToShowLayer(marker, () => marker.openPopup());
        } else {
          marker.openPopup();
        }
      }, 100);
    }
  });
}

/**
 * Tạo divIcon cho quán
 */
function createRestaurantIcon(restaurant, isSelected) {
  const selectedClass = isSelected ? ' marker-pin--selected' : '';
  const categoryGlyph = categoryIcon(restaurant.category, 'marker-pin__svg');

  return L.divIcon({
    className: '',
    html: `<div class="marker-pin${selectedClass}">
             <div class="marker-pin__drop"></div>
             <span class="marker-pin__icon">${categoryGlyph}</span>
           </div>`,
    iconSize: [32, 40],
    iconAnchor: [16, 40],
    popupAnchor: [0, -42],
  });
}

/**
 * Tạo phần tử DOM nội dung popup cho quán (DOM-based, an toàn XSS)
 */
export function createPopupContent(restaurant) {
  const container = document.createElement('div');
  container.className = 'popup';

  const name = document.createElement('div');
  name.className = 'popup__name';
  name.textContent = restaurant.name;

  const address = document.createElement('div');
  address.className = 'popup__address';
  address.textContent = restaurant.address;

  const meta = document.createElement('div');
  meta.className = 'popup__meta';

  // Badge open/closed
  const openStatus = isOpenNow(restaurant.open_time, restaurant.close_time, restaurant.is_24h);
  if (openStatus !== null) {
    const badge = document.createElement('span');
    badge.className = `badge ${openStatus ? 'badge--open' : 'badge--closed'}`;
    badge.textContent = openStatus ? 'Đang mở' : 'Đã đóng';
    meta.appendChild(badge);
  }

  // Price
  const price = formatPrice(restaurant.price_min, restaurant.price_max);
  if (price) {
    const priceEl = document.createElement('span');
    priceEl.textContent = price;
    meta.appendChild(priceEl);
  }

  // Distance
  if (restaurant.distance_km !== undefined) {
    const dist = document.createElement('span');
    dist.textContent = formatDistance(restaurant.distance_km);
    meta.appendChild(dist);
  }

  // Actions
  const actions = document.createElement('div');
  actions.className = 'popup__actions';

  const dirBtn = document.createElement('a');
  dirBtn.className = 'btn btn--primary btn--sm';
  dirBtn.textContent = 'Chỉ đường';
  dirBtn.href = getDirectionsUrl(restaurant);
  dirBtn.target = '_blank';
  dirBtn.rel = 'noopener noreferrer';
  actions.appendChild(dirBtn);

  const gmapBtn = document.createElement('a');
  gmapBtn.className = 'btn btn--ghost btn--sm';
  gmapBtn.textContent = 'Google Maps';
  gmapBtn.href = getGoogleMapsPinUrl(restaurant);
  gmapBtn.target = '_blank';
  gmapBtn.rel = 'noopener noreferrer';
  gmapBtn.title = 'Xem vị trí quán trên Google Maps';
  actions.appendChild(gmapBtn);

  container.appendChild(name);
  container.appendChild(address);
  container.appendChild(meta);
  container.appendChild(actions);

  return container;
}

/**
 * Hiện popup cho marker
 */
function showPopup(marker, restaurant) {
  marker.setPopupContent(createPopupContent(restaurant)).openPopup();
}

/**
 * Bay tới vị trí chuẩn xác tuyệt đối ở trung tâm bản đồ nhìn thấy
 */
export function flyTo(lat, lng, zoom, options = {}) {
  if (!map) return;
  const numLat = Number(lat);
  const numLng = Number(lng);
  if (isNaN(numLat) || isNaN(numLng) || !isFinite(numLat) || !isFinite(numLng)) return;

  map.flyTo([numLat, numLng], zoom || CONFIG.FOCUS_ZOOM, {
    duration: CONFIG.FLY_DURATION,
    easeLinearity: 0.25,
  });
}

/**
 * Highlight marker khi chọn quán và mở popup chi tiết
 */
export function highlightMarker(id, shouldOpenPopup = false) {
  markers.forEach((marker, markerId) => {
    const isMatch = String(markerId) === String(id);
    const el = marker.getElement();
    if (isMatch) {
      if (el) {
        const pin = el.querySelector('.marker-pin');
        if (pin) pin.classList.add('marker-pin--selected');
      }
      marker.setZIndexOffset(1000);
      if (shouldOpenPopup) {
        if (markerGroup && typeof markerGroup.zoomToShowLayer === 'function') {
          markerGroup.zoomToShowLayer(marker, () => marker.openPopup());
        } else {
          marker.openPopup();
        }
      }
    } else {
      if (el) {
        const pin = el.querySelector('.marker-pin');
        if (pin) pin.classList.remove('marker-pin--selected');
      }
      marker.setZIndexOffset(0);
    }
  });
}

/**
 * Đổi tile layer khi chuyển theme
 */
export function setTheme(isDark) {
  if (!tileLayer || !map) return;
  map.removeLayer(tileLayer);
  const tileOpts = {
    attribution: CONFIG.TILE_ATTR,
    maxZoom: 19,
  };
  if (CONFIG.TILE_SUBDOMAINS) {
    tileOpts.subdomains = CONFIG.TILE_SUBDOMAINS;
  }
  tileLayer = L.tileLayer(isDark ? CONFIG.TILE_DARK : CONFIG.TILE_LIGHT, tileOpts).addTo(map);
}

/**
 * Gọi invalidateSize (khi sheet đổi nấc / xoay màn hình)
 */
export function invalidateSize() {
  if (map) {
    setTimeout(() => map.invalidateSize({ animate: false }), 50);
    setTimeout(() => map.invalidateSize({ animate: false }), 200);
  }
}

/**
 * Lấy instance map
 */
export function getMap() {
  return map;
}

/**
 * Lấy directions URL (export cho random card)
 */
export { getDirectionsUrl, getGoogleMapsPinUrl };

