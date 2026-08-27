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

- PHP 8.x
- MySQL 8.x
- Extension PHP `mysqli`

## Cài đặt và chạy project

### 1. Clone project

```bash
git clone https://github.com/hoangphungtech-ops/FashionShop-PHP-mysql.git
WORK PHỤ LỤC 2 – DEPLOY WEBSITE

Nhóm mình sẽ triển khai website theo hướng Shared Hosting + Domain riêng để có 1 link chung cho cả nhóm kiểm tra và dùng luôn khi demo chính thức.

Chi phí: Domain + Hosting sẽ chia đều 6 thành viên. Phùng sẽ kiểm tra gói phù hợp, báo chi phí cụ thể cho nhóm trước khi mua.

Phân công
Phùng – Tổng hợp/Deploy: Chuẩn bị Domain + Hosting, export/import Database, tạo Database/User trên Hosting, cấu hình kết nối DB server, upload Group-test, cấu hình HTTPS, cập nhật source và deploy bản cuối.
Nghi – Frontend User: Kiểm tra/sửa giao diện Trang chủ, danh sách/chi tiết sản phẩm, hình ảnh và responsive trên website online.
Thuỳ – Admin: Kiểm tra/sửa Admin Dashboard, CRUD sản phẩm/danh mục, Editor, upload và quản lý ảnh trên Hosting.
Trọng – Cart & Orders: Kiểm tra/sửa thêm/xóa/cập nhật giỏ hàng, checkout, lưu đơn hàng và lịch sử đơn hàng.
Hoàng – Account: Hoàn thiện phần tài khoản còn thiếu trước, gồm đăng ký, đăng nhập, đăng xuất, profile và phân quyền User/Admin; sau đó kiểm tra trên website online.
Dũng – Advanced & Testing: Kiểm tra search, filter, pagination; tổng hợp checklist lỗi và kiểm thử toàn hệ thống sau khi deploy.
Quy trình làm việc
Mỗi người sửa trên branch cá nhân
        ↓
Test local
        ↓
Commit + Push branch
        ↓
Báo Phùng
        ↓
Phùng merge vào Group-test
        ↓
Cập nhật website chung
        ↓
Cả nhóm test trên 1 domain
        ↓
Ai lỗi phần nào → tự sửa branch phần đó
        ↓
PASS toàn bộ
        ↓
Merge Group-test → main
        ↓
Deploy bản chính thức trên cùng domain

Quy định: Không sửa trực tiếp main hoặc Group-test. Mỗi người chịu trách nhiệm sửa đúng phần mình được phân công, push lên branch cá nhân rồi báo Phùng merge để tránh ghi đè code và Merge Conflict.

Mục tiêu cuối: 1 Domain chung → vừa dùng để test tích hợp, vừa dùng làm link demo chính thức khi hoàn thiện.
