<?php
/**
 * login.php — SURAS sign-in
 * Authenticates against the users table and routes by role.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already signed in? Skip the form entirely.
if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_user()['role']));
    exit;
}

$error = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $old_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try signing in again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "That email address doesn't look quite right.";
    } else {
        $user = attempt_login($email, $password);

        if ($user) {
            login_user($user);
            header('Location: ' . dashboard_for_role($user['role']));
            exit;
        }

        $error = 'Incorrect email or password. Please try again.';
    }
}

// Fresh CSRF token for the form (regenerated each load that doesn't redirect).
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — SURAS</title>
<meta name="description" content="Sign in to the Smart University Resource Allocation System to book labs, rooms, and equipment.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-shell">

  <!-- ===================== Left: brand / atmosphere ===================== -->
  <aside class="auth-aside">
    <div class="auth-aside-top">
      <a href="index.php" class="brand">
        <span class="brand-mark">S</span>
        <span>
          <span class="brand-name">SURAS</span>
          <span class="brand-sub">RESOURCE LEDGER</span>
        </span>
      </a>

      <p class="auth-quote">
        "The lab is free at two, the room is free at four —
        you just have to ask the ledger, not the hallway."
      </p>
      <span class="auth-quote-by">— how booking should feel</span>
    </div>

    <div class="auth-aside-bottom">
      <div class="auth-ledger">
        <div class="row"><span>Lab — Comp. Sci 204</span><span>Approved</span></div>
        <div class="row"><span>Seminar Room B</span><span>Pending</span></div>
        <div class="row"><span>Projector Kit 02</span><span>Waitlist</span></div>
      </div>
    </div>
  </aside>

  <!-- ===================== Right: sign-in form ===================== -->
  <main class="auth-main">
    <div class="auth-card">
      <span class="eyebrow">Welcome back</span>
      <h1>Sign in</h1>
      <p class="auth-sub">Use your university email to access your dashboard.</p>

      <div class="role-pills" aria-hidden="true">
        <span class="role-pill">Student</span>
        <span class="role-pill">Faculty</span>
        <span class="role-pill">Project lead</span>
        <span class="role-pill">Admin</span>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
          <span>⚠️</span>
          <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['logged_out'])): ?>
        <div class="alert alert-success" role="status">
          <span>✓</span>
          <span>You've been signed out.</span>
        </div>
      <?php endif; ?>

      <div id="login-client-error" class="alert alert-error" role="alert" hidden></div>

      <form id="login-form" action="login.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="field">
          <label for="email">University email</label>
          <div class="field-control">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="you@university.edu"
              value="<?php echo $old_email; ?>"
              autocomplete="username"
              required
            >
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="field-control">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            >
            <button type="button" class="field-toggle" data-toggle-password="password" aria-pressed="false">Show</button>
          </div>
        </div>

        <div class="field-row">
          <label class="checkbox">
            <input type="checkbox" name="remember">
            Keep me signed in
          </label>
          <a href="forgot-password.php" class="link-amber">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-amber btn-block">Sign in</button>
      </form>

      <div class="auth-divider">New to SURAS</div>

      <a href="register.php" class="btn btn-ghost btn-block" style="margin-bottom: 14px;">Create an account</a>

      <p class="auth-footer-note">
        Need a faculty or admin account?
        <a href="index.php#contact" class="link-amber">Contact resource admin</a>
      </p>
    </div>
  </main>

</div>

<script src="assets/js/main.js"></script>
</body>
</html>
