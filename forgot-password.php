<?php
/**
 * forgot-password.php — request a password reset link.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_user()['role']));
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = 'Your session expired. Please try again.';
        $messageType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "That doesn't look like a valid email address.";
            $messageType = 'error';
        } else {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = :email AND status = :status');
            $stmt->execute(['email' => $email, 'status' => 'active']);
            $user = $stmt->fetch();

            // Always show success — don't leak whether the email exists.
            $message = 'If that email is registered, a reset link has been sent. Check your inbox.';
            $messageType = 'success';

            if ($user) {
                // Expire any existing tokens for this email.
                $pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = :email")->execute(['email' => $email]);

                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $insert = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)');
                $insert->execute(['email' => $email, 'token' => $token, 'expires' => $expires]);

                $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                           . '://' . $_SERVER['HTTP_HOST']
                           . dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;

                $body = "Hi " . $user['full_name'] . ",\n\n"
                      . "You requested a password reset for your SURAS account.\n\n"
                      . "Click the link below to set a new password (valid for 1 hour):\n"
                      . $resetLink . "\n\n"
                      . "If you did not request this, you can safely ignore this email.\n\n"
                      . "— The SURAS Team";

                // send_email_notification($email, $user['full_name'], '[SURAS] Password Reset Request', $body);
                $result = send_email_notification($email, $user['full_name'], '[SURAS] Password Reset Request', $body);
                if (!$result) {
                    error_log("MAIL FAILED for: " . $email);
                }
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-shell">

  <aside class="auth-aside">
    <div class="auth-aside-top">
      <a href="index.php" class="brand">
        <span class="brand-mark">S</span>
        <span>
          <span class="brand-name">SURAS</span>
          <span class="brand-sub">RESOURCE LEDGER</span>
        </span>
      </a>
      <p class="auth-quote">"A locked door is just a reset link away."</p>
      <span class="auth-quote-by">— getting back in</span>
    </div>
    <div class="auth-aside-bottom">
      <div class="auth-ledger">
        <div class="row"><span>Link valid for</span><span>1 hour</span></div>
        <div class="row"><span>One-time use</span><span>Yes</span></div>
      </div>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <span class="eyebrow">Account recovery</span>
      <h1>Forgot your password?</h1>
      <p class="auth-sub">Enter your university email and we'll send you a reset link.</p>

      <?php if ($message): ?>
        <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
          <span><?php echo $messageType === 'success' ? '✓' : '⚠️'; ?></span>
          <span><?php echo e($message); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($messageType !== 'success'): ?>
        <form method="POST" action="forgot-password.php">
          <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
          <div class="field">
            <label for="email">University email</label>
            <div class="field-control">
              <input type="email" id="email" name="email" placeholder="you@university.edu" required autocomplete="email">
            </div>
          </div>
          <button type="submit" class="btn btn-amber btn-block">Send reset link</button>
        </form>
      <?php endif; ?>

      <div class="auth-divider">Remembered it?</div>
      <p class="auth-footer-note"><a href="login.php" class="link-amber">Back to sign in</a></p>
    </div>
  </main>

</div>

<script src="assets/js/main.js"></script>
</body>
</html>
