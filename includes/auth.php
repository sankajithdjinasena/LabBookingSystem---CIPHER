<?php
/**
 * SURAS — Authentication helpers
 */

if (defined('SURAS_AUTH_LOADED')) {
    return;
}
define('SURAS_AUTH_LOADED', true);

/** Escapes a string for safe HTML output. */
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/** Returns the logged-in user array, or null if no one is logged in. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/** Redirects guests away from a protected page. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Gates a page to one or more roles. Guests go to login; logged-in
 * users with the wrong role get bounced to their own dashboard.
 * $depth lets nested pages (e.g. admin/dashboard.php) correct the
 * relative path back to login.php / dashboard.php.
 */
function require_role(array $roles, int $depth = 0): void
{
    $prefix = str_repeat('../', $depth);

    if (!is_logged_in()) {
        header('Location: ' . $prefix . 'login.php');
        exit;
    }
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        header('Location: ' . $prefix . dashboard_for_role($user['role']));
        exit;
    }
}

/** Where each role lands after a successful login. */
function dashboard_for_role(string $role): string
{
    switch ($role) {
        case 'admin':   return 'admin/dashboard.php';
        case 'faculty': return 'faculty/approvals.php';
        default:        return 'dashboard.php'; // student, project_lead
    }
}

/**
 * Attempts to authenticate a user by email + password.
 * Returns the user array on success, or null on failure.
 */
function attempt_login(string $email, string $password): ?array
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role, department, status
         FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }
    if ($user['status'] !== 'active') {
        return null;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    unset($user['password_hash']);
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}
