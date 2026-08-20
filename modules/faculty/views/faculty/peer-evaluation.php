<?php
/**
 * Peer Evaluation Directory
 * Purpose: View and submit peer evaluations dynamically based on user department
 */
require_once __DIR__ . '/../../../../config/config.php';

// 1. Establish Database Connection
if (!isset($pdo) || !$pdo) {
    $pdo = $conn ?? $db ?? null;
}

if (!$pdo) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'faculty_db'; 
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('<div style="padding: 20px; color: #721c24; background-color: #f8d7da; margin: 20px; border-radius: 5px;">
            <strong>Database Connection Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>');
    }
}

$pageTitle    = 'Peer Evaluation';
$activeModule = 'faculty';
$activePage   = 'peer-evaluation';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Peer Evaluation', 'url' => null],
];

// 2. Identify Current User & Department
$currentUserId  = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$currentFaculty = null;
$userDept       = null;

if ($currentUserId) {
    try {
        $stmt = $pdo->prepare("SELECT id, designated_department FROM faculty_profiles WHERE user_id = :user_id OR id = :id LIMIT 1");
        $stmt->execute(['user_id' => $currentUserId, 'id' => $currentUserId]);
        $currentFaculty = $stmt->fetch();
        
        if ($currentFaculty) {
            $userDept = trim($currentFaculty['designated_department'] ?? '');
        }
    } catch (PDOException $e) {
        $userDept = null;
    }
}

// Fallback to session variables if missing
if (empty($userDept)) {
    $userDept = trim($_SESSION['department'] ?? $_SESSION['designated_department'] ?? '');
}

// 3. Fetch Department Peers from faculty_profiles
$peers = [];
$evaluatorId = $currentFaculty['id'] ?? $currentUserId;

