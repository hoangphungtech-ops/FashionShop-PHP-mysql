<?php

declare(strict_types=1);

// Copy this file to database.php, then edit the fallback values or set DB_* variables.
$readEnvironment = static function (string $key, string $fallback): string {
    $value = getenv($key);

    return is_string($value) && $value !== '' ? $value : $fallback;
};

$port = filter_var(
    $readEnvironment('DB_PORT', '3306'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]]
);

$databaseConfig = [
    'host' => $readEnvironment('DB_HOST', 'localhost'),
    'port' => is_int($port) ? $port : 3306,
    'name' => $readEnvironment('DB_NAME', 'fashion_shop'),
    'username' => $readEnvironment('DB_USERNAME', 'root'),
    'password' => $readEnvironment('DB_PASSWORD', 'your_mysql_password'),
];

// Keep legacy variables available to older modules.
$host = $databaseConfig['host'];
$dbname = $databaseConfig['name'];
$username = $databaseConfig['username'];
$password = $databaseConfig['password'];

require __DIR__ . '/../includes/database.php';

return $databaseConfig;
