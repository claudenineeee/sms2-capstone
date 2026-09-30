<?php
/**
 * SMS 2 - Create a faculty account + profile together (Dean only)
 * Creates a login (sms2_db.users) and a faculty_profiles row (faculty_db),
 * linked via faculty_profiles.user_id = users.id
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../config/database.php'; // facultyDb()

header('Content-Type: application/json');

if (!isAuthenticated() || !in_array(getCurrentUserRoleKey(), ['faculty_admin'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only the Dean can register faculty accounts']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

requireCsrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null);

// ── Map the form's "Position" field to an actual role_key ──────────────
$positionToRole = [
    'Department Head'   => 'department_head',
    'Faculty Secretary' => 'secretary',
    'Faculty Professor' => 'faculty',
];

$firstName   = trim((string) ($_POST['first_name'] ?? ''));
$middleName  = trim((string) ($_POST['middle_name'] ?? ''));
$lastName    = trim((string) ($_POST['last_name'] ?? ''));
$suffix      = trim((string) ($_POST['suffix'] ?? ''));
$birthdate   = trim((string) ($_POST['birthdate'] ?? ''));
$sex         = trim((string) ($_POST['sex'] ?? ''));
$phone       = trim((string) ($_POST['phone'] ?? ''));
$email       = strtolower(trim((string) ($_POST['email'] ?? '')));
$department  = trim((string) ($_POST['designated_dept'] ?? ''));
$position    = trim((string) ($_POST['position'] ?? ''));
$hiredDate   = trim((string) ($_POST['hired_date'] ?? ''));
$contractEnd = trim((string) ($_POST['contractual_end'] ?? ''));
$empStatus   = trim((string) ($_POST['employment_status'] ?? ''));

if ($firstName === '' || $lastName === '' || $email === '' || $department === '' || $position === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email']);
    exit;
}
$roleKey = $positionToRole[$position] ?? null;
if ($roleKey === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown position']);
    exit;
}

$mainPdo = db();
$facPdo  = facultyDb();
if (!$mainPdo || !$facPdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

// ── Generate username + temp password ───────────────────────────────
function smsGenerateUsername(PDO $pdo, string $first, string $last): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', substr($first, 0, 1) . $last));
    $username = $base;
    $suffixNum = 1;
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    while (true) {
        $check->execute([$username]);
        if (!$check->fetch()) {
            return $username;
        }
        $suffixNum++;
        $username = $base . $suffixNum;
    }
}

function smsGenerateTempPassword(): string
{
    return 'Bcp-' . bin2hex(random_bytes(4)); // e.g. Bcp-9f3a2c1d
}

$username = smsGenerateUsername($mainPdo, $firstName, $lastName);
$tempPassword = smsGenerateTempPassword();
$fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $suffix);

$newUserId = null;

try {
    // ── Step 1: create the login account in sms2_db ─────────────────
    $mainPdo->prepare(
        'INSERT INTO users (username, email, password_hash, full_name, role_key, status, must_change_password, password_changed_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW(), NOW())'
    )->execute([
        $username,
        $email,
        password_hash($tempPassword, PASSWORD_DEFAULT),
        $fullName,
        $roleKey,
        'active',
    ]);
    $newUserId = (int) $mainPdo->lastInsertId();

    // ── Step 2: create the faculty profile in faculty_db, linked by user_id ──
    $facultyIdCode = strtoupper($department) . substr(bin2hex(random_bytes(3)), 0, 6);

    $facPdo->prepare(
        'INSERT INTO faculty_profiles
            (faculty_id, first_name, middle_name, last_name, suffix, birthdate, sex, phone,
             designated_department, position, hired_date, contractual_end_date,
             employment_status, profile_status, request_status, created_at, updated_at, user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)'
    )->execute([
        $facultyIdCode,
        $firstName,
        $middleName ?: null,
        $lastName,
        $suffix ?: null,
        $birthdate ?: null,
        $sex ?: null,
        $phone ?: null,
        $department,
        $position,
        $hiredDate ?: null,
        $contractEnd ?: null,
        $empStatus,
        'Active',
        'pending',
        $newUserId,
    ]);

    logActivity('create', "Registered faculty account: {$fullName} ({$roleKey})", 'faculty');

    echo json_encode([
        'ok' => true,
        'username' => $username,
        'temp_password' => $tempPassword, // show once to the dean so they can hand it to the new hire
        'full_name' => $fullName,
        'role' => $roleKey,
    ]);
} catch (Throwable $e) {
    // ── Compensating rollback: undo step 1 if step 2 failed ─────────
    if ($newUserId !== null) {
        $mainPdo->prepare('DELETE FROM users WHERE id = ?')->execute([$newUserId]);
    }
    error_log('Faculty account creation failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not create faculty account. No changes were saved.']);
}