<?php
/**
 * admin/booking.php — create a booking on behalf of any user.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['admin', 'faculty'], 1);
$user = current_user();
$active = 'booking';

$pdo = get_db_connection();
$allResources = fetch_resources();

$stmt = $pdo->query("SELECT id, full_name, role FROM users ORDER BY full_name");
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

$old = [
    'target_user_id' => '',
    'purpose'    => '',
    'date'       => date('Y-m-d'),
    'end_date'   => date('Y-m-d'),
    'start_time' => '10:00',
    'end_time'   => '12:00',
    'urgency'    => 3,
    'team_size'  => 1,
    'resource_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['target_user_id'] = (int) ($_POST['target_user_id'] ?? 0);
    $old['resource_id'] = (int) ($_POST['resource_id'] ?? 0);
    $old['purpose']    = trim($_POST['purpose'] ?? '');
    $old['date']       = $_POST['date'] ?? $old['date'];
    $old['end_date']   = $_POST['end_date'] ?? $_POST['date'] ?? $old['end_date'];
    $old['start_time'] = $_POST['start_time'] ?? $old['start_time'];
    $old['end_time']   = $_POST['end_time'] ?? $old['end_time'];
    $old['urgency']    = (int) ($_POST['urgency'] ?? 3);
    $old['team_size']  = max(1, (int) ($_POST['team_size'] ?? 1));

    $resource = $old['resource_id'] ? get_resource($old['resource_id']) : null;
    $start = $old['date'] . ' ' . $old['start_time'] . ':00';
    $end   = $old['end_date'] . ' ' . $old['end_time'] . ':00';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$old['target_user_id']) {
        $error = 'Please select a user to book for.';
    } elseif (!$resource) {
        $error = 'Please choose a resource to book.';
    } elseif ($resource['status'] !== 'available') {
        $error = 'That resource is currently unavailable.';
    } elseif ($old['purpose'] === '') {
        $error = 'Please tell us what the booking is for.';
    } elseif (strtotime($end) <= strtotime($start)) {
        $error = 'End date/time must be strictly after the start date/time.';
    } elseif (strtotime($start) < time() - 3600) {
        $error = "You can't book a slot in the past.";
    } else {
        $durationSecs = strtotime($end) - strtotime($start);
        $maxDur = 48 * 3600; // Admins get up to 48 hours

        if ($durationSecs > $maxDur) {
            $error = 'Maximum allowed continuous booking duration is 48 hours.';
        } else {
            $result = create_booking($old['target_user_id'], $resource['id'], $old['purpose'] . ' (Admin Override)', $start, $end, $old['urgency'], $old['team_size']);
            $success = "Successfully booked resource for " . ($durationSecs/3600) . " hours! Any conflicting student bookings were automatically cancelled.";
            // Reset fields
            $old['purpose'] = '';
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
<title>Admin Booking Override — SURAS</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container" style="max-width: 800px;">

    <div class="page-head">
      <div>
        <h1>Admin Booking Panel</h1>
        <p>Create special bookings up to 48 hours on behalf of any user. These bookings will instantly override and cancel any overlapping student requests.</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="banner banner-error">
        <span>⚠️</span>
        <span><?php echo e($error); ?></span>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="banner banner-success" style="background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; padding:15px; border-radius:6px; margin-bottom:20px;">
        <span>✅</span>
        <span><?php echo e($success); ?></span>
      </div>
    <?php endif; ?>

    <div class="panel">
    <form method="POST" action="booking.php">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

        <div class="field field-full" style="margin-bottom:22px;">
        <label for="target_user_id">Book on behalf of</label>
        <select id="target_user_id" name="target_user_id" required>
            <option value="">— Select User —</option>
            <?php foreach ($allUsers as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo $old['target_user_id'] == $u['id'] ? 'selected' : ''; ?>>
                <?php echo e($u['full_name']); ?> (<?php echo e(str_replace('_', ' ', $u['role'])); ?>)
            </option>
            <?php endforeach; ?>
        </select>
        </div>

        <div class="field field-full" style="margin-bottom:22px;">
        <label for="resource_id">Resource</label>
        <select id="resource_id" name="resource_id" required>
            <option value="">— Select a resource —</option>
            <?php foreach ($allResources as $r): ?>
            <option value="<?php echo (int) $r['id']; ?>"
                <?php echo $old['resource_id'] == $r['id'] ? 'selected' : ''; ?>
                <?php echo $r['status'] !== 'available' ? 'disabled' : ''; ?>>
                <?php echo e($r['name']); ?> — <?php echo e(category_label($r['category'])); ?><?php echo $r['status'] !== 'available' ? ' (unavailable)' : ''; ?>
            </option>
            <?php endforeach; ?>
        </select>
        </div>

        <div class="form-grid">
        <div class="field field-full">
            <label for="purpose">Purpose</label>
            <textarea id="purpose" name="purpose" placeholder="e.g. Special weekend research project" required><?php echo e($old['purpose']); ?></textarea>
        </div>

        <div class="field">
            <label for="date">Start Date</label>
            <div class="field-control">
            <input type="date" id="date" name="date" value="<?php echo e($old['date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="field">
            <label for="end_date">End Date</label>
            <div class="field-control">
            <input type="date" id="end_date" name="end_date" value="<?php echo e($old['end_date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
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

        <div class="field">
            <label for="team_size">Team size</label>
            <div class="field-control">
            <input type="number" id="team_size" name="team_size" min="1" max="50" value="<?php echo (int) $old['team_size']; ?>" required>
            </div>
        </div>
        </div>
        <p style="font-size:12px; color:#888; margin-top:5px;">Note: Admin override bookings can span multiple days (maximum of 48 hours continuously) and will automatically cancel student bookings in case of overlaps.</p>

        <button type="submit" class="btn btn-amber" style="margin-top: 15px;">Override & Create Booking</button>
    </form>
    </div>

  </div>
</main>

</body>
</html>
