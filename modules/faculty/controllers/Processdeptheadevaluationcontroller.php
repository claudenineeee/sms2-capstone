<?php
/**
 * ProcessDeptHeadEvaluationController
 * Action controller to handle Department Head -> Faculty evaluation submissions.
 *
 * Mirrors ProcessPeerEvaluationController.php's session/database/term
 * handling, with two extra rules specific to this evaluation type:
 *   1. Only accounts whose position is "Department Head" may submit.
 *   2. The target faculty member must be in the SAME department as the
 *      evaluating Department Head.
 * Both are enforced here, not just hidden in the UI - the view page only
 * hides the form for non-department-heads, it doesn't stop someone from
 * POSTing directly to this controller.
 */
require_once __DIR__ . '/../../../config/config.php';

// NOTE: a bare session_start() here (without session_name('SMS2SESSID') set
// first, which config/session.php does) would create/join PHP's default
// session instead of the app's real one. authentication.php pulls in
// config/session.php, which starts the correctly-named session for us.
require_once ROOT_PATH . '/includes/authentication.php';

// 1. Enforce POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

// 2. Database Connection Setup
require_once __DIR__ . '/../config/database.php';

if (function_exists('facultyDb')) {
    $pdo = facultyDb();
}

if (!isset($pdo) || !$pdo) {
    $pdo = $conn ?? $db ?? null;
}

if (!$pdo) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'faculty_db';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
    }
}

// 3. Resolve Evaluator's Faculty ID, Position & Department from Session
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$sessionEmail  = $_SESSION['user_email'] ?? null;
$evaluatorId   = 0;
$evaluatorPosition = null;
$evaluatorDeptId   = null;
$evaluatorDeptName = '';

if ($sessionUserId || $sessionEmail) {
    try {
        $stmtEval = $pdo->prepare("
            SELECT f.faculty_id, f.department_id, fp.position, fp.designated_department
            FROM faculty_profiles fp
            LEFT JOIN faculty f ON f.faculty_id = (
                SELECT f2.faculty_id
                FROM faculty f2
                WHERE (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email)
                   OR f2.faculty_no = fp.faculty_id
                ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email) DESC
                LIMIT 1
            )
            WHERE fp.user_id = :uid1
               OR fp.id = :uid2
               OR (:email1 IS NOT NULL AND fp.email = :email2)
            LIMIT 1
        ");
        $stmtEval->execute([
            'uid1'   => $sessionUserId,
            'uid2'   => $sessionUserId,
            'email1' => $sessionEmail,
            'email2' => $sessionEmail
        ]);
        $evalData = $stmtEval->fetch();

        if ($evalData && $evalData['faculty_id'] !== null) {
            $evaluatorId       = (int)$evalData['faculty_id'];
            $evaluatorDeptId   = $evalData['department_id'] !== null ? (int)$evalData['department_id'] : null;
            $evaluatorDeptName = strtolower(trim((string)($evalData['designated_department'] ?? '')));
            $evaluatorPosition = trim((string)($evalData['position'] ?? ''));
        }
    } catch (PDOException $e) {
        $evaluatorId = 0;
    }
}

