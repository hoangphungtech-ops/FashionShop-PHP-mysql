# Security audit và TODO tích hợp

Ngày audit: 2026-08-31. Phạm vi sửa: **SYSTEM / INTEGRATION của Phùng**.

## Nền tảng đã chuẩn bị

- Credential database chỉ nằm trong `config/database.php` (đã Git ignore); file mẫu không chứa mật khẩu thật.
- mysqli và PDO dùng chung một cấu hình, charset `utf8mb4`, prepared statement native cho PDO.
- Bootstrap chung cấu hình error log, chế độ development/production, security headers và session cookie an toàn.
- Helper dùng chung cho HTML escaping, redirect nội bộ, CSRF, input số/email/text và upload ảnh.
- Authorization foundation có `require_login()` và `require_admin()` trong `includes/auth.php`.
- Apache chặn directory listing, file cấu hình, SQL dump, log và thực thi script trong `uploads/`.

## Vấn đề còn lại theo người phụ trách

Các dòng dưới đây chỉ là audit; chưa sửa để tránh thay đổi chức năng của thành viên khác.

| Mức độ | File/module | Vấn đề | Người nên sửa |
|---|---|---|---|
| Critical | `auth/profile.php:4` | Require `auth/auth_check.php` nhưng file không tồn tại, trang profile lỗi fatal. Cần adapter auth của Hoàng dùng foundation chung. | Hoàng |
| Critical | `admin/index.php`, `admin/categories/*`, `admin/products/*` | Chưa gọi `require_admin()`, người chưa đăng nhập/user thường có thể vào CRUD. | Hoàng phối hợp Thuỳ |
| Critical | `cart/history.php:42-60` | Khi chưa đăng nhập đang truy vấn và hiển thị toàn bộ đơn hàng. | Trọng |
| Critical | `cart/Order_detail.php:21-34` | Truy vấn theo `order_id` nhưng không ràng buộc chủ sở hữu; có nguy cơ IDOR/lộ đơn người khác. | Trọng |
| High | `cart/add.php:80-92`, `cart/checkout.php:39-40` | Cấu trúc session cart không đồng nhất: add lưu `product_id => quantity`, checkout lại đọc `price/quantity`; có thể warning, sai tổng tiền và hỏng checkout khi giỏ có hàng. | Trọng |
| High | `admin/categories/delete.php`, `admin/products/delete.php` | Xóa dữ liệu bằng GET và không có CSRF; cần POST + CSRF + admin guard. | Thuỳ |
| High | Form create/edit Admin | Chưa có CSRF; một số `catch` đưa nguyên lỗi SQL ra giao diện. | Thuỳ |
| High | `cart/add.php`, `cart/checkout.php` | Thay đổi dữ liệu/đặt hàng chưa có CSRF; `add.php` thay đổi giỏ bằng GET. | Trọng |
| High | `auth/profile.php`, `auth/register.php` | Request thay đổi dữ liệu chưa có CSRF. | Hoàng |
| Medium | `auth/logout.php` | Khởi tạo session trực tiếp, chưa dùng cookie settings/destroy helper chung; logout bằng GET. | Hoàng |
| Medium | `cart/checkout.php`, `cart/history.php`, `cart/Order_detail.php` | `session_start()` chạy trước bootstrap nên cấu hình cookie an toàn chưa áp dụng cho request đầu tiên. | Trọng |
| Medium | `cart/add.php:42-44`, `cart/index.php:48-50` | Hiển thị trực tiếp message của PDO khi truy vấn lỗi. | Trọng |
| Medium | `products/index.php:48-50`, `products/detail.php:44-46` | Hiển thị trực tiếp message của PDO khi truy vấn lỗi. | Dũng/Nghi |
| Medium | `admin/products/create.php`, `admin/products/edit.php` | Chưa có upload ảnh thực tế. Khi triển khai, dùng `store_uploaded_image()` thay vì tin tên/extension từ client. | Thuỳ |

## Cách tích hợp đề xuất (không tự động áp dụng)

```php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
```

Trong form POST:

```php
<?= csrf_field() ?>
```

Trước khi xử lý thay đổi dữ liệu:

```php
csrf_protect();
```

Sau khi đăng nhập thành công, login hiện đã gọi `session_regenerate_id(true)`. Hoàng có thể đổi sang `regenerate_session_id_after_login()` để dùng chung foundation.

## Kết quả audit input/output

- Các câu SQL có input được kiểm tra đều dùng prepared statement hoặc ép kiểu ID; chưa thấy SQL injection trực tiếp rõ ràng.
- Phần lớn output động đã dùng `htmlspecialchars`; helper `e()` chuẩn hóa `ENT_QUOTES`, `ENT_SUBSTITUTE`, UTF-8 cho code mới.
- Chưa thấy redirect nhận URL trực tiếp từ người dùng; helper `safe_redirect()` sẵn sàng cho luồng redirect mới.
- Không có code upload file hiện hành; helper mới allowlist JPG/PNG/WEBP theo MIME thật, giới hạn 5 MB và sinh tên ngẫu nhiên.
