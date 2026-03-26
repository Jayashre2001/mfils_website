-- ============================================================
-- MLM SAREE - ADMIN PANEL SQL ADDITIONS
-- Run this AFTER importing main schema.sql in phpMyAdmin
-- ============================================================

USE mlm_saree;

-- Withdrawals table (if not already created by wallet.php)
CREATE TABLE IF NOT EXISTS `withdrawals` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `amount`     DECIMAL(12,2) NOT NULL,
    `method`     VARCHAR(10) NOT NULL DEFAULT 'bank',
    `detail`     JSON,
    `status`     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_wd_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Verify commission_rates table has all 7 levels
INSERT IGNORE INTO commission_rates (level, rate, label) VALUES
(1, 10.00, 'Level 1 – Direct'),
(2,  5.00, 'Level 2'),
(3,  3.00, 'Level 3'),
(4,  2.00, 'Level 4'),
(5,  1.00, 'Level 5'),
(6,  0.50, 'Level 6'),
(7,  0.50, 'Level 7');
