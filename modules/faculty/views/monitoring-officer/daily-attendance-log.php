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
    'absent_faculty'  => 0,
    'total_students'  => 0,
];
if ($deptForStats !== '') {
    $today = date('Y-m-d');
    try {
        $recentLogs = $attendanceModel->getTodayLogs($deptForStats, $today) ?? [];
        $stats = $attendanceModel->getDepartmentStats($deptForStats, $today) ?? $stats;
    } catch (Throwable $e) {
        error_log('Attendance stats fetch failed: ' . $e->getMessage());
    }
}

$totalRecords      = $stats['total_sessions'] ?? 0;
$presentFaculty    = $stats['present_faculty'] ?? 0;
$absentFaculty     = $stats['absent_faculty'] ?? 0;
$totalStudents     = $stats['total_students'] ?? 0;
$totalExpected     = $stats['expected_students'] ?? 0;
$overallAttendance = $totalExpected > 0 ? round(($totalStudents / $totalExpected) * 100) : 0;
$presentRate       = $totalRecords > 0 ? round(($presentFaculty / $totalRecords) * 100) : 0;

$currentUserName = $_SESSION['user_name'] ?? $_SESSION['user']['full_name'] ?? 'Monitoring Officer';

// Sparkline SVG helper
function renderSparkline($data, $color) {
    $pts = array_filter($data, fn($v) => $v !== null);
    if (empty($pts)) return '';
    $w = 120; $h = 32; $pad = 2;
    $min = min(0, min($pts)); $max = max(100, max($pts)); $range = ($max - $min) ?: 1;
    $step = ($w - 2 * $pad) / (count($data) - 1);
    $path = ''; $area = ''; $idx = 0;
    foreach ($data as $v) {
        $x = $pad + $idx * $step;
        $y = $h - $pad - (($v ?? $min) - $min) / $range * ($h - 2 * $pad);
        $sep = ($idx === 0) ? 'M' : 'L';
        $path .= "$sep $x $y ";
        $area .= ($idx === 0) ? "M $x " . ($h - $pad) . " L $x $y " : "L $x $y ";
        $idx++;
    }
    $lastX = $pad + ($idx - 1) * $step;
    $area .= "L $lastX " . ($h - $pad) . " Z";
    $lastVal = end($pts);
    $lastY = $h - $pad - ($lastVal - $min) / $range * ($h - 2 * $pad);
    return "<svg class=\"w-100\" style=\"height:32px; overflow:visible;\" viewBox=\"0 0 $w $h\" preserveAspectRatio=\"none\">
        <defs><linearGradient id=\"spg_{$color}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\">
            <stop offset=\"0%\" stop-color=\"$color\" stop-opacity=\"0.25\"/>
            <stop offset=\"100%\" stop-color=\"$color\" stop-opacity=\"0\"/>
        </linearGradient></defs>
        <path d=\"$area\" fill=\"url(#spg_{$color})\"/>
        <path d=\"$path\" fill=\"none\" stroke=\"$color\" stroke-width=\"2\" stroke-linecap=\"round\"/>
        <circle cx=\"$lastX\" cy=\"$lastY\" r=\"3\" fill=\"$color\"/>
    </svg>";
}
?>

