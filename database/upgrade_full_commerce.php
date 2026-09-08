<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException(
        'Không lấy được kết nối PDO.'
    );
}

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->exec(
    "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
);

function fsColumns(PDO $pdo, string $table): array
{
    return $pdo
        ->query(
            'SHOW COLUMNS FROM `' . $table . '`'
        )
        ->fetchAll(PDO::FETCH_COLUMN);
}

function fsAddColumn(
    PDO $pdo,
    string $table,
    string $column,
    string $definition
): void {

    $columns = fsColumns($pdo, $table);

    if (in_array($column, $columns, true)) {
        echo "COLUMN {$table}.{$column}: EXISTS\n";
        return;
    }

    $pdo->exec(
        "ALTER TABLE `{$table}`
         ADD COLUMN `{$column}` {$definition}"
    );

    echo "ADD {$table}.{$column}: OK\n";
}

echo "DATABASE: "
    . $pdo->query('SELECT DATABASE()')->fetchColumn()
    . PHP_EOL;

echo PHP_EOL;
echo "===== PRODUCTS =====" . PHP_EOL;

fsAddColumn(
    $pdo,
    'products',
    'size',
    "VARCHAR(100) NULL AFTER `stock`"
);

fsAddColumn(
    $pdo,
    'products',
    'color',
    "VARCHAR(150) NULL AFTER `size`"
);

fsAddColumn(
    $pdo,
    'products',
    'material',
    "VARCHAR(255) NULL AFTER `color`"
);

/*
 * Yêu cầu hiện size cho toàn bộ sản phẩm.
 * Sản phẩm chưa được cấu hình size -> Freesize.
 */
$pdo->exec(
    "UPDATE products
     SET size = 'Freesize'
     WHERE size IS NULL
        OR TRIM(size) = ''"
);

/*
 * Hệ thống đơn hàng snapshot riêng.
 * Không phá bảng orders/order_items cũ.
 */

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS fs_orders (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        user_id INT NULL,

        receiver_name VARCHAR(150) NOT NULL,

        phone VARCHAR(30) NOT NULL,

        email VARCHAR(190) NULL,

        address_line VARCHAR(255) NOT NULL,

        province VARCHAR(120) NOT NULL,

        district VARCHAR(120) NOT NULL,

        ward VARCHAR(120) NOT NULL,

        note TEXT NULL,

        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,

        status VARCHAR(40) NOT NULL DEFAULT 'pending',

        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP NOT NULL
            DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),

        KEY idx_fs_orders_user (user_id),
        KEY idx_fs_orders_status (status),
        KEY idx_fs_orders_created (created_at)

    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS fs_order_items (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        order_id BIGINT UNSIGNED NOT NULL,

        product_id INT NULL,

        product_name VARCHAR(255) NOT NULL,

        product_image VARCHAR(255) NULL,

        size VARCHAR(100) NULL,

        color VARCHAR(150) NULL,

        material VARCHAR(255) NULL,

        unit_price DECIMAL(12,2) NOT NULL,

        quantity INT NOT NULL,

        line_total DECIMAL(12,2) NOT NULL,

        PRIMARY KEY (id),

        KEY idx_fs_item_order (order_id),
        KEY idx_fs_item_product (product_id),

        CONSTRAINT fk_fs_item_order
            FOREIGN KEY (order_id)
            REFERENCES fs_orders(id)
            ON DELETE CASCADE

    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci"
);

echo PHP_EOL;
echo "FS ORDERS TABLES: OK" . PHP_EOL;

/*
 * Đồng bộ dữ liệu Ngân nếu source vẫn còn trên máy.
 */

$sourceFile =
    'C:/Users/ACER/OneDrive - ut.edu.vn/Desktop/'
    . 'Lập trình Web/FashionShop-PHP-mysql-main (2)/'
    . 'FashionShop-PHP-mysql-main/assets/images/'
    . 'Sản Phẩm Ảnh Của Ngân/products.sql';

$idMap = [
    1 => 33,
    4 => 34,
    5 => 35,
    6 => 36,
    7 => 37,
    8 => 38,
    9 => 39,
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

if (is_file($sourceFile)) {

    $lines = file(
        $sourceFile,
        FILE_IGNORE_NEW_LINES
    );

    $update = $pdo->prepare(
        'UPDATE products
         SET
            size = :size,
            color = :color,
            material = :material
         WHERE id = :id'
    );

    $synced = 0;

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
         * Ba field cuối của products.sql Ngân:
         * size, color, material
         */
        if (
            !preg_match(
                "/,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)\s*[,;]?\s*$/u",
                $line,
                $m
            )
        ) {
            continue;
        }

        $size = trim($m[1]);
        $color = trim($m[2]);
        $material = trim($m[3]);

        $update->execute([
            ':size' =>
                $size !== ''
                    ? $size
                    : null,

            ':color' =>
                $color !== ''
                    ? $color
                    : null,

            ':material' =>
                $material !== ''
                    ? $material
                    : null,

            ':id' => $idMap[$oldId],
        ]);

        $synced++;
    }

    echo "NGAN SYNC: {$synced}/19\n";
}

echo PHP_EOL;
echo "===== VERIFY PRODUCT ATTRIBUTES =====" . PHP_EOL;

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        size,
        color,
        material
     FROM products
     ORDER BY id'
);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {

    echo
        $row['id']
        . ' | '
        . $row['name']
        . ' | SIZE='
        . ($row['size'] ?: 'Freesize')
        . ' | COLOR='
        . ($row['color'] ?: '-')
        . ' | MATERIAL='
        . ($row['material'] ?: '-')
        . PHP_EOL;
}

ob_end_flush();