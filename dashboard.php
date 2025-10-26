<?php
require_once __DIR__ . '/config.php';
require_login();
// Fetch live username for greeting
$displayName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : '';
if (!empty($_SESSION['user_id'])) {
  try {
    $stmt = db()->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    if ($row = $stmt->fetch()) {
      if (!empty($row['username'])) $displayName = (string)$row['username'];
    }
  } catch (Throwable $e) {}
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard • Yoma Electronics</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 to-gray-50 relative">
  <?php include __DIR__ . '/includes/user_nav.php'; ?>

  <!-- Main content -->
  <main class="max-w-4xl mx-auto px-4 pt-8 pb-10">
    <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl border border-gray-100 p-8">
      <h2 class="text-2xl font-semibold text-gray-900 mb-2">Welcome, <?= htmlspecialchars($displayName) ?></h2>
      <p class="text-gray-600">This is your dashboard. Add your application pages and quick actions here.</p>
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Example cards/placeholders for future modules -->
        <div class="p-4 rounded-lg border border-gray-200 hover:border-indigo-300 transition">
          <div class="text-sm text-gray-500">Module</div>
          <div class="font-medium text-gray-800">Recent Activity</div>
        </div>
        <div class="p-4 rounded-lg border border-gray-200 hover:border-indigo-300 transition">
          <div class="text-sm text-gray-500">Module</div>
          <div class="font-medium text-gray-800">Quick Links</div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>