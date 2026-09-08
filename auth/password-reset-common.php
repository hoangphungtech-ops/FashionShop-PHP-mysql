<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

function fs_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function fs_h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
 * Load PDO an toàn.
 *
 * Điểm quan trọng:
 * Nếu includes/db.php tạo $pdo/$conn,
 * require bên trong closure sẽ lấy được biến đó bằng get_defined_vars().
 */
function fs_try_database_file(string $file): ?PDO
{
    if (!is_file($file)) {
        return null;
    }

    $loader = static function (string $file): ?PDO {

        $result = require $file;

        if ($result instanceof PDO) {
            return $result;
        }

        foreach (get_defined_vars() as $value) {
            if ($value instanceof PDO) {
                return $value;
            }
        }

        return null;
    };

    return $loader($file);
}

function fs_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseFiles = [
        __DIR__ . '/../includes/db.php',
        __DIR__ . '/../config/database.php',
    ];

    foreach ($databaseFiles as $file) {
        try {
            $candidate = fs_try_database_file($file);

            if ($candidate instanceof PDO) {
                $pdo = $candidate;

                $pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                $pdo->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

                return $pdo;
            }
        } catch (Throwable $e) {
            // Thử cơ chế tiếp theo.
        }
    }

    /*
     * Có project dùng hàm getConnection/getPDO
     * thay vì biến $pdo.
     */
    foreach (
        [
            'getPDO',
            'getPdo',
            'getConnection',
            'getDb',
            'getDB',
        ]
        as $function
    ) {
        if (!function_exists($function)) {
            continue;
        }

        try {
            $candidate = $function();

            if ($candidate instanceof PDO) {
                $pdo = $candidate;

                $pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                return $pdo;
            }
        } catch (Throwable $e) {
        }
    }

    /*
     * Cuối cùng tìm PDO đã tồn tại trong GLOBALS.
     */
    foreach ($GLOBALS as $value) {
        if ($value instanceof PDO) {
            $pdo = $value;

            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            return $pdo;
        }
    }

    throw new RuntimeException(
        'Không lấy được kết nối PDO của FashionShop.'
    );
}

function fs_identifier(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException(
            'Tên bảng hoặc cột không hợp lệ.'
        );
    }

    return '`' . $name . '`';
}

function fs_user_schema(PDO $pdo): array
{
    $tables = $pdo
        ->query('SHOW TABLES')
        ->fetchAll(PDO::FETCH_COLUMN);

    $preferred = [
        'users',
        'user',
        'accounts',
        'customers',
        'nguoi_dung',
    ];

    $ordered = [];

    foreach ($preferred as $table) {
        if (in_array($table, $tables, true)) {
            $ordered[] = $table;
        }
    }

    foreach ($tables as $table) {
        if (!in_array($table, $ordered, true)) {
            $ordered[] = $table;
        }
    }

    $emailCandidates = [
        'email',
        'user_email',
        'customer_email',
    ];

    $passwordCandidates = [
        'password',
        'password_hash',
        'passwd',
        'mat_khau',
        'matkhau',
    ];

    foreach ($ordered as $table) {

        if (!preg_match(
            '/^[A-Za-z0-9_]+$/',
            (string)$table
        )) {
            continue;
        }

        $columns = $pdo
            ->query(
                'SHOW COLUMNS FROM '
                . fs_identifier((string)$table)
            )
            ->fetchAll(PDO::FETCH_COLUMN);

        $email = null;
        $password = null;

        foreach ($emailCandidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $email = $candidate;
                break;
            }
        }

        foreach ($passwordCandidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $password = $candidate;
                break;
            }
        }

        if ($email && $password) {
            return [
                'table' => (string)$table,
                'email' => $email,
                'password' => $password,
            ];
        }
    }

    throw new RuntimeException(
        'Không xác định được bảng tài khoản.'
    );
}

function fs_ensure_reset_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            email VARCHAR(190) NOT NULL,

            token_hash CHAR(64) NOT NULL,

            expires_at DATETIME NOT NULL,

            used_at DATETIME NULL DEFAULT NULL,

            created_at TIMESTAMP NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            UNIQUE KEY uq_password_reset_token_hash
                (token_hash),

            KEY idx_password_reset_email
                (email),

            KEY idx_password_reset_expires
                (expires_at)

        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci"
    );
}

