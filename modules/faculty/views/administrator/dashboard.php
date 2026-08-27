<?php
/**
 * SMS 2 - Faculty Admin Dashboard (Senior UI/UX Redesign)
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$pdo = db();

// 1. Fetch Dashboard Metrics
$totalFaculty = (int)$pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles")->fetchColumn();

$pendingApprovals = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM faculty_db.faculty_profiles fp
    JOIN sms2_db.users u ON fp.user_id = u.id
    WHERE u.status = 'pending_approval' OR fp.profile_status = 'Pending Approval'
")->fetchColumn();

$activeFaculty = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM faculty_db.faculty_profiles 
    WHERE LOWER(employment_status) = 'active' OR LOWER(employment_status) = 'regular'
")->fetchColumn();

$departmentHeads = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM faculty_db.faculty_profiles 
    WHERE LOWER(position) LIKE '%head%'
")->fetchColumn();

// 2. Query Analytics Data for Charts
// Chart 1: Department Distribution Top 5
$deptStmt = $pdo->query("
    SELECT designated_department AS dept, COUNT(*) as count 
    FROM faculty_db.faculty_profiles 
    WHERE designated_department IS NOT NULL AND designated_department != ''
    GROUP BY designated_department 
    ORDER BY count DESC 
    LIMIT 5
");
$deptData = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

$deptLabels = array_column($deptData, 'dept');
$deptCounts = array_column($deptData, 'count');

// Chart 2: Status Breakdown
$regularCount = (int)$pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles WHERE LOWER(employment_status) IN ('regular', 'full-time')")->fetchColumn();
$partTimeCount = (int)$pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_profiles WHERE LOWER(employment_status) IN ('part-time', 'contract')")->fetchColumn();
$otherCount = max(0, $totalFaculty - ($regularCount + $partTimeCount));

// 3. Fetch Recent Pending Requests
$stmtPending = $pdo->query("
    SELECT fp.*, u.status AS account_status, u.id AS auth_user_id
    FROM faculty_db.faculty_profiles fp
    JOIN sms2_db.users u ON fp.user_id = u.id
    WHERE u.status = 'pending_approval' OR fp.profile_status = 'Pending Approval'
    ORDER BY fp.created_at DESC
    LIMIT 5
");
$recentPending = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

// Page configuration & breadcrumbs
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

<!-- Chart.js CDN for Analytics Graphics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    .stat-card {
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    .action-pill {
        transition: all 0.2s ease;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
    }
    .action-pill:hover {
        background: var(--bs-tertiary-bg);
        border-color: var(--bs-border-color-translucent);
        transform: translateX(4px);
    }
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .chart-container {
        position: relative;
        height: 240px;
        width: 100%;
    }
</style>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid py-3 px-2 px-md-3">
    <!-- Header with quick actions -->
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-body">
                <i class="fas fa-chart-line text-primary me-2"></i>
                <span>Faculty Management Executive Dashboard</span>
            </h1>
            <p class="text-body-secondary mb-0">Overview of faculty headcounts, department distribution, and pending access approvals.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
            <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" 
            class="btn btn-sm btn-outline-warning rounded-3 fs-7 d-inline-flex align-items-center">
                <i class="fas fa-user-clock me-1"></i> Review Requests (<?= $pendingApprovals ?>)
            </a>
        </div>
    </div>

    <!-- Executive Metric Cards -->
    <div class="row g-2 g-md-3 mb-4">
        <!-- Card 1: Total Faculty -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-primary bg-primary bg-opacity-10 fs-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Total Faculty</h6>
                        <h4 class="mb-0 fw-bold fs-3"><?= number_format($totalFaculty) ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-arrow-trend-up me-1"></i>+4.2% <span class="text-body-secondary fw-normal">from last semester</span>
                        </small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/faculty-directory.php" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Card 2: Pending Approvals -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-warning bg-warning bg-opacity-10 fs-4">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Pending Approvals</h6>
                        <h4 class="mb-0 fw-bold text-warning fs-3"><?= number_format($pendingApprovals) ?></h4>
                        <small class="text-body-secondary fw-normal" style="font-size: 0.75rem;">
                            <i class="fas fa-exclamation-circle text-warning me-1"></i>Requires admin clearance
                        </small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/pending-approvals.php" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Card 3: Active Faculty -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-success bg-success bg-opacity-10 fs-4">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Active Faculty</h6>
                        <h4 class="mb-0 fw-bold text-success fs-3"><?= number_format($activeFaculty) ?></h4>
                        <small class="text-body-secondary fw-normal" style="font-size: 0.75rem;">
                            <?= $totalFaculty > 0 ? round(($activeFaculty / $totalFaculty) * 100, 1) : 0 ?>% active deployment rate
                        </small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/faculty-directory.php" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Card 4: Department Heads -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-info bg-info bg-opacity-10 fs-4">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Department Heads</h6>
                        <h4 class="mb-0 fw-bold text-info fs-3"><?= number_format($departmentHeads) ?></h4>
                        <small class="text-body-secondary fw-normal" style="font-size: 0.75rem;">
                            <i class="fas fa-building me-1"></i>Active supervisory accounts
                        </small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/department-assignments.php" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
    </div>

    <!-- Analytics Section (Charts) -->
    <div class="row g-3 g-md-4 mb-4">
        <!-- Chart 1: Top Department Faculty Counts -->
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm h-100 rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold fs-6"><i class="fas fa-chart-bar me-2 text-primary"></i>Faculty Distribution by Department</h6>
                    <span class="badge bg-body text-body-secondary border border-light-subtle fs-7">Top Departments</span>
                </div>
                <div class="card-body p-3">
                    <div class="chart-container">
                        <canvas id="deptDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Employment Status Ratio -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm h-100 rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3">
                    <h6 class="card-title mb-0 fw-bold fs-6"><i class="fas fa-chart-pie me-2 text-info"></i>Employment Breakdown</h6>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-center">
                    <div class="chart-container mb-2" style="height: 190px;">
                        <canvas id="employmentChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around text-center mt-2 pt-2 border-top border-light-subtle">
                        <div>
                            <span class="small text-body-secondary d-block fs-7">Regular</span>
                            <span class="fw-bold text-primary fs-6"><?= $regularCount ?></span>
                        </div>
                        <div>
                            <span class="small text-body-secondary d-block fs-7">Part-Time</span>
                            <span class="fw-bold text-info fs-6"><?= $partTimeCount ?></span>
                        </div>
                        <div>
                            <span class="small text-body-secondary d-block fs-7">Other</span>
                            <span class="fw-bold text-body-secondary fs-6"><?= $otherCount ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables and Actions Queue -->
    <div class="row g-3 g-md-4">
        <!-- Recent Requests -->
        <div class="col-12 col-lg-8">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold fs-6"><i class="fas fa-clock text-warning me-2"></i>Pending Approval Queue</h6>
                        <span class="text-body-secondary fs-7">Accounts awaiting admin clearance</span>
                    </div>
                    <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" class="btn btn-sm btn-outline-warning rounded-3 fs-7">View All Queue</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead>
                                <tr class="text-body-secondary border-light-subtle">
                                    <th>Faculty Member</th>
                                    <th>Faculty ID</th>
                                    <th class="d-none d-sm-table-cell">Department</th>
                                    <th class="d-none d-md-table-cell">Position</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentPending)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-body-secondary py-4">
                                            <i class="fas fa-check-circle fa-2x mb-2 text-success d-block opacity-75"></i>
                                            No pending account approvals required.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentPending as $row): ?>
                                        <?php 
                                            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                            $initial = !empty($name) ? strtoupper(substr($name, 0, 1)) : '?';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle bg-warning bg-opacity-20 text-warning">
                                                        <?= htmlspecialchars($initial) ?>
                                                    </div>
                                                    <span class="fw-semibold text-body"><?= htmlspecialchars($name) ?></span>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-info"><?= htmlspecialchars($row['faculty_id'] ?? '—') ?></td>
                                            <td class="d-none d-sm-table-cell"><span class="badge bg-body-secondary text-body border border-light-subtle"><?= htmlspecialchars($row['designated_department'] ?? '—') ?></span></td>
                                            <td class="text-body-secondary d-none d-md-table-cell"><?= htmlspecialchars($row['position'] ?? '—') ?></td>
                                            <td class="text-end pe-3">
                                                <a href="<?= BASE_URL ?>/modules/faculty/views/pending-approvals.php" class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 px-3 py-1 text-decoration-none fw-bold fs-7 rounded-pill">
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Quick Access Sidebar -->
    <div class="col-12 col-lg-4">
        <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-bottom border-light-subtle py-3">
                <h6 class="card-title mb-0 fw-bold fs-6"><i class="fas fa-compass text-primary me-2"></i>Quick Navigation</h6>
            </div>
            <div class="card-body p-3 d-flex flex-column gap-2">
                
                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/pending-approvals.php" 
                class="p-3 rounded-3 text-decoration-none text-body border border-light-subtle bg-hover-tertiary d-flex align-items-center justify-content-between icon-link icon-link-hover">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning flex-shrink-0 fs-5 rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-7">Pending Approvals</div>
                            <div class="small text-body-secondary fs-7"><?= $pendingApprovals ?> requests awaiting action</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-body-secondary small ms-2"></i>
                </a>

                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-directory.php" 
                class="p-3 rounded-3 text-decoration-none text-body border border-light-subtle bg-hover-tertiary d-flex align-items-center justify-content-between icon-link icon-link-hover">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info flex-shrink-0 fs-5 rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-address-book"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-7">Faculty Directory</div>
                            <div class="small text-body-secondary fs-7">Browse all department records</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-body-secondary small ms-2"></i>
                </a>

                <a href="<?= BASE_URL ?>/modules/faculty/views/administrator/faculty-profile.php" 
                class="p-3 rounded-3 text-decoration-none text-body border border-light-subtle bg-hover-tertiary d-flex align-items-center justify-content-between icon-link icon-link-hover">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary flex-shrink-0 fs-5 rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-7">Profiles & Credentials</div>
                            <div class="small text-body-secondary fs-7">View academic ranks & details</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-body-secondary small ms-2"></i>
                </a>

            </div>
        </div>
    </div>
    </div>
</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const computedStyle = getComputedStyle(document.body);
    const bodyColor = computedStyle.getPropertyValue('--bs-body-color') || '#6c757d';
    const borderColor = computedStyle.getPropertyValue('--bs-border-color') || 'rgba(0, 0, 0, 0.1)';

    Chart.defaults.color = bodyColor.trim();
    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

    // 1. Department Distribution Bar Chart
    const deptCtx = document.getElementById('deptDistributionChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(!empty($deptLabels) ? $deptLabels : ['CS', 'IT', 'Eng', 'Bus', 'Educ']) ?>,
            datasets: [{
                label: 'Faculty Count',
                data: <?= json_encode(!empty($deptCounts) ? $deptCounts : [12, 19, 8, 15, 7]) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.75)',
                borderColor: '#0d6efd',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
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

    // 2. Employment Breakdown Doughnut Chart
    const empCtx = document.getElementById('employmentChart').getContext('2d');
    new Chart(empCtx, {
        type: 'doughnut',
        data: {
            labels: ['Regular', 'Part-Time', 'Other'],
            datasets: [{
                data: [<?= $regularCount ?>, <?= $partTimeCount ?>, <?= $otherCount ?>],
                backgroundColor: [
                    '#0d6efd',
                    '#0dcaf0',
                    '#6c757d'
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '75%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>