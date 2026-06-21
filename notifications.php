<?php
/**
 * notifications.php — full notification history for the signed-in user.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$active = 'notifications';

$notifications = get_notifications($user['id'], 50);
mark_notifications_read($user['id']);

$iconFor = static function (string $type): string {
    return match ($type) {
        'approval'     => '✓',
        'rejection'    => '✕',
        'cancellation' => '🗑️',
        'reminder'     => '⏰',
        'waitlist'     => '⏳',
        'alternative'  => '🔁',
        default        => '🔔',
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Notifications</h1>
        <p>Approvals, rejections, waitlist movement and reminders, all in one place.</p>
      </div>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="empty-state">
        <span class="empty-icon">🔔</span>
        <p>Nothing here yet — notifications appear as your bookings move through the system.</p>
      </div>
    <?php else: ?>
      <div class="notif-list">
        <?php foreach ($notifications as $n): ?>
          <div class="notif-item">
            <span class="notif-icon"><?php echo $iconFor($n['type']); ?></span>
            <div class="notif-body">
              <div class="notif-message"><?php echo e($n['message']); ?></div>
              <div class="notif-time"><?php echo date('M j, Y · g:i A', strtotime($n['created_at'])); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
