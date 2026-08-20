<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

if (file_exists(__DIR__ . '/../../controllers/faculty-data.php')) {
    require_once __DIR__ . '/../../controllers/faculty-data.php';
}

$pageTitle = 'Faculty Directory';
$activeModule = 'faculty';
$activePage = 'faculty-directory';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Directory', 'url' => null]
];

$message = '';
$messageType = 'success';

$departmentOptions = [
    'BSIT' => 'Information Technology',
    'BSCE' => 'Computer Engineering'
];

function getDepartmentLabel(string $department): string
{
    global $departmentOptions;

    $department = trim($department);
    return $departmentOptions[$department] ?? $department;
}

function computeAge(string $birthdate): int
{
    if ($birthdate === '') {
        return 0;
    }

    $birth = DateTime::createFromFormat('Y-m-d', $birthdate);

    if (!$birth) {
        return 0;
    }

    $today = new DateTime('today');
    $age = $birth->diff($today)->y;

    return $age >= 0 ? $age : 0;
}

// Fetch all faculty profiles for the Dean
$facultyProfiles = function_exists('loadFacultyProfiles') ? loadFacultyProfiles() : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_faculty' || $action === 'add_department_head') {
        $pdo = null;

        try {
            requireCsrf((string) ($_POST['csrf_token'] ?? ''));

            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $middleName = trim((string) ($_POST['middle_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $suffix = trim((string) ($_POST['suffix'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $sex = strtoupper(trim((string) ($_POST['sex'] ?? '')));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $designatedDept = trim((string) ($_POST['designated_dept'] ?? ''));
            $defaultPosition = ($action === 'add_department_head') ? 'Department Head' : 'Faculty Professor';
            $position = trim((string) ($_POST['position'] ?? $defaultPosition));
            $profileStatus = trim((string) ($_POST['profile_status'] ?? 'Active'));
            $hiredDate = trim((string) ($_POST['hired_date'] ?? ''));
            $contractualEnd = trim((string) ($_POST['contractual_end'] ?? ''));
            $employmentStatus = strtolower(
                trim((string) ($_POST['employment_status'] ?? 'regular'))
            );

            $academicRank = trim((string) ($_POST['academic_rank'] ?? ''));
            $tier = trim((string) ($_POST['tier'] ?? ''));
            $specializationAssignment = trim((string) ($_POST['specialization_assignment'] ?? ''));
            $coordinatorType = trim((string) ($_POST['coordinator_type'] ?? ''));

            if (
                $firstName === '' ||
                $lastName === '' ||
                $birthdate === '' ||
                $sex === '' ||
                $phone === '' ||
                $email === '' ||
                $designatedDept === '' ||
                $hiredDate === '' ||
                $employmentStatus === '' ||
                $profileStatus === ''
            ) {
                throw new InvalidArgumentException(
                    'Please fill in all required fields.'
                );
            }

            if (!in_array($sex, ['MALE', 'FEMALE'], true)) {
                throw new InvalidArgumentException(
                    'Please select a valid sex.'
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException(
                    'Please provide a valid email address.'
                );
            }

            $birthDateObject = DateTime::createFromFormat('Y-m-d', $birthdate);
            if (!$birthDateObject) {
                throw new InvalidArgumentException(
                    'Please provide a valid birthdate.'
                );
            }

            $hiredDateObject = DateTime::createFromFormat('Y-m-d', $hiredDate);
            if (!$hiredDateObject) {
                throw new InvalidArgumentException(
                    'Please provide a valid hired date.'
                );
            }

            if ($employmentStatus === 'regular') {
                $contractualEnd = '';
            } elseif ($contractualEnd === '') {
                throw new InvalidArgumentException(
                    'Please provide the contractual end date.'
                );
            }

            $pdo = db();
            if (!$pdo) {
                throw new RuntimeException(
                    'Database connection failed.'
                );
            }

            $pdo->beginTransaction();

            $sequence = getNextFacultySequenceNumber($pdo);

            $profile = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'sex' => $sex,
                'birthdate' => $birthdate,
                'age' => computeAge($birthdate),
                'phone' => $phone,
                'email' => $email,
                'designated_department' => $designatedDept,
                'designated_dept' => $designatedDept,
                'position' => $position,
                'academic_rank' => $academicRank,
                'specialization_assignment' => $specializationAssignment,
                'coordinator_type' => $coordinatorType,
                'tier' => $tier,
                'hired_date' => $hiredDate,
                'contractual_end' => $contractualEnd,
                'employment_status' => $employmentStatus,
                'profile_status' => $profileStatus
            ];

            $profile = populateFacultyAccountFields($profile, $sequence);
            $rawPassword = buildFacultyPassword($profile['last_name'] ?? '');

            // 1. Create user credential account in sms2_db.
            // insertFacultyUser populates $profile['user_id'] via reference.
            $created = insertFacultyUser($pdo, $profile, $rawPassword);
            if (!$created || empty($profile['user_id'])) {
                throw new RuntimeException('Could not create login user account.');
            }

            $profile['raw_password'] = $rawPassword;

            // 2. Insert faculty profile using the active PDO transaction
            if (function_exists('insertFacultyProfile')) {
                $facultyId = insertFacultyProfile($profile, $pdo);
            } else {
                throw new RuntimeException('Function insertFacultyProfile() is not defined.');
            }

            if (!$facultyId) {
                throw new RuntimeException(
                    'Could not insert faculty profile.'
                );
            }

            $pdo->commit();

            sendFacultyAccountEmail(
                $email,
                $profile['faculty_id'],
                $profile['username'],
                $rawPassword,
                $firstName,
                $lastName,
                $sex
            );

            $message = 'Profile and user account successfully registered.';
            $messageType = 'success';
            
            // Reload list after adding
            $facultyProfiles = function_exists('loadFacultyProfiles') ? loadFacultyProfiles() : [];
        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1>
            <i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>
            Faculty Directory
        </h1>
        <p class="text-muted mb-0">Overview of all college faculty profiles across departments</p>
    </div>

    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle bg-body text-body py-2 px-3 fw-bold">
            <i class="fas fa-print me-2"></i>
            Print Directory
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">Scope</p>
                    <h5 class="mb-0 fw-bold text-capitalize">College-Wide (Dean)</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between gap-3 p-4">
                <div>
                    <p class="text-muted small mb-1">Profiles Loaded</p>
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars((string) count($facultyProfiles), ENT_QUOTES, 'UTF-8') ?> profile<?= count($facultyProfiles) === 1 ? '' : 's' ?></h5>
                </div>
                <span class="badge bg-success rounded-pill py-2 px-3">Directory Ready</span>
            </div>
        </div>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> rounded-3 mb-4" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card bg-body border-secondary-subtle p-3 mb-4 shadow-sm">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-6 col-lg-8">
            <div class="input-group">
                <span class="input-group-text bg-body-tertiary border-secondary-subtle text-muted">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="directorySearch" class="form-control bg-body border-secondary-subtle text-body" placeholder="Search departments, names...">
            </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <select id="deptFilter" class="form-select bg-body border-secondary-subtle text-body">
                <option value="All" selected>All Departments</option>
                <?php foreach ($departmentOptions as $code => $name): ?>
                    <option value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
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

<div class="row g-3 mb-4" id="facultyGrid">
    <?php if (count($facultyProfiles) === 0): ?>
        <div class="col-12">
            <div class="alert alert-info rounded-3">
                No faculty profiles are available yet.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($facultyProfiles as $profile): ?>
            <?php
                $fullName = function_exists('buildFacultyFullName')
                    ? buildFacultyFullName($profile)
                    : trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['middle_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
                $departmentLabel = getDepartmentLabel((string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? ''));
                $employmentStatus = ucwords(strtolower((string) ($profile['employment_status'] ?? '')));
                $profileStatus = ucwords(strtolower((string) ($profile['profile_status'] ?? '')));
                $birthdate = trim((string) ($profile['birthdate'] ?? ''));
                $age = $birthdate !== '' ? computeAge($birthdate) : 0;
                $initials = trim(substr($fullName, 0, 1));
            ?>
            <div class="col-12 col-md-6 col-lg-4 faculty-card-item" data-dept="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>">            
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-primary text-white py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold" style="width:44px;height:44px;">
                                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?: 'F' ?>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1 fs-6 fw-bold text-white text-capitalize"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p class="mb-0 text-white-75 small text-capitalize"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-white text-primary text-capitalize mb-1"><?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-white text-secondary text-capitalize d-block"><?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                            <div>
                                <div class="small text-muted">Faculty ID</div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['faculty_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Age</div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) $age, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>

                        <div class="row gx-2 gy-3">
                            <div class="col-6">
                                <div class="small text-muted">Position</div>
                                <div class="fw-semibold text-capitalize"><?= htmlspecialchars((string) ($profile['position'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Department</div>
                                <div class="fw-semibold text-capitalize"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Hired</div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['hired_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Contract End</div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['contractual_end'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-body-tertiary border-top py-3 d-flex justify-content-between align-items-center gap-3">
                        <div class="text-truncate small text-muted" style="max-width: 180px;">
                            <?= htmlspecialchars((string) ($profile['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary view-profile-btn" data-bs-toggle="modal" data-bs-target="#viewProfileModal" data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" data-faculty-id="<?= htmlspecialchars((string) ($profile['faculty_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars((string) ($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-position="<?= htmlspecialchars((string) ($profile['position'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-department="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>" data-profile-status="<?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?>" data-hired-date="<?= htmlspecialchars((string) ($profile['hired_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>" data-contractual-end="<?= htmlspecialchars((string) ($profile['contractual_end'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>">
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
                    <h5 class="modal-title fw-bold" id="viewProfileModalLabel"></h5>
                    <p class="mb-0 small text-white-75">Faculty Profile Details</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-4 px-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="bg-body rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3">Primary Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Faculty ID</dt>
                                <dd class="col-7 mb-3" id="modalFacultyId"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Position</dt>
                                <dd class="col-7 mb-3" id="modalPosition"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Employment Status</dt>
                                <dd class="col-7 mb-3" id="modalStatus"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Academic Rank</dt>
                                <dd class="col-7 mb-3" id="modalAcademicRank"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Profile Tier</dt>
                                <dd class="col-7 mb-0" id="modalProfileTier"></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-body rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3">Contact & Assignment</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Department</dt>
                                <dd class="col-7 mb-3" id="modalDepartment"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Email</dt>
                                <dd class="col-7 mb-3" id="modalEmail"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Hired Date</dt>
                                <dd class="col-7 mb-3" id="modalHiredDate"></dd>

                                <dt class="col-5 text-muted small fw-semibold">Contract End</dt>
                                <dd class="col-7 mb-0" id="modalContractualEnd"></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addFacultyModalLabel">
                    Add Faculty Academic Registry
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <form id="addFacultyForm" method="post">
                    <?= csrfField() ?>

                    <input type="hidden" name="action" value="add_faculty">

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">First Name</label>
                            <input type="text" name="first_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="First Name" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Middle Name">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Last Name</label>
                            <input type="text" name="last_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Last Name" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Suffix</label>
                            <input type="text" name="suffix" class="form-control bg-body border-secondary-subtle text-body" placeholder="Suffix">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-4">
                            <label for="birthdate" class="form-label text-muted small fw-bold">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>

                        <div class="col-6 col-sm-4">
                            <label for="addAge" class="form-label text-muted small fw-bold">Age</label>
                            <input type="text" id="addAge" class="form-control bg-body border-secondary-subtle text-body" placeholder="Age" readonly>
                        </div>

                        <div class="col-6 col-sm-4">
                            <label for="sex" class="form-label text-muted small fw-bold">Sex</label>
                            <select id="sex" name="sex" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Phone</label>
                            <input type="tel" name="phone" class="form-control bg-body border-secondary-subtle text-body" placeholder="Phone Number" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control bg-body border-secondary-subtle text-body" placeholder="Email Address" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="addDept" class="form-label text-muted small fw-bold">Designated Department</label>
                            <select id="addDept" name="designated_dept" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" disabled selected>Select Department</option>
                                <?php foreach ($departmentOptions as $code => $name): ?>
                                    <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6">
                            <label for="positionSelect" class="form-label text-muted small fw-bold">Assigned Position</label>

                            <select id="positionSelect" name="position" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="Faculty Secretary">Faculty Secretary</option>
                                <option value="Faculty Professor" selected>Faculty Professor</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="hired_date" class="form-label text-muted small fw-bold">Hired Date</label>
                            <input type="date" id="hired_date" name="hired_date" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>

                        <div class="col-6" id="contractualEndCol">
                            <label for="contractual_end" class="form-label text-muted small fw-bold">Contractual End Date</label>
                            <input type="date" id="contractual_end" name="contractual_end" class="form-control bg-body border-secondary-subtle text-body">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="employmentStatus" class="form-label text-muted small fw-bold">Employment Status</label>

                            <select id="employmentStatus" name="employment_status" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="regular" selected>Regular</option>
                                <option value="probationary">Probationary</option>
                                <option value="part-time">Part-Time</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Profile Status</label>
                            <input type="text" name="profile_status" class="form-control bg-body border-secondary-subtle text-body" value="Active" readonly>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top border-secondary-subtle pt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle text-body px-4 py-2 fw-bold" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 fw-bold">
                            Register Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cardsPerPage = 6;
    let currentPage = 1;

    const facultyGrid = document.getElementById('facultyGrid');
    const searchInput = document.getElementById('directorySearch');
    const deptFilter = document.getElementById('deptFilter');
    const statusFilter = document.getElementById('statusFilter');
    const pagination = document.getElementById('directoryPagination');
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('addAge');
    const employmentStatus = document.getElementById('employmentStatus');
    const contractualEndCol = document.getElementById('contractualEndCol');
    const contractualEnd = document.getElementById('contractual_end');

    function calculateAge(value) {
        if (!value) {
            return '';
        }

        const birthDate = new Date(value);
        const today = new Date();

        if (Number.isNaN(birthDate.getTime()) || birthDate > today) {
            return '';
        }

        let age = today.getFullYear() - birthDate.getFullYear();
        const month = today.getMonth() - birthDate.getMonth();

        if (month < 0 || (month === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age >= 0 ? age : '';
    }

    function updateAge() {
        if (birthdateInput && ageInput) {
            ageInput.value = calculateAge(birthdateInput.value);
        }
    }

    function updateContractualEnd() {
        if (!employmentStatus || !contractualEndCol || !contractualEnd) return;
        const isRegular = employmentStatus.value === 'regular';

        contractualEndCol.style.display = isRegular ? 'none' : '';
        contractualEnd.required = !isRegular;

        if (isRegular) {
            contractualEnd.value = '';
        }
    }

    function renderPagination(totalPages) {
        if (!pagination) return;
        pagination.innerHTML = '';

        const createPage = (label, page, disabled = false, active = false) => {
            const li = document.createElement('li');

            li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

            const link = document.createElement('a');
            link.href = '#';
            link.className =
                'page-link border-secondary-subtle py-2 px-3 ' +
                (active ? 'bg-primary text-white border-primary' : 'bg-body text-body');

            link.innerHTML = label;

            link.addEventListener('click', event => {
                event.preventDefault();

                if (disabled || page === currentPage) {
                    return;
                }

                currentPage = page;
                renderDirectory();
            });

            li.appendChild(link);
            return li;
        };

        pagination.appendChild(
            createPage(
                '<i class="fas fa-chevron-left small"></i>',
                currentPage - 1,
                currentPage === 1
            )
        );

        for (let page = 1; page <= totalPages; page++) {
            pagination.appendChild(
                createPage(
                    page,
                    page,
                    false,
                    page === currentPage
                )
            );
        }

        pagination.appendChild(
            createPage(
                '<i class="fas fa-chevron-right small"></i>',
                currentPage + 1,
                currentPage === totalPages
            )
        );
    }

    function renderDirectory() {
        if (!facultyGrid) return;

        const cards = Array.from(
            facultyGrid.querySelectorAll('.faculty-card-item')
        );

        const search = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedDept = deptFilter ? deptFilter.value.toLowerCase() : 'all';
        const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

        const visibleCards = cards.filter(card => {
            const name = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const department = (card.dataset.dept || '').toLowerCase();
            const status = (card.dataset.status || '').toLowerCase();

            const matchesSearch =
                name.includes(search) ||
                department.includes(search);

            const matchesDept =
                selectedDept === 'all' ||
                department === selectedDept;

            // DEFINED AND FIXED: matchesStatus variable
            const matchesStatus =
                selectedStatus === 'all' ||
                status === selectedStatus;

            return matchesSearch && matchesDept && matchesStatus;
        });

        cards.forEach(card => {
            card.classList.add('d-none');
        });

        const totalPages = Math.max(
            1,
            Math.ceil(visibleCards.length / cardsPerPage)
        );

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * cardsPerPage;
        const pageCards = visibleCards.slice(
            start,
            start + cardsPerPage
        );

        pageCards.forEach(card => {
            card.classList.remove('d-none');
        });

        renderPagination(totalPages);
    }

    function setupCardModalHandlers() {
        const modal = document.getElementById('viewProfileModal');
        if (!modal) return;
        const modalTitle = modal.querySelector('#viewProfileModalLabel');
        const modalBody = modal.querySelector('.modal-body');

        facultyGrid.querySelectorAll('.view-profile-btn').forEach(button => {
            button.addEventListener('click', () => {
                const fullName = button.dataset.fullName || '';
                const facultyId = button.dataset.facultyId || '';
                const email = button.dataset.email || '';
                const position = button.dataset.position || '';
                const academicRank = button.dataset.academicRank || '';
                const department = button.dataset.department || '';
                const status = button.dataset.status || '';
                const tier = button.dataset.tier || '';
                const profileStatus = button.dataset.profileStatus || '';
                const hiredDate = button.dataset.hiredDate || '';
                const contractualEnd = button.dataset.contractualEnd || '';

                if (modalTitle) modalTitle.textContent = fullName;
                if (modalBody) {
                    modalBody.querySelector('#modalFacultyId').textContent = facultyId || '—';
                    modalBody.querySelector('#modalPosition').textContent = position || '—';
                    modalBody.querySelector('#modalStatus').textContent = status || '—';
                    modalBody.querySelector('#modalAcademicRank').textContent = academicRank || '—';
                    modalBody.querySelector('#modalProfileTier').textContent = tier || '—';
                    modalBody.querySelector('#modalDepartment').textContent = department || '—';
                    modalBody.querySelector('#modalEmail').textContent = email || '—';
                    modalBody.querySelector('#modalHiredDate').textContent = hiredDate || '—';
                    modalBody.querySelector('#modalContractualEnd').textContent = contractualEnd || '—';
                }
            });
        });
    }

    if (birthdateInput) birthdateInput.addEventListener('change', updateAge);
    if (employmentStatus) employmentStatus.addEventListener('change', updateContractualEnd);

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            renderDirectory();
        });
    }

    if (deptFilter) {
        deptFilter.addEventListener('change', () => {
            currentPage = 1;
            renderDirectory();
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            currentPage = 1;
            renderDirectory();
        });
    }

    updateAge();
    updateContractualEnd();
    renderDirectory();
    setupCardModalHandlers();
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>