<?php

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle = 'Department Assignments';
$activeModule = 'faculty';
$activePage = 'department-assignments';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Departments', 'url' => BASE_URL . '/modules/faculty/views/administrator/departments.php'],
    ['label' => 'Assignments', 'url' => null],
];

$formError = '';
$formSuccess = '';

if (isset($_GET['success'])) {
    $formSuccess = (string) $_GET['success'];
}

$departments = [];
$headsByDept = [];
$deanAssignmentsByDeanId = [];
$allHeads = [];

try {

    $pdo = facultyDb();

    if (!$pdo) {
        throw new RuntimeException('Unable to connect to the faculty database.');
    }

    /*
     * Handle reassignment actions before rendering.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'reassign_head') {

            $deptId = (int) ($_POST['department_id'] ?? 0);
            $headProfileId = (int) ($_POST['head_profile_id'] ?? 0);

            if ($deptId <= 0) {
                throw new InvalidArgumentException('Please select a valid department.');
            }

            $deptStmt = $pdo->prepare("SELECT code FROM faculty_db.departments WHERE department_id = :id LIMIT 1");
            $deptStmt->execute([':id' => $deptId]);
            $deptCode = $deptStmt->fetchColumn();

            if (!$deptCode) {
                throw new InvalidArgumentException('Department not found.');
            }

            if ($headProfileId > 0) {
                // Assign this profile as the head of the chosen department.
                // They are moved OUT of whichever department they previously headed.
                $update = $pdo->prepare("
                    UPDATE faculty_db.faculty_profiles
                    SET designated_department = :code
                    WHERE id = :id AND position = 'Department Head'
                ");
                $update->execute([':code' => $deptCode, ':id' => $headProfileId]);

                if ($update->rowCount() === 0) {
                    throw new RuntimeException('That profile could not be assigned (not found or not a Department Head).');
                }

                $redirectMessage = 'Department Head reassigned successfully.';
            } else {
                // "-- No Head Assigned --" selected: clear whoever currently
                // heads this department back to unassigned.
                $clear = $pdo->prepare("
                    UPDATE faculty_db.faculty_profiles
                    SET designated_department = NULL
                    WHERE position = 'Department Head' AND designated_department = :code
                ");
                $clear->execute([':code' => $deptCode]);

                $redirectMessage = 'Department Head unassigned.';
            }

            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode($redirectMessage));
            exit;
        }

        if ($action === 'update_dean_assignments') {

            $deanProfileId = (int) ($_POST['dean_profile_id'] ?? 0);
            $selectedDeptIds = array_filter(array_map('intval', (array) ($_POST['department_ids'] ?? [])));

            if ($deanProfileId <= 0) {
                throw new InvalidArgumentException('Invalid Dean profile.');
            }

            $verify = $pdo->prepare("SELECT id FROM faculty_db.faculty_profiles WHERE id = :id AND position = 'Dean' LIMIT 1");
            $verify->execute([':id' => $deanProfileId]);
            if (!$verify->fetchColumn()) {
                throw new InvalidArgumentException('That profile is not a Dean.');
            }

            $pdo->beginTransaction();

            // Replace the full assignment set for this Dean: remove all,
            // then re-add exactly what was checked. Simpler and safer than
            // diffing, and this table is small per Dean.
            $delete = $pdo->prepare("DELETE FROM faculty_db.faculty_profile_department_assignments WHERE faculty_profile_id = :id");
            $delete->execute([':id' => $deanProfileId]);

            if (!empty($selectedDeptIds)) {
                $insert = $pdo->prepare("
                    INSERT INTO faculty_db.faculty_profile_department_assignments (faculty_profile_id, department_id)
                    VALUES (:profile_id, :dept_id)
                ");
                foreach ($selectedDeptIds as $deptId) {
                    $insert->execute([':profile_id' => $deanProfileId, ':dept_id' => $deptId]);
                }

                // Keep designated_department (the "primary" department) in
                // sync too, defaulting to the first checked department.
                $deptCodeStmt = $pdo->prepare("SELECT code FROM faculty_db.departments WHERE department_id = :id LIMIT 1");
                $deptCodeStmt->execute([':id' => $selectedDeptIds[0]]);
                $primaryCode = $deptCodeStmt->fetchColumn();

                if ($primaryCode) {
                    $updatePrimary = $pdo->prepare("UPDATE faculty_db.faculty_profiles SET designated_department = :code WHERE id = :id");
                    $updatePrimary->execute([':code' => $primaryCode, ':id' => $deanProfileId]);
                }
            }

            $pdo->commit();

            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode('Dean department coverage updated.'));
            exit;
        }

        throw new InvalidArgumentException('Unknown action.');
    }

    /*
     * Load data for display.
     */
    $departments = $pdo->query("SELECT department_id, code, name FROM faculty_db.departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Current head per department (by designated_department code).
    $headRows = $pdo->query("
        SELECT id, designated_department, CONCAT_WS(' ', first_name, last_name) AS name
        FROM faculty_db.faculty_profiles
        WHERE position = 'Department Head'
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($headRows as $h) {
        $headsByDept[$h['designated_department']][] = $h;
        $allHeads[] = $h;
    }

    // All Deans + which departments they currently cover.
    $deanRows = $pdo->query("
        SELECT fp.id, CONCAT_WS(' ', fp.first_name, fp.last_name) AS name, a.department_id
        FROM faculty_db.faculty_profiles fp
        LEFT JOIN faculty_db.faculty_profile_department_assignments a ON a.faculty_profile_id = fp.id
        WHERE fp.position = 'Dean'
        ORDER BY fp.last_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deanRows as $d) {
        if (!isset($deanAssignmentsByDeanId[$d['id']])) {
            $deanAssignmentsByDeanId[$d['id']] = [
                'id' => $d['id'],
                'name' => $d['name'],
                'department_ids' => [],
            ];
        }
        if ($d['department_id']) {
            $deanAssignmentsByDeanId[$d['id']]['department_ids'][] = (int) $d['department_id'];
        }
    }

} catch (Throwable $e) {

    $formError = $e->getMessage();

    error_log(
        '[department-assignments] ' .
        $e->getMessage() .
        PHP_EOL .
        $e->getTraceAsString()
    );
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-building text-primary me-2"></i>
            Department & Program Management
        </h1>
        <p class="text-body-secondary mb-0">Add, configure, and manage academic departments and degree programs across campus.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm small fw-semibold px-3 rounded-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addDeptModal">
        <i class="fas fa-plus me-1"></i> Add New Department
    </button>
</div>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger rounded-3 mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success rounded-3 mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body-tertiary fw-bold text-body py-3 border-bottom">
        <i class="fas fa-user-shield text-primary me-2"></i>Department Heads
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-body-tertiary text-body">
                <tr>
                    <th class="ps-3">Department</th>
                    <th>Current Head</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                    <?php
                        $heads = $headsByDept[$dept['code']] ?? [];
                        $currentHead = $heads[0] ?? null;
                    ?>
                    <tr>
                        <td class="fw-bold ps-3">
                            <span class="text-body"><?= htmlspecialchars($dept['code'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="text-body-secondary small d-block fw-normal"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php if ($currentHead): ?>
                                <span class="text-body fw-semibold"><?= htmlspecialchars($currentHead['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (count($heads) > 1): ?>
                                    <?php 
                                        $headsJson = htmlspecialchars(json_encode($heads), ENT_QUOTES, 'UTF-8');
                                        $deptCodeJson = htmlspecialchars(json_encode($dept['code']), ENT_QUOTES, 'UTF-8');
                                        $deptId = (int) $dept['department_id'];
                                    ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-warning fw-bold border-0 shadow-sm ms-2 py-0 px-2"
                                            onclick="openMultipleHeadsModal(<?= $deptId ?>, <?= $deptCodeJson ?>, <?= $headsJson ?>)">
                                        <i class="fas fa-layer-group me-1"></i>+<?= count($heads) - 1 ?> more
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-body-secondary small">&mdash; Unassigned &mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="openHeadModal(<?= (int) $dept['department_id'] ?>, '<?= htmlspecialchars(addslashes($dept['code']), ENT_QUOTES, 'UTF-8') ?>', <?= $currentHead ? (int) $currentHead['id'] : 0 ?>)">
                                <i class="fas fa-user-edit me-1"></i>Reassign
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body-tertiary fw-bold text-body py-3 border-bottom">
        <i class="fas fa-user-tie text-primary me-2"></i>Deans
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-body-tertiary text-body">
                <tr>
                    <th class="ps-3">Dean</th>
                    <th>Departments Overseen</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deanAssignmentsByDeanId)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-body-secondary py-4">No Deans found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deanAssignmentsByDeanId as $dean): ?>
                        <?php
                            $deptLabels = [];
                            foreach ($dean['department_ids'] as $did) {
                                foreach ($departments as $d) {
                                    if ((int) $d['department_id'] === $did) {
                                        $deptLabels[] = $d['code'];
                                    }
                                }
                            }
                        ?>
                        <tr>
                            <td class="fw-bold ps-3 text-body"><?= htmlspecialchars($dean['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (empty($deptLabels)): ?>
                                    <span class="text-body-secondary small">&mdash; None assigned &mdash;</span>
                                <?php else: ?>
                                    <?php foreach ($deptLabels as $label): ?>
                                        <span class="badge bg-primary-subtle text-primary border me-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="openDeanModal(<?= (int) $dean['id'] ?>, '<?= htmlspecialchars(addslashes($dean['name']), ENT_QUOTES, 'UTF-8') ?>', <?= json_encode($dean['department_ids']) ?>)">
                                    <i class="fas fa-edit me-1"></i>Edit Departments
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reassign Department Head Modal -->
<div class="modal fade" id="headModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-body">Reassign Department Head</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <input type="hidden" name="action" value="reassign_head">
                    <input type="hidden" name="department_id" id="hm-dept-id">
                    <p class="text-body-secondary small mb-3">Department: <strong id="hm-dept-label" class="text-body"></strong></p>
                    
                    <label class="form-label small fw-bold text-body mb-1">Department Head</label>
                    <select name="head_profile_id" id="hm-head-select" class="form-select">
                        <option value="0">&mdash; No Head Assigned &mdash;</option>
                        <?php foreach ($allHeads as $h): ?>
                            <option value="<?= (int) $h['id'] ?>"><?= htmlspecialchars($h['name'], ENT_QUOTES, 'UTF-8') ?> (currently: <?= htmlspecialchars($h['designated_department'] ?? '—', ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-body-secondary small mt-1">Choosing a name here moves them out of whichever department they currently head.</div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Multiple Heads List Modal -->
<div class="modal fade" id="multipleHeadsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-body">
                    <i class="fas fa-users-cog text-warning me-2"></i>
                    All Heads for <span id="mhm-dept-label"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <p class="text-body-secondary small mb-3">
                    The following Department Heads are assigned to this department. Click <strong>Reassign</strong> on any individual to manage their position:
                </p>
                <div class="list-group shadow-sm" id="mhm-heads-list">
                    <!-- Populated dynamically via JavaScript -->
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Dean Departments Modal -->
<div class="modal fade" id="deanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-body">Edit Dean's Departments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <input type="hidden" name="action" value="update_dean_assignments">
                    <input type="hidden" name="dean_profile_id" id="dm-dean-id">
                    <p class="text-body-secondary small mb-3">Dean: <strong id="dm-dean-label" class="text-body"></strong></p>
                    
                    <label class="form-label small fw-bold text-body mb-2">Departments Overseen</label>
                    <div id="dm-dept-checkboxes" class="border rounded-3 p-3 d-flex flex-wrap gap-3 bg-body-tertiary">
                        <?php foreach ($departments as $dept): ?>
                            <div class="form-check">
                                <input class="form-check-input dm-dept-checkbox" type="checkbox" name="department_ids[]"
                                       value="<?= (int) $dept['department_id'] ?>" id="dm-dept-<?= (int) $dept['department_id'] ?>">
                                <label class="form-check-label text-body" for="dm-dept-<?= (int) $dept['department_id'] ?>"><?= htmlspecialchars($dept['code'], ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openHeadModal(deptId, deptLabel, currentHeadId) {
    document.getElementById('hm-dept-id').value = deptId;
    document.getElementById('hm-dept-label').textContent = deptLabel;
    document.getElementById('hm-head-select').value = currentHeadId || '0';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('headModal')).show();
}

function openMultipleHeadsModal(deptId, deptCode, headsList) {
    document.getElementById('mhm-dept-label').textContent = deptCode;
    
    const container = document.getElementById('mhm-heads-list');
    container.innerHTML = '';

    headsList.forEach(function (head) {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-body';
        
        item.innerHTML = `
            <div>
                <div class="fw-bold text-body">${head.name}</div>
                <small class="text-body-secondary">Profile ID: ${head.id}</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="switchModalToReassign(${deptId}, '${deptCode}', ${head.id})">
                <i class="fas fa-user-edit me-1"></i>Reassign
            </button>
        `;
        container.appendChild(item);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('multipleHeadsModal')).show();
}

function switchModalToReassign(deptId, deptCode, headId) {
    // Hide list modal, then open reassign modal pre-selecting this specific head
    const multModalEl = document.getElementById('multipleHeadsModal');
    const multModal = bootstrap.Modal.getInstance(multModalEl);
    if (multModal) {
        multModal.hide();
    }
    
    openHeadModal(deptId, deptCode, headId);
}

function openDeanModal(deanId, deanLabel, currentDeptIds) {
    document.getElementById('dm-dean-id').value = deanId;
    document.getElementById('dm-dean-label').textContent = deanLabel;

    document.querySelectorAll('.dm-dept-checkbox').forEach(function (cb) {
        cb.checked = currentDeptIds.includes(parseInt(cb.value, 10));
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('deanModal')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>