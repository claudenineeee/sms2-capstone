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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = strtoupper(trim($_POST['dept_code'] ?? ''));
        $name = trim($_POST['dept_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!empty($code) && !empty($name)) {
            // ==========================================
            // NEW: CHECK FOR DUPLICATE CODE BEFORE INSERTING
            // ==========================================
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE code = :code");
            $checkStmt->execute([':code' => $code]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $message = "Department code '{$code}' already exists. Please use a unique code.";
                $messageType = "danger";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO departments (code, name, description) VALUES (:code, :name, :desc)");
                    $stmt->execute([
                        ':code' => $code,
                        ':name' => $name,
                        ':desc' => $description
                    ]);
                    $message = "Department '{$code}' created successfully!";
                } catch (PDOException $e) {
                    $message = "Error adding department: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Department code and full name are required.";
            $messageType = "danger";
        }
    }
}

// Fetch departments (added GROUP BY to hide duplicates you already created)
$stmt = $pdo->query("
    SELECT d.*, 
           (SELECT COUNT(*) 
            FROM faculty_profiles fp 
            WHERE fp.designated_department = d.code) AS faculty_count
    FROM departments d
    GROUP BY d.code
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
                <span>Department &amp; Program Management</span>
            </h1>
            <p class="text-body-secondary small mb-0">Add, configure, and manage academic departments and degree programs across campus.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm small fw-semibold px-3 rounded-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="fas fa-plus me-1"></i> Add New Department
        </button>
    </div>

    <!-- Table Container Card -->
    <div class="card bg-body-tertiary border border-light-subtle shadow-sm rounded-4">
        <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="card-title text-body mb-0 fw-bold fs-6">
                <i class="fas fa-list me-2 text-info"></i>Configured Departments &amp; Programs
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-7 text-nowrap" style="min-width: 550px;">
                    <thead>
                        <tr class="text-body-secondary border-light-subtle">
                            <th>Code</th>
                            <th>Department / Program Title</th>
                            <th class="d-none d-md-table-cell">Description</th>
                            <th class="text-center d-none d-sm-table-cell">Assigned Faculty</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-5">
                                    <i class="fas fa-building-circle-exclamation fa-3x mb-3 text-body-tertiary d-block"></i>
                                    <h5 class="fs-6 fw-bold">No departments found</h5>
                                    <p class="mb-0 fs-7">Click "Add New Department" above to register programs like BS CRIM, BSED, etc.</p>
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
                                            <span class="badge text-bg-success px-2 py-1 fw-bold">Active</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger px-2 py-1 fw-bold">Inactive</span>
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
</div>

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast align-items-center border shadow-lg rounded-3 bg-body text-body" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center p-2">
            <div id="toastIconContainer" class="me-2 fs-5 px-1">
                <i id="toastIcon" class="fas fa-check-circle"></i>
            </div>
            <div class="toast-body d-flex align-items-center fs-7 fw-semibold p-1 text-body" id="toastMessageText">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <button type="button" class="btn-close ms-auto me-1 shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Modal: Add New Department -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 text-body">
                    <i class="fas fa-plus-circle text-primary me-2"></i>Add New Program/Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4 fs-7">
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Department Code / Acronym <span class="text-danger">*</span></label>
                        <input type="text" name="dept_code" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g., BS CRIM, BSED, BSBA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Program / Department Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="dept_name" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="e.g., Bachelor of Science in Criminology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-body-secondary small fw-bold">Description / Notes</label>
                        <textarea name="description" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" rows="3" placeholder="Optional details regarding department scope..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3 fs-7" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold fs-7 px-4">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ==========================================
    // TOAST ALERT LOGIC
    // ==========================================
    const phpMessage = <?= json_encode($message) ?>;
    const messageType = <?= json_encode($messageType) ?>;

    if (phpMessage && phpMessage.trim() !== '') {
        const toastEl = document.getElementById('liveToast');
        const toastIcon = document.getElementById('toastIcon');

        // Customize toast color based on PHP message type
        if (messageType === 'success') {
            toastEl.classList.add('border-success');
            toastIcon.className = 'fas fa-check-circle text-success';
        } else if (messageType === 'warning' || messageType === 'danger') {
            toastEl.classList.add('border-danger');
            toastIcon.className = 'fas fa-exclamation-circle text-danger';
        } else {
            toastEl.classList.add('border-primary');
            toastIcon.className = 'fas fa-info-circle text-primary';
        }

        if (toastEl && window.bootstrap && window.bootstrap.Toast) {
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>