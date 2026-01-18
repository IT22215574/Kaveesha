<?php
// config.php — shared configuration with DB-backed authentication
session_start();

// Database configuration (hosted/production)
define('DB_HOST', 'localhost'); // or your remote DB host
define('DB_PORT', '3306');
define('DB_NAME', 'Electronice');
define('DB_USER', 'root');
define('DB_PASS', '');


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
// Ensures: user is logged in, is admin, and has passed admin confirmation step
function require_admin() {
    if (empty($_SESSION['user'])) {
        header('Location: /Kaveesha/login.php');
        exit;
    }
    if (empty($_SESSION['is_admin'])) {
        // Logged-in but not an admin -> send to normal dashboard
        header('Location: /Kaveesha/dashboard.php');
        exit;
    }
    if (empty($_SESSION['is_admin_confirmed'])) {
        // Admin but not yet confirmed -> require confirmation step
        header('Location: /Kaveesha/admin_confirm.php');
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

// Helper: clear navigation cache when user data changes
function clear_nav_cache($userId = null) {
    $userId = $userId ?: ($_SESSION['user_id'] ?? null);
    if (!$userId) return;
    
    // Clear user data cache
    unset($_SESSION['cached_username']);
    unset($_SESSION['cached_mobile']);
    
    // Clear unread count cache
    $isAdmin = $_SESSION['is_admin'] ?? false;
    $cacheKey = 'unread_count_' . ($isAdmin ? 'admin' : $userId);
    unset($_SESSION[$cacheKey]);
    unset($_SESSION[$cacheKey . '_time']);
    
    // Clear SSE cache
    $sseCacheKey = 'sse_unread_count_' . ($isAdmin ? 'admin' : $userId);
    unset($_SESSION[$sseCacheKey]);
    unset($_SESSION[$sseCacheKey . '_time']);
}

?>