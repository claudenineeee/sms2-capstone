<?php
/**
 * Faculty Profile (View Details) — Department Head
 * Purpose: Detailed view of individual faculty members, scoped to the
 * logged-in department head's own department.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php'; // Ensure facultyDb() is loaded
requireAuth();

// ==========================================
// 1. DATABASE CONNECTION
// ==========================================
$corePdo = function_exists('db') ? db() : null;

if (!$corePdo) {
    die("<div style='padding:20px;color:red;'><strong>Database connection failed.</strong> Check DB_HOST / DB_USERNAME / DB_PASSWORD / DB_DATABASE environment variables.</div>");
}

$facultyPdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$facultyPdo) {
    $facultyPdo = $corePdo;
}

$pageTitle    = 'Faculty Profile';
$activeModule = 'faculty';
$activePage   = 'faculty-profile';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

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

// Get current user's department
$currentUserId = getCurrentUserId();
$currentUserEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

try {
    $stmt = $facultyPdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$currentUserId, $currentUserEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['designated_department'])) {
        $headDepartmentCode = strtoupper(trim($row['designated_department']));
        $headDepartment = getDepartmentLabel($headDepartmentCode);
    }
} catch (Throwable $e) {}

if (empty($headDepartmentCode)) {
    $headDepartmentCode = 'BSIT'; // Fallback
    $headDepartment = getDepartmentLabel($headDepartmentCode);
}

// ==========================================
// 2. PROCESS UPDATE SUBMISSION
// ==========================================
$updateMessage = '';
$updateMessageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_faculty') {
    try {
        requireCsrf((string) ($_POST['csrf_token'] ?? ''));
        
        $profileId = (int) ($_POST['profile_id'] ?? 0);
        
        // Update directly in faculty_profiles
        $stmtUpdate = $facultyPdo->prepare("
            UPDATE faculty_profiles SET 
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                sex = ?, birthdate = ?, age = ?, phone = ?, email = ?, 
                hired_date = ?, contractual_end = ?, employment_status = ?,
                academic_rank = ?, tier = ?, specialization_assignment = ?, coordinator_type = ?
            WHERE id = ?
        ");
        
        $stmtUpdate->execute([
            trim((string) ($_POST['first_name'] ?? '')),
            trim((string) ($_POST['middle_name'] ?? '')),
            trim((string) ($_POST['last_name'] ?? '')),
            trim((string) ($_POST['suffix'] ?? '')),
            trim((string) ($_POST['sex'] ?? '')),
            trim((string) ($_POST['birthdate'] ?? '')),
            computeAge(trim((string) ($_POST['birthdate'] ?? ''))),
            trim((string) ($_POST['phone'] ?? '')),
            trim((string) ($_POST['email'] ?? '')),
            trim((string) ($_POST['hired_date'] ?? '')),
            trim((string) ($_POST['contractual_end_date'] ?? '')),
            trim((string) ($_POST['employment_status'] ?? '')),
            trim((string) ($_POST['academic_rank'] ?? '')),
            trim((string) ($_POST['tier'] ?? '')),
            trim((string) ($_POST['specialization_assignment'] ?? '')),
            trim((string) ($_POST['coordinator_type'] ?? '')),
            $profileId
        ]);
        
        $updateMessage = 'Faculty profile updated successfully.';
    } catch (Throwable $e) {
        $updateMessage = 'Unable to update faculty profile: ' . $e->getMessage();
        $updateMessageType = 'danger';
    }
}

// ==========================================
// 3. FETCH PROFILES DIRECTLY FROM FACULTY_DB
// ==========================================
$facultyProfiles = [];
$currentUserId = getCurrentUserId();
$currentUserEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

try {
    $headDepartmentLabel = getDepartmentLabel($headDepartmentCode);
    
    // Match BOTH the code (BSIT) and the label (Information Technology)
    $stmt = $facultyPdo->prepare("
        SELECT * FROM faculty_profiles 
        WHERE LOWER(TRIM(designated_department)) = LOWER(?) 
           OR LOWER(TRIM(designated_department)) = LOWER(?)
        ORDER BY id DESC
    ");
    $stmt->execute([$headDepartmentCode, $headDepartmentLabel]);
    $allProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allProfiles as $profile) {
        $profileUserId = isset($profile['user_id']) ? (int) $profile['user_id'] : 0;
        $profileEmail = strtolower(trim((string) ($profile['email'] ?? '')));
        $profilePosition = strtolower(trim((string) ($profile['position'] ?? '')));
        $rawProfileStatus = strtolower(trim((string) ($profile['profile_status'] ?? '')));

        // EXACT SAME FILTER LOGIC AS FACULTY-DIRECTORY.PHP
        if (strpos($rawProfileStatus, 'inactive') !== false) continue;
        if ($currentUserId && $profileUserId === (int) $currentUserId) continue;
        if ($currentUserEmail && $profileEmail === $currentUserEmail) continue;
        if (strpos($profilePosition, 'dean') !== false) continue;

        $facultyProfiles[] = $profile;
    }
} catch (Throwable $e) {
    $updateMessage = 'Fetch Error: ' . $e->getMessage();
    $updateMessageType = 'danger';
}

// Statistics
$totalFaculty = count($facultyProfiles);
$regularCount = count(array_filter($facultyProfiles, function ($profile) {
    $status = strtolower(trim((string) ($profile['employment_status'] ?? '')));
    return $status === 'regular' || $status === 'active' || $status === 'full-time';
}));
$partTimeCount = count(array_filter($facultyProfiles, function ($profile) {
    $status = strtolower(trim((string) ($profile['employment_status'] ?? '')));
    return $status === 'part-time' || $status === 'contractual';
}));

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
    /* ============================================================
       CUSTOM THIN SCROLLBAR FOR MODALS (LIGHT & DARK MODE)
       ============================================================ */
    #facultyFormModal .modal-body {
        max-height: calc(100vh - 210px); 
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        padding-right: 1.25rem !important; 
    }

    #facultyFormModal .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    #facultyFormModal .modal-body::-webkit-scrollbar-track {
        background: transparent;
        margin: 4px 0;
    }

    #facultyFormModal .modal-body::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 10px;
    }

    #facultyFormModal .modal-body::-webkit-scrollbar-thumb:hover {
        background-color: rgba(156, 163, 175, 0.8);
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] #facultyFormModal .modal-body::-webkit-scrollbar-thumb,
    [data-theme="dark"] #facultyFormModal .modal-body::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.2);
    }

    [data-bs-theme="dark"] #facultyFormModal .modal-body::-webkit-scrollbar-thumb:hover,
    [data-theme="dark"] #facultyFormModal .modal-body::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.4);
    }

    [data-bs-theme="dark"] #facultyFormModal .modal-body,
    [data-theme="dark"] #facultyFormModal .modal-body {
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }
</style>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1>
            <i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>
            Faculty Profile
        </h1>
    </div>
