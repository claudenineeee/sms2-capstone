<?php
/**
 * Attendance Reports & Analytics
 * Purpose: Search faculty and view attendance logs across customizable time periods.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

// =====================================================================
// NEW LOGIC: Fetch Department Head's Dept and Attendance Data
// =====================================================================
require_once __DIR__ . '/../../models/AttendanceModel.php';
$attendanceModel = new AttendanceModel(db());

// 1. Identify Department Head's Department Name
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$deptHeadDept  = null;

if ($currentUserId) {
    try {
        $stmt = db()->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = :uid OR id = :id LIMIT 1");
        $stmt->execute(['uid' => $currentUserId, 'id' => $currentUserId]);
        $row = $stmt->fetch();
        if ($row) {
            $deptHeadDept = trim($row['designated_department'] ?? '');
        }
    } catch (Throwable $e) {
        $deptHeadDept = null;
    }
}

if (empty($deptHeadDept)) {
    $deptHeadDept = trim($_SESSION['department'] ?? $_SESSION['designated_department'] ?? '');
}

// CHANGED: removed the dead $deptId lookup that used to sit here. It
// queried departments to resolve a numeric department_id, but
// that variable was never actually used anywhere on this page — every
// query below works off faculty IDs, not department_id.

// Filter parameters
$selectedPeriod = $_GET['period'] ?? '7days';
$selectedMonth  = $_GET['month'] ?? date('Y-m');

// 3. Fetch Faculty in this department
// CHANGED: was getFacultyByDepartment($deptHeadDept ?? '1'). The ?? operator
// only catches NULL, not an empty string — so if the department lookup above
// came back blank, this passed '' and silently matched zero faculty, making
// the whole page render as zeros. Now falls back properly on empty too.
$facultyInDept = $attendanceModel->getFacultyByDepartment(
    $deptHeadDept !== '' ? $deptHeadDept : '1'
) ?? [];

// Date boundaries
$today      = date('Y-m-d');
$weekStart  = date('Y-m-d', strtotime('-7 days'));
$monthStart = date('Y-m-01');

// CHANGED: this whole section used to call getSessionsForFaculty() four
// separate times per faculty member (today / week / month / selected
// period). With ~29 faculty that was 116 database round trips on every
// page load. Now it pulls the widest range needed ONCE via the new
// batched getSessionsForFacultyIds(), then buckets the rows in PHP.
$facultyIds = array_column($facultyInDept, 'id');

// Widest window we need: earliest of month-start / week-start, through
// the end of the current month (the 'monthly' filter looks ahead to Y-m-t).
$fetchStart = min($monthStart, $weekStart);
$fetchEnd   = max($today, date('Y-m-t'));

$allSessions = $attendanceModel->getSessionsForFacultyIds($facultyIds, $fetchStart, $fetchEnd) ?? [];

// Group sessions by faculty id for quick lookup below.
$sessionsByFaculty = [];
foreach ($allSessions as $s) {
    $sessionsByFaculty[(string) $s['faculty_id']][] = $s;
}

// 4. Build Summary Metrics
$summaryMetrics = [
    'today_present'   => 0,
    'today_total'     => 0,
    'today_percentage'=> 0,
    'weekly_present'  => 0,
    'weekly_total'    => 0,
    'weekly_percentage'=> 0,
    'monthly_present' => 0,
    'monthly_total'   => 0,
    'monthly_percentage'=> 0
];

// CHANGED: 'Late' now counts toward the present tallies. A late professor
// still showed up and still taught, so counting them as not-present made
// the department look worse than it was, and was inconsistent with the
// monitoring officer's own dashboard, which already counts Late as present.
$presentStatuses = ['Present', 'Late'];

foreach ($allSessions as $log) {
    $d = $log['session_date'];
    $isPresent = in_array($log['status'], $presentStatuses, true);

    if ($d === $today) {
        $summaryMetrics['today_total']++;
        if ($isPresent) $summaryMetrics['today_present']++;
    }
    if ($d >= $weekStart && $d <= $today) {
        $summaryMetrics['weekly_total']++;
        if ($isPresent) $summaryMetrics['weekly_present']++;
    }
    if ($d >= $monthStart && $d <= $today) {
        $summaryMetrics['monthly_total']++;
        if ($isPresent) $summaryMetrics['monthly_present']++;
    }
}

// Calculate Percentages
$summaryMetrics['today_percentage'] = $summaryMetrics['today_total'] > 0 ? ($summaryMetrics['today_present'] / $summaryMetrics['today_total']) * 100 : 0;
$summaryMetrics['weekly_percentage'] = $summaryMetrics['weekly_total'] > 0 ? ($summaryMetrics['weekly_present'] / $summaryMetrics['weekly_total']) * 100 : 0;
$summaryMetrics['monthly_percentage'] = $summaryMetrics['monthly_total'] > 0 ? ($summaryMetrics['monthly_present'] / $summaryMetrics['monthly_total']) * 100 : 0;

// 5. Build Faculty Summaries for the table
$facultySummaries = [];

// Determine Date Range based on selected period
$dateRangeStart = $today;
$dateRangeEnd   = $today;

if ($selectedPeriod === '7days') {
    $dateRangeStart = $weekStart;
} elseif ($selectedPeriod === 'monthly') {
    $dateRangeStart = $monthStart;
    $dateRangeEnd   = date('Y-m-t');
}

foreach ($facultyInDept as $fac) {
    $fullName = htmlspecialchars($fac['first_name'] . ' ' . $fac['last_name']);

    // CHANGED: reads from the pre-fetched $sessionsByFaculty bucket and
    // filters by date in PHP, instead of issuing another query per faculty.
    $logs = array_filter(
        $sessionsByFaculty[(string) $fac['id']] ?? [],
        function ($s) use ($dateRangeStart, $dateRangeEnd) {
            return $s['session_date'] >= $dateRangeStart && $s['session_date'] <= $dateRangeEnd;
        }
    );

    $present = 0;
    $absent  = 0;
    $late    = 0;

    foreach ($logs as $log) {
        if ($log['status'] === 'Present') {
            $present++;
        } elseif ($log['status'] === 'Absent') {
            $absent++;
        } elseif ($log['status'] === 'Late') {
            $late++;
        }
    }

    $total = count($logs);
    // CHANGED: Late counts toward the attendance rate here too (see above).
    $rate = $total > 0 ? (($present + $late) / $total) * 100 : 0;

    $facultySummaries[] = [
        'name' => $fullName,
        'total_classes' => $total,
        'present_count' => $present,
        'late_count' => $late,
        'absent_count' => $absent,
        'rate' => $rate
    ];
}

// =====================================================================
// PAGINATION LOGIC (Max 15 per page)
// =====================================================================
$perPage      = 15;
$totalFaculty = count($facultySummaries);
$totalPages   = max(1, ceil($totalFaculty / $perPage));
$page         = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset             = ($page - 1) * $perPage;
$paginatedSummaries = array_slice($facultySummaries, $offset, $perPage);

$pageTitle    = 'Attendance Reports & Analytics';
$activeModule = 'faculty';
$activePage   = 'attendance-summary';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Department Head',   'url' => BASE_URL . '/modules/faculty/users/department_head/dashboard.php'],
    ['label' => 'Attendance Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php'; 
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Header & Action Buttons -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-white">
            <i class="fas fa-clipboard-list text-primary me-2"></i>Attendance Reports & Analytics
        </h1>
        <p class="text-muted mb-0 small">
            Search faculty and view attendance logs across customizable time periods.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print
        </button>
        <a href="<?= BASE_URL ?>/modules/faculty/index.php?action=exportAttendanceReport&month=<?= urlencode($selectedMonth) ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Today's Rate Card -->
    <div class="col-12 col-md-4">
        <section class="card stat-card primary border shadow-sm position-relative h-100 bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Today's Rate</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= number_format($summaryMetrics['today_percentage'] ?? 0, 1) ?>%</h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        <?= $summaryMetrics['today_present'] ?> Present / <?= $summaryMetrics['today_total'] ?> Scheduled
                    </small>
                </div>
            </div>
        </section>
    </div>

    <!-- 7-Day Average Card -->
    <div class="col-12 col-md-4">
        <section class="card stat-card info border shadow-sm position-relative h-100 bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-info fs-4">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">7-Day Average</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= number_format($summaryMetrics['weekly_percentage'] ?? 0, 1) ?>%</h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        <?= $summaryMetrics['weekly_present'] ?> Present / <?= $summaryMetrics['weekly_total'] ?> Total Classes
                    </small>
                </div>
            </div>
        </section>
    </div>

    <!-- Monthly Attendance Card -->
    <div class="col-12 col-md-4">
        <section class="card stat-card success border shadow-sm position-relative h-100 bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-success fs-4">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Monthly Rate (<?= date('M Y', strtotime($selectedMonth)) ?>)</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= number_format($summaryMetrics['monthly_percentage'] ?? 0, 1) ?>%</h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        <?= $summaryMetrics['monthly_present'] ?> Present / <?= $summaryMetrics['monthly_total'] ?> Total Classes
                    </small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-body-secondary">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" <?= $selectedPeriod === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="7days" <?= $selectedPeriod === '7days' ? 'selected' : '' ?>>Past Week (7 Days)</option>
                    <option value="monthly" <?= $selectedPeriod === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-body-secondary">Select Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth) ?>">
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Faculty Attendance Breakdown Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body-tertiary py-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="mb-0 fw-bold text-body">
                Faculty Attendance Breakdown
            </h6>
            <span class="badge bg-secondary-subtle text-secondary border rounded-pill">
                <?= $totalFaculty ?> Faculty Total
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-2 py-md-3">Faculty Name</th>
                        <th class="text-center py-2 py-md-3">Classes</th>
                        <th class="text-center py-2 py-md-3">Present</th>
                        <th class="text-center py-2 py-md-3">Late</th>
                        <th class="text-center py-2 py-md-3">Absent</th>
                        <th class="pe-3 py-2 py-md-3" style="min-width: 160px;">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (!empty($paginatedSummaries)): ?>
                        <?php foreach ($paginatedSummaries as $row): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-body py-2 py-md-3">
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>
                                <td class="text-center py-2 py-md-3 text-body">
                                    <?= $row['total_classes'] ?>
                                </td>
                                <td class="text-center py-2 py-md-3">
                                    <span class="text-success fw-bold"><?= $row['present_count'] ?></span>
                                </td>
                                <td class="text-center py-2 py-md-3">
                                    <span class="text-warning fw-bold"><?= $row['late_count'] ?></span>
                                </td>
                                <td class="text-center py-2 py-md-3">
                                    <span class="text-danger fw-bold"><?= $row['absent_count'] ?></span>
                                </td>
                                <td class="pe-3 py-2 py-md-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; min-width: 80px;">
                                            <div class="progress-bar <?= $row['rate'] >= 85 ? 'bg-success' : ($row['rate'] >= 70 ? 'bg-warning' : 'bg-danger') ?>" 
                                                 style="width: <?= $row['rate'] ?>%"></div>
                                        </div>
                                        <span class="small fw-bold text-body"><?= number_format($row['rate'], 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4 py-md-5">
                                <i class="fas fa-inbox fs-4 d-block mb-2"></i>
                                <span class="small">No attendance logs found for this period.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalFaculty) ?> of <?= $totalFaculty ?> entries
            </div>
            <nav aria-label="Faculty breakdown pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Previous Page Link -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?period=<?= urlencode($selectedPeriod) ?>&month=<?= urlencode($selectedMonth) ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?period=<?= urlencode($selectedPeriod) ?>&month=<?= urlencode($selectedMonth) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Page Link -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?period=<?= urlencode($selectedPeriod) ?>&month=<?= urlencode($selectedMonth) ?>&page=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/../../../../includes/layout-end.php'; 
?>