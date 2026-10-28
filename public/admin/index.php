<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php'; require_admin();
$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
if (!in_array($type, ['food', 'tourism', 'entertainment'], true)) $type = '';
$category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$where = []; $params = [];
if ($q !== '') { $where[] = '(r.name LIKE :q OR r.address LIKE :q)'; $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%'; }
if ($type !== '') { $where[] = 'r.place_type = :type'; $params[':type'] = $type; }
if ($category) { $where[] = 'r.category_id = :category'; $params[':category'] = $category; }
$condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$count = $pdo->prepare('SELECT COUNT(*) FROM restaurants r' . $condition); $count->execute($params); $total = (int) $count->fetchColumn();
$stmt = $pdo->prepare('SELECT r.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon FROM restaurants r JOIN categories c ON c.id = r.category_id' . $condition . ' ORDER BY r.updated_at DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$categories = $pdo->query('SELECT id, name, type_group FROM categories ORDER BY sort_order, name')->fetchAll();
admin_header('Địa điểm'); ?>
<div class="admin-heading"><div><h1>Quản lý địa điểm</h1><p class="admin-note"><?= $total ?> địa điểm trong hệ thống (Ẩm thực, Du lịch, Vui chơi)</p></div><a href="restaurant_form.php" class="btn btn--primary">+ Thêm địa điểm</a></div>
<form class="admin-toolbar" method="get">
  <input class="admin-input" name="q" value="<?= h($q) ?>" placeholder="Tìm tên hoặc địa chỉ">
  <select class="admin-select" name="type">
    <option value="">Tất cả loại hình</option>
    <option value="food" <?= $type === 'food' ? 'selected' : '' ?>>🍜 Ẩm thực</option>
    <option value="tourism" <?= $type === 'tourism' ? 'selected' : '' ?>>🏛️ Du lịch</option>
    <option value="entertainment" <?= $type === 'entertainment' ? 'selected' : '' ?>>🎡 Vui chơi giải trí</option>
  </select>
  <select class="admin-select" name="category">
    <option value="">Tất cả danh mục</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= (int) $cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn--ghost" type="submit">Lọc</button>
</form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Địa điểm</th><th>Loại hình</th><th>Danh mục</th><th>Đánh giá</th><th>Giá / Vé</th><th>Thời lượng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach ($rows as $row):
  $pType = $row['place_type'] ?? 'food';
  $ticket = (int)($row['ticket_price'] ?? 0);
  $pMin = (int)$row['price_min'];
  $pMax = (int)$row['price_max'];
  if ($pType !== 'food' && $ticket === 0 && $pMin === 0 && $pMax === 0) {
      $priceDisplay = '<span style="color:var(--c-open,#0f766e);font-weight:700">Vé miễn phí</span>';
  } elseif ($pType !== 'food' && $ticket > 0) {
      $priceDisplay = 'Vé ' . number_format($ticket / 1000, 0) . 'k';
  } else {
      $priceDisplay = number_format($pMin / 1000, 0) . '–' . number_format($pMax / 1000, 0) . 'k';
  }
?><tr><td><?php if ($row['image_url']): ?><img class="admin-thumb" src="../<?= h($row['image_url']) ?>" alt=""><?php endif; ?><strong><?= h($row['name']) ?></strong><br><small><?= h($row['address']) ?></small></td><td><?= admin_place_type_badge($pType) ?></td><td><span style="display:inline-flex;align-items:center;gap:6px"><?= admin_category_icon($row['category_slug'] ?? $row['category_icon']) ?> <?= h($row['category_name']) ?></span></td><td><span style="display:inline-flex;align-items:center;gap:4px;font-weight:700"><?= admin_svg_icon('star') ?> <?= h((string)$row['rating']) ?></span> <small style="color:var(--c-ink-faint)">(<?= (int)$row['rating_count'] ?>)</small></td><td><?= $priceDisplay ?></td><td><?= (int)($row['estimated_duration'] ?? 60) ?> phút</td><td><?= (int)$row['is_active'] ? 'Đang hiển thị' : 'Đã ẩn' ?></td><td><div class="actions"><a class="btn btn--ghost btn--sm" href="restaurant_form.php?id=<?= (int)$row['id'] ?>">Sửa</a><form method="post" action="restaurant_delete.php" class="inline"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="active" value="<?= (int)$row['is_active'] ? 0 : 1 ?>"><button class="btn btn--ghost btn--sm <?= (int)$row['is_active'] ? 'danger' : '' ?>" type="submit"><?= (int)$row['is_active'] ? 'Ẩn' : 'Hiện' ?></button></form></div></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="8">Không có địa điểm nào phù hợp.</td></tr><?php endif; ?></tbody></table></div>
<?php $pages = max(1, (int) ceil($total / $perPage)); if ($pages > 1): ?><nav class="pagination" aria-label="Phân trang"><?php for ($p=1;$p<=$pages;$p++): ?><a href="?<?= h(http_build_query(['q'=>$q,'type'=>$type ?: null,'category'=>$category ?: null,'page'=>$p])) ?>" <?= $p===$page ? 'aria-current="page"' : '' ?>><?= $p ?></a><?php endfor; ?></nav><?php endif; admin_footer();

