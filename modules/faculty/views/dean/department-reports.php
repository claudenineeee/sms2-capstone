<?php
/**
 * SMS 2 - Dean - Department Reports
 * Cross-department snapshot, RBAC-scoped to the dean's departments.
 * Compares clearance completion, document expiries, leave utilization,
 * faculty count and average evaluation score across departments.
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
   LOAD DEPARTMENT SNAPSHOT
   ============================================================ */
$departmentSnapshot = [];
$loadError          = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList   = implode(',', array_map('intval', $deanDepartments));
        $positionIn = implode(',', array_fill(0, count($teachingPositions), '?'));

        $facStmt = $pdo->prepare("
            SELECT f.faculty_id, f.faculty_no, f.first_name, f.last_name,
                   f.department_id, f.position, f.profile_status,
                   d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.faculty f
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND f.profile_status = 'Active'
              AND f.position IN ($positionIn)
            ORDER BY f.last_name ASC, f.first_name ASC
        ");
        $facStmt->execute($teachingPositions);
        $facultyRoster = $facStmt->fetchAll(PDO::FETCH_ASSOC);

        $facultyByDept = [];
        foreach ($facultyRoster as $f) {
            $did = (int) $f['department_id'];
            if (!isset($facultyByDept[$did])) $facultyByDept[$did] = [];
            $facultyByDept[$did][] = $f;
        }

        $allFacultyIds = array_map(fn($f) => (int) $f['faculty_id'], $facultyRoster);

        $evalAvgByFaculty = [];
        if (!empty($allFacultyIds)) {
            $facultyIdList = implode(',', $allFacultyIds);
            $evalStmt = $pdo->query("
                SELECT faculty_id, AVG(composite_score) AS avg_score, COUNT(*) AS eval_rows
                FROM faculty_db.evaluations
                WHERE faculty_id IN ($facultyIdList)
                  AND submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND composite_score > 0
                GROUP BY faculty_id
            ");
            foreach ($evalStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $evalAvgByFaculty[(int) $r['faculty_id']] = [
                    'avg'   => (float) $r['avg_score'],
                    'count' => (int) $r['eval_rows'],
                ];
            }
        }

        $clearanceByFaculty = [];
        if (!empty($allFacultyIds)) {
            $facultyIdList = implode(',', $allFacultyIds);
            $clStmt = $pdo->query("
                SELECT a.faculty_id, a.items_json
                FROM faculty_db.faculty_clearance_archives a
                INNER JOIN (
                    SELECT faculty_id, MAX(archive_id) AS max_archive
                    FROM faculty_db.faculty_clearance_archives
                    WHERE faculty_id IN ($facultyIdList)
                    GROUP BY faculty_id
                ) latest ON latest.faculty_id = a.faculty_id AND latest.max_archive = a.archive_id
            ");
            foreach ($clStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $fid = (int) $r['faculty_id'];
                $items = json_decode((string) $r['items_json'], true);
                $total = 0; $cleared = 0;
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $total++;
                        $status = strtolower($it['status'] ?? '');
                        if ($status === 'cleared' || $status === 'approved') $cleared++;
                    }
                }
                $clearanceByFaculty[$fid] = [
                    'total'   => $total,
                    'cleared' => $cleared,
                    'pct'     => $total > 0 ? ($cleared / $total) * 100 : 0,
                ];
            }
        }

        $docsExpiringByFaculty = [];
        if (!empty($allFacultyIds)) {
            $facultyIdList = implode(',', $allFacultyIds);
            $docStmt = $pdo->query("
                SELECT faculty_id, COUNT(*) AS expiring_count
                FROM faculty_db.documents
                WHERE faculty_id IN ($facultyIdList)
                  AND expiry_date IS NOT NULL
                  AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                GROUP BY faculty_id
            ");
            foreach ($docStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $docsExpiringByFaculty[(int) $r['faculty_id']] = (int) $r['expiring_count'];
            }
        }

        $leaveByFaculty = [];
        if (!empty($allFacultyIds)) {
            $facultyIdList = implode(',', $allFacultyIds);
            $lvStmt = $pdo->query("
                SELECT faculty_id,
                       SUM(sick_leave_used)      AS sick_used,
                       SUM(sick_leave_total)     AS sick_total,
                       SUM(vacation_leave_used)  AS vac_used,
                       SUM(vacation_leave_total) AS vac_total,
                       SUM(maternity_used)       AS mat_used,
                       SUM(maternity_total)      AS mat_total,
                       SUM(paternity_used)       AS pat_used,
                       SUM(paternity_total)      AS pat_total
                FROM faculty_db.leave_balances
                WHERE faculty_id IN ($facultyIdList)
                GROUP BY faculty_id
            ");
            foreach ($lvStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $used  = (int) $r['sick_used'] + (int) $r['vac_used'] + (int) $r['mat_used'] + (int) $r['pat_used'];
                $total = (int) $r['sick_total'] + (int) $r['vac_total'] + (int) $r['mat_total'] + (int) $r['pat_total'];
                $leaveByFaculty[(int) $r['faculty_id']] = [
                    'used'  => $used,
                    'total' => $total,
                    'pct'   => $total > 0 ? ($used / $total) * 100 : 0,
                ];
            }
        }

        foreach ($deanDepartments as $deptId) {
            $deptId   = (int) $deptId;
            $deptName = $deanDepartmentNames[$deptId] ?? ('Dept #' . $deptId);
            $facList  = $facultyByDept[$deptId] ?? [];

            $facCount     = count($facList);
            $evalSum      = 0; $evalN = 0;
            $clearanceSum = 0; $clearanceN = 0;
            $docsExpiring = 0;
            $leaveUsedSum = 0; $leaveTotalSum = 0;

            foreach ($facList as $f) {
                $fid = (int) $f['faculty_id'];

                if (isset($evalAvgByFaculty[$fid])) {
                    $evalSum += $evalAvgByFaculty[$fid]['avg'];
                    $evalN++;
                }
                if (isset($clearanceByFaculty[$fid])) {
                    $clearanceSum += $clearanceByFaculty[$fid]['pct'];
                    $clearanceN++;
                }
                if (isset($docsExpiringByFaculty[$fid])) {
                    $docsExpiring += $docsExpiringByFaculty[$fid];
                }
                if (isset($leaveByFaculty[$fid])) {
                    $leaveUsedSum  += $leaveByFaculty[$fid]['used'];
                    $leaveTotalSum += $leaveByFaculty[$fid]['total'];
                }
            }

            $departmentSnapshot[] = [
                'department_id'   => $deptId,
                'department_name' => $deptName,
                'faculty_count'   => $facCount,
                'avg_eval'        => $evalN > 0 ? round($evalSum / $evalN, 2) : 0,
                'clearance_pct'   => $clearanceN > 0 ? round($clearanceSum / $clearanceN, 1) : 0,
                'docs_expiring'   => $docsExpiring,
                'leave_pct'       => $leaveTotalSum > 0 ? round(($leaveUsedSum / $leaveTotalSum) * 100, 1) : 0,
                'leave_used'      => $leaveUsedSum,
                'leave_total'     => $leaveTotalSum,
            ];
        }

    } catch (Throwable $e) {
        error_log('Department reports load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Department Reports';
$activeModule = 'faculty';
$activePage   = 'department-reports';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Department Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="drPage">

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
        <h4 class="mb-0 fw-bold">Department Reports</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — department-level aggregate report.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-building-columns text-primary"></i>
                <span>Department Reports</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Cross-department snapshot for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
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
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Departments</h6>
                        <h4 class="mb-0 fw-bold text-primary" id="kpiDepts"><?= count($departmentSnapshot) ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-primary" style="font-size:0.7rem;">In your scope</small>
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Faculty</h6>
                        <h4 class="mb-0 fw-bold text-success" id="kpiFaculty">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-success" style="font-size:0.7rem;">Faculty Professors</small>
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
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Docs Expiring</h6>
                        <h4 class="mb-0 fw-bold text-warning" id="kpiDocs">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-warning" style="font-size:0.7rem;">Next 60 days</small>
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
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Avg Clearance</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiClearance">0%</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Across departments</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filter Row ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <?php if (count($deanDepartments) > 1): ?>
                <div class="col-6 col-md-3 col-lg-3">
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

                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_focus">Focus Metric</label>
                    <select class="form-select form-select-sm" id="f_focus" data-filter="focus">
                        <option value="clearance">Clearance Completion</option>
                        <option value="docs">Documents Expiring</option>
                        <option value="leave">Leave Utilization</option>
                        <option value="eval">Average Evaluation</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_sort">Sort By</label>
                    <select class="form-select form-select-sm" id="f_sort" data-filter="sort">
                        <option value="focus_desc">Focus — High to Low</option>
                        <option value="focus_asc">Focus — Low to High</option>
                        <option value="name_asc">Department Name (A–Z)</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">

        <!-- Radar -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Multi-Metric Profile</h5>
                            <p class="text-body-secondary small mb-0">Normalized 0–100 across metrics</p>
                        </div>
                        <span class="badge text-bg-light border" id="radarBadge">0 depts</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1" style="min-height:340px;">
                        <canvas id="radarChart"></canvas>
                        <div class="dr-empty d-none" id="radarEmpty">
                            <i class="fas fa-bullseye"></i>
                            <h6>No department data</h6>
                            <p>Profiles will appear once records exist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking bars -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Department Ranking</h5>
                            <p class="text-body-secondary small mb-0" id="rankingSubtitle">By clearance completion</p>
                        </div>
                        <span class="badge text-bg-light border" id="rankingBadge">0 ranked</span>
                    </div>
                    <div class="position-relative w-100 flex-grow-1 dr-scroll" style="min-height:340px;max-height:340px;">
                        <div id="rankingList" class="d-flex flex-column gap-2"></div>
                        <div class="dr-empty d-none" id="rankingEmpty">
                            <i class="fas fa-ranking-star"></i>
                            <h6>No departments to rank</h6>
                            <p>Rankings will appear once data exists.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress rings -->
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Focus Metric Snapshot</h5>
                            <p class="text-body-secondary small mb-0" id="focusSubtitle">Clearance completion per department</p>
                        </div>
                        <span class="badge text-bg-light border" id="focusBadge">—</span>
                    </div>
                    <div class="dr-scroll d-flex flex-wrap gap-3" id="ringContainer" style="max-height:340px;overflow-y:auto;padding:0.25rem;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Department Details</h5>
                <p class="text-body-secondary small mb-0" id="tableSubtitle">Loading…</p>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center small fw-semibold text-body-secondary">
                <span class="d-inline-flex align-items-center gap-2">
                    <span class="d-inline-block rounded-circle bg-success" style="width:8px;height:8px;"></span>≥ 90%
                </span>
                <span class="d-inline-flex align-items-center gap-2">
                    <span class="d-inline-block rounded-circle bg-warning" style="width:8px;height:8px;"></span>70–89%
                </span>
                <span class="d-inline-flex align-items-center gap-2">
                    <span class="d-inline-block rounded-circle bg-danger" style="width:8px;height:8px;"></span>&lt; 70%
                </span>
            </div>
        </div>

        <div class="dr-scroll" style="max-height:480px;overflow-y:auto;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 880px;">
                    <thead class="sticky-top">
                        <tr>
                            <th class="text-uppercase small fw-bold text-body-secondary">Department</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center">Faculty</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center d-none d-md-table-cell">Avg Eval</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center">Clearance</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center d-none d-sm-table-cell">Docs Expiring</th>
                            <th class="text-uppercase small fw-bold text-body-secondary text-center">Leave Use</th>
                        </tr>
                    </thead>
                    <tbody id="deptTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
            <div class="small text-body-secondary" id="pagerInfo">Showing 0 of 0</div>
            <nav aria-label="Department pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="pager"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="dr-print-only" id="printReport" aria-hidden="true"></div>

<style>
    /* ============================================================
       Minimal CSS — only what Bootstrap can't express
       ============================================================ */

    /* Empty overlays */
    .dr-empty {
        position: absolute; inset: 1rem;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; pointer-events: none;
    }
    .dr-empty i { font-size: 2rem; opacity: .35; margin-bottom: .6rem; color: var(--sms-text-muted); }
    .dr-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: .2rem; font-size: .9rem; }
    .dr-empty p { font-size: .78rem; color: var(--sms-text-muted); margin: 0; max-width: 240px; }

    /* Custom scrollbars — matches evaluation-summary */
    .dr-scroll { scrollbar-width: thin; scrollbar-color: rgba(13,110,253,.3) transparent; }
    .dr-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .dr-scroll::-webkit-scrollbar-track { background: transparent; }
    .dr-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,.3);
        border-radius: 10px;
    }
    .dr-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(13,110,253,.6); }

    /* Ranking bar fill width transition */
    .dr-bar-fill { transition: width .35s ease; }

    /* Progress ring — SVG stroke animation */
    .dr-ring-svg { position: relative; width: 108px; height: 108px; }
    .dr-ring-svg svg { transform: rotate(-90deg); }
    .dr-ring-track { stroke: rgba(148,163,184,.22); }
    .dr-ring-progress { stroke-linecap: round; transition: stroke-dashoffset .5s ease; }

    /* Privacy blur */
    .privacy-mode .dr-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter .15s ease;
    }
    .privacy-mode .dr-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive tweaks Bootstrap doesn't cover */
    @media (max-width: 400px) {
        #drPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #drPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: .7rem .6rem .7rem .9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: .62rem !important; letter-spacing: .03em !important; }
        .stat-card small { font-size: .65rem !important; }
        .dr-ring-svg { width: 88px; height: 88px; }
        .dr-ring-number { font-size: 1.1rem; }
    }

    /* Print */
    .dr-print-only { display: none; }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #drPage { display: none !important; }
        .dr-print-only {
            display: block !important;
            padding: .4in .35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .dr-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .dr-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .dr-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .dr-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .dr-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .dr-print-only .print-kpi { flex: 1; text-align: center; }
        .dr-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: #555;
        }
        .dr-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .dr-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .dr-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: .4px; font-weight: 700;
        }
        .dr-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .dr-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .dr-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .dr-print-only thead { display: table-header-group; }
        .dr-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const SNAPSHOT = <?= json_encode($departmentSnapshot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const PAGE_SIZE = 10;

    let filters = { dept: 'all', focus: 'clearance', sort: 'focus_desc' };
    let filteredData = [];
    let currentPage = 1;
    let radarChart = null;

    const FOCUS_META = {
        clearance: { label: 'Clearance Completion', unit: '%', color: '#10b981' },
        docs:      { label: 'Documents Expiring',   unit: '',  color: '#f59e0b' },
        leave:     { label: 'Leave Utilization',    unit: '%', color: '#0d6efd' },
        eval:      { label: 'Average Evaluation',   unit: '',  color: '#0284c7' }
    };

    /* ============================================================
       Metric helpers
       ============================================================ */
    function focusValue(dept, key) {
        switch (key) {
            case 'clearance': return parseFloat(dept.clearance_pct) || 0;
            case 'docs':      return parseFloat(dept.docs_expiring) || 0;
            case 'leave':     return parseFloat(dept.leave_pct) || 0;
            case 'eval':      return parseFloat(dept.avg_eval) || 0;
            default:          return 0;
        }
    }

    function focusDisplay(dept, key) {
        const v = focusValue(dept, key);
        if (key === 'docs') return v.toString();
        if (key === 'eval') return v.toFixed(2);
        return v.toFixed(1) + '%';
    }

    function focusSeverity(dept, key) {
        const v = focusValue(dept, key);
        if (key === 'docs') return v === 0 ? 'good' : (v < 3 ? 'warn' : 'bad');
        if (key === 'eval') return v >= 4.5 ? 'good' : (v >= 3.5 ? 'warn' : 'bad');
        if (key === 'leave') return v <= 40 ? 'good' : (v <= 70 ? 'warn' : 'bad');
        return v >= 90 ? 'good' : (v >= 70 ? 'warn' : 'bad');
    }

    function severityColor(sev) {
        return { good: '#10b981', warn: '#f59e0b', bad: '#ef4444' }[sev] || '#64748b';
    }

    function severityTextClass(sev) {
        return { good: 'text-success', warn: 'text-warning', bad: 'text-danger' }[sev] || 'text-secondary';
    }

    function severityBg(sev) {
        return { good: 'bg-success', warn: 'bg-warning', bad: 'bg-danger' }[sev] || 'bg-secondary';
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /* ============================================================
       Filter + sort
       ============================================================ */
    function applyFilters() {
        let rows = SNAPSHOT.slice();

        if (filters.dept !== 'all') {
            rows = rows.filter(r => String(r.department_id) === String(filters.dept));
        }

        const key = filters.focus;
        if (filters.sort === 'focus_desc') {
            rows.sort((a, b) => focusValue(b, key) - focusValue(a, key));
        } else if (filters.sort === 'focus_asc') {
            rows.sort((a, b) => focusValue(a, key) - focusValue(b, key));
        } else if (filters.sort === 'name_asc') {
            rows.sort((a, b) => a.department_name.localeCompare(b.department_name));
        }
        return rows;
    }

    /* ============================================================
       KPIs
       ============================================================ */
    function renderKPIs(rows) {
        const totalFaculty = rows.reduce((s, r) => s + (r.faculty_count || 0), 0);
        const totalDocs    = rows.reduce((s, r) => s + (r.docs_expiring || 0), 0);
        const clearRows    = rows.filter(r => r.clearance_pct > 0);
        const avgClear     = clearRows.length > 0
            ? clearRows.reduce((s, r) => s + r.clearance_pct, 0) / clearRows.length
            : 0;

        document.getElementById('kpiDepts').textContent     = rows.length;
        document.getElementById('kpiFaculty').textContent   = totalFaculty;
        document.getElementById('kpiDocs').textContent      = totalDocs;
        document.getElementById('kpiClearance').textContent = avgClear.toFixed(1) + '%';
    }

    /* ============================================================
       Radar
       ============================================================ */
    function chartColors() {
        const cs = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.getAttribute('data-theme') === 'dark';
        return {
            text:       cs.getPropertyValue('--sms-chart-text').trim() || (isDark ? '#94a3b8' : '#64748b'),
            textStrong: isDark ? '#e2e8f0' : '#0f172a',
            grid:       cs.getPropertyValue('--sms-chart-grid').trim() || (isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,33,88,0.06)'),
            surface:    isDark ? '#121c34' : '#ffffff'
        };
    }

    function normalize(value, key) {
        switch (key) {
            case 'clearance': return Math.min(100, Math.max(0, value));
            case 'docs':      return Math.min(100, Math.max(0, 100 - value * 15));
            case 'leave':     return Math.min(100, Math.max(0, 100 - value));
            case 'eval':      return Math.min(100, Math.max(0, (value / 5) * 100));
            default:          return 0;
        }
    }

    const RADAR_METRICS = [
        { key: 'clearance', label: 'Clearance' },
        { key: 'eval',      label: 'Evaluation' },
        { key: 'leave',     label: 'Leave Health' },
        { key: 'docs',      label: 'Docs Health' }
    ];

    const RADAR_PALETTE = ['#0d6efd', '#10b981', '#f59e0b', '#a855f7', '#ef4444', '#0284c7', '#ec4899', '#14b8a6'];

    function renderRadar(rows) {
        document.getElementById('radarBadge').textContent = rows.length + ' dept' + (rows.length !== 1 ? 's' : '');
        if (typeof Chart === 'undefined') return;

        const emptyEl = document.getElementById('radarEmpty');
        const canvas  = document.getElementById('radarChart');
        if (rows.length === 0) {
            emptyEl.classList.remove('d-none');
            canvas.style.visibility = 'hidden';
            if (radarChart) { radarChart.destroy(); radarChart = null; }
            return;
        }
        emptyEl.classList.add('d-none');
        canvas.style.visibility = 'visible';

        const c = chartColors();
        const labels = RADAR_METRICS.map(m => m.label);

        const datasets = rows.slice(0, 6).map((dept, i) => {
            const data = RADAR_METRICS.map(m => normalize(focusValue(dept, m.key), m.key));
            const color = RADAR_PALETTE[i % RADAR_PALETTE.length];
            return {
                label: dept.department_name,
                data: data,
                borderColor: color,
                backgroundColor: color + '22',
                pointBackgroundColor: color,
                pointBorderColor: c.surface,
                pointRadius: 4, pointHoverRadius: 6,
                borderWidth: 2
            };
        });

        if (radarChart) radarChart.destroy();
        radarChart = new Chart(canvas.getContext('2d'), {
            type: 'radar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.textStrong,
                            boxWidth: 10, boxHeight: 10,
                            usePointStyle: true, pointStyle: 'circle',
                            padding: 12, font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const metric = RADAR_METRICS[ctx.dataIndex];
                                const dept   = rows[ctx.datasetIndex];
                                return dept.department_name + ' · ' + metric.label + ': ' + focusDisplay(dept, metric.key);
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true, max: 100,
                        ticks: { stepSize: 25, color: c.text, backdropColor: 'transparent', font: { size: 9 } },
                        grid:        { color: c.grid },
                        angleLines:  { color: c.grid },
                        pointLabels: { color: c.textStrong, font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    /* ============================================================
       Ranking bars — Bootstrap utilities
       ============================================================ */
    function renderRanking(rows) {
        const container = document.getElementById('rankingList');
        const emptyEl   = document.getElementById('rankingEmpty');
        const meta      = FOCUS_META[filters.focus];

        document.getElementById('rankingSubtitle').textContent = 'By ' + meta.label.toLowerCase();
        document.getElementById('rankingBadge').textContent    = rows.length + ' ranked';

        if (rows.length === 0) {
            container.innerHTML = '';
            emptyEl.classList.remove('d-none');
            return;
        }
        emptyEl.classList.add('d-none');

        const invert = filters.focus === 'docs' || filters.focus === 'leave';
        const values = rows.map(r => focusValue(r, filters.focus));
        const maxV   = Math.max(...values, 1);

        container.innerHTML = rows.map((dept, idx) => {
            const v   = focusValue(dept, filters.focus);
            const pct = invert
                ? Math.max(2, 100 - (v / maxV) * 100)
                : Math.max(2, (v / maxV) * 100);

            const sev   = focusSeverity(dept, filters.focus);
            const bg    = severityBg(sev);
            const text  = severityTextClass(sev);
            const medal = idx === 0 ? 'bg-warning text-white'
                        : idx === 1 ? 'bg-secondary text-white'
                        : idx === 2 ? 'text-white' : 'bg-primary-subtle text-primary';
            const medalStyle = idx === 2 ? 'background:linear-gradient(135deg,#fbbf79,#ea7c3d);' : '';

            return `
                <div class="d-flex align-items-center gap-3 p-2 rounded-3 border bg-body-tertiary">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-2 fw-bold flex-shrink-0 ${medal}"
                         style="min-width:28px;height:28px;font-size:.75rem;${medalStyle}">${idx + 1}</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold small text-body-emphasis text-truncate mb-1" title="${escapeHtml(dept.department_name)}">${escapeHtml(dept.department_name)}</div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar dr-bar-fill ${bg}" role="progressbar"
                                 style="width:${pct.toFixed(1)}%"
                                 aria-valuenow="${pct.toFixed(0)}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="fw-bold small flex-shrink-0 text-end ${text}" style="min-width:56px;">${focusDisplay(dept, filters.focus)}</div>
                </div>
            `;
        }).join('');
    }

    /* ============================================================
       Progress rings
       ============================================================ */
    function renderRings(rows) {
        const container = document.getElementById('ringContainer');
        const meta      = FOCUS_META[filters.focus];

        document.getElementById('focusSubtitle').textContent = meta.label + ' per department';
        document.getElementById('focusBadge').textContent    = rows.length + ' dept' + (rows.length !== 1 ? 's' : '');

        if (rows.length === 0) {
            container.innerHTML =
                '<div class="text-center py-4 text-body-secondary w-100">' +
                '<i class="fas fa-circle-notch d-block mb-2" style="font-size:1.8rem;opacity:.4;"></i>' +
                '<span class="small">No department data in scope.</span></div>';
            return;
        }

        const invert = filters.focus === 'docs' || filters.focus === 'leave';
        const values = rows.map(r => focusValue(r, filters.focus));
        const maxV   = Math.max(...values, 1);

        const R    = 46;
        const CIRC = 2 * Math.PI * R;

        container.innerHTML = rows.map(dept => {
            const v = focusValue(dept, filters.focus);
            const pct = invert
                ? Math.max(0, Math.min(100, 100 - (v / maxV) * 100))
                : Math.max(0, Math.min(100, (v / maxV) * 100));

            const sev = focusSeverity(dept, filters.focus);
            const color = severityColor(sev);
            const offset = CIRC * (1 - pct / 100);

            return `
                <div class="d-flex flex-column align-items-center gap-2 p-3 rounded-3 border bg-body-tertiary"
                     style="flex:0 0 auto;min-width:180px;max-width:220px;">
                    <div class="dr-ring-svg">
                        <svg width="108" height="108" viewBox="0 0 108 108">
                            <circle class="dr-ring-track" cx="54" cy="54" r="${R}" fill="none" stroke-width="8"/>
                            <circle class="dr-ring-progress" cx="54" cy="54" r="${R}" fill="none"
                                    stroke="${color}" stroke-width="8"
                                    stroke-dasharray="${CIRC.toFixed(2)}"
                                    stroke-dashoffset="${offset.toFixed(2)}"/>
                        </svg>
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <div class="dr-ring-number fw-bold" style="font-size:1.35rem;font-variant-numeric:tabular-nums;">${focusDisplay(dept, filters.focus)}</div>
                            <div class="text-uppercase fw-bold text-body-secondary" style="font-size:.68rem;letter-spacing:.06em;">${meta.unit || 'value'}</div>
                        </div>
                    </div>
                    <div class="fw-bold small text-body-emphasis text-truncate w-100 text-center dr-privacy-target"
                         title="${escapeHtml(dept.department_name)}">${escapeHtml(dept.department_name)}</div>
                    <div class="text-body-secondary" style="font-size:.68rem;">${dept.faculty_count} facult${dept.faculty_count === 1 ? 'y' : 'ies'}</div>
                </div>
            `;
        }).join('');
    }

    /* ============================================================
       Table
       ============================================================ */
    function pctTextClass(v, invert = false) {
        if (invert) {
            if (v <= 40) return 'text-success';
            if (v <= 70) return 'text-warning';
            return 'text-danger';
        }
        if (v >= 90) return 'text-success';
        if (v >= 70) return 'text-warning';
        return 'text-danger';
    }

    function renderTable(rows) {
        const tbody = document.getElementById('deptTableBody');
        const total = rows.length;

        if (total === 0) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="text-center py-5 text-body-secondary">
                    <i class="fas fa-building d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
                    No departments match your filters.
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
            const evalDisplay = r.avg_eval > 0 ? r.avg_eval.toFixed(2) : '—';
            const clearCls = pctTextClass(r.clearance_pct);
            const docsCls  = r.docs_expiring === 0 ? 'text-success' : (r.docs_expiring < 3 ? 'text-warning' : 'text-danger');
            const leaveCls = pctTextClass(r.leave_pct, true);

            return '<tr>' +
                '<td><span class="fw-bold text-body-emphasis dr-privacy-target">' + escapeHtml(r.department_name) + '</span></td>' +
                '<td class="text-center fw-bold">' + r.faculty_count + '</td>' +
                '<td class="text-center d-none d-md-table-cell">' + evalDisplay + '</td>' +
                '<td class="text-center"><span class="fw-bold ' + clearCls + '">' + r.clearance_pct.toFixed(1) + '%</span></td>' +
                '<td class="text-center d-none d-sm-table-cell"><span class="fw-bold ' + docsCls + '">' + r.docs_expiring + '</span></td>' +
                '<td class="text-center"><span class="fw-bold ' + leaveCls + '">' + r.leave_pct.toFixed(1) + '%</span></td>' +
            '</tr>';
        }).join('');

        document.getElementById('tableSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' department' + (total !== 1 ? 's' : '');
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

        const totalFaculty = rows.reduce((s, r) => s + (r.faculty_count || 0), 0);
        const totalDocs    = rows.reduce((s, r) => s + (r.docs_expiring || 0), 0);
        const avgClearance = rows.length > 0
            ? rows.reduce((s, r) => s + r.clearance_pct, 0) / rows.length
            : 0;

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (rows[0]?.department_name || ('#' + filters.dept)));
        filterParts.push('Focus: ' + FOCUS_META[filters.focus].label);
        const filterLine = filterParts.join(' · ');

        const bodyRows = rows.map(r =>
            '<tr>' +
                '<td>' + escapeHtml(r.department_name) + '</td>' +
                '<td style="text-align:center;">' + r.faculty_count + '</td>' +
                '<td style="text-align:center;">' + (r.avg_eval > 0 ? r.avg_eval.toFixed(2) : '—') + '</td>' +
                '<td style="text-align:center;">' + r.clearance_pct.toFixed(1) + '%</td>' +
                '<td style="text-align:center;">' + r.docs_expiring + '</td>' +
                '<td style="text-align:center;">' + r.leave_pct.toFixed(1) + '%</td>' +
            '</tr>'
        ).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Department Reports</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi"><div class="print-kpi-label">Departments</div><div class="print-kpi-value">${rows.length}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Faculty</div><div class="print-kpi-value">${totalFaculty}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Docs Expiring</div><div class="print-kpi-value">${totalDocs}</div></div>
                <div class="print-kpi"><div class="print-kpi-label">Avg Clearance</div><div class="print-kpi-value">${avgClearance.toFixed(1)}%</div></div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th style="text-align:center;">Faculty</th>
                        <th style="text-align:center;">Avg Eval</th>
                        <th style="text-align:center;">Clearance</th>
                        <th style="text-align:center;">Docs Expiring</th>
                        <th style="text-align:center;">Leave Use</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="6" style="text-align:center;">No departments in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${rows.length} department${rows.length !== 1 ? 's' : ''} · Confidential — aggregate report
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['Department', 'Faculty Count', 'Avg Evaluation', 'Clearance %', 'Docs Expiring', 'Leave Utilization %', 'Leave Used', 'Leave Total'];
        const lines = rows.map(r => [
            r.department_name,
            r.faculty_count,
            r.avg_eval > 0 ? r.avg_eval.toFixed(2) : '',
            r.clearance_pct.toFixed(1),
            r.docs_expiring,
            r.leave_pct.toFixed(1),
            r.leave_used,
            r.leave_total
        ]);

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'department_reports_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Render pipeline
       ============================================================ */
    function render() {
        filteredData = applyFilters();
        currentPage = 1;
        renderKPIs(filteredData);
        renderRadar(filteredData);
        renderRanking(filteredData);
        renderRings(filteredData);
        renderTable(filteredData);
        renderPrintReport(filteredData);
    }

    function bindFilters() {
        document.querySelectorAll('#drPage [data-filter]').forEach(el => {
            el.addEventListener('change', () => {
                filters[el.dataset.filter] = el.value;
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', focus: 'clearance', sort: 'focus_desc' };
        document.getElementById('f_focus').value = 'clearance';
        document.getElementById('f_sort').value  = 'focus_desc';
        const deptSel = document.getElementById('f_dept');
        if (deptSel) deptSel.value = 'all';
        render();
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        bindFilters();
        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredData));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanDepartmentReportsPrivacy';
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

        const obs = new MutationObserver(() => { renderRadar(filteredData); });
        obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        obs.observe(document.body,            { attributes: true, attributeFilter: ['data-theme'] });

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