<?php
/**
 * Department Head Evaluation Directory
 * Purpose: Let a Department Head evaluate the faculty members in their own
 * department. Access is restricted to accounts whose position is
 * "Department Head" - both here (to hide the UI) and in
 * ProcessDeptHeadEvaluationController.php (to reject the POST).
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

// faculty-data.php defines facultyDb() — load it BEFORE anything calls it.
require_once __DIR__ . '/../../controllers/faculty-data.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$pdo) {
    die('<div style="padding:20px;color:#721c24;background:#f8d7da;margin:20px;border-radius:5px;">
        <strong>Faculty database connection is unavailable.</strong>
    </div>');
}

$pageTitle    = 'Department Head Evaluation';
$activeModule = 'faculty';
$activePage   = 'faculty-member-evaluation';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Department Head Evaluation', 'url' => null],
];

/* ------------------------------------------------------------------
 | 2. Identify current user, department, and real faculty_id (self-healing)
 * ------------------------------------------------------------------ */
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$sessionEmail  = trim((string) ($_SESSION['user_email'] ?? $_SESSION['email'] ?? ''));

$currentFaculty     = null;
$userDept           = '';
$evaluatorProfileId = 0;
$evaluatorFacultyId = null;
$evaluatorPosition  = '';

if ($currentUserId > 0 || $sessionEmail !== '') {
    // (a) by user_id
    if ($currentUserId > 0) {
        $stmt = $pdo->prepare("
            SELECT fp.id, fp.designated_department, fp.email,
                   fp.faculty_id AS profile_faculty_no,
                   fp.position,
                   f.faculty_id AS real_faculty_id
            FROM faculty_profiles fp
            LEFT JOIN faculty f ON f.faculty_id = (
                SELECT f2.faculty_id
                FROM faculty f2
                WHERE (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email)))
                   OR f2.faculty_no = fp.faculty_id
                   OR (fp.user_id IS NOT NULL AND f2.external_user_id = fp.user_id)
                ORDER BY
                    (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email))) DESC,
                    (f2.faculty_no = fp.faculty_id) DESC
                LIMIT 1
            )
            WHERE fp.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':uid' => $currentUserId]);
        $currentFaculty = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // (b) by email + repair user_id
    if (!$currentFaculty && $sessionEmail !== '') {
        $stmt = $pdo->prepare("
            SELECT id, user_id, designated_department, email,
                   faculty_id AS profile_faculty_no, position
            FROM faculty_profiles
            WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email))
            LIMIT 1
        ");
        $stmt->execute([':email' => $sessionEmail]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ($currentUserId > 0 && (int) ($row['user_id'] ?? 0) !== $currentUserId) {
                try {
                    $pdo->prepare("UPDATE faculty_profiles SET user_id = :uid WHERE id = :id")
                        ->execute([':uid' => $currentUserId, ':id' => (int) $row['id']]);
                    error_log("[faculty-member-evaluation] repaired user_id for profile {$row['id']} -> {$currentUserId}");
                } catch (Throwable $e) {
                    error_log('[faculty-member-evaluation][repair] ' . $e->getMessage());
                }
            }

            // Re-run the full lookup now that user_id is fixed
            $stmt = $pdo->prepare("
                SELECT fp.id, fp.designated_department, fp.email,
                       fp.faculty_id AS profile_faculty_no,
                       fp.position,
                       f.faculty_id AS real_faculty_id
                FROM faculty_profiles fp
                LEFT JOIN faculty f ON f.faculty_id = (
                    SELECT f2.faculty_id
                    FROM faculty f2
                    WHERE (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email)))
                       OR f2.faculty_no = fp.faculty_id
                       OR (fp.user_id IS NOT NULL AND f2.external_user_id = fp.user_id)
                    ORDER BY
                        (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email))) DESC,
                        (f2.faculty_no = fp.faculty_id) DESC
                    LIMIT 1
                )
                WHERE fp.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => (int) $row['id']]);
            $currentFaculty = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    if ($currentFaculty) {
        $userDept           = trim((string) ($currentFaculty['designated_department'] ?? ''));
        $evaluatorProfileId = (int) $currentFaculty['id'];
        $evaluatorPosition  = trim((string) ($currentFaculty['position'] ?? ''));
        $evaluatorFacultyId = $currentFaculty['real_faculty_id'] !== null
            ? (int) $currentFaculty['real_faculty_id']
            : null;
    }
}

