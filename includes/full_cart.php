<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function fs_h(?string $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function fs_cart_token(): string
{
    if (
        empty($_SESSION['fs_cart_csrf'])
        || !is_string($_SESSION['fs_cart_csrf'])
    ) {
        $_SESSION['fs_cart_csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['fs_cart_csrf'];
}

function fs_cart_verify_token(?string $token): bool
{
    return
        is_string($token)
        && !empty($_SESSION['fs_cart_csrf'])
        && hash_equals(
            $_SESSION['fs_cart_csrf'],
            $token
        );
}

function &fs_cart(): array
{
    if (
        !isset($_SESSION['fs_cart'])
        || !is_array($_SESSION['fs_cart'])
    ) {
        $_SESSION['fs_cart'] = [];
    }

    return $_SESSION['fs_cart'];
}

function fs_cart_key(
    int $productId,
    string $size,
    string $color
): string {
    return hash(
        'sha256',
        $productId
        . '|'
        . mb_strtolower(trim($size), 'UTF-8')
        . '|'
        . mb_strtolower(trim($color), 'UTF-8')
    );
}

function fs_csv_values(?string $text): array
{
    $text = trim((string)$text);

    if ($text === '') {
        return [];
    }

    $values = [];

    foreach (explode(',', $text) as $value) {

        $value = trim($value);

        if (
            $value !== ''
            && !in_array(
                $value,
                $values,
                true
            )
        ) {
            $values[] = $value;
        }
    }

    return $values;
}

function fs_cart_count(): int
{
    $count = 0;

    foreach (fs_cart() as $item) {
        $count += (int)$item['quantity'];
    }

    return $count;
}

function fs_cart_total(): float
{
    $total = 0;

    foreach (fs_cart() as $item) {

        $total +=
            (float)$item['price']
            *
            (int)$item['quantity'];
    }

    return $total;
}

function fs_current_user_id(): ?int
{
    if (
        isset($_SESSION['user']['id'])
        && is_numeric($_SESSION['user']['id'])
    ) {
        return (int)$_SESSION['user']['id'];
    }

    if (
        isset($_SESSION['user_id'])
        && is_numeric($_SESSION['user_id'])
    ) {
        return (int)$_SESSION['user_id'];
    }

    return null;
}