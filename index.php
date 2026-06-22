<?php
/**
 * index.php — NEXLAB public home page
 * Smart University Resource Allocation System
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// If someone is already signed in, send them straight to their dashboard
// rather than showing the marketing page again.
if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_user()['role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NEXLAB — Smart University Resource Allocation System</title>
<meta name="description" content="Book labs, rooms and equipment in seconds. NEXLAB replaces manual sign-up sheets with fair, conflict-free, priority-aware scheduling for your whole campus.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ============================== HERO ============================== -->
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow">Campus resource ledger</span>
      <h1>Every lab, room and<br>device, <em>booked fairly</em>.</h1>
      <p class="hero-lede">
        NEXLAB replaces the paper sign-up sheet and the group-chat scramble with
        one ledger for the whole university — real-time availability, automatic
        conflict resolution, and a priority system that's actually explainable.
      </p>
      <div class="hero-actions">
        <a href="login.php" class="btn btn-amber">Sign in to book</a>
        <a href="#workflow" class="btn btn-ghost">See how allocation works</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="num">4</span>
          <span class="label">resource categories</span>
        </div>
        <div class="hero-stat">
          <span class="num">24/7</span>
          <span class="label">live availability</span>
        </div>
        <div class="hero-stat">
          <span class="num">0</span>
          <span class="label">double-bookings</span>
        </div>
      </div>
    </div>

    <div class="slip-board" aria-hidden="true">
      <div class="slip slip-1">
        <div class="slip-row">
          <span class="slip-resource">Lab — Comp. Sci 204</span>
          <span class="stamp is-approved">Approved</span>
        </div>
        <div class="slip-meta">Booked by Project Team · Capstone Build</div>
        <div class="slip-time">Wed · 14:00–16:00</div>
      </div>

      <div class="slip slip-2">
        <div class="slip-row">
          <span class="slip-resource">Seminar Room B</span>
          <span class="stamp is-pending">Pending</span>
        </div>
        <div class="slip-meta">Requested by Faculty · Dept. Review</div>
        <div class="slip-time">Thu · 09:30–10:30</div>
      </div>

      <div class="slip slip-3">
        <div class="slip-row">
          <span class="slip-resource">Projector Kit 02</span>
          <span class="stamp is-waitlist">Waitlist</span>
        </div>
        <div class="slip-meta">Auto-reassign on cancellation</div>
        <div class="slip-time">Fri · 11:00–12:00</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== FEATURES ============================== -->
<section id="features">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">What's inside</span>
      <h2>Built to replace the spreadsheet.</h2>
      <p>Everything a resource office needs to stop chasing approvals by email,
      and everything a student needs to stop refreshing a sign-up sheet.</p>
    </div>

    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Secure sign-in</h3>
        <p>Role-based accounts for students, faculty, team leads and admins, each landing on the dashboard built for them.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h3>Real-time availability</h3>
        <p>See exactly which labs, rooms, devices and kits are free right now — no calling the front desk.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Automated approval</h3>
        <p>Routine bookings clear instantly; only edge cases get routed to a human for review.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⚠️</div>
        <h3>Conflict detection</h3>
        <p>Two requests for the same slot are caught the moment they happen, not after someone shows up locked out.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⭐</div>
        <h3>Priority allocation</h3>
        <p>A transparent, weighted score — not "whoever clicked fastest" — decides who gets a contested slot.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔄</div>
        <h3>Round robin sharing</h3>
        <p>Long requests during high demand get split into fair turns instead of one group locking a room all week.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⏳</div>
        <h3>Waitlists that work</h3>
        <p>Miss out on a slot and you're queued automatically — cancellations reassign themselves.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📧</div>
        <h3>Notifications</h3>
        <p>Approvals, rejections, reminders and waitlist movement land in your inbox, not lost in a thread.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Usage analytics</h3>
        <p>Administrators see peak hours, utilisation and department-wise demand at a glance.</p>
      </div>
    </div>
  </div>
</section>

<hr class="section-divider">

<!-- ============================== ROLES ============================== -->
<section id="roles">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Who it's for</span>
      <h2>One ledger, four very different days.</h2>
      <p>Each role sees its own dashboard, scoped to what it actually needs to get done.</p>
    </div>

    <div class="role-board">
      <div class="role-card">
        <span class="role-tag">Student</span>
        <h3>Book and go</h3>
        <ul>
          <li>Browse available resources</li>
          <li>Book in a few taps</li>
          <li>Track booking history</li>
          <li>Cancel and get notified</li>
        </ul>
      </div>
      <div class="role-card">
        <span class="role-tag">Faculty</span>
        <h3>Review and validate</h3>
        <ul>
          <li>Validate academic priority</li>
          <li>Review booking requests</li>
          <li>Monitor department usage</li>
        </ul>
      </div>
      <div class="role-card">
        <span class="role-tag">Project lead</span>
        <h3>Coordinate a team</h3>
        <ul>
          <li>Submit team bookings</li>
          <li>Manage project resource needs</li>
          <li>Track shared schedules</li>
        </ul>
      </div>
      <div class="role-card">
        <span class="role-tag">Administrator</span>
        <h3>Run the system</h3>
        <ul>
          <li>Manage resources and users</li>
          <li>Approve or reject requests</li>
          <li>Configure allocation policy</li>
          <li>View reports and analytics</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ============================== ALLOCATION ============================== -->
<section id="allocation">
  <div class="container">
    <div class="formula-panel">
      <div>
        <span class="eyebrow" style="color:#E3B97A">How contested slots are decided</span>
        <h2>Priority isn't a guess — it's a formula.</h2>
        <p>When two requests land on the same resource, NEXLAB scores both and
        allocates to the higher score. The loser isn't dropped — it's offered
        an alternative slot or placed on a waitlist that fills itself the
        moment a cancellation comes in.</p>
      </div>
      <div class="formula-card">
        <div><span class="term">Priority Score</span> =</div>
        <div>&nbsp;&nbsp;(0.4 × Urgency)
          <div class="weight-bar"><span style="width:40%"></span></div>
        </div>
        <div>+ (0.3 × Team Size)
          <div class="weight-bar"><span style="width:30%"></span></div>
        </div>
        <div>+ (0.2 × Fairness Score)
          <div class="weight-bar"><span style="width:20%"></span></div>
        </div>
        <div>+ (0.1 × Request Time)
          <div class="weight-bar"><span style="width:10%"></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== WORKFLOW ============================== -->
<section id="workflow">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">End to end</span>
      <h2>From search to confirmation.</h2>
    </div>

    <div class="workflow">
      <div class="workflow-step">
        <span class="idx">01</span>
        <h4>Sign in</h4>
        <p>Land on the dashboard built for your role.</p>
      </div>
      <div class="workflow-step">
        <span class="idx">02</span>
        <h4>Search a resource</h4>
        <p>Filter by category — labs, rooms, multimedia, devices.</p>
      </div>
      <div class="workflow-step">
        <span class="idx">03</span>
        <h4>Check availability</h4>
        <p>See open and taken slots in real time.</p>
      </div>
      <div class="workflow-step">
        <span class="idx">04</span>
        <h4>Resolve conflicts</h4>
        <p>If contested, priority score decides — or you're offered an alternative.</p>
      </div>
      <div class="workflow-step">
        <span class="idx">05</span>
        <h4>Get notified</h4>
        <p>Approval, rejection or waitlist update, straight to your inbox.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================== CTA ============================== -->
<section class="cta-band">
  <div class="container">
    <span class="eyebrow">Ready when you are</span>
    <h2>Stop refreshing the sign-up sheet.</h2>
    <p>Sign in with your university account and book your first resource in under a minute.</p>
    <div class="cta-actions">
      <a href="login.php" class="btn btn-amber">Sign in to NEXLAB</a>
      <a href="#features" class="btn btn-ghost">Review features</a>
    </div>
  </div>
</section>

<!-- ============================== FOOTER ============================== -->
<footer class="site-footer" id="contact">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a href="index.php" class="brand" style="margin-bottom:14px;">
          <img src="assets/img/logo.png" alt="NEXLAB Logo" class="brand-mark-img" style="    height: 85px;
    width: auto;
    object-fit: contain;
    border-radius: 19%;">
          <span>
            <span class="brand-name">NEXLAB</span>
            <span class="brand-sub">RESOURCE LEDGER</span>
          </span>
        </a>
        <p style="font-size:13.5px; max-width:32ch;">Smart University Resource Allocation System — built to keep shared campus resources fairly and transparently allocated.</p>
      </div>
      <div>
        <h5>Resources</h5>
        <ul>
          <li><a href="index.php#features">Features</a></li>
          <li><a href="index.php#allocation">Allocation logic</a></li>
          <li><a href="index.php#workflow">Workflow</a></li>
        </ul>
      </div>
      <div>
        <h5>Roles</h5>
        <ul>
          <li><a href="index.php#roles">Students</a></li>
          <li><a href="index.php#roles">Faculty</a></li>
          <li><a href="index.php#roles">Administrators</a></li>
        </ul>
      </div>
      <div>
        <h5>Contact</h5>
        <ul>
          <li><a href="mailto:resources@university.edu">resources@university.edu</a></li>
          <li><a href="login.php">Sign in</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?php echo date('Y'); ?> NEXLAB · Team Predictra · CIPHER 2.0 Case Analysis Competition</span>
      <span>Built for academic and educational purposes.</span>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
