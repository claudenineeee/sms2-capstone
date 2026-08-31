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
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;">24 <small class="text-muted fs-6 fw-normal">units</small></h4>
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
                    <h4 class="mb-0 fw-bold" style="color: #28a745;">8 <small class="text-muted fs-6 fw-normal">subjects</small></h4>
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
                    1st Semester 2025-2026
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
                            $subjects = [
                                ['code'=>'CS101','subject'=>'Intro to Computer Science','section'=>'A','units'=>3,'schedule'=>'MWF 8:00-9:30','room'=>'201'],
                                ['code'=>'CS101','subject'=>'Intro to Computer Science','section'=>'B','units'=>3,'schedule'=>'MWF 8:00-9:30','room'=>'201'],
                                ['code'=>'CS401','subject'=>'Software Engineering','section'=>'A','units'=>3,'schedule'=>'MWF 9:30-11:00','room'=>'203'],
                                ['code'=>'CS301','subject'=>'Algorithms','section'=>'A','units'=>3,'schedule'=>'F 1:00-3:00','room'=>'301'],
                                ['code'=>'CS501','subject'=>'Research Methods','section'=>'A','units'=>3,'schedule'=>'TTH 1:00-2:30','room'=>'204'],
                                ['code'=>'CS201','subject'=>'Data Structures','section'=>'A','units'=>3,'schedule'=>'TTH 10:00-11:30','room'=>'202'],
                                ['code'=>'CS401','subject'=>'Software Engineering','section'=>'B','units'=>3,'schedule'=>'TTH 2:00-3:30','room'=>'203'],
                                ['code'=>'CS101','subject'=>'Intro to Computer Science','section'=>'C','units'=>3,'schedule'=>'TTH 8:00-9:30','room'=>'201'],
                            ];
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
                                <th class="text-uppercase small text-body-secondary fw-semibold ps-4" style="font-size: 0.725rem;">Semester</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Academic Year</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Subjects</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold" style="font-size: 0.725rem;">Total Units</th>
                                <th class="text-uppercase small text-body-secondary fw-semibold pe-4" style="font-size: 0.725rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $history = [
                                ['sem'=>'2nd Semester','year'=>'2025-2026','subjects'=>8,'units'=>24,'students'=>218,'status'=>'Current'],
                                ['sem'=>'1st Semester','year'=>'2025-2026','subjects'=>7,'units'=>21,'students'=>195,'status'=>'Completed'],
                                ['sem'=>'2nd Semester','year'=>'2024-2025','subjects'=>8,'units'=>24,'students'=>210,'status'=>'Completed'],
                                ['sem'=>'1st Semester','year'=>'2024-2025','subjects'=>7,'units'=>21,'students'=>188,'status'=>'Completed'],
                                ['sem'=>'2nd Semester','year'=>'2023-2024','subjects'=>8,'units'=>24,'students'=>205,'status'=>'Completed'],
                            ];
                            foreach ($history as $h) {
                                $isCurrent = $h['status'] === 'Current';
                                $statusBadge = $isCurrent 
                                    ? 'bg-primary bg-opacity-10 text-primary border-primary' 
                                    : 'bg-success bg-opacity-10 text-success border-success';
                                
                                echo <<<HTML
                                <tr>
                                    <td class="fw-semibold text-dark ps-4">{$h['sem']}</td>
                                    <td class="text-body-secondary">{$h['year']}</td>
                                    <td>{$h['subjects']} subjects</td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{$h['units']} units</span></td>
                                    <td class="pe-4"><span class="badge border border-opacity-25 {$statusBadge} rounded-pill px-3">{$h['status']}</span></td>
                                </tr>
                                HTML;
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