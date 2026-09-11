<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

$cart = cart_quantities();

if ($cart === []) {
    cart_flash('error', 'Giá» hÃ ng Ä‘ang trá»‘ng.');
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
        'missingLines' => [],
        'missingIds' => [],
        'total' => 0.0,
        'canCheckout' => false,
    ];
    $error = 'ChÆ°a thá»ƒ kiá»ƒm tra giá» hÃ ng. Vui lÃ²ng thá»­ láº¡i sau.';
}

if (is_post_request()) {
    $csrfToken = $_POST['_csrf_token'] ?? null;

    if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
        $error = 'PhiÃªn Ä‘áº·t hÃ ng Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng táº£i láº¡i trang.';
    } elseif ($receiverName === '' || mb_strlen($receiverName, 'UTF-8') < 2) {
        $error = 'Vui lÃ²ng nháº­p há» vÃ  tÃªn há»£p lá»‡.';
    } elseif (!preg_match('/^[0-9+().\s-]{8,20}$/', $phone)) {
        $error = 'Sá»‘ Ä‘iá»‡n thoáº¡i khÃ´ng há»£p lá»‡.';
    } elseif ($address === '' || mb_strlen($address, 'UTF-8') < 8) {
        $error = 'Vui lÃ²ng nháº­p Ä‘á»‹a chá»‰ nháº­n hÃ ng Ä‘áº§y Ä‘á»§.';
    } else {
        try {
            $pdo->beginTransaction();
            $lockedCart = load_cart($pdo, true);

            if ($lockedCart['cart'] === []
                || !$lockedCart['canCheckout']
                || count($lockedCart['items']) !== count($lockedCart['cart'])) {
                throw new DomainException('Giá» hÃ ng cÃ³ sáº£n pháº©m hoáº·c phÃ¢n loáº¡i khÃ´ng há»£p lá»‡, hoáº·c khÃ´ng Ä‘á»§ tá»“n kho.');
            }

            $totalAmount = number_format((float)$lockedCart['total'], 2, '.', '');
            $sessionUser = $_SESSION['user'] ?? null;
            $userId = is_array($sessionUser)
                ? filter_var($sessionUser['id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ])
                : false;
            $userId = $userId !== false ? (int)$userId : null;

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

            $orderId = (int)$pdo->lastInsertId();

            $itemStatement = $pdo->prepare(
                'INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        product_image,
                        selected_size,
                        selected_color,
                        material,
                        quantity,
                        price
                    )
                 VALUES
                    (
                        :order_id,
                        :product_id,
                        :product_name,
                        :product_image,
                        :selected_size,
                        :selected_color,
                        :material,
                        :quantity,
                        :price
                    )'
            );

            $stockStatement = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity_delta
                 WHERE id = :product_id
                   AND status = 1
                   AND stock >= :quantity_check'
            );

            foreach ($lockedCart['items'] as $item) {
                $quantity = (int)$item['quantity'];
                $productId = (int)$item['id'];

                $itemStatement->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':product_name' => (string)$item['name'],
                    ':product_image' => (string)($item['image'] ?? ''),
                    ':selected_size' => ($item['selected_size'] ?? '') !== '' ? (string)$item['selected_size'] : null,
                    ':selected_color' => ($item['selected_color'] ?? '') !== '' ? (string)$item['selected_color'] : null,
                    ':material' => ($item['material'] ?? '') !== '' ? (string)$item['material'] : null,
                    ':quantity' => $quantity,
                    ':price' => number_format((float)$item['price'], 2, '.', ''),
                ]);

                $stockStatement->execute([
                    ':quantity_delta' => $quantity,
                    ':quantity_check' => $quantity,
                    ':product_id' => $productId,
                ]);

                if ($stockStatement->rowCount() !== 1) {
                    throw new DomainException('Tá»“n kho vá»«a thay Ä‘á»•i. Vui lÃ²ng kiá»ƒm tra láº¡i giá» hÃ ng.');
                }
            }

            $pdo->commit();
            $_SESSION['cart'] = [];

            $recentOrderIds = $_SESSION['recent_order_ids'] ?? [];
            $recentOrderIds = is_array($recentOrderIds) ? $recentOrderIds : [];
            $recentOrderIds[] = $orderId;
            $_SESSION['recent_order_ids'] = array_slice(
                array_values(array_unique(array_map('intval', $recentOrderIds))),
                -20
            );

            safe_redirect(
                'history.php?success=1&order_id=' . $orderId,
                'history.php',
                303
            );
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
            $error = 'Äáº·t hÃ ng tháº¥t báº¡i. Vui lÃ²ng thá»­ láº¡i sau.';
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
$total = (float)$cartData['total'];
$cartCount = cart_quantity_count($cartData['cart']);

