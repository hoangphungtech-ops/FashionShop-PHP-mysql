<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * Normalize the one supported cart shape: [product_id => quantity].
 * Legacy item arrays are converted in-place so old sessions keep working.
 *
 * @return array<int, int>
 */
function cart_quantities(): array
{
    start_secure_session();

    $rawCart = $_SESSION['cart'] ?? [];
    $cart = [];

    if (is_array($rawCart)) {
        foreach ($rawCart as $key => $value) {
            $productId = filter_var($key, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $quantityValue = is_array($value) ? ($value['quantity'] ?? null) : $value;
            $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 9999],
            ]);

            if ($productId !== false && $quantity !== false) {
                $cart[(int) $productId] = (int) $quantity;
            }
        }
    }

    $_SESSION['cart'] = $cart;

    return $cart;
}

function cart_quantity_count(?array $cart = null): int
{
    $cart ??= cart_quantities();

    return array_sum($cart);
}

/**
 * Load current product data for the cart. Prices and stock never come from
 * the session or browser.
 *
 * @return array{
 *     cart: array<int, int>,
 *     items: list<array<string, mixed>>,
 *     missingIds: list<int>,
 *     total: float,
 *     canCheckout: bool
 * }
 */
function load_cart(PDO $pdo, bool $forUpdate = false): array
{
    $cart = cart_quantities();
    $items = [];
    $missingIds = [];
    $total = 0.0;
    $canCheckout = $cart !== [];

    if ($cart === []) {
        return compact('cart', 'items', 'missingIds', 'total', 'canCheckout');
    }

    $ids = array_keys($cart);
    sort($ids, SORT_NUMERIC);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id, name, price, stock, image, status
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
        $productsById[(int) $product['id']] = $product;
    }

    foreach ($ids as $productId) {
        if (!isset($productsById[$productId])) {
            $missingIds[] = $productId;
            $canCheckout = false;
            continue;
        }

        $product = $productsById[$productId];
        $quantity = $cart[$productId];
        $stock = max(0, (int) ($product['stock'] ?? 0));
        $isActive = (int) ($product['status'] ?? 0) === 1;
        $price = (float) ($product['price'] ?? 0);
        $isAvailable = $isActive && $stock > 0 && $quantity <= $stock;
        $subtotal = $price * $quantity;

        if (!$isAvailable) {
            $canCheckout = false;
        }

        $items[] = $product + [
            'quantity' => $quantity,
            'stock' => $stock,
            'is_active' => $isActive,
            'is_available' => $isAvailable,
            'subtotal' => $subtotal,
        ];
        $total += $subtotal;
    }

    return compact('cart', 'items', 'missingIds', 'total', 'canCheckout');
}

function cart_product_image_url(mixed $image, string $basePath = '../'): string
{
    $path = trim(str_replace('\\', '/', (string) $image));
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

/** @return array<string, string> */
function order_status_labels(): array
{
    return [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];
}
