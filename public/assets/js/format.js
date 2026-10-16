/* ============================================================
   FORMAT.JS — Hàm định dạng hiển thị
   ============================================================ */

/**
 * Định dạng khoảng cách: dưới 950m hiện "m", trên hiện "km" (dấu phẩy thập phân kiểu Việt)
 */
export function formatDistance(km) {
  const m = km * 1000;
  if (m < 950) return `${Math.round(m / 10) * 10} m`;
  return `${km.toFixed(1).replace('.', ',')} km`;
}

/**
 * Định dạng khoảng giá: "25–40k"
 */
export function formatPrice(min, max) {
  if (!min && !max) return '';
  const fmtK = (v) => {
    if (v >= 1000000) return `${(v / 1000000).toFixed(1).replace('.0', '')}tr`;
    if (v >= 1000) return `${Math.round(v / 1000)}k`;
    return `${v}đ`;
  };
  if (min && max && min !== max) return `${fmtK(min)}–${fmtK(max)}`;
  return fmtK(min || max);
}

/**
 * Escape HTML — chống XSS khi buộc phải dùng template chuỗi
 */
export function escapeText(s) {
  return String(s).replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

export { renderStars } from './icons.js';

/**
 * Kiểm tra quán có đang mở cửa không
 */
export function isOpenNow(openTime, closeTime, is24h) {
  if (is24h) return true;
  if (!openTime || !closeTime) return null; // không rõ

  const now = new Date();
  const currentMinutes = now.getHours() * 60 + now.getMinutes();

  const parseTime = (t) => {
    const parts = t.split(':');
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
  };

  const open = parseTime(openTime);
  const close = parseTime(closeTime);

  // Ca qua đêm (vd: 18:00 - 02:00)
  if (close <= open) {
    return currentMinutes >= open || currentMinutes < close;
  }
  return currentMinutes >= open && currentMinutes < close;
}

/**
 * Định dạng giờ: "06:00" → "6:00"
 */
export function formatTime(timeStr) {
  if (!timeStr) return '';
  const parts = timeStr.split(':');
  return `${parseInt(parts[0])}:${parts[1]}`;
}

/**
 * Định dạng nhãn loại hình địa điểm
 */
export function formatPlaceType(type) {
  switch (type) {
    case 'tourism': return 'Du lịch & Tham quan';
    case 'entertainment': return 'Vui chơi giải trí';
    default: return 'Ẩm thực & Quán ăn';
  }
}

/**
 * Định dạng giá vé / vé vào cổng
 */
export function formatTicketPrice(ticketPrice) {
  const price = Number(ticketPrice) || 0;
  if (price <= 0) return 'Vé miễn phí';
  if (price >= 1000000) return `${(price / 1000000).toFixed(1).replace('.0', '')}tr đ/vé`;
  if (price >= 1000) return `${Math.round(price / 1000)}k đ/vé`;
  return `${price.toLocaleString('vi-VN')} đ/vé`;
}

/**
 * Định dạng thời lượng trải nghiệm
 */
export function formatDuration(minutes) {
  const m = Number(minutes) || 60;
  if (m >= 60) {
    const h = Math.floor(m / 60);
    const rem = m % 60;
    return rem > 0 ? `~${h}h ${rem}p` : `~${h} giờ`;
  }
  return `~${m} phút`;
}

/**
 * Tạo URL chỉ đường (Google Maps / Apple Maps) ưu tiên địa chỉ và tên quán chính xác
 */
export function getDirectionsUrl(restaurant) {
  if (!restaurant) return '#';
  const isApple = /iPad|iPhone|iPod|Macintosh/.test(navigator.userAgent);
  const target = [restaurant.name, restaurant.address].filter(Boolean).join(', ');
  const destQuery = encodeURIComponent(target.trim() || `${restaurant.lat},${restaurant.lng}`);

  if (isApple) {
    return `https://maps.apple.com/?daddr=${encodeURIComponent(restaurant.address || '')}&q=${encodeURIComponent(restaurant.name || '')}&dirflg=d`;
  }
  return `https://www.google.com/maps/dir/?api=1&destination=${destQuery}&travelmode=driving`;
}

/**
 * Tạo URL chỉ đường toàn bộ lộ trình đa điểm (Multi-waypoint Route) trên Google Maps
 */
export function getMultiStopDirectionsUrl(origin, stops = []) {
  if (!stops || stops.length === 0) return '#';

  const originQuery = origin && origin.lat && origin.lng
    ? `${origin.lat},${origin.lng}`
    : `${stops[0].lat},${stops[0].lng}`;

  const lastStop = stops[stops.length - 1];
  const destQuery = `${lastStop.lat},${lastStop.lng}`;

  // Các điểm dừng trung gian (waypoints)
  const waypointStops = stops.slice(0, stops.length - 1);
  const waypointsQuery = waypointStops.map(s => `${s.lat},${s.lng}`).join('|');

  let url = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(originQuery)}&destination=${encodeURIComponent(destQuery)}&travelmode=driving`;
  if (waypointsQuery) {
    url += `&waypoints=${encodeURIComponent(waypointsQuery)}`;
  }
  return url;
}

