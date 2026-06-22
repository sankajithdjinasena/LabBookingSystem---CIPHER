<?php
/**
 * admin/settings.php — allocation policy & system settings.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';
require_once __DIR__ . '/../includes/settings.php';

require_role(['admin'], 1);
$user = current_user();
$active = 'admin_settings';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $weights = [
            'weight_urgency'      => $_POST['weight_urgency']      ?? '',
            'weight_team_size'    => $_POST['weight_team_size']    ?? '',
            'weight_fairness'     => $_POST['weight_fairness']     ?? '',
            'weight_request_time' => $_POST['weight_request_time'] ?? '',
        ];

        // Validate weights sum to 1.0
        $sum = array_sum(array_map('floatval', $weights));
        if (abs($sum - 1.0) > 0.001) {
            $error = 'The four priority weights must add up to exactly 1.0 (currently ' . round($sum, 3) . ').';
        } else {
            foreach ($weights as $key => $val) {
                save_setting($key, number_format((float) $val, 2, '.', ''));
            }

            $rrMin  = max(1800, (int) ($_POST['rr_min_duration']  ?? 14400));
            $rrSlot = max(900,  (int) ($_POST['rr_slot_duration'] ?? 7200));
            save_setting('rr_min_duration',  (string) $rrMin);
            save_setting('rr_slot_duration', (string) $rrSlot);

            save_setting('notify_email_enabled', isset($_POST['notify_email_enabled']) ? '1' : '0');
            save_setting('notify_from_email', trim($_POST['notify_from_email'] ?? ''));
            save_setting('notify_from_name',  trim($_POST['notify_from_name']  ?? ''));

            $success = 'Settings saved.';
        }
    }
}

$s = get_all_settings();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

// Helper to display seconds as h/m label
function seconds_to_label(int $s): string {
    if ($s % 3600 === 0) return ($s / 3600) . ' hour' . ($s / 3600 !== 1 ? 's' : '');
    if ($s % 60   === 0) return ($s / 60)   . ' minute' . ($s / 60 !== 1 ? 's' : '');
    return $s . ' seconds';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — SURAS Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.settings-grid{ display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
.weight-track{ display: flex; align-items: center; gap: 14px; }
.weight-track input[type=range]{ flex: 1; accent-color: var(--amber); }
.weight-track .val{ font-family: var(--mono); font-size: 14px; font-weight: 700; min-width: 38px; }
.weight-sum{ font-family: var(--mono); font-size: 13px; margin-top: 14px; }
.weight-sum.ok{ color: var(--sage); }
.weight-sum.bad{ color: var(--rust); }
@media(max-width:680px){ .settings-grid{ grid-template-columns: 1fr; } }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Allocation settings</h1>
        <p>Tune the priority-score formula, round-robin thresholds, and email notifications without touching code.</p>
      </div>
    </div>

    <?php if ($success): ?><div class="banner banner-success"><span>✓</span><span><?php echo e($success); ?></span></div><?php endif; ?>
    <?php if ($error):   ?><div class="banner banner-error"><span>⚠️</span><span><?php echo e($error); ?></span></div><?php endif; ?>

    <form method="POST" action="settings.php" id="settings-form">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

      <div class="settings-grid">

        <!-- Priority weights -->
        <div class="panel">
          <div class="panel-head"><h2>Priority score weights</h2></div>
          <p style="font-size:13.5px; margin-bottom:20px;">
            These four values must add up to <strong>1.0</strong>.
            The formula is:<br>
            <code style="font-size:12px;">Score = (wU × Urgency) + (wT × TeamSize) + (wF × Fairness) + (wR × RequestAge)</code>
          </p>

          <?php
          $weightFields = [
              'weight_urgency'      => ['Urgency (wU)',       'How critical is the session?'],
              'weight_team_size'    => ['Team size (wT)',     'How many people need the resource?'],
              'weight_fairness'     => ['Fairness (wF)',      'How few recent bookings has this user had?'],
              'weight_request_time' => ['Request age (wR)',   'First-come tiebreaker.'],
          ];
          foreach ($weightFields as $key => [$label, $hint]): ?>
            <div class="field">
              <label><?php echo $label; ?></label>
              <div class="weight-track">
                <input type="range" name="<?php echo $key; ?>" id="<?php echo $key; ?>"
                       min="0" max="1" step="0.05"
                       value="<?php echo e($s[$key] ?? '0.25'); ?>"
                       oninput="updateWeightDisplay()">
                <span class="val" id="<?php echo $key; ?>_val"><?php echo e($s[$key] ?? '0.25'); ?></span>
              </div>
              <p class="field-hint"><?php echo $hint; ?></p>
            </div>
          <?php endforeach; ?>

          <div class="weight-sum" id="weight-sum-display">Sum: —</div>
        </div>

        <!-- Round robin settings -->
        <div>
          <div class="panel">
            <div class="panel-head"><h2>Round-robin scheduling</h2></div>
            <p style="font-size:13.5px; margin-bottom:20px;">
              When two lab or room bookings overlap and at least one is longer than the threshold,
              the overlap is split into equal time slots and alternated by priority.
            </p>

            <div class="field">
              <label for="rr_min_duration">Trigger threshold</label>
              <select id="rr_min_duration" name="rr_min_duration">
                <?php foreach ([3600 => '1 hour', 7200 => '2 hours', 10800 => '3 hours', 14400 => '4 hours', 21600 => '6 hours'] as $val => $lbl): ?>
                  <option value="<?php echo $val; ?>" <?php echo (int)($s['rr_min_duration'] ?? 14400) === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
              </select>
              <p class="field-hint">Bookings at or above this duration trigger fair-share splitting.</p>
            </div>

            <div class="field">
              <label for="rr_slot_duration">Slot size</label>
              <select id="rr_slot_duration" name="rr_slot_duration">
                <?php foreach ([1800 => '30 minutes', 3600 => '1 hour', 7200 => '2 hours', 10800 => '3 hours'] as $val => $lbl): ?>
                  <option value="<?php echo $val; ?>" <?php echo (int)($s['rr_slot_duration'] ?? 7200) === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
              </select>
              <p class="field-hint">Size of each alternating fair-share slot.</p>
            </div>
          </div>

          <!-- Email notifications -->
          <div class="panel">
            <div class="panel-head"><h2>Email notifications</h2></div>
            <p style="font-size:13.5px; margin-bottom:20px;">
              Requires PHPMailer and valid SMTP credentials in <code>includes/config.php</code>.
            </p>

            <div class="field">
              <label class="checkbox" style="margin-bottom:14px;">
                <input type="checkbox" name="notify_email_enabled" value="1"
                  <?php echo ($s['notify_email_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                Enable email notifications
              </label>
            </div>

            <div class="field">
              <label for="notify_from_email">From email</label>
              <div class="field-control">
                <input type="email" id="notify_from_email" name="notify_from_email"
                       value="<?php echo e($s['notify_from_email'] ?? ''); ?>"
                       placeholder="noreply@university.edu">
              </div>
            </div>

            <div class="field">
              <label for="notify_from_name">From name</label>
              <div class="field-control">
                <input type="text" id="notify_from_name" name="notify_from_name"
                       value="<?php echo e($s['notify_from_name'] ?? ''); ?>"
                       placeholder="SURAS Resource System">
              </div>
            </div>
          </div>
        </div>

      </div><!-- /settings-grid -->

      <div style="margin-top: 8px;">
        <button type="submit" class="btn btn-amber">Save settings</button>
        <a href="dashboard.php" class="btn btn-ghost" style="margin-left:12px;">Cancel</a>
      </div>
    </form>

  </div>
</main>

<script src="../assets/js/main.js"></script>
<script>
  var weightKeys = ['weight_urgency','weight_team_size','weight_fairness','weight_request_time'];

  function updateWeightDisplay() {
    var sum = 0;
    weightKeys.forEach(function(k) {
      var v = parseFloat(document.getElementById(k).value) || 0;
      document.getElementById(k + '_val').textContent = v.toFixed(2);
      sum += v;
    });
    var el = document.getElementById('weight-sum-display');
    el.textContent = 'Sum: ' + sum.toFixed(2) + (Math.abs(sum - 1) < 0.001 ? ' ✓' : ' — must equal 1.0');
    el.className = 'weight-sum ' + (Math.abs(sum - 1) < 0.001 ? 'ok' : 'bad');
  }

  document.addEventListener('DOMContentLoaded', updateWeightDisplay);
</script>
</body>
</html>
