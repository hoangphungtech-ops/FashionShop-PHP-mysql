<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

function catalogStringInput(string $key, int $maxLength = 100): string
{
    $value = $_GET[$key] ?? '';

    if (!is_string($value)) {
        return '';
    }

    $value = trim(str_replace("\0", '', $value));

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength, 'UTF-8')
        : substr($value, 0, $maxLength);
}

function catalogPriceInput(string $key): ?float
{
    $value = $_GET[$key] ?? null;

    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $price = filter_var($value, FILTER_VALIDATE_FLOAT);

    return $price !== false && $price >= 0 && $price <= 999999999999
        ? (float) $price
        : null;
}

function getProductImage(mixed $image): string
{
    $image = trim((string)($image ?? ''));

    // Không có ảnh
    if ($image === '') {
        return '../assets/images/ao-thun.jpg';
    }

    // Chuẩn hóa đường dẫn
    $image = str_replace('\\', '/', $image);
    $image = ltrim($image, '/');

    // DB lưu nguyên đường dẫn:
    // assets/images/abc.jpg
    // uploads/products/abc.jpg
    $fullPath = __DIR__ . '/../' . $image;

    if (file_exists($fullPath)) {
        return '../' . $image;
    }

    // Trường hợp DB chỉ lưu tên file
    $filename = basename($image);

    // Tìm trong uploads/products
    if (file_exists(__DIR__ . '/../uploads/products/' . $filename)) {
        return '../uploads/products/' . $filename;
    }

    // Tìm trong assets/images
    if (file_exists(__DIR__ . '/../assets/images/' . $filename)) {
        return '../assets/images/' . $filename;
    }

    // Ảnh mặc định
    return '../assets/images/ao-thun.jpg';
}

$search = catalogStringInput('q');
$gender = strtolower(catalogStringInput('gender', 10));
$gender = in_array($gender, ['nam', 'nu'], true) ? $gender : '';
$sort = catalogStringInput('sort', 20);

$sortOptions = [
    'newest' => 'Mới nhất',
    'price_asc' => 'Giá tăng dần',
    'price_desc' => 'Giá giảm dần',
];

if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$minPrice = catalogPriceInput('min_price');
$maxPrice = catalogPriceInput('max_price');

if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}

$categoryInput = $_GET['category'] ?? null;
$requestedCategory = is_string($categoryInput) || is_int($categoryInput)
    ? filter_var($categoryInput, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ])
    : false;
$pageInput = $_GET['page'] ?? 1;
$requestedPage = is_string($pageInput) || is_int($pageInput)
    ? filter_var($pageInput, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ])
    : false;
$page = $requestedPage !== false ? (int) $requestedPage : 1;

$products = [];
$categories = [];
$categoryNames = [];
$category = 0;
$totalProducts = 0;
$totalPages = 1;
$perPage = 9;
$catalogError = '';

$orderBy = [
    'newest' => 'products.created_at DESC, products.id DESC',
    'price_asc' => 'products.price ASC, products.id DESC',
    'price_desc' => 'products.price DESC, products.id DESC',
][$sort];

