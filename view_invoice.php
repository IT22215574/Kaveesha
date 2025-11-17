<?php
require_once __DIR__ . '/config.php';
require_admin();

$invoiceId = (int)($_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    $_SESSION['flash'] = 'Invalid invoice ID.';
    header('Location: /Kaveesha/add_listing.php');
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
    header('Location: /Kaveesha/add_listing.php');
    exit;
}

// Fetch invoice items
$stmt = db()->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
$stmt->execute([$invoiceId]);
$items = $stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $validStatuses = ['draft', 'sent', 'paid', 'overdue'];
        
        if (in_array($status, $validStatuses)) {
            $stmt = db()->prepare('UPDATE invoices SET status = ? WHERE id = ?');
            $stmt->execute([$status, $invoiceId]);
            $_SESSION['flash'] = 'Invoice status updated to ' . ucfirst($status);
            header('Location: /Kaveesha/view_invoice.php?id=' . $invoiceId);
            exit;
        }
    } elseif ($action === 'send_invoice') {
        // In a real application, you would send email here
        $stmt = db()->prepare('UPDATE invoices SET status = "sent" WHERE id = ?');
        $stmt->execute([$invoiceId]);
        $_SESSION['flash'] = 'Invoice marked as sent! (In a production system, an email would be sent to ' . htmlspecialchars($invoice['username']) . ')';
        header('Location: /Kaveesha/view_invoice.php?id=' . $invoiceId);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?> • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>
  </div>
  
  <div class="container mx-auto px-4 py-8">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="no-print mb-4 p-3 rounded bg-blue-100 border border-blue-300 text-blue-800">
        <?= htmlspecialchars($_SESSION['flash']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Action buttons -->
    <div class="no-print mb-6 flex flex-wrap gap-3">
      <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Print Invoice
      </button>
      <a href="/Kaveesha/create_invoice.php?listing_id=<?= (int)$invoice['listing_id'] ?>" 
         class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        Edit Invoice
      </a>
      <a href="/Kaveesha/add_listing.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
        Back to Listings
      </a>
      
      <?php if ($invoice['status'] === 'draft'): ?>
        <form method="post" class="inline">
          <input type="hidden" name="action" value="send_invoice">
          <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            Send to Customer
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Status update form -->
    <div class="no-print mb-6 bg-white p-4 rounded-lg shadow-md">
      <form method="post" class="flex items-center gap-3">
        <input type="hidden" name="action" value="update_status">
        <label class="font-medium">Status:</label>
        <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

    <!-- Invoice -->
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-8">
      <!-- Header -->
      <div class="border-b pb-6 mb-6">
        <div class="flex justify-between items-start">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">INVOICE</h1>
            <p class="text-gray-600 mt-1">Yoma Electronics</p>
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
          <div>
            <h3 class="font-semibold text-lg mb-2">Service Details:</h3>
            <p class="text-gray-900 font-medium"><?= htmlspecialchars($invoice['listing_title']) ?></p>
            <?php if (!empty($invoice['listing_description'])): ?>
              <p class="text-gray-600 text-sm mt-1"><?= nl2br(htmlspecialchars($invoice['listing_description'])) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="mb-6">
        <table class="w-full border-collapse border border-gray-300">
          <thead>
            <tr class="bg-gray-50">
              <th class="border border-gray-300 px-4 py-3 text-left font-medium">Description</th>
              <th class="border border-gray-300 px-4 py-3 text-center font-medium">Qty</th>
              <th class="border border-gray-300 px-4 py-3 text-right font-medium">Unit Price</th>
              <th class="border border-gray-300 px-4 py-3 text-right font-medium">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="border border-gray-300 px-4 py-3"><?= htmlspecialchars($item['description']) ?></td>
                <td class="border border-gray-300 px-4 py-3 text-center"><?= (int)$item['quantity'] ?></td>
                <td class="border border-gray-300 px-4 py-3 text-right">Rs. <?= number_format($item['unit_price'], 2) ?></td>
                <td class="border border-gray-300 px-4 py-3 text-right">Rs. <?= number_format($item['total_price'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="flex justify-end">
        <div class="w-64">
          <div class="border-t pt-4 space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Subtotal:</span>
              <span>Rs. <?= number_format($invoice['subtotal'], 2) ?></span>
            </div>
            <?php if ($invoice['tax_amount'] > 0): ?>
              <div class="flex justify-between">
                <span class="text-gray-600">Tax:</span>
                <span>Rs. <?= number_format($invoice['tax_amount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold text-lg border-t pt-2">
              <span>Total:</span>
              <span>Rs. <?= number_format($invoice['total_amount'], 2) ?></span>
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

      <!-- Footer -->
      <div class="mt-8 pt-6 border-t text-center text-gray-600">
        <p>Thank you for your business!</p>
        <p class="text-sm mt-2">For any questions about this invoice, please contact us at 0775604833</p>
      </div>
    </div>
  </div>
</body>
</html>