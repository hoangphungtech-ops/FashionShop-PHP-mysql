<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$project = 'C:/xampp/htdocs/FashionShop-Group';

$sourceFile =
    $project
    . '/database/ngan_features_source_v2.sql';

$backupFile = 'C:/xampp/htdocs/FashionShop-Group/database/backup_before_ngan_features_v2_20260908-144045.json';
$reportFile = 'C:/xampp/htdocs/FashionShop-Group/database/NGAN_FEATURES_V2_20260908-144045.txt';

$reportLines = [];

function out(string $text = ''): void
{
    global $reportLines;

    echo $text . PHP_EOL;

    $reportLines[] = $text;
}

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
                    "SET NAMES utf8mb4"
                );

                return $pdo;
            }

        } catch (Throwable $e) {
        }
    }

    throw new RuntimeException(
        'Không lấy được PDO FashionShop.'
    );
}

/*
 * Lấy INSERT của products.
 *
 * KHÔNG dùng .*?;
 * vì HTML trong description có thể chứa &nbsp;
 */
function extractProductsInsert(
    string $sql
): array {

    $needle = 'INSERT INTO `products`';

    $start = strpos($sql, $needle);

    if ($start === false) {
        throw new RuntimeException(
            'Không tìm thấy INSERT INTO products.'
        );
    }

    $openParen = strpos(
        $sql,
        '(',
        $start
    );

    if ($openParen === false) {
        throw new RuntimeException(
            'Không tìm thấy danh sách columns.'
        );
    }

    $closeParen = strpos(
        $sql,
        ')',
        $openParen
    );

    if ($closeParen === false) {
        throw new RuntimeException(
            'Danh sách columns không hợp lệ.'
        );
    }

    $columnText = substr(
        $sql,
        $openParen + 1,
        $closeParen - $openParen - 1
    );

    preg_match_all(
        '/`([^`]+)`/',
        $columnText,
        $matches
    );

    $columns = $matches[1];

    $valuesPos = stripos(
        $sql,
        'VALUES',
        $closeParen
    );

    if ($valuesPos === false) {
        throw new RuntimeException(
            'Không tìm thấy VALUES.'
        );
    }

    $pos =
        $valuesPos
        + strlen('VALUES');

    $len = strlen($sql);

    $inString = false;
    $escaped  = false;

    $values = '';

    for ($i = $pos; $i < $len; $i++) {

        $ch = $sql[$i];

        if ($inString) {

            $values .= $ch;

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {
                $escaped = true;
                continue;
            }

            if ($ch === "'") {

                /*
                 * SQL cũng có thể dùng ''
                 * để biểu diễn một dấu '
                 */
                if (
                    $i + 1 < $len
                    && $sql[$i + 1] === "'"
                ) {
                    $values .= "'";
                    $i++;
                    continue;
                }

                $inString = false;
            }

            continue;
        }

        if ($ch === "'") {

            $inString = true;
            $values .= $ch;

            continue;
        }

        /*
         * Chỉ dấu ; NGOÀI string mới kết thúc INSERT.
         */
        if ($ch === ';') {
            break;
        }

        $values .= $ch;
    }

    if ($inString) {
        throw new RuntimeException(
            'Source INSERT bị cắt giữa chuỗi.'
        );
    }

    return [
        $columns,
        trim($values),
    ];
}

function splitTuples(string $values): array
{
    $result = [];

    $buffer = '';

    $depth = 0;

    $inString = false;
    $escaped = false;

    $len = strlen($values);

    for ($i = 0; $i < $len; $i++) {

        $ch = $values[$i];

        if ($inString) {

            if ($depth > 0) {
                $buffer .= $ch;
            }

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {
                $escaped = true;
                continue;
            }

            if ($ch === "'") {

                if (
                    $i + 1 < $len
                    && $values[$i + 1] === "'"
                ) {

                    if ($depth > 0) {
                        $buffer .= "'";
                    }

                    $i++;
                    continue;
                }

                $inString = false;
            }

            continue;
        }

        if ($ch === "'") {

            if ($depth > 0) {
                $buffer .= $ch;
            }

            $inString = true;

            continue;
        }

        if ($ch === '(') {

            if ($depth === 0) {

                $depth = 1;
                $buffer = '';

                continue;
            }

            $depth++;
            $buffer .= $ch;

            continue;
        }

        if ($ch === ')') {

            if ($depth === 1) {

                $result[] = $buffer;

                $buffer = '';
                $depth = 0;

                continue;
            }

            if ($depth > 1) {

                $depth--;

                $buffer .= $ch;
            }

            continue;
        }

        if ($depth > 0) {
            $buffer .= $ch;
        }
    }

    if ($depth !== 0 || $inString) {
        throw new RuntimeException(
            'Tuple source SQL không hoàn chỉnh.'
        );
    }

    return $result;
}

