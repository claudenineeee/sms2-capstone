<?php
/**
 * SMS 2 - Faculty Admin - Approval History
 * Charts + Records redesigned; Top Approvers sits beside the records table.
 * Client-side filtering, theme-aware chart legends, responsive to 344px.
 * Records: 10 per page. Top Approvers: top 8 ranked list.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

/* ============================================================
   DATABASE — PATCHED FOR HOSTFORGE
   ============================================================ */
$pdo = null;
try {
    $host = getenv('DB_HOST')     ?: 'localhost';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
    $name = getenv('DB_DATABASE') ?: 'faculty_db';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    error_log('Approval history DB connection failed: ' . $e->getMessage());
    $pdo = null;
}

/* ============================================================
   LOAD ALL ROWS
   ============================================================ */
$allRows = [];
$loadError = null;
$totalRawCount = 0;

try {
    if (!$pdo instanceof PDO) { throw new RuntimeException('Database unavailable.'); }

    // REMOVED faculty_db. prefixes
    $clearStmt = $pdo->query("
        SELECT
            coa.id                  AS row_id,
            'Clearance'             AS category,
            coa.office              AS subject,
            coa.approval_ref        AS ref_no,
            coa.faculty_id          AS faculty_id,
            coa.approver_name       AS actor_name,
            coa.approver_role       AS actor_role,
            coa.status              AS status,
            coa.remarks             AS remarks,
            coa.approved_at         AS acted_at,
            fp.first_name,
            fp.last_name,
            fp.designated_department AS dept
        FROM clearance_office_approvals coa
        LEFT JOIN faculty_profiles fp ON fp.id = coa.faculty_id
        ORDER BY coa.approved_at DESC
    ");
    foreach ($clearStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $allRows[] = [
            'row_id'     => (int) $r['row_id'],
            'category'   => 'Clearance',
            'subject'    => (string) $r['subject'],
            'ref_no'     => (string) $r['ref_no'],
            'faculty_id' => (int) $r['faculty_id'],
            'actor_name' => (string) $r['actor_name'],
            'actor_role' => (string) $r['actor_role'],
            'status'     => (string) $r['status'],
            'remarks'    => (string) ($r['remarks'] ?? ''),
            'acted_at'   => (string) $r['acted_at'],
            'first_name' => (string) ($r['first_name'] ?? ''),
            'last_name'  => (string) ($r['last_name'] ?? ''),
            'dept'       => (string) ($r['dept'] ?? ''),
        ];
    }

    // REMOVED faculty_db. prefixes
    $leaveStmt = $pdo->query("
        SELECT
            lr.id                   AS row_id,
            'Leave'                 AS category,
            lr.leave_type           AS subject,
            lr.request_ref          AS ref_no,
            lr.faculty_id           AS faculty_id,
            lr.approver_id          AS actor_id,
            lr.approved_by_external_id AS actor_ext,
            lr.approval_status      AS status,
            lr.approver_comment     AS remarks,
            COALESCE(lr.approved_at, lr.updated_at, lr.created_at) AS acted_at,
            fp.first_name,
            fp.last_name,
            fp.designated_department AS dept
        FROM leave_requests lr
        LEFT JOIN faculty_profiles fp ON fp.faculty_id = lr.faculty_id
        WHERE lr.approval_status IN ('Approved','Rejected','Pending','Cancelled')
        ORDER BY acted_at DESC
    ");
    foreach ($leaveStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $allRows[] = [
            'row_id'     => (int) $r['row_id'],
            'category'   => 'Leave',
            'subject'    => (string) $r['subject'],
            'ref_no'     => (string) $r['ref_no'],
            'faculty_id' => (int) $r['faculty_id'],
            'actor_name' => $r['actor_ext'] ? ('User #' . $r['actor_ext']) : ($r['actor_id'] ? ('User #' . $r['actor_id']) : '—'),
            'actor_role' => 'Approver',
            'status'     => (string) $r['status'],
            'remarks'    => (string) ($r['remarks'] ?? ''),
            'acted_at'   => (string) $r['acted_at'],
            'first_name' => (string) ($r['first_name'] ?? ''),
            'last_name'  => (string) ($r['last_name'] ?? ''),
            'dept'       => (string) ($r['dept'] ?? ''),
        ];
    }

    usort($allRows, function ($a, $b) {
        return strcmp($b['acted_at'], $a['acted_at']);
    });
    $totalRawCount = count($allRows);
} catch (Throwable $e) {
    error_log('Approval history load failed: ' . $e->getMessage());
    $loadError = 'Some data could not be loaded. Try again later.';
}

/* ============================================================
   FILTER OPTIONS
   ============================================================ */
$schoolYears = [];
foreach ($allRows as $r) {
    $ts = strtotime($r['acted_at']);
    if (!$ts) continue;
    $y = (int) date('Y', $ts);
    $m = (int) date('n', $ts);
    $syStart = $m >= 6 ? $y : $y - 1;
    $schoolYears[$syStart . '-' . ($syStart + 1)] = true;
}
ksort($schoolYears);
$schoolYears = array_keys($schoolYears);

$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
$weekLabels = [1=>'Week 1 · 1–7',2=>'Week 2 · 8–14',3=>'Week 3 · 15–21',4=>'Week 4 · 22–28',5=>'Week 5 · 29–31'];

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Approval History';
$activeModule = 'faculty';
$activePage   = 'approval-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'History', 'url' => null],
    ['label' => 'Approval History', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="apprPage">

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Approval History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Faculty identities are blurred for privacy.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Approval History</h1>
            <p class="text-body-secondary mb-0">Every approval action taken across clearance and leave requests.</p>
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
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Actions</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">All approvals</small>
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
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Approved</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiApproved">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">
                            <span id="kpiApprovedPct">0%</span> approval rate
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
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiPending">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Awaiting action</small>
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
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Rejected</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiRejected">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Declined actions</small>
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

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">

        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Approval Trend</h5>
                            <p class="text-body-secondary small mb-0">Monthly breakdown of approved, pending, and rejected actions</p>
                        </div>
                        <span class="badge text-bg-light border" id="trendCountBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100" style="height:280px;">
                        <canvas id="trendChart"></canvas>
                        <div class="appr-empty d-none" id="trendEmpty">
                            <i class="fas fa-chart-line"></i>
                            <h6>No trend data yet</h6>
                            <p>Activity trends will appear once approvals come in.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Status Distribution</h5>
                            <p class="text-body-secondary small mb-0">Approved vs pending vs rejected</p>
                        </div>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="statusChart"></canvas>
                        <div class="appr-empty d-none" id="statusEmpty">
                            <i class="fas fa-chart-pie"></i>
                            <h6>No status data yet</h6>
                            <p>Status breakdown will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">By Category</h5>
                            <p class="text-body-secondary small mb-0">Clearance vs leave approvals</p>
                        </div>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="categoryChart"></canvas>
                        <div class="appr-empty d-none" id="categoryEmpty">
                            <i class="fas fa-chart-column"></i>
                            <h6>No category data</h6>
                            <p>Category breakdown will appear once approvals exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Records + Top Approvers ================= -->
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border shadow-sm h-100 overflow-hidden">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Approval Records</h5>
                        <p class="text-body-secondary small mb-0" id="recordsSubtitle">No results</p>
                    </div>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
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

                <div class="table-responsive">
                    <table class="table align-middle mb-0 appr-history-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Category</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Subject</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Approver</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell">Ref</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Date</th>
                            </tr>
                        </thead>
                        <tbody id="recordsBody"></tbody>
                    </table>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
                    <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Approval pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="card-title mb-1 fw-bold">Top Approvers</h5>
                    <p class="text-body-secondary small mb-0">Users with the most approval actions</p>
                </div>
                <div class="card-body" id="approverBody">
                    <div class="appr-empty-static d-none" id="approverEmpty">
                        <i class="fas fa-user-tie"></i>
                        <h6>No approver data yet</h6>
                        <p>Top approvers will appear here.</p>
                    </div>
                    <div class="d-flex flex-column gap-3" id="approverBars"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only table -->
<div class="appr-print-only" id="printTable" aria-hidden="true"></div>

<style>
    .appr-empty {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; padding: 1rem;
        pointer-events: none;
    }
    .appr-empty i { font-size: 2rem; opacity: 0.35; margin-bottom: 0.75rem; color: var(--sms-text-muted); }
    .appr-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.25rem; }
    .appr-empty p { font-size: 0.82rem; color: var(--sms-text-muted); margin: 0; max-width: 280px; }

    .appr-empty-static {
        text-align: center;
        padding: 2.5rem 1rem;
    }
    .appr-empty-static i {
        font-size: 2.25rem; opacity: 0.3; display: block;
        margin-bottom: 0.75rem; color: var(--sms-text-muted);
    }
    .appr-empty-static h6 { font-weight: 700; color: var(--sms-heading); }
    .appr-empty-static p { font-size: 0.82rem; color: var(--sms-text-muted); margin: 0; }

    .privacy-mode .appr-history-identity {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .appr-history-identity:hover { filter: blur(0); -webkit-filter: blur(0); }

    .appr-history-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .appr-history-table tbody tr:hover td { background: var(--sms-dropdown-hover); }

    .appr-print-only { display: none; }

    @media (max-width: 400px) {
        #apprPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #apprPage h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .appr-history-table tbody td,
        .appr-history-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        #apprPage .card-title { font-size: 0.9rem; }
    }
    @media (max-width: 360px) {
        .appr-history-table tbody td,
        .appr-history-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    @media print {
        body { background: #fff !important; color: #000 !important; }
        .no-print, nav, header, footer, .sidebar, .sms-sidebar, .sms-navbar,
        .breadcrumb, .card-header, .card-footer, .btn, button, .modal { display: none !important; }
        #apprPage { padding: 0 !important; }
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

        .appr-history-table tbody td:nth-child(1) {
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

    const APPR_ROWS = <?= json_encode($allRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PAGE_SIZE = 10;              // ← Approval Records: 10 per page
    const TOP_APPROVERS_LIMIT = 8;     // ← Top Approvers: top 8 ranked
    const TABLE_EMPTY = <?= $totalRawCount === 0 ? 'true' : 'false' ?>;

    let currentFilters = { sy: '', month: '', week: '' };
    let filteredRows = APPR_ROWS.slice();
    let currentPage = 1;

    let trendChart = null, statusChart = null, categoryChart = null;

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

    /* ============================================================
       HELPERS
       ============================================================ */
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
        if (l === 'approved' || l === 'signed') return 'Approved';
        if (l === 'pending') return 'Pending';
        if (l === 'rejected' || l === 'denied') return 'Rejected';
        if (l === 'cancelled') return 'Cancelled';
        return s || 'Unknown';
    }

    /* ============================================================
       FILTER
       ============================================================ */
    function applyFilters() {
        filteredRows = APPR_ROWS.filter(function (r) {
            const d = parseDate(r.acted_at);
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
        });
        currentPage = 1;
        render();
    }

    /* ============================================================
       RENDER
       ============================================================ */
    function render() {
        renderKPIs();
        renderCharts();
        renderApprovers();
        renderTable();
        renderPager();
        renderPrintTable();
    }

    function renderKPIs() {
        const total = filteredRows.length;
        let a = 0, p = 0, r = 0;
        filteredRows.forEach(function (row) {
            const s = normalizeStatus(row.status);
            if (s === 'Approved') a++;
            else if (s === 'Pending') p++;
            else if (s === 'Rejected') r++;
        });
        document.getElementById('kpiTotal').textContent     = total.toLocaleString();
        document.getElementById('kpiApproved').textContent  = a.toLocaleString();
        document.getElementById('kpiPending').textContent   = p.toLocaleString();
        document.getElementById('kpiRejected').textContent  = r.toLocaleString();
        document.getElementById('kpiApprovedPct').textContent =
            (total > 0 ? Math.round((a / total) * 100) : 0) + '%';
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
            if (!monthMap[key]) monthMap[key] = { approved: 0, pending: 0, rejected: 0 };
            const s = normalizeStatus(row.status);
            if (s === 'Approved') monthMap[key].approved++;
            else if (s === 'Pending') monthMap[key].pending++;
            else if (s === 'Rejected') monthMap[key].rejected++;
        });
        const mKeys = Object.keys(monthMap).sort();
        const labels   = mKeys.map(function (k) {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: 'numeric' });
        });
        const approved = mKeys.map(function (k) { return monthMap[k].approved; });
        const pending  = mKeys.map(function (k) { return monthMap[k].pending; });
        const rejected = mKeys.map(function (k) { return monthMap[k].rejected; });

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
                    datasets: [
                        { label: 'Approved', data: approved, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Pending',  data: pending,  borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.10)', tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Rejected', data: rejected, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.08)',  tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: legendCfg },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: c.text, font: { size: 11 } },
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

        /* ---- Category ---- */
        const catMap = {};
        filteredRows.forEach(function (row) {
            const k = row.category || 'Other';
            catMap[k] = (catMap[k] || 0) + 1;
        });
        const catLabels = Object.keys(catMap);
        const catCounts = catLabels.map(function (k) { return catMap[k]; });

        const categoryEmpty = document.getElementById('categoryEmpty');
        const categoryCanvas = document.getElementById('categoryChart');
        if (catLabels.length === 0) {
            categoryEmpty.classList.remove('d-none');
            categoryCanvas.style.visibility = 'hidden';
        } else {
            categoryEmpty.classList.add('d-none');
            categoryCanvas.style.visibility = 'visible';
            if (categoryChart) categoryChart.destroy();
            categoryChart = new Chart(categoryCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: catLabels,
                    datasets: [{
                        label: 'Actions',
                        data: catCounts,
                        backgroundColor: catLabels.map(function (l) {
                            return l === 'Clearance' ? '#3b82f6' : (l === 'Leave' ? '#06b6d4' : '#f59e0b');
                        }),
                        borderRadius: 8, borderSkipped: false, maxBarThickness: 64
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: c.text, font: { size: 11 } },
                             grid: { color: c.grid } }
                    }
                }
            });
        }
    }

    /* ============================================================
       TOP APPROVERS — top 8 ranked list (no pagination)
       ============================================================ */
    function renderApprovers() {
        const counts = {};
        filteredRows.forEach(function (r) {
            const key = r.actor_name || '';
            if (!key || key === '—') return;
            counts[key] = (counts[key] || 0) + 1;
        });

        const container = document.getElementById('approverBars');
        const emptyEl = document.getElementById('approverEmpty');
        const total = Object.values(counts).reduce(function (a, b) { return a + b; }, 0);

        if (total === 0) {
            container.innerHTML = '';
            emptyEl.classList.remove('d-none');
            return;
        }
        emptyEl.classList.add('d-none');

        const entries = Object.entries(counts)
            .sort(function (a, b) { return b[1] - a[1]; })
            .slice(0, TOP_APPROVERS_LIMIT);
        const max = entries[0][1];

        container.innerHTML = entries.map(function (pair, idx) {
            const label = pair[0], count = pair[1];
            const pct = total > 0 ? ((count / total) * 100).toFixed(1) : '0';
            const tone = ['success', 'info', 'primary', 'warning'][idx % 4];
            const width = Math.max(4, (count / max) * 100);
            return `
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold small text-truncate" style="max-width:65%;" title="${escapeHtml(label)}">${escapeHtml(label)}</span>
                        <span class="fw-bold small">${count.toLocaleString()} <span class="text-body-secondary fw-normal">(${pct}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-${tone}" style="width:${width}%;"></div>
                    </div>
                </div>`;
        }).join('');
    }

    /* ============================================================
       TABLE — 10 rows per page
       ============================================================ */
    function statusBadge(status) {
        const s = normalizeStatus(status);
        if (s === 'Approved')  return 'text-bg-success';
        if (s === 'Pending')   return 'text-bg-warning';
        if (s === 'Rejected')  return 'text-bg-danger';
        if (s === 'Cancelled') return 'text-bg-secondary';
        return 'text-bg-secondary';
    }
    function categoryBadge(cat) {
        if (cat === 'Clearance') return 'text-bg-primary';
        if (cat === 'Leave')     return 'text-bg-info';
        return 'text-bg-secondary';
    }

    function renderTable() {
        const tbody = document.getElementById('recordsBody');
        const total = filteredRows.length;

        if (total === 0) {
            const noFilter = !currentFilters.sy && !currentFilters.month && !currentFilters.week;
            if (TABLE_EMPTY || noFilter) {
                tbody.innerHTML = `
                    <tr><td colspan="7" class="text-center py-5">
                        <div class="d-inline-flex flex-column align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:72px;height:72px;background:rgba(100,116,139,0.10);color:#64748b;font-size:1.9rem;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No approval history yet</h6>
                            <p class="text-body-secondary small mb-0" style="max-width:360px;">
                                This page will populate automatically once clearance or leave approvals are processed.
                            </p>
                        </div>
                    </td></tr>`;
            } else {
                tbody.innerHTML = `
                    <tr><td colspan="7" class="text-center py-5">
                        <div class="d-inline-flex flex-column align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:72px;height:72px;background:rgba(100,116,139,0.10);color:#64748b;font-size:1.9rem;">
                                <i class="fas fa-filter-circle-xmark"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No records match your filters</h6>
                            <p class="text-body-secondary small mb-3" style="max-width:360px;">
                                Try adjusting or clearing your filters.
                            </p>
                            <button class="btn btn-outline-secondary btn-sm" id="emptyResetBtn">
                                <i class="fas fa-rotate-left me-1"></i> Clear all filters
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
                '<td class="appr-history-identity">' +
                    '<div class="fw-semibold">' + escapeHtml(fullName(r)) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.dept || '—') + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell"><span class="badge ' + categoryBadge(r.category) + '">' + escapeHtml(r.category) + '</span></td>' +
                '<td>' + escapeHtml(r.subject || '—') + '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary">' + escapeHtml(r.actor_name || '—') + '</td>' +
                '<td><span class="badge ' + statusBadge(r.status) + '">' + escapeHtml(normalizeStatus(r.status)) + '</span></td>' +
                '<td class="d-none d-sm-table-cell"><code>' + escapeHtml(r.ref_no || '—') + '</code></td>' +
                '<td class="d-none d-lg-table-cell text-nowrap">' + fmtDate(r.acted_at) + '</td>' +
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
            'Showing ' + start + '–' + end + ' of ' + total + ' actions';

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
                document.querySelector('.appr-history-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    /* ---- Print-only table ---- */
    function renderPrintTable() {
        const host = document.getElementById('printTable');
        if (!host) return;
        if (filteredRows.length === 0) { host.innerHTML = ''; return; }

        const rows = filteredRows.map(function (r) {
            return '<tr>' +
                '<td class="blur">' + escapeHtml(fullName(r)) + '</td>' +
                '<td>' + escapeHtml(r.category) + '</td>' +
                '<td>' + escapeHtml(r.subject || '—') + '</td>' +
                '<td>' + escapeHtml(r.actor_name || '—') + '</td>' +
                '<td>' + escapeHtml(normalizeStatus(r.status)) + '</td>' +
                '<td>' + escapeHtml(r.ref_no || '—') + '</td>' +
                '<td>' + fmtDate(r.acted_at) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Category</th><th>Subject</th>
                        <th>Approver</th><th>Status</th><th>Ref No.</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv() {
        const headers = ['Faculty', 'Department', 'Category', 'Subject', 'Approver', 'Role', 'Status', 'Ref No', 'Action Date', 'Remarks'];
        const rows = filteredRows.map(function (r) {
            return [
                fullName(r), r.dept, r.category, r.subject, r.actor_name, r.actor_role,
                normalizeStatus(r.status), r.ref_no, r.acted_at, r.remarks
            ];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(function (e) {
            csv += e.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'approval-history_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       BIND / INIT
       ============================================================ */
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
        const KEY = 'smsApprovalHistoryPrivacyMode';
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