-- ============================================================
-- CuanLog POS - Database Schema & Seed Data
-- Database: db_lomba
-- Sub-tema: Accelerating Digital Transformation for Business & SMEs
-- ============================================================

-- Nonaktifkan foreign key checks agar DROP TABLE bisa berjalan
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `db_lomba`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_lomba`;

-- ============================================================
-- DROP TABLES (urutan: child → parent)
-- ============================================================
DROP TABLE IF EXISTS `transaction_items`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `debts`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- CREATE TABLES
-- ============================================================

-- 1. Users
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `avatar`     VARCHAR(255) DEFAULT NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories
CREATE TABLE `categories` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `icon`        VARCHAR(50)  DEFAULT 'box',
  `description` TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products
CREATE TABLE `products` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `category_id`   INT             DEFAULT NULL,
  `barcode`       VARCHAR(50)     UNIQUE DEFAULT NULL,
  `name`          VARCHAR(150)    NOT NULL,
  `description`   TEXT            DEFAULT NULL,
  `cost_price`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `stock`         INT             NOT NULL DEFAULT 0,
  `min_stock`     INT             NOT NULL DEFAULT 5,
  `unit`          VARCHAR(20)     NOT NULL DEFAULT 'pcs',
  `image`         VARCHAR(255)    DEFAULT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  INDEX (`barcode`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Customers
CREATE TABLE `customers` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)  NOT NULL,
  `phone`       VARCHAR(20)   DEFAULT NULL,
  `email`       VARCHAR(100)  DEFAULT NULL,
  `address`     TEXT          DEFAULT NULL,
  `points`      INT           NOT NULL DEFAULT 0,
  `total_spent` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Transactions
CREATE TABLE `transactions` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no`     VARCHAR(50)   NOT NULL UNIQUE,
  `user_id`        INT           NOT NULL,
  `customer_id`    INT           DEFAULT NULL,
  `subtotal`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `tax`            DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `grand_total`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_cost`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','qris','transfer','debt') NOT NULL DEFAULT 'cash',
  `amount_paid`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `change_due`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status`         ENUM('completed','pending','cancelled') NOT NULL DEFAULT 'completed',
  `notes`          TEXT          DEFAULT NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`customer_id`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Transaction Items
CREATE TABLE `transaction_items` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT           NOT NULL,
  `product_id`     INT           NOT NULL,
  `product_name`   VARCHAR(150)  NOT NULL,
  `cost_price`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty`            INT           NOT NULL DEFAULT 1,
  `subtotal`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  INDEX (`transaction_id`),
  INDEX (`product_id`),
  CONSTRAINT `fk_items_transaction`
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Debts / Kasbon
CREATE TABLE `debts` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT           NOT NULL,
  `amount`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status`      ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `due_date`    DATE          DEFAULT NULL,
  `notes`       TEXT          DEFAULT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`customer_id`),
  CONSTRAINT `fk_debts_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Settings
CREATE TABLE `settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Activity Logs
CREATE TABLE `activity_logs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT           DEFAULT NULL,
  `action`     VARCHAR(100)  NOT NULL,
  `details`    TEXT          DEFAULT NULL,
  `ip_address` VARCHAR(45)   DEFAULT NULL,
  `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aktifkan kembali foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA (Data Demo UMKM)
-- ============================================================

-- Users (password: admin123 / kasir123)
INSERT INTO `users` (`id`,`name`,`username`,`password`,`role`,`phone`,`is_active`) VALUES
(1,'Budi Santoso (Owner)','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','081234567890',1),
(2,'Siti Nurhaliza (Kasir)','kasir','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kasir','089876543210',1);

-- Categories
INSERT INTO `categories` (`id`,`name`,`icon`,`description`) VALUES
(1,'Makanan Instan','utensils','Mie, bubur instan, sarden, makanan cepat saji'),
(2,'Minuman Segar','mug-hot','Kopi, teh, jus, susu, minuman kemasan'),
(3,'Sembako & Pokok','shopping-basket','Beras, minyak goreng, gula, tepung'),
(4,'Snack & Camilan','cookie','Keripik, biskuit, kacang, cokelat'),
(5,'Bumbu Dapur','mortar-pestle','Garam, kecap, saus, penyedap rasa'),
(6,'Perawatan Rumah','soap','Sabun, sampo, deterjen, pasta gigi');

