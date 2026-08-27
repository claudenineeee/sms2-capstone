<?php
/**
 * SMS 2 - Faculty Profile (View & Admin Approvals)
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$controller = new FacultyController();
$pdo = db();

$message = '';
$messageType = 'success';

// 1. Process Approval or Rejection POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'approve_account' && $userId > 0) {
        // Activate user in authentication DB
        $stmt1 = $pdo->prepare("UPDATE sms2_db.users SET status = 'active' WHERE id = :user_id");
        $res1 = $stmt1->execute([':user_id' => $userId]);

        // Activate user profile in faculty DB
        $stmt2 = $pdo->prepare("UPDATE faculty_db.faculty_profiles SET profile_status = 'Active' WHERE user_id = :user_id");
        $res2 = $stmt2->execute([':user_id' => $userId]);

        if ($res1 && $res2) {
            $message = 'Faculty account has been approved and activated!';
            $messageType = 'success';
        } else {
            $message = 'Failed to approve account. Please try again.';
            $messageType = 'danger';
        }
    } elseif ($action === 'reject_account' && $userId > 0) {
        // Reject user in authentication DB
        $stmt1 = $pdo->prepare("UPDATE sms2_db.users SET status = 'rejected' WHERE id = :user_id");
        $res1 = $stmt1->execute([':user_id' => $userId]);

        // Mark profile as Rejected
        $stmt2 = $pdo->prepare("UPDATE faculty_db.faculty_profiles SET profile_status = 'Rejected' WHERE user_id = :user_id");
        $res2 = $stmt2->execute([':user_id' => $userId]);

        if ($res1 && $res2) {
            $message = 'Faculty account request has been rejected.';
            $messageType = 'warning';
        } else {
            $message = 'Failed to reject account.';
            $messageType = 'danger';
        }
    } else {
        // Process standard profile updates
        $updateResult = $controller->handleUpdateFaculty();
        $message = $updateResult['message'] ?? '';
        $messageType = $updateResult['type'] ?? 'success';
    }
}

// 2. Retrieve directory list with Role-Based Scope
$rawProfiles = $controller->getDirectoryList();

// Check for common session key variations
$userRole       = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');
$userCollege    = strtoupper($_SESSION['college'] ?? $_SESSION['assigned_college'] ?? $_SESSION['college_code'] ?? '');
$userDepartment = $_SESSION['department'] ?? $_SESSION['assigned_dept'] ?? $_SESSION['dept'] ?? $_SESSION['user_dept'] ?? '';

// College to Department Mapping
$collegeScopes = [
    'CCS'  => ['BSIT', 'BSCS', 'BSCpE', 'Information Technology'],
    'CCJE' => ['BSCrim', 'Criminology'],
    'CBM'  => ['BSEM', 'BSTM', 'Business Administration'],
    'CED'  => ['BSED', 'Education'],
];

// Department Synonym Mapping for BSIT / Information Technology
$deptAliases = [
    'Information Technology' => ['BSIT', 'Information Technology'],
    'BSIT'                   => ['BSIT', 'Information Technology'],
];

if (in_array($userRole, ['admin', 'superadmin', 'administrator'], true)) {
    // Admin sees all faculty members
    $facultyProfiles = $rawProfiles;
} elseif ($userRole === 'dean') {
    // If college isn't directly mapped, default to CCS if dept is BSIT
    if (empty($userCollege) && in_array($userDepartment, ['BSIT', 'Information Technology'], true)) {
        $userCollege = 'CCS';
    }
    $allowedDepts = $collegeScopes[$userCollege] ?? [];
    
    $facultyProfiles = array_filter($rawProfiles, function ($profile) use ($allowedDepts) {
        $dept = $profile['designated_department'] ?? $profile['designated_dept'] ?? '';
        return in_array($dept, $allowedDepts, true);
    });
} elseif (in_array($userRole, ['department_head', 'head', 'dept_head', 'department head'], true)) {
    // Support both 'BSIT' and 'Information Technology' strings
    $targetDepts = $deptAliases[$userDepartment] ?? [$userDepartment];
    
    $facultyProfiles = array_filter($rawProfiles, function ($profile) use ($targetDepts) {
        $dept = $profile['designated_department'] ?? $profile['designated_dept'] ?? '';
        return in_array($dept, $targetDepts, true);
    });
} else {
    // Default fallback to all profiles if role is undefined
    $facultyProfiles = $rawProfiles; 
}

$pageTitle    = 'Faculty Profile';
$activeModule = 'faculty';
$activePage   = 'faculty-profile';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>
<script src="<?= BASE_URL ?>/../../../../assets/js/loader.js"></script>

<div class="container-fluid py-3 px-2 px-md-3">
    <!-- 1. Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-primary"></i>
                <span>Faculty Profile Directory</span>
            </h1>
            <p class="text-body-secondary small mb-0">View credentials, update personnel ranks, and process account status clearance.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show rounded-3 shadow-sm fs-7 mb-4" role="alert">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Faculty List Section -->
    <div class="card bg-body-tertiary border border-light-subtle shadow-sm rounded-4">
        <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="card-title text-body mb-0 fw-bold fs-6">
                <i class="fas fa-id-card text-info me-2"></i>Faculty Profiles & Credentials
            </h6>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 ms-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body text-body-secondary border-light-subtle">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="facultySearch" class="form-control bg-body text-body border-light-subtle shadow-none fs-7" placeholder="Search faculty..." onkeyup="onSearchInput()">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-7 text-nowrap" style="min-width: 550px;">
                    <thead>
                        <tr class="text-body-secondary border-light-subtle">
                            <th style="width: 55px;" class="text-center">Photo</th>
                            <th class="d-none d-sm-table-cell" style="width: 120px;">Faculty ID</th>
                            <th>Name</th>
                            <th class="d-none d-sm-table-cell">Department</th>
                            <th class="d-none d-md-table-cell">Position</th>
                            <th style="width: 130px;" class="text-center">Status</th>
                            <th style="width: 100px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="facultyListBody">
                        <?php if (empty($facultyProfiles)): ?>
                            <tr id="noDataRow">
                                <td colspan="7" class="text-center py-5 text-body-secondary">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-body-tertiary d-block"></i>
                                    <h5 class="fs-6 fw-bold">No faculty profiles available</h5>
                                    <p class="mb-0 fs-7">Registered personnel will appear here.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facultyProfiles as $profile): ?>
                                <?php
                                    $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
                                    $departmentLabel = FacultyController::getDepartmentLabel((string) ($profile['designated_department'] ?? $profile['designated_dept'] ?? ''));
                                    $employmentStatus = ucwords(strtolower((string) ($profile['employment_status'] ?? '')));
                                    $profileStatus = ucwords(strtolower((string) ($profile['profile_status'] ?? '')));
                                    $position = ucwords(strtolower((string) ($profile['position'] ?? '')));
                                    $email = trim((string) ($profile['email'] ?? ''));
                                    $phone = trim((string) ($profile['phone'] ?? ''));
                                    $facultyId = trim((string) ($profile['faculty_id'] ?? ''));
                                    $birthdate = trim((string) ($profile['birthdate'] ?? ''));
                                    $sex = trim((string) ($profile['sex'] ?? ''));
                                    $hiredDate = trim((string) ($profile['hired_date'] ?? ''));
                                    $contractualEnd = trim((string) ($profile['contractual_end'] ?? ''));
                                    $userId = (int)($profile['user_id'] ?? 0);
                                    
                                    $isPending = str_contains(strtolower($profileStatus), 'pending');

                                    $initials = '';
                                    foreach (array_filter(explode(' ', $fullName)) as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                        if (strlen($initials) >= 2) break;
                                    }
                                    if ($initials === '') $initials = 'NA';
                                ?>
                                <tr class="faculty-row">
                                    <td class="text-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-semibold fs-7 d-inline-flex align-items-center justify-content-center mx-auto" style="width: 38px; height: 38px;">
                                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-info d-none d-sm-table-cell"><?= htmlspecialchars($facultyId ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="fw-semibold text-body"><?= htmlspecialchars($fullName ?: 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge border border-primary text-primary fw-medium px-2 py-1"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-body-secondary d-none d-md-table-cell"><?= htmlspecialchars($position ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <?php if ($isPending): ?>
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-semibold fs-7 shadow-sm">
                                                <i class="fas fa-clock me-1"></i>Pending Approval
                                            </span>
                                        <?php else: ?>
                                            <?php
                                                $statusClass = match ($employmentStatus) {
                                                    'Active', 'Regular' => 'bg-success text-white',
                                                    'Probationary'      => 'bg-warning text-dark',
                                                    default             => 'bg-secondary text-white',
                                                };
                                            ?>
                                            <span class="badge <?= $statusClass ?> px-2.5 py-1.5 rounded-pill fw-semibold fs-7 shadow-sm">
                                                <?= htmlspecialchars($employmentStatus ?: 'Active', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 justify-content-end" role="group">
                                            <!-- View Action Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 fs-7" onclick="viewFaculty(this)"
                                                data-profile-id="<?= (int) ($profile['id'] ?? 0) ?>"
                                                data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-faculty-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                                data-phone="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                                data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                                data-department="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                                data-profile-status="<?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?>"
                                                data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                                data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                                data-sex="<?= htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') ?>"
                                                data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>"
                                                title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- Edit Action Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-3 px-2 py-1 fs-7" onclick="editFaculty(this)"
                                                data-profile-id="<?= (int) ($profile['id'] ?? 0) ?>"
                                                data-first-name="<?= htmlspecialchars(trim((string) ($profile['first_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-middle-name="<?= htmlspecialchars(trim((string) ($profile['middle_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-last-name="<?= htmlspecialchars(trim((string) ($profile['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-suffix="<?= htmlspecialchars(trim((string) ($profile['suffix'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-faculty-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                                data-phone="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                                data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                                data-department-code="<?= htmlspecialchars((string) ($profile['designated_dept'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                                data-profile-status="<?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?>"
                                                data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                                data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                                data-sex="<?= htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') ?>"
                                                data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>"
                                                title="Edit Profile">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer Controls -->
        <div class="card-footer bg-transparent border-top border-light-subtle d-flex flex-wrap justify-content-between align-items-center py-3 px-4">
            <div class="text-body-secondary fs-7 mb-2 mb-md-0" id="paginationInfo">
                Showing 0 to 0 of 0 entries
            </div>
            <nav aria-label="Faculty Table Pagination">
                <ul class="pagination pagination-sm mb-0" id="paginationList">
                    <!-- Dynamic Page Buttons -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- View Faculty Modal -->
<div class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 text-body"><i class="fas fa-user-circle text-primary me-2"></i>Faculty Profile Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7">
                <div class="row g-4">
                    <div class="col-12 col-md-4 text-center border-md-end border-light-subtle pb-3 pb-md-0">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3 fs-2" style="width: 100px; height: 100px;">
                            <span id="viewInitials">JD</span>
                        </div>
                        <h5 class="fw-bold mb-1 text-body fs-6" id="viewFullName">John Doe</h5>
                        <span class="badge mb-2 fs-7" id="viewStatusBadge">Active</span>
                        <div class="text-body-secondary small" id="viewPosition">Head</div>
                        <div class="text-body-secondary small" id="viewDepartment">BSIT</div>
                    </div>
                    <div class="col-12 col-md-8">
                        <h6 class="text-primary border-bottom border-light-subtle pb-2 fw-bold fs-7">Profile Summary</h6>
                        <dl class="row mb-0 g-2">
                            <dt class="col-sm-4 text-body-secondary">Faculty ID</dt>
                            <dd class="col-sm-8 text-body fw-bold" id="viewFacultyId">FAC-2026-001</dd>

                            <dt class="col-sm-4 text-body-secondary">Department</dt>
                            <dd class="col-sm-8 text-body" id="viewDepartmentLabel">Information Technology</dd>

                            <dt class="col-sm-4 text-body-secondary">Position</dt>
                            <dd class="col-sm-8 text-body" id="viewPositionLabel">Head</dd>

                            <dt class="col-sm-4 text-body-secondary">Employment Status</dt>
                            <dd class="col-sm-8 text-body" id="viewEmploymentStatus">Active</dd>

                            <dt class="col-sm-4 text-body-secondary">Profile Status</dt>
                            <dd class="col-sm-8 text-body" id="viewProfileStatus">Active</dd>

                            <dt class="col-sm-4 text-body-secondary">Academic Rank</dt>
                            <dd class="col-sm-8 text-body" id="viewAcademicRank">Assistant Professor</dd>

                            <dt class="col-sm-4 text-body-secondary">Tier</dt>
                            <dd class="col-sm-8 text-body" id="viewTier">Assistant Professor I</dd>

                            <dt class="col-sm-4 text-body-secondary">Hired Date</dt>
                            <dd class="col-sm-8 text-body" id="viewHiredDate">2026-01-10</dd>

                            <dt class="col-sm-4 text-body-secondary">Contractual End</dt>
                            <dd class="col-sm-8 text-body" id="viewContractualEnd">2026-12-31</dd>

                            <dt class="col-sm-4 text-body-secondary">Birthdate</dt>
                            <dd class="col-sm-8 text-body" id="viewBirthdate">1990-07-28</dd>

                            <dt class="col-sm-4 text-body-secondary">Sex</dt>
                            <dd class="col-sm-8 text-body" id="viewSex">Male</dd>

                            <dt class="col-sm-4 text-body-secondary">Email</dt>
                            <dd class="col-sm-8 text-body" id="viewEmail">johndoe@university.edu</dd>

                            <dt class="col-sm-4 text-body-secondary">Phone</dt>
                            <dd class="col-sm-8 text-body" id="viewPhone">+63 912 345 6789</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-light-subtle py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-3 fs-7" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div id="facultyFormModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 text-body"><i class="fas fa-edit text-warning me-2"></i>Update Faculty Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="facultyForm" method="post" action="">
                <input type="hidden" name="action" value="update_faculty">
                <input type="hidden" name="profile_id" id="profileId" value="">
                <div class="modal-body p-4 fs-7">
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom border-light-subtle pb-2 fw-bold fs-7 mb-3">Basic Information</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="facultyId" class="form-label text-body-secondary small fw-bold">Faculty ID</label>
                                <input type="text" id="facultyId" class="form-control bg-body-tertiary text-body border-light-subtle fs-7 shadow-none" readonly>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="firstname" class="form-label text-body-secondary small fw-bold">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="firstname" name="first_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. John" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="lastname" class="form-label text-body-secondary small fw-bold">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="lastname" name="last_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Doe" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="middlename" class="form-label text-body-secondary small fw-bold">Middle Name</label>
                                <input type="text" id="middlename" name="middle_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Santos">
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="suffix" class="form-label text-body-secondary small fw-bold">Suffix</label>
                                <input type="text" id="suffix" name="suffix" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Jr.">
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="sex" class="form-label text-body-secondary small fw-bold">Sex</label>
                                <select id="sex" name="sex" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" disabled selected>Select Sex</option>
                                    <option value="MALE">Male</option>
                                    <option value="FEMALE">Female</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="birthdate" class="form-label text-body-secondary small fw-bold">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-primary border-bottom border-light-subtle pb-2 fw-bold fs-7 mb-3">Contact Information</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label text-body-secondary small fw-bold">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="name@university.edu">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label text-body-secondary small fw-bold">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. 09123456789">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-primary border-bottom border-light-subtle pb-2 fw-bold fs-7 mb-3">Role & Status</h6>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="hiredDate" class="form-label text-body-secondary small fw-bold">Hired Date</label>
                                <input type="date" id="hiredDate" name="hired_date" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none">
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="contractualEnd" class="form-label text-body-secondary small fw-bold">Contractual End</label>
                                <input type="date" id="contractualEnd" name="contractual_end_date" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none">
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="employmentStatus" class="form-label text-body-secondary small fw-bold">Employment Status</label>
                                <select id="employmentStatus" name="employment_status" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Part-Time">Part-Time</option>
                                    <option value="Resigned">Resigned</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="profileStatus" class="form-label text-body-secondary small fw-bold">Profile Status</label>
                                <select id="profileStatus" name="profile_status" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Pending Approval">Pending Approval</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3 fs-7" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold fs-7 px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    const itemsPerPage = 10;
    let filteredRows = [];

    document.addEventListener('DOMContentLoaded', () => {
        initPagination();
    });

    function initPagination() {
        const rows = Array.from(document.querySelectorAll('.faculty-row'));
        const filter = document.getElementById('facultySearch').value.toLowerCase().trim();

        filteredRows = rows.filter(row => {
            return row.textContent.toLowerCase().includes(filter);
        });

        currentPage = 1;
        renderPage();
    }

    function renderPage() {
        const rows = document.querySelectorAll('.faculty-row');
        rows.forEach(row => row.style.display = 'none');

        const totalItems = filteredRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);

        for (let i = startIndex; i < endIndex; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }

        renderPaginationControls(totalPages, totalItems, startIndex, endIndex);
    }

    function renderPaginationControls(totalPages, totalItems, startIndex, endIndex) {
        const paginationList = document.getElementById('paginationList');
        const paginationInfo = document.getElementById('paginationInfo');

        const showingStart = totalItems === 0 ? 0 : startIndex + 1;
        paginationInfo.textContent = `Showing ${showingStart} to ${endIndex} of ${totalItems} entries`;

        paginationList.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous Button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link fs-7" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>`;
        paginationList.appendChild(prevLi);

        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link fs-7" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
            paginationList.appendChild(pageLi);
        }

        // Next Button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link fs-7" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>`;
        paginationList.appendChild(nextLi);
    }

    function changePage(page) {
        currentPage = page;
        renderPage();
    }

    function onSearchInput() {
        initPagination();
    }

    function getModalInstance(id) {
        if (!window.bootstrap || !bootstrap.Modal) return null;
        const modalEl = document.getElementById(id);
        return modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    }

    const academicRankSelect = document.getElementById('academicRank');
    const tierSelect = document.getElementById('tierSelect');

    const tierOptions = {
        instructor: [
            { value: 'Instructor I', label: 'Instructor I' },
            { value: 'Instructor II', label: 'Instructor II' },
            { value: 'Instructor III', label: 'Instructor III' },
        ],
        assistant_professor: [
            { value: 'Assistant Professor I', label: 'Assistant Professor I' },
            { value: 'Assistant Professor II', label: 'Assistant Professor II' },
            { value: 'Assistant Professor III', label: 'Assistant Professor III' },
            { value: 'Assistant Professor IV', label: 'Assistant Professor IV' },
        ],
        associate_professor: [
            { value: 'Associate Professor I', label: 'Associate Professor I' },
            { value: 'Associate Professor II', label: 'Associate Professor II' },
            { value: 'Associate Professor III', label: 'Associate Professor III' },
            { value: 'Associate Professor IV', label: 'Associate Professor IV' },
            { value: 'Associate Professor V', label: 'Associate Professor V' },
        ],
        professor: [
            { value: 'Professor I', label: 'Professor I' },
            { value: 'Professor II', label: 'Professor II' },
            { value: 'Professor III', label: 'Professor III' },
            { value: 'Professor IV', label: 'Professor IV' },
            { value: 'Professor V', label: 'Professor V' },
            { value: 'Professor VI', label: 'Professor VI' },
        ],
    };

    function populateTierOptions(selectedTier = '') {
        if (!academicRankSelect || !tierSelect) return;
        const selectedRank = academicRankSelect.value || 'assistant_professor';
        const options = tierOptions[selectedRank] || [];

        tierSelect.innerHTML = '';
        options.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.label;
            tierSelect.appendChild(opt);
        });

        if (selectedTier && options.some(item => item.value === selectedTier)) {
            tierSelect.value = selectedTier;
        } else if (options.length > 0) {
            tierSelect.value = options[0].value;
        }
    }

    if (academicRankSelect) {
        academicRankSelect.addEventListener('change', () => populateTierOptions(''));
        populateTierOptions();
    }

    function viewFaculty(button) {
        if (!button || !button.dataset) return;
        const modal = getModalInstance('facultyModal');
        document.getElementById('viewInitials').textContent = getInitials(button.dataset.fullName || 'NA');
        document.getElementById('viewFullName').textContent = button.dataset.fullName || 'Unknown';
        const statusBadge = document.getElementById('viewStatusBadge');
        const currentStatus = button.dataset.profileStatus || button.dataset.status || 'Active';
        statusBadge.textContent = currentStatus;
        statusBadge.className = 'badge ' + badgeColorClass(currentStatus);
        document.getElementById('viewPosition').textContent = button.dataset.position || '—';
        document.getElementById('viewDepartment').textContent = button.dataset.department || '—';
        document.getElementById('viewFacultyId').textContent = button.dataset.facultyId || '—';
        document.getElementById('viewDepartmentLabel').textContent = button.dataset.department || '—';
        document.getElementById('viewPositionLabel').textContent = button.dataset.position || '—';
        document.getElementById('viewAcademicRank').textContent = button.dataset.academicRank || '—';
        document.getElementById('viewTier').textContent = button.dataset.tier || '—';
        document.getElementById('viewEmploymentStatus').textContent = button.dataset.status || '—';
        document.getElementById('viewProfileStatus').textContent = button.dataset.profileStatus || '—';
        document.getElementById('viewHiredDate').textContent = button.dataset.hiredDate || '—';
        document.getElementById('viewContractualEnd').textContent = button.dataset.contractualEnd || '—';
        document.getElementById('viewBirthdate').textContent = button.dataset.birthdate || '—';
        document.getElementById('viewSex').textContent = button.dataset.sex || '—';
        document.getElementById('viewEmail').textContent = button.dataset.email || '—';
        document.getElementById('viewPhone').textContent = button.dataset.phone || '—';

        modal?.show();
    }

// Sets a <select>'s value by matching an option case-insensitively.
// Falls back to leaving the placeholder selected if nothing matches,
// instead of silently landing on the wrong option.
function setSelectValueCI(selectEl, rawValue) {
    if (!selectEl) return;
    const value = (rawValue || '').trim();
    if (value === '') return;

    const match = Array.from(selectEl.options).find(
        opt => opt.value.toLowerCase() === value.toLowerCase()
    );

    if (match) {
        selectEl.value = match.value;
    } else if (value) {
        // No matching option exists yet (e.g. legacy/free-form data) -
        // add it on the fly so the current value is still visible and won't
        // be silently overwritten with something else on save.
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
        selectEl.value = value;
    }
}

function editFaculty(button) {
    if (!button || !button.dataset) return;
    document.getElementById('profileId').value = button.dataset.profileId || '';
    document.getElementById('facultyId').value = button.dataset.facultyId || '';
    document.getElementById('firstname').value = button.dataset.firstName || '';
    document.getElementById('middlename').value = button.dataset.middleName || '';
    document.getElementById('lastname').value = button.dataset.lastName || '';
    document.getElementById('suffix').value = button.dataset.suffix || '';
    document.getElementById('birthdate').value = button.dataset.birthdate || '';
    document.getElementById('email').value = button.dataset.email || '';
    document.getElementById('phone').value = button.dataset.phone || '';
    document.getElementById('hiredDate').value = button.dataset.hiredDate || '';
    document.getElementById('contractualEnd').value = button.dataset.contractualEnd || '';

    setSelectValueCI(document.getElementById('sex'), button.dataset.sex);
    setSelectValueCI(document.getElementById('employmentStatus'), button.dataset.status);
    setSelectValueCI(document.getElementById('profileStatus'), button.dataset.profileStatus);

    getModalInstance('facultyFormModal')?.show();
}

    function badgeColorClass(status) {
        const value = (status || '').toLowerCase();
        if (value.includes('pending')) return 'bg-warning-subtle text-warning border border-warning-subtle';
        if (value.includes('active') || value.includes('regular')) return 'bg-success-subtle text-success border border-success-subtle';
        if (value.includes('probationary')) return 'bg-warning-subtle text-warning border border-warning-subtle';
        if (value.includes('part-time')) return 'bg-info-subtle text-info border border-info-subtle';
        return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
    }

    function getInitials(fullName) {
        return (fullName || '')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(name => name.charAt(0).toUpperCase())
            .join('') || 'NA';
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>