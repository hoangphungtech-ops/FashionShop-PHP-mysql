<?php
require_once __DIR__ . "/includes/db.php";

/* =========================
   LẤY SẢN PHẨM
========================= */

$products = [];

try {
    $stmt = $pdo->query("
        SELECT *
        FROM products
        WHERE status = 1
        ORDER BY id DESC
        LIMIT 6
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $products = [];
}


/* =========================
   LẤY ẢNH SẢN PHẨM
========================= */

function getProductImage($image)
{
    $image = trim($image ?? '');

    if ($image === '') {
        return 'assets/images/ao-thun.jpg';
    }

    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }

    $uploadPath = __DIR__ . "/uploads/products/" . $image;

    if (file_exists($uploadPath)) {
        return "uploads/products/" . $image;
    }

    $assetPath = __DIR__ . "/assets/images/" . $image;

    if (file_exists($assetPath)) {
        return "assets/images/" . $image;
    }

    if (file_exists(__DIR__ . "/" . $image)) {
        return $image;
    }

    return "assets/images/ao-thun.jpg";
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fashion Shop</title>

    <!-- Thêm version để trình duyệt không giữ CSS cũ -->
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260831"
    >

</head>

<body class="home-page">


<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="top-bar">

    <div class="container top-bar-inner">

        <span>
            MIỄN PHÍ VẬN CHUYỂN CHO ĐƠN HÀNG TỪ 500.000Đ
        </span>

        <span>
            FASHION SHOP
        </span>

    </div>

</div>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="container header-content">


        <!-- LOGO -->

        <a href="index.php" class="logo">

            <span>FASHION</span>

            <strong>SHOP</strong>

        </a>


        <!-- NAV -->

        <nav
            class="nav"
            id="primary-navigation"
        >

            <a
                href="index.php"
                class="active"
            >
                Trang chủ
            </a>

            <a href="products/index.php">
                Sản phẩm
            </a>

            <a href="products/index.php?category=1">
                Áo
            </a>

            <a href="products/index.php?category=2">
                Quần
            </a>

            <a href="products/index.php?category=3">
                Váy
            </a>

        </nav>


        <!-- ACTION -->

        <div class="header-actions">


            <a
                href="auth/profile.php"
                class="header-action"
                title="Tài khoản"
            >

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                    ></circle>

                    <path
                        d="M4 21a8 8 0 0 1 16 0"
                    ></path>

                </svg>

                <span>
                    Tài khoản
                </span>

            </a>


            <a
                href="cart/index.php"
                class="header-action cart"
                title="Giỏ hàng"
            >

                <svg viewBox="0 0 24 24">

                    <path
                        d="M6 8h12l1 13H5L6 8Z"
                    ></path>

                    <path
                        d="M9 9V6a3 3 0 0 1 6 0v3"
                    ></path>

                </svg>

                <span>
                    Giỏ hàng
                </span>

                <b class="cart-count">
                    0
                </b>

            </a>


            <!-- MOBILE -->

            <button
                class="menu-toggle"
                type="button"
                aria-label="Mở menu"
                aria-controls="primary-navigation"
                aria-expanded="false"
            >

                <span></span>
                <span></span>
                <span></span>

            </button>

        </div>

    </div>

</header>



<!-- =====================================================
     HERO BANNER
===================================================== -->

<section class="hero">

    <div class="hero-background"></div>

    <div class="container hero-inner">


        <div class="hero-copy">

            <div class="hero-label">
                NEW COLLECTION 2026
            </div>


            <h1>

                Mặc đẹp.

                <br>

                <span>Sống chất.</span>

            </h1>


            <p>
                Khám phá bộ sưu tập thời trang
                trẻ trung, hiện đại và thanh lịch
                dành riêng cho phong cách của bạn.
            </p>


            <a
                href="products/index.php"
                class="hero-button"
            >

                KHÁM PHÁ NGAY

                <span>→</span>

            </a>

        </div>


        <div class="hero-picture">

            <img
                src="assets/images/vay-nu-thanh-lich.jpg"
                alt="Bộ sưu tập thời trang"
            >

        </div>

    </div>

</section>



<!-- =====================================================
     COLLECTION / PRODUCTS
===================================================== -->

<section class="collection">

    <div class="container">


        <!-- TITLE -->

        <div class="collection-title">

            <div>

                <span class="eyebrow">
                    OUR COLLECTION
                </span>

                <h2>
                    Sản phẩm nổi bật
                </h2>

            </div>


            <a
                href="products/index.php"
                class="all-products"
            >
                XEM TẤT CẢ
                <span>→</span>
            </a>

        </div>


        <!-- PRODUCT BOX -->

        <div class="product-showcase">


            <div class="showcase-heading">

                <span>
                    FEATURED PRODUCTS
                </span>

                <span>
                    2026
                </span>

            </div>


            <div class="product-grid">


                <?php if (!empty($products)): ?>


                    <?php foreach ($products as $index => $product): ?>

                        <?php
                        $image = getProductImage(
                            $product['image'] ?? ''
                        );
                        ?>


                        <article class="product-card">


                            <a
                                href="products/detail.php?id=<?= (int)$product['id'] ?>"
                                class="product-image"
                            >

                                <span class="product-number">
                                    0<?= $index + 1 ?>
                                </span>


                                <span class="new-badge">
                                    NEW
                                </span>


                                <img
                                    src="<?= htmlspecialchars($image) ?>"
                                    alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>"
                                >


                                <span class="view-product">
                                    XEM SẢN PHẨM →
                                </span>

                            </a>


                            <div class="product-info">


                                <div class="product-category">
                                    FASHION COLLECTION
                                </div>


                                <h3>

                                    <a
                                        href="products/detail.php?id=<?= (int)$product['id'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $product['name'] ?? 'Sản phẩm'
                                        ) ?>

                                    </a>

                                </h3>


                                <div class="product-bottom">

                                    <strong class="price">

                                        <?= number_format(
                                            (float)($product['price'] ?? 0),
                                            0,
                                            ',',
                                            '.'
                                        ) ?>đ

                                    </strong>


                                    <a
                                        href="products/detail.php?id=<?= (int)$product['id'] ?>"
                                        class="product-arrow"
                                    >
                                        →
                                    </a>

                                </div>

                            </div>

                        </article>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty-products">

                        <div class="empty-icon">
                            ♡
                        </div>

                        <h3>
                            Chưa có sản phẩm
                        </h3>

                        <p>
                            Hiện tại chưa có sản phẩm để hiển thị.
                        </p>

                    </div>


                <?php endif; ?>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     CATEGORY BANNER
