<?php

require_once '../config/database.php';
require_once 'auth_check.php';

require_login();

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

    <div class="links">

        <a href="../index.php">Trang chủ</a>

        |

        <a href="logout.php">Đăng xuất</a>

    </div>

</div>

</body>
</html>
