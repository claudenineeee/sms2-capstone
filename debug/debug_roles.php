<?php
/**
 * Test what BASE_URL is detected from different script contexts
 */
require_once __DIR__ . '/config/config.php';

echo "BASE_URL from this script: " . BASE_URL . "\n\n";

// Simulate what clearance-portal.php would detect
$_SERVER['SCRIPT_NAME'] = '/Clone/sms2-capstone/modules/faculty/views/hr/faculty-clearance.php';
// Note: BASE_URL is already defined above, so it won't re-detect

echo "Note: BASE_URL = '" . BASE_URL . "'\n";
echo "clearanceApi would be: '" . BASE_URL . "/modules/faculty/controllers/ClearanceController.php'\n\n";

// Test loading the ClearanceController directly (without auth)
// by simulating the role check
require_once ROOT_PATH . '/modules/faculty/controllers/clearance.php';

// Check if the $profile check in ClearanceController would block things
$db = facultyDb();
if ($db) {
    echo "DB connected OK\n";
    
    // Simulate what happens for HR role with no profile
    // Line 108: if (!$profile && !$isClearanceOffice) => blocks non-office
    // For HR user, $isClearanceOffice = true, so they pass
    
    // The real question: what's $role for reviewer accounts?
    // Check what sms_users table shows for reviewer roles
    $stmt = $db->query("SHOW TABLES LIKE 'sms_users'");
    $r = $stmt->fetch();
    if ($r) {
        echo "sms_users table exists\n";
        $stmt = $db->query("SELECT id, username, role, role_key FROM sms_users LIMIT 10");
        $rows = $stmt->fetchAll();
        echo "Sample sms_users:\n";
        foreach ($rows as $row) {
            echo "  id={$row['id']} user={$row['username']} role={$row['role']} role_key={$row['role_key']}\n";
        }
    } else {
        echo "No sms_users table\n";
    }
    
    // Check smsi_users 
    $stmt2 = $db->query("SHOW TABLES LIKE 'smsi_users'");
    $r2 = $stmt2->fetch();
    if ($r2) {
        echo "\nsmsi_users table exists\n";
    }
    
    // Check what tables are available
    $stmt3 = $db->query("SHOW TABLES");
    $tables = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    echo "\nAll tables in faculty DB:\n";
    echo implode(', ', $tables) . "\n";
} else {
    echo "DB connection FAILED\n";
}
