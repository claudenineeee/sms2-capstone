<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

// ==========================================
// 1. FORCE MANUAL DATABASE CONNECTIONS
// ==========================================
$corePdo = null;
$facultyPdo = null;

if (function_exists('db')) $corePdo = db();
if (function_exists('facultyDb')) $facultyPdo = facultyDb();

if (!$corePdo || !$facultyPdo) {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = ''; 

    try {
        if (!$corePdo) $corePdo = new PDO("mysql:host=$host;dbname=sms2_db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if (!$facultyPdo) $facultyPdo = new PDO("mysql:host=$host;dbname=faculty_db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        die("<div style='padding:20px; color:red;'><strong>Database connection failed.</strong> Error: " . $e->getMessage() . "</div>");
    }
}

$pageTitle    = 'Department Head Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Dashboard',    'url' => null],
];

// ==========================================
// 2. FETCH REAL DATA FROM DATABASE
// ==========================================
$currentUserId = getCurrentUserId();
$currentUserEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
$deptCode = 'BSIT'; 
$deptHeadProfileId = 0;

// Get Department Head's Department and Profile ID
try {
    $stmt = $facultyPdo->prepare("SELECT id, designated_department FROM faculty_profiles WHERE user_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$currentUserId, $currentUserEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $deptHeadProfileId = (int) $row['id'];
        if (!empty($row['designated_department'])) {
            $deptCode = strtoupper(trim($row['designated_department']));
        }
    }
} catch (Throwable $e) {}

