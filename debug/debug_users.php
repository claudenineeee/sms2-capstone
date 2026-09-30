<?php
/**
 * Check main sms2_db users and their role_keys
 * DELETE after use!
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = db();
if (!$db) {
    die("Cannot connect to main DB\n");
}

echo "<pre>\n";

// List users with role_key only
$stmt = $db->query("SELECT id, username, email, role_key, status FROM users ORDER BY id LIMIT 30");
$rows = $stmt->fetchAll();
echo "=== USERS ===\n";
foreach ($rows as $r) {
    echo "id={$r['id']} user={$r['username']} role_key={$r['role_key']} status={$r['status']}\n";
}
echo "\n";

// Check roles table
$roles = $db->query("SELECT * FROM roles LIMIT 30")->fetchAll();
echo "=== ROLES ===\n";
foreach ($roles as $r) {
    echo "  " . print_r($r, true) . "\n";
}
echo "\n";

// Also check faculty_db
require_once ROOT_PATH . '/modules/faculty/controllers/clearance.php';
$fdb = facultyDb();
if ($fdb) {
    // Check all positions
    $stmt2 = $fdb->query("SELECT DISTINCT position FROM faculty_profiles WHERE position IS NOT NULL ORDER BY position");
    $positions = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "=== All Distinct Positions ===\n";
    echo implode(', ', $positions) . "\n\n";
    
    // Check if there's a user_id linkage in faculty_profiles
    $stmt3 = $fdb->query("SELECT id, faculty_id, first_name, last_name, user_id FROM faculty_profiles WHERE user_id IS NOT NULL AND user_id != '' LIMIT 10");
    $rows3 = $stmt3->fetchAll();
    echo "=== Faculty Profiles with user_id ===\n";
    if (empty($rows3)) {
        echo "NONE - no faculty profiles have user_id set\n";
    } else {
        foreach ($rows3 as $r) {
            echo "id={$r['id']} fac_id={$r['faculty_id']} name={$r['first_name']} {$r['last_name']} user_id={$r['user_id']}\n";
        }
    }
}

echo "\n</pre>";
