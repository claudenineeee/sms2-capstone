<?php
/**
 * SMS 2 - Faculty Module Database Configuration
 */

declare(strict_types=1);

// Load the global config. This automatically loads the main db() function.
require_once __DIR__ . '/../../../config/config.php';

// ==========================================
// 1. CORE CONNECTION (db)
// ==========================================
// NOTE: The db() function is already defined globally in config/config.php.
// We do NOT redefine it here to prevent the "Cannot redeclare db()" fatal error.

// ==========================================
// 2. FACULTY CONNECTION (facultyDb)
// ==========================================
if (!function_exists('facultyDb')) {
    function facultyDb(): ?PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        // Read directly from env vars (HostForge sets these)
        $host = sms2_env('DB_HOST', '127.0.0.1');
        $user = sms2_env('DB_USERNAME', 'root');
        $pass = sms2_env('DB_PASSWORD', '');
        $name = sms2_env('DB_DATABASE', 'sms2_db'); // Use the single HostForge DB
        $port = (int) sms2_env('DB_PORT', '3306');

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            error_log('facultyDb() connection failed: ' . $e->getMessage());
            return null;
        }
    }
}