<?php
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Daily Attendance Log';
$activeModule = 'faculty';
$activePage   = 'daily-attendance-log';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Daily Attendance Log', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

require_once __DIR__ . '/../../controllers/FacultyController.php';
$facultyController = new FacultyController();
$facultyList = $facultyController->getDirectoryList();
$facultyList = array_filter($facultyList, function ($member) {
    $position = strtolower(trim((string) ($member['position'] ?? '')));
    return $position === 'faculty professor' || $position === 'teacher' || $position === '';
});

// Real stats + today's logs
require_once __DIR__ . '/../../models/AttendanceModel.php';
$attendanceModel = new AttendanceModel(db());

$deptForStats = $_SESSION['user']['department'] ?? $_SESSION['department'] ?? '';
if (empty($deptForStats) && !empty($facultyList)) {
    $firstMember = reset($facultyList);
    $deptForStats = (string) ($firstMember['designated_department'] ?? $firstMember['department_id'] ?? '');
}

// User department fallback variables for payload
$userDeptId   = $_SESSION['user']['department_id'] ?? $_SESSION['department_id'] ?? 1;
$userDeptName = $_SESSION['user']['department'] ?? $_SESSION['department'] ?? $deptForStats;

$recentLogs = [];
$stats = [
    'total_sessions'  => 0,
    'present_faculty' => 0,
    'late_faculty'    => 0,
    'absent_faculty'  => 0,
    'total_students'  => 0,
];
if ($userDeptId !== '' && $userDeptId !== null) {
    $today = date('Y-m-d');
    try {
        $recentLogs = $attendanceModel->getTodayLogs($userDeptId, $today) ?? [];
        $stats = $attendanceModel->getDepartmentStats($userDeptId, $today) ?? $stats;
    } catch (Throwable $e) {
        error_log('Attendance stats fetch failed: ' . $e->getMessage());
    }
}

$totalRecords      = $stats['total_sessions'] ?? 0;
$presentFaculty    = $stats['present_faculty'] ?? 0;
$lateFaculty       = $stats['late_faculty'] ?? 0;
$absentFaculty     = $stats['absent_faculty'] ?? 0;
$totalStudents     = $stats['total_students'] ?? 0;
$totalExpected     = $stats['expected_students'] ?? 0;
$overallAttendance = $totalExpected > 0 ? round(($totalStudents / $totalExpected) * 100) : 0;
$presentRate       = $totalRecords > 0 ? round((($presentFaculty + $lateFaculty) / $totalRecords) * 100) : 0;

$currentUserName = $_SESSION['user_name'] ?? $_SESSION['user']['full_name'] ?? 'Monitoring Officer';

function getStatusBadgeHtml(array $log): string {
    $rawStatus = $log['status'] 
        ?? $log['faculty_status'] 
        ?? $log['attendance_status'] 
        ?? $log['remarks'] 
        ?? '';

    $statusClean = strtolower(trim((string)$rawStatus));

    if ($statusClean === '' || $statusClean === 'pending') {
        if (!empty($log['is_late']) || !empty($log['late_flag'])) {
            $statusClean = 'late';
        } elseif (!empty($log['is_present'])) {
            $statusClean = 'present';
        } elseif (!empty($log['is_absent'])) {
            $statusClean = 'absent';
        }
    }

    if (str_contains($statusClean, 'late')) {
        return '<span class="badge-status badge-late">Late</span>';
    } elseif (str_contains($statusClean, 'present')) {
        return '<span class="badge-status badge-present">Present</span>';
    } elseif (str_contains($statusClean, 'absent')) {
        return '<span class="badge-status badge-absent">Absent</span>';
    } elseif ($statusClean !== '') {
        return '<span class="badge-status badge-late">' . htmlspecialchars(ucfirst($statusClean)) . '</span>';
    }

    return '<span class="text-muted">—</span>';
}
?>

