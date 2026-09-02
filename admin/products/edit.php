<?php
require_once __DIR__ . "/../../includes/db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Không tìm thấy sản phẩm.");
}

$categories = $pdo
    ->query("SELECT * FROM categories ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $stock = (int)($_POST['stock'] ?? $product['stock']);

    // Giữ ảnh cũ nếu không upload ảnh mới
    $image = $product['image'] ?? '';

    if ($name === '') {
        $error = "Vui lòng nhập tên sản phẩm.";
    } else {

        try {
            // ===== XỬ LÝ UPLOAD ẢNH MỚI =====
            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] === UPLOAD_ERR_OK
            ) {
                $uploadDir = __DIR__ . "/../../uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $originalName = $_FILES['image']['name'];
                $tmpName = $_FILES['image']['tmp_name'];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($extension, $allowed)) {
                    $newName = uniqid('product_', true) . '.' . $extension;
                    $destination = $uploadDir . $newName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        // Xóa ảnh cũ nếu có
                        if (!empty($product['image']) && file_exists($uploadDir . $product['image'])) {
                            unlink($uploadDir . $product['image']);
                        }
                        $image = $newName;
                    }
                }
            }

            // ===== CẬP NHẬT SẢN PHẨM =====
            $stmt = $pdo->prepare("
                UPDATE products
                SET
                    name = :name,
                    price = :price,
                    category_id = :category_id,
                    description = :description,
                    image = :image,
                    status = :status,
                    stock = :stock
                WHERE id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':category_id' => $categoryId > 0 ? $categoryId : null,
                ':description' => $description,
                ':image' => $image,
                ':status' => $status,
                ':stock' => $stock,
                ':id' => $id
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7f5;
            color: #263126;
        }
        .container {
            width: 92%;
            max-width: 900px;
            margin: auto;
        }
        header {
            background: #263126;
            padding: 20px 0;
        }
        header a {
            color: white;
            text-decoration: none;
        }
        main { padding: 40px 0; }
        .box {
            background: white;
            padding: 30px;
            border: 1px solid #e0e5e0;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5ddd6;
            font-family: Arial, sans-serif;
        }
        textarea { min-height: 220px; }
        .btn {
            background: #263126;
            color: white;
            border: 0;
            padding: 13px 22px;
            cursor: pointer;
        }
        .btn:hover { background: #78917d; }
        .error {
            background: #fff0f0;
            color: #a33d3d;
            padding: 12px;
            margin-bottom: 20px;
        }
        .current-image {
            margin-top: 10px;
        }
        .current-image img {
            max-width: 150px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .hint {
            display: block;
            margin-top: 6px;
            color: #777;
            font-size: 13px;
        }
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
                    <input type="text" name="name"
                           value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Giá</label>
                    <input type="number" name="price"
                           value="<?= htmlspecialchars($product['price']) ?>"
                           step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Danh mục</label>
                    <select name="category_id">
                        <option value="0">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['id'] ?>"
                                <?= ((int)$product['category_id'] === (int)$category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Số lượng trong kho</label>
                    <input type="number" name="stock" min="0"
                           value="<?= htmlspecialchars($product['stock'] ?? 0) ?>">
                </div>

                <div class="form-group">
                    <label>Hình ảnh</label>

                    <?php if (!empty($product['image'])): ?>
                        <div class="current-image">
                            <img src="../../uploads/<?= htmlspecialchars($product['image']) ?>"
                                 alt="Ảnh hiện tại">
                            <div class="hint">Ảnh hiện tại</div>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="image"
                           accept="image/jpeg,image/png,image/webp"
                           style="margin-top: 10px;">
                    <span class="hint">
                        Chọn ảnh mới nếu muốn thay thế. Để trống nếu giữ nguyên ảnh cũ.
                    </span>
                </div>

                <div class="form-group">
                    <label>Mô tả sản phẩm</label>
                    <textarea name="description" id="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" value="1"
                               style="width:auto"
                               <?= ((int)$product['status'] === 1) ? 'checked' : '' ?>>
                        Hiển thị sản phẩm
                    </label>
                </div>

                <button type="submit" class="btn">Cập nhật sản phẩm</button>
            </form>
        </div>
    </div>
</main>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('description');
</script>

</body>
</html>
<?php
require_once __DIR__ . "/../../includes/db.php";

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

$error = '';

// Xóa ảnh phụ
if (isset($_GET['delete_img_id'])) {
    $imgId = (int)$_GET['delete_img_id'];
    $stmtFind = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
    $stmtFind->execute([$imgId, $id]);
    $imgData = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if ($imgData) {
        $filePath = __DIR__ . "/../../uploads/" . $imgData['image_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
    }
    header("Location: edit.php?id=" . $id);
    exit;
}

// Cập nhật sản phẩm & thêm ảnh mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');

    try {
        $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, slug = ?, price = ?, stock = ?, category_id = ?, description = ? WHERE id = ?");
        $stmtUpdate->execute([$name, $slug, $price, $stock, $category_id, $description, $id]);

        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $targetDir = __DIR__ . "/../../uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            foreach ($_FILES['new_images']['name'] as $i => $nameFile) {
                if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . $i . '_' . basename($nameFile);
                    if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $targetDir . $fileName)) {
                        $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 0)")->execute([$id, $fileName]);
                    }
                }
            }
        }

        header("Location: edit.php?id=" . $id);
        exit;
    } catch (PDOException $e) {
        $error = "Lỗi Database: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách ảnh của sản phẩm
$stmtImgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmtImgs->execute([$id]);
$productImages = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);
?>

