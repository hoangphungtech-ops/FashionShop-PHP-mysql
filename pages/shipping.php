<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$siteBasePath = '../';
$currentPage = 'policy';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách vận chuyển - FashionShop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="site-body">
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="main-content" class="policy-page">
        <div class="site-container policy-page__inner">
            <nav class="breadcrumb" aria-label="Đường dẫn">
                <a href="/">Trang chủ</a>
                <span aria-hidden="true">&gt;</span>
                <span aria-current="page">Chính sách vận chuyển</span>
            </nav>
            <header class="policy-page__header">
                <div class="policy-page__heading">
                    <p class="policy-page__eyebrow">HỖ TRỢ KHÁCH HÀNG</p>
                    <h1>CHÍNH SÁCH <span>VẬN CHUYỂN</span></h1>
                </div>
                <p class="policy-page__intro">FashionShop hỗ trợ giao hàng trên toàn quốc.</p>
            </header>
            <section class="policy-section" aria-labelledby="policy-section-1">
                <h2 id="policy-section-1"><span>01</span> Phạm vi giao hàng</h2>
                <div class="policy-section__content"><p>FashionShop hỗ trợ giao hàng trên toàn quốc.</p></div>
            </section>
            <section class="policy-section" aria-labelledby="policy-section-2">
                <h2 id="policy-section-2"><span>02</span> Thời gian giao hàng</h2>
                <div class="policy-section__content"><p><strong>Nội thành:</strong> Khoảng 1–3 ngày làm việc.</p><p><strong>Các khu vực khác:</strong> Khoảng 3–7 ngày làm việc.</p><p class="policy-page__note">Thời gian trên chỉ mang tính dự kiến và có thể thay đổi tùy khu vực.</p></div>
            </section>
            <section class="policy-section" aria-labelledby="policy-section-3">
                <h2 id="policy-section-3"><span>03</span> Phí vận chuyển</h2>
                <div class="policy-section__content"><p>Phí vận chuyển có thể thay đổi tùy theo khu vực, phương thức giao hàng và giá trị đơn hàng.</p></div>
            </section>
            <section class="policy-section" aria-labelledby="policy-section-4">
                <h2 id="policy-section-4"><span>04</span> Theo dõi đơn hàng</h2>
                <div class="policy-section__content"><p>Khách hàng có thể theo dõi trạng thái xử lý đơn hàng trên hệ thống FashionShop.</p></div>
            </section>
            <section class="policy-section" aria-labelledby="policy-section-5">
                <h2 id="policy-section-5"><span>05</span> Lưu ý</h2>
                <div class="policy-section__content"><p>Thời gian giao hàng có thể thay đổi do:</p><ul><li>Thời tiết.</li><li>Ngày lễ.</li><li>Số lượng đơn hàng.</li><li>Đơn vị vận chuyển.</li></ul></div>
            </section>
            <aside class="policy-page__closing" aria-labelledby="policy-shopping-title">
                <div>
                    <p class="policy-page__eyebrow">FASHIONSHOP</p>
                    <h2 id="policy-shopping-title">Khám phá phong cách của riêng bạn.</h2>
                    <p class="policy-page__note">Nội dung mang tính minh họa cho đồ án.</p>
                </div>
                <a class="policy-page__cta" href="/products/">Xem sản phẩm <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js" defer></script>
</body>
</html>
