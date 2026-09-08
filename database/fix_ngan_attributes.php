<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$project = 'C:/xampp/htdocs/FashionShop-Group';

$sourceFile =
    $project
    . '/database/ngan-products-attributes-source.sql';

function getPdo(string $project): PDO
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

                $pdo->exec('SET NAMES utf8mb4');

                return $pdo;
            }

        } catch (Throwable $e) {
        }
    }

    throw new RuntimeException(
        'Không lấy được PDO.'
    );
}

function splitFields(string $tuple): array
{
    $fields = [];

    $buffer = '';

    $inString = false;
    $escaped = false;

    $length = strlen($tuple);

    for ($i = 0; $i < $length; $i++) {

        $char = $tuple[$i];

        if ($inString) {

            $buffer .= $char;

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === "'") {

                if (
                    $i + 1 < $length
                    && $tuple[$i + 1] === "'"
                ) {
                    $buffer .= "'";
                    $i++;
                    continue;
                }

                $inString = false;
            }

            continue;
        }

        if ($char === "'") {

            $buffer .= $char;
            $inString = true;

            continue;
        }

        if ($char === ',') {

            $fields[] = trim($buffer);

            $buffer = '';

            continue;
        }

        $buffer .= $char;
    }

    $fields[] = trim($buffer);

    return $fields;
}

function decodeSqlValue(string $raw): ?string
{
    $raw = trim($raw);

    if (strcasecmp($raw, 'NULL') === 0) {
        return null;
    }

    $length = strlen($raw);

    if (
        $length >= 2
        && $raw[0] === "'"
        && $raw[$length - 1] === "'"
    ) {

        $raw = substr(
            $raw,
            1,
            -1
        );

        $raw = str_replace(
            "''",
            "'",
            $raw
        );

        $result = '';

        $len = strlen($raw);

        for ($i = 0; $i < $len; $i++) {

            if (
                $raw[$i] === '\\'
                && $i + 1 < $len
            ) {

                $next = $raw[++$i];

                if ($next === 'n') {
                    $result .= "\n";
                    continue;
                }

                if ($next === 'r') {
                    $result .= "\r";
                    continue;
                }

                if ($next === 't') {
                    $result .= "\t";
                    continue;
                }

                $result .= $next;

                continue;
            }

            $result .= $raw[$i];
        }

        return $result;
    }

    return $raw;
}

function nullable(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);

    return $value === ''
        ? null
        : $value;
}

$pdo = getPdo($project);

$db = $pdo
    ->query('SELECT DATABASE()')
    ->fetchColumn();

echo 'DB: ' . $db . PHP_EOL;

if ($db !== 'fashion_shop') {
    throw new RuntimeException(
        'Sai database.'
    );
}

/*
 * ============================================================
 * DOC 19 PRODUCTS TU SOURCE NGAN
 * ============================================================
 */

$lines = file(
    $sourceFile,
    FILE_IGNORE_NEW_LINES
);

if ($lines === false) {
    throw new RuntimeException(
        'Không đọc được SQL Ngân.'
    );
}

$sourceProducts = [];

foreach ($lines as $line) {

    $line = trim($line);

    if (!preg_match('/^\((\d+),/', $line)) {
        continue;
    }

    /*
     * Bỏ "(" đầu và "),", ");" cuối.
     */
    $line = substr($line, 1);

    $line = preg_replace(
        '/\)\s*[,;]?\s*$/',
        '',
        $line
    );

    $fields = splitFields($line);

    /*
     * products.sql Ngân có 15 columns.
     */
    if (count($fields) !== 15) {

        throw new RuntimeException(
            'Parse source lỗi. Số fields = '
            . count($fields)
            . PHP_EOL
            . mb_substr($line, 0, 150)
        );
    }

    $oldId =
        (int)decodeSqlValue($fields[0]);

    $sourceProducts[$oldId] = [
        'id'       => $oldId,
        'name'     => decodeSqlValue($fields[2]),
        'size'     => nullable(
            decodeSqlValue($fields[12])
        ),
        'color'    => nullable(
            decodeSqlValue($fields[13])
        ),
        'material' => nullable(
            decodeSqlValue($fields[14])
        ),
    ];
}

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

echo 'SOURCE PARSED: '
    . count($sourceProducts)
    . PHP_EOL;

echo PHP_EOL;
echo "===== VALIDATE =====" . PHP_EOL;

$get = $pdo->prepare(
    'SELECT id, name
     FROM products
     WHERE id = :id'
);

foreach ($idMap as $oldId => $newId) {

    if (!isset($sourceProducts[$oldId])) {

        throw new RuntimeException(
            "Source thiếu old ID {$oldId}"
        );
    }

    $get->execute([
        ':id' => $newId,
    ]);

    $target = $get->fetch();

    if (!$target) {

        throw new RuntimeException(
            "DB thiếu new ID {$newId}"
        );
    }

    if (
        trim((string)$sourceProducts[$oldId]['name'])
        !==
        trim((string)$target['name'])
    ) {

        throw new RuntimeException(
            "Sai mapping ID {$oldId} -> {$newId}"
            . PHP_EOL
            . 'SOURCE: '
            . $sourceProducts[$oldId]['name']
            . PHP_EOL
            . 'DB: '
            . $target['name']
        );
    }

    echo
        "[OK] {$oldId} -> {$newId} | "
        . $target['name']
        . PHP_EOL;
}

/*
 * ============================================================
 * THEM COT
 * ============================================================
 */

$columns = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('size', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN size VARCHAR(100) NULL
         AFTER stock'
    );

    echo "ADD size: OK" . PHP_EOL;
}

$columns = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('color', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN color VARCHAR(100) NULL
         AFTER size'
    );

    echo "ADD color: OK" . PHP_EOL;
}

$columns = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('material', $columns, true)) {

    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN material VARCHAR(255) NULL
         AFTER color'
    );

    echo "ADD material: OK" . PHP_EOL;
}

/*
 * ============================================================
 * UPDATE CHI 3 FIELD
 * ============================================================
 */

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

        $source =
            $sourceProducts[$oldId];

        $update->execute([
            ':size' =>
                $source['size'],

            ':color' =>
                $source['color'],

            ':material' =>
                $source['material'],

            ':id' =>
                $newId,
        ]);
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

$rows = $stmt->fetchAll();

$withSize = 0;

foreach ($rows as $row) {

    if (
        $row['size'] !== null
        && trim((string)$row['size']) !== ''
    ) {
        $withSize++;
    }

    echo
        'ID '
        . $row['id']
        . ' | '
        . $row['name']
        . ' | SIZE='
        . ($row['size'] ?: '[TRONG]')
        . ' | MAU='
        . ($row['color'] ?: '[TRONG]')
        . ' | CHAT LIEU='
        . ($row['material'] ?: '[TRONG]')
        . PHP_EOL;
}

echo PHP_EOL;
echo 'PRODUCTS: ' . count($rows) . PHP_EOL;
echo 'CO SIZE : ' . $withSize . PHP_EOL;

echo PHP_EOL;
echo "DATABASE ATTRIBUTES: OK" . PHP_EOL;