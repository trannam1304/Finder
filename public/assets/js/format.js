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
 * Tạo URL chỉ đường (Google Maps / Apple Maps) ưu tiên toạ độ chính xác (lat, lng) khớp với marker
 * Tuyệt đối không ghép restaurant.name vào đầu chuỗi tìm kiếm để tránh Google Maps fuzzy-match sai địa điểm
 */
export function getDirectionsUrl(restaurant) {
  if (!restaurant) return '#';
  const lat = restaurant.lat ?? restaurant.latitude;
  const lng = restaurant.lng ?? restaurant.longitude;
  const isApple = /iPad|iPhone|iPod|Macintosh/.test(navigator.userAgent);

  if (lat && lng) {
    if (isApple) {
      return `https://maps.apple.com/?daddr=${lat},${lng}&dirflg=d`;
    }
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
  }

  // Fallback nếu không có tọa độ: dùng địa chỉ kèm TP. Hồ Chí Minh
  const addr = restaurant.address || '';
  const fallbackAddr = addr
    ? (addr.includes('Hồ Chí Minh') ? addr : `${addr}, TP. Hồ Chí Minh`)
    : '';

  if (isApple) {
    return `https://maps.apple.com/?daddr=${encodeURIComponent(fallbackAddr)}&dirflg=d`;
  }
  return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(fallbackAddr)}&travelmode=driving`;
}

/**
 * Tạo URL chỉ đường toàn bộ lộ trình đa điểm (Multi-waypoint Route) trên Google Maps (Task FE-06)
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

/**
 * Tạo URL xem vị trí ghim (drop pin) trên Google Maps
 * Ưu tiên toạ độ chính xác (lat, lng) để ghim đúng 100% vị trí marker
 */
export function getGoogleMapsPinUrl(restaurant) {
  if (!restaurant) return '#';
  const lat = restaurant.lat ?? restaurant.latitude;
  const lng = restaurant.lng ?? restaurant.longitude;
  if (lat && lng) {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  }
  const addr = restaurant.address || '';
  const fallbackAddr = addr
    ? (addr.includes('Hồ Chí Minh') ? addr : `${addr}, TP. Hồ Chí Minh`)
    : '';
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(fallbackAddr)}`;
}

/**
 * Thông tin hiển thị theo loại hình địa điểm (place_type)
 */
export function getPlaceTypeInfo(placeType = 'food') {
  switch (placeType) {
    case 'tourism':
      return {
        key: 'tourism',
        label: 'Du lịch',
        fullLabel: '🏛️ Du lịch & Tham quan',
        emoji: '🏛️',
        badgeClass: 'badge--tourism',
        markerClass: 'marker-pin--tourism',
      };
    case 'entertainment':
      return {
        key: 'entertainment',
        label: 'Vui chơi',
        fullLabel: '🎡 Vui chơi giải trí',
        emoji: '🎡',
        badgeClass: 'badge--entertainment',
        markerClass: 'marker-pin--entertainment',
      };
    default:
      return {
        key: 'food',
        label: 'Ẩm thực',
        fullLabel: '🍜 Ẩm thực & Đồ uống',
        emoji: '🍜',
        badgeClass: 'badge--food',
        markerClass: 'marker-pin--food',
      };
  }
}

/**
 * Định dạng giá vé vào cổng hoặc khoảng giá cho địa điểm
 */
export function formatTicketOrPrice(place) {
  if (!place) return '';
  const placeType = place.place_type || place.category?.type_group || 'food';
  const ticketPrice = Number(place.ticket_price || 0);
  const priceMin = Number(place.price_min || 0);
  const priceMax = Number(place.price_max || 0);

  if (placeType !== 'food') {
    if (ticketPrice > 0) {
      return `Vé: ${formatPrice(ticketPrice, priceMax > ticketPrice ? priceMax : ticketPrice)}`;
    }
    if (priceMin === 0 && priceMax === 0) {
      return 'Vé miễn phí';
    }
    if (ticketPrice === 0 && priceMax > 0) {
      return `Vào cổng miễn phí (${formatPrice(0, priceMax)})`;
    }
    return 'Vé miễn phí';
  }

  const p = formatPrice(priceMin, priceMax);
  return p || 'Đang cập nhật';
}

/**
 * Định dạng thời lượng trải nghiệm đề xuất (phút)
 */
export function formatDuration(minutes) {
  const m = Number(minutes || 0);
  if (!m || m <= 0) return '';
  return `Gợi ý: ${m} phút`;
}

/**
 * Định dạng khung giờ lý tưởng ghé thăm
 */
export function formatBestTimeSlot(slot) {
  switch (slot) {
    case 'morning':   return 'Buổi sáng (06:00 – 11:00)';
    case 'afternoon': return 'Buổi chiều (13:00 – 17:30)';
    case 'evening':   return 'Buổi tối (17:30 – 22:30)';
    default:          return 'Mọi khung giờ trong ngày';
  }
}
