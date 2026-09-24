<?php
/**
 * AI Insight Endpoint
 * POST JSON { scope: 'faculty'|'department', faculty_id?: int, force?: bool }
 * Returns   { ok, insight?, message? }
 */
declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
$rootPath = dirname(__DIR__, 3);

require_once $rootPath . '/config/config.php';
require_once dirname(__DIR__) . '/config/ai.php';
require_once $rootPath . '/includes/authentication.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/services/GptAiService.php';

// ── Auth gate ─────────────────────────────────────────────────────────────────
requireAuth();
header('Content-Type: application/json');

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// ── Parse request ─────────────────────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$req   = json_decode($raw ?: '[]', true) ?: [];
$scope = $req['scope'] ?? '';

if (!in_array($scope, ['faculty', 'department'], true)) {
    respond(['ok' => false, 'message' => GptAiService::ERR_UNAVAILABLE], 400);
}

try {
    // ── Database ──────────────────────────────────────────────────────────────
    $pdo = facultyDb();
    if (!$pdo) {
        respond(['ok' => false, 'message' => 'Database connection failed. Please try again.'], 500);
    }

    // ── Resolve department from session ───────────────────────────────────────
    $deptId   = (string)($_SESSION['designated_department'] ?? $_SESSION['department_name'] ?? $_SESSION['user_department'] ?? '');
    $deptName = $deptId ?: 'Your Department';

    // ── Shared SQL mirroring FacultyModel::fetchPerformanceRows() ─────────────
    $baseSql = "
        SELECT
            fp.id,
            CONCAT(fp.first_name, ' ', fp.last_name) AS full_name,
            AVG(CASE WHEN e.source_type = 'Student'  THEN e.composite_score END) AS student_score,
            AVG(CASE WHEN e.source_type = 'Peer'     THEN e.composite_score END) AS peer_score,
            AVG(CASE WHEN e.source_type = 'DeptHead' THEN e.composite_score END) AS teaching_score
        FROM faculty_db.faculty_profiles fp
        LEFT JOIN faculty_db.faculty f ON f.faculty_id = (
            SELECT f2.faculty_id
            FROM faculty_db.faculty f2
            WHERE (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email)
               OR f2.faculty_no = fp.faculty_id
            ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email) DESC
            LIMIT 1
        )
        LEFT JOIN faculty_db.evaluations e ON e.faculty_id = f.faculty_id
    ";

    // ── Dept WHERE clause (string match, same as FacultyModel) ───────────────
    $buildDeptWhere = function (string $dept, array &$params): string {
        if ($dept === '') return '';
        $params[':dept']  = $dept;
        $params[':dept2'] = $dept;
        return " AND (LOWER(TRIM(fp.designated_department)) = LOWER(TRIM(:dept)) OR fp.designated_department = :dept2) ";
    };

    // ── Weighted overall calculator ───────────────────────────────────────────
    $computeOverall = function (?float $s, ?float $p, ?float $d): ?float {
        $w = 0.0; $sum = 0.0;
        if ($s !== null) { $sum += $s * 0.50; $w += 0.50; }
        if ($p !== null) { $sum += $p * 0.30; $w += 0.30; }
        if ($d !== null) { $sum += $d * 0.20; $w += 0.20; }
        return $w > 0 ? round($sum / $w, 2) : null;
    };

    $fmt = fn($v) => $v === null ? 'not available' : number_format((float)$v, 2);

    /* =========================================================
       SCOPE: FACULTY — one faculty member
       ========================================================= */
    if ($scope === 'faculty') {
        $facultyId = (int)($req['faculty_id'] ?? 0);
        if ($facultyId <= 0) {
            respond(['ok' => false, 'message' => 'Invalid faculty ID.'], 400);
        }

        $params = [':id' => $facultyId];
        $deptWhere = $buildDeptWhere($deptId, $params);

        $stmt = $pdo->prepare($baseSql . " WHERE fp.id = :id $deptWhere GROUP BY fp.id, fp.first_name, fp.last_name LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            respond(['ok' => false, 'message' => 'Faculty member not found.'], 404);
        }

        $d       = $row['teaching_score'] !== null ? (float)$row['teaching_score'] : null;
        $p       = $row['peer_score']     !== null ? (float)$row['peer_score']     : null;
        $s       = $row['student_score']  !== null ? (float)$row['student_score']  : null;
        $overall = $computeOverall($s, $p, $d);

        // Dept average
        $deptParams = [];
        $deptWhere2 = $buildDeptWhere($deptId, $deptParams);
        $allStmt = $pdo->prepare($baseSql . ($deptWhere2 ? "WHERE 1=1 $deptWhere2" : '') . " GROUP BY fp.id");
        $allStmt->execute($deptParams);
        $vals = [];
        foreach ($allStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $o = $computeOverall(
                $r['student_score']  !== null ? (float)$r['student_score']  : null,
                $r['peer_score']     !== null ? (float)$r['peer_score']     : null,
                $r['teaching_score'] !== null ? (float)$r['teaching_score'] : null
            );
            if ($o !== null) $vals[] = $o;
        }
        $deptAvg = $vals ? round(array_sum($vals) / count($vals), 2) : null;

        // Missing sources list
        $missing = [];
        if ($d === null) $missing[] = 'Department Head';
        if ($p === null) $missing[] = 'Peer-to-Peer';
        if ($s === null) $missing[] = 'Student';
        $missingStr = $missing ? implode(', ', $missing) : 'none';

        $name         = $row['full_name'];
        $overall_fmt  = $fmt($overall);
        $deptHead_fmt = $fmt($d);
        $peer_fmt     = $fmt($p);
        $student_fmt  = $fmt($s);
        $deptAvg_fmt  = $fmt($deptAvg);

        $prompt = <<<PROMPT
You are an academic performance analyst writing a formal report for a department head.

STRICT RULES:
- Do NOT mention any school, college, university, or institution name.
- Do NOT mention AI, models, or tools.
- Do NOT invent numbers. Only use the values provided.
- Do NOT use markdown symbols, bullets, or emojis.
- Write in complete sentences. No fragments.

Write exactly three sections. Each section must be 2 to 3 full sentences (roughly 40 to 60 words per section).
Put the section name on its own line, ending with a colon. Leave one blank line between sections.

Section 1 — Strengths:
Discuss what this faculty member does well, citing their strongest scores and comparing them to the department average. Be specific and analytical.

Section 2 — Areas to Improve:
Discuss weaker scores, incomplete evaluation sources, and anything that needs attention. Explain why the missing data matters for a fair evaluation.

Section 3 — Recommended Action:
Give 3 concrete, actionable steps the department head should take (for example: schedule peer observation, request student evaluations, nominate for mentoring, recognize strength, reassign duties). Each step must be specific.

Data:
Faculty: {$name}
Overall (weighted): {$overall_fmt}
Department Head rating (20% weight): {$deptHead_fmt}
Peer-to-Peer rating (30% weight): {$peer_fmt}
Student rating (50% weight): {$student_fmt}
Department average: {$deptAvg_fmt}
Missing evaluation sources: {$missingStr}
PROMPT;

        $ai = (new GptAiService())->generate($prompt, 2048);
        if (!$ai['ok']) {
            respond(['ok' => false, 'message' => $ai['error']], 200);
        }
        respond(['ok' => true, 'insight' => $ai['text']]);
    }

    /* =========================================================
       SCOPE: DEPARTMENT — whole department summary
       ========================================================= */
    $deptParams = [];
    $deptWhere  = $buildDeptWhere($deptId, $deptParams);

    $stmt = $pdo->prepare($baseSql . ($deptWhere ? "WHERE 1=1 $deptWhere" : '') . " GROUP BY fp.id, fp.first_name, fp.last_name ORDER BY fp.last_name ASC, fp.first_name ASC");
    $stmt->execute($deptParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        respond(['ok' => false, 'message' => 'No faculty data found for your department.'], 404);
    }

    $scores = [];
    $missingPeer = 0; $missingStudent = 0;
    foreach ($rows as $r) {
        $d = $r['teaching_score'] !== null ? (float)$r['teaching_score'] : null;
        $p = $r['peer_score']     !== null ? (float)$r['peer_score']     : null;
        $s = $r['student_score']  !== null ? (float)$r['student_score']  : null;
        if ($p === null) $missingPeer++;
        if ($s === null) $missingStudent++;
        $scores[] = ['name' => $r['full_name'], 'overall' => $computeOverall($s, $p, $d)];
    }

    usort($scores, function ($a, $b) {
        if ($a['overall'] === null && $b['overall'] === null) return 0;
        if ($a['overall'] === null) return 1;
        if ($b['overall'] === null) return -1;
        return $b['overall'] <=> $a['overall'];
    });

    $rated   = array_values(array_filter($scores, fn($x) => $x['overall'] !== null));
    $avg     = $rated ? round(array_sum(array_column($rated, 'overall')) / count($rated), 2) : null;
    $top3    = array_slice($rated, 0, 3);
    $bottom3 = array_slice(array_reverse($rated), 0, 3);
    $below   = count(array_filter($rated, fn($x) => $x['overall'] < 3.8));

    $fmtList = fn(array $arr) => $arr
        ? implode(', ', array_map(fn($x) => $x['name'] . ' (' . $x['overall'] . ')', $arr))
        : 'none';

    $total    = count($scores);
    $ratedCt  = count($rated);
    $avgStr   = $avg === null ? 'not available' : (string)$avg;
    $top3Str  = $ratedCt < 6 ? "fewer than 6 rated faculty ({$ratedCt} total)" : $fmtList($top3);
    $bot3Str  = $ratedCt < 6 ? 'not enough rated faculty for a bottom list' : $fmtList($bottom3);

    $prompt = <<<PROMPT
