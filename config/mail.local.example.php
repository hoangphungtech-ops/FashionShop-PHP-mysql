<?php

return [
    /*
     * Gmail SMTP:
     * - username: Gmail dùng để gửi mail
     * - password: App Password 16 ký tự, KHÔNG dùng mật khẩu Gmail thường
     */

    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',

    'username' => 'YOUR_GMAIL@gmail.com',
    'password' => 'YOUR_16_CHARACTER_APP_PASSWORD',

    'from_email' => 'YOUR_GMAIL@gmail.com',
    'from_name' => 'FashionShop',

    /*
     * false = gửi email thật, không hiện token trên web.
     * Chỉ đổi true khi cần debug local.
     */
    'dev_show_link' => false,
];