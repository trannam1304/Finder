<?php
declare(strict_types=1);

// Entry point router cho Vercel Serverless Functions

// Tắt display_errors ngay đầu tiên để PHP warnings không làm hỏng JSON response
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Bắt đầu output buffering để bắt mọi warnings nếu display_errors chưa tắt kịp
if (!ob_get_level()) {
    ob_start();
}

// 1. Xử lý CORS Preflight (OPTIONS request)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    // Xóa buffer trước khi gửi headers
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// 2. Lấy URI gốc từ các biến môi trường Vercel / PHP
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Nếu REQUEST_URI bị đổi thành /api/index.php hoặc /api, lấy từ các header chuyển tiếp của Vercel
if ($uri === '/api/index.php' || $uri === '/api' || $uri === '/api/') {
    if (!empty($_SERVER['HTTP_X_MATCHED_PATH'])) {
        $uri = parse_url($_SERVER['HTTP_X_MATCHED_PATH'], PHP_URL_PATH) ?? $uri;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_URI'])) {
        $uri = parse_url($_SERVER['HTTP_X_FORWARDED_URI'], PHP_URL_PATH) ?? $uri;
    }
}
if (str_starts_with($uri, '/api/index.php/')) {
    $uri = '/api/' . substr($uri, 15);
}

// Hỗ trợ cả param query fallback nếu có
if (!empty($_GET['__route'])) {
    $uri = '/api/' . ltrim((string) $_GET['__route'], '/');
    unset($_GET['__route']);
}
if (!empty($_GET['__admin_route'])) {
    $uri = '/admin/' . ltrim((string) $_GET['__admin_route'], '/');
    unset($_GET['__admin_route']);
}

$rootDir = dirname(__DIR__);

// 3. Phục vụ các endpoint REST API trong public/api/
if (str_starts_with($uri, '/api/')) {
    $sub = substr($uri, 5); // Bỏ tiền tố '/api/'
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    $sub = ltrim($sub, '/');
    $target = $rootDir . '/public/api/' . $sub;
    if (is_file($target)) {
        require $target;
        exit;
    }
    if (is_file($target . '.php')) {
        require $target . '.php';
        exit;
    }
}

// 4. Phục vụ các endpoint backend trong public/admin/
if (str_starts_with($uri, '/admin')) {
    $sub = substr($uri, 6); // Bỏ tiền tố '/admin'
    if ($sub === '' || $sub === '/') {
        $sub = '/index.php';
    }
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    $target = $rootDir . '/public/admin/' . ltrim($sub, '/');
    if (is_file($target)) {
        require $target;
        exit;
    }
    if (is_file($target . '.php')) {
        require $target . '.php';
        exit;
    }
}

// 5. Endpoint không tồn tại
// Xóa buffer trước khi gửi JSON
while (ob_get_level() > 0) ob_end_clean();
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Endpoint không tồn tại: ' . ($uri ?: '/'),
    ],
    'requested_path' => $uri
], JSON_UNESCAPED_UNICODE);
