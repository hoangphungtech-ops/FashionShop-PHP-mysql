<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $message = "<div class='alert alert-danger'>Email đã được sử dụng!</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $phone, $address])) {
                $message = "<div class='alert alert-success'>Đăng ký thành công! Vui lòng đăng nhập.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra. Vui lòng thử lại.</div>";
            }
        }
    } elseif ($action == 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: /FashionShop-PHP-mysql");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>Email hoặc mật khẩu không chính xác!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập / Đăng ký</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container { display: none; }
        .form-container.active { display: block; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <?= $message ?>
                
                <div id="login-form" class="card shadow-sm form-container active">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Đăng Nhập</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Đăng Nhập</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Chưa có tài khoản? <a href="#" onclick="toggleForms()">Đăng ký ngay</a></p>
                        </div>
                    </div>
                </div>

                <div id="register-form" class="card shadow-sm form-container">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Đăng Ký</h3>
                        <form method="POST" action="" onsubmit="return validatePassword()">
                            <input type="hidden" name="action" value="register">
                            <div class="mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" id="reg-password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nhập lại mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" id="reg-confirm-password" class="form-control" required>
                                <div id="password-error" class="text-danger mt-1" style="display:none; font-size:0.875em;">Mật khẩu không khớp!</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại (Tuỳ chọn)</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ (Tuỳ chọn)</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Đăng Ký</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Đã có tài khoản? <a href="#" onclick="toggleForms()">Đăng nhập</a></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleForms() {
            document.getElementById('login-form').classList.toggle('active');
            document.getElementById('register-form').classList.toggle('active');
        }

        function validatePassword() {
            const pass = document.getElementById('reg-password').value;
            const confirmPass = document.getElementById('reg-confirm-password').value;
            const error = document.getElementById('password-error');
            
            if (pass !== confirmPass) {
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }
    </script>
</body>
</html>
