<?php
/**
 * SMS 2 - Clearance System
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Clearance System';
$activeModule = 'faculty';
$activePage   = 'clearance-system';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Clearance System', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Clearance System</h1>
    </div>
    <div class="d-flex gap-2">
        <select class="form-select border-secondary" style="width: 180px; max-width: 100%;">
                <option value="2025-2026-2s">2nd Semester, AY 2025-2026</option>
                <option value="2025-2026-1s">1st Semester, AY 2025-2026</option>
            </select>
            <button class="btn btn-outline-secondary btn-sm px-3 d-flex align-items-center gap-2">
                <i class="fas fa-chart-bar"></i> Export Report
            </button>
        </div>
</div>

<!-- Analytical Overview Cards (Adaptive Structure) -->
<div class="row g-3 mb-4 dashboard-stats">
        <!-- Total Clearances -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-folder-plus"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Total Clearances</h6>
                        <h4 class="mb-0 fw-bold">142</h4>
                    </div>
                </div>
            </section>
        </div>

        <!-- Pending Review -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning fs-4"><i class="fas fa-spinner"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Pending Review</h6>
                        <h4 class="mb-0 fw-bold">28</h4>
                    </div>
                </div>
            </section>
        </div>

        <!-- With Holds -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card danger border shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-danger fs-4"><i class="fas fa-exclamation-circle"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small">With Holds</h6>
                        <h4 class="mb-0 fw-bold">5</h4>
                    </div>
                </div>
            </section>
        </div>

        <!-- Fully Cleared -->
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Fully Cleared</h6>
                        <h4 class="mb-0 fw-bold">109</h4>
                    </div>
                </div>
            </section>
        </div>
    </div>

<!-- Main Datatable Card Container (Adaptive Theme colors) -->
<div class="card shadow-sm border">
    
    <!-- Table Control Toolbar -->
    <div class="card-header border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0 fw-bold">Clearance Status Matrix</h5>
            <span class="badge bg-primary rounded-pill">Active Period</span>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text border-secondary">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="clearanceSearch" class="form-control border-secondary" placeholder="Search faculty name or ID..." onkeyup="filterClearanceTable()"/>
            </div>
            <select id="statusFilter" class="form-select form-select-sm border-secondary w-auto" onchange="filterClearanceTable()">
                <option value="all">All Statuses</option>
                <option value="Cleared">Cleared</option>
                <option value="In Progress">In Progress</option>
                <option value="With Hold">With Hold</option>
            </select>
        </div>
    </div>

        <!-- System Clearance Breakdown Grid Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <!-- Removed table-dark to allow theme-driven table inheritance -->
                <table class="table table-hover align-middle mb-0" id="clearanceTable">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 60px;" class="ps-3">Profile</th>
                            <th style="width: 150px;">Faculty ID</th>
                            <th style="width: 220px;">Faculty Member</th>
                            <th style="width: 100px;">Dept</th>
                            <th>Clearance Status Progress</th>
                            <th style="width: 140px;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        
                        <!-- Row Template 1: In Progress Structure -->
                        <tr data-status="In Progress">
                            <td class="ps-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 13px; min-width: 38px;">
                                    JD
                                </div>
                            </td>
                            <td class="fw-bold">FAC-2026-001</td>
                            <td>
                                <div class="fw-bold">John Doe</div>
                                <div class="text-muted small">Head Faculty</div>
                            </td>
                            <td><span class="badge bg-secondary px-2 py-1">BSIT</span></td>
                            <td>
                                <!-- Node Milestone System Indicators (Using Light Mode Safe Subtle Badges) -->
                                <div class="d-flex align-items-center gap-3 py-1">
                                <div class="d-flex gap-2 align-items-center">
                                    <!-- Cleared: Deep translucent green tint -->
                                    <span class="badge border border-success border-opacity-25 text-success bg-success bg-opacity-10 fw-medium px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Dean <i class="fas fa-check ms-1" style="font-size: 9px;"></i>
                                    </span>
                                    
                                    <span class="badge border border-success border-opacity-25 text-success bg-success bg-opacity-10 fw-medium px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Lib <i class="fas fa-check ms-1" style="font-size: 9px;"></i>
                                    </span>
                                    
                                    <!-- Pending: Deep translucent amber tint -->
                                    <span class="badge border border-warning border-opacity-25 text-warning bg-warning bg-opacity-10 fw-medium px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Prop <i class="fas fa-hourglass-half ms-1" style="font-size: 9px;"></i>
                                    </span>
                                    
                                    <!-- Neutral/Locked: Subdued gray outline -->
                                    <span class="badge border border-secondary border-opacity-25 text-muted bg-transparent fw-normal px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        HR <i class="far fa-circle ms-1" style="font-size: 9px;"></i>
                                    </span>
                                </div>
                                    <div class="progress flex-grow-1 bg-opacity-25" style="height: 6px; max-width: 120px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-warning fw-bold">50%</small>
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-info" onclick="viewClearanceDetails('FAC-2026-001')" title="Review Requirements">View Tracking</button>
                            </td>
                        </tr>

                        <!-- Row Template 2: With Hold Alert Breakdown -->
                        <tr data-status="With Hold">
                            <td class="ps-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info text-dark fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 13px; min-width: 38px;">
                                    JS
                                </div>
                            </td>
                            <td class="fw-bold">FAC-2026-002</td>
                            <td>
                                <div class="fw-bold">Jane Smith</div>
                                <div class="text-muted small">Regular Instructor</div>
                            </td>
                            <td><span class="badge bg-secondary px-2 py-1">BSTM</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-3 py-1">
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge border border-success border-opacity-25 text-success bg-success bg-opacity-10 fw-medium px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Dean <i class="fas fa-check ms-1" style="font-size: 9px;"></i>
                                    </span>
                                    
                                    <!-- Critical Hold: Subdued dark crimson tint -->
                                    <span class="badge border border-danger border-opacity-25 text-danger bg-danger bg-opacity-10 fw-medium px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Lib <i class="fas fa-exclamation-triangle ms-1" style="font-size: 9px;"></i> Hold
                                    </span>
                                    
                                    <span class="badge border border-secondary border-opacity-25 text-muted bg-transparent fw-normal px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        Prop <i class="far fa-circle ms-1" style="font-size: 9px;"></i>
                                    </span>
                                    
                                    <span class="badge border border-secondary border-opacity-25 text-muted bg-transparent fw-normal px-2 py-1.5" style="font-size: 11px; letter-spacing: 0.3px;">
                                        HR <i class="far fa-circle ms-1" style="font-size: 9px;"></i>
                                    </span>
                                </div>
                                    <div class="progress flex-grow-1 bg-opacity-25" style="height: 6px; max-width: 120px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-danger fw-bold">Blocked</small>
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-danger" onclick="viewClearanceDetails('FAC-2026-002')" title="View Conflict Hold Details">Resolve Hold</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Architecture: Uses default adaptive background variables instead of forcing hard dark classes -->
<div id="clearanceDetailModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Node Clearance Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div id="modalAvatarInitials" class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 50px; height: 50px;">--</div>
                    <div>
                        <h4 class="h6 mb-1 fw-bold" id="modalFacultyName">Faculty Member Name</h4>
                        <span class="badge bg-secondary text-light small" id="modalFacultyId">FAC-XXXX-XXX</span>
                    </div>
                </div>

                <label class="form-label text-muted small text-uppercase tracking-wider fw-bold">Department Milestones</label>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold small">1. Dean & Academic Records Office</div>
                            <small class="text-muted">Final grade encoding sheets submitted</small>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3">Cleared</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold small">2. Campus Library Center</div>
                            <small class="text-muted">No borrowed resource tokens pending</small>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3">Cleared</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold small">3. Property Custodian Office</div>
                            <small class="text-muted">Pending return verification of assigned IT asset laptop</small>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill px-3">Pending Review</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close Overlay</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="alert('Notification ping sent!')">Ping Action Items</button>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filtering Execution JavaScript Context -->
<script>
    function viewClearanceDetails(id) {
        // Mocking dynamic structural data assignment based on targets clicked
        const modal = new bootstrap.Modal(document.getElementById('clearanceDetailModal'));
        if(id === 'FAC-2026-001') {
            document.getElementById('modalFacultyName').innerText = "John Doe";
            document.getElementById('modalFacultyId').innerText = "FAC-2026-001";
            document.getElementById('modalAvatarInitials').innerText = "JD";
            document.getElementById('modalAvatarInitials').className = "rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5";
        } else if (id === 'FAC-2026-002') {
            document.getElementById('modalFacultyName').innerText = "Jane Smith";
            document.getElementById('modalFacultyId').innerText = "FAC-2026-002";
            document.getElementById('modalAvatarInitials').innerText = "JS";
            document.getElementById('modalAvatarInitials').className = "rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold fs-5";
        }
        modal.show();
    }

    function filterClearanceTable() {
        const input = document.getElementById("clearanceSearch").value.toUpperCase();
        const statusFilter = document.getElementById("statusFilter").value;
        const table = document.getElementById("clearanceTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let row = tr[i];
            if (!row) continue;
            
            let textMatch = row.textContent.toUpperCase().indexOf(input) > -1;
            let statusAttr = row.getAttribute("data-status") || "";
            let statusMatch = (statusFilter === "all") || (statusAttr === statusFilter);

            if (textMatch && statusMatch) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>