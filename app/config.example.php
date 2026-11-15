<?php
declare(strict_types=1);

/* Cấu hình mẫu cho Food Finder - Copy file này thành config.php */
const APP_ENV = 'development';

// ── CẤU HÌNH LOCALHOST (XAMPP):
// const DB_HOST = '127.0.0.1';
// const DB_PORT = 3306;
// const DB_NAME = 'food_finder';
// const DB_USER = 'root';
// const DB_PASS = '';
// const DB_SSL  = false;

// ── HOẶC CẤU HÌNH CLOUD TIDB (TiDB Serverless):
// const DB_HOST = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com'; // Host từ TiDB Console
// const DB_PORT = 4000;                                             // TiDB mặc định dùng cổng 4000
// const DB_NAME = 'food_finder';                                     // Tên database
// const DB_USER = 'xxxxxx.root';                                     // Username có prefix từ TiDB
// const DB_PASS = 'your_tidb_password';                             // Mật khẩu TiDB
// const DB_SSL  = true;                                              // BẮT BUỘC TRUE cho TiDB

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_finder');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_SSL')) define('DB_SSL', getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1');
if (!defined('DB_SSL_CA')) define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');
if (!defined('DB_SSL_VERIFY')) define('DB_SSL_VERIFY', false);

if (!defined('APP_URL')) define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
const NOMINATIM_USER_AGENT = 'FoodFinder/1.0 (admin@example.com)';

if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change_this_to_a_random_secret_in_production');
if (!defined('JWT_ACCESS_TTL')) define('JWT_ACCESS_TTL', 900);
if (!defined('JWT_REFRESH_TTL')) define('JWT_REFRESH_TTL', 604800);
if (!defined('COOKIE_SECURE')) define('COOKIE_SECURE', false);
if (!defined('COOKIE_SAMESITE')) define('COOKIE_SAMESITE', 'Lax');
