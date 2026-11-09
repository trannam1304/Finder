<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('METHOD_NOT_ALLOWED', 'Chỉ chấp nhận phương thức POST', 405);
    }

    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
    }

    $identifier = trim((string) ($input['identifier'] ?? $input['username'] ?? $input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($identifier === '') {
        json_error('INVALID_PARAM', 'Vui lòng nhập tên đăng nhập hoặc email', 400, 'identifier');
    }

    if ($password === '') {
        json_error('INVALID_PARAM', 'Vui lòng nhập mật khẩu', 400, 'password');
    }

    $pdo = db();
    $result = auth_login_user($pdo, $identifier, $password);

    json_ok($result, ['message' => 'Đăng nhập thành công!']);
});

