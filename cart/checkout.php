<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

$cart = cart_quantities();

if ($cart === []) {
    cart_flash('error', 'Giỏ hàng đang trống.');
    safe_redirect('index.php', 'index.php', 303);
}

$receiverName = sanitize_text($_POST['receiver_name'] ?? '', 100);
$phone = sanitize_text($_POST['phone'] ?? '', 20);
$address = sanitize_text($_POST['address'] ?? '', 255);
$error = '';

try {
    $cartData = load_cart($pdo);
} catch (PDOException $exception) {
    error_log('[checkout] Cannot load cart: ' . $exception->getMessage());
    $cartData = [
        'cart' => $cart,
        'items' => [],
        'missingIds' => [],
        'total' => 0.0,
        'canCheckout' => false,
    ];
    $error = 'Chưa thể kiểm tra giỏ hàng. Vui lòng thử lại sau.';
}

if (is_post_request()) {
    $csrfToken = $_POST['_csrf_token'] ?? null;

    if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
        $error = 'Phiên đặt hàng đã hết hạn. Vui lòng tải lại trang.';
    } elseif ($receiverName === '' || mb_strlen($receiverName, 'UTF-8') < 2) {
        $error = 'Vui lòng nhập họ và tên hợp lệ.';
    } elseif (!preg_match('/^[0-9+().\s-]{8,20}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ.';
    } elseif ($address === '' || mb_strlen($address, 'UTF-8') < 8) {
        $error = 'Vui lòng nhập địa chỉ nhận hàng đầy đủ.';
    } else {
        try {
            $pdo->beginTransaction();
            $lockedCart = load_cart($pdo, true);

            if ($lockedCart['cart'] === []
                || !$lockedCart['canCheckout']
                || count($lockedCart['items']) !== count($lockedCart['cart'])) {
                throw new DomainException('Giỏ hàng có sản phẩm không hợp lệ hoặc không đủ tồn kho.');
            }

            $totalAmount = number_format((float) $lockedCart['total'], 2, '.', '');
            $sessionUser = $_SESSION['user'] ?? null;
            $userId = is_array($sessionUser)
                ? filter_var($sessionUser['id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ])
                : false;
            $userId = $userId !== false ? (int) $userId : null;

            $orderStatement = $pdo->prepare(
                'INSERT INTO orders
                    (user_id, receiver_name, phone, address, total_amount, status)
                 VALUES
                    (:user_id, :receiver_name, :phone, :address, :total_amount, :status)'
            );
            $orderStatement->execute([
                ':user_id' => $userId,
                ':receiver_name' => $receiverName,
                ':phone' => $phone,
                ':address' => $address,
                ':total_amount' => $totalAmount,
                ':status' => 'pending',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStatement = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (:order_id, :product_id, :quantity, :price)'
            );
            $stockStatement = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity_delta
                 WHERE id = :product_id
                   AND status = 1
                   AND stock >= :quantity_check'
            );

            foreach ($lockedCart['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $productId = (int) $item['id'];

                $itemStatement->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':quantity' => $quantity,
                    ':price' => number_format((float) $item['price'], 2, '.', ''),
                ]);
                $stockStatement->execute([
                    ':quantity_delta' => $quantity,
                    ':quantity_check' => $quantity,
                    ':product_id' => $productId,
                ]);

                if ($stockStatement->rowCount() !== 1) {
                    throw new DomainException('Tồn kho vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng.');
                }
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            $recentOrderIds = $_SESSION['recent_order_ids'] ?? [];
            $recentOrderIds = is_array($recentOrderIds) ? $recentOrderIds : [];
            $recentOrderIds[] = $orderId;
            $_SESSION['recent_order_ids'] = array_slice(array_values(array_unique(array_map('intval', $recentOrderIds))), -20);

            safe_redirect('history.php?success=1&order_id=' . $orderId, 'history.php', 303);
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('[checkout] Order failed: ' . $exception->getMessage());
            $error = 'Đặt hàng thất bại. Vui lòng thử lại sau.';
        }

        if ($error !== '') {
            try {
                $cartData = load_cart($pdo);
            } catch (PDOException $exception) {
                error_log('[checkout] Cannot reload cart: ' . $exception->getMessage());
            }
        }
    }
}

$items = $cartData['items'];
$total = (float) $cartData['total'];
$cartCount = cart_quantity_count($cartData['cart']);

if ($error === '' && !$cartData['canCheckout']) {
    $error = 'Giỏ hàng có sản phẩm ngừng bán, hết hàng hoặc vượt tồn kho. Vui lòng quay lại giỏ hàng để cập nhật.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hoàn tất thông tin giao hàng và đặt hàng tại Fashion Shop.">
    <title>Thanh toán | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="site-body checkout-page">
<?php
$siteBasePath = '../';
$currentPage = 'cart';
$currentCategory = 0;
require __DIR__ . '/../includes/header.php';
?>
<main id="main-content">
    <section class="page-intro page-intro--compact checkout-intro">
        <div class="site-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="../index.php">Trang chủ</a><span aria-hidden="true">/</span>
                <a href="index.php">Giỏ hàng</a><span aria-hidden="true">/</span>
                <span aria-current="page">Thanh toán</span>
            </nav>
            <div class="page-intro__content">
                <p class="eyebrow">Secure checkout</p>
                <h1>Thanh toán</h1>
                <p>Giá và tồn kho được kiểm tra lại khi bạn xác nhận đơn hàng.</p>
            </div>
        </div>
    </section>

    <section class="checkout-section" aria-label="Thông tin thanh toán">
        <div class="site-container checkout-layout">
            <div class="checkout-form-panel">
                <div class="checkout-panel__heading"><span>01</span><div><p class="eyebrow">Delivery details</p><h2>Thông tin giao hàng</h2></div></div>
                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" class="checkout-form" action="checkout.php">
                    <?= csrf_field() ?>
                    <div class="form-field">
                        <label for="receiver_name">Họ và tên <span aria-hidden="true">*</span></label>
                        <input type="text" id="receiver_name" name="receiver_name" value="<?= e($receiverName) ?>" autocomplete="name" maxlength="100" required>
                    </div>
                    <div class="form-field">
                        <label for="phone">Số điện thoại <span aria-hidden="true">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" autocomplete="tel" inputmode="tel" maxlength="20" required>
                    </div>
                    <div class="form-field">
                        <label for="address">Địa chỉ nhận hàng <span aria-hidden="true">*</span></label>
                        <textarea id="address" name="address" autocomplete="street-address" maxlength="255" required><?= e($address) ?></textarea>
                    </div>
                    <button type="submit" class="button button--primary checkout-submit" <?= !$cartData['canCheckout'] ? 'disabled' : '' ?>>Xác nhận đặt hàng</button>
                </form>
                <a class="checkout-back" href="index.php">← Quay lại giỏ hàng</a>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                <div class="checkout-panel__heading"><span>02</span><div><p class="eyebrow">Order summary</p><h2 id="checkout-summary-title">Đơn hàng của bạn</h2></div></div>
                <div class="checkout-summary__items">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-item">
                            <div><h3><?= e($item['name']) ?></h3><span>Số lượng: <?= (int) $item['quantity'] ?></span></div>
                            <strong><?= number_format((float) $item['subtotal'], 0, ',', '.') ?>đ</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="checkout-summary__meta">
                    <div><span>Tạm tính</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                    <div><span>Phí vận chuyển</span><strong>Tính theo địa chỉ</strong></div>
                </div>
                <div class="checkout-summary__total"><span>Tổng cộng</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                <p class="checkout-summary__note">Không có tổng tiền nào từ trình duyệt được dùng để tạo đơn.</p>
            </aside>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
