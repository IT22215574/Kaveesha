<?php
// index.php — redirect to dashboard if logged in, otherwise to login
ob_start(); // Start output buffering to prevent header issues
// Disable error display in production for security
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user'])) {
    header('Location: /dashboard.php');
    exit;
} else {
    header('Location: /login.php');
    exit;
}
?>