<?php
/**
 * Faculty Clearance Management (Department Head View)
 * Process Flow Alignment: Clearance System AS-IS
 * Theme Support: Fully compatible with Light & Dark Modes
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Faculty Clearance';
$activeModule = 'faculty';
$activePage   = 'faculty-clearance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">


<div class="container-fluid p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-file-signature text-primary me-2"></i>Faculty Clearance Management</h3>
            <p class="text-muted small mb-0">Track contract expirations, review submitted requirements, and issue official approvals.</p>
        </div>
        <button id="btnSendNotices" class="btn btn-primary btn-sm px-3 shadow-sm" onclick="sendClearanceNotices()">
            <i class="fas fa-paper-plane me-1"></i> Send Expiration & Clearance Notices
        </button>
    </div>

    <!-- Toast / Alert Feedback Container -->
    <div id="actionAlert" class="alert alert-success alert-dismissible fade show d-none mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i><span id="alertMessage">Action completed successfully.</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Overview Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-body-tertiary border shadow-sm p-3">
                <span class="text-muted small">Contracts Expiring Soon</span>
                <h4 class="fw-bold mb-0 text-warning" id="metricExpiring">5 Faculty</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-body-tertiary border shadow-sm p-3">
                <span class="text-muted small">Pending Verification</span>
                <h4 class="fw-bold mb-0 text-info" id="metricPending">3 Faculty</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-body-tertiary border shadow-sm p-3">
                <span class="text-muted small">Action Required (Incomplete)</span>
                <h4 class="fw-bold mb-0 text-danger" id="metricIncomplete">2 Faculty</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-body-tertiary border shadow-sm p-3">
                <span class="text-muted small">Approved & Archived</span>
                <h4 class="fw-bold mb-0 text-success" id="metricApproved">14 Cleared</h4>
            </div>
        </div>
    </div>

    <!-- Clearance Table -->
    <div class="card bg-body-tertiary border shadow-sm">
        <div class="card-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold">Faculty Clearance Tracking</h6>
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-body border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search faculty..." onkeyup="filterTable()">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="clearanceTable" style="font-size: 13px;">
                    <thead class="bg-body-tertiary border-bottom text-body-secondary text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="py-3 ps-3">Faculty Name</th>
                            <th class="py-3">Contract Expiry</th>
                            <th class="py-3">Requirement Progress</th>
                            <th class="py-3">Status</th>
                            <th class="text-end py-3 pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Scenario 1: Under Review (Ready for Dept Head Review) -->
                        <tr id="row-turing">
                            <td class="fw-bold faculty-name ps-3">Dr. Alan Turing</td>
                            <td>Oct 15, 2026</td>
                            <td>
                                <div class="progress bg-secondary-subtle mb-1" style="height: 6px; width: 130px;">
                                    <div class="progress-bar bg-info" id="bar-turing" style="width: 75%;"></div>
                                </div>
                                <small class="text-muted" id="text-turing">3 of 4 Submitted</small>
                            </td>
                            <td><span class="badge bg-warning text-dark" id="badge-turing">Under Review</span></td>
                            <td class="text-end pe-3 action-cell">
                                <button class="btn btn-sm btn-outline-info" onclick="openReviewModal('Dr. Alan Turing', 'turing', false)">
                                    <i class="fas fa-tasks me-1"></i> Check Requirements
                                </button>
                            </td>
                        </tr>

                        <!-- Scenario 2: Incomplete Requirements -->
                        <tr id="row-lovelace">
                            <td class="fw-bold faculty-name ps-3">Prof. Ada Lovelace</td>
                            <td>Oct 20, 2026</td>
                            <td>
                                <div class="progress bg-secondary-subtle mb-1" style="height: 6px; width: 130px;">
                                    <div class="progress-bar bg-danger" id="bar-lovelace" style="width: 50%;"></div>
                                </div>
                                <small class="text-muted" id="text-lovelace">2 of 4 Submitted</small>
                            </td>
                            <td><span class="badge bg-danger" id="badge-lovelace">Pending Resubmission</span></td>
                            <td class="text-end pe-3 action-cell">
                                <button class="btn btn-sm btn-outline-secondary" disabled id="btn-lovelace">
                                    <i class="fas fa-clock me-1"></i> Awaiting Faculty
                                </button>
                            </td>
                        </tr>

                        <!-- Scenario 3: Completed & Stored -->
                        <tr id="row-feynman">
                            <td class="fw-bold faculty-name ps-3">Dr. Richard Feynman</td>
                            <td>Sep 30, 2026</td>
                            <td>
                                <div class="progress bg-secondary-subtle mb-1" style="height: 6px; width: 130px;">
                                    <div class="progress-bar bg-success" id="bar-feynman" style="width: 100%;"></div>
                                </div>
                                <small class="text-muted" id="text-feynman">4 of 4 Submitted</small>
                            </td>
                            <td><span class="badge bg-success" id="badge-feynman">Approved & Archived</span></td>
                            <td class="text-end pe-3 action-cell">
                                <button class="btn btn-sm btn-outline-success" onclick="downloadClearance('Dr. Richard Feynman')">
                                    <i class="fas fa-file-download me-1"></i> Clearance Record
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FLOW MODAL: Check Faculty Requirements & Complete? (YES/NO) Decision -->
<div class="modal fade" id="checkRequirementsModal" tabindex="-1" aria-labelledby="checkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-body-tertiary border">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="checkModalLabel">
                    <i class="fas fa-clipboard-check text-primary me-2"></i>Review Requirements — <span id="modalFacultyName">Faculty Member</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Inspect submitted documents before issuing final approval.</p>
                <div class="list-group border rounded">
                    <!-- Item 1 -->
                    <div class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">1. Final Grades Submission</div>
                            <small class="text-muted">Submitted • File: <code class="text-primary">grades_q1.pdf</code></small>
                        </div>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Verified</span>
                    </div>
                    <!-- Item 2 -->
                    <div class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">2. Department Property Turnover</div>
                            <small class="text-muted">Submitted • File: <code class="text-primary">property_receipt.pdf</code></small>
                        </div>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Verified</span>
                    </div>
                    <!-- Item 3 -->
                    <div class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">3. Library Clearance Slip</div>
                            <small class="text-muted">Submitted • File: <code class="text-primary">lib_clearance.pdf</code></small>
                        </div>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Verified</span>
                    </div>
                    <!-- Item 4 (Interactive Requirement) -->
                    <div class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold" id="item4Title">4. LMS Module Turn-Over</div>
                            <small class="text-muted" id="item4Desc">Status: <span class="text-danger fw-bold">Missing File</span></small>
                        </div>
                        <span class="badge bg-danger" id="item4Badge">Pending</span>
                    </div>
                </div>
            </div>
            <!-- DECISION GATEWAY -->
            <div class="modal-footer border-top justify-content-between">
                <!-- DECISION: NO -> Request Faculty to Submit Pending Requirements -->
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="rejectRequirements()">
                    <i class="fas fa-times-circle me-1"></i> Incomplete (Notify Faculty to Resubmit)
                </button>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" data-bs-dismiss="modal">Close</button>
                    <!-- DECISION: YES -> Approve Clearance -->
                    <button type="button" class="btn btn-success btn-sm" onclick="approveClearance()">
                        <i class="fas fa-check-circle me-1"></i> Approve Clearance (Complete)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Interactivity -->
<script>
let currentActiveFacultyId = '';
let currentActiveFacultyName = '';

/**
 * 1. Send Clearance Notices Trigger
 */
