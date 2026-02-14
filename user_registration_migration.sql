-- Migration for user self-registration with admin approval
-- Run this in phpMyAdmin or MySQL CLI after importing setup.sql

USE `Electronice`;

-- Create user_registration_requests table
CREATE TABLE IF NOT EXISTS `user_registration_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(191) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Admin user ID who processed the request',
  `rejection_reason` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_mobile_number` (`mobile_number`),
  KEY `idx_requested_at` (`requested_at`),
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
