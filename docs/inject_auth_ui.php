<?php
declare(strict_types=1);

$files = [
    'public/index.html',
    'public/explore.html',
    'public/favorites.html',
    'public/about.html',
    'public/suggest.html',
    'public/restaurant.html',
];

foreach ($files as $file) {
    if (!is_file($file)) continue;

    $content = file_get_contents($file);

    // 1. Chèn auth.css vào <head> nếu chưa có
    if (!str_contains($content, 'assets/css/auth.css')) {
        $content = str_replace('</head>', "  <link rel=\"stylesheet\" href=\"assets/css/auth.css\">\n</head>", $content);
    }

    // 2. Chèn user-nav-container vào header
    if (!str_contains($content, 'user-nav-container')) {
        if (str_contains($content, 'id="btn-theme"')) {
            $content = preg_replace(
                '/(<button[^>]*id="btn-theme"[^>]*>)/',
                '<div id="user-nav-container" class="user-nav"></div>' . "\n        $1",
                $content,
                1
            );
        } elseif (str_contains($content, 'class="header__actions"')) {
            $content = str_replace(
                '<div class="header__actions">',
                "<div class=\"header__actions\">\n        <div id=\"user-nav-container\" class=\"user-nav\"></div>",
                $content
            );
        } elseif (str_contains($content, 'class="page-nav__action"')) {
            $content = str_replace(
                '<div class="page-nav__action">',
                "<div class=\"page-nav__action\">\n      <div id=\"user-nav-container\" class=\"user-nav\"></div>",
                $content
            );
        }
    }

    // 3. Chèn auth.js vào cuối body nếu chưa có
    if (!str_contains($content, 'assets/js/auth.js')) {
        $content = str_replace('</body>', "  <script type=\"module\" src=\"assets/js/auth.js\"></script>\n</body>", $content);
    }

    file_put_contents($file, $content);
    echo "Đã tích hợp Auth UI vào $file\n";
}

echo "Hoàn thành cập nhật tất cả giao diện!\n";

