<?php
session_start();

require_once '../config/database.php';

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($orderId <= 0) {
    header('Location: history.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LẤY THÔNG TIN ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        receiver_name,
        phone,
        address,
        total_amount,
        status,
        created_at
    FROM orders
    WHERE id = ?
");

$stmt->execute([$orderId]);

$order = $stmt->fetch();

if (!$order) {
    die('Không tìm thấy đơn hàng.');
}

/*
|--------------------------------------------------------------------------
| LẤY CHI TIẾT ĐƠN HÀNG
|--------------------------------------------------------------------------
|
| order_items không có product_name.
| Lấy tên sản phẩm từ bảng products.
|
*/

$itemStmt = $pdo->prepare("
    SELECT
        oi.product_id,
        p.name AS product_name,
        oi.price,
        oi.quantity
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.id
    WHERE oi.order_id = ?
");

$itemStmt->execute([$orderId]);

$items = $itemStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Chi tiết đơn hàng</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        .container {
            width: 92%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .box {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
        }

        .info {
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #f2f2f2;
        }

        .total {
            margin-top: 20px;
            text-align: right;
            font-size: 20px;
            font-weight: bold;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>
        Chi tiết đơn hàng #<?= (int) $order['id'] ?>
    </h1>

    <div class="box">

        <h2>Thông tin đơn hàng</h2>

        <div class="info">

            <strong>Người nhận:</strong>

            <?= htmlspecialchars(
                $order['receiver_name']
            ) ?>

            <br>

            <strong>Số điện thoại:</strong>

            <?= htmlspecialchars(
                $order['phone']
            ) ?>

            <br>

            <strong>Địa chỉ:</strong>

            <?= htmlspecialchars(
                $order['address']
            ) ?>

            <br>

            <strong>Trạng thái:</strong>

            <?= htmlspecialchars(
                $order['status']
            ) ?>

            <br>

            <strong>Ngày đặt:</strong>

            <?= htmlspecialchars(
                $order['created_at']
            ) ?>

        </div>

    </div>

    <div class="box">

        <h2>Sản phẩm trong đơn hàng</h2>

        <table>

            <thead>

            <tr>

                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>

            </tr>

            </thead>

            <tbody>

            <?php foreach ($items as $item): ?>

                <?php
                $subtotal =
                    $item['price'] * $item['quantity'];
                ?>

                <tr>

                    <td>

                        <?= htmlspecialchars(
                            $item['product_name']
                                ?? 'Sản phẩm không còn tồn tại'
                        ) ?>

                    </td>

                    <td>

                        <?= number_format(
                            $item['price'],
                            0,
                            ',',
                            '.'
                        ) ?>

                        VNĐ

                    </td>

                    <td>

                        <?= (int) $item['quantity'] ?>

                    </td>

                    <td>

                        <?= number_format(
                            $subtotal,
                            0,
                            ',',
                            '.'
                        ) ?>

                        VNĐ

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

        <div class="total">

            Tổng tiền:

            <?= number_format(
                $order['total_amount'],
                0,
                ',',
                '.'
            ) ?>

            VNĐ

        </div>

    </div>

    <a
        href="history.php"
        class="button"
    >
        ← Quay lại lịch sử mua hàng
    </a>

</div>

</body>

</html>