<?php
declare(strict_types=1);

// Entry point router cho Vercel Serverless Functions
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

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

// 2. Phục vụ các endpoint backend trong public/admin/ (login.php, restaurants.php, ...)
if (str_starts_with($uri, '/admin/') && str_ends_with($uri, '.php')) {
    $sub = substr($uri, 7); // Bỏ tiền tố '/admin/'
    $sub = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $sub);
    $target = dirname(__DIR__) . '/public/admin/' . $sub;
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
