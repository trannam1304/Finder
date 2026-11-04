<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');
    $pdo = db();
    $user = auth_get_current_user($pdo);

    // ── XỬ LÝ GET: LẤY DANH SÁCH ĐỊA ĐIỂM YÊU THÍCH ──
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!$user) {
            // Khách chưa đăng nhập: Trả về mảng rỗng
            json_ok([
                'guest' => true,
                'ids' => [],
                'restaurants' => [],
                'count' => 0,
            ]);
        }

        $userId = (int) $user['id'];

        $stmt = $pdo->prepare('
            SELECT r.id, r.name, r.address, r.phone, r.latitude, r.longitude,
                   r.price_min, r.price_max, r.ticket_price, r.estimated_duration,
                   r.best_time_slot, r.price_level, r.rating, r.rating_count,
                   r.image_url, r.open_time, r.close_time, r.is_24h, r.is_active, r.note,
                   r.place_type,
                   c.id AS category_id, c.name AS category_name, c.slug AS category_slug,
                   c.icon AS category_icon, c.type_group,
                   f.created_at AS favorited_at
            FROM user_favorites f
            JOIN restaurants r ON r.id = f.restaurant_id
            JOIN categories c ON c.id = r.category_id
            WHERE f.user_id = :uid AND r.is_active = 1
            ORDER BY f.created_at DESC
        ');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $ids = [];
        $items = [];
        foreach ($rows as $row) {
            $rId = (int) $row['id'];
            $ids[] = $rId;
            $items[] = [
                'id' => $rId,
                'name' => $row['name'],
                'address' => $row['address'],
                'phone' => $row['phone'],
                'lat' => (float) $row['latitude'],
                'lng' => (float) $row['longitude'],
                'price_min' => (int) $row['price_min'],
                'price_max' => (int) $row['price_max'],
                'ticket_price' => (int) $row['ticket_price'],
                'estimated_duration' => (int) $row['estimated_duration'],
                'best_time_slot' => $row['best_time_slot'],
                'price_level' => (int) $row['price_level'],
                'rating' => (float) $row['rating'],
                'rating_count' => (int) $row['rating_count'],
                'image_url' => $row['image_url'],
                'open_time' => $row['open_time'] ? substr((string) $row['open_time'], 0, 5) : null,
                'close_time' => $row['close_time'] ? substr((string) $row['close_time'], 0, 5) : null,
                'is_24h' => (bool) $row['is_24h'],
                'place_type' => $row['place_type'],
                'favorited_at' => $row['favorited_at'],
                'category' => [
                    'id' => (int) $row['category_id'],
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                    'icon' => $row['category_icon'],
                    'type_group' => $row['type_group'],
                ],
            ];
        }

        json_ok([
            'guest' => false,
            'user_id' => $userId,
            'ids' => $ids,
            'restaurants' => $items,
            'count' => count($ids),
        ]);
    }

    // ── XỬ LÝ POST: THÊM / BẬT TẮT YÊU THÍCH ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $input = $decoded;
            }
        }

        $restaurantId = filter_var($input['restaurant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$restaurantId) {
            json_error('INVALID_PARAM', 'restaurant_id không hợp lệ', 400, 'restaurant_id');
        }

        // Nếu là khách chưa đăng nhập: Trả về trạng thái guest
        if (!$user) {
            json_ok([
                'guest' => true,
                'saved' => (bool) ($input['saved'] ?? true),
                'message' => 'Lưu tạm thời (sẽ bị xóa khi tải lại trang nếu chưa đăng nhập)',
            ]);
        }

        $userId = (int) $user['id'];

        // Kiểm tra xem đã lưu chưa
        $stmtCheck = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = :uid AND restaurant_id = :rid LIMIT 1');
        $stmtCheck->execute([':uid' => $userId, ':rid' => $restaurantId]);
        $favId = $stmtCheck->fetchColumn();

        $action = $input['action'] ?? 'toggle';
        $saved = false;

        if ($favId && ($action === 'toggle' || $action === 'remove')) {
            // Xóa khỏi danh sách yêu thích
            $pdo->prepare('DELETE FROM user_favorites WHERE id = :id')->execute([':id' => $favId]);
            $saved = false;
        } else {
            // Thêm vào danh sách yêu thích
            $pdo->prepare('INSERT IGNORE INTO user_favorites (user_id, restaurant_id) VALUES (:uid, :rid)')
                ->execute([':uid' => $userId, ':rid' => $restaurantId]);
            $saved = true;
        }

        // Lấy lại danh sách ID mới nhất của user
        $stmtIds = $pdo->prepare('SELECT restaurant_id FROM user_favorites WHERE user_id = :uid ORDER BY created_at DESC');
        $stmtIds->execute([':uid' => $userId]);
        $currentIds = array_map('intval', $stmtIds->fetchAll(PDO::FETCH_COLUMN));

        json_ok([
            'guest' => false,
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'saved' => $saved,
            'ids' => $currentIds,
            'count' => count($currentIds),
        ]);
    }

    // ── XỬ LÝ DELETE: XÓA TẤT CẢ HOẶC XÓA 1 MỤC ──
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!$user) {
            json_ok(['guest' => true, 'deleted' => true, 'ids' => [], 'count' => 0]);
        }

        $userId = (int) $user['id'];
        $restaurantId = filter_var($_GET['restaurant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($restaurantId) {
            $pdo->prepare('DELETE FROM user_favorites WHERE user_id = :uid AND restaurant_id = :rid')
                ->execute([':uid' => $userId, ':rid' => $restaurantId]);
        } else {
            // Xóa tất cả yêu thích của user
            $pdo->prepare('DELETE FROM user_favorites WHERE user_id = :uid')->execute([':uid' => $userId]);
        }

        json_ok(['guest' => false, 'deleted' => true, 'ids' => [], 'count' => 0]);
    }

    json_error('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ', 405);
});

