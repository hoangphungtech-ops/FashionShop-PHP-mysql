<?php
session_start();

require_once '../config/database.php';

/*
|--------------------------------------------------------------------------
| Khởi tạo giỏ hàng
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
|--------------------------------------------------------------------------
| Thêm sản phẩm vào giỏ
| URL dự kiến:
| cart/index.php?action=add&id=1
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {

    $productId = (int) $_GET['id'];

    if ($productId > 0) {

        /*
         * Kiểm tra sản phẩm tồn tại.
         * Tên bảng/cột products sẽ được kết nối với phần
         * sản phẩm của nhóm khi phần đó được hoàn thiện.
         */
        $stmt = $pdo->prepare(
            "SELECT id, name, price FROM products WHERE id = ?"
        );

        $stmt->execute([$productId]);

        $product = $stmt->fetch();

        if ($product) {

            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity']++;
            } else {
                $_SESSION['cart'][$productId] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => 1
                ];
            }
        }
    }

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Xóa sản phẩm
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'remove') {

    $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Tăng số lượng
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'increase') {

    $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity']++;
    }

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Giảm số lượng
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'decrease') {

    $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (isset($_SESSION['cart'][$productId])) {

        if ($_SESSION['cart'][$productId]['quantity'] > 1) {
            $_SESSION['cart'][$productId]['quantity']--;
            } else {
            unset($_SESSION['cart'][$productId]);
        }
    }

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Cập nhật số lượng
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_cart'])) {

    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {

        foreach ($_POST['quantity'] as $productId => $quantity) {

            $productId = (int) $productId;
            $quantity = (int) $quantity;

            if (!isset($_SESSION['cart'][$productId])) {
                continue;
            }

            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            }
        }
    }

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Tính tổng tiền
|--------------------------------------------------------------------------
*/
$total = 0;

foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Giỏ hàng - Fashion Shop</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
        }

        .cart-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #f2f2f2;
        }

        .quantity {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .quantity a {
            text-decoration: none;
            background: #ddd;
            color: #000;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .quantity input {
            width: 50px;
            text-align: center;
            padding: 5px;
        }

        .remove {
            color: red;
            text-decoration: none;
        }

        .bottom {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .button {
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .update {
            background: #333;
            color: white;
        }

        .checkout {
            background: #198754;
            color: white;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 40px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Giỏ hàng</h1>

    <div class="cart-box">

        <?php if (empty($_SESSION['cart'])): ?>

            <div class="empty">
                <p>Giỏ hàng đang trống.</p>
            </div>

        <?php else: ?>

            <form method="POST" action="index.php">

                <table>

                    <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($_SESSION['cart'] as $item): ?>

                        <?php
                        $subtotal =
                            $item['price'] * $item['quantity'];
                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($item['name']) ?>
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

                                <div class="quantity">

                                    <a href="index.php?action=decrease&id=<?= $item['id'] ?>">
                                        −
                                    </a>

                                    <input
                                        type="number"
                                        min="1"
                                        name="quantity[<?= $item['id'] ?>]"
                                        value="<?= $item['quantity'] ?>"
                                    >

                                    <a href="index.php?action=increase&id=<?= $item['id'] ?>">
                                        +
                                    </a>

                                </div>

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

                            <td>

                                <a
                                    class="remove"
                                    href="index.php?action=remove&id=<?= $item['id'] ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');"
                                >
                                    Xóa
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

                <div class="bottom">

                    <button
                        type="submit"
                        name="update_cart"
                        class="button update"
                    >
                        Cập nhật giỏ hàng
                    </button>

                    <div class="total">
                        Tổng:
                        <?= number_format(
                            $total,
                            0,

                            ',',

                            '.'

                        ) ?>

                        VNĐ

                    </div>

                    <a

                        href="checkout.php"

                        class="button checkout"

                    >

                        Thanh toán

                    </a>

                </div>

            </form>

        <?php endif; ?>

    </div>

</div>

</body>

</html>