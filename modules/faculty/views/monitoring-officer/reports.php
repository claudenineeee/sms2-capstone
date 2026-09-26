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

require_once __DIR__ . '/../../models/AttendanceModel.php';
$attendanceModel = new AttendanceModel(db());

$selectedFacultyId = $_GET['faculty_id'] ?? null;
$pastLogs = [];

if ($selectedFacultyId) {
    try {
        $pastLogs = $attendanceModel->getLogsByFaculty($selectedFacultyId) ?? [];
    } catch (Throwable $e) {
        error_log('Error fetching reports: ' . $e->getMessage());
    }
}

function renderReportStatusBadge(array $log): string {
    $rawStatus = $log['status'] 
        ?? $log['faculty_status'] 
        ?? $log['attendance_status'] 
        ?? '';

    $statusClean = strtolower(trim((string)$rawStatus));

    if (str_contains($statusClean, 'late')) {
        return '<span class="badge bg-warning text-dark px-2 py-1 fw-bold">Late</span>';
    } elseif (str_contains($statusClean, 'present')) {
        return '<span class="badge bg-success text-white px-2 py-1 fw-bold">Present</span>';
    } elseif (str_contains($statusClean, 'absent')) {
        return '<span class="badge bg-danger text-white px-2 py-1 fw-bold">Absent</span>';
    } elseif ($statusClean !== '' && $statusClean !== 'pending') {
        return '<span class="badge bg-secondary text-white px-2 py-1 fw-bold">' . htmlspecialchars(ucfirst($statusClean)) . '</span>';
    }

    return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fw-bold">Pending</span>';
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
</style>

<div class="rep-wrap">
    <!-- HEADER -->
    <div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1"><i class="fas fa-file-invoice text-primary me-2"></i>Attendance Reports & Analytics</h1>
            <p class="small mb-0">Search faculty and click "Check" to view past attendance logs.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i> Print</button>
            <button class="btn btn-primary btn-sm"><i class="fas fa-file-excel me-1"></i> Export Excel</button>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="row g-4">
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
                <div class="table-responsive">
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
            <div class="card-dark p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>Past Attendance Logs</h6>
                    <span class="badge bg-secondary"><?= count($pastLogs) ?> Logs</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle fs-7 mb-0">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pastLogs)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Select a faculty member from the list to load logs.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pastLogs as $log): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= htmlspecialchars($log['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($log['date'] ?? $log['created_at'] ?? '') ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($log['subject_code'] ?? 'N/A') ?></td>
                                        <td><span class="badge rep-room-badge px-2 py-1"><?= htmlspecialchars($log['room_code'] ?? 'N/A') ?></span></td>
                                        <td><?= renderReportStatusBadge($log) ?></td>
                                        <td><?= htmlspecialchars((string)($log['attending_students'] ?? 0)) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-info btn-sm py-1 px-2"><i class="fas fa-eye"></i> View</button>
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
</script>