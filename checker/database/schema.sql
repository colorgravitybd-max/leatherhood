-- ELHOE Verify - schema.sql
-- MySQL 5.7+ / MariaDB 10.4+
-- All tables InnoDB + utf8mb4 for emoji + Bengali support.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------
-- products: master catalogue (mirrored from WordPress storefront).
-- ----------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(255) NOT NULL,
  `title_bn`       VARCHAR(255) NULL DEFAULT NULL,
  `description`    TEXT NULL,
  `description_bn` TEXT NULL,
  `ingredients`    TEXT NULL,
  `how_to_use`     TEXT NULL,
  `how_to_use_bn`  TEXT NULL,
  `image_url`      VARCHAR(255) NULL,
  `wordpress_url`  VARCHAR(255) NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- verification_codes: every printed/scratch-off authenticity code.
-- `code` is uniquely indexed for O(log n) lookups across millions of rows.
-- ----------------------------------------------------------------
DROP TABLE IF EXISTS `verification_codes`;
CREATE TABLE `verification_codes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    INT UNSIGNED NOT NULL,
  `code`          VARCHAR(100) NOT NULL,
  `code_type`     ENUM('universal','unique') NOT NULL DEFAULT 'unique',
  `batch_number`  VARCHAR(50) NULL,
  `expiry_date`   DATE NULL,
  `scan_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `first_scan_at` TIMESTAMP NULL DEFAULT NULL,
  `last_scan_at`  TIMESTAMP NULL DEFAULT NULL,
  `is_disabled`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_codes_code` (`code`),
  KEY `idx_codes_product`    (`product_id`),
  KEY `idx_codes_batch`      (`batch_number`),
  KEY `idx_codes_expiry`     (`expiry_date`),
  KEY `idx_codes_type`       (`code_type`),
  CONSTRAINT `fk_codes_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- scan_logs: every verification attempt (valid AND invalid).
-- Indexed by ip + scanned_at for the rate limiter; by code_searched for radar.
-- ----------------------------------------------------------------
DROP TABLE IF EXISTS `scan_logs`;
CREATE TABLE `scan_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code_searched` VARCHAR(100) NOT NULL,
  `is_valid`      TINYINT(1) NOT NULL DEFAULT 0,
  `product_id`    INT UNSIGNED NULL,
  `ip_address`    VARCHAR(45) NULL,
  `country`       VARCHAR(100) NULL,
  `region`        VARCHAR(100) NULL,
  `district`      VARCHAR(100) NULL,
  `user_agent`    VARCHAR(255) NULL,
  `scanned_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scans_code`       (`code_searched`),
  KEY `idx_scans_valid_time` (`is_valid`, `scanned_at`),
  KEY `idx_scans_ip_time`    (`ip_address`, `scanned_at`),
  KEY `idx_scans_country`    (`country`),
  KEY `idx_scans_district`   (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- admin_users: dashboard login.
-- ----------------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('superadmin','manager') NOT NULL DEFAULT 'manager',
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
