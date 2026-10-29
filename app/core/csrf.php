<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(?string $token = null): void
{
    if ($token === null) {
        $token = $_POST['csrf'] ?? null;
    }
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(419);
        exit('Phiên làm việc đã hết hạn. Hãy tải lại trang và thử lại.');
    }
}
