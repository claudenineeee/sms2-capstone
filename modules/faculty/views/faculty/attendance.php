<?php
/**
 * Attendance
 * Purpose: Manage personal attendance
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Attendance';
$activeModule = 'faculty';
$activePage   = 'attendance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Attendance', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<?php
// =====================================================================
// CRITICAL FIX: Resolve the correct faculty_profiles.id for the logged-in user
// =====================================================================
require_once __DIR__ . '/../../models/AttendanceModel.php';

$currentFacultyId = null;

// 1. Check if session already has faculty_profile_id (most direct)
if (!empty($_SESSION['user']['faculty_profile_id'])) {
    $currentFacultyId = $_SESSION['user']['faculty_profile_id'];
} 
// 2. Check if session has faculty_id
elseif (!empty($_SESSION['user']['faculty_id'])) {
    $currentFacultyId = $_SESSION['user']['faculty_id'];
}
// 3. If not found, look it up using the system user ID from the session
else {
    $systemUserId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
    if ($systemUserId) {
        try {
            // We assume there is a connection to the database available
            // We query faculty_profiles to find the matching profile ID using the system user_id
            $stmt = db()->prepare("SELECT id FROM faculty_db.faculty_profiles WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $systemUserId]);
            $currentFacultyId = $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Failed to fetch faculty profile ID: ' . $e->getMessage());
        }
    }
}

$myAttendanceRecords = [];
$myStats = [
    'days_present' => 0,
    'days_absent' => 0,
    'total_logs' => 0
];

if ($currentFacultyId) {
    $attendanceModel = new AttendanceModel(db());
    
    try {
        // Fetch the actual records saved by the Monitoring Officer
        $myAttendanceRecords = $attendanceModel->getFacultyAttendanceRecords($currentFacultyId, 15);
        
        // Calculate simple stats based on the fetched records
        foreach ($myAttendanceRecords as $rec) {
            $myStats['total_logs']++;
            if ($rec['status'] === 'Present') {
                $myStats['days_present']++;
            } elseif ($rec['status'] === 'Absent') {
                $myStats['days_absent']++;
            }
        }
    } catch (Throwable $e) {
        error_log('Faculty attendance fetch failed: ' . $e->getMessage());
    }
}

// Compute attendance rate (if there are any logs)
$attendanceRate = ($myStats['total_logs'] > 0) 
    ? round(($myStats['days_present'] / $myStats['total_logs']) * 100) 
    : 0;

// Prepare unique dates for the left table
$uniqueDates = [];
foreach ($myAttendanceRecords as $rec) {
    $dateKey = $rec['session_date'];
    if (!isset($uniqueDates[$dateKey])) {
        $dateObj = new DateTime($dateKey);
        $uniqueDates[$dateKey] = [
            'date' => $dateObj->format('M d, Y'),
            'day' => $dateObj->format('l'),
            'active' => false
        ];
    }
}
$uniqueDates = array_values($uniqueDates);
if (empty($uniqueDates)) {
    $uniqueDates[] = ['date' => date('M d, Y'), 'day' => date('l'), 'active' => true];
}
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            <i class="fas fa-user-check text-primary me-2"></i>My Attendance
        </h1>
        <p class="text-body-secondary mb-0">Track your daily check-in logs and teaching hours recorded by the Monitoring Officer</p>
    </div>    
</div>

<!-- Summary Cards (4-Column Grid with styled indicator bars) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;">
                    <i class="fas fa-percentage"></i>
                </div>
                <div>
                    <!-- Dynamic attendance rate -->
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Attendance Rate</h6>
                    <h4 class="mb-0 fw-bold" style="color: #28a745;"><?= $attendanceRate ?>% <small class="text-muted fs-6">overall</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <!-- Dynamic days present -->
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Days Present</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= $myStats['days_present'] ?> <small class="text-muted fs-6">days</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #ffc107;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <!-- Dynamic days absent -->
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Absences</h6>
                    <h4 class="mb-0 fw-bold" style="color: #ffc107;"><?= $myStats['days_absent'] ?> <small class="text-muted fs-6">days</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ff4d4d; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #ff4d4d;">
                    <i class="fas fa-user-times"></i>
                </div>
                <div>
                    <!-- Dynamic total records -->
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Logs</h6>
                    <h4 class="mb-0 fw-bold" style="color: #ff4d4d;"><?= $myStats['total_logs'] ?> <small class="text-muted fs-6">entries</small></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Side-by-Side Section: Date Selector & Teacher Subject Attendance -->
<div class="row g-4 mb-4">
    <!-- Left Table: Date Selection -->
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold text-primary text-nowrap">
                    <i class="fas fa-calendar-alt me-2"></i>Attendance Dates
                </h6>
                <div class="d-flex align-items-center">
                    <select class="form-select form-select-sm" style="width: auto;">
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive custom-scrollbar" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3 text-body-secondary fw-semibold small text-nowrap">Date</th>
                                <th class="text-body-secondary fw-semibold small text-nowrap">Day</th>
                                <th class="text-end pe-3 text-body-secondary fw-semibold small text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uniqueDates as $index => $d): 
                                $activeClass = $index === 0 ? 'table-active' : '';
                            ?>
                                <tr class="<?= $activeClass ?>" style="cursor: pointer;">
                                    <td class="ps-3 fw-medium small text-nowrap"><?= $d['date'] ?></td>
                                    <td class="small text-body-secondary text-nowrap"><?= $d['day'] ?></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <?php if ($index === 0): ?>
                                            <span class="badge bg-primary rounded-pill">Selected</span>
                                        <?php else: ?>
                                            <button class="btn btn-xs btn-outline-secondary py-0 px-2 style-tiny" style="font-size:0.75rem;">View</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Table: My Attendance Logs (Saved by Monitoring Officer) -->
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-chalkboard-teacher me-2"></i>My Attendance Log
                </h6>
                <span class="badge <?= ($myStats['days_present'] > 0) ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' ?> px-2 px-sm-3 py-1 rounded-pill small text-nowrap">
                    <i class="fas fa-check-circle me-1"></i><?= $myStats['days_present'] ?> Present / <?= $myStats['days_absent'] ?> Absent
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive custom-scrollbar" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3 text-body-secondary fw-semibold small text-nowrap">Date & Time</th>
                                <th class="text-body-secondary fw-semibold small">Subject</th>
                                <th class="text-body-secondary fw-semibold small text-nowrap">Room</th>
                                <th class="pe-3 text-body-secondary fw-semibold small text-end text-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myAttendanceRecords)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No attendance records found yet. Please check back after your Monitoring Officer logs your first session.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($myAttendanceRecords as $c): 
                                    $timeDisplay = $c['time_slot']; 
                                    $dateDisplay = date('M d, Y', strtotime($c['session_date']));
                                    
                                    $statusBadge = match($c['status']) {
                                        'Present' => 'bg-success bg-opacity-10 text-success border-success',
                                        'Absent'  => 'bg-danger bg-opacity-10 text-danger border-danger',
                                        default   => 'bg-secondary bg-opacity-10 text-secondary border-secondary'
                                    };
                                ?>
                                    <tr>
                                        <td class="ps-3 font-monospace small fw-medium text-body-secondary text-nowrap">
                                            <?= htmlspecialchars($dateDisplay) ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($timeDisplay) ?></small>
                                        </td>
                                        <td style="min-width: 160px;">
                                            <div class="fw-bold text-body small text-truncate" style="max-width: 220px;"><?= htmlspecialchars($c['subject_code'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="small text-nowrap">
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i><?= htmlspecialchars($c['room_code'] ?? 'N/A') ?>
                                        </td>
                                        <td class="pe-3 text-end text-nowrap">
                                            <span class="badge border border-opacity-25 rounded-pill px-2 px-sm-3 py-1 <?= $statusBadge ?>">
                                                <?= htmlspecialchars($c['status']) ?>
                                            </span>
                                        </td>
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

<style>
    /* Custom Scrollbar Styles (Chrome, Safari, Edge, Firefox) */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.25);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(13, 110, 253, 0.6);
    }

    /* Light Theme Scrollbar Adjustments */
    [data-bs-theme="light"] .custom-scrollbar,
    body:not([data-bs-theme="dark"]) .custom-scrollbar {
        scrollbar-color: rgba(13, 110, 253, 0.3) transparent;
    }
    [data-bs-theme="light"] .custom-scrollbar::-webkit-scrollbar-thumb,
    body:not([data-bs-theme="dark"]) .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(13, 110, 253, 0.3);
    }
</style>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>