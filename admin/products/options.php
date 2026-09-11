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
        ?? $_POST['id']
        ?? 0
    );

$stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        size,
        color,
        material
     FROM products
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    ':id' => $id,
]);

$product =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );

if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $size =
        trim(
            strip_tags(
                (string)(
                    $_POST['size']
                    ?? ''
                )
            )
        );

    $color =
        trim(
            strip_tags(
                (string)(
                    $_POST['color']
                    ?? ''
                )
            )
        );

    $material =
        trim(
            strip_tags(
                (string)(
                    $_POST['material']
                    ?? ''
                )
            )
        );

    $sizes = [];

    foreach (
        explode(',', $size)
        as $value
    ) {

        $value =
            trim($value);

        if ($value === '') {
            continue;
        }

        if (
            !in_array(
                $value,
                $sizes,
                true
            )
        ) {
            $sizes[] = $value;
        }
    }

    $size =
        $sizes
            ? implode(',', $sizes)
            : '';

    $update = $pdo->prepare(
        'UPDATE products
         SET
            size = :size,
            color = :color,
            material = :material
         WHERE id = :id'
    );

    $update->execute([

        ':size' =>
            $size !== ''
                ? $size
                : null,

        ':color' =>
            $color !== ''
                ? $color
                : null,

        ':material' =>
            $material !== ''
                ? $material
                : null,

        ':id' =>
            $id,
    ]);

    $product['size'] =
        $size;

    $product['color'] =
        $color;

    $product['material'] =
        $material;

    $message =
        'Đã cập nhật thuộc tính sản phẩm.';
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Thuộc tính sản phẩm</title>

<style>

body {
    margin: 0;

    background: #f8f8f4;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.wrap {
    width: min(680px, calc(100% - 40px));

    margin: 55px auto;
}

.card {
    padding: 32px;

    border: 1px solid #dce2da;

    background: white;
}

.field {
    display: grid;

    gap: 7px;

    margin-top: 18px;
}

.field input {
    min-height: 48px;

    padding: 0 13px;

    border: 1px solid #d5dcd6;
}

button {
    width: 100%;

    min-height: 50px;

    margin-top: 25px;

    border: 0;

    background: #173b2e;

    color: white;

    font-weight: 700;
}

.message {
    margin-bottom: 20px;

    padding: 12px;

    background: #edf4ec;
}

</style>

</head>

<body>

<main class="wrap">

    <a href="edit.php?id=<?= $id ?>">
        ← Quay lại sản phẩm
    </a>

    <h1>
        Thuộc tính sản phẩm
    </h1>

    <div class="card">

        <strong>
            <?= htmlspecialchars(
                $product['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>

        <?php if ($message !== ''): ?>

            <div class="message">
                <?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>

        <form method="post">

            <input
                type="hidden"
                name="id"
                value="<?= $id ?>"
            >

            <div class="field">

                <label>
                    Kích cỡ
                </label>

                <input
                    name="size"
                    value="<?= htmlspecialchars(
                        (string)$product['size'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="S,M,L,XL,XXL"
                >

            </div>

            <div class="field">

                <label>
                    Màu sắc
                </label>

                <input
                    name="color"
                    value="<?= htmlspecialchars(
                        (string)$product['color'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Đen"
                >

            </div>

            <div class="field">

                <label>
                    Chất liệu
                </label>

                <input
                    name="material"
                    value="<?= htmlspecialchars(
                        (string)$product['material'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Kaki thun cao cấp"
                >

            </div>

            <button type="submit">
                Lưu thuộc tính
            </button>

        </form>

    </div>

</main>

</body>
</html>