if (!empty($userDept)) {
    // Primary query: match department ignoring status case/whitespace
    $stmt = $pdo->prepare("
        SELECT 
            fp.id, 
            fp.faculty_id AS employee_id, 
            fp.first_name, 
            fp.last_name, 
            fp.designated_department AS department,
            fp.position,
            (SELECT COUNT(*) 
             FROM evaluations e 
             WHERE e.evaluator_id = :evaluator_id 
               AND e.faculty_id = fp.id
               AND e.source_type = 'Peer'
            ) AS evaluation_count
        FROM faculty_profiles fp
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:department)
          AND fp.id != :evaluator_id
          AND (fp.user_id != :current_user_id OR fp.user_id IS NULL)
        ORDER BY fp.last_name ASC
    ");

    $stmt->execute([
        'department'      => $userDept,
        'current_user_id' => $currentUserId,
        'evaluator_id'    => $evaluatorId
    ]);

    $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fallback Query: If department is unassigned or returns zero records, retrieve all other faculty
if (empty($peers)) {
    $stmt = $pdo->prepare("
        SELECT 
            fp.id, 
            fp.faculty_id AS employee_id, 
            fp.first_name, 
            fp.last_name, 
            fp.designated_department AS department,
            fp.position,
            (SELECT COUNT(*) 
             FROM evaluations e 
             WHERE e.evaluator_id = :evaluator_id 
               AND e.faculty_id = fp.id
               AND e.source_type = 'Peer'
            ) AS evaluation_count
        FROM faculty_profiles fp
        WHERE fp.id != :evaluator_id
        ORDER BY fp.last_name ASC
    ");

    $stmt->execute(['evaluator_id' => $evaluatorId]);
    $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate Statistics
$totalPeers = count($peers);
$evaluatedCount = 0;
foreach ($peers as $p) {
    if ($p['evaluation_count'] > 0) {
        $evaluatedCount++;
    }
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header & Department Context -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0 fw-bold text-body">
            <i class="fas fa-user-check text-primary me-2"></i>Peer Evaluation Directory
        </h1>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
            <i class="fas fa-building me-1"></i> Department: <?= htmlspecialchars(!empty($userDept) ? $userDept : 'All Departments') ?>
        </span>
    </div>
</div>

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

<!-- Evaluation Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-primary-subtle text-primary rounded-3 me-3 fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Department Peers</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $totalPeers ?> Colleagues</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-success-subtle text-success rounded-3 me-3 fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Completed Ratings</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $evaluatedCount ?> / <?= $totalPeers ?> Evaluated</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Peers Table Card -->
<div class="card bg-body text-body border-secondary-subtle shadow-sm mb-4">
    <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <h6 class="mb-0 text-primary fw-bold">
                    <i class="fas fa-list-ul me-2"></i>List of Co-Teachers — <?= htmlspecialchars(!empty($userDept) ? $userDept : 'All Departments') ?>
                </h6>
                <small class="text-body-secondary">Rate your peers for current academic term</small>
            </div>
            <div class="col-12 col-md-6 d-flex gap-2 justify-content-md-end">
                <div class="input-group input-group-sm" style="max-width: 240px;">
                    <input type="text" id="peerSearchInput" class="form-control bg-body text-body border-secondary-subtle" placeholder="Search..." onkeyup="filterPeers()">
                    <button class="btn btn-outline-secondary border-secondary-subtle" type="button"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="peerTable">
                <thead class="bg-body-tertiary text-body-secondary small text-uppercase border-bottom border-secondary-subtle">
                    <tr>
                        <th class="ps-3" style="width: 80%;">Faculty Member</th>
                        <th class="text-end pe-3" style="width: 20%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($peers)): ?>
                        <?php foreach ($peers as $peer): ?>
                            <?php 
                                $fullName  = htmlspecialchars('Prof. ' . $peer['first_name'] . ' ' . $peer['last_name']);
                                $initials  = strtoupper(substr($peer['first_name'], 0, 1) . substr($peer['last_name'], 0, 1));
                                $facId     = htmlspecialchars($peer['employee_id'] ?? $peer['id']);
                                $isDone    = $peer['evaluation_count'] > 0;
                                $searchStr = strtolower($fullName . ' ' . $facId);
                            ?>
                            <tr class="peer-row border-bottom border-secondary-subtle" 
                                data-status="<?= $isDone ? 'COMPLETED' : 'PENDING' ?>" 
                                data-search="<?= $searchStr ?>">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="<?= $isDone ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?> fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <?= $initials ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body"><?= $fullName ?></div>
                                            <small class="text-body-secondary">ID: <?= $facId ?> • Dept: <?= htmlspecialchars($peer['department'] ?? 'N/A') ?></small>
                                        </div>
                                    </div>
                                </td>                      
                                <td class="text-end pe-3">
                                    <?php if ($isDone): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold text-uppercase border-0">DONE</span>
                                    <?php else: ?>
                                        <button class="btn btn-primary rounded-pill px-3 py-1 shadow-sm d-inline-flex align-items-center justify-content-center" 
                                                onclick="openEvaluationModal('<?= $peer['id'] ?>', '<?= addslashes($fullName) ?>', '<?= htmlspecialchars($peer['department']) ?>')" 
                                                title="Evaluate Now" 
                                                aria-label="Evaluate Now">
                                            <i class="fas fa-star text-white"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center py-4 text-body-secondary">
                                No active faculty members found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="noPeersMessage" class="text-center py-5 d-none">
            <i class="fas fa-search text-body-secondary fs-2 mb-2"></i>
            <p class="text-body-secondary mb-0">No department peers found matching your criteria.</p>
        </div>
    </div>

    <!-- Table Footer with Pagination Controls -->
    <div class="card-footer bg-body-tertiary border-top border-secondary-subtle py-2 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <small class="text-body-secondary" id="peerPaginationInfo">Showing 0 entries</small>
        <nav aria-label="Peer Table Pagination">
            <ul class="pagination pagination-sm mb-0" id="peerPagination">
            </ul>
        </nav>
    </div>
</div>

<!-- Peer Evaluation Rating Form Modal -->
<div class="modal fade" id="evaluateFacultyModal" tabindex="-1" aria-labelledby="evaluateFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow-lg">           
            <form action="../../controllers/ProcessPeerEvaluationController.php" method="POST" id="peerEvaluationForm" class="d-flex flex-column">
                <input type="hidden" name="faculty_id" id="modalFacultyId">
                <div class="modal-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
                    <div>
                        <h5 class="modal-title fw-bold text-primary fs-6 fs-md-5 mb-0" id="evaluateFacultyModalLabel">
                            <i class="fas fa-award me-2"></i>Peer Evaluation Rating Form
                        </h5>
                        <small class="text-body-secondary d-block">Evaluating: <strong class="text-body" id="modalFacultyName">-</strong> (<span id="modalFacultyDept">-</span>)</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 p-sm-4" style="max-height: 60vh; overflow-y: auto;">
                    <div class="alert alert-info border-info-subtle bg-info-subtle text-info-emphasis d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="fas fa-shield-alt fs-5 flex-shrink-0"></i>
                        <div class="small">
                            Your rating is <strong>completely anonymous</strong> and will be aggregated into the department's peer evaluation report.
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
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">1. shows professionalism in dealing with administrators.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">2. shows professionalism in dealing with colleagues or peers.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">3. shows a high sense of responsibility in performing his/her duties and functions.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">4. shows care and concern for others.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">5. is just and fair in dealing with colleagues and non-teaching personnel.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">6. sets example of integrity and morality.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">7. knows how to keep confidential matters.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">8. sets example of clean and honest living.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">9. has the initiative and willingness to participate in school activities.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">10. respects ideas and opinion of others.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">11. speaks decent language.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_11" value="5"></td>
                                </tr>
                                <tr>
                                    <td class="p-2 p-sm-3"><div class="fw-bold text-body small fs-sm-6">12. is dependable, reliable and cooperative in group and individual assigned tasks.</div></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="1" required></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="2"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="3"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="4"></td>
                                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_12" value="5"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Qualitative Feedback -->
                    <div class="mt-3">
                        <label for="evalRemarks" class="form-label fw-bold text-body small">Other Comments <small class="text-body-secondary fw-normal">(Optional)</small></label>
                        <textarea class="form-control bg-body text-body border-secondary-subtle" id="evalRemarks" name="remarks" rows="3" placeholder="Provide comments or feedback..."></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
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

<script>
function filterPeers() {
    currentPeerPage = 1;
    renderPeerTable();
}

function openEvaluationModal(id, name, dept) {
    document.getElementById('modalFacultyId').value = id;
    document.getElementById('modalFacultyName').textContent = name;
    document.getElementById('modalFacultyDept').textContent = dept;

    document.getElementById('peerEvaluationForm').reset();

    const modal = new bootstrap.Modal(document.getElementById('evaluateFacultyModal'));
    modal.show();
}

// Pagination Logic
let currentPeerPage = 1;
const peersPerPage = 5;

function renderPeerTable() {
    const searchInput = document.getElementById('peerSearchInput').value.toLowerCase().trim();
    const rows = Array.from(document.querySelectorAll('#peerTable .peer-row'));
    const noResultsMsg = document.getElementById('noPeersMessage');
    const paginationNav = document.getElementById('peerPagination');
    const paginationInfo = document.getElementById('peerPaginationInfo');

    const filteredRows = rows.filter(row => {
        const searchData = row.getAttribute('data-search') || '';
        return searchData.includes(searchInput);
    });

    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / peersPerPage) || 1;

    if (currentPeerPage > totalPages) {
        currentPeerPage = totalPages;
    }

    rows.forEach(row => row.classList.add('d-none'));

    if (totalItems > 0) {
        noResultsMsg.classList.add('d-none');
        const start = (currentPeerPage - 1) * peersPerPage;
        const end = start + peersPerPage;

        filteredRows.slice(start, end).forEach(row => row.classList.remove('d-none'));

        const displayStart = start + 1;
        const displayEnd = Math.min(end, totalItems);
        paginationInfo.textContent = `Showing ${displayStart} to ${displayEnd} of ${totalItems} peers`;
    } else {
        noResultsMsg.classList.remove('d-none');
        paginationInfo.textContent = 'Showing 0 entries';
    }
    renderPaginationControls(totalPages, paginationNav);
}

function renderPaginationControls(totalPages, container) {
    container.innerHTML = '';

    if (totalPages <= 1) return;
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPeerPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPeerPage > 1) {
            currentPeerPage--;
            renderPeerTable();
        }
    });
    container.appendChild(prevLi);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPeerPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click', (e) => {
            e.preventDefault();
            currentPeerPage = i;
            renderPeerTable();
        });
        container.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPeerPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPeerPage < totalPages) {
            currentPeerPage++;
            renderPeerTable();
        }
    });
    container.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', renderPeerTable);

// ALERT
function closeToast(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

document.addEventListener('DOMContentLoaded', function () {
    // If Bootstrap JS is loaded, initialize cleanly
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        ['statusToastSuccess', 'statusToastError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const toast = new bootstrap.Toast(el, { delay: 4000 });
                toast.show();
            }
        });
    } else {
        // Fallback: Automatically hide after 4 seconds if Bootstrap JS is missing
        setTimeout(function() {
            ['statusToastSuccess', 'statusToastError'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.transition = 'opacity 0.5s ease';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 500);
                }
            });
        }, 4000);
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>