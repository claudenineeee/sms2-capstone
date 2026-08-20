<?php
/**
 * SMS 2 - Faculty Profile (View)
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

$controller = new FacultyController();

// Process POST update request if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'update_faculty') {
    $profileId = (int) ($_POST['profile_id'] ?? 0);
    $updates = [
        'first_name'           => trim((string) ($_POST['first_name'] ?? '')),
        'middle_name'          => trim((string) ($_POST['middle_name'] ?? '')),
        'last_name'            => trim((string) ($_POST['last_name'] ?? '')),
        'suffix'               => trim((string) ($_POST['suffix'] ?? '')),
        'sex'                  => trim((string) ($_POST['sex'] ?? '')),
        'birthdate'            => trim((string) ($_POST['birthdate'] ?? '')),
        'phone'                => trim((string) ($_POST['phone'] ?? '')),
        'email'                => trim((string) ($_POST['email'] ?? '')),
        'hired_date'           => trim((string) ($_POST['hired_date'] ?? '')),
        'contractual_end_date' => trim((string) ($_POST['contractual_end_date'] ?? '')),
        'employment_status'    => trim((string) ($_POST['employment_status'] ?? '')),
    ];
    
    if ($profileId > 0 && method_exists($controller, 'updateFacultyProfile') && $controller->updateFacultyProfile($profileId, $updates)) {
        $updateMessage = 'Faculty profile updated successfully.';
        $updateMessageType = 'success';
    } else {
        $updateResult = $controller->handleUpdateFaculty();
        $updateMessage = $updateResult['message'] ?? '';
        $updateMessageType = $updateResult['type'] ?? 'success';
    }
}

// Retrieve faculty profiles
$facultyProfiles = $controller->getDirectoryList();

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

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>
<script src="<?= BASE_URL ?>/../../../../assets/js/loader.js"></script>

<!-- 1. Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Faculty Profile</h1>
    </div>
</div>

<!-- Faculty List Section -->
<div class="container-fluid my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="facultySearch" class="form-control border-start-0 ps-0" placeholder="Search faculty..." onkeyup="onSearchInput()">
                </div>
            </div>
        </div>

        <?php if (!empty($updateMessage)): ?>
            <div class="alert alert-<?= htmlspecialchars($updateMessageType, ENT_QUOTES, 'UTF-8') ?> rounded-3 m-3" role="alert">
                <?= htmlspecialchars($updateMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;">Photo</th>
                            <th style="width: 140px;">Faculty ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th style="width: 120px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="facultyListBody">
                        <?php if (empty($facultyProfiles)): ?>
                            <tr id="noDataRow">
                                <td colspan="7" class="text-center py-4 text-muted">No faculty profiles are available.</td>
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
                                    
                                    $initials = '';
                                    foreach (array_filter(explode(' ', $fullName)) as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                        if (strlen($initials) >= 2) break;
                                    }
                                    if ($initials === '') $initials = 'NA';
                                ?>
                                <tr class="faculty-row">
                                    <td>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold shadow-sm" 
                                            style="width: 40px; height: 40px; font-size: 14px; min-width: 40px;">
                                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge border border-primary text-primary fw-medium px-2 py-1"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-<?= $employmentStatus === 'Active' ? 'success' : ($employmentStatus === 'Probationary' ? 'warning text-dark' : 'secondary') ?>"><?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" onclick="viewFaculty(this)"
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
                                                title="View">👁️</button>
                                            <button type="button" class="btn btn-outline-warning" onclick="editFaculty(this)"
                                                data-profile-id="<?= (int) ($profile['id'] ?? 0) ?>"
                                                data-first-name="<?= htmlspecialchars(trim((string) ($profile['first_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-middle-name="<?= htmlspecialchars(trim((string) ($profile['middle_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-last-name="<?= htmlspecialchars(trim((string) ($profile['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-suffix="<?= htmlspecialchars(trim((string) ($profile['suffix'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                                data-phone="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>"
                                                data-hired-date="<?= htmlspecialchars($hiredDate, ENT_QUOTES, 'UTF-8') ?>"
                                                data-contractual-end="<?= htmlspecialchars($contractualEnd, ENT_QUOTES, 'UTF-8') ?>"
                                                data-sex="<?= htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') ?>"
                                                data-birthdate="<?= htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8') ?>"
                                                title="Edit">✎</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="text-muted small mb-2 mb-md-0" id="paginationInfo">
                Showing 0 to 0 of 0 entries
            </div>
            <nav aria-label="Faculty Table Pagination">
                <ul class="pagination pagination-sm mb-0" id="paginationList">
                    <!-- Dynamic Page Links -->
                </ul>
            </nav>
        </div>
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

                            <dt class="col-sm-4 text-muted small">Status</dt>
                            <dd class="col-sm-8" id="viewEmploymentStatus">Active</dd>

                            <dt class="col-sm-4 text-muted small">Profile Status</dt>
                            <dd class="col-sm-8" id="viewProfileStatus">Active</dd>

                            <dt class="col-sm-4 text-muted small">Academic Rank</dt>
                            <dd class="col-sm-8" id="viewAcademicRank">Assistant Professor</dd>

                            <dt class="col-sm-4 text-muted small">Tier</dt>
                            <dd class="col-sm-8" id="viewTier">Assistant Professor I</dd>

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
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>`;
        paginationList.appendChild(prevLi);

        // Page Number Buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
            paginationList.appendChild(pageLi);
        }

        // Next Button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>`;
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

    function viewFaculty(button) {
        if (!button || !button.dataset) return;
        const modal = getModalInstance('facultyModal');
        document.getElementById('viewInitials').textContent = getInitials(button.dataset.fullName || 'NA');
        document.getElementById('viewFullName').textContent = button.dataset.fullName || 'Unknown';
        const statusBadge = document.getElementById('viewStatusBadge');
        statusBadge.textContent = button.dataset.profileStatus || button.dataset.status || 'Unknown';
        statusBadge.className = 'badge ' + badgeColorClass(button.dataset.profileStatus || button.dataset.status || 'Active');
        document.getElementById('viewPosition').textContent = button.dataset.position || '';
        document.getElementById('viewDepartment').textContent = button.dataset.department || '';
        document.getElementById('viewFacultyId').textContent = button.dataset.facultyId || '';
        document.getElementById('viewDepartmentLabel').textContent = button.dataset.department || '';
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
        return (fullName || '')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(name => name.charAt(0).toUpperCase())
            .join('') || 'NA';
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>