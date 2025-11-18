<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');

// Keep connection alive for up to 60 seconds but check less frequently
set_time_limit(60);

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

// Function to send SSE data
function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

$lastUnreadCount = -1;

// Reduced iterations - check every 5 seconds instead of every 1 second
for ($i = 0; $i < 12; $i++) {
    try {
        if ($isAdmin) {
            // For admin, count unread messages from all users
            $stmt = db()->prepare("
                SELECT COUNT(*) as unread_count
                FROM chat_messages
                WHERE sender_type = 'user' AND is_read = 0
            ");
            $stmt->execute();
        } else {
            // For users, count unread messages from admin
            $stmt = db()->prepare("
                SELECT COUNT(*) as unread_count
                FROM chat_messages
                WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = 0
            ");
            $stmt->execute([$userId]);
        }
        
        $result = $stmt->fetch();
        $currentUnreadCount = (int)$result['unread_count'];
        
        // Only send update if count changed
        if ($currentUnreadCount !== $lastUnreadCount) {
            sendSSE(['unread_count' => $currentUnreadCount]);
            $lastUnreadCount = $currentUnreadCount;
        }
        
    } catch (Exception $e) {
        sendSSE(['error' => 'Database error']);
        break;
    }
    
    // Check every 5 seconds instead of every 1 second
    sleep(5);
    
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
}
?>