-- Products
INSERT INTO `products` (`id`,`category_id`,`barcode`,`name`,`description`,`cost_price`,`selling_price`,`stock`,`min_stock`,`unit`,`is_active`) VALUES
(1, 1,'899999901001','Indomie Goreng Spesial 85g',  'Mie instan goreng favorit Indonesia', 2600.00,  3500.00,120,20,'pcs',1),
(2, 1,'899999901002','Indomie Kuah Ayam Bawang 69g','Mie rebus rasa ayam bawang hangat',  2500.00,  3500.00, 85,15,'pcs',1),
(3, 1,'899999901003','Pop Mie Kuah Rasa Baso 75g',  'Mie instan cup praktis',              4200.00,  5500.00,  4,10,'cup',1),
(4, 2,'899999902001','Kopi Kapal Api Special Mix',  'Kopi bubuk plus gula mantap',         12500.00,16000.00, 45,10,'pack',1),
(5, 2,'899999902002','Teh Pucuk Harum 350ml',       'Teh melati segar botol',               3000.00,  4500.00, 60,12,'btl',1),
(6, 2,'899999902003','Ultra Milk Cokelat 250ml',    'Susu UHT lezat kaya kalsium',         5500.00,  7500.00,  3, 8,'kotak',1),
(7, 3,'899999903001','Beras Ramos Super 5 Kg',      'Beras pulen kualitas premium',        65000.00, 76000.00, 25, 5,'sak',1),
(8, 3,'899999903002','Minyak Goreng Bimoli 2L',     'Minyak goreng kelapa sawit',          33000.00, 39500.00, 18, 5,'pouch',1),
(9, 3,'899999903003','Gulaku Tebu Kuning 1 Kg',     'Gula pasir kristal murni',            15000.00, 18500.00, 30, 8,'pack',1),
(10,4,'899999904001','Chitato Sapi Panggang 68g',   'Keripik kentang bergelombang',         9000.00, 12000.00, 40,10,'bks',1),
(11,4,'899999904002','Oreo Vanilla Cream 133g',     'Biskuit cokelat krim vanila',          8500.00, 11500.00,  2, 6,'roll',1),
(12,5,'899999905001','Kecap Manis Bango 520ml',     'Kecap kedelai pilihan',               22000.00, 27500.00, 20, 5,'pouch',1),
(13,6,'899999906001','Sunlight Jeruk Nipis 700ml',  'Cairan pencuci piring anti lemak',    14000.00, 17500.00, 35,10,'pouch',1);

-- Customers
INSERT INTO `customers` (`id`,`name`,`phone`,`email`,`address`,`points`,`total_spent`) VALUES
(1,'Pelanggan Umum (Walk-in)','-','-','-',0,0.00),
(2,'Dewi Kartika',  '081298765432','dewi.kartika@gmail.com','Jl. Anggrek No. 12, RT 03/05',  145, 725000.00),
(3,'Ahmad Fauzi',   '085712345678','ahmad.fauzi@yahoo.com', 'Komplek Permata Hijau B-4',       85, 425000.00),
(4,'Rina Wahyuni',  '087823456789','rina.w@outlook.com',    'Jl. Melati Indah No. 45',        210,1050000.00);

-- Settings
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('store_name',          'CuanLog Mart UMKM'),
('store_tagline',       'Solusi Kasir Pintar & Pencatatan Cuan UMKM'),
('store_address',       'Jl. Pemuda No. 88, Kawasan Niaga Digital, Jakarta Pusat'),
('store_phone',         '0812-3456-7890'),
('store_email',         'halo@cuanlog.id'),
('store_website',       '@cuanlogmart'),
('store_tax_rate',      '0'),
('currency_symbol',     'Rp'),
('low_stock_alert',     '5'),
('receipt_footer',      'Terima kasih telah berbelanja!\nKunjungi kami lagi ya 😊\nBersama CuanLog, UMKM Indonesia Naik Kelas!'),
('qris_merchant_name',  'CUANLOG MART INDONESIA'),
('qris_nmid',           'ID1020023847592');

