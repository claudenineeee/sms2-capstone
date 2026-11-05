<?php
/**
 * SMS 2 - Dean Leave Application & Approval
 * Module: Faculty Management
 *
 * Shows leave applications across ALL departments (no department filter).
 */
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/authentication.php';

requireAuth();

$pdo = null;
$actionMessage = ''; $actionType = '';

try {
    // Read HostForge env vars, with XAMPP fallbacks.
    $dbHost = getenv('DB_HOST')     ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
    $dbUser = getenv('DB_USERNAME') ?: (defined('DB_USER') ? DB_USER : 'root');
    $dbPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (defined('DB_PASS') ? DB_PASS : '');
    $dbName = getenv('DB_DATABASE') ?: (defined('DB_NAME') ? DB_NAME : 'faculty_db');
    $dbPort = (int) (getenv('DB_PORT') ?: 3306);

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
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

/* ------------------------------------------------------------------
 | Fetch stats & leave applications across ALL departments
 * ------------------------------------------------------------------ */
$totalFaculty = 0;
$stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'total_requests' => 0];
$leaveApplications = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. Total faculty across all departments
        $stmtFac = $pdo->query("SELECT COUNT(*) FROM faculty");
        $totalFaculty = (int) $stmtFac->fetchColumn();

        // 2. Leave request stats across all departments
        $stmtStats = $pdo->query("
            SELECT
                SUM(CASE WHEN l.status = 'Pending'  THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN l.status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN l.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
                COUNT(*) AS total_requests
            FROM leave_requests l
        ");
        $statsRow = $stmtStats->fetch(PDO::FETCH_ASSOC);
        if ($statsRow) {
            $stats = array_merge($stats, array_map('intval', $statsRow));
        }

        // 3. All leave applications across all departments
        $stmtLeaves = $pdo->query("
            SELECT l.*, f.first_name, f.last_name,
                   COALESCE(d.code, d.name, 'N/A') AS department
            FROM leave_requests l
            JOIN faculty f ON l.faculty_id = f.faculty_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            ORDER BY l.created_at DESC
        ");
        $leaveApplications = $stmtLeaves->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $actionMessage = "Database query error: " . $e->getMessage();
        $actionType = 'danger';
    }
}

/* ------------------------------------------------------------------
 | Group by faculty & build chart data
 * ------------------------------------------------------------------ */
$facultyLeaveGroups = [];
$trendCounts = [];      // key: 'Y-m'  → count
$categoryCounts = [];   // key: leave type → count

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

    // Trend: group by month (chronological, no ambiguity)
    $dateSource = !empty($app['created_at']) ? $app['created_at']
                : (!empty($app['start_date']) ? $app['start_date'] : null);

    if ($dateSource) {
        $monthKey = date('Y-m', strtotime($dateSource));
        $trendCounts[$monthKey] = ($trendCounts[$monthKey] ?? 0) + 1;
    }

    // Category breakdown
    $category = !empty($app['leave_type']) ? trim($app['leave_type'])
              : (!empty($app['category']) ? trim($app['category']) : 'Other');
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
}

// Sort trend by month key ascending
ksort($trendCounts);

// Build pretty labels for the chart (e.g., "Sep 2026")
$trendLabels = [];
$trendValues = [];
foreach ($trendCounts as $monthKey => $count) {
    $trendLabels[] = date('M Y', strtotime($monthKey . '-01'));
    $trendValues[] = $count;
}

