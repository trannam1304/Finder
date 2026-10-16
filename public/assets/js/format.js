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
 * Tạo URL xem vị trí ghim (drop pin) trên Google Maps
 */
export function getGoogleMapsPinUrl(restaurant) {
  if (!restaurant) return '#';
  const lat = restaurant.lat ?? restaurant.latitude;
  const lng = restaurant.lng ?? restaurant.longitude;
  if (lat && lng) {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  }
  const target = encodeURIComponent([restaurant.name, restaurant.address].filter(Boolean).join(', '));
  return `https://www.google.com/maps/search/?api=1&query=${target}`;
}