if ($userDept === '') {
    $userDept = trim((string) ($_SESSION['department'] ?? $_SESSION['designated_department'] ?? ''));
}

$isDeptHead = $evaluatorPosition !== '' && strcasecmp($evaluatorPosition, 'Department Head') === 0;

/* ------------------------------------------------------------------
 | 3. Fetch Faculty in the same department, mapped to real faculty_id
 * ------------------------------------------------------------------ */
$faculty = [];

if ($isDeptHead && $userDept !== '') {
    $stmt = $pdo->prepare("
        SELECT
            fp.id                              AS profile_id,
            f.faculty_id                       AS id,
            fp.faculty_id                      AS employee_id,
            fp.first_name,
            fp.last_name,
            fp.designated_department           AS department,
            fp.position,
            (
                SELECT COUNT(*)
                FROM evaluations e
                WHERE e.evaluator_id = :evaluator_id
                  AND e.faculty_id = f.faculty_id
                  AND e.source_type = 'DeptHead'
            ) AS evaluation_count
        FROM faculty_profiles fp
        LEFT JOIN faculty f ON f.faculty_id = (
            SELECT f2.faculty_id
            FROM faculty f2
            WHERE (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email)))
               OR f2.faculty_no = fp.faculty_id
               OR (fp.user_id IS NOT NULL AND f2.external_user_id = fp.user_id)
            ORDER BY
                (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(TRIM(f2.email)) = LOWER(TRIM(fp.email))) DESC,
                (f2.faculty_no = fp.faculty_id) DESC
            LIMIT 1
        )
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:department)
          AND fp.id != :profile_id
          AND (fp.user_id != :current_user_id OR fp.user_id IS NULL)
        ORDER BY
            CASE
                WHEN f.faculty_id IS NULL THEN 2
                WHEN (SELECT COUNT(*) FROM evaluations e2
                      WHERE e2.evaluator_id = :evaluator_id_2
                        AND e2.faculty_id = f.faculty_id
                        AND e2.source_type = 'DeptHead') > 0 THEN 1
                ELSE 0
            END ASC,
            fp.last_name ASC,
            fp.first_name ASC
    ");

    $stmt->execute([
        'department'        => $userDept,
        'current_user_id'   => $currentUserId,
        'profile_id'        => $evaluatorProfileId,
        'evaluator_id'      => $evaluatorFacultyId ?? 0,
        'evaluator_id_2'    => $evaluatorFacultyId ?? 0,
    ]);

    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Statistics: only linked faculty are evaluable
