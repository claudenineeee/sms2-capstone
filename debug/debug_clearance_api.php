<?php
/**
 * Direct debug of ClearanceController without auth restrictions
 * DELETE this file after use!
 */
require_once __DIR__ . '/config/config.php';

// Simulate a web request context for BASE_URL detection
$_SERVER['SCRIPT_NAME'] = '/Clone/sms2-capstone/debug_clearance_api.php';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once ROOT_PATH . '/modules/faculty/controllers/clearance.php';

$db = facultyDb();
if (!$db) {
    die(json_encode(['error' => 'DB connection failed']));
}

echo "<pre>\n";

// 1. Check the active term
$term = facultyClearanceTerm($db);
echo "=== ACTIVE TERM ===\n";
print_r($term);
echo "\n";

// 2. Check faculty_profiles table
$stmt = $db->query('SELECT COUNT(*) as cnt FROM faculty_profiles WHERE profile_status = "Active" OR profile_status IS NULL');
$row = $stmt->fetch();
echo "=== Active Faculty Profiles ===\n";
echo "Count: " . $row['cnt'] . "\n\n";

// 3. Check clearance_requests for the active term
if ($term && !empty($term['term_id'])) {
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM clearance_requests WHERE term_id = ?');
    $stmt->execute([$term['term_id']]);
    $row = $stmt->fetch();
    echo "=== Clearance Requests for term {$term['term_id']} ===\n";
    echo "Count: " . $row['cnt'] . "\n\n";
}

// 4. Run the exact SQL that the summary action runs
if ($term) {
    $sql = 'SELECT fp.*, cr.clearance_id, cr.overall_status, cr.submitted_at, cr.updated_at 
            FROM faculty_profiles fp 
            LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id 
            LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ? 
            WHERE (fp.position NOT IN ("Department Head", "Dean") OR fp.position IS NULL) 
            AND (fp.profile_status = ? OR fp.profile_status IS NULL)
            ORDER BY cr.updated_at DESC, fp.last_name, fp.first_name
            LIMIT 5';
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([(int)$term['term_id'], 'Active']);
        $rows = $stmt->fetchAll();
        echo "=== Summary Query Results (first 5) ===\n";
        echo "Count returned: " . count($rows) . "\n";
        foreach ($rows as $i => $r) {
            echo "Row $i: " . ($r['first_name'] ?? '?') . " " . ($r['last_name'] ?? '?') . 
                 " | profile_status=" . ($r['profile_status'] ?? 'NULL') . 
                 " | position=" . ($r['position'] ?? 'NULL') . 
                 " | clearance_id=" . ($r['clearance_id'] ?? 'NULL') . "\n";
        }
    } catch (PDOException $e) {
        echo "SQL ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// 5. Check if faculty table has faculty_no link to faculty_profiles
$stmt = $db->query('SELECT COUNT(*) as total FROM faculty');
$totalFaculty = $stmt->fetch()['total'];
echo "=== faculty table count ===\n";
echo "Total: $totalFaculty\n\n";

// 6. Check the join
$stmt = $db->query('SELECT COUNT(*) as cnt FROM faculty f JOIN faculty_profiles fp ON f.faculty_no = fp.faculty_id LIMIT 1');
$joined = $stmt->fetch()['cnt'];
echo "=== faculty JOIN faculty_profiles (by faculty_no = faculty_id) ===\n";
echo "Joined rows: $joined\n\n";

// 7. Check columns in faculty_profiles  
$stmt = $db->query('DESCRIBE faculty_profiles');
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "=== faculty_profiles columns ===\n";
echo implode(', ', $cols) . "\n\n";

// 8. Check a sample faculty_profiles row
$stmt = $db->query('SELECT id, faculty_id, first_name, last_name, profile_status, position FROM faculty_profiles LIMIT 3');
$rows = $stmt->fetchAll();
echo "=== Sample faculty_profiles rows ===\n";
foreach ($rows as $r) {
    echo "id={$r['id']} | faculty_id={$r['faculty_id']} | name={$r['first_name']} {$r['last_name']} | status={$r['profile_status']} | pos={$r['position']}\n";
}
echo "\n";

// 9. Check a sample faculty row
$stmt = $db->query('SELECT faculty_id, faculty_no, external_user_id FROM faculty LIMIT 3');
$rows = $stmt->fetchAll();
echo "=== Sample faculty rows ===\n";
foreach ($rows as $r) {
    echo "faculty_id={$r['faculty_id']} | faculty_no={$r['faculty_no']} | external_user_id={$r['external_user_id']}\n";
}

echo "\n</pre>";
