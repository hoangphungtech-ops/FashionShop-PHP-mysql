<?php

require_once '../config/database.php';
require_once 'auth_check.php';

requireLogin();

$user_id = $_SESSION['user']['id'];

$success = '';
$error = '';

$name = '';
$email = '';
$phone = '';
$address = '';
$role = '';

/*
|--------------------------------------------------------------------------
| Lấy thông tin user hiện tại
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, address, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param('i', $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    session_destroy();

    header('Location: login.php');
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();

$name = $user['name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| Lấy danh sách đơn hàng của user hiện tại
|--------------------------------------------------------------------------
*/

$orders = [];
$order_items = [];

$order_stmt = $conn->prepare(
    "SELECT id, receiver_name, phone, address, total_amount, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$order_stmt->bind_param('i', $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

while ($order = $order_result->fetch_assoc()) {
    $orders[] = $order;
}
$order_stmt->close();

if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $types = str_repeat('i', count($order_ids));

    $item_stmt = $conn->prepare(
        "SELECT oi.order_id, oi.quantity, oi.price,
                p.name AS product_name, p.image AS product_image
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.id ASC"
    );
    $item_stmt->bind_param($types, ...$order_ids);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();

    while ($item = $item_result->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
    $item_stmt->close();
}

$status_text = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao hàng',
    'completed' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
];

$status_class = [
    'pending' => 'status-pending',
    'confirmed' => 'status-confirmed',
    'shipping' => 'status-shipping',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled'
];

