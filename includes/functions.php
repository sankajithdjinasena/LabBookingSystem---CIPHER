<?php
/**
 * SURAS — shared helper functions
 * Booking conflict detection, priority scoring, notifications,
 * and small view-layer formatting helpers used across pages.
 */

// Guard: if this file is ever pulled in twice in the same request
// (e.g. something used `include` instead of `include_once`), skip
// the second load instead of fatal-erroring on redeclared functions.
if (defined('SURAS_FUNCTIONS_LOADED')) {
    return;
}
define('SURAS_FUNCTIONS_LOADED', true);

require_once __DIR__ . '/database.php';

/* =====================================================================
   Formatting helpers
   ===================================================================== */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function category_label(string $category): string
{
    switch ($category) {
        case 'lab':        return 'Computer Lab';
        case 'room':       return 'Meeting Room';
        case 'multimedia': return 'Multimedia Equipment';
        case 'device':     return 'Testing Device';
        default:           return ucfirst($category);
    }
}

function category_icon(string $category): string
{
    switch ($category) {
        case 'lab':        return '🖥️';
        case 'room':       return '🚪';
        case 'multimedia': return '🎥';
        case 'device':     return '🧪';
        default:           return '📦';
    }
}

function status_badge_class(string $status): string
{
    switch ($status) {
        case 'approved':
        case 'completed':
            return 'is-approved';
        case 'pending':
            return 'is-pending';
        case 'waitlist':
            return 'is-waitlist';
        case 'rejected':
        case 'cancelled':
            return 'is-rejected';
        default:
            return 'is-pending';
    }
}

function role_label(string $role): string
{
    switch ($role) {
        case 'project_lead': return 'Project Team Leader';
        case 'admin':         return 'Administrator';
        case 'faculty':       return 'Faculty Member';
        default:              return 'Student';
    }
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

/* =====================================================================
   Resources
   ===================================================================== */

function fetch_resources(string $category = '', string $search = ''): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT * FROM resources WHERE 1=1';
    $params = [];

    if ($category !== '' && $category !== 'all') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }
    if ($search !== '') {
        // Distinct placeholder names — real prepared statements
        // (ATTR_EMULATE_PREPARES = false) don't allow reusing one
        // named parameter more than once in the same query.
        $sql .= ' AND (name LIKE :search1 OR location LIKE :search2 OR description LIKE :search3)';
        $params['search1'] = '%' . $search . '%';
        $params['search2'] = '%' . $search . '%';
        $params['search3'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY category, name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_resource(int $id): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $resource = $stmt->fetch();
    return $resource ?: null;
}

/* =====================================================================
   Priority scoring
   Priority Score = (0.4 × Urgency) + (0.3 × Team Size)
                  + (0.2 × Fairness Score) + (0.1 × Request Time)
   Every component is normalised to a 0–10 scale before weighting,
   so the final score also sits in a readable 0–10 range.
   ===================================================================== */

function calculate_fairness_score(int $userId): float
{
    // Fewer approved bookings in the last 30 days -> higher fairness score.
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS recent_count FROM bookings
         WHERE user_id = :user_id
           AND status IN ('approved','completed')
           AND created_at >= (NOW() - INTERVAL 30 DAY)"
    );
    $stmt->execute(['user_id' => $userId]);
    $count = (int) $stmt->fetch()['recent_count'];

    // 0 recent bookings -> 10 (most fair claim), caps out losing 2 points per booking.
    return max(0, 10 - ($count * 2));
}

function calculate_priority_score(int $urgency, int $teamSize, float $fairness, string $requestedAt): float
{
    $urgencyNorm  = max(0, min(10, $urgency * 2));   // urgency is 1–5 -> scale to 0–10
    $teamSizeNorm = max(0, min(10, $teamSize));       // team size already roughly 0–10
    $fairnessNorm = max(0, min(10, $fairness));

    // Older requests score slightly higher — a small first-come tiebreaker
    // worth 10% of the total, capped at 10 hours of age.
    $ageInHours = (time() - strtotime($requestedAt)) / 3600;
    $requestTimeNorm = min(10, max(0, $ageInHours));

    $score = (0.4 * $urgencyNorm)
           + (0.3 * $teamSizeNorm)
           + (0.2 * $fairnessNorm)
           + (0.1 * $requestTimeNorm);

    return round($score, 2);
}

