<?php
require_once __DIR__ . '/config.php';

// If already logged in, redirect to appropriate area
if (!empty($_SESSION['user'])) {
  if (!empty($_SESSION['is_admin'])) {
    header('Location: /admin.php');
  } else {
    header('Location: /dashboard.php');
  }
  exit;
}

$flash = '';
$success = '';

// Handle registration request form submission
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
      // Check if mobile number already exists in users table
      $existingUser = db()->prepare('SELECT id FROM users WHERE mobile_number = ? LIMIT 1');
      $existingUser->execute([$mobileDigits]);
      if ($existingUser->fetch()) {
        $flash = 'An account with this mobile number already exists. Please try logging in.';
      } else {
        // Check if there's already ANY request with this mobile number (regardless of status)
        // Only allow if admin has deleted the previous request
        $existingRequest = db()->prepare('SELECT id FROM user_registration_requests WHERE mobile_number = ? LIMIT 1');
        $existingRequest->execute([$mobileDigits]);
        if ($existingRequest->fetch()) {
          $flash = 'A registration request with this mobile number has already been submitted. Only one request per mobile number is allowed.';
        } else {
          // Check if there's already a request with this username
          $existingUsername = db()->prepare('SELECT id FROM user_registration_requests WHERE username = ? LIMIT 1');
          $existingUsername->execute([$name]);
          if ($existingUsername->fetch()) {
            $flash = 'A registration request with this username has already been submitted.';
          } else {
            // Create new registration request and redirect to login
            $stmt = db()->prepare('INSERT INTO user_registration_requests (username, mobile_number, status) VALUES (?, ?, "pending")');
            $stmt->execute([$name, $mobileDigits]);
            // Redirect to login page after successful submission
            header('Location: /login.php');
            exit;
          }
        }
      }
    } catch (PDOException $e) {
      $flash = 'Failed to submit registration request. Please try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Request Account • mctronicservice</title>
  <link rel="icon" type="image/png" href="/logo/logo1.png">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center relative" style="background-image: url('/logo/logo2.png'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
  <!-- Brand top-left -->
  <div class="fixed top-4 left-4">
    <a href="/index.php" class="inline-flex items-center gap-2 font-bold tracking-tight" style="color: #692f69;" onmouseover="this.style.color='#7d3a7d'" onmouseout="this.style.color='#692f69'">
      <span class="text-xl">MC YOMA electronic</span>
    </a>
  </div>

  <!-- Auth Card -->
  <div class="max-w-md w-full bg-white/90 backdrop-blur p-8 rounded-xl shadow-xl border border-gray-100">
    <h1 class="text-2xl font-semibold text-gray-900">Request an account</h1>
    <p class="text-sm text-gray-500 mb-6">Submit your details and wait for admin approval.</p>
    
    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-700"><?=htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-md border border-green-200 bg-green-50 text-green-700">
        <?=htmlspecialchars($success) ?>
        <div class="mt-3">
          <a href="/login.php" class="text-sm font-medium underline" style="color: #692f69;">Back to login</a>
        </div>
      </div>
    <?php else: ?>
      <form action="" method="post" class="space-y-5">
        <div>
          <label for="username" class="block text-sm font-medium text-gray-700">Your Name</label>
          <input id="username" name="username" type="text" placeholder="e.g., John Doe" required class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69; --tw-border-opacity: 1;" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor=''" />
        </div>
        <div>
          <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile number</label>
          <input id="mobile" name="mobile" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" oninput="this.value=this.value.replace(/\D+/g,'').slice(0,10)" title="Enter exactly 10 digits" placeholder="e.g., 0712345678" required class="mt-1 block w-full px-3 py-2.5 rounded-lg border border-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69; --tw-border-opacity: 1;" onfocus="this.style.borderColor='#692f69'" onblur="this.style.borderColor=''" />
          <p class="mt-1 text-xs text-gray-400">Enter exactly 10 digits (e.g., 0771234567)</p>
        </div>
        <div>
          <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg text-white font-medium shadow focus:outline-none focus-visible:ring-2" style="background-color: #692f69; --tw-ring-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
            Submit Request
          </button>
        </div>
        <div class="text-center">
          <a href="/login.php" class="text-sm font-medium inline-flex items-center gap-1" style="color: #692f69;" onmouseover="this.style.color='#7d3a7d'" onmouseout="this.style.color='#692f69'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to login
          </a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
