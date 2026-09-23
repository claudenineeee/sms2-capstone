<?php
/**
 * DEBUG ONLY - DELETE AFTER TESTING
 * Access via: http://localhost/sms2-capstone/debug/ai_debug.php
 */
declare(strict_types=1);

header('Content-Type: application/json');

$result = [];

// 1. Check .env path
$envPath = dirname(__DIR__) . '/.env';
$result['env_path'] = $envPath;
$result['env_exists'] = is_file($envPath);

// 2. Parse .env
$env = [];
if ($result['env_exists']) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}
$result['api_key_length'] = strlen($env['GEMINI_API_KEY'] ?? '');
$result['api_key_first6'] = substr($env['GEMINI_API_KEY'] ?? '', 0, 6);
$result['model'] = $env['GEMINI_MODEL'] ?? 'NOT SET';

// 3. Try DB
$dbConfigPath = dirname(__DIR__) . '/modules/faculty/config/database.php';
$result['db_config_exists'] = is_file($dbConfigPath);
try {
    require_once $dbConfigPath;
    $result['facultyDb_exists'] = function_exists('facultyDb');
    $pdo = facultyDb();
    $result['pdo_connected'] = ($pdo instanceof PDO);
} catch (Throwable $e) {
    $result['db_error'] = $e->getMessage();
    $result['pdo_connected'] = false;
}

// 4. Try faculty query
if (!empty($result['pdo_connected'])) {
    try {
        // Check session
        if (session_status() === PHP_SESSION_NONE) session_start();
        $deptId = (int) ($_SESSION['designated_department'] ?? $_SESSION['department_id'] ?? $_SESSION['user_department_id'] ?? 0);
        $result['session_dept_id'] = $deptId;
        $result['session_keys'] = array_keys($_SESSION ?? []);

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM faculty WHERE department_id = :dept");
        $stmt->execute([':dept' => $deptId]);
        $result['faculty_count_for_dept'] = $stmt->fetchColumn();

        $stmt2 = $pdo->query("SELECT COUNT(*) as total FROM faculty");
        $result['total_faculty'] = $stmt2->fetchColumn();
    } catch (Throwable $e) {
        $result['query_error'] = $e->getMessage();
    }
}

// 5. Test Gemini API
$apiKey = $env['GEMINI_API_KEY'] ?? '';
$model  = $env['GEMINI_MODEL'] ?? 'gemini-3.6-flash';
if ($apiKey) {
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
    $payload  = json_encode(['contents' => [['parts' => [['text' => 'Say OK only.']]]], 'generationConfig' => ['maxOutputTokens' => 5]]);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    $result['gemini_http_code'] = $httpCode;
    $result['gemini_curl_error'] = $curlErr;
    $result['gemini_response_preview'] = substr($resp ?: '', 0, 400);
}

echo json_encode($result, JSON_PRETTY_PRINT);
