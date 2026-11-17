<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', dirname(__DIR__) . '/public');
}

// Bắt đầu output buffering để ngăn PHP warnings/deprecations phá vỡ JSON response headers
// Đặc biệt quan trọng trên PHP 8.4+ (Vercel) khi PDO SSL constants deprecated gây ra output sớm
if (!ob_get_level()) {
    ob_start();
}

// Tắt hiển thị lỗi ra output (vẫn log vào error_log)
// Điều này đảm bảo warnings không làm hỏng JSON response
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Core Infrastructure
// 1. Cấu hình hệ thống (Luôn nạp trước)
$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
    require_once $configFile;
} elseif (is_file(__DIR__ . '/config.example.php')) {
    require_once __DIR__ . '/config.example.php';
}

// 2. Core Infrastructure
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/jwt.php';
require_once __DIR__ . '/core/jwt_auth.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/response.php';

// Helpers
// 3. Helpers
require_once __DIR__ . '/helpers/validate.php';
require_once __DIR__ . '/helpers/format.php';
