-- =============================================================
-- LeatherHood — Master Database Schema
-- MySQL 8 / MariaDB 10.4+ (utf8mb4)
-- Designed for Hostinger Shared Hosting (LiteSpeed)
-- =============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. RBAC ADMIN USERS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(120) NOT NULL,
  `email`        VARCHAR(190) NOT NULL,
  `phone`        VARCHAR(20)  DEFAULT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('super_admin','admin','moderator','support') NOT NULL DEFAULT 'moderator',
  `permissions`  JSON DEFAULT NULL, -- granular overrides
  `avatar`       VARCHAR(255) DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. CRM CUSTOMERS (front-end shoppers, separate from admin users)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`     VARCHAR(80) NOT NULL,
  `last_name`      VARCHAR(80) DEFAULT NULL,
  `email`          VARCHAR(190) DEFAULT NULL,
  `phone`          VARCHAR(20) NOT NULL,
  `password`       VARCHAR(255) DEFAULT NULL, -- optional (guest checkout default)
  `default_division_id`     INT UNSIGNED DEFAULT NULL,
  `default_district_id`     INT UNSIGNED DEFAULT NULL,
  `default_police_station_id` INT UNSIGNED DEFAULT NULL,
  `address_line`   VARCHAR(255) DEFAULT NULL,
  `total_orders`   INT UNSIGNED NOT NULL DEFAULT 0,
  `total_spent`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `last_order_at`  DATETIME DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `tags`           VARCHAR(255) DEFAULT NULL,   -- "vip,wholesale"
  `is_blacklisted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_phone` (`phone`),
  KEY `idx_customers_email` (`email`),
  KEY `idx_customers_total_spent` (`total_spent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. GEOGRAPHY: BANGLADESH SHIPPING ENGINE
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `geo_divisions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80) NOT NULL,
  `name_bn`    VARCHAR(120) DEFAULT NULL,
  `slug`       VARCHAR(80) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_div_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `geo_districts` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `division_id`      INT UNSIGNED NOT NULL,
  `name`             VARCHAR(80) NOT NULL,
  `name_bn`          VARCHAR(120) DEFAULT NULL,
  `slug`             VARCHAR(80) NOT NULL,
  `base_shipping_fee` DECIMAL(8,2) NOT NULL DEFAULT 130.00,
  `is_deliverable`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dist_div` (`division_id`),
  UNIQUE KEY `uq_dist_slug` (`slug`),
  CONSTRAINT `fk_dist_div` FOREIGN KEY (`division_id`) REFERENCES `geo_divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `geo_police_stations` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `district_id`        INT UNSIGNED NOT NULL,
  `name`               VARCHAR(120) NOT NULL,
  `name_bn`            VARCHAR(160) DEFAULT NULL,
  `slug`               VARCHAR(140) NOT NULL,
  `extra_shipping_fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `is_deliverable`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`         INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ps_dist` (`district_id`),
  UNIQUE KEY `uq_ps_dist_slug` (`district_id`,`slug`),
  CONSTRAINT `fk_ps_dist` FOREIGN KEY (`district_id`) REFERENCES `geo_districts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. CATALOG: BRANDS, CATEGORIES, TAGS, ATTRIBUTES, PRODUCTS, VARIATIONS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brands` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `slug`       VARCHAR(140) NOT NULL,
  `logo`       VARCHAR(255) DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brand_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `meta_title`  VARCHAR(190) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`),
  KEY `idx_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tags` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attributes` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,                  -- "Color", "Size"
  `slug` VARCHAR(100) NOT NULL,
  `swatch` ENUM('text','color','image') NOT NULL DEFAULT 'text',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attr_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attribute_values` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attribute_id` INT UNSIGNED NOT NULL,
  `value`        VARCHAR(120) NOT NULL,         -- "Black", "M"
  `meta`         VARCHAR(120) DEFAULT NULL,     -- hex code or image url
  `sort_order`   INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_av_attr` (`attribute_id`),
  CONSTRAINT `fk_av_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku`              VARCHAR(60) NOT NULL,
  `name`             VARCHAR(190) NOT NULL,
  `slug`             VARCHAR(220) NOT NULL,
  `brand_id`         INT UNSIGNED DEFAULT NULL,
  `primary_category_id` INT UNSIGNED DEFAULT NULL,
  `template_type`    ENUM('default','minimal','storyteller','landing','luxury','split') NOT NULL DEFAULT 'default',
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description`      LONGTEXT DEFAULT NULL,
  `specs`            JSON DEFAULT NULL,        -- key/value pairs
  `price`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `compare_at_price` DECIMAL(12,2) DEFAULT NULL,
  `cost_price`       DECIMAL(12,2) DEFAULT NULL,
  `tax_class`        VARCHAR(40) DEFAULT 'standard',
  `has_variations`   TINYINT(1) NOT NULL DEFAULT 0,
  `track_inventory`  TINYINT(1) NOT NULL DEFAULT 1,
  `stock_qty`        INT NOT NULL DEFAULT 0,            -- only when no variations
  `low_stock_threshold` INT NOT NULL DEFAULT 5,
  `weight_g`         INT DEFAULT NULL,
  `length_cm`        DECIMAL(6,2) DEFAULT NULL,
  `width_cm`         DECIMAL(6,2) DEFAULT NULL,
  `height_cm`        DECIMAL(6,2) DEFAULT NULL,
  `featured_image`   VARCHAR(255) DEFAULT NULL,
  `gallery`          JSON DEFAULT NULL,
  `video_url`        VARCHAR(255) DEFAULT NULL,
  `status`           ENUM('draft','active','archived','out_of_stock') NOT NULL DEFAULT 'draft',
  `is_featured`      TINYINT(1) NOT NULL DEFAULT 0,
  `is_popular`       TINYINT(1) NOT NULL DEFAULT 0,
  `is_new`           TINYINT(1) NOT NULL DEFAULT 0,
  `meta_title`       VARCHAR(190) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `meta_keywords`    VARCHAR(255) DEFAULT NULL,
  `views_count`      INT UNSIGNED NOT NULL DEFAULT 0,
  `sales_count`      INT UNSIGNED NOT NULL DEFAULT 0,
  `rating_avg`       DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count`     INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at`     DATETIME DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_cat` (`primary_category_id`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_cat` FOREIGN KEY (`primary_category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_categories` (
  `product_id`  BIGINT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `idx_pc_cat` (`category_id`),
  CONSTRAINT `fk_pc_p` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_c` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_tags` (
  `product_id` BIGINT UNSIGNED NOT NULL,
  `tag_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  CONSTRAINT `fk_pt_p` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_t` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variation/SKU level inventory with batch QA tracking
CREATE TABLE IF NOT EXISTS `product_variations` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`      BIGINT UNSIGNED NOT NULL,
  `sku`             VARCHAR(80) NOT NULL,
  `barcode`         VARCHAR(80) DEFAULT NULL,
  `option_summary`  VARCHAR(190) DEFAULT NULL, -- "Black / M"
  `attribute_map`   JSON DEFAULT NULL,         -- {"color":"Black","size":"M"}
  `price`           DECIMAL(12,2) DEFAULT NULL, -- override
  `compare_at_price` DECIMAL(12,2) DEFAULT NULL,
  `cost_price`      DECIMAL(12,2) DEFAULT NULL,
  `stock_qty`       INT NOT NULL DEFAULT 0,
  `low_stock_threshold` INT NOT NULL DEFAULT 5,
  `weight_g`        INT DEFAULT NULL,
  `image`           VARCHAR(255) DEFAULT NULL,
  `batch_number`    VARCHAR(60) DEFAULT NULL,    -- QA: batch identifier
  `manufacturing_date` DATE DEFAULT NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_var_sku` (`sku`),
  KEY `idx_var_prod` (`product_id`),
  KEY `idx_var_batch` (`batch_number`),
  CONSTRAINT `fk_var_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory ledger for full audit trail (in/out movements)
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `variation_id` BIGINT UNSIGNED DEFAULT NULL,
  `product_id`   BIGINT UNSIGNED DEFAULT NULL,
  `delta`        INT NOT NULL,                       -- +/- quantity
  `reason`       ENUM('purchase','sale','return','adjustment','damage','recall','transfer') NOT NULL,
  `reference_type` VARCHAR(40) DEFAULT NULL,         -- 'order', 'manual', etc
  `reference_id` BIGINT UNSIGNED DEFAULT NULL,
  `batch_number` VARCHAR(60) DEFAULT NULL,
  `note`         VARCHAR(255) DEFAULT NULL,
  `user_id`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_im_var` (`variation_id`),
  KEY `idx_im_prod` (`product_id`),
  KEY `idx_im_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. ORDERS LEDGER
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number`         VARCHAR(30) NOT NULL,
  `customer_id`          BIGINT UNSIGNED DEFAULT NULL,
  `status`               ENUM(
                            'cart','pending_otp','pending','processing',
                            'on_hold','shipped','out_for_delivery','delivered',
                            'completed','cancelled','refunded','returned','abandoned'
                          ) NOT NULL DEFAULT 'pending_otp',
  `payment_status`       ENUM('unpaid','paid','partially_paid','refunded','failed') NOT NULL DEFAULT 'unpaid',
  `payment_method`       ENUM('cod','bkash','nagad','card','bank_transfer') NOT NULL DEFAULT 'cod',
  `payment_reference`    VARCHAR(120) DEFAULT NULL,

  `customer_name`        VARCHAR(160) NOT NULL,
  `customer_phone`       VARCHAR(20) NOT NULL,
  `customer_email`       VARCHAR(190) DEFAULT NULL,

  `division_id`          INT UNSIGNED DEFAULT NULL,
  `district_id`          INT UNSIGNED DEFAULT NULL,
  `police_station_id`    INT UNSIGNED DEFAULT NULL,
  `address_line`         VARCHAR(255) NOT NULL,
  `landmark`             VARCHAR(190) DEFAULT NULL,
  `note`                 TEXT DEFAULT NULL,

  `subtotal`             DECIMAL(14,2) NOT NULL DEFAULT 0,
  `discount_total`       DECIMAL(14,2) NOT NULL DEFAULT 0,
  `coupon_code`          VARCHAR(60) DEFAULT NULL,
  `shipping_total`       DECIMAL(14,2) NOT NULL DEFAULT 0,
  `tax_total`            DECIMAL(14,2) NOT NULL DEFAULT 0,
  `grand_total`          DECIMAL(14,2) NOT NULL DEFAULT 0,
  `currency`             CHAR(3) NOT NULL DEFAULT 'BDT',

  `is_verified`          TINYINT(1) NOT NULL DEFAULT 0,   -- OTP gate
  `verified_at`          DATETIME DEFAULT NULL,
  `verified_by`          BIGINT UNSIGNED DEFAULT NULL,    -- admin override

  `courier`              VARCHAR(40) DEFAULT NULL,        -- steadfast|pathao
  `courier_consignment_id` VARCHAR(120) DEFAULT NULL,
  `courier_tracking_url` VARCHAR(255) DEFAULT NULL,
  `courier_pushed_at`    DATETIME DEFAULT NULL,
  `delivery_status`      VARCHAR(60) DEFAULT NULL,

  `client_ip`            VARCHAR(45) DEFAULT NULL,
  `user_agent`           VARCHAR(255) DEFAULT NULL,
  `referrer`             VARCHAR(255) DEFAULT NULL,
  `utm_source`           VARCHAR(120) DEFAULT NULL,
  `utm_medium`           VARCHAR(120) DEFAULT NULL,
  `utm_campaign`         VARCHAR(120) DEFAULT NULL,
  `fbc`                  VARCHAR(255) DEFAULT NULL,
  `fbp`                  VARCHAR(255) DEFAULT NULL,
  `event_id`             VARCHAR(60) DEFAULT NULL,        -- Meta CAPI dedup

  `placed_at`            DATETIME DEFAULT NULL,
  `shipped_at`           DATETIME DEFAULT NULL,
  `delivered_at`         DATETIME DEFAULT NULL,
  `cancelled_at`         DATETIME DEFAULT NULL,
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_customer` (`customer_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_phone` (`customer_phone`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_div` FOREIGN KEY (`division_id`) REFERENCES `geo_divisions`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_dist` FOREIGN KEY (`district_id`) REFERENCES `geo_districts`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_ps` FOREIGN KEY (`police_station_id`) REFERENCES `geo_police_stations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`       BIGINT UNSIGNED NOT NULL,
  `product_id`     BIGINT UNSIGNED DEFAULT NULL,
  `variation_id`   BIGINT UNSIGNED DEFAULT NULL,
  `sku`            VARCHAR(80) NOT NULL,
  `name`           VARCHAR(190) NOT NULL,
  `option_summary` VARCHAR(190) DEFAULT NULL,
  `image`          VARCHAR(255) DEFAULT NULL,
  `unit_price`     DECIMAL(12,2) NOT NULL,
  `quantity`       INT UNSIGNED NOT NULL,
  `line_total`     DECIMAL(14,2) NOT NULL,
  `batch_number`   VARCHAR(60) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `idx_oi_prod` (`product_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(40) DEFAULT NULL,
  `to_status`   VARCHAR(40) NOT NULL,
  `note`       VARCHAR(255) DEFAULT NULL,
  `user_id`    BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abandoned-cart capture (for cart-abandonment cron)
CREATE TABLE IF NOT EXISTS `abandoned_carts` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token`  VARCHAR(80) NOT NULL,
  `customer_phone` VARCHAR(20) DEFAULT NULL,
  `customer_email` VARCHAR(190) DEFAULT NULL,
  `cart_payload`   JSON NOT NULL,
  `subtotal`       DECIMAL(14,2) NOT NULL DEFAULT 0,
  `recovery_token` VARCHAR(64) NOT NULL,
  `recovered_at`   DATETIME DEFAULT NULL,
  `recovery_email_sent_at` DATETIME DEFAULT NULL,
  `recovery_sms_sent_at`   DATETIME DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ac_token` (`recovery_token`),
  KEY `idx_ac_session` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. SMS / OTP / EMAIL LOGS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone`      VARCHAR(20) NOT NULL,
  `message`    TEXT NOT NULL,
  `purpose`    ENUM('otp','order_update','marketing','admin_alert','recovery') NOT NULL DEFAULT 'otp',
  `provider`   VARCHAR(40) DEFAULT NULL,
  `status`     ENUM('queued','sent','failed','delivered') NOT NULL DEFAULT 'queued',
  `cost`       DECIMAL(8,2) DEFAULT NULL,
  `provider_response` TEXT DEFAULT NULL,
  `order_id`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_phone` (`phone`),
  KEY `idx_sms_order` (`order_id`),
  KEY `idx_sms_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone`      VARCHAR(20) NOT NULL,
  `code_hash`  VARCHAR(255) NOT NULL,
  `purpose`    VARCHAR(40) NOT NULL DEFAULT 'checkout',
  `order_id`   BIGINT UNSIGNED DEFAULT NULL,
  `attempts`   INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_phone` (`phone`),
  KEY `idx_otp_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email`  VARCHAR(190) NOT NULL,
  `subject`   VARCHAR(190) NOT NULL,
  `template`  VARCHAR(80) DEFAULT NULL,
  `body_excerpt` TEXT DEFAULT NULL,
  `status`    ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `error`     TEXT DEFAULT NULL,
  `order_id`  BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_em_status` (`status`),
  KEY `idx_em_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. CMS: PAGES & REVIEWS (REPLICATING WP PAGES + COMMENTS)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(190) NOT NULL,
  `slug`        VARCHAR(220) NOT NULL,
  `content`     LONGTEXT NOT NULL,
  `excerpt`     TEXT DEFAULT NULL,
  `template`    VARCHAR(60) NOT NULL DEFAULT 'default',
  `status`      ENUM('draft','published','private') NOT NULL DEFAULT 'draft',
  `meta_title`  VARCHAR(190) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `author_id`   BIGINT UNSIGNED DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED DEFAULT NULL,
  `order_id`   BIGINT UNSIGNED DEFAULT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(190) DEFAULT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,        -- 1..5
  `title`      VARCHAR(190) DEFAULT NULL,
  `body`       TEXT NOT NULL,
  `status`     ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
  `is_verified_buyer` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_prod` (`product_id`),
  KEY `idx_pr_status` (`status`),
  CONSTRAINT `fk_pr_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. MARKETING / SCRIPTS / SETTINGS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketing_scripts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,            -- "Meta Pixel", "GTM", "MS Clarity"
  `provider`   ENUM('gtm','meta_pixel','ms_clarity','google_ads','google_analytics','tiktok','custom') NOT NULL,
  `placement`  ENUM('head','body_open','body_close','footer') NOT NULL DEFAULT 'head',
  `code`       LONGTEXT NOT NULL,
  `tag_id`     VARCHAR(120) DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `key_name`   VARCHAR(100) NOT NULL,
  `value`      LONGTEXT DEFAULT NULL,
  `group_name` VARCHAR(60) NOT NULL DEFAULT 'general',
  `type`       ENUM('text','number','json','boolean','password','html') NOT NULL DEFAULT 'text',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(60) NOT NULL,
  `type`           ENUM('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent',
  `value`          DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_subtotal`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `max_uses`       INT UNSIGNED DEFAULT NULL,
  `uses_count`     INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at`      DATETIME DEFAULT NULL,
  `expires_at`     DATETIME DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. WEBHOOKS / API / AUDIT
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direction`  ENUM('in','out') NOT NULL,
  `endpoint`   VARCHAR(255) NOT NULL,
  `service`    VARCHAR(60) DEFAULT NULL,        -- 'meta_capi','steadfast','bkash','cloudflare'
  `method`     VARCHAR(10) NOT NULL DEFAULT 'POST',
  `request_payload`  LONGTEXT DEFAULT NULL,
  `response_payload` LONGTEXT DEFAULT NULL,
  `status_code` INT DEFAULT NULL,
  `success`    TINYINT(1) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(40) DEFAULT NULL,
  `reference_id`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wh_service` (`service`),
  KEY `idx_wh_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `name`        VARCHAR(120) NOT NULL,           -- "Warehouse APK"
  `token_hash`  VARCHAR(255) NOT NULL,
  `abilities`   JSON DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `expires_at`  DATETIME DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_at_user` (`user_id`),
  CONSTRAINT `fk_at_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED DEFAULT NULL,
  `action`       VARCHAR(80) NOT NULL,
  `subject_type` VARCHAR(60) DEFAULT NULL,
  `subject_id`   BIGINT UNSIGNED DEFAULT NULL,
  `meta`         JSON DEFAULT NULL,
  `ip`           VARCHAR(45) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_subject` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. MEDIA LIBRARY (auto-WebP records)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `media` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path`          VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `mime`          VARCHAR(80) DEFAULT NULL,
  `width`         INT DEFAULT NULL,
  `height`        INT DEFAULT NULL,
  `size_original_bytes` INT UNSIGNED DEFAULT NULL,
  `size_webp_bytes`     INT UNSIGNED DEFAULT NULL,
  `compression_ratio`   DECIMAL(5,2) DEFAULT NULL,    -- e.g., 78.42 (% saved)
  `alt_text`      VARCHAR(255) DEFAULT NULL,
  `uploaded_by`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_uploader` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. HOMEPAGE BUILDER / BANNERS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `home_sections` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`       ENUM('hero','banner_split','popular_grid','category_strip','trust_badges','newsletter','testimonial') NOT NULL,
  `title`      VARCHAR(190) DEFAULT NULL,
  `subtitle`   VARCHAR(255) DEFAULT NULL,
  `payload`    JSON DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. WISHLIST & RECENT VIEWS (lightweight CRM)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`  BIGINT UNSIGNED DEFAULT NULL,
  `session_token` VARCHAR(80) DEFAULT NULL,
  `product_id`   BIGINT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wl_customer` (`customer_id`),
  KEY `idx_wl_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
