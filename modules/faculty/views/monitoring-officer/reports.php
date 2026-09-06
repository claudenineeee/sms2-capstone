<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$pageTitle    = 'Attendance Reports & Analytics';
$activeModule = 'faculty';
$activePage   = 'reports';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Monitoring Officer', 'url' => BASE_URL . '/modules/faculty/users/monitoring_officer/dashboard.php'],
    ['label' => 'Reports & Analytics', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// Period filtering parameters
$period     = $_GET['period'] ?? 'past_week';

switch ($period) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d');
        break;
    case 'past_month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate   = date('Y-m-d');
        break;
    case 'this_semester':
        $startDate = date('Y-01-01');
        $endDate   = date('Y-m-d');
        break;
    case 'custom':
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate   = $_GET['end_date'] ?? date('Y-m-d');
        break;
    case 'past_week':
    default:
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate   = date('Y-m-d');
        break;
}

require_once __DIR__ . '/../../../../config/database.php';  
require_once __DIR__ . '/../../controllers/faculty-data.php'; 
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../models/AttendanceModel.php';

$facultyController = new FacultyController();
$facultyListRaw = $facultyController->getDirectoryList();
$facultyListRaw = array_filter($facultyListRaw, function ($member) {
    $position = strtolower(trim((string) ($member['position'] ?? '')));
    return $position === 'faculty professor' || $position === 'teacher' || $position === '';
});

$attendanceModel = new AttendanceModel(db());

$facultyList = [];
$allReportData = [];
foreach ($facultyListRaw as $member) {
    $facId = (string) $member['id']; 
    $facName = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
    $facultyList[] = ['id' => $facId, 'name' => $facName];

    $sessions = $attendanceModel->getSessionsForFaculty($member['id'], $startDate, $endDate);
    $allReportData[$facId] = array_map(function ($row) use ($facName) {
        return [
            'id'       => $row['session_id'],
            'date'     => $row['session_date'],
            'faculty'  => $facName,
            'subject'  => $row['subject_code'] ?? 'N/A',
            'room'     => $row['room_code'] ?? 'N/A',
            'status'   => $row['status'],
            'students' => (string) $row['attending_students'],
        ];
    }, $sessions);
}
?>

