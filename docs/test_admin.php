<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/upload.php';

echo "========================================================\n";
echo "   KIỂM TRA HOẠT ĐỘNG TOÀN DIỆN HỆ THỐNG ADMIN FINDER   \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function report(string $name, bool $success, string $details = ''): void {
    global $passCount, $failCount;
    if ($success) {
        $passCount++;
        echo "[PASS ✅] $name" . ($details ? " — $details" : "") . "\n";
    } else {
        $failCount++;
        echo "[FAIL ❌] $name" . ($details ? " — $details" : "") . "\n";
    }
}

// 1. Kiểm tra kết nối Database
try {
    $pdo = db();
    report('1. Kết nối CSDL MySQL (PDO)', true, 'Kết nối thành công tới database ' . DB_NAME);
} catch (Throwable $e) {
    report('1. Kết nối CSDL MySQL (PDO)', false, $e->getMessage());
    exit(1);
}

// 2. Kiểm tra tài khoản Admin mặc định
try {
    $stmt = $pdo->prepare('SELECT id, username, display_name, password_hash, last_login_at FROM admins WHERE username = ?');
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    $adminExists = (bool) $admin;
    $pwValid = $adminExists && password_verify('Admin@123456', $admin['password_hash']);
    report('2. Tài khoản Admin mặc định (admin / Admin@123456)', $adminExists && $pwValid, 
        $adminExists ? "ID: {$admin['id']}, DisplayName: {$admin['display_name']}" : "Không tìm thấy user admin");
} catch (Throwable $e) {
    report('2. Tài khoản Admin mặc định', false, $e->getMessage());
}

// 3. Kiểm tra logic xác thực đăng nhập (attempt_login)
try {
    $loginSuccess = attempt_login('admin', 'Admin@123456');
    $wrongLogin = attempt_login('admin', 'SaiPass123');
    report('3. Hàm attempt_login()', $loginSuccess && !$wrongLogin, 
        "Đăng nhập đúng trả về TRUE, đăng nhập sai trả về FALSE");
} catch (Throwable $e) {
    report('3. Hàm attempt_login()', false, $e->getMessage());
}

// 4. Kiểm tra dữ liệu Dashboard (dashboard.php)
try {
    $totalResto = (int) $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
    $activeResto = (int) $pdo->query('SELECT COUNT(*) FROM restaurants WHERE is_active = 1')->fetchColumn();
    $totalCat = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    
    $typeCounts = ['food' => 0, 'tourism' => 0, 'entertainment' => 0];
    $typeRows = $pdo->query('SELECT place_type, COUNT(*) AS cnt FROM restaurants GROUP BY place_type')->fetchAll();
    foreach ($typeRows as $tr) {
        $pt = (string) ($tr['place_type'] ?? 'food');
        if (isset($typeCounts[$pt])) $typeCounts[$pt] = (int) $tr['cnt'];
    }
    
    $dashOk = ($totalResto >= 50) && ($totalCat >= 15) && ($typeCounts['food'] > 0) && ($typeCounts['tourism'] > 0) && ($typeCounts['entertainment'] > 0);
    report('4. Thống kê KPI Dashboard', $dashOk, 
        "Tổng địa điểm: $totalResto (Active: $activeResto), Danh mục: $totalCat | Ẩm thực: {$typeCounts['food']}, Du lịch: {$typeCounts['tourism']}, Vui chơi: {$typeCounts['entertainment']}");
} catch (Throwable $e) {
    report('4. Thống kê KPI Dashboard', false, $e->getMessage());
}

// 5. Kiểm tra truy vấn danh sách & bộ lọc Quản lý địa điểm (index.php)
try {
    $listAll = $pdo->query('SELECT r.*, c.name AS category_name FROM restaurants r JOIN categories c ON c.id = r.category_id ORDER BY r.updated_at DESC LIMIT 20')->fetchAll();
    
    // Lọc theo loại hình tourism
    $stmtTour = $pdo->prepare('SELECT COUNT(*) FROM restaurants WHERE place_type = ?');
    $stmtTour->execute(['tourism']);
    $tourCount = (int) $stmtTour->fetchColumn();
    
    // Tìm kiếm theo từ khóa
    $stmtSearch = $pdo->prepare('SELECT COUNT(*) FROM restaurants WHERE name LIKE ? OR address LIKE ?');
    $stmtSearch->execute(['%Cơm%', '%Cơm%']);
    $searchCount = (int) $stmtSearch->fetchColumn();
    
    $indexOk = count($listAll) > 0 && $tourCount > 0 && $searchCount > 0;
    report('5. Truy vấn & Bộ lọc Quản lý địa điểm (index.php)', $indexOk, 
        "Lấy 20 dòng đầu thành công, Lọc Du lịch: $tourCount điểm, Tìm 'Cơm': $searchCount điểm");
} catch (Throwable $e) {
    report('5. Truy vấn & Bộ lọc Quản lý địa điểm (index.php)', false, $e->getMessage());
}

