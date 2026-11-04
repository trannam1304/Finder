<?php
declare(strict_types=1);

$files = [
    'public/about.html',
    'public/explore.html',
    'public/favorites.html',
    'public/index.html',
    'public/restaurant.html',
    'public/suggest.html',
];

foreach ($files as $file) {
    if (!is_file($file)) continue;

    $content = file_get_contents($file);

    // Chuyển nút Admin từ btn--primary (màu đỏ nổi bật gây nhầm lẫn) sang btn--ghost (link phụ)
    $content = preg_replace(
        '/<a\s+href="admin\/login\.php"\s+class="btn\s+btn--primary\s+btn--sm"([^>]*)>\s*Quản trị\s*<\/a>/u',
        '<a href="admin/login.php" class="btn btn--ghost btn--sm"$1 title="Dành cho Quản trị viên">Quản trị</a>',
        $content
    );

    file_put_contents($file, $content);
    echo "Đã cập nhật nút Admin trong $file\n";
}

echo "Hoàn thành cập nhật nút Quản trị!\n";

