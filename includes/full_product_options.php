<?php

declare(strict_types=1);

function fs_render_full_product_options(
    array $product
): void {

    $sizes = fs_csv_values(
        $product['size'] ?? ''
    );

    $colors = fs_csv_values(
        $product['color'] ?? ''
    );

    $material =
        trim(
            (string)(
                $product['material']
                ?? ''
            )
        );

    $productId =
        (int)($product['id'] ?? 0);

    $correctName =
        (string)($product['name'] ?? '');

    ?>

<section
    class="fs-product-options"
    id="fs-product-options"
    data-product-id="<?= $productId ?>"
>

    <div class="fs-option-group">

        <div class="fs-option-header">

            <strong>Kích cỡ</strong>

            <span id="fs-size-current">
                Chưa chọn
            </span>

        </div>

        <div class="fs-option-buttons">

            <?php foreach ($sizes as $size): ?>

                <button
                    type="button"
                    class="fs-option-button fs-size-button"
                    data-size="<?= fs_h($size) ?>"
                    aria-pressed="false"
                >
                    <?= fs_h($size) ?>
                </button>

            <?php endforeach; ?>

        </div>

        <p
            class="fs-option-error"
            id="fs-size-error"
            hidden
        >
            Vui lòng chọn kích cỡ trước khi thêm sản phẩm vào giỏ hàng.
        </p>

    </div>

    <?php if ($colors): ?>

        <div class="fs-option-group">

            <div class="fs-option-header">

                <strong>Màu sắc</strong>

                <span id="fs-color-current">
                    <?= count($colors) === 1
                        ? fs_h($colors[0])
                        : 'Chưa chọn'
                    ?>
                </span>

            </div>

            <div class="fs-option-buttons">

                <?php foreach ($colors as $color): ?>

                    <button
                        type="button"
                        class="fs-option-button fs-color-button"
                        data-color="<?= fs_h($color) ?>"
                        aria-pressed="false"
                    >
                        <?= fs_h($color) ?>
                    </button>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($material !== ''): ?>

        <div class="fs-product-material">

            <span>Chất liệu</span>

            <strong>
                <?= fs_h($material) ?>
            </strong>

        </div>

    <?php endif; ?>

</section>

<style>

.fs-product-options {
    width: 100%;
    box-sizing: border-box;

    margin: 22px 0 20px;
    padding: 20px 0;

    border-top: 1px solid #dce2da;
    border-bottom: 1px solid #dce2da;

    color: #173b2e;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.fs-option-group + .fs-option-group {
    margin-top: 20px;
}

.fs-option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 15px;

    margin-bottom: 11px;
}

.fs-option-header strong {
    font-size: 13px;
    font-weight: 700;

    letter-spacing: .04em;
    text-transform: uppercase;
}

.fs-option-header span {
    color: #738078;
    font-size: 12px;
}

.fs-option-buttons {
    display: flex;
    flex-wrap: wrap;

    gap: 9px;
}

.fs-option-button {
    min-width: 46px;
    min-height: 42px;

    padding: 0 14px;

    border: 1px solid #cbd4ce;

    background: #fff;

    color: #173b2e;

    font: inherit;
    font-size: 13px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background .16s ease,
        color .16s ease,
        border-color .16s ease,
        transform .16s ease;
}

.fs-option-button:hover,
.fs-option-button:focus-visible {
    border-color: #173b2e;
}

.fs-option-button.is-selected {
    border-color: #173b2e;

    background: #173b2e;

    color: #fff;

    transform: translateY(-1px);
}

.fs-option-error {
    margin: 9px 0 0;

    color: #9b493e;

    font-size: 12px;
    font-weight: 600;
}

.fs-product-material {
    display: grid;

    grid-template-columns:
        95px
        minmax(0, 1fr);

    gap: 15px;

    margin-top: 18px;

    font-size: 13px;
    line-height: 1.5;
}

