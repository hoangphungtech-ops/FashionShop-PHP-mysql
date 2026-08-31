<?php

declare(strict_types=1);

if (!defined('FASHION_SHOP_ROOT')) {
    define('FASHION_SHOP_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/helpers.php';

function app_environment(): string
{
    $configuredEnvironment = strtolower(trim((string) getenv('APP_ENV')));

    if (in_array($configuredEnvironment, ['development', 'testing', 'production'], true)) {
        return $configuredEnvironment;
    }

    if (PHP_SAPI === 'cli') {
        return 'development';
    }

    $serverAddress = (string) ($_SERVER['SERVER_ADDR'] ?? '');
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $loopbackAddresses = ['127.0.0.1', '::1'];

    return in_array($serverAddress, $loopbackAddresses, true)
        && in_array($remoteAddress, $loopbackAddresses, true)
        ? 'development'
        : 'production';
}

function is_production(): bool
{
    return app_environment() === 'production';
}

function send_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', is_production() ? '0' : '1');
ini_set('display_startup_errors', is_production() ? '0' : '1');

$logDirectory = FASHION_SHOP_ROOT . '/storage/logs';

if (is_dir($logDirectory) && is_writable($logDirectory)) {
    ini_set('error_log', $logDirectory . '/php-error.log');
}

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        '[uncaught] %s in %s:%d\n%s',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    http_response_code(500);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $exception . PHP_EOL);
        return;
    }

    if (!is_production()) {
        echo '<h1>Lỗi hệ thống</h1><pre>' . e((string) $exception) . '</pre>';
        return;
    }

    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Lỗi hệ thống</title></head><body>'
        . '<main style="max-width:680px;margin:80px auto;padding:24px;font-family:Arial,sans-serif">'
        . '<h1>Đã có lỗi xảy ra</h1><p>Vui lòng thử lại sau hoặc liên hệ quản trị viên.</p>'
        . '</main></body></html>';
});

start_secure_session();
send_security_headers();
