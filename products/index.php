<?php

require_once __DIR__ . "/../includes/db.php";

/* =========================
   TÌM KIẾM - LỌC - PHÂN TRANG
========================= */

$keyword = trim($_GET['keyword'] ?? '');
$category = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== ''
    ? max(0, (float) $_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== ''
    ? max(0, (float) $_GET['max_price']) : null;
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$products = [];
$categories = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $categories = $pdo->query(
        "SELECT id, name FROM categories ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $where = ["status = 1"];
    $params = [];

    if ($keyword !== '') {
        $where[] = "name LIKE :keyword";
        $params[':keyword'] = '%' . $keyword . '%';
    }

    if ($category > 0) {
        $where[] = "category_id = :category";
        $params[':category'] = $category;
    }

    if ($minPrice !== null) {
        $where[] = "price >= :min_price";
        $params[':min_price'] = $minPrice;
    }

    if ($maxPrice !== null) {
        $where[] = "price <= :max_price";
        $params[':max_price'] = $maxPrice;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM products WHERE $whereSql"
    );
    $countStmt->execute($params);
    $totalProducts = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $orderBy = match ($sort) {
        'price_asc' => 'price ASC',
        'price_desc' => 'price DESC',
        'name_asc' => 'name ASC',
        default => 'id DESC'
    };

    $sql = "SELECT * FROM products
            WHERE $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi lấy sản phẩm: " . $e->getMessage());
}

function buildPageUrl($pageNumber)
{
    $query = $_GET;
    $query['page'] = $pageNumber;
    return 'index.php?' . http_build_query($query);
}


/* =========================
   LẤY ẢNH SẢN PHẨM
   MỖI SẢN PHẨM CHỈ 1 ẢNH
========================= */

function getProductImage($image)
{
    $image = trim($image ?? '');

    /* Nếu database không có ảnh */
    if ($image === '') {
        return "../uploads/products/ao-thun.jpg";
    }

    /* Chuẩn hóa dấu */
    $image = str_replace("\\", "/", $image);

    /* Lấy tên file */
    $filename = basename($image);

    /* Đường dẫn thật trên máy */
    $fullPath = __DIR__
        . "/../uploads/products/"
        . $filename;

    /* Nếu ảnh tồn tại */
    if (file_exists($fullPath)) {

        return "../uploads/products/" . $filename;

    }

    /* Nếu không tìm thấy ảnh */
    return "../uploads/products/ao-thun.jpg";
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sản phẩm - Fashion Shop</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        /* =========================
           PRODUCTS PAGE
        ========================= */

        .products-page {
            padding: 70px 0 90px;
            background: #ffffff;
        }

        .products-page-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .products-page-header .small-title {
            margin-bottom: 12px;
        }

        .products-page-header h1 {
            font-size: 45px;
            line-height: 1.1;
            color: #263126;
            margin-bottom: 15px;
        }

        .products-page-header p {
            max-width: 600px;
            margin: 0 auto;
            color: #667066;
            font-size: 15px;
        }


        /* =========================
           CATEGORY
        ========================= */

        .category-filter {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 45px;
        }

        .category-filter a {
            padding: 11px 22px;
            border: 1px solid #dfe6df;
            color: #4d584f;
            font-size: 14px;
            font-weight: 600;
            background: #ffffff;
            transition: 0.3s ease;
        }

        .category-filter a:hover {
            background: #263126;
            color: #ffffff;
            border-color: #263126;
        }

        .category-filter a.active {
            background: #263126;
            color: #ffffff;
            border-color: #263126;
        }

        .product-tools {
            margin-bottom: 30px;
            padding: 18px;
            border: 1px solid #e1e7e1;
            background: #f8faf8;
        }

        .product-tools form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 10px;
        }

        .product-tools input,
        .product-tools select,
        .product-tools button {
            min-width: 0;
            padding: 11px 10px;
            border: 1px solid #ccd5cc;
            background: #ffffff;
            font: inherit;
        }

        .product-tools button {
            cursor: pointer;
            border-color: #263126;
            background: #263126;
            color: #ffffff;
        }

        .filter-result {
            margin: 15px 0 25px;
            color: #667066;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 35px;
        }

        .pagination a {
            min-width: 40px;
            padding: 9px 12px;
            border: 1px solid #d6ddd7;
            text-align: center;
        }

        .pagination a:hover,
        .pagination .active {
            border-color: #263126;
            background: #263126;
            color: #ffffff;
        }


        /* =========================
           PRODUCT GRID
        ========================= */

        .products-page .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }


        /* =========================
           PRODUCT CARD
        ========================= */

        .products-page .product-card {
            background: #ffffff;
            border: 1px solid #e7ece7;
            overflow: hidden;
            transition: 0.3s ease;
        }

        .products-page .product-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 12px 30px
                rgba(38, 49, 38, 0.09);
        }


        /* =========================
           PRODUCT IMAGE
        ========================= */

        .products-page .product-image {
            position: relative;

            width: 100%;
            height: 350px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            background: #f3f5f3;
        }

        .products-page .product-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition: 0.5s ease;
        }

        .products-page .product-card:hover
        .product-image img {
            transform: scale(1.04);
        }


        /* =========================
           NEW
        ========================= */

        .products-page .new {
            position: absolute;

            top: 15px;
            left: 15px;

            z-index: 2;

            padding: 6px 11px;

            background: #263126;
            color: #ffffff;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 1px;
        }


        /* =========================
           INFO
        ========================= */

        .products-page .product-info {
            padding: 20px;
        }

        .products-page .product-info small {
            font-size: 10px;
            font-weight: 700;

            letter-spacing: 2px;

            color: #8a958b;
        }

        .products-page .product-info h3 {
            margin-top: 7px;
            margin-bottom: 10px;

            font-size: 18px;

            color: #263126;
        }

        .products-page .product-info h3 a {
            transition: 0.3s ease;
        }

        .products-page .product-info h3 a:hover {
            color: #78917d;
        }

        .products-page .price {
            font-size: 18px;
            font-weight: 700;

            color: #263126;
        }


        /* =========================
           EMPTY
        ========================= */

        .products-empty {
            grid-column: 1 / -1;

            text-align: center;

            padding: 80px 20px;

            background: #f8faf8;

            border: 1px dashed #d6ddd7;
        }

        .products-empty h3 {
            margin-bottom: 10px;

            font-size: 23px;

            color: #263126;
        }

        .products-empty p {
            color: #788078;
        }


        /* =========================
           FOOTER
        ========================= */

        .products-footer {
            background: #263126;
            color: #ffffff;

            padding: 50px 0 25px;
        }

        .products-footer-content {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr;

            gap: 50px;

            padding-bottom: 35px;
        }

        .products-footer h3 {
            font-size: 25px;
            margin-bottom: 12px;
        }

        .products-footer h3 span {
            color: #9ab19f;
        }

        .products-footer p {
            max-width: 350px;

            color: #bdc6bd;

            font-size: 14px;
        }

        .products-footer h4 {
            margin-bottom: 15px;
        }

        .products-footer a {
            display: block;

            margin-bottom: 9px;

            color: #bdc6bd;

            font-size: 14px;
        }

        .products-footer a:hover {
            color: #ffffff;
        }

        .products-copyright {
            border-top: 1px solid #465046;

            padding-top: 20px;

            text-align: center;

            color: #9fa99f;

            font-size: 12px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .product-tools form {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-page .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-footer-content {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 700px) {

            .product-tools form {
                grid-template-columns: 1fr;
            }

            .products-page {
                padding: 50px 0 60px;
            }

            .products-page-header h1 {
                font-size: 36px;
            }

            .products-page .product-grid {
                grid-template-columns: 1fr;
            }

            .products-page .product-image {
                height: 380px;
            }

            .products-footer-content {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="container header-content">

        <a
            href="../index.php"
            class="logo"
        >
            Fashion<span>Shop</span>
        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>

            <a href="index.php">
                Sản phẩm
            </a>

            <a href="index.php?category=1">
                Áo
            </a>

            <a href="index.php?category=2">
                Quần
            </a>

            <a href="index.php?category=3">
                Váy
            </a>

        </nav>


        <a
            href="../cart/index.php"
            class="cart"
        >

            Giỏ hàng

            <span>0</span>

        </a>

    </div>

</header>



<!-- =========================
     PRODUCTS PAGE
========================= -->

<section class="products-page">

    <div class="container">


        <!-- TITLE -->

        <div class="products-page-header">

            <div class="small-title">
                OUR COLLECTION
            </div>

            <h1>
                Tất cả sản phẩm
            </h1>

            <p>
                Khám phá những sản phẩm thời trang
                trẻ trung, hiện đại và phù hợp
                với phong cách của bạn.
            </p>

        </div>


        <!-- TÌM KIẾM VÀ LỌC -->

        <div class="product-tools">

            <form method="GET" action="index.php">

                <input
                    type="text"
                    name="keyword"
                    value="<?= htmlspecialchars($keyword) ?>"
                    placeholder="Nhập tên sản phẩm..."
                >

                <select name="category">
                    <option value="0">Tất cả danh mục</option>

                    <?php foreach ($categories as $item): ?>
                        <option
                            value="<?= (int) $item['id'] ?>"
                            <?= $category === (int) $item['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($item['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="number"
                    name="min_price"
                    min="0"
                    step="1000"
                    value="<?= $minPrice !== null ? (float) $minPrice : '' ?>"
                    placeholder="Giá từ"
                >

                <input
                    type="number"
                    name="max_price"
                    min="0"
                    step="1000"
                    value="<?= $maxPrice !== null ? (float) $maxPrice : '' ?>"
                    placeholder="Giá đến"
                >

                <select name="sort">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên A-Z</option>
                </select>

                <button type="submit">Tìm và lọc</button>

            </form>

        </div>



        <!-- =========================
             CATEGORY FILTER
        ========================= -->

        <div class="category-filter">

            <a
                href="index.php"
                class="<?= $category === 0 ? 'active' : '' ?>"
            >
                Tất cả
            </a>


            <a
                href="index.php?<?= http_build_query(array_merge($_GET, ['category' => 1, 'page' => 1])) ?>"
                class="<?= $category === 1 ? 'active' : '' ?>"
            >
                Áo
            </a>


            <a
                href="index.php?<?= http_build_query(array_merge($_GET, ['category' => 2, 'page' => 1])) ?>"
                class="<?= $category === 2 ? 'active' : '' ?>"
            >
                Quần
            </a>


            <a
                href="index.php?<?= http_build_query(array_merge($_GET, ['category' => 3, 'page' => 1])) ?>"
                class="<?= $category === 3 ? 'active' : '' ?>"
            >
                Váy
            </a>

        </div>

        <div class="filter-result">
            Tìm thấy <?= $totalProducts ?> sản phẩm
        </div>



        <!-- =========================
             PRODUCT LIST
        ========================= -->

        <div class="product-grid">

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $product): ?>

                    <?php

                    $image = getProductImage(
                        $product['image'] ?? ''
                    );

                    ?>


                    <div class="product-card">


                        <a
                            href="detail.php?id=<?= (int) $product['id'] ?>"
                            class="product-image"
                        >

                            <span class="new">
                                NEW
                            </span>


                            <img
                                src="<?= htmlspecialchars($image) ?>"
                                alt="<?= htmlspecialchars(
                                    $product['name'] ?? 'Sản phẩm'
                                ) ?>"
                            >

                        </a>



                        <div class="product-info">


                            <small>
                                FASHION
                            </small>


                            <h3>

                                <a
                                    href="detail.php?id=<?= (int) $product['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $product['name']
                                        ?? 'Sản phẩm'
                                    ) ?>

                                </a>

                            </h3>


                            <div class="price">

                                <?= number_format(
                                    (float) (
                                        $product['price'] ?? 0
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) ?>đ

                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="products-empty">

                    <h3>
                        Chưa có sản phẩm
                    </h3>

                    <p>
                        Hiện tại chưa có sản phẩm
                        phù hợp để hiển thị.
                    </p>

                </div>


            <?php endif; ?>


        </div>


        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars(buildPageUrl($page - 1)) ?>">Trước</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        href="<?= htmlspecialchars(buildPageUrl($i)) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars(buildPageUrl($page + 1)) ?>">Sau</a>
                <?php endif; ?>

            </div>

        <?php endif; ?>


    </div>

</section>



<!-- =========================
     FOOTER
========================= -->

<footer class="products-footer">

    <div class="container">


        <div class="products-footer-content">


            <div>

                <h3>
                    Fashion<span>Shop</span>
                </h3>

                <p>
                    Thời trang trẻ trung,
                    hiện đại và phù hợp
                    với phong cách riêng
                    của bạn.
                </p>

            </div>



            <div>

                <h4>
                    Danh mục
                </h4>

                <a href="index.php">
                    Tất cả sản phẩm
                </a>

                <a href="index.php?category=1">
                    Áo
                </a>

                <a href="index.php?category=2">
                    Quần
                </a>

                <a href="index.php?category=3">
                    Váy
                </a>

            </div>



            <div>

                <h4>
                    Hỗ trợ
                </h4>

                <a href="#">
                    Chính sách đổi trả
                </a>

                <a href="#">
                    Vận chuyển
                </a>

                <a href="#">
                    Liên hệ
                </a>

            </div>


        </div>


        <div class="products-copyright">

            © 2026 Fashion Shop

        </div>


    </div>

</footer>


</body>

</html>
