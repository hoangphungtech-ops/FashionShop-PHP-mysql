<?php

require_once('./config/config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    
    header("Location: /FashionShop-PHP-mysql/auth/login-register.php"); 
    
    exit(); 
}
?>