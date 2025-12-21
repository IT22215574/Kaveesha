# Messaging System Setup and Usage

## Overview
This messaging system provides a chat interface between customers and admin (MC YOMA electronic). It includes:

- Messaging between users and admin
- Unread message notifications with red badges
- Conversation management for admin
- Message history and timestamps
- Optimized performance with minimal database queries
- Manual refresh to see new messages (no auto-polling)

## Database Setup

1. **For New Installations**: The messaging table is already included in `setup.sql`

2. **For Existing Installations**: Run the `messaging_update.sql` file in phpMyAdmin or MySQL CLI:
   ```sql
   -- Navigate to your database and run:
   source /path/to/messaging_update.sql;
   ```

## File Structure

### New Files Added:
- `messages.php` - User messaging interface
- `admin_messages.php` - Admin messaging interface  
- `messages_api.php` - REST API for messaging operations
- `messages_sse.php` - Server-sent events for real-time updates
- `messaging_update.sql` - Database schema update
- `MESSAGING_README.md` - This documentation

### Modified Files:
- `setup.sql` - Added chat_messages table
- `includes/user_nav.php` - Added messages link with notification badge
- `includes/admin_nav.php` - Added messages link with notification badge

### Performance Optimization Files:
- `messaging_performance_indexes.sql` - Database indexes for faster queries

## Features

### User Side (`messages.php`)
- Send messages to admin/support
- View conversation history
- Real-time message updates
- Mark messages as read automatically
- Responsive design for mobile/desktop

### Admin Side (`admin_messages.php`)  
- View all customer conversations
- See unread message counts per conversation
- Reply to customer messages
- Conversation list with last message preview

## Performance Improvements

### What Was Fixed:
1. **Removed Auto-Polling**: Eliminated automatic refresh every 5 seconds that was causing excessive database queries
2. **Optimized Database Queries**: Added LIMIT clauses and composite indexes for faster query execution
3. **Efficient Message Rendering**: New messages are appended without full page reload
4. **Reduced SSE Frequency**: Server-sent events now check every 5 seconds instead of every 1 second
5. **Database Indexing**: Added composite indexes for faster message retrieval

### Performance Benefits:
- **Faster page loads** - No more constant polling and reloading
- **Reduced server load** - Fewer database queries and API calls  
- **Better user experience** - Smooth message sending without delays
- **Lower bandwidth usage** - Only new data is transmitted
- **Scalable architecture** - System can handle more concurrent users

### Manual Refresh:
Users can refresh the page (F5 or reload button) to see new messages. This provides better control over when data is loaded and significantly improves performance.
- Real-time updates for new messages

### API Endpoints (`messages_api.php`)
- `GET ?action=messages&conversation_id=X` - Get messages for conversation
- `GET ?action=conversations` - Get all conversations (admin only)
- `GET ?action=unread_count` - Get unread message count
- `POST ?action=send` - Send a new message
- `POST ?action=mark_read` - Mark messages as read

## Database Schema

### chat_messages Table
```sql
- id: Auto-increment primary key
- conversation_id: Groups messages (equals user_id for user conversations)
- sender_id: Foreign key to users table
- sender_type: 'user' or 'admin'
- message: Text content of the message
- is_read: Boolean flag for read status
- created_at: Timestamp of message creation
```

## Usage Instructions

### For Customers/Users:
1. Click "Messages" in the navigation bar
2. Type message in the input field and click "Send"
3. View conversation history in the chat area
4. Red badge appears on Messages link when new admin replies arrive

### For Admin:
1. Click "Messages" in the admin navigation
2. Select a conversation from the left sidebar
3. View conversation history and unread count
4. Reply using the message input at the bottom
5. Red badge shows total unread messages from all customers

## Real-time Features

### Notification System:
- Red badges appear on navigation links when new messages arrive
- Badges update in real-time using Server-Sent Events (SSE)
- Fallback to polling if SSE is not supported

### Auto-refresh:
- Messages refresh every 5 seconds in chat windows
- Unread counts update every 10 seconds (or real-time via SSE)
- Conversations list updates when new messages arrive

## Security Features

- Authentication required for all messaging endpoints
- Users can only access their own conversations
- Admin access required for conversation management
- SQL injection protection with prepared statements
- XSS protection with HTML escaping

## Browser Compatibility

- Modern browsers: Real-time updates via Server-Sent Events
- Older browsers: Automatic fallback to polling
- Mobile responsive design
- Works with JavaScript disabled (basic functionality)

## Troubleshooting

### Messages not loading:
1. Check database connection in `config.php`
2. Ensure `chat_messages` table exists
3. Verify user authentication is working

### Real-time updates not working:
1. Check browser console for JavaScript errors
2. Verify `messages_sse.php` is accessible
3. Check server configuration for SSE support

### Badges not updating:
1. Check network tab for API call errors
2. Verify `messages_api.php` returns proper JSON
3. Check browser console for JavaScript errors

## Testing

### Sample Test Flow:
1. Login as regular user
2. Go to Messages page and send a test message
3. Login as admin
4. Check that red badge appears on Messages link
5. Go to Admin Messages and reply to the user
6. Switch back to user account and verify reply appears
7. Confirm badges update correctly

## Performance Notes

- Database queries are optimized with proper indexes
- SSE connections auto-terminate after 30 seconds
- Message polling has reasonable intervals to balance real-time feel with server load
- Unread counts are cached and only updated when needed