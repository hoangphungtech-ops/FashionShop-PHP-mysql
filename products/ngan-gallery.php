<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$pdoConn = null;

if (isset($pdo) && $pdo instanceof PDO) {
    $pdoConn = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
    $pdoConn = $conn;
} elseif (isset($db) && $db instanceof PDO) {
    $pdoConn = $db;
}

if (!$pdoConn instanceof PDO) {
    die('Không tìm thấy kết nối database.');
}

$sql = "
    SELECT
        p.id AS product_id,
        p.name AS product_name,
        p.image AS image,
        0 AS sort_order,
        'Ảnh chính' AS image_label
    FROM products p
    WHERE p.image LIKE 'uploads/products/ngan/%'

    UNION ALL

    SELECT
        p.id,
        p.name,
        pi.image,
        1,
        'Ảnh phụ'
    FROM product_images pi
    INNER JOIN products p
        ON p.id = pi.product_id
    WHERE pi.image LIKE 'uploads/products/ngan/%'

    ORDER BY product_id ASC, sort_order ASC
";

$rows = $pdoConn
    ->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);

$products = [];

foreach ($rows as $row) {

    $id = (int)$row['product_id'];

    if (!isset($products[$id])) {

        $products[$id] = [
            'name' => $row['product_name'],
            'images' => [],
        ];
    }

    $products[$id]['images'][] = [
        'path' => $row['image'],
        'label' => $row['image_label'],
    ];
}

function h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$totalImages = count($rows);
?>
<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>93 ảnh sản phẩm Ngân - FashionShop</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f7f6f1;
    color: #173a34;
    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.container {
    width: min(1450px, calc(100% - 40px));
    margin: auto;
    padding: 45px 0 80px;
}

.top {
    margin-bottom: 46px;
}

.label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .18em;
    color: #72867d;
    font-weight: 700;
}

h1 {
    margin: 10px 0 14px;
    font-family: Georgia, serif;
    font-size: clamp(36px, 5vw, 62px);
    font-weight: 500;
}

.stats {
    display: flex;
    gap: 35px;
    flex-wrap: wrap;
    margin-top: 20px;
    font-size: 15px;
}

.stats strong {
    font-size: 24px;
}

.ok {
    color: #17643d;
    font-weight: 700;
}

.product {
    border-top: 1px solid #d7ddd8;
    padding: 38px 0 45px;
}

.product-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.product h2 {
    margin: 0;
    font-family: Georgia, serif;
    font-size: 30px;
    font-weight: 500;
}

.count {
    font-size: 14px;
    color: #708078;
}

.grid {
    display: grid;
    grid-template-columns:
        repeat(5, minmax(0, 1fr));
    gap: 16px;
}

figure {
    margin: 0;
    background: white;
    border: 1px solid #e2e5e1;
    overflow: hidden;
}

img {
    width: 100%;
    aspect-ratio: 4 / 5;
    display: block;
    object-fit: cover;
    background: #efeee9;
}

figcaption {
    padding: 10px 12px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #667770;
}

.main {
    color: #173a34;
    font-weight: 700;
}

.warning {
    padding: 15px;
    background: #fff4d8;
    margin-bottom: 25px;
}

.back {
    display: inline-block;
    margin-top: 20px;
    color: #173a34;
}

@media (max-width: 1050px) {

    .grid {
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {

    .grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 540px) {

    .container {
        width: calc(100% - 24px);
    }

    .grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

</style>

</head>

<body>

<main class="container">

    <section class="top">

        <div class="label">
            FashionShop · Gallery
        </div>

        <h1>
            Toàn bộ ảnh sản phẩm của Ngân
        </h1>

        <div class="stats">

            <div>
                <strong>
                    <?= count($products) ?>
                </strong>
                sản phẩm
            </div>

            <div>
                <strong>
                    <?= $totalImages ?>
                </strong>
                ảnh trên web
            </div>

            <div class="<?= $totalImages === 93 ? 'ok' : '' ?>">
                <?= $totalImages === 93
                    ? '✓ Đủ 93/93'
                    : '⚠ Chưa đủ 93'
                ?>
            </div>

        </div>

        <a class="back" href="./">
            ← Danh sách sản phẩm
        </a>

    </section>

    <?php foreach ($products as $id => $product): ?>

        <section class="product">

            <div class="product-head">

                <h2>
                    <?= h($product['name']) ?>
                </h2>

                <div class="count">
                    ID <?= (int)$id ?>
                    ·
                    <?= count($product['images']) ?>
                    ảnh
                </div>

            </div>

            <div class="grid">

                <?php foreach (
                    $product['images']
                    as $i => $image
                ): ?>

                    <figure>

                        <a
                            href="../<?= h($image['path']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >

                            <img
                                src="../<?= h($image['path']) ?>"
                                alt="<?= h(
                                    $product['name']
                                    . ' ảnh '
                                    . ($i + 1)
                                ) ?>"
                                loading="lazy"
                            >

                        </a>

                        <figcaption>

                            <span class="<?= $image['label'] === 'Ảnh chính'
                                ? 'main'
                                : ''
                            ?>">
                                <?= h($image['label']) ?>
                            </span>

                            <span>
                                <?= $i + 1 ?>
                                /
                                <?= count($product['images']) ?>
                            </span>

                        </figcaption>

                    </figure>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endforeach; ?>

</main>

</body>
</html>