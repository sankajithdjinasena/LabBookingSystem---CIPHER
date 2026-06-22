<?php
require 'includes/config.php';
require 'includes/database.php';
$pdo = get_db_connection();

$faculties = [
    ['full_name' => 'Prof. S. Bandara', 'email' => 's.bandara@university.edu', 'role' => 'faculty'],
    ['full_name' => 'Dr. K. Silva', 'email' => 'k.silva@university.edu', 'role' => 'faculty'],
    ['full_name' => 'Mr. R. Fernando (Lead)', 'email' => 'r.fernando@university.edu', 'role' => 'project_lead']
];

foreach ($faculties as $f) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$f['email']]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $ins->execute([
            $f['full_name'], 
            $f['email'], 
            password_hash('Password123!', PASSWORD_DEFAULT), 
            $f['role']
        ]);
        echo "Created " . $f['full_name'] . "\n";
    }
}
echo "Done.";
