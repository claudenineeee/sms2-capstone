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
    ['label' => 'Department Head',   'url' => BASE_URL . '/modules/faculty/users/department_head/dashboard.php'],
    ['label' => 'Attendance Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php'; 

// Filter parameters
$selectedPeriod = $_GET['period'] ?? '7days';
$selectedMonth  = $_GET['month'] ?? date('Y-m');
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
            <a href="javascript:void(0)" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
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
            <a href="javascript:void(0)" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
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
            <a href="javascript:void(0)" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
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