<?php
require_once __DIR__ . "/../../includes/db.php";

// Lấy sản phẩm, ưu tiên cột image trong products, nếu không có thì lấy từ bảng product_images
$stmt = $pdo->query("
    SELECT p.*, 
           c.name AS category_name,
           COALESCE(NULLIF(p.image, ''), pi.image_path) AS final_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND (pi.is_primary = 1 OR pi.is_primary IS NULL)
    GROUP BY p.id
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        .container { width: 94%; max-width: 1250px; margin: auto; }
        .header { background: #263126; color: white; padding: 20px 0; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; }
        .content { padding: 40px 0; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn { display: inline-block; padding: 11px 18px; background: #263126; color: white; text-decoration: none; border: none; cursor: pointer; border-radius: 4px; }
        .btn:hover { background: #78917d; }
        .table-box { background: white; overflow-x: auto; border: 1px solid #e0e5e0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; border-bottom: 1px solid #e5e9e5; text-align: left; vertical-align: middle; }
        th { background: #eef2ee; }
        .product-img { width: 70px; height: 70px; object-fit: cover; background: #eee; border-radius: 4px; border: 1px solid #ddd; display: block; }
        .edit { color: #526b58; text-decoration: none; margin-right: 10px; }
        .delete { color: #a34d4d; text-decoration: none; }
        .status { padding: 5px 9px; background: #e8f1e9; color: #416047; border-radius: 3px; font-size: 13px; }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-inner">
        <strong>FashionShop Admin</strong>
        <a href="../index.php">Dashboard</a>
    </div>
</header>

<div class="container content">
    <div class="top">
        <h1>Quản lý sản phẩm</h1>
        <a href="create.php" class="btn">+ Thêm sản phẩm</a>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= (int)$product['id'] ?></td>
                    <td>
                        <?php
                        $imgName = trim($product['final_image'] ?? '');
                        
                        if (!empty($imgName)) {
                            // Xử lý các dạng đường dẫn khác nhau
                            if (strpos($imgName, 'http') === 0) {
                                $imgSrc = $imgName;
                            } elseif (strpos($imgName, 'uploads/') === 0) {
                                $imgSrc = '../../' . $imgName;
                            } else {
                                $imgSrc = '../../uploads/' . $imgName;
                            }
                        } else {
                            // Ảnh hiển thị thay thế khi chưa có ảnh trong CSDL
                            $imgSrc = 'https://via.placeholder.com/70x70/e0e5e0/555555?text=No+Image';
                        }
                        ?>
                        <img 
                            src="<?= htmlspecialchars($imgSrc) ?>" 
                            class="product-img" 
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            onerror="this.onerror=null; this.src='https://via.placeholder.com/70x70/f8d7da/721c24?text=Loi+Anh';"
                        >
                    </td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name'] ?? 'Chưa có') ?></td>
                    <td><?= number_format((float)$product['price'] * 1000, 0, ',', '.') ?> VNĐ</td>
                    <td>
                       <?php 
$stock = (int)($product['stock'] ?? 0);
$status = (int)($product['status'] ?? 0);

if ($status === 1 && $stock > 0): ?>
    <span class="status">Đang bán</span>
<?php elseif ($status === 1 && $stock <= 0): ?>
    <span class="status" style="background: #fff3cd; color: #856404;">Hết hàng</span>
<?php else: ?>
    <span class="status" style="background: #f8d7da; color: #721c24;">Ngừng bán</span>
<?php endif; ?>
                    </td>
                    <td>
                        <a href="edit.php?id=<?= (int)$product['id'] ?>" class="edit">Sửa</a>
                        <a href="delete.php?id=<?= (int)$product['id'] ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Chưa có sản phẩm.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>