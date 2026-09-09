<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login-register.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

/* =========================================================
   LẤY THÔNG TIN USER
   ========================================================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   XỬ LÝ CẬP NHẬT THÔNG TIN TÀI KHOẢN
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $update_query = "UPDATE users SET phone = ?, address = ?";
    $params = [$phone, $address];
    $has_error = false;

    /*
     * Nếu người dùng nhập bất kỳ ô mật khẩu nào
     * thì bắt buộc phải nhập đủ 3 ô.
     */
    if (
        !empty($old_password) ||
        !empty($new_password) ||
        !empty($confirm_password)
    ) {

        if (
            empty($old_password) ||
            empty($new_password) ||
            empty($confirm_password)
        ) {

            $message = "
                <div class='alert alert-danger'>
                    Vui lòng nhập đầy đủ thông tin để đổi mật khẩu!
                </div>
            ";

            $has_error = true;

        } else {

            /* Kiểm tra mật khẩu hiện tại */
            if (password_verify($old_password, $user['password'])) {

                /* Kiểm tra mật khẩu mới */
                if ($new_password === $confirm_password) {

                    $hashed_password = password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );

                    $update_query .= ", password = ?";
                    $params[] = $hashed_password;

                } else {

                    $message = "
                        <div class='alert alert-danger'>
                            Mật khẩu mới không khớp!
                        </div>
                    ";

                    $has_error = true;
                }

            } else {

                $message = "
                    <div class='alert alert-danger'>
                        Mật khẩu hiện tại không chính xác!
                    </div>
                ";

                $has_error = true;
            }
        }
    }


    /* =====================================================
       CẬP NHẬT DATABASE
       ===================================================== */
    if (!$has_error) {

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($update_query);

        if ($stmt->execute($params)) {

            $message = "
                <div class='alert alert-success'>
                    Cập nhật thông tin thành công!
                </div>
            ";

            /* Lấy lại thông tin user sau khi cập nhật */
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE id = ?"
            );

            $stmt->execute([$user_id]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } else {

            $message = "
                <div class='alert alert-danger'>
                    Có lỗi xảy ra khi cập nhật.
                </div>
            ";
        }
    }
}


/* =========================================================
   LẤY ĐƠN HÀNG CỦA USER ĐANG ĐĂNG NHẬP
   ========================================================= */
$order_stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$order_stmt->execute([$user_id]);

$orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   LẤY CHI TIẾT SẢN PHẨM TRONG CÁC ĐƠN HÀNG
   ========================================================= */
$order_items = [];

if (!empty($orders)) {

    $order_ids = array_column($orders, 'id');

    /*
     * Tạo ?,?,? tương ứng với số lượng order_id
     */
    $placeholders = implode(
        ',',
        array_fill(0, count($order_ids), '?')
    );

    $item_stmt = $pdo->prepare("
        SELECT
            oi.order_id,
            oi.quantity,
            oi.price,
            p.name AS product_name,
            p.image AS product_image
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_id = p.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");

    $item_stmt->execute($order_ids);

    $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
     * Gom sản phẩm theo từng đơn hàng
     */
    foreach ($items as $item) {

        $order_items[$item['order_id']][] = $item;
    }
}


/* =========================================================
   TÊN TRẠNG THÁI ĐƠN HÀNG
   ========================================================= */
$status_text = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao hàng',
    'completed' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
];


/* =========================================================
   MÀU TRẠNG THÁI BOOTSTRAP
   ========================================================= */
$status_class = [
    'pending'   => 'bg-warning text-dark',
    'confirmed' => 'bg-info text-dark',
    'shipping'  => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger'
];

?>
<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Tài khoản của tôi</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background-color: #f8f9fa;
        }

        .profile-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .order-card {
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            transition: 0.2s;
            background: #fff;
        }

        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .product-placeholder {
            width: 70px;
            height: 70px;
            background: #f1f1f1;
            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }

        .order-total {
            font-size: 20px;
            font-weight: 700;
            color: #dc3545;
        }

        .status-badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .empty-orders {
            padding: 50px 20px;
            text-align: center;
        }

        .empty-orders-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

    </style>

</head>


<body>

