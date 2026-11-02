<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$pdo = db();
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
$editCategory = null;

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Lấy danh sách danh mục kèm số lượng quán
$categories = $pdo->query('
    SELECT c.*, COUNT(r.id) AS resto_count
    FROM categories c
    LEFT JOIN restaurants r ON r.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.id ASC
')->fetchAll();

admin_header('Quản lý danh mục');
?>

<div class="admin-heading">
  <div>
    <h1>Quản lý danh mục đa địa điểm</h1>
    <p class="admin-note">Phân loại danh mục theo 3 nhóm chính: Ẩm thực, Du lịch tham quan &amp; Vui chơi giải trí</p>
  </div>
  <a href="index.php" class="btn btn--ghost">&larr; Về danh sách địa điểm</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: start;">

  <!-- Form thêm / sửa danh mục -->
  <div style="background: var(--c-surface, #fff); border: 1px solid var(--c-border, #ddd); border-radius: .75rem; padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem;">
      <?= $editCategory ? 'Chỉnh sửa danh mục: ' . h($editCategory['name']) : 'Thêm danh mục mới' ?>
    </h3>

    <form method="post" action="category_save.php" style="display: flex; flex-direction: column; gap: 0.85rem;">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <?php if ($editCategory): ?>
        <input type="hidden" name="id" value="<?= (int)$editCategory['id'] ?>">
      <?php endif; ?>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">
          Nhóm loại hình (Type Group) <span style="color:red">*</span>
        </label>
        <select class="admin-select" name="type_group" style="width: 100%;">
          <?php $currentGroup = $editCategory['type_group'] ?? 'food'; ?>
          <option value="food" <?= $currentGroup === 'food' ? 'selected' : '' ?>>🍜 Ẩm thực (Food)</option>
          <option value="tourism" <?= $currentGroup === 'tourism' ? 'selected' : '' ?>>🏛️ Du lịch / Tham quan (Tourism)</option>
          <option value="entertainment" <?= $currentGroup === 'entertainment' ? 'selected' : '' ?>>🎡 Vui chơi giải trí (Entertainment)</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">
          Tên danh mục <span style="color:red">*</span>
        </label>
        <input required type="text" class="admin-input" name="name" style="width: 100%;"
          value="<?= h($editCategory['name'] ?? '') ?>" placeholder="Ví dụ: Bảo tàng, Cà phê, Rạp phim">
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">
          Slug (Đường dẫn tĩnh) <span style="color:red">*</span>
        </label>
        <input required type="text" class="admin-input" name="slug" style="width: 100%;"
          value="<?= h($editCategory['slug'] ?? '') ?>" placeholder="Ví dụ: bao-tang">
        <small style="color: var(--c-ink-soft, #65706a);">Chỉ gồm chữ thường không dấu và gạch ngang</small>
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">
          Biểu tượng (Icon key)
        </label>
        <select class="admin-select" name="icon" style="width: 100%;">
          <?php
          $icons = [
            'rice' => '🍚 Bát cơm (rice)',
            'noodles' => '🍜 Tô bún phở (noodles)',
            'bread' => '🥖 Ổ bánh mì (bread)',
            'grill' => '🔥 Lẩu nướng (grill)',
            'snack' => '🍡 Ăn vặt (snack)',
            'leaf' => '🥬 Món chay (leaf)',
            'coffee' => '☕ Cà phê & Đồ uống (coffee)',
            'plate' => '🍽️ Dĩa thức ăn (plate)',
            'landmark' => '🏛️ Bảo tàng / Triển lãm (landmark)',
            'trees' => '🌳 Công viên / Sinh thái (trees)',
            'monument' => '⛩️ Di tích / Văn hóa (monument)',
            'camera' => '📸 Điểm ngắm cảnh / Check-in (camera)',
            'trophy' => '🎳 Vận động / Thể thao (trophy)',
            'film' => '🎬 Rạp phim / Nghệ thuật (film)',
            'gamepad' => '🎮 Boardgame / Acoustic / Chill (gamepad)',
            'bag' => '🛍️ TTTM / Mua sắm (bag)'
          ];
          $currentIcon = $editCategory['icon'] ?? 'plate';
          foreach ($icons as $val => $label): ?>
            <option value="<?= $val ?>" <?= $currentIcon === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">
          Thứ tự hiển thị
        </label>
        <input type="number" class="admin-input" name="sort_order" style="width: 100%;"
          value="<?= (int)($editCategory['sort_order'] ?? (count($categories) + 1)) ?>">
      </div>

      <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
        <button type="submit" class="btn btn--primary">
          <?= $editCategory ? 'Cập nhật danh mục' : 'Thêm danh mục' ?>
        </button>
        <?php if ($editCategory): ?>
          <a href="categories.php" class="btn btn--ghost">Hủy</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Bảng danh sách danh mục -->
  <div style="background: var(--c-surface, #fff); border: 1px solid var(--c-border, #ddd); border-radius: .75rem; padding: 1.25rem;">
    <h3 style="margin-top: 0; margin-bottom: 1rem;">Danh sách danh mục hiện có (<?= count($categories) ?>)</h3>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Icon</th>
            <th>Tên danh mục</th>
            <th>Nhóm</th>
            <th>Slug</th>
            <th>Thứ tự</th>
            <th>Số điểm</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td style="font-size: 1.2rem; text-align: center;">
                <?= admin_category_icon($cat['slug'] ?? $cat['icon']) ?>
              </td>
              <td><strong><?= h($cat['name']) ?></strong></td>
              <td><?= admin_place_type_badge($cat['type_group'] ?? 'food') ?></td>
              <td><code><?= h($cat['slug']) ?></code></td>
              <td><?= (int)$cat['sort_order'] ?></td>
              <td>
                <span class="badge" style="background: var(--c-bg, #f7f5ef); padding: 2px 8px; border-radius: 999px;">
                  <?= (int)$cat['resto_count'] ?> điểm
                </span>
              </td>
              <td>
                <div class="actions">
                  <a href="categories.php?edit=<?= (int)$cat['id'] ?>" class="btn btn--ghost btn--sm">Sửa</a>
                  <form method="post" action="category_delete.php" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                    <button class="btn btn--ghost btn--sm danger" type="submit" <?= (int)$cat['resto_count'] > 0 ? 'disabled title="Không thể xóa danh mục đang có quán ăn"' : '' ?>>
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
  </div>

</div>

<?php admin_footer(); ?>
