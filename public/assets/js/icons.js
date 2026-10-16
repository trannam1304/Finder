/* Small, local SVG icon set.  Icons are deliberately stroke based for a unified UI. */
const paths = {
  map: '<path d="M3 5.5 8 3l5 2.5L21 3v15.5l-8 2.5-5-2.5L3 21V5.5Z"/><path d="M8 3v15.5M13 5.5V21"/>',
  explore: '<path d="M4 7h16v13H4z"/><path d="M8 7V4h8v3M8 12h8M8 16h5"/>',
  settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.02 2.02-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.55v.09h-2.86v-.09a1.7 1.7 0 0 0-1.03-1.55 1.7 1.7 0 0 0-1.88.34l-.06.06-2.02-2.02.06-.06A1.7 1.7 0 0 0 7.36 15a1.7 1.7 0 0 0-1.55-1.03h-.09v-2.86h.09A1.7 1.7 0 0 0 7.36 10 1.7 1.7 0 0 0 7.02 8.1l-.06-.06 2.02-2.02.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.55v-.09h2.86v.09A1.7 1.7 0 0 0 15.84 6.4a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.02 2.02-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.55 1.03h.09v2.86h-.09A1.7 1.7 0 0 0 19.4 15Z"/>',
  moon: '<path d="M20.5 15.2A8.6 8.6 0 0 1 8.8 3.5 8.7 8.7 0 1 0 20.5 15.2Z"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
  random: '<path d="M4 6h3c4.5 0 5.5 12 10 12h3"/><path d="m18 15 3 3-3 3M4 18h3c1.7 0 2.8-1.7 3.8-3.8M15.2 9.8C16.2 7.7 17.3 6 19 6h2"/><path d="m18 3 3 3-3 3"/>',
  search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
  pin: '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
  phone: '<path d="M7 3h3l1.3 4-2 1.5c1.2 2.4 2.9 4.1 5.3 5.3l1.5-2 4 1.3v3c0 1.1-.9 2-2 2C10.3 18.1 5.9 13.7 5.9 8A2 2 0 0 1 7 3Z"/>',
  clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/>',
  cash: '<rect x="3" y="6" width="18" height="12" rx="1"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9h.01M18 15h.01"/>',
  fork: '<path d="M7 3v8M4.5 3v5a2.5 2.5 0 0 0 5 0V3M7 11v10M16 3v18M16 3c3.5 2.5 3.5 7.5 0 10"/>',
  route: '<path d="M20 5c0 2-2 4-4 4s-4-2-4-4 2-3 4-3 4 1 4 3ZM8 19c0 2-2 3-4 3s-4-1-4-3 2-4 4-4 4 2 4 4Z"/><path d="M4 15c0-7 12 2 12-6"/>',
  share: '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
  heart: '<path d="M20.8 8.6c0 5-8.8 10.8-8.8 10.8S3.2 13.6 3.2 8.6A4.6 4.6 0 0 1 12 6.8a4.6 4.6 0 0 1 8.8 1.8Z"/>',
  filter: '<path d="M3 5h18M6 12h12M10 19h4"/>',
  close: '<path d="m5 5 14 14M19 5 5 19"/>',
  warning: '<path d="M12 3 22 21H2L12 3Z"/><path d="M12 9v5M12 17h.01"/>',
  empty: '<path d="M4 8h16v11H4z"/><path d="M7 8V5h10v3M8 13h8M10 17h4"/>',
  arrowLeft: '<path d="M19 12H5M11 6l-6 6 6 6"/>',
  plate: '<path d="M4 15h16M6 15a6 6 0 0 1 12 0M3 19h18"/><path d="M12 7V4M10 5h4"/>',
  rice: '<path d="M4 16h16M6 16a6 6 0 0 1 12 0M5 20h14"/><path d="M8 11c0-2 1-4 2-4s2 2 2 4M13 11c0-2 1-4 2-4s2 2 2 4"/>',
  noodles: '<path d="M4 15h16l-2 5H6l-2-5Z"/><path d="M7 11c0-2 2-2 2-4s2-2 2-4M12 11c0-2 2-2 2-4s2-2 2-4"/>',
  bread: '<path d="M4 17c0-4 1-8 5-8 3 0 4 2 5 2 2 0 3 2 3 4v2H4Z"/><path d="M8 12c1 1 2 1 3 0M13 13c1 1 2 1 3 0"/>',
  grill: '<path d="M4 9h16M6 9l2 10M18 9l-2 10M5 19h14M8 5h8"/><path d="M9 5c0-1.5 1-2 1-3M13 5c0-1.5 1-2 1-3"/>',
  leaf: '<path d="M20 4C10 4 5 8 5 15c0 3 2 5 5 5 7 0 10-7 10-16Z"/><path d="M4 21c4-5 8-8 13-11"/>',
  coffee: '<path d="M4 8h12v7a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V8Z"/><path d="M16 10h2a2 2 0 0 1 0 4h-2M6 4c0 1 1 1 1 2M10 4c0 1 1 1 1 2"/>',
  snack: '<path d="M2 22l20-20"/><circle cx="8.5" cy="15.5" r="3"/><circle cx="12" cy="12" r="3"/><circle cx="15.5" cy="8.5" r="3"/><path d="M7 14l3 3M10.5 10.5l3 3M14 7l3 3"/>',
  star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="currentColor"/>',
  starEmpty: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="none"/>',
  starHalf: '<path d="M12 2v15.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="none"/>',
  logo: '<circle cx="12" cy="12" r="10"/><path d="M7 15c0-3 1.5-6 5-6s5 3 5 6"/><circle cx="12" cy="7.5" r="1.5"/><path d="M6 17h12"/>',
  dot: '<rect x="8" y="8" width="8" height="8" fill="currentColor"/>',
  tourism: '<path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6M10 10h4"/>',
  entertainment: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2"/><path d="m12 3 2.5 7h7.5l-6 4.5 2.5 7.5-6.5-5-6.5 5 2.5-7.5-6-4.5h7.5Z"/>',
  ticket: '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z"/><path d="M13 6v12M9 10v.01M9 14v.01"/>',
  calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  refresh: '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
  sparkles: '<path d="m12 3 1.9 5.6a2 2 0 0 0 1.25 1.25L21 12l-5.85 1.9a2 2 0 0 0-1.25 1.25L12 21l-1.9-5.85a2 2 0 0 0-1.25-1.25L3 12l5.85-1.9a2 2 0 0 0 1.25-1.25L12 3Z"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
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
  'bao-tang': 'tourism',
  'cong-vien': 'leaf',
  'di-tich': 'tourism',
  'check-in': 'compass',
  'van-dong': 'entertainment',
  'rap-phim': 'entertainment',
  'board-game': 'entertainment',
  'mua-sam': 'entertainment',
};

export function icon(name, className = '') {
  const path = paths[name] || paths.plate;
  return `<svg class="ui-icon ${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">${path}</svg>`;
}

export function categoryIcon(category, className = 'category-glyph') {
  const slug = category?.slug;
  const group = category?.type_group;
  let iconName = categoryNames[slug];
  if (!iconName) {
    if (group === 'tourism') iconName = 'tourism';
    else if (group === 'entertainment') iconName = 'entertainment';
    else iconName = 'plate';
  }
  return icon(iconName, className);
}

export function placeTypeIcon(type, className = '') {
  if (type === 'tourism') return icon('tourism', className);
  if (type === 'entertainment') return icon('entertainment', className);
  return icon('fork', className);
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

