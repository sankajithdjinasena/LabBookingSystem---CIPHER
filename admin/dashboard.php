<?php
/**
 * admin/dashboard.php — admin overview.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['admin'], 1);
$user = current_user();
$active = 'admin_dashboard';

$stats = admin_overview_stats();
$activity = recent_activity(8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Overview — SURAS</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Admin overview</h1>
        <p>How the resource ledger is running right now.</p>
      </div>
      <div style="display:flex; gap:10px;">
        <a href="bookings.php?status=pending" class="btn btn-amber">Review pending requests</a>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-label">Total resources</span>
        <span class="stat-value"><?php echo $stats['total_resources']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Available now</span>
        <span class="stat-value"><?php echo $stats['available_resources']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Pending requests</span>
        <span class="stat-value"><?php echo $stats['pending_requests']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Total users</span>
        <span class="stat-value"><?php echo $stats['total_users']; ?></span>
      </div>
    </div>

    <div class="booking-layout">
      <div>
        <div class="panel">
          <div class="panel-head">
            <h2>Recent activity</h2>
            <a href="bookings.php">View all bookings →</a>
          </div>

          <?php if (empty($activity)): ?>
            <div class="empty-state">
              <span class="empty-icon">📭</span>
              <p>No booking activity yet.</p>
            </div>
          <?php else: ?>
            <table class="data-table">
              <thead>
                <tr><th>User</th><th>Resource</th><th>Requested</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($activity as $a): ?>
                  <tr>
                    <td><?php echo e($a['full_name']); ?></td>
                    <td><?php echo e($a['resource_name']); ?></td>
                    <td><?php echo time_ago($a['created_at']); ?></td>
                    <td><span class="badge <?php echo status_badge_class($a['status']); ?>"><?php echo e($a['status']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h2>Quick links</h2></div>
          <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="support.php" class="btn btn-ghost btn-block" style="background:#fff3e0; border-color:#ffb74d;">Support Desk (Live Chat)</a>
            <a href="resources.php" class="btn btn-ghost btn-block">Manage resources</a>
            <a href="users.php" class="btn btn-ghost btn-block">Manage users</a>
            <a href="bookings.php" class="btn btn-ghost btn-block">Review bookings</a>
            <a href="reports.php" class="btn btn-ghost btn-block">View reports</a>
          </div>
        </div>

        <?php if ($stats['waitlisted'] > 0): ?>
        <div class="banner banner-warn">
          <span>⏳</span>
          <span><?php echo $stats['waitlisted']; ?> booking<?php echo $stats['waitlisted'] === 1 ? '' : 's'; ?> currently on the waitlist.</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
