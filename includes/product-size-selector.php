<?php

if (
    !function_exists(
        'fashion_render_size_selector'
    )
) {

    function fashion_render_size_selector(): void
    {
        global $pdo, $product;

        $productId = 0;

        if (
            is_array($product)
            && isset($product['id'])
        ) {
            $productId =
                (int)$product['id'];
        }

        if (
            $productId < 1
            && isset($_GET['id'])
        ) {
            $productId =
                (int)$_GET['id'];
        }

        if ($productId < 1) {
            return;
        }

        if (
            !isset($pdo)
            || !($pdo instanceof PDO)
        ) {
            return;
        }

        try {

            $stmt = $pdo->prepare(
                'SELECT
                    size,
                    color,
                    material
                 FROM products
                 WHERE id = :id
                 LIMIT 1'
            );

            $stmt->execute([
                ':id' => $productId,
            ]);

            $data =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

        } catch (Throwable $e) {

            return;
        }

        if (!$data) {
            return;
        }

        $sizeRaw =
            trim(
                (string)(
                    $data['size']
                    ?? ''
                )
            );

        $color =
            trim(
                (string)(
                    $data['color']
                    ?? ''
                )
            );

        $material =
            trim(
                (string)(
                    $data['material']
                    ?? ''
                )
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
    id="fashion-size-selector"
    class="fashion-size-selector"
    data-has-size="<?= $sizes ? '1' : '0' ?>"
>

    <?php if ($sizes): ?>

        <div class="fashion-size-head">

            <strong>
                Chọn kích cỡ
            </strong>

            <span id="fashion-size-current">
                Chưa chọn
            </span>

        </div>

        <div class="fashion-size-buttons">

            <?php foreach ($sizes as $size): ?>

                <button
                    type="button"
                    class="fashion-size-button"
                    data-fashion-size="<?= htmlspecialchars(
                        $size,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    aria-pressed="false"
                >
                    <?= htmlspecialchars(
                        $size,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </button>

            <?php endforeach; ?>

        </div>

        <div
            id="fashion-size-error"
            class="fashion-size-error"
            hidden
        >
            Vui lòng chọn kích cỡ trước khi thêm vào giỏ hàng.
        </div>

    <?php endif; ?>

    <?php if (
        $color !== ''
        || $material !== ''
    ): ?>

        <div class="fashion-size-meta">

            <?php if ($color !== ''): ?>

                <div>
                    <span>Màu sắc</span>

                    <strong>
                        <?= htmlspecialchars(
                            $color,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

            <?php endif; ?>

            <?php if ($material !== ''): ?>

                <div>
                    <span>Chất liệu</span>

                    <strong>
                        <?= htmlspecialchars(
                            $material,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>

<style>

.fashion-size-selector {
    width: 100%;
    box-sizing: border-box;

    margin: 22px 0 20px;
    padding: 18px 0;

    border-top: 1px solid #dde3de;
    border-bottom: 1px solid #dde3de;
}

.fashion-size-head {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 12px;
}

.fashion-size-head strong {
    color: #173b2e;

    font-size: 13px;
    font-weight: 700;
}

.fashion-size-head span {
    color: #79827d;

    font-size: 12px;
}

.fashion-size-buttons {
    display: flex;
    flex-wrap: wrap;

    gap: 9px;
}

.fashion-size-button {
    min-width: 46px;
    height: 42px;

    padding: 0 14px;

    border: 1px solid #cbd3ce;

    background: #ffffff;

    color: #173b2e;

    font: inherit;

    font-size: 13px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background .18s ease,
        color .18s ease,
        border-color .18s ease,
        transform .18s ease;
}

.fashion-size-button:hover {
    border-color: #173b2e;
}

.fashion-size-button.is-selected {
    border-color: #173b2e;

    background: #173b2e;

    color: #ffffff;

    transform: translateY(-1px);
}

.fashion-size-error {
    margin-top: 10px;

    color: #a33d32;

    font-size: 12px;
    font-weight: 600;
}

.fashion-size-meta {
    display: grid;

    gap: 9px;

    margin-top: 17px;
}

.fashion-size-meta > div {
    display: grid;

    grid-template-columns:
        92px
        minmax(0, 1fr);

    gap: 14px;

    font-size: 13px;
    line-height: 1.5;
}

.fashion-size-meta span {
    color: #78817c;
}

.fashion-size-meta strong {
    color: #173b2e;
    font-weight: 600;
}

</style>

<script>

(function () {

    function initialiseSizeSelector() {

        var selector =
            document.getElementById(
                'fashion-size-selector'
            );

        if (!selector) {
            return;
        }

        /*
         * Ẩn các block size thử nghiệm cũ
         * để không hiện trùng.
         */
        document
            .querySelectorAll(
                '.fashion-product-attributes, #fashion-product-features'
            )
            .forEach(function (oldBox) {

                if (oldBox !== selector) {
                    oldBox.style.display =
                        'none';
                }
            });

        /*
         * Tìm form Add Cart hiện tại.
         */
        var cartButton = null;

        document
            .querySelectorAll(
                'button, input[type="submit"]'
            )
            .forEach(function (element) {

                if (cartButton) {
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
                    cartButton = element;
                }
            });

        if (!cartButton) {
            return;
        }

        var form =
            cartButton.closest('form');

        if (!form) {
            return;
        }

        /*
         * Đưa selector vào ngay trước phần số lượng.
         */
        form.insertBefore(
            selector,
            form.firstChild
        );

        /*
         * Chỉ đổi qua bridge size.
         * Bridge sau đó chạy cart/add.php cũ.
         */
        if (
            selector.dataset.hasSize === '1'
        ) {

            form.setAttribute(
                'action',
                '../cart/add-size.php'
            );
        }

        var hiddenSize =
            form.querySelector(
                'input[name="size"]'
            );

        if (!hiddenSize) {

            hiddenSize =
                document.createElement(
                    'input'
                );

            hiddenSize.type = 'hidden';
            hiddenSize.name = 'size';
            hiddenSize.value = '';

            form.appendChild(hiddenSize);
        }

        var current =
            document.getElementById(
                'fashion-size-current'
            );

        var error =
            document.getElementById(
                'fashion-size-error'
            );

        var buttons =
            selector.querySelectorAll(
                '.fashion-size-button'
            );

        buttons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        buttons.forEach(
                            function (other) {

                                other.classList.remove(
                                    'is-selected'
                                );

                                other.setAttribute(
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

                        hiddenSize.value =
                            button.dataset.fashionSize;

                        if (current) {
                            current.textContent =
                                'Đã chọn: '
                                + hiddenSize.value;
                        }

                        if (error) {
                            error.hidden = true;
                        }
                    }
                );
            }
        );

        form.addEventListener(
            'submit',
            function (event) {

                if (
                    selector.dataset.hasSize === '1'
                    && !hiddenSize.value
                ) {

                    event.preventDefault();

                    if (error) {
                        error.hidden = false;
                    }

                    selector.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        );
    }

    if (
        document.readyState
        === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initialiseSizeSelector
        );

    } else {

        initialiseSizeSelector();
    }

})();

</script>

        <?php
    }
}