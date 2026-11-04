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

    $identifier = trim((string) ($input['identifier'] ?? $input['email'] ?? ''));
    $resetCode = trim((string) ($input['reset_code'] ?? $input['code'] ?? ''));
    $newPassword = (string) ($input['new_password'] ?? $input['password'] ?? '');

    if ($identifier === '') {
        json_error('INVALID_PARAM', 'Vui lòng cung cấp Email hoặc Tên đăng nhập.', 400, 'identifier');
    }

    if (strlen($resetCode) < 4 || strlen($resetCode) > 10) {
        json_error('INVALID_CODE', 'Mã xác nhận không đúng định dạng.', 400, 'reset_code');
    }

    if (strlen($newPassword) < 6) {
        json_error('INVALID_PASSWORD', 'Mật khẩu mới phải có ít nhất 6 ký tự.', 400, 'new_password');
    }

    $pdo = db();

    // Tìm user
    $stmtUser = $pdo->prepare('SELECT id, username, email, full_name, role, is_active FROM users WHERE email = :id_e OR username = :id_u LIMIT 1');
    $stmtUser->execute([':id_e' => $identifier, ':id_u' => $identifier]);
    $user = $stmtUser->fetch();

    if (!$user) {
        json_error('USER_NOT_FOUND', 'Không tìm thấy tài khoản người dùng.', 404, 'identifier');
    }

    $userId = (int) $user['id'];

    // Kiểm tra mã OTP trong password_resets
    $stmtCheck = $pdo->prepare('
        SELECT id
        FROM password_resets
        WHERE user_id = :uid AND reset_code = :code AND used_at IS NULL AND expires_at > NOW()
        ORDER BY id DESC
        LIMIT 1
    ');
    $stmtCheck->execute([':uid' => $userId, ':code' => $resetCode]);
    $resetRecord = $stmtCheck->fetch();

    if (!$resetRecord) {
        json_error('INVALID_RESET_CODE', 'Mã xác nhận không chính xác hoặc đã hết thời gian hiệu lực (15 phút).', 400, 'reset_code');
    }

    // 1. Cập nhật mật khẩu mới
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute([':hash' => $newHash, ':id' => $userId]);

    // 2. Đánh dấu mã OTP đã sử dụng
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')->execute([':id' => $resetRecord['id']]);

    // 3. Thu hồi toàn bộ Refresh Token cũ để bảo vệ tài khoản
    $pdo->prepare('UPDATE user_refresh_tokens SET revoked_at = NOW() WHERE user_id = :uid AND revoked_at IS NULL')->execute([':uid' => $userId]);

    // 4. Tự động đăng nhập ngay cho người dùng (Cấp cặp JWT token mới trong HttpOnly Cookie)
    $authResult = auth_issue_tokens_for_user($pdo, $user);

    json_ok($authResult, ['message' => 'Đặt lại mật khẩu thành công! Bạn đã được tự động đăng nhập.']);
});

