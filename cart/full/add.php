<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/full_cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: index.php');
    exit;
}

$productId =
    (int)(
        $_POST['product_id']
        ?? $_POST['id']
        ?? 0
    );

$quantity =
    max(
        1,
        (int)(
            $_POST['quantity']
            ?? 1
        )
    );

$selectedSize =
    trim(
        (string)(
            $_POST['size']
            ?? ''
        )
    );

$selectedColor =
    trim(
        (string)(
            $_POST['color']
            ?? ''
        )
    );

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
     LIMIT 1'
);

$stmt->execute([
    ':id' => $productId,
]);

$product =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );

if (
    !$product
    || (int)$product['status'] !== 1
) {

    http_response_code(404);
    exit('Sản phẩm không tồn tại.');
}

$allowedSizes =
    fs_csv_values(
        $product['size']
        ?? ''
    );

if ($allowedSizes) {

    if (
        $selectedSize === ''
        || !in_array(
            $selectedSize,
            $allowedSizes,
            true
        )
    ) {

        header(
            'Location: ../../products/detail.php?id='
            . $productId
            . '&size_error=1'
        );

        exit;
    }

} else {

    $selectedSize = '';
}

$allowedColors =
    fs_csv_values(
        $product['color']
        ?? ''
    );

if (count($allowedColors) === 1) {
    $selectedColor =
        $allowedColors[0];
}

if (
    count($allowedColors) > 1
    && !in_array(
        $selectedColor,
        $allowedColors,
        true
    )
) {

    header(
        'Location: ../../products/detail.php?id='
        . $productId
        . '&color_error=1'
    );

    exit;
}

$stock =
    max(
        0,
        (int)$product['stock']
    );

if ($stock < 1) {
    exit('Sản phẩm đã hết hàng.');
}

$quantity =
    min(
        $quantity,
        $stock
    );

$key =
    fs_cart_key(
        $productId,
        $selectedSize,
        $selectedColor
    );

$cart =& fs_cart();

if (isset($cart[$key])) {

    $cart[$key]['quantity'] =
        min(
            $stock,
            (int)$cart[$key]['quantity']
            + $quantity
        );

} else {

    $cart[$key] = [

        'key' => $key,

        'product_id' =>
            $productId,

        'name' =>
            (string)$product['name'],

        'price' =>
            (float)$product['price'],

        'stock' =>
            $stock,

        'image' =>
            (string)(
                $product['image']
                ?? ''
            ),

        'size' =>
            $selectedSize,

        'color' =>
            $selectedColor,

        'material' =>
            trim(
                (string)(
                    $product['material']
                    ?? ''
                )
            ),

        'quantity' =>
            $quantity,
    ];
}

header(
    'Location: index.php'
);

exit;