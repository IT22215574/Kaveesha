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
function move_uploaded_image_and_get_path($file, $listingId, $imageIndex = 0) {
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
      UPLOAD_ERR_EXTENSION => 'A PHP extension Returnedthe file upload.'
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
  $safeName = 'listing_' . ((int)$listingId) . '_' . $imageIndex . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $destRel = 'uploads/' . $safeName;
  $destAbs = uploads_base_dir() . '/' . $safeName;
  if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
    throw new RuntimeException('Failed to save uploaded image.');
  }
  return $destRel;
}

function process_multiple_images($files, $listingId) {
  $imagePaths = [];
  if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
    return $imagePaths;
  }
  
  $fileCount = count($files['name']);
  for ($i = 0; $i < min($fileCount, 3); $i++) {
    if (empty($files['name'][$i])) continue;
    
    $file = [
      'name' => $files['name'][$i],
      'type' => $files['type'][$i],
      'tmp_name' => $files['tmp_name'][$i],
      'error' => $files['error'][$i],
      'size' => $files['size'][$i]
    ];
    
    if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
      $imagePath = move_uploaded_image_and_get_path($file, $listingId, $i + 1);
      if ($imagePath) {
        $imagePaths[] = $imagePath;
      }
    }
  }
  return $imagePaths;
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

// Check if a listing was just created
$newListingId = null;
if (!empty($_SESSION['new_listing_id'])) {
  $newListingId = (int)$_SESSION['new_listing_id'];
  unset($_SESSION['new_listing_id']);
}

