<?php
/**
 * SMS 2 - Faculty Admin - Leave Request History
 * Redesigned + hardened (array-safe $_GET casting, facultyDb() connection)
 * Print: Faculty + Ref No. columns are auto-blurred.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

/* ============================================================
   DATABASE — use facultyDb() (works with your project setup)
   ============================================================ */
$pdo = null;
if (function_exists('facultyDb')) {
    $pdo = facultyDb();
}
if (!$pdo instanceof PDO && function_exists('db')) {
    try { $pdo = db(); } catch (Throwable $e) { $pdo = null; }
}
if (!$pdo instanceof PDO && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
}

/* ============================================================
   SAFE INPUT CASTING — prevents "array - int" TypeError
   ============================================================ */
function smsSafeStr($v, string $default = ''): string {
    if (is_array($v)) return $default;
    if ($v === null) return $default;
    return trim((string) $v);
}
function smsSafeInt($v, int $default = 0): int {
    if (is_array($v)) return $default;
    if ($v === null || $v === '') return $default;
    return (int) $v;
}

$filterSchoolYear = smsSafeStr($_GET['sy'] ?? '');
$filterYear       = smsSafeStr($_GET['year'] ?? '');
$filterMonth      = smsSafeStr($_GET['month'] ?? '');
$filterWeek       = smsSafeStr($_GET['week'] ?? '');
$filterStatus     = smsSafeStr($_GET['status'] ?? '');
$filterLeaveType  = smsSafeStr($_GET['leave_type'] ?? '');
$filterDept       = smsSafeStr($_GET['dept'] ?? '');
$searchQuery      = smsSafeStr($_GET['q'] ?? '');
$page             = max(1, smsSafeInt($_GET['page'] ?? 1, 1));
$perPage          = 20;

/* ============================================================
   WHERE BUILDER
   ============================================================ */
function smsBuildLeaveHistoryWhere(array $f): array
{
    $where = [];
    $params = [];

    if ($f['sy'] !== '' && preg_match('/^(\d{4})-(\d{4})$/', $f['sy'], $m)) {
        $startY = (int) $m[1];
        $where[] = 'lr.start_date BETWEEN :sy_start AND :sy_end';
        $params['sy_start'] = $startY . '-06-01';
        $params['sy_end']   = ($startY + 1) . '-05-31';
    }
    if ($f['year'] !== '' && ctype_digit($f['year'])) {
        $where[] = 'YEAR(lr.start_date) = :yr';
        $params['yr'] = (int) $f['year'];
    }
    if ($f['month'] !== '' && ctype_digit($f['month']) && (int) $f['month'] >= 1 && (int) $f['month'] <= 12) {
        $where[] = 'MONTH(lr.start_date) = :mo';
        $params['mo'] = (int) $f['month'];
    }
    if ($f['week'] !== '' && ctype_digit($f['week']) && (int) $f['week'] >= 1 && (int) $f['week'] <= 5) {
        $where[] = 'CEIL(DAY(lr.start_date) / 7) = :wk';
        $params['wk'] = (int) $f['week'];
    }
    if ($f['status'] !== '') {
        $where[] = 'lr.approval_status = :st';
        $params['st'] = $f['status'];
    }
    if ($f['leave_type'] !== '') {
        $where[] = 'lr.leave_type = :lt';
        $params['lt'] = $f['leave_type'];
    }
    if ($f['dept'] !== '') {
        $where[] = 'fp.designated_department = :dp';
        $params['dp'] = $f['dept'];
    }
    if ($f['q'] !== '') {
        $where[] = '(fp.first_name LIKE :q1 OR fp.last_name LIKE :q2 OR lr.request_ref LIKE :q3)';
        $like = '%' . $f['q'] . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }

    return [$where, $params];
}

