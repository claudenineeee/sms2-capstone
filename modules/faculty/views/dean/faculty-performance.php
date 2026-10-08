<?php
/**
 * SMS 2 - Dean - Faculty Performance
 * Ranking view of teaching faculty (Faculty Professor + Department Head)
 * in the dean's RBAC-scoped departments.
 * Same topography & cards as faculty-profile.php / department-overview.php.
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
   Change this array to adjust who appears in the ranking.
   ============================================================ */
$teachingPositions = [
    'Faculty Professor',
    'Department Head',
    // Add 'Dean' here if the dean also teaches and should be ranked.
];

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
$facultyRoster   = [];
$allAttendance   = [];
$allEvals        = [];
$allLoads        = [];
$loadError       = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        /* ---- Faculty roster (only teaching positions) ---- */
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
                f.employment_status,
                f.profile_status,
                f.academic_rank,
                f.tier,
                f.hired_date,
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

        /* ---- IDs of teaching faculty in scope (used to filter joins below) ---- */
        $facultyIds = array_map('intval', array_column($facultyRoster, 'faculty_id'));
        $facultyIdList = !empty($facultyIds) ? implode(',', $facultyIds) : '0';

        /* ---- Attendance (12 months) — only for teaching faculty ---- */
        if (!empty($facultyIds)) {
            $attStmt = $pdo->query("
                SELECT
                    a.attendance_id, a.faculty_id, a.attendance_date,
                    a.status, a.hours_rendered, f.department_id
                FROM faculty_db.attendance_records a
                INNER JOIN faculty_db.faculty f ON f.faculty_id = a.faculty_id
                WHERE f.faculty_id IN ($facultyIdList)
                  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY a.attendance_date DESC
            ");
            $allAttendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /* ---- Evaluations (12 months) — only for teaching faculty ---- */
        if (!empty($facultyIds)) {
            $evalStmt = $pdo->query("
                SELECT
                    e.evaluation_id, e.faculty_id, e.source_type,
                    e.composite_score, e.rating_label, e.submitted_at,
                    f.department_id
                FROM faculty_db.evaluations e
                INNER JOIN faculty_db.faculty f ON f.faculty_id = e.faculty_id
                WHERE f.faculty_id IN ($facultyIdList)
                  AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY e.submitted_at DESC
            ");
            $allEvals = $evalStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /* ---- Teaching load — only for teaching faculty ---- */
        if (!empty($facultyIds)) {
            $loadStmt = $pdo->query("
                SELECT
                    tlr.load_request_id, tlr.faculty_id, tlr.term_id,
                    tlr.total_units, tlr.status, tlr.submitted_at,
                    at.academic_year, at.semester,
                    f.department_id
                FROM faculty_db.teaching_load_requests tlr
                INNER JOIN faculty_db.faculty f ON f.faculty_id = tlr.faculty_id
                LEFT JOIN faculty_db.academic_terms at ON at.term_id = tlr.term_id
                WHERE f.faculty_id IN ($facultyIdList)
                ORDER BY tlr.submitted_at DESC
            ");
            $allLoads = $loadStmt->fetchAll(PDO::FETCH_ASSOC);
        }

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
                Composite ranking across attendance, evaluations, and teaching load for teaching faculty in your department<?= count($deanDepartments) > 1 ? 's' : '' ?>.
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

    <!-- ================= Filters ================= -->
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

    <!-- ================= Performance Table ================= -->
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

<!-- Print-only report -->
<div class="fp-print-only" id="printReport" aria-hidden="true"></div>

<style>
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

    .privacy-mode .fp-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .fp-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

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
    }
    @media (max-width: 360px) {
        .fp-table tbody td,
        .fp-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* Print-only */
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

<script>
(function () {
    'use strict';

    /* Server-injected data (already RBAC scoped + position filtered) */
    const FACULTY    = <?= json_encode($facultyRoster, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const ATTENDANCE = <?= json_encode($allAttendance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const EVALS      = <?= json_encode($allEvals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const LOADS      = <?= json_encode($allLoads, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 15;

    let filters = { dept: 'all', sy: '', month: '', week: '', tier: '' };
    let currentPage = 1;
    let rankedRows = [];

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
        return (r.first_name + mid + ' ' + r.last_name).trim() || '—';
    }
    function initials(name) {
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
       Build ranking (already scoped by server to teaching positions)
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
            const attRate = s.attendance.total > 0
                ? (s.attendance.present / s.attendance.total) * 100
                : 0;
            const attScore = (attRate / 100) * 5;
            const evalScore = s.eval.count > 0 ? (s.eval.sum / s.eval.count) : 0;
            const loadScore = Math.min(5, (s.units / 15) * 5);
            const composite = (attScore * 0.35) + (evalScore * 0.50) + (loadScore * 0.15);

            return {
                ...s,
                attRate: Math.round(attRate),
                attScore,
                evalScore,
                loadScore,
                composite: +composite.toFixed(2),
                tier: tierForScore(composite)
            };
        });

        rows.sort((a, b) => b.composite - a.composite);
        return rows;
    }

    function renderKPIs(rows) {
        document.getElementById('kpiFaculty').textContent = rows.length.toLocaleString();
        const top = rows.filter(r => r.tier === 'outstanding').length;
        const needsSupport = rows.filter(r => r.tier === 'needs_support' && r.composite > 0).length;

        const totalComposite = rows.reduce((sum, r) => sum + (r.composite > 0 ? r.composite : 0), 0);
        const avgComposite = rows.length > 0 ? (totalComposite / rows.length) : 0;

        document.getElementById('kpiTop').textContent = top.toLocaleString();
        document.getElementById('kpiComposite').textContent = avgComposite.toFixed(2);
        document.getElementById('kpiNeedsSupport').textContent = needsSupport.toLocaleString();
    }

    function renderTable(rows) {
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

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, total);
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
                        '<span class="fp-avatar">' + escapeHtml(initials(r.name)) + '</span>' +
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
        const wEnd = Math.min(totalPages, currentPage + 2);
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
                renderTable(rankedRows);
                document.querySelector('.fp-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function renderPrintReport(rows) {
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

        const total = rows.length;
        const top = rows.filter(r => r.tier === 'outstanding').length;
        const needsSupport = rows.filter(r => r.tier === 'needs_support' && r.composite > 0).length;
        const totalComposite = rows.reduce((sum, r) => sum + (r.composite > 0 ? r.composite : 0), 0);
        const avgComposite = rows.length > 0 ? (totalComposite / rows.length) : 0;

        const rowsHtml = rows.map((r, idx) => {
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

    function exportCsv(rows) {
        const headers = ['Rank', 'Faculty No', 'Faculty', 'Department', 'Position', 'Present Rate (%)', 'Avg Eval', 'Units', 'Composite', 'Tier'];
        const lines = rows.map((r, idx) => [
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

    function applyTierFilter(rows) {
        if (!filters.tier) return rows;
        return rows.filter(r => r.tier === filters.tier);
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
        currentPage = 1;
        renderKPIs(ranked);
        renderTable(rankedRows);
        renderPrintReport(rankedRows);
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
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(rankedRows));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

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