<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    // Fetch listings for the current user
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
                    WHEN l.status = 2 THEN "Stopped"
                    WHEN l.status = 3 THEN "Finished & Pending Payment"
                    WHEN l.status = 4 THEN "Completed & Received Payment"
                    ELSE "Unknown"
                END as status_text
            FROM listings l 
            WHERE l.user_id = ? 
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
        'listings' => $listings,
        'total' => $totalCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch listings: ' . $e->getMessage()]);
}
?>