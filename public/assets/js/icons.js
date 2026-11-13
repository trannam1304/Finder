/* Small, local SVG icon set. Icons are deliberately stroke based for a unified Neo-Brutalist UI. */
const paths = {
  all: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
  food: '<path d="M4 11h16a1 1 0 0 1 1 1c0 5-4 9-9 9s-9-4-9-9a1 1 0 0 1 1-1Z"/><path d="M7 11V4M12 11V3M17 11V5"/><path d="M2 21h20"/>',
  tourism: '<path d="M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M2 10h20L12 3 2 10ZM12 14v3"/>',
  entertainment: '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="2.5"/><path d="M12 3.5V1M12 23v-2.5M3.5 12H1M23 12h-2.5M6 6l-1.8-1.8M19.8 19.8 18 18M6 18l-1.8 1.8M19.8 4.2 18 6"/><path d="M8 22h8"/>',
  map: '<path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15M15 6v15"/>',
  explore: '<path d="M4 7h16v13H4z"/><path d="M8 7V4h8v3M8 12h8M8 16h5"/>',
  settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.02 2.02-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.55v.09h-2.86v-.09a1.7 1.7 0 0 0-1.03-1.55 1.7 1.7 0 0 0-1.88.34l-.06.06-2.02-2.02.06-.06A1.7 1.7 0 0 0 7.36 15a1.7 1.7 0 0 0-1.55-1.03h-.09v-2.86h.09A1.7 1.7 0 0 0 7.36 10 1.7 1.7 0 0 0 7.02 8.1l-.06-.06 2.02-2.02.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.55v-.09h2.86v.09A1.7 1.7 0 0 0 15.84 6.4a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.02 2.02-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.55 1.03h.09v2.86h-.09A1.7 1.7 0 0 0 19.4 15Z"/>',
  moon: '<path d="M20.5 15.2A8.6 8.6 0 0 1 8.8 3.5 8.7 8.7 0 1 0 20.5 15.2Z"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
  random: '<path d="M4 6h3c4.5 0 5.5 12 10 12h3"/><path d="m18 15 3 3-3 3M4 18h3c1.7 0 2.8-1.7 3.8-3.8M15.2 9.8C16.2 7.7 17.3 6 19 6h2"/><path d="m18 3 3 3-3 3"/>',
  search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
  pin: '<path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Z"/><circle cx="12" cy="9" r="2.5"/>',
  phone: '<path d="M7 3h3l1.3 4-2 1.5c1.2 2.4 2.9 4.1 5.3 5.3l1.5-2 4 1.3v3c0 1.1-.9 2-2 2C10.3 18.1 5.9 13.7 5.9 8A2 2 0 0 1 7 3Z"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  timer: '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M10 2h4M12 2v3"/>',
  car: '<path d="M5 16h14a2 2 0 0 0 2-2v-3a2 2 0 0 0-1-1.73l-2.5-4.27A2 2 0 0 0 15.78 4H8.22a2 2 0 0 0-1.72 1L4 9.27A2 2 0 0 0 3 11v3a2 2 0 0 0 2 2Z"/><circle cx="7" cy="16" r="2"/><circle cx="17" cy="16" r="2"/><path d="M5 11h14"/>',
  lightbulb: '<path d="M9 18h6M10 21h4M12 2a6 6 0 0 0-6 6c0 2.5 1.5 4.5 2.5 5.5.5.5.5 1.5.5 2.5h6c0-1 0-2 .5-2.5 1-1 2.5-3 2.5-5.5a6 6 0 0 0-6-6Z"/>',
  zap: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
  rocket: '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09zM12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="m9 12 2.5-2.5M14 7l2.5-2.5"/>',
  cash: '<rect x="3" y="6" width="18" height="12" rx="1"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9h.01M18 15h.01"/>',
  fork: '<path d="M7 3v8M4.5 3v5a2.5 2.5 0 0 0 5 0V3M7 11v10M16 3v18M16 3c3.5 2.5 3.5 7.5 0 10"/>',
  route: '<circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M12 19h4.5a3.5 3.5 0 0 0 0-7h-9a3.5 3.5 0 0 1 0-7H12"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.59 13.51 6.83 3.98M15.41 6.51l-6.82 3.98"/>',
  heart: '<path d="M19.5 12.572 12 20l-7.5-7.428A5 5 0 1 1 12 6.006a5 5 0 1 1 7.5 6.572Z"/>',
  filter: '<path d="M3 5h18M6 12h12M10 19h4"/>',
  close: '<path d="m18 6-12 12M6 6l12 12"/>',
  warning: '<path d="M12 3 22 21H2L12 3Z"/><path d="M12 9v5M12 17h.01"/>',
  empty: '<path d="M4 8h16v11H4z"/><path d="M7 8V5h10v3M8 13h8M10 17h4"/>',
  arrowLeft: '<path d="M19 12H5M11 6l-6 6 6 6"/>',
  plate: '<path d="M4 15h16M6 15a6 6 0 0 1 12 0M3 19h18"/><path d="M12 7V4M10 5h4"/>',
  rice: '<path d="M4 16h16M6 16a6 6 0 0 1 12 0M5 20h14"/><path d="M8 11c0-2 1-4 2-4s2 2 2 4M13 11c0-2 1-4 2-4s2 2 2 4"/>',
  noodles: '<path d="M4 15h16l-2 5H6l-2-5Z"/><path d="M7 11c0-2 2-2 2-4s2-2 2-4M12 11c0-2 2-2 2-4s2-2 2-4"/>',
  bread: '<path d="M4 17c0-4 1-8 5-8 3 0 4 2 5 2 2 0 3 2 3 4v2H4Z"/><path d="M8 12c1 1 2 1 3 0M13 13c1 1 2 1 3 0"/>',
  grill: '<path d="M4 9h16M6 9l2 10M18 9l-2 10M5 19h14M8 5h8"/><path d="M9 5c0-1.5 1-2 1-3M13 5c0-1.5 1-2 1-3"/>',
  leaf: '<path d="M11 20A7 7 0 0 1 4 13C4 7 12 4 20 4c0 8-3 16-9 16Z"/><path d="M4 20c4-4 8-7 16-16"/>',
  coffee: '<path d="M4 8h12v7a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V8Z"/><path d="M16 10h2a2 2 0 0 1 0 4h-2M6 4c0 1 1 1 1 2M10 4c0 1 1 1 1 2"/>',
  snack: '<path d="M2 22l20-20"/><circle cx="8.5" cy="15.5" r="3"/><circle cx="12" cy="12" r="3"/><circle cx="15.5" cy="8.5" r="3"/><path d="M7 14l3 3M10.5 10.5l3 3M14 7l3 3"/>',
  landmark: '<path d="M3 21h18M4 18h16M6 18v-7M10 18v-7M14 18v-7M18 18v-7M12 3 3 8h18L12 3Z"/>',
  trees: '<path d="M12 3 6 11h4l-3 6h10l-3-6h4L12 3Z"/><path d="M12 17v4M9 21h6"/>',
  monument: '<path d="M4 21h16M6 21V9l6-5 6 5v12"/><path d="M10 21v-5a2 2 0 0 1 4 0v5M9 11h6"/>',
  camera: '<path d="M4 8h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.5"/>',
  trophy: '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3"/>',
  film: '<rect x="3" y="4" width="18" height="16" rx="1"/><path d="M7 4v16M17 4v16M3 8h4M17 8h4M3 12h18M3 16h4M17 16h4"/>',
  gamepad: '<rect x="2" y="6" width="20" height="12" rx="3"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/>',
  bag: '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
  ticket: '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z"/><path d="M13 6v12M9 10v.01M9 14v.01"/>',
  star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="currentColor"/>',
  starEmpty: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="none"/>',
  starHalf: '<path d="M12 2v15.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="none"/>',
  logo: '<circle cx="12" cy="12" r="10"/><path d="M7 15c0-3 1.5-6 5-6s5 3 5 6"/><circle cx="12" cy="7.5" r="1.5"/><path d="M6 17h12"/>',
  dot: '<rect x="8" y="8" width="8" height="8" fill="currentColor"/>',
  refresh: '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
  sparkles: '<path d="m12 3 1.9 4.8L18 9.5l-4.1 1.7L12 16l-1.9-4.8L6 9.5l4.1-1.7L12 3Z"/><path d="M18 16l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2Z"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  copy: '<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
  compass: '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
};

