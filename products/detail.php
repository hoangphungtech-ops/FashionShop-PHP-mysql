<?php

declare(strict_types=1);

<<<<<<< HEAD
require_once __DIR__ . '/../includes/db.php';

function resolveProductImage(mixed $value): string
{
    $image = trim((string) ($value ?? ''));
    $fallback = '../assets/images/ao-thun.jpg';

    if ($image === '') {
        return $fallback;
    }

    if (filter_var($image, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $image : $fallback;
    }

    $image = ltrim(str_replace('\\', '/', $image), '/');
    $projectRoot = dirname(__DIR__);
    $candidates = [];

    if (!preg_match('#(^|/)\.\.(/|$)#', $image) && !str_contains($image, ':')) {
        $candidates[] = $image;
    }

    $filename = basename($image);

    foreach ([
        'uploads/products/',
        'uploads/',
        'assets/images/products/',
        'assets/images/',
        'assets/img/products/',
        'assets/img/',
        'images/products/',
        'images/',
    ] as $folder) {
        $candidates[] = $folder . $filename;
    }

    foreach (array_unique($candidates) as $candidate) {
        if ($candidate === '' || !is_file($projectRoot . '/' . $candidate)) {
            continue;
        }

        $encodedPath = implode('/', array_map(
            static fn (string $segment): string => rawurlencode($segment),
            explode('/', $candidate)
        ));

        return '../' . $encodedPath;
    }

    return $fallback;
}

function sanitizeProductDescription(mixed $value): string
{
    $description = trim((string) ($value ?? ''));

    if ($description === '') {
        return '<p>Sản phẩm thời trang được tuyển chọn cho phong cách hiện đại và linh hoạt.</p>';
    }

    if (!preg_match('/<[^>]+>/', $description)) {
        return nl2br(htmlspecialchars(
            $description,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ));
    }

    if (!class_exists(DOMDocument::class)) {
        return nl2br(htmlspecialchars(
            strip_tags($description),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ));
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousLibxmlState = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="product-description-root">'
        . $description
        . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlState);

    if (!$loaded) {
        return nl2br(htmlspecialchars(
            strip_tags($description),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ));
    }

    $root = $document->getElementById('product-description-root');

    if (!$root instanceof DOMElement) {
        return nl2br(htmlspecialchars(
            strip_tags($description),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ));
    }

    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u',
        'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'h4', 'span',
    ];
    $blockedTags = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'form', 'input', 'button', 'textarea', 'select', 'link', 'meta',
    ];

    $sanitizeNode = static function (DOMNode $node) use (&$sanitizeNode, $allowedTags, $blockedTags): void {
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);

            if (in_array($tagName, $blockedTags, true)) {
                $node->removeChild($child);
                continue;
            }

            $sanitizeNode($child);

            if (!in_array($tagName, $allowedTags, true)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);
                continue;
            }

            while ($child->attributes->length > 0) {
                $attribute = $child->attributes->item(0);

                if ($attribute !== null) {
                    $child->removeAttributeNode($attribute);
                }
            }
        }
    };

    $sanitizeNode($root);
    $safeHtml = '';

    foreach ($root->childNodes as $child) {
        $safeHtml .= $document->saveHTML($child);
    }

    return trim($safeHtml) !== ''
        ? $safeHtml
        : '<p>Thông tin sản phẩm đang được cập nhật.</p>';
}

