<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

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

    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Yêu cầu không hợp lệ</title></head><body>'
        . '<main style="max-width:640px;margin:70px auto;padding:24px;font-family:Arial,sans-serif">'
        . '<h1>Yêu cầu không hợp lệ</h1>'
        . '<p>Vui lòng thêm sản phẩm từ trang chi tiết sản phẩm.</p>'
        . '<a href="../products/index.php">Quay lại sản phẩm</a>'
        . '</main></body></html>';
    exit;
}

$returnUrlInput = $_POST['return_url'] ?? '';
$returnUrl = is_string($returnUrlInput) && is_safe_redirect_target($returnUrlInput)
    ? $returnUrlInput
    : '../products/index.php';
$csrfToken = $_POST['_csrf_token'] ?? null;

if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
    redirectAfterCartAdd(
        'error',
        'Phiên thêm vào giỏ đã hết hạn. Vui lòng thử lại.',
        $returnUrl
    );
}

$productId = input_int($_POST, 'product_id');
$requestedQuantity = input_int($_POST, 'quantity', 1, 9999);

if ($productId === null || $requestedQuantity === null) {
    redirectAfterCartAdd(
        'error',
        'Sản phẩm hoặc số lượng không hợp lệ.',
        $returnUrl
    );
}

try {
    $productStatement = $pdo->prepare(
        'SELECT id, name, stock
         FROM products
         WHERE id = :id
           AND status = :active_status
         LIMIT 1'
    );
    $productStatement->execute([
        ':id' => $productId,
        ':active_status' => 1,
    ]);
    $product = $productStatement->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $exception) {
    error_log('[cart-add] Cannot load product: ' . $exception->getMessage());

    redirectAfterCartAdd(
        'error',
        'Không thể thêm sản phẩm lúc này. Vui lòng thử lại sau.',
        $returnUrl
    );
}

if ($product === null) {
    redirectAfterCartAdd(
        'error',
        'Sản phẩm không tồn tại hoặc đã ngừng bán.',
        $returnUrl
    );
}

$stock = max(0, (int) ($product['stock'] ?? 0));

if ($stock < 1) {
    redirectAfterCartAdd(
        'error',
        'Sản phẩm đã hết hàng.',
        $returnUrl
    );
}

if ($requestedQuantity > $stock) {
    redirectAfterCartAdd(
        'error',
        'Số lượng yêu cầu vượt quá tồn kho hiện có.',
        $returnUrl
    );
}

$cart = $_SESSION['cart'] ?? [];

if (!is_array($cart)) {
    $cart = [];
}

$currentQuantityValue = $cart[$productId] ?? 0;
$currentQuantity = filter_var($currentQuantityValue, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$currentQuantity = $currentQuantity !== false ? (int) $currentQuantity : 0;
$newQuantity = $currentQuantity + $requestedQuantity;

if ($newQuantity > $stock) {
    redirectAfterCartAdd(
        'error',
        'Tổng số lượng trong giỏ vượt quá tồn kho. Hiện bạn đã có '
        . $currentQuantity
        . ' sản phẩm này trong giỏ.',
        $returnUrl
    );
}

$cart[$productId] = $newQuantity;
$_SESSION['cart'] = $cart;

redirectAfterCartAdd(
    'success',
    'Đã thêm '
    . $requestedQuantity
    . ' × '
    . (string) $product['name']
    . ' vào giỏ hàng.',
    $returnUrl
);
