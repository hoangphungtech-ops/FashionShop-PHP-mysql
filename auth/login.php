<?php

require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, phone, address, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Kiểm tra mật khẩu đã hash
            if (password_verify($password, $user['password'])) {

                // Tạo session mới chống session fixation
                session_regenerate_id(true);

                // Không lưu password vào session
                unset($user['password']);

                $_SESSION['user'] = $user;

                $stmt->close();

                header('Location: ../index.php');
                exit;

            } else {

                $error = 'Email hoặc mật khẩu không chính xác.';
            }

        } else {

            $error = 'Email hoặc mật khẩu không chính xác.';
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

    <title>Đăng nhập</title>

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
            width: 420px;
            max-width: 95%;
            margin: 80px auto;
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
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
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

        .success {
            background: #e5ffe9;
            color: #087b20;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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

    <h1>Đăng nhập</h1>

    <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>

        <div class="success">
            Đăng ký thành công! Vui lòng đăng nhập.
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

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

            <label>Mật khẩu</label>

            <input
                type="password"
                name="password"
                required
            >

        </div>

        <button type="submit">
            Đăng nhập
        </button>

    </form>

    <div class="link">

        Chưa có tài khoản?
        <a href="register.php">Đăng ký ngay</a>

    </div>

</div>

</body>
</html>
