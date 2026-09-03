<?php
/**
 * Schedule Approval (Grouped by Faculty Account)
 * Purpose: Review and approve generated class schedules grouped by instructor account, 
 * matching the master-detail workflow pattern used in Faculty Leave Requests.
 * 
 * TODO: Integrate with backend database tables (e.g., schedules, subjects, faculty, rooms).
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Schedule Approval';
$activeModule = 'faculty';
$activePage   = 'schedule-approval';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Schedule Approval', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// ==========================================
// DATABASE & MODULE INTEGRATION PLACEHOLDERS
// ==========================================
/*
 * TODO: Replace these placeholder variables with live dynamic queries from your database.
 * 
 * Example Dynamic Queries (Grouping schedules by faculty member):
 * -------------------------------------------------------------------------
 * // 1. Summary Metrics:
 * $totalSubjects   = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
 * $roomsUsed       = $pdo->query("SELECT COUNT(DISTINCT room_id) FROM schedules WHERE status = 'approved'")->fetchColumn();
 * $conflictsCount  = $pdo->query("SELECT COUNT(*) FROM schedule_conflicts WHERE resolved = 0")->fetchColumn();
 * $facultyAssigned = $pdo->query("SELECT COUNT(DISTINCT faculty_id) FROM schedules")->fetchColumn();
 * 
 * // 2. Main Faculty Accounts List Query with Aggregates:
 * $sql = "SELECT 
 *             f.id AS faculty_id,
 *             f.first_name,
 *             f.last_name,
 *             f.employee_id,
 *             f.email,
 *             COUNT(s.id) AS total_schedules,
 *             SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) AS pending_schedules,
 *             SUM(CASE WHEN s.has_conflict = 1 THEN 1 ELSE 0 END) AS conflict_count
 *         FROM faculty f
 *         LEFT JOIN schedules s ON f.id = s.faculty_id
 *         WHERE 1=1";
 * 
 * // Filter handling...
 * $sql .= " GROUP BY f.id ORDER BY f.last_name ASC";
 * $stmt = $pdo->prepare($sql);
 * $stmt->execute();
 * $facultySchedulesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
 * -------------------------------------------------------------------------
 */

// Placeholder variables initialized dynamically (ready for database mapping)
$totalSubjects   = 0; 
$roomsUsed       = 0; 
$conflictsCount  = 0; 
$facultyAssigned = 0; 

$facultySchedulesList = [];  // TODO: Populate via database query result fetch
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark"><i class="fas fa-calendar-check text-primary me-2"></i>Schedule Approval</h1>
        <p class="text-secondary mb-0">Review and approve generated class schedules grouped by faculty account</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success" onclick="approveAllSelectedFaculty()"><i class="fas fa-check-double me-1"></i>Approve Selected Accounts</button>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<!-- Summary Metrics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-book"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Subjects</h6>
                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($totalSubjects); ?></h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-arrow-trend-up me-1"></i>Active catalog</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #198754; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-door-open"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rooms Used</h6>
                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($roomsUsed); ?></h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-check me-1"></i>Allocated rooms</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-danger fs-4"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Conflicts Detected</h6>
                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($conflictsCount); ?></h4>
                    <small class="text-danger fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-triangle-exclamation me-1"></i>Requires action</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0dcaf0; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-users"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Faculty Assigned</h6>
                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($facultyAssigned); ?></h4>
                    <small class="text-info fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-user-check me-1"></i>Teaching loads</small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Search & Filters Toolbar -->
<form method="GET" action="" class="card border shadow-sm mb-4 bg-white" style="border-color: #e2e8f0; border-radius: 12px;">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small fw-semibold text-secondary">Search Faculty Member</label>
                <input type="text" name="faculty_search" class="form-control form-control-sm text-dark bg-light border-secondary-subtle" placeholder="Name or Employee ID..." value="<?= htmlspecialchars($_GET['faculty_search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small fw-semibold text-secondary">Conflict Status Filter</label>
                <select name="conflict_filter" class="form-select form-select-sm text-dark bg-light border-secondary-subtle">
                    <option value="">All Accounts</option>
                    <option value="has_conflict" <?= (($_GET['conflict_filter'] ?? '') === 'has_conflict') ? 'selected' : ''; ?>>Accounts with Conflicts</option>
                </select>
            </div>
            <div class="col-12 col-xl-5 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold py-2">
                        <i class="fas fa-search me-1"></i>Filter Accounts
                    </button>
                    <a href="?" class="btn btn-outline-secondary btn-sm flex-fill fw-semibold py-2 text-center text-decoration-none">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Faculty Schedule Accounts Master Table (Matches Leave Requests UI Pattern) -->
