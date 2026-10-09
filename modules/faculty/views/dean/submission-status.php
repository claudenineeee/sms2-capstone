<?php
/**
 * SMS 2 - Dean - Submission Status
 * Unified Clearance + Leave submission tracker, RBAC-scoped to dean's departments.
 * Shows per-faculty submission status across both submission types.
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
   LOAD SUBMISSION DATA — scoped to dean's departments
   ============================================================ */
$facultySubmissions = [];
$loadError          = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        /* ---- Faculty roster — Faculty Professor only, excluding anyone
                whose faculty_profiles row marks them as Department Head ---- */
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
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
              AND NOT EXISTS (
                  SELECT 1
                  FROM faculty_db.faculty_profiles fp
                  WHERE (fp.faculty_id = f.faculty_no OR fp.user_id = f.external_user_id)
                    AND fp.position = 'Department Head'
              )
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

        /* ---- Terms lookup ---- */
        $termsStmt = $pdo->query("
            SELECT term_id, academic_year, semester
            FROM faculty_db.academic_terms
        ");
        $termMap = [];
        foreach ($termsStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $termMap[(int) $t['term_id']] = [
                'academic_year' => $t['academic_year'],
                'semester'      => $t['semester'],
            ];
        }

        /* ---- Clearance submissions ---- */
        $clearanceByFaculty = [];
        if (!empty($facultyMap)) {
            $facultyIdList = implode(',', array_keys($facultyMap));
            $clStmt = $pdo->query("
                SELECT
                    cr.faculty_id,
                    cr.term_id,
                    cr.overall_status,
                    cr.form_status,
                    cr.intent_type,
                    cr.submitted_at
                FROM faculty_db.clearance_requests cr
                WHERE cr.faculty_id IN ($facultyIdList)
                ORDER BY cr.submitted_at DESC
            ");
            foreach ($clStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $fid = (int) $r['faculty_id'];
                if (!isset($clearanceByFaculty[$fid])) {
                    $clearanceByFaculty[$fid] = $r;
                }
            }
        }

        /* ---- Leave submissions ---- */
        $leaveByFaculty = [];
        if (!empty($facultyMap)) {
            $facultyIdList = implode(',', array_keys($facultyMap));
            $lvStmt = $pdo->query("
                SELECT
                    lr.faculty_id,
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN lr.approval_status = 'Pending'   THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN lr.approval_status = 'Approved'  THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN lr.approval_status = 'Rejected'  THEN 1 ELSE 0 END) AS rejected_count,
                    MAX(lr.created_at) AS last_submitted
                FROM faculty_db.leave_requests lr
                WHERE lr.faculty_id IN ($facultyIdList)
                GROUP BY lr.faculty_id
            ");
            foreach ($lvStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $leaveByFaculty[(int) $r['faculty_id']] = [
                    'total'          => (int) $r['total_requests'],
                    'pending'        => (int) $r['pending_count'],
                    'approved'       => (int) $r['approved_count'],
                    'rejected'       => (int) $r['rejected_count'],
                    'last_submitted' => $r['last_submitted'],
                ];
            }
        }

        /* ---- Build per-faculty submission rows ---- */
        foreach ($facultyMap as $fid => $fac) {
            $cl = $clearanceByFaculty[$fid] ?? null;
            $lv = $leaveByFaculty[$fid]     ?? null;

            /* Clearance */
            $clearanceStatus = 'Not Submitted';
            $clearanceTerm   = '—';
            $clearanceIntent = '—';
            $clearanceDate   = null;

            if ($cl) {
                $clearanceDate = $cl['submitted_at'] ?? null;
                $tid = (int) ($cl['term_id'] ?? 0);
                $clearanceTerm = ($termMap[$tid]['academic_year'] ?? '—') . ' · ' . ($termMap[$tid]['semester'] ?? '—');
                $clearanceIntent = ucfirst($cl['intent_type'] ?? '—');

                $overall = strtolower($cl['overall_status'] ?? '');
                $formSt  = strtolower($cl['form_status']    ?? '');

                if ($overall === 'cleared' || $overall === 'completed' || $overall === 'approved' || $formSt === 'approved') {
                    $clearanceStatus = 'Approved';
                } elseif (strpos($overall, 'progress') !== false || $formSt === 'submitted') {
                    $clearanceStatus = 'In Progress';
                } elseif ($formSt === 'not submitted') {
                    $clearanceStatus = 'Not Submitted';
                } else {
                    $clearanceStatus = 'Submitted';
                }
            }

            /* Leave */
            $leaveStatus  = 'No Submissions';
            $leaveTotal   = 0;
            $leavePending = 0;
            $leaveLast    = null;

            if ($lv && $lv['total'] > 0) {
                $leaveTotal   = $lv['total'];
                $leavePending = $lv['pending'];
                $leaveLast    = $lv['last_submitted'];

                if ($lv['pending'] > 0) {
                    $leaveStatus = 'Pending';
                } elseif ($lv['rejected'] > 0 && $lv['approved'] === 0) {
                    $leaveStatus = 'Rejected';
                } elseif ($lv['approved'] > 0) {
                    $leaveStatus = 'Approved';
                } else {
                    $leaveStatus = 'Submitted';
                }
            }

            /* Overall */
            $statuses = [$clearanceStatus, $leaveStatus];
            if (in_array('Rejected', $statuses, true)) {
                $overall = 'Rejected';
            } elseif (in_array('Pending', $statuses, true)) {
                $overall = 'Pending';
            } elseif (in_array('In Progress', $statuses, true) || in_array('Submitted', $statuses, true)) {
                $overall = 'In Progress';
            } elseif (in_array('Not Submitted', $statuses, true) || in_array('No Submissions', $statuses, true)) {
                $overall = 'Missing';
            } else {
                $overall = 'Approved';
            }

            $facultySubmissions[] = [
                'faculty_id'       => $fid,
                'faculty_no'       => $fac['faculty_no'],
                'faculty_name'     => $fac['name'],
                'department_id'    => $fac['department_id'],
                'department_name'  => $fac['dept_name'],
                'clearance_status' => $clearanceStatus,
                'clearance_term'   => $clearanceTerm,
                'clearance_intent' => $clearanceIntent,
                'clearance_date'   => $clearanceDate,
                'leave_status'     => $leaveStatus,
                'leave_total'      => $leaveTotal,
                'leave_pending'    => $leavePending,
                'leave_last'       => $leaveLast,
                'overall_status'   => $overall,
            ];
        }

    } catch (Throwable $e) {
        error_log('Submission status load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Submission Status';
$activeModule = 'faculty';
$activePage   = 'submission-status';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Submission Status', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="ssPage">

    <?php if (!$hasDeanDepartments): ?>
        <div class="card border shadow-sm">
            <div class="card-body text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:84px;height:84px;background:rgba(100,116,139,0.10);color:#64748b;font-size:2.2rem;">
                    <i class="fas fa-building-circle-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>
                <p class="text-body-secondary mb-0 mx-auto" style="max-width:420px;">
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
        <h4 class="mb-0 fw-bold">Submission Status Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-inbox text-primary"></i>
                <span>Submission Status</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Clearance &amp; leave submission tracking for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0 small fw-semibold"
                   for="privacyModeToggle" style="cursor:pointer;">
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Faculty</h6>
                        <h4 class="mb-0 fw-bold text-primary" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-primary" style="font-size:0.7rem;">In your scope</small>
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
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Cleared</h6>
                        <h4 class="mb-0 fw-bold text-success" id="kpiApproved">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-success" style="font-size:0.7rem;">
                            <span id="kpiApprovedPct">0%</span> of faculty
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
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending</h6>
                        <h4 class="mb-0 fw-bold text-warning" id="kpiPending">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-warning" style="font-size:0.7rem;">
                            <span id="kpiPendingPct">0%</span> awaiting action
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
                        <i class="fas fa-circle-exclamation"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Missing</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiMissing">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">
                            <span id="kpiMissingPct">0%</span> not submitted
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
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_type">Type</label>
                    <select class="form-select form-select-sm" id="f_type" data-filter="type">
                        <option value="">All types</option>
                        <option value="clearance">Clearance only</option>
                        <option value="leave">Leave only</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_clearance">Clearance</label>
                    <select class="form-select form-select-sm" id="f_clearance" data-filter="clearance">
                        <option value="">Any</option>
                        <option value="Approved">Approved</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Not Submitted">Not Submitted</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_leave">Leave</label>
                    <select class="form-select form-select-sm" id="f_leave" data-filter="leave">
                        <option value="">Any</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Rejected">Rejected</option>
                        <option value="No Submissions">No Submissions</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_overall">Overall</label>
                    <select class="form-select form-select-sm" id="f_overall" data-filter="overall">
                        <option value="">All statuses</option>
                        <option value="Approved">Approved</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Missing">Missing</option>
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

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">

        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Completion Overview</h5>
                            <p class="text-body-secondary small mb-0">Overall submission state across your faculty</p>
                        </div>
                        <span class="badge text-bg-light border" id="donutBadge">0 faculty</span>
                    </div>

                    <div class="row g-4 align-items-center">
                        <!-- Left: chart -->
                        <div class="col-12 col-lg-5">
                            <div class="position-relative w-100" style="min-height:300px;">
                                <canvas id="donutChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none" id="donutCenter">
                                    <div class="fw-bold lh-1" style="font-size:1.6rem;" id="donutCenterValue">0%</div>
                                    <div class="text-uppercase fw-bold text-body-secondary" style="font-size:0.62rem;letter-spacing:0.08em;" id="donutCenterLabel">Approved</div>
                                </div>
                                <div class="ss-empty d-none" id="donutEmpty">
                                    <i class="fas fa-chart-pie"></i>
                                    <h6>No submission data</h6>
                                    <p>Overview will appear once records exist.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: status summary — institutional table-like rows -->
                        <div class="col-12 col-lg-7">
                            <div class="ss-stat-list" id="statusGrid"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Faculty Submission Records</h5>
                <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
            </div>
        </div>

        <div class="ss-scroll" style="max-height:520px;overflow-y:auto;">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="min-width: 960px;">
                    <thead class="sticky-top">
                        <tr>
                            <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                            <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Department</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Clearance</th>
                            <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Term</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Leave</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center d-none d-sm-table-cell">Leaves</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Overall</th>
                        </tr>
                    </thead>
                    <tbody id="submissionBody"></tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
            <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
            <nav aria-label="Submission pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="ss-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* ============================================================
       Empty overlay
       ============================================================ */
    .ss-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; pointer-events: none;
    }
    .ss-empty i { font-size: 2rem; opacity: .35; margin-bottom: .6rem; color: var(--sms-text-muted); }
    .ss-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: .2rem; font-size: .9rem; }
    .ss-empty p { font-size: .78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* ============================================================
       Custom scrollbar
       ============================================================ */
    .ss-scroll { scrollbar-width: thin; scrollbar-color: rgba(13,110,253,.3) transparent; }
    .ss-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .ss-scroll::-webkit-scrollbar-track { background: transparent; }
    .ss-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,.3);
        border-radius: 10px;
    }
    .ss-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(13,110,253,.6); }

    /* ============================================================
       Table badges — readable, bordered chips (no AI-pill look)
       ============================================================ */
    .ss-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .55rem;
        border-radius: 4px;
        font-size: .72rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: nowrap;
        border: 1px solid;
    }
    .ss-badge .ss-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
        background: currentColor;
    }

    /* Light theme palette */
    .ss-badge--approved       { color: #0f5132; background: #d1e7dd; border-color: #a3cfbb; }
    .ss-badge--pending        { color: #664d03; background: #fff3cd; border-color: #ffe69c; }
    .ss-badge--inprogress     { color: #055160; background: #cff4fc; border-color: #9eeaf9; }
    .ss-badge--submitted      { color: #084298; background: #cfe2ff; border-color: #9ec5fe; }
    .ss-badge--rejected       { color: #842029; background: #f8d7da; border-color: #f1aeb5; }
    .ss-badge--missing        { color: #842029; background: #f8d7da; border-color: #f1aeb5; }
    .ss-badge--notsubmitted   { color: #842029; background: #f8d7da; border-color: #f1aeb5; }
    .ss-badge--nosubmissions  { color: #41464b; background: #e2e3e5; border-color: #c4c8cb; }

    /* Dark theme — deeper background + stronger text for readability */
    [data-theme="dark"] .ss-badge--approved      { color: #6ee7b7; background: rgba(16,185,129,.15); border-color: rgba(16,185,129,.4); }
    [data-theme="dark"] .ss-badge--pending       { color: #fcd34d; background: rgba(245,158,11,.15); border-color: rgba(245,158,11,.4); }
    [data-theme="dark"] .ss-badge--inprogress    { color: #7dd3fc; background: rgba(13,202,240,.15); border-color: rgba(13,202,240,.4); }
    [data-theme="dark"] .ss-badge--submitted     { color: #93c5fd; background: rgba(13,110,253,.15); border-color: rgba(13,110,253,.4); }
    [data-theme="dark"] .ss-badge--rejected      { color: #fca5a5; background: rgba(220,53,69,.15); border-color: rgba(220,53,69,.4); }
    [data-theme="dark"] .ss-badge--missing       { color: #fca5a5; background: rgba(220,53,69,.15); border-color: rgba(220,53,69,.4); }
    [data-theme="dark"] .ss-badge--notsubmitted  { color: #fca5a5; background: rgba(220,53,69,.15); border-color: rgba(220,53,69,.4); }
    [data-theme="dark"] .ss-badge--nosubmissions { color: #cbd5e1; background: rgba(100,116,139,.15); border-color: rgba(100,116,139,.4); }

    /* ============================================================
       Right-side status list — institutional data-list look
       ============================================================ */
    .ss-stat-list {
        border: 1px solid var(--sms-border-soft);
        border-radius: 10px;
        overflow: hidden;
        background: var(--sms-surface-muted);
    }
    .ss-stat-row {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.7rem 0.9rem;
        border-bottom: 1px solid var(--sms-border-soft);
    }
    .ss-stat-row:last-child { border-bottom: 0; }
    .ss-stat-row.ss-stat-row--muted { opacity: 0.55; }

    .ss-stat-marker {
        width: 10px; height: 10px;
        border-radius: 2px;
        flex-shrink: 0;
    }
    .ss-stat-marker--approved    { background: #10b981; }
    .ss-stat-marker--inprogress  { background: #0dcaf0; }
    .ss-stat-marker--pending     { background: #f59e0b; }
    .ss-stat-marker--rejected    { background: #ef4444; }
    .ss-stat-marker--missing     { background: #ef4444; }

    .ss-stat-label {
        flex: 1;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--sms-heading);
        letter-spacing: 0.01em;
    }
    .ss-stat-count {
        font-variant-numeric: tabular-nums;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--sms-heading);
        min-width: 26px;
        text-align: right;
    }
    .ss-stat-pct {
        font-variant-numeric: tabular-nums;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--sms-text-muted);
        min-width: 44px;
        text-align: right;
    }

    /* Privacy blur */
    .privacy-mode .ss-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter .15s ease;
    }
    .privacy-mode .ss-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive */
    @media (max-width: 400px) {
        #ssPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #ssPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: .7rem .6rem .7rem .9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: .62rem !important; letter-spacing: .03em !important; }
        .stat-card small { font-size: .65rem !important; }
        #donutCenterValue { font-size: 1.35rem !important; }
        .ss-badge { font-size: .66rem; padding: .16rem .5rem; }
        .ss-stat-row { padding: .55rem .7rem; gap: .6rem; }
        .ss-stat-label { font-size: .76rem; }
        .ss-stat-count { font-size: .88rem; }
    }

    /* Print */
    .ss-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #ssPage { display: none !important; }
        .ss-print-only {
            display: block !important;
            padding: .4in .35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .ss-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .ss-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .ss-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .ss-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .ss-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .ss-print-only .print-kpi { flex: 1; text-align: center; }
        .ss-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: #555;
        }
        .ss-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .ss-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .ss-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: .4px; font-weight: 700;
        }
        .ss-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .ss-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .ss-print-only td.ss-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .ss-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .ss-print-only thead { display: table-header-group; }
        .ss-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const SUBMISSIONS = <?= json_encode($facultySubmissions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PAGE_SIZE = 10;

    let filters = { dept: 'all', type: '', clearance: '', leave: '', overall: '' };
    let filteredData = [];
    let currentPage = 1;

    let donutChart = null;

    /* ============================================================
       Helpers
       ============================================================ */
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    const STATUS_CLASS = {
        'Approved':       'ss-badge--approved',
        'Pending':        'ss-badge--pending',
        'In Progress':    'ss-badge--inprogress',
        'Submitted':      'ss-badge--submitted',
        'Rejected':       'ss-badge--rejected',
        'Not Submitted':  'ss-badge--notsubmitted',
        'No Submissions': 'ss-badge--nosubmissions',
        'Missing':        'ss-badge--missing'
    };

    function statusPill(label) {
        const cls = STATUS_CLASS[label] || STATUS_CLASS['No Submissions'];
        return '<span class="ss-badge ' + cls + '"><span class="ss-dot"></span>' + escapeHtml(label) + '</span>';
    }

    /* ============================================================
       Filters
       ============================================================ */
    function applyFilters() {
        return SUBMISSIONS.filter(r => {
            if (filters.dept !== 'all' && String(r.department_id) !== String(filters.dept)) return false;
            if (filters.type === 'clearance' && r.clearance_status === 'Not Submitted') return false;
            if (filters.type === 'leave'     && r.leave_status === 'No Submissions')    return false;
            if (filters.clearance && r.clearance_status !== filters.clearance) return false;
            if (filters.leave     && r.leave_status     !== filters.leave)     return false;
            if (filters.overall   && r.overall_status   !== filters.overall)   return false;
            return true;
        });
    }

    /* ============================================================
       KPIs
       ============================================================ */
    function renderKPIs(rows) {
        const total    = rows.length;
        const approved = rows.filter(r => r.overall_status === 'Approved').length;
        const pending  = rows.filter(r => r.overall_status === 'Pending' || r.overall_status === 'In Progress').length;
        const missing  = rows.filter(r => r.overall_status === 'Missing' || r.overall_status === 'Rejected').length;

        document.getElementById('kpiTotal').textContent    = total.toLocaleString();
        document.getElementById('kpiApproved').textContent = approved.toLocaleString();
        document.getElementById('kpiPending').textContent  = pending.toLocaleString();
        document.getElementById('kpiMissing').textContent  = missing.toLocaleString();

        const pct = n => total > 0 ? Math.round((n / total) * 100) + '%' : '0%';
        document.getElementById('kpiApprovedPct').textContent = pct(approved);
        document.getElementById('kpiPendingPct').textContent  = pct(pending);
        document.getElementById('kpiMissingPct').textContent  = pct(missing);
    }

    /* ============================================================
       Donut
       ============================================================ */
    function chartColors() {
        const cs = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.getAttribute('data-theme') === 'dark';
        return {
            text:       cs.getPropertyValue('--sms-chart-text').trim() || (isDark ? '#94a3b8' : '#64748b'),
            textStrong: isDark ? '#e2e8f0' : '#0f172a',
            surface:    isDark ? '#121c34' : '#ffffff'
        };
    }

    function renderDonut(rows) {
        const total = rows.length;
        document.getElementById('donutBadge').textContent = total + ' faculty';

        const counts = { 'Approved': 0, 'In Progress': 0, 'Pending': 0, 'Rejected': 0, 'Missing': 0 };
        rows.forEach(r => {
            const s = r.overall_status;
            if (counts[s] !== undefined) counts[s]++;
            else if (s === 'Submitted') counts['In Progress']++;
        });

        const labels = Object.keys(counts);
        const data   = labels.map(k => counts[k]);
        const sum    = data.reduce((a, b) => a + b, 0);

        const pct = sum > 0 ? Math.round((counts['Approved'] / sum) * 100) : 0;
        document.getElementById('donutCenterValue').textContent = pct + '%';
        document.getElementById('donutCenterLabel').textContent = 'Approved';

        if (typeof Chart === 'undefined') return;
        const c = chartColors();
        const emptyEl = document.getElementById('donutEmpty');
        const canvas  = document.getElementById('donutChart');
        const center  = document.getElementById('donutCenter');

        if (sum === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            center.style.visibility = 'hidden';
            if (donutChart) { donutChart.destroy(); donutChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';
        center.style.visibility = 'visible';

        if (donutChart) donutChart.destroy();
        donutChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#0dcaf0', '#f59e0b', '#ef4444', '#64748b'],
                    borderColor: c.surface,
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 12, font: { size: 11, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    /* ============================================================
       Status list — data-list look (not generic progress bars)
       ============================================================ */
    function renderStatusGrid(rows) {
        const grid  = document.getElementById('statusGrid');
        const total = rows.length;

        const buckets = [
            { key: 'Approved',    label: 'Approved',    marker: 'ss-stat-marker--approved' },
            { key: 'In Progress', label: 'In Progress', marker: 'ss-stat-marker--inprogress' },
            { key: 'Pending',     label: 'Pending',     marker: 'ss-stat-marker--pending' },
            { key: 'Rejected',    label: 'Rejected',    marker: 'ss-stat-marker--rejected' },
            { key: 'Missing',     label: 'Missing',     marker: 'ss-stat-marker--missing' }
        ];

        const counts = {};
        buckets.forEach(b => counts[b.key] = 0);
        rows.forEach(r => {
            const s = r.overall_status;
            if (counts[s] !== undefined) counts[s]++;
            else if (s === 'Submitted') counts['In Progress']++;
        });

        grid.innerHTML = buckets.map(b => {
            const c   = counts[b.key];
            const pct = total > 0 ? Math.round((c / total) * 100) : 0;
            const muted = c === 0 ? ' ss-stat-row--muted' : '';

            return `
                <div class="ss-stat-row${muted}">
                    <span class="ss-stat-marker ${b.marker}"></span>
                    <span class="ss-stat-label">${b.label}</span>
                    <span class="ss-stat-count">${c}</span>
                    <span class="ss-stat-pct">${pct}%</span>
                </div>
            `;
        }).join('');
    }

    /* ============================================================
       Table
       ============================================================ */
    function renderTable(rows) {
        const tbody = document.getElementById('submissionBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
                    No submission records match your filters.
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
            return '<tr>' +
                '<td class="ss-privacy-target">' +
                    '<div class="fw-semibold">' + escapeHtml(r.faculty_name || '—') + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.faculty_no || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(r.department_name) + '</td>' +
                '<td>' + statusPill(r.clearance_status) + '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary text-nowrap">' + escapeHtml(r.clearance_term) + '</td>' +
                '<td>' + statusPill(r.leave_status) + '</td>' +
                '<td class="d-none d-sm-table-cell text-center">' + (r.leave_total > 0 ? r.leave_total : '—') + '</td>' +
                '<td>' + statusPill(r.overall_status) + '</td>' +
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

        const total    = rows.length;
        const approved = rows.filter(r => r.overall_status === 'Approved').length;
        const pending  = rows.filter(r => r.overall_status === 'Pending' || r.overall_status === 'In Progress').length;
        const missing  = rows.filter(r => r.overall_status === 'Missing' || r.overall_status === 'Rejected').length;

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (rows[0]?.department_name || ('#' + filters.dept)));
        if (filters.type) filterParts.push('Type: ' + filters.type);
        if (filters.clearance) filterParts.push('Clearance: ' + filters.clearance);
        if (filters.leave) filterParts.push('Leave: ' + filters.leave);
        if (filters.overall) filterParts.push('Overall: ' + filters.overall);
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td class="ss-print-blur">' + escapeHtml(r.faculty_name) + '</td>' +
                '<td>' + escapeHtml(r.department_name) + '</td>' +
                '<td>' + escapeHtml(r.clearance_status) + '</td>' +
                '<td>' + escapeHtml(r.clearance_term) + '</td>' +
                '<td>' + escapeHtml(r.leave_status) + '</td>' +
                '<td style="text-align:center;">' + (r.leave_total > 0 ? r.leave_total : '—') + '</td>' +
                '<td>' + escapeHtml(r.overall_status) + '</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Submission Status Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi"><div class="print-kpi-label">Faculty</div><div class="print-kpi-value">${total}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Cleared</div><div class="print-kpi-value">${approved}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Pending</div><div class="print-kpi-value">${pending}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Missing</div><div class="print-kpi-value">${missing}</div></div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Department</th><th>Clearance</th><th>Term</th>
                        <th>Leave</th><th>Leaves</th><th>Overall</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="7" style="text-align:center;">No submission records in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} faculty record${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Faculty', 'Faculty No', 'Department', 'Clearance Status', 'Clearance Term', 'Clearance Intent', 'Clearance Date', 'Leave Status', 'Leave Count', 'Leave Pending', 'Leave Last', 'Overall Status'];
        const lines = rows.map(r => [
            r.faculty_name,
            r.faculty_no,
            r.department_name,
            r.clearance_status,
            r.clearance_term,
            r.clearance_intent,
            r.clearance_date || '',
            r.leave_status,
            r.leave_total,
            r.leave_pending,
            r.leave_last || '',
            r.overall_status
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'submission_status_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Render
       ============================================================ */
    function render() {
        filteredData = applyFilters();
        currentPage = 1;
        renderKPIs(filteredData);
        renderDonut(filteredData);
        renderStatusGrid(filteredData);
        renderTable(filteredData);
        renderPrintReport(filteredData);
    }

    function bindFilters() {
        document.querySelectorAll('#ssPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', type: '', clearance: '', leave: '', overall: '' };
        document.querySelectorAll('#ssPage [data-filter]').forEach(el => { el.value = ''; });
        const deptSel = document.getElementById('f_dept');
        if (deptSel) deptSel.value = 'all';
        render();
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        bindFilters();
        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredData));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanSubmissionStatusPrivacy';
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

        const obs = new MutationObserver(() => { renderDonut(filteredData); });
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