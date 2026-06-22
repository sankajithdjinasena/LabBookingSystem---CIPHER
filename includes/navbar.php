<?php
/**
 * Public navbar partial — included by index.php and similar pages.
 * Expects nothing from the caller; safe to include anywhere.
 */
?>
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

    <nav aria-label="Primary">
      <ul class="nav-links">
        <li><a href="index.php#features">Features</a></li>
        <li><a href="index.php#roles">Who it's for</a></li>
        <li><a href="index.php#allocation">Allocation</a></li>
        <li><a href="index.php#workflow">How it works</a></li>
      </ul>
    </nav>

    <div class="nav-actions">
      <a href="login.php" class="btn btn-ghost">Sign in</a>
      <a href="login.php" class="btn btn-primary">Book a resource</a>
      <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
        <span></span>
      </button>
    </div>
  </div>
</header>