/* =====================================================================
   Conflict detection
   ===================================================================== */

function has_overlapping_booking(int $resourceId, string $start, string $end, ?int $excludeBookingId = null): bool
{
    $pdo = get_db_connection();
    $sql = "SELECT COUNT(*) AS overlaps FROM bookings
            WHERE resource_id = :resource_id
              AND status IN ('approved','pending')
              AND start_time < :end_time
              AND end_time > :start_time";
    $params = ['resource_id' => $resourceId, 'start_time' => $start, 'end_time' => $end];

    if ($excludeBookingId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetch()['overlaps'] > 0;
}

/** Same as has_overlapping_booking() but returns the conflicting rows themselves. */
function get_overlapping_bookings(int $resourceId, string $start, string $end, ?int $excludeBookingId = null): array
{
    $pdo = get_db_connection();
    $sql = "SELECT * FROM bookings
            WHERE resource_id = :resource_id
              AND status IN ('approved','pending')
              AND start_time < :end_time
              AND end_time > :start_time";
    $params = ['resource_id' => $resourceId, 'start_time' => $start, 'end_time' => $end];

    if ($excludeBookingId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Suggests the next free same-length slot on the same resource, same day, within working hours (08:00–20:00). */
function suggest_alternative_slot(int $resourceId, string $start, string $end): ?array
{
    $pdo = get_db_connection();
    $durationSeconds = strtotime($end) - strtotime($start);
    $dayStart = date('Y-m-d 08:00:00', strtotime($start));
    $dayEnd   = date('Y-m-d 20:00:00', strtotime($start));

    $cursor = strtotime($dayStart);
    $limit  = strtotime($dayEnd) - $durationSeconds;

    while ($cursor <= $limit) {
        $slotStart = date('Y-m-d H:i:s', $cursor);
        $slotEnd   = date('Y-m-d H:i:s', $cursor + $durationSeconds);

        if (!has_overlapping_booking($resourceId, $slotStart, $slotEnd)) {
            return ['start' => $slotStart, 'end' => $slotEnd];
        }
        $cursor += 1800; // step forward 30 minutes
    }
    return null;
}

/* =====================================================================
   Booking creation — the conflict resolution pipeline described in
   the project README: detect conflict, score, then either wait for
   admin/faculty review (no conflict) or join the waitlist (conflict).
   Nothing here ever sets status to 'approved' — only a human action
   in admin/bookings.php or faculty/approvals.php does that.
   ===================================================================== */

function create_booking(int $userId, int $resourceId, string $purpose, string $start, string $end, int $urgency, int $teamSize): array
{
    $pdo = get_db_connection();
    $fairness = calculate_fairness_score($userId);
    $score = calculate_priority_score($urgency, $teamSize, $fairness, date('Y-m-d H:i:s'));

    $pdo->beginTransaction();
    try {
        $conflict = has_overlapping_booking($resourceId, $start, $end);
        $status = $conflict ? 'waitlist' : 'pending';

        $stmt = $pdo->prepare(
            'INSERT INTO bookings (user_id, resource_id, purpose, start_time, end_time, urgency, team_size, priority_score, status)
             VALUES (:user_id, :resource_id, :purpose, :start_time, :end_time, :urgency, :team_size, :score, :status)'
        );
        $stmt->execute([
            'user_id'     => $userId,
            'resource_id' => $resourceId,
            'purpose'     => $purpose,
            'start_time'  => $start,
            'end_time'    => $end,
            'urgency'     => $urgency,
            'team_size'   => $teamSize,
            'score'       => $score,
            'status'      => $status,
        ]);
        $bookingId = (int) $pdo->lastInsertId();

        $alternative = null;
        if ($conflict) {
            $waitStmt = $pdo->prepare(
                'INSERT INTO waitlist (booking_id, resource_id, user_id, start_time, end_time)
                 VALUES (:booking_id, :resource_id, :user_id, :start_time, :end_time)'
            );
            $waitStmt->execute([
                'booking_id'  => $bookingId,
                'resource_id' => $resourceId,
                'user_id'     => $userId,
                'start_time'  => $start,
                'end_time'    => $end,
            ]);

            $alternative = suggest_alternative_slot($resourceId, $start, $end);

            $message = 'Your booking request conflicts with an existing booking and has been placed on the waitlist. '
                . ($alternative
                    ? 'An alternative slot is available: ' . date('M j, g:i A', strtotime($alternative['start'])) . '–' . date('g:i A', strtotime($alternative['end'])) . '.'
                    : 'No alternative slot was found today — you will be notified if the original slot frees up.');
            create_notification($userId, $bookingId, $alternative ? 'alternative' : 'waitlist', $message);
        } else {
            create_notification($userId, $bookingId, 'submission', 'Your booking request has been submitted and is awaiting approval.');
        }

        $pdo->commit();
        return ['booking_id' => $bookingId, 'status' => $status, 'alternative' => $alternative];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function cancel_booking(int $bookingId, int $userId): bool
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $bookingId, 'user_id' => $userId]);
    $booking = $stmt->fetch();
    if (!$booking || in_array($booking['status'], ['cancelled', 'completed'], true)) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
        $update->execute(['id' => $bookingId]);

        create_notification($userId, $bookingId, 'cancellation', 'Your booking has been cancelled.');

        // Promote the earliest-priority waitlisted request for the freed slot, if any.
        // It moves to 'pending', not straight to 'approved' — a human still
        // has to confirm it, same as any other request.
        if ($booking['status'] === 'approved') {
            $promote = $pdo->prepare(
                "SELECT * FROM bookings
                 WHERE resource_id = :resource_id AND status = 'waitlist'
                   AND start_time < :end_time AND end_time > :start_time
                 ORDER BY priority_score DESC, created_at ASC LIMIT 1"
            );
            $promote->execute([
                'resource_id' => $booking['resource_id'],
                'start_time'  => $booking['start_time'],
                'end_time'    => $booking['end_time'],
            ]);
            $next = $promote->fetch();

            if ($next) {
                $approve = $pdo->prepare("UPDATE bookings SET status = 'pending' WHERE id = :id");
                $approve->execute(['id' => $next['id']]);

                $deleteWait = $pdo->prepare("DELETE FROM waitlist WHERE booking_id = :id");
                $deleteWait->execute(['id' => $next['id']]);

                create_notification(
                    (int) $next['user_id'],
                    (int) $next['id'],
                    'waitlist',
                    'A slot you were waitlisted for just opened up — your request now needs final approval.'
                );
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* =====================================================================
   Notifications
   ===================================================================== */

function create_notification(int $userId, ?int $bookingId, string $type, string $message): void
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, booking_id, type, message) VALUES (:user_id, :booking_id, :type, :message)'
    );
    $stmt->execute(['user_id' => $userId, 'booking_id' => $bookingId, 'type' => $type, 'message' => $message]);
}

function get_notifications(int $userId, int $limit = 20): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function unread_notification_count(int $userId): int
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $stmt->execute(['user_id' => $userId]);
    return (int) $stmt->fetch()['c'];
}

function mark_notifications_read(int $userId): void
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
}

