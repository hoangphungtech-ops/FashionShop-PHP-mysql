<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cart.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

require_admin(app_url('auth/login.php'));

$orders = [];
$error = '';

try {
    $statement = $pdo->query(
        'SELECT id, receiver_name, phone, total_amount, status, created_at
         FROM orders ORDER BY id DESC'
    );
    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('[admin-orders] Cannot list orders: ' . $exception->getMessage());
    $error = 'Chưa thể tải danh sách đơn hàng.';
}

$statusLabels = order_status_labels();
$flash = pull_admin_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng | Fashion Shop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        .header { padding: 20px 0; background: #263126; color: #fff; }
        .container { width: 94%; max-width: 1250px; margin: auto; }
        .header__inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .header a { color: #fff; }
        main { padding: 38px 0; }
        .notice { padding: 13px 16px; margin-bottom: 18px; border-left: 4px solid #994b43; background: #fff1ef; }
        .notice--success { border-color: #3e765c; background: #edf6f0; }
        .table-wrap { overflow-x: auto; background: #fff; border: 1px solid #e0e5e0; }
        table { width: 100%; min-width: 850px; border-collapse: collapse; }
        th, td { padding: 14px; border-bottom: 1px solid #e5e9e5; text-align: left; }
        th { background: #eef2ee; }
        .status { display: inline-block; padding: 6px 9px; background: #edf3ee; font-weight: 700; white-space: nowrap; }
        .detail { display: inline-block; padding: 8px 11px; background: #263126; color: #fff; text-decoration: none; white-space: nowrap; }
        .empty { padding: 35px; text-align: center; }
        @media (max-width: 600px) { .header__inner { align-items: flex-start; flex-direction: column; } h1 { font-size: 27px; } }
    </style>
</head>
<body>
<header class="header"><div class="container header__inner"><strong>FashionShop Admin</strong><a href="../index.php">← Dashboard</a></div></header>
<main class="container">
    <h1>Danh sách đơn hàng</h1>
    <?php if ($flash !== null): ?><div class="notice <?= ($flash['type'] ?? '') === 'success' ? 'notice--success' : '' ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Điện thoại</th><th>Tổng tiền</th><th>Ngày đặt</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= e($order['receiver_name']) ?></td>
                    <td><?= e($order['phone']) ?></td>
                    <td><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>đ</td>
                    <td><?= e($order['created_at']) ?></td>
                    <td><span class="status"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td><a class="detail" href="detail.php?id=<?= (int) $order['id'] ?>">Xem chi tiết</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?><tr><td colspan="7" class="empty">Chưa có đơn hàng.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
