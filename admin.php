<?php
require_once __DIR__ . '/config.php';
require_admin();

$flash = '';
$success = '';

// Handle create user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['username']) ? trim($_POST['username']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';

    if ($name === '' || $mobile === '') {
        $flash = 'Please enter both name and mobile number.';
    } elseif (!preg_match('/^[0-9\s+\-()]{7,}$/', $mobile)) {
        $flash = 'Please enter a valid mobile number.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (username, mobile_number) VALUES (?, ?)');
            $stmt->execute([$name, $mobile]);
            $success = 'User created successfully.';
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) { // duplicate entry
                $flash = 'A user with that mobile number or username already exists.';
            } else {
                $flash = 'Failed to create user. Please try again.';
            }
        }
    }
}

// Fetch users list (basic list for visibility)
$users = db()->query('SELECT id, username, mobile_number, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <nav class="bg-white shadow">
    <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between items-center">
      <div class="text-lg font-semibold">Kaveesha — Admin</div>
      <div class="flex items-center space-x-3">
        <a href="/Kaveesha/dashboard.php" class="text-sm bg-gray-200 text-gray-800 px-3 py-1 rounded">Dashboard</a>
        <a href="/Kaveesha/logout.php" class="text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
      </div>
    </div>
  </nav>

  <main class="max-w-5xl mx-auto p-6 space-y-6">
    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Create user</h2>
      <?php if ($flash): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input name="username" required class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Mobile number</label>
          <input name="mobile" type="tel" inputmode="numeric" pattern="[0-9\s+\-()]{7,}" placeholder="e.g., 0771234567" required class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700">Create</button>
        </div>
      </form>
    </section>

    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Recent users</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">ID</th>
              <th class="py-2 pr-4">Name</th>
              <th class="py-2 pr-4">Mobile</th>
              <th class="py-2 pr-4">Role</th>
              <th class="py-2 pr-4">Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr class="border-b last:border-0">
                <td class="py-2 pr-4"><?= (int)$u['id'] ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars($u['username']) ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars($u['mobile_number']) ?></td>
                <td class="py-2 pr-4"><?= !empty($u['is_admin']) ? 'Admin' : 'User' ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars($u['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
