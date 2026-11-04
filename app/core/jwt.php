<?php
declare(strict_types=1);

/**
 * Thư viện xử lý JSON Web Token (JWT) và Cookie An Toàn
 * Chuẩn HMAC-SHA256 (HS256) - Không cần thư viện ngoài (Pure PHP)
 */

if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', 'foodfinder_default_secret_key_please_change_2026');
}
if (!defined('JWT_ACCESS_TTL')) {
    define('JWT_ACCESS_TTL', 900); // 15 phút
}
if (!defined('JWT_REFRESH_TTL')) {
    define('JWT_REFRESH_TTL', 604800); // 7 ngày
}
if (!defined('COOKIE_SECURE')) {
    define('COOKIE_SECURE', false);
}
if (!defined('COOKIE_SAMESITE')) {
    define('COOKIE_SAMESITE', 'Lax');
}

/**
 * Base64URL Encode theo RFC 7515
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64URL Decode theo RFC 7515
 */
function base64url_decode(string $data): ?string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? null : $decoded;
}

/**
 * Ký và tạo chuỗi JWT Access Token
 *
 * @param array $payload Dữ liệu cần mã hóa (sub, email, role, ...)
 * @param int $ttl Thời gian sống (giây), mặc định 15 phút
 * @return string Chuỗi JWT token hoàn chỉnh (Header.Payload.Signature)
 */
function jwt_sign(array $payload, int $ttl = JWT_ACCESS_TTL): string
{
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256',
    ];

    $now = time();
    $payload['iat'] = $payload['iat'] ?? $now;
    $payload['nbf'] = $payload['nbf'] ?? $now;
    $payload['exp'] = $payload['exp'] ?? ($now + $ttl);
    if (!isset($payload['iss']) && defined('APP_URL')) {
        $payload['iss'] = APP_URL;
    }

    $headerEncoded = base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $payloadEncoded = base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $dataToSign = $headerEncoded . '.' . $payloadEncoded;

    $rawSignature = hash_hmac('sha256', $dataToSign, JWT_SECRET, true);
    $signatureEncoded = base64url_encode($rawSignature);

    return $dataToSign . '.' . $signatureEncoded;
}

/**
 * Xác thực và giải mã JWT Token
 *
 * @param string $token Chuỗi JWT
 * @return array|null Trả về payload nếu hợp lệ, ngược lại null
 */
function jwt_verify(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

    // 1. Kiểm tra chữ ký (Signature)
    $dataToSign = $headerEncoded . '.' . $payloadEncoded;
    $expectedRawSignature = hash_hmac('sha256', $dataToSign, JWT_SECRET, true);
    $expectedSignatureEncoded = base64url_encode($expectedRawSignature);

    if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
        return null; // Chữ ký bị sai lệch hoặc bị giả mạo
    }

    // 2. Giải mã Header
    $headerJson = base64url_decode($headerEncoded);
    if (!$headerJson) {
        return null;
    }
    $header = json_decode($headerJson, true);
    if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
        return null;
    }

    // 3. Giải mã Payload
    $payloadJson = base64url_decode($payloadEncoded);
    if (!$payloadJson) {
        return null;
    }
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    $now = time();

    // 4. Kiểm tra thời hạn hết hạn (exp)
    if (isset($payload['exp']) && $now > (int) $payload['exp']) {
        return null; // Token đã hết hạn
    }

    // 5. Kiểm tra thời điểm có hiệu lực (nbf)
    if (isset($payload['nbf']) && $now < (int) $payload['nbf']) {
        return null;
    }

    return $payload;
}

/**
 * Sinh chuỗi Refresh Token ngẫu nhiên (64 ký tự hex an toàn tuyệt đối)
 */
function generate_refresh_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Băm Refresh Token bằng SHA-256 trước khi lưu vào Database
 */
function hash_refresh_token(string $refreshToken): string
{
    return hash('sha256', $refreshToken);
}

/**
 * Lưu Access Token và Refresh Token vào Cookie
 * Chống XSS bằng thuộc tính HttpOnly
 */
function set_auth_cookies(string $accessToken, string $refreshToken, int $refreshTtl = JWT_REFRESH_TTL): void
{
    if (headers_sent()) {
        return;
    }

    $now = time();
    $secure = (bool) (COOKIE_SECURE || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));

    // Access Token Cookie (15 phút, HttpOnly)
    setcookie('access_token', $accessToken, [
        'expires' => $now + JWT_ACCESS_TTL,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => COOKIE_SAMESITE,
    ]);

    // Refresh Token Cookie (7 ngày, HttpOnly - Tránh lưu LocalStorage chống đánh cắp XSS)
    setcookie('refresh_token', $refreshToken, [
        'expires' => $now + $refreshTtl,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => COOKIE_SAMESITE,
    ]);
}

/**
 * Xóa sạch Auth Cookies khi đăng xuất
 */
function clear_auth_cookies(): void
{
    if (headers_sent()) {
        return;
    }

    $secure = (bool) (COOKIE_SECURE || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));
    $past = time() - 3600;

    setcookie('access_token', '', [
        'expires' => $past,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => COOKIE_SAMESITE,
    ]);

    setcookie('refresh_token', '', [
        'expires' => $past,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => COOKIE_SAMESITE,
    ]);
}