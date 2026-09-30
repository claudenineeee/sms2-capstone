<?php
/**
 * Reports (Secretary View)
 * // FIXED: Academic Term selector redesigned as an inline pill control.
 * // FIXED: printSection() rewritten to produce a properly-styled print
 * view — injects Bootstrap + page CSS, converts <canvas> charts to
 * <img> so they actually render, strips interactive controls, adds a
 * print header with title/scope/timestamp.
 * // FIXED: Leave Request Report uses facultyDb() + positional (?) params
 * to avoid SQLSTATE[HY093]. Stat cards removed — only "Latest Requests".
 * // ADDED: CSV export for Leave Request Report (?export=leave_csv).
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';  // facultyDb() helper

requireAuth();

try {
    $pdo = getFacultyDatabaseConnection();
} catch (Exception $e) {
    die('<div style="padding:20px;font-family:sans-serif;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;margin:20px;border-radius:4px;">'
        . '<h3>Database Connection Error</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
}

/* ============================================================
   Separate PDO for leave-request queries — mirrors the exact
   connection used by leave-request-screening.php.
   ============================================================ */
$leavePdo = null;
try {
    if (function_exists('facultyDb')) {
        $leavePdo = facultyDb();
    }
} catch (Throwable $e) {
    $leavePdo = null;
}
if (!$leavePdo) { $leavePdo = $pdo; }

// 1. Secretary's Department Scope
$deptScope = '';
$currentUserId = getCurrentUserId();
if ($currentUserId) {
    try {
        $stmt = $pdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $currentUserId]);
        $deptScope = trim((string) ($stmt->fetchColumn() ?: ''));
    } catch (PDOException $e) { $deptScope = ''; }
}

// 2. Academic Term
$academicTerms = [];
try {
    $academicTerms = $pdo->query("
        SELECT DISTINCT academic_year, semester 
        FROM teaching_load_history 
        WHERE academic_year IS NOT NULL AND semester IS NOT NULL
        ORDER BY academic_year DESC, semester DESC
    ")->fetchAll();
} catch (PDOException $e) { $academicTerms = []; }

$selectedTerm = $_GET['term'] ?? '';
if (empty($selectedTerm) && !empty($academicTerms)) {
    $defaultTerm = null;
    foreach ($academicTerms as $term) {
        if (stripos(trim($term['semester']), '1st') !== false) { $defaultTerm = $term; break; }
    }
    if ($defaultTerm === null) $defaultTerm = $academicTerms[0];
    $selectedTerm = $defaultTerm['academic_year'] . '-' . $defaultTerm['semester'];
} elseif (empty($selectedTerm)) {
    $selectedTerm = '2025-2026-1';
}
$termParts = explode('-', $selectedTerm);
$selectedAY = (count($termParts) >= 2) ? $termParts[0] . '-' . $termParts[1] : '2025-2026';
$selectedSem = $termParts[2] ?? '1';

// 3. SUBJECT LOAD REPORT
$nonTeachingPositions = ['dean','program head','program chair','department head','department chair','coordinator','secretary','registrar','admin','administrator'];
function getMaxUnitsForEmploymentStatus($s) {
    $n = strtolower(trim($s ?? ''));
    if (in_array($n, ['part-time','part time','parttime'])) return 15;
    return 24;
}
$facultyQuerySql = "
    SELECT fp.id, fp.faculty_id AS profile_faculty_no, fp.first_name, fp.last_name,
           fp.designated_department, fp.position, fp.email, fp.employment_status,
           f.faculty_id AS real_faculty_id
    FROM faculty_profiles fp
    LEFT JOIN faculty f ON f.faculty_id = (
        SELECT f2.faculty_id FROM faculty f2
        WHERE (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email)
           OR f2.faculty_no = fp.faculty_id
        ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email) DESC
        LIMIT 1
    )
";
$facultyMembers = [];
try {
    if (!empty($deptScope)) {
        $stmt = $pdo->prepare($facultyQuerySql . " WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:dept) AND LOWER(TRIM(fp.request_status)) = 'approved' ORDER BY fp.last_name ASC");
        $stmt->execute(['dept' => $deptScope]);
        $facultyMembers = $stmt->fetchAll();
    } else {
        $facultyMembers = $pdo->query($facultyQuerySql . " WHERE LOWER(TRIM(fp.request_status)) = 'approved' ORDER BY fp.last_name ASC")->fetchAll();
    }
} catch (PDOException $e) { $facultyMembers = []; }

$facultyMembers = array_values(array_filter($facultyMembers, function ($fac) use ($nonTeachingPositions) {
    $pos = strtolower(trim($fac['position'] ?? ''));
    if ($pos === '') return true;
    foreach ($nonTeachingPositions as $ex) if (strpos($pos, $ex) !== false) return false;
    return true;
}));

$subjectLoadRows = [];
$slTotalFaculty = count($facultyMembers);
$slFullyLoadedCount = 0;
$slTotalUnassignedUnits = 0.0;
$slDeptTotals = [];

