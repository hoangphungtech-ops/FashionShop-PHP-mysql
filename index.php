<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$products = [];

try {
    $statement = $pdo->query(
        'SELECT * FROM products WHERE status = 1 ORDER BY id DESC LIMIT 8'
    );
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('[home] Cannot load featured products: ' . $exception->getMessage());
    $products = [];
}

function homeProductImage(mixed $value): string
{
    $image = trim((string) ($value ?? ''));
    $fallback = 'assets/images/ao-thun.jpg';

    if ($image === '') {
        return $fallback;
    }

    if (filter_var($image, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $image : $fallback;
    }

    $image = ltrim(str_replace('\\', '/', $image), '/');
    $candidates = [];

    if (!preg_match('#(^|/)\.\.(/|$)#', $image) && !str_contains($image, ':')) {
        $candidates[] = $image;
    }

    $filename = basename($image);

    foreach ([
        'uploads/products/',
        'assets/images/',
        'assets/images/Ablum/',
    ] as $folder) {
        $candidates[] = $folder . $filename;
    }

    foreach (array_unique($candidates) as $candidate) {
        if ($candidate !== '' && is_file(__DIR__ . '/' . $candidate)) {
            return implode('/', array_map(
                static fn (string $segment): string => rawurlencode($segment),
                explode('/', $candidate)
            ));
        }
    }

    return $fallback;
}

function homeCartCount(): int
{
    start_secure_session();
    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        return 0;
    }

    $count = 0;

    foreach ($cart as $value) {
        $quantity = is_array($value) ? ($value['quantity'] ?? 0) : $value;
        $parsed = filter_var($quantity, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 9999],
        ]);

        if ($parsed !== false) {
            $count += (int) $parsed;
        }
    }

    return $count;
}

$cartCount = homeCartCount();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FashionShop - thời trang hiện đại, thanh lịch cho phong cách riêng của bạn.">
    <title>FashionShop | Thời trang dành cho bạn</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260915-ui2">
    <script src="assets/js/main.js?v=20260915-ui2" defer></script>
</head>
<body class="home-v2">

<a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>

<div class="home-v2-topbar" aria-label="Thông tin ưu đãi">
    <div class="home-v2-shell home-v2-topbar__inner">
        <span class="home-v2-topbar__item">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
            Freeship cho đơn từ 499.000đ
        </span>
        <span class="home-v2-topbar__promo"><strong>ƯU ĐÃI MÙA MỚI</strong> <i></i> Giảm đến 50% cho bộ sưu tập mới</span>
        <span class="home-v2-topbar__support">Hỗ trợ khách hàng <i></i> +84 93 829 0584</span>
    </div>
</div>

<header class="home-v2-header" data-home-header>
    <div class="home-v2-shell home-v2-header__inner">
        <a class="home-v2-brand" href="index.php" aria-label="FashionShop - Trang chủ">
            Fashion<span>Shop</span>
        </a>

        <button class="home-v2-menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" data-home-menu-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav class="home-v2-nav" aria-label="Điều hướng chính" data-home-nav>
            <a class="is-active" href="index.php" aria-current="page">Trang chủ</a>
            <a href="products/index.php">Sản phẩm</a>
            <a href="products/index.php?category=1">Áo</a>
            <a href="products/index.php?category=2">Quần</a>
            <a href="products/index.php?category=3">Váy</a>
            <a href="products/index.php?sort=newest">Bộ sưu tập</a>
        </nav>

        <div class="home-v2-header__actions">
            <form class="home-v2-search" action="products/index.php" method="get" role="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
                <input type="search" name="q" placeholder="Tìm kiếm sản phẩm..." aria-label="Tìm kiếm sản phẩm">
            </form>

            <a class="home-v2-icon-btn" href="<?= is_logged_in() ? 'auth/profile.php' : 'auth/login.php' ?>" aria-label="<?= is_logged_in() ? 'Tài khoản' : 'Đăng nhập' ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"></circle><path d="M4.5 21c.7-4.2 3.2-6.3 7.5-6.3s6.8 2.1 7.5 6.3"></path></svg>
            </a>

            <a class="home-v2-icon-btn home-v2-cart-btn" href="cart/index.php" aria-label="Giỏ hàng, <?= $cartCount ?> sản phẩm">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l1.8 10.2a2 2 0 0 0 2 1.7h7.9a2 2 0 0 0 1.9-1.4L21 7H6"></path><circle cx="9" cy="20" r="1.3"></circle><circle cx="18" cy="20" r="1.3"></circle></svg>
                <span><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>

