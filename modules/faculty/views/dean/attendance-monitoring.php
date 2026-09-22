<?php
/**
 * Attendance Reports & Analytics
 * Purpose: Search faculty and view attendance logs across customizable time periods.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$pageTitle    = 'Attendance Reports & Analytics';
$activeModule = 'faculty';
$activePage   = 'attendance-summary';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    // CHANGED: was pointing to the department-head dashboard, copy-pasted
    // from that page — this is the Dean's page (views/dean/), so it should
    // point at the Dean's own dashboard instead.
    ['label' => 'Dean',              'url' => BASE_URL . '/modules/faculty/views/dean/index.php'],
    ['label' => 'Attendance Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php'; 

// Filter parameters
$selectedPeriod = $_GET['period'] ?? '7days';
$selectedMonth  = $_GET['month'] ?? date('Y-m');

// CHANGED: this whole block is new. $summaryMetrics and $facultySummaries
// were referenced everywhere below via `?? 0` / `?? []` fallbacks but never
// actually defined anywhere in this file — the page was silently rendering
// all zeros. Unlike the Department Head's attendance-summary.php (scoped to
// one department), the Dean sees EVERY department, so faculty come from
// FacultyController::getDirectoryList() (the same college-wide source used
// by daily-attendance-log.php and the Dean's own Faculty Directory) rather
// than a department-filtered query.
require_once __DIR__ . '/../../../../config/database.php';   // defines db()
require_once __DIR__ . '/../../controllers/faculty-data.php'; // defines facultyDb()
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../models/AttendanceModel.php';

$facultyController = new FacultyController();
$facultyListRaw = $facultyController->getDirectoryList();
// Same position filter used everywhere else this directory is consumed
// (daily-attendance-log.php, reports.php), so this page shows the same
// people who actually appear in the attendance workflow.
$facultyListRaw = array_filter($facultyListRaw, function ($member) {
    $position = strtolower(trim((string) ($member['position'] ?? '')));
    return $position === 'faculty professor' || $position === 'teacher' || $position === '';
});

$attendanceModel = new AttendanceModel(db());

$today          = date('Y-m-d');
$weekStart      = date('Y-m-d', strtotime('-7 days'));
// CHANGED: monthly figures now follow the actual $selectedMonth from the
// filter (the input already existed in the form below — it just was never
// wired to anything), instead of always meaning "this calendar month"
// regardless of what was picked.
$monthStart     = $selectedMonth . '-01';
$monthEnd       = date('Y-m-t', strtotime($monthStart));

$facultyIds = array_column($facultyListRaw, 'id');

// One batched query covering the widest range any of the three cards or
// the table could need, instead of querying per faculty member per range.
$fetchStart  = min($monthStart, $weekStart, $today);
$fetchEnd    = max($monthEnd, $today);
$allSessions = $attendanceModel->getSessionsForFacultyIds($facultyIds, $fetchStart, $fetchEnd) ?? [];

$sessionsByFaculty = [];
foreach ($allSessions as $s) {
    $sessionsByFaculty[(string) $s['faculty_id']][] = $s;
}

// A Late professor still showed up and taught — counts as present here,
// consistent with the monitoring officer's dashboard and the department
// head's attendance-summary.php.
$presentStatuses = ['Present', 'Late'];

$summaryMetrics = [
    'today_present'      => 0, 'today_total'      => 0, 'today_percentage'      => 0,
    'weekly_present'      => 0, 'weekly_total'      => 0, 'weekly_percentage'      => 0,
    'monthly_present'    => 0, 'monthly_total'    => 0, 'monthly_percentage'    => 0,
];

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
    if ($d >= $monthStart && $d <= $monthEnd) {
        $summaryMetrics['monthly_total']++;
        if ($isPresent) $summaryMetrics['monthly_present']++;
    }
}

$summaryMetrics['today_percentage']   = $summaryMetrics['today_total']   > 0 ? ($summaryMetrics['today_present']   / $summaryMetrics['today_total'])   * 100 : 0;
$summaryMetrics['weekly_percentage']  = $summaryMetrics['weekly_total']  > 0 ? ($summaryMetrics['weekly_present']  / $summaryMetrics['weekly_total'])  * 100 : 0;
$summaryMetrics['monthly_percentage'] = $summaryMetrics['monthly_total'] > 0 ? ($summaryMetrics['monthly_present'] / $summaryMetrics['monthly_total']) * 100 : 0;

// Table rows follow the selected Time Period filter (Today / 7 Days / Monthly).
$dateRangeStart = $today;
$dateRangeEnd   = $today;
if ($selectedPeriod === '7days') {
    $dateRangeStart = $weekStart;
} elseif ($selectedPeriod === 'monthly') {
    $dateRangeStart = $monthStart;
    $dateRangeEnd   = $monthEnd;
}

$facultySummaries = [];
foreach ($facultyListRaw as $fac) {
    $fullName = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));

    $logs = array_filter(
        $sessionsByFaculty[(string) $fac['id']] ?? [],
        function ($s) use ($dateRangeStart, $dateRangeEnd) {
            return $s['session_date'] >= $dateRangeStart && $s['session_date'] <= $dateRangeEnd;
        }
    );

    $present = 0; $late = 0; $absent = 0;
    foreach ($logs as $log) {
        if ($log['status'] === 'Present') $present++;
        elseif ($log['status'] === 'Late') $late++;
        elseif ($log['status'] === 'Absent') $absent++;
    }

    $total = count($logs);
    $rate  = $total > 0 ? (($present + $late) / $total) * 100 : 0;

    $facultySummaries[] = [
        'name'          => $fullName,
        'total_classes' => $total,
        'present_count' => $present,
        'late_count'    => $late,
        'absent_count'  => $absent,
        'rate'          => $rate,
    ];
}

// CHANGED: new — powers the "View Details" links on the three stat cards
// (previously href="javascript:void(0)", i.e. dead). Builds the actual list
// of sessions behind each card's number, with the faculty member's real
// name attached (the raw session rows only carry faculty_id).
$facultyNameById = [];
foreach ($facultyListRaw as $fac) {
    $facultyNameById[(string) $fac['id']] = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
}

if (!function_exists('buildAttendanceDetailRows')) {
    function buildAttendanceDetailRows(array $sessions, $rangeStart, $rangeEnd, array $facultyNameById) {
        $rows = [];
        foreach ($sessions as $s) {
            if ($s['session_date'] < $rangeStart || $s['session_date'] > $rangeEnd) {
                continue;
            }
            $rows[] = [
                'date'    => $s['session_date'],
                'faculty' => $facultyNameById[(string) $s['faculty_id']] ?? 'Unknown',
                'subject' => $s['subject_code'] ?? 'N/A',
                'room'    => $s['room_code'] ?? 'N/A',
                'status'  => $s['status'],
            ];
        }
        // Most recent first
        usort($rows, fn($a, $b) => strcmp($b['date'], $a['date']));
        return $rows;
    }
}

$todayDetailRows   = buildAttendanceDetailRows($allSessions, $today, $today, $facultyNameById);
$weeklyDetailRows  = buildAttendanceDetailRows($allSessions, $weekStart, $today, $facultyNameById);
$monthlyDetailRows = buildAttendanceDetailRows($allSessions, $monthStart, $monthEnd, $facultyNameById);
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
    <!-- Today's Rate Card (Primary) -->
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
                        <?= $summaryMetrics['today_present'] ?? 0 ?> Present / <?= $summaryMetrics['today_total'] ?? 0 ?> Scheduled
                    </small>
                </div>
            </div>
            <!-- CHANGED: was href="javascript:void(0)" — a dead link. Now opens
                 a modal listing the actual sessions behind today's numbers. -->
            <a href="#" data-bs-toggle="modal" data-bs-target="#todayDetailsModal" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- 7-Day Average Card (Info) -->
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
                        <?= $summaryMetrics['weekly_present'] ?? 0 ?> Present / <?= $summaryMetrics['weekly_total'] ?? 0 ?> Total Classes
                    </small>
                </div>
            </div>
            <a href="#" data-bs-toggle="modal" data-bs-target="#weeklyDetailsModal" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Monthly Attendance Card (Success) -->
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
                        <?= $summaryMetrics['monthly_present'] ?? 0 ?> Present / <?= $summaryMetrics['monthly_total'] ?? 0 ?> Total Classes
                    </small>
                </div>
            </div>
            <a href="#" data-bs-toggle="modal" data-bs-target="#monthlyDetailsModal" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<?php
// CHANGED: new — the three "View Details" modals. Reused across the file
// via a tiny local render function so the three modals (Today / 7-Day /
// Monthly) don't repeat the same ~25 lines of markup three times.
if (!function_exists('renderAttendanceDetailModal')) {
    function renderAttendanceDetailModal($id, $title, array $rows) {
        ?>
        <div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><?= htmlspecialchars($title) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Faculty</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                        <th class="pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rows)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox d-block mb-2"></i>
                                                No sessions recorded for this period.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r): ?>
                                            <?php
                                                $badge = $r['status'] === 'Present' ? 'bg-success-subtle text-success'
                                                       : ($r['status'] === 'Late' ? 'bg-warning-subtle text-warning'
                                                       : 'bg-danger-subtle text-danger');
                                            ?>
                                            <tr>
                                                <td class="ps-3"><?= htmlspecialchars($r['date']) ?></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($r['faculty']) ?></td>
                                                <td><?= htmlspecialchars($r['subject']) ?></td>
                                                <td><?= htmlspecialchars($r['room']) ?></td>
                                                <td class="pe-3"><span class="badge <?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span></td>
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
        <?php
    }
}

renderAttendanceDetailModal('todayDetailsModal', "Today's Attendance — " . date('M j, Y'), $todayDetailRows);
renderAttendanceDetailModal('weeklyDetailsModal', '7-Day Attendance (' . date('M j', strtotime($weekStart)) . ' – ' . date('M j, Y', strtotime($today)) . ')', $weeklyDetailRows);
renderAttendanceDetailModal('monthlyDetailsModal', 'Monthly Attendance — ' . date('M Y', strtotime($selectedMonth)), $monthlyDetailRows);
?>

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
                <?= count($facultySummaries ?? []) ?> Faculty
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
                    <?php if (!empty($facultySummaries)): ?>
                        <?php foreach ($facultySummaries as $row): ?>
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
</div>

<?php 
require_once __DIR__ . '/../../../../includes/layout-end.php'; 
?>