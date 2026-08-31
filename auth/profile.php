<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login-register.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $update_query = "UPDATE users SET phone = ?, address = ?";
    $params = [$phone, $address];
    $has_error = false;

    if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $message = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ thông tin để đổi mật khẩu!</div>";
            $has_error = true;
        } else {
            if (password_verify($old_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query .= ", password = ?";
                    $params[] = $hashed_password;
                } else {
                    $message = "<div class='alert alert-danger'>Mật khẩu mới không khớp!</div>";
                    $has_error = true;
                }
            } else {
                $message = "<div class='alert alert-danger'>Mật khẩu hiện tại không chính xác!</div>";
                $has_error = true;
            }
        }
    }

    if (!$has_error) {
        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($update_query);
        if ($stmt->execute($params)) {
            $message = "<div class='alert alert-success'>Cập nhật thông tin thành công!</div>";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi cập nhật.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Xin chào, <?= htmlspecialchars($user['name']) ?>!</h2>
                    <a href="logout.php" class="btn btn-danger">Đăng xuất</a>
                </div>

                <?= $message ?>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-4">Thông tin tài khoản</h4>
                        
                        <div class="mb-4">
                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                            <p><strong>Vai trò:</strong> <?= htmlspecialchars(strtoupper($user['role'])) ?></p>
                            <p><strong>Ngày tham gia:</strong> <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                        </div>

                        <hr>
                        <h5 class="mb-3">Cập nhật thông tin</h5>
                        
                        <form method="POST" action="" onsubmit="return validateProfilePassword()">
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                            </div>
                            
                            <div class="alert alert-secondary mt-4">
                                <h6>Đổi mật khẩu (Bỏ trống nếu không muốn đổi)</h6>
                                
                                <div class="mb-3 mt-3">
                                    <label class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" id="old-password" name="old_password" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" id="new-password" name="new_password" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" id="confirm-password" name="confirm_password" class="form-control">
                                    <div id="profile-password-error" class="text-danger mt-1" style="display:none; font-size:0.875em;">Mật khẩu mới không khớp!</div>
                                    <div id="profile-empty-error" class="text-danger mt-1" style="display:none; font-size:0.875em;">Vui lòng điền đủ 3 ô nếu muốn đổi mật khẩu!</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Lưu thay đổi</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validateProfilePassword() {
            const oldPass = document.getElementById('old-password').value;
            const newPass = document.getElementById('new-password').value;
            const confirmPass = document.getElementById('confirm-password').value;
            
            const matchError = document.getElementById('profile-password-error');
            const emptyError = document.getElementById('profile-empty-error');
            
            // Reset hiển thị lỗi
            matchError.style.display = 'none';
            emptyError.style.display = 'none';
            
            // Kiểm tra xem người dùng có đang cố gắng đổi mật khẩu không
            if (oldPass !== '' || newPass !== '' || confirmPass !== '') {
                // Kiểm tra xem có bỏ trống ô nào không
                if (oldPass === '' || newPass === '' || confirmPass === '') {
                    emptyError.style.display = 'block';
                    return false; // Ngăn submit form
                }
                
                // Kiểm tra 2 ô mật khẩu mới có khớp không
                if (newPass !== confirmPass) {
                    matchError.style.display = 'block';
                    return false; // Ngăn submit form
                }
            }
            
            return true; // Cho phép submit form
        }
    </script>
</body>
</html>