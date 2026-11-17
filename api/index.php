<?php
declare(strict_types=1);

// Entry point router cho Vercel Serverless Functions

// Xử lý CORS OPTIONS Preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Nếu REQUEST_URI bị trỏ vào /api/index.php hoặc /api, fallback sang HTTP_X_MATCHED_PATH hoặc query __route
if ($uri === '/api/index.php' || $uri === '/api' || $uri === '/api/') {
    if (!empty($_SERVER['HTTP_X_MATCHED_PATH'])) {
        $uri = parse_url($_SERVER['HTTP_X_MATCHED_PATH'], PHP_URL_PATH) ?? $uri;
    } elseif (!empty($_GET['__route'])) {
        $uri = '/api/' . ltrim((string) $_GET['__route'], '/');
    }
}

// Xử lý trường hợp PATH_INFO như /api/index.php/auth/register.php
if (str_starts_with($uri, '/api/index.php/')) {
    $uri = '/api/' . substr($uri, 15);
}

// 1. Phục vụ các endpoint REST API trong public/api/
if (str_starts_with($uri, '/api/')) {
    $sub = substr($uri, 5); // Bỏ tiền tố '/api/'
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    $target = dirname(__DIR__) . '/public/api/' . $sub;
    if (is_file($target)) {
        require $target;
        exit;
    }
}

// 2. Phục vụ các endpoint backend trong public/admin/ (index.php, login.php, restaurants.php, ...)
if (str_starts_with($uri, '/admin/')) {
    $sub = substr($uri, 7); // Bỏ tiền tố '/admin/'
    if ($sub === '' || $sub === '/') {
        $sub = 'index.php';
    }
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    if (!str_ends_with($sub, '.php')) {
        $sub .= '.php';
    }
    $target = dirname(__DIR__) . '/public/admin/' . $sub;
    if (is_file($target)) {
        require $target;
        exit;
    }
}

if ($uri === '/admin') {
    $target = dirname(__DIR__) . '/public/admin/index.php';
    if (is_file($target)) {
        require $target;
        exit;
    }
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'error' => 'Endpoint not found',
    'requested_path' => $uri
], JSON_UNESCAPED_UNICODE);
