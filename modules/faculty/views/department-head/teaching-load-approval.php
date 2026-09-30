<?php
/**
 * Teaching Load Approval
 * Purpose: Review and approve/reject proposed teaching loads
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Teaching Load Approval';
$activeModule = 'faculty';
$activePage   = 'teaching-load-approval';
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
        <h1 class="h3 fw-bold mb-1 text-white">
            <i class="fas fa-inbox text-purple me-2"></i>Teaching Load Approval
        </h1>
        <p class="text-muted mb-0 small">Review and approve/reject proposed teaching loads</p>
    </div>
    
    <!-- Responsive Button Group -->
    <div class="d-flex flex-wrap gap-1.5 gap-sm-2">
        <button class="btn btn-sm btn-success py-1.5 px-2.5 px-sm-3 d-inline-flex align-items-center" onclick="approveSelected()">
            <i class="fas fa-check me-1"></i>
            <span>Approve<span class="d-none d-sm-inline"> Selected</span></span>
        </button>
        <button class="btn btn-sm btn-danger py-1.5 px-2.5 px-sm-3 d-inline-flex align-items-center" onclick="rejectSelected()">
            <i class="fas fa-times me-1"></i>
            <span>Reject<span class="d-none d-sm-inline"> Selected</span></span>
        </button>
        <button class="btn btn-sm btn-outline-success py-1.5 px-2.5 px-sm-3 d-inline-flex align-items-center">
            <i class="fas fa-file-excel me-1"></i>
            <span>Export</span>
        </button>
    </div>
</div>

<!-- Summary Metrics Bar -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-warning fs-4"><i class="fas fa-clock"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Approvals</h6>
                    <h4 class="mb-0 fw-bold">
                        <?php 
                        // TODO: Fetch pending approvals count from database
                        // $pendingCount = $pdo->query("SELECT COUNT(*) FROM teaching_loads WHERE status = 'pending'")->fetchColumn();
                        // echo htmlspecialchars($pendingCount);
                        echo '0'; 
                        ?>
                    </h4>
                    <small class="text-warning fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i>0 New</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-4">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #198754; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-check-circle"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Approved Today</h6>
                    <h4 class="mb-0 fw-bold">
                        <?php 
                        // TODO: Fetch approved today count from database
                        // $approvedToday = $pdo->query("SELECT COUNT(*) FROM teaching_loads WHERE status = 'approved' AND DATE(updated_at) = CURDATE()")->fetchColumn();
                        // echo htmlspecialchars($approvedToday);
                        echo '0'; 
                        ?>
                    </h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-up me-1"></i>0%</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-4">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-danger fs-4"><i class="fas fa-times-circle"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rejected Today</h6>
                    <h4 class="mb-0 fw-bold">
                        <?php 
                        // TODO: Fetch rejected today count from database
                        // $rejectedToday = $pdo->query("SELECT COUNT(*) FROM teaching_loads WHERE status = 'rejected' AND DATE(updated_at) = CURDATE()")->fetchColumn();
                        // echo htmlspecialchars($rejectedToday);
                        echo '0'; 
                        ?>
                    </h4>
                    <small class="text-secondary fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-minus me-1"></i>0 Action Required</small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form class="row g-2 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small fw-semibold text-body-secondary">Faculty Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body-tertiary border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="Search by name or ID...">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-body-secondary">Subject Code</label>
                <input type="text" class="form-control form-control-sm" placeholder="e.g. CS101">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-body-secondary">Status</label>
                <select class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option selected value="pending">Pending Only</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-body-secondary">Semester</label>
                <select class="form-select form-select-sm">
                    <option>2nd Sem 2025-2026</option>
                    <option>1st Sem 2025-2026</option>
                </select>
            </div>

            <div class="col-12 col-md-2 col-lg-3 d-flex gap-2 ms-auto">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 shadow-sm">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Main Proposals Table Card -->
<div class="card border-0 shadow-sm mb-4">
    <!-- Header & Batch Actions -->
    <div class="card-header bg-body-tertiary py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0 fw-bold text-body">
                <i class="fas fa-list-check text-primary me-2"></i>Pending Proposals (0)
            </h6>
            <!-- Batch Action Bar (Appears when checkboxes selected) -->
            <div class="d-none d-sm-flex align-items-center gap-2 border-start ps-3" id="batchActionBar">
                <button class="btn btn-xs btn-success shadow-sm" disabled id="btnBatchApprove">
                    <i class="fas fa-check me-1"></i> Approve Selected
                </button>
                <button class="btn btn-xs btn-outline-danger" disabled id="btnBatchReject">
                    <i class="fas fa-times me-1"></i> Reject
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm w-auto shadow-none">
                <option>10 per page</option>
                <option>25 per page</option>
                <option>50 per page</option>
            </select>
        </div>
    </div>

    <!-- Table Body -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th>Faculty Member</th>
                        <th>Proposed Load</th>
                        <th>Total Units</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    /* 
                    TODO: Replace this section with dynamic database loop for integration
                    Example:
                    $stmt = $pdo->query("SELECT * FROM teaching_load_proposals WHERE status = 'pending'");
                    while ($row = $stmt->fetch()) {
                        // Render table rows dynamically here
                    }
                    if ($stmt->rowCount() === 0) {
                    */
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No pending teaching load proposals found.
                        </td>
                    </tr>
                    <?php 
                    // } 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Table Footer / Pagination -->
    <div class="card-footer bg-body-tertiary py-2.5 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <small class="text-body-secondary">Showing 0 to 0 of 0 pending proposals</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal 1: Proposal Review Details -->
