<?php
require 'includes/config.php';
require 'includes/functions.php';

$msgLow = strtolower("Mobile PA System");
$pdo = get_db_connection();
$allResStmt = $pdo->query("SELECT name FROM resources");
$allRes = $allResStmt->fetchAll(PDO::FETCH_COLUMN);

$specificRoom = null;
foreach ($allRes as $resName) {
    if (strpos($msgLow, strtolower($resName)) !== false) {
        $specificRoom = $resName;
        break;
    }
}

echo "Specific Room: " . $specificRoom . "\n";
