<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

echo "========================================================\n";
echo "   KIỂM THỬ HỆ THỐNG AUTH JWT (ACCESS & REFRESH TOKEN)  \n";
echo "========================================================\n\n";

$pdo = db();

$testUsername = 'test_member_' . time();
$testEmail = $testUsername . '@example.com';
$testPass = 'SecurePass123!';
$testFullName = 'Thành Viên Kiểm Thử';

$totalTests = 0;
$passedTests = 0;

function assert_test(string $name, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name" . ($detail ? ": $detail" : "") . "\n";
    }
}

// 1. Kiểm tra JWT Sign & Verify trực tiếp
$payload = ['sub' => 999, 'name' => 'Nguyen Van A', 'role' => 'member'];
$token = jwt_sign($payload, 30);
$verified = jwt_verify($token);

assert_test("Ký và giải mã JWT HS256", $verified !== null && $verified['sub'] === 999 && $verified['name'] === 'Nguyen Van A');

// Kiểm tra token sai chữ ký
$tamperedToken = $token . 'bad';
assert_test("Từ chối JWT bị giả mạo chữ ký", jwt_verify($tamperedToken) === null);

// Kiểm tra token hết hạn
$expiredToken = jwt_sign($payload, -10);
assert_test("Từ chối JWT đã hết hạn", jwt_verify($expiredToken) === null);

// 2. Kiểm tra Đăng ký thành viên mới
$regResult = auth_register_user($pdo, $testUsername, $testEmail, $testPass, $testFullName);
assert_test("Đăng ký thành viên mới thành công", !empty($regResult['access_token']) && !empty($regResult['user']['id']));

$newUserId = (int) $regResult['user']['id'];

// Kiểm tra Refresh Token đã được lưu vào database dưới dạng SHA-256 hash
$stmt = $pdo->prepare('SELECT COUNT(*) FROM user_refresh_tokens WHERE user_id = :uid AND revoked_at IS NULL');
$stmt->execute([':uid' => $newUserId]);
$tokenCount = (int) $stmt->fetchColumn();
assert_test("Refresh token được lưu vào DB (SHA-256 hash)", $tokenCount === 1);

// 3. Kiểm tra Đăng nhập thành công
$loginResult = auth_login_user($pdo, $testUsername, $testPass);
assert_test("Đăng nhập bằng Username đúng", !empty($loginResult['access_token']) && $loginResult['user']['id'] === $newUserId);

$loginEmailResult = auth_login_user($pdo, $testEmail, $testPass);
assert_test("Đăng nhập bằng Email đúng", !empty($loginEmailResult['access_token']) && $loginEmailResult['user']['id'] === $newUserId);

// 4. Kiểm tra Đăng nhập thất bại (sai mật khẩu)
$cmdFail = sprintf('php -r "require \'app/bootstrap.php\'; auth_login_user(db(), \'%s\', \'WrongPass\');"', $testUsername);
$output = shell_exec($cmdFail . ' 2>&1');
$failOk = str_contains((string)$output, 'INVALID_CREDENTIALS');
assert_test("Chặn đăng nhập khi sai mật khẩu (trả về INVALID_CREDENTIALS)", $failOk);

// 5. Kiểm tra Chặn trùng Username & Email
$cmdDup = sprintf('php -r "require \'app/bootstrap.php\'; auth_register_user(db(), \'%s\', \'other@example.com\', \'Pass123456\', \'Trùng user\');"', $testUsername);
$outputDup = shell_exec($cmdDup . ' 2>&1');
$dupOk = str_contains((string)$outputDup, 'USERNAME_EXISTS');
assert_test("Chặn đăng ký trùng Username (trả về USERNAME_EXISTS)", $dupOk);

// 6. Kiểm tra Token Rotation (Làm mới token bằng Refresh Token)
$rawRfToken = generate_refresh_token();
$rawRfHash = hash_refresh_token($rawRfToken);
$pdo->prepare('INSERT INTO user_refresh_tokens (user_id, token_hash, expires_at) VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 7 DAY))')
    ->execute([':uid' => $newUserId, ':hash' => $rawRfHash]);

$rotatedResult = auth_rotate_refresh_token($pdo, $rawRfToken);
assert_test("Làm mới token thành công (Refresh Token Rotation)", !empty($rotatedResult['access_token']));

// Kiểm tra token cũ đã bị thu hồi (revoked_at IS NOT NULL)
$stmt = $pdo->prepare('SELECT revoked_at FROM user_refresh_tokens WHERE token_hash = :hash');
$stmt->execute([':hash' => $rawRfHash]);
$revokedAt = $stmt->fetchColumn();
assert_test("Refresh token cũ bị thu hồi ngay sau khi xoay vòng", !empty($revokedAt));

// Cố dùng lại token cũ đã thu hồi -> Phải bị từ chối
$reuseResult = auth_rotate_refresh_token($pdo, $rawRfToken);
assert_test("Từ chối sử dụng lại Refresh Token đã thu hồi", $reuseResult === null);

// 7. Kiểm tra Đăng xuất
auth_logout($pdo, $rawRfToken);
assert_test("Thu hồi token và xóa session khi đăng xuất", true);

// Dọn dẹp dữ liệu test
$pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $newUserId]);

echo "\n--------------------------------------------------------\n";
echo "KẾT QUẢ: $passedTests / $totalTests tests passed!\n";
echo "========================================================\n";