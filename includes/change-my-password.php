<?php
/**
 * Change My Password — shared endpoint (lives at /includes/change-my-password.php).
 * Updates sms2_db.users.password_hash for the currently logged-in user.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/authentication.php';

/* ---------------------------------------------------------------
 | Helpers
 * --------------------------------------------------------------- */
function cmp_json_fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function cmp_json_ok(array $extra = []): void {
    echo json_encode(array_merge(['ok' => true], $extra));
    exit;
}

/* ---------------------------------------------------------------
 | Auth
 * --------------------------------------------------------------- */
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cmp_json_fail('Invalid request method.', 405);
}

/* ---------------------------------------------------------------
 | CSRF
 * --------------------------------------------------------------- */
$postedToken = (string) ($_POST['csrf_token'] ?? '');

if (function_exists('csrfVerify')) {
    if (!csrfVerify($postedToken)) {
        cmp_json_fail('Your session expired. Please refresh the page and try again.', 419);
    }
} elseif (function_exists('csrfToken')) {
    if (!hash_equals((string) csrfToken(), $postedToken)) {
        cmp_json_fail('Your session expired. Please refresh the page and try again.', 419);
    }
}

/* ---------------------------------------------------------------
 | Input
 * --------------------------------------------------------------- */
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword     = (string) ($_POST['new_password']     ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    cmp_json_fail('Please fill in all three password fields.');
}
if ($newPassword !== $confirmPassword) {
    cmp_json_fail('The new password and confirmation do not match.');
}

/* ---------------------------------------------------------------
 | Connect to sms2_db
 * --------------------------------------------------------------- */
try {
    if (function_exists('smsDb')) {
        $pdo = smsDb();
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        // Already opened by config.php
    } else {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $name = defined('SMS2_DB_NAME') ? SMS2_DB_NAME : (defined('DB_NAME') ? DB_NAME : 'sms2_db');
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (Throwable $e) {
    error_log('[change-my-password] db connect failed: ' . $e->getMessage());
    cmp_json_fail('Could not connect to the authentication database.', 500);
}

/* ---------------------------------------------------------------
 | Password policy
 * --------------------------------------------------------------- */
$minLength = 8;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'min_password_length' LIMIT 1");
    $stmt->execute();
    $val = $stmt->fetchColumn();
    if ($val !== false && (int) $val > 0) {
        $minLength = (int) $val;
    }
} catch (Throwable $e) { /* non-fatal */ }

if (mb_strlen($newPassword) < $minLength) {
    cmp_json_fail("New password must be at least {$minLength} characters.");
}
if ($newPassword === $currentPassword) {
    cmp_json_fail('Your new password must be different from your current password.');
}

/* ---------------------------------------------------------------
 | Identify user
 * --------------------------------------------------------------- */
$userId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
if ($userId <= 0) {
    cmp_json_fail('Your session could not be identified. Please log in again.', 401);
}

/* ---------------------------------------------------------------
 | Verify current password
 * --------------------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT id, password_hash, status FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
} catch (Throwable $e) {
    error_log('[change-my-password] lookup failed: ' . $e->getMessage());
    cmp_json_fail('Could not verify your account. Please try again.', 500);
}

if (!$user) {
    cmp_json_fail('Your account could not be found in the authentication database.', 404);
}
if (($user['status'] ?? 'active') !== 'active') {
    cmp_json_fail('Your account is not active. Please contact the administrator.', 403);
}
if (!password_verify($currentPassword, (string) $user['password_hash'])) {
    try {
        $pdo->prepare("
            INSERT INTO activity_logs (user_id, user_name, role_key, action, module_key, detail, ip_address, user_agent, created_at)
            VALUES (:uid, :uname, :role, 'password_change_failed', 'faculty', 'Incorrect current password supplied', :ip, :ua, NOW())
        ")->execute([
            ':uid'   => $userId,
            ':uname' => $_SESSION['username'] ?? $_SESSION['full_name'] ?? 'Unknown',
            ':role'  => $_SESSION['role_key'] ?? null,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) { /* non-fatal */ }

    cmp_json_fail('Your current password is incorrect.');
}

/* ---------------------------------------------------------------
 | Save new password
 * --------------------------------------------------------------- */
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
if ($newHash === false) {
    cmp_json_fail('Could not securely hash the new password. Please try again.', 500);
}

try {
    $upd = $pdo->prepare("
        UPDATE users
        SET password_hash       = :hash,
            password_changed_at = NOW(),
            must_change_password = 0,
            updated_at          = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $upd->execute([':hash' => $newHash, ':id' => $userId]);

    if ($upd->rowCount() === 0) {
        cmp_json_fail('No changes were saved. Please try again.', 500);
    }
} catch (Throwable $e) {
    error_log('[change-my-password] update failed: ' . $e->getMessage());
    cmp_json_fail('Could not save the new password. Please try again.', 500);
}

/* ---------------------------------------------------------------
 | Audit log
 * --------------------------------------------------------------- */
try {
    $pdo->prepare("
        INSERT INTO activity_logs (user_id, user_name, role_key, action, module_key, detail, ip_address, user_agent, created_at)
        VALUES (:uid, :uname, :role, 'password_change', 'faculty', 'Password changed by user', :ip, :ua, NOW())
    ")->execute([
        ':uid'   => $userId,
        ':uname' => $_SESSION['username'] ?? $_SESSION['full_name'] ?? 'Unknown',
        ':role'  => $_SESSION['role_key'] ?? null,
        ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
} catch (Throwable $e) { /* non-fatal */ }

cmp_json_ok();