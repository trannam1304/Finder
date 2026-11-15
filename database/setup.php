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
echo "   (Tuong thich MySQL Localhost & TiDB Cloud Serverless)\n";
echo "========================================================\n\n";

try {
    // 1. Kết nối Database Server (Hỗ trợ SSL cho Cloud Database như TiDB)
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    if (defined('DB_SSL') && DB_SSL) {
        if (defined('DB_SSL_CA') && !empty(DB_SSL_CA) && is_file(DB_SSL_CA)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        } else {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '';
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = defined('DB_SSL_VERIFY') ? DB_SSL_VERIFY : false;
            }
        }
    }

    echo "[1/6] Dang ket noi toi database server (" . DB_HOST . ":" . DB_PORT . ")...\n";
    
    // Thử kết nối trực tiếp vào DB_NAME, nếu chưa có thì kết nối vào database mặc định để tạo
    $pdo = null;
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (Throwable $e) {
        // Nếu DB_NAME chưa tồn tại, thử kết nối qua test hoặc sys hoặc không chỉ định dbname
        $fallbackDbs = ['test', 'sys', ''];
        $connected = false;
        foreach ($fallbackDbs as $fallbackDb) {
            try {
                $dsnFallback = empty($fallbackDb)
                    ? sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT)
                    : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, $fallbackDb);
                $pdo = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
                $connected = true;
                break;
            } catch (Throwable $e2) {
                continue;
            }
        }
        if (!$connected || !$pdo) {
            throw $e;
        }
    }

    echo "      Kiem tra va khoi tao database '" . DB_NAME . "'...\n";
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
        DB_NAME
    ));
    $pdo->exec(sprintf('USE `%s`;', DB_NAME));

    // 2. Chạy schema.sql
    echo "[2/6] Dang import schema.sql (Cau truc cac bang - DB-01, DB-02)...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (is_file($schemaFile)) {
        $schemaSql = (string) file_get_contents($schemaFile);
        $pdo->exec($schemaSql);
    }

    // 3. Chạy seed_categories.sql
    echo "[3/6] Dang import seed_categories.sql (15 danh muc theo 3 nhom lớn)...\n";
    $catFile = __DIR__ . '/seed_categories.sql';
    if (is_file($catFile)) {
        $pdo->exec((string) file_get_contents($catFile));
    }

    // 4. Chạy seed_restaurants.sql
    echo "[4/6] Dang import seed_restaurants.sql (18 quan an mau quanh Q7)...\n";
    $restFile = __DIR__ . '/seed_restaurants.sql';
    if (is_file($restFile)) {
        $pdo->exec((string) file_get_contents($restFile));
    }

    // 5. Chạy seed_places_expansion.sql
    echo "[5/6] Dang import seed_places_expansion.sql (32+ diem Du lich & Vui choi - DB-03)...\n";
    $expansionFile = __DIR__ . '/seed_places_expansion.sql';
    if (is_file($expansionFile)) {
        $pdo->exec((string) file_get_contents($expansionFile));
    }

    // 6. Tự động tạo tài khoản Admin mặc định
    echo "[6/6] Dang tao tai khoan Admin mac dinh...\n";
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, display_name) VALUES (:username, :hash, :display_name) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), display_name = VALUES(display_name)');
    $stmt->execute([
        ':username' => 'admin',
        ':hash' => password_hash('Admin@123456', PASSWORD_DEFAULT),
        ':display_name' => 'Quan tri vien'
    ]);

    // Thống kê nhanh
    $totalPlaces = $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
    $totalCats = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $foodCount = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE place_type = 'food'")->fetchColumn();
    $tourismCount = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE place_type = 'tourism'")->fetchColumn();
    $entCount = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE place_type = 'entertainment'")->fetchColumn();

    echo "\n========================================================\n";
    echo "   TAO DATABASE VA NAP DU LIEU HOAN TAT 100%!\n";
    echo "========================================================\n";
    echo "Thong ke du lieu:\n";
    echo " - Tong danh muc : {$totalCats} (Am thuc, Du lich, Vui choi)\n";
    echo " - Tong dia diem : {$totalPlaces} dia diem thuc te quanh Q7 & TP.HCM\n";
    echo "   + Quan an / Uong (Food)      : {$foodCount}\n";
    echo "   + Du lich & Tham quan (Tour) : {$tourismCount}\n";
    echo "   + Vui choi & Giai tri (Ent)  : {$entCount}\n\n";
    echo "Thong tin dang nhap Admin:\n";
    echo " - Tai khoan: admin\n";
    echo " - Mat khau : Admin@123456\n\n";
    echo "Truy cap web tai    : " . (defined('APP_URL') ? APP_URL : 'http://localhost:8000') . "\n";
    echo "Trang quan tri Admin: " . (defined('APP_URL') ? APP_URL : 'http://localhost:8000') . "/admin.html\n\n";

} catch (PDOException $e) {
    echo "\n[LOI]: " . $e->getMessage() . "\n";
    echo "Luu y: Kiem tra lai thong tin ket noi DB_HOST, DB_USER, DB_PASS hoac trang thai MySQL/TiDB.\n";
    exit(1);
}
