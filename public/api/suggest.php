<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('METHOD_NOT_ALLOWED', 'Chỉ chấp nhận phương thức POST', 405);
    }

    // Nhận dữ liệu từ JSON hoặc POST form thông thường
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
    }

    // Rate limiting: tối đa 10 đề xuất / 10 phút
    start_secure_session();
    $now = time();
    $_SESSION['suggest_timestamps'] = array_values(array_filter(
        $_SESSION['suggest_timestamps'] ?? [],
        static fn(int $ts): bool => $ts > $now - 600
    ));
    if (count($_SESSION['suggest_timestamps']) >= 10) {
        json_error('RATE_LIMITED', 'Bạn đã gửi nhiều đề xuất trong thời gian ngắn. Vui lòng thử lại sau ít phút.', 429);
    }

    // Validate các trường thông tin
    $name = trim((string) ($input['name'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
        json_error('INVALID_PARAM', 'Tên địa điểm phải từ 2 đến 150 ký tự', 400, 'name');
    }

    $placeType = trim((string) ($input['place_type'] ?? 'food'));
    if (!in_array($placeType, ['food', 'tourism', 'entertainment'], true)) {
        $placeType = 'food';
    }

    $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$categoryId) {
        json_error('INVALID_PARAM', 'Danh mục địa điểm không hợp lệ', 400, 'category_id');
    }

    $pdo = db();
    $checkCat = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
    $checkCat->execute([':id' => $categoryId]);
    if (!$checkCat->fetchColumn()) {
        json_error('INVALID_PARAM', 'Danh mục không tồn tại trong hệ thống', 400, 'category_id');
    }

    $address = trim((string) ($input['address'] ?? ''));
    if ($address === '' || mb_strlen($address) > 255) {
        json_error('INVALID_PARAM', 'Địa chỉ không được để trống và tối đa 255 ký tự', 400, 'address');
    }

    $phone = trim((string) ($input['phone'] ?? ''));
    if (mb_strlen($phone) > 20) {
        $phone = mb_substr($phone, 0, 20);
    }

    $lat = filter_var($input['lat'] ?? $input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($input['lng'] ?? $input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        json_error('INVALID_PARAM', 'Tọa độ vị trí (vĩ độ, kinh độ) không hợp lệ', 400, 'coords');
    }

    $priceMin = filter_var($input['price_min'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
    $priceMax = filter_var($input['price_max'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
    $ticketPrice = filter_var($input['ticket_price'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
    $duration = filter_var($input['estimated_duration'] ?? 60, FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 720]]) ?: 60;

    $openTime = trim((string) ($input['open_time'] ?? ''));
    $closeTime = trim((string) ($input['close_time'] ?? ''));
    $openTime = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $openTime) ? substr($openTime, 0, 5) : null;
    $closeTime = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $closeTime) ? substr($closeTime, 0, 5) : null;

    $note = trim((string) ($input['note'] ?? ''));
    if (mb_strlen($note) > 1000) {
        $note = mb_substr($note, 0, 1000);
    }

    $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ip = @inet_pton($rawIp) ?: null;

    $stmt = $pdo->prepare('INSERT INTO place_suggestions
        (name, place_type, category_id, address, phone, latitude, longitude, price_min, price_max, ticket_price, estimated_duration, open_time, close_time, note, submitter_ip, status)
        VALUES
        (:name, :place_type, :cat_id, :address, :phone, :lat, :lng, :p_min, :p_max, :t_price, :duration, :o_time, :c_time, :note, :ip, "pending")');

    $stmt->execute([
        ':name' => $name,
        ':place_type' => $placeType,
        ':cat_id' => $categoryId,
        ':address' => $address,
        ':phone' => $phone ?: null,
        ':lat' => $lat,
        ':lng' => $lng,
        ':p_min' => $priceMin,
        ':p_max' => $priceMax,
        ':t_price' => $ticketPrice,
        ':duration' => $duration,
        ':o_time' => $openTime,
        ':c_time' => $closeTime,
        ':note' => $note ?: null,
        ':ip' => $ip,
    ]);

    $_SESSION['suggest_timestamps'][] = $now;

    json_ok([
        'id' => (int) $pdo->lastInsertId(),
        'message' => 'Cảm ơn bạn! Đề xuất địa điểm đã được ghi nhận thành công và chuyển tới ban quản trị kiểm duyệt.',
    ]);
});

