<?php
require_once __DIR__ . '/config.php';
require_login();

// Check if user is admin
$isAdmin = !empty($_SESSION['is_admin']);

$invoiceId = (int)($_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    $_SESSION['flash'] = 'Invalid invoice ID.';
    $redirectUrl = $isAdmin ? '/invoices.php' : '/dashboard.php';
    header('Location: ' . $redirectUrl);
    exit;
}

// Fetch invoice with listing and user details
$stmt = db()->prepare('
    SELECT i.*, l.title as listing_title, l.description as listing_description,
           u.username, u.mobile_number 
    FROM invoices i 
    JOIN listings l ON i.listing_id = l.id 
    JOIN users u ON i.user_id = u.id 
    WHERE i.id = ? LIMIT 1
');
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    $_SESSION['flash'] = 'Invoice not found.';
    $redirectUrl = $isAdmin ? '/invoices.php' : '/dashboard.php';
    header('Location: ' . $redirectUrl);
    exit;
}

// If not admin, verify the invoice belongs to the logged-in user
if (!$isAdmin && $invoice['user_id'] != $_SESSION['user_id']) {
    $_SESSION['flash'] = 'You do not have permission to view this invoice.';
    header('Location: /dashboard.php');
    exit;
}

// Fetch invoice items
$stmt = db()->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
$stmt->execute([$invoiceId]);
$items = $stmt->fetchAll();

