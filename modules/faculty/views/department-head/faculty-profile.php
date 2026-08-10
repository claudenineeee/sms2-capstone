<?php
/**
 * Faculty Profile (View Details)
 * Purpose: Detailed view of individual faculty member
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$headDepartment = trim($_SESSION['faculty_department'] ?? '');

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
                        <h4 class="fw-bold mb-0">24</h4>
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
                        <h4 class="fw-bold mb-0">18</h4>
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
                        <h4 class="fw-bold mb-0">6</h4>
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

    <!-- Directory Controls: Search, Filter, and Add Button -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-body-tertiary" placeholder="Search by name, ID, or email...">
                    </div>
                </div>

                <!-- Rank Filter -->
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select bg-body-tertiary">
                        <option value="">All Ranks</option>
                        <option value="professor">Professor</option>
                        <option value="associate">Associate Professor</option>
                        <option value="assistant">Assistant Professor</option>
                        <option value="instructor">Instructor</option>
                    </select>
                </div>

                <!-- Employment Type Filter -->
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select bg-body-tertiary">
                        <option value="">All Statuses</option>
                        <option value="regular">Regular</option>
                        <option value="contractual">Contractual</option>
                        <option value="part-time">Part-Time</option>
                    </select>
                </div>

                <!-- Add New Faculty Trigger -->
                <div class="col-12 col-md-2 col-lg-4 text-md-end ms-auto">
</div>
        </div>
    </div>

    <!-- Faculty Member Cards Grid -->
    <div class="row g-3">

        <!-- Member Card 1 -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-truncate" title="Dr. Maria Santos">Dr. Maria Santos</h6>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Professor</span>
                            </div>
                            <span class="text-body-secondary small d-block mb-2">ID: F-001 • Regular</span>
                            
                            <div class="d-flex justify-content-between align-items-center bg-body-tertiary p-2 rounded small mb-3">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Teaching Load</span>
                                    <strong>24 Units</strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Rating</span>
                                    <span class="fw-bold text-success">4.5 <i class="fas fa-star text-warning"></i></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-fill" data-bs-toggle="offcanvas" data-bs-target="#facultyDetailDrawer">
                                    <i class="fas fa-eye me-1"></i> View Profile
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#noteModal">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Card 2 -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-truncate" title="Prof. Luis Tan">Prof. Luis Tan</h6>
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">Associate Prof.</span>
                            </div>
                            <span class="text-body-secondary small d-block mb-2">ID: F-002 • Regular</span>
                            
                            <div class="d-flex justify-content-between align-items-center bg-body-tertiary p-2 rounded small mb-3">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Teaching Load</span>
                                    <strong>21 Units</strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Rating</span>
                                    <span class="fw-bold text-success">4.5 <i class="fas fa-star text-warning"></i></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-fill" data-bs-toggle="offcanvas" data-bs-target="#facultyDetailDrawer">
                                    <i class="fas fa-eye me-1"></i> View Profile
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#noteModal">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Card 3 -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-truncate" title="Dr. Ana Reyes">Dr. Ana Reyes</h6>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Assistant Prof.</span>
                            </div>
                            <span class="text-body-secondary small d-block mb-2">ID: F-003 • Part-Time</span>
                            
                            <div class="d-flex justify-content-between align-items-center bg-body-tertiary p-2 rounded small mb-3">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Teaching Load</span>
                                    <strong>12 Units</strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Rating</span>
                                    <span class="fw-bold text-success">4.5 <i class="fas fa-star text-warning"></i></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-fill" data-bs-toggle="offcanvas" data-bs-target="#facultyDetailDrawer">
                                    <i class="fas fa-eye me-1"></i> View Profile
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#noteModal">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================= -->
<!-- SLIDE-OVER DRAWER (Offcanvas) FOR SELECTED FACULTY PROFILE -->
<!-- ========================================================= -->
<div class="offcanvas offcanvas-end" style="width: 600px;" tabindex="-1" id="facultyDetailDrawer">
    <div class="offcanvas-header border-bottom bg-body-tertiary">
        <h5 class="offcanvas-title fw-bold">Faculty Detailed View</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <!-- Faculty Header Info -->
        <div class="text-center mb-4">
            <i class="fas fa-user-circle fa-5x text-secondary mb-2"></i>
            <h4 class="fw-bold mb-0">Dr. Maria Santos</h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Professor</span>
            <p class="text-body-secondary small mt-1 mb-0">College of Computer Studies • ID: F-001</p>
        </div>

        <!-- Dynamic Tabs inside Drawer -->
        <ul class="nav nav-pills nav-fill mb-3" id="drawerTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active small py-2" data-bs-toggle="tab" data-bs-target="#drawer-overview">Overview</button>
            </li>
            <li class="nav-item">
                <button class="nav-link small py-2" data-bs-toggle="tab" data-bs-target="#drawer-teaching">Teaching Load</button>
            </li>
            <li class="nav-item">
                <button class="nav-link small py-2" data-bs-toggle="tab" data-bs-target="#drawer-history">History</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="drawer-overview">
                <div class="card border bg-body-tertiary p-3 mb-3">
                    <div class="row g-2 small">
                        <div class="col-6"><strong>Email:</strong> msantos@bestlink.edu.ph</div>
                        <div class="col-6"><strong>Contact:</strong> +63 912 345 6789</div>
                        <div class="col-6"><strong>Tenure:</strong> 18 Years</div>
                        <div class="col-6"><strong>Contract:</strong> Regular</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">Academic Background</h6>
                <ul class="list-unstyled small ps-2 border-start border-2 border-primary mb-3">
                    <li class="mb-2"><strong>Ph.D. in Computer Science</strong> — UP (2015)</li>
                    <li class="mb-2"><strong>M.S. in Information Tech</strong> — DLSU (2010)</li>
                    <li><strong>B.S. in Computer Science</strong> — UST (2006)</li>
                </ul>
            </div>

            <!-- Teaching Load Tab -->
            <div class="tab-pane fade" id="drawer-teaching">
                <div class="table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Units</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>CS101 - Intro to CS</td><td>3</td><td>Lecture</td></tr>
                            <tr><td>CS201 - Data Structures</td><td>3</td><td>Lecture/Lab</td></tr>
                            <tr><td>CS301 - Algorithms</td><td>3</td><td>Lecture</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="drawer-history">
                <p class="small text-muted">Evaluation & Previous Teaching Load records load here dynamically.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
