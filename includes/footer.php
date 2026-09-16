<?php
$siteBasePath = $siteBasePath ?? '';
$footerBase = rtrim((string) $siteBasePath, '/');

$footerUrl = static function (string $path) use ($footerBase): string {
    if ($path === '') {
        return $footerBase !== '' ? $footerBase . '/' : '/';
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $path = '/' . ltrim($path, '/');

    return ($footerBase !== '' ? $footerBase : '') . $path;
};

$footerImage = $footerUrl('/assets/images/hero-fashion-nu-new.png');
?>

<style>
   
    .fs-footer-compact {
        --fc-bg: #062c23;
        --fc-bg-deep: #041f19;
        --fc-card: rgba(255,255,255,.07);
        --fc-line: rgba(255,255,255,.12);
        --fc-text: rgba(255,255,255,.78);
        --fc-soft: #9fbaa9;
        --fc-cream: #f6f0e4;
        --fc-gold: #d7c28f;
        margin-top: clamp(42px, 5vw, 70px);
        color: #fff;
        background:
            radial-gradient(circle at 78% 14%, rgba(101,148,121,.22), transparent 28%),
            radial-gradient(circle at 12% 88%, rgba(72,122,97,.16), transparent 26%),
            linear-gradient(140deg, #0a4032 0%, var(--fc-bg) 52%, var(--fc-bg-deep) 100%);
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .fs-footer-compact::before,
    .fs-footer-compact::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
        z-index: -1;
    }

    .fs-footer-compact::before {
        width: 260px;
        height: 260px;
        right: -90px;
        top: 40px;
        border: 1px solid rgba(255,255,255,.08);
        box-shadow: 0 0 0 34px rgba(255,255,255,.018), 0 0 0 68px rgba(255,255,255,.012);
    }

    .fs-footer-compact::after {
        width: 220px;
        height: 220px;
        left: -120px;
        bottom: -100px;
        background: radial-gradient(circle at 60% 38%, rgba(183,205,189,.14), transparent 66%);
    }

    .fs-footer-compact__container {
        width: min(1280px, calc(100% - 48px));
        margin-inline: auto;
    }

    /* Top editorial CTA — compact */
    .fs-footer-compact__promo {
        min-height: 238px;
        padding: 34px 0 30px;
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(310px, .65fr);
        gap: 42px;
        align-items: center;
        border-bottom: 1px solid var(--fc-line);
    }

    .fs-footer-compact__eyebrow {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 0 13px;
        color: rgba(255,255,255,.72);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .22em;
        text-transform: uppercase;
    }

    .fs-footer-compact__eyebrow::after {
        content: "";
        width: 52px;
        height: 1px;
        background: rgba(255,255,255,.34);
    }

    .fs-footer-compact__title {
        max-width: 760px;
        margin: 0;
        color: #fffdf9;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(34px, 3.55vw, 50px);
        font-weight: 700;
        line-height: 1.07;
        letter-spacing: -.035em;
    }

    .fs-footer-compact__title em {
        color: #ead7a7;
        font-weight: 500;
        font-style: italic;
    }

    .fs-footer-compact__lead {
        max-width: 700px;
        margin: 12px 0 0;
        color: rgba(255,255,255,.68);
        font-size: 15px;
        line-height: 1.65;
    }

    .fs-footer-compact__perks {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-top: 20px;
    }

    .fs-footer-compact__perk {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: rgba(255,255,255,.78);
        font-size: 13px;
        white-space: nowrap;
    }

    .fs-footer-compact__perk + .fs-footer-compact__perk::before {
        content: "";
        width: 1px;
        height: 17px;
        margin-right: 10px;
        background: rgba(255,255,255,.18);
    }

    .fs-footer-compact__perk svg {
        width: 19px;
        height: 19px;
        fill: none;
        stroke: #ead7a7;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .fs-footer-compact__editorial {
        position: relative;
        height: 178px;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 18px 36px rgba(0,0,0,.18);
        background: #173b30;
    }

    .fs-footer-compact__editorial img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        object-position: center 32%;
        opacity: .76;
        filter: saturate(.75) contrast(1.03);
    }

    .fs-footer-compact__editorial::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(3,30,23,.76) 0%, rgba(3,30,23,.18) 68%, rgba(3,30,23,.1) 100%);
    }

    .fs-footer-compact__editorial-copy {
        position: absolute;
        inset: 0;
        z-index: 2;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-end;
    }

    .fs-footer-compact__editorial-label {
        margin: 0 0 5px;
        color: #ead7a7;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 20px;
        font-style: italic;
    }

    .fs-footer-compact__cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 44px;
        padding: 0 18px;
        border-radius: 999px;
        background: rgba(255,253,248,.96);
        color: #0a3428;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 8px 22px rgba(0,0,0,.14);
        transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
    }

    .fs-footer-compact__cta:hover {
        transform: translateY(-2px);
        background: #fff;
        box-shadow: 0 12px 28px rgba(0,0,0,.19);
    }

    /* Main footer — compact 3 columns */
    .fs-footer-compact__main {
        padding: 32px 0 26px;
        display: grid;
        grid-template-columns: 1.25fr .85fr .85fr;
        gap: 0;
    }

    .fs-footer-compact__column {
        min-width: 0;
        padding: 0 34px;
    }

    .fs-footer-compact__column:first-child {
        padding-left: 0;
    }

    .fs-footer-compact__column + .fs-footer-compact__column {
        border-left: 1px solid rgba(255,255,255,.11);
    }

    .fs-footer-compact__brand {
        display: inline-flex;
        align-items: baseline;
        margin-bottom: 13px;
        color: #fff;
        font-size: clamp(28px, 2.3vw, 36px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -.055em;
        text-decoration: none;
    }

    .fs-footer-compact__brand span:last-child {
        color: var(--fc-soft);
    }

    .fs-footer-compact__about {
        max-width: 340px;
        margin: 0;
        color: rgba(255,255,255,.67);
        font-size: 13px;
        line-height: 1.7;
    }

    .fs-footer-compact__socials {
        display: flex;
        gap: 9px;
        margin-top: 18px;
    }

    .fs-footer-compact__social {
        width: 35px;
        height: 35px;
        display: inline-grid;
        place-items: center;
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 50%;
        color: #fff;
        text-decoration: none;
        transition: transform .2s ease, background .2s ease, border-color .2s ease;
    }

    .fs-footer-compact__social:hover {
        transform: translateY(-2px);
        background: rgba(255,255,255,.08);
        border-color: rgba(255,255,255,.55);
    }

    .fs-footer-compact__social svg {
        width: 15px;
        height: 15px;
        fill: currentColor;
    }

    .fs-footer-compact__heading {
        margin: 0 0 16px;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .19em;
        text-transform: uppercase;
    }

    .fs-footer-compact__heading::after {
        content: "";
        display: block;
        width: 34px;
        height: 1px;
        margin-top: 9px;
        background: rgba(255,255,255,.52);
    }

    .fs-footer-compact__nav {
        display: grid;
        gap: 6px;
    }

    .fs-footer-compact__link {
        min-height: 31px;
        display: grid;
        grid-template-columns: 23px 1fr auto;
        align-items: center;
        gap: 8px;
        color: rgba(255,255,255,.75);
        text-decoration: none;
        font-size: 13px;
        transition: color .18s ease, transform .18s ease;
    }

    .fs-footer-compact__link:hover {
        color: #fff;
        transform: translateX(2px);
    }

    .fs-footer-compact__link svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.55;
        stroke-linecap: round;
        stroke-linejoin: round;
        opacity: .9;
    }

    .fs-footer-compact__arrow {
        color: rgba(255,255,255,.46);
        font-size: 18px;
    }

    .fs-footer-compact__bottom {
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 14px 0 18px;
        border-top: 1px solid var(--fc-line);
    }

    .fs-footer-compact__copyright,
    .fs-footer-compact__signature {
        margin: 0;
        font-size: 11px;
    }

    .fs-footer-compact__copyright {
        color: rgba(255,255,255,.52);
    }

    .fs-footer-compact__signature {
        color: rgba(215,194,143,.73);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 15px;
        font-style: italic;
    }

    .fs-footer-compact__sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0,0,0,0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* =========================================================
       Responsive parity: giữ cùng ngôn ngữ/bố cục desktop trên mobile,
       chỉ co tỷ lệ và khoảng cách thay vì đổi sang giao diện khác hẳn.
       ========================================================= */
    @media (max-width: 960px) {
        .fs-footer-compact__container {
            width: min(100% - 28px, 1280px);
        }

        .fs-footer-compact__promo {
            min-height: 210px;
            padding: 28px 0 26px;
            grid-template-columns: minmax(0, 1.35fr) minmax(230px, .65fr);
            gap: 24px;
        }

        .fs-footer-compact__title {
            font-size: clamp(30px, 5vw, 42px);
        }

        .fs-footer-compact__lead {
            font-size: 14px;
        }

        .fs-footer-compact__editorial {
            height: 158px;
            border-radius: 20px;
        }

        .fs-footer-compact__main {
            grid-template-columns: 1.18fr .9fr .9fr;
            padding: 28px 0 24px;
        }

        .fs-footer-compact__column {
            padding: 0 20px;
        }
    }

    @media (max-width: 680px) {
        .fs-footer-compact {
            margin-top: 36px;
        }

        .fs-footer-compact__container {
            width: calc(100% - 22px);
        }

        /* Vẫn giữ 2 cột giống desktop */
        .fs-footer-compact__promo {
            min-height: 0;
            padding: 22px 0 20px;
            grid-template-columns: minmax(0, 1.28fr) minmax(118px, .72fr);
            gap: 12px;
            align-items: stretch;
        }

        .fs-footer-compact__eyebrow {
            margin-bottom: 8px;
            gap: 8px;
            font-size: 8px;
            letter-spacing: .18em;
        }

        .fs-footer-compact__eyebrow::after {
            width: 28px;
        }

        .fs-footer-compact__title {
            max-width: none;
            font-size: clamp(23px, 7.4vw, 31px);
            line-height: 1.06;
            letter-spacing: -.03em;
        }

        .fs-footer-compact__lead {
            margin-top: 8px;
            font-size: 10px;
            line-height: 1.5;
        }

        .fs-footer-compact__perks {
            display: flex;
            flex-wrap: wrap;
            gap: 5px 8px;
            margin-top: 12px;
        }

        .fs-footer-compact__perk {
            gap: 4px;
            font-size: 8px;
        }

        .fs-footer-compact__perk + .fs-footer-compact__perk::before {
            display: none;
        }

        .fs-footer-compact__perk svg {
            width: 13px;
            height: 13px;
        }

        .fs-footer-compact__editorial {
            width: 100%;
            height: 100%;
            min-height: 138px;
            border-radius: 16px;
        }

        .fs-footer-compact__editorial img {
            object-position: center 28%;
        }

        .fs-footer-compact__editorial-copy {
            padding: 10px;
        }

        .fs-footer-compact__editorial-label {
            margin-bottom: 4px;
            font-size: 12px;
        }

        .fs-footer-compact__cta {
            min-height: 32px;
            padding: 0 10px;
            gap: 5px;
            font-size: 9px;
        }

        /* Giữ 3 cột giống desktop thay vì xếp dọc */
        .fs-footer-compact__main {
            grid-template-columns: 1.12fr .88fr .88fr;
            padding: 20px 0 16px;
        }

        .fs-footer-compact__column,
        .fs-footer-compact__column:first-child,
        .fs-footer-compact__column:nth-child(3) {
            min-width: 0;
            margin-top: 0;
            padding: 0 9px;
            border-top: 0;
        }

        .fs-footer-compact__column:first-child {
            padding-left: 0;
        }

        .fs-footer-compact__column + .fs-footer-compact__column,
        .fs-footer-compact__column:nth-child(3) {
            border-left: 1px solid rgba(255,255,255,.1);
        }

        .fs-footer-compact__brand {
            margin-bottom: 8px;
            font-size: clamp(18px, 5.6vw, 24px);
        }

        .fs-footer-compact__about {
            font-size: 8px;
            line-height: 1.55;
        }

        .fs-footer-compact__socials {
            gap: 5px;
            margin-top: 10px;
        }

        .fs-footer-compact__social {
            width: 25px;
            height: 25px;
        }

        .fs-footer-compact__social svg {
            width: 11px;
            height: 11px;
        }

        .fs-footer-compact__heading {
            margin-bottom: 9px;
            font-size: 8px;
            letter-spacing: .12em;
        }

        .fs-footer-compact__heading::after {
            width: 22px;
            margin-top: 5px;
        }

        .fs-footer-compact__nav {
            gap: 2px;
        }

        .fs-footer-compact__link {
            min-height: 25px;
            grid-template-columns: 15px 1fr auto;
            gap: 4px;
            font-size: 8px;
        }

        .fs-footer-compact__link svg {
            width: 12px;
            height: 12px;
        }

        .fs-footer-compact__arrow {
            font-size: 12px;
        }

        .fs-footer-compact__bottom {
            min-height: 44px;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 0 12px;
        }

        .fs-footer-compact__copyright {
            font-size: 7px;
        }

        .fs-footer-compact__signature {
            font-size: 9px;
            text-align: right;
        }
    }

    @media (max-width: 420px) {
        .fs-footer-compact__promo {
            grid-template-columns: minmax(0, 1.32fr) minmax(105px, .68fr);
        }

        .fs-footer-compact__title {
            font-size: clamp(21px, 7.1vw, 27px);
        }

        .fs-footer-compact__lead {
            font-size: 9px;
        }

        .fs-footer-compact__editorial {
            min-height: 126px;
        }

        .fs-footer-compact__main {
            grid-template-columns: 1.08fr .92fr .92fr;
        }

        .fs-footer-compact__column,
        .fs-footer-compact__column:first-child,
        .fs-footer-compact__column:nth-child(3) {
            padding-right: 6px;
            padding-left: 6px;
        }

        .fs-footer-compact__column:first-child {
            padding-left: 0;
        }

        .fs-footer-compact__about,
        .fs-footer-compact__link {
            font-size: 7.5px;
        }
    }

    .floating-zalo { z-index: 9999; }
