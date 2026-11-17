<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/app/core/jwt.php';

class JwtTest extends TestCase
{
    public function testBase64UrlEncodeAndDecode(): void
    {
        $samples = [
            'Hello World',
            'Thử nghiệm tiếng Việt có dấu 123456!@#$%^&*()',
            '{"sub":1,"name":"Admin","roles":["admin","user"]}',
            random_bytes(64)
        ];

        foreach ($samples as $sample) {
            $encoded = base64url_encode($sample);
            $this->assertFalse(str_contains($encoded, '+'), "Encoded string must not contain '+'");
            $this->assertFalse(str_contains($encoded, '/'), "Encoded string must not contain '/'");
            $this->assertFalse(str_contains($encoded, '='), "Encoded string must not contain '='");

            $decoded = base64url_decode($encoded);
            $this->assertEquals($sample, $decoded);
        }
    }

    public function testJwtSignStructure(): void
    {
        $payload = ['sub' => 42, 'role' => 'admin'];
        $token = jwt_sign($payload, 300);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts, "JWT must consist of exactly 3 parts separated by dots");

        $header = json_decode(base64url_decode($parts[0]), true);
        $this->assertEquals('JWT', $header['typ'] ?? null);
        $this->assertEquals('HS256', $header['alg'] ?? null);

        $decodedPayload = json_decode(base64url_decode($parts[1]), true);
        $this->assertEquals(42, $decodedPayload['sub'] ?? null);
        $this->assertEquals('admin', $decodedPayload['role'] ?? null);
        $this->assertTrue(isset($decodedPayload['exp']), "JWT payload must have 'exp'");
    }

    public function testJwtVerifyValidToken(): void
    {
        $payload = ['sub' => 101, 'email' => 'dev@foodfinder.vn', 'name' => 'Kiet Developer'];
        $token = jwt_sign($payload, 60);

        $verified = jwt_verify($token);
        $this->assertNotNull($verified);
        $this->assertEquals(101, $verified['sub']);
        $this->assertEquals('dev@foodfinder.vn', $verified['email']);
        $this->assertEquals('Kiet Developer', $verified['name']);
    }

    public function testJwtVerifyRejectsTamperedSignature(): void
    {
        $token = jwt_sign(['sub' => 99], 60);
        $tamperedToken = $token . 'forged';
        $this->assertNull(jwt_verify($tamperedToken));
    }

    public function testJwtVerifyRejectsTamperedPayload(): void
    {
        $token = jwt_sign(['sub' => 99, 'role' => 'member'], 60);
        $parts = explode('.', $token);

        // Giả mạo payload thành admin
        $forgedPayload = base64url_encode(json_encode(['sub' => 99, 'role' => 'admin', 'exp' => time() + 60]));
        $forgedToken = $parts[0] . '.' . $forgedPayload . '.' . $parts[2];

        $this->assertNull(jwt_verify($forgedToken), "Tampered payload with original signature must be rejected");
    }

    public function testJwtVerifyRejectsExpiredToken(): void
    {
        $token = jwt_sign(['sub' => 12], -30); // Đã hết hạn cách đây 30 giây
        $this->assertNull(jwt_verify($token), "Expired token must be rejected");
    }

    public function testRefreshTokenGenerationAndHashing(): void
    {
        $token = generate_refresh_token();
        $this->assertTrue(strlen($token) >= 64, "Raw refresh token must be at least 64 chars long");

        $hash1 = hash_refresh_token($token);
        $hash2 = hash_refresh_token($token);
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1), "SHA-256 hash must be exactly 64 hex characters");
    }
}
