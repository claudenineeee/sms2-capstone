<?php
/**
 * SMS 2 - Dean - Evaluation History
 * Faculty Professor evaluation log, RBAC-scoped to the dean's departments.
 * Flat timeline of every row in faculty_db.evaluations, filterable client-side
 * after server-side RBAC scoping.
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
   LOAD DATA — scoped to dean's departments AND teaching positions
   ============================================================ */
$allEvaluations   = [];
$facultyRoster    = [];
$academicTerms    = [];
$loadError        = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        /* ---- Faculty roster (Faculty Professor only) ---- */
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
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
        $facultyRoster = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        $facultyMap = [];
        foreach ($facultyRoster as $f) {
            $facultyMap[(int) $f['faculty_id']] = [
                'faculty_id'    => (int) $f['faculty_id'],
                'faculty_no'    => $f['faculty_no'] ?? '',
                'name'          => trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? '')),
                'department_id' => (int) $f['department_id'],
                'dept_name'     => $f['dept_name'] ?: ($f['dept_code'] ?: '—'),
            ];
        }

        /* ---- Academic terms (for filter + labels) ---- */
        $termsStmt = $pdo->query("
            SELECT term_id, academic_year, semester, is_current
            FROM faculty_db.academic_terms
            ORDER BY academic_year DESC, semester ASC
        ");
        $academicTerms = $termsStmt->fetchAll(PDO::FETCH_ASSOC);

        $termMap = [];
        foreach ($academicTerms as $t) {
            $termMap[(int) $t['term_id']] = [
                'academic_year' => $t['academic_year'],
                'semester'      => $t['semester'],
            ];
        }

        /* ---- Evaluation rows for those faculty ---- */
        $facultyIds = array_keys($facultyMap);
        $feedbackMap = [];
        if (!empty($facultyIds)) {
            $facultyIdList = implode(',', array_map('intval', $facultyIds));
            $evalStmt = $pdo->query("
                SELECT
                    e.evaluation_id,
                    e.faculty_id,
                    e.term_id,
                    e.source_type,
                    e.evaluator_id,
                    e.evaluator_external_id,
                    e.composite_score,
                    e.rating_label,
                    e.eval_count,
                    e.response_rate,
                    e.submitted_at
                FROM faculty_db.evaluations e
                INNER JOIN faculty_db.faculty f ON f.faculty_id = e.faculty_id
                WHERE f.faculty_id IN ($facultyIdList)
                ORDER BY e.submitted_at DESC
                LIMIT 5000
            ");
            $allEvaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

            /* ---- Optional feedback preview ---- */
            $evalIds = array_map(fn($r) => (int) $r['evaluation_id'], $allEvaluations);
            if (!empty($evalIds)) {
                $evalIdList = implode(',', $evalIds);
                $fbStmt = $pdo->query("
                    SELECT evaluation_id, strength_comment, improvement_comment
                    FROM faculty_db.evaluation_feedback
                    WHERE evaluation_id IN ($evalIdList)
                ");
                foreach ($fbStmt->fetchAll(PDO::FETCH_ASSOC) as $fb) {
                    $feedbackMap[(int) $fb['evaluation_id']] = $fb;
                }
            }
        }

        /* ---- Attach faculty + term info ---- */
        foreach ($allEvaluations as &$row) {
            $fid = (int) $row['faculty_id'];
            $tid = (int) $row['term_id'];
            $f   = $facultyMap[$fid] ?? null;
            $t   = $termMap[$tid]    ?? null;

            $row['faculty_name']   = $f ? $f['name']          : ('Faculty #' . $fid);
            $row['faculty_no']     = $f ? $f['faculty_no']    : '';
            $row['faculty_dept']   = $f ? $f['dept_name']     : '—';
            $row['department_id']  = $f ? $f['department_id'] : 0;
            $row['academic_year']  = $t ? $t['academic_year'] : '—';
            $row['semester']       = $t ? $t['semester']      : '—';

            $fb = $feedbackMap[(int) $row['evaluation_id']] ?? null;
            $row['strength_comment']    = $fb['strength_comment']    ?? '';
            $row['improvement_comment'] = $fb['improvement_comment'] ?? '';
        }
        unset($row);

    } catch (Throwable $e) {
        error_log('Dean evaluation history load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Evaluation History';
$activeModule = 'faculty';
$activePage   = 'evaluation-history';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Evaluation History', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="ehPage">

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
        <h4 class="mb-0 fw-bold">Evaluation History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-clock-rotate-left text-primary"></i>
                <span>Evaluation History</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Faculty Professor evaluation records for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
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

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Records</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">All evaluation rows</small>
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
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Student</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiStudent">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">
                            <span id="kpiStudentPct">0%</span> of total
                        </small>
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
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Peer</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiPeer">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">
                            <span id="kpiPeerPct">0%</span> of total
                        </small>
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
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Dept. Head</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiHead">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">
                            <span id="kpiHeadPct">0%</span> of total
                        </small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <?php if (count($deanDepartments) > 1): ?>
                <div class="col-6 col-md-4 col-lg-2">
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

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_ay">School Year</label>
                    <select class="form-select form-select-sm" id="f_ay" data-filter="ay">
                        <option value="">All years</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sem">Semester</label>
                    <select class="form-select form-select-sm" id="f_sem" data-filter="sem">
                        <option value="">All semesters</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_source">Source</label>
                    <select class="form-select form-select-sm" id="f_source" data-filter="source">
                        <option value="">All sources</option>
                        <option value="Student">Student</option>
                        <option value="Peer">Peer</option>
                        <option value="DeptHead">Dept. Head</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_rating">Rating</label>
                    <select class="form-select form-select-sm" id="f_rating" data-filter="rating">
                        <option value="">All ratings</option>
                        <option value="Outstanding">Outstanding</option>
                        <option value="Very Satisfactory">Very Satisfactory</option>
                        <option value="Satisfactory">Satisfactory</option>
                        <option value="Average">Average</option>
                        <option value="Needs Improvement">Needs Improvement</option>
                        <option value="No Rating">No Rating</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts + Table — 2 rows × 2 columns ================= -->
    <div class="row g-3 mb-3 no-print">

        <!-- ROW 1 · COL 1 (wider) — Source Composition -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Source Composition</h5>
                        <span class="badge text-bg-light border" id="compositionBadge">0 total</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:280px;">
                        <canvas id="compositionChart"></canvas>
                        <div class="eh-empty d-none" id="compositionEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No evaluations yet</h6>
                            <p>Composition will appear once records exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 1 · COL 2 (narrower) — Score Distribution -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Score Distribution</h5>
                        <span class="badge text-bg-light border" id="ratingBadge">0 records</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:280px;">
                        <canvas id="ratingChart"></canvas>
                        <div class="eh-empty d-none" id="ratingEmpty">
                            <i class="fas fa-chart-bar"></i>
                            <h6>No rating data yet</h6>
                            <p>Distribution will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2 · COL 1 (narrower) — Evaluations by Term -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Evaluations by Term</h5>
                        <span class="badge text-bg-light border" id="trendBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:280px;">
                        <canvas id="trendChart"></canvas>
                        <div class="eh-empty d-none" id="trendEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No trend data yet</h6>
                            <p>Term trends will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2 · COL 2 (wider) — Evaluation Records -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100 overflow-hidden d-flex flex-column">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-2 py-3">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Evaluation Records</h5>
                        <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#10b981;"></span>Stu
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Peer
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#0d6efd;"></span>DH
                        </span>
                    </div>
                </div>

                <div class="eh-table-scroll flex-grow-1">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 eh-table" style="min-width: 760px;">
                            <thead>
                                <tr>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Date</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Term</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Source</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary text-center">Score</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Rating</th>
                                </tr>
                            </thead>
                            <tbody id="evaluationBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Evaluation pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
                    </nav>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Print-only report -->
<div class="eh-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* Empty states */
    .eh-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .eh-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.6rem; color: var(--sms-text-muted); }
    .eh-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.2rem; font-size: 0.9rem; }
    .eh-empty p { font-size: 0.78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* Scrollbars — match evaluation-summary.php */
    .eh-table-scroll {
        max-height: 420px;
        overflow-y: auto;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(13,110,253,0.3) transparent;
    }
    .eh-table-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .eh-table-scroll::-webkit-scrollbar-track { background: transparent; }
    .eh-table-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,0.3);
        border-radius: 10px;
    }
    .eh-table-scroll::-webkit-scrollbar-thumb:hover {
        background-color: rgba(13,110,253,0.6);
    }

    /* Table */
    .eh-table thead th {
        white-space: nowrap;
        background: var(--sms-table-head-bg);
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .eh-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .eh-table tbody td { padding: 0.6rem 0.65rem; font-size: 0.82rem; vertical-align: middle; }
    .eh-table thead th { padding: 0.6rem 0.65rem; font-size: 0.68rem; }

    /* Source badges */
    .eh-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem; font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .eh-badge::before {
        content: ""; width: 6px; height: 6px; border-radius: 50%;
        background: currentColor; opacity: 0.85;
    }
    .eh-badge--student { background: rgba(16,185,129,0.12); color: #059669; border-color: rgba(16,185,129,0.22); }
    .eh-badge--peer    { background: rgba(245,158,11,0.14); color: #b45309; border-color: rgba(245,158,11,0.24); }
    .eh-badge--head    { background: rgba(13,110,253,0.12); color: #0d6efd; border-color: rgba(13,110,253,0.22); }
    [data-theme="dark"] .eh-badge--student { color: #6ee7b7; }
    [data-theme="dark"] .eh-badge--peer    { color: #fcd34d; }
    [data-theme="dark"] .eh-badge--head    { color: #93c5fd; }

    .eh-rating { font-weight: 600; font-size: 0.72rem; white-space: nowrap; }
    .eh-rating--outstanding { color: #059669; }
    .eh-rating--very        { color: #0d6efd; }
    .eh-rating--satisfactory{ color: #0284c7; }
    .eh-rating--average     { color: #b45309; }
    .eh-rating--needs       { color: #dc2626; }
    .eh-rating--none        { color: #64748b; }

    .eh-score { font-variant-numeric: tabular-nums; }

    /* Privacy blur */
    .privacy-mode .eh-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .eh-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive */
    @media (max-width: 400px) {
        #ehPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #ehPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .eh-table tbody td,
        .eh-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        .eh-empty { inset: 0.5rem; }
        .eh-empty h6 { font-size: 0.82rem; }
        .eh-empty p { font-size: 0.72rem; }
        .eh-table-scroll { max-height: 340px; }
        .eh-badge { font-size: 0.64rem; padding: 0.18rem 0.5rem; }
    }
    @media (max-width: 360px) {
        .eh-table tbody td,
        .eh-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
        .eh-badge { font-size: 0.6rem; padding: 0.15rem 0.42rem; }
        .eh-rating { font-size: 0.66rem; }
    }

    /* Print */
    .eh-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #ehPage { display: none !important; }
        .eh-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .eh-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .eh-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .eh-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .eh-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .eh-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .eh-print-only .print-kpi { flex: 1; text-align: center; }
        .eh-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .eh-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .eh-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .eh-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .eh-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .eh-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .eh-print-only td.eh-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .eh-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .eh-print-only thead { display: table-header-group; }
        .eh-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const EVALUATIONS = <?= json_encode($allEvaluations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES  = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 10;

    let filters = { dept: 'all', ay: '', sem: '', source: '', rating: '' };
    let filteredData = [];
    let currentPage = 1;

    let compositionChart = null;
    let trendChart = null;
    let ratingChart = null;

    /* ============================================================
       Helpers
       ============================================================ */
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3]);
    }

    function fmtDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function ratingClass(label) {
        switch (label) {
            case 'Outstanding':       return 'eh-rating--outstanding';
            case 'Very Satisfactory': return 'eh-rating--very';
            case 'Satisfactory':      return 'eh-rating--satisfactory';
            case 'Average':           return 'eh-rating--average';
            case 'Needs Improvement': return 'eh-rating--needs';
            default:                  return 'eh-rating--none';
        }
    }

    function sourceBadge(src) {
        const map = {
            'Student':  { cls: 'eh-badge--student', label: 'Student' },
            'Peer':     { cls: 'eh-badge--peer',    label: 'Peer' },
            'DeptHead': { cls: 'eh-badge--head',    label: 'Dept. Head' }
        };
        const m = map[src] || map['Student'];
        return '<span class="eh-badge ' + m.cls + '">' + m.label + '</span>';
    }

    function ratingLabelFromScore(score) {
        const n = parseFloat(score);
        if (!n || n <= 0) return 'No Rating';
        if (n >= 4.50) return 'Outstanding';
        if (n >= 3.50) return 'Very Satisfactory';
        if (n >= 2.50) return 'Satisfactory';
        if (n >= 1.50) return 'Average';
        return 'Needs Improvement';
    }

    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }

    /* ============================================================
       Apply filters
       ============================================================ */
    function applyFilters() {
        return EVALUATIONS.filter(r => {
            if (!deptMatches(r)) return false;
            if (filters.ay && r.academic_year !== filters.ay) return false;
            if (filters.sem && r.semester !== filters.sem) return false;
            if (filters.source && r.source_type !== filters.source) return false;

            if (filters.rating) {
                const label = r.rating_label || ratingLabelFromScore(r.composite_score);
                if (label !== filters.rating) return false;
            }
            return true;
        });
    }

    /* ============================================================
       KPIs
       ============================================================ */
    function renderKPIs(rows) {
        const total   = rows.length;
        const student = rows.filter(r => r.source_type === 'Student').length;
        const peer    = rows.filter(r => r.source_type === 'Peer').length;
        const head    = rows.filter(r => r.source_type === 'DeptHead').length;

        document.getElementById('kpiTotal').textContent   = total.toLocaleString();
        document.getElementById('kpiStudent').textContent = student.toLocaleString();
        document.getElementById('kpiPeer').textContent    = peer.toLocaleString();
        document.getElementById('kpiHead').textContent    = head.toLocaleString();

        const pct = n => total > 0 ? Math.round((n / total) * 100) + '%' : '0%';
        document.getElementById('kpiStudentPct').textContent = pct(student);
        document.getElementById('kpiPeerPct').textContent    = pct(peer);
        document.getElementById('kpiHeadPct').textContent    = pct(head);
    }

    /* ============================================================
       Charts — theme aware
       ============================================================ */
    function chartColors() {
        const cs = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.getAttribute('data-theme') === 'dark';
        return {
            text:       cs.getPropertyValue('--sms-chart-text').trim() || (isDark ? '#94a3b8' : '#64748b'),
            textStrong: isDark ? '#e2e8f0' : '#0f172a',
            grid:       cs.getPropertyValue('--sms-chart-grid').trim() || (isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,33,88,0.06)'),
            surface:    isDark ? '#121c34' : '#ffffff'
        };
    }

    function renderCompositionChart(rows) {
        if (typeof Chart === 'undefined') return;
        const c = chartColors();

        const counts = { Student: 0, Peer: 0, DeptHead: 0 };
        rows.forEach(r => { if (counts[r.source_type] !== undefined) counts[r.source_type]++; });

        const labels = ['Student', 'Peer', 'Dept. Head'];
        const data   = [counts.Student, counts.Peer, counts.DeptHead];
        const total  = data.reduce((a, b) => a + b, 0);

        document.getElementById('compositionBadge').textContent = total + ' total';

        const emptyEl = document.getElementById('compositionEmpty');
        const canvas  = document.getElementById('compositionChart');
        if (total === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (compositionChart) { compositionChart.destroy(); compositionChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (compositionChart) compositionChart.destroy();
        compositionChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#f59e0b', '#0d6efd'],
                    borderColor: c.surface,
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 14,
                            font: { size: 12, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    function renderTrendChart(rows) {
        if (typeof Chart === 'undefined') return;
        const c = chartColors();

        const termMap = {};
        rows.forEach(r => {
            const ay  = r.academic_year || '—';
            const sem = r.semester || '—';
            const key = ay + '|' + sem;
            if (!termMap[key]) termMap[key] = { Student: 0, Peer: 0, DeptHead: 0, ay: ay, sem: sem };
            if (termMap[key][r.source_type] !== undefined) termMap[key][r.source_type]++;
        });

        const keys = Object.keys(termMap).sort((a, b) => {
            const A = termMap[a], B = termMap[b];
            if (A.ay !== B.ay) return A.ay < B.ay ? -1 : 1;
            const order = { '1st Semester': 1, '2nd Semester': 2, 'Summer': 3 };
            return (order[A.sem] || 9) - (order[B.sem] || 9);
        });

        const labels = keys.map(k => {
            const t = termMap[k];
            const shortAy = t.ay.length === 9 ? t.ay.slice(0, 4) + '–' + t.ay.slice(5) : t.ay;
            return shortAy + ' · ' + t.sem.replace(' Semester', '');
        });

        document.getElementById('trendBadge').textContent = keys.length + ' period' + (keys.length !== 1 ? 's' : '');

        const emptyEl = document.getElementById('trendEmpty');
        const canvas  = document.getElementById('trendChart');
        if (keys.length === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (trendChart) trendChart.destroy();
        trendChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Student',    data: keys.map(k => termMap[k].Student),  backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, maxBarThickness: 32 },
                    { label: 'Peer',       data: keys.map(k => termMap[k].Peer),     backgroundColor: '#f59e0b', borderRadius: 6, borderSkipped: false, maxBarThickness: 32 },
                    { label: 'Dept. Head', data: keys.map(k => termMap[k].DeptHead), backgroundColor: '#0d6efd', borderRadius: 6, borderSkipped: false, maxBarThickness: 32 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 12,
                            font: { size: 10, weight: '600' }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: c.text, font: { size: 9 }, maxRotation: 45, minRotation: 0 } },
                    y: { stacked: true, beginAtZero: true, ticks: { color: c.text, font: { size: 10 }, precision: 0 }, grid: { color: c.grid } }
                }
            }
        });
    }

    function renderRatingChart(rows) {
        if (typeof Chart === 'undefined') return;
        const c = chartColors();

        const buckets = {
            'Outstanding': 0,
            'Very Satisfactory': 0,
            'Satisfactory': 0,
            'Average': 0,
            'Needs Improvement': 0,
            'No Rating': 0
        };

        rows.forEach(r => {
            const label = r.rating_label || ratingLabelFromScore(r.composite_score);
            if (buckets[label] !== undefined) buckets[label]++;
        });

        const labels = Object.keys(buckets);
        const data   = labels.map(k => buckets[k]);
        const total  = data.reduce((a, b) => a + b, 0);

        document.getElementById('ratingBadge').textContent = total + ' record' + (total !== 1 ? 's' : '');

        const emptyEl = document.getElementById('ratingEmpty');
        const canvas  = document.getElementById('ratingChart');
        if (total === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (ratingChart) { ratingChart.destroy(); ratingChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (ratingChart) ratingChart.destroy();
        ratingChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Records',
                    data: data,
                    backgroundColor: ['#059669', '#0d6efd', '#0284c7', '#f59e0b', '#dc2626', '#64748b'],
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: c.text, font: { size: 9 }, maxRotation: 40, minRotation: 0 } },
                    y: { beginAtZero: true, ticks: { color: c.text, font: { size: 10 }, precision: 0 }, grid: { color: c.grid } }
                }
            }
        });
    }

    /* ============================================================
       Table
       ============================================================ */
    function renderTable(rows) {
        const tbody = document.getElementById('evaluationBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-clipboard-list d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No evaluation records match your filters.
                </td></tr>`;
            document.getElementById('tableSubtitle').textContent = 'No records';
            document.getElementById('pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end   = Math.min(start + PAGE_SIZE, total);
        const page  = rows.slice(start, end);

        tbody.innerHTML = page.map(r => {
            const score = r.composite_score != null ? parseFloat(r.composite_score).toFixed(2) : '—';
            const label = r.rating_label || ratingLabelFromScore(r.composite_score);
            const term  = (r.academic_year || '—') + ' · ' + (r.semester || '—');

            return '<tr>' +
                '<td class="text-nowrap">' + escapeHtml(fmtDate(r.submitted_at)) + '</td>' +
                '<td class="eh-privacy-target">' +
                    '<div class="fw-semibold">' + escapeHtml(r.faculty_name || '—') + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.faculty_no || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary text-nowrap">' + escapeHtml(term) + '</td>' +
                '<td>' + sourceBadge(r.source_type) + '</td>' +
                '<td class="text-center eh-score fw-bold">' + score + '</td>' +
                '<td class="d-none d-md-table-cell"><span class="eh-rating ' + ratingClass(label) + '">' + escapeHtml(label) + '</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' record' + (total !== 1 ? 's' : '');
        document.getElementById('pagerInfo').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total;
        renderPager(totalPages);
    }

    function renderPager(totalPages) {
        const pager = document.getElementById('pager');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, currentPage - 1);
        const wEnd   = Math.min(totalPages, currentPage + 1);
        if (wStart > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
            if (wStart > 2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        if (wEnd < totalPages) {
            if (wEnd < totalPages - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
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
                renderTable(filteredData);
            });
        });
    }

    /* ============================================================
       Print
       ============================================================ */
    function renderPrintReport(rows) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const total   = rows.length;
        const student = rows.filter(r => r.source_type === 'Student').length;
        const peer    = rows.filter(r => r.source_type === 'Peer').length;
        const head    = rows.filter(r => r.source_type === 'DeptHead').length;

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (DEPT_NAMES[filters.dept] || ('Dept #' + filters.dept)));
        if (filters.ay)     filterParts.push('School Year: ' + filters.ay);
        if (filters.sem)    filterParts.push('Semester: ' + filters.sem);
        if (filters.source) filterParts.push('Source: ' + filters.source);
        if (filters.rating) filterParts.push('Rating: ' + filters.rating);
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const bodyRows = rows.map(r => {
            const score = r.composite_score != null ? parseFloat(r.composite_score).toFixed(2) : '—';
            const label = r.rating_label || ratingLabelFromScore(r.composite_score);
            const term  = (r.academic_year || '—') + ' · ' + (r.semester || '—');
            return '<tr>' +
                '<td>' + escapeHtml(fmtDate(r.submitted_at)) + '</td>' +
                '<td class="eh-print-blur">' + escapeHtml(r.faculty_name || '—') + '</td>' +
                '<td>' + escapeHtml(term) + '</td>' +
                '<td>' + escapeHtml(r.source_type === 'DeptHead' ? 'Dept. Head' : r.source_type) + '</td>' +
                '<td style="text-align:center;">' + score + '</td>' +
                '<td>' + escapeHtml(label) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Evaluation History Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total</div>
                    <div class="print-kpi-value">${total}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Student</div>
                    <div class="print-kpi-value">${student}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Peer</div>
                    <div class="print-kpi-value">${peer}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Dept. Head</div>
                    <div class="print-kpi-value">${head}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>Faculty</th><th>Term</th>
                        <th>Source</th><th>Score</th><th>Rating</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="6" style="text-align:center;">No evaluation records in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} record${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Date', 'Faculty', 'Faculty No', 'Department', 'School Year', 'Semester', 'Source', 'Score', 'Rating', 'Evaluations', 'Strength Comment', 'Improvement Comment'];
        const lines = rows.map(r => [
            r.submitted_at,
            r.faculty_name || '',
            r.faculty_no || '',
            r.faculty_dept || '',
            r.academic_year || '',
            r.semester || '',
            r.source_type === 'DeptHead' ? 'Dept. Head' : r.source_type,
            r.composite_score != null ? parseFloat(r.composite_score).toFixed(2) : '',
            r.rating_label || '',
            r.eval_count != null ? r.eval_count : '',
            r.strength_comment || '',
            r.improvement_comment || ''
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'evaluation_history_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Populate year filter
       ============================================================ */
    function populateYearFilter() {
        const years = new Set();
        EVALUATIONS.forEach(r => {
            if (r.academic_year && r.academic_year !== '—') years.add(r.academic_year);
        });
        const sel = document.getElementById('f_ay');
        if (!sel) return;
        Array.from(years).sort().reverse().forEach(ay => {
            const opt = document.createElement('option');
            opt.value = ay; opt.textContent = ay;
            sel.appendChild(opt);
        });
    }

    /* ============================================================
       Render
       ============================================================ */
    function render() {
        filteredData = applyFilters();
        currentPage = 1;
        renderKPIs(filteredData);
        renderCompositionChart(filteredData);
        renderTrendChart(filteredData);
        renderRatingChart(filteredData);
        renderTable(filteredData);
        renderPrintReport(filteredData);
    }

    function bindFilters() {
        document.querySelectorAll('#ehPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', ay: '', sem: '', source: '', rating: '' };
        document.querySelectorAll('#ehPage [data-filter]').forEach(el => {
            el.value = '';
        });
        const deptSel = document.getElementById('f_dept');
        if (deptSel) deptSel.value = 'all';
        render();
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        populateYearFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredData));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanEvaluationHistoryPrivacy';
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

        const obs = new MutationObserver(() => {
            renderCompositionChart(filteredData);
            renderTrendChart(filteredData);
            renderRatingChart(filteredData);
        });
        obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        obs.observe(document.body,            { attributes: true, attributeFilter: ['data-theme'] });

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