<?php
declare(strict_types=1);

$file = 'public/assets/js/main.js';
$content = file_get_contents($file);

if (!str_contains($content, "import { getSavedItineraries } from './favorites.js';")) {
    $content = str_replace(
        "import { setExitItineraryHandler } from './itinerary-view.js';",
        "import { setExitItineraryHandler } from './itinerary-view.js';\nimport { getSavedItineraries } from './favorites.js';",
        $content
    );
}

$content = str_replace(
    "const savedList = JSON.parse(localStorage.getItem('food_finder_saved_itineraries') || '[]');",
    "const savedList = getSavedItineraries();",
    $content
);

file_put_contents($file, $content);
echo "Đã cập nhật main.js thành công!\n";

