<?php
/**
 * SMS 2 - Dean - Subject Assignments
 * Reviews subject assignments for the dean's departments.
 * Data comes from an external REST API (not faculty_db).
 * Same topography & cards as faculty-profile.php / department-overview.php.
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
   Page config
   ============================================================ */
$pageTitle    = 'Subject Assignments';
$activeModule = 'faculty';
$activePage   = 'subject-assignments';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Subject Assignments', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="saPage">

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

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Subject Assignments Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-book text-primary"></i>
                <span>Subject Assignments</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Faculty-to-subject assignments for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="exportCsvBtn">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">CSV</span>
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="printBtn">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= Loading / Error banners ================= -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3 d-none" id="saLoading" role="alert">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <div>Loading subject assignments…</div>
    </div>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 d-none" id="saError" role="alert">
        <i class="fas fa-triangle-exclamation"></i>
        <div id="saErrorMsg">Could not load subject assignments.</div>
    </div>

    <!-- ================= KPI Cards ================= -->
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

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <?php if (count($deanDepartments) > 1): ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_dept">Department</label>
                    <select class="form-select form-select-sm" id="f_dept" data-filter="dept">
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
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_term">Term</label>
                    <select class="form-select form-select-sm" id="f_term" data-filter="term">
                        <option value="all">All terms</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" data-filter="status">
                        <option value="all">All statuses</option>
                        <option value="assigned">Assigned</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_q">Search</label>
                    <input type="text" class="form-control form-control-sm" id="f_q" data-filter="q" placeholder="Subject or faculty…" autocomplete="off">
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Subject Assignments Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Assignments</h5>
                <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#10b981;"></span>Assigned
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59e0b;"></span>Unassigned
                </span>
            </div>
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
            <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
            <nav aria-label="Assignments pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="sa-print-only" id="printReport" aria-hidden="true"></div>

