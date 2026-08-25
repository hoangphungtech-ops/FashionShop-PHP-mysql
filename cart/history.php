<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   KIỂM TRA ĐĂNG NHẬP
========================= */

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================
   LẤY LỊCH SỬ ĐƠN HÀNG
========================= */

$orders = [];

try {

    $sql = "SELECT
                id,
                receiver_name,
                phone,
                address,
                total_amount,
                status,
                created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Lỗi database: " . $e->getMessage());

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
        Lịch sử đơn hàng - Fashion Shop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .history-page {
            padding: 70px 0 90px;
            background: #f8fbf7;
            min-height: 650px;
        }

        .history-title {
            text-align: center;
            margin-bottom: 45px;
        }

        .history-title .small-title {
            margin-bottom: 10px;
        }

        .history-title h1 {
            font-size: 42px;
            color: #263126;
            margin-bottom: 12px;
        }

        .history-title p {
            color: #697369;
            font-size: 15px;
        }

        .history-box {
            background: #ffffff;
            border: 1px solid #e3e9e3;
            padding: 30px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            padding: 15px;
            background: #263126;
            color: #ffffff;
            text-align: left;
            font-size: 13px;
        }

        .history-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #e6ebe6;
            color: #394239;
            vertical-align: middle;
        }

        .order-id {
            font-weight: 700;
            color: #263126;
        }

        .order-price {
            font-weight: 700;
            color: #78917d;
        }

        .order-status {
            display: inline-block;
            padding: 7px 12px;
            background: #edf4ee;
            color: #526b58;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-history {
            text-align: center;
            padding: 70px 20px;
        }

        .empty-history h2 {
            color: #263126;
            margin-bottom: 12px;
        }

        .empty-history p {
            color: #707970;
            margin-bottom: 25px;
        }

        .history-btn {
            display: inline-flex;
            padding: 13px 25px;
            background: #263126;
            color: #ffffff;
            font-weight: 700;
        }

        .history-btn:hover {
            background: #78917d;
        }

        .back-cart {
            display: inline-block;
            margin-top: 25px;
            color: #78917d;
            font-weight: 600;
        }

        @media (max-width: 800px) {

            .history-box {
                padding: 15px;
                overflow-x: auto;
            }

            .history-table {
                min-width: 850px;
            }

        }

        @media (max-width: 600px) {

            .history-page {
                padding: 45px 0 60px;
            }

            .history-title h1 {
                font-size: 34px;
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

            <span>
                <?= count($_SESSION['cart'] ?? []) ?>
            </span>

        </a>

    </div>

</header>


<!-- HISTORY -->

<section class="history-page">

    <div class="container">


        <div class="history-title">

            <div class="small-title">
                ORDER HISTORY
            </div>

            <h1>
                Lịch sử đơn hàng
            </h1>

            <p>
                Theo dõi những đơn hàng bạn đã đặt tại Fashion Shop.
            </p>

        </div>


        <?php if (empty($orders)): ?>


            <div class="history-box">

                <div class="empty-history">

                    <h2>
                        Chưa có đơn hàng
                    </h2>

                    <p>
                        Bạn chưa thực hiện đơn hàng nào.
                    </p>

                    <a
                        href="../products/index.php"
                        class="history-btn"
                    >
                        Xem sản phẩm
                    </a>

                </div>

            </div>


        <?php else: ?>


            <div class="history-box">

                <table class="history-table">

                    <thead>

                        <tr>

                            <th>
                                Mã đơn
                            </th>

                            <th>
                                Người nhận
                            </th>

                            <th>
                                Số điện thoại
                            </th>

                            <th>
                                Địa chỉ
                            </th>

                            <th>
                                Tổng tiền
                            </th>

                            <th>
                                Trạng thái
                            </th>

                            <th>
                                Ngày đặt
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($orders as $order): ?>


                            <tr>

                                <td class="order-id">

                                    #<?= (int)$order['id'] ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['receiver_name']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['phone']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['address']
                                    ) ?>

                                </td>


                                <td class="order-price">

                                    <?= number_format(
                                        (float)$order['total_amount'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>đ

                                </td>


                                <td>

                                    <span class="order-status">

                                        <?= htmlspecialchars(
                                            $order['status']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    ) ?>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>


                <a
                    href="index.php"
                    class="back-cart"
                >
                    ← Quay lại giỏ hàng
                </a>

            </div>


        <?php endif; ?>


    </div>

</section>


</body>

</html>
