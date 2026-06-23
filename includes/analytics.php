<?php
/**
 * includes/analytics.php
 * NEXLAB Intelligence Engine
 * Predictive Demand Forecasting + Anomaly Detection
 */

// ─────────────────────────────────────────────────────────────
// SECTION 1: PREDICTIVE DEMAND FORECASTING
// ─────────────────────────────────────────────────────────────

/**
 * Get demand forecast for the next N days per resource.
 * Algorithm: For each future day, look at the same day-of-week
 * over the past 4 weeks and calculate average booking hours.
 * Utilization = booked hours / 12 available hours * 100.
 */
function get_demand_forecast(int $daysAhead = 7): array
{
    $pdo = get_db_connection();

    // Get all active resources
    $resources = $pdo->query("SELECT id, name, category, capacity FROM resources WHERE status = 'available' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    $forecast = [];
    $today = new DateTime();

    for ($d = 1; $d <= $daysAhead; $d++) {
        $targetDate = clone $today;
        $targetDate->modify("+{$d} days");
        $targetDow  = (int) $targetDate->format('N'); // 1=Mon, 7=Sun
        $targetDateStr = $targetDate->format('Y-m-d');
        $dayLabel   = $targetDate->format('D, M j');

        foreach ($resources as $res) {
            $resId = (int) $res['id'];

            // Look at same day-of-week over last 8 weeks for this resource
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60.0 AS booked_hours,
                    COUNT(*) as booking_count
                FROM bookings
                WHERE resource_id = :rid
                  AND status IN ('approved','completed')
                  AND DAYOFWEEK(start_time) = :dow
                  AND start_time >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                  AND DATE(start_time) < CURDATE()
            ");
            // DAYOFWEEK: 1=Sun, 2=Mon...7=Sat; convert from ISO (1=Mon,7=Sun)
            $mysqlDow = ($targetDow % 7) + 1; // ISO Mon=1 → MySQL 2
            $stmt->execute(['rid' => $resId, 'dow' => $mysqlDow]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $bookedHours   = round((float)($row['booked_hours'] ?? 0), 2);
            $historicCount = (int)($row['booking_count'] ?? 0);

            // Count weeks we actually have data for to compute weekly average
            $weekStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT DATE(start_time)) as data_days
                FROM bookings
                WHERE resource_id = :rid
                  AND status IN ('approved','completed')
                  AND DAYOFWEEK(start_time) = :dow
                  AND start_time >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
            ");
            $weekStmt->execute(['rid' => $resId, 'dow' => $mysqlDow]);
            $dataWeeks = max(1, (int) $weekStmt->fetchColumn());

            $avgBookedHours = round($bookedHours / $dataWeeks, 2);
            $availableHours = 12.0; // 8 AM to 8 PM
            $utilization    = min(100, round(($avgBookedHours / $availableHours) * 100));

            // Count already-placed bookings for that specific future date
            $futureStmt = $pdo->prepare("
                SELECT COUNT(*) FROM bookings
                WHERE resource_id = :rid
                  AND DATE(start_time) = :dt
                  AND status IN ('pending','approved')
            ");
            $futureStmt->execute(['rid' => $resId, 'dt' => $targetDateStr]);
            $confirmedCount = (int) $futureStmt->fetchColumn();

            // Suggest buffer slots if utilization >= 70%
            $suggestedSlots = [];
            if ($utilization >= 70) {
                $suggestedSlots = suggest_buffer_slots($resId, $targetDateStr);
            }

            $forecast[] = [
                'date'             => $targetDateStr,
                'day_label'        => $dayLabel,
                'day_index'        => $d,
                'resource_id'      => $resId,
                'resource_name'    => $res['name'],
                'category'         => $res['category'],
                'capacity'         => $res['capacity'],
                'avg_booked_hours' => $avgBookedHours,
                'utilization'      => $utilization,
                'confirmed_count'  => $confirmedCount,
                'historical_count' => $historicCount,
                'data_weeks'       => $dataWeeks,
                'risk_level'       => $utilization >= 85 ? 'critical' : ($utilization >= 70 ? 'high' : ($utilization >= 50 ? 'medium' : 'low')),
                'suggested_slots'  => $suggestedSlots,
            ];
        }
    }

    return $forecast;
}

/**
 * Suggest open buffer slots for a resource on a given date.
 */
function suggest_buffer_slots(int $resourceId, string $date): array
{
    $pdo = get_db_connection();

    // Get all booked windows for this date
    $stmt = $pdo->prepare("
        SELECT TIME(start_time) as s, TIME(end_time) as e
        FROM bookings
        WHERE resource_id = :rid AND DATE(start_time) = :dt
          AND status IN ('pending','approved')
        ORDER BY start_time
    ");
    $stmt->execute(['rid' => $resourceId, 'dt' => $date]);
    $booked = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build 2-hour slots from 8am to 8pm
    $slots = [];
    for ($h = 8; $h < 20; $h += 2) {
        $slotStart = sprintf('%02d:00', $h);
        $slotEnd   = sprintf('%02d:00', $h + 2);

        $isFree = true;
        foreach ($booked as $b) {
            // Check overlap
            if ($b['s'] < $slotEnd && $b['e'] > $slotStart) {
                $isFree = false;
                break;
            }
        }
        if ($isFree) {
            $slots[] = $slotStart . '–' . $slotEnd;
            if (count($slots) >= 3) break; // Return max 3 suggestions
        }
    }

    return $slots;
}

/**
 * Get overall utilization summary grouped by resource for a quick overview.
 */
function get_utilization_summary(int $lookbackDays = 30): array
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.name,
            r.category,
            COUNT(b.id) as total_bookings,
            COALESCE(SUM(TIMESTAMPDIFF(HOUR, b.start_time, b.end_time)), 0) as total_hours,
            ROUND(COALESCE(SUM(TIMESTAMPDIFF(HOUR, b.start_time, b.end_time)), 0) / (:days * 12) * 100, 1) as utilization_pct
        FROM resources r
        LEFT JOIN bookings b ON r.id = b.resource_id
            AND b.status IN ('approved','completed')
            AND b.start_time >= DATE_SUB(NOW(), INTERVAL :days2 DAY)
        WHERE r.status = 'available'
        GROUP BY r.id, r.name, r.category
        ORDER BY utilization_pct DESC
    ");
    $stmt->execute(['days' => $lookbackDays, 'days2' => $lookbackDays]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ─────────────────────────────────────────────────────────────
// SECTION 2: ANOMALY DETECTION ENGINE
// ─────────────────────────────────────────────────────────────

/**
 * Run full anomaly detection scan.
 * Returns array of anomalous users with reasons and severity.
 */
function detect_anomalies(): array
{
    $pdo = get_db_connection();
    $anomalies = [];

    // 1. HIGH FREQUENCY: >5 bookings in last 7 days (any status)
    $stmt = $pdo->query("
        SELECT 
            u.id, u.full_name, u.email, u.role, u.department,
            u.is_flagged, u.flag_reason, u.flag_count, u.status as account_status,
            COUNT(b.id) as weekly_count
        FROM users u
        JOIN bookings b ON u.id = b.user_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND u.role != 'admin'
        GROUP BY u.id
        HAVING weekly_count > 15
        ORDER BY weekly_count DESC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $anomalies[$row['id']]['user']    = $row;
        $anomalies[$row['id']]['triggers'][] = [
            'type'     => 'high_frequency',
            'severity' => $row['weekly_count'] > 25 ? 'high' : 'medium',
            'label'    => 'High Volume Booker',
            'detail'   => "{$row['weekly_count']} bookings in the last 7 days (threshold: 15)",
        ];
    }

    // 2. URGENCY ABUSE: >70% of last 20 bookings have urgency = 5
    $stmt = $pdo->query("
        SELECT 
            u.id, u.full_name, u.email, u.role, u.department,
            u.is_flagged, u.flag_reason, u.flag_count, u.status as account_status,
            COUNT(*) as total,
            SUM(CASE WHEN b.urgency = 5 THEN 1 ELSE 0 END) as max_urgency_count,
            ROUND(SUM(CASE WHEN b.urgency = 5 THEN 1 ELSE 0 END) / COUNT(*) * 100) as urgency_pct
        FROM users u
        JOIN bookings b ON u.id = b.user_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND u.role != 'admin'
        GROUP BY u.id
        HAVING total >= 3 AND urgency_pct >= 70
        ORDER BY urgency_pct DESC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!isset($anomalies[$row['id']]['user'])) {
            $anomalies[$row['id']]['user'] = $row;
        }
        $anomalies[$row['id']]['triggers'][] = [
            'type'     => 'urgency_abuse',
            'severity' => $row['urgency_pct'] >= 90 ? 'critical' : 'high',
            'label'    => 'Urgency Score Manipulation',
            'detail'   => "{$row['urgency_pct']}% of bookings submitted with maximum urgency (threshold: 70%)",
        ];
    }

    // 3. RESOURCE HOARDING: Same resource booked >3 times in 7 days
    $stmt = $pdo->query("
        SELECT 
            u.id, u.full_name, u.email, u.role, u.department,
            u.is_flagged, u.flag_reason, u.flag_count, u.status as account_status,
            r.name as resource_name,
            COUNT(b.id) as repeat_count
        FROM users u
        JOIN bookings b ON u.id = b.user_id
        JOIN resources r ON b.resource_id = r.id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND u.role != 'admin'
        GROUP BY u.id, b.resource_id
        HAVING repeat_count > 3
        ORDER BY repeat_count DESC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!isset($anomalies[$row['id']]['user'])) {
            $anomalies[$row['id']]['user'] = $row;
        }
        $anomalies[$row['id']]['triggers'][] = [
            'type'     => 'resource_hoarding',
            'severity' => 'high',
            'label'    => 'Resource Hoarding',
            'detail'   => "Booked \"{$row['resource_name']}\" {$row['repeat_count']} times in 7 days (threshold: 3)",
        ];
    }

    // 4. RAPID FIRE: Multiple bookings created within 10 minutes
    $stmt = $pdo->query("
        SELECT 
            u.id, u.full_name, u.email, u.role, u.department,
            u.is_flagged, u.flag_reason, u.flag_count, u.status as account_status,
            COUNT(*) as burst_count,
            MIN(b.created_at) as burst_start
        FROM users u
        JOIN bookings b ON u.id = b.user_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND u.role != 'admin'
        GROUP BY u.id, DATE(b.created_at), HOUR(b.created_at), FLOOR(MINUTE(b.created_at) / 10)
        HAVING burst_count >= 3
        ORDER BY burst_count DESC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!isset($anomalies[$row['id']]['user'])) {
            $anomalies[$row['id']]['user'] = $row;
        }
        $anomalies[$row['id']]['triggers'][] = [
            'type'     => 'rapid_fire',
            'severity' => 'medium',
            'label'    => 'Rapid-Fire Booking',
            'detail'   => "{$row['burst_count']} bookings submitted within 10 minutes on " . date('M j', strtotime($row['burst_start'])),
        ];
    }

    // Calculate overall severity per user
    foreach ($anomalies as $uid => &$entry) {
        $severities = array_column($entry['triggers'], 'severity');
        if (in_array('critical', $severities)) $entry['overall_severity'] = 'critical';
        elseif (in_array('high', $severities))    $entry['overall_severity'] = 'high';
        else                                       $entry['overall_severity'] = 'medium';
        $entry['trigger_count'] = count($entry['triggers']);
    }
    unset($entry);

    // Sort: critical first
    uasort($anomalies, function($a, $b) {
        $order = ['critical' => 0, 'high' => 1, 'medium' => 2];
        return ($order[$a['overall_severity']] ?? 3) <=> ($order[$b['overall_severity']] ?? 3);
    });

    return $anomalies;
}

/**
 * Auto-flag a user for admin review (not suspend — just mark for review).
 */
function flag_user_for_review(int $userId, string $reason): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_flagged = 1, flag_reason = :reason, flag_date = NOW(),
            flag_count = flag_count + 1
        WHERE id = :id
    ");
    return $stmt->execute(['reason' => $reason, 'id' => $userId]);
}

/**
 * Dismiss a flag (clear it without suspending).
 */
function dismiss_user_flag(int $userId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE users SET is_flagged = 0, flag_reason = NULL, flag_date = NULL WHERE id = :id");
    return $stmt->execute(['id' => $userId]);
}

/**
 * Suspend a user account.
 */
function suspend_user(int $userId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', is_flagged = 1 WHERE id = :id");
    return $stmt->execute(['id' => $userId]);
}

/**
 * Reactivate a suspended user.
 */
function reactivate_user(int $userId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE users SET status = 'active', is_flagged = 0, flag_reason = NULL, flag_date = NULL WHERE id = :id");
    return $stmt->execute(['id' => $userId]);
}

/**
 * Get count of currently flagged users (for dashboard badge).
 */
function get_flagged_user_count(): int
{
    $pdo = get_db_connection();
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_flagged = 1")->fetchColumn();
}

/**
 * Get count of high-risk forecast days (for dashboard badge).
 */
function get_forecast_alert_count(): int
{
    $forecast = get_demand_forecast(7);
    return count(array_filter($forecast, fn($f) => $f['utilization'] >= 70));
}
