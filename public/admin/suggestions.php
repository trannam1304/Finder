<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$pdo = db();

// Xử lý các thao tác POST: Duyệt, Từ chối, Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

    if ($id > 0) {
        if ($action === 'approve') {
            // Lấy thông tin đề xuất
            $stmt = $pdo->prepare('SELECT * FROM place_suggestions WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $sug = $stmt->fetch();

            if ($sug && $sug['status'] !== 'approved') {
                // Xác định price_level
                $pMax = (int) $sug['price_max'];
                $priceLevel = 1;
                if ($pMax > 200000) {
                    $priceLevel = 3;
                } elseif ($pMax > 50000) {
                    $priceLevel = 2;
                }

                // Ảnh bìa mặc định theo loại hình
                $defaultImg = match ($sug['place_type']) {
                    'tourism' => 'assets/img/tourism_cover.jpg',
                    'entertainment' => 'assets/img/entertainment_cover.jpg',
                    default => 'assets/img/highlands.jpg',
                };

                // Thêm vào bảng restaurants
                $insResto = $pdo->prepare('INSERT INTO restaurants
                    (category_id, place_type, name, address, phone, latitude, longitude,
                     price_min, price_max, ticket_price, estimated_duration, best_time_slot,
                     price_level, rating, rating_count, image_url, open_time, close_time,
                     is_24h, is_active, note)
                    VALUES
                    (:cat_id, :place_type, :name, :address, :phone, :lat, :lng,
                     :p_min, :p_max, :t_price, :duration, "any",
                     :p_level, 5.0, 1, :img, :o_time, :c_time,
                     0, 1, :note)');

                $insResto->execute([
                    ':cat_id' => (int) $sug['category_id'],
                    ':place_type' => $sug['place_type'],
                    ':name' => $sug['name'],
                    ':address' => $sug['address'],
                    ':phone' => $sug['phone'],
                    ':lat' => (float) $sug['latitude'],
                    ':lng' => (float) $sug['longitude'],
                    ':p_min' => (int) $sug['price_min'],
                    ':p_max' => (int) $sug['price_max'],
                    ':t_price' => (int) $sug['ticket_price'],
                    ':duration' => (int) $sug['estimated_duration'],
                    ':p_level' => $priceLevel,
                    ':img' => $defaultImg,
                    ':o_time' => $sug['open_time'],
                    ':c_time' => $sug['close_time'],
                    ':note' => $sug['note'],
                ]);

                // Cập nhật trạng thái đề xuất thành approved
                $updSug = $pdo->prepare('UPDATE place_suggestions SET status = "approved", reviewed_at = NOW() WHERE id = :id');
                $updSug->execute([':id' => $id]);

                admin_flash('Đã duyệt và xuất bản địa điểm "' . htmlspecialchars($sug['name'], ENT_QUOTES, 'UTF-8') . '" lên bản đồ thành công!');
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare('UPDATE place_suggestions SET status = "rejected", reviewed_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $id]);
            admin_flash('Đã từ chối đề xuất #' . $id . '.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM place_suggestions WHERE id = :id');
            $stmt->execute([':id' => $id]);
            admin_flash('Đã xóa bản ghi đề xuất #' . $id . '.');
        }
    }

    header('Location: suggestions.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Bộ lọc trạng thái
$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

$whereClause = '';
$params = [];
if ($statusFilter !== 'all') {
    $whereClause = 'WHERE ps.status = :status';
    $params[':status'] = $statusFilter;
}

// Truy vấn danh sách đề xuất
$sql = "SELECT ps.*, c.name AS category_name, c.icon AS category_icon, c.type_group
        FROM place_suggestions ps
        LEFT JOIN categories c ON c.id = ps.category_id
        $whereClause
        ORDER BY ps.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suggestions = $stmt->fetchAll();

// Thống kê số lượng theo trạng thái
$countPending = (int) $pdo->query('SELECT COUNT(*) FROM place_suggestions WHERE status = "pending"')->fetchColumn();
$countApproved = (int) $pdo->query('SELECT COUNT(*) FROM place_suggestions WHERE status = "approved"')->fetchColumn();
$countRejected = (int) $pdo->query('SELECT COUNT(*) FROM place_suggestions WHERE status = "rejected"')->fetchColumn();
$countTotal = $countPending + $countApproved + $countRejected;

admin_header('Duyệt đề xuất địa điểm');
?>

<div class="admin-heading">
  <div>
    <h1>Duyệt địa điểm người dùng đóng góp</h1>
    <p class="admin-note">Xem xét, kiểm duyệt và xuất bản các địa điểm do cộng đồng gửi về</p>
  </div>
  <div style="display: flex; gap: var(--sp-2);">
    <a href="dashboard.php" class="btn btn--ghost">&larr; Bảng điều khiển</a>
  </div>
</div>

<!-- Thanh Tabs bộ lọc trạng thái -->
<div style="display: flex; gap: var(--sp-2); margin-bottom: 1.25rem; flex-wrap: wrap;">
  <a href="suggestions.php?status=all" class="btn btn--sm <?= $statusFilter === 'all' ? 'btn--primary' : 'btn--ghost' ?>">
    Tất cả (<?= $countTotal ?>)
  </a>
  <a href="suggestions.php?status=pending" class="btn btn--sm <?= $statusFilter === 'pending' ? 'btn--primary' : 'btn--ghost' ?>" style="<?= $countPending > 0 ? 'background:#e11d48;color:#fff;border-color:#181818;' : '' ?>">
    ⏳ Chờ duyệt (<?= $countPending ?>)
  </a>
  <a href="suggestions.php?status=approved" class="btn btn--sm <?= $statusFilter === 'approved' ? 'btn--primary' : 'btn--ghost' ?>">
    ✅ Đã duyệt (<?= $countApproved ?>)
  </a>
  <a href="suggestions.php?status=rejected" class="btn btn--sm <?= $statusFilter === 'rejected' ? 'btn--primary' : 'btn--ghost' ?>">
    ❌ Từ chối (<?= $countRejected ?>)
  </a>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width: 50px;">ID</th>
        <th>Tên địa điểm</th>
        <th>Phân loại & Danh mục</th>
        <th>Địa chỉ & Liên hệ</th>
        <th>Chi phí / Giờ mở</th>
        <th>Thời gian gửi</th>
        <th>Trạng thái</th>
        <th style="text-align: right;">Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($suggestions)): ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--c-ink-soft);">
            Không có đề xuất địa điểm nào trong mục này.
          </td>
        </tr>
      <?php endif; ?>

      <?php foreach ($suggestions as $item): ?>
        <?php
          $typeColor = match ($item['place_type']) {
              'tourism' => '#0284c7',
              'entertainment' => '#d97706',
              default => 'var(--c-primary, #e11d48)',
          };
          $typeLabel = match ($item['place_type']) {
              'tourism' => '🏛️ Du lịch',
              'entertainment' => '🎡 Vui chơi',
              default => '🍜 Ẩm thực',
          };
          $statusBadge = match ($item['status']) {
              'approved' => '<span class="status-badge" style="background:#dcfce7;color:#166534;border:1px solid #166534;">✅ Đã xuất bản</span>',
              'rejected' => '<span class="status-badge" style="background:#fee2e2;color:#991b1b;border:1px solid #991b1b;">❌ Đã từ chối</span>',
              default => '<span class="status-badge" style="background:#fef3c7;color:#92400e;border:1px solid #92400e;">⏳ Chờ duyệt</span>',
          };
        ?>
        <tr>
          <td><strong>#<?= (int) $item['id'] ?></strong></td>
          <td>
            <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 2px;">
              <?= h($item['name']) ?>
            </div>
            <?php if (!empty($item['note'])): ?>
              <div style="font-size: 0.8rem; color: var(--c-ink-soft); max-width: 280px; font-style: italic;">
                "<?= h($item['note']) ?>"
              </div>
            <?php endif; ?>
          </td>
          <td>
            <span style="display:inline-block; font-size:0.75rem; font-weight:700; padding:2px 6px; border:1px solid #181818; background:<?= $typeColor ?>; color:#fff; border-radius:3px; margin-bottom:3px;">
              <?= $typeLabel ?>
            </span>
            <div style="font-size: 0.85rem; color: var(--c-ink-soft);">
              <?= h($item['category_name'] ?? 'Chưa rõ') ?>
            </div>
          </td>
          <td>
            <div style="font-size: 0.85rem; max-width: 250px;">
              📍 <?= h($item['address']) ?>
            </div>
            <?php if (!empty($item['phone'])): ?>
              <div style="font-size: 0.8rem; color: var(--c-ink-soft);">
                ☎ <?= h($item['phone']) ?>
              </div>
            <?php endif; ?>
            <div style="font-size: 0.75rem; color: var(--c-ink-faint); margin-top: 2px;">
              GPS: <?= round((float)$item['latitude'], 4) ?>, <?= round((float)$item['longitude'], 4) ?>
            </div>
          </td>
          <td style="font-size: 0.85rem;">
            <?php if ($item['place_type'] !== 'food' && $item['ticket_price'] > 0): ?>
              <div>Vé: <strong><?= number_format((int)$item['ticket_price']) ?>đ</strong></div>
            <?php elseif ($item['price_max'] > 0): ?>
              <div><?= number_format((int)$item['price_min']) ?>đ - <?= number_format((int)$item['price_max']) ?>đ</div>
            <?php else: ?>
              <div style="color:var(--c-ink-soft)">Vào cổng tự do</div>
            <?php endif; ?>

            <?php if (!empty($item['open_time']) && !empty($item['close_time'])): ?>
              <div style="font-size: 0.75rem; color: var(--c-ink-soft); margin-top: 2px;">
                ⏰ <?= substr($item['open_time'], 0, 5) ?> - <?= substr($item['close_time'], 0, 5) ?>
              </div>
            <?php endif; ?>
          </td>
          <td style="font-size: 0.8rem; color: var(--c-ink-soft);">
            <?= date('H:i d/m/Y', strtotime($item['created_at'])) ?>
          </td>
          <td>
            <?= $statusBadge ?>
          </td>
          <td style="text-align: right; white-space: nowrap;">
            <div style="display: inline-flex; gap: 4px;">
              <?php if ($item['status'] !== 'approved'): ?>
                <!-- Nút Duyệt -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn duyệt và xuất bản địa điểm này lên hệ thống?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn btn--sm" style="background:#16a34a;color:#fff;border-color:#181818;" title="Duyệt & Xuất bản">
                    Duyệt
                  </button>
                </form>
              <?php endif; ?>

              <?php if ($item['status'] === 'pending'): ?>
                <!-- Nút Từ chối -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Từ chối đề xuất này?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn btn--ghost btn--sm" title="Từ chối đề xuất">
                    Từ chối
                  </button>
                </form>
              <?php endif; ?>

              <!-- Nút Xóa -->
              <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa vĩnh viễn đề xuất này?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn--ghost btn--sm" style="color:#e11d48;" title="Xóa đề xuất">
                  Xóa
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php admin_footer(); ?>

