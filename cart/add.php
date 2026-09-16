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
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Yêu cầu không hợp lệ</title></head><body>'
        . '<p>Vui lòng thêm sản phẩm từ trang chi tiết sản phẩm.</p></body></html>';
    exit;
}

$returnUrlInput = $_POST['return_url'] ?? '';
$returnUrl = is_string($returnUrlInput) && is_safe_redirect_target($returnUrlInput)
    ? $returnUrlInput
    : '../products/index.php';

$csrfToken = $_POST['_csrf_token'] ?? null;

if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
    redirectAfterCartAdd('error', 'Phiên thêm vào giỏ đã hết hạn. Vui lòng thử lại.', $returnUrl);
}

$productId = input_int($_POST, 'product_id');
$requestedQuantity = input_int($_POST, 'quantity', 1, 9999);
$requestedSize = trim((string)($_POST['size'] ?? ''));
$requestedColor = trim((string)($_POST['color'] ?? ''));

if ($productId === null || $requestedQuantity === null) {
    redirectAfterCartAdd('error', 'Sản phẩm hoặc số lượng không hợp lệ.', $returnUrl);
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
    redirectAfterCartAdd('error', 'Không thể thêm sản phẩm lúc này. Vui lòng thử lại sau.', $returnUrl);
}

if ($product === null) {
    redirectAfterCartAdd('error', 'Sản phẩm không tồn tại hoặc đã ngừng bán.', $returnUrl);
}

$stock = max(0, (int)$product['stock']);

if ($stock < 1) {
    redirectAfterCartAdd('error', 'Sản phẩm đã hết hàng.', $returnUrl);
}

$allowedSizes = cart_option_list($product['size'] ?? '');
$allowedColors = cart_option_list($product['color'] ?? '');

$size = '';
$color = '';

if ($allowedSizes !== []) {
    $size = cart_match_option($requestedSize, $allowedSizes) ?? '';

    if ($size === '') {
        redirectAfterCartAdd('error', 'Vui lòng chọn kích cỡ hợp lệ.', $returnUrl);
    }
}

if ($allowedColors !== []) {
    if ($requestedColor === '' && count($allowedColors) === 1) {
        $color = $allowedColors[0];
    } else {
        $color = cart_match_option($requestedColor, $allowedColors) ?? '';
    }

    if ($color === '') {
        redirectAfterCartAdd('error', 'Vui lòng chọn màu sắc hợp lệ.', $returnUrl);
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
        'Tổng số lượng các phân loại trong giỏ vượt quá tồn kho hiện có.',
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
    $variant[] = 'màu ' . $color;
}

$message = 'Đã thêm '
    . $requestedQuantity
    . ' × '
    . (string)$product['name'];

if ($variant !== []) {
    $message .= ' (' . implode(', ', $variant) . ')';
}

$message .= ' vào giỏ hàng.';

redirectAfterCartAdd('success', $message, $returnUrl);