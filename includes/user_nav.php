<?php
require_once __DIR__ . '/../config.php';
require_login();

$current = basename($_SERVER['PHP_SELF']);
$isDashboard = ($current === 'dashboard.php');
$isContact = ($current === 'contact.php');

if (!function_exists('user_nav_link_classes')) {
    function user_nav_link_classes($active) {
        $base = 'text-sm px-3 py-1 rounded transition-colors';
        return $active
            ? $base . ' bg-indigo-600 text-white'
            : $base . ' text-gray-700 hover:bg-gray-100';
    }
}

// Live user info from DB (prefer DB over session for latest)
$displayName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : 'User';
$mobileNumber = '';
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = db()->prepare('SELECT username, mobile_number FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        if ($row = $stmt->fetch()) {
            if (!empty($row['username'])) $displayName = (string)$row['username'];
            if (!empty($row['mobile_number'])) $mobileNumber = (string)$row['mobile_number'];
        }
    } catch (Throwable $e) {
        // ignore and fallback to session
    }
}
?>
<nav class="bg-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <a href="/Kaveesha/dashboard.php" class="text-lg font-semibold text-indigo-700 hover:text-indigo-900">Yoma Electronics</a>

    <!-- Desktop nav -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="/Kaveesha/dashboard.php" class="<?= user_nav_link_classes($isDashboard) ?>">Home</a>
      <a href="/Kaveesha/contact.php" class="<?= user_nav_link_classes($isContact) ?>">Contact Yoma Electronics</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="/Kaveesha/admin.php" class="text-sm bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600 transition-colors">Admin Panel</a>
      <?php endif; ?>
      <span class="text-sm text-gray-600 hidden lg:inline">Signed in as <strong><?= htmlspecialchars($displayName) ?></strong></span>
    <a href="/Kaveesha/logout.php" class="logout-link text-sm bg-red-500 text-white px-3 py-1 rounded">Logout</a>
    </div>

    <!-- Mobile hamburger -->
    <button id="userNavToggle" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-controls="userMobileMenu" aria-expanded="false" aria-label="Open menu">
      <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="userMobileMenu" class="md:hidden hidden border-t border-gray-200">
    <div class="px-4 py-4 space-y-3">
      <div class="text-sm text-gray-500 mb-2">Signed in as <strong><?= htmlspecialchars($displayName) ?></strong><?= $mobileNumber ? ' • ' . htmlspecialchars($mobileNumber) : '' ?></div>
      <a href="/Kaveesha/dashboard.php" class="block w-full <?= $isDashboard ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Dashboard</a>
      <a href="/Kaveesha/contact.php" class="block w-full <?= $isContact ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Contact</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="/Kaveesha/admin.php" class="block w-full bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600">Admin Panel</a>
      <?php endif; ?>
      <div class="pt-2 mt-2 border-t border-gray-200">
        <a href="/Kaveesha/logout.php" class="logout-link block text-center bg-red-500 text-white px-3 py-2 rounded">Logout</a>
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
          e.preventDefault();
          openModal(a.getAttribute('href'));
        });
      });
    }
    if (confirmBtn) confirmBtn.addEventListener('click', function(){ if (pendingHref) window.location.href = pendingHref; });
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
    bindLogoutLinks();
  })();
  </script>
