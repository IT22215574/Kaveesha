<?php
// Admin nav partial — include from admin pages after calling require_admin()
$current = basename($_SERVER['PHP_SELF']);
$isUsers = in_array($current, ['admin.php', 'admin_users.php'], true);
$isCreate = ($current === 'admin_create_user.php');

if (!function_exists('nav_link_classes')) {
    function nav_link_classes($active) {
        $base = 'text-sm px-3 py-1 rounded transition-colors';
        return $active
            ? $base . ' bg-indigo-600 text-white'
            : $base . ' text-gray-700 hover:bg-gray-100';
    }
}

// Use cached admin name from session (updated on login/profile changes)
$adminName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : 'Admin';
// Only fetch from DB if session cache is missing or expired
if (!empty($_SESSION['user_id']) && empty($_SESSION['cached_username'])) {
  try {
    $stmt = db()->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && isset($row['username']) && $row['username'] !== '') {
      $adminName = (string)$row['username'];
      $_SESSION['cached_username'] = $adminName; // Cache for future requests
    }
  } catch (Throwable $e) {
    // Fallback to session name on any error
  }
} elseif (!empty($_SESSION['cached_username'])) {
  $adminName = $_SESSION['cached_username'];
}
?>
<nav class="bg-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
  <!-- Admin name + role (live from DB) -->
  <div class="text-lg font-semibold"><?= htmlspecialchars($adminName) ?> — Yoma Electronics</div>

    <!-- Desktop nav -->
    <div class="hidden md:flex items-center space-x-8">
      <div class="flex items-center space-x-4">
        <a href="/Kaveesha/admin.php" class="<?= nav_link_classes($isUsers) ?>">Users</a>
        <a href="/Kaveesha/admin_create_user.php" class="<?= nav_link_classes($isCreate) ?>">Create User</a>
        <a href="/Kaveesha/admin_messages.php" class="<?= nav_link_classes($current === 'admin_messages.php') ?> relative">
          Messages
          <span class="admin-messages-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
        </a>
      </div>
      <div class="flex items-center space-x-4">
        <a href="/Kaveesha/dashboard.php" class="text-sm bg-gray-200 text-gray-800 px-3 py-1 rounded">Dashboard</a>
        <a href="/Kaveesha/logout.php" class="logout-link text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
      </div>
    </div>

    <!-- Mobile hamburger -->
    <button id="adminNavToggle" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-controls="adminMobileMenu" aria-expanded="false" aria-label="Open menu">
      <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="adminMobileMenu" class="md:hidden hidden border-t border-gray-200">
    <div class="px-4 py-4 space-y-3">
      <a href="/Kaveesha/admin.php" class="block w-full <?= $isUsers ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Users</a>
      <a href="/Kaveesha/admin_create_user.php" class="block w-full <?= $isCreate ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Create User</a>
      <a href="/Kaveesha/admin_messages.php" class="block w-full <?= ($current === 'admin_messages.php') ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded relative">
        Messages
        <span class="admin-messages-badge absolute top-0 right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
      </a>
      <div class="pt-2 mt-2 border-t border-gray-200 flex items-center space-x-2">
        <a href="/Kaveesha/dashboard.php" class="flex-1 text-center bg-gray-200 text-gray-800 px-3 py-2 rounded">Dashboard</a>
        <a href="/Kaveesha/logout.php" class="logout-link flex-1 text-center bg-red-500 text-white px-3 py-2 rounded">Logout</a>
      </div>
    </div>
  </div>

  <!-- Logout confirmation modal -->
  <div id="logoutConfirmModal" class="fixed inset-0 z-50 hidden" aria-labelledby="logoutConfirmTitle" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative mx-auto mt-28 w-[90%] max-w-sm rounded-lg bg-white shadow-xl">
      <div class="p-5">
        <h2 id="logoutConfirmTitle" class="text-lg font-semibold text-gray-900">Confirm logout</h2>
        <p class="mt-2 text-sm text-gray-600">Are you sure you want to log out?</p>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" id="logoutCancelBtn" class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
          <button type="button" id="logoutConfirmBtn" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
  (function(){
    // Mobile menu toggle
    const btn = document.getElementById('adminNavToggle');
    const menu = document.getElementById('adminMobileMenu');
    if (btn && menu) {
      btn.addEventListener('click', function(){
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
          menu.classList.remove('hidden');
          btn.setAttribute('aria-expanded', 'true');
        } else {
          menu.classList.add('hidden');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Logout confirm modal logic
    const modal = document.getElementById('logoutConfirmModal');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    let pendingHref = null;

    function openModal(href){
      pendingHref = href;
      if (modal) modal.classList.remove('hidden');
    }
    function closeModal(){
      if (modal) modal.classList.add('hidden');
      pendingHref = null;
    }
    function bindLogoutLinks(){
      document.querySelectorAll('a.logout-link').forEach(function(a){
        a.addEventListener('click', function(e){
          // If JS fails to load, normal navigation happens. Here we intercept.
          e.preventDefault();
          openModal(a.getAttribute('href'));
        });
      });
    }
    
    // Update unread message count for admin with debouncing
    let updateTimeout = null;
    function updateAdminUnreadCount() {
      // Debounce API calls to prevent excessive requests
      if (updateTimeout) clearTimeout(updateTimeout);
      updateTimeout = setTimeout(() => {
        fetch('/Kaveesha/messages_api.php?action=unread_count')
          .then(response => response.json())
          .then(data => {
            updateAdminBadges(data.unread_count);
          })
          .catch(error => {
            console.error('Error updating admin unread count:', error);
            // Retry after delay on error
            setTimeout(updateAdminUnreadCount, 10000);
          });
      }, 1000);
    }
    
    function updateAdminBadges(count) {
      const badges = document.querySelectorAll('.admin-messages-badge');
      badges.forEach(badge => {
        if (count > 0) {
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      });
    }
    
    // Setup real-time updates with fallback for admin
    let sseRetryTimeout = null;
    let sseRetryCount = 0;
    const maxRetries = 3;
    
    function setupAdminRealtimeUpdates() {
      if (typeof(EventSource) !== "undefined" && sseRetryCount < maxRetries) {
        const eventSource = new EventSource('/Kaveesha/messages_sse.php');
        
        eventSource.onopen = function() {
          sseRetryCount = 0; // Reset retry count on successful connection
        };
        
        eventSource.onmessage = function(event) {
          try {
            const data = JSON.parse(event.data);
            if (data.unread_count !== undefined) {
              updateAdminBadges(data.unread_count);
            }
          } catch (e) {
            console.error('Error parsing SSE data:', e);
          }
        };
        
        eventSource.onerror = function() {
          eventSource.close();
          sseRetryCount++;
          if (sseRetryCount < maxRetries) {
            // Exponential backoff retry
            const retryDelay = Math.pow(2, sseRetryCount) * 1000;
            sseRetryTimeout = setTimeout(setupAdminRealtimeUpdates, retryDelay);
          } else {
            // Fall back to periodic polling after max retries
            setInterval(updateAdminUnreadCount, 30000);
          }
        };
      } else {
        // No SSE support or max retries reached - use polling
        setInterval(updateAdminUnreadCount, 30000);
      }
    }
    
    // Check for unread messages on page load and setup real-time updates
    document.addEventListener('DOMContentLoaded', function() {
      updateAdminUnreadCount(); // Initial load
      setupAdminRealtimeUpdates();
    });
    
    if (confirmBtn) confirmBtn.addEventListener('click', function(){ if (pendingHref) window.location.href = pendingHref; });
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    // Close when clicking backdrop
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
    // Bind on DOM ready (script is at bottom so DOM exists)
    bindLogoutLinks();
  })();
  </script>
