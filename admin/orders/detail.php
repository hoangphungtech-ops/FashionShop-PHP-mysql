<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cart.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

require_admin(app_url('auth/login.php'));

$orderId = input_int($_GET, 'id');
$statusLabels = order_status_labels();
$allowedStatuses = array_keys($statusLabels);

if ($orderId === null) {
    http_response_code(404);
    $order = null;
    $items = [];
} else {
    if (is_post_request()) {
        $csrfToken = $_POST['_csrf_token'] ?? null;
        $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';

        if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
            admin_flash('error', 'Phiên cập nhật đã hết hạn. Vui lòng thử lại.');
        } elseif (!in_array($status, $allowedStatuses, true)) {
            admin_flash('error', 'Trạng thái đơn hàng không hợp lệ.');
        } else {
            try {
                $updateStatement = $pdo->prepare(
                    'UPDATE orders SET status = :status WHERE id = :id'
                );
                $updateStatement->execute([':status' => $status, ':id' => $orderId]);
                admin_flash(
                    $updateStatement->rowCount() > 0 ? 'success' : 'error',
                    $updateStatement->rowCount() > 0
                        ? 'Đã cập nhật trạng thái đơn hàng.'
                        : 'Đơn hàng không tồn tại hoặc trạng thái không thay đổi.'
                );
            } catch (PDOException $exception) {
                error_log('[admin-order-status] Update failed: ' . $exception->getMessage());
                admin_flash('error', 'Chưa thể cập nhật trạng thái đơn hàng.');
            }
        }

        safe_redirect('detail.php?id=' . $orderId, 'index.php', 303);
    }

    try {
        $orderStatement = $pdo->prepare(
            'SELECT id, user_id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders WHERE id = :id LIMIT 1'
        );
        $orderStatement->execute([':id' => $orderId]);
        $order = $orderStatement->fetch(PDO::FETCH_ASSOC) ?: null;
        $items = [];

        if ($order !== null) {
            $itemStatement = $pdo->prepare(
                'SELECT oi.product_id, oi.price, oi.quantity, p.name AS product_name, p.image
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = :order_id ORDER BY oi.id'
            );
            $itemStatement->execute([':order_id' => $orderId]);
            $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        error_log('[admin-order-detail] Cannot load order: ' . $exception->getMessage());
        http_response_code(500);
        $order = null;
        $items = [];
    }
}

$flash = pull_admin_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng | Fashion Shop Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        .container { width: 94%; max-width: 1120px; margin: 35px auto; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
        .box { padding: 24px; margin: 20px 0; background: #fff; border: 1px solid #e0e5e0; }
        .notice { padding: 13px 16px; border-left: 4px solid #994b43; background: #fff1ef; }
        .notice--success { border-color: #3e765c; background: #edf6f0; }
        .info { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px 28px; }
        .status-form { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        .status-form label { display: flex; flex-direction: column; gap: 7px; font-weight: 700; }
        select, button { min-height: 42px; padding: 8px 12px; border: 1px solid #cbd5cd; background: #fff; }
        button { border-color: #263126; background: #263126; color: #fff; cursor: pointer; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e3e7e4; text-align: left; }
        th { background: #eef2ee; }
        .product { display: flex; align-items: center; gap: 12px; }
        .product img { width: 58px; height: 72px; object-fit: cover; background: #eef1ee; }
        .total { text-align: right; font-size: 21px; font-weight: 700; }
        .back { color: #315e4c; }
        @media (max-width: 650px) { .info { grid-template-columns: 1fr; } .box { padding: 17px; } }
    </style>
</head>
<body>
<main class="container">
    <div class="top"><h1>Chi tiết đơn hàng<?= $order !== null ? ' #' . (int) $order['id'] : '' ?></h1><a class="back" href="index.php">← Danh sách đơn</a></div>
    <?php if ($flash !== null): ?><div class="notice <?= ($flash['type'] ?? '') === 'success' ? 'notice--success' : '' ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <?php if ($order === null): ?>
        <section class="box"><h2>Không tìm thấy đơn hàng</h2><p>Đơn hàng không tồn tại hoặc chưa thể tải.</p></section>
    <?php else: ?>
        <section class="box">
            <h2>Thông tin khách hàng</h2>
            <div class="info">
                <span><strong>Khách hàng:</strong> <?= e($order['receiver_name']) ?></span>
                <span><strong>Điện thoại:</strong> <?= e($order['phone']) ?></span>
                <span><strong>Địa chỉ:</strong> <?= e($order['address']) ?></span>
                <span><strong>Ngày đặt:</strong> <?= e($order['created_at']) ?></span>
                <span><strong>Trạng thái:</strong> <?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>
            <form class="status-form" method="post" action="detail.php?id=<?= (int) $order['id'] ?>">
                <?= csrf_field() ?>
                <label for="status">Cập nhật trạng thái
                    <select id="status" name="status">
                        <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                            <option value="<?= e($statusValue) ?>" <?= $order['status'] === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Lưu trạng thái</button>
            </form>
        </section>
        <section class="box table-wrap">
            <h2>Sản phẩm trong đơn</h2>
            <table>
                <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $subtotal = (float) $item['price'] * (int) $item['quantity']; ?>
                    <tr>
                        <td><div class="product"><img src="<?= e(admin_product_image_url($item['image'])) ?>" alt=""><span><?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?></span></div></td>
                        <td><?= number_format((float) $item['price'], 0, ',', '.') ?>đ</td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="total">Tổng tiền: <?= number_format((float) $order['total_amount'], 0, ',', '.') ?>đ</p>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
