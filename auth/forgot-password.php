<?php

declare(strict_types=1);

require_once __DIR__ . '/password-reset-common.php';

fs_start_session();

$message = '';
$error = '';
$devResetUrl = '';

try {
    $pdo = fs_pdo();

    fs_ensure_reset_table($pdo);

} catch (Throwable $e) {

    $error =
        'Không thể kết nối hệ thống. '
        . 'Vui lòng thử lại sau.';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $error === ''
) {
    $csrf = (string)(
        $_POST['csrf_token']
        ?? ''
    );

    if (!fs_valid_csrf($csrf)) {

        $error =
            'Phiên làm việc đã hết hạn. '
            . 'Vui lòng tải lại trang.';
    }

    $email = trim(
        (string)(
            $_POST['email']
            ?? ''
        )
    );

    if (
        $error === ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $error =
            'Vui lòng nhập địa chỉ email hợp lệ.';
    }

    if ($error === '') {

        try {
            $schema =
                fs_user_schema($pdo);

            $table =
                fs_identifier(
                    $schema['table']
                );

            $emailColumn =
                fs_identifier(
                    $schema['email']
                );

            $stmt = $pdo->prepare(
                "SELECT {$emailColumn}
                 FROM {$table}
                 WHERE {$emailColumn} = :email
                 LIMIT 1"
            );

            $stmt->execute([
                ':email' => $email,
            ]);

            $accountExists =
                (bool)$stmt->fetchColumn();

            /*
             * Không tiết lộ ngoài giao diện
             * email có tồn tại hay không.
             */
            if ($accountExists) {

                /*
                 * Chống gửi mail liên tục:
                 * tối thiểu 60 giây giữa 2 yêu cầu.
                 */
                $cooldown = $pdo->prepare(
                    'SELECT created_at
                     FROM password_reset_tokens
                     WHERE email = :email
                     ORDER BY id DESC
                     LIMIT 1'
                );

                $cooldown->execute([
                    ':email' => $email,
                ]);

                $lastRequest =
                    $cooldown->fetchColumn();

                $canSend = true;

                if ($lastRequest) {

                    $lastTime =
                        strtotime(
                            $lastRequest
                            . ' UTC'
                        );

                    if (
                        $lastTime !== false
                        && (time() - $lastTime) < 60
                    ) {
                        $canSend = false;
                    }
                }

                if ($canSend) {

                    $token =
                        bin2hex(
                            random_bytes(32)
                        );

                    $tokenHash =
                        hash(
                            'sha256',
                            $token
                        );

                    $resetUrl =
                        fs_reset_url($token);

                    $pdo->beginTransaction();

                    /*
                     * Token cũ bị vô hiệu hóa.
                     */
                    $delete = $pdo->prepare(
                        'DELETE
                         FROM password_reset_tokens
                         WHERE email = :email'
                    );

                    $delete->execute([
                        ':email' => $email,
                    ]);

                    $insert = $pdo->prepare(
                        'INSERT INTO password_reset_tokens
                            (
                                email,
                                token_hash,
                                expires_at
                            )
                         VALUES
                            (
                                :email,
                                :token_hash,
                                :expires_at
                            )'
                    );

                    $insert->execute([
                        ':email' => $email,

                        ':token_hash' =>
                            $tokenHash,

                        ':expires_at' =>
                            gmdate(
                                'Y-m-d H:i:s',
                                time() + 1800
                            ),
                    ]);

                    $pdo->commit();

                    try {

                        fs_send_reset_email(
                            $email,
                            $resetUrl
                        );

                    } catch (Throwable $mailError) {

                        /*
                         * Nếu mail gửi thất bại,
                         * không giữ token chết.
                         */
                        $remove = $pdo->prepare(
                            'DELETE
                             FROM password_reset_tokens
                             WHERE token_hash = :hash'
                        );

                        $remove->execute([
                            ':hash' =>
                                $tokenHash,
                        ]);

                        throw $mailError;
                    }

                    /*
                     * Chỉ hiện link nếu dev_show_link=true.
                     * Production nên để false.
                     */
                    $config =
                        fs_mail_config();

                    if (
                        !empty(
                            $config['dev_show_link']
                        )
                    ) {
                        $devResetUrl =
                            $resetUrl;
                    }
                }
            }

            /*
             * Phản hồi chung để tránh dò email tài khoản.
             */
            $message =
                'Nếu email tồn tại trong hệ thống, '
                . 'một liên kết đặt lại mật khẩu '
                . 'sẽ được gửi tới email của bạn.';

        } catch (Throwable $e) {

            if (
                $pdo instanceof PDO
                && $pdo->inTransaction()
            ) {
                $pdo->rollBack();
            }

            /*
             * Local chưa cấu hình SMTP thì báo rõ
             * để quản trị viên biết cần cấu hình.
             */
            if (!fs_mail_is_configured()) {

                $error =
                    'Hệ thống email chưa được cấu hình. '
                    . 'Vui lòng cấu hình SMTP để gửi '
                    . 'email đặt lại mật khẩu.';

            } else {

                $error =
                    'Không thể gửi email lúc này. '
                    . 'Vui lòng thử lại sau.';
            }
        }
    }
}

