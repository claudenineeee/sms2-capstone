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

$pdo = getFacultyDatabaseConnection();
$today = date('Y-m-d');

// Fetch today's sessions for live metrics
$stmtToday = $pdo->prepare("
    SELECT s.*, f.first_name, f.last_name 
    FROM class_attendance_sessions s 
    LEFT JOIN faculty_profiles f ON s.faculty_id = f.id 
    WHERE s.session_date = ? 
    ORDER BY s.session_id DESC
");
$stmtToday->execute([$today]);
$todaySessions = $stmtToday->fetchAll(PDO::FETCH_ASSOC);

$totalChecks     = count($todaySessions);
$presentFaculty  = count(array_filter($todaySessions, fn($r) => strtolower($r['status']) === 'present'));
$absentFaculty   = count(array_filter($todaySessions, fn($r) => strtolower($r['status']) === 'absent'));
$presenceRate    = $totalChecks > 0 ? round(($presentFaculty / $totalChecks) * 100) : 0;

$totalStudents   = array_sum(array_column($todaySessions, 'attending_students'));
$avgStudents     = $totalChecks > 0 ? round($totalStudents / $totalChecks) : 0;

// Recent Inspection Log (last 5 records)
$stmtRecent = $pdo->prepare("
    SELECT s.*, f.first_name, f.last_name 
    FROM class_attendance_sessions s 
    LEFT JOIN faculty_profiles f ON s.faculty_id = f.id 
    ORDER BY s.session_id DESC 
    LIMIT 5
");
$stmtRecent->execute();
$recentLogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// Weekly Trend Data (Past 6 days for chart)
$weeklyDataLabels = [];
$weeklyPresent = [];
$weeklyAbsent = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weeklyDataLabels[] = date('D', strtotime($date));
    
    $stmtDay = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as p_count,
            SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as a_count
        FROM class_attendance_sessions 
        WHERE session_date = ?
    ");
    $stmtDay->execute([$date]);
    $dayStats = $stmtDay->fetch(PDO::FETCH_ASSOC);
    
    $weeklyPresent[] = (int)($dayStats['p_count'] ?? 0);
    $weeklyAbsent[] = (int)($dayStats['a_count'] ?? 0);
}

// Status Mix Breakdown for Doughnut Chart
$stmtMix = $pdo->query("
    SELECT 
        SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
        COUNT(*) as total_count
    FROM class_attendance_sessions
");
$mixStats = $stmtMix->fetch(PDO::FETCH_ASSOC);
$totCount = max((int)($mixStats['total_count'] ?? 0), 1);
$pctPresent = round(((int)($mixStats['present_count'] ?? 0) / $totCount) * 100);
$pctAbsent = round(((int)($mixStats['absent_count'] ?? 0) / $totCount) * 100);
$pctUnverified = max(0, 100 - ($pctPresent + $pctAbsent));
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-3 px-2 px-md-3">

    <!-- Hero Header Banner -->
    <div class="card border border-light-subtle shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-body-tertiary text-body position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative z-1">
            <div>
                <span class="badge bg-primary text-white rounded-pill px-3 py-1 mb-2 fs-7 fw-semibold shadow-sm">
                    <i class="fas fa-shield-alt me-1"></i> Live Monitoring Console
                </span>
                <h2 class="h4 h3-md fw-bold mb-1 text-body d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i>
                    <span>Officer Dashboard</span>
                </h2>
                <p class="text-body-secondary small mb-0 fs-7 fs-md-6">Track real-time room inspections, faculty presence trends, and student attendance metrics.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                <a href="daily-attendance-log.php" class="btn btn-primary rounded-3 px-3 shadow-sm btn-sm fs-7 fw-semibold py-1 px-2 py-sm-2 px-sm-3">
                    <i class="fas fa-clipboard-check me-1"></i> Start Room Check
                </a>
                <a href="reports.php" class="btn btn-outline-secondary rounded-3 px-3 btn-sm fs-7 py-1 px-2 py-sm-2 px-sm-3">
                    <i class="fas fa-file-invoice me-1"></i> View Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Quick KPI Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100 bg-white">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-door-open"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Today's Checks</h6>
                        <h4 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($totalChecks); ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>Live Today</small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100 bg-white">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #198754; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Faculty Presence</h6>
                        <h4 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($presenceRate); ?>%</h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-check me-1"></i><?= $presentFaculty ?> Present</small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100 bg-white">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 text-danger fs-4"><i class="fas fa-triangle-exclamation"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Unattended Rooms</h6>
                        <h4 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($absentFaculty); ?></h4>
                        <small class="text-danger fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-triangle-exclamation me-1"></i>Requires action</small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100 bg-white">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0dcaf0; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Avg Student Headcount</h6>
                        <h4 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($avgStudents); ?></h4>
                        <small class="text-info fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-user-check me-1"></i>Active average</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Analytics Charts Section -->
    <div class="row g-3 g-md-4 mb-4">
        
        <!-- Attendance Trend Line Chart -->
        <div class="col-12 col-lg-8">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
                    <div>
                        <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-chart-area text-primary me-2"></i>Weekly Attendance Performance Trend</h5>
                        <small class="text-body-secondary fs-7">Daily distribution of present vs. absent faculty members over the past week</small>
                    </div>
                    <span class="badge bg-secondary text-white border-0 fs-7 px-3 py-1 rounded-pill ms-auto ms-sm-0">Past 6 Days</span>
                </div>
                <!-- Scrollable wrapper for small screens to prevent squeezing/thin graphs -->
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <div class="chart-container position-relative" style="height: 280px; min-width: 500px;">
                        <canvas id="attendanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown Doughnut Chart -->
        <div class="col-12 col-lg-4">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-chart-pie text-success me-2"></i>Faculty Status Mix</h5>
                        <small class="text-body-secondary fs-7">Proportion of overall faculty status</small>
                    </div>
                </div>
                <div class="chart-container position-relative d-flex align-items-center justify-content-center" style="height: 220px;">
                    <canvas id="statusBreakdownChart"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center pt-3 border-top border-light-subtle mt-2">
                    <div>
                        <small class="text-body-secondary d-block fs-7">Present</small>
                        <span class="fw-bold text-success fs-6"><?= $pctPresent ?>%</span>
                    </div>
                    <div>
                        <small class="text-body-secondary d-block fs-7">Absent</small>
                        <span class="fw-bold text-danger fs-6"><?= $pctAbsent ?>%</span>
                    </div>
                    <div>
                        <small class="text-body-secondary d-block fs-7">Unverified</small>
                        <span class="fw-bold text-warning fs-6"><?= $pctUnverified ?>%</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Action Cards & Live Feed Row -->
    <div class="row g-3 g-md-4">
        
        <!-- Quick Action Navigation Cards -->
        <div class="col-12 col-lg-5">
            <div class="d-flex flex-column gap-3 h-100">
                <div class="card border border-light-subtle shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary text-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle flex-shrink-0">
                            <i class="fas fa-clipboard-check fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 fs-6">Daily Attendance Log</h5>
                            <p class="text-body-secondary small mb-0 fs-7">Perform live room inspections and enter headcount verification.</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light-subtle flex-wrap gap-2">
                        <span class="badge bg-primary text-white fw-semibold px-2 py-1"><i class="fas fa-bolt me-1"></i> Live Inspection</span>
                        <a href="daily-attendance-log.php" class="btn btn-primary btn-sm rounded-3 px-3 w-100 w-sm-auto text-center py-1 fs-7">
                            Open Console <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="card border border-light-subtle shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary text-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle flex-shrink-0">
                            <i class="fas fa-file-invoice-dollar fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 fs-6">Reports & Historical Analytics</h5>
                            <p class="text-body-secondary small mb-0 fs-7">Search, filter, and export inspection datasets across ranges.</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light-subtle flex-wrap gap-2">
                        <span class="badge bg-success text-white fw-semibold px-2 py-1"><i class="fas fa-filter me-1"></i> Filterable Records</span>
                        <a href="reports.php" class="btn btn-success btn-sm rounded-3 px-3 w-100 w-sm-auto text-center py-1 fs-7">
                            View Analytics <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed Table -->
        <div class="col-12 col-lg-7">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-bottom border-light-subtle p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-stream text-primary me-2"></i>Recent Inspection Log</h5>
                    <a href="reports.php" class="text-primary fs-7 text-decoration-none fw-semibold">View All <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead>
                            <tr class="text-body-secondary border-light-subtle">
                                <th>Faculty Member</th>
                                <th>Room ID</th>
                                <th class="d-none d-sm-table-cell">Subject ID</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-4">No recent inspection logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <?php 
                                        $facName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Unknown Faculty';
                                        $status = ucfirst(strtolower($log['status'] ?? 'Present'));
                                        $badgeClass = $status === 'Present' ? 'bg-success text-white fw-semibold' : 'bg-danger text-white fw-semibold';
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-body"><?= htmlspecialchars($facName) ?></td>
                                        <td><span class="badge bg-secondary text-white">Room #<?= htmlspecialchars($log['room_id'] ?? 'N/A') ?></span></td>
                                        <td class="d-none d-sm-table-cell">Subj #<?= htmlspecialchars($log['subject_id'] ?? 'N/A') ?></td>
                                        <td><span class="badge <?= $badgeClass ?> rounded-pill px-2 px-sm-3 py-1"><?= $status ?></span></td>
                                        <td class="text-body-secondary d-none d-md-table-cell"><?= htmlspecialchars($log['session_date'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const computedStyle = getComputedStyle(document.body);
    const bodyColor = computedStyle.getPropertyValue('--bs-body-color') || '#6c757d';
    const borderColor = computedStyle.getPropertyValue('--bs-border-color') || 'rgba(0, 0, 0, 0.1)';

    Chart.defaults.color = bodyColor.trim();
    Chart.defaults.borderColor = borderColor.trim();

    // 1. Weekly Attendance Line Chart (Dynamic PHP Data)
    const ctxTrend = document.getElementById('attendanceTrendChart').getContext('2d');
    
    const gradientPresent = ctxTrend.createLinearGradient(0, 0, 0, 250);
    gradientPresent.addColorStop(0, 'rgba(13, 110, 253, 0.35)');
    gradientPresent.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

    const gradientAbsent = ctxTrend.createLinearGradient(0, 0, 0, 250);
    gradientAbsent.addColorStop(0, 'rgba(220, 53, 69, 0.25)');
    gradientAbsent.addColorStop(1, 'rgba(220, 53, 69, 0.0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode($weeklyDataLabels) ?>,
            datasets: [
                {
                    label: 'Present Faculty',
                    data: <?= json_encode($weeklyPresent) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: gradientPresent,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0d6efd',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Absent Incidents',
                    data: <?= json_encode($weeklyAbsent) ?>,
                    borderColor: '#dc3545',
                    backgroundColor: gradientAbsent,
                    borderWidth: 2,
                    borderDash: [4, 4],
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    padding: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: borderColor }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Status Breakdown Doughnut Chart (Dynamic PHP Data)
    const ctxStatus = document.getElementById('statusBreakdownChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Unverified'],
            datasets: [{
                data: [<?= $pctPresent ?>, <?= $pctAbsent ?>, <?= $pctUnverified ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>