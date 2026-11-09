<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';

/**
 * Chuyển địa chỉ IP người dùng thành dạng nhị phân 16 bytes
 */
function auth_client_ip_binary(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $bin = inet_pton($ip);
    return $bin !== false ? $bin : (inet_pton('127.0.0.1') ?: str_repeat("\0", 16));
}

/**
 * Tạo token mới (Access Token + Refresh Token) và lưu Cookie HttpOnly
 */
function auth_issue_tokens_for_user(PDO $pdo, array $user): array
{
    $userId = (int) $user['id'];
    $role = $user['role'] ?? 'member';
    $username = $user['username'] ?? '';
    $email = $user['email'] ?? '';
    $fullName = $user['full_name'] ?? '';

    // 1. Tạo Access Token (JWT ngắn hạn 15 phút)
    $payload = [
        'sub' => $userId,
        'username' => $username,
        'email' => $email,
        'name' => $fullName,
        'role' => $role,
    ];
    $accessToken = jwt_sign($payload, JWT_ACCESS_TTL);

    // 2. Tạo Refresh Token (Chuỗi ngẫu nhiên 64 ký tự, dài hạn 7 ngày)
    $refreshToken = generate_refresh_token();
    $tokenHash = hash_refresh_token($refreshToken);

    $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_TTL);
    $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $ipBin = auth_client_ip_binary();

    // 3. Lưu SHA-256 hash của Refresh Token vào Database
    $stmt = $pdo->prepare('
        INSERT INTO user_refresh_tokens (user_id, token_hash, expires_at, user_agent, ip_address)
        VALUES (:user_id, :token_hash, :expires_at, :user_agent, :ip_address)
    ');
    $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
        ':user_agent' => $userAgent,
        ':ip_address' => $ipBin,
    ]);

    // 4. Lưu cả 2 token vào Cookie với cờ HttpOnly (chống XSS và không lưu localStorage)
    set_auth_cookies($accessToken, $refreshToken, JWT_REFRESH_TTL);

    return [
        'access_token' => $accessToken,
        'token_type' => 'Bearer',
        'expires_in' => JWT_ACCESS_TTL,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'role' => $role,
            'avatar_url' => $user['avatar_url'] ?? null,
        ],
    ];
}

/**
 * Đăng ký tài khoản thành viên mới
 */
