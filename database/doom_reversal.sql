-- DOOM REVERSAL: MySQL 5.7+/8.x atau MariaDB 10.4+ lokal.
-- Import lewat phpMyAdmin. Tidak menghapus tabel/data yang sudah ada.
CREATE DATABASE IF NOT EXISTS doom_reversal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doom_reversal;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(161) NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 category VARCHAR(30) NOT NULL,
 color VARCHAR(50) NOT NULL,
 price INT UNSIGNED NOT NULL,
 image VARCHAR(160) NOT NULL,
 available TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(32) NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 recipient_name VARCHAR(100) NOT NULL,
 phone VARCHAR(20) NOT NULL,
 address TEXT NOT NULL,
 subtotal INT UNSIGNED NOT NULL,
 discount INT UNSIGNED NOT NULL DEFAULT 0,
 total INT UNSIGNED NOT NULL,
 status VARCHAR(100) NOT NULL DEFAULT 'Simulasi - tidak ada pembayaran',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_orders_code (code),
 KEY idx_orders_user (user_id),
 CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NULL,
 product_name VARCHAR(160) NOT NULL,
 color VARCHAR(50) NOT NULL,
 image VARCHAR(160) NOT NULL,
 size VARCHAR(4) NOT NULL,
 quantity SMALLINT UNSIGNED NOT NULL,
 unit_price INT UNSIGNED NOT NULL,
 subtotal INT UNSIGNED NOT NULL,
 KEY idx_items_order (order_id),
 CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Produk awal. Import ulang mempertahankan produk yang telah diedit.
INSERT INTO products (id,name,category,color,price,image,available) VALUES
(1, 'Heavyweight Hoodie', 'Men', 'Lilac', 300000, 'men-1.jpg', 1),
(2, 'Boxy Hoodie', 'Men', 'Stone', 300000, 'men-2.jpg', 1),
(3, 'Oversized Hoodie', 'Men', 'Burgundy', 300000, 'men-3.jpg', 1),
(4, 'Signature Hoodie', 'Men', 'Black', 300000, 'men-4.jpg', 1),
(5, 'Signature Hoodie', 'Men', 'Navy', 300000, 'men-5.jpg', 1),
(6, 'Air Essential Hoodie', 'Men', 'Olive', 300000, 'men-6.jpg', 1),
(7, 'Signature Hoodie', 'Women', 'Forest', 300000, 'women-1.jpg', 1),
(8, 'Signature Hoodie', 'Women', 'Sand', 300000, 'women-2.jpg', 1),
(9, 'Signature Hoodie', 'Women', 'Burgundy', 300000, 'women-3.jpg', 1),
(10, 'Signature Hoodie', 'Women', 'Lilac', 300000, 'women-4.jpg', 1),
(11, 'Signature Hoodie', 'Women', 'Black', 300000, 'women-5.jpg', 1),
(12, 'Signature Hoodie', 'Women', 'Pink', 300000, 'women-6.jpg', 1),
(13, 'Relic Everyday Chino Pants', 'Pants', 'Black', 300000, 'pants-detail.jpg', 1)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Satu transaksi pembayaran demo untuk setiap pesanan.
CREATE TABLE IF NOT EXISTS payments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 method VARCHAR(30) NULL,
 amount INT UNSIGNED NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 reference VARCHAR(40) NULL,
 paid_at TIMESTAMP NULL DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_payment_order (order_id),
 UNIQUE KEY uq_payment_reference (reference),
 CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Menambahkan row pending untuk pesanan versi SQL sebelumnya bila ada.
INSERT INTO payments (order_id, amount, status)
SELECT o.id, o.total, 'pending' FROM orders o
LEFT JOIN payments p ON p.order_id = o.id WHERE p.id IS NULL;
