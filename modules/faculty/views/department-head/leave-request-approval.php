<?php

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle = 'Leave Request Approval';
$activeModule = 'faculty';
$activePage = 'leave-request-approval';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Requests', 'url' => null],
];

$leaveRequests = [];
$totalRequests = 0;
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$documentRequiredCount = 0;
$availableLeaveTypes = [];
$leaveUsageData = [];
$facultyGroupedRequests = [];

$formError = '';
$formSuccess = '';

try {

    $pdo = facultyDb();

    if (!$pdo) {
        throw new RuntimeException('Unable to connect to the faculty database.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = trim((string) ($_POST['action'] ?? ''));
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            throw new RuntimeException('Invalid leave request.');
        }

        if (!in_array($action, ['approve', 'reject', 'notify_faculty', 'delete_document'], true)) {
            throw new RuntimeException('Invalid leave request action.');
        }

        $approverId = null;

        if (function_exists('getCurrentUserId')) {
            $approverId = (int) getCurrentUserId();
        }

        if ($approverId <= 0) {
            $approverId = (int) ($_SESSION['user_id'] ?? 0);
        }

        if ($approverId <= 0) {
            throw new RuntimeException('Your account could not be identified. Please log in again.');
        }

        $stmt = $pdo->prepare("
            SELECT id, status, documents, screening_status
            FROM faculty_db.leave_requests
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $requestId
        ]);

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new RuntimeException('The selected leave request could not be found.');
        }

        if (($request['screening_status'] ?? '') !== 'Screened') {
            throw new RuntimeException('This leave request has not been screened by the Secretary yet.');
        }

        $requestStatus = strtolower(trim((string) ($request['status'] ?? '')));
        $requestStatus = str_replace(' ', '_', $requestStatus);

        if ($action === 'notify_faculty') {
            $allowedStatuses = ['pending', 'approved', 'document_required', 'rejected'];
        } elseif ($action === 'delete_document') {
            $allowedStatuses = ['approved', 'document_required', 'pending'];
        } else {
            $allowedStatuses = ['pending', 'document_required'];
        }

        if (!in_array($requestStatus, $allowedStatuses, true)) {
            $statusLabel = ucfirst(str_replace('_', ' ', $requestStatus));
            throw new RuntimeException('This leave request is currently ' . $statusLabel . ' and cannot be processed with that action.');
        }

        $comment = null;

        if ($action === 'reject') {
            $comment = trim((string) ($_POST['comment'] ?? ''));

            if ($comment === '') {
                throw new RuntimeException('Please provide a reason for rejecting the leave request.');
            }
        }

        $columnStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = 'faculty_db'
              AND TABLE_NAME = 'leave_requests'
              AND COLUMN_NAME = 'approver_comment'
        ");

        $columnStmt->execute();

        $hasApproverComment = (int) $columnStmt->fetchColumn() > 0;

        $notificationColumn = null;
        $notificationSetSql = '';

        $notificationColumnStmt = $pdo->prepare("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = 'faculty_db'
                    AND TABLE_NAME = 'leave_requests'
                    AND COLUMN_NAME IN ('notification', 'is_notified', 'notified', 'notification_sent', 'notification_seen', 'notification_read', 'is_seen', 'document_notification', 'notify_flag', 'is_alerted', 'notification_status', 'is_notification')
                LIMIT 1
        ");
        $notificationColumnStmt->execute();
        $notificationColumn = $notificationColumnStmt->fetchColumn();

        if ($notificationColumn) {
            $notificationSetSql = ', ' . $notificationColumn . ' = 1';
        }

        if ($action === 'approve') {

            $hasDocument = trim((string) ($request['documents'] ?? '')) !== '';

            if (!$hasDocument) {
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests
                    SET
                        status = 'Approved',
                        approver_id = :approver_id,
                        approver_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                      AND LOWER(status) IN ('pending', 'document_required')
                ");

                $stmt->bindValue(':approver_id', $approverId, PDO::PARAM_INT);
                $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException('The leave request could not be approved with the missing document notice.');
                }

                $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
                header('Location: ' . $redirectUrl . '?success=' . urlencode('Leave request approved successfully. Faculty may submit the supporting document later.'));
                exit;
            }

            if ($hasApproverComment) {
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests
                    SET
                        status = 'Approved',
                        approver_id = :approver_id,
                        approver_at = NOW(),
                        approver_comment = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                      AND LOWER(status) IN ('pending', 'document_required')
                ");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests
                    SET
                        status = 'Approved',
                        approver_id = :approver_id,
                        approver_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                      AND LOWER(status) IN ('pending', 'document_required')
                ");
            }

            $stmt->bindValue(':approver_id', $approverId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The leave request could not be approved.');
            }

        } elseif ($action === 'notify_faculty') {

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    status = 'Approved',
                    updated_at = NOW()
                    " . $notificationSetSql . "
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'approved', 'document_required', 'rejected')
            ");

            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The faculty could not be notified for the missing document.');
            }

        } elseif ($action === 'delete_document') {

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    documents = NULL,
                    status = 'Approved',
                    updated_at = NOW()
                    " . $notificationSetSql . "
                WHERE id = :id
                  AND LOWER(status) IN ('approved', 'document_required', 'pending')
            ");

            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The uploaded document could not be removed.');
            }

        } else {

            if ($hasApproverComment) {
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests
                    SET
                        status = 'Rejected',
                        approver_id = :approver_id,
                        approver_at = NOW(),
                        approver_comment = :comment,
                        updated_at = NOW()
                        " . $notificationSetSql . "
                    WHERE id = :id
                      AND LOWER(status) IN ('pending', 'document_required')
                ");

                $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests
                    SET
                        status = 'Rejected',
                        approver_id = :approver_id,
                        approver_at = NOW(),
                        updated_at = NOW()
                        " . $notificationSetSql . "
                    WHERE id = :id
                      AND LOWER(status) IN ('pending', 'document_required')
                ");
            }

            $stmt->bindValue(':approver_id', $approverId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The leave request could not be rejected.');
            }
        }

        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');

        $successText = match ($action) {
            'approve' => 'Leave request approved successfully.',
            'notify_faculty' => 'Faculty has been notified to submit the required document.',
            'delete_document' => 'The uploaded document was removed and the faculty has been notified to resubmit it.',
            default => 'Leave request rejected successfully.',
        };

        header('Location: ' . $redirectUrl . '?success=' . urlencode($successText));
        exit;
    }

    if (isset($_GET['success'])) {
        $formSuccess = trim((string) $_GET['success']);
    }

    $countStmt = $pdo->prepare(
        "SELECT LOWER(status) AS status, COUNT(*) AS cnt FROM faculty_db.leave_requests GROUP BY LOWER(status)"
    );

    $countStmt->execute();

    $counts = [];

    while ($r = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $statusKey = (string) ($r['status'] ?? '');
        $counts[$statusKey] = (int) ($r['cnt'] ?? 0);
    }

    $pendingCount = $counts['pending'] ?? 0;
    $approvedCount = $counts['approved'] ?? 0;
    $rejectedCount = $counts['rejected'] ?? 0;
    $documentRequiredCount = $counts['document required'] ?? $counts['document_required'] ?? 0;
    $totalRequests = array_sum($counts);

    $typeStmt = $pdo->query("SELECT DISTINCT leave_type FROM faculty_db.leave_requests WHERE leave_type IS NOT NULL AND leave_type != '' ORDER BY leave_type ASC");
    $dbLeaveTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $defaultLeaveTypes = ['Vacation Leave', 'Sick Leave', 'Emergency Leave', 'Study Leave'];
    $availableLeaveTypes = array_unique(array_merge($defaultLeaveTypes, $dbLeaveTypes));
    sort($availableLeaveTypes);

    $usageStmt = $pdo->query("
        SELECT faculty_id, SUM(DATEDIFF(end_date, start_date) + 1) AS total_used_days
        FROM faculty_db.leave_requests
        WHERE LOWER(status) IN ('approved', 'finished') AND faculty_id IS NOT NULL
        GROUP BY faculty_id
    ");
    while ($row = $usageStmt->fetch(PDO::FETCH_ASSOC)) {
        $fId = (int) $row['faculty_id'];
        $days = (int) $row['total_used_days'];
        $leaveUsageData[$fId] = $days;
    }

    $sql = "SELECT lr.*, fp.id AS faculty_profile_id, fp.user_id AS faculty_user_id, fp.faculty_id AS faculty_identifier, CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name, DATEDIFF(lr.end_date, lr.start_date) + 1 AS days FROM faculty_db.leave_requests lr LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id WHERE lr.screening_status = 'Screened' ORDER BY lr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($leaveRequests as $req) {
        $fId = (int) ($req['faculty_profile_id'] ?? 0);
        if ($fId <= 0) continue;

        if (!isset($facultyGroupedRequests[$fId])) {
            $facultyGroupedRequests[$fId] = [
                'faculty_profile_id' => $fId,
                'faculty_name' => $req['faculty_name'] ?? 'Unknown Faculty',
                'faculty_identifier' => $req['faculty_identifier'] ?? '',
                'requests' => []
            ];
        }
        $facultyGroupedRequests[$fId]['requests'][] = $req;
    }

} catch (Throwable $e) {

    $formError = $e->getMessage();

    error_log(
        '[leave-request-approval] ' .
        $e->getMessage() .
        PHP_EOL .
        $e->getTraceAsString()
    );
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<style>
/* Responsive Fixes & Prevent Text Stacking/Wrapping */
.table th, .table td {
    white-space: nowrap !important;
}

@media (max-width: 576px) {
    body {
        font-size: 0.85rem;
    }
    .h3, h3 {
        font-size: 1.25rem;
    }
    .h5, h5 {
        font-size: 1rem;
    }
    .table th, .table td {
        padding: 0.5rem 0.5rem;
        font-size: 0.8rem;
    }
    .modal-dialog {
        margin: 0.5rem;
    }
    .btn-sm {
        padding: 0.2rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-plane-departure text-primary me-2"></i>
            Leave Request Approval
        </h1>

        <p class="text-body-secondary mb-0 small">
            Review and approve or reject submitted leave requests grouped by faculty account
        </p>
    </div>
</div>
<!-- Metric Summary Cards -->
<div class="row g-3 mb-3">
    <!-- Pending Requests Card -->
    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100" style="border-left-color: var(--bs-warning) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-warning fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Pending Requests</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $pendingCount ?></h4>
                    <small class="text-warning fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-hourglass-half me-1"></i>Awaiting Screening/Approval
                    </small>
                </div>
            </div>
        </section>
    </div>

    <!-- Approved Card -->
    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100" style="border-left-color: var(--bs-success) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-success fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Approved</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $approvedCount ?></h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-thumbs-up me-1"></i>Successfully Processed
                    </small>
                </div>
            </div>
        </section>
    </div>

    <!-- Rejected Card -->
    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100" style="border-left-color: var(--bs-danger) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-danger fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Rejected</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $rejectedCount ?></h4>
                    <small class="text-danger fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-ban me-1"></i>Declined Requests
                    </small>
                </div>
            </div>
        </section>
    </div>
</div>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Automatic Search / Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-body-secondary">Search</label>
                <input type="search" id="liveSearchInput" class="form-control form-control-sm" placeholder="Search by faculty name or reference">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-body-secondary">Leave Type</label>
                <select id="liveTypeFilter" class="form-select form-select-sm">
                    <option value="">All Leave Types</option>
                    <?php foreach ($availableLeaveTypes as $lType): ?>
                        <option value="<?= htmlspecialchars($lType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lType, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-body-secondary">Status</label>
                <select id="liveStatusFilter" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="document_required">Awaiting Docs</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-body-tertiary py-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

            <h6 class="mb-0 fw-bold text-body">
                <i class="fas fa-list-check text-primary me-2"></i>
                Faculty Leave Accounts
                <span class="text-body-secondary fw-normal">
                    (<?= count($facultyGroupedRequests) ?> accounts)
                </span>
            </h6>

            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2">
                <?= $pendingCount ?> Pending Requests
            </span>

        </div>
    </div>

    <div class="card-body p-0">

        <!-- Responsive Table Container -->
        <div class="table-responsive">

            <table class="table align-middle table-hover mb-0" id="leaveRequestsTable">

                <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary text-nowrap">

                    <tr>
                        <th class="ps-3 py-3" style="width: 40px;">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="selectAll"
                            >
                        </th>

                        <th class="py-3">Faculty Member</th>
                        <th class="py-3 text-center">Total Requests</th>
                        <th class="py-3 text-center">Pending Review</th>
                        <th class="text-end pe-3 py-3">Actions</th>
                    </tr>

                </thead>

                <tbody class="text-body">

                <?php if (empty($facultyGroupedRequests)): ?>

                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-5">
                            <i class="fas fa-inbox fs-3 d-block mb-2 text-body-tertiary"></i>
                            <span>No leave requests found.</span>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($facultyGroupedRequests as $fId => $group): ?>

                        <?php
                        $facultyName = $group['faculty_name'];
                        $requests = $group['requests'];
                        $totalFacultyRequests = count($requests);
                        
                        $pendingFacultyRequests = 0;
                        $allRefs = [];
                        $allTypes = [];
                        $allStatuses = [];

                        foreach ($requests as $r) {
                            $stRaw = strtolower(trim($r['status'] ?? 'pending'));
                            if ($stRaw === 'pending' || $stRaw === 'document required' || $stRaw === 'document_required') {
                                if ($stRaw === 'pending') {
                                    $pendingFacultyRequests++;
                                }
                            }
                            $allRefs[] = strtolower($r['request_ref'] ?? ('lr-' . $r['id']));
                            $allTypes[] = strtolower($r['leave_type'] ?? '');
                            $allStatuses[] = $stRaw;
                        }

                        $encodedGroup = htmlspecialchars(
                            json_encode(
                                $group,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_AMP |
                                JSON_HEX_QUOT
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                        <tr class="align-middle faculty-row" 
                            data-name="<?= htmlspecialchars(strtolower($facultyName), ENT_QUOTES, 'UTF-8') ?>" 
                            data-refs="<?= htmlspecialchars(implode(' ', $allRefs), ENT_QUOTES, 'UTF-8') ?>" 
                            data-types="<?= htmlspecialchars(implode(',', $allTypes), ENT_QUOTES, 'UTF-8') ?>" 
                            data-statuses="<?= htmlspecialchars(implode(',', $allStatuses), ENT_QUOTES, 'UTF-8') ?>">

                            <td class="ps-3 py-3">
                                <input
                                    type="checkbox"
                                    class="form-check-input row-select"
                                    value="<?= $fId ?>"
                                >
                            </td>

                            <!-- Faculty Member -->
                            <td class="py-3">
                                <div class="d-flex align-items-center">
                                    <div
                                        class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold flex-shrink-0"
                                        style="width: 36px; height: 36px;"
                                    >
                                        <?= htmlspecialchars(
                                            strtoupper(substr($facultyName, 0, 1)),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>

                                    <div>
                                        <strong class="text-body-emphasis d-block text-nowrap">
                                            <?= htmlspecialchars($facultyName, ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                        <small class="d-block text-body-secondary font-monospace text-nowrap">
                                            ID: <?= htmlspecialchars($group['faculty_identifier'] ?: $fId, ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 text-center">
                                <span class="fw-bold text-body bg-body-tertiary border rounded px-2 py-1">
                                    <?= $totalFacultyRequests ?>
                                </span>
                            </td>

                            <td class="py-3 text-center">
                                <?php if ($pendingFacultyRequests > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">
                                        <?= $pendingFacultyRequests ?> Pending
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                        All Processed
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end pe-3 py-3 text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm hover-elevate d-inline-flex align-items-center gap-2 text-nowrap"
                                    onclick='showFacultyRequestsModal(<?= $encodedGroup ?>)'
                                >
                                    <span class="d-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle flex-shrink-0" style="width: 22px; height: 22px;">
                                        <i class="fas fa-eye text-white" style="font-size: 0.75rem;"></i>
                                    </span>
                                    <span>View Leave Profile</span>
                                    <?php if ($pendingFacultyRequests > 0): ?>
                                        <span class="badge bg-light text-primary ms-1 rounded-pill"><?= $pendingFacultyRequests ?></span>
                                    <?php endif; ?>
                                </button>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- Faculty Requests & Balance Modal -->
<div class="modal fade" id="facultyRequestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="p-2 bg-primary-subtle text-primary rounded me-2">
                        <i class="fas fa-user-clock fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0">
                            Leave Requests & Balance: <span id="modal-faculty-title-name">-</span>
                        </h5>
                        <span class="small text-body-secondary">
                            Account Leave Summary and Requests
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                
                <!-- Leave Balance Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <h6 class="fw-bold text-body mb-0">
                            <i class="fas fa-chart-pie text-primary me-2"></i> Semester Leave Balance Pool
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 small fw-semibold">
                            Shared Limit: 7 Days Total per Semester
                        </span>
                    </div>
                    <div class="p-3 border rounded bg-body-tertiary">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-8">
                                <div class="d-flex justify-content-between mb-1 small fw-semibold text-body">
                                    <span>Total Used Across All Leave Types</span>
                                    <span id="modal-pool-text">0 / 7 Days</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div id="modal-pool-progress" class="progress-bar bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="7"></div>
                                </div>
                                <small class="text-muted d-block mt-1">Includes approved leaves from all categories (Sick, Vacation, Emergency, Study). Max limit is 7 days total per semester across all leave types.</small>
                            </div>
                            <div class="col-12 col-md-4 text-md-end text-center">
                                <div class="p-2 border rounded bg-white shadow-sm d-inline-block text-center px-4">
                                    <span class="d-block small text-muted text-uppercase fw-bold">Remaining Pool</span>
                                    <h3 class="mb-0 fw-bold text-primary" id="modal-pool-remaining">7 Days</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Leave Requests List Section -->
                <div>
                    <h6 class="fw-bold text-body mb-3">
                        <i class="fas fa-list text-primary me-2"></i> Submitted Leave Requests
                    </h6>
                    <!-- Added table-responsive container to prevent character stacking -->
                    <div class="table-responsive">
                        <table class="table align-middle table-bordered mb-0 text-nowrap" id="modal-requests-table">
                            <thead class="table-light small text-uppercase fw-bold text-body-secondary">
                                <tr>
                                    <th>Ref / Date</th>
                                    <th>Leave Type</th>
                                    <th>Duration / Days</th>
                                    <th>Reason & Docs</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-requests-tbody">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Document Viewer Modal (In-App PDF & Image Viewer / Auto-Download for Office Docs) -->
<div class="modal fade" id="documentViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 90vw; height: 85vh;">
        <div class="modal-content border-0 shadow h-100">
            <div class="modal-header border-bottom py-3 bg-body-tertiary">
                <div class="d-flex align-items-center">
                    <div class="p-2 bg-info-subtle text-info rounded me-2">
                        <i class="fas fa-file-alt fs-5" id="viewer-file-icon"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0" id="viewer-modal-title">Supporting Document Viewer</h5>
                        <span class="small text-body-secondary" id="viewer-file-subtitle">Viewing attached document</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="viewer-download-btn" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" download>
                        <i class="fas fa-download"></i> Download File
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-dark d-flex align-items-center justify-content-center position-relative" style="min-height: 70vh; overflow: hidden;">
                <!-- Container for displaying PDFs, images or fallback notice -->
                <div id="viewer-content-container" class="w-100 h-100 d-flex align-items-center justify-content-center">
                    <!-- Dynamic Content inserted via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Single Leave Detail Review Modal (for viewing reason/docs in detail) -->
<div class="modal fade" id="proposalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="p-2 bg-primary-subtle text-primary rounded me-2">
                        <i class="fas fa-plane-departure fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0">Leave Request Details</h5>
                        <span class="small text-body-secondary">Submitted via Portal</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="p-3 bg-body-tertiary rounded border mb-4">
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">Faculty</span>
                            <strong class="text-body" id="detail-modal-faculty-name">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">Filed Date</span>
                            <strong class="text-body" id="detail-modal-filed-date">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">Leave Type</span>
                            <strong class="text-info" id="detail-modal-leave-type">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">Duration</span>
                            <strong class="text-body" id="detail-modal-duration">-</strong>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-body-secondary small">Reason Provided</label>
                    <div class="bg-body-tertiary p-3 rounded-3 border text-break text-wrap text-body" id="detail-modal-reason">-</div>
                </div>
                <div>
                    <label class="form-label fw-semibold text-body-secondary small">Attached Documents</label>
                    <div class="d-flex flex-wrap gap-2" id="detail-modal-docs-container">
                        <span class="small text-body-secondary">No attachments</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i> Reject Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <p class="text-body-secondary small mb-3">Please provide a reason for rejecting this leave request.</p>
                <label for="reject-reason" class="form-label fw-semibold text-body small">Reason for Rejection <span class="text-danger">*</span></label>
                <textarea id="reject-reason" class="form-control text-body" rows="5" maxlength="1000" placeholder="Enter the reason for rejection..."></textarea>
                <div class="small text-body-secondary text-end mt-1">Maximum 1000 characters</div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">
                    <i class="fas fa-times me-1"></i> Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</div>

<script>

const BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
const leaveUsageData = <?= json_encode($leaveUsageData) ?>;

function submitAction(action, id, comment = '') {
    if (!id) {
        alert('Invalid leave request.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    form.style.display = 'none';

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'request_id';
    idInput.value = id;

    form.appendChild(actionInput);
    form.appendChild(idInput);

    if (comment !== '') {
        const commentInput = document.createElement('input');
        commentInput.type = 'hidden';
        commentInput.name = 'comment';
        commentInput.value = comment;
        form.appendChild(commentInput);
    }

    document.body.appendChild(form);
    form.submit();
}

function showFacultyRequestsModal(group) {
    document.getElementById('modal-faculty-title-name').textContent = group.faculty_name;

    const totalUsed = leaveUsageData[group.faculty_profile_id] || 0;
    const maxLimit = 7;
    const remainingDays = Math.max(0, maxLimit - totalUsed);
    const percentage = Math.min(100, Math.round((totalUsed / maxLimit) * 100));

    document.getElementById('modal-pool-text').textContent = `${totalUsed} / ${maxLimit} Days`;
    document.getElementById('modal-pool-remaining').textContent = `${remainingDays} Day${remainingDays === 1 ? '' : 's'}`;
    
    const progressBar = document.getElementById('modal-pool-progress');
    progressBar.style.width = `${percentage}%`;
    progressBar.setAttribute('aria-valuenow', totalUsed);
    
    if (totalUsed > maxLimit) {
        progressBar.className = 'progress-bar bg-danger';
    } else if (totalUsed >= maxLimit) {
        progressBar.className = 'progress-bar bg-warning';
    } else {
        progressBar.className = 'progress-bar bg-primary';
    }

    const tbody = document.getElementById('modal-requests-tbody');
    tbody.innerHTML = '';

    if (!group.requests || group.requests.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No requests found for this faculty member.</td></tr>`;
    } else {
        group.requests.forEach(req => {
            const reqId = req.id;
            const ref = req.request_ref || ('LR-' + reqId);
            const type = req.leave_type || '';
            const startDate = req.start_date || '';
            const endDate = req.end_date || '';
            const days = req.days || 0;
            const reason = req.reason || '';
            const statusRaw = (req.status || 'pending').toLowerCase();
            const statusDisplay = statusRaw === 'document required' || statusRaw === 'document_required' ? 'Awaiting Docs' : ucfirstStatus(statusRaw);

            let statusClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            if (statusRaw === 'approved') statusClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
            else if (statusRaw === 'rejected') statusClass = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
            else if (statusRaw === 'document_required' || statusRaw === 'document required') statusClass = 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';

            const resolvedDocUrl = resolveDocumentPath(req.documents);

            let docHtml = '<span class="text-muted small">No doc</span>';
            if (resolvedDocUrl) {
                docHtml = `
                    <div class="mt-1">
                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 font-monospace text-nowrap" onclick="openDocumentViewer('${encodeURIComponent(resolvedDocUrl)}')">
                            <i class="fas fa-eye me-1"></i>View Doc
                        </button>
                    </div>
                `;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <strong class="font-monospace text-body">${ref}</strong>
                    <small class="d-block text-muted font-monospace">${req.created_at || ''}</small>
                </td>
                <td><span class="badge bg-primary bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">${type}</span></td>
                <td>
                    <div class="font-monospace small text-nowrap">${startDate} &rarr; ${endDate}</div>
                    <span class="badge bg-light text-dark border mt-1">${days} day(s)</span>
                </td>
                <td>
                    <div class="small text-truncate" style="max-width: 200px;" title="${escapeHtml(reason)}">${escapeHtml(reason || 'No reason provided')}</div>
                    ${docHtml}
                </td>
                <td><span class="badge ${statusClass} px-2 py-1">${statusDisplay}</span></td>
                <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick='viewSingleDetail(${JSON.stringify(req)}, ${JSON.stringify(group.faculty_name)})' title="View Details"><i class="fas fa-search"></i></button>
                        ${(statusRaw === 'pending' || statusRaw === 'document_required' || statusRaw === 'document required') ? `
                            <button type="button" class="btn btn-outline-success" onclick="approveRequest(${reqId})" title="Approve"><i class="fas fa-check"></i></button>
                            <button type="button" class="btn btn-outline-danger" onclick="rejectRequest(${reqId})" title="Reject"><i class="fas fa-times"></i></button>
                        ` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('facultyRequestsModal')).show();
}

function resolveDocumentPath(docs) {
    if (!docs) return null;
    let path = null;
    if (typeof docs === 'string') {
        try {
            const parsed = JSON.parse(docs);
            if (Array.isArray(parsed) && parsed.length > 0) {
                const last = parsed[parsed.length - 1];
                path = (typeof last === 'string') ? last : (last && last.file ? last.file : String(last));
            } else if (typeof parsed === 'string') {
                path = parsed;
            }
        } catch (e) { path = docs; }
    } else if (Array.isArray(docs)) {
        const last = docs[docs.length - 1];
        path = (typeof last === 'string') ? last : (last && last.file ? last.file : String(last));
    } else {
        path = String(docs);
    }
    if (!path) return null;
    if (/^(https?:)?\/\//i.test(path) || path.charAt(0) === '/') return path;
    return BASE_URL + '/' + path.replace(/^\/+/, '');
}

function openDocumentViewer(encodedUrl) {
    const url = decodeURIComponent(encodedUrl);
    const filename = url.split('/').pop().split('?')[0] || 'document';
    const ext = filename.split('.').pop().toLowerCase();

    const downloadBtn = document.getElementById('viewer-download-btn');
    downloadBtn.href = url;
    downloadBtn.setAttribute('download', filename);

    document.getElementById('viewer-modal-title').textContent = filename;
    document.getElementById('viewer-file-subtitle').textContent = `File type: .${ext.toUpperCase()}`;

    const container = document.getElementById('viewer-content-container');
    container.innerHTML = '';

    const iconEl = document.getElementById('viewer-file-icon');
    
    if (ext === 'pdf') {
        iconEl.className = 'fas fa-file-pdf fs-5 text-danger';
        const iframe = document.createElement('iframe');
        iframe.src = url + '#view=FitH';
        iframe.className = 'w-100 h-100 border-0';
        container.appendChild(iframe);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('documentViewerModal')).show();
    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
        iconEl.className = 'fas fa-file-image fs-5 text-success';
        const img = document.createElement('img');
        img.src = url;
        img.className = 'img-fluid mw-100 mh-100 object-fit-contain rounded';
        container.appendChild(img);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('documentViewerModal')).show();
    } else {
        iconEl.className = 'fas fa-file-word fs-5 text-primary';
        
        const hiddenLink = document.createElement('a');
        hiddenLink.href = url;
        hiddenLink.download = filename;
        document.body.appendChild(hiddenLink);
        hiddenLink.click();
        document.body.removeChild(hiddenLink);

        container.innerHTML = `
            <div class="text-center p-5 text-white">
                <div class="mb-3 text-warning fs-1"><i class="fas fa-file-download"></i></div>
                <h4 class="fw-bold mb-2">Document Automatically Downloaded</h4>
                <p class="text-light mb-4 small">Files with extension <strong>.${ext.toUpperCase()}</strong> cannot be previewed directly in the browser and have started downloading automatically.</p>
                <a href="${url}" class="btn btn-primary px-4" download="${filename}">
                    <i class="fas fa-download me-2"></i>Download Again
                </a>
            </div>
        `;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('documentViewerModal')).show();
    }
}

function ucfirstStatus(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function viewSingleDetail(req, facultyName) {
    document.getElementById('detail-modal-faculty-name').textContent = facultyName;
    document.getElementById('detail-modal-filed-date').textContent = req.created_at || '-';
    document.getElementById('detail-modal-leave-type').textContent = req.leave_type || '-';
    document.getElementById('detail-modal-duration').textContent = (req.start_date && req.end_date) ? (req.start_date + ' - ' + req.end_date) : '-';
    document.getElementById('detail-modal-reason').textContent = req.reason || '-';

    const docsContainer = document.getElementById('detail-modal-docs-container');
    docsContainer.innerHTML = '';

    const resolved = resolveDocumentPath(req.documents);
    if (resolved) {
        const parts = resolved.split('/');
        const fileName = parts[parts.length - 1] || 'document';
        const viewBtn = document.createElement('button');
        viewBtn.type = 'button';
        viewBtn.className = 'btn btn-sm btn-outline-info';
        viewBtn.innerHTML = `<i class="fas fa-eye me-1"></i> ${fileName}`;
        viewBtn.onclick = function() {
            openDocumentViewer(encodeURIComponent(resolved));
        };
        docsContainer.appendChild(viewBtn);
    } else {
        docsContainer.innerHTML = '<span class="small text-body-secondary">No attachments</span>';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('proposalModal')).show();
}

function approveRequest(id) {
    if (!id) return;
    if (!confirm('Approve this leave request?')) return;
    submitAction('approve', id);
}

function rejectRequest(id) {
    if (!id) return;
    document.getElementById('rejectModal').dataset.requestId = id;
    document.getElementById('reject-reason').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectModal')).show();
}

function confirmReject() {
    const modal = document.getElementById('rejectModal');
    const id = modal.dataset.requestId;
    const reason = document.getElementById('reject-reason').value.trim();

    if (!id || !reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    if (!confirm('Reject this leave request?')) return;
    submitAction('reject', id, reason);
}

document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-select').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    const searchInput = document.getElementById('liveSearchInput');
    const typeFilter = document.getElementById('liveTypeFilter');
    const statusFilter = document.getElementById('liveStatusFilter');
    const rows = document.querySelectorAll('.faculty-row');

    function filterTable() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedType = (typeFilter.value || '').toLowerCase();
        const selectedStatus = (statusFilter.value || '').toLowerCase();

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const refs = row.getAttribute('data-refs') || '';
            const types = row.getAttribute('data-types') || '';
            const statuses = row.getAttribute('data-statuses') || '';

            const matchesSearch = query === '' || name.includes(query) || refs.includes(query);
            const matchesType = selectedType === '' || types.includes(selectedType);
            const matchesStatus = selectedStatus === '' || statuses.includes(selectedStatus);

            if (matchesSearch && matchesType && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (typeFilter) typeFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
});

</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>