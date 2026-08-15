<?php
session_start();

require_once '../config/database.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: index.php');
    exit;
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

    $customerName = trim($_POST['customer_name'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $note         = trim($_POST['note'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA THÔNG TIN
    |--------------------------------------------------------------------------
    */
    if ($customerName === '') {
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
                    customer_name,
                    phone,
                    address,
                    note,
                    total_amount,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $status = 'Pending';

            $stmt->execute([
                $customerName,
                $phone,
                $address,
                $note,
                $total,
                $status
            ]);

            /*
            |--------------------------------------------------------------------------
            | LẤY ID ĐƠN HÀNG VỪA TẠO
            |--------------------------------------------------------------------------
            */
            $orderId = $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | LƯU CHI TIẾT ĐƠN HÀNG
            |--------------------------------------------------------------------------
            */
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    productid,
                    product_name,
                    price,
                    quantity,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart as $item) {

                $subtotal =
                    $item['price'] * $item['quantity'];

                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $subtotal
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
            | XÓA GIỎ HÀNG SAU KHI ĐẶT HÀNG THÀNH CÔNG
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Thanh toán - Fashion Shop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        .box {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .box h2 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
        }

        .order-item-name {
            flex: 1;
        }

        .total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 20px;
            font-weight: bold;
        }

        .button {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            border: none;
            border-radius: 6px;
            background: #198754;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .back {
            display: inline-block;
            margin-top: 15px;
            color: #333;
            text-decoration: none;
        }

        @media (max-width: 800px) {

            .checkout {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <h1>Thanh toán & Đặt hàng</h1>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="checkout">

        <!-- THÔNG TIN NHẬN HÀNG -->

        <div class="box">

            <h2>Thông tin nhận hàng</h2>

            <form method="POST">

                <div class="form-group">

                    <label for="customer_name">
                        Họ và tên *
                    </label>

                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?= htmlspecialchars(
                            $_POST['customer_name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="phone">
                        Số điện thoại *
                        </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $_POST['phone'] ?? ''
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="address">
                        Địa chỉ nhận hàng *
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        required
                    ><?= htmlspecialchars(
                        $_POST['address'] ?? ''
                    ) ?></textarea>

                </div>

                <div class="form-group">

                    <label for="note">
                        Ghi chú
                    </label>

                    <textarea
                        id="note"
                        name="note"
                    ><?= htmlspecialchars(
                        $_POST['note'] ?? ''
                    ) ?></textarea>

                </div>
                <button

                    type="submit"

                    class="button"

                >

                    Xác nhận đặt hàng

                </button>

            </form>

            <a

                href="index.php"

                class="back"

            >

                ← Quay lại giỏ hàng

            </a>

        </div>

        <!-- TÓM TẮT ĐƠN HÀNG -->

        <div class="box">

            <h2>Đơn hàng của bạn</h2>

            <?php foreach ($cart as $item): ?>

                <?php

                $subtotal =

                    $item['price'] * $item['quantity'];

                ?>

                <div class="order-item">

                    <div class="order-item-name">

                        <?= htmlspecialchars(

                            $item['name']

                        ) ?>

                        <br>

                        <small>

                            Số lượng:

                            <?= $item['quantity'] ?>

                        </small>

                    </div>

                    <strong>

                        <?= number_format(

                            $subtotal,

                            0,

                            ',',

                            '.'

                        ) ?>

                        VNĐ

                    </strong>

                </div>

            <?php endforeach; ?>

            <div class="total">

                <span>Tổng cộng:</span>

                <span>

                    <?= number_format(

                        $total,

                        0,

                        ',',

                        '.'

                    ) ?>

                    VNĐ

                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>
            