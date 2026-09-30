<?php
/**
 * SMS 2 - Dean Leave Application & Approval
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/authentication.php';

requireAuth();

// Explicitly connect to faculty_db since sms2_db is reserved for authentication/users
$pdo = null;
$actionMessage = ''; $actionType = '';

try {
    $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    
    // Explicitly target faculty_db database
    $dsn = "mysql:host={$dbHost};dbname=faculty_db;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    if (function_exists('getFacultyDatabaseConnection')) {
        try {
            $pdo = getFacultyDatabaseConnection();
        } catch (Throwable $ex) {
            $actionMessage = "Database connection error: " . $ex->getMessage(); 
            $actionType = 'danger';
        }
    } else {
        $actionMessage = "Database connection error: " . $e->getMessage(); 
        $actionType = 'danger';
    }
}

// Session scope variables matching the faculty profile pattern
$userRole       = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'dean');
$userCollege    = strtoupper($_SESSION['college'] ?? $_SESSION['assigned_college'] ?? $_SESSION['college_code'] ?? 'CCS');
$userDepartment = $_SESSION['department'] ?? $_SESSION['assigned_dept'] ?? $_SESSION['dept'] ?? $_SESSION['user_dept'] ?? 'BSIT';

$collegeScopes = [
    'CCS'  => ['BSIT', 'BSCS', 'BSCpE', 'Information Technology'],
    'CCJE' => ['BSCrim', 'Criminology'],
    'CBM'  => ['BSEM', 'BSTM', 'Business Administration'],
    'CED'  => ['BSED', 'Education'],
];

$allowedDepts = $collegeScopes[$userCollege] ?? ['BSIT', 'BSCS', 'BSCpE', 'Information Technology'];
if (!empty($userDepartment) && !in_array($userDepartment, $allowedDepts)) {
    $allowedDepts = [$userDepartment];
}

// Fetch Dynamic Statistics based on Department Scope
$totalFaculty = 0;
$stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'total_requests' => 0];
$leaveApplications = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $placeholders = implode(',', array_fill(0, count($allowedDepts), '?'));
        $facParams = array_merge($allowedDepts, $allowedDepts);
        
        // 1. Total Faculty count under current department scope
        $stmtFac = $pdo->prepare("
            SELECT COUNT(*) 
            FROM faculty f
            JOIN departments d ON f.department_id = d.department_id
            WHERE d.code IN ($placeholders) OR d.name IN ($placeholders)
        ");
        $stmtFac->execute($facParams);
        $totalFaculty = $stmtFac->fetchColumn();

        // 2. Counts for Leave Requests
        $stmtStats = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN l.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN l.status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN l.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
                COUNT(*) as total_requests
            FROM leave_requests l
            JOIN faculty f ON l.faculty_id = f.faculty_id
            JOIN departments d ON f.department_id = d.department_id
            WHERE d.code IN ($placeholders) OR d.name IN ($placeholders)
        ");
        $stmtStats->execute($facParams);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: $stats;

        // 3. Fetch Actual Leave Application Records
        $stmtLeaves = $pdo->prepare("
            SELECT l.*, f.first_name, f.last_name, COALESCE(d.code, d.name) AS department 
            FROM leave_requests l
            JOIN faculty f ON l.faculty_id = f.faculty_id
            JOIN departments d ON f.department_id = d.department_id
            WHERE d.code IN ($placeholders) OR d.name IN ($placeholders)
            ORDER BY l.created_at DESC
        ");
        $stmtLeaves->execute($facParams);
        $leaveApplications = $stmtLeaves->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $actionMessage = "Database query error: " . $e->getMessage();
        $actionType = 'danger';
    }
}

// Group leave applications by faculty member for the left table
$facultyLeaveGroups = [];
$trendCounts = [];
$categoryCounts = [];

foreach ($leaveApplications as $app) {
    $facId = $app['faculty_id'];
    if (!isset($facultyLeaveGroups[$facId])) {
        $facultyLeaveGroups[$facId] = [
            'faculty_id' => $facId,
            'first_name' => $app['first_name'],
            'last_name'  => $app['last_name'],
            'department' => $app['department'],
            'requests'   => []
        ];
    }
    $facultyLeaveGroups[$facId]['requests'][] = $app;

    // Process Trend Data (Grouped by application date)
    $dateKey = !empty($app['created_at']) ? date('M d', strtotime($app['created_at'])) : (!empty($app['start_date']) ? date('M d', strtotime($app['start_date'])) : 'Unknown');
    $trendCounts[$dateKey] = ($trendCounts[$dateKey] ?? 0) + 1;

    // Process Category Breakdown Data using official leave type/category columns or fallback to leave_type field if exists
    $category = !empty($app['leave_type']) ? trim($app['leave_type']) : (!empty($app['category']) ? trim($app['category']) : 'Personal Leave');
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
}

// Sort trend counts chronologically if possible
uksort($trendCounts, function($a, $b) {
    return strtotime($a) - strtotime($b);
});

// Fallbacks if tables are empty
if (empty($trendCounts)) {
    $trendCounts = ['No Data' => 0];
}
if (empty($categoryCounts)) {
    $categoryCounts = ['Vacation' => 1, 'Sick Leave' => 1];
}

$pageTitle    = 'Leave Application & Approval';
$activeModule = 'faculty';
$activePage   = 'leave-application-approval';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Application & Approval', 'url' => null],
];

$breadcrumbsPath = __DIR__ . '/../../../../includes/breadcrumbs.php';
if (file_exists($breadcrumbsPath)) {
    require_once $breadcrumbsPath;
}

require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php 
if (function_exists('renderBreadcrumbs')) {
    renderBreadcrumbs($breadcrumbs);
} else {
    echo '<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb">';
    foreach ($breadcrumbs as $b) {
        if (!empty($b['url'])) {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($b['url']) . '">' . htmlspecialchars($b['label']) . '</a></li>';
        } else {
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($b['label']) . '</li>';
        }
    }
    echo '</ol></nav>';
}
?>
<script src="<?= BASE_URL ?>/../../../../assets/js/loader.js"></script>

<div class="container-fluid py-3 px-2 px-md-3">
    <?php if (!empty($actionMessage)): ?>
        <div class="alert alert-<?= $actionType ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($actionMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-envelope-open-text text-primary"></i>
                <span>Leave Application &amp; Approval</span>
            </h1>
            <p class="text-body-secondary small mb-0">Monitor and review faculty leave status requests filtered for your department scope.</p>
        </div>
    </div>

    <div class="row g-3 mb-4 dashboard-stats">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card bg-body-tertiary border border-light-subtle shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary fs-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Department Faculty</h6>
                        <h4 class="mb-0 fw-bold text-body"><?= intval($totalFaculty) ?></h4>
                        <small class="text-success fw-semibold fs-8">
                            <i class="fas fa-arrow-trend-up me-1"></i>Active Scope
                        </small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card bg-body-tertiary border border-light-subtle shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning fs-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Pending Review</h6>
                        <h4 class="mb-0 fw-bold text-body"><?= intval($stats['pending_count']) ?></h4>
                        <small class="text-warning fw-semibold fs-8">Awaiting action</small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card bg-body-tertiary border border-light-subtle shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Approved</h6>
                        <h4 class="mb-0 fw-bold text-body"><?= intval($stats['approved_count']) ?></h4>
                        <small class="text-success fw-semibold fs-8">This term</small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card bg-body-tertiary border border-light-subtle shadow-sm position-relative h-100 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-danger fs-4">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Rejected</h6>
                        <h4 class="mb-0 fw-bold text-body"><?= intval($stats['rejected_count']) ?></h4>
                        <small class="text-danger fw-semibold fs-8">This term</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm h-100 w-100 rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-chart-line me-2"></i>Leave Application Trends</h6>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; width: 100%; height: 280px; min-height: 280px;">
                        <canvas id="leaveTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm h-100 rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-chart-pie me-2"></i>Leave Categories Breakdown</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                    <div style="position: relative; width: 100%; height: 200px; max-height: 200px; display: flex; justify-content: center;">
                        <canvas id="leaveDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Left Box: Unique Faculty List with View Button -->
        <div class="col-12 col-md-6 col-xl-5">
            <div class="card bg-body-tertiary text-body border border-light-subtle h-100 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-id-card-clip me-2"></i>Faculty Leave Accounts (<?= count($facultyLeaveGroups) ?>)</h6>
                </div>
                <div class="card-body d-flex flex-column gap-3 overflow-auto" style="max-height: 440px;">
                    <?php if (empty($facultyLeaveGroups)): ?>
                        <p class="text-body-secondary text-center small my-4">No leave applications found for this department scope.</p>
                    <?php else: ?>
                        <?php foreach ($facultyLeaveGroups as $fac): 
                            $initials = strtoupper(substr($fac['first_name'] ?? 'U', 0, 1) . substr($fac['last_name'] ?? 'N', 0, 1));
                            $fullName = htmlspecialchars(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
                            $requestCount = count($fac['requests']);
                        ?>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-sm-between border-bottom border-light-subtle pb-3 gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-semibold fs-7 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;"><?= $initials ?></div>
                                    <div>
                                        <h6 class="mb-0 fw-bold fs-7 text-body"><?= $fullName ?></h6>
                                        <small class="text-body-secondary fs-8">Total Requests: <strong><?= $requestCount ?></strong></small>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fs-8 fw-semibold rounded-pill view-faculty-btn" data-faculty-id="<?= intval($fac['faculty_id']) ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Box: Status Record Log Details Viewer (All Requests for Selected Faculty) -->
        <div class="col-12 col-md-6 col-xl-7">
            <div class="card bg-body-tertiary text-body border border-light-subtle h-100 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-tasks me-2"></i>Status Record Log Details</h6>
                </div>
                <div class="card-body d-flex flex-column gap-3 overflow-auto p-3" style="max-height: 440px;" id="statusRecordDetailsContainer">
                    <p class="text-body-secondary text-center small my-auto">Select a faculty member from the left and click <strong>View</strong> to inspect all their leave requests here.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Embedded Leave Data and Chart Data for Client-Side Scripts -->
<script>
const facultyLeaveGroupsData = <?= json_encode(array_values($facultyLeaveGroups)); ?>;
const chartLabels = <?= json_encode(array_keys($trendCounts)); ?>;
const chartValues = <?= json_encode(array_values($trendCounts)); ?>;
const categoryLabels = <?= json_encode(array_keys($categoryCounts)); ?>;
const categoryValues = <?= json_encode(array_values($categoryCounts)); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const gridColor = 'rgba(128, 128, 128, 0.15)';
    const textColor = getComputedStyle(document.body).getPropertyValue('--bs-body-color') || '#6c757d';

    // Functional Trends Line Chart
    const ctxTrends = document.getElementById('leaveTrendsChart').getContext('2d');
    new Chart(ctxTrends, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Applications Received',
                data: chartValues,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 }, precision: 0 }, beginAtZero: true }
            }
        }
    });

    // Functional Categories Doughnut Chart
    const ctxDist = document.getElementById('leaveDistributionChart').getContext('2d');
    new Chart(ctxDist, {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues,
                backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#6c757d'],
                borderWidth: 0,
                weight: 0.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    display: true,
                    position: 'bottom',
                    labels: { color: textColor, font: { size: 11 } }
                } 
            },
            cutout: '70%'
        }
    });

    // View button click event handler to populate right box with all requests of the selected faculty
    const viewButtons = document.querySelectorAll('.view-faculty-btn');
    const detailsContainer = document.getElementById('statusRecordDetailsContainer');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const facultyId = this.getAttribute('data-faculty-id');
            const facultyGroup = facultyLeaveGroupsData.find(item => String(item.faculty_id) === String(facultyId));

            if (!facultyGroup || !facultyGroup.requests || facultyGroup.requests.length === 0) {
                detailsContainer.innerHTML = `<p class="text-danger text-center small my-auto">No leave records found for this faculty member.</p>`;
                return;
            }

            const initials = (facultyGroup.first_name ? facultyGroup.first_name.charAt(0) : 'U').toUpperCase() + (facultyGroup.last_name ? facultyGroup.last_name.charAt(0) : 'N').toUpperCase();
            const fullName = `${facultyGroup.first_name || ''} ${facultyGroup.last_name || ''}`;
            const department = facultyGroup.department || 'Department';

            let requestsHtml = '';
            facultyGroup.requests.forEach(app => {
                const status = app.status || 'Pending';
                let badgeClass = 'bg-secondary text-white';
                if (status === 'Approved') badgeClass = 'bg-success text-white';
                else if (status === 'Rejected') badgeClass = 'bg-danger text-white';
                else if (status === 'Document Required') badgeClass = 'bg-warning text-dark';
                else if (status === 'Pending') badgeClass = 'bg-warning text-dark';

                requestsHtml += `
                    <div class="p-3 rounded-3 border border-light-subtle bg-body shadow-sm mb-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-semibold fs-8 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">${initials}</div>
                                <div>
                                    <h6 class="mb-0 fw-bold fs-7 text-body">${fullName}</h6>
                                    <small class="text-body-secondary fs-8">${department} Faculty</small>
                                </div>
                            </div>
                            <div>
                                <span class="badge ${badgeClass} px-2.5 py-1 rounded-pill fw-semibold fs-8 shadow-sm">${status}</span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-body-secondary mb-1 fw-bold fs-8">Reason for Leave</label>
                            <div class="p-2 border rounded-3 bg-body-tertiary text-body border-light-subtle fw-medium fs-8">${app.reason || 'No reason specified'}</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <label class="form-label text-body-secondary mb-1 fw-bold fs-8">From</label>
                                <div class="p-2 border rounded-3 bg-body-tertiary text-body border-light-subtle fw-medium fs-8">${app.start_date || 'N/A'}</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label text-body-secondary mb-1 fw-bold fs-8">To</label>
                                <div class="p-2 border rounded-3 bg-body-tertiary text-body border-light-subtle fw-medium fs-8">${app.end_date || 'N/A'}</li></div>
                            </div>
                        </div>
                    </div>
                `;
            });

            detailsContainer.innerHTML = `
                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom border-light-subtle">
                    <div>
                        <h6 class="fw-bold mb-0 text-body">${fullName}</h6>
                        <small class="text-body-secondary">${department} Faculty &bull; ${facultyGroup.requests.length} Request(s)</small>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${requestsHtml}
                </div>
            `;
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>