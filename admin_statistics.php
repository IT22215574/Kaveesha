<?php
require_once __DIR__ . '/config.php';
require_admin();

// Get filter period (default: all time)
$period = isset($_GET['period']) ? trim((string)$_GET['period']) : 'all';
$validPeriods = ['today', 'week', 'month', 'year', 'all'];
if (!in_array($period, $validPeriods, true)) {
    $period = 'all';
}

// Calculate date range based on period
$dateFilter = '';
$dateParams = [];
switch ($period) {
    case 'today':
        $dateFilter = ' AND DATE(created_at) = CURDATE()';
        break;
    case 'week':
        $dateFilter = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        break;
    case 'month':
        $dateFilter = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        break;
    case 'year':
        $dateFilter = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
        break;
    case 'all':
    default:
        $dateFilter = '';
        break;
}

// 1. Fetch overall listing statistics
$listingsStats = db()->query("
    SELECT 
        COUNT(*) as total_listings,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as not_finished,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as stopped,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as pending_payment,
        SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as completed
    FROM listings
    WHERE 1=1 $dateFilter
")->fetch(PDO::FETCH_ASSOC);

// 2. Fetch revenue statistics from invoices
$revenueStats = db()->query("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN status IN ('draft', 'sent', 'overdue') THEN total_amount ELSE 0 END), 0) as pending_payments,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
        COUNT(CASE WHEN status IN ('draft', 'sent', 'overdue') THEN 1 END) as pending_invoices
    FROM invoices
    WHERE 1=1 $dateFilter
")->fetch(PDO::FETCH_ASSOC);

// 3. Fetch revenue breakdown by period for chart
$revenueByPeriod = [];
if ($period === 'month' || $period === 'all') {
    $revenueByPeriod = db()->query("
        SELECT 
            DATE_FORMAT(invoice_date, '%Y-%m') as period,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as revenue
        FROM invoices
        WHERE invoice_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
        ORDER BY period DESC
        LIMIT 12
    ")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($period === 'week') {
    $revenueByPeriod = db()->query("
        SELECT 
            DATE(invoice_date) as period,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as revenue
        FROM invoices
        WHERE invoice_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(invoice_date)
        ORDER BY period DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Fetch per-user statistics
$userStats = db()->query("
    SELECT 
        u.id,
        u.username,
        u.mobile_number,
        COUNT(l.id) as total_listings,
        SUM(CASE WHEN l.status = 1 THEN 1 ELSE 0 END) as not_finished,
        SUM(CASE WHEN l.status = 2 THEN 1 ELSE 0 END) as stopped,
        SUM(CASE WHEN l.status = 3 THEN 1 ELSE 0 END) as pending_payment,
        SUM(CASE WHEN l.status = 4 THEN 1 ELSE 0 END) as completed,
        COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN i.status IN ('draft', 'sent', 'overdue') THEN i.total_amount ELSE 0 END), 0) as pending_revenue
    FROM users u
    LEFT JOIN listings l ON u.id = l.user_id" . ($dateFilter ? " AND l.created_at >= DATE_SUB(NOW(), INTERVAL " . ($period === 'today' ? '1 DAY' : ($period === 'week' ? '7 DAY' : ($period === 'month' ? '30 DAY' : ($period === 'year' ? '1 YEAR' : '100 YEAR')))) . ")" : "") . "
    LEFT JOIN invoices i ON l.id = i.listing_id" . ($dateFilter ? " AND i.created_at >= DATE_SUB(NOW(), INTERVAL " . ($period === 'today' ? '1 DAY' : ($period === 'week' ? '7 DAY' : ($period === 'month' ? '30 DAY' : ($period === 'year' ? '1 YEAR' : '100 YEAR')))) . ")" : "") . "
    WHERE u.is_admin = 0
    GROUP BY u.id, u.username, u.mobile_number
    HAVING total_listings > 0
    ORDER BY total_listings DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Top performing users by revenue
$topUsers = db()->query("
    SELECT 
        u.id,
        u.username,
        COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_revenue,
        COUNT(DISTINCT l.id) as total_listings
    FROM users u
    LEFT JOIN listings l ON u.id = l.user_id
    LEFT JOIN invoices i ON l.id = i.listing_id
    WHERE u.is_admin = 0
    GROUP BY u.id, u.username
    HAVING total_revenue > 0
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 6. Invoice status breakdown for verification
$invoiceBreakdown = db()->query("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as total_amount
    FROM invoices
    WHERE 1=1 $dateFilter
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// 7. Debug: Check all invoices to verify status values
$allInvoices = db()->query("
    SELECT 
        i.id,
        i.invoice_number,
        i.status,
        i.total_amount,
        l.title as listing_title,
        l.status as listing_status
    FROM invoices i
    JOIN listings l ON i.listing_id = l.id
    ORDER BY i.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Format numbers
$totalRevenue = number_format((float)($revenueStats['total_revenue'] ?? 0), 2);
$pendingPayments = number_format((float)($revenueStats['pending_payments'] ?? 0), 2);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Statistics • Yoma Electronics</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <!-- Flash Message -->
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg shadow">
        <?= htmlspecialchars($_SESSION['flash']) ?>
        <?php unset($_SESSION['flash']); ?>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold text-gray-900">Statistics Dashboard</h1>
      
      <!-- Period Filter -->
      <div class="flex items-center space-x-2">
        <label class="text-sm font-medium text-gray-700">Period:</label>
        <select id="periodFilter" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69;" onchange="window.location.href='?period='+this.value">
          <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
          <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
          <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Last Year</option>
        </select>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Listings -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Listings</p>
            <p class="text-3xl font-bold text-gray-900"><?= number_format((int)($listingsStats['total_listings'] ?? 0)) ?></p>
          </div>
          <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: #692f69;">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Completed -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Completed</p>
            <p class="text-3xl font-bold text-green-600"><?= number_format((int)($listingsStats['completed'] ?? 0)) ?></p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Payment -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Pending Payment</p>
            <p class="text-3xl font-bold text-yellow-600"><?= number_format((int)($listingsStats['pending_payment'] ?? 0)) ?></p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Stopped -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Stopped</p>
            <p class="text-3xl font-bold text-red-600"><?= number_format((int)($listingsStats['stopped'] ?? 0)) ?></p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Revenue Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue Cards -->
      <div class="space-y-4">
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
          <p class="text-sm font-medium opacity-90">Total Revenue (Paid)</p>
          <p class="text-4xl font-bold mt-2">LKR <?= $totalRevenue ?></p>
          <p class="text-sm mt-1 opacity-75"><?= number_format((int)($revenueStats['paid_invoices'] ?? 0)) ?> paid invoices</p>
        </div>
        
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg shadow-lg p-6 text-white">
          <p class="text-sm font-medium opacity-90">Pending Payments</p>
          <p class="text-4xl font-bold mt-2">LKR <?= $pendingPayments ?></p>
          <p class="text-sm mt-1 opacity-75"><?= number_format((int)($revenueStats['pending_invoices'] ?? 0)) ?> pending invoices</p>
        </div>
      </div>
    </div>

    <!-- Invoice Status Breakdown (Debug Info) -->
    <?php if (!empty($invoiceBreakdown)): ?>
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Status Breakdown</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php 
        $statusColors = [
          'draft' => 'bg-gray-100 text-gray-800',
          'sent' => 'bg-blue-100 text-blue-800',
          'paid' => 'bg-green-100 text-green-800',
          'overdue' => 'bg-red-100 text-red-800'
        ];
        foreach ($invoiceBreakdown as $breakdown): 
          $status = $breakdown['status'];
          $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
        ?>
        <div class="<?= $colorClass ?> rounded-lg p-4">
          <p class="text-xs font-medium uppercase opacity-75"><?= htmlspecialchars($status) ?></p>
          <p class="text-2xl font-bold mt-1"><?= number_format((int)$breakdown['count']) ?></p>
          <p class="text-xs mt-1">LKR <?= number_format((float)$breakdown['total_amount'], 2) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Invoices Debug Table -->
    <?php if (!empty($allInvoices)): ?>
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Invoices (Debug)</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="py-2 px-3 text-left font-semibold text-gray-700">Invoice #</th>
              <th class="py-2 px-3 text-left font-semibold text-gray-700">Listing</th>
              <th class="py-2 px-3 text-left font-semibold text-gray-700">Invoice Status</th>
              <th class="py-2 px-3 text-left font-semibold text-gray-700">Listing Status</th>
              <th class="py-2 px-3 text-right font-semibold text-gray-700">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allInvoices as $inv): 
              $listingStatuses = [1 => 'Not Finished', 2 => 'Stopped', 3 => 'Pending Payment', 4 => 'Completed & Paid'];
            ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-2 px-3">
                <a href="/Kaveesha/view_invoice.php?id=<?= (int)$inv['id'] ?>" class="text-blue-600 hover:underline">
                  <?= htmlspecialchars($inv['invoice_number']) ?>
                </a>
              </td>
              <td class="py-2 px-3"><?= htmlspecialchars($inv['listing_title']) ?></td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs rounded <?php 
                  switch($inv['status']) {
                    case 'paid': echo 'bg-green-100 text-green-800'; break;
                    case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                    case 'draft': echo 'bg-gray-100 text-gray-800'; break;
                    case 'overdue': echo 'bg-red-100 text-red-800'; break;
                    default: echo 'bg-yellow-100 text-yellow-800';
                  }
                ?>">
                  <?= htmlspecialchars($inv['status']) ?>
                </span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs rounded <?php 
                  switch((int)$inv['listing_status']) {
                    case 4: echo 'bg-green-100 text-green-800'; break;
                    case 3: echo 'bg-yellow-100 text-yellow-800'; break;
                    case 2: echo 'bg-red-100 text-red-800'; break;
                    default: echo 'bg-blue-100 text-blue-800';
                  }
                ?>">
                  <?= $listingStatuses[(int)$inv['listing_status']] ?? 'Unknown' ?>
                </span>
              </td>
              <td class="py-2 px-3 text-right font-semibold">LKR <?= number_format((float)$inv['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="text-sm text-gray-600 mt-4">
          <strong>Note:</strong> Only invoices with status = "paid" are counted in Total Revenue. 
          If you marked a listing as "Completed & Received Payment" but the invoice status is still "draft" or "sent", 
          you need to update the invoice status to "paid" in the View Invoice page.
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top Users by Revenue -->
    <?php if (!empty($topUsers)): ?>
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Users by Revenue</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-left font-semibold text-gray-700">Rank</th>
              <th class="py-3 px-4 text-left font-semibold text-gray-700">User</th>
              <th class="py-3 px-4 text-left font-semibold text-gray-700">Total Listings</th>
              <th class="py-3 px-4 text-left font-semibold text-gray-700">Total Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topUsers as $idx => $user): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-3 px-4">
                <?php if ($idx === 0): ?>
                  <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-400 text-white rounded-full font-bold text-xs">1</span>
                <?php elseif ($idx === 1): ?>
                  <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-400 text-white rounded-full font-bold text-xs">2</span>
                <?php elseif ($idx === 2): ?>
                  <span class="inline-flex items-center justify-center w-6 h-6 bg-orange-600 text-white rounded-full font-bold text-xs">3</span>
                <?php else: ?>
                  <span class="text-gray-600"><?= $idx + 1 ?></span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-4 font-medium"><?= htmlspecialchars($user['username']) ?></td>
              <td class="py-3 px-4"><?= number_format((int)$user['total_listings']) ?></td>
              <td class="py-3 px-4 font-semibold text-green-600">LKR <?= number_format((float)$user['total_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Detailed User Statistics -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">User Listing Statistics</h3>
      <?php if (empty($userStats)): ?>
        <p class="text-center text-gray-500 py-8">No user data available for the selected period.</p>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-left font-semibold text-gray-700">User</th>
              <th class="py-3 px-4 text-left font-semibold text-gray-700">Mobile</th>
              <th class="py-3 px-4 text-center font-semibold text-gray-700">Total</th>
              <th class="py-3 px-4 text-center font-semibold text-gray-700">In Progress</th>
              <th class="py-3 px-4 text-center font-semibold text-gray-700">Stopped</th>
              <th class="py-3 px-4 text-center font-semibold text-gray-700">Pending Pay</th>
              <th class="py-3 px-4 text-center font-semibold text-gray-700">Completed</th>
              <th class="py-3 px-4 text-right font-semibold text-gray-700">Revenue</th>
              <th class="py-3 px-4 text-right font-semibold text-gray-700">Pending</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($userStats as $user): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-3 px-4">
                <a href="/Kaveesha/admin_user_listings.php?user_id=<?= (int)$user['id'] ?>" class="font-medium hover:underline" style="color: #692f69;">
                  <?= htmlspecialchars($user['username']) ?>
                </a>
              </td>
              <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($user['mobile_number']) ?></td>
              <td class="py-3 px-4 text-center font-semibold"><?= number_format((int)$user['total_listings']) ?></td>
              <td class="py-3 px-4 text-center">
                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                  <?= number_format((int)$user['not_finished']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <span class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">
                  <?= number_format((int)$user['stopped']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">
                  <?= number_format((int)$user['pending_payment']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">
                  <?= number_format((int)$user['completed']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-right font-semibold text-green-600">
                LKR <?= number_format((float)$user['total_revenue'], 2) ?>
              </td>
              <td class="py-3 px-4 text-right font-semibold text-orange-600">
                LKR <?= number_format((float)$user['pending_revenue'], 2) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="bg-gray-50 font-bold">
              <td colspan="2" class="py-3 px-4 text-left">TOTALS</td>
              <td class="py-3 px-4 text-center"><?= number_format(array_sum(array_column($userStats, 'total_listings'))) ?></td>
              <td class="py-3 px-4 text-center"><?= number_format(array_sum(array_column($userStats, 'not_finished'))) ?></td>
              <td class="py-3 px-4 text-center"><?= number_format(array_sum(array_column($userStats, 'stopped'))) ?></td>
              <td class="py-3 px-4 text-center"><?= number_format(array_sum(array_column($userStats, 'pending_payment'))) ?></td>
              <td class="py-3 px-4 text-center"><?= number_format(array_sum(array_column($userStats, 'completed'))) ?></td>
              <td class="py-3 px-4 text-right text-green-600">
                LKR <?= number_format(array_sum(array_column($userStats, 'total_revenue')), 2) ?>
              </td>
              <td class="py-3 px-4 text-right text-orange-600">
                LKR <?= number_format(array_sum(array_column($userStats, 'pending_revenue')), 2) ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Status Distribution Chart -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Listing Status Distribution</h3>
      <div class="max-w-md mx-auto">
        <canvas id="statusChart" height="300"></canvas>
      </div>
    </div>
  </main>

  <script>
    // Status Distribution Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['In Progress', 'Stopped', 'Pending Payment', 'Completed'],
        datasets: [{
          data: [
            <?= (int)($listingsStats['not_finished'] ?? 0) ?>,
            <?= (int)($listingsStats['stopped'] ?? 0) ?>,
            <?= (int)($listingsStats['pending_payment'] ?? 0) ?>,
            <?= (int)($listingsStats['completed'] ?? 0) ?>
          ],
          backgroundColor: [
            '#3b82f6',
            '#ef4444',
            '#f59e0b',
            '#10b981'
          ],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>
</html>
