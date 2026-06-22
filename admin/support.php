<?php
/**
 * admin/support.php — admin support desk.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_role(['admin', 'faculty'], 1);
$user = current_user();
$active = 'support';

$pdo = get_db_connection();

// Get list of students who have sent a message to/from this admin
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.role 
    FROM users u 
    JOIN support_messages s ON (u.id = s.sender_id OR u.id = s.receiver_id)
    WHERE u.role = 'student' AND (s.sender_id = :my_id OR s.receiver_id = :my_id2)
    ORDER BY u.full_name
");
$stmt->execute(['my_id' => $user['id'], 'my_id2' => $user['id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedUserId = (int)($_GET['user_id'] ?? ($students[0]['id'] ?? 0));

// Ensure the selected student is in sidebar (may have been selected via dropdown)
$activeStudent = null;
if ($selectedUserId) {
    foreach ($students as $s) {
        if ($s['id'] == $selectedUserId) { $activeStudent = $s; break; }
    }
    if (!$activeStudent) {
        $studentStmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE id = ? AND role = 'student'");
        $studentStmt->execute([$selectedUserId]);
        $newStudent = $studentStmt->fetch(PDO::FETCH_ASSOC);
        if ($newStudent) {
            array_unshift($students, $newStudent);
            $activeStudent = $newStudent;
        } else {
            $selectedUserId = 0; // Invalid student ID, reset
        }
    }
}

// Pre-load messages server-side for instant display
$initialMessages = [];
if ($selectedUserId && $activeStudent) {
    $msgStmt = $pdo->prepare("
        SELECT s.*, u.full_name as sender_name, u.role as sender_role 
        FROM support_messages s
        JOIN users u ON s.sender_id = u.id
        WHERE (s.sender_id = :uid1 AND s.receiver_id = :other1) 
           OR (s.sender_id = :other2 AND s.receiver_id = :uid2)
        ORDER BY s.created_at ASC
    ");
    $msgStmt->execute([
        'uid1' => $user['id'], 'other1' => $selectedUserId,
        'uid2' => $user['id'], 'other2' => $selectedUserId
    ]);
    $initialMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Desk — SURAS</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.support-container { display: flex; gap: 0; height: 600px; max-height: 80vh; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;}
.support-sidebar { width: 300px; background: #f0f2f5; border-right: 1px solid #d1d7db; overflow-y: auto; flex-shrink: 0; }
.sidebar-header { padding: 15px 20px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; position: sticky; top: 0; z-index: 10;}
.sidebar-header h3 { margin: 0; font-size: 16px; color: #111b21; }
.support-sidebar a { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid #f2f2f2; text-decoration: none; color: #111b21; background: #fff; transition: background 0.2s;}
.support-sidebar a:hover { background: #f5f6f6; }
.support-sidebar a.active { background: #e9ecef; }
.avatar { width: 45px; height: 45px; border-radius: 50%; background: #dfe5e7; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; font-weight: bold; margin-right: 15px; flex-shrink: 0; }
.avatar.student { background: #6c5ce7; }
.contact-info { flex: 1; min-width: 0; }
.contact-name { font-size: 15px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.contact-role { font-size: 13px; color: #667781; text-transform: capitalize; }

.support-chat { flex: 1; background: #efeae2; display: flex; flex-direction: column; position: relative; min-width: 0; }
.chat-header { padding: 15px 20px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; display: flex; align-items: center; gap: 15px;}
.chat-header h3 { margin: 0; font-size: 16px; color: #111b21; }

.support-messages { flex: 1; padding: 20px 4%; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
.msg-bubble { max-width: 65%; padding: 8px 12px; border-radius: 8px; font-size: 14.5px; line-height: 1.4; position: relative; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); margin-bottom: 4px; word-wrap: break-word; }
.msg-incoming { background: #fff; align-self: flex-start; border-top-left-radius: 0; }
.msg-outgoing { background: #d9fdd3; align-self: flex-end; border-top-right-radius: 0; }
.msg-sender { font-size: 12px; font-weight: 600; color: #1faeeb; margin-bottom: 2px; }
.msg-time { font-size: 11px; color: #667781; float: right; margin-left: 12px; margin-top: 4px; }

.support-input { padding: 15px 20px; background: #f0f2f5; display: flex; gap: 10px; align-items: center;}
.support-input input { flex: 1; padding: 12px 15px; border: none; border-radius: 8px; background: #fff; font-size: 15px; outline: none; }
.support-input button { background: #00a884; color: #fff; border: none; border-radius: 50%; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; flex-shrink: 0; font-size: 18px; line-height: 1;}
.support-input button:hover { background: #008f6f; }
.no-conv-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #888; gap: 10px; }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container" style="max-width: 1000px;">
    <div class="page-head">
      <div>
        <h1>Support Desk (Live Chat)</h1>
        <p>Respond to student requests and messages across the entire platform.</p>
      </div>
    </div>

    <div class="support-container">
        <div class="support-sidebar">
            <div class="sidebar-header">
                <h3>Conversations</h3>
            </div>
            <!-- Dropdown to start a new conversation -->
            <div style="padding:10px 15px; border-bottom:1px solid #d1d7db; background:#fff;">
                <form method="GET" action="support.php">
                    <select name="user_id" onchange="this.form.submit()" style="width:100%; padding:8px; border-radius:6px; border:1px solid #d1d7db; font-size:13px; outline:none; background:#f9f9f9; cursor:pointer;">
                        <option value="">— Start Chat with Student —</option>
                        <?php 
                        $allStudentsStmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'student' ORDER BY full_name");
                        foreach($allStudentsStmt->fetchAll() as $ast) {
                            $sel = $ast['id'] == $selectedUserId ? 'selected' : '';
                            echo "<option value='{$ast['id']}' $sel>" . e($ast['full_name']) . "</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
            <!-- Sidebar conversation list -->
            <?php foreach($students as $s): ?>
                <a href="?user_id=<?php echo $s['id']; ?>" class="<?php echo $s['id'] == $selectedUserId ? 'active' : ''; ?>">
                    <div class="avatar student"><?php echo strtoupper(substr($s['full_name'], 0, 1)); ?></div>
                    <div class="contact-info">
                        <div class="contact-name"><?php echo e($s['full_name']); ?></div>
                        <div class="contact-role">Student</div>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if(empty($students)): ?>
                <div style="padding:20px;color:#888;text-align:center;font-size:13px;">No conversations yet.<br>Use the dropdown above to start one.</div>
            <?php endif; ?>
        </div>

        <div class="support-chat">
            <?php if($selectedUserId && $activeStudent): ?>
                <div class="chat-header">
                    <div class="avatar student"><?php echo strtoupper(substr($activeStudent['full_name'], 0, 1)); ?></div>
                    <div class="contact-info">
                        <div class="contact-name"><?php echo e($activeStudent['full_name']); ?></div>
                        <div class="contact-role">Student</div>
                    </div>
                </div>

                <div class="support-messages" id="chatWindow">
                    <?php if(empty($initialMessages)): ?>
                        <p style="color:#888;text-align:center;margin-top:20px;background:#fff;padding:8px;border-radius:8px;align-self:center;box-shadow:0 1px 1px rgba(0,0,0,0.1);font-size:13px;">No messages yet. Say hello! 👋</p>
                    <?php else: ?>
                        <?php foreach($initialMessages as $m): 
                            $isAdmin = $m['sender_role'] !== 'student';
                            $timeStr = '';
                            try { $timeStr = date('H:i', strtotime($m['created_at'])); } catch(Exception $e) {}
                        ?>
                        <div class="msg-bubble <?php echo $isAdmin ? 'msg-outgoing' : 'msg-incoming'; ?>">
                            <?php if(!$isAdmin): ?><div class="msg-sender"><?php echo e($m['sender_name']); ?></div><?php endif; ?>
                            <span><?php echo e($m['message']); ?></span>
                            <span class="msg-time"><?php echo $timeStr; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="support-input">
                    <input type="text" id="msgInput" placeholder="Type a reply..." autocomplete="off">
                    <button id="sendBtn" title="Send message">&#10148;</button>
                </div>
            <?php else: ?>
                <div class="no-conv-placeholder">
                    <span style="font-size:48px;">💬</span>
                    <p style="font-size:15px;">Select or start a conversation from the sidebar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
  </div>
</main>

<?php if($selectedUserId && $activeStudent): ?>
<script>
// activeUserId = student we are chatting with
var activeUserId = <?php echo (int)$selectedUserId; ?>;

(function() {
    var chatWindow = document.getElementById('chatWindow');
    var msgInput = document.getElementById('msgInput');
    var sendBtn = document.getElementById('sendBtn');

    if (!chatWindow || !sendBtn || !msgInput) return;

    // Scroll to bottom on load
    chatWindow.scrollTop = chatWindow.scrollHeight;

    function formatTime(dateStr) {
        if (!dateStr) return '';
        try {
            var d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return '';
            return d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        } catch(e) { return ''; }
    }

    function loadMessages() {
        fetch('../api/support.php?action=fetch&user_id=' + activeUserId, {credentials: 'same-origin'})
        .then(function(r) { return r.text(); })
        .then(function(text) {
            try {
                var data = JSON.parse(text);
                if (data.error) {
                    console.warn('Chat API error:', data.error);
                    return;
                }
                if (data.messages) {
                    if (data.messages.length === 0) {
                        chatWindow.innerHTML = '<p style="color:#888;text-align:center;margin-top:20px;background:#fff;padding:8px;border-radius:8px;align-self:center;box-shadow:0 1px 1px rgba(0,0,0,0.1);font-size:13px;">No messages yet. Say hello! 👋</p>';
                    } else {
                        var html = '';
                        data.messages.forEach(function(m) {
                            var isAdminMsg = m.sender_role !== 'student';
                            var timeStr = formatTime(m.created_at);
                            html += '<div class="msg-bubble ' + (isAdminMsg ? 'msg-outgoing' : 'msg-incoming') + '">';
                            if (!isAdminMsg) { html += '<div class="msg-sender">' + m.sender_name + '</div>'; }
                            html += '<span>' + m.message.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>';
                            html += '<span class="msg-time">' + timeStr + '</span>';
                            html += '</div>';
                        });
                        chatWindow.innerHTML = html;
                        chatWindow.scrollTop = chatWindow.scrollHeight;
                    }
                }
            } catch(e) {
                console.error('Chat parse error:', text.substring(0, 200));
            }
        }).catch(function(e) {
            console.error('Chat fetch error:', e);
        });
    }

    function sendMessage() {
        var text = msgInput.value.trim();
        if (!text) return;
        
        sendBtn.disabled = true;
        var formData = new FormData();
        formData.append('action', 'send');
        formData.append('receiver_id', activeUserId);
        formData.append('message', text);

        fetch('../api/support.php', {method: 'POST', body: formData, credentials: 'same-origin'})
        .then(function(r) { return r.text(); })
        .then(function(responseText) {
            sendBtn.disabled = false;
            try {
                var data = JSON.parse(responseText);
                if (data.error) {
                    alert('Could not send: ' + data.error);
                } else {
                    msgInput.value = '';
                    loadMessages();
                }
            } catch(e) {
                console.error('Send response error:', responseText.substring(0, 200));
                alert('Failed to send message. Please refresh and try again.');
            }
        }).catch(function(e) {
            sendBtn.disabled = false;
            console.error('Send fetch error:', e);
            alert('Network error. Please check your connection.');
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    msgInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    // Poll for new messages every 4 seconds
    setInterval(loadMessages, 4000);
})();
</script>
<?php endif; ?>

</body>
</html>
