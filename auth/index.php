<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
} else {
    header("Location: login-register.php");
    exit();
}
?>
