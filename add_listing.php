<?php
require_once __DIR__ . '/config.php';
require_admin();

// Helpers for uploads
function uploads_base_dir() { return __DIR__ . '/uploads'; }
function ensure_uploads_dir_writable() {
  $dir = uploads_base_dir();
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException('Uploads directory cannot be created at: ' . $dir);
    }
  }
  if (!is_writable($dir)) {
    @chmod($dir, 0775);
    if (!is_writable($dir)) {
      throw new RuntimeException('Uploads directory is not writable: ' . $dir);
    }
  }
}
function is_allowed_image_ext($ext) {
  $ext = strtolower($ext);
  return in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
}
function move_uploaded_image_and_get_path($file, $listingId) {
  if (!isset($file) || !is_array($file)) return null;
  $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($err !== UPLOAD_ERR_OK) {
    // Map common PHP upload errors to friendly messages
    $messages = [
      UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
      UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
      UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
      UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
    ];
    // Only throw for actual errors (not NO_FILE case handled by caller)
    if ($err !== UPLOAD_ERR_NO_FILE) {
      throw new RuntimeException($messages[$err] ?? ('Upload failed with error code ' . (int)$err));
    }
    return null;
  }
  if (!is_uploaded_file($file['tmp_name'])) return null;
  $size = (int)$file['size'];
  if ($size <= 0 || $size > 5 * 1024 * 1024) { // 5MB limit
    throw new RuntimeException('Image too large (max 5MB).');
  }
  $name = (string)($file['name'] ?? '');
  $ext = pathinfo($name, PATHINFO_EXTENSION);
  if (!is_allowed_image_ext($ext)) {
    throw new RuntimeException('Unsupported image type. Use JPG, PNG, GIF, or WEBP.');
  }
  $ext = strtolower($ext);
  ensure_uploads_dir_writable();
  $safeName = 'listing_' . ((int)$listingId) . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $destRel = 'uploads/' . $safeName;
  $destAbs = uploads_base_dir() . '/' . $safeName;
  if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
    throw new RuntimeException('Failed to save uploaded image.');
  }
  return $destRel;
}
function delete_image_if_safe($imagePath) {
  if (!$imagePath) return;
  $imagePath = (string)$imagePath;
  if (strpos($imagePath, 'uploads/') !== 0) return; // prevent deleting outside uploads
  $full = __DIR__ . '/' . $imagePath;
  if (is_file($full)) @unlink($full);
}

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

