<?php
session_start();

require_once '../config/database.php';

/*
|--------------------------------------------------------------------------
| LẤY GIỎ HÀNG
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LẤY USER ID NẾU ĐÃ ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? null;

if ($userId === null && isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
}

/*
|--------------------------------------------------------------------------
| TÍNH TỔNG ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

$error = '';

/*
|--------------------------------------------------------------------------
| XỬ LÝ ĐẶT HÀNG
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $receiverName = trim($_POST['receiver_name'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA THÔNG TIN
    |--------------------------------------------------------------------------
    */

    if ($receiverName === '') {

        $error = 'Vui lòng nhập họ và tên.';

    } elseif ($phone === '') {

        $error = 'Vui lòng nhập số điện thoại.';

    } elseif ($address === '') {

        $error = 'Vui lòng nhập địa chỉ nhận hàng.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | BẮT ĐẦU TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | LƯU ĐƠN HÀNG
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    receiver_name,
                    phone,
                    address,
                    total_amount,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $status = 'pending';

            $stmt->execute([
                $userId,
                $receiverName,
                $phone,
                $address,
                $total,
                $status
            ]);

            /*
            |--------------------------------------------------------------------------
            | LẤY ID ĐƠN HÀNG
            |--------------------------------------------------------------------------
            */

            $orderId = $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | LƯU CHI TIẾT ĐƠN HÀNG
            |--------------------------------------------------------------------------
            |
            | Database chung chỉ có:
            | id
            | order_id
            | product_id
            | quantity
            | price
            |
            */

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    quantity,
                    price
                )
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart as $item) {

                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | HOÀN TẤT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | XÓA GIỎ HÀNG
            |--------------------------------------------------------------------------
            */

            $_SESSION['cart'] = [];

            /*
            |--------------------------------------------------------------------------
            | CHUYỂN SANG LỊCH SỬ ĐƠN HÀNG
            |--------------------------------------------------------------------------
            */

            header(
                'Location: history.php?success=1&order_id=' .
                urlencode($orderId)
            );

            exit;

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK NẾU CÓ LỖI
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Đặt hàng thất bại. Vui lòng thử lại.';
        }
    }
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
$cartCount = count($cart);
require __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="page-intro page-intro--compact checkout-intro">
        <div class="site-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="../index.php">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <a href="index.php">Giỏ hàng</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Thanh toán</span>
            </nav>

            <div class="page-intro__content">
                <p class="eyebrow">Secure checkout</p>
                <h1>Thanh toán</h1>
                <p>Hoàn tất thông tin giao hàng để xác nhận đơn hàng của bạn.</p>
            </div>
        </div>
    </section>

    <section class="checkout-section" aria-label="Thông tin thanh toán">
        <div class="site-container checkout-layout">
            <div class="checkout-form-panel">
                <div class="checkout-panel__heading">
                    <span>01</span>
                    <div>
                        <p class="eyebrow">Delivery details</p>
                        <h2>Thông tin giao hàng</h2>
                    </div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7.5v5M12 16.5v.1"></path>
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="checkout-form">
                    <div class="form-field">
                        <label for="receiver_name">Họ và tên <span aria-hidden="true">*</span></label>
                        <input
                            type="text"
                            id="receiver_name"
                            name="receiver_name"
                            value="<?= htmlspecialchars($_POST['receiver_name'] ?? '') ?>"
                            autocomplete="name"
                            placeholder="Nhập họ và tên người nhận"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="phone">Số điện thoại <span aria-hidden="true">*</span></label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            autocomplete="tel"
                            inputmode="tel"
                            placeholder="Nhập số điện thoại"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="address">Địa chỉ nhận hàng <span aria-hidden="true">*</span></label>
                        <textarea
                            id="address"
                            name="address"
                            autocomplete="street-address"
                            placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"
                            required
                        ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="button button--primary checkout-submit">
                        Xác nhận đặt hàng
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 7l5 5-5 5"></path></svg>
                    </button>
                </form>

                <a class="checkout-back" href="index.php">← Quay lại giỏ hàng</a>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                <div class="checkout-panel__heading">
                    <span>02</span>
                    <div>
                        <p class="eyebrow">Order summary</p>
                        <h2 id="checkout-summary-title">Đơn hàng của bạn</h2>
                    </div>
                </div>

                <div class="checkout-summary__items">
                    <?php foreach ($cart as $item): ?>
                        <?php

                        $subtotal =

                            $item['price'] * $item['quantity'];

                        ?>
                        <div class="checkout-item">
                            <div>
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <span>Số lượng: <?= (int)$item['quantity'] ?></span>
                            </div>
                            <strong><?= number_format($subtotal, 0, ',', '.') ?>đ</strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-summary__meta">
                    <div>
                        <span>Tạm tính</span>
                        <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                    </div>
                    <div>
                        <span>Phí vận chuyển</span>
                        <strong>Tính theo địa chỉ</strong>
                    </div>
                </div>

                <div class="checkout-summary__total">
                    <span>Tổng cộng</span>
                    <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                </div>

                <p class="checkout-summary__note">
                    Thông tin đơn hàng sẽ được xác nhận sau khi bạn hoàn tất đặt hàng.
                </p>
            </aside>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