try {
    $categoryStatement = $pdo->prepare(
        'SELECT id, name FROM categories ORDER BY name ASC'
    );
    $categoryStatement->execute();
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as $categoryRow) {
        $categoryNames[(int) $categoryRow['id']] = (string) $categoryRow['name'];
    }

    if ($requestedCategory !== false && isset($categoryNames[(int) $requestedCategory])) {
        $category = (int) $requestedCategory;
    }

    $conditions = ['products.status = :active_status'];
    $parameters = [':active_status' => 1];

    if ($search !== '') {
        $conditions[] = 'products.name LIKE :search';
        $parameters[':search'] = '%' . $search . '%';
    }

    if ($category > 0) {
        $conditions[] = 'products.category_id = :category_id';
        $parameters[':category_id'] = $category;
    }

    if ($gender !== '') {
        $conditions[] = 'products.name LIKE :gender_keyword';
        $parameters[':gender_keyword'] = $gender === 'nu' ? '%nữ%' : '%nam%';
    }

    if ($minPrice !== null) {
        $conditions[] = 'products.price >= :min_price';
        $parameters[':min_price'] = $minPrice;
    }

    if ($maxPrice !== null) {
        $conditions[] = 'products.price <= :max_price';
        $parameters[':max_price'] = $maxPrice;
    }

    $whereClause = implode(' AND ', $conditions);
    $countStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM products WHERE {$whereClause}"
    );
    $countStatement->execute($parameters);
    $totalProducts = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $productStatement = $pdo->prepare(
        "SELECT products.id,
                products.category_id,
                products.name,
                products.price,
                products.image,
                products.created_at,
                categories.name AS category_name
         FROM products
         LEFT JOIN categories ON categories.id = products.category_id
         WHERE {$whereClause}
         ORDER BY {$orderBy}
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $productStatement->execute($parameters);
    $products = $productStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('[catalog] Cannot load products: ' . $exception->getMessage());
    http_response_code(500);
    $catalogError = 'Không thể tải danh sách sản phẩm lúc này. Vui lòng thử lại sau.';
}

$genderLabel = $gender === 'nu' ? 'Nữ' : ($gender === 'nam' ? 'Nam' : '');

if ($search !== '') {
    $pageHeading = 'Kết quả tìm kiếm';
} elseif ($category > 0 && $genderLabel !== '') {
    $pageHeading = $categoryNames[$category] . ' ' . $genderLabel;
} elseif ($category > 0) {
    $pageHeading = $categoryNames[$category];
} elseif ($genderLabel !== '') {
    $pageHeading = 'Thời trang ' . $genderLabel;
} else {
    $pageHeading = 'Tất cả sản phẩm';
}

$queryState = [
    'q' => $search,
    'category' => $category > 0 ? $category : null,
    'gender' => $gender,
    'min_price' => $minPrice !== null ? (string) (int) $minPrice : null,
    'max_price' => $maxPrice !== null ? (string) (int) $maxPrice : null,
    'sort' => $sort !== 'newest' ? $sort : null,
];
$hasActiveFilters = $search !== ''
    || $category > 0
    || $gender !== ''
    || $minPrice !== null
    || $maxPrice !== null
    || $sort !== 'newest';

$buildCatalogUrl = static function (array $overrides = []) use ($queryState): string {
    $parameters = array_merge($queryState, $overrides);

    foreach ($parameters as $key => $value) {
        if ($value === null || $value === '' || ($key === 'page' && (int) $value <= 1)) {
            unset($parameters[$key]);
        }
    }

    $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

    return 'index.php' . ($query !== '' ? '?' . $query : '');
};

