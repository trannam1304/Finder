<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/services/itinerary.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    $currentId = required_int($_GET, 'current_id', 1, PHP_INT_MAX);
    $fromLat = required_float($_GET, 'from_lat', -90, 90);
    $fromLng = required_float($_GET, 'from_lng', -180, 180);
    $placeType = optional_enum($_GET, 'place_type', 'food', ['food', 'tourism', 'entertainment']);
    $vibe = optional_enum($_GET, 'vibe', 'chill', ['chill', 'adventure', 'foodie', 'date']);
    $radius = optional_float($_GET, 'radius', 10.0, 1.0, 30.0);

    // Xử lý exclude_ids (ví dụ: "1,5,12")
    $excludeRaw = $_GET['exclude_ids'] ?? '';
    $excludeIds = [];
    if (is_string($excludeRaw) && $excludeRaw !== '') {
        $parts = explode(',', $excludeRaw);
        foreach ($parts as $p) {
            $val = filter_var(trim($p), FILTER_VALIDATE_INT);
            if ($val !== false && $val > 0) {
                $excludeIds[] = $val;
            }
        }
    }

    $pdo = db();
    $candidate = reroll_itinerary_stop(
        $pdo,
        $currentId,
        $excludeIds,
        $fromLat,
        $fromLng,
        $placeType,
        $vibe,
        $radius
    );

    if ($candidate === null) {
        json_error('Không tìm thấy địa điểm thay thế phù hợp trong khu vực lân cận.', 404);
        return;
    }

    json_ok([
        'place' => $candidate,
        'message' => 'Đã đổi địa điểm thành công.',
    ]);
});
