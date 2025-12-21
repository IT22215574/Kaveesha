<?php
require_once __DIR__ . '/config.php';
// If already logged in, go to appropriate area
if (!empty($_SESSION['user'])) {
  if (!empty($_SESSION['is_admin'])) {
    if (!empty($_SESSION['is_admin_confirmed'])) {
      header('Location: /admin.php');
    } else {
      header('Location: /admin_confirm.php');
    }
  } else {
    header('Location: /dashboard.php');
  }
  exit;
}

// Handle POST (classic embedded PHP in the same file)
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
  if ($mobile === '') {
    $flash = 'Please enter your mobile number.';
  } else {
    $user = find_user_by_mobile($mobile);
    if ($user) {
      session_regenerate_id(true);
      // Store session details for later use
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['user'] = $user['username'] ?: $user['mobile_number'];
      $_SESSION['is_admin'] = !empty($user['is_admin']);
      // Cache user data on login for better navigation performance
      $_SESSION['cached_username'] = $user['username'] ?: $user['mobile_number'];
      $_SESSION['cached_mobile'] = $user['mobile_number'];
      // Reset any prior admin confirmation state on new login
      unset($_SESSION['is_admin_confirmed']);
      if (!empty($_SESSION['is_admin'])) {
        // For admin via mobile login, require extra confirmation step
        header('Location: /admin_confirm.php');
      } else {
        header('Location: /dashboard.php');
      }
      exit;
    } else {
      $flash = 'Mobile number not found.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center relative" style="background-image: url('/logo/logo2.png'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
  <!-- Brand top-left -->
  <div class="fixed top-4 left-4">
    <a href="/index.php" class="inline-flex items-center gap-2 font-bold tracking-tight" style="color: #692f69;" onmouseover="this.style.color='#7d3a7d'" onmouseout="this.style.color='#692f69'">
      <span class="text-xl">MC YOMA electronic</span>
    </a>
  </div>

  <!-- Auth Card -->
  <div class="max-w-md w-full bg-white/90 backdrop-blur p-8 rounded-xl shadow-xl border border-gray-100">
    <h1 class="text-2xl font-semibold text-gray-900">Welcome back</h1>
    <p class="text-sm text-gray-500 mb-6">Sign in with your mobile number to continue.</p>
    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-700"><?=htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <form action="" method="post" class="space-y-5">
      <div>
        <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile number</label>
        <input id="mobile" name="mobile" type="tel" inputmode="numeric" pattern="[0-9\s+\-()]{7,}" placeholder="e.g., 0712345678" required class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69; --tw-border-opacity: 1;" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor=''" />
        <p class="mt-1 text-xs text-gray-400">We’ll never share your number.</p>
      </div>
      <div>
        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg text-white font-medium shadow focus:outline-none focus-visible:ring-2" style="background-color: #692f69; --tw-ring-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
          Sign in
        </button>
      </div>
    </form>
  </div>
</body>
</html>