function fs_csrf_token(): string
{
    fs_start_session();

    if (
        empty($_SESSION['password_reset_csrf'])
        || !is_string(
            $_SESSION['password_reset_csrf']
        )
    ) {
        $_SESSION['password_reset_csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['password_reset_csrf'];
}

function fs_valid_csrf(?string $token): bool
{
    fs_start_session();

    if (
        !$token
        || empty($_SESSION['password_reset_csrf'])
        || !is_string(
            $_SESSION['password_reset_csrf']
        )
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['password_reset_csrf'],
        $token
    );
}

function fs_reset_url(string $token): string
{
    $https =
        !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    $scheme = $https
        ? 'https'
        : 'http';

    $host =
        $_SERVER['HTTP_HOST']
        ?? 'group.test';

    return
        $scheme
        . '://'
        . $host
        . '/auth/reset-password.php?token='
        . rawurlencode($token);
}

function fs_mail_config(): array
{
    $file =
        __DIR__
        . '/../config/mail.local.php';

    if (!is_file($file)) {
        throw new RuntimeException(
            'Chưa có config/mail.local.php.'
        );
    }

    $config = require $file;

    if (!is_array($config)) {
        throw new RuntimeException(
            'Cấu hình SMTP không hợp lệ.'
        );
    }

    return $config;
}

function fs_mail_is_configured(): bool
{
    try {
        $config = fs_mail_config();

        $required = [
            'host',
            'port',
            'username',
            'password',
            'from_email',
        ];

        foreach ($required as $key) {
            if (
                empty($config[$key])
                || str_contains(
                    (string)$config[$key],
                    'YOUR_'
                )
            ) {
                return false;
            }
        }

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

function fs_send_reset_email(
    string $email,
    string $resetUrl
): void {
    $autoload =
        __DIR__
        . '/../vendor/autoload.php';

    if (!is_file($autoload)) {
        throw new RuntimeException(
            'Chưa cài PHPMailer.'
        );
    }

    require_once $autoload;

    $config = fs_mail_config();

    if (!fs_mail_is_configured()) {
        throw new RuntimeException(
            'SMTP chưa được cấu hình.'
        );
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    $mail->CharSet = 'UTF-8';

    $mail->isSMTP();

    $mail->Host =
        (string)$config['host'];

    $mail->Port =
        (int)$config['port'];

    $mail->SMTPAuth = true;

    $mail->Username =
        (string)$config['username'];

    $mail->Password =
        (string)$config['password'];

    $encryption =
        strtolower(
            (string)(
                $config['encryption']
                ?? 'tls'
            )
        );

    if ($encryption === 'ssl') {
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom(
        (string)$config['from_email'],
        (string)(
            $config['from_name']
            ?? 'FashionShop'
        )
    );

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject =
        'Đặt lại mật khẩu FashionShop';

    $safeUrl = htmlspecialchars(
        $resetUrl,
        ENT_QUOTES,
        'UTF-8'
    );

    $mail->Body = '
        <div style="
            font-family:Arial,sans-serif;
            max-width:600px;
            margin:auto;
            color:#17362b;
            line-height:1.7
        ">
            <h2>Đặt lại mật khẩu FashionShop</h2>

            <p>
                Chúng tôi nhận được yêu cầu đặt lại
                mật khẩu cho tài khoản của bạn.
            </p>

            <p>
                <a
                    href="' . $safeUrl . '"
                    style="
                        display:inline-block;
                        padding:13px 22px;
                        background:#17362b;
                        color:#ffffff;
                        text-decoration:none;
                        font-weight:700
                    "
                >
                    Đặt lại mật khẩu
                </a>
            </p>

            <p>
                Liên kết này có hiệu lực trong
                <strong>30 phút</strong>
                và chỉ sử dụng được một lần.
            </p>

            <p style="color:#6a756e">
                Nếu bạn không yêu cầu thay đổi
                mật khẩu, bạn có thể bỏ qua email này.
            </p>
        </div>
    ';

    $mail->AltBody =
        "Đặt lại mật khẩu FashionShop\n\n"
        . "Mở liên kết sau trong vòng 30 phút:\n"
        . $resetUrl
        . "\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.";

    $mail->send();
}