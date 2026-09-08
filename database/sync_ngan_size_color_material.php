<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../includes/db.php';

if (
    !isset($pdo)
    || !($pdo instanceof PDO)
) {
    throw new RuntimeException(
        'Không lấy được PDO.'
    );
}

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->exec('SET NAMES utf8mb4');

$sourceFile =
    __DIR__
    . '/ngan-products-size-source.sql';

if (!is_file($sourceFile)) {
    throw new RuntimeException(
        'Không tìm thấy source Ngân.'
    );
}

/*
 * Mapping đã xác nhận khi import:
 * old ID Ngân => ID hiện tại FashionShop
 */
$idMap = [
    1  => 33,
    4  => 34,
    5  => 35,
    6  => 36,
    7  => 37,
    8  => 38,
    9  => 39,
    10 => 40,
    11 => 41,
    12 => 42,
    13 => 43,
    14 => 44,
    15 => 45,
    16 => 46,
    17 => 47,
    18 => 48,
    19 => 49,
    22 => 50,
    23 => 51,
];

$columns = $pdo
    ->query('SHOW COLUMNS FROM products')
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('size', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN size VARCHAR(100) NULL
         AFTER stock'
    );

    echo "ADD size: OK\n";
}

$columns = $pdo
    ->query('SHOW COLUMNS FROM products')
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('color', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN color VARCHAR(100) NULL
         AFTER size'
    );

    echo "ADD color: OK\n";
}

$columns = $pdo
    ->query('SHOW COLUMNS FROM products')
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('material', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN material VARCHAR(255) NULL
         AFTER color'
    );

    echo "ADD material: OK\n";
}

/*
 * products.sql của Ngân có mỗi sản phẩm trên một dòng.
 * Chỉ lấy 3 field CUỐI:
 *
 * size, color, material
 *
 * Không đọc description nên tránh lỗi &nbsp; / HTML.
 */

$lines = file(
    $sourceFile,
    FILE_IGNORE_NEW_LINES
);

if ($lines === false) {
    throw new RuntimeException(
        'Không đọc được source SQL.'
    );
}

$sourceData = [];

foreach ($lines as $line) {

    $line = trim($line);

    if (
        !preg_match(
            '/^\((\d+),/',
            $line,
            $idMatch
        )
    ) {
        continue;
    }

    $oldId = (int)$idMatch[1];

    if (!isset($idMap[$oldId])) {
        continue;
    }

    /*
     * Lấy chính xác 3 chuỗi cuối tuple.
     */
    if (
        !preg_match(
            "/,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)\s*[,;]?\s*$/u",
            $line,
            $m
        )
    ) {
        throw new RuntimeException(
            "Không parse được feature old ID {$oldId}"
        );
    }

    $size =
        trim($m[1]);

    $color =
        trim($m[2]);

    $material =
        trim($m[3]);

    $sourceData[$oldId] = [
        'size' =>
            $size !== '' ? $size : null,

        'color' =>
            $color !== '' ? $color : null,

        'material' =>
            $material !== '' ? $material : null,
    ];
}

if (count($sourceData) !== 19) {

    throw new RuntimeException(
        'Source feature không đủ 19 sản phẩm. Có: '
        . count($sourceData)
    );
}

$update = $pdo->prepare(
    'UPDATE products
     SET
        size = :size,
        color = :color,
        material = :material
     WHERE id = :id'
);

$pdo->beginTransaction();

try {

    foreach ($idMap as $oldId => $newId) {

        $data = $sourceData[$oldId];

        $update->execute([
            ':size' => $data['size'],
            ':color' => $data['color'],
            ':material' => $data['material'],
            ':id' => $newId,
        ]);
    }

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

echo PHP_EOL;
echo "===== VERIFY =====" . PHP_EOL;

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        size,
        color,
        material
     FROM products
     WHERE id BETWEEN 33 AND 51
     ORDER BY id'
);

foreach (
    $stmt->fetchAll(PDO::FETCH_ASSOC)
    as $row
) {

    echo
        $row['id']
        . ' | '
        . $row['name']
        . ' | SIZE='
        . (
            $row['size']
            ?: '[KHONG CO SIZE]'
        )
        . ' | MAU='
        . (
            $row['color']
            ?: '[TRONG]'
        )
        . ' | CHAT LIEU='
        . (
            $row['material']
            ?: '[TRONG]'
        )
        . PHP_EOL;
}

echo PHP_EOL;
echo "NGAN SIZE DATA: OK" . PHP_EOL;