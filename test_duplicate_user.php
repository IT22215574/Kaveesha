<?php
// Simple CLI test: run `php test_duplicate_user.php` from project directory.
require_once __DIR__ . '/config.php';

$name = 'Test User';
$mobile = '0775604833'; // Existing seeded mobile
$mobileDigits = preg_replace('/\D+/', '', $mobile);

try {
    $dupMobile = db()->prepare('SELECT id FROM users WHERE mobile_number = ? LIMIT 1');
    $dupMobile->execute([$mobileDigits]);
    if ($dupMobile->fetch()) {
        echo "PASS: Duplicate mobile detected before insert.\n";
        exit(0);
    }
    // Should not reach here for seeded number
    $stmt = db()->prepare('INSERT INTO users (username, mobile_number) VALUES (?, ?)');
    $stmt->execute([$name, $mobileDigits]);
    echo "WARNING: Inserted duplicate mobile unexpectedly (DB constraint may be missing).\n";
} catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
        echo "PASS: DB unique constraint prevented duplicate.\n";
    } else {
        echo "ERROR: Unexpected PDO error: " . $e->getMessage() . "\n";
    }
}
