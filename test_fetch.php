<?php
require 'includes/config.php';
require 'includes/database.php';
$pdo=get_db_connection();
$stmt = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'faculty', 'project_lead') ORDER BY role, full_name");
echo json_encode(['admins' => $stmt->fetchAll()]);
