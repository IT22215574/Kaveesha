<?php
require_once __DIR__ . '/config.php';
require_admin();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
  $_SESSION['flash'] = 'Invalid user selection.';
  header('Location: /Kaveesha/admin.php');
  exit;
}

// Fetch user to display context
$stmt = db()->prepare('SELECT id, username, mobile_number FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
  $_SESSION['flash'] = 'User not found.';
  header('Location: /Kaveesha/admin.php');
  exit;
}

$flash = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
  $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
  $price = isset($_POST['price']) ? trim((string)$_POST['price']) : '';

  if ($title === '') {
    $flash = 'Please enter a title for the listing.';
  } else {
    try {
      $stmt = db()->prepare('INSERT INTO listings (user_id, title, description, price) VALUES (?, ?, ?, ?)');
      $priceVal = ($price === '' ? null : $price);
      $stmt->execute([$userId, $title, $description, $priceVal]);
      $success = 'Listing added successfully for ' . htmlspecialchars($user['username']);
    } catch (PDOException $e) {
      // 42S02: Base table or view not found (listings table may not exist yet)
      if ($e->getCode() === '42S02') {
        $flash = 'Listings table is missing. Please run the updated setup.sql to create the listings table.';
      } else {
        $flash = 'Failed to add listing. Please try again.';
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
  <title>Add Listing • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .container { max-width: 56rem; }
  </style>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="icon" href="data:,">
  <script>
    // Simple client-side clean-up for price input
    function sanitizePrice(el){
      el.value = el.value.replace(/[^\d.]/g, '').replace(/(\..*)\./g, '$1');
    }
  </script>
  </head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="container mx-auto p-6 space-y-6">
    <a href="/Kaveesha/admin.php" class="inline-flex items-center text-indigo-700 hover:underline">&larr; Back to Users</a>

    <section class="bg-white rounded shadow p-6">
      <h1 class="text-2xl font-semibold mb-2">Add listing</h1>
      <p class="text-gray-600 mb-4">For user <span class="font-medium">#<?= (int)$user['id'] ?> — <?= htmlspecialchars($user['username']) ?></span> (<?= htmlspecialchars($user['mobile_number']) ?>)</p>

      <?php if ($flash): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= $success ?></div>
      <?php endif; ?>

      <form method="post" class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-600">*</span></label>
          <input name="title" required maxlength="191" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Samsung 55\" 4K TV" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea name="description" rows="5" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Details about the product..."></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Price (LKR)</label>
            <input name="price" inputmode="decimal" oninput="sanitizePrice(this)" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g., 129999" />
          </div>
        </div>
        <div class="pt-2">
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Save Listing</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>