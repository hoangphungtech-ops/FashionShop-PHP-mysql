<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/upload.php';

function app_base_url(): string
{
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(dirname(__DIR__));

    if ($documentRoot === false || $projectRoot === false) {
        return '';
    }

    $documentRoot = str_replace('\\', '/', $documentRoot);
    $projectRoot = str_replace('\\', '/', $projectRoot);

    if (!str_starts_with(strtolower($projectRoot), strtolower(rtrim($documentRoot, '/')))) {
        return '';
    }

    $relativePath = substr($projectRoot, strlen(rtrim($documentRoot, '/')));

    return '/' . trim((string) $relativePath, '/');
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim(app_base_url(), '/');
    $path = ltrim($path, '/');

    return $baseUrl . ($path === '' ? '/' : '/' . $path);
}
