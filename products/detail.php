<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   KIỂM TRA ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Sản phẩm không hợp lệ.");
}

$id = (int) $_GET['id'];


/* =========================
   LẤY SẢN PHẨM
========================= */

try {

    $sql = "SELECT products.*,
                   categories.name AS category_name
            FROM products
            LEFT JOIN categories
                ON products.category_id = categories.id
            WHERE products.id = :id
            AND products.status = 1
            LIMIT 1";

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


/* =========================
   XỬ LÝ 1 ẢNH SẢN PHẨM
========================= */

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


    /* Chuẩn hóa đường dẫn */

    $image = str_replace("\\", "/", $image);

    $projectRoot = dirname(__DIR__);


    /* =========================
       DATABASE LƯU uploads/...
    ========================= */

    if (strpos($image, "uploads/") === 0) {

        $fullPath = $projectRoot . "/" . $image;

        if (file_exists($fullPath)) {

            return "../" . $image;

        }
    }


    /* =========================
       DATABASE LƯU assets/...
    ========================= */

    if (strpos($image, "assets/") === 0) {

        $fullPath = $projectRoot . "/" . $image;

        if (file_exists($fullPath)) {

            return "../" . $image;

        }
    }


    /* =========================
       CHỈ LƯU TÊN FILE
    ========================= */

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

        $fullPath =
            $projectRoot .
            "/" .
            $folder .
            $filename;

        if (file_exists($fullPath)) {

            return "../" .
                   $folder .
                   $filename;

        }
    }


    /* =========================
       ẢNH MẶC ĐỊNH
    ========================= */

    return "../assets/images/ao-thun.jpg";
}


/* =========================
   LẤY ẢNH
========================= */

