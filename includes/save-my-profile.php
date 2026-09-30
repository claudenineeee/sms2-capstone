<?php
/**
 * SMS 2 - Save "My Profile" edits (any faculty-module role, own record only)
 * Path: modules/faculty/includes/save-my-profile.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../config/database.php';

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
$facPdo = facultyDb();
$mainPdo = db();
if (!$facPdo || !$mainPdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

// Find the caller's own faculty_profiles row — never trust a submitted profile_id
$find = $facPdo->prepare('SELECT id, email FROM faculty_profiles WHERE user_id = ? LIMIT 1');
$find->execute([$userId]);
$own = $find->fetch();
if (!$own) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No faculty profile linked to your account']);
    exit;
}

$phone = trim((string) ($_POST['phone'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$address = trim((string) ($_POST['address'] ?? ''));
$emergencyName = trim((string) ($_POST['emergency_contact_name'] ?? ''));
$emergencyPhone = trim((string) ($_POST['emergency_contact_phone'] ?? ''));
$emergencyRelationship = trim((string) ($_POST['emergency_relationship'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email']);
    exit;
}

try {
    $emailChanged = strtolower($own['email'] ?? '') !== $email;

    if ($emailChanged) {
        // Email doubles as username in this system — must stay unique across users
        $dupe = $mainPdo->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ? LIMIT 1');
        $dupe->execute([$email, $email, $userId]);
        if ($dupe->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'That email is already in use by another account']);
            exit;
        }
    }

    // Update faculty_profiles (own record only, matched by user_id — not a submitted id)
    $facPdo->prepare(
        'UPDATE faculty_profiles SET
            phone = ?, email = ?, address = ?,
            emergency_contact_name = ?, emergency_contact_phone = ?, emergency_relationship = ?,
            updated_at = NOW()
         WHERE user_id = ?'
    )->execute([$phone, $email, $address ?: null, $emergencyName ?: null, $emergencyPhone ?: null, $emergencyRelationship ?: null, $userId]);

    // Keep sms2_db.users.email/username in sync, since login uses email as username
    if ($emailChanged) {
        $mainPdo->prepare('UPDATE users SET email = ?, username = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$email, $email, $userId]);
    } else {
        $mainPdo->prepare('UPDATE users SET updated_at = NOW() WHERE id = ?')->execute([$userId]);
    }

    logActivity('update', 'Updated own profile', 'faculty');

    echo json_encode(['ok' => true, 'email_changed' => $emailChanged]);
} catch (Throwable $e) {
    error_log('save-my-profile failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save changes']);
}