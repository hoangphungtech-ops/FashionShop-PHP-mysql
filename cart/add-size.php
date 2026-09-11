<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $productId =
        (int)(
            $_POST['product_id']
            ?? $_POST['id']
            ?? 0
        );

    $selectedSize =
        trim(
            (string)(
                $_POST['size']
                ?? ''
            )
        );

    if (
        $productId > 0
        && isset($pdo)
        && $pdo instanceof PDO
    ) {

        $stmt = $pdo->prepare(
            'SELECT size
             FROM products
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            ':id' => $productId,
        ]);

        $sizeRaw =
            trim(
                (string)(
                    $stmt->fetchColumn()
                    ?: ''
                )
            );

        if ($sizeRaw !== '') {

            $allowedSizes = array_values(
                array_filter(
                    array_map(
                        'trim',
                        explode(
                            ',',
                            $sizeRaw
                        )
                    ),
                    static fn ($value) =>
                        $value !== ''
                )
            );

            if (
                $selectedSize === ''
                || !in_array(
                    $selectedSize,
                    $allowedSizes,
                    true
                )
            ) {

                header(
                    'Location: ../products/detail.php?id='
                    . $productId
                    . '&size_error=1'
                );

                exit;
            }

            $_SESSION[
                'cart_selected_size'
            ][$productId] =
                $selectedSize;
        }
    }
}

/*
 * Chạy đúng logic add cart cũ:
 * CSRF + quantity + stock vẫn giữ nguyên.
 */
require __DIR__ . '/add.php';