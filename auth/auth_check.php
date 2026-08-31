<?php
/**
 * File: auth/auth_check.php
 * Module: Kiểm tra phiên đăng nhập & Phân quyền chi tiết (User / Staff / Admin)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. CÁC HÀM KIỂM TRA TRẠNG THÁI (BOOLEAN)
// ==========================================

/**
 * Lấy dữ liệu user hiện tại trong session
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Kiểm tra xem tài khoản hiện tại có phải là Admin không
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Kiểm tra xem tài khoản hiện tại có phải là Nhân viên (Staff) không
 */
function isStaff() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'staff';
}

/**
 * Kiểm tra xem tài khoản hiện tại có phải là Khách hàng (Customer / User) không
 */
function isCustomer() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'customer';
}

/**
 * Kiểm tra vai trò tùy ý
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

// ==========================================
// 2. CÁC HÀM CHẶN VÀ ÉP BUỘC QUYỀN TRUY CẬP
// ==========================================

/**
 * Bắt buộc phải đăng nhập (Dành cho trang chung: Xem giỏ hàng, Profile...)
 * Chưa đăng nhập -> Điều hướng về login.php
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        $_SESSION['auth_error'] = "Vui lòng đăng nhập để tiếp tục!";
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Bắt buộc quyền Quản trị viên (Admin)
 * Không phải Admin -> Chặn và trả về giao diện lỗi 403
 */
function requireAdmin() {
    requireLogin('login.php');

    if (!isAdmin()) {
        renderForbiddenView("Khu vực này chỉ dành riêng cho <strong>Quản trị viên (Admin)</strong>.");
    }
}

/**
 * Bắt buộc quyền Nhân viên hoặc Quản trị viên (Admin & Staff)
 */
function requireStaffOrAdmin() {
    requireLogin('login.php');

    if (!isAdmin() && !isStaff()) {
        renderForbiddenView("Trang này chỉ dành cho <strong>Nhân viên quản lý</strong> hoặc <strong>Quản trị viên</strong>.");
    }
}

/**
 * Bắt buộc quyền theo danh sách vai trò cho phép linh hoạt
 * @param array $allowedRoles Danh sách vai trò, ví dụ: ['admin', 'staff']
 */
function requireRoles(array $allowedRoles) {
    requireLogin('login.php');

    $role = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        renderForbiddenView("Tài khoản của bạn không có đủ thẩm quyền truy cập trang này.");
    }
}

// ==========================================
// 3. GIAO DIỆN BÁO LỖI 403 FORBIDDEN
// ==========================================

/**
 * Hiển thị trang chặn quyền chuẩn UX/UI
 */
function renderForbiddenView($message) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Truy cập bị từ chối</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .error-card { background: #fff; padding: 40px 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 480px; width: 90%; }
            h1 { color: #dc3545; font-size: 56px; margin: 0 0 10px; font-weight: 800; }
            h3 { color: #343a40; margin: 0 0 15px; font-size: 20px; }
            p { color: #6c757d; font-size: 15px; line-height: 1.5; margin-bottom: 25px; }
            .btn { display: inline-block; padding: 10px 22px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h1>403</h1>
            <h3>Truy cập bị từ chối!</h3>
            <p><?= $message ?></p>
            <a href="profile.php" class="btn">Quay lại trang cá nhân</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}