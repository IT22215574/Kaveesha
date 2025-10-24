<?php
// index.php — redirect to dashboard if logged in, otherwise to login
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user'])) {
    header('Location: /Kaveesha/dashboard.php');
} else {
    header('Location: /Kaveesha/login.php');
}
exit;
?>