<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

function h(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function admin_flash(?string $message = null, string $type = 'success'): ?array {
    start_secure_session();
    if ($message !== null) { $_SESSION['flash'] = ['message' => $message, 'type' => $type]; return null; }
    $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $flash;
}
function admin_svg_icon(string $name, string $class = 'ui-icon'): string {
    $paths = [
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="var(--c-accent, #ffd400)"/>',
        'rice' => '<path d="M4 16h16M6 16a6 6 0 0 1 12 0M5 20h14"/><path d="M8 11c0-2 1-4 2-4s2 2 2 4M13 11c0-2 1-4 2-4s2 2 2 4"/>',
        'noodles' => '<path d="M4 15h16l-2 5H6l-2-5Z"/><path d="M7 11c0-2 2-2 2-4s2-2 2-4M12 11c0-2 2-2 2-4s2-2 2-4"/>',
        'bread' => '<path d="M4 17c0-4 1-8 5-8 3 0 4 2 5 2 2 0 3 2 3 4v2H4Z"/><path d="M8 12c1 1 2 1 3 0M13 13c1 1 2 1 3 0"/>',
        'grill' => '<path d="M4 9h16M6 9l2 10M18 9l-2 10M5 19h14M8 5h8"/><path d="M9 5c0-1.5 1-2 1-3M13 5c0-1.5 1-2 1-3"/>',
        'snack' => '<path d="M2 22l20-20"/><circle cx="8.5" cy="15.5" r="3"/><circle cx="12" cy="12" r="3"/><circle cx="15.5" cy="8.5" r="3"/><path d="M7 14l3 3M10.5 10.5l3 3M14 7l3 3"/>',
        'leaf' => '<path d="M20 4C10 4 5 8 5 15c0 3 2 5 5 5 7 0 10-7 10-16Z"/><path d="M4 21c4-5 8-8 13-11"/>',
        'coffee' => '<path d="M4 8h12v7a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V8Z"/><path d="M16 10h2a2 2 0 0 1 0 4h-2M6 4c0 1 1 1 1 2M10 4c0 1 1 1 1 2"/>',
        'plate' => '<path d="M4 15h16M6 15a6 6 0 0 1 12 0M3 19h18"/><path d="M12 7V4M10 5h4"/>',
        'landmark' => '<path d="M3 22h18M6 18v-7M10 18v-7M14 18v-7M18 18v-7M12 2 20 7H4l8-5ZM4 18h16"/>',
        'trees' => '<path d="M10 10v.2A3 3 0 0 1 8.9 16v0H5v0h0a3 3 0 0 1-1-5.8V10a3 3 0 0 1 6 0Z"/><path d="M7 16v6"/><path d="M13 19v3"/><path d="M12 19h8.3a1 1 0 0 0 .7-1.7L18 14h.3a1 1 0 0 0 .7-1.7L16 9h.2a1 1 0 0 0 .8-1.7L13 3l-1.4 1.5"/>',
        'monument' => '<path d="M12 2 8 7h8l-4-5ZM9 7v11h6V7M5 22h14M7 18h10v4H7z"/>',
        'camera' => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'trophy' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        'film' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18M17 3v18M3 7.5h4M17 7.5h4M3 12h18M3 16.5h4M17 16.5h4"/>',
        'gamepad' => '<line x1="6" x2="10" y1="12" y2="12"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="15" x2="15.01" y1="13" y2="13"/><line x1="18" x2="18.01" y1="11" y2="11"/><rect width="20" height="12" x="2" y="6" rx="2"/>',
        'bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'logo' => '<circle cx="16" cy="16" r="14" fill="var(--c-primary, #ff4d2e)"/><path d="M10 20c0-4 2-8 6-8s6 4 6 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="16" cy="11" r="2" fill="#fff"/><path d="M9 22h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>',
    ];
    $svg = $paths[$name] ?? $paths['plate'];
    $viewBox = $name === 'logo' ? '0 0 32 32' : '0 0 24 24';
    $fill = $name === 'star' ? 'currentColor' : 'none';
    return '<svg class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" viewBox="' . $viewBox . '" fill="' . $fill . '" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter" style="width:1.25em;height:1.25em;vertical-align:-0.22em;display:inline-block;" aria-hidden="true">' . $svg . '</svg>';
}
function admin_category_icon(string $slugOrIcon): string {
    $alias = [
        'com' => 'rice', 'rice' => 'rice', '🍚' => 'rice',
        'bun-pho' => 'noodles', 'noodles' => 'noodles', '🍜' => 'noodles',
        'banh-mi' => 'bread', 'bread' => 'bread', '🥖' => 'bread',
        'lau-nuong' => 'grill', 'grill' => 'grill', '🔥' => 'grill',
        'an-vat' => 'snack', 'snack' => 'snack', '🍡' => 'snack',
        'chay' => 'leaf', 'leaf' => 'leaf', '🥬' => 'leaf',
        'ca-phe' => 'coffee', 'coffee' => 'coffee', '☕' => 'coffee',
        'bao-tang' => 'landmark', 'landmark' => 'landmark', '🏛️' => 'landmark',
        'cong-vien' => 'trees', 'trees' => 'trees', '🌳' => 'trees',
        'di-tich' => 'monument', 'monument' => 'monument', '⛩️' => 'monument',
        'check-in' => 'camera', 'camera' => 'camera', '📸' => 'camera',
        'van-dong' => 'trophy', 'trophy' => 'trophy', '🎳' => 'trophy',
        'rap-phim' => 'film', 'film' => 'film', '🎬' => 'film',
        'board-game' => 'gamepad', 'gamepad' => 'gamepad', '🎮' => 'gamepad',
        'mua-sam' => 'bag', 'bag' => 'bag', '🛍️' => 'bag',
    ];
    $key = $alias[trim($slugOrIcon)] ?? 'plate';
    return admin_svg_icon($key, 'ui-icon category-glyph');
}
function admin_place_type_badge(string $type): string {
    $map = [
        'food' => ['label' => '🍜 Ẩm thực', 'bg' => '#ff4d2e', 'color' => '#ffffff'],
        'tourism' => ['label' => '🏛️ Du lịch', 'bg' => '#0284c7', 'color' => '#ffffff'],
        'entertainment' => ['label' => '🎡 Vui chơi', 'bg' => '#f59e0b', 'color' => '#121212'],
    ];
    $info = $map[$type] ?? $map['food'];
    return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border:1.5px solid #181818;font-size:0.75rem;font-weight:700;background:' . $info['bg'] . ';color:' . $info['color'] . ';white-space:nowrap;">' . $info['label'] . '</span>';
}
function admin_header(string $title): void { $flash = admin_flash(); ?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($title) ?> — Finder CMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/tokens.css"><link rel="stylesheet" href="../assets/css/base.css"><link rel="stylesheet" href="../assets/css/components.css"><link rel="stylesheet" href="assets/admin.css"></head><body>
<header class="admin-header"><a href="dashboard.php" class="admin-brand"><?= admin_svg_icon('logo') ?> Finder <small>Quản trị</small></a><nav><a href="dashboard.php">Tổng quan</a><a href="index.php">Địa điểm</a><a href="categories.php">Danh mục</a><a href="reviews.php">Đánh giá</a><a href="restaurant_form.php">+ Thêm địa điểm</a><a href="../index.html" target="_blank" rel="noopener">Xem bản đồ</a><form action="logout.php" method="post" class="inline"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="link-button">Đăng xuất</button></form></nav></header><main class="admin-container">
<?php if ($flash): ?><div class="admin-flash admin-flash--<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div><?php endif; ?>
<?php }
function admin_footer(): void { ?></main></body></html><?php }

