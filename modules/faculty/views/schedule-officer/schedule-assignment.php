<?php
/**
 * Schedule Assignment
 * Purpose: Manage faculty schedule assignments without hardcoded demo data.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle    = 'Schedule Assignment';
$activeModule = 'faculty';
$activePage   = 'schedule-assignment';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Schedule Assignment', 'url' => null],
];

$summaryStats = [
    'total_classes' => 0,
    'approved_classes' => 0,
    'no_prof_classes' => 0,
    'pending_classes' => 0,
];

$scheduleRows = [];
$assignmentCards = [];
$facultyProfessors = [];
$unassignedClasses = [];
$formError = '';
$formSuccess = isset($_GET['success']) && $_GET['success'] === '1' 
    ? 'Faculty schedule assignment created successfully.' 
    : '';

try {
    $pdo = facultyDb();
    
    // Fetch faculty professors (faculty_professor role) from faculty_db
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT fp.id, fp.first_name, fp.last_name, fp.faculty_id
            FROM faculty_db.faculty_profiles fp
            WHERE LOWER(TRIM(fp.position)) LIKE '%faculty professor%'
            OR LOWER(TRIM(fp.position)) = 'faculty'
            ORDER BY fp.last_name, fp.first_name
        ");
        $stmt->execute();
        $facultyProfessors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summaryStats['total_classes'] = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.classes")->fetchColumn();
        $summaryStats['no_prof_classes'] = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.classes WHERE status = 0")->fetchColumn();

        // Count classes with professors (approved, pending, waiting for approval)
        $summaryStats['approved_classes'] = (int) $pdo->query("SELECT COUNT(DISTINCT class_id) FROM faculty_db.faculty_class_assignments WHERE status IN ('approved')")->fetchColumn();
        $summaryStats['pending_classes'] = (int) $pdo->query("SELECT COUNT(*) FROM faculty_db.faculty_class_assignments WHERE status IN ('pending', 'waiting for approval')")->fetchColumn();

        $assignmentQuery = "
            SELECT fca.id, fca.faculty_id, fca.class_id, fca.days, fca.room, fca.time, fca.units,
                   fp.first_name, fp.last_name,
                   c.students,
                   fca.status
            FROM faculty_db.faculty_class_assignments fca
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = fca.faculty_id
            LEFT JOIN faculty_db.classes c ON c.id = fca.class_id
            ORDER BY fca.id DESC
        ";
        $assignmentStmt = $pdo->query($assignmentQuery);
        $assignmentCards = $assignmentStmt ? $assignmentStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Fetch unassigned classes (status = 0)
        $classStmt = $pdo->prepare("
            SELECT id, students
            FROM faculty_db.classes
            WHERE status = 0
            ORDER BY id
        ");
        $classStmt->execute();
        $unassignedClasses = $classStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Handle assignment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_faculty_schedule'])) {
        $facultyId = (int) ($_POST['faculty_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $room = trim((string) ($_POST['room'] ?? ''));
        $days = isset($_POST['days']) && is_array($_POST['days'])
            ? array_values(array_filter(array_map('trim', $_POST['days'])))
            : [];
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));
        $units = (int) ($_POST['units'] ?? 0);
        $time = $startTime !== '' && $endTime !== '' ? $startTime . ' - ' . $endTime : '';
        $daysValue = implode(', ', $days);
        
        if ($facultyId <= 0 || $classId <= 0 || $room === '' || $daysValue === '' || $time === '' || $units < 1 || $units > 5) {
            $formError = 'Please select at least one day, both times, and a valid unit value (1-5).';
        } else {
            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO faculty_db.faculty_class_assignments (faculty_id, class_id, days, room, time, units)
                    VALUES (:faculty_id, :class_id, :days, :room, :time, :units)
                ");
                $insertStmt->execute([
                    ':faculty_id' => $facultyId,
                    ':class_id' => $classId,
                    ':days' => $daysValue,
                    ':room' => $room,
                    ':time' => $time,
                    ':units' => $units,
                ]);
                
                // Update class status to assigned (1)
                $updateStmt = $pdo->prepare("UPDATE faculty_db.classes SET status = 1 WHERE id = :id");
                $updateStmt->execute([':id' => $classId]);
                
                // PRG Pattern Redirect (Prevents duplicate entries on refresh)
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                exit();
            } catch (PDOException $e) {
                $formError = 'Unable to save assignment: ' . $e->getMessage();
                error_log('[schedule-assignment] ' . $e->getMessage());
            }
        }
    }
    
} catch (Throwable $e) {
    $formError = 'Database error: ' . $e->getMessage();
    error_log('[schedule-assignment] ' . $e->getMessage());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark"><i class="fas fa-calendar-check text-primary me-2"></i>Schedule Assignment</h1>
        <p class="text-secondary mb-0">Review and manage faculty schedule assignments.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#assignFacultyModal">
            <i class="fas fa-plus me-1"></i>Assign Faculty Schedule
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Total Class', 'value' => (int) $summaryStats['total_classes'], 'icon' => 'fas fa-calendar-alt', 'tone' => 'primary'],
        ['label' => 'Approved Class', 'value' => (int) $summaryStats['approved_classes'], 'icon' => 'fas fa-user-check', 'tone' => 'success'],
        ['label' => 'No Prof Class', 'value' => (int) $summaryStats['no_prof_classes'], 'icon' => 'fas fa-user-slash', 'tone' => 'warning'],
        ['label' => 'Pending Class', 'value' => (int) $summaryStats['pending_classes'], 'icon' => 'fas fa-hourglass-half', 'tone' => 'secondary'],
    ];
    ?>

    <?php foreach ($cards as $card): ?>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <div class="text-uppercase small text-muted fw-semibold"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="display-6 fw-bold text-<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?> mb-0"><?= (int) $card['value'] ?></div>
                    </div>
                    <div class="rounded-circle bg-<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?> d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                        <i class="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php if (!empty($assignmentCards)): ?>
        <?php foreach ($assignmentCards as $assignment): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-uppercase small text-muted fw-semibold">Faculty Assignment</div>
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars(trim(($assignment['first_name'] ?? '') . ' ' . ($assignment['last_name'] ?? '')) ?: 'Unknown Faculty', ENT_QUOTES, 'UTF-8') ?></h5>
                            </div>
                            <span class="badge rounded-pill bg-primary-subtle text-primary"><?= htmlspecialchars((string) ($assignment['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="small text-muted mb-2">Class ID: <strong class="text-dark"><?= (int) ($assignment['class_id'] ?? 0) ?></strong></div>
                        <div class="small text-muted mb-2">Students: <strong class="text-dark"><?= (int) ($assignment['students'] ?? 0) ?></strong></div>
                        <div class="small text-muted mb-2">Days: <strong class="text-dark"><?= htmlspecialchars((string) ($assignment['days'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="small text-muted mb-2">Time: <strong class="text-dark"><?= htmlspecialchars((string) ($assignment['time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="small text-muted mb-2">Room: <strong class="text-dark"><?= htmlspecialchars((string) ($assignment['room'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="small text-muted">Units: <strong class="text-dark"><?= (int) ($assignment['units'] ?? 0) ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-4">
                    No faculty assignments found in the database yet.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Weekly Calendar View -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-calendar-week me-2 text-primary"></i>Weekly Schedule View</h5>
        <small class="text-muted">Current faculty assignments across the week</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">Time Slot</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $uniqueTimeSlots = [];
                    foreach ($assignmentCards as $assignment) {
                        $time = (string) ($assignment['time'] ?? '');
                        if (!empty($time) && !in_array($time, $uniqueTimeSlots)) {
                            $uniqueTimeSlots[] = $time;
                        }
                    }
                    
                    usort($uniqueTimeSlots, function($a, $b) {
                        $aStart = explode(' - ', $a)[0] ?? '';
                        $bStart = explode(' - ', $b)[0] ?? '';
                        return strtotime($aStart) - strtotime($bStart);
                    });
                    
                    $dayShorts = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    $colors = ['bg-primary-subtle', 'bg-success-subtle', 'bg-warning-subtle', 'bg-danger-subtle', 'bg-info-subtle'];
                    ?>
                    
                    <?php if (!empty($uniqueTimeSlots)): ?>
                        <?php foreach ($uniqueTimeSlots as $timeSlot): ?>
                            <tr style="height: 80px;">
                                <td class="fw-bold text-center align-middle bg-light" style="vertical-align: middle;">
                                    <small><?= htmlspecialchars($timeSlot, ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <?php foreach ($dayShorts as $dayShort): ?>
                                    <td class="align-middle p-2" style="vertical-align: top;">
                                        <?php 
                                        $dayAssignments = array_filter($assignmentCards, function($assignment) use ($timeSlot, $dayShort) {
                                            $time = (string) ($assignment['time'] ?? '');
                                            $days = (string) ($assignment['days'] ?? '');
                                            return $time === $timeSlot && strpos($days, $dayShort) !== false;
                                        });
                                        ?>
                                        
                                        <?php foreach ($dayAssignments as $idx => $assign): ?>
                                            <div class="badge <?= $colors[$idx % count($colors)] ?> text-dark w-100 mb-1" style="font-size: 0.8rem; word-wrap: break-word;">
                                                <div>
                                                    <strong><?= htmlspecialchars(substr($assign['first_name'] ?? '', 0, 1) . '. ' . ($assign['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                                </div>
                                                <small>Class <?= (int) ($assign['class_id'] ?? 0) ?> | Room <?= htmlspecialchars((string) ($assign['room'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No classes scheduled yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="assignFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-tasks text-primary"></i>
                    Assign Faculty Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignFacultyForm" method="post" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Faculty <span class="text-danger">*</span></label>
                        <select name="faculty_id" id="faculty_id" class="form-select bg-light" required onchange="populateFacultyName()">
                            <option value="" selected disabled>Select faculty professor...</option>
                            <?php foreach ($facultyProfessors as $faculty): ?>
                                <option value="<?= (int) $faculty['id'] ?>" 
                                        data-name="<?= htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($faculty['faculty_id'] . ' - ' . $faculty['first_name'] . ' ' . $faculty['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Faculty Name</label>
                        <input type="text" id="faculty_name" class="form-control bg-light" readonly>
                        <small class="text-muted">Auto-populated when faculty is selected</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select bg-light" required>
                            <option value="" selected disabled>Select class...</option>
                            <?php foreach ($unassignedClasses as $class): ?>
                                <option value="<?= (int) $class['id'] ?>">
                                    Class <?= (int) $class['id'] ?> (<?= (int) $class['students'] ?> students)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Only unassigned classes are shown</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Room <span class="text-danger">*</span></label>
                        <input type="text" name="room" class="form-control bg-light" placeholder="e.g., Room 301" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Day(s) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php $dayOptions = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; ?>
                            <?php foreach ($dayOptions as $day): ?>
                                <label class="form-check form-check-inline border rounded px-3 py-2 bg-light">
                                    <input class="form-check-input" type="checkbox" name="days[]" value="<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="form-check-label ms-1"><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Time <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="start_time" class="form-select bg-light" required>
                                    <option value="" selected disabled>Start</option>
                                    <?php foreach (['6:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','1:00 PM','1:30 PM','2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM','5:00 PM','5:30 PM','6:00 PM','6:30 PM','7:00 PM','7:30 PM','8:00 PM'] as $slot): ?>
                                        <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="end_time" class="form-select bg-light" required>
                                    <option value="" selected disabled>End</option>
                                    <?php foreach (['7:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','1:00 PM','1:30 PM','2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM','5:00 PM','5:30 PM','6:00 PM','6:30 PM','7:00 PM','7:30 PM','8:00 PM','8:30 PM','9:00 PM'] as $slot): ?>
                                        <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-medium">Units <span class="text-danger">*</span></label>
                        <select name="units" class="form-select bg-light" required>
                            <option value="" selected disabled>Select units...</option>
                            <?php foreach ([1, 2, 3, 4, 5] as $unit): ?>
                                <option value="<?= (int) $unit ?>"><?= (int) $unit ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_faculty_schedule" class="btn btn-primary rounded-pill px-4">Assign Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function populateFacultyName() {
    const select = document.getElementById('faculty_id');
    const nameInput = document.getElementById('faculty_name');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        nameInput.value = selectedOption.getAttribute('data-name') || '';
    } else {
        nameInput.value = '';
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>