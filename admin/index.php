<?php
require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productCount = 0;
$categoryCount = 0;
$orderCount = 0;

try {
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FashionShop - Admin Dashboard</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f5f1;
            color: #263126;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           LAYOUT
        ========================= */

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 255px;
            min-height: 100vh;
            background: #263126;
            color: white;
            padding: 30px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .brand {
            padding: 0 15px 35px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 28px;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-name span {
            color: #a9c0ae;
        }

        .brand-subtitle {
            color: #9ba79d;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 7px;
        }


        /* MENU */

        .menu-title {
            font-size: 10px;
            color: #8f9b91;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 25px 12px 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;

            color: #dce2dc;

            padding: 13px 14px;
            margin-bottom: 5px;

            border-radius: 9px;

            font-size: 14px;

            transition: 0.25s;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            transform: translateX(3px);
        }

        .sidebar a.active {
            background: #78917d;
            color: white;
            box-shadow: 0 8px 20px rgba(120,145,125,0.22);
        }

        .icon {
            width: 25px;
            text-align: center;
            font-size: 16px;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 255px;
            width: calc(100% - 255px);
            padding: 32px 42px;
        }


        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 35px;
        }

        .page-title small {
            display: block;
            color: #7c857d;
            font-size: 12px;
            margin-bottom: 7px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .page-title h1 {
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -0.8px;
        }


        /* ADMIN PROFILE */

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-info {
            text-align: right;
        }

        .admin-info strong {
            display: block;
            font-size: 14px;
        }

        .admin-info span {
            font-size: 11px;
            color: #7c857d;
        }

        .avatar {
            width: 44px;
            height: 44px;

            border-radius: 50%;

            background: #78917d;
            color: white;

            display: flex;
            justify-content: center;
            align-items: center;

            font-weight: bold;
            font-size: 14px;

            box-shadow: 0 5px 15px rgba(38,49,38,0.15);
        }


        /* =========================
           WELCOME
        ========================= */

        .welcome {
            background: #263126;
            color: white;

            padding: 30px 35px;

            border-radius: 14px;

            margin-bottom: 28px;

            position: relative;
            overflow: hidden;
        }

        .welcome::after {
            content: "";
            position: absolute;

            width: 220px;
            height: 220px;

            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50%;

            right: -60px;
            top: -80px;
        }

        .welcome::before {
            content: "";
            position: absolute;

            width: 130px;
            height: 130px;

            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 50%;

            right: 80px;
            bottom: -80px;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome h2 {
            font-size: 22px;
            margin-bottom: 9px;
        }

        .welcome p {
            color: #c3ccc4;
            font-size: 13px;
        }


        /* =========================
           STATISTICS
        ========================= */

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #4d594f;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;

            margin-bottom: 30px;
        }

        .card {
            background: white;

            padding: 24px;

            border: 1px solid #e2e6e1;

            border-radius: 12px;

            transition: 0.25s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(38,49,38,0.07);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 20px;
        }

        .card h3 {
            font-size: 12px;
            color: #7a847b;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .card-icon {
            width: 38px;
            height: 38px;

            background: #edf1ed;

            border-radius: 9px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 17px;
        }

        .number {
            font-size: 34px;
            font-weight: 700;

            letter-spacing: -1px;
        }

        .card-bottom {
            margin-top: 8px;

            color: #849087;
            font-size: 11px;
        }


        /* =========================
           QUICK ACTION
        ========================= */

        .quick {
            background: white;

            border: 1px solid #e2e6e1;

            border-radius: 12px;

            padding: 27px;

            margin-bottom: 30px;
        }

        .quick-header {
            margin-bottom: 20px;
        }

        .quick-header h2 {
            font-size: 17px;
            margin-bottom: 5px;
        }

        .quick-header p {
            color: #8a938b;
            font-size: 12px;
        }

        .quick-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .quick-btn {
            display: flex;
            align-items: center;
            gap: 13px;

            padding: 16px;

            border: 1px solid #e0e5e0;

            border-radius: 9px;

            color: #263126;

            transition: 0.25s;
        }

        .quick-btn:hover {
            border-color: #78917d;
            background: #f6f8f5;
            transform: translateY(-2px);
        }

        .quick-icon {
            width: 38px;
            height: 38px;

            background: #263126;
            color: white;

            border-radius: 8px;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quick-text strong {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .quick-text span {
            color: #89928a;
            font-size: 10px;
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {
            text-align: center;
            color: #9aa29b;
            font-size: 11px;
            padding: 10px 0 20px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width: 900px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
                padding: 25px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .quick-buttons {
                grid-template-columns: 1fr;
            }
        }


        @media(max-width: 650px) {

            .admin-wrapper {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .topbar {
                display: block;
            }

            .admin-profile {
                margin-top: 20px;
            }

            .admin-info {
                text-align: left;
            }

            .welcome {
                padding: 25px;
            }
        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-name">
                Fashion<span>Shop</span>
            </div>

            <div class="brand-subtitle">
                Administration
            </div>

        </div>


        <div class="menu-title">
            Tổng quan
        </div>

        <a href="index.php" class="active">

            <span class="icon">⌂</span>

            <span>Dashboard</span>

        </a>


        <div class="menu-title">
            Quản lý cửa hàng
        </div>

        <a href="products/index.php">

            <span class="icon">▣</span>

            <span>Sản phẩm</span>

        </a>

        <a href="categories/index.php">

            <span class="icon">▤</span>

            <span>Danh mục</span>

        </a>

        <a href="orders/index.php">

            <span class="icon">▢</span>

            <span>Đơn hàng</span>

        </a>


        <div class="menu-title">
            Website
        </div>

        <a href="../index.php">

            <span class="icon">↗</span>

            <span>Về trang chủ</span>

        </a>

    </aside>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="main">


        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <small>
                    Overview
                </small>

                <h1>
                    Dashboard
                </h1>

            </div>


            <div class="admin-profile">

                <div class="admin-info">

                    <strong>
                        .
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

                <div class="avatar">
                    zz
                </div>

            </div>

        </div>


        <!-- WELCOME -->

        <section class="welcome">

            <div class="welcome-content">

                <h2>
                    Chào mừng trở lại
                </h2>

                <p>
                    Quản lý cửa hàng FashionShop của bạn một cách dễ dàng.
                </p>

            </div>

        </section>


        <!-- STATISTICS -->

        <div class="section-title">
            Tổng quan cửa hàng
        </div>


        <div class="cards">


            <!-- PRODUCT -->

            <div class="card">

                <div class="card-top">

                    <h3>
                        Sản phẩm
                    </h3>

                    <div class="card-icon">
                        ♢
                    </div>

                </div>

                <div class="number">
                    <?= (int)$productCount ?>
                </div>

                <div class="card-bottom">
                    Tổng số sản phẩm trong cửa hàng
                </div>

            </div>


            <!-- CATEGORY -->

            <div class="card">

                <div class="card-top">

                    <h3>
                        Danh mục
                    </h3>

                    <div class="card-icon">
                        ◇
                    </div>

                </div>

                <div class="number">
                    <?= (int)$categoryCount ?>
                </div>

                <div class="card-bottom">
                    Tổng số danh mục sản phẩm
                </div>

            </div>


            <!-- ORDER -->

            <div class="card">

                <div class="card-top">

                    <h3>
                        Đơn hàng
                    </h3>

                    <div class="card-icon">
                        ○
                    </div>

                </div>

                <div class="number">
                    <?= (int)$orderCount ?>
                </div>

                <div class="card-bottom">
                    Tổng số đơn hàng hiện tại
                </div>

            </div>


        </div>


        <!-- QUICK ACTION -->

        <section class="quick">

            <div class="quick-header">

                <h2>
                    Thao tác nhanh
                </h2>

                <p>
                    Truy cập nhanh các chức năng quản trị thường dùng.
                </p>

            </div>


            <div class="quick-buttons">


                <a
                    href="products/index.php"
                    class="quick-btn"
                >

                    <div class="quick-icon">
                        ♢
                    </div>

                    <div class="quick-text">

                        <strong>
                            Quản lý sản phẩm
                        </strong>

                        <span>
                            Xem và chỉnh sửa sản phẩm
                        </span>

                    </div>

                </a>


                <a
                    href="products/create.php"
                    class="quick-btn"
                >

                    <div class="quick-icon">
                        +
                    </div>

                    <div class="quick-text">

                        <strong>
                            Thêm sản phẩm
                        </strong>

                        <span>
                            Tạo sản phẩm mới
                        </span>

                    </div>

                </a>


                <a
                    href="categories/index.php"
                    class="quick-btn"
                >

                    <div class="quick-icon">
                        ◇
                    </div>

                    <div class="quick-text">

                        <strong>
                            Quản lý danh mục
                        </strong>

                        <span>
                            Xem các danh mục
                        </span>

                    </div>

                </a>


            </div>

        </section>


        <div class="footer">

            FashionShop Admin Panel · Management System

        </div>


    </main>


</div>


</body>
</html>