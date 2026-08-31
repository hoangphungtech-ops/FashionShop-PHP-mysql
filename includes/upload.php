<?php

declare(strict_types=1);

/**
 * Validate a single uploaded raster image using its real MIME type.
 *
 * @return array{mime: string, extension: string, size: int, temporary_path: string}
 */
function validate_uploaded_image(array $file, int $maxBytes = 5_242_880): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ảnh tải lên không hợp lệ hoặc tải lên thất bại.');
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    if ($size < 1 || $size > $maxBytes) {
        throw new RuntimeException('Ảnh phải có dung lượng không quá 5 MB.');
    }

    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Nguồn file tải lên không hợp lệ.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($temporaryPath);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_string($mime) || !isset($allowedTypes[$mime])) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, JPEG, PNG hoặc WEBP.');
    }

    return [
        'mime' => $mime,
        'extension' => $allowedTypes[$mime],
        'size' => $size,
        'temporary_path' => $temporaryPath,
    ];
}

/**
 * Store a validated image with a cryptographically random filename.
 */
function store_uploaded_image(
    array $file,
    string $destinationDirectory,
    int $maxBytes = 5_242_880
): string {
    $image = validate_uploaded_image($file, $maxBytes);

    if (!is_dir($destinationDirectory)
        && !mkdir($destinationDirectory, 0755, true)
        && !is_dir($destinationDirectory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }

    if (!is_writable($destinationDirectory)) {
        throw new RuntimeException('Thư mục lưu ảnh không có quyền ghi.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $image['extension'];
    $targetPath = rtrim($destinationDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($image['temporary_path'], $targetPath)) {
        throw new RuntimeException('Không thể lưu ảnh tải lên.');
    }

    return $filename;
}
