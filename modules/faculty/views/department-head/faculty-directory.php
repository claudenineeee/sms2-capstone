<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Faculty Directory';
$activeModule = 'faculty';
$activePage = 'faculty-directory';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Directory', 'url' => null]
];

// ==========================================
// 1. DATABASE CONNECTIONS (env-aware, handled by config.php)
// ==========================================
$corePdo = function_exists('db') ? db() : null;
$facultyPdo = function_exists('facultyDb') ? facultyDb() : null;

if (!$corePdo || !$facultyPdo) {
    die("<div style='padding:20px;color:red;'><strong>Database connection failed.</strong> Check DB_HOST / DB_USERNAME / DB_PASSWORD / DB_DATABASE environment variables.</div>");
}

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
    if ($birthdate === '') return 0;
    $birth = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$birth) return 0;
    return $birth->diff(new DateTime('today'))->y;
}

function getDepartmentHeadDepartment($facultyPdo): array
{
    global $departmentOptions;

    try {
        $userId = getCurrentUserId();
        $userEmail = trim((string) ($_SESSION['user_email'] ?? ''));
        $row = null;

        if ($userId !== null) {
            $stmt = $facultyPdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ((empty($row) || empty($row['designated_department'])) && $userEmail !== '') {
            $stmt = $facultyPdo->prepare("SELECT designated_department FROM faculty_profiles WHERE email = ? LIMIT 1");
            $stmt->execute([$userEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row || empty($row['designated_department'])) return ['', ''];

        $department = trim((string) $row['designated_department']);
        foreach ($departmentOptions as $code => $name) {
            if (strcasecmp($department, $code) === 0 || strcasecmp($department, $name) === 0) {
                return [$name, $code];
            }
        }
        return [$department, ''];
    } catch (Throwable $e) {
        return ['', ''];
    }
}

[$headDepartment, $headDepartmentCode] = getDepartmentHeadDepartment($facultyPdo);

// ==========================================
// 2. PROCESS FORM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_department_head') {
    try {
        requireCsrf((string) ($_POST['csrf_token'] ?? ''));

        if ($headDepartmentCode === '') {
            throw new Exception("Your account does not have a valid designated department assigned.");
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            throw new Exception("Please fill in all required fields.");
        }

        $corePdo->beginTransaction();
        
        // 1. Insert into users table (core DB)
        $tempPassword = 'TemporaryPassword123!';
        $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        $stmt = $corePdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $userId = $existing['id'];
        } else {
            $stmt = $corePdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role_key, status) VALUES (?, ?, ?, ?, 'faculty', 'active')");
            $stmt->execute([$email, $email, $hash, "$firstName $lastName"]);
            $userId = $corePdo->lastInsertId();
        }

        // 2. Check if profile already exists to prevent duplicates
        $stmtCheck = $facultyPdo->prepare("SELECT id FROM faculty_profiles WHERE email = ?");
        $stmtCheck->execute([$email]);
        $existingProfile = $stmtCheck->fetch();

        if ($existingProfile) {
            // Update existing profile instead of inserting a duplicate
            $stmtUpdate = $facultyPdo->prepare("
                UPDATE faculty_profiles SET 
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                    birthdate = ?, age = ?, sex = ?, phone = ?, 
                    academic_rank = ?, tier = ?, hired_date = ?, contractual_end = ?, 
                    employment_status = ?, specialization_assignment = ?, coordinator_type = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $firstName,
                trim((string) ($_POST['middle_name'] ?? '')),
                $lastName,
                trim((string) ($_POST['suffix'] ?? '')),
                $_POST['birthdate'] ?? null,
                computeAge($_POST['birthdate'] ?? ''),
                strtoupper($_POST['sex'] ?? 'MALE'),
                $_POST['phone'] ?? '',
                $_POST['academic_rank'] ?? '',
                $_POST['tier'] ?? null,
                $_POST['hired_date'] ?? null,
                !empty($_POST['contractual_end']) ? $_POST['contractual_end'] : null,
                strtolower($_POST['employment_status'] ?? 'regular'),
                $_POST['specialization_assignment'] ?? null,
                $_POST['coordinator_type'] ?? null,
                $existingProfile['id']
            ]);
        } else {
            // Insert new profile
            $stmt = $facultyPdo->prepare("
                INSERT INTO faculty_profiles (
                    faculty_id, first_name, middle_name, last_name, suffix, 
                    birthdate, age, sex, phone, email, 
                    designated_department, position, academic_rank, tier, 
                    hired_date, contractual_end, employment_status, 
                    profile_status, request_status, user_id,
                    specialization_assignment, coordinator_type
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?,
                    ?, ?
                )
            ");

            $facultyId = 'FAC-' . date('Y') . '-' . rand(1000, 9999);
            
            $stmt->execute([
                $facultyId,
                $firstName,
                trim((string) ($_POST['middle_name'] ?? '')),
                $lastName,
                trim((string) ($_POST['suffix'] ?? '')),
                $_POST['birthdate'] ?? null,
                computeAge($_POST['birthdate'] ?? ''),
                strtoupper($_POST['sex'] ?? 'MALE'),
                $_POST['phone'] ?? '',
                $email,
                $headDepartmentCode,
                $_POST['position'] ?? 'Faculty Professor',
                $_POST['academic_rank'] ?? '',
                $_POST['tier'] ?? null,
                $_POST['hired_date'] ?? null,
                !empty($_POST['contractual_end']) ? $_POST['contractual_end'] : null,
                strtolower($_POST['employment_status'] ?? 'regular'),
                'Pending Approval',
                'pending',
                $userId,
                $_POST['specialization_assignment'] ?? null,
                $_POST['coordinator_type'] ?? null
            ]);
        }

        $corePdo->commit();
        
        // Set flash message and REDIRECT to prevent duplicate insertion on refresh
        $_SESSION['flash_success'] = 'Account created successfully. Please wait for administrator approval.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;

    } catch (Throwable $e) {
        if ($corePdo->inTransaction()) $corePdo->rollBack();
        $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ==========================================
// 3. FETCH PROFILES
// ==========================================
$facultyProfiles = [];
$currentUserId = getCurrentUserId();
$currentUserEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

if ($headDepartmentCode !== '') {
    try {
        $stmt = $facultyPdo->prepare("
            SELECT * FROM faculty_profiles 
            WHERE LOWER(TRIM(designated_department)) = LOWER(?)
            ORDER BY id DESC
        ");
        $stmt->execute([$headDepartmentCode]);
        $allProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProfiles as $profile) {
            $profileUserId = isset($profile['user_id']) ? (int) $profile['user_id'] : 0;
            $profileEmail = strtolower(trim((string) ($profile['email'] ?? '')));
            $profilePosition = strtolower(trim((string) ($profile['position'] ?? '')));
            $rawProfileStatus = strtolower(trim((string) ($profile['profile_status'] ?? '')));

            if (strpos($rawProfileStatus, 'inactive') !== false) continue;
            if ($currentUserId && $profileUserId === (int) $currentUserId) continue;
            if ($currentUserEmail && $profileEmail === $currentUserEmail) continue;
            if (strpos($profilePosition, 'dean') !== false) continue;

            $facultyProfiles[] = $profile;
        }
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'Fetch Error: ' . $e->getMessage();
    }
}

// Retrieve flash messages
$message = $_SESSION['flash_success'] ?? '';
$messageType = 'success';
unset($_SESSION['flash_success']);

if (isset($_SESSION['flash_error'])) {
    $message = $_SESSION['flash_error'];
    $messageType = 'danger';
    unset($_SESSION['flash_error']);
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-primary me-2"></i>Faculty Directory</h1>
        <?php if ($headDepartmentCode !== ''): ?>
            <p class="text-muted mb-0">Showing faculty assigned to <strong><?= htmlspecialchars($headDepartment) ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fas fa-user-plus me-2"></i> Add New Account
        </button>
    </div>
</div>

<!-- Search & Filter -->
<div class="card bg-body border-secondary-subtle p-3 mb-4 shadow-sm">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-body-tertiary border-secondary-subtle text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="directorySearch" class="form-control bg-body border-secondary-subtle text-body" placeholder="Search names, position, email...">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <select id="statusFilter" class="form-select bg-body border-secondary-subtle text-body rounded-2 shadow-none">
                <option value="All" selected>All Statuses</option>
                <option value="Regular">Regular</option>
                <option value="Probationary">Probationary</option>
                <option value="Part-Time">Part-Time</option>
            </select>
        </div>
    </div>
</div>

<!-- Grid -->
<div class="row g-3 mb-4" id="facultyGrid">
    <?php if (count($facultyProfiles) === 0): ?>
        <div class="col-12"><div class="alert alert-info rounded-3">No faculty profiles are available yet.</div></div>
    <?php else: ?>
        <?php foreach ($facultyProfiles as $profile): ?>
            <?php
                $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
                $initials = strtoupper(substr($fullName, 0, 1));
                $isPending = (strtolower($profile['profile_status'] ?? '') === 'pending approval');
                $employmentStatus = ucwords(strtolower($profile['employment_status'] ?? ''));
                $departmentLabel = getDepartmentLabel($profile['designated_department'] ?? '');
            ?>
            <div class="col-12 col-md-6 col-lg-4 faculty-card-item" data-dept="<?= htmlspecialchars($departmentLabel) ?>" data-status="<?= htmlspecialchars($employmentStatus) ?>">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-header <?= $isPending ? 'bg-warning text-dark' : 'bg-primary text-white' ?> py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle <?= $isPending ? 'bg-dark text-warning' : 'bg-white text-primary' ?> d-flex align-items-center justify-content-center fw-bold" style="width:44px;height:44px;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1 fs-6 fw-bold <?= $isPending ? 'text-dark' : 'text-white' ?> text-capitalize"><?= htmlspecialchars($fullName) ?></h5>
                                    <p class="mb-0 small <?= $isPending ? 'text-dark-50' : 'text-white-75' ?>"><?= htmlspecialchars($departmentLabel) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $isPending ? 'bg-dark text-white' : 'bg-white text-primary' ?> text-capitalize mb-1"><?= htmlspecialchars($employmentStatus) ?></span>
                                <span class="badge <?= $isPending ? 'bg-warning text-dark' : 'bg-white text-secondary' ?> text-capitalize d-block">
                                    <?= htmlspecialchars($profile['profile_status'] ?? 'Active') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-body p-3">
                        <div class="row gx-2 gy-3">
                            <div class="col-6">
                                <div class="small text-muted">Faculty ID</div>
                                <div class="fw-semibold"><?= htmlspecialchars($profile['faculty_id'] ?? '—') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Position</div>
                                <div class="fw-semibold text-capitalize"><?= htmlspecialchars($profile['position'] ?? '—') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Hired</div>
                                <div class="fw-semibold"><?= htmlspecialchars($profile['hired_date'] ?? '—') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Contract End</div>
                                <div class="fw-semibold"><?= htmlspecialchars($profile['contractual_end'] ?? '—') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-body-tertiary border-top py-3 d-flex justify-content-between align-items-center gap-3">
                        <div class="text-truncate small text-muted" style="max-width: 180px;"><?= htmlspecialchars($profile['email'] ?? '—') ?></div>
                        <button type="button" class="btn btn-sm btn-outline-primary view-profile-btn" data-bs-toggle="modal" data-bs-target="#viewProfileModal" data-full-name="<?= htmlspecialchars($fullName) ?>" data-faculty-id="<?= htmlspecialchars($profile['faculty_id'] ?? '') ?>" data-email="<?= htmlspecialchars($profile['email'] ?? '') ?>" data-position="<?= htmlspecialchars($profile['position'] ?? '') ?>" data-department="<?= htmlspecialchars($departmentLabel) ?>" data-status="<?= htmlspecialchars($employmentStatus) ?>" data-profile-status="<?= htmlspecialchars($profile['profile_status'] ?? 'Active') ?>" data-hired-date="<?= htmlspecialchars($profile['hired_date'] ?? '—') ?>" data-contractual-end="<?= htmlspecialchars($profile['contractual_end'] ?? '—') ?>">
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
<div class="modal fade" id="viewProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-secondary-subtle shadow-sm">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="viewProfileModalLabel"></h5>
                    <p class="mb-0 small text-white-75">Faculty Profile Details</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 px-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="bg-body rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3">Primary Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Faculty ID</dt><dd class="col-7 mb-3" id="modalFacultyId"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Position</dt><dd class="col-7 mb-3" id="modalPosition"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Employment Status</dt><dd class="col-7 mb-3" id="modalStatus"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Approval Status</dt><dd class="col-7 mb-0" id="modalProfileStatus"></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-body rounded-4 p-3 h-100 shadow-sm">
                            <h6 class="fw-semibold mb-3">Contact & Assignment</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small fw-semibold">Department Scope</dt><dd class="col-7 mb-3" id="modalDepartment"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Email</dt><dd class="col-7 mb-3" id="modalEmail"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Hired Date</dt><dd class="col-7 mb-3" id="modalHiredDate"></dd>
                                <dt class="col-5 text-muted small fw-semibold">Contract End</dt><dd class="col-7 mb-0" id="modalContractualEnd"></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Add Faculty Account Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addFacultyModalLabel"><i class="fas fa-user-plus text-primary me-2"></i>Create Department Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-warning d-flex align-items-center gap-3 rounded-3 mb-4">
                    <i class="fas fa-shield-alt fs-3 text-warning"></i>
                    <div class="small"><strong>Admin Review Required:</strong> Accounts created here will remain set to <strong>Pending Approval</strong> until reviewed by the Faculty Admin.</div>
                </div>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_department_head">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label text-muted small fw-bold">First Name</label><input type="text" name="first_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="First Name" required></div>
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Middle Name</label><input type="text" name="middle_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Middle Name"></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Last Name</label><input type="text" name="last_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Last Name" required></div>
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Suffix</label><input type="text" name="suffix" class="form-control bg-body border-secondary-subtle text-body" placeholder="Suffix"></div>
                    </div>

                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted small fw-bold mb-1"><i class="fas fa-calendar-day text-primary me-1"></i> Date of Birth</label>
                            <div class="input-group shadow-sm">
                                <select id="birthMonthSelect" class="form-select bg-body border-secondary-subtle text-body fw-medium" style="max-width: 42%;" required>
                                    <option value="" disabled selected hidden>Month</option>
                                    <option value="01">January</option><option value="02">February</option><option value="03">March</option>
                                    <option value="04">April</option><option value="05">May</option><option value="06">June</option>
                                    <option value="07">July</option><option value="08">August</option><option value="09">September</option>
                                    <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                                </select>
                                <input type="number" id="birthDayInput" class="form-control bg-body border-secondary-subtle text-body text-center fw-medium" placeholder="Day" min="1" max="31" required>
                                <input type="number" id="birthYearInput" class="form-control bg-body border-secondary-subtle text-body text-center fw-medium" placeholder="Year" min="1900" max="2026" required>
                            </div>
                            <input type="hidden" id="birthdate" name="birthdate" required>
                        </div>
                        <div class="col-4 col-md-2">
                            <label for="addAge" class="form-label text-muted small fw-bold mb-1">Age</label>
                            <input type="text" id="addAge" class="form-control bg-body-tertiary border-secondary-subtle text-body text-center fw-bold shadow-sm" placeholder="--" readonly>
                        </div>
                        <div class="col-8 col-md-4">
                            <label for="sex" class="form-label text-muted small fw-bold mb-1">Sex</label>
                            <select id="sex" name="sex" class="form-select bg-body border-secondary-subtle text-body shadow-sm" required><option value="MALE">Male</option><option value="FEMALE">Female</option></select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Phone</label><input type="tel" name="phone" class="form-control bg-body border-secondary-subtle text-body" placeholder="Phone Number" required></div>
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Email</label><input type="email" name="email" class="form-control bg-body border-secondary-subtle text-body" placeholder="Email Address" required></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Assigned Department</label><input type="text" class="form-control bg-body-tertiary border-secondary-subtle text-body fw-bold" value="<?= htmlspecialchars($headDepartment) ?>" readonly disabled></div>
                        <div class="col-6">
                            <label for="positionSelect" class="form-label text-muted small fw-bold">Assigned Role / Position</label>
                            <select id="positionSelect" name="position" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="Faculty Secretary">Faculty Secretary</option>
                                <option value="Attendance Monitoring Officer">Attendance Monitoring Officer</option>
                                <option value="Faculty Professor" selected>Faculty Professor</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="assignmentLabel" class="form-label text-muted small fw-bold">Specialization Assignment</label>
                            <select id="assignmentLabel" name="specialization_assignment" class="form-select bg-body border-secondary-subtle text-body">
                                <option value="" selected>-- No Special Assignment --</option>
                                <optgroup label="Technical Support Track (Advisors)">
                                    <option value="Capstone Advisor / Panelist">Capstone Advisor</option>
                                    <option value="OJT Academic Advisor">Practicum / OJT Academic Advisor</option>
                                    <option value="Systems Advisor">Systems Advisor</option>
                                    <option value="Technical Advisor">Technical Advisor</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="coordinatorType" class="form-label text-muted small fw-bold">Coordinator Type</label>
                            <select id="coordinatorType" name="coordinator_type" class="form-select bg-body border-secondary-subtle text-body">
                                <option value="" selected>-- No Coordinator Type --</option>
                                <option value="NSTP">NSTP</option><option value="OJT">OJT</option><option value="RESEARCH">RESEARCH & CAPSTONE</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="academicRankSelect" class="form-label text-muted small fw-bold">Academic Rank</label>
                            <select id="academicRankSelect" name="academic_rank" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>Select Academic Rank</option>
                                <option value="Instructor">Instructor</option><option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option><option value="Professor">Professor</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="academicTierSelect" class="form-label text-muted small fw-bold">Tier</label>
                            <select id="academicTierSelect" name="tier" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>Select Tier</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6"><label for="hired_date" class="form-label text-muted small fw-bold">Hired Date</label><input type="date" id="hired_date" name="hired_date" class="form-control bg-body border-secondary-subtle text-body" required></div>
                        <div class="col-6" id="contractualEndCol"><label for="contractual_end" class="form-label text-muted small fw-bold">Contractual End Date</label><input type="date" id="contractual_end" name="contractual_end" class="form-control bg-body border-secondary-subtle text-body"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="employmentStatus" class="form-label text-muted small fw-bold">Employment Status</label>
                            <select id="employmentStatus" name="employment_status" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="regular" selected>Regular</option><option value="probationary">Probationary</option><option value="part-time">Part-Time</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label text-muted small fw-bold">Initial Account Status</label><input type="text" class="form-control bg-body border-warning text-warning fw-bold" value="Pending Admin Approval" readonly></div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top border-secondary-subtle pt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle text-body px-4 py-2 fw-bold" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 fw-bold">Submit for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION CONTAINER -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="statusToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="statusToastIcon" class="fas fa-check-circle fs-5"></i>
                <span id="statusToastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. TOAST NOTIFICATION LOGIC
    // ==========================================
    const serverMessage = <?= json_encode($message) ?>;
    const messageType = <?= json_encode($messageType) ?>;
    
    if (serverMessage && serverMessage.trim() !== '') {
        const toastEl = document.getElementById('statusToast');
        const toastIcon = document.getElementById('statusToastIcon');
        const toastMsg = document.getElementById('statusToastMessage');
        
        if (messageType === 'success') {
            toastEl.classList.add('bg-success');
            toastIcon.className = 'fas fa-check-circle fs-5';
        } else {
            toastEl.classList.add('bg-danger');
            toastIcon.className = 'fas fa-exclamation-triangle fs-5';
        }
        
        toastMsg.textContent = serverMessage;
        
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    // ==========================================
    // 2. FORM DROPDOWN LOGIC
    // ==========================================
    const RANK_TIERS = {
        "Instructor": ["Instructor I", "Instructor II", "Instructor III"],
        "Assistant Professor": ["Assistant Professor I", "Assistant Professor II", "Assistant Professor III", "Assistant Professor IV"],
        "Associate Professor": ["Associate Professor I", "Associate Professor II", "Associate Professor III", "Associate Professor IV"],
        "Professor": ["Professor I", "Professor II", "Professor III", "Professor IV", "Professor V", "Professor VI"]
    };
    const academicRankSelect = document.getElementById('academicRankSelect');
    const academicTierSelect = document.getElementById('academicTierSelect');

    if (academicRankSelect && academicTierSelect) {
        academicRankSelect.addEventListener('change', function() {
            const tiers = RANK_TIERS[this.value] || [];
            academicTierSelect.innerHTML = '<option value="" selected disabled>Select Tier</option>' + tiers.map(t => `<option value="${t}">${t}</option>`).join('');
        });
    }

    const birthMonthSel = document.getElementById('birthMonthSelect');
    const birthDayInp = document.getElementById('birthDayInput');
    const birthYearInp = document.getElementById('birthYearInput');
    const hiddenBirthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('addAge');

    function syncBirthdateAndAge() {
        const m = birthMonthSel.value;
        let d = parseInt(birthDayInp.value, 10);
        let y = parseInt(birthYearInp.value, 10);
        if (!m || isNaN(d) || isNaN(y) || y < 1900 || y > 2026) { hiddenBirthdateInput.value = ''; ageInput.value = ''; return; }
        const dateObj = new Date(`${y}-${m}-${String(d).padStart(2, '0')}`);
        if (isNaN(dateObj.getTime())) { hiddenBirthdateInput.value = ''; ageInput.value = ''; return; }
        hiddenBirthdateInput.value = `${y}-${m}-${String(d).padStart(2, '0')}`;
        const today = new Date();
        let age = today.getFullYear() - dateObj.getFullYear();
        if (today.getMonth() < dateObj.getMonth() || (today.getMonth() === dateObj.getMonth() && today.getDate() < dateObj.getDate())) age--;
        ageInput.value = age >= 0 ? age : '';
    }
    if (birthMonthSel) { birthMonthSel.addEventListener('change', syncBirthdateAndAge); birthDayInp.addEventListener('input', syncBirthdateAndAge); birthYearInp.addEventListener('input', syncBirthdateAndAge); }

    const employmentStatus = document.getElementById('employmentStatus');
    const contractualEndCol = document.getElementById('contractualEndCol');
    const contractualEnd = document.getElementById('contractual_end');
    if (employmentStatus) {
        employmentStatus.addEventListener('change', function() {
            const isRegular = this.value === 'regular';
            contractualEndCol.style.display = isRegular ? 'none' : '';
            contractualEnd.required = !isRegular;
            if (isRegular) contractualEnd.value = '';
        });
        employmentStatus.dispatchEvent(new Event('change'));
    }

    // ==========================================
    // 3. SEARCH & PAGINATION LOGIC
    // ==========================================
    const facultyGrid = document.getElementById('facultyGrid');
    const searchInput = document.getElementById('directorySearch');
    const statusFilter = document.getElementById('statusFilter');
    const pagination = document.getElementById('directoryPagination');
    const cardsPerPage = 6;
    let currentPage = 1;

    function renderDirectory() {
        const cards = Array.from(facultyGrid.querySelectorAll('.faculty-card-item'));
        const search = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value.toLowerCase();

        const visibleCards = cards.filter(card => {
            const name = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const department = (card.dataset.dept || '').toLowerCase();
            const status = (card.dataset.status || '').toLowerCase();
            return (name.includes(search) || department.includes(search)) && (selectedStatus === 'all' || status === selectedStatus);
        });

        cards.forEach(card => card.classList.add('d-none'));
        const totalPages = Math.max(1, Math.ceil(visibleCards.length / cardsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * cardsPerPage;
        visibleCards.slice(start, start + cardsPerPage).forEach(card => card.classList.remove('d-none'));
        
        pagination.innerHTML = '';
        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); currentPage = i; renderDirectory(); });
                pagination.appendChild(li);
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => { currentPage = 1; renderDirectory(); });
        statusFilter.addEventListener('change', () => { currentPage = 1; renderDirectory(); });
        renderDirectory();
    }

    document.querySelectorAll('.view-profile-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('viewProfileModalLabel').textContent = button.dataset.fullName;
            document.getElementById('modalFacultyId').textContent = button.dataset.facultyId;
            document.getElementById('modalPosition').textContent = button.dataset.position;
            document.getElementById('modalStatus').textContent = button.dataset.status;
            document.getElementById('modalProfileStatus').textContent = button.dataset.profileStatus;
            document.getElementById('modalDepartment').textContent = button.dataset.department;
            document.getElementById('modalEmail').textContent = button.dataset.email;
            document.getElementById('modalHiredDate').textContent = button.dataset.hiredDate;
            document.getElementById('modalContractualEnd').textContent = button.dataset.contractualEnd;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>