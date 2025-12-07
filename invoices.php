<?php
require_once __DIR__ . '/config.php';
require_admin();

// Fetch all invoices with listing and user details
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = '
    SELECT i.*, l.title as listing_title, u.username, u.mobile_number 
    FROM invoices i 
    JOIN listings l ON i.listing_id = l.id 
    JOIN users u ON i.user_id = u.id 
    WHERE 1=1
';

$params = [];

if ($searchQuery) {
    $sql .= ' AND (i.invoice_number LIKE ? OR l.title LIKE ? OR u.username LIKE ?)';
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter && in_array($statusFilter, ['draft', 'sent', 'paid', 'overdue'])) {
    $sql .= ' AND i.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY i.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Stats
$statsStmt = db()->prepare('
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue,
        SUM(total_amount) as total_amount,
        SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as paid_amount
    FROM invoices
');
$statsStmt->execute();
$stats = $statsStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoices • Admin • Kaveesha</title>
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

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-4">Invoice Management</h1>
      
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-2xl font-bold text-blue-600"><?= (int)$stats['total'] ?></div>
          <div class="text-sm text-gray-600">Total Invoices</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-2xl font-bold text-gray-600"><?= (int)$stats['draft'] ?></div>
          <div class="text-sm text-gray-600">Draft</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-2xl font-bold text-blue-600"><?= (int)$stats['sent'] ?></div>
          <div class="text-sm text-gray-600">Sent</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-2xl font-bold text-green-600"><?= (int)$stats['paid'] ?></div>
          <div class="text-sm text-gray-600">Paid</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-2xl font-bold text-red-600"><?= (int)$stats['overdue'] ?></div>
          <div class="text-sm text-gray-600">Overdue</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-xl font-bold text-green-600">Rs. <?= number_format($stats['paid_amount'] ?? 0, 0) ?></div>
          <div class="text-sm text-gray-600">Paid Amount</div>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="get" class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-64">
            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
            <input type="text" id="search" name="search" 
                   value="<?= htmlspecialchars($searchQuery) ?>"
                   placeholder="Invoice number, listing title, or customer name..."
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm  focus:ring-2" style="--tw-ring-color: #692f69">
          </div>
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status" 
                    class="mt-1 block rounded-md border-gray-300 shadow-sm  focus:ring-2" style="--tw-ring-color: #692f69">
              <option value="">All Statuses</option>
              <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
              <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
              <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
              <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Search
            </button>
            <a href="/Kaveesha/invoices.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
              Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Listing</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($invoices)): ?>
              <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                  No invoices found. <?php if ($searchQuery || $statusFilter): ?>
                    <a href="/Kaveesha/invoices.php" class="text-blue-600 underline">Clear filters</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($invoices as $invoice): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">#<?= htmlspecialchars($invoice['invoice_number']) ?></div>
                    <div class="text-sm text-gray-500">Due: <?= date('M d, Y', strtotime($invoice['due_date'])) ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($invoice['username']) ?></div>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($invoice['mobile_number']) ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900"><?= htmlspecialchars($invoice['listing_title']) ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">Rs. <?= number_format($invoice['total_amount'], 2) ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full 
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
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('M d, Y', strtotime($invoice['created_at'])) ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex gap-2">
                      <a href="/Kaveesha/view_invoice.php?id=<?= (int)$invoice['id'] ?>" 
                         style="color: #692f69;" onmouseover="this.style.color='#7d3a7d'" onmouseout="this.style.color='#692f69'">View</a>
                      <a href="/Kaveesha/create_invoice.php?listing_id=<?= (int)$invoice['listing_id'] ?>" 
                         class="text-green-600 hover:text-green-900">Edit</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>