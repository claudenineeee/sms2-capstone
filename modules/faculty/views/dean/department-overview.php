<?php
/**
 * SMS 2 - Dean - Department Overview
 * Landing dashboard for the Dean, scoped to their RBAC-assigned departments.
 * RBAC source: faculty_db.faculty_profile_department_assignments
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../includes/dean_rbac.php';

requireAuth();

/* ============================================================
   DEAN RBAC — fetch assigned departments (from the helper)
   ============================================================ */
$deanUserId          = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);
$deanDepartments     = getDeanAssignedDepartments($deanUserId);
$deanDepartmentNames = getDeanDepartmentNames($deanDepartments);
$hasDeanDepartments  = !empty($deanDepartments);

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
   LOAD DATA — scoped to dean's departments (via faculty.department_id)
   ============================================================ */
$allFaculty      = [];
$allAttendance   = [];
$allLeaves       = [];
$allEvals        = [];
$loadError       = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ---- Faculty in scope (via faculty.department_id) ---- */
        $facStmt = $pdo->query("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
                f.email,
                f.department_id,
                f.position,
                f.employment_status,
                f.profile_status,
                d.code AS dept_code,
                d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $allFaculty = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Recent attendance (last 90 days) — filter by faculty.department_id ---- */
        $attStmt = $pdo->query("
            SELECT
                a.attendance_id,
                a.faculty_id,
                a.attendance_date,
                a.time_in,
                a.time_out,
                a.status,
                a.hours_rendered,
                a.notes,
                f.department_id
            FROM faculty_db.attendance_records a
            INNER JOIN faculty_db.faculty f ON f.faculty_id = a.faculty_id
            WHERE f.department_id IN ($deptList)
              AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            ORDER BY a.attendance_date DESC
        ");
        $allAttendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Pending leaves in scope ---- */
        $leaveStmt = $pdo->query("
            SELECT
                lr.id,
                lr.request_ref,
                lr.faculty_id,
                lr.leave_type,
                lr.start_date,
                lr.end_date,
                lr.total_days,
                lr.approval_status,
                lr.created_at,
                f.department_id,
                f.first_name,
                f.last_name
            FROM faculty_db.leave_requests lr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
            WHERE f.department_id IN ($deptList)
              AND lr.approval_status IN ('Pending')
            ORDER BY lr.created_at DESC
            LIMIT 200
        ");
        $allLeaves = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Evaluations in scope (last 12 months) ---- */
        $evalStmt = $pdo->query("
            SELECT
                e.evaluation_id,
                e.faculty_id,
                e.source_type,
                e.composite_score,
                e.rating_label,
                e.submitted_at,
                f.department_id
            FROM faculty_db.evaluations e
            INNER JOIN faculty_db.faculty f ON f.faculty_id = e.faculty_id
            WHERE f.department_id IN ($deptList)
              AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY e.submitted_at DESC
        ");
        $allEvals = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log('Dean department overview load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Department Overview';
$activeModule = 'faculty';
$activePage   = 'department-overview';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Department Overview', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="doPage">

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
        <h4 class="mb-0 fw-bold">Department Overview Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Faculty identities are blurred for privacy.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Department Overview</h1>
            <p class="text-body-secondary mb-0">
                Snapshot of your department<?= count($deanDepartments) > 1 ? 's' : '' ?> — faculty, attendance, pending approvals, and evaluation progress.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="exportCsvBtn" type="button">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">CSV</span>
            </button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="printBtn" type="button">
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
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Faculty</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiFaculty">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">Active members</small>
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
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Approvals</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiPending">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Needs your action</small>
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
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Present Today</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiPresent">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">
                            <span id="kpiPresentRate">0%</span> of faculty
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
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Avg Eval Score</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiAvgScore">0.00</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Last 12 months</small>
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
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_dept">Department</label>
                    <select class="form-select form-select-sm" id="f_dept">
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
                    <select class="form-select form-select-sm" id="f_sy">
                        <option value="">All years</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_month">Month</label>
                    <select class="form-select form-select-sm" id="f_month">
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
                    <select class="form-select form-select-sm" id="f_week">
                        <option value="">All weeks</option>
                        <option value="1">Week 1 · 1–7</option><option value="2">Week 2 · 8–14</option>
                        <option value="3">Week 3 · 15–21</option><option value="4">Week 4 · 22–28</option>
                        <option value="5">Week 5 · 29–31</option>
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

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Attendance Trend</h5>
                            <p class="text-body-secondary small mb-0">Department-wide present rate over time</p>
                        </div>
                        <span class="badge text-bg-light border" id="trendCountBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:280px;">
                        <canvas id="attTrendChart"></canvas>
                        <div class="do-empty d-none" id="trendEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No attendance data yet</h6>
                            <p>Trend will appear once attendance is recorded.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Evaluation Distribution</h5>
                            <p class="text-body-secondary small mb-0">By rating tier</p>
                        </div>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:280px;">
                        <canvas id="evalChart"></canvas>
                        <div class="do-empty d-none" id="evalEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No evaluations yet</h6>
                            <p>Distribution will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Attention + Roster ================= -->
    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="card-title mb-1 fw-bold">Needs Your Attention</h5>
                    <p class="text-body-secondary small mb-0">Pending items from your department</p>
                </div>
                <div class="card-body p-0">
                    <div id="attentionList" class="do-attention-list"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card border shadow-sm h-100 overflow-hidden">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Faculty Roster</h5>
                        <p class="text-body-secondary small mb-0" id="rosterCount">0 faculty</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 do-roster-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Department</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell">Position</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Status</th>
                            </tr>
                        </thead>
                        <tbody id="rosterBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="do-print-only" id="printTable" aria-hidden="true"></div>

<style>
    .do-empty {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; padding: 1rem;
        pointer-events: none;
    }
    .do-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.75rem; color: var(--sms-text-muted); }
    .do-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.25rem; }
    .do-empty p { font-size: 0.82rem; color: var(--sms-text-muted); margin: 0; max-width: 280px; }

    .privacy-mode .do-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .do-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    .do-roster-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .do-roster-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .do-roster-table tbody td { padding: 0.6rem 0.75rem; font-size: 0.85rem; }
    .do-roster-table thead th { padding: 0.65rem 0.75rem; font-size: 0.7rem; }

    .do-attention-list { max-height: 480px; overflow-y: auto; }
    .do-attention-item {
        display: flex; align-items: flex-start; gap: 0.85rem;
        padding: 0.9rem 1.15rem;
        border-bottom: 1px solid var(--sms-border-soft);
        transition: background 0.15s ease;
    }
    .do-attention-item:last-child { border-bottom: none; }
    .do-attention-item:hover { background: var(--sms-dropdown-hover); }
    .do-attention-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.95rem; flex-shrink: 0;
    }
    .do-attention-icon--leave { background: rgba(6,182,212,0.14); color: #0891b2; }
    .do-attention-body { min-width: 0; flex: 1; }
    .do-attention-title {
        font-size: 0.88rem; font-weight: 700;
        color: var(--sms-heading);
        margin: 0 0 0.15rem;
        overflow-wrap: anywhere;
    }
    .do-attention-meta { font-size: 0.75rem; color: var(--sms-text-muted); }
    .do-attention-empty {
        padding: 2.5rem 1.15rem; text-align: center;
        color: var(--sms-text-muted);
    }
    .do-attention-empty i {
        font-size: 2rem; opacity: 0.35; display: block; margin-bottom: 0.5rem;
    }

    @media (max-width: 400px) {
        #doPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #doPage h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .do-roster-table tbody td,
        .do-roster-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        #doPage .card-title { font-size: 0.9rem; }
    }
    @media (max-width: 360px) {
        .do-roster-table tbody td,
        .do-roster-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* Print-only */
    .do-print-only { display: none; }

    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }

        #doPage { display: none !important; }

        .do-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .do-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .do-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .do-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .do-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .do-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .do-print-only .print-kpi { flex: 1; text-align: center; }
        .do-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .do-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .do-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .do-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .do-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .do-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .do-print-only td.do-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .do-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .do-print-only thead { display: table-header-group; }
        .do-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const FACULTY    = <?= json_encode($allFaculty, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const ATTENDANCE = <?= json_encode($allAttendance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const LEAVES     = <?= json_encode($allLeaves, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const EVALS      = <?= json_encode($allEvals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    let currentFilters = { dept: 'all', sy: '', month: '', week: '' };
    let trendChart = null, evalChart = null;

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
        const name = (r.first_name + mid + ' ' + r.last_name).trim();
        return name || '—';
    }
    function fmtDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function getSyRange(sy) {
        const m = /^(\d{4})-(\d{4})$/.exec(sy);
        if (!m) return null;
        const y = +m[1];
        return { start: new Date(y, 5, 1), end: new Date(y + 1, 4, 31, 23, 59, 59) };
    }
    function normalizeStatus(s) {
        const l = (s || '').toLowerCase();
        if (l === 'approved' || l === 'completed' || l === 'current') return 'Approved';
        if (l === 'pending') return 'Pending';
        if (l === 'rejected') return 'Rejected';
        return s || 'Unknown';
    }
    function normalizeRating(label) {
        const l = (label || '').toLowerCase();
        if (l.indexOf('outstanding') !== -1)          return 'Outstanding';
        if (l.indexOf('very satisfactory') !== -1)    return 'Very Satisfactory';
        if (l.indexOf('satisfactory') !== -1)         return 'Satisfactory';
        if (l.indexOf('needs') !== -1)                return 'Needs Improvement';
        return label || 'Unrated';
    }

    function dateMatches(str) {
        const d = parseDate(str);
        if (!d) return false;
        if (currentFilters.sy) {
            const range = getSyRange(currentFilters.sy);
            if (!range || d < range.start || d > range.end) return false;
        }
        if (currentFilters.month && (d.getMonth() + 1) !== +currentFilters.month) return false;
        if (currentFilters.week) {
            const day = d.getDate();
            const w = +currentFilters.week;
            if (w === 1 && !(day >= 1  && day <= 7))  return false;
            if (w === 2 && !(day >= 8  && day <= 14)) return false;
            if (w === 3 && !(day >= 15 && day <= 21)) return false;
            if (w === 4 && !(day >= 22 && day <= 28)) return false;
            if (w === 5 && !(day >= 29))              return false;
        }
        return true;
    }
    function deptMatches(row) {
        if (currentFilters.dept === 'all') return true;
        return String(row.department_id) === String(currentFilters.dept);
    }

    function render() {
        renderKPIs();
        renderCharts();
        renderAttention();
        renderRoster();
        renderPrintTable();
    }

    function renderKPIs() {
        const faculty  = FACULTY.filter(deptMatches);
        const att      = ATTENDANCE.filter(function (r) { return deptMatches(r) && dateMatches(r.attendance_date); });
        const leaves   = LEAVES.filter(function (r) { return deptMatches(r) && dateMatches(r.created_at); });
        const evals    = EVALS.filter(function (r) { return deptMatches(r) && dateMatches(r.submitted_at); });

        const today = new Date().toISOString().slice(0, 10);
        const presentToday = att.filter(function (r) { return r.attendance_date === today && r.status === 'Present'; }).length;
        const pendingLeaves = leaves.filter(function (r) { return normalizeStatus(r.approval_status) === 'Pending'; }).length;

        let sumScore = 0, scoreCount = 0;
        evals.forEach(function (r) {
            const s = parseFloat(r.composite_score);
            if (!isNaN(s)) { sumScore += s; scoreCount++; }
        });
        const avgScore = scoreCount > 0 ? (sumScore / scoreCount) : 0;

        document.getElementById('kpiFaculty').textContent    = faculty.length.toLocaleString();
        document.getElementById('kpiPending').textContent    = pendingLeaves.toLocaleString();
        document.getElementById('kpiPresent').textContent    = presentToday.toLocaleString();
        document.getElementById('kpiAvgScore').textContent   = avgScore.toFixed(2);
        document.getElementById('kpiPresentRate').textContent =
            (faculty.length > 0 ? Math.round((presentToday / faculty.length) * 100) : 0) + '%';
    }

    function getChartColors() {
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

    function renderCharts() {
        if (typeof Chart === 'undefined') return;
        const c = getChartColors();
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily || 'Inter, sans-serif';
        Chart.defaults.color = c.text;

        const legendCfg = {
            position: 'bottom',
            labels: {
                color: c.textStrong,
                boxWidth: 10, boxHeight: 10,
                usePointStyle: true, pointStyle: 'circle',
                padding: 16,
                font: { size: 12, weight: '600' }
            }
        };

        /* ---- Attendance trend ---- */
        const att = ATTENDANCE.filter(function (r) { return deptMatches(r) && dateMatches(r.attendance_date); });
        const monthMap = {};
        att.forEach(function (r) {
            const d = parseDate(r.attendance_date);
            if (!d) return;
            const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            if (!monthMap[key]) monthMap[key] = { present: 0, total: 0 };
            monthMap[key].total++;
            if (r.status === 'Present') monthMap[key].present++;
        });
        const mKeys = Object.keys(monthMap).sort();
        const labels = mKeys.map(function (k) {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: 'numeric' });
        });
        const rates = mKeys.map(function (k) {
            const m = monthMap[k];
            return m.total > 0 ? +((m.present / m.total) * 100).toFixed(1) : 0;
        });

        const trendEmpty = document.getElementById('trendEmpty');
        const trendCanvas = document.getElementById('attTrendChart');
        if (labels.length === 0) {
            trendEmpty.classList.remove('d-none');
            trendCanvas.style.visibility = 'hidden';
            document.getElementById('trendCountBadge').textContent = '0 periods';
        } else {
            trendEmpty.classList.add('d-none');
            trendCanvas.style.visibility = 'visible';
            document.getElementById('trendCountBadge').textContent =
                labels.length + ' period' + (labels.length !== 1 ? 's' : '');
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(trendCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Present Rate (%)',
                        data: rates,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.12)',
                        tension: 0.35, fill: true,
                        pointRadius: 4, pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                        y: { beginAtZero: true, suggestedMax: 100,
                             ticks: { color: c.text, font: { size: 11 }, callback: function (v) { return v + '%'; } },
                             grid: { color: c.grid } }
                    }
                }
            });
        }

        /* ---- Evaluation distribution ---- */
        const evals = EVALS.filter(function (r) { return deptMatches(r) && dateMatches(r.submitted_at); });
        const tiers = { 'Outstanding': 0, 'Very Satisfactory': 0, 'Satisfactory': 0, 'Needs Improvement': 0 };
        evals.forEach(function (r) {
            const t = normalizeRating(r.rating_label);
            if (tiers[t] !== undefined) tiers[t]++;
        });
        const tierLabels = Object.keys(tiers);
        const tierData   = tierLabels.map(function (k) { return tiers[k]; });
        const tierTotal  = tierData.reduce(function (a, b) { return a + b; }, 0);

        const evalEmpty = document.getElementById('evalEmpty');
        const evalCanvas = document.getElementById('evalChart');
        if (tierTotal === 0) {
            evalEmpty.classList.remove('d-none');
            evalCanvas.style.visibility = 'hidden';
        } else {
            evalEmpty.classList.add('d-none');
            evalCanvas.style.visibility = 'visible';
            if (evalChart) evalChart.destroy();
            evalChart = new Chart(evalCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: tierLabels,
                    datasets: [{
                        data: tierData,
                        backgroundColor: ['#10b981', '#06b6d4', '#f59e0b', '#ef4444'],
                        borderColor: c.surface,
                        borderWidth: 3,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '68%',
                    plugins: { legend: legendCfg }
                }
            });
        }
    }

    function renderAttention() {
        const host = document.getElementById('attentionList');
        const leaves = LEAVES.filter(function (r) { return deptMatches(r) && dateMatches(r.created_at); });

        if (leaves.length === 0) {
            host.innerHTML = `
                <div class="do-attention-empty">
                    <i class="fas fa-check-circle"></i>
                    <div class="fw-bold text-body-emphasis">All caught up</div>
                    <div class="small">No pending items from your department.</div>
                </div>`;
            return;
        }

        host.innerHTML = leaves.slice(0, 8).map(function (l) {
            return `
                <div class="do-attention-item">
                    <div class="do-attention-icon do-attention-icon--leave">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div class="do-attention-body">
                        <div class="do-attention-title do-privacy-target">${escapeHtml(fullName(l))}</div>
                        <div class="do-attention-meta">
                            ${escapeHtml(l.leave_type || 'Leave')} ·
                            ${escapeHtml(fmtDate(l.start_date))} – ${escapeHtml(fmtDate(l.end_date))} ·
                            ${(l.total_days || 0)} day${l.total_days !== 1 ? 's' : ''}
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderRoster() {
        const tbody = document.getElementById('rosterBody');
        const faculty = FACULTY.filter(deptMatches);

        if (faculty.length === 0) {
            tbody.innerHTML = `
                <tr><td colspan="4" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-user-group d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No faculty in your selected scope.
                </td></tr>`;
            document.getElementById('rosterCount').textContent = '0 faculty';
            return;
        }

        tbody.innerHTML = faculty.map(function (f) {
            return '<tr>' +
                '<td class="do-privacy-target">' +
                    '<div class="fw-semibold">' + escapeHtml(fullName(f)) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(f.faculty_no || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(f.dept_name || f.dept_code || '—') + '</td>' +
                '<td class="d-none d-sm-table-cell small">' + escapeHtml(f.position || '—') + '</td>' +
                '<td class="d-none d-lg-table-cell"><span class="badge text-bg-light border">' + escapeHtml(f.employment_status || '—') + '</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('rosterCount').textContent = faculty.length + ' faculty';
    }

    function renderPrintTable() {
        const host = document.getElementById('printTable');
        if (!host) return;

        const faculty = FACULTY.filter(deptMatches);
        const att     = ATTENDANCE.filter(function (r) { return deptMatches(r) && dateMatches(r.attendance_date); });
        const leaves  = LEAVES.filter(function (r) { return deptMatches(r) && dateMatches(r.created_at); });
        const evals   = EVALS.filter(function (r) { return deptMatches(r) && dateMatches(r.submitted_at); });

        let sumScore = 0, scoreCount = 0;
        evals.forEach(function (r) {
            const s = parseFloat(r.composite_score);
            if (!isNaN(s)) { sumScore += s; scoreCount++; }
        });
        const avgScore = scoreCount > 0 ? (sumScore / scoreCount).toFixed(2) : '0.00';

        const filterParts = [];
        if (currentFilters.dept !== 'all') {
            filterParts.push('Department: ' + (DEPT_NAMES[currentFilters.dept] || ('Dept #' + currentFilters.dept)));
        }
        if (currentFilters.sy)    filterParts.push('School Year: ' + currentFilters.sy);
        if (currentFilters.month) {
            const mNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            filterParts.push('Month: ' + mNames[+currentFilters.month - 1]);
        }
        if (currentFilters.week)  filterParts.push('Week: ' + currentFilters.week);
        const filterLine = filterParts.length
            ? 'Filters — ' + filterParts.join(' · ')
            : 'No filters applied — showing all departments & records';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        if (faculty.length === 0) {
            host.innerHTML = `
                <div class="print-header">
                    <h3>Department Overview Report</h3>
                    <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
                </div>
                <p class="print-filters">${escapeHtml(filterLine)}</p>
                <p style="text-align:center; font-size:10pt; color:#555; margin-top:40px;">
                    No faculty records match the current scope.
                </p>
                <div class="print-footer">SMS 2 · Faculty Module · Confidential</div>`;
            return;
        }

        const rows = faculty.map(function (f) {
            return '<tr>' +
                '<td class="do-print-blur">' + escapeHtml(fullName(f)) + '</td>' +
                '<td>' + escapeHtml(f.faculty_no || '—') + '</td>' +
                '<td>' + escapeHtml(f.dept_name || f.dept_code || '—') + '</td>' +
                '<td>' + escapeHtml(f.position || '—') + '</td>' +
                '<td>' + escapeHtml(f.employment_status || '—') + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Department Overview Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Faculty</div>
                    <div class="print-kpi-value">${faculty.length.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Pending Approvals</div>
                    <div class="print-kpi-value">${leaves.length.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Attendance Records</div>
                    <div class="print-kpi-value">${att.length.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Avg Eval Score</div>
                    <div class="print-kpi-value">${avgScore}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Faculty No</th><th>Department</th>
                        <th>Position</th><th>Employment</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${faculty.length} faculty · Confidential — Faculty names blurred
            </div>`;
    }

    function exportCsv() {
        const faculty = FACULTY.filter(deptMatches);
        const headers = ['Faculty No', 'First Name', 'Middle Name', 'Last Name', 'Department', 'Position', 'Employment Status', 'Email'];
        const rows = faculty.map(function (f) {
            return [
                f.faculty_no || '',
                f.first_name || '',
                f.middle_name || '',
                f.last_name || '',
                f.dept_name || f.dept_code || '',
                f.position || '',
                f.employment_status || '',
                f.email || ''
            ];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(function (e) {
            csv += e.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'department-overview_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function populateYearFilter() {
        const years = new Set();
        ATTENDANCE.forEach(function (r) {
            const d = parseDate(r.attendance_date);
            if (!d) return;
            const y = d.getFullYear();
            const m = d.getMonth() + 1;
            const syStart = m >= 6 ? y : y - 1;
            years.add(syStart + '-' + (syStart + 1));
        });
        const sel = document.getElementById('f_sy');
        if (!sel) return;
        Array.from(years).sort().reverse().forEach(function (sy) {
            const opt = document.createElement('option');
            opt.value = sy; opt.textContent = sy;
            sel.appendChild(opt);
        });
    }

    function bindFilters() {
        const fDept = document.getElementById('f_dept');
        const fSy   = document.getElementById('f_sy');
        const fMo   = document.getElementById('f_month');
        const fWk   = document.getElementById('f_week');

        if (fDept) fDept.addEventListener('change', function () { currentFilters.dept = fDept.value; render(); });
        if (fSy)   fSy.addEventListener('change',   function () { currentFilters.sy = fSy.value;     render(); });
        if (fMo)   fMo.addEventListener('change',   function () { currentFilters.month = fMo.value;  render(); });
        if (fWk)   fWk.addEventListener('change',   function () { currentFilters.week = fWk.value;   render(); });
    }

    function resetAllFilters() {
        currentFilters = { dept: 'all', sy: '', month: '', week: '' };
        const fDept = document.getElementById('f_dept');
        const fSy   = document.getElementById('f_sy');
        const fMo   = document.getElementById('f_month');
        const fWk   = document.getElementById('f_week');
        if (fDept) fDept.value = 'all';
        if (fSy)   fSy.value = '';
        if (fMo)   fMo.value = '';
        if (fWk)   fWk.value = '';
        render();
    }

    function init() {
        populateYearFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
        document.getElementById('printBtn').addEventListener('click', function () { window.print(); });

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanDeptOverviewPrivacy';
        if (pt) {
            const apply = function (on) { document.body.classList.toggle('privacy-mode', on); };
            try {
                const saved = localStorage.getItem(KEY) === '1';
                pt.checked = saved;
                apply(saved);
            } catch (e) { /* ignore */ }
            pt.addEventListener('change', function () {
                apply(pt.checked);
                try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

        const obs = new MutationObserver(function () { renderCharts(); });
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