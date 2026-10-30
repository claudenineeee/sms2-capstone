<?php
/**
 * Faculty Profile (View Details) — Department Head
 * Purpose: Detailed view of individual faculty member, scoped to the
 * logged-in department head's own department.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

// We still need the controller for the getDepartmentLabel() function
require_once __DIR__ . '/../../controllers/FacultyController.php';
$facultyController = new FacultyController();

// Load the data controller that contains loadFacultyProfiles()
if (file_exists(__DIR__ . '/../../controllers/faculty-data.php')) {
    require_once __DIR__ . '/../../controllers/faculty-data.php';
}

$currentUserId = getCurrentUserId();
$currentUserEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

// 1. DETERMINE THE HEAD'S DEPARTMENT DIRECTLY FROM THE DB (Like in faculty-directory.php)
$headDepartmentCode = '';
try {
    $pdo = db();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT designated_department FROM faculty_db.faculty_profiles WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $currentUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && !empty($row['designated_department'])) {
            $headDepartmentCode = strtoupper(trim($row['designated_department']));
        }
    }
} catch (Throwable $e) {
    // Silently fail and fallback to BSIT below
}

// Fallback if the department wasn't found
if (empty($headDepartmentCode)) {
    $headDepartmentCode = 'BSIT';
}
$headDepartmentLabel = FacultyController::getDepartmentLabel($headDepartmentCode);

// 2. FETCH AND FILTER THE PROFILES
$facultyProfiles = [];
if (function_exists('loadFacultyProfiles')) {
    foreach (loadFacultyProfiles() as $profile) {
        // Extract department safely (matches faculty-directory.php logic)
        $profileDept = trim((string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? ''));
        $profileUserId = isset($profile['user_id']) ? (int) $profile['user_id'] : 0;
        $profileEmail = strtolower(trim((string) ($profile['email'] ?? '')));
        $profilePosition = strtolower(trim((string) ($profile['position'] ?? '')));
        
        $rawProfileStatus = strtolower(trim((string) ($profile['profile_status'] ?? '')));
        $rawAccountStatus = strtolower(trim((string) ($profile['account_status'] ?? '')));

        // A. Exclude inactive, disabled, or rejected accounts
        if (
            strpos($rawAccountStatus, 'inactive') !== false ||
            strpos($rawAccountStatus, 'disabled') !== false ||
            strpos($rawAccountStatus, 'rejected') !== false ||
            strpos($rawProfileStatus, 'inactive') !== false ||
            strpos($rawProfileStatus, 'rejected') !== false
        ) {
            continue;
        }

        // B. Exclude the currently logged-in Department Head
        if (
            ($currentUserId !== null && $profileUserId === (int) $currentUserId) ||
            ($currentUserEmail !== '' && $profileEmail === $currentUserEmail)
        ) {
            continue;
        }

        // C. Exclude Deans
        if (strpos($profilePosition, 'dean') !== false) {
            continue;
        }

        // D. Must match the Head's Department
        // We check if the profile's department matches the head's code OR label
        if (
            strcasecmp($profileDept, $headDepartmentCode) === 0 ||
            strcasecmp($profileDept, $headDepartmentLabel) === 0
        ) {
            $facultyProfiles[] = $profile;
        }
    }
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

$updateMessage = '';
$updateMessageType = 'success';

function getDepartmentLabel(string $department): string
{
    return FacultyController::getDepartmentLabel($department);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'update_faculty') {
    $profileId = (int) ($_POST['profile_id'] ?? 0);
    $updates = [
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'suffix' => trim((string) ($_POST['suffix'] ?? '')),
        'sex' => trim((string) ($_POST['sex'] ?? '')),
        'birthdate' => trim((string) ($_POST['birthdate'] ?? '')),
        'age' => computeAge(trim((string) ($_POST['birthdate'] ?? ''))),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'hired_date' => trim((string) ($_POST['hired_date'] ?? '')),
        'contractual_end_date' => trim((string) ($_POST['contractual_end_date'] ?? '')),
        'employment_status' => trim((string) ($_POST['employment_status'] ?? '')),
    ];
    if ($profileId > 0 && function_exists('updateFacultyProfile') && updateFacultyProfile($profileId, $updates)) {
        $updateMessage = 'Faculty profile updated successfully.';
        $updateMessageType = 'success';
        
        // Refresh after edit
        $facultyProfiles = [];
        foreach (loadFacultyProfiles() as $profile) {
            $profileDept = trim((string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? ''));
            $profileUserId = isset($profile['user_id']) ? (int) $profile['user_id'] : 0;
            $profileEmail = strtolower(trim((string) ($profile['email'] ?? '')));
            $profilePosition = strtolower(trim((string) ($profile['position'] ?? '')));
            $rawProfileStatus = strtolower(trim((string) ($profile['profile_status'] ?? '')));
            $rawAccountStatus = strtolower(trim((string) ($profile['account_status'] ?? '')));

            if (strpos($rawAccountStatus, 'inactive') !== false || strpos($rawAccountStatus, 'disabled') !== false || strpos($rawAccountStatus, 'rejected') !== false || strpos($rawProfileStatus, 'inactive') !== false || strpos($rawProfileStatus, 'rejected') !== false) continue;
            if (($currentUserId !== null && $profileUserId === (int) $currentUserId) || ($currentUserEmail !== '' && $profileEmail === $currentUserEmail)) continue;
            if (strpos($profilePosition, 'dean') !== false) continue;
            
            if (strcasecmp($profileDept, $headDepartmentCode) === 0 || strcasecmp($profileDept, $headDepartmentLabel) === 0) {
                $facultyProfiles[] = $profile;
            }
        }
    } else {
        $updateMessage = 'Unable to update faculty profile.';
        $updateMessageType = 'danger';
    }
}

$totalFaculty = count($facultyProfiles);
$regularCount = count(array_filter($facultyProfiles, function ($profile) {
    $status = strtolower(trim((string) ($profile['employment_status'] ?? '')));
    return $status === 'regular' || $status === 'active' || $status === 'full-time';
}));
$partTimeCount = count(array_filter($facultyProfiles, function ($profile) {
    $status = strtolower(trim((string) ($profile['employment_status'] ?? '')));
    return $status === 'part-time' || $status === 'contractual';
}));

?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1>
            <i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>
            Faculty Profile
        </h1>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Top Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Faculty</h6>
                        <h4 class="mb-0 fw-bold"><?= $totalFaculty ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>+2.5% <span class="text-muted fw-normal">from last month</span></small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details"><i class="fas fa-arrow-up-right-from-square"></i></a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Full-Time / Regular</h6>
                        <h4 class="mb-0 fw-bold"><?= $regularCount ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>Active <span class="text-muted fw-normal">status</span></small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details"><i class="fas fa-arrow-up-right-from-square"></i></a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning fs-4"><i class="fas fa-book-reader"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Part-Time / Contract</h6>
                        <h4 class="mb-0 fw-bold"><?= $partTimeCount ?></h4>
                        <small class="text-muted fw-normal" style="font-size: 0.75rem;">Updated this term</small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details"><i class="fas fa-arrow-up-right-from-square"></i></a>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-star"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Dept Avg Rating</h6>
                        <h4 class="mb-0 fw-bold">4.52</h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>+0.3 <span class="text-muted fw-normal">vs last term</span></small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details"><i class="fas fa-arrow-up-right-from-square"></i></a>
            </section>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input id="directorySearch" type="text" class="form-control border-start-0 bg-body-tertiary" placeholder="Search by name, ID, or email...">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <select id="statusFilter" class="form-select bg-body-tertiary">
                        <option value="all">All Statuses</option>
                        <option value="regular">Regular / Active</option>
                        <option value="contractual">Contractual</option>
                        <option value="part-time">Part-Time</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (count($facultyProfiles) === 0): ?>
                <div class="alert alert-info rounded-3 m-3">
                    No faculty profiles are available for your assigned department.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="facultyTable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">Photo</th>
                                <th scope="col">Faculty ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Department</th>
                                <th scope="col">Position</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody">
                            <?php foreach ($facultyProfiles as $profile): ?>
                                <?php
                                    $fullName = function_exists('buildFacultyFullName') ? buildFacultyFullName($profile) : trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['middle_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
                                    $rawDept = (string) ($profile['designated_dept'] ?? $profile['designated_department'] ?? '');
                                    $departmentLabel = getDepartmentLabel($rawDept);
                                    $employmentStatus = ucwords(strtolower((string) ($profile['employment_status'] ?? '')));
                                    $profileStatus = ucwords(strtolower((string) ($profile['profile_status'] ?? '')));
                                    $academicRank = ucwords(str_replace('_', ' ', strtolower((string) ($profile['academic_rank'] ?? ''))));
                                    $position = ucwords(strtolower((string) ($profile['position'] ?? '')));
                                    $email = trim((string) ($profile['email'] ?? ''));
                                    $facultyId = trim((string) ($profile['faculty_id'] ?? ''));
                                    $birthdate = trim((string) ($profile['birthdate'] ?? ''));
                                    $hiredDate = trim((string) ($profile['hired_date'] ?? ''));
                                    $contractualEnd = trim((string) ($profile['contractual_end'] ?? ''));
                                    $age = $birthdate !== '' ? computeAge($birthdate) : 0;
                                    $initials = implode('', array_slice(array_map(function ($part) { return strtoupper(substr($part, 0, 1)); }, array_filter(explode(' ', $fullName))), 0, 2));
                                    if ($initials === '') { $initials = 'NA'; }
                                ?>
                                <tr class="faculty-row" 
                                    data-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-dept="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                    data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="ps-3">
                                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px;">
                                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="faculty-id-field fw-semibold text-uppercase"><?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge border border-primary text-primary fw-medium px-2 py-1"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-capitalize"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-capitalize"><?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="viewFaculty(this)"
                                            data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-faculty-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                            data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                            data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-department-label="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                            data-profile-status="<?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?>"
                                            data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                            data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                            data-sex="<?= htmlspecialchars((string) ($profile['sex'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editFaculty(this)"
                                            data-profile-id="<?= (int) ($profile['id'] ?? 0) ?>"
                                            data-first-name="<?= htmlspecialchars(trim((string) ($profile['first_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-middle-name="<?= htmlspecialchars(trim((string) ($profile['middle_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-last-name="<?= htmlspecialchars(trim((string) ($profile['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-suffix="<?= htmlspecialchars(trim((string) ($profile['suffix'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-faculty-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                            data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-department-code="<?= htmlspecialchars((string) ($profile['designated_dept'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-department-label="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                            data-profile-status="<?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?>"
                                            data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                            data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                            data-sex="<?= htmlspecialchars((string) ($profile['sex'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($facultyProfiles) > 0): ?>
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                <nav aria-label="Faculty pagination">
                    <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Faculty Modal -->
<div class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 900px !important; width: 100%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Faculty Profile Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center border-end">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 150px; height: 150px; font-size: 32px;">
                            <span id="viewInitials">JD</span>
                        </div>
                        <h4 class="h5 fw-bold mb-1" id="viewFullName">John Doe</h4>
                        <span class="badge" id="viewStatusBadge">Active</span>
                        <div class="mt-3 text-muted small" id="viewPosition">Head</div>
                        <div class="text-muted small" id="viewDepartment">BSIT</div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-primary border-bottom pb-2 fw-bold">Profile Summary</h6>
                        <dl class="row mb-3">
                            <dt class="col-sm-4 text-muted small">Faculty ID</dt>
                            <dd class="col-sm-8" id="viewFacultyId">FAC-2026-001</dd>
                            <dt class="col-sm-4 text-muted small">Department</dt>
                            <dd class="col-sm-8" id="viewDepartmentLabel">Information Technology</dd>
                            <dt class="col-sm-4 text-muted small">Position</dt>
                            <dd class="col-sm-8" id="viewPositionLabel">Head</dd>
                            <dt class="col-sm-4 text-muted small">Academic Rank</dt>
                            <dd class="col-sm-8" id="viewAcademicRank">Assistant Professor</dd>
                            <dt class="col-sm-4 text-muted small">Tier</dt>
                            <dd class="col-sm-8" id="viewTier">Assistant Professor I</dd>
                            <dt class="col-sm-4 text-muted small">Status</dt>
                            <dd class="col-sm-8" id="viewEmploymentStatus">Active</dd>
                            <dt class="col-sm-4 text-muted small">Profile Status</dt>
                            <dd class="col-sm-8" id="viewProfileStatus">Active</dd>
                            <dt class="col-sm-4 text-muted small">Hired Date</dt>
                            <dd class="col-sm-8" id="viewHiredDate">2026-01-10</dd>
                            <dt class="col-sm-4 text-muted small">Contractual End</dt>
                            <dd class="col-sm-8" id="viewContractualEnd">2026-12-31</dd>
                            <dt class="col-sm-4 text-muted small">Birthdate</dt>
                            <dd class="col-sm-8" id="viewBirthdate">1990-07-28</dd>
                            <dt class="col-sm-4 text-muted small">Sex</dt>
                            <dd class="col-sm-8" id="viewSex">Male</dd>
                            <dt class="col-sm-4 text-muted small">Email</dt>
                            <dd class="col-sm-8" id="viewEmail">johndoe@university.edu</dd>
                            <dt class="col-sm-4 text-muted small">Phone</dt>
                            <dd class="col-sm-8" id="viewPhone">+63 912 345 6789</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div id="facultyFormModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" style="max-width: 860px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Faculty Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="facultyForm" method="post" action="">
                <input type="hidden" name="action" value="update_faculty">
                <input type="hidden" name="profile_id" id="profileId" value="">
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Basic Information</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" id="firstname" name="first_name" class="form-control" placeholder="e.g. Juan" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middlename" class="form-label">Middle Name</label>
                                <input type="text" id="middlename" name="middle_name" class="form-control" placeholder="e.g. H.">
                            </div>
                            <div class="col-md-4">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" id="lastname" name="last_name" class="form-control" placeholder="e.g. Cruz" required>
                            </div>
                            <div class="col-md-4">
                                <label for="suffix" class="form-label">Suffix</label>
                                <input type="text" id="suffix" name="suffix" class="form-control" placeholder="e.g. Jr.">
                            </div>
                            <div class="col-md-4">
                                <label for="sex" class="form-label">Sex</label>
                                <select id="sex" name="sex" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Contact Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="name@university.edu">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 09123456789">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Role & Status</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="hiredDate" class="form-label">Hired Date</label>
                                <input type="date" id="hiredDate" name="hired_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="contractualEnd" class="form-label">Contractual End</label>
                                <input type="date" id="contractualEnd" name="contractual_end_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="employmentStatus" class="form-label">Employment Status</label>
                                <select id="employmentStatus" name="employment_status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Part-Time">Part-Time</option>
                                    <option value="Resigned">Resigned</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function getModalInstance(id) {
        if (!window.bootstrap || !bootstrap.Modal) return null;
        const modalEl = document.getElementById(id);
        if (!modalEl) return null;
        return bootstrap.Modal.getOrCreateInstance(modalEl);
    }

    function viewFaculty(button) {
        if (!button || !button.dataset) return;
        const modal = getModalInstance('facultyModal');
        document.getElementById('viewInitials').textContent = getInitials(button.dataset.fullName || 'NA');
        document.getElementById('viewFullName').textContent = button.dataset.fullName || 'Unknown';
        const statusBadge = document.getElementById('viewStatusBadge');
        statusBadge.textContent = button.dataset.profileStatus || button.dataset.status || 'Unknown';
        statusBadge.className = 'badge ' + badgeColorClass(button.dataset.profileStatus || button.dataset.status || 'Active');
        document.getElementById('viewPosition').textContent = button.dataset.position || '';
        document.getElementById('viewDepartment').textContent = button.dataset.departmentLabel || '';
        document.getElementById('viewFacultyId').textContent = button.dataset.facultyId || '';
        document.getElementById('viewDepartmentLabel').textContent = button.dataset.departmentLabel || '';
        document.getElementById('viewPositionLabel').textContent = button.dataset.position || '';
        document.getElementById('viewAcademicRank').textContent = button.dataset.academicRank || '';
        document.getElementById('viewTier').textContent = button.dataset.tier || '';
        document.getElementById('viewEmploymentStatus').textContent = button.dataset.status || '';
        document.getElementById('viewProfileStatus').textContent = button.dataset.profileStatus || '';
        document.getElementById('viewHiredDate').textContent = button.dataset.hiredDate || '';
        document.getElementById('viewContractualEnd').textContent = button.dataset.contractualEnd || '';
        document.getElementById('viewBirthdate').textContent = button.dataset.birthdate || '';
        document.getElementById('viewSex').textContent = button.dataset.sex || '';
        document.getElementById('viewEmail').textContent = button.dataset.email || '';
        document.getElementById('viewPhone').textContent = button.dataset.phone || '';
        modal?.show();
    }

    function editFaculty(button) {
        if (!button || !button.dataset) return;
        document.getElementById('profileId').value = button.dataset.profileId || '';
        document.getElementById('firstname').value = button.dataset.firstName || '';
        document.getElementById('middlename').value = button.dataset.middleName || '';
        document.getElementById('lastname').value = button.dataset.lastName || '';
        document.getElementById('suffix').value = button.dataset.suffix || '';
        document.getElementById('sex').value = button.dataset.sex || 'Male';
        document.getElementById('birthdate').value = button.dataset.birthdate || '';
        document.getElementById('email').value = button.dataset.email || '';
        document.getElementById('phone').value = button.dataset.phone || '';
        document.getElementById('hiredDate').value = button.dataset.hiredDate || '';
        document.getElementById('contractualEnd').value = button.dataset.contractualEnd || '';
        document.getElementById('employmentStatus').value = button.dataset.status || 'Active';
        getModalInstance('facultyFormModal')?.show();
    }

    function badgeColorClass(status) {
        const value = (status || '').toLowerCase();
        if (value.includes('active')) return 'bg-success';
        if (value.includes('probationary')) return 'bg-warning text-dark';
        if (value.includes('part-time')) return 'bg-info text-dark';
        if (value.includes('inactive') || value.includes('resigned')) return 'bg-secondary';
        return 'bg-primary';
    }

    function getInitials(fullName) {
        return (fullName || '').split(' ').filter(Boolean).slice(0, 2).map(name => name.charAt(0).toUpperCase()).join('') || 'NA';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const rowsPerPage = 10;
        let currentPage = 1;
        let filteredRows = [];

        const searchInput = document.getElementById('directorySearch');
        const statusFilter = document.getElementById('statusFilter');
        const allRows = Array.from(document.querySelectorAll('.faculty-row'));
        const paginationControls = document.getElementById('paginationControls');
        const paginationInfo = document.getElementById('paginationInfo');

        function filterRows() {
            const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

            filteredRows = allRows.filter(row => {
                const name = (row.dataset.name || '').toLowerCase();
                const email = (row.dataset.email || '').toLowerCase();
                const id = (row.dataset.id || '').toLowerCase();
                const status = (row.dataset.status || '').toLowerCase();

                const matchesSearch = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm) || id.includes(searchTerm);
                
                let matchesStatus = selectedStatus === 'all';
                if (!matchesStatus) {
                    if (selectedStatus === 'regular') {
                        matchesStatus = status.includes('regular') || status.includes('active') || status.includes('full-time');
                    } else {
                        matchesStatus = status.includes(selectedStatus);
                    }
                }

                return matchesSearch && matchesStatus;
            });

            currentPage = 1;
            renderPagination();
        }

        function renderPagination() {
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, totalRows);

            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(startIndex, endIndex).forEach(row => row.style.display = '');

            if (paginationInfo) {
                paginationInfo.textContent = totalRows === 0 ? 'Showing 0 entries' : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
            }

            if (!paginationControls) return;
            paginationControls.innerHTML = '';
            if (totalPages <= 1) return;

            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
            prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { currentPage--; renderPagination(); } });
            paginationControls.appendChild(prevLi);

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); currentPage = i; renderPagination(); });
                paginationControls.appendChild(li);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { currentPage++; renderPagination(); } });
            paginationControls.appendChild(nextLi);
        }

        if (searchInput) searchInput.addEventListener('input', filterRows);
        if (statusFilter) statusFilter.addEventListener('change', filterRows);

        filterRows();
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>