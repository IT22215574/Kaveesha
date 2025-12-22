<?php
// Admin nav partial — include from admin pages after calling require_admin()
$current = basename($_SERVER['PHP_SELF']);
$isUsers = in_array($current, ['admin.php', 'admin_users.php'], true);
$isCreate = ($current === 'admin_create_user.php');
$isStatistics = ($current === 'admin_statistics.php');

if (!function_exists('nav_link_classes')) {
    function nav_link_classes($active) {
        $base = 'text-sm px-3 py-1 rounded transition-colors';
        return $active
            ? $base . ' text-white'
            : $base . ' text-gray-700 hover:bg-gray-100';
    }
}

if (!function_exists('nav_link_style')) {
    function nav_link_style($active) {
        return $active ? 'background-color: #692f69;' : '';
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
  <div class="flex items-center space-x-2 text-lg font-semibold">
    <img src="logo/logo1.png" alt="MC YOMA electronic Logo" class="h-24 w-auto">
    <span style="color: #692f69;"><?= htmlspecialchars($adminName) ?> — MC YOMA electronic</span>
  </div>

    <!-- Desktop nav -->
    <div class="hidden md:flex items-center space-x-8">
      <div class="flex items-center space-x-4">
        <a href="/admin.php" class="<?= nav_link_classes($isUsers) ?>" <?= $isUsers ? 'style="' . nav_link_style($isUsers) . '"' : '' ?>>Users</a>
        <a href="/admin_create_user.php" class="<?= nav_link_classes($isCreate) ?>" <?= $isCreate ? 'style="' . nav_link_style($isCreate) . '"' : '' ?>>Create User</a>
        <a href="/admin_statistics.php" class="<?= nav_link_classes($isStatistics) ?>" <?= $isStatistics ? 'style="' . nav_link_style($isStatistics) . '"' : '' ?>>Statistics</a>
        <a href="/admin_messages.php" class="<?= nav_link_classes($current === 'admin_messages.php') ?> relative" <?= ($current === 'admin_messages.php') ? 'style="' . nav_link_style(true) . '"' : '' ?>>
          Messages
          <span class="admin-messages-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
        </a>
        <!-- Consent Form link removed -->
      </div>
      <div class="flex items-center space-x-4">
        <a href="/dashboard.php" class="text-sm bg-gray-200 text-gray-800 px-3 py-1 rounded">Dashboard</a>
        <a href="/logout.php" class="logout-link text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
      </div>
    </div>

    <!-- Mobile hamburger -->
    <button id="adminNavToggle" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69;" aria-controls="adminMobileMenu" aria-expanded="false" aria-label="Open menu">
      <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="adminMobileMenu" class="md:hidden hidden border-t border-gray-200">
    <div class="px-4 py-4 space-y-3">
      <a href="/admin.php" class="block w-full <?= $isUsers ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded" <?= $isUsers ? 'style="background-color: #692f69;"' : '' ?>>Users</a>
      <a href="/admin_create_user.php" class="block w-full <?= $isCreate ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded" <?= $isCreate ? 'style="background-color: #692f69;"' : '' ?>>Create User</a>
      <a href="/admin_statistics.php" class="block w-full <?= $isStatistics ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded" <?= $isStatistics ? 'style="background-color: #692f69;"' : '' ?>>Statistics</a>
      <a href="/admin_messages.php" class="block w-full <?= ($current === 'admin_messages.php') ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded relative" <?= ($current === 'admin_messages.php') ? 'style="background-color: #692f69;"' : '' ?>>
        Messages
        <span class="admin-messages-badge absolute top-0 right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
      </a>
      <!-- Consent Form link removed -->
      <div class="pt-2 mt-2 border-t border-gray-200 flex items-center space-x-2">
        <a href="/dashboard.php" class="flex-1 text-center bg-gray-200 text-gray-800 px-3 py-2 rounded">Dashboard</a>
        <a href="/logout.php" class="logout-link flex-1 text-center bg-red-500 text-white px-3 py-2 rounded">Logout</a>
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
    
    function toggleMobileMenu() {
      if (!menu) return;
      const isHidden = menu.classList.contains('hidden');
      if (isHidden) {
        menu.classList.remove('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'true');
      } else {
        menu.classList.add('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    }
    
    if (btn && menu) {
      // Remove any existing listener before adding new one
      btn.removeEventListener('click', toggleMobileMenu);
      btn.addEventListener('click', toggleMobileMenu);
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
    function handleLogoutClick(e) {
      e.preventDefault();
      const href = e.currentTarget.getAttribute('href');
      if (href) openModal(href);
    }
    
    function bindLogoutLinks(){
      document.querySelectorAll('a.logout-link').forEach(function(a){
        // Remove existing listener before adding to prevent duplicates
        a.removeEventListener('click', handleLogoutClick);
        a.addEventListener('click', handleLogoutClick);
      });
    }
    
    // ---------- Admin Unread Count (Optimized) ----------
    let adminLastUnread = null;
    let adminPollingActive = false;
    let adminPollTimer = null;
    let adminAdaptiveDelay = 15000;
    let adminUnchangedCycles = 0;
    let adminSSESource = null;
    let adminFallbackStarted = false;

    function updateAdminBadges(count) {
      document.querySelectorAll('.admin-messages-badge').forEach(badge => {
        badge.style.display = count > 0 ? 'flex' : 'none';
      });
    }

    function adminScheduleNextPoll() {
      if (!adminPollingActive) return;
      const delay = document.hidden ? Math.max(adminAdaptiveDelay, 30000) : adminAdaptiveDelay;
      adminPollTimer = setTimeout(adminPollUnreadCount, delay);
    }

    function adminPollUnreadCount() {
      fetch('messages_api.php?action=unread_count', { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
          const c = data.unread_count || 0;
          updateAdminBadges(c);
          if (adminLastUnread === c) {
            adminUnchangedCycles++;
            if (adminUnchangedCycles > 3 && adminAdaptiveDelay < 60000) {
              adminAdaptiveDelay += 5000;
            }
          } else {
            adminLastUnread = c;
            adminUnchangedCycles = 0;
            adminAdaptiveDelay = 10000;
          }
        })
        .catch(err => {
          console.error('Admin unread poll failed', err);
          adminAdaptiveDelay = Math.min(adminAdaptiveDelay + 10000, 60000);
        })
        .finally(() => adminScheduleNextPoll());
    }

    function adminStartPollingFallback() {
      if (adminFallbackStarted) return;
      adminFallbackStarted = true;
      adminStopSSE();
      adminPollingActive = true;
      adminAdaptiveDelay = 15000;
      adminUnchangedCycles = 0;
      adminPollUnreadCount();
    }

    function adminStopSSE() {
      if (adminSSESource) {
        adminSSESource.close();
        adminSSESource = null;
      }
    }

    function setupAdminRealtimeUpdates() {
      if (typeof EventSource === 'undefined') {
        adminStartPollingFallback();
        return;
      }
      adminStopSSE();
      adminSSESource = new EventSource('/messages_sse.php');
      adminSSESource.onmessage = function(event) {
        try {
          const data = JSON.parse(event.data);
          if (data.type === 'closing') {
            adminStopSSE();
            setTimeout(setupAdminRealtimeUpdates, 2500);
            return;
          }
          if (data.unread_count !== undefined) {
            const c = data.unread_count;
            updateAdminBadges(c);
            adminLastUnread = c;
            adminAdaptiveDelay = 10000;
            adminUnchangedCycles = 0;
          }
        } catch(e) {
          console.error('Admin SSE parse error', e);
        }
      };
      adminSSESource.onerror = function() {
        if (adminSSESource && adminSSESource.readyState === 2) { // CLOSED
          adminStopSSE();
          setTimeout(setupAdminRealtimeUpdates, 3000);
        } else {
          adminStartPollingFallback();
        }
      };
    }

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        adminStopSSE();
      } else if (!adminFallbackStarted) {
        setupAdminRealtimeUpdates();
      }
    });

    function handleConfirmLogout() {
      if (pendingHref) window.location.href = pendingHref;
    }
    
    function handleModalBackdropClick(e) {
      if (e.target === modal) closeModal();
    }
    
    // Cleanup function
    function cleanup() {
      adminStopSSE();
      if (adminPollTimer) clearTimeout(adminPollTimer);
      adminPollingActive = false;
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);
    window.addEventListener('pagehide', cleanup);
    
    // Initialize on DOM ready or immediately if already loaded
    function init() {
      setupAdminRealtimeUpdates();
      adminStartPollingFallback();
      
      // Bind modal handlers
      if (confirmBtn) {
        confirmBtn.removeEventListener('click', handleConfirmLogout);
        confirmBtn.addEventListener('click', handleConfirmLogout);
      }
      if (cancelBtn) {
        cancelBtn.removeEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
      }
      if (modal) {
        modal.removeEventListener('click', handleModalBackdropClick);
        modal.addEventListener('click', handleModalBackdropClick);
      }
      
      // Bind logout links
      bindLogoutLinks();
    }
    
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
  </script>
