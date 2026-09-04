<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   KIỂM TRA GIỎ HÀNG
========================= */

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: index.php");
    exit;
}


/* =========================
   LẤY SẢN PHẨM
========================= */

$products = [];
$total = 0;

$ids = array_keys($cart);

$ids = array_map('intval', $ids);

$ids = array_filter($ids, function ($id) {
    return $id > 0;
});


if (!empty($ids)) {

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    try {

        $sql = "SELECT *
                FROM products
                WHERE id IN ($placeholders)
                AND status = 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute($ids);

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        die("Lỗi database: " . $e->getMessage());

    }
}


/* =========================
   TÍNH TỔNG
========================= */

foreach ($products as $product) {

    $productId = (int)$product['id'];

    $quantity = (int)(
        $cart[$productId] ?? 0
    );

    if ($quantity <= 0) {
        continue;
    }

    $price = (float)(
        $product['price'] ?? 0
    );

    $total += $price * $quantity;
}


/* =========================
   XỬ LÝ ĐẶT HÀNG
========================= */

$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $address = trim(
        $_POST['address'] ?? ''
    );


    if (
        $name === '' ||
        $phone === '' ||
        $address === ''
    ) {

        $error = "Vui lòng nhập đầy đủ thông tin.";

    } else {

        try {

            $userId = $_SESSION['user_id'] ?? null;


            /* =========================
               TẠO ĐƠN HÀNG
            ========================= */

            $sql = "INSERT INTO orders
                    (
                        user_id,
                        receiver_name,
                        phone,
                        address,
                        total_amount,
                        status
                    )
                    VALUES
                    (
                        :user_id,
                        :receiver_name,
                        :phone,
                        :address,
                        :total_amount,
                        'pending'
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([

                ':user_id' =>
                    $userId,

                ':receiver_name' =>
                    $name,

                ':phone' =>
                    $phone,

                ':address' =>
                    $address,

                ':total_amount' =>
                    $total

            ]);


            /* =========================
               LẤY ID ĐƠN HÀNG
            ========================= */

            $orderId = $pdo->lastInsertId();


            /* =========================
               LƯU ORDER ITEMS
            ========================= */

            $itemSql = "INSERT INTO order_items
                        (
                            order_id,
                            product_id,
                            quantity,
                            price
                        )
                        VALUES
                        (
                            :order_id,
                            :product_id,
                            :quantity,
                            :price
                        )";

            $itemStmt = $pdo->prepare($itemSql);


            foreach ($products as $product) {

                $productId =
                    (int)$product['id'];

                $quantity =
                    (int)(
                        $cart[$productId]
                        ?? 0
                    );

                if ($quantity <= 0) {
                    continue;
                }

                $price =
                    (float)(
                        $product['price']
                        ?? 0
                    );


                $itemStmt->execute([

                    ':order_id' =>
                        $orderId,

                    ':product_id' =>
                        $productId,

                    ':quantity' =>
                        $quantity,

                    ':price' =>
                        $price

                ]);

            }


            /* =========================
               XÓA GIỎ HÀNG
            ========================= */

            $_SESSION['cart'] = [];

            $success = true;


        } catch (PDOException $e) {

            $error =
                "Không thể đặt hàng: "
                . $e->getMessage();

        }

    }

}

?>


