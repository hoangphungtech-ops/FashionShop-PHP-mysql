<?php

require_once __DIR__ . "/../includes/db.php";


/* =========================
   KIỂM TRA ID
========================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    die("Sản phẩm không hợp lệ.");
}


/* =========================
   LẤY SẢN PHẨM
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
      AND status = 1
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Không tìm thấy sản phẩm.");
}


/* =========================
   LẤY ẢNH PHỤ
========================= */

$extraImages = [];

try {

    $imageStmt = $pdo->prepare("
        SELECT id, product_id, image
        FROM product_images
        WHERE product_id = ?
        ORDER BY id ASC
    ");

    $imageStmt->execute([$id]);

    $extraImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    // Nếu bảng product_images có vấn đề
    // vẫn cho trang sản phẩm chạy bằng ảnh chính
    $extraImages = [];

}


/* =========================
   XỬ LÝ ĐƯỜNG DẪN ẢNH
========================= */

function detailImage($image)
{
    $image = trim((string)$image);

    /*
     * Không có ảnh
     */
    if ($image === '') {
        return '../assets/images/no-image.jpg';
    }


    /*
     * Nếu là URL đầy đủ
     */
    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }


    /*
     * Chuẩn hóa dấu \
     */
    $image = str_replace('\\', '/', $image);


    /*
     * Nếu database đã lưu đường dẫn
     * assets/images/xxx.jpg
     */
    if (strpos($image, 'assets/images/') === 0) {

        $file = __DIR__ . '/../' . $image;

        if (file_exists($file)) {
            return '../' . $image;
        }
    }


    /*
     * Nếu database lưu
     * uploads/products/xxx.jpg
     */
    if (strpos($image, 'uploads/products/') === 0) {

        $file = __DIR__ . '/../' . $image;

        if (file_exists($file)) {
            return '../' . $image;
        }
    }


    /*
     * Nếu database lưu ./uploads/products/xxx.jpg
     */
    if (strpos($image, './') === 0) {

        $cleanImage = substr($image, 2);

        $file = __DIR__ . '/../' . $cleanImage;

        if (file_exists($file)) {
            return '../' . $cleanImage;
        }
    }


    /*
     * Nếu chỉ lưu tên file
     * Ví dụ:
     * ao-thun.jpg
     */

    $uploadFile = __DIR__ . '/../uploads/products/' . $image;

    if (file_exists($uploadFile)) {
        return '../uploads/products/' . $image;
    }


    /*
     * Thử assets/images
     */

    $assetFile = __DIR__ . '/../assets/images/' . $image;

    if (file_exists($assetFile)) {
        return '../assets/images/' . $image;
    }


    /*
     * Thử đường dẫn trong thư mục products
     */

    $productFile = __DIR__ . '/' . $image;

    if (file_exists($productFile)) {
        return $image;
    }


    /*
     * Không tìm thấy ảnh
     */

    return '../assets/images/no-image.jpg';
}


/* =========================
   ẢNH CHÍNH
========================= */

$mainImage = detailImage(
    $product['image'] ?? ''
);


/* =========================
   DANH SÁCH ẢNH
========================= */

$allImages = [];


/*
 * Thêm ảnh chính
 */

if (!empty($product['image'])) {

    $allImages[] = [
        'image' => $product['image']
    ];
}


/*
 * Thêm ảnh phụ
 */

foreach ($extraImages as $img) {

    if (!empty($img['image'])) {

        $allImages[] = [
            'image' => $img['image']
        ];
    }
}


/*
 * Nếu không có ảnh nào
 */