// Handle status updates (admin only)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $validStatuses = ['draft', 'sent', 'paid', 'overdue'];
        
        if (in_array($status, $validStatuses)) {
            $stmt = db()->prepare('UPDATE invoices SET status = ? WHERE id = ?');
            $stmt->execute([$status, $invoiceId]);
            $_SESSION['flash'] = 'Invoice status updated to ' . ucfirst($status);
            header('Location: /view_invoice.php?id=' . $invoiceId);
            exit;
        }
    } elseif ($action === 'send_invoice') {
        // In a real application, you would send email here
        $stmt = db()->prepare('UPDATE invoices SET status = "sent" WHERE id = ?');
        $stmt->execute([$invoiceId]);
        $_SESSION['flash'] = 'Invoice marked as sent! (In a production system, an email would be sent to ' . htmlspecialchars($invoice['username']) . ')';
        header('Location: /view_invoice.php?id=' . $invoiceId);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      body { margin: 0; padding: 0; }
      .min-h-screen { min-height: auto; }
      .bg-gray-50 { background: white !important; }
      .shadow-lg { box-shadow: none !important; }
      .rounded-lg { border-radius: 0 !important; }
      
      /* Full-width layout with no margins for print */
      .container { margin: 0 !important; padding: 0 !important; }
      .mx-2, .md\:mx-auto, .md\:mx-2, .mx-0 { margin-left: 0 !important; margin-right: 0 !important; }
      .p-1, .md\:p-2, .md\:p-1, .px-4, .py-8 { padding: 0 !important; }
      .max-w-full, .md\:max-w-6xl, .md\:max-w-7xl { max-width: 100% !important; width: 100% !important; }
      
      /* Full-width content areas */
      .bg-white { margin: 0 !important; width: 100% !important; }
      .rounded-lg { margin: 0 !important; }
      
      /* Ultra-compact header and section spacing */
      .border-b { padding-bottom: 2px !important; }
      .mb-6 { margin-bottom: 2px !important; }
      .pb-6 { padding-bottom: 2px !important; }
      .pt-6 { padding-top: 2px !important; }
      .mt-8 { margin-top: 3px !important; }
      
      /* Full-width table with enhanced number spacing */
      table { 
        page-break-inside: avoid; 
        width: 100% !important; 
        margin: 0 !important; 
        table-layout: fixed !important;
      }
      .overflow-x-auto { overflow: visible !important; margin: 0 !important; }
      
      /* Optimized column widths for maximum number space */
      colgroup col:nth-child(1) { width: 35% !important; } /* Description - reduced */
      colgroup col:nth-child(2) { width: 7% !important; }  /* Qty - reduced */
      colgroup col:nth-child(3) { width: 20% !important; } /* Unit Price - increased for big numbers */
      colgroup col:nth-child(4) { width: 18% !important; } /* Discount - increased */
      colgroup col:nth-child(5) { width: 20% !important; } /* Total - maximum for big totals */
      
      /* Maximum spacing for numerical columns - enhanced for big numbers */
      tbody td:nth-child(2) { padding: 1px 6px !important; } /* Qty - compact */
      tbody td:nth-child(3) { padding: 1px 20px !important; } /* Unit Price - extra space for big currency */
      tbody td:nth-child(4) { padding: 1px 18px !important; } /* Discount - more space for currency */
      tbody td:nth-child(5) { padding: 1px 22px !important; } /* Total - maximum space for large totals */
      
      /* Header cells with enhanced spacing for big numbers */
      thead th:nth-child(2) { padding: 1px 6px !important; } /* Qty header */
      thead th:nth-child(3) { padding: 1px 20px !important; } /* Unit Price header */
      thead th:nth-child(4) { padding: 1px 18px !important; } /* Discount header */
      thead th:nth-child(5) { padding: 1px 22px !important; } /* Total header */
      
      /* Keep description column compact but readable */
      .px-4 { padding-left: 2px !important; padding-right: 2px !important; } /* Description column */
      tbody td:nth-child(1) { padding: 1px 3px !important; } /* Description cells */
      thead th:nth-child(1) { padding: 1px 3px !important; } /* Description header */
      
      /* Other padding overrides */
      .px-5, .px-3, .px-2 { padding-left: 1px !important; padding-right: 1px !important; }
      .py-3 { padding-top: 1px !important; padding-bottom: 1px !important; }
      
      /* Ultra-compact text sizing */
      h1 { font-size: 18px !important; margin: 0 !important; }
      h3 { font-size: 12px !important; margin: 1px 0 !important; }
      th, td { font-size: 11px !important; line-height: 1.2 !important; }
      p { margin: 1px 0 !important; font-size: 10px !important; }
      
      /* Ultra-compact totals section */
      .w-96 { width: 280px !important; }
      .space-y-3 > * + * { margin-top: 1px !important; }
      .gap-4 { gap: 2px !important; }
      
      /* Payment notice fixed at bottom of page */
      .bg-yellow-50 { 
        position: fixed !important;
        bottom: 40px !important;
        left: 0px !important;
        right: 0px !important;
        padding: 4px !important; 
        margin: 0 !important; 
        font-size: 9px !important;
        page-break-inside: avoid !important;
        background: #fefce8 !important;
        border-left: 3px solid #f59e0b !important;
        border-radius: 0px !important;
      }
      .bg-yellow-50 h3 { font-size: 10px !important; margin: 0 !important; }
      .bg-yellow-50 p { font-size: 9px !important; margin: 2px 0 !important; }
      .bg-yellow-50 .ml-3 { margin-left: 8px !important; }
      
      /* Footer always at bottom of page */
      .text-center.text-gray-600 { 
        font-size: 10px !important; 
        margin-top: 0 !important;
        position: fixed !important;
        bottom: 10px !important;
        left: 0 !important;
        right: 0 !important;
        text-align: center !important;
        page-break-inside: avoid !important;
      }
      
      .whitespace-nowrap { white-space: nowrap !important; }
      .text-right { text-align: right !important; }
      .font-medium { font-weight: 500 !important; }
      
      /* Logo print styles */
      img { 
        height: 224px !important; /* Match h-56 */
        width: auto !important; 
        max-width: none !important;
        display: block !important;
      }
      .flex-shrink-0 { flex-shrink: 0 !important; }
      .gap-4 { gap: 16px !important; }
      
      /* Reduce calculation font sizes by 50% for print */
      .totals-section .grid {
        font-size: 0.5em !important;
      }
      .totals-section .font-bold {
        font-size: 0.6em !important;
      }
    }
  </style>
  <style>
    @media print {
      .no-print { display: none !important; }
      .print-only { display: block !important; }
      body { background: white !important; }
      .shadow-md { box-shadow: none !important; }
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="no-print">
    <?php 
      if ($isAdmin) {
        include __DIR__ . '/includes/admin_nav.php';
      } else {
        include __DIR__ . '/includes/user_nav.php';
      }
    ?>
  </div>
  
  <div class="container mx-auto px-4 py-8">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="no-print mb-4 p-3 rounded bg-blue-100 border border-blue-300 text-blue-800">
        <?= htmlspecialchars($_SESSION['flash']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Invoice Notification (User only) -->
    <?php if (!$isAdmin): ?>
    <div class="no-print mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border-l-4 p-4 rounded-lg shadow-md" style="border-left-color: #692f69;">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="h-6 w-6" style="color: #692f69;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium" style="color: #5a1a5a;">
            📋 You have received an invoice for: <span class="font-bold"><?= htmlspecialchars($invoice['listing_title']) ?></span>
          </p>
          <a href="/dashboard.php?listing_id=<?= (int)$invoice['listing_id'] ?>" class="mt-2 inline-flex items-center text-sm font-semibold" style="color: #692f69;" onmouseover="this.style.color='#7d3a7d'" onmouseout="this.style.color='#692f69'">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            View Listing
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <div class="no-print mb-6 flex flex-wrap gap-3">
      <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Print Invoice
      </button>
      
      <?php if ($isAdmin): ?>
        <a href="/create_invoice.php?listing_id=<?= (int)$invoice['listing_id'] ?>" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          Edit Invoice
        </a>
        <a href="/invoices.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
          Back to Invoices
        </a>
        
        <?php if ($invoice['status'] === 'draft' || $invoice['status'] === 'sent'): ?>
          <form method="post" class="inline">
            <input type="hidden" name="action" value="send_invoice">
            <button type="submit" class="px-4 py-2 text-white rounded-lg" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
              <?= $invoice['status'] === 'draft' ? 'Send to Customer' : 'Resend to Customer' ?>
            </button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <button onclick="downloadInvoice()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          Download Invoice
        </button>
        <a href="/dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
          Back to Dashboard
        </a>
      <?php endif; ?>
    </div>

    <!-- Status update form (Admin only) -->
    <?php if ($isAdmin): ?>
    <div class="no-print mb-6 bg-white p-4 rounded-lg shadow-md">
      <form method="post" class="flex items-center gap-3">
        <input type="hidden" name="action" value="update_status">
        <label class="font-medium">Status:</label>
        <select name="status" class="rounded-md border-gray-300 shadow-sm  focus:ring-2" style="--tw-ring-color: #692f69">
          <option value="draft" <?= $invoice['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="sent" <?= $invoice['status'] === 'sent' ? 'selected' : '' ?>>Sent</option>
          <option value="paid" <?= $invoice['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="overdue" <?= $invoice['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
          Update Status
        </button>
        <span class="px-3 py-1 text-sm rounded-full 
          <?php 
            switch($invoice['status']) {
              case 'draft': echo 'bg-gray-200 text-gray-800'; break;
              case 'sent': echo 'bg-blue-200 text-blue-800'; break;
              case 'paid': echo 'bg-green-200 text-green-800'; break;
              case 'overdue': echo 'bg-red-200 text-red-800'; break;
            }
          ?>">
          <?= ucfirst($invoice['status']) ?>
        </span>
      </form>
    </div>
    <?php endif; ?>

    <!-- Invoice -->
    <div class="max-w-full md:max-w-7xl mx-0 md:mx-2 bg-white rounded-lg shadow-md p-0 md:p-1">
      <!-- Header -->
      <div class="border-b pb-6 mb-6">
        <!-- Logo at top-left -->
        <div class="flex justify-start">
          <div class="flex-shrink-0">
            <img src="/logo/logo1.png" alt="MC YOMA electronic Logo" class="h-56 w-auto">
          </div>
        </div>
        <!-- Text content below logo in parallel columns -->
        <div class="flex justify-between items-start">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">INVOICE</h1>
            <p class="text-gray-600">MC YOMA electronic</p>
            <p class="text-gray-600">Mobile: 0775604833</p>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-gray-900">#<?= htmlspecialchars($invoice['invoice_number']) ?></p>
            <p class="text-gray-600">Date: <?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></p>
            <p class="text-gray-600">Due Date: <?= date('M d, Y', strtotime($invoice['due_date'])) ?></p>
          </div>
        </div>
      </div>

      <!-- Customer Info -->
      <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="font-semibold text-lg mb-2">Bill To:</h3>
            <p class="text-gray-900 font-medium"><?= htmlspecialchars($invoice['username']) ?></p>
            <p class="text-gray-600">Mobile: <?= htmlspecialchars($invoice['mobile_number']) ?></p>
          </div>

        </div>
      </div>

      <!-- Items Table -->
      <div class="mb-6">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse border border-gray-300 table-fixed">
            <colgroup>
              <col class="w-2/5">
              <col class="w-1/12">
              <col class="w-1/6">
              <col class="w-1/6">
              <col class="w-1/6">
            </colgroup>
            <thead>
              <tr class="bg-gray-50">
                <th class="border border-gray-300 px-4 py-3 text-left font-medium">Description</th>
                <th class="border border-gray-300 px-3 py-3 text-center font-medium">Qty</th>
                <th class="border border-gray-300 px-5 py-3 text-right font-medium">Unit Price</th>
                <th class="border border-gray-300 px-5 py-3 text-right font-medium">Discount/Unit</th>
                <th class="border border-gray-300 px-5 py-3 text-right font-medium">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td class="border border-gray-300 px-4 py-3 break-words"><?= htmlspecialchars($item['description']) ?></td>
                  <td class="border border-gray-300 px-3 py-3 text-center"><?= (int)$item['quantity'] ?></td>
                  <td class="border border-gray-300 px-5 py-3 text-right whitespace-nowrap font-medium">Rs. <?= number_format($item['unit_price'], 2) ?></td>
                  <td class="border border-gray-300 px-5 py-3 text-right whitespace-nowrap font-medium">
                    <?php if ($item['discount_amount'] > 0): ?>
                      Rs. <?= number_format($item['discount_amount'], 2) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="border border-gray-300 px-5 py-3 text-right whitespace-nowrap font-medium">Rs. <?= number_format($item['total_price'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Totals -->
      <div class="flex justify-end">
        <div class="w-96">
          <div class="totals-section border-t pt-4 space-y-3">
            <?php 
              // Calculate total before discount
              $totalBeforeDiscount = $invoice['subtotal'] + $invoice['discount_amount'];
            ?>
            <div class="grid grid-cols-2 gap-4">
              <span class="text-gray-600 text-right">Total Before Discount:</span>
              <span class="text-right font-medium">Rs. <?= number_format($totalBeforeDiscount, 2) ?></span>
            </div>
            <?php if ($invoice['discount_amount'] > 0): ?>
              <div class="grid grid-cols-2 gap-4 text-green-600">
                <span class="text-right">Total Discount:</span>
                <span class="text-right font-medium">- Rs. <?= number_format($invoice['discount_amount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="grid grid-cols-2 gap-4">
              <span class="text-gray-600 text-right">Subtotal:</span>
              <span class="text-right font-medium">Rs. <?= number_format($invoice['subtotal'], 2) ?></span>
            </div>
            <?php if ($invoice['service_charge'] > 0): ?>
              <div class="grid grid-cols-2 gap-4">
                <span class="text-gray-600 text-right">Service Charge:</span>
                <span class="text-right font-medium">Rs. <?= number_format($invoice['service_charge'], 2) ?></span>
              </div>
            <?php endif; ?>
            <?php if ($invoice['tax_amount'] > 0): ?>
              <div class="grid grid-cols-2 gap-4">
                <span class="text-gray-600 text-right">Tax:</span>
                <span class="text-right font-medium">Rs. <?= number_format($invoice['tax_amount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="grid grid-cols-2 gap-4 font-bold text-lg border-t pt-3">
              <span class="text-right">Total:</span>
              <span class="text-right">Rs. <?= number_format($invoice['total_amount'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <?php if (!empty($invoice['notes'])): ?>
        <div class="mt-8 pt-6 border-t">
          <h3 class="font-semibold text-lg mb-2">Notes:</h3>
          <p class="text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        </div>
      <?php endif; ?>

      <!-- Payment Notice -->
      <div class="mt-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
              Payment Notice
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p><strong>Important:</strong> Please ensure to make the payment within <strong>seven (7) days</strong> after the invoice is issued. Late payments may incur additional charges.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-8 pt-6 border-t text-center text-gray-600">
        <p>Thank you for your business!</p>
        <p class="text-sm mt-2">For any questions about this invoice, please contact us at 0775604833</p>
      </div>
    </div>
  </div>

  <script>
    function downloadInvoice() {
      // Create a temporary title for the print dialog
      const originalTitle = document.title;
      document.title = 'Invoice_<?= htmlspecialchars($invoice['invoice_number']) ?>_<?= htmlspecialchars($invoice['listing_title']) ?>';
      
      // Trigger print dialog (user can save as PDF)
      window.print();
      
      // Restore original title
      setTimeout(() => {
        document.title = originalTitle;
      }, 100);
    }
  </script>
</body>
</html>