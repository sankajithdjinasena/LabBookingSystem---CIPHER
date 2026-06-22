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
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

// Ensure the notifications directory exists

/* =====================================================================
   Formatting helpers
   ===================================================================== */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
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
        case 'admin':        return 'Administrator';
        case 'faculty':      return 'Faculty Member';
        default:             return 'Student';
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
    // Load weights from settings (falls back to spec defaults if DB unavailable).
    $wU = (float) get_setting('weight_urgency',      '0.40');
    $wT = (float) get_setting('weight_team_size',    '0.30');
    $wF = (float) get_setting('weight_fairness',     '0.20');
    $wR = (float) get_setting('weight_request_time', '0.10');

    $urgencyNorm  = max(0, min(10, $urgency * 2));   // 1–5 → 0–10
    $teamSizeNorm = max(0, min(10, $teamSize));
    $fairnessNorm = max(0, min(10, $fairness));

    // Earlier requests (relative to "now") score slightly higher — a small
    // first-come tiebreaker worth 10% of the total. We calculate the age in hours.

    $ageInHours      = (time() - strtotime($requestedAt)) / 3600;
    $requestTimeNorm = min(10, max(0, $ageInHours));

    $score = ($wU * $urgencyNorm)
           + ($wT * $teamSizeNorm)
           + ($wF * $fairnessNorm)
           + ($wR * $requestTimeNorm);

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
   the project README: detect conflict, score, allocate, suggest
   alternative, or waitlist.
   ===================================================================== */

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


