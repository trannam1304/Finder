<?php
declare(strict_types=1);

// Entry point router cho Vercel Serverless Functions

// 1. Xử lý CORS Preflight (OPTIONS request)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// 2. Xác định route cần gọi
$route = $_GET['__route'] ?? null;
$adminRoute = $_GET['__admin_route'] ?? null;
unset($_GET['__route'], $_GET['__admin_route']);

$rootDir = dirname(__DIR__);

// 3. Phục vụ các endpoint REST API trong public/api/
if ($route !== null && $route !== '') {
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string) $route);
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

// 4. Phục vụ các endpoint admin trong public/admin/
if ($adminRoute !== null && $adminRoute !== '') {
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string) $adminRoute);
    $sub = ltrim($sub, '/');
    if ($sub === '' || $sub === 'index') {
        $sub = 'index.php';
    }
    $target = $rootDir . '/public/admin/' . $sub;
    if (is_file($target)) {
        require $target;
        exit;
    }
    if (is_file($target . '.php')) {
        require $target . '.php';
        exit;
    }
}

// 5. Fallback qua REQUEST_URI (cho môi trường dev cục bộ hoặc rewrite trực tiếp)
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Nếu REQUEST_URI bị trỏ vào /api/index.php, fallback sang HTTP_X_MATCHED_PATH
if (($uri === '/api/index.php' || $uri === '/api' || $uri === '/api/') && !empty($_SERVER['HTTP_X_MATCHED_PATH'])) {
    $uri = parse_url($_SERVER['HTTP_X_MATCHED_PATH'], PHP_URL_PATH) ?? $uri;
}
if (str_starts_with($uri, '/api/index.php/')) {
    $uri = '/api/' . substr($uri, 15);
}

if (str_starts_with($uri, '/api/')) {
    $sub = substr($uri, 5); // Bỏ tiền tố '/api/'
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    $target = $rootDir . '/public/api/' . ltrim($sub, '/');
    if (is_file($target)) {
        require $target;
        exit;
    }
    if (is_file($target . '.php')) {
        require $target . '.php';
        exit;
    }
}

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

// 6. Endpoint không tồn tại
$displayPath = $route ? ('/api/' . $route) : ($adminRoute ? ('/admin/' . $adminRoute) : $uri);
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Endpoint không tồn tại: ' . ($displayPath ?: '/'),
    ],
    'requested_path' => $displayPath
], JSON_UNESCAPED_UNICODE);
