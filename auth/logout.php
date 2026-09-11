<?php

session_start();

// Xóa toàn bộ dữ liệu session
$_SESSION = [];

// Xóa session cookie nếu có
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hủy session
session_destroy();

// Quay về trang đăng nhập
header('Location: login.php');
exit;