function splitFields(string $tuple): array
{
    $fields = [];

    $buffer = '';

    $inString = false;
    $escaped = false;

    $len = strlen($tuple);

    for ($i = 0; $i < $len; $i++) {

        $ch = $tuple[$i];

        if ($inString) {

            $buffer .= $ch;

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {
                $escaped = true;
                continue;
            }

            if ($ch === "'") {

                if (
                    $i + 1 < $len
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

        if ($ch === "'") {

            $buffer .= $ch;
            $inString = true;

            continue;
        }

        if ($ch === ',') {

            $fields[] = trim($buffer);

            $buffer = '';

            continue;
        }

        $buffer .= $ch;
    }

    $fields[] = trim($buffer);

    return $fields;
}

function decodeBackslashes(string $value): string
{
    $result = '';

    $len = strlen($value);

    for ($i = 0; $i < $len; $i++) {

        $ch = $value[$i];

        if (
            $ch !== '\\'
            || $i + 1 >= $len
        ) {
            $result .= $ch;
            continue;
        }

        $next = $value[++$i];

        switch ($next) {

            case '0':
                $result .= "\0";
                break;

            case 'n':
                $result .= "\n";
                break;

            case 'r':
                $result .= "\r";
                break;

            case 't':
                $result .= "\t";
                break;

            case 'Z':
                $result .= chr(26);
                break;

            case "'":
                $result .= "'";
                break;

            case '"':
                $result .= '"';
                break;

            case '\\':
                $result .= '\\';
                break;

            default:
                $result .= $next;
                break;
        }
    }

    return $result;
}

function decodeValue(string $raw): mixed
{
    $raw = trim($raw);

    if (strcasecmp($raw, 'NULL') === 0) {
        return null;
    }

    $len = strlen($raw);

    if (
        $len >= 2
        && $raw[0] === "'"
        && $raw[$len - 1] === "'"
    ) {

        $value = substr(
            $raw,
            1,
            -1
        );

        /*
         * SQL doubled quote
         */
        $value = str_replace(
            "''",
            "'",
            $value
        );

        return decodeBackslashes($value);
    }

    return $raw;
}

function normalizedNullable(
    mixed $value
): ?string {

    if ($value === null) {
        return null;
    }

    $value = trim(
        (string)$value
    );

    if ($value === '') {
        return null;
    }

    return $value;
}

/*
 * ============================================================
 * START
 * ============================================================
 */

out('==============================================');
out('FASHIONSHOP - NGAN FEATURES V2');
out('==============================================');
out();

$pdo = loadFashionPDO($project);

$dbName = (string)$pdo
    ->query('SELECT DATABASE()')
    ->fetchColumn();

out('DATABASE: ' . $dbName);

if ($dbName !== 'fashion_shop') {
    throw new RuntimeException(
        'Sai database. Dừng lại.'
    );
}

$sql = file_get_contents($sourceFile);

if ($sql === false) {
    throw new RuntimeException(
        'Không đọc được source SQL.'
    );
}

[$columns, $valuesText] =
    extractProductsInsert($sql);

out(
    'SOURCE COLUMNS: '
    . implode(', ', $columns)
);

$requiredColumns = [
    'id',
    'name',
    'size',
    'color',
    'material',
];

foreach ($requiredColumns as $required) {

    if (
        !in_array(
            $required,
            $columns,
            true
        )
    ) {
        throw new RuntimeException(
            'Source thiếu column: '
            . $required
        );
    }
}

$tupleTexts =
    splitTuples($valuesText);

out(
    'SOURCE TUPLES: '
    . count($tupleTexts)
);

$sourceProducts = [];

foreach ($tupleTexts as $tupleText) {

    $fields =
        splitFields($tupleText);

    if (
        count($fields)
        !==
        count($columns)
    ) {

        throw new RuntimeException(
            'Sai số field. Expected '
            . count($columns)
            . ', actual '
            . count($fields)
        );
    }

    $row = [];

    foreach (
        $columns as $index => $column
    ) {
        $row[$column] =
            decodeValue(
                $fields[$index]
            );
    }

    $id = (int)$row['id'];

    $sourceProducts[$id] = $row;
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

if (count($idMap) !== 19) {
    throw new RuntimeException(
        'Mapping không đủ 19.'
    );
}

/*
 * ============================================================
 * VALIDATE 19 SAN PHAM TRUOC KHI SUA DB
 * ============================================================
 */

out();
out('===== VALIDATE MAPPING =====');

$getTarget = $pdo->prepare(
    'SELECT id, name
     FROM products
     WHERE id = :id'
);

foreach ($idMap as $oldId => $newId) {

    if (
        !isset(
            $sourceProducts[$oldId]
        )
    ) {
        throw new RuntimeException(
            "Source thiếu ID {$oldId}"
        );
    }

    $getTarget->execute([
        ':id' => $newId,
    ]);

    $target = $getTarget->fetch();

    if (!$target) {
        throw new RuntimeException(
            "Target thiếu ID {$newId}"
        );
    }

    $sourceName = trim(
        (string)$sourceProducts[$oldId]['name']
    );

    $targetName = trim(
        (string)$target['name']
    );

    if ($sourceName !== $targetName) {

        throw new RuntimeException(
            "Mapping sai old={$oldId}, new={$newId}"
            . PHP_EOL
            . "SOURCE={$sourceName}"
            . PHP_EOL
            . "TARGET={$targetName}"
        );
    }

    out(
        "OK old={$oldId} -> new={$newId}"
        . ' | '
        . $targetName
    );
}

/*
 * ============================================================
 * BACKUP
 * ============================================================
 */

$targetRows = $pdo
    ->query(
        'SELECT *
         FROM products
         WHERE id BETWEEN 33 AND 51
         ORDER BY id'
    )
    ->fetchAll();

if (count($targetRows) !== 19) {
    throw new RuntimeException(
        'Target không đủ 19 products.'
    );
}

$backupJson = json_encode(
    [
        'created_at' => date(DATE_ATOM),
        'database' => $dbName,
        'rows' => $targetRows,
    ],
    JSON_PRETTY_PRINT
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

if ($backupJson === false) {
    throw new RuntimeException(
        'Không tạo được backup JSON.'
    );
}

if (
    file_put_contents(
        $backupFile,
        $backupJson,
        LOCK_EX
    )
    === false
) {
    throw new RuntimeException(
        'Không ghi được backup.'
    );
}

out();
out('BACKUP: OK');
out($backupFile);

/*
 * ============================================================
 * ADD COLUMNS
 * ============================================================
 */

out();
out('===== DATABASE SCHEMA =====');

$columnsNow = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (
    !in_array(
        'size',
        $columnsNow,
        true
    )
) {
    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN size VARCHAR(100) NULL
         AFTER stock'
    );

    out('ADD size: OK');
}

$columnsNow = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (
    !in_array(
        'color',
        $columnsNow,
        true
    )
) {
    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN color VARCHAR(100) NULL
         AFTER size'
    );

    out('ADD color: OK');
}

$columnsNow = $pdo
    ->query(
        'SHOW COLUMNS FROM products'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if (
    !in_array(
        'material',
        $columnsNow,
        true
    )
) {
    $pdo->exec(
        'ALTER TABLE products
         ADD COLUMN material VARCHAR(255) NULL
         AFTER color'
    );

    out('ADD material: OK');
}

/*
 * ============================================================
 * UPDATE
 * ============================================================
 */

out();
out('===== UPDATE 19 PRODUCTS =====');

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

    foreach (
        $idMap as $oldId => $newId
    ) {

        $source =
            $sourceProducts[$oldId];

        $size =
            normalizedNullable(
                $source['size']
            );

        $color =
            normalizedNullable(
                $source['color']
            );

        $material =
            normalizedNullable(
                $source['material']
            );

        $update->execute([
            ':size' => $size,
            ':color' => $color,
            ':material' => $material,
            ':id' => $newId,
        ]);

        out(
            'ID '
            . $newId
            . ' | '
            . $source['name']
            . ' | SIZE='
            . ($size ?? '[TRONG]')
            . ' | MAU='
            . ($color ?? '[TRONG]')
            . ' | CHAT LIEU='
            . ($material ?? '[TRONG]')
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
 * VERIFY EXACTLY AGAINST SOURCE
 * ============================================================
 */

out();
out('===== VERIFY EXACT =====');

$get = $pdo->prepare(
    'SELECT
        id,
        name,
        size,
        color,
        material
     FROM products
     WHERE id = :id'
);

$errors = 0;

$sizeCount = 0;
$colorCount = 0;
$materialCount = 0;

foreach (
    $idMap as $oldId => $newId
) {

    $get->execute([
        ':id' => $newId,
    ]);

    $target = $get->fetch();

    $source =
        $sourceProducts[$oldId];

    $expectedSize =
        normalizedNullable(
            $source['size']
        );

    $expectedColor =
        normalizedNullable(
            $source['color']
        );

    $expectedMaterial =
        normalizedNullable(
            $source['material']
        );

    $actualSize =
        normalizedNullable(
            $target['size']
        );

    $actualColor =
        normalizedNullable(
            $target['color']
        );

    $actualMaterial =
        normalizedNullable(
            $target['material']
        );

    if ($actualSize !== null) {
        $sizeCount++;
    }

    if ($actualColor !== null) {
        $colorCount++;
    }

    if ($actualMaterial !== null) {
        $materialCount++;
    }

    $ok =
        $expectedSize === $actualSize
        && $expectedColor === $actualColor
        && $expectedMaterial === $actualMaterial;

    if (!$ok) {
        $errors++;
    }

    out(
        ($ok ? '[OK] ' : '[LOI] ')
        . 'ID '
        . $newId
        . ' | '
        . $target['name']
    );
}

out();
out('Tong product : 19');
out('Co size      : ' . $sizeCount);
out('Co mau       : ' . $colorCount);
out('Co chat lieu : ' . $materialCount);
out('Sai du lieu  : ' . $errors);

out();

if ($errors !== 0) {
    throw new RuntimeException(
        'VERIFY phát hiện dữ liệu sai.'
    );
}

out('==============================================');
out('TICH HOP SIZE / MAU / CHAT LIEU: THANH CONG');
out('==============================================');

file_put_contents(
    $reportFile,
    implode(
        PHP_EOL,
        $reportLines
    ),
    LOCK_EX
);