$firstVisibleProduct = $totalProducts > 0 ? (($page - 1) * $perPage) + 1 : 0;
$lastVisibleProduct = min($page * $perPage, $totalProducts);
?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageHeading) ?> - Fashion Shop</title>

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
            margin-bottom: 30px;
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

        .products-page-header .products-page-count {
            width: fit-content;
            margin-top: 16px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #edf4ef;
            color: #526c5c;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
        }


        /* =========================
           SEARCH / FILTER / SORT
        ========================= */

        .catalog-toolbar {
            margin-bottom: 22px;
            padding: 22px;
            border: 1px solid #e1e7e1;
            background: #f8faf8;
        }

        .catalog-filter {
            display: grid;
            grid-template-columns: minmax(240px, 2fr) repeat(5, minmax(110px, 1fr)) auto;
            gap: 14px;
            align-items: end;
        }

        .catalog-filter__field {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 7px;
        }

        .catalog-filter__field label {
            color: #526054;
            font-size: 12px;
            font-weight: 700;
        }

        .catalog-filter__field input,
        .catalog-filter__field select {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #d6ded7;
            border-radius: 0;
            outline: none;
            background: #ffffff;
            color: #263126;
            font: inherit;
            font-size: 14px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .catalog-filter__field input:focus,
        .catalog-filter__field select:focus {
            border-color: #708474;
            box-shadow: 0 0 0 3px rgba(112, 132, 116, .13);
        }

        .catalog-filter__actions {
            display: flex;
            gap: 8px;
        }

        .catalog-filter__submit,
        .catalog-filter__clear {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 10px 17px;
            border: 1px solid #263126;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color .2s ease, color .2s ease, border-color .2s ease;
        }

        .catalog-filter__submit {
            background: #263126;
            color: #ffffff;
        }

        .catalog-filter__submit:hover,
        .catalog-filter__submit:focus-visible {
            background: #526c5c;
            border-color: #526c5c;
        }

        .catalog-filter__clear {
            border-color: #d6ded7;
            background: #ffffff;
            color: #526054;
        }

        .catalog-filter__clear:hover,
        .catalog-filter__clear:focus-visible {
            border-color: #263126;
            color: #263126;
        }

        .catalog-results-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 18px 0 24px;
            color: #667066;
            font-size: 14px;
        }

        .catalog-results-summary strong {
            color: #263126;
        }

        .catalog-results-summary a {
            color: #526c5c;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }


        /* =========================
           CATEGORY SHORTCUTS
        ========================= */

        .category-filter {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 0;
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

        .category-filter a:focus-visible {
            outline: 3px solid rgba(112, 132, 116, .25);
            outline-offset: 2px;
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
            display: flex;
            min-width: 0;
            height: 100%;
            flex-direction: column;
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
            height: auto;
            aspect-ratio: 4 / 5;

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
            display: flex;
            min-height: 132px;
            flex: 1;
            flex-direction: column;
            padding: 20px;
        }

        .products-page .price {
            margin-top: auto;
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

        .products-empty a {
            display: inline-flex;
            margin-top: 22px;
            padding: 11px 18px;
            background: #263126;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
        }

        .products-empty--error {
            border-color: #dfc5c2;
            background: #fff8f7;
        }


        /* =========================
           PAGINATION
        ========================= */

        .catalog-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 38px;
        }

        .catalog-pagination a,
        .catalog-pagination span {
            display: inline-flex;
            min-width: 42px;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border: 1px solid #dfe6df;
            color: #526054;
            font-size: 13px;
            font-weight: 700;
        }

        .catalog-pagination a:hover,
        .catalog-pagination a:focus-visible,
        .catalog-pagination .is-current {
            border-color: #263126;
            background: #263126;
            color: #ffffff;
        }

        .catalog-pagination .is-disabled {
            opacity: .45;
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

            .catalog-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .catalog-filter__field--search,
            .catalog-filter__actions {
                grid-column: 1 / -1;
            }

            .products-page .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-footer-content {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 700px) {

            .products-page {
                padding: 50px 0 60px;
            }

            .products-page-header h1 {
                font-size: 36px;
            }

            .catalog-toolbar {
                padding: 16px;
            }

            .catalog-filter {
                grid-template-columns: 1fr;
            }

            .catalog-filter__field--search,
            .catalog-filter__actions {
                grid-column: auto;
            }

            .catalog-filter__actions {
                flex-direction: column;
            }

            .catalog-results-summary {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .category-filter {
                justify-content: flex-start;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .category-filter a {
                flex: 0 0 auto;
            }

            .products-page .product-grid {
                grid-template-columns: 1fr;
            }

            .products-page .product-image {
                height: auto;
                aspect-ratio: 4 / 5;
            }

            .products-footer-content {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body class="catalog-page">


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

            <a href="index.php?gender=nam">
                Nam
            </a>

            <a href="index.php?gender=nu">
                Nữ
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
                <?= htmlspecialchars($pageHeading) ?>
            </h1>

            <p>
                Khám phá những sản phẩm thời trang
                trẻ trung, hiện đại và phù hợp
                với phong cách của bạn.
            </p>

            <p class="products-page-count">
                <?= number_format($totalProducts, 0, ',', '.') ?> sản phẩm được tìm thấy
            </p>

        </div>



        <!-- =========================
             SEARCH / FILTER / SORT
        ========================= -->

        <div class="catalog-toolbar">
            <form class="catalog-filter" action="index.php" method="get" role="search">
                <div class="catalog-filter__field catalog-filter__field--search">
                    <label for="catalog-search">Tìm theo tên sản phẩm</label>
                    <input
                        id="catalog-search"
                        name="q"
                        type="search"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ví dụ: áo khoác, quần jean..."
                        maxlength="100"
                    >
                </div>

                <div class="catalog-filter__field">
                    <label for="catalog-category">Danh mục</label>
                    <select id="catalog-category" name="category">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $categoryOption): ?>
                            <?php $categoryOptionId = (int) $categoryOption['id']; ?>
                            <option
                                value="<?= $categoryOptionId ?>"
                                <?= $category === $categoryOptionId ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars((string) $categoryOption['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="catalog-filter__field">
                    <label for="catalog-gender">Phong cách</label>
                    <select id="catalog-gender" name="gender">
                        <option value="">Nam &amp; Nữ</option>
                        <option value="nam" <?= $gender === 'nam' ? 'selected' : '' ?>>Nam</option>
                        <option value="nu" <?= $gender === 'nu' ? 'selected' : '' ?>>Nữ</option>
                    </select>
                </div>

                <div class="catalog-filter__field">
                    <label for="catalog-min-price">Giá từ</label>
                    <input
                        id="catalog-min-price"
                        name="min_price"
                        type="number"
                        value="<?= $minPrice !== null ? (int) $minPrice : '' ?>"
                        min="0"
                        step="1000"
                        placeholder="0đ"
                    >
                </div>

                <div class="catalog-filter__field">
                    <label for="catalog-max-price">Giá đến</label>
                    <input
                        id="catalog-max-price"
                        name="max_price"
                        type="number"
                        value="<?= $maxPrice !== null ? (int) $maxPrice : '' ?>"
                        min="0"
                        step="1000"
                        placeholder="1.000.000đ"
                    >
                </div>

                <div class="catalog-filter__field">
                    <label for="catalog-sort">Sắp xếp</label>
                    <select id="catalog-sort" name="sort">
                        <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                            <option value="<?= $sortValue ?>" <?= $sort === $sortValue ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="catalog-filter__actions">
                    <button class="catalog-filter__submit" type="submit">Áp dụng</button>
                    <?php if ($hasActiveFilters): ?>
                        <a class="catalog-filter__clear" href="index.php">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="category-filter" aria-label="Lọc nhanh theo danh mục">
            <a
                href="<?= htmlspecialchars($buildCatalogUrl(['category' => null, 'page' => null]), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= $category === 0 ? 'active' : '' ?>"
                <?= $category === 0 ? 'aria-current="page"' : '' ?>
            >Tất cả danh mục</a>

            <?php foreach ($categories as $categoryShortcut): ?>
                <?php $categoryShortcutId = (int) $categoryShortcut['id']; ?>
                <a
                    href="<?= htmlspecialchars($buildCatalogUrl(['category' => $categoryShortcutId, 'page' => null]), ENT_QUOTES, 'UTF-8') ?>"
                    class="<?= $category === $categoryShortcutId ? 'active' : '' ?>"
                    <?= $category === $categoryShortcutId ? 'aria-current="page"' : '' ?>
                ><?= htmlspecialchars((string) $categoryShortcut['name'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>

        <div class="catalog-results-summary">
            <p>
                Hiển thị <strong><?= $firstVisibleProduct ?>–<?= $lastVisibleProduct ?></strong>
                trong <strong><?= number_format($totalProducts, 0, ',', '.') ?></strong> sản phẩm
                <?php if ($search !== ''): ?>
                    cho “<strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>”
                <?php endif; ?>
            </p>

            <?php if ($hasActiveFilters): ?>
                <a href="index.php">Xóa tất cả bộ lọc</a>
            <?php endif; ?>
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

                    $productName = (string)($product['name'] ?? 'Sản phẩm');
                    $productGender = preg_match('/(^|\s)nữ($|\s)/iu', $productName)
                        ? 'NỮ'
                        : (preg_match('/(^|\s)nam($|\s)/iu', $productName) ? 'NAM' : 'UNISEX');
                    $productCategory = (string)($product['category_name'] ?? 'THỜI TRANG');
                    $createdTimestamp = strtotime((string) ($product['created_at'] ?? ''));
                    $isNew = $createdTimestamp !== false && $createdTimestamp >= strtotime('-30 days');

                    ?>


                    <div class="product-card">


                        <a
                            href="detail.php?id=<?= (int) $product['id'] ?>"
                            class="product-image"
                        >

                            <?php if ($isNew): ?>
                                <span class="new">NEW</span>
                            <?php endif; ?>


                            <img
                                src="<?= htmlspecialchars($image) ?>"
                                alt="<?= htmlspecialchars(
                                    $product['name'] ?? 'Sản phẩm'
                                ) ?>"
                                loading="lazy"
                                decoding="async"
                            >

                        </a>



                        <div class="product-info">


                            <small>
                                <?= htmlspecialchars($productGender . ' · ' . $productCategory) ?>
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


            <?php elseif ($catalogError !== ''): ?>


                <div class="products-empty products-empty--error" role="alert">

                    <h3>Chưa thể tải sản phẩm</h3>

                    <p><?= htmlspecialchars($catalogError, ENT_QUOTES, 'UTF-8') ?></p>

                    <a href="index.php">Thử lại</a>

                </div>


            <?php else: ?>


                <div class="products-empty">

                    <h3>
                        Không tìm thấy sản phẩm
                    </h3>

                    <p>
                        Hãy thử từ khóa khác hoặc điều chỉnh bộ lọc của bạn.
                    </p>

                    <a href="index.php">Xóa bộ lọc</a>

                </div>


            <?php endif; ?>


        </div>

        <?php if ($catalogError === '' && $totalPages > 1): ?>
            <?php
            $paginationPages = [1, $totalPages];

            for ($paginationPage = max(1, $page - 2); $paginationPage <= min($totalPages, $page + 2); $paginationPage++) {
                $paginationPages[] = $paginationPage;
            }

            $paginationPages = array_values(array_unique($paginationPages));
            sort($paginationPages);
            $previousPaginationPage = 0;
            ?>

            <nav class="catalog-pagination" aria-label="Phân trang sản phẩm">
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars($buildCatalogUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>" rel="prev">
                        Trước
                    </a>
                <?php else: ?>
                    <span class="is-disabled" aria-disabled="true">Trước</span>
                <?php endif; ?>

                <?php foreach ($paginationPages as $paginationPage): ?>
                    <?php if ($previousPaginationPage > 0 && $paginationPage > $previousPaginationPage + 1): ?>
                        <span class="is-disabled" aria-hidden="true">…</span>
                    <?php endif; ?>

                    <?php if ($paginationPage === $page): ?>
                        <span class="is-current" aria-current="page"><?= $paginationPage ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($buildCatalogUrl(['page' => $paginationPage]), ENT_QUOTES, 'UTF-8') ?>">
                            <?= $paginationPage ?>
                        </a>
                    <?php endif; ?>

                    <?php $previousPaginationPage = $paginationPage; ?>
                <?php endforeach; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars($buildCatalogUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>" rel="next">
                        Sau
                    </a>
                <?php else: ?>
                    <span class="is-disabled" aria-disabled="true">Sau</span>
                <?php endif; ?>
            </nav>
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
