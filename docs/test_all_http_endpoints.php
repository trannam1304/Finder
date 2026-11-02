<?php
declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

function http_request(string $method, string $path, array $data = [], ?string $cookie = null): array {
    global $baseUrl;
    $url = $baseUrl . $path;
    $ch = curl_init();
    
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    
    if ($cookie) {
        $opts[CURLOPT_COOKIE] = $cookie;
    }
    
    if ($method === 'POST') {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($data);
    }
    
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'json' => json_decode($body, true),
    ];
}

echo "========================================================\n";
echo "   KIỂM THỬ TOÀN BỘ ENDPOINT HTTP CỦA FOOD FINDER      \n";
echo "========================================================\n\n";

$tests = [
    // 1. Frontend Pages
    ['Trang chủ (index.html)', 'GET', '/index.html', 200, false],
    ['Trang khám phá (explore.html)', 'GET', '/explore.html', 200, false],
    ['Trang gợi ý & lộ trình (suggest.html)', 'GET', '/suggest.html', 200, false],
    ['Trang yêu thích (favorites.html)', 'GET', '/favorites.html', 200, false],
    ['Trang chi tiết (restaurant.html)', 'GET', '/restaurant.html?id=1', 200, false],
    ['Trang giới thiệu (about.html)', 'GET', '/about.html', 200, false],
    ['Trang login (login.html)', 'GET', '/login.html', 200, false],
    ['Trang admin (admin.html)', 'GET', '/admin.html', 200, false],
    
    // 2. Public REST APIs
    ['API Danh mục (/api/categories.php)', 'GET', '/api/categories.php', 200, true],
    ['API Quán ăn 3km (/api/restaurants.php)', 'GET', '/api/restaurants.php?lat=10.7326&lng=106.6996&radius=3', 200, true],
    ['API Quán ăn loại Food (/api/restaurants.php)', 'GET', '/api/restaurants.php?lat=10.7326&lng=106.6996&radius=5&type=food', 200, true],
    ['API Điểm Du lịch (/api/restaurants.php)', 'GET', '/api/restaurants.php?lat=10.7326&lng=106.6996&radius=10&type=tourism', 200, true],
    ['API Điểm Vui chơi (/api/restaurants.php)', 'GET', '/api/restaurants.php?lat=10.7326&lng=106.6996&radius=10&type=entertainment', 200, true],
    ['API Quán ăn bán kính 25km (favorites.js dùng)', 'GET', '/api/restaurants.php?lat=10.7326&lng=106.6996&radius=25', 200, true],
    ['API Chi tiết quán ID=1 (/api/restaurant.php)', 'GET', '/api/restaurant.php?id=1', 200, true],
    ['API Chi tiết quán ID không tồn tại (Expect 404)', 'GET', '/api/restaurant.php?id=99999', 404, true],
    ['API Gợi ý ngẫu nhiên (/api/random.php)', 'GET', '/api/random.php?lat=10.7326&lng=106.6996&radius=5', 200, true],
    ['API Lộ trình Nửa ngày Chill (/api/itinerary.php)', 'GET', '/api/itinerary.php?lat=10.7326&lng=106.6996&duration=half_day&vibe=chill&radius=8', 200, true],
    ['API Lộ trình Cả ngày Vận động (/api/itinerary.php)', 'GET', '/api/itinerary.php?lat=10.7326&lng=106.6996&duration=full_day&vibe=adventure&radius=10', 200, true],
    ['API Lộ trình Tối Hẹn hò (/api/itinerary.php)', 'GET', '/api/itinerary.php?lat=10.7326&lng=106.6996&duration=evening&vibe=date&radius=8', 200, true],
    ['API Đổi chặng hợp lệ (/api/itinerary_reroll.php)', 'GET', '/api/itinerary_reroll.php?current_id=1&from_lat=10.7326&from_lng=106.6996&place_type=food&vibe=chill', 200, true],
    ['API Đổi chặng không ứng viên (Expect 404)', 'GET', '/api/itinerary_reroll.php?current_id=99999&from_lat=0&from_lng=0&place_type=food&vibe=chill&radius=1.0', 404, true],
    ['API Lấy đánh giá của quán ID=1 (/api/reviews.php)', 'GET', '/api/reviews.php?restaurant_id=1', 200, true],
    ['API Trang duyệt đề xuất Admin (Bảo vệ session)', 'GET', '/admin/suggestions.php', 302, false],
];

$passCount = 0;
$failCount = 0;

foreach ($tests as $t) {
    [$name, $method, $path, $expectedCode, $expectJson] = $t;
    $res = http_request($method, $path);
    
    $codeOk = ($res['code'] === $expectedCode);
    $jsonOk = !$expectJson || ($res['json'] !== null);
    
    if ($codeOk && $jsonOk) {
        $extra = "";
        if ($expectJson && isset($res['json']['data'])) {
            $count = is_array($res['json']['data']) ? count($res['json']['data']) : 'object';
            $extra = " [Data: $count items]";
        }
        echo "[PASS ✅] $name (HTTP {$res['code']})$extra\n";
        $passCount++;
    } else {
        echo "[FAIL ❌] $name (Expected HTTP $expectedCode, got {$res['code']})\n";
        if (!$jsonOk) {
            echo "   -> JSON parse error or empty body: " . substr(strip_tags($res['body']), 0, 150) . "\n";
        } elseif (isset($res['json']['error'])) {
            echo "   -> Error response: " . json_encode($res['json']['error'], JSON_UNESCAPED_UNICODE) . "\n";
        }
        $failCount++;
    }
}

echo "\n--------------------------------------------------------\n";
echo "KẾT QUẢ KIỂM THỬ: $passCount PASS / $failCount FAIL\n";
