<?php
require_once __DIR__ . '/config.php';
require_login();
$user = htmlspecialchars($_SESSION['user']);
$is_admin = !empty($_SESSION['is_admin']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <nav class="bg-white shadow">
    <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
      <div class="text-lg font-semibold">Kaveesha</div>
      <div class="flex items-center space-x-4">
        <?php if ($is_admin): ?>
          <a href="/Kaveesha/admin.php" class="text-sm bg-indigo-600 text-white px-3 py-1 rounded">Admin</a>
        <?php endif; ?>
        <span class="text-sm text-gray-700">Signed in as <strong><?= $user ?></strong></span>
        <a href="/Kaveesha/logout.php" class="text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
      </div>
    </div>
  </nav>
  <main class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-2">Welcome, <?= $user ?></h2>
      <p class="text-gray-700">This is a demo dashboard. Add your application pages here.</p>
    </div>
  </main>
</body>
</html>