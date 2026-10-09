<?php
/**
 * SMS 2 - Dean - Class Schedules
 * Class schedule viewer for Faculty Professors, RBAC-scoped to the dean's departments.
 *
 * ----------------------------------------------------------------------------
 * FUTURE INTEGRATION NOTE
 * ----------------------------------------------------------------------------
 * Class schedules are intended to be sourced from an external module via REST
 * API in a later phase. For now, this page reads from the local faculty_db
 * tables (class_schedules, subjects, rooms, academic_terms).
 *
 * When the REST integration is ready:
 *   1. Replace the "LOCAL QUERY" block below (search for: INTEGRATION POINT)
 *      with a call to the external endpoint, e.g.
 *        GET {EXTERNAL_API_BASE}/schedules?department_ids[]=1&department_ids[]=2
 *   2. Normalize the external payload into the same $schedules array shape
 *      used further down in this file (see $schedules[] structure).
 *   3. Keep the RBAC scoping server-side — send the dean's department list
 *      to the API, but never accept a department_id from the client.
 *   4. Failure handling: if the API call fails, set $loadError and let the
 *      empty state render — never fall back to unscoped data.
 * ----------------------------------------------------------------------------
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
   LOAD SCHEDULES — scoped to dean's departments
   ============================================================ */
$schedules       = [];
$loadError       = null;
$integrationMode = 'local'; // ← swap to 'rest' when the external API is wired up

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        /* ========================================================
           INTEGRATION POINT
           --------------------------------------------------------
           When the external REST API is available, replace the
           LOCAL QUERY block below with an HTTP call. Example stub:

             $restUrl = EXTERNAL_API_BASE . '/schedules'
                      . '?department_ids=' . urlencode(implode(',', $deanDepartments))
                      . '&position=' . urlencode('Faculty Professor');

             $ch = curl_init($restUrl);
             curl_setopt_array($ch, [
                 CURLOPT_RETURNTRANSFER => true,
                 CURLOPT_HTTPHEADER     => [
                     'Authorization: Bearer ' . EXTERNAL_API_TOKEN,
                     'Accept: application/json',
                 ],
                 CURLOPT_TIMEOUT        => 10,
             ]);
             $body   = curl_exec($ch);
             $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
             curl_close($ch);

             if ($status !== 200 || !$body) {
                 throw new RuntimeException('External schedule API failed');
             }

             $payload   = json_decode($body, true) ?: [];
             $schedules = normalizeExternalSchedules($payload); // your mapper
             $integrationMode = 'rest';
           ======================================================== */

        /* ---------------- LOCAL QUERY (default) ---------------- */
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        $sql = "
            SELECT
                cs.class_schedule_id AS schedule_id,
                cs.faculty_id,
                f.faculty_no,
                f.first_name,
                f.last_name,
                f.department_id,
                d.code AS dept_code,
                d.name AS dept_name,
                s.code  AS subject_code,
                s.title AS subject_title,
                cs.section,
                cs.units,
                r.room_code,
                cs.day_pattern,
                cs.time_start,
                cs.time_end,
                cs.term_id,
                at.academic_year,
                at.semester,
                cs.status,
                cs.enrolled_students
            FROM faculty_db.class_schedules cs
            INNER JOIN faculty_db.faculty f        ON f.faculty_id    = cs.faculty_id
            LEFT  JOIN faculty_db.departments d    ON d.department_id = f.department_id
            LEFT  JOIN faculty_db.subjects s       ON s.subject_id    = cs.subject_id
            LEFT  JOIN faculty_db.rooms r          ON r.room_id       = cs.room_id
            LEFT  JOIN faculty_db.academic_terms at ON at.term_id     = cs.term_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
              AND NOT EXISTS (
                  SELECT 1
                  FROM faculty_db.faculty_profiles fp
                  WHERE (fp.faculty_id = f.faculty_no OR fp.user_id = f.external_user_id)
                    AND fp.position = 'Department Head'
              )
            ORDER BY at.academic_year DESC, at.semester ASC,
                     FIELD(cs.day_pattern, 'M', 'T', 'W', 'Th', 'F', 'S'),
                     cs.time_start ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($teachingPositions);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            $schedules[] = [
                'schedule_id'       => (int) $r['schedule_id'],
                'faculty_id'        => (int) $r['faculty_id'],
                'faculty_no'        => $r['faculty_no'] ?? '',
                'faculty_name'      => $fullName ?: '—',
                'department_id'     => (int) $r['department_id'],
                'department_name'   => $r['dept_name'] ?: ($r['dept_code'] ?: '—'),
                'subject_code'      => $r['subject_code'] ?? '—',
                'subject_title'     => $r['subject_title'] ?? '—',
                'section'           => $r['section'] ?? '—',
                'units'             => (float) ($r['units'] ?? 0),
                'room_code'         => $r['room_code'] ?? '—',
                'day_pattern'       => $r['day_pattern'] ?? '—',
                'time_start'        => $r['time_start'] ?? '',
                'time_end'          => $r['time_end'] ?? '',
                'term_id'           => (int) ($r['term_id'] ?? 0),
                'academic_year'     => $r['academic_year'] ?? '—',
                'semester'          => $r['semester'] ?? '—',
                'status'            => $r['status'] ?? 'Proposed',
                'enrolled_students' => $r['enrolled_students'] !== null ? (int) $r['enrolled_students'] : null,
            ];
        }
        /* ---------------- END LOCAL QUERY ---------------- */

    } catch (Throwable $e) {
        error_log('Class schedules load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Class Schedules';
$activeModule = 'faculty';
$activePage   = 'class-schedules';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Class Schedules', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="csPage">

    <?php if (!$hasDeanDepartments): ?>
        <div class="card border shadow-sm">
            <div class="card-body text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:84px;height:84px;background:rgba(100,116,139,0.10);color:#64748b;font-size:2.2rem;">
                    <i class="fas fa-building-circle-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>
                <p class="text-body-secondary mb-0 mx-auto" style="max-width:420px;">
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
        <h4 class="mb-0 fw-bold">Class Schedules Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-calendar-days text-primary"></i>
                <span>Class Schedules</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Assigned class schedules for Faculty Professors in the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0 small fw-semibold"
                   for="privacyModeToggle" style="cursor:pointer;">
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

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Schedules</h6>
                        <h4 class="mb-0 fw-bold text-primary" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-primary" style="font-size:0.7rem;">Total class entries</small>
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
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Approved</h6>
                        <h4 class="mb-0 fw-bold text-success" id="kpiApproved">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-success" style="font-size:0.7rem;">
                            <span id="kpiApprovedPct">0%</span> of schedules
                        </small>
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
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Proposed</h6>
                        <h4 class="mb-0 fw-bold text-warning" id="kpiProposed">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-warning" style="font-size:0.7rem;">
                            <span id="kpiProposedPct">0%</span> pending action
                        </small>
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
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Rejected</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiRejected">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">
                            <span id="kpiRejectedPct">0%</span> of schedules
                        </small>
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
                <div class="col-6 col-md-4 col-lg-2">
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

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_ay">School Year</label>
                    <select class="form-select form-select-sm" id="f_ay" data-filter="ay">
                        <option value="">All years</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sem">Semester</label>
                    <select class="form-select form-select-sm" id="f_sem" data-filter="sem">
                        <option value="">All semesters</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_day">Day</label>
                    <select class="form-select form-select-sm" id="f_day" data-filter="day">
                        <option value="">All days</option>
                        <option value="M">Monday</option>
                        <option value="T">Tuesday</option>
                        <option value="W">Wednesday</option>
                        <option value="Th">Thursday</option>
                        <option value="F">Friday</option>
                        <option value="S">Saturday</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" data-filter="status">
                        <option value="">All statuses</option>
                        <option value="Approved">Approved</option>
                        <option value="Proposed">Proposed</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">

        <!-- ROW 1 · Weekly Timetable Density (wider) + Faculty Load Summary (narrower) -->
        <div class="col-12 col-lg-8">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Weekly Timetable Density</h5>
                            <p class="text-body-secondary small mb-0">Classes per day &amp; time block</p>
                        </div>
                        <span class="badge text-bg-light border" id="heatmapBadge">0 slots</span>
                    </div>
                    <div class="position-relative flex-grow-1" style="min-height:340px;">
                        <div id="timetableGrid" style="display:grid;grid-template-columns:80px repeat(6, minmax(0,1fr));gap:6px;min-height:340px;"></div>
                        <div class="cs-empty d-none" id="timetableEmpty">
                            <i class="fas fa-table-cells"></i>
                            <h6>No schedules yet</h6>
                            <p>Timetable density will appear once schedules exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Faculty Load Summary</h5>
                            <p class="text-body-secondary small mb-0">Units per faculty member</p>
                        </div>
                        <span class="badge text-bg-light border" id="loadBadge">0 faculty</span>
                    </div>
                    <div class="cs-scroll flex-grow-1" id="loadSummary" style="max-height:340px;overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        <!-- ROW 2 · Day Distribution (narrower) + Class Schedule Records (wider) -->
        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Day Distribution</h5>
                            <p class="text-body-secondary small mb-0">Classes per day of the week</p>
                        </div>
                        <span class="badge text-bg-light border" id="dayBadge">0 days</span>
                    </div>
                    <div class="position-relative flex-grow-1 cs-scroll" id="dayListWrap" style="max-height:520px;overflow-y:auto;">
                        <div id="dayList" class="d-flex flex-column gap-2"></div>
                        <div class="cs-empty d-none" id="dayEmpty">
                            <i class="fas fa-calendar-day"></i>
                            <h6>No day data</h6>
                            <p>Distribution will show here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card border shadow-sm h-100 overflow-hidden d-flex flex-column">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-2 py-3">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Class Schedule Records</h5>
                        <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center small fw-semibold text-body-secondary">
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="d-inline-block rounded-circle bg-success" style="width:8px;height:8px;"></span>Apr
                        </span>
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="d-inline-block rounded-circle bg-warning" style="width:8px;height:8px;"></span>Pro
                        </span>
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="d-inline-block rounded-circle bg-danger" style="width:8px;height:8px;"></span>Rej
                        </span>
                    </div>
                </div>

                <div class="cs-scroll flex-grow-1" style="max-height:400px;overflow-y:auto;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 860px;">
                            <thead class="sticky-top">
                                <tr>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Subject</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Section</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Day</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Time</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary text-center d-none d-md-table-cell">Units</th>
                                    <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
                    <nav aria-label="Schedule pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="cs-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* Empty overlay */
    .cs-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; pointer-events: none;
    }
    .cs-empty i { font-size: 2rem; opacity: .35; margin-bottom: .6rem; color: var(--sms-text-muted); }
    .cs-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: .2rem; font-size: .9rem; }
    .cs-empty p { font-size: .78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* Thin themed scrollbar — matches evaluation-summary accent */
    .cs-scroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(13,110,253,.28) transparent;
    }
    .cs-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
    .cs-scroll::-webkit-scrollbar-track { background: transparent; }
    .cs-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,.28);
        border-radius: 999px;
    }
    .cs-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(13,110,253,.55); }

    /* Dark mode scrollbar tweak */
    [data-theme="dark"] .cs-scroll { scrollbar-color: rgba(96,165,250,.35) transparent; }
    [data-theme="dark"] .cs-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(96,165,250,.35);
    }
    [data-theme="dark"] .cs-scroll::-webkit-scrollbar-thumb:hover {
        background-color: rgba(96,165,250,.65);
    }

    /* Day row */
    .cs-day-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .55rem .75rem;
        border: 1px solid var(--sms-border-soft);
        border-radius: 10px;
        background: var(--sms-surface-muted);
    }

    /* Privacy blur */
    .privacy-mode .cs-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter .15s ease;
    }
    .privacy-mode .cs-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive */
    @media (max-width: 991.98px) {
        #dayListWrap { max-height: 340px !important; }
    }
    @media (max-width: 400px) {
        #csPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #csPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: .7rem .6rem .7rem .9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: .62rem !important; letter-spacing: .03em !important; }
        .stat-card small { font-size: .65rem !important; }
        #timetableGrid { grid-template-columns: 56px repeat(6, minmax(0,1fr)) !important; gap: 4px !important; }
        #loadSummary { max-height: 280px !important; }
    }

    /* Print */
    .cs-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #csPage { display: none !important; }
        .cs-print-only {
            display: block !important;
            padding: .4in .35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .cs-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .cs-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .cs-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .cs-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .cs-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .cs-print-only .print-kpi { flex: 1; text-align: center; }
        .cs-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: #555;
        }
        .cs-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .cs-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .cs-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: .4px; font-weight: 700;
        }
        .cs-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .cs-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .cs-print-only td.cs-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .cs-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .cs-print-only thead { display: table-header-group; }
        .cs-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    const SCHEDULES = <?= json_encode($schedules, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PAGE_SIZE = 10;

    const DAY_ORDER = ['M', 'T', 'W', 'Th', 'F', 'S'];
    const DAY_LABEL = { 'M': 'Monday', 'T': 'Tuesday', 'W': 'Wednesday', 'Th': 'Thursday', 'F': 'Friday', 'S': 'Saturday' };
    const TIME_BLOCKS = [
        { label: '6–8 AM',   start: 6 * 60,  end: 8 * 60 },
        { label: '8–10 AM',  start: 8 * 60,  end: 10 * 60 },
        { label: '10–12',    start: 10 * 60, end: 12 * 60 },
        { label: '12–2 PM',  start: 12 * 60, end: 14 * 60 },
        { label: '2–4 PM',   start: 14 * 60, end: 16 * 60 },
        { label: '4–6 PM',   start: 16 * 60, end: 18 * 60 },
        { label: '6–9 PM',   start: 18 * 60, end: 21 * 60 }
    ];

    let filters = { dept: 'all', ay: '', sem: '', day: '', status: '' };
    let filteredData = [];
    let currentPage = 1;

    /* ============================================================
       Helpers
       ============================================================ */
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function fmtTime(t) {
        if (!t) return '—';
        const parts = String(t).split(':');
        if (parts.length < 2) return t;
        let h = parseInt(parts[0], 10);
        const m = parts[1];
        const ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ap;
    }

    function timeToMinutes(t) {
        if (!t) return 0;
        const parts = String(t).split(':');
        if (parts.length < 2) return 0;
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function splitDays(pattern) {
        if (!pattern) return [];
        const clean = String(pattern).replace(/\s+/g, '').replace(/,/g, '');
        const text = clean
            .replace(/Mon/gi, 'M').replace(/Tue(s)?/gi, 'T').replace(/Wed/gi, 'W')
            .replace(/Thu(r(s)?)?/gi, 'Th').replace(/Fri/gi, 'F').replace(/Sat/gi, 'S')
            .replace(/Sun/gi, '');
        const out = [];
        let i = 0;
        while (i < text.length) {
            if (text.substring(i, i + 2).toLowerCase() === 'th') { out.push('Th'); i += 2; continue; }
            const ch = text[i];
            if (['M', 'T', 'W', 'F', 'S'].includes(ch)) out.push(ch);
            i++;
        }
        return Array.from(new Set(out));
    }

    function statusBadge(status) {
        const map = {
            'Approved': 'bg-success-subtle text-success border-success-subtle',
            'Proposed': 'bg-warning-subtle text-warning border-warning-subtle',
            'Rejected': 'bg-danger-subtle text-danger border-danger-subtle'
        };
        const cls = map[status] || map['Proposed'];
        return '<span class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-2 border fw-semibold text-nowrap ' + cls + '" style="font-size:.72rem;">' +
               escapeHtml(status || '—') + '</span>';
    }

    /* ============================================================
       Filters
       ============================================================ */
    function applyFilters() {
        return SCHEDULES.filter(r => {
            if (filters.dept !== 'all' && String(r.department_id) !== String(filters.dept)) return false;
            if (filters.ay   && r.academic_year !== filters.ay)   return false;
            if (filters.sem  && r.semester      !== filters.sem)  return false;
            if (filters.status && r.status      !== filters.status) return false;
            if (filters.day) {
                const days = splitDays(r.day_pattern);
                if (!days.includes(filters.day)) return false;
            }
            return true;
        });
    }

    /* ============================================================
       KPIs
       ============================================================ */
    function renderKPIs(rows) {
        const total    = rows.length;
        const approved = rows.filter(r => r.status === 'Approved').length;
        const proposed = rows.filter(r => r.status === 'Proposed').length;
        const rejected = rows.filter(r => r.status === 'Rejected').length;

        document.getElementById('kpiTotal').textContent    = total.toLocaleString();
        document.getElementById('kpiApproved').textContent = approved.toLocaleString();
        document.getElementById('kpiProposed').textContent = proposed.toLocaleString();
        document.getElementById('kpiRejected').textContent = rejected.toLocaleString();

        const pct = n => total > 0 ? Math.round((n / total) * 100) + '%' : '0%';
        document.getElementById('kpiApprovedPct').textContent = pct(approved);
        document.getElementById('kpiProposedPct').textContent = pct(proposed);
        document.getElementById('kpiRejectedPct').textContent = pct(rejected);
    }

    /* ============================================================
       Weekly timetable
       ============================================================ */
    function renderTimetable(rows) {
        const grid = document.getElementById('timetableGrid');
        const emptyEl = document.getElementById('timetableEmpty');

        const matrix = {};
        DAY_ORDER.forEach(d => { matrix[d] = TIME_BLOCKS.map(() => 0); });

        rows.forEach(r => {
            const days = splitDays(r.day_pattern);
            if (days.length === 0) return;
            const startMin = timeToMinutes(r.time_start);
            const endMin   = timeToMinutes(r.time_end) || (startMin + 60);

            days.forEach(d => {
                if (!matrix[d]) return;
                TIME_BLOCKS.forEach((blk, idx) => {
                    if (startMin < blk.end && endMin > blk.start) matrix[d][idx]++;
                });
            });
        });

        let totalSlots = 0;
        Object.values(matrix).forEach(arr => arr.forEach(v => totalSlots += v));

        document.getElementById('heatmapBadge').textContent = totalSlots + ' slot' + (totalSlots !== 1 ? 's' : '');

        if (rows.length === 0) {
            emptyEl.classList.remove('d-none');
            grid.innerHTML = '';
            return;
        }
        emptyEl.classList.add('d-none');

        let maxCount = 0;
        Object.values(matrix).forEach(arr => arr.forEach(v => { if (v > maxCount) maxCount = v; }));

        const cellBg = (count) => {
            if (count === 0) return 'background:var(--sms-surface-muted);color:var(--sms-text-muted);';
            const ratio = count / Math.max(maxCount, 1);
            if (ratio <= 0.25) return 'background:rgba(13,110,253,.12);color:#0d6efd;';
            if (ratio <= 0.50) return 'background:rgba(13,110,253,.22);color:#0d6efd;';
            if (ratio <= 0.75) return 'background:rgba(13,110,253,.40);color:#fff;';
            return 'background:rgba(13,110,253,.65);color:#fff;';
        };

        let html = '';
        html += '<div class="text-uppercase fw-bold text-body-secondary text-center" style="font-size:0.68rem;padding:0.4rem 0.2rem;letter-spacing:0.04em;"></div>';
        DAY_ORDER.forEach(d => {
            html += '<div class="text-uppercase fw-bold text-body-secondary text-center" style="font-size:0.68rem;padding:0.4rem 0.2rem;letter-spacing:0.04em;">' + escapeHtml(d) + '</div>';
        });

        TIME_BLOCKS.forEach((blk, blkIdx) => {
            html += '<div class="d-flex align-items-center justify-content-end text-body-secondary fw-bold pe-2" style="font-size:0.66rem;font-variant-numeric:tabular-nums;">' + escapeHtml(blk.label) + '</div>';
            DAY_ORDER.forEach(d => {
                const count = matrix[d][blkIdx];
                html += '<div class="d-flex align-items-center justify-content-center rounded-2 fw-bold border" ' +
                        'style="min-height:42px;font-size:0.72rem;' + cellBg(count) + '">' + (count > 0 ? count : '') + '</div>';
            });
        });

        grid.innerHTML = html;
    }

    /* ============================================================
       Day distribution — vertical list (fits narrow column)
       ============================================================ */
    function renderDayList(rows) {
        const container = document.getElementById('dayList');
        const emptyEl   = document.getElementById('dayEmpty');

        const counts = {};
        DAY_ORDER.forEach(d => counts[d] = 0);
        rows.forEach(r => {
            splitDays(r.day_pattern).forEach(d => {
                if (counts[d] !== undefined) counts[d]++;
            });
        });

        const entries = DAY_ORDER
            .map(d => ({ day: d, label: DAY_LABEL[d], count: counts[d] }))
            .filter(e => e.count > 0);

        document.getElementById('dayBadge').textContent = entries.length + ' day' + (entries.length !== 1 ? 's' : '');

        if (entries.length === 0) {
            container.innerHTML = '';
            emptyEl.classList.remove('d-none');
            return;
        }
        emptyEl.classList.add('d-none');

        const total = entries.reduce((s, e) => s + e.count, 0);
        const maxCount = Math.max(...entries.map(e => e.count), 1);

        container.innerHTML = entries.map(e => {
            const pct = total > 0 ? Math.round((e.count / total) * 100) : 0;
            const barWidth = Math.max(6, (e.count / maxCount) * 100);

            return `
                <div class="p-3 rounded-3 border bg-body-tertiary">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-2 fw-bold flex-shrink-0"
                             style="width:30px;height:30px;font-size:0.7rem;background:rgba(13,110,253,.14);color:#0d6efd;">
                            ${escapeHtml(e.day)}
                        </div>
                        <div class="fw-bold small text-body-emphasis flex-grow-1">${escapeHtml(e.label)}</div>
                        <div class="fw-bold text-primary" style="font-size:1rem;font-variant-numeric:tabular-nums;">${e.count}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px;">
                            <div class="progress-bar bg-primary" role="progressbar"
                                 style="width:${barWidth.toFixed(1)}%;transition:width .35s ease;"
                                 aria-valuenow="${e.count}" aria-valuemin="0" aria-valuemax="${maxCount}"></div>
                        </div>
                        <div class="text-body-secondary fw-semibold" style="font-size:0.68rem;min-width:32px;text-align:right;">${pct}%</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    /* ============================================================
       Faculty load summary
       ============================================================ */
    function renderLoadSummary(rows) {
        const container = document.getElementById('loadSummary');
        const byFaculty = {};

        rows.forEach(r => {
            const id = r.faculty_id;
            if (!byFaculty[id]) {
                byFaculty[id] = { name: r.faculty_name, no: r.faculty_no, dept: r.department_name, units: 0, classes: 0 };
            }
            byFaculty[id].units   += parseFloat(r.units) || 0;
            byFaculty[id].classes += 1;
        });

        const list = Object.values(byFaculty).sort((a, b) => b.units - a.units);
        document.getElementById('loadBadge').textContent = list.length + ' faculty';

        if (list.length === 0) {
            container.innerHTML =
                '<div class="text-center py-4 text-body-secondary">' +
                '<i class="fas fa-user-slash d-block mb-2" style="font-size:1.8rem;opacity:0.4;"></i>' +
                '<span class="small">No faculty load data in scope.</span></div>';
            return;
        }

        container.innerHTML = list.map(f => {
            const initials = String(f.name || '')
                .split(/\s+/).filter(Boolean).slice(0, 2)
                .map(w => w[0] || '').join('').toUpperCase() || '—';
            return `
                <div class="d-flex align-items-center gap-3 p-2 rounded-3 border bg-body-tertiary mb-2">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-2 fw-bold flex-shrink-0 cs-privacy-target"
                         style="width:36px;height:36px;font-size:0.72rem;background:rgba(59,130,246,.14);color:#3b82f6;">${escapeHtml(initials)}</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold small text-body-emphasis text-truncate cs-privacy-target" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
                        <div class="text-body-secondary" style="font-size:0.7rem;">
                            ${escapeHtml(f.no || '—')} · ${f.classes} class${f.classes !== 1 ? 'es' : ''}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary" style="font-size:1rem;font-variant-numeric:tabular-nums;">${f.units.toFixed(1)}</div>
                        <div class="text-uppercase fw-bold text-body-secondary" style="font-size:0.62rem;letter-spacing:.04em;">units</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    /* ============================================================
       Table
       ============================================================ */
    function renderTable(rows) {
        const tbody = document.getElementById('scheduleBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-calendar-xmark d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
                    No class schedules match your filters.
                </td></tr>`;
            document.getElementById('tableSubtitle').textContent = 'No records';
            document.getElementById('pagerInfo').textContent = 'Showing 0 of 0';
            document.getElementById('pager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end   = Math.min(start + PAGE_SIZE, total);
        const page  = rows.slice(start, end);

        tbody.innerHTML = page.map(r => {
            const time = (r.time_start && r.time_end)
                ? fmtTime(r.time_start) + ' – ' + fmtTime(r.time_end)
                : '—';

            return '<tr>' +
                '<td class="cs-privacy-target">' +
                    '<div class="fw-semibold">' + escapeHtml(r.faculty_name) + '</div>' +
                    '<div class="small text-body-secondary">' + escapeHtml(r.faculty_no || '—') + '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="fw-semibold">' + escapeHtml(r.subject_code) + '</div>' +
                    '<div class="small text-body-secondary text-truncate" style="max-width:180px;">' + escapeHtml(r.subject_title) + '</div>' +
                '</td>' +
                '<td class="d-none d-md-table-cell small text-body-secondary">' + escapeHtml(r.section) + '</td>' +
                '<td class="small text-nowrap">' + escapeHtml(r.day_pattern) + '</td>' +
                '<td class="small text-nowrap">' + escapeHtml(time) + '</td>' +
                '<td class="text-center d-none d-md-table-cell">' + (r.units != null ? Number(r.units).toFixed(1) : '—') + '</td>' +
                '<td>' + statusBadge(r.status) + '</td>' +
            '</tr>';
        }).join('');

        document.getElementById('tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' record' + (total !== 1 ? 's' : '');
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
        const wEnd   = Math.min(totalPages, currentPage + 2);
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
                renderTable(filteredData);
            });
        });
    }

    /* ============================================================
       Print
       ============================================================ */
    function renderPrintReport(rows) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const total    = rows.length;
        const approved = rows.filter(r => r.status === 'Approved').length;
        const proposed = rows.filter(r => r.status === 'Proposed').length;
        const totalUnits = rows.reduce((s, r) => s + (parseFloat(r.units) || 0), 0);

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (rows[0]?.department_name || ('#' + filters.dept)));
        if (filters.ay)     filterParts.push('School Year: ' + filters.ay);
        if (filters.sem)    filterParts.push('Semester: ' + filters.sem);
        if (filters.day)    filterParts.push('Day: ' + (DAY_LABEL[filters.day] || filters.day));
        if (filters.status) filterParts.push('Status: ' + filters.status);
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const bodyRows = rows.map(r => {
            const time = (r.time_start && r.time_end)
                ? fmtTime(r.time_start) + ' – ' + fmtTime(r.time_end)
                : '—';
            const term = (r.academic_year || '—') + ' · ' + (r.semester || '—');
            return '<tr>' +
                '<td class="cs-print-blur">' + escapeHtml(r.faculty_name) + '</td>' +
                '<td>' + escapeHtml(r.department_name) + '</td>' +
                '<td>' + escapeHtml(r.subject_code) + '<br><small>' + escapeHtml(r.subject_title) + '</small></td>' +
                '<td>' + escapeHtml(r.section) + '</td>' +
                '<td>' + escapeHtml(r.day_pattern) + '</td>' +
                '<td>' + escapeHtml(time) + '</td>' +
                '<td>' + escapeHtml(r.room_code) + '</td>' +
                '<td style="text-align:center;">' + (r.units != null ? Number(r.units).toFixed(1) : '—') + '</td>' +
                '<td>' + escapeHtml(term) + '</td>' +
                '<td>' + escapeHtml(r.status) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Class Schedules Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi"><div class="print-kpi-label">Schedules</div><div class="print-kpi-value">${total}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Approved</div><div class="print-kpi-value">${approved}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Proposed</div><div class="print-kpi-value">${proposed}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Total Units</div><div class="print-kpi-value">${totalUnits.toFixed(1)}</div></div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th><th>Department</th><th>Subject</th><th>Section</th>
                        <th>Day</th><th>Time</th><th>Room</th><th>Units</th>
                        <th>Term</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="10" style="text-align:center;">No class schedules in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${total} schedule record${total !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Faculty', 'Faculty No', 'Department', 'Subject Code', 'Subject Title', 'Section', 'Day', 'Time Start', 'Time End', 'Room', 'Units', 'School Year', 'Semester', 'Status', 'Enrolled'];
        const lines = rows.map(r => [
            r.faculty_name,
            r.faculty_no || '',
            r.department_name,
            r.subject_code,
            r.subject_title,
            r.section,
            r.day_pattern,
            r.time_start,
            r.time_end,
            r.room_code,
            r.units != null ? Number(r.units).toFixed(1) : '',
            r.academic_year,
            r.semester,
            r.status,
            r.enrolled_students != null ? r.enrolled_students : ''
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'class_schedules_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Populate year filter
       ============================================================ */
    function populateYearFilter() {
        const years = new Set();
        SCHEDULES.forEach(r => {
            if (r.academic_year && r.academic_year !== '—') years.add(r.academic_year);
        });
        const sel = document.getElementById('f_ay');
        if (!sel) return;
        Array.from(years).sort().reverse().forEach(ay => {
            const opt = document.createElement('option');
            opt.value = ay; opt.textContent = ay;
            sel.appendChild(opt);
        });
    }

    /* ============================================================
       Render
       ============================================================ */
    function render() {
        filteredData = applyFilters();
        currentPage = 1;
        renderKPIs(filteredData);
        renderTimetable(filteredData);
        renderDayList(filteredData);
        renderLoadSummary(filteredData);
        renderTable(filteredData);
        renderPrintReport(filteredData);
    }

    function bindFilters() {
        document.querySelectorAll('#csPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', ay: '', sem: '', day: '', status: '' };
        document.querySelectorAll('#csPage [data-filter]').forEach(el => { el.value = ''; });
        const deptSel = document.getElementById('f_dept');
        if (deptSel) deptSel.value = 'all';
        render();
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        populateYearFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredData));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanClassSchedulesPrivacy';
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

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>