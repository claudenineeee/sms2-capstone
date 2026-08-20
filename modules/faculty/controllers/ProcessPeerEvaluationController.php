<?php
/**
 * ProcessPeerEvaluationController
 * Action controller to handle Peer Evaluation submissions
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';

// 1. Enforce POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
    exit;
}

// 2. Database Connection Setup
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
$sessionUserId = $_SESSION['faculty_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$evaluatorId   = 0;

if ($sessionUserId) {
    try {
        // Query faculty table to get the matching faculty record
        $stmtEval = $pdo->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = :sid OR user_id = :sid LIMIT 1");
        $stmtEval->execute(['sid' => $sessionUserId]);
        $evalData = $stmtEval->fetch();
        if ($evalData) {
            $evaluatorId = (int)$evalData['faculty_id'];
        } else {
            $evaluatorId = (int)$sessionUserId;
        }
    } catch (PDOException $e) {
        $evaluatorId = (int)$sessionUserId;
    }
}

if (!$evaluatorId) {
    $_SESSION['flash_error'] = 'Session expired or user profile not found. Please log in again.';
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

// 5. Calculate Composite Rating (Scale 1-5 across 12 criteria)
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

// 6. Database Insert Transaction
try {
    $pdo->beginTransaction();

    // Prevent duplicate peer evaluation submissions
    $checkStmt = $pdo->prepare("
        SELECT evaluation_id 
        FROM evaluations 
        WHERE evaluator_id = :evaluator_id 
          AND faculty_id = :faculty_id 
          AND source_type = 'Peer' 
        LIMIT 1
    ");
    $checkStmt->execute([
        'evaluator_id' => $evaluatorId,
        'faculty_id'   => $targetFacultyId
    ]);

    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'You have already submitted an evaluation for this colleague.';
        header('Location: ' . BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php');
        exit;
    }

    // Insert Evaluation Record
    $insertEval = $pdo->prepare("
        INSERT INTO evaluations (evaluator_id, faculty_id, source_type, composite_score, submitted_at)
        VALUES (:evaluator_id, :faculty_id, 'Peer', :composite_score, NOW())
    ");
    $insertEval->execute([
        'evaluator_id'    => $evaluatorId,
        'faculty_id'      => $targetFacultyId,
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