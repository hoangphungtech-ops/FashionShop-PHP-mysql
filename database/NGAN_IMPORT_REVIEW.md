# Kiểm tra tích hợp sản phẩm Ngân — 08/09/2026

## Trạng thái

**Dừng ở giai đoạn 1. Không import thật vì thiếu toàn bộ ảnh, chưa xác nhận đơn vị giá và hai sản phẩm có nội dung/category chưa nhất quán.**

Project: `C:\xampp\htdocs\FashionShop-Group` — website `http://group.test/`.

Nguồn: `C:\Users\ACER\OneDrive - ut.edu.vn\Desktop\Lập trình Web\FashionShop-PHP-mysql-main (2)\FashionShop-PHP-mysql-main\assets\images\Sản Phẩm Ảnh Của Ngân`.

Đúng bộ dữ liệu là `categories.sql`, `products.sql`, `product_images.sql` trong thư mục nguồn: dump ngày 07/09/2026, có tên sản phẩm và tên ảnh `product_6a...`. Các SQL khác trong hai project là schema/demo, không phải bộ Ngân. Không thực thi SQL dump, CREATE/ALTER hoặc thiết lập AUTO_INCREMENT trong dump.

## Số liệu đã xác minh

| Nội dung | Kết quả |
|---|---:|
| Sản phẩm Ngân | 19 |
| Category Ngân | 5 |
| Dòng product_images nguồn | 76 |
| Tên ảnh duy nhất được tham chiếu | 93 (36 JPG, 57 PNG) |
| Tổng file thật trong thư mục nguồn | 3, đều là SQL |
| JPG / JPEG / PNG / WEBP thật | 0 / 0 / 0 / 0 |
| Ảnh thiếu | 93 |
| Ảnh có nhưng không được SQL dùng | 0 |
| Dự kiến ảnh chính / gallery đích | 19 / 74 |
| Sản phẩm / gallery đã import | 0 / 0 |
| Sản phẩm được nhận diện đã import, SKIPPED | 0 |

Danh sách đầy đủ 93 tên ảnh thiếu và từng sản phẩm nằm trong `ngan-dry-run-reviewed.json`, khóa `missing_images` và `products`. Đã tìm tên ảnh `product_6a*` trong cả project nguồn và Group: không thấy file ảnh.

## Schema và mapping

Schema đọc trực tiếp qua config hiện tại, không đoán từ dump. Trước dry-run: categories = 3, products = 7, product_images = 0. ID sản phẩm đang có: 2, 3, 4, 6, 7, 9, 32. ID nguồn có giao nhau nên tuyệt đối không giữ ID; importer lấy `lastInsertId()` sau từng INSERT. Không dự đoán ID từ AUTO_INCREMENT hiện tại.

| Category nguồn | Đích | Hành động |
|---|---|---|
| 1 — đầm váy | Váy, ID 3 | Reuse, chờ xác nhận ID sản phẩm 8 |
| 2 — áo | Áo, ID 1 | Reuse |
| 3 — chân váy | Chân váy | Tạo mới khi apply |
| 4 — quần | Quần, ID 2 | Reuse, chờ xác nhận ID sản phẩm 13 |
| 5 — cardigan | Cardigan | Tạo mới khi apply |

ID 8 có tên “Đồ Nữ Áo Sơ Mi Peplum Tay Bồng Hồng” nhưng category đầm váy và mô tả bộ váy; ID 13 tên “Quần Tây Nữ Ống Rộng” nhưng mô tả chân váy. Không tự sửa nội dung hay phân loại.

Group products dùng id, category_id, name, slug, description, price, stock, image, status, created_at, updated_at. Tên tối đa 150 ký tự (nguồn 255); slug tối đa 180 (nguồn 255). Tất cả tên nguồn nằm trong giới hạn. Group categories cần slug, không có description. Group product_images chỉ có id, product_id, image; chuyển image_path → image, không thêm is_primary hay created_at vào schema.

Đã đọc products/index.php, products/detail.php, admin/products/index.php, create.php, edit.php và helper ảnh/slug. Admin lưu upload trong `uploads/products/`. Đích dự kiến: `uploads/products/ngan/`; database lưu path relative đầy đủ. Chưa copy ảnh nào. Không chỉnh trang PHP, header/footer, hero, CSS hay JS hiện tại.

Ưu tiên ảnh đánh dấu is_primary=1 làm products.image. 17 sản phẩm có ảnh products.image khác ảnh primary; giữ những ảnh này trong gallery để không mất tham chiếu. Loại ảnh chính khỏi gallery và loại tham chiếu lặp. Vì vậy 76 dòng ảnh nguồn chuyển thành 74 gallery đích, cùng 19 ảnh chính (93 file tổng cộng).

Slug nguồn chỉ là “áo”, “váy”, “quần”, trùng và có dấu. Importer tạo slug từ tên sản phẩm, chuyển dấu tiếng Việt tường minh trước khi gọi helper admin hiện tại (iconv Windows không chuyển đúng tất cả dấu), kiểm tra URL-safe và thêm hậu tố nếu trùng. Không thay đổi slug sản phẩm cũ.

Giữ nguyên stock, status 0/1 và created_at nguồn. ID 6 có stock=0: giữ hết hàng, không tự nâng tồn kho. updated_at dùng mặc định Group.

## Giá và field chưa hỗ trợ

