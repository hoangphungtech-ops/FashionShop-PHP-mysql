<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function admin_slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = str_replace(['đ', 'Đ'], 'd', $value);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = is_string($ascii) ? $ascii : $value;
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim(strtolower($value), '-');

    return $value !== '' ? $value : 'item';
}

function admin_unique_slug(
    PDO $pdo,
    string $table,
    string $source,
    ?int $excludeId = null
): string {
    if (!in_array($table, ['categories', 'products'], true)) {
        throw new InvalidArgumentException('Bảng tạo slug không hợp lệ.');
    }

    $base = admin_slugify($source);
    $candidate = $base;
    $suffix = 2;
    $sql = "SELECT id FROM {$table} WHERE slug = :slug";

    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
    }

    $sql .= ' LIMIT 1';
    $statement = $pdo->prepare($sql);

    while (true) {
        $parameters = [':slug' => $candidate];

        if ($excludeId !== null) {
            $parameters[':exclude_id'] = $excludeId;
        }

        $statement->execute($parameters);

        if (!$statement->fetchColumn()) {
            return $candidate;
        }

        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

function admin_category_exists(PDO $pdo, int $categoryId): bool
{
    $statement = $pdo->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $categoryId]);

    return (bool) $statement->fetchColumn();
}

function admin_product_image_url(mixed $image, string $basePath = '../../'): string
{
    $path = trim(str_replace('\\', '/', (string) $image));

    if ($path === '' || str_contains($path, '..')) {
        return $basePath . 'assets/images/ao-thun.jpg';
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'assets/')) {
        return $basePath . $path;
    }

    return $basePath . 'uploads/products/' . rawurlencode(basename($path));
}

function admin_flash(string $type, string $message): void
{
    start_secure_session();
    $_SESSION['admin_flash'] = [
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => $message,
    ];
}

function pull_admin_flash(): ?array
{
    start_secure_session();
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return is_array($flash) ? $flash : null;
}
