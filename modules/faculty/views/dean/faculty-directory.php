<?php
/**
 * SMS 2 - Faculty Directory
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
if (file_exists(__DIR__ . '/../includes/faculty-data.php')) {
    require_once __DIR__ . '/../includes/faculty-data.php';
}

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

<!-- Faculty account requests panel removed; approve/deny buttons moved into each faculty card below -->

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Faculty Directory</h1>
    </div>
    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle bg-body text-body py-2 px-3 fw-bold">
            <i class="fas fa-print me-2"></i>Print Directory
        </button>      
            <button type="button" class="btn btn-sm btn-primary py-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                <i class="fas fa-user-plus me-2"></i>Add Department Head Profile
            </button>
    </div>
</div>

<!-- Dynamic Repository Filters Row -->
    <div class="alert alert-success rounded-3 mb-4" role="alert">
    </div>
    <div class="alert alert-danger rounded-3 mb-4" role="alert">
    </div>
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
                <option value="Information Technology">Information Technology</option>
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

<!-- Faculty Cards Directory Matrix Grid -->
<div class="row g-3 mb-4" id="facultyGrid">
        <div class="col-12">
            <div class="alert alert-info rounded-3" role="alert">
                No faculty profiles are available yet.
            </div>
        </div>
            <div class="col-12 col-md-6 col-xl-4 faculty-card-item">
                <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100 p-3 position-relative">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-circle bg-secondary bg-opacity-20 d-flex align-items-center justify-content-center border border-secondary-subtle fw-bold text-primary shadow-sm" style="width: 48px; height: 48px; min-width: 48px;"></div>
                        <div class="w-100">
                            <span class="badge bg-body-tertiary border border-secondary-subtle text-muted mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;"></span>
                            <h5 class="mb-0 fw-bold name-field" style="font-size: 0.95rem;"></h5>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"></small>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 border-top border-secondary-subtle pt-3 text-muted" style="font-size: 0.8rem;">
                                <div class="d-flex justify-content-between gap-2"><span>Employment Contract:</span><strong class="text-body-emphasis text-end"></strong></div>
                        <div class="d-flex justify-content-between gap-2"><span>Email:</span><a href="mailto:<?= htmlspecialchars($profile['email'] ?? '#') ?>" class="text-primary text-decoration-none text-end"><?= htmlspecialchars($profile['email'] ?? '') ?></a></div>
                    </div>
                        <div class="d-flex gap-2 mt-3">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="approve_request">
                                <input type="hidden" name="profile_id" value="">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="deny_request">
                                <input type="hidden" name="profile_id" value="">
                                <button type="submit" class="btn btn-sm btn-danger">Deny</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-bs-toggle="modal" data-bs-target="#viewProfileModal-<?= (int) ($profile['id'] ?? 0) ?>">View</button>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewProfileModal-<?= (int) ($profile['id'] ?? 0) ?>">View</button>
                        </div>
                </div>
            </div>

            <!-- View Profile Modal -->
            <div class="modal fade" id="viewProfileModal-" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewProfileModalLabel-</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <dl class="row">
                            </dl>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- Plain Bootstrap Centered Pagination Container -->
<nav aria-label="Directory navigation" class="d-flex justify-content-center mb-4">
    <ul class="pagination pagination-sm mb-0 shadow-sm" id="directoryPagination">
        <!-- Generated Dynamically via JS -->
    </ul>
</nav>

<!-- Modal Container Component: Add Faculty Academic Registry -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow">
            <div class="modal-header bg-body-tertiary border-bottom py-3">
                <h5 class="modal-title fw-bold text-body" id="addFacultyModalLabel">Add Faculty Academic Registry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addFacultyForm" method="post" action="<?= htmlspecialchars(BASE_URL . '/modules/faculty/pages/faculty-directory.php') ?>">
                    <input type="hidden" name="action" value="add_department_head">
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Full Name</label>
                            <input type="text" name="first_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="First Name" required><br>
                            <input type="text" name="middle_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Middle Name">
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">&nbsp;</label>
                            <input type="text" name="last_name" class="form-control bg-body border-secondary-subtle text-body" placeholder="Last Name" required><br>
                            <input type="text" name="suffix" class="form-control bg-body border-secondary-subtle text-body" placeholder="Suffix">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-4">
                            <label for="birthdate" class="form-label text-muted small fw-bold mb-1">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-muted small fw-bold mb-1">Age</label>
                            <input type="text" id="addAge" class="form-control bg-body border-secondary-subtle text-body" placeholder="Age" disabled>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-muted small fw-bold mb-1">Sex</label>
                            <select name="sex" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Phone</label>
                            <input type="tel" name="phone" class="form-control bg-body border-secondary-subtle text-body" placeholder="Phone Number" required>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Email</label>
                            <input type="email" name="email" class="form-control bg-body border-secondary-subtle text-body" placeholder="Email Address" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Designated Department</label>
                            <select id="addDept" name="designated_dept" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="" selected disabled>-- Select a Department --</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Position</label>
                            <select name="position" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="Department Head" selected>Department Head</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6">
                            <label for="hired_date" class="form-label text-muted small fw-bold mb-1">Hired Date</label>
                            <input type="date" id="hired_date" name="hired_date" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>
                        <div class="col-6 col-sm-6" id="contractualEndCol">
                            <label for="contractual_end" class="form-label text-muted small fw-bold mb-1">Contractual End Date</label>
                            <input type="date" id="contractual_end" name="contractual_end" class="form-control bg-body border-secondary-subtle text-body" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6">
                            <label class="form-label text-muted small fw-bold mb-1">Employment Status</label>
                            <select id="employmentStatus" name="employment_status" class="form-select bg-body border-secondary-subtle text-body" required>
                                <option value="regular" selected>Regular / Permanent</option>
                                <option value="probationary">Probationary</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6">
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
    const cardsPerPage = 3; // Change this number to control elements visible per view state page
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
        if (!birthDateString) {
            return '';
        }

        const birthDate = new Date(birthDateString);
        const today = new Date();

        if (isNaN(birthDate.getTime()) || birthDate > today) {
            return '';
        }

        let years = today.getFullYear() - birthDate.getFullYear();
        const monthDelta = today.getMonth() - birthDate.getMonth();
        const dayDelta = today.getDate() - birthDate.getDate();

        if (monthDelta < 0 || (monthDelta === 0 && dayDelta < 0)) {
            years -= 1;
        }

        return years >= 0 ? years : '';
    }

    birthdateInput.addEventListener("change", function () {
        ageInput.value = computeAge(this.value);
    });

    function updateContractualEndVisibility() {
        const isRegular = employmentStatusSelect.value === 'regular';
        if (contractualEndCol) {
            contractualEndCol.style.display = isRegular ? 'none' : 'block';
            const contractualInput = contractualEndCol.querySelector('input');
            if (contractualInput) {
                contractualInput.required = !isRegular;
            }
        }
    }

    employmentStatusSelect.addEventListener('change', updateContractualEndVisibility);
    updateContractualEndVisibility();

    function renderEngine() {
        const cards = Array.from(facultyGrid.querySelectorAll(".faculty-card-item"));
        const rawInput = searchInput.value;
        const sanitizedInput = rawInput.replace(/<\/?[^>]+(>|$)/g, ""); // Strips any <script> or HTML tags
        const searchText = sanitizedInput.toLowerCase().trim();
        const activeDept = deptFilter.value;
        const activeStatus = statusFilter.value;

        // 1. Identify which rows clear criteria parameters
        let visibleCards = cards.filter(card => {
            const name = card.querySelector(".name-field").textContent.toLowerCase();
            const cardDept = card.getAttribute("data-dept");
            const cardStatus = card.getAttribute("data-status");

            const matchesSearch = name.includes(searchText);
            const matchesDept = (activeDept === "All" || cardDept === activeDept);
            const matchesStatus = (activeStatus === "All" || cardStatus === activeStatus);

            return matchesSearch && matchesDept && matchesStatus;
        });

        // 2. Clear current display properties
        cards.forEach(card => card.classList.add("d-none"));

        // 3. Map bounds pagination range
        const totalPages = Math.ceil(visibleCards.length / cardsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * cardsPerPage;
        const endIndex = startIndex + cardsPerPage;
        const pageSlice = visibleCards.slice(startIndex, endIndex);

        // 4. Reveal current sliced selection blocks
        pageSlice.forEach(card => card.classList.remove("d-none"));

        // 5. Generate matching numeric selector lists
        paginationUl.innerHTML = "";
        
        // Prev button
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link bg-body border-secondary-subtle text-muted py-2 px-3" href="#"><i class="fas fa-chevron-left small"></i></a>`;
        prevLi.addEventListener("click", (e) => { e.preventDefault(); if(currentPage > 1) { currentPage--; renderEngine(); } });
        paginationUl.appendChild(prevLi);

        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            li.className = `page-item ${currentPage === i ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link border-secondary-subtle py-2 px-3 ${currentPage === i ? 'bg-primary text-white border-primary' : 'bg-body text-body'}" href="#">${i}</a>`;
            li.addEventListener("click", (e) => { e.preventDefault(); currentPage = i; renderEngine(); });
            paginationUl.appendChild(li);
        }

        // Next button
        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link bg-body border-secondary-subtle text-muted py-2 px-3" href="#"><i class="fas fa-chevron-right small"></i></a>`;
        nextLi.addEventListener("click", (e) => { e.preventDefault(); if(currentPage < totalPages) { currentPage++; renderEngine(); } });
        paginationUl.appendChild(nextLi);
    }

// Event hooks
    searchInput.addEventListener("input", () => { currentPage = 1; renderEngine(); });
    deptFilter.addEventListener("change", () => { currentPage = 1; renderEngine(); });
    statusFilter.addEventListener("change", () => { currentPage = 1; renderEngine(); });

    // Initial load
    renderEngine();
});
</script>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>











