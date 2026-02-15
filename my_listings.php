<?php
require_once __DIR__ . '/config.php';
require_login();

// Fetch user info for display
$displayName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : '';
if (!empty($_SESSION['user_id'])) {
  try {
    $stmt = db()->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    if ($row = $stmt->fetch()) {
      if (!empty($row['username'])) $displayName = (string)$row['username'];
    }
  } catch (Throwable $e) {}
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Listings • MC YOMA electronic</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 to-gray-50 relative">
  <?php include __DIR__ . '/includes/user_nav.php'; ?>

  <!-- Main content -->
  <main class="max-w-6xl mx-auto px-4 pt-8 pb-10">
    <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl border border-gray-100 p-8">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900">My Listings</h2>
        <span id="totalCount" class="block mt-1 text-sm text-gray-600"></span>
      </div>
      
      <!-- Loading state -->
      <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-b-2" style="border-bottom-color: #692f69"></div>
        <p class="mt-2 text-gray-600">Loading your listings...</p>
      </div>

      <!-- Error state -->
      <div id="error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
        <p id="errorMessage">Failed to load listings.</p>
      </div>

      <!-- Empty state -->
      <div id="empty" class="hidden text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">📋</div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No listings found</h3>
        <p class="text-gray-600 mb-4">You haven't created any listings yet.</p>
        <a href="/dashboard.php" class="inline-block px-6 py-2 text-white rounded-lg transition" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
          Go to Dashboard
        </a>
      </div>

      <!-- Listings grid -->
      <div id="listingsContainer" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Listings will be populated here -->
      </div>
    </div>
  </main>

  <script>
    const APP_BASE = '';

    function toAppUrl(path) {
      if (!path) return null;
      const raw = String(path);
      if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
      if (raw.startsWith('/')) return raw;

      // Relative path - add leading slash
      return '/' + raw;
    }

    let listings = [];

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
        const response = await fetch('/user_listings_api.php');
        
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

    function resolveImagePath(imagePath) {
      if (!imagePath) return null;
      // Remove leading slashes
      let clean = imagePath.replace(/^\/+/, '');
      // If path already starts with uploads/, use it directly
      if (clean.startsWith('uploads/')) {
        return toAppUrl(clean);
      }
      // Otherwise, assume it's just the filename
      return toAppUrl(`uploads/${clean}`);
    }

    function renderListings() {
      const container = document.getElementById('listingsContainer');
      
      container.innerHTML = listings.map(listing => {
        const statusColors = {
          1: 'bg-yellow-100 text-yellow-800 border-yellow-200',
          2: 'bg-red-100 text-red-800 border-red-200',
          3: 'bg-blue-100 text-blue-800 border-blue-200',
          4: 'bg-green-100 text-green-800 border-green-200'
        };
        
        const statusClass = statusColors[listing.status] || 'bg-gray-100 text-gray-800 border-gray-200';
        
        // Display first available image
        const fallbackImage = toAppUrl('logo/logo2.png');
        const imagePath = listing.image_path || listing.image_path_2 || listing.image_path_3;
        // Database stores as 'uploads/filename'
        const imageSrc = imagePath ? toAppUrl(imagePath) : fallbackImage;
        const imageHtml = imagePath
          ? `<img src="${imageSrc}" alt="${escapeHtml(listing.title)}" class="w-full h-48 object-cover" onerror="this.onerror=null;this.src='${fallbackImage}';">`
          : `<img src="${fallbackImage}" alt="No image available" class="w-full h-48 object-cover opacity-70">`;
        
        return `
          <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
            ${imageHtml}
            <div class="p-4">
              <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">${escapeHtml(listing.title)}</h3>
              <p class="text-gray-600 text-sm mb-3 line-clamp-3">${escapeHtml(listing.description || 'No description')}</p>
              <div class="flex items-center justify-between">
                <span class="px-2 py-1 text-xs font-medium rounded-full border ${statusClass}">
                  ${escapeHtml(listing.status_text)}
                </span>
                <span class="text-xs text-gray-500">
                  ${formatDate(listing.created_at)}
                </span>
              </div>
            </div>
          </div>
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

  <style>
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
</body>
</html>