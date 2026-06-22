<?php
/**
 * support.php — student support desk (WhatsApp Style).
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_login();
$user = current_user();
if (in_array($user['role'], ['admin', 'faculty'], true)) {
    header('Location: admin/support.php');
    exit;
}
$active = 'support';

$pdo = get_db_connection();

// Get list of admins/faculty the student can message
$stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'faculty', 'project_lead') AND id != ? ORDER BY role, full_name");
$stmt->execute([$user['id']]);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedAdminId = (int)($_GET['admin_id'] ?? ($admins[0]['id'] ?? 0));

// Find active admin object
$activeAdmin = null;
if ($selectedAdminId) {
    foreach ($admins as $a) {
        if ($a['id'] == $selectedAdminId) { $activeAdmin = $a; break; }
    }
}

// Pre-load messages server-side
$initialMessages = [];
if ($selectedAdminId && $activeAdmin) {
    $msgStmt = $pdo->prepare("
        SELECT s.*, u.full_name as sender_name, u.role as sender_role 
        FROM support_messages s
        JOIN users u ON s.sender_id = u.id
        WHERE (s.sender_id = :uid1 AND s.receiver_id = :other1) 
           OR (s.sender_id = :other2 AND s.receiver_id = :uid2)
        ORDER BY s.created_at ASC
    ");
    $msgStmt->execute([
        'uid1' => $user['id'], 'other1' => $selectedAdminId,
        'uid2' => $user['id'], 'other2' => $selectedAdminId
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
<link rel="stylesheet" href="assets/css/style.css">
<style>
.support-container { display: flex; gap: 0; height: 600px; max-height: 80vh; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;}
.support-sidebar { width: 280px; background: #f0f2f5; border-right: 1px solid #d1d7db; overflow-y: auto; flex-shrink: 0; }
.sidebar-header { padding: 15px 20px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; position: sticky; top: 0; z-index: 10;}
.sidebar-header h3 { margin: 0; font-size: 16px; color: #111b21; }
.support-sidebar a { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid #f2f2f2; text-decoration: none; color: #111b21; background: #fff; transition: background 0.2s;}
.support-sidebar a:hover { background: #f5f6f6; }
.support-sidebar a.active { background: #e9ecef; }
.avatar { width: 45px; height: 45px; border-radius: 50%; background: #dfe5e7; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; font-weight: bold; margin-right: 12px; flex-shrink: 0; }
.avatar.admin { background: #00a884; }
.avatar.faculty { background: #1faeeb; }
.avatar.project_lead { background: #e17055; }
.contact-info { flex: 1; min-width: 0; }
.contact-name { font-size: 15px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.contact-role { font-size: 13px; color: #667781; text-transform: capitalize; }

.support-chat { flex: 1; background: #efeae2; display: flex; flex-direction: column; position: relative; min-width: 0; }
.chat-header { padding: 15px 20px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; display: flex; align-items: center; gap: 12px;}

.support-messages { flex: 1; padding: 20px 4%; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
.msg-bubble { max-width: 65%; padding: 8px 12px; border-radius: 8px; font-size: 14.5px; line-height: 1.4; position: relative; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); margin-bottom: 4px; word-wrap: break-word; }
.msg-incoming { background: #fff; align-self: flex-start; border-top-left-radius: 0; }
.msg-outgoing { background: #d9fdd3; align-self: flex-end; border-top-right-radius: 0; }
.msg-sender { font-size: 12px; font-weight: 600; color: #1faeeb; margin-bottom: 2px; }
.msg-time { font-size: 11px; color: #667781; float: right; margin-left: 12px; margin-top: 4px; }

.support-input { padding: 15px 20px; background: #f0f2f5; display: flex; gap: 10px; align-items: center;}
.support-input input { flex: 1; padding: 12px 15px; border: none; border-radius: 8px; background: #fff; font-size: 15px; outline: none; }
.support-input button { background: #00a884; color: #fff; border: none; border-radius: 50%; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; flex-shrink: 0; font-size: 18px; line-height: 1; }
.support-input button:hover { background: #008f6f; }
.no-chat-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #888; gap: 10px; }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/app-navbar.php'; ?>

<main class="app-main">
  <div class="container" style="max-width: 1000px;">
    <div class="page-head">
      <div>
        <h1>Support Desk</h1>
        <p>Chat directly with Administrators or Faculty members for booking approvals and inquiries.</p>
      </div>
    </div>

    <div class="support-container">
        <div class="support-sidebar">
            <div class="sidebar-header">
                <h3>Contacts</h3>
            </div>
            <?php foreach($admins as $a): 
                $roleClass = in_array($a['role'], ['admin','faculty','project_lead']) ? $a['role'] : 'admin';
            ?>
                <a href="?admin_id=<?php echo $a['id']; ?>" class="<?php echo $a['id'] == $selectedAdminId ? 'active' : ''; ?>">
                    <div class="avatar <?php echo $roleClass; ?>"><?php echo strtoupper(substr($a['full_name'], 0, 1)); ?></div>
                    <div class="contact-info">
                        <div class="contact-name"><?php echo e($a['full_name']); ?></div>
                        <div class="contact-role"><?php echo e(str_replace('_', ' ', $a['role'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if(empty($admins)): ?>
                <div style="padding:20px;color:#888;text-align:center;font-size:13px;">No contacts available.</div>
            <?php endif; ?>
        </div>

        <div class="support-chat">
            <?php if($selectedAdminId && $activeAdmin): ?>
                <div class="chat-header">
                    <?php $roleClass = in_array($activeAdmin['role'], ['admin','faculty','project_lead']) ? $activeAdmin['role'] : 'admin'; ?>
                    <div class="avatar <?php echo $roleClass; ?>"><?php echo strtoupper(substr($activeAdmin['full_name'], 0, 1)); ?></div>
                    <div class="contact-info">
                        <div class="contact-name"><?php echo e($activeAdmin['full_name']); ?></div>
                        <div class="contact-role"><?php echo e(str_replace('_', ' ', $activeAdmin['role'])); ?></div>
                    </div>
                </div>

                <div class="support-messages" id="chatWindow">
                    <?php if(empty($initialMessages)): ?>
                        <p style="color:#888;text-align:center;margin-top:20px;background:#fff;padding:8px;border-radius:8px;align-self:center;box-shadow:0 1px 1px rgba(0,0,0,0.1);font-size:13px;">No messages yet. Send a message to start the conversation! 👋</p>
                    <?php else: ?>
                        <?php foreach($initialMessages as $m): 
                            $isMe = $m['sender_id'] == $user['id'];
                            $timeStr = '';
                            try { $timeStr = date('H:i', strtotime($m['created_at'])); } catch(Exception $e) {}
                        ?>
                        <div class="msg-bubble <?php echo $isMe ? 'msg-outgoing' : 'msg-incoming'; ?>">
                            <?php if(!$isMe): ?><div class="msg-sender"><?php echo e($m['sender_name']); ?></div><?php endif; ?>
                            <span><?php echo e($m['message']); ?></span>
                            <span class="msg-time"><?php echo $timeStr; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="support-input">
                    <input type="text" id="msgInput" placeholder="Type a message..." autocomplete="off">
                    <button id="sendBtn" title="Send message">&#10148;</button>
                </div>
            <?php else: ?>
                <div class="no-chat-placeholder">
                    <span style="font-size:48px;">💬</span>
                    <p>Select a contact from the left to start a conversation.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
  </div>
</main>

<?php if($selectedAdminId && $activeAdmin): ?>
<script>
var activeAdminId = <?php echo (int)$selectedAdminId; ?>;
var myUserId = <?php echo (int)$user['id']; ?>;

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
        fetch('api/support.php?action=fetch&admin_id=' + activeAdminId, {credentials: 'same-origin'})
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
                        chatWindow.innerHTML = '<p style="color:#888;text-align:center;margin-top:20px;background:#fff;padding:8px;border-radius:8px;align-self:center;box-shadow:0 1px 1px rgba(0,0,0,0.1);font-size:13px;">No messages yet. Send a message to start the conversation! 👋</p>';
                    } else {
                        var html = '';
                        data.messages.forEach(function(m) {
                            var isMe = m.sender_id == myUserId;
                            var timeStr = formatTime(m.created_at);
                            html += '<div class="msg-bubble ' + (isMe ? 'msg-outgoing' : 'msg-incoming') + '">';
                            if (!isMe) { html += '<div class="msg-sender">' + m.sender_name.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>'; }
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
        formData.append('receiver_id', activeAdminId);
        formData.append('message', text);

        fetch('api/support.php', {method: 'POST', body: formData, credentials: 'same-origin'})
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

<script src="assets/js/main.js"></script>
</body>
</html>
