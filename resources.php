<?php
/**
 * resources.php — browse / search / filter resources by category.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$active = 'resources';

$category = $_GET['category'] ?? 'all';
$search   = trim($_GET['q'] ?? '');
$resources = fetch_resources($category, $search);

$categories = [
    'all'        => 'All resources',
    'lab'        => 'Computer Labs',
    'room'       => 'Meeting Rooms',
    'multimedia' => 'Multimedia',
    'device'     => 'Testing Devices',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resources — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Resources</h1>
        <p>Browse what's available across campus, and book what you need.</p>
      </div>
    </div>

    <form class="filter-bar" method="GET" action="resources.php">
      <input type="search" name="q" placeholder="Search by name or location…" value="<?php echo e($search); ?>">
      <?php if ($category !== 'all'): ?>
        <input type="hidden" name="category" value="<?php echo e($category); ?>">
      <?php endif; ?>
      <button type="submit" class="btn btn-ghost">Search</button>
    </form>

    <div class="chip-group" style="margin-bottom: 28px;">
      <?php foreach ($categories as $key => $label): ?>
        <a class="chip <?php echo $category === $key ? 'is-active' : ''; ?>"
           href="resources.php?category=<?php echo e($key); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>">
          <?php echo e($label); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($resources)): ?>
      <div class="empty-state">
        <span class="empty-icon">🔍</span>
        <p>No resources match that search. Try a different category or keyword.</p>
      </div>
    <?php else: ?>
      <div class="resource-grid">
        <?php foreach ($resources as $r): ?>
          <?php $isAvailable = $r['status'] === 'available'; ?>
          <div class="resource-card">
            <div class="resource-card-top">
              <span class="resource-icon"><?php echo category_icon($r['category']); ?></span>
              <span class="availability-dot <?php echo $isAvailable ? 'is-available' : 'is-unavailable'; ?>">
                <?php echo $isAvailable ? 'Available' : ucfirst($r['status']); ?>
              </span>
            </div>
            <div>
              <h3><?php echo e($r['name']); ?></h3>
              <p class="resource-meta">
                <?php echo e(category_label($r['category'])); ?><?php echo $r['location'] ? ' · ' . e($r['location']) : ''; ?>
                <?php echo $r['capacity'] ? ' · Capacity ' . (int) $r['capacity'] : ''; ?>
              </p>
            </div>
            <p class="resource-desc"><?php echo e($r['description']); ?></p>
            <div class="resource-foot">
              <?php if ($isAvailable): ?>
                <a href="booking.php?resource_id=<?php echo (int) $r['id']; ?>" class="btn btn-amber" style="padding:9px 18px; font-size:13.5px;">Book now</a>
              <?php else: ?>
                <button class="btn btn-ghost" style="padding:9px 18px; font-size:13.5px;" disabled>Unavailable</button>
              <?php endif; ?>
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