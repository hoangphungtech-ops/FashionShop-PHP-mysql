<?php
require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   KIỂM TRA ID SẢN PHẨM
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Sản phẩm không hợp lệ.");
}

$id = (int) $_GET['id'];


/* =========================================================
   LẤY THÔNG TIN SẢN PHẨM
========================================================= */

try {

    $sql = "
        SELECT 
            products.*,
            categories.name AS category_name
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.id
        WHERE products.id = :id
        AND products.status = 1
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Lỗi database: " . $e->getMessage());

}


if (!$product) {
    die("Không tìm thấy sản phẩm.");
}


/* =========================================================
   MÀU SẮC CỐ ĐỊNH THEO TỪNG SẢN PHẨM
   Cần có cột `color` trong bảng products.
========================================================= */

$productColor = trim((string)($product['color'] ?? ''));

if ($productColor === '') {
    $productColor = 'Đen';
}

function getProductColorHex($colorName)
{
    $color = mb_strtolower(
        trim((string)$colorName),
        'UTF-8'
    );

    $colorMap = [
        'đen' => '#111111',
        'black' => '#111111',
        'xám' => '#666666',
        'xám than' => '#333333',
        'xám sáng' => '#9ca3af',
        'trắng' => '#ffffff',
        'white' => '#ffffff',
        'be' => '#e3d2bc',
        'be kem' => '#e3d2bc',
        'kem' => '#f4ead5',
        'nâu' => '#795548',
        'xanh' => '#2563eb',
        'xanh dương' => '#2563eb',
        'xanh navy' => '#1e3a5f',
        'xanh lá' => '#2f6b45',
        'đỏ' => '#dc2626',
        'hồng' => '#ec4899',
        'vàng' => '#eab308',
        'cam' => '#f97316',
        'tím' => '#7c3aed'
    ];

    return $colorMap[$color] ?? '#555555';
}

$productColorHex = getProductColorHex($productColor);


/* =========================================================
   LẤY ẢNH PHỤ
========================================================= */

$subImagesData = [];

