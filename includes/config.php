<?php
/**
 * SURAS — Global configuration
 * Smart University Resource Allocation System
 */

require_once __DIR__ . '/env.php';

// ---- Database credentials -------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'suras');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- SMTP (used by includes/mailer.php + PHPMailer) --------------------
// Set notify_email_enabled = 1 in Admin → Settings to activate emails.


define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM',       'predictrasusl@gmail.com');
define('MAIL_FROM_NAME',  'SURAS Team');
define('MAIL_ENCRYPTION', 'tls');// 'tls' or 'ssl'

// ---- Application settings --------------------------------------------------
define('APP_NAME', 'SURAS');
define('APP_FULL_NAME', 'Smart University Resource Allocation System');
define('BASE_URL', '/'); // change to a sub-path if SURAS is not hosted at the web root

// ---- Session & Security Headers ---------------------------------------------
// Send security headers to protect the application
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");

// ---- Session ------------------------------------------------------------
// Hardened session cookie settings — must run before session_start().
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1))
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $secureCookie,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Error reporting (toggle off in production) -----------------------------
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
