<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function testPasswordHashingBcrypt(): void
    {
        $rawPassword = 'SuperSecretPassword@2026';
        $hash = password_hash($rawPassword, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($rawPassword, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
        $this->assertFalse(password_verify($rawPassword . ' ', $hash));
    }

    public function testOtpCodeFormatAndRandomness(): void
    {
        // Kiểm tra logic sinh OTP 6 chữ số
        $otps = [];
        for ($i = 0; $i < 50; $i++) {
            $code = (string) random_int(100000, 999999);
            $this->assertEquals(6, strlen($code));
            $this->assertTrue(ctype_digit($code));
            $otps[$code] = true;
        }
        // Đảm bảo tính ngẫu nhiên (không bị trùng lặp nhiều)
        $this->assertTrue(count($otps) >= 48, "Random OTP generation should have high entropy");
    }

    public function testTimingAttackSafeComparison(): void
    {
        $secret = '3f7a9b8c2d1e0f4a';
        $matching = '3f7a9b8c2d1e0f4a';
        $mismatched = '3f7a9b8c2d1e0f4b';

        $this->assertTrue(hash_equals($secret, $matching));
        $this->assertFalse(hash_equals($secret, $mismatched));
        $this->assertFalse(hash_equals($secret, 'short'));
    }
}
