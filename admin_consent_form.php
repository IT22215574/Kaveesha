<?php
require_once __DIR__ . '/config.php';
require_admin();

$message = '';
$error = '';
$consentFormPath = __DIR__ . '/forms/consent_form.pdf';
$consentFormExists = file_exists($consentFormPath);

// Handle form upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['consent_pdf'])) {
    try {
        $file = $_FILES['consent_pdf'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed with error code: ' . $file['error']);
        }
        
        // Validate file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileType !== 'pdf') {
            throw new Exception('Only PDF files are allowed.');
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit.');
        }
        
        // Ensure forms directory exists with proper permissions
        $formsDir = __DIR__ . '/forms';
        if (!is_dir($formsDir)) {
            if (!mkdir($formsDir, 0777, true)) {
                throw new Exception('Failed to create forms directory.');
            }
            chmod($formsDir, 0777);
        }
        
        // Ensure directory is writable
        if (!is_writable($formsDir)) {
            chmod($formsDir, 0777);
            if (!is_writable($formsDir)) {
                throw new Exception('Forms directory is not writable. Please check permissions.');
            }
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $consentFormPath)) {
            chmod($consentFormPath, 0666); // Make file readable
            $message = 'Consent form uploaded successfully!';
            $consentFormExists = true;
        } else {
            throw new Exception('Failed to save the uploaded file. Check server permissions.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle form deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form'])) {
    if ($consentFormExists && unlink($consentFormPath)) {
        $message = 'Consent form deleted successfully!';
        $consentFormExists = false;
    } else {
        $error = 'Failed to delete consent form.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Consent Form Management - Yoma Electronics</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow p-6">
      <h1 class="text-2xl font-bold mb-6" style="color: #692f69;">Consent Form Management</h1>
      
      <?php if ($message): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($consentFormExists): ?>
        <!-- Current Form Display -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
          <h2 class="text-lg font-semibold mb-3 text-blue-900">Current Consent Form</h2>
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <svg class="w-12 h-12 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
              </svg>
              <div>
                <p class="font-medium text-gray-900">consent_form.pdf</p>
                <p class="text-sm text-gray-600">Size: <?= number_format(filesize($consentFormPath) / 1024, 2) ?> KB</p>
                <p class="text-sm text-gray-600">Last modified: <?= date('Y-m-d H:i:s', filemtime($consentFormPath)) ?></p>
              </div>
            </div>
            <div class="flex flex-col space-y-2">
              <a href="admin_print_consent.php" target="_blank" 
                 class="px-4 py-2 text-white rounded hover:opacity-90 transition text-center"
                 style="background-color: #692f69;">
                View & Print Form
              </a>
              <form method="post" onsubmit="return confirm('Are you sure you want to delete the current consent form?');">
                <button type="submit" name="delete_form" value="1"
                        class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                  Delete Form
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Upload New Form -->
      <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4" style="color: #692f69;">
          <?= $consentFormExists ? 'Replace Consent Form' : 'Upload Consent Form' ?>
        </h2>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Select PDF File
            </label>
            <input type="file" 
                   name="consent_pdf" 
                   accept=".pdf,application/pdf"
                   required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
            <p class="mt-2 text-sm text-gray-500">
              Maximum file size: 10MB. Only PDF files are allowed.
            </p>
          </div>
          
          <button type="submit" 
                  class="w-full px-4 py-2 text-white rounded hover:opacity-90 transition"
                  style="background-color: #692f69;">
            <?= $consentFormExists ? 'Replace Consent Form' : 'Upload Consent Form' ?>
          </button>
        </form>
      </div>

      <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded">
        <h3 class="font-semibold mb-2">How to use:</h3>
        <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
          <li>Upload your consent form PDF using the form above</li>
          <li>Click "View & Print Form" to open the PDF in a new window</li>
          <li>Print the form directly from your browser (Ctrl+P or Cmd+P)</li>
          <li>Have customers sign the printed form</li>
          <li>You can replace the form anytime by uploading a new PDF</li>
        </ol>
      </div>
    </div>
  </div>
</body>
</html>
