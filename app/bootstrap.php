<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', dirname(__DIR__) . '/public');
}

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