function sendClearanceNotices() {
    const btn = document.getElementById('btnSendNotices');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';
    
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Expiration & Clearance Notices';
        showAlert('Clearance and expiration notices successfully broadcasted to all active faculty members!');
    }, 800);
}

/**
 * 2. Open Modal with Dynamic Data
 */
function openReviewModal(facultyName, facultyId, isComplete) {
    currentActiveFacultyId = facultyId;
    currentActiveFacultyName = facultyName;
    document.getElementById('modalFacultyName').textContent = facultyName;

    const item4Desc = document.getElementById('item4Desc');
    const item4Badge = document.getElementById('item4Badge');

    if (isComplete) {
        item4Desc.innerHTML = 'Submitted • File: <code class="text-primary">lms_archive_proof.pdf</code>';
        item4Badge.className = 'badge bg-success';
        item4Badge.innerHTML = '<i class="fas fa-check me-1"></i>Verified';
    } else {
        item4Desc.innerHTML = 'Status: <span class="text-danger fw-bold">Missing File</span>';
        item4Badge.className = 'badge bg-danger';
        item4Badge.textContent = 'Pending';
    }

    const modalEl = document.getElementById('checkRequirementsModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

/**
 * 3. Decision: NO (Incomplete / Reject)
 */
function rejectRequirements() {
    if (!currentActiveFacultyId) return;

    // Update Row UI
    document.getElementById(`badge-${currentActiveFacultyId}`).className = 'badge bg-danger';
    document.getElementById(`badge-${currentActiveFacultyId}`).textContent = 'Pending Resubmission';
    
    document.getElementById(`bar-${currentActiveFacultyId}`).className = 'progress-bar bg-danger';
    document.getElementById(`bar-${currentActiveFacultyId}`).style.width = '50%';
    document.getElementById(`text-${currentActiveFacultyId}`).textContent = '2 of 4 Submitted';

    const actionCell = document.querySelector(`#row-${currentActiveFacultyId} .action-cell`);
    actionCell.innerHTML = `
        <button class="btn btn-sm btn-outline-secondary" disabled>
            <i class="fas fa-clock me-1"></i> Awaiting Faculty
        </button>
    `;

    closeModal();
    showAlert(`Notification sent to ${currentActiveFacultyName} to resubmit pending requirements.`);
}

/**
 * 4. Decision: YES (Approve Clearance)
 */
function approveClearance() {
    if (!currentActiveFacultyId) return;

    // Update Row UI
    document.getElementById(`badge-${currentActiveFacultyId}`).className = 'badge bg-success';
    document.getElementById(`badge-${currentActiveFacultyId}`).textContent = 'Approved & Archived';

    document.getElementById(`bar-${currentActiveFacultyId}`).className = 'progress-bar bg-success';
    document.getElementById(`bar-${currentActiveFacultyId}`).style.width = '100%';
    document.getElementById(`text-${currentActiveFacultyId}`).textContent = '4 of 4 Submitted';

    const actionCell = document.querySelector(`#row-${currentActiveFacultyId} .action-cell`);
    actionCell.innerHTML = `
        <button class="btn btn-sm btn-outline-success" onclick="downloadClearance('${currentActiveFacultyName}')">
            <i class="fas fa-file-download me-1"></i> Clearance Record
        </button>
    `;

    closeModal();
    showAlert(`Clearance approved and archived successfully for ${currentActiveFacultyName}!`);
}

/**
 * 5. Download Certificate Action
 */
function downloadClearance(facultyName) {
    showAlert(`Downloading official Clearance Certificate PDF for ${facultyName}...`);
}

/**
 * Helper: Search Filter
 */
function filterTable() {
    const filter = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#clearanceTable tbody tr');

    rows.forEach(row => {
        const name = row.querySelector('.faculty-name').textContent.toLowerCase();
        row.style.display = name.includes(filter) ? '' : 'none';
    });
}

/**
 * Helper: Close Modal
 */
function closeModal() {
    const modalEl = document.getElementById('checkRequirementsModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

/**
 * Helper: Show Feedback Alert Banner
 */
function showAlert(message) {
    const alertBox = document.getElementById('actionAlert');
    document.getElementById('alertMessage').textContent = message;
    alertBox.classList.remove('d-none');
    
    setTimeout(() => {
        alertBox.classList.add('d-none');
    }, 4000);
}
</script>   

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>