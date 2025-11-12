<?php
require_once __DIR__ . '/config.php';
require_admin();

$flash = '';
$success = '';

// Handle create user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = isset($_POST['username']) ? trim($_POST['username']) : '';
  $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
  $mobileDigits = preg_replace('/\D+/', '', $mobile);

  if ($name === '' || $mobile === '') {
        $flash = 'Please enter both name and mobile number.';
  } elseif (!preg_match('/^\d{10}$/', $mobileDigits)) {
    $flash = 'Please enter a valid 10-digit mobile number.';
  } else {
    // Proactive duplicate checks before attempting insert
    try {
      // Check mobile number uniqueness
      $dupMobile = db()->prepare('SELECT id FROM users WHERE mobile_number = ? LIMIT 1');
      $dupMobile->execute([$mobileDigits]);
      if ($dupMobile->fetch()) {
        $flash = 'An account with that mobile number already exists.';
      } else {
        // Check username uniqueness (optional but keeps clarity in messaging)
        $dupUser = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $dupUser->execute([$name]);
        if ($dupUser->fetch()) {
          $flash = 'A user with that username already exists.';
        } else {
          // Safe to insert
          $stmt = db()->prepare('INSERT INTO users (username, mobile_number) VALUES (?, ?)');
          $stmt->execute([$name, $mobileDigits]);
          $success = 'User created successfully.';
        }
      }
    } catch (PDOException $e) {
      // Fallback: rely on DB unique constraints just in case of race condition
      if ((int)$e->errorInfo[1] === 1062) { // duplicate entry
        $flash = 'Duplicate entry detected. Please use a unique mobile number and username.';
      } else {
        $flash = 'Failed to create user. Please try again.';
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
  <title>Create User • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="max-w-3xl mx-auto p-6 space-y-6">
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
          <input name="mobile" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" oninput="this.value=this.value.replace(/\D+/g,'').slice(0,10)" title="Enter exactly 10 digits" placeholder="e.g., 0771234567" required class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700">Create</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
