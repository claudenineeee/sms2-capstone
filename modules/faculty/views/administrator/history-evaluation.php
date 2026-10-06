<?php
/**
 * SMS 2 - Faculty Admin - Evaluation History
 * Filters: 3 dropdowns (Year / Month / Week) + Reset — clean layout
 * Client-side filtering (no page reload)
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
   LOAD ALL ROWS ONCE — client filters them instantly
   ============================================================ */
$allRows = [];
$loadError = null;
$rawTableEmpty = true;

try {
    if (!$pdo instanceof PDO) { throw new RuntimeException('Database unavailable.'); }

    $rawCount = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.evaluations")->fetchColumn();
    $rawTableEmpty = ($rawCount === 0);

    if (!$rawTableEmpty) {
        $stmt = $pdo->query("
            SELECT
                e.evaluation_id,
                e.faculty_id,
                e.term_id,
                e.source_type,
                e.composite_score,
                e.rating_label,
                e.eval_count,
                e.response_rate,
                e.submitted_at,
                fp.first_name,
                fp.last_name,
                fp.faculty_id AS faculty_no,
                fp.designated_department
            FROM faculty_db.evaluations e
            LEFT JOIN faculty_db.faculty f       ON f.faculty_id  = e.faculty_id
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = f.faculty_no
            ORDER BY e.submitted_at DESC
        ");
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Eval history load failed: ' . $e->getMessage());
    $loadError = 'Some data could not be loaded. Try again later.';
}

/* ============================================================
   FILTER DROPDOWN OPTIONS — derived from the loaded rows
   ============================================================ */
$schoolYears = [];
foreach ($allRows as $r) {
    $ts = strtotime((string) $r['submitted_at']);
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
$pageTitle    = 'Evaluation History';
$activeModule = 'faculty';
$activePage   = 'evaluation-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'History', 'url' => null],
    ['label' => 'Evaluation Records', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="evalHistoryPage">

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Evaluation History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Faculty identities are blurred for privacy.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Evaluation History</h1>
            <p class="text-body-secondary mb-0">Composite scores across student, peer, and department-head evaluations.</p>
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
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="printEvalBtn">
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
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Evals</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">All sources</small>
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
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Avg Score</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiAvg">0.00</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">Composite</small>
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Student</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiStudent">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Submissions</small>
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
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Peer + Head</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiPeerHead">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Combined</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters — clean 3-dropdown bar ================= -->
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
                        <i class="fas fa-rotate-left"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">

        <!-- Score Trend (full width) -->
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Score Trend</h5>
                            <p class="text-body-secondary small mb-0">Average composite score over time</p>
                        </div>
                        <span class="badge text-bg-light border" id="trendCountBadge">0 periods</span>
                    </div>
                    <div class="position-relative w-100" style="height:300px;">
                        <canvas id="evalTrendChart"></canvas>
                        <div class="eval-empty-state d-none" id="trendEmpty">
                            <div class="eval-empty-state__icon" style="background:rgba(59,130,246,0.10);color:#3b82f6;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No trend data yet</h6>
                            <p class="text-body-secondary small mb-0">Score trends will appear once evaluations come in.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Source Distribution -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Source Distribution</h5>
                            <p class="text-body-secondary small mb-0">Where evaluations come from</p>
                        </div>
                        <span class="badge text-bg-light border" id="sourceCountBadge">0 total</span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="evalSourceChart"></canvas>
                        <div class="eval-empty-state d-none" id="sourceEmpty">
                            <div class="eval-empty-state__icon" style="background:rgba(6,182,212,0.10);color:#06b6d4;">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No sources yet</h6>
                            <p class="text-body-secondary small mb-0">Student, peer, and head breakdown will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Averages -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Department Averages</h5>
                            <p class="text-body-secondary small mb-0">Score comparison across departments</p>
                        </div>
                        <span class="badge text-bg-light border" id="deptCountBadge">0 dept</span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="evalDeptChart"></canvas>
                        <div class="eval-empty-state d-none" id="deptEmpty">
                            <div class="eval-empty-state__icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                                <i class="fas fa-building"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No department data</h6>
                            <p class="text-body-secondary small mb-0">Department averages will appear once evaluations exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating Breakdown -->
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Rating Breakdown</h5>
                            <p class="text-body-secondary small mb-0">Count per rating tier</p>
                        </div>
                    </div>
                    <div id="ratingBreakdownBody">
                        <div class="eval-empty-state eval-empty-state--static" id="ratingEmpty">
                            <div class="eval-empty-state__icon" style="background:rgba(100,116,139,0.10);color:#64748b;">
                                <i class="fas fa-chart-simple"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No rating data yet</h6>
                            <p class="text-body-secondary small mb-0">Rating tier counts will appear here.</p>
                        </div>
                        <div class="d-flex flex-column gap-3" id="ratingBars"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Evaluation Records</h5>
                <p class="text-body-secondary small mb-0" id="recordsSubtitle">No results</p>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#0d6efd;"></span>Student
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#8b5cf6;"></span>Peer
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Dept Head
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0 eval-history-table">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Source</th>
                        <th class="text-uppercase small fw-bold text-body-secondary text-center">Score</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Rating</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell text-center">Responses</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell text-center">Rate</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Submitted</th>
                    </tr>
                </thead>
                <tbody id="recordsBody"></tbody>
            </table>
        </div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
            <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
            <nav aria-label="Evaluation pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
            </nav>
        </div>
    </div>
</div>

<style>
    /* ---------- Empty states ---------- */
    .eval-empty-state {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1rem;
        pointer-events: none;
    }
    .eval-empty-state--static {
        position: static;
        min-height: 200px;
    }
    .eval-empty-state__icon {
        width: 64px; height: 64px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        margin-bottom: 0.85rem;
    }

    /* ---------- Privacy blur ---------- */
    .privacy-mode .eval-history-identity {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .eval-history-identity:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* ---------- Table ---------- */
    .eval-history-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .eval-history-table tbody tr:hover td { background: var(--sms-dropdown-hover); }

    @media (max-width: 400px) {
        #evalHistoryPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #evalHistoryPage h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .eval-history-table tbody td,
        .eval-history-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        #evalHistoryPage .card-title { font-size: 0.9rem; }
    }
    @media (max-width: 360px) {
        .eval-history-table tbody td,
        .eval-history-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* ---------- Print ---------- */
    @media print {
        body { background: #fff !important; color: #000 !important; }
        .no-print, nav, header, footer, .sidebar, .sms-sidebar, .sms-navbar,
        .breadcrumb, .card-header, .card-footer, .btn, button, .modal { display: none !important; }
        #evalHistoryPage { padding: 0 !important; }
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

        .eval-history-table tbody td:nth-child(1) {
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

    /* ============================================================
       DATA
       ============================================================ */
    const EV_ROWS = <?= json_encode(array_map(function ($r) {
        return [
            'id'         => (int) $r['evaluation_id'],
            'faculty_id' => (int) $r['faculty_id'],
            'source'     => (string) $r['source_type'],
            'score'      => (float) $r['composite_score'],
            'rating'     => (string) ($r['rating_label'] ?? ''),
            'count'      => (int) $r['eval_count'],
            'rate'       => $r['response_rate'] !== null ? (float) $r['response_rate'] : null,
            'submitted'  => (string) $r['submitted_at'],
            'first_name' => (string) ($r['first_name'] ?? ''),
            'last_name'  => (string) ($r['last_name'] ?? ''),
            'faculty_no' => (string) ($r['faculty_no'] ?? ''),
            'dept'       => (string) ($r['designated_department'] ?? ''),
        ];
    }, $allRows), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 15;
    const TABLE_EMPTY = <?= $rawTableEmpty ? 'true' : 'false' ?>;

    /* ============================================================
       STATE
       ============================================================ */
    let currentFilters = { sy: '', month: '', week: '' };
    let filteredRows = EV_ROWS.slice();
    let currentPage = 1;

    let trendChart = null, sourceChart = null, deptChart = null;

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

    /* ============================================================
       FILTER
       ============================================================ */
    function applyFilters() {
        filteredRows = EV_ROWS.filter(function (r) {
            const d = parseDate(r.submitted);
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
        renderRatingBars();
        renderTable();
        renderPager();
    }

    function renderKPIs() {
        const total = filteredRows.length;
        let sum = 0, student = 0, peer = 0, head = 0;
        filteredRows.forEach(function (r) {
            sum += r.score;
            if (r.source === 'Student')  student++;
            if (r.source === 'Peer')     peer++;
            if (r.source === 'DeptHead') head++;
        });
        const avg = total > 0 ? (sum / total) : 0;

        document.getElementById('kpiTotal').textContent     = total.toLocaleString();
        document.getElementById('kpiAvg').textContent       = avg.toFixed(2);
        document.getElementById('kpiStudent').textContent   = student.toLocaleString();
        document.getElementById('kpiPeerHead').textContent  = (peer + head).toLocaleString();
    }

    function renderCharts() {
        if (typeof Chart === 'undefined') return;

        const themeIsDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.getAttribute('data-theme') === 'dark';
        const gridColor = themeIsDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.06)';
        const textColor = themeIsDark ? '#94a3b8' : '#64748b';

        /* ---- Score Trend ---- */
        const monthMap = {};
        filteredRows.forEach(function (r) {
            const d = parseDate(r.submitted);
            if (!d) return;
            const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            if (!monthMap[key]) monthMap[key] = { sum: 0, count: 0 };
            monthMap[key].sum += r.score;
            monthMap[key].count++;
        });
        const monthKeys = Object.keys(monthMap).sort();
        const trendLabels = monthKeys.map(function (k) {
            const [y, m] = k.split('-');
            return new Date(+y, +m - 1, 1).toLocaleString('default', { month: 'short', year: 'numeric' });
        });
        const trendScores = monthKeys.map(function (k) {
            return +(monthMap[k].sum / monthMap[k].count).toFixed(2);
        });

        const trendEmpty = document.getElementById('trendEmpty');
        const trendCanvas = document.getElementById('evalTrendChart');
        if (trendLabels.length === 0) {
            trendEmpty.classList.remove('d-none');
            trendCanvas.style.visibility = 'hidden';
            document.getElementById('trendCountBadge').textContent = '0 periods';
        } else {
            trendEmpty.classList.add('d-none');
            trendCanvas.style.visibility = 'visible';
            document.getElementById('trendCountBadge').textContent =
                trendLabels.length + ' period' + (trendLabels.length !== 1 ? 's' : '');

            if (trendChart) trendChart.destroy();
            trendChart = new Chart(trendCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Avg Score',
                        data: trendScores,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.12)',
                        tension: 0.35, fill: true,
                        pointRadius: 4, pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
                        y: { beginAtZero: true, suggestedMax: 5,
                             ticks: { color: textColor, font: { size: 11 } },
                             grid: { color: gridColor } }
                    }
                }
            });
        }

        /* ---- Source Distribution ---- */
        let sCount = 0, pCount = 0, hCount = 0;
        filteredRows.forEach(function (r) {
            if (r.source === 'Student')  sCount++;
            if (r.source === 'Peer')     pCount++;
            if (r.source === 'DeptHead') hCount++;
        });

        const sourceEmpty = document.getElementById('sourceEmpty');
        const sourceCanvas = document.getElementById('evalSourceChart');
        const sourceTotal = sCount + pCount + hCount;
        if (sourceTotal === 0) {
            sourceEmpty.classList.remove('d-none');
            sourceCanvas.style.visibility = 'hidden';
            document.getElementById('sourceCountBadge').textContent = '0 total';
        } else {
            sourceEmpty.classList.add('d-none');
            sourceCanvas.style.visibility = 'visible';
            document.getElementById('sourceCountBadge').textContent = sourceTotal + ' total';

            if (sourceChart) sourceChart.destroy();
            sourceChart = new Chart(sourceCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Student', 'Peer', 'Dept Head'],
                    datasets: [{
                        data: [sCount, pCount, hCount],
                        backgroundColor: ['#0d6efd', '#8b5cf6', '#f59e0b'],
                        borderWidth: 0, hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom',
                                  labels: { color: textColor, boxWidth: 10, font: { size: 11 },
                                            usePointStyle: true, pointStyle: 'circle', padding: 14 } }
                    }
                }
            });
        }

        /* ---- Department Averages ---- */
        const deptMap = {};
        filteredRows.forEach(function (r) {
            const key = r.dept || 'Unassigned';
            if (!deptMap[key]) deptMap[key] = { sum: 0, count: 0 };
            deptMap[key].sum += r.score;
            deptMap[key].count++;
        });
        const deptKeys = Object.keys(deptMap)
            .map(function (k) { return { key: k, avg: deptMap[k].sum / deptMap[k].count }; })
            .sort(function (a, b) { return b.avg - a.avg; })
            .slice(0, 8);
        const deptLabels = deptKeys.map(function (o) { return o.key; });
        const deptScores = deptKeys.map(function (o) { return +o.avg.toFixed(2); });

        const deptEmpty = document.getElementById('deptEmpty');
        const deptCanvas = document.getElementById('evalDeptChart');
        if (deptLabels.length === 0) {
            deptEmpty.classList.remove('d-none');
            deptCanvas.style.visibility = 'hidden';
            document.getElementById('deptCountBadge').textContent = '0 dept';
        } else {
            deptEmpty.classList.add('d-none');
            deptCanvas.style.visibility = 'visible';
            document.getElementById('deptCountBadge').textContent = deptLabels.length + ' dept';

            const palette = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];
            if (deptChart) deptChart.destroy();
            deptChart = new Chart(deptCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        label: 'Avg Score',
                        data: deptScores,
                        backgroundColor: palette,
                        borderRadius: 8, borderSkipped: false, maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
                        y: { beginAtZero: true, suggestedMax: 5,
                             grid: { color: gridColor },
                             ticks: { color: textColor, font: { size: 11 } } }
                    }
                }
            });
        }
    }

    function renderRatingBars() {
        const counts = {};
        filteredRows.forEach(function (r) {
            if (!r.rating) return;
            counts[r.rating] = (counts[r.rating] || 0) + 1;
        });
        const container = document.getElementById('ratingBars');
        const emptyEl = document.getElementById('ratingEmpty');
        const total = Object.values(counts).reduce(function (a, b) { return a + b; }, 0);

        if (total === 0) {
            container.innerHTML = '';
            emptyEl.classList.remove('d-none');
            return;
        }
        emptyEl.classList.add('d-none');

        const entries = Object.entries(counts).sort(function (a, b) { return b[1] - a[1]; });
        const max = entries[0][1];
        container.innerHTML = entries.map(function (pair) {
            const label = pair[0], count = pair[1];
            const pct = total > 0 ? ((count / total) * 100).toFixed(1) : '0';
            let tone = 'primary';
            const l = label.toLowerCase();
            if (l.indexOf('outstanding') !== -1)          tone = 'success';
            else if (l.indexOf('very satisfactory') !== -1) tone = 'info';
            else if (l.indexOf('satisfactory') !== -1)    tone = 'warning';
            else if (l.indexOf('needs') !== -1)           tone = 'danger';

            const width = Math.max(4, (count / max) * 100);
            return `
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold small">${escapeHtml(label)}</span>
                        <span class="fw-bold small">${count.toLocaleString()} <span class="text-body-secondary fw-normal">(${pct}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-${tone}" style="width:${width}%;"></div>
                    </div>
                </div>`;
        }).join('');
    }

    /* ---- Table ---- */
    function srcBadge(source) {
        if (source === 'Student')  return 'text-bg-primary';
        if (source === 'Peer')     return 'text-bg-info';
        if (source === 'DeptHead') return 'text-bg-warning';
        return 'text-bg-secondary';
    }
    function ratingBadge(label) {
        const l = (label || '').toLowerCase();
        if (l.indexOf('outstanding') !== -1)          return 'text-bg-success';
        if (l.indexOf('very satisfactory') !== -1)    return 'text-bg-info';
        if (l.indexOf('satisfactory') !== -1)         return 'text-bg-warning';
        if (l.indexOf('needs') !== -1)                return 'text-bg-danger';
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
                                <i class="fas fa-star-half-stroke"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-body-emphasis">No evaluations yet</h6>
                            <p class="text-body-secondary small mb-0" style="max-width:360px;">
                                This page will populate automatically once evaluations are submitted.
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
                '<td class="eval-history-identity">' +
                    '<div class="fw-semibold">' + escapeHtml(fullName(r)) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.dept || '—') + '</div>' +
                '</td>' +
                '<td><span class="badge ' + srcBadge(r.source) + '">' + escapeHtml(r.source || '—') + '</span></td>' +
                '<td class="text-center fw-bold">' + r.score.toFixed(2) + '</td>' +
                '<td><span class="badge ' + ratingBadge(r.rating) + '">' + escapeHtml(r.rating || '—') + '</span></td>' +
                '<td class="d-none d-sm-table-cell text-center">' + r.count + '</td>' +
                '<td class="d-none d-md-table-cell text-center">' + (r.rate !== null ? r.rate.toFixed(1) + '%' : '—') + '</td>' +
                '<td class="d-none d-lg-table-cell text-nowrap">' + fmtDate(r.submitted) + '</td>' +
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
                document.querySelector('.eval-history-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        document.getElementById('recordsSubtitle').textContent =
            'Showing ' + start + '–' + end + ' of ' + total + ' records';
    }

    /* ============================================================
       CSV EXPORT
       ============================================================ */
    function exportCsv() {
        const headers = ['Faculty', 'Faculty No', 'Department', 'Source', 'Score', 'Rating', 'Responses', 'Response Rate', 'Submitted'];
        const rows = filteredRows.map(function (r) {
            return [
                fullName(r),
                r.faculty_no,
                r.dept,
                r.source,
                r.score.toFixed(2),
                r.rating,
                r.count,
                r.rate !== null ? r.rate.toFixed(1) : '',
                r.submitted
            ];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        rows.forEach(function (e) {
            csv += e.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'evaluation-history_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       FILTER BINDING
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

    /* ============================================================
       INIT
       ============================================================ */
    function init() {
        bindFilters();
        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
        document.getElementById('printEvalBtn').addEventListener('click', function () { window.print(); });

        const privacyToggle = document.getElementById('privacyModeToggle');
        const KEY = 'smsEvalHistoryPrivacyMode';
        if (privacyToggle) {
            const apply = function (on) { document.body.classList.toggle('privacy-mode', on); };
            try {
                const saved = localStorage.getItem(KEY) === '1';
                privacyToggle.checked = saved;
                apply(saved);
            } catch (e) { /* ignore */ }
            privacyToggle.addEventListener('change', function () {
                apply(privacyToggle.checked);
                try { localStorage.setItem(KEY, privacyToggle.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

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