if (empty($allImages)) {

    $allImages[] = [
        'image' => ''
    ];
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
        <?= htmlspecialchars($product['name']) ?>
        - Fashion Shop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =========================
           DETAIL PAGE
        ========================= */

        .detail-page {

            padding: 70px 0;

            background: #f7faf7;
        }


        .detail-layout {

            display: grid;

            grid-template-columns:
                1.05fr .95fr;

            gap: 70px;

            align-items: start;
        }


        /* =========================
           ẢNH
        ========================= */

        .detail-gallery {

            display: grid;

            grid-template-columns:
                85px 1fr;

            gap: 15px;
        }


        .detail-thumbnails {

            display: flex;

            flex-direction: column;

            gap: 12px;
        }


        .detail-thumb {

            width: 85px;

            height: 105px;

            border: 1px solid #dfe7e1;

            background: #fff;

            padding: 0;

            cursor: pointer;

            overflow: hidden;
        }


        .detail-thumb.active {

            border: 2px solid #173d32;
        }


        .detail-thumb img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        .detail-main-image {

            width: 100%;

            height: 600px;

            background: #eef3ef;

            overflow: hidden;
        }


        .detail-main-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* =========================
           THÔNG TIN
        ========================= */

        .detail-info {

            padding-top: 10px;
        }


        .detail-category {

            color: #71937d;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 3px;

            margin-bottom: 15px;
        }


        .detail-info h1 {

            color: #173d32;

            font-size: 42px;

            line-height: 1.1;

            margin-bottom: 18px;
        }


        .detail-price {

            color: #638b72;

            font-size: 28px;

            font-weight: 700;

            margin-bottom: 25px;
        }


        .detail-description {

            color: #66766e;

            font-size: 16px;

            line-height: 1.9;

            margin-bottom: 25px;
        }


        .detail-stock {

            padding: 15px 18px;

            background: #eef4ef;

            margin-bottom: 25px;

            color: #173d32;

            font-size: 14px;

            font-weight: 600;
        }


        .back-products {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 15px 24px;

            background: #173d32;

            color: #fff;

            font-weight: 700;

            transition: .2s;
        }


        .back-products:hover {

            background: #285746;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .detail-page {

                padding: 35px 0;
            }


            .detail-layout {

                grid-template-columns: 1fr;

                gap: 35px;
            }


            .detail-gallery {

                grid-template-columns: 1fr;

                gap: 12px;
            }


            .detail-main-image {

                height: 430px;
            }


            .detail-thumbnails {

                flex-direction: row;

                overflow-x: auto;

                padding-bottom: 5px;
            }


            .detail-thumb {

                min-width: 70px;

                width: 70px;

                height: 85px;
            }


            .detail-info h1 {

                font-size: 32px;
            }


            .detail-price {

                font-size: 24px;
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

            <span class="logo-fashion">
                Fashion
            </span>

            <span class="logo-shop">
                Shop
            </span>

        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>

            <a
                href="index.php"
                class="active"
            >
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


        <div class="header-actions">

            <a
                href="../auth/profile.php"
                class="header-action"
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

            </a>


            <a
                href="../cart/index.php"
                class="header-action cart"
            >

                <svg viewBox="0 0 24 24">

                    <path
                        d="M6 8h12l1 13H5L6 8Z"
                    ></path>

                    <path
                        d="M9 9V6a3 3 0 0 1 6 0v3"
                    ></path>

                </svg>

                <span class="cart-count">
                    0
                </span>

            </a>

        </div>

    </div>

</header>


<!-- =========================
     DETAIL
========================= -->

<main class="detail-page">

    <div class="container">

        <div class="detail-layout">


            <!-- =========================
                 GALLERY
            ========================= -->

            <div class="detail-gallery">


                <div class="detail-thumbnails">

                    <?php foreach ($allImages as $index => $img): ?>

                        <?php
                        $thumbImage = detailImage(
                            $img['image']
                        );
                        ?>

                        <button
                            type="button"
                            class="detail-thumb <?= $index === 0 ? 'active' : '' ?>"
                            onclick="changeImage(this.dataset.image, this)"
                            data-image="<?= htmlspecialchars(
                                $thumbImage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <img
                                src="<?= htmlspecialchars(
                                    $thumbImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="Ảnh sản phẩm"
                                onerror="this.src='../assets/images/no-image.jpg';"
                            >

                        </button>

                    <?php endforeach; ?>

                </div>


                <div class="detail-main-image">

                    <img
                        id="mainProductImage"
                        src="<?= htmlspecialchars(
                            $mainImage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $product['name']
                        ) ?>"
                        onerror="this.src='../assets/images/no-image.jpg';"
                    >

                </div>


            </div>


            <!-- =========================
                 INFO
            ========================= -->

            <div class="detail-info">

                <div class="detail-category">

                    FASHION

                </div>


                <h1>

                    <?= htmlspecialchars(
                        $product['name']
                    ) ?>

                </h1>


                <div class="detail-price">

                    <?= number_format(
                        (float)$product['price'],
                        0,
                        ',',
                        '.'
                    ) ?>đ

                </div>


                <p class="detail-description">

                    <?= nl2br(
                        htmlspecialchars(
                            $product['description'] ?? ''
                        )
                    ) ?>

                </p>


                <div class="detail-stock">

                    Còn lại:

                    <?= (int)(
                        $product['stock'] ?? 0
                    ) ?>

                    sản phẩm

                </div>


                <a
                    href="index.php"
                    class="back-products"
                >

                    ← Quay lại sản phẩm

                </a>

            </div>


        </div>

    </div>

</main>


<script>

function changeImage(src, button) {

    const mainImage =
        document.getElementById(
            "mainProductImage"
        );


    mainImage.src = src;


    document
        .querySelectorAll(".detail-thumb")
        .forEach(function(item) {

            item.classList.remove("active");

        });


    button.classList.add("active");

}

</script>


</body>

</html>