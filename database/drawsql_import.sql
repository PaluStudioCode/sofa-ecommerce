CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
);

CREATE TABLE `user_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `detail` TEXT NOT NULL,
  `formatted_address` TEXT NOT NULL,
  `city` VARCHAR(255) NULL DEFAULT NULL,
  `district` VARCHAR(255) NULL DEFAULT NULL,
  `postal_code` VARCHAR(20) NULL DEFAULT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_addresses_is_default_index` (`is_default`),
  KEY `user_addresses_user_id_is_default_index` (`user_id`, `is_default`),
  KEY `user_addresses_latitude_longitude_index` (`latitude`, `longitude`),
  CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_is_active_index` (`is_active`)
);

CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_status_index` (`status`),
  KEY `products_is_featured_index` (`is_featured`),
  KEY `products_category_id_status_index` (`category_id`, `status`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE `product_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku` VARCHAR(100) NULL DEFAULT NULL,
  `variant_name` VARCHAR(255) NULL DEFAULT NULL,
  `size` VARCHAR(255) NULL DEFAULT NULL,
  `material` VARCHAR(255) NULL DEFAULT NULL,
  `color` VARCHAR(100) NULL DEFAULT NULL,
  `price` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('aktif', 'nonaktif', 'stok_habis') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_status_index` (`status`),
  KEY `product_variants_product_id_status_index` (`product_id`, `status`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_product_variants_reserved_stock` CHECK (`reserved_stock` <= `stock`)
);

