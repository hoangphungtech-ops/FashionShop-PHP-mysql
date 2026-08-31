<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LẤY GIỎ HÀNG
|--------------------------------------------------------------------------
| Giả sử giỏ hàng được lưu trong session:
| $_SESSION['cart'][product_id] = số lượng
*/

$cart = $_SESSION['cart'] ?? [];

$products = [];
$total = 0;

if (!empty($cart)) {

    $ids = array_keys($cart);

    $ids = array_map('intval', $ids);

    $ids = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (!empty($ids)) {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {

            $sql = "SELECT *
                    FROM products
                    WHERE id IN ($placeholders)
                    AND status = 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            die("Lỗi database: " . $e->getMessage());

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kiểm tra giỏ hàng của bạn tại Fashion Shop.">
    <title>Giỏ hàng | Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="site-body cart-page">
<?php
$siteBasePath = '../';
$currentPage = 'cart';
$currentCategory = 0;
$cartCount = count($cart);
require __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="page-intro page-intro--compact">
        <div class="site-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="../index.php">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Giỏ hàng</span>
            </nav>

            <div class="page-intro__content">
                <p class="eyebrow">Your shopping bag</p>
                <h1>Giỏ hàng</h1>
                <p>Kiểm tra lựa chọn của bạn trước khi chuyển sang bước thanh toán.</p>
            </div>
        </div>
    </section>

    <section class="cart-section" aria-label="Sản phẩm trong giỏ hàng">
        <div class="site-container">
            <?php if (empty($products)): ?>
                <div class="empty-state empty-state--cart">
                    <span class="empty-state__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M5.75 8.25h12.5l1 12H4.75l1-12Z"></path>
                            <path d="M8.75 9V6.75a3.25 3.25 0 0 1 6.5 0V9"></path>
                        </svg>
                    </span>
                    <p class="eyebrow">Your bag is empty</p>
                    <h2>Giỏ hàng đang trống</h2>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                    <a class="button button--primary" href="../products/index.php">
                        Khám phá sản phẩm
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-list">
                        <div class="cart-list__heading">
                            <h2>Sản phẩm đã chọn</h2>
                            <span><?= count($products) ?> sản phẩm</span>
                        </div>

                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Đơn giá</th>
                                    <th scope="col">Số lượng</th>
                                    <th scope="col">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php

                                    $productId = (int)$product['id'];

                                    $quantity = (int)(
                                        $cart[$productId]
                                        ?? 0
                                    );

                                    if ($quantity <= 0) {
                                        continue;
                                    }

                                    $price = (float)(
                                        $product['price']
                                        ?? 0
                                    );

                                    $subtotal =
                                        $price * $quantity;

                                    $total += $subtotal;


                                    $image = trim(
                                        $product['image']
                                        ?? ''
                                    );

                                    if ($image === '') {
                                        $image =
                                            "../assets/images/ao-thun.jpg";
                                    } else {

                                        if (
                                            strpos(
                                                $image,
                                                "uploads/"
                                            ) === 0
                                        ) {

                                            $image = "../" . $image;

                                        } elseif (
                                            strpos(
                                                $image,
                                                "assets/"
                                            ) === 0
                                        ) {

                                            $image = "../" . $image;

                                        } else {

                                            $image =
                                                "../uploads/products/"
                                                . basename($image);

                                        }
                                    }

                                    ?>

                                    <tr>
                                        <td data-label="Sản phẩm">
                                            <div class="cart-product">
                                                <a
                                                    class="cart-product__image"
                                                    href="../products/detail.php?id=<?= $productId ?>"
                                                >
                                                    <img
                                                        src="<?= htmlspecialchars($image) ?>"
                                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                                    >
                                                </a>
                                                <div class="cart-product__info">
                                                    <span>Fashion selection</span>
                                                    <h3>
                                                        <a href="../products/detail.php?id=<?= $productId ?>">
                                                            <?= htmlspecialchars($product['name']) ?>
                                                        </a>
                                                    </h3>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="cart-price" data-label="Đơn giá">
                                            <?= number_format($price, 0, ',', '.') ?>đ
                                        </td>
                                        <td class="cart-quantity" data-label="Số lượng">
                                            <span><?= $quantity ?></span>
                                        </td>
                                        <td class="cart-total" data-label="Thành tiền">
                                            <?= number_format($subtotal, 0, ',', '.') ?>đ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <aside class="order-summary" aria-labelledby="cart-summary-title">
                        <p class="eyebrow">Order summary</p>
                        <h2 id="cart-summary-title">Tóm tắt đơn hàng</h2>

                        <div class="order-summary__rows">
                            <div>
                                <span>Tạm tính</span>
                                <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                            </div>
                            <div>
                                <span>Phí vận chuyển</span>
                                <strong>Tính khi thanh toán</strong>
                            </div>
                        </div>

                        <div class="order-summary__total">
                            <span>Tổng cộng</span>
                            <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                        </div>

                        <a class="button button--primary order-summary__checkout" href="checkout.php">
                            Thanh toán
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 7l5 5-5 5"></path></svg>
                        </a>
                        <a class="order-summary__continue" href="../products/index.php">← Tiếp tục mua hàng</a>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js" defer></script>
</body>
</html>
