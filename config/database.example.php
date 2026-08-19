<?php

$host = 'localhost';
$dbname = 'fashion_shop';
$username = 'root';
$password = 'your_mysql_password';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Kết nối database thất bại: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');