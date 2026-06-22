<?php
session_start();
$_SESSION['user_id'] = 4; // Mathurya
$_GET['action'] = 'fetch_admins';
require 'api/support.php';
