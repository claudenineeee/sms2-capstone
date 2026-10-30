<?php
// Using 3 levels up is correct for this folder structure
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../controllers/FacultyController.php';

requireAuth();
header('Content-Type: application/json');

// 1. CSRF Check
if (!isset($_POST['csrf_token']) || !csrfVerify($_POST['csrf_token'])) {
    echo json_encode(['ok' => false, 'error' => 'Security token missing or invalid.']);
    exit;
}

$facultyController = new FacultyController();
$profile = $facultyController->getMyProfile();

if (!$profile) {
    echo json_encode(['ok' => false, 'error' => 'Profile not found.']);
    exit;
}

// 2. Get inputs
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');
$emName    = trim($_POST['emergency_contact_name'] ?? '');
$emPhone   = trim($_POST['emergency_contact_phone'] ?? '');
$emRel     = trim($_POST['emergency_relationship'] ?? '');

if (!$email) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Get the faculty database connection
$db = function_exists('facultyDb') ? facultyDb() : null;
if (!$db) {
    echo json_encode(['ok' => false, 'error' => 'Faculty database connection failed.']);
    exit;
}

try {
    $db->beginTransaction();

    // 3. Update faculty_profiles
    $stmt = $db->prepare("
        UPDATE faculty_profiles 
        SET email = ?, phone = ?, address = ?, 
            emergency_contact_name = ?, emergency_contact_phone = ?, emergency_relationship = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $email, $phone, $address, 
        $emName, $emPhone, $emRel, 
        $profile['id'] // The profile ID
    ]);

    // 4. If email changed, update the CORE user table
    $emailChanged = ($email !== $profile['email']);
    if ($emailChanged) {
        if (function_exists('db')) {
            $coreDb = db(); 
            if ($coreDb && !empty($profile['user_id'])) {
                $stmtUser = $coreDb->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmtUser->execute([$email, $email, $profile['user_id']]);
            }
        }
    }

    $db->commit();
    echo json_encode(['ok' => true, 'email_changed' => $emailChanged]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Save profile failed: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}