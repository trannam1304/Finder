<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');
    $pdo = db();
    $user = auth_get_current_user($pdo);

    // ── XỬ LÝ GET: LẤY DANH SÁCH LỘ TRÌNH ĐÃ LƯU CỦA USER ──
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!$user) {
            json_ok([
                'guest' => true,
                'list' => [],
                'count' => 0,
            ]);
        }

        $userId = (int) $user['id'];
        $stmt = $pdo->prepare('
            SELECT id, itinerary_uid, title, duration_type, vibe, stops_count, total_distance_km, data_json, created_at
            FROM user_saved_itineraries
            WHERE user_id = :uid
            ORDER BY created_at DESC
        ');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $list = [];
        foreach ($rows as $r) {
            $data = json_decode($r['data_json'], true) ?: [];
            $data['id'] = $r['itinerary_uid'];
            $data['db_id'] = (int) $r['id'];
            $data['saved_at'] = $r['created_at'];
            $list[] = $data;
        }

        json_ok([
            'guest' => false,
            'user_id' => $userId,
            'list' => $list,
            'count' => count($list),
        ]);
    }

    // ── XỬ LÝ POST: LƯU LỘ TRÌNH MỚI ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $input = $decoded;
            }
        }

        $itinerary = $input['itinerary'] ?? $input;
        $itinUid = trim((string) ($itinerary['id'] ?? ''));
        if (!$itinUid) {
            $itinUid = 'itin_' . time() . '_' . bin2hex(random_bytes(4));
            $itinerary['id'] = $itinUid;
        }

        if (!$user) {
            // Khách: Trả về trạng thái lưu tạm thời
            json_ok([
                'guest' => true,
                'itinerary' => $itinerary,
                'message' => 'Lưu tạm thời (sẽ bị xóa khi tải lại trang nếu chưa đăng nhập)',
            ]);
        }

        $userId = (int) $user['id'];
        $title = trim((string) ($itinerary['title'] ?? 'Lộ trình khám phá'));
        $duration = (string) ($itinerary['summary']['duration'] ?? $itinerary['duration'] ?? 'half_day');
        $vibe = (string) ($itinerary['summary']['vibe'] ?? $itinerary['vibe'] ?? 'chill');
        $stopsCount = (int) count($itinerary['stops'] ?? []);
        $distance = (float) ($itinerary['summary']['total_distance_km'] ?? $itinerary['total_distance_km'] ?? 0);
        $dataJson = json_encode($itinerary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare('
            INSERT INTO user_saved_itineraries (user_id, itinerary_uid, title, duration_type, vibe, stops_count, total_distance_km, data_json)
            VALUES (:uid, :uid_str, :title, :duration, :vibe, :stops, :dist, :json)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                duration_type = VALUES(duration_type),
                vibe = VALUES(vibe),
                stops_count = VALUES(stops_count),
                total_distance_km = VALUES(total_distance_km),
                data_json = VALUES(data_json),
                created_at = NOW()
        ');
        $stmt->execute([
            ':uid' => $userId,
            ':uid_str' => $itinUid,
            ':title' => $title,
            ':duration' => $duration,
            ':vibe' => $vibe,
            ':stops' => $stopsCount,
            ':dist' => $distance,
            ':json' => $dataJson,
        ]);

        json_ok([
            'guest' => false,
            'user_id' => $userId,
            'itinerary_uid' => $itinUid,
            'saved' => true,
        ]);
    }

    // ── XỬ LÝ DELETE: XÓA LỘ TRÌNH ĐÃ LƯU ──
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!$user) {
            json_ok(['guest' => true, 'deleted' => true]);
        }

        $userId = (int) $user['id'];
        $itinUid = trim((string) ($_GET['id'] ?? ''));

        if ($itinUid) {
            $pdo->prepare('DELETE FROM user_saved_itineraries WHERE user_id = :uid AND itinerary_uid = :id')
                ->execute([':uid' => $userId, ':id' => $itinUid]);
        } else {
            // Xóa tất cả lộ trình của user
            $pdo->prepare('DELETE FROM user_saved_itineraries WHERE user_id = :uid')->execute([':uid' => $userId]);
        }

        json_ok(['guest' => false, 'deleted' => true]);
    }

    json_error('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ', 405);
});

