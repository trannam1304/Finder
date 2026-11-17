<?php
declare(strict_types=1);

function json_headers(): void
{
    // Xóa sạch output buffer của chính ứng dụng (do bootstrap.php khởi tạo)
    // để loại bỏ PHP warnings/deprecated notices trước khi gửi JSON headers.
    // Chỉ xóa các buffer do chính app tạo ra — không đụng đến buffer hệ thống ngoài cùng (level 0).
    while (ob_get_level() > 1) {
        ob_end_clean();
    }
    // Nếu còn đúng 1 buffer (có thể do Vercel runtime), flush/clean nội dung đã tích lũy
    if (ob_get_level() === 1) {
        ob_clean(); // Chỉ xóa nội dung, giữ buffer
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
        $msg = $e->getMessage();
        error_log('[api_guard] ' . get_class($e) . ': ' . $msg . ' in ' . $e->getFile() . ':' . $e->getLine());
        // Trên môi trường production, ẩn chi tiết lỗi nội bộ
        $displayMsg = (defined('APP_ENV') && APP_ENV === 'production')
            ? 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.'
            : $msg;
        json_error('SERVER_ERROR', $displayMsg, 500);
    }
}
