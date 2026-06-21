<?php
/**
 * privacy-policy.php — SURAS Privacy Policy
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$logged_in = is_logged_in();
$last_updated = 'June 21, 2026';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy — SURAS</title>
<meta name="description" content="How SURAS collects, uses, and protects your information.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container nav">
    <a href="index.php" class="brand">
      <span class="brand-mark">S</span>
      <span>
        <span class="brand-name">SURAS</span>
        <span class="brand-sub">RESOURCE LEDGER</span>
      </span>
    </a>

    <button class="nav-toggle" aria-label="Toggle menu"><span></span></button>

    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="terms.php">Terms</a></li>
      <li><a href="privacy-policy.php" class="is-active">Privacy</a></li>
    </ul>

    <div class="nav-actions">
      <?php if ($logged_in): ?>
        <a href="dashboard.php" class="btn btn-amber">Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost">Sign in</a>
        <a href="register.php" class="btn btn-amber">Get started</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <span class="eyebrow">Legal</span>
        <h1>Privacy Policy</h1>
        <p>Last updated: <?php echo $last_updated; ?></p>
      </div>
    </div>

    <div class="booking-layout">

      <!-- ===================== Main content ===================== -->
      <div>

        <div class="panel" id="intro">
          <p style="margin:0;">
            This policy explains what information SURAS collects when you book or manage university
            resources, and how that information is used, shared, and protected.
          </p>
        </div>

        <div class="panel" id="collect">
          <div class="panel-head"><h2>1. What we collect</h2></div>
          <ul>
            <li><strong>Account info</strong> — name, university ID, email, role (Student / Team Leader / Faculty / Admin)</li>
            <li><strong>Booking info</strong> — resource, time slot, purpose, team size, urgency</li>
            <li><strong>Usage data</strong> — booking history, priority scores, login activity</li>
          </ul>
        </div>

        <div class="panel" id="use">
          <div class="panel-head"><h2>2. How we use it</h2></div>
          <ul>
            <li>To process bookings, detect conflicts, and calculate priority scores</li>
            <li>To manage waitlists and suggest alternative slots or resources</li>
            <li>To send email/SMS booking notifications and reminders</li>
            <li>To generate usage analytics for administrators</li>
          </ul>
        </div>

        <div class="panel" id="access">
          <div class="panel-head"><h2>3. Who can see it</h2></div>
          <p style="margin:0;">
            Access is role-based. Students see their own bookings, team leaders see their team's requests,
            faculty see requests needing validation, and admins have full access for system management.
          </p>
        </div>

        <div class="panel" id="sharing">
          <div class="panel-head"><h2>4. Sharing</h2></div>
          <p style="margin:0;">
            We don't sell your data. It's shared only with our Email/SMS providers (to send notifications)
            and university staff for booking approval and audits.
          </p>
        </div>

        <div class="banner banner-info" id="security">
          <span>🔒</span>
          <span><strong>Security.</strong> We use role-based access control and secure storage to protect your data, though no system is 100% secure.</span>
        </div>

        <div class="panel" id="rights">
          <div class="panel-head"><h2>5. Your rights</h2></div>
          <p style="margin:0;">
            You can view, correct, or request deletion of your account data, subject to academic
            record-keeping requirements.
          </p>
        </div>

        <div class="panel" id="contact">
          <div class="panel-head"><h2>6. Contact</h2></div>
          <p style="margin:0;">
            Questions about this policy? Email
            <a class="link-amber" href="mailto:support@suras.edu">support@suras.edu</a>.
          </p>
        </div>

      </div>

      <!-- ===================== Sticky quick nav ===================== -->
      <aside class="summary-card panel">
        <span class="eyebrow" style="margin-bottom:14px;">On this page</span>
        <div class="summary-row"><a class="link-amber" href="#collect">What we collect</a></div>
        <div class="summary-row"><a class="link-amber" href="#use">How we use it</a></div>
        <div class="summary-row"><a class="link-amber" href="#access">Who can see it</a></div>
        <div class="summary-row"><a class="link-amber" href="#sharing">Sharing</a></div>
        <div class="summary-row"><a class="link-amber" href="#security">Security</a></div>
        <div class="summary-row"><a class="link-amber" href="#rights">Your rights</a></div>
        <div class="summary-row"><a class="link-amber" href="#contact">Contact</a></div>
      </aside>

    </div>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-bottom">
    <span>&copy; <?php echo date('Y'); ?> SURAS &mdash; Team Predictra</span>
    <span>
      <a href="terms.php" class="link-amber">Terms of Service</a>
    </span>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
