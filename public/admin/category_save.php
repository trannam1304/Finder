<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

csrf_check();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$name = trim((string) ($_POST['name'] ?? ''));
$slug = trim((string) ($_POST['slug'] ?? ''));
$icon = trim((string) ($_POST['icon'] ?? 'plate'));
$typeGroup = trim((string) ($_POST['type_group'] ?? 'food'));
if (!in_array($typeGroup, ['food', 'tourism', 'entertainment'], true)) {
    $typeGroup = 'food';
}
$sortOrder = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT) ?: 0;

if ($name === '' || $slug === '') {
    admin_flash('Vui lòng nhập đầy đủ tên danh mục và slug.', 'error');
    header('Location: categories.php' . ($id ? "?edit={$id}" : ''));
    exit;
}

// Chuẩn hóa slug
$slug = strtolower(preg_replace('/[^a-z0-9\-]/', '-', $slug));
$slug = preg_replace('/-+/', '-', $slug);
$slug = trim($slug, '-');

$pdo = db();

try {
    if ($id > 0) {
        // Cập nhật
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, icon = ?, type_group = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $icon, $typeGroup, $sortOrder, $id]);
        admin_flash('Cập nhật danh mục thành công!');
    } else {
        // Thêm mới
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, type_group, sort_order) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $icon, $typeGroup, $sortOrder]);
        admin_flash('Thêm danh mục mới thành công!');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        admin_flash('Slug danh mục này đã tồn tại, vui lòng chọn slug khác.', 'error');
    } else {
        admin_flash('Lỗi lưu danh mục: ' . $e->getMessage(), 'error');
    }
}

header('Location: categories.php');
exit;
