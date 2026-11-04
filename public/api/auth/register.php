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

    // 1. Validate Username
    $username = trim((string) ($input['username'] ?? ''));
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        json_error('INVALID_USERNAME', 'Tên đăng nhập phải từ 3 đến 30 ký tự, chỉ chứa chữ cái, số và dấu gạch dưới (_)', 400, 'username');
    }

    // 2. Validate Email
    $email = trim((string) ($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        json_error('INVALID_EMAIL', 'Địa chỉ email không đúng định dạng hợp lệ', 400, 'email');
    }

    // 3. Validate Họ và tên
    $fullName = trim((string) ($input['full_name'] ?? ''));
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        json_error('INVALID_NAME', 'Họ và tên phải từ 2 đến 100 ký tự', 400, 'full_name');
    }

    // 4. Validate Mật khẩu
    $password = (string) ($input['password'] ?? '');
    if (strlen($password) < 6) {
        json_error('INVALID_PASSWORD', 'Mật khẩu phải có độ dài tối thiểu 6 ký tự', 400, 'password');
    }

    $pdo = db();
    $result = auth_register_user($pdo, $username, $email, $password, $fullName);

    json_ok($result, ['message' => 'Đăng ký tài khoản thành công!'], 201);
});

