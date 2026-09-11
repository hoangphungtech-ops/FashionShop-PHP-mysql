<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

$orderId = input_int($_GET, 'id');

if ($orderId === null) {
    http_response_code(404);
    $order = null;
    $items = [];
} else {
    $sessionUser = $_SESSION['user'] ?? null;
    $userIdValue = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
    $userId = filter_var($userIdValue, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $userId = $userId !== false ? (int)$userId : null;

    $recentOrderIds = array_map(
        'intval',
        is_array($_SESSION['recent_order_ids'] ?? null)
            ? $_SESSION['recent_order_ids']
            : []
    );

    if ($userId !== null) {
        $orderStatement = $pdo->prepare(
            'SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $orderStatement->execute([
            ':id' => $orderId,
            ':user_id' => $userId,
        ]);
    } elseif (in_array($orderId, $recentOrderIds, true)) {
        $orderStatement = $pdo->prepare(
            'SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders
             WHERE id = :id AND user_id IS NULL
             LIMIT 1'
        );
        $orderStatement->execute([':id' => $orderId]);
    } else {
        $orderStatement = null;
    }

    $order = $orderStatement?->fetch(PDO::FETCH_ASSOC) ?: null;
    $items = [];

    if ($order !== null) {
        $itemStatement = $pdo->prepare(
            "SELECT
                oi.product_id,
                oi.price,
                oi.quantity,
                oi.selected_size,
                oi.selected_color,
                oi.material,
                COALESCE(NULLIF(oi.product_name, ''), p.name, 'Sáº£n pháº©m Ä‘Ã£ xÃ³a') AS product_name,
                COALESCE(NULLIF(oi.product_image, ''), p.image, '') AS product_image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id"
        );
        $itemStatement->execute([':order_id' => $orderId]);
        $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        http_response_code(404);
    }
}

$statusLabels = order_status_labels();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiáº¿t Ä‘Æ¡n hÃ ng | Fashion Shop</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; background:#f5f7f5; color:#263126; }
        .container { width:92%; max-width:1050px; margin:40px auto; }
        .box { padding:24px; margin-bottom:20px; background:#fff; border:1px solid #e0e6e1; }
        .info { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 25px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; min-width:760px; border-collapse:collapse; }
        th,td { padding:12px; border-bottom:1px solid #e3e7e4; text-align:left; vertical-align:top; }
        .product { display:flex; align-items:center; gap:12px; min-width:220px; }
        .product img { width:58px; height:72px; object-fit:cover; background:#eef1ee; }
        .variant { display:block; margin-top:4px; color:#647068; font-size:12px; }
        .total { margin-top:20px; text-align:right; font-size:21px; font-weight:700; }
        .button { display:inline-block; padding:10px 15px; background:#263126; color:#fff; text-decoration:none; }
        @media(max-width:650px){ .info{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<main class="container">
    <?php if ($order === null): ?>
        <div class="box"><h1>KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n hÃ ng</h1><p>ÄÆ¡n khÃ´ng tá»“n táº¡i hoáº·c báº¡n khÃ´ng cÃ³ quyá»n xem.</p></div>
    <?php else: ?>
        <h1>Chi tiáº¿t Ä‘Æ¡n hÃ ng #<?= (int)$order['id'] ?></h1>

        <section class="box">
            <h2>ThÃ´ng tin giao hÃ ng</h2>
            <div class="info">
                <span><strong>NgÆ°á»i nháº­n:</strong> <?= e($order['receiver_name']) ?></span>
                <span><strong>Äiá»‡n thoáº¡i:</strong> <?= e($order['phone']) ?></span>
                <span><strong>Äá»‹a chá»‰:</strong> <?= e($order['address']) ?></span>
                <span><strong>NgÃ y Ä‘áº·t:</strong> <?= e($order['created_at']) ?></span>
                <span><strong>Tráº¡ng thÃ¡i:</strong> <?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>
        </section>

        <section class="box table-wrap">
            <h2>Sáº£n pháº©m</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sáº£n pháº©m</th>
                        <th>PhÃ¢n loáº¡i</th>
                        <th>ÄÆ¡n giÃ¡</th>
                        <th>Sá»‘ lÆ°á»£ng</th>
                        <th>ThÃ nh tiá»n</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $subtotal = (float)$item['price'] * (int)$item['quantity']; ?>
                    <tr>
                        <td>
                            <div class="product">
                                <img src="<?= e(cart_product_image_url($item['product_image'])) ?>" alt="">
                                <span><?= e($item['product_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if (($item['selected_size'] ?? '') !== ''): ?>
                                <span class="variant">KÃ­ch cá»¡: <?= e($item['selected_size']) ?></span>
                            <?php endif; ?>
                            <?php if (($item['selected_color'] ?? '') !== ''): ?>
                                <span class="variant">MÃ u: <?= e($item['selected_color']) ?></span>
                            <?php endif; ?>
                            <?php if (($item['material'] ?? '') !== ''): ?>
                                <span class="variant">Cháº¥t liá»‡u: <?= e($item['material']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((float)$item['price'], 0, ',', '.') ?>Ä‘</td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= number_format($subtotal, 0, ',', '.') ?>Ä‘</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p class="total">Tá»•ng tiá»n: <?= number_format((float)$order['total_amount'], 0, ',', '.') ?>Ä‘</p>
        </section>
    <?php endif; ?>

    <a class="button" href="history.php">â† Quay láº¡i lá»‹ch sá»­</a>
</main>
</body>
</html>