<?php
/**
 * dashboard.php — role-aware landing page after sign-in.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$active = 'dashboard';

$stats = dashboard_stats($user['id']);
$recentBookings = array_slice(get_user_bookings($user['id']), 0, 5);

$pdo = get_db_connection();
$qbStmt = $pdo->prepare("SELECT r.id, r.name, r.category FROM bookings b JOIN resources r ON b.resource_id = r.id WHERE b.user_id = :uid GROUP BY r.id, r.name, r.category ORDER BY COUNT(b.id) DESC LIMIT 1");
$qbStmt->execute(['uid' => $user['id']]);
$quickBookResource = $qbStmt->fetch();
$recentNotifications = get_notifications($user['id'], 4);
$justRegistered = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/assistant.css">
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <?php if ($justRegistered): ?>
      <div class="banner banner-success">
        <span>✓</span>
        <span>Welcome to SURAS, <?php echo e($user['full_name']); ?> — your account is ready. Start by browsing resources below.</span>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['booked'])): ?>
      <?php $bs = $_GET['booked']; ?>
      <div class="banner <?php echo $bs === 'waitlist' ? 'banner-warn' : 'banner-success'; ?>">
        <span><?php echo $bs === 'waitlist' ? '⏳' : '✓'; ?></span>
        <span>
          <?php
            if ($bs === 'approved')       echo 'Your booking was approved automatically.';
            elseif ($bs === 'waitlist')   echo 'Your booking conflicted with a higher-priority request and was added to the waitlist — check Notifications for an alternative slot.';
            else                          echo 'Your booking was processed — check My Bookings for details.';
          ?>
        </span>
      </div>
    <?php endif; ?>

    <div class="page-head">
      <div>
        <h1>Welcome back, <?php echo e(explode(' ', $user['full_name'])[0]); ?>.</h1>
        <p><?php echo e(role_label($user['role'])); ?><?php echo $user['department'] ? ' · ' . e($user['department']) : ''; ?></p>
      </div>
      <a href="resources.php" class="btn btn-amber">Book a resource</a>
    </div>

    <?php if ($quickBookResource): ?>
    <div class="quick-book-card">
      <div class="quick-book-info">
        <h3>Quick Book: <?php echo e($quickBookResource['name']); ?></h3>
        <p>Book your most used resource instantly for the next available slot.</p>
      </div>
      <a href="booking.php?resource_id=<?php echo (int)$quickBookResource['id']; ?>" class="btn btn-amber" style="white-space:nowrap; margin-left:20px;">Book Now</a>
    </div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-label">Today's bookings</span>
        <span class="stat-value"><?php echo $stats['today']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Pending requests</span>
        <span class="stat-value"><?php echo $stats['pending']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">On waitlist</span>
        <span class="stat-value"><?php echo $stats['waitlisted']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Resources available</span>
        <span class="stat-value"><?php echo $stats['available_resources']; ?></span>
      </div>
    </div>

    <div class="booking-layout">
      <div>
        <div class="panel">
          <div class="panel-head">
            <h2>Recent bookings</h2>
            <a href="my-bookings.php">View all →</a>
          </div>

          <?php if (empty($recentBookings)): ?>
            <div class="empty-state">
              <span class="empty-icon">📭</span>
              <p>No bookings yet. Find a resource to get started.</p>
              <a href="resources.php" class="btn btn-ghost" style="margin-top:8px;">Browse resources</a>
            </div>
          <?php else: ?>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Resource</th>
                  <th>When</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentBookings as $b): ?>
                  <tr>
                    <td>
                      <div class="resource-name"><?php echo e($b['resource_name']); ?></div>
                      <div class="resource-sub"><?php echo e(category_label($b['resource_category'])); ?></div>
                    </td>
                    <td>
                      <?php echo date('M j, g:i A', strtotime($b['start_time'])); ?> –
                      <?php echo date('g:i A', strtotime($b['end_time'])); ?>
                    </td>
                    <td><span class="badge <?php echo status_badge_class($b['status']); ?>"><?php echo e($b['status']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head">
            <h2>Notifications</h2>
            <a href="notifications.php">View all →</a>
          </div>

          <?php if (empty($recentNotifications)): ?>
            <div class="empty-state">
              <span class="empty-icon">🔔</span>
              <p>You're all caught up.</p>
            </div>
          <?php else: ?>
            <div class="notif-list">
              <?php foreach ($recentNotifications as $n): ?>
                <div class="notif-item <?php echo $n['is_read'] ? '' : 'is-unread'; ?>">
                  <span class="notif-icon"><?php
                    echo $n['type'] === 'submission' ? '📝' : ($n['type'] === 'approval' ? '✓' : ($n['type'] === 'rejection' ? '✕' : ($n['type'] === 'waitlist' ? '⏳' : '🔔')));
                  ?></span>
                  <div class="notif-body">
                    <div class="notif-message"><?php echo e($n['message']); ?></div>
                    <div class="notif-time"><?php echo time_ago($n['created_at']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>



  </div>
</main>

<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/assistant.js?v=<?php echo time(); ?>"></script>
</body>
</html>