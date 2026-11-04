<?php
declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

function send_request(string $method, string $path, array $data = [], array $cookies = []): array {
    global $baseUrl;
    $url = $baseUrl . $path;
    $ch = curl_init();

    $headers = ['Accept: application/json'];
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => $method,
    ];

    if (!empty($cookies)) {
        $cookiePairs = [];
        foreach ($cookies as $k => $v) {
            $cookiePairs[] = "$k=$v";
        }
        $opts[CURLOPT_COOKIE] = implode('; ', $cookiePairs);
    }

    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerText = substr($raw, 0, $headerSize);
    $bodyText = substr($raw, $headerSize);

    // Bóc tách Set-Cookie headers
    $setCookies = [];
    preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]*)/mi', $headerText, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $setCookies[trim($m[1])] = trim($m[2]);
    }

    return [
        'code' => $httpCode,
        'headers' => $headerText,
        'cookies' => $setCookies,
        'body' => $bodyText,
        'json' => json_decode($bodyText, true),
    ];
}

echo "========================================================\n";
echo "   KIỂM THỬ LUỒNG HTTP AUTH JWT & COOKIES ĐẦY ĐỦ       \n";
echo "========================================================\n\n";

$testUser = 'http_user_' . time();
$testEmail = $testUser . '@gmail.com';
$testPass = 'Pass@123456';
$testName = 'Kiểm Thử HTTP Flow';

$testsPassed = 0;
$total = 0;

function report(string $label, bool $ok, string $extra = '') {
    global $testsPassed, $total;
    $total++;
    if ($ok) {
        $testsPassed++;
        echo "[PASS ✅] $label\n";
    } else {
        echo "[FAIL ❌] $label: $extra\n";
    }
}

// 1. Kiểm tra /api/auth/me.php khi chưa có cookie -> phải trả 401
$res1 = send_request('GET', '/api/auth/me.php');
report('Truy cập /api/auth/me.php khi chưa đăng nhập trả về 401', $res1['code'] === 401);

// 2. Đăng ký tài khoản mới qua POST /api/auth/register.php
$regData = [
    'username' => $testUser,
    'email' => $testEmail,
    'password' => $testPass,
    'full_name' => $testName,
];
$res2 = send_request('POST', '/api/auth/register.php', $regData);
$hasAccessCookie = !empty($res2['cookies']['access_token']);
$hasRefreshCookie = !empty($res2['cookies']['refresh_token']);
$isHttpOnly = str_contains($res2['headers'], 'HttpOnly');

report('Đăng ký tài khoản thành công (HTTP 201)', $res2['code'] === 201);
report('Server cấp access_token và refresh_token trong Cookie', $hasAccessCookie && $hasRefreshCookie);
report('Cookie chứa thuộc tính HttpOnly (chống XSS)', $isHttpOnly);

$currentCookies = $res2['cookies'];

// 3. Lấy thông tin cá nhân qua /api/auth/me.php bằng Cookie vừa nhận
$res3 = send_request('GET', '/api/auth/me.php', [], $currentCookies);
$nameMatches = ($res3['json']['data']['user']['full_name'] ?? '') === $testName;
report('Lấy thông tin profile /api/auth/me.php thành công từ Cookie (HTTP 200)', $res3['code'] === 200 && $nameMatches);

// 4. Kiểm tra Đăng nhập lại qua POST /api/auth/login.php
$res4 = send_request('POST', '/api/auth/login.php', ['identifier' => $testUser, 'password' => $testPass]);
report('Đăng nhập lại qua /api/auth/login.php thành công (HTTP 200)', $res4['code'] === 200 && !empty($res4['cookies']['access_token']));

$newCookies = $res4['cookies'];

// 5. Làm mới Access Token qua /api/auth/refresh.php bằng refresh_token cookie
$res5 = send_request('POST', '/api/auth/refresh.php', [], ['refresh_token' => $newCookies['refresh_token']]);
report('Đổi mới Access Token qua /api/auth/refresh.php thành công (HTTP 200)', $res5['code'] === 200 && !empty($res5['cookies']['access_token']));

$rotatedCookies = $res5['cookies'];

// 6. Đăng xuất qua POST /api/auth/logout.php
$res6 = send_request('POST', '/api/auth/logout.php', [], $rotatedCookies);
report('Đăng xuất thành công qua /api/auth/logout.php (HTTP 200)', $res6['code'] === 200);

// 7. Truy cập lại /api/auth/me.php bằng token cũ đã logout -> Phải bị chặn 401
$res7 = send_request('GET', '/api/auth/me.php', [], ['refresh_token' => $rotatedCookies['refresh_token']]);
report('Token đã đăng xuất bị thu hồi và từ chối truy cập (HTTP 401)', $res7['code'] === 401);

echo "\n--------------------------------------------------------\n";
echo "TỔNG KẾT: $testsPassed / $total kiểm thử HTTP Auth thành công!\n";
echo "========================================================\n";

