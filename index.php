<?php
/**
 * Redirect root to welcome page, or respond OK to health checks
 */
if (
    isset($_GET['health']) ||
    (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'health') !== false) ||
    (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'kube-probe') !== false)
) {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK";
    exit;
}

header('Location: welcome/index.php');
exit;
