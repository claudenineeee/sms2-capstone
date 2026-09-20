<?php
/**
 * Teaching Load
 * Purpose: View assigned teaching load (read-only)
 */
require_once __DIR__ . '/../../../../config/config.php';


$pageTitle    = 'Teaching Load';
$activeModule = 'faculty';
$activePage   = 'teaching-load';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Teaching Load', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1><i class="fas fa-book-open text-purple me-2"></i>Teaching Load</h1>
        <p class="text-muted mb-0">View your assigned teaching load (read-only)</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i>Export Load</button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Current Load</h6>
                    <!-- TODO: Fetch total units dynamically via REST API -->
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;">0 <small class="text-muted fs-6 fw-normal">units</small></h4>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-md-6">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Subjects</h6>
                    <!-- TODO: Fetch total subjects count dynamically via REST API -->
                    <h4 class="mb-0 fw-bold" style="color: #28a745;">0 <small class="text-muted fs-6 fw-normal">subjects</small></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Combined ROW: Current Teaching Load & Load History Side-by-Side (Extended Height) -->
<div class="row g-4 mb-4">
    <!-- Column 1: Current Teaching Load Table -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                    <i class="fas fa-list me-2 fs-5"></i>Current Teaching Load
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1.5 rounded-pill fw-medium border border-primary border-opacity-25 small">
                    <!-- TODO: Dynamically display current active term/semester -->
                    Active Semester
                </span>
            </div>
            <div class="card-body p-0">
                <!-- Extended Scrollable Table Container -->
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="text-uppercase small text-body-secondary fw-semibold ps-4" style="font-size: 0.725rem;">Code</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Subject</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Sec</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold text-center" style="font-size: 0.725rem;">Units</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Schedule</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold pe-4" style="font-size: 0.725rem;">Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            /**
                             * TODO: REST API Integration Point for Current Teaching Load
                             * 
                             * Future data should be fetched via REST API (e.g., calling an internal endpoint 
                             * or fetching JSON data) and populated into this $subjects array.
                             * 
                             * Expected array structure:
                             * $subjects = [
                             *     [
                             *         'code'     => string, // e.g., 'CS101'
                             *         'subject'  => string, // e.g., 'Intro to Computer Science'
                             *         'section'  => string, // e.g., 'A'
                             *         'units'    => int,    // e.g., 3
                             *         'schedule' => string, // e.g., 'MWF 8:00-9:30'
                             *         'room'     => string  // e.g., '201'
                             *     ],
                             *     ...
                             * ];
                             */
                            $subjects = []; // Hardcoded array removed; ready for REST API dataset

                            if (empty($subjects)) {
                                echo '<tr><td colspan="6" class="text-center text-muted py-4">No current teaching load found.</td></tr>';
                            } else {
                                foreach ($subjects as $s) {
                                    echo <<<HTML
                                    <tr>
                                        <td class="fw-bold text-primary ps-4">{$s['code']}</td>
                                        <td class="fw-medium text-dark">{$s['subject']}</td>
                                        <td><span class="badge bg-light text-dark border">{$s['section']}</span></td>
                                        <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{$s['units']} units</span></td>
                                        <td class="small text-body-secondary"><i class="far fa-clock me-1 text-muted"></i>{$s['schedule']}</td>
                                        <td class="pe-4"><span class="badge bg-secondary bg-opacity-10 text-secondary">{$s['room']}</span></td>
                                    </tr>
                                    HTML;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Column 2: Teaching Load History Table -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom-0">
                <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                    <i class="fas fa-history me-2 fs-5"></i>Teaching Load History
                </h6>
            </div>
            <div class="card-body p-0">
                <!-- Extended Scrollable Table Container -->
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="text-uppercase small text-body-secondary fw-semibold ps-4" style="font-size: 0.725rem;">Semester & Ends On</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Academic Year</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Subjects</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Total Units</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold pe-4" style="font-size: 0.725rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            /**
                             * TODO: REST API Integration Point for Teaching Load History
                             * 
                             * Future data should be fetched via REST API and populated into this $history array.
                             * The 'semester_end' property indicates the date/time when the semester concludes.
                             * 
                             * Expected array structure:
                             * $history = [
                             *     [
                             *         'sem'          => string, // e.g., '2nd Semester'
                             *         'semester_end' => string, // e.g., 'May 31, 2026' (Shows when the semester ends)[cite: 19]
                             *         'year'         => string, // e.g., '2025-2026'
                             *         'subjects'     => int,    // e.g., 8
                             *         'units'        => int,    // e.g., 24
                             *         'status'       => string  // e.g., 'Current' or 'Completed'
                             *     ],
                             *     ...
                             * ];
                             */
                            $history = []; // Hardcoded array removed; ready for REST API dataset

                            if (empty($history)) {
                                echo '<tr><td colspan="5" class="text-center text-muted py-4">No teaching load history found.</td></tr>';
                            } else {
                                foreach ($history as $h) {
                                    $isCurrent = $h['status'] === 'Current';
                                    $statusBadge = $isCurrent 
                                        ? 'bg-primary bg-opacity-10 text-primary border-primary' 
                                        : 'bg-success bg-opacity-10 text-success border-success';
                                    
                                    echo <<<HTML
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-semibold text-dark d-block">{$h['sem']}</span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1"></i>Ends: {$h['semester_end']}</small>
                                        </td>
                                        <td class="text-body-secondary">{$h['year']}</td>
                                        <td>{$h['subjects']} subjects</td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{$h['units']} units</span></td>
                                        <td class="pe-4"><span class="badge border border-opacity-25 {$statusBadge} rounded-pill px-3">{$h['status']}</span></td>
                                    </tr>
                                    HTML;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>