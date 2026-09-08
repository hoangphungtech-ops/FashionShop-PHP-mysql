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

$cart =& fs_cart();

unset($cart[$key]);

header('Location: index.php');
exit;