<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Kaveesha/login.php');
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    $_SESSION['flash'] = 'Please provide username and password.';
    header('Location: /Kaveesha/login.php');
    exit;
}

if (check_credentials($username, $password)) {
    // Successful login
    session_regenerate_id(true);
    $_SESSION['user'] = $username;
    header('Location: /Kaveesha/dashboard.php');
    exit;
} else {
    $_SESSION['flash'] = 'Invalid username or password.';
    header('Location: /Kaveesha/login.php');
    exit;
}
?>