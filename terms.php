<?php
/**
 * terms.php — NEXLAB Terms of Service
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
<title>Terms of Service — NEXLAB</title>
<meta name="description" content="The terms that govern your use of NEXLAB.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container nav">
    <a href="index.php" class="brand">
      <img src="assets/img/logo.png" alt="NEXLAB Logo" class="brand-mark-img" style="    height: 85px;
    width: auto;
    object-fit: contain;
    border-radius: 19%;">
      <span>
        <span class="brand-name">NEXLAB</span>
        <span class="brand-sub">RESOURCE LEDGER</span>
      </span>
    </a>

    <button class="nav-toggle" aria-label="Toggle menu"><span></span></button>

    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="terms.php" class="is-active">Terms</a></li>
      <li><a href="privacy-policy.php">Privacy</a></li>
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
        <h1>Terms of Service</h1>
        <p>Last updated: <?php echo $last_updated; ?></p>
      </div>
    </div>

    <div class="booking-layout">

      <!-- ===================== Main content ===================== -->
      <div>

        <div class="panel" id="intro">
          <p style="margin:0;">
            By using NEXLAB, you agree to the following terms for booking and managing university resources.
          </p>
        </div>

        <div class="panel" id="roles">
          <div class="panel-head"><h2>1. User roles</h2></div>
          <p style="margin:0;">
            Students submit requests, Team Leaders manage team bookings, Faculty validate academic-priority
            requests, and Administrators manage resources and policies.
          </p>
        </div>

        <div class="panel" id="account">
          <div class="panel-head"><h2>2. Account responsibility</h2></div>
          <ul>
            <li>Keep your login credentials confidential</li>
            <li>Provide accurate booking information (urgency, team size, purpose)</li>
            <li>You're responsible for all activity under your account</li>
          </ul>
        </div>

        <div class="panel" id="booking">
          <div class="panel-head"><h2>3. Booking process</h2></div>
          <ul>
            <li>Requests are checked against real-time availability</li>
            <li>Conflicts are resolved using priority scores (urgency, team size, fairness, request time)</li>
            <li>If unavailable, you may get an alternative slot, alternative resource, or waitlist spot</li>
            <li>Status updates are sent via email/SMS</li>
          </ul>
        </div>

        <div class="panel" id="cancel">
          <div class="panel-head"><h2>4. Cancellations &amp; no-shows</h2></div>
          <p style="margin:0;">
            Cancel in advance when possible. Unconfirmed bookings expire automatically. Repeated no-shows
            may lower your fairness score and future priority.
          </p>
        </div>

        <div class="panel" id="prohibited">
          <div class="panel-head"><h2>5. Prohibited conduct</h2></div>
          <ul>
            <li>Submitting false urgency or team size to game your priority score</li>
            <li>Booking resources for unrelated purposes</li>
            <li>Sharing your account or attempting unauthorized access</li>
          </ul>
          <p style="margin:0;">Violations may lead to booking restrictions or account suspension.</p>
        </div>

        <div class="banner banner-warn" id="override">
          <span>⚠️</span>
          <span><strong>Admin &amp; emergency overrides.</strong> Administrators may reassign or cancel bookings to resolve conflicts. Officially sanctioned emergency requests may override existing bookings — affected users are notified and offered alternatives.</span>
        </div>

        <div class="panel" id="liability">
          <div class="panel-head"><h2>6. Liability</h2></div>
          <p style="margin:0;">
            NEXLAB is provided "as is" for academic purposes (CIPHER 2.0 — Team Predictra). We're not liable
            for losses from downtime, scheduling errors, or missed notifications.
          </p>
        </div>

        <div class="panel" id="contact">
          <div class="panel-head"><h2>7. Contact</h2></div>
          <p style="margin:0;">
            Questions about these terms? Email
            <a class="link-amber" href="mailto:support@NEXLAB.edu">support@NEXLAB.edu</a>.
          </p>
        </div>

      </div>

      <!-- ===================== Sticky quick nav ===================== -->
      <aside class="summary-card panel">
        <span class="eyebrow" style="margin-bottom:14px;">On this page</span>
        <div class="summary-row"><a class="link-amber" href="#roles">User roles</a></div>
        <div class="summary-row"><a class="link-amber" href="#account">Account responsibility</a></div>
        <div class="summary-row"><a class="link-amber" href="#booking">Booking process</a></div>
        <div class="summary-row"><a class="link-amber" href="#cancel">Cancellations &amp; no-shows</a></div>
        <div class="summary-row"><a class="link-amber" href="#prohibited">Prohibited conduct</a></div>
        <div class="summary-row"><a class="link-amber" href="#override">Admin overrides</a></div>
        <div class="summary-row"><a class="link-amber" href="#liability">Liability</a></div>
        <div class="summary-row"><a class="link-amber" href="#contact">Contact</a></div>
      </aside>

    </div>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-bottom">
    <span>&copy; <?php echo date('Y'); ?> NEXLAB &mdash; Team Predictra</span>
    <span>
      <a href="privacy-policy.php" class="link-amber">Privacy Policy</a>
    </span>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
