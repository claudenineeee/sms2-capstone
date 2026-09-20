<?php
/**
 * _shared/clearance-portal.php
 *
 * Shared Faculty Clearance Portal UI for all clearance office roles.
 * Expects these variables to be set by the including file:
 *   $officeLabel  – human-readable office name (e.g. "HR Clearance Office")
 *   $officeIcon   – FontAwesome icon class without "fa-" prefix (e.g. "fa-user-tie")
 *   $officeColor  – Bootstrap color name (e.g. "primary", "success")
 */
$officeLabel = $officeLabel ?? 'Clearance Office';
$officeIcon = $officeIcon ?? 'fa-clipboard-check';
$officeColor = $officeColor ?? 'primary';
$targetRequirementName = $targetRequirementName ?? null;

if (!$targetRequirementName) {
    $currentRole = getCurrentUserRoleKey();
    if (in_array($currentRole, ['registrar_clearance', 'registrar'], true)) {
        $targetRequirementName = 'Academic Clearance';
    } elseif (in_array($currentRole, ['finance_office', 'finance'], true)) {
        $targetRequirementName = 'Financial Clearance';
    } elseif (in_array($currentRole, ['property_custodian_office', 'property'], true)) {
        $targetRequirementName = 'Property Clearance';
    } elseif (in_array($currentRole, ['hr_clearance', 'hr'], true)) {
        $targetRequirementName = 'HR Clearance';
    } elseif (in_array($currentRole, ['library_clearance', 'library'], true)) {
        $targetRequirementName = 'Library Clearance';
    }
}
?>
<?php renderBreadcrumbs($breadcrumbs); ?>
<div class="container-fluid p-3 p-md-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-<?= $officeColor ?> text-uppercase small fw-bold mb-1"><?= htmlspecialchars($officeLabel) ?>
            </p>
            <h3 class="fw-bold text-body-emphasis mb-1">
                <i class="fas <?= htmlspecialchars($officeIcon) ?> text-<?= $officeColor ?> me-2"></i>Faculty Clearance
                Portal
            </h3>
            <p class="text-body-secondary small mb-0">
                Track ongoing clearance submissions, review requirements, and inspect archived completed records.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-<?= $officeColor ?> btn-sm btn-md-normal w-100 w-sm-auto"
                onclick="refreshCurrentTab()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <div id="trackingAlert" class="alert d-none" role="status"></div>

    <!-- Metric Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card primary border shadow-sm position-relative h-100 role-button"
                onclick="switchToActiveTab('pending')">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-clock"></i></div>
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

        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card border shadow-sm position-relative h-100 role-button"
                style="border-left: 4px solid #dc3545 !important;" onclick="switchToActiveTab('action')">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-danger fs-4"><i class="fas fa-exclamation-triangle"></i></div>
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

        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card success border shadow-sm position-relative h-100 role-button"
                onclick="switchToArchiveTab()">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-archive"></i></div>
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

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 p-1 bg-body-tertiary border rounded-3 d-flex flex-column flex-sm-row gap-1"
        id="clearanceTabs" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active rounded-2 w-100 py-2 text-center" id="tab-active-btn" data-bs-toggle="pill"
                data-bs-target="#tab-active" type="button" role="tab" aria-selected="true">
                <i class="fas fa-tasks me-2"></i><span class="d-inline-block">Active Clearance Tracking</span>
                <span class="badge bg-<?= $officeColor ?> ms-1" id="activeBadgeCount">0</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link rounded-2 w-100 py-2 text-center" id="tab-archive-btn" data-bs-toggle="pill"
                data-bs-target="#tab-archive" type="button" role="tab" aria-selected="false" onclick="loadArchives()">
                <i class="fas fa-archive me-2"></i><span class="d-inline-block">Archived Completed Records</span>
                <span class="badge bg-success ms-1" id="archiveBadgeCount">0</span>
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
                    <h6 class="fw-bold mb-0 text-body-emphasis">
                        <i class="fas fa-list-ul me-2 text-<?= $officeColor ?>"></i>Ongoing Faculty Clearance Records
                    </h6>
                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                        <select id="trackingEmpStatusFilter" class="form-select form-select-sm"
                            onchange="filterTracking()">
                            <option value="all">All Employment Types</option>
                            <option value="Probationary">Probationary Only</option>
                            <option value="Regular">Regular Only</option>
                            <option value="Part-Time">Part-Time Only</option>
                        </select>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-secondary text-body-secondary">
                                <i class="fas fa-search"></i>
                            </span>
                            <input id="trackingSearch" class="form-control" placeholder="Search faculty or ID"
                                oninput="filterTracking()">
                        </div>
                        <button type="button"
                            class="btn btn-outline-secondary btn-sm text-nowrap d-flex align-items-center gap-1"
                            onclick="resetTrackingFilters()" title="Reset Filters & Search">
                            <i class="fas fa-rotate-left"></i>
                            <span class="d-none d-sm-inline">Reset</span>
                        </button>
                    </div>
                </div>
                <div class="p-3 bg-body-tertiary border-bottom" id="statusControlsContainer"></div>
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
                            <h6 class="fw-bold mb-0 text-success">
                                <i class="fas fa-archive me-2"></i>Archived Completed Clearance History
                            </h6>
                            <small class="text-body-secondary">Official record of all completed clearances and approved
                                faculty documents.</small>
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
                            <select id="archiveEmpFilter" class="form-select form-select-sm"
                                onchange="filterArchives()">
                                <option value="all">All Statuses</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Regular">Regular</option>
                                <option value="Part-Time">Part-Time</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <button type="button"
                                class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-1"
                                onclick="resetArchiveFilters()" title="Reset Filters & Search">
                                <i class="fas fa-rotate-left"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="archiveTable">
                        <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary">
                            <tr>
                                <th class="ps-3">Faculty</th>
                                <th>Academic Term</th>
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

