# Website bán hàng thời trang

Bài tập nhóm cuối kỳ môn **Lập trình Web**.

## Công nghệ sử dụng

- PHP
- MySQL
- HTML
- CSS
- JavaScript

## Thành viên

1. Hoàng Phùng
2. Uyễn Nghi
3. Ngân Thuỳ
4. Hoà Trọng
5. Nhật Hoàng
6. Khang Dũng

## Phân công công việc

- **Hoàng Phùng** – Hệ thống / Tổng hợp source / Database
- **Uyễn Nghi** – Giao diện người dùng
- **Ngân Thuỳ** – Trang quản trị / CRUD
- **Hoà Trọng** – Giỏ hàng / Đặt hàng
- **Nhật Hoàng** – Tài khoản / Phân quyền
- **Khang Dũng** – Tính năng nâng cao / Testing

## Yêu cầu môi trường

- PHP 8.2+
- MySQL 8.x
- Apache 2.4+ (khuyến nghị bật `mod_headers`)
- Extension PHP `mysqli`, `pdo_mysql`, `fileinfo`

## Cài đặt và chạy project

### 1. Clone project

```bash
git clone https://github.com/hoangphungtech-ops/FashionShop-PHP-mysql.git
cd FashionShop-PHP-mysql
```

### 2. Tạo cấu hình database local

Sao chép `config/database.example.php` thành `config/database.php`, sau đó sửa các giá trị fallback:

```text
DB_HOST=localhost
DB_PORT=3306
DB_NAME=fashion_shop
DB_USERNAME=root
DB_PASSWORD=mat_khau_local
```

Project không tự đọc file `.env`. Các tên trên là biến môi trường tùy chọn; nếu hosting không hỗ trợ, sửa trực tiếp fallback trong `config/database.php`. File này đã được `.gitignore` và không được commit.

### 3. Import database

Tạo database `fashion_shop`, rồi import file `database/fashion_shop.sql` bằng phpMyAdmin hoặc MySQL CLI. Kết nối luôn dùng charset `utf8mb4`.

### 4. Chạy local

Đặt project trong Apache document root (ví dụ `htdocs`) và truy cập thư mục project. Không cần cấu hình domain hoặc URL cứng.

Có thể kiểm tra nhanh bằng PHP development server:

```bash
php -S 127.0.0.1:8080 -t .
```

## Deploy Shared Hosting

1. Upload source vào document root/subdirectory mong muốn.
2. Không upload `.git`, log local hoặc credential cũ.
3. Import `database/fashion_shop.sql`.
4. Tạo `config/database.php` từ file mẫu và nhập credential hosting, hoặc cấu hình biến môi trường `DB_*`.
5. Đảm bảo `uploads/products` và `storage/logs` có quyền ghi phù hợp (thường `0755`; chỉ tăng khi hosting yêu cầu).
6. Bật HTTPS. Session cookie sẽ tự bật cờ `Secure` khi request chạy qua HTTPS.
7. Đặt `APP_ENV=production` nếu hosting cho phép. Khi không cấu hình, web request mặc định chạy production, ngoại trừ truy cập loopback local.
8. Kiểm tra homepage, products, admin và cart; sau đó xem `storage/logs/php-error.log` nếu có lỗi.

Không cần Composer và không có bước build asset.

## Helper hệ thống

- `includes/bootstrap.php`: error handling, logging, headers và session defaults.
- `includes/security.php`: escape output, safe redirect, CSRF và validate input cơ bản.
- `includes/auth.php`: foundation `require_login()` / `require_admin()` để Hoàng tích hợp.
- `includes/upload.php`: chuẩn kiểm tra và lưu ảnh an toàn để Thuỳ áp dụng.
- `docs/SECURITY_AUDIT.md`: các TODO bảo mật còn thuộc module thành viên khác.
