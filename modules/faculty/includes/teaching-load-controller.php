<?php
/**
 * Teaching Load - shared controller
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../../../includes/security.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$teachingLoadPageUrl = $teachingLoadPageUrl ?? (BASE_URL . '/modules/faculty/views/teaching-load-workflow.php');
$roleKey = strtolower(trim((string) getCurrentUserRoleKey()));
$userId = (int) (getCurrentUserId() ?? 0);
$chairRoles = ['program_chair', 'department_head', 'dept_head'];
$deanRoles = ['dean', 'faculty_admin'];
$registrarRoles = ['registrar', 'registrar_clearance'];
$historyAuthorizedRoles = array_merge($chairRoles, $deanRoles, $registrarRoles, ['vpaa']);
$allowedRoles = array_merge($chairRoles, $deanRoles, $registrarRoles, ['vpaa', 'faculty']);
$teachingLoadPageRoles = $teachingLoadPageRoles ?? $allowedRoles;
if (!in_array($roleKey, $allowedRoles, true) || !in_array($roleKey, $teachingLoadPageRoles, true)) {
    http_response_code(403);
    exit('You do not have access to the teaching-load workflow.');
}

$vpaaRequired = filter_var((string) (getenv('SMS2_REQUIRE_VPAA_LOAD_APPROVAL') ?: '0'), FILTER_VALIDATE_BOOLEAN);
$pdo = null;
$databaseError = '';
$errorMessage = '';
$successMessage = '';

function smsTeachingLoadEnsureSchema(PDO $pdo): void {
    $columns = $pdo->query('SHOW COLUMNS FROM teaching_load_requests')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('workflow_stage', $columns, true)) $pdo->exec("ALTER TABLE teaching_load_requests ADD COLUMN workflow_stage VARCHAR(40) NOT NULL DEFAULT 'dean_review'");
    if (!in_array('faculty_user_id', $columns, true)) $pdo->exec('ALTER TABLE teaching_load_requests ADD COLUMN faculty_user_id INT UNSIGNED NULL');
    if (!in_array('submitted_by_external_id', $columns, true)) $pdo->exec('ALTER TABLE teaching_load_requests ADD COLUMN submitted_by_external_id VARCHAR(64) NULL');
    $itemColumns = $pdo->query('SHOW COLUMNS FROM teaching_load_request_items')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('created_for_request', $itemColumns, true)) $pdo->exec('ALTER TABLE teaching_load_request_items ADD COLUMN created_for_request TINYINT(1) NOT NULL DEFAULT 0');
    $pdo->exec("CREATE TABLE IF NOT EXISTS teaching_load_workflow_history (history_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, load_request_id INT UNSIGNED NOT NULL, stage VARCHAR(40) NOT NULL, action VARCHAR(40) NOT NULL, performed_by_external_id VARCHAR(64) DEFAULT NULL, performed_by_role VARCHAR(80) NOT NULL, performed_by_name VARCHAR(150) NOT NULL, remarks TEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_load_workflow_request (load_request_id), KEY idx_load_workflow_stage (stage)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS teaching_load_final_records (final_record_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, load_request_id INT UNSIGNED NOT NULL, faculty_id INT UNSIGNED NOT NULL, term_id INT UNSIGNED NOT NULL, total_units DECIMAL(5,1) NOT NULL DEFAULT 0.0, subject_count INT UNSIGNED NOT NULL DEFAULT 0, assignments_json LONGTEXT NOT NULL, acknowledged_by_external_id VARCHAR(64) NOT NULL, finalized_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_final_record_request (load_request_id), KEY idx_final_record_faculty_term (faculty_id, term_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function smsTeachingLoadHistory(PDO $pdo, int $requestId, string $stage, string $action, string $remarks = ''): void {
    $insert = $pdo->prepare('INSERT INTO teaching_load_workflow_history (load_request_id, stage, action, performed_by_external_id, performed_by_role, performed_by_name, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([$requestId, $stage, $action, (string) (getCurrentUserId() ?? ''), (string) getCurrentUserRoleKey(), (string) getCurrentUserName(), $remarks !== '' ? $remarks : null]);
}

function smsTeachingLoadRedirect(string $notice): never {
    global $teachingLoadPageUrl;
    header('Location: ' . $teachingLoadPageUrl . '?notice=' . rawurlencode($notice));
    exit;
}

try {
    $pdo = getFacultyDatabaseConnection();
    smsTeachingLoadEnsureSchema($pdo);
} catch (Throwable $exception) {
    $databaseError = 'Teaching-load storage is unavailable. Check the faculty database connection and teaching-load tables.';
    error_log('Teaching load workflow initialization failed: ' . $exception->getMessage());
}

$roleStage = null;
if (in_array($roleKey, $deanRoles, true)) { $roleStage = 'dean_review'; }
elseif (in_array($roleKey, $registrarRoles, true)) { $roleStage = 'registrar_validation'; }
elseif ($roleKey === 'vpaa') { $roleStage = 'vpaa_approval'; }

$chairDepartment = '';
if (in_array($roleKey, $chairRoles, true) && $pdo instanceof PDO) {
    try {
        $mainDb = db();
        $emailStmt = $mainDb instanceof PDO ? $mainDb->prepare('SELECT email FROM users WHERE id = ? LIMIT 1') : null;
        $emailStmt?->execute([$userId]);
        $chairEmail = strtolower(trim((string) ($emailStmt?->fetchColumn() ?: '')));
        $departmentStmt = $pdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = ? OR (? <> '' AND LOWER(TRIM(email)) = ? AND LOWER(TRIM(request_status)) = 'approved') ORDER BY (user_id = ?) DESC LIMIT 1");
        $departmentStmt->execute([$userId, $chairEmail, $chairEmail, $userId]);
        $chairDepartment = trim((string) ($departmentStmt->fetchColumn() ?: ''));
    } catch (Throwable $exception) { error_log('Teaching load chair department lookup failed: ' . $exception->getMessage()); }
}

if ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null);
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'submit_load') {
            if (!in_array($roleKey, $chairRoles, true)) throw new RuntimeException('Only the Program Chair can submit a teaching load.');
            if ($chairDepartment === '') throw new RuntimeException('Your Program Chair account must have a linked faculty profile with a designated department before submitting loads.');

            $facultyId = (int) ($_POST['faculty_id'] ?? 0);
            $termId = (int) ($_POST['term_id'] ?? 0);
            $requestedUnits = (float) ($_POST['requested_units'] ?? 0);

            if ($facultyId <= 0 || $termId <= 0 || $requestedUnits <= 0) throw new RuntimeException('Choose a faculty member, term, and requested units.');

            $facultyStmt = $pdo->prepare("SELECT f.faculty_id, fp.user_id FROM faculty f JOIN faculty_profiles fp ON fp.id = (SELECT fp2.id FROM faculty_profiles fp2 WHERE fp2.user_id IS NOT NULL AND LOWER(TRIM(fp2.request_status)) = 'approved' AND ((f.email IS NOT NULL AND f.email <> '' AND LOWER(fp2.email) = LOWER(f.email)) OR fp2.faculty_id = f.faculty_no) ORDER BY (f.email IS NOT NULL AND f.email <> '' AND LOWER(fp2.email) = LOWER(f.email)) DESC LIMIT 1) WHERE f.faculty_id = ? AND f.profile_status = 'Active' LIMIT 1");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);
            if (!$faculty) throw new RuntimeException('This faculty member needs an approved, linked faculty account before a load can be submitted for acknowledgement.');

            $departmentCheck = $pdo->prepare("SELECT f.department_id, COUNT(*) AS match_count FROM faculty f JOIN departments d ON d.department_id = f.department_id WHERE f.faculty_id = ? AND (LOWER(TRIM(d.code)) = LOWER(?) OR LOWER(TRIM(d.name)) = LOWER(?)) GROUP BY f.department_id");
            $departmentCheck->execute([$facultyId, $chairDepartment, $chairDepartment]);
            $facultyDepartmentId = $departmentCheck->fetch(PDO::FETCH_ASSOC);
            if (!$facultyDepartmentId || (int) $facultyDepartmentId['match_count'] !== 1) throw new RuntimeException('You can only submit teaching loads for faculty in your designated department.');

            $pendingStmt = $pdo->prepare("SELECT load_request_id FROM teaching_load_requests WHERE faculty_id = ? AND term_id = ? AND status <> 'Rejected' LIMIT 1");
            $pendingStmt->execute([$facultyId, $termId]);
            if ($pendingStmt->fetchColumn()) throw new RuntimeException('A teaching-load request already exists for this faculty member and term.');

            $pdo->beginTransaction();
            $insert = $pdo->prepare("INSERT INTO teaching_load_requests (faculty_id, term_id, total_units, status, submitted_at, workflow_stage, faculty_user_id, submitted_by_external_id) VALUES (?, ?, ?, 'Pending', NOW(), 'dean_review', ?, ?)");
            $insert->execute([$facultyId, $termId, $requestedUnits, (int) $faculty['user_id'], (string) $userId]);
            $requestId = (int) $pdo->lastInsertId();
            
            $scheduleInsert = $pdo->prepare("INSERT INTO class_schedules (term_id, subject_id, faculty_id, section, units, day_pattern, time_start, time_end, status, has_conflict) VALUES (?, 1, ?, 'TBA', ?, NULL, NULL, NULL, 'Proposed', 0)");
            $scheduleInsert->execute([$termId, $facultyId, $requestedUnits]);
            $scheduleId = (int) $pdo->lastInsertId();

            $itemInsert = $pdo->prepare('INSERT INTO teaching_load_request_items (load_request_id, class_schedule_id, created_for_request) VALUES (?, ?, 1)');
            $itemInsert->execute([$requestId, $scheduleId]);

            smsTeachingLoadHistory($pdo, $requestId, 'program_chair', 'submitted', 'Submitted ' . $requestedUnits . ' units to Dean for review.');
            $pdo->commit();
            smsTeachingLoadRedirect('submitted');
        }

        if (in_array($action, ['dean_approve', 'registrar_validate', 'vpaa_approve', 'faculty_acknowledge', 'reject'], true)) {
            $requestId = (int) ($_POST['load_request_id'] ?? 0);
            $remarks = trim((string) ($_POST['remarks'] ?? ''));
            if ($requestId <= 0) throw new RuntimeException('The teaching-load request was not found.');
            if ($action === 'reject' && $remarks === '') throw new RuntimeException('Enter a reason before rejecting this load.');

            $pdo->beginTransaction();
            $requestStmt = $pdo->prepare('SELECT * FROM teaching_load_requests WHERE load_request_id = ? FOR UPDATE');
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) throw new RuntimeException('The teaching-load request was not found.');

            $stage = (string) $request['workflow_stage'];
            $nextStage = null;
            $historyAction = '';
            $finalStatus = 'Pending';
            $authorized = false;

            if ($action === 'dean_approve' && in_array($roleKey, $deanRoles, true) && $stage === 'dean_review') { $nextStage = 'registrar_validation'; $historyAction = 'dean_approved'; $authorized = true; }
            elseif ($action === 'registrar_validate' && in_array($roleKey, $registrarRoles, true) && $stage === 'registrar_validation') { $nextStage = $vpaaRequired ? 'vpaa_approval' : 'faculty_acknowledgement'; $historyAction = 'registrar_validated'; $authorized = true; }
            elseif ($action === 'vpaa_approve' && $roleKey === 'vpaa' && $vpaaRequired && $stage === 'vpaa_approval') { $nextStage = 'faculty_acknowledgement'; $historyAction = 'vpaa_approved'; $authorized = true; }
            elseif ($action === 'faculty_acknowledge' && $roleKey === 'faculty' && $stage === 'faculty_acknowledgement' && (int) $request['faculty_user_id'] === $userId) { $nextStage = 'completed'; $historyAction = 'faculty_acknowledged'; $finalStatus = 'Approved'; $authorized = true; }
            elseif ($action === 'reject' && $roleStage !== null && $stage === $roleStage) { $nextStage = 'rejected'; $historyAction = 'rejected'; $finalStatus = 'Rejected'; $authorized = true; }

            if (!$authorized || $nextStage === null) throw new RuntimeException('This request is not awaiting an action from your role.');

            if ($action === 'reject') {
                $rejectAssignments = $pdo->prepare("UPDATE class_schedules cs JOIN teaching_load_request_items i ON i.class_schedule_id = cs.class_schedule_id SET cs.status = 'Rejected', cs.updated_at = NOW() WHERE i.load_request_id = ? AND i.created_for_request = 1 AND cs.status = 'Proposed'");
                $rejectAssignments->execute([$requestId]);
            }

            if ($action === 'faculty_acknowledge') {
                $assignmentStmt = $pdo->prepare("SELECT s.code, s.title, cs.section, cs.units, cs.day_pattern, cs.time_start, cs.time_end FROM teaching_load_request_items i JOIN class_schedules cs ON cs.class_schedule_id = i.class_schedule_id JOIN subjects s ON s.subject_id = cs.subject_id WHERE i.load_request_id = ? ORDER BY s.code, cs.section");
                $assignmentStmt->execute([$requestId]);
                $assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($assignments === []) throw new RuntimeException('The request has no subject assignments and cannot be finalized.');
                $snapshot = json_encode($assignments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($snapshot === false) throw new RuntimeException('The final teaching-load snapshot could not be created.');
                $approverStmt = $pdo->prepare("SELECT performed_by_external_id FROM teaching_load_workflow_history WHERE load_request_id = ? AND action IN ('registrar_validated', 'vpaa_approved') ORDER BY history_id DESC LIMIT 1");
                $approverStmt->execute([$requestId]);
                $approverId = (string) ($approverStmt->fetchColumn() ?: '');
                if ($approverId === '') throw new RuntimeException('The required academic-office approval record is missing; this load cannot be finalized.');
                $approveAssignments = $pdo->prepare("UPDATE class_schedules cs JOIN teaching_load_request_items i ON i.class_schedule_id = cs.class_schedule_id SET cs.status = 'Approved', cs.approved_by_external_id = ?, cs.approved_at = NOW(), cs.updated_at = NOW() WHERE i.load_request_id = ? AND cs.status = 'Proposed'");
                $approveAssignments->execute([$approverId, $requestId]);
                $finalRecord = $pdo->prepare('INSERT INTO teaching_load_final_records (load_request_id, faculty_id, term_id, total_units, subject_count, assignments_json, acknowledged_by_external_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $finalRecord->execute([$requestId, (int) $request['faculty_id'], (int) $request['term_id'], (float) $request['total_units'], count($assignments), $snapshot, (string) $userId]);
            }

            $update = $pdo->prepare('UPDATE teaching_load_requests SET workflow_stage = ?, status = ?, reviewed_by_external_id = ?, reviewed_at = NOW(), comments = ? WHERE load_request_id = ?');
            $update->execute([$nextStage, $finalStatus, (string) $userId, $remarks !== '' ? $remarks : null, $requestId]);
            smsTeachingLoadHistory($pdo, $requestId, $stage, $historyAction, $remarks);
            $pdo->commit();
            smsTeachingLoadRedirect($historyAction);
        }
        throw new RuntimeException('Unknown teaching-load action.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorMessage = $exception instanceof RuntimeException ? $exception->getMessage() : 'The teaching-load action could not be completed.';
        if (!$exception instanceof RuntimeException) error_log('Teaching load workflow action failed: ' . $exception->getMessage());
    }
}

if (isset($_GET['notice'])) {
    $notices = [
        'submitted' => 'Teaching load submitted to the Dean.',
        'dean_approved' => 'Dean approval recorded. The request is with the Registrar.',
        'registrar_validated' => $vpaaRequired ? 'Registrar validation recorded. The request is with the VP for Academic Affairs.' : 'Registrar validation recorded. The request is ready for faculty acknowledgement.',
        'vpaa_approved' => 'Final approval recorded. The request is ready for faculty acknowledgement.',
        'faculty_acknowledged' => 'Acknowledgement recorded. This is now the final teaching-load record.',
        'rejected' => 'The request was rejected and the reason was recorded.',
    ];
    $successMessage = $notices[(string) $_GET['notice']] ?? '';
}

$terms = [];
$facultyOptions = [];
$subjects = [];
$schedules = [];
$requests = [];
$itemsByRequest = [];
$historyByRequest = [];
$selectedFacultyId = (int) ($_GET['faculty_id'] ?? 0);
$selectedTermId = (int) ($_GET['term_id'] ?? 0);

if ($pdo instanceof PDO) {
    try {
        $terms = $pdo->query('SELECT term_id, academic_year, semester, is_current FROM academic_terms ORDER BY is_current DESC, academic_year DESC, semester ASC')->fetchAll(PDO::FETCH_ASSOC);
        if (in_array($roleKey, $chairRoles, true)) {
            if ($chairDepartment !== '') {
                // CHANGED: Added employment_status to the SELECT query
                $facultyStmt = $pdo->prepare("SELECT f.faculty_id, f.faculty_no, f.first_name, f.last_name, f.employment_status, fp.user_id, COALESCE(NULLIF(fp.designated_department, ''), 'Department not set') AS department FROM faculty f JOIN departments d ON d.department_id = f.department_id JOIN faculty_profiles fp ON fp.id = (SELECT fp2.id FROM faculty_profiles fp2 WHERE fp2.user_id IS NOT NULL AND LOWER(TRIM(fp2.request_status)) = 'approved' AND ((f.email IS NOT NULL AND f.email <> '' AND LOWER(fp2.email) = LOWER(f.email)) OR fp2.faculty_id = f.faculty_no) ORDER BY (f.email IS NOT NULL AND f.email <> '' AND LOWER(fp2.email) = LOWER(f.email)) DESC LIMIT 1) WHERE f.profile_status = 'Active' AND f.position = 'Faculty Professor' AND (LOWER(TRIM(d.code)) = LOWER(?) OR LOWER(TRIM(d.name)) = LOWER(?)) ORDER BY f.last_name, f.first_name");
                $facultyStmt->execute([$chairDepartment, $chairDepartment]);
                $facultyOptions = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $requestSql = "SELECT r.load_request_id, r.faculty_id, r.term_id, r.total_units, r.status, r.workflow_stage, r.submitted_at, r.faculty_user_id, f.first_name, f.last_name, f.faculty_no, t.academic_year, t.semester FROM teaching_load_requests r JOIN faculty f ON f.faculty_id = r.faculty_id JOIN academic_terms t ON t.term_id = r.term_id";
        $params = [];
        if (in_array($roleKey, $chairRoles, true)) { $requestSql .= ' WHERE r.submitted_by_external_id = ?'; $params[] = (string) $userId; }
        elseif (in_array($roleKey, $deanRoles, true)) { $requestSql .= ' WHERE (r.workflow_stage = ? OR r.workflow_stage IN (\'registrar_validation\', \'vpaa_approval\', \'faculty_acknowledgement\', \'completed\', \'rejected\'))'; $params[] = $roleStage; }
        elseif (in_array($roleKey, $registrarRoles, true)) { $requestSql .= ' WHERE (r.workflow_stage = ? OR r.workflow_stage IN (\'vpaa_approval\', \'faculty_acknowledgement\', \'completed\', \'rejected\'))'; $params[] = $roleStage; }
        elseif ($roleKey === 'vpaa') { $requestSql .= ' WHERE r.workflow_stage = ?'; $params[] = $roleStage; }
        else { $requestSql .= ' WHERE r.faculty_user_id = ?'; $params[] = $userId; }
        $requestSql .= ' ORDER BY r.submitted_at DESC, r.load_request_id DESC LIMIT 300';
        $requestStmt = $pdo->prepare($requestSql);
        $requestStmt->execute($params);
        $requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($requests !== []) {
            $requestIds = array_map(static fn(array $row): int => (int) $row['load_request_id'], $requests);
            $marks = implode(',', array_fill(0, count($requestIds), '?'));
            $itemStmt = $pdo->prepare("SELECT i.load_request_id, s.code, s.title, cs.section, cs.units FROM teaching_load_request_items i JOIN class_schedules cs ON cs.class_schedule_id = i.class_schedule_id JOIN subjects s ON s.subject_id = cs.subject_id WHERE i.load_request_id IN ($marks) ORDER BY s.code, cs.section");
            $itemStmt->execute($requestIds);
            foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $item) { $itemsByRequest[(int) $item['load_request_id']][] = $item; }
            $finalStmt = $pdo->prepare("SELECT load_request_id, assignments_json FROM teaching_load_final_records WHERE load_request_id IN ($marks)");
            $finalStmt->execute($requestIds);
            foreach ($finalStmt->fetchAll(PDO::FETCH_ASSOC) as $finalRecord) {
                $snapshot = json_decode((string) $finalRecord['assignments_json'], true);
                if (is_array($snapshot)) $itemsByRequest[(int) $finalRecord['load_request_id']] = $snapshot;
            }
            if (in_array($roleKey, $historyAuthorizedRoles, true)) {
                $historyStmt = $pdo->prepare("SELECT load_request_id, stage, action, performed_by_name, performed_by_role, remarks, created_at FROM teaching_load_workflow_history WHERE load_request_id IN ($marks) ORDER BY created_at ASC, history_id ASC");
                $historyStmt->execute($requestIds);
                foreach ($historyStmt->fetchAll(PDO::FETCH_ASSOC) as $history) { $historyByRequest[(int) $history['load_request_id']][] = $history; }
            }
        }
    } catch (Throwable $exception) {
        if ($databaseError === '') {
            $databaseError = 'Teaching-load data could not be read.';
            error_log('Teaching load workflow query failed: ' . $exception->getMessage());
        }
    }
}

$stageLabels = ['dean_review' => 'Dean review', 'registrar_validation' => 'Registrar validation', 'vpaa_approval' => 'VPAA final approval', 'faculty_acknowledgement' => 'Faculty acknowledgement', 'completed' => 'Final record', 'rejected' => 'Rejected'];