function create_booking(int $userId, int $resourceId, string $purpose, string $start, string $end, int $urgency, int $teamSize): array
{
    $pdo = get_db_connection();
    $fairness = calculate_fairness_score($userId);
    $score = calculate_priority_score($urgency, $teamSize, $fairness, date('Y-m-d H:i:s'));

    $pdo->beginTransaction();
    try {
        $userStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $role = $userStmt->fetchColumn();
        
        $currentUser = function_exists('current_user') && is_logged_in() ? current_user() : null;
        $activeRole = $currentUser ? $currentUser['role'] : $role;
        $isAdminOverride = in_array($activeRole, ['admin', 'faculty', 'project_lead']);

        $conflicts = get_overlapping_bookings($resourceId, $start, $end);
        
        if ($isAdminOverride && !empty($conflicts)) {
            // Cancel all overlapping bookings to make way for the admin/faculty booking
            foreach ($conflicts as $cb) {
                $cancelStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $cancelStmt->execute([$cb['id']]);
            }
            $conflicts = []; // Clear conflicts so it proceeds normally without splitting
        }

        $resource = get_resource($resourceId);

        // 1. Check if we should trigger the Round Robin splitting logic
        $rrMinDuration  = (int) get_setting('rr_min_duration',  '14400'); // default 4 hrs
        $rrSlotDuration = (int) get_setting('rr_slot_duration', '7200');  // default 2 hrs

        $isLongBooking = (strtotime($end) - strtotime($start)) >= $rrMinDuration;
        $hasLongConflict = false;
        $mainConflict = null;
        foreach ($conflicts as $cb) {
            if ((strtotime($cb['end_time']) - strtotime($cb['start_time'])) >= $rrMinDuration) {
                $hasLongConflict = true;
                $mainConflict = $cb;
                break;
            }
        }

        $shouldSplit = ($isLongBooking || $hasLongConflict)
                       && $mainConflict
                       && in_array($resource['category'], ['lab', 'room'], true);

        if ($shouldSplit) {
            // Fair-Share Round Robin Scheduling Mechanism
            $overlapStart = max(strtotime($start), strtotime($mainConflict['start_time']));
            $overlapEnd = min(strtotime($end), strtotime($mainConflict['end_time']));

            if ($overlapEnd > $overlapStart) {
                $userAId = (int) $mainConflict['user_id'];
                $scoreA = (float) $mainConflict['priority_score'];
                $userBId = $userId;
                $scoreB = $score;

                // Adjust existing booking A's time range
                $originalStart = strtotime($mainConflict['start_time']);
                $originalEnd = strtotime($mainConflict['end_time']);

                if ($originalStart < $overlapStart) {
                    $updateA = $pdo->prepare("UPDATE bookings SET end_time = :end_time WHERE id = :id");
                    $updateA->execute([
                        'end_time' => date('Y-m-d H:i:s', $overlapStart),
                        'id' => $mainConflict['id']
                    ]);
                } else {
                    $updateA = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
                    $updateA->execute(['id' => $mainConflict['id']]);
                }

                if ($overlapEnd < $originalEnd) {
                    $stmtA = $pdo->prepare(
                        'INSERT INTO bookings (user_id, resource_id, purpose, start_time, end_time, urgency, team_size, priority_score, status)
                         VALUES (:user_id, :resource_id, :purpose, :start_time, :end_time, :urgency, :team_size, :score, :status)'
                    );
                    $stmtA->execute([
                        'user_id'     => $userAId,
                        'resource_id' => $resourceId,
                        'purpose'     => $mainConflict['purpose'] . ' (Extended)',
                        'start_time'  => date('Y-m-d H:i:s', $overlapEnd),
                        'end_time'    => date('Y-m-d H:i:s', $originalEnd),
                        'urgency'     => $mainConflict['urgency'],
                        'team_size'   => $mainConflict['team_size'],
                        'score'       => $scoreA,
                        'status'      => 'approved',
                    ]);
                }

                // Divide overlap into configurable slots
                $cursor = $overlapStart;
                $slotDuration = $rrSlotDuration;
                $slotIndex = 0;

                $slotsAllocatedToA = [];
                $slotsAllocatedToB = [];

                while ($cursor < $overlapEnd) {
                    $currentSlotEnd = min($overlapEnd, $cursor + $slotDuration);

                    // Alternate based on priority
                    $assignedUser = ($scoreB >= $scoreA)
                        ? (($slotIndex % 2 === 0) ? 'B' : 'A')
                        : (($slotIndex % 2 === 0) ? 'A' : 'B');

                    $assignedUserId = ($assignedUser === 'A') ? $userAId : $userBId;
                    $assignedPurpose = ($assignedUser === 'A') ? $mainConflict['purpose'] : $purpose;
                    $assignedUrgency = ($assignedUser === 'A') ? $mainConflict['urgency'] : $urgency;
                    $assignedTeamSize = ($assignedUser === 'A') ? $mainConflict['team_size'] : $teamSize;
                    $assignedScore = ($assignedUser === 'A') ? $scoreA : $scoreB;

                    $stmtSlot = $pdo->prepare(
                        'INSERT INTO bookings (user_id, resource_id, purpose, start_time, end_time, urgency, team_size, priority_score, status)
                         VALUES (:user_id, :resource_id, :purpose, :start_time, :end_time, :urgency, :team_size, :score, :status)'
                    );
                    $stmtSlot->execute([
                        'user_id'     => $assignedUserId,
                        'resource_id' => $resourceId,
                        'purpose'     => $assignedPurpose . ' (Fair-Share Slot)',
                        'start_time'  => date('Y-m-d H:i:s', $cursor),
                        'end_time'    => date('Y-m-d H:i:s', $currentSlotEnd),
                        'urgency'     => $assignedUrgency,
                        'team_size'   => $assignedTeamSize,
                        'score'       => $assignedScore,
                        'status'      => 'approved',
                    ]);

                    $timeString = date('g:i A', $cursor) . '–' . date('g:i A', $currentSlotEnd);
                    if ($assignedUser === 'A') {
                        $slotsAllocatedToA[] = $timeString;
                    } else {
                        $slotsAllocatedToB[] = $timeString;
                    }

                    $cursor += $slotDuration;
                    $slotIndex++;
                }

                // Non-overlapping parts of new booking B
                $bStart = strtotime($start);
                $bEnd = strtotime($end);
                if ($bStart < $overlapStart) {
                    $stmtPreB = $pdo->prepare(
                        'INSERT INTO bookings (user_id, resource_id, purpose, start_time, end_time, urgency, team_size, priority_score, status)
                         VALUES (:user_id, :resource_id, :purpose, :start_time, :end_time, :urgency, :team_size, :score, :status)'
                    );
                    $stmtPreB->execute([
                        'user_id'     => $userBId,
                        'resource_id' => $resourceId,
                        'purpose'     => $purpose . ' (Early Slot)',
                        'start_time'  => date('Y-m-d H:i:s', $bStart),
                        'end_time'    => date('Y-m-d H:i:s', $overlapStart),
                        'urgency'     => $urgency,
                        'team_size'   => $teamSize,
                        'score'       => $scoreB,
                        'status'      => 'approved',
                    ]);
                    $slotsAllocatedToB[] = date('g:i A', $bStart) . '–' . date('g:i A', $overlapStart);
                }
                if ($overlapEnd < $bEnd) {
                    $stmtPostB = $pdo->prepare(
                        'INSERT INTO bookings (user_id, resource_id, purpose, start_time, end_time, urgency, team_size, priority_score, status)
                         VALUES (:user_id, :resource_id, :purpose, :start_time, :end_time, :urgency, :team_size, :score, :status)'
                    );
                    $stmtPostB->execute([
                        'user_id'     => $userBId,
                        'resource_id' => $resourceId,
                        'purpose'     => $purpose . ' (Late Slot)',
                        'start_time'  => date('Y-m-d H:i:s', $overlapEnd),
                        'end_time'    => date('Y-m-d H:i:s', $bEnd),
                        'urgency'     => $urgency,
                        'team_size'   => $teamSize,
                        'score'       => $scoreB,
                        'status'      => 'approved',
                    ]);
                    $slotsAllocatedToB[] = date('g:i A', $overlapEnd) . '–' . date('g:i A', $bEnd);
                }

                // Send notifications
                $msgA = "Your booking for " . $resource['name'] . " conflicted with another request. It was split fairly using Round Robin. Approved slots: " . implode(', ', $slotsAllocatedToA) . ".";
                create_notification($userAId, (int) $mainConflict['id'], 'alternative', $msgA);

                $msgB = "Your booking for " . $resource['name'] . " was split fairly using Round Robin. Approved slots: " . implode(', ', $slotsAllocatedToB) . ".";
                create_notification($userBId, null, 'alternative', $msgB);

                $pdo->commit();
                return ['booking_id' => 0, 'status' => 'approved', 'alternative' => null];
            }
        }

        // 2. Normal Priority-Based Bumping
        if (empty($conflicts)) {
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
                'status'      => $isAdminOverride ? 'approved' : 'pending',
            ]);
            $bookingId = (int) $pdo->lastInsertId();
            if ($isAdminOverride) {
                create_notification($userId, $bookingId, 'approval', 'Your resource booking request has been approved by administrator override.');
            } else {
                create_notification($userId, $bookingId, 'submission', 'Your booking request has been submitted and is pending faculty/admin approval.');
            }

            $pdo->commit();
            return ['booking_id' => $bookingId, 'status' => $isAdminOverride ? 'approved' : 'pending', 'alternative' => null];
        } else {
            $hasHigherPriority = true;
            foreach ($conflicts as $cb) {
                // Early-Bird Immunity: If the existing booking was made > 7 days before its start time, it is locked.
                $daysInAdvance = (strtotime($cb['start_time']) - strtotime($cb['created_at'])) / 86400;
                $isImmune = ($daysInAdvance >= 7);

                if ($score <= (float) $cb['priority_score'] || $isImmune) {
                    $hasHigherPriority = false;
                    break;
                }
            }

            if ($hasHigherPriority) {
                // Bump conflicts to waitlist
                foreach ($conflicts as $cb) {
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'waitlist' WHERE id = :id");
                    $updateStmt->execute(['id' => $cb['id']]);

                    $waitStmt = $pdo->prepare(
                        'INSERT INTO waitlist (booking_id, resource_id, user_id, start_time, end_time)
                         VALUES (:booking_id, :resource_id, :user_id, :start_time, :end_time)'
                    );
                    $waitStmt->execute([
                        'booking_id'  => $cb['id'],
                        'resource_id' => $cb['resource_id'],
                        'user_id'     => $cb['user_id'],
                        'start_time'  => $cb['start_time'],
                        'end_time'    => $cb['end_time'],
                    ]);

                    $cbAlt = suggest_alternative_slot((int) $cb['resource_id'], $cb['start_time'], $cb['end_time']);
                    $cbMsg = "Your booking for " . $resource['name'] . " on " . date('M j', strtotime($cb['start_time'])) . " was demoted to the waitlist because a higher priority request was approved. "
                        . ($cbAlt
                            ? "Alternative slot available: " . date('g:i A', strtotime($cbAlt['start'])) . "–" . date('g:i A', strtotime($cbAlt['end'])) . "."
                            : "No alternative slot was found today.");
                    create_notification((int) $cb['user_id'], (int) $cb['id'], $cbAlt ? 'alternative' : 'waitlist', $cbMsg);
                }

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
                    'status'      => 'approved',
                ]);
                $bookingId = (int) $pdo->lastInsertId();
                create_notification($userId, $bookingId, 'approval', 'Your booking request overrode an existing lower-priority booking.');

                $pdo->commit();
                return ['booking_id' => $bookingId, 'status' => 'approved', 'alternative' => null];
            } else {
                // Place new request on waitlist
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
                    'status'      => 'waitlist',
                ]);
                $bookingId = (int) $pdo->lastInsertId();

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
                $message = 'Your booking request conflicted with a higher-priority request and has been waitlisted. '
                    . ($alternative
                        ? 'Alternative slot: ' . date('M j, g:i A', strtotime($alternative['start'])) . '–' . date('g:i A', strtotime($alternative['end'])) . '.'
                        : 'No alternative slot found today.');
                create_notification($userId, $bookingId, $alternative ? 'alternative' : 'waitlist', $message);

                $pdo->commit();
                return ['booking_id' => $bookingId, 'status' => 'waitlist', 'alternative' => $alternative];
            }
        }
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
                $approve = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
                $approve->execute(['id' => $next['id']]);

                $deleteWait = $pdo->prepare("DELETE FROM waitlist WHERE booking_id = :id");
                $deleteWait->execute(['id' => $next['id']]);

                create_notification(
                    (int) $next['user_id'],
                    (int) $next['id'],
                    'approval',
                    'A slot you were waitlisted for just opened up — your booking is now confirmed.'
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

    // Mirror as email when PHPMailer is configured and enabled.
    $subjects = [
        'approval'     => '[SURAS] Booking Approved',
        'rejection'    => '[SURAS] Booking Rejected',
        'cancellation' => '[SURAS] Booking Cancelled',
        'waitlist'     => '[SURAS] Added to Waitlist',
        'alternative'  => '[SURAS] Alternative Slot Available',
        'reminder'     => '[SURAS] Booking Reminder',
        'submission'   => '[SURAS] Booking Submitted',
    ];
    $subject = $subjects[$type] ?? '[SURAS] Notification';
    if (!function_exists('notify_user_by_email')) {
        function notify_user_by_email($userId, $subject, $message) {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if ($user && !empty($user['email'])) {
                $toName = trim($user['full_name']);
                send_email_notification($user['email'], $toName, $subject, $message);
            }
        }
    }

    notify_user_by_email($userId, $subject, $message);
}

function get_notifications(int $userId, int $limit = 20): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :user_id AND created_at <= NOW() ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function unread_notification_count(int $userId): int
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = :user_id AND is_read = 0 AND created_at <= NOW()');
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
            SUM(CASE WHEN status = 'approved' AND DATE(start_time) = CURDATE() THEN 1 ELSE 0 END) AS today,
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
