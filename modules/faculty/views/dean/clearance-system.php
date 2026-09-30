<?php
/** Dean clearance tracking and view (read-only, rich Dept-Head UI style). */
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
if (!in_array(getCurrentUserRoleKey(), ['dean'], true)) {
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
$activePage = 'clearance-system';
$breadcrumbs = [['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'], ['label' => 'Faculty Clearance', 'url' => null]];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<?php renderBreadcrumbs($breadcrumbs); ?>
<style>
    /* Scope of Verification Checklist in Review Modal */
    .scope-verification-box {
        min-width: 290px;
    }

    .scope-item {
        user-select: none;
        transition: background-color 0.15s ease, border-color 0.15s ease;
        border: 1px solid var(--bs-border-color-translucent, #e9ecef);
        background-color: var(--bs-body-bg, #ffffff);
        cursor: default;
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
        border: 1px solid #212529 !important;
        border-radius: 6px !important;
        background-color: #ffffff;
    }

    .clearance-btn-group a {
        color: #212529 !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        padding: 0.25rem 0.85rem !important;
        line-height: 1.4 !important;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .clearance-btn-group a:hover {
        background-color: #f1f3f5 !important;
    }

    .clearance-btn-group a:first-child {
        border-right: 1px solid #212529 !important;
    }

    /* Clearance Archive Table */
    .clearance-table thead th {
        letter-spacing: 0.03em;
        font-size: 0.74rem;
        font-weight: 600;
        color: #495057;
        background-color: #ffffff;
        border-bottom: 1px solid #dee2e6;
        border-right: 1px solid #eef2f6;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }

    .clearance-table thead th:last-child {
        border-right: none;
    }

    .clearance-table tbody td {
        padding-top: 0.95rem;
        padding-bottom: 0.95rem;
        vertical-align: middle;
    }

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

    .clr-status-chip:not(.active) .badge-secondary {
        background-color: #e5e7eb;
        color: #6b7280;
    }

    /* Archive Detail Status Pills (Light Mode) */
    .clr-pill-approved {
        background-color: #e8f5e9;
        border: 1px solid #b2dfdb;
        color: #1e7e34;
    }

    .clr-pill-missing {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #6c757d;
    }

    .clr-pill-denied {
        background-color: #fdeded;
        border: 1px solid #f5c2c7;
        color: #dc3545;
    }

    /* Dark Mode Overrides */
    [data-theme="dark"] .clearance-btn-group,
    [data-bs-theme="dark"] .clearance-btn-group {
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        background-color: rgba(255, 255, 255, 0.06) !important;
    }

    [data-theme="dark"] .clearance-btn-group a,
    [data-bs-theme="dark"] .clearance-btn-group a {
        color: #e2e8f0 !important;
    }

    [data-theme="dark"] .clearance-btn-group a:hover,
    [data-bs-theme="dark"] .clearance-btn-group a:hover {
        background-color: rgba(255, 255, 255, 0.14) !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .clearance-btn-group a:first-child,
    [data-bs-theme="dark"] .clearance-btn-group a:first-child {
        border-right: 1px solid rgba(255, 255, 255, 0.22) !important;
    }

    [data-theme="dark"] .clearance-table thead th,
    [data-bs-theme="dark"] .clearance-table thead th {
        color: #94a3b8;
        background-color: rgba(255, 255, 255, 0.04);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-right: 1px solid rgba(255, 255, 255, 0.06);
    }

    [data-theme="dark"] .clearance-table tbody td,
    [data-bs-theme="dark"] .clearance-table tbody td {
        color: #cbd5e1;
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }

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

    [data-theme="dark"] .clr-status-chip:not(.active) .badge-secondary,
    [data-bs-theme="dark"] .clr-status-chip:not(.active) .badge-secondary {
        background-color: rgba(148, 163, 184, 0.2);
        color: #cbd5e1;
    }

    [data-theme="dark"] .clr-pill-approved,
    [data-bs-theme="dark"] .clr-pill-approved {
        background-color: rgba(34, 197, 94, 0.18) !important;
        border-color: rgba(34, 197, 94, 0.35) !important;
        color: #4ade80 !important;
    }

    [data-theme="dark"] .clr-pill-missing,
    [data-bs-theme="dark"] .clr-pill-missing {
        background-color: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #94a3b8 !important;
    }

    [data-theme="dark"] .clr-pill-denied,
    [data-bs-theme="dark"] .clr-pill-denied {
        background-color: rgba(239, 68, 68, 0.18) !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
        color: #f87171 !important;
    }

    [data-theme="dark"] #archiveRequirementsBody {
        background-color: transparent !important;
    }

    /* Stepper Styles */
    .horizontal-clearance-stepper .stepper-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .horizontal-clearance-stepper .stepper-divider {
        height: 2px;
        background: var(--bs-border-color);
        transition: background-color 0.2s ease;
    }
</style>
<div class="container-fluid p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-primary text-uppercase small fw-bold mb-1">
                Dean Account
            </p>
            <h3 class="fw-bold text-body-emphasis mb-1">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Faculty Clearance Portal
            </h3>
            <p class="text-body-secondary small mb-0">
                View access to faculty clearance records across all departments, track overall progress, and review
                completed archives.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary btn-sm btn-md-normal w-100 w-sm-auto" onclick="refreshCurrentTab()">
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
                    <h6 class="fw-bold mb-0 text-body-emphasis">
                        <i class="fas fa-list-ul me-2 text-primary"></i>Ongoing Faculty Clearance Records
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
                            <span class="input-group-text bg-body-secondary text-body-secondary"><i
                                    class="fas fa-search"></i></span>
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
                                <th>Overall Progress</th>
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
                            <small class="text-body-secondary">Official record of all completed clearances and approved
                                documents across departments.</small>
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
                                <td colspan="6" class="text-center text-body-secondary py-5">Loading archived clearance
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
<!-- REVIEW MODAL FOR ACTIVE CLEARANCE (DEAN VIEW ONLY) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1240px;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header / Navy Topbar -->
            <div class="modal-header py-3 px-4 text-white d-flex align-items-center justify-content-between"
                style="background: linear-gradient(135deg, #0b345f 0%, #0d2847 100%);">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div
                        class="d-flex align-items-center gap-2 text-white-50 small bg-white bg-opacity-10 px-3 py-1 rounded-pill flex-shrink-0">
                        <i class="fas fa-eye text-white"></i>
                        <span class="text-white fw-medium">Dean Portal</span>
                    </div>
                </div>
                <button class="btn-close btn-close-white ms-3 flex-shrink-0" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Top Section: Title & Top Summary Stats Card -->
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold text-body-emphasis mb-1" id="reviewMainTitle">Faculty Clearance</h4>
                        <p class="text-body-secondary small mb-0" id="reviewMainSubtitle">Official record and
                            verification status of faculty clearance requirements.</p>
                        <span class="d-none" id="reviewTitle"></span>
                        <span class="d-none" id="reviewMeta"></span>
                    </div>
                    <!-- Stats Card (3 Segments) -->
                    <div class="card bg-body border rounded-3 shadow-none p-3" style="min-width: 360px;">
                        <div class="row g-3 align-items-center text-body">
                            <div class="col-12 col-sm-5 border-end-sm border-body-subtle pe-sm-3">
                                <div>
                                    <small class="text-body-secondary d-block" style="font-size:0.73rem;">Current
                                        Contract Expiry</small>
                                    <span class="fw-bold text-body-emphasis small d-block"
                                        id="summaryContractExpiry">—</span>
                                    <small class="d-block" id="summaryDaysRemaining" style="font-size:0.7rem;"></small>
                                </div>
                            </div>
                            <div class="col-12 col-sm-3 border-end-sm border-body-subtle px-sm-2">
                                <small class="text-body-secondary d-block mb-1" style="font-size:0.73rem;">Employment
                                    Status</small>
                                <span
                                    class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1 rounded-pill fw-semibold"
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
                                    style="font-size:0.7rem;">Clearance items</small>
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
                                    id="stepperStep2Title">Office Reviews &amp; Approvals</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;" id="stepperStep2Sub">In
                                    Progress</div>
                            </div>
                        </div>
                        <div class="stepper-divider flex-grow-1 mx-2" id="stepperLine2" style="min-width:24px;"></div>
                        <!-- Step 3 -->
                        <div class="d-flex align-items-center gap-2 flex-shrink-0" id="stepperStep4">
                            <div class="stepper-circle bg-body-secondary text-body-secondary" id="stepperStep4Circle">3
                            </div>
                            <div>
                                <div class="fw-semibold small text-body-secondary" style="font-size:0.8rem;"
                                    id="stepperStep4Title">Completed &amp; Cleared</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;" id="stepperStep4Sub">Not yet
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Full-Width Grid -->
                <div class="row g-4">
                    <div class="col-12">
                        <!-- Status Banner -->
                        <div id="reviewSuccessBanner"
                            class="alert alert-info d-flex align-items-center gap-3 p-3 rounded-3 border border-info-subtle mb-4">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-info"></i></div>
                            <span class="fw-semibold small text-info-emphasis" id="reviewSuccessBannerText">Clearance
                                requirements are currently under review.</span>
                        </div>

                        <!-- System alerts -->
                        <div id="reviewAlert" class="alert d-none mb-3"></div>

                        <!-- Clearance Form Status Card (Read-Only) -->
                        <div class="card border rounded-3 mb-4 shadow-sm" id="agreementFormReviewCard">
                            <div
                                class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="fw-bold small text-uppercase"><i
                                        class="fas fa-file-contract text-primary me-2"></i>Clearance Form Status</span>
                                <span id="agreementFormStatusBadge"
                                    class="badge bg-secondary-subtle text-body-secondary border">Not Submitted</span>
                            </div>
                            <div class="card-body p-3" id="agreementFormReviewBody"></div>
                        </div>

                        <!-- Clearance Requirements Section -->
                        <div class="card border-0 rounded-4 mb-4 shadow-sm overflow-hidden">
                            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between"
                                style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width:32px;height:32px;font-size:0.9rem;">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-body-emphasis" style="font-size:0.9rem;">Clearance
                                            Requirements (All Offices)</h6>
                                        <small class="text-body-secondary" style="font-size:0.72rem;">Inspect submitted
                                            files, office verification status, and verification scopes</small>
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
                                                style="font-size:0.68rem;letter-spacing:0.06em;">
                                                <i
                                                    class="fas fa-folder-open me-1 text-primary opacity-75"></i>Requirement
                                                / Office
                                            </th>
                                            <th class="py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;">
                                                <i class="fas fa-paperclip me-1 text-primary opacity-75"></i>File
                                                Attachment
                                            </th>
                                            <th class="py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;">
                                                <i class="fas fa-circle-dot me-1 text-primary opacity-75"></i>Status
                                            </th>
                                            <th class="pe-4 py-2.5 text-uppercase fw-semibold text-body-secondary"
                                                style="font-size:0.68rem;letter-spacing:0.06em;min-width:290px;">
                                                <i class="fas fa-clipboard-check me-1 text-primary opacity-75"></i>Scope
                                                of Verification
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="reviewBody" class="text-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Faculty Declaration Card (Read-Only) -->
                        <div class="card border rounded-3 mb-4 shadow-sm" id="facultyDeclarationReviewCard">
                            <div
                                class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="fw-bold small text-uppercase">
                                    <i class="fas fa-file-signature text-primary me-2"></i>Faculty Declaration &amp;
                                    Digital Signature
                                </span>
                                <span id="declarationReviewBadge"
                                    class="badge bg-secondary-subtle text-body-secondary border px-2 py-1">
                                    <i class="fas fa-lock me-1"></i>Pending Document Approvals
                                </span>
                            </div>
                            <div class="card-body p-3" id="declarationReviewBody"></div>
                        </div>

                        <!-- Transaction Summary Card -->
                        <div class="card border rounded-3 shadow-sm mb-4">
                            <div
                                class="card-header bg-body-tertiary py-2.5 px-3 border-bottom d-flex align-items-center gap-2">
                                <i class="fas fa-receipt text-primary"></i>
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

            <!-- Modal Footer: View Only -> Close button only! -->
            <div class="modal-footer bg-body-tertiary border-top d-flex justify-content-end align-items-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- ARCHIVE DETAIL MODAL (SAME RICH DESIGN) -->
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
                                <strong class="text-body-emphasis" id="archiveFacultyName">-</strong>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Faculty ID No.</small>
                                <span id="archiveFacultyNo">-</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Department</small>
                                <span id="archiveDepartment">-</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <small class="text-body-secondary d-block">Academic Rank</small>
                                <span id="archiveRank">-</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <small class="text-body-secondary d-block">Contract Expiration Date</small>
                                <strong class="text-success" id="archiveContractEnd">-</strong>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <small class="text-body-secondary d-block">Employment Status</small>
                                <span id="archiveEmpStatus">-</span>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <small class="text-body-secondary d-block">Contact Email</small>
                                <span id="archiveEmail" class="small">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clearance Requirements Table -->
                <h6 class="fw-bold text-uppercase small text-body-secondary mb-3"><i
                        class="fas fa-tasks me-1 text-success"></i> Approved Clearance Requirements</h6>
                <div class="table-responsive mb-3 border rounded-2 overflow-hidden">
                    <table class="table clearance-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th style="min-width: 190px;">REQUIREMENT</th>
                                <th style="min-width: 290px;">SUBMITTED FILE ATTACHMENT</th>
                                <th style="min-width: 140px;">STATUS</th>
                                <th style="min-width: 220px;">REVIEWER NOTE</th>
                                <th class="pe-3" style="min-width: 140px;">CLEARED DATE</th>
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
    let trackingRows = [];
    let archiveRows = [];
    let reviewModal;
    let archiveDetailModal;
    let currentReviewFacultyId = null;
    let currentReviewProfile = null;
    let currentReviewClearance = null;
    let activeStatusGroup = 'all';
    let currentPage = 1;
    const trackingPageSize = 8;

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
        if (body) body.innerHTML = '<tr><td colspan="6" class="text-center text-body-secondary py-5"><i class="fas fa-spinner fa-spin me-2"></i>Loading archived clearance records...</td></tr>';

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
                body.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">
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
        const container = document.getElementById('statusControlsContainer');
        if (!container) return;

        const groups = [
            ['all', 'All Active', 'dark'],
            ['pending', 'Pending Verification', 'primary'],
            ['action', 'Denied / Resubmission', 'danger'],
            ['not-submitted', 'Not Submitted', 'secondary'],
        ];

        container.innerHTML = `<div class="d-flex flex-wrap gap-2 align-items-center">` +
            groups.map(([key, label, badgeColor]) => {
                const count = key === 'all'
                    ? trackingRows.length
                    : trackingRows.filter(row => statusGroupFor(row) === key).length;
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

    function statusGroupFor(row) {
        const status = row.clearance?.status || 'Not Submitted';
        if (status === 'Pending Verification' || status === 'Under Review' || status === 'Locked' || status === 'For Final Approval' || status === 'For Department Head Approval') return 'pending';
        if (status === 'Action Required' || status === 'Resubmission' || status === 'With Deficiency') return 'action';
        if (status === 'Completed' || status === 'Approved' || status === 'Archived' || status === 'Cleared') return 'completed';
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

                    const progressPct = c.progress || 0;
                    const approvedCount = c.approved_items || 0;
                    const totalCount = c.total_items || 0;
                    const progressTone = (progressPct === 100) ? 'success' : (progressPct > 0 ? 'primary' : 'secondary');

                    const rowNameEsc = escapeHtml(row.name || 'Unknown');

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
                        <div class="progress mb-1" style="height:7px">
                            <div class="progress-bar bg-${progressTone}" style="width:${progressPct}%"></div>
                        </div>
                        <small class="text-body-secondary">${progressPct}% (${approvedCount}/${totalCount})</small>
                    </td>
                    <td><span class="badge bg-${tone}-subtle text-${tone} border border-${tone}-subtle px-2 py-1">${escapeHtml(cStatus)}</span></td>
                    <td>${submittedDateStr}</td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="openReview(${row.id})" title="View Clearance Details">
                                <i class="fas fa-eye me-1"></i>View
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
            const text = `${row.name} ${row.faculty_no} ${row.designated_department}`.toLowerCase();
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
            body.innerHTML = '<tr><td colspan="6" class="text-center text-body-secondary py-5"><i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>No archived clearance records found matching your filters.</td></tr>';
        } else {
            body.innerHTML = visibleRows.map(row => {
                const expiry = row.contractual_end && row.contractual_end !== '0000-00-00'
                    ? new Date(`${row.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                    : '-';
                const clearedAt = row.updated_at
                    ? new Date(row.updated_at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '-';

                const allItems = row.items || [];
                const reqTags = allItems.map(it => {
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

            document.getElementById('archiveModalTitle').innerHTML = `<i class="fas fa-archive me-2"></i>Archived Record - ${escapeHtml(r.name)}`;
            document.getElementById('archiveModalMeta').textContent = `${r.faculty_no || ''} · ${r.designated_department || 'Department'}`;
            document.getElementById('archiveModalTerm').textContent = `Academic Term: ${r.academic_year} · ${r.semester}`;
            document.getElementById('archiveModalCompletedAt').textContent = r.completed_at || r.updated_at ? new Date((r.completed_at || r.updated_at).replace(' ', 'T')).toLocaleString() : '-';

            document.getElementById('archiveFacultyName').textContent = r.name;
            document.getElementById('archiveFacultyNo').textContent = r.faculty_no || '-';
            document.getElementById('archiveDepartment').textContent = r.designated_department || '-';
            document.getElementById('archiveRank').textContent = r.academic_rank || r.position || '-';
            document.getElementById('archiveContractEnd').textContent = r.contractual_end && r.contractual_end !== '0000-00-00' ? new Date(`${r.contractual_end}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            document.getElementById('archiveEmpStatus').textContent = r.employment_status || '-';
            document.getElementById('archiveEmail').textContent = r.email || '-';

            const body = document.getElementById('archiveRequirementsBody');
            const displayItems = r.items || [];

            if (!displayItems.length) {
                body.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-body-secondary">
                    <i class="fas fa-info-circle me-2 text-info"></i>No clearance items recorded in this archive.
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
                        <div class="fw-normal text-body-emphasis" style="font-size: 0.92rem; line-height: 1.35;">${escapeHtml(it.name || 'Clearance Item')}</div>
                        <div class="text-body-secondary" style="font-size: 0.82rem;">${escapeHtml(it.office_name || 'Office')}</div>
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
                        <span class="badge rounded-pill clr-pill-missing d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
                            <i class="fas fa-minus" style="font-size: 0.75rem;"></i> Missing
                        </span>` : (it.status === 'Cleared' ? `
                        <span class="badge rounded-pill clr-pill-approved d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
                            <i class="fas fa-check" style="font-size: 0.78rem;"></i> Approved
                        </span>` : `
                        <span class="badge rounded-pill clr-pill-denied d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-medium" style="font-size: 0.84rem;">
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

    /* ACTIVE CLEARANCE REVIEW LOGIC (DEAN VIEW-ONLY) */
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

            const empStatus = profile.employment_status || 'Probationary';

            // Top Header: Title & Subtitle
            const mainTitle = document.getElementById('reviewMainTitle');
            if (mainTitle) mainTitle.textContent = `${empStatus} Employee Clearance`;
            const mainSub = document.getElementById('reviewMainSubtitle');
            if (mainSub) mainSub.textContent = 'Official review and verification status of faculty clearance requirements.';

            // Stats Card: Contract Expiry
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
                empEl.className = `badge rounded-pill ${empStatus === 'Regular' ? 'bg-success-subtle text-success border border-success-subtle' : (empStatus === 'Probationary' ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-secondary-subtle text-body-secondary border')} px-2.5 py-1 fw-semibold`;
            }

            // Stats Card: Overall Progress
            const progressPct = c.progress || 0;
            const approvedCount = c.approved_items || 0;
            const totalCount = c.total_items || 0;
            const isCompleted = (totalCount > 0 && approvedCount >= totalCount) || c.status === 'Completed' || c.status === 'Cleared';

            const progressBar = document.getElementById('summaryProgressBar');
            if (progressBar) progressBar.style.width = `${progressPct}%`;
            const progressText = document.getElementById('summaryProgressText');
            if (progressText) progressText.textContent = `${progressPct}% (${approvedCount}/${totalCount})`;
            const progressSub = document.getElementById('summaryProgressSub');
            if (progressSub) progressSub.textContent = isCompleted ? 'All requirements completed' : `${approvedCount} of ${totalCount} items approved`;

            // Transaction Summary Card
            const txnId = c.clearance_no || ('TRX-' + (new Date().getFullYear()) + '-' + String(c.clearance_id || 1).padStart(6, '0'));
            const empFullName = `${(profile.last_name || '').toUpperCase()}, ${(profile.first_name || '').toUpperCase()} ${profile.middle_name ? profile.middle_name.charAt(0) + '.' : ''}`.trim();
            const posTitle = profile.employment_status ? `${profile.employment_status} Employee` : (profile.rank || 'Faculty Member');
            const subDateFormatted = c.created_at ? new Date(c.created_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
            const updDateFormatted = c.updated_at ? new Date(c.updated_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : subDateFormatted;

            const elTxnId = document.getElementById('reviewTxnId'); if (elTxnId) elTxnId.textContent = txnId;
            const elTxnEmp = document.getElementById('reviewTxnEmployee'); if (elTxnEmp) elTxnEmp.textContent = empFullName;
            const elTxnPos = document.getElementById('reviewTxnPosition'); if (elTxnPos) elTxnPos.textContent = posTitle;
            const elTxnSub = document.getElementById('reviewTxnSubmitted'); if (elTxnSub) elTxnSub.textContent = subDateFormatted;
            const elTxnUpd = document.getElementById('reviewTxnUpdated'); if (elTxnUpd) elTxnUpd.textContent = updDateFormatted;

            // Horizontal Stepper (3 steps)
            const step2Circle = document.getElementById('stepperStep2Circle');
            const step2Sub = document.getElementById('stepperStep2Sub');
            const stepLine2 = document.getElementById('stepperLine2');
            const step4Circle = document.getElementById('stepperStep4Circle');
            const step4Sub = document.getElementById('stepperStep4Sub');

            if (isCompleted) {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-success text-white'; step2Circle.innerHTML = '<i class="fas fa-check"></i>'; }
                if (step2Sub) step2Sub.textContent = 'Completed';
                if (stepLine2) stepLine2.style.background = '#198754';
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-success text-white'; step4Circle.innerHTML = '<i class="fas fa-check"></i>'; }
                if (step4Sub) step4Sub.textContent = 'Cleared';
            } else if (approvedCount > 0) {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-primary text-white'; step2Circle.textContent = '2'; }
                if (step2Sub) step2Sub.textContent = 'In Progress';
                if (stepLine2) stepLine2.style.background = 'var(--bs-border-color)';
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-body-secondary text-body-secondary'; step4Circle.textContent = '3'; }
                if (step4Sub) step4Sub.textContent = 'Pending';
            } else {
                if (step2Circle) { step2Circle.className = 'stepper-circle bg-primary text-white'; step2Circle.textContent = '2'; }
                if (step2Sub) step2Sub.textContent = 'Under Review';
                if (stepLine2) stepLine2.style.background = 'var(--bs-border-color)';
                if (step4Circle) { step4Circle.className = 'stepper-circle bg-body-secondary text-body-secondary'; step4Circle.textContent = '3'; }
                if (step4Sub) step4Sub.textContent = 'Not yet';
            }

            // Success Status Banner
            const banner = document.getElementById('reviewSuccessBanner');
            if (banner) {
                if (isCompleted) {
                    banner.className = 'alert alert-success d-flex align-items-center gap-3 p-3 rounded-3 border border-success-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-check"></i></div>
                    <span class="fw-semibold small text-success-emphasis">All clearance requirements have been officially cleared and verified.</span>`;
                } else {
                    banner.className = 'alert alert-info d-flex align-items-center gap-3 p-3 rounded-3 border border-info-subtle mb-4';
                    banner.innerHTML = `<div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:0.85rem;"><i class="fas fa-info"></i></div>
                    <span class="fw-semibold small text-info-emphasis">Clearance requirements are currently under review by designated office signers.</span>`;
                }
            }

            // Clearance Agreement Form Review Section (Read-Only)
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
                                <span class="badge bg-success text-white px-3 py-2"><i class="fas fa-check me-1"></i>Endorsed by Department Head</span>
                            </div>
                        </div>
                    `;
                } else if (isFormSub && formSt === 'Pending Review') {
                    formBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
                    formBadge.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Pending Department Head Review';
                    formBody.innerHTML = `
                        <div class="p-3 bg-warning-subtle bg-opacity-25 rounded border border-warning-subtle">
                            <h6 class="fw-bold text-warning-emphasis mb-1"><i class="fas fa-hourglass-half me-1"></i>Awaiting Endorsement</h6>
                            <div class="small text-body-secondary mb-1">Submitted on <strong>${formDate}</strong> with agreement acknowledgment.</div>
                            <div class="small text-body-secondary fst-italic">&ldquo;I hereby acknowledge and agree that I have complied with the rules, regulations, policies, and professional standards of the institution.&rdquo;</div>
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

            // Table rows - All clearance items (Read-Only)
            const body = document.getElementById('reviewBody');
            const visibleItems = c.items || [];
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
                const isItemCleared = item.status === 'Cleared' || item.status === 'Approved';

                return `<tr style="border-bottom:1px solid var(--bs-border-color-translucent);">
                <td class="ps-4 py-3 align-top">
                    <div>
                        <div class="fw-bold text-body-emphasis" style="font-size:0.875rem;">${escapeHtml(item.name || 'Clearance Requirement')}</div>
                        <small class="text-body-secondary" style="font-size:0.7rem;">${escapeHtml(item.office_name || 'Designated Office')}</small>
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
                                ${item.uploaded_at ? new Date(item.uploaded_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Uploaded recently'}
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
                    ${renderScopeVerificationReadOnly(item)}
                </td>
            </tr>`;
            }).join('') : `<tr><td colspan="4" class="text-center py-5">
                <div class="text-body-secondary">
                    <i class="fas fa-inbox fs-2 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold">No clearance requirements found</div>
                    <small>Requirements will populate once the clearance checklist is initialized.</small>
                </div>
            </td></tr>`;

            // Faculty Declaration Section (Read-Only)
            const declBadge = document.getElementById('declarationReviewBadge');
            const declBody = document.getElementById('declarationReviewBody');
            if (declBadge && declBody) {
                const hasSig = !!c.signature_data;

                if (hasSig) {
                    declBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
                    declBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Signed &amp; Submitted';
                    declBody.innerHTML = `
                        <div class="p-3 bg-success-subtle bg-opacity-25 rounded border border-success-subtle">
                            <div class="d-flex flex-column flex-md-row gap-4 align-items-md-start">
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-success-emphasis mb-2">
                                        <i class="fas fa-check-circle me-2"></i>Faculty Declaration Completed
                                    </div>
                                    <p class="text-body-secondary small fst-italic mb-3 ps-2 border-start border-success-subtle border-3">
                                        &ldquo;I hereby certify that I have completed and submitted the required documents and have returned any school property, records, or other accountable items assigned to me.&rdquo;
                                    </p>
                                    <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                        <span><i class="fas fa-user text-primary me-1"></i><strong>${escapeHtml(profile.first_name + ' ' + (profile.last_name || ''))}</strong></span>
                                        <span><i class="fas fa-calendar-check text-primary me-1"></i>Declaration signed during clearance submission</span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-center p-3 bg-white rounded-3 border shadow-sm" style="min-width:200px;">
                                    <div class="small text-body-secondary fw-semibold text-uppercase mb-2" style="font-size:0.65rem;letter-spacing:.06em;">
                                        <i class="fas fa-signature text-primary me-1"></i>Digital Signature
                                    </div>
                                    <img src="${c.signature_data}" alt="Faculty Digital Signature"
                                        class="rounded mb-2"
                                        style="max-width:180px;max-height:80px;object-fit:contain;display:block;margin:0 auto;border:1px solid #dee2e6;padding:6px;background:#fff;">
                                    <div class="small text-success fw-semibold">
                                        <i class="fas fa-check-circle me-1"></i>Verified &amp; Signed
                                    </div>
                                    <div class="small text-body-secondary mt-1" style="font-size:0.7rem;">
                                        ${escapeHtml(profile.first_name + ' ' + (profile.last_name || ''))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (isCompleted) {
                    declBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
                    declBadge.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Awaiting Faculty Signature';
                    declBody.innerHTML = `
                        <div class="p-3 bg-warning-subtle bg-opacity-25 rounded border border-warning-subtle d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                                <i class="fas fa-pen-clip"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-warning-emphasis">All documents approved awaiting faculty signature</div>
                                <div class="small text-body-secondary">
                                    All clearance items have been cleared. Awaiting faculty declaration signature.
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    declBadge.className = 'badge bg-secondary-subtle text-body-secondary border px-2 py-1';
                    declBadge.innerHTML = '<i class="fas fa-lock me-1"></i>Pending Document Approvals';
                    declBody.innerHTML = `
                        <div class="p-3 text-center">
                            <i class="fas fa-lock text-secondary mb-2" style="font-size:1.6rem;opacity:.4;"></i>
                            <div class="fw-bold text-body-emphasis small mb-1">Digital Signature Locked</div>
                            <div class="small text-body-secondary">
                                The Faculty Declaration unlocks once all required office documents are cleared.
                                <span class="d-block mt-1">
                                    <strong>${approvedCount}</strong> of <strong>${totalCount}</strong> document${totalCount !== 1 ? 's' : ''} approved so far.
                                </span>
                            </div>
                        </div>
                    `;
                }
            }

            reviewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal'));
            reviewModal.show();
        } catch (error) {
            showTrackingAlert(error.message, 'danger');
        }
    }

    function renderScopeVerificationReadOnly(item) {
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

        const isCleared = item && (item.status === 'Cleared' || item.status === 'Approved');
        const hasScopeEntries = scope.passed.length > 0 || scope.failed.length > 0;

        if (!hasScopeEntries && isCleared) {
            return `
                <div class="scope-verification-box">
                    <div class="d-flex align-items-center gap-1.5 py-1 px-2.5 rounded bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size:0.75rem;">
                        <i class="fas fa-check-circle"></i> Requirement Verified &amp; Cleared
                    </div>
                </div>
            `;
        }

        if (!hasScopeEntries) {
            return `
                <div class="scope-verification-box text-body-secondary small" style="font-size:0.78rem;">
                    <span class="fst-italic opacity-75">No verification checklist items specified</span>
                </div>
            `;
        }

        return `
            <div class="scope-verification-box">
                <div class="d-flex flex-column gap-1">
                    ${scope.passed.map(p => `
                        <div class="scope-item is-passed d-flex align-items-center gap-2 py-1 px-2 rounded-2 border">
                            <i class="fas fa-check-circle text-success flex-shrink-0" style="font-size:0.9rem;"></i>
                            <span class="small text-success fw-medium" style="font-size:0.78rem; line-height:1.3;">${escapeHtml(p)}</span>
                        </div>
                    `).join('')}
                    ${scope.failed.map(f => `
                        <div class="scope-item is-failed d-flex align-items-center gap-2 py-1 px-2 rounded-2 border">
                            <i class="fas fa-times-circle text-danger flex-shrink-0" style="font-size:0.9rem;"></i>
                            <span class="small text-danger fw-semibold" style="font-size:0.78rem; line-height:1.3;">${escapeHtml(f)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function formatClearanceRemark(remarks, isMissing = false) {
        if (!remarks || !String(remarks).trim()) {
            return `<small class="text-body-secondary fst-italic">${isMissing ? 'No file submitted' : ''}</small>`;
        }

        let raw = String(remarks).trim();
        raw = raw.replace(/<!--SCOPE_STATE:.*?-->/g, '').trim();
        raw = raw.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '').trim();
        raw = raw.replace(/Deficiencies Flagged:[\s\S]*?(?=Instructions:|$)/i, '')
            .replace(/Complied:[\s\S]*?(?=Instructions:|$)/i, '')
            .replace(/^Instructions:\s*/i, '')
            .trim();

        if (!raw) {
            return `<small class="text-body-secondary fst-italic">${isMissing ? 'No file submitted' : ''}</small>`;
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
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>