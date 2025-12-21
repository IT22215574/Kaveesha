<?php
require_once __DIR__ . '/config.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
}

if ($id <= 0) {
    $_SESSION['flash'] = 'Invalid user.';
    header('Location: /admin.php');
    exit;
}

// Fetch user
$stmt = db()->prepare('SELECT id, username, mobile_number, is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION['flash'] = 'User not found.';
    header('Location: /admin.php');
    exit;
}

$flash = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = isset($_POST['username']) ? trim($_POST['username']) : '';
  $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
  $mobileDigits = preg_replace('/\D+/', '', $mobile);

  if ($name === '' || $mobile === '') {
    $flash = 'Please enter both name and mobile number.';
  } elseif (!preg_match('/^\d{10}$/', $mobileDigits)) {
    $flash = 'Please enter a valid 10-digit mobile number.';
  } else {
        try {
      $stmt = db()->prepare('UPDATE users SET username = ?, mobile_number = ? WHERE id = ?');
      $stmt->execute([$name, $mobileDigits, $id]);
            $_SESSION['flash'] = 'User updated successfully.';
            header('Location: /admin.php');
            exit;
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) { // duplicate entry
                $flash = 'A user with that mobile number or username already exists.';
            } else {
                $flash = 'Failed to update user. Please try again.';
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
  <title>Edit User • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="max-w-3xl mx-auto p-6 space-y-6">
    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Edit user #<?= (int)$user['id'] ?></h2>
      <?php if ($flash): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
        <div>
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input name="username" value="<?= htmlspecialchars($user['username']) ?>" required style="--tw-ring-color: #692f69;" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Mobile number</label>
          <input name="mobile" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" oninput="this.value=this.value.replace(/\D+/g,'').slice(0,10)" title="Enter exactly 10 digits" value="<?= htmlspecialchars($user['mobile_number']) ?>" required style="--tw-ring-color: #692f69;" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2" />
        </div>
        <div class="flex items-end">
          <button type="submit" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'" class="w-full py-2 px-4 text-white rounded">Save</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
