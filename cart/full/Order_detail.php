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
    $userId = filter_var($userIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userId = $userId !== false ? (int)$userId : null;

    $recentOrderIds = array_map(
        'intval',
        is_array($_SESSION['recent_order_ids'] ?? null) ? $_SESSION['recent_order_ids'] : []
    );

    if ($userId !== null) {
        $orderStatement = $pdo->prepare(
            'SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $orderStatement->execute([':id' => $orderId, ':user_id' => $userId]);
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
            'SELECT oi.product_id, oi.price, oi.quantity, p.name AS product_name, p.image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id'
        );
        $itemStatement->execute([':order_id' => $orderId]);
        $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        http_response_code(404);
    }
}

$statusLabels = order_status_labels();
$status = (string)($order['status'] ?? '');
$statusStep = match ($status) {
    'pending' => 1,
    'confirmed' => 2,
    'shipping' => 3,
    'completed' => 4,
    default => 0,
};
$formatDate = static function (mixed $date): string {
    $timestamp = strtotime((string)$date);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : (string)$date;
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Chi tiết đơn hàng tại Fashion Shop.">
    <title>Chi tiết đơn hàng | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        *{box-sizing:border-box}
        :root{--g:#103b2f;--ink:#18251f;--muted:#6f7b74;--line:#e3e7e2;--soft:#f6f7f3;--shadow:0 16px 38px rgba(25,43,35,.07)}
        body.order-detail-page{margin:0;background:#f8f8f5;color:var(--ink)}
        .detail-wrap{max-width:1180px;margin:0 auto;padding:36px 24px 72px}
        .detail-back{display:inline-block;margin-bottom:18px;color:#6f7b74;font-size:12px;text-decoration:none}
        .detail-title{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px}
        .detail-title p{margin:0 0 5px;color:#7d9588;font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .detail-title h1{margin:0;color:var(--g);font-size:clamp(32px,4vw,48px);letter-spacing:-.035em}
        .detail-status{padding:7px 11px;border-radius:999px;background:#eef4f0;color:var(--g);font-size:11px;font-weight:800}
        .detail-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:22px;align-items:start}
        .detail-card{padding:24px;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:var(--shadow)}
        .detail-card h2{margin:0 0 18px;font-size:20px}
        .detail-info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .detail-info div{padding:13px;border-radius:11px;background:#f8f9f6}
        .detail-info span{display:block;color:#879189;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .detail-info strong{display:block;margin-top:5px;font-size:12px;line-height:1.5}
        .detail-products{margin-top:22px}
        .detail-product{display:grid;grid-template-columns:68px minmax(0,1fr) auto;gap:14px;align-items:center;padding:14px 0;border-top:1px solid #edf0ec}
        .detail-product img{width:68px;height:82px;object-fit:cover;border-radius:10px;background:#eef1ed}
        .detail-product h3{margin:0 0 5px;font-size:14px}.detail-product p{margin:0;color:#7b8780;font-size:11px}
        .detail-product__price{text-align:right}.detail-product__price span{display:block;color:#7b8780;font-size:10px}.detail-product__price strong{display:block;margin-top:4px;font-size:14px}
        .detail-summary{position:sticky;top:110px}
        .detail-summary__row{display:flex;justify-content:space-between;gap:15px;padding:11px 0;border-bottom:1px solid #edf0ec;font-size:12px}
        .detail-total{display:flex;justify-content:space-between;gap:15px;padding:20px 0 8px}.detail-total span{font-weight:800}.detail-total strong{color:var(--g);font-size:23px}
        .progress{display:grid;grid-template-columns:repeat(4,1fr);margin:24px 0 5px}
        .progress span{position:relative;padding-top:16px;color:#9aa49e;font-size:8px;text-align:center}
        .progress span:before{content:"";position:absolute;top:3px;left:50%;z-index:2;width:8px;height:8px;border:2px solid #b9c5be;border-radius:50%;background:#fff;transform:translateX(-50%)}
        .progress span:not(:last-child):after{content:"";position:absolute;top:7px;left:50%;width:100%;height:1px;background:#cfd7d2}
        .progress span.active{color:var(--g);font-weight:700}.progress span.active:before{border-color:var(--g);background:var(--g)}.progress span.active:not(:last-child):after{background:var(--g)}
        .detail-empty{padding:40px;border:1px dashed #cad4ce;border-radius:18px;background:#fff;text-align:center}
        @media(max-width:850px){.detail-grid{grid-template-columns:1fr}.detail-summary{position:static}}
        @media(max-width:600px){.detail-info{grid-template-columns:1fr}.detail-title{align-items:flex-start;flex-direction:column}.detail-product{grid-template-columns:58px minmax(0,1fr)}.detail-product img{width:58px;height:70px}.detail-product__price{grid-column:2;text-align:left}}
    </style>
</head>
<body class="site-body order-detail-page">
<?php
$siteBasePath = '../';
$currentPage = 'cart';
$currentCategory = 0;
require __DIR__ . '/../includes/header.php';
?>
<main class="detail-wrap">
    <a class="detail-back" href="history.php">← Quay lại lịch sử đơn hàng</a>

    <?php if ($order === null): ?>
        <div class="detail-empty">
            <h1>Không tìm thấy đơn hàng</h1>
            <p>Đơn không tồn tại hoặc bạn không có quyền xem.</p>
        </div>
    <?php else: ?>
        <div class="detail-title">
            <div>
                <p>Order details</p>
                <h1>Đơn hàng #FS<?= (int)$order['id'] ?></h1>
            </div>
            <span class="detail-status"><?= e($statusLabels[$status] ?? $status) ?></span>
        </div>

        <div class="detail-grid">
            <div>
                <section class="detail-card">
                    <h2>Thông tin giao hàng</h2>
                    <div class="detail-info">
                        <div><span>Người nhận</span><strong><?= e($order['receiver_name']) ?></strong></div>
                        <div><span>Điện thoại</span><strong><?= e($order['phone']) ?></strong></div>
                        <div><span>Địa chỉ</span><strong><?= e($order['address']) ?></strong></div>
                        <div><span>Ngày đặt</span><strong><?= e($formatDate($order['created_at'])) ?></strong></div>
                    </div>

                    <?php if ($status !== 'cancelled'): ?>
                        <div class="progress" aria-label="Tiến trình đơn hàng">
                            <?php foreach (['Đặt hàng','Xác nhận','Đang giao','Hoàn thành'] as $index => $label): ?>
                                <span class="<?= ($index + 1) <= $statusStep ? 'active' : '' ?>"><?= e($label) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="detail-card detail-products">
                    <h2>Sản phẩm</h2>
                    <?php foreach ($items as $item): ?>
                        <?php $subtotal = (float)$item['price'] * (int)$item['quantity']; ?>
                        <article class="detail-product">
                            <img src="<?= e(cart_product_image_url($item['image'])) ?>" alt="">
                            <div>
                                <h3><?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?></h3>
                                <p>Số lượng: <?= (int)$item['quantity'] ?> · <?= number_format((float)$item['price'], 0, ',', '.') ?>đ / sản phẩm</p>
                            </div>
                            <div class="detail-product__price">
                                <span>Thành tiền</span>
                                <strong><?= number_format($subtotal, 0, ',', '.') ?>đ</strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </div>

            <aside class="detail-card detail-summary">
                <h2>Tóm tắt đơn hàng</h2>
                <div class="detail-summary__row"><span>Trạng thái</span><strong><?= e($statusLabels[$status] ?? $status) ?></strong></div>
                <div class="detail-summary__row"><span>Mã đơn</span><strong>#FS<?= (int)$order['id'] ?></strong></div>
                <div class="detail-total"><span>Tổng cộng</span><strong><?= number_format((float)$order['total_amount'], 0, ',', '.') ?>đ</strong></div>
            </aside>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