<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Thanh toán - Fashion Shop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .checkout-page {
            padding: 70px 0 90px;
            background: #f8fbf7;
            min-height: 650px;
        }

        .checkout-title {
            text-align: center;
            margin-bottom: 45px;
        }

        .checkout-title h1 {
            font-size: 42px;
            color: #263126;
            margin-bottom: 12px;
        }

        .checkout-title p {
            color: #697369;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.4fr 0.8fr;
            gap: 30px;
        }

        .checkout-box {
            background: #ffffff;
            border: 1px solid #e3e9e3;
            padding: 30px;
        }

        .checkout-box h2 {
            color: #263126;
            font-size: 23px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #263126;
            font-size: 14px;
            font-weight: 700;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d9e0da;
            background: #ffffff;
            color: #263126;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .checkout-submit {
            width: 100%;
            margin-top: 15px;
            min-height: 50px;
            border: none;
            background: #263126;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .checkout-submit:hover {
            background: #78917d;
        }

        .checkout-back {
            display: inline-block;
            margin-top: 15px;
            color: #78917d;
            font-size: 14px;
            font-weight: 600;
        }

        .checkout-error {
            margin-bottom: 25px;
            padding: 14px 16px;
            background: #fff1f1;
            border: 1px solid #e8caca;
            color: #9a4545;
        }

        .checkout-success {
            background: #ffffff;
            border: 1px solid #dce7dd;
            padding: 60px 30px;
            text-align: center;
        }

        .checkout-success h2 {
            color: #263126;
            font-size: 30px;
            margin-bottom: 15px;
        }

        .checkout-success p {
            color: #697369;
            margin-bottom: 25px;
        }

        .success-btn {
            display: inline-flex;
            padding: 13px 25px;
            background: #263126;
            color: #ffffff;
            font-weight: 700;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e5eae5;
        }

        .order-item-name {
            color: #263126;
            font-weight: 600;
        }

        .order-item-quantity {
            color: #7a837b;
            font-size: 13px;
            margin-top: 5px;
        }

        .order-item-price {
            color: #263126;
            font-weight: 700;
            white-space: nowrap;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #263126;
        }

        .order-total strong {
            font-size: 20px;
            color: #263126;
        }

        .order-total span {
            font-size: 22px;
            font-weight: 700;
            color: #78917d;
        }

        @media (max-width: 800px) {

            .checkout-layout {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<header class="header">

    <div class="container header-content">

        <a
            href="../index.php"
            class="logo"
        >
            Fashion<span>Shop</span>
        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>

            <a href="../products/index.php">
                Sản phẩm
            </a>

            <a href="../products/index.php?category=1">
                Áo
            </a>

            <a href="../products/index.php?category=2">
                Quần
            </a>

            <a href="../products/index.php?category=3">
                Váy
            </a>

        </nav>


        <a
            href="index.php"
            class="cart"
        >
            Giỏ hàng
            <span>
                <?= count($_SESSION['cart'] ?? []) ?>
            </span>
        </a>

    </div>

</header>


<section class="checkout-page">

    <div class="container">


        <div class="checkout-title">

            <div class="small-title">
                CHECKOUT
            </div>

            <h1>
                Thanh toán
            </h1>

            <p>
                Nhập thông tin nhận hàng để hoàn tất đơn hàng.
            </p>

        </div>


        <?php if ($success): ?>


            <div class="checkout-success">

                <h2>
                    Đặt hàng thành công!
                </h2>

                <p>
                    Đơn hàng của bạn đã được ghi nhận.
                </p>

                <a
                    href="history.php"
                    class="success-btn"
                >
                    Xem lịch sử đơn hàng
                </a>

            </div>


        <?php else: ?>


            <?php if ($error !== ""): ?>

                <div class="checkout-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <div class="checkout-layout">


                <!-- THÔNG TIN NHẬN HÀNG -->

                <div class="checkout-box">

                    <h2>
                        Thông tin nhận hàng
                    </h2>


                    <form
                        method="POST"
                        action=""
                    >


                        <div class="form-group">

                            <label>
                                Họ và tên
                            </label>

                            <input
                                type="text"
                                name="name"
                                placeholder="Nhập họ và tên"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Số điện thoại
                            </label>

                            <input
                                type="text"
                                name="phone"
                                placeholder="Nhập số điện thoại"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Địa chỉ nhận hàng
                            </label>

                            <textarea
                                name="address"
                                placeholder="Nhập địa chỉ nhận hàng"
                                required
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="checkout-submit"
                        >
                            Xác nhận đặt hàng
                        </button>


                    </form>


                    <a
                        href="index.php"
                        class="checkout-back"
                    >
                        ← Quay lại giỏ hàng
                    </a>

                </div>


                <!-- ĐƠN HÀNG -->

                <div class="checkout-box">

                    <h2>
                        Đơn hàng của bạn
                    </h2>


                    <?php foreach ($products as $product): ?>

                        <?php

                        $productId =
                            (int)$product['id'];

                        $quantity =
                            (int)(
                                $cart[$productId]
                                ?? 0
                            );

                        if ($quantity <= 0) {
                            continue;
                        }

                        $price =
                            (float)(
                                $product['price']
                                ?? 0
                            );

                        $subtotal =
                            $price * $quantity;

                        ?>


                        <div class="order-item">

                            <div>

                                <div class="order-item-name">

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                </div>

                                <div class="order-item-quantity">

                                    Số lượng:
                                    <?= $quantity ?>

                                </div>

                            </div>


                            <div class="order-item-price">

                                <?= number_format(
                                    $subtotal,
                                    0,
                                    ',',
                                    '.'
                                ) ?>đ

                            </div>

                        </div>


                    <?php endforeach; ?>


                    <div class="order-total">

                        <strong>
                            Tổng cộng
                        </strong>

                        <span>

                            <?= number_format(
                                $total,
                                0,
                                ',',
                                '.'
                            ) ?>đ

                        </span>

                    </div>

                </div>


            </div>


        <?php endif; ?>


    </div>

</section>


</body>

</html>