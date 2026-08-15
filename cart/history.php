<?php
session_start();

require_once '../config/database.php';

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        customer_name,
        phone,
        address,
        note,
        total_amount,
        status,
        created_at
    FROM orders
    ORDER BY id DESC
");

$orders = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| LẤY THÔNG BÁO ĐẶT HÀNG THÀNH CÔNG
|--------------------------------------------------------------------------
*/

$success = isset($_GET['success']) && $_GET['success'] == '1';
$orderId = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : 0;
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Lịch sử mua hàng - Fashion Shop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            width: 92%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            text-align: center;
        }

        .order-box {
            background: white;
            margin-bottom: 25px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        .order-id {
            font-size: 19px;
            font-weight: bold;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            background: #fff3cd;
            color: #664d03;
        }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin-top: 20px;
        }

        .info-item {
            padding: 5px 0;
        }

        .label {
            font-weight: bold;
        }

        .order-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: right;
            font-size: 20px;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 10px;
        }

        .button {
            display: inline-block;
            margin-top: 25px;
            padding: 11px 18px;
            border-radius: 6px;
            background: #333;
            color: white;
            text-decoration: none;
        }

        .detail-button {
            display: inline-block;
            margin-top: 15px;
            padding: 9px 15px;
            border-radius: 5px;
            background: #198754;
            color: white;
            text-decoration: none;
        }

        @media (max-width: 700px) {

            .order-info {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <h1>Lịch sử mua hàng</h1>


    <?php if ($success): ?>

        <div class="success">

            Đặt hàng thành công!

            <?php if ($orderId > 0): ?>

                Mã đơn hàng:
                <strong>#<?= $orderId ?></strong>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <?php if (empty($orders)): ?>

        <div class="empty">

            <h2>Chưa có đơn hàng</h2>

            <p>
                Bạn chưa có đơn hàng nào.
            </p>

            <a
                href="../products/index.php"
                class="button"
            >
                Tiếp tục mua hàng
            </a>

        </div>

    <?php else: ?>


        <?php foreach ($orders as $order): ?>

            <div class="order-box">

                <div class="order-header">

                    <div class="order-id">

                        Đơn hàng #<?= (int) $order['id'] ?>

                    </div>

                    <div class="status">

                        <?= htmlspecialchars(
                            $order['status']
                        ) ?>

                    </div>

                </div>


                <div class="order-info">

                    <div class="info-item">

                        <span class="label">
                            Người nhận:
                        </span>

                        <?= htmlspecialchars(
                            $order['customer_name']
                        ) ?>

                    </div>


                    <div class="info-item">

                        <span class="label">
                            Số điện thoại:
                        </span>

                        <?= htmlspecialchars(
                            $order['phone']
                        ) ?>

                    </div>


                    <div class="info-item">

                        <span class="label">
                            Địa chỉ:
                        </span>

                        <?= htmlspecialchars(
                            $order['address']
                        ) ?>
                        </div>


                    <div class="info-item">

                        <span class="label">
                            Ngày đặt:
                        </span>

                        <?= htmlspecialchars(
                            $order['created_at']
                        ) ?>

                    </div>


                    <?php if (!empty($order['note'])): ?>

                        <div class="info-item">

                            <span class="label">
                                Ghi chú:
                            </span>

                            <?= htmlspecialchars(
                                $order['note']
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="order-total">

                    Tổng tiền:

                    <?= number_format(
                        $order['total_amount'],
                        0,
                        ',',
                        '.'
                    ) ?>

                    VNĐ

                </div>


                <a
                    href="order_detail.php?id=<?= (int) $order['id'] ?>"
                    class="detail-button"
                >
                    Xem chi tiết đơn hàng
                </a>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>

</div>

</body>

</html>