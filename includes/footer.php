<?php $siteBasePath = $siteBasePath ?? ''; ?>

<footer class="site-footer">
    <div class="site-container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a class="brand brand--footer" href="<?= $siteBasePath ?>index.php">
                    <span class="brand__fashion">Fashion</span><span class="brand__shop">Shop</span>
                </a>
                <p>Thời trang hiện đại được tuyển chọn cho phong cách riêng của bạn.</p>
            </div>

            <div class="site-footer__column">
                <h2>Khám phá</h2>
                <a href="<?= $siteBasePath ?>index.php">Trang chủ</a>
                <a href="<?= $siteBasePath ?>products/index.php">Tất cả sản phẩm</a>
                <a href="<?= $siteBasePath ?>cart/index.php">Giỏ hàng</a>
            </div>

            <div class="site-footer__column">
                <h2>Danh mục</h2>
                <a href="<?= $siteBasePath ?>products/index.php?category=1">Áo</a>
                <a href="<?= $siteBasePath ?>products/index.php?category=2">Quần</a>
                <a href="<?= $siteBasePath ?>products/index.php?category=3">Váy</a>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>© 2026 Fashion Shop. All rights reserved.</p>
            <p>Designed for everyday confidence.</p>
        </div>
    </div>
</footer>

<button class="back-to-top" type="button" aria-label="Lên đầu trang" data-back-to-top>
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m6.5 14.5 5.5-5 5.5 5"></path>
    </svg>
</button>