/*
|--------------------------------------------------------------------------
| Cập nhật thông tin
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = 'Họ tên không được để trống.';

    } elseif ($email === '') {

        $error = 'Email không được để trống.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Email không hợp lệ.';

    } elseif (
        $new_password !== '' &&
        strlen($new_password) < 6
    ) {

        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';

    } elseif (
        $new_password !== $confirm_password
    ) {

        $error = 'Xác nhận mật khẩu mới không khớp.';
    }

    /*
    |--------------------------------------------------------------------------
    | Kiểm tra email có bị người khác sử dụng không
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->bind_param(
            'si',
            $email,
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = 'Email này đã được tài khoản khác sử dụng.';
        }

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | Update database
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        if ($new_password !== '') {

            // Có đổi mật khẩu
            $hashed_password = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "UPDATE users
                 SET name = ?,
                     email = ?,
                     phone = ?,
                     address = ?,
                     password = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'sssssi',
                $name,
                $email,
                $phone,
                $address,
                $hashed_password,
                $user_id
            );

        } else {

            // Không đổi mật khẩu
            $stmt = $conn->prepare(
                "UPDATE users
                 SET name = ?,
                     email = ?,
                     phone = ?,
                     address = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ssssi',
                $name,
                $email,
                $phone,
                $address,
                $user_id
            );
        }

        if ($stmt->execute()) {

            $success = 'Cập nhật thông tin thành công.';

            /*
            |--------------------------------------------------------------------------
            | Cập nhật lại SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;

        } else {

            $error = 'Cập nhật thất bại. Vui lòng thử lại.';
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Thông tin cá nhân</title>

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
            width: 650px;
            max-width: 95%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
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
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        input[readonly] {
            background: #eee;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #333;
        }

        .success {
            background: #e4f8e8;
            color: #137333;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error {
            background: #ffe5e5;
            color: #c00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .account-info {
            background: #f7f7f7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .role {
            font-weight: bold;
            color: #0066cc;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #0066cc;
            text-decoration: none;
            margin: 0 8px;
        }


        /* Đơn hàng của tôi - phần được thêm vào */
        .orders-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #ddd;
        }
        .orders-section h3 { margin-bottom: 18px; }
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 18px;
            background: #fff;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .order-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cff4fc; color: #055160; }
        .status-shipping { background: #cfe2ff; color: #084298; }
        .status-completed { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #842029; }
        .order-info {
            color: #555;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 12px;
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid #eee;
        }
        .order-item img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .order-item-info { flex: 1; }
        .order-item-name { font-weight: bold; margin-bottom: 5px; }
        .order-item-price { color: #555; font-size: 14px; }
        .order-total {
            text-align: right;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-weight: bold;
            font-size: 16px;
        }
        .no-orders {
            background: #f7f7f7;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            color: #666;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Thông tin cá nhân</h1>

    <div class="account-info">

        <strong>Loại tài khoản:</strong>

        <span class="role">
            <?= $role === 'admin' ? 'Admin' : 'User' ?>
        </span>

    </div>

    <?php if ($success !== ''): ?>

        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>Họ và tên</label>

            <input
                type="text"
                name="name"
                value="<?= htmlspecialchars($name) ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Email</label>

            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Số điện thoại</label>

            <input
                type="text"
                name="phone"
                value="<?= htmlspecialchars($phone) ?>"
            >

        </div>

        <div class="form-group">

            <label>Địa chỉ</label>

            <textarea name="address"><?= htmlspecialchars($address) ?></textarea>

        </div>

        <hr>

        <h3>Đổi mật khẩu</h3>

        <p>
            Nếu không muốn đổi mật khẩu, hãy để trống hai ô bên dưới.
        </p>

        <div class="form-group">

            <label>Mật khẩu mới</label>

            <input
                type="password"
                name="new_password"
                minlength="6"
            >

        </div>

        <div class="form-group">

            <label>Xác nhận mật khẩu mới</label>

            <input
                type="password"
                name="confirm_password"
                minlength="6"
            >

        </div>

        <button type="submit">
            Cập nhật thông tin
        </button>

    </form>


    <!-- Đơn hàng của tôi - phần được thêm vào -->
    <div class="orders-section">
        <h3>Đơn hàng của tôi</h3>

        <?php if (empty($orders)): ?>

            <div class="no-orders">
                Bạn chưa có đơn hàng nào.
            </div>

        <?php else: ?>

            <?php foreach ($orders as $order): ?>

                <div class="order-card">

                    <div class="order-header">
                        <strong>Đơn hàng #<?= htmlspecialchars($order['id']) ?></strong>

                        <span class="order-status <?= htmlspecialchars($status_class[$order['status']] ?? '') ?>">
                            <?= htmlspecialchars($status_text[$order['status']] ?? $order['status']) ?>
                        </span>
                    </div>

                    <div class="order-info">
                        <div>
                            <strong>Người nhận:</strong>
                            <?= htmlspecialchars($order['receiver_name']) ?>
                        </div>
                        <div>
                            <strong>Số điện thoại:</strong>
                            <?= htmlspecialchars($order['phone']) ?>
                        </div>
                        <div>
                            <strong>Địa chỉ:</strong>
                            <?= htmlspecialchars($order['address']) ?>
                        </div>
                        <div>
                            <strong>Ngày đặt:</strong>
                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($order_items[$order['id']])): ?>

                        <?php foreach ($order_items[$order['id']] as $item): ?>

                            <div class="order-item">

                                <?php if (!empty($item['product_image'])): ?>
                                    <img
                                        src="../<?= htmlspecialchars($item['product_image']) ?>"
                                        alt="<?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?>"
                                    >
                                <?php endif; ?>

                                <div class="order-item-info">
                                    <div class="order-item-name">
                                        <?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm đã xóa') ?>
                                    </div>

                                    <div class="order-item-price">
                                        <?= number_format((float)$item['price'], 0, ',', '.') ?> đ
                                        × <?= (int)$item['quantity'] ?>
                                    </div>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    <div class="order-total">
                        Tổng tiền:
                        <?= number_format((float)$order['total_amount'], 0, ',', '.') ?> đ
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div class="links">

        <a href="../index.php">Trang chủ</a>

        |

        <a href="logout.php">Đăng xuất</a>

    </div>

</div>

</body>
</html>
