<?php

require_once __DIR__ . "/../includes/db.php";

/* =========================
   LẤY CATEGORY
========================= */

$category = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

/* =========================
   LẤY SẢN PHẨM
========================= */

$products = [];

try {

    if ($category > 0) {

        $sql = "SELECT *
                FROM products
                WHERE status = 1
                AND category_id = :category
                ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':category' => $category
        ]);

    } else {

        $sql = "SELECT *
                FROM products
                WHERE status = 1
                ORDER BY id DESC";

        $stmt = $pdo->query($sql);
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Lỗi lấy sản phẩm: " . $e->getMessage());

}

/* =========================
   LẤY ẢNH SẢN PHẨM
   MỖI SẢN PHẨM CHỈ 1 ẢNH
========================= */

function getProductImage($image)
{
    $image = trim($image ?? '');

    /* Nếu database không có ảnh */
    if ($image === '') {
        return "../uploads/products/ao-thun.jpg";
    }

    /* Nếu là URL hợp lệ (như link Pinterest, http://, https://) thì trả về dùng luôn */
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }

    /* Chuẩn hóa dấu gạch chéo */
    $image = str_replace("\\", "/", $image);

    /* Lấy tên file */
    $filename = basename($image);

    /* Danh sách các đường dẫn kiểm tra trên máy (tính từ file hiện tại) */
    $paths = [
        __DIR__ . "/../uploads/products/" . $filename,
        __DIR__ . "/../uploads/" . $filename,
        __DIR__ . "/../assets/images/" . $filename,
        __DIR__ . "/../../uploads/products/" . $filename,
        __DIR__ . "/../../uploads/" . $filename
    ];

    foreach ($paths as $fullPath) {
        if (file_exists($fullPath)) {
            $relativePath = str_replace("\\", "/", str_replace(__DIR__ . "/", "", $fullPath));
            return $relativePath;
        }
    }

    /* Nếu chuỗi trong DB thực chất là một đường dẫn tương đối từ gốc */
    if (file_exists(__DIR__ . "/../" . $image)) {
        return "../" . $image;
    }

    /* Nếu không tìm thấy ảnh nào khớp */
    return "../uploads/products/ao-thun.jpg";
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sản phẩm - Fashion Shop</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        /* =========================
            PRODUCTS PAGE
        ========================= */

        .products-page {
            padding: 70px 0 90px;
            background: #ffffff;
        }

        .products-page-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .products-page-header .small-title {
            margin-bottom: 12px;
        }

        .products-page-header h1 {
            font-size: 45px;
            line-height: 1.1;
            color: #263126;
            margin-bottom: 15px;
        }

        .products-page-header p {
            max-width: 600px;
            margin: 0 auto;
            color: #667066;
            font-size: 15px;
        }


        /* =========================
            CATEGORY
        ========================= */

        .category-filter {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 45px;
        }

        .category-filter a {
            padding: 11px 22px;
            border: 1px solid #dfe6df;
            color: #4d584f;
            font-size: 14px;
            font-weight: 600;
            background: #ffffff;
            transition: 0.3s ease;
        }

        .category-filter a:hover {
            background: #263126;
            color: #ffffff;
            border-color: #263126;
        }

        .category-filter a.active {
            background: #263126;
            color: #ffffff;
            border-color: #263126;
        }


        /* =========================
            PRODUCT GRID
        ========================= */

        .products-page .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }


        /* =========================
            PRODUCT CARD
        ========================= */

        .products-page .product-card {
            background: #ffffff;
            border: 1px solid #e7ece7;
            overflow: hidden;
            transition: 0.3s ease;
        }

        .products-page .product-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 12px 30px
                rgba(38, 49, 38, 0.09);
        }


        /* =========================
            PRODUCT IMAGE
        ========================= */

        .products-page .product-image {
            position: relative;

            width: 100%;
            height: 350px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            background: #f3f5f3;
        }

        .products-page .product-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition: 0.5s ease;
        }

        .products-page .product-card:hover
        .product-image img {
            transform: scale(1.04);
        }


        /* =========================
            NEW
        ========================= */

        .products-page .new {
            position: absolute;

            top: 15px;
            left: 15px;

            z-index: 2;

            padding: 6px 11px;

            background: #263126;
            color: #ffffff;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 1px;
        }


        /* =========================
            INFO
        ========================= */

        .products-page .product-info {
            padding: 20px;
        }

        .products-page .product-info small {
            font-size: 10px;
            font-weight: 700;

            letter-spacing: 2px;

            color: #8a958b;
        }

        .products-page .product-info h3 {
            margin-top: 7px;
            margin-bottom: 10px;

            font-size: 18px;

            color: #263126;
        }

        .products-page .product-info h3 a {
            transition: 0.3s ease;
        }

        .products-page .product-info h3 a:hover {
            color: #78917d;
        }

        .products-page .price {
            font-size: 18px;
            font-weight: 700;

            color: #263126;
        }


        /* =========================
            EMPTY
        ========================= */

        .products-empty {
            grid-column: 1 / -1;

            text-align: center;

            padding: 80px 20px;

            background: #f8faf8;

            border: 1px dashed #d6ddd7;
        }

        .products-empty h3 {
            margin-bottom: 10px;

            font-size: 23px;

            color: #263126;
        }

        .products-empty p {
            color: #788078;
        }


        /* =========================
            FOOTER
        ========================= */

        .products-footer {
            background: #263126;
            color: #ffffff;

            padding: 50px 0 25px;
        }

        .products-footer-content {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr;

            gap: 50px;

            padding-bottom: 35px;
        }

        .products-footer h3 {
            font-size: 25px;
            margin-bottom: 12px;
        }

        .products-footer h3 span {
            color: #9ab19f;
        }

        .products-footer p {
            max-width: 350px;

            color: #bdc6bd;

            font-size: 14px;
        }

        .products-footer h4 {
            margin-bottom: 15px;
        }

        .products-footer a {
            display: block;

            margin-bottom: 9px;

            color: #bdc6bd;

            font-size: 14px;
        }

        .products-footer a:hover {
            color: #ffffff;
        }

        .products-copyright {
            border-top: 1px solid #465046;

            padding-top: 20px;

            text-align: center;

            color: #9fa99f;

            font-size: 12px;
        }


        /* =========================
            RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .products-page .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-footer-content {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 700px) {

            .products-page {
                padding: 50px 0 60px;
            }

            .products-page-header h1 {
                font-size: 36px;
            }

            .products-page .product-grid {
                grid-template-columns: 1fr;
            }

            .products-page .product-image {
                height: 380px;
            }

            .products-footer-content {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="container header-content">

        <a
            href="../index.php"
            class="logo"
        >
            Fashion<span>Shop</span>
        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>

            <a href="index.php">
                Sản phẩm
            </a>

            <a href="index.php?category=1">
                Áo
            </a>

            <a href="index.php?category=2">
                Quần
            </a>

            <a href="index.php?category=3">
                Váy
            </a>

        </nav>


        <a
            href="../cart/index.php"
            class="cart"
        >

            Giỏ hàng

            <span>0</span>

        </a>

    </div>

</header>



<!-- =========================
     PRODUCTS PAGE
========================= -->

<section class="products-page">

    <div class="container">


        <!-- TITLE -->

        <div class="products-page-header">

            <div class="small-title">
                OUR COLLECTION
            </div>

            <h1>
                Tất cả sản phẩm
            </h1>

            <p>
                Khám phá những sản phẩm thời trang
                trẻ trung, hiện đại và phù hợp
                với phong cách của bạn.
            </p>

        </div>



        <!-- =========================
             CATEGORY FILTER
========================= -->

        <div class="category-filter">

            <a
                href="index.php"
                class="<?= $category === 0 ? 'active' : '' ?>"
            >
                Tất cả
            </a>


            <a
                href="index.php?category=1"
                class="<?= $category === 1 ? 'active' : '' ?>"
            >
                Áo
            </a>


            <a
                href="index.php?category=2"
                class="<?= $category === 2 ? 'active' : '' ?>"
            >
                Quần
            </a>


            <a
                href="index.php?category=3"
                class="<?= $category === 3 ? 'active' : '' ?>"
            >
                Váy
            </a>

        </div>



        <!-- =========================
             PRODUCT LIST
========================= -->

        <div class="product-grid">

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $product): ?>

                    <?php

                    $image = getProductImage(
                        $product['image'] ?? ''
                    );

                    ?>


                    <div class="product-card">


                        <a
                            href="detail.php?id=<?= (int) $product['id'] ?>"
                            class="product-image"
                        >

                            <span class="new">
                                NEW
                            </span>


                            <img
                                src="<?= htmlspecialchars($image) ?>"
                                alt="<?= htmlspecialchars(
                                    $product['name'] ?? 'Sản phẩm'
                                ) ?>"
                            >

                        </a>



                        <div class="product-info">


                            <small>
                                FASHION
                            </small>


                            <h3>

                                <a
                                    href="detail.php?id=<?= (int) $product['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $product['name']
                                        ?? 'Sản phẩm'
                                    ) ?>

                                </a>

                            </h3>


                            <div class="price">

                                <?= number_format(
                                    (float) (
                                        $product['price'] ?? 0
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) ?>đ

                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="products-empty">

                    <h3>
                        Chưa có sản phẩm
                    </h3>

                    <p>
                        Hiện tại chưa có sản phẩm
                        phù hợp để hiển thị.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>

</section>



<!-- =========================
     FOOTER
========================= -->

<footer class="products-footer">

    <div class="container">


        <div class="products-footer-content">


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

                <a href="index.php">
                    Tất cả sản phẩm
                </a>

                <a href="index.php?category=1">
                    Áo
                </a>

                <a href="index.php?category=2">
                    Quần
                </a>

                <a href="index.php?category=3">
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


        <div class="products-copyright">

            © 2026 Fashion Shop

        </div>


    </div>

</footer>


</body>

</html>