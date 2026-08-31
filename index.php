<?php

require_once __DIR__ . "/includes/db.php";

/* =========================
   GET PRODUCTS
========================= */

$products = [];

try {

    $sql = "SELECT products.*, categories.name AS category_name
            FROM products
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE products.status = 1
            ORDER BY products.id DESC
            LIMIT 6";

    $stmt = $pdo->query($sql);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $products = [];

}


/* =========================
   PRODUCT IMAGE
========================= */

function getProductImage($image)
{
    $image = trim($image ?? '');

    /* Không có ảnh */
    if ($image === '') {
        return 'assets/images/ao-thun.jpg';
    }

    /* Nếu database lưu URL */
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }

    /*
        Nếu database chỉ lưu tên file

        Ví dụ:
        ao-thun.jpg
    */

    $uploadPath = __DIR__ . "/uploads/products/" . $image;

    if (file_exists($uploadPath)) {
        return "uploads/products/" . $image;
    }


    /*
        Trường hợp ảnh nằm trong assets/images
    */

    $assetPath = __DIR__ . "/assets/images/" . $image;

    if (file_exists($assetPath)) {
        return "assets/images/" . $image;
    }


    /*
        Nếu database đã lưu sẵn đường dẫn
    */

    if (file_exists(__DIR__ . "/" . $image)) {
        return $image;
    }


    /* Ảnh mặc định */

    return "assets/images/ao-thun.jpg";
}

?>


<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fashion Shop</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

</head>


<body class="home-page">


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="container header-content">


        <a href="index.php"
           class="logo">

            <span class="logo-fashion">Fashion</span><span class="logo-shop">Shop</span>

        </a>


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


        <nav class="nav" id="primary-navigation" aria-label="Điều hướng chính">

            <a href="index.php" class="active" aria-current="page">
                Trang chủ
            </a>

            <a href="products/index.php">
                Sản phẩm
            </a>

            <a href="products/index.php?gender=nam">
                Nam
            </a>

            <a href="products/index.php?gender=nu">
                Nữ
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


        <div class="header-actions">

            <a href="auth/profile.php"
               class="header-action account-link">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21a8 8 0 0 1 16 0"></path>
                </svg>

                <span class="action-label">Tài khoản</span>

            </a>


            <a href="cart/index.php"
               class="header-action cart">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 8h12l1 13H5L6 8Z"></path>
                    <path d="M9 9V6a3 3 0 0 1 6 0v3"></path>
                </svg>

                <span class="action-label">Giỏ hàng</span>

                <span class="cart-count" aria-label="0 sản phẩm">0</span>

            </a>

        </div>


    </div>

</header>



<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="container hero-content">


        <div class="hero-text">

            <div class="small-title">
                FASHION SHOP
            </div>


            <h1>

                Thời trang

                <span>
                    dành cho bạn
                </span>

            </h1>


            <p>

                Khám phá những thiết kế thời trang
                trẻ trung, hiện đại và thanh lịch.
                Tìm cho mình phong cách phù hợp
                với cá tính riêng của bạn.

            </p>


            <a href="products/index.php"
               class="btn">

                Khám phá sản phẩm

                <span>→</span>

            </a>

        </div>



        <div class="hero-image">

            <img
                src="assets/images/hero-fashion-nu-new.png"
                alt="Người mẫu nữ trong thiết kế thanh lịch của Fashion Shop"
            >

        </div>


    </div>

</section>



<!-- =========================
     PRODUCTS
========================= -->

<section class="products">

    <div class="container">


        <div class="section-header">


            <div>

                <div class="small-title">
                    OUR COLLECTION
                </div>


                <h2>
                    Sản phẩm nổi bật
                </h2>

            </div>


            <a href="products/index.php"
               class="view-all">

                Xem tất cả →

            </a>


        </div>



        <div class="product-grid">


            <?php if (!empty($products)): ?>


                <?php foreach ($products as $product): ?>


                    <?php

                    $image = getProductImage(
                        $product['image'] ?? ''
                    );

                    $productName = (string)($product['name'] ?? 'Sản phẩm');
                    $productGender = preg_match('/(^|\s)nữ($|\s)/iu', $productName)
                        ? 'NỮ'
                        : (preg_match('/(^|\s)nam($|\s)/iu', $productName) ? 'NAM' : 'UNISEX');
                    $productCategory = (string)($product['category_name'] ?? 'THỜI TRANG');

                    ?>


                    <div class="product-card">


                        <a
                            href="products/detail.php?id=<?= (int)$product['id'] ?>"
                            class="product-image"
                        >

                            <span class="new">
                                NEW
                            </span>


                            <img
                                src="<?= htmlspecialchars($image) ?>"
                                alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>"
                            >

                        </a>



                        <div class="product-info">


                            <small>
                                <?= htmlspecialchars($productGender . ' · ' . $productCategory) ?>
                            </small>


                            <h3>

                                <a
                                    href="products/detail.php?id=<?= (int)$product['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $product['name'] ?? 'Sản phẩm'
                                    ) ?>

                                </a>

                            </h3>


                            <div class="price">

                                <?= number_format(
                                    (float)($product['price'] ?? 0),
                                    0,
                                    ',',
                                    '.'
                                ) ?>đ

                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- Nếu database chưa có sản phẩm -->

                <div class="empty-products">

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

</section>



<!-- =========================
     WHY
========================= -->

<section class="why">

    <div class="container why-content">


        <div>

            <div class="small-title">
                WHY CHOOSE US?
            </div>


            <h2>

                Đẹp theo cách

                <br>

                của bạn.

            </h2>

        </div>



        <div class="features">


            <div class="feature">

                <div class="number">
                    01
                </div>

                <h3>
                    Chất lượng tốt
                </h3>

                <p>
                    Sản phẩm được lựa chọn
                    kỹ càng và phù hợp
                    để sử dụng hằng ngày.
                </p>

            </div>



            <div class="feature">

                <div class="number">
                    02
                </div>

                <h3>
                    Thiết kế hiện đại
                </h3>

                <p>
                    Kiểu dáng trẻ trung,
                    dễ phối đồ và phù hợp
                    với nhiều phong cách.
                </p>

            </div>



            <div class="feature">

                <div class="number">
                    03
                </div>

                <h3>
                    Giao hàng nhanh
                </h3>

                <p>
                    Đóng gói cẩn thận
                    và giao hàng đến
                    tận nơi.
                </p>

            </div>


        </div>

    </div>

</section>



<!-- =========================
     FOOTER
========================= -->

<footer class="footer">

    <div class="container">


        <div class="footer-content">


            <div>

                <h3>
                    Fashion<span>Shop</span>
                </h3>


                <p>

                    Thời trang trẻ trung,
                    hiện đại và phù hợp
                    với phong cách riêng
                    của bạn.

                </p>

            </div>



            <div>

                <h4>
                    Danh mục
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



            <div>

                <h4>
                    Hỗ trợ
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


        </div>


        <div class="copyright">

            © 2026 Fashion Shop

        </div>


    </div>

</footer>


<script src="assets/js/main.js" defer></script>


</body>

</html>