// A. Fetch ALL members in the department except the Dept Head
$teachers = [];
try {
    $stmt = $facultyPdo->prepare("
        SELECT * FROM faculty_profiles 
        WHERE LOWER(TRIM(designated_department)) = LOWER(?) 
          AND id != ?
    ");
    $stmt->execute([$deptCode, $deptHeadProfileId]);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$totalFaculty = count($teachers);

// B. Fetch Evaluations
$evaluatedCount = 0;
$evalScores = [];
foreach ($teachers as $t) {
    $stmt = $facultyPdo->prepare("SELECT composite_score FROM evaluations WHERE faculty_id = ? AND source_type = 'DeptHead'");
    $stmt->execute([$t['faculty_id']]);
    $score = $stmt->fetchColumn();
    if ($score !== false) {
        $evaluatedCount++;
        $evalScores[] = (float) $score;
    }
}
$avgRating = count($evalScores) > 0 ? array_sum($evalScores) / count($evalScores) : 0;

// C. Clearance Status
$clearedCount = 0;
try {
    $stmt = $facultyPdo->prepare("
        SELECT COUNT(*) FROM faculty_clearance_archives a
        INNER JOIN faculty_profiles fp ON a.faculty_id = fp.id
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(?)
          AND fp.id != ?
    ");
    $stmt->execute([$deptCode, $deptHeadProfileId]);
    $clearedCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) {}

// D. Attendance Rate
$attendanceRate = 0;
try {
    $stmt = $facultyPdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present
        FROM attendance_records ar
        INNER JOIN faculty_profiles fp ON ar.faculty_id = fp.faculty_id
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(?)
          AND fp.id != ?
    ");
    $stmt->execute([$deptCode, $deptHeadProfileId]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($att && $att['total'] > 0) {
        $attendanceRate = ($att['present'] / $att['total']) * 100;
    }
} catch (Throwable $e) {}

// E. Leave Requests
$pendingLeaves = 0;
$leaveRequests = [];
try {
    $stmt = $facultyPdo->prepare("
        SELECT lr.*, fp.first_name, fp.last_name 
        FROM leave_requests lr
        INNER JOIN faculty_profiles fp ON lr.faculty_id = fp.faculty_id
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(?)
          AND fp.id != ?
          AND lr.approval_status = 'Pending'
        ORDER BY lr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$deptCode, $deptHeadProfileId]);
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pendingLeaves = count($leaveRequests);
} catch (Throwable $e) {}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-chart-pie text-primary me-2"></i>Department Head Dashboard
        </h1>
        <p class="text-muted mb-0 small">Overview of faculty performance and attendance for the current term.</p>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
            <i class="fas fa-building me-1"></i> Department: <?= htmlspecialchars($deptCode) ?>
        </span>
    </div>
</div>

<!-- Top Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center p-3 ps-4">
                <div class="stat-icon me-3 text-primary fs-2"><i class="fas fa-users"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Members</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($totalFaculty) ?></h3>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>Active <span class="text-muted fw-normal">members</span></small>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #198754; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center p-3 ps-4">
                <div class="stat-icon me-3 text-success fs-2"><i class="fas fa-star"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Avg Rating</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($avgRating, 2) ?></h3>
                    <small class="text-muted fw-normal" style="font-size: 0.75rem;">Current term average</small>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center p-3 ps-4">
                <div class="stat-icon me-3 text-warning fs-2"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Cleared</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($clearedCount) ?></h3>
                    <small class="text-muted fw-normal" style="font-size: 0.75rem;">Members cleared</small>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0dcaf0; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center p-3 ps-4">
                <div class="stat-icon me-3 text-info fs-2"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Attendance Rate</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($attendanceRate, 1) ?>%</h3>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-check-circle me-1"></i>On track</small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Middle Row: Charts & Leave Requests -->
<div class="row g-3 mb-4">
    <!-- Donut Chart: Evaluation Progress (4 cols) -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body">Evaluation Progress</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                <div style="position: relative; height: 200px; width: 100%;">
                    <canvas id="evaluationProgressChart"></canvas>
                </div>
                <div class="mt-3 text-center">
                    <h5 class="mb-0 fw-bold"><?= $evaluatedCount ?> / <?= $totalFaculty ?></h5>
                    <small class="text-muted">Members Evaluated</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Chart: Clearance Status (4 cols) -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body">Clearance Submission Status</h6>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="clearanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Leave Requests (4 cols) -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-body"><i class="fas fa-calendar-times text-danger me-2"></i>Pending Leave Requests</h6>
                <span class="badge bg-danger rounded-pill"><?= $pendingLeaves ?></span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush small" style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($leaveRequests)): ?>
                        <div class="text-muted text-center py-4">
                            <i class="fas fa-inbox fs-4 d-block mb-2 text-secondary"></i>
                            No pending leave requests.
                        </div>
                    <?php else: ?>
                        <?php foreach ($leaveRequests as $lr): ?>
                            <div class="list-group-item bg-transparent py-3 border-bottom border-secondary-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold"><?= htmlspecialchars($lr['first_name'] . ' ' . $lr['last_name']) ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= date('M d', strtotime($lr['created_at'])) ?></span>
                                </div>
                                <div class="text-muted"><?= htmlspecialchars($lr['leave_type']) ?> • <?= $lr['total_days'] ?> days</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-body-tertiary border-top py-2 text-center mt-auto">
                <a href="<?= BASE_URL ?>/modules/faculty/views/department-head/leave-request-approval.php" class="text-decoration-none small fw-bold">View All Requests <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Faculty Roster & Completion -->
<div class="row g-3 mb-4">
    <!-- Main Table: Department Faculty Roster (8 cols) -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body">Department Faculty Roster</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap" id="facultyRosterTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-3" style="font-size: 1rem;">Faculty Name</th>
                                <th class="text-center py-3" style="font-size: 1rem;">Position</th>
                                <th class="text-center py-3" style="font-size: 1rem;">Employment</th>
                                <th class="pe-3 py-3" style="font-size: 1rem;">Clearance</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6" id="rosterTableBody">
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No faculty members found in this department.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $index => $t): ?>
                                    <?php
                                        // We keep the evaluation check for the chart stats above, 
                                        // but we no longer render it in the table.
                                        $stmtClear = $facultyPdo->prepare("SELECT COUNT(*) FROM faculty_clearance_archives WHERE faculty_id = (SELECT id FROM faculty_profiles WHERE faculty_id = ? LIMIT 1)");
                                        $stmtClear->execute([$t['faculty_id']]);
                                        $isCleared = $stmtClear->fetchColumn() > 0;
                                    ?>
                                    <tr class="faculty-row" data-index="<?= $index ?>">
                                        <td class="ps-3 fw-bold py-3"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                        <td class="text-center text-capitalize py-3"><?= htmlspecialchars($t['position']) ?></td>
                                        <td class="text-center text-capitalize py-3"><?= htmlspecialchars($t['employment_status']) ?></td>
                                        <td class="pe-3 py-3">
                                            <?php if ($isCleared): ?>
                                                <span class="text-success fw-bold" style="font-size: 0.9rem;"><i class="fas fa-check-circle"></i> Cleared</span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.9rem;"><i class="fas fa-hourglass-half"></i> In Progress</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination Footer -->
            <?php if (count($teachers) > 0): ?>
            <div class="card-footer bg-body-tertiary border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted" id="rosterInfo">Showing 0 to 0 of 0 entries</small>
                <nav aria-label="Faculty roster pagination">
                    <ul class="pagination pagination-sm mb-0" id="rosterPagination"></ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Side Panels: Top Rated & Completion (4 cols) -->
    <div class="col-12 col-xl-4">
        <!-- Top Performing Faculty -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body"><i class="fas fa-trophy text-warning me-2"></i>Top Rated Faculty</h6>
            </div>
            <div class="card-body p-3">
                <?php 
                    if (count($evalScores) > 0) {
                        rsort($evalScores);
                        $top3 = array_slice($evalScores, 0, 3);
                        foreach ($top3 as $index => $score) {
                            $badgeColor = ['bg-warning text-dark', 'bg-secondary text-light', 'bg-danger text-light'][$index];
                            echo '<div class="d-flex align-items-center justify-content-between mb-3">';
                            echo '<div class="d-flex align-items-center gap-2">';
                            echo '<span class="badge '.$badgeColor.' rounded-circle p-2">'.($index + 1).'</span>';
                            echo '<span class="fw-semibold small">Faculty Member</span>';
                            echo '</div>';
                            echo '<span class="text-success fw-bold small">'.number_format($score, 2).'</span>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="text-muted small text-center py-3">No evaluations submitted yet.</div>';
                    }
                ?>
            </div>
        </div>

        <!-- Evaluation Completion Rate Chart -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body">Evaluation Completion</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center p-3">
                <div style="position: relative; height: 150px; width: 100%;" class="mb-3">
                    <canvas id="completionChart"></canvas>
                </div>
                <div class="w-100 small text-center">
                    <h5 class="mb-0 fw-bold text-primary"><?= number_format($totalFaculty > 0 ? ($evaluatedCount / $totalFaculty) * 100 : 0, 1) ?>%</h5>
                    <small class="text-muted">Overall Completion</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. ROSTER PAGINATION LOGIC
    // ==========================================
    const rowsPerPage = 10;
    let currentPage = 1;
    
    const tableBody = document.getElementById('rosterTableBody');
    const rows = Array.from(tableBody.querySelectorAll('.faculty-row'));
    const paginationControls = document.getElementById('rosterPagination');
    const paginationInfo = document.getElementById('rosterInfo');

    function renderPagination() {
        const totalRows = rows.length;
        if (totalRows === 0) return;

        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        // Hide all rows first
        rows.forEach(row => row.classList.add('d-none'));

        // Show only current page rows
        const start = (currentPage - 1) * rowsPerPage;
        const end = Math.min(start + rowsPerPage, totalRows);
        rows.slice(start, end).forEach(row => row.classList.remove('d-none'));

        // Update text info
        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${start + 1} to ${end} of ${totalRows} entries`;
        }

        // Render buttons
        if (!paginationControls) return;
        paginationControls.innerHTML = '';
        if (totalPages <= 1) return;

        // Prev Button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
        prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { currentPage--; renderPagination(); } });
        paginationControls.appendChild(prevLi);

        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener('click', (e) => { e.preventDefault(); currentPage = i; renderPagination(); });
            paginationControls.appendChild(li);
        }

        // Next Button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
        nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { currentPage++; renderPagination(); } });
        paginationControls.appendChild(nextLi);
    }

    if (rows.length > 0) {
        renderPagination();
    }

    // ==========================================
    // 2. CHARTS LOGIC
    // ==========================================
    // Evaluation Progress Chart (Donut)
    const ctxEval = document.getElementById('evaluationProgressChart');
    if (ctxEval) {
        new Chart(ctxEval, {
            type: 'doughnut',
            data: {
                labels: ['Evaluated', 'Pending'],
                datasets: [{
                    data: [<?= $evaluatedCount ?>, <?= max(0, $totalFaculty - $evaluatedCount) ?>],
                    backgroundColor: ['#198754', '#e9ecef'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // Clearance Status Bar Chart
    const ctxClearance = document.getElementById('clearanceChart');
    if (ctxClearance) {
        new Chart(ctxClearance, {
            type: 'bar',
            data: {
                labels: ['Cleared', 'In Progress', 'Missing'],
                datasets: [{
                    label: 'Faculty Count',
                    data: [<?= $clearedCount ?>, <?= max(0, $totalFaculty - $clearedCount) ?>, 0], 
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderRadius: 4,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // Evaluation Completion Rate Chart (Half Donut)
    const ctxCompletion = document.getElementById('completionChart');
    if (ctxCompletion) {
        let completionRate = <?= $totalFaculty > 0 ? ($evaluatedCount / $totalFaculty) * 100 : 0 ?>;
        new Chart(ctxCompletion, {
            type: 'doughnut',
            data: {
                labels: ['Done', 'Pending'],
                datasets: [{
                    data: [completionRate, 100 - completionRate],
                    backgroundColor: ['#0d6efd', '#e9ecef'],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: 270
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>