<?php

declare(strict_types=1);

require_once __DIR__ . '/password-reset-common.php';

fs_start_session();

$error = '';
$success = false;

$token = trim(
    (string)(
        $_POST['token']
        ?? $_GET['token']
        ?? ''
    )
);

$tokenRow = null;
$pdo = null;

try {
    $pdo = fs_pdo();
    fs_ensure_reset_table($pdo);
} catch (Throwable $e) {
    $error =
        'Không thể kết nối hệ thống. '
        . 'Vui lòng thử lại sau.';
}

function fs_find_reset_token(
    PDO $pdo,
    string $token
): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
        return null;
    }

    $hash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        'SELECT id, email, expires_at, used_at
         FROM password_reset_tokens
         WHERE token_hash = :token_hash
         LIMIT 1'
    );

    $stmt->execute([
        ':token_hash' => $hash,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    if (!empty($row['used_at'])) {
        return null;
    }

    $expiresAt = strtotime(
        $row['expires_at'] . ' UTC'
    );

    if (
        $expiresAt === false
        || $expiresAt < time()
    ) {
        return null;
    }

    return $row;
}

if ($error === '' && $token !== '') {
    $tokenRow = fs_find_reset_token($pdo, $token);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $error === ''
) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!fs_valid_csrf(is_string($csrf) ? $csrf : '')) {
        $error =
            'Phiên làm việc đã hết hạn. '
            . 'Vui lòng thử lại.';
    }

    if ($error === '') {
        $tokenRow = fs_find_reset_token($pdo, $token);

        if (!$tokenRow) {
            $error =
                'Liên kết đặt lại mật khẩu '
                . 'không hợp lệ hoặc đã hết hạn.';
        }
    }

    if ($error === '') {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if (strlen($password) < 8) {
            $error =
                'Mật khẩu mới phải có ít nhất 8 ký tự.';
        }

        if (
            $error === ''
            && $password !== $confirm
        ) {
            $error =
                'Xác nhận mật khẩu không khớp.';
        }
    }

    if ($error === '') {
        try {
            $schema = fs_user_schema($pdo);

            $table = fs_identifier($schema['table']);
            $emailColumn = fs_identifier($schema['email']);
            $passwordColumn =
                fs_identifier($schema['password']);

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $pdo->beginTransaction();

            $update = $pdo->prepare(
                "UPDATE {$table}
                 SET {$passwordColumn} = :password
                 WHERE {$emailColumn} = :email"
            );

            $update->execute([
                ':password' => $passwordHash,
                ':email' => $tokenRow['email'],
            ]);

            if ($update->rowCount() < 1) {
                throw new RuntimeException(
                    'Không cập nhật được tài khoản.'
                );
            }

            $mark = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = :used_at
                 WHERE id = :id
                   AND used_at IS NULL'
            );

            $mark->execute([
                ':used_at' => gmdate('Y-m-d H:i:s'),
                ':id' => $tokenRow['id'],
            ]);

            if ($mark->rowCount() !== 1) {
                throw new RuntimeException(
                    'Token đã được sử dụng.'
                );
            }

            $pdo->commit();

            $success = true;
            $tokenRow = null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Không thể đặt lại mật khẩu. '
                . 'Vui lòng thử lại.';
        }
    }
}

if (
    !$success
    && $error === ''
    && !$tokenRow
) {
    $error =
        'Liên kết đặt lại mật khẩu '
        . 'không hợp lệ hoặc đã hết hạn.';
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
    <title>Đặt lại mật khẩu - FashionShop</title>
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
            background: #fff;
        }

        .reset-auth-card h1 {
            margin: 0 0 12px;
            font-family: Georgia, serif;
            font-size: 38px;
            font-weight: 500;
        }

        .reset-auth-card > p {
            margin: 0 0 28px;
            color: #6a756e;
            line-height: 1.7;
        }

        .reset-auth-field {
            margin-bottom: 18px;
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

        .reset-auth-submit,
        .reset-auth-login {
            width: 100%;
            min-height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: 0;
            background: #17362b;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .reset-auth-error {
            margin-bottom: 20px;
            padding: 13px 15px;
            background: #fff0ed;
            color: #8b3428;
            line-height: 1.6;
        }

        .reset-auth-success {
            margin-bottom: 24px;
            padding: 14px 16px;
            background: #edf5ef;
            color: #24543d;
            line-height: 1.6;
        }

        .reset-auth-back {
            display: inline-block;
            margin-top: 22px;
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

        <h1>Đặt lại mật khẩu</h1>

        <?php if ($success): ?>

            <div class="reset-auth-success">
                Đặt lại mật khẩu thành công.
            </div>

            <a
                class="reset-auth-login"
                href="login.php"
            >
                Đăng nhập ngay
            </a>

        <?php else: ?>

            <?php if ($error !== ''): ?>
                <div class="reset-auth-error">
                    <?= fs_h($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($tokenRow): ?>

                <p>
                    Nhập mật khẩu mới cho tài khoản của bạn.
                </p>

                <form method="post" autocomplete="off">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= fs_h($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="token"
                        value="<?= fs_h($token) ?>"
                    >

                    <div class="reset-auth-field">
                        <label for="password">
                            Mật khẩu mới
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <div class="reset-auth-field">
                        <label for="password_confirm">
                            Xác nhận mật khẩu mới
                        </label>

                        <input
                            id="password_confirm"
                            name="password_confirm"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <button
                        class="reset-auth-submit"
                        type="submit"
                    >
                        Đặt lại mật khẩu
                    </button>

                </form>

            <?php else: ?>

                <a
                    class="reset-auth-login"
                    href="forgot-password.php"
                >
                    Yêu cầu liên kết mới
                </a>

            <?php endif; ?>

        <?php endif; ?>

        <a
            class="reset-auth-back"
            href="login.php"
        >
            ← Quay lại đăng nhập
        </a>

    </section>

</main>

</body>
</html>