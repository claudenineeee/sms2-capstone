<?php
/**
 * SMS 2 - Faculty Module Database Configuration
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';

// ==========================================
// 1. CORE CONNECTION (db)
// We define it here to ensure it's available, just in case a root database.php doesn't exist.
// ==========================================
if (!function_exists('db')) {
    function db(): ?PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        $host = sms2_env('DB_HOST', '127.0.0.1');
        $user = sms2_env('DB_USERNAME', 'root');
        $pass = sms2_env('DB_PASSWORD', '');
        $name = sms2_env('DB_DATABASE', 'sms2_db');
        $port = (int) sms2_env('DB_PORT', '3306');

        if (!defined('DB_HOST')) define('DB_HOST', $host);
        if (!defined('DB_PORT')) define('DB_PORT', (string) $port);
        if (!defined('DB_NAME')) define('DB_NAME', $name);
        if (!defined('DB_USER')) define('DB_USER', $user);
        if (!defined('DB_PASS')) define('DB_PASS', $pass);

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            error_log('db() connection failed: ' . $e->getMessage());
            return null;
        }
    }
}

// ==========================================
// 2. FACULTY CONNECTION SETUP
// ==========================================
if (!defined('FACULTY_DB_HOST')) {
    define('FACULTY_DB_HOST', sms2_env('SMS2_FACULTY_DB_HOST', sms2_env('FACULTY_DB_HOST', DB_HOST)));
}
if (!defined('FACULTY_DB_PORT')) {
    define('FACULTY_DB_PORT', sms2_env('SMS2_FACULTY_DB_PORT', sms2_env('FACULTY_DB_PORT', DB_PORT)));
}
if (!defined('FACULTY_DB_NAME')) {
    // If running in a single cloud DB (like HostForge), default to DB_NAME
    $defaultFacultyDb = (defined('DB_NAME') && DB_NAME !== 'sms2_db') ? DB_NAME : 'faculty_db';
    define('FACULTY_DB_NAME', sms2_env('SMS2_FACULTY_DB_NAME', sms2_env('FACULTY_DB_NAME', $defaultFacultyDb)));
}
if (!defined('FACULTY_DB_USER')) {
    define('FACULTY_DB_USER', sms2_env('SMS2_FACULTY_DB_USER', sms2_env('FACULTY_DB_USER', DB_USER)));
}
if (!defined('FACULTY_DB_PASS')) {
    define('FACULTY_DB_PASS', sms2_env('SMS2_FACULTY_DB_PASS', sms2_env('FACULTY_DB_PASS', DB_PASS)));
}
if (!defined('FACULTY_DB_CHARSET')) {
    define('FACULTY_DB_CHARSET', sms2_env('SMS2_FACULTY_DB_CHARSET', defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'));
}

// ==========================================
// 3. FACULTY CONNECTION (facultyDb)
// ==========================================
if (!function_exists('getFacultyDatabaseConnection')) {
    function getFacultyDatabaseConnection(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        $dsn = 'mysql:host=' . FACULTY_DB_HOST . ';port=' . FACULTY_DB_PORT . ';dbname=' . FACULTY_DB_NAME . ';charset=' . FACULTY_DB_CHARSET;

        try {
            $pdo = new PDO($dsn, FACULTY_DB_USER, FACULTY_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
        } catch (PDOException $e) {
            error_log('SMS2 Faculty DB connection failed: ' . $e->getMessage());
            throw new RuntimeException('Faculty database unavailable. Host=' . FACULTY_DB_HOST . ', DB=' . FACULTY_DB_NAME . '. Error: ' . $e->getMessage());
        }
        return $pdo;
    }
}

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