// Handle actions
$sessionFlash = '';
if (!empty($_SESSION['flash'])) {
  $sessionFlash = (string)$_SESSION['flash'];
  unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  try {
    if ($action === 'create') {
      $token = $_POST['token'] ?? '';
      if (!consume_token($token, 'create')) {
        $_SESSION['flash'] = 'This form was already submitted or has expired.';
        header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
        exit;
      }
      $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
      $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
      $price = isset($_POST['price']) ? trim((string)$_POST['price']) : '';
      if ($title === '') throw new RuntimeException('Please enter a title for the listing.');
      $priceVal = ($price === '' ? null : $price);
      $stmt = db()->prepare('INSERT INTO listings (user_id, title, description, price) VALUES (?, ?, ?, ?)');
      $stmt->execute([$userId, $title, $description, $priceVal]);
      $newId = (int)db()->lastInsertId();
      // Image optional
      if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $rel = move_uploaded_image_and_get_path($_FILES['image'], $newId);
        if ($rel) {
          $u = db()->prepare('UPDATE listings SET image_path = ? WHERE id = ?');
          $u->execute([$rel, $newId]);
        }
      }
      $_SESSION['flash'] = 'Listing added successfully.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    } elseif ($action === 'update') {
      $listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
      $token = $_POST['token'] ?? '';
      if (!consume_token($token, 'update', $listingId)) {
        $_SESSION['flash'] = 'This form was already submitted or has expired.';
        header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
        exit;
      }
      if ($listingId <= 0) throw new RuntimeException('Invalid listing.');
      $row = db()->prepare('SELECT id, user_id, image_path FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
      $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
      $price = isset($_POST['price']) ? trim((string)$_POST['price']) : '';
      if ($title === '') throw new RuntimeException('Please enter a title for the listing.');
      $priceVal = ($price === '' ? null : $price);
      $stmt = db()->prepare('UPDATE listings SET title = ?, description = ?, price = ? WHERE id = ?');
      $stmt->execute([$title, $description, $priceVal, $listingId]);
      // If new image uploaded, replace and delete old
      if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $rel = move_uploaded_image_and_get_path($_FILES['image'], $listingId);
        if ($rel) {
          $u = db()->prepare('UPDATE listings SET image_path = ? WHERE id = ?');
          $u->execute([$rel, $listingId]);
          delete_image_if_safe($existing['image_path'] ?? null);
        }
      }
      $_SESSION['flash'] = 'Listing updated.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    } elseif ($action === 'delete') {
      $listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
      $token = $_POST['token'] ?? '';
      if (!consume_token($token, 'delete', $listingId)) {
        $_SESSION['flash'] = 'This action was already performed or has expired.';
        header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
        exit;
      }
      if ($listingId <= 0) throw new RuntimeException('Invalid listing.');
      $row = db()->prepare('SELECT id, user_id, image_path FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $del = db()->prepare('DELETE FROM listings WHERE id = ?');
      $del->execute([$listingId]);
      delete_image_if_safe($existing['image_path'] ?? null);
      $_SESSION['flash'] = 'Listing deleted.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    } elseif ($action === 'delete_image') {
      $listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
      $token = $_POST['token'] ?? '';
      if (!consume_token($token, 'delete_image', $listingId)) {
        $_SESSION['flash'] = 'This action was already performed or has expired.';
        header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
        exit;
      }
      if ($listingId <= 0) throw new RuntimeException('Invalid listing.');
      $row = db()->prepare('SELECT id, user_id, image_path FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $u = db()->prepare('UPDATE listings SET image_path = NULL WHERE id = ?');
      $u->execute([$listingId]);
      delete_image_if_safe($existing['image_path'] ?? null);
      $_SESSION['flash'] = 'Image removed.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    }
  } catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
      $sessionFlash = 'Listings table is missing. Please run the updated setup.sql to create the listings table.';
    } else {
      $sessionFlash = 'Operation failed. Please try again.';
    }
  } catch (Throwable $e) {
    $sessionFlash = $e->getMessage();
  }
}

// Fetch listings for the user
$listings = [];
try {
  $s = db()->prepare('SELECT id, title, description, price, image_path, created_at FROM listings WHERE user_id = ? ORDER BY created_at DESC');
  $s->execute([$userId]);
  $listings = $s->fetchAll();
} catch (Throwable $e) {
  // ignore when table missing; UI will show helpful message if needed
}

// --- CSRF + duplicate submission tokens ---
if (!isset($_SESSION['listing_tokens']) || !is_array($_SESSION['listing_tokens'])) {
  $_SESSION['listing_tokens'] = [];
}
// Reset tokens on each GET (fresh page view) to ensure single-use semantics
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $_SESSION['listing_tokens'] = [];
}
// Helper to mint a token and register it
function mint_token($scope, $id = null) {
  $key = $scope . ($id !== null ? ':' . $id : '');
  $token = bin2hex(random_bytes(16));
  $_SESSION['listing_tokens'][$key] = $token;
  return $token;
}
// Helper to validate & consume token
function consume_token($token, $scope, $id = null) {
  $key = $scope . ($id !== null ? ':' . $id : '');
  if (empty($_SESSION['listing_tokens'][$key])) return false;
  if (!hash_equals($_SESSION['listing_tokens'][$key], (string)$token)) return false;
  unset($_SESSION['listing_tokens'][$key]);
  return true;
}

