<?php
/**
 * SMS 2 - Evaluation Summary (Dean)
 * Faculty Professor evaluation summary, RBAC-scoped to the dean's departments.
 * Composite weighting: 50% Student / 30% Peer / 20% Dept-Head (Dept-Head source
 * still contributes to the composite if present, but is NOT exposed as a tab).
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../includes/dean_rbac.php';

requireAuth();

/* ============================================================
   DEAN RBAC
   ============================================================ */
$deanUserId          = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);
$deanDepartments     = getDeanAssignedDepartments($deanUserId);
$deanDepartmentNames = getDeanDepartmentNames($deanDepartments);
$hasDeanDepartments  = !empty($deanDepartments);

/* ============================================================
   Positions that count as "teaching faculty"
   ============================================================ */
$teachingPositions = ['Faculty Professor'];

/* ============================================================
   DATABASE
   ============================================================ */
$pdo = null;
if (function_exists('facultyDb')) { $pdo = facultyDb(); }
if (!$pdo instanceof PDO && function_exists('db')) {
    try { $pdo = db(); } catch (Throwable $e) { $pdo = null; }
}
if (!$pdo instanceof PDO && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
}

/* ============================================================
   LOAD FACULTY + THEIR EVALUATIONS
   ============================================================ */
$facultyMembers       = [];
$performanceDB        = [];
$totalFacultyCount    = 0;
$fullyEvaluatedCount  = 0;
$deptOverallAverage   = 0;
$deptStudentAverage   = 0;
$deptPeerAverage      = 0;
$deptHeadAverage      = 0;
$deptStudentScores    = [];
$deptPeerScores       = [];
$deptHeadScores       = [];
$deptPeerRatedCount   = 0;
$evaluatedFacultyCount= 0;
$topPerformer         = null;
$topPerformerScore    = 0;
$needsAttentionList   = [];
$needsAttentionCount  = 0;
$loadError            = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        /* ---- Faculty roster — only Faculty Professors ---- */
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
                f.email,
                f.department_id,
                f.position,
                f.profile_status,
                d.code AS dept_code,
                d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyMembers    = $facStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalFacultyCount = count($facultyMembers);

        $evalsByFaculty = [];
        $feedbackByEval = [];

        if (!empty($facultyMembers)) {
            $facultyIds    = array_map(fn($f) => (int) $f['faculty_id'], $facultyMembers);
            $facultyIdList = implode(',', $facultyIds);

            $evalStmt = $pdo->query("
                SELECT
                    e.evaluation_id,
                    e.faculty_id,
                    e.source_type,
                    e.composite_score,
                    e.rating_label,
                    e.eval_count,
                    e.submitted_at
                FROM faculty_db.evaluations e
                WHERE e.faculty_id IN ($facultyIdList)
                  AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY e.submitted_at DESC
            ");
            $evalIds = [];
            foreach ($evalStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $fid = (int) $r['faculty_id'];
                if (!isset($evalsByFaculty[$fid])) $evalsByFaculty[$fid] = [];
                $evalsByFaculty[$fid][] = $r;
                $evalIds[]              = (int) $r['evaluation_id'];
            }

            if (!empty($evalIds)) {
                $evalIdList = implode(',', $evalIds);
                $fbStmt = $pdo->query("
                    SELECT evaluation_id, strength_comment, improvement_comment
                    FROM faculty_db.evaluation_feedback
                    WHERE evaluation_id IN ($evalIdList)
                ");
                foreach ($fbStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $feedbackByEval[(int) $r['evaluation_id']] = $r;
                }
            }
        }

        /* ---- Rating label helper ---- */
        $ratingLabel = function (float $score): string {
            if ($score >= 4.50) return 'Outstanding';
            if ($score >= 3.50) return 'Very Satisfactory';
            if ($score >= 2.50) return 'Satisfactory';
            if ($score >= 1.50) return 'Average';
            if ($score >  0)    return 'Needs Improvement';
            return 'No Rating';
        };

        foreach ($facultyMembers as $fac) {
            $fid      = (int) $fac['faculty_id'];
            $fullName = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
            $initials = strtoupper(
                substr($fac['first_name'] ?? '', 0, 1) .
                substr($fac['last_name'] ?? '', 0, 1)
            );

            $rows = $evalsByFaculty[$fid] ?? [];

            $bySource = [
                'Student'  => ['sum' => 0, 'count' => 0, 'evals' => 0, 'feedback' => []],
                'Peer'     => ['sum' => 0, 'count' => 0, 'evals' => 0, 'feedback' => []],
                'DeptHead' => ['sum' => 0, 'count' => 0, 'evals' => 0, 'feedback' => []],
            ];

            foreach ($rows as $r) {
                $src = $r['source_type'];
                if (!isset($bySource[$src])) continue;

                $score = (float) $r['composite_score'];
                $bySource[$src]['sum']   += $score;
                $bySource[$src]['count'] += 1;
                $bySource[$src]['evals'] += (int) $r['eval_count'];

                if (isset($feedbackByEval[(int) $r['evaluation_id']])) {
                    $fb = $feedbackByEval[(int) $r['evaluation_id']];
                    if (!empty($fb['strength_comment'])) {
                        $bySource[$src]['feedback'][] = ['strong' => $fb['strength_comment']];
                    }
                }
            }

            $studentAvg = $bySource['Student']['count']  > 0 ? $bySource['Student']['sum']  / $bySource['Student']['count']  : 0;
            $peerAvg    = $bySource['Peer']['count']     > 0 ? $bySource['Peer']['sum']     / $bySource['Peer']['count']     : 0;
            $headAvg    = $bySource['DeptHead']['count'] > 0 ? $bySource['DeptHead']['sum'] / $bySource['DeptHead']['count'] : 0;

            $hasStudent = $bySource['Student']['count']  > 0;
            $hasPeer    = $bySource['Peer']['count']     > 0;
            $hasHead    = $bySource['DeptHead']['count'] > 0;

            // 50/30/20 — renormalized over available sources
            $weightSum = 0;
            $composite = 0;
            if ($hasStudent) { $composite += $studentAvg * 0.50; $weightSum += 0.50; }
            if ($hasPeer)    { $composite += $peerAvg    * 0.30; $weightSum += 0.30; }
            if ($hasHead)    { $composite += $headAvg    * 0.20; $weightSum += 0.20; }
            $composite = $weightSum > 0 ? $composite / $weightSum : 0;

            $totalEvals     = $bySource['Student']['count'] + $bySource['Peer']['count'] + $bySource['DeptHead']['count'];
            $fullyEvaluated = $hasStudent && $hasPeer && $hasHead;

            $performanceDB[$fid] = [
                'id'         => $fid,
                'name'       => $fullName ?: '—',
                'initials'   => $initials ?: '—',
                'position'   => $fac['position'] ?: 'Faculty Professor',
                'department' => $fac['dept_name'] ?: ($fac['dept_code'] ?: '—'),
                'faculty_no' => $fac['faculty_no'] ?? '',
                'isLinked'   => true,
                'compositeScore'  => number_format($composite, 2),
                'compositeRating' => $ratingLabel($composite),
                'totalEvals'      => $totalEvals,
                'fullyEvaluated'  => $fullyEvaluated,
                'sources' => [
                    'student' => [
                        'score'      => number_format($studentAvg, 2),
                        'ratingText' => $ratingLabel($studentAvg),
                        'evalCount'  => $bySource['Student']['evals'],
                        'feedback'   => $bySource['Student']['feedback'],
                    ],
                    'peer' => [
                        'score'      => number_format($peerAvg, 2),
                        'ratingText' => $ratingLabel($peerAvg),
                        'evalCount'  => $bySource['Peer']['evals'],
                        'feedback'   => $bySource['Peer']['feedback'],
                    ],
                    'head' => [
                        'score'      => number_format($headAvg, 2),
                        'ratingText' => $ratingLabel($headAvg),
                        'evalCount'  => $bySource['DeptHead']['evals'],
                        'feedback'   => $bySource['DeptHead']['feedback'],
                    ],
                ],
            ];

            if ($composite > 0) { $deptOverallAverage += $composite; $evaluatedFacultyCount++; }
            if ($hasStudent) $deptStudentScores[] = $studentAvg;
            if ($hasPeer)    $deptPeerScores[]    = $peerAvg;
            if ($hasHead)    $deptHeadScores[]    = $headAvg;
            if ($hasPeer)    $deptPeerRatedCount++;
            if ($fullyEvaluated) $fullyEvaluatedCount++;

            if ($composite > 0 && $composite < 3.50) {
                $needsAttentionList[] = ['name' => $fullName, 'score' => $composite];
            }
        }

        if ($evaluatedFacultyCount > 0) {
            $deptOverallAverage = $deptOverallAverage / $evaluatedFacultyCount;
        }
        $deptStudentAverage = !empty($deptStudentScores) ? array_sum($deptStudentScores) / count($deptStudentScores) : 0;
        $deptPeerAverage    = !empty($deptPeerScores)    ? array_sum($deptPeerScores)    / count($deptPeerScores)    : 0;
        $deptHeadAverage    = !empty($deptHeadScores)    ? array_sum($deptHeadScores)    / count($deptHeadScores)    : 0;

        foreach ($performanceDB as $p) {
            $score = (float) $p['compositeScore'];
            if ($score > $topPerformerScore) {
                $topPerformerScore = $score;
                $topPerformer      = $p;
            }
        }
        $needsAttentionCount = count($needsAttentionList);
        usort($needsAttentionList, fn($a, $b) => $a['score'] <=> $b['score']);

    } catch (Throwable $e) {
        error_log('Evaluation summary load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Evaluation Summary';
$activeModule = 'faculty';
$activePage   = 'evaluation-summary';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Evaluation Summary', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="esPage">

    <?php if (!$hasDeanDepartments): ?>
        <div class="card border shadow-sm">
            <div class="card-body text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:84px;height:84px;background:rgba(100,116,139,0.10);color:#64748b;font-size:2.2rem;">
                    <i class="fas fa-building-circle-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>
                <p class="text-body-secondary mb-0" style="max-width:420px;margin:0 auto;">
                    Your account doesn't have any department access yet. Contact your administrator
                    to assign you one or more departments.
                </p>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../../../includes/layout-end.php'; exit; ?>
    <?php endif; ?>

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Evaluation Summary Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-chart-pie text-primary"></i>
                <span>Evaluation Summary</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Faculty Professor evaluation summary for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="exportCsvBtn">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">CSV</span>
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="printBtn">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= Department Overview KPI Cards ================= -->
    <div class="row g-3 mb-3 no-print">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0dcaf0;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#0dcaf0;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Fully Evaluated</h6>
                        <h4 class="mb-0 fw-bold" id="kpiFully">
                            <?= (int) $fullyEvaluatedCount ?> <span class="text-muted fs-6 fw-normal">/ <?= (int) $totalFacultyCount ?></span>
                        </h4>
                        <small class="text-muted fw-semibold" style="font-size:0.75rem;">All 3 sources complete</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#0d6efd;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">College Overall Avg</h6>
                        <?php if ($evaluatedFacultyCount > 0): ?>
                            <h4 class="mb-0 fw-bold"><?= number_format($deptOverallAverage, 2) ?> <span class="text-muted fs-6 fw-normal">/ 5.00</span></h4>
                            <small class="text-success fw-semibold" style="font-size:0.75rem;">
                                <?php
                                    $lbl = 'No Rating';
                                    if ($deptOverallAverage >= 4.50) $lbl = 'Outstanding';
                                    elseif ($deptOverallAverage >= 3.50) $lbl = 'Very Satisfactory';
                                    elseif ($deptOverallAverage >= 2.50) $lbl = 'Satisfactory';
                                    elseif ($deptOverallAverage >= 1.50) $lbl = 'Average';
                                    elseif ($deptOverallAverage > 0)     $lbl = 'Needs Improvement';
                                    echo htmlspecialchars($lbl);
                                ?>
                            </small>
                        <?php else: ?>
                            <h4 class="mb-0 fw-bold">--</h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">No data yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#10b981;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="text-truncate">
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Top Performer</h6>
                        <?php if ($topPerformer): ?>
                            <h4 class="mb-0 fw-bold text-truncate es-privacy-target" style="max-width:170px;font-size:1.15rem;" title="<?= htmlspecialchars($topPerformer['name']) ?>">
                                <?= htmlspecialchars($topPerformer['name']) ?>
                            </h4>
                            <small class="text-success fw-semibold" style="font-size:0.75rem;">
                                <i class="fas fa-star me-1"></i><?= number_format($topPerformerScore, 2) ?> / 5.00
                            </small>
                        <?php else: ?>
                            <h4 class="mb-0 fw-bold">--</h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">No data yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#ff4d4d;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#ff4d4d;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Needs Attention</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;"><?= (int) $needsAttentionCount ?></h4>
                        <small class="fw-semibold" style="color:#ff4d4d;font-size:0.75rem;">Below 3.50 overall</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Per-Source Comparison ================= -->
    <div class="row g-3 mb-3 no-print">
        <div class="col-12 col-md-6">
            <section class="card stat-card info border shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0dcaf0;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#0dcaf0;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Student Avg <span class="fw-normal">(50%)</span></h6>
                        <?php if (!empty($deptStudentScores)): ?>
                            <h4 class="mb-0 fw-bold"><?= number_format($deptStudentAverage, 2) ?> <span class="text-muted fs-6 fw-normal">/ 5.00</span></h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">based on <?= count($deptStudentScores) ?> rated faculty</small>
                        <?php else: ?>
                            <h4 class="mb-0 fw-bold">--</h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">No ratings yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-md-6">
            <section class="card stat-card warning border shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color:#f59e0b;">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Peer Avg <span class="fw-normal">(30%)</span></h6>
                        <?php if ($deptPeerRatedCount > 0): ?>
                            <h4 class="mb-0 fw-bold"><?= number_format($deptPeerAverage, 2) ?> <span class="text-muted fs-6 fw-normal">/ 5.00</span></h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">based on <?= $deptPeerRatedCount ?> rated faculty</small>
                        <?php else: ?>
                            <h4 class="mb-0 fw-bold">--</h4>
                            <small class="text-muted fw-semibold" style="font-size:0.75rem;">No ratings yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php if ($needsAttentionCount > 0): ?>
    <div class="alert alert-warning border-warning-subtle bg-warning-subtle text-warning-emphasis d-flex align-items-start gap-2 mb-3 no-print" role="alert">
        <i class="fas fa-triangle-exclamation fs-5 flex-shrink-0 mt-1"></i>
        <div class="small">
            <strong><?= $needsAttentionCount ?> faculty member<?= $needsAttentionCount === 1 ? '' : 's' ?> trending below 3.50 overall:</strong>
            <?= htmlspecialchars(implode(', ', array_map(fn($n) => $n['name'] . ' (' . number_format($n['score'], 2) . ')', array_slice($needsAttentionList, 0, 6)))) ?><?= $needsAttentionCount > 6 ? ', and ' . ($needsAttentionCount - 6) . ' more' : '' ?>.
        </div>
    </div>
    <?php endif; ?>

    <!-- ================= Two-Column Layout ================= -->
    <div class="row g-3 align-items-start">

        <!-- LEFT: Faculty list -->
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="card border shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-bold">Faculty Professors</h5>
                    <span class="badge text-bg-light border" id="facultyCountBadge"><?= count($facultyMembers) ?></span>
                </div>
                <div class="card-body py-3 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body text-body-secondary border-light-subtle">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="facultySearchInput" class="form-control bg-body text-body border-light-subtle shadow-none"
                               placeholder="Search faculty…" autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-2 es-faculty-scroll">
                    <div class="d-flex flex-column gap-2" id="facultyListContainer">
                        <?php if (empty($facultyMembers)): ?>
                            <div class="text-center py-5 text-body-secondary">
                                <i class="fas fa-user-slash d-block mb-2" style="font-size:1.8rem;opacity:0.4;"></i>
                                <span class="small">No Faculty Professors in your department.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($facultyMembers as $fac): ?>
                                <?php
                                    $fId      = (int) $fac['faculty_id'];
                                    $fName    = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
                                    $initials = strtoupper(
                                        substr($fac['first_name'] ?? '', 0, 1) .
                                        substr($fac['last_name'] ?? '', 0, 1)
                                    );
                                    $deptName = $fac['dept_name'] ?: ($fac['dept_code'] ?: '—');
                                ?>
                                <div class="es-faculty-card"
                                     data-name="<?= htmlspecialchars(strtolower($fName), ENT_QUOTES, 'UTF-8') ?>"
                                     data-faculty-id="<?= $fId ?>">
                                    <div class="es-avatar"><?= htmlspecialchars($initials ?: '—') ?></div>
                                    <div class="es-faculty-info">
                                        <div class="es-faculty-name es-privacy-target" title="<?= htmlspecialchars($fName) ?>">
                                            <?= htmlspecialchars($fName ?: '—') ?>
                                        </div>
                                        <div class="es-faculty-meta">
                                            <?= htmlspecialchars($fac['position'] ?? 'Faculty Professor') ?>
                                            <span class="es-dot">·</span>
                                            <?= htmlspecialchars($deptName) ?>
                                        </div>
                                    </div>
                                    <span class="es-faculty-chevron" aria-hidden="true">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-2">
                    <small class="text-body-secondary" id="paginationInfo">Showing 0</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1 es-pagination" id="paginationList"></ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- RIGHT: Evaluation detail -->
        <div class="col-12 col-lg-8 col-xl-9">
            <!-- Header card -->
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="d-flex flex-row align-items-start justify-content-between gap-2 flex-wrap">
                        <div class="d-flex align-items-start gap-3" style="min-width:0;flex:1;">
                            <div id="profAvatar" class="es-avatar-lg">--</div>
                            <div style="min-width:0;flex:1;">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="mb-0 fw-bold">Faculty Multi-Source Summary</h5>
                                    <span class="badge text-bg-light border">Last 12 months</span>
                                </div>
                                <div class="text-body-secondary small">
                                    <span>Faculty: <strong class="text-body-emphasis es-privacy-target" id="profName">—</strong></span>
                                    <span class="mx-1">·</span>
                                    <span>Position: <strong class="text-body-emphasis" id="profPosition">—</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs (Composite / Student / Peer only) -->
            <div class="mb-3 border-bottom overflow-x-auto text-nowrap es-tabs-scroll">
                <ul class="nav nav-tabs border-0 flex-nowrap" id="evalSourceTabs">
                    <li class="nav-item flex-shrink-0">
                        <button class="nav-link active fw-bold py-2 px-3 small" data-tab="all" type="button">
                            <i class="fas fa-chart-pie me-1 text-primary"></i> Composite
                        </button>
                    </li>
                    <li class="nav-item flex-shrink-0">
                        <button class="nav-link text-secondary py-2 px-3 small" data-tab="student" type="button">
                            <i class="fas fa-user-graduate me-1 text-info"></i> Student (50%)
                        </button>
                    </li>
                    <li class="nav-item flex-shrink-0">
                        <button class="nav-link text-secondary py-2 px-3 small" data-tab="peer" type="button">
                            <i class="fas fa-user-friends me-1 text-warning"></i> Peer (30%)
                        </button>
                    </li>
                </ul>
            </div>

            <div class="row g-3">
                <!-- Score Breakdown Sidebar -->
                <div class="col-12 col-md-5 col-xl-4">
                    <div class="card border shadow-sm mb-3">
                        <div class="card-body p-3 p-sm-4 text-center">
                            <small class="text-uppercase text-muted fw-bold" style="font-size:10px;letter-spacing:0.8px;" id="scoreCardTitle">Composite Rating</small>

                            <div class="d-flex align-items-baseline justify-content-center gap-1 my-2">
                                <span class="display-5 fw-bold" id="profScore">0.00</span>
                                <span class="text-muted fs-6">/ 5.00</span>
                            </div>

                            <div class="p-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25 mb-3">
                                <span class="text-success fw-bold small" id="profRatingLabel">No Rating</span>
                            </div>

                            <div class="row g-2 pt-2 border-top border-secondary border-opacity-25">
                                <div class="col-12">
                                    <div class="p-2 rounded border border-secondary border-opacity-25">
                                        <span class="d-block text-muted text-uppercase" style="font-size:10px;">Total Evaluations</span>
                                        <span class="fw-bold fs-6" id="profCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border shadow-sm mb-3" id="sourceBreakdownCard">
                        <div class="card-header border-bottom border-secondary border-opacity-25 py-2">
                            <h6 class="mb-0 fw-bold small text-uppercase" style="font-size:11px;">Breakdown Weight</h6>
                        </div>
                        <div class="card-body p-3 small" style="font-size:11px;">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-graduate me-1 text-info"></i> Student (50%)</span>
                                    <span class="fw-bold" id="scoreStudentWeight">0.00</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-friends me-1 text-warning"></i> Peer (30%)</span>
                                    <span class="fw-bold" id="scorePeerWeight">0.00</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tie me-1 text-primary"></i> Dept. Head (20%)</span>
                                    <span class="fw-bold" id="scoreHeadWeight">0.00</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card border shadow-sm">
                        <div class="card-header border-bottom border-secondary border-opacity-25 py-2">
                            <h6 class="mb-0 fw-bold small text-uppercase" style="font-size:11px;">Rating Scale</h6>
                        </div>
                        <div class="card-body p-3 small" style="font-size:11px;">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                                <li class="d-flex justify-content-between"><span class="fw-bold text-success">5 - Outstanding</span> <span>4.50 - 5.00</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-primary">4 - Very Satisfactory</span> <span>3.50 - 4.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-info">3 - Satisfactory</span> <span>2.50 - 3.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-warning">2 - Average</span> <span>1.50 - 2.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-danger">1 - Needs Improvement</span> <span>1.00 - 1.49</span></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feedback panel -->
                <div class="col-12 col-md-7 col-xl-8">
                    <div class="card border shadow-sm">
                        <div class="card-header border-bottom border-secondary border-opacity-25 py-3">
                            <h6 class="mb-0 fw-bold small text-uppercase" id="feedbackHeaderTitle">Qualitative Feedback &amp; Remarks</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive es-feedback-scroll">
                                <table class="table table-hover align-middle mb-0 small" style="font-size:12px;">
                                    <thead class="table-light text-muted text-uppercase">
                                        <tr><th class="ps-3 py-2 w-100">Feedback Comments</th></tr>
                                    </thead>
                                    <tbody id="commentsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="es-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* ============================================================
       Faculty list scroll container
       ============================================================ */
    .es-faculty-scroll {
        max-height: 480px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(13,110,253,0.3) transparent;
    }
    .es-faculty-scroll::-webkit-scrollbar { width: 6px; }
    .es-faculty-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,0.3);
        border-radius: 10px;
    }

    /* ============================================================
       Redesigned faculty rows — avatar + name + meta + chevron
       ============================================================ */
    .es-faculty-card {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.6rem 0.75rem;
        background: var(--sms-surface-muted);
        border: 1px solid var(--sms-border-soft);
        border-left: 3px solid transparent;
        border-radius: 12px;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }
    .es-faculty-card:hover {
        background: rgba(59,130,246,0.05);
        border-color: rgba(59,130,246,0.30);
        border-left-color: rgba(59,130,246,0.55);
        transform: translateY(-1px);
    }
    .es-faculty-card.es-active {
        background: rgba(59,130,246,0.08);
        border-color: rgba(59,130,246,0.45);
        border-left-color: #3b82f6;
    }
    .es-faculty-card.es-active .es-faculty-chevron { color: #3b82f6; }

    .es-avatar {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.78rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
        letter-spacing: 0.02em;
    }
    .es-faculty-card.es-active .es-avatar {
        background: #3b82f6;
        color: #fff;
    }

    .es-faculty-info { min-width: 0; flex: 1; }
    .es-faculty-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--sms-heading);
        line-height: 1.25;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .es-faculty-meta {
        font-size: 0.68rem;
        color: var(--sms-text-muted);
        line-height: 1.3;
        margin-top: 1px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .es-dot { opacity: 0.5; margin: 0 2px; }

    .es-faculty-chevron {
        color: var(--sms-text-muted);
        font-size: 0.72rem;
        flex-shrink: 0;
        transition: color .15s ease, transform .15s ease;
    }
    .es-faculty-card:hover .es-faculty-chevron { transform: translateX(2px); }

    /* Large avatar in the detail header */
    .es-avatar-lg {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.95rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }

    .es-tabs-scroll { scrollbar-width: thin; }
    .es-feedback-scroll { max-height: 420px; overflow-y: auto; }

    .es-pagination .page-link {
        min-width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 600;
        border-radius: 8px !important;
        padding: 0 0.4rem;
    }

    .privacy-mode .es-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .es-privacy-target:hover {
        filter: blur(0); -webkit-filter: blur(0);
    }

    @media (max-width: 991.98px) {
        .es-faculty-scroll { max-height: 320px; }
        .es-feedback-scroll { max-height: 380px; }
    }
    @media (max-width: 400px) {
        #esPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #esPage h1.h4 { font-size: 1.15rem; }
        .es-avatar { width: 32px; height: 32px; font-size: 0.7rem; border-radius: 8px; }
        .es-avatar-lg { width: 38px; height: 38px; font-size: 0.82rem; border-radius: 10px; }
        .es-faculty-card { padding: 0.5rem 0.6rem; gap: 0.55rem; border-radius: 10px; }
        .es-faculty-name { font-size: 0.76rem; }
        .es-faculty-meta { font-size: 0.62rem; }
        .es-faculty-chevron { font-size: 0.66rem; }
        #esPage .card-title { font-size: 0.9rem; }
        .display-5 { font-size: 2rem; }
    }
    @media (max-width: 360px) {
        .es-avatar { width: 28px; height: 28px; font-size: 0.64rem; }
        #esPage .card-body { padding: 0.6rem; }
    }

    .es-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #esPage { display: none !important; }
        .es-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .es-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .es-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .es-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .es-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .es-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .es-print-only .print-kpi { flex: 1; text-align: center; }
        .es-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .es-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .es-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .es-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .es-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .es-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .es-print-only td.es-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .es-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .es-print-only thead { display: table-header-group; }
        .es-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    const PERFORMANCE_DB = <?= json_encode($performanceDB, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 10;

    let activeFacultyId = Object.keys(PERFORMANCE_DB)[0] || null;
    let currentTab      = 'all';
    let currentPage     = 1;
    let filteredCards   = [];

    /* ============================================================
       Helpers
       ============================================================ */
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function ratingClass(label) {
        switch (label) {
            case 'Outstanding':       return 'text-success';
            case 'Very Satisfactory': return 'text-primary';
            case 'Satisfactory':      return 'text-info';
            case 'Average':           return 'text-warning';
            case 'Needs Improvement': return 'text-danger';
            default:                  return 'text-secondary';
        }
    }

    /* ============================================================
       Faculty selection
       ============================================================ */
    function selectFaculty(facId) {
        activeFacultyId = String(facId);
        currentTab      = 'all';

        document.querySelectorAll('.es-faculty-card').forEach(card => {
            card.classList.toggle('es-active', card.dataset.facultyId === String(facId));
        });

        document.querySelectorAll('#evalSourceTabs .nav-link').forEach(btn => {
            const isAll = btn.dataset.tab === 'all';
            btn.classList.toggle('active', isAll);
            btn.classList.toggle('fw-bold', isAll);
            btn.classList.toggle('text-secondary', !isAll);
        });

        renderData();
    }

    /* ============================================================
       Render detail panel
       ============================================================ */
    function renderData() {
        const fac = PERFORMANCE_DB[activeFacultyId];

        if (!fac) {
            document.getElementById('profName').textContent        = '—';
            document.getElementById('profPosition').textContent    = '—';
            document.getElementById('profAvatar').textContent      = '--';
            document.getElementById('profScore').textContent       = '0.00';
            document.getElementById('profRatingLabel').textContent = 'No Rating';
            document.getElementById('profCount').textContent       = '0';
            document.getElementById('commentsTableBody').innerHTML =
                '<tr><td class="ps-3 text-secondary py-4 text-center">Select a faculty member to view their evaluation summary.</td></tr>';
            return;
        }

        document.getElementById('profName').textContent     = fac.name;
        document.getElementById('profPosition').textContent = fac.position;
        document.getElementById('profAvatar').textContent   = fac.initials;

        document.getElementById('scoreStudentWeight').textContent = fac.sources.student.score;
        document.getElementById('scorePeerWeight').textContent    = fac.sources.peer.score;
        document.getElementById('scoreHeadWeight').textContent    = fac.sources.head.score;

        let activeSourceData;
        let ratingLabelText;

        if (currentTab === 'all') {
            document.getElementById('scoreCardTitle').textContent     = 'Overall Composite Rating (50/30/20)';
            document.getElementById('profScore').textContent           = fac.compositeScore;
            document.getElementById('profRatingLabel').textContent     = fac.compositeRating;
            document.getElementById('profCount').textContent           = fac.totalEvals;
            document.getElementById('sourceBreakdownCard').classList.remove('d-none');
            document.getElementById('feedbackHeaderTitle').textContent = 'Combined Feedback & Remarks';

            activeSourceData = fac.sources.student;
            ratingLabelText  = fac.compositeRating;
        } else {
            activeSourceData = fac.sources[currentTab];
            ratingLabelText  = activeSourceData.ratingText;

            document.getElementById('sourceBreakdownCard').classList.add('d-none');

            const titles = {
                student: 'Student Evaluation (50% Weight)',
                peer:    'Peer / Co-Worker Evaluation (30% Weight)',
                head:    'Department Head Evaluation (20% Weight)'
            };
            document.getElementById('scoreCardTitle').textContent     = titles[currentTab];
            document.getElementById('profScore').textContent           = activeSourceData.score;
            document.getElementById('profRatingLabel').textContent     = ratingLabelText;
            document.getElementById('profCount').textContent           = activeSourceData.evalCount;
            document.getElementById('feedbackHeaderTitle').textContent = currentTab.toUpperCase() + ' Feedback & Remarks';
        }

        const lblEl = document.getElementById('profRatingLabel');
        lblEl.className = 'fw-bold small ' + ratingClass(ratingLabelText);

        const tbody    = document.getElementById('commentsTableBody');
        const feedback = activeSourceData.feedback || [];
        if (feedback.length === 0) {
            tbody.innerHTML = '<tr><td class="ps-3 text-secondary py-4 text-center">No qualitative feedback recorded for this source.</td></tr>';
        } else {
            tbody.innerHTML = feedback.map(item =>
                '<tr><td class="ps-3 text-secondary py-2">' + escapeHtml(item.strong) + '</td></tr>'
            ).join('');
        }
    }

    /* ============================================================
       Faculty list — pagination + search
       ============================================================ */
    function initFacultyPagination() {
        const query    = (document.getElementById('facultySearchInput').value || '').toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.es-faculty-card'));

        filteredCards = allCards.filter(card => {
            const name = card.dataset.name || '';
            return !query || name.indexOf(query) !== -1;
        });

        document.getElementById('facultyCountBadge').textContent = filteredCards.length;

        const totalPages = Math.max(1, Math.ceil(filteredCards.length / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = 1;

        renderFacultyPage();
    }

    function renderFacultyPage() {
        document.querySelectorAll('.es-faculty-card').forEach(c => c.classList.add('d-none'));

        const start = (currentPage - 1) * PAGE_SIZE;
        const end   = start + PAGE_SIZE;
        filteredCards.slice(start, end).forEach(c => c.classList.remove('d-none'));

        const total    = filteredCards.length;
        const startNum = total === 0 ? 0 : start + 1;
        const endNum   = Math.min(end, total);

        document.getElementById('paginationInfo').textContent =
            total === 0 ? 'No results' : ('Showing ' + startNum + '–' + endNum + ' of ' + total);

        renderPaginationControls();
    }

    function renderPaginationControls() {
        const totalPages = Math.max(1, Math.ceil(filteredCards.length / PAGE_SIZE));
        const pager      = document.getElementById('paginationList');
        pager.innerHTML  = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, currentPage - 2);
        const wEnd   = Math.min(totalPages, currentPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === currentPage) return;
                currentPage = p;
                renderFacultyPage();
            });
        });
    }

    /* ============================================================
       Tabs
       ============================================================ */
    function bindTabs() {
        document.querySelectorAll('#evalSourceTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTab = btn.dataset.tab;
                document.querySelectorAll('#evalSourceTabs .nav-link').forEach(b => {
                    const isMe = b === btn;
                    b.classList.toggle('active', isMe);
                    b.classList.toggle('fw-bold', isMe);
                    b.classList.toggle('text-secondary', !isMe);
                });
                renderData();
            });
        });
    }

    /* ============================================================
       Print report
       ============================================================ */
    function renderPrintReport() {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const rows = Object.values(PERFORMANCE_DB);

        const total          = rows.length;
        const fullyEvaluated = rows.filter(r => r.fullyEvaluated).length;
        const needsAttention = rows.filter(r => {
            const s = parseFloat(r.compositeScore);
            return s > 0 && s < 3.50;
        }).length;

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td class="es-print-blur">' + escapeHtml(r.name) + '</td>' +
                '<td>' + escapeHtml(r.faculty_no || '—') + '</td>' +
                '<td>' + escapeHtml(r.department) + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(r.sources.student.score) + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(r.sources.peer.score) + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(r.sources.head.score) + '</td>' +
                '<td style="text-align:center;"><strong>' + escapeHtml(r.compositeScore) + '</strong></td>' +
                '<td>' + escapeHtml(r.compositeRating) + '</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Evaluation Summary Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">All Faculty Professors in your assigned departments · Last 12 months</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total Faculty</div>
                    <div class="print-kpi-value">${total}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Fully Evaluated</div>
                    <div class="print-kpi-value">${fullyEvaluated}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Needs Attention</div>
                    <div class="print-kpi-value">${needsAttention}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Faculty No</th>
                        <th>Department</th>
                        <th>Student (50%)</th>
                        <th>Peer (30%)</th>
                        <th>Head (20%)</th>
                        <th>Composite</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="8" style="text-align:center;">No evaluation data in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} faculty record${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV export
       ============================================================ */
    function exportCsv() {
        const headers = ['Faculty', 'Faculty No', 'Department', 'Student (50%)', 'Peer (30%)', 'Head (20%)', 'Composite', 'Rating', 'Total Evals'];
        const rows    = Object.values(PERFORMANCE_DB);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(r => {
            const line = [
                r.name,
                r.faculty_no || '',
                r.department,
                r.sources.student.score,
                r.sources.peer.score,
                r.sources.head.score,
                r.compositeScore,
                r.compositeRating,
                r.totalEvals
            ];
            csv += line.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'evaluation_summary_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        if (activeFacultyId) {
            const first = document.querySelector('.es-faculty-card[data-faculty-id="' + activeFacultyId + '"]');
            if (first) first.classList.add('es-active');
        }

        const searchInput = document.getElementById('facultySearchInput');
        if (searchInput) {
            let t;
            searchInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => { currentPage = 1; initFacultyPagination(); }, 150);
            });
        }

        // Whole card is clickable now
        const listEl = document.getElementById('facultyListContainer');
        if (listEl) {
            listEl.addEventListener('click', e => {
                const card = e.target.closest('.es-faculty-card');
                if (!card) return;
                selectFaculty(card.dataset.facultyId);
            });
        }

        document.getElementById('printBtn').addEventListener('click', () => window.print());
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);

        const pt  = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanEvaluationSummaryPrivacy';
        if (pt) {
            const apply = on => document.body.classList.toggle('privacy-mode', on);
            try {
                const saved = localStorage.getItem(KEY) === '1';
                pt.checked = saved; apply(saved);
            } catch (e) { /* ignore */ }
            pt.addEventListener('change', () => {
                apply(pt.checked);
                try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

        bindTabs();
        initFacultyPagination();
        renderData();
        renderPrintReport();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>