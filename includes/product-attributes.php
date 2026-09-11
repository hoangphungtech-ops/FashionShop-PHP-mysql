<?php

if (!function_exists('fashion_attr_h')) {

    function fashion_attr_h(?string $value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('fashion_attr_pdo')) {

    function fashion_attr_pdo(): ?PDO
    {
        foreach (
            ['pdo', 'conn', 'db', 'connection']
            as $name
        ) {

            if (
                isset($GLOBALS[$name])
                && $GLOBALS[$name] instanceof PDO
            ) {
                return $GLOBALS[$name];
            }
        }

        foreach ($GLOBALS as $value) {

            if ($value instanceof PDO) {
                return $value;
            }
        }

        $files = [
            __DIR__ . '/db.php',
            __DIR__ . '/../config/database.php',
        ];

        foreach ($files as $file) {

            if (!is_file($file)) {
                continue;
            }

            $loader = static function (
                string $file
            ): ?PDO {

                $result = require $file;

                if ($result instanceof PDO) {
                    return $result;
                }

                foreach (
                    get_defined_vars()
                    as $value
                ) {

                    if ($value instanceof PDO) {
                        return $value;
                    }
                }

                return null;
            };

            try {

                $pdo = $loader($file);

                if ($pdo instanceof PDO) {
                    return $pdo;
                }

            } catch (Throwable $e) {
            }
        }

        return null;
    }
}

if (
    !function_exists(
        'fashion_render_product_attributes'
    )
) {

    function fashion_render_product_attributes(): void
    {
        $id = isset($_GET['id'])
            ? (int)$_GET['id']
            : 0;

        if ($id < 1) {
            return;
        }

        $pdo = fashion_attr_pdo();

        if (!$pdo) {
            return;
        }

        try {

            $stmt = $pdo->prepare(
                'SELECT
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

            $row =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

        } catch (Throwable $e) {

            return;
        }

        if (!$row) {
            return;
        }

        $name =
            trim((string)$row['name']);

        $sizeText =
            trim((string)($row['size'] ?? ''));

        $color =
            trim((string)($row['color'] ?? ''));

        $material =
            trim((string)($row['material'] ?? ''));

        $sizes = [];

        if ($sizeText !== '') {

            foreach (
                explode(',', $sizeText)
                as $size
            ) {

                $size = trim($size);

                if (
                    $size !== ''
                    && !in_array(
                        $size,
                        $sizes,
                        true
                    )
                ) {
                    $sizes[] = $size;
                }
            }
        }

        if (
            !$sizes
            && $color === ''
            && $material === ''
        ) {
            return;
        }

        ?>

<section
    class="fashion-product-attributes"
    id="fashion-product-attributes"
>

    <?php if ($sizes): ?>

        <div class="fashion-attribute-section">

            <div class="fashion-attribute-title">
                Kích cỡ có sẵn
            </div>

            <div class="fashion-size-options">

                <?php foreach ($sizes as $size): ?>

                    <span class="fashion-size-chip">
                        <?= fashion_attr_h($size) ?>
                    </span>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <div class="fashion-product-meta">

        <?php if ($color !== ''): ?>

            <div class="fashion-meta-row">

                <span>Màu sắc</span>

                <strong>
                    <?= fashion_attr_h($color) ?>
                </strong>

            </div>

        <?php endif; ?>

        <?php if ($material !== ''): ?>

            <div class="fashion-meta-row">

                <span>Chất liệu</span>

                <strong>
                    <?= fashion_attr_h($material) ?>
                </strong>

            </div>

        <?php endif; ?>

    </div>

</section>

<style>

.fashion-safe-product-title {
    font-family:
        Georgia,
        "Times New Roman",
        serif !important;

    font-kerning: normal !important;
    font-variant-ligatures: normal !important;

    letter-spacing: -0.025em !important;
    word-spacing: normal !important;

    text-transform: none !important;

    unicode-bidi: plaintext;

    text-rendering: optimizeLegibility;
}

.fashion-product-attributes {
    width: 100%;
    box-sizing: border-box;

    margin: 22px 0 20px;
    padding: 18px 0;

    border-top: 1px solid #dde3de;
    border-bottom: 1px solid #dde3de;

    color: #173b2e;
}

.fashion-attribute-title {
    margin-bottom: 11px;

    color: #4d6157;

    font-size: 12px;
    font-weight: 700;

    letter-spacing: .06em;
    text-transform: uppercase;
}

.fashion-size-options {
    display: flex;
    flex-wrap: wrap;

    gap: 8px;
}

.fashion-size-chip {
    min-width: 42px;
    height: 40px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    box-sizing: border-box;

    padding: 0 13px;

    border: 1px solid #c8d1cb;

    background: #ffffff;

    color: #183d30;

    font-size: 13px;
    font-weight: 700;
}

.fashion-product-meta {
    display: grid;

    gap: 10px;

    margin-top: 18px;
}

.fashion-meta-row {
    display: grid;

    grid-template-columns:
        95px
        minmax(0, 1fr);

    gap: 14px;

    font-size: 13px;
    line-height: 1.5;
}

.fashion-meta-row span {
    color: #718077;
}

.fashion-meta-row strong {
    color: #173b2e;

    font-weight: 600;
}

@media (max-width: 600px) {

    .fashion-product-attributes {
        margin: 18px 0;
    }

}

</style>

<script>

(function () {

    function runFashionProductFix() {

        var productName =
            <?= json_encode(
                $name,
                JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            ) ?>;

        /*
         * =====================================================
         * FIX TEN SAN PHAM
         * =====================================================
         *
         * Lấy thẳng tên UTF-8 đúng từ DB,
         * loại bỏ trường hợp JS/CSS cũ split từng ký tự.
         */

        var headings =
            document.querySelectorAll('h1');

        var productTitle = null;

        headings.forEach(function (heading) {

            var text =
                (heading.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim();

            if (
                text === productName
                || text.indexOf('Quần Tây') !== -1
                || text.indexOf('Áo ') !== -1
                || text.indexOf('Váy') !== -1
                || text.indexOf('Cardigan') !== -1
            ) {
                productTitle = heading;
            }
        });

        if (!productTitle && headings.length) {
            productTitle =
                headings[headings.length - 1];
        }

        if (productTitle) {

            if (
                typeof productName.normalize
                === 'function'
            ) {
                productName =
                    productName.normalize('NFC');
            }

            /*
             * Xóa các span từng chữ nếu code cũ đã split title.
             */
            productTitle.textContent =
                productName;

            productTitle.classList.add(
                'fashion-safe-product-title'
            );
        }

        /*
         * =====================================================
         * CHUYEN SIZE / MAU / CHAT LIEU
         * LEN NGAY TRUOC SO LUONG
         * =====================================================
         */

        var box =
            document.getElementById(
                'fashion-product-attributes'
            );

        if (!box) {
            return;
        }

        var cartButton = null;

        document
            .querySelectorAll(
                'button, input[type="submit"], a'
            )
            .forEach(function (element) {

                var text =
                    (
                        element.textContent
                        || element.value
                        || ''
                    )
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();

                if (
                    text.indexOf(
                        'thêm vào giỏ hàng'
                    ) !== -1
                ) {
                    cartButton = element;
                }
            });

        if (cartButton) {

            var form =
                cartButton.closest('form');

            if (
                form
                && form.parentNode
            ) {

                form.parentNode.insertBefore(
                    box,
                    form
                );

                return;
            }
        }

        /*
         * Fallback: tìm chữ Số lượng.
         */

        var candidates =
            document.querySelectorAll(
                'label, span, strong, p, div'
            );

        var quantityElement = null;

        candidates.forEach(function (element) {

            if (quantityElement) {
                return;
            }

            if (element.children.length > 0) {
                return;
            }

            var text =
                (element.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();

            if (
                text === 'số lượng'
                || text === 'so luong'
            ) {
                quantityElement = element;
            }
        });

        if (
            quantityElement
            && quantityElement.parentElement
            && quantityElement.parentElement.parentElement
        ) {

            var anchor =
                quantityElement.parentElement;

            anchor.parentElement.insertBefore(
                box,
                anchor
            );
        }
    }

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            runFashionProductFix
        );

    } else {

        runFashionProductFix();
    }

})();

</script>

        <?php
    }
}