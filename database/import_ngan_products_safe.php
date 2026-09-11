<?php
declare(strict_types=1);

// CLI only. Never execute the source dump: read INSERT literals, ignore all DDL.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function readDump(string $path, string $table): array
{
    $sql = file_get_contents($path);
    if ($sql === false || !mb_check_encoding($sql, 'UTF-8')) { throw new RuntimeException('Invalid UTF-8 dump: ' . $path); }
    $pattern = '/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?\s*\(([^)]+)\)\s*VALUES\s*/i';
    if (preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE) !== 1) { throw new RuntimeException('Expected one INSERT in ' . $table); }
    preg_match_all('/`([a-z_]+)`/', $matches[1][0][0], $columns);
    $columns = $columns[1];
    $i = $matches[0][0][1] + strlen($matches[0][0][0]);
    $rows = [];
    $space = static function () use (&$i, $sql): void { while (isset($sql[$i]) && ctype_space($sql[$i])) { $i++; } };
    do {
        $space();
        if (($sql[$i++] ?? '') !== '(') { throw new RuntimeException('Invalid tuple: ' . $table); }
        $values = [];
        foreach ($columns as $column) {
            $space();
            if (($sql[$i] ?? '') === "'") {
                $i++; $value = ''; $closed = false;
                while (isset($sql[$i])) {
                    $char = $sql[$i++];
                    if ($char === '\\') {
                        $escaped = $sql[$i++] ?? throw new RuntimeException('Truncated escape');
                        $value .= match ($escaped) { 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1a", default => $escaped };
                    } elseif ($char === "'") {
                        if (($sql[$i] ?? '') === "'") { $value .= "'"; $i++; } else { $closed = true; break; }
                    } else { $value .= $char; }
                }
                if (!$closed) { throw new RuntimeException('Unclosed SQL string'); }
            } else {
                $start = $i;
                while (isset($sql[$i]) && !in_array($sql[$i], [',', ')'], true)) { $i++; }
                $value = trim(substr($sql, $start, $i - $start));
                if ($value === 'NULL') { $value = null; }
                elseif (!preg_match('/^-?\d+(\.\d+)?$/D', $value)) { throw new RuntimeException('Nonliteral SQL value'); }
            }
            $values[$column] = $value;
            $space();
            $expected = $column === end($columns) ? ')' : ',';
            if (($sql[$i++] ?? '') !== $expected) { throw new RuntimeException('Column count mismatch'); }
        }
        $id = (int) $values['id'];
        if (isset($rows[$id])) { throw new RuntimeException('Duplicate source ID'); }
        $rows[$id] = $values;
        $space(); $next = $sql[$i++] ?? '';
    } while ($next === ',');
    if ($next !== ';') { throw new RuntimeException('Invalid INSERT terminator'); }
    return $rows;
}

function writeJson(string $path, array $data): void
{
    $handle = fopen($path, 'xb');
    if (!$handle) { throw new RuntimeException('Cannot create new audit file: ' . $path); }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    try {
        if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) { throw new RuntimeException('Audit write failed'); }
    } finally { fclose($handle); }
}

