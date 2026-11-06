<?php
/**
 * My Schedule
 * Purpose: View personal class schedule
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../../../includes/security.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

$pageTitle    = 'My Schedule';
$activeModule = 'faculty';
$activePage   = 'my-schedule';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'My Schedule', 'url' => null],
];

$teachingLoadUnits = 0;
$totalSubjectsCount = 0;
$totalSectionsCount = 0;
$classesTodayCount = 0;
$weeklyScheduleData = array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], []);
$dailyScheduleData = [];
$assignedSubjects = [];
$availableTerms = [];
$selectedTerm = null;
$scheduleError = '';
$currentUserId = (int) (getCurrentUserId() ?? 0);
$todayName = date('l, F j, Y');

// We are mocking the schedule data here since the tables/API are not available yet.
// Replace this block with your REST API call when it is ready.
$assignedSubjects = []; 
$teachingLoadUnits = 0;

$pendingAcknowledgement = false;

// Load the page layout and breadcrumbs helper
require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <?php if ($scheduleError !== ''): ?>
        <div id="statusToast" class="toast align-items-center text-bg-danger border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <span><?= e($scheduleError) ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($pendingAcknowledgement): ?>
        <div id="pendingToast" class="toast align-items-center text-bg-info border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="7000">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle fs-5"></i>
                    <span>A teaching load is waiting for your acknowledgement.</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                <a href="<?= BASE_URL ?>/modules/faculty/views/teaching-load-workflow.php" class="btn btn-sm btn-light text-info fw-bold me-2 my-auto text-nowrap">Review</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-calendar text-purple me-2"></i>My Schedule</h1>
        <p class="text-muted mb-0">View your personal class schedule</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
        <button class="btn btn-outline-success" type="button" onclick="window.print()"><i class="fas fa-file-pdf me-1"></i>Save as PDF</button>
    </div>
</div>

<!-- Summary Cards (4-Column Grid with styled indicator bars) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Teaching Load</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= e(number_format($teachingLoadUnits, 1)) ?> <small class="text-muted fs-6">units</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;">
                    <i class="fas fa-journal-whills"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Subjects</h6>
                    <h4 class="mb-0 fw-bold" style="color: #28a745;"><?= $totalSubjectsCount ?></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0dcaf0; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0dcaf0;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Sections</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0dcaf0;"><?= $totalSectionsCount ?></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #ffc107;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Classes Today</h6>
                    <h4 class="mb-0 fw-bold" style="color: #ffc107;"><?= $classesTodayCount ?></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- 2-Column Row Layout for Schedule Views & Assigned Subjects -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        
        <!-- Weekly Calendar View -->
        <div class="card border-0 shadow-sm rounded-3 h-100" id="weeklyView">
            <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-calendar-week me-2"></i>Weekly Calendar
                    </h6>
                    <label class="visually-hidden" for="viewType">Schedule view</label>
                    <select class="form-select form-select-sm w-auto" id="viewType" onchange="changeView()">
                        <option value="weekly" selected>Weekly</option>
                        <option value="daily">Today</option>
                    </select>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-medium">
                    No finalized load
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 100px;" class="text-body-secondary fw-semibold small">Day</th>
                                <th class="text-body-secondary fw-semibold small">Classes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weeklyScheduleData as $day => $classes): ?>
                                <tr>
                                    <th scope="row" class="table-light fw-semibold small text-body-secondary align-middle"><?= e($day) ?></th>
                                    <td class="p-2">
                                        <?php if ($classes === []): ?>
                                            <span class="small text-body-secondary">No classes</span>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-2">
                                                <?php foreach ($classes as $class): ?>
                                                    <div class="p-2 rounded border bg-primary bg-opacity-10 text-start">
                                                        <strong class="text-primary"><?= e($class['code'] ?? '') ?></strong>
                                                        <span class="small"><?= e($class['title'] ?? '') ?></span>
                                                        <div class="small text-body-secondary">
                                                            <?= e($class['display_time'] ?? '') ?> · Section <?= e($class['section'] ?? '') ?> · <?= e((string) ($class['units'] ?? '0')) ?> units
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily View (Hidden by Default) -->
        <div class="card border-0 shadow-sm rounded-3 h-100 d-none" id="dailyView">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-calendar-day me-2"></i>Daily Schedule
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-medium">
                    <?= e($todayName) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <?php if ($dailyScheduleData === []): ?>
                        <div class="text-center text-muted py-4 small">No finalized classes scheduled for today.</div>
                    <?php else: ?>
                        <?php foreach ($dailyScheduleData as $class): ?>
                            <article class="d-flex align-items-start gap-3 p-3 rounded border bg-primary bg-opacity-10">
                                <div class="fw-bold text-body-secondary small text-nowrap">
                                    <i class="far fa-clock me-1"></i><?= e($class['display_time'] ?? '') ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="h6 mb-1 fw-bold"><?= e($class['code'] ?? '') ?> · <?= e($class['title'] ?? '') ?></h3>
                                    <div class="d-flex flex-wrap gap-3 text-body-secondary small">
                                        <span>Section <?= e($class['section'] ?? '') ?></span>
                                        <span><?= e((string) ($class['units'] ?? '0')) ?> units</span>
                                        <span>Room <?= e($class['display_room'] ?? '') ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Column 2: Assigned Subjects Table -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-list me-2"></i>Assigned Subjects
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-body-secondary fw-semibold small">Code</th>
                                <th class="text-body-secondary fw-semibold small">Subject Title</th>
                                <th class="text-body-secondary fw-semibold small">Sec</th>
                                <th class="text-body-secondary fw-semibold small">Units</th>
                                <th class="text-body-secondary fw-semibold small">Schedule</th>
                                <th class="pe-3 text-body-secondary fw-semibold small">Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($assignedSubjects === []): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4 small">No finalized subjects found for this term.</td></tr>
                            <?php else: ?>
                                <?php foreach ($assignedSubjects as $subject): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-primary small"><?= e((string) ($subject['code'] ?? '')) ?></td>
                                        <td class="fw-medium small"><?= e((string) ($subject['title'] ?? '')) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e((string) ($subject['section'] ?? '')) ?></span></td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= e((string) ($subject['units'] ?? '0')) ?></span></td>
                                        <td class="small text-body-secondary" style="font-size: 12px;">
                                            <?= e((string) ($subject['display_schedule'] ?? 'Days not set')) ?> · <?= e($subject['display_time'] ?? '') ?>
                                        </td>
                                        <td class="pe-3"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($subject['display_room'] ?? '') ?></span></td>
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
function changeView() {
    const viewType = document.getElementById('viewType')?.value;
    document.getElementById('weeklyView')?.classList.toggle('d-none', viewType !== 'weekly');
    document.getElementById('dailyView')?.classList.toggle('d-none', viewType !== 'daily');
}

// Initialize Toasts
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        ['statusToast', 'pendingToast'].forEach(id => {
            const el = document.getElementById(id);
            if (el) new bootstrap.Toast(el).show();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>