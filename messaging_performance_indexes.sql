-- Performance optimization indexes for messaging system
-- Run this in phpMyAdmin or MySQL CLI to improve query performance

USE `Electronice`;

-- Add composite indexes for better query performance on chat_messages table
-- Check and add indexes one by one to avoid duplicate errors
ALTER TABLE `chat_messages` ADD INDEX IF NOT EXISTS `idx_conversation_read_time` (`conversation_id`, `is_read`, `created_at`);
ALTER TABLE `chat_messages` ADD INDEX IF NOT EXISTS `idx_conversation_sender_time` (`conversation_id`, `sender_type`, `created_at`);
ALTER TABLE `chat_messages` ADD INDEX IF NOT EXISTS `idx_unread_messages` (`sender_type`, `is_read`, `created_at`);

-- Optimize existing indexes (skip sender_id as it's part of foreign key constraint)
DROP INDEX IF EXISTS `idx_conversation_id` ON `chat_messages`;
DROP INDEX IF EXISTS `idx_created_at` ON `chat_messages`;

-- Re-add optimized single-column indexes
ALTER TABLE `chat_messages`
ADD INDEX `idx_conversation_id` (`conversation_id`),
ADD INDEX `idx_created_at_desc` (`created_at` DESC);

-- Note: idx_sender_id cannot be dropped as it's required for foreign key constraint

-- Add index for faster user lookups in messaging
ALTER TABLE `users` 
ADD INDEX `idx_username_mobile` (`username`, `mobile_number`);

-- Show index usage (optional - for verification)
-- SHOW INDEX FROM `chat_messages`;