-- Sample Transactions
INSERT INTO `transactions` (`id`,`invoice_no`,`user_id`,`customer_id`,`subtotal`,`discount`,`tax`,`grand_total`,`total_cost`,`payment_method`,`amount_paid`,`change_due`,`status`,`notes`,`created_at`) VALUES
(1,'TR-20261107-0001',1,2, 83500.00,  0.00,0.00, 83500.00,68000.00,'qris',    83500.00,    0.00,'completed','Belanja bulanan',       '2026-11-07 10:15:00'),
(2,'TR-20261107-0002',2,1, 38000.00,  0.00,0.00, 38000.00,29000.00,'cash',    50000.00,12000.00,'completed','Pembelian langsung',    '2026-11-07 14:22:00'),
(3,'TR-20261108-0001',2,3,114000.00,4000.00,0.00,110000.00,92000.00,'cash',  150000.00,40000.00,'completed','Diskon pelanggan setia','2026-11-08 09:40:00'),
(4,'TR-20261108-0002',1,4,155500.00,5500.00,0.00,150000.00,124000.00,'transfer',150000.00,0.00,'completed','Transfer BCA',          '2026-11-08 16:05:00'),
(5,'TR-20261109-0001',2,1, 45000.00,  0.00,0.00, 45000.00,34500.00,'cash',    50000.00, 5000.00,'completed','Pelanggan pagi',        '2026-11-09 08:30:00'),
(6,'TR-20261109-0002',1,2, 94000.00,  0.00,0.00, 94000.00,77500.00,'qris',    94000.00,    0.00,'completed','QRIS Dinamis',          '2026-11-09 11:15:00'),
(7,'TR-20261110-0001',2,3, 56000.00,  0.00,0.00, 56000.00,45000.00,'cash',   100000.00,44000.00,'completed','Belanja pagi',          '2026-11-10 08:00:00'),
(8,'TR-20261110-0002',1,4, 76000.00,  0.00,0.00, 76000.00,65000.00,'qris',    76000.00,    0.00,'completed','QRIS scan',             '2026-11-10 10:30:00');

-- Transaction Items
INSERT INTO `transaction_items` (`transaction_id`,`product_id`,`product_name`,`cost_price`,`price`,`qty`,`subtotal`) VALUES
(1, 1,'Indomie Goreng Spesial 85g',   2600.00, 3500.00,5,17500.00),
(1, 8,'Minyak Goreng Bimoli 2L',     33000.00,39500.00,1,39500.00),
(1,12,'Kecap Manis Bango 520ml',     22000.00,27500.00,1,27500.00),
(2, 5,'Teh Pucuk Harum 350ml',        3000.00, 4500.00,4,18000.00),
(2,10,'Chitato Sapi Panggang 68g',    9000.00,12000.00,1,12000.00),
(2, 2,'Indomie Kuah Ayam Bawang 69g', 2500.00, 3500.00,2, 7000.00),
(3, 7,'Beras Ramos Super 5 Kg',      65000.00,76000.00,1,76000.00),
(3, 9,'Gulaku Tebu Kuning 1 Kg',     15000.00,18500.00,2,37000.00),
(4, 7,'Beras Ramos Super 5 Kg',      65000.00,76000.00,1,76000.00),
(4, 8,'Minyak Goreng Bimoli 2L',     33000.00,39500.00,1,39500.00),
(4, 4,'Kopi Kapal Api Special Mix',  12500.00,16000.00,2,32000.00),
(4, 1,'Indomie Goreng Spesial 85g',   2600.00, 3500.00,2, 7000.00),
(5, 5,'Teh Pucuk Harum 350ml',        3000.00, 4500.00,4,18000.00),
(5, 4,'Kopi Kapal Api Special Mix',  12500.00,16000.00,1,16000.00),
(5,11,'Oreo Vanilla Cream 133g',      8500.00,11500.00,1,11500.00),
(6, 8,'Minyak Goreng Bimoli 2L',     33000.00,39500.00,1,39500.00),
(6,13,'Sunlight Jeruk Nipis 700ml',  14000.00,17500.00,2,35000.00),
(6, 9,'Gulaku Tebu Kuning 1 Kg',     15000.00,18500.00,1,18500.00),
(7, 1,'Indomie Goreng Spesial 85g',   2600.00, 3500.00,8,28000.00),
(7, 5,'Teh Pucuk Harum 350ml',        3000.00, 4500.00,4,18000.00),
(7,10,'Chitato Sapi Panggang 68g',    9000.00,12000.00,1,12000.00),
(8, 7,'Beras Ramos Super 5 Kg',      65000.00,76000.00,1,76000.00);

-- Activity Logs (Initial)
INSERT INTO `activity_logs` (`user_id`,`action`,`details`,`ip_address`) VALUES
(1,'System Init',   'CuanLog database seeded successfully for UMKM POS demo','127.0.0.1'),
(1,'Login',         'Admin pertama kali masuk ke sistem','127.0.0.1'),
(2,'Login',         'Kasir pertama kali masuk ke sistem','127.0.0.1');
