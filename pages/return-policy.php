<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Chính sách đổi trả - FashionShop</title>


<style>

:root {
    --policy-primary: #173b2e;
    --policy-secondary: #73836f;
    --policy-accent: #9a8058;
    --policy-bg: #fbfaf6;
    --policy-soft: #f0f3ec;
    --policy-border: #dce2da;
    --policy-danger: #9b554a;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    background: var(--policy-bg);

    color: var(--policy-primary);

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.policy-shell {
    width: min(1060px, calc(100% - 40px));

    margin: 0 auto;

    padding: 55px 0 80px;
}

.policy-back {
    color: var(--policy-secondary);

    text-decoration: none;

    font-size: 13px;
}

.policy-intro {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(280px, .8fr);

    gap: 60px;

    align-items: end;

    padding: 45px 0 35px;

    border-bottom:
        1px solid
        var(--policy-border);
}

.policy-kicker {
    margin-bottom: 13px;

    color: var(--policy-secondary);

    font-size: 10px;
    font-weight: 800;

    letter-spacing: .22em;
}

.policy-title {
    margin: 0;

    font-size: clamp(38px, 6vw, 70px);

    line-height: .98;

    letter-spacing: -.045em;

    font-weight: 650;
}

.policy-title span {
    display: block;

    color: var(--policy-secondary);
}

.policy-lead {
    max-width: 500px;

    color: #57665d;

    line-height: 1.8;
}

.policy-row {
    display: grid;

    grid-template-columns:
        48px
        250px
        minmax(0, 1fr);

    gap: 28px;

    padding: 28px 0;

    border-bottom:
        1px solid
        var(--policy-border);
}

.policy-no {
    color: var(--policy-secondary);

    font-size: 11px;
}

.policy-heading {
    font-weight: 750;
}

.policy-content {
    color: #48584f;

    line-height: 1.75;
}

.policy-content strong {
    color: var(--policy-primary);
}

.policy-accent {
    color: var(--policy-accent);

    font-weight: 750;
}

.policy-danger {
    color: var(--policy-danger);
}

.policy-list {
    margin: 9px 0 0;

    padding-left: 19px;
}

.policy-list li {
    margin: 7px 0;
}

.policy-steps {
    display: grid;

    gap: 11px;
}

.policy-step {
    display: grid;

    grid-template-columns:
        85px
        1fr;

    gap: 20px;

    padding: 13px 0;

    border-bottom:
        1px dashed
        var(--policy-border);
}

.policy-cta {
    display: grid;

    grid-template-columns:
        1fr
        auto;

    gap: 30px;

    align-items: center;

    margin-top: 40px;

    padding: 30px;

    background:
        var(--policy-soft);
}

.policy-cta small {
    display: block;

    margin-bottom: 8px;

    color: var(--policy-secondary);

    font-size: 9px;
    font-weight: 800;

    letter-spacing: .22em;
}

.policy-cta h2 {
    margin: 0 0 6px;
}

.policy-cta p {
    margin: 0;

    color: #788078;

    font-size: 13px;
}

.policy-button {
    min-width: 160px;
    min-height: 48px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    background:
        var(--policy-primary);

    color: #fff;

    text-decoration: none;

    font-weight: 700;

    transition:
        background .2s ease;
}

.policy-button:hover {
    background: #244d3c;
}

@media (max-width: 760px) {

    .policy-shell {
        width: min(
            100% - 40px,
            1060px
        );
    }

    .policy-intro {
        grid-template-columns: 1fr;

        gap: 25px;
    }

    .policy-row {
        grid-template-columns:
            36px
            1fr;
    }

    .policy-content {
        grid-column: 2;
    }

    .policy-cta {
        grid-template-columns: 1fr;
    }

}

</style>


</head>

<body>

<main class="policy-shell">

<a
    href="../"
    class="policy-back"
>
    Trang chủ / Chính sách đổi trả
</a>

<header class="policy-intro">

    <div>

        <div class="policy-kicker">
            HỖ TRỢ KHÁCH HÀNG
        </div>

        <h1 class="policy-title">
            CHÍNH SÁCH
            <span>ĐỔI TRẢ</span>
        </h1>

    </div>

    <p class="policy-lead">
        FashionShop mong muốn mỗi sản phẩm
        khi đến tay khách hàng đều đúng với lựa chọn
        về mẫu mã, kích cỡ và màu sắc.
    </p>

</header>

<section class="policy-row">

    <div class="policy-no">01</div>

    <div class="policy-heading">
        Thời hạn đổi trả
    </div>

    <div class="policy-content">

        FashionShop hỗ trợ yêu cầu đổi sản phẩm
        trong vòng

        <span class="policy-accent">
            07 ngày
        </span>

        kể từ ngày khách hàng nhận hàng.

    </div>

</section>

<section class="policy-row">

    <div class="policy-no">02</div>

    <div class="policy-heading">
        Điều kiện đổi trả
    </div>

    <div class="policy-content">

        Sản phẩm cần đáp ứng:

        <ul class="policy-list">
            <li>✓ Chưa qua sử dụng.</li>
            <li>✓ Không có dấu hiệu giặt hoặc làm bẩn.</li>
            <li>✓ Còn nguyên tem/nhãn sản phẩm.</li>
            <li>✓ Không bị hư hỏng do người sử dụng.</li>
            <li>✓ Có thông tin đơn hàng để đối chiếu.</li>
        </ul>

    </div>

</section>

<section class="policy-row">

    <div class="policy-no">03</div>

    <div class="policy-heading">
        Trường hợp được hỗ trợ
    </div>

    <div class="policy-content">

        <ul class="policy-list">

            <li>
                ✓ Giao sai sản phẩm.
            </li>

            <li>
                ✓ Giao
                <span class="policy-accent">
                    sai size
                </span>
                so với đơn đặt hàng.
            </li>

            <li>
                ✓ Giao
                <span class="policy-accent">
                    sai màu.
                </span>
            </li>

            <li>
                ✓ Sản phẩm có
                <span class="policy-accent">
                    lỗi từ FashionShop.
                </span>
            </li>

        </ul>

    </div>

</section>

<section class="policy-row">

    <div class="policy-no">04</div>

    <div class="policy-heading">
        Trường hợp không hỗ trợ
    </div>

    <div class="policy-content">

        <ul class="policy-list">

            <li class="policy-danger">
                ✕ Sản phẩm đã qua sử dụng.
            </li>

            <li>
                ✕ Khách chọn nhầm size nhưng
                sản phẩm không còn size thay thế.
            </li>

            <li>
                ✕ Sản phẩm bị hư hỏng
                do bảo quản không đúng cách.
            </li>

            <li>
                ✕ Quá thời hạn đổi trả.
            </li>

        </ul>

    </div>

</section>

<section class="policy-row">

    <div class="policy-no">05</div>

    <div class="policy-heading">
        Quy trình đổi trả
    </div>

    <div class="policy-content">

        <div class="policy-steps">

            <div class="policy-step">
                <strong>BƯỚC 1</strong>
                <span>Liên hệ FashionShop.</span>
            </div>

            <div class="policy-step">
                <strong>BƯỚC 2</strong>
                <span>
                    Cung cấp mã đơn hàng
                    và thông tin sản phẩm.
                </span>
            </div>

            <div class="policy-step">
                <strong>BƯỚC 3</strong>
                <span>
                    FashionShop xác nhận yêu cầu.
                </span>
            </div>

            <div class="policy-step">
                <strong>BƯỚC 4</strong>
                <span>
                    Khách gửi sản phẩm theo hướng dẫn.
                </span>
            </div>

            <div class="policy-step">
                <strong>BƯỚC 5</strong>
                <span>
                    FashionShop kiểm tra và xử lý đổi sản phẩm.
                </span>
            </div>

        </div>

    </div>

</section>

<section class="policy-cta">

    <div>

        <small>FASHIONSHOP</small>

        <h2>
            Khám phá phong cách của riêng bạn.
        </h2>

        <p>
            Nơi từng trang phục minh họa cho dấu ấn.
        </p>

    </div>

    <a
        class="policy-button"
        href="../products/"
    >
        Xem sản phẩm →
    </a>

</section>

</main>

</body>
</html>