function currentCartQuantityCount(): int
{
    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        return 0;
    }

    $count = 0;

    foreach ($cart as $quantity) {
        $validatedQuantity = filter_var($quantity, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($validatedQuantity !== false) {
            $count += (int) $validatedQuantity;
        }
    }

    return $count;
=======

/* =========================
   KIỂM TRA ID
========================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    die("Sản phẩm không hợp lệ.");
}


/* =========================
   LẤY SẢN PHẨM
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
      AND status = 1
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Không tìm thấy sản phẩm.");
}


/* =========================
   LẤY ẢNH PHỤ
========================= */

$extraImages = [];

try {

    $imageStmt = $pdo->prepare("
        SELECT id, product_id, image
        FROM product_images
        WHERE product_id = ?
        ORDER BY id ASC
    ");

    $imageStmt->execute([$id]);

    $extraImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    // Nếu bảng product_images có vấn đề
    // vẫn cho trang sản phẩm chạy bằng ảnh chính
    $extraImages = [];

>>>>>>> origin/GiaoDien-Nghi
}

$idInput = $_GET['id'] ?? null;
$validatedId = is_string($idInput) || is_int($idInput)
    ? filter_var($idInput, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ])
    : false;
$productId = $validatedId !== false ? (int) $validatedId : 0;
$product = null;
$pageError = '';
$galleryImages = [];
$relatedProducts = [];

<<<<<<< HEAD
if ($productId < 1) {
    http_response_code(404);
} else {
    try {
        $productStatement = $pdo->prepare(
            'SELECT products.id,
                    products.category_id,
                    products.name,
                    products.description,
                    products.price,
                    products.stock,
                    products.image,
                    products.created_at,
                    categories.name AS category_name
             FROM products
             LEFT JOIN categories ON categories.id = products.category_id
             WHERE products.id = :id
               AND products.status = :active_status
             LIMIT 1'
        );
        $productStatement->execute([
            ':id' => $productId,
            ':active_status' => 1,
        ]);
        $product = $productStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($product === null) {
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        error_log('[product-detail] Cannot load product: ' . $exception->getMessage());
        http_response_code(500);
        $pageError = 'Không thể tải thông tin sản phẩm lúc này. Vui lòng thử lại sau.';
    }
}

if ($product !== null) {
    $imageValues = [(string) ($product['image'] ?? '')];

    try {
        $imageStatement = $pdo->prepare(
            'SELECT image
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY id ASC'
        );
        $imageStatement->execute([':product_id' => $productId]);

        foreach ($imageStatement->fetchAll(PDO::FETCH_ASSOC) as $imageRow) {
            $imageValues[] = (string) ($imageRow['image'] ?? '');
        }
    } catch (PDOException $exception) {
        error_log('[product-detail] Cannot load gallery: ' . $exception->getMessage());
    }

    foreach ($imageValues as $imageValue) {
        $resolvedImage = resolveProductImage($imageValue);
        $galleryImages[$resolvedImage] = $resolvedImage;
    }

    $galleryImages = array_values($galleryImages);

    if ((int) ($product['category_id'] ?? 0) > 0) {
        try {
            $relatedStatement = $pdo->prepare(
                'SELECT products.id,
                        products.name,
                        products.price,
                        products.image,
                        products.created_at,
                        categories.name AS category_name
                 FROM products
                 LEFT JOIN categories ON categories.id = products.category_id
                 WHERE products.category_id = :category_id
                   AND products.id <> :product_id
                   AND products.status = :active_status
                 ORDER BY products.created_at DESC, products.id DESC
                 LIMIT 4'
            );
            $relatedStatement->execute([
                ':category_id' => (int) $product['category_id'],
                ':product_id' => $productId,
                ':active_status' => 1,
            ]);
            $relatedProducts = $relatedStatement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log('[product-detail] Cannot load related products: ' . $exception->getMessage());
        }
    }
}

$cartFlash = $_SESSION['cart_flash'] ?? null;
unset($_SESSION['cart_flash']);

if (!is_array($cartFlash)
    || !isset($cartFlash['type'], $cartFlash['message'])
    || !in_array($cartFlash['type'], ['success', 'error'], true)
    || !is_string($cartFlash['message'])) {
    $cartFlash = null;
}

$cartCount = currentCartQuantityCount();
$productName = (string) ($product['name'] ?? 'Không tìm thấy sản phẩm');
$categoryName = (string) ($product['category_name'] ?? 'Thời trang');
$categoryId = (int) ($product['category_id'] ?? 0);
$stock = max(0, (int) ($product['stock'] ?? 0));
$isAvailable = $product !== null && $stock > 0;
$productGender = preg_match('/(^|\s)nữ($|\s)/iu', $productName)
    ? 'Nữ'
    : (preg_match('/(^|\s)nam($|\s)/iu', $productName) ? 'Nam' : 'Unisex');
$descriptionHtml = $product !== null
    ? sanitizeProductDescription($product['description'] ?? '')
    : '';
$pageTitle = $product !== null ? $productName : 'Không tìm thấy sản phẩm';

?>
=======
/* =========================
   XỬ LÝ ĐƯỜNG DẪN ẢNH
========================= */

function detailImage($image)
{
    $image = trim((string)$image);

    /*
     * Không có ảnh
     */
    if ($image === '') {
        return '../assets/images/no-image.jpg';
    }


    /*
     * Nếu là URL đầy đủ
     */
    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }


    /*
     * Chuẩn hóa dấu \
     */
    $image = str_replace('\\', '/', $image);


    /*
     * Nếu database đã lưu đường dẫn
     * assets/images/xxx.jpg
     */
    if (strpos($image, 'assets/images/') === 0) {

        $file = __DIR__ . '/../' . $image;

        if (file_exists($file)) {
            return '../' . $image;
        }
    }


    /*
     * Nếu database lưu
     * uploads/products/xxx.jpg
     */
    if (strpos($image, 'uploads/products/') === 0) {

        $file = __DIR__ . '/../' . $image;

        if (file_exists($file)) {
            return '../' . $image;
        }
    }


    /*
     * Nếu database lưu ./uploads/products/xxx.jpg
     */
    if (strpos($image, './') === 0) {

        $cleanImage = substr($image, 2);

        $file = __DIR__ . '/../' . $cleanImage;

        if (file_exists($file)) {
            return '../' . $cleanImage;
        }
    }


    /*
     * Nếu chỉ lưu tên file
     * Ví dụ:
     * ao-thun.jpg
     */

    $uploadFile = __DIR__ . '/../uploads/products/' . $image;

    if (file_exists($uploadFile)) {
        return '../uploads/products/' . $image;
    }


    /*
     * Thử assets/images
     */

    $assetFile = __DIR__ . '/../assets/images/' . $image;

    if (file_exists($assetFile)) {
        return '../assets/images/' . $image;
    }


    /*
     * Thử đường dẫn trong thư mục products
     */

    $productFile = __DIR__ . '/' . $image;

    if (file_exists($productFile)) {
        return $image;
    }


    /*
     * Không tìm thấy ảnh
     */

    return '../assets/images/no-image.jpg';
}


/* =========================
   ẢNH CHÍNH
========================= */

$mainImage = detailImage(
    $product['image'] ?? ''
);


/* =========================
   DANH SÁCH ẢNH
========================= */

$allImages = [];


/*
 * Thêm ảnh chính
 */

if (!empty($product['image'])) {

    $allImages[] = [
        'image' => $product['image']
    ];
}


/*
 * Thêm ảnh phụ
 */

foreach ($extraImages as $img) {

    if (!empty($img['image'])) {

        $allImages[] = [
            'image' => $img['image']
        ];
    }
}


/*
 * Nếu không có ảnh nào
 */

if (empty($allImages)) {

    $allImages[] = [
        'image' => ''
    ];
}

?>

>>>>>>> origin/GiaoDien-Nghi
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta
        name="description"
        content="<?= htmlspecialchars($product !== null ? $productName . ' tại Fashion Shop' : 'Thông tin sản phẩm Fashion Shop', ENT_QUOTES, 'UTF-8') ?>"
    >
<<<<<<< HEAD
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
=======

    <title>
        <?= htmlspecialchars($product['name']) ?>
        - Fashion Shop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =========================
           DETAIL PAGE
        ========================= */

        .detail-page {

            padding: 70px 0;

            background: #f7faf7;
        }


        .detail-layout {

            display: grid;

            grid-template-columns:
                1.05fr .95fr;

            gap: 70px;

            align-items: start;
        }


        /* =========================
           ẢNH
        ========================= */

        .detail-gallery {

            display: grid;

            grid-template-columns:
                85px 1fr;

            gap: 15px;
        }


        .detail-thumbnails {

            display: flex;

            flex-direction: column;

            gap: 12px;
        }


        .detail-thumb {

            width: 85px;

            height: 105px;

            border: 1px solid #dfe7e1;

            background: #fff;

            padding: 0;

            cursor: pointer;

            overflow: hidden;
        }


        .detail-thumb.active {

            border: 2px solid #173d32;
        }


        .detail-thumb img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        .detail-main-image {

            width: 100%;

            height: 600px;

            background: #eef3ef;

            overflow: hidden;
        }


        .detail-main-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* =========================
           THÔNG TIN
        ========================= */

        .detail-info {

            padding-top: 10px;
        }


        .detail-category {

            color: #71937d;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 3px;

            margin-bottom: 15px;
        }


        .detail-info h1 {

            color: #173d32;

            font-size: 42px;

            line-height: 1.1;

            margin-bottom: 18px;
        }


        .detail-price {

            color: #638b72;

            font-size: 28px;

            font-weight: 700;

            margin-bottom: 25px;
        }


        .detail-description {

            color: #66766e;

            font-size: 16px;

            line-height: 1.9;

            margin-bottom: 25px;
        }


        .detail-stock {

            padding: 15px 18px;

            background: #eef4ef;

            margin-bottom: 25px;

            color: #173d32;

            font-size: 14px;

            font-weight: 600;
        }


        .back-products {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 15px 24px;

            background: #173d32;

            color: #fff;

            font-weight: 700;

            transition: .2s;
        }


        .back-products:hover {

            background: #285746;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .detail-page {

                padding: 35px 0;
            }


            .detail-layout {

                grid-template-columns: 1fr;

                gap: 35px;
            }


            .detail-gallery {

                grid-template-columns: 1fr;

                gap: 12px;
            }


            .detail-main-image {

                height: 430px;
            }


            .detail-thumbnails {

                flex-direction: row;

                overflow-x: auto;

                padding-bottom: 5px;
            }


            .detail-thumb {

                min-width: 70px;

                width: 70px;

                height: 85px;
            }


            .detail-info h1 {

                font-size: 32px;
            }


            .detail-price {

                font-size: 24px;
            }

        }

    </style>