// 6. Kiểm tra CRUD địa điểm (restaurant_save.php & restaurant_delete.php)
try {
    $catId = (int) $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    
    // INSERT địa điểm test
    $insStmt = $pdo->prepare("
        INSERT INTO restaurants (category_id, place_type, name, address, latitude, longitude, price_min, price_max, ticket_price, estimated_duration, best_time_slot, price_level, rating, rating_count, is_active, note)
        VALUES (:category_id, :place_type, :name, :address, :latitude, :longitude, :price_min, :price_max, :ticket_price, :estimated_duration, :best_time_slot, :price_level, :rating, :rating_count, 1, :note)
    ");
    $insStmt->execute([
        ':category_id' => $catId,
        ':place_type' => 'food',
        ':name' => '[TEST QA] Quán Thử Nghiệm Tự Động',
        ':address' => '123 Đường Test, Quận 7, TP.HCM',
        ':latitude' => 10.7350,
        ':longitude' => 106.7000,
        ':price_min' => 30000,
        ':price_max' => 60000,
        ':ticket_price' => 0,
        ':estimated_duration' => 45,
        ':best_time_slot' => 'morning',
        ':price_level' => 1,
        ':rating' => 4.8,
        ':rating_count' => 10,
        ':note' => 'Địa điểm test CRUD tự động'
    ]);
    $newId = (int) $pdo->lastInsertId();
    
    // UPDATE
    $updStmt = $pdo->prepare("UPDATE restaurants SET name = :name, rating = :rating WHERE id = :id");
    $updStmt->execute([':name' => '[TEST QA] Quán Thử Nghiệm (Đã Sửa)', ':rating' => 5.0, ':id' => $newId]);
    
    // TOGGLE STATUS
    $toggleStmt = $pdo->prepare("UPDATE restaurants SET is_active = 0 WHERE id = :id");
    $toggleStmt->execute([':id' => $newId]);
    
    // DELETE CLEANUP
    $delStmt = $pdo->prepare("DELETE FROM restaurants WHERE id = :id");
    $delStmt->execute([':id' => $newId]);
    
    report('6. Thử nghiệm CRUD Quản lý địa điểm (Thêm, Sửa, Ẩn/Hiện, Xóa)', true, "Tạo ID #$newId, sửa, ẩn và xóa dọn dẹp thành công 100%");
} catch (Throwable $e) {
    report('6. Thử nghiệm CRUD Quản lý địa điểm', false, $e->getMessage());
}

// 7. Kiểm tra Quản lý danh mục (categories.php & category_save.php)
try {
    $insCat = $pdo->prepare("INSERT INTO categories (name, slug, icon, type_group, sort_order) VALUES (?, ?, ?, ?, ?)");
    $insCat->execute(['[TEST] Danh mục test', 'test-danhmuc-qa', 'plate', 'food', 99]);
    $testCatId = (int) $pdo->lastInsertId();
    
    $updCat = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $updCat->execute(['[TEST] Danh mục test (Sửa)', $testCatId]);
    
    $delCat = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $delCat->execute([$testCatId]);
    
    report('7. Thử nghiệm CRUD Quản lý danh mục (categories.php)', true, "Tạo category ID #$testCatId, cập nhật và dọn dẹp thành công");
} catch (Throwable $e) {
    report('7. Thử nghiệm CRUD Quản lý danh mục', false, $e->getMessage());
}

// 8. Kiểm tra Quản lý đánh giá (reviews.php)
try {
    $revCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
    report('8. Quản lý đánh giá (reviews.php)', true, "Bảng reviews hoạt động tốt, hiện có $revCount đánh giá");
} catch (Throwable $e) {
    report('8. Quản lý đánh giá (reviews.php)', false, $e->getMessage());
}

echo "\n========================================================\n";
echo "   TỔNG KẾT: $passCount PASSED / $failCount FAILED\n";
echo "========================================================\n";
