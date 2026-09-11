START TRANSACTION;

SET @category_ao   = (SELECT id FROM categories WHERE slug = 'ao' LIMIT 1);
SET @category_quan = (SELECT id FROM categories WHERE slug = 'quan' LIMIT 1);
SET @category_vay  = (SELECT id FROM categories WHERE slug = 'vay' LIMIT 1);

-- Ẩn/xóa các sản phẩm demo cũ không còn sử dụng
UPDATE products
SET status = 0
WHERE slug IN (
    'ao-so-mi-nam-oxford',
    'quan-jean-nam-indigo',
    'cardigan-nu-sage',
    'chan-vay-midi-nu',
    'ao-kieu-nu-co-drape',
    'quan-suong-nu-tailored',
    'quan-ong-rong-nu-modern-flow'
);

-- 6 sản phẩm chính sử dụng ảnh thật
INSERT INTO products
(category_id, name, slug, description, price, stock, image, status)
VALUES
(
    @category_ao,
    'Áo thun nam Essential',
    'ao-thun-nam-essential',
    'Áo thun nam thiết kế tối giản, dễ phối đồ và phù hợp sử dụng hằng ngày.',
    299000,
    30,
    'assets/images/ao-thun.jpg',
    1
),
(
    @category_ao,
    'Áo khoác denim nam',
    'ao-khoac-denim-nam-heritage',
    'Áo khoác denim nam phong cách trẻ trung, dễ kết hợp với nhiều trang phục.',
    699000,
    20,
    'assets/images/ao-khoac-denim.jpg',
    1
),
(
    @category_quan,
    'Quần kaki nam',
    'quan-kaki-nam-dang-suong',
    'Quần kaki nam dáng gọn, phù hợp phong cách casual và smart casual.',
    499000,
    25,
    'assets/images/quan-kaki-nam.jpg',
    1
),
(
    @category_ao,
    'Áo sơ mi nữ',
    'ao-so-mi-nu-lua-mem',
    'Áo sơ mi nữ kiểu dáng thanh lịch, phù hợp đi học, đi làm và đi chơi.',
    459000,
    25,
    'assets/images/ao-so-mi-nu - Copy.jpg',
    1
),
(
    @category_quan,
    'Quần jean nữ',
    'quan-jean-nu-ong-rong',
    'Quần jean nữ phong cách hiện đại, dễ phối cùng áo thun hoặc sơ mi.',
    549000,
    25,
    'assets/images/quan-jean-nu.jpg',
    1
),
(
    @category_vay,
    'Váy nữ thanh lịch',
    'vay-nu-thanh-lich',
    'Váy nữ thiết kế thanh lịch, phù hợp đi làm, đi chơi và những dịp đặc biệt.',
    599000,
    20,
    'assets/images/vay-nu-thanh-lich.jpg',
    1
)
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    price = VALUES(price),
    stock = VALUES(stock),
    image = VALUES(image),
    status = VALUES(status);

COMMIT;