>>>>>>> origin/GiaoDien-Nghi
</head>

<body class="detail-page-body">
<header class="header">
    <div class="container header-content">
<<<<<<< HEAD
        <a href="../index.php" class="logo" aria-label="Fashion Shop - Trang chủ">
            Fashion<span>Shop</span>
        </a>

        <nav class="nav" aria-label="Điều hướng chính">
            <a href="../index.php">Trang chủ</a>
            <a href="index.php" class="active" aria-current="page">Sản phẩm</a>
            <a href="index.php?gender=nam">Nam</a>
            <a href="index.php?gender=nu">Nữ</a>
            <a href="index.php?category=1">Áo</a>
            <a href="index.php?category=2">Quần</a>
            <a href="index.php?category=3">Váy</a>
        </nav>

        <a
            href="../cart/index.php"
            class="cart"
            aria-label="Giỏ hàng, <?= $cartCount ?> sản phẩm"
        >
            Giỏ hàng
            <span><?= $cartCount ?></span>
        </a>
=======


        <a
            href="../index.php"
            class="logo"
        >

            <span class="logo-fashion">
                Fashion
            </span>

            <span class="logo-shop">
                Shop
            </span>

        </a>


        <nav class="nav">

            <a href="../index.php">
                Trang chủ
            </a>

            <a
                href="index.php"
                class="active"
            >
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


        <div class="header-actions">

            <a
                href="../auth/profile.php"
                class="header-action"
            >

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                    ></circle>

                    <path
                        d="M4 21a8 8 0 0 1 16 0"
                    ></path>

                </svg>

            </a>


            <a
                href="../cart/index.php"
                class="header-action cart"
            >

                <svg viewBox="0 0 24 24">

                    <path
                        d="M6 8h12l1 13H5L6 8Z"
                    ></path>

                    <path
                        d="M9 9V6a3 3 0 0 1 6 0v3"
                    ></path>

                </svg>

                <span class="cart-count">
                    0
                </span>

            </a>

        </div>

