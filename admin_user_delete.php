<?php
require_once __DIR__ . '/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['flash'] = 'Invalid user.';
    header('Location: /admin.php');
    exit;
}

// Fetch target user
$stmt = db()->prepare('SELECT id, username, is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION['flash'] = 'User not found.';
    header('Location: /admin.php');
    exit;
}

// Prevent deleting yourself
if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$user['id']) {
    $_SESSION['flash'] = 'You cannot delete your own account while logged in.';
    header('Location: /admin.php');
    exit;
}

// If deleting an admin, ensure there will be at least one admin left
if (!empty($user['is_admin'])) {
    $countAdmins = (int)db()->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 1')->fetchColumn();
    if ($countAdmins <= 1) {
        $_SESSION['flash'] = 'Cannot delete the only admin user.';
        header('Location: /admin.php');
        exit;
    }
}

try {
    $del = db()->prepare('DELETE FROM users WHERE id = ?');
    $del->execute([$id]);
    $_SESSION['flash'] = 'User deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['flash'] = 'Failed to delete user.';
}

header('Location: /admin.php');
exit;
