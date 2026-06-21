<?php
/**
 * my-bookings.php — booking history with status tracking and cancellation.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$active = 'bookings';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = 'error:Your session expired. Please try again.';
    } else {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $cancelled = cancel_booking($bookingId, $user['id']);
        $message = $cancelled ? 'success:Booking cancelled.' : 'error:That booking could not be cancelled.';
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$bookings = get_user_bookings($user['id'], $statusFilter);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

$filters = [
    'all'       => 'All',
    'pending'   => 'Pending',
    'approved'  => 'Approved',
    'waitlist'  => 'Waitlist',
    'rejected'  => 'Rejected',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>My Bookings</h1>
        <p>Track every request you've made, and cancel anything you no longer need.</p>
      </div>
      <a href="resources.php" class="btn btn-amber">Book a resource</a>
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
        <a class="chip <?php echo $statusFilter === $key ? 'is-active' : ''; ?>" href="my-bookings.php?status=<?php echo e($key); ?>">
          <?php echo e($label); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($bookings)): ?>
      <div class="empty-state">
        <span class="empty-icon">📭</span>
        <p>No bookings in this view yet.</p>
        <a href="resources.php" class="btn btn-ghost" style="margin-top:8px;">Browse resources</a>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Resource</th>
            <th>Purpose</th>
            <th>When</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td>
                <div class="resource-name"><?php echo e($b['resource_name']); ?></div>
                <div class="resource-sub"><?php echo e(category_label($b['resource_category'])); ?></div>
              </td>
              <td><?php echo e($b['purpose']); ?></td>
              <td>
                <?php echo date('M j, g:i A', strtotime($b['start_time'])); ?> –
                <?php echo date('g:i A', strtotime($b['end_time'])); ?>
              </td>
              <td><span class="badge <?php echo status_badge_class($b['status']); ?>"><?php echo e($b['status']); ?></span></td>
              <td>
                <?php if (in_array($b['status'], ['pending', 'approved', 'waitlist'], true)): ?>
                  <form method="POST" action="my-bookings.php" onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                    <button type="submit" class="icon-btn">Cancel</button>
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

<script src="assets/js/main.js"></script>
</body>
</html>
