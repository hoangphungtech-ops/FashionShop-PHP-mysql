<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/full_cart.php';

$orderId =
    (int)(
        $_GET['id']
        ?? 0
    );

?>
<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Đặt hàng thành công - FashionShop</title>

<style>

body {
    margin: 0;

    min-height: 100vh;

    display: grid;
    place-items: center;

    background: #fbfaf6;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.box {
    width: min(520px, calc(100% - 40px));

    padding: 45px;

    border: 1px solid #dce2da;

    background: #fff;

    text-align: center;
}

.box a {
    display: inline-flex;

    margin-top: 20px;

    padding: 13px 22px;

    background: #173b2e;

    color: white;

    text-decoration: none;
}

</style>

</head>

<body>

<div class="box">

    <div>✓</div>

    <h1>Đặt hàng thành công</h1>

    <p>
        Mã đơn hàng:
        <strong>
            #<?= $orderId ?>
        </strong>
    </p>

    <a href="../../products/">
        Tiếp tục mua sắm
    </a>

</div>

</body>
</html>