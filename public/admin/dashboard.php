<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$pdo = db();

// Thống kê số liệu KPI tổng quan
$totalRestaurants = (int) $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
$activeRestaurants = (int) $pdo->query('SELECT COUNT(*) FROM restaurants WHERE is_active = 1')->fetchColumn();
$totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$avgRating = (float) ($pdo->query('SELECT AVG(rating) FROM restaurants WHERE is_active = 1')->fetchColumn() ?: 0.0);
$totalReviews = (int) ($pdo->query('SELECT SUM(rating_count) FROM restaurants')->fetchColumn() ?: 0);

// Thống kê 3 nhóm địa điểm (Ẩm thực, Du lịch, Vui chơi)
$typeCounts = ['food' => 0, 'tourism' => 0, 'entertainment' => 0];
try {
    $typeRows = $pdo->query('SELECT place_type, COUNT(*) AS cnt FROM restaurants GROUP BY place_type')->fetchAll();
    foreach ($typeRows as $tr) {
        $pt = (string) ($tr['place_type'] ?? 'food');
        if (isset($typeCounts[$pt])) {
            $typeCounts[$pt] = (int) $tr['cnt'];
        }
    }
} catch (Throwable $e) {
    $typeCounts['food'] = $totalRestaurants;
}

// Thống kê lượt tạo lịch trình thông minh (itinerary_logs)
$totalItineraries = 0;
$itineraryByVibe = ['chill' => 0, 'adventure' => 0, 'foodie' => 0, 'date' => 0];
$itineraryByDuration = ['half_day' => 0, 'full_day' => 0, 'evening' => 0];
try {
    $totalItineraries = (int) $pdo->query('SELECT COUNT(*) FROM itinerary_logs')->fetchColumn();
    $vibeRows = $pdo->query('SELECT vibe, COUNT(*) AS cnt FROM itinerary_logs GROUP BY vibe')->fetchAll();
    foreach ($vibeRows as $vr) {
        $v = (string) ($vr['vibe'] ?? '');
        if (isset($itineraryByVibe[$v])) {
            $itineraryByVibe[$v] = (int) $vr['cnt'];
        }
    }
    $durRows = $pdo->query('SELECT duration_type, COUNT(*) AS cnt FROM itinerary_logs GROUP BY duration_type')->fetchAll();
    foreach ($durRows as $dr) {
        $d = (string) ($dr['duration_type'] ?? '');
        if (isset($itineraryByDuration[$d])) {
            $itineraryByDuration[$d] = (int) $dr['cnt'];
        }
    }
} catch (Throwable $e) {
    // Bảng itinerary_logs có thể chưa tồn tại trên môi trường cũ
}

