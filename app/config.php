<?php
declare(strict_types=1);

/* Cấu hình kết nối hệ thống (TiDB Cloud Serverless) */
const APP_ENV = 'production';

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 4000));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_finder');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '218Ru2P4NDTMbg3.root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'd1XN05kLUwUdlFLe');

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
