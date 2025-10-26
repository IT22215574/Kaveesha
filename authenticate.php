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
    // Fetch user details to determine role and id
    try {
        $stmt = db()->prepare('SELECT id, is_admin FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($row = $stmt->fetch()) {
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['is_admin'] = !empty($row['is_admin']);
        }
    } catch (Throwable $e) {
        // leave as basic session if query fails
    }
    if (!empty($_SESSION['is_admin'])) {
        header('Location: /Kaveesha/admin.php');
    } else {
        header('Location: /Kaveesha/dashboard.php');
    }
    exit;
} else {
    $_SESSION['flash'] = 'Invalid username or password.';
    header('Location: /Kaveesha/login.php');
    exit;
}
?>