<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Vui lòng chạy script này từ Command Line / Terminal.\n");
}

$config = dirname(__DIR__) . '/app/config.php';
if (!is_file($config)) {
    $exampleConfig = dirname(__DIR__) . '/app/config.example.php';
    if (is_file($exampleConfig)) {
        require_once $exampleConfig;
    } else {
        exit("Lỗi: Không tìm thấy file cấu hình app/config.php hoặc app/config.example.php\n");
    }
} else {
    require_once $config;
}

echo "========================================================\n";
echo "   KHOI TAO / TAI TAO DATABASE FOOD FINDER\n";
echo "========================================================\n\n";

try {
    // 1. Kết nối MySQL Server (chưa trỏ vào database cụ thể)
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "[1/4] Dang kiem tra va tao database '" . DB_NAME . "'...\n";
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
        DB_NAME
    ));
    $pdo->exec(sprintf('USE `%s`;', DB_NAME));

    // 2. Chạy schema.sql
    echo "[2/4] Dang import schema.sql (Cau truc cac bang)...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (is_file($schemaFile)) {
        $pdo->exec((string) file_get_contents($schemaFile));
    }

    // 3. Chạy seed_categories.sql
    echo "[3/4] Dang import seed_categories.sql (Nap danh muc mon an)...\n";
    $catFile = __DIR__ . '/seed_categories.sql';
    if (is_file($catFile)) {
        $pdo->exec((string) file_get_contents($catFile));
    }

    // 4. Chạy seed_restaurants.sql
    echo "[4/4] Dang import seed_restaurants.sql (Nap danh sach quan an mau)...\n";
    $restFile = __DIR__ . '/seed_restaurants.sql';
    if (is_file($restFile)) {
        $pdo->exec((string) file_get_contents($restFile));
    }

    // 5. Tự động tạo tài khoản Admin mặc định
    echo "[5/5] Dang tao tai khoan Admin mac dinh...\n";
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, display_name) VALUES (:username, :hash, :display_name)');
    $stmt->execute([
        ':username' => 'admin',
        ':hash' => password_hash('Admin@123456', PASSWORD_DEFAULT),
        ':display_name' => 'Quan tri vien'
    ]);

    echo "\n========================================================\n";
    echo "   TAO DATABASE VA NAP DU LIEU HOAN TAT 100%!\n";
    echo "========================================================\n";
    echo "Thong tin quan tri mac dinh:\n";
    echo " - Tai khoan: admin\n";
    echo " - Mat khau : Admin@123456\n\n";
    echo "Ban co the truy cap web tai: " . (defined('APP_URL') ? APP_URL : 'http://localhost:8000') . "\n";
    echo "Trang quan tri: " . (defined('APP_URL') ? APP_URL : 'http://localhost:8000') . "/admin.html\n\n";

} catch (PDOException $e) {
    echo "\n[LOI]: " . $e->getMessage() . "\n";
    echo "Luu y: Hay dam bao MySQL trong XAMPP Control Panel dang [Start] (Mau xanh).\n";
    exit(1);
}
