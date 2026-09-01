<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

if (is_post_request()) {
    if (!csrf_validate(is_string($_POST['_csrf_token'] ?? null) ? $_POST['_csrf_token'] : null)) {
        cart_flash('error', 'Phiên thao tác đã hết hạn. Vui lòng thử lại.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $productId = input_int($_POST, 'product_id');
    $cart = cart_quantities();

    if ($productId === null || !isset($cart[$productId])) {
        cart_flash('error', 'Sản phẩm không có trong giỏ hàng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($action === 'remove') {
        unset($cart[$productId]);
        $_SESSION['cart'] = $cart;
        cart_flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $quantity = $cart[$productId];

    if ($action === 'increase') {
        $quantity++;
    } elseif ($action === 'decrease') {
        $quantity--;
    } elseif ($action === 'update') {
        $quantityInput = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 9999],
        ]);

        if ($quantityInput === false) {
            cart_flash('error', 'Số lượng phải là số nguyên không âm.');
            safe_redirect('index.php', 'index.php', 303);
        }

        $quantity = (int) $quantityInput;
    } else {
        cart_flash('error', 'Thao tác giỏ hàng không hợp lệ.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($quantity <= 0) {
        unset($cart[$productId]);
        $_SESSION['cart'] = $cart;
        cart_flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    try {
        $stockStatement = $pdo->prepare(
            'SELECT stock, status FROM products WHERE id = :id LIMIT 1'
        );
        $stockStatement->execute([':id' => $productId]);
        $product = $stockStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $exception) {
        error_log('[cart-update] Cannot load product: ' . $exception->getMessage());
        cart_flash('error', 'Chưa thể cập nhật giỏ hàng lúc này. Vui lòng thử lại.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($product === null || (int) $product['status'] !== 1) {
        cart_flash('error', 'Sản phẩm không còn được bán. Bạn có thể xóa sản phẩm khỏi giỏ.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $stock = max(0, (int) $product['stock']);

    if ($quantity > $stock) {
        cart_flash('error', 'Số lượng yêu cầu vượt quá tồn kho hiện có (' . $stock . ').');
        safe_redirect('index.php', 'index.php', 303);
    }

    $cart[$productId] = $quantity;
    $_SESSION['cart'] = $cart;
    cart_flash('success', 'Đã cập nhật số lượng sản phẩm.');
    safe_redirect('index.php', 'index.php', 303);
}

try {
    $cartData = load_cart($pdo);
    $loadError = '';
} catch (PDOException $exception) {
    error_log('[cart] Cannot load cart: ' . $exception->getMessage());
    $cartData = [
        'cart' => cart_quantities(),
        'items' => [],
        'missingIds' => [],
        'total' => 0.0,
        'canCheckout' => false,
    ];
    $loadError = 'Chưa thể tải giỏ hàng lúc này. Vui lòng thử lại sau.';
}

$cart = $cartData['cart'];
$products = $cartData['items'];
$total = (float) $cartData['total'];
$cartFlash = pull_cart_flash();
$hasMissingProducts = $cartData['missingIds'] !== [];
$cartCount = cart_quantity_count($cart);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kiểm tra giỏ hàng của bạn tại Fashion Shop.">
    <title>Giỏ hàng | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .cart-notice { margin: 0 0 20px; padding: 13px 16px; border-left: 3px solid #8e3f36; background: #fff3f1; color: #71342e; }
        .cart-notice--success { border-color: #3e765c; background: #edf6f0; color: #28553f; }
        .cart-quantity-form { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .cart-quantity-form button { min-width: 34px; min-height: 34px; border: 1px solid #ccd6ce; background: #fff; cursor: pointer; }
        .cart-quantity-form input { width: 64px; min-height: 34px; padding: 5px; border: 1px solid #ccd6ce; text-align: center; }
        .cart-quantity-form .cart-remove { border-color: transparent; color: #93483f; text-decoration: underline; }
        .cart-stock { display: block; margin-top: 6px; color: #687168; font-size: 12px; }
        .cart-stock--error { color: #93483f; font-weight: 700; }
        .cart-missing-form { display: inline; margin-left: 8px; }
        .cart-missing-form button { border: 0; background: transparent; color: inherit; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; }
        .order-summary__checkout[aria-disabled="true"] { pointer-events: none; opacity: .5; }
        @media (max-width: 700px) { .cart-quantity-form { justify-content: flex-end; } }
    </style>
</head>
<body class="site-body cart-page">
<?php
$siteBasePath = '../';
$currentPage = 'cart';
$currentCategory = 0;
require __DIR__ . '/../includes/header.php';
?>
<main id="main-content">
    <section class="page-intro page-intro--compact">
        <div class="site-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="../index.php">Trang chủ</a><span aria-hidden="true">/</span>
                <span aria-current="page">Giỏ hàng</span>
            </nav>
            <div class="page-intro__content">
                <p class="eyebrow">Your shopping bag</p>
                <h1>Giỏ hàng</h1>
                <p>Kiểm tra lựa chọn và tồn kho trước khi thanh toán.</p>
            </div>
        </div>
    </section>

    <section class="cart-section" aria-label="Sản phẩm trong giỏ hàng">
        <div class="site-container">
            <?php if ($cartFlash !== null): ?>
                <div class="cart-notice <?= ($cartFlash['type'] ?? '') === 'success' ? 'cart-notice--success' : '' ?>" role="status">
                    <?= e($cartFlash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <?php if ($loadError !== ''): ?>
                <div class="cart-notice" role="alert"><?= e($loadError) ?></div>
            <?php endif; ?>
            <?php if ($hasMissingProducts): ?>
                <div class="cart-notice" role="alert">
                    Có sản phẩm trong phiên giỏ hàng không còn tồn tại.
                    <?php foreach ($cartData['missingIds'] as $missingProductId): ?>
                        <form class="cart-missing-form" method="post" action="index.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $missingProductId ?>">
                            <button type="submit" name="action" value="remove">Xóa mục #<?= (int) $missingProductId ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($cart === []): ?>
                <div class="empty-state empty-state--cart">
                    <span class="empty-state__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M5.75 8.25h12.5l1 12H4.75l1-12Z"></path><path d="M8.75 9V6.75a3.25 3.25 0 0 1 6.5 0V9"></path></svg>
                    </span>
                    <p class="eyebrow">Your bag is empty</p>
                    <h2>Giỏ hàng đang trống</h2>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                    <a class="button button--primary" href="../products/index.php">Khám phá sản phẩm</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-list">
                        <div class="cart-list__heading"><h2>Sản phẩm đã chọn</h2><span><?= $cartCount ?> sản phẩm</span></div>
                        <table class="cart-table">
                            <thead><tr><th scope="col">Sản phẩm</th><th scope="col">Đơn giá</th><th scope="col">Số lượng</th><th scope="col">Thành tiền</th></tr></thead>
                            <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $productId = (int) $product['id'];
                                $quantity = (int) $product['quantity'];
                                $stock = (int) $product['stock'];
                                $isAvailable = (bool) $product['is_available'];
                                ?>
                                <tr>
                                    <td data-label="Sản phẩm">
                                        <div class="cart-product">
                                            <a class="cart-product__image" href="../products/detail.php?id=<?= $productId ?>">
                                                <img src="<?= e(cart_product_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                                            </a>
                                            <div class="cart-product__info">
                                                <span>Fashion selection</span>
                                                <h3><a href="../products/detail.php?id=<?= $productId ?>"><?= e($product['name']) ?></a></h3>
                                                <span class="cart-stock <?= !$isAvailable ? 'cart-stock--error' : '' ?>">
                                                    <?php if (!(bool) $product['is_active']): ?>Ngừng bán
                                                    <?php elseif ($stock < 1): ?>Hết hàng
                                                    <?php elseif ($quantity > $stock): ?>Chỉ còn <?= $stock ?> sản phẩm
                                                    <?php else: ?>Còn <?= $stock ?> sản phẩm<?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart-price" data-label="Đơn giá"><?= number_format((float) $product['price'], 0, ',', '.') ?>đ</td>
                                    <td class="cart-quantity" data-label="Số lượng">
                                        <form class="cart-quantity-form" method="post" action="index.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                                            <button type="submit" name="action" value="decrease" aria-label="Giảm số lượng">−</button>
                                            <input type="number" name="quantity" value="<?= $quantity ?>" min="0" max="<?= max(0, $stock) ?>" aria-label="Số lượng <?= e($product['name']) ?>">
                                            <button type="submit" name="action" value="increase" aria-label="Tăng số lượng">+</button>
                                            <button type="submit" name="action" value="update">Cập nhật</button>
                                            <button class="cart-remove" type="submit" name="action" value="remove" formnovalidate>Xóa</button>
                                        </form>
                                    </td>
                                    <td class="cart-total" data-label="Thành tiền"><?= number_format((float) $product['subtotal'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <aside class="order-summary" aria-labelledby="cart-summary-title">
                        <p class="eyebrow">Order summary</p>
                        <h2 id="cart-summary-title">Tóm tắt đơn hàng</h2>
                        <div class="order-summary__rows">
                            <div><span>Tạm tính</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                            <div><span>Phí vận chuyển</span><strong>Tính khi thanh toán</strong></div>
                        </div>
                        <div class="order-summary__total"><span>Tổng cộng</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                        <a class="button button--primary order-summary__checkout" href="checkout.php" <?= !$cartData['canCheckout'] ? 'aria-disabled="true"' : '' ?>>Thanh toán</a>
                        <a class="order-summary__continue" href="../products/index.php">← Tiếp tục mua hàng</a>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
