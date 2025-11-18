<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Connection: keep-alive');

// Optimized connection - shorter timeout, less frequent checks
set_time_limit(30);

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

// Function to send SSE data
function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

$lastUnreadCount = -1;
$checkInterval = 10; // Check every 10 seconds for better performance
$maxIterations = 3;  // Only run for 30 seconds total

// Send initial heartbeat
sendSSE(['type' => 'connected', 'timestamp' => time()]);

// Optimized loop - fewer iterations, longer intervals
for ($i = 0; $i < $maxIterations; $i++) {
    try {
        // Use session cache if available and recent
        $cacheKey = 'sse_unread_count_' . ($isAdmin ? 'admin' : $userId);
        $useCache = isset($_SESSION[$cacheKey]) && 
                   isset($_SESSION[$cacheKey . '_time']) && 
                   (time() - $_SESSION[$cacheKey . '_time'] < 5);
        
        if ($useCache) {
            $currentUnreadCount = (int)$_SESSION[$cacheKey];
        } else {
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
            
            // Cache the result
            $_SESSION[$cacheKey] = $currentUnreadCount;
            $_SESSION[$cacheKey . '_time'] = time();
        }
        
        // Only send update if count changed
        if ($currentUnreadCount !== $lastUnreadCount) {
            sendSSE([
                'unread_count' => $currentUnreadCount, 
                'timestamp' => time(),
                'cached' => $useCache
            ]);
            $lastUnreadCount = $currentUnreadCount;
        }
        
    } catch (Exception $e) {
        sendSSE(['error' => 'Database error', 'timestamp' => time()]);
        break;
    }
    
    // Check every 10 seconds for better performance
    sleep($checkInterval);
    
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
}

// Send final message before closing
sendSSE(['type' => 'closing', 'timestamp' => time()]);
?>