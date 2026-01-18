<?php
// index.php — redirect to dashboard if logged in, otherwise to login
ob_start(); // Start output buffering to prevent header issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user'])) {
    header('Location: /Kaveesha/dashboard.php');
    exit;
} else {
    header('Location: /Kaveesha/login.php');
    exit;
}
?>