$filterInput = [
    'sy' => $filterSchoolYear, 'year' => $filterYear, 'month' => $filterMonth,
    'week' => $filterWeek, 'status' => $filterStatus, 'leave_type' => $filterLeaveType,
    'dept' => $filterDept, 'q' => $searchQuery,
];
[$whereParts, $whereParams] = smsBuildLeaveHistoryWhere($filterInput);
$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$baseFrom = "
    FROM faculty_db.leave_requests lr
    LEFT JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
    LEFT JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = f.faculty_no
    $whereSql
";

/* ============================================================
   CSV EXPORT
   ============================================================ */
if (($_GET['export'] ?? '') === 'csv' && !is_array($_GET['export'] ?? '')) {
    try {
        $exportStmt = $pdo->prepare("
            SELECT lr.request_ref, fp.first_name, fp.last_name, fp.faculty_id AS faculty_no,
                   fp.designated_department, lr.leave_type, lr.start_date, lr.end_date,
                   DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days,
                   lr.approval_status, lr.created_at
            $baseFrom
            ORDER BY lr.created_at DESC
        ");
        $exportStmt->execute($whereParams);
        $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leave-history_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Request Ref', 'Faculty Name', 'Faculty No', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Total Days', 'Status', 'Submitted On']);
        foreach ($exportRows as $r) {
            fputcsv($out, [
                $r['request_ref'],
                trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                $r['faculty_no'],
                $r['designated_department'],
                $r['leave_type'],
                $r['start_date'],
                $r['end_date'],
                $r['total_days'],
                $r['approval_status'],
                $r['created_at'],
            ]);
        }
        fclose($out);
    } catch (Throwable $e) {
        error_log('Leave history CSV export failed: ' . $e->getMessage());
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Export failed. Please adjust your filters and try again.';
    }
    exit;
}

/* ============================================================
   DATA FETCH — safe defaults
   ============================================================ */
$totalRequests = $totalApproved = $totalPending = $totalRejected = 0;
$approvalRate = 0.0;
$trendLabels = $trendApproved = $trendPending = $trendRejected = [];
$typeLabels = $typeCounts = [];
$deptLabelsH = $deptCountsH = [];
$leaveRows = [];
$totalFiltered = 0;
$totalPages = 1;
$schoolYearOptions = [];
$yearOptions = [];
$leaveTypeOptions = [];
$deptOptions = [];
$pageLoadError = null;

