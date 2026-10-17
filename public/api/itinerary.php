<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/services/itinerary.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    $lat = required_float($_GET, 'lat', -90, 90);
    $lng = required_float($_GET, 'lng', -180, 180);
    $duration = optional_enum($_GET, 'duration', 'half_day', ['half_day', 'full_day', 'evening']);
    $vibe = optional_enum($_GET, 'vibe', 'chill', ['chill', 'adventure', 'foodie', 'date']);
    $radius = optional_float($_GET, 'radius', 8.0, 1.0, 25.0);

    $pdo = db();
    $result = generate_itinerary($pdo, $lat, $lng, $duration, $vibe, $radius);

    // Ghi log vào bảng itinerary_logs phục vụ thống kê Dashboard Admin
    try {
        $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = @inet_pton($rawIp) ?: null;
        $stmt = $pdo->prepare('INSERT INTO itinerary_logs (user_ip, start_lat, start_lng, duration_type, vibe, stops_count) VALUES (:ip, :lat, :lng, :duration, :vibe, :count)');
        $stmt->execute([
            ':ip' => $ip,
            ':lat' => $lat,
            ':lng' => $lng,
            ':duration' => $duration,
            ':vibe' => $vibe,
            ':count' => count($result['stops'] ?? []),
        ]);
    } catch (Throwable) {
        // Bỏ qua lỗi logging để không ảnh hưởng phản hồi chính
    }

    json_ok($result);
});