<div class="card border shadow-sm mb-4 bg-white" style="border-color: #e2e8f0; border-radius: 12px; overflow: hidden;">
    <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light border-bottom">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="fas fa-users-cog me-2 text-primary"></i>Faculty Schedule Accounts <span class="small fw-normal text-secondary">(<?= count($facultySchedulesList); ?> accounts)</span>
        </h6>
        <span class="badge bg-warning text-dark px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
            <?= $conflictsCount; ?> Pending Schedule Conflicts
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr class="text-uppercase small text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <th class="ps-4" style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAllFaculty"></th>
                        <th>Faculty Member</th>
                        <th class="text-center">Total Schedules</th>
                        <th class="text-center">Pending Review</th>
                        <th class="text-center">Status / Conflicts</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-dark small">
                    <?php if (!empty($facultySchedulesList)): ?>
                        <?php foreach ($facultySchedulesList as $fac): ?>
                        <tr>
                            <td class="ps-4"><input type="checkbox" class="form-check-input faculty-select" value="<?= htmlspecialchars($fac['faculty_id'] ?? ''); ?>"></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                                        <?= strtoupper(substr($fac['first_name'] ?? 'F', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block"><?= htmlspecialchars(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? '')); ?></strong>
                                        <span class="text-muted" style="font-size: 0.75rem;">ID: <?= htmlspecialchars($fac['employee_id'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-semibold"><?= htmlspecialchars($fac['total_schedules'] ?? 0); ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1">
                                    <?= htmlspecialchars($fac['pending_schedules'] ?? 0); ?> classes
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($fac['conflict_count']) && $fac['conflict_count'] > 0): ?>
                                    <span class="badge rounded-pill px-2.5 py-1 bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="fas fa-exclamation-triangle me-1"></i><?= $fac['conflict_count']; ?> Conflict(s)
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill px-2.5 py-1 bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check me-1"></i>All Processed / Clear
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <!-- Clicking this opens the modal or drawer listing all assigned schedules for this specific teacher -->
                                <button class="btn btn-sm btn-primary fw-semibold px-3" onclick="viewFacultySchedules(<?= htmlspecialchars($fac['faculty_id'] ?? 0); ?>, '<?= htmlspecialchars(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? '')); ?>')">
                                    <i class="fas fa-eye me-1.5"></i>Review Schedules
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="fas fa-info-circle fa-2x mb-2 text-muted d-block"></i>
                                No faculty schedule accounts found. Connect your database tables to populate records dynamically.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light border-top">
        <span class="small text-secondary">Showing 0-0 of 0 accounts</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal: Faculty Schedules Breakdown (Master-Detail View) -->
<div class="modal fade" id="facultySchedulesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-white text-dark border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header py-3 px-4 border-bottom bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>Schedules for: <span id="modalFacultyName" class="text-primary">-</span>
                    </h5>
                    <small class="text-secondary">Review, approve, or resolve individual class allocations for this instructor</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Action bar inside modal for bulk actions on this specific teacher's schedules -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-secondary fw-semibold">Assigned Teaching Load & Time Slots</span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success fw-semibold" onclick="approveTeacherSchedules()"><i class="fas fa-check me-1"></i>Approve All for Instructor</button>
                        <button class="btn btn-sm btn-outline-danger fw-semibold" onclick="requestTeacherModifications()"><i class="fas fa-edit me-1"></i>Request Changes</button>
                    </div>
                </div>

                <!-- Sub-table containing all schedules for the selected teacher -->
                <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3"><input type="checkbox" class="form-check-input" id="selectAllModalRows"></th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Room</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="modalSchedulesTableBody">
                            <!-- Populated dynamically via JavaScript/AJAX when user clicks "Review Schedules" -->
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading schedules for instructor...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-3 px-4 border-top bg-light">
                <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Select All Toggle for Main Accounts Table
    document.getElementById('selectAllFaculty')?.addEventListener('change', function () {
        document.querySelectorAll('.faculty-select').forEach(cb => cb.checked = this.checked);
    });

    // Select All Toggle inside Modal Sub-table
    document.getElementById('selectAllModalRows')?.addEventListener('change', function () {
        document.querySelectorAll('.modal-row-select').forEach(cb => cb.checked = this.checked);
    });
});

// Open Modal and Load Teacher's Schedules via AJAX
function viewFacultySchedules(facultyId, facultyName) {
    document.getElementById('modalFacultyName').textContent = facultyName;
    
    const modalEl = document.getElementById('facultySchedulesModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // TODO: Perform AJAX request to fetch schedules for this specific faculty member
        // fetch(`get_teacher_schedules.php?faculty_id=${facultyId}`)
        //   .then(response => response.json())
        //   .then(data => { ... render rows into #modalSchedulesTableBody ... });
    }
}

function approveTeacherSchedules() {
    alert('Processing approval for instructor schedules...');
}

function requestTeacherModifications() {
    alert('Triggering schedule revision request for this instructor...');
}

function approveAllSelectedFaculty() {
    alert('Processing bulk approval for selected faculty accounts...');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>