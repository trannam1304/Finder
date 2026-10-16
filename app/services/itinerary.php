<?php
declare(strict_types=1);

require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/restaurant_query.php';

/**
 * Cấu hình các chặng dừng (Slot templates) cho từng loại thời lượng và vibe.
 */
function itinerary_template(string $duration, string $vibe): array
{
    if ($duration === 'evening') {
        return [
            [
                'step' => 1,
                'title' => 'Khởi đầu: Ngắm hoàng hôn & Check-in',
                'time_slot' => '17:30 - 18:30',
                'place_type' => 'tourism',
                'preferred_cats' => [11, 9, 8], // Check-in hoàng hôn, Công viên, Bảo tàng
                'fallback_types' => ['tourism', 'food'],
            ],
            [
                'step' => 2,
                'title' => 'Bữa tối: Thưởng thức ẩm thực ấm cúng',
                'time_slot' => '18:45 - 20:15',
                'place_type' => 'food',
                'preferred_cats' => ($vibe === 'date') ? [4, 1] : (($vibe === 'foodie') ? [1, 2, 4, 5] : [1, 2, 4]),
                'fallback_types' => ['food'],
            ],
            [
                'step' => 3,
                'title' => 'Buổi tối: Thư giãn, giải trí & Trò chuyện',
                'time_slot' => '20:30 - 22:00',
                'place_type' => ($vibe === 'adventure') ? 'entertainment' : 'food',
                'preferred_cats' => ($vibe === 'adventure') ? [12, 13, 14, 15] : [7, 14, 11], // Cà phê chill, Boardgame
                'fallback_types' => ['entertainment', 'food'],
            ],
        ];
    }

    if ($duration === 'full_day') {
        return [
            [
                'step' => 1,
                'title' => 'Sáng: Khởi đầu ngày mới & Cà phê năng lượng',
                'time_slot' => '08:30 - 09:30',
                'place_type' => 'food',
                'preferred_cats' => [7, 3, 2, 1], // Cafe, Bánh mì, Phở
                'fallback_types' => ['food'],
            ],
            [
                'step' => 2,
                'title' => 'Sáng muộn: Tham quan văn hóa & Trải nghiệm',
                'time_slot' => '09:45 - 11:30',
                'place_type' => 'tourism',
                'preferred_cats' => [8, 10, 9, 11], // Bảo tàng, Di tích, Công viên
                'fallback_types' => ['tourism', 'entertainment'],
            ],
            [
                'step' => 3,
                'title' => 'Trưa: Bữa trưa ngon & Nghỉ ngơi',
                'time_slot' => '11:45 - 13:15',
                'place_type' => 'food',
                'preferred_cats' => [1, 2, 6, 4], // Cơm, Bún phở, Chay, Lẩu
                'fallback_types' => ['food'],
            ],
            [
                'step' => 4,
                'title' => 'Chiều: Vui chơi giải trí & Vận động bung tỏa',
                'time_slot' => '14:00 - 16:30',
                'place_type' => 'entertainment',
                'preferred_cats' => ($vibe === 'adventure') ? [12, 15, 13] : [14, 15, 13, 12], // Thể thao, Mall, Boardgame, Rạp phim
                'fallback_types' => ['entertainment', 'tourism'],
            ],
            [
                'step' => 5,
                'title' => 'Tối: Ngắm cảnh hoàng hôn & Ăn tối đặc sắc',
                'time_slot' => '17:00 - 19:30',
                'place_type' => ($vibe === 'foodie') ? 'food' : 'tourism',
                'preferred_cats' => [11, 4, 9, 7], // Check-in hoàng hôn, Lẩu nướng
                'fallback_types' => ['food', 'tourism'],
            ],
        ];
    }

    // Mặc định: half_day (Nửa ngày - 3 chặng dừng)
    return [
        [
            'step' => 1,
            'title' => 'Chặng 1: Gặp gỡ & Cà phê bắt đầu chuyến đi',
            'time_slot' => '08:30 - 09:30',
            'place_type' => 'food',
            'preferred_cats' => [7, 3, 2], // Cà phê, Bánh mì, Phở
            'fallback_types' => ['food'],
        ],
        [
            'step' => 2,
            'title' => 'Chặng 2: Hoạt động khám phá & Trải nghiệm chính',
            'time_slot' => '09:45 - 11:45',
            'place_type' => ($vibe === 'adventure') ? 'entertainment' : 'tourism',
            'preferred_cats' => ($vibe === 'adventure') ? [12, 13, 14, 15] : [8, 9, 11, 10], // Vận động hoặc Tham quan
            'fallback_types' => ['tourism', 'entertainment'],
        ],
        [
            'step' => 3,
            'title' => 'Chặng 3: Bữa ăn chất lượng & Nạp lại năng lượng',
            'time_slot' => '12:00 - 13:30',
            'place_type' => 'food',
            'preferred_cats' => [1, 2, 4, 5, 6], // Cơm, Phở, Lẩu, Ăn vặt
            'fallback_types' => ['food'],
        ],
    ];
}

