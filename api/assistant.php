<?php
/**
 * api/assistant.php — Mock NLP backend for the SURAS AI Assistant.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['action']) && $input['action'] === 'clear') {
    unset($_SESSION['ai_booking_state']);
    echo json_encode(['status' => 'cleared']);
    exit;
}

$message = trim($input['message'] ?? '');
$msgLow = strtolower($message);
$pdo = get_db_connection();

// --- MULTI-STEP STATE MACHINE ---
if (isset($_SESSION['ai_booking_state'])) {
    $state = $_SESSION['ai_booking_state'];

    // Check for cancellation
    if (preg_match('/\b(cancel|stop|abort|quit|no)\b/', $msgLow) && $state['step'] !== 'awaiting_confirmation') {
        unset($_SESSION['ai_booking_state']);
        echo json_encode(['reply' => 'Booking cancelled. What else can I help you with?']);
        exit;
    }

    if ($state['step'] === 'awaiting_time') {
        // Did they ask for availability?
        if (strpos($msgLow, 'free') !== false || strpos($msgLow, 'schedule') !== false || strpos($msgLow, 'when') !== false || strpos($msgLow, 'availab') !== false) {
            $sql = "SELECT start_time, end_time FROM bookings 
                    WHERE resource_id = :rid AND status IN ('pending', 'approved') 
                    AND start_time < DATE_ADD(NOW(), INTERVAL 48 HOUR) AND end_time > NOW() 
                    ORDER BY start_time ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['rid' => $state['resource_id']]);
            $bks = $stmt->fetchAll();
            
            if (empty($bks)) {
                $reply = "Good news! **" . htmlspecialchars($state['resource_name']) . "** is completely free for the next 48 hours. What time would you like to book it?";
            } else {
                $reply = "**" . htmlspecialchars($state['resource_name']) . "** is booked during these times over the next 48 hours:\n";
                foreach ($bks as $b) {
                    $reply .= "- " . date('M j, g:i A', strtotime($b['start_time'])) . " to " . date('g:i A', strtotime($b['end_time'])) . "\n";
                }
                $reply .= "\nOtherwise, it's free! When would you like your slot?";
            }
            echo json_encode(['reply' => nl2br($reply)]);
            exit;
        }

        // Clean up common numbers, typos, and convert slashes to dashes for EU format
        $cleanMsg = str_replace(
            ['thirteen', 'fourteen', 'fifteen', '/', 'tommorow', 'tommorrow'], 
            ['13', '14', '15', '-', 'tomorrow', 'tomorrow'], 
            $msgLow
        );
        $timeStr = strtotime($cleanMsg);

        if ($timeStr) {
            // If time is exactly midnight but they didn't explicitly say 12am/midnight
            if (date('H:i:s', $timeStr) === '00:00:00' && !preg_match('/\b(12\s*am|midnight|00:00)\b/', $msgLow)) {
                $timeStr += 36000; // +10 hours
            }

            // Assume future if the parsed time has passed today
            if ($timeStr < time() && date('Y-m-d', $timeStr) === date('Y-m-d')) {
                $timeStr = strtotime('+1 day', $timeStr);
            } elseif ($timeStr < time()) {
                echo json_encode(['reply' => "That time is in the past! Please provide a future date and time."]);
                exit;
            }

            // Extract custom duration if provided (e.g., "for 4 hours")
            $durationSecs = 7200; // Default 2 hours
            if (preg_match('/\b(\d+)\s*(hour|hr)s?\b/', $msgLow, $durMatch)) {
                $durationSecs = ((int)$durMatch[1]) * 3600;
            }

            $user = current_user();
            $isAdminOverride = in_array($user['role'], ['admin', 'faculty', 'project_lead']);
            $maxDur = $isAdminOverride ? 48 * 3600 : 12 * 3600;

            if ($durationSecs > $maxDur) {
                unset($_SESSION['ai_booking_state']);
                if ($isAdminOverride) {
                    echo json_encode(['reply' => "Even as an administrator, you cannot book a resource for more than 48 hours continuously."]);
                } else {
                    echo json_encode([
                        'reply' => "I am not authorized to automatically process bookings longer than 12 hours. Please use the Support Desk below to arrange this extended session.",
                        'action' => ['label' => 'Open Support Desk', 'url' => '#adminSelect']
                    ]);
                }
                exit;
            }

            $start = date('Y-m-d H:i:s', $timeStr);
            $end = date('Y-m-d H:i:s', $timeStr + $durationSecs);

            $state['start_time'] = $start;
            $state['end_time'] = $end;
            $state['step'] = 'awaiting_team_size';
            $_SESSION['ai_booking_state'] = $state;

            $hours = $durationSecs / 3600;
            echo json_encode(['reply' => "Got it. I have you down for **" . date('M j \a\t g:i A', $timeStr) . "** for " . $hours . " hours. \nHow many people will be in your team? (e.g. '4' or 'just me')"]);
            exit;
        } else {
            echo json_encode(['reply' => "I couldn't quite understand that time. You can say something like 'Tomorrow at 11 AM' or '13/05 2pm'."]);
            exit;
        }
    }

    if ($state['step'] === 'awaiting_team_size') {
        // Did they actually mean to change the duration?
        if (preg_match('/\b(\d+)\s*(hour|hr)s?\b/', $msgLow, $durMatch)) {
            $durationSecs = ((int)$durMatch[1]) * 3600;
            
            $user = current_user();
            $isAdminOverride = in_array($user['role'], ['admin', 'faculty', 'project_lead']);
            $maxDur = $isAdminOverride ? 48 * 3600 : 12 * 3600;

            if ($durationSecs > $maxDur) {
                unset($_SESSION['ai_booking_state']);
                if ($isAdminOverride) {
                    echo json_encode(['reply' => "Even as an administrator, you cannot book a resource for more than 48 hours continuously."]);
                } else {
                    echo json_encode([
                        'reply' => "I am not authorized to automatically process bookings longer than 12 hours. Please use the Support Desk to contact Faculty.",
                        'action' => ['label' => 'Open Support Desk', 'url' => 'support.php']
                    ]);
                }
                exit;
            }

            $state['end_time'] = date('Y-m-d H:i:s', strtotime($state['start_time']) + $durationSecs);
            $_SESSION['ai_booking_state'] = $state;
            
            $hours = $durationSecs / 3600;
            echo json_encode(['reply' => "I've updated the duration to " . $hours . " hours!\nNow, how many people will be in your team?"]);
            exit;
        }

        if (preg_match('/\b(\d+)\b/', $msgLow, $m)) {
            $state['team_size'] = (int) $m[1];
        } else {
            $state['team_size'] = 1; // Default
        }
        
        $state['step'] = 'awaiting_purpose';
        $_SESSION['ai_booking_state'] = $state;
        
        echo json_encode(['reply' => "Perfect, team size is " . $state['team_size'] . ".\nWhat is the purpose of this booking? (e.g., 'Lab Assignment', 'Meeting')"]);
        exit;
    }

    if ($state['step'] === 'awaiting_purpose') {
        $state['purpose'] = substr(trim($message), 0, 100);
        $state['step'] = 'awaiting_urgency';
        $_SESSION['ai_booking_state'] = $state;
        
        echo json_encode(['reply' => "Got it.\nOn a scale of 1 to 5, what is the **Priority/Urgency** level of this request?"]);
        exit;
    }

    if ($state['step'] === 'awaiting_urgency') {
        if (preg_match('/\b([1-5])\b/', $msgLow, $m)) {
            $state['urgency'] = (int) $m[1];
            $state['step'] = 'awaiting_confirmation';
            $_SESSION['ai_booking_state'] = $state;

            $reply = "Almost done! Here are the details:\n";
            $reply .= "- Resource: **" . htmlspecialchars($state['resource_name']) . "**\n";
            $reply .= "- Time: **" . date('M j \a\t g:i A', strtotime($state['start_time'])) . "**\n";
            $reply .= "- Team Size: **" . $state['team_size'] . "**\n";
            $reply .= "- Purpose: **" . htmlspecialchars($state['purpose']) . "**\n";
            $reply .= "- Priority: **" . $state['urgency'] . "**\n";
            $reply .= "\nShall I submit this booking for approval? (Yes/No)";

            echo json_encode(['reply' => nl2br($reply)]);
            exit;
        } else {
            echo json_encode(['reply' => "Please enter a number from 1 to 5 for the urgency."]);
            exit;
        }
    }

    if ($state['step'] === 'awaiting_confirmation') {
        if (preg_match('/\b(yes|yeah|yep|sure|ok|okay)\b/', $msgLow)) {
            $user = current_user();
            $res = create_booking((int)$user['id'], $state['resource_id'], $state['purpose'], $state['start_time'], $state['end_time'], $state['urgency'], $state['team_size']);
            unset($_SESSION['ai_booking_state']);

            if ($res['status'] === 'pending') {
                echo json_encode(['reply' => "✅ **Success!** Your booking request has been submitted and is pending faculty/admin approval. Check your dashboard!"]);
            } else {
                echo json_encode(['reply' => "⏳ Your booking request was placed on the waitlist due to conflicts. Check your dashboard!"]);
            }
            exit;
        } else {
            unset($_SESSION['ai_booking_state']);
            echo json_encode(['reply' => 'Booking cancelled. What else can I help you with?']);
            exit;
        }
    }
}
// --- END STATE MACHINE ---

// Remove legacy yes check
unset($_SESSION['ai_last_action']);

// 3. Simple conversational fallbacks
if (strpos($msgLow, 'hello') !== false || strpos($msgLow, 'hi') !== false || strpos($msgLow, 'hey') !== false) {
    echo json_encode([
        'reply' => 'Hi there! I am your SURAS AI Assistant. I can help you book resources or answer questions about lab rules, waitlists, and priorities!'
    ]);
    exit;
}

// 4. FAQ Check
$faqFile = __DIR__ . '/faq.json';
if (file_exists($faqFile)) {
    $faqs = json_decode(file_get_contents($faqFile), true);
    foreach ($faqs as $faq) {
        foreach ($faq['keywords'] as $kw) {
            if (strpos($msgLow, $kw) !== false) {
                echo json_encode(['reply' => $faq['answer']]);
                exit;
            }
        }
    }
}

// 5. Booking Extraction Logic
$category = null;
$capacity = 1;
$specificRoom = null;

$allResStmt = $pdo->query("SELECT name FROM resources");
$allRes = $allResStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($allRes as $resName) {
    if (strpos($msgLow, strtolower($resName)) !== false) {
        $specificRoom = $resName;
        break;
    }
}

if (strpos($msgLow, 'lab') !== false || strpos($msgLow, 'computer') !== false) {
    $category = 'lab';
} elseif (strpos($msgLow, 'room') !== false || strpos($msgLow, 'seminar') !== false || strpos($msgLow, 'meeting') !== false) {
    $category = 'room';
} elseif (strpos($msgLow, 'projector') !== false || strpos($msgLow, 'camera') !== false || strpos($msgLow, 'multimedia') !== false) {
    $category = 'multimedia';
} elseif (strpos($msgLow, 'device') !== false || strpos($msgLow, 'vr') !== false || strpos($msgLow, 'oscilloscope') !== false) {
    $category = 'device';
}

if (preg_match('/\b(\d+)\s*(people|students|persons|pax)\b/', $msgLow, $matches)) {
    $capacity = (int)$matches[1];
} elseif (!$specificRoom && preg_match('/\b(204|101|301|404|\d{3})\b/', $msgLow, $matches)) {
    $specificRoom = $matches[1];
} elseif (preg_match('/\b(\d{1,2})\b/', $msgLow, $matches)) {
    $capacity = (int)$matches[1];
}

if (!$category && strpos($msgLow, 'book') === false && !$specificRoom && strpos($msgLow, 'available') === false && strpos($msgLow, 'resource') === false) {
    echo json_encode([
        'reply' => "I didn't quite catch what you're looking for. Could you specify if you need a **lab**, **room**, **projector**, or **device**? You can also ask me general questions about SURAS rules."
    ]);
    exit;
}

// 6. Query the database
$sql = "SELECT r.* FROM resources r
        LEFT JOIN bookings b ON b.resource_id = r.id AND b.status IN ('pending', 'approved') 
            AND NOW() BETWEEN b.start_time AND b.end_time
        WHERE r.status = 'available' AND b.id IS NULL";
$params = [];

if ($category) {
    $sql .= " AND r.category = :category";
    $params['category'] = $category;
}

if (isset($specificRoom)) {
    $sql .= " AND r.name LIKE :room_name";
    $params['room_name'] = '%' . $specificRoom . '%';
}

if ($capacity > 1 && !isset($specificRoom)) {
    if (in_array($category, ['lab', 'room'])) {
        $sql .= " AND r.capacity >= :capacity";
        $params['capacity'] = $capacity;
    }
}

$sql .= " ORDER BY r.capacity ASC LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

if (!empty($resources)) {
    if (count($resources) === 1) {
        $r = $resources[0];
        $reply = "I found **" . htmlspecialchars($r['name']) . "** which is free right now! ";
        if ($r['capacity']) $reply .= "It has a capacity of " . $r['capacity'] . " people. ";
        $reply .= "When would you like to book it? (Please provide BOTH Date and Time, e.g. 'Tomorrow at 11 AM' or '13/05/2026 2pm')";
        
        $_SESSION['ai_booking_state'] = [
            'step' => 'awaiting_time',
            'resource_id' => $r['id'],
            'resource_name' => $r['name'],
            'team_size' => $capacity
        ];
        
        echo json_encode(['reply' => $reply]);
    } else {
        $reply = "<p>Here are the available resources right now:</p>";
        $reply .= "<table class='data-table' style='width:100%; margin-top:10px; font-size: 0.9em;'>";
        $reply .= "<thead><tr><th style='text-align:left'>Resource</th><th style='text-align:left'>Category</th><th>Cap</th></tr></thead><tbody>";
        foreach ($resources as $r) {
            $cap = $r['capacity'] ? $r['capacity'] : "-";
            $cat = ucfirst($r['category']);
            $reply .= "<tr><td>" . htmlspecialchars($r['name']) . "</td><td>" . $cat . "</td><td style='text-align:center'>" . $cap . "</td></tr>";
        }
        $reply .= "</tbody></table>";
        $reply .= "<p style='margin-top:10px;'>Tell me which one you'd like to book!</p>";
        echo json_encode(['reply' => $reply]);
    }
} else {
    echo json_encode([
        'reply' => "I couldn't find any available resources matching your criteria right now. Try adjusting your team size or category."
    ]);
}
