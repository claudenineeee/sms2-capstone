<?php
/**
 * SMS 2 - Dean - Faculty Performance (merged with Evaluation Summary)
 *
 * Contains tabs:
 *   - Performance          (ranking view)
 *   - Evaluation Summary   (per-faculty multi-source breakdown)
 *
 * RBAC: every query scoped to dean's assigned departments.
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

/* Positions that count as "teaching faculty" for the ranking tab */
$teachingPositions = ['Faculty Professor', 'Department Head'];

/* Positions that count as "Faculty Professor" for the evaluation summary tab */
$evalPositions = ['Faculty Professor'];

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
   LOAD DATA — PERFORMANCE TAB
   ============================================================ */
$facultyRoster   = [];
$allAttendance   = [];
$allEvals        = [];
$allLoads        = [];
$loadError       = null;

/* ============================================================
   LOAD DATA — EVALUATION SUMMARY TAB
   ============================================================ */
$facultyMembers        = [];
$performanceDB         = [];
$totalFacultyCount     = 0;
$fullyEvaluatedCount   = 0;
$deptOverallAverage    = 0;
$deptStudentAverage    = 0;
$deptPeerAverage       = 0;
$deptHeadAverage       = 0;
$deptStudentScores     = [];
$deptPeerScores        = [];
$deptHeadScores        = [];
$deptPeerRatedCount    = 0;
$evaluatedFacultyCount = 0;
$topPerformer          = null;
$topPerformerScore     = 0;
$needsAttentionList    = [];
$needsAttentionCount   = 0;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ============================================================
           TAB 1 — PERFORMANCE: faculty + attendance + evals + loads
           ============================================================ */
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id, f.faculty_no, f.first_name, f.middle_name, f.last_name,
                f.email, f.department_id, f.position, f.employment_status, f.profile_status,
                f.academic_rank, f.tier, f.hired_date,
                d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyRoster = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        $facultyIds    = array_map('intval', array_column($facultyRoster, 'faculty_id'));
        $facultyIdList = !empty($facultyIds) ? implode(',', $facultyIds) : '0';

        if (!empty($facultyIds)) {
            $attStmt = $pdo->query("
                SELECT a.attendance_id, a.faculty_id, a.attendance_date,
                       a.status, a.hours_rendered, f.department_id
                FROM faculty_db.attendance_records a
                INNER JOIN faculty_db.faculty f ON f.faculty_id = a.faculty_id
                WHERE f.faculty_id IN ($facultyIdList)
                  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY a.attendance_date DESC
            ");
            $allAttendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);

            $evalStmt = $pdo->query("
                SELECT e.evaluation_id, e.faculty_id, e.source_type,
                       e.composite_score, e.rating_label, e.submitted_at, f.department_id
                FROM faculty_db.evaluations e
                INNER JOIN faculty_db.faculty f ON f.faculty_id = e.faculty_id
                WHERE f.faculty_id IN ($facultyIdList)
                  AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY e.submitted_at DESC
            ");
            $allEvals = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

            $loadStmt = $pdo->query("
                SELECT tlr.load_request_id, tlr.faculty_id, tlr.term_id,
                       tlr.total_units, tlr.status, tlr.submitted_at,
                       at.academic_year, at.semester, f.department_id
                FROM faculty_db.teaching_load_requests tlr
                INNER JOIN faculty_db.faculty f ON f.faculty_id = tlr.faculty_id
                LEFT JOIN faculty_db.academic_terms at ON at.term_id = tlr.term_id
                WHERE f.faculty_id IN ($facultyIdList)
                ORDER BY tlr.submitted_at DESC
            ");
            $allLoads = $loadStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /* ============================================================
           TAB 2 — EVALUATION SUMMARY: only Faculty Professors
           ============================================================ */
        $evalPositionIn = implode(',', array_fill(0, count($evalPositions), '?'));
        $esFacStmt = $pdo->prepare("
            SELECT
                f.faculty_id, f.faculty_no, f.first_name, f.middle_name, f.last_name,
                f.email, f.department_id, f.position, f.profile_status,
                d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($evalPositionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $esFacStmt->execute($evalPositions);
        $facultyMembers    = $esFacStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalFacultyCount = count($facultyMembers);

        $evalsByFaculty = [];
        $feedbackByEval = [];

        if (!empty($facultyMembers)) {
            $esFacultyIds    = array_map(fn($f) => (int) $f['faculty_id'], $facultyMembers);
            $esFacultyIdList = implode(',', $esFacultyIds);

            $esEvalStmt = $pdo->query("
                SELECT e.evaluation_id, e.faculty_id, e.source_type,
                       e.composite_score, e.rating_label, e.eval_count, e.submitted_at
                FROM faculty_db.evaluations e
                WHERE e.faculty_id IN ($esFacultyIdList)
                  AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY e.submitted_at DESC
            ");
            $evalIds = [];
            foreach ($esEvalStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
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
        error_log('Dean faculty performance load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Faculty Performance';
$activeModule = 'faculty';
$activePage   = 'faculty-performance';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Faculty Performance', 'url' => null],
];

$initialTab = strtolower((string) ($_GET['tab'] ?? 'performance'));
if (!in_array($initialTab, ['performance', 'evaluations'], true)) $initialTab = 'performance';

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="fpPage">

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
        <h4 class="mb-0 fw-bold">Faculty Performance Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-chart-simple text-primary"></i>
                <span>Faculty Performance</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Composite ranking and multi-source evaluation summary for teaching faculty in your department<?= count($deanDepartments) > 1 ? 's' : '' ?>.
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

    <!-- ================= Tabs ================= -->
    <ul class="nav nav-tabs mb-3 no-print" id="fpTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $initialTab === 'performance' ? 'active' : '' ?>" id="tab-perf-btn"
                    data-bs-toggle="tab" data-bs-target="#tab-performance" type="button" role="tab">
                <i class="fas fa-ranking-star me-1"></i>Performance Ranking
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $initialTab === 'evaluations' ? 'active' : '' ?>" id="tab-eval-btn"
                    data-bs-toggle="tab" data-bs-target="#tab-evaluations" type="button" role="tab">
                <i class="fas fa-chart-pie me-1"></i>Evaluation Summary
            </button>
        </li>
    </ul>

    <div class="tab-content" id="fpTabContent">

        <!-- ============================================================
             TAB 1 — PERFORMANCE RANKING
             ============================================================ -->
        <div class="tab-pane fade <?= $initialTab === 'performance' ? 'show active' : '' ?>" id="tab-performance" role="tabpanel">

            <!-- KPI Cards -->
            <div class="row g-3 mb-4 no-print">
                <div class="col-6 col-lg-3">
                    <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                                <i class="fas fa-user-group"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Faculty Ranked</h6>
                                <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiFaculty">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">Teaching staff only</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Top Performers</h6>
                                <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiTop">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">Outstanding tier</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                                <i class="fas fa-arrow-trend-up"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Avg Composite</h6>
                                <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiComposite">0.00</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">On 5-point scale</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#ff4d4d;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(255,77,77,0.14);color:#ff4d4d;">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Needs Support</h6>
                                <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiNeedsSupport">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Below threshold</small>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border shadow-sm mb-4 no-print">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <?php if (count($deanDepartments) > 1): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_dept">Department</label>
                            <select class="form-select form-select-sm" id="f_dept" data-filter="dept">
                                <option value="all">All my departments</option>
                                <?php foreach ($deanDepartments as $deptId): ?>
                                    <option value="<?= (int) $deptId ?>">
                                        <?= htmlspecialchars($deanDepartmentNames[$deptId] ?? ('Dept #' . $deptId)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sy">School Year</label>
                            <select class="form-select form-select-sm" id="f_sy" data-filter="sy">
                                <option value="">All years</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_month">Month</label>
                            <select class="form-select form-select-sm" id="f_month" data-filter="month">
                                <option value="">All months</option>
                                <option value="1">January</option><option value="2">February</option>
                                <option value="3">March</option><option value="4">April</option>
                                <option value="5">May</option><option value="6">June</option>
                                <option value="7">July</option><option value="8">August</option>
                                <option value="9">September</option><option value="10">October</option>
                                <option value="11">November</option><option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_week">Week</label>
                            <select class="form-select form-select-sm" id="f_week" data-filter="week">
                                <option value="">All weeks</option>
                                <option value="1">Week 1 · 1–7</option><option value="2">Week 2 · 8–14</option>
                                <option value="3">Week 3 · 15–21</option><option value="4">Week 4 · 22–28</option>
                                <option value="5">Week 5 · 29–31</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_tier">Tier</label>
                            <select class="form-select form-select-sm" id="f_tier" data-filter="tier">
                                <option value="">All tiers</option>
                                <option value="outstanding">Outstanding (≥ 4.50)</option>
                                <option value="very_satisfactory">Very Satisfactory (4.00–4.49)</option>
                                <option value="satisfactory">Satisfactory (3.50–3.99)</option>
                                <option value="needs_support">Needs Support (&lt; 3.50)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                                <i class="fas fa-rotate-left"></i><span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Table -->
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Performance Ranking</h5>
                        <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
                    </div>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#10b981;"></span>Outstanding
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#06b6d4;"></span>Very Satisfactory
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Satisfactory
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#ef4444;"></span>Needs Support
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0 fp-table" style="min-width: 720px;">
                        <thead>
                            <tr>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center" style="width:60px;">#</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Department</th>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center">Present</th>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center">Eval</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell text-center">Units</th>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center">Composite</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Tier</th>
                            </tr>
                        </thead>
                        <tbody id="perfBody"></tbody>
                    </table>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
                    <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Performance pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ============================================================
             TAB 2 — EVALUATION SUMMARY
             ============================================================ -->
        <div class="tab-pane fade <?= $initialTab === 'evaluations' ? 'show active' : '' ?>" id="tab-evaluations" role="tabpanel">

            <!-- KPI Cards -->
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
                                <h4 class="mb-0 fw-bold">
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

            <!-- Per-Source Comparison -->
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

            <!-- Two-Column: Faculty list + Evaluation detail -->
            <div class="row g-3 align-items-start">
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

                <div class="col-12 col-lg-8 col-xl-9">
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
    </div>
</div>

<!-- Print-only report -->
<div class="fp-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* ============================================================
       Tabs
       ============================================================ */
    #fpTabs { border-bottom: 1px solid var(--sms-border-soft); }
    #fpTabs .nav-link {
        color: var(--sms-text-muted);
        font-weight: 600;
        font-size: 0.85rem;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.6rem 1rem;
        background: transparent;
    }
    #fpTabs .nav-link:hover { color: var(--sms-heading); }
    #fpTabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom-color: #0d6efd;
    }

    /* ============================================================
       Performance table
       ============================================================ */
    .fp-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .fp-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .fp-table tbody td { padding: 0.7rem 0.75rem; font-size: 0.85rem; vertical-align: middle; }
    .fp-table thead th { padding: 0.65rem 0.75rem; font-size: 0.7rem; }

    .fp-rank {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        border-radius: 9px;
        font-weight: 800; font-size: 0.85rem;
        background: var(--sms-surface-muted);
        color: var(--sms-text-strong);
        border: 1px solid var(--sms-border-soft);
    }
    .fp-rank--1 { background: rgba(234,179,8,0.14); color: #b45309; border-color: rgba(234,179,8,0.30); }
    .fp-rank--2 { background: rgba(148,163,184,0.14); color: #475569; border-color: rgba(148,163,184,0.30); }
    .fp-rank--3 { background: rgba(217,119,6,0.14); color: #92400e; border-color: rgba(217,119,6,0.30); }

    .fp-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        font-weight: 700; font-size: 0.78rem;
        flex-shrink: 0;
    }

    .fp-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem; font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .fp-badge--outstanding       { background: rgba(16,185,129,0.14); color: #059669; border-color: rgba(16,185,129,0.24); }
    .fp-badge--very_satisfactory { background: rgba(6,182,212,0.14);  color: #0891b2; border-color: rgba(6,182,212,0.24); }
    .fp-badge--satisfactory      { background: rgba(245,158,11,0.16); color: #b45309; border-color: rgba(245,158,11,0.28); }
    .fp-badge--needs_support     { background: rgba(239,68,68,0.14);  color: #dc2626; border-color: rgba(239,68,68,0.24); }

    [data-theme="dark"] .fp-badge--outstanding       { color: #6ee7b7; }
    [data-theme="dark"] .fp-badge--very_satisfactory { color: #7dd3fc; }
    [data-theme="dark"] .fp-badge--satisfactory      { color: #fcd34d; }
    [data-theme="dark"] .fp-badge--needs_support     { color: #fca5a5; }
    [data-theme="dark"] .fp-rank--1 { color: #fcd34d; }
    [data-theme="dark"] .fp-rank--2 { color: #cbd5e1; }
    [data-theme="dark"] .fp-rank--3 { color: #fbbf24; }

    /* ============================================================
       Evaluation Summary — faculty list
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

    /* ============================================================
       Privacy
       ============================================================ */
    .privacy-mode .fp-privacy-target,
    .privacy-mode .es-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .fp-privacy-target:hover,
    .privacy-mode .es-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* ============================================================
       Responsive
       ============================================================ */
    @media (max-width: 991.98px) {
        .es-faculty-scroll { max-height: 320px; }
        .es-feedback-scroll { max-height: 380px; }
    }
    @media (max-width: 400px) {
        #fpPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #fpPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .fp-table tbody td,
        .fp-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        .fp-avatar { width: 30px; height: 30px; font-size: 0.68rem; }
        .fp-rank { width: 26px; height: 26px; font-size: 0.72rem; }
        .es-avatar { width: 32px; height: 32px; font-size: 0.7rem; border-radius: 8px; }
        .es-avatar-lg { width: 38px; height: 38px; font-size: 0.82rem; border-radius: 10px; }
        .es-faculty-card { padding: 0.5rem 0.6rem; gap: 0.55rem; border-radius: 10px; }
        .es-faculty-name { font-size: 0.76rem; }
        .es-faculty-meta { font-size: 0.62rem; }
        .es-faculty-chevron { font-size: 0.66rem; }
        #fpPage .card-title { font-size: 0.9rem; }
        .display-5 { font-size: 2rem; }
    }
    @media (max-width: 360px) {
        .fp-table tbody td,
        .fp-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
        .es-avatar { width: 28px; height: 28px; font-size: 0.64rem; }
        #fpPage .card-body { padding: 0.6rem; }
    }

    /* ============================================================
       Print
       ============================================================ */
    .fp-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #fpPage { display: none !important; }
        .fp-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .fp-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .fp-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .fp-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .fp-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .fp-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .fp-print-only .print-kpi { flex: 1; text-align: center; }
        .fp-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .fp-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .fp-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .fp-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .fp-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .fp-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .fp-print-only td.fp-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .fp-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .fp-print-only thead { display: table-header-group; }
        .fp-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    /* ============================================================
       Data (both tabs)
       ============================================================ */
    const FACULTY    = <?= json_encode($facultyRoster, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const ATTENDANCE = <?= json_encode($allAttendance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const EVALS      = <?= json_encode($allEvals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const LOADS      = <?= json_encode($allLoads, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PERFORMANCE_DB = <?= json_encode($performanceDB, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PERF_PAGE_SIZE   = 15;
    const FACULTY_PAGE_SIZE = 10;

    let filters = { dept: 'all', sy: '', month: '', week: '', tier: '' };
    let perfPage = 1;
    let rankedRows = [];

    let activeFacultyId = Object.keys(PERFORMANCE_DB)[0] || null;
    let currentTab      = 'all';
    let facultyPage     = 1;
    let filteredCards   = [];

    /* ============================================================
       Helpers
       ============================================================ */
    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function fullName(r) {
        const mid = r.middle_name ? ' ' + r.middle_name.charAt(0) + '.' : '';
        return ((r.first_name || '') + mid + ' ' + (r.last_name || '')).trim() || '—';
    }
    function initialsOf(name) {
        return (name || '').split(' ').filter(Boolean).slice(0, 2).map(n => n.charAt(0).toUpperCase()).join('') || '—';
    }
    function getSyRange(sy) {
        const m = /^(\d{4})-(\d{4})$/.exec(sy);
        if (!m) return null;
        const y = +m[1];
        return { start: new Date(y, 5, 1), end: new Date(y + 1, 4, 31, 23, 59, 59) };
    }
    function dateMatches(str) {
        const d = parseDate(str);
        if (!d) return false;
        if (filters.sy) {
            const range = getSyRange(filters.sy);
            if (!range || d < range.start || d > range.end) return false;
        }
        if (filters.month && (d.getMonth() + 1) !== +filters.month) return false;
        if (filters.week) {
            const day = d.getDate();
            const w = +filters.week;
            if (w === 1 && !(day >= 1  && day <= 7))  return false;
            if (w === 2 && !(day >= 8  && day <= 14)) return false;
            if (w === 3 && !(day >= 15 && day <= 21)) return false;
            if (w === 4 && !(day >= 22 && day <= 28)) return false;
            if (w === 5 && !(day >= 29))              return false;
        }
        return true;
    }
    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }

    function tierForScore(score) {
        if (score >= 4.50) return 'outstanding';
        if (score >= 4.00) return 'very_satisfactory';
        if (score >= 3.50) return 'satisfactory';
        if (score >  0)    return 'needs_support';
        return 'needs_support';
    }
    function tierLabel(t) {
        return {
            outstanding:       'Outstanding',
            very_satisfactory: 'Very Satisfactory',
            satisfactory:      'Satisfactory',
            needs_support:     'Needs Support'
        }[t] || '—';
    }

    /* ============================================================
       TAB 1 — PERFORMANCE
       ============================================================ */
    function buildRanking() {
        const att   = ATTENDANCE.filter(r => deptMatches(r) && dateMatches(r.attendance_date));
        const evals = EVALS.filter(r => deptMatches(r) && dateMatches(r.submitted_at));
        const loads = LOADS.filter(r => deptMatches(r));

        const byFaculty = {};
        FACULTY.filter(deptMatches).forEach(f => {
            byFaculty[f.faculty_id] = {
                faculty_id: f.faculty_id,
                faculty_no: f.faculty_no || '',
                name: fullName(f),
                dept: f.dept_name || f.dept_code || '—',
                position: f.position || '—',
                attendance: { present: 0, total: 0 },
                eval: { sum: 0, count: 0 },
                units: 0
            };
        });

        att.forEach(r => {
            const s = byFaculty[r.faculty_id]; if (!s) return;
            s.attendance.total++;
            if (r.status === 'Present') s.attendance.present++;
        });
        evals.forEach(r => {
            const s = byFaculty[r.faculty_id]; if (!s) return;
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { s.eval.sum += v; s.eval.count++; }
        });
        loads.forEach(r => {
            const s = byFaculty[r.faculty_id]; if (!s) return;
            s.units += (parseFloat(r.total_units) || 0);
        });

        const rows = Object.values(byFaculty).map(s => {
            const attRate = s.attendance.total > 0 ? (s.attendance.present / s.attendance.total) * 100 : 0;
            const attScore = (attRate / 100) * 5;
            const evalScore = s.eval.count > 0 ? (s.eval.sum / s.eval.count) : 0;
            const loadScore = Math.min(5, (s.units / 15) * 5);
            const composite = (attScore * 0.35) + (evalScore * 0.50) + (loadScore * 0.15);

            return {
                ...s,
                attRate: Math.round(attRate),
                attScore, evalScore, loadScore,
                composite: +composite.toFixed(2),
                tier: tierForScore(composite)
            };
        });

        rows.sort((a, b) => b.composite - a.composite);
        return rows;
    }

    function renderPerfKPIs(rows) {
        document.getElementById('kpiFaculty').textContent = rows.length.toLocaleString();
        const top = rows.filter(r => r.tier === 'outstanding').length;
        const needsSupport = rows.filter(r => r.tier === 'needs_support' && r.composite > 0).length;
        const totalComposite = rows.reduce((sum, r) => sum + (r.composite > 0 ? r.composite : 0), 0);
        const avgComposite = rows.length > 0 ? (totalComposite / rows.length) : 0;

        document.getElementById('kpiTop').textContent = top.toLocaleString();
        document.getElementById('kpiComposite').textContent = avgComposite.toFixed(2);
        document.getElementById('kpiNeedsSupport').textContent = needsSupport.toLocaleString();
    }

    function renderPerfTable(rows) {
        const tbody = document.getElementById('perfBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="8" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-user-group d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No teaching faculty in the current scope.
                </td></tr>`;
            document.getElementById('tableSubtitle').textContent = 'No teaching faculty in scope';
            document.getElementById('pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PERF_PAGE_SIZE));
        if (perfPage > totalPages) perfPage = totalPages;
        const start = (perfPage - 1) * PERF_PAGE_SIZE;
        const end = Math.min(start + PERF_PAGE_SIZE, total);
        const page = rows.slice(start, end);

        tbody.innerHTML = page.map((r, idx) => {
            const rank = start + idx + 1;
            const rankClass = rank === 1 ? 'fp-rank--1' : (rank === 2 ? 'fp-rank--2' : (rank === 3 ? 'fp-rank--3' : ''));
            const tierClass = 'fp-badge--' + r.tier;
            const unitsDisplay = r.units > 0 ? r.units.toFixed(1) : '—';
            const evalDisplay = r.evalScore > 0 ? r.evalScore.toFixed(2) : '—';
            const compositeDisplay = r.composite > 0 ? r.composite.toFixed(2) : '—';

            return '<tr>' +
                '<td class="text-center"><span class="fp-rank ' + rankClass + '">' + rank + '</span></td>' +
                '<td>' +
                    '<div class="d-flex align-items-center gap-2 min-w-0">' +
                        '<span class="fp-avatar">' + escapeHtml(initialsOf(r.name)) + '</span>' +
                        '<div class="min-w-0">' +
                            '<div class="fw-semibold fp-privacy-target">' + escapeHtml(r.name) + '</div>' +
                            '<div class="small text-body-secondary">' + escapeHtml(r.faculty_no || '—') + '</div>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(r.dept) + '</td>' +
                '<td class="text-center">' + r.attRate + '<small class="text-body-secondary">%</small></td>' +
                '<td class="text-center">' + evalDisplay + '</td>' +
                '<td class="d-none d-sm-table-cell text-center">' + unitsDisplay + '</td>' +
                '<td class="text-center fw-bold">' + compositeDisplay + '</td>' +
                '<td><span class="fp-badge ' + tierClass + '">' + tierLabel(r.tier) + '</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' faculty';
        document.getElementById('pagerInfo').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total;
        renderPerfPager(totalPages);
    }

    function renderPerfPager(totalPages) {
        const pager = document.getElementById('pager');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (perfPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (perfPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, perfPage - 2);
        const wEnd = Math.min(totalPages, perfPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === perfPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (perfPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (perfPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === perfPage) return;
                perfPage = p;
                renderPerfTable(rankedRows);
            });
        });
    }

    function applyTierFilter(rows) {
        if (!filters.tier) return rows;
        return rows.filter(r => r.tier === filters.tier);
    }

    /* ============================================================
       TAB 2 — EVALUATION SUMMARY
       ============================================================ */
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

        renderEvalDetail();
    }

    function renderEvalDetail() {
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

    function initFacultyPagination() {
        const query    = (document.getElementById('facultySearchInput').value || '').toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.es-faculty-card'));

        filteredCards = allCards.filter(card => {
            const name = card.dataset.name || '';
            return !query || name.indexOf(query) !== -1;
        });

        document.getElementById('facultyCountBadge').textContent = filteredCards.length;

        const totalPages = Math.max(1, Math.ceil(filteredCards.length / FACULTY_PAGE_SIZE));
        if (facultyPage > totalPages) facultyPage = 1;

        renderFacultyPage();
    }

    function renderFacultyPage() {
        document.querySelectorAll('.es-faculty-card').forEach(c => c.classList.add('d-none'));

        const start = (facultyPage - 1) * FACULTY_PAGE_SIZE;
        const end   = start + FACULTY_PAGE_SIZE;
        filteredCards.slice(start, end).forEach(c => c.classList.remove('d-none'));

        const total    = filteredCards.length;
        const startNum = total === 0 ? 0 : start + 1;
        const endNum   = Math.min(end, total);

        document.getElementById('paginationInfo').textContent =
            total === 0 ? 'No results' : ('Showing ' + startNum + '–' + endNum + ' of ' + total);

        renderFacultyPager();
    }

    function renderFacultyPager() {
        const totalPages = Math.max(1, Math.ceil(filteredCards.length / FACULTY_PAGE_SIZE));
        const pager      = document.getElementById('paginationList');
        pager.innerHTML  = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (facultyPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (facultyPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, facultyPage - 2);
        const wEnd   = Math.min(totalPages, facultyPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === facultyPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (facultyPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (facultyPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === facultyPage) return;
                facultyPage = p;
                renderFacultyPage();
            });
        });
    }

    function bindEvalTabs() {
        document.querySelectorAll('#evalSourceTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTab = btn.dataset.tab;
                document.querySelectorAll('#evalSourceTabs .nav-link').forEach(b => {
                    const isMe = b === btn;
                    b.classList.toggle('active', isMe);
                    b.classList.toggle('fw-bold', isMe);
                    b.classList.toggle('text-secondary', !isMe);
                });
                renderEvalDetail();
            });
        });
    }

    /* ============================================================
       Print (uses performance ranking by default)
       ============================================================ */
    function renderPrintReport() {
        const host = document.getElementById('printReport');
        if (!host) return;

        const filterParts = [];
        if (filters.dept !== 'all') {
            filterParts.push('Department: ' + (DEPT_NAMES[filters.dept] || ('Dept #' + filters.dept)));
        }
        if (filters.sy)    filterParts.push('School Year: ' + filters.sy);
        if (filters.month) {
            const mNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            filterParts.push('Month: ' + mNames[+filters.month - 1]);
        }
        if (filters.week)  filterParts.push('Week: ' + filters.week);
        if (filters.tier)  filterParts.push('Tier: ' + tierLabel(filters.tier));
        const filterLine = filterParts.length
            ? 'Filters — ' + filterParts.join(' · ')
            : 'No filters applied — showing all teaching faculty';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const total = rankedRows.length;
        const top = rankedRows.filter(r => r.tier === 'outstanding').length;
        const needsSupport = rankedRows.filter(r => r.tier === 'needs_support' && r.composite > 0).length;
        const totalComposite = rankedRows.reduce((sum, r) => sum + (r.composite > 0 ? r.composite : 0), 0);
        const avgComposite = rankedRows.length > 0 ? (totalComposite / rankedRows.length) : 0;

        const rowsHtml = rankedRows.map((r, idx) => {
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td class="fp-print-blur">' + escapeHtml(r.name) + '</td>' +
                '<td>' + escapeHtml(r.dept) + '</td>' +
                '<td>' + r.attRate + '%</td>' +
                '<td>' + (r.evalScore > 0 ? r.evalScore.toFixed(2) : '—') + '</td>' +
                '<td>' + (r.units > 0 ? r.units.toFixed(1) : '—') + '</td>' +
                '<td>' + (r.composite > 0 ? r.composite.toFixed(2) : '—') + '</td>' +
                '<td>' + escapeHtml(tierLabel(r.tier)) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Faculty Performance Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Faculty</div>
                    <div class="print-kpi-value">${total}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Top Performers</div>
                    <div class="print-kpi-value">${top}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Avg Composite</div>
                    <div class="print-kpi-value">${avgComposite.toFixed(2)}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Needs Support</div>
                    <div class="print-kpi-value">${needsSupport}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Faculty</th><th>Department</th>
                        <th>Present</th><th>Eval</th><th>Units</th>
                        <th>Composite</th><th>Tier</th>
                    </tr>
                </thead>
                <tbody>${rowsHtml || '<tr><td colspan="8" style="text-align:center;">No teaching faculty in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} faculty · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv() {
        const headers = ['Rank', 'Faculty No', 'Faculty', 'Department', 'Position', 'Present Rate (%)', 'Avg Eval', 'Units', 'Composite', 'Tier'];
        const lines = rankedRows.map((r, idx) => [
            idx + 1,
            r.faculty_no,
            r.name,
            r.dept,
            r.position,
            r.attRate,
            r.evalScore > 0 ? r.evalScore.toFixed(2) : '',
            r.units > 0 ? r.units.toFixed(1) : '',
            r.composite > 0 ? r.composite.toFixed(2) : '',
            tierLabel(r.tier)
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'faculty-performance_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function populateYearFilter() {
        const years = new Set();
        ATTENDANCE.forEach(r => {
            const d = parseDate(r.attendance_date); if (!d) return;
            const y = d.getFullYear(), m = d.getMonth() + 1;
            const syStart = m >= 6 ? y : y - 1;
            years.add(syStart + '-' + (syStart + 1));
        });
        const sel = document.getElementById('f_sy');
        if (!sel) return;
        Array.from(years).sort().reverse().forEach(sy => {
            const opt = document.createElement('option');
            opt.value = sy; opt.textContent = sy;
            sel.appendChild(opt);
        });
    }

    function render() {
        const ranked = buildRanking();
        rankedRows = applyTierFilter(ranked);
        perfPage = 1;
        renderPerfKPIs(ranked);
        renderPerfTable(rankedRows);
        renderPrintReport();
    }

    function bindFilters() {
        document.querySelectorAll('#fpPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }
    function resetAllFilters() {
        filters = { dept: 'all', sy: '', month: '', week: '', tier: '' };
        document.querySelectorAll('#fpPage [data-filter]').forEach(el => {
            el.value = (el.tagName === 'SELECT' && el.dataset.filter === 'dept') ? 'all' : '';
        });
        render();
    }

    function init() {
        populateYearFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        /* Evaluation tab: faculty list + tabs */
        if (activeFacultyId) {
            const first = document.querySelector('.es-faculty-card[data-faculty-id="' + activeFacultyId + '"]');
            if (first) first.classList.add('es-active');
        }

        const searchInput = document.getElementById('facultySearchInput');
        if (searchInput) {
            let t;
            searchInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => { facultyPage = 1; initFacultyPagination(); }, 150);
            });
        }

        const listEl = document.getElementById('facultyListContainer');
        if (listEl) {
            listEl.addEventListener('click', e => {
                const card = e.target.closest('.es-faculty-card');
                if (!card) return;
                selectFaculty(card.dataset.facultyId);
            });
        }

        bindEvalTabs();
        initFacultyPagination();
        renderEvalDetail();

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanFacultyPerformancePrivacy';
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

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>