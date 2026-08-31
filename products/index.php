<?php
require_once __DIR__ . '/../includes/db.php';


/* =========================
   LỌC SẢN PHẨM THEO DANH MỤC
========================= */

$category = isset($_GET['category'])
    ? (int)$_GET['category']
    : 0;


/* =========================
   LẤY SẢN PHẨM
========================= */

try {

    if ($category > 0) {

        // Có chọn danh mục
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c 
                ON p.category_id = c.id
            WHERE p.status = 1
              AND p.category_id = ?
            ORDER BY p.id DESC
        ");

        $stmt->execute([$category]);

    } else {

        // Không chọn danh mục -> hiện tất cả
        $stmt = $pdo->query("
            SELECT 
                p.*,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c 
                ON p.category_id = c.id
            WHERE p.status = 1
            ORDER BY p.id DESC
        ");

    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $products = [];

}


/* =========================
   HÀM XỬ LÝ ẢNH
========================= */

function productImage($image)
{
    if (empty($image)) {
        return '../assets/images/no-image.jpg';
    }

    $image = trim($image);

    // Nếu database đã lưu đường dẫn đầy đủ
    if (
        str_starts_with($image, 'http://') ||
        str_starts_with($image, 'https://') ||
        str_starts_with($image, '../') ||
        str_starts_with($image, '/')
    ) {
        return $image;
    }

    // Ảnh nằm trong uploads/products
    return '../uploads/products/' . $image;
}


/* =========================
   TIÊU ĐỀ THEO DANH MỤC
========================= */

if ($category == 1) {

    $pageTitle = 'Áo';
    $pageDescription = 'Khám phá những mẫu áo trẻ trung, hiện đại và dễ phối đồ.';

} elseif ($category == 2) {

    $pageTitle = 'Quần';
    $pageDescription = 'Khám phá những mẫu quần thời trang, thoải mái và phong cách.';

} elseif ($category == 3) {

    $pageTitle = 'Váy';
    $pageDescription = 'Khám phá những mẫu váy thanh lịch, nữ tính và hiện đại.';

} else {

    $pageTitle = 'Tất cả sản phẩm';
    $pageDescription = 'Khám phá những thiết kế thời trang hiện đại và thanh lịch.';

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

    <title>
        <?= htmlspecialchars($pageTitle) ?> | Fashion Shop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f8f5;
            color: #123c32;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* =========================
           HEADER
        ========================= */

        .shop-header {
            height: 84px;

            background: #fff;

            border-bottom: 1px solid #e5e9e4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 5%;
        }


        .logo {
            font-size: 27px;

            font-weight: 800;

            letter-spacing: 1px;
        }


        .logo span {
            color: #7b9c8c;
        }


        .nav {
            display: flex;

            align-items: center;

            gap: 30px;
        }


        .nav a {
            font-size: 16px;

            font-weight: 600;

            transition: .2s;
        }


        .nav a:hover {
            color: #789786;
        }


        /* =========================
           PAGE TITLE
        ========================= */

        .page {
            max-width: 1280px;

            margin: auto;

            padding: 70px 30px 90px;
        }


        .page-heading {
            text-align: center;

            margin-bottom: 55px;
        }


        .small-title {
            color: #789786;

            font-size: 13px;

            font-weight: 700;

            letter-spacing: 4px;

            margin-bottom: 15px;
        }


        .page-heading h1 {
            font-size: 46px;

            line-height: 1.1;

            margin-bottom: 15px;
        }


        .page-heading p {
            color: #718078;

            font-size: 17px;
        }


        /* =========================
           PRODUCT GRID
        ========================= */

        .product-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 32px;
        }


        /* =========================
           PRODUCT CARD
        ========================= */

        .product-card {
            background: #fff;

            border: 1px solid #e1e7e2;

            overflow: hidden;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .product-card:hover {
            transform: translateY(-7px);

            box-shadow:
                0 18px 40px rgba(18, 60, 50, .12);
        }


        .product-link {
            display: block;
        }


        /* =========================
           IMAGE
        ========================= */

        .product-image {
            width: 100%;

            height: 420px;

            background: #f1f3ef;

            overflow: hidden;

            position: relative;
        }


        .product-image img {
            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

            transition:
                transform .4s ease;
        }


        .product-card:hover
        .product-image img {
            transform: scale(1.04);
        }


        /* =========================
           NEW LABEL
        ========================= */

        .new-label {
            position: absolute;

            top: 18px;

            left: 18px;

            background: #123c32;

            color: #fff;

            padding: 9px 14px;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 1px;

            z-index: 2;
        }


        /* =========================
           PRODUCT INFO
        ========================= */

        .product-info {
            padding: 25px 27px 28px;
        }


        .category {
            color: #789786;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 3px;

            text-transform: uppercase;

            margin-bottom: 12px;
        }


        .product-name {
            font-size: 22px;

            font-weight: 700;

            color: #123c32;

            margin-bottom: 16px;
        }


        .product-price {
            color: #6d917e;

            font-size: 21px;

            font-weight: 800;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;

            padding: 80px 20px;

            background: #fff;

            border: 1px solid #e1e7e2;
        }


        .empty h2 {
            margin-bottom: 10px;
        }


        .empty p {
            color: #777;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .product-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .product-image {
                height: 350px;
            }

        }


        @media (max-width: 600px) {

            .shop-header {
                padding: 0 20px;
            }


            .logo {
                font-size: 21px;
            }


            .nav {
                gap: 12px;
            }


            .nav a {
                font-size: 14px;
            }


            .page {
                padding: 45px 15px 60px;
            }


            .page-heading h1 {
                font-size: 34px;
            }


            .product-grid {
                grid-template-columns: 1fr;
            }


            .product-image {
                height: 430px;
            }

        }

    </style>

</head>


<body>


    <!-- =========================
         HEADER
    ========================= -->

    <header class="shop-header">

        <a
            href="../index.php"
            class="logo"
        >
            FASHION <span>SHOP</span>
        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>


            <a href="index.php">
                Sản phẩm
            </a>


            <!-- ÁO -->

            <a href="index.php?category=1">
                Áo
            </a>


            <!-- QUẦN -->

            <a href="index.php?category=2">
                Quần
            </a>


            <!-- VÁY -->

            <a href="index.php?category=3">
                Váy
            </a>


            <a href="../cart/index.php">
                Giỏ hàng
            </a>


            <a href="../auth/login.php">
                Tài khoản
            </a>

        </nav>

    </header>



    <!-- =========================
         CONTENT
    ========================= -->

    <main class="page">


        <div class="page-heading">

            <div class="small-title">
                OUR COLLECTION
            </div>


            <h1>
                <?= htmlspecialchars($pageTitle) ?>
            </h1>


            <p>
                <?= htmlspecialchars($pageDescription) ?>
            </p>

        </div>



        <!-- =========================
             PRODUCTS
        ========================= -->

        <?php if (empty($products)): ?>


            <div class="empty">

                <h2>
                    Chưa có sản phẩm
                </h2>


                <p>
                    Hiện tại cửa hàng chưa có sản phẩm nào
                    trong danh mục này.
                </p>

            </div>


        <?php else: ?>


            <div class="product-grid">


                <?php foreach ($products as $product): ?>


                    <article class="product-card">


                        <!--
                            BẤM VÀO CARD
                            -> DETAIL SẢN PHẨM
                        -->

                        <a
                            href="detail.php?id=<?= (int)$product['id'] ?>"
                            class="product-link"
                        >


                            <div class="product-image">


                                <span class="new-label">
                                    NEW
                                </span>


                                <img
                                    src="<?= htmlspecialchars(
                                        productImage(
                                            $product['image'] ?? ''
                                        )
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product['name']
                                    ) ?>"
                                    loading="lazy"
                                >


                            </div>



                            <div class="product-info">


                                <div class="category">

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ?? 'FASHION'
                                    ) ?>

                                </div>



                                <h2 class="product-name">

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                </h2>



                                <div class="product-price">

                                    <?= number_format(
                                        (float)$product['price'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>đ

                                </div>


                            </div>


                        </a>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </main>


</body>

</html>