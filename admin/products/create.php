<?php
require_once __DIR__ . "/../../includes/db.php";

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
    $image = trim($_POST['image'] ?? '');

    if ($name === '') {
        $error = "Vui lòng nhập tên sản phẩm.";
    } elseif ($price < 0) {
        $error = "Giá sản phẩm không hợp lệ.";
    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO products
                (
                    name,
                    price,
                    category_id,
                    description,
                    image,
                    status
                )
                VALUES
                (
                    :name,
                    :price,
                    :category_id,
                    :description,
                    :image,
                    :status
                )
            ");

            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':category_id' => $categoryId,
                ':description' => $description,
                ':image' => $image,
                ':status' => $status
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

            <form method="POST">

                <div class="form-group">

                    <label>
                        Tên sản phẩm
                    </label>

                    <input
                        type="text"
                        name="name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Giá
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Danh mục
                    </label>

                    <select name="category_id">

                        <option value="0">
                            -- Chọn danh mục --
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option value="<?= (int)$category['id'] ?>">

                                <?= htmlspecialchars($category['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Hình ảnh
                    </label>

                    <input
                        type="text"
                        name="image"
                        placeholder="Ví dụ: assets/images/ao-thun.jpg"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Mô tả sản phẩm
                    </label>

                    <textarea
                        name="description"
                        id="description"
                    ></textarea>

                </div>

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

                <button class="btn" type="submit">
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