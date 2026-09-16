<?php

require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, phone, address, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                unset($user['password']);
                $_SESSION['user'] = $user;

                $stmt->close();
                header('Location: ../index.php');
                exit;
            }

            $error = 'Email hoặc mật khẩu không chính xác.';
        } else {
            $error = 'Email hoặc mật khẩu không chính xác.';
        }

        $stmt->close();
    }
}

$cartCount = 0;
$headerCart = $_SESSION['cart'] ?? [];
if (is_array($headerCart)) {
    foreach ($headerCart as $cartValue) {
        $quantity = is_array($cartValue) ? ($cartValue['quantity'] ?? 0) : $cartValue;
        if (is_numeric($quantity) && (int)$quantity > 0) {
            $cartCount += (int)$quantity;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Đăng nhập | FashionShop</title>
    <style>
        :root {
            --green-950: #082f27;
            --green-900: #0b3d32;
            --green-800: #145244;
            --green-700: #246657;
            --green-200: #dbe9e1;
            --green-100: #edf4ef;
            --cream: #f7f3ea;
            --cream-2: #fbf9f4;
            --text: #16231f;
            --muted: #718079;
            --line: #dfe6e1;
            --white: #ffffff;
            --danger-bg: #fff0ee;
            --danger-text: #a7382c;
            --success-bg: #ebf7ef;
            --success-text: #21653e;
            --shadow: 0 22px 70px rgba(23, 49, 41, .13);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 6% 45%, rgba(180, 199, 184, .25), transparent 23%),
                radial-gradient(circle at 94% 18%, rgba(206, 220, 207, .38), transparent 23%),
                linear-gradient(135deg, #fbfaf6 0%, #f6f2e8 52%, #faf9f5 100%);
        }

        a {
            color: inherit;
        }

        .top-strip {
            min-height: 42px;
            padding: 9px 28px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 18px;
            background: var(--green-950);
            color: #fff;
            font-size: 13px;
        }

        .top-strip__center {
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .top-strip__right {
            justify-self: end;
        }

        .site-header {
            min-height: 82px;
            padding: 0 34px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 28px;
            background: rgba(255, 255, 255, .93);
            border-bottom: 1px solid rgba(20, 82, 68, .08);
            backdrop-filter: blur(18px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand {
            display: inline-flex;
            width: fit-content;
            align-items: baseline;
            text-decoration: none;
            font-size: clamp(25px, 2.4vw, 34px);
            font-weight: 850;
            letter-spacing: -.05em;
            color: var(--green-950);
        }

        .brand span:last-child {
            color: #7d9f8f;
        }

        .site-nav {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .site-nav a {
            text-decoration: none;
            font-size: 15px;
            font-weight: 650;
            color: #273730;
            transition: color .2s ease;
        }

        .site-nav a:hover {
            color: var(--green-700);
        }

        .header-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }

        .icon-link {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: var(--green-950);
            text-decoration: none;
            position: relative;
            transition: background .2s ease, transform .2s ease;
        }

        .icon-link:hover {
            background: var(--green-100);
            transform: translateY(-1px);
        }

        .icon-link svg {
            width: 23px;
            height: 23px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cart-badge {
            position: absolute;
            right: 0;
            top: 1px;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: var(--green-950);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            border: 2px solid #fff;
        }

        .auth-page {
            min-height: calc(100vh - 124px);
            padding: 62px 28px 54px;
            position: relative;
            overflow: hidden;
        }

        .auth-page::before,
        .auth-page::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-page::before {
            width: 520px;
            height: 520px;
            left: -210px;
            top: 70px;
            background: rgba(216, 225, 210, .3);
        }

        .auth-page::after {
            width: 620px;
            height: 620px;
            right: -260px;
            bottom: -280px;
            border: 1px solid rgba(68, 105, 88, .08);
            background: rgba(255, 255, 255, .12);
        }

        .auth-shell {
            width: min(1100px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: .93fr 1.07fr;
            min-height: 670px;
            border-radius: 30px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(19, 77, 64, .08);
        }

        .auth-visual {
            min-height: 670px;
            position: relative;
            background: #d8d2c8 url('../assets/images/hero-fashion-nu-new.png') center 35% / cover no-repeat;
            isolation: isolate;
        }

        .auth-visual::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(9, 41, 34, .04) 8%, rgba(7, 37, 30, .12) 53%, rgba(5, 39, 31, .78) 100%);
            z-index: -1;
        }

        .visual-copy {
            position: absolute;
            left: 42px;
            right: 42px;
            top: 56px;
            color: #fff;
        }

        .visual-copy__script {
            display: block;
            max-width: 330px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(42px, 5vw, 68px);
            font-style: italic;
            font-weight: 500;
            line-height: .94;
            letter-spacing: -.045em;
            text-shadow: 0 2px 18px rgba(0, 0, 0, .18);
        }

        .visual-note {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 34px 42px 38px;
            color: #fff;
            background: linear-gradient(180deg, transparent, rgba(8, 54, 43, .92));
        }

        .visual-note__eyebrow {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .3em;
        }

        .visual-note p {
            margin: 0;
            max-width: 310px;
            font-size: 15px;
            line-height: 1.65;
            color: rgba(255,255,255,.92);
        }

        .auth-panel {
            padding: clamp(42px, 6vw, 76px);
            display: flex;
            align-items: center;
            background:
                radial-gradient(circle at 90% 10%, rgba(226, 237, 228, .58), transparent 31%),
                #fff;
        }

        .auth-panel__inner {
            width: min(100%, 500px);
            margin: auto;
        }

        .auth-brand {
            display: inline-flex;
            align-items: baseline;
            margin-bottom: 28px;
            font-size: 27px;
            font-weight: 850;
            letter-spacing: -.05em;
            color: var(--green-950);
        }

        .auth-brand span:last-child {
            color: #7d9f8f;
        }

        .auth-title {
            margin: 0;
            color: var(--green-950);
            font-size: clamp(42px, 5vw, 58px);
            line-height: 1.03;
            letter-spacing: -.05em;
        }

        .auth-subtitle {
            margin: 15px 0 34px;
            font-size: 17px;
            line-height: 1.65;
            color: var(--muted);
        }

        .notice {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.5;
            border: 1px solid transparent;
        }

        .notice--error {
            color: var(--danger-text);
            background: var(--danger-bg);
            border-color: rgba(167, 56, 44, .14);
        }

        .notice--success {
            color: var(--success-text);
            background: var(--success-bg);
            border-color: rgba(33, 101, 62, .14);
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 750;
            color: #25362f;
        }

        .input-shell {
            height: 58px;
            display: flex;
            align-items: center;
            border: 1px solid #d7dfda;
            border-radius: 14px;
            background: #fff;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .input-shell:focus-within {
            border-color: #7ca08f;
            box-shadow: 0 0 0 4px rgba(94, 139, 119, .12);
            transform: translateY(-1px);
        }

        .input-icon {
            width: 52px;
            display: grid;
            place-items: center;
            flex: 0 0 52px;
            color: #547165;
        }

        .input-icon svg,
        .password-toggle svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .input-shell input {
            width: 100%;
            min-width: 0;
            height: 100%;
            padding: 0 14px 0 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text);
            font: inherit;
            font-size: 15px;
        }

        .input-shell input::placeholder {
            color: #a0aaa5;
        }

        .password-toggle {
            width: 52px;
            height: 100%;
            flex: 0 0 52px;
            border: 0;
            background: transparent;
            color: #6b7b74;
            display: grid;
            place-items: center;
            cursor: pointer;
            border-radius: 12px;
        }

        .password-toggle:hover {
            color: var(--green-800);
            background: var(--green-100);
        }

        .form-meta {
            display: flex;
            justify-content: flex-end;
            margin: -3px 0 24px;
        }

        .forgot-link {
            color: var(--green-700);
            font-size: 14px;
            font-weight: 750;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .submit-btn {
            width: 100%;
            height: 58px;
            border: 0;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            background: linear-gradient(135deg, var(--green-800), var(--green-950));
            color: #fff;
            font: inherit;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 26px rgba(14, 65, 53, .18);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 32px rgba(14, 65, 53, .24);
            filter: brightness(1.04);
        }

        .submit-btn svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .register-row {
            margin-top: 28px;
            padding-top: 25px;
            text-align: center;
            border-top: 1px solid var(--line);
            color: #68766f;
            font-size: 14px;
        }

        .register-row a {
            margin-left: 4px;
            color: var(--green-700);
            font-weight: 800;
            text-decoration: none;
        }

        .register-row a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .trust-row {
            width: min(980px, 100%);
            margin: 32px auto 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .trust-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 18px;
            border: 1px solid rgba(20, 82, 68, .08);
            border-radius: 16px;
            background: rgba(255,255,255,.62);
            backdrop-filter: blur(10px);
            color: #42534c;
            font-size: 13px;
        }

        .trust-item strong {
            display: block;
            color: #21332b;
            margin-bottom: 2px;
            font-size: 13px;
        }

        .trust-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--green-100);
            color: var(--green-800);
            flex: 0 0 38px;
        }

        .trust-icon svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (max-width: 900px) {
            .top-strip {
                grid-template-columns: 1fr auto;
            }

            .top-strip__center {
                display: none;
            }

            .site-header {
                grid-template-columns: 1fr auto;
            }

            .site-nav {
                display: none;
            }

            .auth-page {
                padding-top: 34px;
            }

            .auth-shell {
                grid-template-columns: 1fr;
                width: min(680px, 100%);
            }

            .auth-visual {
                min-height: 330px;
                background-position: center 32%;
            }

            .visual-copy {
                top: 30px;
                left: 28px;
            }

            .visual-copy__script {
                font-size: 46px;
            }

            .visual-note {
                padding: 70px 28px 24px;
            }

            .auth-panel {
                padding: 46px 34px 50px;
            }

            .trust-row {
                grid-template-columns: 1fr;
                width: min(680px, 100%);
            }
        }

        @media (max-width: 560px) {
            .top-strip {
                display: none;
            }

            .site-header {
                min-height: 68px;
                padding: 0 18px;
            }

            .brand {
                font-size: 26px;
            }

            .auth-page {
                min-height: calc(100vh - 68px);
                padding: 20px 14px 30px;
            }

            .auth-shell {
                border-radius: 22px;
            }

            .auth-visual {
                min-height: 260px;
            }

            .visual-copy__script {
                font-size: 38px;
            }

            .visual-note p {
                font-size: 13px;
            }

            .auth-panel {
                padding: 34px 22px 38px;
            }

            .auth-brand {
                margin-bottom: 20px;
            }

            .auth-title {
                font-size: 40px;
            }

            .auth-subtitle {
                font-size: 15px;
                margin-bottom: 26px;
            }

            .trust-row {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="top-strip" aria-label="Thông tin ưu đãi">
        <div>🚚 Freeship cho đơn từ 499.000đ</div>
        <div class="top-strip__center">Ưu đãi mùa mới &nbsp; | &nbsp; Giảm đến 50%</div>
        <div class="top-strip__right">Hỗ trợ khách hàng &nbsp;|&nbsp; 1900 1234</div>
    </div>

    <header class="site-header">
        <a class="brand" href="../index.php" aria-label="FashionShop - Trang chủ">
            <span>Fashion</span><span>Shop</span>
        </a>

        <nav class="site-nav" aria-label="Điều hướng chính">
            <a href="../index.php">Trang chủ</a>
            <a href="../products/index.php">Sản phẩm</a>
            <a href="../products/index.php?category=1">Áo</a>
            <a href="../products/index.php?category=2">Quần</a>
            <a href="../products/index.php?category=3">Váy</a>
        </nav>

        <div class="header-actions">
            <a class="icon-link" href="login.php" aria-label="Đăng nhập">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5"></circle>
                    <path d="M5 20c.75-4 3.08-6 7-6s6.25 2 7 6"></path>
                </svg>
            </a>
            <a class="icon-link" href="../cart/index.php" aria-label="Giỏ hàng">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 8h14l-1 11H6L5 8Z"></path>
                    <path d="M9 8V6.5a3 3 0 0 1 6 0V8"></path>
                </svg>
                <span class="cart-badge"><?= (int)$cartCount ?></span>
            </a>
        </div>
    </header>

    <main class="auth-page">
        <section class="auth-shell" aria-labelledby="login-title">
            <aside class="auth-visual" aria-label="Bộ sưu tập FashionShop">
                <div class="visual-copy">
                    <span class="visual-copy__script">Be Your<br>Own Style</span>
                </div>
                <div class="visual-note">
                    <div class="visual-note__eyebrow">FASHION SHOP</div>
                    <p>Thời trang không chỉ để mặc mà còn là cách bạn thể hiện phong cách riêng.</p>
                </div>
            </aside>

            <div class="auth-panel">
                <div class="auth-panel__inner">
                    <div class="auth-brand" aria-hidden="true">
                        <span>Fashion</span><span>Shop</span>
                    </div>

                    <h1 class="auth-title" id="login-title">Đăng nhập</h1>
                    <p class="auth-subtitle">Chào mừng bạn trở lại! Tiếp tục hành trình thời trang của bạn.</p>

                    <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
                        <div class="notice notice--success" role="status">
                            Đăng ký thành công! Vui lòng đăng nhập.
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="notice notice--error" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="on">
                        <div class="field">
                            <label for="email">Email</label>
                            <div class="input-shell">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2"></rect><path d="m5 7 7 5.5L19 7"></path></svg>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    placeholder="Nhập email của bạn"
                                    autocomplete="email"
                                    required
                                >
                            </div>
                        </div>

                        <div class="field">
                            <label for="password">Mật khẩu</label>
                            <div class="input-shell">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Nhập mật khẩu của bạn"
                                    autocomplete="current-password"
                                    required
                                >
                                <button class="password-toggle" type="button" aria-label="Hiện mật khẩu" aria-pressed="false" data-password-toggle>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-meta">
                            <a class="forgot-link" href="forgot-password.php">Quên mật khẩu?</a>
                        </div>

                        <button class="submit-btn" type="submit">
                            <span>Đăng nhập</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m14 7 5 5-5 5"></path></svg>
                        </button>
                    </form>

                    <div class="register-row">
                        Chưa có tài khoản?
                        <a href="register.php">Đăng ký ngay</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="trust-row" aria-label="Cam kết dịch vụ">
            <div class="trust-item">
                <span class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 7h11v10H3z"></path><path d="M14 10h4l3 3v4h-7"></path><circle cx="7" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle></svg>
                </span>
                <div><strong>Freeship</strong>Đơn từ 499.000đ</div>
            </div>
            <div class="trust-item">
                <span class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.7 3.05 7.85 7.5 9.5 4.45-1.65 7.5-4.8 7.5-9.5V6L12 3Z"></path><path d="m8.5 12 2.3 2.3 4.7-4.8"></path></svg>
                </span>
                <div><strong>Thanh toán an toàn</strong>Bảo mật thông tin</div>
            </div>
            <div class="trust-item">
                <span class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 13v-2a7 7 0 0 1 14 0v2"></path><path d="M5 12H3v6h4v-6H5Z"></path><path d="M19 12h2v6h-4v-6h2Z"></path><path d="M17 19c-1.3 1-2.95 1.5-5 1.5"></path></svg>
                </span>
                <div><strong>Hỗ trợ khách hàng</strong>1900 1234</div>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const toggle = document.querySelector('[data-password-toggle]');
            const password = document.getElementById('password');

            if (!toggle || !password) return;

            toggle.addEventListener('click', () => {
                const isVisible = password.type === 'text';
                password.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            });
        })();
    </script>
</body>
</html>
