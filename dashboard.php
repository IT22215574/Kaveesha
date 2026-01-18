<?php
require_once __DIR__ . '/config.php';
require_login();
// Fetch live username for greeting
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
  <title>Home • MC YOMA electronic</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 to-gray-50 relative">
  <?php include __DIR__ . '/includes/user_nav.php'; ?>

  <!-- Banner Section -->
  <div class="relative">
    <div class="w-full h-96 md:h-[450px] lg:h-[600px] overflow-hidden">
      <img src="/Kaveesha/logo/banner.jpeg" alt="MC YOMA electronic Banner" class="w-full h-full object-cover" style="object-position: center 20%;">
      <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
        <div class="text-center text-white">
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4">Welcome to MC YOMA electronic</h1>
          <p class="text-lg md:text-xl lg:text-2xl">Your trusted partner for electronic services</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <main class="max-w-6xl mx-auto px-4 pt-8 pb-10">
    <!-- Welcome Section -->
    <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl border border-gray-100 p-6 mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-2">Welcome back, <?= htmlspecialchars($displayName) ?></h2>
          <p class="text-gray-600">Manage your electronic service listings and track their progress</p>
        </div>
        <div>
          <span id="totalCount" class="text-sm text-gray-600"></span>
        </div>
      </div>
    </div>

    <!-- Invoice Notifications Section -->
    <div id="invoiceSection" class="hidden bg-gradient-to-r from-purple-50 to-pink-50 backdrop-blur rounded-xl shadow-xl border border-purple-200 p-6 mb-8">
      <!-- Loading state -->
      <div id="invoiceLoading" class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2" style="border-bottom-color: #692f69;"></div>
        <p class="mt-2 text-sm text-gray-600">Checking for invoices...</p>
      </div>

      <!-- Invoice Alert -->
      <div id="invoiceAlert" class="hidden">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              📋 You have <span id="invoiceCount" style="color: #692f69;">0</span> pending invoice(s)
            </h3>
            <div id="latestInvoiceInfo" class="mb-4 p-3 bg-white rounded-lg border border-blue-200">
              <p class="text-sm text-gray-600 mb-1">Latest Invoice:</p>
              <p class="font-semibold text-gray-900" id="latestInvoiceListing"></p>
              <p class="text-sm text-gray-500 mt-1">
                Invoice #<span id="latestInvoiceNumber"></span> • 
                <span id="latestInvoiceDate"></span> • 
                Status: <span id="latestInvoiceStatus" class="font-semibold"></span>
              </p>
              <p class="text-lg font-bold mt-2" style="color: #692f69;">
                Amount: Rs. <span id="latestInvoiceAmount"></span>
              </p>
            </div>
            <button onclick="showInvoiceModal()" class="inline-flex items-center px-4 py-2 text-white rounded-lg transition shadow-md" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              View All Invoices
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- My Listings Section -->
    <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl border border-gray-100 p-6">
      <h3 class="text-xl font-semibold text-gray-900 mb-6">My Service Listings</h3>
      
      <!-- Loading state -->
      <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2" style="border-bottom-color: #692f69;"></div>
        <p class="mt-2 text-gray-600">Loading your listings...</p>
      </div>

      <!-- Error state -->
      <div id="error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
        <p id="errorMessage">Failed to load listings.</p>
      </div>

      <!-- Empty state -->
      <div id="empty" class="hidden text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">📋</div>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">No service requests found</h4>
        <p class="text-gray-600 mb-4">You don't have any service requests yet. Contact us to get started!</p>
        <a href="/Kaveesha/messages.php" class="inline-block px-6 py-2 text-white rounded-lg transition" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
          Send a Message
        </a>
      </div>

      <!-- Listings grid -->
      <div id="listingsContainer" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Listings will be populated here -->
      </div>
    </div>
  </main>

  <!-- Invoice Modal -->
  <div id="invoiceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-900">📋 My Invoices</h3>
        <button onclick="closeInvoiceModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
          &times;
        </button>
      </div>
      <div class="p-6">
        <div id="invoiceListContainer">
          <!-- Invoices will be populated here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for full description -->
  <div id="descriptionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
        <h3 id="modalTitle" class="text-xl font-semibold text-gray-900"></h3>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
          &times;
        </button>
      </div>
      <div class="p-6">
        <!-- Image Slider -->
        <div id="imageSliderContainer" class="mb-4 relative">
          <div id="modalImageSlider" class="relative rounded-lg overflow-hidden bg-gray-100"></div>
          
          <!-- Previous Arrow -->
          <button id="prevImageBtn" onclick="changeImage(-1)" class="hidden absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition-all z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          
          <!-- Next Arrow -->
          <button id="nextImageBtn" onclick="changeImage(1)" class="hidden absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition-all z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
          
          <!-- Image Counter -->
          <div id="imageCounter" class="hidden absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full">
            <span id="currentImageNum">1</span> / <span id="totalImages">1</span>
          </div>
        </div>
        
        <div class="mb-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-2">Description:</h4>
          <p id="modalDescription" class="text-gray-600 whitespace-pre-wrap"></p>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <span id="modalStatus" class="px-3 py-1 text-sm font-medium rounded-full border"></span>
          <span id="modalDate" class="text-sm text-gray-500"></span>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    const APP_BASE = '/Kaveesha';

    function toAppUrl(path) {
      if (!path) return '';
      const raw = String(path);
      if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
      if (raw.startsWith(APP_BASE + '/')) return raw;

      const trimmed = raw.replace(/^\/+/, '');
      if (trimmed.startsWith('Kaveesha/') || trimmed.startsWith('kaveesha/')) {
        return '/' + trimmed;
      }
      return `${APP_BASE}/${trimmed}`;
    }

    let listings = [];
    let invoices = [];

    // Load invoices
    async function loadInvoices() {
      try {
          const response = await fetch('user_invoices_api.php');

          if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        
        if (!data.success) {
          throw new Error(data.error || 'Failed to load invoices');
        }

        invoices = data.invoices || [];
        const unreadCount = data.unread_count || 0;
        const latestInvoice = data.latest_invoice;

        document.getElementById('invoiceLoading').classList.add('hidden');

        // Show invoice section if there are pending invoices
        if (unreadCount > 0 && latestInvoice) {
          document.getElementById('invoiceSection').classList.remove('hidden');
          document.getElementById('invoiceAlert').classList.remove('hidden');
          document.getElementById('invoiceCount').textContent = unreadCount;
          document.getElementById('latestInvoiceListing').textContent = latestInvoice.listing_title;
          document.getElementById('latestInvoiceNumber').textContent = latestInvoice.invoice_number;
          document.getElementById('latestInvoiceDate').textContent = formatDate(latestInvoice.invoice_date);
          document.getElementById('latestInvoiceStatus').textContent = latestInvoice.status.toUpperCase();
          document.getElementById('latestInvoiceAmount').textContent = parseFloat(latestInvoice.total_amount).toFixed(2);
          
          // Set status color
          const statusColors = {
            'sent': 'text-blue-600',
            'overdue': 'text-red-600',
            'paid': 'text-green-600',
            'draft': 'text-gray-600'
          };
          const statusEl = document.getElementById('latestInvoiceStatus');
          statusEl.className = 'font-semibold ' + (statusColors[latestInvoice.status] || 'text-gray-600');
        }

      } catch (err) {
        console.error('Error loading invoices:', err);
        document.getElementById('invoiceLoading').classList.add('hidden');
      }
    }

    function showInvoiceModal() {
      const modal = document.getElementById('invoiceModal');
      const container = document.getElementById('invoiceListContainer');
      
      if (invoices.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">No invoices found.</p>';
      } else {
        container.innerHTML = invoices.map(invoice => {
          const statusColors = {
            'draft': 'bg-gray-100 text-gray-800 border-gray-300',
            'sent': 'bg-blue-100 text-blue-800 border-blue-300',
            'paid': 'bg-green-100 text-green-800 border-green-300',
            'overdue': 'bg-red-100 text-red-800 border-red-300'
          };
          const statusClass = statusColors[invoice.status] || 'bg-gray-100 text-gray-800 border-gray-300';
          
          return `
            <div class="mb-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition bg-white">
              <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900 mb-1">${escapeHtml(invoice.listing_title)}</h4>
                  <p class="text-sm text-gray-600">Invoice #${escapeHtml(invoice.invoice_number)}</p>
                  <p class="text-xs text-gray-500 mt-1">
                    Date: ${formatDate(invoice.invoice_date)} • Due: ${formatDate(invoice.due_date)}
                  </p>
                </div>
                <span class="px-3 py-1 text-xs font-semibold rounded-full border ${statusClass}">
                  ${invoice.status.toUpperCase()}
                </span>
              </div>
              <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
                <span class="text-lg font-bold" style="color: #692f69;">
                  Rs. ${parseFloat(invoice.total_amount).toFixed(2)}
                </span>
                 <a href="/Kaveesha/view_invoice.php?id=${invoice.id}" 
                   class="inline-flex items-center px-4 py-2 text-white text-sm rounded-lg transition" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  View Invoice
                </a>
              </div>
            </div>
          `;
        }).join('');
      }
      
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeInvoiceModal() {
      document.getElementById('invoiceModal').classList.add('hidden');
      document.body.style.overflow = 'auto';
    }

    // Close invoice modal on background click
    document.getElementById('invoiceModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeInvoiceModal();
      }
    });

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
        const response = await fetch('user_listings_api.php');

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
      const container = document.getElementById('listingsContainer');
      
      container.innerHTML = listings.map((listing, index) => {
        const statusColors = {
          1: 'bg-yellow-100 text-yellow-800 border-yellow-200',
          2: 'bg-red-100 text-red-800 border-red-200',
          3: 'bg-blue-100 text-blue-800 border-blue-200',
          4: 'bg-green-100 text-green-800 border-green-200'
        };
        
        const statusClass = statusColors[listing.status] || 'bg-gray-100 text-gray-800 border-gray-200';
        
        // Display first available image
        const imagePath = listing.image_path || listing.image_path_2 || listing.image_path_3;
        // Database stores paths as 'uploads/filename'
        const imageHtml = imagePath 
          ? `<img src="${toAppUrl(imagePath)}" alt="${escapeHtml(listing.title)}" class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity" onerror="this.onerror=null;this.src='${toAppUrl('logo/logo2.png')}';" onclick="openModal(${index})">`
          : `<div class="w-full h-48 bg-gray-200 flex items-center justify-center cursor-pointer hover:bg-gray-300 transition-colors" onclick="openModal(${index})">
               <span class="text-gray-400 text-4xl">📷</span>
             </div>`;
        
        return `
          <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
            ${imageHtml}
            <div class="p-4">
              <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2">${escapeHtml(listing.title)}</h4>
              <div class="flex items-center justify-between mt-3">
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

    let currentImageIndex = 0;
    let currentImages = [];

    function openModal(index) {
      const listing = listings[index];
      const modal = document.getElementById('descriptionModal');
      
      // Set modal content
      document.getElementById('modalTitle').textContent = listing.title;
      document.getElementById('modalDescription').textContent = listing.description || 'No description available';
      
      // Gather all available images
      currentImages = [];
      if (listing.image_path) currentImages.push(listing.image_path);
      if (listing.image_path_2) currentImages.push(listing.image_path_2);
      if (listing.image_path_3) currentImages.push(listing.image_path_3);
      
      // Reset to first image
      currentImageIndex = 0;
      
      // Setup image slider
      if (currentImages.length > 0) {
        displayCurrentImage();
        
        // Show/hide navigation arrows and counter
        const prevBtn = document.getElementById('prevImageBtn');
        const nextBtn = document.getElementById('nextImageBtn');
        const counter = document.getElementById('imageCounter');
        
        if (currentImages.length > 1) {
          prevBtn.classList.remove('hidden');
          nextBtn.classList.remove('hidden');
          counter.classList.remove('hidden');
          document.getElementById('totalImages').textContent = currentImages.length;
        } else {
          prevBtn.classList.add('hidden');
          nextBtn.classList.add('hidden');
          counter.classList.add('hidden');
        }
      } else {
        document.getElementById('modalImageSlider').innerHTML = '<div class="w-full h-64 flex items-center justify-center text-gray-400"><span class="text-6xl">📷</span></div>';
        document.getElementById('prevImageBtn').classList.add('hidden');
        document.getElementById('nextImageBtn').classList.add('hidden');
        document.getElementById('imageCounter').classList.add('hidden');
      }
      
      // Set status
      const statusColors = {
        1: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        2: 'bg-red-100 text-red-800 border-red-200',
        3: 'bg-blue-100 text-blue-800 border-blue-200',
        4: 'bg-green-100 text-green-800 border-green-200'
      };
      const statusClass = statusColors[listing.status] || 'bg-gray-100 text-gray-800 border-gray-200';
      const modalStatus = document.getElementById('modalStatus');
      modalStatus.textContent = listing.status_text;
      modalStatus.className = `px-3 py-1 text-sm font-medium rounded-full border ${statusClass}`;
      
      // Set date
      document.getElementById('modalDate').textContent = formatDate(listing.created_at);
      
      // Show modal
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function displayCurrentImage() {
      const slider = document.getElementById('modalImageSlider');
      const imagePath = currentImages[currentImageIndex];
      
      slider.innerHTML = `<img src="${toAppUrl(imagePath)}" alt="Image ${currentImageIndex + 1}" class="w-full h-auto max-h-96 object-contain" onerror="this.src='${toAppUrl('logo/logo2.png')}';">`;
      
      // Update counter
      document.getElementById('currentImageNum').textContent = currentImageIndex + 1;
    }

    function changeImage(direction) {
      currentImageIndex += direction;
      
      // Wrap around
      if (currentImageIndex < 0) {
        currentImageIndex = currentImages.length - 1;
      } else if (currentImageIndex >= currentImages.length) {
        currentImageIndex = 0;
      }
      
      displayCurrentImage();
    }

    function closeModal() {
      document.getElementById('descriptionModal').classList.add('hidden');
      document.body.style.overflow = 'auto';
    }

    // Close modal on background click
    document.getElementById('descriptionModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Close modals on Escape key and arrow keys for image navigation
    document.addEventListener('keydown', function(e) {
      const descModal = document.getElementById('descriptionModal');
      const invModal = document.getElementById('invoiceModal');
      
      if (!descModal.classList.contains('hidden')) {
        if (e.key === 'Escape') {
          closeModal();
        } else if (e.key === 'ArrowLeft') {
          if (currentImages.length > 1) changeImage(-1);
        } else if (e.key === 'ArrowRight') {
          if (currentImages.length > 1) changeImage(1);
        }
      } else if (!invModal.classList.contains('hidden')) {
        if (e.key === 'Escape') {
          closeInvoiceModal();
        }
      }
    });

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

    // Load data on page load
    document.addEventListener('DOMContentLoaded', () => {
      loadInvoices();
      loadListings();
      
      // Check if there's a listing_id in URL to auto-open
      const urlParams = new URLSearchParams(window.location.search);
      const listingId = urlParams.get('listing_id');
      if (listingId) {
        // Wait for listings to load, then open the modal
        const checkListingsInterval = setInterval(() => {
          if (listings.length > 0) {
            clearInterval(checkListingsInterval);
            const listingIndex = listings.findIndex(l => l.id == listingId);
            if (listingIndex !== -1) {
              openModal(listingIndex);
              // Clean URL without reloading
              window.history.replaceState({}, document.title, '/dashboard.php');
            }
          }
        }, 100);
        
        // Timeout after 5 seconds
        setTimeout(() => clearInterval(checkListingsInterval), 5000);
      }
    });
  </script>

  <style>
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    /* Smooth modal animation */
    #descriptionModal:not(.hidden) {
      animation: fadeIn 0.2s ease-in-out;
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }
  </style>
</body>
</html>