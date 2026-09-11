<?php

declare(strict_types=1);

if (!function_exists('fashion_feature_h')) {

    function fashion_feature_h(?string $value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('fashion_feature_pdo')) {

    function fashion_feature_pdo(): ?PDO
    {
        foreach (['pdo', 'conn', 'db', 'connection'] as $name) {

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

            $loader = static function (string $file): ?PDO {

                $result = require $file;

                if ($result instanceof PDO) {
                    return $result;
                }

                foreach (get_defined_vars() as $value) {

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

if (!function_exists('fashion_render_product_features')) {

    function fashion_render_product_features(
        mixed $product = null
    ): void {

        $productId = 0;

        if (
            is_array($product)
            && isset($product['id'])
        ) {
            $productId = (int)$product['id'];
        }

        if (
            $productId < 1
            && isset($_GET['id'])
        ) {
            $productId = (int)$_GET['id'];
        }

        if ($productId < 1) {
            return;
        }

        $pdo = fashion_feature_pdo();

        if (!$pdo) {
            return;
        }

        try {

            $stmt = $pdo->prepare(
                'SELECT size, color, material
                 FROM products
                 WHERE id = :id
                 LIMIT 1'
            );

            $stmt->execute([
                ':id' => $productId,
            ]);

            $features = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {

            return;
        }

        if (!$features) {
            return;
        }

        $sizeRaw = trim(
            (string)($features['size'] ?? '')
        );

        $color = trim(
            (string)($features['color'] ?? '')
        );

        $material = trim(
            (string)($features['material'] ?? '')
        );

        $sizes = [];

        if ($sizeRaw !== '') {

            foreach (
                explode(',', $sizeRaw)
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
            class="fashion-product-features"
            id="fashion-product-features"
            aria-label="Thông tin sản phẩm"
        >

            <?php if ($sizes): ?>

                <div class="fashion-feature-block">

                    <div class="fashion-feature-heading">
                        Kích cỡ
                    </div>

                    <div
                        class="fashion-size-list"
                        aria-label="Các kích cỡ hiện có"
                    >

                        <?php foreach ($sizes as $size): ?>

                            <span class="fashion-size-item">
                                <?= fashion_feature_h($size) ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <?php if ($color !== '' || $material !== ''): ?>

                <div class="fashion-feature-meta">

                    <?php if ($color !== ''): ?>

                        <div class="fashion-feature-row">

                            <span class="fashion-feature-label">
                                Màu sắc
                            </span>

                            <strong>
                                <?= fashion_feature_h($color) ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                    <?php if ($material !== ''): ?>

                        <div class="fashion-feature-row">

                            <span class="fashion-feature-label">
                                Chất liệu
                            </span>

                            <strong>
                                <?= fashion_feature_h($material) ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

        <style>

        .fashion-product-features {
            width: 100%;
            box-sizing: border-box;
            margin: 24px 0;
            padding: 22px 0;
            border-top: 1px solid #e4e7e2;
            border-bottom: 1px solid #e4e7e2;
            color: #23352d;
        }

        .fashion-feature-block + .fashion-feature-meta {
            margin-top: 22px;
        }

        .fashion-feature-heading {
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #53645b;
        }

        .fashion-size-list {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .fashion-size-item {
            min-width: 44px;
            height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            box-sizing: border-box;

            padding: 0 13px;

            border: 1px solid #cfd6d1;
            background: #fff;

            color: #26362e;

            font-size: 13px;
            font-weight: 700;
        }

        .fashion-feature-meta {
            display: grid;
            gap: 12px;
        }

        .fashion-feature-row {
            display: grid;
            grid-template-columns: 105px minmax(0, 1fr);
            gap: 16px;
            align-items: start;

            font-size: 14px;
            line-height: 1.55;
        }

        .fashion-feature-label {
            color: #778078;
        }

        .fashion-feature-row strong {
            color: #27382f;
            font-weight: 600;
        }

        @media (max-width: 600px) {

            .fashion-product-features {
                margin: 20px 0;
                padding: 18px 0;
            }

            .fashion-feature-row {
                grid-template-columns: 90px minmax(0, 1fr);
            }
        }

        </style>

        <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                var box =
                    document.getElementById(
                        'fashion-product-features'
                    );

                if (!box) {
                    return;
                }

                /*
                 * Cố gắng đưa block lên ngay khu vực
                 * thông tin sản phẩm, thay vì nằm cuối trang.
                 */
                var selectors = [
                    '.product-info',
                    '.product-detail__info',
                    '.product-detail-info',
                    '.detail-info',
                    '.product-content',
                    '.product-summary'
                ];

                var target = null;

                for (
                    var i = 0;
                    i < selectors.length;
                    i++
                ) {

                    target =
                        document.querySelector(
                            selectors[i]
                        );

                    if (target) {
                        break;
                    }
                }

                if (!target) {
                    return;
                }

                var action =
                    target.querySelector(
                        'form, .product-actions, .add-to-cart'
                    );

                if (action) {

                    target.insertBefore(
                        box,
                        action
                    );

                    return;
                }

                target.appendChild(box);
            }
        );

        </script>
        <?php
    }
}