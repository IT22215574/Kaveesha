<?php
require_once __DIR__ . '/config.php';
require_login();

// Fetch current user's profile for prefill
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$displayName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : '';
$mobileNumber = '';
if ($userId) {
  try {
    $stmt = db()->prepare('SELECT username, mobile_number FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    if ($row = $stmt->fetch()) {
      if (!empty($row['username'])) $displayName = (string)$row['username'];
      if (!empty($row['mobile_number'])) $mobileNumber = (string)$row['mobile_number'];
    }
  } catch (Throwable $e) {}
}

$flash = '';
$flashType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
  if ($message === '') {
    $flash = 'Please enter a message.';
    $flashType = 'error';
  } else {
    try {
      // Ensure messages table exists (idempotent)
      db()->exec('CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NULL,
        `name` VARCHAR(191) NULL,
        `phone` VARCHAR(32) NULL,
        `message` TEXT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

      $stmt = db()->prepare('INSERT INTO messages (user_id, name, phone, message) VALUES (?, ?, ?, ?)');
      $stmt->execute([$userId ?: null, $displayName ?: null, $mobileNumber ?: null, $message]);
      $flash = 'Your message has been sent to the admin.';
      $flashType = 'success';
    } catch (Throwable $e) {
      $flash = 'Failed to send your message. Please try again later.';
      $flashType = 'error';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Admin • Yoma Electronics</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Kaveesha/assets/css/styles.css">
  <meta name="description" content="Send a message to the Yoma Electronics admin." />
  <meta name="robots" content="noindex, nofollow" />
  <style>
    textarea{min-height:140px}
  </style>
  <script>
    // Optional: character counter
    document.addEventListener('DOMContentLoaded', () => {
      const ta = document.getElementById('message');
      const out = document.getElementById('charCount');
      if (ta && out) {
        const sync = () => { out.textContent = (ta.value || '').length + ' characters'; };
        ta.addEventListener('input', sync); sync();
      }
    });
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 to-gray-50 relative">
  <?php include __DIR__ . '/includes/user_nav.php'; ?>

  <main class="max-w-3xl mx-auto px-4 pt-8 pb-12">
    <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl border border-gray-100 p-6 sm:p-8">
      <h1 class="text-2xl font-semibold text-gray-900">Contact Yoma Electronics</h1>
      <p class="text-gray-600 mt-1">Have a question or need help? Send us a message and we’ll get back to you.</p>

      <?php if ($flash): ?>
        <div class="mt-4 p-3 rounded-md border <?= $flashType==='success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-700' ?>">
          <?= htmlspecialchars($flash) ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" class="mt-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="<?= htmlspecialchars($displayName) ?>" readonly class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 bg-gray-100 text-gray-700"/>
          </div>
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone number</label>
            <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($mobileNumber) ?>" readonly class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 bg-gray-100 text-gray-700"/>
          </div>
        </div>

        <div>
          <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
          <textarea id="message" name="message" required placeholder="Type your message here..." class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          <div id="charCount" class="mt-1 text-xs text-gray-400"></div>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-medium shadow hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            Send message
          </button>
          <a href="/Kaveesha/dashboard.php" class="text-sm text-gray-600 hover:text-gray-800">Back to home</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
