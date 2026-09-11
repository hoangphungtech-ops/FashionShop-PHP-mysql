<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$project = 'C:/xampp/htdocs/FashionShop-Group';

$sourceFile = $project
    . '/database/ngan_products_features_source.sql';

$backupFile = 'C:/xampp/htdocs/FashionShop-Group/database/backup_products_before_features_20260908-143733.json';
$reportFile = 'C:/xampp/htdocs/FashionShop-Group/database/NGAN_PRODUCT_FEATURES_20260908-143733.txt';

function loadFashionPDO(string $project): PDO
{
    $files = [
        $project . '/includes/db.php',
        $project . '/config/database.php',
    ];

    foreach ($files as $file) {

        if (!is_file($file)) {
            continue;
        }

        $loader = static function (string $file): ?PDO {

            $result = require $file;

            if ($result instanceof PDO) {
                return $result;
            }

            foreach (get_defined_vars() as $value) {

                if ($value instanceof PDO) {
                    return $value;
                }
            }

            return null;
        };

        try {

            $pdo = $loader($file);

            if ($pdo instanceof PDO) {

                $pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                $pdo->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

                $pdo->exec(
                    "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                );

                return $pdo;
            }

        } catch (Throwable $e) {
        }
    }

    throw new RuntimeException(
        'Không lấy được PDO của FashionShop.'
    );
}

function output(string $text = ''): void
{
    global $lines;

    echo $text . PHP_EOL;
    $lines[] = $text;
}

function nullableText(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);

    return $value === ''
        ? null
        : $value;
}

$lines = [];

$pdo = loadFashionPDO($project);

output('==============================================');
output('FASHIONSHOP - TICH HOP SIZE / MAU / CHAT LIEU');
output('==============================================');
output();

output(
    'DATABASE: '
    . $pdo->query('SELECT DATABASE()')->fetchColumn()
);

output();

if (!is_file($sourceFile)) {
    throw new RuntimeException(
        'Không tìm thấy source SQL của Ngân.'
    );
}

/*
 * Mapping đã được xác nhận từ lần import Ngân:
 * old source id => new fashion_shop id
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

/*
 * ============================================================
 * BACKUP TOAN BO 19 PRODUCTS TRUOC KHI SUA
 * ============================================================
 */

$currentRows = $pdo
    ->query(
        'SELECT *
         FROM products
         WHERE id BETWEEN 33 AND 51
         ORDER BY id'
    )
    ->fetchAll();

if (count($currentRows) !== 19) {

    throw new RuntimeException(
        'Database hiện tại không đủ đúng 19 sản phẩm ID 33-51. '
        . 'Dừng để tránh cập nhật sai.'
    );
}

file_put_contents(
    $backupFile,
    json_encode(
        [
            'created_at' => date(DATE_ATOM),
            'database' => 'fashion_shop',
            'products' => $currentRows,
        ],
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    )
);

output('BACKUP: OK');
output($backupFile);
output();

/*
 * ============================================================
 * DOC SOURCE SQL NGAN VA TAO TEMP TABLE
 * ============================================================
 */

$sourceSql = file_get_contents($sourceFile);

if ($sourceSql === false) {
    throw new RuntimeException(
        'Không đọc được products.sql của Ngân.'
    );
}

if (
    !preg_match(
        '/INSERT\s+INTO\s+`products`\s*'
        . '\((.*?)\)\s*VALUES\s*(.*?);/si',
        $sourceSql,
        $match
    )
) {
    throw new RuntimeException(
        'Không tìm thấy INSERT products trong source Ngân.'
    );
}

$pdo->exec(
    'DROP TEMPORARY TABLE IF EXISTS ngan_products_source'
);

$pdo->exec(
    "CREATE TEMPORARY TABLE ngan_products_source (

        id INT NOT NULL,

        category_id INT NULL,

        name VARCHAR(255) NOT NULL,

        slug VARCHAR(255) NULL,

        price DECIMAL(12,2) NOT NULL DEFAULT 0,

        original_price DECIMAL(12,2) NULL,

        description TEXT NULL,

        image VARCHAR(255) NULL,

        gallery TEXT NULL,

        status TINYINT(1) NULL,

        created_at DATETIME NULL,

        stock INT NULL,

        size VARCHAR(100) NULL,

        color VARCHAR(100) NULL,

        material VARCHAR(255) NULL,

        PRIMARY KEY (id)

    )
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci"
);

$insertSql =
    'INSERT INTO `ngan_products_source` ('
    . $match[1]
    . ') VALUES '
    . $match[2];

$pdo->exec($insertSql);

$sourceCount = (int)$pdo
    ->query(
        'SELECT COUNT(*)
         FROM ngan_products_source'
    )
    ->fetchColumn();

output('SOURCE NGAN PRODUCTS: ' . $sourceCount);

if ($sourceCount < 19) {
    throw new RuntimeException(
        'Source Ngân không đủ dữ liệu sản phẩm.'
    );
}

