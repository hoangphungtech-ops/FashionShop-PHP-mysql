<?php

declare(strict_types=1);

// Legacy PDO adapter. Credentials live only in config/database.php.
$configPath = dirname(__DIR__) . '/config/database.php';

if (!is_file($configPath)) {
    require_once __DIR__ . '/bootstrap.php';

    throw new RuntimeException(
        'Thiếu config/database.php. Hãy sao chép config/database.example.php và cấu hình database.'
    );
}

$databaseConnectionMode = 'pdo';
require $configPath;
unset($databaseConnectionMode);

if (!isset($pdo) || !$pdo instanceof PDO) {
    throw new RuntimeException('Không thể khởi tạo kết nối PDO.');
}
