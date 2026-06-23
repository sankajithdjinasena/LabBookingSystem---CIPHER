<?php
/**
 * admin/analytics.php — NEXLAB Intelligence Dashboard
 * Predictive Demand Forecasting + Anomaly Detection
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';
require_once __DIR__ . '/../includes/analytics.php';

require_role(['admin'], 1);
$user  = current_user();
$active = 'analytics';

// Handle POST actions
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $actionMsg = 'error:Session expired. Please try again.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $act      = $_POST['action'] ?? '';

        if ($targetId && $act === 'flag') {
            $reason = trim($_POST['reason'] ?? 'Flagged by administrator after anomaly detection review.');
            flag_user_for_review($targetId, $reason);
            $actionMsg = 'success:User has been flagged for review.';
        } elseif ($targetId && $act === 'dismiss') {
            dismiss_user_flag($targetId);
            $actionMsg = 'success:Flag dismissed successfully.';
        } elseif ($targetId && $act === 'suspend') {
            suspend_user($targetId);
            $actionMsg = 'success:User account suspended.';
        } elseif ($targetId && $act === 'reactivate') {
            reactivate_user($targetId);
            $actionMsg = 'success:User account reactivated.';
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// Load data
$forecast        = get_demand_forecast(7);
$anomalies       = detect_anomalies();
$utilSummary     = get_utilization_summary(30);

// Group forecast by date for display
$forecastByDate  = [];
foreach ($forecast as $f) {
    $forecastByDate[$f['date']][] = $f;
}

// Separate critical/high alerts
$criticalForecast = array_filter($forecast, fn($f) => $f['risk_level'] === 'critical');
$highForecast     = array_filter($forecast, fn($f) => $f['risk_level'] === 'high');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Intelligence Dashboard — NEXLAB</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* ── Intelligence Dashboard Styles ── */
.intel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
@media(max-width:900px){ .intel-grid { grid-template-columns: 1fr; } }