// Check if consent form exists


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
      $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
      if ($title === '') throw new RuntimeException('Please enter a title for the listing.');
      if (!in_array($status, [1, 2, 3, 4])) $status = 1;
      $stmt = db()->prepare('INSERT INTO listings (user_id, title, description, status) VALUES (?, ?, ?, ?)');
      $stmt->execute([$userId, $title, $description, $status]);
      $newId = (int)db()->lastInsertId();
      
      // Handle individual image uploads
      $updateFields = [];
      $updateValues = [];
      
      for ($i = 1; $i <= 3; $i++) {
        $fieldName = 'image_' . $i;
        if (!empty($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE) {
          $imagePath = move_uploaded_image_and_get_path($_FILES[$fieldName], $newId, $i);
          if ($imagePath) {
            $dbFieldName = $i === 1 ? 'image_path' : 'image_path_' . $i;
            $updateFields[] = $dbFieldName . ' = ?';
            $updateValues[] = $imagePath;
          }
        }
      }
      
      if (!empty($updateFields)) {
        $updateValues[] = $newId;
        $u = db()->prepare('UPDATE listings SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
        $u->execute($updateValues);
      }
      $_SESSION['flash'] = 'Listing added successfully.';
      $_SESSION['new_listing_id'] = $newId;
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
      $row = db()->prepare('SELECT id, user_id, image_path, image_path_2, image_path_3 FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
      $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
      $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
      if ($title === '') throw new RuntimeException('Please enter a title for the listing.');
      if (!in_array($status, [1, 2, 3, 4])) $status = 1;
      $stmt = db()->prepare('UPDATE listings SET title = ?, description = ?, status = ? WHERE id = ?');
      $stmt->execute([$title, $description, $status, $listingId]);
      
      // Handle individual image uploads (only update if new image provided)
      $updateFields = [];
      $updateValues = [];
      
      for ($i = 1; $i <= 3; $i++) {
        $fieldName = 'image_' . $i;
        if (!empty($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE) {
          $dbFieldName = $i === 1 ? 'image_path' : 'image_path_' . $i;
          $oldImagePath = $existing[$dbFieldName] ?? null;
          
          $imagePath = move_uploaded_image_and_get_path($_FILES[$fieldName], $listingId, $i);
          if ($imagePath) {
            // Delete old image if it exists
            if ($oldImagePath) {
              delete_image_if_safe($oldImagePath);
            }
            $updateFields[] = $dbFieldName . ' = ?';
            $updateValues[] = $imagePath;
          }
        }
      }
      
      if (!empty($updateFields)) {
        $updateValues[] = $listingId;
        $u = db()->prepare('UPDATE listings SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
        $u->execute($updateValues);
      }
      
      // Return JSON response for AJAX request
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Listing updated successfully']);
        exit;
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
      $row = db()->prepare('SELECT id, user_id, image_path, image_path_2, image_path_3 FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $del = db()->prepare('DELETE FROM listings WHERE id = ?');
      $del->execute([$listingId]);
      delete_image_if_safe($existing['image_path'] ?? null);
      delete_image_if_safe($existing['image_path_2'] ?? null);
      delete_image_if_safe($existing['image_path_3'] ?? null);
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
      $row = db()->prepare('SELECT id, user_id, image_path, image_path_2, image_path_3 FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $u = db()->prepare('UPDATE listings SET image_path = NULL, image_path_2 = NULL, image_path_3 = NULL WHERE id = ?');
      $u->execute([$listingId]);
      delete_image_if_safe($existing['image_path'] ?? null);
      delete_image_if_safe($existing['image_path_2'] ?? null);
      delete_image_if_safe($existing['image_path_3'] ?? null);
      
      // Return JSON response for AJAX request
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'All images removed successfully']);
        exit;
      }
      
      $_SESSION['flash'] = 'All images removed.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    } elseif ($action === 'delete_single_image') {
      $listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
      $imageIndex = isset($_POST['image_index']) ? (int)$_POST['image_index'] : 0;
      $token = $_POST['token'] ?? '';
      if (!consume_token($token, 'delete_single_image', $listingId . '_' . $imageIndex)) {
        $_SESSION['flash'] = 'This action was already performed or has expired.';
        header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
        exit;
      }
      if ($listingId <= 0 || $imageIndex < 1 || $imageIndex > 3) throw new RuntimeException('Invalid request.');
      $row = db()->prepare('SELECT id, user_id, image_path, image_path_2, image_path_3 FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      
      $fieldName = $imageIndex === 1 ? 'image_path' : 'image_path_' . $imageIndex;
      $imagePath = $existing[$fieldName] ?? null;
      
      if ($imagePath) {
        $u = db()->prepare("UPDATE listings SET $fieldName = NULL WHERE id = ?");
        $u->execute([$listingId]);
        delete_image_if_safe($imagePath);
        $message = "Image $imageIndex removed successfully";
      } else {
        $message = 'Image not found';
      }
      
      // Return JSON response for AJAX request
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
      }
      
      $_SESSION['flash'] = $message;
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    } elseif ($action === 'update_status') {
      $listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
      $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
      if ($listingId <= 0) throw new RuntimeException('Invalid listing.');
      if (!in_array($status, [1, 2, 3, 4])) throw new RuntimeException('Invalid status.');
      $row = db()->prepare('SELECT id, user_id FROM listings WHERE id = ? LIMIT 1');
      $row->execute([$listingId]);
      $existing = $row->fetch();
      if (!$existing || (int)$existing['user_id'] !== $userId) throw new RuntimeException('Listing not found.');
      $stmt = db()->prepare('UPDATE listings SET status = ? WHERE id = ?');
      $stmt->execute([$status, $listingId]);
      
      // If status is set to 4 (Completed & Received Payment), automatically mark the invoice as paid
      if ($status === 4) {
        $updateInvoice = db()->prepare('UPDATE invoices SET status = "paid" WHERE listing_id = ? AND status != "paid"');
        $updateInvoice->execute([$listingId]);
      }
      
      // Return JSON response for AJAX request
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
      }
      $_SESSION['flash'] = 'Status updated successfully.';
      header('Location: /Kaveesha/add_listing.php?user_id=' . $userId);
      exit;
    }
  } catch (PDOException $e) {
    $errorMessage = '';
    if ($e->getCode() === '42S02') {
      $errorMessage = 'Listings table is missing. Please run the updated setup.sql to create the listings table.';
    } else {
      $errorMessage = 'Operation failed. Please try again.';
    }
    
    // Return JSON error for AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMessage]);
      exit;
    }
    
    $sessionFlash = $errorMessage;
  } catch (Throwable $e) {
    $errorMessage = $e->getMessage();
    
    // Return JSON error for AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMessage]);
      exit;
    }
    
    $sessionFlash = $errorMessage;
  }
}

