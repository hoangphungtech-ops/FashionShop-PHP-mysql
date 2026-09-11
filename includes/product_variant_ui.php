<?php

declare(strict_types=1);

if (!function_exists('fashion_variant_h')) {

    function fashion_variant_h(?string $value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('fashion_variant_csv')) {

    function fashion_variant_csv(?string $value): array
    {
        $value = trim((string)$value);

        if ($value === '') {
            return [];
        }

        $result = [];

        foreach (explode(',', $value) as $item) {

            $item = trim($item);

            if (
                $item !== ''
                && !in_array(
                    $item,
                    $result,
                    true
                )
            ) {
                $result[] = $item;
            }
        }

        return $result;
    }
}

if (!function_exists('fashion_variant_pdo')) {

    function fashion_variant_pdo(): ?PDO
    {
        global $pdo, $conn, $db;

        foreach ([$pdo ?? null, $conn ?? null, $db ?? null] as $candidate) {

            if ($candidate instanceof PDO) {
                return $candidate;
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

                $candidate = $loader($file);

                if ($candidate instanceof PDO) {
                    return $candidate;
                }

            } catch (Throwable $e) {
            }
        }

        return null;
    }
}

if (!function_exists('fashion_render_variant_ui')) {

    function fashion_render_variant_ui(array $product): void
    {
        /*
         * QUAN TRỌNG:
         * Không phụ thuộc SELECT của detail.php có lấy size/color/material hay không.
         * Luôn đọc lại 3 field từ DB bằng product ID.
         */

        $productId = (int)($product['id'] ?? 0);

        $sizeRaw = trim(
            (string)($product['size'] ?? '')
        );

        $colorRaw = trim(
            (string)($product['color'] ?? '')
        );

        $material = trim(
            (string)($product['material'] ?? '')
        );

        if ($productId > 0) {

            $pdo = fashion_variant_pdo();

            if ($pdo instanceof PDO) {

                try {

                    $pdo->exec('SET NAMES utf8mb4');

                    $stmt = $pdo->prepare(
                        'SELECT size, color, material
                         FROM products
                         WHERE id = :id
                         LIMIT 1'
                    );

                    $stmt->execute([
                        ':id' => $productId,
                    ]);

                    $dbFeatures = $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );

                    if ($dbFeatures) {

                        $sizeRaw = trim(
                            (string)($dbFeatures['size'] ?? '')
                        );

                        $colorRaw = trim(
                            (string)($dbFeatures['color'] ?? '')
                        );

                        $material = trim(
                            (string)($dbFeatures['material'] ?? '')
                        );
                    }

                } catch (Throwable $e) {
                }
            }
        }

        /*
         * KHÔNG tự sinh Freesize.
         */
        $sizes = fashion_variant_csv($sizeRaw);
        $colors = fashion_variant_csv($colorRaw);

        if (
            !$sizes
            && !$colors
            && $material === ''
        ) {
            return;
        }

        ?>

<section
    class="fashion-variant-ui"
    id="fashion-variant-ui"
>

    <?php if ($sizes): ?>

        <div class="fashion-variant-group">

            <div class="fashion-variant-head">

                <strong>
                    Kích cỡ
                </strong>

                <span id="fashion-selected-size-text">
                    Chưa chọn
                </span>

            </div>

            <div class="fashion-variant-options">

                <?php foreach ($sizes as $size): ?>

                    <button
                        type="button"
                        class="fashion-variant-button fashion-size-button"
                        data-size="<?= fashion_variant_h($size) ?>"
                        aria-pressed="false"
                    >
                        <?= fashion_variant_h($size) ?>
                    </button>

                <?php endforeach; ?>

            </div>

            <div
                id="fashion-size-error"
                class="fashion-variant-error"
                hidden
            >
                Vui lòng chọn kích cỡ trước khi thêm sản phẩm vào giỏ hàng.
            </div>

        </div>

    <?php endif; ?>

    <?php if ($colors): ?>

        <div class="fashion-variant-group">

            <div class="fashion-variant-head">

                <strong>
                    Màu sắc
                </strong>

                <span id="fashion-selected-color-text">
                    <?= count($colors) === 1
                        ? fashion_variant_h($colors[0])
                        : 'Chưa chọn'
                    ?>
                </span>

            </div>

            <div class="fashion-variant-options">

                <?php foreach ($colors as $color): ?>

                    <button
                        type="button"
                        class="fashion-variant-button fashion-color-button"
                        data-color="<?= fashion_variant_h($color) ?>"
                        aria-pressed="false"
                    >
                        <?= fashion_variant_h($color) ?>
                    </button>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($material !== ''): ?>

        <div class="fashion-material-row">

            <span>
                Chất liệu
            </span>

            <strong>
                <?= fashion_variant_h($material) ?>
            </strong>

        </div>

    <?php endif; ?>

</section>

<style>

/*
 * Chỉ style component mới.
 * Không thay CSS global.
 */

.fashion-variant-ui {
    width: 100%;
    box-sizing: border-box;

    margin: 22px 0 18px;
    padding: 18px 0;

    border-top: 1px solid #dce2da;
    border-bottom: 1px solid #dce2da;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.fashion-variant-group + .fashion-variant-group {
    margin-top: 18px;
}

.fashion-variant-head {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 16px;

    margin-bottom: 11px;
}

.fashion-variant-head strong {
    font-size: 13px;
    font-weight: 700;

    letter-spacing: .04em;

    text-transform: uppercase;
}

.fashion-variant-head span {
    color: #788179;

    font-size: 12px;
}

.fashion-variant-options {
    display: flex;
    flex-wrap: wrap;

    gap: 8px;
}

.fashion-variant-button {
    min-width: 45px;
    min-height: 41px;

    padding: 0 13px;

    border: 1px solid #cbd3ce;

    background: #fff;

    color: #173b2e;

    font: inherit;
    font-size: 13px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background .16s ease,
        color .16s ease,
        border-color .16s ease;
}

.fashion-variant-button:hover,
.fashion-variant-button:focus-visible {
    border-color: #173b2e;
}

.fashion-variant-button.is-selected {
    border-color: #173b2e;

    background: #173b2e;

    color: #fff;
}

.fashion-variant-error {
    margin-top: 9px;

    color: #97483e;

    font-size: 12px;
    font-weight: 600;
}

.fashion-material-row {
    display: grid;

    grid-template-columns:
        95px
        minmax(0, 1fr);

    gap: 14px;

    margin-top: 17px;

    font-size: 13px;
}

.fashion-material-row span {
    color: #788179;
}

.fashion-material-row strong {
    font-weight: 600;
}

</style>

<script>

(function () {

    function initialiseFashionVariants() {

        var box =
            document.getElementById(
                'fashion-variant-ui'
            );

        if (!box) {
            return;
        }

        var submitButton = null;

        document
            .querySelectorAll(
                'button, input[type="submit"]'
            )
            .forEach(function (element) {

                if (submitButton) {
                    return;
                }

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
                    submitButton = element;
                }
            });

        if (!submitButton) {
            return;
        }

        var form =
            submitButton.closest('form');

        if (!form) {
            return;
        }

        /*
         * Đưa component vào đúng form hiện tại,
         * không thay action/method.
         */
        form.insertBefore(
            box,
            form.firstChild
        );

        function getHidden(name) {

            var input =
                form.querySelector(
                    'input[name="' + name + '"]'
                );

            if (!input) {

                input =
                    document.createElement('input');

                input.type = 'hidden';
                input.name = name;
                input.value = '';

                form.appendChild(input);
            }

            return input;
        }

        var sizeInput = getHidden('size');
        var colorInput = getHidden('color');

        var sizeButtons =
            box.querySelectorAll(
                '.fashion-size-button'
            );

        var colorButtons =
            box.querySelectorAll(
                '.fashion-color-button'
            );

        function selectButton(
            buttons,
            selected
        ) {

            buttons.forEach(function (button) {

                button.classList.remove(
                    'is-selected'
                );

                button.setAttribute(
                    'aria-pressed',
                    'false'
                );
            });

            selected.classList.add(
                'is-selected'
            );

            selected.setAttribute(
                'aria-pressed',
                'true'
            );
        }

        sizeButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        selectButton(
                            sizeButtons,
                            button
                        );

                        sizeInput.value =
                            button.dataset.size;

                        var text =
                            document.getElementById(
                                'fashion-selected-size-text'
                            );

                        if (text) {

                            text.textContent =
                                'Đã chọn: '
                                + sizeInput.value;
                        }

                        var error =
                            document.getElementById(
                                'fashion-size-error'
                            );

                        if (error) {
                            error.hidden = true;
                        }
                    }
                );
            }
        );

        colorButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        selectButton(
                            colorButtons,
                            button
                        );

                        colorInput.value =
                            button.dataset.color;

                        var text =
                            document.getElementById(
                                'fashion-selected-color-text'
                            );

                        if (text) {

                            text.textContent =
                                'Đã chọn: '
                                + colorInput.value;
                        }
                    }
                );
            }
        );

        /*
         * Một màu duy nhất:
         * dùng chính màu thật trong DB.
         */
        if (colorButtons.length === 1) {

            colorInput.value =
                colorButtons[0].dataset.color;

            selectButton(
                colorButtons,
                colorButtons[0]
            );
        }

        form.addEventListener(
            'submit',
            function (event) {

                if (
                    sizeButtons.length > 0
                    && sizeInput.value === ''
                ) {

                    event.preventDefault();

                    var error =
                        document.getElementById(
                            'fashion-size-error'
                        );

                    if (error) {
                        error.hidden = false;
                    }

                    box.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    return;
                }

                if (
                    colorButtons.length > 1
                    && colorInput.value === ''
                ) {

                    event.preventDefault();

                    alert(
                        'Vui lòng chọn màu sắc.'
                    );
                }
            }
        );
    }

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            initialiseFashionVariants
        );

    } else {

        initialiseFashionVariants();
    }

})();

</script>

        <?php
    }
}