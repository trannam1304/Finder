<?php
declare(strict_types=1);

function json_headers(): void
{
    // Xóa sạch output buffer để loại bỏ bất kỳ warnings/deprecations nào PHP đã in ra
    // Điều này ngăn PHP warnings (ví dụ: PDO SSL deprecated trên PHP 8.4+) phá vỡ JSON response
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function json_ok(mixed $data, array $meta = [], int $status = 200): never
{
    json_headers();
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data, 'meta' => (object) $meta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $code, string $message, int $status = 400, ?string $field = null): never
{
    json_headers();
    http_response_code($status);
    $error = ['code' => $code, 'message' => $message];
    if ($field !== null) $error['field'] = $field;
    echo json_encode(['success' => false, 'error' => $error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_guard(callable $handler): void
{
    try {
        $handler();
    } catch (InvalidArgumentException $e) {
        json_error('INVALID_PARAM', $e->getMessage(), 400);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        json_error('SERVER_ERROR', 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.', 500);
    }
}