.intel-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.07); overflow: hidden; }
.intel-card-header { padding: 18px 22px 14px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f0f0f0; }
.intel-card-header h2 { margin:0; font-size:16px; font-weight:700; }
.intel-card-header p { margin:0; font-size:12px; color:#888; }
.intel-card-body { padding: 0; }

/* Risk badge */
.risk-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.risk-critical { background:#fff0f0; color:#c62828; }
.risk-high     { background:#fff8e1; color:#e65100; }
.risk-medium   { background:#fffde7; color:#f57f17; }
.risk-low      { background:#f1f8e9; color:#2e7d32; }

/* Forecast rows */
.forecast-date-group { border-bottom: 1px solid #f5f5f5; }
.forecast-date-header { padding: 10px 22px; background: #f9fafb; font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .5px; }
.forecast-row { display: flex; align-items: center; padding: 12px 22px; gap: 16px; border-bottom: 1px solid #fafafa; transition: background .15s; }
.forecast-row:hover { background: #f9fbff; }
.forecast-resource { flex: 1; min-width: 0; }
.forecast-resource-name { font-size: 14px; font-weight: 600; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.forecast-resource-meta { font-size: 11px; color: #888; margin-top: 2px; }
.util-bar-wrap { width: 120px; flex-shrink: 0; }
.util-bar-bg { height: 8px; background: #eee; border-radius: 10px; overflow: hidden; }
.util-bar-fill { height: 100%; border-radius: 10px; transition: width .5s; }
.util-pct { font-size: 11px; font-weight: 700; margin-top: 3px; text-align: right; }
.suggested-slots { font-size: 11px; color: #1976d2; margin-top: 2px; }

/* Anomaly rows */
.anomaly-row { padding: 16px 22px; border-bottom: 1px solid #fafafa; transition: background .15s; }
.anomaly-row:hover { background: #fefefe; }
.anomaly-row:last-child { border-bottom: none; }
.anomaly-user-name { font-size:15px; font-weight:700; color:#1a1a2e; }
.anomaly-user-meta { font-size:12px; color:#888; margin-top:2px; }
.trigger-list { margin: 10px 0 0; display: flex; flex-direction: column; gap: 6px; }
.trigger-item { background: #f9f9f9; border-left: 3px solid #eee; padding: 6px 12px; border-radius: 0 6px 6px 0; font-size: 12px; }
.trigger-item.critical { border-left-color: #f44336; background: #fff5f5; }
.trigger-item.high     { border-left-color: #ff9800; background: #fffbf0; }
.trigger-item.medium   { border-left-color: #ffc107; background: #fffdf0; }
.trigger-label { font-weight: 700; margin-bottom: 2px; }
.trigger-detail { color: #666; }
.anomaly-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
.btn-xs { padding: 5px 14px; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; transition: all .2s; }
.btn-flag     { background: #fff3e0; color: #e65100; }
.btn-flag:hover { background: #ffe0b2; }
.btn-suspend  { background: #ffebee; color: #c62828; }
.btn-suspend:hover { background: #ffcdd2; }
.btn-dismiss  { background: #f3f4f6; color: #555; }
.btn-dismiss:hover { background: #e5e7eb; }
.btn-reactivate { background: #e8f5e9; color: #2e7d32; }
.btn-reactivate:hover { background: #c8e6c9; }
.suspended-badge { background: #ffebee; color: #c62828; font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; margin-left:8px; text-transform:uppercase; }
.flagged-badge { background: #fff3e0; color: #e65100; font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; margin-left:8px; text-transform:uppercase; }

/* Summary cards */
.summary-stat { text-align:center; padding: 20px 16px; }
.summary-stat .stat-num { font-size: 36px; font-weight: 800; line-height: 1; }
.summary-stat .stat-lbl { font-size: 12px; color: #888; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }
.summary-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-bottom: 1px solid #f0f0f0; }
.summary-stats-row > div { border-right: 1px solid #f0f0f0; }
.summary-stats-row > div:last-child { border-right: none; }

/* Util heatmap */
.util-summary-row { display: flex; align-items: center; padding: 10px 22px; border-bottom: 1px solid #fafafa; gap: 14px; }
.util-summary-name { flex:1; font-size:13px; font-weight:600; }
.util-summary-bar-wrap { width: 180px; }
.util-summary-pct { width: 48px; text-align: right; font-size: 13px; font-weight: 700; }

/* No-data state */
.no-data { padding: 40px 22px; text-align: center; color: #bbb; }
.no-data .icon { font-size: 40px; }
.no-data p { margin: 8px 0 0; font-size: 14px; }

/* Tab system */
.tab-bar { display:flex; gap:0; border-bottom:2px solid #f0f0f0; padding: 0 22px; background: #fafafa; }
.tab-btn { padding:12px 20px; font-size:13px; font-weight:600; color:#888; border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .2s; }
.tab-btn.active { color: #6c5ce7; border-bottom-color: #6c5ce7; }
.tab-panel { display:none; }
.tab-panel.active { display:block; }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
<div class="container" style="max-width:1100px;">

  <div class="page-head">
    <div>
      <h1>🧠 Intelligence Dashboard</h1>
      <p>AI-powered demand forecasting and anomaly detection for NEXLAB resources.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <?php if(count($criticalForecast)): ?>
        <span class="risk-badge risk-critical">⚠ <?= count($criticalForecast) ?> Critical Forecast</span>
      <?php endif; ?>
      <?php if(count($anomalies)): ?>
        <span class="risk-badge risk-high">🚨 <?= count($anomalies) ?> Anomalies</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($actionMsg): [$type, $msg] = explode(':', $actionMsg, 2); ?>
    <div class="banner banner-<?= $type === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
      <span><?= $type === 'success' ? '✓' : '⚠' ?></span><span><?= e($msg) ?></span>
    </div>
  <?php endif; ?>

  <!-- Top Stats Row -->
  <div class="intel-card" style="margin-bottom:24px;">
    <div class="summary-stats-row">
      <div class="summary-stat">
        <div class="stat-num" style="color:#6c5ce7;"><?= count($criticalForecast) + count($highForecast) ?></div>
        <div class="stat-lbl">High-Risk Days Ahead</div>
      </div>
      <div class="summary-stat">
        <div class="stat-num" style="color:#e17055;"><?= count($anomalies) ?></div>
        <div class="stat-lbl">Anomalous Users</div>
      </div>
      <div class="summary-stat">
        <div class="stat-num" style="color:#00b894;">
          <?= count(array_filter($utilSummary, fn($u) => $u['utilization_pct'] > 0)) ?>
        </div>
        <div class="stat-lbl">Active Resources (30d)</div>
      </div>
      <div class="summary-stat">
        <?php
          $avgUtil = count($utilSummary) > 0
            ? round(array_sum(array_column($utilSummary, 'utilization_pct')) / count($utilSummary), 1)
            : 0;
        ?>
        <div class="stat-num" style="color:#0984e3;"><?= $avgUtil ?>%</div>
        <div class="stat-lbl">Avg Utilization (30d)</div>
      </div>
    </div>
  </div>

  <div class="intel-grid">

    <!-- ── DEMAND FORECAST PANEL ── -->
    <div class="intel-card" style="grid-column: 1 / -1;">
      <div class="intel-card-header">
        <div>
          <h2>🔮 7-Day Demand Forecast</h2>
          <p>Predicted resource utilization based on historical day-of-week patterns from the last 8 weeks</p>
        </div>
      </div>

      <?php if (empty($forecast)): ?>
        <div class="no-data"><div class="icon">📊</div><p>Not enough historical data yet. Forecasts appear after the first week of bookings.</p></div>
      <?php else: ?>
        <!-- Tabs for days -->
        <div class="tab-bar" id="forecastTabs">
          <?php $first = true; foreach ($forecastByDate as $date => $rows): ?>
            <?php
              $hasAlert = !empty(array_filter($rows, fn($r) => $r['risk_level'] === 'critical' || $r['risk_level'] === 'high'));
              $label = (new DateTime($date))->format('D j');
            ?>
            <button class="tab-btn <?= $first ? 'active' : '' ?>"
                    onclick="switchTab('<?= $date ?>', this)"
                    id="tab-<?= $date ?>">
              <?= $hasAlert ? '⚠ ' : '' ?><?= $label ?>
            </button>
          <?php $first = false; endforeach; ?>
        </div>

        <?php $first = true; foreach ($forecastByDate as $date => $rows): ?>
          <div class="tab-panel <?= $first ? 'active' : '' ?>" id="panel-<?= $date ?>">
            <?php foreach ($rows as $f):
              $color = $f['risk_level'] === 'critical' ? '#f44336' :
                      ($f['risk_level'] === 'high'     ? '#ff9800' :
                      ($f['risk_level'] === 'medium'   ? '#ffc107' : '#4caf50'));
            ?>
            <div class="forecast-row">
              <div class="forecast-resource" style="min-width:0;flex:1;">
                <div class="forecast-resource-name"><?= e($f['resource_name']) ?></div>
                <div class="forecast-resource-meta">
                  <?= ucfirst($f['category']) ?>
                  <?= $f['confirmed_count'] ? " · {$f['confirmed_count']} booking(s) already placed" : '' ?>
                  <?= $f['data_weeks'] > 1 ? " · Based on {$f['data_weeks']} weeks of data" : '' ?>
                </div>
                <?php if (!empty($f['suggested_slots'])): ?>
                  <div class="suggested-slots">💡 Suggested buffer slots: <?= implode(', ', $f['suggested_slots']) ?></div>
                <?php endif; ?>
              </div>
              <div class="util-bar-wrap">
                <div class="util-bar-bg">
                  <div class="util-bar-fill" style="width:<?= $f['utilization'] ?>%;background:<?= $color ?>;"></div>
                </div>
                <div class="util-pct" style="color:<?= $color ?>;"><?= $f['utilization'] ?>%</div>
              </div>
              <span class="risk-badge risk-<?= $f['risk_level'] ?>">
                <?= strtoupper($f['risk_level']) ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php $first = false; endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ── ANOMALY DETECTION PANEL ── -->
    <div class="intel-card" style="grid-column: 1 / -1;">
      <div class="intel-card-header">
        <div>
          <h2>📊 Anomaly Detection</h2>
          <p>Users with unusual booking behaviour detected in the last 7–30 days</p>
        </div>
      </div>
      <div class="intel-card-body">
        <?php if (empty($anomalies)): ?>
          <div class="no-data">
            <div class="icon">✅</div>
            <p>No anomalies detected. All users are within normal booking patterns.</p>
          </div>
        <?php else: ?>
          <?php foreach ($anomalies as $uid => $entry):
            $u = $entry['user'];
            $isSuspended = $u['account_status'] === 'suspended';
            $isFlagged   = (bool)$u['is_flagged'];
          ?>
          <div class="anomaly-row">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <div>
                <span class="anomaly-user-name"><?= e($u['full_name']) ?></span>
                <?php if ($isSuspended): ?><span class="suspended-badge">Suspended</span><?php endif; ?>
                <?php if ($isFlagged && !$isSuspended): ?><span class="flagged-badge">Flagged</span><?php endif; ?>
                <div class="anomaly-user-meta">
                  <?= e($u['email']) ?> · <?= e(str_replace('_', ' ', $u['role'])) ?>
                  <?= $u['department'] ? ' · ' . e($u['department']) : '' ?>
                  <?= $u['flag_count'] > 0 ? " · Flagged {$u['flag_count']}x previously" : '' ?>
                </div>
              </div>
              <span class="risk-badge risk-<?= $entry['overall_severity'] ?>">
                <?= count($entry['triggers']) ?> trigger<?= count($entry['triggers']) > 1 ? 's' : '' ?> · <?= strtoupper($entry['overall_severity']) ?>
              </span>
            </div>

            <div class="trigger-list">
              <?php foreach ($entry['triggers'] as $t): ?>
              <div class="trigger-item <?= $t['severity'] ?>">
                <div class="trigger-label"><?= $t['label'] ?></div>
                <div class="trigger-detail"><?= e($t['detail']) ?></div>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="anomaly-actions">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <?php if (!$isFlagged): ?>
                  <input type="hidden" name="action" value="flag">
                  <button type="submit" class="btn-xs btn-flag">🚩 Flag for Review</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="dismiss">
                  <button type="submit" class="btn-xs btn-dismiss">✕ Dismiss Flag</button>
                <?php endif; ?>
              </form>

              <?php if (!$isSuspended): ?>
              <form method="POST" style="display:inline;"
                    onsubmit="return confirm('Suspend <?= e(addslashes($u['full_name'])) ?>? They will not be able to log in.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <input type="hidden" name="action" value="suspend">
                <button type="submit" class="btn-xs btn-suspend">🚫 Suspend Account</button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <input type="hidden" name="action" value="reactivate">
                <button type="submit" class="btn-xs btn-reactivate">✅ Reactivate Account</button>
              </form>
              <?php endif; ?>

              <a href="../admin/users.php?highlight=<?= $uid ?>" class="btn-xs btn-dismiss" style="text-decoration:none;">👤 View Profile</a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── 30-DAY UTILIZATION SUMMARY ── -->
    <div class="intel-card" style="grid-column: 1 / -1;">
      <div class="intel-card-header">
        <div>
          <h2>📈 30-Day Utilization Summary</h2>
          <p>Actual resource usage over the past 30 days (approved + completed bookings)</p>
        </div>
      </div>
      <div class="intel-card-body">
        <?php foreach ($utilSummary as $res):
          $pct   = min(100, (float)$res['utilization_pct']);
          $color = $pct >= 85 ? '#f44336' : ($pct >= 60 ? '#ff9800' : ($pct >= 30 ? '#2196f3' : '#9e9e9e'));
        ?>
        <div class="util-summary-row">
          <div class="util-summary-name">
            <?= e($res['name']) ?>
            <span style="font-size:11px;color:#aaa;font-weight:400;"> · <?= ucfirst($res['category']) ?></span>
          </div>
          <div class="util-summary-bar-wrap">
            <div class="util-bar-bg">
              <div class="util-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
            </div>
          </div>
          <div class="util-summary-pct" style="color:<?= $color ?>;"><?= $pct ?>%</div>
          <span style="font-size:11px;color:#aaa;width:140px;text-align:right;">
            <?= $res['total_bookings'] ?> booking<?= $res['total_bookings'] != 1 ? 's' : '' ?> · <?= $res['total_hours'] ?>h
          </span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($utilSummary)): ?>
          <div class="no-data"><p>No booking data yet.</p></div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /intel-grid -->
</div>
</main>

<script>
function switchTab(dateKey, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + dateKey).classList.add('active');
    btn.classList.add('active');
}
</script>
</body>
</html>
