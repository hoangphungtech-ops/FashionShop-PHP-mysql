<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LẤY GIỎ HÀNG
|--------------------------------------------------------------------------
| Giả sử giỏ hàng được lưu trong session:
| $_SESSION['cart'][product_id] = số lượng
*/

$cart = $_SESSION['cart'] ?? [];

$products = [];
$total = 0;

if (!empty($cart)) {

    $ids = array_keys($cart);

    $ids = array_map('intval', $ids);

    $ids = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (!empty($ids)) {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

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

    <title>Giỏ hàng - Fashion Shop</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .cart-page {
            padding: 70px 0 90px;
            background: #f8fbf7;
            min-height: 600px;
        }

        .cart-title {
            text-align: center;
            margin-bottom: 45px;
        }

        .cart-title .small-title {
            margin-bottom: 10px;
        }

        .cart-title h1 {
            font-size: 42px;
            color: #263126;
            margin-bottom: 12px;
        }

        .cart-title p {
            color: #697369;
            font-size: 15px;
        }

        .cart-box {
            background: #ffffff;
            border: 1px solid #e3e9e3;
            padding: 30px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th {
            padding: 15px;
            background: #263126;
            color: #ffffff;
            text-align: left;
            font-size: 13px;
        }

        .cart-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #e6ebe6;
            color: #394239;
            vertical-align: middle;
        }

        .cart-product {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-product img {
            width: 80px;
            height: 90px;
            object-fit: cover;
            background: #f2f4f2;
        }

        .cart-product-name {
            font-weight: 700;
            color: #263126;
        }

        .cart-price {
            font-weight: 600;
        }

        .cart-quantity {
            font-weight: 700;
        }

        .cart-total {
            font-weight: 700;
            color: #263126;
        }

        .cart-summary {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .cart-grand-total {
            font-size: 22px;
            font-weight: 700;
            color: #263126;
        }

        .cart-grand-total span {
            color: #78917d;
        }

        .cart-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .cart-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 24px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.3s ease;
        }

        .continue-btn {
            border: 1px solid #78917d;
            color: #78917d;
            background: #ffffff;
        }

        .continue-btn:hover {
            background: #edf4ee;
        }

        .checkout-btn {
            border: 1px solid #263126;
            background: #263126;
            color: #ffffff;
        }

        .checkout-btn:hover {
            background: #78917d;
            border-color: #78917d;
        }

        .cart-empty {
            text-align: center;
            padding: 80px 20px;
        }

        .cart-empty h2 {
            color: #263126;
            margin-bottom: 12px;
        }

        .cart-empty p {
            color: #707970;
            margin-bottom: 25px;
        }

        .empty-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 25px;
            background: #263126;
            color: #ffffff;
            font-weight: 700;
        }

        .cart-footer {
            background: #263126;
            color: #ffffff;
            padding: 50px 0 25px;
        }

        .cart-footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 50px;
            padding-bottom: 35px;
        }

        .cart-footer h3 {
            font-size: 25px;
            margin-bottom: 12px;
        }

        .cart-footer h3 span {
            color: #9ab19f;
        }

        .cart-footer h4 {
            margin-bottom: 15px;
        }

        .cart-footer p,
        .cart-footer a {
            color: #bdc6bd;
            font-size: 14px;
        }

        .cart-footer a {
            display: block;
            margin-bottom: 9px;
        }

        .cart-footer a:hover {
            color: #ffffff;
        }

        .cart-copyright {
            border-top: 1px solid #465046;
            padding-top: 20px;
            text-align: center;
            color: #9fa99f;
            font-size: 12px;
        }

        @media (max-width: 800px) {

            .cart-box {
                padding: 15px;
                overflow-x: auto;
            }

            .cart-table {
                min-width: 700px;
            }

            .cart-footer-content {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .cart-page {
                padding: 45px 0 60px;
            }

            .cart-title h1 {
                font-size: 34px;
            }

            .cart-footer-content {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

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
            <span><?= count($cart) ?></span>
        </a>

    </div>

</header>


<!-- CART -->

<section class="cart-page">

    <div class="container">


        <div class="cart-title">

            <div class="small-title">
                YOUR SHOPPING BAG
            </div>

            <h1>
                Giỏ hàng
            </h1>

            <p>
                Kiểm tra sản phẩm trước khi thanh toán.
            </p>

        </div>


        <?php if (empty($products)): ?>


            <div class="cart-box">

                <div class="cart-empty">

                    <h2>
                        Giỏ hàng đang trống
                    </h2>

                    <p>
                        Bạn chưa có sản phẩm nào trong giỏ hàng.
                    </p>

                    <a
                        href="../products/index.php"
                        class="empty-btn"
                    >
                        Xem sản phẩm
                    </a>

                </div>

            </div>


        <?php else: ?>


            <div class="cart-box">

                <table class="cart-table">

                    <thead>

                        <tr>

                            <th>
                                Sản phẩm
                            </th>

                            <th>
                                Đơn giá
                            </th>

                            <th>
                                Số lượng
                            </th>

                            <th>
                                Thành tiền
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($products as $product): ?>


                            <?php

                            $productId = (int)$product['id'];

                            $quantity = (int)(
                                $cart[$productId]
                                ?? 0
                            );

                            if ($quantity <= 0) {
                                continue;
                            }

                            $price = (float)(
                                $product['price']
                                ?? 0
                            );

                            $subtotal =
                                $price * $quantity;

                            $total += $subtotal;


                            $image = trim(
                                $product['image']
                                ?? ''
                            );

                            if ($image === '') {
                                $image =
                                    "../assets/images/ao-thun.jpg";
                            } else {

                                if (
                                    strpos(
                                        $image,
                                        "uploads/"
                                    ) === 0
                                ) {

                                    $image = "../" . $image;

                                } elseif (
                                    strpos(
                                        $image,
                                        "assets/"
                                    ) === 0
                                ) {

                                    $image = "../" . $image;

                                } else {

                                    $image =
                                        "../uploads/products/"
                                        . basename($image);

                                }
                            }

                            ?>


                            <tr>


                                <td>

                                    <div class="cart-product">

                                        <img
                                            src="<?= htmlspecialchars($image) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                        >

                                        <div>

                                            <div class="cart-product-name">

                                                <?= htmlspecialchars(
                                                    $product['name']
                                                ) ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <td class="cart-price">

                                    <?= number_format(
                                        $price,
                                        0,
                                        ',',
                                        '.'
                                    ) ?>đ

                                </td>


                                <td class="cart-quantity">

                                    <?= $quantity ?>

                                </td>


                                <td class="cart-total">

                                    <?= number_format(
                                        $subtotal,
                                        0,
                                        ',',
                                        '.'
                                    ) ?>đ

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>


                <div class="cart-summary">


                    <div class="cart-grand-total">

                        Tổng tiền:

                        <span>

                            <?= number_format(
                                $total,
                                0,
                                ',',
                                '.'
                            ) ?>đ

                        </span>

                    </div>


                    <div class="cart-actions">

                        <a
                            href="../products/index.php"
                            class="cart-btn continue-btn"
                        >
                            Tiếp tục mua hàng
                        </a>


                        <a
                            href="checkout.php"
                            class="cart-btn checkout-btn"
                        >
                            Thanh toán
                        </a>

                    </div>


                </div>


            </div>


        <?php endif; ?>


    </div>

</section>


<!-- FOOTER -->

<footer class="cart-footer">

    <div class="container">


        <div class="cart-footer-content">


            <div>

                <h3>
                    Fashion<span>Shop</span>
                </h3>

                <p>
                    Thời trang trẻ trung,
                    hiện đại và phù hợp
                    với phong cách riêng
                    của bạn.
                </p>

            </div>


            <div>

                <h4>
                    Danh mục
                </h4>

                <a href="../products/index.php">
                    Tất cả sản phẩm
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

            </div>


            <div>

                <h4>
                    Hỗ trợ
                </h4>

                <a href="#">
                    Chính sách đổi trả
                </a>

                <a href="#">
                    Vận chuyển
                </a>

                <a href="#">
                    Liên hệ
                </a>

            </div>


        </div>


        <div class="cart-copyright">

            © 2026 Fashion Shop

        </div>


    </div>

</footer>


</body>

</html>