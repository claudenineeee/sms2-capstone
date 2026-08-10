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
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-calendar-check text-purple me-2"></i>Schedule Approval</h1>
        <p class="text-muted mb-0">Review and approve generated class schedules</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success" onclick="approveAll()"><i class="fas fa-check-double me-1"></i>Approve All (No Conflicts)</button>
        <button class="btn btn-danger" onclick="rejectSelected()"><i class="fas fa-times me-1"></i>Reject Selected</button>
        <button class="btn btn-outline-primary" onclick="requestModification()"><i class="fas fa-edit me-1"></i>Request Modification</button>
        <button class="btn btn-outline-success"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<!-- Top Fixed/Sticky Notification Banner for Conflicts -->
<div class="alert fade show p-0 mb-4 border-0 shadow-lg" role="alert" style="background-color: #0b1329; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 82, 99, 0.5) !important;">
    <div class="d-flex align-items-stretch">
        <!-- Notification Visual Strip -->
        <div class="d-flex align-items-center justify-content-center px-3 px-md-4" style="background: rgba(255, 82, 99, 0.2); border-right: 1px solid rgba(255, 82, 99, 0.3);">
            <div class="position-relative d-flex align-items-center justify-content-center">
                <i class="fas fa-bell fs-4" style="color: #ff5263;"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="animation: pulse 1.5s infinite;">
                    <span class="visually-hidden">New alert</span>
                </span>
            </div>
        </div>

        <!-- Notification Content -->
        <div class="p-3 p-md-3 flex-grow-1 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill text-uppercase px-2 py-0.5" style="background-color: rgba(255, 82, 99, 0.25); color: #ff5263; font-size: 0.7rem; letter-spacing: 0.5px; border: 1px solid rgba(255, 82, 99, 0.4);">
                        Action Required
                    </span>
                    <span class="small text-muted" style="color: #94a3b8 !important;">Just now</span>
                </div>
                <p class="mb-0 text-white small fw-medium">
                    <strong style="color: #ff5263;">Conflict Detected in Room 301:</strong> Double booking on <span class="text-white">Friday, 1:00 PM - 3:00 PM</span> (CS301 & IT401).
                </p>
            </div>

            <!-- Notification Actions -->
            <div class="d-flex align-items-center gap-2 align-self-end align-self-md-center">
                <button type="button" class="btn btn-sm text-white fw-semibold px-3 py-1.5 border-0" onclick="viewConflict()" style="background-color: rgba(255, 82, 99, 0.2); border: 1px solid rgba(255, 82, 99, 0.4) !important;">
                    <i class="fas fa-eye me-1.5"></i>Review
                </button>
                <button type="button" class="btn btn-sm fw-semibold px-3 py-1.5 border-0" onclick="resolveConflict()" style="background-color: #ff5263; color: #ffffff;">
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
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(47, 120, 255, 0.15); color: #2f78ff;">
                        <i class="fas fa-book fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Total Subjects</h6>
                        <h3 class="mb-0 fw-bold text-white">42</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(47, 120, 255, 0.15); color: #2f78ff; border: 1px solid rgba(47, 120, 255, 0.3); font-size: 0.7rem;">Active</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Rooms Used -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(0, 208, 132, 0.15); color: #00d084;">
                        <i class="fas fa-door-open fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Rooms Used</h6>
                        <h3 class="mb-0 fw-bold text-white">12</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3); font-size: 0.7rem;">Available</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Conflicts Detected -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid rgba(255, 82, 99, 0.4) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(255, 82, 99, 0.15); color: #ff5263;">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Conflicts Detected</h6>
                        <h3 class="mb-0 fw-bold text-white">1</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(255, 82, 99, 0.2); color: #ff5263; border: 1px solid rgba(255, 82, 99, 0.4); font-size: 0.7rem;">Critical</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Faculty Assigned -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(0, 200, 255, 0.15); color: #00c8ff;">
                        <i class="fas fa-users fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Faculty Assigned</h6>
                        <h3 class="mb-0 fw-bold text-white">18</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(0, 200, 255, 0.15); color: #00c8ff; border: 1px solid rgba(0, 200, 255, 0.3); font-size: 0.7rem;">Assigned</span>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters Toolbar -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0b1329; border: 1px solid #1b2745; border-radius: 12px;">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold" style="color: #94a3b8;">Subject Code</label>
                <input type="text" class="form-control form-control-sm text-white" placeholder="e.g. CS101" style="background-color: rgba(255,255,255,0.03); border-color: #1b2745;">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold" style="color: #94a3b8;">Instructor</label>
                <input type="text" class="form-control form-control-sm text-white" placeholder="Name..." style="background-color: rgba(255,255,255,0.03); border-color: #1b2745;">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold" style="color: #94a3b8;">Room</label>
                <input type="text" class="form-control form-control-sm text-white" placeholder="e.g. 301" style="background-color: rgba(255,255,255,0.03); border-color: #1b2745;">
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold" style="color: #94a3b8;">Conflict Status</label>
                <select class="form-select form-select-sm text-white" style="background-color: rgba(255,255,255,0.03); border-color: #1b2745;">
                    <option value="" style="background: #0b1329;">All</option>
                    <option style="background: #0b1329;">No Conflict</option>
                    <option selected style="background: #0b1329;">Conflict Detected</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold" style="color: #94a3b8;">Day</label>
                <select class="form-select form-select-sm text-white" style="background-color: rgba(255,255,255,0.03); border-color: #1b2745;">
                    <option value="" style="background: #0b1329;">All</option>
                    <option style="background: #0b1329;">Monday</option>
                    <option style="background: #0b1329;">Tuesday</option>
                    <option style="background: #0b1329;">Wednesday</option>
                    <option style="background: #0b1329;">Thursday</option>
                    <option style="background: #0b1329;">Friday</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-sm flex-fill fw-semibold py-2 border-0 text-white" style="background-color: #2f78ff;">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <button class="btn btn-sm flex-fill fw-semibold py-2" style="border: 1px solid #1b2745; color: #94a3b8; background-color: rgba(255,255,255,0.02);">
                        <i class="fas fa-redo me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Approval Data Table -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0b1329; border: 1px solid #1b2745; border-radius: 12px; overflow: hidden;">
    <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background-color: rgba(255, 255, 255, 0.02); border-bottom: 1px solid #1b2745;">
        <h6 class="mb-0 fw-bold text-white">
            <i class="fas fa-list me-2" style="color: #2f78ff;"></i>Schedule Pending Approval <span class="small fw-normal text-muted" style="color: #94a3b8 !important;">(42 entries)</span>
        </h6>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm text-white" style="background-color: rgba(255,255,255,0.03); border-color: #1b2745; width: auto;">
                <option style="background: #0b1329;">10 per page</option>
                <option style="background: #0b1329;">25 per page</option>
                <option style="background: #0b1329;">50 per page</option>
            </select>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; --bs-table-border-color: #1b2745;">
                <thead style="background-color: rgba(255,255,255,0.03); border-bottom: 1px solid #1b2745;">
                    <tr class="text-uppercase small" style="color: #94a3b8; font-size: 0.75rem; letter-spacing: 0.5px;">
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
                <tbody class="text-white small">
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-white">CS101</td>
                        <td>Intro to CS</td>
                        <td style="color: #94a3b8;">Dr. M. Santos</td>
                        <td>201</td>
                        <td>MWF</td>
                        <td>8:00-9:30</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3);">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #2f78ff; background: rgba(47, 120, 255, 0.1);" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #ff9800; background: rgba(255, 152, 0, 0.1);" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-white">CS201</td>
                        <td>Data Structures</td>
                        <td style="color: #94a3b8;">Prof. L. Tan</td>
                        <td>202</td>
                        <td>TTH</td>
                        <td>10:00-11:30</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3);">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #2f78ff; background: rgba(47, 120, 255, 0.1);" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #ff9800; background: rgba(255, 152, 0, 0.1);" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr style="background-color: rgba(255, 82, 99, 0.08);">
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold" style="color: #ff5263;">CS301</td>
                        <td>Algorithms</td>
                        <td style="color: #94a3b8;">Prof. K. Lim</td>
                        <td>301</td>
                        <td>F</td>
                        <td>1:00-3:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(255, 82, 99, 0.2); color: #ff5263; border: 1px solid rgba(255, 82, 99, 0.3);">Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #2f78ff; background: rgba(47, 120, 255, 0.1);" title="View Details" onclick="viewConflict()"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #ff9800; background: rgba(255, 152, 0, 0.1);" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr style="background-color: rgba(255, 82, 99, 0.08);">
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold" style="color: #ff5263;">IT401</td>
                        <td>Network Security</td>
                        <td style="color: #94a3b8;">Prof. J. Aquino</td>
                        <td>301</td>
                        <td>F</td>
                        <td>1:00-3:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(255, 82, 99, 0.2); color: #ff5263; border: 1px solid rgba(255, 82, 99, 0.3);">Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #2f78ff; background: rgba(47, 120, 255, 0.1);" title="View Details" onclick="viewConflict()"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #ff9800; background: rgba(255, 152, 0, 0.1);" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-select"></td>
                        <td class="fw-semibold text-white">CS401</td>
                        <td>Software Eng</td>
                        <td style="color: #94a3b8;">Dr. A. Reyes</td>
                        <td>203</td>
                        <td>MWF</td>
                        <td>9:30-11:00</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3);">No Conflict</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #2f78ff; background: rgba(47, 120, 255, 0.1);" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm" style="border: 1px solid #1b2745; color: #ff9800; background: rgba(255, 152, 0, 0.1);" title="Modify"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background-color: rgba(255, 255, 255, 0.02); border-top: 1px solid #1b2745;">
        <span class="small" style="color: #94a3b8;">Showing 1-5 of 42 entries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#" style="background: #0b1329; border-color: #1b2745; color: #94a3b8;">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#" style="background: #2f78ff; border-color: #2f78ff; color: #ffffff;">1</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background: #0b1329; border-color: #1b2745; color: #94a3b8;">2</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background: #0b1329; border-color: #1b2745; color: #94a3b8;">3</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background: #0b1329; border-color: #1b2745; color: #94a3b8;">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-white" style="background-color: #0b1329; border: 1px solid #1b2745; border-radius: 12px;">
            <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #1b2745;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-calendar me-2" style="color: #2f78ff;"></i>Schedule Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid #1b2745;">
                            <span class="small d-block text-muted" style="color: #94a3b8 !important;">Subject</span>
                            <strong class="text-white">CS301 - Algorithms</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid #1b2745;">
                            <span class="small d-block text-muted" style="color: #94a3b8 !important;">Instructor</span>
                            <strong class="text-white">Prof. Katherine Lim</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid #1b2745;">
                            <span class="small d-block text-muted" style="color: #94a3b8 !important;">Room Assigned</span>
                            <strong class="text-white">Room 301</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid #1b2745;">
                            <span class="small d-block text-muted" style="color: #94a3b8 !important;">Schedule Time</span>
                            <strong class="text-white">Friday, 1:00 - 3:00 PM</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded d-flex align-items-start gap-3" style="background-color: rgba(255, 82, 99, 0.1); border: 1px solid rgba(255, 82, 99, 0.3);">
                    <i class="fas fa-exclamation-triangle mt-1" style="color: #ff5263;"></i>
                    <div class="small">
                        <strong style="color: #ff5263;">Conflict Detected:</strong>
                        <span style="color: #94a3b8;"> Room 301 is also booked for IT401 - Network Security (Prof. J. Aquino) at this exact timeframe.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-3 px-4" style="border-top: 1px solid #1b2745;">
                <button type="button" class="btn btn-sm px-3" data-bs-dismiss="modal" style="border: 1px solid #1b2745; color: #94a3b8;">Close</button>
                <button type="button" class="btn btn-sm px-3 border-0 fw-semibold" onclick="resolveConflict()" style="background-color: #ff9800; color: #0b1329;">
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
