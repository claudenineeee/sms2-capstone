<?php
/**
 * Leave Request
 * Purpose: Submit and manage leave requests
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Leave Request';
$activeModule = 'faculty';
$activePage   = 'leave-request';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Leave Request', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-plane-departure fs-5"></i>
            </span>
            Leave Management
        </h4>
        <p class="text-secondary small mb-0">Submit new leave applications and track your approval history</p>
    </div>
    <button class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#newLeaveModal">
        <i class="fas fa-plus fs-6"></i>
        <span>New Request</span>
    </button>
</div>

<!-- Summary Metrics Bar -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-5">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Total Balance</span>
                    <h4 class="fw-bold mb-0">10 <small class="text-muted fs-6">days available</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-5">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Pending</span>
                    <h4 class="fw-bold mb-0 text-warning">1 <small class="text-muted fs-6">awaiting review</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-5">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Approved</span>
                    <h4 class="fw-bold mb-0 text-primary">5 <small class="text-muted fs-6">requests YTD</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-3 fs-5">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Rejected</span>
                    <h4 class="fw-bold mb-0 text-secondary">0 <small class="text-muted fs-6">requests</small></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Category Breakdown (Visual Allocation Progress) -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-0 pt-3 px-4">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-chart-pie text-primary"></i>
            Leave Entitlement Breakdown
        </h6>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Vacation</span>
                        <span class="fw-bold text-primary">5 <small class="text-muted fw-normal">/ 10</small></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 50%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Sick Leave</span>
                        <span class="fw-bold text-success">3 <small class="text-muted fw-normal">/ 5</small></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 60%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Emergency</span>
                        <span class="fw-bold text-warning">2 <small class="text-muted fw-normal">/ 3</small></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 66%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Study Leave</span>
                        <span class="fw-bold text-secondary">0 <small class="text-muted fw-normal">/ 0</small></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Requests Data Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-history text-primary"></i>
            Application Records
        </h6>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted d-none d-sm-inline">Display:</span>
            <select class="form-select form-select-sm border-0 bg-light w-auto fw-medium">
                <option>10 rows</option>
                <option>25 rows</option>
                <option>50 rows</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom border-light-subtle">
                    <tr>
                        <th class="ps-4 text-uppercase small text-secondary fw-semibold">ID</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Type</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Duration</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Days</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Status</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Filed Date</th>
                        <th class="pe-4 text-end text-uppercase small text-secondary fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Row 1 (Pending) -->
                    <tr>
                        <td class="ps-4 fw-semibold text-primary">LEAVE-057</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2">Sick Leave</span></td>
                        <td class="small">
                            <i class="far fa-calendar text-muted me-1"></i>Aug 21, 2025 
                            <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> Aug 22, 2025
                        </td>
                        <td><span class="fw-medium">2</span> <span class="text-muted small">d</span></td>
                        <td><span class="badge border rounded-pill px-2.5 py-1.5 bg-warning bg-opacity-10 text-warning border-warning border-opacity-25">Pending</span></td>
                        <td class="small text-muted">Today</td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary border" title="View Details" onclick='viewDetails({"id":"LEAVE-057","type":"Sick Leave","start":"Aug 21, 2025","end":"Aug 22, 2025","days":2,"status":"Pending","date":"Today","reason":"Medical appointment for check-up","doc":"Medical_Certificate.pdf"})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-light text-danger border" title="Cancel Request" onclick="cancelRequest('LEAVE-057')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 2 (Approved) -->
                    <tr>
                        <td class="ps-4 fw-semibold text-primary">LEAVE-056</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2">Vacation Leave</span></td>
                        <td class="small">
                            <i class="far fa-calendar text-muted me-1"></i>Jul 15, 2025 
                            <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> Jul 19, 2025
                        </td>
                        <td><span class="fw-medium">5</span> <span class="text-muted small">d</span></td>
                        <td><span class="badge border rounded-pill px-2.5 py-1.5 bg-success bg-opacity-10 text-success border-success border-opacity-25">Approved</span></td>
                        <td class="small text-muted">Jul 10, 2025</td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary border" title="View Details" onclick='viewDetails({"id":"LEAVE-056","type":"Vacation Leave","start":"Jul 15, 2025","end":"Jul 19, 2025","days":5,"status":"Approved","date":"Jul 10, 2025","reason":"Annual family trip","doc":"None"})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 3 (Approved) -->
                    <tr>
                        <td class="ps-4 fw-semibold text-primary">LEAVE-055</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2">Sick Leave</span></td>
                        <td class="small">
                            <i class="far fa-calendar text-muted me-1"></i>Jun 20, 2025 
                            <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> Jun 20, 2025
                        </td>
                        <td><span class="fw-medium">1</span> <span class="text-muted small">d</span></td>
                        <td><span class="badge border rounded-pill px-2.5 py-1.5 bg-success bg-opacity-10 text-success border-success border-opacity-25">Approved</span></td>
                        <td class="small text-muted">Jun 19, 2025</td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary border" title="View Details" onclick='viewDetails({"id":"LEAVE-055","type":"Sick Leave","start":"Jun 20, 2025","end":"Jun 20, 2025","days":1,"status":"Approved","date":"Jun 19, 2025","reason":"Severe migraine","doc":"Prescription.pdf"})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 4 (Approved) -->
                    <tr>
                        <td class="ps-4 fw-semibold text-primary">LEAVE-054</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2">Emergency Leave</span></td>
                        <td class="small">
                            <i class="far fa-calendar text-muted me-1"></i>May 10, 2025 
                            <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> May 10, 2025
                        </td>
                        <td><span class="fw-medium">1</span> <span class="text-muted small">d</span></td>
                        <td><span class="badge border rounded-pill px-2.5 py-1.5 bg-success bg-opacity-10 text-success border-success border-opacity-25">Approved</span></td>
                        <td class="small text-muted">May 10, 2025</td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary border" title="View Details" onclick='viewDetails({"id":"LEAVE-054","type":"Emergency Leave","start":"May 10, 2025","end":"May 10, 2025","days":1,"status":"Approved","date":"May 10, 2025","reason":"Home plumbing emergency","doc":"None"})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 5 (Approved) -->
                    <tr>
                        <td class="ps-4 fw-semibold text-primary">LEAVE-053</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2">Vacation Leave</span></td>
                        <td class="small">
                            <i class="far fa-calendar text-muted me-1"></i>Apr 01, 2025 
                            <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> Apr 05, 2025
                        </td>
                        <td><span class="fw-medium">5</span> <span class="text-muted small">d</span></td>
                        <td><span class="badge border rounded-pill px-2.5 py-1.5 bg-success bg-opacity-10 text-success border-success border-opacity-25">Approved</span></td>
                        <td class="small text-muted">Mar 25, 2025</td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary border" title="View Details" onclick='viewDetails({"id":"LEAVE-053","type":"Vacation Leave","start":"Apr 01, 2025","end":"Apr 05, 2025","days":5,"status":"Approved","date":"Mar 25, 2025","reason":"Personal rest","doc":"None"})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 py-3 px-4 d-flex justify-content-between align-items-center">
        <span class="small text-muted">Showing 1 to 5 of 5 entries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link border-0" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link rounded-circle mx-1" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link border-0" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal: New Leave Request -->
<div class="modal fade" id="newLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature text-primary"></i>
                    Apply for Leave
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="leaveRequestForm">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Leave Type <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" required>
                            <option value="" disabled selected>Select category...</option>
                            <option>Vacation Leave</option>
                            <option>Sick Leave</option>
                            <option>Emergency Leave</option>
                            <option>Study Leave</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-light" rows="3" placeholder="Provide details regarding your request..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Supporting Documents</label>
                        <input type="file" class="form-control bg-light">
                        <div class="form-text fs-7">Attach medical certificates or official notes if required.</div>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 rounded-3 d-flex align-items-center gap-2 text-primary small">
                        <i class="fas fa-info-circle fs-6"></i>
                        <span>You currently have <strong>10 available days</strong> in total balance.</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Discard</button>
                <button type="button" class="btn btn-primary rounded-pill px-4">Submit Application</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-primary"></i>
                    Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted small d-block">Reference ID</span>
                            <span class="fw-bold text-dark" id="modal-req-id">-</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Status</span>
                            <span id="modal-status-badge">-</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Leave Category</span>
                        <span class="fw-semibold" id="modal-leave-type">-</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Duration</span>
                        <span class="fw-semibold"><span id="modal-days">-</span> Days</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Start Date</span>
                        <span class="fw-semibold" id="modal-start-date">-</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">End Date</span>
                        <span class="fw-semibold" id="modal-end-date">-</span>
                    </div>
                </div>

                <hr class="my-3 text-muted opacity-25">

                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Reason Provided</span>
                    <p class="mb-0 bg-light p-3 rounded-3 text-dark small" id="modal-reason">-</p>
                </div>

                <div>
                    <span class="text-muted small d-block mb-1">Attached Document</span>
                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 text-truncate" id="modal-doc-container">
                        <i class="fas fa-file-pdf text-danger fs-5"></i>
                        <span class="small fw-medium text-dark text-truncate" id="modal-doc-name">No attachment</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0" id="modal-footer-actions">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(data) {
    document.getElementById('modal-req-id').textContent = data.id;
    document.getElementById('modal-leave-type').textContent = data.type;
    document.getElementById('modal-days').textContent = data.days;
    document.getElementById('modal-start-date').textContent = data.start;
    document.getElementById('modal-end-date').textContent = data.end;
    document.getElementById('modal-reason').textContent = data.reason || 'N/A';
    document.getElementById('modal-doc-name').textContent = data.doc || 'No document attached';

    // Badge Render
    let badgeClass = 'bg-secondary';
    if(data.status === 'Pending') badgeClass = 'bg-warning text-dark';
    if(data.status === 'Approved') badgeClass = 'bg-success';
    if(data.status === 'Rejected') badgeClass = 'bg-danger';

    document.getElementById('modal-status-badge').innerHTML = 
        `<span class="badge ${badgeClass} rounded-pill px-2.5 py-1">${data.status}</span>`;

    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

function cancelRequest(id) {
    if(confirm(`Are you sure you want to cancel request ${id}?`)) {
        alert(`Request ${id} has been successfully cancelled.`);
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
