<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

require_admin(app_url('auth/login.php'));

if (!is_post_request()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Phương thức không được hỗ trợ.');
}

if (!csrf_validate(is_string($_POST['_csrf_token'] ?? null) ? $_POST['_csrf_token'] : null)) {
    admin_flash('error', 'Phiên thao tác đã hết hạn. Vui lòng thử lại.');
    safe_redirect('index.php', 'index.php', 303);
}

$id = input_int($_POST, 'id');

if ($id === null) {
    admin_flash('error', 'Sản phẩm không hợp lệ.');
    safe_redirect('index.php', 'index.php', 303);
}

try {
    $referenceStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM order_items WHERE product_id = :id'
    );
    $referenceStatement->execute([':id' => $id]);

    if ((int) $referenceStatement->fetchColumn() > 0) {
        $statement = $pdo->prepare('UPDATE products SET status = 0 WHERE id = :id');
        $statement->execute([':id' => $id]);
        admin_flash('success', 'Sản phẩm đã có trong đơn hàng nên được chuyển sang ngừng bán để giữ lịch sử.');
    } else {
        $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute([':id' => $id]);
        admin_flash(
            $statement->rowCount() === 1 ? 'success' : 'error',
            $statement->rowCount() === 1 ? 'Đã xóa sản phẩm.' : 'Sản phẩm không tồn tại.'
        );
    }
} catch (PDOException $exception) {
    error_log('[admin-product-delete] Delete failed: ' . $exception->getMessage());
    admin_flash('error', 'Không thể xóa sản phẩm lúc này.');
}

safe_redirect('index.php', 'index.php', 303);