===================================================== -->

<section class="category-section">

    <div class="container category-grid">


        <a
            href="products/index.php?category=1"
            class="category-card category-shirt"
        >

            <div>

                <span>
                    COLLECTION 01
                </span>

                <h3>
                    Áo
                </h3>

                <small>
                    KHÁM PHÁ →
                </small>

            </div>

        </a>


        <a
            href="products/index.php?category=2"
            class="category-card category-pants"
        >

            <div>

                <span>
                    COLLECTION 02
                </span>

                <h3>
                    Quần
                </h3>

                <small>
                    KHÁM PHÁ →
                </small>

            </div>

        </a>


        <a
            href="products/index.php?category=3"
            class="category-card category-dress"
        >

            <div>

                <span>
                    COLLECTION 03
                </span>

                <h3>
                    Váy
                </h3>

                <small>
                    KHÁM PHÁ →
                </small>

            </div>

        </a>

    </div>

</section>



<!-- =====================================================
     WHY CHOOSE US
===================================================== -->

<section class="why">

    <div class="container">


        <div class="why-heading">

            <span class="eyebrow">
                WHY FASHION SHOP
            </span>

            <h2>
                Đẹp theo cách
                <span>của bạn.</span>
            </h2>

        </div>


        <div class="features">


            <div class="feature">

                <span class="feature-number">
                    01
                </span>

                <div>

                    <h3>
                        Chất lượng tốt
                    </h3>

                    <p>
                        Sản phẩm được lựa chọn
                        kỹ càng, phù hợp để sử dụng
                        hằng ngày.
                    </p>

                </div>

            </div>


            <div class="feature">

                <span class="feature-number">
                    02
                </span>

                <div>

                    <h3>
                        Thiết kế hiện đại
                    </h3>

                    <p>
                        Kiểu dáng trẻ trung,
                        dễ phối đồ và phù hợp
                        với nhiều phong cách.
                    </p>

                </div>

            </div>


            <div class="feature">

                <span class="feature-number">
                    03
                </span>

                <div>

                    <h3>
                        Giao hàng nhanh
                    </h3>

                    <p>
                        Đóng gói cẩn thận và giao
                        hàng đến tận nơi.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

    <div class="container">


        <div class="footer-main">


            <div class="footer-brand">

                <a href="index.php" class="footer-logo">
                    FASHION<span>SHOP</span>
                </a>

                <p>
                    Thời trang trẻ trung,
                    hiện đại và phù hợp
                    với phong cách riêng
                    của bạn.
                </p>

            </div>


            <div class="footer-column">

                <h4>
                    DANH MỤC
                </h4>

                <a href="products/index.php">
                    Tất cả sản phẩm
                </a>

                <a href="products/index.php?category=1">
                    Áo
                </a>

                <a href="products/index.php?category=2">
                    Quần
                </a>

                <a href="products/index.php?category=3">
                    Váy
                </a>

            </div>


            <div class="footer-column">

                <h4>
                    HỖ TRỢ
                </h4>

                <a href="#">
                    Chính sách đổi trả
                </a>

                <a href="#">
                    Vận chuyển
                </a>

                <a href="#">
                    Liên hệ
                </a>

            </div>


            <div class="footer-column">

                <h4>
                    FASHION SHOP
                </h4>

                <p>
                    Cảm ơn bạn đã lựa chọn
                    Fashion Shop.
                </p>

            </div>


        </div>


        <div class="footer-bottom">

            <span>
                © 2026 Fashion Shop
            </span>

            <span>
                MADE WITH ♡
            </span>

        </div>

    </div>

</footer>



<script src="assets/js/main.js?v=20260831"></script>

</body>
</html>