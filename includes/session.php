<?php

declare(strict_types=1);

/**
 * Detect HTTPS without trusting client-controlled proxy headers.
 */
function is_https_request(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

    return ($https !== '' && $https !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/**
 * Apply secure cookie defaults before the first session_start() call.
 */
function configure_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    configure_secure_session();

    if (!session_start()) {
        throw new RuntimeException('Không thể khởi tạo phiên làm việc.');
    }
}

/**
 * Call immediately after a successful login.
 */
function regenerate_session_id_after_login(): void
{
    start_secure_session();
    session_regenerate_id(true);
}

function destroy_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
