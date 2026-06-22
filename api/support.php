<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = current_user();
$userId = (int) $user['id'];
$isAdmin = in_array($user['role'], ['admin', 'faculty', 'project_lead']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$pdo = get_db_connection();

if ($action === 'fetch_admins') {
    $stmt = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'faculty', 'project_lead') ORDER BY role, full_name");
    echo json_encode(['admins' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'fetch') {
    $otherUserId = $isAdmin ? (int)($_GET['user_id'] ?? 0) : (int)($_GET['admin_id'] ?? 0);

    if (!$otherUserId) {
        echo json_encode(['error' => 'Invalid user thread']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name as sender_name, u.role as sender_role 
        FROM support_messages s
        JOIN users u ON s.sender_id = u.id
        WHERE (s.sender_id = :uid1 AND s.receiver_id = :other1) 
           OR (s.sender_id = :other2 AND s.receiver_id = :uid2)
        ORDER BY s.created_at ASC
    ");
    $stmt->execute([
        'uid1' => $userId, 'other1' => $otherUserId,
        'uid2' => $userId, 'other2' => $otherUserId
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['messages' => $messages]);
    exit;
}

if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if (empty($message)) {
        echo json_encode(['error' => 'Message is empty']);
        exit;
    }

    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    if (!$receiverId) {
        echo json_encode(['error' => 'No receiver specified']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO support_messages (sender_id, receiver_id, message) VALUES (:sender, :receiver, :msg)");
    $stmt->execute(['sender' => $userId, 'receiver' => $receiverId, 'msg' => $message]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
exit;