if (!$evaluatorId) {
    $_SESSION['flash_error'] = 'Your account is not linked to an official faculty record, so an evaluation cannot be recorded under your name. Please contact the administrator.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

// Server-side enforcement: only Department Heads may submit this evaluation type.
if (strcasecmp($evaluatorPosition, 'Department Head') !== 0) {
    $_SESSION['flash_error'] = 'Only Department Heads can submit this type of evaluation.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

// 4. Input Validation
$targetFacultyId = filter_input(INPUT_POST, 'faculty_id', FILTER_VALIDATE_INT);
$remarks         = trim($_POST['remarks'] ?? '');

if (!$targetFacultyId) {
    $_SESSION['flash_error'] = 'Invalid faculty member selected.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

if ($targetFacultyId === $evaluatorId) {
    $_SESSION['flash_error'] = 'You cannot evaluate yourself.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

// Defensive check: confirm target faculty exists and belongs to the SAME department
try {
    $checkFaculty = $pdo->prepare("
        SELECT f.faculty_id, f.department_id, fp.designated_department 
        FROM faculty f
        LEFT JOIN faculty_profiles fp ON (
            (fp.email IS NOT NULL AND fp.email <> '' AND fp.email = f.email)
            OR fp.faculty_id = f.faculty_no
        )
        WHERE f.faculty_id = :fid 
        LIMIT 1
    ");
    $checkFaculty->execute(['fid' => $targetFacultyId]);
    $targetFaculty = $checkFaculty->fetch();

    if (!$targetFaculty) {
        $_SESSION['flash_error'] = 'This faculty member is not linked to an official faculty record and cannot be evaluated yet.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
        exit;
    }

    $targetDeptId   = $targetFaculty['department_id'] !== null ? (int)$targetFaculty['department_id'] : null;
    $targetDeptName = strtolower(trim((string)($targetFaculty['designated_department'] ?? '')));

    // Compare by Department ID or Department Name string (e.g. "BSIT")
    $idMatch   = ($evaluatorDeptId !== null && $targetDeptId !== null && $evaluatorDeptId === $targetDeptId);
    $nameMatch = (!empty($evaluatorDeptName) && !empty($targetDeptName) && $evaluatorDeptName === $targetDeptName);

    if (!$idMatch && !$nameMatch) {
        $_SESSION['flash_error'] = 'You can only evaluate faculty members within your own department.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Unable to verify the selected faculty member: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

// 5. Resolve Current Academic Term
try {
    $termStmt = $pdo->query("SELECT term_id FROM academic_terms WHERE is_current = 1 LIMIT 1");
    $currentTerm = $termStmt->fetch();
} catch (PDOException $e) {
    $currentTerm = false;
}

if (!$currentTerm) {
    $_SESSION['flash_error'] = 'No active academic term is configured. Please ask the administrator to set one before submitting evaluations.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
    exit;
}

$termId = (int)$currentTerm['term_id'];

// 6. Calculate Composite Rating (Scale 1-5 across 12 criteria)
$totalScore = 0;
for ($i = 1; $i <= 12; $i++) {
    $val = filter_input(INPUT_POST, "crit_{$i}", FILTER_VALIDATE_INT);
    if (!$val || $val < 1 || $val > 5) {
        $_SESSION['flash_error'] = 'Please complete all rating criteria before submitting.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
        exit;
    }
    $totalScore += $val;
}

$compositeScore = round($totalScore / 12, 2);

// 7. Database Insert Transaction
try {
    $pdo->beginTransaction();

    // Prevent duplicate DeptHead evaluation submissions for the same term
    $checkStmt = $pdo->prepare("
        SELECT evaluation_id 
        FROM evaluations 
        WHERE evaluator_id = :evaluator_id 
          AND faculty_id = :faculty_id 
          AND term_id = :term_id
          AND source_type = 'DeptHead' 
        LIMIT 1
    ");
    $checkStmt->execute([
        'evaluator_id' => $evaluatorId,
        'faculty_id'   => $targetFacultyId,
        'term_id'      => $termId
    ]);

    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'You have already submitted an evaluation for this faculty member.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
        exit;
    }

    // Insert Evaluation Record
    $insertEval = $pdo->prepare("
        INSERT INTO evaluations (evaluator_id, faculty_id, term_id, source_type, composite_score, submitted_at)
        VALUES (:evaluator_id, :faculty_id, :term_id, 'DeptHead', :composite_score, NOW())
    ");
    $insertEval->execute([
        'evaluator_id'    => $evaluatorId,
        'faculty_id'      => $targetFacultyId,
        'term_id'         => $termId,
        'composite_score' => $compositeScore
    ]);

    $evaluationId = $pdo->lastInsertId();

    // Insert optional comments into feedback table if provided
    if (!empty($remarks) && $evaluationId) {
        $insertFeedback = $pdo->prepare("
            INSERT INTO evaluation_feedback (evaluation_id, strength_comment)
            VALUES (:evaluation_id, :remarks)
        ");
        $insertFeedback->execute([
            'evaluation_id' => $evaluationId,
            'remarks'       => $remarks
        ]);
    }

    $pdo->commit();
    $_SESSION['flash_success'] = 'Department Head evaluation submitted successfully!';

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Error saving evaluation: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/modules/faculty/views/department-head/faculty-member-evaluation.php');
exit;