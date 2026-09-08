<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/full_cart.php';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !fs_cart_verify_token(
        $_POST['csrf_token'] ?? null
    )
) {
    http_response_code(403);
    exit('Yêu cầu không hợp lệ.');
}

$key =
    (string)(
        $_POST['key']
        ?? ''
    );

$quantity =
    max(
        1,
        (int)(
            $_POST['quantity']
            ?? 1
        )
    );

$cart =& fs_cart();

if (isset($cart[$key])) {

    $quantity =
        min(
            $quantity,
            (int)$cart[$key]['stock']
        );

    $cart[$key]['quantity'] =
        $quantity;
}

header('Location: index.php');
exit;