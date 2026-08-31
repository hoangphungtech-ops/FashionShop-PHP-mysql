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
    $image = trim((string)($image ?? ''));

    // Không có ảnh
    if ($image === '') {
        return '../assets/images/ao-thun.jpg';
    }

    // Chuẩn hóa đường dẫn
    $image = str_replace('\\', '/', $image);
    $image = ltrim($image, '/');

    // DB lưu nguyên đường dẫn:
    // assets/images/abc.jpg
    // uploads/products/abc.jpg
    $fullPath = __DIR__ . '/../' . $image;

    if (file_exists($fullPath)) {
        return '../' . $image;
    }

    // Trường hợp DB chỉ lưu tên file
    $filename = basename($image);

    // Tìm trong uploads/products
    if (file_exists(__DIR__ . '/../uploads/products/' . $filename)) {
        return '../uploads/products/' . $filename;
    }

    // Tìm trong assets/images
    if (file_exists(__DIR__ . '/../assets/images/' . $filename)) {
        return '../assets/images/' . $filename;
    }

    // Ảnh mặc định
    return '../assets/images/ao-thun.jpg';
}

$categoryNames = [
    1 => 'Áo',
    2 => 'Quần',
    3 => 'Váy'
];

$pageHeading = $categoryNames[$category] ?? 'Tất cả sản phẩm';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Khám phá bộ sưu tập thời trang hiện đại tại Fashion Shop.">
    <title><?= htmlspecialchars($pageHeading) ?> | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="site-body catalog-page">
<?php
$siteBasePath = '../';
$currentPage = 'products';
$currentCategory = $category;
$cartCount = 0;
require __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="page-intro">
        <div class="site-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="../index.php">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= htmlspecialchars($pageHeading) ?></span>
            </nav>

            <div class="page-intro__content">
                <p class="eyebrow">The collection</p>
                <h1><?= htmlspecialchars($pageHeading) ?></h1>
                <p>Khám phá những thiết kế được tuyển chọn cho tủ đồ hiện đại, thanh lịch và mang dấu ấn riêng.</p>
            </div>
        </div>
    </section>

    <section class="catalog" aria-label="Danh sách sản phẩm">
        <div class="site-container">
            <div class="catalog-toolbar">
                <p class="catalog-toolbar__count">
                    <strong><?= count($products) ?></strong>
                    sản phẩm
                </p>

                <nav class="category-filter" aria-label="Lọc theo danh mục">
                    <a href="index.php" class="<?= $category === 0 ? 'is-active' : '' ?>">Tất cả</a>
                    <a href="index.php?category=1" class="<?= $category === 1 ? 'is-active' : '' ?>">Áo</a>
                    <a href="index.php?category=2" class="<?= $category === 2 ? 'is-active' : '' ?>">Quần</a>
                    <a href="index.php?category=3" class="<?= $category === 3 ? 'is-active' : '' ?>">Váy</a>
                </nav>
            </div>

            <?php if (!empty($products)): ?>
                <div class="product-grid product-grid--catalog">
                    <?php foreach ($products as $product): ?>
                        <?php $image = getProductImage($product['image'] ?? ''); ?>
                        <article class="product-card">
                            <a
                                class="product-card__media"
                                href="detail.php?id=<?= (int)$product['id'] ?>"
                            >
                                <span class="product-badge">New</span>
                                <img
                                    src="<?= htmlspecialchars($image) ?>"
                                    alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>"
                                    loading="lazy"
                                >
                                <span class="product-card__view" aria-hidden="true">Xem chi tiết</span>
                            </a>
                            <div class="product-card__body">
                                <p class="product-card__category">
                                    <?= htmlspecialchars($categoryNames[(int)($product['category_id'] ?? 0)] ?? 'Fashion') ?>
                                </p>
                                <h2>
                                    <a href="detail.php?id=<?= (int)$product['id'] ?>">
                                        <?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>
                                    </a>
                                </h2>
                                <p class="product-card__price">
                                    <?= number_format((float)($product['price'] ?? 0), 0, ',', '.') ?>đ
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p class="eyebrow">No products found</p>
                    <h2>Chưa có sản phẩm phù hợp</h2>
                    <p>Danh mục này đang được cập nhật. Hãy khám phá các lựa chọn khác.</p>
                    <a class="button button--primary" href="index.php">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
