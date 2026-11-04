<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    $pdo = db();
    $user = auth_get_current_user($pdo);

    if (!$user) {
        json_error('UNAUTHORIZED', 'Chưa đăng nhập hoặc phiên làm việc đã kết thúc.', 401);
    }

    // Lấy thông tin mới nhất từ DB
    $stmt = $pdo->prepare('SELECT id, username, email, full_name, avatar_url, role, is_active, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $user['id']]);
    $freshUser = $stmt->fetch();

    if (!$freshUser || (int) $freshUser['is_active'] !== 1) {
        clear_auth_cookies();
        json_error('UNAUTHORIZED', 'Tài khoản không tồn tại hoặc đã bị khóa.', 401);
    }

    unset($freshUser['password_hash']);

    json_ok([
        'user' => [
            'id' => (int) $freshUser['id'],
            'username' => $freshUser['username'],
            'email' => $freshUser['email'],
            'full_name' => $freshUser['full_name'],
            'avatar_url' => $freshUser['avatar_url'],
            'role' => $freshUser['role'],
            'created_at' => $freshUser['created_at'],
        ],
    ]);
});

