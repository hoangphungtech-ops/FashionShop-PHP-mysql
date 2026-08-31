<?php

require_once __DIR__ . "/includes/db.php";

/* =========================
   GET PRODUCTS
========================= */

$products = [];

try {

    $sql = "SELECT * FROM products
            WHERE status = 1
            ORDER BY id DESC
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fashion Shop - thời trang hiện đại, thanh lịch và được tuyển chọn cho phong cách riêng của bạn.">
    <title>Fashion Shop | Thời trang dành cho bạn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="site-body home-page">
<?php
$siteBasePath = '';
$currentPage = 'home';
$currentCategory = 0;
$cartCount = 0;
require __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="home-hero">
        <div class="site-container home-hero__grid">
            <div class="home-hero__content">
                <p class="eyebrow">New season · 2026</p>
                <h1>Thời trang<br><em>dành cho bạn</em></h1>
                <p class="home-hero__intro">
                    Những thiết kế hiện đại, tinh tế và dễ mặc — được tuyển chọn để bạn tự tin thể hiện phong cách riêng mỗi ngày.
                </p>
                <div class="home-hero__actions">
                    <a class="button button--primary" href="products/index.php">
                        Khám phá sản phẩm
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 7l5 5-5 5"></path></svg>
                    </a>
                    <a class="text-link" href="#featured-products">Xem bộ sưu tập mới</a>
                </div>
                <div class="home-hero__note" aria-label="Cam kết của Fashion Shop">
                    <span>Chọn lọc kỹ lưỡng</span>
                    <span>Phong cách hiện đại</span>
                </div>
            </div>

            <div class="home-hero__visual">
                <div class="home-hero__frame">
                    <img
                        src="assets/images/vay-nu-thanh-lich.jpg"
                        alt="Váy nữ thanh lịch trong bộ sưu tập Fashion Shop"
                    >
                </div>
                <p class="home-hero__caption"><span>01</span> The everyday edit</p>
            </div>
        </div>
    </section>

    <section class="category-edit" aria-labelledby="category-heading">
        <div class="site-container">
            <div class="section-heading section-heading--split">
                <div>
                    <p class="eyebrow">Shop by category</p>
                    <h2 id="category-heading">Chọn phong cách của bạn</h2>
                </div>
                <p>Những lựa chọn thiết yếu để xây dựng tủ đồ hiện đại, thanh lịch và linh hoạt.</p>
            </div>

            <div class="category-grid">
                <a class="category-tile category-tile--large" href="products/index.php?category=1">
                    <img src="assets/images/ao-so-mi-nu - Copy.jpg" alt="Bộ sưu tập áo">
                    <span class="category-tile__overlay"></span>
                    <span class="category-tile__content">
                        <small>01 · Collection</small>
                        <strong>Áo</strong>
                        <span>Khám phá <b aria-hidden="true">↗</b></span>
                    </span>
                </a>

                <a class="category-tile" href="products/index.php?category=2">
                    <img src="assets/images/quan-jean-nu.jpg" alt="Bộ sưu tập quần">
                    <span class="category-tile__overlay"></span>
                    <span class="category-tile__content">
                        <small>02 · Collection</small>
                        <strong>Quần</strong>
                        <span>Khám phá <b aria-hidden="true">↗</b></span>
                    </span>
                </a>

                <a class="category-tile" href="products/index.php?category=3">
                    <img src="assets/images/vay-nu-thanh-lich.jpg" alt="Bộ sưu tập váy">
                    <span class="category-tile__overlay"></span>
                    <span class="category-tile__content">
                        <small>03 · Collection</small>
                        <strong>Váy</strong>
                        <span>Khám phá <b aria-hidden="true">↗</b></span>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <section class="featured-products" id="featured-products" aria-labelledby="featured-heading">
        <div class="site-container">
            <div class="section-heading section-heading--split section-heading--baseline">
                <div>
                    <p class="eyebrow">Selected for you</p>
                    <h2 id="featured-heading">Sản phẩm nổi bật</h2>
                </div>
                <a class="text-link text-link--arrow" href="products/index.php">Xem tất cả sản phẩm <span>→</span></a>
            </div>

            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php $image = getProductImage($product['image'] ?? ''); ?>
                        <article class="product-card">
                            <a
                                class="product-card__media"
                                href="products/detail.php?id=<?= (int)$product['id'] ?>"
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
                                <p class="product-card__category">Fashion selection</p>
                                <h3>
                                    <a href="products/detail.php?id=<?= (int)$product['id'] ?>">
                                        <?= htmlspecialchars($product['name'] ?? 'Sản phẩm') ?>
                                    </a>
                                </h3>
                                <p class="product-card__price">
                                    <?= number_format((float)($product['price'] ?? 0), 0, ',', '.') ?>đ
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p class="eyebrow">Collection update</p>
                    <h3>Chưa có sản phẩm</h3>
                    <p>Các sản phẩm mới đang được cập nhật. Vui lòng quay lại sau.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="brand-story" aria-labelledby="story-heading">
        <div class="site-container brand-story__grid">
            <div class="brand-story__image">
                <img src="assets/images/ao-khoac-denim.jpg" alt="Chi tiết áo khoác denim Fashion Shop" loading="lazy">
                <span>Modern essentials</span>
            </div>

            <div class="brand-story__content">
                <p class="eyebrow">Our philosophy</p>
                <h2 id="story-heading">Đẹp theo cách<br><em>của bạn.</em></h2>
                <p>
                    Chúng tôi tin rằng thời trang tốt không cần phô trương. Mỗi thiết kế được lựa chọn dựa trên sự cân bằng giữa kiểu dáng, chất lượng và khả năng đồng hành cùng bạn mỗi ngày.
                </p>
                <div class="brand-values">
                    <div>
                        <span>01</span>
                        <strong>Chất lượng chọn lọc</strong>
                        <p>Sản phẩm phù hợp cho nhịp sống hiện đại.</p>
                    </div>
                    <div>
                        <span>02</span>
                        <strong>Thiết kế linh hoạt</strong>
                        <p>Dễ kết hợp, dễ tạo dấu ấn riêng.</p>
                    </div>
                    <div>
                        <span>03</span>
                        <strong>Trải nghiệm chỉn chu</strong>
                        <p>Từ lựa chọn đến khi sản phẩm tới tay bạn.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="site-container final-cta__inner">
            <div>
                <p class="eyebrow">Your style, your story</p>
                <h2>Sẵn sàng làm mới phong cách?</h2>
            </div>
            <a class="button button--light" href="products/index.php">
                Mua sắm ngay
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 7l5 5-5 5"></path></svg>
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/main.js" defer></script>
</body>
</html>
