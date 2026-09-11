<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Không có PDO.');
}

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->exec('SET NAMES utf8mb4');

/*
 * Các sản phẩm cũ trước đợt import Ngân vốn không có
 * cột size. Script trước đã tự gán Freesize cho chúng.
 *
 * Sản phẩm Ngân nằm từ ID 33 trở lên và đã có size nguồn.
 * Chỉ xóa Freesize giả ở nhóm sản phẩm cũ.
 */

$stmt = $pdo->prepare(
    "UPDATE products
     SET size = NULL
     WHERE id < 33
       AND TRIM(LOWER(size)) = 'freesize'"
);

$stmt->execute();

echo 'Đã bỏ Freesize tự gán: '
    . $stmt->rowCount()
    . PHP_EOL;

echo PHP_EOL;
echo "===== PRODUCT SIZE HIEN TAI =====" . PHP_EOL;

$rows = $pdo
    ->query(
        'SELECT
            id,
            name,
            size,
            color,
            material
         FROM products
         ORDER BY id'
    )
    ->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {

    echo
        $row['id']
        . ' | '
        . $row['name']
        . ' | SIZE='
        . (
            trim((string)$row['size']) !== ''
                ? $row['size']
                : '[CHUA CO SIZE]'
        )
        . PHP_EOL;
}

ob_end_flush();