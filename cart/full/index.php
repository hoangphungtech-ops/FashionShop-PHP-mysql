<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/full_cart.php';

$cart = fs_cart();

$token = fs_cart_token();

?>
<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Giỏ hàng - FashionShop</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    background: #fbfaf6;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.cart-shell {
    width: min(1180px, calc(100% - 40px));

    margin: 0 auto;

    padding: 45px 0 70px;
}

.cart-top {
    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 20px;

    margin-bottom: 38px;
}

.cart-top a {
    color: #173b2e;
    text-decoration: none;
}

.cart-top h1 {
    margin: 0;

    font-size: clamp(30px, 5vw, 52px);
    font-weight: 600;

    letter-spacing: -.03em;
}

.cart-list {
    display: grid;
    gap: 1px;

    background: #dce2da;
}

.cart-item {
    display: grid;

    grid-template-columns:
        110px
        minmax(0, 1fr)
        135px
        150px;

    gap: 24px;
    align-items: center;

    padding: 24px;

    background: #fff;
}

.cart-image {
    width: 110px;
    height: 135px;

    object-fit: cover;

    background: #f1f1ed;
}

.cart-item h2 {
    margin: 0 0 9px;

    font-size: 18px;
}

.cart-meta {
    display: grid;
    gap: 5px;

    color: #6f7973;

    font-size: 13px;
}

.cart-meta strong {
    color: #173b2e;
}

.cart-price {
    font-weight: 700;
}

.cart-qty input {
    width: 70px;
    height: 38px;

    border: 1px solid #d4dbd5;

    text-align: center;
}

.cart-qty button,
.cart-remove button {
    min-height: 38px;

    border: 0;

    background: transparent;

    color: #173b2e;

    cursor: pointer;

    font-weight: 600;
}

.cart-summary {
    display: flex;
    justify-content: flex-end;

    margin-top: 30px;
}

.cart-summary-box {
    width: min(100%, 390px);

    padding: 26px;

    background: #f0f3ec;

    border: 1px solid #dce2da;
}

.cart-total {
    display: flex;
    justify-content: space-between;

    margin-bottom: 20px;

    font-size: 19px;
    font-weight: 700;
}

.cart-checkout {
    min-height: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #173b2e;

    color: #fff;

    text-decoration: none;

    font-weight: 700;
}

.cart-empty {
    padding: 70px 20px;

    border: 1px solid #dce2da;

    background: #fff;

    text-align: center;
}

@media (max-width: 780px) {

    .cart-item {
        grid-template-columns:
            85px
            1fr;

        gap: 17px;
    }

    .cart-image {
        width: 85px;
        height: 105px;
    }

    .cart-price,
    .cart-qty {
        grid-column: 2;
    }

}

</style>

</head>

<body>

<main class="cart-shell">

    <div class="cart-top">

        <div>

            <a href="../../products/">
                ← Tiếp tục mua sắm
            </a>

            <h1>
                Giỏ hàng
            </h1>

        </div>

        <strong>
            <?= fs_cart_count() ?> sản phẩm
        </strong>

    </div>

    <?php if (!$cart): ?>

        <div class="cart-empty">

            <h2>Giỏ hàng đang trống</h2>

            <a href="../../products/">
                Xem sản phẩm
            </a>

        </div>

    <?php else: ?>

        <div class="cart-list">

            <?php foreach ($cart as $item): ?>

                <article class="cart-item">

                    <img
                        class="cart-image"
                        src="<?= fs_h(
                            '../../'
                            . ltrim(
                                (string)$item['image'],
                                '/'
                            )
                        ) ?>"
                        alt="<?= fs_h($item['name']) ?>"
                    >

                    <div>

                        <h2>
                            <?= fs_h($item['name']) ?>
                        </h2>

                        <div class="cart-meta">

                            <span>
                                Size:
                                <strong>
                                    <?= fs_h($item['size']) ?>
                                </strong>
                            </span>

                            <?php if ($item['color'] !== ''): ?>

                                <span>
                                    Màu sắc:
                                    <strong>
                                        <?= fs_h($item['color']) ?>
                                    </strong>
                                </span>

                            <?php endif; ?>

                            <?php if ($item['material'] !== ''): ?>

                                <span>
                                    Chất liệu:
                                    <strong>
                                        <?= fs_h($item['material']) ?>
                                    </strong>
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="cart-price">

                        <?= number_format(
                            (float)$item['price'],
                            0,
                            ',',
                            '.'
                        ) ?>đ

                    </div>

                    <div class="cart-qty">

                        <form
                            method="post"
                            action="update.php"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= fs_h($token) ?>"
                            >

                            <input
                                type="hidden"
                                name="key"
                                value="<?= fs_h($item['key']) ?>"
                            >

                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                max="<?= (int)$item['stock'] ?>"
                                value="<?= (int)$item['quantity'] ?>"
                            >

                            <button type="submit">
                                Cập nhật
                            </button>

                        </form>

                        <form
                            method="post"
                            action="delete.php"
                            class="cart-remove"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= fs_h($token) ?>"
                            >

                            <input
                                type="hidden"
                                name="key"
                                value="<?= fs_h($item['key']) ?>"
                            >

                            <button type="submit">
                                Xóa
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

        <div class="cart-summary">

            <div class="cart-summary-box">

                <div class="cart-total">

                    <span>Tổng cộng</span>

                    <strong>
                        <?= number_format(
                            fs_cart_total(),
                            0,
                            ',',
                            '.'
                        ) ?>đ
                    </strong>

                </div>

                <a
                    class="cart-checkout"
                    href="checkout.php"
                >
                    Tiến hành đặt hàng →
                </a>

            </div>

        </div>

    <?php endif; ?>

</main>

</body>
</html>