<div class="container-fluid py-3 px-2 px-md-3">

    <!-- Header -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-body-tertiary text-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1 fs-5 fs-md-3 d-flex align-items-center gap-2">
                    <i class="fas fa-file-invoice text-primary"></i>
                    <span>Attendance Reports & Analytics</span>
                </h3>
                <p class="text-body-secondary small mb-0">Search faculty and click "Check" to view past attendance logs.</p>
            </div>
            <div class="d-flex gap-2 w-100 w-sm-auto">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-3 flex-fill flex-sm-grow-0">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-sm flex-fill flex-sm-grow-0" onclick="alert('Exporting dataset...')">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body-tertiary text-body">
        <form method="GET" class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fs-7 fw-semibold text-body-secondary">Time Period</label>
                <select name="period" class="form-select bg-body text-body border-light-subtle rounded-3" onchange="toggleCustomDates(this.value)">
                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="past_week" <?= $period === 'past_week' ? 'selected' : '' ?>>Past Week (7 Days)</option>
                    <option value="past_month" <?= $period === 'past_month' ? 'selected' : '' ?>>Past Month (30 Days)</option>
                    <option value="this_semester" <?= $period === 'this_semester' ? 'selected' : '' ?>>Current Semester</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3 custom-date-field <?= $period !== 'custom' ? 'd-none' : '' ?>">
                <label class="form-label fs-7 fw-semibold text-body-secondary">Start Date</label>
                <input type="date" name="start_date" class="form-control bg-body text-body border-light-subtle rounded-3" value="<?= htmlspecialchars($startDate) ?>">
            </div>

            <div class="col-12 col-sm-6 col-md-3 custom-date-field <?= $period !== 'custom' ? 'd-none' : '' ?>">
                <label class="form-label fs-7 fw-semibold text-body-secondary">End Date</label>
                <input type="date" name="end_date" class="form-control bg-body text-body border-light-subtle rounded-3" value="<?= htmlspecialchars($endDate) ?>">
            </div>

            <div class="col-12 col-md-2 ms-auto">
                <button type="submit" class="btn btn-primary w-100 rounded-3">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Two Side-by-Side Tables -->
    <div class="row g-4">
        
        <!-- Left Table: Simplified Faculty List with Search -->
        <div class="col-12 col-lg-4">
            <div class="card bg-body-tertiary text-body border-0 shadow-sm rounded-4 h-100 d-flex flex-column">
                <div class="card-header bg-transparent border-bottom border-light-subtle p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0 fs-6"><i class="fas fa-users me-2 text-primary"></i>Faculty List</h5>
                        <span class="badge bg-primary-subtle text-primary"><?= count($facultyList) ?> Active</span>
                    </div>
                    <!-- Live Search Input -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body text-body-secondary border-light-subtle border-end-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="facultySearchInput" class="form-control bg-body text-body border-light-subtle border-start-0 shadow-none fs-7" placeholder="Search faculty name..." onkeyup="filterFacultyList()">
                    </div>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead>
                            <tr class="text-body-secondary border-light-subtle">
                                <th>Name</th>
                                <th class="text-end text-sm-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody">
                            <!-- Populated dynamically via JS pagination -->
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-top border-light-subtle p-2 d-flex justify-content-between align-items-center">
                    <small class="text-body-secondary fs-8" id="facultyPaginationInfo">Showing 0-0 of 0</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="facultyPaginationControls">
                            <!-- Populated via JS -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Right Table: Past Attendance Logs -->
        <div class="col-12 col-lg-8">
            <div class="card bg-body-tertiary text-body border-0 shadow-sm rounded-4 h-100 d-flex flex-column">
                <div class="card-header bg-transparent border-bottom border-light-subtle p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 fs-6">
                        <i class="fas fa-history me-2 text-primary"></i>Past Attendance Logs 
                        <span id="selectedFacultyTitle" class="text-primary fs-7 ms-1 fw-normal"></span>
                    </h5>
                    <span class="badge bg-primary-subtle text-primary" id="logCountBadge">0 Logs</span>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead>
                            <tr class="text-body-secondary border-light-subtle">
                                <th>Log ID</th>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceLogsBody">
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-5">
                                    <i class="fas fa-hand-pointer fs-3 d-block mb-2 opacity-50"></i>
                                    Please click <strong>"Check"</strong> on a faculty member to load their attendance history.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-top border-light-subtle p-2 d-flex justify-content-between align-items-center">
                    <small class="text-body-secondary fs-8" id="logsPaginationInfo">Showing 0-0 of 0</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="logsPaginationControls">
                            <!-- Populated via JS -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6"><i class="fas fa-info-circle text-primary me-2"></i>Attendance Record Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7">
                <div class="mb-2"><strong>Log ID:</strong> <span id="m_id" class="text-body-secondary font-monospace"></span></div>
                <div class="mb-2"><strong>Date:</strong> <span id="m_date"></span></div>
                <div class="mb-2"><strong>Faculty:</strong> <span id="m_faculty" class="fw-bold text-primary"></span></div>
                <div class="mb-2"><strong>Subject:</strong> <span id="m_subject"></span></div>
                <div class="mb-2"><strong>Room:</strong> <span id="m_room"></span></div>
                <div class="mb-2"><strong>Status:</strong> <span id="m_status"></span></div>
                <div class="mb-2"><strong>Headcount:</strong> <span id="m_students"></span></div>
            </div>
            <div class="modal-footer border-top border-light-subtle py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Master dataset passed from PHP
const facultyMasterList = <?= json_encode($facultyList) ?>;
const attendanceRecords = <?= json_encode($allReportData) ?>;

let currentFacultyPage = 1;
let currentLogsPage = 1;
const rowsPerPage = 10;
let activeFilteredFaculty = [...facultyMasterList];
let activeLogs = [];
let selectedFacultyId = null;
let selectedFacultyName = '';

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    renderFacultyTable();
});

function filterFacultyList() {
    const query = document.getElementById('facultySearchInput').value.toLowerCase().trim();
    activeFilteredFaculty = facultyMasterList.filter(fac => fac.name.toLowerCase().includes(query));
    currentFacultyPage = 1; // Reset to page 1 on search
    renderFacultyTable();
}

