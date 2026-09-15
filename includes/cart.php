<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

/** @return list<string> */
function cart_option_list(mixed $value): array
{
    $raw = trim((string)$value);

    if ($raw === '') {
        return [];
    }

    $result = [];

    foreach (explode(',', $raw) as $item) {
        $item = trim($item);

        if ($item !== '' && !in_array($item, $result, true)) {
            $result[] = $item;
        }
    }

    return $result;
}

function cart_match_option(string $requested, array $allowed): ?string
{
    $requested = trim($requested);

    if ($requested === '') {
        return null;
    }

    foreach ($allowed as $option) {
        if (mb_strtolower($option, 'UTF-8') === mb_strtolower($requested, 'UTF-8')) {
            return $option;
        }
    }

    return null;
}

function cart_line_key(int $productId, string $size = '', string $color = ''): string
{
    return 'p' . $productId . '_' . substr(
        hash('sha256', $productId . "\0" . trim($size) . "\0" . trim($color)),
        0,
        24
    );
}

/**
 * Session format:
 * [
 *   line_key => [
 *     product_id => int,
 *     quantity   => int,
 *     size       => string,
 *     color      => string
 *   ]
 * ]
 *
 * Legacy [product_id => quantity] sessions are normalized automatically.
 *
 * @return array<string, array{product_id:int,quantity:int,size:string,color:string}>
 */
function cart_quantities(): array
{
    start_secure_session();

    $rawCart = $_SESSION['cart'] ?? [];
    $cart = [];

    if (is_array($rawCart)) {
        foreach ($rawCart as $key => $value) {
            $productIdValue = null;
            $quantityValue = null;
            $size = '';
            $color = '';

            if (is_array($value)) {
                $productIdValue = $value['product_id'] ?? null;
                $quantityValue = $value['quantity'] ?? null;
                $size = trim((string)($value['size'] ?? $value['selected_size'] ?? ''));
                $color = trim((string)($value['color'] ?? $value['selected_color'] ?? ''));
            } else {
                $productIdValue = $key;
                $quantityValue = $value;
            }

            $productId = filter_var($productIdValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 9999],
            ]);

            if ($productId === false || $quantity === false) {
                continue;
            }

            $productId = (int)$productId;
            $lineKey = cart_line_key($productId, $size, $color);

            if (isset($cart[$lineKey])) {
                $cart[$lineKey]['quantity'] = min(
                    9999,
                    $cart[$lineKey]['quantity'] + (int)$quantity
                );
            } else {
                $cart[$lineKey] = [
                    'product_id' => $productId,
                    'quantity' => (int)$quantity,
                    'size' => $size,
                    'color' => $color,
                ];
            }
        }
    }

    $_SESSION['cart'] = $cart;

    return $cart;
}

function cart_quantity_count(?array $cart = null): int
{
    $cart ??= cart_quantities();
    $count = 0;

    foreach ($cart as $line) {
        if (is_array($line)) {
            $count += max(0, (int)($line['quantity'] ?? 0));
        } else {
            $count += max(0, (int)$line);
        }
    }

    return $count;
}

/**
 * @return array{
 *   cart:array<string,array<string,mixed>>,
 *   items:list<array<string,mixed>>,
 *   missingLines:list<array{line_key:string,product_id:int}>,
 *   missingIds:list<int>,
 *   total:float,
 *   canCheckout:bool
 * }
 */
function load_cart(PDO $pdo, bool $forUpdate = false): array
{
    $cart = cart_quantities();
    $items = [];
    $missingLines = [];
    $missingIds = [];
    $total = 0.0;
    $canCheckout = $cart !== [];

    if ($cart === []) {
        return compact(
            'cart',
            'items',
            'missingLines',
            'missingIds',
            'total',
            'canCheckout'
        );
    }

    $ids = [];

    foreach ($cart as $line) {
        $ids[] = (int)$line['product_id'];
    }

    $ids = array_values(array_unique(array_filter($ids)));
    sort($ids, SORT_NUMERIC);

    if ($ids === []) {
        return compact(
            'cart',
            'items',
            'missingLines',
            'missingIds',
            'total',
            'canCheckout'
        );
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id, name, price, stock, image, status, size, color, material
            FROM products
            WHERE id IN ($placeholders)
            ORDER BY id";

    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($ids);
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
    $productsById = [];

    foreach ($products as $product) {
        $productsById[(int)$product['id']] = $product;
    }

    $totalsByProduct = [];

    foreach ($cart as $line) {
        $pid = (int)$line['product_id'];
        $totalsByProduct[$pid] = ($totalsByProduct[$pid] ?? 0)
            + (int)$line['quantity'];
    }

    foreach ($cart as $lineKey => $line) {
        $productId = (int)$line['product_id'];

        if (!isset($productsById[$productId])) {
            $missingLines[] = [
                'line_key' => (string)$lineKey,
                'product_id' => $productId,
            ];
            $missingIds[] = $productId;
            $canCheckout = false;
            continue;
        }

        $product = $productsById[$productId];
        $quantity = (int)$line['quantity'];
        $stock = max(0, (int)($product['stock'] ?? 0));
        $isActive = (int)($product['status'] ?? 0) === 1;
        $price = (float)($product['price'] ?? 0);
        $size = trim((string)($line['size'] ?? ''));
        $color = trim((string)($line['color'] ?? ''));

        $allowedSizes = cart_option_list($product['size'] ?? '');
        $allowedColors = cart_option_list($product['color'] ?? '');

        $sizeValid = $allowedSizes === []
            || cart_match_option($size, $allowedSizes) !== null;

        $colorValid = $allowedColors === []
            || cart_match_option($color, $allowedColors) !== null;

        $aggregateQuantity = (int)($totalsByProduct[$productId] ?? $quantity);
        $isAvailable = $isActive
            && $stock > 0
            && $aggregateQuantity <= $stock
            && $sizeValid
            && $colorValid;

        if (!$isAvailable) {
            $canCheckout = false;
        }

        $subtotal = $price * $quantity;

        $items[] = array_merge($product, [
            'line_key' => (string)$lineKey,
            'quantity' => $quantity,
            'selected_size' => $size,
            'selected_color' => $color,
            'stock' => $stock,
            'is_active' => $isActive,
            'is_available' => $isAvailable,
            'variant_valid' => $sizeValid && $colorValid,
            'subtotal' => $subtotal,
        ]);

        $total += $subtotal;
    }

    $missingIds = array_values(array_unique($missingIds));

    return compact(
        'cart',
        'items',
        'missingLines',
        'missingIds',
        'total',
        'canCheckout'
    );
}

function cart_product_image_url(mixed $image, string $basePath = '../'): string
{
    $path = trim(str_replace('\\', '/', (string)$image));
    $basePath = rtrim($basePath, '/') . '/';

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

function cart_flash(string $type, string $message): void
{
    start_secure_session();

    $_SESSION['cart_page_flash'] = [
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => $message,
    ];
}

function pull_cart_flash(): ?array
{
    start_secure_session();

    $flash = $_SESSION['cart_page_flash'] ?? $_SESSION['cart_flash'] ?? null;
    unset($_SESSION['cart_page_flash'], $_SESSION['cart_flash']);

    return is_array($flash) ? $flash : null;
}

/** @return array<string,string> */
function order_status_labels(): array
{
    return [
        'pending' => 'Chá» xÃ¡c nháº­n',
        'confirmed' => 'ÄÃ£ xÃ¡c nháº­n',
        'shipping' => 'Äang giao',
        'completed' => 'HoÃ n thÃ nh',
        'cancelled' => 'ÄÃ£ há»§y',
    ];
}