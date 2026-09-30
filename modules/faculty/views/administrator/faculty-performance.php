<?php
/**
 * SMS 2 - Evaluation Summary
 * Module: Faculty Management
 *
 * Uses ONLY faculty_db.
 * Shows ALL Faculty Professors across ALL departments (no department filter).
 *
 * A faculty is considered a "Faculty Professor" if:
 *   - faculty.position = 'Faculty Professor'
 *   - AND faculty_profiles (matched by faculty_no) does NOT say they are
 *     something else (Department Head, Dean, Faculty Secretary, etc.)
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

require_once __DIR__ . '/../../controllers/faculty-data.php';

$pdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$pdo) {
    http_response_code(503);
    die('Faculty database is currently unavailable. Please try again later.');
}

/* ------------------------------------------------------------------
 | 1. Fetch ALL Faculty Professors from faculty_db
 |
 | Two-step approach:
 |   a) Query `faculty` for everyone with position = 'Faculty Professor'
 |   b) Exclude anyone whose `faculty_profiles` (by faculty_no) says
 |      they are Department Head / Dean / Secretary / etc.
 |
 | This matches exactly what the Faculty Clearance page shows, so both
 | modules display the same list.
 * ------------------------------------------------------------------ */
$facultyMembers = [];

try {
    $stmt = $pdo->query("
        SELECT f.faculty_id,
               f.faculty_no,
               f.first_name,
               f.middle_name,
               f.last_name,
               f.email,
               f.position,
               f.profile_status,
               f.employment_status,
               f.department_id,
               d.code AS dept_code,
               d.name AS dept_name
        FROM faculty f
        LEFT JOIN departments d ON d.department_id = f.department_id
        WHERE f.position = 'Faculty Professor'
          AND (f.profile_status IS NULL OR f.profile_status <> 'Inactive')
          AND NOT EXISTS (
              SELECT 1
              FROM faculty_profiles fp
              WHERE fp.faculty_id = f.faculty_no
                AND fp.position IS NOT NULL
                AND TRIM(fp.position) <> ''
                AND TRIM(fp.position) <> 'Faculty Professor'
          )
        ORDER BY d.code ASC, f.last_name ASC, f.first_name ASC
    ");
    $facultyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('[EvalSummary][faculty-list] ' . $e->getMessage());
    $facultyMembers = [];
}

/* ------------------------------------------------------------------
 | Helper: Rating Label Generator
 * ------------------------------------------------------------------ */
function getRatingLabel($score) {
    $num = (float) $score;
    if ($num >= 4.50) return '5 - Outstanding';
    if ($num >= 3.50) return '4 - Very Satisfactory';
    if ($num >= 2.50) return '3 - Satisfactory';
    if ($num >= 1.50) return '2 - Average';
    if ($num > 0)     return '1 - Needs Improvement';
    return 'No Rating';
}

/* ------------------------------------------------------------------
 | 2. Weighted evaluation ratings per faculty
 * ------------------------------------------------------------------ */
$performanceDB = [];

$stmtStud = $pdo->prepare("SELECT COALESCE(AVG(composite_score), 0) AS avg_score, COUNT(evaluation_id) AS total_evals FROM evaluations WHERE faculty_id = :fac_id AND source_type = 'Student'");
$stmtPeer = $pdo->prepare("SELECT COALESCE(AVG(composite_score), 0) AS avg_score, COUNT(evaluation_id) AS total_evals FROM evaluations WHERE faculty_id = :fac_id AND source_type = 'Peer'");
$stmtHead = $pdo->prepare("SELECT COALESCE(AVG(composite_score), 0) AS avg_score, COUNT(evaluation_id) AS total_evals FROM evaluations WHERE faculty_id = :fac_id AND source_type = 'DeptHead'");

foreach ($facultyMembers as $fac) {
    $facId = (int) $fac['faculty_id'];

    $stmtStud->execute(['fac_id' => $facId]);
    $s = $stmtStud->fetch();
    $studentAvg   = (float)($s['avg_score'] ?? 0);
    $studentCount = (int)($s['total_evals'] ?? 0);

    $stmtPeer->execute(['fac_id' => $facId]);
    $p = $stmtPeer->fetch();
    $peerAvg   = (float)($p['avg_score'] ?? 0);
    $peerCount = (int)($p['total_evals'] ?? 0);

    $stmtHead->execute(['fac_id' => $facId]);
    $h = $stmtHead->fetch();
    $headAvg   = (float)($h['avg_score'] ?? 0);
    $headCount = (int)($h['total_evals'] ?? 0);

    $weightedSources = [
        ['avg' => $studentAvg, 'count' => $studentCount, 'weight' => 0.50],
        ['avg' => $peerAvg,    'count' => $peerCount,    'weight' => 0.30],
        ['avg' => $headAvg,    'count' => $headCount,    'weight' => 0.20],
    ];
    $weightedSum = 0;
    $weightTotal = 0;
    foreach ($weightedSources as $src) {
        if ($src['count'] > 0) {
            $weightedSum += $src['avg'] * $src['weight'];
            $weightTotal += $src['weight'];
        }
    }
    $composite = $weightTotal > 0 ? $weightedSum / $weightTotal : 0;

    $mid      = trim((string) ($fac['middle_name'] ?? ''));
    $fullName = 'Prof. ' . trim(
        $fac['first_name']
        . ($mid !== '' ? ' ' . $mid : '')
        . ' ' . $fac['last_name']
    );

    $deptLabel = $fac['dept_code'] ?: ($fac['dept_name'] ?: 'N/A');

    $performanceDB[$facId] = [
        'name'            => $fullName,
        'department'      => $deptLabel,
        'compositeScore'  => $composite > 0 ? number_format($composite, 2) : 'N/A',
        'rawComposite'    => $composite,
        'compositeRating' => getRatingLabel($composite),
        'headScore'       => $headCount > 0 ? number_format($headAvg, 2) : '—',
        'peerScore'       => $peerCount > 0 ? number_format($peerAvg, 2) : '—',
        'studentScore'    => $studentCount > 0 ? number_format($studentAvg, 2) : '—',
        'totalEvals'      => $studentCount + $peerCount + $headCount,
    ];
}

/* ------------------------------------------------------------------
 | 3. Overview stats (across all departments)
 * ------------------------------------------------------------------ */
$totalFacultyCount = count($performanceDB);

$deptOverallScores = [];
$topPerformers     = [];
$fullyEvaluated    = 0;

foreach ($performanceDB as $facId => $data) {
    if ((int) $data['totalEvals'] > 0) {
        $deptOverallScores[$facId] = (float) $data['rawComposite'];

        if ($data['headScore'] !== '—' && $data['peerScore'] !== '—' && $data['studentScore'] !== '—') {
            $fullyEvaluated++;
        }
        if ((float) $data['rawComposite'] >= 4.5) {
            $topPerformers[$facId] = $data;
        }
    }
}

$evaluatedFacultyCount = count($deptOverallScores);
$deptOverallAverage    = !empty($deptOverallScores)
    ? array_sum($deptOverallScores) / count($deptOverallScores)
    : 0;

$topPerformersCount = count($topPerformers);

$pageTitle    = 'Evaluation Summary';
$activeModule = 'faculty';
$activePage   = 'faculty-performance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Evaluation Summary', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<style>
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header mb-3">
    <h1 class="h4 h3-md mb-1"><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Evaluation Summary</h1>
    <small class="text-muted small d-block">
        Showing <strong>Faculty Professors</strong> across <strong>All Departments</strong>
    </small>
    <small class="text-muted small d-block">Overall = 50% Student + 30% Peer + 20% Department Head</small>
</div>

<!-- KPI CARDS -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #f59e0b; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #f59e0b;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Top Performers</h6>
                    <h4 class="mb-0 fw-bold" style="color: #f59e0b;"><?= (int) $topPerformersCount ?></h4>
                    <small class="fw-semibold" style="color: #f59e0b; font-size: 0.75rem;">
                        Rating &ge; 4.5
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Top Performers">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <div class="col-12 col-sm-4">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #10b981; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #10b981;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Dept Average</h6>
                    <h4 class="mb-0 fw-bold" style="color: #10b981;"><?= number_format($deptOverallAverage, 1) ?></h4>
                    <small class="fw-semibold" style="color: #10b981; font-size: 0.75rem;">
                        Overall Score
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Analytics">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <div class="col-12 col-sm-4">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Fully Evaluated</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= (int) $fullyEvaluated ?> / <?= (int) $evaluatedFacultyCount ?></h4>
                    <small class="fw-semibold" style="color: #0d6efd; font-size: 0.75rem;">
                        All 3 sources complete
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Evaluations">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<!-- TOP PERFORMERS PANEL -->
<div class="card border shadow-sm mb-3">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-trophy text-warning me-2"></i>Top Performers
            <span class="text-muted fw-normal small">(Rating ≥ 4.5)</span>
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($topPerformers)): ?>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($topPerformers as $tp): ?>
                    <div class="border rounded-3 bg-white p-3 text-center" style="min-width: 110px;">
                        <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 42px; height: 42px;">
                            <i class="fas fa-user text-secondary"></i>
                        </div>
                        <div class="fw-bold small text-truncate" title="<?= htmlspecialchars($tp['name']) ?>">
                            <?= htmlspecialchars($tp['name']) ?>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($tp['compositeScore']) ?> —</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="border rounded-3 bg-white p-3 text-center" style="min-width: 110px;">
                <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 42px; height: 42px;">
                    <i class="fas fa-user text-secondary"></i>
                </div>
                <div class="fw-bold small">Pending Data</div>
                <div class="small text-muted">0.0 —</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PERFORMANCE OVERVIEW -->
