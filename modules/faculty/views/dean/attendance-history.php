<?php
/**
 * SMS 2 - Dean - Attendance History
 * Faculty Professor attendance log, RBAC-scoped to the dean's departments.
 * Same topography as faculty-profile.php / department-overview.php.
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
$allAttendance   = [];
$facultyRoster   = [];
$loadError       = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ---- Faculty roster (Faculty Professor only) ---- */
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
                f.department_id,
                f.position,
                d.code AS dept_code,
                d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyRoster = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        $facultyMap = [];
        foreach ($facultyRoster as $f) {
            $facultyMap[(int) $f['faculty_id']] = [
                'faculty_id' => (int) $f['faculty_id'],
                'faculty_no' => $f['faculty_no'] ?? '',
                'name'       => trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? '')),
                'department_id' => (int) $f['department_id'],
                'dept_name'  => $f['dept_name'] ?: ($f['dept_code'] ?: '—'),
            ];
        }

        /* ---- Attendance rows for those faculty (last 12 months) ---- */
        $facultyIds = array_keys($facultyMap);
        if (!empty($facultyIds)) {
            $facultyIdList = implode(',', array_map('intval', $facultyIds));
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
                WHERE f.faculty_id IN ($facultyIdList)
                  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY a.attendance_date DESC
                LIMIT 3000
            ");
            $allAttendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /* Attach faculty info */
        foreach ($allAttendance as &$row) {
            $fid = (int) $row['faculty_id'];
            $f   = $facultyMap[$fid] ?? null;
            $row['faculty_name']   = $f ? $f['name'] : ('Faculty #' . $fid);
            $row['faculty_no']     = $f ? $f['faculty_no'] : '';
            $row['faculty_dept']   = $f ? $f['dept_name'] : '—';
            $row['department_id']  = $f ? $f['department_id'] : (int) $row['department_id'];
        }
        unset($row);

    } catch (Throwable $e) {
        error_log('Dean attendance history load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Attendance History';
$activeModule = 'faculty';
$activePage   = 'attendance-history';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Attendance History', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="ahPage">

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
        <h4 class="mb-0 fw-bold">Attendance History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-clipboard-list text-primary"></i>
                <span>Attendance History</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Faculty Professor attendance records for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
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
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">Last 12 months</small>
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
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Present</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiPresent">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">
                            <span id="kpiPresentPct">0%</span> of total
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
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Late</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiLate">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">
                            <span id="kpiLatePct">0%</span> of total
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
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Absent / Leave</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiAbsentLeave">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">
                            <span id="kpiAbsentLeavePct">0%</span> of total
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
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" data-filter="status">
                        <option value="">All statuses</option>
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Absent">Absent</option>
                        <option value="On Leave">On Leave</option>
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
        <!-- Composition doughnut -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Attendance Composition</h5>
                        <span class="badge text-bg-light border" id="compositionBadge">0 total</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="compositionChart"></canvas>
                        <div class="ah-empty d-none" id="compositionEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No attendance yet</h6>
                            <p>Composition will appear once records exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly trend -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Monthly Attendance</h5>
                        <span class="badge text-bg-light border" id="trendBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:260px;">
                        <canvas id="trendChart"></canvas>
                        <div class="ah-empty d-none" id="trendEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No trend data yet</h6>
                            <p>Monthly trends will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Records Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Attendance Records</h5>
                <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#10b981;"></span>Present
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Late
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#ef4444;"></span>Absent
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#0284c7;"></span>On Leave
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0 ah-table" style="min-width: 780px;">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-body-secondary">Date</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Department</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell">Time In</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell">Time Out</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell text-center">Hours</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Notes</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody"></tbody>
            </table>
        </div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
            <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
            <nav aria-label="Attendance pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="ah-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* Empty states */
    .ah-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .ah-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.6rem; color: var(--sms-text-muted); }
    .ah-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.2rem; font-size: 0.9rem; }
    .ah-empty p { font-size: 0.78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* Table */
    .ah-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .ah-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .ah-table tbody td { padding: 0.7rem 0.75rem; font-size: 0.85rem; vertical-align: middle; }
    .ah-table thead th { padding: 0.65rem 0.75rem; font-size: 0.7rem; }

    /* Status badges */
    .ah-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem; font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .ah-badge::before {
        content: ""; width: 6px; height: 6px; border-radius: 50%;
        background: currentColor; opacity: 0.85;
    }
    .ah-badge--present  { background: rgba(16,185,129,0.12); color: #059669; border-color: rgba(16,185,129,0.22); }
    .ah-badge--late     { background: rgba(245,158,11,0.14); color: #b45309; border-color: rgba(245,158,11,0.24); }
    .ah-badge--absent   { background: rgba(220,38,38,0.12);  color: #dc2626; border-color: rgba(220,38,38,0.22); }
    .ah-badge--leave    { background: rgba(2,132,199,0.12);  color: #0284c7; border-color: rgba(2,132,199,0.22); }
    [data-theme="dark"] .ah-badge--present { color: #6ee7b7; }
    [data-theme="dark"] .ah-badge--late    { color: #fcd34d; }
    [data-theme="dark"] .ah-badge--absent  { color: #fca5a5; }
    [data-theme="dark"] .ah-badge--leave   { color: #7dd3fc; }

    /* Privacy blur */
    .privacy-mode .ah-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .ah-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive */
    @media (max-width: 400px) {
        #ahPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #ahPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .ah-table tbody td,
        .ah-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        .ah-empty { inset: 0.5rem; }
        .ah-empty h6 { font-size: 0.82rem; }
        .ah-empty p { font-size: 0.72rem; }
    }
    @media (max-width: 360px) {
        .ah-table tbody td,
        .ah-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* Print */
    .ah-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #ahPage { display: none !important; }
        .ah-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .ah-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .ah-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .ah-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .ah-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .ah-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .ah-print-only .print-kpi { flex: 1; text-align: center; }
        .ah-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .ah-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .ah-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .ah-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .ah-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .ah-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .ah-print-only td.ah-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .ah-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .ah-print-only thead { display: table-header-group; }
        .ah-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    /* Server-injected data (already RBAC scoped + position filtered) */
    const ATTENDANCE = <?= json_encode($allAttendance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 15;

    let filters = { dept: 'all', sy: '', month: '', week: '', status: '' };
    let filteredData = [];
    let currentPage = 1;

    let compositionChart = null;
    let trendChart = null;

    /* ============================================================
       Helpers
       ============================================================ */
    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3]);
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function fmtDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function fmtTime(str) {
        if (!str) return '—';
        const parts = String(str).split(':');
        if (parts.length < 2) return str;
        let h = parseInt(parts[0], 10);
        const m = parts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }
    function getSyRange(sy) {
        const m = /^(\d{4})-(\d{4})$/.exec(sy);
        if (!m) return null;
        const y = +m[1];
        return { start: new Date(y, 5, 1), end: new Date(y + 1, 4, 31, 23, 59, 59) };
    }
    function statusBadge(status) {
        const cls = {
            'Present':  'ah-badge--present',
            'Late':     'ah-badge--late',
            'Absent':   'ah-badge--absent',
            'On Leave': 'ah-badge--leave'
        }[status] || 'ah-badge--present';
        return '<span class="ah-badge ' + cls + '">' + escapeHtml(status) + '</span>';
    }
    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }

    /* ============================================================
       Apply filters
       ============================================================ */
    function applyFilters() {
        return ATTENDANCE.filter(r => {
            if (!deptMatches(r)) return false;

            const d = parseDate(r.attendance_date);
            if (!d) return false;

            if (filters.sy) {
                const range = getSyRange(filters.sy);
                if (!range || d < range.start || d > range.end) return false;
            }
            if (filters.month && (d.getMonth() + 1) !== +filters.month) return false;
            if (filters.week) {
                const day = d.getDate();
                const w = +filters.week;
                let ok = false;
                if (w === 1 && day >= 1  && day <= 7)  ok = true;
                if (w === 2 && day >= 8  && day <= 14) ok = true;
                if (w === 3 && day >= 15 && day <= 21) ok = true;
                if (w === 4 && day >= 22 && day <= 28) ok = true;
                if (w === 5 && day >= 29)              ok = true;
                if (!ok) return false;
            }
            if (filters.status && r.status !== filters.status) return false;

            return true;
        });
    }

    /* ============================================================
       KPIs
       ============================================================ */
    function renderKPIs(rows) {
        const total = rows.length;
        const present = rows.filter(r => r.status === 'Present').length;
        const late = rows.filter(r => r.status === 'Late').length;
        const absentLeave = rows.filter(r => r.status === 'Absent' || r.status === 'On Leave').length;

        document.getElementById('kpiTotal').textContent = total.toLocaleString();
        document.getElementById('kpiPresent').textContent = present.toLocaleString();
        document.getElementById('kpiLate').textContent = late.toLocaleString();
        document.getElementById('kpiAbsentLeave').textContent = absentLeave.toLocaleString();

        const pct = n => total > 0 ? Math.round((n / total) * 100) + '%' : '0%';
        document.getElementById('kpiPresentPct').textContent = pct(present);
        document.getElementById('kpiLatePct').textContent = pct(late);
        document.getElementById('kpiAbsentLeavePct').textContent = pct(absentLeave);
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

        const counts = { Present: 0, Late: 0, Absent: 0, 'On Leave': 0 };
        rows.forEach(r => { if (counts[r.status] !== undefined) counts[r.status]++; });

        const labels = Object.keys(counts);
        const data = labels.map(k => counts[k]);
        const total = data.reduce((a, b) => a + b, 0);

        document.getElementById('compositionBadge').textContent = total + ' total';

        const emptyEl = document.getElementById('compositionEmpty');
        const canvas = document.getElementById('compositionChart');
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
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#0284c7'],
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

        // Group by month, split by status
        const monthMap = {};
        rows.forEach(r => {
            const d = parseDate(r.attendance_date);
            if (!d) return;
            const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            if (!monthMap[key]) monthMap[key] = { Present: 0, Late: 0, Absent: 0, 'On Leave': 0 };
            if (monthMap[key][r.status] !== undefined) monthMap[key][r.status]++;
        });
        const keys = Object.keys(monthMap).sort();
        const labels = keys.map(k => {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: '2-digit' });
        });

        document.getElementById('trendBadge').textContent = keys.length + ' period' + (keys.length !== 1 ? 's' : '');

        const emptyEl = document.getElementById('trendEmpty');
        const canvas = document.getElementById('trendChart');
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
                    { label: 'Present',  data: keys.map(k => monthMap[k].Present), backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 },
                    { label: 'Late',     data: keys.map(k => monthMap[k].Late),    backgroundColor: '#f59e0b', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 },
                    { label: 'Absent',   data: keys.map(k => monthMap[k].Absent),  backgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 },
                    { label: 'On Leave', data: keys.map(k => monthMap[k]['On Leave']), backgroundColor: '#0284c7', borderRadius: 6, borderSkipped: false, maxBarThickness: 40 }
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
       Table
       ============================================================ */
    function renderTable(rows) {
        const tbody = document.getElementById('attendanceBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="8" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-clipboard-list d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No attendance records match your filters.
                </td></tr>`;
            document.getElementById('tableSubtitle').textContent = 'No records';
            document.getElementById('pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, total);
        const page = rows.slice(start, end);

        tbody.innerHTML = page.map(r => {
            const hours = r.hours_rendered != null ? r.hours_rendered : '—';
            return '<tr>' +
                '<td class="text-nowrap">' + escapeHtml(fmtDate(r.attendance_date)) + '</td>' +
                '<td class="ah-privacy-target">' +
                    '<div class="fw-semibold">' + escapeHtml(r.faculty_name || '—') + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.faculty_no || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(r.faculty_dept || '—') + '</td>' +
                '<td>' + statusBadge(r.status) + '</td>' +
                '<td class="d-none d-sm-table-cell text-nowrap">' + escapeHtml(fmtTime(r.time_in)) + '</td>' +
                '<td class="d-none d-sm-table-cell text-nowrap">' + escapeHtml(fmtTime(r.time_out)) + '</td>' +
                '<td class="d-none d-lg-table-cell text-center">' + hours + '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary">' + escapeHtml(r.notes || '—') + '</td>' +
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

        const total = rows.length;
        const present = rows.filter(r => r.status === 'Present').length;
        const late = rows.filter(r => r.status === 'Late').length;
        const absentLeave = rows.filter(r => r.status === 'Absent' || r.status === 'On Leave').length;

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (DEPT_NAMES[filters.dept] || ('Dept #' + filters.dept)));
        if (filters.sy) filterParts.push('School Year: ' + filters.sy);
        if (filters.month) {
            const mn = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            filterParts.push('Month: ' + mn[+filters.month - 1]);
        }
        if (filters.week) filterParts.push('Week: ' + filters.week);
        if (filters.status) filterParts.push('Status: ' + filters.status);
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td>' + escapeHtml(fmtDate(r.attendance_date)) + '</td>' +
                '<td class="ah-print-blur">' + escapeHtml(r.faculty_name || '—') + '</td>' +
                '<td>' + escapeHtml(r.faculty_dept || '—') + '</td>' +
                '<td>' + escapeHtml(r.status) + '</td>' +
                '<td>' + escapeHtml(fmtTime(r.time_in)) + '</td>' +
                '<td>' + escapeHtml(fmtTime(r.time_out)) + '</td>' +
                '<td style="text-align:center;">' + (r.hours_rendered != null ? r.hours_rendered : '—') + '</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Attendance History Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total</div>
                    <div class="print-kpi-value">${total}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Present</div>
                    <div class="print-kpi-value">${present}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Late</div>
                    <div class="print-kpi-value">${late}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Absent / Leave</div>
                    <div class="print-kpi-value">${absentLeave}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>Faculty</th><th>Department</th><th>Status</th>
                        <th>Time In</th><th>Time Out</th><th>Hours</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="7" style="text-align:center;">No attendance records in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} record${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Date', 'Faculty', 'Faculty No', 'Department', 'Status', 'Time In', 'Time Out', 'Hours', 'Notes'];
        const lines = rows.map(r => [
            r.attendance_date,
            r.faculty_name || '',
            r.faculty_no || '',
            r.faculty_dept || '',
            r.status,
            r.time_in || '',
            r.time_out || '',
            r.hours_rendered != null ? r.hours_rendered : '',
            r.notes || ''
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'attendance_history_' + new Date().toISOString().slice(0, 10) + '.csv';
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
        ATTENDANCE.forEach(r => {
            const d = parseDate(r.attendance_date);
            if (!d) return;
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

    /* ============================================================
       Render
       ============================================================ */
    function render() {
        filteredData = applyFilters();
        currentPage = 1;
        renderKPIs(filteredData);
        renderCompositionChart(filteredData);
        renderTrendChart(filteredData);
        renderTable(filteredData);
        renderPrintReport(filteredData);
    }

    function bindFilters() {
        document.querySelectorAll('#ahPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', sy: '', month: '', week: '', status: '' };
        document.querySelectorAll('#ahPage [data-filter]').forEach(el => {
            el.value = '';
        });
        // department defaults to "all" if present
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
        const KEY = 'smsDeanAttendanceHistoryPrivacy';
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