<?php
declare(strict_types=1);

$file = 'public/admin/restaurant_form.php';
$content = file_get_contents($file);

$oldTile = "L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{maxZoom:19,subdomains:'abcd',attribution:'© OpenStreetMap © CARTO'}).addTo(map);";
$newTile = "L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'}).addTo(map);";

if (str_contains($content, $oldTile)) {
    $content = str_replace($oldTile, $newTile, $content);
    file_put_contents($file, $content);
    echo "Đã thay thế tile Carto sang OpenStreetMap miễn phí thành công!\n";
} else {
    echo "Không tìm thấy chuỗi tile cũ.\n";
}