function snapshot(PDO $pdo): array
{
    $result = [];
    foreach (['categories', 'products', 'product_images'] as $table) {
        $result[$table] = $pdo->query("SELECT * FROM `$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }
    return $result;
}

function nganSlugSource(string $name): string
{
    // Windows iconv can discard Vietnamese accents incorrectly. Transliterate explicitly
    // before using the existing admin uniqueness/suffix convention.
    $groups = ['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ'];
    $name = mb_strtolower($name, 'UTF-8');
    foreach ($groups as $ascii => $letters) {
        $name = str_replace(preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY), $ascii, $name);
    }
    return $name;
}

function assertPreserved(PDO $pdo, array $before): void
{
    foreach ($before as $table => $rows) {
        $statement = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        foreach ($rows as $row) {
            $statement->execute([$row['id']]);
            if ($statement->fetch(PDO::FETCH_ASSOC) !== $row) { throw new RuntimeException('Existing row changed: ' . $table . '/' . $row['id']); }
        }
    }
}

// Default is read-only. --apply must follow a clean dry-run with the same options.
$options = getopt('', ['source:', 'dry-run', 'apply', 'price-multiplier:', 'confirm-categories', 'report:']);
$root = dirname(__DIR__);
$pdo = null;
try {
    require $root . '/includes/db.php';
    session_write_close();
    require_once $root . '/includes/admin_helpers.php';
    $source = realpath($options['source'] ?? '');
    if (!$source || !isset($options['source'])) { throw new RuntimeException('Required: --source="directory containing the three SQL files and images"'); }
    if (isset($options['apply'], $options['dry-run'])) { throw new RuntimeException('Choose dry-run OR apply'); }
    $apply = isset($options['apply']);
    $multiplier = (string) ($options['price-multiplier'] ?? '');
    $issues = [];
    if (!in_array($multiplier, ['1', '1000'], true)) { $issues[] = 'PRICE_UNCONFIRMED: supply --price-multiplier=1 or 1000 only after owner confirmation'; }
    if (!isset($options['confirm-categories'])) { $issues[] = 'CATEGORY_UNCONFIRMED: source IDs 8 and 13 have inconsistent names/descriptions; owner must confirm source categories'; }
    $data = [];
    foreach (['categories','products','product_images'] as $table) { $data[$table] = readDump($source . '/' . $table . '.sql', $table); }
    $expected = [
        'categories' => ['id','name','slug','created_at'],
        'products' => ['id','category_id','name','slug','description','price','stock','image','status','created_at','updated_at'],
        'product_images' => ['id','product_id','image'],
    ];
    $schema = [];
    foreach ($expected as $table => $columns) {
        $actual = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (array_column($actual, 'Field') !== $columns) { throw new RuntimeException('Schema changed; review required: ' . $table); }
        $types = array_column($actual, 'Type', 'Field');
        $requiredTypes = match ($table) {
            'categories' => ['name' => 'varchar(100)', 'slug' => 'varchar(150)'],
            'products' => ['name' => 'varchar(150)', 'slug' => 'varchar(180)', 'image' => 'varchar(255)', 'price' => 'decimal(12,2)', 'description' => 'text', 'stock' => 'int', 'status' => 'tinyint(1)'],
            default => ['image' => 'varchar(255)'],
        };
        foreach ($requiredTypes as $column => $type) {
            if (strtolower($types[$column]) !== $type) { throw new RuntimeException('Column type changed: ' . $table . '.' . $column); }
        }
        if (!str_contains($actual[0]['Extra'], 'auto_increment')) { throw new RuntimeException('Expected auto-increment ID'); }
        $schema[$table] = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
        if (!str_contains($schema[$table], 'ENGINE=InnoDB')) { throw new RuntimeException('Transaction requires InnoDB'); }
    }
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 0)');
    $lock->execute(['fashion_shop_ngan_import']);
    if ((int) $lock->fetchColumn() !== 1) { throw new RuntimeException('Another importer is running'); }
    $pdo->beginTransaction();
    $before = snapshot($pdo);
    $categoryNames = ['đầm váy' => 'Váy', 'áo' => 'Áo', 'chân váy' => 'Chân váy', 'quần' => 'Quần', 'cardigan' => 'Cardigan'];
    $categoryPlan = [];
    foreach ($data['categories'] as $id => $row) {
        $name = $categoryNames[mb_strtolower(trim($row['name']), 'UTF-8')] ?? null;
        if ($name === null) { throw new RuntimeException('Unknown category: ' . $row['name']); }
        $matching = array_values(array_filter($before['categories'], static fn ($c) => mb_strtolower(trim($c['name']), 'UTF-8') === mb_strtolower($name, 'UTF-8')));
        if (count($matching) > 1) { throw new RuntimeException('Ambiguous category: ' . $name); }
        $categoryPlan[$id] = ['source_name' => $row['name'], 'name' => $name, 'existing_id' => $matching[0]['id'] ?? null];
    }
    $inventory = []; $counts = ['files' => 0, 'jpg' => 0, 'jpeg' => 0, 'png' => 0, 'webp' => 0];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!$file->isFile()) { continue; }
        $counts['files']++;
        $extension = strtolower($file->getExtension());
        if (!array_key_exists($extension, $counts) || $extension === 'files') { continue; }
        $counts[$extension]++;
        if (isset($inventory[$file->getFilename()])) { throw new RuntimeException('Ambiguous filename: ' . $file->getFilename()); }
        $inventory[$file->getFilename()] = $file->getPathname();
    }
    $references = []; $imageRows = [];
    foreach ($data['product_images'] as $row) {
        if (!isset($data['products'][(int) $row['product_id']])) { throw new RuntimeException('Orphan source image'); }
        $imageRows[(int) $row['product_id']][] = $row;
    }
    $plan = []; $usedSlugs = []; $copies = [];
    foreach ($data['products'] as $id => $row) {
        if (!isset($categoryPlan[(int) $row['category_id']])) { throw new RuntimeException('Missing source category'); }
        if (mb_strlen($row['name'], 'UTF-8') > 150 || strlen($row['description'] ?? '') > 65535) { throw new RuntimeException('Product exceeds target column size'); }
        if (!in_array((string) $row['status'], ['0','1'], true) || !ctype_digit((string) $row['stock'])) { throw new RuntimeException('Invalid stock/status'); }
        if ((int) $row['stock'] > 1000000 || !is_numeric($row['price']) || (float) $row['price'] < 0 || (float) $row['price'] * max(1, (int) $multiplier) > 9999999999.99) { throw new RuntimeException('Price/stock outside supported range'); }
        if (!empty($row['gallery'])) { throw new RuntimeException('Nonempty gallery field requires review'); }
        $primaryRows = array_values(array_filter($imageRows[$id] ?? [], static fn ($r) => (int) $r['is_primary'] === 1));
        if (count($primaryRows) > 1) { throw new RuntimeException('Multiple primary images'); }
        $main = $primaryRows[0]['image_path'] ?? $row['image'];
        $files = array_values(array_unique(array_filter(array_merge([$row['image'], $main], array_column($imageRows[$id] ?? [], 'image_path')))));
        $paths = [];
        foreach ($files as $file) {
            if (!preg_match('/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp)$/iD', $file)) { throw new RuntimeException('Unsafe image filename'); }
            $references[$file] = true;
            $relative = 'uploads/products/ngan/' . $file;
            if (isset($inventory[$file])) {
                if (!getimagesize($inventory[$file])) { throw new RuntimeException('Invalid image: ' . $file); }
                $hash = hash_file('sha256', $inventory[$file]);
                if (is_file($root . '/' . $relative) && hash_file('sha256', $root . '/' . $relative) !== $hash) {
                    $relative = 'uploads/products/ngan/' . pathinfo($file, PATHINFO_FILENAME) . '-' . $hash . '.' . pathinfo($file, PATHINFO_EXTENSION);
                }
                if (is_file($root . '/' . $relative) && hash_file('sha256', $root . '/' . $relative) !== $hash) { throw new RuntimeException('Image collision'); }
                $copies[$relative] = ['source' => $inventory[$file], 'sha256' => $hash];
            }
            $paths[$file] = $relative;
        }
        $base = admin_unique_slug($pdo, 'products', nganSlugSource($row['name'])); $slug = $base; $suffix = 2;
        while (isset($usedSlugs[$slug])) { $slug = $base . '-' . $suffix++; }
        if (strlen($slug) > 180 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) { throw new RuntimeException('Invalid target slug'); }
        $usedSlugs[$slug] = true;
        $duplicate = $pdo->prepare('SELECT id FROM products WHERE name = ? AND image = ?');
        $duplicate->execute([$row['name'], $paths[$main]]);
        $existing = $duplicate->fetchAll(PDO::FETCH_COLUMN);
        if (count($existing) > 1) { throw new RuntimeException('Ambiguous imported identity'); }
        $plan[$id] = ['old_id' => $id, 'existing_id' => $existing[0] ?? null, 'action' => $existing ? 'SKIPPED' : 'INSERT',
            'category_source_id' => (int) $row['category_id'], 'name' => $row['name'], 'slug' => $slug,
            'price_source' => $row['price'], 'price' => in_array($multiplier, ['1','1000'], true) ? number_format((float) $row['price'] * (int) $multiplier, 2, '.', '') : null,
            'stock' => (int) $row['stock'], 'status' => (int) $row['status'], 'image' => $paths[$main],
            'gallery' => array_values(array_diff(array_values($paths), [$paths[$main]])),
            'description' => $row['description'], 'created_at' => $row['created_at']];
    }
    $missing = array_values(array_diff(array_keys($references), array_keys($inventory)));
    if ($missing) { $issues[] = 'MISSING_IMAGES: ' . count($missing); }
    $report = ['mode' => $apply ? 'APPLY' : 'DRY_RUN', 'source' => $source, 'schema' => $schema,
        'source_counts' => array_map('count', $data), 'source_sql_sha256' => array_combine(array_keys($data), array_map(static fn ($table) => hash_file('sha256', $source . '/' . $table . '.sql'), array_keys($data))), 'inventory' => $counts,
        'referenced_images' => count($references), 'missing_images' => $missing,
        'unused_images' => array_values(array_diff(array_keys($inventory), array_keys($references))),
        'existing_counts' => array_map('count', $before), 'existing_sha256' => hash('sha256', serialize($before)),
        'category_mapping' => $categoryPlan, 'price_multiplier' => $multiplier ?: null,
        'unsupported_fields' => ['products.original_price','products.gallery (all NULL)','products.size','products.color','products.material','categories.description (all NULL)','product_images.created_at'],
        'primary_rule' => 'is_primary=1 becomes products.image; retain other unique images including original products.image in gallery',
        'products' => $plan, 'issues' => $issues];
    if (isset($options['report'])) { writeJson($options['report'], $report); }
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
    if (!$apply || $issues) {
        assertPreserved($pdo, $before);
        $pdo->rollBack();
        echo $issues ? "BLOCKED: no database or image changes.\n" : "DRY_RUN OK: no database or image changes.\n";
        exit($issues ? 2 : 0);
    }
    // Backup only the three product tables, before any mutation. storage is HTTP-denied.
    $auditDir = $root . '/storage/ngan-import';
    if (!is_dir($auditDir) && !mkdir($auditDir, 0770, true)) { throw new RuntimeException('Cannot create backup directory'); }
    $run = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(6));
    writeJson($auditDir . '/' . $run . '-before.json', ['schema' => $schema, 'rows' => $before, 'plan' => $report]);
    foreach ($copies as $relative => $copy) {
        $destination = $root . '/' . $relative;
        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0775, true)) { throw new RuntimeException('Cannot create image directory'); }
        if (!is_file($destination)) {
            $input = fopen($copy['source'], 'rb'); $output = fopen($destination, 'xb');
            if (!$input || !$output) { throw new RuntimeException('Exclusive image copy failed'); }
            try { if (stream_copy_to_stream($input, $output) === false) { throw new RuntimeException('Copy failed'); } }
            finally { fclose($input); fclose($output); }
        }
        if (hash_file('sha256', $destination) !== $copy['sha256']) { throw new RuntimeException('Image verification failed'); }
    }
    $categoryMap = []; $newCategories = [];
    foreach ($categoryPlan as $id => $category) {
        if ($category['existing_id'] !== null) { $categoryMap[$id] = (int) $category['existing_id']; continue; }
        $statement = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        $statement->execute([$category['name'], admin_unique_slug($pdo, 'categories', nganSlugSource($category['name']))]);
        $categoryMap[$id] = (int) $pdo->lastInsertId(); $newCategories[] = $categoryMap[$id];
    }
    $insert = $pdo->prepare('INSERT INTO products (category_id,name,slug,description,price,stock,image,status,created_at) VALUES (?,?,?,?,?,?,?,?,?)');
    $galleryInsert = $pdo->prepare('INSERT INTO product_images (product_id,image) VALUES (?,?)');
    $mapping = []; $newProducts = []; $newImages = [];
    foreach ($plan as $id => $product) {
        if ($product['existing_id'] !== null) { $mapping[$id] = (int) $product['existing_id']; echo "SKIPPED $id\n"; continue; }
        $insert->execute([$categoryMap[$product['category_source_id']],$product['name'],admin_unique_slug($pdo, 'products', nganSlugSource($product['name'])),$product['description'],$product['price'],$product['stock'],$product['image'],$product['status'],$product['created_at']]);
        $mapping[$id] = (int) $pdo->lastInsertId(); $newProducts[] = $mapping[$id];
        foreach ($product['gallery'] as $image) {
            $galleryInsert->execute([$mapping[$id], $image]); $newImages[] = (int) $pdo->lastInsertId();
        }
    }
    assertPreserved($pdo, $before);
    $after = snapshot($pdo);
    // Persist exact IDs before commit, so a filesystem failure can still roll back SQL.
    writeJson($auditDir . '/' . $run . '-transaction.json', ['state' => 'prepared; verify DB before manual rollback', 'mapping' => $mapping, 'new_categories' => $newCategories, 'new_products' => $newProducts, 'new_product_images' => $newImages, 'after' => $after]);
    $pdo->commit();
    echo 'COMMITTED ' . json_encode(['products' => count($newProducts), 'product_images' => count($newImages), 'counts' => array_map('count', $after), 'mapping' => $mapping, 'backup' => $auditDir . '/' . $run . '-before.json', 'old_rows_preserved' => true], JSON_UNESCAPED_UNICODE), "\n";
} catch (Throwable $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'FAILED: ' . $error->getMessage() . ". SQL rolled back if transaction active; copied files are retained.\n");
    exit(1);
}