try {
    if (!$pdo instanceof PDO) { throw new RuntimeException('Database connection unavailable.'); }

    /* ---- KPI summary ---- */
    $summaryStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN lr.approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN lr.approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN lr.approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
        $baseFrom
    ");
    $summaryStmt->execute($whereParams);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalRequests = (int) ($summary['total'] ?? 0);
    $totalApproved = (int) ($summary['approved'] ?? 0);
    $totalPending  = (int) ($summary['pending'] ?? 0);
    $totalRejected = (int) ($summary['rejected'] ?? 0);
    $approvalRate  = $totalRequests > 0 ? round(($totalApproved / $totalRequests) * 100, 1) : 0.0;

    /* ---- Monthly trend ---- */
    $trendStmt = $pdo->prepare("
        SELECT DATE_FORMAT(lr.start_date, '%Y-%m') AS ym,
               SUM(CASE WHEN lr.approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
               SUM(CASE WHEN lr.approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
               SUM(CASE WHEN lr.approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
        $baseFrom
        GROUP BY ym
        ORDER BY ym ASC
    ");
    $trendStmt->execute($whereParams);
    foreach ($trendStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $trendLabels[]   = date('M Y', strtotime($r['ym'] . '-01'));
        $trendApproved[] = (int) $r['approved'];
        $trendPending[]  = (int) $r['pending'];
        $trendRejected[] = (int) $r['rejected'];
    }
    if (empty($trendLabels)) {
        $trendLabels = ['No data']; $trendApproved = $trendPending = $trendRejected = [0];
    }

    /* ---- Leave type breakdown ---- */
    $typeStmt = $pdo->prepare("
        SELECT COALESCE(NULLIF(lr.leave_type, ''), 'Unspecified') AS lt, COUNT(*) AS cnt
        $baseFrom
        GROUP BY lt
        ORDER BY cnt DESC
    ");
    $typeStmt->execute($whereParams);
    $typeRowsFetched = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
    $typeLabels = array_column($typeRowsFetched, 'lt');
    $typeCounts = array_map('intval', array_column($typeRowsFetched, 'cnt'));
    if (empty($typeLabels)) { $typeLabels = ['No data']; $typeCounts = [0]; }

    /* ---- Department breakdown ---- */
    $deptStmtH = $pdo->prepare("
        SELECT COALESCE(NULLIF(fp.designated_department, ''), 'Unassigned') AS dp, COUNT(*) AS cnt
        $baseFrom
        GROUP BY dp
        ORDER BY cnt DESC
        LIMIT 8
    ");
    $deptStmtH->execute($whereParams);
    $deptRowsFetched = $deptStmtH->fetchAll(PDO::FETCH_ASSOC);
    $deptLabelsH = array_column($deptRowsFetched, 'dp');
    $deptCountsH = array_map('intval', array_column($deptRowsFetched, 'cnt'));
    if (empty($deptLabelsH)) { $deptLabelsH = ['No data']; $deptCountsH = [0]; }

    /* ---- Pagination + rows ---- */
    $countStmt = $pdo->prepare("SELECT COUNT(*) $baseFrom");
    $countStmt->execute($whereParams);
    $totalFiltered = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $listStmt = $pdo->prepare("
        SELECT lr.id, lr.request_ref, lr.leave_type, lr.start_date, lr.end_date,
               DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days,
               lr.approval_status, lr.created_at,
               fp.first_name, fp.last_name, fp.faculty_id AS faculty_no,
               fp.designated_department
        $baseFrom
        ORDER BY lr.created_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($whereParams as $k => $v) {
        $listStmt->bindValue($k, $v);
    }
    $listStmt->bindValue('lim', $perPage, PDO::PARAM_INT);
    $listStmt->bindValue('off', $offset, PDO::PARAM_INT);
    $listStmt->execute();
    $leaveRows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---- Filter options ---- */
    $syRangeRow = $pdo->query("SELECT MIN(start_date) AS min_d, MAX(start_date) AS max_d FROM faculty_db.leave_requests")->fetch(PDO::FETCH_ASSOC);

    $smsComputeSyStart = function (string $dateStr): int {
        $ts = strtotime($dateStr);
        $y = (int) date('Y', $ts);
        $m = (int) date('n', $ts);
        return $m >= 6 ? $y : $y - 1;
    };

    $currentSyStart = $smsComputeSyStart(date('Y-m-d'));
    $syMin = !empty($syRangeRow['min_d']) ? $smsComputeSyStart($syRangeRow['min_d']) : $currentSyStart;
    $syMax = !empty($syRangeRow['max_d']) ? $smsComputeSyStart($syRangeRow['max_d']) : $currentSyStart;
    $syMax = max($syMax, $currentSyStart);
    for ($y = $syMax; $y >= $syMin; $y--) {
        $schoolYearOptions[] = $y . '-' . ($y + 1);
    }

    $yearOptions = $pdo->query("SELECT DISTINCT YEAR(start_date) AS yr FROM faculty_db.leave_requests ORDER BY yr DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($yearOptions)) { $yearOptions = [(int) date('Y')]; }

    $leaveTypeOptions = $pdo->query("
        SELECT DISTINCT leave_type FROM faculty_db.leave_requests
        WHERE leave_type IS NOT NULL AND leave_type <> '' ORDER BY leave_type ASC
    ")->fetchAll(PDO::FETCH_COLUMN);

    $deptOptions = $pdo->query("
        SELECT DISTINCT designated_department FROM faculty_db.faculty_profiles
        WHERE designated_department IS NOT NULL AND designated_department <> '' ORDER BY designated_department ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('Leave history page data load failed: ' . $e->getMessage());
    $pageLoadError = 'Some data could not be loaded. Try adjusting or clearing your filters.';
}

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
];
$weekLabels = [1 => 'Week 1 (1–7)', 2 => 'Week 2 (8–14)', 3 => 'Week 3 (15–21)', 4 => 'Week 4 (22–28)', 5 => 'Week 5 (29–31)'];

function smsLeaveHistoryQs(array $overrides = []): string
{
    $current = [
        'sy' => smsSafeStr($_GET['sy'] ?? ''),
        'year' => smsSafeStr($_GET['year'] ?? ''),
        'month' => smsSafeStr($_GET['month'] ?? ''),
        'week' => smsSafeStr($_GET['week'] ?? ''),
        'status' => smsSafeStr($_GET['status'] ?? ''),
        'leave_type' => smsSafeStr($_GET['leave_type'] ?? ''),
        'dept' => smsSafeStr($_GET['dept'] ?? ''),
        'q' => smsSafeStr($_GET['q'] ?? ''),
        'page' => smsSafeStr($_GET['page'] ?? ''),
    ];
    $merged = array_merge($current, $overrides);
    $merged = array_filter($merged, static fn ($v) => $v !== '' && $v !== null && !is_array($v));
    return http_build_query($merged);
}

$activeFilterCount = count(array_filter(
    [$filterSchoolYear, $filterYear, $filterMonth, $filterWeek, $filterStatus, $filterLeaveType, $filterDept, $searchQuery],
    static fn ($v) => $v !== ''
));

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Leave Request History';
$activeModule = 'faculty';
$activePage   = 'leave-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'History', 'url' => null],
    ['label' => 'Leave Requests', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="leaveHistoryPage">

    <?php if ($pageLoadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($pageLoadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Leave Request History</h4>
        <small>Generated <?= date('F j, Y g:i A') ?><?= $activeFilterCount ? ' — ' . $activeFilterCount . ' filter(s) applied' : ' — all records' ?></small>
        <p class="small mb-0 mt-1"><em>Faculty identities are blurred for privacy.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Leave Request History</h1>
            <p class="text-body-secondary mb-0">Every leave request ever filed — approved, pending, and rejected.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy Mode</span>
            </label>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2"
               href="?<?= htmlspecialchars(smsLeaveHistoryQs(['export' => 'csv'])) ?>">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">Export CSV</span>
            </a>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="printLeaveHistoryBtn">
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
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;"><?= number_format($totalRequests) ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">All filtered</small>
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
                        <h4 class="mb-0 fw-bold" style="color:#10b981;"><?= number_format($totalApproved) ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;"><?= $approvalRate ?>% rate</small>
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
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;"><?= number_format($totalPending) ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Awaiting</small>
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
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;"><?= number_format($totalRejected) ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Declined</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="get" id="leaveHistoryFilterForm" class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sy">School Year</label>
                    <select class="form-select form-select-sm" id="f_sy" name="sy">
                        <option value="">All</option>
                        <?php foreach ($schoolYearOptions as $sy): ?>
                            <option value="<?= htmlspecialchars($sy) ?>" <?= $filterSchoolYear === $sy ? 'selected' : '' ?>>SY <?= htmlspecialchars($sy) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_year">Year</label>
                    <select class="form-select form-select-sm" id="f_year" name="year">
                        <option value="">All</option>
                        <?php foreach ($yearOptions as $yr): ?>
                            <option value="<?= (int) $yr ?>" <?= (string) $filterYear === (string) $yr ? 'selected' : '' ?>><?= (int) $yr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_month">Month</label>
                    <select class="form-select form-select-sm" id="f_month" name="month">
                        <option value="">All</option>
                        <?php foreach ($monthNames as $num => $name): ?>
                            <option value="<?= $num ?>" <?= (string) $filterMonth === (string) $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_week">Week</label>
                    <select class="form-select form-select-sm" id="f_week" name="week">
                        <option value="">All</option>
                        <?php foreach ($weekLabels as $num => $label): ?>
                            <option value="<?= $num ?>" <?= (string) $filterWeek === (string) $num ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" name="status">
                        <option value="">All</option>
                        <?php foreach (['Approved', 'Pending', 'Rejected'] as $st): ?>
                            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_type">Leave Type</label>
                    <select class="form-select form-select-sm" id="f_type" name="leave_type">
                        <option value="">All</option>
                        <?php foreach ($leaveTypeOptions as $lt): ?>
                            <option value="<?= htmlspecialchars($lt) ?>" <?= $filterLeaveType === $lt ? 'selected' : '' ?>><?= htmlspecialchars($lt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_dept">Department</label>
                    <select class="form-select form-select-sm" id="f_dept" name="dept">
                        <option value="">All</option>
                        <?php foreach ($deptOptions as $dp): ?>
                            <option value="<?= htmlspecialchars($dp) ?>" <?= $filterDept === $dp ? 'selected' : '' ?>><?= htmlspecialchars($dp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5 col-lg-4">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_q">Search</label>
                    <input type="text" class="form-control form-control-sm" id="f_q" name="q"
                           value="<?= htmlspecialchars($searchQuery) ?>"
                           placeholder="Faculty name or request ref…"
                           autocomplete="off">
                </div>
                <div class="col-6 col-md-2 col-lg-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-filter"></i><span>Apply</span>
                    </button>
                </div>
                <div class="col-6 col-md-2 col-lg-2">
                    <a href="?" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-rotate-left"></i><span>Clear</span>
                    </a>
                </div>
            </form>

            <?php if ($activeFilterCount > 0): ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php if ($filterSchoolYear !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['sy' => '', 'page' => ''])) ?>">SY <?= htmlspecialchars($filterSchoolYear) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterYear !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['year' => '', 'page' => ''])) ?>">Year <?= htmlspecialchars($filterYear) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterMonth !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['month' => '', 'page' => ''])) ?>"><?= htmlspecialchars($monthNames[(int) $filterMonth] ?? $filterMonth) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterWeek !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['week' => '', 'page' => ''])) ?>"><?= htmlspecialchars($weekLabels[(int) $filterWeek] ?? $filterWeek) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterStatus !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['status' => '', 'page' => ''])) ?>"><?= htmlspecialchars($filterStatus) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterLeaveType !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['leave_type' => '', 'page' => ''])) ?>"><?= htmlspecialchars($filterLeaveType) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($filterDept !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['dept' => '', 'page' => ''])) ?>"><?= htmlspecialchars($filterDept) ?> <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                    <?php if ($searchQuery !== ''): ?><a class="badge text-bg-primary text-decoration-none fw-semibold" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['q' => '', 'page' => ''])) ?>">"<?= htmlspecialchars($searchQuery) ?>" <i class="fas fa-xmark ms-1"></i></a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-line text-primary me-2"></i>Leave Trend</h5>
                        <span class="badge text-bg-light border"><?= count($trendLabels) ?> period<?= count($trendLabels) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="leaveHistoryTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-pie text-info me-2"></i>By Leave Type</h5>
                        <span class="badge text-bg-light border"><?= count(array_filter($typeCounts, fn($c) => $c > 0)) ?> type<?= count(array_filter($typeCounts, fn($c) => $c > 0)) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="leaveHistoryTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-building text-warning me-2"></i>By Department</h5>
                        <span class="badge text-bg-light border"><?= count(array_filter($deptCountsH, fn($c) => $c > 0)) ?> dept</span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="leaveHistoryDeptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Records</h5>
                <p class="text-body-secondary small mb-0">
                    <?php if ($totalFiltered > 0): ?>
                        Showing <?= number_format(((int) $page - 1) * (int) $perPage + 1) ?>–<?= number_format(min((int) $page * (int) $perPage, $totalFiltered)) ?> of <?= number_format($totalFiltered) ?>
                    <?php else: ?>
                        No results
                    <?php endif; ?>
                </p>
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
            <table class="table align-middle mb-0 leave-history-table">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Ref No.</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Leave Type</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Period</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell text-center">Days</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaveRows)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-body-secondary">
                                <i class="fas fa-inbox d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                                No leave requests match these filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveRows as $row): ?>
                            <?php
                            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: '—';
                            $statusVal = (string) ($row['approval_status'] ?? '');
                            $badgeClass = 'text-bg-warning';
                            if (strcasecmp($statusVal, 'Approved') === 0) { $badgeClass = 'text-bg-success'; }
                            elseif (strcasecmp($statusVal, 'Rejected') === 0) { $badgeClass = 'text-bg-danger'; }
                            ?>
                            <tr>
                                <td class="leave-history-identity">
                                    <div class="fw-semibold"><?= htmlspecialchars($fullName) ?></div>
                                    <div class="small text-body-secondary"><?= htmlspecialchars($row['designated_department'] ?? '—') ?></div>
                                </td>
                                <td class="d-none d-md-table-cell"><code><?= htmlspecialchars($row['request_ref'] ?? '—') ?></code></td>
                                <td><?= htmlspecialchars($row['leave_type'] ?? '—') ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars(date('M j, Y', strtotime((string) $row['start_date']))) ?> – <?= htmlspecialchars(date('M j, Y', strtotime((string) $row['end_date']))) ?></td>
                                <td class="d-none d-sm-table-cell text-center"><?= (int) ($row['total_days'] ?? 0) ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusVal ?: 'Pending') ?></span></td>
                                <td class="d-none d-lg-table-cell"><?= htmlspecialchars(date('M j, Y', strtotime((string) $row['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-transparent border-top no-print">
                <nav aria-label="Leave history pagination">
                    <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['page' => (string) max(1, (int) $page - 1)])) ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <?php
                        $windowStart = max(1, (int) $page - 2);
                        $windowEnd = min($totalPages, (int) $page + 2);
                        for ($p = $windowStart; $p <= $windowEnd; $p++):
                        ?>
                            <li class="page-item <?= $p === (int) $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['page' => (string) $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= htmlspecialchars(smsLeaveHistoryQs(['page' => (string) min($totalPages, (int) $page + 1)])) ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .privacy-mode .leave-history-identity {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .leave-history-identity:hover { filter: blur(0); -webkit-filter: blur(0); }

    .leave-history-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .leave-history-table tbody tr:hover td { background: var(--sms-dropdown-hover); }

    @media (max-width: 400px) {
        #leaveHistoryPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #leaveHistoryPage h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .leave-history-table tbody td,
        .leave-history-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
        #leaveHistoryPage .card-title { font-size: 0.9rem; }
    }
    @media (max-width: 360px) {
        .leave-history-table tbody td,
        .leave-history-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* ============================================================
       PRINT — auto-blur on identity columns
       ============================================================ */
    @media print {
        body { background: #fff !important; color: #000 !important; }
        .no-print, nav, header, footer, .sidebar, .sms-sidebar, .sms-navbar,
        .breadcrumb, .card-header, .card-footer, .btn, button, .modal { display: none !important; }
        #leaveHistoryPage { padding: 0 !important; }
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

        /* ─── Privacy blur ──────────────────────────────────────
           Faculty column (td 1) and Ref No. column (td 2)
           are automatically blurred on print for privacy.      */
        .leave-history-table tbody td:nth-child(1),
        .leave-history-table tbody td:nth-child(2) {
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

    /* ---------- Navbar autofill killer ---------- */
    (function fixNavbarAutofill() {
        var selectors = [
            'input[type="search"]', 'input[placeholder*="Search"]', 'input[placeholder*="search"]',
            'nav input[type="text"]', '.navbar input[type="text"]',
            '.sms-navbar input[type="text"]', '.sms-navbar input[type="search"]'
        ];
        var inputs = [];
        selectors.forEach(function (sel) {
            try {
                document.querySelectorAll(sel).forEach(function (el) {
                    if (inputs.indexOf(el) === -1) inputs.push(el);
                });
            } catch (e) { /* ignore */ }
        });
        function wipe() {
            inputs.forEach(function (input) {
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('autocorrect', 'off');
                input.setAttribute('autocapitalize', 'off');
                input.setAttribute('spellcheck', 'false');
                if (!input.name || ['q','search','s'].indexOf(input.name) !== -1) {
                    input.name = 'sms2_global_search_' + Date.now();
                }
                if (input.type === 'text') input.type = 'search';
                if (input.value && input.value.indexOf('@') !== -1) input.value = '';
            });
        }
        wipe();
        setTimeout(wipe, 300);
        setTimeout(wipe, 1000);
        inputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                if (this.value && this.value.indexOf('@') !== -1) this.value = '';
            });
        });
    })();

    /* ---------- Charts ---------- */
    var hasChart = (typeof Chart !== 'undefined');
    var themeIsDark = document.documentElement.getAttribute('data-theme') === 'dark'
        || document.body.getAttribute('data-theme') === 'dark';
    var gridColor = themeIsDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.06)';
    var textColor = themeIsDark ? '#94a3b8' : '#64748b';
    var palette   = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];

    if (hasChart) {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily || 'Inter, sans-serif';

        var trendCtx = document.getElementById('leaveHistoryTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trendLabels) ?>,
                    datasets: [
                        { label: 'Approved', data: <?= json_encode($trendApproved) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.10)', tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Pending',  data: <?= json_encode($trendPending) ?>,  borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Rejected', data: <?= json_encode($trendRejected) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)', tension: 0.35, fill: true, pointRadius: 4, pointHoverRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle', padding: 14 } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: textColor, font: { size: 10 } }, grid: { color: gridColor } }
                    }
                }
            });
        }

        var typeCtx = document.getElementById('leaveHistoryTypeChart');
        if (typeCtx) {
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($typeLabels) ?>,
                    datasets: [{ data: <?= json_encode($typeCounts) ?>, backgroundColor: palette, borderWidth: 0, hoverOffset: 6 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, font: { size: 10 }, usePointStyle: true, pointStyle: 'circle', padding: 10 } }
                    }
                }
            });
        }

        var deptCtx = document.getElementById('leaveHistoryDeptChart');
        if (deptCtx) {
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($deptLabelsH) ?>,
                    datasets: [{ data: <?= json_encode($deptCountsH) ?>, backgroundColor: palette.slice().reverse(), borderWidth: 0, hoverOffset: 6 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, font: { size: 10 }, usePointStyle: true, pointStyle: 'circle', padding: 10 } }
                    }
                }
            });
        }
    }

    /* ---------- Auto-submit filter dropdowns ---------- */
    var filterForm = document.getElementById('leaveHistoryFilterForm');
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { filterForm.submit(); });
        });
    }

    /* ---------- Privacy mode ---------- */
    var privacyToggle = document.getElementById('privacyModeToggle');
    var PRIVACY_KEY = 'smsLeaveHistoryPrivacyMode';
    if (privacyToggle) {
        var applyPrivacy = function (on) { document.body.classList.toggle('privacy-mode', on); };
        try {
            var saved = localStorage.getItem(PRIVACY_KEY) === '1';
            privacyToggle.checked = saved;
            applyPrivacy(saved);
        } catch (e) { /* ignore */ }
        privacyToggle.addEventListener('change', function () {
            applyPrivacy(privacyToggle.checked);
            try { localStorage.setItem(PRIVACY_KEY, privacyToggle.checked ? '1' : '0'); } catch (e) { /* ignore */ }
        });
    }

    /* ---------- Print ---------- */
    var printBtn = document.getElementById('printLeaveHistoryBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () { window.print(); });
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>