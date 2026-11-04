<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    $rawRefreshToken = $_COOKIE['refresh_token'] ?? null;
    if (!$rawRefreshToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded['refresh_token'])) {
                $rawRefreshToken = (string) $decoded['refresh_token'];
            }
        }
    }

    if (!$rawRefreshToken) {
        clear_auth_cookies();
        json_error('MISSING_REFRESH_TOKEN', 'Không tìm thấy refresh token trong cookie.', 401);
    }

    $pdo = db();
    $result = auth_rotate_refresh_token($pdo, $rawRefreshToken);

    if (!$result) {
        json_error('INVALID_REFRESH_TOKEN', 'Refresh token không hợp lệ hoặc phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 401);
    }

    json_ok($result, ['message' => 'Làm mới token thành công!']);
});

