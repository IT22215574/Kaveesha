<?php
require_once __DIR__ . '/config.php';
require_admin();

// Search params and validation
$sessionFlash = '';
if (!empty($_SESSION['flash'])) {
  $sessionFlash = (string)$_SESSION['flash'];
  unset($_SESSION['flash']);
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$digits = preg_replace('/\D+/', '', $q);
$hasAlpha = (preg_match('/[A-Za-z]/', $q) === 1);

$where = [];
$params = [];

// Single merged search logic:
// - Exactly 10 digits and no letters => exact mobile match
// - 1-9 digits and no letters       => mobile starts-with match
// - otherwise                        => name partial match
if ($q !== '') {
  if (!$hasAlpha && preg_match('/^\d{10}$/', $digits)) {
    $where[] = 'mobile_number = ?';
    $params[] = $digits;
  } elseif (!$hasAlpha && $digits !== '') {
    $where[] = 'mobile_number LIKE ?';
    $params[] = $digits . '%';
  } else {
    $where[] = 'username LIKE ?';
    $params[] = '%' . $q . '%';
  }
}

// Fetch users list (Users page) with optional filters
$sql = 'SELECT id, username, mobile_number, is_admin, created_at FROM users';
if (!empty($where)) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';

if (!empty($params)) {
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $users = $stmt->fetchAll();
} else {
  $users = db()->query($sql)->fetchAll();
}

// If this is an AJAX request, return just the table rows to avoid page reload and keep focus in the search box
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
  header('Content-Type: text/html; charset=utf-8');
  if (!$users) {
    echo '<tr><td colspan="6" class="py-3 px-4 text-center text-gray-500">No users found.</td></tr>';
    exit;
  }
  foreach ($users as $u) {
    $id = (int)$u['id'];
    $username = htmlspecialchars($u['username']);
    $mobile = htmlspecialchars($u['mobile_number']);
    $role = !empty($u['is_admin']) ? 'Admin' : 'User';
    $created = htmlspecialchars($u['created_at']);
    $isSelf = isset($_SESSION['user_id']) && ((int)$_SESSION['user_id'] === $id);
    echo '<tr class="border-b last:border-0">';
    echo '<td class="py-2 pr-4">' . $id . '</td>';
    echo '<td class="py-2 pr-4">' . $username . '</td>';
    echo '<td class="py-2 pr-4">' . $mobile . '</td>';
    echo '<td class="py-2 pr-4">' . $role . '</td>';
    echo '<td class="py-2 pr-4">' . $created . '</td>';
    echo '<td class="py-2 pr-4">';
    echo '<div class="flex items-center gap-2">';
    echo '<a href="/Kaveesha/admin_user_edit.php?id=' . $id . '" class="inline-block px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>';
    echo '<form action="/Kaveesha/admin_user_delete.php" method="post" onsubmit="return confirm(\'Delete this user? This cannot be undone.\');">';
    echo '<input type="hidden" name="id" value="' . $id . '">';
    $btnClass = $isSelf ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700';
    $disabled = $isSelf ? ' disabled' : '';
    echo '<button type="submit" class="px-3 py-1 rounded ' . $btnClass . '"' . $disabled . '>Delete</button>';
    echo '</form>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
  }
  exit;
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
    <?php if ($sessionFlash): ?>
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded">
        <?= htmlspecialchars($sessionFlash) ?>
      </div>
    <?php endif; ?>
    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Users</h2>
      <form id="userSearchForm" method="get" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1" for="q">Search by name or mobile</label>
        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Type a name or 10-digit mobile..." autocomplete="off" />
      </form>
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
          <tbody id="usersTbody">
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
  <script>
    (function(){
      const form = document.getElementById('userSearchForm');
      const q = document.getElementById('q');
      const tbody = document.getElementById('usersTbody');
      if (!form || !q || !tbody) return;
      let t = null;
      let lastValue = q.value;
      const doFetch = () => {
        const val = q.value;
        if (val === lastValue) return; // avoid duplicate fetches
        lastValue = val;
        const url = new URL(window.location.href);
        if (val) url.searchParams.set('q', val); else url.searchParams.delete('q');
        window.history.replaceState(null, '', url);
        fetch(url.pathname + (url.search || ''), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.text()).then(html => {
          tbody.innerHTML = html;
        }).catch(() => {
          // optional: could show an error state
        });
      };
      const debounced = () => {
        if (t) clearTimeout(t);
        t = setTimeout(doFetch, 350);
      };
      q.addEventListener('input', debounced);

      // Keep focus on load when coming back with q in URL
      window.addEventListener('DOMContentLoaded', () => {
        if (q.value) {
          q.focus();
          // place caret at end
          const len = q.value.length;
          q.setSelectionRange(len, len);
        }
      });
    })();
  </script>
</body>
</html>
