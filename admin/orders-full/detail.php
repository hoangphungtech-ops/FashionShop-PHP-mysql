<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (function_exists('require_admin')) {
    require_admin();
}

$id =
    (int)(
        $_GET['id']
        ?? 0
    );

$stmt = $pdo->prepare(
    'SELECT *
     FROM fs_orders
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    ':id' => $id,
]);

$order =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );

if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM fs_order_items
     WHERE order_id = :id
     ORDER BY id'
);

$stmt->execute([
    ':id' => $id,
]);

$items =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
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

<title>
    Đơn hàng #<?= $id ?>
</title>

<style>

body {
    margin: 0;

    background: #f7f8f5;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.wrap {
    width: min(1100px, calc(100% - 40px));

    margin: 45px auto;
}

.grid {
    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 22px;
}

.card {
    padding: 25px;

    border: 1px solid #dce2da;

    background: white;
}

.row {
    display: grid;

    grid-template-columns:
        135px
        1fr;

    gap: 15px;

    padding: 8px 0;
}

.items {
    margin-top: 25px;
}

.item {
    display: grid;

    grid-template-columns:
        85px
        minmax(0, 1fr)
        100px
        70px
        130px;

    gap: 18px;
    align-items: center;

    padding: 18px;

    border-bottom: 1px solid #e0e4df;

    background: white;
}

.item img {
    width: 85px;
    height: 100px;

    object-fit: cover;
}

.total {
    margin-top: 20px;

    text-align: right;

    font-size: 22px;
    font-weight: 700;
}

.status-form {
    margin-top: 20px;

    display: flex;
    gap: 10px;
}

select,
button {
    min-height: 44px;

    padding: 0 13px;
}

button {
    border: 0;

    background: #173b2e;

    color: white;
}

@media (max-width: 760px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .item {
        grid-template-columns:
            70px
            1fr;
    }

}

</style>

</head>

<body>

<main class="wrap">

    <a href="index.php">
        ← Danh sách đơn hàng
    </a>

    <h1>
        Đơn hàng #<?= $id ?>
    </h1>

    <div class="grid">

        <section class="card">

            <h2>
                Thông tin khách hàng
            </h2>

            <div class="row">
                <strong>Họ tên</strong>
                <span><?= htmlspecialchars($order['receiver_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="row">
                <strong>Số điện thoại</strong>
                <span><?= htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="row">
                <strong>Email</strong>
                <span><?= htmlspecialchars((string)$order['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="row">
                <strong>Địa chỉ</strong>

                <span>
                    <?= htmlspecialchars(
                        $order['address_line']
                        . ', '
                        . $order['ward']
                        . ', '
                        . $order['district']
                        . ', '
                        . $order['province'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="row">
                <strong>Ghi chú</strong>
                <span><?= nl2br(htmlspecialchars((string)$order['note'], ENT_QUOTES, 'UTF-8')) ?></span>
            </div>

        </section>

        <section class="card">

            <h2>
                Thông tin đơn hàng
            </h2>

            <div class="row">
                <strong>Ngày đặt</strong>
                <span><?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="row">
                <strong>Trạng thái</strong>
                <span><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <form
                method="post"
                action="status.php"
                class="status-form"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $id ?>"
                >

                <select name="status">

                    <option value="pending">
                        Chờ xác nhận
                    </option>

                    <option value="confirmed">
                        Đã xác nhận
                    </option>

                    <option value="preparing">
                        Đang chuẩn bị hàng
                    </option>

                    <option value="shipping">
                        Đang giao hàng
                    </option>

                    <option value="delivered">
                        Đã giao
                    </option>

                    <option value="cancelled">
                        Đã hủy
                    </option>

                </select>

                <button type="submit">
                    Cập nhật
                </button>

            </form>

        </section>

    </div>

    <section class="items">

        <h2>Sản phẩm</h2>

        <?php foreach ($items as $item): ?>

            <article class="item">

                <img
                    src="<?= htmlspecialchars(
                        '../../'
                        . ltrim(
                            (string)$item['product_image'],
                            '/'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt=""
                >

                <div>

                    <strong>
                        <?= htmlspecialchars(
                            $item['product_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <div>
                        Size:
                        <?= htmlspecialchars(
                            (string)$item['size'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div>
                        Màu:
                        <?= htmlspecialchars(
                            (string)$item['color'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div>
                        Chất liệu:
                        <?= htmlspecialchars(
                            (string)$item['material'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

                <div>
                    <?= number_format(
                        (float)$item['unit_price'],
                        0,
                        ',',
                        '.'
                    ) ?>đ
                </div>

                <div>
                    × <?= (int)$item['quantity'] ?>
                </div>

                <strong>
                    <?= number_format(
                        (float)$item['line_total'],
                        0,
                        ',',
                        '.'
                    ) ?>đ
                </strong>

            </article>

        <?php endforeach; ?>

        <div class="total">

            Tổng cộng:
            <?= number_format(
                (float)$order['total_amount'],
                0,
                ',',
                '.'
            ) ?>đ

        </div>

    </section>

</main>

</body>
</html>