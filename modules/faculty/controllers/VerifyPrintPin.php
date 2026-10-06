<?php
/**
 * Verify the print PIN for the currently authenticated user.
 * Uses the existing `user_pins` table inside faculty_db.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$pin    = trim($input['pin'] ?? '');
$userId = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);

if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated.']);
    exit;
}

$db = facultyDb();
if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Faculty database unavailable.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT pin_hash FROM user_pins WHERE external_user_id = ? LIMIT 1");
    $stmt->execute([(string) $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // No PIN set → allow print, client will prompt user to set one
    if (!$row || empty($row['pin_hash'])) {
        echo json_encode(['ok' => true, 'note' => 'no_pin_set']);
        exit;
    }

    if (password_verify($pin, $row['pin_hash'])) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Incorrect PIN.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Verification failed.']);
}