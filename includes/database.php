<?php
/**
 * SURAS — Database connection
 * Provides a single shared PDO instance via get_db_connection().
 */

if (defined('SURAS_DATABASE_LOADED')) {
    return;
}
define('SURAS_DATABASE_LOADED', true);

require_once __DIR__ . '/config.php';

function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            die('Database connection failed: ' . $e->getMessage());
        }
        die('We could not connect to SURAS right now. Please try again shortly.');
    }

    return $pdo;
}