foreach ($facultyMembers as $fac) {
    $realFacultyId = $fac['real_faculty_id'] !== null ? (int) $fac['real_faculty_id'] : null;
    $maxUnitsLimit = getMaxUnitsForEmploymentStatus($fac['employment_status'] ?? '');
    $assignedSubjects = [];
    if ($realFacultyId !== null) {
        try {
            $stmtLoad = $pdo->prepare("SELECT units FROM teaching_load_history WHERE faculty_id = :fac_id AND academic_year = :ay AND semester = :sem");
            $stmtLoad->execute(['fac_id' => $realFacultyId, 'ay' => $selectedAY, 'sem' => $selectedSem]);
            $assignedSubjects = $stmtLoad->fetchAll();
        } catch (PDOException $e) {}
    }
    if (empty($assignedSubjects)) {
        try {
            $stmtLoadAlt = $pdo->prepare("SELECT units FROM teaching_load_history WHERE (faculty_id = :f1 OR faculty_no = :f2) AND academic_year = :ay AND semester = :sem");
            $stmtLoadAlt->execute(['f1' => $fac['id'], 'f2' => $fac['profile_faculty_no'] ?? '', 'ay' => $selectedAY, 'sem' => $selectedSem]);
            $assignedSubjects = $stmtLoadAlt->fetchAll();
        } catch (PDOException $e) {}
    }
    $totalAssignedUnits = 0.0;
    foreach ($assignedSubjects as $s) $totalAssignedUnits += floatval($s['units'] ?? 0);
    $isFullyLoaded = ($totalAssignedUnits >= $maxUnitsLimit);
    if ($isFullyLoaded) $slFullyLoadedCount++;
    else $slTotalUnassignedUnits += max(0, $maxUnitsLimit - $totalAssignedUnits);
    $deptKey = $fac['designated_department'] ?? 'N/A';
    $slDeptTotals[$deptKey] = ($slDeptTotals[$deptKey] ?? 0) + $totalAssignedUnits;
    $subjectLoadRows[] = [
        'name' => 'Prof. ' . $fac['first_name'] . ' ' . $fac['last_name'],
        'department' => $deptKey,
        'employment' => $fac['employment_status'] ?? 'N/A',
        'total_units' => $totalAssignedUnits,
        'max_units' => $maxUnitsLimit,
        'remaining' => max(0, $maxUnitsLimit - $totalAssignedUnits),
        'is_full' => $isFullyLoaded,
    ];
}
$slFullyLoadedPct = $slTotalFaculty > 0 ? round(($slFullyLoadedCount / $slTotalFaculty) * 100) : 0;
$slUnderLoadedCount = $slTotalFaculty - $slFullyLoadedCount;
$slEmploymentOptions = array_values(array_unique(array_filter(array_map(fn($r) => $r['employment'], $subjectLoadRows))));
sort($slEmploymentOptions);
$slDeptHasData = array_sum($slDeptTotals) > 0;

// 4. ASSIGNMENT MONITORING REPORT
$assignmentRows = [];
$assignmentTableAvailable = false;
$assignmentStatusMessage = 'Pending schedule integration (REST API) — see assignment-monitoring.php';
$amTotalAssignments = 0; $amTotalUnits = 0; $amConflictCount = 0;
$amStatusCounts = []; $amFacultyUnits = [];

try {
    if ($pdo->query("SHOW TABLES LIKE 'faculty_class_assignments'")->fetchColumn() !== false) {
        $assignmentTableAvailable = true;
        $amSql = "SELECT fca.id, fca.faculty_id, fca.class_id, fca.units, fca.room, fca.time, fca.days, fca.status,
                         fp.first_name, fp.last_name, fp.designated_department
                  FROM faculty_class_assignments fca
                  LEFT JOIN faculty_profiles fp ON fp.id = fca.faculty_id";
        if (!empty($deptScope)) {
            $st = $pdo->prepare($amSql . " WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:dept) ORDER BY fca.id DESC");
            $st->execute(['dept' => $deptScope]);
        } else {
            $st = $pdo->query($amSql . " ORDER BY fca.id DESC");
        }
        $assignmentRows = $st->fetchAll();
        $amTotalAssignments = count($assignmentRows);
        foreach ($assignmentRows as $row) {
            $amTotalUnits += (int) ($row['units'] ?? 0);
            $statusKey = trim((string) ($row['status'] ?? 'pending')) ?: 'pending';
            $amStatusCounts[$statusKey] = ($amStatusCounts[$statusKey] ?? 0) + 1;
            $facName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('Faculty ID ' . (int) ($row['faculty_id'] ?? 0));
            $amFacultyUnits[$facName] = ($amFacultyUnits[$facName] ?? 0) + (int) ($row['units'] ?? 0);
        }
        $idx = [];
        foreach ($assignmentRows as $row) {
            $fid = (int) ($row['faculty_id'] ?? 0);
            $key = ($row['time'] ?? '') . '|' . ($row['days'] ?? '');
            if ($fid > 0 && trim($key, '|') !== '') $idx[$fid][$key] = ($idx[$fid][$key] ?? 0) + 1;
        }
        foreach ($idx as $slots) foreach ($slots as $c) if ($c > 1) $amConflictCount++;
        $assignmentStatusMessage = $amTotalAssignments > 0
            ? 'Connected — showing live data from faculty_class_assignments'
            : 'faculty_class_assignments table exists but has no rows yet';
    }
} catch (PDOException $e) {
    $assignmentTableAvailable = false;
    $assignmentStatusMessage = 'Database error while reading assignment data: ' . $e->getMessage();
}
arsort($amFacultyUnits);
$amFacultyUnitsTop = array_slice($amFacultyUnits, 0, 10, true);
$amStatusOptions = array_keys($amStatusCounts);
sort($amStatusOptions);

/* ============================================================
   5. LEAVE REQUEST REPORT DATA
   ============================================================ */
$lrTotalCount = 0;
$lrTypeCounts = [];
$lrTopFaculty = [];
$lrRows = [];
$lrDebugError = '';

