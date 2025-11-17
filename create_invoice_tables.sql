USE Electronice;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT NULL,
  `status` ENUM('draft', 'sent', 'paid', 'overdue') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invoice_number` (`invoice_number`),
  KEY `idx_listing_id` (`listing_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;