try {

    $imgSql = "
        SELECT image_path
        FROM product_images
        WHERE product_id = :id
    ";

    $imgStmt = $pdo->prepare($imgSql);

    $imgStmt->execute([
        ':id' => $id
    ]);

    $subImagesData = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {

    try {

        $imgSql = "
            SELECT image
            FROM product_images
            WHERE product_id = :id
        ";

        $imgStmt = $pdo->prepare($imgSql);

        $imgStmt->execute([
            ':id' => $id
        ]);

        $subImagesData = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (Exception $e2) {

        $subImagesData = [];

    }
}


/* =========================================================
   XỬ LÝ ĐƯỜNG DẪN ẢNH
========================================================= */

function getProductImage($image)
{
    $image = trim($image ?? '');

    if ($image === '') {
        return "../assets/images/ao-thun.jpg";
    }

    /* Nếu là URL */
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }

    /* Chuẩn hóa dấu */
    $image = str_replace("\\", "/", $image);

    /* Nếu bắt đầu bằng / */
    if (strpos($image, "/") === 0) {
        return $image;
    }

    /* Nếu đã có đường dẫn */
    if (
        strpos($image, "uploads/") === 0 ||
        strpos($image, "assets/") === 0 ||
        strpos($image, "images/") === 0
    ) {
        return "../" . ltrim($image, "./");
    }

    $filename = basename($image);

    $folders = [
        "uploads/products/",
        "uploads/",
        "assets/images/products/",
        "assets/images/",
        "assets/img/products/",
        "assets/img/",
        "images/products/",
        "images/"
    ];

    foreach ($folders as $folder) {

        $checkPath =
            __DIR__ . "/../" . $folder . $filename;

        if (file_exists($checkPath)) {

            return "../" . $folder . $filename;

        }
    }

    return "../assets/images/ao-thun.jpg";
}


/* =========================================================
   TẠO KEY ĐỂ LỌC ẢNH TRÙNG
========================================================= */

function getImageDuplicateKey($image)
{
    $image = trim((string)$image);

    if ($image === '') {
        return null;
    }

    $normalized = str_replace("\\", "/", $image);

    $path = parse_url(
        $normalized,
        PHP_URL_PATH
    );

    $filename = strtolower(
        basename(
            $path ?: $normalized
        )
    );

    $folders = [
        "uploads/products/",
        "uploads/",
        "assets/images/products/",
        "assets/images/",
        "assets/img/products/",
        "assets/img/",
        "images/products/",
        "images/"
    ];

    foreach ($folders as $folder) {

        $fullPath =
            __DIR__ . "/../" .
            $folder .
            $filename;

        if (is_file($fullPath)) {

            $hash = @md5_file($fullPath);

            if ($hash !== false) {

                return "hash:" . $hash;

            }
        }
    }

    return "name:" . $filename;
}


/* =========================================================
   THÊM ẢNH KHÔNG BỊ TRÙNG
========================================================= */

function addProductImageUnique(
    &$productImages,
    &$seenImageKeys,
    $image
) {

    $image = trim((string)$image);

    if ($image === '') {
        return;
    }

    $key = getImageDuplicateKey($image);

    if (
        $key === null ||
        isset($seenImageKeys[$key])
    ) {
        return;
    }

    $formattedImg =
        getProductImage($image);

    /*
       Không thêm ảnh mặc định nếu
       ảnh thật không tồn tại
    */

    if (
        $formattedImg === "../assets/images/ao-thun.jpg" &&
        strtolower(basename($image)) !== "ao-thun.jpg"
    ) {
        return;
    }

    $seenImageKeys[$key] = true;

    $productImages[] = $formattedImg;
}


/* =========================================================
   GOM TẤT CẢ ẢNH
========================================================= */

$productImages = [];

$seenImageKeys = [];


/* Ảnh chính */

if (!empty($product['image'])) {

    addProductImageUnique(
        $productImages,
        $seenImageKeys,
        $product['image']
    );

}


/* Ảnh phụ */

if (
    !empty($subImagesData) &&
    is_array($subImagesData)
) {

    foreach ($subImagesData as $subImg) {

        if (empty($subImg)) {
            continue;
        }

        addProductImageUnique(
            $productImages,
            $seenImageKeys,
            $subImg
        );

    }
}


/* Nếu không có ảnh */

if (empty($productImages)) {

    $productImages[] =
        "../assets/images/ao-thun.jpg";

}


$productImage =
    $productImages[0];

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
        <?= htmlspecialchars(
            $product['name'] ?? 'Chi tiết sản phẩm'
        ) ?>
        - Fashion Shop
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =====================================================
           DETAIL PAGE
        ===================================================== */

        .detail-page {

            padding: 70px 0 90px;

            background: #f8fbf7;

            min-height: 600px;

        }


        .detail-container {

            width: 90%;

            max-width: 1100px;

            margin: 0 auto;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 55px;

            padding: 35px;

            background: #fff;

            border: 1px solid #e4ebe4;

        }


        /* =====================================================
           IMAGE GALLERY
        ===================================================== */

        .detail-gallery {

            display: flex;

            gap: 15px;

            width: 100%;

        }


        /* =====================================================
           THUMBNAILS
        ===================================================== */

        .product-thumbs {

            display: flex;

            flex-direction: column;

            gap: 10px;

            width: 70px;

            height: 520px;

            overflow-y: auto;

            flex-shrink: 0;

        }


        .thumb-item {

            width: 70px;

            height: 110px;

            padding: 0;

            flex-shrink: 0;

            overflow: hidden;

            cursor: pointer;

            border: 1px solid #ddd;

            border-radius: 5px;

            background: #fff;

            transition: 0.2s ease;

        }


        .thumb-item img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

        }


        .thumb-item:hover {

            border-color: #263126;

        }


        .thumb-item.active {

            border: 2px solid #263126;

        }


        /* =====================================================
           ẢNH CHÍNH
        ===================================================== */

        .detail-main-image {

            position: relative;

            flex: 1;

            height: 520px;

            overflow: hidden;

            background: #f1f4f1;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 6px;

        }


        .detail-main-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            cursor: zoom-in;

            transition:
                opacity 0.2s ease,
                transform 0.3s ease;

        }


        .detail-main-image img:hover {

            transform: scale(1.03);

        }


        /* Chữ hướng dẫn */

        .image-zoom-hint {

            position: absolute;

            bottom: 12px;

            right: 12px;

            padding: 7px 12px;

            background: rgba(0,0,0,0.6);

            color: #fff;

            border-radius: 4px;

            font-size: 12px;

            opacity: 0;

            pointer-events: none;

            transition: 0.3s;

        }


        .detail-main-image:hover
        .image-zoom-hint {

            opacity: 1;

        }


        /* =====================================================
           IMAGE MODAL
        ===================================================== */

        .image-modal {

            display: none;

            position: fixed;

            z-index: 99999;

            inset: 0;

            background:
                rgba(0, 0, 0, 0.88);

            align-items: center;

            justify-content: center;

            padding: 25px;

        }


        .image-modal.show {

            display: flex;

        }


        .image-modal-content {

            position: relative;

            width: 100%;

            max-width: 1100px;

            height: 95vh;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

        }


        /* =====================================================
           ẢNH LỚN TRONG MODAL
        ===================================================== */

        #modalImage {

            max-width: 850px;

            max-height: 75vh;

            width: auto;

            height: auto;

            object-fit: contain;

            background: #fff;

            border-radius: 5px;

            box-shadow:
                0 10px 40px
                rgba(0,0,0,0.4);

        }


        /* =====================================================
           NÚT ĐÓNG
        ===================================================== */

        .image-modal-close {

            position: absolute;

            top: 15px;

            right: 15px;

            width: 45px;

            height: 45px;

            border: none;

            border-radius: 50%;

            background: #fff;

            color: #263126;

            font-size: 30px;

            line-height: 45px;

            cursor: pointer;

            z-index: 20;

        }


        .image-modal-close:hover {

            background: #263126;

            color: #fff;

        }


        /* =====================================================
           NÚT TRÁI / PHẢI
        ===================================================== */

        .modal-prev,
        .modal-next {

            position: absolute;

            top: 45%;

            transform: translateY(-50%);

            width: 50px;

            height: 70px;

            border: none;

            background:
                rgba(255,255,255,0.9);

            color: #263126;

            font-size: 40px;

            cursor: pointer;

            border-radius: 5px;

            z-index: 10;

        }


        .modal-prev {

            left: 20px;

        }


        .modal-next {

            right: 20px;

        }


        .modal-prev:hover,
        .modal-next:hover {

            background: #263126;

            color: #fff;

        }


        /* =====================================================
           THUMBNAIL TRONG MODAL
        ===================================================== */

        .modal-thumbnails {

            display: flex;

            gap: 10px;

            margin-top: 18px;

            max-width: 90%;

            overflow-x: auto;

            padding: 5px;

        }


        .modal-thumb {

            width: 70px;

            height: 70px;

            flex-shrink: 0;

            padding: 0;

            border: 2px solid transparent;

            background: #fff;

            border-radius: 4px;

            overflow: hidden;

            cursor: pointer;

        }


        .modal-thumb img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

        }


        .modal-thumb:hover {

            border-color: #78917d;

        }


        .modal-thumb.active {

            border-color: #fff;

        }


        /* =====================================================
           PRODUCT INFO
        ===================================================== */

        .detail-info {

            padding: 20px 5px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .detail-category {

            margin-bottom: 14px;

            color: #78917d;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 3px;

            text-transform: uppercase;

        }


        .detail-info h1 {

            margin-bottom: 12px;

            color: #263126;

            font-size: 36px;

            line-height: 1.15;

        }


        /* =====================================================
           RATING
        ===================================================== */

        .rating-box {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 18px;

        }


        .rating-stars {

            color: #f59e0b;

            font-size: 18px;

            letter-spacing: 2px;

        }


        .rating-info {

            display: flex;

            align-items: center;

            gap: 5px;

            color: #687168;

            font-size: 14px;

        }


        .rating-info strong {

            color: #263126;

            font-size: 16px;

        }


        .rating-divider {

            margin: 0 5px;

            color: #c4c4c4;

        }


        /* =====================================================
           PRICE
        ===================================================== */

        .price-box {

            display: flex;

            align-items: baseline;

            gap: 15px;

            margin-bottom: 20px;

        }


        .detail-price {

            margin-bottom: 0 !important;

            color: #78917d;

            font-size: 26px;

            font-weight: 700;

        }


        .old-price {

            text-decoration: line-through;

            color: #888;

            font-size: 18px;

        }


        .discount-badge {

            background: #263126;

            color: #fff;

            padding: 2px 6px;

            font-size: 12px;

            border-radius: 4px;

        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .detail-description {

            margin-bottom: 20px;

            color: #687168;

            font-size: 15px;

            line-height: 1.8;

        }


        /* =====================================================
           CHI TIẾT SẢN PHẨM CUỐI TRANG
        ===================================================== */

        .product-detail-bottom {

            padding: 0 0 70px;

            background: #f8fbf7;

        }


        .product-detail-content {

            width: 90%;

            max-width: 1100px;

            margin: 0 auto;

            padding: 35px;

            background: #fff;

            border: 1px solid #e4ebe4;

        }


        .product-detail-title {

            margin: 0 0 25px;

            padding-bottom: 14px;

            border-bottom: 2px solid #263126;

            color: #263126;

            font-size: 28px;

        }


        .product-detail-description {

            margin-bottom: 28px;

            color: #687168;

            font-size: 15px;

            line-height: 1.9;

        }


        .product-detail-subtitle {

            margin: 0 0 15px;

            color: #263126;

            font-size: 18px;

        }


        .product-detail-specs {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 12px 30px;

        }


        .spec-item {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom: 1px solid #edf0ed;

            color: #687168;

            font-size: 14px;

        }


        .spec-item strong {

            color: #263126;

            white-space: nowrap;

        }


        .spec-item span {

            text-align: right;

        }


        @media (max-width: 600px) {

            .product-detail-content {

                width: 92%;

                padding: 20px;

            }


            .product-detail-title {

                font-size: 23px;

            }


            .product-detail-specs {

                grid-template-columns: 1fr;

                gap: 0;

            }

        }


        /* =====================================================
           STOCK
        ===================================================== */

        .detail-stock {

            margin-bottom: 20px;

            padding: 12px 16px;

            background: #f3f7f3;

            color: #667066;

            font-size: 14px;

        }


        .detail-stock strong {

            color: #263126;

        }


        /* =====================================================
           COLOR
        ===================================================== */

        .option-group {

            margin-bottom: 20px;

        }


        .option-group label {

            display: block;

            font-weight: 700;

            color: #263126;

            margin-bottom: 8px;

            font-size: 14px;

        }


        .color-options {

            display: flex;

            gap: 10px;

        }


        .color-dot {

            width: 24px;

            height: 24px;

            border-radius: 50%;

            border: 2px solid #ddd;

            cursor: pointer;

            display: inline-block;

        }


        .color-dot.active {

            border-color: #263126;

            transform: scale(1.1);

        }


        /* =====================================================
           SIZE
        ===================================================== */

        .size-options {

            display: flex;

            gap: 10px;

        }


        .size-btn {

            padding: 8px 16px;

            border: 1px solid #ccc;

            background: #fff;

            cursor: pointer;

            border-radius: 4px;

            font-size: 14px;

            transition: 0.2s;

        }


        .size-btn:hover,
        .size-btn.active {

            border-color: #263126;

            background: #263126;

            color: #fff;

            font-weight: bold;

        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .detail-buttons {

            display: flex;

            align-items: center;

            gap: 12px;

            flex-wrap: wrap;

            margin-bottom: 20px;

        }


        .back-btn,
        .cart-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 48px;

            padding: 0 22px;

            font-size: 14px;

            font-weight: 700;

            transition: 0.3s ease;

        }


        .back-btn {

            border: 1px solid #78917d;

            background: #fff;

            color: #78917d;

        }


        .back-btn:hover {

            background: #edf4ee;

        }


        .cart-btn {

            border: 1px solid #263126;

            background: #263126;

            color: #fff;

            flex: 1;

            cursor: pointer;

        }


        .cart-btn:hover {

            border-color: #78917d;

            background: #78917d;

        }


        /* =====================================================
           POLICIES
        ===================================================== */

        .policies {

            border-top: 1px solid #eee;

            padding-top: 15px;

            display: flex;

            justify-content: space-between;

            font-size: 12px;

            color: #666;

            text-align: center;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .detail-footer {

            padding: 55px 0 25px;

            background: #263126;

            color: #fff;

        }


        .detail-footer-content {

            display: grid;

            grid-template-columns: 2fr 1fr 1fr;

            gap: 50px;

            padding-bottom: 35px;

        }


        .detail-footer h3 {

            margin-bottom: 12px;

            font-size: 25px;

        }


        .detail-footer h3 span {

            color: #9ab19f;

        }


        .detail-footer h4 {

            margin-bottom: 15px;

        }


        .detail-footer p {

            max-width: 350px;

            color: #bdc6bd;

            font-size: 14px;

            line-height: 1.7;

        }


        .detail-footer a {

            display: block;

            margin-bottom: 9px;

            color: #bdc6bd;

            font-size: 14px;

        }


        .detail-footer a:hover {

            color: #fff;

        }


        .detail-copyright {

            padding-top: 20px;

            border-top: 1px solid #465046;

            text-align: center;

            color: #9fa99f;

            font-size: 12px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 850px) {

            .detail-container {

                grid-template-columns: 1fr;

                gap: 35px;

            }


            .detail-gallery {

                flex-direction: column;

            }


            .product-thumbs {

                flex-direction: row;

                width: 100%;

                height: auto;

                overflow-x: auto;

                overflow-y: hidden;

            }


            .thumb-item {

                width: 70px;

                height: 85px;

            }


            .detail-main-image {

                height: 450px;

            }


            .detail-footer-content {

                grid-template-columns: 1fr 1fr;

            }

        }


        @media (max-width: 600px) {

            .detail-page {

                padding: 40px 0 60px;

            }


            .detail-container {

                width: 92%;

                padding: 20px;

            }


            .detail-main-image {

                height: 380px;

            }


            .detail-info h1 {

                font-size: 28px;

            }


            .detail-price {

                font-size: 23px;

            }


            .detail-buttons {

                flex-direction: column;

                width: 100%;

            }


            .back-btn,
            .cart-btn {

                width: 100%;

            }


            .detail-footer-content {

                grid-template-columns: 1fr;

            }


            /* Modal mobile */

            .image-modal {

                padding: 10px;

            }


            #modalImage {

                max-width: 95vw;

                max-height: 70vh;

            }


            .modal-prev,
            .modal-next {

                width: 40px;

                height: 55px;

                font-size: 28px;

            }


            .modal-prev {

                left: 5px;

            }


            .modal-next {

                right: 5px;

            }


            .modal-thumb {

                width: 55px;

                height: 55px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

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


        <a
            href="../cart/index.php"
            class="cart"
        >
            Giỏ hàng
            <span>0</span>
        </a>

    </div>

</header>



<!-- =========================================================
     PRODUCT DETAIL
========================================================= -->

<section class="detail-page">

    <div class="container">

        <div class="detail-container">


            <!-- =================================================
                 PRODUCT GALLERY
            ================================================= -->

            <div class="detail-gallery">


                <!-- THUMBNAILS -->

                <div class="product-thumbs">

                    <?php foreach (
                        $productImages
                        as $index => $img
                    ): ?>

                        <button
                            type="button"
                            class="thumb-item <?= $index === 0 ? 'active' : '' ?>"
                            onclick="changeMainImage(this, <?= $index ?>)"
                        >

                            <img
                                src="<?= htmlspecialchars($img) ?>"
                                alt="Ảnh sản phẩm <?= $index + 1 ?>"
                            >

                        </button>

                    <?php endforeach; ?>

                </div>


                <!-- ẢNH CHÍNH -->

                <div class="detail-main-image">

                    <img
                        id="mainImage"
                        src="<?= htmlspecialchars($productImage) ?>"
                        alt="<?= htmlspecialchars(
                            $product['name'] ?? 'Sản phẩm'
                        ) ?>"
                        onclick="openImageModal(currentImageIndex)"
                    >


                    <div class="image-zoom-hint">
                        🔍 Bấm vào ảnh để xem lớn
                    </div>

                </div>


            </div>



            <!-- =================================================
                 PRODUCT INFO
            ================================================= -->

            <div class="detail-info">


                <!-- CATEGORY -->

                <p class="detail-category">

                    <?= htmlspecialchars(
                        $product['category_name']
                        ?? 'Thời trang'
                    ) ?>

                </p>


                <!-- NAME -->

                <h1>

                    <?= htmlspecialchars(
                        $product['name']
                        ?? 'Sản phẩm'
                    ) ?>

                </h1>


                <!-- RATING -->

                <div class="rating-box">

                    <div class="rating-stars">
                        ★★★★★
                    </div>

                    <div class="rating-info">

                        <strong>4.8</strong>

                        <span>/ 5</span>

                        <span class="rating-divider">|</span>

                        <span>128 đánh giá</span>

                    </div>

                </div>


                <!-- PRICE -->

                <div class="price-box">

                    <p class="detail-price">

                        <?= number_format(
                            (float)(
                                ($product['price'] ?? 0) * 1000
                            ),
                            0,
                            '',
                            '.'
                        ) ?>đ

                    </p>


                    <?php
                    if (
                        !empty($product['original_price']) &&
                        $product['original_price'] >
                        $product['price']
                    ):
                    ?>

                        <span class="old-price">

                            <?= number_format(
                                $product['original_price'] * 1000,
                                0,
                                '',
                                '.'
                            ) ?>đ

                        </span>


                        <span class="discount-badge">

                            -<?= round(
                                (
                                    (
                                        $product['original_price']
                                        -
                                        $product['price']
                                    )
                                    /
                                    $product['original_price']
                                ) * 100
                            ) ?>%

                        </span>

                    <?php endif; ?>

                </div>


                <!-- DESCRIPTION -->

                <div class="detail-description">

                    <?= nl2br(
                        strip_tags(
                            $product['description']
                            ??
                            'Sản phẩm thời trang chất lượng cao.'
                        )
                    ) ?>

                </div>


                <!-- STOCK -->

                <p class="detail-stock">

                    Còn lại:

                    <strong>

                        <?= (int)(
                            $product['stock'] ?? 0
                        ) ?>

                    </strong>

                    sản phẩm

                </p>


                <!-- COLOR -->

                <div class="option-group">

                    <label>

                        Màu sắc:

                        <span id="selectedColorText">

                            <?= htmlspecialchars($productColor) ?>

                        </span>

                    </label>


                    <div class="color-options">

                        <span
                            class="color-dot active"
                            style="background-color:<?= htmlspecialchars($productColorHex) ?>;"
                            title="<?= htmlspecialchars($productColor) ?>"
                        ></span>

                    </div>

                </div>


                <!-- SIZE -->

                <div class="option-group">

                    <label>

                        Kích thước:

                        <span id="selectedSizeText">
                            M
                        </span>

                    </label>


                    <div class="size-options">

                        <?php
                        foreach (
                            ['S', 'M', 'L', 'XL', 'XXL']
                            as $sizeName
                        ):
                        ?>

                            <button
                                type="button"
                                class="size-btn <?= $sizeName === 'M' ? 'active' : '' ?>"
                                onclick="
                                    selectSize(
                                        this,
                                        '<?= $sizeName ?>'
                                    )
                                "
                            >

                                <?= $sizeName ?>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- ADD CART -->

                <form
                    action="../cart/add.php"
                    method="GET"
                    class="detail-buttons"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$product['id'] ?>"
                    >


                    <input
                        type="hidden"
                        name="color"
                        id="inputColor"
                        value="<?= htmlspecialchars($productColor) ?>"
                    >


                    <input
                        type="hidden"
                        name="size"
                        id="inputSize"
                        value="M"
                    >


                    <a
                        href="index.php"
                        class="back-btn"
                    >

                        ← Quay lại

                    </a>


                    <button
                        type="submit"
                        class="cart-btn"
                    >

                        🛒 Thêm vào giỏ hàng

                    </button>

                </form>


                <!-- POLICIES -->

                <div class="policies">

                    <div>

                        🚚 Miễn phí vận chuyển

                        <br>

                        <small>
                            Cho đơn từ 500.000đ
                        </small>

                    </div>


                    <div>

                        🔄 Đổi trả dễ dàng

                        <br>

                        <small>
                            Trong vòng 7 ngày
                        </small>

                    </div>


                    <div>

                        🔒 Thanh toán an toàn

                        <br>

                        <small>
                            Bảo mật thông tin
                        </small>

                    </div>

                </div>


            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     CHI TIẾT SẢN PHẨM - CUỐI TRANG
========================================================= -->

<section class="product-detail-bottom">

    <div class="product-detail-content">

        <h2 class="product-detail-title">
            Chi tiết sản phẩm
        </h2>


        <div class="product-detail-description">

            <?= nl2br(
                strip_tags(
                    $product['description']
                    ?? 'Sản phẩm thời trang chất lượng cao, được thiết kế phù hợp với nhu cầu sử dụng hằng ngày.'
                )
            ) ?>

        </div>


        <h3 class="product-detail-subtitle">
            Thông tin chi tiết
        </h3>


        <div class="product-detail-specs">


            <div class="spec-item">

                <strong>Danh mục</strong>

                <span>

                    <?= htmlspecialchars(
                        $product['category_name']
                        ?? 'Thời trang'
                    ) ?>

                </span>

            </div>


            <div class="spec-item">

                <strong>Màu sắc</strong>

                <span>

                    <?= htmlspecialchars($productColor) ?>

                </span>

            </div>


            <div class="spec-item">

                <strong>Kích thước</strong>

                <span>
                    S, M, L, XL, XXL
                </span>

            </div>


            <div class="spec-item">

                <strong>Tình trạng</strong>

                <span>

                    <?= ($product['stock'] ?? 0) > 0
                        ? 'Còn hàng'
                        : 'Hết hàng'
                    ?>

                </span>

            </div>


            <div class="spec-item">

                <strong>Số lượng còn lại</strong>

                <span>

                    <?= (int)($product['stock'] ?? 0) ?>

                    sản phẩm

                </span>

            </div>


            <div class="spec-item">

                <strong>Giá bán</strong>

                <span>

                    <?= number_format(
                        (float)(($product['price'] ?? 0) * 1000),
                        0,
                        '',
                        '.'
                    ) ?>đ

                </span>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     IMAGE MODAL
========================================================= -->

<div
    id="imageModal"
    class="image-modal"
>


    <div class="image-modal-content">


        <!-- NÚT ĐÓNG -->

        <button
            type="button"
            class="image-modal-close"
            onclick="closeImageModal()"
        >
            ×
        </button>


        <!-- NÚT TRÁI -->

        <button
            type="button"
            class="modal-prev"
            onclick="changeModalImage(-1)"
        >
            ‹
        </button>


        <!-- ẢNH LỚN -->

        <img
            id="modalImage"
            src=""
            alt="Ảnh sản phẩm"
        >


        <!-- NÚT PHẢI -->

        <button
            type="button"
            class="modal-next"
            onclick="changeModalImage(1)"
        >
            ›
        </button>


        <!-- THUMBNAILS -->

        <div class="modal-thumbnails">

            <?php foreach (
                $productImages
                as $index => $img
            ): ?>

                <button
                    type="button"
                    class="modal-thumb <?= $index === 0 ? 'active' : '' ?>"
                    onclick="
                        showModalImage(<?= $index ?>)
                    "
                >

                    <img
                        src="<?= htmlspecialchars($img) ?>"
                        alt="Ảnh <?= $index + 1 ?>"
                    >

                </button>

            <?php endforeach; ?>

        </div>


    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   DANH SÁCH ẢNH
========================================================= */

const productImages = [

    <?php foreach ($productImages as $img): ?>

        "<?= htmlspecialchars(
            $img,
            ENT_QUOTES
        ) ?>",

    <?php endforeach; ?>

];


let currentImageIndex = 0;



/* =========================================================
   ĐỔI ẢNH CHÍNH
========================================================= */

function changeMainImage(element, index) {

    const mainImage =
        document.getElementById('mainImage');

    const thumbImage =
        element.querySelector('img');


    if (!thumbImage) {
        return;
    }


    currentImageIndex = index;


    /* Hiệu ứng */

    mainImage.style.opacity = '0';


    setTimeout(function () {

        mainImage.src =
            productImages[index];

        mainImage.style.opacity = '1';

    }, 150);


    /* Xóa active */

    document
        .querySelectorAll('.thumb-item')
        .forEach(function (item) {

            item.classList.remove('active');

        });


    /* Active ảnh được chọn */

    element.classList.add('active');

}



/* =========================================================
   MỞ ẢNH LỚN
========================================================= */

function openImageModal(index) {

    if (productImages.length === 0) {
        return;
    }


    currentImageIndex = index;


    const modal =
        document.getElementById('imageModal');


    modal.classList.add('show');


    /* Không cho trang phía sau scroll */

    document.body.style.overflow = 'hidden';


    showModalImage(index);

}



/* =========================================================
   HIỂN THỊ ẢNH TRONG MODAL
========================================================= */

function showModalImage(index) {

    if (productImages.length === 0) {
        return;
    }


    /* Nếu nhỏ hơn 0 */

    if (index < 0) {

        index =
            productImages.length - 1;

    }


    /* Nếu vượt quá số ảnh */

    if (
        index >= productImages.length
    ) {

        index = 0;

    }


    currentImageIndex = index;


    const modalImage =
        document.getElementById('modalImage');


    /* Đổi ảnh */

    modalImage.src =
        productImages[index];


    /* Active thumbnail modal */

    document
        .querySelectorAll('.modal-thumb')
        .forEach(function (thumb, i) {

            if (i === index) {

                thumb.classList.add('active');

            } else {

                thumb.classList.remove('active');

            }

        });


    /* Active thumbnail bên ngoài */

    document
        .querySelectorAll('.thumb-item')
        .forEach(function (thumb, i) {

            if (i === index) {

                thumb.classList.add('active');

            } else {

                thumb.classList.remove('active');

            }

        });


    /* Đồng bộ ảnh chính */

    document
        .getElementById('mainImage')
        .src = productImages[index];

}



/* =========================================================
   ẢNH TRƯỚC / SAU
========================================================= */

function changeModalImage(direction) {

    let newIndex =
        currentImageIndex + direction;


    if (newIndex < 0) {

        newIndex =
            productImages.length - 1;

    }


    if (
        newIndex >=
        productImages.length
    ) {

        newIndex = 0;

    }


    showModalImage(newIndex);

}



/* =========================================================
   ĐÓNG MODAL
========================================================= */

function closeImageModal() {

    const modal =
        document.getElementById('imageModal');


    modal.classList.remove('show');


    /* Cho phép trang scroll lại */

    document.body.style.overflow = '';

}



/* =========================================================
   BẤM RA NGOÀI ẢNH → ĐÓNG
========================================================= */

document
    .getElementById('imageModal')
    .addEventListener(
        'click',
        function(event) {

            if (
                event.target === this
            ) {

                closeImageModal();

            }

        }
    );



/* =========================================================
   PHÍM BÀN PHÍM
========================================================= */

document.addEventListener(
    'keydown',
    function(event) {

        const modal =
            document.getElementById(
                'imageModal'
            );


        if (
            !modal.classList.contains('show')
        ) {

            return;

        }


        /* ESC */

        if (event.key === 'Escape') {

            closeImageModal();

        }


        /* ← */

        if (event.key === 'ArrowLeft') {

            changeModalImage(-1);

        }


        /* → */

        if (event.key === 'ArrowRight') {

            changeModalImage(1);

        }

    }
);



/* =========================================================
   CHỌN MÀU
========================================================= */

function selectColor(
    element,
    colorName
) {

    document
        .querySelectorAll('.color-dot')
        .forEach(function(el) {

            el.classList.remove('active');

        });


    element.classList.add('active');


    document
        .getElementById(
            'selectedColorText'
        )
        .innerText = colorName;


    document
        .getElementById('inputColor')
        .value = colorName;

}



/* =========================================================
   CHỌN SIZE
========================================================= */

function selectSize(
    element,
    sizeName
) {

    document
        .querySelectorAll('.size-btn')
        .forEach(function(el) {

            el.classList.remove('active');

        });


    element.classList.add('active');


    document
        .getElementById(
            'selectedSizeText'
        )
        .innerText = sizeName;


    document
        .getElementById('inputSize')
        .value = sizeName;

}

</script>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="detail-footer">

    <div class="container">


        <div class="detail-footer-content">


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


        <div class="detail-copyright">

            © 2026 Fashion Shop

        </div>


    </div>

</footer>


</body>

</html>