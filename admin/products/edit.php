<?php
require_once __DIR__ . "/../../includes/db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// 1. Lấy thông tin sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

$error = '';

// 2. Xử lý ĐẶT LÀM ẢNH CHÍNH (Set Primary)
if (isset($_GET['set_primary_id'])) {
    $imgId = (int)$_GET['set_primary_id'];
    
    // Bỏ tất cả ảnh chính cũ của sản phẩm này về 0
    $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$id]);
    
    // Gán is_primary = 1 cho ảnh được chọn
    $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?")->execute([$imgId, $id]);
    
    header("Location: edit.php?id=" . $id);
    exit;
}

// 3. Xử lý XÓA ÁNH
if (isset($_GET['delete_img_id'])) {
    $imgId = (int)$_GET['delete_img_id'];
    $stmtFind = $pdo->prepare("SELECT image_path, is_primary FROM product_images WHERE id = ? AND product_id = ?");
    $stmtFind->execute([$imgId, $id]);
    $imgData = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if ($imgData) {
        $filePath = __DIR__ . "/../../uploads/" . $imgData['image_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);

        // Nếu xóa đúng ảnh chính, tự động gán 1 ảnh còn lại làm ảnh chính
        if ($imgData['is_primary'] == 1) {
            $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? LIMIT 1")->execute([$id]);
        }
    }
    header("Location: edit.php?id=" . $id);
    exit;
}

// 4. Xử lý CẬP NHẬT SẢN PHẨM & TẢI ẢNH MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $slug          = trim($_POST['slug'] ?? '');
    $price         = (float)($_POST['price'] ?? 0);
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $categoryId    = (int)($_POST['category_id'] ?? 0);
    $description   = $_POST['description'] ?? ''; // Giữ nguyên định dạng HTML từ CKEditor
    $stock         = (int)($_POST['stock'] ?? 0);
    $status        = isset($_POST['status']) ? 1 : 0;

    $sizes         = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
    $color         = trim($_POST['color'] ?? '');
    $material      = trim($_POST['material'] ?? '');

    if ($name === '') {
        $error = "Vui lòng nhập tên sản phẩm.";
    } elseif ($price < 0) {
        $error = "Giá sản phẩm không hợp lệ.";
    } else {
        try {
            // Cập nhật thông tin vào bảng products
            $stmtUpdate = $pdo->prepare("
                UPDATE products 
                SET name = :name, 
                    slug = :slug, 
                    price = :price, 
                    original_price = :original_price,
                    category_id = :category_id, 
                    description = :description, 
                    stock = :stock,
                    size = :size,
                    color = :color,
                    material = :material,
                    status = :status 
                WHERE id = :id
            ");

            $stmtUpdate->execute([
                ':name'           => $name,
                ':slug'           => $slug,
                ':price'          => $price,
                ':original_price' => $originalPrice,
                ':category_id'    => $categoryId > 0 ? $categoryId : null,
                ':description'    => $description,
                ':stock'          => $stock,
                ':size'           => $sizes,
                ':color'          => $color,
                ':material'       => $material,
                ':status'         => $status,
                ':id'             => $id
            ]);

            // Tải thêm ảnh mới
            if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
                $targetDir = __DIR__ . "/../../uploads/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                // Kiểm tra xem sản phẩm đã có ảnh chính chưa
                $checkPrimary = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
                $checkPrimary->execute([$id]);
                $hasPrimary = (int)$checkPrimary->fetchColumn() > 0;

                foreach ($_FILES['new_images']['name'] as $i => $nameFile) {
                    if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $extension = strtolower(pathinfo($nameFile, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                        if (in_array($extension, $allowed)) {
                            $fileName = uniqid('product_', true) . '.' . $extension;
                            
                            if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $targetDir . $fileName)) {
                                $isPrimary = !$hasPrimary ? 1 : 0;
                                
                                $pdo->prepare("
                                    INSERT INTO product_images (product_id, image_path, is_primary) 
                                    VALUES (?, ?, ?)
                                ")->execute([$id, $fileName, $isPrimary]);

                                $hasPrimary = true;
                            }
                        }
                    }
                }
            }

           header("Location: index.php");
exit;

        } catch (PDOException $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}

// 5. Lấy dữ liệu danh mục & ảnh sản phẩm
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmtImgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmtImgs->execute([$id]);
$productImages = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

$currentSizes = !empty($product['size']) ? explode(',', $product['size']) : [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm</title>
    <!-- Tích hợp thư viện CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        .container { max-width: 900px; width: 92%; margin: auto; }
        header { background: #263126; color: white; padding: 20px 0; }
        header a { color: white; text-decoration: none; }
        main { padding: 40px 0; }
        .box { background: white; padding: 30px; border: 1px solid #e0e5e0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #d5ddd6; font-family: Arial, sans-serif; }
        .ck-editor__editable { min-height: 250px; }
        .btn { background: #263126; color: white; border: 0; padding: 13px 22px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #78917d; }
        .error { background: #fff0f0; color: #a33d3d; padding: 12px; margin-bottom: 20px; }
        .hint { display: block; margin-top: 6px; color: #777; font-size: 13px; }
        .checkbox-group label { display: inline-block; margin-right: 15px; font-weight: normal; }
        
        .gallery { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px; }
        .img-card { position: relative; border: 1px solid #ddd; padding: 8px; background: #fff; text-align: center; border-radius: 4px; }
        .img-card img { width: 110px; height: 110px; object-fit: cover; display: block; border-radius: 4px; }
        .btn-delete-img { color: #d9534f; text-decoration: none; font-size: 12px; font-weight: bold; display: block; margin-top: 5px; }
        .btn-set-primary { color: #0275d8; text-decoration: none; font-size: 12px; display: block; margin-top: 5px; }
        .badge-primary { background: #5cb85c; color: white; padding: 2px 6px; font-weight: bold; font-size: 11px; border-radius: 3px; display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <a href="index.php">← Quản lý sản phẩm</a>
    </div>
</header>

<main>
    <div class="container">
        <div class="box">
            <h1>Sửa sản phẩm</h1>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Tên sản phẩm</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? $product['slug'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Giá bán (VNĐ)</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Giá gốc / Giá cũ (VNĐ)</label>
                    <input type="number" name="original_price" value="<?= htmlspecialchars($_POST['original_price'] ?? $product['original_price'] ?? '') ?>" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label>Danh mục</label>
                    <select name="category_id">
                        <option value="0">-- Chọn danh mục --</option>
                        <?php 
                        $selectedCat = $_POST['category_id'] ?? $product['category_id'];
                        foreach ($categories as $category): 
                        ?>
                            <option value="<?= (int)$category['id'] ?>" <?= ((int)$selectedCat === (int)$category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <label>Kích thước (Size)</label>
                    <?php 
                    $availableSizes = ['S', 'M', 'L', 'XL', 'XXL'];
                    $postSizes = $_POST['sizes'] ?? $currentSizes;
                    foreach ($availableSizes as $sz):
                    ?>
                        <label>
                            <input type="checkbox" name="sizes[]" value="<?= $sz ?>" style="width:auto" <?= in_array($sz, $postSizes) ? 'checked' : '' ?>> <?= $sz ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <label>Màu sắc</label>
                    <input type="text" name="color" value="<?= htmlspecialchars($_POST['color'] ?? $product['color'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Chất liệu</label>
                    <input type="text" name="material" value="<?= htmlspecialchars($_POST['material'] ?? $product['material'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Số lượng trong kho</label>
                    <input type="number" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock'] ?? 0) ?>">
                </div>

                <!-- QUẢN LÝ ALBUM ẢNH -->
                <div class="form-group">
                    <label>Hình ảnh sản phẩm hiện tại</label>
                    <?php if (!empty($productImages)): ?>
                        <div class="gallery">
                            <?php foreach ($productImages as $img): ?>
                                <div class="img-card">
                                    <img src="../../uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="Ảnh">
                                    
                                    <?php if ((int)$img['is_primary'] === 1): ?>
                                        <span class="badge-primary">Ảnh chính</span>
                                    <?php else: ?>
                                        <a href="edit.php?id=<?= $id ?>&set_primary_id=<?= $img['id'] ?>" class="btn-set-primary">Đặt làm ảnh chính</a>
                                    <?php endif; ?>

                                    <a href="edit.php?id=<?= $id ?>&delete_img_id=<?= $img['id'] ?>" 
                                       class="btn-delete-img" 
                                       onclick="return confirm('Bạn có chắc muốn xóa ảnh này?')">Xóa</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="hint">Chưa có hình ảnh nào.</p>
                    <?php endif; ?>

                    <br>
                    <label>Tải lên ảnh mới</label>
                    <input type="file" name="new_images[]" multiple accept="image/jpeg,image/png,image/webp">
                    <span class="hint">Chọn một hoặc nhiều ảnh để thêm vào danh sách.</span>
                </div>

                <div class="form-group">
                    <label>Mô tả sản phẩm</label>
                    <textarea name="description" id="editor"><?= html_entity_decode($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" value="1" style="width:auto" <?= (($_POST['status'] ?? $product['status']) == 1) ? 'checked' : '' ?>>
                        Hiển thị sản phẩm
                    </label>
                </div>

                <button type="submit" class="btn">Cập nhật sản phẩm</button>
            </form>
        </div>
    </div>
</main>

<script>
    // Khởi tạo bộ soạn thảo CKEditor 5
    ClassicEditor
        .create(document.querySelector('#editor'))
        .catch(error => {
            console.error(error);
        });
</script>

</body>
</html>