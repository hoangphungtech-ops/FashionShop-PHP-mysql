<?php
$siteBasePath = $siteBasePath ?? '';
$currentPage = $currentPage ?? '';
$currentCategory = $currentCategory ?? 0;
if (!isset($cartCount)) {
    $cartCount = 0;
    $headerCart = $_SESSION['cart'] ?? [];

    if (is_array($headerCart)) {
        foreach ($headerCart as $headerCartValue) {
            $headerQuantityValue = is_array($headerCartValue)
                ? ($headerCartValue['quantity'] ?? 0)
                : $headerCartValue;
            $headerQuantity = filter_var($headerQuantityValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($headerQuantity !== false) {
                $cartCount += (int) $headerQuantity;
            }
        }
    }
}
?>

<a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>

<header class="site-header" data-site-header>
    <div class="site-container site-header__inner">
        <a class="brand" href="<?= $siteBasePath ?>index.php" aria-label="Fashion Shop - Trang chủ">
            <span class="brand__fashion">Fashion</span><span class="brand__shop">Shop</span>
        </a>

        <button
            class="site-header__toggle"
            type="button"
            aria-label="Mở menu"
            aria-controls="site-navigation"
            aria-expanded="false"
            data-menu-toggle
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav" id="site-navigation" aria-label="Điều hướng chính" data-site-nav>
            <a
                href="<?= $siteBasePath ?>index.php"
                class="<?= $currentPage === 'home' ? 'is-active' : '' ?>"
                <?= $currentPage === 'home' ? 'aria-current="page"' : '' ?>
            >Trang chủ</a>
            <a
                href="<?= $siteBasePath ?>products/index.php"
                class="<?= $currentPage === 'products' && $currentCategory === 0 ? 'is-active' : '' ?>"
                <?= $currentPage === 'products' && $currentCategory === 0 ? 'aria-current="page"' : '' ?>
            >Sản phẩm</a>
            <a
                href="<?= $siteBasePath ?>products/index.php?category=1"
                class="<?= $currentCategory === 1 ? 'is-active' : '' ?>"
                <?= $currentCategory === 1 ? 'aria-current="page"' : '' ?>
            >Áo</a>
            <a
                href="<?= $siteBasePath ?>products/index.php?category=2"
                class="<?= $currentCategory === 2 ? 'is-active' : '' ?>"
                <?= $currentCategory === 2 ? 'aria-current="page"' : '' ?>
            >Quần</a>
            <a
                href="<?= $siteBasePath ?>products/index.php?category=3"
                class="<?= $currentCategory === 3 ? 'is-active' : '' ?>"
                <?= $currentCategory === 3 ? 'aria-current="page"' : '' ?>
            >Váy</a>
        </nav>

        <div class="site-header__actions">
            <a class="header-action" href="<?= $siteBasePath ?>auth/profile.php" aria-label="Tài khoản">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.75"></circle>
                    <path d="M4.75 20.25c.7-4.1 3.18-6.15 7.25-6.15s6.55 2.05 7.25 6.15"></path>
                </svg>
                <span class="header-action__label">Tài khoản</span>
            </a>

            <a
                class="header-action header-action--cart <?= $currentPage === 'cart' ? 'is-active' : '' ?>"
                href="<?= $siteBasePath ?>cart/index.php"
                aria-label="Giỏ hàng, <?= (int)$cartCount ?> sản phẩm"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5.75 8.25h12.5l1 12H4.75l1-12Z"></path>
                    <path d="M8.75 9V6.75a3.25 3.25 0 0 1 6.5 0V9"></path>
                </svg>
                <span class="header-action__label">Giỏ hàng</span>
                <span class="header-action__badge"><?= (int)$cartCount ?></span>
            </a>
        </div>
    </div>
</header>
