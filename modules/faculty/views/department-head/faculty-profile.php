<?php
/**
 * Faculty Profile (View Details) — Department Head
 * Purpose: Detailed view of individual faculty member, scoped to the
 * logged-in department head's own department via the shared FacultyModel.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

require_once __DIR__ . '/../../controllers/FacultyController.php';
$facultyController = new FacultyController();

$facultyProfiles = $facultyController->getDirectoryList();

$headDepartmentCode = '';
$headDepartmentLabel = '';
if (!empty($facultyProfiles)) {
    $headDepartmentCode = (string) ($facultyProfiles[0]['designated_department'] ?? '');
    $headDepartmentLabel = FacultyController::getDepartmentLabel($headDepartmentCode);
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
        $facultyProfiles = $facultyController->getDirectoryList(); // refresh after edit
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
        <h1><i class="fas fa-user text-purple me-2"></i>Faculty Profile</h1>
    </div>
    <div class="d-flex flex-wrap gap-1.5 gap-sm-2">
        <button class="btn btn-sm btn-sms-primary flex-fill flex-sm-grow-0 py-2 px-3 d-inline-flex align-items-center justify-content-center">
            <i class="fas fa-file-pdf me-1.5"></i>
            <span>Download<span class="d-inline d-sm-none"> CV</span><span class="d-none d-sm-inline"> CV</span></span>
        </button>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Top Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 bg-body-tertiary">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <span class="text-body-secondary small fw-semibold">Total Faculty</span>
                        <h4 class="fw-bold mb-0"><?= $totalFaculty ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 bg-body-tertiary">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                        <i class="fas fa-user-check fa-lg"></i>
                    </div>
                    <div>
                        <span class="text-body-secondary small fw-semibold">Full-Time / Regular</span>
                        <h4 class="fw-bold mb-0"><?= $regularCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 bg-body-tertiary">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                        <i class="fas fa-book-reader fa-lg"></i>
                    </div>
                    <div>
                        <span class="text-body-secondary small fw-semibold">Part-Time / Contract</span>
                        <h4 class="fw-bold mb-0"><?= $partTimeCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 bg-body-tertiary">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 me-3">
                        <i class="fas fa-star fa-lg"></i>
                    </div>
                    <div>
                        <span class="text-body-secondary small fw-semibold">Dept Avg Rating</span>
                        <h4 class="fw-bold mb-0">4.52</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Directory Controls: Search & Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input id="directorySearch" type="text" class="form-control border-start-0 bg-body-tertiary" placeholder="Search by name, ID, or email...">
                    </div>
                </div>

                <!-- Rank Filter -->
                <div class="col-6 col-md-3">
                    <select id="rankFilter" class="form-select bg-body-tertiary">
                        <option value="all">All Ranks</option>
                        <option value="professor">Professor</option>
                        <option value="associate">Associate Professor</option>
                        <option value="assistant">Assistant Professor</option>
                        <option value="instructor">Instructor</option>
                    </select>
                </div>

                <!-- Employment Type Filter -->
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
    </div>

    <!-- Faculty Member Table -->
    <?php if (count($facultyProfiles) === 0): ?>
        <div class="alert alert-info rounded-3">
            No faculty profiles are available for your assigned department.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="facultyTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Photo</th>
                        <th scope="col">Faculty ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Department</th>
                        <th scope="col">Position</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="facultyTableBody">
                    <?php foreach ($facultyProfiles as $profile): ?>
                        <?php
                            $fullName = function_exists('buildFacultyFullName')
                                ? buildFacultyFullName($profile)
                                : trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['middle_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
                            $rawDept = (string) ($profile['designated_department'] ?? $profile['designated_dept'] ?? '');
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
                            $initials = implode('', array_slice(array_map(function ($part) {
                                return strtoupper(substr($part, 0, 1));
                            }, array_filter(explode(' ', $fullName))), 0, 2));
                            if ($initials === '') {
                                $initials = 'NA';
                            }
                        ?>
                        <tr class="faculty-row" 
                            data-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" 
                            data-dept="<?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?>" 
                            data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>" 
                            data-academic-rank="<?= htmlspecialchars((string) ($profile['academic_rank'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                            data-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>" 
                            data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" 
                            data-id="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px;">
                                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td class="faculty-id-field fw-semibold text-uppercase"><?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge border border-primary text-primary fw-medium px-2 py-1"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-capitalize"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-capitalize"><?= htmlspecialchars($profileStatus, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
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

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="text-muted small" id="paginationInfo">
                Showing 0 to 0 of 0 entries
            </div>
            <nav aria-label="Faculty pagination">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    <!-- Pagination JS dynamically populates items here -->
                </ul>
            </nav>
        </div>
    <?php endif; ?>
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
                    <!-- Basic Information -->
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

                    <!-- Contact Information -->
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

                    <!-- Role & Status -->
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
        if (!window.bootstrap || !bootstrap.Modal) {
            console.warn('Bootstrap modal API is not available.');
            return null;
        }

        const modalEl = document.getElementById(id);
        if (!modalEl) {
            console.warn('Modal element not found:', id);
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalEl);
    }

    const academicRankSelect = document.getElementById('academicRank');
    const tierSelect = document.getElementById('tier');

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
        if (!academicRankSelect || !tierSelect) {
            return;
        }

        const selectedRank = academicRankSelect.value;
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
        academicRankSelect.addEventListener('change', () => populateTierOptions(tierSelect.value));
    }

    function viewFaculty(button) {
        if (!button || !button.dataset) {
            return;
        }
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
        if (!button || !button.dataset) {
            return;
        }
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
        if (value.includes('active')) {
            return 'bg-success';
        }
        if (value.includes('probationary')) {
            return 'bg-warning text-dark';
        }
        if (value.includes('part-time')) {
            return 'bg-info text-dark';
        }
        if (value.includes('inactive') || value.includes('resigned')) {
            return 'bg-secondary';
        }
        return 'bg-primary';
    }

    function getInitials(fullName) {
        return (fullName || '')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(name => name.charAt(0).toUpperCase())
            .join('') || 'NA';
    }

    // --- SEARCH, FILTERS & PAGINATION LOGIC ---
    document.addEventListener('DOMContentLoaded', function () {
        const rowsPerPage = 10;
        let currentPage = 1;
        let filteredRows = [];

        const searchInput = document.getElementById('directorySearch');
        const rankFilter = document.getElementById('rankFilter');
        const statusFilter = document.getElementById('statusFilter');
        const allRows = Array.from(document.querySelectorAll('.faculty-row'));
        const paginationControls = document.getElementById('paginationControls');
        const paginationInfo = document.getElementById('paginationInfo');

        function filterRows() {
            const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const selectedRank = rankFilter ? rankFilter.value.toLowerCase() : 'all';
            const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

            filteredRows = allRows.filter(row => {
                const name = (row.dataset.name || '').toLowerCase();
                const email = (row.dataset.email || '').toLowerCase();
                const id = (row.dataset.id || '').toLowerCase();
                const academicRank = (row.dataset.academicRank || '').toLowerCase();
                const status = (row.dataset.status || '').toLowerCase();

                // Search match
                const matchesSearch = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm) || id.includes(searchTerm);
                
                // Rank match
                const matchesRank = selectedRank === 'all' || academicRank.includes(selectedRank);

                // Status match
                let matchesStatus = selectedStatus === 'all';
                if (!matchesStatus) {
                    if (selectedStatus === 'regular') {
                        matchesStatus = status.includes('regular') || status.includes('active') || status.includes('full-time');
                    } else {
                        matchesStatus = status.includes(selectedStatus);
                    }
                }

                return matchesSearch && matchesRank && matchesStatus;
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

            // Hide all rows, then show only slice for current page
            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(startIndex, endIndex).forEach(row => row.style.display = '');

            // Update Info text
            if (paginationInfo) {
                if (totalRows === 0) {
                    paginationInfo.textContent = 'Showing 0 entries';
                } else {
                    paginationInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
                }
            }

            // Build page numbers
            if (!paginationControls) return;
            paginationControls.innerHTML = '';

            if (totalPages <= 1) return;

            // Previous Button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    renderPagination();
                }
            });
            paginationControls.appendChild(prevLi);

            // Numbered Buttons
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    renderPagination();
                });
                paginationControls.appendChild(li);
            }

            // Next Button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPagination();
                }
            });
            paginationControls.appendChild(nextLi);
        }

        // Attach Event Listeners
        if (searchInput) searchInput.addEventListener('input', filterRows);
        if (rankFilter) rankFilter.addEventListener('change', filterRows);
        if (statusFilter) statusFilter.addEventListener('change', filterRows);

        // Initial Run
        filterRows();
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>