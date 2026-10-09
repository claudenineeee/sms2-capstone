<?php
/**
 * SMS 2 - Dean - Teaching History
 * Faculty Professor teaching load history, RBAC-scoped to the dean's departments.
 * Same topography as faculty-profile.php / department-overview.php.
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
   LOAD FACULTY + THEIR TEACHING HISTORY
   ============================================================ */
$facultyMembers    = [];
$teachingHistoryDB = [];
$allAcademicYears  = [];
$allSemesters      = [];
$loadError         = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ---- Faculty — only Faculty Professors in the dean's departments ---- */
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));
        $facStmt = $pdo->prepare("
            SELECT
                f.faculty_id,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
                f.email,
                f.department_id,
                f.position,
                f.profile_status,
                d.code AS dept_code,
                d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyMembers = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Teaching history for those faculty ---- */
        foreach ($facultyMembers as $fac) {
            $realFacultyId = (int) $fac['faculty_id'];

            $historyRecords = [];
            try {
                $stmtHistory = $pdo->prepare("
                    SELECT
                        tlh.history_id,
                        tlh.term_id,
                        tlh.subject_count,
                        tlh.total_units,
                        tlh.total_students,
                        tlh.status,
                        at.academic_year,
                        at.semester
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
                Faculty Professor teaching logs for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
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

    <!-- ================= Two-Column Layout ================= -->
    <div class="row g-3">

        <!-- LEFT: Faculty list -->
        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm h-100">
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

<!-- Print-only report -->
<div class="th-print-only" id="printReport" aria-hidden="true"></div>

<style>
    .th-faculty-scroll {
        max-height: 480px;
        overflow-y: auto;
    }
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
    .th-faculty-meta {
        font-size: 0.72rem;
    }
    .th-avatar {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.72rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }
    .th-view-btn {
        font-size: 0.72rem;
        flex-shrink: 0;
    }
    .th-avatar-lg {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.95rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }
    .th-table-container {
        max-height: 480px;
        overflow-y: auto;
    }
    .th-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--sms-table-head-bg);
        border-bottom: 1px solid var(--sms-table-border);
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
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
    .th-table tbody tr:hover td {
        background: var(--sms-dropdown-hover);
    }
    .th-pagination .page-link {
        min-width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 8px !important;
        padding: 0 0.4rem;
    }
    .privacy-mode .th-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .th-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

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
    }
    @media (max-width: 360px) {
        .th-table tbody td,
        .th-table thead th { font-size: 0.68rem; padding: 0.45rem 0.4rem; }
    }

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

    const HISTORY_DB = <?= json_encode($teachingHistoryDB, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 10;

    let activeFacultyId = Object.keys(HISTORY_DB)[0] || null;
    let currentPage = 1;
    let filteredCards = [];

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /* ============================================================
       Faculty selection
       ============================================================ */
    function selectFaculty(facId) {
        activeFacultyId = String(facId);
        document.getElementById('filterAcademicYear').value = '';
        document.getElementById('filterSemester').value = '';

        document.querySelectorAll('.th-faculty-card').forEach(card => {
            card.classList.toggle('th-active', card.dataset.facultyId === String(facId));
        });

        renderData();
    }

    /* ============================================================
       Render history log
       ============================================================ */
    function renderData() {
        const fac = HISTORY_DB[activeFacultyId];
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

    /* ============================================================
       Faculty list — pagination + search
       ============================================================ */
    function initFacultyPagination() {
        const query = (document.getElementById('facultySearchInput').value || '').toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.th-faculty-card'));

        filteredCards = allCards.filter(card => {
            const name = (card.dataset.name || '').toLowerCase();
            return !query || name.indexOf(query) !== -1;
        });

        document.getElementById('facultyCountBadge').textContent = filteredCards.length;
        const totalPages = Math.max(1, Math.ceil(filteredCards.length / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = 1;

        renderFacultyPage();
    }

    function renderFacultyPage() {
        const allCards = document.querySelectorAll('.th-faculty-card');
        allCards.forEach(card => card.classList.add('d-none'));

        const start = (currentPage - 1) * PAGE_SIZE;
        const end = start + PAGE_SIZE;
        const pageItems = filteredCards.slice(start, end);
        pageItems.forEach(card => card.classList.remove('d-none'));

        const total = filteredCards.length;
        const startNum = total === 0 ? 0 : start + 1;
        const endNum = Math.min(end, total);
        document.getElementById('paginationInfo').textContent =
            total === 0 ? 'No results' : ('Showing ' + startNum + '–' + endNum + ' of ' + total);

        renderPaginationControls();

        // Wire up View buttons + card clicks for the currently visible cards
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

    function renderPaginationControls() {
        const totalPages = Math.max(1, Math.ceil(filteredCards.length / PAGE_SIZE));
        const pager = document.getElementById('paginationList');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, currentPage - 2);
        const wEnd = Math.min(totalPages, currentPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === currentPage) return;
                currentPage = p;
                renderFacultyPage();
            });
        });
    }

    /* ============================================================
       Print report
       ============================================================ */
    function renderPrintReport() {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

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
                        <th>Faculty</th>
                        <th>Faculty No</th>
                        <th>Department</th>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Subjects</th>
                        <th>Units</th>
                        <th>Students</th>
                        <th>Status</th>
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
        // Highlight the first card if data exists
        if (activeFacultyId) {
            const first = document.querySelector('.th-faculty-card[data-faculty-id="' + activeFacultyId + '"]');
            if (first) first.classList.add('th-active');
        }

        // Search (debounced)
        const searchInput = document.getElementById('facultySearchInput');
        if (searchInput) {
            let t;
            searchInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    currentPage = 1;
                    initFacultyPagination();
                }, 150);
            });
        }

        // Year / semester filters
        document.getElementById('filterAcademicYear').addEventListener('change', renderData);
        document.getElementById('filterSemester').addEventListener('change', renderData);

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

        initFacultyPagination();
        renderData();
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