<?php
require_once __DIR__ . '/../config.php';
require_login();

$current = basename($_SERVER['PHP_SELF']);
$isDashboard = ($current === 'dashboard.php');

if (!function_exists('user_nav_link_classes')) {
    function user_nav_link_classes($active) {
        $base = 'text-sm px-3 py-1 rounded transition-colors';
        return $active
            ? $base . ' text-white'
            : $base . ' text-gray-700 hover:bg-gray-100';
    }
}

if (!function_exists('user_nav_link_style')) {
    function user_nav_link_style($active) {
        return $active ? 'background-color: #692f69;' : '';
    }
}

// Use cached user info from session (updated on login/profile changes)
$displayName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : 'User';
$mobileNumber = '';
// Only fetch from DB if session cache is missing or expired
if (!empty($_SESSION['user_id']) && (empty($_SESSION['cached_username']) || empty($_SESSION['cached_mobile']))) {
    try {
        $stmt = db()->prepare('SELECT username, mobile_number FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        if ($row = $stmt->fetch()) {
            if (!empty($row['username'])) {
                $displayName = (string)$row['username'];
                $_SESSION['cached_username'] = $displayName;
            }
            if (!empty($row['mobile_number'])) {
                $mobileNumber = (string)$row['mobile_number'];
                $_SESSION['cached_mobile'] = $mobileNumber;
            }
        }
    } catch (Throwable $e) {
        // ignore and fallback to session
    }
} else {
    // Use cached values
    if (!empty($_SESSION['cached_username'])) $displayName = $_SESSION['cached_username'];
    if (!empty($_SESSION['cached_mobile'])) $mobileNumber = $_SESSION['cached_mobile'];
}
?>
<nav class="bg-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <a href="dashboard.php" class="flex items-center space-x-2 text-lg font-semibold hover:opacity-80">
      <img src="logo/logo1.png" alt="MC YOMA electronic Logo" class="h-24 w-auto">
      <span style="color: #692f69;">MC YOMA electronic</span>
    </a>

    <!-- Desktop nav -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="dashboard.php" class="<?= user_nav_link_classes($isDashboard) ?>" <?= $isDashboard ? 'style="' . user_nav_link_style($isDashboard) . '"' : '' ?>>Home</a>
      <a href="messages.php" class="<?= user_nav_link_classes($current === 'messages.php') ?> relative" <?= ($current === 'messages.php') ? 'style="' . user_nav_link_style(true) . '"' : '' ?>>
        Messages
        <span class="messages-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
      </a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="admin.php" class="text-sm text-white px-3 py-1 rounded transition-colors" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">Admin Panel</a>
      <?php endif; ?>
      <span class="text-sm text-gray-600 hidden lg:inline">Signed in as <strong><?= htmlspecialchars($displayName) ?></strong></span>
    <a href="logout.php" class="logout-link text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
    </div>

    <!-- Mobile hamburger -->
    <button id="userNavToggle" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69;" aria-controls="userMobileMenu" aria-expanded="false" aria-label="Open menu">
      <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="userMobileMenu" class="md:hidden hidden border-t border-gray-200">
    <div class="px-4 py-4 space-y-3">
      <div class="text-sm text-gray-500 mb-2">Signed in as <strong><?= htmlspecialchars($displayName) ?></strong><?= $mobileNumber ? ' • ' . htmlspecialchars($mobileNumber) : '' ?></div>
      <a href="dashboard.php" class="block w-full <?= $isDashboard ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded" <?= $isDashboard ? 'style="background-color: #692f69;"' : '' ?>>Home</a>
      <a href="messages.php" class="block w-full <?= ($current === 'messages.php') ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded relative" <?= ($current === 'messages.php') ? 'style="background-color: #692f69;"' : '' ?>>
        Messages
        <span class="messages-badge absolute top-0 right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="display: none;"></span>
      </a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="admin.php" class="block w-full text-white px-3 py-2 rounded" style="background-color: #692f69;" onmouseover="this.style.backgroundColor='#7d3a7d'" onmouseout="this.style.backgroundColor='#692f69'">Admin Panel</a>
      <?php endif; ?>
      <div class="pt-2 mt-2 border-t border-gray-200">
        <a href="logout.php" class="logout-link block text-center bg-red-500 text-white px-3 py-2 rounded">Logout</a>
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
    const btn = document.getElementById('userNavToggle');
    const menu = document.getElementById('userMobileMenu');
    
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
    
    // ---------- Unread Count Logic (Optimized) ----------
    let lastUnread = null;
    let pollingActive = false;
    let pollTimer = null;
    let adaptiveDelay = 15000; // start at 15s
    let unchangedCycles = 0;
    let sseSource = null;
    let fallbackStarted = false;

    function updateBadges(count) {
      document.querySelectorAll('.messages-badge').forEach(badge => {
        badge.style.display = count > 0 ? 'flex' : 'none';
      });
    }

    function scheduleNextPoll() {
      if (!pollingActive) return;
      if (document.hidden) {
        // Slow polling when tab hidden
        pollTimer = setTimeout(pollUnreadCount, Math.max(adaptiveDelay, 30000));
      } else {
        pollTimer = setTimeout(pollUnreadCount, adaptiveDelay);
      }
    }

    function pollUnreadCount() {
      fetch('messages_api.php?action=unread_count', { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
          const c = data.unread_count || 0;
          updateBadges(c);
          if (lastUnread === c) {
            unchangedCycles++;
            // Gradually back off up to 60s
            if (unchangedCycles > 3 && adaptiveDelay < 60000) {
              adaptiveDelay += 5000;
            }
          } else {
            lastUnread = c;
            unchangedCycles = 0;
            adaptiveDelay = 10000; // speed up when change detected
          }
        })
        .catch(err => {
          console.error('Unread poll failed', err);
          // On error, back off more aggressively
          adaptiveDelay = Math.min(adaptiveDelay + 10000, 60000);
        })
        .finally(() => scheduleNextPoll());
    }

    function startPollingFallback() {
      if (fallbackStarted) return; // ensure single fallback
      fallbackStarted = true;
      stopSSE();
      pollingActive = true;
      adaptiveDelay = 15000;
      unchangedCycles = 0;
      pollUnreadCount();
    }

    // ---------- SSE Real-time (Graceful) ----------
    function stopSSE() {
      if (sseSource) {
        sseSource.close();
        sseSource = null;
      }
    }

    function setupRealtimeUpdates() {
      if (typeof EventSource === 'undefined') {
        startPollingFallback();
        return;
      }
      stopSSE();
      sseSource = new EventSource('messages_sse.php');
      sseSource.onmessage = function(event) {
        try {
          const data = JSON.parse(event.data);
          if (data.type === 'closing') {
            // Graceful server close: restart SSE quickly
            stopSSE();
            setTimeout(setupRealtimeUpdates, 2000);
            return;
          }
          if (data.unread_count !== undefined) {
            const c = data.unread_count;
            updateBadges(c);
            lastUnread = c;
            // Reset adaptive state because we have a change via SSE
            adaptiveDelay = 10000;
            unchangedCycles = 0;
          }
        } catch(e) {
          console.error('SSE parse error', e);
        }
      };
      sseSource.onerror = function() {
        // Distinguish between normal close and network error
        if (sseSource && sseSource.readyState === 2) { // CLOSED
          // Try to re-establish after short delay without fallback escalation
          stopSSE();
          setTimeout(setupRealtimeUpdates, 3000);
        } else {
          // Actual error -> fallback polling
          startPollingFallback();
        }
      };
    }

    // Page visibility: pause SSE when hidden, resume when visible
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopSSE();
      } else if (!fallbackStarted) {
        setupRealtimeUpdates();
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
      stopSSE();
      if (pollTimer) clearTimeout(pollTimer);
      pollingActive = false;
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);
    window.addEventListener('pagehide', cleanup);
    
    // Initialize on DOM ready or immediately if already loaded
    function init() {
      setupRealtimeUpdates();
      startPollingFallback();
      
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
