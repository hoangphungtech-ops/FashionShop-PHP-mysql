<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin(app_url('auth/login.php'));

$productCount = 0;
$categoryCount = 0;
$orderCount = 0;

try {
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} catch (PDOException $e) {
    error_log('[admin-dashboard] Cannot load counters: ' . $e->getMessage());
    $error = 'Chưa thể tải số liệu dashboard.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Fashion Shop</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7f5;
            color: #263126;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #263126;
            color: white;
            padding: 30px 20px;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 40px;
            text-align: center;
        }

        .logo span {
            color: #a9c0ae;
        }

        .menu-title {
            font-size: 12px;
            color: #aab5ac;
            margin: 20px 10px 10px;
            text-transform: uppercase;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 5px;
            border-radius: 6px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #78917d;
        }

        .main {
            flex: 1;
            padding: 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .topbar h1 {
            font-size: 30px;
        }

        .back {
            text-decoration: none;
            color: #263126;
            background: white;
            padding: 11px 18px;
            border: 1px solid #ddd;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .card {
            background: white;
            padding: 28px;
            border: 1px solid #e0e5e0;
        }

        .card h3 {
            color: #697369;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .card .number {
            font-size: 35px;
            font-weight: bold;
        }

        .quick {
            margin-top: 35px;
            background: white;
            padding: 30px;
        }

        .quick h2 {
            margin-bottom: 20px;
        }

        .quick a {
            display: inline-block;
            padding: 13px 20px;
            background: #263126;
            color: white;
            text-decoration: none;
            margin-right: 10px;
        }

        .quick a:hover {
            background: #78917d;
        }

        @media(max-width: 800px) {
            .sidebar {
                width: 200px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {
            .admin-wrapper {
                display: block;
            }

            .sidebar {
                width: 100%;
            }

            .main {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="admin-wrapper">

    <aside class="sidebar">

        <div class="logo">
            Fashion<span>Shop</span>
        </div>

        <div class="menu-title">Dashboard</div>

        <a href="index.php" class="active">
            Dashboard
        </a>

        <div class="menu-title">Quản lý</div>

        <a href="products/index.php">
            Sản phẩm
        </a>

        <a href="categories/index.php">
            Danh mục
        </a>

        <a href="orders/index.php">
            Đơn hàng
        </a>

        <div class="menu-title">Website</div>

        <a href="../index.php">
            Về trang chủ
        </a>

    </aside>

    <main class="main">

        <div class="topbar">

            <h1>
                Admin Dashboard
            </h1>

            <a href="../index.php" class="back">
                ← Trang chủ
            </a>

        </div>

        <div class="cards">

            <div class="card">
                <h3>Tổng sản phẩm</h3>
                <div class="number">
                    <?= (int)$productCount ?>
                </div>
            </div>

            <div class="card">
                <h3>Tổng danh mục</h3>
                <div class="number">
                    <?= (int)$categoryCount ?>
                </div>
            </div>

            <div class="card">
                <h3>Tổng đơn hàng</h3>
                <div class="number">
                    <?= (int)$orderCount ?>
                </div>
            </div>

        </div>

        <div class="quick">

            <h2>
                Quản lý nhanh
            </h2>

            <a href="products/index.php">
                Quản lý sản phẩm
            </a>

            <a href="products/create.php">
                + Thêm sản phẩm
            </a>

            <a href="categories/index.php">
                Quản lý danh mục
            </a>

        </div>

    </main>

</div>

</body>
</html>
