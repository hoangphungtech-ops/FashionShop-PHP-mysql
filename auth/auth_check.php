<?php
// Khởi động session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Kiểm tra vai trò của người dùng hiện tại
 * @param string $role Tên vai trò cần so sánh ('admin', 'staff', 'customer')
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

/**
 * Bắt buộc đăng nhập (Dành cho mọi trang yêu cầu tài khoản)
 * Chưa đăng nhập -> Điều hướng về login.php
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Vui lòng đăng nhập để tiếp tục!";
        header("Location: login.php");
        exit();
    }
}

/**
 * Bắt buộc quyền Admin (Dành riêng cho các trang quản trị hệ thống)
 * Không phải Admin -> Báo lỗi 403 Forbidden và dừng thực thi
 */
function requireAdmin() {
    requireLogin(); // Đảm bảo đã đăng nhập trước
    
    if (!hasRole('admin')) {
        http_response_code(403);
        echo "
        <!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <title>403 - Không có quyền truy cập</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .error-card { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; max-width: 450px; }
                h1 { color: #dc3545; font-size: 50px; margin: 0 0 10px; }
                p { color: #6c757d; font-size: 16px; margin-bottom: 25px; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .btn:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class='error-card'>
                <h1>403</h1>
                <h3>Truy cập bị từ chối</h3>
                <p>Bạn không có quyền <strong>Quản trị viên (Admin)</strong> để truy cập khu vực này.</p>
                <a href='profile.php' class='btn'>Về trang cá nhân</a>
            </div>
        </body>
        </html>";
        exit();
    }
}

/**
 * Kiểm tra danh sách quyền linh hoạt (VD: Cho phép cả Admin và Staff vào quản lý kho/đơn)
 * @param array $allowedRoles Danh sách các role được phép truy cập
 */
function requireRoles(array $allowedRoles) {
    requireLogin();
    
    $currentRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);
        die("<h3 style='color:red; text-align:center; margin-top:50px;'>403 Forbidden: Bạn không có quyền thực hiện thao tác này!</h3>");
    }
}