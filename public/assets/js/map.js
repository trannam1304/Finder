/* ============================================================
   MAP.JS — Khởi tạo Leaflet, markers, flyTo, cluster
   ============================================================ */

import { CONFIG } from './config.js';
import { formatDistance, formatPrice, escapeText, isOpenNow, formatTime, getDirectionsUrl, getGoogleMapsPinUrl, getPlaceTypeInfo, formatTicketOrPrice, formatDuration } from './format.js';
import { categoryIcon } from './icons.js';

let map = null;
let tileLayer = null;
let userMarker = null;
let markerGroup = null;
let markers = new Map(); // id → L.marker
let routePolyline = null;
let routeShadowPolyline = null;
let routeMarkers = [];
let routeRequestId = 0;
let routeAbortController = null;
const osrmRouteCache = new Map(); // LRU Cache cho OSRM street coordinates
const MAX_ROUTE_CACHE = 50;
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

  const markerBatch = [];
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

    markers.set(r.id, marker);
    markerBatch.push(marker);

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

  // Tối ưu hiệu năng: Bulk insertion qua addLayers giúp giảm độ phức tạp từ O(N^2) xuống O(N log N)
  if (markerBatch.length > 0) {
    if (typeof markerGroup.addLayers === 'function') {
      markerGroup.addLayers(markerBatch);
    } else {
      markerBatch.forEach(m => markerGroup.addLayer(m));
    }

    // Animation cho nhóm marker đầu tiên
    const animLimit = Math.min(markerBatch.length, CONFIG.MAX_ANIMATED_MARKERS);
    for (let i = 0; i < animLimit; i++) {
      const el = markerBatch[i].getElement();
      if (el) {
        const pin = el.querySelector('.marker-pin');
        if (pin) {
          pin.style.animationDelay = `${i * CONFIG.MARKER_STAGGER_MS}ms`;
          pin.classList.add('marker-pin--animate');
        }
      }
    }
  }
}

/**
/**
 * Tạo divIcon cho địa điểm (Màu riêng theo loại hình: Ẩm thực / Du lịch / Vui chơi)
 */
function createRestaurantIcon(restaurant, isSelected) {
  const placeType = restaurant.place_type || restaurant.category?.type_group || 'food';
  const typeInfo = getPlaceTypeInfo(placeType);
  const selectedClass = isSelected ? ' marker-pin--selected' : '';
  const categoryGlyph = categoryIcon(restaurant.category, 'marker-pin__svg');

  return L.divIcon({
    className: '',
    html: `<div class="marker-pin ${typeInfo.markerClass}${selectedClass}">
             <div class="marker-pin__drop"></div>
             <span class="marker-pin__icon">${categoryGlyph}</span>
           </div>`,
    iconSize: [32, 40],
    iconAnchor: [16, 40],
    popupAnchor: [0, -42],
  });
}

/**
 * Tạo phần tử DOM nội dung popup cho địa điểm (DOM-based, an toàn XSS)
 */
