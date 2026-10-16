<?php
/**
 * SMS 2 - Dean - Teaching History (merged with Subject Assignments)
 *
 * Contains tabs:
 *   - Teaching History     (from faculty_db.teaching_load_history)
 *   - Subject Assignments  (from external REST API — see fetchSubjects())
 *
 * RBAC: scoped to dean's assigned departments.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../includes/dean_rbac.php';

requireAuth();

/* ============================================================
   DEAN RBAC
   ============================================================ */
$deanUserId          = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);
$deanDepartments     = getDeanAssignedDepartments($deanUserId);
$deanDepartmentNames = getDeanDepartmentNames($deanDepartments);
$hasDeanDepartments  = !empty($deanDepartments);

/* ============================================================
   Positions that count as "teaching faculty"
   ============================================================ */
$teachingPositions = ['Faculty Professor'];

/* ============================================================
   DATABASE
   ============================================================ */
$pdo = null;
if (function_exists('facultyDb')) { $pdo = facultyDb(); }
if (!$pdo instanceof PDO && function_exists('db')) {
    try { $pdo = db(); } catch (Throwable $e) { $pdo = null; }
}
if (!$pdo instanceof PDO && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
}

/* ============================================================
   LOAD FACULTY + TEACHING HISTORY
   ============================================================ */
