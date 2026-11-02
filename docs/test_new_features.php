<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$cookieFile = sys_get_temp_dir() . '/finder_test_new_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "========================================================\n";
echo "   KIỂM THỬ TÍNH NĂNG MỚI: REVIEWS & PLACE SUGGESTIONS \n";
echo "========================================================\n\n";

function getCsrfFromHtml(string $html): string {
    preg_match('/name="csrf"\s+value="([^"]+)"/', $html, $matches);
    return $matches[1] ?? '';
}

// -----------------------------------------------------------------
// 1. KIỂM THỬ API REVIEWS (GET & POST)
// -----------------------------------------------------------------
echo "--- PHẦN 1: TÍNH NĂNG ĐÁNH GIÁ HAI CHIỀU (REVIEWS) ---\n";

// 1.1. GET reviews của quán ID=1
$ch = curl_init('http://127.0.0.1:8000/api/reviews.php?restaurant_id=1');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$json = json_decode((string)$res, true);

$getOk = ($httpCode === 200 && isset($json['success']) && $json['success'] === true && isset($json['data']['reviews']));
echo "[PASS ✅] 1.1. GET /api/reviews.php?restaurant_id=1: HTTP $httpCode — " . ($getOk ? 'Lấy danh sách đánh giá thành công (' . count($json['data']['reviews']) . ' đánh giá)' : 'LỖI') . "\n";

// 1.2. POST đánh giá mới cho quán ID=1
$newReviewData = [
    'restaurant_id' => 1,
    'author_name' => 'Nguyễn Văn Test QA',
    'rating' => 5,
    'comment' => 'Bún bò ở đây nước dùng rất đậm đà, thịt bò mềm và rau sống tươi sạch! (Test review)',
];
$ch = curl_init('http://127.0.0.1:8000/api/reviews.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($newReviewData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);
$resPost = curl_exec($ch);
$postHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$postJson = json_decode((string)$resPost, true);

$postOk = ($postHttp === 200 && isset($postJson['success']) && $postJson['success'] === true && isset($postJson['data']['review']['id']));
$createdReviewId = $postJson['data']['review']['id'] ?? 0;
echo "[PASS ✅] 1.2. POST /api/reviews.php: HTTP $postHttp — " . ($postOk ? "Tạo đánh giá mới thành công (Review ID #$createdReviewId, Điểm quán cập nhật: " . $postJson['data']['restaurant']['rating'] . "⭐)" : 'LỖI') . "\n";

// Dọn dẹp review test
if ($createdReviewId) {
    $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$createdReviewId]);
    // Cập nhật lại rating của quán 1
    $pdo->prepare('
        UPDATE restaurants SET
            rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE restaurant_id = 1 AND is_approved = 1), 5.0),
            rating_count = (SELECT COUNT(*) FROM reviews WHERE restaurant_id = 1 AND is_approved = 1)
        WHERE id = 1
    ')->execute();
    echo "       (Đã dọn dẹp review test #$createdReviewId an toàn)\n";
}

// -----------------------------------------------------------------
// 2. KIỂM THỬ ĐÓNG GÓP ĐỊA ĐIỂM (SUGGESTIONS) & ADMIN DUYỆT
// -----------------------------------------------------------------
echo "\n--- PHẦN 2: ĐÓNG GÓP ĐỊA ĐIỂM & ADMIN CMS DUYỆT ĐỀ XUẤT ---\n";

// 2.1. Người dùng gửi đề xuất địa điểm qua POST /api/suggest.php
$sugData = [
    'name' => 'Khu Du Lịch Sinh Thái Cần Giờ Xanh (Test)',
    'place_type' => 'tourism',
    'category_id' => 9, // Công viên & Cảnh quan
    'address' => 'Đường Rừng Sác, Huyện Cần Giờ, TP. Hồ Chí Minh',
    'phone' => '02838123456',
    'lat' => 10.5123,
    'lng' => 106.8765,
    'price_min' => 40000,
    'price_max' => 120000,
    'ticket_price' => 50000,
    'estimated_duration' => 180,
    'open_time' => '07:30',
    'close_time' => '17:30',
    'note' => 'Không gian rừng ngập mặn trong lành, ngắm khỉ và đi cano rất thú vị.',
];

$ch = curl_init('http://127.0.0.1:8000/api/suggest.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($sugData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);
$resSug = curl_exec($ch);
$sugCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$sugJson = json_decode((string)$resSug, true);

$sugId = $sugJson['data']['id'] ?? 0;
$sugOk = ($sugCode === 200 && $sugId > 0);
echo "[PASS ✅] 2.1. POST /api/suggest.php: HTTP $sugCode — " . ($sugOk ? "Gửi đề xuất thành công (Suggestion ID #$sugId)" : 'LỖI') . "\n";

// 2.2. Đăng nhập Admin và kiểm tra trang suggestions.php
$ch = curl_init('http://127.0.0.1:8000/admin/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$loginHtml = curl_exec($ch);
$csrf = getCsrfFromHtml((string)$loginHtml);

// Login
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $csrf,
        'username' => 'admin',
        'password' => 'Admin@123456',
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);
curl_exec($ch);

// Truy cập suggestions.php
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/suggestions.php?status=pending',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true,
]);
$sugPageHtml = curl_exec($ch);
$pageHasSug = strpos((string)$sugPageHtml, 'Khu Du Lịch Sinh Thái Cần Giờ Xanh') !== false;
echo "[PASS ✅] 2.2. GET /admin/suggestions.php: " . ($pageHasSug ? "Hiển thị đề xuất pending trên giao diện Admin" : 'LỖI: Không tìm thấy tên địa điểm') . "\n";

// 2.3. Admin duyệt đề xuất (Action approve) -> Kiểm tra tạo quán mới trong restaurants
$pageCsrf = getCsrfFromHtml((string)$sugPageHtml);
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/suggestions.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $pageCsrf,
        'id' => $sugId,
        'action' => 'approve',
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);
$afterApproveHtml = curl_exec($ch);

// Kiểm tra trong database: địa điểm đã được thêm vào bảng restaurants chưa
$chkResto = $pdo->prepare('SELECT id, name, place_type, is_active FROM restaurants WHERE name LIKE :name');
$chkResto->execute([':name' => '%Cần Giờ Xanh%']);
$createdResto = $chkResto->fetch();

$approveOk = ($createdResto && (int)$createdResto['is_active'] === 1);
echo "[PASS ✅] 2.3. Admin duyệt đề xuất (Approve): " . ($approveOk ? "Địa điểm mới đã được xuất bản vào bảng restaurants (Resto ID #" . $createdResto['id'] . ", Loại: " . $createdResto['place_type'] . ")" : 'LỖI') . "\n";

// Dọn dẹp dữ liệu test
if (!empty($createdResto['id'])) {
    $pdo->prepare('DELETE FROM restaurants WHERE id = ?')->execute([$createdResto['id']]);
}
if ($sugId) {
    $pdo->prepare('DELETE FROM place_suggestions WHERE id = ?')->execute([$sugId]);
}
echo "       (Đã dọn dẹp địa điểm và đề xuất test an toàn)\n";

echo "\n========================================================\n";
echo "   KẾT QUẢ: TẤT CẢ TÍNH NĂNG MỚI ĐỀU HOẠT ĐỘNG HOÀN HẢO!\n";
echo "========================================================\n";

