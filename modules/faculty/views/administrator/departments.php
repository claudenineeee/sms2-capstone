<?php
/**
 * SMS 2 - Department & Program Management View
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

$pdo = db();
$message = '';
$messageType = 'success';

// Handle Add / Edit / Toggle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['dept_code'] ?? ''));
        $name = trim($_POST['dept_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!empty($code) && !empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO faculty_db.departments (code, name, description) VALUES (:code, :name, :desc)");
                $stmt->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':desc' => $description
                ]);
                $message = "Department '{$code}' created successfully!";
            } catch (PDOException $e) {
                $message = "Error adding department: Department code may already exist.";
                $messageType = "danger";
            }
        }
    } elseif ($action === 'toggle_status') {
        $deptId = (int)($_POST['dept_id'] ?? 0);
        $currentStatus = $_POST['current_status'] ?? 'Active';
        $newStatus = ($currentStatus === 'Active') ? 'Inactive' : 'Active';

        if ($deptId > 0) {
            $stmt = $pdo->prepare("UPDATE faculty_db.departments SET status = :status WHERE department_id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $deptId]);
            $message = "Department status updated to {$newStatus}.";
        }
    }
}

// Fetch departments with assigned faculty count matching faculty_profiles table
$stmt = $pdo->query("
    SELECT d.*, 
           (SELECT COUNT(*) 
            FROM faculty_db.faculty_profiles fp 
            WHERE fp.designated_department = d.code) AS faculty_count
    FROM faculty_db.departments d
    ORDER BY d.created_at DESC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle    = 'Department Management';
$activeModule = 'faculty';
$activePage   = 'departments';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Departments', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid py-3 px-2 px-md-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h5 h4-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-building text-primary"></i>
                <span>Department & Program Management</span>
            </h1>
            <p class="text-body-secondary small mb-0">Add, configure, and manage academic departments and degree programs across campus.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm small fw-semibold px-3 rounded-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="fas fa-plus me-1"></i> Add New Department
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-3 shadow-sm small" role="alert">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Table Container Card -->
    <div class="card bg-body-tertiary border border-light-subtle shadow-sm rounded-4">
        <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="card-title text-body mb-0 fw-bold small">
                <i class="fas fa-list me-2 text-info"></i>Configured Departments & Programs
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="text-body-secondary border-light-subtle text-nowrap">
                            <th>Code</th>
                            <th>Department / Program Title</th>
                            <th class="d-none d-md-table-cell">Description</th>
                            <th class="text-center d-none d-sm-table-cell">Assigned Faculty</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-body-secondary py-5">
                                    <i class="fas fa-building-circle-exclamation fa-3x mb-3 text-body-tertiary d-block"></i>
                                    <h5 class="small fw-bold">No departments found</h5>
                                    <p class="mb-0 small">Click "Add New Department" above to register programs like BS CRIM, BSED, etc.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td class="fw-bold text-primary align-middle text-nowrap"><?= htmlspecialchars($dept['code']) ?></td>
                                    <td class="fw-semibold text-body align-middle"><?= htmlspecialchars($dept['name']) ?></td>
                                    <td class="text-body-secondary d-none d-md-table-cell align-middle"><?= htmlspecialchars($dept['description'] ?? '—') ?></td>
                                    <td class="text-center d-none d-sm-table-cell align-middle text-nowrap">
                                        <span class="badge bg-body-secondary text-body border border-light-subtle px-2 py-1">
                                            <?= number_format($dept['faculty_count']) ?> Faculty
                                        </span>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <?php if (($dept['status'] ?? 'Active') === 'Active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <form method="post" class="d-inline-flex justify-content-center align-items-center mb-0">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="dept_id" value="<?= $dept['department_id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $dept['status'] ?? 'Active' ?>">
                                            <button type="submit" class="btn btn-sm <?= ($dept['status'] ?? 'Active') === 'Active' ? 'btn-outline-warning' : 'btn-outline-success' ?> rounded-3 fw-semibold text-nowrap px-2 py-1">
                                                <i class="fas <?= ($dept['status'] ?? 'Active') === 'Active' ? 'fa-ban' : 'fa-check' ?> me-1"></i>
                                                <?= ($dept['status'] ?? 'Active') === 'Active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add New Department -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold small text-body">
                    <i class="fas fa-plus-circle text-primary me-2"></i>Add New Program/Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-3 p-md-4 small">
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Department Code / Acronym <span class="text-danger">*</span></label>
                        <input type="text" name="dept_code" class="form-control bg-body text-body border-light-subtle small shadow-none" placeholder="e.g., BS CRIM, BSED, BSBA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Program / Department Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="dept_name" class="form-control bg-body text-body border-light-subtle small shadow-none" placeholder="e.g., Bachelor of Science in Criminology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Description / Notes</label>
                        <textarea name="description" class="form-control bg-body text-body border-light-subtle small shadow-none" rows="3" placeholder="Optional details regarding department scope..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-3 px-md-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3 small" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold small px-3 px-md-4">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>