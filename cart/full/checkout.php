<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/full_cart.php';

$cart = fs_cart();

if (!$cart) {

    header('Location: index.php');
    exit;
}

$user =
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : [];

$error = '';

$form = [

    'receiver_name' =>
        trim(
            (string)(
                $user['name']
                ?? $user['full_name']
                ?? ''
            )
        ),

    'phone' =>
        trim(
            (string)(
                $user['phone']
                ?? ''
            )
        ),

    'email' =>
        trim(
            (string)(
                $user['email']
                ?? ''
            )
        ),

    'address_line' =>
        trim(
            (string)(
                $user['address']
                ?? ''
            )
        ),

    'province' => '',

    'district' => '',

    'ward' => '',

    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !fs_cart_verify_token(
            $_POST['csrf_token'] ?? null
        )
    ) {

        $error =
            'Phiên làm việc không hợp lệ.';

    } else {

        foreach (
            array_keys($form)
            as $field
        ) {

            $form[$field] =
                trim(
                    (string)(
                        $_POST[$field]
                        ?? ''
                    )
                );
        }

        if (
            $form['receiver_name'] === ''
            || $form['phone'] === ''
            || $form['address_line'] === ''
            || $form['province'] === ''
            || $form['district'] === ''
            || $form['ward'] === ''
        ) {

            $error =
                'Vui lòng nhập đầy đủ thông tin giao hàng.';
        }

        if (
            $error === ''
            && $form['email'] !== ''
            && !filter_var(
                $form['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'Địa chỉ email không hợp lệ.';
        }
    }

    if ($error === '') {

        try {

            $pdo->beginTransaction();

            $verifiedItems = [];
            $total = 0;

            foreach ($cart as $item) {

                $stmt = $pdo->prepare(
                    'SELECT
                        id,
                        name,
                        price,
                        stock,
                        image,
                        size,
                        color,
                        material,
                        status
                     FROM products
                     WHERE id = :id
                     FOR UPDATE'
                );

                $stmt->execute([
                    ':id' =>
                        (int)$item['product_id'],
                ]);

                $product =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );

                if (
                    !$product
                    || (int)$product['status'] !== 1
                ) {
                    throw new RuntimeException(
                        'Có sản phẩm không còn khả dụng.'
                    );
                }

                $qty =
                    (int)$item['quantity'];

                if (
                    $qty < 1
                    || $qty > (int)$product['stock']
                ) {
                    throw new RuntimeException(
                        'Số lượng sản phẩm vượt tồn kho.'
                    );
                }

                $sizes =
                    fs_csv_values(
                        $product['size']
                        ?? ''
                    );

                if (
                    $sizes
                    && !in_array(
                        $item['size'],
                        $sizes,
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'Kích cỡ sản phẩm không còn hợp lệ.'
                    );
                }

                $colors =
                    fs_csv_values(
                        $product['color']
                        ?? ''
                    );

                if (
                    $colors
                    && !in_array(
                        $item['color'],
                        $colors,
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'Màu sản phẩm không còn hợp lệ.'
                    );
                }

                $price =
                    (float)$product['price'];

                $lineTotal =
                    $price * $qty;

                $total += $lineTotal;

                $verifiedItems[] = [

                    'product' => $product,

                    'quantity' => $qty,

                    'size' =>
                        (string)$item['size'],

                    'color' =>
                        (string)$item['color'],

                    'line_total' =>
                        $lineTotal,
                ];
            }

            $orderStmt = $pdo->prepare(
                'INSERT INTO fs_orders
                (
                    user_id,
                    receiver_name,
                    phone,
                    email,
                    address_line,
                    province,
                    district,
                    ward,
                    note,
                    total_amount,
                    status
                )
                VALUES
                (
                    :user_id,
                    :receiver_name,
                    :phone,
                    :email,
                    :address_line,
                    :province,
                    :district,
                    :ward,
                    :note,
                    :total_amount,
                    :status
                )'
            );

            $orderStmt->execute([

                ':user_id' =>
                    fs_current_user_id(),

                ':receiver_name' =>
                    $form['receiver_name'],

                ':phone' =>
                    $form['phone'],

                ':email' =>
                    $form['email'] !== ''
                        ? $form['email']
                        : null,

                ':address_line' =>
                    $form['address_line'],

                ':province' =>
                    $form['province'],

                ':district' =>
                    $form['district'],

                ':ward' =>
                    $form['ward'],

                ':note' =>
                    $form['note'] !== ''
                        ? $form['note']
                        : null,

                ':total_amount' =>
                    $total,

                ':status' =>
                    'pending',
            ]);

            $orderId =
                (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO fs_order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    product_image,
                    size,
                    color,
                    material,
                    unit_price,
                    quantity,
                    line_total
                )
                VALUES
                (
                    :order_id,
                    :product_id,
                    :product_name,
                    :product_image,
                    :size,
                    :color,
                    :material,
                    :unit_price,
                    :quantity,
                    :line_total
                )'
            );

            $stockStmt = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity
                 WHERE id = :id
                   AND stock >= :quantity'
            );

            foreach ($verifiedItems as $item) {

                $product =
                    $item['product'];

                $itemStmt->execute([

                    ':order_id' =>
                        $orderId,

                    ':product_id' =>
                        (int)$product['id'],

                    ':product_name' =>
                        (string)$product['name'],

                    ':product_image' =>
                        (string)(
                            $product['image']
                            ?? ''
                        ),

                    ':size' =>
                        $item['size'],

                    ':color' =>
                        $item['color'] !== ''
                            ? $item['color']
                            : null,

                    ':material' =>
                        trim(
                            (string)(
                                $product['material']
                                ?? ''
                            )
                        ) !== ''
                            ? trim(
                                (string)$product['material']
                            )
                            : null,

                    ':unit_price' =>
                        (float)$product['price'],

                    ':quantity' =>
                        $item['quantity'],

                    ':line_total' =>
                        $item['line_total'],
                ]);

                $stockStmt->execute([

                    ':quantity' =>
                        $item['quantity'],

                    ':id' =>
                        (int)$product['id'],
                ]);

                if (
                    $stockStmt->rowCount()
                    !== 1
                ) {
                    throw new RuntimeException(
                        'Không thể cập nhật tồn kho.'
                    );
                }
            }

            $pdo->commit();

            $_SESSION['fs_cart'] = [];

            header(
                'Location: success.php?id='
                . $orderId
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Không thể tạo đơn hàng: '
                . $e->getMessage();
        }
    }
}

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

