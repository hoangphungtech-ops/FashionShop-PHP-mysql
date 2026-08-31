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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta
        name="description"
        content="<?= htmlspecialchars($product['name'] ?? 'Chi tiết sản phẩm Fashion Shop') ?>"
    >
    <title><?= htmlspecialchars($product['name'] ?? 'Chi tiết sản phẩm') ?> | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="site-body product-detail-page">
<?php
$siteBasePath = '../';
$currentPage = 'products';
$currentCategory = (int)($product['category_id'] ?? 0);
$cartCount = isset($_SESSION['cart']) && is_array($_SESSION['cart'])
    ? count($_SESSION['cart'])
    : 0;
require __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <div class="site-container">
        <nav class="breadcrumb breadcrumb--detail" aria-label="Breadcrumb">
            <a href="../index.php">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <a href="index.php">Sản phẩm</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= htmlspecialchars($product['name'] ?? 'Chi tiết') ?></span>
        </nav>
    </div>

    <section class="product-detail" aria-labelledby="product-title">
        <div class="site-container product-detail__grid">
            <div class="product-gallery" data-product-gallery>
                <div class="product-gallery__main">
                    <span class="product-badge">New</span>
                    <img
                        src="<?= htmlspecialchars($productImage) ?>"
                        alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>"
                        data-gallery-main
                    >
                </div>

                <div class="product-gallery__thumbs" aria-label="Ảnh sản phẩm">
                    <button
                        class="product-gallery__thumb is-active"
                        type="button"
                        aria-label="Xem ảnh chính"
                        aria-pressed="true"
                        data-gallery-thumb
                        data-gallery-src="<?= htmlspecialchars($productImage) ?>"
                    >
                        <img
                            src="<?= htmlspecialchars($productImage) ?>"
                            alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?> - ảnh chính"
                        >
                    </button>
                    <!-- TODO(product_images): hiển thị thumbnail bổ sung khi module dữ liệu ảnh cung cấp danh sách. -->
                </div>
            </div>

            <div class="product-detail__info">
                <p class="eyebrow">
                    <?= htmlspecialchars($product['category_name'] ?? 'Thời trang') ?>
                </p>
                <h1 id="product-title"><?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?></h1>
                <p class="product-detail__price">
                    <?= number_format((float)($product['price'] ?? 0), 0, ',', '.') ?>đ
                </p>

                <div class="product-detail__rule"></div>

                <div class="product-detail__description">
                    <h2>Mô tả sản phẩm</h2>
                    <p>
                        <?= nl2br(htmlspecialchars(
                            $product['description']
                            ?? 'Sản phẩm thời trang chất lượng cao.'
                        )) ?>
                    </p>
                </div>

                <div class="product-detail__meta">
                    <div>
                        <span>Tình trạng</span>
                        <strong>
                            <?= (int)($product['stock'] ?? 0) > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                        </strong>
                    </div>
                    <div>
                        <span>Số lượng trong kho</span>
                        <strong><?= (int)($product['stock'] ?? 0) ?> sản phẩm</strong>
                    </div>
                </div>

                <div class="product-detail__actions">
                    <a
                        class="button button--primary product-detail__cart"
                        href="../cart/add.php?id=<?= (int)$product['id'] ?>"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5.75 8.25h12.5l1 12H4.75l1-12Z"></path>
                            <path d="M8.75 9V6.75a3.25 3.25 0 0 1 6.5 0V9"></path>
                        </svg>
                        Thêm vào giỏ hàng
                    </a>
                    <a class="button button--outline" href="index.php">Tiếp tục mua sắm</a>
                </div>

                <div class="product-detail__service">
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3.5 6.5h11v10h-11zM14.5 10h3.25l2.75 3v3.5h-6z"></path>
                            <circle cx="7" cy="18" r="1.5"></circle>
                            <circle cx="17.5" cy="18" r="1.5"></circle>
                        </svg>
                        <span><strong>Giao hàng cẩn thận</strong>Đóng gói chỉn chu cho từng đơn hàng.</span>
                    </div>
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3.5 19 6v5.5c0 4.3-2.35 7.3-7 9-4.65-1.7-7-4.7-7-9V6l7-2.5Z"></path>
                            <path d="m8.75 12 2.1 2.1 4.4-4.45"></path>
                        </svg>
                        <span><strong>Sản phẩm chọn lọc</strong>Ưu tiên chất lượng và trải nghiệm.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
