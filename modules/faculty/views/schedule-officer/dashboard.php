<?php
/**
 * Department Head Dashboard
 * Purpose: Overview of department status and key metrics
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';

requireAuth();

$pageTitle    = 'Department Head Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Department Head', 'url' => BASE_URL . '/modules/faculty/users/head/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-user-tie text-purple me-2"></i>Department Head Dashboard</h1>
        <p class="text-muted mb-0">College of Computer Studies (CCS) — Overview and quick actions</p>
    </div>
        <div class="d-flex flex-wrap gap-1.5 gap-sm-2">   
        <button class="btn btn-sm btn-warning py-1.5 px-2.5 px-sm-3 d-inline-flex align-items-center" 
                onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/head/pages/teaching-load-approval.php'">
            <i class="fas fa-inbox me-1"></i>
            <span>Pending<span class="d-none d-sm-inline"> Approvals</span></span>
            <span class="badge text-bg-dark ms-1.5">5</span>
        </button>
        <button class="btn btn-sm btn-sms-primary py-1.5 px-2.5 px-sm-3 d-inline-flex align-items-center" 
                onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/head/pages/faculty-directory.php'">
            <i class="fas fa-users me-1"></i>
            <span>Faculty<span class="d-none d-sm-inline"> Directory</span></span>
        </button>

    </div>
</div>


<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-primary fs-4 me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Faculty Count</h6>
                        <h4 class="mb-0 fw-bold text-body">18</h4>
                    </div>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill">↑ 2 this sem</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-warning fs-4 me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Pending Requests</h6>
                        <h4 class="mb-0 fw-bold text-body">5</h4>
                    </div>
                </div>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill">5 new</span>
            </div>
        </div>
    </div>

    <!-- Row 1, Col 3: Teaching Load -->
    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-success fs-4 me-3">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Teaching Load</h6>
                        <h4 class="mb-0 fw-bold text-body">17.8 <span class="fs-6 fw-normal text-body-secondary">u</span></h4>
                    </div>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill">Target: 18</span>
            </div>
        </div>
    </div>

    <!-- Row 2, Col 1: Schedule Conflicts -->
    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-danger fs-4 me-3">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Schedule Conflicts</h6>
                        <h4 class="mb-0 fw-bold text-body">1</h4>
                    </div>
                </div>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill">Open</span>
            </div>
        </div>
    </div>

    <!-- Row 2, Col 2: Faculty Performance -->
    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-info fs-4 me-3">
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Faculty Performance</h6>
                        <h4 class="mb-0 fw-bold text-body">4.3 <span class="fs-6 fw-normal text-body-secondary">/ 5.0</span></h4>
                    </div>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill">↑ 0.2</span>
            </div>
        </div>
    </div>

    <!-- Row 2, Col 3: Dept. Attendance -->
    <div class="col-12 col-md-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="text-primary fs-4 me-3">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Dept. Attendance</h6>
                        <h4 class="mb-0 fw-bold text-body">92%</h4>
                    </div>
                </div>
                <span class="badge bg-body-tertiary text-body-secondary border rounded-pill">This month</span>
            </div>
        </div>
    </div>
</div>


<!-- Charts -->
<div class="row g-3 mb-4">
    <!-- Chart 1: Department Performance -->
    <div class="col-12 col-xl-6">
        <div class="card h-100 shadow-sm border">
            <div class="card-header bg-body-tertiary border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded bg-primary bg-opacity-10 text-primary me-2">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-body">Department Performance</h6>
                </div>
                <select class="form-select form-select-sm w-auto align-self-start align-self-sm-auto shadow-none">
                    <option>6 Semester View</option>
                    <option>12 Semester View</option>
                </select>
            </div>
            <div class="card-body p-2 p-sm-3">
                <div class="ratio ratio-16x9 ratio-xl-21x9">
                    <canvas id="deptPerfChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart 2: Attendance Trend -->
    <div class="col-12 col-xl-6">
        <div class="card h-100 shadow-sm border">
            <div class="card-header bg-body-tertiary border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded bg-info bg-opacity-10 text-info me-2">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-body">Attendance Trend (30 Days)</h6>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill align-self-start align-self-sm-auto">
                    <i class="fas fa-arrow-up me-1"></i>92% Avg
                </span>
            </div>
            <div class="card-body p-2 p-sm-3">
                <div class="ratio ratio-16x9 ratio-xl-21x9">
                    <canvas id="attTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4 shadow-sm border">
    <div class="card-header bg-body-tertiary border-bottom py-3">
        <h6 class="mb-0 fw-semibold text-body">
            <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="<?= BASE_URL ?>/modules/faculty/users/head/pages/faculty-directory.php" class="btn btn-outline-primary w-100 h-100 p-3 d-flex flex-column align-items-center justify-content-center gap-2 border">
                    <i class="fas fa-users fa-2x"></i>
                    <span class="fw-semibold">View Faculty</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_URL ?>/modules/faculty/users/head/pages/teaching-load-approval.php" class="btn btn-outline-warning w-100 h-100 p-3 d-flex flex-column align-items-center justify-content-center gap-2 border">
                    <i class="fas fa-inbox fa-2x"></i>
                    <span class="fw-semibold">Review Loads</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_URL ?>/modules/faculty/users/head/pages/schedule-approval.php" class="btn btn-outline-danger w-100 h-100 p-3 d-flex flex-column align-items-center justify-content-center gap-2 border">
                    <i class="fas fa-calendar-times fa-2x"></i>
                    <span class="fw-semibold">View Conflicts</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_URL ?>/modules/faculty/users/head/pages/faculty-performance.php" class="btn btn-outline-success w-100 h-100 p-3 d-flex flex-column align-items-center justify-content-center gap-2 border">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <span class="fw-semibold">Performance</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card mb-4 shadow-sm border">
    <div class="card-header bg-body-tertiary border-bottom py-3">
        <h6 class="mb-0 fw-semibold text-body">
            <i class="fas fa-history me-2 text-info"></i>Recent Department Activity
        </h6>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item bg-transparent d-flex align-items-center justify-content-between p-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-body">Teaching load submitted for approval</div>
                        <div class="small text-body-secondary">Prof. John Doe • BSIT Department</div>
                    </div>
                </div>
                <small class="text-body-tertiary">10 mins ago</small>
            </li>
            <li class="list-group-item bg-transparent d-flex align-items-center justify-content-between p-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-body">Schedule conflict detected</div>
                        <div class="small text-body-secondary">Room 302 • Mon/Wed 08:00 AM</div>
                    </div>
                </div>
                <small class="text-body-tertiary">1 hour ago</small>
            </li>
        </ul>
    </div>
</div>

<script>
(function() {
    /* Department Performance Chart */
    const ctxPerf = document.getElementById('deptPerfChart');
    if (ctxPerf) {
        const sems = ['2024-2S', '2025-1S', '2025-2S', '2026-1S', '2026-2S', '2027-1S'];
        new Chart(ctxPerf, {
            type: 'bar',
            data: {
                labels: sems,
                datasets: [
                    { label: 'Student Eval', data: [3.9, 4.0, 4.1, 4.2, 4.3, 4.4], backgroundColor: '#0d6efd' },
                    { label: 'Attendance', data: [4.4, 4.3, 4.5, 4.5, 4.6, 4.6], backgroundColor: '#198754' },
                    { label: 'Peer Review', data: [3.8, 3.9, 4.0, 4.1, 4.2, 4.3], backgroundColor: '#ffc107' },
                    { label: 'Research', data: [3.4, 3.5, 3.6, 3.8, 3.9, 4.0], backgroundColor: '#0dcaf0' },
                    { 
                        label: 'Dept Trend', 
                        type: 'line', 
                        data: [3.88, 3.93, 4.05, 4.15, 4.25, 4.33], 
                        borderColor: '#212529', 
                        backgroundColor: 'transparent', 
                        tension: 0.35, 
                        borderWidth: 2, 
                        pointRadius: 4 
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 576 ? 'bottom' : 'top', // Adjust legend position dynamically
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45, // Angles x-axis text on small screens to fit neatly
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }

    /* Attendance Trend Chart */
    const ctxTrend = document.getElementById('attTrendChart');
    if (ctxTrend) {
        const labels = Array.from({ length: 30 }, (_, i) => `D${i + 1}`);
        const data = labels.map(() => 86 + Math.round(Math.random() * 13));
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Attendance %',
                    data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 1,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        min: 70,
                        max: 100,
                        ticks: { callback: v => v + '%' }
                    }
                }
            }
        });
    }
})();

</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
