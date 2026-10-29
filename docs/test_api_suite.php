<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/restaurant_query.php';
require_once __DIR__ . '/../app/services/itinerary.php';

$pdo = db();
$results = [];

function assert_test(string $name, bool $cond, string $details = ''): void {
    global $results;
    $results[] = [
        'name' => $name,
        'pass' => $cond,
        'details' => $details
    ];
    echo ($cond ? "[PASS ✅] " : "[FAIL ❌] ") . $name . ($details ? " ($details)" : "") . PHP_EOL;
}

echo "=== 1. KIỂM THỬ RESTAURANT_FILTERS ===\n";
try {
    $f = restaurant_filters(['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '3']);
    assert_test("restaurant_filters với radius 3", $f['radius'] === 3.0);
} catch (Exception $e) {
    assert_test("restaurant_filters với radius 3", false, $e->getMessage());
}

try {
    $f = restaurant_filters(['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '25']);
    assert_test("restaurant_filters với radius 25", true);
} catch (Exception $e) {
    assert_test("restaurant_filters với radius 25", false, $e->getMessage());
}

try {
    $f = restaurant_filters(['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '8']);
    assert_test("restaurant_filters với radius 8", true);
} catch (Exception $e) {
    assert_test("restaurant_filters với radius 8 (không nằm trong mảng cứng)", false, $e->getMessage());
}

echo "\n=== 2. KIỂM THỬ TRUY VẤN RESTAURANTS QUERY ===\n";
try {
    $filters = [
        'lat' => 10.7326, 'lng' => 106.6996, 'radius' => 5.0,
        'category_id' => null, 'type' => 'all', 'price_level' => null,
        'min_rating' => 0.0, 'open_now' => false, 'q' => '',
        'sort' => 'distance', 'limit' => 50, 'offset' => 0
    ];
    $rows = restaurant_rows($pdo, $filters);
    assert_test("Lấy danh sách địa điểm bán kính 5km", count($rows) > 0, "Tìm thấy " . count($rows) . " điểm");
} catch (Exception $e) {
    assert_test("Lấy danh sách địa điểm bán kính 5km", false, $e->getMessage());
}

// Thử filter type=tourism
try {
    $filters['type'] = 'tourism';
    $filters['radius'] = 15.0;
    $rows = restaurant_rows($pdo, $filters);
    assert_test("Lấy danh sách điểm Du lịch bán kính 15km", count($rows) > 0, "Tìm thấy " . count($rows) . " điểm");
} catch (Exception $e) {
    assert_test("Lấy danh sách điểm Du lịch", false, $e->getMessage());
}

// Thử filter type=entertainment
try {
    $filters['type'] = 'entertainment';
    $filters['radius'] = 15.0;
    $rows = restaurant_rows($pdo, $filters);
    assert_test("Lấy danh sách điểm Vui chơi bán kính 15km", count($rows) > 0, "Tìm thấy " . count($rows) . " điểm");
} catch (Exception $e) {
    assert_test("Lấy danh sách điểm Vui chơi", false, $e->getMessage());
}

echo "\n=== 3. KIỂM THỬ TẠO LỘ TRÌNH ITINERARY ===\n";
try {
    $itin = generate_itinerary($pdo, 10.7326, 106.6996, 'half_day', 'chill', 8.0);
    assert_test("Tạo lộ trình half_day chill", count($itin['stops']) === 3, "Stops: " . count($itin['stops']));
    assert_test("Google Maps URL hợp lệ", str_starts_with($itin['summary']['google_maps_url'], 'https://www.google.com/maps/dir/'));
} catch (Exception $e) {
    assert_test("Tạo lộ trình half_day chill", false, $e->getMessage());
}

try {
    $itin = generate_itinerary($pdo, 10.7326, 106.6996, 'full_day', 'adventure', 10.0);
    assert_test("Tạo lộ trình full_day adventure", count($itin['stops']) === 5, "Stops: " . count($itin['stops']));
} catch (Exception $e) {
    assert_test("Tạo lộ trình full_day adventure", false, $e->getMessage());
}

echo "\n=== 4. KIỂM THỬ ĐỔI CHẶNG REROLL ===\n";
try {
    $cand = reroll_itinerary_stop($pdo, 1, [1, 2, 3], 10.7326, 106.6996, 'tourism', 'chill', 10.0);
    assert_test("Reroll chặng tourism", $cand !== null && !in_array($cand['id'], [1, 2, 3], true), "Replaced with ID: " . ($cand['id'] ?? 'none'));
} catch (Exception $e) {
    assert_test("Reroll chặng tourism", false, $e->getMessage());
}

// Thử reroll khi không tìm thấy candidate (bán kính siêu nhỏ 0.001km giữa biển hoặc exclude hết)
try {
    $allIds = array_column($pdo->query("SELECT id FROM restaurants")->fetchAll(), 'id');
    $candNone = reroll_itinerary_stop($pdo, 99999, $allIds, 0.0, 0.0, 'tourism', 'chill', 0.001);
    assert_test("Reroll khi không có ứng viên trả về null", $candNone === null);
} catch (Exception $e) {
    assert_test("Reroll không có ứng viên", false, $e->getMessage());
}