>>>>>>> origin/GiaoDien-Nghi
    </div>
</header>

<<<<<<< HEAD
<main id="main-content" class="product-detail-page">
=======

<!-- =========================
     DETAIL
========================= -->

<main class="detail-page">

>>>>>>> origin/GiaoDien-Nghi
    <div class="container">
        <nav class="product-detail-breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <a href="index.php">Sản phẩm</a>

<<<<<<< HEAD
            <?php if ($product !== null && $categoryId > 0): ?>
                <span aria-hidden="true">/</span>
                <a href="index.php?category=<?= $categoryId ?>">
                    <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>

            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></span>
        </nav>
=======
        <div class="detail-layout">
>>>>>>> origin/GiaoDien-Nghi

        <?php if ($cartFlash !== null): ?>
            <div
                class="product-detail-notice product-detail-notice--<?= $cartFlash['type'] ?>"
                role="<?= $cartFlash['type'] === 'error' ? 'alert' : 'status' ?>"
            >
                <?= htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

<<<<<<< HEAD
        <?php if ($product !== null): ?>
            <article class="product-detail-layout">
                <div class="product-detail-gallery" data-product-gallery>
                    <div class="product-detail-gallery__main">
                        <img
                            src="<?= htmlspecialchars($galleryImages[0], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>"
                            data-gallery-main
                        >
                    </div>
