<?php
/**
 * SMS 2 - Dean - Department Analytics
 * Same topography/cards as faculty-profile.php.
 * Only the charts and their layout are unique.
 * RBAC scoped to the dean's assigned departments.
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
   LOAD DATA — scoped to dean's departments
   ============================================================ */
$facultyRoster   = [];
$allAttendance   = [];
$allEvals        = [];
$allLeaves       = [];
$loadError       = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ---- Faculty roster ---- */
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
                f.academic_rank,
                f.tier,
                d.code AS dept_code,
                d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facultyRoster = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Attendance (12 months) ---- */
        $attStmt = $pdo->query("
            SELECT
                a.attendance_id, a.faculty_id, a.attendance_date,
                a.status, a.hours_rendered, f.department_id
            FROM faculty_db.attendance_records a
            INNER JOIN faculty_db.faculty f ON f.faculty_id = a.faculty_id
            WHERE f.department_id IN ($deptList)
              AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY a.attendance_date DESC
        ");
        $allAttendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Evaluations (12 months) ---- */
        $evalStmt = $pdo->query("
            SELECT
                e.evaluation_id, e.faculty_id, e.source_type,
                e.composite_score, e.rating_label, e.submitted_at,
                f.department_id
            FROM faculty_db.evaluations e
            INNER JOIN faculty_db.faculty f ON f.faculty_id = e.faculty_id
            WHERE f.department_id IN ($deptList)
              AND e.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY e.submitted_at DESC
        ");
        $allEvals = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Leaves (12 months) ---- */
        $leaveStmt = $pdo->query("
            SELECT
                lr.id, lr.request_ref, lr.faculty_id, lr.leave_type,
                lr.start_date, lr.end_date, lr.total_days,
                lr.approval_status, lr.created_at, f.department_id
            FROM faculty_db.leave_requests lr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
            WHERE f.department_id IN ($deptList)
              AND lr.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY lr.created_at DESC
        ");
        $allLeaves = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log('Dean department analytics load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Department Analytics';
$activeModule = 'faculty';
$activePage   = 'department-analytics';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Department Analytics', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="daPage">

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
        <h4 class="mb-0 fw-bold">Department Analytics Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — for internal review only.</em></p>
    </div>

    <!-- ================= Page Header (same as faculty-profile) ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-chart-line text-primary"></i>
                <span>Department Analytics</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Performance across attendance, evaluations, and leave for your assigned department<?= count($deanDepartments) > 1 ? 's' : '' ?>.
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

    <!-- ================= KPI Cards (same as faculty-profile style) ================= -->
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
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Present Rate</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiPresentRate">0%</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;" id="kpiPresentSub">Across records in scope</small>
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
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Avg Eval Score</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiAvgScore">0.00</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;" id="kpiAvgScoreSub">Last 12 months</small>
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
                        <i class="fas fa-plane-departure"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Leave Days</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiLeaveDays">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;" id="kpiLeaveSub">In scope · 12 months</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters (same style as other dean pages) ================= -->
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
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         UNIQUE CHART LAYOUT (only this section differs from other pages)
         Row A: Radial gauge + horizontal attendance bars
         Row B: Full-width hero: present rate + eval score dual-line
         Row C: Leave composition (stacked columns by month)
         ============================================================ -->

    <!-- ===== Row A ===== -->
    <div class="row g-3 mb-4 no-print">

        <!-- Attendance Rate Gauge -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Attendance Rate</h5>
                        <span class="badge text-bg-light border" id="gaugeBadge">0 records</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="gaugeChart"></canvas>
                        <div class="da-empty d-none" id="gaugeEmpty">
                            <i class="fas fa-gauge-high"></i>
                            <h6>No attendance data</h6>
                            <p>Rate will appear once attendance is recorded.</p>
                        </div>
                    </div>
                    <div class="text-center text-body-secondary small mt-2">
                        <span class="fw-bold text-body-emphasis" id="gaugeValue">0%</span> present
                        · <span id="gaugeAbsent">0%</span> absent / late
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance by Day-of-Week -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Attendance by Day of Week</h5>
                        <span class="badge text-bg-light border" id="dowBadge">—</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="dowChart"></canvas>
                        <div class="da-empty d-none" id="dowEmpty">
                            <i class="fas fa-calendar-day"></i>
                            <h6>No attendance data</h6>
                            <p>Day-of-week pattern will show once records exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Row B: Hero dual-line chart (full width) ===== -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Attendance & Evaluation Trend</h5>
                            <p class="text-body-secondary small mb-0">Monthly present rate vs. average eval score</p>
                        </div>
                        <span class="badge text-bg-light border" id="trendBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100" style="height:320px;">
                        <canvas id="heroChart"></canvas>
                        <div class="da-empty d-none" id="heroEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No trend data yet</h6>
                            <p>Trends will appear once attendance and evaluations are recorded.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Row C: Leave stacked bar + status pie ===== -->
    <div class="row g-3 mb-4 no-print">
        <!-- Leave composition stacked by month -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Leave Days by Month</h5>
                        <span class="badge text-bg-light border" id="leaveMonthBadge">0 months</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="leaveMonthChart"></canvas>
                        <div class="da-empty d-none" id="leaveMonthEmpty">
                            <i class="fas fa-chart-column"></i>
                            <h6>No leave data</h6>
                            <p>Monthly leave totals will show once leaves are filed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave by status doughnut -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Leave by Status</h5>
                        <span class="badge text-bg-light border" id="leaveStatusBadge">0 total</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="leaveStatusChart"></canvas>
                        <div class="da-empty d-none" id="leaveStatusEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No leave data</h6>
                            <p>Status distribution will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="da-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* Empty states inside cards */
    .da-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .da-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.6rem; color: var(--sms-text-muted); }
    .da-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.2rem; font-size: 0.9rem; }
    .da-empty p { font-size: 0.78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* Privacy blur */
    .privacy-mode .da-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .da-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Small-screen tweaks — match other dean pages */
    @media (max-width: 640px) {
        #daPage .position-relative[style*="height:320px"] { height: 240px !important; }
    }
    @media (max-width: 400px) {
        #daPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #daPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        #daPage .card-title { font-size: 0.9rem; }
        #daPage .position-relative[style*="height:320px"] { height: 220px !important; }
        .da-empty { inset: 0.5rem; }
        .da-empty h6 { font-size: 0.82rem; }
        .da-empty p { font-size: 0.72rem; }
    }
    @media (max-width: 360px) {
        #daPage .position-relative[style*="height:320px"] { height: 200px !important; }
    }

    /* Print-only */
    .da-print-only { display: none; }

    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }

        #daPage { display: none !important; }

        .da-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .da-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .da-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .da-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .da-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .da-print-only .print-metrics {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .da-print-only .print-metric { flex: 1; text-align: center; }
        .da-print-only .print-metric-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .da-print-only .print-metric-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .da-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .da-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .da-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .da-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .da-print-only td.da-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .da-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .da-print-only thead { display: table-header-group; }
        .da-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const FACULTY    = <?= json_encode($facultyRoster, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const ATTENDANCE = <?= json_encode($allAttendance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const EVALS      = <?= json_encode($allEvals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const LEAVES     = <?= json_encode($allLeaves, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    let filters = { dept: 'all', sy: '', month: '', week: '' };
    let gaugeChart, dowChart, heroChart, leaveMonthChart, leaveStatusChart;

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
        return (r.first_name + mid + ' ' + r.last_name).trim() || '—';
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

    function scoped() {
        const faculty = FACULTY.filter(deptMatches);
        const att     = ATTENDANCE.filter(r => deptMatches(r) && dateMatches(r.attendance_date));
        const evals   = EVALS.filter(r => deptMatches(r) && dateMatches(r.submitted_at));
        const leaves  = LEAVES.filter(r => deptMatches(r) && dateMatches(r.created_at));
        return { faculty, att, evals, leaves };
    }

    /* ============================================================
       Theme colors
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

    /* ============================================================
       KPI cards
       ============================================================ */
    function renderKPIs() {
        const s = scoped();

        document.getElementById('kpiFaculty').textContent = s.faculty.length.toLocaleString();

        let present = 0;
        s.att.forEach(r => { if (r.status === 'Present') present++; });
        const presentRate = s.att.length > 0 ? Math.round((present / s.att.length) * 100) : 0;
        document.getElementById('kpiPresentRate').textContent = presentRate + '%';
        document.getElementById('kpiPresentSub').textContent =
            s.att.length > 0 ? (present + ' of ' + s.att.length + ' records') : 'Across records in scope';

        let sumScore = 0, scoreCount = 0;
        s.evals.forEach(r => {
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { sumScore += v; scoreCount++; }
        });
        const avg = scoreCount > 0 ? (sumScore / scoreCount) : 0;
        document.getElementById('kpiAvgScore').textContent = avg.toFixed(2);
        document.getElementById('kpiAvgScoreSub').textContent = scoreCount + ' evaluation' + (scoreCount !== 1 ? 's' : '');

        let leaveDays = 0;
        s.leaves.forEach(r => { leaveDays += (parseInt(r.total_days, 10) || 0); });
        document.getElementById('kpiLeaveDays').textContent = leaveDays.toLocaleString();
        document.getElementById('kpiLeaveSub').textContent =
            s.leaves.length + ' request' + (s.leaves.length !== 1 ? 's' : '') + ' filed';
    }

    /* ============================================================
       Chart: Attendance Rate Gauge (half doughnut)
       ============================================================ */
    function renderGaugeChart() {
        if (typeof Chart === 'undefined') return;
        const s = scoped();
        const c = chartColors();

        let present = 0;
        s.att.forEach(r => { if (r.status === 'Present') present++; });
        const total = s.att.length;
        const rate = total > 0 ? Math.round((present / total) * 100) : 0;

        document.getElementById('gaugeBadge').textContent = total + ' record' + (total !== 1 ? 's' : '');
        document.getElementById('gaugeValue').textContent = rate + '%';
        document.getElementById('gaugeAbsent').textContent = (100 - rate) + '%';

        const emptyEl = document.getElementById('gaugeEmpty');
        const canvas = document.getElementById('gaugeChart');
        if (total === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (gaugeChart) { gaugeChart.destroy(); gaugeChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (gaugeChart) gaugeChart.destroy();
        gaugeChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent / Late / Leave'],
                datasets: [{
                    data: [rate, 100 - rate],
                    backgroundColor: ['#10b981', c.surface === '#ffffff' ? '#e5e7eb' : '#1e293b'],
                    borderColor: c.surface,
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                rotation: -90,
                circumference: 360,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 14,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.label + ': ' + ctx.parsed + '%'
                        }
                    }
                }
            }
        });
    }

    /* ============================================================
       Chart: Attendance by Day of Week (radar)
       ============================================================ */
    function renderDowChart() {
        if (typeof Chart === 'undefined') return;
        const s = scoped();
        const c = chartColors();

        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const counts = { Sun: 0, Mon: 0, Tue: 0, Wed: 0, Thu: 0, Fri: 0, Sat: 0 };
        s.att.forEach(r => {
            const d = parseDate(r.attendance_date);
            if (!d) return;
            const key = days[d.getDay()];
            counts[key] = (counts[key] || 0) + 1;
        });

        const total = Object.values(counts).reduce((a, b) => a + b, 0);
        document.getElementById('dowBadge').textContent = total + ' records';

        const emptyEl = document.getElementById('dowEmpty');
        const canvas = document.getElementById('dowChart');
        if (total === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (dowChart) { dowChart.destroy(); dowChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (dowChart) dowChart.destroy();
        dowChart = new Chart(canvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Attendance Records',
                    data: days.map(d => counts[d]),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.18)',
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: c.surface,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        angleLines: { color: c.grid },
                        grid: { color: c.grid },
                        pointLabels: {
                            color: c.textStrong,
                            font: { size: 11, weight: '600' }
                        },
                        ticks: {
                            color: c.text,
                            backdropColor: 'transparent',
                            font: { size: 9 }
                        }
                    }
                }
            }
        });
    }

    /* ============================================================
       Chart: Hero dual-line (present rate vs. avg eval score)
       ============================================================ */
    function renderHeroChart() {
        if (typeof Chart === 'undefined') return;
        const s = scoped();
        const c = chartColors();

        const monthKeys = new Set();
        const attByMonth = {};
        s.att.forEach(r => {
            const d = parseDate(r.attendance_date); if (!d) return;
            const k = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            monthKeys.add(k);
            if (!attByMonth[k]) attByMonth[k] = { present: 0, total: 0 };
            attByMonth[k].total++;
            if (r.status === 'Present') attByMonth[k].present++;
        });
        const evalByMonth = {};
        s.evals.forEach(r => {
            const d = parseDate(r.submitted_at); if (!d) return;
            const k = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            monthKeys.add(k);
            if (!evalByMonth[k]) evalByMonth[k] = { sum: 0, count: 0 };
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { evalByMonth[k].sum += v; evalByMonth[k].count++; }
        });

        const sortedKeys = Array.from(monthKeys).sort();
        const labels = sortedKeys.map(k => {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: 'numeric' });
        });
        const presentRates = sortedKeys.map(k => {
            const m = attByMonth[k];
            return m && m.total > 0 ? +((m.present / m.total) * 100).toFixed(1) : null;
        });
        const avgScores = sortedKeys.map(k => {
            const m = evalByMonth[k];
            return m && m.count > 0 ? +(m.sum / m.count).toFixed(2) : null;
        });

        document.getElementById('trendBadge').textContent = labels.length + ' period' + (labels.length !== 1 ? 's' : '');
        const emptyEl = document.getElementById('heroEmpty');
        const canvas = document.getElementById('heroChart');
        if (labels.length === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (heroChart) { heroChart.destroy(); heroChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (heroChart) heroChart.destroy();
        heroChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present Rate (%)',
                        data: presentRates,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.12)',
                        tension: 0.35, fill: true,
                        pointRadius: 4, pointHoverRadius: 6,
                        yAxisID: 'y', spanGaps: true
                    },
                    {
                        label: 'Avg Eval Score',
                        data: avgScores,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.10)',
                        tension: 0.35, fill: false,
                        pointRadius: 4, pointHoverRadius: 6,
                        yAxisID: 'y2', spanGaps: true,
                        borderDash: [5, 4]
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 16,
                            font: { size: 12, weight: '600' }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                    y: {
                        type: 'linear', position: 'left',
                        beginAtZero: true, suggestedMax: 100,
                        ticks: { color: '#10b981', font: { size: 11 }, callback: v => v + '%' },
                        grid: { color: c.grid }
                    },
                    y2: {
                        type: 'linear', position: 'right',
                        beginAtZero: true, suggestedMax: 5,
                        ticks: { color: '#8b5cf6', font: { size: 11 }, stepSize: 1 },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    /* ============================================================
       Chart: Leave days by month (stacked bars)
       ============================================================ */
    function renderLeaveMonthChart() {
        if (typeof Chart === 'undefined') return;
        const s = scoped();
        const c = chartColors();

        const monthMap = {};
        s.leaves.forEach(r => {
            const d = parseDate(r.start_date || r.created_at); if (!d) return;
            const k = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            if (!monthMap[k]) monthMap[k] = { pending: 0, approved: 0, rejected: 0 };
            const days = parseInt(r.total_days, 10) || 0;
            const st = String(r.approval_status || '').toLowerCase();
            if (st === 'approved') monthMap[k].approved += days;
            else if (st === 'rejected') monthMap[k].rejected += days;
            else monthMap[k].pending += days;
        });
        const keys = Object.keys(monthMap).sort();
        const labels = keys.map(k => {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: '2-digit' });
        });

        document.getElementById('leaveMonthBadge').textContent = keys.length + ' month' + (keys.length !== 1 ? 's' : '');
        const emptyEl = document.getElementById('leaveMonthEmpty');
        const canvas = document.getElementById('leaveMonthChart');
        if (keys.length === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (leaveMonthChart) { leaveMonthChart.destroy(); leaveMonthChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (leaveMonthChart) leaveMonthChart.destroy();
        leaveMonthChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Approved', data: keys.map(k => monthMap[k].approved), backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 },
                    { label: 'Pending',  data: keys.map(k => monthMap[k].pending),  backgroundColor: '#f59e0b', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 },
                    { label: 'Rejected', data: keys.map(k => monthMap[k].rejected), backgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 }
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
                            padding: 14,
                            font: { size: 11, weight: '600' }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                    y: { stacked: true, beginAtZero: true, ticks: { color: c.text, font: { size: 11 }, precision: 0 }, grid: { color: c.grid } }
                }
            }
        });
    }

    /* ============================================================
       Chart: Leave by status (doughnut)
       ============================================================ */
    function renderLeaveStatusChart() {
        if (typeof Chart === 'undefined') return;
        const s = scoped();
        const c = chartColors();

        const counts = { Approved: 0, Pending: 0, Rejected: 0 };
        s.leaves.forEach(r => {
            const st = String(r.approval_status || '').toLowerCase();
            if (st === 'approved') counts.Approved++;
            else if (st === 'rejected') counts.Rejected++;
            else counts.Pending++;
        });
        const labels = Object.keys(counts);
        const data = labels.map(k => counts[k]);
        const total = data.reduce((a, b) => a + b, 0);

        document.getElementById('leaveStatusBadge').textContent = total + ' total';
        const emptyEl = document.getElementById('leaveStatusEmpty');
        const canvas = document.getElementById('leaveStatusChart');
        if (total === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (leaveStatusChart) { leaveStatusChart.destroy(); leaveStatusChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        if (leaveStatusChart) leaveStatusChart.destroy();
        leaveStatusChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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
                            font: { size: 11, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    /* ============================================================
       Print report
       ============================================================ */
    function renderPrintReport() {
        const host = document.getElementById('printReport');
        if (!host) return;

        const s = scoped();

        let present = 0;
        s.att.forEach(r => { if (r.status === 'Present') present++; });
        const presentRate = s.att.length > 0 ? Math.round((present / s.att.length) * 100) : 0;
        let sumScore = 0, scoreCount = 0;
        s.evals.forEach(r => {
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { sumScore += v; scoreCount++; }
        });
        const avgScore = scoreCount > 0 ? (sumScore / scoreCount).toFixed(2) : '0.00';
        let leaveDays = 0;
        s.leaves.forEach(r => { leaveDays += (parseInt(r.total_days, 10) || 0); });

        const statsByFaculty = {};
        s.faculty.forEach(f => {
            statsByFaculty[f.faculty_id] = {
                name: fullName(f),
                dept: f.dept_name || f.dept_code || '—',
                position: f.position || '—',
                attendance: { present: 0, total: 0 },
                eval: { sum: 0, count: 0 },
                leaveDays: 0
            };
        });
        s.att.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            st.attendance.total++;
            if (r.status === 'Present') st.attendance.present++;
        });
        s.evals.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { st.eval.sum += v; st.eval.count++; }
        });
        s.leaves.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            st.leaveDays += (parseInt(r.total_days, 10) || 0);
        });

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
        const filterLine = filterParts.length
            ? 'Filters — ' + filterParts.join(' · ')
            : 'No filters applied — showing all departments & records';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const rows = s.faculty.map(f => {
            const st = statsByFaculty[f.faculty_id];
            const attRate = st.attendance.total > 0
                ? Math.round((st.attendance.present / st.attendance.total) * 100) + '%'
                : '—';
            const avgEval = st.eval.count > 0 ? (st.eval.sum / st.eval.count).toFixed(2) : '—';
            return '<tr>' +
                '<td class="da-print-blur">' + escapeHtml(st.name) + '</td>' +
                '<td>' + escapeHtml(st.dept) + '</td>' +
                '<td>' + escapeHtml(st.position) + '</td>' +
                '<td>' + attRate + '</td>' +
                '<td>' + avgEval + '</td>' +
                '<td>' + st.leaveDays + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Department Analytics Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-metrics">
                <div class="print-metric">
                    <div class="print-metric-label">Faculty</div>
                    <div class="print-metric-value">${s.faculty.length}</div>
                </div>
                <div class="print-metric">
                    <div class="print-metric-label">Present Rate</div>
                    <div class="print-metric-value">${presentRate}%</div>
                </div>
                <div class="print-metric">
                    <div class="print-metric-label">Avg Eval</div>
                    <div class="print-metric-value">${avgScore}</div>
                </div>
                <div class="print-metric">
                    <div class="print-metric-label">Leave Days</div>
                    <div class="print-metric-value">${leaveDays}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Department</th><th>Position</th>
                        <th>Present</th><th>Eval</th><th>Leave (d)</th>
                    </tr>
                </thead>
                <tbody>${rows || '<tr><td colspan="6" style="text-align:center;">No faculty in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${s.faculty.length} faculty · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv() {
        const s = scoped();

        const statsByFaculty = {};
        s.faculty.forEach(f => {
            statsByFaculty[f.faculty_id] = {
                facultyNo: f.faculty_no || '',
                name: fullName(f),
                dept: f.dept_name || f.dept_code || '',
                position: f.position || '',
                attendance: { present: 0, total: 0 },
                eval: { sum: 0, count: 0 },
                leaveDays: 0
            };
        });
        s.att.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            st.attendance.total++;
            if (r.status === 'Present') st.attendance.present++;
        });
        s.evals.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            const v = parseFloat(r.composite_score);
            if (!isNaN(v)) { st.eval.sum += v; st.eval.count++; }
        });
        s.leaves.forEach(r => {
            const st = statsByFaculty[r.faculty_id]; if (!st) return;
            st.leaveDays += (parseInt(r.total_days, 10) || 0);
        });

        const headers = ['Faculty No', 'Faculty', 'Department', 'Position', 'Present Rate (%)', 'Avg Eval', 'Leave Days'];
        const rows = s.faculty.map(f => {
            const st = statsByFaculty[f.faculty_id];
            const attRate = st.attendance.total > 0
                ? Math.round((st.attendance.present / st.attendance.total) * 100)
                : '';
            const avgEval = st.eval.count > 0 ? (st.eval.sum / st.eval.count).toFixed(2) : '';
            return [st.facultyNo, st.name, st.dept, st.position, attRate, avgEval, st.leaveDays];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'department-analytics_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Filters
       ============================================================ */
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

    function bindFilters() {
        document.querySelectorAll('#daPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }
    function resetAllFilters() {
        filters = { dept: 'all', sy: '', month: '', week: '' };
        document.querySelectorAll('#daPage [data-filter]').forEach(el => {
            el.value = (el.tagName === 'SELECT' && el.dataset.filter === 'dept') ? 'all' : '';
        });
        render();
    }

    /* ============================================================
       Render all charts
       ============================================================ */
    function render() {
        renderKPIs();
        renderGaugeChart();
        renderDowChart();
        renderHeroChart();
        renderLeaveMonthChart();
        renderLeaveStatusChart();
        renderPrintReport();
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        populateYearFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanDeptAnalyticsPrivacy';
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
            renderGaugeChart();
            renderDowChart();
            renderHeroChart();
            renderLeaveMonthChart();
            renderLeaveStatusChart();
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