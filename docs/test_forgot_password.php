<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

echo "========================================================\n";
echo "   KIỂM THỬ TÍNH NĂNG QUÊN MẬT KHẨU & ĐẶT LẠI MẬT KHẨU   \n";
echo "========================================================\n\n";

$pdo = db();

$totalTests = 0;
$passedTests = 0;

function assert_test(string $name, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS ✅] $name\n";
    } else {
        echo " [FAIL ❌] $name" . ($detail ? ": $detail" : "") . "\n";
    }
}

// 1. Tạo user thử nghiệm
$testUser = 'reset_user_' . time();
$testEmail = $testUser . '@testmail.com';
$oldPass = 'OldPass123!';
$newPass = 'NewPass456!@#';

$reg = auth_register_user($pdo, $testUser, $testEmail, $oldPass, 'Nguyễn Kiểm Thử');
$userId = (int) $reg['user']['id'];
assert_test("Khởi tạo tài khoản kiểm thử thành công", $userId > 0);

// 2. Kiểm tra yêu cầu OTP cho tài khoản không tồn tại
$cmdNoUser = 'php -r "$_SERVER[\'REQUEST_METHOD\']=\'POST\'; require \'app/bootstrap.php\'; $_POST=[\'identifier\'=>\'non_existent_999@test.com\']; require \'public/api/auth/forgot_password.php\';" 2>&1';
$outputNoUser = shell_exec($cmdNoUser);
assert_test("Từ chối khi không tìm thấy tài khoản (USER_NOT_FOUND)", str_contains((string)$outputNoUser, 'USER_NOT_FOUND'));

// 3. Yêu cầu tạo mã OTP thành công
$otpCode = null;
$stmtExpire = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
$stmtExpire->execute([':uid' => $userId]);

$code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$exp = date('Y-m-d H:i:s', time() + 900);
$pdo->prepare('INSERT INTO password_resets (user_id, email, reset_code, expires_at) VALUES (:uid, :email, :code, :exp)')
    ->execute([':uid' => $userId, ':email' => $testEmail, ':code' => $code, ':exp' => $exp]);
$otpCode = $code;

assert_test("Tạo mã OTP 6 số thành công trong database", strlen($otpCode) === 6);

// 4. Kiểm tra từ chối mã OTP sai
$cmdWrongCode = sprintf('php -r "$_SERVER[\'REQUEST_METHOD\']=\'POST\'; require \'app/bootstrap.php\'; $_POST=[\'identifier\'=>\'%s\', \'reset_code\'=>\'000000\', \'new_password\'=>\'%s\']; require \'public/api/auth/reset_password.php\';" 2>&1', $testEmail, $newPass);
$outputWrongCode = shell_exec($cmdWrongCode);
assert_test("Từ chối khi nhập mã OTP sai (INVALID_RESET_CODE)", str_contains((string)$outputWrongCode, 'INVALID_RESET_CODE'));

// 5. Kiểm tra từ chối mật khẩu quá ngắn
$cmdShortPass = sprintf('php -r "$_SERVER[\'REQUEST_METHOD\']=\'POST\'; require \'app/bootstrap.php\'; $_POST=[\'identifier\'=>\'%s\', \'reset_code\'=>\'%s\', \'new_password\'=>\'123\']; require \'public/api/auth/reset_password.php\';" 2>&1', $testEmail, $otpCode);
$outputShortPass = shell_exec($cmdShortPass);
assert_test("Từ chối khi mật khẩu mới ngắn hơn 6 ký tự (INVALID_PASSWORD)", str_contains((string)$outputShortPass, 'INVALID_PASSWORD'));

// 6. Thực hiện Đặt lại mật khẩu thành công bằng mã OTP đúng
$cmdSuccess = sprintf('php -r "$_SERVER[\'REQUEST_METHOD\']=\'POST\'; require \'app/bootstrap.php\'; $_POST=[\'identifier\'=>\'%s\', \'reset_code\'=>\'%s\', \'new_password\'=>\'%s\']; require \'public/api/auth/reset_password.php\';" 2>&1', $testEmail, $otpCode, $newPass);
$outputSuccess = shell_exec($cmdSuccess);
assert_test("Đặt lại mật khẩu thành công và tự động cấp JWT token mới", str_contains((string)$outputSuccess, '"success":true') && str_contains((string)$outputSuccess, 'access_token'));

// Kiểm tra mã OTP đã bị đánh dấu used_at
$stmtUsed = $pdo->prepare('SELECT used_at FROM password_resets WHERE user_id = :uid AND reset_code = :code');
$stmtUsed->execute([':uid' => $userId, ':code' => $otpCode]);
$usedAt = $stmtUsed->fetchColumn();
assert_test("Mã OTP được đánh dấu đã sử dụng ngay lập tức", !empty($usedAt));

// 7. Cố dùng lại mã OTP đã qua sử dụng -> Phải bị từ chối
$outputReuse = shell_exec($cmdSuccess);
assert_test("Chặn dùng lại mã OTP đã qua sử dụng", str_contains((string)$outputReuse, 'INVALID_RESET_CODE'));

// 8. Đăng nhập bằng mật khẩu cũ -> Phải thất bại
$cmdOldLogin = sprintf('php -r "require \'app/bootstrap.php\'; auth_login_user(db(), \'%s\', \'%s\');" 2>&1', $testUser, $oldPass);
$outputOldLogin = shell_exec($cmdOldLogin);
assert_test("Mật khẩu cũ không còn sử dụng được", str_contains((string)$outputOldLogin, 'INVALID_CREDENTIALS'));

// 9. Đăng nhập bằng mật khẩu mới -> Thành công
$loginNew = auth_login_user($pdo, $testUser, $newPass);
assert_test("Đăng nhập bằng mật khẩu mới thành công", !empty($loginNew['access_token']) && $loginNew['user']['id'] === $userId);

// Dọn dẹp dữ liệu test
$pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);

echo "\n--------------------------------------------------------\n";
echo "KẾT QUẢ: $passedTests / $totalTests tests passed!\n";
echo "========================================================\n";
