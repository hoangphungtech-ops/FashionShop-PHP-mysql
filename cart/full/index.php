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
    $lineKey = is_string($_POST['line_key'] ?? null) ? trim($_POST['line_key']) : '';
    $cart = cart_quantities();

    if ($lineKey === '' || !isset($cart[$lineKey])) {
        cart_flash('error', 'Sản phẩm không có trong giỏ hàng.');
        safe_redirect('index.php', 'index.php', 303);
    }

    $line = $cart[$lineKey];
    $productId = (int)$line['product_id'];

    if ($action === 'remove') {
        unset($cart[$lineKey]);
        $_SESSION['cart'] = $cart;
        cart_flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
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
            cart_flash('error', 'Số lượng phải là số nguyên không âm.');
            safe_redirect('index.php', 'index.php', 303);
        }

        $quantity = (int)$quantityInput;
    } else {
        cart_flash('error', 'Thao tác giỏ hàng không hợp lệ.');
        safe_redirect('index.php', 'index.php', 303);
    }

    if ($quantity <= 0) {
        unset($cart[$lineKey]);
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

    if ($product === null || (int)$product['status'] !== 1) {
        cart_flash('error', 'Sản phẩm không còn được bán.');
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
        cart_flash('error', 'Tổng số lượng các phân loại vượt tồn kho hiện có (' . $stock . ').');
        safe_redirect('index.php', 'index.php', 303);
    }

    $cart[$lineKey]['quantity'] = $quantity;
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
        'missingLines' => [],
        'missingIds' => [],
        'total' => 0.0,
        'canCheckout' => false,
    ];
    $loadError = 'Chưa thể tải giỏ hàng lúc này. Vui lòng thử lại sau.';
}

$cart = $cartData['cart'];
$products = $cartData['items'];
$total = (float)$cartData['total'];
$cartFlash = pull_cart_flash();
$hasMissingProducts = $cartData['missingLines'] !== [];
$cartCount = cart_quantity_count($cart);

$recentOrders = [];
$recentOrderItems = [];
$orderHistoryError = '';