/* =====================================================================
   Bookings — listing helpers
   ===================================================================== */

function get_user_bookings(int $userId, string $statusFilter = ''): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT b.*, r.name AS resource_name, r.category AS resource_category, r.location AS resource_location
            FROM bookings b
            JOIN resources r ON r.id = b.resource_id
            WHERE b.user_id = :user_id';
    $params = ['user_id' => $userId];

    if ($statusFilter !== '' && $statusFilter !== 'all') {
        $sql .= ' AND b.status = :status';
        $params['status'] = $statusFilter;
    }
    $sql .= ' ORDER BY b.start_time DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dashboard_stats(int $userId): array
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'pending'  THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status IN ('approved','pending') AND DATE(start_time) = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN status = 'waitlist' THEN 1 ELSE 0 END) AS waitlisted,
            COUNT(*) AS total
         FROM bookings WHERE user_id = :user_id"
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    $resourceCount = $pdo->query("SELECT COUNT(*) AS c FROM resources WHERE status = 'available'")->fetch();

    return [
        'pending'           => (int) ($row['pending'] ?? 0),
        'today'             => (int) ($row['today'] ?? 0),
        'waitlisted'        => (int) ($row['waitlisted'] ?? 0),
        'total'             => (int) ($row['total'] ?? 0),
        'available_resources' => (int) ($resourceCount['c'] ?? 0),
    ];
}