</div>

<div class="container-fluid py-2">
    <!-- Top Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Faculty</h6>
                        <h4 class="mb-0 fw-bold"><?= $totalFaculty ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>Active <span class="text-muted fw-normal">members</span></small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Full-Time / Regular</h6>
                        <h4 class="mb-0 fw-bold"><?= $regularCount ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-check-circle me-1"></i>Verified</small>
                    </div>
                </div>
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
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-building"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Department Scope</h6>
                        <h4 class="mb-0 fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($headDepartmentCode) ?></h4>
                    </div>
                </div>
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
                                    $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
                                    $rawDept = (string) ($profile['designated_department'] ?? '');
                                    $departmentLabel = getDepartmentLabel($rawDept);
                                    $employmentStatus = ucwords(strtolower((string) ($profile['employment_status'] ?? '')));
                                    $profileStatus = ucwords(strtolower((string) ($profile['profile_status'] ?? '')));
                                    $position = ucwords(strtolower((string) ($profile['position'] ?? '')));
                                    $email = trim((string) ($profile['email'] ?? ''));
                                    $facultyId = trim((string) ($profile['faculty_id'] ?? ''));
                                    $birthdate = trim((string) ($profile['birthdate'] ?? ''));
                                    $hiredDate = trim((string) ($profile['hired_date'] ?? ''));
                                    $contractualEnd = trim((string) ($profile['contractual_end'] ?? ''));
                                    $initials = strtoupper(substr($fullName, 0, 1));

                                    // Badge styling for accurate status
                                    $statusBadgeClass = 'bg-secondary';
                                    if (strpos(strtolower($profileStatus), 'pending') !== false) $statusBadgeClass = 'bg-warning text-dark';
                                    elseif (strpos(strtolower($profileStatus), 'active') !== false) $statusBadgeClass = 'bg-success';
                                    elseif (strpos(strtolower($profileStatus), 'probation') !== false) $statusBadgeClass = 'bg-info text-dark';
                                ?>
                                <tr class="faculty-row" 
                                    data-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-dept="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" 
                                    data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>" 
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
                                    <td><span class="badge <?= $statusBadgeClass ?> px-2 py-1 fw-bold"><?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="viewFaculty(this)"
                                            data-full-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-faculty-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                            data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                            data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-specialization="<?= htmlspecialchars((string) ($profile['specialization_assignment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-coordinator-type="<?= htmlspecialchars((string) ($profile['coordinator_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
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
                                            data-sex="<?= htmlspecialchars((string) ($profile['sex'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                            data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                            data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-tier="<?= htmlspecialchars((string) ($profile['tier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-specialization="<?= htmlspecialchars((string) ($profile['specialization_assignment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-coordinator-type="<?= htmlspecialchars((string) ($profile['coordinator_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast align-items-center border shadow-lg rounded-3 bg-body text-body" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center p-2">
            <div id="toastIconContainer" class="me-2 fs-5 px-1">
                <i id="toastIcon" class="fas fa-check-circle"></i>
            </div>
            <div class="toast-body d-flex align-items-center fs-7 fw-semibold p-1 text-body" id="toastMessageText">
                <?= htmlspecialchars($updateMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <button type="button" class="btn-close ms-auto me-1 shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- View Faculty Modal -->
<div class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 900px !important; width: 100%;">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 text-body"><i class="fas fa-user-circle text-primary me-2"></i>Faculty Profile Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7">
                <div class="row g-4">
                    <div class="col-md-4 text-center border-md-end border-light-subtle pb-3 pb-md-0">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3 fs-2" style="width: 120px; height: 120px;">
                            <span id="viewInitials">JD</span>
                        </div>
                        <h5 class="fw-bold mb-1 text-body fs-6" id="viewFullName">John Doe</h5>
                        <span class="badge mb-2 fs-7" id="viewStatusBadge">Active</span>
                        <div class="text-body-secondary small" id="viewPosition">Head</div>
                        <div class="text-body-secondary small" id="viewDepartment">BSIT</div>
                    </div>
                    <div class="col-md-8">
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
                            
                            <dt class="col-sm-4 text-body-secondary">Specialization</dt>
                            <dd class="col-sm-8 text-body" id="viewSpecialization">Software Engineering</dd>
                            
                            <dt class="col-sm-4 text-body-secondary">Coordinator Type</dt>
                            <dd class="col-sm-8 text-body" id="viewCoordinatorType">—</dd>
                            
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
            <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div id="facultyFormModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" style="max-width: 860px;">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 text-body"><i class="fas fa-edit text-warning me-2"></i>Update Faculty Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="facultyForm" method="post" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_faculty">
                <input type="hidden" name="profile_id" id="profileId" value="">
                <div class="modal-body p-4 fs-7">
                    
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom border-light-subtle pb-2 fw-bold fs-7 mb-3">Basic Information</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="firstname" class="form-label text-body-secondary small fw-bold">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="firstname" name="first_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. John" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="middlename" class="form-label text-body-secondary small fw-bold">Middle Name</label>
                                <input type="text" id="middlename" name="middle_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Santos">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="lastname" class="form-label text-body-secondary small fw-bold">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="lastname" name="last_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Doe" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="suffix" class="form-label text-body-secondary small fw-bold">Suffix</label>
                                <input type="text" id="suffix" name="suffix" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g. Jr.">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="sex" class="form-label text-body-secondary small fw-bold">Sex</label>
                                <select id="sex" name="sex" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="MALE">Male</option>
                                    <option value="FEMALE">Female</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
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
                                <label for="academicRank" class="form-label text-body-secondary small fw-bold">Academic Rank</label>
                                <select id="academicRank" name="academic_rank" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" disabled selected>Select Rank</option>
                                    <option value="Instructor">Instructor</option>
                                    <option value="Assistant Professor">Assistant Professor</option>
                                    <option value="Associate Professor">Associate Professor</option>
                                    <option value="Professor">Professor</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="academicTier" class="form-label text-body-secondary small fw-bold">Tier</label>
                                <select id="academicTier" name="tier" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" disabled selected>Select Academic Rank first</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="specialization" class="form-label text-body-secondary small fw-bold">Specialization Assignment</label>
                                <select id="specialization" name="specialization_assignment" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" selected>-- No Special Assignment --</option>
                                    <optgroup label="Technical Support Track (Advisors)">
                                        <option value="Capstone Advisor / Panelist">Capstone Advisor</option>
                                        <option value="OJT Academic Advisor">Practicum / OJT Academic Advisor</option>
                                        <option value="Systems Advisor">Systems Advisor</option>
                                        <option value="Technical Advisor">Technical Advisor</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="coordinatorType" class="form-label text-body-secondary small fw-bold">Coordinator Type</label>
                                <select id="coordinatorType" name="coordinator_type" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="" selected>-- No Coordinator Type --</option>
                                    <option value="NSTP">NSTP</option>
                                    <option value="OJT">OJT</option>
                                    <option value="RESEARCH">RESEARCH & CAPSTONE</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="hiredDate" class="form-label text-body-secondary small fw-bold">Hired Date</label>
                                <input type="date" id="hiredDate" name="hired_date" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none">
                            </div>
                            <div class="col-12 col-sm-6 col-md-6">
                                <label for="contractualEnd" class="form-label text-body-secondary small fw-bold">Contractual End</label>
                                <input type="date" id="contractualEnd" name="contractual_end_date" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="employmentStatus" class="form-label text-body-secondary small fw-bold">Employment Status</label>
                                <select id="employmentStatus" name="employment_status" class="form-select bg-body text-body border-light-subtle fs-7 shadow-none">
                                    <option value="Regular">Regular</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Part-Time">Part-Time</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3 fs-7" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-bold fs-7 px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // 1. TOAST NOTIFICATION LOGIC
    // ==========================================
    document.addEventListener('DOMContentLoaded', () => {
        const phpMessage = <?= json_encode($updateMessage) ?>;
        const messageType = <?= json_encode($updateMessageType) ?>;

        if (phpMessage && phpMessage.trim() !== '') {
            const toastEl = document.getElementById('liveToast');
            const toastIcon = document.getElementById('toastIcon');

            if (messageType === 'success') {
                toastEl.classList.add('border-success');
                toastIcon.className = 'fas fa-check-circle text-success';
            } else {
                toastEl.classList.add('border-danger');
                toastIcon.className = 'fas fa-exclamation-circle text-danger';
            }

            if (toastEl && window.bootstrap && window.bootstrap.Toast) {
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();
            }
        }
    });

    // ==========================================
    // 2. FORM DROPDOWN LOGIC (Academic Rank -> Tier)
    // ==========================================
    const RANK_TIERS = {
        "Instructor": ["Instructor I", "Instructor II", "Instructor III"],
        "Assistant Professor": ["Assistant Professor I", "Assistant Professor II", "Assistant Professor III", "Assistant Professor IV"],
        "Associate Professor": ["Associate Professor I", "Associate Professor II", "Associate Professor III", "Associate Professor IV"],
        "Professor": ["Professor I", "Professor II", "Professor III", "Professor IV", "Professor V", "Professor VI"]
    };

    const academicRankSelect = document.getElementById('academicRank');
    const academicTierSelect = document.getElementById('academicTier');

    function populateTiers(selectedTier = '') {
        if (!academicRankSelect || !academicTierSelect) return;
        const selectedRank = academicRankSelect.value || 'Instructor';
        const tiers = RANK_TIERS[selectedRank] || [];

        academicTierSelect.innerHTML = '<option value="" disabled selected>Select Tier</option>';
        tiers.forEach(tier => {
            const opt = document.createElement('option');
            opt.value = tier;
            opt.textContent = tier;
            academicTierSelect.appendChild(opt);
        });

        if (selectedTier && tiers.includes(selectedTier)) {
            academicTierSelect.value = selectedTier;
        } else if (tiers.length > 0) {
            academicTierSelect.value = tiers[0];
        }
    }

    if (academicRankSelect) {
        academicRankSelect.addEventListener('change', () => populateTiers(''));
        populateTiers();
    }

    // ==========================================
    // 3. MODAL CONTROLS
    // ==========================================
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
        
        document.getElementById('viewPosition').textContent = button.dataset.position || '—';
        document.getElementById('viewDepartment').textContent = button.dataset.departmentLabel || '—';
        document.getElementById('viewFacultyId').textContent = button.dataset.facultyId || '—';
        document.getElementById('viewDepartmentLabel').textContent = button.dataset.departmentLabel || '—';
        document.getElementById('viewPositionLabel').textContent = button.dataset.position || '—';
        document.getElementById('viewAcademicRank').textContent = button.dataset.academicRank || '—';
        document.getElementById('viewSpecialization').textContent = button.dataset.specialization || '—';
        document.getElementById('viewCoordinatorType').textContent = button.dataset.coordinatorType || '—';
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

    function editFaculty(button) {
        if (!button || !button.dataset) return;
        
        document.getElementById('profileId').value = button.dataset.profileId || '';
        document.getElementById('firstname').value = button.dataset.firstName || '';
        document.getElementById('middlename').value = button.dataset.middleName || '';
        document.getElementById('lastname').value = button.dataset.lastName || '';
        document.getElementById('suffix').value = button.dataset.suffix || '';
        document.getElementById('sex').value = button.dataset.sex || 'MALE';
        document.getElementById('birthdate').value = button.dataset.birthdate || '';
        document.getElementById('email').value = button.dataset.email || '';
        document.getElementById('phone').value = button.dataset.phone || '';
        document.getElementById('hiredDate').value = button.dataset.hiredDate || '';
        document.getElementById('contractualEnd').value = button.dataset.contractualEnd || '';
        document.getElementById('employmentStatus').value = button.dataset.status || 'Regular';
        
        document.getElementById('specialization').value = button.dataset.specialization || '';
        document.getElementById('coordinatorType').value = button.dataset.coordinatorType || '';

        // Populate Academic Rank and Tier safely
        const rankValue = button.dataset.academicRank || '';
        if (academicRankSelect) {
            let hasOption = Array.from(academicRankSelect.options).some(opt => opt.value === rankValue);
            if (!hasOption && rankValue) {
                const opt = document.createElement('option');
                opt.value = rankValue;
                opt.textContent = rankValue;
                academicRankSelect.appendChild(opt);
            }
            academicRankSelect.value = rankValue;
            populateTiers(button.dataset.tier || '');
        }

        getModalInstance('facultyFormModal')?.show();
    }

    function badgeColorClass(status) {
        const value = (status || '').toLowerCase();
        if (value.includes('active')) return 'bg-success';
        if (value.includes('pending')) return 'bg-warning text-dark';
        if (value.includes('probationary')) return 'bg-info text-dark';
        if (value.includes('inactive') || value.includes('resigned')) return 'bg-secondary';
        return 'bg-primary';
    }

    function getInitials(fullName) {
        return (fullName || '').split(' ').filter(Boolean).slice(0, 2).map(name => name.charAt(0).toUpperCase()).join('') || 'NA';
    }

    // ==========================================
    // 4. SEARCH & PAGINATION
    // ==========================================
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