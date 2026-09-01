<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

require_admin(app_url('auth/login.php'));

$id = input_int($_GET, 'id');

if ($id === null) {
    safe_redirect('index.php', 'index.php');
}

$productStatement = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$productStatement->execute([':id' => $id]);
$product = $productStatement->fetch(PDO::FETCH_ASSOC) ?: null;

if ($product === null) {
    http_response_code(404);
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$name = (string) ($product['name'] ?? '');
$priceInput = (string) ($product['price'] ?? '');
$stockInput = (string) ($product['stock'] ?? '0');
$categoryId = isset($product['category_id']) ? (int) $product['category_id'] : null;
$description = (string) ($product['description'] ?? '');
$status = (int) ($product['status'] ?? 0);
$imagePath = (string) ($product['image'] ?? '');

if ($product !== null && is_post_request()) {
    $name = sanitize_text($_POST['name'] ?? '', 150);
    $priceInput = trim((string) ($_POST['price'] ?? ''));
    $stockInput = trim((string) ($_POST['stock'] ?? ''));
    $price = filter_var($priceInput, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($stockInput, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 1000000],
    ]);
    $categoryValue = $_POST['category_id'] ?? '';
    $categoryId = ($categoryValue === '' || $categoryValue === '0')
        ? null
        : filter_var($categoryValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $description = sanitize_text($_POST['description'] ?? '', 20000);
    $status = isset($_POST['status']) ? 1 : 0;
    $csrfToken = $_POST['_csrf_token'] ?? null;

    if (!is_string($csrfToken) || !csrf_validate($csrfToken)) {
        $error = 'Phiên thao tác đã hết hạn. Vui lòng thử lại.';
    } elseif ($name === '' || mb_strlen($name, 'UTF-8') < 2) {
        $error = 'Tên sản phẩm phải có ít nhất 2 ký tự.';
    } elseif ($price === false || $price < 0 || $price > 9999999999.99) {
        $error = 'Giá sản phẩm không hợp lệ.';
    } elseif ($stock === false) {
        $error = 'Tồn kho phải là số nguyên không âm.';
    } elseif ($categoryId === false || ($categoryId !== null && !admin_category_exists($pdo, (int) $categoryId))) {
        $error = 'Danh mục không hợp lệ.';
    } else {
        try {
            $imageFile = $_FILES['image'] ?? null;

            if (is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $filename = store_uploaded_image(
                    $imageFile,
                    dirname(__DIR__, 2) . '/uploads/products'
                );
                $imagePath = 'uploads/products/' . $filename;
            }

            $slug = admin_unique_slug($pdo, 'products', $name, $id);
            $statement = $pdo->prepare(
                'UPDATE products SET
                    category_id = :category_id,
                    name = :name,
                    slug = :slug,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    image = :image,
                    status = :status
                 WHERE id = :id'
            );
            $statement->execute([
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':price' => number_format((float) $price, 2, '.', ''),
                ':stock' => (int) $stock,
                ':image' => $imagePath !== '' ? $imagePath : null,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash('success', 'Đã cập nhật sản phẩm.');
            safe_redirect('index.php', 'index.php', 303);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('[admin-product-edit] Update failed: ' . $exception->getMessage());
            $error = 'Không thể cập nhật sản phẩm. Vui lòng kiểm tra dữ liệu và thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm | Fashion Shop Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f5; color: #263126; }
        header { padding: 20px 0; background: #263126; }
        header a { color: #fff; }
        .container { width: 92%; max-width: 850px; margin: auto; }
        main { padding: 38px 0; }
        .box { padding: 28px; background: #fff; border: 1px solid #e0e5e0; }
        .field { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; font-weight: 700; }
        input, select, textarea { width: 100%; padding: 11px; border: 1px solid #ccd5cd; font: inherit; }
        textarea { min-height: 180px; resize: vertical; }
        .check { display: flex; align-items: center; gap: 8px; }
        .check input { width: auto; }
        .current-image { width: 100px; height: 125px; margin-bottom: 10px; object-fit: cover; background: #eef1ee; }
        .error { padding: 13px; margin-bottom: 18px; background: #fff1ef; color: #7e3933; }
        button { padding: 12px 20px; border: 0; background: #263126; color: #fff; cursor: pointer; }
        small { display: block; margin-top: 6px; color: #667066; }
        @media (max-width: 600px) { .box { padding: 18px; } }
    </style>
</head>
<body>
<header><div class="container"><a href="index.php">← Quản lý sản phẩm</a></div></header>
<main class="container">
    <div class="box">
        <?php if ($product === null): ?>
            <h1>Không tìm thấy sản phẩm</h1>
        <?php else: ?>
            <h1>Sửa sản phẩm #<?= $id ?></h1>
            <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="edit.php?id=<?= $id ?>">
                <?= csrf_field() ?>
                <div class="field"><label for="name">Tên sản phẩm</label><input id="name" name="name" value="<?= e($name) ?>" maxlength="150" required></div>
                <div class="field"><label for="price">Giá</label><input id="price" type="number" name="price" value="<?= e($priceInput) ?>" min="0" max="9999999999.99" step="0.01" required></div>
                <div class="field"><label for="stock">Tồn kho</label><input id="stock" type="number" name="stock" value="<?= e($stockInput) ?>" min="0" max="1000000" step="1" required></div>
                <div class="field"><label for="category_id">Danh mục</label><select id="category_id" name="category_id"><option value="">-- Chưa phân loại --</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="image">Thay ảnh sản phẩm</label><?php if ($imagePath !== ''): ?><img class="current-image" src="<?= e(admin_product_image_url($imagePath)) ?>" alt="Ảnh hiện tại"><?php endif; ?><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Để trống để giữ ảnh hiện tại. JPG, PNG hoặc WEBP, tối đa 5 MB.</small></div>
                <div class="field"><label for="description">Mô tả</label><textarea id="description" name="description"><?= e($description) ?></textarea></div>
                <div class="field"><label class="check"><input type="checkbox" name="status" value="1" <?= $status === 1 ? 'checked' : '' ?>> Đang bán</label></div>
                <button type="submit">Cập nhật sản phẩm</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
