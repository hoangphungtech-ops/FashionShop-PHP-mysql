<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

function redirectAfterCartAdd(string $type, string $message, string $returnUrl): never
{
    $_SESSION['cart_flash'] = [
        'type' => in_array($type, ['success', 'error'], true) ? $type : 'error',
        'message' => $message,
    ];

    safe_redirect($returnUrl, '../products/index.php', 303);
}

if (!is_post_request()) {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>YÃªu cáº§u khÃ´ng há»£p lá»‡</title></head><body>'
        . '<p>Vui lÃ²ng thÃªm sáº£n pháº©m tá»« trang chi tiáº¿t sáº£n pháº©m.</p></body></html>';
    exit;
}

$returnUrlInput = $_POST['return_url'] ?? '';
$returnUrl = is_string($returnUrlInput) && is_safe_redirect_target($returnUrlInput)
    ? $returnUrlInput
    : '../products/index.php';

$csrfToken = $_POST['_csrf_token'] ?? null;

if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
    redirectAfterCartAdd('error', 'PhiÃªn thÃªm vÃ o giá» Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng thá»­ láº¡i.', $returnUrl);
}

$productId = input_int($_POST, 'product_id');
$requestedQuantity = input_int($_POST, 'quantity', 1, 9999);
$requestedSize = trim((string)($_POST['size'] ?? ''));
$requestedColor = trim((string)($_POST['color'] ?? ''));

if ($productId === null || $requestedQuantity === null) {
    redirectAfterCartAdd('error', 'Sáº£n pháº©m hoáº·c sá»‘ lÆ°á»£ng khÃ´ng há»£p lá»‡.', $returnUrl);
}

try {
    $statement = $pdo->prepare(
        'SELECT id, name, stock, status, size, color, material
         FROM products
         WHERE id = :id
           AND status = 1
         LIMIT 1'
    );
    $statement->execute([':id' => $productId]);
    $product = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $exception) {
    error_log('[cart-add] Cannot load product: ' . $exception->getMessage());
    redirectAfterCartAdd('error', 'KhÃ´ng thá»ƒ thÃªm sáº£n pháº©m lÃºc nÃ y. Vui lÃ²ng thá»­ láº¡i sau.', $returnUrl);
}

if ($product === null) {
    redirectAfterCartAdd('error', 'Sáº£n pháº©m khÃ´ng tá»“n táº¡i hoáº·c Ä‘Ã£ ngá»«ng bÃ¡n.', $returnUrl);
}

$stock = max(0, (int)$product['stock']);

if ($stock < 1) {
    redirectAfterCartAdd('error', 'Sáº£n pháº©m Ä‘Ã£ háº¿t hÃ ng.', $returnUrl);
}

$allowedSizes = cart_option_list($product['size'] ?? '');
$allowedColors = cart_option_list($product['color'] ?? '');

$size = '';
$color = '';

if ($allowedSizes !== []) {
    $size = cart_match_option($requestedSize, $allowedSizes) ?? '';

    if ($size === '') {
        redirectAfterCartAdd('error', 'Vui lÃ²ng chá»n kÃ­ch cá»¡ há»£p lá»‡.', $returnUrl);
    }
}

if ($allowedColors !== []) {
    if ($requestedColor === '' && count($allowedColors) === 1) {
        $color = $allowedColors[0];
    } else {
        $color = cart_match_option($requestedColor, $allowedColors) ?? '';
    }

    if ($color === '') {
        redirectAfterCartAdd('error', 'Vui lÃ²ng chá»n mÃ u sáº¯c há»£p lá»‡.', $returnUrl);
    }
}

$cart = cart_quantities();
$lineKey = cart_line_key($productId, $size, $color);
$currentLineQuantity = isset($cart[$lineKey])
    ? (int)$cart[$lineKey]['quantity']
    : 0;

$productQuantityInCart = 0;

foreach ($cart as $line) {
    if ((int)$line['product_id'] === $productId) {
        $productQuantityInCart += (int)$line['quantity'];
    }
}

if (($productQuantityInCart + $requestedQuantity) > $stock) {
    redirectAfterCartAdd(
        'error',
        'Tá»•ng sá»‘ lÆ°á»£ng cÃ¡c phÃ¢n loáº¡i trong giá» vÆ°á»£t quÃ¡ tá»“n kho hiá»‡n cÃ³.',
        $returnUrl
    );
}

$cart[$lineKey] = [
    'product_id' => $productId,
    'quantity' => $currentLineQuantity + $requestedQuantity,
    'size' => $size,
    'color' => $color,
];

$_SESSION['cart'] = $cart;

$variant = [];

if ($size !== '') {
    $variant[] = 'size ' . $size;
}

if ($color !== '') {
    $variant[] = 'mÃ u ' . $color;
}

$message = 'ÄÃ£ thÃªm '
    . $requestedQuantity
    . ' Ã— '
    . (string)$product['name'];

if ($variant !== []) {
    $message .= ' (' . implode(', ', $variant) . ')';
}

$message .= ' vÃ o giá» hÃ ng.';

redirectAfterCartAdd('success', $message, $returnUrl);