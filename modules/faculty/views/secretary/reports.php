<?php
/**
 * Reports
 * Purpose: Generate daily, attendance, and document status reports
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Reports';
$activeModule = 'faculty';
$activePage   = 'reports';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// Mock Data for Generated Reports History
$reports = [
    ['name' => 'Daily Attendance Report - August 1, 2025', 'type' => 'Daily', 'by' => 'Secretary', 'date' => 'Today, 5:00 PM', 'format' => 'PDF'],
    ['name' => 'Monthly Attendance Report - July 2025', 'type' => 'Attendance', 'by' => 'Secretary', 'date' => 'Yesterday', 'format' => 'Excel'],
    ['name' => 'Document Status Summary - July 2025', 'type' => 'Document', 'by' => 'Secretary', 'date' => '2 days ago', 'format' => 'PDF'],
    ['name' => 'Daily Activity Log - July 31, 2025', 'type' => 'Daily', 'by' => 'Secretary', 'date' => '3 days ago', 'format' => 'PDF'],
    ['name' => 'Expiring Documents Report - August 2025', 'type' => 'Document', 'by' => 'Secretary', 'date' => '4 days ago', 'format' => 'Excel'],
    ['name' => 'Attendance Rate by Faculty - July 2025', 'type' => 'Attendance', 'by' => 'Secretary', 'date' => '5 days ago', 'format' => 'PDF'],
];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<style>
    .hover-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
    }
    .hover-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    }
    .card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
    }

    /* Dark-Theme Pill Badges for Report Types */
    .badge-type-daily {
        background-color: #0b1d3a !important;
        color: #4da3ff !important;
        border: 1px solid #163e75 !important;
    }

    .badge-type-attendance {
        background-color: #0d2822 !important;
        color: #2be49b !important;
        border: 1px solid #14533c !important;
    }

    .badge-type-document {
        background-color: #311c08 !important;
        color: #f3a833 !important;
        border: 1px solid #63360b !important;
    }

    /* Dark-Theme Pill Badges for Formats */
    .badge-format-pdf {
        background-color: #2d1215 !important;
        color: #ff5263 !important;
        border: 1px solid #5a1e24 !important;
    }

    .badge-format-excel {
        background-color: #0d2822 !important;
        color: #2be49b !important;
        border: 1px solid #14533c !important;
    }

    .badge-format-csv {
        background-color: #22222a !important;
        color: #b0b0cc !important;
        border: 1px solid #3c3c4d !important;
    }

    /* Feature Item Checklist Style */
    .report-feature-item {
        font-size: 0.85rem;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-1">
            <i class="fas fa-file-alt text-purple me-2"></i>Reports
        </h2>
        <p class="text-muted small mb-0">Generate daily, attendance, and document status reports</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customReportModal">
            <i class="fas fa-plus me-1"></i>Custom Report Builder
        </button>
    </div>
</div>

<!-- Report Type Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 hover-card shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-primary-subtle text-primary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Daily Reports</h6>
                        <small class="text-muted">Real-time operational summaries</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="report-feature-item"><i class="fas fa-check-circle text-primary"></i>Daily Attendance Report</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-primary"></i>Daily Activity Log</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-primary"></i>Daily Leave Summary</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-primary"></i>Daily Document Updates</li>
                    </ul>
                </div>
                <button class="btn btn-outline-primary w-100 fw-semibold" onclick="generateReport('daily')">
                    <i class="fas fa-cog me-1"></i>Generate Daily
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 hover-card shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-success-subtle text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Attendance Reports</h6>
                        <small class="text-muted">Faculty presence analytics</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="report-feature-item"><i class="fas fa-check-circle text-success"></i>Monthly Attendance Report</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-success"></i>Attendance Rate by Faculty</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-success"></i>Attendance Trends</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-success"></i>Absenteeism Report</li>
                    </ul>
                </div>
                <button class="btn btn-outline-success w-100 fw-semibold" onclick="generateReport('attendance')">
                    <i class="fas fa-cog me-1"></i>Generate Attendance
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 hover-card shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-warning-subtle text-warning">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Document Reports</h6>
                        <small class="text-muted">Compliance & status logs</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="report-feature-item"><i class="fas fa-check-circle text-warning"></i>Document Status Summary</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-warning"></i>Expiring Documents Report</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-warning"></i>Missing Documents Report</li>
                        <li class="report-feature-item"><i class="fas fa-check-circle text-warning"></i>Document Audit Trail</li>
                    </ul>
                </div>
                <button class="btn btn-outline-warning text-dark w-100 fw-semibold" onclick="generateReport('document')">
                    <i class="fas fa-cog me-1"></i>Generate Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report History Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history text-purple me-2"></i>Generated Reports History</h6>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control" placeholder="Search reports...">
            </div>
            <select class="form-select form-select-sm w-auto">
                <option value="">All Types</option>
                <option>Daily</option>
                <option>Attendance</option>
                <option>Document</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Report Name</th>
                        <th>Type</th>
                        <th>Generated By</th>
                        <th>Date Generated</th>
                        <th>Format</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): 
                        $typeBadge = match($r['type']) {
                            'Daily'      => 'badge-type-daily',
                            'Attendance' => 'badge-type-attendance',
                            default      => 'badge-type-document'
                        };

                        $formatBadge = match($r['format']) {
                            'PDF'   => 'badge-format-pdf',
                            'Excel' => 'badge-format-excel',
                            default => 'badge-format-csv'
                        };
                    ?>
                    <tr>
                        <td class="ps-3 fw-semibold text-dark"><?= $r['name'] ?></td>
                        <td><span class="badge <?= $typeBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $r['type'] ?></span></td>
                        <td class="small text-muted"><?= $r['by'] ?></td>
                        <td class="small font-monospace text-muted"><?= $r['date'] ?></td>
                        <td><span class="badge <?= $formatBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $r['format'] ?></span></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary border-0" title="View" onclick="viewReport('<?= $r['name'] ?>')">
                                    <i class="fas fa-eye text-primary"></i>
                                </button>
                                <button class="btn btn-outline-secondary border-0" title="Download" onclick="downloadReport('<?= $r['name'] ?>')">
                                    <i class="fas fa-download text-success"></i>
                                </button>
                                <button class="btn btn-outline-secondary border-0" title="Delete" onclick="deleteReport('<?= $r['name'] ?>')">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">Showing 1-6 of 18 reports</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Prev</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Generate Report Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-cog text-purple me-2"></i>Generate Standard Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Report Category</label>
                    <select class="form-select form-select-sm" id="reportType">
                        <option value="daily">Daily Report</option>
                        <option value="attendance">Attendance Report</option>
                        <option value="document">Document Report</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Specific Report</label>
                    <select class="form-select form-select-sm" id="specificReport">
                        <option>Daily Attendance Report</option>
                        <option>Daily Activity Log</option>
                        <option>Daily Leave Summary</option>
                        <option>Daily Document Updates</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Date Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" id="startDate">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" id="endDate">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Output Format</label>
                    <select class="form-select form-select-sm">
                        <option>PDF</option>
                        <option>Excel</option>
                        <option>CSV</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="confirmGenerate()"><i class="fas fa-cog me-1"></i>Generate Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Report Modal -->