CREATE TABLE `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_variant_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(255) NULL DEFAULT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_is_primary_index` (`is_primary`),
  KEY `product_images_product_variant_id_sort_order_index` (`product_variant_id`, `sort_order`),
  CONSTRAINT `product_images_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `cart_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_variant_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_items_user_id_product_variant_id_unique` (`user_id`, `product_variant_id`),
  KEY `cart_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `cart_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `vouchers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `discount_type` ENUM('nominal', 'percentage') NOT NULL,
  `discount_value` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `max_discount` DECIMAL(15, 2) UNSIGNED NULL DEFAULT NULL,
  `minimum_purchase` DECIMAL(15, 2) UNSIGNED NOT NULL DEFAULT 0,
  `quota` INT UNSIGNED NULL DEFAULT NULL,
  `per_user_limit` INT UNSIGNED NULL DEFAULT NULL,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME NOT NULL,
  `status` ENUM('aktif', 'nonaktif', 'kedaluwarsa', 'kuota_habis') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vouchers_code_unique` (`code`),
  KEY `vouchers_status_index` (`status`),
  KEY `vouchers_status_start_at_end_at_index` (`status`, `start_at`, `end_at`)
);

CREATE TABLE `shipping_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `origin_name` VARCHAR(255) NOT NULL,
  `origin_address` TEXT NULL,
  `origin_latitude` DECIMAL(10, 8) NOT NULL,
  `origin_longitude` DECIMAL(11, 8) NOT NULL,
  `radius_km` DECIMAL(8, 2) UNSIGNED NOT NULL,
  `shipping_cost_per_km` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_settings_is_active_index` (`is_active`),
  KEY `shipping_settings_origin_latitude_origin_longitude_index` (`origin_latitude`, `origin_longitude`),
  CONSTRAINT `chk_shipping_settings_radius_positive` CHECK (`radius_km` > 0)
);

CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(100) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `customer_note` TEXT NULL,
  `order_status` ENUM('menunggu_pembayaran', 'diproses', 'dalam_perjalanan', 'barang_diterima') NOT NULL DEFAULT 'menunggu_pembayaran',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_order_status_index` (`order_status`),
  KEY `orders_user_id_order_status_index` (`user_id`, `order_status`),
  KEY `orders_order_status_created_at_index` (`order_status`, `created_at`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE `order_totals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `subtotal_amount` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `discount_amount` DECIMAL(15, 2) UNSIGNED NOT NULL DEFAULT 0,
  `shipping_cost` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `total_amount` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_totals_order_id_unique` (`order_id`),
  CONSTRAINT `order_totals_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `order_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `delivery_scheduled_at` DATETIME NULL DEFAULT NULL,
  `delivery_delivered_at` DATETIME NULL DEFAULT NULL,
  `driver_name` VARCHAR(255) NULL DEFAULT NULL,
  `driver_phone` VARCHAR(30) NULL DEFAULT NULL,
  `vehicle_note` VARCHAR(255) NULL DEFAULT NULL,
  `delivery_note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_deliveries_order_id_unique` (`order_id`),
  CONSTRAINT `order_deliveries_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `order_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_address_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `recipient_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `detail` TEXT NOT NULL,
  `formatted_address` TEXT NOT NULL,
  `city` VARCHAR(255) NULL DEFAULT NULL,
  `district` VARCHAR(255) NULL DEFAULT NULL,
  `postal_code` VARCHAR(20) NULL DEFAULT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_addresses_order_id_unique` (`order_id`),
  KEY `order_addresses_user_address_id_foreign` (`user_address_id`),
  KEY `order_addresses_latitude_longitude_index` (`latitude`, `longitude`),
  CONSTRAINT `order_addresses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_addresses_user_address_id_foreign` FOREIGN KEY (`user_address_id`) REFERENCES `user_addresses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `order_voucher_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `voucher_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `voucher_code` VARCHAR(100) NOT NULL,
  `voucher_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_voucher_snapshots_order_id_unique` (`order_id`),
  KEY `order_voucher_snapshots_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `order_voucher_snapshots_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_voucher_snapshots_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `order_shipping_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `shipping_setting_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `origin_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_shipping_snapshots_order_id_unique` (`order_id`),
  KEY `order_shipping_snapshots_shipping_setting_id_foreign` (`shipping_setting_id`),
  CONSTRAINT `order_shipping_snapshots_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_shipping_snapshots_shipping_setting_id_foreign` FOREIGN KEY (`shipping_setting_id`) REFERENCES `shipping_settings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_variant_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_name` VARCHAR(255) NULL DEFAULT NULL,
  `variant_sku` VARCHAR(100) NULL DEFAULT NULL,
  `variant_size` VARCHAR(255) NULL DEFAULT NULL,
  `variant_material` VARCHAR(255) NULL DEFAULT NULL,
  `variant_color` VARCHAR(100) NULL DEFAULT NULL,
  `product_price` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `subtotal` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `attempt_number` INT UNSIGNED NOT NULL,
  `midtrans_order_id` VARCHAR(100) NOT NULL,
  `midtrans_transaction_id` VARCHAR(100) NULL DEFAULT NULL,
  `payment_type` VARCHAR(100) NULL DEFAULT NULL,
  `status` ENUM('pending', 'success', 'failed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
  `transaction_status` VARCHAR(100) NOT NULL DEFAULT 'pending',
  `fraud_status` VARCHAR(100) NULL DEFAULT NULL,
  `gross_amount` DECIMAL(15, 2) UNSIGNED NOT NULL,
  `snap_token` VARCHAR(255) NULL DEFAULT NULL,
  `redirect_url` VARCHAR(255) NULL DEFAULT NULL,
  `paid_at` DATETIME NULL DEFAULT NULL,
  `expired_at` DATETIME NULL DEFAULT NULL,
  `raw_response` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_midtrans_order_id_unique` (`midtrans_order_id`),
  UNIQUE KEY `payments_order_id_attempt_number_unique` (`order_id`, `attempt_number`),
  KEY `payments_midtrans_transaction_id_index` (`midtrans_transaction_id`),
  KEY `payments_status_index` (`status`),
  KEY `payments_transaction_status_index` (`transaction_status`),
  KEY `payments_order_id_status_index` (`order_id`, `status`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);
