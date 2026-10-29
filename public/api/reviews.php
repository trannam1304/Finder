<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: no-store');
    $pdo = db();

    // ── XỬ LÝ GET: LẤY DANH SÁCH ĐÁNH GIÁ ──────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $restoId = filter_input(INPUT_GET, 'restaurant_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$restoId) {
            json_error('INVALID_PARAM', 'restaurant_id không hợp lệ', 400, 'restaurant_id');
        }

        // Lấy danh sách review đã duyệt
        $stmt = $pdo->prepare('
            SELECT id, author_name, rating, comment, created_at
            FROM reviews
            WHERE restaurant_id = :resto_id AND is_approved = 1
            ORDER BY created_at DESC, id DESC
            LIMIT 50
        ');
        $stmt->execute([':resto_id' => $restoId]);
        $rows = $stmt->fetchAll();

        // Tính phân bố số sao (1 -> 5)
        $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $totalRatingSum = 0;
        foreach ($rows as $r) {
            $star = (int) $r['rating'];
            if (isset($dist[$star])) {
                $dist[$star]++;
            }
            $totalRatingSum += $star;
        }

        $count = count($rows);
        $avg = $count > 0 ? round($totalRatingSum / $count, 1) : 0.0;

        // Định dạng thời gian thân thiện (time ago)
        $formattedReviews = array_map(static function (array $item): array {
            $createdTime = strtotime($item['created_at']);
            $diff = time() - $createdTime;
            $timeAgo = 'Vừa xong';
            if ($diff >= 86400 * 30) {
                $timeAgo = date('d/m/Y', $createdTime);
            } elseif ($diff >= 86400) {
                $days = (int) floor($diff / 86400);
                $timeAgo = "{$days} ngày trước";
            } elseif ($diff >= 3600) {
                $hours = (int) floor($diff / 3600);
                $timeAgo = "{$hours} giờ trước";
            } elseif ($diff >= 60) {
                $mins = (int) floor($diff / 60);
                $timeAgo = "{$mins} phút trước";
            }

            return [
                'id' => (int) $item['id'],
                'author_name' => $item['author_name'],
                'rating' => (int) $item['rating'],
                'comment' => $item['comment'] ?? '',
                'created_at' => $item['created_at'],
                'time_ago' => $timeAgo,
            ];
        }, $rows);

        json_ok([
            'summary' => [
                'average_rating' => $avg,
                'total_reviews' => $count,
                'distribution' => $dist,
            ],
            'reviews' => $formattedReviews,
        ]);
        return;
    }

    // ── XỬ LÝ POST: GỬI ĐÁNH GIÁ MỚI ───────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Rate limit: tối đa 5 review / 5 phút trên session
        start_secure_session();
        $now = time();
        $_SESSION['review_timestamps'] = array_values(array_filter(
            $_SESSION['review_timestamps'] ?? [],
            static fn(int $ts): bool => $ts > $now - 300
        ));
        if (count($_SESSION['review_timestamps']) >= 5) {
            json_error('RATE_LIMITED', 'Bạn đã gửi nhiều đánh giá trong thời gian ngắn. Vui lòng chờ ít phút.', 429);
        }

        $restoId = filter_var($input['restaurant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$restoId) {
            json_error('INVALID_PARAM', 'restaurant_id không hợp lệ', 400, 'restaurant_id');
        }

        // Kiểm tra địa điểm có tồn tại
        $checkResto = $pdo->prepare('SELECT id, name FROM restaurants WHERE id = :id');
        $checkResto->execute([':id' => $restoId]);
        if (!$checkResto->fetchColumn()) {
            json_error('NOT_FOUND', 'Địa điểm không tồn tại hoặc đã bị ẩn', 404);
        }

        $authorName = trim((string) ($input['author_name'] ?? ''));
        if (mb_strlen($authorName) < 2 || mb_strlen($authorName) > 80) {
            json_error('INVALID_PARAM', 'Họ tên người đánh giá phải từ 2 đến 80 ký tự', 400, 'author_name');
        }

        $rating = filter_var($input['rating'] ?? 5, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        if (!$rating) {
            json_error('INVALID_PARAM', 'Điểm đánh giá phải từ 1 đến 5 sao', 400, 'rating');
        }

        $comment = trim((string) ($input['comment'] ?? ''));
        if (mb_strlen($comment) > 1000) {
            $comment = mb_substr($comment, 0, 1000);
        }

        // Chèn vào bảng reviews với is_approved = 1
        $ins = $pdo->prepare('
            INSERT INTO reviews (restaurant_id, author_name, rating, comment, is_approved)
            VALUES (:resto_id, :author, :rating, :comment, 1)
        ');
        $ins->execute([
            ':resto_id' => $restoId,
            ':author' => $authorName,
            ':rating' => $rating,
            ':comment' => $comment ?: null,
        ]);
        $reviewId = (int) $pdo->lastInsertId();

        // Tự động tính toán lại rating và rating_count trong bảng restaurants
        $updResto = $pdo->prepare('
            UPDATE restaurants SET
                rating = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE restaurant_id = :id1 AND is_approved = 1),
                rating_count = (SELECT COUNT(*) FROM reviews WHERE restaurant_id = :id2 AND is_approved = 1)
            WHERE id = :id3
        ');
        $updResto->execute([':id1' => $restoId, ':id2' => $restoId, ':id3' => $restoId]);

        // Lấy lại điểm số mới nhất của địa điểm
        $fresh = $pdo->prepare('SELECT rating, rating_count FROM restaurants WHERE id = :id');
        $fresh->execute([':id' => $restoId]);
        $restoStats = $fresh->fetch();

        $_SESSION['review_timestamps'][] = $now;

        json_ok([
            'message' => 'Cảm ơn bạn! Đánh giá đã được gửi và cập nhật thành công.',
            'review' => [
                'id' => $reviewId,
                'author_name' => $authorName,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => date('Y-m-d H:i:s'),
                'time_ago' => 'Vừa xong',
            ],
            'restaurant' => [
                'rating' => (float) ($restoStats['rating'] ?? 5.0),
                'rating_count' => (int) ($restoStats['rating_count'] ?? 1),
            ],
        ]);
        return;
    }

    json_error('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ', 405);
});