function auth_register_user(PDO $pdo, string $username, string $email, string $password, string $fullName): array
{
    // Kiểm tra trùng username hoặc email
    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :u OR email = :e LIMIT 1');
    $stmt->execute([':u' => $username, ':e' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if (strcasecmp($existing['username'], $username) === 0) {
            json_error('USERNAME_EXISTS', 'Tên đăng nhập này đã được sử dụng. Vui lòng chọn tên khác.', 409, 'username');
        }
        if (strcasecmp($existing['email'], $email) === 0) {
            json_error('EMAIL_EXISTS', 'Địa chỉ email này đã được đăng ký. Vui lòng đăng nhập.', 409, 'email');
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
        INSERT INTO users (username, email, password_hash, full_name, role, is_active)
        VALUES (:username, :email, :password_hash, :full_name, "member", 1)
    ');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':full_name' => $fullName,
    ]);

    $newId = (int) $pdo->lastInsertId();

    $user = [
        'id' => $newId,
        'username' => $username,
        'email' => $email,
        'full_name' => $fullName,
        'role' => 'member',
        'avatar_url' => null,
    ];

    return auth_issue_tokens_for_user($pdo, $user);
}

/**
 * Đăng nhập bằng Tên đăng nhập/Email và Mật khẩu
 */
function auth_login_user(PDO $pdo, string $identifier, string $password): array
{
    // Cho phép đăng nhập bằng cả username hoặc email
    $stmt = $pdo->prepare('
        SELECT id, username, email, password_hash, full_name, avatar_url, role, is_active
        FROM users
        WHERE username = :id_u OR email = :id_e
        LIMIT 1
    ');
    $stmt->execute([':id_u' => $identifier, ':id_e' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_error('INVALID_CREDENTIALS', 'Tên đăng nhập/Email hoặc mật khẩu không chính xác.', 401);
    }

    if ((int) $user['is_active'] !== 1) {
        json_error('ACCOUNT_DISABLED', 'Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ ban quản trị.', 403);
    }

    return auth_issue_tokens_for_user($pdo, $user);
}

/**
 * Đổi mới Access Token bằng Refresh Token (Token Rotation)
 */
function auth_rotate_refresh_token(PDO $pdo, ?string $rawRefreshToken = null): ?array
{
    if (!$rawRefreshToken) {
        $rawRefreshToken = $_COOKIE['refresh_token'] ?? null;
    }

    if (!$rawRefreshToken || !is_string($rawRefreshToken) || strlen($rawRefreshToken) < 32) {
        clear_auth_cookies();
        return null;
    }

    $tokenHash = hash_refresh_token($rawRefreshToken);

    // Tìm token trong DB (chưa thu hồi và chưa hết hạn)
    $stmt = $pdo->prepare('
        SELECT t.id as token_id, t.user_id, t.expires_at,
               u.id, u.username, u.email, u.full_name, u.avatar_url, u.role, u.is_active
        FROM user_refresh_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token_hash = :hash AND t.revoked_at IS NULL AND t.expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([':hash' => $tokenHash]);
    $record = $stmt->fetch();

    if (!$record) {
        // Token không hợp lệ hoặc đã hết hạn -> Xóa cookie
        clear_auth_cookies();
        return null;
    }

    if ((int) $record['is_active'] !== 1) {
        // Vô hiệu hóa token nếu tài khoản bị khóa
        $pdo->prepare('UPDATE user_refresh_tokens SET revoked_at = NOW() WHERE id = :id')->execute([':id' => $record['token_id']]);
        clear_auth_cookies();
        return null;
    }

    // 1. Thu hồi token cũ (Refresh Token Rotation - tăng cường bảo mật)
    $pdo->prepare('UPDATE user_refresh_tokens SET revoked_at = NOW() WHERE id = :id')->execute([':id' => $record['token_id']]);

    // 2. Phát hành cặp token mới và cập nhật Cookie
    $user = [
        'id' => (int) $record['id'],
        'username' => $record['username'],
        'email' => $record['email'],
        'full_name' => $record['full_name'],
        'avatar_url' => $record['avatar_url'],
        'role' => $record['role'],
    ];

    return auth_issue_tokens_for_user($pdo, $user);
}

/**
 * Thu hồi Refresh Token khi đăng xuất
 */
function auth_logout(PDO $pdo, ?string $rawRefreshToken = null): void
{
    if (!$rawRefreshToken) {
        $rawRefreshToken = $_COOKIE['refresh_token'] ?? null;
    }

    if ($rawRefreshToken && is_string($rawRefreshToken)) {
        $tokenHash = hash_refresh_token($rawRefreshToken);
        $stmt = $pdo->prepare('UPDATE user_refresh_tokens SET revoked_at = NOW() WHERE token_hash = :hash AND revoked_at IS NULL');
        $stmt->execute([':hash' => $tokenHash]);
    }

    clear_auth_cookies();
}

/**
 * Lấy Access Token từ Cookie hoặc Authorization Header
 */
function auth_extract_access_token(): ?string
{
    if (!empty($_COOKIE['access_token'])) {
        return $_COOKIE['access_token'];
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Lấy thông tin thành viên hiện tại từ JWT (có hỗ trợ tự động refresh nếu access_token vừa hết hạn)
 */
function auth_get_current_user(?PDO $pdo = null): ?array
{
    $token = auth_extract_access_token();

    if ($token) {
        $payload = jwt_verify($token);
        if ($payload && isset($payload['sub'])) {
            return [
                'id' => (int) $payload['sub'],
                'username' => $payload['username'] ?? '',
                'email' => $payload['email'] ?? '',
                'full_name' => $payload['name'] ?? '',
                'role' => $payload['role'] ?? 'member',
            ];
        }
    }

    // Nếu Access Token hết hạn nhưng còn Refresh Token trong Cookie -> Thử auto refresh
    if (!empty($_COOKIE['refresh_token'])) {
        $pdo = $pdo ?? db();
        $result = auth_rotate_refresh_token($pdo);
        if ($result && isset($result['user'])) {
            return $result['user'];
        }
    }

    return null;
}

/**
 * Middleware bảo vệ API: Yêu cầu đăng nhập, trả về 401 nếu chưa xác thực
 */
function auth_require_user(?PDO $pdo = null): array
{
    $user = auth_get_current_user($pdo);
    if (!$user) {
        json_error('UNAUTHORIZED', 'Vui lòng đăng nhập để thực hiện chức năng này.', 401);
    }
    return $user;
}

