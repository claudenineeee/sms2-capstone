
<?php
/**
 * ProcessPeerEvaluationController
 * Action controller to handle Peer Evaluation submissions
 */
require_once __DIR__ . '/../../../config/config.php';

// NOTE: previously this called a bare session_start() here. Without first
// setting session_name('SMS2SESSID') (which config/session.php does), a bare
// session_start() creates/joins PHP's default session instead of the app's
// real one - so this controller would never see the logged-in user's
// session even after login. authentication.php pulls in config/session.php,
// which starts the correctly-named session for us.
require_once ROOT_PATH . '/includes/authentication.php';

// 1. Enforce POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

// 2. Database Connection Setup
//
// facultyDb() lives in modules/faculty/config/database.php - a SEPARATE
// file from the root config/database.php that authentication.php loads
// (which only connects to sms2_db). Nothing was requiring this file, so
// facultyDb() was always undefined here and the page silently fell through
// to the sms2_db connection via the "$conn ?? $db" guess below.
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

// 3. Resolve Evaluator's Faculty ID from Session
//
// IMPORTANT: The session stores users.id / users.email (from sms2_db), NOT a
// faculty.faculty_id. The evaluations table's FKs (evaluator_id, faculty_id)
// point at faculty.faculty_id, so we must bridge through faculty_profiles
// (matching on faculty_no/email) to get the real id.
//
// CONFIRMED against includes/authentication.php -> smsCompleteLoginSession():
// login sets $_SESSION['user_id']    = users.id
//            $_SESSION['user_email'] = users.email
//
// An earlier version of this file queried
// "faculty WHERE faculty_id = :sid OR user_id = :sid" - but the faculty
// table has no user_id column, so that query always threw and silently
// fell back to using the raw session id as evaluator_id, which does not
// correspond to any real faculty.faculty_id and breaks the FK constraint
// on insert.
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$sessionEmail  = $_SESSION['user_email'] ?? null;
$evaluatorId   = 0;

if ($sessionUserId || $sessionEmail) {
    try {
        $stmtEval = $pdo->prepare("
            SELECT f.faculty_id
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
            $evaluatorId = (int)$evalData['faculty_id'];
        }
    } catch (PDOException $e) {
        $evaluatorId = 0;
    }
}

if (!$evaluatorId) {
    $_SESSION['flash_error'] = 'Your account is not linked to an official faculty record, so an evaluation cannot be recorded under your name. Please contact the administrator.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

// 4. Input Validation
$targetFacultyId = filter_input(INPUT_POST, 'faculty_id', FILTER_VALIDATE_INT);
$remarks         = trim($_POST['remarks'] ?? '');

if (!$targetFacultyId) {
    $_SESSION['flash_error'] = 'Invalid faculty member selected.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

if ($targetFacultyId === $evaluatorId) {
    $_SESSION['flash_error'] = 'You cannot evaluate yourself.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

// Defensive check: confirm the target id is a real faculty.faculty_id before we
// attempt the insert (gives a friendly error instead of a raw FK violation).
try {
    $checkFaculty = $pdo->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = :fid LIMIT 1");
    $checkFaculty->execute(['fid' => $targetFacultyId]);
    if (!$checkFaculty->fetch()) {
        $_SESSION['flash_error'] = 'This colleague is not linked to an official faculty record and cannot be evaluated yet.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Unable to verify the selected faculty member: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

// 5. Resolve the Current Academic Term
// evaluations.term_id is NOT NULL with a foreign key to academic_terms - the
// previous version never set it at all, so every insert failed.
try {
    $termStmt = $pdo->query("SELECT term_id FROM academic_terms WHERE is_current = 1 LIMIT 1");
    $currentTerm = $termStmt->fetch();
} catch (PDOException $e) {
    $currentTerm = false;
}

if (!$currentTerm) {
    $_SESSION['flash_error'] = 'No active academic term is configured. Please ask the administrator to set one before submitting evaluations.';
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

$termId = (int)$currentTerm['term_id'];

// 6. Calculate Composite Rating (Scale 1-5 across 12 criteria)
$totalScore = 0;
for ($i = 1; $i <= 12; $i++) {
    $val = filter_input(INPUT_POST, "crit_{$i}", FILTER_VALIDATE_INT);
    if (!$val || $val < 1 || $val > 5) {
        $_SESSION['flash_error'] = 'Please complete all rating criteria before submitting.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
        exit;
    }
    $totalScore += $val;
}

$compositeScore = round($totalScore / 12, 2);

// 7. Database Insert Transaction
try {
    $pdo->beginTransaction();

    // Prevent duplicate peer evaluation submissions
    $checkStmt = $pdo->prepare("
        SELECT evaluation_id 
        FROM evaluations 
        WHERE evaluator_id = :evaluator_id 
          AND faculty_id = :faculty_id 
          AND term_id = :term_id
          AND source_type = 'Peer' 
        LIMIT 1
    ");
    $checkStmt->execute([
        'evaluator_id' => $evaluatorId,
        'faculty_id'   => $targetFacultyId,
        'term_id'      => $termId
    ]);

    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'You have already submitted an evaluation for this colleague.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
        exit;
    }

    // Insert Evaluation Record
    $insertEval = $pdo->prepare("
        INSERT INTO evaluations (evaluator_id, faculty_id, term_id, source_type, composite_score, submitted_at)
        VALUES (:evaluator_id, :faculty_id, :term_id, 'Peer', :composite_score, NOW())
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
    $_SESSION['flash_success'] = 'Peer evaluation submitted successfully!';

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Error saving evaluation: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
exit;