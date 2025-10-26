<?php
require_once __DIR__ . '/config.php';
// If already logged in, go to appropriate area
if (!empty($_SESSION['user'])) {
  if (!empty($_SESSION['is_admin'])) {
    header('Location: /Kaveesha/admin.php');
  } else {
    header('Location: /Kaveesha/dashboard.php');
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
      if (!empty($_SESSION['is_admin'])) {
        header('Location: /Kaveesha/admin.php');
      } else {
        header('Location: /Kaveesha/dashboard.php');
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
<body class="min-h-screen bg-gradient-to-br from-indigo-50 to-gray-50 flex items-center justify-center relative">
  <!-- Brand top-left -->
  <div class="fixed top-4 left-4">
    <a href="/Kaveesha/index.php" class="inline-flex items-center gap-2 text-indigo-700 hover:text-indigo-900 font-bold tracking-tight">
      <span class="text-xl">Yoma Electronics</span>
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
        <input id="mobile" name="mobile" type="tel" inputmode="numeric" pattern="[0-9\s+\-()]{7,}" placeholder="e.g., 0712345678" required class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
        <p class="mt-1 text-xs text-gray-400">We’ll never share your number.</p>
      </div>
      <div>
        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-medium shadow hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
          Sign in
        </button>
      </div>
    </form>
  </div>
</body>
</html>