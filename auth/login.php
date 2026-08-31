<?php
require_once __DIR__ . "/../includes/db.php";

// require_once 'auth_check.php';

$error = '';

// Nếu đã đăng nhập từ trước mà cố vào lại login.php -> Tự động redirect theo vai trò
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/admin_users.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

// Xử lý khi nhấn nút Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 1. Kiểm tra tài khoản và mật khẩu
        if ($user && password_verify($password, $user['password'])) {
            
            // 2. Chặn nếu tài khoản bị Admin khóa
            if ($user['status'] === 'banned') {
                $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin!';
            } else {
                // 3. Khởi tạo Session
                $_SESSION['user'] = [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'phone'     => $user['phone'],
                    'role'      => $user['role'] // 'admin', 'staff', 'customer'
                ];

                // 4. XỬ LÝ REDIRECT THEO PHÂN QUYỀN
                if ($user['role'] === 'admin') {
                    // Admin đăng nhập -> Chuyển thẳng tới trang quản trị
                    header("Location: ../admin/admin_users.php");
                } elseif ($user['role'] === 'staff') {
                    // Nhân viên đăng nhập -> Chuyển tới trang nhân viên / hồ sơ
                    header("Location: profile.php");
                } else {
                    // Khách hàng -> Chuyển tới trang cá nhân hoặc trang chủ mua hàng
                    header("Location: profile.php");
                }
                exit();
            }
        } else {
            $error = 'Email hoặc mật khẩu không chính xác!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; padding: 35px 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 380px; }
        .box h2 { text-align: center; margin: 0 0 20px 0; color: #212529; font-size: 22px; }
        .box input { width: 100%; padding: 10px 12px; margin: 8px 0 14px 0; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }
        .box button { width: 100%; padding: 11px; background: #007bff; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .box button:hover { background: #0056b3; }
        .msg { padding: 10px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .footer-link { text-align: center; font-size: 14px; margin-top: 18px; color: #6c757d; }
        .footer-link a { color: #007bff; text-decoration: none; font-weight: 500; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="box">
    <h2>Đăng Nhập</h2>
    
    <?php if ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Địa chỉ Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
    </form>

    <div class="footer-link">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </div>
</div>
</body>
</html>