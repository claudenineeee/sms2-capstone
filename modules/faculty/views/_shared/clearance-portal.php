<?php
/**
 * _shared/clearance-portal.php
 *
 * Shared Faculty Clearance Portal UI for all clearance office roles
 * (HR, Finance, Registrar, Property, Library).
 * Uses the complete, rich Department Head Review Clearance UI template,
 * parameterized dynamically per office.
 */
declare(strict_types=1);

$officeLabel = $officeLabel ?? 'Clearance Office';
$officeIcon = $officeIcon ?? 'fa-clipboard-check';
$officeColor = $officeColor ?? 'primary';
$targetRequirementName = $targetRequirementName ?? null;

$currentRole = $currentRole ?? getCurrentUserRoleKey();
$isDeptHeadRole = in_array($currentRole, ['department_head', 'dept_head'], true);

if (!$targetRequirementName) {
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

require_once ROOT_PATH . '/modules/faculty/controllers/clearance.php';
$sections = function_exists('facultyClearanceSections') ? facultyClearanceSections() : [];
$scopeItems = $scopeItems ?? ($sections[$targetRequirementName]['items'] ?? [
    'Submitted documents verified',
    'Office requirements and records checked',
    'Accountabilities and obligations cleared'
]);
?>
<?php renderBreadcrumbs($breadcrumbs); ?>
<style>
    /* Status Filter Chips (Light Mode) */
    .clr-status-chip {
        background-color: #f0f2f5;
        color: #4b5563;
        border: 1px solid #e2e6ea;
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
    }

    .clr-status-chip:hover {
        background-color: #e4e7eb;
        color: #1f2937;
    }

    .clr-status-chip.active {
        background-color: #1e2533;
        color: #ffffff;
        border-color: #1e2533;
    }

    .clr-status-chip .clr-chip-count {
        border-radius: 20px;
        padding: 1px 8px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
        transition: all 0.15s ease-in-out;
    }

    .clr-status-chip.active .clr-chip-count {
        background-color: rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    .clr-status-chip:not(.active) .badge-dark {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .clr-status-chip:not(.active) .badge-primary {
        background-color: #dbeafe;
        color: #1d4ed8;
    }

    .clr-status-chip:not(.active) .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .clr-status-chip:not(.active) .badge-success {
        background-color: #dcfce7;
        color: #15803d;
    }

    .clr-status-chip:not(.active) .badge-secondary {
        background-color: #e5e7eb;
        color: #6b7280;
    }

    /* Status Filter Chips (Dark Mode) */
    [data-theme="dark"] .clr-status-chip,
    [data-bs-theme="dark"] .clr-status-chip {
        background-color: rgba(255, 255, 255, 0.06);
        color: #cbd5e1;
        border-color: rgba(255, 255, 255, 0.12);
    }

    [data-theme="dark"] .clr-status-chip:hover,
    [data-bs-theme="dark"] .clr-status-chip:hover {
        background-color: rgba(255, 255, 255, 0.12);
        color: #f8fafc;
        border-color: rgba(255, 255, 255, 0.22);
    }

    [data-theme="dark"] .clr-status-chip.active,
    [data-bs-theme="dark"] .clr-status-chip.active {
        background-color: #2563eb;
        color: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 0 12px rgba(37, 99, 235, 0.35);
    }

    [data-theme="dark"] .clr-status-chip.active .clr-chip-count,
    [data-bs-theme="dark"] .clr-status-chip.active .clr-chip-count {
        background-color: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-dark,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-dark {
        background-color: rgba(255, 255, 255, 0.12);
        color: #cbd5e1;
    }

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-primary,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-primary {
        background-color: rgba(59, 130, 246, 0.22);
        color: #93c5fd;
    }

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-danger,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-danger {
        background-color: rgba(239, 68, 68, 0.22);
        color: #fca5a5;
    }

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-success,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-success {
        background-color: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-secondary,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-secondary {
        background-color: rgba(148, 163, 184, 0.2);
        color: #cbd5e1;
    }

    /* Scope of Verification Checklist in Review Modal */
    .scope-verification-box {
        min-width: 290px;
    }

    .scope-item {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
        border: 1px solid var(--bs-border-color-translucent, #e9ecef);
        background-color: var(--bs-body-bg, #ffffff);
    }

    .scope-item:hover {
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-color: var(--bs-primary, #0d6efd);
    }

    .scope-item:active {
        transform: scale(0.985);
    }

    .scope-item.is-passed {
        background-color: rgba(25, 135, 84, 0.08) !important;
        border-color: rgba(25, 135, 84, 0.3) !important;
    }

    .scope-item.is-failed {
        background-color: rgba(220, 53, 69, 0.08) !important;
        border-color: rgba(220, 53, 69, 0.3) !important;
    }

    /* Clearance Button Group (View / Download) */
    .clearance-btn-group {
        border: 1px solid var(--bs-border-color, #dee2e6);
        background-color: var(--bs-body-bg, #ffffff);
    }

    .clearance-btn-group .btn {
        background: transparent;
        color: var(--bs-body-color, #212529);
        font-size: 0.82rem;
        font-weight: 500;
        transition: background 0.15s, color 0.15s;
    }

    .clearance-btn-group .btn:hover {
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        color: var(--bs-primary, #0d6efd);
    }

    .clearance-btn-group .btn:first-child {
        border-right: 1px solid var(--bs-border-color, #dee2e6) !important;
    }

    [data-theme="dark"] .clearance-btn-group,
    [data-bs-theme="dark"] .clearance-btn-group {
        border-color: rgba(255, 255, 255, 0.15);
        background-color: rgba(255, 255, 255, 0.05);
    }

    [data-theme="dark"] .clearance-btn-group .btn,
    [data-bs-theme="dark"] .clearance-btn-group .btn {
        color: #e9ecef;
    }

    [data-theme="dark"] .clearance-btn-group .btn:hover,
    [data-bs-theme="dark"] .clearance-btn-group .btn:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    [data-theme="dark"] .clearance-btn-group .btn:first-child,
    [data-bs-theme="dark"] .clearance-btn-group .btn:first-child {
        border-right-color: rgba(255, 255, 255, 0.15) !important;
    }

    /* Stepper Styling */
    .horizontal-clearance-stepper .stepper-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .horizontal-clearance-stepper .stepper-divider {
        height: 2px;
        background: var(--bs-border-color);
        transition: background-color 0.2s ease;
    }

    [data-theme="dark"] .horizontal-clearance-stepper,
    [data-bs-theme="dark"] .horizontal-clearance-stepper {
        background-color: rgba(255, 255, 255, 0.04) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    #remarkModal,
    #confirmDhVerifyModal {
        z-index: 1085 !important;
    }

    /* Office Signature Pad (Light & Dark Mode) */
    .office-sign-pad-wrap {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
    }
    #officeSignCanvas {
        background: #f8fafc !important;
    }
    [data-theme="dark"] .office-sign-pad-wrap,
    [data-bs-theme="dark"] .office-sign-pad-wrap {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] #officeSignCanvas,
    [data-bs-theme="dark"] #officeSignCanvas {
        background: #1e293b !important;
    }
</style>

<div class="container-fluid p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-<?= $officeColor ?> text-uppercase small fw-bold mb-1"><?= htmlspecialchars($officeLabel) ?>
            </p>
            <h3 class="fw-bold text-body-emphasis mb-1"><i
                    class="fas <?= htmlspecialchars($officeIcon) ?> text-<?= $officeColor ?> me-2"></i>Faculty Clearance
                Portal</h3>
            <p class="text-body-secondary small mb-0">Track ongoing clearance submissions, review requirements, and
                inspect archived completed records.</p>
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
                    class="badge bg-<?= $officeColor ?> ms-1" id="activeBadgeCount">0</span>
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
                            <span class="d-none d-sm-inline"></span>
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
                        <div class="col-12 col-sm-auto text-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                onclick="resetArchiveFilters()" title="Reset Filters & Search">
                                <i class="fas fa-rotate-left me-1"></i>Reset
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
                                <th>Contract Expiration</th>
                                <th>Final Clearance</th>
                                <th>Archived Date</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="archiveBody" class="text-body">
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-5">Loading archived records...
                                </td>
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
<div class="modal fade" id="remarkModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
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

<!-- REVIEW MODAL FOR ACTIVE CLEARANCE (DEPARTMENT HEAD RICH UI) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1240px;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header / Navy Topbar -->
            <div class="modal-header py-3 px-4 text-white"
                style="background: linear-gradient(135deg, #0b345f 0%, #0d2847 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-halved fs-5 text-white"></i>
                    <h5 class="modal-title fw-bold text-white mb-0" style="letter-spacing: -0.01em;">Employee Clearance
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div
                        class="d-none d-sm-flex align-items-center gap-2 text-white-50 small bg-white bg-opacity-10 px-3 py-1 rounded-pill">
                        <i class="fas fa-circle-user text-white"></i>
                        <span class="text-white fw-medium"><?= htmlspecialchars($officeLabel) ?></span>
                    </div>
                    <button class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Top Section: Title & Top Summary Stats Card -->
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold text-body-emphasis mb-1" id="reviewMainTitle">Probationary Employee Clearance
                        </h4>
                        <p class="text-body-secondary small mb-0" id="reviewMainSubtitle">Complete all clearance
                            requirements and submit your documents for review.</p>
                        <!-- Preserved DOM IDs for JS backward compatibility -->
                        <span class="d-none" id="reviewTitle"></span>
                        <span class="d-none" id="reviewMeta"></span>
                    </div>
                    <!-- Stats Card (3 Segments) -->
                    <div class="card bg-body border rounded-3 shadow-none p-3" style="min-width: 360px;">
                        <div class="row g-3 align-items-center text-body">
                            <div class="col-12 col-sm-5 border-end-sm border-body-subtle pe-sm-3">
                                <div class="d-flex align-items-center gap-2.5">
                                    <i class="fas fa-calendar-days text-<?= $officeColor ?> fs-3 opacity-75"></i>
                                    <div>
                                        <small class="text-body-secondary d-block" style="font-size:0.73rem;">Current
                                            Contract Expiry</small>
                                        <span class="fw-bold text-body-emphasis small d-block"
                                            id="summaryContractExpiry">—</span>
                                        <small class="d-block" id="summaryDaysRemaining"
                                            style="font-size:0.7rem;"></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-3 border-end-sm border-body-subtle px-sm-2">
                                <small class="text-body-secondary d-block mb-1" style="font-size:0.73rem;">Employment
                                    Status</small>
                                <span
                                    class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 rounded-pill fw-semibold"
                                    id="summaryEmpStatus" style="font-size:0.75rem;">Probationary</span>
                            </div>
                            <div class="col-12 col-sm-4 ps-sm-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-body-secondary" style="font-size:0.73rem;">Clearance
                                        Progress</small>
                                    <span class="small fw-bold text-body-emphasis" id="summaryProgressText"
                                        style="font-size:0.73rem;">0%</span>
                                </div>
                                <div class="progress mb-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" id="summaryProgressBar" style="width: 0%">
                                    </div>
                                </div>
                                <small class="text-body-secondary d-block" id="summaryProgressSub"
                                    style="font-size:0.7rem;">All requirements completed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Stepper (3 Steps) -->
                <div class="horizontal-clearance-stepper mb-4 p-3 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between overflow-x-auto gap-2">
                        <!-- Step 1 -->
                        <div class="d-flex align-items-center gap-2 flex-shrink-0" id="stepperStep1">
                            <div class="stepper-circle bg-success text-white" id="stepperStep1Circle"><i
                                    class="fas fa-check"></i></div>
                            <div>
                                <div class="fw-bold small text-body-emphasis" style="font-size:0.8rem;"
                                    id="stepperStep1Title">Requirements Submission</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;" id="stepperStep1Date">
                                    Completed</div>
                            </div>
                        </div>
                        <div class="stepper-divider flex-grow-1 mx-2" id="stepperLine1"
                            style="min-width:24px;background:#198754;"></div>
                        <!-- Step 2 -->
                        <div class="d-flex align-items-center gap-2 flex-shrink-0" id="stepperStep2">
                            <div class="stepper-circle bg-success text-white" id="stepperStep2Circle"><i
                                    class="fas fa-check"></i></div>
                            <div>
                                <div class="fw-bold small text-body-emphasis" style="font-size:0.8rem;"
                                    id="stepperStep2Title">Requirement Review</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;" id="stepperStep2Sub">
                                    Completed</div>
                            </div>
                        </div>
                        <div class="stepper-divider flex-grow-1 mx-2" id="stepperLine2" style="min-width:24px;"></div>
                        <!-- Step 3 -->
                        <div class="d-flex align-items-center gap-2 flex-shrink-0" id="stepperStep4">
                            <div class="stepper-circle bg-body-secondary text-body-secondary" id="stepperStep4Circle">3
                            </div>
                            <div>
                                <div class="fw-semibold small text-body-secondary" style="font-size:0.8rem;"
                                    id="stepperStep4Title">Completed</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;" id="stepperStep4Sub">Not yet
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Full-Width Grid -->
                <div class="row g-4">
                    <div class="col-12">
                        <!-- Success Status Banner -->
                        <div id="reviewSuccessBanner"
                            class="alert alert-success d-flex align-items-center gap-3 p-3 rounded-3 border border-success-subtle mb-4">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-check"></i></div>
                            <span class="fw-semibold small text-success-emphasis" id="reviewSuccessBannerText">All
                                requirements have been cleared.</span>
                        </div>

                        <!-- System alerts (for inline notifications) -->
                        <div id="reviewAlert" class="alert d-none mb-3"></div>

                        <!-- Clearance Form Status & Review Card -->
                        <div class="card border rounded-3 mb-4 shadow-sm" id="agreementFormReviewCard">
                            <div
                                class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="fw-bold small text-uppercase"><i
                                        class="fas fa-file-contract text-<?= $officeColor ?> me-2"></i>Clearance
                                    Form</span>
                                <span id="agreementFormStatusBadge"
                                    class="badge bg-secondary-subtle text-body-secondary border">Not Submitted</span>
                            </div>
                            <div class="card-body p-3" id="agreementFormReviewBody">
                                <!-- Loaded dynamically in openReview() -->
                            </div>
                        </div>

                        <!-- Clearance Requirements Section -->
                        <div class="card border-0 rounded-4 mb-4 shadow-sm overflow-hidden">
                            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between"
                                style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-3 bg-<?= $officeColor ?> text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width:32px;height:32px;font-size:0.9rem;">
                                        <i class="fas <?= htmlspecialchars($officeIcon) ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-body-emphasis" style="font-size:0.9rem;">Clearance
                                            Requirements</h6>
                                        <small class="text-body-secondary" style="font-size:0.72rem;">Review submitted
                                            documents and verify scope items</small>
                                    </div>
                                </div>
                            </div>
                            <div class="p-0">
                                <table class="table table-borderless align-top mb-0" style="table-layout:fixed;">
                                    <colgroup>
                                        <col style="width:220px;">
                                        <col style="width:220px;">
                                        <col style="width:160px;">
                                        <col>
                                    </colgroup>
                                    <thead
                                        style="background:rgba(var(--bs-primary-rgb),0.04);border-bottom:2px solid var(--bs-border-color);">
                                        <tr>
                                            <th class="ps-4 py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;"><i
                                                    class="fas fa-folder-open me-1 text-<?= $officeColor ?> opacity-75"></i>Requirement
                                            </th>
                                            <th class="py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;"><i
                                                    class="fas fa-paperclip me-1 text-<?= $officeColor ?> opacity-75"></i>File
                                                Attachment</th>
                                            <th class="py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;"><i
                                                    class="fas fa-circle-dot me-1 text-<?= $officeColor ?> opacity-75"></i>Status
                                            </th>
                                            <th class="pe-4 py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;min-width:290px;"><i
                                                    class="fas fa-clipboard-check me-1 text-<?= $officeColor ?> opacity-75"></i>Scope
                                                of Verification</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reviewBody" class="text-body"></tbody>
                                </table>
                            </div>
                        </div>


                        <!-- Transaction Summary Card -->
                        <div class="card border rounded-3 shadow-sm mb-4">
                            <div
                                class="card-header bg-body-tertiary py-2.5 px-3 border-bottom d-flex align-items-center gap-2">
                                <i class="fas fa-receipt text-<?= $officeColor ?>"></i>
                                <h6 class="fw-bold mb-0 text-body-emphasis small text-uppercase">Transaction Summary
                                </h6>
                            </div>
                            <div class="card-body py-2.5 px-3">
                                <div class="row g-3 align-items-center">
                                    <div class="col-6 col-md-auto pe-md-4 border-end">
                                        <small class="text-body-secondary d-block"
                                            style="font-size:0.72rem;">Transaction ID</small>
                                        <span class="fw-bold text-body-emphasis small" id="reviewTxnId">—</span>
                                    </div>
                                    <div class="col-6 col-md-auto pe-md-4 border-end">
                                        <small class="text-body-secondary d-block"
                                            style="font-size:0.72rem;">Employee</small>
                                        <span class="fw-bold text-body-emphasis small" id="reviewTxnEmployee">—</span>
                                    </div>
                                    <div class="col-6 col-md-auto pe-md-4 border-end">
                                        <small class="text-body-secondary d-block"
                                            style="font-size:0.72rem;">Position</small>
                                        <span class="text-body-emphasis small" id="reviewTxnPosition">—</span>
                                    </div>
                                    <div class="col-6 col-md-auto pe-md-4 border-end">
                                        <small class="text-body-secondary d-block" style="font-size:0.72rem;">Submitted
                                            On</small>
                                        <span class="text-body-emphasis small" id="reviewTxnSubmitted">—</span>
                                    </div>
                                    <div class="col-6 col-md-auto">
                                        <small class="text-body-secondary d-block" style="font-size:0.72rem;">Last
                                            Updated</small>
                                        <span class="text-body-emphasis small" id="reviewTxnUpdated">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="modal-footer bg-body-tertiary border-top d-flex justify-content-between align-items-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- CONFIRM SCOPE LOCK MODAL -->
<div class="modal fade" id="confirmScopeLockModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false" style="z-index:1080">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 bg-success-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:38px;height:38px;font-size:1.1rem;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-success-emphasis">Confirm &amp; Lock Scope</h6>
                        <small class="text-body-secondary">This action is permanent and cannot be undone.</small>
                    </div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" id="confirmScopeLockItemId" value="">
                <div
                    class="p-3 bg-warning-subtle border border-warning-subtle rounded-3 mb-3 d-flex gap-2 align-items-start">
                    <i class="fas fa-triangle-exclamation text-warning mt-0.5 flex-shrink-0"></i>
                    <div class="small text-warning-emphasis">
                        <strong>Are you sure?</strong> Once locked, the Scope of Verification checklist for this
                        requirement cannot be edited. Make sure all items are correctly marked before confirming.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top gap-2 flex-nowrap">
                <button type="button" class="btn btn-outline-secondary flex-fill"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success flex-fill fw-semibold" id="btnConfirmScopeLock"
                    onclick="executeScopeLock()">
                    <i class="fas fa-lock me-1"></i>Confirm &amp; Lock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DIGITAL OFFICE SIGNATURE MODAL -->
<div class="modal fade" id="officeSignatureModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false" style="z-index:1085">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 bg-primary-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:38px;height:38px;font-size:1.1rem;">
                        <i class="fas fa-signature"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-primary-emphasis">Office Digital Signature &amp;
                            Approval</h6>
                        <small class="text-body-secondary">Authorize and clear this requirement for the faculty</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" id="officeSignItemId" value="">

                <div
                    class="d-flex align-items-center justify-content-between p-2.5 rounded-3 bg-body-tertiary border mb-3">
                    <div>
                        <small class="text-muted d-block" style="font-size:0.7rem;">OFFICE REPRESENTATIVE</small>
                        <strong class="text-body-emphasis small"
                            id="officeSignApproverName"><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Authorized Officer') ?></strong>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block" style="font-size:0.7rem;">CLEARANCE STAGE</small>
                        <span class="badge bg-primary text-white"
                            id="officeSignOfficeLabel"><?= htmlspecialchars($officeLabel ?? 'Office Clearance') ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small fw-semibold mb-0">
                            <i class="fas fa-pen-fancy me-1 text-primary"></i>Draw Digital Signature <span
                                class="text-danger">*</span>
                        </label>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                            style="font-size:0.72rem;" onclick="clearOfficeSignature()">
                            <i class="fas fa-eraser me-1"></i>Clear
                        </button>
                    </div>
                    <div class="border rounded-3 p-1 position-relative office-sign-pad-wrap"
                        style="box-shadow:inset 0 1px 3px rgba(0,0,0,0.06);">
                        <canvas id="officeSignCanvas" width="480" height="150" class="w-100"
                            style="touch-action:none;cursor:crosshair;display:block;border-radius:4px;"></canvas>
                        <div id="officeSignPlaceholder"
                            class="position-absolute top-50 start-50 translate-middle text-muted small opacity-50"
                            style="user-select:none;pointer-events:none;">
                            <i class="fas fa-signature me-1"></i>Sign here using mouse or touch
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block" style="font-size:0.7rem;">
                        <i class="fas fa-shield-alt text-success me-1"></i>Your signature will be stored and permanently
                        displayed on the faculty member's clearance records.
                    </small>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1" for="officeSignRemarks">
                        Approval Remarks / Notes <small class="text-muted fw-normal">(Optional)</small>
                    </label>
                    <input type="text" class="form-control form-control-sm" id="officeSignRemarks"
                        placeholder="Approved and verified.">
                </div>

                <div id="officeSignAlert" class="alert alert-danger py-2 px-3 small d-none mb-0"></div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top gap-2 flex-nowrap">
                <button type="button" class="btn btn-outline-secondary flex-fill"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success flex-fill fw-semibold" id="btnSubmitOfficeSignature"
                    onclick="submitOfficeSignature()">
                    <i class="fas fa-check-circle me-1"></i>Sign &amp; Approve Clearance
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CONFIRM ARCHIVE MODAL -->
<div class="modal fade" id="confirmArchiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning-subtle py-3">
                <h6 class="modal-title fw-bold text-body-emphasis mb-0">
                    <i class="fas fa-archive text-warning me-2"></i>Archive Clearance Record
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width: 56px; height: 56px;">
                    <i class="fas fa-archive fs-3"></i>
                </div>
                <h6 class="fw-bold text-body-emphasis mb-2">Are you sure you want to archive this clearance record?</h6>
                <p class="text-body-secondary small mb-3">
                    Faculty: <strong class="text-body-emphasis" id="archiveTargetFacultyName">-</strong>
                </p>
                <div class="alert alert-info border border-info-subtle py-2 px-3 small text-start mb-0">
                    <i class="fas fa-info-circle me-1"></i> Archiving saves a permanent record snapshot in the
                    <strong>Archived Completed Records</strong> tab.
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning fw-semibold px-4" id="btnExecuteArchive"
                    onclick="executeArchiveClearance()">
                    <i class="fas fa-archive me-1"></i> Yes, Archive Record
                </button>
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
                        <div class="small text-body-secondary mt-1" id="archiveModalTerm">Academic Term: -</div>
                    </div>
                    <div class="text-md-end">
                        <small class="text-body-secondary d-block">Completion Timestamp</small>
                        <strong class="text-body-emphasis" id="archiveModalCompletedAt">-</strong>
                    </div>
                </div>

                <!-- Faculty Meta Information Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <div class="p-2.5 bg-body-tertiary rounded-3 border">
                            <small class="text-body-secondary d-block" style="font-size:0.75rem;">Faculty ID</small>
                            <span class="fw-bold text-body-emphasis small" id="archiveFacultyNo">-</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="p-2.5 bg-body-tertiary rounded-3 border">
                            <small class="text-body-secondary d-block" style="font-size:0.75rem;">Department</small>
                            <span class="fw-bold text-body-emphasis small" id="archiveDept">-</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="p-2.5 bg-body-tertiary rounded-3 border">
                            <small class="text-body-secondary d-block" style="font-size:0.75rem;">Contract End
                                Date</small>
                            <span class="fw-bold text-body-emphasis small" id="archiveContractEnd">-</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="p-2.5 bg-body-tertiary rounded-3 border">
                            <small class="text-body-secondary d-block" style="font-size:0.75rem;">Employment
                                Status</small>
                            <span class="fw-bold text-body-emphasis small" id="archiveEmpStatus">-</span>
                        </div>
                    </div>
                </div>

                <!-- Archived Requirements Table -->
                <div class="card border rounded-3 mb-4 shadow-none">
                    <div class="card-header bg-body-tertiary py-2.5 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="fas fa-file-shield text-success"></i>
                        <h6 class="fw-bold mb-0 text-body-emphasis small text-uppercase">Verified Office Clearance
                            Requirements</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle border-0 mb-0">
                            <thead class="table-light small text-uppercase text-body-secondary border-bottom">
                                <tr>
                                    <th class="ps-3 py-2.5" style="width: 5%;">#</th>
                                    <th class="py-2.5" style="width: 25%;">Requirement</th>
                                    <th class="py-2.5" style="width: 25%;">Submitted Document</th>
                                    <th class="py-2.5" style="width: 15%;">Status</th>
                                    <th class="py-2.5" style="width: 20%;">Reviewer Remark</th>
                                    <th class="pe-3 py-2.5" style="width: 10%;">Verified On</th>
                                </tr>
                            </thead>
                            <tbody id="archiveRequirementsBody" class="text-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Faculty Contact & Email -->
                <div class="p-3 bg-body-tertiary rounded-3 border d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-body-secondary d-block">Registered Contact Email</small>
                        <span class="fw-semibold text-body-emphasis small" id="archiveEmail">-</span>
                    </div>
                    <span class="badge bg-secondary-subtle text-body-secondary border px-2.5 py-1.5"><i
                            class="fas fa-shield-alt me-1"></i>Permanent Record</span>
                </div>
            </div>
            <div
                class="modal-footer bg-body-tertiary border-top d-flex justify-content-between align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="printSingleArchive()"><i
                        class="fas fa-print me-1"></i> Print Summary</button>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';
    const TARGET_REQUIREMENT_NAME = <?= json_encode($targetRequirementName) ?>;
    const OFFICE_SCOPE_ITEMS = <?= json_encode($scopeItems) ?>;
    const OFFICE_LABEL = <?= json_encode($officeLabel) ?>;
    const OFFICE_COLOR = <?= json_encode($officeColor) ?>;
    const isDeptHeadRole = <?= json_encode($isDeptHeadRole) ?>;

    let trackingRows = [];
    let archiveRows = [];
    let reviewModal = null;
    let archiveDetailModal = null;
    let currentReviewFacultyId = null;
    let currentReviewProfile = null;
    let currentReviewClearance = null;
    let _currentApprovalFacultyId = null;
    let _currentClearanceId = null;
    let activeStatusGroup = 'all';
    let currentPage = 1;
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

    function refreshCurrentTab() {
        loadTracking();
        loadArchives();
    }

    function switchToActiveTab(group) {
        const triggerEl = document.querySelector('#tab-active-btn');
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        if (group) selectStatusGroup(group);
    }

    function switchToArchiveTab() {
        const triggerEl = document.querySelector('#tab-archive-btn');
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }

    function getTargetItem(clearance) {
        if (!clearance || !Array.isArray(clearance.items)) return null;
        if (!TARGET_REQUIREMENT_NAME) return clearance.items[0] || null;
        return clearance.items.find(it => it.name === TARGET_REQUIREMENT_NAME) || null;
    }

    function matchesStatusGroup(row, group) {
        if (group === 'all') return true;
        const c = row.clearance || { status: 'Not Submitted' };
        const targetItem = getTargetItem(c);
        const itemStatus = targetItem ? targetItem.status : c.status;

        if (group === 'pending') {
            return itemStatus === 'Pending' || itemStatus === 'Under Review' || c.status === 'In Progress' || c.status === 'Under Review' || (targetItem && targetItem.file_name && targetItem.status !== 'Cleared');
        }
        if (group === 'action') {
            return itemStatus === 'Denied' || itemStatus === 'Hold' || itemStatus === 'With Deficiency' || c.status === 'Action Required' || c.status === 'With Deficiency';
        }
        if (group === 'cleared') {
            return itemStatus === 'Cleared' || itemStatus === 'Approved' || c.status === 'Completed' || c.status === 'Cleared';
        }
        if (group === 'not-submitted') {
            const hasFile = Boolean(targetItem && (targetItem.file_name || targetItem.file_path || targetItem.original_name));
            const isCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
            const isAction = targetItem && (targetItem.status === 'Denied' || targetItem.status === 'Hold' || targetItem.status === 'With Deficiency');
            return !hasFile && !isCleared && !isAction;
        }
        return true;
    }

    function renderStatusControls() {
        const container = document.getElementById('statusControlsContainer');
        if (!container) return;

        // [key, label, badgeColor] — badgeColor maps to badge-* classes
        const groups = [
            ['all', 'All Active', 'dark'],
            ['pending', 'Pending Verification', 'primary'],
            ['action', 'Denied / Resubmission', 'danger'],
            ['cleared', 'Cleared', 'success'],
            ['not-submitted', 'Not Submitted', 'secondary'],
        ];

        container.innerHTML = `<div class="d-flex flex-wrap gap-2 align-items-center">` +
            groups.map(([key, label, badgeColor]) => {
                const count = key === 'all'
                    ? trackingRows.length
                    : trackingRows.filter(row => matchesStatusGroup(row, key)).length;
                const isActive = activeStatusGroup === key;

                return `<button type="button"
                    class="clr-status-chip ${isActive ? 'active' : ''}"
                    onclick="selectStatusGroup('${key}')">
                    <span>${escapeHtml(label)}</span>
                    <span class="clr-chip-count badge-${badgeColor}">${count}</span>
                </button>`;
            }).join('') +
            `</div>`;
    }

    function selectStatusGroup(group) {
        activeStatusGroup = group;
        currentPage = 1;
        renderStatusControls();
        renderTracking();
    }

    function renderTracking() {
        const body = document.getElementById('trackingBody');
        const query = (document.getElementById('trackingSearch')?.value || '').toLowerCase();
        const empFilter = document.getElementById('trackingEmpStatusFilter')?.value || 'all';

        const filtered = trackingRows.filter(row => {
            const matchesGroup = matchesStatusGroup(row, activeStatusGroup);
            const text = `${row.name} ${row.faculty_id || row.faculty_no || ''} ${row.designated_department}`.toLowerCase();
            const matchesQuery = !query || text.includes(query);
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
                try {
                    const c = row.clearance || { status: 'Not Submitted', progress: 0, approved_items: 0, total_items: 0 };
                    let expiry = 'Not set';
                    if (row.contractual_end && row.contractual_end !== '0000-00-00') {
                        try {
                            const rawEnd = String(row.contractual_end).split(' ')[0];
                            const eDate = new Date(`${rawEnd}T00:00:00`);
                            if (!isNaN(eDate.getTime())) {
                                expiry = eDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
                            }
                        } catch (_) { }
                    }
                    const cStatus = c.status || 'Not Submitted';
                    const tone = (cStatus === 'Action Required' || cStatus === 'With Deficiency') ? 'danger' : ((cStatus === 'Completed' || cStatus === 'Cleared') ? 'success' : (cStatus === 'Not Submitted' ? 'secondary' : ((cStatus === 'For Final Approval' || cStatus === 'For Department Head Approval') ? 'warning' : 'info')));

                    const targetItem = getTargetItem(c);
                    const isItemCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
                    const officeProgress = isItemCleared ? 100 : 0;
                    const officeApprovedCount = isItemCleared ? 1 : 0;
                    const officeTone = isItemCleared ? 'success' : (targetItem && (targetItem.status === 'Denied' || targetItem.status === 'Hold' || targetItem.status === 'With Deficiency') ? 'danger' : (targetItem && targetItem.file_name ? 'info' : 'secondary'));

                    const statusIcon = (cStatus === 'Completed' || cStatus === 'Cleared') ? 'fa-check-circle' :
                        ((cStatus === 'Action Required' || cStatus === 'With Deficiency') ? 'fa-exclamation-circle' :
                            (cStatus === 'Not Submitted' ? 'fa-minus-circle' : 'fa-clock'));

                    const rowNameEsc = escapeHtml(row.name || 'Unknown');
                    const rowNameAttr = JSON.stringify(row.name || '').replace(/"/g, '&quot;');

                    let submittedDateStr = '-';
                    if (row.submitted_at) {
                        try {
                            const rawSub = typeof row.submitted_at === 'string' ? row.submitted_at.replace(' ', 'T') : row.submitted_at;
                            const sDate = new Date(rawSub);
                            if (!isNaN(sDate.getTime())) {
                                submittedDateStr = sDate.toLocaleDateString();
                            }
                        } catch (_) { }
                    }

                    return `<tr>
                    <td class="ps-3">
                        <div class="fw-semibold text-body-emphasis">${rowNameEsc}</div>
                        <small class="text-body-secondary">${escapeHtml(row.faculty_id || row.faculty_no || '')}</small>
                    </td>
                    <td>${escapeHtml(row.designated_department || 'N/A')}</td>
                    <td class="${row.days_remaining !== null && row.days_remaining <= 30 ? 'text-danger fw-bold' : ''}">${expiry}<small class="d-block text-body-secondary">${row.days_remaining === null ? '' : (row.days_remaining < 0 ? 'Expired' : row.days_remaining + ' days remaining')}</small></td>
                    <td style="min-width:150px">
                        <div class="progress mb-1" style="height:7px"><div class="progress-bar bg-${officeTone}" style="width:${officeProgress}%"></div></div>
                        <small class="text-body-secondary">${officeProgress}% (${officeApprovedCount}/1)</small>
                    </td>
                    <td><span class="badge bg-${tone}-subtle text-${tone} border border-${tone}-subtle px-2 py-1">${escapeHtml(cStatus)}</span></td>
                    <td>${submittedDateStr}</td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="openReview(${row.id})" title="Review Clearance Details">
                                <i class="fas fa-search me-1"></i>Review
                            </button>
                        </div>
                    </td>
                </tr>`;
                } catch (rowErr) {
                    console.error('Error rendering clearance row:', rowErr, row);
                    return `<tr><td colspan="7" class="text-center text-muted small py-2">Error displaying faculty record (ID: ${row.id}): ${escapeHtml(rowErr && rowErr.message ? rowErr.message : 'Render error')}</td></tr>`;
                }
            }).join('');
        }
        renderPagination(totalPages, filtered.length);
    }

    function filterTracking() {
        renderTracking();
    }

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
        const empFilter = document.getElementById('archiveEmpFilter')?.value || 'all';

        return archiveRows.filter(row => {
            const text = `${row.name} ${row.faculty_no || ''} ${row.designated_department || ''}`.toLowerCase();
            const matchesQuery = !query || text.includes(query);
            const termLabel = `${row.academic_year} · ${row.semester}`;
            const matchesTerm = termFilter === 'all' || termLabel === termFilter;
            const rowEmp = row.employment_status || '';
            const matchesEmp = empFilter === 'all' || rowEmp.toLowerCase() === empFilter.toLowerCase();
            return matchesQuery && matchesTerm && matchesEmp;
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
                    : '-';
                const clearedAt = row.updated_at
                    ? new Date(row.updated_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '-';

                const officeOnlyItems = (row.items || []).filter(it => !TARGET_REQUIREMENT_NAME || it.name === TARGET_REQUIREMENT_NAME);
                const reqTags = (officeOnlyItems.length > 0 ? officeOnlyItems : (row.items || [])).map(it => {
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
                    <div class="fw-semibold text-body-emphasis">${escapeHtml(row.name || ((row.first_name || '') + ' ' + (row.last_name || '')).trim() || 'Unknown')}</div>
                    <small class="text-body-secondary d-block">${escapeHtml(row.faculty_no || '')}</small>
                    <small class="text-body-secondary">${escapeHtml(row.designated_department || '')}</small>
                </td>
                <td><span class="badge bg-secondary-subtle text-body-secondary border">${escapeHtml(row.academic_year)} · ${escapeHtml(row.semester)}</span></td>
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
            const queryParam = `clearance_id=${clearanceId}${archiveId > 0 ? '&archive_id=' + archiveId : ''}`;
            const response = await fetch(`${clearanceApi}?action=archive-detail&${queryParam}`);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            const r = data.record;

            const modalTitle = document.getElementById('archiveModalTitle');
            if (modalTitle) modalTitle.innerHTML = `<i class="fas fa-archive me-2"></i>Archived Record &bull; ${escapeHtml(r.name || 'Faculty Member')}`;
            const modalMeta = document.getElementById('archiveModalMeta');
            if (modalMeta) modalMeta.textContent = `${r.faculty_no || ''} &bull; ${r.designated_department || ''}`;

            document.getElementById('archiveModalTerm').textContent = `Academic Term: ${r.academic_year || ''} · ${r.semester || ''}`;
            document.getElementById('archiveModalCompletedAt').textContent = r.updated_at ? new Date(r.updated_at.replace(' ', 'T')).toLocaleString() : '-';
            document.getElementById('archiveFacultyNo').textContent = r.faculty_no || '-';
            document.getElementById('archiveDept').textContent = r.designated_department || '-';
            document.getElementById('archiveContractEnd').textContent = r.contractual_end && r.contractual_end !== '0000-00-00' ? new Date(`${r.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            document.getElementById('archiveEmpStatus').textContent = r.employment_status || '-';
            document.getElementById('archiveEmail').textContent = r.email || '-';

            const body = document.getElementById('archiveRequirementsBody');
            const allItems = r.items || [];
            const officeItems = allItems.filter(it => !TARGET_REQUIREMENT_NAME || it.name === TARGET_REQUIREMENT_NAME);
            const displayItems = officeItems.length > 0 ? officeItems : allItems;

            if (!displayItems.length) {
                body.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-body-secondary">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    <strong>${escapeHtml(TARGET_REQUIREMENT_NAME || 'Office Clearance')} file not found in archive.</strong><br>
                    <small>The file may have been uploaded before the archive system was initialized. Please check the faculty's active clearance record.</small>
                </td></tr>`;
            } else {
                body.innerHTML = displayItems.map((it, idx) => {
                    const hasFile = Boolean(it.file_name || it.original_name || it.file_path);
                    const rawFileName = it.file_name || it.original_name || (it.file_path ? it.file_path.split('/').pop() : 'clearance-file.pdf');
                    const displayCleanName = rawFileName.replace(/\.[^/.]+$/, "");
                    const isMissing = !hasFile && it.status !== 'Cleared';
                    const fileUrl = it.file_path
                        ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                        : `${clearanceApi}?action=file&download=1&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`;
                    const viewUrl = it.file_path
                        ? `${clearanceApi}?action=file&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                        : (it.id ? `${clearanceApi}?action=file&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}` : null);

                    const clearedDateStr = it.cleared_at
                        ? new Date(it.cleared_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                        : (it.updated_at ? new Date(it.updated_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-');

                    let rawRemark = (it.remarks ? String(it.remarks) : '').trim();
                    let scopeBadges = '';
                    const scopeMatch = rawRemark.match(/<!--SCOPE_STATE:(.*?)-->/);
                    if (scopeMatch) {
                        try {
                            const parsedScope = JSON.parse(scopeMatch[1]);
                            const pList = Array.isArray(parsedScope.passed) ? parsedScope.passed : [];
                            const fList = Array.isArray(parsedScope.failed) ? parsedScope.failed : [];
                            if (pList.length || fList.length) {
                                scopeBadges = `
                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                        ${pList.map(p => `<span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:0.7rem;"><i class="fas fa-check me-1"></i>${escapeHtml(p)}</span>`).join('')}
                                        ${fList.map(f => `<span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:0.7rem;"><i class="fas fa-times me-1"></i>${escapeHtml(f)}</span>`).join('')}
                                    </div>
                                `;
                            }
                        } catch (e) { }
                    }
                    rawRemark = rawRemark.replace(/<!--SCOPE_STATE:.*?-->/g, '').trim();
                    rawRemark = rawRemark.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '').trim();
                    rawRemark = rawRemark.replace(/Deficiencies Flagged:[\s\S]*?(?=Instructions:|$)/i, '')
                        .replace(/Complied:[\s\S]*?(?=Instructions:|$)/i, '')
                        .replace(/^Instructions:\s*/i, '')
                        .trim();
                    const remarkDisplay = rawRemark || (isMissing ? 'No file submitted' : 'Requirement approved.');

                    return `<tr class="border-bottom">
                    <td class="ps-3 fw-normal text-body" style="font-size: 0.95rem;">${idx + 1}</td>
                    <td>
                        <div class="fw-normal text-body-emphasis" style="font-size: 0.92rem; line-height: 1.35;">${escapeHtml(it.name || TARGET_REQUIREMENT_NAME || 'Office Clearance')}</div>
                        <div class="text-body-secondary" style="font-size: 0.82rem;">Office Verification</div>
                    </td>
                    <td>
                        ${hasFile ? `
                        <div>
                            <div class="d-flex align-items-center gap-1.5 mb-1.5">
                                <i class="far fa-file-pdf fs-4 text-danger me-1"></i>
                                <span class="fw-semibold text-body-emphasis text-truncate" style="max-width: 230px; font-size: 0.88rem;" title="${escapeHtml(rawFileName)}">
                                    ${escapeHtml(displayCleanName)}
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border-0 px-1.5 py-0.5 rounded-1" style="font-size: 0.65rem; font-weight: 600;">PDF</span>
                            </div>
                            <div class="btn-group clearance-btn-group rounded-2 overflow-hidden shadow-none" style="height: 31px;">
                                ${viewUrl ? `
                                <a href="${viewUrl}" target="_blank" class="btn btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1 border-0">
                                    <i class="far fa-eye"></i> View
                                </a>` : ''}
                                <a href="${fileUrl}" download="${escapeHtml(rawFileName)}" class="btn btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1 border-0">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>` : `
                        <div class="d-flex align-items-center gap-1.5 text-body-secondary small">
                            <i class="far fa-file-circle-xmark fs-5"></i>
                            <span>No file uploaded</span>
                        </div>`}
                    </td>
                    <td>
                        ${isMissing ? `
                        <span class="badge rounded-pill bg-secondary-subtle text-body-secondary border d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
                            <i class="fas fa-minus" style="font-size: 0.75rem;"></i> Missing
                        </span>` : (it.status === 'Cleared' ? `
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
                            <i class="fas fa-check" style="font-size: 0.78rem;"></i> Approved
                        </span>` : `
                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
                            <i class="fas fa-times" style="font-size: 0.78rem;"></i> ${escapeHtml(it.status)}
                        </span>`)}
                    </td>
                    <td>
                        <div class="d-flex flex-column" style="font-size: 0.88rem;">
                            <div class="d-inline-flex align-items-center gap-2">
                                <i class="far fa-comment text-body-secondary" style="font-size: 0.95rem;"></i>
                                <span class="text-body">${escapeHtml(remarkDisplay)}</span>
                            </div>
                            ${scopeBadges}
                        </div>
                    </td>
                    <td class="pe-3">
                        <div class="d-inline-flex align-items-center gap-2" style="font-size: 0.88rem;">
                            <i class="far fa-calendar-alt text-body-secondary" style="font-size: 0.95rem;"></i>
                            <span class="text-body">${clearedDateStr}</span>
                        </div>
                    </td>
                </tr>`;
                }).join('');
            }

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
        const headers = ['Clearance ID', 'Faculty Name', 'Faculty ID', 'Department', 'Employment Status', 'Academic Term', 'Contract Expiry Date', 'Date Completed'];
        const rows = filtered.map(r => [
            r.clearance_id,
            `"${(r.name || '').replace(/"/g, '""')}"`,
            `"${(r.faculty_no || '').replace(/"/g, '""')}"`,
            `"${(r.designated_department || '').replace(/"/g, '""')}"`,
            `"${r.employment_status || 'Probationary'}"`,
            `"${r.academic_year} · ${r.semester}"`,
            `"${r.intent_type || ''}"`,
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

    /* ACTIVE CLEARANCE REVIEW LOGIC (DEPARTMENT HEAD RICH UI TEMPLATE) */
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
            _currentApprovalFacultyId = facultyId;
            _currentClearanceId = c.clearance_id || 0;

            const empStatus = profile.employment_status || 'Probationary';

            // Top Header: Title & Subtitle
            const mainTitle = document.getElementById('reviewMainTitle');
            if (mainTitle) mainTitle.textContent = `${empStatus} Employee Clearance`;
            const mainSub = document.getElementById('reviewMainSubtitle');
            if (mainSub) mainSub.textContent = 'Complete all clearance requirements and submit your documents for review.';

            // Hidden backward-compatibility elements
            const revTitle = document.getElementById('reviewTitle');
            if (revTitle) revTitle.innerHTML = `<i class="fas fa-clipboard-check me-2"></i>Review Clearance - ${escapeHtml(profile.first_name)} ${escapeHtml(profile.last_name || '')}`;
            const revMeta = document.getElementById('reviewMeta');
            if (revMeta) revMeta.textContent = `${profile.faculty_id || ''} · ${profile.designated_department || 'Department'}`;

            // 1. Stats Card: Contract Expiry
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

            // Stats Card: Employment Status Badge
            const empEl = document.getElementById('summaryEmpStatus');
            if (empEl) {
                empEl.textContent = empStatus;
                empEl.className = `badge rounded-pill ${empStatus === 'Regular' ? 'bg-success-subtle text-success border border-success-subtle' : (empStatus === 'Probationary' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-body-secondary border')} px-2.5 py-1 fw-semibold`;
            }

            // Stats Card: Progress
            const targetItem = getTargetItem(c);
            const isItemCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
            const officeProgress = isItemCleared ? 100 : 0;
            const officeApprovedCount = isItemCleared ? 1 : 0;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${officeProgress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${officeProgress}% (${officeApprovedCount}/1)`;
            const progressSub = document.getElementById('summaryProgressSub');
            if (progressSub) progressSub.textContent = isItemCleared ? 'All requirements completed' : `${officeProgress}% completed`;

            // 2. Transaction Summary Card
            const txnId = c.clearance_no || ('TRX-' + (new Date().getFullYear()) + '-' + String(c.clearance_id || 1).padStart(6, '0'));
            const empFullName = `${(profile.last_name || '').toUpperCase()}, ${(profile.first_name || '').toUpperCase()} ${profile.middle_name ? profile.middle_name.charAt(0) + '.' : ''}`.trim();
            const posTitle = profile.employment_status ? `${profile.employment_status} Employee` : (profile.rank || 'Probationary Employee');
            const subDateFormatted = c.created_at ? new Date(c.created_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
            const updDateFormatted = c.updated_at ? new Date(c.updated_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : subDateFormatted;

            const elTxnId = document.getElementById('reviewTxnId'); if (elTxnId) elTxnId.textContent = txnId;
            const elTxnEmp = document.getElementById('reviewTxnEmployee'); if (elTxnEmp) elTxnEmp.textContent = empFullName;
            const elTxnPos = document.getElementById('reviewTxnPosition'); if (elTxnPos) elTxnPos.textContent = posTitle;
            const elTxnSub = document.getElementById('reviewTxnSubmitted'); if (elTxnSub) elTxnSub.textContent = subDateFormatted;
            const elTxnUpd = document.getElementById('reviewTxnUpdated'); if (elTxnUpd) elTxnUpd.textContent = updDateFormatted;

            // 3. Horizontal Stepper (3 steps)
            const step1Date = document.getElementById('stepperStep1Date'); if (step1Date) step1Date.textContent = 'Completed';
            const step2Circle = document.getElementById('stepperStep2Circle');
            const step2Sub = document.getElementById('stepperStep2Sub');
            const stepLine2 = document.getElementById('stepperLine2');
            const step4Circle = document.getElementById('stepperStep4Circle');
            const step4Sub = document.getElementById('stepperStep4Sub');

            if (isItemCleared) {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-success text-white'; step2Circle.innerHTML = '<i class="fas fa-check"></i>'; }
                if (step2Sub) step2Sub.textContent = 'Completed';
                if (stepLine2) stepLine2.style.background = '#198754';
            } else {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-primary text-white'; step2Circle.textContent = '2'; }
                if (step2Sub) step2Sub.textContent = 'In Progress';
                if (stepLine2) stepLine2.style.background = 'var(--bs-border-color)';
            }

            // Step 3 (Completed) state
            const isCompleted = isItemCleared && !!c.signature_data;
            if (isCompleted) {
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-success text-white'; step4Circle.innerHTML = '<i class="fas fa-check"></i>'; }
                if (step4Sub) step4Sub.textContent = 'Completed';
            } else if (isItemCleared) {
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-primary text-white'; step4Circle.textContent = '3'; }
                if (step4Sub) step4Sub.textContent = 'Pending';
            } else {
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-body-secondary text-body-secondary'; step4Circle.textContent = '3'; }
                if (step4Sub) step4Sub.textContent = 'Not yet';
            }

            // 4. Success Status Banner (Sequential lock aware)
            const banner = document.getElementById('reviewSuccessBanner');
            const reqName = TARGET_REQUIREMENT_NAME || (targetItem ? targetItem.name : '');
            const currentStage = (c.stages && reqName) ? c.stages[reqName] : null;
            const isStageLocked = currentStage ? (currentStage.is_unlocked === false || currentStage.state === 'locked') : false;
            const stageLockReason = (currentStage && currentStage.lock_reason) ? currentStage.lock_reason : 'Complete the previous clearance step to unlock this requirement.';

            if (banner) {
                if (isItemCleared) {
                    banner.className = 'alert alert-success d-flex align-items-center gap-3 p-3 rounded-3 border border-success-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-check"></i></div>
                    <span class="fw-semibold small text-success-emphasis">All requirements have been cleared.</span>`;
                } else if (isStageLocked) {
                    banner.className = 'alert alert-warning d-flex align-items-center gap-3 p-3 rounded-3 border border-warning-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-lock"></i></div>
                    <div>
                        <div class="fw-bold small text-warning-emphasis">Clearance Step Locked</div>
                        <div class="small text-warning-emphasis">${escapeHtml(stageLockReason)}</div>
                    </div>`;
                } else {
                    banner.className = 'alert alert-info d-flex align-items-center gap-3 p-3 rounded-3 border border-info-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-info"></i></div>
                    <span class="fw-semibold small text-info-emphasis">Clearance requirements are currently under review. Please review the submitted document.</span>`;
                }
            }

            // 5. Clearance Agreement Form Review Section
            const formBadge = document.getElementById('agreementFormStatusBadge');
            const formBody = document.getElementById('agreementFormReviewBody');
            if (formBadge && formBody) {
                const isFormSub = !!c.form_submitted;
                const formSt = c.form_status || (isFormSub ? 'Pending Review' : 'Not Submitted');
                const formDate = c.form_submitted_at ? new Date(c.form_submitted_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
                const appDate = c.form_approved_at ? new Date(c.form_approved_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '-';

                if (formSt === 'Approved') {
                    formBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
                    formBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Approved &amp; Endorsed';
                    formBody.innerHTML = `
                        <div class="p-3 bg-success-subtle bg-opacity-25 rounded border border-success-subtle">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <strong class="text-success-emphasis d-block mb-1"><i class="fas fa-file-signature me-1"></i>Clearance Form Endorsed</strong>
                                    <small class="text-body-secondary">Submitted on <strong>${formDate}</strong> · Approved on <strong>${appDate}</strong></small>
                                </div>
                                <span class="badge bg-success text-white px-3 py-2"><i class="fas fa-check me-1"></i>Endorsed by Dept Head</span>
                            </div>
                        </div>
                    `;
                } else if (isFormSub && formSt === 'Pending Review') {
                    formBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
                    formBadge.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Pending Department Head Review';
                    formBody.innerHTML = `
                        <div class="p-3 bg-warning-subtle bg-opacity-25 rounded border border-warning-subtle">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h6 class="fw-bold text-warning-emphasis mb-1"><i class="fas fa-exclamation-circle me-1"></i>Clearance Agreement Form</h6>
                                    <div class="small text-body-secondary mb-1">Submitted on <strong>${formDate}</strong> with agreement acknowledgment.</div>
                                    <div class="small text-body-secondary fst-italic">&ldquo;I hereby acknowledge and agree that I have complied with the rules, regulations, policies, and professional standards of the institution.&rdquo;</div>
                                </div>
                                ${isDeptHeadRole ? `
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <button type="button" class="btn btn-success btn-sm fw-semibold px-3 py-2" onclick="endorseClearanceForm(${facultyId}, 'approve')">
                                        <i class="fas fa-check me-1"></i> Approve &amp; Endorse
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm fw-semibold px-3 py-2" onclick="endorseClearanceForm(${facultyId}, 'reject')">
                                        <i class="fas fa-rotate-left me-1"></i> Return
                                    </button>
                                </div>` : `<span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-hourglass-half me-1"></i>Awaiting Dept Head Endorsement</span>`}
                            </div>
                        </div>
                    `;
                } else if (formSt === 'Rejected') {
                    formBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
                    formBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>Returned for Revision';
                    formBody.innerHTML = `
                        <div class="p-2 bg-danger-subtle bg-opacity-25 rounded border border-danger-subtle small">
                            <strong class="text-danger"><i class="fas fa-info-circle me-1"></i>Returned to Faculty for Revision:</strong> ${escapeHtml(c.form_remarks || 'Revision required.')}
                        </div>
                    `;
                } else {
                    formBadge.className = 'badge bg-secondary-subtle text-body-secondary border px-2 py-1';
                    formBadge.innerHTML = '<i class="fas fa-circle-xmark me-1"></i>Not Submitted';
                    formBody.innerHTML = `
                        <div class="small text-body-secondary">
                            <i class="fas fa-info-circle me-1"></i>The faculty member has not submitted their Clearance Form for this term yet.
                        </div>
                    `;
                }
            }

            // 6. Clearance Requirements Table rows
            const body = document.getElementById('reviewBody');
            let visibleItems = c.items || [];
            if (TARGET_REQUIREMENT_NAME) {
                visibleItems = visibleItems.filter(item => item.name === TARGET_REQUIREMENT_NAME);
            }
            body.innerHTML = visibleItems.length ? visibleItems.map(item => {
                const hasFile = Boolean(item.file_name || item.original_name || item.file_path);
                const rawFileName = item.file_name || item.original_name || (item.file_path ? item.file_path.split('/').pop() : 'clearance-file.pdf');
                const isMissing = !hasFile && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const fileUrl = item.file_path
                    ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(item.file_path)}&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                    : `${clearanceApi}?action=file&download=1&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`;
                const viewUrl = item.file_path
                    ? `${clearanceApi}?action=file&path=${encodeURIComponent(item.file_path)}&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                    : (item.id ? `${clearanceApi}?action=file&item_id=${item.id}&filename=${encodeURIComponent(rawFileName)}` : null);
                const isCleared = item.status === 'Cleared' || item.status === 'Approved';

                return `<tr style="border-bottom:1px solid var(--bs-border-color-translucent);">
                <td class="ps-4 py-3 align-top">
                    <div>
                        <div class="fw-bold text-body-emphasis" style="font-size:0.875rem;">${escapeHtml(item.name || TARGET_REQUIREMENT_NAME || 'Office Clearance')}</div>
                        <small class="text-body-secondary" style="font-size:0.7rem;"><?= htmlspecialchars($officeLabel) ?></small>
                    </div>
                </td>
                <td class="py-3 align-top">
                    ${hasFile ? `
                    <div class="p-2 rounded-3 border bg-body-tertiary d-flex align-items-center gap-2" style="max-width:210px;">
                        <div class="rounded-2 bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                            <i class="fas fa-file-pdf text-danger" style="font-size:1rem;"></i>
                        </div>
                        <div style="min-width:0;">
                            <a href="${viewUrl || fileUrl}" target="_blank"
                               class="fw-semibold text-primary text-decoration-none d-block"
                               style="font-size:0.78rem;max-width:130px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;"
                               title="${escapeHtml(rawFileName)}">
                                ${escapeHtml(rawFileName)}
                            </a>
                            <div class="text-body-secondary" style="font-size:0.66rem;">
                                ${item.uploaded_at ? new Date(item.uploaded_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : subDateFormatted}
                            </div>
                        </div>
                    </div>
                    <a href="${fileUrl}" class="btn btn-outline-secondary btn-sm mt-1.5 w-100" style="font-size:0.72rem;max-width:210px;" title="Download file">
                        <i class="fas fa-download me-1"></i>Download
                    </a>` : `
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-dashed bg-body-tertiary" style="max-width:210px;">
                        <div class="rounded-2 bg-secondary-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                            <i class="fas fa-file-circle-xmark text-secondary" style="font-size:1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-body-secondary" style="font-size:0.78rem;">No file uploaded</div>
                            <div class="text-body-secondary" style="font-size:0.68rem;">Awaiting faculty submission</div>
                        </div>
                    </div>`}
                </td>
                <td class="py-3 align-top">
                    <span class="badge rounded-pill px-3 py-1.5 fw-semibold mb-1 d-inline-block"
                        style="font-size:0.75rem;
                        ${isMissing ? 'background:rgba(108,117,125,0.12);color:#6c757d;border:1px solid rgba(108,117,125,0.25);' :
                        isCleared ? 'background:rgba(25,135,84,0.12);color:#198754;border:1px solid rgba(25,135,84,0.3);' :
                            (item.status === 'Denied' || item.status === 'Hold') ? 'background:rgba(220,53,69,0.12);color:#dc3545;border:1px solid rgba(220,53,69,0.25);' :
                                'background:rgba(13,110,253,0.1);color:#0d6efd;border:1px solid rgba(13,110,253,0.2);'}">
                        <i class="fas ${isCleared ? 'fa-check-circle' : isMissing ? 'fa-circle-xmark' : (item.status === 'Denied' || item.status === 'Hold') ? 'fa-times-circle' : 'fa-hourglass-half'} me-1"></i>
                        ${escapeHtml(isCleared ? 'Cleared' : statusLabel)}
                    </span>
                    <div>${formatClearanceRemark(item.remarks, isMissing)}</div>
                </td>
                <td class="pe-4 py-3 align-top">
                    ${renderScopeVerificationList(item)}
                </td>
            </tr>`;
            }).join('') : `<tr><td colspan="4" class="text-center py-5">
                <div class="text-body-secondary">
                    <i class="fas fa-inbox fs-2 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold">No ${escapeHtml(TARGET_REQUIREMENT_NAME || 'Office Clearance')} submitted</div>
                    <small>This section will populate once the faculty submits their clearance.</small>
                </div>
            </td></tr>`;

            reviewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal'));
            reviewModal.show();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    /* ARCHIVE CONFIRMATION POP-UP HANDLERS */
    let pendingArchiveTarget = { facultyId: 0, facultyName: '', clearanceId: 0 };
    let confirmArchiveModalInstance = null;

    function confirmDeclarationAndArchiveFromRow(facultyId, facultyName, clearanceId) {
        pendingArchiveTarget = { facultyId, facultyName, clearanceId };
        executeArchiveClearance();
    }


    async function executeArchiveClearance() {
        const modalBtn = document.getElementById('btnExecuteArchive');
        const originalModalHtml = modalBtn ? modalBtn.innerHTML : '';
        if (modalBtn) {
            modalBtn.disabled = true;
            modalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Archiving...';
        }

        const form = new FormData();
        form.append('action', 'archive-clearance');
        form.append('faculty_id', pendingArchiveTarget.facultyId);
        if (pendingArchiveTarget.clearanceId) {
            form.append('clearance_id', pendingArchiveTarget.clearanceId);
        }

        try {
            const response = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'Failed to archive clearance record.');

            showTrackingAlert(data.message || 'The clearance record has been archived.', 'success');
            confirmArchiveModalInstance?.hide();
            reviewModal?.hide();
            loadTracking();
            switchToArchiveTab();
        } catch (error) {
            alert(error.message);
        } finally {
            if (modalBtn) {
                modalBtn.disabled = false;
                modalBtn.innerHTML = originalModalHtml || '<i class="fas fa-archive me-1"></i> Yes, Archive Record';
            }
        }
    }

    async function endorseClearanceForm(facultyId, decision) {
        let remark = '';
        if (decision === 'reject') {
            const res = await openRemarkModal({
                title: 'Return Clearance Form',
                sub: 'Provide a reason or instructions for the faculty member',
                label: 'Reason for Return *',
                hint: 'Explain what needs correction before the form can be endorsed.',
                btnText: 'Return Form',
                btnClass: 'btn-danger',
                headerClass: 'bg-danger-subtle',
                iconClass: 'bg-danger text-white',
                btnIcon: 'fa-rotate-left',
                required: true,
            });
            if (res === null) return;
            remark = res;
        }

        const form = new FormData();
        form.append('action', 'endorse-clearance-form');
        form.append('faculty_id', facultyId);
        form.append('decision', decision);
        form.append('remark', remark);

        try {
            const response = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);

            showTrackingAlert(data.message, 'success');
            await openReview(facultyId);
            refreshCurrentTab();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    // Remark Modal State
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

            cancelBtn.onclick = () => { _remarkModal.hide(); resolve(null); };

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

            input.onkeydown = (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); confirm.click(); } };

            _remarkModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('remarkModal'));
            _remarkModal.show();
            setTimeout(() => { input.focus(); }, 100);
        });
    }

    /* SCOPE OF VERIFICATION CHECKLIST (PASS / FAIL TOGGLES) */
    function getParsedScopeState(item) {
        let scope = { passed: [], failed: [], locked: false };
        if (item && item.remarks) {
            const match = String(item.remarks).match(/<!--SCOPE_STATE:(.*?)-->/);
            if (match) {
                try {
                    const parsed = JSON.parse(match[1]);
                    if (Array.isArray(parsed.passed)) scope.passed = parsed.passed.map(s => String(s).trim());
                    if (Array.isArray(parsed.failed)) scope.failed = parsed.failed.map(s => String(s).trim());
                    if (parsed.locked || parsed.confirmed) scope.locked = true;
                } catch (e) { }
            }
        }
        const isCleared = Boolean(item && (item.status === 'Cleared' || item.status === 'Approved'));
        if (isCleared) {
            scope.locked = true;
            if (scope.failed.length === 0 && scope.passed.length === 0) {
                scope.passed = [...OFFICE_SCOPE_ITEMS];
            }
        }
        return scope;
    }

    function isScopeConfirmed(item) {
        if (!item) return false;
        if (item.status === 'Cleared' || item.status === 'Approved') return true;
        const scope = getParsedScopeState(item);
        return Boolean(scope.locked);
    }

    function renderScopeVerificationList(item) {
        const hasFile = Boolean(item && (item.file_name || item.original_name || item.file_path));
        const isCleared = Boolean(item && (item.status === 'Cleared' || item.status === 'Approved'));
        const confirmed = isCleared || isScopeConfirmed(item);
        const scope = getParsedScopeState(item);
        const passedSet = new Set(scope.passed.map(s => s.toLowerCase()));
        const failedSet = new Set(scope.failed.map(s => s.toLowerCase()));

        // Sequential Locking check
        const reqName = TARGET_REQUIREMENT_NAME || (item ? item.name : '');
        const currentStage = (currentReviewClearance?.stages && reqName) ? currentReviewClearance.stages[reqName] : null;
        const isStageLocked = currentStage ? (currentStage.is_unlocked === false || currentStage.state === 'locked') : false;
        const stageLockReason = (currentStage && currentStage.lock_reason) ? currentStage.lock_reason : 'Complete the previous clearance step to unlock this requirement.';

        if (isStageLocked && !isCleared) {
            return `
                <div class="scope-verification-box" id="scopeBox_${item.id}">
                    <div class="d-flex align-items-center justify-content-between mb-1.5 text-body-secondary" style="font-size:0.7rem;">
                        <span class="fw-semibold text-uppercase text-secondary" style="letter-spacing:0.04em;">
                            <i class="fas fa-lock text-secondary me-1"></i>Scope Checklist
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" style="font-size:0.68rem;">
                            <i class="fas fa-lock me-1"></i>Step Locked
                        </span>
                    </div>
                    <div class="alert alert-secondary py-1.5 px-2.5 mb-2 small text-body-secondary d-flex align-items-center gap-1.5 rounded-2" style="font-size:0.73rem;">
                        <i class="fas fa-lock text-muted"></i>
                        <span>${escapeHtml(stageLockReason)}</span>
                    </div>
                    <div class="d-flex flex-column gap-1.5 opacity-50" style="pointer-events: none; cursor: not-allowed;">
                        ${OFFICE_SCOPE_ITEMS.map((text) => `
                            <div class="scope-item d-flex align-items-center gap-2 py-1.5 px-2.5 rounded-2 border bg-body-tertiary" style="cursor: not-allowed;">
                                <i class="fas fa-circle-minus text-secondary opacity-50 flex-shrink-0" style="font-size:1.05rem;"></i>
                                <span class="small text-body-secondary" style="font-size:0.84rem; line-height:1.3;">
                                    ${escapeHtml(text)}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // State 1: No file — fully locked (grey)
        if (!hasFile) {
            return `
                <div class="scope-verification-box" id="scopeBox_${item.id}">
                    <div class="d-flex align-items-center justify-content-between mb-1.5 text-body-secondary" style="font-size:0.7rem;">
                        <span class="fw-semibold text-uppercase text-secondary" style="letter-spacing:0.04em;">
                            <i class="fas fa-lock text-secondary me-1"></i>Scope Checklist
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" style="font-size:0.68rem;">
                            <i class="fas fa-lock me-1"></i>Locked
                        </span>
                    </div>
                    <div class="alert alert-secondary py-1 px-2 mb-2 small text-body-secondary d-flex align-items-center gap-1.5 rounded-2" style="font-size:0.73rem;">
                        <i class="fas fa-info-circle text-muted"></i>
                        <span>Waiting for faculty to submit file.</span>
                    </div>
                    <div class="d-flex flex-column gap-1.5 opacity-50" style="pointer-events: none; cursor: not-allowed;">
                        ${OFFICE_SCOPE_ITEMS.map((text) => `
                            <div class="scope-item d-flex align-items-center gap-2 py-1.5 px-2.5 rounded-2 border bg-body-tertiary" style="cursor: not-allowed;">
                                <i class="fas fa-circle-minus text-secondary opacity-50 flex-shrink-0" style="font-size:1.05rem;"></i>
                                <span class="small text-body-secondary" style="font-size:0.84rem; line-height:1.3;">
                                    ${escapeHtml(text)}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // State 2: Scope confirmed & locked OR item cleared (green)
        if (confirmed || isCleared) {
            return `
                <div class="scope-verification-box" id="scopeBox_${item.id}">
                    <div class="d-flex align-items-center justify-content-between mb-1.5" style="font-size:0.7rem;">
                        <span class="fw-semibold text-uppercase text-success" style="letter-spacing:0.04em;">
                            <i class="fas fa-lock text-success me-1"></i>Scope Checklist
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size:0.68rem;">
                            <i class="fas fa-check-circle me-1"></i>Confirmed
                        </span>
                    </div>
                    <div class="alert alert-success py-1 px-2 mb-2 small text-success-emphasis d-flex align-items-center gap-1.5 rounded-2" style="font-size:0.73rem;">
                        <i class="fas fa-shield-check text-success"></i>
                        <span>Scope verification confirmed and locked.</span>
                    </div>
                    <div class="d-flex flex-column gap-1.5" style="pointer-events: none; user-select: none;">
                        ${OFFICE_SCOPE_ITEMS.map((text) => {
                const norm = text.toLowerCase();
                const isP = passedSet.has(norm);
                const isF = failedSet.has(norm);
                const iconClass = isF ? 'fas fa-times-circle text-danger' : (isP ? 'fas fa-check-circle text-success' : 'fas fa-circle-check text-secondary opacity-50');
                const textClass = isF ? 'text-danger fw-semibold' : (isP ? 'text-success fw-medium' : 'text-body-secondary');
                const cls = isF ? 'is-failed' : (isP ? 'is-passed' : '');
                return `
                                <div class="scope-item d-flex align-items-center gap-2 py-1.5 px-2.5 rounded-2 border ${cls}" style="pointer-events: none !important; cursor: default !important; user-select: none;">
                                    <i class="${iconClass} flex-shrink-0" style="font-size:1.05rem; pointer-events: none !important;"></i>
                                    <span class="small ${textClass}" style="font-size:0.84rem; line-height:1.3; pointer-events: none !important;">${escapeHtml(text)}</span>
                                </div>`;
            }).join('')}
                    </div>
                    ${isCleared ? `
                        <div class="d-flex align-items-center justify-content-center gap-1.5 py-1 px-2 mt-2 rounded bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size:0.75rem;">
                            <i class="fas fa-check-circle"></i> Digitally Signed &amp; Approved
                        </div>
                    ` : `
                        <button type="button"
                            class="btn btn-success btn-sm w-100 fw-semibold mt-2 shadow-sm"
                            style="font-size:0.78rem;"
                            onclick="openOfficeSignatureModal(${item.id})"
                            title="Provide digital signature to sign off and approve">
                            <i class="fas fa-signature me-1"></i>Sign &amp; Approve Clearance
                        </button>
                    `}
                </div>
            `;
        }

        // State 3: Editable — show checklist + Confirm & Lock button
        return `
            <div class="scope-verification-box" id="scopeBox_${item.id}">
                <div class="d-flex align-items-center justify-content-between mb-1.5 text-body-secondary" style="font-size:0.7rem;">
                    <span class="fw-semibold text-uppercase" style="letter-spacing:0.04em;">
                        <i class="fas fa-clipboard-check text-<?= $officeColor ?> me-1"></i>Scope Checklist
                    </span>
                    <span class="badge bg-body-tertiary text-body-secondary border px-2 py-0.5" style="font-size:0.68rem;">
                        <span class="text-success fw-bold">&#10003; 1-click</span> &bull; <span class="text-danger fw-bold">&#10005; 2-clicks</span>
                    </span>
                </div>
                <div class="d-flex flex-column gap-1.5 mb-2">
                    ${OFFICE_SCOPE_ITEMS.map((text, idx) => {
            const norm = text.toLowerCase();
            const isP = passedSet.has(norm);
            const isF = failedSet.has(norm);
            let iconClass = 'fas fa-circle-check text-secondary opacity-50';
            let textClass = 'text-body';
            let itemClass = '';
            let tip = 'Single-click to mark as Checked (Passed) &bull; Double-click to mark as ✕ (Deficient)';
            if (isF) { iconClass = 'fas fa-times-circle text-danger'; textClass = 'text-danger fw-semibold'; itemClass = 'is-failed'; tip = 'Flagged as Deficient &bull; Single-click to switch to Check &bull; Double-click to Reset'; }
            else if (isP) { iconClass = 'fas fa-check-circle text-success'; textClass = 'text-success fw-medium'; itemClass = 'is-passed'; tip = 'Verified & Complied &bull; Single-click to Reset &bull; Double-click to switch to ✕'; }
            return `
                            <div class="scope-item d-flex align-items-center gap-2 py-1.5 px-2.5 rounded-2 ${itemClass}"
                                 data-item-id="${item.id}" data-scope-index="${idx}"
                                 onclick="handleScopeItemClick(${item.id}, ${idx}, event)" title="${tip}">
                                <i class="${iconClass} flex-shrink-0" style="font-size:1.05rem;"></i>
                                <span class="small ${textClass}" style="font-size:0.84rem; line-height:1.3;">${escapeHtml(text)}</span>
                            </div>`;
        }).join('')}
                </div>
                ${(() => {
                    const allPassed = OFFICE_SCOPE_ITEMS.length > 0 && passedSet.size >= OFFICE_SCOPE_ITEMS.length && failedSet.size === 0;
                    const hasFailed = failedSet.size > 0;
                    const noneChecked = passedSet.size === 0 && failedSet.size === 0;
                    const btnDisabled = !allPassed;
                    let btnTitle = 'Confirm and permanently lock this scope verification';
                    if (noneChecked) btnTitle = 'You must check all scope items before confirming';
                    else if (hasFailed) btnTitle = 'Cannot lock: some items are flagged as deficient';
                    else if (!allPassed) btnTitle = `Cannot lock: ${passedSet.size} of ${OFFICE_SCOPE_ITEMS.length} items checked — all must be verified`;
                    return `<button type="button"
                    class="btn ${allPassed ? 'btn-outline-success' : 'btn-outline-secondary'} btn-sm w-100 fw-semibold mt-1"
                    style="font-size:0.78rem; ${btnDisabled ? 'opacity:0.55; cursor:not-allowed;' : ''}"
                    ${btnDisabled ? 'disabled' : `onclick="promptScopeLock(${item.id})"`}
                    title="${btnTitle}">
                    <i class="fas fa-lock me-1"></i>${allPassed ? 'Confirm &amp; Lock' : (noneChecked ? 'Check All Items First' : (hasFailed ? 'Resolve Deficiencies First' : `${passedSet.size}/${OFFICE_SCOPE_ITEMS.length} Items Checked`))}
                </button>`;
                })()}
            </div>
        `;
    }

    let _scopeClickTimers = {};

    function handleScopeItemClick(itemId, index, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        let currentItem = (currentReviewClearance?.items || []).find(it => it.id === itemId);
        const reqName = TARGET_REQUIREMENT_NAME || (currentItem ? currentItem.name : '');
        const currentStage = (currentReviewClearance?.stages && reqName) ? currentReviewClearance.stages[reqName] : null;
        if (currentStage && (currentStage.is_unlocked === false || currentStage.state === 'locked')) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 small';
                alertBox.innerHTML = `<i class="fas fa-lock me-1"></i>Cannot verify scope: ${escapeHtml(currentStage.lock_reason || 'This stage is locked until previous clearances are cleared.')}`;
                alertBox.classList.remove('d-none');
            }
            return;
        }
        const hasFile = Boolean(currentItem && (currentItem.file_name || currentItem.original_name || currentItem.file_path));
        if (!hasFile) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 small';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Cannot verify scope: The faculty member has not submitted a file for this requirement yet.';
                alertBox.classList.remove('d-none');
            }
            return;
        }
        if (isScopeConfirmed(currentItem)) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-info py-2 px-3 small';
                alertBox.innerHTML = '<i class="fas fa-lock me-1"></i>Scope verification has been confirmed and locked. No further changes are allowed.';
                alertBox.classList.remove('d-none');
            }
            return;
        }
        const key = `${itemId}_${index}`;
        if (_scopeClickTimers[key]) {
            clearTimeout(_scopeClickTimers[key]);
            delete _scopeClickTimers[key];
            executeScopeToggle(itemId, index, 'double');
        } else {
            _scopeClickTimers[key] = setTimeout(() => {
                delete _scopeClickTimers[key];
                executeScopeToggle(itemId, index, 'single');
            }, 260);
        }
    }

    async function executeScopeToggle(itemId, index, type) {
        const targetText = OFFICE_SCOPE_ITEMS[index];
        if (!targetText) return;
        const targetNorm = targetText.toLowerCase();

        let currentItem = (currentReviewClearance?.items || []).find(it => it.id === itemId);
        const hasFile = Boolean(currentItem && (currentItem.file_name || currentItem.original_name || currentItem.file_path));
        if (!hasFile) return;
        if (isScopeConfirmed(currentItem)) return;
        let scope = getParsedScopeState(currentItem);

        const wasPassed = scope.passed.some(s => s.toLowerCase() === targetNorm);
        const wasFailed = scope.failed.some(s => s.toLowerCase() === targetNorm);

        if (type === 'single') {
            if (wasPassed) {
                scope.passed = scope.passed.filter(s => s.toLowerCase() !== targetNorm);
            } else {
                scope.failed = scope.failed.filter(s => s.toLowerCase() !== targetNorm);
                scope.passed = scope.passed.filter(s => s.toLowerCase() !== targetNorm);
                scope.passed.push(targetText);
            }
        } else if (type === 'double') {
            if (wasFailed) {
                scope.failed = scope.failed.filter(s => s.toLowerCase() !== targetNorm);
            } else {
                scope.passed = scope.passed.filter(s => s.toLowerCase() !== targetNorm);
                scope.failed = scope.failed.filter(s => s.toLowerCase() !== targetNorm);
                scope.failed.push(targetText);
            }
        }

        // Optimistic UI update in modal
        if (currentItem) {
            const scopeJson = JSON.stringify(scope);
            currentItem.remarks = (currentItem.remarks || '').replace(/<!--SCOPE_STATE:.*?-->/g, '').trim() + ` <!--SCOPE_STATE:${scopeJson}-->`;
            if (scope.failed.length > 0) {
                currentItem.status = 'Denied';
            } else {
                currentItem.status = 'Under Review';
            }
            const box = document.getElementById(`scopeBox_${itemId}`);
            if (box) {
                box.outerHTML = renderScopeVerificationList(currentItem);
            }
        }

        const form = new FormData();
        form.append('action', 'update-scope');
        form.append('item_id', itemId);
        form.append('passed', JSON.stringify(scope.passed));
        form.append('failed', JSON.stringify(scope.failed));

        try {
            const resp = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await resp.json();
            if (!data.ok) throw new Error(data.error || 'Failed to update scope');

            if (currentReviewFacultyId) {
                await refreshReviewItems(currentReviewFacultyId);
            }
            loadTracking();
            loadArchives();
        } catch (err) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-2 px-3 small';
                alertBox.textContent = err.message;
                alertBox.classList.remove('d-none');
            }
            if (currentReviewFacultyId) {
                await refreshReviewItems(currentReviewFacultyId);
            }
        }
    }

    function promptScopeLock(itemId) {
        let currentItem = (currentReviewClearance?.items || []).find(it => it.id === itemId);
        const reqName = TARGET_REQUIREMENT_NAME || (currentItem ? currentItem.name : '');
        const currentStage = (currentReviewClearance?.stages && reqName) ? currentReviewClearance.stages[reqName] : null;
        if (currentStage && (currentStage.is_unlocked === false || currentStage.state === 'locked')) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 small';
                alertBox.innerHTML = `<i class="fas fa-lock me-1"></i>Cannot lock scope: ${escapeHtml(currentStage.lock_reason || 'This stage is locked until previous clearances are cleared.')}`;
                alertBox.classList.remove('d-none');
            }
            return;
        }
        const hasFile = Boolean(currentItem && (currentItem.file_name || currentItem.original_name || currentItem.file_path));
        if (!hasFile) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 small';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Cannot lock: The faculty member has not submitted a file yet.';
                alertBox.classList.remove('d-none');
            }
            return;
        }
        if (isScopeConfirmed(currentItem)) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-info py-2 px-3 small';
                alertBox.innerHTML = '<i class="fas fa-lock me-1"></i>Scope verification is already confirmed and locked.';
                alertBox.classList.remove('d-none');
            }
            return;
        }

        // Guard: all scope items must be passed (none failed, none unchecked)
        const scopeState = (() => {
            const rem = currentItem?.remarks || '';
            const m = rem.match(/<!--SCOPE_STATE:(.*?)-->/);
            if (m) { try { return JSON.parse(m[1]); } catch(e) {} }
            return null;
        })();
        const passedItems = scopeState?.passed || [];
        const failedItems = scopeState?.failed || [];
        const totalItems = (typeof OFFICE_SCOPE_ITEMS !== 'undefined') ? OFFICE_SCOPE_ITEMS.length : 0;
        const alertBox2 = document.getElementById('reviewAlert');
        if (failedItems.length > 0) {
            if (alertBox2) {
                alertBox2.className = 'alert alert-danger py-2 px-3 small';
                alertBox2.innerHTML = '<i class="fas fa-times-circle me-1"></i>Cannot confirm & lock: some scope items are flagged as deficient. Resolve all deficiencies first.';
                alertBox2.classList.remove('d-none');
            }
            return;
        }
        if (totalItems > 0 && passedItems.length < totalItems) {
            if (alertBox2) {
                alertBox2.className = 'alert alert-warning py-2 px-3 small';
                alertBox2.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Cannot confirm & lock: only ${passedItems.length} of ${totalItems} scope items have been verified. Please check all items first.`;
                alertBox2.classList.remove('d-none');
            }
            return;
        }

        const modal = document.getElementById('confirmScopeLockModal');
        if (modal) {
            document.getElementById('confirmScopeLockItemId').value = itemId;
            bootstrap.Modal.getOrCreateInstance(modal).show();
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length >= 2) {
                    backdrops[backdrops.length - 1].style.zIndex = '1075';
                }
            }, 50);
        }
    }

    async function executeScopeLock() {
        const itemId = parseInt(document.getElementById('confirmScopeLockItemId')?.value || 0);
        if (!itemId) return;

        const btn = document.getElementById('btnConfirmScopeLock');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Locking...'; }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmScopeLockModal')).hide();

        const form = new FormData();
        form.append('action', 'lock-scope');
        form.append('item_id', itemId);

        try {
            const resp = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await resp.json();
            if (!data.ok) throw new Error(data.error || 'Failed to lock scope.');

            let currentItem = (currentReviewClearance?.items || []).find(it => it.id === itemId);
            if (currentItem) {
                let scope = getParsedScopeState(currentItem);
                scope.locked = true;
                const scopeJson = JSON.stringify(scope);
                currentItem.remarks = (currentItem.remarks || '')
                    .replace(/<!--SCOPE_STATE:.*?-->/g, '')
                    .replace(/<!--SCOPE_CONFIRMED-->/g, '')
                    .trim() + ` <!--SCOPE_STATE:${scopeJson}-->`;
                const box = document.getElementById(`scopeBox_${itemId}`);
                if (box) box.outerHTML = renderScopeVerificationList(currentItem);
            }

            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-success py-2 px-3 small';
                alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i>Scope verification confirmed and locked successfully.';
                alertBox.classList.remove('d-none');
            }

            if (currentReviewFacultyId) await refreshReviewItems(currentReviewFacultyId);
            loadTracking();
            loadArchives();

            // Automatically open Digital Signature Modal after scope is locked
            setTimeout(() => {
                openOfficeSignatureModal(itemId);
            }, 300);
        } catch (err) {
            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-2 px-3 small';
                alertBox.textContent = err.message;
                alertBox.classList.remove('d-none');
            }
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-lock me-1"></i>Confirm'; }
        }
    }

    /* ── DIGITAL OFFICE SIGNATURE PAD LOGIC ── */
    let officeSignCanvas = null;
    let officeSignCtx = null;
    let officeSignDrawing = false;
    let officeSignHasDrawn = false;
    let officeSignLastPos = { x: 0, y: 0 };
    let officeSignPendingItemId = 0;

    function initOfficeSignaturePad() {
        officeSignCanvas = document.getElementById('officeSignCanvas');
        if (!officeSignCanvas) return;
        officeSignCtx = officeSignCanvas.getContext('2d');

        function resizeCanvas() {
            if (!officeSignCanvas) return;
            const rect = officeSignCanvas.getBoundingClientRect();
            if (rect.width > 0 && officeSignCanvas.width !== Math.round(rect.width)) {
                const temp = officeSignHasDrawn ? officeSignCanvas.toDataURL() : null;
                officeSignCanvas.width = Math.round(rect.width);
                officeSignCanvas.height = 150;
                if (temp) {
                    const img = new Image();
                    img.onload = () => {
                        if (officeSignCtx) officeSignCtx.drawImage(img, 0, 0);
                    };
                    img.src = temp;
                }
            }
        }

        function getPos(e) {
            const rect = officeSignCanvas.getBoundingClientRect();
            const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : e.clientX;
            const clientY = (e.touches && e.touches.length > 0) ? e.touches[0].clientY : e.clientY;
            const scaleX = officeSignCanvas.width / (rect.width || 1);
            const scaleY = officeSignCanvas.height / (rect.height || 1);
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }

        function startDraw(e) {
            if (e.type === 'touchstart') e.preventDefault();
            officeSignDrawing = true;
            officeSignLastPos = getPos(e);
            const placeholder = document.getElementById('officeSignPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
        }

        function isAppDarkMode() {
            return document.documentElement.getAttribute('data-theme') === 'dark'
                || document.documentElement.dataset.theme === 'dark'
                || document.documentElement.getAttribute('data-bs-theme') === 'dark'
                || document.documentElement.classList.contains('dark-mode')
                || document.body.classList.contains('dark-mode');
        }

        function draw(e) {
            if (!officeSignDrawing || !officeSignCtx) return;
            if (e.type === 'touchmove') e.preventDefault();
            const pos = getPos(e);
            officeSignCtx.beginPath();
            officeSignCtx.moveTo(officeSignLastPos.x, officeSignLastPos.y);
            officeSignCtx.lineTo(pos.x, pos.y);
            // Black pen in light mode, white pen in dark mode
            officeSignCtx.strokeStyle = isAppDarkMode() ? '#ffffff' : '#000000';
            officeSignCtx.lineWidth = 2.5;
            officeSignCtx.lineCap = 'round';
            officeSignCtx.lineJoin = 'round';
            officeSignCtx.stroke();
            officeSignLastPos = pos;
            officeSignHasDrawn = true;
        }

        function stopDraw(e) {
            if (officeSignDrawing) {
                if (e && e.type === 'touchend') e.preventDefault();
                officeSignDrawing = false;
            }
        }

        officeSignCanvas.onmousedown = startDraw;
        officeSignCanvas.onmousemove = draw;
        window.addEventListener('mouseup', stopDraw);

        officeSignCanvas.ontouchstart = startDraw;
        officeSignCanvas.ontouchmove = draw;
        window.addEventListener('touchend', stopDraw);

        resizeCanvas();
    }

    function clearOfficeSignature() {
        if (!officeSignCanvas || !officeSignCtx) {
            officeSignCanvas = document.getElementById('officeSignCanvas');
            if (officeSignCanvas) officeSignCtx = officeSignCanvas.getContext('2d');
        }
        if (officeSignCanvas && officeSignCtx) {
            officeSignCtx.clearRect(0, 0, officeSignCanvas.width, officeSignCanvas.height);
        }
        officeSignHasDrawn = false;
        const placeholder = document.getElementById('officeSignPlaceholder');
        if (placeholder) placeholder.style.display = 'block';
        const alertBox = document.getElementById('officeSignAlert');
        if (alertBox) alertBox.classList.add('d-none');
    }

    function openOfficeSignatureModal(itemId) {
        officeSignPendingItemId = itemId;
        const modalEl = document.getElementById('officeSignatureModal');
        if (!modalEl) return;

        clearOfficeSignature();
        const alertBox = document.getElementById('officeSignAlert');
        if (alertBox) alertBox.classList.add('d-none');

        const remarksInput = document.getElementById('officeSignRemarks');
        if (remarksInput) remarksInput.value = 'Approved and verified.';

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length >= 2) {
                backdrops[backdrops.length - 1].style.zIndex = '1082';
            }
            initOfficeSignaturePad();
        }, 150);
    }

    async function submitOfficeSignature() {
        if (!officeSignHasDrawn) {
            const alertBox = document.getElementById('officeSignAlert');
            if (alertBox) {
                alertBox.textContent = 'Please draw your digital signature before confirming approval.';
                alertBox.classList.remove('d-none');
            }
            return;
        }

        const btn = document.getElementById('btnSubmitOfficeSignature');
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Signing & Approving...';
        }

        try {
            const sigData = officeSignCanvas.toDataURL('image/png');
            const remarks = (document.getElementById('officeSignRemarks')?.value || '').trim() || 'Approved and verified.';

            const form = new FormData();
            form.append('action', 'office-approve-sign');
            form.append('faculty_id', currentReviewFacultyId);
            form.append('signature_data', sigData);
            form.append('remarks', remarks);
            if (typeof TARGET_REQUIREMENT_NAME !== 'undefined' && TARGET_REQUIREMENT_NAME) {
                form.append('office', TARGET_REQUIREMENT_NAME);
            }

            const resp = await fetch(clearanceApi, { method: 'POST', body: form });
            const data = await resp.json();
            if (!data.ok) throw new Error(data.error || 'Failed to approve and sign clearance.');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('officeSignatureModal')).hide();

            const alertBox = document.getElementById('reviewAlert');
            if (alertBox) {
                alertBox.className = 'alert alert-success py-2 px-3 small';
                alertBox.innerHTML = `<i class="fas fa-check-circle me-1"></i><strong>${escapeHtml(data.message || 'Clearance successfully approved and digitally signed.')}</strong>`;
                alertBox.classList.remove('d-none');
            }

            showTrackingAlert(data.message || 'Clearance approved and signed.', 'success');

            if (currentReviewFacultyId) {
                await refreshReviewItems(currentReviewFacultyId);
            }
            loadTracking();
            loadArchives();
        } catch (err) {
            const alertBox = document.getElementById('officeSignAlert');
            if (alertBox) {
                alertBox.textContent = err.message;
                alertBox.classList.remove('d-none');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml || '<i class="fas fa-check-circle me-1"></i>Sign &amp; Approve Clearance';
            }
        }
    }

    async function refreshReviewItems(facultyId) {
        try {
            const response = await fetch(`${clearanceApi}?action=review&faculty_id=${facultyId}`);
            const data = await response.json();
            if (!data.ok) return;
            const c = data.clearance;
            currentReviewClearance = c;
            const body = document.getElementById('reviewBody');
            if (!body) return;

            const targetItem = getTargetItem(c);
            const isCleared = targetItem && (targetItem.status === 'Cleared' || targetItem.status === 'Approved');
            const officeProgress = isCleared ? 100 : 0;
            const officeApprovedCount = isCleared ? 1 : 0;

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${officeProgress}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${officeProgress}% (${officeApprovedCount}/1)`;
            const progressSub = document.getElementById('summaryProgressSub');
            if (progressSub) progressSub.textContent = isCleared ? 'All requirements completed' : `${officeProgress}% completed`;

            // Update banner & stepper (Sequential lock aware)
            const reqName = TARGET_REQUIREMENT_NAME || (targetItem ? targetItem.name : '');
            const currentStage = (c.stages && reqName) ? c.stages[reqName] : null;
            const isStageLocked = currentStage ? (currentStage.is_unlocked === false || currentStage.state === 'locked') : false;
            const stageLockReason = (currentStage && currentStage.lock_reason) ? currentStage.lock_reason : 'Complete the previous clearance step to unlock this requirement.';

            const banner = document.getElementById('reviewSuccessBanner');
            if (banner) {
                if (isCleared) {
                    banner.className = 'alert alert-success d-flex align-items-center gap-3 p-3 rounded-3 border border-success-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-check"></i></div>
                    <span class="fw-semibold small text-success-emphasis">All requirements have been cleared.</span>`;
                } else if (isStageLocked) {
                    banner.className = 'alert alert-warning d-flex align-items-center gap-3 p-3 rounded-3 border border-warning-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-lock"></i></div>
                    <div>
                        <div class="fw-bold small text-warning-emphasis">Clearance Step Locked</div>
                        <div class="small text-warning-emphasis">${escapeHtml(stageLockReason)}</div>
                    </div>`;
                } else {
                    banner.className = 'alert alert-info d-flex align-items-center gap-3 p-3 rounded-3 border border-info-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-info"></i></div>
                    <span class="fw-semibold small text-info-emphasis">Clearance requirements are currently under review. Please review the submitted document.</span>`;
                }
            }

            const step2Circle = document.getElementById('stepperStep2Circle');
            const step2Sub = document.getElementById('stepperStep2Sub');
            const stepLine2 = document.getElementById('stepperLine2');
            if (isCleared) {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-success text-white'; step2Circle.innerHTML = '<i class="fas fa-check"></i>'; }
                if (step2Sub) step2Sub.textContent = 'Completed';
                if (stepLine2) stepLine2.style.background = '#198754';
            } else {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-primary text-white'; step2Circle.textContent = '2'; }
                if (step2Sub) step2Sub.textContent = 'In Progress';
                if (stepLine2) stepLine2.style.background = 'var(--bs-border-color)';
            }

            let refreshItems = c.items || [];
            if (TARGET_REQUIREMENT_NAME) {
                refreshItems = refreshItems.filter(item => item.name === TARGET_REQUIREMENT_NAME);
            }
            body.innerHTML = refreshItems.length ? refreshItems.map(item => {
                const hasFile = Boolean(item.file_name || item.original_name || item.file_path);
                const rawFileName = item.file_name || item.original_name || (item.file_path ? item.file_path.split('/').pop() : 'clearance-file.pdf');
                const isMissing = !hasFile && item.status !== 'Cleared';
                const statusLabel = isMissing ? 'Missing' : (item.display_status || item.status);
                const fileUrl = item.file_path
                    ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(item.file_path)}&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                    : `${clearanceApi}?action=file&download=1&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`;
                const viewUrl = item.file_path
                    ? `${clearanceApi}?action=file&path=${encodeURIComponent(item.file_path)}&item_id=${item.id || 0}&filename=${encodeURIComponent(rawFileName)}`
                    : (item.id ? `${clearanceApi}?action=file&item_id=${item.id}&filename=${encodeURIComponent(rawFileName)}` : null);
                const isItemCleared = item.status === 'Cleared' || item.status === 'Approved';

                return `<tr style="border-bottom:1px solid var(--bs-border-color-translucent);">
                <td class="ps-4 py-3 align-top">
                    <div>
                        <div class="fw-bold text-body-emphasis" style="font-size:0.875rem;">${escapeHtml(item.name || TARGET_REQUIREMENT_NAME || 'Office Clearance')}</div>
                        <small class="text-body-secondary" style="font-size:0.7rem;"><?= htmlspecialchars($officeLabel) ?></small>
                    </div>
                </td>
                <td class="py-3 align-top">
                    ${hasFile ? `
                    <div class="p-2 rounded-3 border bg-body-tertiary d-flex align-items-center gap-2" style="max-width:210px;">
                        <div class="rounded-2 bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                            <i class="fas fa-file-pdf text-danger" style="font-size:1rem;"></i>
                        </div>
                        <div style="min-width:0;">
                            <a href="${viewUrl || fileUrl}" target="_blank"
                               class="fw-semibold text-primary text-decoration-none d-block"
                               style="font-size:0.78rem;max-width:130px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;"
                               title="${escapeHtml(rawFileName)}">
                                ${escapeHtml(rawFileName)}
                            </a>
                            <div class="text-body-secondary" style="font-size:0.66rem;">
                                ${item.uploaded_at ? new Date(item.uploaded_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'recently'}
                            </div>
                        </div>
                    </div>
                    <a href="${fileUrl}" class="btn btn-outline-secondary btn-sm mt-1.5 w-100" style="font-size:0.72rem;max-width:210px;" title="Download file">
                        <i class="fas fa-download me-1"></i>Download
                    </a>` : `
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-dashed bg-body-tertiary" style="max-width:210px;">
                        <div class="rounded-2 bg-secondary-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                            <i class="fas fa-file-circle-xmark text-secondary" style="font-size:1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-body-secondary" style="font-size:0.78rem;">No file uploaded</div>
                            <div class="text-body-secondary" style="font-size:0.68rem;">Awaiting faculty submission</div>
                        </div>
                    </div>`}
                </td>
                <td class="py-3 align-top">
                    <span class="badge rounded-pill px-3 py-1.5 fw-semibold mb-1 d-inline-block"
                        style="font-size:0.75rem;
                        ${isMissing ? 'background:rgba(108,117,125,0.12);color:#6c757d;border:1px solid rgba(108,117,125,0.25);' :
                        isItemCleared ? 'background:rgba(25,135,84,0.12);color:#198754;border:1px solid rgba(25,135,84,0.3);' :
                            (item.status === 'Denied' || item.status === 'Hold') ? 'background:rgba(220,53,69,0.12);color:#dc3545;border:1px solid rgba(220,53,69,0.25);' :
                                'background:rgba(13,110,253,0.1);color:#0d6efd;border:1px solid rgba(13,110,253,0.2);'}">
                        <i class="fas ${isItemCleared ? 'fa-check-circle' : isMissing ? 'fa-circle-xmark' : (item.status === 'Denied' || item.status === 'Hold') ? 'fa-times-circle' : 'fa-hourglass-half'} me-1"></i>
                        ${escapeHtml(isItemCleared ? 'Cleared' : statusLabel)}
                    </span>
                    <div>${formatClearanceRemark(item.remarks, isMissing)}</div>
                </td>
                <td class="pe-4 py-3 align-top">
                    ${renderScopeVerificationList(item)}
                </td>
            </tr>`;
            }).join('') : `<tr><td colspan="4" class="text-center py-5">
                <div class="text-body-secondary">
                    <i class="fas fa-inbox fs-2 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold">No ${escapeHtml(TARGET_REQUIREMENT_NAME || 'Office Clearance')} submitted</div>
                    <small>This section will populate once the faculty submits their clearance.</small>
                </div>
            </td></tr>`;
        } catch (e) {
            // silent
        }
    }

    function formatClearanceRemark(remarks, isMissing = false) {
        if (!remarks || !String(remarks).trim()) {
            return `<small class="text-body-secondary fst-italic">${isMissing ? 'No file submitted' : 'No remark'}</small>`;
        }

        let raw = String(remarks).trim();
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
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            loadTracking();
            loadArchives();
        });
    } else {
        loadTracking();
        loadArchives();
    }
</script>