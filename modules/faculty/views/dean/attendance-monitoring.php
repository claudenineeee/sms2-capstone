<?php
/**
 * SMS 2 - Attendance Monitoring
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Attendance Monitoring';
$activeModule = 'faculty';
$activePage   = 'attendance-monitoring';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Attendance Monitoring', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold text-body"><i class="fas fa-clipboard-check text-primary me-2"></i>HR Faculty Attendance & Compliance Verification</h1>
        <p class="text-muted small mb-0">Review finalized department attendance submissions, inspector sign-offs, and room utilization audits.</p>
    </div>
    <div class="d-flex gap-2 ctrl-buttons flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary py-2 px-3 fw-bold" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Export HR Audit Summary
        </button>
        <button type="button" id="btnAnalyzeAI" class="btn btn-sm btn-success py-2 px-3 fw-bold">
            <i class="fas fa-brain me-2"></i>Run Cross-Department AI Audit
        </button>
    </div>
</div>

<!-- Global HR Filter & Department Status Summary Bar -->
<div class="card bg-body border-secondary-subtle p-3 mb-4 shadow-sm">
    <div class="row g-3 align-items-center">
        <div class="col-12 col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">Audit Date</label>
            <input type="date" id="auditDate" class="form-control bg-body border-secondary-subtle text-body" value="2026-07-16">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">Campus Location</label>
            <select id="campusLocation" class="form-select bg-body border-secondary-subtle text-body">
                <option value="MV Campus (Main)" selected>MV Campus</option>
                <option value="Main Campus">Main Campus</option>
                <option value="Bulacan Campus">Bulacan Campus</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">Filter Department</label>
            <select id="departmentFilter" class="form-select bg-body border-secondary-subtle text-body">
                <option value="ALL" selected>All Departments</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Teacher Education">Teacher Education</option>
                <option value="Engineering">Engineering</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">Sheet Status</label>
            <select id="statusFilter" class="form-select bg-body border-secondary-subtle text-body">
                <option value="ALL" selected>All Statuses</option>
                <option value="Finalized">Finalized & Signed</option>
                <option value="Pending">Pending Sign-off</option>
            </select>
        </div>
    </div>
</div>

<!-- Executive Summary Quick Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-secondary-subtle p-3 shadow-sm bg-body">
            <div class="text-muted small fw-bold text-uppercase">Total Classes Audited</div>
            <div class="h3 fw-bold text-body mb-0" id="statTotalClasses">3</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-secondary-subtle p-3 shadow-sm bg-body">
            <div class="text-muted small fw-bold text-uppercase">Finalized Sheets</div>
            <div class="h3 fw-bold text-success mb-0" id="statFinalized">3 / 3</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-secondary-subtle p-3 shadow-sm bg-body">
            <div class="text-muted small fw-bold text-uppercase">Student Footprint</div>
            <div class="h3 fw-bold text-primary mb-0" id="statStudents">113</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-secondary-subtle p-3 shadow-sm bg-body">
            <div class="text-muted small fw-bold text-uppercase">Flagged Capacity Risks</div>
            <div class="h3 fw-bold text-warning mb-0" id="statWarnings">1</div>
        </div>
    </div>
</div>

<!-- AI Insights Container -->
<div class="row g-4 d-none mb-4" id="aiInsightsSection">
    <div class="col-12">
        <div class="card border-success bg-body shadow-sm">
            <div class="card-header bg-success bg-opacity-10 text-success border-success-subtle d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-brain me-2"></i> HR Executive AI Insights & Attendance Integrity Audit</h6>
                <span class="badge bg-success">Automated Compliance Report</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4 border-end border-secondary-subtle">
                        <span class="d-block text-muted small fw-bold mb-2">HR COMPLIANCE SUMMARY</span>
                        <p class="text-body small" id="aiSummaryText">
                            Scanning cross-departmental records...
                        </p>
                    </div>
                    <div class="col-12 col-md-8">
                        <span class="d-block text-danger small fw-bold mb-2">DEPARTMENTAL ANOMALIES & AUDIT FLAGS</span>
                        <ul class="list-group list-group-flush small" id="aiFlagsList">
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Attendance Registry Matrix -->
<div class="card bg-body border-secondary-subtle p-3 shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-body mb-0">
            <i class="fas fa-clipboard-list me-2 text-muted"></i>Master Departmental Attendance Verification Sheet
        </h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
            <i class="fas fa-lock me-1"></i> Finalized Submissions Only
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-secondary-subtle mb-0" style="font-size: 0.875rem;">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Department</th>
                    <th scope="col">Faculty Member</th>
                    <th scope="col">Room</th>
                    <th scope="col">Subject / Course</th>
                    <th scope="col">Time Slot</th>
                    <th scope="col" class="text-end">Attending Students</th>
                    <th scope="col" class="text-center">Secretary Verification</th>
                    <th scope="col" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody id="hrMonitoringTableBody" class="text-body">
                <!-- Pre-submitted, Secretary-Signed Entries -->
                <tr data-dept="Information Technology">
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">IT Dept</span></td>
                    <td class="fw-bold">Dr. Earl Salvame</td>
                    <td>Room 403-B</td>
                    <td>SIA-201</td>
                    <td>09:30 AM</td>
                    <td class="text-end fw-bold text-primary">38</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border p-1" title="Signed by Department Secretary">
                            <i class="fas fa-signature text-primary me-1"></i> Appr. Sec. Maria
                        </span>
                    </td>
                    <td class="text-center"><span class="badge bg-success">Verified</span></td>
                </tr>
                <tr data-dept="Information Technology">
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold"">IT Dept</span></td>
                    <td class="fw-bold">Prof. Juan Dela Cruz</td>
                    <td>Room 501</td>
                    <td>SDF-101</td>
                    <td>11:00 AM</td>
                    <td class="text-end fw-bold text-primary">42</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border p-1" title="Signed by Department Secretary">
                            <i class="fas fa-signature text-primary me-1"></i> Appr. Sec. Maria
                        </span>
                    </td>
                    <td class="text-center"><span class="badge bg-success">Verified</span></td>
                </tr>
                <tr data-dept="Teacher Education">
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">Teacher Ed</span></td>
                    <td class="fw-bold">Dr. Aj</td>
                    <td>Room 102-Sci</td>
                    <td>PHY-101</td>
                    <td>02:00 PM</td>
                    <td class="text-end fw-bold text-primary">33</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border p-1" title="Signed by Department Secretary">
                            <i class="fas fa-signature text-primary me-1"></i> Appr. Sec. John
                        </span>
                    </td>
                    <td class="text-center"><span class="badge bg-success">Verified</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const departmentFilter = document.getElementById("departmentFilter");
    const hrMonitoringTableBody = document.getElementById("hrMonitoringTableBody");
    const btnAnalyzeAI = document.getElementById("btnAnalyzeAI");

    // Filter Table Rows by Department
    if (departmentFilter) {
        departmentFilter.addEventListener("change", function () {
            const selectedDept = this.value;
            const rows = hrMonitoringTableBody.querySelectorAll("tr");

            rows.forEach(row => {
                const rowDept = row.getAttribute("data-dept");
                if (selectedDept === "ALL" || rowDept === selectedDept) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }

    // HR Executive AI Engine
    if (btnAnalyzeAI) {
        btnAnalyzeAI.addEventListener("click", function () {
            const insightsSection = document.getElementById("aiInsightsSection");
            const summaryText = document.getElementById("aiSummaryText");
            const flagsList = document.getElementById("aiFlagsList");

            insightsSection.classList.remove("d-none");
            summaryText.innerHTML = `<div class="spinner-border spinner-border-sm text-success me-2"></div>Aggregating multi-department datasets...`;
            flagsList.innerHTML = "";

            setTimeout(() => {
                summaryText.innerHTML = `All <strong>3 finalized records</strong> across <strong>Information Technology</strong> and <strong>Teacher Education</strong> have passed departmental sign-off validation for July 16, 2026. Faculty workload allocation is optimal.`;

                flagsList.innerHTML = `
                    <li class="list-group-item bg-transparent text-body border-0 px-0 py-1">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i> 
                        <strong>High Occupancy Alert (Information Technology):</strong> Dr. Earl Salvame's session in Room 403-B reached 95% capacity (38/40). Recommend reviewing larger room availability for subsequent terms.
                    </li>
                    <li class="list-group-item bg-transparent text-body border-0 px-0 py-1">
                        <i class="fas fa-check-circle text-success me-2"></i> 
                        <strong>100% Departmental Compliance:</strong> Both IT and Teacher Education Secretaries submitted signed logs prior to the 5:00 PM HR cutoff.
                    </li>
                `;
            }, 1200);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>