function renderFacultyTable() {
    const tbody = document.getElementById('facultyTableBody');
    const info = document.getElementById('facultyPaginationInfo');
    const controls = document.getElementById('facultyPaginationControls');

    const totalRows = activeFilteredFaculty.length;
    if (totalRows === 0) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center text-body-secondary py-4"><i class="fas fa-search me-1"></i> No matching faculty found.</td></tr>`;
        info.textContent = `Showing 0-0 of 0`;
        controls.innerHTML = ``;
        return;
    }

    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if (currentFacultyPage > totalPages) currentFacultyPage = totalPages;

    const start = (currentFacultyPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, totalRows);
    const paginatedRows = activeFilteredFaculty.slice(start, end);

    let html = '';
    paginatedRows.forEach(fac => {
        html += `
            <tr class="faculty-row">
                <td class="fw-bold text-body faculty-name">${fac.name}</td>
                <td class="text-end text-sm-center">
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-2 px-sm-3" onclick="checkFacultyLogs('${fac.id}', '${escapeHtml(fac.name)}')">
                        <i class="fas fa-clipboard-check me-1"></i> Check
                    </button>
                </td>
            </tr>`;
    });
    tbody.innerHTML = html;

    info.textContent = `Showing ${start + 1}-${end} of ${totalRows}`;
    renderPaginationControls(totalPages, currentFacultyPage, 'changeFacultyPage', controls);
}

function changeFacultyPage(page) {
    currentFacultyPage = page;
    renderFacultyTable();
}

function checkFacultyLogs(facultyId, facultyName) {
    selectedFacultyId = facultyId;
    selectedFacultyName = facultyName;
    currentLogsPage = 1;

    const title = document.getElementById('selectedFacultyTitle');
    title.textContent = `— ${facultyName}`;
    
    activeLogs = attendanceRecords[facultyId] || [];
    document.getElementById('logCountBadge').textContent = `${activeLogs.length} Logs`;

    renderLogsTable();
}

function renderLogsTable() {
    const tbody = document.getElementById('attendanceLogsBody');
    const info = document.getElementById('logsPaginationInfo');
    const controls = document.getElementById('logsPaginationControls');

    const totalRows = activeLogs.length;
    if (totalRows === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-body-secondary py-4">
                    <i class="fas fa-folder-open me-2"></i>No attendance logs found for ${selectedFacultyName}.
                </td>
            </tr>`;
        info.textContent = `Showing 0-0 of 0`;
        controls.innerHTML = ``;
        return;
    }

    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if (currentLogsPage > totalPages) currentLogsPage = totalPages;

    const start = (currentLogsPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, totalRows);
    const paginatedRows = activeLogs.slice(start, end);

    let rowsHtml = '';
    paginatedRows.forEach(log => {
        const badgeClass = log.status === 'Present' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';
        rowsHtml += `
            <tr>
                <td class="font-monospace text-body-secondary">#${log.id}</td>
                <td>${log.date}</td>
                <td>${log.subject}</td>
                <td>${log.room}</td>
                <td><span class="badge ${badgeClass} rounded-pill px-3 py-1">${log.status}</span></td>
                <td>${log.students}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-info btn-sm rounded-3 px-2 px-sm-3" onclick='viewLogDetail(${JSON.stringify(log)})'>
                        <i class="fas fa-eye me-sm-1"></i><span class="d-none d-sm-inline"> View</span>
                    </button>
                </td>
            </tr>`;
    });

    tbody.innerHTML = rowsHtml;
    info.textContent = `Showing ${start + 1}-${end} of ${totalRows}`;
    renderPaginationControls(totalPages, currentLogsPage, 'changeLogsPage', controls);
}

function changeLogsPage(page) {
    currentLogsPage = page;
    renderLogsTable();
}

function renderPaginationControls(totalPages, currentPage, jsFunctionNama, container) {
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = ``;
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="${jsFunctionNama}(${currentPage - 1})">&laquo;</button>
             </li>`;

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <button class="page-link" onclick="${jsFunctionNama}(${i})">${i}</button>
                     </li>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="${jsFunctionNama}(${currentPage + 1})">&raquo;</button>
             </li>`;

    container.innerHTML = html;
}

function viewLogDetail(data) {
    document.getElementById('m_id').textContent = '#' + data.id;
    document.getElementById('m_date').textContent = data.date;
    document.getElementById('m_faculty').textContent = data.faculty;
    document.getElementById('m_subject').textContent = data.subject;
    document.getElementById('m_room').textContent = data.room;
    document.getElementById('m_status').textContent = data.status;
    document.getElementById('m_students').textContent = data.students;

    const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    modal.show();
}

function toggleCustomDates(val) {
    document.querySelectorAll('.custom-date-field').forEach(el => {
        if (val === 'custom') {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });
}

function escapeHtml(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>