<style>
    .hero-banner {
        background: radial-gradient(1200px 400px at 100% -50%, rgba(13,110,253,0.12), transparent 60%),
                    radial-gradient(800px 300px at 0% 120%, rgba(111,66,193,0.10), transparent 55%),
                    linear-gradient(180deg, rgba(13,110,253,0.03), transparent 60%);
    }
    .stepper-circle {
        width: 40px; height: 40px; border-radius: 50%; display: flex;
        align-items: center; justify-content: center; z-index: 1; font-weight: bold;
    }
    .stepper-step {
        cursor: pointer;
        transition: transform 0.15s ease-in-out;
    }
    .stepper-step:hover .stepper-circle {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
    }
    .stepper-step:not(:last-child)::after {
        content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 2px;
        background-color: var(--bs-border-color); z-index: 0;
    }
    .stepper-step.active .stepper-circle { background-color: var(--bs-primary); color: #fff; }
    .stepper-step.completed .stepper-circle { background-color: var(--bs-success); color: #fff; }
    .sig-canvas-wrap { height: 260px; border: 2px dashed var(--bs-border-color); background: var(--bs-body-bg); }
    .workflow-panel.d-none { display: none !important; }
    .swimlane-col { border-top: 3px solid var(--bs-primary); }
    .swimlane-col.alt { border-top-color: var(--bs-success); }
    .node-card { transition: all 0.2s ease-in-out; opacity: 0.5; }
    .node-card.active { opacity: 1; border-color: var(--bs-primary) !important; box-shadow: var(--bs-box-shadow-sm); }
    .node-card.done { opacity: 0.85; border-color: var(--bs-success) !important; }
</style>

<div class="container-fluid py-3">

    <!-- Page Hero Header -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 hero-banner">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                        <i class="fas fa-circle text-primary me-1" style="font-size:0.5rem;"></i> Live Monitoring Console
                    </span>
                    <span class="badge rounded-pill bg-purple-subtle text-purple border border-purple-subtle" style="background:#f3e8ff; color:#7e22ce;">
                        <i class="fas fa-calendar-day me-1"></i> <?= date('l, F j, Y') ?>
                    </span>
                </div>
                <h2 class="h3 fw-bold mb-1">
                    <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Daily Attendance Log
                </h2>
                <p class="text-muted mb-0 small">End-to-end room check workflow with dual-role validation — Monitoring Officer, Professor, and Mayor of the Class.</p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="badge bg-body-tertiary text-dark border font-monospace" id="liveClock"><?= date('h:i:s A') ?></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#recordsModal">
                        <i class="fas fa-table me-1"></i> All Records
                    </button>
                    <button type="button" id="btnResetWorkflow" class="btn btn-outline-danger btn-sm rounded-3 d-none">
                        <i class="fas fa-rotate-left me-1"></i> Reset
                    </button>
                    <button type="button" id="btnNewRoomCheck" class="btn btn-primary btn-sm rounded-3 shadow-sm">
                        <i class="fas fa-bolt me-1"></i> Start Room Check
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold fs-7">Room Checks</span>
                        <h3 class="fw-bold mb-0" id="statTotal"><?= $totalRecords ?></h3>
                    </div>
                    <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="fas fa-clipboard-check"></i></div>
                </div>
                <div class="d-flex justify-content-between text-muted fs-7 mb-2">
                    <span>Today's sessions</span>
                    <span class="text-success fw-bold"><i class="fas fa-arrow-up"></i> +<?= $totalRecords ?></span>
                </div>
                <?= renderSparkline([20,40,30,70,50,80,90], '#0d6efd') ?>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold fs-7">Present Faculty</span>
                        <h3 class="fw-bold mb-0" id="statPresent"><?= $presentFaculty ?></h3>
                    </div>
                    <div class="p-2 bg-success-subtle text-success rounded-3"><i class="fas fa-user-check"></i></div>
                </div>
                <div class="d-flex justify-content-between text-muted fs-7 mb-2">
                    <span>Presence rate</span>
                    <span class="text-success fw-bold"><i class="fas fa-arrow-up"></i> <?= $presentRate ?>%</span>
                </div>
                <?= renderSparkline([60,70,65,85,90,95,100], '#198754') ?>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold fs-7">Absent Faculty</span>
                        <h3 class="fw-bold mb-0" id="statAbsent"><?= $absentFaculty ?></h3>
                    </div>
                    <div class="p-2 bg-warning-subtle text-warning rounded-3"><i class="fas fa-user-times"></i></div>
                </div>
                <div class="d-flex justify-content-between text-muted fs-7 mb-2">
                    <span>Unattended slots</span>
                    <span class="text-muted fw-bold"><?= $totalRecords > 0 ? (100 - $presentRate) . '%' : '0%' ?></span>
                </div>
                <?= renderSparkline([10,5,15,0,10,5,0], '#ffc107') ?>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold fs-7">Student Attendance</span>
                        <h3 class="fw-bold mb-0" id="statRate"><?= $overallAttendance ?>%</h3>
                    </div>
                    <div class="p-2 bg-danger-subtle text-danger rounded-3"><i class="fas fa-users"></i></div>
                </div>
                <div class="d-flex justify-content-between text-muted fs-7 mb-2">
                    <span>Enrollees total: <?= $totalExpected ?></span>
                    <span class="text-primary fw-bold"><?= $totalStudents ?> Present</span>
                </div>
                <?= renderSparkline([50,60,75,70,80,85,90], '#dc3545') ?>
            </div>
        </div>
    </div>

    <!-- Workflow Map & Stepper -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-3">
            <div>
                <span class="text-uppercase text-muted fw-bold fs-7">Workflow Map</span>
                <h5 class="mb-0 fw-bold">Dual-lane attendance monitoring cycle</h5>
            </div>
            <span class="badge bg-primary-subtle text-primary"><i class="fas fa-diagram-project me-1"></i> State-driven</span>
        </div>

        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between position-relative" id="stepperTrack"></div>
        </div>

        <div class="card-body p-3 bg-body-tertiary">
            
        </div>
    </div>

    <!-- Active Stage & Recent Logs -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" id="workflowStage">

                <!-- STEP 1: Start Room Check -->
                <div class="workflow-panel" data-panel="START_ROOM_CHECK">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0"><i class="fas fa-door-open me-2 text-primary"></i>Initiate Room Check</h4>
                        <span class="badge bg-primary-subtle text-primary">Step 1 of 4</span>
                    </div>
                    <form id="startRoomCheckForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="faculty_select">Faculty / Professor</label>
                                <select name="faculty_id" id="faculty_select" class="form-select" required>
                                    <option value="" disabled selected>Select instructor...</option>
                                    <?php foreach ($facultyList as $faculty): ?>
                                        <?php
                                            $fullName = htmlspecialchars(($faculty['last_name'] ?? '') . ', ' . ($faculty['first_name'] ?? ''));
                                            $pos = !empty($faculty['position']) ? ' (' . htmlspecialchars($faculty['position']) . ')' : '';
                                            $facId = htmlspecialchars($faculty['id'] ?? '');
                                        ?>
                                        <option value="<?= $facId ?>"><?= $fullName . $pos ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7" for="form_room">Room</label>
                                <input type="text" id="form_room" class="form-control" placeholder="e.g. 403-B" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7" for="form_time">Time Slot</label>
                                <input type="text" id="form_time" class="form-control" value="<?= date('h:i A') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="form_subject">Subject Code</label>
                                <input type="text" id="form_subject" class="form-control" placeholder="e.g. SIA-201" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7" for="form_expected">Expected Enrollees</label>
                                <input type="number" id="form_expected" class="form-control" placeholder="e.g. 45" min="0" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-7" for="form_officer">Monitoring Officer</label>
                                <input type="text" id="form_officer" class="form-control" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-primary rounded-3 px-4">
                                <i class="fas fa-play me-2"></i>Begin Monitoring Session
                            </button>
                        </div>
                    </form>
                </div>

                <!-- STEP 2: PRESENCE CHECK -->
                <div class="workflow-panel d-none" data-panel="PRESENCE_CHECK">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0"><i class="fas fa-question-circle me-2 text-primary"></i>Presence Check</h4>
                        <span class="badge bg-secondary-subtle text-secondary" id="pc_session_id"></span>
                    </div>
                    <p class="text-muted">Is <strong id="pc_faculty_name">Professor</strong> present for <strong id="pc_subject"></strong> in <strong id="pc_room"></strong>?</p>
                    <div class="row g-3 my-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-success w-100 p-3 text-start btn-branch" data-branch="PRESENT">
                                <div class="fw-bold fs-6 mb-1"><i class="fas fa-user-check me-2"></i>Professor Present</div>
                                <small class="text-muted d-block">Proceed to Professor Digital Signature.</small>
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-danger w-100 p-3 text-start btn-branch" data-branch="ABSENT">
                                <div class="fw-bold fs-6 mb-1"><i class="fas fa-user-times me-2"></i>Professor Absent</div>
                                <small class="text-muted d-block">Flag absent & request Class Mayor Signature.</small>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3A: PROF SIGNATURE -->
                <div class="workflow-panel d-none" data-panel="PROF_SIGNATURE">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0 text-success"><i class="fas fa-file-signature me-2"></i>Professor Signature</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label for="profSigColor" class="small text-muted mb-0">Pen color</label>
                            <input type="color" id="profSigColor" value="#22c55e" style="width:36px; height:32px; border:none; border-radius:6px; cursor:pointer; background:transparent;">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="sig-canvas-wrap rounded-3 position-relative">
                                <canvas id="profSignatureCanvas" style="width:100%; height:100%; touch-action:none; cursor:crosshair;"></canvas>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="button" id="prof_sig_clear" class="btn btn-light btn-sm"><i class="fas fa-eraser me-1"></i>Clear</button>
                                <button type="button" id="prof_sig_save" class="btn btn-success rounded-3"><i class="fas fa-check me-2"></i>Validate Signature</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-body-tertiary p-3 rounded-3 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase text-muted">Session Details</h6>
                                <p class="mb-1 fs-7"><strong>Prof:</strong> <span id="ps_prof"></span></p>
                                <p class="mb-1 fs-7"><strong>Subject:</strong> <span id="ps_subj"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3B: MAYOR SIGNATURE -->
                <div class="workflow-panel d-none" data-panel="MAYOR_SIGNATURE">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0 text-warning"><i class="fas fa-file-signature me-2"></i>Mayor Signature</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label for="mayorSigColor" class="small text-muted mb-0">Pen color</label>
                            <input type="color" id="mayorSigColor" value="#f59e0b" style="width:36px; height:32px; border:none; border-radius:6px; cursor:pointer; background:transparent;">
                        </div>
                    </div>
                    <div class="alert alert-warning fs-7 py-2"><i class="fas fa-exclamation-triangle me-2"></i>Professor marked absent. Class Mayor signature required.</div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="sig-canvas-wrap rounded-3 position-relative">
                                <canvas id="mayorSignatureCanvas" style="width:100%; height:100%; touch-action:none; cursor:crosshair;"></canvas>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="button" id="mayor_sig_clear" class="btn btn-light btn-sm"><i class="fas fa-eraser me-1"></i>Clear</button>
                                <button type="button" id="mayor_sig_save" class="btn btn-warning rounded-3"><i class="fas fa-check me-2"></i>Validate Signature</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-body-tertiary p-3 rounded-3 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase text-muted">Session Details</h6>
                                <p class="mb-1 fs-7"><strong>Absent Prof:</strong> <span id="ms_prof"></span></p>
                                <p class="mb-1 fs-7"><strong>Room/Time:</strong> <span id="ms_rt"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: STUDENT COUNT -->
                <div class="workflow-panel d-none" data-panel="STUDENT_COUNT">
                    <h4 class="fw-bold mb-3"><i class="fas fa-users me-2 text-purple"></i>Record Student Headcount</h4>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="card border p-3 text-center rounded-3 mb-3">
                                <label class="form-label text-muted fw-bold fs-7">Present Headcount</label>
                                <div class="d-flex justify-content-center align-items-center gap-3 my-2">
                                    <button type="button" class="btn btn-outline-secondary btn-lg" id="countMinus"><i class="fas fa-minus"></i></button>
                                    <input type="number" id="studentCount" class="form-control form-control-lg text-center font-monospace fw-bold fs-2" style="max-width: 140px;" value="0" min="0">
                                    <button type="button" class="btn btn-outline-secondary btn-lg" id="countPlus"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="d-flex justify-content-between fs-7 text-muted mt-3">
                                    <span>Expected: <strong id="sc_expected">0</strong></span>
                                    <span>Rate: <strong class="text-primary" id="sc_rate">0%</strong></span>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <div class="progress-bar bg-primary" id="sc_progress" style="width: 0%;"></div>
                                </div>
                            </div>
                            <button type="button" id="saveStudentCountBtn" class="btn btn-primary w-100 rounded-3"><i class="fas fa-database me-2"></i>Finalize Session</button>
                        </div>
                        <div class="col-md-5">
                            <div class="bg-body-tertiary p-3 rounded-3 h-100">
                                <h6 class="fw-bold fs-7 text-uppercase text-muted">Summary</h6>
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
                    <h3 class="fw-bold mb-1">Session Saved Successfully</h3>
                    <p class="text-muted mb-4">All validation entries have been logged to the attendance database.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" id="btnNextSession" class="btn btn-primary rounded-3"><i class="fas fa-plus-circle me-1"></i> New Room Check</button>
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-toggle="modal" data-bs-target="#recordsModal"><i class="fas fa-table me-1"></i> View Records</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recent Logs Table Sidebar -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-3">
                    <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-history text-primary me-2"></i>Recent Logs</h5>
                    <span class="badge bg-primary-subtle text-primary"><span id="logCount"><?= count($recentLogs) ?></span> records</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead class="table-light">
                            <tr>
                                <th>Faculty</th>
                                <th>Status</th>
                                <th>Room/Subj</th>
                                <th class="text-end">Rate</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No sessions recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <?php
                                        $badgeClass = ($log['status'] ?? '') === 'Present' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                        $rate = !empty($log['attending_students']) && !empty($log['expected_students'])
                                            ? round(($log['attending_students'] / $log['expected_students']) * 100)
                                            : '—';
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($log['faculty_name'] ?? '') ?></td>
                                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['status'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars(($log['room_code'] ?? 'N/A') . ' / ' . ($log['subject_code'] ?? '')) ?></td>
                                        <td class="text-end fw-bold text-primary"><?= is_numeric($rate) ? $rate . '%' : $rate ?></td>
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
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-table text-primary me-2"></i>Attendance & Performance Ledgers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAttendance">Attendance Logs</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPerformance">Faculty Performance</button></li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane fade show active" id="tabAttendance">
                        <table class="table table-striped align-middle fs-7 mb-0">
                            <thead>
                                <tr><th>Faculty</th><th>Status</th><th>Room</th><th>Subject</th><th>Headcount</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['faculty_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($log['status'] ?? '') ?></td>
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
                        <table class="table table-striped align-middle fs-7 mb-0">
                            <thead>
                                <tr><th>Faculty</th><th>Sessions</th><th>Present</th><th>Absent</th><th>Presence %</th></tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">Performance summary not yet available.</td></tr>
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

    setInterval(() => {
        const clock = document.getElementById('liveClock');
        if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);

function renderStepper() {
        const track = document.getElementById('stepperTrack');
        if (!track) return;

        track.innerHTML = STEPS.map((s, idx) => {
            const isDone = STEPS.findIndex(x => x.key === currentStep) > idx;
            const isActive = s.key === currentStep || (currentStep.includes('SIGNATURE') && s.key === 'SIGNATURE');
            const stateClass = isDone ? 'completed' : (isActive ? 'active' : '');
            
            return `
                <div class="stepper-step text-center flex-fill position-relative ${stateClass}" data-step-key="${s.key}">
                    <div class="stepper-circle mx-auto mb-1">${idx + 1}</div>
                    <small class="fw-semibold text-muted d-block fs-7">${s.label}</small>
                </div>
            `;
        }).join('');

        document.querySelectorAll('.node-card').forEach(card => {
            card.classList.remove('active', 'done');
            const target = card.dataset.stepLane;
            if (target === currentStep) card.classList.add('active');
        });
    }

    function switchPanel(panelKey) {
        currentStep = panelKey;
        document.querySelectorAll('.workflow-panel').forEach(p => p.classList.add('d-none'));
        const activePanel = document.querySelector(`[data-panel="${panelKey}"]`);
        if (activePanel) activePanel.classList.remove('d-none');
        renderStepper();
    }

    document.getElementById('btnNewRoomCheck')?.addEventListener('click', resetAll);

    // Step 1 Submit
    document.getElementById('startRoomCheckForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const facultySelect = document.getElementById('faculty_select');
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

    // Branch Choice
    document.querySelectorAll('.btn-branch').forEach(btn => {
        btn.addEventListener('click', function() {
            const branch = this.dataset.branch;
            sessionData.status = (branch === 'PRESENT') ? 'Present' : 'Absent';

            if (branch === 'PRESENT') {
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

    // Signature canvas — shared drawing logic, works for either canvas, with live color picking
    let canvasCtx = null;
    function initCanvas(id, color) {
        activeCanvasId = id;
        const canvas = document.getElementById(id);
        if (!canvas) return;
        canvas.width = canvas.parentElement.clientWidth;
        canvas.height = canvas.parentElement.clientHeight;
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = color || '#000';
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

    document.getElementById('profSigColor')?.addEventListener('input', function () {
        if (canvasCtx && activeCanvasId === 'profSignatureCanvas') canvasCtx.strokeStyle = this.value;
    });
    document.getElementById('mayorSigColor')?.addEventListener('input', function () {
        if (canvasCtx && activeCanvasId === 'mayorSignatureCanvas') canvasCtx.strokeStyle = this.value;
    });

    document.getElementById('prof_sig_clear')?.addEventListener('click', () => initCanvas('profSignatureCanvas', document.getElementById('profSigColor').value));
    document.getElementById('mayor_sig_clear')?.addEventListener('click', () => initCanvas('mayorSignatureCanvas', document.getElementById('mayorSigColor').value));

    function captureSignature(canvasId) {
        const canvas = document.getElementById(canvasId);
        sessionData.signature = canvas ? canvas.toDataURL('image/png') : null;
    }

    document.getElementById('prof_sig_save')?.addEventListener('click', function () {
        captureSignature('profSignatureCanvas');
        proceedToHeadcount();
    });
    document.getElementById('mayor_sig_save')?.addEventListener('click', function () {
        captureSignature('mayorSignatureCanvas');
        proceedToHeadcount();
    });

    function proceedToHeadcount() {
        document.getElementById('sc_faculty').textContent = sessionData.faculty;
        document.getElementById('sc_status').textContent = sessionData.status;
        document.getElementById('sc_subject').textContent = sessionData.subject;
        document.getElementById('sc_room').textContent = sessionData.room;
        document.getElementById('sc_expected').textContent = sessionData.expected;
        switchPanel('STUDENT_COUNT');
    }

    // Headcount Counter
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

    // Final Save — routes directly through AttendanceController
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
                alert('Failed to save attendance: ' + (json.message || 'Server returned status ' + res.status));
                btn.disabled = false;
                return;
            }

            switchPanel('COMPLETE');
            document.getElementById('btnResetWorkflow').classList.remove('d-none');
        } catch (err) {
            console.error('Fetch Error:', err);
            alert('API Communication Error: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('btnNextSession')?.addEventListener('click', resetAll);
    document.getElementById('btnResetWorkflow')?.addEventListener('click', resetAll);

    function resetAll() {
        document.getElementById('startRoomCheckForm').reset();
        document.getElementById('btnResetWorkflow').classList.add('d-none');
        switchPanel('START_ROOM_CHECK');
    }

    renderStepper();
})();
</script>