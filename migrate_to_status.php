<?php
// migrate_to_status.php - Run this once to migrate existing listings from price to status
require_once __DIR__ . '/config.php';

try {
    // Check if listings table exists and has price column
    $checkTable = db()->query("SHOW TABLES LIKE 'listings'")->fetch();
    if (!$checkTable) {
        echo "Listings table doesn't exist yet. No migration needed.\n";
        exit(0);
    }

    // Check if price column exists
    $checkPrice = db()->query("SHOW COLUMNS FROM listings LIKE 'price'")->fetch();
    $checkStatus = db()->query("SHOW COLUMNS FROM listings LIKE 'status'")->fetch();

    if ($checkPrice && !$checkStatus) {
        echo "Migrating from price to status column...\n";
        
        // Add status column
        db()->exec("ALTER TABLE listings ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=not finished, 2=stopped, 3=finished & pending payments, 4=completed & received payments'");
        
        // Set status to 1 (not finished) for all existing records
        db()->exec("UPDATE listings SET status = 1");
        
        // Drop price column
        db()->exec("ALTER TABLE listings DROP COLUMN price");
        
        echo "Migration completed successfully!\n";
        echo "All existing listings have been set to status 1 (Not Finished).\n";
        
    } elseif (!$checkPrice && $checkStatus) {
        echo "Already migrated - status column exists and price column doesn't.\n";
        
    } elseif ($checkPrice && $checkStatus) {
        echo "Both price and status columns exist. Manual cleanup may be needed.\n";
        
    } else {
        echo "Table structure is ready for new installations.\n";
    }

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>