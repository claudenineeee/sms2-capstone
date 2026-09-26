<?php
require_once __DIR__ . '/../modules/faculty/controllers/clearance.php';

function assertTest($condition, $message)
{
    if (!$condition) {
        echo "❌ FAILED: $message\n";
        exit(1);
    } else {
        echo "✅ PASSED: $message\n";
    }
}

echo "=== TESTING SEQUENTIAL CLEARANCE SYSTEM ===\n";

// 1. Test sequential stages order
$stages = facultyClearanceSequentialStages();
assertTest(count($stages) === 6, "Exactly 6 sequential stages defined");
assertTest($stages[0] === 'Academic Clearance', "Stage 1 is Academic Clearance");
assertTest($stages[1] === 'Library Clearance', "Stage 2 is Library Clearance");
assertTest($stages[2] === 'Financial Clearance', "Stage 3 is Financial Clearance");
assertTest($stages[3] === 'Property Clearance', "Stage 4 is Property Clearance");
assertTest($stages[4] === 'HR Clearance', "Stage 5 is HR Clearance");
assertTest($stages[5] === 'Department Clearance', "Stage 6 is Department Clearance");

// Helper to make mock request
function makeMockRequest($formStatus, $itemsStatusMap)
{
    $items = [];
    $id = 1;
    foreach ($itemsStatusMap as $name => $status) {
        $items[] = [
            'clearance_item_id' => $id,
            'clearance_office_id' => $id,
            'requirement_name' => $name,
            'status' => $status,
            'file_path' => ($status !== 'Missing') ? 'uploads/test.pdf' : null,
            'original_name' => ($status !== 'Missing') ? 'test.pdf' : null,
            'remarks' => ($status === 'Denied') ? '[With Deficiency] Please fix' : null,
            'cleared_at' => ($status === 'Cleared') ? date('Y-m-d H:i:s') : null,
        ];
        $id++;
    }
    return [
        'clearance_id' => 101,
        'faculty_id' => 5,
        'term_id' => 1,
        'form_submitted' => 1,
        'form_status' => $formStatus,
        'overall_status' => 'Under Verification',
        'items' => $items,
    ];
}

// 2. State A: Form pending / not approved -> Academic is locked, all others locked
$reqA = makeMockRequest('Pending Review', [
    'Academic Clearance' => 'Missing',
    'Library Clearance' => 'Missing',
    'Financial Clearance' => 'Missing',
    'Property Clearance' => 'Missing',
    'HR Clearance' => 'Missing',
    'Department Clearance' => 'Missing',
]);
$progA = facultyClearanceStageProgression($reqA);
assertTest($progA['Academic Clearance']['is_unlocked'] === false, "State A: Academic is locked when form not approved");
assertTest($progA['Library Clearance']['is_unlocked'] === false, "State A: Library is locked");
assertTest($progA['Department Clearance']['is_unlocked'] === false, "State A: Department Clearance is locked");

// 3. State B: Form approved -> Academic is ready (unlocked), Library-Department are locked
$reqB = makeMockRequest('Approved', [
    'Academic Clearance' => 'Missing',
    'Library Clearance' => 'Missing',
    'Financial Clearance' => 'Missing',
    'Property Clearance' => 'Missing',
    'HR Clearance' => 'Missing',
    'Department Clearance' => 'Missing',
]);
$progB = facultyClearanceStageProgression($reqB);
assertTest($progB['Academic Clearance']['is_unlocked'] === true, "State B: Academic is unlocked");
assertTest($progB['Academic Clearance']['state'] === 'ready', "State B: Academic state is ready");
assertTest($progB['Library Clearance']['is_unlocked'] === false, "State B: Library is locked");
assertTest($progB['Department Clearance']['is_unlocked'] === false, "State B: Department Clearance is locked");

