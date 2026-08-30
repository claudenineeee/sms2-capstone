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
$finishedCount = 0;
$documentRequiredCount = 0;

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

        if (!in_array($action, ['approve', 'reject', 'finish', 'notify_faculty', 'delete_document'], true)) {
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
        } elseif ($action === 'finish') {
            $allowedStatuses = ['approved', 'document_required', 'pending'];
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

        } elseif ($action === 'finish') {

            $hasDocument = trim((string) ($request['documents'] ?? '')) !== '';

            if (!$hasDocument) {
                throw new RuntimeException('You cannot finish this leave request because no supporting document was submitted.');
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    status = 'Finished',
                    approver_id = :approver_id,
                    approver_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
                  AND LOWER(status) IN ('approved', 'pending', 'document_required')
            ");

            $stmt->bindValue(':approver_id', $approverId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The leave request could not be finished.');
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
            'finish' => 'Leave request marked as finished successfully.',
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
    $finishedCount = $counts['finished'] ?? 0;
    $documentRequiredCount = $counts['document required'] ?? $counts['document_required'] ?? 0;
    $totalRequests = array_sum($counts);

    $q = trim((string) ($_GET['q'] ?? ''));
    $filterType = trim((string) ($_GET['type'] ?? ''));
    $filterStatus = trim((string) ($_GET['status'] ?? ''));

    $where = [];
    $params = [];

    $where[] = "lr.screening_status = 'Screened'";

    if ($q !== '') {
        $where[] = "(CONCAT_WS(' ', fp.first_name, fp.last_name) LIKE :q_name OR lr.request_ref LIKE :q_ref)";
        $params[':q_name'] = '%' . $q . '%';
        $params[':q_ref'] = '%' . $q . '%';
    }

    if ($filterType !== '') {
        $where[] = 'lr.leave_type = :leave_type';
        $params[':leave_type'] = $filterType;
    }

    if ($filterStatus !== '') {
        $where[] = 'LOWER(lr.status) = :status';
        $params[':status'] = strtolower($filterStatus);
    }

    $sql = "SELECT lr.*, fp.id AS faculty_profile_id, fp.user_id AS faculty_user_id, fp.faculty_id AS faculty_identifier, CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name, DATEDIFF(lr.end_date, lr.start_date) + 1 AS days FROM faculty_db.leave_requests lr LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id";

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY CASE WHEN LOWER(lr.status) = 'pending' THEN 0 WHEN LOWER(lr.status) = 'approved' THEN 1 WHEN LOWER(lr.status) = 'rejected' THEN 2 ELSE 3 END, lr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->execute();

    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

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

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-plane-departure text-primary me-2"></i>
            Leave Request Approval
        </h1>

        <p class="text-body-secondary mb-0 small">
            Review and approve or reject submitted leave requests
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
            <a href="?status=pending" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle text-decoration-none" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Pending Requests">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
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
            <a href="?status=approved" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle text-decoration-none" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Approved Requests">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
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
            <a href="?status=rejected" class="position-absolute top-0 end-0 m-3 text-body-secondary border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle text-decoration-none" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Rejected Requests">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
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

<!-- Search / Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-body-secondary">Search</label>
                <input type="search" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm" placeholder="Search by faculty name">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-body-secondary">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="document_required" <?= (isset($_GET['status']) && $_GET['status'] === 'document_required') ? 'selected' : '' ?>>Awaiting Docs</option>
                    <option value="approved" <?= (isset($_GET['status']) && $_GET['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="finished" <?= (isset($_GET['status']) && $_GET['status'] === 'finished') ? 'selected' : '' ?>>Finished</option>
                    <option value="rejected" <?= (isset($_GET['status']) && $_GET['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-body-tertiary py-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

            <h6 class="mb-0 fw-bold text-body">
                <i class="fas fa-list-check text-primary me-2"></i>
                Leave Requests
                <span class="text-body-secondary fw-normal">
                    (<?= $totalRequests ?>)
                </span>
            </h6>

            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2">
                <?= $pendingCount ?> Pending
            </span>

        </div>
    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table align-middle table-hover mb-0 text-nowrap">

                <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary">

                    <tr>
                        <th class="ps-3 py-3">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="selectAll"
                            >
                        </th>

                        <th class="py-3">Faculty Member</th>
                        <th class="py-3">Type</th>
                        <th class="py-3">Duration</th>
                        <th class="py-3 text-center">Days</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Filed Date</th>
                        <th class="text-end pe-3 py-3">Actions</th>
                    </tr>

                </thead>

                <tbody class="text-body">

                <?php if (empty($leaveRequests)): ?>

                    <tr>
                        <td colspan="8" class="text-center text-body-secondary py-5">
                            <i class="fas fa-inbox fs-3 d-block mb-2 text-body-tertiary"></i>
                            <span>No leave requests found.</span>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($leaveRequests as $request): ?>

                        <?php

                        $id = (int) ($request['id'] ?? 0);

                        $requestRef = (string) (
                            $request['request_ref'] ??
                            ('LR-' . $id)
                        );

                        $facultyName = trim(
                            (string) ($request['faculty_name'] ?? '')
                        );

                        if ($facultyName === '') {
                            $facultyName = 'Unknown Faculty';
                        }

                        $leaveType = (string) (
                            $request['leave_type'] ?? ''
                        );

                        $startDate = (string) (
                            $request['start_date'] ?? ''
                        );

                        $endDate = (string) (
                            $request['end_date'] ?? ''
                        );

                        $days = (int) (
                            $request['days'] ?? 0
                        );

                        $statusRaw = strtolower(
                            trim((string) ($request['status'] ?? 'Pending'))
                        );

                        if ($statusRaw === 'document required') {
                            $statusRaw = 'document_required';
                        }

                        $status = ucfirst(str_replace('_', ' ', $statusRaw));

                        $createdAt = (string) (
                            $request['created_at'] ?? ''
                        );

                        $reason = (string) (
                            $request['reason'] ?? ''
                        );

                        $documents = (string) (
                            $request['documents'] ?? ''
                        );

                        $dataObj = [
                            'id' => $id,
                            'ref' => $requestRef,
                            'faculty' => $facultyName,
                            'type' => $leaveType,
                            'start' => $startDate,
                            'end' => $endDate,
                            'days' => $days,
                            'status' => $status,
                            'date' => $createdAt,
                            'reason' => $reason,
                            'documents' => $documents,
                        ];

                        $statusClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                        $statusIcon = 'fa-solid fa-clock';

                        if ($statusRaw === 'approved') {
                            $statusClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                            $statusIcon = 'fa-solid fa-circle-check';
                        } elseif ($statusRaw === 'rejected') {
                            $statusClass = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
                            $statusIcon = 'fa-solid fa-circle-xmark';
                        } elseif ($statusRaw === 'finished') {
                            $statusClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                            $statusIcon = 'fa-solid fa-circle-check';
                        } elseif ($statusRaw === 'document_required') {
                            $statusClass = 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
                            $statusIcon = 'fa-solid fa-circle-exclamation';
                        }

                        $encodedData = htmlspecialchars(
                            json_encode(
                                $dataObj,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_AMP |
                                JSON_HEX_QUOT
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                        <tr class="align-middle">

                            <td class="ps-3 py-3">

                                <?php if ($statusRaw === 'pending'): ?>

                                    <input
                                        type="checkbox"
                                        class="form-check-input row-select"
                                        value="<?= $id ?>"
                                    >

                                <?php endif; ?>

                            </td>

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

                                        <strong class="text-body-emphasis d-block">
                                            <?= htmlspecialchars(
                                                $facultyName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                        <small class="d-block text-body-secondary font-monospace">
                                            <?= htmlspecialchars(
                                                $requestRef,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                            </td>

                            <!-- High Contrast Type Badge -->
                            <td class="py-3">

                                <span class="badge bg-primary bg-opacity-10 text-info border border-info border-opacity-25 rounded-3 px-3 py-2 fw-semibold">
                                    <?= htmlspecialchars(
                                        $leaveType,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </td>

                            <td class="py-3">

                                <div class="d-inline-flex align-items-center text-body font-monospace">
                                    <span><?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?></span>
                                    <i class="fas fa-arrow-right mx-2 text-body-tertiary"></i>
                                    <span><?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>

                            </td>

                            <td class="py-3 text-center">
                                <span class="fw-bold text-body bg-body-tertiary border rounded px-2 py-1">
                                    <?= $days ?>
                                </span>
                            </td>

                            <td class="py-3 text-center">

                                <span class="badge <?= $statusClass ?> rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center gap-1">
                                    <?php if ($statusRaw === 'pending'): ?>
                                        <i class="<?= $statusIcon ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars(
                                        $statusRaw === 'pending' ? 'Pending Approval' : $status,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </td>

                            <td class="py-3">

                                <small class="text-body-secondary font-monospace d-block">
                                    <?= htmlspecialchars(
                                        $createdAt,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </td>

                            <td class="text-end pe-3 py-3">

                                <div class="btn-group btn-group-sm">

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        onclick='viewDetails(<?= $encodedData ?>)'
                                    >
                                        <i class="fas fa-search me-1"></i>
                                        Review
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown"
                                    >
                                        <span class="visually-hidden">
                                            Toggle Dropdown
                                        </span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                        <?php if ($statusRaw === 'pending' || $statusRaw === 'document_required'): ?>

                                            <?php if (empty(trim((string) ($request['documents'] ?? ''))) || $statusRaw === 'document_required'): ?>
                                                <li>
                                                    <button type="button" class="dropdown-item text-primary" onclick="notifyFaculty(<?= $id ?>)">
                                                        <i class="fas fa-bell me-2"></i> Notify Faculty
                                                    </button>
                                                </li>
                                            <?php endif; ?>

                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-success"
                                                    onclick="approveRequest(<?= $id ?>)"
                                                >
                                                    <i class="fas fa-check me-2"></i>
                                                    Approve
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-danger"
                                                    onclick="rejectRequest(<?= $id ?>)"
                                                >
                                                    <i class="fas fa-times me-2"></i>
                                                    Reject
                                                </button>
                                            </li>

                                        <?php elseif ($statusRaw === 'approved' || $statusRaw === 'document_required'): ?>

                                            <?php if (!empty(trim((string) ($request['documents'] ?? '')))): ?>
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-info"
                                                        onclick="finishRequest(<?= $id ?>)"
                                                    >
                                                        <i class="fas fa-check-double me-2"></i>
                                                        Finish Leave
                                                    </button>
                                                </li>
                                            <?php endif; ?>

                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-warning"
                                                    onclick="deleteDocument(<?= $id ?>)"
                                                >
                                                    <i class="fas fa-trash me-2"></i>
                                                    Delete Document
                                                </button>
                                            </li>

                                        <?php elseif ($statusRaw === 'rejected'): ?>

                                            <li>
                                                <span class="dropdown-item-text text-body-secondary small">
                                                    Rejected requests are view-only
                                                </span>
                                            </li>

                                        <?php else: ?>

                                            <li>
                                                <span class="dropdown-item-text text-body-secondary small">
                                                    Request already processed
                                                </span>
                                            </li>

                                        <?php endif; ?>

                                    </ul>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- Leave Details Modal -->
<div class="modal fade" id="proposalModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content border-0 shadow">

            <div class="modal-header border-bottom py-3">

                <div class="d-flex align-items-center">

                    <div class="p-2 bg-primary-subtle text-primary rounded me-2">
                        <i class="fas fa-plane-departure fs-5"></i>
                    </div>

                    <div>

                        <h5 class="modal-title fw-bold text-body mb-0">
                            Leave Request Details
                        </h5>

                        <span class="small text-body-secondary">
                            Submitted via Portal
                        </span>

                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <div class="modal-body p-3 p-md-4">

                <div class="p-3 bg-body-tertiary rounded border mb-4">

                    <div class="row g-3">

                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">
                                Faculty
                            </span>
                            <strong class="text-body" id="modal-faculty-name">
                                -
                            </strong>
                        </div>

                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">
                                Filed Date
                            </span>
                            <strong class="text-body" id="modal-filed-date">
                                -
                            </strong>
                        </div>

                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">
                                Leave Type
                            </span>
                            <strong class="text-info" id="modal-leave-type">
                                -
                            </strong>
                        </div>

                        <div class="col-6 col-sm-3">
                            <span class="d-block text-uppercase text-body-secondary fw-semibold small">
                                Duration
                            </span>
                            <strong class="text-body" id="modal-duration">
                                -
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold text-body-secondary small">
                        Reason Provided
                    </label>

                    <div
                        class="bg-body-tertiary p-3 rounded-3 border text-break text-wrap text-body"
                        id="modal-reason"
                    >
                        -
                    </div>

                </div>

                <div>

                    <label class="form-label fw-semibold text-body-secondary small">
                        Attached Documents
                    </label>

                    <div
                        class="d-flex flex-wrap gap-2"
                        id="modal-docs-container"
                    >
                        <span class="small text-body-secondary">
                            No attachments
                        </span>
                    </div>

                </div>

            </div>

            <div class="modal-footer border-top bg-body-tertiary d-flex flex-wrap gap-2">

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

                <button
                    type="button"
                    id="modal-reject-btn"
                    class="btn btn-outline-danger"
                    onclick="openRejectFromModal()"
                >
                    <i class="fas fa-times me-1"></i>
                    Reject
                </button>

                <button
                    type="button"
                    id="modal-approve-btn"
                    class="btn btn-success"
                    onclick="approveCurrentRequest()"
                >
                    <i class="fas fa-check me-1"></i>
                    Approve Request
                </button>

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
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Reject Leave Request
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <div class="modal-body p-3 p-md-4">

                <p class="text-body-secondary small mb-3">
                    Please provide a reason for rejecting this leave request.
                </p>

                <label
                    for="reject-reason"
                    class="form-label fw-semibold text-body small"
                >
                    Reason for Rejection
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    id="reject-reason"
                    class="form-control text-body"
                    rows="5"
                    maxlength="1000"
                    placeholder="Enter the reason for rejection..."
                ></textarea>

                <div class="small text-body-secondary text-end mt-1">
                    Maximum 1000 characters
                </div>

            </div>

            <div class="modal-footer border-top bg-body-tertiary">

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="confirmReject()"
                >
                    <i class="fas fa-times me-1"></i>
                    Confirm Rejection
                </button>

            </div>

        </div>

    </div>

</div>

<script>

const BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;

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

function viewDetails(data) {

    document.getElementById('modal-faculty-name').textContent =
        data.faculty || '-';

    document.getElementById('modal-filed-date').textContent =
        data.date || '-';

    document.getElementById('modal-leave-type').textContent =
        data.type || '-';

    document.getElementById('modal-duration').textContent =
        data.start && data.end
            ? data.start + ' - ' + data.end
            : '-';

    document.getElementById('modal-reason').textContent =
        data.reason || '-';

    const docsContainer = document.getElementById('modal-docs-container');
    docsContainer.innerHTML = '';

    function resolveDocumentPath(docs) {
        if (!docs) return null;

        let path = null;

        if (typeof docs === 'string') {
            try {
                const parsed = JSON.parse(docs);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    const last = parsed[parsed.length - 1];
                    if (typeof last === 'string') path = last;
                    else if (last && typeof last === 'object' && last.file) path = last.file;
                    else path = String(last);
                } else if (typeof parsed === 'string') {
                    path = parsed;
                }
            } catch (e) {
                path = docs;
            }
        } else if (Array.isArray(docs)) {
            const last = docs[docs.length - 1];
            if (typeof last === 'string') path = last;
            else if (last && typeof last === 'object' && last.file) path = last.file;
            else path = String(last);
        } else if (typeof docs === 'object' && docs !== null) {
            path = String(docs);
        }

        if (!path) return null;

        if (/^(https?:)?\/\//i.test(path) || path.charAt(0) === '/') return path;

        return BASE_URL + '/' + path.replace(/^\/+/, '');
    }

    const resolved = resolveDocumentPath(data.documents);

    if (resolved) {
        const viewLink = document.createElement('a');
        viewLink.href = resolved;
        viewLink.target = '_blank';
        viewLink.rel = 'noopener noreferrer';
        viewLink.className = 'btn btn-sm btn-outline-info';

        const parts = resolved.split('/');
        const fileName = parts[parts.length - 1] || 'document';
        viewLink.textContent = fileName;

        docsContainer.appendChild(viewLink);

        const downloadLink = document.createElement('a');
        downloadLink.href = resolved;
        downloadLink.className = 'btn btn-sm btn-outline-secondary ms-2';
        downloadLink.setAttribute('download', fileName);
        downloadLink.setAttribute('role', 'button');
        downloadLink.textContent = 'Download';

        docsContainer.appendChild(downloadLink);
    } else {
        docsContainer.innerHTML = '<span class="small text-body-secondary">No attachments</span>';
    }

    document.getElementById('proposalModal').dataset.requestId =
        data.id || '';

    document.getElementById('proposalModal').dataset.requestStatus =
        (data.status || '').toString();

    try {
        var approveBtn = document.getElementById('modal-approve-btn');
        var rejectBtn = document.getElementById('modal-reject-btn');
        var st = ((data.status || '') + '').toLowerCase();
        var isPending = st === 'pending';
        var isDocumentApproval = st === 'document required' || st === 'document_required';
        var isRejected = st === 'rejected';
        var isActionable = isPending || isDocumentApproval;

        if (approveBtn) approveBtn.style.display = isActionable ? '' : 'none';
        if (rejectBtn) rejectBtn.style.display = isPending ? '' : 'none';

        if (isRejected) {
            if (approveBtn) approveBtn.style.display = 'none';
            if (rejectBtn) rejectBtn.style.display = 'none';
        }

        var modalElement = document.getElementById('proposalModal');
        if (modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    } catch (e) {
        console.error('Unable to open leave request details modal:', e);
    }
}

function notifyFaculty(id) {
    if (!id) {
        alert('No leave request selected.');
        return;
    }

    if (!confirm('Notify the faculty to submit the required supporting document?')) {
        return;
    }

    submitAction('notify_faculty', id);
}

function deleteDocument(id) {
    if (!id) {
        alert('No leave request selected.');
        return;
    }

    if (!confirm('Remove the uploaded supporting document and request a resubmission?')) {
        return;
    }

    submitAction('delete_document', id);
}

function finishRequest(id) {
    if (!id) {
        alert('No leave request selected.');
        return;
    }

    if (!confirm('Finish this leave request after verification?')) {
        return;
    }

    submitAction('finish', id);
}

function approveCurrentRequest() {

    const id =
        document.getElementById('proposalModal').dataset.requestId;

    approveRequest(id);
}

function approveRequest(id) {

    if (!id) {
        alert('No leave request selected.');
        return;
    }

    try {
        var modalEl = document.getElementById('proposalModal');
        var status = (modalEl?.dataset?.requestStatus || '').toLowerCase();
        var actionable = status === 'pending' || status === 'document required' || status === 'document_required';
        if (status && !actionable) {
            alert('This leave request has already been processed and cannot be updated.');
            return;
        }
    } catch (e) {}

    if (!confirm('Approve this leave request?')) {
        return;
    }

    submitAction('approve', id);
}

function openRejectFromModal() {

    const id =
        document.getElementById('proposalModal').dataset.requestId;

    if (!id) {
        alert('No leave request selected.');
        return;
    }

    document.getElementById('rejectModal').dataset.requestId = id;

    const proposalModal =
        bootstrap.Modal.getInstance(
            document.getElementById('proposalModal')
        );

    if (proposalModal) {
        proposalModal.hide();
    }

    setTimeout(function () {

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('rejectModal')
        ).show();

    }, 200);
}

function rejectRequest(id) {

    if (!id) {
        alert('No leave request selected.');
        return;
    }

    try {
        var row = document.querySelector('.row-select[value="' + id + '"]');
        var modalEl = document.getElementById('proposalModal');
        var status = (modalEl?.dataset?.requestStatus || '').toLowerCase();
        var actionable = status === 'pending';
        if (status && !actionable) {
            alert('This leave request cannot be rejected while it is in document approval status.');
            return;
        }
    } catch (e) {}

    document.getElementById('rejectModal').dataset.requestId = id;
    document.getElementById('reject-reason').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectModal')).show();
}

function confirmReject() {

    const modal =
        document.getElementById('rejectModal');

    const id = modal.dataset.requestId;

    const reason =
        document.getElementById('reject-reason').value.trim();

    if (!id) {
        alert('No leave request selected.');
        return;
    }

    if (!reason) {
        alert('Please provide a reason for rejection.');
        document.getElementById('reject-reason').focus();
        return;
    }

    if (!confirm('Reject this leave request?')) {
        return;
    }

    submitAction('reject', id, reason);
}

function getSelectedIds() {

    return Array.from(
        document.querySelectorAll('.row-select:checked')
    ).map(function (checkbox) {
        return checkbox.value;
    });
}

document.addEventListener('DOMContentLoaded', function () {

    const selectAll =
        document.getElementById('selectAll');

    if (selectAll) {

        selectAll.addEventListener('change', function () {

            document
                .querySelectorAll('.row-select')
                .forEach(function (checkbox) {

                    checkbox.checked =
                        selectAll.checked;
                });

        });
    }

});

</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>