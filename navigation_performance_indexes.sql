-- Navigation Performance Optimization Indexes
-- Run these commands to optimize navigation bar performance

-- Add indexes for unread message count queries
-- These indexes will speed up the frequently called unread count queries

-- Index for admin unread count query (sender_type + is_read)
CREATE INDEX IF NOT EXISTS idx_chat_messages_admin_unread 
ON chat_messages (sender_type, is_read);

-- Index for user unread count query (conversation_id + sender_type + is_read)  
CREATE INDEX IF NOT EXISTS idx_chat_messages_user_unread 
ON chat_messages (conversation_id, sender_type, is_read);

-- Index for general message performance (created_at for ordering)
CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at 
ON chat_messages (created_at);

-- Index for user lookups (used in navigation)
CREATE INDEX IF NOT EXISTS idx_users_mobile_number 
ON users (mobile_number);

-- Composite index for better performance on username lookups
CREATE INDEX IF NOT EXISTS idx_users_username_id 
ON users (username, id);

-- Show current indexes to verify
SHOW INDEX FROM chat_messages;
SHOW INDEX FROM users;