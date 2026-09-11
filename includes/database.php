<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($databaseConfig) || !is_array($databaseConfig)) {
    throw new LogicException('Cấu hình database chưa được nạp.');
}

$databaseConnectionMode = $databaseConnectionMode ?? 'both';

if (!in_array($databaseConnectionMode, ['mysqli', 'pdo', 'both'], true)) {
    throw new LogicException('Chế độ kết nối database không hợp lệ.');
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $databaseConfig['host'],
    $databaseConfig['port'],
    $databaseConfig['name']
);

try {
    if (in_array($databaseConnectionMode, ['mysqli', 'both'], true)) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $conn = new mysqli(
            $databaseConfig['host'],
            $databaseConfig['username'],
            $databaseConfig['password'],
            $databaseConfig['name'],
            $databaseConfig['port']
        );
        $conn->set_charset('utf8mb4');
    }

    if (in_array($databaseConnectionMode, ['pdo', 'both'], true)) {
        $pdo = new PDO($dsn, $databaseConfig['username'], $databaseConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
} catch (Throwable $exception) {
    error_log('[database] Connection failed: ' . $exception->getMessage());

    throw new RuntimeException(
        'Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra cấu hình hoặc thử lại sau.',
        0,
        $exception
    );
}
