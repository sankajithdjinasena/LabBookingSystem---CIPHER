<?php
/**
 * faculty/approvals.php — faculty review of booking requests,
 * scoped to their own department by default.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['faculty', 'admin'], 1);
$user = current_user();
$active = 'faculty_approvals';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = 'error:Your session expired. Please try again.';
    } else {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $actionType = $_POST['action'] ?? '';

        // Security check for Faculty: Ensure the booking belongs to their department
        $canApprove = true;
        if ($user['role'] === 'faculty') {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare('SELECT u.department FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = :id');
            $stmt->execute(['id' => $bookingId]);
            $bDept = $stmt->fetchColumn();
            if ($bDept !== $user['department']) {
                $canApprove = false;
                $message = 'error:You can only manage bookings from your own department.';
            }
        }

        if ($canApprove) {
            if ($actionType === 'approve') {
                approve_booking_admin($bookingId);
                $message = 'success:Booking approved.';
            } elseif ($actionType === 'reject') {
                reject_booking_admin($bookingId, trim($_POST['reason'] ?? ''));
                $message = 'success:Booking rejected.';
            }
        }
    }
}

// Faculty MUST only see their own department. Admin can see all.
if ($user['role'] === 'faculty') {
    $scope = 'department';
    $departmentFilter = $user['department'] ?? '';
} else {
    $scope = $_GET['scope'] ?? 'all';
    $departmentFilter = ($scope === 'department') ? ($user['department'] ?? '') : '';
}

$statusFilter = $_GET['status'] ?? 'pending';
$bookings = get_bookings_for_review($statusFilter, $departmentFilter);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

$filters = ['pending' => 'Pending', 'approved' => 'Approved', 'waitlist' => 'Waitlist', 'rejected' => 'Rejected', 'all' => 'All'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approvals — SURAS Faculty</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Booking approvals</h1>
        <p>
          <?php if ($departmentFilter): ?>
            Showing requests from <strong><?php echo e($departmentFilter); ?></strong>.
          <?php else: ?>
            Showing requests from all departments.
          <?php endif; ?>
        </p>
      </div>
      <?php if ($user['role'] === 'admin'): ?>
        <div class="chip-group">
          <a class="chip <?php echo $scope === 'all' ? 'is-active' : ''; ?>" href="approvals.php?scope=all&status=<?php echo e($statusFilter); ?>">All departments</a>
          <a class="chip <?php echo $scope === 'department' ? 'is-active' : ''; ?>" href="approvals.php?scope=department&status=<?php echo e($statusFilter); ?>">My department</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($message): ?>
      <?php [$kind, $text] = explode(':', $message, 2); ?>
      <div class="banner <?php echo $kind === 'success' ? 'banner-success' : 'banner-error'; ?>">
        <span><?php echo $kind === 'success' ? '✓' : '⚠️'; ?></span>
        <span><?php echo e($text); ?></span>
      </div>
    <?php endif; ?>

    <div class="chip-group" style="margin-bottom: 24px;">
      <?php foreach ($filters as $key => $label): ?>
        <a class="chip <?php echo $statusFilter === $key ? 'is-active' : ''; ?>" href="approvals.php?status=<?php echo e($key); ?>&scope=<?php echo e($scope); ?>"><?php echo e($label); ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($bookings)): ?>
      <div class="empty-state">
        <span class="empty-icon">✓</span>
        <p>Nothing waiting on your review.</p>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Requester</th><th>Resource</th><th>Purpose</th><th>When</th><th>Priority</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td>
                <div class="resource-name"><?php echo e($b['full_name']); ?></div>
                <div class="resource-sub"><?php echo e(role_label($b['user_role'])); ?><?php echo $b['user_department'] ? ' · ' . e($b['user_department']) : ''; ?></div>
              </td>
              <td>
                <div class="resource-name"><?php echo e($b['resource_name']); ?></div>
                <div class="resource-sub"><?php echo e(category_label($b['resource_category'])); ?></div>
              </td>
              <td><?php echo e($b['purpose']); ?></td>
              <td>
                <?php echo date('M j, g:i A', strtotime($b['start_time'])); ?> –
                <?php echo date('g:i A', strtotime($b['end_time'])); ?>
              </td>
              <td class="resource-sub"><?php echo number_format((float) $b['priority_score'], 1); ?> / 10</td>
              <td><span class="badge <?php echo status_badge_class($b['status']); ?>"><?php echo e($b['status']); ?></span></td>
              <td style="white-space:nowrap;">
                <?php if (in_array($b['status'], ['pending', 'waitlist'], true)): ?>
                  <form method="POST" action="approvals.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                    <button type="submit" class="icon-btn" style="color:var(--sage);">Approve</button>
                  </form>
                  <form method="POST" action="approvals.php" style="display:inline;" onsubmit="return confirm('Reject this booking?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                    <button type="submit" class="icon-btn">Reject</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
