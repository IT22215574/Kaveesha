<?php
// test_db_connection.php - Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

$hosts_to_try = ['localhost', '127.0.0.1'];
$db_name = 'u983239990_electronics';
$db_user = 'u983239990_Yoma';
$db_pass = 'Yomal@1234@';

foreach ($hosts_to_try as $host) {
    echo "<h2>Testing with host: $host</h2>";
    
    try {
        $dsn = "mysql:host=$host;port=3306;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "✅ <strong>Success!</strong> Connected to MySQL server with host: <strong>$host</strong><br>";
        
        // Now try to select the database
        try {
            $pdo->exec("USE `$db_name`");
            echo "✅ <strong>Success!</strong> Database '$db_name' exists and is accessible<br>";
            
            // Check if users table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo "✅ <strong>Success!</strong> 'users' table exists<br>";
            } else {
                echo "❌ <strong>Warning:</strong> 'users' table not found. You need to import setup.sql<br>";
            }
            
        } catch (PDOException $e) {
            echo "❌ <strong>Error:</strong> Database '$db_name' does not exist or is not accessible<br>";
            echo "Error message: " . $e->getMessage() . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ <strong>Failed:</strong> Could not connect with host: $host<br>";
        echo "Error message: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

echo "<h2>Summary:</h2>";
echo "<p>If you see ✅ Success messages above, use that hostname in config.php and db.php</p>";
echo "<p>If you see ❌ errors, check:</p>";
echo "<ul>";
echo "<li>Database credentials are correct in Hostinger</li>";
echo "<li>Database '$db_name' exists in Hostinger</li>";
echo "<li>You have imported setup.sql via phpMyAdmin</li>";
echo "</ul>";
?>
