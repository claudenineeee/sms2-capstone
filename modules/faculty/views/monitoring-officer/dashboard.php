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

// Session records count for metrics
$records         = $_SESSION['attendance_records'] ?? [];
$totalChecks     = count($records) > 0 ? count($records) : 24;
$presentFaculty  = count(array_filter($records, fn($r) => ($r['professor_status'] ?? '') === 'Present'));
$presentFaculty  = $presentFaculty > 0 ? $presentFaculty : 21;
$absentFaculty   = count(array_filter($records, fn($r) => ($r['professor_status'] ?? '') === 'Absent'));
$absentFaculty   = $absentFaculty > 0 ? $absentFaculty : 3;
$presenceRate    = $totalChecks > 0 ? round(($presentFaculty / $totalChecks) * 100) : 88;
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-3 px-2 px-md-3">

    <!-- Hero Header Banner -->
    <div class="card border border-light-subtle shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-body-tertiary text-body position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative z-1">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 mb-2 fs-7">
                    <i class="fas fa-shield-alt me-1"></i> Live Monitoring Console
                </span>
                <h2 class="h4 h3-md fw-bold mb-1 text-body d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i>
                    <span>Officer Dashboard</span>
                </h2>
                <p class="text-body-secondary small mb-0 fs-7 fs-md-6">Track real-time room inspections, faculty presence trends, and student attendance metrics.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                <a href="daily-attendance-log.php" class="btn btn-primary rounded-3 px-3 shadow-sm btn-sm fs-7 fw-semibold">
                    <i class="fas fa-clipboard-check me-1"></i> Start Room Check
                </a>
                <a href="reports.php" class="btn btn-outline-secondary rounded-3 px-3 btn-sm fs-7">
                    <i class="fas fa-file-invoice me-1"></i> View Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Quick KPI Metrics Cards -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm rounded-4 p-3 bg-body-tertiary text-body h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-body-secondary fw-semibold fs-7 d-block mb-1">Today's Checks</span>
                        <h2 class="fw-bold mb-0 text-primary fs-3 fs-md-2"><?= $totalChecks ?></h2>
                    </div>
                    <div class="p-2 p-md-3 bg-primary bg-opacity-10 text-primary rounded-4">
                        <i class="fas fa-door-open fs-5 fs-md-4"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center text-body-secondary fs-7">
                    <span class="badge bg-success-subtle text-success me-2"><i class="fas fa-arrow-up me-1"></i>+12%</span>
                    <span>vs. yesterday</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm rounded-4 p-3 bg-body-tertiary text-body h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-body-secondary fw-semibold fs-7 d-block mb-1">Faculty Presence</span>
                        <h2 class="fw-bold mb-0 text-success fs-3 fs-md-2"><?= $presenceRate ?>%</h2>
                    </div>
                    <div class="p-2 p-md-3 bg-success bg-opacity-10 text-success rounded-4">
                        <i class="fas fa-user-check fs-5 fs-md-4"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center fs-7 text-body-secondary flex-wrap">
                    <span class="text-success fw-bold me-1"><i class="fas fa-check-circle me-1"></i><?= $presentFaculty ?> Present</span>
                    <span>/ <?= $absentFaculty ?> Absent</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm rounded-4 p-3 bg-body-tertiary text-body h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-body-secondary fw-semibold fs-7 d-block mb-1">Unattended Rooms</span>
                        <h2 class="fw-bold mb-0 text-warning fs-3 fs-md-2"><?= $absentFaculty ?></h2>
                    </div>
                    <div class="p-2 p-md-3 bg-warning bg-opacity-10 text-warning rounded-4">
                        <i class="fas fa-exclamation-triangle fs-5 fs-md-4"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center text-warning fs-7">
                    <i class="fas fa-clock me-1"></i> Requires mayor verification
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm rounded-4 p-3 bg-body-tertiary text-body h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-body-secondary fw-semibold fs-7 d-block mb-1">Avg Student Attendance</span>
                        <h2 class="fw-bold mb-0 text-info fs-3 fs-md-2">94%</h2>
                    </div>
                    <div class="p-2 p-md-3 bg-info bg-opacity-10 text-info rounded-4">
                        <i class="fas fa-users fs-5 fs-md-4"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center text-body-secondary fs-7">
                    <span class="badge bg-info-subtle text-info me-2">Stable</span>
                    <span>Across active rooms</span>
                </div>
            </div>
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
                    <span class="badge bg-body text-body-secondary border border-light-subtle fs-7 px-3 py-1 rounded-pill ms-auto ms-sm-0">Past 7 Days</span>
                </div>
                <div class="chart-container position-relative" style="height: 280px;">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Status Breakdown Doughnut Chart -->
        <div class="col-12 col-lg-4">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-chart-pie text-success me-2"></i>Faculty Status Mix</h5>
                        <small class="text-body-secondary fs-7">Proportion of faculty status today</small>
                    </div>
                </div>
                <div class="chart-container position-relative d-flex align-items-center justify-content-center" style="height: 220px;">
                    <canvas id="statusBreakdownChart"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center pt-3 border-top border-light-subtle mt-2">
                    <div>
                        <small class="text-body-secondary d-block fs-7">Present</small>
                        <span class="fw-bold text-success fs-6">88%</span>
                    </div>
                    <div>
                        <small class="text-body-secondary d-block fs-7">Absent</small>
                        <span class="fw-bold text-danger fs-6">8%</span>
                    </div>
                    <div>
                        <small class="text-body-secondary d-block fs-7">Unverified</small>
                        <span class="fw-bold text-warning fs-6">4%</span>
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
                        <span class="badge bg-primary-subtle text-primary"><i class="fas fa-bolt me-1"></i> Live Inspection</span>
                        <a href="daily-attendance-log.php" class="btn btn-primary btn-sm rounded-3 px-3 w-100 w-sm-auto text-center">
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
                        <span class="badge bg-success-subtle text-success"><i class="fas fa-filter me-1"></i> Filterable Records</span>
                        <a href="reports.php" class="btn btn-success btn-sm rounded-3 px-3 w-100 w-sm-auto text-center">
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
                                <th>Room</th>
                                <th class="d-none d-sm-table-cell">Subject</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-body">Dr. Earl Salvame</td>
                                <td><span class="badge bg-body-secondary text-body">403-B</span></td>
                                <td class="d-none d-sm-table-cell">SIA-201</td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 px-sm-3 py-1">Present</span></td>
                                <td class="text-body-secondary d-none d-md-table-cell">10 mins ago</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-body">Prof. Juan Dela Cruz</td>
                                <td><span class="badge bg-body-secondary text-body">301-A</span></td>
                                <td class="d-none d-sm-table-cell">ITE-101</td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 px-sm-3 py-1">Present</span></td>
                                <td class="text-body-secondary d-none d-md-table-cell">25 mins ago</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-body">Dr. Maria Santos</td>
                                <td><span class="badge bg-body-secondary text-body">202-C</span></td>
                                <td class="d-none d-sm-table-cell">EDUC-30</td>
                                <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 px-sm-3 py-1">Absent</span></td>
                                <td class="text-body-secondary d-none d-md-table-cell">1 hour ago</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-body">Prof. Luis Tan</td>
                                <td><span class="badge bg-body-secondary text-body">104-A</span></td>
                                <td class="d-none d-sm-table-cell">BUS-110</td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 px-sm-3 py-1">Present</span></td>
                                <td class="text-body-secondary d-none d-md-table-cell">2 hours ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Dynamic chart color detection based on computed styles
    const computedStyle = getComputedStyle(document.body);
    const bodyColor = computedStyle.getPropertyValue('--bs-body-color') || '#6c757d';
    const borderColor = computedStyle.getPropertyValue('--bs-border-color') || 'rgba(0, 0, 0, 0.1)';

    Chart.defaults.color = bodyColor.trim();
    Chart.defaults.borderColor = borderColor.trim();

    // 1. Weekly Attendance Line Chart
    const ctxTrend = document.getElementById('attendanceTrendChart').getContext('2d');
    
    // Gradient fills
    const gradientPresent = ctxTrend.createLinearGradient(0, 0, 0, 250);
    gradientPresent.addColorStop(0, 'rgba(13, 110, 253, 0.35)');
    gradientPresent.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

    const gradientAbsent = ctxTrend.createLinearGradient(0, 0, 0, 250);
    gradientAbsent.addColorStop(0, 'rgba(220, 53, 69, 0.25)');
    gradientAbsent.addColorStop(1, 'rgba(220, 53, 69, 0.0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [
                {
                    label: 'Present Faculty',
                    data: [18, 22, 20, 24, 21, 19],
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
                    data: [2, 1, 3, 0, 2, 1],
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

    // 2. Status Breakdown Doughnut Chart
    const ctxStatus = document.getElementById('statusBreakdownChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Unverified'],
            datasets: [{
                data: [88, 8, 4],
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