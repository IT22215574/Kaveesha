<?php
require_once __DIR__ . '/config.php';
require_admin();

header('Content-Type: application/json');

try {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }

    // Verify user exists
    $userStmt = db()->prepare('SELECT id, username, mobile_number FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Fetch listings for the specified user
    $sql = 'SELECT 
                l.id,
                l.title,
                l.description,
                l.status,
                l.image_path,
                l.image_path_2,
                l.image_path_3,
                l.created_at,
                CASE 
                    WHEN l.status = 1 THEN "Not Finished"
                    WHEN l.status = 2 THEN "Returned"
                    WHEN l.status = 3 THEN "Finished & Pending Payment"
                    WHEN l.status = 4 THEN "Completed & Received Payment"
                    ELSE "Unknown"
                END as status_text,
                COUNT(i.id) as invoice_count
            FROM listings l 
            LEFT JOIN invoices i ON i.listing_id = l.id
            WHERE l.user_id = ? 
            GROUP BY l.id
            ORDER BY l.created_at DESC';
    
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    $listings = $stmt->fetchAll();

    // Get total count
    $countSql = 'SELECT COUNT(*) as total FROM listings WHERE user_id = ?';
    $countStmt = db()->prepare($countSql);
    $countStmt->execute([$userId]);
    $totalCount = $countStmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'user' => $user,
        'listings' => $listings,
        'total' => $totalCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch listings: ' . $e->getMessage()]);
}
?>