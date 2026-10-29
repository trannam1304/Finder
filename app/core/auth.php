<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (!headers_sent()) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
        session_start();
    } else {
        @session_start();
    }
}

function client_ip_binary(): string
{
    return inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?: inet_pton('127.0.0.1');
}

function login_is_throttled(PDO $pdo): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)');
    $stmt->execute([':ip' => client_ip_binary()]);
    return (int) $stmt->fetchColumn() >= 5;
}

function record_login_attempt(PDO $pdo, string $username): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, username) VALUES (:ip, :username)');
    $stmt->execute([':ip' => client_ip_binary(), ':username' => mb_substr($username, 0, 50)]);
}

function attempt_login(string $username, string $password): bool
{
    $pdo = db();
    if (login_is_throttled($pdo)) return false;
    $stmt = $pdo->prepare('SELECT id, username, display_name, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        record_login_attempt($pdo, $username);
        return false;
    }
    start_secure_session();
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['admin'] = ['id' => (int) $admin['id'], 'username' => $admin['username'], 'display_name' => $admin['display_name']];
    $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id')->execute([':id' => $admin['id']]);
    return true;
}

function require_admin(): void
{
    start_secure_session();
    if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
}