try {
    $restrictedDeptCode = null;
    if (function_exists('getRestrictedDepartmentId')) {
        $restrictedDeptId = getRestrictedDepartmentId();
        if ($restrictedDeptId !== null && $restrictedDeptId > 0) {
            $stDept = $leavePdo->prepare("SELECT code FROM departments WHERE department_id = ? LIMIT 1");
            $stDept->execute([$restrictedDeptId]);
            $restrictedDeptCode = $stDept->fetchColumn() ?: null;
        }
    }
    if ($restrictedDeptCode === null && !empty($deptScope)) {
        $restrictedDeptCode = $deptScope;
    }

    $fromJoins = "
        FROM leave_requests lr
        LEFT JOIN faculty f            ON f.faculty_id = lr.faculty_id
        LEFT JOIN faculty_profiles fp  ON fp.email = f.email
        LEFT JOIN faculty_profiles fp2 ON fp2.id = lr.faculty_id
    ";

    $whereClause = '';
    $params = [];
    if ($restrictedDeptCode !== null) {
        $whereClause = " WHERE (fp.designated_department = ? OR fp2.designated_department = ?)";
        $params = [$restrictedDeptCode, $restrictedDeptCode];
    }

    $stTotal = $leavePdo->prepare("SELECT COUNT(*) $fromJoins $whereClause");
    $stTotal->execute($params);
    $lrTotalCount = (int) $stTotal->fetchColumn();

    $typeWhere = $whereClause === ''
        ? " WHERE (lr.leave_type IS NOT NULL AND lr.leave_type <> '')"
        : $whereClause . " AND (lr.leave_type IS NOT NULL AND lr.leave_type <> '')";
    $stTypes = $leavePdo->prepare("
        SELECT lr.leave_type, COUNT(*) AS cnt
        $fromJoins
        $typeWhere
        GROUP BY lr.leave_type
        ORDER BY cnt DESC
    ");
    $stTypes->execute($params);
    while ($row = $stTypes->fetch(PDO::FETCH_ASSOC)) {
        $lrTypeCounts[(string)$row['leave_type']] = (int)$row['cnt'];
    }

    $stList = $leavePdo->prepare("
        SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date,
               lr.screening_status, lr.created_at,
               DATEDIFF(lr.end_date, lr.start_date) + 1 AS days,
               COALESCE(
                   NULLIF(CONCAT_WS(' ', fp.first_name, fp.last_name), ' '),
                   NULLIF(CONCAT_WS(' ', fp2.first_name, fp2.last_name), ' ')
               ) AS faculty_name
        $fromJoins
        $whereClause
        ORDER BY CASE WHEN lr.screening_status = 'Pending' THEN 0 ELSE 1 END,
                 lr.created_at DESC
        LIMIT 10
    ");
    $stList->execute($params);
    $lrRows = $stList->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $counts = [];
    foreach ($lrRows as $r) {
        $fn = trim((string)($r['faculty_name'] ?? ''));
        if ($fn === '') continue;
        $counts[$fn] = ($counts[$fn] ?? 0) + 1;
    }
    arsort($counts);
    $lrTopFaculty = array_slice($counts, 0, 5, true);

} catch (Throwable $e) {
    $lrDebugError = $e->getMessage();
    error_log('[reports.php][leave-report] ' . $e->getMessage());
}

/* ============================================================
   CSV EXPORT — Leave Request Report
   ============================================================ */
if (isset($_GET['export']) && $_GET['export'] === 'leave_csv') {
    try {
        $exportPdo = $leavePdo;

        $restrictedDeptCode = null;
        if (function_exists('getRestrictedDepartmentId')) {
            $restrictedDeptId = getRestrictedDepartmentId();
            if ($restrictedDeptId !== null && $restrictedDeptId > 0) {
                $stDept = $exportPdo->prepare("SELECT code FROM departments WHERE department_id = ? LIMIT 1");
                $stDept->execute([$restrictedDeptId]);
                $restrictedDeptCode = $stDept->fetchColumn() ?: null;
            }
        }
        if ($restrictedDeptCode === null && !empty($deptScope)) {
            $restrictedDeptCode = $deptScope;
        }

        $fromJoins = "
            FROM leave_requests lr
            LEFT JOIN faculty f            ON f.faculty_id = lr.faculty_id
            LEFT JOIN faculty_profiles fp  ON fp.email = f.email
            LEFT JOIN faculty_profiles fp2 ON fp2.id = lr.faculty_id
        ";
        $whereClause = '';
        $params = [];
        if ($restrictedDeptCode !== null) {
            $whereClause = " WHERE (fp.designated_department = ? OR fp2.designated_department = ?)";
            $params = [$restrictedDeptCode, $restrictedDeptCode];
        }

        $stExport = $exportPdo->prepare("
            SELECT
                lr.id,
                COALESCE(
                    NULLIF(CONCAT_WS(' ', fp.first_name, fp.last_name), ' '),
                    NULLIF(CONCAT_WS(' ', fp2.first_name, fp2.last_name), ' ')
                ) AS faculty_name,
                COALESCE(fp.designated_department, fp2.designated_department) AS department,
                lr.leave_type,
                lr.start_date,
                lr.end_date,
                DATEDIFF(lr.end_date, lr.start_date) + 1 AS days,
                lr.screening_status,
                lr.created_at
            $fromJoins
            $whereClause
            ORDER BY CASE WHEN lr.screening_status = 'Pending' THEN 0 ELSE 1 END,
                     lr.created_at DESC
            LIMIT 200
        ");
        $stExport->execute($params);
        $exportRows = $stExport->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $filename = 'leave-requests-' . date('Y-m-d_His') . '.csv';

        while (ob_get_level() > 0) { ob_end_clean(); }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Request ID', 'Faculty', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Screening Status', 'Filed At']);
        foreach ($exportRows as $r) {
            fputcsv($out, [
                (int) ($r['id'] ?? 0),
                (string) ($r['faculty_name'] ?? ''),
                (string) ($r['department'] ?? ''),
                (string) ($r['leave_type'] ?? ''),
                (string) ($r['start_date'] ?? ''),
                (string) ($r['end_date'] ?? ''),
                (int) ($r['days'] ?? 0),
                (string) ($r['screening_status'] ?? ''),
                (string) ($r['created_at'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    } catch (Throwable $e) {
        error_log('[reports.php][leave-csv] ' . $e->getMessage());
        $lrDebugError = 'CSV export failed: ' . $e->getMessage();
    }
}

$pageTitle    = 'Reports';
$activeModule = 'faculty';
$activePage   = 'reports';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Reports', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    /* Badge styles — verbatim from leave-request-screening.php */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 650;
        border-radius: 6px;
        line-height: 1;
        letter-spacing: 0.01em;
        transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    }
    .badge-pending  { background-color: rgba(245, 158, 11, 0.15) !important; color: #d97706 !important; border: 1px solid rgba(245, 158, 11, 0.3); }
    .badge-screened { background-color: rgba(16, 185, 129, 0.15) !important; color: #059669 !important; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-returned { background-color: rgba(239, 68, 68, 0.15) !important;  color: #dc2626 !important; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-none     { background-color: rgba(148, 163, 184, 0.15) !important; color: #64748b !important; border: 1px solid rgba(148, 163, 184, 0.25); }
    [data-bs-theme="dark"] .badge-pending,  [data-theme="dark"] .badge-pending,  body.dark-mode .badge-pending  { background-color: rgba(245, 158, 11, 0.22) !important; color: #fbbf24 !important; border-color: rgba(251, 191, 36, 0.35); }
    [data-bs-theme="dark"] .badge-screened, [data-theme="dark"] .badge-screened, body.dark-mode .badge-screened { background-color: rgba(16, 185, 129, 0.22) !important; color: #34d399 !important; border-color: rgba(52, 211, 153, 0.35); }
    [data-bs-theme="dark"] .badge-returned, [data-theme="dark"] .badge-returned, body.dark-mode .badge-returned { background-color: rgba(239, 68, 68, 0.22) !important;  color: #f87171 !important; border-color: rgba(248, 113, 113, 0.35); }
    [data-bs-theme="dark"] .badge-none,     [data-theme="dark"] .badge-none,     body.dark-mode .badge-none     { background-color: rgba(148, 163, 184, 0.20) !important; color: #94a3b8 !important; border-color: rgba(148, 163, 184, 0.3); }

    .report-chart-wrap { position: relative; height: 200px; }
    .stat-card .card-body { padding: 0.85rem 1rem; }
    .stat-card .stat-icon { font-size: 1.1rem; }
    .stat-card h6 { font-size: 0.7rem; letter-spacing: 0.03em; }
    .stat-card h4 { font-size: 1.15rem; }
    .stat-card small { font-size: 0.7rem; }
    .compact-table thead th { font-size: 0.7rem; padding: 0.6rem 0.5rem; }
    .compact-table tbody td { font-size: 0.8rem; padding: 0.6rem 0.5rem; }
    .report-section-header { font-size: 0.85rem; padding: 0; margin-bottom: 0.75rem; }
    .compact-filter .form-control,
    .compact-filter .form-select,
    .compact-filter .input-group-text { font-size: 0.8rem; padding: 0.35rem 0.6rem; }
    .compact-filter .form-label { font-size: 0.7rem; }

/* ============================================================
   Academic Term pill — theme-aware (uses explicit selectors
   instead of --bs-* fallbacks, so it works with any theme
   mechanism the layout uses: data-bs-theme, data-theme, or
   .dark-mode class).
   ============================================================ */

/* ---------- LIGHT (default) ---------- */
.term-pill {
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.35rem 0.5rem 0.35rem 0.85rem;
    gap: 0.5rem !important;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}
.term-pill-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    letter-spacing: 0.02em;
    display: inline-flex;
    align-items: center;
}
.term-pill-label i { color: #0d6efd; }
.term-pill-select {
    appearance: none;
    -webkit-appearance: none;
    background-color: #ffffff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.6rem center;
    border: 1px solid #cbd5e1;
    border-radius: 0.35rem;
    padding: 0.35rem 1.9rem 0.35rem 0.65rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    min-width: 210px;
    line-height: 1.2;
}
.term-pill-select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
}

/* ---------- DARK — matches leave-request-screening palette ---------- */
[data-bs-theme="dark"] .term-pill,
[data-theme="dark"] .term-pill,
body.dark-mode .term-pill {
    background-color: #131c2e;
    border-color: #1f2a44;
}
[data-bs-theme="dark"] .term-pill-label,
[data-theme="dark"] .term-pill-label,
body.dark-mode .term-pill-label {
    color: #94a3b8;
}
[data-bs-theme="dark"] .term-pill-select,
[data-theme="dark"] .term-pill-select,
body.dark-mode .term-pill-select {
    background-color: #0d1526;
    color: #e2e8f0;
    border-color: #1f2a44;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
}
[data-bs-theme="dark"] .term-pill-select option,
[data-theme="dark"] .term-pill-select option,
body.dark-mode .term-pill-select option {
    background-color: #0d1526;
    color: #e2e8f0;
}

@media (max-width: 575.98px) {
    .term-pill {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.25rem !important;
        width: 100%;
    }
    .term-pill-select { width: 100%; min-width: 0; }
}
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1 text-body">
            <i class="fas fa-file-alt text-primary me-2"></i>
            Reports
        </h1>
        <p class="text-muted mb-0 small">
            Department Scope: <strong class="text-primary"><?= htmlspecialchars(!empty($deptScope) ? $deptScope : 'All Departments') ?></strong>
        </p>
    </div>

    <!-- Inline academic term selector -->
    <div class="term-pill d-flex align-items-center gap-2">
        <label for="academicTermSelect" class="term-pill-label mb-0">
            <i class="fas fa-calendar-alt me-1"></i>Academic Term:
        </label>
        <select class="term-pill-select" id="academicTermSelect" onchange="changeAcademicTerm(this.value)">
            <?php if (!empty($academicTerms)): ?>
                <?php foreach ($academicTerms as $term): ?>
                    <?php $termVal = $term['academic_year'] . '-' . $term['semester']; ?>
                    <option value="<?= htmlspecialchars($termVal) ?>" <?= $selectedTerm === $termVal ? 'selected' : '' ?>>
                        A.Y. <?= htmlspecialchars($term['academic_year']) ?> | <?= htmlspecialchars($term['semester']) ?> Semester
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="2025-2026-1" selected>A.Y. 2025–2026 | 1st Semester</option>
            <?php endif; ?>
        </select>
    </div>
</div>

<!-- ============================ SUBJECT LOAD REPORT ============================ -->
<div class="mb-4" id="subject-load-report">
    <div class="d-flex justify-content-between align-items-center report-section-header">
        <h6 class="mb-0 fw-bold text-body"><i class="fas fa-chalkboard-teacher text-primary me-2"></i>Subject Load Report</h6>
        <button class="btn btn-sm btn-outline-primary" onclick="printSection('subject-load-report')">
            <i class="fas fa-file-pdf me-1"></i>Export / Print
        </button>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card primary border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 text-uppercase fw-bold">Total Active Faculty</h6>
                        <h4 class="mb-0 fw-bold text-primary"><?= $slTotalFaculty ?></h4>
                        <small class="text-muted fw-semibold">In current scope</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card success border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 text-uppercase fw-bold">Fully Loaded</h6>
                        <h4 class="mb-0 fw-bold text-success"><?= $slFullyLoadedCount ?> <span class="fs-6 text-muted">/ <?= $slTotalFaculty ?></span></h4>
                        <small class="text-muted fw-semibold"><?= $slFullyLoadedPct ?>% of faculty</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <section class="card stat-card border-0 border-start border-4 shadow-sm position-relative h-100" style="border-left-color: #f59e0b !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 text-uppercase fw-bold">Unassigned Units</h6>
                        <h4 class="mb-0 fw-bold text-warning"><?= $slTotalUnassignedUnits ?></h4>
                        <small class="text-muted fw-semibold">Still to distribute</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2 small text-uppercase fw-bold">Load Status Distribution</h6>
                    <?php if ($slTotalFaculty > 0): ?>
                        <div class="report-chart-wrap"><canvas id="slStatusChart"></canvas></div>
                    <?php else: ?>
                        <p class="text-muted small text-center py-4 mb-0">No data to chart yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2 small text-uppercase fw-bold">Assigned Units by Department</h6>
                    <?php if ($slDeptHasData): ?>
                        <div class="report-chart-wrap"><canvas id="slDeptChart"></canvas></div>
                    <?php else: ?>
                        <p class="text-muted small text-center py-4 mb-0">No units assigned in this term yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3 compact-filter">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" id="slSearchInput" class="form-control border-start-0 ps-0 bg-light" placeholder="Faculty name or department" onkeyup="filterSubjectLoadTable()">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Employment Status</label>
                    <select id="slEmploymentFilter" class="form-select bg-light" onchange="filterSubjectLoadTable()">
                        <option value="">All</option>
                        <?php foreach ($slEmploymentOptions as $opt): ?>
                            <option value="<?= htmlspecialchars(strtolower($opt)) ?>"><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Load Status</label>
                    <select id="slStatusFilter" class="form-select bg-light" onchange="filterSubjectLoadTable()">
                        <option value="">All</option>
                        <option value="full">Full Load</option>
                        <option value="under">Under-loaded</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 compact-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Faculty</th>
                        <th>Department</th>
                        <th>Employment</th>
                        <th class="text-center">Assigned</th>
                        <th class="text-center">Max</th>
                        <th class="text-center">Remaining</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="subjectLoadTableBody">
                    <?php if (!empty($subjectLoadRows)): ?>
                        <?php foreach ($subjectLoadRows as $row): ?>
                            <?php $searchStr = strtolower($row['name'] . ' ' . $row['department']); ?>
                            <tr data-search="<?= htmlspecialchars($searchStr) ?>"
                                data-employment="<?= htmlspecialchars(strtolower($row['employment'])) ?>"
                                data-status="<?= $row['is_full'] ? 'full' : 'under' ?>">
                                <td class="fw-bold ps-3"><?= htmlspecialchars($row['name']) ?></td>
                                <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($row['department']) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($row['employment']) ?></td>
                                <td class="text-center fw-medium"><?= $row['total_units'] ?></td>
                                <td class="text-center small text-muted"><?= $row['max_units'] ?></td>
                                <td class="text-center small text-muted"><?= $row['remaining'] ?></td>
                                <td class="text-center">
                                    <span class="status-badge <?= $row['is_full'] ? 'badge-screened' : 'badge-pending' ?>">
                                        <?= $row['is_full'] ? 'Full Load' : 'Under-loaded' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No faculty found for this department scope and term.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================ LEAVE REQUEST REPORT ============================ -->
<div class="mb-4" id="leave-request-report">
    <div class="d-flex justify-content-between align-items-center report-section-header">
        <h6 class="mb-0 fw-bold text-body"><i class="fas fa-file-signature text-primary me-2"></i>Leave Request Report</h6>
        <a href="<?= BASE_URL ?>/modules/faculty/views/secretary/leave-request-screening.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-external-link-alt me-1"></i>Open Screening
        </a>
    </div>

    <?php if ($lrDebugError !== ''): ?>
        <div class="alert alert-danger small py-2 mb-3">
            <strong>Leave request query error:</strong> <?= htmlspecialchars($lrDebugError) ?>
        </div>
    <?php endif; ?>

    <?php if ($lrTotalCount > 0): ?>
    <div class="row g-2 mb-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2 small text-uppercase fw-bold">Leave Type Breakdown</h6>
                    <div class="report-chart-wrap"><canvas id="lrTypeChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2 small text-uppercase fw-bold">Top Faculty by Requests</h6>
                    <?php if (!empty($lrTopFaculty)): ?>
                        <div class="report-chart-wrap"><canvas id="lrFacultyChart"></canvas></div>
                    <?php else: ?>
                        <p class="text-muted small text-center py-4 mb-0">No faculty data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Latest Requests</h6>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Total on file: <strong><?= (int) $lrTotalCount ?></strong></span>
            <a href="?export=leave_csv" class="btn btn-sm btn-outline-success" title="Download all leave requests as CSV">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 compact-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Faculty</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th class="text-center">Days</th>
                    <th>Screening Status</th>
                    <th>Filed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lrRows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No leave requests in your department scope yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lrRows as $r): ?>
                        <?php
                            $fn = trim((string)($r['faculty_name'] ?? ''));
                            if ($fn === '') $fn = 'Unknown Faculty';
                            $s = $r['screening_status'] ?? 'Pending';
                            $badgeCls = match ($s) {
                                'Screened' => 'badge-screened',
                                'Returned' => 'badge-returned',
                                default    => 'badge-pending',
                            };
                            $days = (int) ($r['days'] ?? 0);
                        ?>
                        <tr>
                            <td class="fw-bold ps-3"><?= htmlspecialchars($fn) ?></td>
                            <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($r['leave_type'] ?? '—') ?></span></td>
                            <td class="small text-muted">
                                <?= htmlspecialchars($r['start_date'] ?? '') ?> &rarr; <?= htmlspecialchars($r['end_date'] ?? '') ?>
                            </td>
                            <td class="text-center fw-medium"><?= $days ?></td>
                            <td><span class="status-badge <?= $badgeCls ?>"><?= htmlspecialchars($s) ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================ ASSIGNMENT MONITORING REPORT ============================ -->
<div class="mb-4" id="assignment-monitoring-report">
    <div class="d-flex justify-content-between align-items-center report-section-header">
        <h6 class="mb-0 fw-bold text-body"><i class="fas fa-clipboard-list text-primary me-2"></i>Assignment Monitoring Report</h6>
        <button class="btn btn-sm btn-outline-primary" onclick="printSection('assignment-monitoring-report')" <?= !$assignmentTableAvailable ? 'disabled' : '' ?>>
            <i class="fas fa-file-pdf me-1"></i>Export / Print
        </button>
    </div>

    <?php if (!$assignmentTableAvailable || $amTotalAssignments === 0): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <i class="fas fa-plug text-muted fs-4 mb-2 d-block opacity-50"></i>
                <p class="text-muted small mb-0"><?= htmlspecialchars($assignmentStatusMessage) ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-2 mb-3">
            <div class="col-12 col-sm-6 col-xl-4">
                <section class="card stat-card primary border shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3 text-primary"><i class="fas fa-list-check"></i></div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase fw-bold">Total Assignments</h6>
                            <h4 class="mb-0 fw-bold text-primary"><?= $amTotalAssignments ?></h4>
                            <small class="text-muted fw-semibold">Class assignments on file</small>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <section class="card stat-card primary border shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3 text-primary"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase fw-bold">Total Units Assigned</h6>
                            <h4 class="mb-0 fw-bold text-primary"><?= $amTotalUnits ?></h4>
                            <small class="text-muted fw-semibold">Across all assignments</small>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <section class="card stat-card border-0 border-start border-4 shadow-sm h-100" style="border-left-color: <?= $amConflictCount > 0 ? '#dc3545' : '#198754' ?> !important;">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3 <?= $amConflictCount > 0 ? 'text-danger' : 'text-success' ?>"><i class="fas fa-triangle-exclamation"></i></div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase fw-bold">Conflicts Detected</h6>
                            <h4 class="mb-0 fw-bold <?= $amConflictCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $amConflictCount ?></h4>
                            <small class="text-muted fw-semibold"><?= $amConflictCount > 0 ? 'Needs review' : 'None found' ?></small>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 small text-uppercase fw-bold">Assignment Status</h6>
                        <div class="report-chart-wrap"><canvas id="amStatusChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 small text-uppercase fw-bold">Top Faculty by Assigned Units</h6>
                        <div class="report-chart-wrap"><canvas id="amFacultyChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 compact-filter">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="amSearchInput" class="form-control border-start-0 ps-0 bg-light" placeholder="Faculty, room, or department" onkeyup="filterAssignmentTable()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Status</label>
                        <select id="amStatusFilter" class="form-select bg-light" onchange="filterAssignmentTable()">
                            <option value="">All</option>
                            <?php foreach ($amStatusOptions as $opt): ?>
                                <option value="<?= htmlspecialchars(strtolower($opt)) ?>"><?= htmlspecialchars(ucfirst($opt)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 compact-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Faculty</th>
                        <th>Department</th>
                        <th>Class ID</th>
                        <th class="text-center">Units</th>
                        <th>Room</th>
                        <th>Time</th>
                        <th>Days</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="assignmentTableBody">
                    <?php foreach ($assignmentRows as $row): ?>
                        <?php
                            $facName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('Faculty ID ' . (int) ($row['faculty_id'] ?? 0));
                            $rowStatus = trim((string) ($row['status'] ?? 'pending')) ?: 'pending';
                            $searchStr = strtolower($facName . ' ' . ($row['room'] ?? '') . ' ' . ($row['designated_department'] ?? ''));
                            $badgeCls = 'badge-pending';
                            if (strtolower($rowStatus) === 'approved') $badgeCls = 'badge-screened';
                            if (strtolower($rowStatus) === 'rejected') $badgeCls = 'badge-returned';
                        ?>
                        <tr data-search="<?= htmlspecialchars($searchStr) ?>" data-status="<?= htmlspecialchars(strtolower($rowStatus)) ?>">
                            <td class="fw-bold ps-3"><?= htmlspecialchars($facName) ?></td>
                            <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($row['designated_department'] ?? 'N/A') ?></span></td>
                            <td class="small text-muted font-monospace"><?= (int) ($row['class_id'] ?? 0) ?></td>
                            <td class="text-center fw-medium"><?= (int) ($row['units'] ?? 0) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['room'] ?? '') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['time'] ?? '') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['days'] ?? '') ?></td>
                            <td><span class="status-badge <?= $badgeCls ?>"><?= htmlspecialchars(ucfirst($rowStatus)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function changeAcademicTerm(v) {
        const u = new URL(window.location.href);
        u.searchParams.set('term', v);
        window.location.href = u.toString();
    }

    /**
     * printSection()
     * Opens a new window with a fully-styled printable version of the
     * given section:
     *   - Clones the section so the original DOM isn't modified.
     *   - Converts every <canvas> chart to a static <img> using its
     *     current PNG data (so charts actually show up in print).
     *   - Strips form controls and interactive buttons.
     *   - Injects Bootstrap + the page's own <style> so all classes
     *     render correctly.
     *   - Adds a proper print header (title + scope + timestamp).
     */
    function printSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (!section) return;

        // --- Clone and prepare the section ---
        const clone = section.cloneNode(true);

        // 1. Convert every canvas (chart) to an image
        const originalCanvases = section.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');
        clonedCanvases.forEach((canvas, idx) => {
            const src = originalCanvases[idx];
            if (!src) return;
            try {
                const dataUrl = src.toDataURL('image/png');
                const img = document.createElement('img');
                img.src = dataUrl;
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                img.style.display = 'block';
                img.style.margin = '0 auto';
                canvas.parentNode.replaceChild(img, canvas);
            } catch (e) {
                const ph = document.createElement('div');
                ph.textContent = '[Chart]';
                ph.style.textAlign = 'center';
                ph.style.color = '#999';
                ph.style.padding = '2rem';
                canvas.parentNode.replaceChild(ph, canvas);
            }
        });

        // 2. Remove interactive-only elements
        clone.querySelectorAll('.btn, .compact-filter, select, input[type="text"]').forEach(el => el.remove());

        // 3. Remove inline "corner link" icons on stat cards
        clone.querySelectorAll('.position-absolute.top-0.end-0').forEach(el => el.remove());

        // --- Collect CSS from the host page ---
        const stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
            .map(l => `<link rel="stylesheet" href="${l.href}">`)
            .join('\n');

        const inlineStyles = Array.from(document.querySelectorAll('style'))
            .map(s => s.outerHTML)
            .join('\n');

        // --- Compose the print HTML ---
        const docTitle = 'Reports — ' + (
            document.querySelector('.page-header small strong')?.textContent?.trim() || 'All Departments'
        );
        const printedAt = new Date().toLocaleString();

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>${docTitle}</title>
                ${stylesheets}
                ${inlineStyles}
                <style>
                    @page { size: A4; margin: 12mm; }
                    body {
                        background: #ffffff !important;
                        color: #111111 !important;
                        padding: 0 !important;
                        font-size: 11px;
                    }
                    .report-chart-wrap { height: 220px !important; }

                    .print-header {
                        border-bottom: 2px solid #0d6efd;
                        padding-bottom: 0.5rem;
                        margin-bottom: 1rem;
                    }
                    .print-header h1 {
                        font-size: 16px;
                        font-weight: 700;
                        color: #0d6efd;
                        margin: 0 0 0.15rem 0;
                    }
                    .print-header .meta {
                        font-size: 10px;
                        color: #555;
                        margin: 0;
                    }

                    .report-section-header { margin-bottom: 0.5rem !important; }
                    .stat-card { page-break-inside: avoid; }
                    .card { box-shadow: none !important; border: 1px solid #ddd !important; }

                    [data-bs-theme="dark"] body,
                    [data-theme="dark"] body,
                    body.dark-mode { background: #ffffff !important; color: #111111 !important; }
                    .card, .stat-card { background: #ffffff !important; color: #111111 !important; }
                    .text-muted { color: #666 !important; }
                    .table thead th { background: #f0f0f0 !important; color: #111 !important; }

                    a.btn, button.btn { display: none !important; }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>${escapeHtml(docTitle)}</h1>
                    <p class="meta">
                        Department Scope: ${escapeHtml(document.querySelector('.page-header small strong')?.textContent?.trim() || 'All Departments')}
                        &nbsp;·&nbsp; Printed: ${escapeHtml(printedAt)}
                    </p>
                </div>
                ${clone.outerHTML}
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();

        const triggerPrint = () => {
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
            }, 300);
        };

        if (printWindow.document.readyState === 'complete') {
            triggerPrint();
        } else {
            printWindow.addEventListener('load', triggerPrint);
            setTimeout(triggerPrint, 1500);
        }
    }

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function filterSubjectLoadTable() {
        const search = document.getElementById('slSearchInput').value.toLowerCase().trim();
        const employment = document.getElementById('slEmploymentFilter').value;
        const status = document.getElementById('slStatusFilter').value;
        document.querySelectorAll('#subjectLoadTableBody tr[data-search]').forEach(r => {
            const ok = (!search || r.dataset.search.includes(search))
                    && (!employment || r.dataset.employment === employment)
                    && (!status || r.dataset.status === status);
            r.style.display = ok ? '' : 'none';
        });
    }

    function filterAssignmentTable() {
        const s = document.getElementById('amSearchInput');
        const st = document.getElementById('amStatusFilter');
        if (!s || !st) return;
        const search = s.value.toLowerCase().trim();
        const status = st.value;
        document.querySelectorAll('#assignmentTableBody tr[data-search]').forEach(r => {
            const ok = (!search || r.dataset.search.includes(search))
                    && (!status || r.dataset.status === status);
            r.style.display = ok ? '' : 'none';
        });
    }

    // ============================================================
    // CHARTS
    // ============================================================
    const charts = {};

    function getBootstrapToken(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }
    function chartColors() {
        return {
            bodyBg:      getBootstrapToken('--bs-body-bg')      || '#ffffff',
            bodyColor:   getBootstrapToken('--bs-body-color')   || '#212529',
            borderColor: getBootstrapToken('--bs-border-color') || 'rgba(0,0,0,0.1)'
        };
    }

    function renderCharts() {
        if (typeof Chart === 'undefined') return;
        const c = chartColors();
        Object.values(charts).forEach(ch => { try { ch.destroy(); } catch (e) {} });

        const slS = document.getElementById('slStatusChart');
        if (slS) charts.slS = new Chart(slS, {
            type: 'doughnut',
            data: {
                labels: ['Fully Loaded', 'Under-loaded'],
                datasets: [{ data: [<?= (int) $slFullyLoadedCount ?>, <?= (int) $slUnderLoadedCount ?>],
                    backgroundColor: ['#198754', '#ffc107'], borderWidth: 2, borderColor: c.bodyBg }]
            },
            options: { responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, color: c.bodyColor, font: { size: 11 } } } },
                cutout: '65%' }
        });

        const slD = document.getElementById('slDeptChart');
        if (slD) {
            const dl = <?= json_encode(array_keys($slDeptTotals), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const dv = <?= json_encode(array_values($slDeptTotals)) ?>;
            const mv = Math.max(...dv, 0);
            const yMax = mv > 0 ? Math.ceil(mv * 1.15) : 10;
            charts.slD = new Chart(slD, {
                type: 'bar',
                data: { labels: dl, datasets: [{ label: 'Assigned Units', data: dv, backgroundColor: '#0d6efd', borderRadius: 6, maxBarThickness: 50 }] },
                options: { responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, suggestedMax: yMax, grid: { color: c.borderColor },
                             ticks: { color: c.bodyColor, font: { size: 10 }, stepSize: Math.max(1, Math.ceil(yMax/5)), callback: v => Number.isInteger(v) ? v : '' } },
                        x: { grid: { display: false }, ticks: { color: c.bodyColor, font: { size: 10 } } }
                    } }
            });
        }

        const lrT = document.getElementById('lrTypeChart');
        if (lrT) {
            const tl = <?= json_encode(array_keys($lrTypeCounts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const tv = <?= json_encode(array_values($lrTypeCounts)) ?>;
            charts.lrT = new Chart(lrT, {
                type: 'doughnut',
                data: { labels: tl, datasets: [{ data: tv,
                    backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#20c997','#fd7e14','#0dcaf0'],
                    borderWidth: 2, borderColor: c.bodyBg }] },
                options: { responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, color: c.bodyColor, font: { size: 10 } } } },
                    cutout: '62%' }
            });
        }

        const lrF = document.getElementById('lrFacultyChart');
        if (lrF) {
            const fl = <?= json_encode(array_keys($lrTopFaculty ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const fv = <?= json_encode(array_values($lrTopFaculty ?? [])) ?>;
            const mv = Math.max(...fv, 0);
            const xMax = mv > 0 ? Math.ceil(mv * 1.15) : 5;
            charts.lrF = new Chart(lrF, {
                type: 'bar',
                data: { labels: fl, datasets: [{ label: 'Requests', data: fv, backgroundColor: '#0d6efd', borderRadius: 6, maxBarThickness: 26 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, suggestedMax: xMax, grid: { color: c.borderColor },
                             ticks: { color: c.bodyColor, font: { size: 10 }, stepSize: Math.max(1, Math.ceil(xMax/5)), callback: v => Number.isInteger(v) ? v : '' } },
                        y: { grid: { display: false }, ticks: { color: c.bodyColor, font: { size: 10 } } }
                    } }
            });
        }

        const amS = document.getElementById('amStatusChart');
        if (amS) charts.amS = new Chart(amS, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map('ucfirst', array_keys($amStatusCounts)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                datasets: [{ data: <?= json_encode(array_values($amStatusCounts)) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997'],
                    borderWidth: 2, borderColor: c.bodyBg }]
            },
            options: { responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, color: c.bodyColor, font: { size: 11 } } } },
                cutout: '65%' }
        });

        const amF = document.getElementById('amFacultyChart');
        if (amF) {
            const fl = <?= json_encode(array_keys($amFacultyUnitsTop), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const fv = <?= json_encode(array_values($amFacultyUnitsTop)) ?>;
            const mv = Math.max(...fv, 0);
            const xMax = mv > 0 ? Math.ceil(mv * 1.15) : 10;
            charts.amF = new Chart(amF, {
                type: 'bar',
                data: { labels: fl, datasets: [{ label: 'Units Assigned', data: fv, backgroundColor: '#0dcaf0', borderRadius: 6, maxBarThickness: 24 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, suggestedMax: xMax, grid: { color: c.borderColor },
                             ticks: { color: c.bodyColor, font: { size: 10 }, stepSize: Math.max(1, Math.ceil(xMax/5)), callback: v => Number.isInteger(v) ? v : '' } },
                        y: { grid: { display: false }, ticks: { color: c.bodyColor, font: { size: 10 } } }
                    } }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', renderCharts);
    new MutationObserver(renderCharts).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme', 'data-theme', 'class']
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>