<?php
// db.php - Database connection for hosted web application
// Update these variables with your actual database credentials
$DB_HOST = 'localhost'; // or your remote DB host
$DB_NAME = 'u983239990_electronics';
$DB_USER = 'u983239990_Yoma';
$DB_PASS = 'Admin@Yoma2025';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // In production, log this error instead of displaying it
    exit('Database connection failed: ' . $e->getMessage());
}
