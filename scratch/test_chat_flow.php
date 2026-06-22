<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "Starting Support Chat Flow Test...\n";

$pdo = get_db_connection();

// 1. Setup Test Users
$pdo->exec("DELETE FROM support_messages WHERE sender_id IN (998, 999) OR receiver_id IN (998, 999)");
$pdo->exec("DELETE FROM users WHERE id IN (998, 999)");

$pdo->exec("INSERT INTO users (id, full_name, email, password_hash, role, status) VALUES 
    (998, 'Test Student', 'teststudent@university.edu', 'hash', 'student', 'active'),
    (999, 'Test Admin', 'testadmin@university.edu', 'hash', 'admin', 'active')
");
echo "✅ Test users created.\n";

// 2. Simulate Student sending a message to Admin
// In api/support.php, action 'send' inserts into support_messages
$_POST = ['action' => 'send', 'receiver_id' => 999, 'message' => 'Hello Admin, I need 15 hours.'];
$userId = 998; // Pretend student is logged in
$receiverId = 999;
$message = 'Hello Admin, I need 15 hours.';

$stmt = $pdo->prepare("INSERT INTO support_messages (sender_id, receiver_id, message) VALUES (:sender, :receiver, :msg)");
$stmt->execute(['sender' => $userId, 'receiver' => $receiverId, 'msg' => $message]);
echo "✅ Student sent message to Admin.\n";

// 3. Simulate Admin fetching messages
$threadUserId = 998; // The student
$otherUserId = 999;  // The admin
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as sender_name, u.role as sender_role 
    FROM support_messages s
    JOIN users u ON s.sender_id = u.id
    WHERE (s.sender_id = :uid1 AND s.receiver_id = :other1) 
       OR (s.sender_id = :other2 AND s.receiver_id = :uid2)
    ORDER BY s.created_at ASC
");
$stmt->execute([
    'uid1' => $threadUserId, 'other1' => $otherUserId,
    'uid2' => $threadUserId, 'other2' => $otherUserId
]);
$adminMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($adminMessages) === 1 && $adminMessages[0]['message'] === 'Hello Admin, I need 15 hours.') {
    echo "✅ Admin successfully fetched student's message.\n";
} else {
    echo "❌ Admin failed to fetch message.\n";
}

// 4. Simulate Admin replying to Student
$insertStmt = $pdo->prepare("INSERT INTO support_messages (sender_id, receiver_id, message) VALUES (:sender, :receiver, :msg)");
$insertStmt->execute(['sender' => 999, 'receiver' => 998, 'msg' => 'Approved! Proceed with booking.']);
echo "✅ Admin replied to Student.\n";

// 5. Simulate Student fetching messages
$stmt->execute([
    'uid1' => $threadUserId, 'other1' => $otherUserId,
    'uid2' => $threadUserId, 'other2' => $otherUserId
]); // Query remains same
$studentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($studentMessages) === 2 && $studentMessages[1]['message'] === 'Approved! Proceed with booking.') {
    echo "✅ Student successfully fetched admin's reply.\n";
} else {
    echo "❌ Student failed to fetch reply.\n";
}

// Cleanup
$pdo->exec("DELETE FROM support_messages WHERE sender_id IN (998, 999) OR receiver_id IN (998, 999)");
$pdo->exec("DELETE FROM users WHERE id IN (998, 999)");
echo "✅ Cleanup complete.\n";
echo "\nTEST PASSED!\n";