<main id="main-content">
    <section class="home-v2-hero" aria-labelledby="home-hero-title">
        <div class="home-v2-shell home-v2-hero__grid">
            <article class="home-v2-hero-main">
                <div class="home-v2-hero-copy">
                    <p class="home-v2-eyebrow">FASHION SHOP</p>
                    <h1 id="home-hero-title">Thời trang<br><span>dành cho bạn</span></h1>
                    <p class="home-v2-hero-lead">Khám phá những thiết kế thời trang trẻ trung, hiện đại và thanh lịch. Tìm cho mình phong cách phù hợp với cá tính riêng của bạn.</p>
                    <div class="home-v2-hero-actions">
                        <a class="home-v2-btn home-v2-btn--primary" href="products/index.php">Khám phá sản phẩm <span>→</span></a>
                        <a class="home-v2-btn home-v2-btn--ghost" href="#featured-products">
                            <span class="home-v2-play">▶</span> Xem bộ sưu tập
                        </a>
                    </div>
                    <div class="home-v2-stats" aria-label="Thống kê FashionShop">
                        <div><strong>1000+</strong><span>Sản phẩm đa dạng</span></div>
                        <div><strong>50K+</strong><span>Khách hàng tin tưởng</span></div>
                        <div><strong>4.9/5</strong><span>Đánh giá trung bình</span></div>
                    </div>
                </div>
                <div class="home-v2-hero-photo" aria-hidden="true">
                    <span class="home-v2-script">Be Your<br>Own Style</span>
                    <img src="assets/images/hero-fashion-nu-new.png" alt="" fetchpriority="high">
                </div>
            </article>

            <aside class="home-v2-hero-side" aria-label="Bộ sưu tập nổi bật">
                <a class="home-v2-editorial-card" href="products/index.php?gender=nam">
                    <img src="assets/images/namthanhlich.png" alt="Bộ sưu tập nam lịch lãm">
                    <span class="home-v2-editorial-card__overlay"></span>
                    <span class="home-v2-editorial-card__copy"><strong>BST Nam</strong><small>Lịch lãm &amp; Hiện đại</small></span>
                    <span class="home-v2-card-arrow">→</span>
                </a>

                <a class="home-v2-editorial-card" href="products/index.php?gender=nu">
                    <img src="assets/images/bst-nu-streetwear.jpg" alt="Bộ sưu tập nữ cá tính">
                    <span class="home-v2-editorial-card__overlay"></span>
                    <span class="home-v2-editorial-card__copy"><strong>BST Nữ</strong><small>Cá tính &amp; Hiện đại</small></span>
                    <span class="home-v2-card-arrow">→</span>
                </a>
            </aside>

            <aside class="home-v2-sale-card" aria-label="Ưu đãi mùa mới">
                <p>SALE</p>
                <h2>UP TO<br><strong>50%</strong></h2>
                <span>Phong cách mới<br>Cho phiên bản tốt hơn</span>
                <a href="products/index.php" class="home-v2-sale-btn">Mua ngay <b>→</b></a>
                <span class="home-v2-sale-leaf home-v2-sale-leaf--one"></span>
                <span class="home-v2-sale-leaf home-v2-sale-leaf--two"></span>
            </aside>
        </div>
    </section>

    <section class="home-v2-benefits" aria-label="Quyền lợi khách hàng">
        <div class="home-v2-shell home-v2-benefits__grid">
            <div class="home-v2-benefit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                <div><strong>Giao hàng nhanh</strong><span>Toàn quốc</span></div>
            </div>
            <div class="home-v2-benefit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.7 3 8.2 7.5 9.5 4.5-1.3 7.5-4.8 7.5-9.5V6L12 3Z"></path><path d="m8.7 12 2.1 2.1 4.7-4.7"></path></svg>
                <div><strong>Thanh toán an toàn</strong><span>Nhiều phương thức</span></div>
            </div>
            <div class="home-v2-benefit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5"></path><path d="M19 12a7.5 7.5 0 1 1-2.2-5.3L20 10"></path></svg>
                <div><strong>Đổi trả dễ dàng</strong><span>Trong 7 ngày</span></div>
            </div>
            <div class="home-v2-benefit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2"></path><path d="M4 13H2v5h4v-7M20 13h2v5h-4v-7M18 19c-1 1.3-2.8 2-5 2"></path></svg>
                <div><strong>Hỗ trợ 24/7</strong><span>Luôn sẵn sàng</span></div>
            </div>
        </div>
    </section>

    <section class="home-v2-section home-v2-categories" aria-labelledby="featured-categories-title">
        <div class="home-v2-shell">
            <div class="home-v2-section-head">
                <div>
                    <p class="home-v2-eyebrow">SHOP BY CATEGORY</p>
                    <h2 id="featured-categories-title">Danh mục nổi bật</h2>
                </div>
                <a href="products/index.php" class="home-v2-text-link">Xem tất cả <span>→</span></a>
            </div>

            <div class="home-v2-category-grid">
                <a class="home-v2-category-card" href="products/index.php?category=1">
                    <img src="assets/images/ao-so-mi-nu.jpg" alt="Danh mục Áo" loading="lazy">
                    <span class="home-v2-category-card__shade"></span>
                    <span class="home-v2-category-card__name">Áo</span>
                    <span class="home-v2-category-card__arrow">→</span>
                </a>
                <a class="home-v2-category-card" href="products/index.php?category=2">
                    <img src="assets/images/quan-kaki-nam.jpg" alt="Danh mục Quần" loading="lazy">
                    <span class="home-v2-category-card__shade"></span>
                    <span class="home-v2-category-card__name">Quần</span>
                    <span class="home-v2-category-card__arrow">→</span>
                </a>
                <a class="home-v2-category-card" href="products/index.php?category=3">
                    <img src="assets/images/vay-nu-thanh-lich.jpg" alt="Danh mục Váy" loading="lazy">
                    <span class="home-v2-category-card__shade"></span>
                    <span class="home-v2-category-card__name">Váy</span>
                    <span class="home-v2-category-card__arrow">→</span>
                </a>
                <a class="home-v2-category-card" href="products/index.php?sort=newest">
                    <img src="assets/images/Ablum/Effortless%20Elegance.jpg" alt="Bộ sưu tập mới" loading="lazy">
                    <span class="home-v2-category-card__shade"></span>
                    <span class="home-v2-category-card__name">Bộ sưu tập</span>
                    <span class="home-v2-category-card__arrow">→</span>
                </a>
            </div>
        </div>
    </section>

    <section class="home-v2-section home-v2-products" id="featured-products" aria-labelledby="featured-products-title">
        <div class="home-v2-shell">
            <div class="home-v2-section-head">
                <div>
                    <p class="home-v2-eyebrow">NEW ARRIVALS</p>
                    <h2 id="featured-products-title">Sản phẩm nổi bật</h2>
                    <p class="home-v2-section-description">Những lựa chọn mới nhất dành cho phong cách mỗi ngày.</p>
                </div>
                <a href="products/index.php" class="home-v2-text-link">Khám phá tất cả <span>→</span></a>
            </div>

            <?php if ($products !== []): ?>
                <div class="home-v2-product-grid">
                    <?php foreach ($products as $index => $product): ?>
                        <?php $image = homeProductImage($product['image'] ?? ''); ?>
                        <article class="home-v2-product-card">
                            <a class="home-v2-product-media" href="products/detail.php?id=<?= (int) $product['id'] ?>">
                                <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($product['name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                <span class="home-v2-product-badge"><?= $index < 3 ? 'NEW' : 'TRENDING' ?></span>
                                <span class="home-v2-product-view">Xem nhanh <b>→</b></span>
                            </a>
                            <div class="home-v2-product-info">
                                <p>FASHIONSHOP</p>
                                <h3><a href="products/detail.php?id=<?= (int) $product['id'] ?>"><?= htmlspecialchars((string) ($product['name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8') ?></a></h3>
                                <strong><?= number_format((float) ($product['price'] ?? 0), 0, ',', '.') ?>đ</strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="home-v2-empty">
                    <strong>Sản phẩm đang được cập nhật</strong>
                    <p>Hãy quay lại sau để xem bộ sưu tập mới nhất.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-v2-story">
        <div class="home-v2-shell home-v2-story__grid">
            <div class="home-v2-story__media">
                <img src="assets/images/Ablum/Men%27s%20Old%20Money%20Outfit.jpg" alt="Phong cách thời trang thanh lịch" loading="lazy">
                <span>EDITOR'S PICK · 2026</span>
            </div>
            <div class="home-v2-story__copy">
                <p class="home-v2-eyebrow">YOUR STYLE, YOUR STORY</p>
                <h2>Đẹp theo cách<br><em>của bạn.</em></h2>
                <p>FashionShop hướng đến những thiết kế dễ mặc, dễ phối và đủ tinh tế để đồng hành từ ngày thường đến những dịp đặc biệt.</p>
                <div class="home-v2-story__points">
                    <div><span>01</span><strong>Chất lượng tuyển chọn</strong><p>Ưu tiên chất liệu và trải nghiệm mặc thoải mái.</p></div>
                    <div><span>02</span><strong>Thiết kế hiện đại</strong><p>Phom dáng linh hoạt, phù hợp nhiều phong cách.</p></div>
                    <div><span>03</span><strong>Dịch vụ tận tâm</strong><p>Mua sắm đơn giản với hỗ trợ nhanh chóng.</p></div>
                </div>
                <a class="home-v2-btn home-v2-btn--primary" href="products/index.php">Mua sắm ngay <span>→</span></a>
            </div>
        </div>
    </section>

    <section class="home-v2-newsletter" aria-labelledby="newsletter-title">
        <div class="home-v2-shell home-v2-newsletter__inner">
            <div>
                <p class="home-v2-eyebrow">FASHION NOTES</p>
                <h2 id="newsletter-title">Khám phá phong cách mới mỗi tuần.</h2>
                <p>Theo dõi bộ sưu tập mới và những gợi ý phối đồ từ FashionShop.</p>
            </div>
            <a class="home-v2-btn home-v2-btn--light" href="products/index.php?sort=newest">Xem hàng mới <span>→</span></a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
