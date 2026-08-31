<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }

    if (!is_scalar($value) && !$value instanceof Stringable) {
        return '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post_request(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function csrf_token(): string
{
    start_secure_session();

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_validate(?string $token): bool
{
    start_secure_session();

    $storedToken = $_SESSION['_csrf_token'] ?? null;

    return is_string($token)
        && is_string($storedToken)
        && hash_equals($storedToken, $token);
}

function csrf_protect(): void
{
    $token = $_POST['_csrf_token'] ?? null;

    if (!is_post_request() || !is_string($token) || !csrf_validate($token)) {
        http_response_code(419);
        exit('Yêu cầu đã hết hạn hoặc không hợp lệ. Vui lòng tải lại trang.');
    }
}

function is_safe_redirect_target(string $target): bool
{
    $target = trim($target);

    if ($target === '' || str_contains($target, "\r") || str_contains($target, "\n")) {
        return false;
    }

    if (str_starts_with($target, '//') || str_contains($target, '\\')) {
        return false;
    }

    $parts = parse_url($target);

    return $parts !== false
        && !isset($parts['scheme'])
        && !isset($parts['host'])
        && !isset($parts['user']);
}

function safe_redirect(string $target, string $fallback = 'index.php', int $status = 302): never
{
    if (!is_safe_redirect_target($target)) {
        $target = is_safe_redirect_target($fallback) ? $fallback : 'index.php';
    }

    header('Location: ' . $target, true, $status);
    exit;
}

function sanitize_text(mixed $value, int $maxLength = 255): string
{
    $text = trim(str_replace("\0", '', (string) $value));

    if ($maxLength < 1) {
        return '';
    }

    return function_exists('mb_substr')
        ? mb_substr($text, 0, $maxLength, 'UTF-8')
        : substr($text, 0, $maxLength);
}

function validate_email(mixed $value): ?string
{
    $email = trim((string) $value);
    $validated = filter_var($email, FILTER_VALIDATE_EMAIL);

    return is_string($validated) ? $validated : null;
}

function input_int(
    array $source,
    string $key,
    int $minimum = 1,
    int $maximum = PHP_INT_MAX
): ?int {
    if (!array_key_exists($key, $source)) {
        return null;
    }

    $value = filter_var($source[$key], FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => $minimum,
            'max_range' => $maximum,
        ],
    ]);

    return is_int($value) ? $value : null;
}