/**
 * Tiêu đề mô tả lộ trình dựa trên vibe và duration.
 */
function itinerary_meta(string $duration, string $vibe): array
{
    $vibeNames = [
        'chill' => 'Thư Thái & Check-in',
        'adventure' => 'Vui Chơi Năng Động & Trải Nghiệm',
        'foodie' => 'Ăn Sập & Thưởng Thức Ẩm Thực',
        'date' => 'Hẹn Hò Lãng Mạn',
    ];
    $durationNames = [
        'half_day' => 'Nửa ngày (~4 tiếng)',
        'full_day' => 'Cả ngày (~8 tiếng)',
        'evening' => 'Buổi tối (~3.5 tiếng)',
    ];

    $title = sprintf(
        'Lộ Trình %s — Phong Cách %s',
        $durationNames[$duration] ?? 'Khám Phá',
        $vibeNames[$vibe] ?? 'Tự Do'
    );

    return [
        'title' => $title,
        'duration_label' => $durationNames[$duration] ?? 'Nửa ngày',
        'vibe_label' => $vibeNames[$vibe] ?? 'Chill',
    ];
}

/**
 * Tìm 1 địa điểm tốt nhất cho một chặng dừng (Nearest Neighbor có ưu tiên category & rating).
 */
function find_best_candidate(
    PDO $pdo,
    float $fromLat,
    float $fromLng,
    array $slot,
    array $excludeIds,
    float $maxRadiusKm
): ?array {
    // 1. Tìm các ứng viên tiềm năng trong bán kính từ điểm xuất phát
    $box = bounding_box($fromLat, $fromLng, $maxRadiusKm);
    $distanceSql = '(2 * 6371.0088 * ASIN(SQRT('
        . 'POW(SIN((RADIANS(r.latitude) - RADIANS(:lat_1)) / 2), 2)'
        . ' + COS(RADIANS(:lat_2)) * COS(RADIANS(r.latitude))'
        . ' * POW(SIN((RADIANS(r.longitude) - RADIANS(:lng_1)) / 2), 2)'
        . ')))';

    $where = [
        'r.is_active = 1',
        'r.latitude BETWEEN :lat_min AND :lat_max',
        'r.longitude BETWEEN :lng_min AND :lng_max',
    ];
    $bind = [
        ':lat_1' => $fromLat,
        ':lat_2' => $fromLat,
        ':lng_1' => $fromLng,
        ':lat_min' => $box['lat_min'],
        ':lat_max' => $box['lat_max'],
        ':lng_min' => $box['lng_min'],
        ':lng_max' => $box['lng_max'],
        ':radius' => $maxRadiusKm,
    ];

    if (!empty($excludeIds)) {
        $inPlaceholders = [];
        foreach ($excludeIds as $idx => $exId) {
            $key = ':ex_' . $idx;
            $inPlaceholders[] = $key;
            $bind[$key] = (int) $exId;
        }
        $where[] = 'r.id NOT IN (' . implode(',', $inPlaceholders) . ')';
    }

    // Điều kiện loại hình (ưu tiên place_type chính của slot)
    $typeList = array_unique(array_merge([$slot['place_type']], $slot['fallback_types'] ?? []));
    $typePlaceholders = [];
    foreach ($typeList as $tIdx => $tVal) {
        $key = ':t_' . $tIdx;
        $typePlaceholders[] = $key;
        $bind[$key] = $tVal;
    }
    $where[] = 'r.place_type IN (' . implode(',', $typePlaceholders) . ')';

    $sql = "SELECT r.id, r.place_type, r.name, r.address, r.phone, r.latitude AS lat, r.longitude AS lng,
        r.price_min, r.price_max, r.ticket_price, r.estimated_duration, r.best_time_slot,
        r.price_level, r.rating, r.rating_count, r.image_url, r.open_time, r.close_time, r.is_24h,
        c.id AS category_id, c.type_group, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
        {$distanceSql} AS distance_km
        FROM restaurants r
        JOIN categories c ON c.id = r.category_id
        WHERE " . implode(' AND ', $where) . "
        HAVING distance_km <= :radius
        ORDER BY distance_km ASC";

    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        return null;
    }

    // Tính điểm ưu tiên cho từng candidate:
    // Điểm = (10 - Khoảng cách km) * 1.5 + (Rating * 2.0) + (Khớp category ưu tiên ? +15 : 0) + (Khớp place_type chính ? +10 : 0)
    $bestCandidate = null;
    $bestScore = -999999.0;

    foreach ($candidates as $cand) {
        $dist = (float) $cand['distance_km'];
        $rating = (float) $cand['rating'];
        $catId = (int) $cand['category_id'];
        $pType = (string) $cand['place_type'];

        $score = (10.0 - min($dist, 10.0)) * 1.5 + ($rating * 2.0);

        if ($pType === $slot['place_type']) {
            $score += 10.0;
        }
        if (in_array($catId, $slot['preferred_cats'] ?? [], true)) {
            $score += 15.0;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestCandidate = $cand;
        }
    }

    if ($bestCandidate === null) {
        $bestCandidate = $candidates[0];
    }

    // Chuẩn hóa kiểu dữ liệu
    $bestCandidate['id'] = (int) $bestCandidate['id'];
    $bestCandidate['lat'] = (float) $bestCandidate['lat'];
    $bestCandidate['lng'] = (float) $bestCandidate['lng'];
    $bestCandidate['price_min'] = (int) $bestCandidate['price_min'];
    $bestCandidate['price_max'] = (int) $bestCandidate['price_max'];
    $bestCandidate['ticket_price'] = (int) ($bestCandidate['ticket_price'] ?? 0);
    $bestCandidate['estimated_duration'] = (int) ($bestCandidate['estimated_duration'] ?? 60);
    $bestCandidate['distance_km'] = round((float) $bestCandidate['distance_km'], 2);
    $bestCandidate['rating'] = (float) $bestCandidate['rating'];
    $bestCandidate['rating_count'] = (int) $bestCandidate['rating_count'];
    $bestCandidate['category'] = [
        'id' => (int) $bestCandidate['category_id'],
        'type_group' => $bestCandidate['type_group'] ?? 'food',
        'name' => $bestCandidate['category_name'],
        'slug' => $bestCandidate['category_slug'],
        'icon' => $bestCandidate['category_icon'],
    ];
    unset($bestCandidate['category_id'], $bestCandidate['type_group'], $bestCandidate['category_name'], $bestCandidate['category_slug'], $bestCandidate['category_icon']);

    return $bestCandidate;
}

