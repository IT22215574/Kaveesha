<?php
require_once __DIR__ . '/config.php';
require_admin();

// Get listing ID from query parameter
$listingId = (int)($_GET['listing_id'] ?? 0);
if ($listingId <= 0) {
    $_SESSION['flash'] = 'Invalid listing ID.';
    header('Location: /Kaveesha/add_listing.php');
    exit;
}

// Fetch listing details
$stmt = db()->prepare('SELECT l.*, u.username, u.mobile_number FROM listings l 
                       JOIN users u ON l.user_id = u.id 
                       WHERE l.id = ? LIMIT 1');
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['flash'] = 'Listing not found.';
    header('Location: /Kaveesha/add_listing.php');
    exit;
}

// Check if invoice already exists for this listing
$existingInvoice = db()->prepare('SELECT id, invoice_number, status FROM invoices WHERE listing_id = ? LIMIT 1');
$existingInvoice->execute([$listingId]);
$invoice = $existingInvoice->fetch();

// Generate invoice number if creating new
function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        try {
            db()->beginTransaction();
            
            $invoiceNumber = $_POST['invoice_number'] ?? generateInvoiceNumber();
            $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
            $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));
            $notes = trim($_POST['notes'] ?? '');
            $taxRate = (float)($_POST['tax_rate'] ?? 0);
            $serviceCharge = max(0, (float)($_POST['service_charge'] ?? 0));
            
            // Process invoice items
            $items = [];
            $subtotal = 0;
            $totalDiscount = 0;
            
            if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
                for ($i = 0; $i < count($_POST['item_description']); $i++) {
                    $desc = trim($_POST['item_description'][$i] ?? '');
                    $qty = max(1, (int)($_POST['item_quantity'][$i] ?? 1));
                    $price = max(0, (float)($_POST['item_price'][$i] ?? 0));
                    $discountPerUnit = max(0, (float)($_POST['item_discount'][$i] ?? 0));
                    
                    if ($desc !== '') {
                        $itemTotal = $qty * ($price - $discountPerUnit);
                        $itemTotalDiscount = $qty * $discountPerUnit;
                        $items[] = [
                            'description' => $desc,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'discount_amount' => $discountPerUnit,
                            'total_price' => $itemTotal
                        ];
                        $subtotal += $itemTotal;
                        $totalDiscount += $itemTotalDiscount;
                    }
                }
            }
            
            $taxAmount = $subtotal * ($taxRate / 100);
            $totalAmount = $subtotal + $serviceCharge + $taxAmount;
            
            if ($action === 'create') {
                // Create new invoice
                $stmt = db()->prepare('INSERT INTO invoices (listing_id, user_id, invoice_number, invoice_date, due_date, subtotal, discount_amount, service_charge, tax_amount, total_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $status = ($action === 'send') ? 'sent' : 'draft';
                $stmt->execute([$listingId, $listing['user_id'], $invoiceNumber, $invoiceDate, $dueDate, $subtotal, $totalDiscount, $serviceCharge, $taxAmount, $totalAmount, $notes, $status]);
                $invoiceId = db()->lastInsertId();
                
                $_SESSION['flash'] = 'Invoice saved successfully!';
            } else {
                // Update existing invoice
                $invoiceId = $invoice['id'];
                $status = ($action === 'send') ? 'sent' : $invoice['status'];
                $stmt = db()->prepare('UPDATE invoices SET invoice_date = ?, due_date = ?, subtotal = ?, discount_amount = ?, service_charge = ?, tax_amount = ?, total_amount = ?, notes = ?, status = ? WHERE id = ?');
                $stmt->execute([$invoiceDate, $dueDate, $subtotal, $totalDiscount, $serviceCharge, $taxAmount, $totalAmount, $notes, $status, $invoiceId]);
                
                // Delete existing items
                $stmt = db()->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
                $stmt->execute([$invoiceId]);
                
                $_SESSION['flash'] = 'Invoice updated successfully!';
            }
            
            // Insert invoice items
            if (!empty($items)) {
                $stmt = db()->prepare('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, discount_amount, total_price) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($items as $item) {
                    $stmt->execute([$invoiceId, $item['description'], $item['quantity'], $item['unit_price'], $item['discount_amount'], $item['total_price']]);
                }
            }
            
            // Handle send action
            if ($action === 'send') {
                $_SESSION['flash'] = 'Invoice sent to customer successfully!';
            }
            
            db()->commit();
            
            // Redirect to view invoice
            header('Location: /Kaveesha/view_invoice.php?id=' . $invoiceId);
            exit;
            
        } catch (Exception $e) {
            db()->rollBack();
            $_SESSION['flash'] = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'send') {
        // Mark invoice as sent
        if ($invoice) {
            $stmt = db()->prepare('UPDATE invoices SET status = "sent" WHERE id = ?');
            $stmt->execute([$invoice['id']]);
            $_SESSION['flash'] = 'Invoice marked as sent!';
            header('Location: /Kaveesha/view_invoice.php?id=' . $invoice['id']);
            exit;
        }
    }
}

