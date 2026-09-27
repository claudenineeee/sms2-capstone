<?php
declare(strict_types=1);

// Immediately send 200 OK so HostForge / cloud health checks never timeout
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$dbStatus = 'skipped';

// Optional quick DB check with a strict 2-second timeout if requested
if (isset($_GET['check_db']) || isset($_GET['db'])) {
    try {
        @ini_set('default_socket_timeout', '2');
        if (file_exists(__DIR__ . '/config/config.php') && file_exists(__DIR__ . '/config/database.php')) {
            require_once __DIR__ . '/config/config.php';
            require_once __DIR__ . '/config/database.php';
            if (function_exists('db')) {
                $pdo = db();
                $dbStatus = ($pdo instanceof PDO) ? 'connected' : 'disconnected';
            }
        }
    } catch (Throwable $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }
}

echo "OK\nService: web\nStatus: healthy\nDatabase: {$dbStatus}\n";
exit(0);

