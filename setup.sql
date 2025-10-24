-- setup.sql — initialize MySQL database and users table for Kaveesha app
-- Run this in phpMyAdmin or the MySQL CLI

-- 1) Create database
CREATE DATABASE IF NOT EXISTS `kaveesha_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kaveesha_db`;

-- 2) Create users table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(191) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_mobile` (`mobile_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3) Seed an admin user
-- Mobile-only login: password column is unused and may be NULL
INSERT INTO `users` (`username`, `mobile_number`, `password_hash`) VALUES
('admin', '0712345678', NULL);
