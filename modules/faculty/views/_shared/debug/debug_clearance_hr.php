<?php
/**
 * Simulate ClearanceController for an HR user
 * Tests if the controller produces valid JSON for clearance office roles
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

// Don't load authentication - just test the data functions directly
require_once __DIR__ . '/../../../../modules/faculty/controllers/clearance.php';

// Fake session for testing
$_SESSION['user_id'] = 250; // hr user (hr_clearance role)
$_SESSION['user_role_key'] = 'hr_clearance';

$db = facultyDb();
if (!$db) {
    echo json_encode(['ok' => false, 'error' => 'DB unavailable']);
    exit;
}

$userId = 250; // HR user
$role = 'hr_clearance';
$profile = facultyClearanceProfile($db, $userId);
$term = facultyClearanceTerm($db);
$offices = facultyClearanceOffices($db);

$isDeptHead = in_array($role, ['department_head', 'dept_head'], true);
$isClearanceOffice = in_array($role, ['department_head', 'dept_head', 'hr', 'hr_clearance', 'faculty_admin', 'dean', 'registrar_clearance', 'registrar', 'finance_office', 'finance', 'library_clearance', 'library', 'property_custodian_office', 'property', 'admin', 'super_admin'], true);
$assignedDepartments = facultyClearanceAssignedDepartments($profile ?: [], $db);

echo "<pre>\n";
echo "User ID: $userId\n";
echo "Role: $role\n";
echo "isClearanceOffice: " . ($isClearanceOffice ? 'YES' : 'NO') . "\n";
echo "Profile: " . ($profile ? "id={$profile['id']} name={$profile['first_name']}" : "NULL") . "\n";
echo "Term: " . ($term ? "term_id={$term['term_id']} {$term['academic_year']}" : "NULL") . "\n";
echo "Assigned Depts: " . (empty($assignedDepartments) ? "none (sees all)" : implode(', ', $assignedDepartments)) . "\n\n";

// Run the summary query
$sql = 'SELECT fp.*, cr.clearance_id, cr.overall_status, cr.submitted_at, cr.updated_at FROM faculty_profiles fp LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ? WHERE (fp.position NOT IN ("Department Head", "Dean") OR fp.position IS NULL) AND (fp.profile_status = ? OR fp.profile_status IS NULL)';
$params = [(int) $term['term_id'], 'Active'];

// HR role: no department filter (sees all)
$sql .= ' ORDER BY cr.updated_at DESC, fp.last_name, fp.first_name';

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    echo "Summary query returned " . count($rows) . " rows\n\n";

    $rowsProcessed = [];
    foreach ($rows as &$row) {
        $clearanceData = facultyClearanceJson($row['clearance_id'] ? facultyClearanceRequest($db, (int) $row['id'], (int) $term['term_id']) : null);
        $row['clearance'] = $clearanceData;
        $row['name'] = facultyClearanceDisplayName($row);
        $rowsProcessed[] = [
            'name' => $row['name'],
            'id' => $row['id'],
            'clearance_status' => $clearanceData['status'] ?? 'Not Submitted'
        ];
    }
    unset($row);

    echo "Rows processed successfully:\n";
    foreach ($rowsProcessed as $r) {
        echo "  id={$r['id']} name={$r['name']} status={$r['clearance_status']}\n";
    }

    // Test JSON encoding
    $response = ['ok' => true, 'profile' => $profile, 'term' => $term, 'offices' => $offices, 'rows' => $rows, 'metrics' => ['pending' => 0, 'action_required' => 0, 'archived' => 0]];
    $json = json_encode($response, JSON_UNESCAPED_SLASHES);

    echo "\nJSON encode result: " . ($json !== false ? "SUCCESS" : "FAILED: " . json_last_error_msg()) . "\n";
    echo "JSON length: " . strlen($json ?? '') . " bytes\n";
    echo "First 200 chars: " . substr($json ?? 'null', 0, 200) . "\n";

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