<div class="container mt-5 mb-5">

    <!-- =====================================================
         THÔNG TIN TÀI KHOẢN
         ===================================================== -->

    <div class="row justify-content-center">

        <div class="col-md-8 col-lg-6">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h2>
                    Xin chào,
                    <?= htmlspecialchars($user['name']) ?>!
                </h2>

                <a
                    href="logout.php"
                    class="btn btn-danger"
                >
                    Đăng xuất
                </a>

            </div>


            <!-- THÔNG BÁO -->

            <?= $message ?>


            <div class="card shadow-sm profile-card">

                <div class="card-body p-4">

                    <h4 class="mb-4">
                        Thông tin tài khoản
                    </h4>


                    <div class="mb-4">

                        <p>
                            <strong>Email:</strong>
                            <?= htmlspecialchars($user['email']) ?>
                        </p>

                        <p>
                            <strong>Vai trò:</strong>
                            <?= htmlspecialchars(
                                strtoupper($user['role'])
                            ) ?>
                        </p>

                        <p>
                            <strong>Ngày tham gia:</strong>
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($user['created_at'])
                            ) ?>
                        </p>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Cập nhật thông tin
                    </h5>


                    <form
                        method="POST"
                        action=""
                        onsubmit="return validateProfilePassword()"
                    >

                        <!-- SỐ ĐIỆN THOẠI -->

                        <div class="mb-3">

                            <label class="form-label">
                                Số điện thoại
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['phone'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- ĐỊA CHỈ -->

                        <div class="mb-3">

                            <label class="form-label">
                                Địa chỉ
                            </label>

                            <input
                                type="text"
                                name="address"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['address'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- ĐỔI MẬT KHẨU -->

                        <div class="alert alert-secondary mt-4">

                            <h6>
                                Đổi mật khẩu
                                <small class="text-muted">
                                    (Bỏ trống nếu không muốn đổi)
                                </small>
                            </h6>


                            <!-- MẬT KHẨU CŨ -->

                            <div class="mb-3 mt-3">

                                <label class="form-label">
                                    Mật khẩu hiện tại
                                </label>

                                <input
                                    type="password"
                                    id="old-password"
                                    name="old_password"
                                    class="form-control"
                                >

                            </div>


                            <!-- MẬT KHẨU MỚI -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Mật khẩu mới
                                </label>

                                <input
                                    type="password"
                                    id="new-password"
                                    name="new_password"
                                    class="form-control"
                                >

                            </div>


                            <!-- XÁC NHẬN -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Xác nhận mật khẩu mới
                                </label>

                                <input
                                    type="password"
                                    id="confirm-password"
                                    name="confirm_password"
                                    class="form-control"
                                >


                                <div
                                    id="profile-password-error"
                                    class="text-danger mt-1"
                                    style="
                                        display:none;
                                        font-size:0.875em;
                                    "
                                >
                                    Mật khẩu mới không khớp!
                                </div>


                                <div
                                    id="profile-empty-error"
                                    class="text-danger mt-1"
                                    style="
                                        display:none;
                                        font-size:0.875em;
                                    "
                                >
                                    Vui lòng điền đủ 3 ô nếu muốn đổi mật khẩu!
                                </div>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Lưu thay đổi
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         ĐƠN HÀNG CỦA TÔI
         ===================================================== -->

    <div class="row justify-content-center">

        <div class="col-md-10 col-lg-8">

            <div class="card shadow-sm profile-card mt-4">

                <div class="card-body p-4">

                    <h4 class="mb-4">
                        🛍️ Đơn hàng của tôi
                    </h4>


                    <?php if (empty($orders)): ?>


                        <!-- =================================================
                             CHƯA CÓ ĐƠN HÀNG
                             ================================================= -->

                        <div class="empty-orders">

                            <div class="empty-orders-icon">
                                📦
                            </div>

                            <h5>
                                Bạn chưa có đơn hàng nào
                            </h5>

                            <p class="text-muted mb-0">
                                Các đơn hàng của bạn sẽ được
                                hiển thị tại đây.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             DANH SÁCH ĐƠN HÀNG
                             ================================================= -->

                        <?php foreach ($orders as $order): ?>

                            <?php

                            $status = $order['status'];

                            $current_status_text =
                                $status_text[$status]
                                ?? $status;

                            $current_status_class =
                                $status_class[$status]
                                ?? 'bg-secondary';

                            ?>


                            <div class="order-card p-3 mb-3">


                                <!-- =========================================
                                     HEADER ĐƠN HÀNG
                                     ========================================= -->

                                <div
                                    class="
                                        d-flex
                                        justify-content-between
                                        align-items-center
                                        flex-wrap
                                        gap-2
                                    "
                                >

                                    <div>

                                        <h6 class="mb-1">

                                            Đơn hàng
                                            <strong>
                                                #<?= htmlspecialchars(
                                                    $order['id']
                                                ) ?>
                                            </strong>

                                        </h6>


                                        <div class="text-muted small">

                                            Ngày đặt:
                                            <?= date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $order['created_at']
                                                )
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- TRẠNG THÁI -->

                                    <span
                                        class="
                                            badge
                                            status-badge
                                            <?= $current_status_class ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            $current_status_text
                                        ) ?>

                                    </span>

                                </div>


                                <hr>


                                <!-- =========================================
                                     SẢN PHẨM TRONG ĐƠN
                                     ========================================= -->

                                <?php
                                $current_items =
                                    $order_items[$order['id']]
                                    ?? [];
                                ?>


                                <?php if (!empty($current_items)): ?>


                                    <?php foreach (
                                        $current_items as $item
                                    ): ?>


                                        <div
                                            class="
                                                d-flex
                                                align-items-center
                                                mb-3
                                            "
                                        >


                                            <!-- HÌNH SẢN PHẨM -->

                                            <?php
                                            $product_image =
                                                $item['product_image']
                                                ?? '';
                                            ?>


                                            <?php if (
                                                !empty($product_image)
                                            ): ?>

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $product_image
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $item['product_name']
                                                        ?? 'Sản phẩm'
                                                    ) ?>"
                                                    class="product-image"
                                                >

                                            <?php else: ?>

                                                <div
                                                    class="
                                                        product-placeholder
                                                    "
                                                >
                                                    📦
                                                </div>

                                            <?php endif; ?>


                                            <!-- THÔNG TIN SẢN PHẨM -->

                                            <div
                                                class="
                                                    ms-3
                                                    flex-grow-1
                                                "
                                            >

                                                <div
                                                    class="fw-semibold"
                                                >

                                                    <?= htmlspecialchars(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                        ?? 'Sản phẩm không tồn tại'
                                                    ) ?>

                                                </div>


                                                <div
                                                    class="
                                                        text-muted
                                                        small
                                                    "
                                                >

                                                    Số lượng:
                                                    <?= (int)
                                                        $item[
                                                            'quantity'
                                                        ] ?>

                                                </div>

                                            </div>


                                            <!-- GIÁ -->

                                            <div class="text-end">

                                                <div
                                                    class="fw-semibold"
                                                >

                                                    <?= number_format(
                                                        $item['price'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>đ

                                                </div>


                                                <div
                                                    class="
                                                        text-muted
                                                        small
                                                    "
                                                >

                                                    x
                                                    <?= (int)
                                                        $item[
                                                            'quantity'
                                                        ] ?>

                                                </div>

                                            </div>

                                        </div>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <p class="text-muted mb-0">
                                        Không có sản phẩm trong đơn hàng này.
                                    </p>


                                <?php endif; ?>


                                <hr>


                                <!-- =========================================
                                     TỔNG TIỀN
                                     ========================================= -->

                                <div
                                    class="
                                        d-flex
                                        justify-content-between
                                        align-items-center
                                        flex-wrap
                                        gap-2
                                    "
                                >

                                    <span class="fw-semibold">
                                        Tổng tiền
                                    </span>


                                    <span class="order-total">

                                        <?= number_format(
                                            $order['total_amount'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?>đ

                                    </span>

                                </div>


                                <!-- =========================================
                                     THÔNG TIN NHẬN HÀNG
                                     ========================================= -->

                                <div class="mt-3">

                                    <div class="small">

                                        <strong>
                                            Người nhận:
                                        </strong>

                                        <?= htmlspecialchars(
                                            $order['receiver_name']
                                        ) ?>

                                    </div>


                                    <div class="small mt-1">

                                        <strong>
                                            SĐT:
                                        </strong>

                                        <?= htmlspecialchars(
                                            $order['phone']
                                        ) ?>

                                    </div>


                                    <div class="small mt-1">

                                        <strong>
                                            Địa chỉ:
                                        </strong>

                                        <?= htmlspecialchars(
                                            $order['address']
                                        ) ?>

                                    </div>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT KIỂM TRA MẬT KHẨU
     ========================================================= -->

<script>

function validateProfilePassword() {

    const oldPass =
        document.getElementById('old-password').value;

    const newPass =
        document.getElementById('new-password').value;

    const confirmPass =
        document.getElementById('confirm-password').value;


    const matchError =
        document.getElementById('profile-password-error');

    const emptyError =
        document.getElementById('profile-empty-error');


    /* Reset lỗi */

    matchError.style.display = 'none';

    emptyError.style.display = 'none';


    /*
     * Kiểm tra người dùng có muốn đổi mật khẩu không
     */

    if (
        oldPass !== '' ||
        newPass !== '' ||
        confirmPass !== ''
    ) {


        /*
         * Nếu thiếu một trong ba ô
         */

        if (
            oldPass === '' ||
            newPass === '' ||
            confirmPass === ''
        ) {

            emptyError.style.display = 'block';

            return false;
        }


        /*
         * Kiểm tra mật khẩu mới có khớp không
         */

        if (newPass !== confirmPass) {

            matchError.style.display = 'block';

            return false;
        }

    }


    return true;
}

</script>


</body>
</html>