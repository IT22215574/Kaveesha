<?php
require_once __DIR__ . '/config.php';

// Only allow logged-in admins to access this page
if (empty($_SESSION['user'])) {
  header('Location: /login.php');
  exit;
}
if (empty($_SESSION['is_admin'])) {
  // Not an admin -> normal users shouldn't be here
  header('Location: /dashboard.php');
  exit;
}

// If already confirmed, go to admin area
if (!empty($_SESSION['is_admin_confirmed'])) {
  header('Location: /admin.php');
  exit;
}

// Load current admin record
$adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$admin = null;
if ($adminId > 0) {
  try {
    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE id = ? AND is_admin = 1 LIMIT 1');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
  } catch (Throwable $e) {
    $admin = null;
  }
}
if (!$admin) {
  // Fallback: if session says admin but record not found, force logout
  $_SESSION['flash'] = 'Your session is invalid. Please sign in again.';
  header('Location: /logout.php');
  exit;
}

$hasPassword = !empty($admin['password_hash']);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF is out of scope for now; session-only page.
  if (isset($_POST['action']) && $_POST['action'] === 'set') {
    // Set admin password (first-time setup)
    $p1 = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $p2 = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';
    if ($hasPassword) {
      $flash = 'Password is already set. Please use the verify form.';
    } elseif (strlen($p1) < 6) {
      $flash = 'Password must be at least 6 characters.';
    } elseif ($p1 !== $p2) {
      $flash = 'Passwords do not match.';
    } else {
      try {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND is_admin = 1');
        $upd->execute([$hash, $adminId]);
        $_SESSION['is_admin_confirmed'] = true;
        $_SESSION['flash'] = 'Admin password set and confirmation successful.';
        header('Location: /admin.php');
        exit;
      } catch (Throwable $e) {
        $flash = 'Failed to set password. Please try again.';
      }
    }
  } else {
    // Verify existing admin password
    $p = isset($_POST['password']) ? (string)$_POST['password'] : '';
    if (!$hasPassword) {
      $flash = 'No password is set yet. Please create one.';
    } else {
      if ($p === '') {
        $flash = 'Please enter your admin password.';
      } else {
        try {
          // Re-fetch latest hash just in case it changed
          $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? AND is_admin = 1 LIMIT 1');
          $stmt->execute([$adminId]);
          $row = $stmt->fetch();
          $hash = $row ? (string)$row['password_hash'] : '';
          if ($hash && password_verify($p, $hash)) {
            $_SESSION['is_admin_confirmed'] = true;
            header('Location: /admin.php');
            exit;
          } else {
            $flash = 'Incorrect password. Please try again.';
          }
        } catch (Throwable $e) {
          $flash = 'Verification failed. Please try again.';
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirm Admin • mctronicservice</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 to-gray-50 flex items-center justify-center relative">
  <div class="max-w-md w-full bg-white/90 backdrop-blur p-8 rounded-xl shadow-xl border border-gray-100">
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">Admin confirmation</h1>
    <p class="text-sm text-gray-600 mb-6">For security, please <?php echo $hasPassword ? 'enter your admin password' : 'create an admin password'; ?> to access the admin area.</p>

    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-700"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if ($hasPassword): ?>
      <form method="post" class="space-y-4">
        <input type="hidden" name="action" value="verify" />
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Admin password</label>
          <input id="password" name="password" type="password" required style="--tw-ring-color: #692f69; border-color: #e5e7eb;" class="mt-1 block w-full px-3 py-2.5 rounded-lg border placeholder-gray-400 focus:outline-none focus:ring-2" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor='#e5e7eb'" />
        </div>
        <div>
          <button type="submit" style="background-color: #692f69; --tw-ring-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'" class="w-full inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg text-white font-medium shadow focus:outline-none focus-visible:ring-2">
            Confirm and continue
          </button>
        </div>
      </form>
    <?php else: ?>
      <form method="post" class="space-y-4">
        <input type="hidden" name="action" value="set" />
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Create admin password</label>
          <input id="password" name="password" type="password" minlength="6" required style="--tw-ring-color: #692f69; border-color: #e5e7eb;" class="mt-1 block w-full px-3 py-2.5 rounded-lg border placeholder-gray-400 focus:outline-none focus:ring-2" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor='#e5e7eb'" />
        </div>
        <div>
          <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm password</label>
          <input id="confirm_password" name="confirm_password" type="password" minlength="6" required style="--tw-ring-color: #692f69; border-color: #e5e7eb;" class="mt-1 block w-full px-3 py-2.5 rounded-lg border placeholder-gray-400 focus:outline-none focus:ring-2" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor='#e5e7eb'" />
        </div>
        <div>
          <button type="submit" style="background-color: #692f69; --tw-ring-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'" class="w-full inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg text-white font-medium shadow focus:outline-none focus-visible:ring-2">
            Set password and continue
          </button>
        </div>
      </form>
    <?php endif; ?>

    <div class="mt-6 text-center">
      <a href="/logout.php" class="text-sm text-gray-500 hover:text-gray-700">Not you? Sign out</a>
    </div>
  </div>
</body>
</html>
