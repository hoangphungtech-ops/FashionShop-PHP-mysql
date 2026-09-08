<?php $siteBasePath = $siteBasePath ?? ''; ?>

<footer class="site-footer">
    <div class="site-container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a class="brand brand--footer" href="/" aria-label="FashionShop - Trang chủ">
                    <span class="brand__fashion">Fashion</span><span class="brand__shop">Shop</span>
                </a>
                <p>Thời trang trẻ trung, hiện đại và phù hợp với phong cách riêng của bạn.</p>
            </div>
            <nav class="site-footer__column" aria-label="Danh mục sản phẩm">
                <h2>Danh mục</h2>
                <a href="/products/">Tất cả sản phẩm</a>
                <a href="/products/?category=1">Áo</a>
                <a href="/products/?category=2">Quần</a>
                <a href="/products/?category=3">Váy</a>
            </nav>
            <nav class="site-footer__column" aria-label="Hỗ trợ khách hàng">
                <h2>Hỗ trợ</h2>
                <a href="/pages/return-policy.php">Chính sách đổi trả</a>
                <a href="/pages/shipping.php">Vận chuyển</a>
                <a href="https://zalo.me/g/juaxctm7cvtayvvaq3g9" target="_blank" rel="noopener noreferrer">Liên hệ<span class="site-footer__sr-only"> (mở Zalo trong tab mới)</span></a>
            </nav>
        </div>
        <div class="site-footer__bottom">
            <p>© 2026 Fashion Shop</p>
        </div>
    </div>
</footer>

<button class="back-to-top" type="button" aria-label="Lên đầu trang" data-back-to-top>
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m6.5 14.5 5.5-5 5.5 5"></path>
    </svg>
</button>
