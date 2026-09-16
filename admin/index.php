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
    <title>Admin Dashboard - FashionShop</title>
    <style>
        :root {
            --green-950: #0b2d23;
            --green-900: #10382c;
            --green-850: #154235;
            --green-700: #3e6f5a;
            --green-500: #789c8a;
            --green-200: #dcebe2;
            --green-100: #edf5ef;
            --cream: #fbfaf6;
            --warm: #f3ead8;
            --text: #10251e;
            --muted: #73827b;
            --line: #e8ebe8;
            --white: #ffffff;
            --shadow: 0 14px 38px rgba(15, 46, 36, .08);
            --shadow-soft: 0 8px 24px rgba(15, 46, 36, .06);
            --radius-xl: 22px;
            --radius-lg: 18px;
            --radius-md: 14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: var(--cream);
            color: var(--text);
        }

        a {
            color: inherit;
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
        }

        /* SIDEBAR */
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
            padding: 32px 20px 24px;
            color: #fff;
            background:
                radial-gradient(circle at 10% 8%, rgba(114, 162, 136, .18), transparent 32%),
                linear-gradient(180deg, #123b2f 0%, #0c2f25 100%);
            border-right: 1px solid rgba(255, 255, 255, .06);
        }

        .sidebar::before,
        .sidebar::after {
            content: "";
            position: absolute;
            pointer-events: none;
            opacity: .16;
        }

        .sidebar::before {
            width: 150px;
            height: 310px;
            left: 5px;
            bottom: 74px;
            border-radius: 100% 0 100% 0;
            border-left: 2px solid #8db09d;
            transform: rotate(28deg);
        }

        .sidebar::after {
            width: 85px;
            height: 150px;
            left: 46px;
            bottom: 120px;
            border-radius: 90% 0 90% 0;
            background: linear-gradient(135deg, rgba(142, 184, 160, .5), transparent 68%);
            transform: rotate(-24deg);
        }

        .brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 2px 10px 28px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            color: #f2e3bf;
            flex: 0 0 auto;
        }

        .brand-mark svg {
            width: 43px;
            height: 43px;
        }

        .brand-name {
            font-size: 24px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .brand-name span {
            color: #9eb9aa;
        }

        .brand-tagline {
            margin-top: 7px;
            color: rgba(255, 255, 255, .52);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2.4px;
            white-space: nowrap;
        }

        .sidebar-nav {
            position: relative;
            z-index: 1;
        }

        .nav-group {
            margin-top: 14px;
        }

        .nav-title {
            margin: 0 12px 9px;
            color: rgba(255, 255, 255, .55);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .nav-link {
            min-height: 54px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 7px;
            padding: 0 16px;
            border-radius: 13px;
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            font-size: 15px;
            font-weight: 650;
            transition: background .2s ease, transform .2s ease, color .2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, .09);
            transform: translateX(3px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #6d927e, #4f7d67);
            box-shadow: 0 12px 25px rgba(0, 0, 0, .18);
            color: #fff;
        }

        .nav-icon,
        .nav-arrow {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .nav-icon svg {
            width: 21px;
            height: 21px;
        }

        .nav-arrow {
            margin-left: auto;
            opacity: .65;
        }

        .nav-arrow svg {
            width: 16px;
            height: 16px;
        }

        .nav-divider {
            height: 1px;
            margin: 15px 12px 20px;
            background: rgba(255, 255, 255, .12);
        }

        .sidebar-note {
            position: absolute;
            z-index: 1;
            left: 22px;
            right: 22px;
            bottom: 23px;
            padding: 18px 18px 16px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            background: rgba(255, 255, 255, .055);
            backdrop-filter: blur(8px);
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            line-height: 1.6;
        }

        .sidebar-note::after {
            content: "";
            display: block;
            width: 32px;
            height: 2px;
            margin-top: 13px;
            border-radius: 99px;
            background: #e8d8af;
        }

        /* MAIN */
        .main {
            position: relative;
            min-width: 0;
            overflow: hidden;
            padding: 34px 42px 48px;
            background:
                radial-gradient(circle at 93% 4%, rgba(233, 242, 235, .9), transparent 26%),
                linear-gradient(180deg, #fcfbf8 0%, #fbfbf8 100%);
        }

        .main::after {
            content: "";
            position: absolute;
            z-index: 0;
            width: 620px;
            height: 310px;
            right: -180px;
            bottom: -130px;
            border-radius: 50% 50% 0 0;
            background: linear-gradient(135deg, rgba(221, 236, 226, .68), rgba(248, 248, 242, .15));
            transform: rotate(-12deg);
            pointer-events: none;
        }

        .main > * {
            position: relative;
            z-index: 1;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 34px;
        }

        .eyebrow {
            margin-bottom: 6px;
            color: #8aa193;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 3.4px;
            text-transform: uppercase;
        }

        .page-title {
            font-size: clamp(32px, 3.2vw, 46px);
            line-height: 1.05;
            letter-spacing: -1.8px;
            font-weight: 850;
        }

        .page-subtitle {
            margin-top: 10px;
            color: var(--muted);
            font-size: 15px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 4px;
        }

        .icon-button,
        .home-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, .82);
            box-shadow: var(--shadow-soft);
            text-decoration: none;
            color: var(--text);
        }

        .icon-button {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }

        .icon-button svg {
            width: 20px;
            height: 20px;
        }

        .home-button {
            min-height: 48px;
            gap: 9px;
            padding: 0 18px;
            border-radius: 13px;
            font-size: 14px;
            font-weight: 700;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .home-button svg {
            width: 18px;
            height: 18px;
        }

        .alert {
            margin-bottom: 22px;
            padding: 13px 16px;
            border: 1px solid #efcfcb;
            border-radius: 12px;
            background: #fff4f2;
            color: #9d4338;
            font-size: 14px;
        }

        /* STAT CARDS */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            position: relative;
            min-height: 166px;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 26px 26px;
            border: 1px solid rgba(227, 232, 227, .86);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, .88);
            box-shadow: var(--shadow-soft);
            transition: transform .22s ease, box-shadow .22s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 145px;
            height: 70px;
            right: -24px;
            bottom: -18px;
            border-radius: 70% 0 0 0;
            opacity: .78;
        }

        .stat-card.product::after,
        .stat-card.order::after {
            background: linear-gradient(135deg, rgba(214, 236, 220, .72), rgba(242, 249, 244, .16));
        }

        .stat-card.category::after {
            background: linear-gradient(135deg, rgba(245, 228, 188, .64), rgba(252, 247, 231, .13));
        }

        .stat-icon {
            width: 68px;
            height: 68px;
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 50%;
        }

        .stat-card.product .stat-icon,
        .stat-card.order .stat-icon {
            background: #e4f2e8;
            color: #2e694f;
        }

        .stat-card.category .stat-icon {
            background: #fbf0d8;
            color: #a57209;
        }

        .stat-icon svg {
            width: 32px;
            height: 32px;
        }

        .stat-content {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .stat-number {
            margin-top: 4px;
            font-size: 42px;
            line-height: 1;
            font-weight: 850;
            letter-spacing: -1.5px;
        }

        .stat-meta {
            margin-top: 9px;
            color: #8a9791;
            font-size: 12px;
        }

        /* QUICK */
        .quick-panel {
            padding: 28px;
            border: 1px solid rgba(228, 233, 229, .9);
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, .9);
            box-shadow: var(--shadow-soft);
        }

        .quick-heading {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-heading-icon {
            width: 54px;
            height: 54px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #edf4ef;
            color: #285d47;
        }

        .quick-heading-icon svg {
            width: 25px;
            height: 25px;
        }

        .quick-heading h2 {
            font-size: 24px;
            letter-spacing: -.6px;
        }

        .quick-heading p {
            margin-top: 4px;
            color: var(--muted);
            font-size: 14px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .quick-link {
            min-height: 78px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 22px;
            border-radius: 13px;
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 750;
            box-shadow: 0 12px 24px rgba(20, 65, 49, .12);
            transition: transform .22s ease, box-shadow .22s ease, filter .22s ease;
        }

        .quick-link:nth-child(1),
        .quick-link:nth-child(3) {
            background: linear-gradient(135deg, #183f32, #0e3026);
        }

        .quick-link:nth-child(2) {
            background: linear-gradient(135deg, #6f987f, #4d765f);
        }

        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 30px rgba(20, 65, 49, .2);
            filter: saturate(1.05);
        }

        .quick-link-icon,
        .quick-link-arrow {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .quick-link-icon svg {
            width: 27px;
            height: 27px;
        }

        .quick-link-arrow {
            margin-left: auto;
        }

        .quick-link-arrow svg {
            width: 21px;
            height: 21px;
        }

        .footer-brand {
            position: absolute;
            right: 38px;
            bottom: 20px;
            text-align: right;
            color: rgba(80, 107, 95, .42);
            pointer-events: none;
        }

        .footer-brand strong {
            display: block;
            font-size: 9px;
            letter-spacing: 4px;
        }

        .footer-brand span {
            display: block;
            margin-top: 8px;
            font-size: 8px;
            letter-spacing: 3px;
        }

        @media (max-width: 1080px) {
            .admin-shell {
                grid-template-columns: 245px minmax(0, 1fr);
            }

            .main {
                padding: 30px 26px 46px;
            }

            .stats,
            .quick-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                min-height: 132px;
            }

            .sidebar-note {
                display: none;
            }
        }

        @media (max-width: 760px) {
            .admin-shell {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px 16px;
            }

            .brand {
                padding-bottom: 14px;
            }

            .sidebar-nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .nav-group {
                display: contents;
            }

            .nav-title,
            .nav-divider {
                display: none;
            }

            .nav-link {
                margin: 0;
                min-height: 48px;
            }

            .sidebar::before,
            .sidebar::after,
            .sidebar-note {
                display: none;
            }

            .main {
                padding: 24px 16px 36px;
            }

            .topbar {
                align-items: flex-start;
            }

            .page-title {
                font-size: 32px;
            }

            .page-subtitle {
                max-width: 420px;
            }

            .icon-button {
                display: none;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-panel {
                padding: 20px;
            }

            .footer-brand {
                display: none;
            }
        }

        @media (max-width: 500px) {
            .sidebar-nav {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
            }

            .top-actions {
                width: 100%;
            }

            .home-button {
                width: 100%;
            }

            .stat-card {
                padding: 22px;
            }

            .stat-icon {
                width: 58px;
                height: 58px;
            }

            .stat-number {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8V6a6 6 0 0 1 12 0v2"/>
                    <path d="M4.5 8h15l1 13h-17l1-13Z"/>
                </svg>
            </div>
            <div>
                <div class="brand-name">Fashion<span>Shop</span></div>
                <div class="brand-tagline">STYLE FOR A BETTER YOU</div>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Điều hướng quản trị">
            <div class="nav-group">
                <div class="nav-title">Dashboard</div>
                <a class="nav-link active" href="index.php">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </a>
            </div>

            <div class="nav-group">
                <div class="nav-title">Quản lý</div>
                <a class="nav-link" href="products/index.php">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21 8-9 5-9-5"/><path d="m3 8 9-5 9 5v8l-9 5-9-5V8Z"/><path d="M12 13v8"/>
                        </svg>
                    </span>
                    <span>Sản phẩm</span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </a>

                <a class="nav-link" href="categories/index.php">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 13 11 22l-9-9V4h9l9 9Z"/><circle cx="7" cy="9" r="1"/>
                        </svg>
                    </span>
                    <span>Danh mục</span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </a>

                <a class="nav-link" href="orders/index.php">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M3 4h2l2.4 10.3a2 2 0 0 0 2 1.7h7.8a2 2 0 0 0 2-1.6L21 8H7"/>
                        </svg>
                    </span>
                    <span>Đơn hàng</span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </a>
            </div>

            <div class="nav-divider"></div>

            <div class="nav-group">
                <div class="nav-title">Website</div>
                <a class="nav-link" href="../index.php">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>
                        </svg>
                    </span>
                    <span>Về trang chủ</span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </a>
            </div>
        </nav>

        <div class="sidebar-note">
            Thời trang tạo nên phiên bản tốt hơn của bạn
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <div class="eyebrow">Chào mừng bạn đến với</div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Quản lý cửa hàng FashionShop một cách dễ dàng và hiệu quả</p>
            </div>

            <div class="top-actions">
                <span class="icon-button" aria-hidden="true" title="Thông báo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                    </svg>
                </span>
                <a class="home-button" href="../index.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
                    </svg>
                    Trang chủ
                </a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="stats" aria-label="Tổng quan cửa hàng">
            <article class="stat-card product">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21 8-9 5-9-5"/><path d="m3 8 9-5 9 5v8l-9 5-9-5V8Z"/><path d="M12 13v8"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tổng sản phẩm</div>
                    <div class="stat-number"><?= (int)$productCount ?></div>
                    <div class="stat-meta">Sản phẩm trong cửa hàng</div>
                </div>
            </article>

            <article class="stat-card category">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tổng danh mục</div>
                    <div class="stat-number"><?= (int)$categoryCount ?></div>
                    <div class="stat-meta">Danh mục sản phẩm</div>
                </div>
            </article>

            <article class="stat-card order">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M3 4h2l2.4 10.3a2 2 0 0 0 2 1.7h7.8a2 2 0 0 0 2-1.6L21 8H7"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tổng đơn hàng</div>
                    <div class="stat-number"><?= (int)$orderCount ?></div>
                    <div class="stat-meta">Đơn hàng đã đặt</div>
                </div>
            </article>
        </section>

        <section class="quick-panel">
            <div class="quick-heading">
                <div class="quick-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m13 2-9 12h8l-1 8 9-12h-8l1-8Z"/>
                    </svg>
                </div>
                <div>
                    <h2>Quản lý nhanh</h2>
                    <p>Truy cập nhanh các chức năng quản lý chính</p>
                </div>
            </div>

            <div class="quick-grid">
                <a class="quick-link" href="products/index.php">
                    <span class="quick-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21 8-9 5-9-5"/><path d="m3 8 9-5 9 5v8l-9 5-9-5V8Z"/><path d="M12 13v8"/>
                        </svg>
                    </span>
                    Quản lý sản phẩm
                    <span class="quick-link-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </span>
                </a>

                <a class="quick-link" href="products/create.php">
                    <span class="quick-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    </span>
                    Thêm sản phẩm
                    <span class="quick-link-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </span>
                </a>

                <a class="quick-link" href="categories/index.php">
                    <span class="quick-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </span>
                    Quản lý danh mục
                    <span class="quick-link-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </span>
                </a>
            </div>
        </section>

        <div class="footer-brand" aria-hidden="true">
            <strong>FASHIONSHOP</strong>
            <span>THỜI TRANG CHO CUỘC SỐNG TỐT ĐẸP HƠN</span>
        </div>
    </main>
</div>
</body>
</html>
