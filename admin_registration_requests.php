<?php
require_once __DIR__ . '/config.php';
require_admin();

$flash = '';
$success = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if ($requestId <= 0) {
    $flash = 'Invalid request ID.';
  } elseif ($action === 'approve') {
    try {
      // Get the registration request details
      $stmt = db()->prepare('SELECT username, mobile_number FROM user_registration_requests WHERE id = ? AND status = "pending" LIMIT 1');
      $stmt->execute([$requestId]);
      $request = $stmt->fetch();

      if (!$request) {
        $flash = 'Registration request not found or already processed.';
      } else {
        // Check if user already exists (race condition protection)
        $existingUser = db()->prepare('SELECT id FROM users WHERE mobile_number = ? LIMIT 1');
        $existingUser->execute([$request['mobile_number']]);
        if ($existingUser->fetch()) {
          // Update request status to rejected
          $updateStmt = db()->prepare('UPDATE user_registration_requests SET status = "rejected", processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ?');
          $updateStmt->execute([$_SESSION['user_id'], 'Mobile number already exists', $requestId]);
          $flash = 'User with this mobile number already exists.';
        } else {
          // Create the user account
          $insertStmt = db()->prepare('INSERT INTO users (username, mobile_number, is_admin) VALUES (?, ?, 0)');
          $insertStmt->execute([$request['username'], $request['mobile_number']]);

          // Update request status to approved
          $updateStmt = db()->prepare('UPDATE user_registration_requests SET status = "approved", processed_at = NOW(), processed_by = ? WHERE id = ?');
          $updateStmt->execute([$_SESSION['user_id'], $requestId]);

          $success = 'User account created successfully!';
        }
      }
    } catch (PDOException $e) {
      if ((int)$e->errorInfo[1] === 1062) { // duplicate entry
        $flash = 'Duplicate entry detected. User with this mobile number or username already exists.';
        // Mark as rejected
        try {
          $updateStmt = db()->prepare('UPDATE user_registration_requests SET status = "rejected", processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ?');
          $updateStmt->execute([$_SESSION['user_id'], 'Duplicate entry', $requestId]);
        } catch (Exception $e2) {
          // Ignore
        }
      } else {
        $flash = 'Failed to create user account. Please try again.';
      }
    }
  } elseif ($action === 'reject') {
    $rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : 'Request rejected by admin';
    try {
      $updateStmt = db()->prepare('UPDATE user_registration_requests SET status = "rejected", processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ? AND status = "pending"');
      $updateStmt->execute([$_SESSION['user_id'], $rejectionReason, $requestId]);
      if ($updateStmt->rowCount() > 0) {
        $success = 'Registration request rejected.';
      } else {
        $flash = 'Request not found or already processed.';
      }
    } catch (PDOException $e) {
      $flash = 'Failed to reject request. Please try again.';
    }
  } else {
    $flash = 'Invalid action.';
  }
}

// Fetch all registration requests (pending first, then processed)
try {
  $stmt = db()->prepare('
    SELECT 
      urr.*,
      u.username as processed_by_username
    FROM user_registration_requests urr
    LEFT JOIN users u ON urr.processed_by = u.id
    ORDER BY 
      CASE urr.status 
        WHEN "pending" THEN 1
        WHEN "approved" THEN 2
        WHEN "rejected" THEN 3
      END,
      urr.requested_at DESC
  ');
  $stmt->execute();
  $requests = $stmt->fetchAll();
} catch (PDOException $e) {
  $requests = [];
  $flash = 'Failed to load registration requests.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration Requests • Admin • mctronicservice</title>
  <link rel="icon" type="image/png" href="/logo/logo1.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: white;
      padding: 2rem;
      border-radius: 0.5rem;
      max-width: 500px;
      width: 90%;
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include __DIR__ . '/includes/admin_nav.php'; ?>

  <main class="max-w-7xl mx-auto p-6 space-y-6">
    <section class="bg-white rounded shadow p-6">
      <h2 class="text-xl font-semibold mb-4">User Registration Requests</h2>
      
      <?php if ($flash): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if (empty($requests)): ?>
        <p class="text-gray-500 text-center py-8">No registration requests found.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mobile</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($requests as $req): ?>
                <tr class="<?= $req['status'] === 'pending' ? 'bg-yellow-50' : '' ?>">
                  <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['username']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['mobile_number']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($req['requested_at'])) ?></td>
                  <td class="px-4 py-3 text-sm">
                    <?php if ($req['status'] === 'pending'): ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                    <?php elseif ($req['status'] === 'approved'): ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                    <?php else: ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <?php if ($req['status'] === 'pending'): ?>
                      <form method="post" class="inline-flex gap-2">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>" />
                        <button type="submit" name="action" value="approve" class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700">
                          Approve
                        </button>
                        <button type="button" onclick="openRejectModal(<?= $req['id'] ?>)" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700">
                          Reject
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-xs text-gray-400">
                        <?php if ($req['processed_at']): ?>
                          <?= date('M j, Y', strtotime($req['processed_at'])) ?>
                          <?php if ($req['processed_by_username']): ?>
                            by <?= htmlspecialchars($req['processed_by_username']) ?>
                          <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($req['status'] === 'rejected' && $req['rejection_reason']): ?>
                          <br><span class="text-red-600">(<?= htmlspecialchars($req['rejection_reason']) ?>)</span>
                        <?php endif; ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Reject Modal -->
  <div id="rejectModal" class="modal">
    <div class="modal-content">
      <h3 class="text-lg font-semibold mb-4">Reject Registration Request</h3>
      <form method="post">
        <input type="hidden" name="request_id" id="rejectRequestId" />
        <input type="hidden" name="action" value="reject" />
        <div class="mb-4">
          <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for rejection (optional)</label>
          <textarea id="rejection_reason" name="rejection_reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="--tw-ring-color: #692f69;" placeholder="Enter reason..."></textarea>
        </div>
        <div class="flex gap-3 justify-end">
          <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
            Reject Request
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openRejectModal(requestId) {
      document.getElementById('rejectRequestId').value = requestId;
      document.getElementById('rejectModal').classList.add('active');
    }

    function closeRejectModal() {
      document.getElementById('rejectModal').classList.remove('active');
      document.getElementById('rejection_reason').value = '';
    }

    // Close modal on outside click
    document.getElementById('rejectModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeRejectModal();
      }
    });
  </script>
</body>
</html>