<style>
    /* =========================================================
       DYNAMIC THEME ADAPTATION (LIGHT & DARK MODE)
       ========================================================= */
    :root {
        --dal-bg: #0d1527;
        --dal-card: #10192d;
        --dal-card-2: #16223b;
        --dal-border: #1e2d4a;
        --dal-text: #e2e8f0;
        --dal-text-strong: #ffffff;
        --dal-muted: #8492a6;
        --dal-input-bg: #111c35;
        --dal-hover: rgba(59, 130, 246, 0.08);
        --dal-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    [data-bs-theme="light"] .dal-wrap,
    [data-theme="light"] .dal-wrap,
    html.light .dal-wrap,
    body.light .dal-wrap {
        --dal-bg: #f8fafc !important;
        --dal-card: #ffffff !important;
        --dal-card-2: #f1f5f9 !important;
        --dal-border: #e2e8f0 !important;
        --dal-text: #334155 !important;
        --dal-text-strong: #0f172a !important;
        --dal-muted: #64748b !important;
        --dal-input-bg: #f8fafc !important;
        --dal-hover: rgba(59, 130, 246, 0.05) !important;
        --dal-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }

    [data-bs-theme="dark"] .dal-wrap,
    [data-theme="dark"] .dal-wrap,
    html.dark .dal-wrap,
    body.dark .dal-wrap {
        --dal-bg: #0d1527 !important;
        --dal-card: #10192d !important;
        --dal-card-2: #16223b !important;
        --dal-border: #1e2d4a !important;
        --dal-text: #e2e8f0 !important;
        --dal-text-strong: #ffffff !important;
        --dal-muted: #8492a6 !important;
        --dal-input-bg: #111c35 !important;
        --dal-hover: rgba(59, 130, 246, 0.08) !important;
        --dal-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    }

    .dal-wrap {
        padding: 1.5rem 1.25rem;
        background-color: transparent !important;
    }

    .dashboard-header { padding: 0.5rem 0 1rem 0; }
    .dashboard-header .header-icon { font-size: 1.6rem; color: #3b82f6; line-height: 1; margin-top: 0.15rem; }
    .dashboard-header h1 { font-size: 1.4rem; font-weight: 700; color: var(--dal-text-strong) !important; }
    .dashboard-header .subtitle { font-size: 0.825rem; font-weight: 500; margin-top: 0.1rem; color: var(--dal-muted) !important; }
    .dashboard-header .subtitle .date-highlight { font-weight: 600; color: var(--dal-text-strong) !important; }
    .dashboard-header .description { font-size: 0.825rem; margin-top: 0.25rem; color: var(--dal-muted) !important; }

    .dashboard-header button.btn-header {
        background-color: var(--dal-card-2) !important; 
        border: 1px solid var(--dal-border) !important;
        color: var(--dal-text-strong) !important;
        font-size: 0.75rem !important; 
        font-weight: 600 !important;
        padding: 0.4rem 0.85rem !important; 
        border-radius: 0.4rem !important;
        transition: all 0.2s ease !important;
    }
    .dashboard-header button.btn-header:hover {
        background-color: var(--dal-hover) !important;
        border-color: #3b82f6 !important;
        color: #3b82f6 !important;
    }

    .card-dark, .stat-card-dark {
        background-color: var(--dal-card) !important;
        border: 1px solid var(--dal-border) !important;
        border-radius: 0.5rem;
        color: var(--dal-text) !important;
        box-shadow: var(--dal-shadow) !important;
    }
    .stat-card-dark .stat-icon-bg {
        width: 42px; height: 42px; border-radius: 0.5rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }

    .card-dark-inner {
        background-color: var(--dal-card-2) !important;
        border: 1px solid var(--dal-border) !important;
        color: var(--dal-text) !important;
    }

    .form-dark, .form-dark[readonly], .form-dark:disabled {
        background-color: var(--dal-input-bg) !important;
        border: 1px solid var(--dal-border) !important;
        color: var(--dal-text-strong) !important;
        font-size: 0.85rem !important;
    }
    .form-dark:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
    }
    .form-dark::placeholder { color: var(--dal-muted) !important; }

    #faculty_dropdown_list {
        background-color: var(--dal-card) !important;
        border: 1px solid var(--dal-border) !important;
        border-radius: 0.375rem;
        box-shadow: var(--dal-shadow) !important;
    }
    #faculty_dropdown_list .faculty-option {
        background-color: transparent !important;
        color: var(--dal-text) !important;
        border-bottom: 1px solid var(--dal-border) !important;
        padding: 0.45rem 0.75rem !important;
        font-size: 0.8rem !important;
    }
    #faculty_dropdown_list .faculty-option:hover {
        background-color: var(--dal-card-2) !important;
        color: #3b82f6 !important;
    }
    .faculty-pos-tag { font-size: 0.725rem; color: var(--dal-muted); }

    .table.table-dark-custom thead,
    .table.table-dark-custom thead tr,
    .table.table-dark-custom thead tr th,
    .table.table-dark-custom th {
        background-color: var(--dal-card-2) !important;
        color: var(--dal-muted) !important;
        border-bottom: 1px solid var(--dal-border) !important;
        font-size: 0.725rem !important;
        text-transform: uppercase !important;
    }
    .table.table-dark-custom tbody td {
        background-color: transparent !important;
        border-bottom: 1px solid var(--dal-border) !important;
        color: var(--dal-text) !important;
    }
    .table.table-dark-custom tbody tr:hover td {
        background-color: var(--dal-hover) !important;
    }

    .stepper-circle {
        width: 32px; height: 32px; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        font-weight: 700; font-size: 0.85rem;
        background-color: var(--dal-card-2);
        color: var(--dal-muted);
        border: 2px solid var(--dal-card);
    }
    .stepper-step { position: relative; }
    .stepper-step:not(:last-child)::after {
        content: ''; position: absolute; top: 16px; left: 50%; width: 100%; height: 2px;
        background-color: var(--dal-border); z-index: 1;
    }
    .stepper-step.active .stepper-circle { background-color: #2563eb !important; color: #fff !important; }
    .stepper-step.completed .stepper-circle { background-color: #10b981 !important; color: #fff !important; }
    .stepper-step.completed:not(:last-child)::after { background-color: #10b981 !important; }
    .stepper-step small { color: var(--dal-text); }

    .sig-canvas-wrap { 
        height: 220px; border: 2px dashed var(--dal-border); 
        background: var(--dal-input-bg); border-radius: 0.5rem;
    }

    .badge-status {
        display: inline-block; padding: 0.25rem 0.6rem;
        font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; text-align: center;
    }
    .badge-present { background-color: rgba(16, 185, 129, 0.18) !important; color: #34d399 !important; }
    .badge-late { background-color: rgba(245, 158, 11, 0.18) !important; color: #fbbf24 !important; }
    .badge-absent { background-color: rgba(239, 68, 68, 0.18) !important; color: #f87171 !important; }
    .workflow-panel.d-none { display: none !important; }
</style>

<div class="dal-wrap">
    <div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-start gap-3">
            <div class="header-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
                <h1 class="mb-0">Daily Attendance Log</h1>
                <div class="subtitle">Live room inspections · Today: <span class="date-highlight"><?= date('F j, Y') ?></span></div>
                <p class="description mb-0">Track real-time faculty presence, student headcount, and unattended room incidents.</p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-header" data-bs-toggle="modal" data-bs-target="#recordsModal">
                <i class="fas fa-table me-1"></i> All Records
            </button>
            <button type="button" id="btnResetWorkflow" class="btn btn-header d-none">
                <i class="fas fa-rotate-left me-1"></i> Reset
            </button>
            <button type="button" id="btnNewRoomCheck" class="btn btn-header">
                <i class="fas fa-bolt me-1"></i> Start Room Check
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card-dark p-3 h-100 position-relative overflow-hidden" style="border-left: 3px solid #3b82f6 !important;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-bg me-3" style="background-color: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold" style="font-size: 0.675rem; color: var(--dal-muted); letter-spacing: 0.05em;">Today's Checks</div>
                        <h3 class="mb-0 fw-bold" id="statTotal"><?= htmlspecialchars($totalRecords); ?></h3>
                        <div class="mt-1" style="font-size: 0.75rem; color: #34d399;"><i class="fas fa-arrow-trend-up me-1"></i>Live today</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card-dark p-3 h-100 position-relative overflow-hidden" style="border-left: 3px solid #10b981 !important;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-bg me-3" style="background-color: rgba(16, 185, 129, 0.15); color: #34d399;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold" style="font-size: 0.675rem; color: var(--dal-muted); letter-spacing: 0.05em;">Faculty Presence</div>
                        <h3 class="mb-0 fw-bold" id="statPresent"><?= htmlspecialchars($presentRate); ?>%</h3>
                        <div class="mt-1" style="font-size: 0.75rem; color: #34d399;"><i class="fas fa-check me-1"></i><?= htmlspecialchars($presentFaculty); ?> present</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card-dark p-3 h-100 position-relative overflow-hidden" style="border-left: 3px solid #f59e0b !important;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-bg me-3" style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold" style="font-size: 0.675rem; color: var(--dal-muted); letter-spacing: 0.05em;">Late Faculty</div>
                        <h3 class="mb-0 fw-bold" id="statLate"><?= htmlspecialchars($lateFaculty); ?></h3>
                        <div class="mt-1" style="font-size: 0.75rem; color: #fbbf24;"><i class="fas fa-clock me-1"></i>Late arrivals</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card-dark p-3 h-100 position-relative overflow-hidden" style="border-left: 3px solid #ef4444 !important;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-bg me-3" style="background-color: rgba(239, 68, 68, 0.15); color: #f87171;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold" style="font-size: 0.675rem; color: var(--dal-muted); letter-spacing: 0.05em;">Unattended Rooms</div>
                        <h3 class="mb-0 fw-bold" id="statAbsent"><?= htmlspecialchars($absentFaculty); ?></h3>
                        <div class="mt-1" style="font-size: 0.75rem; color: #fbbf24;"><i class="fas fa-triangle-exclamation me-1"></i>Requires action</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card-dark p-3 h-100 position-relative overflow-hidden" style="border-left: 3px solid #06b6d4 !important;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-bg me-3" style="background-color: rgba(6, 182, 212, 0.15); color: #22d3ee;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold" style="font-size: 0.675rem; color: var(--dal-muted); letter-spacing: 0.05em;">Student Attendance</div>
                        <h3 class="mb-0 fw-bold" id="statRate"><?= htmlspecialchars($overallAttendance); ?>%</h3>
                        <div class="mt-1" style="font-size: 0.75rem; color: #60a5fa;"><i class="fas fa-user-check me-1"></i><?= htmlspecialchars($totalStudents); ?> / <?= htmlspecialchars($totalExpected); ?> Present</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow Map Container -->
    <div class="card-dark p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-uppercase fw-bold fs-7 opacity-75">Workflow Map</span>
                <h6 class="mb-0 fw-bold">Dual-lane attendance monitoring cycle</h6>
            </div>
            <span class="badge" style="background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                <i class="fas fa-diagram-project me-1"></i> State-driven
            </span>
        </div>
        <div class="pt-2 pb-3 border-top border-bottom" style="border-color: var(--dal-border) !important;">
            <div class="d-flex justify-content-between position-relative" id="stepperTrack"></div>
        </div>
    </div>

    <!-- Active Stage & Recent Logs Split -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card-dark p-4 h-100" id="workflowStage">
                <!-- STEP 1: Start Room Check -->
                <div class="workflow-panel" data-panel="START_ROOM_CHECK">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-door-open me-2 text-primary"></i>Initiate Room Check</h5>
                        <span class="badge" style="background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">Step 1 of 4</span>
                    </div>
                    <form id="startRoomCheckForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="faculty_search">Faculty / Professor</label>
                                <div class="position-relative">
                                    <input type="text" id="faculty_search" class="form-control form-dark" placeholder="Search instructor..." autocomplete="off">
                                    <div id="faculty_dropdown_list" class="list-group" style="display:none; position:absolute; width:100%; z-index:1050; max-height:180px; overflow-y:auto;">
                                        <?php foreach ($facultyList as $faculty): ?>
                                            <?php
                                                $nameOnly = htmlspecialchars(($faculty['last_name'] ?? '') . ', ' . ($faculty['first_name'] ?? ''));
                                                $posText  = !empty($faculty['position']) ? htmlspecialchars($faculty['position']) : 'Faculty Professor';
                                                $fullVal  = $nameOnly . ' (' . $posText . ')';
                                                $facId    = htmlspecialchars($faculty['id'] ?? $faculty['faculty_id'] ?? '');
                                            ?>
                                            <button type="button" class="list-group-item list-group-item-action faculty-option" data-id="<?= $facId ?>" data-name="<?= $fullVal ?>">
                                                <span><?= $nameOnly ?></span>
                                                <span class="faculty-pos-tag">(<?= $posText ?>)</span>
                                            </button>
                                        <?php endforeach; ?>
                                        <div id="faculty_no_match" class="list-group-item bg-transparent text-muted small p-2 d-none">No matching faculty found.</div>
                                    </div>
                                </div>
                                <select name="faculty_id" id="faculty_select" class="d-none">
                                    <option value="" disabled selected>Select instructor...</option>
                                    <?php foreach ($facultyList as $faculty): ?>
                                        <?php
                                            $fullName = htmlspecialchars(($faculty['last_name'] ?? '') . ', ' . ($faculty['first_name'] ?? ''));
                                            $pos = !empty($faculty['position']) ? ' (' . htmlspecialchars($faculty['position']) . ')' : '';
                                            $facId = htmlspecialchars($faculty['id'] ?? $faculty['faculty_id'] ?? '');
                                        ?>
                                        <option value="<?= $facId ?>"><?= $fullName . $pos ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7" for="form_room">Room</label>
                                <input type="text" id="form_room" class="form-control form-dark" placeholder="e.g. 403-B" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7" for="form_time">Time Slot</label>
                                <input type="text" id="form_time" class="form-control form-dark" value="<?= date('h:i A') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="form_subject">Subject Code</label>
                                <input type="text" id="form_subject" class="form-control form-dark" placeholder="e.g. SIA-201" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="form_expected">Expected Enrollees</label>
                                <input type="number" id="form_expected" class="form-control form-dark" placeholder="e.g. 45" min="0" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-7" for="form_officer">Monitoring Officer</label>
                                <input type="text" id="form_officer" class="form-control form-dark" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top" style="border-color: var(--dal-border) !important;">
                            <button type="submit" class="btn btn-primary rounded-2 px-4"><i class="fas fa-play me-2"></i>Begin Monitoring Session</button>
                        </div>
                    </form>
                </div>

                <!-- STEP 2: PRESENCE CHECK -->
                <div class="workflow-panel d-none" data-panel="PRESENCE_CHECK">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-question-circle me-2 text-primary"></i>Presence Check</h5>
                        <span class="badge bg-secondary" id="pc_session_id"></span>
                    </div>
                    <p>Is <strong id="pc_faculty_name">Professor</strong> present for <strong id="pc_subject"></strong> in <strong id="pc_room"></strong>?</p>
                    <div class="row g-3 my-3">
                        <div class="col-md-4">
                            <button type="button" class="btn w-100 p-3 text-start btn-branch" data-branch="PRESENT" style="background-color: var(--dal-card-2); border: 1px solid #10b981; color: #34d399;">
                                <div class="fw-bold fs-6 mb-1"><i class="fas fa-user-check me-2"></i>Professor Present</div>
                                <small class="opacity-75 d-block">Proceed to Professor Signature.</small>
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn w-100 p-3 text-start btn-branch" data-branch="LATE" style="background-color: var(--dal-card-2); border: 1px solid #f59e0b; color: #fbbf24;">
                                <div class="fw-bold fs-6 mb-1"><i class="fas fa-user-clock me-2"></i>Professor Late</div>
                                <small class="opacity-75 d-block">Arrived late.</small>
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn w-100 p-3 text-start btn-branch" data-branch="ABSENT" style="background-color: var(--dal-card-2); border: 1px solid #ef4444; color: #f87171;">
                                <div class="fw-bold fs-6 mb-1"><i class="fas fa-user-times me-2"></i>Professor Absent</div>
                                <small class="opacity-75 d-block">Flag absent & Mayor signature.</small>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3A: PROF SIGNATURE -->
                <div class="workflow-panel d-none" data-panel="PROF_SIGNATURE">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color: #34d399;"><i class="fas fa-file-signature me-2"></i>Professor Signature</h5>
                        <div class="d-flex align-items-center gap-2">
                            <label for="profSigColor" class="small mb-0">Pen color</label>
                            <input type="color" id="profSigColor" value="#34d399" style="width:36px; height:32px; border:none; border-radius:6px; cursor:pointer; background:transparent;">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="sig-canvas-wrap position-relative">
                                <canvas id="profSignatureCanvas" style="width:100%; height:100%; touch-action:none; cursor:crosshair;"></canvas>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="button" id="prof_sig_clear" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eraser me-1"></i>Clear</button>
                                <button type="button" id="prof_sig_save" class="btn btn-success rounded-2"><i class="fas fa-check me-2"></i>Validate</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-dark-inner p-3 rounded-2 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase opacity-75">Session Details</h6>
                                <p class="mb-1 fs-7"><strong>Prof:</strong> <span id="ps_prof"></span></p>
                                <p class="mb-1 fs-7"><strong>Subject:</strong> <span id="ps_subj"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3B: MAYOR SIGNATURE -->
                <div class="workflow-panel d-none" data-panel="MAYOR_SIGNATURE">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color: #fbbf24;"><i class="fas fa-file-signature me-2"></i>Mayor Signature</h5>
                        <div class="d-flex align-items-center gap-2">
                            <label for="mayorSigColor" class="small mb-0">Pen color</label>
                            <input type="color" id="mayorSigColor" value="#fbbf24" style="width:36px; height:32px; border:none; border-radius:6px; cursor:pointer; background:transparent;">
                        </div>
                    </div>
                    <div class="alert fs-7 py-2" style="background-color: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Professor marked absent. Class Mayor signature required.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="sig-canvas-wrap position-relative">
                                <canvas id="mayorSignatureCanvas" style="width:100%; height:100%; touch-action:none; cursor:crosshair;"></canvas>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="button" id="mayor_sig_clear" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eraser me-1"></i>Clear</button>
                                <button type="button" id="mayor_sig_save" class="btn btn-warning rounded-2"><i class="fas fa-check me-2"></i>Validate</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-dark-inner p-3 rounded-2 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase opacity-75">Session Details</h6>
                                <p class="mb-1 fs-7"><strong>Absent Prof:</strong> <span id="ms_prof"></span></p>
                                <p class="mb-1 fs-7"><strong>Room/Time:</strong> <span id="ms_rt"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: STUDENT COUNT -->
                <div class="workflow-panel d-none" data-panel="STUDENT_COUNT">
                    <h5 class="fw-bold mb-3"><i class="fas fa-users me-2 text-info"></i>Record Student Headcount</h5>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="card-dark-inner p-3 text-center rounded-2 mb-3">
                                <label class="form-label fw-bold fs-7 opacity-75">Present Headcount</label>
                                <div class="d-flex justify-content-center align-items-center gap-3 my-2">
                                    <button type="button" class="btn btn-outline-secondary btn-lg" id="countMinus"><i class="fas fa-minus"></i></button>
                                    <input type="number" id="studentCount" class="form-control form-dark text-center font-monospace fw-bold fs-2" style="max-width: 140px;" value="0" min="0">
                                    <button type="button" class="btn btn-outline-secondary btn-lg" id="countPlus"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="d-flex justify-content-between fs-7 mt-3">
                                    <span>Expected: <strong id="sc_expected">0</strong></span>
                                    <span>Rate: <strong style="color: #60a5fa;" id="sc_rate">0%</strong></span>
                                </div>
                                <div class="progress mt-2" style="height: 6px; background-color: var(--dal-border);">
                                    <div class="progress-bar bg-primary" id="sc_progress" style="width: 0%;"></div>
                                </div>
                            </div>
                            <button type="button" id="saveStudentCountBtn" class="btn btn-primary w-100 rounded-2"><i class="fas fa-database me-2"></i>Finalize Session</button>
                        </div>
                        <div class="col-md-5">
                            <div class="card-dark-inner p-3 rounded-2 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase opacity-75">Summary</h6>
                                <p class="mb-1 fs-7"><strong>Faculty:</strong> <span id="sc_faculty"></span></p>
                                <p class="mb-1 fs-7"><strong>Status:</strong> <span id="sc_status"></span></p>
                                <p class="mb-1 fs-7"><strong>Subject:</strong> <span id="sc_subject"></span></p>
                                <p class="mb-1 fs-7"><strong>Room:</strong> <span id="sc_room"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: COMPLETE -->
                <div class="workflow-panel d-none text-center py-4" data-panel="COMPLETE">
                    <div class="text-success mb-3"><i class="fas fa-check-circle fa-4x"></i></div>
                    <h4 class="fw-bold mb-1">Session Saved Successfully</h4>
                    <p class="mb-4 opacity-75">All validation entries have been logged to the attendance database.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" id="btnNextSession" class="btn btn-primary rounded-2"><i class="fas fa-plus-circle me-1"></i> New Room Check</button>
                        <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-toggle="modal" data-bs-target="#recordsModal"><i class="fas fa-table me-1"></i> View Records</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logs Table Sidebar -->
        <div class="col-lg-5">
            <div class="card-dark h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--dal-border) !important;">
                    <h6 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>Recent Inspection Log</h6>
                    <button class="btn btn-sm btn-header" data-bs-toggle="modal" data-bs-target="#recordsModal">View All <i class="fas fa-chevron-right ms-1"></i></button>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle mb-0 fs-7">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th>Room</th>
                                <th>Subject</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="4" class="text-center opacity-75 py-4">No sessions recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($log['faculty_name'] ?? '') ?></td>
                                        <td><span class="badge" style="background-color: var(--dal-card-2); color: var(--dal-text); border: 1px solid var(--dal-border);"><?= htmlspecialchars($log['room_code'] ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars($log['subject_code'] ?? '') ?></td>
                                        <td><?= getStatusBadgeHtml($log) ?></td>
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

<!-- Records Modal -->
<div class="modal fade" id="recordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content card-dark border-0">
            <div class="modal-header border-bottom" style="border-color: var(--dal-border) !important;">
                <h5 class="modal-title fw-bold"><i class="fas fa-table text-primary me-2"></i>Attendance & Performance Ledgers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-2 border-bottom" style="border-color: var(--dal-border) !important;" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAttendance">Attendance Logs</button></li>
                    <li class="nav-item"><button class="nav-link text-muted" data-bs-toggle="tab" data-bs-target="#tabPerformance">Faculty Performance</button></li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane fade show active" id="tabAttendance">
                        <table class="table table-dark-custom align-middle fs-7 mb-0">
                            <thead>
                                <tr>
                                    <th>Faculty</th>
                                    <th>Status</th>
                                    <th>Room</th>
                                    <th>Subject</th>
                                    <th>Headcount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr><td colspan="5" class="text-center opacity-75">No records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['faculty_name'] ?? '') ?></td>
                                            <td><?= getStatusBadgeHtml($log) ?></td>
                                            <td><?= htmlspecialchars($log['room_code'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($log['subject_code'] ?? '') ?></td>
                                            <td><?= htmlspecialchars((string) ($log['attending_students'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="tabPerformance">
                        <table class="table table-dark-custom align-middle fs-7 mb-0">
                            <thead>
                                <tr>
                                    <th>Faculty</th>
                                    <th>Sessions</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Presence %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center opacity-75">Performance summary not yet available.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>

<script>
(function() {
    'use strict';
    let currentStep = 'START_ROOM_CHECK';
    let sessionData = {};
    let activeCanvasId = null;

    const STEPS = [
        { key: 'START_ROOM_CHECK', label: 'Start Check', icon: 'fa-door-open' },
        { key: 'PRESENCE_CHECK',   label: 'Presence',    icon: 'fa-question-circle' },
        { key: 'SIGNATURE',        label: 'Signature',   icon: 'fa-file-signature' },
        { key: 'STUDENT_COUNT',    label: 'Headcount',   icon: 'fa-users' },
        { key: 'COMPLETE',         label: 'Complete',    icon: 'fa-check-double' }
    ];

    function renderStepper() {
        const track = document.getElementById('stepperTrack');
        if (!track) return;
        track.innerHTML = STEPS.map((s, idx) => {
            const isDone = STEPS.findIndex(x => x.key === currentStep) > idx;
            const isActive = s.key === currentStep || (currentStep.includes('SIGNATURE') && s.key === 'SIGNATURE');
            const stateClass = isDone ? 'completed' : (isActive ? 'active' : '');
            return `
                <div class="stepper-step text-center flex-fill ${stateClass}" data-step-key="${s.key}">
                    <div class="stepper-circle mx-auto mb-1">${idx + 1}</div>
                    <small class="fw-semibold d-block fs-7" style="opacity: ${isActive || isDone ? '1' : '0.6'};">${s.label}</small>
                </div>
            `;
        }).join('');
    }

    function switchPanel(panelKey) {
        currentStep = panelKey;
        document.querySelectorAll('.workflow-panel').forEach(p => p.classList.add('d-none'));
        const activePanel = document.querySelector(`[data-panel="${panelKey}"]`);
        if (activePanel) activePanel.classList.remove('d-none');
        renderStepper();
    }

    document.getElementById('btnNewRoomCheck')?.addEventListener('click', () => window.location.reload());
    document.getElementById('btnResetWorkflow')?.addEventListener('click', () => window.location.reload());
    document.getElementById('btnNextSession')?.addEventListener('click', () => window.location.reload());

    document.getElementById('startRoomCheckForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const facultySelect = document.getElementById('faculty_select');
        if (!facultySelect.value) {
            alert('Please select a faculty member from the list.');
            document.getElementById('faculty_search').focus();
            return;
        }
        const expectedInput = document.getElementById('form_expected');
        sessionData = {
            facultyId: facultySelect.value,
            faculty: facultySelect.options[facultySelect.selectedIndex].text,
            room: document.getElementById('form_room').value,
            time: document.getElementById('form_time').value,
            subject: document.getElementById('form_subject').value,
            expected: expectedInput ? (parseInt(expectedInput.value) || 0) : 0,
            officer: document.getElementById('form_officer').value,
            department_id: <?= json_encode($userDeptId) ?>,
            department: <?= json_encode($userDeptName) ?>,
            status: 'Pending',
            presentCount: 0,
            signature: null
        };
        document.getElementById('pc_faculty_name').textContent = sessionData.faculty;
        document.getElementById('pc_subject').textContent = sessionData.subject;
        document.getElementById('pc_room').textContent = sessionData.room;
        switchPanel('PRESENCE_CHECK');
    });

    document.querySelectorAll('.btn-branch').forEach(btn => {
        btn.addEventListener('click', function() {
            const branch = this.dataset.branch;
            sessionData.status = (branch === 'PRESENT') ? 'Present' : (branch === 'LATE') ? 'Late' : 'Absent';
            if (branch === 'PRESENT' || branch === 'LATE') {
                document.getElementById('ps_prof').textContent = sessionData.faculty;
                document.getElementById('ps_subj').textContent = sessionData.subject;
                switchPanel('PROF_SIGNATURE');
                initCanvas('profSignatureCanvas', document.getElementById('profSigColor').value);
            } else {
                document.getElementById('ms_prof').textContent = sessionData.faculty;
                document.getElementById('ms_rt').textContent = `${sessionData.room} @ ${sessionData.time}`;
                switchPanel('MAYOR_SIGNATURE');
                initCanvas('mayorSignatureCanvas', document.getElementById('mayorSigColor').value);
            }
        });
    });

    let canvasCtx = null;
    function initCanvas(id, color) {
        activeCanvasId = id;
        const canvas = document.getElementById(id);
        if (!canvas) return;
        canvas.width = canvas.parentElement.clientWidth;
        canvas.height = canvas.parentElement.clientHeight;
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = color || '#34d399';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        canvasCtx = ctx;

        let drawing = false;
        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const point = e.touches ? e.touches[0] : e;
            return { x: point.clientX - rect.left, y: point.clientY - rect.top };
        }
        canvas.onmousedown = canvas.ontouchstart = (e) => { e.preventDefault(); drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
        canvas.onmouseup = canvas.onmouseleave = canvas.ontouchend = () => { drawing = false; };
        canvas.onmousemove = canvas.ontouchmove = (e) => {
            if (!drawing) return;
            e.preventDefault();
            const p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        };
    }

    document.getElementById('profSigColor')?.addEventListener('input', function() { if (canvasCtx && activeCanvasId === 'profSignatureCanvas') canvasCtx.strokeStyle = this.value; });
    document.getElementById('mayorSigColor')?.addEventListener('input', function() { if (canvasCtx && activeCanvasId === 'mayorSignatureCanvas') canvasCtx.strokeStyle = this.value; });

    document.getElementById('prof_sig_clear')?.addEventListener('click', () => initCanvas('profSignatureCanvas', document.getElementById('profSigColor').value));
    document.getElementById('mayor_sig_clear')?.addEventListener('click', () => initCanvas('mayorSignatureCanvas', document.getElementById('mayorSigColor').value));

    function captureSignature(canvasId) {
        const canvas = document.getElementById(canvasId);
        sessionData.signature = canvas ? canvas.toDataURL('image/png') : null;
    }

    document.getElementById('prof_sig_save')?.addEventListener('click', () => { captureSignature('profSignatureCanvas'); proceedToHeadcount(); });
    document.getElementById('mayor_sig_save')?.addEventListener('click', () => { captureSignature('mayorSignatureCanvas'); proceedToHeadcount(); });

    function proceedToHeadcount() {
        document.getElementById('sc_faculty').textContent = sessionData.faculty;
        document.getElementById('sc_status').textContent = sessionData.status;
        document.getElementById('sc_subject').textContent = sessionData.subject;
        document.getElementById('sc_room').textContent = sessionData.room;
        document.getElementById('sc_expected').textContent = sessionData.expected;
        switchPanel('STUDENT_COUNT');
    }

    const studentInput = document.getElementById('studentCount');
    document.getElementById('countMinus')?.addEventListener('click', () => updateCount(-1));
    document.getElementById('countPlus')?.addEventListener('click', () => updateCount(1));
    studentInput?.addEventListener('input', () => updateCount(0));

    function updateCount(delta) {
        let val = (parseInt(studentInput.value) || 0) + delta;
        if (val < 0) val = 0;
        studentInput.value = val;
        sessionData.presentCount = val;
        const rate = sessionData.expected > 0 ? Math.round((val / sessionData.expected) * 100) : 0;
        document.getElementById('sc_rate').textContent = `${rate}%`;
        document.getElementById('sc_progress').style.width = `${Math.min(rate, 100)}%`;
    }

    document.getElementById('saveStudentCountBtn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch('<?= BASE_URL ?>/modules/faculty/controllers/AttendanceController.php?action=store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    faculty_id: sessionData.facultyId,
                    subject_code: sessionData.subject,
                    room_code: sessionData.room,
                    time_slot: sessionData.time,
                    attending_students: sessionData.presentCount,
                    expected_students: sessionData.expected,
                    status: sessionData.status,
                    signature: sessionData.signature || null,
                    department_id: sessionData.department_id,
                    department: sessionData.department
                }),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                alert('Failed to save attendance: ' + (json.message || 'Server error'));
                btn.disabled = false;
                return;
            }
            switchPanel('COMPLETE');
            document.getElementById('btnResetWorkflow').classList.remove('d-none');
        } catch (err) {
            alert('API Communication Error: ' + err.message);
            btn.disabled = false;
        }
    });

    (function initFacultySearch() {
        const searchInput = document.getElementById('faculty_search');
        const dropdownList = document.getElementById('faculty_dropdown_list');
        const noMatch = document.getElementById('faculty_no_match');
        const hiddenSelect = document.getElementById('faculty_select');
        if (!searchInput || !dropdownList || !hiddenSelect) return;
        const options = Array.from(dropdownList.querySelectorAll('.faculty-option'));

        function openDropdown() { dropdownList.style.display = 'block'; }
        function closeDropdown() { dropdownList.style.display = 'none'; }
        function filterList() {
            const term = searchInput.value.trim().toLowerCase();
            let anyVisible = false;
            options.forEach(opt => {
                const match = opt.dataset.name.toLowerCase().includes(term);
                opt.classList.toggle('d-none', !match);
                if (match) anyVisible = true;
            });
            if (noMatch) noMatch.classList.toggle('d-none', anyVisible);
        }

        searchInput.addEventListener('focus', () => { filterList(); openDropdown(); });
        searchInput.addEventListener('input', () => { hiddenSelect.value = ''; filterList(); openDropdown(); });
        options.forEach(opt => {
            opt.addEventListener('click', () => {
                hiddenSelect.value = opt.dataset.id;
                searchInput.value = opt.dataset.name;
                closeDropdown();
            });
        });
        document.addEventListener('click', e => {
            if (!e.target.closest('#faculty_search') && !e.target.closest('#faculty_dropdown_list')) closeDropdown();
        });
    })();

    renderStepper();
})();
</script>