<title>Thanh toán - FashionShop</title>

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

.checkout {
    width: min(1100px, calc(100% - 40px));

    margin: 0 auto;

    padding: 50px 0 75px;
}

.checkout h1 {
    margin: 10px 0 40px;

    font-size: clamp(32px, 5vw, 52px);

    letter-spacing: -.03em;
}

.checkout-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        380px;

    gap: 50px;
}

.checkout-form {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;
}

.field {
    display: grid;
    gap: 7px;
}

.field.full {
    grid-column: 1 / -1;
}

.field label {
    font-size: 13px;
    font-weight: 700;
}

.field input,
.field textarea {
    width: 100%;

    min-height: 49px;

    padding: 12px 13px;

    border: 1px solid #d6ddd8;

    background: #fff;

    font: inherit;
}

.field textarea {
    min-height: 100px;
    resize: vertical;
}

.checkout-summary {
    padding: 25px;

    background: #f0f3ec;

    align-self: start;
}

.summary-item {
    padding: 13px 0;

    border-bottom: 1px solid #d8dfd8;

    font-size: 13px;
}

.summary-item strong {
    display: block;
    margin-bottom: 5px;
}

.summary-total {
    display: flex;
    justify-content: space-between;

    margin: 22px 0;

    font-size: 18px;
    font-weight: 700;
}

.checkout-button {
    width: 100%;
    min-height: 51px;

    border: 0;

    background: #173b2e;

    color: #fff;

    font-weight: 700;

    cursor: pointer;
}

.checkout-error {
    grid-column: 1 / -1;

    padding: 13px 15px;

    background: #fff0ed;

    color: #93443a;
}

@media (max-width: 820px) {

    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .checkout-form {
        grid-template-columns: 1fr;
    }

    .field.full {
        grid-column: auto;
    }

}

</style>

</head>

<body>

<main class="checkout">

    <a href="index.php">
        ← Giỏ hàng
    </a>

    <h1>
        Thông tin giao hàng
    </h1>

    <form
        method="post"
        class="checkout-grid"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= fs_h($token) ?>"
        >

        <section class="checkout-form">

            <?php if ($error !== ''): ?>

                <div class="checkout-error">
                    <?= fs_h($error) ?>
                </div>

            <?php endif; ?>

            <div class="field">

                <label>Họ và tên *</label>

                <input
                    name="receiver_name"
                    required
                    value="<?= fs_h($form['receiver_name']) ?>"
                >

            </div>

            <div class="field">

                <label>Số điện thoại *</label>

                <input
                    name="phone"
                    required
                    value="<?= fs_h($form['phone']) ?>"
                >

            </div>

            <div class="field full">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    value="<?= fs_h($form['email']) ?>"
                >

            </div>

            <div class="field full">

                <label>Địa chỉ giao hàng *</label>

                <input
                    name="address_line"
                    required
                    value="<?= fs_h($form['address_line']) ?>"
                >

            </div>

            <div class="field">

                <label>Tỉnh / Thành phố *</label>

                <input
                    name="province"
                    required
                    value="<?= fs_h($form['province']) ?>"
                >

            </div>

            <div class="field">

                <label>Quận / Huyện *</label>

                <input
                    name="district"
                    required
                    value="<?= fs_h($form['district']) ?>"
                >

            </div>

            <div class="field full">

                <label>Phường / Xã *</label>

                <input
                    name="ward"
                    required
                    value="<?= fs_h($form['ward']) ?>"
                >

            </div>

            <div class="field full">

                <label>Ghi chú đơn hàng</label>

                <textarea name="note"><?= fs_h($form['note']) ?></textarea>

            </div>

        </section>

        <aside class="checkout-summary">

            <h2>
                Đơn hàng
            </h2>

            <?php foreach ($cart as $item): ?>

                <div class="summary-item">

                    <strong>
                        <?= fs_h($item['name']) ?>
                    </strong>

                    <div>
                        Size:
                        <?= fs_h($item['size']) ?>
                    </div>

                    <?php if ($item['color'] !== ''): ?>

                        <div>
                            Màu:
                            <?= fs_h($item['color']) ?>
                        </div>

                    <?php endif; ?>

                    <div>
                        <?= (int)$item['quantity'] ?>
                        ×
                        <?= number_format(
                            (float)$item['price'],
                            0,
                            ',',
                            '.'
                        ) ?>đ
                    </div>

                </div>

            <?php endforeach; ?>

            <div class="summary-total">

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

            <button
                class="checkout-button"
                type="submit"
            >
                Đặt hàng
            </button>

        </aside>

    </form>

</main>

</body>
</html>