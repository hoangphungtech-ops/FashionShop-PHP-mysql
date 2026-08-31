<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function current_user(): ?array
{
    start_secure_session();
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && ($user['role'] ?? null) === 'admin';
}

function require_login(?string $loginUrl = null): void
{
    if (!is_logged_in()) {
        safe_redirect($loginUrl ?? app_url('auth/login.php'));
    }
}

function require_admin(?string $loginUrl = null): void
{
    require_login($loginUrl);

    if (!is_admin()) {
        http_response_code(403);
        exit('Bạn không có quyền truy cập khu vực quản trị.');
    }
}