$facultyMembers    = [];
$teachingHistoryDB = [];
$allAcademicYears  = [];
$allSemesters      = [];
$loadError         = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id, f.faculty_no, f.first_name, f.middle_name, f.last_name,
                f.email, f.department_id, f.position, f.profile_status,
                d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyMembers = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($facultyMembers as $fac) {
            $realFacultyId = (int) $fac['faculty_id'];

            $historyRecords = [];
            try {
                $stmtHistory = $pdo->prepare("
                    SELECT
                        tlh.history_id, tlh.term_id, tlh.subject_count,
                        tlh.total_units, tlh.total_students, tlh.status,
                        at.academic_year, at.semester
                    FROM faculty_db.teaching_load_history tlh
                    LEFT JOIN faculty_db.academic_terms at ON at.term_id = tlh.term_id
                    WHERE tlh.faculty_id = :fac_id
                    ORDER BY at.academic_year DESC, at.semester DESC
                ");
                $stmtHistory->execute(['fac_id' => $realFacultyId]);
                $historyRecords = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $historyRecords = [];
            }

            foreach ($historyRecords as $rec) {
                if (!empty($rec['academic_year']) && !in_array($rec['academic_year'], $allAcademicYears, true)) {
                    $allAcademicYears[] = $rec['academic_year'];
                }
                if (!empty($rec['semester']) && !in_array($rec['semester'], $allSemesters, true)) {
                    $allSemesters[] = $rec['semester'];
                }
            }

            $fullName = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
            $initials = strtoupper(
                substr($fac['first_name'] ?? '', 0, 1) .
                substr($fac['last_name'] ?? '', 0, 1)
            );

            $teachingHistoryDB[$realFacultyId] = [
                'faculty_id' => $realFacultyId,
                'faculty_no' => $fac['faculty_no'] ?? '',
                'name'       => $fullName ?: '—',
                'department' => $fac['dept_name'] ?: ($fac['dept_code'] ?: '—'),
                'position'   => $fac['position'] ?: 'Faculty Professor',
                'initials'   => $initials ?: '—',
                'history'    => $historyRecords,
            ];
        }

        rsort($allAcademicYears);
        sort($allSemesters);

    } catch (Throwable $e) {
        error_log('Teaching history load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Teaching History';
$activeModule = 'faculty';
$activePage   = 'teaching-history';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Teaching History', 'url' => null],
];

$initialTab = strtolower((string) ($_GET['tab'] ?? 'history'));
if (!in_array($initialTab, ['history', 'assignments'], true)) $initialTab = 'history';

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="thPage">

    <?php if (!$hasDeanDepartments): ?>
        <div class="card border shadow-sm">
            <div class="card-body text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:84px;height:84px;background:rgba(100,116,139,0.10);color:#64748b;font-size:2.2rem;">
                    <i class="fas fa-building-circle-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>
                <p class="text-body-secondary mb-0" style="max-width:420px;margin:0 auto;">
                    Your account doesn't have any department access yet. Contact your administrator
                    to assign you one or more departments.
                </p>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../../../includes/layout-end.php'; exit; ?>
    <?php endif; ?>

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Teaching History Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-primary"></i>
                <span>Teaching History</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Faculty Professor teaching logs and subject assignments for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="printBtn">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= Tabs ================= -->
    <ul class="nav nav-tabs mb-3 no-print" id="thTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $initialTab === 'history' ? 'active' : '' ?>" id="tab-history-btn"
                    data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab">
                <i class="fas fa-clock-rotate-left me-1"></i>Teaching History
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $initialTab === 'assignments' ? 'active' : '' ?>" id="tab-assignments-btn"
                    data-bs-toggle="tab" data-bs-target="#tab-assignments" type="button" role="tab">
                <i class="fas fa-book me-1"></i>Subject Assignments
            </button>
        </li>
    </ul>

    <div class="tab-content" id="thTabContent">

        <!-- ============================================================
             TAB 1 — TEACHING HISTORY (existing view)
             ============================================================ -->
        <div class="tab-pane fade <?= $initialTab === 'history' ? 'show active' : '' ?>" id="tab-history" role="tabpanel">

            <div class="row g-3">

                <!-- LEFT: Faculty list -->
                <div class="col-12 col-lg-4">
                    <div class="card border shadow-sm">
                        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                            <h5 class="card-title mb-0 fw-bold">Faculty Professors</h5>
                            <span class="badge text-bg-light border" id="facultyCountBadge"><?= count($facultyMembers) ?></span>
                        </div>
                        <div class="card-body py-3 border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body text-body-secondary border-light-subtle">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="facultySearchInput" class="form-control bg-body text-body border-light-subtle shadow-none"
                                       placeholder="Search faculty…" autocomplete="off">
                            </div>
                        </div>
                        <div class="card-body p-2 th-faculty-scroll">
                            <div class="d-flex flex-column gap-2" id="facultyListContainer">
                                <?php if (empty($facultyMembers)): ?>
                                    <div class="text-center py-5 text-body-secondary">
                                        <i class="fas fa-user-slash d-block mb-2" style="font-size:1.8rem;opacity:0.4;"></i>
                                        <span class="small">No Faculty Professors in your department.</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($facultyMembers as $fac): ?>
                                        <?php
                                            $fId = (int) $fac['faculty_id'];
                                            $fName = trim(($fac['first_name'] ?? '') . ' ' . ($fac['last_name'] ?? ''));
                                            $initials = strtoupper(
                                                substr($fac['first_name'] ?? '', 0, 1) .
                                                substr($fac['last_name'] ?? '', 0, 1)
                                            );
                                        ?>
                                        <div class="th-faculty-card d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 border"
                                             data-name="<?= htmlspecialchars(strtolower($fName), ENT_QUOTES, 'UTF-8') ?>"
                                             data-faculty-id="<?= $fId ?>">
                                            <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1;">
                                                <div class="th-avatar"><?= htmlspecialchars($initials ?: '—') ?></div>
                                                <div style="min-width: 0; flex: 1;">
                                                    <div class="fw-semibold small text-truncate th-privacy-target" title="<?= htmlspecialchars($fName) ?>">
                                                        <?= htmlspecialchars($fName ?: '—') ?>
                                                    </div>
                                                    <div class="text-body-secondary th-faculty-meta text-truncate">
                                                        <?= htmlspecialchars($fac['faculty_no'] ?? '—') ?> ·
                                                        <?= htmlspecialchars($fac['dept_name'] ?: ($fac['dept_code'] ?: '—')) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 th-view-btn" type="button">
                                                View
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-2">
                            <small class="text-body-secondary" id="paginationInfo">Showing 0</small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0 gap-1 th-pagination" id="paginationList"></ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Teaching history log -->
                <div class="col-12 col-lg-8">
                    <div class="card border shadow-sm mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex flex-row align-items-start justify-content-between gap-2 flex-wrap">
                                <div class="d-flex align-items-start gap-3" style="min-width: 0; flex: 1;">
                                    <div id="profAvatar" class="th-avatar-lg">--</div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h5 class="mb-0 fw-bold">Teaching History Log</h5>
                                            <span class="badge text-bg-light border" id="profPosition">Faculty Professor</span>
                                        </div>
                                        <div class="text-body-secondary small">
                                            <span>Instructor: <strong class="text-body-emphasis th-privacy-target" id="profName">—</strong></span>
                                            <span class="mx-1">·</span>
                                            <span>Department: <strong class="text-body-emphasis" id="profSubject">—</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="fas fa-list-ul text-primary me-2"></i>Assigned Loads
                            </h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <select id="filterAcademicYear" class="form-select form-select-sm" style="width: 150px;">
                                    <option value="">All School Years</option>
                                    <?php foreach ($allAcademicYears as $ay): ?>
                                        <option value="<?= htmlspecialchars($ay) ?>"><?= htmlspecialchars($ay) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filterSemester" class="form-select form-select-sm" style="width: 140px;">
                                    <option value="">All Semesters</option>
                                    <?php foreach ($allSemesters as $sem): ?>
                                        <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="badge text-bg-light border" id="totalHistoryCount">0 Records</span>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive th-table-container">
                                <table class="table table-hover align-middle mb-0 th-table" style="min-width: 640px;">
                                    <thead class="th-table-head">
                                        <tr>
                                            <th class="ps-3">School Year / Semester</th>
                                            <th class="text-center">Subjects</th>
                                            <th class="text-center">Units</th>
                                            <th class="text-center">Students</th>
                                            <th class="pe-3 text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             TAB 2 — SUBJECT ASSIGNMENTS
             ============================================================ -->
        <div class="tab-pane fade <?= $initialTab === 'assignments' ? 'show active' : '' ?>" id="tab-assignments" role="tabpanel">

            <div class="alert alert-info d-flex align-items-center gap-2 mb-3 d-none" id="saLoading" role="alert">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <div>Loading subject assignments…</div>
            </div>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 d-none" id="saError" role="alert">
                <i class="fas fa-triangle-exclamation"></i>
                <div id="saErrorMsg">Could not load subject assignments.</div>
            </div>

            <!-- KPI Cards -->
            <div class="row g-3 mb-4 no-print">
                <div class="col-6 col-lg-3">
                    <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Subjects</h6>
                                <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiSubjects">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">In scope</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Assigned</h6>
                                <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiAssigned">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">Have faculty</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Unassigned</h6>
                                <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiUnassigned">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">No faculty yet</small>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-6 col-lg-3">
                    <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                        <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#ff4d4d;z-index:1;"></div>
                        <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                            <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:42px;height:42px;font-size:1.2rem;background:rgba(255,77,77,0.14);color:#ff4d4d;">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Units</h6>
                                <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiUnits">0</h4>
                                <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Across all subjects</small>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border shadow-sm mb-4 no-print">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <?php if (count($deanDepartments) > 1): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="sa_f_dept">Department</label>
                            <select class="form-select form-select-sm" id="sa_f_dept" data-sa-filter="dept">
                                <option value="all">All my departments</option>
                                <?php foreach ($deanDepartments as $deptId): ?>
                                    <option value="<?= (int) $deptId ?>">
                                        <?= htmlspecialchars($deanDepartmentNames[$deptId] ?? ('Dept #' . $deptId)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="sa_f_term">Term</label>
                            <select class="form-select form-select-sm" id="sa_f_term" data-sa-filter="term">
                                <option value="all">All terms</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="sa_f_status">Status</label>
                            <select class="form-select form-select-sm" id="sa_f_status" data-sa-filter="status">
                                <option value="all">All statuses</option>
                                <option value="assigned">Assigned</option>
                                <option value="unassigned">Unassigned</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="sa_f_q">Search</label>
                            <input type="text" class="form-control form-control-sm" id="sa_f_q" data-sa-filter="q" placeholder="Subject or faculty…" autocomplete="off">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="sa_reset_filters">
                                <i class="fas fa-rotate-left"></i><span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments Table -->
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Assignments</h5>
                        <p class="text-body-secondary small mb-0" id="sa_tableSubtitle">Loading…</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="sa_exportCsvBtn">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0 sa-table" style="min-width: 720px;">
                        <thead>
                            <tr>
                                <th class="text-uppercase small fw-bold text-body-secondary">Subject</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Code</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell text-center">Units</th>
                                <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Department</th>
                                <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Term</th>
                                <th class="text-uppercase small fw-bold text-body-secondary text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="assignmentsBody"></tbody>
                    </table>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
                    <div class="small text-body-secondary" id="sa_pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Assignments pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="sa_pager"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="th-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* ============================================================
       Tabs
       ============================================================ */
    #thTabs { border-bottom: 1px solid var(--sms-border-soft); }
    #thTabs .nav-link {
        color: var(--sms-text-muted);
        font-weight: 600;
        font-size: 0.85rem;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.6rem 1rem;
        background: transparent;
    }
    #thTabs .nav-link:hover { color: var(--sms-heading); }
    #thTabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom-color: #0d6efd;
    }

    /* ============================================================
       Teaching History tab
       ============================================================ */
    .th-faculty-scroll { max-height: 480px; overflow-y: auto; }
    .th-faculty-card {
        background: var(--sms-surface-muted);
        border-color: var(--sms-border-soft) !important;
        transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
        cursor: pointer;
    }
    .th-faculty-card:hover {
        border-color: rgba(59,130,246,0.35) !important;
        transform: translateY(-1px);
    }
    .th-faculty-card.th-active {
        border-color: #3b82f6 !important;
        background: rgba(59,130,246,0.06);
    }
    .th-faculty-meta { font-size: 0.72rem; }
    .th-avatar {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.72rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }
    .th-view-btn { font-size: 0.72rem; flex-shrink: 0; }
    .th-avatar-lg {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.95rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }
    .th-table-container { max-height: 480px; overflow-y: auto; }
    .th-table thead th {
        position: sticky; top: 0; z-index: 2;
        background: var(--sms-table-head-bg);
        border-bottom: 1px solid var(--sms-table-border);
        font-size: 0.68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--sms-text-muted);
        white-space: nowrap;
        padding: 0.7rem 0.75rem;
    }
    .th-table tbody td {
        padding: 0.75rem;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--sms-table-border);
        vertical-align: middle;
    }
    .th-table tbody tr:hover td { background: var(--sms-dropdown-hover); }

    .th-pagination .page-link {
        min-width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 600;
        border-radius: 8px !important;
        padding: 0 0.4rem;
    }

    /* ============================================================
       Subject Assignments tab
       ============================================================ */
    .sa-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .sa-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .sa-table tbody td { padding: 0.7rem 0.75rem; font-size: 0.85rem; vertical-align: middle; }
    .sa-table thead th { padding: 0.65rem 0.75rem; font-size: 0.7rem; }

    .sa-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem; font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .sa-badge--assigned   { background: rgba(16,185,129,0.14); color: #059669; border-color: rgba(16,185,129,0.24); }
    .sa-badge--unassigned { background: rgba(245,158,11,0.16); color: #b45309; border-color: rgba(245,158,11,0.28); }
    [data-theme="dark"] .sa-badge--assigned   { color: #6ee7b7; }
    [data-theme="dark"] .sa-badge--unassigned { color: #fcd34d; }

    /* ============================================================
       Privacy
       ============================================================ */
    .privacy-mode .th-privacy-target,
    .privacy-mode .sa-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .th-privacy-target:hover,
    .privacy-mode .sa-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* ============================================================
       Responsive
       ============================================================ */
    @media (max-width: 991.98px) {
        .th-faculty-scroll { max-height: 320px; }
        .th-table-container { max-height: 380px; }
    }
    @media (max-width: 400px) {
        #thPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #thPage h1.h4 { font-size: 1.15rem; }
        .th-avatar { width: 30px; height: 30px; font-size: 0.68rem; border-radius: 8px; }
        .th-avatar-lg { width: 38px; height: 38px; font-size: 0.82rem; border-radius: 10px; }
        .th-view-btn { font-size: 0.66rem; padding: 0.25rem 0.5rem; }
        .th-faculty-meta { font-size: 0.66rem; }
        .th-table tbody td,
        .th-table thead th { font-size: 0.72rem; padding: 0.5rem 0.5rem; }
        #thPage .card-title { font-size: 0.9rem; }

        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .sa-table tbody td,
        .sa-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
    }
    @media (max-width: 360px) {
        .th-table tbody td,
        .th-table thead th { font-size: 0.68rem; padding: 0.45rem 0.4rem; }
        .sa-table tbody td,
        .sa-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* ============================================================
       Print
       ============================================================ */
    .th-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #thPage { display: none !important; }
        .th-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .th-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .th-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .th-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .th-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .th-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .th-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .th-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .th-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .th-print-only td.th-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .th-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .th-print-only thead { display: table-header-group; }
        .th-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    /* ============================================================
       Server-injected data
       ============================================================ */
    const HISTORY_DB = <?= json_encode($teachingHistoryDB, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEAN_DEPARTMENT_IDS = <?= json_encode(array_values($deanDepartments), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /* ============================================================
       TAB 1 — TEACHING HISTORY
       ============================================================ */
    const TH_PAGE_SIZE = 10;
    let thActiveFacultyId = Object.keys(HISTORY_DB)[0] || null;
    let thCurrentPage = 1;
    let thFilteredCards = [];

    function selectFaculty(facId) {
        thActiveFacultyId = String(facId);
        document.getElementById('filterAcademicYear').value = '';
        document.getElementById('filterSemester').value = '';

        document.querySelectorAll('.th-faculty-card').forEach(card => {
            card.classList.toggle('th-active', card.dataset.facultyId === String(facId));
        });

        renderHistoryData();
    }

    function renderHistoryData() {
        const fac = HISTORY_DB[thActiveFacultyId];
        if (!fac) {
            document.getElementById('profName').textContent = '—';
            document.getElementById('profSubject').textContent = '—';
            document.getElementById('profPosition').textContent = 'Faculty Professor';
            document.getElementById('profAvatar').textContent = '--';
            document.getElementById('historyTableBody').innerHTML =
                '<tr><td colspan="5" class="text-center text-body-secondary py-5">Select a faculty member to view their teaching history.</td></tr>';
            document.getElementById('totalHistoryCount').textContent = '0 Records';
            return;
        }

        document.getElementById('profName').textContent = fac.name;
        document.getElementById('profSubject').textContent = fac.department;
        document.getElementById('profPosition').textContent = fac.position;
        document.getElementById('profAvatar').textContent = fac.initials;

        const selAY = document.getElementById('filterAcademicYear').value;
        const selSem = document.getElementById('filterSemester').value;

        const filtered = (fac.history || []).filter(item => {
            const matchAY = !selAY || item.academic_year === selAY;
            const matchSem = !selSem || item.semester === selSem;
            return matchAY && matchSem;
        });

        const tbody = document.getElementById('historyTableBody');
        document.getElementById('totalHistoryCount').textContent =
            filtered.length + ' Record' + (filtered.length !== 1 ? 's' : '');

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-body-secondary py-5">No teaching history logs found.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(item => {
            const ay = item.academic_year || '—';
            const sem = item.semester || '—';
            const subjCount = item.subject_count != null ? item.subject_count : '—';
            const units = item.total_units != null ? item.total_units : '—';
            const students = item.total_students != null ? item.total_students : '—';
            const status = item.status || 'Completed';
            const statusClass = status.toLowerCase() === 'current'
                ? 'text-bg-primary'
                : (status.toLowerCase() === 'completed' ? 'text-bg-success' : 'text-bg-secondary');

            return '<tr>' +
                '<td class="ps-3">' +
                    '<div class="fw-semibold text-nowrap">' + escapeHtml(ay) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(sem) + '</div>' +
                '</td>' +
                '<td class="text-center">' + subjCount + '</td>' +
                '<td class="text-center fw-bold">' + units + '</td>' +
                '<td class="text-center">' + students + '</td>' +
                '<td class="pe-3 text-end"><span class="badge ' + statusClass + '">' + escapeHtml(status) + '</span></td>' +
            '</tr>';
        }).join('');
    }

    function initFacultyPagination() {
        const query = (document.getElementById('facultySearchInput').value || '').toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.th-faculty-card'));

        thFilteredCards = allCards.filter(card => {
            const name = (card.dataset.name || '').toLowerCase();
            return !query || name.indexOf(query) !== -1;
        });

        document.getElementById('facultyCountBadge').textContent = thFilteredCards.length;
        const totalPages = Math.max(1, Math.ceil(thFilteredCards.length / TH_PAGE_SIZE));
        if (thCurrentPage > totalPages) thCurrentPage = 1;

        renderFacultyPage();
    }

    function renderFacultyPage() {
        const allCards = document.querySelectorAll('.th-faculty-card');
        allCards.forEach(card => card.classList.add('d-none'));

        const start = (thCurrentPage - 1) * TH_PAGE_SIZE;
        const end = start + TH_PAGE_SIZE;
        thFilteredCards.slice(start, end).forEach(card => card.classList.remove('d-none'));

        const total = thFilteredCards.length;
        const startNum = total === 0 ? 0 : start + 1;
        const endNum = Math.min(end, total);
        document.getElementById('paginationInfo').textContent =
            total === 0 ? 'No results' : ('Showing ' + startNum + '–' + endNum + ' of ' + total);

        renderFacultyPager();

        document.querySelectorAll('.th-faculty-card').forEach(card => {
            if (card.classList.contains('d-none')) return;

            const btn = card.querySelector('.th-view-btn');
            if (btn && !btn._wired) {
                btn._wired = true;
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    selectFaculty(card.dataset.facultyId);
                });
            }
            if (!card._wired) {
                card._wired = true;
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.th-view-btn')) return;
                    selectFaculty(card.dataset.facultyId);
                });
            }
        });
    }

    function renderFacultyPager() {
        const totalPages = Math.max(1, Math.ceil(thFilteredCards.length / TH_PAGE_SIZE));
        const pager = document.getElementById('paginationList');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (thCurrentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (thCurrentPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, thCurrentPage - 2);
        const wEnd = Math.min(totalPages, thCurrentPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === thCurrentPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (thCurrentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (thCurrentPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === thCurrentPage) return;
                thCurrentPage = p;
                renderFacultyPage();
            });
        });
    }

    /* ============================================================
       TAB 2 — SUBJECT ASSIGNMENTS
       ============================================================
       ⚠ REPLACE fetchSubjects() WITH YOUR REAL API CALL.

       Expected response: array of objects with at least:
         { subject_id, subject_code, subject_title, units,
           faculty_id, faculty_name, department_id, term, status }
       See normalizeSubject() below for the exact field mapping.
       ============================================================ */
    async function fetchSubjects() {
        // TODO: replace with your real API call, e.g.
        //
        // const url = '/api/subjects?department_id=' + DEAN_DEPARTMENT_IDS.join(',');
        // const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        // if (!res.ok) throw new Error('API ' + res.status);
        // const json = await res.json();
        // return Array.isArray(json) ? json : (json.data || []);

        return []; // empty until API is wired
    }

    function normalizeSubject(s) {
        const facultyId   = s.faculty_id ?? s.facultyId ?? null;
        const facultyName = s.faculty_name ?? s.facultyName ?? s.instructor_name ?? null;
        const deptId      = s.department_id ?? s.departmentId ?? s.dept_id ?? null;

        return {
            subject_id:      s.subject_id ?? s.id ?? null,
            subject_code:    s.subject_code ?? s.code ?? '—',
            subject_title:   s.subject_title ?? s.title ?? s.name ?? '—',
            units:           parseFloat(s.units ?? s.unit ?? 0) || 0,
            faculty_id:      facultyId,
            faculty_name:    facultyName,
            department_id:   deptId,
            department_name: s.department_name ?? s.departmentName ?? (DEPT_NAMES[deptId] || '—'),
            term:            s.term ?? s.term_label ?? (s.academic_year && s.semester ? (s.academic_year + ' · ' + s.semester) : '—'),
            status:          s.status ?? (facultyId ? 'Assigned' : 'Unassigned'),
        };
    }

    const SA_PAGE_SIZE = 15;
    let saAll = [];
    let saFiltered = [];
    let saPage = 1;
    let saFilters = { dept: 'all', term: 'all', status: 'all', q: '' };

    function saDeptMatches(row) {
        if (saFilters.dept === 'all') return true;
        return String(row.department_id) === String(saFilters.dept);
    }
    function saTermMatches(row) {
        if (saFilters.term === 'all') return true;
        return String(row.term) === String(saFilters.term);
    }
    function saStatusMatches(row) {
        if (saFilters.status === 'all') return true;
        const s = String(row.status || '').toLowerCase();
        return s === saFilters.status;
    }
    function saQMatches(row) {
        if (!saFilters.q) return true;
        const q = saFilters.q.toLowerCase();
        return (
            String(row.subject_code || '').toLowerCase().indexOf(q) !== -1 ||
            String(row.subject_title || '').toLowerCase().indexOf(q) !== -1 ||
            String(row.faculty_name || '').toLowerCase().indexOf(q) !== -1
        );
    }

    function saRenderKPIs(subjects) {
        const total = subjects.length;
        const assigned = subjects.filter(s => s.faculty_id).length;
        const unassigned = total - assigned;
        const units = subjects.reduce((sum, s) => sum + (parseFloat(s.units) || 0), 0);

        document.getElementById('kpiSubjects').textContent   = total.toLocaleString();
        document.getElementById('kpiAssigned').textContent   = assigned.toLocaleString();
        document.getElementById('kpiUnassigned').textContent = unassigned.toLocaleString();
        document.getElementById('kpiUnits').textContent      = units.toLocaleString(undefined, { maximumFractionDigits: 1 });
    }

    function saRenderTable(rows) {
        const tbody = document.getElementById('assignmentsBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-book-open d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No subject assignments to show.
                </td></tr>`;
            document.getElementById('sa_tableSubtitle').textContent = 'No assignments';
            document.getElementById('sa_pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('sa_pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / SA_PAGE_SIZE));
        if (saPage > totalPages) saPage = totalPages;
        const start = (saPage - 1) * SA_PAGE_SIZE;
        const end = Math.min(start + SA_PAGE_SIZE, total);
        const page = rows.slice(start, end);

        tbody.innerHTML = page.map(s => {
            const isAssigned = !!s.faculty_id;
            const badgeClass = isAssigned ? 'sa-badge--assigned' : 'sa-badge--unassigned';
            const badgeLabel = isAssigned ? 'Assigned' : 'Unassigned';

            return '<tr>' +
                '<td><div class="fw-semibold">' + escapeHtml(s.subject_title) + '</div></td>' +
                '<td class="d-none d-md-table-cell"><code>' + escapeHtml(s.subject_code) + '</code></td>' +
                '<td class="d-none d-sm-table-cell text-center">' + escapeHtml(String(s.units)) + '</td>' +
                '<td class="sa-privacy-target">' + escapeHtml(s.faculty_name || '—') + '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(s.department_name) + '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary">' + escapeHtml(s.term) + '</td>' +
                '<td class="text-center"><span class="sa-badge ' + badgeClass + '">' + badgeLabel + '</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('sa_tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' subject' + (total !== 1 ? 's' : '');
        document.getElementById('sa_pagerInfo').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total;

        saRenderPager(totalPages);
    }

    function saRenderPager(totalPages) {
        const pager = document.getElementById('sa_pager');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (saPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (saPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, saPage - 2);
        const wEnd = Math.min(totalPages, saPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === saPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (saPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (saPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === saPage) return;
                saPage = p;
                saRenderTable(saFiltered);
            });
        });
    }

    function saApplyFilters() {
        return saAll.filter(s => saDeptMatches(s) && saTermMatches(s) && saStatusMatches(s) && saQMatches(s));
    }

    function saPopulateTermFilter() {
        const terms = new Set();
        saAll.forEach(s => { if (s.term && s.term !== '—') terms.add(s.term); });
        const sel = document.getElementById('sa_f_term');
        if (!sel) return;
        sel.innerHTML = '<option value="all">All terms</option>';
        Array.from(terms).sort().reverse().forEach(t => {
            const opt = document.createElement('option');
            opt.value = t; opt.textContent = t;
            sel.appendChild(opt);
        });
    }

    function saRender() {
        saFiltered = saApplyFilters();
        saPage = 1;
        saRenderKPIs(saAll);
        saRenderTable(saFiltered);
    }

    function saBindFilters() {
        document.querySelectorAll('#thPage [data-sa-filter]').forEach(el => {
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, () => {
                if (evt === 'input') {
                    clearTimeout(el._t);
                    el._t = setTimeout(() => {
                        saFilters[el.dataset.saFilter] = el.value;
                        saRender();
                    }, 200);
                } else {
                    saFilters[el.dataset.saFilter] = el.value;
                    saRender();
                }
            });
        });
    }

    function saResetFilters() {
        saFilters = { dept: 'all', term: 'all', status: 'all', q: '' };
        document.querySelectorAll('#thPage [data-sa-filter]').forEach(el => {
            el.value = el.tagName === 'SELECT' ? 'all' : '';
        });
        saRender();
    }

    function saExportCsv(rows) {
        const headers = ['Subject Code', 'Subject Title', 'Units', 'Faculty', 'Department', 'Term', 'Status'];
        const lines = rows.map(r => [
            r.subject_code, r.subject_title, r.units,
            r.faculty_name || '', r.department_name, r.term,
            r.faculty_id ? 'Assigned' : 'Unassigned'
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'subject_assignments_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    async function saLoadAndRender() {
        const loading = document.getElementById('saLoading');
        const error = document.getElementById('saError');
        const errorMsg = document.getElementById('saErrorMsg');

        loading.classList.remove('d-none');
        error.classList.add('d-none');

        try {
            const raw = await fetchSubjects();

            // RBAC re-check — never trust the client-side API response
            const allowed = new Set(DEAN_DEPARTMENT_IDS.map(String));
            saAll = raw
                .map(normalizeSubject)
                .filter(s => {
                    if (s.department_id == null) return true;
                    return allowed.has(String(s.department_id));
                });

            saPopulateTermFilter();
            saRender();
        } catch (e) {
            console.error('[subject-assignments]', e);
            errorMsg.textContent = e.message || 'Could not load subject assignments.';
            error.classList.remove('d-none');
        } finally {
            loading.classList.add('d-none');
        }
    }

    /* ============================================================
       Print — combining both tabs (as needed)
       ============================================================ */
    function renderPrintReport() {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        // Teaching history rows
        const rows = [];
        Object.values(HISTORY_DB).forEach(fac => {
            (fac.history || []).forEach(h => {
                rows.push({
                    faculty: fac.name,
                    facultyNo: fac.faculty_no,
                    department: fac.department,
                    ay: h.academic_year || '—',
                    semester: h.semester || '—',
                    subjects: h.subject_count != null ? h.subject_count : '—',
                    units: h.total_units != null ? h.total_units : '—',
                    students: h.total_students != null ? h.total_students : '—',
                    status: h.status || 'Completed',
                });
            });
        });

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td class="th-print-blur">' + escapeHtml(r.faculty) + '</td>' +
                '<td>' + escapeHtml(r.facultyNo || '—') + '</td>' +
                '<td>' + escapeHtml(r.department) + '</td>' +
                '<td>' + escapeHtml(r.ay) + '</td>' +
                '<td>' + escapeHtml(r.semester) + '</td>' +
                '<td style="text-align:center;">' + r.subjects + '</td>' +
                '<td style="text-align:center;">' + r.units + '</td>' +
                '<td style="text-align:center;">' + r.students + '</td>' +
                '<td>' + escapeHtml(r.status) + '</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Teaching History Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">All Faculty Professors in your assigned departments</p>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Faculty No</th><th>Department</th>
                        <th>School Year</th><th>Semester</th>
                        <th>Subjects</th><th>Units</th><th>Students</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="9" style="text-align:center;">No teaching history in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${rows.length} load record${rows.length !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        // Teaching History tab
        if (thActiveFacultyId) {
            const first = document.querySelector('.th-faculty-card[data-faculty-id="' + thActiveFacultyId + '"]');
            if (first) first.classList.add('th-active');
        }

        const searchInput = document.getElementById('facultySearchInput');
        if (searchInput) {
            let t;
            searchInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    thCurrentPage = 1;
                    initFacultyPagination();
                }, 150);
            });
        }

        document.getElementById('filterAcademicYear').addEventListener('change', renderHistoryData);
        document.getElementById('filterSemester').addEventListener('change', renderHistoryData);

        initFacultyPagination();
        renderHistoryData();

        // Subject Assignments tab
        saBindFilters();
        document.getElementById('sa_reset_filters').addEventListener('click', saResetFilters);
        document.getElementById('sa_exportCsvBtn').addEventListener('click', () => saExportCsv(saFiltered));
        saLoadAndRender();

        // Print
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        // Privacy toggle
        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanTeachingHistoryPrivacy';
        if (pt) {
            const apply = on => document.body.classList.toggle('privacy-mode', on);
            try {
                const saved = localStorage.getItem(KEY) === '1';
                pt.checked = saved; apply(saved);
            } catch (e) { /* ignore */ }
            pt.addEventListener('change', () => {
                apply(pt.checked);
                try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

        renderPrintReport();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>