<?php

require_once __DIR__ . "/../../includes/db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM products WHERE id = ?"
        );

        $stmt->execute([$id]);

    } catch (PDOException $e) {

        die("Không thể xóa sản phẩm: " . $e->getMessage());

    }
}

header("Location: index.php");
exit;