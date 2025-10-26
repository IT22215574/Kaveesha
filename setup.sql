-- setup.sql — initialize MySQL database and users table for Kaveesha app
-- Run this in phpMyAdmin or the MySQL CLI

-- 1) Create database
CREATE DATABASE IF NOT EXISTS `Electronice` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `Electronice`;

-- 2) Create users table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(191) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_mobile` (`mobile_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3) Seed users
-- Admin user (mobile-only login): password column is unused and may be NULL
INSERT INTO `users` (`username`, `mobile_number`, `password_hash`, `is_admin`) VALUES
('yoma electronics', '0775604833', NULL, 1),
('Demo User', '0712345678', NULL, 0);

-- 4) Create messages table for contact form submissions
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `phone` VARCHAR(32) NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
