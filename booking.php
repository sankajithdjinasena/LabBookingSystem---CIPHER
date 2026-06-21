<?php
/**
 * booking.php — create a booking for a resource.
 * Accepts ?resource_id= to preselect a resource (from resources.php),
 * or shows a resource picker if none is given.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$active = 'resources';

$allResources = fetch_resources();
$error = '';

$selectedResourceId = (int) ($_GET['resource_id'] ?? $_POST['resource_id'] ?? 0);

$old = [
    'purpose'    => '',
    'date'       => date('Y-m-d'),
    'start_time' => '10:00',
    'end_time'   => '12:00',
    'urgency'    => 3,
    'team_size'  => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['purpose']    = trim($_POST['purpose'] ?? '');
    $old['date']       = $_POST['date'] ?? $old['date'];
    $old['start_time'] = $_POST['start_time'] ?? $old['start_time'];
    $old['end_time']   = $_POST['end_time'] ?? $old['end_time'];
    $old['urgency']    = (int) ($_POST['urgency'] ?? 3);
    $old['team_size']  = max(1, (int) ($_POST['team_size'] ?? 1));

    $resource = $selectedResourceId ? get_resource($selectedResourceId) : null;
    $start = $old['date'] . ' ' . $old['start_time'] . ':00';
    $end   = $old['date'] . ' ' . $old['end_time'] . ':00';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$resource) {
        $error = 'Please choose a resource to book.';
    } elseif ($resource['status'] !== 'available') {
        $error = 'That resource is currently unavailable for booking.';
    } elseif ($old['purpose'] === '') {
        $error = 'Please tell us what the booking is for.';
    } elseif (strtotime($start) === false || strtotime($end) === false || strtotime($end) <= strtotime($start)) {
        $error = 'Please choose a valid time range — the end time must be after the start time.';
    } elseif (strtotime($start) < time() - 300) {
        $error = "You can't book a slot in the past.";
    } elseif ($old['urgency'] < 1 || $old['urgency'] > 5) {
        $error = 'Urgency must be between 1 and 5.';
    } else {
        $result = create_booking($user['id'], $resource['id'], $old['purpose'], $start, $end, $old['urgency'], $old['team_size']);
        header('Location: dashboard.php?booked=' . ($result['status'] === 'waitlist' ? 'waitlist' : 'approved'));
        exit;
    }
}

$selectedResource = $selectedResourceId ? get_resource($selectedResourceId) : null;

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a resource — SURAS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Book a resource</h1>
        <p>Pick a time — conflicts are resolved automatically by priority, with a waitlist as backup.</p>
      </div>
      <a href="resources.php" class="btn btn-ghost">← Back to resources</a>
    </div>

    <?php if ($error): ?>
      <div class="banner banner-error">
        <span>⚠️</span>
        <span><?php echo e($error); ?></span>
      </div>
    <?php endif; ?>

    <div class="booking-layout">

      <div class="panel">
        <form method="POST" action="booking.php">
          <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

          <div class="field field-full" style="margin-bottom:22px;">
            <label for="resource_id">Resource</label>
            <select id="resource_id" name="resource_id" required onchange="window.location.href='booking.php?resource_id=' + this.value">
              <option value="">— Select a resource —</option>
              <?php foreach ($allResources as $r): ?>
                <option value="<?php echo (int) $r['id']; ?>"
                  <?php echo $selectedResourceId === (int) $r['id'] ? 'selected' : ''; ?>
                  <?php echo $r['status'] !== 'available' ? 'disabled' : ''; ?>>
                  <?php echo e($r['name']); ?> — <?php echo e(category_label($r['category'])); ?><?php echo $r['status'] !== 'available' ? ' (unavailable)' : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-grid">
            <div class="field field-full">
              <label for="purpose">Purpose</label>
              <textarea id="purpose" name="purpose" placeholder="e.g. Capstone project build session" required><?php echo e($old['purpose']); ?></textarea>
            </div>

            <div class="field">
              <label for="date">Date</label>
              <div class="field-control">
                <input type="date" id="date" name="date" value="<?php echo e($old['date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>

            <div class="field">
              <label for="team_size">Team size</label>
              <div class="field-control">
                <input type="number" id="team_size" name="team_size" min="1" max="50" value="<?php echo (int) $old['team_size']; ?>" required>
              </div>
            </div>

            <div class="field">
              <label for="start_time">Start time</label>
              <div class="field-control">
                <input type="time" id="start_time" name="start_time" value="<?php echo e($old['start_time']); ?>" required>
              </div>
            </div>

            <div class="field">
              <label for="end_time">End time</label>
              <div class="field-control">
                <input type="time" id="end_time" name="end_time" value="<?php echo e($old['end_time']); ?>" required>
              </div>
            </div>

            <div class="field field-full">
              <label for="urgency">Urgency <span style="color:var(--ink-soft); font-weight:400;">(1 = flexible, 5 = critical)</span></label>
              <div class="field-control">
                <input type="range" id="urgency" name="urgency" min="1" max="5" value="<?php echo (int) $old['urgency']; ?>"
                       style="width:100%; accent-color: var(--amber);"
                       oninput="document.getElementById('urgency-out').textContent = this.value">
              </div>
              <p class="field-hint">Selected: <strong id="urgency-out"><?php echo (int) $old['urgency']; ?></strong> / 5</p>
            </div>
          </div>

          <button type="submit" class="btn btn-amber" style="margin-top: 10px;">Submit booking request</button>
        </form>
      </div>

      <div class="panel summary-card">
        <?php if ($selectedResource): ?>
          <span class="resource-icon" style="margin-bottom:14px;"><?php echo category_icon($selectedResource['category']); ?></span>
          <div class="resource-name-lg"><?php echo e($selectedResource['name']); ?></div>
          <p class="resource-meta" style="margin-bottom:16px;">
            <?php echo e(category_label($selectedResource['category'])); ?><?php echo $selectedResource['location'] ? ' · ' . e($selectedResource['location']) : ''; ?>
          </p>
          <p style="font-size:13.5px;"><?php echo e($selectedResource['description']); ?></p>

          <div class="summary-row">
            <span class="k">Status</span>
            <span class="v"><?php echo e(ucfirst($selectedResource['status'])); ?></span>
          </div>
          <?php if ($selectedResource['capacity']): ?>
          <div class="summary-row">
            <span class="k">Capacity</span>
            <span class="v"><?php echo (int) $selectedResource['capacity']; ?> people</span>
          </div>
          <?php endif; ?>
          <div class="summary-row">
            <span class="k">If contested</span>
            <span class="v">Waitlist + alternative slot</span>
          </div>
        <?php else: ?>
          <p>Select a resource to see its details here.</p>
        <?php endif; ?>
      </div>

    </div>

  </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>