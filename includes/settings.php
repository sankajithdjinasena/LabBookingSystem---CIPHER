<?php
/**
 * SURAS — settings helpers
 * Read and write the `settings` table. Values are always stored as
 * strings; callers cast to float/int as needed.
 */

if (defined('SURAS_SETTINGS_LOADED')) {
    return;
}
define('SURAS_SETTINGS_LOADED', true);

require_once __DIR__ . '/database.php';

// Module-level cache — visible to both get_all_settings() and save_setting().
$_suras_settings_cache = null;

/** Returns all settings as [key => value]. Cached for the request. */
function get_all_settings(): array
{
    global $_suras_settings_cache;
    if ($_suras_settings_cache !== null) {
        return $_suras_settings_cache;
    }
    $pdo = get_db_connection();
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $_suras_settings_cache = [];
    foreach ($rows as $row) {
        $_suras_settings_cache[$row['setting_key']] = $row['setting_value'];
    }
    return $_suras_settings_cache;
}

/** Returns a single setting value, or $default if not found. */
function get_setting(string $key, string $default = ''): string
{
    $all = get_all_settings();
    return $all[$key] ?? $default;
}

/** Persists a single setting value and busts the in-request cache. */
function save_setting(string $key, string $value): void
{
    global $_suras_settings_cache;
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'UPDATE settings SET setting_value = :value WHERE setting_key = :key'
    );
    $stmt->execute(['value' => $value, 'key' => $key]);

    // Bust the cache so subsequent get_setting() calls see the new value.
    $_suras_settings_cache = null;
}

/** Returns all settings rows with labels/descriptions for the admin UI. */
function get_settings_for_admin(): array
{
    $pdo = get_db_connection();
    return $pdo->query('SELECT * FROM settings ORDER BY setting_key')->fetchAll();
}