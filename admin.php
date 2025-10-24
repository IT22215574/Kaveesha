<?php
require_once __DIR__ . '/config.php';
require_admin();

// Fetch users list (Users page)
$users = db()->query('SELECT id, username, mobile_number, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
$flash = '';
if (!empty($_SESSION['flash'])) {
  $flash = (string)$_SESSION['flash'];
  unset($_SESSION['flash']);
}
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
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto p-6 space-y-6">
    <?php if ($flash): ?>
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded">
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>
    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Users</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">ID</th>
              <th class="py-2 pr-4">Name</th>
              <th class="py-2 pr-4">Mobile</th>
              <th class="py-2 pr-4">Role</th>
              <th class="py-2 pr-4">Created</th>
              <th class="py-2 pr-4">Actions</th>
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
                <td class="py-2 pr-4">
                  <div class="flex items-center gap-2">
                    <a href="/Kaveesha/admin_user_edit.php?id=<?= (int)$u['id'] ?>" class="inline-block px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>
                    <form action="/Kaveesha/admin_user_delete.php" method="post" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <?php $isSelf = isset($_SESSION['user_id']) && ((int)$_SESSION['user_id'] === (int)$u['id']); ?>
                      <button type="submit" class="px-3 py-1 rounded <?= $isSelf ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700' ?>" <?= $isSelf ? 'disabled' : '' ?>>Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