try {
    $sessionUser = $_SESSION['user'] ?? null;
    $historyUserIdValue = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
    $historyUserId = filter_var(
        $historyUserIdValue,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $historyUserId = $historyUserId !== false ? (int)$historyUserId : null;

    $guestRecentOrderIds = array_values(array_filter(array_map(
        'intval',
        is_array($_SESSION['recent_order_ids'] ?? null) ? $_SESSION['recent_order_ids'] : []
    ), static fn (int $id): bool => $id > 0));

    if ($historyUserId !== null) {
        $recentOrdersStatement = $pdo->prepare(
            'SELECT id, total_amount, status, created_at
             FROM orders
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT 3'
        );
        $recentOrdersStatement->execute([':user_id' => $historyUserId]);
        $recentOrders = $recentOrdersStatement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($guestRecentOrderIds !== []) {
        $guestRecentOrderIds = array_slice(array_reverse(array_unique($guestRecentOrderIds)), 0, 3);
        $placeholders = implode(',', array_fill(0, count($guestRecentOrderIds), '?'));
        $recentOrdersStatement = $pdo->prepare(
            "SELECT id, total_amount, status, created_at
             FROM orders
             WHERE user_id IS NULL AND id IN ($placeholders)
             ORDER BY id DESC"
        );
        $recentOrdersStatement->execute($guestRecentOrderIds);
        $recentOrders = $recentOrdersStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($recentOrders !== []) {
        $recentIds = array_map(static fn (array $order): int => (int)$order['id'], $recentOrders);
        $placeholders = implode(',', array_fill(0, count($recentIds), '?'));

        $recentItemsStatement = $pdo->prepare(
            "SELECT oi.order_id, oi.quantity, p.image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id IN ($placeholders)
             ORDER BY oi.id ASC"
        );
        $recentItemsStatement->execute($recentIds);

        foreach ($recentItemsStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $orderId = (int)$row['order_id'];

            if (!isset($recentOrderItems[$orderId])) {
                $recentOrderItems[$orderId] = [
                    'quantity' => 0,
                    'images' => [],
                ];
            }

            $recentOrderItems[$orderId]['quantity'] += max(0, (int)$row['quantity']);

            if (
                count($recentOrderItems[$orderId]['images']) < 2
                && is_string($row['image'] ?? null)
                && trim((string)$row['image']) !== ''
            ) {
                $recentOrderItems[$orderId]['images'][] = (string)$row['image'];
            }
        }
    }
} catch (PDOException $exception) {
    error_log('[cart-recent-orders] Cannot load recent orders: ' . $exception->getMessage());
    $orderHistoryError = 'Chưa thể tải đơn hàng gần đây.';
}

$statusLabels = order_status_labels();

$formatOrderDate = static function (mixed $date): string {
    $timestamp = strtotime((string)$date);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : (string)$date;
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
        .cart-variant { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 7px; }
        .cart-variant span { padding: 3px 7px; border: 1px solid #dce2da; font-size: 12px; color: #4f5e54; }
        .cart-missing-form { display: inline; margin-left: 8px; }
        .cart-missing-form button { border: 0; background: transparent; color: inherit; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; }
        .order-summary__checkout[aria-disabled="true"] { pointer-events: none; opacity: .5; }
        @media (max-width: 700px) { .cart-quantity-form { justify-content: flex-end; } }
    
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


        /* ===== Recent orders + history actions v3 ===== */
        .cart-page .order-summary__history{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            width:100%;
            min-height:44px;
            margin-top:10px;
            border:1px solid #bfd0c7;
            border-radius:12px;
            background:#fff;
            color:var(--fs-green);
            font-size:12px;
            font-weight:800;
            text-decoration:none;
            transition:.2s ease;
        }
        .cart-page .order-summary__history:hover{
            background:#f1f6f3;
            border-color:#9eb7aa;
            transform:translateY(-1px);
        }
        .cart-page .empty-state__actions{
            display:flex;
            justify-content:center;
            flex-wrap:wrap;
            gap:10px;
            margin-top:20px;
        }
        .cart-page .button--secondary-modern{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:44px;
            padding:0 18px;
            border:1px solid #b9c9c0;
            border-radius:10px;
            background:#fff;
            color:var(--fs-green);
            font-weight:750;
            text-decoration:none;
        }
        .recent-orders{
            padding:0 0 72px;
        }
        .recent-orders .site-container{
            max-width:1320px;
        }
        .recent-orders__head{
            display:flex;
            align-items:end;
            justify-content:space-between;
            gap:20px;
            margin-bottom:18px;
        }
        .recent-orders__head p{
            margin:0 0 5px;
            color:#7d9588;
            font-size:10px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
        }
        .recent-orders__head h2{
            margin:0;
            color:var(--fs-ink);
            font-size:25px;
            line-height:1.2;
            letter-spacing:-.02em;
        }
        .recent-orders__head > a{
            color:var(--fs-green);
            font-size:12px;
            font-weight:800;
            text-decoration:none;
            white-space:nowrap;
        }
        .recent-orders__grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:14px;
        }
        .recent-order{
            min-width:0;
            padding:18px;
            border:1px solid var(--fs-line);
            border-radius:16px;
            background:#fff;
            box-shadow:0 12px 28px rgba(24,43,34,.06);
        }
        .recent-order__top{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
        }
        .recent-order__identity{
            display:flex;
            align-items:center;
            gap:11px;
            min-width:0;
        }
        .recent-order__images{
            display:flex;
            flex:0 0 auto;
        }
        .recent-order__images img,
        .recent-order__image-placeholder{
            width:42px;
            height:50px;
            object-fit:cover;
            border:2px solid #fff;
            border-radius:9px;
            background:#f0f2ee;
        }
        .recent-order__images img + img{
            margin-left:-12px;
        }
        .recent-order__meta{
            min-width:0;
        }
        .recent-order__meta strong{
            display:block;
            color:var(--fs-ink);
            font-size:13px;
            line-height:1.3;
        }
        .recent-order__meta span{
            display:block;
            margin-top:3px;
            color:#7b8780;
            font-size:10px;
        }
        .order-status{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 9px;
            border-radius:999px;
            font-size:10px;
            font-weight:800;
            white-space:nowrap;
        }
        .order-status::before{
            content:"";
            width:7px;
            height:7px;
            border-radius:50%;
            background:currentColor;
        }
        .order-status--pending{background:#fff5db;color:#94691c}
        .order-status--confirmed{background:#e9f1ff;color:#3268a8}
        .order-status--shipping{background:#fff0df;color:#ae6419}
        .order-status--completed{background:#e5f5ea;color:#277449}
        .order-status--cancelled{background:#fdeaea;color:#a64747}
        .recent-order__price{
            display:flex;
            align-items:end;
            justify-content:space-between;
            gap:12px;
            margin-top:14px;
        }
        .recent-order__price span{
            color:#7d8881;
            font-size:10px;
        }
        .recent-order__price strong{
            color:var(--fs-green);
            font-size:16px;
        }
        .order-progress{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:0;
            margin-top:15px;
        }
        .order-progress__step{
            position:relative;
            padding-top:15px;
            color:#9aa49e;
            font-size:8px;
            text-align:center;
        }
        .order-progress__step::before{
            content:"";
            position:absolute;
            top:3px;
            left:50%;
            z-index:2;
            width:8px;
            height:8px;
            border:2px solid #b9c5be;
            border-radius:50%;
            background:#fff;
            transform:translateX(-50%);
        }
        .order-progress__step:not(:last-child)::after{
            content:"";
            position:absolute;
            top:7px;
            left:50%;
            width:100%;
            height:1px;
            background:#cfd7d2;
        }
        .order-progress__step.is-active{
            color:var(--fs-green);
            font-weight:700;
        }
        .order-progress__step.is-active::before{
            border-color:var(--fs-green);
            background:var(--fs-green);
        }
        .order-progress__step.is-active:not(:last-child)::after{
            background:var(--fs-green);
        }
        .recent-order__link{
            display:block;
            margin-top:13px;
            padding-top:12px;
            border-top:1px solid #edf0ec;
            color:var(--fs-green);
            font-size:11px;
            font-weight:800;
            text-decoration:none;
        }
        .recent-orders__empty{
            padding:20px;
            border:1px dashed #cbd5cf;
            border-radius:14px;
            background:#fbfcfa;
            color:#6f7b74;
            font-size:13px;
        }
        @media (max-width:980px){
            .recent-orders__grid{grid-template-columns:1fr}
        }
        @media (max-width:700px){
            .recent-orders{padding-bottom:48px}
            .recent-orders__head{
                align-items:flex-start;
                flex-direction:column;
                gap:8px;
            }
        }

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
                    Có sản phẩm trong giỏ không còn tồn tại.
                    <?php foreach ($cartData['missingLines'] as $missing): ?>
                        <form class="cart-missing-form" method="post" action="index.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="line_key" value="<?= e($missing['line_key']) ?>">
                            <button type="submit" name="action" value="remove">Xóa mục #<?= (int)$missing['product_id'] ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($cart === []): ?>
                <div class="empty-state empty-state--cart">
                    <p class="eyebrow">Your bag is empty</p>
                    <h2>Giỏ hàng đang trống</h2>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                    <div class="empty-state__actions">
                        <a class="button button--primary" href="../products/index.php">Khám phá sản phẩm</a>
                        <a class="button--secondary-modern" href="history.php">Xem lịch sử đơn hàng</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-list">
                        <div class="cart-list__heading">
                            <h2>Sản phẩm đã chọn</h2>
                            <span><?= $cartCount ?> sản phẩm</span>
                        </div>

                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
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
                                                            <span>Kích cỡ: <?= e($product['selected_size']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (($product['selected_color'] ?? '') !== ''): ?>
                                                            <span>Màu: <?= e($product['selected_color']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <span class="cart-stock <?= !$isAvailable ? 'cart-stock--error' : '' ?>">
                                                    <?php if (!(bool)$product['is_active']): ?>Ngừng bán
                                                    <?php elseif (!$product['variant_valid']): ?>Phân loại không còn hợp lệ
                                                    <?php elseif ($stock < 1): ?>Hết hàng
                                                    <?php elseif (!$isAvailable): ?>Số lượng vượt tồn kho
                                                    <?php else: ?>Còn <?= $stock ?> sản phẩm<?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format((float)$product['price'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <form class="cart-quantity-form" method="post" action="index.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="line_key" value="<?= e($product['line_key']) ?>">
                                            <button type="submit" name="action" value="decrease">−</button>
                                            <input type="number" name="quantity" value="<?= $quantity ?>" min="0" max="<?= max(0, $stock) ?>">
                                            <button type="submit" name="action" value="increase">+</button>
                                            <button type="submit" name="action" value="update">Cập nhật</button>
                                            <button class="cart-remove" type="submit" name="action" value="remove" formnovalidate>Xóa</button>
                                        </form>
                                    </td>
                                    <td><?= number_format((float)$product['subtotal'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <aside class="order-summary">
                        <p class="eyebrow">Order summary</p>
                        <h2>Tóm tắt đơn hàng</h2>
                        <div class="order-summary__rows">
                            <div><span>Tạm tính</span><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></div>
                            <div><span>Phí vận chuyển</span><strong>Tính khi thanh toán</strong></div>
                        </div>
                        <div class="order-summary__total">
                            <span>Tổng cộng</span>
                            <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                        </div>
                        <a class="button button--primary order-summary__checkout"
                           href="checkout.php"
                           <?= !$cartData['canCheckout'] ? 'aria-disabled="true"' : '' ?>>
                            Thanh toán
                        </a>
                        <a class="order-summary__history" href="history.php">◷ &nbsp; Xem lịch sử đơn hàng</a>
                        <a class="order-summary__continue" href="../products/index.php">← Tiếp tục mua hàng</a>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="recent-orders" aria-labelledby="recent-orders-title">
        <div class="site-container">
            <div class="recent-orders__head">
                <div>
                    <p>Order tracking</p>
                    <h2 id="recent-orders-title">Đơn hàng gần đây của bạn</h2>
                </div>
                <a href="history.php">Xem tất cả đơn hàng →</a>
            </div>

            <?php if ($orderHistoryError !== ''): ?>
                <div class="recent-orders__empty"><?= e($orderHistoryError) ?></div>
            <?php elseif ($recentOrders === []): ?>
                <div class="recent-orders__empty">
                    Chưa có đơn hàng gần đây. Sau khi đặt hàng, trạng thái đơn sẽ hiển thị tại đây.
                </div>
            <?php else: ?>
                <div class="recent-orders__grid">
                    <?php foreach ($recentOrders as $recentOrder): ?>
                        <?php
                        $recentId = (int)$recentOrder['id'];
                        $recentStatus = (string)$recentOrder['status'];
                        $recentStatusCss = $statusClass($recentStatus);
                        $recentStep = $statusStep($recentStatus);
                        $recentInfo = $recentOrderItems[$recentId] ?? ['quantity' => 0, 'images' => []];
                        ?>
                        <article class="recent-order">
                            <div class="recent-order__top">
                                <div class="recent-order__identity">
                                    <div class="recent-order__images" aria-hidden="true">
                                        <?php if ($recentInfo['images'] === []): ?>
                                            <span class="recent-order__image-placeholder"></span>
                                        <?php else: ?>
                                            <?php foreach ($recentInfo['images'] as $recentImage): ?>
                                                <img src="<?= e(cart_product_image_url($recentImage)) ?>" alt="">
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="recent-order__meta">
                                        <strong>#FS<?= $recentId ?></strong>
                                        <span><?= e($formatOrderDate($recentOrder['created_at'])) ?></span>
                                    </div>
                                </div>
                                <span class="order-status order-status--<?= e($recentStatusCss) ?>">
                                    <?= e($statusLabels[$recentStatus] ?? $recentStatus) ?>
                                </span>
                            </div>

                            <div class="recent-order__price">
                                <span><?= (int)$recentInfo['quantity'] ?> sản phẩm</span>
                                <strong><?= number_format((float)$recentOrder['total_amount'], 0, ',', '.') ?>đ</strong>
                            </div>

                            <?php if ($recentStatus !== 'cancelled'): ?>
                                <div class="order-progress" aria-label="Tiến trình đơn hàng">
                                    <?php
                                    $steps = ['Đặt hàng', 'Xác nhận', 'Đang giao', 'Hoàn thành'];
                                    foreach ($steps as $index => $stepLabel):
                                        $stepNumber = $index + 1;
                                    ?>
                                        <span class="order-progress__step <?= $stepNumber <= $recentStep ? 'is-active' : '' ?>">
                                            <?= e($stepLabel) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <a class="recent-order__link" href="Order_detail.php?id=<?= $recentId ?>">
                                Xem chi tiết đơn hàng →
                            </a>
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