// Fallbacks when empty
if (empty($trendLabels)) {
    $trendLabels = ['No Data'];
    $trendValues = [0];
}
if (empty($categoryCounts)) {
    $categoryCounts = ['No Data' => 1];
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-envelope-open-text text-primary"></i>
                <span>Leave Application &amp; Approval</span>
            </h1>
            <p class="text-body-secondary small mb-0">Monitor and review faculty leave status requests across all departments.</p>
        </div>
    </div>

    <!-- ==================== Metric Summary Cards ==================== -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Faculty</h6>
                        <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= (int) $totalFaculty ?></h4>
                        <small class="fw-semibold" style="color: #0d6efd; font-size: 0.75rem;">All departments</small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Faculty">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #f59e0b; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Requests</h6>
                        <h4 class="mb-0 fw-bold" style="color: #f59e0b;"><?= (int) $stats['pending_count'] ?></h4>
                        <small class="fw-semibold" style="color: #f59e0b; font-size: 0.75rem;">Awaiting action</small>
                    </div>
                </div>
                <a href="#statusRecordDetailsContainer" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Pending">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #10b981; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Approved</h6>
                        <h4 class="mb-0 fw-bold" style="color: #10b981;"><?= (int) $stats['approved_count'] ?></h4>
                        <small class="fw-semibold" style="color: #10b981; font-size: 0.75rem;">Successfully processed</small>
                    </div>
                </div>
                <a href="#statusRecordDetailsContainer" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Approved">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ff4d4d; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #ff4d4d;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rejected</h6>
                        <h4 class="mb-0 fw-bold" style="color: #ff4d4d;"><?= (int) $stats['rejected_count'] ?></h4>
                        <small class="fw-semibold" style="color: #ff4d4d; font-size: 0.75rem;">Declined applications</small>
                    </div>
                </div>
                <a href="#statusRecordDetailsContainer" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Rejected">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card bg-body-tertiary text-body border border-light-subtle shadow-sm h-100 w-100 rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-chart-line me-2"></i>Leave Application Trends</h6>
                    <small class="text-body-secondary fs-8">Monthly totals across all departments</small>
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
        <div class="col-12 col-md-6 col-xl-5">
            <div class="card bg-body-tertiary text-body border border-light-subtle h-100 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-primary fw-bold fs-6"><i class="fas fa-id-card-clip me-2"></i>Faculty Leave Accounts (<?= count($facultyLeaveGroups) ?>)</h6>
                </div>
                <div class="card-body d-flex flex-column gap-3 overflow-auto" style="max-height: 440px;">
                    <?php if (empty($facultyLeaveGroups)): ?>
                        <p class="text-body-secondary text-center small my-4">No leave applications found.</p>
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
                                        <small class="text-body-secondary fs-8">
                                            <?= htmlspecialchars($fac['department'] ?? 'N/A') ?> &bull; Total Requests: <strong><?= $requestCount ?></strong>
                                        </small>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fs-8 fw-semibold rounded-pill view-faculty-btn" data-faculty-id="<?= (int) $fac['faculty_id'] ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

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

<!-- Toast container (success / error / info) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="actionToast" class="toast align-items-center border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center p-2">
            <div class="me-2 fs-5 px-1">
                <i id="actionToastIcon" class="fas fa-info-circle text-primary"></i>
            </div>
            <div class="toast-body fs-7 fw-semibold p-1" id="actionToastMessage"></div>
            <button type="button" class="btn-close ms-auto me-1 shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Embedded data for Chart.js & interactions -->
<script>
const facultyLeaveGroupsData = <?= json_encode(array_values($facultyLeaveGroups)); ?>;
const chartLabels   = <?= json_encode($trendLabels); ?>;
const chartValues   = <?= json_encode($trendValues); ?>;
const categoryLabels = <?= json_encode(array_keys($categoryCounts)); ?>;
const categoryValues = <?= json_encode(array_values($categoryCounts)); ?>;

/* ---------- Toast helper ---------- */
function showActionToast(message, type) {
    if (!message) return;

    const toastEl    = document.getElementById('actionToast');
    const iconEl     = document.getElementById('actionToastIcon');
    const messageEl  = document.getElementById('actionToastMessage');
    const colorClass = type === 'danger' ? 'text-danger'
                     : type === 'warning' ? 'text-warning'
                     : type === 'success' ? 'text-success'
                     : 'text-primary';

    toastEl.className = 'toast align-items-center border-0 shadow-lg rounded-4';
    if (type === 'danger')  toastEl.classList.add('border', 'border-danger');
    if (type === 'warning') toastEl.classList.add('border', 'border-warning');
    if (type === 'success') toastEl.classList.add('border', 'border-success');

    iconEl.className = 'fas fa-info-circle ' + colorClass;
    if (type === 'danger')  iconEl.className = 'fas fa-times-circle '       + colorClass;
    if (type === 'warning') iconEl.className = 'fas fa-exclamation-triangle ' + colorClass;
    if (type === 'success') iconEl.className = 'fas fa-check-circle '       + colorClass;

    messageEl.textContent = message;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 6000 }).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const gridColor = 'rgba(128, 128, 128, 0.15)';
    const textColor = getComputedStyle(document.body).getPropertyValue('--bs-body-color') || '#6c757d';

    // ---- Leave Trends Line Chart ----
    const trendsCanvas = document.getElementById('leaveTrendsChart');
    if (trendsCanvas) {
        new Chart(trendsCanvas.getContext('2d'), {
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
    }

    // ---- Categories Doughnut Chart ----
    const distCanvas = document.getElementById('leaveDistributionChart');
    if (distCanvas) {
        new Chart(distCanvas.getContext('2d'), {
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
    }

    // ---- View button handler ----
    const detailsContainer = document.getElementById('statusRecordDetailsContainer');

    document.querySelectorAll('.view-faculty-btn').forEach(button => {
        button.addEventListener('click', function() {
            const facultyId = this.getAttribute('data-faculty-id');
            const facultyGroup = facultyLeaveGroupsData.find(item => String(item.faculty_id) === String(facultyId));

            if (!facultyGroup || !facultyGroup.requests || facultyGroup.requests.length === 0) {
                detailsContainer.innerHTML = `<p class="text-danger text-center small my-auto">No leave records found for this faculty member.</p>`;
                return;
            }

            const initials = (facultyGroup.first_name ? facultyGroup.first_name.charAt(0) : 'U').toUpperCase()
                           + (facultyGroup.last_name  ? facultyGroup.last_name.charAt(0)  : 'N').toUpperCase();
            const fullName   = `${facultyGroup.first_name || ''} ${facultyGroup.last_name || ''}`;
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
                                <div class="p-2 border rounded-3 bg-body-tertiary text-body border-light-subtle fw-medium fs-8">${app.end_date || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                `;
            });

            detailsContainer.innerHTML = `
                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom border-light-subtle">
                    <div>
                        <h6 class="fw-bold mb-0 text-body">${fullName}</h6>
                        <small class="text-body-secondary">${department} &bull; ${facultyGroup.requests.length} Request(s)</small>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${requestsHtml}
                </div>
            `;
        });
    });

    // ---- Show the PHP-driven message as a toast (works for success AND error) ----
    const phpMessage = <?= json_encode($actionMessage) ?>;
    const phpType    = <?= json_encode($actionType ?: 'success') ?>;
    if (phpMessage) {
        showActionToast(phpMessage, phpType);
    }
});
</script>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>