=======
            <!-- =========================
                 GALLERY
            ========================= -->

            <div class="detail-gallery">


                <div class="detail-thumbnails">

                    <?php foreach ($allImages as $index => $img): ?>

                        <?php
                        $thumbImage = detailImage(
                            $img['image']
                        );
                        ?>

                        <button
                            type="button"
                            class="detail-thumb <?= $index === 0 ? 'active' : '' ?>"
                            onclick="changeImage(this.dataset.image, this)"
                            data-image="<?= htmlspecialchars(
                                $thumbImage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <img
                                src="<?= htmlspecialchars(
                                    $thumbImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="Ảnh sản phẩm"
                                onerror="this.src='../assets/images/no-image.jpg';"
                            >

                        </button>

                    <?php endforeach; ?>

                </div>


                <div class="detail-main-image">

                    <img
                        id="mainProductImage"
                        src="<?= htmlspecialchars(
                            $mainImage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $product['name']
                        ) ?>"
                        onerror="this.src='../assets/images/no-image.jpg';"
                    >
>>>>>>> origin/GiaoDien-Nghi

                    <?php if (count($galleryImages) > 1): ?>
                        <div class="product-detail-gallery__thumbs" aria-label="Ảnh sản phẩm">
                            <?php foreach ($galleryImages as $imageIndex => $galleryImage): ?>
                                <button
                                    class="product-detail-gallery__thumb <?= $imageIndex === 0 ? 'is-active' : '' ?>"
                                    type="button"
                                    data-gallery-thumb
                                    data-gallery-src="<?= htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="Xem ảnh <?= $imageIndex + 1 ?> của <?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>"
                                >
                                    <img
                                        src="<?= htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($productName . ' - ảnh ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8') ?>"
                                        loading="lazy"
                                    >
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

