<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

$sessionUser = $_SESSION['user'] ?? null;
$userIdValue = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
$userId = filter_var($userIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = $userId !== false ? (int) $userId : null;
$recentOrderIds = array_values(array_filter(array_map(
    'intval',
    is_array($_SESSION['recent_order_ids'] ?? null) ? $_SESSION['recent_order_ids'] : []
), static fn (int $id): bool => $id > 0));
$orders = [];
$error = '';

try {
    if ($userId !== null) {
        $statement = $pdo->prepare(
            'SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders WHERE user_id = :user_id ORDER BY id DESC'
        );
        $statement->execute([':user_id' => $userId]);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($recentOrderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($recentOrderIds), '?'));
        $statement = $pdo->prepare(
            "SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders WHERE user_id IS NULL AND id IN ($placeholders) ORDER BY id DESC"
        );
        $statement->execute($recentOrderIds);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $exception) {
    error_log('[order-history] Cannot load orders: ' . $exception->getMessage());
    $error = 'Chưa thể tải lịch sử đơn hàng lúc này.';
}

$statusLabels = order_status_labels();
$successOrderId = input_int($_GET, 'order_id');
$success = ($_GET['success'] ?? '') === '1'
    && $successOrderId !== null
    && in_array($successOrderId, array_map(static fn (array $order): int => (int) $order['id'], $orders), true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mua hàng | Fashion Shop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        .container { width: 92%; max-width: 1050px; margin: 40px auto; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 25px; }
        a { color: #315e4c; }
        .notice, .order, .empty { padding: 22px; margin-bottom: 18px; background: #fff; border: 1px solid #e0e6e1; }
        .notice { border-left: 4px solid #3e765c; background: #edf6f0; }
        .notice--error { border-color: #9a4b43; background: #fff3f1; }
        .order__head, .order__meta { display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
        .order__meta { margin: 18px 0; color: #59655b; }
        .status { padding: 6px 10px; background: #edf3ee; font-weight: 700; }
        .total { font-size: 19px; font-weight: 700; }
        .button { display: inline-block; padding: 10px 15px; background: #263126; color: #fff; text-decoration: none; }
        @media (max-width: 600px) { .top { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<main class="container">
    <div class="top"><h1>Lịch sử mua hàng</h1><a href="../products/index.php">← Tiếp tục mua hàng</a></div>
    <?php if ($success): ?><div class="notice">Đặt hàng thành công. Mã đơn hàng: <strong>#<?= $successOrderId ?></strong></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="notice notice--error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <?php if ($orders === []): ?>
        <div class="empty">
            <h2>Chưa có đơn hàng</h2>
            <p><?= $userId === null ? 'Đơn hàng khách chỉ hiển thị trong đúng phiên đã đặt.' : 'Tài khoản của bạn chưa có đơn hàng nào.' ?></p>
            <a class="button" href="../products/index.php">Khám phá sản phẩm</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <article class="order">
                <div class="order__head">
                    <h2>Đơn hàng #<?= (int) $order['id'] ?></h2>
                    <span class="status"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
                </div>
                <div class="order__meta">
                    <span><strong>Người nhận:</strong> <?= e($order['receiver_name']) ?></span>
                    <span><strong>Điện thoại:</strong> <?= e($order['phone']) ?></span>
                    <span><strong>Ngày đặt:</strong> <?= e($order['created_at']) ?></span>
                </div>
                <p class="total">Tổng tiền: <?= number_format((float) $order['total_amount'], 0, ',', '.') ?>đ</p>
                <a class="button" href="Order_detail.php?id=<?= (int) $order['id'] ?>">Xem chi tiết</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
