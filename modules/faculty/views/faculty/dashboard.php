<?php
/**
 * Faculty Dashboard
 * Purpose: Personal dashboard for faculty member
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Faculty Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-user text-purple me-2"></i>Faculty Dashboard</h1>
        <p class="text-muted mb-0">Welcome, Prof. Maria Santos</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary btn-sm fw-medium" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/faculty/pages/leave-request.php'">
            <i class="fas fa-plane me-1"></i>Submit Leave
        </button>
        <button class="btn btn-dark btn-sm fw-medium" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/faculty/pages/my-schedule.php'">
            <i class="fas fa-calendar me-1"></i>My Schedule
        </button>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Stat Metric Cards (Subtle & Neutral) -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">  
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-2 bg-body">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-5">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Teaching Load</span>
                    <h4 class="fw-bold mb-0">24 <small class="text-muted fs-6">units</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-2 bg-body">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-5">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Classes Today</span>
                    <h4 class="fw-bold mb-0">3</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-2 bg-body">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-5">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Attendance Rate</span>
                    <h4 class="fw-bold mb-0">92%</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-2 bg-body">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-5">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Rating</span>
                    <h4 class="fw-bold mb-0">4.6 <small class="text-muted fs-6">/5.0</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-2 bg-body">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-3 fs-5">
                    <i class="fas fa-plane"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Leave Balance</span>
                    <h4 class="fw-bold mb-0">10 <small class="text-muted fs-6">days</small></h4>
                </div>
            </div>
        </div>
    </div>
</div>
    
  <!-- Main Content Area -->
<div class="row g-4">
    <!-- Schedule & Deliverables (Left Column) -->
    <div class="col-12 col-lg-8">         
        <!-- Schedule Card -->
        <div class="card border shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="far fa-clock text-primary me-2"></i>Today's Schedule
                </h6>
                <span class="badge bg-light text-secondary border fw-normal">August 1, 2025</span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex flex-column gap-2">

                    <!-- Class Row 1 -->
                    <div class="p-3 rounded-2 border bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">CS101 — Intro to Computer Science</h6>
                                <span class="text-secondary small"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Room 201</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">08:00 - 09:30 AM</span>
                        </div>
                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button class="btn btn-sm btn-primary">Take Attendance</button>
                        </div>
                    </div>

                    <!-- Class Row 2 -->
                    <div class="p-3 rounded-2 border bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">CS401 — Software Engineering</h6>
                                <span class="text-secondary small"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Room 203</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">09:30 - 11:00 AM</span>
                        </div>
                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button class="btn btn-sm btn-outline-primary">Take Attendance</button>
                        </div>
                    </div>

                    <!-- Class Row 3 -->
                    <div class="p-3 rounded-2 border bg-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-semibold text-secondary mb-1">CS301 — Design & Analysis of Algorithms</h6>
                                <span class="text-muted small"><i class="fas fa-map-marker-alt me-1"></i> Room 301</span>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace">01:00 - 03:00 PM</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Deliverables & Deadlines -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="far fa-calendar-check text-primary me-2"></i>Upcoming Deadlines
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php
                    $deadlines = [
                        ['date' => 'AUG 05', 'title' => 'Submit Midterm Exam Grades', 'desc' => 'Final portal locking at midnight'],
                        ['date' => 'AUG 10', 'title' => 'Performance Self-Assessment Due', 'desc' => 'Submit form via Faculty Portal'],
                        ['date' => 'AUG 15', 'title' => 'Monthly Departmental Meeting', 'desc' => 'CCS Conference Room at 2:00 PM'],
                        ['date' => 'AUG 20', 'title' => 'Research Paper Draft Submission', 'desc' => 'Submit to Dean\'s Office']
                    ];
                    foreach ($deadlines as $d): ?>
                        <li class="list-group-item d-flex align-items-center gap-3 py-3 px-3">
                            <div class="text-center border border-primary-subtle rounded px-2 py-1 bg-primary-subtle text-primary flex-shrink-0" style="min-width: 60px;">
                                <span class="d-block fw-bold small"><?= $d['date'] ?></span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="mb-0 fw-semibold text-dark text-truncate small"><?= $d['title'] ?></h6>
                                <small class="text-secondary d-block style-tiny"><?= $d['desc'] ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>

    <!-- Sidebar Stream (Right Column) -->
    <div class="col-12 col-lg-4">

        <!-- Notifications Card -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="far fa-bell text-primary me-2"></i>Recent Notifications
                </h6>
                <button class="btn btn-sm btn-link text-primary text-decoration-none p-0 small" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/faculty/pages/notifications.php'">
                    View All
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php
                    $notifications = [
                        ['title' => 'Schedule Change', 'msg' => 'CS301 schedule changed to Room 302', 'time' => '1h ago'],
                        ['title' => 'Leave Approved', 'msg' => 'Your sick leave request for Aug 21-22 has been approved', 'time' => '2h ago'],
                        ['title' => 'Performance Update', 'msg' => 'Your evaluation for 2nd Semester 2025 is available', 'time' => '1d ago']
                    ];
                    foreach ($notifications as $n): ?>
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small text-dark"><?= $n['title'] ?></strong>
                                <span class="text-muted style-tiny" style="font-size: 0.75rem;"><?= $n['time'] ?></span>
                            </div>
                            <p class="mb-0 small text-secondary"><?= $n['msg'] ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
