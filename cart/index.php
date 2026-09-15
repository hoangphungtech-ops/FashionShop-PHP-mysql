<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart.php';

if (is_post_request()) {
    if (!csrf_validate(is_string($_POST['_csrf_token'] ?? null) ? $_POST['_csrf_token'] : null)) {
        cart_flash('error', 'PhiÃªn thao tÃ¡c Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng thá»­ láº¡i.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $lineKey = is_string($_POST['line_key'] ?? null) ? trim($_POST['line_key']) : '';
    $cart = cart_quantities();

    if ($lineKey === '' || !isset($cart[$lineKey])) {
        cart_flash('error', 'Sáº£n pháº©m khÃ´ng cÃ³ trong giá» hÃ ng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $line = $cart[$lineKey];
    $productId = (int)$line['product_id'];

    if ($action === 'remove') {
        unset($cart[$lineKey]);
        $_SESSION['cart'] = $cart;
        cart_flash('success', 'ÄÃ£ xÃ³a sáº£n pháº©m khá»i giá» hÃ ng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $quantity = (int)$line['quantity'];

    if ($action === 'increase') {
        $quantity++;
    } elseif ($action === 'decrease') {
        $quantity--;
    } elseif ($action === 'update') {
        $quantityInput = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 9999],
        ]);

        if ($quantityInput === false) {
            cart_flash('error', 'Sá»‘ lÆ°á»£ng pháº£i lÃ  sá»‘ nguyÃªn khÃ´ng Ã¢m.');
            safe_redirect('index.php', 'index.php', 303);
        }

        $quantity = (int)$quantityInput;
    } else {
        cart_flash('error', 'Thao tÃ¡c giá» hÃ ng khÃ´ng há»£p lá»‡.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($quantity <= 0) {
        unset($cart[$lineKey]);
        $_SESSION['cart'] = $cart;
        cart_flash('success', 'ÄÃ£ xÃ³a sáº£n pháº©m khá»i giá» hÃ ng.');
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
        cart_flash('error', 'ChÆ°a thá»ƒ cáº­p nháº­t giá» hÃ ng lÃºc nÃ y. Vui lÃ²ng thá»­ láº¡i.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($product === null || (int)$product['status'] !== 1) {
        cart_flash('error', 'Sáº£n pháº©m khÃ´ng cÃ²n Ä‘Æ°á»£c bÃ¡n.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $stock = max(0, (int)$product['stock']);
    $aggregate = 0;

    foreach ($cart as $key => $cartLine) {
        if ((int)$cartLine['product_id'] !== $productId) {
            continue;
        }

        $aggregate += $key === $lineKey
            ? $quantity
            : (int)$cartLine['quantity'];
    }

    if ($aggregate > $stock) {
        cart_flash('error', 'Tá»•ng sá»‘ lÆ°á»£ng cÃ¡c phÃ¢n loáº¡i vÆ°á»£t tá»“n kho hiá»‡n cÃ³ (' . $stock . ').');
        safe_redirect('index.php', 'index.php', 303);
    }

    $cart[$lineKey]['quantity'] = $quantity;
    $_SESSION['cart'] = $cart;

    cart_flash('success', 'ÄÃ£ cáº­p nháº­t sá»‘ lÆ°á»£ng sáº£n pháº©m.');
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
        'missingLines' => [],
        'missingIds' => [],
        'total' => 0.0,
        'canCheckout' => false,
    ];
    $loadError = 'ChÆ°a thá»ƒ táº£i giá» hÃ ng lÃºc nÃ y. Vui lÃ²ng thá»­ láº¡i sau.';
}

$cart = $cartData['cart'];
$products = $cartData['items'];
$total = (float)$cartData['total'];
$cartFlash = pull_cart_flash();
$hasMissingProducts = $cartData['missingLines'] !== [];
$cartCount = cart_quantity_count($cart);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kiá»ƒm tra giá» hÃ ng cá»§a báº¡n táº¡i Fashion Shop.">
    <title>Giá» hÃ ng | Fashion Shop</title>
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
        .cart-variant { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 7px; }
        .cart-variant span { padding: 3px 7px; border: 1px solid #dce2da; font-size: 12px; color: #4f5e54; }
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
                <a href="../index.php">Trang chá»§</a><span aria-hidden="true">/</span>
                <span aria-current="page">Giá» hÃ ng</span>
            </nav>
            <div class="page-intro__content">
                <p class="eyebrow">Your shopping bag</p>
                <h1>Giá» hÃ ng</h1>
                <p>Kiá»ƒm tra lá»±a chá»n vÃ  tá»“n kho trÆ°á»›c khi thanh toÃ¡n.</p>
            </div>
        </div>
    </section>

    <section class="cart-section">
        <div class="site-container">
            <?php if ($cartFlash !== null): ?>
                <div class="cart-notice <?= ($cartFlash['type'] ?? '') === 'success' ? 'cart-notice--success' : '' ?>">
                    <?= e($cartFlash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <?php if ($loadError !== ''): ?>
                <div class="cart-notice"><?= e($loadError) ?></div>
            <?php endif; ?>

            <?php if ($hasMissingProducts): ?>
                <div class="cart-notice">
                    CÃ³ sáº£n pháº©m trong giá» khÃ´ng cÃ²n tá»“n táº¡i.
                    <?php foreach ($cartData['missingLines'] as $missing): ?>
                        <form class="cart-missing-form" method="post" action="index.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="line_key" value="<?= e($missing['line_key']) ?>">
                            <button type="submit" name="action" value="remove">XÃ³a má»¥c #<?= (int)$missing['product_id'] ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($cart === []): ?>
                <div class="empty-state empty-state--cart">
                    <p class="eyebrow">Your bag is empty</p>
                    <h2>Giá» hÃ ng Ä‘ang trá»‘ng</h2>
                    <p>Báº¡n chÆ°a cÃ³ sáº£n pháº©m nÃ o trong giá» hÃ ng.</p>
                    <a class="button button--primary" href="../products/index.php">KhÃ¡m phÃ¡ sáº£n pháº©m</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-list">
                        <div class="cart-list__heading">
                            <h2>Sáº£n pháº©m Ä‘Ã£ chá»n</h2>
                            <span><?= $cartCount ?> sáº£n pháº©m</span>
                        </div>

                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Sáº£n pháº©m</th>
                                    <th>ÄÆ¡n giÃ¡</th>
                                    <th>Sá»‘ lÆ°á»£ng</th>
                                    <th>ThÃ nh tiá»n</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $productId = (int)$product['id'];
                                $quantity = (int)$product['quantity'];
                                $stock = (int)$product['stock'];
                                $isAvailable = (bool)$product['is_available'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="cart-product">
                                            <a class="cart-product__image" href="../products/detail.php?id=<?= $productId ?>">
                                                <img src="<?= e(cart_product_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                                            </a>
                                            <div class="cart-product__info">
                                                <span>Fashion selection</span>
                                                <h3><a href="../products/detail.php?id=<?= $productId ?>"><?= e($product['name']) ?></a></h3>

                                                <?php if (($product['selected_size'] ?? '') !== '' || ($product['selected_color'] ?? '') !== ''): ?>
                                                    <div class="cart-variant">
                                                        <?php if (($product['selected_size'] ?? '') !== ''): ?>
                                                            <span>KÃ­ch cá»¡: <?= e($product['selected_size']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (($product['selected_color'] ?? '') !== ''): ?>
                                                            <span>MÃ u: <?= e($product['selected_color']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <span class="cart-stock <?= !$isAvailable ? 'cart-stock--error' : '' ?>">
                                                    <?php if (!(bool)$product['is_active']): ?>Ngá»«ng bÃ¡n
                                                    <?php elseif (!$product['variant_valid']): ?>PhÃ¢n loáº¡i khÃ´ng cÃ²n há»£p lá»‡
                                                    <?php elseif ($stock < 1): ?>Háº¿t hÃ ng
                                                    <?php elseif (!$isAvailable): ?>Sá»‘ lÆ°á»£ng vÆ°á»£t tá»“n kho
                                                    <?php else: ?>CÃ²n <?= $stock ?> sáº£n pháº©m<?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format((float)$product['price'], 0, ',', '.') ?>Ä‘</td>
                                    <td>
                                        <form class="cart-quantity-form" method="post" action="index.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="line_key" value="<?= e($product['line_key']) ?>">
                                            <button type="submit" name="action" value="decrease">âˆ’</button>
                                            <input type="number" name="quantity" value="<?= $quantity ?>" min="0" max="<?= max(0, $stock) ?>">
                                            <button type="submit" name="action" value="increase">+</button>
                                            <button type="submit" name="action" value="update">Cáº­p nháº­t</button>
                                            <button class="cart-remove" type="submit" name="action" value="remove" formnovalidate>XÃ³a</button>
                                        </form>
                                    </td>
                                    <td><?= number_format((float)$product['subtotal'], 0, ',', '.') ?>Ä‘</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <aside class="order-summary">
                        <p class="eyebrow">Order summary</p>
                        <h2>TÃ³m táº¯t Ä‘Æ¡n hÃ ng</h2>
                        <div class="order-summary__rows">
                            <div><span>Táº¡m tÃ­nh</span><strong><?= number_format($total, 0, ',', '.') ?>Ä‘</strong></div>
                            <div><span>PhÃ­ váº­n chuyá»ƒn</span><strong>TÃ­nh khi thanh toÃ¡n</strong></div>
                        </div>
                        <div class="order-summary__total">
                            <span>Tá»•ng cá»™ng</span>
                            <strong><?= number_format($total, 0, ',', '.') ?>Ä‘</strong>
                        </div>
                        <a class="button button--primary order-summary__checkout"
                           href="checkout.php"
                           <?= !$cartData['canCheckout'] ? 'aria-disabled="true"' : '' ?>>
                            Thanh toÃ¡n
                        </a>
                        <a class="order-summary__continue" href="../products/index.php">â† Tiáº¿p tá»¥c mua hÃ ng</a>
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