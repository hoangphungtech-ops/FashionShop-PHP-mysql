<?php

declare(strict_types=1);

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

    foreach ($cart as $value) {
        $quantityValue = is_array($value)
            ? ($value['quantity'] ?? null)
            : $value;

        $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($quantity !== false) {
            $count += (int)$quantity;
        }
    }

    return $count;
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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta
        name="description"
        content="<?= htmlspecialchars($product !== null ? $productName . ' tại Fashion Shop' : 'Thông tin sản phẩm Fashion Shop', ENT_QUOTES, 'UTF-8') ?>"
    >
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - Fashion Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="detail-page-body">
<header class="header">
    <div class="container header-content">
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
    </div>
</header>

<main id="main-content" class="product-detail-page">
    <div class="container">
        <nav class="product-detail-breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <a href="index.php">Sản phẩm</a>

            <?php if ($product !== null && $categoryId > 0): ?>
                <span aria-hidden="true">/</span>
                <a href="index.php?category=<?= $categoryId ?>">
                    <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>

            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></span>
        </nav>

        <?php if ($cartFlash !== null): ?>
            <div
                class="product-detail-notice product-detail-notice--<?= $cartFlash['type'] ?>"
                role="<?= $cartFlash['type'] === 'error' ? 'alert' : 'status' ?>"
            >
                <?= htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

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

                        <button
                            class="product-detail-actions__submit"
                            type="submit"
                            <?= !$isAvailable ? 'disabled aria-disabled="true"' : '' ?>
                        >
                            <?= $isAvailable ? 'Thêm vào giỏ hàng' : 'Sản phẩm hết hàng' ?>
                        </button>
                    <!-- PRODUCT_VARIANT_FORM_PATCH_BEGIN -->
<?php
$variantProductId = (int)($product['id'] ?? ($_GET['id'] ?? 0));
$variantSizes = [];
$variantColors = [];
$variantMaterial = '';

if ($variantProductId > 0) {
    try {
        $variantStatement = $pdo->prepare(
            'SELECT size, color, material
             FROM products
             WHERE id = :id
             LIMIT 1'
        );
        $variantStatement->execute([':id' => $variantProductId]);
        $variantRow = $variantStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $variantParse = static function (mixed $value): array {
            $value = trim((string)$value);

            if ($value === '') {
                return [];
            }

            $items = [];

            foreach (explode(',', $value) as $item) {
                $item = trim($item);

                if ($item !== '' && !in_array($item, $items, true)) {
                    $items[] = $item;
                }
            }

            return $items;
        };

        $variantSizes = $variantParse($variantRow['size'] ?? '');
        $variantColors = $variantParse($variantRow['color'] ?? '');
        $variantMaterial = trim((string)($variantRow['material'] ?? ''));
    } catch (Throwable $variantException) {
        error_log('[product-variant-ui] ' . $variantException->getMessage());
    }
}
?>

