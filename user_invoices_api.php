<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: application/json');

try {
    $userId = (int)$_SESSION['user_id'];
    
    // Fetch all invoices for the logged-in user
    $stmt = db()->prepare('
        SELECT i.*, l.title as listing_title, l.description as listing_description
        FROM invoices i 
        JOIN listings l ON i.listing_id = l.id 
        WHERE i.user_id = ? 
        ORDER BY i.created_at DESC
    ');
    $stmt->execute([$userId]);
    $invoices = $stmt->fetchAll();
    
    // Count unread invoices (invoices with status 'sent' or 'overdue' that haven't been paid)
    $stmt = db()->prepare('
        SELECT COUNT(*) as unread_count
        FROM invoices 
        WHERE user_id = ? AND status IN ("sent", "overdue")
    ');
    $stmt->execute([$userId]);
    $unreadData = $stmt->fetch();
    $unreadCount = $unreadData ? (int)$unreadData['unread_count'] : 0;
    
    // Get the most recent invoice
    $latestInvoice = null;
    if (!empty($invoices)) {
        // Find the latest sent or overdue invoice
        foreach ($invoices as $inv) {
            if (in_array($inv['status'], ['sent', 'overdue'])) {
                $latestInvoice = $inv;
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'unread_count' => $unreadCount,
        'latest_invoice' => $latestInvoice,
        'total' => count($invoices)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch invoices: ' . $e->getMessage()
    ]);
}
