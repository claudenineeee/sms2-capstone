<?php
/**
 * SMS 2 - Database Configuration (Phase 2)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$hasExplicitSms2Database = sms2_env('SMS2_DB_HOST') !== null;
$databaseUrl = $hasExplicitSms2Database ? null : sms2_env('DATABASE_URL', sms2_env('MYSQL_URL'));
$dbUrlParts = [];
if (!empty($databaseUrl)) {
    $parsed = parse_url($databaseUrl);
    if ($parsed !== false) {
        $dbUrlParts = [
            'host' => $parsed['host'] ?? null,
            'port' => isset($parsed['port']) ? (string) $parsed['port'] : null,
            'user' => isset($parsed['user']) ? urldecode($parsed['user']) : null,
            'pass' => isset($parsed['pass']) ? urldecode($parsed['pass']) : null,
            'name' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : null,
        ];
    }
}

if (!defined('DB_HOST')) {
    define('DB_HOST', $dbUrlParts['host'] ?? sms2_env('SMS2_DB_HOST', sms2_env('DB_HOST', sms2_env('MYSQL_HOST', sms2_env('MYSQLHOST', 'localhost')))));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', $dbUrlParts['port'] ?? sms2_env('SMS2_DB_PORT', sms2_env('DB_PORT', sms2_env('MYSQL_PORT', sms2_env('MYSQLPORT', '3306')))));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $dbUrlParts['name'] ?? sms2_env('SMS2_DB_NAME', sms2_env('DB_DATABASE', sms2_env('DB_NAME', sms2_env('MYSQL_DATABASE', sms2_env('MYSQLDATABASE', 'sms2_db'))))));
}
if (!defined('DB_USER')) {
    define('DB_USER', $dbUrlParts['user'] ?? sms2_env('SMS2_DB_USER', sms2_env('DB_USERNAME', sms2_env('DB_USER', sms2_env('MYSQL_USER', sms2_env('MYSQLUSER', 'root'))))));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $dbUrlParts['pass'] ?? sms2_env('SMS2_DB_PASS', sms2_env('DB_PASSWORD', sms2_env('DB_PASS', sms2_env('MYSQL_PASSWORD', sms2_env('MYSQLPASSWORD', ''))))));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', sms2_env('SMS2_DB_CHARSET', sms2_env('DB_CHARSET', 'utf8mb4')));
}

/**
 * Shared PDO connection (singleton).
 *
 * @throws RuntimeException when connection fails
 */
function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST .
        ';port=' . DB_PORT .
        ';dbname=' . DB_NAME .
        ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
    } catch (PDOException $e) {
        error_log('SMS2 DB connection failed: ' . $e->getMessage());
        throw new RuntimeException(
            'Database unavailable. Host=' . DB_HOST . ', DB=' . DB_NAME . ', User=' . DB_USER . '. Error: ' . $e->getMessage()
        );
    }

    return $pdo;
}

/**
 * Safe helper — returns null instead of throwing (for optional features).
 */
function db(): ?PDO
{
    try {
        return getDatabaseConnection();
    } catch (Throwable $e) {
        return null;
    }
}