You are an academic performance analyst writing a formal department-wide report for a department head.

STRICT RULES:
- Do NOT mention any school, college, university, or institution name.
- Do NOT mention AI, models, or tools.
- Do NOT invent numbers. Only use the values provided.
- Do NOT use markdown symbols, bullets, or emojis.
- Write in complete sentences. No fragments.

Write exactly three sections. Each section must be 4 to 6 full sentences (roughly 90 to 140 words per section). Be analytical, not just descriptive.

Section 1 — Overall State:
Describe the department's overall performance. Discuss the average score, how many faculty are rated above the 4.5 threshold, how many fall below 3.8, and what the distribution suggests. Also comment on the completeness of the evaluation data.

Section 2 — Top and Bottom Performers:
Name the top 3 faculty by name and score, and explain what distinguishes them. Then name the bottom 3 and clearly separate those who genuinely scored low from those whose scores are missing or incomplete. Do not treat missing data as poor performance.

Section 3 — Key Concern and Recommendation:
Identify the single most important issue this period (missing evaluations, low scores, data quality, etc.). Then give 4 concrete recommendations with clear next steps for the department head.

Data:
Department: {$deptName}
Total faculty: {$total}
Rated faculty (at least one score): {$ratedCt}
Department average: {$avgStr}
Top 3: {$top3Str}
Bottom 3: {$bot3Str}
Faculty below 3.8: {$below}
Faculty missing peer evaluation: {$missingPeer}
Faculty missing student evaluation: {$missingStudent}
Note: student scores come from an external source and may be entirely absent.
PROMPT;

    $ai = (new GptAiService())->generate($prompt, 4096);
    if (!$ai['ok']) {
        respond(['ok' => false, 'message' => $ai['error']], 200);
    }
    respond(['ok' => true, 'insight' => $ai['text']]);

} catch (Throwable $e) {
    error_log('ai-insight error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    respond(['ok' => false, 'message' => GptAiService::ERR_UNAVAILABLE], 500);
}