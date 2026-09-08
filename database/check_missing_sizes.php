<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query(
    "SELECT
        id,
        name,
        size
     FROM products
     ORDER BY id"
);

$missing = 0;
$freesize = 0;

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {

    $size =
        trim(
            (string)(
                $row['size']
                ?? ''
            )
        );

    if ($size === '') {

        $missing++;

        echo
            '[CHUA CO SIZE] '
            . $row['id']
            . ' | '
            . $row['name']
            . PHP_EOL;
    }

    if (
        mb_strtolower(
            $size,
            'UTF-8'
        ) === 'freesize'
    ) {
        $freesize++;
    }
}

echo PHP_EOL;
echo 'Sản phẩm chưa có size: '
    . $missing
    . PHP_EOL;

echo 'Freesize còn lại: '
    . $freesize
    . PHP_EOL;

ob_end_flush();