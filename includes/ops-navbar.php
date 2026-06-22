<?php
/**
 * Authenticated nav for admin/ and faculty/ pages.
 * Expects $user (current_user()) and $active to highlight the
 * current section. Lives one level deep, so links are root-relative
 * via '../'.
 */
$active = $active ?? '';
$isAdmin = $user['role'] === 'admin';
?>
<header class="site-header">
  <div class="container nav">
    <a href="<?php echo $isAdmin ? 'dashboard.php' : '../dashboard.php'; ?>" class="brand">
      <span class="brand-mark">S</span>
      <span>
        <span class="brand-name">SURAS</span>
        <span class="brand-sub"><?php echo $isAdmin ? 'ADMIN CONSOLE' : 'FACULTY REVIEW'; ?></span>
      </span>
    </a>

    <nav aria-label="Primary">
      <ul class="nav-links">
        <?php if ($isAdmin): ?>
          <li><a href="dashboard.php" class="<?php echo $active === 'admin_dashboard' ? 'is-active' : ''; ?>">Overview</a></li>
          <li><a href="resources.php" class="<?php echo $active === 'admin_resources' ? 'is-active' : ''; ?>">Resources</a></li>
          <li><a href="users.php" class="<?php echo $active === 'admin_users' ? 'is-active' : ''; ?>">Users</a></li>
          <li><a href="booking.php" class="<?php echo $active === 'booking' ? 'is-active' : ''; ?>">Admin Booking</a></li>
          <li><a href="bookings.php" class="<?php echo $active === 'admin_bookings' ? 'is-active' : ''; ?>">Bookings</a></li>
          <li><a href="reports.php" class="<?php echo $active === 'admin_reports' ? 'is-active' : ''; ?>">Reports</a></li>
          <li><a href="support.php" class="<?php echo $active === 'support' ? 'is-active' : ''; ?>">Support</a></li>
          <li><a href="settings.php" class="<?php echo $active === 'admin_settings' ? 'is-active' : ''; ?>">Settings</a></li>
        <?php else: ?>
          <li><a href="approvals.php" class="<?php echo $active === 'faculty_approvals' ? 'is-active' : ''; ?>">Approvals</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="nav-actions">
      <div class="user-chip">
        <span class="user-chip-avatar"><?php echo e(strtoupper(substr($user['full_name'], 0, 1))); ?></span>
        <span class="user-chip-meta">
          <span class="user-chip-name"><?php echo e($user['full_name']); ?></span>
          <span class="user-chip-role"><?php echo e(role_label($user['role'])); ?></span>
        </span>
      </div>
      <a href="<?php echo $isAdmin ? '../dashboard.php' : '../dashboard.php'; ?>" class="btn btn-ghost">My account</a>
      <a href="../logout.php" class="btn btn-ghost">Sign out</a>
      <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
        <span></span>
      </button>
    </div>
  </div>
</header>
