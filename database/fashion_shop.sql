-- =========================================
-- DATABASE - GIỎ HÀNG & ĐẶT HÀNG
-- FashionShop - PHP + MySQL
-- =========================================

CREATE DATABASE IF NOT EXISTS fashion_shop
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fashion_shop;


-- =========================================
-- BẢNG ĐƠN HÀNG
-- =========================================

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    note TEXT,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- BẢNG CHI TIẾT ĐƠN HÀNG
-- =========================================

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
);


-- =========================================
-- INDEX
-- =========================================

CREATE INDEX idx_orders_created_at
ON orders(created_at);

CREATE INDEX idx_orders_status
ON orders(status);

CREATE INDEX idx_order_items_order_id
ON order_items(order_id);