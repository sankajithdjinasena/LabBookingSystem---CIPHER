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
<aside class="admin-sidebar">
  <!-- Brand Area -->
  <div class="sidebar-brand">
    <a href="<?php echo $isAdmin ? 'dashboard.php' : '../dashboard.php'; ?>">
      <img src="../assets/img/logo.png" alt="NEXLAB Logo" class="sidebar-logo">
      <div class="sidebar-brand-text">
        <span class="brand-name">NEXLAB</span>
        <span class="brand-sub"><?php echo $isAdmin ? 'ADMIN CONSOLE' : 'FACULTY REVIEW'; ?></span>
      </div>
    </a>
  </div>

  <!-- Navigation Links -->
  <nav class="sidebar-nav">
    <ul>
      <?php if ($isAdmin): ?>
        <li>
          <a href="../admin/dashboard.php" class="<?php echo $active === 'admin_dashboard' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Overview
          </a>
        </li>
        <li>
          <a href="../admin/resources.php" class="<?php echo $active === 'admin_resources' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Resources
          </a>
        </li>
        <li>
          <a href="../admin/users.php" class="<?php echo $active === 'admin_users' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Users
          </a>
        </li>
        <li>
          <a href="../admin/bookings.php" class="<?php echo $active === 'admin_bookings' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Bookings
          </a>
        </li>
        <li>
          <a href="../admin/reports.php" class="<?php echo $active === 'admin_reports' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Reports
          </a>
        </li>
        <li>
          <a href="../admin/support.php" class="<?php echo $active === 'support' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            Support
          </a>
        </li>
        <li>
          <a href="../admin/analytics.php" class="<?php echo $active === 'analytics' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            Intelligence
          </a>
        </li>
        <li>
          <a href="../admin/settings.php" class="<?php echo $active === 'admin_settings' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Settings
          </a>
        </li>
      <?php else: ?>
        <li>
          <a href="../faculty/approvals.php" class="<?php echo $active === 'faculty_approvals' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Approvals
          </a>
        </li>
        <li>
          <a href="../faculty/support.php" class="<?php echo $active === 'support' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            Support
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- Sidebar Footer (User Info & Logout) -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?php echo e(strtoupper(substr($user['full_name'], 0, 1))); ?></div>
      <div class="user-details">
        <span class="user-name"><?php echo e($user['full_name']); ?></span>
        <span class="user-role"><?php echo e(role_label($user['role'])); ?></span>
      </div>
    </div>
    <div class="sidebar-actions">
      <a href="../dashboard.php" title="View Student Dashboard">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
      </a>
      <a href="../logout.php" class="logout-btn" title="Sign out">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      </a>
    </div>
  </div>
</aside>

<!-- Mobile Toggle Button (Only visible on small screens) -->
<button class="mobile-sidebar-toggle" aria-label="Toggle Sidebar">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
</button>

<script>
document.querySelector('.mobile-sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.admin-sidebar').classList.toggle('is-open');
});
</script>