<div class="modal fade" id="proposalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="p-2 bg-primary-subtle text-primary rounded me-2">
                        <i class="fas fa-file-alt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0">Teaching Load Proposal</h5>
                        <span class="small text-body-secondary">Submitted proposal details</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Quick Info Header Box -->
                <div class="p-3 bg-body-tertiary rounded border mb-4">
                    <div class="row g-3 text-center text-sm-start">
                        <div class="col-6 col-sm-3 border-end-sm">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small" style="font-size:0.68rem;">Faculty</span>
                            <strong class="text-body"><!-- Dynamically populated via JS/PHP -->--</strong>
                        </div>
                        <div class="col-6 col-sm-3 border-end-sm">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small" style="font-size:0.68rem;">Submission Date</span>
                            <strong class="text-body">--</strong>
                        </div>
                        <div class="col-6 col-sm-3 border-end-sm">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small" style="font-size:0.68rem;">Proposed Units</span>
                            <strong class="text-primary">-- Units</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small" style="font-size:0.68rem;">Previous Load</span>
                            <strong class="text-body">-- Units</strong>
                        </div>
                    </div>
                </div>

                <!-- Proposed Subjects Table -->
                <h6 class="fw-bold text-body mb-2">Subject Assignments</h6>
                <div class="table-responsive border rounded mb-4">
                    <table class="table table-sm align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Code</th>
                                <th>Subject Title</th>
                                <th class="text-center">Section</th>
                                <th class="text-center">Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- TODO: Populate subject assignments dynamically via database query or AJAX injection -->
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No subjects loaded for review.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Notes -->
                <div class="mb-0">
                    <label class="form-label fw-semibold text-body-secondary small">Approval / Rejection Comments</label>
                    <textarea class="form-control" rows="3" placeholder="Add optional comments or instructions for the faculty member..."></textarea>
                </div>
            </div>

            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fas fa-times me-1"></i> Reject
                </button>
                <button type="button" class="btn btn-success shadow-sm">
                    <i class="fas fa-check me-1"></i> Approve Load
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Dedicated Reject Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Reject Load Proposal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-body-secondary small mb-3">Please provide a reason for rejecting this proposal. The faculty member will receive a notification to adjust their submission.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body small">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="4" placeholder="Specify reasons (e.g. Unit overload, schedule overlap, unassigned prerequisite...)" required></textarea>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<script>
// CHART
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('loadDistChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['< 18 Units', '18 - 20 Units', '21 - 24 Units', '> 24 Units'],
            datasets: [{
                label: 'Faculty Count',
                data: [0, 0, 0, 0], // TODO: Populate dynamically with real integration data
                backgroundColor: [
                    '#ff9800',  
                    '#00d084',  
                    '#00d084',  
                    '#ff5263'   
                ],
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 28
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1b2745',
                    titleColor: '#ffffff',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#1b2745' },
                    ticks: { color: '#94a3b8', stepSize: 2 }
                }
            }
        }
    });
});

function viewDetails(faculty) {
    const modal = new bootstrap.Modal(document.getElementById('proposalModal'));
    modal.show();
}
function approveProposal(faculty) {
    if(confirm('Approve teaching load for ' + faculty + '?')) {
        alert('Approved!');
    }
}
function rejectProposal(faculty) {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
function approveSelected() {
    const selected = document.querySelectorAll('.row-select:checked').length;
    if(selected === 0) {
        alert('Please select proposals to approve.');
        return;
    }
    if(confirm('Approve ' + selected + ' selected proposals?')) {
        alert('Approved!');
    }
}
function rejectSelected() {
    const selected = document.querySelectorAll('.row-select:checked').length;
    if(selected === 0) {
        alert('Please select proposals to reject.');
        return;
    }
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>