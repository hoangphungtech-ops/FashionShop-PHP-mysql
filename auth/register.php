<?php
require_once 'db.php';

$error = '';
$success = '';

if (isset($_SESSION['user'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ họ tên, email và mật khẩu!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Định dạng email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự!';
    } elseif ($password !== $confirm_password) {
        $error = 'Xác nhận mật khẩu không khớp!';
    } else {
        // Kiểm tra trùng email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email này đã được sử dụng!';
        } else {
            // Mã hóa mật khẩu an toàn
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
            if ($insertStmt->execute([$full_name, $email, $phone, $hashed_password])) {
                $success = 'Đăng ký thành công! <a href="login.php">Đăng nhập ngay</a>';
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 360px; }
        .box h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .box input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .box button { width: 100%; padding: 10px; background: #007bff; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .box button:hover { background: #0056b3; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 12px; font-size: 14px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
<div class="box">
    <h2>Đăng Ký Tài Khoản</h2>
    <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
    
    <form method="POST" action="register.php">
        <input type="text" name="full_name" placeholder="Họ và tên" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Địa chỉ Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="text" name="phone" placeholder="Số điện thoại" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
        <button type="submit">Tạo tài khoản</button>
    </form>
    <p style="text-align: center; font-size: 14px; margin-top: 15px;">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</div>
</body>
</html>