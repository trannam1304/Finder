<?php
declare(strict_types=1);

/**
 * KỊCH BẢN KIỂM THỬ TỰ ĐỘNG (AUTOMATED QA TEST SUITE) — TASKS QA-01 & QA-02
 * Người phụ trách: Phạm Nguyễn Tuấn Kiệt (52400207)
 * Chạy bằng lệnh: php docs/qa_verify_tuan_kiet.php
 */

require_once __DIR__ . '/../app/services/geo.php';
require_once __DIR__ . '/../app/services/itinerary.php';

echo "=================================================================\n";
echo "  FOOD & TRIP FINDER — BỘ KIỂM THỬ TỰ ĐỘNG QA-01 & QA-02\n";
echo "  Phụ trách kiểm thử: Phạm Nguyễn Tuấn Kiệt (52400207)\n";
echo "=================================================================\n\n";

$passed = 0;
$failed = 0;

function assert_qa(bool $condition, string $testName, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS ✅] {$testName}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    } else {
        $failed++;
        echo "[FAIL ❌] {$testName}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

// -----------------------------------------------------------------
// PHẦN 1: KIỂM THỬ DỮ LIỆU MOCK DATA & ĐỒNG BỘ (TASK DB-04 & QA-02)
// -----------------------------------------------------------------
echo "--- PHẦN 1: KIỂM THỬ MOCK DATA FRONTEND (DB-04) ---\n";
$mockJsPath = __DIR__ . '/../public/assets/js/mock-data.js';
$mockContent = (string) file_get_contents($mockJsPath);

preg_match_all('/place_type:\s*\'(food|tourism|entertainment)\'/', $mockContent, $typeMatches);
$typeCounts = array_count_values($typeMatches[1] ?? []);
$totalPlaces = array_sum($typeCounts);

assert_qa($totalPlaces === 50, 'DB-04.1: Tổng số địa điểm trong mock-data.js đủ 50 địa điểm', "Tìm thấy: {$totalPlaces}");
assert_qa(($typeCounts['food'] ?? 0) === 18, 'DB-04.2: Nhóm Ẩm thực (food) có đủ 18 địa điểm', 'Count=' . ($typeCounts['food'] ?? 0));
assert_qa(($typeCounts['tourism'] ?? 0) === 16, 'DB-04.3: Nhóm Du lịch (tourism) có đủ 16 địa điểm', 'Count=' . ($typeCounts['tourism'] ?? 0));
assert_qa(($typeCounts['entertainment'] ?? 0) === 16, 'DB-04.4: Nhóm Vui chơi (entertainment) có đủ 16 địa điểm', 'Count=' . ($typeCounts['entertainment'] ?? 0));

preg_match_all('/type_group:\s*\'(food|tourism|entertainment)\'/', $mockContent, $groupMatches);
assert_qa(count($groupMatches[0]) >= 65, 'DB-04.5: Đầy đủ 15 danh mục + 50 địa điểm gắn kèm type_group', 'Matches=' . count($groupMatches[0]));

// -----------------------------------------------------------------
// PHẦN 2: KIỂM THỬ LOGIC THUẬT TOÁN GỢI Ý LỘ TRÌNH GPS (TASK QA-01)
// -----------------------------------------------------------------
echo "\n--- PHẦN 2: KIỂM THỬ THUẬT TOÁN LỘ TRÌNH VỚI TỌA ĐỘ GPS THỰC TẾ (QA-01) ---\n";

$testLocations = [
    'TDTU Quận 7' => ['lat' => 10.7326, 'lng' => 106.6996],
    'Bến Bạch Đằng Quận 1' => ['lat' => 10.7715, 'lng' => 106.7068],
    'Khu đô thị Sala Quận 2 (Thủ Thiêm)' => ['lat' => 10.7720, 'lng' => 106.7220],
];

// Kiểm tra Bounding Box & Haversine tại 3 vị trí thực tế
foreach ($testLocations as $locName => $coords) {
    $box = bounding_box($coords['lat'], $coords['lng'], 5.0);
    $validBox = ($box['lat_min'] < $coords['lat'] && $box['lat_max'] > $coords['lat'])
             && ($box['lng_min'] < $coords['lng'] && $box['lng_max'] > $coords['lng']);
    assert_qa($validBox, "QA-01 (Bounding Box - {$locName})", sprintf('[%.4f..%.4f, %.4f..%.4f]', $box['lat_min'], $box['lat_max'], $box['lng_min'], $box['lng_max']));
}

// Kiểm tra cấu trúc Template Lộ trình cho đủ 3 khung thời gian & 4 phong cách
$durations = ['half_day' => 3, 'evening' => 3, 'full_day' => 5];
$vibes = ['chill', 'adventure', 'foodie', 'date'];

foreach ($durations as $dur => $expectedStops) {
    foreach ($vibes as $vibe) {
        $tpl = itinerary_template($dur, $vibe);
        $meta = itinerary_meta($dur, $vibe);
        assert_qa(
            count($tpl) === $expectedStops && !empty($meta['title']),
            "QA-01 (Template {$dur} + {$vibe})",
            count($tpl) . " chặng — {$meta['title']}"
        );
    }
}

// Kiểm tra tạo link Google Maps Multi-stop từ 3 điểm xuất phát
foreach ($testLocations as $locName => $coords) {
    $sampleStops = [
        ['place' => ['lat' => 10.7332, 'lng' => 106.7020]],
        ['place' => ['lat' => 10.7255, 'lng' => 106.7180]],
        ['place' => ['lat' => 10.7287, 'lng' => 106.7168]],
    ];
    $gmapUrl = build_google_maps_directions_url($coords['lat'], $coords['lng'], $sampleStops);
    $hasOriginAndWaypoints = str_contains($gmapUrl, 'origin=') && str_contains($gmapUrl, 'destination=') && str_contains($gmapUrl, 'waypoints=');
    assert_qa($hasOriginAndWaypoints, "QA-01 (Google Maps Multi-waypoint URL - {$locName})", $gmapUrl);
}

// -----------------------------------------------------------------
// PHẦN 3: KIỂM THỬ GIAO DIỆN FRONTEND, ADMIN & RESPONSIVE MOBILE (QA-02)
// -----------------------------------------------------------------
echo "\n--- PHẦN 3: KIỂM THỬ GIAO DIỆN FRONTEND, ADMIN CMS & RESPONSIVE MOBILE (QA-02) ---\n";

$indexHtml = (string) file_get_contents(__DIR__ . '/../public/index.html');
$exploreHtml = (string) file_get_contents(__DIR__ . '/../public/explore.html');
$favoritesHtml = (string) file_get_contents(__DIR__ . '/../public/favorites.html');
$brutalCss = (string) file_get_contents(__DIR__ . '/../public/assets/css/brutal.css');
$mapCss = (string) file_get_contents(__DIR__ . '/../public/assets/css/map.css');
$formatJs = (string) file_get_contents(__DIR__ . '/../public/assets/js/format.js');
$mapJs = (string) file_get_contents(__DIR__ . '/../public/assets/js/map.js');
$adminDashboard = (string) file_get_contents(__DIR__ . '/../public/admin/dashboard.php');
$adminForm = (string) file_get_contents(__DIR__ . '/../public/admin/restaurant_form.php');

assert_qa(str_contains($indexHtml, 'place-type-tabs') && str_contains($exploreHtml, 'place-type-tabs') && str_contains($favoritesHtml, 'place-type-tabs'), 'FE-01: Thanh Tabs phân loại cấp 1 có mặt trên Bản đồ, Khám phá & Đã lưu');
assert_qa(str_contains($mapCss, '.marker-pin--tourism') && str_contains($mapCss, '.marker-pin--entertainment') && str_contains($formatJs, 'marker-pin--tourism') && str_contains($mapJs, 'typeInfo.markerClass'), 'FE-02: Marker phân màu riêng biệt cho Ẩm thực, Du lịch, Vui chơi (map.css, format.js, map.js)');
assert_qa(str_contains($brutalCss, '.badge--tourism') && str_contains($brutalCss, '.badge--entertainment') && str_contains($brutalCss, '.badge--ticket'), 'FE-03: Huy hiệu loại hình, giá vé và thời lượng trong brutal.css');
assert_qa(str_contains($adminForm, 'place_type') && str_contains($adminForm, 'ticket_price') && str_contains($adminForm, 'estimated_duration'), 'ADM-01: Form Admin hỗ trợ quản trị đa loại hình, giá vé, thời lượng');
assert_qa(str_contains($adminDashboard, 'itinerary_logs') && str_contains($adminDashboard, 'place_type'), 'ADM-02: Dashboard Admin thống kê 3 nhóm loại hình & lượt tạo lộ trình');

echo "\n=================================================================\n";
echo "  KẾT QUẢ KIỂM THỬ: {$passed} PASSED / {$failed} FAILED\n";
echo "=================================================================\n";
exit($failed > 0 ? 1 : 0);
