<?php
declare(strict_types=1);

$file = 'public/assets/js/itinerary-view.js';
$content = file_get_contents($file);

// 1. Thêm import
if (!str_contains($content, "import { auth } from './auth.js';")) {
    $content = str_replace(
        "import { icon, categoryIcon } from './icons.js';",
        "import { icon, categoryIcon } from './icons.js';\nimport { auth } from './auth.js';\nimport { getSavedItineraries, saveItinerariesList, deleteSavedItinerary } from './favorites.js';",
        $content
    );
}

// 2. Thay thế STORAGE_KEY và các hàm save/get/delete
$oldBlock = 'const STORAGE_KEY = \'food_finder_saved_itineraries\';';
if (str_contains($content, $oldBlock)) {
    $content = str_replace($oldBlock, '// STORAGE_KEY được quản lý động theo từng User trong favorites.js', $content);
}

// Thay thế function getSavedItineraries() cục bộ thành re-export
$patternGet = '/export function getSavedItineraries\(\)\s*\{[\s\S]*?return \[\];\s*\}\s*\}/';
$content = preg_replace($patternGet, '// Re-export từ favorites.js' . "\n" . 'export { getSavedItineraries, deleteSavedItinerary };', $content);

// Thay thế deleteSavedItinerary cũ nếu còn
$patternDel = '/export function deleteSavedItinerary\(id\)\s*\{[\s\S]*?return false;\s*\}\s*\}/';
$content = preg_replace($patternDel, '', $content);

// Cập nhật saveCurrentItinerary để dùng saveItinerariesList và đồng bộ server
$content = str_replace(
    'localStorage.setItem(STORAGE_KEY, JSON.stringify(saved.slice(0, 50)));',
    "saveItinerariesList(saved.slice(0, 50));\n    if (auth.isLoggedIn()) {\n      fetch('/api/user_itineraries.php', {\n        method: 'POST',\n        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },\n        credentials: 'same-origin',\n        body: JSON.stringify({ itinerary: entry }),\n      }).catch(err => console.warn('Lỗi đồng bộ lộ trình:', err));\n    }",
    $content
);

file_put_contents($file, $content);
echo "Đã cập nhật itinerary-view.js thành công!\n";

