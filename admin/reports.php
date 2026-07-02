<?php
/**
 * admin/reports.php — analytics dashboard.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['admin'], 1);
$user = current_user();
$active = 'admin_reports';

$daily = bookings_per_day(14);
$topResources = most_used_resources(6);
$peakHours = peak_booking_hours();
$deptUsage = department_usage();
$cancelStats = cancellation_stats();
$utilization = resource_utilization_rate();

// Prepare a continuous 14-day series (fill gaps with 0) for the chart.
$dailyMap = [];
foreach ($daily as $row) {
    $dailyMap[$row['day']] = (int) $row['total'];
}
$dailyLabels = [];
$dailyValues = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('M j', strtotime($day));
    $dailyValues[] = $dailyMap[$day] ?? 0;
}

// 24-hour peak map.
$hourMap = array_fill(0, 24, 0);
foreach ($peakHours as $row) {
    $hourMap[(int) $row['hour']] = (int) $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — NEXLAB Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Reports & analytics</h1>
        <p>Utilisation, demand patterns, and where cancellations are coming from.</p>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-label">Resource utilization</span>
        <span class="stat-value"><?php echo $utilization; ?>%</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Cancellation rate</span>
        <span class="stat-value"><?php echo $cancelStats['cancellation_rate']; ?>%</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Total cancelled</span>
        <span class="stat-value"><?php echo $cancelStats['cancelled']; ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Total rejected</span>
        <span class="stat-value"><?php echo $cancelStats['rejected']; ?></span>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Bookings — last 14 days</h2></div>
      <canvas id="dailyChart" height="90"></canvas>
    </div>

    <div class="booking-layout">
      <div class="panel">
        <div class="panel-head"><h2>Most used resources</h2></div>
        <?php if (empty($topResources)): ?>
          <div class="empty-state"><span class="empty-icon">📊</span><p>No approved bookings yet.</p></div>
        <?php else: ?>
          <canvas id="resourceChart" height="200"></canvas>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>Peak booking hours</h2></div>
        <canvas id="hourChart" height="200"></canvas>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Department-wise usage</h2></div>
      <?php if (empty($deptUsage)): ?>
        <div class="empty-state"><span class="empty-icon">🏛️</span><p>No approved bookings yet.</p></div>
      <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Department</th><th>Approved bookings</th></tr></thead>
          <tbody>
            <?php foreach ($deptUsage as $d): ?>
              <tr><td><?php echo e($d['department']); ?></td><td><?php echo (int) $d['total']; ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</main>

<script src="../assets/js/main.js"></script>
<script>
  const inkSoft = '#3C4A68';
  const amber = '#C8862B';
  const sage = '#5C7A6B';
  const lineColor = '#DED7C5';

  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.color = inkSoft;

  new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode($dailyLabels); ?>,
      datasets: [{
        label: 'Bookings',
        data: <?php echo json_encode($dailyValues); ?>,
        borderColor: amber,
        backgroundColor: 'rgba(200,134,43,0.12)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: lineColor } },
        x: { grid: { display: false } }
      }
    }
  });

  <?php if (!empty($topResources)): ?>
  new Chart(document.getElementById('resourceChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column($topResources, 'name')); ?>,
      datasets: [{
        label: 'Approved bookings',
        data: <?php echo json_encode(array_map('intval', array_column($topResources, 'total'))); ?>,
        backgroundColor: amber,
        borderRadius: 4,
      }]
    },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: lineColor } },
        y: { grid: { display: false } }
      }
    }
  });
  <?php endif; ?>

  new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
      labels: Array.from({length: 24}, (_, i) => i + ':00'),
      datasets: [{
        label: 'Bookings',
        data: <?php echo json_encode(array_values($hourMap)); ?>,
        backgroundColor: sage,
        borderRadius: 3,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: lineColor } },
        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } }
      }
    }
  });
</script>
</body>
</html>
