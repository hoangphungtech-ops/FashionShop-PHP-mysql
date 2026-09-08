<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (function_exists('require_admin')) {
    require_admin();
}

$orders = $pdo
    ->query(
        'SELECT *
         FROM fs_orders
         ORDER BY id DESC'
    )
    ->fetchAll(
        PDO::FETCH_ASSOC
    );

$statusLabels = [

    'pending' =>
        'Chờ xác nhận',

    'confirmed' =>
        'Đã xác nhận',

    'preparing' =>
        'Đang chuẩn bị hàng',

    'shipping' =>
        'Đang giao hàng',

    'delivered' =>
        'Đã giao',

    'cancelled' =>
        'Đã hủy',
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Đơn hàng - Admin</title>

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
    width: min(1200px, calc(100% - 40px));

    margin: 45px auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    background: white;
}

th,
td {
    padding: 15px;

    border-bottom: 1px solid #e0e4df;

    text-align: left;
}

th {
    background: #f0f3ec;

    font-size: 12px;
    text-transform: uppercase;
}

a {
    color: #173b2e;
}

</style>

</head>

<body>

<main class="wrap">

    <a href="../">
        ← Dashboard
    </a>

    <h1>
        Đơn hàng đầy đủ
    </h1>

    <table>

        <thead>

            <tr>
                <th>Mã</th>
                <th>Khách hàng</th>
                <th>SĐT</th>
                <th>Địa chỉ</th>
                <th>Tổng</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
                <th></th>
            </tr>

        </thead>

        <tbody>

        <?php foreach ($orders as $order): ?>

            <tr>

                <td>
                    #<?= (int)$order['id'] ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['receiver_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['phone'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td>
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
                </td>

                <td>
                    <?= number_format(
                        (float)$order['total_amount'],
                        0,
                        ',',
                        '.'
                    ) ?>đ
                </td>

                <td>
                    <?= htmlspecialchars(
                        $statusLabels[
                            $order['status']
                        ]
                        ?? $order['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['created_at'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td>

                    <a href="detail.php?id=<?= (int)$order['id'] ?>">
                        Chi tiết
                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</main>

</body>
</html>