<?php if ($variantSizes !== [] || $variantColors !== [] || $variantMaterial !== ''): ?>
<style>
.product-variant-form-patch{margin:18px 0;display:grid;gap:14px}
.product-variant-form-patch__label{display:block;margin-bottom:8px;font-weight:700;color:#263126}
.product-variant-form-patch__sizes{display:flex;flex-wrap:wrap;gap:8px}
.product-variant-form-patch__size{position:relative;cursor:pointer}
.product-variant-form-patch__size input{position:absolute;opacity:0;pointer-events:none}
.product-variant-form-patch__size span{display:inline-flex;align-items:center;justify-content:center;min-width:44px;height:40px;padding:0 12px;border:1px solid #cfd8d1;background:#fff;color:#263126;transition:.18s}
.product-variant-form-patch__size input:checked + span{border-color:#263126;background:#263126;color:#fff}
.product-variant-form-patch select{width:100%;max-width:300px;min-height:42px;padding:8px 10px;border:1px solid #cfd8d1;background:#fff;color:#263126}
.product-variant-form-patch__value{display:inline-block;padding:8px 11px;border:1px solid #dce2da;background:#f8faf8;color:#4f5e54}
</style>

<div class="product-variant-form-patch">
    <?php if ($variantSizes !== []): ?>
        <div>
            <span class="product-variant-form-patch__label">KÃ­ch cá»¡</span>
            <div class="product-variant-form-patch__sizes">
                <?php foreach ($variantSizes as $variantSize): ?>
                    <label class="product-variant-form-patch__size">
                        <input
                            type="radio"
                            name="size"
                            value="<?= htmlspecialchars($variantSize, ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                        <span><?= htmlspecialchars($variantSize, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($variantColors) === 1): ?>
        <div>
            <span class="product-variant-form-patch__label">MÃ u sáº¯c</span>
            <input
                type="hidden"
                name="color"
                value="<?= htmlspecialchars($variantColors[0], ENT_QUOTES, 'UTF-8') ?>"
            >
            <span class="product-variant-form-patch__value">
                <?= htmlspecialchars($variantColors[0], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    <?php elseif (count($variantColors) > 1): ?>
        <div>
            <label class="product-variant-form-patch__label" for="product-color">
                MÃ u sáº¯c
            </label>
            <select id="product-color" name="color" required>
                <option value="">Chá»n mÃ u</option>
                <?php foreach ($variantColors as $variantColor): ?>
                    <option value="<?= htmlspecialchars($variantColor, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($variantColor, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if ($variantMaterial !== ''): ?>
        <div>
            <span class="product-variant-form-patch__label">Cháº¥t liá»‡u</span>
            <span class="product-variant-form-patch__value">
                <?= htmlspecialchars($variantMaterial, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<!-- PRODUCT_VARIANT_FORM_PATCH_END -->
</form>

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
<?php require __DIR__ . '/../includes/footer.php'; ?>

<script src="../assets/js/main.js" defer></script>

<!-- === HARD PRODUCT TITLE FIX START === -->

<style>

/*
 * FIX CỨNG TITLE TRANG CHI TIẾT.
 * Không dùng font custom cho tiêu đề sản phẩm.
 */

body h1,
main h1,
.product-detail h1,
.product-info h1,
.product-summary h1,
.product-title,
.detail-title {
    font-family:
        Arial,
        "Segoe UI",
        "Helvetica Neue",
        sans-serif !important;

    font-style: normal !important;
    font-weight: 600 !important;

    font-kerning: none !important;
    font-feature-settings: normal !important;
    font-variant-ligatures: none !important;

    letter-spacing: 0 !important;
    word-spacing: 0 !important;

    text-transform: none !important;

    white-space: normal !important;

    text-rendering: geometricPrecision !important;

    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

body h1 *,
main h1 *,
.product-detail h1 *,
.product-info h1 *,
.product-summary h1 *,
.product-title *,
.detail-title * {
    font-family: inherit !important;

    display: inline !important;

    position: static !important;

    margin: 0 !important;
    padding: 0 !important;

    letter-spacing: 0 !important;
    word-spacing: 0 !important;

    transform: none !important;

    opacity: 1 !important;
}

</style>

<script>

(function () {

    var correctName =
        <?= json_encode(
            (string)($product['name'] ?? ''),
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?>;

    if (!correctName) {
        return;
    }

    if (correctName.normalize) {
        correctName = correctName.normalize('NFC');
    }

    function applyFix() {

        var candidates =
            document.querySelectorAll('h1');

        if (!candidates.length) {
            return;
        }

        /*
         * Trang detail hiện tại chỉ có title sản phẩm là h1 lớn.
         * Chọn h1 có text gần tên product nhất.
         */
        var title = null;

        for (
            var i = 0;
            i < candidates.length;
            i++
        ) {

            var text =
                (candidates[i].textContent || '')
                .replace(/\s+/g, '')
                .toLowerCase();

            var compare =
                correctName
                .replace(/\s+/g, '')
                .toLowerCase();

            if (
                text === compare
                || text.indexOf(compare) !== -1
            ) {
                title = candidates[i];
                break;
            }
        }

        if (!title) {
            title = candidates[candidates.length - 1];
        }

        /*
         * Xóa toàn bộ span/letter animation cũ.
         */
        title.replaceChildren(
            document.createTextNode(correctName)
        );

        title.style.setProperty(
            'font-family',
            'Arial, "Segoe UI", "Helvetica Neue", sans-serif',
            'important'
        );

        title.style.setProperty(
            'letter-spacing',
            '0',
            'important'
        );

        title.style.setProperty(
            'word-spacing',
            '0',
            'important'
        );

        title.style.setProperty(
            'font-kerning',
            'none',
            'important'
        );

        title.style.setProperty(
            'font-variant-ligatures',
            'none',
            'important'
        );
    }

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            applyFix
        );

    } else {

        applyFix();
    }

    /*
     * Chạy lại sau JS giao diện cũ.
     */
    setTimeout(applyFix, 100);
    setTimeout(applyFix, 500);
    setTimeout(applyFix, 1500);

})();

</script>

<!-- === HARD PRODUCT TITLE FIX END === -->

<!-- === FINAL PRODUCT VARIANT UI START === -->
<?php
require_once __DIR__ . '/../includes/product_variant_ui.php';

if (
    isset($product)
    && is_array($product)
) {
    fashion_render_variant_ui($product);
}
?>
<!-- === FINAL PRODUCT VARIANT UI END === -->

</body>
</html>
