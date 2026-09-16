<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

$sessionUser = $_SESSION['user'] ?? null;
$userIdValue = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
$userId = filter_var($userIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = $userId !== false ? (int)$userId : null;

$recentOrderIds = array_values(array_filter(array_map(
    'intval',
    is_array($_SESSION['recent_order_ids'] ?? null) ? $_SESSION['recent_order_ids'] : []
), static fn (int $id): bool => $id > 0));

$orders = [];
$orderItems = [];
$error = '';

try {
    if ($userId !== null) {
        $statement = $pdo->prepare(
            'SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders
             WHERE user_id = :user_id
             ORDER BY id DESC'
        );
        $statement->execute([':user_id' => $userId]);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($recentOrderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($recentOrderIds), '?'));
        $statement = $pdo->prepare(
            "SELECT id, receiver_name, phone, address, total_amount, status, created_at
             FROM orders
             WHERE user_id IS NULL AND id IN ($placeholders)
             ORDER BY id DESC"
        );
        $statement->execute($recentOrderIds);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($orders !== []) {
        $ids = array_map(static fn (array $order): int => (int)$order['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $itemsStatement = $pdo->prepare(
            "SELECT oi.order_id, oi.quantity, p.image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id IN ($placeholders)
             ORDER BY oi.id"
        );
        $itemsStatement->execute($ids);

        foreach ($itemsStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $orderId = (int)$row['order_id'];
            $orderItems[$orderId] ??= ['quantity' => 0, 'images' => []];
            $orderItems[$orderId]['quantity'] += max(0, (int)$row['quantity']);

            if (
                count($orderItems[$orderId]['images']) < 3
                && is_string($row['image'] ?? null)
                && trim((string)$row['image']) !== ''
            ) {
                $orderItems[$orderId]['images'][] = (string)$row['image'];
            }
        }
    }
} catch (PDOException $exception) {
    error_log('[order-history] Cannot load orders: ' . $exception->getMessage());
    $error = 'Chưa thể tải lịch sử đơn hàng lúc này.';
}

$statusLabels = order_status_labels();
$successOrderId = input_int($_GET, 'order_id');
$success = ($_GET['success'] ?? '') === '1'
    && $successOrderId !== null
    && in_array($successOrderId, array_map(static fn (array $order): int => (int)$order['id'], $orders), true);

$formatOrderDate = static function (mixed $date): string {
    $timestamp = strtotime((string)$date);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : (string)$date;
};

$statusClass = static function (string $status): string {
    return in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)
        ? $status
        : 'pending';
};

$statusStep = static function (string $status): int {
    return match ($status) {
        'pending' => 1,
        'confirmed' => 2,
        'shipping' => 3,
        'completed' => 4,
        default => 0,
    };
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Theo dõi lịch sử và trạng thái đơn hàng tại Fashion Shop.">
    <title>Lịch sử đơn hàng | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        *{box-sizing:border-box}
        :root{--g:#103b2f;--ink:#18251f;--muted:#6f7b74;--line:#e3e7e2;--soft:#f6f7f3;--shadow:0 16px 38px rgba(25,43,35,.07)}
        body.order-history-page{margin:0;background:#f8f8f5;color:var(--ink)}
        .history-hero{padding:36px 0 30px;background:radial-gradient(circle at 85% 20%,rgba(174,196,182,.23),transparent 28%),linear-gradient(180deg,#fbfbf8,#f2f4ef);border-bottom:1px solid var(--line)}
        .history-hero .site-container,.history-section .site-container{max-width:1240px}
        .history-hero__back{display:inline-block;margin-bottom:18px;color:#75827a;font-size:12px;text-decoration:none}
        .history-hero p{margin:0 0 7px;color:#7d9588;font-size:10px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
        .history-hero h1{margin:0;color:var(--g);font-size:clamp(38px,5vw,60px);line-height:1;letter-spacing:-.04em}
        .history-hero__desc{margin-top:10px!important;color:var(--muted)!important;font-size:14px!important;font-weight:400!important;letter-spacing:0!important;text-transform:none!important}
        .history-section{padding:32px 0 72px}
        .history-notice{margin-bottom:16px;padding:13px 15px;border:1px solid #cce1d5;border-radius:12px;background:#edf7f1;color:#285b43;font-size:13px}
        .history-notice--error{border-color:#efd6d2;background:#fff4f3;color:#8b4841}
        .history-list{display:grid;gap:14px}
        .history-card{padding:20px;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:var(--shadow)}
        .history-card__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding-bottom:15px;border-bottom:1px solid #edf0ec}
        .history-card__identity{display:flex;align-items:center;gap:13px;min-width:0}
        .history-card__images{display:flex;flex:0 0 auto}
        .history-card__images img,.history-card__placeholder{width:46px;height:56px;object-fit:cover;border:2px solid #fff;border-radius:10px;background:#eef1ed}
        .history-card__images img+img{margin-left:-13px}
        .history-card__identity h2{margin:0;font-size:16px}
        .history-card__identity p{margin:4px 0 0;color:#7b8780;font-size:11px}
        .status{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}
        .status:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
        .status--pending{background:#fff5db;color:#94691c}.status--confirmed{background:#e9f1ff;color:#3268a8}.status--shipping{background:#fff0df;color:#ae6419}.status--completed{background:#e5f5ea;color:#277449}.status--cancelled{background:#fdeaea;color:#a64747}
        .history-card__body{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;padding-top:16px;align-items:end}
        .history-card__info{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .history-card__info div{padding:11px 12px;border-radius:11px;background:#f8f9f6}
        .history-card__info span{display:block;color:#879189;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .history-card__info strong{display:block;margin-top:4px;font-size:12px;line-height:1.4}
        .history-card__price{text-align:right}
        .history-card__price span{display:block;color:#7d8881;font-size:10px}
        .history-card__price strong{display:block;margin-top:3px;color:var(--g);font-size:22px}
        .progress{display:grid;grid-template-columns:repeat(4,1fr);margin-top:18px}
        .progress span{position:relative;padding-top:16px;color:#9aa49e;font-size:9px;text-align:center}
        .progress span:before{content:"";position:absolute;top:3px;left:50%;z-index:2;width:8px;height:8px;border:2px solid #b9c5be;border-radius:50%;background:#fff;transform:translateX(-50%)}
        .progress span:not(:last-child):after{content:"";position:absolute;top:7px;left:50%;width:100%;height:1px;background:#cfd7d2}
        .progress span.active{color:var(--g);font-weight:700}.progress span.active:before{border-color:var(--g);background:var(--g)}.progress span.active:not(:last-child):after{background:var(--g)}
        .history-card__actions{display:flex;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid #edf0ec}
        .history-card__actions a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 15px;border-radius:10px;background:var(--g);color:#fff;font-size:11px;font-weight:800;text-decoration:none}
        .history-empty{padding:38px;border:1px dashed #cad4ce;border-radius:18px;background:#fff;text-align:center}
        .history-empty h2{margin:0 0 8px}.history-empty p{color:var(--muted)}
        .history-empty a{display:inline-flex;margin-top:10px;padding:12px 16px;border-radius:10px;background:var(--g);color:#fff;text-decoration:none;font-weight:800}
        @media(max-width:850px){.history-card__body{grid-template-columns:1fr}.history-card__price{text-align:left}.history-card__info{grid-template-columns:1fr}}
        @media(max-width:600px){.history-card__head{flex-direction:column}.history-section{padding-bottom:48px}}
    </style>
</head>
<body class="site-body order-history-page">
<?php
$siteBasePath = '../';
$currentPage = 'cart';
$currentCategory = 0;
require __DIR__ . '/../includes/header.php';
?>
<main>
    <section class="history-hero">
        <div class="site-container">
            <a class="history-hero__back" href="index.php">← Quay lại giỏ hàng</a>
            <p>Order tracking</p>
            <h1>Lịch sử đơn hàng</h1>
            <p class="history-hero__desc">Theo dõi trạng thái, thông tin giao hàng và các đơn bạn đã đặt.</p>
        </div>
    </section>

    <section class="history-section">
        <div class="site-container">
            <?php if ($success): ?>
                <div class="history-notice">Đặt hàng thành công. Mã đơn hàng: <strong>#FS<?= $successOrderId ?></strong></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="history-notice history-notice--error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($orders === []): ?>
                <div class="history-empty">
                    <h2>Chưa có đơn hàng</h2>
                    <p><?= $userId === null ? 'Đơn hàng khách chỉ hiển thị trong đúng phiên đã đặt.' : 'Tài khoản của bạn chưa có đơn hàng nào.' ?></p>
                    <a href="../products/index.php">Khám phá sản phẩm</a>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int)$order['id'];
                        $status = (string)$order['status'];
                        $cssStatus = $statusClass($status);
                        $step = $statusStep($status);
                        $info = $orderItems[$orderId] ?? ['quantity' => 0, 'images' => []];
                        ?>
                        <article class="history-card">
                            <div class="history-card__head">
                                <div class="history-card__identity">
                                    <div class="history-card__images" aria-hidden="true">
                                        <?php if ($info['images'] === []): ?>
                                            <span class="history-card__placeholder"></span>
                                        <?php else: ?>
                                            <?php foreach ($info['images'] as $image): ?>
                                                <img src="<?= e(cart_product_image_url($image)) ?>" alt="">
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h2>Đơn hàng #FS<?= $orderId ?></h2>
                                        <p><?= e($formatOrderDate($order['created_at'])) ?> · <?= (int)$info['quantity'] ?> sản phẩm</p>
                                    </div>
                                </div>
                                <span class="status status--<?= e($cssStatus) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                            </div>

                            <div class="history-card__body">
                                <div class="history-card__info">
                                    <div><span>Người nhận</span><strong><?= e($order['receiver_name']) ?></strong></div>
                                    <div><span>Điện thoại</span><strong><?= e($order['phone']) ?></strong></div>
                                    <div><span>Địa chỉ</span><strong><?= e($order['address']) ?></strong></div>
                                </div>
                                <div class="history-card__price">
                                    <span>Tổng đơn</span>
                                    <strong><?= number_format((float)$order['total_amount'], 0, ',', '.') ?>đ</strong>
                                </div>
                            </div>

                            <?php if ($status !== 'cancelled'): ?>
                                <div class="progress" aria-label="Tiến trình đơn hàng">
                                    <?php foreach (['Đặt hàng','Xác nhận','Đang giao','Hoàn thành'] as $index => $label): ?>
                                        <span class="<?= ($index + 1) <= $step ? 'active' : '' ?>"><?= e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="history-card__actions">
                                <a href="Order_detail.php?id=<?= $orderId ?>">Xem chi tiết →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
