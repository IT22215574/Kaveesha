<?php
// Admin nav partial — include from admin pages after calling require_admin()
$current = basename($_SERVER['PHP_SELF']);
$isUsers = in_array($current, ['admin.php', 'admin_users.php'], true);
$isCreate = ($current === 'admin_create_user.php');
$isListings = in_array($current, ['add_listing.php'], true);
$isInvoices = in_array($current, ['invoices.php', 'create_invoice.php', 'view_invoice.php'], true);

if (!function_exists('nav_link_classes')) {
    function nav_link_classes($active) {
        $base = 'text-sm px-3 py-1 rounded transition-colors';
        return $active
            ? $base . ' bg-indigo-600 text-white'
            : $base . ' text-gray-700 hover:bg-gray-100';
    }
}

// Fetch live admin name from DB so changes reflect immediately
$adminName = isset($_SESSION['user']) ? (string)$_SESSION['user'] : 'Admin';
if (!empty($_SESSION['user_id'])) {
  try {
    $stmt = db()->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && isset($row['username']) && $row['username'] !== '') {
      $adminName = (string)$row['username'];
    }
  } catch (Throwable $e) {
    // Fallback to session name on any error
  }
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
        <a href="/Kaveesha/add_listing.php" class="<?= nav_link_classes($isListings) ?>">Listings</a>
        <a href="/Kaveesha/invoices.php" class="<?= nav_link_classes($isInvoices) ?>">Invoices</a>
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
      <a href="/Kaveesha/add_listing.php" class="block w-full <?= $isListings ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Listings</a>
      <a href="/Kaveesha/invoices.php" class="block w-full <?= $isInvoices ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 py-2 rounded">Invoices</a>
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
    if (confirmBtn) confirmBtn.addEventListener('click', function(){ if (pendingHref) window.location.href = pendingHref; });
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    // Close when clicking backdrop
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
    // Bind on DOM ready (script is at bottom so DOM exists)
    bindLogoutLinks();
  })();
  </script>