// Thống kê phân bố theo danh mục
$catDistribution = $pdo->query('
    SELECT c.id, c.name, c.slug, c.icon, c.type_group, COUNT(r.id) AS resto_count
    FROM categories c
    LEFT JOIN restaurants r ON r.category_id = c.id
    GROUP BY c.id
    ORDER BY resto_count DESC, c.sort_order ASC
')->fetchAll();

// Top 5 địa điểm đánh giá cao nhất
$topRated = $pdo->query('
    SELECT r.id, r.name, r.place_type, r.address, r.rating, r.rating_count, r.image_url, c.name AS category_name
    FROM restaurants r
    JOIN categories c ON c.id = r.category_id
    WHERE r.is_active = 1
    ORDER BY r.rating DESC, r.rating_count DESC
    LIMIT 5
')->fetchAll();

// 5 địa điểm mới cập nhật gần đây
$recentlyUpdated = $pdo->query('
    SELECT r.id, r.name, r.place_type, r.address, r.updated_at, r.is_active, c.name AS category_name
    FROM restaurants r
    JOIN categories c ON c.id = r.category_id
    ORDER BY r.updated_at DESC
    LIMIT 5
')->fetchAll();

admin_header('Bảng điều khiển');
?>

<div class="admin-heading">
  <div>
    <h1>Bảng điều khiển tổng quan</h1>
    <p class="admin-note">Thống kê đa địa điểm (Ẩm thực, Du lịch, Vui chơi) &amp; Lịch trình thông minh Finder</p>
  </div>
  <div style="display: flex; gap: var(--sp-2);">
    <a href="restaurant_form.php" class="btn btn--primary">+ Thêm địa điểm</a>
    <a href="categories.php" class="btn btn--ghost">Quản lý danh mục</a>
  </div>
</div>

<!-- KPI Cards Grid: 3 Nhóm Địa Điểm + Lượt Lịch Trình + Tổng Quan -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
  
  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.15rem; display: flex; flex-direction: column;">
    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--c-ink-soft, #65706a); font-weight: 800; letter-spacing: .05em;">Tổng số địa điểm</span>
    <div style="font-size: 2.1rem; font-weight: 900; color: var(--c-ink, #181818); margin: 0.25rem 0;">
      <?= number_format($totalRestaurants) ?>
    </div>
    <small style="color: var(--c-ink-soft, #65706a);"><?= $activeRestaurants ?> điểm đang hoạt động &bull; <?= $totalCategories ?> danh mục</small>
  </div>

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); border-top: 5px solid #ff4d2e; box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.15rem; display: flex; flex-direction: column;">
    <span style="font-size: 0.78rem; text-transform: uppercase; color: #ff4d2e; font-weight: 800; letter-spacing: .05em;">🍜 Nhóm Ẩm thực</span>
    <div style="font-size: 2.1rem; font-weight: 900; color: #ff4d2e; margin: 0.25rem 0;">
      <?= number_format($typeCounts['food']) ?>
    </div>
    <small style="color: var(--c-ink-soft, #65706a);">Quán ăn, Cà phê, Ăn vặt</small>
  </div>

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); border-top: 5px solid #0284c7; box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.15rem; display: flex; flex-direction: column;">
    <span style="font-size: 0.78rem; text-transform: uppercase; color: #0284c7; font-weight: 800; letter-spacing: .05em;">🏛️ Nhóm Du lịch</span>
    <div style="font-size: 2.1rem; font-weight: 900; color: #0284c7; margin: 0.25rem 0;">
      <?= number_format($typeCounts['tourism']) ?>
    </div>
    <small style="color: var(--c-ink-soft, #65706a);">Bảo tàng, Di tích, Công viên</small>
  </div>

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); border-top: 5px solid #f59e0b; box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.15rem; display: flex; flex-direction: column;">
    <span style="font-size: 0.78rem; text-transform: uppercase; color: #d97706; font-weight: 800; letter-spacing: .05em;">🎡 Nhóm Vui chơi</span>
    <div style="font-size: 2.1rem; font-weight: 900; color: #d97706; margin: 0.25rem 0;">
      <?= number_format($typeCounts['entertainment']) ?>
    </div>
    <small style="color: var(--c-ink-soft, #65706a);">Rạp phim, Vận động, TTTM</small>
  </div>

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); border-top: 5px solid #14705a; box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.15rem; display: flex; flex-direction: column;">
    <span style="font-size: 0.78rem; text-transform: uppercase; color: #14705a; font-weight: 800; letter-spacing: .05em;">✨ Lượt tạo lịch trình</span>
    <div style="font-size: 2.1rem; font-weight: 900; color: #14705a; margin: 0.25rem 0;">
      <?= number_format($totalItineraries) ?>
    </div>
    <small style="color: var(--c-ink-soft, #65706a);">Đánh giá TB: <?= number_format($avgRating, 1) ?>★ (<?= number_format($totalReviews) ?> lượt)</small>
  </div>

</div>

<!-- Chart.js Section: Tỷ trọng 3 nhóm địa điểm & Xu hướng tạo lịch trình -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.05rem; display: flex; justify-content: space-between; align-items: center;">
      <span>Biểu đồ cơ cấu 3 nhóm địa điểm</span>
      <a href="index.php" style="font-size: 0.8rem; color: var(--c-primary, #d22b1f); text-decoration: none; font-weight: 700;">Quản lý &rarr;</a>
    </h3>
    <div style="position: relative; height: 240px; display: flex; align-items: center; justify-content: center;">
      <canvas id="placeTypeChart"></canvas>
    </div>
  </div>

  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.05rem; display: flex; justify-content: space-between; align-items: center;">
      <span>Thống kê lịch trình theo Phong cách (Vibe)</span>
      <span style="font-size: 0.78rem; font-weight: 700; color: var(--c-ink-soft, #65706a);"><?= number_format($totalItineraries) ?> lượt tạo</span>
    </h3>
    <div style="position: relative; height: 240px;">
      <canvas id="itineraryVibeChart"></canvas>
    </div>
  </div>

</div>

