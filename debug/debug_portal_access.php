<?php
/**
 * Simulate what ClearanceController does for each role
 * DELETE after use!
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/modules/faculty/controllers/clearance.php';

$db = facultyDb();
$term = $db ? facultyClearanceTerm($db) : null;

echo "<pre>\n";

// Test what roles are accepted as clearance office
$testRoles = [
    'hr',
    'hr_clearance',
    'finance',
    'finance_office',
    'registrar',
    'registrar_clearance',
    'library',
    'library_clearance',
    'property',
    'property_custodian_office',
    'department_head',
    'dept_head',
    'faculty_admin',
    'dean',
    'admin',
    'superadmin'
];

$officeRoles = [
    'department_head',
    'dept_head',
    'hr',
    'hr_clearance',
    'faculty_admin',
    'dean',
    'registrar_clearance',
    'registrar',
    'finance_office',
    'finance',
    'library_clearance',
    'library',
    'property_custodian_office',
    'property',
    'admin',
    'super_admin'
];

echo "=== Role Check Analysis ===\n";
foreach ($testRoles as $role) {
    $isClearanceOffice = in_array($role, $officeRoles, true);
    echo "$role: " . ($isClearanceOffice ? "CLEARANCE OFFICE - can see records" : "BLOCKED") . "\n";
}
echo "\n";

// Now check what the finance portal gate looks like
echo "=== Finance Portal Role Gate ===\n";
$financeGate = ['finance_office', 'finance', 'faculty_admin'];
$hrGate = ['hr_clearance', 'hr', 'faculty_admin'];
$registrarGate = ['registrar_clearance', 'registrar', 'faculty_admin'];
$libraryGate = ['library_clearance', 'library', 'faculty_admin'];
$propertyGate = ['property_custodian_office', 'property', 'faculty_admin'];
$deptHeadGate = ['department_head', 'dept_head', 'hr', 'faculty_admin'];

// Look up users from main DB
$mainDb = db();
if ($mainDb) {
    $stmt = $mainDb->query("SELECT id, username, role_key FROM users WHERE status = 'active' ORDER BY id");
    $users = $stmt->fetchAll();

    echo "\n=== Active Users and Portal Access ===\n";
    echo sprintf("%-5s %-35s %-30s %s\n", "ID", "Username", "Role Key", "Can Access Portals");
    echo str_repeat("-", 100) . "\n";

    foreach ($users as $u) {
        $rk = $u['role_key'];
        $portals = [];
        if (in_array($rk, $deptHeadGate))
            $portals[] = "DeptHead";
        if (in_array($rk, $hrGate))
            $portals[] = "HR";
        if (in_array($rk, $financeGate))
            $portals[] = "Finance";
        if (in_array($rk, $registrarGate))
            $portals[] = "Registrar";
        if (in_array($rk, $libraryGate))
            $portals[] = "Library";
        if (in_array($rk, $propertyGate))
            $portals[] = "Property";

        if (!empty($portals)) {
            echo sprintf("%-5s %-35s %-30s %s\n", $u['id'], $u['username'], $rk, implode(', ', $portals));
        }
    }
}

echo "\n";
echo "=== Summary Query with faculty_admin role (no department filter) ===\n";
if ($db && $term) {
    $sql = 'SELECT fp.id, fp.first_name, fp.last_name, fp.designated_department, fp.profile_status, cr.clearance_id, cr.overall_status 
            FROM faculty_profiles fp 
            LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id 
            LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ? 
            WHERE (fp.position NOT IN ("Department Head", "Dean") OR fp.position IS NULL) 
            AND (fp.profile_status = ? OR fp.profile_status IS NULL)
            ORDER BY fp.last_name, fp.first_name
            LIMIT 10';
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $term['term_id'], 'Active']);
        $rows = $stmt->fetchAll();
        echo "Found " . count($rows) . " rows\n";
        foreach ($rows as $r) {
            echo "  id={$r['id']} name={$r['first_name']} {$r['last_name']} dept={$r['designated_department']} clearance_id=" . ($r['clearance_id'] ?? 'NULL') . "\n";
        }
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n</pre>";
