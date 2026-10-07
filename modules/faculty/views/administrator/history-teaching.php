<?php
/**
 * SMS 2 - Faculty Admin - Teaching Load History
 * Layout: 2 rows × 2 columns.
 *   Row 1: Teaching Load Trend | Teaching Load Records
 *   Row 2: Status Distribution | Units by Department
 * Pagination: 10 per page. Responsive to 344px.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

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
   LOAD ALL ROWS
   ============================================================ */
$allRows = [];
$loadError = null;
$totalRawCount = 0;

try {
    if (!$pdo instanceof PDO) { throw new RuntimeException('Database unavailable.'); }

    /* ---- Requests ---- */
    $reqStmt = $pdo->query("
        SELECT
            tlr.load_request_id       AS row_id,
            'Request'                 AS kind,
            tlr.faculty_id            AS faculty_id,
            tlr.term_id               AS term_id,
            tlr.total_units           AS total_units,
            tlr.status                AS status,
            tlr.submitted_at          AS acted_at,
            tlr.reviewed_at           AS reviewed_at,
            tlr.comments              AS remarks,
            at.academic_year,
            at.semester,
            fp.first_name,
            fp.last_name,
            fp.designated_department AS dept,
            (SELECT COUNT(*) FROM faculty_db.teaching_load_request_items tri
                WHERE tri.load_request_id = tlr.load_request_id) AS subject_count
        FROM faculty_db.teaching_load_requests tlr
        LEFT JOIN faculty_db.academic_terms at ON at.term_id = tlr.term_id
        LEFT JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = tlr.faculty_id
        ORDER BY tlr.submitted_at DESC
    ");
    foreach ($reqStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $allRows[] = [
            'row_id'        => (int) $r['row_id'],
            'kind'          => 'Request',
            'faculty_id'    => (int) $r['faculty_id'],
            'term_id'       => (int) $r['term_id'],
            'academic_year' => (string) ($r['academic_year'] ?? ''),
            'semester'      => (string) ($r['semester'] ?? ''),
            'subject_count' => (int) ($r['subject_count'] ?? 0),
            'total_units'   => (float) ($r['total_units'] ?? 0),
            'status'        => (string) ($r['status'] ?? 'Pending'),
            'acted_at'      => (string) ($r['acted_at'] ?? ''),
            'reviewed_at'   => (string) ($r['reviewed_at'] ?? ''),
            'remarks'       => (string) ($r['remarks'] ?? ''),
            'first_name'    => (string) ($r['first_name'] ?? ''),
            'last_name'     => (string) ($r['last_name'] ?? ''),
            'dept'          => (string) ($r['dept'] ?? ''),
        ];
    }

    /* ---- Archived ---- */
    $histStmt = $pdo->query("
        SELECT
            tlh.history_id            AS row_id,
            'Archived'                AS kind,
            tlh.faculty_id            AS faculty_id,
            tlh.term_id               AS term_id,
            tlh.total_units           AS total_units,
            tlh.subject_count         AS subject_count,
            tlh.total_students        AS total_students,
            tlh.status                AS status,
            at.academic_year,
            at.semester,
            fp.first_name,
            fp.last_name,
            fp.designated_department AS dept
        FROM faculty_db.teaching_load_history tlh
        LEFT JOIN faculty_db.academic_terms at ON at.term_id = tlh.term_id
        LEFT JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = tlh.faculty_id
        ORDER BY tlh.history_id DESC
    ");
    foreach ($histStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $year = $r['academic_year'] ?? '';
        $actedAt = '';
        if (preg_match('/^(\d{4})-/', $year, $m)) {
            $actedAt = $m[1] . '-06-01 00:00:00';
        }
        $allRows[] = [
            'row_id'        => (int) $r['row_id'],
            'kind'          => 'Archived',
            'faculty_id'    => (int) $r['faculty_id'],
            'term_id'       => (int) $r['term_id'],
            'academic_year' => (string) ($r['academic_year'] ?? ''),
            'semester'      => (string) ($r['semester'] ?? ''),
            'subject_count' => (int) ($r['subject_count'] ?? 0),
            'total_units'   => (float) ($r['total_units'] ?? 0),
            'total_students'=> (int) ($r['total_students'] ?? 0),
            'status'        => (string) ($r['status'] ?? 'Completed'),
            'acted_at'      => $actedAt,
            'reviewed_at'   => '',
            'remarks'       => '',
            'first_name'    => (string) ($r['first_name'] ?? ''),
            'last_name'     => (string) ($r['last_name'] ?? ''),
            'dept'          => (string) ($r['dept'] ?? ''),
        ];
    }

    usort($allRows, function ($a, $b) {
        return strcmp($b['acted_at'], $a['acted_at']);
    });

    $totalRawCount = count($allRows);
} catch (Throwable $e) {
    error_log('Teaching load history load failed: ' . $e->getMessage());
    $loadError = 'Some data could not be loaded. Try again later.';
}

/* ============================================================
   FILTER OPTIONS
   ============================================================ */
$schoolYears = [];
foreach ($allRows as $r) {
    if (!empty($r['academic_year'])) {
        $schoolYears[$r['academic_year']] = true;
    } elseif ($r['acted_at']) {
        $ts = strtotime($r['acted_at']);
        if ($ts) {
            $y = (int) date('Y', $ts);
            $m = (int) date('n', $ts);
            $syStart = $m >= 6 ? $y : $y - 1;
            $schoolYears[$syStart . '-' . ($syStart + 1)] = true;
        }
    }
}
ksort($schoolYears);
$schoolYears = array_keys($schoolYears);

$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
$weekLabels = [1=>'Week 1 · 1–7',2=>'Week 2 · 8–14',3=>'Week 3 · 15–21',4=>'Week 4 · 22–28',5=>'Week 5 · 29–31'];

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Teaching Load History';
$activeModule = 'faculty';
$activePage   = 'teaching-load-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'History', 'url' => null],
    ['label' => 'Teaching Load', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="tlPage">

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Teaching Load History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Faculty identities are blurred for privacy.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Teaching Load History</h1>
            <p class="text-body-secondary mb-0">Submitted load requests and archived teaching assignments across terms.</p>
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
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Loads</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">All records</small>
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
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Approved / Done</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiApproved">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">
                            <span id="kpiApprovedPct">0%</span> of total
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
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiPending">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Awaiting review</small>
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
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Units</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiUnits">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Units assigned</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sy">School Year</label>
                    <select class="form-select form-select-sm" id="f_sy">
                        <option value="">All years</option>
                        <?php foreach ($schoolYears as $sy): ?>
                            <option value="<?= htmlspecialchars($sy) ?>"><?= htmlspecialchars($sy) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_month">Month</label>
                    <select class="form-select form-select-sm" id="f_month">
                        <option value="">All months</option>
                        <?php foreach ($monthNames as $num => $name): ?>
                            <option value="<?= $num ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_week">Week</label>
                    <select class="form-select form-select-sm" id="f_week">
                        <option value="">All weeks</option>
                        <?php foreach ($weekLabels as $num => $label): ?>
                            <option value="<?= $num ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
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
         ROW 1 — Teaching Load Trend (left) | Teaching Load Records (right)
         ============================================================ -->
    <div class="row g-3 mb-3">
        <!-- Trend -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Teaching Load Trend</h5>
                            <p class="text-body-secondary small mb-0">Monthly total units assigned</p>
                        </div>
                        <span class="badge text-bg-light border" id="trendCountBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:320px;">
                        <canvas id="trendChart"></canvas>
                        <div class="tl-empty d-none" id="trendEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No trend data yet</h6>
                            <p>Load trends will appear once teaching loads are submitted.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Records -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100 overflow-hidden d-flex flex-column">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Teaching Load Records</h5>
                        <p class="text-body-secondary small mb-0" id="recordsSubtitle">No results</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#10b981;"></span>Approved
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Pending
                        </span>
                        <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                            <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#ef4444;"></span>Rejected
                        </span>
                    </div>
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table align-middle mb-0 tl-history-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Term</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell text-center">Subj</th>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center">Units</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recordsBody"></tbody>
                    </table>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 no-print">
                    <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Teaching load pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         ROW 2 — Status Distribution (left) | Units by Department (right)
         ============================================================ -->
    <div class="row g-3">
        <!-- Status -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Status Distribution</h5>
                            <p class="text-body-secondary small mb-0">Approved vs pending vs rejected</p>
                        </div>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:300px;">
                        <canvas id="statusChart"></canvas>
                        <div class="tl-empty d-none" id="statusEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No status data yet</h6>
                            <p>Status breakdown will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Units by Department</h5>
                            <p class="text-body-secondary small mb-0">Total units assigned per department</p>
                        </div>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:300px;">
                        <canvas id="deptChart"></canvas>
                        <div class="tl-empty d-none" id="deptEmpty">
                            <i class="fas fa-building"></i>
                            <h6>No department data</h6>
                            <p>Department breakdown will appear once records exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only table -->
<div class="tl-print-only" id="printTable" aria-hidden="true"></div>

<style>
    .tl-empty {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; padding: 1rem;
        pointer-events: none;
    }
    .tl-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.75rem; color: var(--sms-text-muted); }
    .tl-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.25rem; }
    .tl-empty p { font-size: 0.82rem; color: var(--sms-text-muted); margin: 0; max-width: 280px; }

    .privacy-mode .tl-history-identity {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .tl-history-identity:hover { filter: blur(0); -webkit-filter: blur(0); }

    .tl-history-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .tl-history-table tbody tr:hover td { background: var(--sms-dropdown-hover); }

    .tl-print-only { display: none; }

    /* Compact table cells so 10 rows fit comfortably in the card */
    .tl-history-table tbody td { padding: 0.6rem 0.75rem; font-size: 0.85rem; }
    .tl-history-table thead th { padding: 0.65rem 0.75rem; font-size: 0.7rem; }

    @media (max-width: 400px) {
        #tlPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #tlPage h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .tl-history-table tbody td,
        .tl-history-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        #tlPage .card-title { font-size: 0.9rem; }
    }
    @media (max-width: 360px) {
        .tl-history-table tbody td,
        .tl-history-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    @media print {
        body { background: #fff !important; color: #000 !important; }
        .no-print, nav, header, footer, .sidebar, .sms-sidebar, .sms-navbar,
        .breadcrumb, .card-header, .card-footer, .btn, button, .modal { display: none !important; }
        #tlPage { padding: 0 !important; }
        .container-fluid { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important;
                background: #fff !important; backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important; border-radius: 8px !important; }
        .table { font-size: 10pt; color: #000 !important; }
        .table thead th { background: #f1f5f9 !important; color: #000 !important;
                          border-color: #ccc !important; padding: 0.5rem 0.6rem !important; }
        .table tbody td { color: #000 !important; border-color: #ddd !important;
                          background: transparent !important; padding: 0.5rem 0.6rem !important; }
        .table tbody tr:hover td { background: transparent !important; }
        .badge { border: 1px solid #999 !important; background: #f3f4f6 !important; color: #000 !important; }

        .tl-history-table tbody td:nth-child(1) {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
            -webkit-user-select: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const TL_ROWS = <?= json_encode($allRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PAGE_SIZE = 10;             // ← 10 per page
    const TABLE_EMPTY = <?= $totalRawCount === 0 ? 'true' : 'false' ?>;

    let currentFilters = { sy: '', month: '', week: '' };
    let filteredRows = TL_ROWS.slice();
    let currentPage = 1;

    let trendChart = null, statusChart = null, deptChart = null;

    /* ============================================================
       THEME-AWARE COLORS
       ============================================================ */
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

    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
    }
    function fmtDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function fullName(row) {
        const name = (row.first_name + ' ' + row.last_name).trim();
        return name || '—';
    }
    function termLabel(row) {
        const y = row.academic_year || '';
        const s = row.semester || '';
        if (y && s) return y + ' · ' + s;
        if (y) return y;
        if (s) return s;
        return '—';
    }
    function getSyRange(sy) {
        const m = /^(\d{4})-(\d{4})$/.exec(sy);
        if (!m) return null;
        const y = +m[1];
        return { start: new Date(y, 5, 1), end: new Date(y + 1, 4, 31, 23, 59, 59) };
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function normalizeStatus(s) {
        const l = (s || '').toLowerCase();
        if (l === 'approved' || l === 'completed' || l === 'current') return 'Approved';
        if (l === 'pending') return 'Pending';
        if (l === 'rejected') return 'Rejected';
        return s || 'Unknown';
    }

    function applyFilters() {
        filteredRows = TL_ROWS.filter(function (r) {
            const d = parseDate(r.acted_at);
            if (!d) return false;

            if (currentFilters.sy) {
                if (r.academic_year && r.academic_year === currentFilters.sy) {
                    // match
                } else {
                    const range = getSyRange(currentFilters.sy);
                    if (!range || d < range.start || d > range.end) return false;
                }
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
        });
        currentPage = 1;
        render();
    }

    function render() {
        renderKPIs();
        renderCharts();
        renderTable();
        renderPager();
        renderPrintTable();
    }

    function renderKPIs() {
        const total = filteredRows.length;
        let approved = 0, pending = 0, rejected = 0, units = 0;
        filteredRows.forEach(function (row) {
            const s = normalizeStatus(row.status);
            if (s === 'Approved') approved++;
            else if (s === 'Pending') pending++;
            else if (s === 'Rejected') rejected++;
            units += (row.total_units || 0);
        });

        document.getElementById('kpiTotal').textContent    = total.toLocaleString();
        document.getElementById('kpiApproved').textContent = approved.toLocaleString();
        document.getElementById('kpiPending').textContent  = pending.toLocaleString();
        document.getElementById('kpiUnits').textContent    = units.toLocaleString(undefined, { maximumFractionDigits: 1 });
        document.getElementById('kpiApprovedPct').textContent =
            (total > 0 ? Math.round((approved / total) * 100) : 0) + '%';
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

        /* ---- Trend ---- */
        const monthMap = {};
        filteredRows.forEach(function (row) {
            const d = parseDate(row.acted_at);
            if (!d) return;
            const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            if (!monthMap[key]) monthMap[key] = { units: 0, count: 0 };
            monthMap[key].units += (row.total_units || 0);
            monthMap[key].count++;
        });
        const mKeys = Object.keys(monthMap).sort();
        const labels = mKeys.map(function (k) {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: 'numeric' });
        });
        const units = mKeys.map(function (k) { return +monthMap[k].units.toFixed(1); });

        const trendEmpty = document.getElementById('trendEmpty');
        const trendCanvas = document.getElementById('trendChart');
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
                        label: 'Units',
                        data: units,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.14)',
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
                        y: { beginAtZero: true, ticks: { color: c.text, font: { size: 11 } },
                             grid: { color: c.grid } }
                    }
                }
            });
        }

        /* ---- Status ---- */
        let sA = 0, sP = 0, sR = 0;
        filteredRows.forEach(function (row) {
            const s = normalizeStatus(row.status);
            if (s === 'Approved') sA++;
            else if (s === 'Pending') sP++;
            else if (s === 'Rejected') sR++;
        });
        const statusEmpty = document.getElementById('statusEmpty');
        const statusCanvas = document.getElementById('statusChart');
        if (sA + sP + sR === 0) {
            statusEmpty.classList.remove('d-none');
            statusCanvas.style.visibility = 'hidden';
        } else {
            statusEmpty.classList.add('d-none');
            statusCanvas.style.visibility = 'visible';
            if (statusChart) statusChart.destroy();
            statusChart = new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [sA, sP, sR],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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

        /* ---- Department ---- */
        const deptMap = {};
        filteredRows.forEach(function (row) {
            const k = row.dept || 'Unassigned';
            deptMap[k] = (deptMap[k] || 0) + (row.total_units || 0);
        });
        const deptKeys = Object.keys(deptMap)
            .map(function (k) { return { key: k, units: deptMap[k] }; })
            .sort(function (a, b) { return b.units - a.units; })
            .slice(0, 8);
        const deptLabels = deptKeys.map(function (o) { return o.key; });
        const deptUnits  = deptKeys.map(function (o) { return +o.units.toFixed(1); });

        const deptEmpty = document.getElementById('deptEmpty');
        const deptCanvas = document.getElementById('deptChart');
        if (deptLabels.length === 0) {
            deptEmpty.classList.remove('d-none');
            deptCanvas.style.visibility = 'hidden';
        } else {
            deptEmpty.classList.add('d-none');
            deptCanvas.style.visibility = 'visible';
            if (deptChart) deptChart.destroy();
            const palette = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];
            deptChart = new Chart(deptCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        label: 'Units',
                        data: deptUnits,
                        backgroundColor: palette,
                        borderRadius: 8, borderSkipped: false, maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.text, font: { size: 11 }, maxRotation: 40 } },
                        y: { beginAtZero: true, ticks: { color: c.text, font: { size: 11 } },
                             grid: { color: c.grid } }
                    }
                }
            });
        }
    }

    function statusBadge(status) {
        const s = normalizeStatus(status);
        if (s === 'Approved') return 'text-bg-success';
        if (s === 'Pending')  return 'text-bg-warning';
        if (s === 'Rejected') return 'text-bg-danger';
        return 'text-bg-secondary';
    }

    function renderTable() {
        const tbody = document.getElementById('recordsBody');
        const total = filteredRows.length;

        if (total === 0) {
            const noFilter = !currentFilters.sy && !currentFilters.month && !currentFilters.week;
            if (TABLE_EMPTY || noFilter) {
                tbody.innerHTML = `
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="d-inline-flex flex-column align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:64px;height:64px;background:rgba(100,116,139,0.10);color:#64748b;font-size:1.6rem;">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No teaching load history yet</h6>
                            <p class="text-body-secondary small mb-0" style="max-width:320px;">
                                This panel will populate once teaching loads are submitted or archived.
                            </p>
                        </div>
                    </td></tr>`;
            } else {
                tbody.innerHTML = `
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="d-inline-flex flex-column align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:64px;height:64px;background:rgba(100,116,139,0.10);color:#64748b;font-size:1.6rem;">
                                <i class="fas fa-filter-circle-xmark"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No records match your filters</h6>
                            <p class="text-body-secondary small mb-3" style="max-width:320px;">
                                Try adjusting or clearing your filters.
                            </p>
                            <button class="btn btn-outline-secondary btn-sm" id="emptyResetBtn">
                                <i class="fas fa-rotate-left me-1"></i> Clear filters
                            </button>
                        </div>
                    </td></tr>`;
                const b = document.getElementById('emptyResetBtn');
                if (b) b.addEventListener('click', resetAllFilters);
            }
            return;
        }

        const start = (currentPage - 1) * PAGE_SIZE;
        const pageRows = filteredRows.slice(start, start + PAGE_SIZE);

        tbody.innerHTML = pageRows.map(function (r) {
            return '<tr>' +
                '<td class="tl-history-identity">' +
                    '<div class="fw-semibold">' + escapeHtml(fullName(r)) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.dept || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(termLabel(r)) + '</td>' +
                '<td class="d-none d-sm-table-cell text-center">' + (r.subject_count || 0) + '</td>' +
                '<td class="text-center fw-bold">' + (r.total_units || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) + '</td>' +
                '<td><span class="badge ' + statusBadge(r.status) + '">' + escapeHtml(normalizeStatus(r.status)) + '</span></td>' +
            '</tr>';
        }).join('');
    }

    function renderPager() {
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        const info = document.getElementById('pagerInfo');
        const pager = document.getElementById('pager');

        if (total === 0) {
            info.textContent = 'Showing 0 of 0';
            pager.innerHTML = '';
            return;
        }
        const start = (currentPage - 1) * PAGE_SIZE + 1;
        const end = Math.min(currentPage * PAGE_SIZE, total);
        info.textContent = 'Showing ' + start + '–' + end + ' of ' + total;

        document.getElementById('recordsSubtitle').textContent =
            'Showing ' + start + '–' + end + ' of ' + total + ' records';

        if (totalPages <= 1) { pager.innerHTML = ''; return; }

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
        pager.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.getAttribute('data-page'), 10);
                if (!p || p < 1 || p > totalPages || p === currentPage) return;
                currentPage = p;
                renderTable();
                renderPager();
                document.querySelector('.tl-history-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function renderPrintTable() {
        const host = document.getElementById('printTable');
        if (!host) return;
        if (filteredRows.length === 0) { host.innerHTML = ''; return; }

        const rows = filteredRows.map(function (r) {
            return '<tr>' +
                '<td class="blur">' + escapeHtml(fullName(r)) + '</td>' +
                '<td>' + escapeHtml(termLabel(r)) + '</td>' +
                '<td>' + (r.subject_count || 0) + '</td>' +
                '<td>' + (r.total_units || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) + '</td>' +
                '<td>' + escapeHtml(normalizeStatus(r.status)) + '</td>' +
                '<td>' + escapeHtml(r.kind) + '</td>' +
                '<td>' + fmtDate(r.acted_at) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Term</th><th>Subjects</th>
                        <th>Units</th><th>Status</th><th>Kind</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
    }

    function exportCsv() {
        const headers = ['Faculty', 'Department', 'Term', 'Subjects', 'Total Units', 'Status', 'Kind', 'Date', 'Remarks'];
        const rows = filteredRows.map(function (r) {
            return [
                fullName(r), r.dept, termLabel(r),
                r.subject_count || 0, r.total_units || 0,
                normalizeStatus(r.status), r.kind, r.acted_at, r.remarks
            ];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(function (e) {
            csv += e.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'teaching-load-history_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function bindFilters() {
        const map = { f_sy: 'sy', f_month: 'month', f_week: 'week' };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', function () {
                currentFilters[map[id]] = el.value;
                applyFilters();
            });
        });
    }
    function resetAllFilters() {
        currentFilters = { sy: '', month: '', week: '' };
        document.getElementById('f_sy').value = '';
        document.getElementById('f_month').value = '';
        document.getElementById('f_week').value = '';
        applyFilters();
    }

    function init() {
        bindFilters();
        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
        document.getElementById('printBtn').addEventListener('click', function () { window.print(); });

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsTeachingLoadHistoryPrivacyMode';
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

        applyFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>