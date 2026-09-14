<?php
/**
 * My Schedule
 * Purpose: View personal class schedule
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'My Schedule';
$activeModule = 'faculty';
$activePage   = 'my-schedule';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'My Schedule', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-calendar text-purple me-2"></i>My Schedule</h1>
        <p class="text-muted mb-0">View your personal class schedule</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
        <button class="btn btn-outline-success"><i class="fas fa-file-pdf me-1"></i>Download PDF</button>
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
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;">24 <small class="text-muted fs-6">units</small></h4>
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
                    <h4 class="mb-0 fw-bold" style="color: #28a745;">8</h4>
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
                    <h4 class="mb-0 fw-bold" style="color: #0dcaf0;">6</h4>
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
                    <h4 class="mb-0 fw-bold" style="color: #ffc107;">3</h4>
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
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-calendar-week me-2"></i>Weekly Calendar
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-medium">
                    Week 1: August 1-7, 2025
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 100px;" class="text-body-secondary fw-semibold small">Time</th>
                                <th class="text-body-secondary fw-semibold small">Mon</th>
                                <th class="text-body-secondary fw-semibold small">Tue</th>
                                <th class="text-body-secondary fw-semibold small">Wed</th>
                                <th class="text-body-secondary fw-semibold small">Thu</th>
                                <th class="text-body-secondary fw-semibold small">Fri</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $times = ['7:00-8:00','8:00-9:30','9:30-11:00','10:00-11:30','11:30-1:00','1:00-2:30','1:00-3:00','2:00-4:00','3:00-4:30','4:30-6:00'];
                            foreach ($times as $t) {
                                echo "<tr>";
                                echo "<td class='table-light fw-medium small text-body-secondary align-middle'>$t</td>";
                                
                                for ($d = 0; $d < 5; $d++) {
                                    $badge = '';
                                    if ($t === '8:00-9:30' && $d === 0) {
                                        $badge = '<div class="p-1 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded text-start"><strong class="d-block text-primary small">CS101</strong><span class="small d-block text-body-secondary" style="font-size: 11px;">Rm 201</span></div>';
                                    }
                                    if ($t === '8:00-9:30' && $d === 2) {
                                        $badge = '<div class="p-1 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded text-start"><strong class="d-block text-primary small">CS101</strong><span class="small d-block text-body-secondary" style="font-size: 11px;">Rm 201</span></div>';
                                    }
                                    if ($t === '9:30-11:00' && $d === 0) {
                                        $badge = '<div class="p-1 bg-success bg-opacity-10 border border-success border-opacity-25 rounded text-start"><strong class="d-block text-success small">CS401</strong><span class="small d-block text-body-secondary" style="font-size: 11px;">Rm 203</span></div>';
                                    }
                                    if ($t === '1:00-3:00' && $d === 4) {
                                        $badge = '<div class="p-1 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded text-start"><strong class="d-block text-warning-emphasis small">CS301</strong><span class="small d-block text-body-secondary" style="font-size: 11px;">Rm 301</span></div>';
                                    }
                                    echo "<td class='p-1' style='height: 60px;'>$badge</td>";
                                }
                                echo "</tr>";
                            }
                            ?>
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
                    Monday, August 1, 2025
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <?php
                    $dailySchedule = [
                        ['time'=>'8:00 - 9:30 AM', 'subject'=>'CS101 - Intro to CS', 'room'=>'201', 'section'=>'A', 'isBreak'=>false],
                        ['time'=>'9:30 - 11:00 AM', 'subject'=>'CS401 - Software Engineering', 'room'=>'203', 'section'=>'A', 'isBreak'=>false],
                        ['time'=>'11:00 - 1:00 PM', 'subject'=>'Lunch Break', 'room'=>'-', 'section'=>'-', 'isBreak'=>true],
                        ['time'=>'1:00 - 3:00 PM', 'subject'=>'CS301 - Algorithms', 'room'=>'301', 'section'=>'A', 'isBreak'=>false],
                    ];

                    foreach ($dailySchedule as $s) {
                        $bgTheme = $s['isBreak'] ? 'bg-secondary bg-opacity-10 border-secondary' : 'bg-primary bg-opacity-10 border-primary';
                        $badgeTheme = $s['isBreak'] ? 'bg-secondary' : 'bg-primary';

                        echo <<<HTML
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 border border-opacity-25 $bgTheme">
                            <div class="fw-bold text-body-secondary small text-nowrap">
                                <i class="far fa-clock me-1"></i>{$s['time']}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold fs-6">{$s['subject']}</h6>
                                <div class="d-flex gap-3 text-body-secondary small" style="font-size: 12px;">
                                    <span><i class="fas fa-door-open me-1"></i>Room {$s['room']}</span>
                                    <span><i class="fas fa-users me-1"></i>Section {$s['section']}</span>
                                </div>
                            </div>
                            <div>
                                <span class="badge $badgeTheme rounded-pill">{$s['section']}</span>
                            </div>
                        </div>
                        HTML;
                    }
                    ?>
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
                            <?php
                            $subjects = [
                                ['code'=>'CS101','subject'=>'Intro to Computer Science','section'=>'A','units'=>3,'students'=>45,'schedule'=>'MWF 8:00-9:30','room'=>'201'],
                                ['code'=>'CS101','subject'=>'Intro to Computer Science','section'=>'B','units'=>3,'students'=>42,'schedule'=>'MWF 8:00-9:30','room'=>'201'],
                                ['code'=>'CS401','subject'=>'Software Engineering','section'=>'A','units'=>3,'students'=>38,'schedule'=>'MWF 9:30-11:00','room'=>'203'],
                                ['code'=>'CS301','subject'=>'Algorithms','section'=>'A','units'=>3,'students'=>30,'schedule'=>'F 1:00-3:00','room'=>'301'],
                                ['code'=>'CS501','subject'=>'Research Methods','section'=>'A','units'=>3,'students'=>28,'schedule'=>'TTH 1:00-2:30','room'=>'204'],
                                ['code'=>'CS201','subject'=>'Data Structures','section'=>'A','units'=>3,'students'=>35,'schedule'=>'TTH 10:00-11:30','room'=>'202'],
                            ];
                            foreach ($subjects as $s) {
                                echo <<<HTML
                                <tr>
                                    <td class="ps-3 fw-bold text-primary small">{$s['code']}</td>
                                    <td class="fw-medium small">{$s['subject']}</td>
                                    <td><span class="badge bg-light text-dark border">{$s['section']}</span></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary">{$s['units']}</span></td>
                                    <td class="small text-body-secondary" style="font-size: 12px;">{$s['schedule']}</td>
                                    <td class="pe-3"><span class="badge bg-secondary bg-opacity-10 text-secondary">{$s['room']}</span></td>
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

<script>
function changeView() {
    const viewType = document.getElementById('viewType').value;
    document.getElementById('weeklyView').classList.add('d-none');
    document.getElementById('dailyView').classList.add('d-none');
    
    document.getElementById(viewType + 'View').classList.remove('d-none');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>