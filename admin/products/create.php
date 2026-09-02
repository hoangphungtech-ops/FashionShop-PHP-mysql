<?php
require_once __DIR__ . "/../../includes/db.php";

$categories = $pdo
    ->query("SELECT * FROM categories ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name === '') {

        $error = "Vui lòng nhập tên sản phẩm.";

    } elseif ($slug === '') {

        $error = "Vui lòng nhập slug sản phẩm.";

    } elseif ($price < 0) {

        $error = "Giá sản phẩm không hợp lệ.";

    } elseif ($stock < 0) {

        $error = "Số lượng tồn kho không hợp lệ.";

    } else {

        try {

            /*
             * ==========================
             * 1. THÊM SẢN PHẨM
             * ==========================
             */

            $stmt = $pdo->prepare("
                INSERT INTO products
                (
                    name,
                    slug,
                    price,
                    category_id,
                    description,
                    stock,
                    status
                )
                VALUES
                (
                    :name,
                    :slug,
                    :price,
                    :category_id,
                    :description,
                    :stock,
                    :status
                )
            ");

            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':price' => $price,
                ':category_id' => $categoryId > 0 ? $categoryId : null,
                ':description' => $description,
                ':stock' => $stock,
                ':status' => $status
            ]);

            // Lấy ID sản phẩm vừa tạo
            $productId = $pdo->lastInsertId();


            /*
             * ==========================
             * 2. UPLOAD NHIỀU ẢNH
             * ==========================
             */

            if (
                isset($_FILES['images']) &&
                !empty($_FILES['images']['name'][0])
            ) {

                $uploadDir = __DIR__ . "/../../uploads/";

                // Nếu chưa có thư mục uploads thì tạo
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $allowedTypes = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                $firstImage = null;

                foreach ($_FILES['images']['name'] as $key => $originalName) {

                    // Bỏ qua file lỗi
                    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $_FILES['images']['tmp_name'][$key];

                    $extension = strtolower(
                        pathinfo(
                            $originalName,
                            PATHINFO_EXTENSION
                        )
                    );

                    // Chỉ cho phép ảnh
                    if (!in_array($extension, $allowedTypes)) {
                        continue;
                    }

                    // Tạo tên file mới
                    $newName =
                        uniqid('product_', true)
                        . '.'
                        . $extension;

                    $destination = $uploadDir . $newName;

                    if (move_uploaded_file($tmpName, $destination)) {

                        /*
                         * Lưu ảnh đầu tiên làm ảnh đại diện
                         */
                        if ($firstImage === null) {
                            $firstImage = $newName;
                        }

                        /*
                         * Lưu tất cả ảnh vào product_images
                         */
                        $imageStmt = $pdo->prepare("
                            INSERT INTO product_images
                            (
                                product_id,
                                image
                            )
                            VALUES
                            (
                                :product_id,
                                :image
                            )
                        ");

                        $imageStmt->execute([
                            ':product_id' => $productId,
                            ':image' => $newName
                        ]);
                    }
                }


                /*
                 * ==========================
                 * 3. LƯU ẢNH ĐẠI DIỆN
                 * ==========================
                 */

                if ($firstImage !== null) {

                    $updateImage = $pdo->prepare("
                        UPDATE products
                        SET image = :image
                        WHERE id = :id
                    ");

                    $updateImage->execute([
                        ':image' => $firstImage,
                        ':id' => $productId
                    ]);
                }
            }


            /*
             * ==========================
             * 4. QUAY VỀ DANH SÁCH
             * ==========================
             */

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

    <title>Thêm sản phẩm</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7f5;
            color: #263126;
        }

        .container {
            max-width: 900px;
            width: 92%;
            margin: auto;
        }

        header {
            background: #263126;
            color: white;
            padding: 20px 0;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        main {
            padding: 40px 0;
        }

        .box {
            background: white;
            padding: 30px;
            border: 1px solid #e0e5e0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5ddd6;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 220px;
        }

        .btn {
            padding: 13px 22px;
            background: #263126;
            color: white;
            border: 0;
            cursor: pointer;
        }

        .error {
            background: #fff0f0;
            color: #a33d3d;
            padding: 12px;
            margin-bottom: 20px;
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

        <a href="index.php">
            ← Quản lý sản phẩm
        </a>

    </div>

</header>


<main>

    <div class="container">

        <div class="box">

            <h1>
                Thêm sản phẩm
            </h1>


            <?php if ($error): ?>

                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- TÊN -->

                <div class="form-group">

                    <label>
                        Tên sản phẩm
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- SLUG -->

                <div class="form-group">

                    <label>
                        Slug
                    </label>

                    <input
                        type="text"
                        name="slug"
                        value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>"
                        placeholder="vi-du-ao-thun-nam"
                        required
                    >

                    <span class="hint">
                        Ví dụ: ao-thun-nam
                    </span>

                </div>


                <!-- GIÁ -->

                <div class="form-group">

                    <label>
                        Giá
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- DANH MỤC -->

                <div class="form-group">

                    <label>
                        Danh mục
                    </label>

                    <select name="category_id">

                        <option value="0">
                            -- Chọn danh mục --
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int)$category['id'] ?>"
                                <?= (
                                    (int)($_POST['category_id'] ?? 0)
                                    === (int)$category['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($category['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- STOCK -->

                <div class="form-group">

                    <label>
                        Số lượng trong kho
                    </label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        value="<?= htmlspecialchars($_POST['stock'] ?? 0) ?>"
                    >

                </div>


                <!-- NHIỀU ẢNH -->

                <div class="form-group">

                    <label>
                        Hình ảnh sản phẩm
                    </label>

                    <input
                        type="file"
                        name="images[]"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <span class="hint">
                        Có thể chọn nhiều ảnh cùng lúc.
                        Ảnh đầu tiên sẽ được dùng làm ảnh đại diện.
                    </span>

                </div>


                <!-- MÔ TẢ -->

                <div class="form-group">

                    <label>
                        Mô tả sản phẩm
                    </label>

                    <textarea
                        name="description"
                        id="description"
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>

                        <input
                            type="checkbox"
                            name="status"
                            value="1"
                            checked
                            style="width:auto"
                        >

                        Hiển thị sản phẩm

                    </label>

                </div>


                <button
                    class="btn"
                    type="submit"
                >
                    Lưu sản phẩm
                </button>


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

