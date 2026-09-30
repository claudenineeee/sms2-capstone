<?php
/**
 * SMS 2 - Faculty Module Database Configuration
 * Separate database for faculty-module domain data (profiles, teaching history, etc.)
 * Auth/identity (users, roles, permissions) stays in the main sms2_db — see /config/database.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

if (!defined('FACULTY_DB_HOST')) {
    define('FACULTY_DB_HOST', sms2_env('SMS2_FACULTY_DB_HOST', sms2_env('FACULTY_DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost')));
}
if (!defined('FACULTY_DB_PORT')) {
    define('FACULTY_DB_PORT', sms2_env('SMS2_FACULTY_DB_PORT', sms2_env('FACULTY_DB_PORT', defined('DB_PORT') ? DB_PORT : '3306')));
}
if (!defined('FACULTY_DB_NAME')) {
    // If running in a single cloud DB (like HostForge hf_db_*), default to DB_NAME
    $defaultFacultyDb = (defined('DB_NAME') && DB_NAME !== 'sms2_db') ? DB_NAME : 'faculty_db';
    define('FACULTY_DB_NAME', sms2_env('SMS2_FACULTY_DB_NAME', sms2_env('FACULTY_DB_NAME', $defaultFacultyDb)));
}
if (!defined('FACULTY_DB_USER')) {
    define('FACULTY_DB_USER', sms2_env('SMS2_FACULTY_DB_USER', sms2_env('FACULTY_DB_USER', defined('DB_USER') ? DB_USER : 'root')));
}
if (!defined('FACULTY_DB_PASS')) {
    define('FACULTY_DB_PASS', sms2_env('SMS2_FACULTY_DB_PASS', sms2_env('FACULTY_DB_PASS', defined('DB_PASS') ? DB_PASS : '')));
}
if (!defined('FACULTY_DB_CHARSET')) {
    define('FACULTY_DB_CHARSET', sms2_env('SMS2_FACULTY_DB_CHARSET', defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'));
}

/**
 * Shared PDO connection to the faculty module's own database (singleton).
 *
 * @throws RuntimeException when connection fails
 */
if (!function_exists('getFacultyDatabaseConnection')) {
    function getFacultyDatabaseConnection(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dsn = 'mysql:host=' . FACULTY_DB_HOST .
            ';port=' . FACULTY_DB_PORT .
            ';dbname=' . FACULTY_DB_NAME .
            ';charset=' . FACULTY_DB_CHARSET;

        try {
            $pdo = new PDO($dsn, FACULTY_DB_USER, FACULTY_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
        } catch (PDOException $e) {
            error_log('SMS2 Faculty DB connection failed: ' . $e->getMessage());
            throw new RuntimeException(
                'Faculty database unavailable. Host=' . FACULTY_DB_HOST . ', DB=' . FACULTY_DB_NAME . '. Error: ' . $e->getMessage()
            );
        }

        return $pdo;
    }
}

/**
 * Safe helper — returns null instead of throwing (for optional features).
 */
if (!function_exists('facultyDb')) {
    function facultyDb(): ?PDO
    {
        try {
            return getFacultyDatabaseConnection();
        } catch (Throwable $e) {
            return null;
        }
    }
}