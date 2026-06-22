<?php
/**
 * reset-password.php — set a new password via a reset token.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_user()['role']));
    exit;
}

$token  = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error  = '';
$done   = false;

// Validate the token whether GET or POST.
$resetRow = null;
if ($token !== '') {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "SELECT * FROM password_resets
         WHERE token = :token AND used = 0 AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute(['token' => $token]);
    $resetRow = $stmt->fetch();
}

if ($token === '' || !$resetRow) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetRow) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 8) {
            $error = 'Your new password needs to be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = "Those passwords don't match.";
        } else {
            $pdo = get_db_connection();
            $pdo->beginTransaction();
            try {
                // Update the user's password.
                $update = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
                $update->execute([
                    'hash'  => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $resetRow['email'],
                ]);

                // Mark the token as used so it can't be replayed.
                $expire = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
                $expire->execute(['token' => $token]);

                $pdo->commit();
                $done = true;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Something went wrong. Please try again.';
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
<title>Reset Password — SURAS</title>
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
      <p class="auth-quote">"A strong password is the first line of fair resource allocation."</p>
      <span class="auth-quote-by">— choose wisely</span>
    </div>
    <div class="auth-aside-bottom">
      <div class="auth-ledger">
        <div class="row"><span>Minimum length</span><span>8 characters</span></div>
        <div class="row"><span>One-time link</span><span>Expires after use</span></div>
      </div>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <span class="eyebrow">Account recovery</span>
      <h1>Set a new password</h1>

      <?php if ($done): ?>
        <div class="alert alert-success" role="status">
          <span>✓</span>
          <span>Password updated. You can now sign in with your new password.</span>
        </div>
        <a href="login.php" class="btn btn-amber btn-block" style="margin-top:8px;">Go to sign in</a>

      <?php elseif ($error && !$resetRow): ?>
        <div class="alert alert-error" role="alert">
          <span>⚠️</span>
          <span><?php echo e($error); ?></span>
        </div>
        <a href="forgot-password.php" class="btn btn-ghost btn-block" style="margin-top:8px;">Request a new link</a>

      <?php else: ?>
        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">
            <span>⚠️</span>
            <span><?php echo e($error); ?></span>
          </div>
        <?php endif; ?>

        <p class="auth-sub">Resetting password for <strong><?php echo e($resetRow['email']); ?></strong></p>

        <form method="POST" action="reset-password.php">
          <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
          <input type="hidden" name="token" value="<?php echo e($token); ?>">

          <div class="field">
            <label for="password">New password</label>
            <div class="field-control">
              <input type="password" id="password" name="password"
                     placeholder="At least 8 characters" autocomplete="new-password" required>
              <button type="button" class="field-toggle" data-toggle-password="password" aria-pressed="false">Show</button>
            </div>
          </div>

          <div class="field">
            <label for="confirm_password">Confirm new password</label>
            <div class="field-control">
              <input type="password" id="confirm_password" name="confirm_password"
                     placeholder="Repeat password" autocomplete="new-password" required>
              <button type="button" class="field-toggle" data-toggle-password="confirm_password" aria-pressed="false">Show</button>
            </div>
          </div>

          <button type="submit" class="btn btn-amber btn-block">Update password</button>
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
