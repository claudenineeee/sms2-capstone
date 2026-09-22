<?php
/**
 * AI Insight Endpoint
 * POST JSON { scope: 'faculty'|'department', faculty_id?: int }
 * Returns   { ok, insight?, message?, cached? }
 *
 * Column names are matched to FacultyPerformanceController:
 *   - faculty.id, faculty.full_name
 *   - teaching_score  (labeled "Department Head" in the UI)
 *   - peer_score
 *   - student_score   (external service; may be NULL)
 *   - department_id   (scoping column)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/GeminiService.php';

requireAuth();
header('Content-Type: application/json');

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$raw   = file_get_contents('php://input');
$req   = json_decode($raw ?: '[]', true) ?: [];
$scope = $req['scope'] ?? '';

if (!in_array($scope, ['faculty', 'department'], true)) {
    respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 400);
}

try {
    $pdo = function_exists('facultyDb') ? facultyDb() : null;
    if (!$pdo) {
        respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 500);
    }

    // ---- Resolve head's department from session (same keys as controller) ----
    $deptId = (int) (
        $_SESSION['designated_department']
        ?? $_SESSION['department_id']
        ?? $_SESSION['user_department_id']
        ?? 1
    );
    $deptName = (string) (
        $_SESSION['department_name']
        ?? $_SESSION['user_department']
        ?? 'Your Department'
    );

    // ---- Helper: compute overall from available sources ----------------------
    // Your UI header shows the formula: 50% Student + 30% Peer + 20% Dept Head.
    // If a source is NULL, we renormalize the weights so we don't invent numbers.
    $computeOverall = function (?float $student, ?float $peer, ?float $deptHead): ?float {
        $w = 0.0; $sum = 0.0;
        if ($student  !== null) { $sum += $student  * 0.50; $w += 0.50; }
        if ($peer     !== null) { $sum += $peer     * 0.30; $w += 0.30; }
        if ($deptHead !== null) { $sum += $deptHead * 0.20; $w += 0.20; }
        if ($w <= 0) return null;
        return round($sum / $w, 2);
    };

    /* =========================================================
       SCOPE: FACULTY — one faculty member
       ========================================================= */
    if ($scope === 'faculty') {
        $facultyId = (int) ($req['faculty_id'] ?? 0);
        if ($facultyId <= 0) {
            respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 400);
        }

        $stmt = $pdo->prepare("
            SELECT id, full_name, teaching_score, peer_score, student_score
            FROM faculty
            WHERE id = :id AND department_id = :dept
            LIMIT 1
        ");
        $stmt->execute([':id' => $facultyId, ':dept' => $deptId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 404);
        }

        $deptHead = $row['teaching_score'] !== null ? (float) $row['teaching_score'] : null;
        $peer     = $row['peer_score']     !== null ? (float) $row['peer_score']     : null;
        $student  = $row['student_score']  !== null ? (float) $row['student_score']  : null;
        $overall  = $computeOverall($student, $peer, $deptHead);

        // Dept average (using same renormalization rule)
        $allStmt = $pdo->prepare("
            SELECT teaching_score, peer_score, student_score
            FROM faculty WHERE department_id = :dept
        ");
        $allStmt->execute([':dept' => $deptId]);
        $vals = [];
        foreach ($allStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $o = $computeOverall(
                $r['student_score']  !== null ? (float) $r['student_score']  : null,
                $r['peer_score']     !== null ? (float) $r['peer_score']     : null,
                $r['teaching_score'] !== null ? (float) $r['teaching_score'] : null
            );
            if ($o !== null) $vals[] = $o;
        }
        $deptAvg = $vals ? round(array_sum($vals) / count($vals), 2) : null;

        $dataHash = sha1(json_encode([
            $row['id'], $deptHead, $peer, $student, $deptAvg, $deptId,
        ]));

        if (empty($req['force'])) {
            $c = $pdo->prepare("
                SELECT insight_text FROM ai_insights_cache
                WHERE scope='faculty' AND faculty_id=:id AND data_hash=:h
                  AND created_at > (NOW() - INTERVAL 24 HOUR)
                LIMIT 1
            ");
            $c->execute([':id' => $facultyId, ':h' => $dataHash]);
            if ($cached = $c->fetchColumn()) {
                respond(['ok' => true, 'insight' => $cached, 'cached' => true]);
            }
        }

        $fmt = fn($v) => $v === null ? 'not available' : number_format((float)$v, 2);

        $prompt = <<<PROMPT
You are writing a performance insight for a Philippine college department head.
Write three short sections. Put the section name on its own line ending with a colon.
Blank line between sections. Do NOT use markdown symbols (no **, no #, no bullets).
Do not invent numbers. Do not mention AI, models, or GPT.

Sections in this exact order: Strengths, Areas to Improve, Recommended Action.

Data:
Faculty: {$row['full_name']}
Department Head rating (20% weight): {$fmt($deptHead)}
Peer-to-Peer rating (30% weight): {$fmt($peer)}
Student rating (50% weight, from external service): {$fmt($student)}
Computed overall (renormalized over available sources): {$fmt($overall)}
Department average: {$fmt($deptAvg)}
PROMPT;

        $ai = (new GeminiService())->generate($prompt, 500);
        if (!$ai['ok']) {
            respond(['ok' => false, 'message' => $ai['error']], 200);
        }
        $insight = $ai['text'];

        $ins = $pdo->prepare("
            INSERT INTO ai_insights_cache (scope, faculty_id, department, data_hash, insight_text, model)
            VALUES ('faculty', :id, :d, :h, :t, :m)
            ON DUPLICATE KEY UPDATE insight_text=VALUES(insight_text), created_at=NOW()
        ");
        $ins->execute([
            ':id' => $facultyId,
            ':d'  => (string) $deptId,
            ':h'  => $dataHash,
            ':t'  => $insight,
            ':m'  => ai_config('GEMINI_MODEL', 'gemini-2.5-flash'),
        ]);

        respond(['ok' => true, 'insight' => $insight, 'cached' => false]);
    }

    /* =========================================================
       SCOPE: DEPARTMENT — whole department summary
       ========================================================= */
    $stmt = $pdo->prepare("
        SELECT id, full_name, teaching_score, peer_score, student_score
        FROM faculty WHERE department_id = :dept
    ");
    $stmt->execute([':dept' => $deptId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 404);
    }

    $scores = [];
    $missingPeer = 0; $missingStudent = 0;
    foreach ($rows as $r) {
        $d = $r['teaching_score'] !== null ? (float) $r['teaching_score'] : null;
        $p = $r['peer_score']     !== null ? (float) $r['peer_score']     : null;
        $s = $r['student_score']  !== null ? (float) $r['student_score']  : null;
        if ($p === null) $missingPeer++;
        if ($s === null) $missingStudent++;
        $scores[] = [
            'name'    => $r['full_name'],
            'overall' => $computeOverall($s, $p, $d),
        ];
    }

    // Sort by overall desc, N/A last
    usort($scores, function($a, $b) {
        if ($a['overall'] === null && $b['overall'] === null) return 0;
        if ($a['overall'] === null) return 1;
        if ($b['overall'] === null) return -1;
        return $b['overall'] <=> $a['overall'];
    });

    $rated = array_values(array_filter($scores, fn($x) => $x['overall'] !== null));
    $avg   = $rated ? round(array_sum(array_column($rated, 'overall')) / count($rated), 2) : null;
    $top   = array_slice($rated, 0, 3);
    $low   = array_slice(array_reverse($rated), 0, 3);
    $below = count(array_filter($rated, fn($s) => $s['overall'] < 3.8));

    $dataHash = sha1(json_encode([$deptId, $scores]));

    if (empty($req['force'])) {
        $c = $pdo->prepare("
            SELECT insight_text FROM ai_insights_cache
            WHERE scope='department' AND department=:d AND data_hash=:h
              AND created_at > (NOW() - INTERVAL 24 HOUR)
            LIMIT 1
        ");
        $c->execute([':d' => (string) $deptId, ':h' => $dataHash]);
        if ($cached = $c->fetchColumn()) {
            respond(['ok' => true, 'insight' => $cached, 'cached' => true]);
        }
    }

    $fmtList = function(array $arr): string {
        if (!$arr) return 'none';
        return implode(', ', array_map(fn($x) => "{$x['name']} ({$x['overall']})", $arr));
    };

    $total   = count($scores);
    $topStr  = $fmtList($top);
    $lowStr  = $fmtList($low);
    $avgStr  = $avg === null ? 'not available' : $avg;

    $prompt = <<<PROMPT
You are summarizing department performance for a Philippine college department head.
Write three short sections. Put the section name on its own line ending with a colon.
Blank line between sections. Do NOT use markdown symbols (no **, no #, no bullets).
Do not invent numbers. Do not mention AI, models, or GPT.

Sections in this exact order: Overall State, Top and Bottom Performers, Key Concern and Recommendation.

Data:
Department: {$deptName}
Faculty count: {$total}
Rated faculty (have at least one score): {count($rated)}
Department average: {$avgStr}
Top performers: {$topStr}
Lowest performers: {$lowStr}
Faculty below 3.8: {$below}
Faculty missing peer evaluation: {$missingPeer}
Faculty missing student evaluation: {$missingStudent}
Note: student scores come from an external service and may be entirely absent.
PROMPT;

    $ai = (new GeminiService())->generate($prompt, 600);
    if (!$ai['ok']) {
        respond(['ok' => false, 'message' => $ai['error']], 200);
    }
    $insight = $ai['text'];

    $ins = $pdo->prepare("
        INSERT INTO ai_insights_cache (scope, faculty_id, department, data_hash, insight_text, model)
        VALUES ('department', NULL, :d, :h, :t, :m)
        ON DUPLICATE KEY UPDATE insight_text=VALUES(insight_text), created_at=NOW()
    ");
    $ins->execute([
        ':d' => (string) $deptId,
        ':h' => $dataHash,
        ':t' => $insight,
        ':m' => ai_config('GEMINI_MODEL', 'gemini-2.5-flash'),
    ]);

    respond(['ok' => true, 'insight' => $insight, 'cached' => false]);

} catch (Throwable $e) {
    error_log('ai-insight error: ' . $e->getMessage());
    respond(['ok' => false, 'message' => GeminiService::ERR_UNAVAILABLE], 500);
}