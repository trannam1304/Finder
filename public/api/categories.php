<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

api_guard(function (): void {
    header('Cache-Control: public, max-age=3600');
    $rows = db()->query('SELECT id, type_group, name, slug, icon, sort_order FROM categories ORDER BY sort_order, name')->fetchAll();
    json_ok(array_map(static fn(array $row) => [
        'id' => (int) $row['id'],
        'type_group' => $row['type_group'] ?? 'food',
        'name' => $row['name'],
        'slug' => $row['slug'],
        'icon' => $row['icon'],
        'sort_order' => (int) $row['sort_order'],
    ], $rows));
});