<<<<<<< HEAD
                <section class="product-detail-info" aria-labelledby="product-title">
                    <a
                        class="product-detail-info__category"
                        href="index.php<?= $categoryId > 0 ? '?category=' . $categoryId : '' ?>"
                    >
                        <?= htmlspecialchars($productGender . ' · ' . $categoryName, ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <h1 id="product-title"><?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?></h1>

                    <p class="product-detail-info__price">
                        <?= number_format((float) $product['price'], 0, ',', '.') ?>đ
                    </p>

                    <div class="product-detail-stock <?= $isAvailable ? 'is-available' : 'is-sold-out' ?>">
                        <span aria-hidden="true"></span>
                        <div>
                            <strong><?= $isAvailable ? 'Còn hàng' : 'Hết hàng' ?></strong>
                            <p>
                                <?php if (!$isAvailable): ?>
                                    Sản phẩm hiện chưa thể đặt mua.
                                <?php elseif ($stock <= 5): ?>
                                    Chỉ còn <?= $stock ?> sản phẩm — bạn nên đặt sớm.
                                <?php else: ?>
                                    Sẵn sàng giao từ kho Fashion Shop.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="product-detail-description">
                        <h2>Mô tả sản phẩm</h2>
                        <div class="product-detail-description__content">
                            <?= $descriptionHtml ?>
                        </div>
                    </div>

                    <form class="product-detail-actions" action="../cart/add.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                        <input type="hidden" name="return_url" value="../products/detail.php?id=<?= $productId ?>">

                        <div class="product-detail-quantity">
                            <label for="product-quantity">Số lượng</label>
                            <div class="product-detail-quantity__control" data-quantity-control>
                                <button
                                    type="button"
                                    aria-label="Giảm số lượng"
                                    data-quantity-decrease
                                    <?= !$isAvailable ? 'disabled' : '' ?>
                                >−</button>
                                <input
                                    id="product-quantity"
                                    name="quantity"
                                    type="number"
                                    value="1"
                                    min="1"
                                    max="<?= $stock ?>"
                                    inputmode="numeric"
                                    aria-label="Số lượng sản phẩm"
                                    data-quantity-input
                                    <?= !$isAvailable ? 'disabled' : '' ?>
                                >
                                <button
                                    type="button"
                                    aria-label="Tăng số lượng"
                                    data-quantity-increase
                                    <?= !$isAvailable ? 'disabled' : '' ?>
                                >+</button>
                            </div>
                        </div>
=======

            </div>


            <!-- =========================
                 INFO
            ========================= -->

            <div class="detail-info">

                <div class="detail-category">

                    FASHION

                </div>


                <h1>

                    <?= htmlspecialchars(
                        $product['name']
                    ) ?>

                </h1>


                <div class="detail-price">

                    <?= number_format(
                        (float)$product['price'],
                        0,
                        ',',
                        '.'
                    ) ?>đ

                </div>


                <p class="detail-description">

                    <?= nl2br(
                        htmlspecialchars(
                            $product['description'] ?? ''
                        )
                    ) ?>

                </p>


                <div class="detail-stock">

                    Còn lại:

                    <?= (int)(
                        $product['stock'] ?? 0
                    ) ?>

                    sản phẩm

                </div>
>>>>>>> origin/GiaoDien-Nghi

                        <button
                            class="product-detail-actions__submit"
                            type="submit"
                            <?= !$isAvailable ? 'disabled aria-disabled="true"' : '' ?>
                        >
                            <?= $isAvailable ? 'Thêm vào giỏ hàng' : 'Sản phẩm hết hàng' ?>
                        </button>
                    </form>

<<<<<<< HEAD
                    <ul class="product-detail-services" aria-label="Dịch vụ mua hàng">
                        <li>Giao hàng toàn quốc</li>
                        <li>Kiểm tra sản phẩm trước khi nhận</li>
                        <li>Hỗ trợ đổi trả theo chính sách</li>
                    </ul>
                </section>
            </article>
        <?php else: ?>
            <section class="product-detail-empty" role="<?= $pageError !== '' ? 'alert' : 'status' ?>">
                <p class="product-detail-empty__eyebrow">Fashion Shop</p>
                <h1><?= $pageError !== '' ? 'Chưa thể tải sản phẩm' : 'Không tìm thấy sản phẩm' ?></h1>
                <p>
                    <?= htmlspecialchars(
                        $pageError !== ''
                            ? $pageError
                            : 'Sản phẩm không tồn tại, đã ngừng hiển thị hoặc đường dẫn chưa chính xác.',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
                <a href="index.php">Khám phá sản phẩm khác</a>
            </section>
        <?php endif; ?>
    </div>

    <?php if ($product !== null && !empty($relatedProducts)): ?>
        <section class="related-products" aria-labelledby="related-products-title">
            <div class="container">
                <div class="related-products__heading">
                    <div>
                        <p>SAME EDIT</p>
                        <h2 id="related-products-title">Sản phẩm liên quan</h2>
                    </div>
                    <a href="index.php?category=<?= $categoryId ?>">Xem danh mục</a>
                </div>

                <div class="product-grid related-products__grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <?php
                        $relatedProductId = (int) $relatedProduct['id'];
                        $relatedProductName = (string) $relatedProduct['name'];
                        $relatedImage = resolveProductImage($relatedProduct['image'] ?? '');
                        $relatedCreatedAt = strtotime((string) ($relatedProduct['created_at'] ?? ''));
                        $relatedIsNew = $relatedCreatedAt !== false
                            && $relatedCreatedAt >= strtotime('-30 days');
                        ?>
                        <article class="product-card">
                            <a class="product-image" href="detail.php?id=<?= $relatedProductId ?>">
                                <?php if ($relatedIsNew): ?>
                                    <span class="new">NEW</span>
                                <?php endif; ?>
                                <img
                                    src="<?= htmlspecialchars($relatedImage, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($relatedProductName, ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </a>
                            <div class="product-info">
                                <small><?= htmlspecialchars((string) ($relatedProduct['category_name'] ?? 'Thời trang'), ENT_QUOTES, 'UTF-8') ?></small>
                                <h3>
                                    <a href="detail.php?id=<?= $relatedProductId ?>">
                                        <?= htmlspecialchars($relatedProductName, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h3>
                                <p class="price">
                                    <?= number_format((float) $relatedProduct['price'], 0, ',', '.') ?>đ
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div>
                <h3>Fashion<span>Shop</span></h3>
                <p>Thời trang hiện đại được tuyển chọn cho phong cách riêng của bạn.</p>
            </div>
            <div>
                <h4>Danh mục</h4>
                <a href="index.php">Tất cả sản phẩm</a>
                <a href="index.php?category=1">Áo</a>
                <a href="index.php?category=2">Quần</a>
                <a href="index.php?category=3">Váy</a>
            </div>
            <div>
                <h4>Hỗ trợ</h4>
                <a href="../cart/index.php">Giỏ hàng</a>
                <a href="../cart/checkout.php">Thanh toán</a>
            </div>
        </div>
        <div class="copyright">© 2026 Fashion Shop</div>
    </div>
</footer>
=======
                <a
                    href="index.php"
                    class="back-products"
                >

                    ← Quay lại sản phẩm

                </a>

            </div>


        </div>

    </div>

</main>


<script>

function changeImage(src, button) {

    const mainImage =
        document.getElementById(
            "mainProductImage"
        );


    mainImage.src = src;


    document
        .querySelectorAll(".detail-thumb")
        .forEach(function(item) {

            item.classList.remove("active");

        });


    button.classList.add("active");

}

</script>
>>>>>>> origin/GiaoDien-Nghi

<script src="../assets/js/main.js" defer></script>
</body>
</html>
