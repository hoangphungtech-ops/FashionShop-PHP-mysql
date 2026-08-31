<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /FashionShop-PHP-mysql/auth/profile.php");
    exit();
} else {
    header("Location: /FashionShop-PHP-mysql/auth/login-register.php");
    exit();
}
?>
