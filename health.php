<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    db();

    http_response_code(200);
    header('Content-Type: text/plain');
    echo 'OK';
    exit(0);

} catch (Exception $e) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo 'Database connection failed.';
    exit(1);
}
