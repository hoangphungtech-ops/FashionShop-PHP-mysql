<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Không lấy được PDO.');
}

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->exec(
    "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
);

$stmt = $pdo->prepare(
    "UPDATE products
     SET size = NULL
     WHERE LOWER(TRIM(COALESCE(size, ''))) = 'freesize'"
);

$stmt->execute();

echo 'Đã xóa Freesize khỏi DB: '
    . $stmt->rowCount()
    . PHP_EOL;

echo PHP_EOL;
echo "===== SIZE HIEN TAI =====" . PHP_EOL;

$rows = $pdo->query(
    "SELECT id, name, size
     FROM products
     ORDER BY id"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {

    $size = trim(
        (string)($row['size'] ?? '')
    );

    echo
        $row['id']
        . ' | '
        . $row['name']
        . ' | '
        . (
            $size !== ''
            ? 'SIZE=' . $size
            : 'SIZE=[CHUA CO]'
        )
        . PHP_EOL;
}

$count = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM products
     WHERE LOWER(TRIM(COALESCE(size, ''))) = 'freesize'"
)->fetchColumn();

echo PHP_EOL;
echo 'FREESIZE TRONG DB: '
    . $count
    . PHP_EOL;

ob_end_flush();