if ($error === '' && !$cartData['canCheckout']) {
    $error = 'Giá» hÃ ng cÃ³ sáº£n pháº©m, phÃ¢n loáº¡i hoáº·c tá»“n kho khÃ´ng há»£p lá»‡. Vui lÃ²ng quay láº¡i giá» hÃ ng.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HoÃ n táº¥t thÃ´ng tin giao hÃ ng vÃ  Ä‘áº·t hÃ ng táº¡i Fashion Shop.">
    <title>Thanh toÃ¡n | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .checkout-variant { display:block; margin-top:4px; color:#637067; font-size:12px; }
    </style>
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
                <a href="../index.php">Trang chá»§</a><span>/</span>
                <a href="index.php">Giá» hÃ ng</a><span>/</span>
                <span>Thanh toÃ¡n</span>
            </nav>
            <div class="page-intro__content">
                <p class="eyebrow">Secure checkout</p>
                <h1>Thanh toÃ¡n</h1>
                <p>GiÃ¡, phÃ¢n loáº¡i vÃ  tá»“n kho Ä‘Æ°á»£c kiá»ƒm tra láº¡i khi xÃ¡c nháº­n Ä‘Æ¡n hÃ ng.</p>
            </div>
        </div>
    </section>

    <section class="checkout-section">
        <div class="site-container checkout-layout">
            <div class="checkout-form-panel">
                <div class="checkout-panel__heading">
                    <span>01</span>
                    <div><p class="eyebrow">Delivery details</p><h2>ThÃ´ng tin giao hÃ ng</h2></div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" class="checkout-form" action="checkout.php">
                    <?= csrf_field() ?>

                    <div class="form-field">
                        <label for="receiver_name">Há» vÃ  tÃªn *</label>
                        <input type="text" id="receiver_name" name="receiver_name" value="<?= e($receiverName) ?>" maxlength="100" required>
                    </div>

                    <div class="form-field">
                        <label for="phone">Sá»‘ Ä‘iá»‡n thoáº¡i *</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" maxlength="20" required>
                    </div>

                    <div class="form-field">
                        <label for="address">Äá»‹a chá»‰ nháº­n hÃ ng *</label>
                        <textarea id="address" name="address" maxlength="255" required><?= e($address) ?></textarea>
                    </div>

                    <button type="submit"
                            class="button button--primary checkout-submit"
                            <?= !$cartData['canCheckout'] ? 'disabled' : '' ?>>
                        XÃ¡c nháº­n Ä‘áº·t hÃ ng
                    </button>
                </form>

                <a class="checkout-back" href="index.php">â† Quay láº¡i giá» hÃ ng</a>
            </div>

            <aside class="checkout-summary">
                <div class="checkout-panel__heading">
                    <span>02</span>
                    <div><p class="eyebrow">Order summary</p><h2>ÄÆ¡n hÃ ng cá»§a báº¡n</h2></div>
                </div>

                <div class="checkout-summary__items">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-item">
                            <div>
                                <h3><?= e($item['name']) ?></h3>
                                <span>Sá»‘ lÆ°á»£ng: <?= (int)$item['quantity'] ?></span>

                                <?php if (($item['selected_size'] ?? '') !== '' || ($item['selected_color'] ?? '') !== ''): ?>
                                    <span class="checkout-variant">
                                        <?php if (($item['selected_size'] ?? '') !== ''): ?>
                                            KÃ­ch cá»¡: <?= e($item['selected_size']) ?>
                                        <?php endif; ?>

                                        <?php if (($item['selected_size'] ?? '') !== '' && ($item['selected_color'] ?? '') !== ''): ?>
                                            Â·
                                        <?php endif; ?>

                                        <?php if (($item['selected_color'] ?? '') !== ''): ?>
                                            MÃ u: <?= e($item['selected_color']) ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <strong><?= number_format((float)$item['subtotal'], 0, ',', '.') ?>Ä‘</strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-summary__meta">
                    <div><span>Táº¡m tÃ­nh</span><strong><?= number_format($total, 0, ',', '.') ?>Ä‘</strong></div>
                    <div><span>PhÃ­ váº­n chuyá»ƒn</span><strong>TÃ­nh theo chÃ­nh sÃ¡ch</strong></div>
                </div>

                <div class="checkout-summary__total">
                    <span>Tá»•ng cá»™ng</span>
                    <strong><?= number_format($total, 0, ',', '.') ?>Ä‘</strong>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>