const categoryNames = {
  com: 'rice',
  'bun-pho': 'noodles',
  'banh-mi': 'bread',
  'lau-nuong': 'grill',
  'an-vat': 'snack',
  chay: 'leaf',
  'ca-phe': 'coffee',
  'bao-tang': 'landmark',
  'cong-vien': 'trees',
  'di-tich': 'monument',
  'check-in': 'camera',
  'van-dong': 'trophy',
  'rap-phim': 'film',
  'board-game': 'gamepad',
  'mua-sam': 'bag',
};

export function icon(name, className = '') {
  const path = paths[name] || paths.plate;
  return `<svg class="ui-icon ${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${path}</svg>`;
}

export function categoryIcon(category, className = 'category-glyph') {
  const key = categoryNames[category?.slug] || category?.icon || 'plate';
  return icon(key, className);
}

export function placeTypeIcon(type, className = '') {
  if (type === 'tourism') return icon('tourism', className);
  if (type === 'entertainment') return icon('entertainment', className);
  if (type === 'all') return icon('all', className);
  return icon('food', className);
}

export function setIcon(element, name, className = '') {
  if (element) element.innerHTML = icon(name, className);
}

export function renderStars(rating, max = 5) {
  const num = Number(rating) || 0;
  const full = Math.min(max, Math.floor(num));
  const half = (num - full >= 0.5 && full < max) ? 1 : 0;
  const empty = Math.max(0, max - full - half);
  let html = '';
  for (let i = 0; i < full; i++) html += icon('star', 'ui-icon--star ui-icon--star-filled');
  if (half) html += icon('starHalf', 'ui-icon--star ui-icon--star-half');
  for (let i = 0; i < empty; i++) html += icon('starEmpty', 'ui-icon--star ui-icon--star-empty');
  return html;
}