export function createPopupContent(restaurant) {
  const container = document.createElement('div');
  container.className = 'popup';

  const placeType = restaurant.place_type || restaurant.category?.type_group || 'food';
  const typeInfo = getPlaceTypeInfo(placeType);

  // Top badges: Place Type + Category
  const badgesRow = document.createElement('div');
  badgesRow.className = 'popup__badges';

  const typeBadge = document.createElement('span');
  typeBadge.className = `badge ${typeInfo.badgeClass}`;
  typeBadge.textContent = `${typeInfo.emoji} ${typeInfo.label}`;
  badgesRow.appendChild(typeBadge);

  if (restaurant.category?.name) {
    const catBadge = document.createElement('span');
    catBadge.className = 'badge badge--category';
    catBadge.textContent = restaurant.category.name;
    badgesRow.appendChild(catBadge);
  }

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

  // Ticket price / Food price
  const priceText = formatTicketOrPrice(restaurant);
  if (priceText) {
    const priceEl = document.createElement('span');
    priceEl.className = 'popup__price';
    priceEl.textContent = priceText;
    meta.appendChild(priceEl);
  }

  // Estimated duration
  const durationText = formatDuration(restaurant.estimated_duration);
  if (durationText) {
    const durEl = document.createElement('span');
    durEl.className = 'popup__duration';
    durEl.textContent = `⏱ ${durationText}`;
    meta.appendChild(durEl);
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

  const detailBtn = document.createElement('a');
  detailBtn.className = 'btn btn--ghost btn--sm';
  detailBtn.textContent = 'Chi tiết';
  detailBtn.href = `restaurant.html?id=${encodeURIComponent(restaurant.id)}`;
  actions.appendChild(detailBtn);

  const dirBtn = document.createElement('a');
  dirBtn.className = 'btn btn--primary btn--sm';
  dirBtn.textContent = 'Chỉ đường';
  dirBtn.href = getDirectionsUrl(restaurant);
  dirBtn.target = '_blank';
  dirBtn.rel = 'noopener noreferrer';
  actions.appendChild(dirBtn);

  const gmapBtn = document.createElement('a');
  gmapBtn.className = 'btn btn--ghost btn--sm';
  gmapBtn.textContent = 'Bản đồ';
  gmapBtn.href = getGoogleMapsPinUrl(restaurant);
  gmapBtn.target = '_blank';
  gmapBtn.rel = 'noopener noreferrer';
  gmapBtn.title = 'Xem vị trí trên Google Maps';
  actions.appendChild(gmapBtn);

  container.appendChild(badgesRow);
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

/**
 * Cập nhật cặp polyline (viền đổ bóng Neo-Brutalist + đường nét chính)
 * @param {Array<[number, number]>} latlngs - Mảng các cặp [lat, lng]
 * @param {boolean} isStreetRoute - true nếu là tuyến đường phố thực tế (nét liền), false nếu là đường chim bay (nét đứt)
 */
function updateRoutePolylines(latlngs, isStreetRoute = false) {
  if (!map || typeof L === 'undefined' || !Array.isArray(latlngs) || latlngs.length < 2) return;

  // Xóa polyline cũ trên bản đồ trước khi vẽ mới
  if (routePolyline) {
    try { map.removeLayer(routePolyline); } catch (_) {}
    routePolyline = null;
  }
  if (routeShadowPolyline) {
    try { map.removeLayer(routeShadowPolyline); } catch (_) {}
    routeShadowPolyline = null;
  }

  // 1. Lớp viền đổ bóng Neo-Brutalist (đen đậm, tạo độ nổi khối)
  routeShadowPolyline = L.polyline(latlngs, {
    color: '#000000',
    weight: 9,
    opacity: 0.45,
    lineCap: 'round',
    lineJoin: 'round',
  }).addTo(map);

  // 2. Lớp đường nét chính nổi bật
  routePolyline = L.polyline(latlngs, {
    color: '#4F46E5', // Neo-Brutalist Indigo / Purple nổi bật
    weight: 5,
    opacity: 0.95,
    dashArray: isStreetRoute ? null : '10, 8', // Nét liền khi là đường phố thực tế, nét đứt khi fallback
    lineCap: 'round',
    lineJoin: 'round',
  }).addTo(map);

  map.fitBounds(routePolyline.getBounds(), {
    padding: [60, 60],
    maxZoom: 15,
  });
}

/**
 * FE-06: Vẽ Tuyến Đường Đa Điểm trên Leaflet theo Mạng Lưới Đường Phố Thực Tế (OSRM Routing)
 * @param {Array} stops - Danh sách stops [{ place: {...}, step_order: 1, ... }]
 * @param {Object} userPos - { lat, lng }
 */
export async function renderItineraryRoute(stops = [], userPos = null) {
  if (!map || typeof L === 'undefined') return;

  // Xóa tuyến đường cũ trước khi vẽ mới
  clearItineraryRoute();
  const currentReq = ++routeRequestId;

  // Ẩn tạm markers thông thường để tập trung vào lộ trình
  if (markerGroup) {
    markerGroup.clearLayers();
  }

  const directLatLngs = [];
  const waypoints = []; // [{ lat, lng }]

  if (userPos && userPos.lat && userPos.lng) {
    directLatLngs.push([userPos.lat, userPos.lng]);
    waypoints.push({ lat: Number(userPos.lat), lng: Number(userPos.lng) });
  }

  stops.forEach((stop, index) => {
    const place = stop.place || stop;
    const stepNum = stop.step_order || (index + 1);
    directLatLngs.push([place.lat, place.lng]);
    waypoints.push({ lat: Number(place.lat), lng: Number(place.lng) });

    // Tạo numbered marker [1], [2], [3]...
    const placeType = place.place_type || 'food';
    const numberedIcon = L.divIcon({
      className: '',
      html: `<div class="marker-pin marker-pin--numbered marker-pin--${placeType}">
               <div class="marker-pin__drop"></div>
               <span class="marker-pin__num">${stepNum}</span>
             </div>`,
      iconSize: [36, 44],
      iconAnchor: [18, 44],
      popupAnchor: [0, -46],
    });

    const marker = L.marker([place.lat, place.lng], {
      icon: numberedIcon,
      zIndexOffset: 1200 + stepNum,
      title: `${stepNum}. ${place.name}`,
    }).addTo(map);

    marker.on('click', () => {
      showPopup(marker, place);
    });

    routeMarkers.push(marker);
  });

  if (directLatLngs.length < 2) return;

  // 1. Vẽ ngay đường nối thẳng (chim bay) tức thì làm fallback và tránh trễ giao diện
  updateRoutePolylines(directLatLngs, false);

  // 2. Tự động gọi OSRM API để lấy hình học đường đi uốn lượn chính xác theo đường phố thực tế
  try {
    routeAbortController = new AbortController();
    const timeoutId = setTimeout(() => {
      try { routeAbortController?.abort(); } catch (_) {}
    }, 6000); // 6s timeout phòng trường hợp mạng yếu hoặc OSRM bận

    // OSRM nhận định dạng {kinh_do},{vi_do} tức {lng},{lat} ngăn cách bởi dấu chấm phẩy ;
    const coordString = waypoints
      .map(p => `${p.lng.toFixed(6)},${p.lat.toFixed(6)}`)
      .join(';');

    // Kiểm tra bộ nhớ đệm (LRU cache) để tránh gọi mạng dư thừa
    if (osrmRouteCache.has(coordString)) {
      const cachedCoords = osrmRouteCache.get(coordString);
      updateRoutePolylines(cachedCoords, true);
      return;
    }

    const url = `https://router.project-osrm.org/route/v1/driving/${coordString}?overview=full&geometries=geojson`;

    const response = await fetch(url, {
      signal: routeAbortController.signal,
    });
    clearTimeout(timeoutId);

    // Kiểm tra xem yêu cầu này có còn là mới nhất hay không
    if (currentReq !== routeRequestId) return;

    if (!response.ok) {
      throw new Error(`OSRM HTTP error: ${response.status}`);
    }

    const data = await response.json();
    if (currentReq !== routeRequestId) return;

    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
      const route = data.routes[0];
      const geoCoords = route.geometry?.coordinates; // Mảng [[lng, lat], ...]
      if (Array.isArray(geoCoords) && geoCoords.length >= 2) {
        // Đảo ngược [lng, lat] thành [lat, lng] cho Leaflet L.polyline
        const streetLatLngs = geoCoords.map(([lng, lat]) => [lat, lng]);

        // Lưu vào LRU Cache
        if (osrmRouteCache.size >= MAX_ROUTE_CACHE) {
          const firstKey = osrmRouteCache.keys().next().value;
          osrmRouteCache.delete(firstKey);
        }
        osrmRouteCache.set(coordString, streetLatLngs);

        updateRoutePolylines(streetLatLngs, true);
        console.info(`[OSRM Routing] Tuyến đường phố thực tế đã vẽ: ${geoCoords.length} điểm, ${(route.distance / 1000).toFixed(1)} km`);
      }
    } else {
      console.warn('[OSRM Routing] Phản hồi không có tuyến hợp lệ:', data.message || data.code);
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      console.warn('[OSRM Routing] Lỗi hoặc timeout OSRM API, tự động giữ đường chim bay fallback:', err.message);
    }
    // Cơ chế fallback: giữ nguyên đường polyline nối thẳng đã vẽ ở bước 1, không crash bản đồ
  }
}

/**
 * Xóa tuyến đường lộ trình và các marker số
 */
export function clearItineraryRoute() {
  routeRequestId++;
  if (routeAbortController) {
    try { routeAbortController.abort(); } catch (_) {}
    routeAbortController = null;
  }
  if (routePolyline && map) {
    try { map.removeLayer(routePolyline); } catch (_) {}
    routePolyline = null;
  }
  if (routeShadowPolyline && map) {
    try { map.removeLayer(routeShadowPolyline); } catch (_) {}
    routeShadowPolyline = null;
  }
  if (routeMarkers && routeMarkers.length > 0 && map) {
    routeMarkers.forEach(m => {
      try { map.removeLayer(m); } catch (_) {}
    });
    routeMarkers = [];
  }
}