$productImage = getProductImage(
    $product['image'] ?? ''
);

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

        /* =========================
           DETAIL PAGE
        ========================= */

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

            background: #ffffff;

            border: 1px solid #e4ebe4;

        }


        /* =========================
           IMAGE & THUMBS
        ========================= */

        .detail-gallery {

            display: flex;

            gap: 15px;

            width: 100%;

        }

        .product-thumbs {

            display: flex;

            flex-direction: column;

            gap: 10px;

        }

        .thumb-item {

            width: 70px;

            height: 85px;

            object-fit: cover;

            cursor: pointer;

            border: 1px solid #ddd;

            border-radius: 4px;

            transition: 0.2s;

        }

        .thumb-item:hover, .thumb-item.active {

            border-color: #263126;

        }


        .detail-main-image {

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

            transition: 0.4s ease;

        }


        .detail-main-image img:hover {

            transform: scale(1.03);

        }


        /* =========================
           INFO
        ========================= */

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

        /* Đánh giá sao & Giá tiền */
        .rating-box {
            color: #f59e0b;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .price-box {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-price {
            margin-bottom: 0px !important;
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


        .detail-description {

            margin-bottom: 20px;

            color: #687168;

            font-size: 15px;

            line-height: 1.8;

        }


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


        /* =========================
           MÀU SẮC & KÍCH THƯỚC
        ========================= */
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

        .size-btn:hover, .size-btn.active {
            border-color: #263126;
            background: #263126;
            color: #fff;
            font-weight: bold;
        }


        /* =========================
           BUTTON
        ========================= */

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

            background: #ffffff;

            color: #78917d;

        }


        .back-btn:hover {

            background: #edf4ee;

        }


        .cart-btn {

            border: 1px solid #263126;

            background: #263126;

            color: #ffffff;
            
            flex: 1;

        }


        .cart-btn:hover {

            border-color: #78917d;

            background: #78917d;

        }


        /* Chính sách dịch vụ nhỏ */
        .policies {
            border-top: 1px solid #eee;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            text-align: center;
        }


        /* =========================
           FOOTER
        ========================= */

        .detail-footer {

            padding: 55px 0 25px;

            background: #263126;

            color: #ffffff;

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

            color: #ffffff;

        }


        .detail-copyright {

            padding-top: 20px;

            border-top: 1px solid #465046;

            text-align: center;

            color: #9fa99f;

            font-size: 12px;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 850px) {

            .detail-container {

                grid-template-columns: 1fr;

                gap: 35px;

            }

            .detail-gallery {
                flex-direction: column-reverse;
            }

            .product-thumbs {
                flex-direction: row;
                overflow-x: auto;
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



<!-- =========================
     PRODUCT DETAIL
========================= -->

<section class="detail-page">

    <div class="container">


        <div class="detail-container">


            <!-- =========================
                 PRODUCT IMAGE & THUMBS
            ========================= -->

            <div class="detail-gallery">

                <!-- Danh sách ảnh nhỏ bên trái -->
                <div class="product-thumbs">
                    <img src="<?= htmlspecialchars($productImage) ?>" 
                         class="thumb-item active" 
                         onclick="changeMainImage(this)"
                         alt="Thumbnail">
                </div>

                <!-- Ảnh lớn bên phải -->
                <div class="detail-main-image">

                    <img
                        id="mainImage"
                        src="<?= htmlspecialchars($productImage) ?>"
                        alt="<?= htmlspecialchars(
                            $product['name'] ?? 'Sản phẩm'
                        ) ?>"
                    >

                </div>

            </div>



            <!-- =========================
                 PRODUCT INFO
            ========================= -->

            <div class="detail-info">


                <p class="detail-category">

                    <?= htmlspecialchars(
                        $product['category_name']
                        ?? 'Thời trang'
                    ) ?>

                </p>


                <h1>

                    <?= htmlspecialchars(
                        $product['name']
                        ?? 'Sản phẩm'
                    ) ?>

                </h1>

                <!-- Đánh giá sao mẫu -->
                <div class="rating-box">
                    ★★★★★ <span style="color: #666; margin-left: 5px;">4.8 (128 đánh giá)</span>
                </div>

                <!-- Giá tiền & Giảm giá -->
                <div class="price-box">
                    <p class="detail-price">
                        <?= number_format(
                            (float)(
                                $product['price'] ?? 0
                            ),
                            0,
                            ',',
                            '.'
                        ) ?>đ
                    </p>
                    
                   <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                        <span class="old-price">
                            <?= number_format($product['original_price'], 0, ',', '.') ?>đ
                        </span>
                        <span class="discount-badge">
                            -<?= round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) ?>%
                        </span>
                    <?php endif; ?>
                </div>


                <div class="detail-description">

                    <?= nl2br(
                        strip_tags(
                            $product['description']
                            ??
                            'Sản phẩm thời trang chất lượng cao.'
                        )
                    ) ?>

                </div>


                <p class="detail-stock">

                    Còn lại:

                    <strong>

                        <?= (int)(
                            $product['stock'] ?? 0
                        ) ?>

                    </strong>

                    sản phẩm

                </p>


                <!-- Tùy chọn Màu sắc -->
                <div class="option-group">
                    <label>Màu sắc: <span id="selectedColorText">Xám than</span></label>
                    <div class="color-options">
                        <span class="color-dot active" style="background-color: #333;" onclick="selectColor(this, 'Xám than')"></span>
                        <span class="color-dot" style="background-color: #555;" onclick="selectColor(this, 'Xám sáng')"></span>
                        <span class="color-dot" style="background-color: #e3d2bc;" onclick="selectColor(this, 'Be kem')"></span>
                        <span class="color-dot" style="background-color: #111;" onclick="selectColor(this, 'Đen')"></span>
                    </div>
                </div>


                <!-- Tùy chọn Kích thước Size -->
                <div class="option-group">
                    <label>Kích thước: <span id="selectedSizeText">M</span></label>
                    <div class="size-options">
                        <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $sizeName): ?>
                            <button type="button" class="size-btn <?= $sizeName === 'M' ? 'active' : '' ?>" onclick="selectSize(this, '<?= $sizeName ?>')"><?= $sizeName ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>


                <div class="detail-buttons">

                    <a
                        href="index.php"
                        class="back-btn"
                    >

                        ← Quay lại

                    </a>

                    <a
                        href="../cart/add.php?id=<?= (int)$product['id'] ?>"
                        class="cart-btn"
                    >

                        🛒 Thêm vào giỏ hàng

                    </a>

                </div>


                <!-- Chính sách dịch vụ nhỏ -->
                <div class="policies">
                    <div>🚚 Miễn phí vận chuyển <br><small>Cho đơn từ 2.500.000đ</small></div>
                    <div>🔄 Đổi trả dễ dàng <br><small>Trong 30 ngày</small></div>
                    <div>🔒 Thanh toán an toàn <br><small>Bảo mật tuyệt đối</small></div>
                </div>


            </div>


        </div>

    </div>

</section>


<script>
    // Hàm đổi ảnh lớn khi bấm vào ảnh nhỏ
    function changeMainImage(element) {
        document.getElementById('mainImage').src = element.src;
        document.querySelectorAll('.thumb-item').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    }

    // Hàm xử lý chọn màu sắc
    function selectColor(element, colorName) {
        document.querySelectorAll('.color-dot').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        document.getElementById('selectedColorText').innerText = colorName;
    }

    // Hàm xử lý chọn size
    function selectSize(element, sizeName) {
        document.querySelectorAll('.size-btn').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        document.getElementById('selectedSizeText').innerText = sizeName;
    }
</script>



<!-- =========================
     FOOTER
========================= -->

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