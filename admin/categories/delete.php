<?php

require_once __DIR__ . "/../../includes/db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM categories WHERE id = ?"
        );

        $stmt->execute([$id]);

    } catch (PDOException $e) {

        die(
            "Không thể xóa danh mục. Có thể danh mục đang được sử dụng bởi sản phẩm."
        );

    }
}

header("Location: index.php");
exit;