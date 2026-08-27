<?php
/** Department Head clearance tracking and review. */
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
if (!in_array(getCurrentUserRoleKey(), ['department_head', 'dept_head', 'hr', 'faculty_admin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}
require_once __DIR__ . '/../../controllers/clearance.php';
$db = facultyDb();
$profile = $db ? facultyClearanceProfile($db, (int) getCurrentUserId()) : null;
function deptClearanceEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
$pageTitle = 'Faculty Clearance';
$activeModule = 'faculty';
$activePage = 'faculty-clearance';
$breadcrumbs = [['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'], ['label' => 'Faculty Clearance', 'url' => null]];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<?php renderBreadcrumbs($breadcrumbs); ?>
<div class="container-fluid p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-primary text-uppercase small fw-bold mb-1">Department Head Account</p>
            <h3 class="fw-bold text-body-emphasis mb-1"><i class="fas fa-clipboard-check text-primary me-2"></i>Faculty
                Clearance Portal</h3>
            <p class="text-body-secondary small mb-0">Track ongoing clearance submissions, review requirements, update
                employment status (Probationary / Regular), and inspect archived completed records.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary btn-sm btn-md-normal w-100 w-sm-auto"
                onclick="refreshCurrentTab()"><i class="fas fa-sync-alt me-2"></i>Refresh</button>
        </div>
    </div>

    <div id="trackingAlert" class="alert d-none" role="status"></div>

    <!-- Metric Overview Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Pending Verification -->
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card primary border shadow-sm position-relative h-100 role-button"
                onclick="switchToActiveTab('pending')">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-info fs-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Verification</h6>
                        <h4 class="mb-0 fw-bold" id="metricPending">0</h4>
                        <small class="text-info fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-tasks me-1"></i>Awaiting Review
                        </small>
                    </div>
                </div>
                <a href="javascript:void(0)" onclick="switchToActiveTab('pending')"
                    class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                    style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Pending">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Card 2: Denied / Action Required -->
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card border shadow-sm position-relative h-100 role-button"
                style="border-left: 4px solid #dc3545 !important;" onclick="switchToActiveTab('action')">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-danger fs-4">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Denied / Action Required</h6>
                        <h4 class="mb-0 fw-bold" id="metricAction">0</h4>
                        <small class="text-danger fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-rotate-left me-1"></i>Needs Resubmission
                        </small>
                    </div>
                </div>
                <a href="javascript:void(0)" onclick="switchToActiveTab('action')"
                    class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                    style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Action Required">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>

        <!-- Card 3: Approved & Archived -->
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card success border shadow-sm position-relative h-100 role-button"
                onclick="switchToArchiveTab()">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Approved &amp; Archived</h6>
                        <h4 class="mb-0 fw-bold" id="metricArchived">0</h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-check-circle me-1"></i>Completed Clearances
                        </small>
                    </div>
                </div>
                <a href="javascript:void(0)" onclick="switchToArchiveTab()"
                    class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                    style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Archive">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <ul class="nav nav-pills mb-4 p-1 bg-body-tertiary border rounded-3 d-flex flex-column flex-sm-row gap-1"
        id="clearanceTabs" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active rounded-2 w-100 py-2 text-center" id="tab-active-btn" data-bs-toggle="pill"
                data-bs-target="#tab-active" type="button" role="tab" aria-selected="true">
                <i class="fas fa-tasks me-2"></i><span class="d-inline-block">Active Clearance Tracking</span> <span
                    class="badge bg-primary ms-1" id="activeBadgeCount">0</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link rounded-2 w-100 py-2 text-center" id="tab-archive-btn" data-bs-toggle="pill"
                data-bs-target="#tab-archive" type="button" role="tab" aria-selected="false" onclick="loadArchives()">
                <i class="fas fa-archive me-2"></i><span class="d-inline-block">Archived Completed Records</span> <span
                    class="badge bg-success ms-1" id="archiveBadgeCount">0</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="clearanceTabsContent">
        <!-- TAB 1: ACTIVE TRACKING -->
        <div class="tab-pane fade show active" id="tab-active" role="tabpanel" aria-labelledby="tab-active-btn">
            <div class="card border shadow-sm">
                <div
                    class="card-header bg-body-tertiary border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3">
                    <h6 class="fw-bold mb-0 text-body-emphasis"><i class="fas fa-list-ul me-2 text-primary"></i>Ongoing
                        Faculty Clearance Records</h6>
                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                        <select id="trackingEmpStatusFilter" class="form-select form-select-sm"
                            onchange="filterTracking()">
                            <option value="all">All Employment Types</option>
                            <option value="Probationary">Probationary Only</option>
                            <option value="Regular">Regular Only</option>
                            <option value="Part-Time">Part-Time Only</option>
                        </select>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-secondary text-body-secondary"><i
                                    class="fas fa-search"></i></span>
                            <input id="trackingSearch" class="form-control" placeholder="Search faculty or ID"
                                oninput="filterTracking()">
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-body-tertiary border-bottom" id="statusControlsContainer">
                    <!-- Status buttons rendered here -->
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="trackingTable">
                        <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary">
                            <tr>
                                <th class="ps-3">Faculty</th>
                                <th>Department</th>
                                <th>Contract Expiration</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="trackingBody" class="text-body">
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-5">Loading clearance
                                    records...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="trackingPagination"></div>
            </div>
        </div>

        <!-- TAB 2: ARCHIVED RECORDS -->
        <div class="tab-pane fade" id="tab-archive" role="tabpanel" aria-labelledby="tab-archive-btn">
            <div class="card border shadow-sm">
                <div class="card-header bg-body-tertiary border-bottom py-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-success"><i class="fas fa-archive me-2"></i>Archived Completed
                                Clearance History</h6>
                            <small class="text-body-secondary">Official record of all completed clearances, renewed
                                contracts, regularizations, and approved faculty documents.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success flex-grow-1 flex-sm-grow-0"
                                onclick="exportArchiveCsv()">
                                <i class="fas fa-file-excel me-1"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 flex-sm-grow-0"
                                onclick="printArchiveTable()">
                                <i class="fas fa-print me-1"></i> Print Records
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-body-tertiary border-bottom">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-secondary text-body-secondary"><i
                                        class="fas fa-search"></i></span>
                                <input id="archiveSearch" class="form-control"
                                    placeholder="Search by faculty name, ID, or department..."
                                    oninput="filterArchives()">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select id="archiveTermFilter" class="form-select form-select-sm"
                                onchange="filterArchives()">
                                <option value="all">All Academic Terms</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select id="archiveIntentFilter" class="form-select form-select-sm"
                                onchange="filterArchives()">
                                <option value="all">All Intent Types</option>
                                <option value="renewal">Contract Renewal / Extension</option>
                                <option value="regularization">Regularization</option>
                                <option value="resignation">Proceed with Clearance</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <select id="archiveEmpFilter" class="form-select form-select-sm"
                                onchange="filterArchives()">
                                <option value="all">All Statuses</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Regular">Regular</option>
                                <option value="Part-Time">Part-Time</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="archiveTable">
                        <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary">
                            <tr>
                                <th class="ps-3">Faculty</th>
                                <th>Academic Term</th>
                                <th>Statement of Intent</th>
                                <th>Contract Expiry</th>
                                <th>Requirements Summary</th>
                                <th>Date Cleared</th>
                                <th class="text-end pe-3">Record Details</th>
                            </tr>
                        </thead>
                        <tbody id="archiveBody" class="text-body">
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-5">Loading archived clearance
                                    records...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="archivePagination"></div>
            </div>
        </div>
    </div>
</div>

<!-- REMARK MODAL (replaces browser prompt) -->
<style>
    /* Ensure remark modal stacks above the review modal */
    #remarkModal {
        z-index: 1075 !important;
    }

    #remarkModal+.modal-backdrop,
    .modal-backdrop.remark-backdrop {
        z-index: 1070 !important;
    }
</style>
<div class="modal fade" id="remarkModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false" style="z-index:1075">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 border-bottom" id="remarkModalHeader">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" id="remarkModalIcon"
                        style="width:36px;height:36px;font-size:1rem;"></div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="remarkModalTitle">Add Remark</h6>
                        <small class="text-body-secondary" id="remarkModalSub"></small>
                    </div>
                </div>
            </div>
            <div class="modal-body px-4 py-3">
                <label for="remarkModalInput" class="form-label small fw-semibold text-body-emphasis mb-2"
                    id="remarkModalLabel">Remark</label>
                <textarea id="remarkModalInput" class="form-control" rows="3"
                    placeholder="Enter remark here..."></textarea>
                <div class="invalid-feedback" id="remarkModalError">A remark is required for this action.</div>
                <div class="mt-2">
                    <small class="text-body-secondary" id="remarkModalHint"></small>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top gap-2 flex-nowrap">
                <button type="button" class="btn btn-outline-secondary flex-fill" id="remarkModalCancel">Cancel</button>
                <button type="button" class="btn flex-fill fw-semibold" id="remarkModalConfirm">
                    <i class="fas fa-check me-1" id="remarkModalConfirmIcon"></i>
                    <span id="remarkModalConfirmText">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- REVIEW MODAL FOR ACTIVE CLEARANCE -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="reviewTitle"><i class="fas fa-clipboard-check me-2"></i>Review
                        Clearance</h5>
                    <small class="text-white-50" id="reviewMeta"></small>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div id="reviewAlert" class="alert d-none mb-3"></div>

                <!-- Faculty Contract & Status Summary Bar -->
                <div class="card bg-body-tertiary border mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 text-body">
                            <div class="col-12 col-sm-6 col-md-3 border-end-md border-body-subtle">
                                <small class="text-body-secondary d-block">Current Contract Expiry</small>
                                <span class="fw-bold fs-6 text-body-emphasis" id="summaryContractExpiry">—</span>
                                <small class="d-block" id="summaryDaysRemaining"></small>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3 border-end-md border-body-subtle">
                                <small class="text-body-secondary d-block mb-1">Employment Status</small>
                                <span
                                    class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"
                                    id="summaryEmpStatus">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3 border-end-md border-body-subtle">
                                <small class="text-body-secondary d-block mb-1">Statement of Intent</small>
                                <span
                                    class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"
                                    id="summaryIntentType">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Clearance Progress</small>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" id="summaryProgressBar" style="width: 0%">
                                        </div>
                                    </div>
                                    <span class="small fw-semibold text-body-emphasis"
                                        id="summaryProgressText">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase small text-body-secondary mb-3"><i
                        class="fas fa-list-check me-1 text-primary"></i> Submitted Clearance Requirements</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle border mb-0">
                        <thead class="table-light small text-uppercase text-body-secondary">
                            <tr>
                                <th>Requirement</th>
                                <th>File Attachment</th>
                                <th>Status</th>
                                <th>Remark</th>
                                <th class="text-end">Review Action</th>
                            </tr>
                        </thead>
                        <tbody id="reviewBody" class="text-body"></tbody>
                    </table>
                </div>

                <!-- Contract Renewal & Employment Status Management Section -->
                <div class="card border-success shadow-sm" id="renewalCard">
                    <div
                        class="card-header bg-success-subtle border-bottom border-success-subtle py-2 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <span class="fw-bold text-success small text-uppercase"><i
                                class="fas fa-user-edit me-2"></i>Contract Renewal &amp; Employment Status (Probationary
                            / Regular)</span>
                        <span class="badge bg-success text-white align-self-start align-self-sm-auto">Faculty Status
                            Management</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <p class="text-body-secondary small mb-4">Set whether the faculty member is
                            <strong>Probationary</strong> or <strong>Regular</strong>, and configure their contract
                            renewal date. Submitting immediately updates their account across the portal.
                        </p>

                        <form id="renewalForm" onsubmit="event.preventDefault(); submitContractRenewal();">
                            <div class="row g-3">
                                <!-- Field 1: Employment Status -->
                                <div class="col-12 col-md-6">
                                    <label for="employmentStatusSelect"
                                        class="form-label fw-semibold small text-body-emphasis mb-1">
                                        Employment Status <span class="text-danger">*</span>
                                    </label>
                                    <select id="employmentStatusSelect" class="form-select mb-2" required>
                                        <option value="Probationary">Probationary</option>
                                        <option value="Regular">Regular</option>
                                        <option value="Part-Time">Part-Time</option>
                                    </select>
                                    <div id="empStatusHint" class="small text-body-secondary">
                                        Select Probationary or Regular
                                    </div>
                                </div>

                                <!-- Field 2: New Contract End Date & Presets -->
                                <div class="col-12 col-md-6">
                                    <label for="newContractEndDate"
                                        class="form-label fw-semibold small text-body-emphasis mb-1">
                                        New Contract End Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="newContractEndDate" class="form-control mb-2" required>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <small class="text-body-secondary me-1 fw-medium">Presets:</small>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary py-1 px-2 small"
                                                onclick="applyRenewalPreset(6)">+6 Mo (1 Sem)</button>
                                            <button type="button" class="btn btn-outline-secondary py-1 px-2 small"
                                                onclick="applyRenewalPreset(12)">+1 Yr (1 AY)</button>
                                            <button type="button" class="btn btn-outline-secondary py-1 px-2 small"
                                                onclick="applyRenewalPreset(24)">+2 Yrs</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Field 3: Department Head Remarks -->
                                <div class="col-12">
                                    <label for="renewalRemarks"
                                        class="form-label fw-semibold small text-body-emphasis mb-1">
                                        Department Head Remarks / Note <small
                                            class="text-body-secondary">(Optional)</small>
                                    </label>
                                    <input type="text" id="renewalRemarks" class="form-control"
                                        placeholder="e.g., Status updated to Regular / Contract renewed following successful clearance completion.">
                                </div>
                            </div>

                            <!-- Action Button Bar -->
                            <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-success px-4 py-2 fw-semibold w-100 w-sm-auto"
                                    id="btnSubmitRenewal">
                                    <i class="fas fa-save me-2"></i>Save &amp; Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-body-tertiary border-top">
                <button type="button" class="btn btn-secondary px-4 w-100 w-sm-auto"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ARCHIVE DETAIL MODAL -->
<div class="modal fade" id="archiveDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="archiveModalTitle"><i class="fas fa-archive me-2"></i>Archived
                        Clearance Record</h5>
                    <small class="text-white-50" id="archiveModalMeta"></small>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="archivePrintArea">
                <!-- Info Header -->
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-body-tertiary border rounded-3 mb-4">
                    <div>
                        <span
                            class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold mb-1">
                            <i class="fas fa-check-circle me-1"></i> Status: Clearance Completed &amp; Cleared
                        </span>
                        <div class="small text-body-secondary mt-1" id="archiveModalTerm">Academic Term: —</div>
                    </div>
                    <div class="text-md-end">
                        <small class="text-body-secondary d-block">Completion Timestamp</small>
                        <strong class="text-body-emphasis" id="archiveModalCompletedAt">—</strong>
                    </div>
                </div>

                <!-- Faculty Profile Details -->
                <div class="card bg-body-tertiary border mb-4">
                    <div class="card-header bg-body-secondary py-2 border-bottom">
                        <h6 class="mb-0 fw-bold small text-uppercase text-body-emphasis"><i
                                class="fas fa-id-card me-2 text-primary"></i>Faculty Information</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 text-body">
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Faculty Member</small>
                                <strong class="text-body-emphasis" id="archiveFacultyName">—</strong>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Faculty ID No.</small>
                                <span id="archiveFacultyNo">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Department</small>
                                <span id="archiveDepartment">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Academic Rank</small>
                                <span id="archiveRank">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block mb-1">Statement of Intent</small>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                    id="archiveIntent">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Contract Expiration Date</small>
                                <strong class="text-success" id="archiveContractEnd">—</strong>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Employment Status</small>
                                <span id="archiveEmpStatus">—</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Contact Email</small>
                                <span id="archiveEmail" class="small">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clearance Requirements Table -->
                <h6 class="fw-bold text-uppercase small text-body-secondary mb-3"><i
                        class="fas fa-tasks me-1 text-success"></i> Approved Clearance Requirements</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-hover align-middle border mb-0">
                        <thead class="table-light small text-uppercase text-body-secondary">
                            <tr>
                                <th>#</th>
                                <th>Requirement</th>
                                <th>Submitted File Attachment</th>
                                <th>Status</th>
                                <th>Reviewer Note</th>
                                <th>Cleared Date</th>
                            </tr>
                        </thead>
                        <tbody id="archiveRequirementsBody" class="text-body"></tbody>
                    </table>
                </div>
            </div>
            <div
                class="modal-footer bg-body-tertiary border-top d-flex flex-column flex-sm-row justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto"
                    onclick="printSingleArchive()"><i class="fas fa-print me-1"></i> Print Summary</button>
                <button type="button" class="btn btn-secondary px-4 w-100 w-sm-auto"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';
    let trackingRows = [];
    let archiveRows = [];
    let reviewModal;
    let archiveDetailModal;
    let currentReviewFacultyId = null;
    let currentReviewProfile = null;
    let activeStatusGroup = 'all';
    let currentPage = 1;
    const trackingPageSize = 5;

    let archiveCurrentPage = 1;
    const archivePageSize = 8;

    async function loadTracking() {
        try {
            const response = await fetch(`${clearanceApi}?action=summary`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            trackingRows = data.rows || [];
            document.getElementById('metricPending').textContent = data.metrics.pending;
            document.getElementById('metricAction').textContent = data.metrics.action_required;
            document.getElementById('metricArchived').textContent = data.metrics.archived;
            document.getElementById('activeBadgeCount').textContent = trackingRows.length;
            currentPage = 1;
            renderStatusControls();
            renderTracking();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    async function loadArchives() {
        const body = document.getElementById('archiveBody');
        if (body) body.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary py-5"><i class="fas fa-spinner fa-spin me-2"></i>Loading archived clearance records...</td></tr>';

        try {
            const response = await fetch(`${clearanceApi}?action=archives`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            archiveRows = data.archives || [];
            document.getElementById('archiveBadgeCount').textContent = archiveRows.length;
            document.getElementById('metricArchived').textContent = archiveRows.length;

            // Populate term filter
            populateArchiveTermFilter();
            archiveCurrentPage = 1;
            renderArchives();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    function refreshCurrentTab() {
        loadTracking();
        loadArchives();
    }

    function switchToActiveTab(group) {
        const triggerEl = document.querySelector('#tab-active-btn');
        const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
        tab.show();
        if (group) selectStatusGroup(group);
    }

    function switchToArchiveTab() {
        const triggerEl = document.querySelector('#tab-archive-btn');
        const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
        tab.show();
        loadArchives();
    }

    function renderStatusControls() {
        let container = document.getElementById('statusControlsContainer');
        if (!container) return;
        const groups = [
            ['all', 'All Active', 'secondary'],
            ['pending', 'Pending Verification', 'info'],
            ['action', 'Denied / Resubmission', 'danger'],
            ['not-submitted', 'Not Submitted', 'secondary'],
        ];
        container.innerHTML = `<div class="d-flex flex-wrap gap-2">` + groups.map(([key, label, tone]) => {
            const count = key === 'all' ? trackingRows.length : trackingRows.filter(row => statusGroupFor(row) === key).length;
            return `<button type="button" class="btn btn-sm btn-${tone} ${activeStatusGroup === key ? '' : 'opacity-75'}" onclick="selectStatusGroup('${key}')">${label} <span class="badge text-bg-light ms-1">${count}</span></button>`;
        }).join('') + `</div>`;
    }

    function selectStatusGroup(group) {
        activeStatusGroup = group;
        currentPage = 1;
        renderStatusControls();
        renderTracking();
    }

    function statusGroupFor(row) {
        const status = row.clearance?.status || 'Not Submitted';
        if (status === 'Pending Verification' || status === 'Under Review') return 'pending';
        if (status === 'Action Required' || status === 'Resubmission') return 'action';
        if (status === 'Completed' || status === 'Approved' || status === 'Archived') return 'completed';
        return 'not-submitted';
    }

    function renderTracking() {
        const body = document.getElementById('trackingBody');
        const query = (document.getElementById('trackingSearch')?.value || '').toLowerCase();
        const empFilter = document.getElementById('trackingEmpStatusFilter')?.value || 'all';

        const filtered = trackingRows.filter(row => {
            const matchesGroup = activeStatusGroup === 'all' || statusGroupFor(row) === activeStatusGroup;
            const text = `${row.name} ${row.faculty_id} ${row.designated_department}`.toLowerCase();
            const matchesQuery = text.includes(query);
            const rowEmp = row.employment_status || '';
            const matchesEmp = empFilter === 'all' || rowEmp.toLowerCase() === empFilter.toLowerCase();
            return matchesGroup && matchesQuery && matchesEmp;
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / trackingPageSize));
        currentPage = Math.min(currentPage, totalPages);
        const visibleRows = filtered.slice((currentPage - 1) * trackingPageSize, currentPage * trackingPageSize);

        if (!visibleRows.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary py-5">No faculty clearance records matching your filters.</td></tr>';
        } else {
            body.innerHTML = visibleRows.map(row => {
                const c = row.clearance;
                const expiry = row.contractual_end && row.contractual_end !== '0000-00-00'
                    ? new Date(`${row.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                    : 'Not set';
                const tone = c.status === 'Action Required' ? 'danger' : (c.status === 'Completed' ? 'success' : (c.status === 'Not Submitted' ? 'secondary' : 'info'));

                const emp = row.employment_status || 'Probationary';
                const empBadge = emp === 'Regular'
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1 small">Regular</span>'
                    : (emp === 'Probationary'
                        ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1 small">Probationary</span>'
                        : '<span class="badge bg-secondary-subtle text-body-secondary border ms-1 small">Part-Time</span>');

                return `<tr>
                <td class="ps-3">
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <strong class="text-body-emphasis">${escapeHtml(row.name)}</strong>
                        ${empBadge}
                    </div>
                    <small class="d-block text-body-secondary">${escapeHtml(row.faculty_id || '')}</small>
                </td>
                <td>${escapeHtml(row.designated_department || 'N/A')}</td>
                <td class="${row.days_remaining !== null && row.days_remaining <= 30 ? 'text-danger fw-bold' : ''}">${expiry}<small class="d-block text-body-secondary">${row.days_remaining === null ? '' : (row.days_remaining < 0 ? 'Expired' : row.days_remaining + ' days remaining')}</small></td>
                <td style="min-width:150px"><div class="progress mb-1" style="height:7px"><div class="progress-bar bg-${tone}" style="width:${c.progress}%"></div></div><small class="text-body-secondary">${c.progress}% (${c.approved_items}/${c.total_items})</small></td>
                <td><span class="badge bg-${tone}-subtle text-${tone} border border-${tone}-subtle px-2 py-1">${escapeHtml(c.status)}</span></td>
                <td>${row.submitted_at ? new Date(row.submitted_at.replace(' ', 'T')).toLocaleDateString() : '—'}</td>
                <td class="text-end pe-3"><button class="btn btn-sm btn-outline-primary" onclick="openReview(${row.id})"><i class="fas fa-search me-1"></i>Review</button></td>
            </tr>`;
            }).join('');
        }
        renderPagination(totalPages, filtered.length);
    }

    function filterTracking() {
        renderTracking();
    }

    function renderPagination(totalPages, totalRows) {
        let pager = document.getElementById('trackingPagination');
        if (!pager) return;
        pager.className = 'd-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top';
        pager.innerHTML = `<small class="text-body-secondary">${totalRows ? `Page ${currentPage} of ${totalPages} · ${totalRows} active records` : 'No records'}</small><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" ${currentPage <= 1 ? 'disabled' : ''} onclick="changeTrackingPage(-1)"><i class="fas fa-chevron-left"></i></button><button class="btn btn-outline-secondary" ${currentPage >= totalPages ? 'disabled' : ''} onclick="changeTrackingPage(1)"><i class="fas fa-chevron-right"></i></button></div>`;
    }

    function changeTrackingPage(direction) {
        currentPage += direction;
        renderTracking();
    }

    /* ARCHIVED RECORDS LOGIC */
    function populateArchiveTermFilter() {
        const select = document.getElementById('archiveTermFilter');
        if (!select) return;
        const currentVal = select.value;
        const terms = Array.from(new Set(archiveRows.map(r => `${r.academic_year} · ${r.semester}`)));
        select.innerHTML = '<option value="all">All Academic Terms</option>' + terms.map(t => `<option value="${escapeHtml(t)}" ${currentVal === t ? 'selected' : ''}>${escapeHtml(t)}</option>`).join('');
    }

    function getFilteredArchives() {
        const query = (document.getElementById('archiveSearch')?.value || '').toLowerCase();
        const termFilter = document.getElementById('archiveTermFilter')?.value || 'all';
        const intentFilter = document.getElementById('archiveIntentFilter')?.value || 'all';
        const empFilter = document.getElementById('archiveEmpFilter')?.value || 'all';

        return archiveRows.filter(row => {
            const text = `${row.name} ${row.faculty_no} ${row.designated_department}`.toLowerCase();
            const matchesQuery = !query || text.includes(query);
            const termLabel = `${row.academic_year} · ${row.semester}`;
            const matchesTerm = termFilter === 'all' || termLabel === termFilter;
            const matchesIntent = intentFilter === 'all' || row.intent_type === intentFilter;
            const rowEmp = row.employment_status || '';
            const matchesEmp = empFilter === 'all' || rowEmp.toLowerCase() === empFilter.toLowerCase();
            return matchesQuery && matchesTerm && matchesIntent && matchesEmp;
        });
    }

    function renderArchives() {
        const body = document.getElementById('archiveBody');
        const filtered = getFilteredArchives();
        const totalPages = Math.max(1, Math.ceil(filtered.length / archivePageSize));
        archiveCurrentPage = Math.min(archiveCurrentPage, totalPages);
        const visibleRows = filtered.slice((archiveCurrentPage - 1) * archivePageSize, archiveCurrentPage * archivePageSize);

        if (!visibleRows.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary py-5"><i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>No archived clearance records found matching your filters.</td></tr>';
        } else {
            body.innerHTML = visibleRows.map(row => {
                const expiry = row.contractual_end && row.contractual_end !== '0000-00-00'
                    ? new Date(`${row.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                    : 'Not set';
                const clearedAt = row.updated_at
                    ? new Date(row.updated_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '—';
                const intentLabel = row.intent_type === 'renewal' ? 'Contract Renewal' : (row.intent_type === 'regularization' ? 'Regularization' : 'Clearance Only');

                const emp = row.employment_status || 'Regular';
                const empBadge = emp === 'Regular'
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1 small">Regular</span>'
                    : (emp === 'Probationary'
                        ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1 small">Probationary</span>'
                        : '<span class="badge bg-secondary-subtle text-body-secondary border ms-1 small">Part-Time</span>');

                const reqTags = (row.items || []).map(it => {
                    if (!it.file_name && it.status !== 'Cleared') {
                        return `<span class="badge bg-secondary-subtle text-body-secondary border me-1 mb-1 small" title="${escapeHtml(it.name)}: Missing"><i class="fas fa-times-circle me-1"></i>${escapeHtml(it.name)}: Missing</span>`;
                    }
                    if (it.status === 'Hold' || it.status === 'Denied') {
                        return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1 mb-1 small" title="${escapeHtml(it.name)}: Denied"><i class="fas fa-exclamation-circle me-1"></i>${escapeHtml(it.name)}: Denied</span>`;
                    }
                    return `<span class="badge bg-success-subtle text-success border border-success-subtle me-1 mb-1 small" title="${escapeHtml(it.name)}: Approved"><i class="fas fa-check-circle me-1"></i>${escapeHtml(it.name)}</span>`;
                }).join('');

                return `<tr>
                <td class="ps-3">
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <strong class="text-body-emphasis">${escapeHtml(row.name)}</strong>
                        ${empBadge}
                    </div>
                    <small class="d-block text-body-secondary">${escapeHtml(row.faculty_no || '')} · ${escapeHtml(row.designated_department || '')}</small>
                </td>
                <td><span class="badge bg-secondary-subtle text-body-secondary border">${escapeHtml(row.academic_year)} · ${escapeHtml(row.semester)}</span></td>
                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">${escapeHtml(intentLabel)}</span></td>
                <td><strong class="text-success">${expiry}</strong></td>
                <td style="max-width: 250px;">${reqTags || '<span class="text-body-secondary small">No requirements</span>'}</td>
                <td><small class="text-body-secondary">${clearedAt}</small></td>
                <td class="text-end pe-3">
                    <button class="btn btn-sm btn-outline-success" onclick="openArchiveDetail(${row.clearance_id || 0}, ${row.archive_id || 0})">
                        <i class="fas fa-folder-open me-1"></i>View Record
                    </button>
                </td>
            </tr>`;
            }).join('');
        }
        renderArchivePagination(totalPages, filtered.length);
    }

    function filterArchives() {
        archiveCurrentPage = 1;
        renderArchives();
    }

    function renderArchivePagination(totalPages, totalRows) {
        let pager = document.getElementById('archivePagination');
        if (!pager) return;
        pager.className = 'd-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top';
        pager.innerHTML = `<small class="text-body-secondary">${totalRows ? `Page ${archiveCurrentPage} of ${totalPages} · ${totalRows} completed records` : 'No records'}</small><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" ${archiveCurrentPage <= 1 ? 'disabled' : ''} onclick="changeArchivePage(-1)"><i class="fas fa-chevron-left"></i></button><button class="btn btn-outline-secondary" ${archiveCurrentPage >= totalPages ? 'disabled' : ''} onclick="changeArchivePage(1)"><i class="fas fa-chevron-right"></i></button></div>`;
    }

    function changeArchivePage(direction) {
        archiveCurrentPage += direction;
        renderArchives();
    }

    async function openArchiveDetail(clearanceId, archiveId = 0) {
        try {
            const queryParam = archiveId > 0 ? `archive_id=${archiveId}` : `clearance_id=${clearanceId}`;
            const response = await fetch(`${clearanceApi}?action=archive-detail&${queryParam}`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            const r = data.record;

            document.getElementById('archiveModalTitle').innerHTML = `<i class="fas fa-archive me-2"></i>Archived Record - ${escapeHtml(r.name)}`;
            document.getElementById('archiveModalMeta').textContent = `${r.faculty_no || ''} · ${r.designated_department || 'Department'}`;
            document.getElementById('archiveModalTerm').textContent = `Academic Term: ${r.academic_year} · ${r.semester}`;
            document.getElementById('archiveModalCompletedAt').textContent = r.completed_at || r.updated_at ? new Date((r.completed_at || r.updated_at).replace(' ', 'T')).toLocaleString() : '—';

            document.getElementById('archiveFacultyName').textContent = r.name;
            document.getElementById('archiveFacultyNo').textContent = r.faculty_no || '—';
            document.getElementById('archiveDepartment').textContent = r.designated_department || '—';
            document.getElementById('archiveRank').textContent = r.academic_rank || r.position || '—';
            document.getElementById('archiveIntent').textContent = r.intent_type === 'renewal' ? 'Contract Renewal / Extension' : (r.intent_type === 'regularization' ? 'Regularization' : 'Proceed with Clearance');
            document.getElementById('archiveContractEnd').textContent = r.contractual_end && r.contractual_end !== '0000-00-00' ? new Date(`${r.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not set';
            document.getElementById('archiveEmpStatus').textContent = r.employment_status || 'Regular';
            document.getElementById('archiveEmail').textContent = r.email || '—';

            const body = document.getElementById('archiveRequirementsBody');
            body.innerHTML = (r.items || []).map((it, idx) => {
                const isMissing = !it.file_name && it.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (it.status === 'Cleared' ? 'Approved / Cleared' : (it.status === 'Hold' ? 'Denied' : it.status));
                const badgeClass = isMissing ? 'bg-secondary-subtle text-body-secondary border' : (it.status === 'Cleared' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
                const fileUrl = it.file_path
                    ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}`
                    : `${clearanceApi}?action=file&download=1&item_id=${it.id || 0}`;

                return `<tr>
                <td>${idx + 1}</td>
                <td><strong class="text-body-emphasis">${escapeHtml(it.name)}</strong></td>
                <td>${it.file_name ? `<a class="btn btn-sm btn-outline-success" href="${fileUrl}" download="${escapeHtml(it.file_name)}" title="Download file"><i class="fas fa-download me-1"></i>Download (${escapeHtml(it.file_name)})</a>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-circle-xmark me-1"></i>No file (Missing)</span>'}</td>
                <td><span class="badge ${badgeClass}"><i class="${isMissing ? 'fas fa-question-circle' : (it.status === 'Cleared' ? 'fas fa-check-circle' : 'fas fa-times-circle')} me-1"></i>${escapeHtml(statusLabel)}</span></td>
                <td><small class="text-body-secondary">${escapeHtml(it.remarks || (isMissing ? 'Requirement not submitted.' : 'Approved without remarks.'))}</small></td>
                <td><small class="text-body-secondary">${it.cleared_at ? new Date(it.cleared_at.replace(' ', 'T')).toLocaleDateString() : '—'}</small></td>
            </tr>`;
            }).join('');

            archiveDetailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('archiveDetailModal'));
            archiveDetailModal.show();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    function exportArchiveCsv() {
        const filtered = getFilteredArchives();
        if (!filtered.length) {
            alert('No archived clearance records available to export.');
            return;
        }
        const headers = ['Clearance ID', 'Faculty Name', 'Faculty ID', 'Department', 'Employment Status', 'Academic Term', 'Statement of Intent', 'Contract Expiry Date', 'Date Completed'];
        const rows = filtered.map(r => [
            r.clearance_id,
            `"${(r.name || '').replace(/"/g, '""')}"`,
            `"${(r.faculty_no || '').replace(/"/g, '""')}"`,
            `"${(r.designated_department || '').replace(/"/g, '""')}"`,
            `"${r.employment_status || 'Probationary'}"`,
            `"${r.academic_year} · ${r.semester}"`,
            `"${r.intent_type}"`,
            `"${r.contractual_end || ''}"`,
            `"${r.updated_at || ''}"`
        ]);

        const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `faculty_clearance_archives_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printArchiveTable() {
        window.print();
    }

    function printSingleArchive() {
        const printContents = document.getElementById('archivePrintArea').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`<html><head><title>Faculty Clearance Archive Record</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head><body class="p-4 bg-white text-dark">${printContents}</body></html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }

    /* ACTIVE CLEARANCE REVIEW LOGIC */
    async function openReview(facultyId) {
        currentReviewFacultyId = facultyId;
        const alertBox = document.getElementById('reviewAlert');
        if (alertBox) alertBox.classList.add('d-none');

        try {
            const response = await fetch(`${clearanceApi}?action=review&faculty_id=${facultyId}`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);

            const profile = data.profile;
            const c = data.clearance;
            currentReviewProfile = profile;

            document.getElementById('reviewTitle').innerHTML = `<i class="fas fa-clipboard-check me-2"></i>Review Clearance - ${escapeHtml(profile.first_name)} ${escapeHtml(profile.last_name)}`;
            document.getElementById('reviewMeta').textContent = `${profile.faculty_id || ''} · ${profile.designated_department || 'Department'}`;

            const expiry = profile.contractual_end && profile.contractual_end !== '0000-00-00'
                ? new Date(`${profile.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                : 'Not set';
            const expiryEl = document.getElementById('summaryContractExpiry');
            if (expiryEl) expiryEl.textContent = expiry;

            const daysRemaining = profile.contractual_end && profile.contractual_end !== '0000-00-00'
                ? Math.floor((new Date(`${profile.contractual_end}T00:00:00`) - new Date()) / 86400000)
                : null;
            const daysEl = document.getElementById('summaryDaysRemaining');
            if (daysEl) {
                daysEl.textContent = daysRemaining === null ? 'No expiration date' : (daysRemaining < 0 ? 'Contract Expired' : `${daysRemaining} days remaining`);
                daysEl.className = `small d-block ${daysRemaining !== null && daysRemaining <= 30 ? 'text-danger fw-bold' : 'text-body-secondary'}`;
            }

            const empStatus = profile.employment_status || 'Probationary';
            const empEl = document.getElementById('summaryEmpStatus');
            if (empEl) {
                empEl.textContent = empStatus;
                empEl.className = `badge ${empStatus === 'Regular' ? 'bg-success-subtle text-success border border-success-subtle' : (empStatus === 'Probationary' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-body-secondary border')} px-2 py-1`;
            }

            const intentTypeLabel = (c.intent_type === 'renewal')
                ? 'Contract Renewal / Extension'
                : (c.intent_type === 'regularization' ? 'Regularization' : (c.intent_type === 'resignation' ? 'Proceed with Clearance' : (c.intent_type || 'Contract Renewal')));
            const intentEl = document.getElementById('summaryIntentType');
            if (intentEl) intentEl.textContent = intentTypeLabel;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${c.progress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${c.progress}% (${c.approved_items}/${c.total_items})`;

            // Pre-fill renewal date with +1 year from base
            const baseDate = (profile.contractual_end && profile.contractual_end !== '0000-00-00')
                ? new Date(`${profile.contractual_end}T00:00:00`)
                : new Date();
            const nextYear = new Date(baseDate);
            nextYear.setFullYear(nextYear.getFullYear() + 1);
            const dateInput = document.getElementById('newContractEndDate');
            if (dateInput) dateInput.value = nextYear.toISOString().split('T')[0];

            // Pre-select employment status
            const empSelect = document.getElementById('employmentStatusSelect');
            const empHint = document.getElementById('empStatusHint');
            if (empSelect) {
                if (c.intent_type === 'regularization') {
                    empSelect.value = 'Regular';
                    if (empHint) empHint.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check me-1"></i>Regularization requested in Letter of Intent</span>';
                } else {
                    empSelect.value = empStatus;
                    if (empHint) empHint.textContent = 'Current status: ' + empStatus;
                }
            }

            // Table rows
            const body = document.getElementById('reviewBody');
            body.innerHTML = c.items.length ? c.items.map(item => {
                const isMissing = !item.file_name && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const badgeClass = isMissing
                    ? 'bg-secondary-subtle text-body-secondary border'
                    : (item.status === 'Cleared'
                        ? 'bg-success-subtle text-success border border-success-subtle'
                        : (item.status === 'Denied' || item.status === 'Hold'
                            ? 'bg-danger-subtle text-danger border border-danger-subtle'
                            : (item.status === 'On Hold'
                                ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle')));

                return `<tr>
                <td><strong class="text-body-emphasis">${escapeHtml(item.name)}</strong></td>
                <td>${item.file_name ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${clearanceApi}?action=file&item_id=${item.id}"><i class="fas fa-eye me-1"></i>View file</a><small class="d-block text-body-secondary mt-1">${escapeHtml(item.file_name)}</small>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-excel me-1"></i>No file uploaded (Missing)</span>'}</td>
                <td><span class="badge ${badgeClass} px-2 py-1">${escapeHtml(statusLabel)}</span></td>
                <td><small class="text-body-secondary">${escapeHtml(item.remarks || (isMissing ? 'No file submitted' : 'No remark'))}</small></td>
                <td class="text-end"><div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="reviewItem(${item.id}, 'approve')" ${item.file_name ? '' : 'disabled'} title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger" onclick="reviewItem(${item.id}, 'deny')" ${item.file_name ? '' : 'disabled'} title="Deny (Red)"><i class="fas fa-times"></i></button>
                    <button class="btn btn-warning text-dark" onclick="reviewItem(${item.id}, 'hold')" ${item.file_name ? '' : 'disabled'} title="Put On Hold (Yellow)"><i class="fas fa-pause"></i></button>
                </div></td>
            </tr>`;
            }).join('') : '<tr><td colspan="5" class="text-center text-body-secondary py-4">No clearance submitted.</td></tr>';

            reviewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal'));
            reviewModal.show();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    function applyRenewalPreset(months) {
        const baseDate = (currentReviewProfile && currentReviewProfile.contractual_end && currentReviewProfile.contractual_end !== '0000-00-00')
            ? new Date(`${currentReviewProfile.contractual_end}T00:00:00`)
            : new Date();
        baseDate.setMonth(baseDate.getMonth() + months);
        const dateInput = document.getElementById('newContractEndDate');
        if (dateInput) {
            dateInput.value = baseDate.toISOString().split('T')[0];
        }
    }

    async function submitContractRenewal() {
        if (!currentReviewFacultyId) return;
        const dateInput = document.getElementById('newContractEndDate');
        const newDate = dateInput?.value;
        if (!newDate) {
            alert('Please select a valid new contract expiration date.');
            dateInput?.focus();
            return;
        }
        const remarks = document.getElementById('renewalRemarks')?.value || '';
        const empStatus = document.getElementById('employmentStatusSelect')?.value || 'Probationary';

        const btn = document.getElementById('btnSubmitRenewal');
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
        }

        const form = new FormData();
        form.append('action', 'renew-contract');
        form.append('faculty_id', currentReviewFacultyId);
        form.append('new_contract_end', newDate);
        form.append('employment_status', empStatus);
        form.append('renewal_remark', remarks);

        try {
            const response = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);

            showTrackingAlert(data.message, 'success');
            reviewModal?.hide();
            loadTracking();
            loadArchives();
        } catch (error) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = error.message;
                alertBox.classList.remove('d-none');
            } else {
                showTrackingAlert(error.message, 'danger');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }

    // ── Remark Modal state ──────────────────────────────────────────────────────
    let _remarkResolve = null;
    let _remarkModal = null;

    function openRemarkModal({ title, sub, label, hint, placeholder, defaultValue = '', required = true, iconClass, headerClass, btnClass, btnIcon, btnText }) {
        return new Promise((resolve) => {
            _remarkResolve = resolve;

            const header = document.getElementById('remarkModalHeader');
            const iconEl = document.getElementById('remarkModalIcon');
            const titleEl = document.getElementById('remarkModalTitle');
            const subEl = document.getElementById('remarkModalSub');
            const labelEl = document.getElementById('remarkModalLabel');
            const input = document.getElementById('remarkModalInput');
            const hintEl = document.getElementById('remarkModalHint');
            const confirm = document.getElementById('remarkModalConfirm');
            const confirmIcon = document.getElementById('remarkModalConfirmIcon');
            const confirmText = document.getElementById('remarkModalConfirmText');
            const errEl = document.getElementById('remarkModalError');
            const cancelBtn = document.getElementById('remarkModalCancel');

            // Style
            header.className = `modal-header py-3 border-bottom ${headerClass || ''}`;
            iconEl.className = `rounded-circle d-flex align-items-center justify-content-center ${iconClass || ''}`;
            iconEl.innerHTML = `<i class="fas ${btnIcon || 'fa-pen'}"></i>`;
            titleEl.textContent = title || 'Add Remark';
            subEl.textContent = sub || '';
            labelEl.textContent = label || 'Remark';
            hintEl.textContent = hint || '';
            input.value = defaultValue;
            input.placeholder = placeholder || 'Enter remark...';
            input.classList.remove('is-invalid');
            confirm.className = `btn flex-fill fw-semibold ${btnClass || 'btn-primary'}`;
            confirmIcon.className = `fas ${btnIcon || 'fa-check'} me-1`;
            confirmText.textContent = btnText || 'Confirm';
            errEl.textContent = required ? 'A remark is required for this action.' : '';

            // Cancel clears promise
            cancelBtn.onclick = () => { _remarkModal.hide(); resolve(null); };

            // Confirm validates and resolves
            confirm.onclick = () => {
                const val = input.value.trim();
                if (required && !val) {
                    input.classList.add('is-invalid');
                    input.focus();
                    return;
                }
                _remarkModal.hide();
                resolve(val || defaultValue);
            };

            // Allow Enter to submit
            input.onkeydown = (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); confirm.click(); } };

            _remarkModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('remarkModal'));
            _remarkModal.show();
            // Bump the dynamically-generated backdrop above the review modal
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length >= 2) {
                    backdrops[backdrops.length - 1].style.zIndex = '1070';
                }
                input.focus();
            }, 50);
        });
    }

    async function reviewItem(itemId, decision) {
        const isApprove = decision === 'approve';
        const isDeny = decision === 'deny';

        let remark;
        if (isApprove) {
            remark = await openRemarkModal({
                title: 'Approve Requirement',
                sub: 'You are approving this clearance submission.',
                label: 'Remark (Optional)',
                hint: 'Leave blank to use the default approval message.',
                placeholder: 'Requirement approved.',
                defaultValue: 'Requirement approved.',
                required: false,
                headerClass: 'bg-success-subtle',
                iconClass: 'bg-success text-white',
                btnClass: 'btn-success',
                btnIcon: 'fa-check',
                btnText: 'Approve'
            });
        } else if (isDeny) {
            remark = await openRemarkModal({
                title: 'Deny Requirement',
                sub: 'The faculty member will be notified to resubmit.',
                label: 'Denial Reason (Required)',
                hint: 'Explain clearly why this submission was denied.',
                placeholder: 'e.g., Document is incomplete or incorrect.',
                defaultValue: '',
                required: true,
                headerClass: 'bg-danger-subtle',
                iconClass: 'bg-danger text-white',
                btnClass: 'btn-danger',
                btnIcon: 'fa-times',
                btnText: 'Deny'
            });
        } else {
            remark = await openRemarkModal({
                title: 'Place On Hold',
                sub: 'The requirement will be flagged for further review.',
                label: 'Hold Reason (Required)',
                hint: 'State what needs to be addressed before approval.',
                placeholder: 'e.g., Pending additional verification.',
                defaultValue: '',
                required: true,
                headerClass: 'bg-warning-subtle',
                iconClass: 'bg-warning text-dark',
                btnClass: 'btn-warning text-dark',
                btnIcon: 'fa-pause',
                btnText: 'Put On Hold'
            });
        }

        if (remark === null) return; // Cancelled

        // Disable all action buttons while submitting
        const reviewBody = document.getElementById('reviewBody');
        const btns = reviewBody ? reviewBody.querySelectorAll('button') : [];
        btns.forEach(b => { b.disabled = true; });

        const form = new FormData();
        form.append('action', 'review-item');
        form.append('item_id', itemId);
        form.append('decision', decision);
        form.append('remark', remark);

        try {
            const response = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);

            // Show inline success alert inside the review modal
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-success';
                alertBox.innerHTML = `<i class="fas fa-check-circle me-2"></i>${escapeHtml(data.message)}`;
                alertBox.classList.remove('d-none');
            }

            // Refresh the items table in-place without closing the modal
            if (currentReviewFacultyId) {
                await refreshReviewItems(currentReviewFacultyId);
            }

            // Refresh tracking & archive counts in the background
            loadTracking();
            loadArchives();
        } catch (error) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = error.message;
                alertBox.classList.remove('d-none');
            }
        } finally {
            btns.forEach(b => { b.disabled = false; });
        }
    }

    async function refreshReviewItems(facultyId) {
        try {
            const response = await fetch(`${clearanceApi}?action=review&faculty_id=${facultyId}`);
            const data = await response.json();
            if (!data.ok) return;
            const c = data.clearance;
            const body = document.getElementById('reviewBody');
            if (!body) return;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${c.progress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${c.progress}% (${c.approved_items}/${c.total_items})`;

            body.innerHTML = c.items.length ? c.items.map(item => {
                const isMissing = !item.file_name && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const badgeClass = isMissing
                    ? 'bg-secondary-subtle text-body-secondary border'
                    : (item.status === 'Cleared'
                        ? 'bg-success-subtle text-success border border-success-subtle'
                        : (item.status === 'Denied' || item.status === 'Hold'
                            ? 'bg-danger-subtle text-danger border border-danger-subtle'
                            : (item.status === 'On Hold'
                                ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle')));
                return `<tr>
                <td><strong class="text-body-emphasis">${escapeHtml(item.name)}</strong></td>
                <td>${item.file_name ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${clearanceApi}?action=file&item_id=${item.id}"><i class="fas fa-eye me-1"></i>View file</a><small class="d-block text-body-secondary mt-1">${escapeHtml(item.file_name)}</small>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-excel me-1"></i>No file uploaded (Missing)</span>'}</td>
                <td><span class="badge ${badgeClass} px-2 py-1">${escapeHtml(statusLabel)}</span></td>
                <td><small class="text-body-secondary">${escapeHtml(item.remarks || (isMissing ? 'No file submitted' : 'No remark'))}</small></td>
                <td class="text-end"><div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="reviewItem(${item.id}, 'approve')" ${item.file_name ? '' : 'disabled'} title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger" onclick="reviewItem(${item.id}, 'deny')" ${item.file_name ? '' : 'disabled'} title="Deny"><i class="fas fa-times"></i></button>
                    <button class="btn btn-warning text-dark" onclick="reviewItem(${item.id}, 'hold')" ${item.file_name ? '' : 'disabled'} title="Put On Hold"><i class="fas fa-pause"></i></button>
                </div></td>
            </tr>`;
            }).join('') : '<tr><td colspan="5" class="text-center text-body-secondary py-4">No clearance submitted.</td></tr>';
        } catch (e) {
            // silent
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        }[character]));
    }

    function showTrackingAlert(message, tone) {
        const alert = document.getElementById('trackingAlert');
        if (!alert) return;
        alert.className = `alert alert-${tone} alert-dismissible fade show mb-4`;
        alert.innerHTML = `<i class="fas fa-${tone === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    }

    // Initial load
    loadTracking();
    loadArchives();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>