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

    $pdo = db();
    auth_logout($pdo, $rawRefreshToken);

    json_ok(['logged_out' => true], ['message' => 'Đăng xuất thành công!']);
});