</style>

<footer class="fs-footer-compact">
    <section class="fs-footer-compact__container fs-footer-compact__promo" aria-label="Gợi ý phong cách">
        <div>
            <p class="fs-footer-compact__eyebrow">Fashion Notes</p>
            <h2 class="fs-footer-compact__title">
                Khám phá phong cách <em>mới mỗi tuần.</em>
            </h2>
            <p class="fs-footer-compact__lead">
                Theo dõi bộ sưu tập mới và những gợi ý phối đồ từ FashionShop.
            </p>

            <div class="fs-footer-compact__perks" aria-label="Điểm nổi bật FashionShop">
                <span class="fs-footer-compact__perk">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4c-7 1-12 5-13 12 3 1 7 0 10-3 3-3 4-6 3-9Z"></path><path d="M5 20c2-5 5-8 10-11"></path></svg>
                    Xu hướng mới
                </span>
                <span class="fs-footer-compact__perk">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></svg>
                    Phong cách riêng
                </span>
                <span class="fs-footer-compact__perk">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"></path></svg>
                    Cảm hứng mỗi tuần
                </span>
            </div>
        </div>

        <div class="fs-footer-compact__editorial">
            <img src="<?= htmlspecialchars($footerImage, ENT_QUOTES, 'UTF-8') ?>" alt="Bộ sưu tập thời trang FashionShop">
            <div class="fs-footer-compact__editorial-copy">
                <p class="fs-footer-compact__editorial-label">Be Your Own Style</p>
                <a class="fs-footer-compact__cta" href="<?= htmlspecialchars($footerUrl('/products/'), ENT_QUOTES, 'UTF-8') ?>">
                    Xem hàng mới <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    <section class="fs-footer-compact__container fs-footer-compact__main">
        <div class="fs-footer-compact__column">
            <a class="fs-footer-compact__brand" href="<?= htmlspecialchars($footerUrl('/'), ENT_QUOTES, 'UTF-8') ?>" aria-label="FashionShop - Trang chủ">
                <span>Fashion</span><span>Shop</span>
            </a>
            <p class="fs-footer-compact__about">
                Thời trang trẻ trung, hiện đại và phù hợp với phong cách riêng của bạn.
            </p>

            <div class="fs-footer-compact__socials" aria-label="Mạng xã hội FashionShop">
                <a class="fs-footer-compact__social" href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.6 22v-9h3l.5-3h-3.5V8.1c0-.9.3-1.6 1.7-1.6H17V3.8c-.3 0-1.5-.1-2.8-.1-2.8 0-4.7 1.7-4.7 4.9V10H6.4v3h3.1v9h4.1Z"></path></svg>
                </a>
                <a class="fs-footer-compact__social" href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 2h9.6A5.2 5.2 0 0 1 22 7.2v9.6a5.2 5.2 0 0 1-5.2 5.2H7.2A5.2 5.2 0 0 1 2 16.8V7.2A5.2 5.2 0 0 1 7.2 2Zm0 2A3.2 3.2 0 0 0 4 7.2v9.6A3.2 3.2 0 0 0 7.2 20h9.6a3.2 3.2 0 0 0 3.2-3.2V7.2A3.2 3.2 0 0 0 16.8 4H7.2Zm10.1 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"></path></svg>
                </a>
                <a class="fs-footer-compact__social" href="#" aria-label="TikTok">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h3.1c.3 1.7 1.3 3 2.9 3.8V10a8.7 8.7 0 0 1-3-1v6.3A5.7 5.7 0 1 1 11.3 9c.4 0 .8 0 1.2.1v3.1a2.7 2.7 0 1 0 1.5 2.4V3Z"></path></svg>
                </a>
                <a class="fs-footer-compact__social" href="#" aria-label="YouTube">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12s0-3.5-.4-5.1A2.7 2.7 0 0 0 19.7 5C18 4.6 12 4.6 12 4.6s-6 0-7.7.4a2.7 2.7 0 0 0-1.9 1.9C2 8.5 2 12 2 12s0 3.5.4 5.1A2.7 2.7 0 0 0 4.3 19c1.7.4 7.7.4 7.7.4s6 0 7.7-.4a2.7 2.7 0 0 0 1.9-1.9C22 15.5 22 12 22 12Zm-12 3.4V8.6l6 3.4-6 3.4Z"></path></svg>
                </a>
            </div>
        </div>

        <nav class="fs-footer-compact__column" aria-label="Danh mục sản phẩm">
            <h2 class="fs-footer-compact__heading">Danh mục</h2>
            <div class="fs-footer-compact__nav">
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/products/'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="14" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect></svg>
                    <span>Tất cả sản phẩm</span><span class="fs-footer-compact__arrow">›</span>
                </a>
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/products/?category=1'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 4 4-2 4 2 4 5-3 2v10H7V11L4 9l4-5Z"></path></svg>
                    <span>Áo</span><span class="fs-footer-compact__arrow">›</span>
                </a>
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/products/?category=2'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l1 18h-5l-1-10-1 10H5L8 3Z"></path></svg>
                    <span>Quần</span><span class="fs-footer-compact__arrow">›</span>
                </a>
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/products/?category=3'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 3h4l1 5 4 11H5L9 8l1-5Z"></path></svg>
                    <span>Váy</span><span class="fs-footer-compact__arrow">›</span>
                </a>
            </div>
        </nav>

        <nav class="fs-footer-compact__column" aria-label="Hỗ trợ khách hàng">
            <h2 class="fs-footer-compact__heading">Hỗ trợ</h2>
            <div class="fs-footer-compact__nav">
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/pages/return-policy.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l4 4v16H6V2Z"></path><path d="M14 2v5h5"></path><path d="M9 12h6M9 16h6"></path></svg>
                    <span>Chính sách đổi trả</span><span class="fs-footer-compact__arrow">›</span>
                </a>
                <a class="fs-footer-compact__link" href="<?= htmlspecialchars($footerUrl('/pages/shipping.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v11H3V6Z"></path><path d="M14 10h4l3 3v4h-7v-7Z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle></svg>
                    <span>Vận chuyển</span><span class="fs-footer-compact__arrow">›</span>
                </a>
                <a class="fs-footer-compact__link" href="https://zalo.me/g/juaxctm7cvtayvvaq3g9" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2"></path><path d="M4 13H2v4h4v-4H4Zm16 0h2v4h-4v-4h2ZM19 18c-1 2-3 3-6 3"></path></svg>
                    <span>Liên hệ<span class="fs-footer-compact__sr-only"> qua Zalo, mở tab mới</span></span><span class="fs-footer-compact__arrow">›</span>
                </a>
            </div>
        </nav>
    </section>

    <div class="fs-footer-compact__container fs-footer-compact__bottom">
        <p class="fs-footer-compact__copyright">© 2026 Fashion Shop. All rights reserved.</p>
        <p class="fs-footer-compact__signature">More Style. A Brighter You.</p>
    </div>
</footer>

<button class="back-to-top" type="button" aria-label="Lên đầu trang" data-back-to-top>
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.5 14.5 5.5-5 5.5 5"></path></svg>
</button>

<!-- === FLOATING ZALO START === -->
<a
    href="https://zalo.me/g/juaxctm7cvtayvvaq3g9"
    class="floating-zalo"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Liên hệ qua Zalo"
    title="Chat qua Zalo"
>
    <span class="floating-zalo__icon" aria-hidden="true">
        <svg viewBox="0 0 64 64" role="img">
            <rect x="5" y="9" width="54" height="42" rx="14" fill="#0068ff" />
            <path d="M18 50 L14 58 L29 51" fill="#0068ff" />
            <text x="32" y="36" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="17" font-weight="700">Zalo</text>
        </svg>
    </span>
    <span class="floating-zalo__label">Chat Zalo</span>
</a>