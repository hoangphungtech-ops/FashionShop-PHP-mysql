<?php

require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$name = '';
$email = '';
$phone = '';
$address = '';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // =========================
    // VALIDATE
    // =========================

    if ($name === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }

    if ($email === '') {
        $errors[] = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    // =========================
    // KIỂM TRA EMAIL
    // =========================

    if (empty($errors)) {

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = 'Email này đã được sử dụng.';
        }

        $stmt->close();
    }

    // =========================
    // INSERT USER
    // =========================

    if (empty($errors)) {

        // Hash mật khẩu
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Người đăng ký luôn là user
        $role = 'user';

        $stmt = $conn->prepare(
            "INSERT INTO users 
            (name, email, password, phone, address, role)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'ssssss',
            $name,
            $email,
            $hashed_password,
            $phone,
            $address,
            $role
        );

        if ($stmt->execute()) {

            $stmt->close();

            header('Location: login.php?register=success');
            exit;

        } else {

            $errors[] = 'Đăng ký thất bại. Vui lòng thử lại.';

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng ký tài khoản</title>

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
            width: 450px;
            max-width: 95%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px;
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
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        button {
            width: 100%;
            padding: 12px;
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

        .error {
            background: #ffe5e5;
            color: #c00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error ul {
            margin: 0;
            padding-left: 20px;
        }

        .link {
            text-align: center;
            margin-top: 20px;
        }

        .link a {
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Đăng ký</h1>

    <?php if (!empty($errors)): ?>

        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li>
                        <?= htmlspecialchars($error) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
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

        <div class="form-group">
            <label>Mật khẩu</label>

            <input
                type="password"
                name="password"
                required
            >
        </div>

        <div class="form-group">
            <label>Xác nhận mật khẩu</label>

            <input
                type="password"
                name="confirm_password"
                required
            >
        </div>

        <button type="submit">
            Đăng ký
        </button>

    </form>

    <div class="link">
        Đã có tài khoản?
        <a href="login.php">Đăng nhập</a>
    </div>

</div>

</body>
</html>
