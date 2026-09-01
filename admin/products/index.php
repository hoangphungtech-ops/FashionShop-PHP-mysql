<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/admin_helpers.php";

require_admin(app_url('auth/login.php'));

$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$flash = pull_admin_flash();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý sản phẩm</title>

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
            width: 94%;
            max-width: 1250px;
            margin: auto;
        }

        .header {
            background: #263126;
            color: white;
            padding: 20px 0;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header a {
            color: white;
            text-decoration: none;
        }

        .content {
            padding: 40px 0;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            background: #263126;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #78917d;
        }

        .table-box {
            background: white;
            overflow-x: auto;
            border: 1px solid #e0e5e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #e5e9e5;
            text-align: left;
        }

        th {
            background: #eef2ee;
        }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            background: #eee;
        }

        .edit {
            color: #526b58;
            text-decoration: none;
            margin-right: 10px;
        }

        .delete {
            color: #a34d4d;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-decoration: underline;
        }

        .delete-form { display: inline; }
        .notice { padding: 12px 15px; margin-bottom: 18px; background: #edf6f0; color: #28553f; }

        .status {
            padding: 5px 9px;
            background: #e8f1e9;
            color: #416047;
        }

        @media(max-width: 600px) {
            .top {
                display: block;
            }

            .top .btn {
                margin-top: 15px;
            }
        }
    </style>
</head>

<body>

<header class="header">

    <div class="container header-inner">

        <strong>
            FashionShop Admin
        </strong>

        <a href="../index.php">
            Dashboard
            </a>

    </div>

</header>

<div class="container content">

    <?php if ($flash !== null): ?>
        <div class="notice" role="status"><?= e($flash['message'] ?? '') ?></div>
    <?php endif; ?>

    <div class="top">

        <h1>
            Quản lý sản phẩm
        </h1>

        <a href="create.php" class="btn">
            + Thêm sản phẩm
        </a>

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

                    <td>
                        <?= (int)$product['id'] ?>
                    </td>

                    <td>

                        <img
                            src="<?= e(admin_product_image_url($product['image'] ?? '')) ?>"
                            class="product-img"
                            alt="<?= e($product['name']) ?>"
                        >

                    </td>

                    <td>
                        <?= htmlspecialchars($product['name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $product['category_name'] ?? 'Chưa có'
                        ) ?>
                    </td>

                    <td>
                        <?= number_format(
                            (float)$product['price'],
                            0,
                            ',',
                            '.'
                        ) ?>đ
                    </td>

                    <td>

                        <?php if ((int)$product['status'] === 1): ?>

                            <span class="status">
                                Đang bán
                            </span>

                        <?php else: ?>

                            <span>
                                Ngừng bán
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <a
                            href="edit.php?id=<?= (int)$product['id'] ?>"
                            class="edit"
                        >
                            Sửa
                        </a>

                        <form class="delete-form" method="post" action="delete.php" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <button type="submit" class="delete">Xóa</button>
                        </form>
                        </td>

                </tr>

            <?php endforeach; ?>

            <?php if (empty($products)): ?>

                <tr>
                    <td colspan="7">
                        Chưa có sản phẩm.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
