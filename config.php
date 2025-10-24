<?php
// config.php — shared configuration with DB-backed authentication
session_start();

// Database configuration
// Adjust these to your local MySQL credentials if needed
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'Electronice');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); // XAMPP default often has empty password for root

// PDO singleton
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<h1>Database connection failed</h1>';
            echo '<p>Please ensure MySQL is running, setup.sql is imported, and config.php credentials are correct.</p>';
            exit;
        }
    }
    return $pdo;
}

// Helper: check credentials against DB users table
function check_credentials($username, $password) {
    if ($username === '' || $password === '') return false;
    $sql = 'SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) return false;
    return password_verify($password, $row['password_hash']);
}

// Helper: find user by mobile (mobile-only login)
// Returns associative array with keys: id, username, mobile_number, created_at; or false if not found
function find_user_by_mobile($mobile) {
    $mobile = trim((string)$mobile);
    if ($mobile === '') return false;
    $sql = 'SELECT id, username, mobile_number, is_admin, created_at FROM users WHERE mobile_number = ? LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$mobile]);
    $row = $stmt->fetch();
    return $row ?: false;
}

// Helper: require admin
function require_admin() {
    if (empty($_SESSION['user']) || empty($_SESSION['is_admin'])) {
        header('Location: /Kaveesha/login.php');
        exit;
    }
}

// Helper: require login
function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: /Kaveesha/login.php');
        exit;
    }
}

?>