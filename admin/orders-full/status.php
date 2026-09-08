<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (function_exists('require_admin')) {
    require_admin();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id =
    (int)(
        $_POST['id']
        ?? 0
    );

$status =
    (string)(
        $_POST['status']
        ?? ''
    );

$allowed = [
    'pending',
    'confirmed',
    'preparing',
    'shipping',
    'delivered',
    'cancelled',
];

if (
    !in_array(
        $status,
        $allowed,
        true
    )
) {
    exit('Trạng thái không hợp lệ.');
}

$stmt = $pdo->prepare(
    'UPDATE fs_orders
     SET status = :status
     WHERE id = :id'
);

$stmt->execute([
    ':status' => $status,
    ':id' => $id,
]);

header(
    'Location: detail.php?id='
    . $id
);

exit;