/*
 * ============================================================
 * KIEM TRA SOURCE VS TARGET TRUOC KHI ALTER
 * ============================================================
 */

output();
output('===== KIEM TRA MAPPING =====');

foreach ($idMap as $oldId => $newId) {

    $sourceStmt = $pdo->prepare(
        'SELECT id, name, size, color, material
         FROM ngan_products_source
         WHERE id = :id'
    );

    $sourceStmt->execute([
        ':id' => $oldId,
    ]);

    $source = $sourceStmt->fetch();

    if (!$source) {
        throw new RuntimeException(
            "Thiếu source old ID {$oldId}"
        );
    }

    $targetStmt = $pdo->prepare(
        'SELECT id, name
         FROM products
         WHERE id = :id'
    );

    $targetStmt->execute([
        ':id' => $newId,
    ]);

    $target = $targetStmt->fetch();

    if (!$target) {
        throw new RuntimeException(
            "Thiếu target new ID {$newId}"
        );
    }

    /*
     * Tên phải giống tuyệt đối.
     * Không update nếu mapping có dấu hiệu sai.
     */
    if (
        trim((string)$source['name'])
        !==
        trim((string)$target['name'])
    ) {
        throw new RuntimeException(
            "Tên không khớp: old={$oldId}, new={$newId}"
            . PHP_EOL
            . 'SOURCE: ' . $source['name']
            . PHP_EOL
            . 'TARGET: ' . $target['name']
        );
    }

    output(
        "OK old={$oldId} -> new={$newId} | "
        . $target['name']
    );
}

/*
 * ============================================================
 * THEM COT NEU CHUA CO
 * ============================================================
 */

output();
output('===== DATABASE COLUMNS =====');

$columns = $pdo
    ->query('SHOW COLUMNS FROM products')
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('size', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN size VARCHAR(100) NULL
         AFTER stock'
    );

    output('ADD COLUMN size: OK');

} else {

    output('COLUMN size: DA CO');
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

    output('ADD COLUMN color: OK');

} else {

    output('COLUMN color: DA CO');
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

    output('ADD COLUMN material: OK');

} else {

    output('COLUMN material: DA CO');
}

/*
 * ============================================================
 * UPDATE 19 SAN PHAM
 * ============================================================
 */

output();
output('===== CAP NHAT DU LIEU NGAN =====');

$pdo->beginTransaction();

try {

    $sourceStmt = $pdo->prepare(
        'SELECT name, size, color, material
         FROM ngan_products_source
         WHERE id = :id'
    );

    $updateStmt = $pdo->prepare(
        'UPDATE products
         SET
             size = :size,
             color = :color,
             material = :material
         WHERE id = :id'
    );

    foreach ($idMap as $oldId => $newId) {

        $sourceStmt->execute([
            ':id' => $oldId,
        ]);

        $source = $sourceStmt->fetch();

        if (!$source) {
            throw new RuntimeException(
                "Không tìm thấy old ID {$oldId}"
            );
        }

        $size =
            nullableText($source['size']);

        $color =
            nullableText($source['color']);

        $material =
            nullableText($source['material']);

        $updateStmt->execute([
            ':size' => $size,
            ':color' => $color,
            ':material' => $material,
            ':id' => $newId,
        ]);

        output(
            'ID '
            . $newId
            . ' | '
            . $source['name']
            . ' | SIZE='
            . ($size ?? '[CHUA CO]')
            . ' | MAU='
            . ($color ?? '[CHUA CO]')
            . ' | CHAT LIEU='
            . ($material ?? '[CHUA CO]')
        );
    }

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

/*
 * ============================================================
 * VERIFY
 * ============================================================
 */

output();
output('===== VERIFY DATABASE =====');

$rows = $pdo
    ->query(
        'SELECT
            id,
            name,
            size,
            color,
            material
         FROM products
         WHERE id BETWEEN 33 AND 51
         ORDER BY id'
    )
    ->fetchAll();

$count = count($rows);

$withSize = 0;
$withColor = 0;
$withMaterial = 0;

foreach ($rows as $row) {

    if (
        $row['size'] !== null
        && trim((string)$row['size']) !== ''
    ) {
        $withSize++;
    }

    if (
        $row['color'] !== null
        && trim((string)$row['color']) !== ''
    ) {
        $withColor++;
    }

    if (
        $row['material'] !== null
        && trim((string)$row['material']) !== ''
    ) {
        $withMaterial++;
    }
}

output('Tong products Ngan : ' . $count);
output('Co size            : ' . $withSize);
output('Co mau             : ' . $withColor);
output('Co chat lieu       : ' . $withMaterial);

output();
output('==============================================');

if ($count === 19) {

    output('TICH HOP DU LIEU NGAN: THANH CONG');

} else {

    output('CAN KIEM TRA LAI');
}

output('==============================================');

file_put_contents(
    $reportFile,
    implode(PHP_EOL, $lines),
    LOCK_EX
);