$csrfToken = fs_csrf_token();

?>
<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Quên mật khẩu - FashionShop</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

<style>

body.reset-auth-page {
    min-height: 100vh;
    margin: 0;

    background:
        linear-gradient(
            135deg,
            #fbfaf6 0%,
            #f3f6f2 100%
        );

    color: #17362b;
}

.reset-auth-shell {
    width: min(100% - 32px, 520px);
    margin: 0 auto;
    padding: 72px 0;
}

.reset-auth-brand {
    display: block;
    margin-bottom: 34px;

    color: #17362b;

    font-size: 23px;
    font-weight: 800;

    text-decoration: none;
}

.reset-auth-brand span {
    color: #87a393;
    font-weight: 400;
}

.reset-auth-card {
    padding: 38px;

    border: 1px solid #e2e6e1;

    background: #ffffff;
}

.reset-auth-card h1 {
    margin: 0 0 12px;

    font-family: Georgia, serif;

    font-size: 38px;
    font-weight: 500;
}

.reset-auth-card > p {
    margin: 0 0 30px;

    color: #6a756e;

    line-height: 1.7;
}

.reset-auth-field {
    margin-bottom: 20px;
}

.reset-auth-field label {
    display: block;

    margin-bottom: 8px;

    font-size: 13px;
    font-weight: 700;
}

.reset-auth-field input {
    width: 100%;
    min-height: 50px;

    box-sizing: border-box;

    padding: 0 14px;

    border: 1px solid #dfe4df;

    font-size: 15px;
}

.reset-auth-submit {
    width: 100%;
    min-height: 52px;

    border: 0;

    background: #17362b;
    color: #ffffff;

    font-weight: 700;

    cursor: pointer;
}

.reset-auth-message {
    margin-bottom: 20px;
    padding: 14px 16px;

    background: #edf5ef;

    color: #24543d;

    line-height: 1.6;
}

.reset-auth-error {
    margin-bottom: 20px;
    padding: 14px 16px;

    background: #fff0ed;

    color: #8b3428;

    line-height: 1.6;
}

.reset-auth-dev {
    margin-top: 18px;
    padding: 14px;

    border: 1px dashed #87a393;

    background: #f6faf6;

    overflow-wrap: anywhere;
}

.reset-auth-back {
    display: inline-block;

    margin-top: 24px;

    color: #17362b;

    font-weight: 600;

    text-decoration: none;
}

@media (max-width: 600px) {

    .reset-auth-shell {
        padding: 38px 0;
    }

    .reset-auth-card {
        padding: 26px 20px;
    }

    .reset-auth-card h1 {
        font-size: 32px;
    }
}

</style>

</head>

<body class="reset-auth-page">

<main class="reset-auth-shell">

<a
    href="../"
    class="reset-auth-brand"
>
    FASHION<span>SHOP</span>
</a>

<section class="reset-auth-card">

<h1>Quên mật khẩu</h1>

<p>
    Nhập email đã đăng ký.
    Hệ thống sẽ gửi liên kết đặt lại mật khẩu cho bạn.
</p>

<?php if ($message !== ''): ?>

<div class="reset-auth-message">
    <?= fs_h($message) ?>
</div>

<?php endif; ?>

<?php if ($error !== ''): ?>

<div class="reset-auth-error">
    <?= fs_h($error) ?>
</div>

<?php endif; ?>

<form method="post">

<input
    type="hidden"
    name="csrf_token"
    value="<?= fs_h($csrfToken) ?>"
>

<div class="reset-auth-field">

<label for="email">
    Email
</label>

<input
    id="email"
    name="email"
    type="email"
    autocomplete="email"
    required
    placeholder="example@email.com"
>

</div>

<button
    type="submit"
    class="reset-auth-submit"
>
    Gửi liên kết đặt lại mật khẩu
</button>

</form>

<?php if ($devResetUrl !== ''): ?>

<div class="reset-auth-dev">

<strong>
    DEV MODE:
</strong>

<a href="<?= fs_h($devResetUrl) ?>">
    <?= fs_h($devResetUrl) ?>
</a>

</div>

<?php endif; ?>

<a
    href="login.php"
    class="reset-auth-back"
>
    ← Quay lại đăng nhập
</a>

</section>

</main>

</body>
</html>