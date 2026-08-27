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

$headDepartment = '';
$headDepartmentCode = '';

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

function getDepartmentHeadDepartment(): array
{
    global $departmentOptions;

    try {
        $pdo = db();
        if (!$pdo) {
            return ['', ''];
        }

        $userId = getCurrentUserId();
        $userEmail = trim((string) ($_SESSION['user_email'] ?? ''));
        $row = null;

        if ($userId !== null) {
            $stmt = $pdo->prepare("
                SELECT designated_department
                FROM faculty_db.faculty_profiles
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ((empty($row) || empty($row['designated_department'])) && $userEmail !== '') {
            $stmt = $pdo->prepare("
                SELECT designated_department
                FROM faculty_db.faculty_profiles
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $userEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row || empty($row['designated_department'])) {
            return ['', ''];
        }

        $department = trim((string) $row['designated_department']);

        foreach ($departmentOptions as $code => $name) {
            if (
                strcasecmp($department, $code) === 0 ||
                strcasecmp($department, $name) === 0
            ) {
                return [$name, $code];
            }
        }

        return [$department, ''];
    } catch (Throwable $e) {
        return ['', ''];
    }
}

[$headDepartment, $headDepartmentCode] = getDepartmentHeadDepartment();

// -------------------------------------------------------------------------
// 1. PROCESS FORM SUBMISSION BEFORE FETCHING PROFILES
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_department_head') {
        $pdo = null;

        try {
            requireCsrf((string) ($_POST['csrf_token'] ?? ''));

            if ($headDepartmentCode === '') {
                throw new InvalidArgumentException(
                    'Your account does not have a valid designated department.'
                );
            }

            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $middleName = trim((string) ($_POST['middle_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $suffix = trim((string) ($_POST['suffix'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $sex = strtoupper(trim((string) ($_POST['sex'] ?? '')));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $position = trim((string) ($_POST['position'] ?? 'Faculty Professor'));
            $hiredDate = trim((string) ($_POST['hired_date'] ?? ''));
            $contractualEnd = trim((string) ($_POST['contractual_end'] ?? ''));
            $employmentStatus = strtolower(
                trim((string) ($_POST['employment_status'] ?? 'regular'))
            );

            // Set pending approval status flags
            $profileStatus = 'Pending Approval';
            $accountStatus = 'pending_approval';

            if (
                $firstName === '' ||
                $lastName === '' ||
                $birthdate === '' ||
                $sex === '' ||
                $phone === '' ||
                $email === '' ||
                $hiredDate === '' ||
                $employmentStatus === ''
            ) {
                throw new InvalidArgumentException(
                    'Please fill in all required fields.'
                );
            }

            if (!in_array($sex, ['MALE', 'FEMALE'], true)) {
                throw new InvalidArgumentException('Please select a valid sex.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Please provide a valid email address.');
            }

            if (!DateTime::createFromFormat('Y-m-d', $birthdate)) {
                throw new InvalidArgumentException('Please provide a valid birthdate.');
            }

            if (!DateTime::createFromFormat('Y-m-d', $hiredDate)) {
                throw new InvalidArgumentException('Please provide a valid hired date.');
            }

            if ($employmentStatus === 'regular') {
                $contractualEnd = '';
            } elseif ($contractualEnd === '') {
                throw new InvalidArgumentException('Please provide the contractual end date.');
            }

            $pdo = db();
            if (!$pdo) {
                throw new RuntimeException('Database connection failed.');
            }

            $pdo->beginTransaction();

            $sequence = getNextFacultySequenceNumber($pdo);

            // Populate profile payload with department keys
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
                'designated_dept' => $headDepartmentCode,
                'designated_department' => $headDepartmentCode,
                'position' => $position,
                'academic_rank' => '',
                'specialization_assignment' => '',
                'coordinator_type' => '',
                'tier' => '',
                'hired_date' => $hiredDate,
                'contractual_end' => $contractualEnd,
                'employment_status' => $employmentStatus,
                'profile_status' => $profileStatus,
                'request_status' => 'pending',
                'account_status' => $accountStatus
            ];

            $profile = populateFacultyAccountFields($profile, $sequence);
            $rawPassword = buildFacultyPassword($profile['last_name'] ?? '');

            // Insert user account with pending approval status
            $userId = insertFacultyUser($pdo, $profile, $rawPassword);
            $profile['user_id'] = $userId;
            $profile['raw_password'] = $rawPassword;

            $facultyId = insertFacultyProfile($profile);

            if (!$facultyId) {
                throw new RuntimeException('Could not insert faculty profile.');
            }

            $pdo->commit();

            if (function_exists('sendFacultyPendingApprovalEmail')) {
                sendFacultyPendingApprovalEmail($email, $firstName, $lastName, $position);
            }

            $message = 'Account created successfully! It has been submitted to the Admin for final approval.';
            $messageType = 'success';
        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// -------------------------------------------------------------------------
// 2. FETCH PROFILES AFTER SUBMISSION (PRESERVES NEWLY CREATED RECORDS)
// -------------------------------------------------------------------------
$facultyProfiles = [];
if ($headDepartmentCode !== '' && function_exists('loadFacultyProfiles')) {
    foreach (loadFacultyProfiles() as $profile) {
        $profileDept = trim((string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? ''));

        if (
            strcasecmp($profileDept, $headDepartmentCode) === 0 ||
            strcasecmp($profileDept, $headDepartment) === 0
        ) {
            $facultyProfiles[] = $profile;
        }
    }
}

// -------------------------------------------------------------------------
// 3. SORT NEWEST FIRST SO PENDING CREATIONS APPEAR ON PAGE 1
// -------------------------------------------------------------------------
usort($facultyProfiles, function ($a, $b) {
    return strcmp((string) ($b['faculty_id'] ?? ''), (string) ($a['faculty_id'] ?? ''));
});

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
        <?php if ($headDepartmentCode !== ''): ?>
            <p class="text-muted mb-0">Showing faculty assigned to <strong><?= htmlspecialchars($headDepartment, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle bg-body text-body py-2 px-3 fw-bold">
            <i class="fas fa-print me-2"></i>
            Print Directory
        </button>

        <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fas fa-user-plus me-2"></i>
            Add New Account
        </button>
    </div>
</div>

<<div class="row g-3 mb-4">
    <!-- Assigned Department Scope Card -->
    <div class="col-12 col-md-6">
        <section class="card stat-card primary border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Department Scope</h6>
                    <h4 class="mb-0 fw-bold text-capitalize"><?= htmlspecialchars($headDepartment !== '' ? $headDepartment : 'Not assigned', ENT_QUOTES, 'UTF-8') ?></h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-check-circle me-1"></i><span class="text-muted fw-normal">Assigned Scope</span>
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Scope Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Total Profiles Loaded Card -->
    <div class="col-12 col-md-6">
        <section class="card stat-card primary border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Faculty</h6>
                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars((string) count($facultyProfiles), ENT_QUOTES, 'UTF-8') ?></h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-arrow-trend-up me-1"></i>Directory Ready
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> rounded-3 mb-4" role="alert">
        <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
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
                <input type="text" id="directorySearch" class="form-control bg-body border-secondary-subtle text-body" placeholder="Search departments, names, position...">
            </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <select id="deptFilter" class="form-select bg-body border-secondary-subtle text-body" disabled>
                <?php if ($headDepartmentCode !== ''): ?>
                    <option value="<?= htmlspecialchars($headDepartment, ENT_QUOTES, 'UTF-8') ?>" selected>
                        <?= htmlspecialchars($headDepartment, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php else: ?>
                    <option selected>Department Not Assigned</option>
                <?php endif; ?>
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
                
                $rawDept = (string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? '');
                $departmentLabel = getDepartmentLabel($rawDept);
                $employmentStatus = ucwords(strtolower((string) ($profile['employment_status'] ?? '')));
                
                $rawProfile = strtolower(trim((string) ($profile['profile_status'] ?? '')));
                $rawAccount = strtolower(trim((string) ($profile['account_status'] ?? '')));

                $isPending = ($rawProfile === 'pending approval' || $rawAccount === 'pending_approval' || $rawAccount === 'pending');

                $displayStatusLabel = $isPending ? 'Pending Approval' : (!empty($profile['profile_status']) ? $profile['profile_status'] : 'Active');
                $birthdate = trim((string) ($profile['birthdate'] ?? ''));
                $age = $birthdate !== '' ? computeAge($birthdate) : 0;
                $initials = trim(substr($fullName, 0, 1));
            ?>
            <div class="col-12 col-md-6 col-lg-4 faculty-card-item" data-dept="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-header <?= $isPending ? 'bg-warning text-dark' : 'bg-primary text-white' ?> py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle <?= $isPending ? 'bg-dark text-warning' : 'bg-white text-primary' ?> d-flex align-items-center justify-content-center fw-bold" style="width:44px;height:44px;">
                                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?: 'F' ?>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1 fs-6 fw-bold <?= $isPending ? 'text-dark' : 'text-white' ?> text-capitalize"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p class="mb-0 small text-capitalize <?= $isPending ? 'text-dark-50' : 'text-white-75' ?>"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $isPending ? 'bg-dark text-white' : 'bg-white text-primary' ?> text-capitalize mb-1"><?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge <?= $isPending ? 'bg-warning text-dark' : 'bg-white text-secondary' ?> text-capitalize d-block">
                                    <?= htmlspecialchars($displayStatusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
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
                        <button type="button" class="btn btn-sm btn-outline-primary view-profile-btn" data-bs-toggle="modal" data-bs-target="#viewProfileModal" data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" data-faculty-id="<?= htmlspecialchars((string) ($profile['faculty_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars((string) ($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-position="<?= htmlspecialchars((string) ($profile['position'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-department="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>" data-profile-status="<?= htmlspecialchars($displayStatusLabel, ENT_QUOTES, 'UTF-8') ?>" data-hired-date="<?= htmlspecialchars((string) ($profile['hired_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>" data-contractual-end="<?= htmlspecialchars((string) ($profile['contractual_end'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>">
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

                                <dt class="col-5 text-muted small fw-semibold">Approval Status</dt>
                                <dd class="col-7 mb-0" id="modalProfileStatus"></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-body rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3">Contact & Assignment</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Department Scope</dt>
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

<!-- Add Faculty Account Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addFacultyModalLabel">
                    <i class="fas fa-user-plus text-primary me-2"></i>Create Department Account
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">

                <div class="alert alert-warning border-warning d-flex align-items-center gap-3 rounded-3 mb-4">
                    <i class="fas fa-shield-alt fs-3 text-warning"></i>
                    <div class="small">
                        <strong>Admin Review Required:</strong> Accounts created here will automatically inherit your department scope and remain set to <strong>Pending Approval</strong> until reviewed by the Faculty Admin.
                    </div>
                </div>

                <form id="addFacultyForm" method="post">
                    <?= csrfField() ?>

                    <input type="hidden" name="action" value="add_department_head">
                    <?php if ($headDepartmentCode !== ''): ?>
                        <input type="hidden" name="designated_dept" value="<?= htmlspecialchars($headDepartmentCode, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>

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
                            <label class="form-label text-muted small fw-bold">Assigned Department Scope</label>
                            <input type="text" class="form-control bg-body-tertiary border-secondary-subtle text-body fw-bold" value="<?= htmlspecialchars($headDepartment !== '' ? $headDepartment : 'No Department Scope Assigned', ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                        </div>

                        <div class="col-6">
                            <label for="positionSelect" class="form-label text-muted small fw-bold">Assigned Role / Position</label>
                            <select id="positionSelect" name="position" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="Faculty Secretary">Faculty Secretary</option>
                                <option value="Attendance Monitoring Officer">Attendance Monitoring Officer</option>
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
                            <label class="form-label text-muted small fw-bold">Initial Account Status</label>
                            <input type="text" class="form-control bg-body border-warning text-warning fw-bold" value="Pending Admin Approval" readonly>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top border-secondary-subtle pt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle text-body px-4 py-2 fw-bold" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 fw-bold" <?= $headDepartmentCode === '' ? 'disabled' : '' ?>>
                            Submit for Approval
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
    const statusFilter = document.getElementById('statusFilter');
    const pagination = document.getElementById('directoryPagination');
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('addAge');
    const employmentStatus = document.getElementById('employmentStatus');
    const contractualEndCol = document.getElementById('contractualEndCol');
    const contractualEnd = document.getElementById('contractual_end');

    function calculateAge(value) {
        if (!value) return '';
        const birthDate = new Date(value);
        const today = new Date();
        if (Number.isNaN(birthDate.getTime()) || birthDate > today) return '';

        let age = today.getFullYear() - birthDate.getFullYear();
        const month = today.getMonth() - birthDate.getMonth();
        if (month < 0 || (month === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age >= 0 ? age : '';
    }

    function updateAge() {
        ageInput.value = calculateAge(birthdateInput.value);
    }

    function updateContractualEnd() {
        const isRegular = employmentStatus.value === 'regular';
        contractualEndCol.style.display = isRegular ? 'none' : '';
        contractualEnd.required = !isRegular;

        if (isRegular) {
            contractualEnd.value = '';
        }
    }

    function renderPagination(totalPages) {
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
                if (disabled || page === currentPage) return;
                currentPage = page;
                renderDirectory();
            });

            li.appendChild(link);
            return li;
        };

        pagination.appendChild(createPage('<i class="fas fa-chevron-left small"></i>', currentPage - 1, currentPage === 1));

        for (let page = 1; page <= totalPages; page++) {
            pagination.appendChild(createPage(page, page, false, page === currentPage));
        }

        pagination.appendChild(createPage('<i class="fas fa-chevron-right small"></i>', currentPage + 1, currentPage === totalPages));
    }

    function renderDirectory() {
        const cards = Array.from(facultyGrid.querySelectorAll('.faculty-card-item'));
        const search = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value.toLowerCase();

        const visibleCards = cards.filter(card => {
            const name = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const department = (card.dataset.dept || '').toLowerCase();
            const status = (card.dataset.status || '').toLowerCase();

            const matchesSearch = name.includes(search) || department.includes(search);
            const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

            return matchesSearch && matchesStatus;
        });

        cards.forEach(card => card.classList.add('d-none'));

        const totalPages = Math.max(1, Math.ceil(visibleCards.length / cardsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * cardsPerPage;
        const pageCards = visibleCards.slice(start, start + cardsPerPage);

        pageCards.forEach(card => card.classList.remove('d-none'));
        renderPagination(totalPages);
    }

    function setupCardModalHandlers() {
        const modal = document.getElementById('viewProfileModal');
        const modalTitle = modal.querySelector('#viewProfileModalLabel');
        const modalBody = modal.querySelector('.modal-body');

        facultyGrid.querySelectorAll('.view-profile-btn').forEach(button => {
            button.addEventListener('click', () => {
                const fullName = button.dataset.fullName || '';
                const facultyId = button.dataset.facultyId || '';
                const email = button.dataset.email || '';
                const position = button.dataset.position || '';
                const department = button.dataset.department || '';
                const status = button.dataset.status || '';
                const profileStatus = button.dataset.profileStatus || '';
                const hiredDate = button.dataset.hiredDate || '';
                const contractualEnd = button.dataset.contractualEnd || '';

                modalTitle.textContent = fullName;
                modalBody.querySelector('#modalFacultyId').textContent = facultyId || '—';
                modalBody.querySelector('#modalPosition').textContent = position || '—';
                modalBody.querySelector('#modalStatus').textContent = status || '—';
                modalBody.querySelector('#modalProfileStatus').textContent = profileStatus || '—';
                modalBody.querySelector('#modalDepartment').textContent = department || '—';
                modalBody.querySelector('#modalEmail').textContent = email || '—';
                modalBody.querySelector('#modalHiredDate').textContent = hiredDate || '—';
                modalBody.querySelector('#modalContractualEnd').textContent = contractualEnd || '—';
            });
        });
    }

    birthdateInput.addEventListener('change', updateAge);
    employmentStatus.addEventListener('change', updateContractualEnd);

    searchInput.addEventListener('input', () => {
        currentPage = 1;
        renderDirectory();
    });

    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        renderDirectory();
    });

    updateAge();
    updateContractualEnd();
    renderDirectory();
    setupCardModalHandlers();
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>