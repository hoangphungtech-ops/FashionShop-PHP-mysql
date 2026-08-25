<?php

require_once __DIR__ . "/../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   KIỂM TRA ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../products/index.php");
    exit;
}

$id = (int) $_GET['id'];


/* =========================
   LẤY SẢN PHẨM
========================= */

try {

    $sql = "SELECT *
            FROM products
            WHERE id = :id
            AND status = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Lỗi database: " . $e->getMessage());

}


/* =========================
   KIỂM TRA SẢN PHẨM
========================= */

if (!$product) {
    die("Không tìm thấy sản phẩm.");
}


/* =========================
   KIỂM TRA TỒN KHO
========================= */

if ((int)$product['stock'] <= 0) {
    die("Sản phẩm đã hết hàng.");
}


/* =========================
   TẠO GIỎ HÀNG
========================= */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


/* =========================
   THÊM SẢN PHẨM
========================= */

if (isset($_SESSION['cart'][$id])) {

    $quantity = (int) $_SESSION['cart'][$id];

    if ($quantity < (int)$product['stock']) {

        $_SESSION['cart'][$id] = $quantity + 1;

    }

} else {

    $_SESSION['cart'][$id] = 1;

}


/* =========================
   ĐI ĐẾN GIỎ HÀNG
========================= */

header("Location: index.php");
exit;

?>