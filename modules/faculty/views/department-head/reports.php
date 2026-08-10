<?php
/**
 * Reports
 * Purpose: Generate and view various department reports
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Reports';
$activeModule = 'faculty';
$activePage   = 'reports';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-file-alt text-purple me-2"></i>Reports</h1>
        <p class="text-muted mb-0">Generate and view department, faculty, and schedule reports</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-sms-primary" data-bs-toggle="modal" data-bs-target="#customReportModal"><i class="fas fa-plus me-1"></i>Custom Report</button>
    </div>
</div>

<!-- Report Type Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 hover-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-primary-subtle text-primary"><i class="fas fa-building"></i></div>
                    <div>
                        <h6 class="mb-0">Department Reports</h6>
                        <small class="text-muted">Department-level summaries</small>
                    </div>
                </div>
                <ul class="list-unstyled mb-3">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Department Performance Summary</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Faculty Load Distribution</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Attendance Report</li>
                    <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Schedule Overview</li>
                </ul>
                <button class="btn btn-outline-primary w-100" onclick="generateReport('department')"><i class="fas fa-cog me-1"></i>Generate</button>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 hover-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-success-subtle text-success"><i class="fas fa-user-tie"></i></div>
                    <div>
                        <h6 class="mb-0">Faculty Reports</h6>
                        <small class="text-muted">Individual faculty reports</small>
                    </div>
                </div>
                <ul class="list-unstyled mb-3">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Faculty Performance Report</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Teaching Load History</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Leave History</li>
                    <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Attendance Record</li>
                </ul>
                <button class="btn btn-outline-success w-100" onclick="generateReport('faculty')"><i class="fas fa-cog me-1"></i>Generate</button>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 hover-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-warning-subtle text-warning"><i class="fas fa-calendar"></i></div>
                    <div>
                        <h6 class="mb-0">Schedule Reports</h6>
                        <small class="text-muted">Schedule and conflict reports</small>
                    </div>
                </div>
                <ul class="list-unstyled mb-3">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Master Schedule</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Conflict Report</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Room Utilization</li>
                    <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Faculty Schedule Matrix</li>
                </ul>
                <button class="btn btn-outline-warning w-100" onclick="generateReport('schedule')"><i class="fas fa-cog me-1"></i>Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- Report History -->
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-history text-purple me-2"></i>Generated Reports History</h6>
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" placeholder="Search reports..." style="width: 200px;">
            <select class="form-select form-select-sm w-auto">
                <option value="">All Types</option>
                <option>Department</option>
                <option>Faculty</option>
                <option>Schedule</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Report Name</th>
                        <th>Type</th>
                        <th>Generated By</th>
                        <th>Date Generated</th>
                        <th>Format</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reports = [
                        ['name'=>'Department Performance Summary - 2nd Sem 2025','type'=>'Department','by'=>'Dept. Head','date'=>'Today, 10:30 AM','format'=>'PDF'],
                        ['name'=>'Faculty Load Distribution - 1st Sem 2025','type'=>'Department','by'=>'Dept. Head','date'=>'Yesterday','format'=>'Excel'],
                        ['name'=>'Dr. Maria Santos Performance Report','type'=>'Faculty','by'=>'Dept. Head','date'=>'2 days ago','format'=>'PDF'],
                        ['name'=>'Master Schedule - 2nd Sem 2025','type'=>'Schedule','by'=>'Schedule Officer','date'=>'3 days ago','format'=>'PDF'],
                        ['name'=>'Conflict Report - Week 32','type'=>'Schedule','by'=>'Schedule Officer','date'=>'4 days ago','format'=>'Excel'],
                        ['name'=>'Faculty Attendance Report - July 2025','type'=>'Department','by'=>'Secretary','date'=>'5 days ago','format'=>'PDF'],
                    ];
                    foreach ($reports as $r) {
                        $typeBadge = $r['type'] === 'Department' ? 'bg-primary' : ($r['type'] === 'Faculty' ? 'bg-success' : 'bg-warning');
                        echo <<<HTML
                        <tr>
                            <td><strong>{$r['name']}</strong></td>
                            <td><span class="badge {$typeBadge}">{$r['type']}</span></td>
                            <td>{$r['by']}</td>
                            <td class="small text-muted">{$r['date']}</td>
                            <td><span class="badge bg-secondary">{$r['format']}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="View" onclick="viewReport('{$r['name']}')"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-outline-success" title="Download" onclick="downloadReport('{$r['name']}')"><i class="fas fa-download"></i></button>
                                    <button class="btn btn-outline-danger" title="Delete" onclick="deleteReport('{$r['name']}')"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        HTML;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing 1-6 of 24 reports</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">4</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Generate Report Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog text-purple me-2"></i>Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" id="reportType">
                        <option value="department">Department Report</option>
                        <option value="faculty">Faculty Report</option>
                        <option value="schedule">Schedule Report</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specific Report</label>
                    <select class="form-select" id="specificReport">
                        <option>Department Performance Summary</option>
                        <option>Faculty Load Distribution</option>
                        <option>Attendance Report</option>
                        <option>Schedule Overview</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control" id="endDate">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Output Format</label>
                    <select class="form-select">
                        <option>PDF</option>
                        <option>Excel</option>
                        <option>CSV</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Include Charts</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeCharts" checked>
                        <label class="form-check-label" for="includeCharts">Yes, include visual charts</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sms-primary" onclick="confirmGenerate()"><i class="fas fa-cog me-1"></i>Generate Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Report Modal -->
<div class="modal fade" id="customReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus text-purple me-2"></i>Custom Report Builder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Report Name</label>
                        <input type="text" class="form-control" placeholder="Enter report name...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Data Sources</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds1" checked>
                                <label class="form-check-label" for="ds1">Faculty Data</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds2" checked>
                                <label class="form-check-label" for="ds2">Performance Data</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds3">
                                <label class="form-check-label" for="ds3">Schedule Data</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds4">
                                <label class="form-check-label" for="ds4">Attendance Data</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ds5">
                                <label class="form-check-label" for="ds5">Leave Data</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Filters</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select">
                                <option value="">All Departments</option>
                                <option selected>College of Computer Studies</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select">
                                <option value="">All Faculty</option>
                                <option>Full-time Faculty</option>
                                <option>Part-time Faculty</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select">
                                <option value="">All Ranks</option>
                                <option>Professor</option>
                                <option>Associate Professor</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Output Format</label>
                    <select class="form-select">
                        <option>PDF</option>
                        <option>Excel</option>
                        <option>CSV</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sms-primary"><i class="fas fa-magic me-1"></i>Generate Custom Report</button>
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
    alert('Report generation started. You will be notified when complete.');
    bootstrap.Modal.getInstance(document.getElementById('generateModal')).hide();
}
function viewReport(name) {
    alert('Viewing report: ' + name);
}
function downloadReport(name) {
    alert('Downloading report: ' + name);
}
function deleteReport(name) {
    if(confirm('Delete report: ' + name + '?')) {
        alert('Report deleted.');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