// 4. State C: Academic is Cleared -> Academic cleared, Library is ready (unlocked), Financial locked
$reqC = makeMockRequest('Approved', [
    'Academic Clearance' => 'Cleared',
    'Library Clearance' => 'Missing',
    'Financial Clearance' => 'Missing',
    'Property Clearance' => 'Missing',
    'HR Clearance' => 'Missing',
    'Department Clearance' => 'Missing',
]);
$progC = facultyClearanceStageProgression($reqC);
assertTest($progC['Academic Clearance']['state'] === 'cleared', "State C: Academic is cleared");
assertTest($progC['Library Clearance']['is_unlocked'] === true, "State C: Library is unlocked");
assertTest($progC['Library Clearance']['state'] === 'ready', "State C: Library is ready");
assertTest($progC['Financial Clearance']['is_unlocked'] === false, "State C: Financial is locked");
assertTest($progC['Department Clearance']['is_unlocked'] === false, "State C: Department Clearance is locked");

// 5. State D: Academic cleared, Library has deficiency (Denied/With Deficiency) -> Library is with_issue, Financial remains locked
$reqD = makeMockRequest('Approved', [
    'Academic Clearance' => 'Cleared',
    'Library Clearance' => 'Denied',
    'Financial Clearance' => 'Missing',
    'Property Clearance' => 'Missing',
    'HR Clearance' => 'Missing',
    'Department Clearance' => 'Missing',
]);
$progD = facultyClearanceStageProgression($reqD);
assertTest($progD['Library Clearance']['is_unlocked'] === true, "State D: Library is unlocked for re-upload");
assertTest($progD['Library Clearance']['state'] === 'with_issue', "State D: Library state is with_issue");
assertTest($progD['Financial Clearance']['is_unlocked'] === false, "State D: Financial remains locked because Library has issue");
assertTest($progD['Department Clearance']['is_unlocked'] === false, "State D: Department Clearance remains locked");

// 6. State E: Stages 1-5 all Cleared -> Department Clearance (Step 6) is ready (unlocked)
$reqE = makeMockRequest('Approved', [
    'Academic Clearance' => 'Cleared',
    'Library Clearance' => 'Cleared',
    'Financial Clearance' => 'Cleared',
    'Property Clearance' => 'Cleared',
    'HR Clearance' => 'Cleared',
    'Department Clearance' => 'Missing',
]);
$progE = facultyClearanceStageProgression($reqE);
assertTest($progE['Academic Clearance']['state'] === 'cleared', "State E: Academic cleared");
assertTest($progE['HR Clearance']['state'] === 'cleared', "State E: HR cleared");
assertTest($progE['Department Clearance']['is_unlocked'] === true, "State E: Department Clearance unlocked");
assertTest($progE['Department Clearance']['state'] === 'ready', "State E: Department Clearance ready");

// 7. State F: All 6 Cleared -> overall status = Completed
$reqF = makeMockRequest('Approved', [
    'Academic Clearance' => 'Cleared',
    'Library Clearance' => 'Cleared',
    'Financial Clearance' => 'Cleared',
    'Property Clearance' => 'Cleared',
    'HR Clearance' => 'Cleared',
    'Department Clearance' => 'Cleared',
]);
$progF = facultyClearanceStageProgression($reqF);
$statusF = facultyClearanceStatus($reqF);
assertTest($statusF === 'Completed', "State F: Clearance status is Completed when all 6 cleared");

// 8. Test facultyClearanceIsOfficeUnlocked
assertTest(facultyClearanceIsOfficeUnlocked($reqB, 'Academic Clearance') === true, "Helper: Academic is unlocked in State B");
assertTest(facultyClearanceIsOfficeUnlocked($reqB, 'Library Clearance') === false, "Helper: Library is locked in State B");
assertTest(facultyClearanceIsOfficeUnlocked($reqC, 'Library Clearance') === true, "Helper: Library is unlocked in State C");
assertTest(facultyClearanceIsOfficeUnlocked($reqC, 'Financial Clearance') === false, "Helper: Financial is locked in State C");

echo "\n🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