<!-- 2-Column Section: Distribution & Top Rated -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">

  <!-- Phân bố theo danh mục -->
  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center;">
      <span>Phân bố địa điểm theo 15 danh mục</span>
      <a href="categories.php" style="font-size: 0.8rem; color: var(--c-primary, #d22b1f); text-decoration: none; font-weight: 700;">Chi tiết &rarr;</a>
    </h3>

    <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 360px; overflow-y: auto; padding-right: 4px;">
      <?php foreach ($catDistribution as $cat): 
        $pct = $totalRestaurants > 0 ? round(((int)$cat['resto_count'] / $totalRestaurants) * 100) : 0;
        $tGroup = $cat['type_group'] ?? 'food';
        $barColor = $tGroup === 'tourism' ? '#0284c7' : ($tGroup === 'entertainment' ? '#f59e0b' : '#ff4d2e');
      ?>
        <div>
          <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 3px;">
            <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
              <?= admin_category_icon($cat['slug'] ?? $cat['icon']) ?> <?= h($cat['name']) ?>
            </span>
            <span style="color: var(--c-ink-soft, #65706a);"><?= (int)$cat['resto_count'] ?> điểm (<?= $pct ?>%)</span>
          </div>
          <div style="height: 8px; background: var(--c-bg, #f7f5ef); border-radius: 999px; overflow: hidden;">
            <div style="height: 100%; width: <?= $pct ?>%; background: <?= $barColor ?>; border-radius: 999px;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top 5 địa điểm đánh giá cao -->
  <div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem;">Top địa điểm đánh giá cao</h3>
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
      <?php foreach ($topRated as $i => $item): ?>
        <div style="display: flex; align-items: center; gap: 0.75rem; padding-bottom: 0.65rem; border-bottom: 1px solid var(--c-border, #eee);">
          <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $i === 0 ? 'var(--c-accent, #ffd400)' : 'var(--c-bg, #f7f5ef)' ?>; color: #000; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <?= $i + 1 ?>
          </div>
          <?php if ($item['image_url']): ?>
            <img src="../<?= h($item['image_url']) ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover; flex-shrink: 0;" alt="">
          <?php endif; ?>
          <div style="flex: 1; min-width: 0;">
            <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 6px;">
              <a href="restaurant_form.php?id=<?= (int)$item['id'] ?>" style="color: inherit; text-decoration: none;"><?= h($item['name']) ?></a>
            </div>
            <small style="color: var(--c-ink-soft, #65706a);"><?= h($item['category_name']) ?> &bull; <?= h($item['address']) ?></small>
          </div>
          <?= admin_place_type_badge($item['place_type'] ?? 'food') ?>
          <div style="display: flex; align-items: center; gap: 4px; font-weight: 700; color: var(--c-ink, #18231e); font-size: 0.85rem;">
            <?= admin_svg_icon('star') ?> <?= h((string)$item['rating']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Recent Updates Table -->
<div style="background: var(--c-surface, #fff); border: 2px solid var(--c-border, #181818); box-shadow: 3px 3px 0 var(--c-border, #181818); padding: 1.25rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3 style="margin: 0; font-size: 1.1rem;">Địa điểm cập nhật gần nhất</h3>
    <a href="index.php" style="font-size: 0.85rem; color: var(--c-primary, #d22b1f); text-decoration: none; font-weight: 600;">Xem tất cả địa điểm &rarr;</a>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Tên địa điểm</th>
          <th>Loại hình</th>
          <th>Danh mục</th>
          <th>Địa chỉ</th>
          <th>Trạng thái</th>
          <th>Thời gian cập nhật</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentlyUpdated as $row): ?>
          <tr>
            <td><strong><?= h($row['name']) ?></strong></td>
            <td><?= admin_place_type_badge($row['place_type'] ?? 'food') ?></td>
            <td><?= h($row['category_name']) ?></td>
            <td><small><?= h($row['address']) ?></small></td>
            <td>
              <span class="badge" style="padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; background: <?= (int)$row['is_active'] ? '#eaf7ef' : '#fff0ed' ?>; color: <?= (int)$row['is_active'] ? '#145a32' : '#9c271e' ?>;">
                <?= (int)$row['is_active'] ? 'Hoạt động' : 'Đã ẩn' ?>
              </span>
            </td>
            <td><small><?= date('H:i d/m/Y', strtotime((string)$row['updated_at'])) ?></small></td>
            <td>
              <a href="restaurant_form.php?id=<?= (int)$row['id'] ?>" class="btn btn--ghost btn--sm">Chỉnh sửa</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.Chart) return;
  const ctxType = document.getElementById('placeTypeChart');
  if (ctxType) {
    new Chart(ctxType, {
      type: 'doughnut',
      data: {
        labels: ['🍜 Ẩm thực', '🏛️ Du lịch', '🎡 Vui chơi giải trí'],
        datasets: [{
          data: [<?= (int)$typeCounts['food'] ?>, <?= (int)$typeCounts['tourism'] ?>, <?= (int)$typeCounts['entertainment'] ?>],
          backgroundColor: ['#ff4d2e', '#0284c7', '#f59e0b'],
          borderColor: '#181818',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { font: { family: 'Be Vietnam Pro', weight: 'bold' } } }
        }
      }
    });
  }

  const ctxVibe = document.getElementById('itineraryVibeChart');
  if (ctxVibe) {
    new Chart(ctxVibe, {
      type: 'bar',
      data: {
        labels: ['🌿 Chill thư thái', '⚡ Năng động (Adventure)', '🍜 Ăn sập (Foodie)', '💖 Hẹn hò (Date)'],
        datasets: [{
          label: 'Lượt tạo lịch trình',
          data: [
            <?= (int)$itineraryByVibe['chill'] ?>,
            <?= (int)$itineraryByVibe['adventure'] ?>,
            <?= (int)$itineraryByVibe['foodie'] ?>,
            <?= (int)$itineraryByVibe['date'] ?>
          ],
          backgroundColor: ['#0284c7', '#d97706', '#dc2626', '#ec4899'],
          borderColor: '#181818',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  }
});
</script>

<?php admin_footer(); ?>