<!-- REMARK MODAL -->
<style>
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
                <div class="mt-2"><small class="text-body-secondary" id="remarkModalHint"></small></div>
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

<!-- REVIEW MODAL -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-<?= $officeColor ?> text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="reviewTitle"><i class="fas fa-clipboard-check me-2"></i>Review
                        Clearance</h5>
                    <small class="text-white-50" id="reviewMeta"></small>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div id="reviewAlert" class="alert d-none mb-3"></div>
                <div class="card bg-body-tertiary border mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 text-body">
                            <div class="col-12 col-md-4 border-end-md border-body-subtle">
                                <small class="text-body-secondary d-block">Current Contract Expiry</small>
                                <span class="fw-bold fs-6 text-body-emphasis" id="summaryContractExpiry">—</span>
                                <small class="d-block" id="summaryDaysRemaining"></small>
                            </div>
                            <div class="col-12 col-md-4 border-end-md border-body-subtle">
                                <small class="text-body-secondary d-block mb-1">Employment Status</small>
                                <span
                                    class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"
                                    id="summaryEmpStatus">—</span>
                            </div>
                            <div class="col-12 col-md-4">
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

                <h6 class="fw-bold text-uppercase small text-body-secondary mb-3">
                    <i class="fas fa-list-check me-1 text-<?= $officeColor ?>"></i> Submitted
                    <?= htmlspecialchars($targetRequirementName ?? 'Clearance') ?> Requirement
                </h6>
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
                        <tbody id="reviewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top d-flex justify-content-end align-items-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
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
                <div class="card bg-body-tertiary border mb-4">
                    <div class="card-header bg-body-secondary py-2 border-bottom">
                        <h6 class="mb-0 fw-bold small text-uppercase text-body-emphasis"><i
                                class="fas fa-id-card me-2 text-<?= $officeColor ?>"></i>Faculty Information</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 text-body">
                            <div class="col-12 col-sm-6 col-md-3"><small class="text-body-secondary d-block">Faculty
                                    Member</small><strong class="text-body-emphasis" id="archiveFacultyName">—</strong>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3"><small class="text-body-secondary d-block">Faculty ID
                                    No.</small><span id="archiveFacultyNo">—</span></div>
                            <div class="col-12 col-sm-6 col-md-3"><small
                                    class="text-body-secondary d-block">Department</small><span
                                    id="archiveDepartment">—</span></div>
                            <div class="col-12 col-sm-6 col-md-4"><small class="text-body-secondary d-block">Academic
                                    Rank</small><span id="archiveRank">—</span></div>
                            <div class="col-12 col-sm-6 col-md-4"><small class="text-body-secondary d-block">Contract
                                    Expiration Date</small><strong class="text-success"
                                    id="archiveContractEnd">—</strong></div>
                            <div class="col-12 col-sm-6 col-md-4"><small class="text-body-secondary d-block">Employment
                                    Status</small><span id="archiveEmpStatus">—</span></div>
                            <div class="col-12 col-sm-6 col-md-3"><small class="text-body-secondary d-block">Contact
                                    Email</small><span id="archiveEmail" class="small">—</span></div>
                        </div>
                    </div>
                </div>
                <h6 class="fw-bold text-uppercase small text-body-secondary mb-3"><i
                        class="fas fa-tasks me-1 text-success"></i>
                    <?= $targetRequirementName ? htmlspecialchars($targetRequirementName) . ' Record' : 'Approved Clearance Requirements' ?>
                </h6>
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
                        <tbody id="archiveRequirementsBody"></tbody>
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
    const officeRequirementName = <?= json_encode($targetRequirementName ?? '') ?>;
    let trackingRows = [], archiveRows = [], reviewModal, archiveDetailModal;
    let currentReviewFacultyId = null, currentReviewProfile = null, currentReviewClearance = null;
    let activeStatusGroup = 'all', currentPage = 1;
    const trackingPageSize = 5;
    let archiveCurrentPage = 1;
    const archivePageSize = 8;

    async function loadTracking() {
        const body = document.getElementById('trackingBody');
        try {
            const response = await fetch(`${clearanceApi}?action=summary`);
            if (!response.ok) {
                const errText = await response.text();
                let errMsg = `Server returned status ${response.status}`;
                try {
                    const parsed = JSON.parse(errText);
                    if (parsed.error) errMsg = parsed.error;
                } catch (_) { }
                throw new Error(errMsg);
            }
            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'Failed to retrieve clearance data.');
            trackingRows = Array.isArray(data.rows) ? data.rows : [];
            if (data.metrics) {
                const elPending = document.getElementById('metricPending');
                if (elPending) elPending.textContent = data.metrics.pending ?? 0;
                const elAction = document.getElementById('metricAction');
                if (elAction) elAction.textContent = data.metrics.action_required ?? 0;
                const elArchived = document.getElementById('metricArchived');
                if (elArchived) elArchived.textContent = data.metrics.archived ?? 0;
            }
            const elBadge = document.getElementById('activeBadgeCount');
            if (elBadge) elBadge.textContent = trackingRows.length;
            currentPage = 1;
            renderStatusControls();
            renderTracking();
        } catch (error) {
            console.error('Clearance tracking error:', error);
            showTrackingAlert(error.message, 'danger');
            if (body) {
                body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    ${escapeHtml(error.message || 'Unable to load clearance records.')}
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="loadTracking()"><i class="fas fa-rotate-left me-1"></i>Retry</button>
                </td></tr>`;
            }
        }
    }

    async function loadArchives() {
        const body = document.getElementById('archiveBody');
        if (body) body.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary py-5"><i class="fas fa-spinner fa-spin me-2"></i>Loading archived clearance records...</td></tr>';
        try {
            const response = await fetch(`${clearanceApi}?action=archives`);
            if (!response.ok) {
                const errText = await response.text();
                let errMsg = `Server returned status ${response.status}`;
                try {
                    const parsed = JSON.parse(errText);
                    if (parsed.error) errMsg = parsed.error;
                } catch (_) { }
                throw new Error(errMsg);
            }
            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'Failed to retrieve archive records.');
            archiveRows = Array.isArray(data.archives) ? data.archives : [];
            const elBadge = document.getElementById('archiveBadgeCount');
            if (elBadge) elBadge.textContent = archiveRows.length;
            const elMetric = document.getElementById('metricArchived');
            if (elMetric) elMetric.textContent = archiveRows.length;
            populateArchiveTermFilter();
            archiveCurrentPage = 1;
            renderArchives();
        } catch (error) {
            console.error('Clearance archives error:', error);
            showTrackingAlert(error.message, 'danger');
            if (body) {
                body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    ${escapeHtml(error.message || 'Unable to load archived records.')}
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="loadArchives()"><i class="fas fa-rotate-left me-1"></i>Retry</button>
                </td></tr>`;
            }
        }
    }

    function refreshCurrentTab() { loadTracking(); loadArchives(); }

    function switchToActiveTab(group) {
        const triggerEl = document.querySelector('#tab-active-btn');
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        if (group) selectStatusGroup(group);
    }

    function switchToArchiveTab() {
        const triggerEl = document.querySelector('#tab-archive-btn');
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        loadArchives();
    }

    function renderStatusControls() {
        const container = document.getElementById('statusControlsContainer');
        if (!container) return;

        // [key, label, badgeColor] — badgeColor is used for the count badge when NOT active
        const groups = [
            ['all',          'All Active',            'dark'],
            ['pending',      'Pending Verification',  'primary'],
            ['action',       'Denied / Resubmission', 'danger'],
            ['not-submitted','Not Submitted',          'secondary'],
        ];

        container.innerHTML = `<div class="d-flex flex-wrap gap-2 align-items-center">` +
            groups.map(([key, label, badgeColor]) => {
                const count = key === 'all'
                    ? trackingRows.length
                    : trackingRows.filter(row => statusGroupFor(row) === key).length;
                const isActive = activeStatusGroup === key;

                // Active chip: dark filled pill with white text + light badge
                // Inactive chip: white/light pill with muted text + colored badge
                const chipStyle = isActive
                    ? `background:#1e2533;color:#fff;border:none;`
                    : `background:#f0f2f5;color:#4b5563;border:1px solid #e2e6ea;`;

                const badgeStyle = isActive
                    ? `background:rgba(255,255,255,0.2);color:#fff;`
                    : (badgeColor === 'primary'   ? `background:#dbeafe;color:#1d4ed8;`
                     : badgeColor === 'danger'    ? `background:#fee2e2;color:#dc2626;`
                     : `background:#e5e7eb;color:#6b7280;`);

                return `<button type="button"
                    onclick="selectStatusGroup('${key}')"
                    style="
                        ${chipStyle}
                        border-radius:20px;
                        padding:4px 14px;
                        font-size:0.8rem;
                        font-weight:600;
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                        cursor:pointer;
                        transition:all .15s;
                        white-space:nowrap;
                    ">
                    ${escapeHtml(label)}
                    <span style="
                        ${badgeStyle}
                        border-radius:20px;
                        padding:1px 8px;
                        font-size:0.75rem;
                        font-weight:700;
                        min-width:20px;
                        text-align:center;
                    ">${count}</span>
                </button>`;
            }).join('') +
        `</div>`;
    }

    function selectStatusGroup(group) { activeStatusGroup = group; currentPage = 1; renderStatusControls(); renderTracking(); }

    function statusGroupFor(row) {
        const status = row.clearance?.status || 'Not Submitted';
        if (['Pending Verification', 'Under Review', 'Under Verification', 'For Final Approval', 'For Department Head Approval'].includes(status)) return 'pending';
        if (['Action Required', 'Resubmission', 'With Deficiency'].includes(status)) return 'action';
        if (['Completed', 'Approved', 'Archived', 'Cleared'].includes(status)) return 'completed';
        return 'not-submitted';
    }

    function renderTracking() {
        const body = document.getElementById('trackingBody');
        const query = (document.getElementById('trackingSearch')?.value || '').toLowerCase();
        const empFilter = document.getElementById('trackingEmpStatusFilter')?.value || 'all';
        const filtered = trackingRows.filter(row => {
            const matchesGroup = activeStatusGroup === 'all' || statusGroupFor(row) === activeStatusGroup;
            const text = `${row.name} ${row.faculty_id} ${row.designated_department}`.toLowerCase();
            const rowEmp = row.employment_status || '';
            return matchesGroup && text.includes(query) && (empFilter === 'all' || rowEmp.toLowerCase() === empFilter.toLowerCase());
        });
        const totalPages = Math.max(1, Math.ceil(filtered.length / trackingPageSize));
        currentPage = Math.min(currentPage, totalPages);
        const visibleRows = filtered.slice((currentPage - 1) * trackingPageSize, currentPage * trackingPageSize);
        if (!visibleRows.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary py-5">No faculty clearance records matching your filters.</td></tr>';
        } else {
            body.innerHTML = visibleRows.map(row => {
                try {
                    const c = row.clearance || { status: 'Not Submitted', progress: 0, approved_items: 0, total_items: 0 };
                    const expiry = row.contractual_end && row.contractual_end !== '0000-00-00'
                        ? new Date(`${row.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not set';
                    const tone = (c.status === 'Action Required' || c.status === 'With Deficiency') ? 'danger' : (c.status === 'Completed' || c.status === 'Cleared' ? 'success' : (c.status === 'Not Submitted' ? 'secondary' : (c.status === 'For Final Approval' || c.status === 'For Department Head Approval' ? 'warning' : 'info')));
                    const targetItem = (c.items || []).find(it => !officeRequirementName || it.name === officeRequirementName);
                    const isOfficeCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
                    const officeProgress = isOfficeCleared ? 100 : 0;
                    const officeApprovedCount = isOfficeCleared ? 1 : 0;
                    const officeTone = isOfficeCleared ? 'success' : (targetItem && (targetItem.status === 'Denied' || targetItem.status === 'Hold' || targetItem.status === 'With Deficiency') ? 'danger' : (targetItem && targetItem.status === 'On Hold' ? 'warning' : (targetItem && targetItem.file_name ? 'info' : 'secondary')));

                    return `<tr>
                    <td class="ps-3"><div class="fw-semibold text-body-emphasis">${escapeHtml(row.name || 'Unknown')}</div><small class="text-body-secondary">${escapeHtml(row.faculty_id || row.faculty_no || '')}</small></td>
                    <td>${escapeHtml(row.designated_department || 'N/A')}</td>
                    <td class="${row.days_remaining !== null && row.days_remaining <= 30 ? 'text-danger fw-bold' : ''}">${expiry}<small class="d-block text-body-secondary">${row.days_remaining === null ? '' : (row.days_remaining < 0 ? 'Expired' : row.days_remaining + ' days remaining')}</small></td>
                    <td style="min-width:150px"><div class="progress mb-1" style="height:7px"><div class="progress-bar bg-${officeTone}" style="width:${officeProgress}%"></div></div><small class="text-body-secondary">${officeProgress}% (${officeApprovedCount}/1)</small></td>
                    <td><span class="badge bg-${tone}-subtle text-${tone} border border-${tone}-subtle px-2 py-1">${escapeHtml(c.status || 'Not Submitted')}</span></td>
                    <td>${row.submitted_at ? new Date(row.submitted_at.replace(' ', 'T')).toLocaleDateString() : '—'}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="openReview(${row.id})" title="Review Clearance Details"><i class="fas fa-search me-1"></i>Review</button>
                    </td></tr>`;
                } catch (rowErr) {
                    console.error('Error rendering clearance row:', rowErr, row);
                    return `<tr><td colspan="7" class="text-center text-muted small py-2">Error displaying faculty record (ID: ${row.id})</td></tr>`;
                }
            }).join('');
        }
        renderPagination(totalPages, filtered.length);
    }

    function filterTracking() { renderTracking(); }

    function resetTrackingFilters() {
        const searchInput = document.getElementById('trackingSearch');
        const empFilter = document.getElementById('trackingEmpStatusFilter');
        if (searchInput) searchInput.value = '';
        if (empFilter) empFilter.value = 'all';
        activeStatusGroup = 'all';
        currentPage = 1;
        renderStatusControls();
        renderTracking();
    }

    function resetArchiveFilters() {
        const searchInput = document.getElementById('archiveSearch');
        const termFilter = document.getElementById('archiveTermFilter');
        const empFilter = document.getElementById('archiveEmpFilter');
        if (searchInput) searchInput.value = '';
        if (termFilter) termFilter.value = 'all';
        if (empFilter) empFilter.value = 'all';
        archiveCurrentPage = 1;
        renderArchives();
    }

    function renderPagination(totalPages, totalRows) {
        let pager = document.getElementById('trackingPagination');
        if (!pager) return;
        pager.className = 'd-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top';
        pager.innerHTML = `<small class="text-body-secondary">${totalRows ? `Page ${currentPage} of ${totalPages} · ${totalRows} active records` : 'No records'}</small><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" ${currentPage <= 1 ? 'disabled' : ''} onclick="changeTrackingPage(-1)"><i class="fas fa-chevron-left"></i></button><button class="btn btn-outline-secondary" ${currentPage >= totalPages ? 'disabled' : ''} onclick="changeTrackingPage(1)"><i class="fas fa-chevron-right"></i></button></div>`;
    }

    function changeTrackingPage(direction) { currentPage += direction; renderTracking(); }

    function populateArchiveTermFilter() {
        const select = document.getElementById('archiveTermFilter');
        if (!select) return;
        const terms = Array.from(new Set(archiveRows.map(r => `${r.academic_year} · ${r.semester}`)));
        select.innerHTML = '<option value="all">All Academic Terms</option>' + terms.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
    }

    function getFilteredArchives() {
        const query = (document.getElementById('archiveSearch')?.value || '').toLowerCase();
        const termFilter = document.getElementById('archiveTermFilter')?.value || 'all';
        const empFilter = document.getElementById('archiveEmpFilter')?.value || 'all';
        return archiveRows.filter(row => {
            const text = `${row.name} ${row.faculty_no} ${row.designated_department}`.toLowerCase();
            const termLabel = `${row.academic_year} · ${row.semester}`;
            const rowEmp = row.employment_status || '';
            return (!query || text.includes(query)) && (termFilter === 'all' || termLabel === termFilter) && (empFilter === 'all' || rowEmp.toLowerCase() === empFilter.toLowerCase());
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
                const expiry = row.contractual_end && row.contractual_end !== '0000-00-00' ? new Date(`${row.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not set';
                const clearedAt = row.updated_at ? new Date(row.updated_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
                let archiveListItems = row.items || [];
                if (officeRequirementName) {
                    archiveListItems = archiveListItems.filter(it => it.name === officeRequirementName);
                }
                const reqTags = archiveListItems.map(it => {
                    if (!it.file_name && it.status !== 'Cleared') return `<span class="badge bg-secondary-subtle text-body-secondary border me-1 mb-1 small"><i class="fas fa-times-circle me-1"></i>${escapeHtml(it.name)}: Missing</span>`;
                    if (it.status === 'Hold' || it.status === 'Denied') return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1 mb-1 small"><i class="fas fa-exclamation-circle me-1"></i>${escapeHtml(it.name)}: Denied</span>`;
                    return `<span class="badge bg-success-subtle text-success border border-success-subtle me-1 mb-1 small"><i class="fas fa-check-circle me-1"></i>${escapeHtml(it.name)}</span>`;
                }).join('');
                return `<tr>
                <td class="ps-3"><div class="fw-semibold text-body-emphasis">${escapeHtml(row.name || 'Unknown')}</div><small class="text-body-secondary d-block">${escapeHtml(row.faculty_no || '')}</small><small class="text-body-secondary">${escapeHtml(row.designated_department || '')}</small></td>
                <td><span class="badge bg-secondary-subtle text-body-secondary border">${escapeHtml(row.academic_year)} · ${escapeHtml(row.semester)}</span></td>
                <td><strong class="text-success">${expiry}</strong></td>
                <td style="max-width: 250px;">${reqTags || '<span class="text-body-secondary small">No requirements</span>'}</td>
                <td><small class="text-body-secondary">${clearedAt}</small></td>
                <td class="text-end pe-3"><button class="btn btn-sm btn-outline-success" onclick="openArchiveDetail(${row.clearance_id || 0}, ${row.archive_id || 0})"><i class="fas fa-folder-open me-1"></i>View Record</button></td></tr>`;
            }).join('');
        }
        renderArchivePagination(totalPages, filtered.length);
    }

    function filterArchives() { archiveCurrentPage = 1; renderArchives(); }

    function renderArchivePagination(totalPages, totalRows) {
        let pager = document.getElementById('archivePagination');
        if (!pager) return;
        pager.className = 'd-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top';
        pager.innerHTML = `<small class="text-body-secondary">${totalRows ? `Page ${archiveCurrentPage} of ${totalPages} · ${totalRows} completed records` : 'No records'}</small><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" ${archiveCurrentPage <= 1 ? 'disabled' : ''} onclick="changeArchivePage(-1)"><i class="fas fa-chevron-left"></i></button><button class="btn btn-outline-secondary" ${archiveCurrentPage >= totalPages ? 'disabled' : ''} onclick="changeArchivePage(1)"><i class="fas fa-chevron-right"></i></button></div>`;
    }

    function changeArchivePage(direction) { archiveCurrentPage += direction; renderArchives(); }

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
            document.getElementById('archiveContractEnd').textContent = r.contractual_end && r.contractual_end !== '0000-00-00' ? new Date(`${r.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not set';
            document.getElementById('archiveEmpStatus').textContent = r.employment_status || 'Regular';
            document.getElementById('archiveEmail').textContent = r.email || '—';
            const body = document.getElementById('archiveRequirementsBody');
            let archiveItems = r.items || [];
            if (officeRequirementName) {
                archiveItems = archiveItems.filter(it => it.name === officeRequirementName);
            }
            body.innerHTML = archiveItems.length ? archiveItems.map((it, idx) => {
                const isMissing = !it.file_name && it.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (it.status === 'Cleared' ? 'Approved / Cleared' : (it.status === 'Hold' ? 'Denied' : it.status));
                const badgeClass = isMissing ? 'bg-secondary-subtle text-body-secondary border' : (it.status === 'Cleared' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
                const fileUrl = it.file_path ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}` : `${clearanceApi}?action=file&download=1&item_id=${it.id || 0}`;
                return `<tr>
                <td>${idx + 1}</td>
                <td><strong class="text-body-emphasis">${escapeHtml(it.name)}</strong></td>
                <td>${it.file_name ? `<a class="btn btn-sm btn-outline-success" href="${fileUrl}" download="${escapeHtml(it.file_name)}"><i class="fas fa-download me-1"></i>Download (${escapeHtml(it.file_name)})</a>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-circle-xmark me-1"></i>No file (Missing)</span>'}</td>
                <td><span class="badge ${badgeClass}"><i class="${isMissing ? 'fas fa-question-circle' : (it.status === 'Cleared' ? 'fas fa-check-circle' : 'fas fa-times-circle')} me-1"></i>${escapeHtml(statusLabel)}</span></td>
                <td>${formatClearanceRemark(it.remarks, isMissing)}</td>
                <td><small class="text-body-secondary">${it.cleared_at ? new Date(it.cleared_at.replace(' ', 'T')).toLocaleDateString() : '—'}</small></td></tr>`;
            }).join('') : `<tr><td colspan="6" class="text-center text-body-secondary py-4">No ${escapeHtml(officeRequirementName || 'clearance')} record found.</td></tr>`;
            archiveDetailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('archiveDetailModal'));
            archiveDetailModal.show();
        } catch (error) { showTrackingAlert(error.message, 'danger'); }
    }

    function exportArchiveCsv() {
        const filtered = getFilteredArchives();
        if (!filtered.length) { alert('No archived clearance records available to export.'); return; }
        const headers = ['Clearance ID', 'Faculty Name', 'Faculty ID', 'Department', 'Employment Status', 'Academic Term', 'Contract Expiry Date', 'Date Completed'];
        const rows = filtered.map(r => [r.clearance_id, `"${(r.name || '').replace(/"/g, '""')}"`, `"${(r.faculty_no || '').replace(/"/g, '""')}"`, `"${(r.designated_department || '').replace(/"/g, '""')}"`, `"${r.employment_status || 'Probationary'}"`, `"${r.academic_year} · ${r.semester}"`, `"${r.contractual_end || ''}"`, `"${r.updated_at || ''}"`]);
        const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
        const link = document.createElement('a');
        link.setAttribute('href', encodeURI(csvContent));
        link.setAttribute('download', `faculty_clearance_archives_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }

    function printArchiveTable() { window.print(); }

    function printSingleArchive() {
        const printContents = document.getElementById('archivePrintArea').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`<html><head><title>Faculty Clearance Archive Record</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head><body class="p-4 bg-white text-dark">${printContents}</body></html>`);
        printWindow.document.close(); printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }

    async function openReview(facultyId) {
        currentReviewFacultyId = facultyId;
        currentReviewClearance = null;
        const alertBox = document.getElementById('reviewAlert');
        if (alertBox) alertBox.classList.add('d-none');
        try {
            const response = await fetch(`${clearanceApi}?action=review&faculty_id=${facultyId}`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            const profile = data.profile;
            const c = data.clearance;
            currentReviewProfile = profile;
            currentReviewClearance = c;
            document.getElementById('reviewTitle').innerHTML = `<i class="fas fa-clipboard-check me-2"></i>Review Clearance - ${escapeHtml(profile.first_name)} ${escapeHtml(profile.last_name)}`;
            document.getElementById('reviewMeta').textContent = `${profile.faculty_id || ''} · ${profile.designated_department || 'Department'}`;
            const expiry = profile.contractual_end && profile.contractual_end !== '0000-00-00' ? new Date(`${profile.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not set';
            const expiryEl = document.getElementById('summaryContractExpiry');
            if (expiryEl) expiryEl.textContent = expiry;
            const daysRemaining = profile.contractual_end && profile.contractual_end !== '0000-00-00' ? Math.floor((new Date(`${profile.contractual_end}T00:00:00`) - new Date()) / 86400000) : null;
            const daysEl = document.getElementById('summaryDaysRemaining');
            if (daysEl) {
                daysEl.textContent = daysRemaining === null ? 'No expiration date' : (daysRemaining < 0 ? 'Contract Expired' : `${daysRemaining} days remaining`);
                daysEl.className = `small d-block ${daysRemaining !== null && daysRemaining <= 30 ? 'text-danger fw-bold' : 'text-body-secondary'}`;
            }
            const empStatus = profile.employment_status || 'Probationary';
            const empEl = document.getElementById('summaryEmpStatus');
            if (empEl) { empEl.textContent = empStatus; empEl.className = `badge rounded-pill ${empStatus === 'Regular' ? 'bg-success-subtle text-success border border-success-subtle' : (empStatus === 'Probationary' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-body-secondary border')} px-3 py-1 fw-semibold`; }
            const targetItem = (c.items || []).find(it => !officeRequirementName || it.name === officeRequirementName);
            const isOfficeCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
            const officeProgress = isOfficeCleared ? 100 : 0;
            const officeApprovedCount = isOfficeCleared ? 1 : 0;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${officeProgress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${officeProgress}% (${officeApprovedCount}/1)`;

            // Items table – filter by this office's requirement if applicable
            const body = document.getElementById('reviewBody');
            let visibleItems = c.items || [];
            if (officeRequirementName) {
                visibleItems = visibleItems.filter(item => item.name === officeRequirementName);
            }
            body.innerHTML = visibleItems.length ? visibleItems.map(item => {
                const isMissing = !item.file_name && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const isApproved = item.status === 'Cleared' || item.status === 'Approved';
                const isRejected = item.status === 'Denied' || item.status === 'Hold' || item.status === 'With Deficiency';
                const isPending = !isApproved && !isRejected && !isMissing;

                let verificationStatus = 'pending';
                let verificationIcon = 'fa-circle text-body-tertiary';
                let verificationClass = 'bg-body-tertiary';

                if (isApproved) {
                    verificationStatus = 'approved';
                    verificationIcon = 'fa-check-circle text-success';
                    verificationClass = 'bg-success-subtle border-success';
                } else if (isRejected) {
                    verificationStatus = 'rejected';
                    verificationIcon = 'fa-times-circle text-danger';
                    verificationClass = 'bg-danger-subtle border-danger';
                }

                return `<tr class="verification-row ${verificationClass} border ${item.file_name ? '' : 'opacity-50'}" 
                           data-item-id="${item.id}" 
                           data-item-name="${escapeHtml(item.name)}"
                           data-current-status="${verificationStatus}">
                <td class="verification-cell">
                    <div class="d-flex align-items-center gap-2">
                        <div class="verification-icon">
                            <i class="fas ${verificationIcon} fs-5"></i>
                        </div>
                        <strong class="text-body-emphasis">${escapeHtml(item.name)}</strong>
                    </div>
                </td>
                <td>${item.file_name ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${clearanceApi}?action=file&item_id=${item.id}" onclick="event.stopPropagation()"><i class="fas fa-eye me-1"></i>View file</a><small class="d-block text-body-secondary mt-1">${escapeHtml(item.file_name)}</small>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-excel me-1"></i>No file uploaded (Missing)</span>'}</td>
                <td><span class="badge ${isApproved ? 'bg-success-subtle text-success border border-success-subtle' : (isRejected ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-body-secondary border')} px-2 py-1">${escapeHtml(statusLabel)}</span></td>
                <td>${formatClearanceRemark(item.remarks, isMissing)}</td>
                <td class="text-end"><div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="reviewItem(${item.id}, 'approve')" ${item.file_name ? '' : 'disabled'} title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger" onclick="reviewItem(${item.id}, 'deny', '${escapeHtml(item.name)}')" ${item.file_name ? '' : 'disabled'} title="Deny"><i class="fas fa-times"></i></button>
                    <button class="btn btn-warning text-dark" onclick="reviewItem(${item.id}, 'hold')" ${item.file_name ? '' : 'disabled'} title="Put On Hold"><i class="fas fa-pause"></i></button>
                </div></td></tr>`;
            }).join('') : `<tr><td colspan="5" class="text-center text-body-secondary py-4">No ${escapeHtml(officeRequirementName || 'clearance')} submitted.</td></tr>`;

            reviewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal'));
            reviewModal.show();
        } catch (error) { showTrackingAlert(error.message, 'danger'); }
    }

    let _remarkResolve = null, _remarkModal = null;

    function openRemarkModal({ title, sub, label, hint, placeholder, defaultValue = '', required = true, iconClass, headerClass, btnClass, btnIcon, btnText }) {
        return new Promise((resolve) => {
            _remarkResolve = resolve;
            document.getElementById('remarkModalHeader').className = `modal-header py-3 border-bottom ${headerClass || ''}`;
            const iconEl = document.getElementById('remarkModalIcon');
            iconEl.className = `rounded-circle d-flex align-items-center justify-content-center ${iconClass || ''}`;
            iconEl.innerHTML = `<i class="fas ${btnIcon || 'fa-pen'}"></i>`;
            document.getElementById('remarkModalTitle').textContent = title || 'Add Remark';
            document.getElementById('remarkModalSub').textContent = sub || '';
            document.getElementById('remarkModalLabel').textContent = label || 'Remark';
            document.getElementById('remarkModalHint').textContent = hint || '';
            const input = document.getElementById('remarkModalInput');
            input.value = defaultValue; input.placeholder = placeholder || 'Enter remark...'; input.classList.remove('is-invalid');
            const confirm = document.getElementById('remarkModalConfirm');
            confirm.className = `btn flex-fill fw-semibold ${btnClass || 'btn-primary'}`;
            document.getElementById('remarkModalConfirmIcon').className = `fas ${btnIcon || 'fa-check'} me-1`;
            document.getElementById('remarkModalConfirmText').textContent = btnText || 'Confirm';
            document.getElementById('remarkModalCancel').onclick = () => { _remarkModal.hide(); resolve(null); };
            confirm.onclick = () => { const val = input.value.trim(); if (required && !val) { input.classList.add('is-invalid'); input.focus(); return; } _remarkModal.hide(); resolve(val || defaultValue); };
            input.onkeydown = (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); confirm.click(); } };
            _remarkModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('remarkModal'));
            _remarkModal.show();
            setTimeout(() => { const backdrops = document.querySelectorAll('.modal-backdrop'); if (backdrops.length >= 2) backdrops[backdrops.length - 1].style.zIndex = '1070'; input.focus(); }, 50);
        });
    }

    async function reviewItem(itemId, decision, reqName = '') {
        const isApprove = decision === 'approve';
        const isDeny = decision === 'deny';
        let remark;
        if (isApprove) {
            remark = await openRemarkModal({ title: 'Approve Requirement', sub: 'You are approving this clearance submission.', label: 'Remark (Optional)', hint: 'Leave blank to use the default approval message.', placeholder: 'Requirement approved.', defaultValue: 'Requirement approved.', required: false, headerClass: 'bg-success-subtle', iconClass: 'bg-success text-white', btnClass: 'btn-success', btnIcon: 'fa-check', btnText: 'Approve' });
        } else if (isDeny) {
            remark = await openRemarkModal({
                title: 'Deny Requirement',
                sub: 'Provide a reason for denying this requirement submission.',
                label: 'Denial Reason (Required)',
                hint: 'Explain what needs to be addressed before this requirement can be approved.',
                placeholder: 'e.g., Incomplete documentation or missing official stamp.',
                defaultValue: '',
                required: true,
                headerClass: 'bg-danger-subtle',
                iconClass: 'bg-danger text-white',
                btnClass: 'btn-danger',
                btnIcon: 'fa-times',
                btnText: 'Deny'
            });
        } else {
            remark = await openRemarkModal({ title: 'Place On Hold', sub: 'The requirement will be flagged for further review.', label: 'Hold Reason (Required)', hint: 'State what needs to be addressed before approval.', placeholder: 'e.g., Pending additional verification.', defaultValue: '', required: true, headerClass: 'bg-warning-subtle', iconClass: 'bg-warning text-dark', btnClass: 'btn-warning text-dark', btnIcon: 'fa-pause', btnText: 'Put On Hold' });
        }
        if (remark === null) return;
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
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) { alertBox.className = 'alert alert-success'; alertBox.innerHTML = `<i class="fas fa-check-circle me-2"></i>${escapeHtml(data.message)}`; alertBox.classList.remove('d-none'); }
            if (currentReviewFacultyId) await refreshReviewItems(currentReviewFacultyId);
            loadTracking(); loadArchives();
        } catch (error) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) { alertBox.className = 'alert alert-danger'; alertBox.textContent = error.message; alertBox.classList.remove('d-none'); }
        } finally { btns.forEach(b => { b.disabled = false; }); }
    }

    async function refreshReviewItems(facultyId) {
        try {
            const response = await fetch(`${clearanceApi}?action=review&faculty_id=${facultyId}`);
            const data = await response.json();
            if (!data.ok) return;
            const c = data.clearance;
            const body = document.getElementById('reviewBody');
            if (!body) return;
            const targetItem = (c.items || []).find(it => !officeRequirementName || it.name === officeRequirementName);
            const isOfficeCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
            const officeProgress = isOfficeCleared ? 100 : 0;
            const officeApprovedCount = isOfficeCleared ? 1 : 0;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${officeProgress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${officeProgress}% (${officeApprovedCount}/1)`;
            let refreshItems = c.items || [];
            if (officeRequirementName) {
                refreshItems = refreshItems.filter(item => item.name === officeRequirementName);
            }
            body.innerHTML = refreshItems.length ? refreshItems.map(item => {
                const isMissing = !item.file_name && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const badgeClass = isMissing ? 'bg-secondary-subtle text-body-secondary border' : (item.status === 'Cleared' ? 'bg-success-subtle text-success border border-success-subtle' : (item.status === 'Denied' || item.status === 'Hold' ? 'bg-danger-subtle text-danger border border-danger-subtle' : (item.status === 'On Hold' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle')));
                return `<tr>
                <td><strong class="text-body-emphasis">${escapeHtml(item.name)}</strong></td>
                <td>${item.file_name ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${clearanceApi}?action=file&item_id=${item.id}"><i class="fas fa-eye me-1"></i>View file</a><small class="d-block text-body-secondary mt-1">${escapeHtml(item.file_name)}</small>` : '<span class="badge bg-secondary-subtle text-body-secondary border"><i class="fas fa-file-excel me-1"></i>No file uploaded (Missing)</span>'}</td>
                <td><span class="badge ${badgeClass} px-2 py-1">${escapeHtml(statusLabel)}</span></td>
                <td>${formatClearanceRemark(item.remarks, isMissing)}</td>
                <td class="text-end"><div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="reviewItem(${item.id}, 'approve', '${escapeHtml(item.name)}')" ${item.file_name ? '' : 'disabled'} title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger" onclick="reviewItem(${item.id}, 'deny', '${escapeHtml(item.name)}')" ${item.file_name ? '' : 'disabled'} title="Deny"><i class="fas fa-times"></i></button>
                    <button class="btn btn-warning text-dark" onclick="reviewItem(${item.id}, 'hold', '${escapeHtml(item.name)}')" ${item.file_name ? '' : 'disabled'} title="Put On Hold"><i class="fas fa-pause"></i></button>
                </div></td></tr>`;
            }).join('') : `<tr><td colspan="5" class="text-center text-body-secondary py-4">No ${escapeHtml(officeRequirementName || 'clearance')} submitted.</td></tr>`;
        } catch (e) { /* silent */ }
    }

    function formatClearanceRemark(remarks, isMissing = false) {
        if (!remarks || !String(remarks).trim()) {
            return `<small class="text-body-secondary fst-italic">${isMissing ? 'No file submitted' : 'No remark'}</small>`;
        }

        let raw = String(remarks).trim();
        // Clean out comment tag, bracketed status, and any legacy scope markers
        raw = raw.replace(/<!--SCOPE_STATE:.*?-->/g, '').trim();
            raw = raw.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '').trim();
        raw = raw.replace(/Deficiencies Flagged:[\s\S]*?(?=Instructions:|$)/i, '')
            .replace(/Complied:[\s\S]*?(?=Instructions:|$)/i, '')
            .replace(/^Instructions:\s*/i, '')
            .trim();

        if (!raw) {
            return `<small class="text-body-secondary fst-italic">${isMissing ? 'No file submitted' : 'No remark'}</small>`;
        }

        return `<div class="text-body-secondary small text-start" style="white-space: pre-line; font-size:0.8rem;">${escapeHtml(raw)}</div>`;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c]));
    }

    function showTrackingAlert(message, tone) {
        const alert = document.getElementById('trackingAlert');
        if (!alert) return;
        alert.className = `alert alert-${tone} alert-dismissible fade show mb-4`;
        alert.innerHTML = `<i class="fas fa-${tone === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    }

    // Initial load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            loadTracking();
            loadArchives();
        });
    } else {
        loadTracking();
        loadArchives();
    }
</script>