<?php
require_once __DIR__ . '/config.php';
// If already logged in, go to dashboard
if (!empty($_SESSION['user'])) {
  header('Location: /Kaveesha/dashboard.php');
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
      header('Location: /Kaveesha/dashboard.php');
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
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="max-w-md w-full bg-white p-8 rounded shadow">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Sign in with your mobile</h1>
    <?php if ($flash): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?=htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <form action="" method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Mobile number</label>
        <input name="mobile" type="tel" inputmode="numeric" pattern="[0-9\s+\-()]{7,}" placeholder="e.g., 0712345678" required class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
      </div>
      <div>
        <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700">Sign in</button>
      </div>
    </form>
    <p class="mt-4 text-sm text-gray-600">Demo: try <strong>0712345678</strong>.</p>
  </div>
</body>
</html>