-- ============================================================
--  MLM Saree Business - Database Schema
--  Supports 7-level referral commission distribution
-- ============================================================

CREATE DATABASE IF NOT EXISTS mlm_saree CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mlm_saree;

-- -----------------------------------------------------------
-- Users table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    referrer_id   INT UNSIGNED NULL DEFAULT NULL,
    referral_code VARCHAR(12)  NOT NULL UNIQUE,
    wallet        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Products table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    image_url   VARCHAR(255),
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Orders / Purchases table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    amount      DECIMAL(12,2) NOT NULL,
    status      ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user    FOREIGN KEY (user_id)    REFERENCES users(id),
    CONSTRAINT fk_order_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Commission transactions table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS commissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    buyer_id        INT UNSIGNED NOT NULL,
    beneficiary_id  INT UNSIGNED NOT NULL,
    level           TINYINT UNSIGNED NOT NULL COMMENT '1=direct upline … 7=7th upline',
    rate            DECIMAL(5,2) NOT NULL COMMENT 'percentage rate used',
    commission_amt  DECIMAL(12,2) NOT NULL,
    status          ENUM('pending','credited') NOT NULL DEFAULT 'credited',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comm_order       FOREIGN KEY (order_id)       REFERENCES orders(id),
    CONSTRAINT fk_comm_buyer       FOREIGN KEY (buyer_id)       REFERENCES users(id),
    CONSTRAINT fk_comm_beneficiary FOREIGN KEY (beneficiary_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Commission rates table  (editable from DB)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS commission_rates (
    level      TINYINT UNSIGNED PRIMARY KEY,
    rate       DECIMAL(5,2) NOT NULL,
    label      VARCHAR(30)  NOT NULL
) ENGINE=InnoDB;

INSERT INTO commission_rates (level, rate, label) VALUES
(1, 10.00, 'Level 1 – Direct'),
(2,  5.00, 'Level 2'),
(3,  3.00, 'Level 3'),
(4,  2.00, 'Level 4'),
(5,  1.00, 'Level 5'),
(6,  0.50, 'Level 6'),
(7,  0.50, 'Level 7');

-- -----------------------------------------------------------
-- Sample products
-- -----------------------------------------------------------
INSERT INTO products (name, description, price, image_url) VALUES
('Kanjivaram Silk Saree',   'Pure Kanjivaram silk with gold zari border',          4500.00, 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=400'),
('Banarasi Silk Saree',     'Hand-woven Banarasi silk with intricate motifs',      3800.00, 'https://images.unsplash.com/photo-1583391733981-8498408ee4b6?w=400'),
('Sambalpuri Cotton Saree', 'Traditional Odisha ikat weave in vibrant colours',    1200.00, 'https://images.unsplash.com/photo-1617627143233-30b85eb4e895?w=400'),
('Chanderi Silk Saree',     'Lightweight Chanderi with delicate floral patterns',  2200.00, 'https://images.unsplash.com/photo-1614948523892-e67a0f03d9f5?w=400'),
('Mysore Silk Saree',       'Premium Mysore silk with contrast pallu',             3200.00, 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=400');

-- -----------------------------------------------------------
-- Demo users (passwords are bcrypt of "Password@123")
-- -----------------------------------------------------------
INSERT INTO users (username, email, password, referrer_id, referral_code, wallet) VALUES
('jayashree', 'jayashree@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL,  'JAY001', 500.00),
('sritam',    'sritam@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1,    'SRI002', 200.00),
('rashmita',  'rashmita@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2,    'RAS003', 100.00),
('user3',     'user3@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3,    'USR004', 0.00),
('user4',     'user4@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4,    'USR005', 0.00),
('user5',     'user5@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5,    'USR006', 0.00),
('user6',     'user6@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6,    'USR007', 0.00),
('user7',     'user7@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7,    'USR008', 0.00);
