<?php
/**
 * Schedule Approval
 * Purpose: Review and approve generated class schedules
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Schedule Approval';
$activeModule = 'faculty';
$activePage   = 'schedule-approval';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark"><i class="fas fa-calendar-check text-primary me-2"></i>Schedule Approval</h1>
        <p class="text-secondary mb-0">Review and approve generated class schedules</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success" onclick="approveAll()"><i class="fas fa-check-double me-1"></i>Approve All (No Conflicts)</button>
        <button class="btn btn-danger" onclick="rejectSelected()"><i class="fas fa-times me-1"></i>Reject Selected</button>
        <button class="btn btn-outline-primary" onclick="requestModification()"><i class="fas fa-edit me-1"></i>Request Modification</button>
        <button class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<!-- Top Fixed/Sticky Notification Banner for Conflicts (Dark Theme Ready) -->
<div class="alert fade show p-0 mb-4 border border-danger border-opacity-25 shadow-sm bg-danger bg-opacity-10" role="alert" style="border-radius: 12px; overflow: hidden;">
    <div class="d-flex align-items-stretch">
        <!-- Notification Visual Strip -->
        <div class="d-flex align-items-center justify-content-center px-3 px-md-4 bg-danger bg-opacity-25 border-end border-danger border-opacity-25">
            <div class="position-relative d-flex align-items-center justify-content-center">
                <i class="fas fa-bell fs-4 text-danger"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-dark rounded-circle" style="animation: pulse 1.5s infinite;">
                    <span class="visually-hidden">New alert</span>
                </span>
            </div>
        </div>

        <!-- Notification Content -->
        <div class="p-3 p-md-3 flex-grow-1 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill text-uppercase px-2 py-0.5 bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        Action Required
                    </span>
                    <span class="small text-body-secondary">Just now</span>
                </div>
                <p class="mb-0 text-body small fw-medium">
                    <strong class="text-danger">Conflict Detected in Room 301:</strong> Double booking on <span class="text-body-emphasis fw-bold">Friday, 1:00 PM - 3:00 PM</span> (CS301 & IT401).
                </p>
            </div>

            <!-- Notification Actions -->
            <div class="d-flex align-items-center gap-2 align-self-end align-self-md-center">
                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3 py-1.5" onclick="viewConflict()">
                    <i class="fas fa-eye me-1.5"></i>Review
                </button>
                <button type="button" class="btn btn-sm btn-danger fw-semibold px-3 py-1.5 text-white" onclick="resolveConflict()">
                    <i class="fas fa-bolt me-1.5"></i>Resolve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Metrics Cards Row -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Subjects -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border shadow-sm h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3 bg-primary-subtle text-primary">
                        <i class="fas fa-book fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Subjects</h6>
                        <h3 class="mb-0 fw-bold text-dark">42</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1 bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Active</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Rooms Used -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border shadow-sm h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3 bg-success-subtle text-success">
                        <i class="fas fa-door-open fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.5px;">Rooms Used</h6>
                        <h3 class="mb-0 fw-bold text-dark">12</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1 bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Available</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Conflicts Detected -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border shadow-sm h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3 bg-danger-subtle text-danger">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.5px;">Conflicts Detected</h6>
                        <h3 class="mb-0 fw-bold text-dark">1</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1 bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Critical</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Faculty Assigned -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border shadow-sm h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3 bg-info-subtle text-info">
                        <i class="fas fa-users fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.5px;">Faculty Assigned</h6>
                        <h3 class="mb-0 fw-bold text-dark">18</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1 bg-info-subtle text-info border border-info-subtle" style="font-size: 0.7rem;">Assigned</span>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters Toolbar -->
<div class="card border shadow-sm mb-4 bg-white" style="border-color: #e2e8f0; border-radius: 12px;">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-secondary">Subject Code</label>
                <input type="text" class="form-control form-control-sm text-dark bg-light border-secondary-subtle" placeholder="e.g. CS101">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-secondary">Instructor</label>
                <input type="text" class="form-control form-control-sm text-dark bg-light border-secondary-subtle" placeholder="Name...">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-secondary">Room</label>
                <input type="text" class="form-control form-control-sm text-dark bg-light border-secondary-subtle" placeholder="e.g. 301">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-secondary">Conflict Status</label>
                <select class="form-select form-select-sm text-dark bg-light border-secondary-subtle">
                    <option value="">All</option>
                    <option>No Conflict</option>
                    <option selected>Conflict Detected</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-secondary">Day</label>
                <select class="form-select form-select-sm text-dark bg-light border-secondary-subtle">
                    <option value="">All</option>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-primary btn-sm flex-fill fw-semibold py-2">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <button class="btn btn-outline-secondary btn-sm flex-fill fw-semibold py-2">
                        <i class="fas fa-redo me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Approval Data Table -->
<div class="card border shadow-sm mb-4 bg-white" style="border-color: #e2e8f0; border-radius: 12px; overflow: hidden;">
    <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light border-bottom">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="fas fa-list me-2 text-primary"></i>Schedule Pending Approval <span class="small fw-normal text-secondary">(42 entries)</span>
        </h6>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm text-dark bg-white border-secondary-subtle" style="width: auto;">
                <option>10 per page</option>
                <option>25 per page</option>
                <option>50 per page</option>
            </select>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr class="text-uppercase small text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <th class="ps-4" style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th>Code</th>
                        <th>Subject</th>
                        <th>Instructor</th>
                        <th>Room</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Conflict</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-dark small">
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-dark">CS101</td>
                        <td>Intro to CS</td>
                        <td class="text-secondary">Dr. M. Santos</td>
                        <td>201</td>
                        <td>MWF</td>
                        <td>8:00-9:30</td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-success-subtle text-success border border-success-subtle">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-dark">CS201</td>
                        <td>Data Structures</td>
                        <td class="text-secondary">Prof. L. Tan</td>
                        <td>202</td>
                        <td>TTH</td>
                        <td>10:00-11:30</td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-success-subtle text-success border border-success-subtle">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="table-danger">
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-danger">CS301</td>
                        <td class="fw-semibold text-danger">Algorithms</td>
                        <td class="text-secondary">Prof. K. Lim</td>
                        <td>301</td>
                        <td>F</td>
                        <td>1:00-3:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-danger-subtle text-danger border border-danger-subtle">Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewConflict()"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="table-danger">
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-danger">IT401</td>
                        <td class="fw-semibold text-danger">Network Security</td>
                        <td class="text-secondary">Prof. J. Aquino</td>
                        <td>301</td>
                        <td>F</td>
                        <td>1:00-3:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-danger-subtle text-danger border border-danger-subtle">Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewConflict()"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-dark">CS401</td>
                        <td>Software Eng</td>
                        <td class="text-secondary">Dr. A. Reyes</td>
                        <td>203</td>
                        <td>MWF</td>
                        <td>9:30-11:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-success-subtle text-success border border-success-subtle">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light border-top">
        <span class="small text-secondary">Showing 1-5 of 42 entries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-white text-dark border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header py-3 px-4 border-bottom">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fas fa-calendar me-2 text-primary"></i>Schedule Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="small d-block text-secondary">Subject</span>
                            <strong class="text-dark">CS301 - Algorithms</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="small d-block text-secondary">Instructor</span>
                            <strong class="text-dark">Prof. Katherine Lim</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="small d-block text-secondary">Room Assigned</span>
                            <strong class="text-dark">Room 301</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="small d-block text-secondary">Schedule Time</span>
                            <strong class="text-dark">Friday, 1:00 - 3:00 PM</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded d-flex align-items-start gap-3 bg-danger-subtle border border-danger-subtle">
                    <i class="fas fa-exclamation-triangle mt-1 text-danger"></i>
                    <div class="small">
                        <strong class="text-danger">Conflict Detected:</strong>
                        <span class="text-secondary"> Room 301 is also booked for IT401 - Network Security (Prof. J. Aquino) at this exact timeframe.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-3 px-4 border-top">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-warning px-3 fw-semibold text-dark" onclick="resolveConflict()">
                    Resolve Conflict
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Checkbox Toggle Event
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
    });
});

// Helper Functions
function viewConflict() {
    const modalEl = document.getElementById('scheduleModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function resolveConflict() {
    alert('Redirecting to conflict resolution workflow...');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
