<?php
/**
 * Save or update the print PIN for the currently authenticated user.
 * Uses the existing `user_pins` table inside faculty_db.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$pin     = trim($input['pin'] ?? '');
$current = trim($input['current_pin'] ?? '');
$userId  = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);

if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated.']);
    exit;
}

if (!preg_match('/^\d{4,6}$/', $pin)) {
    echo json_encode(['ok' => false, 'error' => 'PIN must be 4–6 digits.']);
    exit;
}

$db = facultyDb();
if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Faculty database unavailable.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT pin_id, pin_hash FROM user_pins WHERE external_user_id = ? LIMIT 1");
    $stmt->execute([(string) $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['pin_hash'])) {
        if ($current === '' || !password_verify($current, $row['pin_hash'])) {
            echo json_encode(['ok' => false, 'error' => 'Current PIN is incorrect.']);
            exit;
        }
    }

    $newHash = password_hash($pin, PASSWORD_DEFAULT);

    if ($row) {
        $upd = $db->prepare("UPDATE user_pins SET pin_hash = ? WHERE pin_id = ?");
        $upd->execute([$newHash, $row['pin_id']]);
        echo json_encode(['ok' => true, 'message' => 'PIN updated.']);
    } else {
        $ins = $db->prepare("INSERT INTO user_pins (external_user_id, pin_hash) VALUES (?, ?)");
        $ins->execute([(string) $userId, $newHash]);
        echo json_encode(['ok' => true, 'message' => 'PIN created.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save PIN.']);
}