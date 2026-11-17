<?php
declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

class DatabaseAuthTest extends TestCase
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function testDatabaseConnectionAlive(): void
    {
        $stmt = $this->pdo->query('SELECT 1');
        $this->assertEquals(1, (int) $stmt->fetchColumn());
    }

    public function testUserLifecycleAndJwtRotation(): void
    {
        $username = 'unittest_' . bin2hex(random_bytes(4));
        $email = $username . '@example.com';
        $password = 'TestPass@2026';
        $fullName = 'Unit Test Runner';

        // 1. Đăng ký người dùng mới
        $reg = auth_register_user($this->pdo, $username, $email, $password, $fullName);
        $this->assertArrayHasKey('access_token', $reg);
        $this->assertArrayHasKey('user', $reg);

        $userId = (int) $reg['user']['id'];
        $this->assertEquals($username, $reg['user']['username']);

        // 2. Kiểm tra Refresh token được lưu dưới dạng SHA-256 hash trong DB
        $stmt = $this->pdo->prepare('SELECT id, token_hash, revoked_at FROM user_refresh_tokens WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        $this->assertNotNull($row, "Refresh token hash must exist in DB");
        $this->assertNull($row['revoked_at'], "Initial refresh token must not be revoked");
        $this->assertEquals(64, strlen($row['token_hash']), "Stored hash must be 64 characters SHA-256");

        // 3. Đăng nhập kiểm tra
        $login = auth_login_user($this->pdo, $username, $password);
        $this->assertEquals($userId, (int) $login['user']['id']);

        // 4. Kiểm tra cấp Refresh Token & Xoay vòng Token (Rotation)
        $rawToken = generate_refresh_token();
        $tokenHash = hash_refresh_token($rawToken);
        $stmtIns = $this->pdo->prepare('
            INSERT INTO user_refresh_tokens (user_id, token_hash, expires_at)
            VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 7 DAY))
        ');
        $stmtIns->execute([':uid' => $userId, ':hash' => $tokenHash]);

        // Xoay vòng token
        $rotated = auth_rotate_refresh_token($this->pdo, $rawToken);
        $this->assertNotNull($rotated);
        $this->assertArrayHasKey('access_token', $rotated);
        $this->assertEquals($userId, (int) $rotated['user']['id']);

        // Token cũ phải bị thu hồi ngay lập tức
        $stmtCheck = $this->pdo->prepare('SELECT revoked_at FROM user_refresh_tokens WHERE token_hash = :hash');
        $stmtCheck->execute([':hash' => $tokenHash]);
        $revokedRow = $stmtCheck->fetch();
        $this->assertNotNull($revokedRow['revoked_at'], "Old refresh token must be revoked after rotation");

        // Dùng lại token cũ phải bị từ chối
        $replayAttempt = auth_rotate_refresh_token($this->pdo, $rawToken);
        $this->assertNull($replayAttempt, "Replay of revoked token must return null");

        // 5. Cleanup dọn dẹp dữ liệu test
        $del = $this->pdo->prepare('DELETE FROM users WHERE id = :uid');
        $del->execute([':uid' => $userId]);
    }
}
