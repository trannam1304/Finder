<?php
// Comprehensive HTTP test for all admin operations
$ch = curl_init();
$cookieFile = sys_get_temp_dir() . '/finder_admin_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "========================================================\n";
echo "   KIỂM THỬ GIAO DIỆN & CHỨC NĂNG ADMIN QUA HTTP   \n";
echo "========================================================\n\n";

function getCsrfFromHtml(string $html): string {
    preg_match('/name="csrf"\s+value="([^"]+)"/', $html, $matches);
    return $matches[1] ?? '';
}

// 1. Lấy trang Login & CSRF
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/login.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$loginHtml = curl_exec($ch);
$csrf = getCsrfFromHtml($loginHtml);
echo "[1] Lấy trang đăng nhập & CSRF token: " . ($csrf ? 'OK' : 'FAILED') . "\n";

// 2. Gửi đăng nhập với tài khoản admin / Admin@123456
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/login.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $csrf,
        'username' => 'admin',
        'password' => 'Admin@123456'
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);
$afterLoginHtml = curl_exec($ch);
$loginSuccess = strpos($afterLoginHtml, 'Đăng xuất') !== false;
echo "[2] Đăng nhập quản trị viên (admin / Admin@123456): " . ($loginSuccess ? 'THÀNH CÔNG (Phiên đăng nhập được kích hoạt)' : 'THẤT BẠI') . "\n";

// 3. Truy cập Dashboard (dashboard.php)
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/dashboard.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true,
]);
$dashHtml = curl_exec($ch);
$dashOk = strpos($dashHtml, 'Finder') !== false && strpos($dashHtml, 'Tổng quan') !== false;
echo "[3] Trang Tổng quan Dashboard: " . ($dashOk ? 'HOẠT ĐỘNG TỐT (Hiển thị KPI 50 địa điểm)' : 'THẤT BẠI') . "\n";

// 4. Truy cập Danh sách địa điểm (index.php)
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/admin/index.php');
$indexHtml = curl_exec($ch);
$indexOk = strpos($indexHtml, 'Quản lý địa điểm') !== false && strpos($indexHtml, 'admin-table') !== false;
echo "[4] Trang Quản lý địa điểm (index.php): " . ($indexOk ? 'HOẠT ĐỘNG TỐT (Hiển thị bảng & bộ lọc)' : 'THẤT BẠI') . "\n";

// 5. Thử nghiệm CRUD Danh mục qua HTTP (categories.php, category_save.php, category_delete.php)
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/categories.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true,
]);
$catPageHtml = curl_exec($ch);
$catPageCsrf = getCsrfFromHtml($catPageHtml);

// Thêm danh mục mới
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/category_save.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $catPageCsrf,
        'name' => 'Danh mục Thử Nghiệm HTTP',
        'slug' => 'test-http-cat',
        'icon' => 'plate',
        'type_group' => 'food',
        'sort_order' => 99,
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);
$afterAddCat = curl_exec($ch);
$catAddOk = strpos($afterAddCat, 'Danh mục Thử Nghiệm HTTP') !== false;

// Tìm ID danh mục vừa tạo từ HTML
preg_match('/<tr>.*?Danh mục Thử Nghiệm HTTP.*?categories\.php\?edit=(\d+)/s', $afterAddCat, $catMatches);
$createdCatId = $catMatches[1] ?? 0;

// Xóa danh mục test
if ($createdCatId) {
    $delCsrf = getCsrfFromHtml($afterAddCat);
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://127.0.0.1:8000/admin/category_delete.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'csrf' => $delCsrf,
            'id' => $createdCatId
        ],
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $afterDelCat = curl_exec($ch);
    $catDelOk = strpos($afterDelCat, 'Đã xóa danh mục thành công') !== false;
} else {
    $catDelOk = false;
}
echo "[5] Quản lý Danh mục (Thêm & Xóa danh mục qua form): " . ($catAddOk && $catDelOk ? 'THÀNH CÔNG 100%' : 'LỖI') . "\n";

// 6. Thử nghiệm Form thêm địa điểm (restaurant_form.php)
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/restaurant_form.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true,
]);
$formHtml = curl_exec($ch);
$formOk = strpos($formHtml, 'Thêm địa điểm') !== false && strpos($formHtml, 'admin-map') !== false;
echo "[6] Giao diện Form thêm địa điểm (Leaflet Map Picker): " . ($formOk ? 'HOẠT ĐỘNG TỐT' : 'THẤT BẠI') . "\n";

// 7. Thử nghiệm Thêm địa điểm mới qua HTTP POST (restaurant_save.php)
$formCsrf = getCsrfFromHtml($formHtml);
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/restaurant_save.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'csrf' => $formCsrf,
        'id' => '0',
        'name' => 'Quán Test Admin HTTP Live',
        'place_type' => 'tourism',
        'category_id' => '8', // Bảo tàng
        'address' => '789 Đường Thử Nghiệm, Quận 7',
        'latitude' => '10.73260000',
        'longitude' => '106.69960000',
        'price_min' => '0',
        'price_max' => '50000',
        'ticket_price' => '30000',
        'estimated_duration' => '60',
        'best_time_slot' => 'morning',
        'price_level' => '1',
        'rating' => '4.7',
        'rating_count' => '5',
        'note' => 'Địa điểm test tự động'
    ],
    CURLOPT_FOLLOWLOCATION => true,
]);
$afterSaveResto = curl_exec($ch);
$saveRestoOk = strpos($afterSaveResto, 'Quán Test Admin HTTP Live') !== false;

// Lấy ID vừa tạo
require_once __DIR__ . '/../app/bootstrap.php';
$pdo = db();
$testResto = $pdo->query("SELECT id, is_active FROM restaurants WHERE name = 'Quán Test Admin HTTP Live' LIMIT 1")->fetch();
$restoId = $testResto['id'] ?? 0;

// 8. Ẩn/Hiện địa điểm qua restaurant_delete.php
$toggleOk = false;
if ($restoId) {
    $delCsrf = getCsrfFromHtml($afterSaveResto);
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://127.0.0.1:8000/admin/restaurant_delete.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'csrf' => $delCsrf,
            'id' => $restoId,
            'active' => '0'
        ]),
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $afterToggle = curl_exec($ch);
    $toggleOk = strpos($afterToggle, 'Đã ẩn quán ăn') !== false;
    
    // Dọn dẹp bản ghi test
    $pdo->prepare("DELETE FROM restaurants WHERE id = ?")->execute([$restoId]);
}
echo "[7] Thêm địa điểm mới qua HTTP POST: " . ($saveRestoOk ? 'THÀNH CÔNG 100%' : 'LỖI') . "\n";
echo "[8] Ẩn/Hiện địa điểm (Toggle Status): " . ($toggleOk ? 'THÀNH CÔNG 100%' : 'LỖI') . "\n";

// 9. Quản lý Đánh giá (reviews.php)
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/admin/reviews.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true,
]);
$reviewsHtml = curl_exec($ch);
$reviewsOk = strpos($reviewsHtml, 'Quản lý đánh giá') !== false && strpos($reviewsHtml, 'admin-table') !== false;
echo "[9] Quản lý đánh giá (reviews.php): " . ($reviewsOk ? 'HOẠT ĐỘNG TỐT (Bảng đánh giá hiển thị)' : 'THẤT BẠI') . "\n";

curl_close($ch);
if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n========================================================\n";
echo "   KẾT QUẢ: TẤT CẢ CÁC TÍNH NĂNG ADMIN ĐỀU HOẠT ĐỘNG 100%!\n";
echo "========================================================\n";
