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
    if ($identifier === '') {
        json_error('INVALID_PARAM', 'Vui lòng nhập Email hoặc Tên đăng nhập của bạn.', 400, 'identifier');
    }

    $pdo = db();

    // Tìm user theo email hoặc username
    $stmt = $pdo->prepare('SELECT id, username, email, full_name, is_active FROM users WHERE email = :id_e OR username = :id_u LIMIT 1');
    $stmt->execute([':id_e' => $identifier, ':id_u' => $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        json_error('USER_NOT_FOUND', 'Không tìm thấy tài khoản nào khớp với thông tin đã nhập.', 404, 'identifier');
    }

    if ((int) $user['is_active'] !== 1) {
        json_error('ACCOUNT_DISABLED', 'Tài khoản này hiện đang bị tạm khóa.', 403);
    }

    // Sinh mã OTP 6 số ngẫu nhiên
    $resetCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 phút

    // Vô hiệu hóa các mã OTP cũ chưa dùng của user này
    $stmtExpireOld = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
    $stmtExpireOld->execute([':uid' => $user['id']]);

    // Lưu mã OTP mới vào database
    $stmtInsert = $pdo->prepare('
        INSERT INTO password_resets (user_id, email, reset_code, expires_at)
        VALUES (:user_id, :email, :reset_code, :expires_at)
    ');
    $stmtInsert->execute([
        ':user_id' => $user['id'],
        ':email' => $user['email'],
        ':reset_code' => $resetCode,
        ':expires_at' => $expiresAt,
    ]);

    // Tạo email che giấu (masked email) ví dụ: d***r@gmail.com
    $parts = explode('@', $user['email']);
    $namePart = $parts[0];
    $domainPart = $parts[1] ?? '';
    $maskedName = mb_substr($namePart, 0, 1) . '***' . (mb_strlen($namePart) > 1 ? mb_substr($namePart, -1) : '');
    $maskedEmail = $maskedName . '@' . $domainPart;

    // Trả về kết quả (Kèm theo OTP trong data để hỗ trợ trải nghiệm trực tiếp ngay trên localhost/development)
    json_ok([
        'email' => $maskedEmail,
        'raw_email' => $user['email'],
        'reset_code' => $resetCode,
        'expires_in' => 900,
    ], [
        'message' => 'Mã xác nhận gồm 6 chữ số đã được tạo. Vui lòng kiểm tra và nhập mã để đặt lại mật khẩu.'
    ]);
});

