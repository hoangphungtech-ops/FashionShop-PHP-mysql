<?php

require_once __DIR__ . "/../../includes/db.php";

$stmt = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY id DESC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý danh mục</title>

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
            max-width: 1000px;
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

        .top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .btn {
            padding: 12px 18px;
            background: #263126;
            color: white;
            text-decoration: none;
        }

        .box {
            background: white;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid #e5e9e5;
            text-align: left;
        }

        th {
            background: #eef2ee;
        }

        .edit {
            color: #526b58;
            text-decoration: none;
            margin-right: 10px;
        }

        .delete {
            color: #a33d3d;
            text-decoration: none;
        }

    </style>

</head>

<body>

<header>

    <div class="container">

        <a href="../index.php">
            ← Dashboard
        </a>

    </div>

</header>

<main>

    <div class="container">

        <div class="top">

            <h1>
                Quản lý danh mục
            </h1>

            <a
                href="create.php"
                class="btn"
            >
                + Thêm danh mục
            </a>

        </div>

        <div class="box">

            <table>

                <thead>

                <tr>

                    <th>ID</th>

                    <th>Tên danh mục</th>

                    <th>Thao tác</th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($categories as $category): ?>

                    <tr>

                        <td>
                            <?= (int)$category['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($category['name']) ?>
                        </td>

                        <td>

                            <a
                            href="edit.php?id=<?= (int)$category['id'] ?>"
                                class="edit"
                            >
                                Sửa
                            </a>

                            <a
                                href="delete.php?id=<?= (int)$category['id'] ?>"
                                class="delete"
                                onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')"
                            >
                                Xóa
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</body>

</html>
