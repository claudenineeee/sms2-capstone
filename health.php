<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$dbStatus = 'disconnected';
try {
    $pdo = db();
    if ($pdo instanceof PDO) {
        $dbStatus = 'connected';
    }
} catch (Throwable $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo "OK\nService: web\nDatabase: {$dbStatus}\n";
exit(0);