// Load existing invoice data if editing
$invoiceData = null;
$invoiceItems = [];
if ($invoice) {
    $stmt = db()->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$invoice['id']]);
    $invoiceData = $stmt->fetch();
    
    $stmt = db()->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
    $stmt->execute([$invoice['id']]);
    $invoiceItems = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $invoice ? 'Edit' : 'Create' ?> Invoice • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>
  
  <div class="container mx-auto px-4 py-8">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="mb-4 p-3 rounded bg-blue-100 border border-blue-300 text-blue-800">
        <?= htmlspecialchars($_SESSION['flash']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
          <?= $invoice ? 'Edit Invoice' : 'Create Invoice' ?>
        </h1>
        <div class="bg-gray-100 p-4 rounded-lg">
          <h3 class="font-semibold text-lg"><?= htmlspecialchars($listing['title']) ?></h3>
          <p class="text-gray-600">Customer: <?= htmlspecialchars($listing['username']) ?> (<?= htmlspecialchars($listing['mobile_number']) ?>)</p>
          <p class="text-gray-600">Status: 
            <?php 
            $statusLabels = [1 => 'Not Finished', 2 => 'Stopped', 3 => 'Finished & Pending Payments', 4 => 'Completed & Received Payments'];
            echo $statusLabels[$listing['status']] ?? 'Unknown';
            ?>
          </p>
        </div>
      </div>

      <?php if ($invoice && $invoiceData['status'] !== 'draft'): ?>
        <div class="mb-6 p-4 bg-yellow-100 border border-yellow-300 rounded-lg">
          <p class="text-yellow-800">
            <strong>Note:</strong> This invoice has been sent (Status: <?= ucfirst($invoiceData['status']) ?>). 
            <a href="/Kaveesha/view_invoice.php?id=<?= $invoice['id'] ?>" class="text-blue-600 underline">View Invoice</a>
          </p>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6">
        <input type="hidden" name="action" value="<?= $invoice ? 'update' : 'create' ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="invoice_number" class="block text-sm font-medium text-gray-700">Invoice Number</label>
            <input type="text" id="invoice_number" name="invoice_number" 
                   value="<?= htmlspecialchars($invoiceData['invoice_number'] ?? generateInvoiceNumber()) ?>"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   <?= $invoice ? 'readonly' : '' ?>>
          </div>
          <div>
            <label for="invoice_date" class="block text-sm font-medium text-gray-700">Invoice Date</label>
            <input type="date" id="invoice_date" name="invoice_date" 
                   value="<?= $invoiceData['invoice_date'] ?? date('Y-m-d') ?>"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          <div>
            <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
            <input type="date" id="due_date" name="due_date" 
                   value="<?= $invoiceData['due_date'] ?? date('Y-m-d', strtotime('+7 days')) ?>"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Invoice Items</label>
          <div id="invoice-items">
            <?php if (!empty($invoiceItems)): ?>
              <?php foreach ($invoiceItems as $index => $item): ?>
                <div class="invoice-item grid grid-cols-1 md:grid-cols-6 gap-3 mb-3 p-3 border rounded-lg">
                  <div class="md:col-span-2">
                    <input type="text" name="item_description[]" placeholder="Description" 
                           value="<?= htmlspecialchars($item['description']) ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  </div>
                  <div>
                    <input type="number" name="item_quantity[]" placeholder="Qty" min="1" 
                           value="<?= $item['quantity'] ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  </div>
                  <div>
                    <input type="number" name="item_price[]" placeholder="Unit Price" min="0" step="0.01" 
                           value="<?= number_format($item['unit_price'], 2, '.', '') ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  </div>
                  <div>
                    <input type="number" name="item_discount[]" placeholder="Discount/Unit" min="0" step="0.01" 
                           value="<?= number_format($item['discount_amount'] ?? 0, 2, '.', '') ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  </div>
                  <div class="flex items-center">
                    <span class="item-total text-sm font-medium">Rs. <?= number_format($item['total_price'], 2) ?></span>
                    <button type="button" onclick="removeItem(this)" class="ml-2 text-red-600 hover:text-red-800">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="invoice-item grid grid-cols-1 md:grid-cols-6 gap-3 mb-3 p-3 border rounded-lg">
                <div class="md:col-span-2">
                  <input type="text" name="item_description[]" placeholder="Description" 
                         class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <input type="number" name="item_quantity[]" placeholder="Qty" min="1"
                         class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <input type="number" name="item_price[]" placeholder="Unit Price" min="0" step="0.01" 
                         class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <input type="number" name="item_discount[]" placeholder="Discount/Unit" min="0" step="0.01"
                         class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-center">
                  <span class="item-total text-sm font-medium">Rs. 0.00</span>
                  <button type="button" onclick="removeItem(this)" class="ml-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" onclick="addItem()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Add Item
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea id="notes" name="notes" rows="4" 
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Additional notes or terms..."><?= htmlspecialchars($invoiceData['notes'] ?? '') ?></textarea>
          </div>
          
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>Subtotal:</span>
                <span id="subtotal">Rs. <?= isset($invoiceData) ? number_format($invoiceData['subtotal'], 2) : '0.00' ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span>Service Charge:</span>
                <input type="number" name="service_charge" id="service_charge" min="0" step="0.01" 
                       value="<?= isset($invoiceData) ? number_format($invoiceData['service_charge'], 2, '.', '') : '0' ?>"
                       class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              </div>
              <div class="flex justify-between">
                <span>Total Discount:</span>
                <span id="total-discount">Rs. <?= isset($invoiceData) ? number_format($invoiceData['discount_amount'], 2) : '0.00' ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span>Tax Rate (%):</span>
                <input type="number" name="tax_rate" id="tax_rate" min="0" max="100" step="0.01" 
                       value="<?= isset($invoiceData) ? number_format(($invoiceData['tax_amount'] / max($invoiceData['subtotal'], 0.01)) * 100, 2) : '0' ?>"
                       class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              </div>
              <div class="flex justify-between">
                <span>Tax Amount:</span>
                <span id="tax-amount">Rs. <?= isset($invoiceData) ? number_format($invoiceData['tax_amount'], 2) : '0.00' ?></span>
              </div>
              <div class="flex justify-between font-bold text-lg border-t pt-2">
                <span>Total:</span>
                <span id="total-amount">Rs. <?= isset($invoiceData) ? number_format($invoiceData['total_amount'], 2) : '0.00' ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3 pt-6 border-t">
          <button type="submit" name="action" value="<?= $invoice ? 'update' : 'create' ?>" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <?= $invoice ? 'Save Invoice' : 'Save Invoice' ?>
          </button>
          
          <button type="submit" name="action" value="send" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Send Invoice
          </button>
          
          <a href="/Kaveesha/add_listing.php" onclick="return confirmLeave()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Listings
          </a>
          
          <?php if ($invoice): ?>
            <a href="/Kaveesha/view_invoice.php?id=<?= $invoice['id'] ?>" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
              View Invoice
            </a>
          <?php endif; ?>
        </div>
      </form>
      
    </div>
  </div>

  <!-- Payment Notice Popup Modal -->
  <div id="paymentNoticeModal" class="fixed inset-0 z-50 hidden" aria-labelledby="paymentNoticeTitle" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative mx-auto mt-20 w-[90%] max-w-md rounded-lg bg-white shadow-xl">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0 mr-3">
            <svg class="h-8 w-8 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <h2 id="paymentNoticeTitle" class="text-lg font-semibold text-gray-900">Payment Notice</h2>
        </div>
        <div class="mb-6">
          <p class="text-sm text-gray-700">
            <strong class="text-red-600">Important:</strong> Please ensure to make the payment within 
            <strong class="text-red-600">seven (7) days</strong> after the invoice is issued. 
            Late payments may incur additional charges.
          </p>
          <p class="text-xs text-gray-500 mt-2">
            This notice will be included with your invoice when sent to the customer.
          </p>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" id="paymentNoticeOkBtn" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Understood
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let hasUnsavedChanges = false;
    let originalFormData = '';

    function addItem() {
      const container = document.getElementById('invoice-items');
      const itemHtml = `
        <div class="invoice-item grid grid-cols-1 md:grid-cols-6 gap-3 mb-3 p-3 border rounded-lg">
          <div class="md:col-span-2">
            <input type="text" name="item_description[]" placeholder="Description" 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          <div>
            <input type="number" name="item_quantity[]" placeholder="Qty" min="1"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          <div>
            <input type="number" name="item_price[]" placeholder="Unit Price" min="0" step="0.01" 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          <div>
            <input type="number" name="item_discount[]" placeholder="Discount/Unit" min="0" step="0.01"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          <div class="flex items-center">
            <span class="item-total text-sm font-medium">Rs. 0.00</span>
            <button type="button" onclick="removeItem(this)" class="ml-2 text-red-600 hover:text-red-800">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', itemHtml);
      attachEventListeners();
      hasUnsavedChanges = true;
    }

    function removeItem(button) {
      const item = button.closest('.invoice-item');
      item.remove();
      calculateTotal();
      hasUnsavedChanges = true;
    }

    function calculateTotal() {
      let subtotal = 0;
      let totalDiscount = 0;
      
      document.querySelectorAll('.invoice-item').forEach(item => {
        const qty = parseFloat(item.querySelector('input[name="item_quantity[]"]').value) || 0;
        const price = parseFloat(item.querySelector('input[name="item_price[]"]').value) || 0;
        const discountPerUnit = parseFloat(item.querySelector('input[name="item_discount[]"]').value) || 0;
        const total = qty * (price - discountPerUnit);
        const itemTotalDiscount = qty * discountPerUnit;
        
        item.querySelector('.item-total').textContent = 'Rs. ' + Math.max(0, total).toFixed(2);
        subtotal += Math.max(0, total);
        totalDiscount += itemTotalDiscount;
      });
      
      const serviceCharge = parseFloat(document.getElementById('service_charge').value) || 0;
      const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
      const taxAmount = subtotal * (taxRate / 100);
      const totalAmount = subtotal + serviceCharge + taxAmount;
      
      document.getElementById('subtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
      document.getElementById('total-discount').textContent = 'Rs. ' + totalDiscount.toFixed(2);
      document.getElementById('tax-amount').textContent = 'Rs. ' + taxAmount.toFixed(2);
      document.getElementById('total-amount').textContent = 'Rs. ' + totalAmount.toFixed(2);
    }

    function attachEventListeners() {
      document.querySelectorAll('input[name="item_quantity[]"], input[name="item_price[]"], input[name="item_discount[]"]').forEach(input => {
        input.addEventListener('input', function() {
          calculateTotal();
          hasUnsavedChanges = true;
        });
      });
      
      document.getElementById('tax_rate').addEventListener('input', function() {
        calculateTotal();
        hasUnsavedChanges = true;
      });
      
      document.getElementById('service_charge').addEventListener('input', function() {
        calculateTotal();
        hasUnsavedChanges = true;
      });
    }

    function confirmLeave() {
      if (hasUnsavedChanges) {
        return confirm('You have unsaved changes. Are you sure you want to leave without saving?');
      }
      return true;
    }

    function trackFormChanges() {
      document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('input', function() {
          hasUnsavedChanges = true;
        });
      });
    }

    function showPaymentNotice() {
      const modal = document.getElementById('paymentNoticeModal');
      if (modal) {
        modal.classList.remove('hidden');
      }
    }

    function hidePaymentNotice() {
      const modal = document.getElementById('paymentNoticeModal');
      if (modal) {
        modal.classList.add('hidden');
      }
    }

    // Initialize event listeners on page load
    document.addEventListener('DOMContentLoaded', function() {
      attachEventListeners();
      trackFormChanges();
      calculateTotal();
      
      // Auto-set due date when invoice date changes
      document.getElementById('invoice_date').addEventListener('change', function() {
        const invoiceDate = new Date(this.value);
        if (!isNaN(invoiceDate.getTime())) {
          const dueDate = new Date(invoiceDate);
          dueDate.setDate(dueDate.getDate() + 7);
          document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
          hasUnsavedChanges = true;
        }
      });
      
      // Store original form data
      const form = document.querySelector('form');
      originalFormData = new FormData(form).toString();
      
      // Reset unsaved changes flag when form is submitted
      form.addEventListener('submit', function() {
        hasUnsavedChanges = false;
      });
      
      // Payment notice modal handlers
      const paymentModal = document.getElementById('paymentNoticeModal');
      const paymentOkBtn = document.getElementById('paymentNoticeOkBtn');
      
      if (paymentOkBtn) {
        paymentOkBtn.addEventListener('click', hidePaymentNotice);
      }
      
      // Close modal when clicking outside
      if (paymentModal) {
        paymentModal.addEventListener('click', function(e) {
          if (e.target === paymentModal) {
            hidePaymentNotice();
          }
        });
      }
      
      // Show payment notice when Send Invoice button is clicked
      const sendButtons = document.querySelectorAll('button[value="send"]');
      sendButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          showPaymentNotice();
          
          // After user acknowledges, submit the form
          paymentOkBtn.addEventListener('click', function() {
            hidePaymentNotice();
            hasUnsavedChanges = false;
            button.closest('form').submit();
          }, { once: true });
        });
      });
      
      // Warn before leaving page
      window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
    });
  </script>
</body>
</html>