<?php
/**
 * SMS 2 - Faculty Directory View
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

// Instantiate Controller & Handle Request
$controller     = new FacultyController();
$flash          = $controller->handleAddDepartmentHead();
$deanFlash      = $controller->handleAddDean();
$facultyList    = $controller->getDirectoryList();

$departments = method_exists($controller, 'getAllDepartments')
    ? $controller->getAllDepartments()
    : [
        ['department_id' => 1, 'code' => 'BSIT',    'name' => 'Bachelor of Science in Information Technology'],
        ['department_id' => 2, 'code' => 'BSED',    'name' => 'Bachelor of Secondary Education'],
        ['department_id' => 3, 'code' => 'BS CRIM', 'name' => 'Bachelor of Science in Criminology'],
        ['department_id' => 4, 'code' => 'BSBA',    'name' => 'Bachelor of Science in Business Administration'],
    ];

/* ------------------------------------------------------------------
 | Scrub credentials from flash messages.
 | The controller may return strings like:
 |   "Department Head profile successfully registered. Username: xxx / Temp password: yyy"
 | We only want the human-readable part; credentials must never hit the UI.
 * ------------------------------------------------------------------ */
function sanitizeFlashMessage(string $msg): string
{
    if ($msg === '') {
        return '';
    }

    // Drop anything from "Username:" or "Temp password:" onwards.
    $msg = preg_replace('/\s*(Username|Temp(?:orary)?\s*password|Password)\s*:.*$/i', '', $msg);

    // Also drop parenthetical credential hints like "(Username: xxx)"
    $msg = preg_replace('/\s*\(.*?(Username|Password).*?\)/i', '', $msg);

    return trim($msg);
}

$message     = sanitizeFlashMessage($flash['message'] ?? '');
$messageType = $flash['type'] ?? 'success';

$deanMessage     = sanitizeFlashMessage($deanFlash['message'] ?? '');
$deanMessageType = $deanFlash['type'] ?? 'success';

$pageTitle    = 'Faculty Directory';
$activeModule = 'faculty';
$activePage   = 'faculty-directory';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Directory', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
    .toast-container {
        z-index: 1080;
    }
</style>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessageBody"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Faculty Directory</h1>
        <p class="text-muted mb-0">Overview of all faculty profiles within the department</p>
    </div>
    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle bg-body text-body py-2 px-3 fw-bold">
            <i class="fas fa-print me-2"></i>Print Directory
        </button>      
        <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addDeanModal">
            <i class="fas fa-user-tie me-2"></i>Add Dean
        </button>
        <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addDeptHeadModal">
            <i class="fas fa-user-shield me-2"></i>Add Department Head
        </button>
    </div>
</div>

<!-- Filter & Search Section -->
<div class="card bg-body border-secondary-subtle p-3 mb-4 shadow-sm">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-6 col-lg-8">
            <div class="input-group">
                <span class="input-group-text bg-body-tertiary border-secondary-subtle text-muted">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="directorySearch" class="form-control bg-body border-secondary-subtle text-body" placeholder="Search departments, names, positions...">
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <select id="deptFilter" class="form-select bg-body border-secondary-subtle text-body">
                <option value="All" selected>All Departments</option>
                <option value="BSIT">Information Technology</option>
                <option value="BSCE">Computer Engineering</option>
                <option value="Teacher Education">Teacher Education</option>
                <option value="Business Administration">Business Administration</option>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <select id="statusFilter" class="form-select bg-body border-secondary-subtle text-body">
                <option value="All" selected>All Statuses</option>
                <option value="Regular">Regular</option>
                <option value="Probationary">Probationary</option>
                <option value="Part-Time">Part-Time</option>
            </select>
        </div>
    </div>
</div>