<div class="modal fade" id="customReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-sliders-h text-purple me-2"></i>Custom Report Builder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Report Title</label>
                        <input type="text" class="form-control form-control-sm" placeholder="e.g., Q3 Faculty Leave & Attendance Summary">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold d-block">Data Sources</label>
                    <div class="row g-2 p-3 bg-light rounded-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds1" checked>
                                <label class="form-check-label small" for="ds1">Attendance Records</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds2" checked>
                                <label class="form-check-label small" for="ds2">Leave & Absence Data</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds3">
                                <label class="form-check-label small" for="ds3">Document Compliance Data</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds4">
                                <label class="form-check-label small" for="ds4">Faculty Profile Information</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Department Filter</label>
                        <select class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <option selected>College of Computer Studies</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Faculty Status</label>
                        <select class="form-select form-select-sm">
                            <option value="">All Faculty Types</option>
                            <option>Full-time Faculty</option>
                            <option>Part-time Faculty</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Output Format</label>
                        <select class="form-select form-select-sm">
                            <option>PDF Document (.pdf)</option>
                            <option>Excel Spreadsheet (.xlsx)</option>
                            <option>CSV Format (.csv)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary"><i class="fas fa-magic me-1"></i>Generate Custom Report</button>
            </div>
        </div>
    </div>
</div>

<script>
function generateReport(type) {
    const modal = new bootstrap.Modal(document.getElementById('generateModal'));
    document.getElementById('reportType').value = type;
    modal.show();
}

function confirmGenerate() {
    alert('Report generation process initiated. You will be notified once ready for download.');
    bootstrap.Modal.getInstance(document.getElementById('generateModal')).hide();
}

function viewReport(name) {
    alert('Viewing report preview: ' + name);
}

function downloadReport(name) {
    alert('Downloading report: ' + name);
}

function deleteReport(name) {
    if (confirm('Are you sure you want to delete this historical report entry: ' + name + '?')) {
        alert('Report log entry removed.');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>