$totalFaculty   = 0;
$evaluatedCount = 0;
$unlinkedCount  = 0;
foreach ($faculty as $p) {
    if (empty($p['id'])) {
        $unlinkedCount++;
        continue;
    }
    $totalFaculty++;
    if ((int) $p['evaluation_count'] > 0) {
        $evaluatedCount++;
    }
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0 fw-bold text-body">
            <i class="fas fa-user-tie text-primary me-2"></i>Department Head Evaluation
        </h1>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
            <i class="fas fa-building me-1"></i> Department: <?= htmlspecialchars($userDept !== '' ? $userDept : 'Unassigned') ?>
        </span>
    </div>
</div>

<?php if (!$isDeptHead): ?>
    <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger-emphasis d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="fas fa-ban fs-5 flex-shrink-0"></i>
        <div class="small">
            <strong>Access restricted.</strong>
            This page is only available to accounts with the Department Head position.
        </div>
    </div>
<?php else: ?>

    <?php if (empty($evaluatorFacultyId)): ?>
        <div class="alert alert-warning border-warning-subtle bg-warning-subtle text-warning-emphasis d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation fs-5 flex-shrink-0"></i>
            <div class="small">
                <strong>Your account isn't linked to an official faculty record yet.</strong>
                Evaluation submissions will be blocked until your profile is linked.
            </div>
        </div>
    <?php endif; ?>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div id="statusToastSuccess" class="toast align-items-center text-bg-success border-0 shadow-lg show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="fas fa-check-circle fs-5"></i>
                        <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="closeToast('statusToastSuccess')" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div id="statusToastError" class="toast align-items-center text-bg-danger border-0 shadow-lg show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                        <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="closeToast('statusToastError')" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #0d6efd;"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Department Faculty</h6>
                        <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= $totalFaculty ?> <small class="text-muted fs-6 fw-normal">Members</small></h4>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-md-6">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
                <div class="card-body d-flex align-items-center ps-4">
                    <div class="stat-icon me-3 fs-4" style="color: #28a745;"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Completed Ratings</h6>
                        <h4 class="mb-0 fw-bold" style="color: #28a745;"><?= $evaluatedCount ?> <small class="text-muted fs-6 fw-normal">/ <?= $totalFaculty ?> Evaluated</small></h4>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Faculty Table -->
    <div class="card bg-body text-body border-secondary-subtle shadow-sm mb-4">
        <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <h6 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-list-ul me-2"></i>Faculty Members — <?= htmlspecialchars($userDept !== '' ? $userDept : 'Unassigned') ?>
                    </h6>
                    <small class="text-body-secondary">Rate your department's faculty for current academic term</small>
                </div>
                <div class="col-12 col-md-6 d-flex gap-2 justify-content-md-end align-items-center flex-wrap">
                    <select id="facultyStatusFilter" class="form-select form-select-sm bg-body text-body border-secondary-subtle" style="max-width: 150px;" onchange="filterFaculty()">
                        <option value="">All Status</option>
                        <option value="COMPLETED">Done</option>
                        <option value="PENDING">Pending</option>
                    </select>
                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <input type="text" id="facultySearchInput" class="form-control bg-body text-body border-secondary-subtle" placeholder="Search..." onkeyup="filterFaculty()">
                        <button class="btn btn-outline-secondary border-secondary-subtle" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="facultyTable">
                    <thead class="bg-body-tertiary text-body-secondary small text-uppercase border-bottom border-secondary-subtle">
                        <tr>
                            <th class="ps-3" style="width: 80%;">Faculty Member</th>
                            <th class="text-end pe-3" style="width: 20%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($faculty)): ?>
                            <?php foreach ($faculty as $member): ?>
                                <?php
                                    // Skip unlinked profiles entirely — they can't be evaluated
                                    // and shouldn't appear in the list.
                                    if (empty($member['id'])) {
                                        continue;
                                    }

                                    $fullName  = htmlspecialchars('Prof. ' . $member['first_name'] . ' ' . $member['last_name']);
                                    $initials  = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
                                    $facId     = htmlspecialchars($member['employee_id'] ?? $member['id']);
                                    $isDone    = (int) $member['evaluation_count'] > 0;
                                    $searchStr = strtolower($fullName . ' ' . $facId);
                                ?>
                                <tr class="faculty-row border-bottom border-secondary-subtle"
                                    data-status="<?= $isDone ? 'COMPLETED' : 'PENDING' ?>"
                                    data-search="<?= $searchStr ?>">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="<?= $isDone ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?> fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-body"><?= $fullName ?></div>
                                                <small class="text-body-secondary">ID: <?= $facId ?> • Dept: <?= htmlspecialchars($member['department'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($isDone): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold text-uppercase border-0">DONE</span>
                                        <?php else: ?>
                                            <button class="btn btn-primary rounded-pill px-3 py-1 shadow-sm d-inline-flex align-items-center justify-content-center"
                                                    onclick="openEvaluationModal('<?= (int) $member['id'] ?>', '<?= addslashes($fullName) ?>', '<?= htmlspecialchars($member['department']) ?>')"
                                                    title="Evaluate Now" aria-label="Evaluate Now"
                                                    <?= empty($evaluatorFacultyId) ? 'disabled' : '' ?>>
                                                <i class="fas fa-star text-white"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($totalFaculty === 0): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-body-secondary">
                                        No department faculty members found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center py-4 text-body-secondary">
                                    No department faculty members found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="noFacultyMessage" class="text-center py-5 d-none">
                <i class="fas fa-search text-body-secondary fs-2 mb-2"></i>
                <p class="text-body-secondary mb-0">No faculty members found matching your criteria.</p>
            </div>
        </div>

        <!-- Pagination Footer -->
        <div class="card-footer bg-body-tertiary border-top border-secondary-subtle py-2 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <small class="text-body-secondary" id="facultyPaginationInfo">Showing 0 entries</small>
            <nav aria-label="Faculty Table Pagination">
                <ul class="pagination pagination-sm mb-0" id="facultyPagination"></ul>
            </nav>
        </div>
    </div>

    <!-- Evaluation Modal -->
    <div class="modal fade" id="evaluateFacultyModal" tabindex="-1" aria-labelledby="evaluateFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-body text-body border-secondary-subtle shadow-lg">
                <form action="../../controllers/ProcessDeptHeadEvaluationController.php" method="POST" id="deptHeadEvaluationForm" class="d-flex flex-column">
                    <input type="hidden" name="faculty_id" id="modalFacultyId">
                    <div class="modal-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
                        <div>
                            <h5 class="modal-title fw-bold text-primary fs-6 fs-md-5 mb-0" id="evaluateFacultyModalLabel">
                                <i class="fas fa-award me-2"></i>Department Head Evaluation Rating Form
                            </h5>
                            <small class="text-body-secondary d-block">Evaluating: <strong class="text-body" id="modalFacultyName">-</strong> (<span id="modalFacultyDept">-</span>)</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-3 p-sm-4" style="max-height: 60vh; overflow-y: auto;">
                        <div class="alert alert-info border-info-subtle bg-info-subtle text-info-emphasis d-flex align-items-center gap-2 mb-3" role="alert">
                            <i class="fas fa-shield-alt fs-5 flex-shrink-0"></i>
                            <div class="small">
                                This evaluation will be recorded as part of this faculty member's official performance record for the current academic term.
                            </div>
                        </div>

                        <div class="table-responsive border rounded border-secondary-subtle mb-3">
                            <table class="table table-bordered align-middle mb-0" style="min-width: 540px;">
                                <thead class="bg-body-tertiary text-body-secondary small text-uppercase">
                                    <tr>
                                        <th class="sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 45%;">CRITERIA</th>
                                        <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">1<br><small class="text-lowercase font-monospace fw-normal">Poor</small></th>
                                        <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">2<br><small class="text-lowercase font-monospace fw-normal">Fair</small></th>
                                        <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">3<br><small class="text-lowercase font-monospace fw-normal">Good</small></th>
                                        <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">4<br><small class="text-lowercase font-monospace fw-normal">V.Good</small></th>
                                        <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">5<br><small class="text-lowercase font-monospace fw-normal">Excel</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="6" class="p-2 ps-3 fw-bold text-body small text-uppercase">A. Professionalism and Work Performance</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">1. Demonstrates professionalism in carrying out assigned teaching and departmental responsibilities.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">2. Performs assigned duties and functions consistently and responsibly.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">3. Demonstrates punctuality, reliability, and proper compliance with departmental schedules and requirements.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">4. Maintains a professional and respectful attitude toward students, colleagues, administrators, and other personnel.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="5"></td>
                                    </tr>

                                    <tr class="table-secondary">
                                        <td colspan="6" class="p-2 ps-3 fw-bold text-body small text-uppercase">B. Teaching and Academic Responsibilities</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">5. Demonstrates adequate preparation and organization in performing teaching responsibilities.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">6. Effectively performs academic and instructional duties according to established school and departmental standards.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">7. Demonstrates commitment to maintaining the quality of instruction and supporting student learning.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">8. Uses appropriate knowledge, skills, and teaching practices in fulfilling academic responsibilities.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="5"></td>
                                    </tr>

                                    <tr class="table-secondary">
                                        <td colspan="6" class="p-2 ps-3 fw-bold text-body small text-uppercase">C. Departmental Participation and Compliance</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">9. Actively participates in departmental meetings, activities, programs, and other assigned school functions.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">10. Complies with departmental policies, procedures, deadlines, and administrative requirements.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">11. Cooperates with the Department Head and colleagues in accomplishing departmental goals and assigned tasks.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">12. Demonstrates initiative and willingness to contribute to the improvement and development of the department.</div></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="1" required></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="2"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="3"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="4"></td>
                                        <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="5"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <label for="evalRemarks" class="form-label fw-bold text-body small">Other Comments <small class="text-body-secondary fw-normal">(Optional)</small></label>
                            <textarea class="form-control bg-body text-body border-secondary-subtle" id="evalRemarks" name="remarks" rows="3" placeholder="Provide comments or feedback..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle">
                        <button type="button" class="btn btn-outline-secondary border-secondary-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-paper-plane me-1"></i> Submit Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
function filterFaculty() {
    currentFacultyPage = 1;
    renderFacultyTable();
}

function openEvaluationModal(id, name, dept) {
    document.getElementById('modalFacultyId').value = id;
    document.getElementById('modalFacultyName').textContent = name;
    document.getElementById('modalFacultyDept').textContent = dept;
    document.getElementById('deptHeadEvaluationForm').reset();
    new bootstrap.Modal(document.getElementById('evaluateFacultyModal')).show();
}

let currentFacultyPage = 1;
const facultyPerPage = 5;

function renderFacultyTable() {
    const searchInput = document.getElementById('facultySearchInput')?.value.toLowerCase().trim() || '';
    const statusFilter = document.getElementById('facultyStatusFilter')?.value || '';
    const rows = Array.from(document.querySelectorAll('#facultyTable .faculty-row'));
    const noResultsMsg = document.getElementById('noFacultyMessage');
    const paginationNav = document.getElementById('facultyPagination');
    const paginationInfo = document.getElementById('facultyPaginationInfo');

    if (!rows.length && !paginationNav) return;

    const filteredRows = rows.filter(row => {
        const searchData = row.getAttribute('data-search') || '';
        const rowStatus  = row.getAttribute('data-status') || '';
        const matchesSearch = searchData.includes(searchInput);
        const matchesStatus = (statusFilter === '' || rowStatus === statusFilter);
        return matchesSearch && matchesStatus;
    });

    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / facultyPerPage) || 1;

    if (currentFacultyPage > totalPages) currentFacultyPage = totalPages;

    rows.forEach(row => row.classList.add('d-none'));

    if (totalItems > 0) {
        noResultsMsg?.classList.add('d-none');
        const start = (currentFacultyPage - 1) * facultyPerPage;
        const end = start + facultyPerPage;
        filteredRows.slice(start, end).forEach(row => row.classList.remove('d-none'));

        const displayStart = start + 1;
        const displayEnd = Math.min(end, totalItems);
        if (paginationInfo) paginationInfo.textContent = `Showing ${displayStart} to ${displayEnd} of ${totalItems} faculty`;
    } else {
        noResultsMsg?.classList.remove('d-none');
        if (paginationInfo) paginationInfo.textContent = 'Showing 0 entries';
    }
    renderPaginationControls(totalPages, paginationNav);
}

function renderPaginationControls(totalPages, container) {
    if (!container) return;
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentFacultyPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentFacultyPage > 1) { currentFacultyPage--; renderFacultyTable(); }
    });
    container.appendChild(prevLi);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentFacultyPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click', (e) => {
            e.preventDefault();
            currentFacultyPage = i;
            renderFacultyTable();
        });
        container.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentFacultyPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentFacultyPage < totalPages) { currentFacultyPage++; renderFacultyTable(); }
    });
    container.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', renderFacultyTable);

function closeToast(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        ['statusToastSuccess', 'statusToastError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) new bootstrap.Toast(el, { delay: 4000 }).show();
        });
    } else {
        setTimeout(function () {
            ['statusToastSuccess', 'statusToastError'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.style.transition = 'opacity 0.5s ease'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
            });
        }, 4000);
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>