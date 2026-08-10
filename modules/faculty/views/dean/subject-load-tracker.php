<?php
/**
 * SMS 2 - Subject Load Tracker
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Subject Load Tracker';
$activeModule = 'faculty';
$activePage   = 'subject-load-tracker';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Subject Load Tracker', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

echo '<link rel="stylesheet" href="' . BASE_URL . '/modules/faculty/assets/css/faculty.css">';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Subject Load Tracker</h1>
    </div>
</div>

<!-- Subject Load Tracker Section Header & Quick Analytics -->
<div class="row g-3 mb-4 dashboard-stats">
    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card primary border shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Active Faculty</h6>
                    <h4 class="mb-0 fw-bold">42 Professors</h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card success border shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-success fs-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Fully Loaded Faculty</h6>
                    <h4 class="mb-0 fw-bold">36 / 42 (85%)</h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card warning border shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-warning fs-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Unassigned Units</h6>
                    <h4 class="mb-0 fw-bold">15 Units Remaining</h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Main Section Grid -->
<div class="row g-4" id="subject-load-section">
    
    <!-- Left Column: Faculty Directory Loading Overview -->
    <div class="col-xl-5 col-lg-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            
            <!-- Card Header with Department Filter & Search -->
            <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-users-cog me-2"></i>Faculty Loading Directory
                    </h6>
                    <small class="text-body-secondary" id="visibleFacultyCount">Showing 3 Faculty</small>
                </div>

                <!-- Controls Row: Department Filter + Search Bar -->
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <select class="form-select form-select-sm bg-body text-body border-secondary-subtle" id="deptFilter" onchange="filterFaculty()">
                            <option value="ALL" selected>All Departments</option>
                            <option value="BSIT">BSIT Department</option>
                            <option value="BSCS">BSCS Department</option>
                            <option value="BSEMC">BSEMC Department</option>
                            <option value="BSIS">BSIS Department</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="input-group input-group-sm">
                            <input type="text" id="facultySearchInput" onkeyup="filterFaculty()" class="form-control bg-body text-body border-secondary-subtle" placeholder="Search name or ID...">
                            <button class="btn btn-outline-secondary border-secondary-subtle" type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="facultyList">
                    
                    <!-- Faculty Item 1 (BSIT) -->
                    <a href="javascript:void(0);" 
                       class="list-group-item list-group-item-action faculty-item bg-primary bg-opacity-10 text-body p-3 border-0 border-start border-4 border-primary" 
                       data-dept="BSIT"
                       data-search="prof eri alcantara s230000002 bsit"
                       onclick="loadFacultyLoad('S230000002', 'Prof. Eri Alcantara', '21/21', this)">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                                    EA
                                </div>
                                <div>
                                    <div class="fw-bold mb-0 text-body">Prof. Eri Alcantara</div>
                                    <small class="text-body-secondary">ID: S230000002 • <span class="badge bg-secondary-subtle text-body border border-secondary-subtle px-1">BSIT</span></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">21 / 21 Units</span>
                                <div class="progress mt-2 bg-body-tertiary" style="height: 5px; width: 90px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                                </div>
                                <small class="text-success fw-semibold mt-1 d-block" style="font-size: 10px;">Full Load</small>
                            </div>
                        </div>
                    </a>

                    <!-- Faculty Item 2 (BSCS) -->
                    <a href="javascript:void(0);" 
                       class="list-group-item list-group-item-action faculty-item bg-transparent text-body p-3 border-0 border-bottom border-secondary-subtle" 
                       data-dept="BSCS"
                       data-search="prof juan dela cruz s230000008 bscs"
                       onclick="loadFacultyLoad('S230000008', 'Prof. Juan Dela Cruz', '24/21', this)">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-body rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px;">
                                    JD
                                </div>
                                <div>
                                    <div class="fw-bold mb-0 text-body">Prof. Juan Dela Cruz</div>
                                    <small class="text-body-secondary">ID: S230000008 • <span class="badge bg-secondary-subtle text-body border border-secondary-subtle px-1">BSCS</span></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">24 / 21 Units</span>
                                <div class="progress mt-2 bg-body-tertiary" style="height: 5px; width: 90px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%;"></div>
                                </div>
                                <small class="text-danger fw-semibold mt-1 d-block" style="font-size: 10px;">+3 Units Overload</small>
                            </div>
                        </div>
                    </a>

                    <!-- Faculty Item 3 (BSIT) -->
                    <a href="javascript:void(0);" 
                       class="list-group-item list-group-item-action faculty-item bg-transparent text-body p-3 border-0 border-bottom border-secondary-subtle" 
                       data-dept="BSIT"
                       data-search="prof maria santos s230000012 bsit"
                       onclick="loadFacultyLoad('S230000012', 'Prof. Maria Santos', '15/21', this)">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-body rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px;">
                                    MS
                                </div>
                                <div>
                                    <div class="fw-bold mb-0 text-body">Prof. Maria Santos</div>
                                    <small class="text-body-secondary">ID: S230000012 • <span class="badge bg-secondary-subtle text-body border border-secondary-subtle px-1">BSIT</span></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">15 / 21 Units</span>
                                <div class="progress mt-2 bg-body-tertiary" style="height: 5px; width: 90px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 71%;"></div>
                                </div>
                                <small class="text-warning-emphasis fw-semibold mt-1 d-block" style="font-size: 10px;">6 Units Remaining</small>
                            </div>
                        </div>
                    </a>

                </div>

                <!-- Empty State Message when no match is found -->
                <div id="noFacultyMessage" class="text-center py-4 d-none">
                    <i class="fas fa-user-slash text-body-secondary fs-3 mb-2"></i>
                    <p class="text-body-secondary mb-0 small">No faculty members found matching the selected filter.</p>
                </div>
            </div>
            
            <div class="card-footer bg-body-tertiary text-center py-2 border-top border-secondary-subtle">
                <small class="text-body-secondary" id="footerFacultyCount">Showing 3 of 42 Faculty Members</small>
            </div>
        </div>
    </div>

    <!-- Right Column: Subject Load Matrix View -->
    <div class="col-xl-7 col-lg-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3">
                <div>
                    <h6 class="mb-0 text-primary fw-bold" id="target-faculty-name">
                        <i class="fas fa-book-open me-2"></i>Subject Loading Matrix: <span class="text-body" id="activeFacultyName">Prof. Eri Alcantara</span>
                    </h6>
                    <small class="text-body-secondary" id="target-faculty-meta">Max Allowed: 21 Units | Assigned: 21 Units | Remaining: 0 Units</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary border-secondary-subtle" title="Export Schedule PDF">
                        <i class="fas fa-file-pdf text-danger"></i>
                    </button>
                    <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#assignSubjectModal">
                        <i class="fas fa-plus"></i> Add Subject Load
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark text-body-secondary small text-uppercase">
                            <tr class="border-bottom border-secondary-subtle">
                                <th class="ps-3" style="width: 15%">Code</th>
                                <th style="width: 35%">Subject Description</th>
                                <th class="text-center" style="width: 10%">Units</th>
                                <th style="width: 30%">Schedule & Location</th>
                                <th class="text-end pe-3" style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-bottom border-secondary-subtle">
                                <td class="ps-3">
                                    <span class="fw-bold text-primary font-monospace">IT311</span>
                                    <span class="badge bg-body-tertiary text-body border border-secondary-subtle d-block mt-1 font-monospace" style="font-size: 10px;">Sec: BSIT 3-A</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-body">Advanced Database Systems</div>
                                    <small class="text-body-secondary"><i class="fas fa-layer-group me-1"></i>Lecture & Lab Component</small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-body">3.0</span>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="far fa-clock me-1"></i>MW 08:00 AM - 09:30 AM</span>
                                    </div>
                                    <small class="text-body-secondary"><i class="fas fa-door-open me-1 text-warning"></i>Lab Room CL3</small>
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm text-danger border-0 p-1" title="Unassign Subject">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="border-bottom border-secondary-subtle">
                                <td class="ps-3">
                                    <span class="fw-bold text-primary font-monospace">IT312</span>
                                    <span class="badge bg-body-tertiary text-body border border-secondary-subtle d-block mt-1 font-monospace" style="font-size: 10px;">Sec: BSIT 3-B</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-body">Web Development Technologies</div>
                                    <small class="text-body-secondary"><i class="fas fa-layer-group me-1"></i>Capstone Foundation Block</small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-body">3.0</span>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="far fa-clock me-1"></i>TTH 01:00 PM - 02:30 PM</span>
                                    </div>
                                    <small class="text-body-secondary"><i class="fas fa-door-open me-1 text-warning"></i>Lecture Room 402</small>
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm text-danger border-0 p-1" title="Unassign Subject">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer & Commitment Action -->
            <div class="card-footer bg-body-tertiary border-top border-secondary-subtle d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center gap-3">
                    <small class="text-body-secondary">Total Classes: <strong class="text-body">2 Sections</strong></small>
                    <small class="text-body-secondary">Total Units: <strong class="text-primary">6.0 Units</strong></small>
                </div>
                <button type="button" class="btn btn-primary btn-sm px-4">
                    <i class="fas fa-save me-1"></i> Commit Loading Updates
                </button>
            </div>

        </div>
    </div>

</div>

<!-- Filter Logic JS -->
<script>
function filterFaculty() {
    const selectedDept = document.getElementById('deptFilter').value;
    const searchVal = document.getElementById('facultySearchInput').value.toLowerCase().trim();
    const items = document.querySelectorAll('.faculty-item');
    const noMsg = document.getElementById('noFacultyMessage');
    
    let visibleCount = 0;

    items.forEach(item => {
        const itemDept = item.getAttribute('data-dept');
        const itemSearch = item.getAttribute('data-search').toLowerCase();

        const matchesDept = (selectedDept === 'ALL' || itemDept === selectedDept);
        const matchesSearch = (searchVal === '' || itemSearch.includes(searchVal));

        if (matchesDept && matchesSearch) {
            item.classList.remove('d-none');
            visibleCount++;
        } else {
            item.classList.add('d-none');
        }
    });

    // Toggle Empty Message
    if (visibleCount === 0) {
        noMsg.classList.remove('d-none');
    } else {
        noMsg.classList.add('d-none');
    }

    // Update Counters
    document.getElementById('visibleFacultyCount').textContent = `Showing ${visibleCount} Faculty`;
    document.getElementById('footerFacultyCount').textContent = `Showing ${visibleCount} of 42 Faculty Members`;
}

function loadFacultyLoad(id, name, load, element) {
    // Update active highlight style
    document.querySelectorAll('.faculty-item').forEach(el => {
        el.classList.remove('bg-primary', 'bg-opacity-10', 'border-start', 'border-4', 'border-primary');
        el.classList.add('bg-transparent');
    });

    element.classList.remove('bg-transparent');
    element.classList.add('bg-primary', 'bg-opacity-10', 'border-start', 'border-4', 'border-primary');

    // Update right panel header
    document.getElementById('activeFacultyName').textContent = name;
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
