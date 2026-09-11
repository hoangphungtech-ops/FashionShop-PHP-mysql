<?php

require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/admin_helpers.php";

require_admin(app_url('auth/login.php'));

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT * FROM categories WHERE id = ?"
);

$stmt->execute([$id]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Không tìm thấy danh mục.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitize_text($_POST['name'] ?? '', 100);

    if (!csrf_validate(is_string($_POST['_csrf_token'] ?? null) ? $_POST['_csrf_token'] : null)) {
        $error = "Phiên thao tác đã hết hạn. Vui lòng thử lại.";
    } elseif ($name === '' || mb_strlen($name, 'UTF-8') < 2) {

        $error = "Vui lòng nhập tên danh mục.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE categories
                SET name = ?, slug = ?
                WHERE id = ?
            ");

            $slug = admin_unique_slug($pdo, 'categories', $name, $id);
            $stmt->execute([
                $name,
                $slug,
                $id
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            error_log('[admin-category-edit] Update failed: ' . $e->getMessage());
            $error = 'Không thể cập nhật danh mục. Vui lòng thử lại.';

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa danh mục</title>

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
            width: 92%;
            max-width: 700px;
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

        main {
            padding: 40px 0;
        }

        .box {
            background: white;
            padding: 30px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d5ddd6;
            margin-bottom: 20px;
        }

        button {
            background: #263126;
            color: white;
            border: 0;
            padding: 13px 22px;
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
            ← Danh mục
        </a>

    </div>

</header>

<main>

    <div class="container">

        <div class="box">

            <h1>
                Sửa danh mục
            </h1>

            <?php if ($error): ?>

                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <?= csrf_field() ?>

                <label>
                    Tên danh mục
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars($category['name']) ?>"
                    maxlength="100"
                    required
                >

                <button type="submit">
                    Cập nhật
                </button>

            </form>

        </div>

    </div>

</main>

</body>

</html>
