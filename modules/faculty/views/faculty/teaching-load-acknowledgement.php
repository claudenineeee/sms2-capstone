<?php
/**
 * Teaching Load Acknowledgement & Request
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

$pageTitle    = 'Teaching Load';
$activeModule = 'faculty';
$activePage   = 'teaching-load-workflow';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Teaching Load', 'url' => null],
];

$pdo = function_exists('facultyDb') ? facultyDb() : null;
if (!$pdo) {
    die('Database connection failed.');
}

// ==========================================
// 1. PROFILE SETUP & MAX UNITS
// ==========================================
$currentFacultyProfileId = $_SESSION['user']['faculty_profile_id'] ?? null;
$systemUserId            = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$sessionEmail            = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';

$query = "SELECT id, faculty_id, first_name, last_name, employment_status FROM faculty_profiles WHERE ";
$params = [];
if ($currentFacultyProfileId) {
    $query .= "id = :pid"; $params['pid'] = $currentFacultyProfileId;
} elseif ($systemUserId) {
    $query .= "user_id = :uid"; $params['uid'] = $systemUserId;
} elseif ($sessionEmail) {
    $query .= "LOWER(TRIM(email)) = LOWER(TRIM(:email))"; $params['email'] = $sessionEmail;
} else {
    die("Could not identify faculty profile.");
}
$query .= " LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die("Faculty profile not found.");
}

$profileId    = (int)$profile['id'];
$empStatusRaw = strtolower(trim((string)($profile['employment_status'] ?? 'regular')));

$maxAllowedUnits = 24; // Default (Regular/Probationary)
if (strpos($empStatusRaw, 'part') !== false) {
    $maxAllowedUnits = 15;
}

// Get current active term
$termStmt = $pdo->query("SELECT term_id, academic_year, semester FROM academic_terms WHERE is_current = 1 LIMIT 1");
$currentTerm = $termStmt->fetch(PDO::FETCH_ASSOC);
$termId = $currentTerm ? (int)$currentTerm['term_id'] : 1;
$termLabel = $currentTerm ? $currentTerm['academic_year'] . ' - ' . $currentTerm['semester'] . ' Semester' : 'Current Term';

// ==========================================
// 2. HANDLE POST REQUEST (SUBMIT UNITS)
// ==========================================
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'request_load') {
        try {
            requireCsrf($_POST['csrf_token'] ?? '');
            $requestedSubjects = $_POST['subjects'] ?? [];

            if (empty($requestedSubjects)) {
                throw new Exception("Please select at least one subject to request.");
            }

            // Check if user already has pending requests
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM teaching_load_history WHERE faculty_id = ? AND term_id = ? AND status = 'pending'");
            $checkStmt->execute([$profile['faculty_id'], $termId]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("You already have pending requests for this term. Please wait for approval.");
            }

            // Calculate total units
            $placeholders = implode(',', array_fill(0, count($requestedSubjects), '?'));
            $stmtUnits = $pdo->prepare("SELECT SUM(units) FROM subjects WHERE subject_id IN ($placeholders)");
            $stmtUnits->execute($requestedSubjects);
            $totalRequestedUnits = (float)$stmtUnits->fetchColumn();

            if ($totalRequestedUnits > $maxAllowedUnits) {
                throw new Exception("Your request ({$totalRequestedUnits} units) exceeds your maximum allowed limit of {$maxAllowedUnits} units.");
            }

            // Insert Requests (Pending)
            $pdo->beginTransaction();
            $insStmt = $pdo->prepare("
                INSERT INTO teaching_load_history 
                (faculty_id, faculty_no, subject_code, subject_title, units, section, status, term_id, academic_year, semester) 
                SELECT ?, ?, code, title, units, 'TBA', 'pending', ?, ?, ? 
                FROM subjects WHERE subject_id = ?
            ");
            // Note: Adjust columns based on your exact teaching_load_history schema
            foreach ($requestedSubjects as $subjId) {
                $insStmt->execute([
                    $profile['faculty_id'], $profile['faculty_id'], 
                    $termId, $currentTerm['academic_year'] ?? '', $currentTerm['semester'] ?? '',
                    $subjId
                ]);
            }
            $pdo->commit();

            $message = "Teaching load request submitted successfully! Pending Department Head approval.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'acknowledge_load') {
        try {
            requireCsrf($_POST['csrf_token'] ?? '');
            $loadId = (int)$_POST['load_id'];
            
            // Assuming you have an 'acknowledged_at' or 'is_acknowledged' column
            $updStmt = $pdo->prepare("UPDATE teaching_load_history SET is_acknowledged = 1 WHERE id = ? AND faculty_id = ?");
            $updStmt->execute([$loadId, $profile['faculty_id']]);
            
            $message = "Teaching load acknowledged successfully!";
        } catch (Throwable $e) {
            $message = "Failed to acknowledge: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ==========================================
// 3. FETCH DATA FOR UI
// ==========================================
$approvedSubjects = [];
$pendingSubjects = [];
$approvedUnits = 0;
$pendingUnits = 0;

try {
    // Fetch current load (Approved & Pending)
    $stmtLoad = $pdo->prepare("
        SELECT id, subject_code, subject_title, units, status, is_acknowledged 
        FROM teaching_load_history 
        WHERE faculty_id = ? AND term_id = ? AND status IN ('approved', 'pending', 'active')
    ");
    $stmtLoad->execute([$profile['faculty_id'], $termId]);
    $myLoad = $stmtLoad->fetchAll(PDO::FETCH_ASSOC);

    foreach ($myLoad as $l) {
        $st = strtolower($l['status']);
        if ($st === 'approved' || $st === 'active') {
            $approvedSubjects[] = $l;
            $approvedUnits += (float)$l['units'];
        } elseif ($st === 'pending') {
            $pendingSubjects[] = $l;
            $pendingUnits += (float)$l['units'];
        }
    }

    // Fetch available subjects (Not already in load)
    $existingSubjectCodes = array_column($myLoad, 'subject_code');
    $existingCodesStr = empty($existingSubjectCodes) ? "''" : "'" . implode("','", $existingSubjectCodes) . "'";
    
    $subjStmt = $pdo->query("SELECT subject_id, code, title, units FROM subjects WHERE code NOT IN ($existingCodesStr) ORDER BY code ASC");
    $availableSubjects = $subjStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $availableSubjects = [];
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<!-- TOAST NOTIFICATION -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="statusToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="statusToastIcon" class="fas fa-check-circle fs-5"></i>
                <span id="statusToastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid py-3 px-2 px-md-4">
    
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fas fa-book-open text-primary me-2"></i>Teaching Load</h1>
            <p class="text-muted small mb-0">Review your approved load, acknowledge it, and request new subjects for <strong><?= htmlspecialchars($termLabel) ?></strong>.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
                Status: <strong><?= htmlspecialchars(ucfirst($empStatusRaw)) ?></strong>
            </span>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-1 small text-uppercase fw-bold">Max Allowed</h6>
                    <h3 class="mb-0 fw-bold text-primary"><?= $maxAllowedUnits ?> <small class="fs-6 text-muted fw-normal">Units</small></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-1 small text-uppercase fw-bold">Approved Load</h6>
                    <h3 class="mb-0 fw-bold text-success"><?= $approvedUnits ?> <small class="fs-6 text-muted fw-normal">Units</small></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Requests</h6>
                    <h3 class="mb-0 fw-bold text-warning"><?= $pendingUnits ?> <small class="fs-6 text-muted fw-normal">Units</small></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: My Current Load -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-check-circle text-success me-2"></i>My Approved Load</h6>
                    <span class="badge bg-success rounded-pill"><?= count($approvedSubjects) ?> Subjects</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Code</th>
                                    <th>Subject Title</th>
                                    <th class="text-center">Units</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approvedSubjects)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">You have no approved subjects yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($approvedSubjects as $sub): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-primary font-monospace"><?= htmlspecialchars($sub['subject_code']) ?></td>
                                            <td class="text-wrap" style="min-width: 150px;"><?= htmlspecialchars($sub['subject_title']) ?></td>
                                            <td class="text-center fw-bold"><?= $sub['units'] ?></td>
                                            <td class="text-end pe-3">
                                                <?php if (!empty($sub['is_acknowledged'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check me-1"></i>Acknowledged</span>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="acknowledge_load">
                                                        <input type="hidden" name="load_id" value="<?= $sub['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold">
                                                            <i class="fas fa-signature me-1"></i>Acknowledge
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pending Requests View -->
            <?php if (!empty($pendingSubjects)): ?>
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-header bg-warning-subtle border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-warning-emphasis"><i class="fas fa-clock text-warning me-2"></i>Pending Requests (Awaiting Approval)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <tbody>
                                <?php foreach ($pendingSubjects as $sub): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-primary font-monospace"><?= htmlspecialchars($sub['subject_code']) ?></td>
                                        <td class="text-wrap" style="min-width: 150px;"><?= htmlspecialchars($sub['subject_title']) ?></td>
                                        <td class="text-center fw-bold"><?= $sub['units'] ?></td>
                                        <td class="text-end pe-3"><span class="badge bg-warning text-dark">Pending</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Request New Load -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-primary text-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Request Additional Units</h6>
                </div>
                <form method="POST" id="requestForm" class="card-body p-3 d-flex flex-column">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="request_load">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Available Subjects</label>
                        <select class="form-select" id="subjectSelector" onchange="addSubjectToList()">
                            <option value="">-- Select a subject to add --</option>
                            <?php foreach ($availableSubjects as $avail): ?>
                                <option value="<?= $avail['subject_id'] ?>" 
                                        data-code="<?= htmlspecialchars($avail['code']) ?>" 
                                        data-title="<?= htmlspecialchars($avail['title']) ?>" 
                                        data-units="<?= $avail['units'] ?>">
                                    <?= htmlspecialchars($avail['code']) ?> - <?= htmlspecialchars($avail['title']) ?> (<?= $avail['units'] ?> units)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="border rounded-3 bg-body-tertiary mb-3 flex-grow-1" style="min-height: 150px;">
                        <ul class="list-group list-group-flush" id="requestList">
                            <li class="list-group-item text-center text-muted py-4 small" id="emptyRequestMsg">No subjects added yet.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                        <div>
                            <span class="small text-muted d-block">Total Requested</span>
                            <h5 class="mb-0 fw-bold text-primary"><span id="totalReqUnits">0</span> Units</h5>
                        </div>
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2" id="submitReqBtn" disabled>
                            <i class="fas fa-paper-plane me-1"></i> Submit
                        </button>
                    </div>
                    <div id="limitWarning" class="text-danger small mt-2 d-none fw-bold">
                        <i class="fas fa-exclamation-triangle me-1"></i> Total units exceed your maximum allowed limit of <?= $maxAllowedUnits ?>!
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Toast Logic
    const phpMessage = <?= json_encode($message) ?>;
    const messageType = <?= json_encode($messageType) ?>;
    if (phpMessage && phpMessage.trim() !== '') {
        const toastEl = document.getElementById('statusToast');
        const toastIcon = document.getElementById('statusToastIcon');
        const toastMsg = document.getElementById('statusToastMessage');
        
        if (messageType === 'success') {
            toastEl.classList.add('bg-success');
            toastIcon.className = 'fas fa-check-circle fs-5';
        } else {
            toastEl.classList.add('bg-danger');
            toastIcon.className = 'fas fa-exclamation-triangle fs-5';
        }
        toastMsg.textContent = phpMessage;
        
        if (window.bootstrap && bootstrap.Toast) {
            bootstrap.Toast.getOrCreateInstance(toastEl).show();
        }
    }

    // 2. Request Units Logic
    const maxUnits = <?= $maxAllowedUnits ?>;
    const approvedUnits = <?= $approvedUnits ?>;
    const existingPendingUnits = <?= $pendingUnits ?>;
    const remainingQuota = maxUnits - approvedUnits - existingPendingUnits;
    
    let currentRequestUnits = 0;
    let addedSubjects = [];

    function addSubjectToList() {
        const select = document.getElementById('subjectSelector');
        const option = select.options[select.selectedIndex];
        if (!option.value) return;

        const id = option.value;
        const code = option.dataset.code;
        const title = option.dataset.title;
        const units = parseFloat(option.dataset.units);

        if (addedSubjects.includes(id)) {
            alert('Subject already added.');
            select.value = '';
            return;
        }

        addedSubjects.push(id);
        currentRequestUnits += units;

        const list = document.getElementById('requestList');
        const emptyMsg = document.getElementById('emptyRequestMsg');
        if (emptyMsg) emptyMsg.remove();

        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center p-2';
        li.id = 'req_item_' + id;
        li.innerHTML = `
            <div class="text-truncate me-2" style="max-width: 80%;">
                <span class="fw-bold text-primary font-monospace">${code}</span>
                <span class="small text-muted d-block text-truncate">${title}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">${units} u</span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeSubject('${id}', ${units})"><i class="fas fa-times"></i></button>
                <input type="hidden" name="subjects[]" value="${id}" id="input_${id}">
            </div>
        `;
        list.appendChild(li);

        // Remove from dropdown
        option.remove();
        select.value = '';

        updateTotals();
    }

    function removeSubject(id, units) {
        const li = document.getElementById('req_item_' + id);
        if (li) li.remove();
        
        addedSubjects = addedSubjects.filter(subId => subId !== id);
        currentRequestUnits -= units;
        
        updateTotals();
        
        // If list is empty, show placeholder
        if (addedSubjects.length === 0) {
            const list = document.getElementById('requestList');
            list.innerHTML = '<li class="list-group-item text-center text-muted py-4 small" id="emptyRequestMsg">No subjects added yet.</li>';
        }
    }

    function updateTotals() {
        document.getElementById('totalReqUnits').textContent = currentRequestUnits;
        
        const submitBtn = document.getElementById('submitReqBtn');
        const warning = document.getElementById('limitWarning');

        // Disable submit if no subjects or if it exceeds remaining quota
        if (currentRequestUnits === 0) {
            submitBtn.disabled = true;
            warning.classList.add('d-none');
        } else if (currentRequestUnits > remainingQuota) {
            submitBtn.disabled = true;
            warning.classList.remove('d-none');
        } else {
            submitBtn.disabled = false;
            warning.classList.add('d-none');
        }
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>