<div class="card border shadow-sm mb-4">
    <div class="card-header border-bottom border-secondary border-opacity-25 py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="mb-0 fw-bold">Performance Overview</h6>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Per page:</span>
            <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage()">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
            </select>
        </div>
    </div>

    <div class="card-body border-bottom border-secondary border-opacity-25 bg-light py-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label text-muted small text-uppercase fw-bold mb-1">Faculty Name</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="facultySearchInput" class="form-control border-start-0 ps-0 bg-white" placeholder="Search faculty..." onkeyup="filterTable()">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label text-muted small text-uppercase fw-bold mb-1">Evaluation Period</label>
                <select id="periodFilter" class="form-select form-select-sm bg-white" onchange="filterTable()">
                    <option value="all">All Periods</option>
                    <option value="2026-2027">AY 2026-2027</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label text-muted small text-uppercase fw-bold mb-1">Rating Range</label>
                <select id="ratingFilter" class="form-select form-select-sm bg-white" onchange="filterTable()">
                    <option value="all">All</option>
                    <option value="outstanding">Outstanding (4.50+)</option>
                    <option value="satisfactory">Satisfactory (2.50-4.49)</option>
                    <option value="needs-improvement">Needs Improvement (&lt;2.50)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary w-100 bg-white" onclick="resetFilters()" title="Reset Filters">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive custom-scrollbar">
            <table class="table table-hover align-middle mb-0 small" style="font-size: 13px;">
                <thead class="table-light text-muted text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Faculty</th>
                        <th class="py-3">Department</th>
                        <th class="py-3">Overall</th>
                        <th class="py-3">Department Head</th>
                        <th class="py-3">Peer-to-Peer</th>
                        <th class="py-3">Student</th>
                        <th class="pe-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="performanceTableBody">
                    <?php if (!empty($performanceDB)): ?>
                        <?php foreach ($performanceDB as $facId => $fac): ?>
                            <tr class="faculty-row"
                                data-name="<?= htmlspecialchars(strtolower($fac['name'])) ?>"
                                data-score="<?= $fac['rawComposite'] ?>"
                                data-dept="<?= htmlspecialchars(strtolower($fac['department'])) ?>">
                                <td class="ps-3 fw-bold text-body"><?= htmlspecialchars($fac['name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($fac['department']) ?></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-body border border-secondary border-opacity-25 px-2 py-1"><?= $fac['compositeScore'] ?></span></td>
                                <td class="text-muted"><?= $fac['headScore'] ?></td>
                                <td class="text-muted"><?= $fac['peerScore'] ?></td>
                                <td class="text-muted"><?= $fac['studentScore'] ?></td>
                                <td class="pe-3 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewFacultyDetails('<?= $facId ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Evaluation Analytics" onclick="viewFacultyAnalytics('<?= $facId ?>')">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-user-slash fs-4 mb-2 d-block opacity-50"></i>
                                <span class="small">No Faculty Professors found.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterTable() {
        const query = document.getElementById('facultySearchInput').value.toLowerCase().trim();
        const ratingFilter = document.getElementById('ratingFilter').value;
        const rows = document.querySelectorAll('.faculty-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const score = parseFloat(row.getAttribute('data-score') || '0');

            const matchesSearch = name.includes(query);
            let matchesRating = true;

            if (ratingFilter === 'outstanding') {
                matchesRating = score >= 4.50;
            } else if (ratingFilter === 'satisfactory') {
                matchesRating = score >= 2.50 && score < 4.50;
            } else if (ratingFilter === 'needs-improvement') {
                matchesRating = score > 0 && score < 2.50;
            }

            row.style.display = (matchesSearch && matchesRating) ? '' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('facultySearchInput').value = '';
        document.getElementById('periodFilter').value = 'all';
        document.getElementById('ratingFilter').value = 'all';
        filterTable();
    }

    function changePerPage() { /* hook */ }

    function viewFacultyDetails(facId) {
        alert('Viewing evaluation details for faculty ID: ' + facId);
    }

    function viewFacultyAnalytics(facId) {
        alert('Viewing evaluation analytics for faculty ID: ' + facId);
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>