// Prepare tokens for create + per-listing update forms
$createToken = mint_token('create');
$updateTokens = [];
$deleteTokens = [];
$deleteImageTokens = [];
foreach ($listings as $lTok) {
  $lid = (int)$lTok['id'];
  $updateTokens[$lid] = mint_token('update', $lid);
  $deleteTokens[$lid] = mint_token('delete', $lid);
  $deleteImageTokens[$lid] = mint_token('delete_image', $lid);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Listings • Admin • Kaveesha</title>
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
    function openModal(id){ document.getElementById(id)?.classList.remove('hidden'); }
    function closeModal(id){ document.getElementById(id)?.classList.add('hidden'); }
  </script>
  </head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="container mx-auto p-6 space-y-6">
    <a href="/Kaveesha/admin.php" class="inline-flex items-center text-indigo-700 hover:underline">&larr; Back to Users</a>

    <section class="bg-white rounded shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Listings</h1>
          <p class="text-gray-600">For user <span class="font-medium">#<?= (int)$user['id'] ?> — <?= htmlspecialchars($user['username']) ?></span> (<?= htmlspecialchars($user['mobile_number']) ?>)</p>
        </div>
        <button onclick="openModal('createModal')" class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
          <span class="text-lg">＋</span> <span>Add listing</span>
        </button>
      </div>

      <?php if ($sessionFlash): ?>
        <div class="mt-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($sessionFlash) ?></div>
      <?php endif; ?>

      <div class="mt-6 space-y-4">
        <?php if (!$listings): ?>
          <div class="text-gray-500">No listings yet.</div>
        <?php else: ?>
          <?php foreach ($listings as $l): ?>
            <div class="border rounded p-4">
              <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-40 shrink-0">
                  <?php if (!empty($l['image_path'])): ?>
                    <img src="/Kaveesha/<?= htmlspecialchars($l['image_path']) ?>" alt="Listing image" class="w-full h-28 object-cover rounded border" />
                  <?php else: ?>
                    <div class="w-full h-28 flex items-center justify-center bg-gray-100 text-gray-400 border rounded">No image</div>
                  <?php endif; ?>
                </div>
                <div class="flex-1">
                  <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div class="text-lg font-semibold"><?= htmlspecialchars($l['title']) ?></div>
                      <div class="text-gray-500 text-sm">#<?= (int)$l['id'] ?> • <?= htmlspecialchars($l['created_at']) ?></div>
                    </div>
                    <div class="text-indigo-700 font-medium"><?= $l['price'] !== null ? 'LKR ' . htmlspecialchars($l['price']) : '' ?></div>
                  </div>
                  <?php if (!empty($l['description'])): ?>
                    <p class="mt-2 text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($l['description'])) ?></p>
                  <?php endif; ?>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" onclick="document.getElementById('edit-<?= (int)$l['id'] ?>').classList.toggle('hidden')" class="px-3 py-1 rounded border border-indigo-600 text-indigo-700 hover:bg-indigo-50">Edit</button>
                    <form method="post" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                      <input type="hidden" name="token" value="<?= htmlspecialchars($deleteTokens[(int)$l['id']]) ?>" />
                      <button type="submit" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">Delete</button>
                    </form>
                    <?php if (!empty($l['image_path'])): ?>
                      <form method="post" onsubmit="return confirm('Remove the image from this listing?');">
                        <input type="hidden" name="action" value="delete_image" />
                        <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                        <input type="hidden" name="token" value="<?= htmlspecialchars($deleteImageTokens[(int)$l['id']]) ?>" />
                        <button type="submit" class="px-3 py-1 rounded bg-yellow-500 text-white hover:bg-yellow-600">Remove image</button>
                      </form>
                    <?php endif; ?>
                  </div>
                  <div id="edit-<?= (int)$l['id'] ?>" class="mt-4 hidden">
                    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <input type="hidden" name="action" value="update" />
                      <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                      <input type="hidden" name="token" value="<?= htmlspecialchars($updateTokens[(int)$l['id']]) ?>" />
                      <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Title *</label>
                        <input name="title" required maxlength="191" value="<?= htmlspecialchars($l['title']) ?>" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($l['description']) ?></textarea>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Price (LKR)</label>
                        <input name="price" inputmode="decimal" oninput="sanitizePrice(this)" value="<?= htmlspecialchars((string)$l['price']) ?>" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Replace image</label>
                        <input type="file" name="image" accept="image/*" class="mt-1 block w-full" />
                        <div class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, WEBP.</div>
                      </div>
                      <div class="sm:col-span-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Save changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Create Modal -->
      <div id="createModal" class="fixed inset-0 bg-black/40 z-40 hidden" aria-modal="true" role="dialog">
        <div class="absolute inset-0 flex items-start justify-center pt-24 px-4">
          <div class="bg-white rounded shadow-xl w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-semibold">Add listing</h2>
              <button type="button" onclick="closeModal('createModal')" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-4">
              <input type="hidden" name="action" value="create" />
              <input type="hidden" name="token" value="<?= htmlspecialchars($createToken) ?>" />
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
                <div>
                  <label class="block text-sm font-medium text-gray-700">Image</label>
                  <input type="file" name="image" accept="image/*" class="mt-1 block w-full" />
                  <div class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, WEBP.</div>
                </div>
              </div>
              <div class="pt-2 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Save Listing</button>
                <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 border rounded">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>