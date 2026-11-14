<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$pageTitle    = 'Monitoring Officer Dashboard';
$activeModule = 'faculty';
$activePage   = 'monitoring-dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Monitoring Officer', 'url' => BASE_URL . '/modules/faculty/users/monitoring_officer/dashboard.php'],
    ['label' => 'Dashboard',          'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AttendanceModel.php';

// Use the safe facultyDb() function
$pdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$pdo) {
    die('Database connection failed.');
}

$today = date('Y-m-d');

// --- 1. KPI STATS ---
$stmtToday = $pdo->prepare("
    SELECT s.* 
    FROM class_attendance_sessions s 
    WHERE s.session_date = ? 
");
$stmtToday->execute([$today]);
$todaySessions = $stmtToday->fetchAll(PDO::FETCH_ASSOC);

$totalChecks    = count($todaySessions);
$presentFaculty = 0;
$absentFaculty  = 0;
foreach ($todaySessions as $s) {
    $st = strtolower(trim((string)($s['status'] ?? '')));
    if ($st === 'present' || $st === '' || $st === 'pending') $presentFaculty++;
    if ($st === 'absent') $absentFaculty++;
}
$presenceRate = $totalChecks > 0 ? round(($presentFaculty / $totalChecks) * 100) : 0;

// --- 2. CHART STATS ---
$chartData = ['present' => 0, 'late' => 0, 'absent' => 0];
$stmtChart = $pdo->query("SELECT status, COUNT(*) as cnt FROM class_attendance_sessions GROUP BY status");
while ($row = $stmtChart->fetch(PDO::FETCH_ASSOC)) {
    $st = strtolower(trim((string)$row['status']));
    if ($st === 'present' || $st === '' || $st === 'pending') $chartData['present'] += (int)$row['cnt'];
    elseif ($st === 'late') $chartData['late'] += (int)$row['cnt'];
    elseif ($st === 'absent') $chartData['absent'] += (int)$row['cnt'];
}

// --- 3. PAGINATION & RECENT LOGS ---
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalRowsStmt = $pdo->query("SELECT COUNT(*) FROM class_attendance_sessions");
$totalRows = (int)$totalRowsStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// FIXED: The JOIN uses OR to catch both integer and string ID variations
$stmtRecent = $pdo->prepare("
    SELECT s.*, f.first_name, f.last_name, r.room_code, sub.code AS subject_code 
    FROM class_attendance_sessions s 
    LEFT JOIN faculty_profiles f ON (s.faculty_id = f.id OR s.faculty_id = f.faculty_id)
    LEFT JOIN rooms r ON s.room_id = r.room_id
    LEFT JOIN subjects sub ON s.subject_id = sub.subject_id
    ORDER BY s.session_id DESC 
    LIMIT :limit OFFSET :offset
");
$stmtRecent->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmtRecent->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtRecent->execute();
$recentLogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* =========================================================
       DYNAMIC THEME ADAPTATION (LIGHT & DARK MODE)
       ========================================================= */
    :root {
        --dash-bg: #0d1527;
        --dash-card: #10192d;
        --dash-card-2: #16223b;
        --dash-border: #1e2d4a;
        --dash-text: #e2e8f0;
        --dash-text-strong: #ffffff;
        --dash-muted: #8492a6;
        --dash-input-bg: #111c35;
        --dash-hover: rgba(59, 130, 246, 0.08);
        --dash-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    [data-bs-theme="light"] .dash-wrap,
    [data-theme="light"] .dash-wrap,
    html.light .dash-wrap,
    body.light .dash-wrap {
        --dash-bg: #f8fafc !important;
        --dash-card: #ffffff !important;
        --dash-card-2: #f1f5f9 !important;
        --dash-border: #e2e8f0 !important;
        --dash-text: #334155 !important;
        --dash-text-strong: #0f172a !important;
        --dash-muted: #64748b !important;
        --dash-input-bg: #f8fafc !important;
        --dash-hover: rgba(59, 130, 246, 0.05) !important;
        --dash-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }

    [data-bs-theme="dark"] .dash-wrap,
    [data-theme="dark"] .dash-wrap,
    html.dark .dash-wrap,
    body.dark .dash-wrap {
        --dash-bg: #0d1527 !important;
        --dash-card: #10192d !important;
        --dash-card-2: #16223b !important;
        --dash-border: #1e2d4a !important;
        --dash-text: #e2e8f0 !important;
        --dash-text-strong: #ffffff !important;
        --dash-muted: #8492a6 !important;
        --dash-input-bg: #111c35 !important;
        --dash-hover: rgba(59, 130, 246, 0.08) !important;
        --dash-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    }

    .dash-wrap {
        padding: 1.5rem 1.25rem;
        background-color: transparent !important;
    }

    .dash-page-head {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
    }
    .dash-page-title {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1.5rem; font-weight: 700; margin: 0;
        color: var(--dash-text-strong) !important;
    }
    .dash-page-title i { color: #3b82f6 !important; }
    .dash-page-sub { color: var(--dash-muted) !important; font-size: .875rem; margin-top: .25rem; }
    .dash-page-sub strong { color: var(--dash-text-strong) !important; }
    .dash-meta { color: var(--dash-muted) !important; font-size: .8rem; margin-top: .35rem; }

    .dash-btn-outline {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        color: var(--dash-text-strong) !important;
        border-radius: .6rem; padding: .5rem 1rem;
        font-size: .85rem; font-weight: 500;
        display: inline-flex; align-items: center; gap: .45rem;
        transition: all .2s ease; text-decoration: none;
    }
    .dash-btn-outline:hover {
        background: var(--dash-card-2) !important;
        border-color: #3b82f6 !important;
        color: #3b82f6 !important;
    }

    .dash-kpi {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: .85rem; padding: 1rem 1.1rem;
        position: relative; display: flex; align-items: center; gap: .9rem;
        height: 100%; min-height: 100px; overflow: hidden;
        box-shadow: var(--dash-shadow) !important;
    }
    .dash-kpi::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; border-radius: .85rem 0 0 .85rem;
    }
    .dash-kpi.kpi-yellow::before { background: #eab308; }
    .dash-kpi.kpi-green::before  { background: #22c55e; }
    .dash-kpi.kpi-blue::before   { background: #3b82f6; }

    .dash-kpi-icon {
        width: 46px; height: 46px; border-radius: .65rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;
    }
    .dash-kpi.kpi-yellow .dash-kpi-icon { background: rgba(234, 179, 8, 0.15); color: #eab308; }
    .dash-kpi.kpi-green  .dash-kpi-icon { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
    .dash-kpi.kpi-blue   .dash-kpi-icon { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }

    .dash-kpi-body { flex: 1; min-width: 0; }
    .dash-kpi-label {
        text-transform: uppercase; letter-spacing: .06em;
        font-size: .68rem; font-weight: 700; color: var(--dash-muted) !important; margin: 0 0 .25rem 0;
    }
    .dash-kpi-value {
        font-size: 1.55rem; font-weight: 700; margin: 0; color: var(--dash-text-strong) !important; line-height: 1.1;
    }
    .dash-kpi-note { font-size: .75rem; margin-top: .25rem; display: inline-flex; align-items: center; gap: .3rem; }
    .dash-kpi-note.note-green { color: #22c55e; }
    .dash-kpi-note.note-yellow { color: #eab308; }

    .dash-card {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: .85rem; padding: 1.15rem 1.25rem;
        box-shadow: var(--dash-shadow) !important;
    }
    .dash-section-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .95rem; font-weight: 700; color: var(--dash-text-strong) !important; margin: 0 0 1rem 0;
    }

    .dash-table { width: 100%; border-collapse: collapse; font-size: .875rem; white-space: nowrap; }
    .dash-table thead th {
        text-transform: uppercase; letter-spacing: .06em;
        font-size: .7rem; font-weight: 700; color: var(--dash-muted) !important;
        padding: .75rem .9rem; text-align: left;
        border-bottom: 1px solid var(--dash-border) !important; background: transparent !important;
    }
    .dash-table tbody td {
        padding: .9rem .9rem; color: var(--dash-text) !important;
        border-bottom: 1px solid var(--dash-border) !important; vertical-align: middle; background: transparent !important;
    }
    .dash-table tbody tr:hover td { background: var(--dash-hover) !important; }

    .dash-pill {
        display: inline-block; padding: .28rem .7rem; border-radius: 999px; font-size: .75rem; font-weight: 600;
    }
    .dash-pill.present { background: rgba(34, 197, 94, 0.18) !important; color: #4ade80 !important; }
    .dash-pill.late    { background: rgba(234, 179, 8, 0.18) !important; color: #facc15 !important; }
    .dash-pill.absent  { background: rgba(239, 68, 68, 0.18) !important; color: #f87171 !important; }
    .dash-pill.room    { background: var(--dash-card-2) !important; color: var(--dash-text) !important; border: 1px solid var(--dash-border); }

    .dash-action-btn {
        width: 30px; height: 30px; display: inline-flex;
        align-items: center; justify-content: center; border-radius: .4rem;
        border: 1px solid var(--dash-border) !important; background: var(--dash-card-2) !important;
        color: var(--dash-text-strong) !important; font-size: .75rem; margin-right: .25rem; text-decoration: none;
    }
    .dash-action-btn:hover { border-color: #3b82f6 !important; color: #3b82f6 !important; }

    .pagination .page-link {
        background-color: var(--dash-card-2) !important;
        border-color: var(--dash-border) !important;
        color: var(--dash-text) !important;
    }
    .pagination .page-item.active .page-link {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #fff !important;
    }

    /* =========================================================
       RESPONSIVE ADJUSTMENTS (DOWN TO 344px)
       ========================================================= */
    @media (max-width: 575.98px) {
        .dash-wrap { padding: 1rem 0.5rem; }
        .dash-page-title { font-size: 1.25rem; }
        .dash-kpi { padding: 0.75rem; gap: 0.6rem; }
        .dash-kpi-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .dash-kpi-value { font-size: 1.25rem; }
        .dash-kpi-label { font-size: 0.6rem; }
        .dash-card { padding: 0.85rem; }
        .dash-table thead th, .dash-table tbody td { padding: 0.6rem 0.5rem; font-size: 0.8rem; }
    }

    @media (max-width: 400px) {
        .dash-page-title { font-size: 1.1rem; }
        .dash-btn-outline { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
        .dash-kpi { flex-direction: column; align-items: flex-start; min-height: auto; }
        .dash-kpi-icon { margin-bottom: 0.5rem; }
        .dash-kpi-value { font-size: 1.15rem; }
        .chart-container { height: 200px !important; }
    }
</style>

<div class="dash-wrap">
    <!-- PAGE HEADER -->
    <div class="dash-page-head">
        <div>
            <h1 class="dash-page-title"><i class="fas fa-user-shield"></i> <span>Monitoring Officer</span></h1>
            <p class="dash-page-sub">Live room inspections &middot; <strong>Today: <?= htmlspecialchars(date('F j, Y')) ?></strong></p>
            <p class="dash-meta d-none d-sm-block">Track real-time faculty presence, student headcount, and unattended room incidents.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="daily-attendance-log.php" class="dash-btn-outline"><i class="fas fa-clipboard-check"></i> <span class="d-none d-sm-inline">Start Room Check</span><span class="d-sm-none">Check</span></a>
            <a href="reports.php" class="dash-btn-outline"><i class="fas fa-file-invoice"></i> <span class="d-none d-sm-inline">View Reports</span><span class="d-sm-none">Reports</span></a>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="dash-kpi kpi-blue">
                <div class="dash-kpi-icon"><i class="fas fa-door-open"></i></div>
                <div class="dash-kpi-body">
                    <p class="dash-kpi-label">Today's Checks</p>
                    <p class="dash-kpi-value"><?= htmlspecialchars($totalChecks) ?></p>
                    <span class="dash-kpi-note note-green"><i class="fas fa-arrow-trend-up"></i> Live today</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="dash-kpi kpi-green">
                <div class="dash-kpi-icon"><i class="fas fa-user-check"></i></div>
                <div class="dash-kpi-body">
                    <p class="dash-kpi-label">Faculty Presence</p>
                    <p class="dash-kpi-value"><?= htmlspecialchars($presenceRate) ?>%</p>
                    <span class="dash-kpi-note note-green"><i class="fas fa-check"></i> <?= (int)$presentFaculty ?> present</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="dash-kpi kpi-yellow">
                <div class="dash-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="dash-kpi-body">
                    <p class="dash-kpi-label">Unattended Rooms</p>
                    <p class="dash-kpi-value"><?= htmlspecialchars($absentFaculty) ?></p>
                    <span class="dash-kpi-note note-yellow"><i class="fas fa-circle-exclamation"></i> Requires action</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART + RECENT INSPECTION LOG (SAME ROW) -->
    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="dash-card h-100">
                <h3 class="dash-section-title"><i class="fas fa-chart-pie"></i> Attendance Overview</h3>
                <div class="chart-container" style="height: 250px; position: relative; width: 100%;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="dash-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="dash-section-title mb-0"><i class="fas fa-list-check"></i> Recent Inspection Log</h3>
                    <a href="reports.php" class="dash-btn-outline" style="padding:.35rem .8rem; font-size:.8rem;">View All</a>
                </div>

                <div class="table-responsive">
                    <table class="dash-table" id="dashLogTable">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th>Room</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No recent inspection logs found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): 
                                    $facName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
                                    
                                    if ($facName === '') {
                                        $facName = !empty($log['faculty_id']) ? 'ID: ' . $log['faculty_id'] : 'Unknown Faculty';
                                    }

                                    $rawStatus = strtolower(trim((string)($log['status'] ?? '')));
                                    if ($rawStatus === '' || $rawStatus === 'pending') $rawStatus = 'present';
                                    
                                    $statusLabel = ucfirst($rawStatus);
                                    $statusClass = $rawStatus === 'present' ? 'present' : ($rawStatus === 'late' ? 'late' : ($rawStatus === 'absent' ? 'absent' : 'pending'));
                                    $roomDisp = !empty($log['room_code']) ? $log['room_code'] : ('Room #' . ($log['room_id'] ?? 'N/A'));
                                    $subjDisp = !empty($log['subject_code']) ? $log['subject_code'] : ('Subj #' . ($log['subject_id'] ?? 'N/A'));
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($facName) ?></strong></td>
                                        <td><span class="dash-pill room"><?= htmlspecialchars($roomDisp) ?></span></td>
                                        <td><?= htmlspecialchars($subjDisp) ?></td>
                                        <td><span class="dash-pill <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                                        <td><?= htmlspecialchars($log['session_date'] ?? '') ?></td>
                                        <td>
                                            <a href="daily-attendance-log.php?session_id=<?= (int)($log['session_id'] ?? 0) ?>" class="dash-action-btn" title="View details"><i class="fas fa-eye"></i></a>
                                            <a href="reports.php?faculty_id=<?= (int)($log['faculty_id'] ?? 0) ?>" class="dash-action-btn" title="Analytics"><i class="fas fa-chart-simple"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-3 pt-3" style="border-top: 1px solid var(--dash-border); gap: 0.5rem;">
                    <small class="text-muted text-center text-sm-start">Showing Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRows ?> records)</small>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Prev</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    // Check for dark mode to adjust chart text colors
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' 
                || document.documentElement.getAttribute('data-theme') === 'dark'
                || document.body.classList.contains('dark-mode');
                
    const textColor = isDark ? '#8492a6' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Present', 'Late', 'Absent'],
            datasets: [{
                label: 'Total Sessions',
                data: [<?= $chartData['present'] ?>, <?= $chartData['late'] ?>, <?= $chartData['absent'] ?>],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.7)',  // Present - Green
                    'rgba(234, 179, 8, 0.7)',  // Late - Yellow
                    'rgba(239, 68, 68, 0.7)'   // Absent - Red
                ],
                borderColor: [
                    '#22c55e',
                    '#eab308',
                    '#ef4444'
                ],
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#16223b' : '#ffffff',
                    titleColor: isDark ? '#ffffff' : '#0f172a',
                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                    borderColor: isDark ? '#1e2d4a' : '#e2e8f0',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor, precision: 0 },
                    grid: { color: gridColor, drawBorder: false }
                },
                x: {
                    ticks: { color: textColor, font: { weight: '600' } },
                    grid: { display: false, drawBorder: false }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>