<style>
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

    .privacy-mode .sa-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .sa-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    @media (max-width: 400px) {
        #saPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #saPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .sa-table tbody td,
        .sa-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
    }
    @media (max-width: 360px) {
        .sa-table tbody td,
        .sa-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    .sa-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #saPage { display: none !important; }
        .sa-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .sa-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .sa-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .sa-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .sa-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .sa-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .sa-print-only .print-kpi { flex: 1; text-align: center; }
        .sa-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .sa-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .sa-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .sa-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .sa-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .sa-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .sa-print-only td.sa-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .sa-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .sa-print-only thead { display: table-header-group; }
        .sa-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    /* ============================================================
       Server-injected RBAC context
       ============================================================ */
    const DEAN_DEPARTMENT_IDS = <?= json_encode(array_values($deanDepartments), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES          = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    /* ============================================================
       ═══════════════════════════════════════════════════════════
       ⬇⬇⬇  PLACE YOUR REST API CALLS HERE  ⬇⬇⬇
       ═══════════════════════════════════════════════════════════

       Contract — your API should return a JSON array of subjects.
       Each subject object MUST have at least these fields (rename
       the mapping below if your API uses different keys):

       {
           "subject_id":    123,                        // required
           "subject_code":  "IT 101",                   // required
           "subject_title": "Introduction to Computing",// required
           "units":         3,                          // number
           "faculty_id":    53,                         // int or null if unassigned
           "faculty_name":  "Prof. Juan Dela Cruz",     // string or null
           "department_id": 1,                          // int — used for RBAC filter
           "department_name": "BSIT",                   // string (optional; falls back to DEPT_NAMES)
           "term":          "2026-2027 · 1st Semester", // string (optional)
           "status":        "Assigned"                  // "Assigned" | "Unassigned" (optional — we compute it if missing)
       }

       Recommended endpoint shape:
           GET /api/subjects?department_id=1,2,3&term=2026-2027

       Below, `fetchSubjects()` is the ONLY function you need to edit.
       The rest of the page works with whatever it returns.

       If your API uses a different key for any field, change it in
       `normalizeSubject()` (right below the fetch call) — that's the
       single place where API field names are mapped to this page's
       internal field names.

       ═══════════════════════════════════════════════════════════
       ⬆⬆⬆  END OF PLACEHOLDERS  ⬆⬆⬆
       ═══════════════════════════════════════════════════════════
       ============================================================ */

    /**
     * Fetch subjects from the REST API.
     *
     * ⚠️  REPLACE THIS IMPLEMENTATION
     *
     * @returns {Promise<Array>}  resolves to array of subject objects
     */
    async function fetchSubjects() {
        // ─── EXAMPLE IMPLEMENTATION (delete when wiring real API) ───
        //
        // const deptParam = DEAN_DEPARTMENT_IDS.join(',');
        // const url = `/api/subjects?department_id=${encodeURIComponent(deptParam)}`;
        //
        // const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        // const response = await fetch(url, {
        //     method: 'GET',
        //     credentials: 'same-origin',       // include session cookies
        //     headers: {
        //         'Accept': 'application/json',
        //         'X-Requested-With': 'XMLHttpRequest',
        //         'X-CSRF-Token': token,
        //     },
        // });
        //
        // if (!response.ok) {
        //     throw new Error('API returned ' + response.status);
        // }
        //
        // const json = await response.json();
        // return Array.isArray(json) ? json : (json.data || []);
        //
        // ─── END EXAMPLE ───

        // Temporary: return an empty array so the page renders with the
        // "No assignments yet" empty state until the API is wired up.
        return [];

        // ─── OR, to test the page with fake data before your API is ready,
        //     uncomment this block: ───
        //
        // return [
        //     { subject_id: 1, subject_code: 'IT 101', subject_title: 'Introduction to Computing',
        //       units: 3, faculty_id: 53, faculty_name: 'Prof. Jean Espejo',
        //       department_id: 1, term: '2026-2027 · 1st Semester' },
        //     { subject_id: 2, subject_code: 'IT 102', subject_title: 'Computer Programming 1',
        //       units: 3, faculty_id: null, faculty_name: null,
        //       department_id: 1, term: '2026-2027 · 1st Semester' },
        // ];
    }

    /**
     * Normalize one subject object from the API into the shape the
     * rest of this page expects.
     *
     * ⚠️  EDIT THE RIGHT-HAND SIDE if your API uses different keys.
     */
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

    /* ============================================================
       STATE
       ============================================================ */
    const PAGE_SIZE = 15;
    let allSubjects = [];
    let filteredSubjects = [];
    let currentPage = 1;

    let filters = { dept: 'all', term: 'all', status: 'all', q: '' };

    /* ============================================================
       Helpers
       ============================================================ */
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }
    function termMatches(row) {
        if (filters.term === 'all') return true;
        return String(row.term) === String(filters.term);
    }
    function statusMatches(row) {
        if (filters.status === 'all') return true;
        const s = String(row.status || '').toLowerCase();
        return s === filters.status;
    }
    function qMatches(row) {
        if (!filters.q) return true;
        const q = filters.q.toLowerCase();
        return (
            String(row.subject_code || '').toLowerCase().indexOf(q) !== -1 ||
            String(row.subject_title || '').toLowerCase().indexOf(q) !== -1 ||
            String(row.faculty_name || '').toLowerCase().indexOf(q) !== -1
        );
    }

    /* ============================================================
       Render KPIs
       ============================================================ */
    function renderKPIs(subjects) {
        const total = subjects.length;
        const assigned = subjects.filter(s => s.faculty_id).length;
        const unassigned = total - assigned;
        const units = subjects.reduce((sum, s) => sum + (parseFloat(s.units) || 0), 0);

        document.getElementById('kpiSubjects').textContent   = total.toLocaleString();
        document.getElementById('kpiAssigned').textContent   = assigned.toLocaleString();
        document.getElementById('kpiUnassigned').textContent = unassigned.toLocaleString();
        document.getElementById('kpiUnits').textContent      = units.toLocaleString(undefined, { maximumFractionDigits: 1 });
    }

    /* ============================================================
       Render table
       ============================================================ */
    function renderTable(rows) {
        const tbody = document.getElementById('assignmentsBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-book-open d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                    No subject assignments to show.
                </td></tr>`;
            document.getElementById('tableSubtitle').textContent = 'No assignments';
            document.getElementById('pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, total);
        const page = rows.slice(start, end);

        tbody.innerHTML = page.map(s => {
            const isAssigned = !!s.faculty_id;
            const badgeClass = isAssigned ? 'sa-badge--assigned' : 'sa-badge--unassigned';
            const badgeLabel = isAssigned ? 'Assigned' : 'Unassigned';

            return '<tr>' +
                '<td>' +
                    '<div class="fw-semibold">' + escapeHtml(s.subject_title) + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell"><code>' + escapeHtml(s.subject_code) + '</code></td>' +
                '<td class="d-none d-sm-table-cell text-center">' + escapeHtml(String(s.units)) + '</td>' +
                '<td class="sa-privacy-target">' + escapeHtml(s.faculty_name || '—') + '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(s.department_name) + '</td>' +
                '<td class="d-none d-lg-table-cell small text-body-secondary">' + escapeHtml(s.term) + '</td>' +
                '<td class="text-center"><span class="sa-badge ' + badgeClass + '">' + badgeLabel + '</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' subject' + (total !== 1 ? 's' : '');
        document.getElementById('pagerInfo').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total;

        renderPager(totalPages);
    }

    function renderPager(totalPages) {
        const pager = document.getElementById('pager');
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
                renderTable(filteredSubjects);
            });
        });
    }

    /* ============================================================
       Print report
       ============================================================ */
    function renderPrintReport(rows) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const total = rows.length;
        const assigned = rows.filter(s => s.faculty_id).length;
        const unassigned = total - assigned;
        const units = rows.reduce((sum, s) => sum + (parseFloat(s.units) || 0), 0);

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td class="sa-print-blur">' + escapeHtml(r.subject_title) + '</td>' +
                '<td>' + escapeHtml(r.subject_code) + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(String(r.units)) + '</td>' +
                '<td>' + escapeHtml(r.faculty_name || '—') + '</td>' +
                '<td>' + escapeHtml(r.department_name) + '</td>' +
                '<td>' + escapeHtml(r.term) + '</td>' +
                '<td>' + escapeHtml(r.faculty_id ? 'Assigned' : 'Unassigned') + '</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Subject Assignments Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">All subjects in your assigned departments</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Subjects</div>
                    <div class="print-kpi-value">${total}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Assigned</div>
                    <div class="print-kpi-value">${assigned}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Unassigned</div>
                    <div class="print-kpi-value">${unassigned}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Total Units</div>
                    <div class="print-kpi-value">${units}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th><th>Code</th><th>Units</th>
                        <th>Faculty</th><th>Department</th><th>Term</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="7" style="text-align:center;">No subject assignments in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} subject${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Subject Code', 'Subject Title', 'Units', 'Faculty', 'Department', 'Term', 'Status'];
        const lines = rows.map(r => [
            r.subject_code,
            r.subject_title,
            r.units,
            r.faculty_name || '',
            r.department_name,
            r.term,
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

    /* ============================================================
       Filters
       ============================================================ */
    function applyFilters() {
        return allSubjects.filter(s =>
            deptMatches(s) && termMatches(s) && statusMatches(s) && qMatches(s)
        );
    }

    function populateTermFilter() {
        const terms = new Set();
        allSubjects.forEach(s => { if (s.term && s.term !== '—') terms.add(s.term); });
        const sel = document.getElementById('f_term');
        if (!sel) return;
        sel.innerHTML = '<option value="all">All terms</option>';
        Array.from(terms).sort().reverse().forEach(t => {
            const opt = document.createElement('option');
            opt.value = t; opt.textContent = t;
            sel.appendChild(opt);
        });
    }

    function render() {
        filteredSubjects = applyFilters();
        currentPage = 1;
        renderKPIs(allSubjects);
        renderTable(filteredSubjects);
        renderPrintReport(filteredSubjects);
    }

    function bindFilters() {
        document.querySelectorAll('#saPage [data-filter]').forEach(el => {
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, () => {
                if (evt === 'input') {
                    clearTimeout(el._t);
                    el._t = setTimeout(() => {
                        filters[el.dataset.filter] = el.value;
                        render();
                    }, 200);
                } else {
                    filters[el.dataset.filter] = el.value;
                    render();
                }
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', term: 'all', status: 'all', q: '' };
        document.querySelectorAll('#saPage [data-filter]').forEach(el => {
            el.value = el.tagName === 'SELECT' ? 'all' : '';
        });
        render();
    }

    /* ============================================================
       Load + Init
       ============================================================ */
    async function loadAndRender() {
        const loading = document.getElementById('saLoading');
        const error = document.getElementById('saError');
        const errorMsg = document.getElementById('saErrorMsg');

        loading.classList.remove('d-none');
        error.classList.add('d-none');

        try {
            const raw = await fetchSubjects();

            // ─── RBAC re-check (never trust the API) ───
            // The API should already filter by department, but we defensively
            // reject any subject that isn't in the dean's assigned departments.
            const allowed = new Set(DEAN_DEPARTMENT_IDS.map(String));
            allSubjects = raw
                .map(normalizeSubject)
                .filter(s => {
                    // If the API doesn't send department_id, keep it
                    // (the API is trusted to have already filtered).
                    if (s.department_id == null) return true;
                    return allowed.has(String(s.department_id));
                });

            populateTermFilter();
            render();

        } catch (e) {
            console.error('[subject-assignments]', e);
            errorMsg.textContent = e.message || 'Could not load subject assignments.';
            error.classList.remove('d-none');
        } finally {
            loading.classList.add('d-none');
        }
    }

    function init() {
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredSubjects));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanSubjectAssignmentsPrivacy';
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

        loadAndRender();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>