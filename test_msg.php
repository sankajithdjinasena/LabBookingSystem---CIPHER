<?php
require 'includes/config.php';
require 'includes/database.php';
$pdo = get_db_connection();
print_r($pdo->query('SELECT * FROM support_messages')->fetchAll());
