<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) json_error('INVALID_PARAM', 'id không hợp lệ', 400, 'id');
    $pdo = db();
    $stmt = $pdo->prepare('SELECT r.id, r.place_type, r.name, r.address, r.phone, r.latitude AS lat, r.longitude AS lng, r.price_min, r.price_max, r.ticket_price, r.estimated_duration, r.best_time_slot, r.price_level, r.rating, r.rating_count, r.image_url, r.open_time, r.close_time, r.is_24h, r.note, c.id AS category_id, c.type_group, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon FROM restaurants r JOIN categories c ON c.id = r.category_id WHERE r.id = :id AND r.is_active = 1');
    $stmt->execute([':id' => $id]); $row = $stmt->fetch();
    if (!$row) json_error('NOT_FOUND', 'Không tìm thấy địa điểm', 404);
    $images = $pdo->prepare('SELECT url FROM restaurant_images WHERE restaurant_id = :id ORDER BY sort_order, id');
    $images->execute([':id' => $id]);
    $row['id'] = (int) $row['id']; $row['lat'] = (float) $row['lat']; $row['lng'] = (float) $row['lng'];
    foreach (['price_min', 'price_max', 'ticket_price', 'estimated_duration', 'price_level', 'rating_count'] as $key) $row[$key] = (int) ($row[$key] ?? 0);
    $row['place_type'] = $row['place_type'] ?? 'food';
    $row['best_time_slot'] = $row['best_time_slot'] ?? 'any';
    $row['rating'] = (float) $row['rating']; $row['is_24h'] = (bool) $row['is_24h'];
    $row['category'] = ['id' => (int) $row['category_id'], 'type_group' => $row['type_group'] ?? 'food', 'name' => $row['category_name'], 'slug' => $row['category_slug'], 'icon' => $row['category_icon']];
    $row['images'] = array_map(static fn(array $image) => $image['url'], $images->fetchAll());
    unset($row['category_id'], $row['type_group'], $row['category_name'], $row['category_slug'], $row['category_icon']);
    json_ok($row);
});
