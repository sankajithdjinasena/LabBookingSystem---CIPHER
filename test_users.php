<?php
require 'includes/config.php';
require 'includes/database.php';
$pdo=get_db_connection();
foreach($pdo->query('SELECT id, full_name, role FROM users') as $u) {
    echo $u['id'].' - '.$u['full_name'].' ('.$u['role'].')'."\n";
}
