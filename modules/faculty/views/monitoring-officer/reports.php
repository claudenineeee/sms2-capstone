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
$startDate  = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate    = $_GET['end_date'] ?? date('Y-m-d');

// Simplified Faculty Roster
$facultyList = [
    ['id' => 'FAC-001', 'name' => 'Dr. Earl Salvame'],
    ['id' => 'FAC-002', 'name' => 'Prof. Juan Dela Cruz'],
    ['id' => 'FAC-003', 'name' => 'Dr. Maria Santos'],
    ['id' => 'FAC-004', 'name' => 'Prof. Luis Tan'],
];

// Historical attendance dataset tied to faculty ID
$allReportData = [
    'FAC-001' => [
        ['id' => 101, 'date' => date('Y-m-d', strtotime('-1 day')),  'faculty' => 'Dr. Earl Salvame',     'subject' => 'SIA-201',  'room' => '403-B', 'status' => 'Present', 'students' => '38/40'],
        ['id' => 105, 'date' => date('Y-m-d', strtotime('-5 days')), 'faculty' => 'Dr. Earl Salvame',     'subject' => 'SIA-202',  'room' => '403-B', 'status' => 'Present', 'students' => '39/40'],
    ],
    'FAC-002' => [
        ['id' => 102, 'date' => date('Y-m-d', strtotime('-2 days')), 'faculty' => 'Prof. Juan Dela Cruz', 'subject' => 'ITE-101',  'room' => '301-A', 'status' => 'Present', 'students' => '42/45'],
    ],
    'FAC-003' => [
        ['id' => 103, 'date' => date('Y-m-d', strtotime('-3 days')), 'faculty' => 'Dr. Maria Santos',     'subject' => 'EDUC-30', 'room' => '202-C', 'status' => 'Absent',  'students' => '0/35'],
    ],
    'FAC-004' => [
        ['id' => 104, 'date' => date('Y-m-d', strtotime('-4 days')), 'faculty' => 'Prof. Luis Tan',       'subject' => 'BUS-110',  'room' => '104-A', 'status' => 'Present', 'students' => '28/30'],
    ]
];
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
            <div class="card bg-body-tertiary text-body border-0 shadow-sm rounded-4 h-100">
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead>
                            <tr class="text-body-secondary border-light-subtle">
                                <th>Name</th>
                                <th class="text-end text-sm-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody">
                            <?php foreach ($facultyList as $fac): ?>
                                <tr class="faculty-row">
                                    <td class="fw-bold text-body faculty-name"><?= $fac['name'] ?></td>
                                    <td class="text-end text-sm-center">
                                        <button type="button" class="btn btn-primary btn-sm rounded-3 px-2 px-sm-3" onclick="checkFacultyLogs('<?= $fac['id'] ?>', '<?= addslashes($fac['name']) ?>')">
                                            <i class="fas fa-clipboard-check me-1"></i> Check
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noFacultyFoundRow" class="d-none">
                                <td colspan="2" class="text-center text-body-secondary py-4">
                                    <i class="fas fa-search me-1"></i> No matching faculty found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Table: Past Attendance Logs -->
        <div class="col-12 col-lg-8">
            <div class="card bg-body-tertiary text-body border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-bottom border-light-subtle p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 fs-6">
                        <i class="fas fa-history me-2 text-primary"></i>Past Attendance Logs 
                        <span id="selectedFacultyTitle" class="text-primary fs-7 ms-1 fw-normal"></span>
                    </h5>
                    <span class="badge bg-primary-subtle text-primary" id="logCountBadge">0 Logs</span>
                </div>
                <div class="table-responsive">
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
const attendanceRecords = <?= json_encode($allReportData) ?>;

function filterFacultyList() {
    const query = document.getElementById('facultySearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#facultyTableBody .faculty-row');
    const noMatchRow = document.getElementById('noFacultyFoundRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.querySelector('.faculty-name').textContent.toLowerCase();
        if (name.includes(query)) {
            row.classList.remove('d-none');
            visibleCount++;
        } else {
            row.classList.add('d-none');
        }
    });

    if (visibleCount === 0) {
        noMatchRow.classList.remove('d-none');
    } else {
        noMatchRow.classList.add('d-none');
    }
}

function checkFacultyLogs(facultyId, facultyName) {
    const tbody = document.getElementById('attendanceLogsBody');
    const title = document.getElementById('selectedFacultyTitle');
    const badge = document.getElementById('logCountBadge');
    
    title.textContent = `— ${facultyName}`;
    
    const logs = attendanceRecords[facultyId] || [];
    badge.textContent = `${logs.length} Logs`;

    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-body-secondary py-4">
                    <i class="fas fa-folder-open me-2"></i>No attendance logs found for ${facultyName}.
                </td>
            </tr>`;
        return;
    }

    let rowsHtml = '';
    logs.forEach(log => {
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
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>