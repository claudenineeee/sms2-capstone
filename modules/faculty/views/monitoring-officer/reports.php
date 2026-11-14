<?php
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Attendance Reports & Analytics';
$activeModule = 'faculty';
$activePage   = 'reports';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Attendance Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

require_once __DIR__ . '/../../controllers/FacultyController.php';
$facultyController = new FacultyController();
$facultyList = $facultyController->getDirectoryList();
// Restrict to teaching faculty only
$facultyList = array_filter($facultyList, function ($member) {
    $position = strtolower(trim((string) ($member['position'] ?? '')));
    return $position === 'faculty professor' || $position === 'teacher' || $position === '';
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AttendanceModel.php';

// FIX: Use safe facultyDb() instead of the broken getFacultyDatabaseConnection()
$pdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$pdo) {
    die('Database connection failed.');
}

$attendanceModel = new AttendanceModel($pdo);

$selectedFacultyId = $_GET['faculty_id'] ?? null;
$pastLogs = [];
$selectedFacultyName = '';

if ($selectedFacultyId) {
    try {
        $pastLogs = $attendanceModel->getLogsByFaculty($selectedFacultyId) ?? [];
        foreach ($facultyList as $f) {
            if ($f['id'] == $selectedFacultyId) {
                $selectedFacultyName = htmlspecialchars(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                break;
            }
        }
    } catch (Throwable $e) {
        error_log('Error fetching reports: ' . $e->getMessage());
    }
}

function getReportStatusText(array $log): string {
    $rawStatus = $log['status'] 
        ?? $log['faculty_status'] 
        ?? $log['attendance_status'] 
        ?? '';

    $statusClean = strtolower(trim((string)$rawStatus));

    if (str_contains($statusClean, 'late')) {
        return 'Late';
    } elseif (str_contains($statusClean, 'present')) {
        return 'Present';
    } elseif (str_contains($statusClean, 'absent')) {
        return 'Absent';
    } elseif ($statusClean !== '' && $statusClean !== 'pending') {
        return ucfirst($statusClean);
    }

    // FIX: Since these are past logs, default to 'Recorded' instead of 'Pending'
    return 'Recorded';
}

function renderReportStatusBadge(array $log): string {
    $statusText = getReportStatusText($log);
    $statusClean = strtolower($statusText);

    if ($statusClean === 'late') {
        return '<span class="badge bg-warning text-dark px-2 py-1 fw-bold">Late</span>';
    } elseif ($statusClean === 'present' || $statusClean === 'recorded') {
        return '<span class="badge bg-success text-white px-2 py-1 fw-bold">' . htmlspecialchars($statusText) . '</span>';
    } elseif ($statusClean === 'absent') {
        return '<span class="badge bg-danger text-white px-2 py-1 fw-bold">Absent</span>';
    }
    
    return '<span class="badge bg-secondary text-white px-2 py-1 fw-bold">' . htmlspecialchars($statusText) . '</span>';
}
?>

<style>
    /* =========================================================
       DYNAMIC THEME ADAPTATION (LIGHT & DARK MODE)
       ========================================================= */
    :root {
        --rep-bg: #0d1527;
        --rep-card: #10192d;
        --rep-card-2: #16223b;
        --rep-border: #1e2d4a;
        --rep-text: #e2e8f0;
        --rep-text-strong: #ffffff;
        --rep-muted: #8492a6;
        --rep-input-bg: #111c35;
        --rep-hover: rgba(59, 130, 246, 0.08);
        --rep-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    [data-bs-theme="light"] .rep-wrap,
    [data-theme="light"] .rep-wrap,
    html.light .rep-wrap,
    body.light .rep-wrap {
        --rep-bg: #f8fafc !important;
        --rep-card: #ffffff !important;
        --rep-card-2: #f1f5f9 !important;
        --rep-border: #e2e8f0 !important;
        --rep-text: #334155 !important;
        --rep-text-strong: #0f172a !important;
        --rep-muted: #64748b !important;
        --rep-input-bg: #f8fafc !important;
        --rep-hover: rgba(59, 130, 246, 0.05) !important;
        --rep-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }

    [data-bs-theme="dark"] .rep-wrap,
    [data-theme="dark"] .rep-wrap,
    html.dark .rep-wrap,
    body.dark .rep-wrap {
        --rep-bg: #0d1527 !important;
        --rep-card: #10192d !important;
        --rep-card-2: #16223b !important;
        --rep-border: #1e2d4a !important;
        --rep-text: #e2e8f0 !important;
        --rep-text-strong: #ffffff !important;
        --rep-muted: #8492a6 !important;
        --rep-input-bg: #111c35 !important;
        --rep-hover: rgba(59, 130, 246, 0.08) !important;
        --rep-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    }

    .rep-wrap {
        padding: 1.5rem 1.25rem;
        background-color: transparent !important;
    }

    .dashboard-header h1 {
        font-size: 1.4rem; font-weight: 700; color: var(--rep-text-strong) !important;
    }
    .dashboard-header p {
        color: var(--rep-muted) !important;
    }

    .card-dark {
        background-color: var(--rep-card) !important;
        border: 1px solid var(--rep-border) !important;
        border-radius: 0.85rem;
        box-shadow: var(--rep-shadow) !important;
        color: var(--rep-text) !important;
    }
    .card-dark h6 { color: var(--rep-text-strong) !important; }

    .form-dark {
        background-color: var(--rep-input-bg) !important;
        border: 1px solid var(--rep-border) !important;
        color: var(--rep-text-strong) !important;
    }
    .form-dark::placeholder { color: var(--rep-muted) !important; }

    .table-dark-custom { color: var(--rep-text) !important; }
    .table-dark-custom thead th {
        background-color: var(--rep-card-2) !important;
        color: var(--rep-muted) !important;
        border-bottom: 1px solid var(--rep-border) !important;
        font-size: 0.725rem; text-transform: uppercase;
    }
    .table-dark-custom tbody td {
        background-color: transparent !important;
        color: var(--rep-text) !important;
        border-bottom: 1px solid var(--rep-border) !important;
    }
    .table-dark-custom tbody tr:hover td {
        background-color: var(--rep-hover) !important;
    }

    .rep-room-badge {
        background-color: var(--rep-card-2) !important;
        border: 1px solid var(--rep-border) !important;
        color: var(--rep-text-strong) !important;
    }

    /* Print Specific Styles */
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000 !important; }
        .no-print, .col-lg-4, .dashboard-header, .card-dark:first-child { display: none !important; }
        .col-lg-8 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
        .card-dark { border: 1px solid #ddd !important; box-shadow: none !important; background: #fff !important; }
        .table-dark-custom thead th { background: #f0f0f0 !important; color: #000 !important; }
        .table-dark-custom tbody td { color: #000 !important; }
        .badge { border: 1px solid #999 !important; color: #000 !important; background: transparent !important; }
    }
</style>

<div class="rep-wrap">
    <!-- HEADER -->
    <div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1"><i class="fas fa-file-invoice text-primary me-2"></i>Attendance Reports & Analytics</h1>
            <p class="small mb-0">Search faculty and click "Check" to view past attendance logs.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="printAttendanceReport()" class="btn btn-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Export / Print
            </button>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="row g-4" id="report-content-area">
        <!-- Faculty Selection List -->
        <div class="col-lg-4">
            <div class="card-dark p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i>Faculty List</h6>
                    <span class="badge bg-primary"><?= count($facultyList) ?> Active</span>
                </div>
                <div class="mb-3">
                    <input type="text" id="facultySearchInput" class="form-control form-dark form-control-sm" placeholder="Search faculty name...">
                </div>
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-dark-custom align-middle fs-7 mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody">
                            <?php foreach ($facultyList as $faculty): ?>
                                <?php $fullName = htmlspecialchars(($faculty['first_name'] ?? '') . ' ' . ($faculty['last_name'] ?? '')); ?>
                                <tr>
                                    <td class="fw-semibold"><?= $fullName ?></td>
                                    <td class="text-end">
                                        <a href="?faculty_id=<?= $faculty['id'] ?>" class="btn btn-primary btn-sm rounded-2 py-1 px-3 fs-7">
                                            <i class="fas fa-search me-1"></i>Check
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Logs Panel -->
        <div class="col-lg-8">
            <div class="card-dark p-3 h-100" id="printable-logs-panel">
                <!-- PRINT HEADER -->
                <div class="d-none d-print-block mb-4 text-center">
                    <h3 class="fw-bold mb-1">Attendance Report</h3>
                    <p class="mb-1"><strong>Faculty:</strong> <?= $selectedFacultyName ?: 'N/A' ?></p>
                    <p class="mb-0"><strong>Printed:</strong> <?= date('F j, Y g:i A') ?></p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-history text-primary me-2 d-print-none"></i>
                        Past Attendance Logs
                        <?php if ($selectedFacultyName): ?>
                            <span class="text-muted fw-normal fs-7 ms-1">— <?= $selectedFacultyName ?></span>
                        <?php endif; ?>
                    </h6>
                    <span class="badge bg-secondary"><?= count($pastLogs) ?> Logs</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle fs-7 mb-0">
                        <thead>
                            <tr>
                                <!-- REMOVED: Log ID column -->
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th class="text-end d-print-none">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pastLogs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Select a faculty member from the list to load logs.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pastLogs as $log): ?>
                                    <?php 
                                        $statusText = getReportStatusText($log);
                                        $dateStr = htmlspecialchars($log['date'] ?? $log['created_at'] ?? '');
                                        $subjectStr = htmlspecialchars($log['subject_code'] ?? 'N/A');
                                        $roomStr = htmlspecialchars($log['room_code'] ?? 'N/A');
                                        $studentsStr = htmlspecialchars((string)($log['attending_students'] ?? 0));
                                        $logId = htmlspecialchars($log['id'] ?? '');
                                    ?>
                                    <tr>
                                        <!-- REMOVED: Log ID cell -->
                                        <td><?= $dateStr ?></td>
                                        <td class="fw-semibold"><?= $subjectStr ?></td>
                                        <td><span class="badge rep-room-badge px-2 py-1"><?= $roomStr ?></span></td>
                                        <td><?= renderReportStatusBadge($log) ?></td>
                                        <td><?= $studentsStr ?></td>
                                        <td class="text-end d-print-none">
                                            <!-- FIXED: Added onclick to view details -->
                                            <button class="btn btn-outline-info btn-sm py-1 px-2" 
                                                    onclick="viewLogDetails(this)"
                                                    data-id="<?= $logId ?>"
                                                    data-date="<?= $dateStr ?>"
                                                    data-subject="<?= $subjectStr ?>"
                                                    data-room="<?= $roomStr ?>"
                                                    data-status="<?= htmlspecialchars($statusText) ?>"
                                                    data-students="<?= $studentsStr ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i>Attendance Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7 bg-body text-body">
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Log ID</small>
                        <strong id="modal-log-id">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Date</small>
                        <strong id="modal-log-date">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Subject</small>
                        <strong id="modal-log-subject">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Room</small>
                        <strong id="modal-log-room">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Status</small>
                        <span id="modal-log-status">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Students Attending</small>
                        <strong id="modal-log-students">-</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>

<script>
document.getElementById('facultySearchInput')?.addEventListener('input', function() {
    const term = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#facultyTableBody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

function viewLogDetails(btn) {
    const id = btn.getAttribute('data-id');
    const date = btn.getAttribute('data-date');
    const subject = btn.getAttribute('data-subject');
    const room = btn.getAttribute('data-room');
    const status = btn.getAttribute('data-status');
    const students = btn.getAttribute('data-students');

    document.getElementById('modal-log-id').textContent = '#' + id;
    document.getElementById('modal-log-date').textContent = date;
    document.getElementById('modal-log-subject').textContent = subject;
    document.getElementById('modal-log-room').textContent = room;
    
    // Customize status badge color in modal
    const statusEl = document.getElementById('modal-log-status');
    statusEl.textContent = status;
    statusEl.className = 'badge px-2 py-1';
    if (status.toLowerCase().includes('late')) statusEl.classList.add('bg-warning', 'text-dark');
    else if (status.toLowerCase().includes('absent')) statusEl.classList.add('bg-danger', 'text-white');
    else statusEl.classList.add('bg-success', 'text-white');

    document.getElementById('modal-log-students').textContent = students;

    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    modal.show();
}

function printAttendanceReport() {
    const printContent = document.getElementById('printable-logs-panel').innerHTML;
    const facultyName = "<?= addslashes($selectedFacultyName) ?>";
    
    if (!facultyName) {
        alert("Please select a faculty member first before printing.");
        return;
    }

    const printWindow = window.open('', '_blank', 'width=900,height=700');
    
    // Inject Bootstrap CSS + custom print styles
    printWindow.document.write(`
        <html>
        <head>
            <title>Attendance Report - ${facultyName}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                @page { size: A4 landscape; margin: 10mm; }
                body { 
                    background: #ffffff !important; 
                    color: #000000 !important; 
                    font-family: Arial, sans-serif; 
                    padding: 20px;
                }
                .card-dark { border: none !important; }
                .table-dark-custom { width: 100% !important; border-collapse: collapse !important; }
                .table-dark-custom th, .table-dark-custom td { 
                    border: 1px solid #ccc !important; 
                    padding: 8px !important; 
                    color: #000 !important; 
                    background: #fff !important;
                }
                .table-dark-custom thead th { background-color: #f0f0f0 !important; font-weight: bold !important; }
                .badge { border: 1px solid #999 !important; color: #000 !important; background: transparent !important; }
                .d-print-none, .btn, button { display: none !important; }
                .d-print-block { display: block !important; }
            </style>
        </head>
        <body>
            ${printContent.replace('d-none d-print-block', 'd-block')}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    
    // Wait for the window to render fully before triggering print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>