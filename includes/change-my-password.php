<?php
/**
 * SMS 2 - Change own password (any role, self-service)
 * Path: modules/faculty/includes/change-my-password.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

requireCsrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null);

$userId = getCurrentUserId();
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

// Fetch status along with password_hash
$stmt = $pdo->prepare('SELECT status, password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'User account not found']);
    exit;
}

// Block execution if the account status is pending or not active
$status = strtolower(trim((string) ($row['status'] ?? '')));
if ($status !== 'active') {
    http_response_code(403);
    echo json_encode([
        'ok' => false, 
        'error' => str_contains($status, 'pending') 
            ? 'Your account is currently pending administrator approval.' 
            : 'Account is inactive or disabled.'
    ]);
    exit;
}

if (!password_verify($currentPassword, (string) $row['password_hash'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Current password is incorrect']);
    exit;
}

$minLength = (int) smsSetting('min_password_length', '8');
if (strlen($newPassword) < $minLength) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => "Password must be at least {$minLength} characters"]);
    exit;
}
if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'New password and confirmation do not match']);
    exit;
}

try {
    $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, password_changed_at = NOW() WHERE id = ?')
        ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

    logActivity('password_change', 'Changed own password', 'faculty');

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('change-my-password failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not update password']);
}