.fs-product-material span {
    color: #738078;
}

.fs-product-material strong {
    font-weight: 600;
}

.fs-product-title-fixed {
    font-family:
        "Segoe UI",
        Arial,
        sans-serif !important;

    letter-spacing: 0 !important;
    word-spacing: normal !important;
    font-kerning: normal !important;
    text-transform: none !important;
}

@media (max-width: 600px) {

    .fs-product-options {
        margin: 18px 0;
        padding: 16px 0;
    }

}

</style>

<script>

(function () {

    var correctName =
        <?= json_encode(
            $correctName,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?>;

    var productId =
        <?= $productId ?>;

    function init() {

        var options =
            document.getElementById(
                'fs-product-options'
            );

        if (!options) {
            return;
        }

        /*
         * Fix tiêu đề tiếng Việt.
         */
        var headings =
            document.querySelectorAll('h1');

        if (headings.length) {

            var title =
                headings[
                    headings.length - 1
                ];

            if (correctName) {

                if (
                    typeof correctName.normalize
                    === 'function'
                ) {
                    correctName =
                        correctName.normalize('NFC');
                }

                title.textContent =
                    correctName;

                title.classList.add(
                    'fs-product-title-fixed'
                );
            }
        }

        /*
         * Tìm nút add cart hiện tại.
         */
        var addButton = null;

        document
            .querySelectorAll(
                'button, input[type="submit"]'
            )
            .forEach(function (element) {

                if (addButton) {
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
                    addButton = element;
                }
            });

        if (!addButton) {
            return;
        }

        var form =
            addButton.closest('form');

        if (!form) {
            return;
        }

        /*
         * Di chuyển options lên ngay trước phần quantity.
         */
        form.insertBefore(
            options,
            form.firstChild
        );

        form.method = 'post';

        form.action =
            '/cart/full/add.php';

        function hidden(
            name,
            value
        ) {

            var input =
                form.querySelector(
                    'input[name="' + name + '"]'
                );

            if (!input) {

                input =
                    document.createElement(
                        'input'
                    );

                input.type = 'hidden';
                input.name = name;

                form.appendChild(input);
            }

            input.value = value;

            return input;
        }

        hidden(
            'product_id',
            String(productId)
        );

        var sizeInput =
            hidden('size', '');

        var colorInput =
            hidden('color', '');

        var sizeButtons =
            options.querySelectorAll(
                '.fs-size-button'
            );

        var colorButtons =
            options.querySelectorAll(
                '.fs-color-button'
            );

        function selectButton(
            buttons,
            button
        ) {

            buttons.forEach(
                function (item) {

                    item.classList.remove(
                        'is-selected'
                    );

                    item.setAttribute(
                        'aria-pressed',
                        'false'
                    );
                }
            );

            button.classList.add(
                'is-selected'
            );

            button.setAttribute(
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

                        var current =
                            document.getElementById(
                                'fs-size-current'
                            );

                        if (current) {
                            current.textContent =
                                'Đã chọn: '
                                + sizeInput.value;
                        }

                        var error =
                            document.getElementById(
                                'fs-size-error'
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

                        var current =
                            document.getElementById(
                                'fs-color-current'
                            );

                        if (current) {
                            current.textContent =
                                'Đã chọn: '
                                + colorInput.value;
                        }
                    }
                );
            }
        );

        /*
         * Một màu duy nhất -> tự dùng màu đó.
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

                if (!sizeInput.value) {

                    event.preventDefault();

                    var error =
                        document.getElementById(
                            'fs-size-error'
                        );

                    if (error) {
                        error.hidden = false;
                    }

                    options.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    return;
                }

                if (
                    colorButtons.length > 1
                    && !colorInput.value
                ) {

                    event.preventDefault();

                    alert(
                        'Vui lòng chọn màu sắc.'
                    );
                }
            }
        );
    }

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();
    }

})();

</script>

    <?php
}