/**
 * Sinh link chỉ đường Google Maps Multi-waypoint.
 */
function build_google_maps_directions_url(float $startLat, float $startLng, array $stops): string
{
    if (empty($stops)) {
        return sprintf('https://www.google.com/maps/search/?api=1&query=%f,%f', $startLat, $startLng);
    }

    $lastStop = end($stops);
    $destLat = $lastStop['place']['lat'];
    $destLng = $lastStop['place']['lng'];

    $waypoints = [];
    // Các điểm nằm giữa điểm xuất phát và điểm đích cuối
    for ($i = 0; $i < count($stops) - 1; $i++) {
        $waypoints[] = sprintf('%f,%f', $stops[$i]['place']['lat'], $stops[$i]['place']['lng']);
    }

    $url = sprintf(
        'https://www.google.com/maps/dir/?api=1&origin=%f,%f&destination=%f,%f',
        $startLat,
        $startLng,
        $destLat,
        $destLng
    );

    if (!empty($waypoints)) {
        $url .= '&waypoints=' . urlencode(implode('|', $waypoints));
    }

    return $url;
}

/**
 * Thuật toán chính: Đề xuất lộ trình thông minh (Nearest Neighbor Heuristic).
 */
function generate_itinerary(
    PDO $pdo,
    float $userLat,
    float $userLng,
    string $duration = 'half_day',
    string $vibe = 'chill',
    float $maxRadiusKm = 8.0
): array {
    $validDurations = ['half_day', 'full_day', 'evening'];
    if (!in_array($duration, $validDurations, true)) {
        $duration = 'half_day';
    }

    $validVibes = ['chill', 'adventure', 'foodie', 'date'];
    if (!in_array($vibe, $validVibes, true)) {
        $vibe = 'chill';
    }

    $template = itinerary_template($duration, $vibe);
    $meta = itinerary_meta($duration, $vibe);

    $stops = [];
    $excludeIds = [];
    $currentLat = $userLat;
    $currentLng = $userLng;

    $totalDistanceKm = 0.0;
    $totalMinutes = 0;
    $totalBudgetMin = 0;

    foreach ($template as $slot) {
        $candidate = find_best_candidate($pdo, $currentLat, $currentLng, $slot, $excludeIds, $maxRadiusKm);

        if ($candidate === null) {
            // Mở rộng bán kính gấp đôi nếu chưa tìm thấy
            $candidate = find_best_candidate($pdo, $currentLat, $currentLng, $slot, $excludeIds, $maxRadiusKm * 2.0);
        }

        if ($candidate !== null) {
            $excludeIds[] = $candidate['id'];

            // Tính thời gian di chuyển (trung bình 25km/h trong đô thị + 3 phút chờ đèn đỏ)
            $travelMinutes = max(3, (int) round(($candidate['distance_km'] / 25.0) * 60));
            $durationMinutes = $candidate['estimated_duration'] > 0 ? $candidate['estimated_duration'] : 60;

            $totalDistanceKm += $candidate['distance_km'];
            $totalMinutes += ($travelMinutes + $durationMinutes);
            $totalBudgetMin += ($candidate['ticket_price'] + $candidate['price_min']);

            $stops[] = [
                'step' => $slot['step'],
                'action_title' => $slot['title'],
                'time_slot' => $slot['time_slot'],
                'travel_time_minutes' => $travelMinutes,
                'distance_from_prev_km' => $candidate['distance_km'],
                'place' => $candidate,
            ];

            // Cập nhật vị trí hiện tại cho chặng tiếp theo
            $currentLat = $candidate['lat'];
            $currentLng = $candidate['lng'];
        }
    }

    $googleMapsUrl = build_google_maps_directions_url($userLat, $userLng, $stops);

    return [
        'summary' => [
            'title' => $meta['title'],
            'duration' => $duration,
            'duration_label' => $meta['duration_label'],
            'vibe' => $vibe,
            'vibe_label' => $meta['vibe_label'],
            'total_stops' => count($stops),
            'total_distance_km' => round($totalDistanceKm, 2),
            'total_time_minutes' => $totalMinutes,
            'estimated_budget_min' => $totalBudgetMin,
            'starting_point' => [
                'lat' => $userLat,
                'lng' => $userLng,
            ],
            'google_maps_url' => $googleMapsUrl,
        ],
        'stops' => $stops,
    ];
}

/**
 * Task BE-04: Đổi chặng đơn lẻ (Reroll a single stop).
 */
function reroll_itinerary_stop(
    PDO $pdo,
    int $currentStopId,
    array $excludeIds,
    float $fromLat,
    float $fromLng,
    string $placeType = 'food',
    string $vibe = 'chill',
    float $maxRadiusKm = 10.0
): ?array {
    $excludeIds[] = $currentStopId;

    $slot = [
        'step' => 0,
        'title' => 'Chặng thay thế',
        'time_slot' => '',
        'place_type' => $placeType,
        'preferred_cats' => ($placeType === 'tourism') ? [11, 8, 9] : (($placeType === 'entertainment') ? [12, 13, 14, 15] : [1, 2, 4, 7]),
        'fallback_types' => [$placeType],
    ];

    $candidate = find_best_candidate($pdo, $fromLat, $fromLng, $slot, $excludeIds, $maxRadiusKm);

    if ($candidate === null) {
        $candidate = find_best_candidate($pdo, $fromLat, $fromLng, $slot, $excludeIds, $maxRadiusKm * 2.0);
    }

    return $candidate;
}