Giá nguồn 174–650; original_price 225–950. Group đang lưu 299.000–1.510.000 đồng; các trang dùng number_format(price, 0, ',', '.') + đ, không nhân 1000. Điều này gợi ý giá nguồn có thể là nghìn đồng nhưng chưa đủ xác nhận. **Chưa áp dụng conversion nào.** Chỉ cho phép `--price-multiplier=1000` hoặc `1` sau khi chủ dữ liệu xác nhận; thiếu tham số sẽ chặn apply.

Các field của Ngân chưa được import vì schema hiện tại chưa hỗ trợ: products.original_price, gallery (toàn bộ NULL), size, color, material; categories.description (NULL); product_images.created_at. is_primary được sử dụng để chọn ảnh chính, không lưu thêm cột. Không tự ghép các field vào description.

## Chạy importer

Importer CLI: `database/import_ngan_products_safe.php`. Mặc định chỉ dry-run; `--apply` mới ghi dữ liệu. Cấm gọi qua web, và thư mục database đã có .htaccess chặn HTTP.

```powershell
$nganSource = 'C:\Users\ACER\OneDrive - ut.edu.vn\Desktop\Lập trình Web\FashionShop-PHP-mysql-main (2)\FashionShop-PHP-mysql-main\assets\images\Sản Phẩm Ảnh Của Ngân'
& C:\xampp\php\php.exe database/import_ngan_products_safe.php --dry-run "--source=$nganSource"
```

Sau khi bổ sung đầy đủ 93 ảnh và xác nhận giá/category, chạy dry-run lại với các tham số đã xác nhận. `--confirm-categories` nghĩa là chủ dữ liệu chấp nhận giữ category nguồn cho ID 8 và 13. Không dùng cờ này để bỏ qua một quyết định chưa có. Chỉ chuyển sang `--apply` khi dry-run sạch; dùng đúng các tham số giá/category của lần kiểm tra. `--report=<file-mới.json>` ghi báo cáo UTF-8 không BOM, không ghi đè báo cáo cũ. Mã thoát: 0 thành công, 2 bị chặn dữ liệu, 1 lỗi kỹ thuật.

Chống trùng bằng name + đường dẫn ảnh chính đích. Chạy lại cùng dữ liệu sẽ SKIPPED sản phẩm đã tồn tại; không cập nhật giá, stock hoặc gallery của sản phẩm đã có. Nếu đã đổi tên/ảnh sản phẩm sau import, phải đối chiếu audit mapping trước khi chạy lại. GET_LOCK ngăn hai bản importer chạy đồng thời. Tránh thao tác admin ghi dữ liệu trong lúc import để bản backup phản ánh thời điểm chuyển đổi rõ ràng.

## Backup và rollback

Dry-run không INSERT/UPDATE/DELETE, không copy ảnh, không tăng AUTO_INCREMENT; đọc và đối chiếu mọi dòng cũ trước rollback transaction chỉ đọc.

Apply dùng prepared statements, transaction InnoDB, không ALTER hoặc xóa dữ liệu. Trước mutation, ghi backup JSON gồm schema và toàn bộ dòng của đúng categories/products/product_images vào `storage/ngan-import/<run>-before.json`. Thư mục storage có .htaccess chặn HTTP. Không backup users/password/config. Trước commit ghi `<run>-transaction.json` chứa mapping old→new, chính xác ID mới và snapshot sau import. File này ghi trước COMMIT nên phải đối chiếu DB nếu máy dừng ngay lúc commit.

Nếu lỗi trước commit: rollback SQL tự động. Ảnh đã copy được giữ lại, không xóa ảnh cũ hay nguồn; lần chạy sau reuse khi SHA-256 khớp. Copy dùng chế độ tạo file độc quyền, trùng tên khác nội dung sẽ thêm SHA-256. Không khôi phục backup bằng ghi đè các bảng.

Nếu cần hoàn tác sau commit: đọc đúng audit của run, kiểm tra từng ID mới và nội dung với snapshot; kiểm tra toàn bộ foreign key trỏ đến sản phẩm (đơn hàng/giỏ hàng/phụ thuộc mới). Chỉ khi chưa được sử dụng và dữ liệu khớp mới xây dựng transaction hoàn tác theo danh sách ID của run: gallery mới, sản phẩm mới, cuối cùng category mới nếu không còn tham chiếu. Không xóa theo khoảng ID, không chạy xóa hàng loạt, không reset AUTO_INCREMENT, không xóa file ảnh. Chưa thực thi bất kỳ rollback sau commit nào vì chưa import.

## Kiểm thử và giới hạn

PHP lint importer: PASS. Dry-run thực tế: BLOCKED (93 ảnh thiếu, giá/category chưa xác nhận); không có thay đổi DB. Nhánh ghi dữ liệu và rollback lỗi giữa chừng chưa chạy với DB thật vì nguồn đang bị chặn; cần kiểm thử tiếp khi đủ dữ liệu. Chưa thể mở 3–5 sản phẩm Ngân hoặc kiểm thử thêm giỏ hàng do chưa có ID mới. Kết quả website và git cuối cùng xem báo cáo bàn giao.

Các thay đổi git đã có từ đầu: assets/css/style.css, assets/js/main.js, auth/profile.php, includes/footer.php, index.php, products/detail.php, products/index.php; untracked auth/index.php và pages/. Không add/commit/push/merge.
