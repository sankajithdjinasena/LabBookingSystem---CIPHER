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
$user   = current_user();
$active = 'analytics';

// ── Handle POST actions ──────────────────────────────────────
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $actionMsg = 'error:Session expired. Please try again.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $act      = $_POST['action'] ?? '';

        if ($targetId && $act === 'flag') {
            flag_user_for_review($targetId, 'Flagged by administrator after anomaly detection review.');
            $actionMsg = 'success:User flagged for review.';
        } elseif ($targetId && $act === 'dismiss') {
            dismiss_user_flag($targetId);
            $actionMsg = 'success:Flag dismissed.';
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

// ── Load data ────────────────────────────────────────────────
$forecast       = get_demand_forecast(7);

$minFreq  = max(1, (int)($_GET['min_freq'] ?? 40));
$minRapid = max(1, (int)($_GET['min_rapid'] ?? 40));
$anomalies      = detect_anomalies($minFreq, $minRapid);

$utilSummary    = get_utilization_summary(30);

$forecastByDate = [];
foreach ($forecast as $f) {
    $forecastByDate[$f['date']][] = $f;
}

$criticalForecast = array_filter($forecast, fn($f) => $f['risk_level'] === 'critical');
$highForecast     = array_filter($forecast, fn($f) => $f['risk_level'] === 'high');

$avgUtil = count($utilSummary) > 0
    ? round(array_sum(array_column($utilSummary, 'utilization_pct')) / count($utilSummary), 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Intelligence Dashboard — NEXLAB</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* ── Layout ── */
.intel-section { background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.06); margin-bottom:24px; overflow:hidden; }

/* ── Section header ── */
.section-header { padding:18px 24px 16px; border-bottom:1px solid #f0f0f0; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.section-header-left h2 { margin:0 0 3px; font-size:15px; font-weight:700; color:#1a1a2e; letter-spacing:-.2px; }
.section-header-left p  { margin:0; font-size:12px; color:#999; }
.section-tag { display:inline-block; padding:3px 10px; border-radius:4px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.tag-forecast  { background:#ede9fe; color:#5b21b6; }
.tag-anomaly   { background:#fef3c7; color:#92400e; }
.tag-summary   { background:#dbeafe; color:#1e40af; }

/* ── Top stat strip ── */
.stat-strip { display:grid; grid-template-columns:repeat(4,1fr); border-bottom:1px solid #f0f0f0; }
.stat-strip-cell { padding:22px 20px; text-align:center; border-right:1px solid #f0f0f0; }
.stat-strip-cell:last-child { border-right:none; }
.stat-strip-num { font-size:32px; font-weight:800; line-height:1; }
.stat-strip-lbl { font-size:11px; color:#999; margin-top:5px; text-transform:uppercase; letter-spacing:.5px; }

/* ── Risk badge ── */
.risk-tag { display:inline-block; padding:2px 9px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.risk-critical { background:#fee2e2; color:#b91c1c; }
.risk-high     { background:#ffedd5; color:#c2410c; }
.risk-medium   { background:#fefce8; color:#854d0e; }
.risk-low      { background:#f0fdf4; color:#166534; }

/* ── Tab bar ── */
.tab-bar { display:flex; background:#f9fafb; border-bottom:1px solid #f0f0f0; padding:0 24px; overflow-x:auto; }
.tab-btn { padding:11px 18px; font-size:12px; font-weight:600; color:#888; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-1px; cursor:pointer; white-space:nowrap; transition:color .15s, border-color .15s; }
.tab-btn:hover { color:#5b21b6; }
.tab-btn.active { color:#5b21b6; border-bottom-color:#5b21b6; }
.tab-btn.has-alert { color:#c2410c; }
.tab-btn.has-alert.active { border-bottom-color:#c2410c; }
.tab-panel { display:none; }
.tab-panel.active { display:block; }

/* ── Forecast row ── */
.forecast-row { display:flex; align-items:center; gap:20px; padding:13px 24px; border-bottom:1px solid #fafafa; transition:background .12s; }
.forecast-row:hover { background:#f9fafb; }
.forecast-row:last-child { border-bottom:none; }
.forecast-name { flex:1; min-width:0; }
.forecast-name strong { display:block; font-size:13px; font-weight:600; color:#1a1a2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.forecast-name span { display:block; font-size:11px; color:#aaa; margin-top:2px; }
.forecast-hint { font-size:11px; color:#6366f1; margin-top:3px; }
.bar-wrap { width:140px; flex-shrink:0; }
.bar-bg { height:6px; background:#eee; border-radius:6px; overflow:hidden; }
.bar-fill { height:100%; border-radius:6px; }
.bar-label { font-size:11px; font-weight:700; margin-top:3px; text-align:right; }

/* ── Anomaly rows ── */
.anomaly-row { padding:18px 24px; border-bottom:1px solid #f5f5f5; }
.anomaly-row:last-child { border-bottom:none; }
.anomaly-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
.anomaly-name { font-size:14px; font-weight:700; color:#1a1a2e; }
.anomaly-meta { font-size:12px; color:#999; margin-top:3px; }
.status-pill { display:inline-block; padding:1px 8px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-left:6px; }
.pill-suspended { background:#fee2e2; color:#b91c1c; }
.pill-flagged   { background:#ffedd5; color:#c2410c; }

/* ── Trigger items ── */
.trigger-list { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
.trigger-item { display:flex; gap:10px; align-items:flex-start; padding:9px 12px; border-radius:6px; border-left:3px solid; font-size:12px; }
.trigger-item.critical { border-left-color:#b91c1c; background:#fef2f2; }
.trigger-item.high     { border-left-color:#c2410c; background:#fff7ed; }
.trigger-item.medium   { border-left-color:#b45309; background:#fffbeb; }
.trigger-item.low      { border-left-color:#475569; background:#f8fafc; }
.trigger-type { width:70px; flex-shrink:0; font-weight:700; color:inherit; text-transform:uppercase; font-size:10px; letter-spacing:.3px; padding-top:1px; }
.trigger-content { flex:1; display:flex; flex-direction:column; gap:6px; }
.trigger-detail-text { color:#555; line-height:1.4; }

/* ── Live threshold meter ── */
.trigger-meter-wrap { display:flex; align-items:center; gap:10px; }
.trigger-meter-bg { flex:1; max-width:180px; height:6px; background:#e2e8f0; border-radius:6px; position:relative; overflow:hidden; }
.trigger-meter-fill { height:100%; border-radius:6px; }
.trigger-meter-marker { position:absolute; top:0; bottom:0; width:2px; background:#1e293b; z-index:2; }
.trigger-meter-lbl { font-size:10px; font-weight:700; color:#64748b; font-variant-numeric: tabular-nums; }

/* ── Action buttons ── */
.action-bar { display:flex; gap:8px; flex-wrap:wrap; }
.act-btn { padding:5px 14px; font-size:12px; font-weight:600; border-radius:5px; border:1px solid transparent; cursor:pointer; transition:all .15s; line-height:1.5; }
.act-btn-flag     { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.act-btn-flag:hover { background:#ffedd5; }
.act-btn-dismiss  { background:#f9fafb; color:#555; border-color:#e5e7eb; }
.act-btn-dismiss:hover { background:#f3f4f6; }
.act-btn-suspend  { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
.act-btn-suspend:hover { background:#fee2e2; }
.act-btn-reactivate { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.act-btn-reactivate:hover { background:#dcfce7; }
.act-btn-view     { background:#f9fafb; color:#3730a3; border-color:#e0e7ff; text-decoration:none; display:inline-flex; align-items:center; }
.act-btn-view:hover { background:#eef2ff; }

/* ── Dynamic Filter ── */
.filter-bar { display:flex; gap:16px; padding:12px 24px; background:#f8fafc; border-bottom:1px solid #f0f0f0; align-items:center; flex-wrap:wrap; }
.filter-group { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:#475569; }
.filter-input { width:60px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px; font-weight:700; color:#1e293b; text-align:center; }
.filter-input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,0.2); }
.filter-btn { padding:6px 14px; background:#1e293b; color:#fff; border:none; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s; }
.filter-btn:hover { background:#0f172a; }

/* ── Util summary rows ── */
.util-row { display:flex; align-items:center; gap:16px; padding:11px 24px; border-bottom:1px solid #fafafa; }
.util-row:last-child { border-bottom:none; }
.util-row-name { flex:1; font-size:13px; font-weight:600; color:#1a1a2e; }
.util-row-cat  { font-size:11px; font-weight:400; color:#bbb; margin-left:4px; }
.util-row-bar  { width:200px; }
.util-row-pct  { width:44px; text-align:right; font-size:13px; font-weight:700; }
.util-row-meta { width:150px; text-align:right; font-size:11px; color:#bbb; }

/* ── No data ── */
.no-data-state { padding:48px 24px; text-align:center; }
.no-data-state .nd-icon { width:36px; height:36px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
.no-data-state p { margin:0; font-size:13px; color:#aaa; }

/* ── Divider ── */
.section-divider { height:1px; background:#f0f0f0; margin:0; }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
<div class="container" style="max-width:1080px;">

  <!-- Page heading -->
  <div class="page-head">
    <div>
      <h1>Intelligence Dashboard</h1>
      <p>Predictive demand forecasting and behavioral activity flags for NEXLAB resources.</p>
    </div>
    <a href="dashboard.php" class="btn btn-ghost">Back to Overview</a>
  </div>

  <!-- Action feedback banner -->
  <?php if ($actionMsg): [$msgType, $msgText] = explode(':', $actionMsg, 2); ?>
  <div class="banner banner-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
    <span><?= $msgType === 'success' ? '&#10003;' : '&#9888;' ?></span>
    <span><?= e($msgText) ?></span>
  </div>
  <?php endif; ?>

  <!-- ── TOP STAT STRIP ─────────────────────────────────────── -->
  <div class="intel-section" style="margin-bottom:24px;">
    <div class="stat-strip">
      <div class="stat-strip-cell">
        <div class="stat-strip-num" style="color:#5b21b6;"><?= count($criticalForecast) + count($highForecast) ?></div>
        <div class="stat-strip-lbl">High-Risk Days (7d)</div>
      </div>
      <div class="stat-strip-cell">
        <div class="stat-strip-num" style="color:#c2410c;"><?= count($anomalies) ?></div>
        <div class="stat-strip-lbl">Flagged Users</div>
      </div>
      <div class="stat-strip-cell">
        <div class="stat-strip-num" style="color:#0369a1;">
          <?= count(array_filter($utilSummary, fn($u) => (float)$u['utilization_pct'] > 0)) ?>
        </div>
        <div class="stat-strip-lbl">Active Resources (30d)</div>
      </div>
      <div class="stat-strip-cell">
        <div class="stat-strip-num" style="color:#047857;"><?= $avgUtil ?>%</div>
        <div class="stat-strip-lbl">Avg Utilization (30d)</div>
      </div>
    </div>
  </div>

  <!-- ── DEMAND FORECAST ────────────────────────────────────── -->
  <div class="intel-section">
    <div class="section-header">
      <div class="section-header-left">
        <h2>7-Day Demand Forecast</h2>
        <p>Predicted utilization per resource based on same-day historical patterns from the last 8 weeks. Suggested buffer slots appear for resources above 70% utilization.</p>
      </div>
      <span class="section-tag tag-forecast">Forecast</span>
    </div>

    <?php if (empty($forecastByDate)): ?>
      <div class="no-data-state">
        <div class="nd-icon">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <p>Not enough historical data yet. Forecasts appear after the first week of approved bookings.</p>
      </div>
    <?php else: ?>
      <div class="tab-bar">
        <?php $first = true; foreach ($forecastByDate as $date => $rows):
          $hasAlert = !empty(array_filter($rows, fn($r) => in_array($r['risk_level'], ['critical','high'])));
          $label    = (new DateTime($date))->format('D, M j');
        ?>
        <button class="tab-btn <?= $first ? 'active' : '' ?> <?= $hasAlert ? 'has-alert' : '' ?>"
                onclick="switchTab('<?= $date ?>', this)">
          <?= e($label) ?><?= $hasAlert ? ' &bull;' : '' ?>
        </button>
        <?php $first = false; endforeach; ?>
      </div>

      <?php $first = true; foreach ($forecastByDate as $date => $rows): ?>
      <div class="tab-panel <?= $first ? 'active' : '' ?>" id="panel-<?= $date ?>">
        <?php foreach ($rows as $f):
          $barColor = '#166534'; // default
          if ($f['risk_level'] === 'critical') {
              $barColor = '#b91c1c';
          } elseif ($f['risk_level'] === 'high') {
              $barColor = '#c2410c';
          } elseif ($f['risk_level'] === 'medium') {
              $barColor = '#b45309';
          }
        ?>
        <div class="forecast-row">
          <div class="forecast-name">
            <strong><?= e($f['resource_name']) ?></strong>
            <span>
              <?= ucfirst($f['category']) ?>
              <?= $f['confirmed_count'] ? " &middot; {$f['confirmed_count']} booking(s) already placed" : '' ?>
              <?= $f['data_weeks'] > 1 ? " &middot; {$f['data_weeks']} weeks of historical data" : '' ?>
            </span>
            <?php if (!empty($f['suggested_slots'])): ?>
            <div class="forecast-hint">Suggested buffer slots: <?= implode(', ', array_map('e', $f['suggested_slots'])) ?></div>
            <?php endif; ?>
          </div>
          <div class="bar-wrap">
            <div class="bar-bg">
              <div class="bar-fill" style="width:<?= $f['utilization'] ?>%;background:<?= $barColor ?>;"></div>
            </div>
            <div class="bar-label" style="color:<?= $barColor ?>;"><?= $f['utilization'] ?>%</div>
          </div>
          <span class="risk-tag risk-<?= $f['risk_level'] ?>"><?= strtoupper($f['risk_level']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php $first = false; endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── BEHAVIORAL ACTIVITY FLAGS ──────────────────────────────────── -->
  <div class="intel-section" id="anomalies">
    <div class="section-header">
      <div class="section-header-left">
        <h2>Behavioral Activity & Flags</h2>
        <p>Users exhibiting notable booking behaviour in the last 7&#8211;30 days.</p>
      </div>
      <span class="section-tag tag-anomaly">Activity Flags</span>
    </div>
    
    <form class="filter-bar" method="GET" action="analytics.php#anomalies">
      <div class="filter-group">
        <label>High Volume Threshold (7d):</label>
        <input type="number" name="min_freq" value="<?= $minFreq ?>" min="1" class="filter-input" title="Bookings per week">
      </div>
      <div class="filter-group">
        <label>Batch Session Threshold (10m):</label>
        <input type="number" name="min_rapid" value="<?= $minRapid ?>" min="1" class="filter-input" title="Bookings per 10 minutes">
      </div>
      <button type="submit" class="filter-btn">Apply Filters</button>
    </form>

    <?php if (empty($anomalies)): ?>
      <div class="no-data-state">
        <div class="nd-icon">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p>No unusual activity detected. All users are within standard booking patterns.</p>
      </div>
    <?php else: ?>
      <?php foreach ($anomalies as $uid => $entry):
        $u           = $entry['user'];
        $isSuspended = $u['account_status'] === 'suspended';
        $isFlagged   = (bool)$u['is_flagged'];
      ?>
      <div class="anomaly-row">
        <div class="anomaly-top">
          <div>
            <div>
              <span class="anomaly-name"><?= e($u['full_name']) ?></span>
              <?php if ($isSuspended): ?><span class="status-pill pill-suspended">Suspended</span><?php endif; ?>
              <?php if ($isFlagged && !$isSuspended): ?><span class="status-pill pill-flagged">Flagged</span><?php endif; ?>
            </div>
            <div class="anomaly-meta">
              <?= e($u['email']) ?> &middot; <?= e(ucwords(str_replace('_', ' ', $u['role']))) ?>
              <?= $u['department'] ? ' &middot; ' . e($u['department']) : '' ?>
              <?= (int)$u['flag_count'] > 0 ? " &middot; Flagged {$u['flag_count']}x previously" : '' ?>
            </div>
          </div>
          <span class="risk-tag risk-<?= $entry['overall_severity'] ?>">
            <?= $entry['trigger_count'] ?> Trigger<?= $entry['trigger_count'] > 1 ? 's' : '' ?> &middot; <?= strtoupper($entry['overall_severity']) ?>
          </span>
        </div>

        <div class="trigger-list">
          <?php foreach ($entry['triggers'] as $t): ?>
          <div class="trigger-item <?= $t['severity'] ?>">
            <div class="trigger-type"><?= strtoupper(str_replace('_', ' ', $t['type'])) ?></div>
            <div class="trigger-content">
              <div class="trigger-detail-text"><strong><?= e($t['label']) ?></strong> — <?= e($t['detail']) ?></div>
              
              <?php if (isset($t['current_val']) && isset($t['threshold_val'])): 
                // Calculate meter fill % (capped at 100%)
                $maxDisplay = max($t['current_val'], $t['threshold_val'] * 2);
                if ($t['type'] === 'urgency_abuse') { $maxDisplay = 100; }
                $fillPct = min(100, ($t['current_val'] / max(1, $maxDisplay)) * 100);
                $markerPct = min(100, ($t['threshold_val'] / max(1, $maxDisplay)) * 100);
                
                $fillColor = '#475569';
                if ($t['severity'] === 'critical') {
                    $fillColor = '#b91c1c';
                } elseif ($t['severity'] === 'high') {
                    $fillColor = '#c2410c';
                } elseif ($t['severity'] === 'medium') {
                    $fillColor = '#b45309';
                }
              ?>
              <div class="trigger-meter-wrap">
                <div class="trigger-meter-bg" title="Threshold marker at <?= $t['threshold_val'] ?>">
                  <div class="trigger-meter-fill" style="width:<?= $fillPct ?>%; background:<?= $fillColor ?>;"></div>
                  <div class="trigger-meter-marker" style="left:<?= $markerPct ?>%;"></div>
                </div>
                <div class="trigger-meter-lbl">
                  <?= $t['current_val'] ?> / <?= $t['threshold_val'] ?> threshold
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="action-bar">
          <!-- Flag / Dismiss -->
          <form method="POST" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="user_id"   value="<?= (int)$uid ?>">
            <?php if (!$isFlagged): ?>
              <input type="hidden" name="action" value="flag">
              <button type="submit" class="act-btn act-btn-flag">Flag for Review</button>
            <?php else: ?>
              <input type="hidden" name="action" value="dismiss">
              <button type="submit" class="act-btn act-btn-dismiss">Dismiss Flag</button>
            <?php endif; ?>
          </form>

          <!-- Suspend / Reactivate -->
          <?php if (!$isSuspended): ?>
          <form method="POST" style="display:contents;"
                onsubmit="return confirm('Suspend <?= e(addslashes($u['full_name'])) ?>? This will immediately block their access.');">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="user_id"   value="<?= (int)$uid ?>">
            <input type="hidden" name="action"    value="suspend">
            <button type="submit" class="act-btn act-btn-suspend">Suspend Account</button>
          </form>
          <?php else: ?>
          <form method="POST" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="user_id"   value="<?= (int)$uid ?>">
            <input type="hidden" name="action"    value="reactivate">
            <button type="submit" class="act-btn act-btn-reactivate">Reactivate Account</button>
          </form>
          <?php endif; ?>

          <!-- View profile link -->
          <a href="users.php" class="act-btn act-btn-view">View in Users</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── 30-DAY UTILIZATION SUMMARY ────────────────────────── -->
  <div class="intel-section">
    <div class="section-header">
      <div class="section-header-left">
        <h2>30-Day Utilization Summary</h2>
        <p>Actual resource usage over the past 30 days across all approved and completed bookings, sorted by utilization.</p>
      </div>
      <span class="section-tag tag-summary">Historical</span>
    </div>

    <?php if (empty($utilSummary)): ?>
      <div class="no-data-state"><p>No booking data yet.</p></div>
    <?php else: ?>
      <?php foreach ($utilSummary as $res):
        $pct   = min(100, round((float)$res['utilization_pct'], 1));
        $color = $pct >= 85 ? '#b91c1c' : ($pct >= 60 ? '#c2410c' : ($pct >= 30 ? '#0369a1' : '#9ca3af'));
      ?>
      <div class="util-row">
        <div class="util-row-name">
          <?= e($res['name']) ?>
          <span class="util-row-cat"><?= ucfirst($res['category']) ?></span>
        </div>
        <div class="util-row-bar">
          <div class="bar-bg">
            <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
          </div>
        </div>
        <div class="util-row-pct" style="color:<?= $color ?>;"><?= $pct ?>%</div>
        <div class="util-row-meta">
          <?= (int)$res['total_bookings'] ?> booking<?= $res['total_bookings'] != 1 ? 's' : '' ?> &middot; <?= (int)$res['total_hours'] ?>h total
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
</main>

<script>
function switchTab(dateKey, btn) {
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('panel-' + dateKey).classList.add('active');
    btn.classList.add('active');
}
</script>
</body>
</html>
