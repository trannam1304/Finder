<?php
declare(strict_types=1);

/**
 * Script hỗ trợ cấu hình và kết nối TiDB Cloud Serverless tự động cho Food Finder
 * Cách dùng:
 *   1. Chuyển sang TiDB Cloud: php connect_tidb.php "mat_khau_vua_generate"
 *   2. Chuyển về Localhost  : php connect_tidb.php local
 */

if (PHP_SAPI !== 'cli') {
    exit("Vui lòng chạy script này từ Command Line / Terminal.\n");
}

echo "========================================================\n";
echo "   CONG CU KET NOI TIDB CLOUD - FOOD FINDER\n";
echo "========================================================\n\n";

$configFile = __DIR__ . '/app/config.php';
$target = $argv[1] ?? '';

if (empty($target)) {
    echo "HUONG DAN SU DUNG:\n";
    echo "1. Ban hay nhan nut [Generate Password] tren man hinh TiDB Cloud.\n";
    echo "2. Copy mat khau vua duoc tao ra.\n";
    echo "3. Chay lenh sau trong terminal:\n";
    echo "   php connect_tidb.php \"d1XN05kLUwUdlFLe\"\n\n";
    echo "Neu muon quay ve Localhost (MySQL cuc bo):\n";
    echo "   php connect_tidb.php local\n\n";
    exit(0);
}

if (strtolower($target) === 'local') {
    echo "Dang chuyen cau hinh ve Localhost (127.0.0.1:3306)...\n";
    $configContent = <<<'PHP'
<?php
declare(strict_types=1);

/* Cấu hình kết nối hệ thống (Localhost MySQL) */
const APP_ENV = 'development';

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_finder');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// SSL/TLS (Tắt khi chạy Localhost)
if (!defined('DB_SSL')) define('DB_SSL', false);
if (!defined('DB_SSL_CA')) define('DB_SSL_CA', '');
if (!defined('DB_SSL_VERIFY')) define('DB_SSL_VERIFY', false);

if (!defined('APP_URL')) define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
const NOMINATIM_USER_AGENT = 'FoodFinder/1.0 (admin@example.com)';

// Cấu hình JWT & Cookie Authentication
if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'foodfinder_jwt_secret_key_2026_super_secure_random_string_938102');
if (!defined('JWT_ACCESS_TTL')) define('JWT_ACCESS_TTL', 900);
if (!defined('JWT_REFRESH_TTL')) define('JWT_REFRESH_TTL', 604800);
if (!defined('COOKIE_SECURE')) define('COOKIE_SECURE', false);
if (!defined('COOKIE_SAMESITE')) define('COOKIE_SAMESITE', 'Lax');

PHP;
    file_put_contents($configFile, $configContent);
    echo "[OK] Da cap nhat app/config.php ve Localhost thanh cong!\n";
    exit(0);
}

// Cấu hình TiDB Cloud từ ảnh của người dùng
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = 4000;
$user = '218Ru2P4NDTMbg3.root';
$dbname = 'food_finder';
$pass = trim($target);

echo "[1/3] Dang cap nhat app/config.php voi thong so TiDB Cloud:\n";
echo "      Host: $host\n";
echo "      Port: $port\n";
echo "      User: $user\n";
echo "      DB  : $dbname\n";
echo "      SSL : True\n\n";

$escapedPass = addcslashes($pass, "'\\");
$configContent = <<<PHP
<?php
declare(strict_types=1);

/* Cấu hình kết nối hệ thống (TiDB Cloud Serverless) */
const APP_ENV = 'production';

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '$host');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: $port));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: '$dbname');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '$user');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '$escapedPass');

// SSL/TLS (Bắt buộc bật true khi kết nối TiDB Cloud Serverless)
if (!defined('DB_SSL')) define('DB_SSL', true);
if (!defined('DB_SSL_CA')) define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');
if (!defined('DB_SSL_VERIFY')) define('DB_SSL_VERIFY', false);

if (!defined('APP_URL')) define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
const NOMINATIM_USER_AGENT = 'FoodFinder/1.0 (admin@example.com)';

// Cấu hình JWT & Cookie Authentication
if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'foodfinder_jwt_secret_key_2026_super_secure_random_string_938102');
if (!defined('JWT_ACCESS_TTL')) define('JWT_ACCESS_TTL', 900);
if (!defined('JWT_REFRESH_TTL')) define('JWT_REFRESH_TTL', 604800);
if (!defined('COOKIE_SECURE')) define('COOKIE_SECURE', false);
if (!defined('COOKIE_SAMESITE')) define('COOKIE_SAMESITE', 'Lax');

PHP;

file_put_contents($configFile, $configContent);
echo "[OK] Da ghi thong so vao app/config.php\n\n";

echo "[2/3] Dang kiem tra ket noi TLS toi TiDB Cloud...\n";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10,
    PDO::MYSQL_ATTR_SSL_CA => '',
];
if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    // Kết nối thử vào TiDB Cloud
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=sys;charset=utf8mb4', $host, $port);
    $pdo = new PDO($dsn, $user, $pass, $options);
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "[OK] KET NOI THANH CONG! TiDB Server Version: $version\n\n";
} catch (Throwable $e) {
    echo "\n[THAT BAI] Khong the ket noi toi TiDB Cloud:\n";
    echo "Loi: " . $e->getMessage() . "\n\n";
    echo "Goi y kiem tra:\n";
    echo "1. Ban da bam [Generate Password] tren man hinh TiDB va copy dung mat khau chua?\n";
    echo "2. Dia chi IP cua ban co bi chan khong? (Man hinh TiDB dang ghi nhan IP 123.20.151.120 duoc phep).\n";
    exit(1);
}

echo "[3/3] Dang tu dong khoi tao database va nap toan bo du lieu len TiDB Cloud...\n";
passthru('php database/setup.php');

echo "\n========================================================\n";
echo "   CHUC MUNG! FOOD FINDER DA HOAN TOAN KET NOI VOI TIDB CLOUD!\n";
echo "========================================================\n";
