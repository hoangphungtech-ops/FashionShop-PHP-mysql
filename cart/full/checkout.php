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
        'missingLines' => [],
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
                throw new DomainException('Giỏ hàng có sản phẩm hoặc phân loại không hợp lệ, hoặc không đủ tồn kho.');
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
                    throw new DomainException('Tồn kho vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng.');
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
$total = (float)$cartData['total'];
$cartCount = cart_quantity_count($cartData['cart']);

if ($error === '' && !$cartData['canCheckout']) {
    $error = 'Giỏ hàng có sản phẩm, phân loại hoặc tồn kho không hợp lệ. Vui lòng quay lại giỏ hàng.';
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
    <style>
        .checkout-variant { display:block; margin-top:4px; color:#637067; font-size:12px; }
    
        /* ===== FashionShop Cart/Checkout Compact Modern v2 ===== */
        :root{
            --fs-green:#103b2f;
            --fs-green-2:#174d3e;
            --fs-ink:#17251f;
            --fs-muted:#6f7b74;
            --fs-line:#e4e7e2;
            --fs-soft:#f5f5f1;
            --fs-card:#ffffff;
            --fs-shadow:0 16px 40px rgba(24,43,34,.08);
        }

        body.cart-page,
        body.checkout-page{
            background:#f8f8f5;
            color:var(--fs-ink);
        }

        .cart-page .page-intro,
        .checkout-page .page-intro{
            min-height:0 !important;
            padding:34px 0 30px !important;
            margin:0 !important;
            background:
                radial-gradient(circle at 82% 22%, rgba(184,199,188,.22), transparent 28%),
                linear-gradient(180deg,#fafaf7 0%,#f3f4ef 100%) !important;
            border-bottom:1px solid var(--fs-line);
        }

        .cart-page .page-intro .site-container,
        .checkout-page .page-intro .site-container{
            max-width:1320px;
        }

        .cart-page .breadcrumb,
        .checkout-page .breadcrumb{
            margin-bottom:18px !important;
            font-size:13px;
            color:#7c877f;
        }

        .cart-page .page-intro__content,
        .checkout-page .page-intro__content{
            max-width:680px;
        }

        .cart-page .page-intro .eyebrow,
        .checkout-page .page-intro .eyebrow{
            margin:0 0 8px;
            font-size:11px;
            letter-spacing:.18em;
            font-weight:800;
            text-transform:uppercase;
            color:#7d9588;
        }

        .cart-page .page-intro h1,
        .checkout-page .page-intro h1{
            margin:0 0 8px !important;
            font-family:inherit !important;
            font-size:clamp(40px,5vw,64px) !important;
            line-height:1 !important;
            letter-spacing:-.035em !important;
            font-weight:750 !important;
            color:var(--fs-green) !important;
        }

        .cart-page .page-intro__content > p:last-child,
        .checkout-page .page-intro__content > p:last-child{
            margin:0 !important;
            font-size:15px !important;
            line-height:1.6;
            color:var(--fs-muted);
        }

        .cart-page .cart-section,
        .checkout-page .checkout-section{
            padding:34px 0 70px !important;
        }

        .cart-page .cart-section > .site-container,
        .checkout-page .checkout-section > .site-container{
            max-width:1320px;
        }

        .cart-page .cart-layout{
            display:grid !important;
            grid-template-columns:minmax(0,1fr) 340px !important;
            gap:26px !important;
            align-items:start;
        }

        .cart-page .cart-list,
        .cart-page .order-summary,
        .checkout-page .checkout-form-panel,
        .checkout-page .checkout-summary{
            background:var(--fs-card) !important;
            border:1px solid var(--fs-line) !important;
            border-radius:20px !important;
            box-shadow:var(--fs-shadow) !important;
        }

        .cart-page .cart-list{
            padding:26px 28px !important;
            overflow:auto;
        }

        .cart-page .cart-list__heading{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
            margin-bottom:12px !important;
            padding-bottom:18px;
            border-bottom:1px solid var(--fs-line);
        }

        .cart-page .cart-list__heading h2,
        .cart-page .order-summary h2,
        .checkout-page .checkout-panel__heading h2{
            margin:0 !important;
            font-family:inherit !important;
            letter-spacing:-.025em !important;
            color:var(--fs-ink) !important;
        }

        .cart-page .cart-list__heading h2{
            font-size:28px !important;
            font-weight:750 !important;
        }

        .cart-page .cart-list__heading > span{
            padding:7px 11px;
            border-radius:999px;
            background:#edf3ef;
            color:var(--fs-green);
            font-size:11px;
            font-weight:800;
            letter-spacing:.08em;
            text-transform:uppercase;
            white-space:nowrap;
        }

        .cart-page .cart-table{
            width:100%;
            border-collapse:collapse;
            min-width:720px;
        }

        .cart-page .cart-table thead th{
            padding:15px 8px 12px !important;
            border-bottom:1px solid var(--fs-line) !important;
            font-size:10px !important;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:#718078;
            font-weight:800;
        }

        .cart-page .cart-table tbody td{
            padding:22px 8px !important;
            border-bottom:1px solid #edf0ec !important;
            vertical-align:middle;
        }

        .cart-page .cart-table tbody tr:last-child td{
            border-bottom:0 !important;
        }

        .cart-page .cart-product{
            display:flex;
            align-items:center;
            gap:16px;
            min-width:310px;
        }

        .cart-page .cart-product__image{
            width:78px !important;
            height:94px !important;
            flex:0 0 78px;
            border-radius:12px;
            overflow:hidden;
            background:var(--fs-soft);
            border:1px solid #edf0ec;
        }

        .cart-page .cart-product__image img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }

        .cart-page .cart-product__info > span:first-child{
            display:block;
            margin-bottom:5px;
            color:#7d9588;
            font-size:10px;
            font-weight:800;
            letter-spacing:.12em;
            text-transform:uppercase;
        }

        .cart-page .cart-product__info h3{
            margin:0 !important;
            font-size:15px !important;
            line-height:1.35;
            font-weight:700 !important;
        }

        .cart-page .cart-product__info h3 a{
            color:var(--fs-ink) !important;
            text-decoration:none !important;
        }

        .cart-page .cart-variant{
            margin-top:7px !important;
            gap:6px !important;
        }

        .cart-page .cart-variant span{
            padding:4px 8px !important;
            border-radius:8px;
            border:1px solid var(--fs-line) !important;
            background:#fafbf9;
            font-size:11px !important;
        }

        .cart-page .cart-stock{
            margin-top:7px !important;
            font-size:10px !important;
            letter-spacing:.06em;
            text-transform:uppercase;
            font-weight:700;
            color:#5f8d72 !important;
        }

        .cart-page .cart-quantity-form{
            display:grid !important;
            grid-template-columns:34px 54px 34px;
            gap:5px !important;
            align-items:center;
            justify-content:start;
            width:max-content;
        }

        .cart-page .cart-quantity-form button,
        .cart-page .cart-quantity-form input{
            height:34px !important;
            min-height:34px !important;
            border:1px solid #dce2dd !important;
            border-radius:8px !important;
            background:#fff !important;
            color:var(--fs-ink);
            box-shadow:none !important;
        }

        .cart-page .cart-quantity-form input{
            width:54px !important;
            padding:0 6px !important;
        }

        .cart-page .cart-quantity-form button[name="action"][value="update"]{
            grid-column:1 / span 2;
            width:93px;
            margin-top:2px;
            background:#eef4f0 !important;
            color:var(--fs-green) !important;
            border-color:#d6e3dc !important;
            font-size:11px;
            font-weight:750;
        }

        .cart-page .cart-quantity-form .cart-remove{
            grid-column:3;
            width:34px;
            margin-top:2px;
            text-decoration:none !important;
            color:#9a4d45 !important;
            background:#fff7f6 !important;
            border-color:#f0d8d5 !important;
            font-size:0;
        }

        .cart-page .cart-quantity-form .cart-remove::after{
            content:"×";
            font-size:18px;
            line-height:1;
        }

        .cart-page .order-summary{
            position:sticky;
            top:110px;
            padding:26px !important;
        }

        .cart-page .order-summary .eyebrow{
            margin:0 0 6px;
            color:#7d9588;
            font-size:10px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
        }

        .cart-page .order-summary h2{
            font-size:27px !important;
            font-weight:750 !important;
            line-height:1.15;
            word-spacing:0 !important;
        }

        .cart-page .order-summary__rows{
            margin-top:20px !important;
            padding:18px 0 !important;
            border-top:1px solid var(--fs-line);
            border-bottom:1px solid var(--fs-line);
        }

        .cart-page .order-summary__rows > div,
        .cart-page .order-summary__total{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
        }

        .cart-page .order-summary__rows > div + div{
            margin-top:12px;
        }

        .cart-page .order-summary__rows span,
        .cart-page .order-summary__rows strong{
            font-size:13px !important;
        }

        .cart-page .order-summary__total{
            padding:20px 0 18px !important;
        }

        .cart-page .order-summary__total span{
            font-size:14px;
            font-weight:750;
        }

        .cart-page .order-summary__total strong{
            font-family:inherit !important;
            font-size:26px !important;
            letter-spacing:-.03em;
            color:var(--fs-green);
        }

        .cart-page .order-summary__checkout,
        .checkout-page .checkout-submit{
            width:100%;
            min-height:48px;
            border-radius:12px !important;
            border:0 !important;
            background:linear-gradient(135deg,var(--fs-green),var(--fs-green-2)) !important;
            color:#fff !important;
            font-weight:800 !important;
            box-shadow:0 10px 20px rgba(16,59,47,.14);
        }

        .cart-page .order-summary__continue,
        .checkout-page .checkout-back{
            display:block;
            margin-top:14px !important;
            color:#68766e !important;
            font-size:12px !important;
            text-align:center;
            text-decoration:none !important;
        }

        /* Checkout */
        .checkout-page .checkout-layout{
            display:grid !important;
            grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr) !important;
            gap:26px !important;
            align-items:start;
        }

        .checkout-page .checkout-form-panel,
        .checkout-page .checkout-summary{
            padding:28px !important;
        }

        .checkout-page .checkout-summary{
            position:sticky;
            top:110px;
        }

        .checkout-page .checkout-panel__heading{
            display:flex;
            align-items:flex-start;
            gap:12px;
            margin-bottom:22px !important;
            padding-bottom:18px;
            border-bottom:1px solid var(--fs-line);
        }

        .checkout-page .checkout-panel__heading > span{
            display:grid;
            place-items:center;
            width:30px;
            height:30px;
            flex:0 0 30px;
            border-radius:9px;
            background:#edf3ef;
            color:var(--fs-green);
            font-size:12px;
            font-weight:800;
        }

        .checkout-page .checkout-panel__heading .eyebrow{
            margin:1px 0 4px !important;
            font-size:10px;
            font-weight:800;
            letter-spacing:.16em;
            color:#7d9588;
            text-transform:uppercase;
        }

        .checkout-page .checkout-panel__heading h2{
            font-size:26px !important;
            font-weight:750 !important;
            line-height:1.15;
        }

        .checkout-page .checkout-form{
            display:grid;
            gap:16px;
        }

        .checkout-page .form-field{
            margin:0 !important;
        }

        .checkout-page .form-field label{
            display:block;
            margin-bottom:7px;
            font-size:12px;
            font-weight:750;
            color:#34433b;
        }

        .checkout-page .form-field input,
        .checkout-page .form-field textarea{
            width:100%;
            border:1px solid #dce2dd !important;
            border-radius:12px !important;
            background:#fbfcfa !important;
            color:var(--fs-ink);
            outline:none;
            box-shadow:none !important;
            transition:border-color .2s, box-shadow .2s;
        }

        .checkout-page .form-field input{
            min-height:48px !important;
            padding:0 14px !important;
        }

        .checkout-page .form-field textarea{
            min-height:100px !important;
            padding:13px 14px !important;
            resize:vertical;
        }

        .checkout-page .form-field input:focus,
        .checkout-page .form-field textarea:focus{
            border-color:#87a395 !important;
            box-shadow:0 0 0 3px rgba(16,59,47,.07) !important;
        }

        .checkout-page .checkout-summary__items{
            display:grid;
            gap:12px;
        }

        .checkout-page .checkout-item{
            display:grid !important;
            grid-template-columns:minmax(0,1fr) auto;
            gap:18px;
            padding:14px 0 !important;
            border-bottom:1px solid #edf0ec;
        }

        .checkout-page .checkout-item h3{
            margin:0 0 5px !important;
            font-size:14px !important;
            line-height:1.4;
            font-weight:750 !important;
        }

        .checkout-page .checkout-item span{
            font-size:12px !important;
            color:#738078;
        }

        .checkout-page .checkout-item > strong{
            font-size:14px;
            color:var(--fs-ink);
            white-space:nowrap;
        }

        .checkout-page .checkout-summary__meta{
            margin-top:16px !important;
            padding:16px 0 !important;
            border-bottom:1px solid var(--fs-line);
        }

        .checkout-page .checkout-summary__meta > div,
        .checkout-page .checkout-summary__total{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
        }

        .checkout-page .checkout-summary__meta > div + div{
            margin-top:11px;
        }

        .checkout-page .checkout-summary__meta span,
        .checkout-page .checkout-summary__meta strong{
            font-size:13px !important;
        }

        .checkout-page .checkout-summary__total{
            padding-top:20px !important;
        }

        .checkout-page .checkout-summary__total span{
            font-size:14px;
            font-weight:750;
        }

        .checkout-page .checkout-summary__total strong{
            font-family:inherit !important;
            font-size:26px !important;
            letter-spacing:-.03em;
            color:var(--fs-green);
        }

        .checkout-page .form-alert{
            margin:0 0 16px !important;
            padding:12px 14px !important;
            border-radius:10px;
            border:1px solid #efd5d2;
            background:#fff5f4;
            color:#88453f;
            font-size:13px;
        }

        @media (max-width:980px){
            .cart-page .cart-layout,
            .checkout-page .checkout-layout{
                grid-template-columns:1fr !important;
            }
            .cart-page .order-summary,
            .checkout-page .checkout-summary{
                position:static;
            }
        }

        @media (max-width:700px){
            .cart-page .page-intro,
            .checkout-page .page-intro{
                padding:24px 0 22px !important;
            }
            .cart-page .page-intro h1,
            .checkout-page .page-intro h1{
                font-size:40px !important;
            }
            .cart-page .cart-section,
            .checkout-page .checkout-section{
                padding:22px 0 48px !important;
            }
            .cart-page .cart-list,
            .cart-page .order-summary,
            .checkout-page .checkout-form-panel,
            .checkout-page .checkout-summary{
                padding:20px !important;
                border-radius:16px !important;
            }
            .cart-page .cart-table{
                min-width:680px;
            }
        }

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
                <a href="../index.php">Trang chủ</a><span>/</span>
                <a href="index.php">Giỏ hàng</a><span>/</span>
                <span>Thanh toán</span>
            </nav>
            <div class="page-intro__content">
                <p class="eyebrow">Secure checkout</p>
                <h1>Thanh toán</h1>
                <p>Giá, phân loại và tồn kho được kiểm tra lại khi xác nhận đơn hàng.</p>
            </div>
        </div>
    </section>

    <section class="checkout-section">
        <div class="site-container checkout-layout">
            <div class="checkout-form-panel">
                <div class="checkout-panel__heading">
                    <span>01</span>
                    <div><p class="eyebrow">Delivery details</p><h2>Thông tin giao hàng</h2></div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" class="checkout-form" action="checkout.php">
                    <?= csrf_field() ?>

                    <div class="form-field">
                        <label for="receiver_name">Họ và tên *</label>
                        <input type="text" id="receiver_name" name="receiver_name" value="<?= e($receiverName) ?>" maxlength="100" required>
                    </div>

                    <div class="form-field">
                        <label for="phone">Số điện thoại *</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" maxlength="20" required>
                    </div>

                    <div class="form-field">
                        <label for="address">Địa chỉ nhận hàng *</label>
                        <textarea id="address" name="address" maxlength="255" required><?= e($address) ?></textarea>
                    </div>

                    <button type="submit"
                            class="button button--primary checkout-submit"
                            <?= !$cartData['canCheckout'] ? 'disabled' : '' ?>>
                        Xác nhận đặt hàng
                    </button>
                </form>

                <a class="checkout-back" href="index.php">← Quay lại giỏ hàng</a>
            </div>

            <aside class="checkout-summary">
                <div class="checkout-panel__heading">
                    <span>02</span>
                    <div><p class="eyebrow">Order summary</p><h2>Đơn hàng của bạn</h2></div>
                </div>

                <div class="checkout-summary__items">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-item">
                            <div>
                                <h3><?= e($item['name']) ?></h3>
                                <span>Số lượng: <?= (int)$item['quantity'] ?></span>

                                <?php if (($item['selected_size'] ?? '') !== '' || ($item['selected_color'] ?? '') !== ''): ?>
                                    <span class="checkout-variant">
                                        <?php if (($item['selected_size'] ?? '') !== ''): ?>
                                            Kích cỡ: <?= e($item['selected_size']) ?>
                                        <?php endif; ?>

                                        <?php if (($item['selected_size'] ?? '') !== '' && ($item['selected_color'] ?? '') !== ''): ?>
                                            ·
                                        <?php endif; ?>

                                        <?php if (($item['selected_color'] ?? '') !== ''): ?>
                                            Màu: <?= e($item['selected_color']) ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <strong><?= number_format((float)$item['subtotal'], 0, ',', '.') ?>đ</strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-summary__meta">
                    <div><span>Tạm tính</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                    <div><span>Phí vận chuyển</span><strong>Tính theo chính sách</strong></div>
                </div>

                <div class="checkout-summary__total">
                    <span>Tổng cộng</span>
                    <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>