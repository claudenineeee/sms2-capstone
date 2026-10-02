<?php
/**
 * SMS 2 - Faculty Admin Dashboard
 * Pure Bootstrap — relies on theme.css for light/dark theming.
 * Charts use gradient fills + theme-aware palettes.
 * Leave trend supports 1W / 1M / 6M / 1Y range switching.
 * Responsive: KPI cards go full-width on phones, charts adapt.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$pdo = db();
$userName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Faculty Admin'));

/* ============================================================
 | 1. KPI METRICS
 * ============================================================ */
$totalFaculty = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles")->fetchColumn();

$pendingApprovals = (int) $pdo->query("
    SELECT COUNT(*) 
    FROM faculty_db.faculty_profiles fp
    JOIN sms2_db.users u ON fp.user_id = u.id
    WHERE u.status = 'pending_approval' OR fp.profile_status = 'Pending Approval'
")->fetchColumn();

$activeFaculty = (int) $pdo->query("
    SELECT COUNT(*) 
    FROM faculty_db.faculty_profiles 
    WHERE LOWER(employment_status) IN ('active','regular')
")->fetchColumn();

$inactiveFaculty = (int) $pdo->query("
    SELECT COUNT(DISTINCT fp.id)
    FROM faculty_db.faculty_profiles fp
    INNER JOIN sms2_db.users u ON u.id = fp.user_id
    WHERE LOWER(u.status) = 'inactive'
")->fetchColumn();

$onLeaveToday = (int) $pdo->query("
    SELECT COUNT(DISTINCT lr.faculty_id)
    FROM faculty_db.leave_requests lr
    WHERE lr.approval_status = 'Approved'
      AND CURDATE() BETWEEN lr.start_date AND lr.end_date
")->fetchColumn();

/* ============================================================
 | 2. CHART DATA — Leave Trend (1W / 1M / 6M / 1Y)
 * ============================================================ */
function buildLeaveTrend(PDO $pdo, string $range): array
{
    $now = new DateTime('today');
    $labels = []; $approvedMap = []; $pendingMap = []; $rejectedMap = [];

    switch ($range) {
        case '1w':
            $start = (clone $now)->modify('-6 days');
            $rows = $pdo->prepare("
                SELECT DATE(created_at) AS bucket,
                       SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                       SUM(CASE WHEN approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
                FROM faculty_db.leave_requests
                WHERE DATE(created_at) BETWEEN :start AND :end
                GROUP BY bucket ORDER BY bucket
            ");
            $rows->execute([':start' => $start->format('Y-m-d'), ':end' => $now->format('Y-m-d')]);
            $data = [];
            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $data[$r['bucket']] = $r; }
            for ($i = 0; $i < 7; $i++) {
                $d = (clone $start)->modify("+{$i} days");
                $key = $d->format('Y-m-d');
                $labels[]      = $d->format('D d');
                $approvedMap[] = (int)($data[$key]['approved'] ?? 0);
                $pendingMap[]  = (int)($data[$key]['pending']  ?? 0);
                $rejectedMap[] = (int)($data[$key]['rejected'] ?? 0);
            }
            break;

        case '1m':
            $start = (clone $now)->modify('-29 days');
            $rows = $pdo->prepare("
                SELECT YEARWEEK(created_at, 1) AS bucket,
                       MIN(DATE(created_at)) AS week_start,
                       SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                       SUM(CASE WHEN approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
                FROM faculty_db.leave_requests
                WHERE DATE(created_at) BETWEEN :start AND :end
                GROUP BY bucket ORDER BY bucket
            ");
            $rows->execute([':start' => $start->format('Y-m-d'), ':end' => $now->format('Y-m-d')]);
            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $labels[]      = date('M d', strtotime($r['week_start']));
                $approvedMap[] = (int)$r['approved'];
                $pendingMap[]  = (int)$r['pending'];
                $rejectedMap[] = (int)$r['rejected'];
            }
            if (empty($labels)) { $labels = ['No data']; $approvedMap = $pendingMap = $rejectedMap = [0]; }
            break;

        case '1y':
            $start = (clone $now)->modify('-11 months')->modify('first day of this month');
            $rows = $pdo->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket,
                       SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                       SUM(CASE WHEN approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
                FROM faculty_db.leave_requests
                WHERE DATE(created_at) >= :start
                GROUP BY bucket ORDER BY bucket
            ");
            $rows->execute([':start' => $start->format('Y-m-d')]);
            $data = [];
            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $data[$r['bucket']] = $r; }
            for ($i = 0; $i < 12; $i++) {
                $d = (clone $start)->modify("+{$i} months");
                $key = $d->format('Y-m');
                $labels[]      = $d->format('M');
                $approvedMap[] = (int)($data[$key]['approved'] ?? 0);
                $pendingMap[]  = (int)($data[$key]['pending']  ?? 0);
                $rejectedMap[] = (int)($data[$key]['rejected'] ?? 0);
            }
            break;

        case '6m':
        default:
            $start = (clone $now)->modify('-5 months')->modify('first day of this month');
            $rows = $pdo->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket,
                       SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                       SUM(CASE WHEN approval_status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
                FROM faculty_db.leave_requests
                WHERE DATE(created_at) >= :start
                GROUP BY bucket ORDER BY bucket
            ");
            $rows->execute([':start' => $start->format('Y-m-d')]);
            $data = [];
            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $data[$r['bucket']] = $r; }
            for ($i = 0; $i < 6; $i++) {
                $d = (clone $start)->modify("+{$i} months");
                $key = $d->format('Y-m');
                $labels[]      = $d->format('M Y');
                $approvedMap[] = (int)($data[$key]['approved'] ?? 0);
                $pendingMap[]  = (int)($data[$key]['pending']  ?? 0);
                $rejectedMap[] = (int)($data[$key]['rejected'] ?? 0);
            }
            break;
    }
    return ['labels' => $labels, 'approved' => $approvedMap, 'pending' => $pendingMap, 'rejected' => $rejectedMap];
}

$range = strtolower((string)($_GET['range'] ?? '6m'));
if (!in_array($range, ['1w', '1m', '6m', '1y'], true)) $range = '6m';

$trend = buildLeaveTrend($pdo, $range);
$leaveTrendLabels   = $trend['labels'];
$leaveTrendApproved = $trend['approved'];
$leaveTrendPending  = $trend['pending'];
$leaveTrendRejected = $trend['rejected'];

$totalLeaveApproved = array_sum($leaveTrendApproved);
$totalLeavePending  = array_sum($leaveTrendPending);
$totalLeaveRejected = array_sum($leaveTrendRejected);

$rangeLabels = ['1w' => 'Last 1 Week', '1m' => 'Last 1 Month', '6m' => 'Last 6 Months', '1y' => 'Last 1 Year'];

/* ============================================================
 | 3. OTHER CHART DATA
 * ============================================================ */
$regularCount  = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles WHERE LOWER(employment_status) IN ('regular','full-time')")->fetchColumn();
$partTimeCount = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles WHERE LOWER(employment_status) IN ('part-time','contract')")->fetchColumn();
$otherCount    = max(0, $totalFaculty - ($regularCount + $partTimeCount));

$deptRows = $pdo->query("
    SELECT designated_department AS dept, COUNT(*) AS count
    FROM faculty_db.faculty_profiles
    WHERE designated_department IS NOT NULL AND designated_department != ''
    GROUP BY designated_department
    ORDER BY count DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$deptLabels = array_column($deptRows, 'dept');
$deptCounts = array_column($deptRows, 'count');

/* ============================================================
 | 4. RECENT REQUEST RESULTS
 * ============================================================ */
$recentResults = $pdo->query("
    SELECT lr.id, lr.request_ref, lr.leave_type, lr.start_date, lr.end_date,
           lr.approval_status, lr.created_at,
           fp.first_name, fp.last_name, fp.faculty_id AS faculty_no,
           fp.designated_department, fp.position
    FROM faculty_db.leave_requests lr
    LEFT JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
    LEFT JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = f.faculty_no
    ORDER BY lr.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$resultApprovedCount = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.leave_requests WHERE approval_status = 'Approved'")->fetchColumn();
$resultPendingCount  = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.leave_requests WHERE approval_status = 'Pending'")->fetchColumn();
$resultRejectedCount = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.leave_requests WHERE approval_status = 'Rejected'")->fetchColumn();

/* ============================================================
 | 5. FACULTY PERFORMANCE OVERVIEW
 * ============================================================ */
$perfRows = $pdo->query("
    SELECT fp.first_name, fp.last_name, fp.designated_department, fp.position,
           f.faculty_id,
           COALESCE(AVG(e.composite_score), 0) AS composite
    FROM faculty_db.faculty f
    INNER JOIN faculty_db.faculty_profiles fp ON fp.faculty_id = f.faculty_no
    LEFT JOIN faculty_db.evaluations e ON e.faculty_id = f.faculty_id
    WHERE fp.position = 'Faculty Professor'
      AND (fp.profile_status IS NULL OR fp.profile_status <> 'Inactive')
    GROUP BY f.faculty_id
    ORDER BY composite DESC, fp.last_name ASC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
 | Page config
 * ============================================================ */
$pageTitle    = 'Faculty Admin Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
    .stat-icon {
        width: 44px; height: 44px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; font-size: 1.05rem; flex-shrink: 0;
    }
    .min-w-0 { min-width: 0; }

    .chart-stage {
        position: relative; width: 100%; border-radius: 12px;
        padding: 0.65rem 0.5rem 0.35rem;
        background:
            radial-gradient(ellipse 70% 80% at 50% 0%, rgba(59, 130, 246, 0.06), transparent 60%),
            var(--sms-surface-muted);
        border: 1px solid var(--sms-border-soft);
        overflow: hidden;
    }
    .chart-stage--tall   { height: 200px; }
    .chart-stage--donut  { height: 155px; }
    .chart-stage--auto   { flex: 1 1 auto; min-height: 200px; padding: 0.55rem 0.5rem 0.35rem; }

    /* ============================================================
       KPI CARDS — template-matching design
       ============================================================ */
    .kpi-card {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 4px;
        z-index: 1;
    }
    .kpi-card--amber::before  { background: #f59e0b; }
    .kpi-card--green::before  { background: #10b981; }
    .kpi-card--red::before    { background: #ef4444; }
    .kpi-card--blue::before   { background: #3b82f6; }
    .kpi-card--slate::before  { background: #64748b; }

    .kpi-card--amber .kpi-icon { color: #f59e0b; }
    .kpi-card--green .kpi-icon { color: #10b981; }
    .kpi-card--red   .kpi-icon { color: #ef4444; }
    .kpi-card--blue  .kpi-icon { color: #3b82f6; }
    .kpi-card--slate .kpi-icon { color: #64748b; }

    .kpi-card--amber .kpi-value { color: #f59e0b; }
    .kpi-card--green .kpi-value { color: #10b981; }
    .kpi-card--red   .kpi-value { color: #ef4444; }
    .kpi-card--blue  .kpi-value { color: #3b82f6; }
    .kpi-card--slate .kpi-value { color: #64748b; }

    .kpi-card--amber .kpi-sub { color: #f59e0b; }
    .kpi-card--green .kpi-sub { color: #10b981; }
    .kpi-card--red   .kpi-sub { color: #ef4444; }
    .kpi-card--blue  .kpi-sub { color: #3b82f6; }
    .kpi-card--slate .kpi-sub { color: #64748b; }

    [data-theme="dark"] .kpi-card--amber .kpi-icon,
    [data-theme="dark"] .kpi-card--amber .kpi-value,
    [data-theme="dark"] .kpi-card--amber .kpi-sub { color: #fbbf24; }
    [data-theme="dark"] .kpi-card--green .kpi-icon,
    [data-theme="dark"] .kpi-card--green .kpi-value,
    [data-theme="dark"] .kpi-card--green .kpi-sub { color: #34d399; }
    [data-theme="dark"] .kpi-card--red .kpi-icon,
    [data-theme="dark"] .kpi-card--red .kpi-value,
    [data-theme="dark"] .kpi-card--red .kpi-sub { color: #f87171; }
    [data-theme="dark"] .kpi-card--blue .kpi-icon,
    [data-theme="dark"] .kpi-card--blue .kpi-value,
    [data-theme="dark"] .kpi-card--blue .kpi-sub { color: #60a5fa; }
    [data-theme="dark"] .kpi-card--slate .kpi-icon,
    [data-theme="dark"] .kpi-card--slate .kpi-value,
    [data-theme="dark"] .kpi-card--slate .kpi-sub { color: #94a3b8; }

    .kpi-card .kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin: 0;
    }
    .kpi-card .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        margin: 0;
    }
    .kpi-card .kpi-sub {
        font-size: 0.72rem;
        font-weight: 600;
    }
    .kpi-card .kpi-icon {
        font-size: 1.4rem;
        line-height: 1;
    }
    .kpi-card .kpi-link {
        position: absolute;
        top: 0.75rem; right: 0.75rem;
        width: 24px; height: 24px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px;
        font-size: 0.7rem;
        color: var(--sms-text-muted);
        border: 1px solid var(--sms-border);
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .kpi-card .kpi-link:hover {
        background: var(--sms-surface-muted);
        color: var(--sms-primary);
        border-color: var(--sms-primary);
    }

    /* ============================================================
       CLEANER BADGES
       ============================================================ */
    .badge-clean {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.32rem 0.7rem; border-radius: 999px;
        font-size: 0.72rem; font-weight: 600; line-height: 1;
        letter-spacing: 0.01em; border: 0; white-space: nowrap;
    }
    .badge-clean i { font-size: 0.68rem; }
    .badge-clean--approved { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .badge-clean--pending  { background: rgba(245, 158, 11, 0.14); color: #b45309; }
    .badge-clean--rejected { background: rgba(239, 68, 68, 0.12);  color: #dc2626; }
    .badge-clean--neutral  { background: var(--sms-surface-muted); color: var(--sms-text-muted); }
    [data-theme="dark"] .badge-clean--approved { background: rgba(52, 211, 153, 0.16); color: #6ee7b7; }
    [data-theme="dark"] .badge-clean--pending  { background: rgba(251, 191, 36, 0.16); color: #fcd34d; }
    [data-theme="dark"] .badge-clean--rejected { background: rgba(248, 113, 113, 0.16); color: #fca5a5; }
    [data-theme="dark"] .badge-clean--neutral  { background: rgba(255, 255, 255, 0.06); color: #94a3b8; }

    /* ============================================================
       VIEW ALL
       ============================================================ */
    .btn-view-all {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.28rem 0.65rem; font-size: 0.75rem; font-weight: 600;
        line-height: 1; border-radius: 8px;
        color: var(--sms-primary);
        background: rgba(59, 130, 246, 0.08);
        border: 1px solid transparent;
        text-decoration: none;
        transition: background 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
    }
    .btn-view-all:hover {
        background: rgba(59, 130, 246, 0.14);
        color: var(--sms-primary);
        border-color: rgba(59, 130, 246, 0.22);
        transform: translateX(2px);
    }
    .btn-view-all i { font-size: 0.68rem; transition: transform 0.15s ease; }
    .btn-view-all:hover i { transform: translateX(2px); }
    [data-theme="dark"] .btn-view-all { color: #93c5fd; background: rgba(96, 165, 250, 0.10); }
    [data-theme="dark"] .btn-view-all:hover { background: rgba(96, 165, 250, 0.18); border-color: rgba(96, 165, 250, 0.28); color: #bfdbfe; }

    /* ============================================================
       RANGE SWITCH
       ============================================================ */
    .range-switch {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.18rem; border-radius: 10px;
        background: var(--sms-surface-muted);
        border: 1px solid var(--sms-border-soft);
    }
    .range-switch a {
        display: inline-flex; align-items: center;
        padding: 0.28rem 0.6rem; font-size: 0.72rem; font-weight: 600;
        line-height: 1; border-radius: 8px; text-decoration: none;
        color: var(--sms-text-muted);
        transition: background 0.15s ease, color 0.15s ease;
        white-space: nowrap;
    }
    .range-switch a:hover { color: var(--sms-primary); background: rgba(59, 130, 246, 0.08); }
    .range-switch a.active {
        background: var(--sms-primary); color: #fff;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.28);
    }
    [data-theme="dark"] .range-switch a.active { background: #3b82f6; color: #ffffff; box-shadow: 0 2px 10px rgba(59, 130, 246, 0.35); }

    /* ============================================================
       QUICK NAVIGATION
       ============================================================ */
    .qn-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.75rem;
        border-radius: 12px;
        text-decoration: none;
        border: 1px solid var(--sms-border);
        background: var(--sms-surface-muted);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .qn-item:hover {
        transform: translateX(3px);
        border-color: var(--sms-primary);
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.10);
    }
    .qn-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.95rem; flex-shrink: 0;
        border: 1px solid transparent;
    }
    .qn-icon--amber { background: rgba(245, 158, 11, 0.15); color: #d97706; border-color: rgba(245, 158, 11, 0.30); }
    .qn-icon--blue  { background: rgba(59, 130, 246, 0.15); color: #2563eb; border-color: rgba(59, 130, 246, 0.30); }
    .qn-icon--green { background: rgba(16, 185, 129, 0.15); color: #059669; border-color: rgba(16, 185, 129, 0.30); }
    .qn-icon--violet{ background: rgba(139, 92, 246, 0.15); color: #7c3aed; border-color: rgba(139, 92, 246, 0.30); }
    [data-theme="dark"] .qn-icon--amber  { background: rgba(251, 191, 36, 0.22); color: #fcd34d; border-color: rgba(251, 191, 36, 0.40); }
    [data-theme="dark"] .qn-icon--blue   { background: rgba(96, 165, 250, 0.22); color: #93c5fd; border-color: rgba(96, 165, 250, 0.40); }
    [data-theme="dark"] .qn-icon--green  { background: rgba(52, 211, 153, 0.22); color: #6ee7b7; border-color: rgba(52, 211, 153, 0.40); }
    [data-theme="dark"] .qn-icon--violet { background: rgba(167, 139, 250, 0.22); color: #c4b5fd; border-color: rgba(167, 139, 250, 0.40); }

    .qn-title { font-size: 0.82rem; font-weight: 600; color: var(--sms-text-strong); line-height: 1.15; }
    .qn-sub   { font-size: 0.7rem;  color: var(--sms-text-muted); line-height: 1.15; }
    .qn-arrow { font-size: 0.72rem; color: var(--sms-text-faint); flex-shrink: 0; transition: transform 0.15s ease, color 0.15s ease; }
    .qn-item:hover .qn-arrow { color: var(--sms-primary); transform: translateX(2px); }

    .legend-dot {
        width: 9px; height: 9px; border-radius: 50%;
        display: inline-block; flex-shrink: 0;
    }

    /* ============================================================
       RESPONSIVE — mobile-first tweaks
       ============================================================ */

    /* <= 991px (tablet portrait & below): shrink chart stage heights */
    @media (max-width: 991.98px) {
        .chart-stage--tall   { height: 180px; }
        .chart-stage--donut  { height: 145px; }
        .chart-stage--auto   { min-height: 180px; }
    }

    /* <= 767px (phones): tighter spacing, smaller text, KPI full-width */
    @media (max-width: 767.98px) {
        .stat-icon { width: 36px; height: 36px; font-size: 0.9rem; border-radius: 10px; }

        /* KPI cards — single column, horizontal layout */
        .kpi-card .card-body {
            padding: 0.7rem 0.8rem 0.7rem 1rem !important;
            align-items: center !important;
        }
        .kpi-card .kpi-label { font-size: 0.68rem; }
        .kpi-card .kpi-value { font-size: 1.35rem; }
        .kpi-card .kpi-sub   { font-size: 0.68rem; }
        .kpi-card .kpi-icon  { font-size: 1.25rem; margin-right: 0.75rem !important; }
        .kpi-card .kpi-link  { top: 0.55rem; right: 0.55rem; width: 22px; height: 22px; font-size: 0.62rem; }

        /* Chart stages */
        .chart-stage { padding: 0.5rem 0.35rem 0.25rem; }
        .chart-stage--tall   { height: 180px; }
        .chart-stage--donut  { height: 150px; }
        .chart-stage--auto   { min-height: 180px; }

        /* Card headers wrap better */
        .card-header { padding: 0.6rem 0.85rem !important; }
        .card-header h6 { font-size: 0.82rem; }

        /* Range switch — smaller pills, tighter */
        .range-switch { padding: 0.15rem; gap: 0.15rem; }
        .range-switch a { padding: 0.24rem 0.5rem; font-size: 0.68rem; }

        /* Recent results rows — tighter */
        .p-2.p-md-3 { padding: 0.55rem 0.65rem !important; }

        /* Quick nav items — tight */
        .qn-item { padding: 0.55rem 0.65rem; gap: 0.6rem; }
        .qn-icon { width: 34px; height: 34px; font-size: 0.85rem; }
        .qn-title { font-size: 0.78rem; }
        .qn-sub   { font-size: 0.66rem; }
    }

    /* <= 575px (small phones): even tighter */
    @media (max-width: 575.98px) {
        /* KPI cards still one per row but more compact */
        .kpi-card .card-body {
            padding: 0.6rem 0.7rem 0.6rem 0.85rem !important;
        }
        .kpi-card .kpi-label { font-size: 0.64rem; }
        .kpi-card .kpi-value { font-size: 1.25rem; }
        .kpi-card .kpi-sub   { font-size: 0.62rem; }
        .kpi-card .kpi-icon  { font-size: 1.15rem; margin-right: 0.65rem !important; }

        /* Chart stage heights shrink further */
        .chart-stage--tall   { height: 165px; }
        .chart-stage--donut  { height: 140px; }
        .chart-stage--auto   { min-height: 165px; }

        /* Legend row below charts — tighter */
        .row.text-center.mt-2 > div { padding-left: 0.25rem; padding-right: 0.25rem; }
        .row.text-center.mt-2 small { font-size: 0.6rem; }
        .row.text-center.mt-2 strong { font-size: 0.85rem; }
    }

    /* Extra small (< 400px): stack range switch below title */
    @media (max-width: 400px) {
        .range-switch a { padding: 0.2rem 0.4rem; font-size: 0.62rem; }
        .kpi-card .kpi-label { letter-spacing: 0.02em; }
    }
</style>

<div class="container-fluid py-3 px-2 px-md-3">

    <!-- ============================================================
         KPI CARDS — full-width on phones, 5-up on desktop
         ============================================================ -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">

        <!-- Total Faculty — Blue -->
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <section class="card kpi-card kpi-card--blue h-100 shadow-sm">
                <div class="card-body d-flex align-items-center ps-4 p-3">
                    <div class="kpi-icon me-3"><i class="fas fa-users"></i></div>
                    <div class="min-w-0">
                        <h6 class="kpi-label text-body-secondary">Total Faculty</h6>
                        <h4 class="kpi-value"><?= number_format($totalFaculty) ?></h4>
                        <small class="kpi-sub">Registered personnel</small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-profile.php" class="kpi-link" title="View Faculty">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Active Faculty — Green -->
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <section class="card kpi-card kpi-card--green h-100 shadow-sm">
                <div class="card-body d-flex align-items-center ps-4 p-3">
                    <div class="kpi-icon me-3"><i class="fas fa-user-check"></i></div>
                    <div class="min-w-0">
                        <h6 class="kpi-label text-body-secondary">Active Faculty</h6>
                        <h4 class="kpi-value"><?= number_format($activeFaculty) ?></h4>
                        <small class="kpi-sub">
                            <?= $totalFaculty > 0 ? round(($activeFaculty / $totalFaculty) * 100, 1) : 0 ?>% active rate
                        </small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-profile.php" class="kpi-link" title="View Active">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Pending Approvals — Amber -->
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <section class="card kpi-card kpi-card--amber h-100 shadow-sm">
                <div class="card-body d-flex align-items-center ps-4 p-3">
                    <div class="kpi-icon me-3"><i class="fas fa-hourglass-half"></i></div>
                    <div class="min-w-0">
                        <h6 class="kpi-label text-body-secondary">Pending Approvals</h6>
                        <h4 class="kpi-value"><?= number_format($pendingApprovals) ?></h4>
                        <small class="kpi-sub"><?= $pendingApprovals ?> awaiting action</small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" class="kpi-link" title="View Pending">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- On Leave Today — Slate -->
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <section class="card kpi-card kpi-card--slate h-100 shadow-sm">
                <div class="card-body d-flex align-items-center ps-4 p-3">
                    <div class="kpi-icon me-3"><i class="fas fa-calendar-day"></i></div>
                    <div class="min-w-0">
                        <h6 class="kpi-label text-body-secondary">On Leave Today</h6>
                        <h4 class="kpi-value"><?= number_format($onLeaveToday) ?></h4>
                        <small class="kpi-sub"><?= $onLeaveToday > 0 ? 'Faculty on leave' : 'None today' ?></small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/leave-application-approval.php" class="kpi-link" title="View Leaves">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Inactive Faculty — Red -->
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <section class="card kpi-card kpi-card--red h-100 shadow-sm">
                <div class="card-body d-flex align-items-center ps-4 p-3">
                    <div class="kpi-icon me-3"><i class="fas fa-user-slash"></i></div>
                    <div class="min-w-0">
                        <h6 class="kpi-label text-body-secondary">Inactive Faculty</h6>
                        <h4 class="kpi-value"><?= number_format($inactiveFaculty) ?></h4>
                        <small class="kpi-sub"><?= $inactiveFaculty > 0 ? 'Blocked from login' : 'None at the moment' ?></small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-profile.php?view=inactive" class="kpi-link" title="View Inactive">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
    </div>

    <!-- ANALYTICS -->
    <div class="row g-2 g-md-3 g-lg-4 mb-3 mb-md-4">
        <div class="col-12 col-lg-5">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary me-2"></i>Overall Leave Request Trend</h6>
                    <div class="range-switch" role="tablist" aria-label="Leave trend range">
                        <a href="?range=1w" class="<?= $range === '1w' ? 'active' : '' ?>" role="tab">1W</a>
                        <a href="?range=1m" class="<?= $range === '1m' ? 'active' : '' ?>" role="tab">1M</a>
                        <a href="?range=6m" class="<?= $range === '6m' ? 'active' : '' ?>" role="tab">6M</a>
                        <a href="?range=1y" class="<?= $range === '1y' ? 'active' : '' ?>" role="tab">1Y</a>
                    </div>
                </div>
                <div class="card-body p-2 p-md-3 d-flex flex-column flex-grow-1">
                    <div class="chart-stage chart-stage--tall">
                        <canvas id="leaveTrendChart"></canvas>
                    </div>
                    <div class="row text-center mt-2 mt-md-3 pt-2 border-top g-0">
                        <div class="col-4">
                            <span class="legend-dot" style="background:#10b981;"></span>
                            <small class="text-body-secondary d-block">Approved</small>
                            <strong><?= $totalLeaveApproved ?></strong>
                        </div>
                        <div class="col-4">
                            <span class="legend-dot" style="background:#f59e0b;"></span>
                            <small class="text-body-secondary d-block">Pending</small>
                            <strong><?= $totalLeavePending ?></strong>
                        </div>
                        <div class="col-4">
                            <span class="legend-dot" style="background:#ef4444;"></span>
                            <small class="text-body-secondary d-block">Rejected</small>
                            <strong><?= $totalLeaveRejected ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-info me-2"></i>Employment Breakdown</h6>
                </div>
                <div class="card-body p-2 p-md-3 d-flex flex-column flex-grow-1">
                    <div class="chart-stage chart-stage--donut">
                        <canvas id="employmentChart"></canvas>
                    </div>
                    <div class="small mt-2 mt-md-3 pt-2 border-top">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-body-secondary d-flex align-items-center gap-2">
                                <span class="legend-dot" style="background:#3b82f6;"></span>Regular
                            </span>
                            <strong><?= $regularCount ?> (<?= $totalFaculty > 0 ? round(($regularCount/$totalFaculty)*100,1) : 0 ?>%)</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-body-secondary d-flex align-items-center gap-2">
                                <span class="legend-dot" style="background:#06b6d4;"></span>Part-Time
                            </span>
                            <strong><?= $partTimeCount ?> (<?= $totalFaculty > 0 ? round(($partTimeCount/$totalFaculty)*100,1) : 0 ?>%)</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-body-secondary d-flex align-items-center gap-2">
                                <span class="legend-dot" style="background:#8b5cf6;"></span>Other
                            </span>
                            <strong><?= $otherCount ?> (<?= $totalFaculty > 0 ? round(($otherCount/$totalFaculty)*100,1) : 0 ?>%)</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-column text-primary me-2"></i>Faculty by Department</h6>
                    <span class="badge-clean badge-clean--neutral d-none d-sm-inline">All Departments</span>
                </div>
                <div class="card-body p-2 p-md-3 d-flex flex-column flex-grow-1">
                    <div class="chart-stage chart-stage--auto">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ROW -->
    <div class="row g-2 g-md-3 g-lg-4">

        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard-check text-primary me-2"></i>Recent Request Results</h6>
                        <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" class="btn-view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge-clean badge-clean--approved"><i class="fas fa-check"></i><?= $resultApprovedCount ?> Approved</span>
                        <span class="badge-clean badge-clean--pending"><i class="fas fa-clock"></i><?= $resultPendingCount ?> Pending</span>
                        <span class="badge-clean badge-clean--rejected"><i class="fas fa-times"></i><?= $resultRejectedCount ?> Rejected</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentResults)): ?>
                        <div class="text-center text-body-secondary py-4 small">
                            <i class="fas fa-inbox fs-3 d-block mb-2 opacity-50"></i>
                            No recent requests to display.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentResults as $r):
                            $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            if ($fullName === '') $fullName = 'Unknown Faculty';
                            $initial  = strtoupper(substr($fullName, 0, 1));
                            $status   = $r['approval_status'] ?? 'Pending';
                            $badge    = match (strtolower($status)) {
                                'approved' => 'badge-clean--approved',
                                'rejected' => 'badge-clean--rejected',
                                default    => 'badge-clean--pending',
                            };
                            $icon     = match (strtolower($status)) {
                                'approved' => 'fa-check',
                                'rejected' => 'fa-times',
                                default    => 'fa-clock',
                            };
                            $dateStr  = !empty($r['created_at']) ? date('M d', strtotime($r['created_at'])) : '—';
                        ?>
                        <div class="p-2 p-md-3 d-flex align-items-center gap-2 gap-md-3 border-bottom">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width:34px;height:34px;font-size:0.75rem;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?= htmlspecialchars($fullName) ?></div>
                                <div class="small text-body-secondary text-truncate">
                                    <?= htmlspecialchars($r['leave_type'] ?? 'Leave') ?> &bull; <?= htmlspecialchars($r['designated_department'] ?? '—') ?>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge-clean <?= $badge ?>"><i class="fas <?= $icon ?>"></i><?= htmlspecialchars($status) ?></span>
                                <div class="small text-body-secondary mt-1"><?= $dateStr ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-success me-2"></i>Faculty Performance Overview</h6>
                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-performance.php" class="btn-view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-2 p-md-3 d-flex flex-column gap-2 gap-md-3">
                    <?php if (empty($perfRows)): ?>
                        <div class="text-center text-body-secondary py-3 small">
                            <i class="fas fa-user-slash fs-3 d-block mb-2 opacity-50"></i>
                            No Faculty Professors found.
                        </div>
                    <?php else: ?>
                        <?php foreach ($perfRows as $p):
                            $name = trim($p['first_name'] . ' ' . $p['last_name']);
                            $initial = strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1));
                            $score = (float) $p['composite'];
                            $pct = min(100, max(0, ($score / 5.0) * 100));
                            $tone = $score >= 4.5 ? 'success' : ($score >= 3.5 ? 'primary' : ($score >= 2.5 ? 'info' : 'warning'));
                        ?>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="stat-icon bg-<?= $tone ?> bg-opacity-10 text-<?= $tone ?>" style="width:30px;height:30px;font-size:0.7rem;">
                                    <?= htmlspecialchars($initial) ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate"><?= htmlspecialchars($name) ?></div>
                                    <div class="small text-body-secondary"><?= htmlspecialchars($p['designated_department'] ?? '—') ?></div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="fw-bold text-<?= $tone ?>"><?= number_format($score, 2) ?></span>
                                    <span class="small text-body-secondary">/ 5.00</span>
                                </div>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-<?= $tone ?>" role="progressbar" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- QUICK NAVIGATION -->
        <div class="col-12 col-xl-3">
            <div class="card h-100 shadow-sm d-flex flex-column">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-compass text-primary me-2"></i>Quick Navigation</h6>
                </div>
                <div class="card-body p-2 p-md-3 d-flex flex-column gap-2">

                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" class="qn-item">
                        <div class="qn-icon qn-icon--amber"><i class="fas fa-user-check"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="qn-title">Pending Approvals</div>
                            <div class="qn-sub text-truncate"><?= $pendingApprovals ?> requests awaiting action</div>
                        </div>
                        <i class="fas fa-chevron-right qn-arrow"></i>
                    </a>

                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-profile.php" class="qn-item">
                        <div class="qn-icon qn-icon--blue"><i class="fas fa-address-book"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="qn-title">Faculty Directory</div>
                            <div class="qn-sub text-truncate">Browse all department records</div>
                        </div>
                        <i class="fas fa-chevron-right qn-arrow"></i>
                    </a>

                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-performance.php" class="qn-item">
                        <div class="qn-icon qn-icon--green"><i class="fas fa-chart-line"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="qn-title">Evaluation Summary</div>
                            <div class="qn-sub text-truncate">Multi-source ratings</div>
                        </div>
                        <i class="fas fa-chevron-right qn-arrow"></i>
                    </a>

                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/leave-application-approval.php" class="qn-item">
                        <div class="qn-icon qn-icon--violet"><i class="fas fa-envelope-open-text"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="qn-title">Leave Applications</div>
                            <div class="qn-sub text-truncate">Review faculty leave requests</div>
                        </div>
                        <i class="fas fa-chevron-right qn-arrow"></i>
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     CHART INIT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    function detectTheme() {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme') || html.getAttribute('data-bs-theme');
        if (t === 'dark') return 'dark';
        if (t === 'light') return 'light';
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    let currentTheme = detectTheme();

    const palette = {
        dark: {
            text: '#cbd5e1', muted: '#94a3b8',
            grid: 'rgba(148, 163, 184, 0.10)', gridStrong: 'rgba(148, 163, 184, 0.20)',
            lineApproved: '#34d399', lineApprovedGlow: 'rgba(52, 211, 153, 0.28)',
            linePending:  '#fbbf24', linePendingGlow:  'rgba(251, 191, 36, 0.22)',
            lineRejected: '#f87171', lineRejectedGlow: 'rgba(248, 113, 113, 0.22)',
            barTop: '#93c5fd', barBottom: '#3b82f6', barBorder: '#60a5fa',
            doughnut: ['#60a5fa', '#22d3ee', '#a78bfa']
        },
        light: {
            text: '#334155', muted: '#64748b',
            grid: 'rgba(15, 33, 88, 0.06)', gridStrong: 'rgba(15, 33, 88, 0.10)',
            lineApproved: '#10b981', lineApprovedGlow: 'rgba(16, 185, 129, 0.20)',
            linePending:  '#f59e0b', linePendingGlow:  'rgba(245, 158, 11, 0.16)',
            lineRejected: '#ef4444', lineRejectedGlow: 'rgba(239, 68, 68, 0.16)',
            barTop: '#60a5fa', barBottom: '#2563eb', barBorder: '#3b82f6',
            doughnut: ['#3b82f6', '#06b6d4', '#8b5cf6']
        }
    };

    function makeVerticalGradient(ctx, area, topColor, bottomColor) {
        if (!area) return topColor;
        const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, topColor); g.addColorStop(1, bottomColor);
        return g;
    }

    function makeDonutGradients(ctx, colors) {
        const light = ['#93c5fd', '#67e8f9', '#c4b5fd'];
        return colors.map((c, i) => {
            const g = ctx.createRadialGradient(0, 0, 0, 0, 0, 220);
            g.addColorStop(0, light[i] || c); g.addColorStop(1, c);
            return g;
        });
    }

    function buildCharts() {
        const p = palette[currentTheme];
        Chart.defaults.color = p.text;
        Chart.defaults.font.family = 'Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.font.size = window.innerWidth < 768 ? 9 : 11;

        ['leaveTrendChart', 'employmentChart', 'deptChart'].forEach(id => {
            const c = document.getElementById(id);
            if (c && c.chart) c.chart.destroy();
        });

        const trendCtx = document.getElementById('leaveTrendChart');
        if (trendCtx) {
            const ctx = trendCtx.getContext('2d');
            trendCtx.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($leaveTrendLabels ?: ['No Data']) ?>,
                    datasets: [
                        { label: 'Approved', data: <?= json_encode($leaveTrendApproved ?: [0]) ?>,
                          borderColor: p.lineApproved, backgroundColor: p.lineApprovedGlow,
                          tension: 0.4, borderWidth: 2.5, pointRadius: 3.5,
                          pointBackgroundColor: p.lineApproved, pointBorderColor: '#fff',
                          pointBorderWidth: 1.5, pointHoverRadius: 5, fill: 'origin' },
                        { label: 'Pending', data: <?= json_encode($leaveTrendPending ?: [0]) ?>,
                          borderColor: p.linePending, backgroundColor: p.linePendingGlow,
                          tension: 0.4, borderWidth: 2.5, pointRadius: 3.5,
                          pointBackgroundColor: p.linePending, pointBorderColor: '#fff',
                          pointBorderWidth: 1.5, pointHoverRadius: 5, fill: false },
                        { label: 'Rejected', data: <?= json_encode($leaveTrendRejected ?: [0]) ?>,
                          borderColor: p.lineRejected, backgroundColor: p.lineRejectedGlow,
                          tension: 0.4, borderWidth: 2.5, pointRadius: 3.5,
                          pointBackgroundColor: p.lineRejected, pointBorderColor: '#fff',
                          pointBorderWidth: 1.5, pointHoverRadius: 5, fill: false }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { top: 8, right: 8, bottom: 0, left: 0 } },
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: currentTheme === 'dark' ? 'rgba(15,23,42,0.96)' : 'rgba(255,255,255,0.98)',
                            titleColor: p.text, bodyColor: p.text,
                            borderColor: p.gridStrong, borderWidth: 1,
                            padding: 10, cornerRadius: 8, displayColors: true, boxPadding: 4
                        }
                    },
                    scales: {
                        x: { grid: { color: p.grid, drawBorder: false }, ticks: { color: p.muted, maxRotation: 0, autoSkipPadding: 12 }, border: { display: false } },
                        y: { grid: { color: p.grid, drawBorder: false }, ticks: { color: p.muted, precision: 0 }, border: { display: false }, beginAtZero: true,
                             suggestedMax: Math.max(3, ...<?= json_encode($leaveTrendApproved ?: [0]) ?>, ...<?= json_encode($leaveTrendPending ?: [0]) ?>, ...<?= json_encode($leaveTrendRejected ?: [0]) ?>) + 1 }
                    }
                }
            });
        }

        const empCtx = document.getElementById('employmentChart');
        if (empCtx) {
            const ctx = empCtx.getContext('2d');
            const donutColors = makeDonutGradients(ctx, p.doughnut);
            empCtx.chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Regular', 'Part-Time', 'Other'],
                    datasets: [{
                        data: [<?= $regularCount ?>, <?= $partTimeCount ?>, <?= $otherCount ?>],
                        backgroundColor: donutColors,
                        borderColor: currentTheme === 'dark' ? 'rgba(15,23,42,0.9)' : '#fff',
                        borderWidth: 2, hoverOffset: 8,
                        hoverBorderColor: currentTheme === 'dark' ? '#60a5fa' : '#2563eb', hoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: 6 }, cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: currentTheme === 'dark' ? 'rgba(15,23,42,0.96)' : 'rgba(255,255,255,0.98)',
                            titleColor: p.text, bodyColor: p.text,
                            borderColor: p.gridStrong, borderWidth: 1, padding: 10, cornerRadius: 8
                        }
                    }
                }
            });
        }

        const deptCtx = document.getElementById('deptChart');
        if (deptCtx) {
            const ctx = deptCtx.getContext('2d');
            deptCtx.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($deptLabels ?: ['No Data']) ?>,
                    datasets: [{
                        label: 'Faculty Count',
                        data: <?= json_encode($deptCounts ?: [0]) ?>,
                        backgroundColor: (context) => {
                            const { ctx: c, chartArea } = context.chart;
                            if (!chartArea) return p.barTop;
                            return makeVerticalGradient(c, chartArea, p.barTop, p.barBottom);
                        },
                        borderColor: p.barBorder, borderWidth: 0,
                        borderRadius: { topLeft: 10, topRight: 10, bottomLeft: 4, bottomRight: 4 },
                        borderSkipped: false, maxBarThickness: 56,
                        hoverBackgroundColor: (context) => {
                            const { ctx: c, chartArea } = context.chart;
                            if (!chartArea) return p.barBorder;
                            return makeVerticalGradient(c, chartArea, p.barBorder, p.barBottom);
                        }
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { top: 6, right: 10, bottom: 0, left: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: currentTheme === 'dark' ? 'rgba(15,23,42,0.96)' : 'rgba(255,255,255,0.98)',
                            titleColor: p.text, bodyColor: p.text,
                            borderColor: p.gridStrong, borderWidth: 1,
                            padding: 10, cornerRadius: 8, displayColors: false,
                            callbacks: { label: (item) => `Faculty: ${item.formattedValue}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false, drawBorder: false }, ticks: { color: p.muted }, border: { display: false } },
                        y: { grid: { color: p.grid, drawBorder: false }, ticks: { color: p.muted, precision: 0, maxTicksLimit: 6 }, border: { display: false }, beginAtZero: true, grace: '10%' }
                    }
                }
            });
        }
    }

    buildCharts();

    const observer = new MutationObserver(function () {
        const newTheme = detectTheme();
        if (newTheme !== currentTheme) { currentTheme = newTheme; buildCharts(); }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-bs-theme'] });
    observer.observe(document.body, { attributes: true, attributeFilter: ['data-theme', 'data-bs-theme', 'class'] });

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(buildCharts, 200);
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>