<?php
/**
 * SMS 2 - Faculty Module Database Configuration
 * Separate database for faculty-module domain data (profiles, teaching history, etc.)
 * Auth/identity (users, roles, permissions) stays in the main sms2_db — see /config/database.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';

if (!defined('FACULTY_DB_HOST')) {
    define('FACULTY_DB_HOST', sms2_env('SMS2_FACULTY_DB_HOST', 'localhost'));
}
if (!defined('FACULTY_DB_NAME')) {
    define('FACULTY_DB_NAME', sms2_env('SMS2_FACULTY_DB_NAME', 'faculty_db'));
}
if (!defined('FACULTY_DB_USER')) {
    define('FACULTY_DB_USER', sms2_env('SMS2_FACULTY_DB_USER', 'root'));
}
if (!defined('FACULTY_DB_PASS')) {
    define('FACULTY_DB_PASS', sms2_env('SMS2_FACULTY_DB_PASS', ''));
}
if (!defined('FACULTY_DB_CHARSET')) {
    define('FACULTY_DB_CHARSET', sms2_env('SMS2_FACULTY_DB_CHARSET', 'utf8mb4'));
}

/**
 * Shared PDO connection to the faculty module's own database (singleton).
 *
 * @throws RuntimeException when connection fails
 */
function getFacultyDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . FACULTY_DB_HOST . ';dbname=' . FACULTY_DB_NAME . ';charset=' . FACULTY_DB_CHARSET;

    try {
        $pdo = new PDO($dsn, FACULTY_DB_USER, FACULTY_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('SMS2 Faculty DB connection failed: ' . $e->getMessage());
        throw new RuntimeException(
            'Faculty database unavailable. Check FACULTY_DB_NAME and that MySQL is running.'
        );
    }

    return $pdo;
}

/**
 * Safe helper — returns null instead of throwing (for optional features).
 */
function facultyDb(): ?PDO
{
    try {
        return getFacultyDatabaseConnection();
    } catch (Throwable $e) {
        return null;
    }
}