<?php
require_once __DIR__ . '/config.php';
require_admin();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if (!$userId) {
    header('Location: /Kaveesha/admin.php');
    exit;
}

// Fetch user info
try {
    $userStmt = db()->prepare('SELECT id, username, mobile_number FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        $_SESSION['flash'] = 'User not found.';
        header('Location: /Kaveesha/admin.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['flash'] = 'Error loading user: ' . $e->getMessage();
    header('Location: /Kaveesha/admin.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Listings: <?= htmlspecialchars($user['username']) ?> • Admin • Kaveesha</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Listings for <?= htmlspecialchars($user['username']) ?></h1>
          <p class="text-gray-600">Mobile: <?= htmlspecialchars($user['mobile_number']) ?></p>
        </div>
        <div class="flex items-center gap-4">
          <span id="totalCount" class="text-sm text-gray-600"></span>
          <a href="/Kaveesha/add_listing.php?user_id=<?= $userId ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Add New Listing
          </a>
          <a href="/Kaveesha/admin.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            Back to Users
          </a>
        </div>
      </div>
      
      <!-- Loading state -->
      <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="mt-2 text-gray-600">Loading listings...</p>
      </div>

      <!-- Error state -->
      <div id="error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 mb-6">
        <p id="errorMessage">Failed to load listings.</p>
      </div>

      <!-- Empty state -->
      <div id="empty" class="hidden text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">📋</div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No listings found</h3>
        <p class="text-gray-600 mb-4">This user hasn't created any listings yet.</p>
        <a href="/Kaveesha/add_listing.php?user_id=<?= $userId ?>" class="inline-block px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
          Create First Listing
        </a>
      </div>

      <!-- Listings table -->
      <div id="listingsContainer" class="hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoices</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody id="listingsTableBody" class="bg-white divide-y divide-gray-200">
              <!-- Listings will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    let listings = [];
    const userId = <?= $userId ?>;

    async function loadListings() {
      const loading = document.getElementById('loading');
      const error = document.getElementById('error');
      const empty = document.getElementById('empty');
      const container = document.getElementById('listingsContainer');
      const totalCount = document.getElementById('totalCount');

      // Show loading state
      loading.classList.remove('hidden');
      error.classList.add('hidden');
      empty.classList.add('hidden');
      container.classList.add('hidden');

      try {
        const response = await fetch(`/Kaveesha/admin_user_listings_api.php?user_id=${userId}`);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (!data.success) {
          throw new Error(data.error || 'Unknown error occurred');
        }

        listings = data.listings || [];
        
        // Update total count
        totalCount.textContent = `Total: ${data.total || 0} listings`;

        // Hide loading
        loading.classList.add('hidden');

        if (listings.length === 0) {
          empty.classList.remove('hidden');
        } else {
          renderListings();
          container.classList.remove('hidden');
        }

      } catch (err) {
        console.error('Error loading listings:', err);
        loading.classList.add('hidden');
        document.getElementById('errorMessage').textContent = err.message;
        error.classList.remove('hidden');
      }
    }

    function renderListings() {
      const tbody = document.getElementById('listingsTableBody');
      
      tbody.innerHTML = listings.map(listing => {
        const statusColors = {
          1: 'bg-yellow-100 text-yellow-800',
          2: 'bg-red-100 text-red-800',
          3: 'bg-blue-100 text-blue-800',
          4: 'bg-green-100 text-green-800'
        };
        
        const statusClass = statusColors[listing.status] || 'bg-gray-100 text-gray-800';
        const invoiceText = listing.invoice_count > 0 ? `${listing.invoice_count} invoice(s)` : 'No invoices';
        
        return `
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              ${listing.id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">${escapeHtml(listing.title)}</div>
              <div class="text-sm text-gray-500 truncate max-w-xs" title="${escapeHtml(listing.description || '')}">
                ${escapeHtml(listing.description ? listing.description.substring(0, 60) + (listing.description.length > 60 ? '...' : '') : 'No description')}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                ${escapeHtml(listing.status_text)}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              ${invoiceText}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              ${formatDate(listing.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex space-x-2">
                <a href="/Kaveesha/add_listing.php?user_id=${userId}&listing_id=${listing.id}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="/Kaveesha/invoices.php?listing_id=${listing.id}" class="text-green-600 hover:text-green-900">Invoices</a>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDate(dateString) {
      try {
        return new Date(dateString).toLocaleDateString();
      } catch (e) {
        return dateString;
      }
    }

    // Load listings on page load
    document.addEventListener('DOMContentLoaded', loadListings);
  </script>
</body>
</html>