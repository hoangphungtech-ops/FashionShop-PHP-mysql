<?php
require_once 'db.php';
require_login(); // Kiểm tra đăng nhập

$user_id = $_SESSION['user']['id'];
$msg = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (!empty($full_name)) {
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $msg = '<div class="msg error">Mật khẩu mới phải từ 6 ký tự trở lên!</div>';
            } else {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $phone, $hashed, $user_id]);
                $msg = '<div class="msg success">Cập nhật thông tin và mật khẩu thành công!</div>';
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $user_id]);
            $msg = '<div class="msg success">Cập nhật thông tin thành công!</div>';
        }
        
        // Cập nhật lại session
        $_SESSION['user']['full_name'] = $full_name;
        $_SESSION['user']['phone'] = $phone;
    }
}

// Lấy thông tin mới nhất từ database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ cá nhân</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; color: #fff; text-transform: uppercase; }
        .badge-admin { background: #dc3545; }
        .badge-staff { background: #ffc107; color: #000; }
        .badge-customer { background: #17a2b8; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 18px; background: #007bff; border: none; color: #fff; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .logout-btn { float: right; background: #6c757d; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        .role-section { margin-top: 25px; padding-top: 15px; border-top: 2px dashed #ddd; }
    </style>
</head>
<body>
<div class="container">
    <a href="logout.php" class="logout-btn">Đăng xuất</a>
    <h2>Hồ Sơ Cá Nhân</h2>
    
    <p>
        <strong>Vai trò:</strong> 
        <span class="badge badge-<?= htmlspecialchars($currentUser['role']) ?>">
            <?= htmlspecialchars($currentUser['role']) ?>
        </span>
    </p>

    <?= $msg ?>

    <form method="POST" action="profile.php">
        <div class="form-group">
            <label>Email (Không thể thay đổi):</label>
            <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Họ và tên:</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($currentUser['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Đổi mật khẩu mới (để trống nếu không đổi):</label>
            <input type="password" name="new_password" placeholder="Nhập mật khẩu mới...">
        </div>
        <button type="submit">Lưu thông tin</button>
    </form>

    <!-- Khu vực hiển thị riêng biệt theo phân quyền -->
    <div class="role-section">
        <h3>Chức năng theo quyền của bạn:</h3>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <p style="color: #dc3545; font-weight: bold;">[Khu vực Admin] Toàn quyền quản lý:</p>
            <ul>
                <li>Xem báo cáo doanh thu cửa hàng</li>
                <li>Quản lý danh sách tài khoản & Phân quyền user</li>
                <li>Xóa & Khóa tài khoản khách hàng / nhân viên</li>
            </ul>
        <?php elseif ($currentUser['role'] === 'staff'): ?>
            <p style="color: #d39e00; font-weight: bold;">[Khu vực Nhân viên]:</p>
            <ul>
                <li>Quản lý sản phẩm thời trang & Tồn kho</li>
                <li>Xác nhận đơn hàng và giao hàng</li>
            </ul>
        <?php else: ?>
            <p style="color: #17a2b8; font-weight: bold;">[Khu vực Khách hàng]:</p>
            <ul>
                <li>Xem lịch sử đặt hàng thời trang</li>
                <li>Quản lý sổ địa chỉ nhận hàng</li>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>