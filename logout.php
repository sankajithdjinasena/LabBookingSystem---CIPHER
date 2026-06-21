<?php
/**
 * logout.php — ends the session and returns to the sign-in page.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
header('Location: login.php?logged_out=1');
exit;
