<?php
/**
 * Authenticated app navbar — included by dashboard.php, resources.php,
 * booking.php, my-bookings.php, notifications.php.
 * Expects $user (current_user()) and optionally $active to highlight
 * the current nav item ('dashboard' | 'resources' | 'bookings' | 'notifications').
 */
$active = $active ?? '';
$unread = function_exists('unread_notification_count') ? unread_notification_count($user['id']) : 0;

$brandUrl = 'dashboard.php';
if ($user['role'] === 'admin') {
    $brandUrl = 'admin/dashboard.php';
} elseif ($user['role'] === 'faculty') {
    $brandUrl = 'faculty/approvals.php';
}
?>
<?php
$brandUrl = 'dashboard.php';
if ($user['role'] === 'admin') {
    $brandUrl = 'admin/dashboard.php';
} elseif ($user['role'] === 'faculty') {
    $brandUrl = 'faculty/approvals.php';
}
?>
<header class="site-header">
  <div class="container nav">
    <a href="<?php echo $brandUrl; ?>" class="brand">
      <span class="brand-mark">S</span>
      <span>
        <span class="brand-name">SURAS</span>
        <span class="brand-sub">RESOURCE LEDGER</span>
      </span>
    </a>

    <nav aria-label="Primary">
      <ul class="nav-links">
        <li><a href="dashboard.php" class="<?php echo $active === 'dashboard' ? 'is-active' : ''; ?>">Dashboard</a></li>
        <li><a href="resources.php" class="<?php echo $active === 'resources' ? 'is-active' : ''; ?>">Resources</a></li>
        <li><a href="my-bookings.php" class="<?php echo $active === 'bookings' ? 'is-active' : ''; ?>">My Bookings</a></li>
        <li><a href="<?php echo in_array($user['role'], ['admin', 'faculty'], true) ? 'admin/support.php' : 'support.php'; ?>" class="<?php echo $active === 'support' ? 'is-active' : ''; ?>">Support</a></li>
        <li><a href="notifications.php" class="<?php echo $active === 'notifications' ? 'is-active' : ''; ?>">
          Notifications<?php if ($unread > 0): ?> <span class="nav-badge"><?php echo $unread; ?></span><?php endif; ?>
        </a></li>
        <?php if ($user['role'] === 'admin'): ?>
          <li><a href="admin/dashboard.php" style="color:var(--amber); font-weight:600;">Admin Console</a></li>
        <?php elseif ($user['role'] === 'faculty'): ?>
          <li><a href="faculty/approvals.php" style="color:var(--amber); font-weight:600;">Faculty Review</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="nav-actions">
      <?php if ($user['role'] === 'admin'): ?>
        <a href="admin/dashboard.php" class="btn btn-ghost" style="font-size:13px;">Admin console</a>
      <?php elseif ($user['role'] === 'faculty'): ?>
        <a href="faculty/approvals.php" class="btn btn-ghost" style="font-size:13px;">Approvals</a>
      <?php endif; ?>
      <div class="user-chip">
        <span class="user-chip-avatar"><?php echo e(strtoupper(substr($user['full_name'], 0, 1))); ?></span>
        <span class="user-chip-meta">
          <span class="user-chip-name"><?php echo e($user['full_name']); ?></span>
          <span class="user-chip-role"><?php echo e(role_label($user['role'])); ?></span>
        </span>
      </div>
      <a href="logout.php" class="btn btn-ghost">Sign out</a>
      <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
        <span></span>
      </button>
    </div>
  </div>
</header>