<!-- Faculty Cards Grid (2 Columns x 3 Rows = 6 Cards Per Page) -->
<div class="row g-3 mb-4" id="facultyGrid">
    <?php if (empty($facultyList)): ?>
        <div class="col-12">
            <div class="alert alert-info rounded-3" role="alert">
                No faculty profiles are available yet.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($facultyList as $f): ?>
            <?php 
                $firstName = trim($f['first_name'] ?? '');
                $lastName  = trim($f['last_name'] ?? '');
                $initials  = strtoupper(
                    substr($firstName, 0, 1) . substr($lastName, 0, 1)
                );
                $fullName = trim("{$firstName} " . ($f['middle_name'] ?? '') . " {$lastName}");
                $deptName = $f['designated_department'] ?? $f['designated_dept'] ?? 'N/A';
                $empStatus = ucwords(strtolower($f['employment_status'] ?? 'Regular'));
                $profStatus = ucwords(strtolower($f['profile_status'] ?? 'Active'));
                $contractEnd = !empty($f['contractual_end']) ? $f['contractual_end'] : '—';
                $hiredDate = !empty($f['hired_date']) ? $f['hired_date'] : '—';
                $phone = $f['phone'] ?? '—';
                $email = $f['email'] ?? '—';
                $academicRank = trim((string) ($f['academic_rank'] ?? ''));
                $tier = trim((string) ($f['tier'] ?? ''));
            ?>
            <div class="col-12 col-md-6 col-lg-4 faculty-card-item"
                 data-dept="<?= htmlspecialchars($deptName) ?>"
                 data-status="<?= htmlspecialchars($empStatus) ?>">
                <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-primary text-white p-3 border-0 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3 overflow-hidden me-2">
                            <div class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 44px; height: 44px; font-size: 0.95rem;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div class="text-truncate">
                                <h6 class="mb-0 fw-bold text-truncate text-white name-field" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($fullName) ?>
                                </h6>
                                <small class="text-white-50 d-block text-truncate" style="font-size: 0.8rem;">
                                    <?= htmlspecialchars($deptName) ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                            <span class="badge bg-white text-primary rounded-2 px-2 py-1" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($empStatus) ?>
                            </span>
                            <span class="badge bg-white text-dark rounded-2 px-2 py-1" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($profStatus) ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 bg-body">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Faculty ID</span>
                                <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($f['faculty_id'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-4 text-end">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Age</span>
                                <strong class="text-dark d-block"><?= htmlspecialchars($f['age'] ?? '0') ?></strong>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Position</span>
                                <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($f['position'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Department</span>
                                <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($deptName) ?></strong>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Hired</span>
                                <strong class="text-dark d-block"><?= htmlspecialchars($hiredDate) ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block" style="font-size: 0.75rem;">Contract End</span>
                                <strong class="text-dark d-block"><?= htmlspecialchars($contractEnd) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-body border-top border-secondary-subtle p-3 d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-truncate me-2" style="max-width: 200px;" title="<?= htmlspecialchars($email) ?>">
                            <?= htmlspecialchars($email) ?>
                        </span>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary view-profile-btn py-1 px-3" 
                                data-bs-toggle="modal" 
                                data-bs-target="#viewProfileModal"
                                data-full-name="<?= htmlspecialchars($fullName) ?>"
                                data-faculty-id="<?= htmlspecialchars($f['faculty_id'] ?? '—') ?>"
                                data-email="<?= htmlspecialchars($email) ?>"
                                data-phone="<?= htmlspecialchars($phone) ?>"
                                data-position="<?= htmlspecialchars($f['position'] ?? '—') ?>"
                                data-department="<?= htmlspecialchars($deptName) ?>"
                                data-academic-rank="<?= htmlspecialchars($academicRank !== '' ? $academicRank : '—') ?>"
                                data-tier="<?= htmlspecialchars($tier !== '' ? $tier : '—') ?>"
                                data-status="<?= htmlspecialchars($empStatus) ?>"
                                data-profile-status="<?= htmlspecialchars($profStatus) ?>"
                                data-hired-date="<?= htmlspecialchars($hiredDate) ?>"
                                data-contractual-end="<?= htmlspecialchars($contractEnd) ?>">
                            View
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<nav aria-label="Directory navigation" class="d-flex justify-content-center mb-4">
    <ul class="pagination pagination-sm mb-0 shadow-sm" id="directoryPagination"></ul>
</nav>

<!-- View Profile Modal -->
<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-labelledby="viewProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-secondary-subtle shadow-sm">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="viewProfileModalLabel">Faculty Details</h5>
                    <p class="mb-0 small text-white-75">Department Faculty Profile Data</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="bg-body-tertiary rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3 text-primary"><i class="fas fa-id-card me-2"></i>Primary Details</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Faculty ID</dt>
                                <dd class="col-7 mb-3 fw-bold" id="modalFacultyId">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Position</dt>
                                <dd class="col-7 mb-3" id="modalPosition">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Academic Rank</dt>
                                <dd class="col-7 mb-3" id="modalAcademicRank">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Tier</dt>
                                <dd class="col-7 mb-3" id="modalTier">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Employment</dt>
                                <dd class="col-7 mb-3" id="modalStatus">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Profile Status</dt>
                                <dd class="col-7 mb-0" id="modalProfileStatus">—</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-body-tertiary rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3 text-primary"><i class="fas fa-building me-2"></i>Contact & Service</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Department</dt>
                                <dd class="col-7 mb-3 fw-bold" id="modalDepartment">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Email</dt>
                                <dd class="col-7 mb-3 text-truncate" id="modalEmail">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Phone</dt>
                                <dd class="col-7 mb-3" id="modalPhone">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Hired Date</dt>
                                <dd class="col-7 mb-3" id="modalHiredDate">—</dd>

                                <dt class="col-5 text-muted small fw-semibold">Contract End</dt>
                                <dd class="col-7 mb-0" id="modalContractualEnd">—</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-sm btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Component: Add Dean Registry -->
<div class="modal fade" id="addDeanModal" tabindex="-1" aria-labelledby="addDeanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addDeanModalLabel">Add Dean Registry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addDeanForm" method="post" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_dean">
                    <input type="hidden" name="position" value="Dean">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">First Name</label>
                            <input type="text" name="first_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="First Name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Middle Name">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Last Name</label>
                            <input type="text" name="last_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Last Name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Suffix</label>
                            <input type="text" name="suffix" class="form-control bg-body border-secondary-subtle text-body" placeholder="Suffix">
                        </div>
                    </div>
                               <div class="row g-3 mb-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label for="birthMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-day text-primary me-1"></i> Birthdate
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="birthMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="birthDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="birthYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2026" required>
                            </div>
                            <input type="hidden" id="birthdate" name="birthdate" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-muted small fw-bold mb-1">Age</label>
                            <input type="text" id="addAge" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Age" disabled>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-muted small fw-bold mb-1">Sex</label>
                            <select name="sex" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Phone</label>
                            <input type="tel" name="phone" class="form-control bg-body border-secondary-subtle text-body" placeholder="Phone Number" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Email</label>
                            <input type="email" name="email" class="form-control bg-body border-secondary-subtle text-body" placeholder="Email Address" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold mb-1">
                                Departments Overseen
                                <span class="text-muted fw-normal" style="font-size: 0.75rem;">(select one or more)</span>
                            </label>
                            <div id="deanDeptGroup" class="border border-secondary-subtle rounded-3 p-3 d-flex flex-wrap gap-3">
                                <?php foreach ($departments as $dept): ?>
                                    <div class="form-check">
                                        <input class="form-check-input dean-dept-checkbox" type="checkbox"
                                               name="department_ids[]"
                                               value="<?= htmlspecialchars($dept['department_id']) ?>"
                                               id="deanDept<?= htmlspecialchars($dept['department_id']) ?>">
                                        <label class="form-check-label" for="deanDept<?= htmlspecialchars($dept['department_id']) ?>">
                                            <?= htmlspecialchars($dept['code']) ?>
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;"><?= htmlspecialchars($dept['name']) ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="deanDeptError" class="text-danger small mt-1 d-none">Select at least one department.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="deanAcademicRankSelect" class="form-label text-muted small fw-bold mb-1">Academic Rank</label>
                            <select id="deanAcademicRankSelect" name="academic_rank" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>Select Academic Rank</option>
                                <option value="Instructor">Instructor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="deanAcademicTierSelect" class="form-label text-muted small fw-bold mb-1">Tier</label>
                            <select id="deanAcademicTierSelect" name="tier" class="form-select bg-body border-secondary-subtle text-body" required disabled>
                                <option value="" selected>Select Academic Rank first</option>
                            </select>
                        </div>
                    </div>

                                        <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="hiredMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-check text-primary me-1"></i> Hired Date
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="hiredMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="hiredDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="hiredYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2030" required>
                            </div>
                            <input type="hidden" id="hired_date" name="hired_date" required>
                        </div>

                        <div class="col-12 col-md-6" id="contractualEndCol">
                            <label for="contractMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-xmark text-primary me-1"></i> Contractual End Date
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="contractMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="contractDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="contractYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2030" required>
                            </div>
                            <input type="hidden" id="contractual_end" name="contractual_end" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Employment Status</label>
                            <select id="employmentStatus" name="employment_status" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="regular" selected>Regular / Permanent</option>
                                <option value="probationary">Probationary</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Profile Status</label>
                            <select name="profile_status" class="form-select bg-body border-secondary-subtle text-body" disabled>
                                <option value="Active" selected>Active</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top border-secondary-subtle pt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle text-body px-4 py-2 fw-bold" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 fw-bold">Register Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Component: Add Department Head Registry -->
<div class="modal fade" id="addDeptHeadModal" tabindex="-1" aria-labelledby="addDeptHeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addDeptHeadModalLabel">Add Department Head Registry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addDeptHeadForm" method="post" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_department_head">
                    <input type="hidden" name="position" value="Department Head">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">First Name</label>
                            <input type="text" name="first_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="First Name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Middle Name">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Last Name</label>
                            <input type="text" name="last_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Last Name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Suffix</label>
                            <input type="text" name="suffix" class="form-control bg-body border-secondary-subtle text-body" placeholder="Suffix">
                        </div>
                    </div>
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label for="deptHeadBirthMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-day text-primary me-1"></i> Birthdate
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="deptHeadBirthMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="deptHeadBirthDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="deptHeadBirthYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2026" required>
                            </div>
                            <input type="hidden" id="deptHeadBirthdate" name="birthdate" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-muted small fw-bold mb-1">Age</label>
                            <input type="text" id="deptHeadAge" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Age" disabled>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-muted small fw-bold mb-1">Sex</label>
                            <select name="sex" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Phone</label>
                            <input type="tel" name="phone" class="form-control bg-body border-secondary-subtle text-body" placeholder="Phone Number" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Email</label>
                            <input type="email" name="email" class="form-control bg-body border-secondary-subtle text-body" placeholder="Email Address" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold mb-1">Designated Department</label>
                            <select name="designated_department" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>-- Select a Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['code']) ?>"><?= htmlspecialchars($dept['code']) ?> — <?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">A Department Head oversees exactly one department. For multiple departments, use Add Dean instead.</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="deptHeadAcademicRankSelect" class="form-label text-muted small fw-bold mb-1">Academic Rank</label>
                            <select id="deptHeadAcademicRankSelect" name="academic_rank" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>Select Academic Rank</option>
                                <option value="Instructor">Instructor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="deptHeadAcademicTierSelect" class="form-label text-muted small fw-bold mb-1">Tier</label>
                            <select id="deptHeadAcademicTierSelect" name="tier" class="form-select bg-body border-secondary-subtle text-body" required disabled>
                                <option value="" selected>Select Academic Rank first</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="deptHeadHiredMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-check text-primary me-1"></i> Hired Date
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="deptHeadHiredMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="deptHeadHiredDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="deptHeadHiredYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2030" required>
                            </div>
                            <input type="hidden" id="deptHeadHiredDate" name="hired_date" required>
                        </div>

                        <div class="col-12 col-md-6" id="deptHeadContractualEndCol">
                            <label for="deptHeadContractMonthSelect" class="form-label text-muted small fw-bold mb-1">
                                <i class="fas fa-calendar-xmark text-primary me-1"></i> Contractual End Date
                            </label>
                            <div class="input-group shadow-sm">
                                <select id="deptHeadContractMonthSelect" class="form-select bg-body border-secondary-subtle text-body" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <input type="number" id="deptHeadContractDayInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="deptHeadContractYearInput" class="form-control bg-body border-secondary-subtle text-body text-center" placeholder="Year" min="1900" max="2030" required>
                            </div>
                            <input type="hidden" id="deptHeadContractualEnd" name="contractual_end" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Employment Status</label>
                            <select id="deptHeadEmploymentStatus" name="employment_status" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="regular" selected>Regular / Permanent</option>
                                <option value="probationary">Probationary</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Profile Status</label>
                            <select name="profile_status" class="form-select bg-body border-secondary-subtle text-body" disabled>
                                <option value="Active" selected>Active</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top border-secondary-subtle pt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle text-body px-4 py-2 fw-bold" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 fw-bold">Register Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const cardsPerPage = 6;
    let currentPage = 1;

    const facultyGrid = document.getElementById("facultyGrid");
    const searchInput = document.getElementById("directorySearch");
    const deptFilter = document.getElementById("deptFilter");
    const statusFilter = document.getElementById("statusFilter");
    const paginationUl = document.getElementById("directoryPagination");

    // ---- Toast helper ----
    function showDirectoryToast(message, type = 'success') {
        if (!message) return;
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toastMessageBody');
        const closeBtn = toastEl.querySelector('.btn-close');

        toastEl.className = 'toast align-items-center text-white border-0 shadow-lg';
        toastEl.style.backgroundColor = (type === 'danger' || type === 'error')
            ? '#842029'
            : (type === 'warning' ? '#664d03' : '#0f5132');

        closeBtn.classList.remove('btn-close-white');
        closeBtn.style.filter = 'invert(1) grayscale(100%) brightness(200%)';

        const iconClass = (type === 'danger' || type === 'error')
            ? 'fas fa-exclamation-circle'
            : (type === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-check-circle');

        toastBody.innerHTML = `<i class="${iconClass} fs-5 text-white"></i> <span class="text-white">${message}</span>`;

        new bootstrap.Toast(toastEl, { delay: 5000 }).show();
    }

    // Fire PHP-driven flashes as toasts
    const flashMessage     = <?= json_encode($message) ?>;
    const flashType        = <?= json_encode($messageType) ?>;
    const deanFlashMessage = <?= json_encode($deanMessage) ?>;
    const deanFlashType    = <?= json_encode($deanMessageType) ?>;

    if (flashMessage)     showDirectoryToast(flashMessage, flashType);
    if (deanFlashMessage) showDirectoryToast(deanFlashMessage, deanFlashType);

    // --- rest of the original script unchanged ---
    const birthdateInput = document.getElementById("birthdate");
    const birthMonthSel  = document.getElementById("birthMonthSelect");
    const birthDayInp    = document.getElementById("birthDayInput");
    const birthYearInp   = document.getElementById("birthYearInput");
    const ageInput       = document.getElementById("addAge");

    const hiredMonthSel  = document.getElementById("hiredMonthSelect");
    const hiredDayInp    = document.getElementById("hiredDayInput");
    const hiredYearInp   = document.getElementById("hiredYearInput");
    const hiredDateInput = document.getElementById("hired_date");

    const deptHeadBirthdateInput = document.getElementById("deptHeadBirthdate");
    const deptHeadBirthMonthSel  = document.getElementById("deptHeadBirthMonthSelect");
    const deptHeadBirthDayInp    = document.getElementById("deptHeadBirthDayInput");
    const deptHeadBirthYearInp   = document.getElementById("deptHeadBirthYearInput");
    const deptHeadAgeInput       = document.getElementById("deptHeadAge");

    const deptHeadHiredMonthSel  = document.getElementById("deptHeadHiredMonthSelect");
    const deptHeadHiredDayInp    = document.getElementById("deptHeadHiredDayInput");
    const deptHeadHiredYearInp   = document.getElementById("deptHeadHiredYearInput");
    const deptHeadHiredDateInput = document.getElementById("deptHeadHiredDate");

    const contractMonthSel  = document.getElementById("contractMonthSelect");
    const contractDayInp    = document.getElementById("contractDayInput");
    const contractYearInp   = document.getElementById("contractYearInput");
    const contractDateInput = document.getElementById("contractual_end");

    const deptHeadContractMonthSel  = document.getElementById("deptHeadContractMonthSelect");
    const deptHeadContractDayInp    = document.getElementById("deptHeadContractDayInput");
    const deptHeadContractYearInp   = document.getElementById("deptHeadContractYearInput");
    const deptHeadContractDateInput = document.getElementById("deptHeadContractualEnd");
    const employmentStatusSelect = document.getElementById("employmentStatus");
    const contractualEndCol = document.getElementById("contractualEndCol");

    const RANK_TIERS = <?= json_encode(function_exists('getAcademicRankTiers') ? getAcademicRankTiers() : []) ?>;

    function bindAcademicRankTier(rankSelectId, tierSelectId) {
        const rankSelect = document.getElementById(rankSelectId);
        const tierSelect = document.getElementById(tierSelectId);
        if (!rankSelect || !tierSelect) return;

        function populateTiers() {
            const tiers = RANK_TIERS[rankSelect.value] || [];
            if (tiers.length === 0) {
                tierSelect.disabled = true;
                tierSelect.innerHTML = '<option value="" selected>Select Academic Rank first</option>';
                return;
            }
            tierSelect.disabled = false;
            tierSelect.innerHTML = '<option value="" selected disabled>Select Tier</option>' +
                tiers.map(t => `<option value="${t}">${t}</option>`).join('');
        }

        rankSelect.addEventListener('change', populateTiers);
        populateTiers();
    }

    bindAcademicRankTier('deanAcademicRankSelect', 'deanAcademicTierSelect');
    bindAcademicRankTier('deptHeadAcademicRankSelect', 'deptHeadAcademicTierSelect');

    function computeAge(birthDateString) {
        if (!birthDateString) return '';
        const birthDate = new Date(birthDateString);
        const today = new Date();
        if (isNaN(birthDate.getTime()) || birthDate > today) return '';

        let years = today.getFullYear() - birthDate.getFullYear();
        const monthDelta = today.getMonth() - birthDate.getMonth();
        const dayDelta = today.getDate() - birthDate.getDate();

        if (monthDelta < 0 || (monthDelta === 0 && dayDelta < 0)) years -= 1;
        return years >= 0 ? years : '';
    }

    function bindSegmentGroup(monthSel, dayInp, yearInp, hiddenInput, ageField, minYear, maxYear) {
        if (!monthSel || !dayInp || !yearInp || !hiddenInput) return;

        const sync = () => {
            const m = monthSel.value;
            const d = parseInt(dayInp.value, 10);
            const y = parseInt(yearInp.value, 10);

            if (!m || isNaN(d) || isNaN(y) || y < minYear || y > maxYear) {
                hiddenInput.value = '';
                if (ageField) ageField.value = '';
                return;
            }

            const formatted = `${y}-${m}-${String(d).padStart(2, '0')}`;
            const parsed = new Date(formatted);
            if (Number.isNaN(parsed.getTime())) {
                hiddenInput.value = '';
                if (ageField) ageField.value = '';
                return;
            }

            hiddenInput.value = formatted;
            if (ageField) ageField.value = computeAge(formatted);
        };

        monthSel.addEventListener('change', sync);
        dayInp.addEventListener('input', sync);
        yearInp.addEventListener('input', sync);
    }

    bindSegmentGroup(birthMonthSel, birthDayInp, birthYearInp, birthdateInput, ageInput, 1900, 2026);
    bindSegmentGroup(hiredMonthSel, hiredDayInp, hiredYearInp, hiredDateInput, null, 1900, 2030);
    bindSegmentGroup(contractMonthSel, contractDayInp, contractYearInp, contractDateInput, null, 1900, 2030);
    bindSegmentGroup(deptHeadBirthMonthSel, deptHeadBirthDayInp, deptHeadBirthYearInp, deptHeadBirthdateInput, deptHeadAgeInput, 1900, 2026);
    bindSegmentGroup(deptHeadHiredMonthSel, deptHeadHiredDayInp, deptHeadHiredYearInp, deptHeadHiredDateInput, null, 1900, 2030);
    bindSegmentGroup(deptHeadContractMonthSel, deptHeadContractDayInp, deptHeadContractYearInp, deptHeadContractDateInput, null, 1900, 2030);

    function updateContractualEndVisibility() {
        if (!employmentStatusSelect || !contractualEndCol) return;
        const isRegular = employmentStatusSelect.value === 'regular';
        contractualEndCol.style.display = isRegular ? 'none' : 'block';

        contractualEndCol.querySelectorAll('input, select').forEach(el => {
            el.required = !isRegular;
        });

        if (isRegular) {
            if (contractDateInput) contractDateInput.value = '';
            if (contractMonthSel) contractMonthSel.value = '';
            if (contractDayInp) contractDayInp.value = '';
            if (contractYearInp) contractYearInp.value = '';
        }
    }

    if (employmentStatusSelect) {
        employmentStatusSelect.addEventListener('change', updateContractualEndVisibility);
        updateContractualEndVisibility();
    }

    const deptHeadEmploymentStatusSelect = document.getElementById("deptHeadEmploymentStatus");
    const deptHeadContractualEndCol = document.getElementById("deptHeadContractualEndCol");

    function updateDeptHeadContractualEndVisibility() {
        if (!deptHeadEmploymentStatusSelect || !deptHeadContractualEndCol) return;
        const isRegular = deptHeadEmploymentStatusSelect.value === 'regular';
        deptHeadContractualEndCol.style.display = isRegular ? 'none' : 'block';

        deptHeadContractualEndCol.querySelectorAll('input, select').forEach(el => {
            el.required = !isRegular;
        });

        if (isRegular) {
            if (deptHeadContractDateInput) deptHeadContractDateInput.value = '';
            if (deptHeadContractMonthSel) deptHeadContractMonthSel.value = '';
            if (deptHeadContractDayInp) deptHeadContractDayInp.value = '';
            if (deptHeadContractYearInp) deptHeadContractYearInp.value = '';
        }
    }

    if (deptHeadEmploymentStatusSelect) {
        deptHeadEmploymentStatusSelect.addEventListener('change', updateDeptHeadContractualEndVisibility);
        updateDeptHeadContractualEndVisibility();
    }

    const addDeanForm = document.getElementById('addDeanForm');
    const deanDeptError = document.getElementById('deanDeptError');
    if (addDeanForm) {
        addDeanForm.addEventListener('submit', function (e) {
            const checked = addDeanForm.querySelectorAll('.dean-dept-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                deanDeptError?.classList.remove('d-none');
                document.getElementById('deanDeptGroup')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                deanDeptError?.classList.add('d-none');
            }
        });
    }

    function setupViewModal() {
        const viewModal = document.getElementById('viewProfileModal');
        if (!viewModal) return;

        facultyGrid.querySelectorAll('.view-profile-btn').forEach(button => {
            button.addEventListener('click', function() {
                viewModal.querySelector('#viewProfileModalLabel').textContent = this.dataset.fullName || 'Faculty Profile';
                viewModal.querySelector('#modalFacultyId').textContent = this.dataset.facultyId || '—';
                viewModal.querySelector('#modalPosition').textContent = this.dataset.position || '—';
                viewModal.querySelector('#modalAcademicRank').textContent = this.dataset.academicRank || '—';
                viewModal.querySelector('#modalTier').textContent = this.dataset.tier || '—';
                viewModal.querySelector('#modalStatus').textContent = this.dataset.status || '—';
                viewModal.querySelector('#modalProfileStatus').textContent = this.dataset.profileStatus || '—';
                viewModal.querySelector('#modalDepartment').textContent = this.dataset.department || '—';
                viewModal.querySelector('#modalEmail').textContent = this.dataset.email || '—';
                viewModal.querySelector('#modalPhone').textContent = this.dataset.phone || '—';
                viewModal.querySelector('#modalHiredDate').textContent = this.dataset.hiredDate || '—';
                viewModal.querySelector('#modalContractualEnd').textContent = this.dataset.contractualEnd || '—';
            });
        });
    }

    function renderEngine() {
        const cards = Array.from(facultyGrid.querySelectorAll(".faculty-card-item"));
        const rawInput = searchInput.value;
        const sanitizedInput = rawInput.replace(/<\/?[^>]+(>|$)/g, "");
        const searchText = sanitizedInput.toLowerCase().trim();
        const activeDept = deptFilter.value.toLowerCase();
        const activeStatus = statusFilter.value.toLowerCase();

        let visibleCards = cards.filter(card => {
            const name = card.querySelector(".name-field").textContent.toLowerCase();
            const cardDept = (card.getAttribute("data-dept") || "").toLowerCase();
            const cardStatus = (card.getAttribute("data-status") || "").toLowerCase();

            const matchesSearch = name.includes(searchText) || cardDept.includes(searchText);
            const matchesDept = (activeDept === "all" || cardDept.includes(activeDept) || activeDept.includes(cardDept));
            const matchesStatus = (activeStatus === "all" || cardStatus === activeStatus);

            return matchesSearch && matchesDept && matchesStatus;
        });

        cards.forEach(card => card.classList.add("d-none"));

        const totalPages = Math.ceil(visibleCards.length / cardsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * cardsPerPage;
        const pageSlice = visibleCards.slice(startIndex, startIndex + cardsPerPage);

        pageSlice.forEach(card => card.classList.remove("d-none"));

        paginationUl.innerHTML = "";
        
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link bg-body border-secondary-subtle text-muted py-2 px-3" href="#"><i class="fas fa-chevron-left small"></i></a>`;
        prevLi.addEventListener("click", (e) => { e.preventDefault(); if(currentPage > 1) { currentPage--; renderEngine(); } });
        paginationUl.appendChild(prevLi);

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            li.className = `page-item ${currentPage === i ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link border-secondary-subtle py-2 px-3 ${currentPage === i ? 'bg-primary text-white border-primary' : 'bg-body text-body'}" href="#">${i}</a>`;
            li.addEventListener("click", (e) => { e.preventDefault(); currentPage = i; renderEngine(); });
            paginationUl.appendChild(li);
        }

        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link bg-body border-secondary-subtle text-muted py-2 px-3" href="#"><i class="fas fa-chevron-right small"></i></a>`;
        nextLi.addEventListener("click", (e) => { e.preventDefault(); if(currentPage < totalPages) { currentPage++; renderEngine(); } });
        paginationUl.appendChild(nextLi);
    }

    if (searchInput) searchInput.addEventListener("input", () => { currentPage = 1; renderEngine(); });
    if (deptFilter) deptFilter.addEventListener("change", () => { currentPage = 1; renderEngine(); });
    if (statusFilter) statusFilter.addEventListener("change", () => { currentPage = 1; renderEngine(); });

    setupViewModal();
    renderEngine();
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>