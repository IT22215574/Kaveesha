<?php
require_once __DIR__ . '/config.php';
require_admin();

$consentFormPath = __DIR__ . '/forms/consent_form.pdf';

// Check if consent form exists
if (!file_exists($consentFormPath)) {
    http_response_code(404);
    echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form Not Found</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-lg shadow max-w-md text-center">
    <h1 class="text-2xl font-bold text-red-600 mb-4">Consent Form Not Found</h1>
    <p class="text-gray-700 mb-6">No consent form has been uploaded yet.</p>
    <a href="admin_consent_form.php" class="inline-block px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800">
      Upload Consent Form
    </a>
  </div>
</body>
</html>';
    exit;
}

// Set headers to display PDF inline
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="consent_form.pdf"');
header('Content-Length: ' . filesize($consentFormPath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output the PDF file
readfile($consentFormPath);
exit;
