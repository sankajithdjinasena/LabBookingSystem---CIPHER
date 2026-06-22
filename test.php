<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
session_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'student'];

// Mock input
$input = '{"message": "lab 4"}';
// Override file_get_contents inside the script... actually we can't easily override php://input.
// Let's just modify api/assistant.php temporarily to accept $_POST['message'] or fallback to input.
