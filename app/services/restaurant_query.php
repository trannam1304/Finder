<?php
declare(strict_types=1);

require_once __DIR__ . '/geo.php';

function restaurant_rows(PDO $pdo, array $filters, bool $paginate = true): array
{
    $box = bounding_box($filters['lat'], $filters['lng'], $filters['radius']);
    $distance = '(2 * 6371.0088 * ASIN(SQRT('
        . 'POW(SIN((RADIANS(r.latitude) - RADIANS(:lat_1)) / 2), 2)'
        . ' + COS(RADIANS(:lat_2)) * COS(RADIANS(r.latitude))'
        . ' * POW(SIN((RADIANS(r.longitude) - RADIANS(:lng_1)) / 2), 2)'
        . ')))';
    $where = ['r.is_active = 1', 'r.latitude BETWEEN :lat_min AND :lat_max', 'r.longitude BETWEEN :lng_min AND :lng_max'];
    $bind = [
        ':lat_1' => $filters['lat'], ':lat_2' => $filters['lat'], ':lng_1' => $filters['lng'],
        ':lat_min' => $box['lat_min'], ':lat_max' => $box['lat_max'], ':lng_min' => $box['lng_min'], ':lng_max' => $box['lng_max'],
        ':radius' => $filters['radius'],
    ];
    if ($filters['category_id'] !== null) { $where[] = 'r.category_id = :category_id'; $bind[':category_id'] = $filters['category_id']; }
    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $where[] = 'r.place_type = :place_type';
        $bind[':place_type'] = $filters['type'];
    }
    if ($filters['price_level'] !== null) { $where[] = 'r.price_level = :price_level'; $bind[':price_level'] = $filters['price_level']; }
    if ($filters['min_rating'] > 0) { $where[] = 'r.rating >= :min_rating'; $bind[':min_rating'] = $filters['min_rating']; }
    if ($filters['open_now']) {
        $where[] = '(r.is_24h = 1 OR (r.close_time > r.open_time AND CURTIME() >= r.open_time AND CURTIME() < r.close_time) OR (r.close_time <= r.open_time AND (CURTIME() >= r.open_time OR CURTIME() < r.close_time)))';
    }
    if ($filters['q'] !== '') {
        $where[] = '(r.name LIKE :q OR r.address LIKE :q)';
        $bind[':q'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']) . '%';
    }
    $order = match ($filters['sort']) {
        'rating' => 'r.rating DESC, distance_km ASC',
        'price' => 'r.price_min ASC, distance_km ASC',
        default => 'distance_km ASC',
    };
    $sql = "SELECT r.id, r.place_type, r.name, r.address, r.phone, r.latitude AS lat, r.longitude AS lng,
        r.price_min, r.price_max, r.ticket_price, r.estimated_duration, r.best_time_slot,
        r.price_level, r.rating, r.rating_count, r.image_url,
        r.open_time, r.close_time, r.is_24h, c.id AS category_id, c.type_group, c.name AS category_name,
        c.slug AS category_slug, c.icon AS category_icon, {$distance} AS distance_km
        FROM restaurants r JOIN categories c ON c.id = r.category_id
        WHERE " . implode(' AND ', $where) . " HAVING distance_km <= :radius ORDER BY {$order}";
    if ($paginate) $sql .= ' LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($bind as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    if ($paginate) {
        $stmt->bindValue(':limit', $filters['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $filters['offset'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return array_map(static function (array $r): array {
        $r['id'] = (int) $r['id']; $r['lat'] = (float) $r['lat']; $r['lng'] = (float) $r['lng'];
        $r['price_min'] = (int) $r['price_min']; $r['price_max'] = (int) $r['price_max'];
        $r['ticket_price'] = (int) ($r['ticket_price'] ?? 0);
        $r['estimated_duration'] = (int) ($r['estimated_duration'] ?? 60);
        $r['best_time_slot'] = $r['best_time_slot'] ?? 'any';
        $r['place_type'] = $r['place_type'] ?? 'food';
        $r['price_level'] = (int) $r['price_level'];
        $r['rating'] = (float) $r['rating']; $r['rating_count'] = (int) $r['rating_count'];
        $r['distance_km'] = round((float) $r['distance_km'], 4); $r['is_24h'] = (bool) $r['is_24h'];
        $r['is_open_now'] = is_open_now($r);
        $r['category'] = [
            'id' => (int) $r['category_id'],
            'type_group' => $r['type_group'] ?? 'food',
            'name' => $r['category_name'],
            'slug' => $r['category_slug'],
            'icon' => $r['category_icon']
        ];
        unset($r['category_id'], $r['category_name'], $r['category_slug'], $r['category_icon'], $r['type_group']);
        return $r;
    }, $rows);
}
