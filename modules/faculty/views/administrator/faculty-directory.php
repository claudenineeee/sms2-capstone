<?php
/**
 * SMS 2 - Faculty Directory View
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

// Instantiate Controller & Handle Request
$controller   = new FacultyController();
$flash        = $controller->handleAddDepartmentHead();
$facultyList  = $controller->getDirectoryList();

$message     = $flash['message'] ?? '';
$messageType = $flash['type'] ?? 'success';

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

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Faculty Directory</h1>
        <p class="text-muted mb-0">Overview of all faculty profiles within the department</p>
    </div>
    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle bg-body text-body py-2 px-3 fw-bold">
            <i class="fas fa-print me-2"></i>Print Directory
        </button>      
        <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fas fa-user-plus me-2"></i>Add Faculty Profile
        </button>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> rounded-3 mb-4" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

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

<!-- Modal Component: Add Faculty Registry -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addFacultyModalLabel">Add Faculty Academic Registry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addFacultyForm" method="post" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_department_head">
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
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label for="birthdate" class="form-label text-muted small fw-bold mb-1">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label text-muted small fw-bold mb-1">Age</label>
                            <input type="text" id="addAge" class="form-control bg-body border-secondary-subtle text-body" placeholder="Age" disabled>
                        </div>
                        <div class="col-4">
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
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Designated Department</label>
                            <select id="addDept" name="designated_department" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>-- Select a Department --</option>
                                <option value="BSIT">Information Technology</option>
                                <option value="BSCE">Computer Engineering</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold mb-1">Position</label>
                            <select name="position" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" disabled selected>-- Select Position --</option>
                                <option value="Department Head">Department Head</option>
                                <option value="Department Secretary">Department Secretary</option>
                                <option value="Faculty Professor">Faculty Professor</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="hired_date" class="form-label text-muted small fw-bold mb-1">Hired Date</label>
                            <input type="date" id="hired_date" name="hired_date" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>
                        <div class="col-6" id="contractualEndCol">
                            <label for="contractual_end" class="form-label text-muted small fw-bold mb-1">Contractual End Date</label>
                            <input type="date" id="contractual_end" name="contractual_end" class="form-control bg-body border-secondary-subtle text-body" required>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 6 cards per page limit (3 rows x 2 columns)
    const cardsPerPage = 6;
    let currentPage = 1;

    const facultyGrid = document.getElementById("facultyGrid");
    const searchInput = document.getElementById("directorySearch");
    const deptFilter = document.getElementById("deptFilter");
    const statusFilter = document.getElementById("statusFilter");
    const paginationUl = document.getElementById("directoryPagination");
    const birthdateInput = document.getElementById("birthdate");
    const ageInput = document.getElementById("addAge");
    const employmentStatusSelect = document.getElementById("employmentStatus");
    const contractualEndCol = document.getElementById("contractualEndCol");

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

    if (birthdateInput) {
        birthdateInput.addEventListener("change", function () {
            ageInput.value = computeAge(this.value);
        });
    }

    function updateContractualEndVisibility() {
        if (!employmentStatusSelect || !contractualEndCol) return;
        const isRegular = employmentStatusSelect.value === 'regular';
        contractualEndCol.style.display = isRegular ? 'none' : 'block';
        const contractualInput = contractualEndCol.querySelector('input');
        if (contractualInput) contractualInput.required = !isRegular;
    }

    if (employmentStatusSelect) {
        employmentStatusSelect.addEventListener('change', updateContractualEndVisibility);
        updateContractualEndVisibility();
    }

    // Modal data populate handler
    function setupViewModal() {
        const viewModal = document.getElementById('viewProfileModal');
        if (!viewModal) return;

        facultyGrid.querySelectorAll('.view-profile-btn').forEach(button => {
            button.addEventListener('click', function() {
                viewModal.querySelector('#viewProfileModalLabel').textContent = this.dataset.fullName || 'Faculty Profile';
                viewModal.querySelector('#modalFacultyId').textContent = this.dataset.facultyId || '—';
                viewModal.querySelector('#modalPosition').textContent = this.dataset.position || '—';
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