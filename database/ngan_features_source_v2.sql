-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 07, 2026 lúc 06:44 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `fashion_shop`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `original_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) DEFAULT 0,
  `size` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `price`, `original_price`, `description`, `image`, `gallery`, `status`, `created_at`, `stock`, `size`, `color`, `material`) VALUES
(1, 2, 'Áo thun cổ V xẻ viền chỉ', 'Áo', 174.00, 225.00, '<p>Áo thun cộc tay phong cách Casual nổi bật với thiết kế cổ xẻ chữ V thanh thoát và điểm nhấn đường chỉ viền nổi màu nâu tương phản đầy tinh tế. Được may từ chất liệu thun Cotton cao cấp mềm mịn, co giãn và thoáng mát, sản phẩm sở hữu phom dáng ôm vừa vặn (Regular fit) cùng tông màu kem trang nhã, rất dễ dàng kết hợp cùng quần jeans, quần cạp cao hay chân váy cho trang phục dạo phố và đi làm hàng ngày.</p>', 'product_6a944e45c52727.60503924.jpg', NULL, 1, '2026-08-30 15:33:56', 31, 'S,M,L,XL,XXL', 'be', 'Cotton'),
(4, 2, 'Áo Thun Ringer L\'oeil Studio Vintage', 'áo', 185.00, 225.00, '<p>Áo thun ringer \"Loeil Studio\" sở hữu thiết kế vintage trẻ trung với gam màu trắng kem phối viền cổ và tay áo xanh dương nổi bật. Được làm từ chất liệu cotton mềm mại, thoáng mát cùng form dáng suông rộng rãi, sản phẩm mang lại sự thoải mái tối đa cho người mặc. Điểm nhấn của áo là phần họa tiết in chữ cổ điển trước ngực, dễ dàng phối hợp cùng quần jeans hay chân váy cho các buổi đi học, đi chơi hàng ngày.</p><p>&nbsp;</p>', 'product_6a95043e37d7e7.06710049.jpg', NULL, 1, '2026-08-30 15:56:41', 14, 'S,M,L,XL', 'trắng', 'Cotton'),
(5, 2, 'Áo Thun Baby Tee', 'áo', 179.00, 225.00, '<p>Áo thun raglan phối màu hồng - đen mang phong cách thể thao trẻ trung, cá tính. Sản phẩm làm từ chất liệu cotton co giãn, thoáng mát với form dáng suông thoải mái, điểm xuyết chi tiết 3 sọc trắng ở tay áo cùng họa tiết in số 93 cổ điển trước ngực, dễ dàng mix-match cho các outfit hàng ngày.</p>', 'product_6a9508ae04bf83.34670908.jpg', NULL, 1, '2026-08-31 04:52:09', 16, 'S,M,L', 'hồng', 'Cotton'),
(6, 1, 'Đầm Chấm Bi Lệch Vai', 'váy', 300.00, 550.00, '<p>Đầm xòe xám chấm bi thiết kế lệch vai quyến rũ, nổi bật với chi tiết nơ trắng to bản tạo điểm nhấn nữ tính, thanh lịch cùng phần chân váy xếp ly xòe nhẹ giúp tôn dáng hoàn hảo cho các buổi tiệc hay dạo phố.</p>', 'product_6a950aaf10afa0.76117007.jpg', NULL, 1, '2026-08-31 04:57:55', 0, 'S,M,L,XL,XXL', 'xám', 'Kaki thun cao cấp'),
(7, 1, 'Đầm Yếm Nữ Đen Phối Áo Sơ Mi Trắng', 'váy', 325.00, 600.00, '<p>Được dệt từ chất liệu vải kaki lụa đứng form, dày dặn vừa phải, hạn chế nhăn và co giãn nhẹ giúp ôm dáng, set trang phục này mang đến vẻ đẹp vừa thanh lịch, vừa cổ điển với phần thân váy đen thêu họa tiết hình học chìm, chân váy xếp ly nhẹ và phần nơ cổ tháo rời được phối nơ ngọc trai sang trọng, hoàn hảo cho các buổi tiệc hay đi làm hàng ngày.</p>', 'product_6a950af3ac77e7.41048516.jpg', NULL, 1, '2026-08-31 04:58:26', 11, 'S,M,L,XL,XXL', 'đen', 'Kaki thun cao cấp'),
(8, 1, 'Đồ Nữ Áo Sơ Mi Peplum Tay Bồng Hồng', 'váy', 324.00, 550.00, '<p>Được dệt từ chất liệu vải kaki lụa (hoặc vải tây cao cấp) đứng form, dày dặn vừa phải, hạn chế nhăn và co giãn nhẹ giúp ôm dáng, set trang phục này mang đến vẻ đẹp vừa thanh lịch, vừa cổ điển với phần thân váy đen thêu họa tiết hình học chìm, chân váy xếp ly nhẹ và phần nơ cổ tháo rời được phối nơ ngọc trai sang trọng, hoàn hảo cho các buổi tiệc hay đi làm hàng ngày.</p>', 'product_6a950c0b64e256.10175962.jpg', NULL, 1, '2026-08-31 04:58:59', 11, 'S,M,L,XL', 'hồng đen', 'Kaki thun cao cấp'),
(9, 3, 'Chân Váy Ngắn Chữ A Cạp Cao', 'váy', 199.00, 285.00, '<p>Chân váy chữ A dáng ngắn màu nâu mang phong cách thanh lịch, được cắt may từ chất liệu dày dặn, bề mặt nhám nhẹ giúp giữ form chuẩn. Mặt trước váy nổi bật với phần cạp cao có đỉa luồn thắt lưng, khuy cài tròn đồng màu, hai túi xéo hai bên hông và chi tiết cơi túi giả ngang tạo điểm nhấn cá tính</p>', 'product_6a950d112f7225.49409242.jpg', NULL, 1, '2026-08-31 05:00:32', 22, 'S,M,L,XXL', 'be', 'kaki'),
(10, 3, 'Chân Váy Ngắn Màu Kem Be Nữ', 'váy', 198.00, 285.00, '<p>Chân váy denim dáng ngắn màu trắng kem mang đến phong cách trẻ trung và hiện đại, được may từ chất liệu jean dày dặn giữ phom chuẩn. Mặt trước thiết kế cạp cao nổi bật với đỉa đai thắt lưng kèm khóa kim loại cá tính, cúc kim loại và chi tiết túi xéo phối túi nhỏ đóng đinh rivê chắc chắn. Mặt sau may chỉ chìm tỉ mỉ với cạp đai điều chỉnh, mác da thương hiệu sang trọng cùng hai túi ốp vuông vức.</p>', 'product_6a950e8a11a864.03046700.jpg', NULL, 1, '2026-08-31 05:14:13', 21, 'S,M,L', 'trắng', 'kaki thun'),
(11, 3, 'Chân Váy Dài Nữ Dáng A Xòe', 'váy', 325.00, 500.00, '<p>Chân váy xòe dáng midi màu kem beige mang phong cách tối giản, nhã nhặn và nữ tính. Váy được may từ chất liệu vải đứng phom, các đường xếp ly gân dập nổi chạy dọc thân giúp tôn dáng và tạo độ rủ tự nhiên. Mặt trước nổi bật với phần cạp cao vừa vặn kèm thắt lưng da mảnh màu nâu trầm cá tính; mặt sau ôm gọn gàng tạo tổng thể chỉn chu, thanh lịch, thích hợp cho cả không gian công sở lẫn những buổi dạo phố nhẹ nhàng.</p>', 'product_6a950f952a7c29.22722959.jpg', NULL, 1, '2026-08-31 05:14:55', 17, 'S,M,L,XL,XXL', 'be', 'kaki thun cao cấp'),
(12, 4, 'Quần Short Tây Nữ', 'quần', 185.00, 300.00, '<p>Quần short giả váy màu nâu tây mang phong cách thanh lịch, hiện đại và vô cùng tiện lợi. Thiết kế cạp cao nổi bật với các đường xếp ly chìm tinh tế ở mặt trước giúp hack dáng, che khuyết điểm bụng và tôn vòng 3 khéo léo. Quần đi kèm thắt lưng da mảnh màu nâu thẫm với mặt khóa kim loại thanh thoát, tạo điểm nhấn chỉn chu cho tổng thể.&nbsp;</p>', 'product_6a951036eb2748.25897447.jpg', NULL, 1, '2026-08-31 05:15:26', 20, 'S,M,L,XL,XXL', 'nâu', 'Kaki cao cấp'),
(13, 4, 'Quần Tây Nữ Ống Rộng', 'quần', 350.00, 500.00, '<p>Chân váy dài dáng chữ A màu nâu tây mang phong cách tối giản, thanh lịch và mộc mạc. Váy được làm từ chất liệu vải dệt nổi vân gân mịn, vừa tạo bề mặt có chiều sâu tự nhiên vừa giữ phom mềm rủ nhẹ nhàng. Mặt trước nổi bật với phần cạp cao có đỉa đai, khuy cài tròn vân đồi mồi thời trang và túi xéo hông xẻ chéo trẻ trung. Mặt sau được may ôm tinh tế với đường gân sống lưng chạy dọc kéo dài, kết hợp cùng hai túi ốp vuông vức tiện lợi</p>', 'product_6a95110ff119c6.73415053.jpg', NULL, 1, '2026-08-31 05:16:37', 15, 'S,M,L,XL,XXL', 'nâu', 'vân đũi cao cấp'),
(14, 4, 'Quần Tây Nữ Cạp Cao Phối Viền', 'quần', 389.00, 650.00, '<p>Quần ống rộng dáng suông màu xám ghi mang phong cách streetwear hiện đại, phá cách và cá tính. Thiết kế cạp cao nổi bật với chi tiết viền ren chạy dọc lưng quần, đỉa đai thắt lưng kèm logo kim loại đính nổi tinh tế. Mặt trước tạo điểm nhấn bằng các đường xếp ly sâu giúp tăng độ rủ và kéo dài đôi chân tối đa. Mặt sau được cắt may ôm dáng gọn gàng với hai túi cơi ngang lịch sự.</p>', 'product_6a9511c87a7951.68311707.jpg', NULL, 1, '2026-08-31 05:17:09', 30, 'S,M,L,XL', 'xám', 'tây cao cấp'),
(15, 4, 'Quần Cargo Nữ Ống Rộng', 'quần', 265.00, 550.00, '<p>Sản phẩm sở hữu thiết kế xếp lớp (layering) màu xám ghi vô cùng độc đáo và đậm chất thời trang đường phố. Điểm nhấn nổi bật nằm ở phần lớp vạt váy ngắn bất đối xứng phủ bên ngoài chiếc quần ống rộng dáng suông dài. Mặt trước cạp quần thiết kế cài cúc lệch cá tính, kết hợp cùng hệ thống túi hộp có khóa kéo nổi bật ở bên hông.</p>', 'product_6a95124e898549.89574751.jpg', NULL, 1, '2026-08-31 05:17:39', 5, 'S,M,L,XL', 'trắng', 'kaki thô mỏng'),
(16, 5, 'Áo Khoác Len Cardigan Nữ', 'áo', 650.00, 900.00, '<p>Áo khoác len cardigan dệt kim màu vàng kem (kem bơ) mang phong cách Vintage nhẹ nhàng, ấm áp và thanh lịch. Áo được may từ chất liệu sợi len dày dặn, mềm mại với kiểu dệt gân trơn tỉ mỉ, giúp giữ ấm tốt và đứng phom. Thiết kế cổ tròn bo nhẹ, vai raglan tạo cảm giác thoải mái khi di chuyển. Mặt trước nổi bật với hàng cúc gỗ tròn phong cách cổ điển, vạt áo và gấu tay may bo gân hạt gạo chắc chắn</p>', 'product_6a9512f5c16330.23297903.jpg', NULL, 1, '2026-08-31 05:36:30', 52, 'S,M,L,XL,XXL', 'kem bơ', 'len dệt kim cao cấp'),
(17, 5, 'Áo Khoác Len Cardigan Nữ Cổ Bẻ', 'áo', 650.00, 900.00, '<p>Áo khoác len dệt kim cổ bẻ màu nâu sô-cô-la mang phong cách Preppy cổ điển, hiện đại và vô cùng ấm áp. Áo sử dụng chất liệu len gân nổi (ribbed knit) dày dặn, mềm mại và giữ phom chuẩn. Thiết kế nổi bật với phần cổ bẻ thanh lịch được nhấn nhá viền chỉ màu xanh navy tương phản ở mép cổ và gấu tay áo, tạo điểm nhấn thu hút đầy tinh tế. Mặt trước trang bị hàng cúc cài màu tối đồng điệu cùng tay áo raglan bo nhẹ mang lại sự thoải mái khi vận động.</p>', 'product_6a95136e5495a3.03982113.jpg', NULL, 1, '2026-08-31 05:36:34', 33, 'S,M,L,XL,XXL', 'Nâu Socola', 'len dệt cao cấp'),
(18, 5, 'Cardigan Nữ Dệt Kim', 'áo', 600.00, 950.00, '<p>Áo khoác len cardigan cổ V màu đỏ đô (burgundy) mang phong cách Preppy/Academic cổ điển, sang trọng và thanh lịch. Áo được dệt từ chất liệu sợi len vặn thừng (cable-knit) kết hợp gân dọc tinh tế, tạo bề mặt nổi 3D nổi bật và khả năng giữ ấm vượt trội. Thiết kế cổ chữ V sâu hoàn hảo để phối layer cùng áo sơ mi kẻ gân bên trong, đi kèm hàng cúc tròn may đồng màu tinh tế</p>', 'product_6a95140471c7f4.74734751.jpg', NULL, 1, '2026-08-31 05:41:09', 25, 'S,M,L,XL', 'Đỏ', 'len dệt cao cấp'),
(19, 5, 'Cardigan Nữ Cổ V Dệt Kim', 'áo', 600.00, 950.00, '<p>Áo khoác len cardigan cổ V màu xám ghi mang phong cách Minimalist thanh lịch, hiện đại và vô cùng linh hoạt. Áo được dệt từ chất liệu len mỏng nhẹ, mềm mịn, bề mặt đơm sợi êm ái mang lại cảm giác thoải mái tối đa khi mặc. Thiết kế cổ chữ V xẻ sâu tinh tế, lý tưởng để phối layer cùng áo sơ mi kẻ sọc bên trong, đi kèm hàng cúc cài nhỏ đồng màu sang trọng.</p>', 'product_6a9514531e6a86.27578002.jpg', NULL, 1, '2026-08-31 05:41:12', 25, '', 'xám', 'len dệt kim trơn mịn'),
(22, 4, 'Quần Dù Ống Suông', 'quần', 325.00, 600.00, '<p>Quần ống rộng phối lớp vạt váy (skirt over pants) màu xám ghi đậm chất Y2K và streetwear hiện đại. Sản phẩm được may từ chất liệu vải dù cao cấp, mỏng nhẹ, giữ phom rủ tự nhiên và mang lại cảm giác thoải mái khi vận động. Thiết kế độc đáo với cạp lưng thun co giãn đi kèm dây rút tùy chỉnh, phối lớp vạt váy ngắn màu đen tương phản cắt vát bất đối xứng nổi bật với dòng chữ nghệ thuật thêu/in tinh tế</p>', 'product_6a99b086a07de1.34389903.jpg', NULL, 1, '2026-09-02 18:42:56', 4, 'S,M,L,XL,XXL', 'xám', 'vải dù'),
(23, 4, 'Quần Tây Ống Rộng', 'quần', 325.00, 600.00, '<p>Quần tây ống rộng dáng phồng (balloon/barrel pants) màu đen tuyền mang phong cách Minimalist hiện đại, tối giản nhưng vô cùng cá tính. Sản phẩm được chế tác từ chất liệu vải tuyết mưa/dạ mỏng cao cấp, đứng phom, chống nhăn và tạo độ rủ phồng tự nhiên độc đáo. Thiết kế cạp cao tôn dáng vượt trội với phần cạp chỉn chu tích hợp đỉa thắt lưng</p>', 'product_6a99b1736d1c74.02476069.jpg', NULL, 1, '2026-09-02 18:49:19', 14, 'S,M,L,XXL', 'đen', 'Kaki thun cao cấp');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