// Fetch listings for the user
$listings = [];
try {
  $s = db()->prepare('SELECT id, title, description, status, image_path, image_path_2, image_path_3, created_at FROM listings WHERE user_id = ? ORDER BY created_at DESC');
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

// Status helper function
function get_status_info($status) {
  $statuses = [
    1 => ['text' => 'Not Finished', 'color' => 'bg-blue-100 text-blue-800', 'badge_color' => 'bg-blue-500'],
    2 => ['text' => 'Returned', 'color' => 'bg-red-100 text-red-800', 'badge_color' => 'bg-red-500'],
    3 => ['text' => 'Finished & Pending Payments', 'color' => 'bg-amber-100 text-amber-800', 'badge_color' => 'bg-amber-500'],
    4 => ['text' => 'Completed & Received Payments', 'color' => 'bg-green-100 text-green-800', 'badge_color' => 'bg-green-500']
  ];
  return $statuses[$status] ?? $statuses[1];
}

// Prepare tokens for create + per-listing update forms
$createToken = mint_token('create');
$updateTokens = [];
$deleteTokens = [];
$deleteImageTokens = [];
$deleteSingleImageTokens = [];
foreach ($listings as $lTok) {
  $lid = (int)$lTok['id'];
  $updateTokens[$lid] = mint_token('update', $lid);
  $deleteTokens[$lid] = mint_token('delete', $lid);
  $deleteImageTokens[$lid] = mint_token('delete_image', $lid);
  $deleteSingleImageTokens[$lid] = [];
  for ($i = 1; $i <= 3; $i++) {
    $deleteSingleImageTokens[$lid][$i] = mint_token('delete_single_image', $lid . '_' . $i);
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Listings • Admin </title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .container { max-width: 56rem; }
    .progress-bar {
      width: 0%;
      height: 100%;
      background-color: #4f46e5;
      transition: width 0.3s ease;
      border-radius: 4px;
    }
    .image-preview {
      position: relative;
      display: inline-block;
    }
    .remove-image {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ef4444;
      color: white;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 12px;
    }
    .has-unsaved-changes {
      border-left: 4px solid #f59e0b;
      background-color: #fffbeb;
    }
  </style>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="icon" href="data:,">
  <script>
    function openModal(id){ document.getElementById(id)?.classList.remove('hidden'); }
    function closeModal(id){ document.getElementById(id)?.classList.add('hidden'); }
    
    // Status update functionality
    function updateStatus(form) {
      const listingId = form.dataset.listingId;
      const status = form.querySelector('select[name="status"]').value;
      const select = form.querySelector('select');
      
      // Update select styling based on status
      const statusColors = {
        '1': 'bg-blue-100 text-blue-800',
        '2': 'bg-red-100 text-red-800',
        '3': 'bg-amber-100 text-amber-800',
        '4': 'bg-green-100 text-green-800'
      };
      
      // Remove old color classes
      select.className = select.className.replace(/bg-(blue|red|amber|green)-(100|800)/g, '');
      // Add new color classes
      select.className += ' ' + statusColors[status];
      
      // Send AJAX request
      const formData = new FormData(form);
      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Show temporary success message
          showStatusMessage('Status updated successfully', 'success');
        } else {
          showStatusMessage('Failed to update status', 'error');
        }
      })
      .catch(error => {
        console.error('Error updating status:', error);
        showStatusMessage('Failed to update status', 'error');
      });
    }
    
    function showStatusMessage(message, type) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `fixed top-4 right-4 px-4 py-2 rounded shadow z-50 ${
        type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
      }`;
      messageDiv.textContent = message;
      document.body.appendChild(messageDiv);
      
      setTimeout(() => {
        messageDiv.remove();
      }, 2000);
    }
    
    // Individual image preview
    function previewSingleImage(input, index) {
      const file = input.files[0];
      const previewContainer = document.querySelector(`.preview-${index}`);
      
      if (!previewContainer) return;
      
      previewContainer.innerHTML = '';
      
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const preview = document.createElement('div');
          preview.className = 'image-preview relative inline-block mt-1';
          preview.innerHTML = `
            <img src="${e.target.result}" alt="Preview ${index + 1}" class="w-full h-16 object-cover rounded border" />
            <span class="remove-image absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center cursor-pointer" onclick="removeSingleImagePreview(this, ${index})">&times;</span>
          `;
          previewContainer.appendChild(preview);
        };
        reader.readAsDataURL(file);
      }
    }
    
    function removeSingleImagePreview(element, index) {
      const preview = element.closest('.image-preview');
      const input = document.querySelector(`input[name="image_${index + 1}"]`);
      input.value = '';
      preview.remove();
    }
    
    function simulateUploadProgress(form) {
      const progressContainer = form.querySelector('.upload-progress');
      const progressBar = form.querySelector('.progress-bar');
      const progressText = form.querySelector('.progress-text');
      
      if (!progressContainer || !progressBar) return;
      
      progressContainer.classList.remove('hidden');
      let progress = 0;
      
      const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90;
        
        progressBar.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
        
        if (progress >= 90) {
          clearInterval(interval);
          progressText.textContent = 'Processing...';
        }
      }, 200);
    }
    
    function handleFormSubmit(form) {
      // Check if this is an update form
      const action = form.querySelector('input[name="action"]').value;
      const listingId = form.querySelector('input[name="listing_id"]')?.value;
      
      if (action === 'update' && listingId) {
        // Handle update via AJAX
        submitFormAjax(form);
        return false; // Prevent default form submission
      }
      
      // For create forms, use normal submission
      const imageInputs = form.querySelectorAll('input[type="file"][name^="image_"]');
      let hasImages = false;
      
      imageInputs.forEach(input => {
        if (input.files.length > 0) {
          hasImages = true;
        }
      });
      
      if (hasImages) {
        simulateUploadProgress(form);
      }
      
      return true; // Allow normal form submission
    }
    
    function submitFormAjax(form) {
      const formData = new FormData(form);
      const listingId = form.querySelector('input[name="listing_id"]').value;
      
      // Show progress if there are images
      const imageInputs = form.querySelectorAll('input[type="file"][name^="image_"]');
      let hasImages = false;
      imageInputs.forEach(input => {
        if (input.files.length > 0) {
          hasImages = true;
        }
      });
      
      if (hasImages) {
        simulateUploadProgress(form);
      }
      
      // Disable form during submission
      const submitButton = form.querySelector('button[type="submit"]');
      const originalText = submitButton.textContent;
      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showStatusMessage(data.message, 'success');
          // Update the stored original values to current form values
          updateOriginalValuesAfterSave(listingId);
          // Remove visual indicator since changes are now saved
          updateUnsavedChangesIndicator(listingId);
          // Clear any file input previews since they're now saved
          clearFileInputPreviews(listingId);
          // No page reload - keep user in edit mode
        } else {
          showStatusMessage(data.message || 'Failed to update listing', 'error');
        }
      })
      .catch(error => {
        console.error('Error updating listing:', error);
        showStatusMessage('Failed to update listing', 'error');
      })
      .finally(() => {
        // Re-enable form
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        // Hide progress bar
        const progressContainer = form.querySelector('.upload-progress');
        if (progressContainer) {
          progressContainer.classList.add('hidden');
        }
      });
    }
    
    // Track original form values for unsaved changes detection
    window.originalFormValues = {};
    
    function storeOriginalValues(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form) return;
      
      const values = {};
      const inputs = form.querySelectorAll('input[name="title"], textarea[name="description"], select[name="status"]');
      inputs.forEach(input => {
        values[input.name] = input.value;
      });
      
      window.originalFormValues[listingId] = values;
    }
    
    function hasUnsavedChanges(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form || !window.originalFormValues[listingId]) return false;
      
      const originalValues = window.originalFormValues[listingId];
      const inputs = form.querySelectorAll('input[name="title"], textarea[name="description"], select[name="status"]');
      
      for (const input of inputs) {
        if (input.value !== originalValues[input.name]) {
          return true;
        }
      }
      
      // Check if any file inputs have files selected
      const fileInputs = form.querySelectorAll('input[type="file"]');
      for (const fileInput of fileInputs) {
        if (fileInput.files.length > 0) {
          return true;
        }
      }
      
      return false;
    }
    
    function restoreOriginalValues(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form || !window.originalFormValues[listingId]) return;
      
      const originalValues = window.originalFormValues[listingId];
      const inputs = form.querySelectorAll('input[name="title"], textarea[name="description"], select[name="status"]');
      
      inputs.forEach(input => {
        input.value = originalValues[input.name];
      });
      
      // Clear any file inputs and their previews
      const fileInputs = form.querySelectorAll('input[type="file"]');
      fileInputs.forEach(input => {
        input.value = '';
      });
      
      // Clear image previews
      for (let i = 0; i < 3; i++) {
        const previewContainer = form.querySelector(`.preview-${i}`);
        if (previewContainer) {
          previewContainer.innerHTML = '';
        }
      }
      
      // Remove unsaved changes indicator
      const editDiv = document.getElementById(`edit-${listingId}`);
      if (editDiv) {
        editDiv.classList.remove('has-unsaved-changes');
      }
    }
    
    function closeEditForm(listingId) {
      if (hasUnsavedChanges(listingId)) {
        openUnsavedChangesModal(listingId);
      } else {
        const editDiv = document.getElementById(`edit-${listingId}`);
        editDiv.classList.add('hidden');
        editDiv.classList.remove('has-unsaved-changes');
        // Clean up stored values
        if (window.originalFormValues[listingId]) {
          delete window.originalFormValues[listingId];
        }
      }
    }
    
    function openUnsavedChangesModal(listingId) {
      const modal = document.getElementById('unsavedChangesModal');
      if (modal) {
        modal.dataset.listingId = listingId;
        modal.classList.remove('hidden');
      }
    }
    
    function closeUnsavedChangesModal() {
      const modal = document.getElementById('unsavedChangesModal');
      if (modal) {
        modal.classList.add('hidden');
        delete modal.dataset.listingId;
      }
    }
    
    function continueWithoutSaving() {
      const modal = document.getElementById('unsavedChangesModal');
      const listingId = modal ? modal.dataset.listingId : null;
      
      if (listingId) {
        restoreOriginalValues(listingId);
        document.getElementById(`edit-${listingId}`).classList.add('hidden');
        
        // Clean up stored values
        if (window.originalFormValues[listingId]) {
          delete window.originalFormValues[listingId];
        }
      }
      
      closeUnsavedChangesModal();
    }
    
    // Override the existing edit button functionality to store original values
    function toggleEditForm(listingId) {
      const editDiv = document.getElementById(`edit-${listingId}`);
      if (editDiv.classList.contains('hidden')) {
        storeOriginalValues(listingId);
        editDiv.classList.remove('hidden');
        setupChangeTracking(listingId);
      } else {
        closeEditForm(listingId);
      }
    }
    
    function setupChangeTracking(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form) return;
      
      const inputs = form.querySelectorAll('input[name="title"], textarea[name="description"], select[name="status"], input[type="file"]');
      
      inputs.forEach(input => {
        const eventType = input.type === 'file' ? 'change' : 'input';
        input.addEventListener(eventType, () => {
          updateUnsavedChangesIndicator(listingId);
        });
      });
    }
    
    function updateUnsavedChangesIndicator(listingId) {
      const editDiv = document.getElementById(`edit-${listingId}`);
      if (!editDiv) return;
      
      if (hasUnsavedChanges(listingId)) {
        editDiv.classList.add('has-unsaved-changes');
      } else {
        editDiv.classList.remove('has-unsaved-changes');
      }
    }
    
    function deleteSingleImage(listingId, imageIndex, token) {
      if (!confirm(`Remove image ${imageIndex}?`)) return;
      
      const formData = new FormData();
      formData.append('action', 'delete_single_image');
      formData.append('listing_id', listingId);
      formData.append('image_index', imageIndex);
      formData.append('token', token);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showStatusMessage(data.message, 'success');
          // Update the UI to reflect image deletion without page reload
          updateImageDisplayAfterDeletion(listingId, imageIndex);
        } else {
          showStatusMessage(data.message || 'Failed to delete image', 'error');
        }
      })
      .catch(error => {
        console.error('Error deleting image:', error);
        showStatusMessage('Failed to delete image', 'error');
      });
    }
    
    function deleteAllImages(listingId, token) {
      if (!confirm('Remove ALL images from this listing?')) return;
      
      const formData = new FormData();
      formData.append('action', 'delete_image');
      formData.append('listing_id', listingId);
      formData.append('token', token);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showStatusMessage(data.message, 'success');
          // Update the UI to reflect all images deletion without page reload
          updateImageDisplayAfterDeletion(listingId, 'all');
        } else {
          showStatusMessage(data.message || 'Failed to delete images', 'error');
        }
      })
      .catch(error => {
        console.error('Error deleting images:', error);
        showStatusMessage('Failed to delete images', 'error');
      });
    }
    
    function updateImageDisplayAfterDeletion(listingId, imageIndex) {
      // Find the listing container
      const listingContainers = document.querySelectorAll('.border.rounded.p-4');
      let targetContainer = null;
      
      for (const container of listingContainers) {
        const editForm = container.querySelector(`#edit-${listingId}`);
        if (editForm) {
          targetContainer = container;
          break;
        }
      }
      
      if (!targetContainer) return;
      
      // Find the image display area
      const imageGrid = targetContainer.querySelector('.grid.grid-cols-3.gap-1');
      if (!imageGrid) return;
      
      if (imageIndex === 'all') {
        // Hide all delete buttons and show placeholder for all images
        const imageContainers = imageGrid.querySelectorAll('.relative');
        imageContainers.forEach((container, index) => {
          container.innerHTML = `<div class="w-full h-16 bg-gray-100 border border-dashed rounded flex items-center justify-center text-xs text-gray-400">${index + 1}</div>`;
        });
        
        // Hide all individual delete buttons
        const deleteButtons = targetContainer.querySelectorAll('button[onclick*="deleteSingleImage"]');
        deleteButtons.forEach(btn => btn.style.display = 'none');
        
        // Hide "Del All" button
        const delAllButton = targetContainer.querySelector('button[onclick*="deleteAllImages"]');
        if (delAllButton) delAllButton.style.display = 'none';
      } else {
        // Hide specific image and its delete button
        const specificImageContainer = imageGrid.children[imageIndex - 1];
        if (specificImageContainer) {
          specificImageContainer.innerHTML = `<div class="w-full h-16 bg-gray-100 border border-dashed rounded flex items-center justify-center text-xs text-gray-400">${imageIndex}</div>`;
        }
        
        // Hide the specific delete button
        const deleteButton = targetContainer.querySelector(`button[onclick*="deleteSingleImage(${listingId}, ${imageIndex}"]`);
        if (deleteButton) {
          deleteButton.style.display = 'none';
        }
        
        // Check if all images are deleted, if so hide "Del All" button
        const remainingImages = targetContainer.querySelectorAll('button[onclick*="deleteSingleImage"]:not([style*="display: none"])');
        if (remainingImages.length === 0) {
          const delAllButton = targetContainer.querySelector('button[onclick*="deleteAllImages"]');
          if (delAllButton) delAllButton.style.display = 'none';
        }
      }
    }
    
    function updateOriginalValuesAfterSave(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form) return;
      
      // Update the stored original values with current form values
      const values = {};
      const inputs = form.querySelectorAll('input[name="title"], textarea[name="description"], select[name="status"]');
      inputs.forEach(input => {
        values[input.name] = input.value;
      });
      
      window.originalFormValues[listingId] = values;
    }
    
    function clearFileInputPreviews(listingId) {
      const form = document.querySelector(`#edit-${listingId} form`);
      if (!form) return;
      
      // Clear file inputs (they're now saved)
      const fileInputs = form.querySelectorAll('input[type="file"]');
      fileInputs.forEach(input => {
        input.value = '';
      });
      
      // Clear image previews
      for (let i = 0; i < 3; i++) {
        const previewContainer = form.querySelector(`.preview-${i}`);
        if (previewContainer) {
          previewContainer.innerHTML = '';
        }
      }
    }
  </script>
  </head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="container mx-auto p-6 space-y-6">
    <a href="/admin.php" class="inline-flex items-center hover:underline" style="color: #692f69;">&larr; Back to Users</a>

    <section class="bg-white rounded shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Listings</h1>
          <p class="text-gray-600">For user <span class="font-medium">#<?= (int)$user['id'] ?> — <?= htmlspecialchars($user['username']) ?></span> (<?= htmlspecialchars($user['mobile_number']) ?>)</p>
        </div>
        <button onclick="openModal('createModal')" class="inline-flex items-center gap-2 px-3 py-2 text-white rounded" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
          <span class="text-lg">＋</span> <span>Add listing</span>
        </button>
      </div>

      <?php if ($sessionFlash): ?>
        <div class="mt-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($sessionFlash) ?></div>
      <?php endif; ?>

      <?php if ($newListingId): ?>
        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-blue-900">Consent Form Ready</h3>
              <p class="text-sm text-blue-700 mt-1">Listing #<?= (int)$newListingId ?> has been created. Download the consent form for this customer.</p>
              <div class="mt-3 flex gap-2">
                <a href="/consent_form_pdf.php?listing_id=<?= (int)$newListingId ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                  </svg>
                  Download Consent Form (PDF)
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php elseif ($newListingId && !$consentFormExists): ?>
        <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-yellow-900">Consent Form Not Available</h3>
              <p class="text-sm text-yellow-700 mt-1">Listing #<?= (int)$newListingId ?> has been created, but no consent form template is uploaded.</p>
              <div class="mt-3">
                <!-- Upload Consent Form link removed -->
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="mt-6 space-y-4">
        <?php if (!$listings): ?>
          <div class="text-gray-500">No listings yet.</div>
        <?php else: ?>
          <?php foreach ($listings as $l): ?>
            <div class="border rounded p-4">
              <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48 shrink-0">
                  <?php 
                  $images = [
                    $l['image_path'] ?? null,
                    $l['image_path_2'] ?? null,
                    $l['image_path_3'] ?? null
                  ];
                  $hasImages = array_filter($images);
                  ?>
                  <?php if (!empty($hasImages)): ?>
                    <div class="grid grid-cols-3 gap-1">
                      <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="relative">
                          <?php if (!empty($images[$i])): ?>
                            <img src="/Kaveesha/<?= htmlspecialchars($images[$i]) ?>" alt="Image <?= $i + 1 ?>" class="w-full h-16 object-cover rounded border" />
                            <div class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"><?= $i + 1 ?></div>
                          <?php else: ?>
                            <div class="w-full h-16 bg-gray-100 border border-dashed rounded flex items-center justify-center text-xs text-gray-400"><?= $i + 1 ?></div>
                          <?php endif; ?>
                        </div>
                      <?php endfor; ?>
                    </div>
                  <?php else: ?>
                    <div class="grid grid-cols-3 gap-1">
                      <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="w-full h-16 bg-gray-100 border border-dashed rounded flex items-center justify-center text-xs text-gray-400"><?= $i + 1 ?></div>
                      <?php endfor; ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="flex-1">
                  <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div class="text-lg font-semibold"><?= htmlspecialchars($l['title']) ?></div>
                      <div class="text-gray-500 text-sm">#<?= (int)$l['id'] ?> • <?= htmlspecialchars($l['created_at']) ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                      <?php $statusInfo = get_status_info($l['status']); ?>
                      <form method="post" class="inline-flex items-center gap-1 status-form" data-listing-id="<?= (int)$l['id'] ?>">
                        <input type="hidden" name="action" value="update_status" />
                        <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                        <select name="status" class="text-sm px-2 py-1 border rounded <?= $statusInfo['color'] ?>" onchange="updateStatus(this.form)" style="min-width: 180px;">
                          <option value="1" <?= $l['status'] == 1 ? 'selected' : '' ?>>Not Finished</option>
                          <option value="2" <?= $l['status'] == 2 ? 'selected' : '' ?>>Returned</option>
                          <option value="3" <?= $l['status'] == 3 ? 'selected' : '' ?>>Finished & Pending Payments</option>
                          <option value="4" <?= $l['status'] == 4 ? 'selected' : '' ?>>Completed & Received Payments</option>
                        </select>
                      </form>
                    </div>
                  </div>
                  <?php if (!empty($l['description'])): ?>
                    <p class="mt-2 text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($l['description'])) ?></p>
                  <?php endif; ?>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" onclick="toggleEditForm(<?= (int)$l['id'] ?>)" class="px-3 py-1 rounded border hover:bg-purple-50" style="border-color: #692f69; color: #692f69;">Edit</button>
                    <form method="post" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                      <input type="hidden" name="token" value="<?= htmlspecialchars($deleteTokens[(int)$l['id']]) ?>" />
                      <button type="submit" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">Delete</button>
                    </form>
                    <?php if (!empty($hasImages)): ?>
                      <div class="flex flex-wrap gap-1">
                        <?php for ($imgIdx = 1; $imgIdx <= 3; $imgIdx++): ?>
                          <?php $imgField = $imgIdx === 1 ? 'image_path' : 'image_path_' . $imgIdx; ?>
                          <?php if (!empty($l[$imgField])): ?>
                            <button type="button" onclick="deleteSingleImage(<?= (int)$l['id'] ?>, <?= $imgIdx ?>, '<?= htmlspecialchars($deleteSingleImageTokens[(int)$l['id']][$imgIdx]) ?>')" class="px-2 py-1 text-xs rounded bg-yellow-500 text-white hover:bg-yellow-600">Del <?= $imgIdx ?></button>
                          <?php endif; ?>
                        <?php endfor; ?>
                        <button type="button" onclick="deleteAllImages(<?= (int)$l['id'] ?>, '<?= htmlspecialchars($deleteImageTokens[(int)$l['id']]) ?>')" class="px-2 py-1 text-xs rounded bg-red-500 text-white hover:bg-red-600">Del All</button>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="mt-3 flex justify-end items-center space-x-2">
                    <a href="/consent_form_pdf.php?listing_id=<?= (int)$l['id'] ?>" target="_blank" class="px-4 py-2 text-sm rounded bg-purple-700 text-white hover:bg-purple-800 inline-block font-medium">Download Consent Form</a>
                    <a href="create_invoice.php?listing_id=<?= (int)$l['id'] ?>" class="px-4 py-2 text-sm rounded bg-green-600 text-white hover:bg-green-700 inline-block font-medium">Invoice</a>
                  </div>
                  <div id="edit-<?= (int)$l['id'] ?>" class="mt-4 hidden">
                    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4" onsubmit="return handleFormSubmit(this)">
                      <input type="hidden" name="action" value="update" />
                      <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>" />
                      <input type="hidden" name="token" value="<?= htmlspecialchars($updateTokens[(int)$l['id']]) ?>" />
                      <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Title *</label>
                        <input name="title" required maxlength="191" value="<?= htmlspecialchars($l['title']) ?>" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69" />
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69"><?= htmlspecialchars($l['description']) ?></textarea>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69">
                          <option value="1" <?= $l['status'] == 1 ? 'selected' : '' ?>>Not Finished</option>
                          <option value="2" <?= $l['status'] == 2 ? 'selected' : '' ?>>Returned</option>
                          <option value="3" <?= $l['status'] == 3 ? 'selected' : '' ?>>Finished & Pending Payments</option>
                          <option value="4" <?= $l['status'] == 4 ? 'selected' : '' ?>>Completed & Received Payments</option>
                        </select>
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Images (up to 3)</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <?php 
                          $currentImages = [
                            $l['image_path'] ?? null,
                            $l['image_path_2'] ?? null,
                            $l['image_path_3'] ?? null
                          ];
                          ?>
                          <?php for ($imgIndex = 0; $imgIndex < 3; $imgIndex++): ?>
                            <div class="image-slot">
                              <label class="block text-xs font-medium text-gray-600 mb-1">Image <?= $imgIndex + 1 ?></label>
                              <?php if (!empty($currentImages[$imgIndex])): ?>
                                <div class="relative mb-2">
                                  <img src="/Kaveesha/<?= htmlspecialchars($currentImages[$imgIndex]) ?>" alt="Current image <?= $imgIndex + 1 ?>" class="w-full h-20 object-cover rounded border" />
                                  <div class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full px-1">Current</div>
                                </div>
                              <?php endif; ?>
                              <input type="file" name="image_<?= $imgIndex + 1 ?>" accept="image/*" class="block w-full text-sm border rounded px-2 py-1" onchange="previewSingleImage(this, <?= $imgIndex ?>)" />
                              <div class="preview-<?= $imgIndex ?> mt-1"></div>
                            </div>
                          <?php endfor; ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-2">Max 5MB per image. JPG, PNG, GIF, WEBP. Leave empty to keep current image.</div>
                        <div class="upload-progress mt-2 hidden">
                          <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                            <span>Uploading images...</span>
                            <span class="progress-text">0%</span>
                          </div>
                          <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="progress-bar"></div>
                          </div>
                        </div>
                      </div>
                      <div class="sm:col-span-2 flex gap-2">
                        <button type="submit" class="px-4 py-2 text-white rounded" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">Save changes</button>
                        <button type="button" onclick="closeEditForm(<?= (int)$l['id'] ?>)" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">Close</button>
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
            <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-4" onsubmit="return handleFormSubmit(this)">
              <input type="hidden" name="action" value="create" />
              <input type="hidden" name="token" value="<?= htmlspecialchars($createToken) ?>" />
              <div>
                <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-600">*</span></label>
                <input name="title" required maxlength="191" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69" placeholder="e.g., Samsung 55\" 4K TV" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="5" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69" placeholder="Details about the product..."></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-2" style="--tw-ring-color: #692f69">
                  <option value="1" selected>Not Finished</option>
                  <option value="2">Returned</option>
                  <option value="3">Finished & Pending Payments</option>
                  <option value="4">Completed & Received Payments</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Images (up to 3)</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <?php for ($imgIndex = 0; $imgIndex < 3; $imgIndex++): ?>
                    <div class="image-slot">
                      <label class="block text-xs font-medium text-gray-600 mb-1">Image <?= $imgIndex + 1 ?></label>
                      <input type="file" name="image_<?= $imgIndex + 1 ?>" accept="image/*" class="block w-full text-sm border rounded px-2 py-1" onchange="previewSingleImage(this, <?= $imgIndex ?>)" />
                      <div class="preview-<?= $imgIndex ?> mt-1"></div>
                    </div>
                  <?php endfor; ?>
                </div>
                <div class="text-xs text-gray-500 mt-2">Max 5MB per image. JPG, PNG, GIF, WEBP.</div>
              </div>
              <div class="upload-progress mt-4 hidden">
                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                  <span>Uploading images...</span>
                  <span class="progress-text">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div class="progress-bar"></div>
                </div>
              </div>
              <div class="pt-2 flex gap-2">
                <button type="submit" class="px-4 py-2 text-white rounded" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">Save Listing</button>
                <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 border rounded">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Unsaved Changes Confirmation Modal -->
      <div id="unsavedChangesModal" class="fixed inset-0 bg-black/40 z-50 hidden" aria-modal="true" role="dialog">
        <div class="absolute inset-0 flex items-center justify-center px-4">
          <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Unsaved Changes</h2>
              <button type="button" onclick="closeUnsavedChangesModal()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <div class="mb-6">
              <p class="text-sm text-gray-600">There are unsaved changes. If not saved and you press continue, the changes will disappear.</p>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" onclick="closeUnsavedChangesModal()" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded">Cancel</button>
              <button type="button" onclick="continueWithoutSaving()" class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded">Continue</button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>