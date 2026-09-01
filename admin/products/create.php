<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

require_admin(app_url('auth/login.php'));

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$name = '';
$priceInput = '';
$stockInput = '0';
$categoryId = null;
$description = '';
$status = 1;

if (is_post_request()) {
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
            $imagePath = null;
            $imageFile = $_FILES['image'] ?? null;

            if (is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $filename = store_uploaded_image(
                    $imageFile,
                    dirname(__DIR__, 2) . '/uploads/products'
                );
                $imagePath = 'uploads/products/' . $filename;
            }

            $slug = admin_unique_slug($pdo, 'products', $name);
            $statement = $pdo->prepare(
                'INSERT INTO products
                    (category_id, name, slug, description, price, stock, image, status)
                 VALUES
                    (:category_id, :name, :slug, :description, :price, :stock, :image, :status)'
            );
            $statement->execute([
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':price' => number_format((float) $price, 2, '.', ''),
                ':stock' => (int) $stock,
                ':image' => $imagePath,
                ':status' => $status,
            ]);
            admin_flash('success', 'Đã tạo sản phẩm.');
            safe_redirect('index.php', 'index.php', 303);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('[admin-product-create] Insert failed: ' . $exception->getMessage());
            $error = 'Không thể tạo sản phẩm. Vui lòng kiểm tra dữ liệu và thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm | Fashion Shop Admin</title>
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
        <h1>Thêm sản phẩm</h1>
        <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field"><label for="name">Tên sản phẩm</label><input id="name" name="name" value="<?= e($name) ?>" maxlength="150" required></div>
            <div class="field"><label for="price">Giá</label><input id="price" type="number" name="price" value="<?= e($priceInput) ?>" min="0" max="9999999999.99" step="0.01" required></div>
            <div class="field"><label for="stock">Tồn kho</label><input id="stock" type="number" name="stock" value="<?= e($stockInput) ?>" min="0" max="1000000" step="1" required></div>
            <div class="field"><label for="category_id">Danh mục</label><select id="category_id" name="category_id"><option value="">-- Chưa phân loại --</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="image">Ảnh sản phẩm</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG hoặc WEBP, tối đa 5 MB.</small></div>
            <div class="field"><label for="description">Mô tả</label><textarea id="description" name="description"><?= e($description) ?></textarea></div>
            <div class="field"><label class="check"><input type="checkbox" name="status" value="1" <?= $status === 1 ? 'checked' : '' ?>> Đang bán</label></div>
            <button type="submit">Lưu sản phẩm</button>
        </form>
    </div>
</main>
</body>
</html>
