<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'messages':
            getMessages();
            break;
        case 'conversations':
            getConversations();
            break;
        case 'unread_count':
            getUnreadCount();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'send':
            sendMessage();
            break;
        case 'mark_read':
            markMessagesRead();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getMessages() {
    $conversationId = $_GET['conversation_id'] ?? '';
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['is_admin'] ?? false;
    
    if (!$conversationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID required']);
        return;
    }
    
    // For regular users, they can only see their own conversation
    if (!$isAdmin && $conversationId != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $stmt = db()->prepare("
        SELECT 
            cm.*,
            u.username as sender_name
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.conversation_id = ?
        ORDER BY cm.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll();
    
    // Reverse to show oldest first (bottom to top chronological order)
    $messages = array_reverse($messages);
    
    echo json_encode(['messages' => $messages]);
}

function getConversations() {
    $isAdmin = $_SESSION['is_admin'] ?? false;
    
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }
    
    $stmt = db()->prepare("
        SELECT 
            cm.conversation_id,
            u.username,
            u.mobile_number,
            MAX(cm.created_at) as last_message_at,
            SUM(CASE WHEN cm.is_read = 0 AND cm.sender_type = 'user' THEN 1 ELSE 0 END) as unread_count,
            SUBSTRING((SELECT message FROM chat_messages 
             WHERE conversation_id = cm.conversation_id 
             ORDER BY created_at DESC LIMIT 1), 1, 50) as last_message
        FROM chat_messages cm
        JOIN users u ON cm.conversation_id = u.id
        GROUP BY cm.conversation_id, u.username, u.mobile_number
        ORDER BY last_message_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $conversations = $stmt->fetchAll();
    
    echo json_encode(['conversations' => $conversations]);
}

function getUnreadCount() {
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['is_admin'] ?? false;
    
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
    echo json_encode(['unread_count' => (int)$result['unread_count']]);
}

function sendMessage() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['is_admin'] ?? false;
    $message = trim($input['message']);
    
    if ($isAdmin) {
        // Admin sending to a user
        $conversationId = $input['conversation_id'] ?? '';
        if (!$conversationId) {
            http_response_code(400);
            echo json_encode(['error' => 'Conversation ID required for admin']);
            return;
        }
        $senderType = 'admin';
    } else {
        // User sending (conversation_id is their own user_id)
        $conversationId = $userId;
        $senderType = 'user';
    }
    
    $stmt = db()->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message)
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$conversationId, $userId, $senderType, $message])) {
        $messageId = db()->lastInsertId();
        
        // Get the complete message with sender info
        $stmt = db()->prepare("
            SELECT 
                cm.*,
                u.username as sender_name
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.id = ?
        ");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch();
        
        echo json_encode(['success' => true, 'message' => $newMessage]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
}

function markMessagesRead() {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = $input['conversation_id'] ?? '';
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['is_admin'] ?? false;
    
    if (!$conversationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID required']);
        return;
    }
    
    // Mark messages as read based on user type
    if ($isAdmin) {
        // Admin marking user messages as read
        $stmt = db()->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_type = 'user' AND is_read = 0
        ");
    } else {
        // User marking admin messages as read (only their own conversation)
        if ($conversationId != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        $stmt = db()->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = 0
        ");
    }
    
    if ($stmt->execute([$conversationId])) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to mark messages as read']);
    }
}
?>