<?php

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';
require_once __DIR__ . '/../../../../includes/Exception.php';
require_once __DIR__ . '/../../../../includes/PHPMailer.php';
require_once __DIR__ . '/../../../../includes/SMTP.php';

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

/**
 * Helper function to send email notifications via PHPMailer using your specific credentials
 */
function sendLeaveStatusEmail($toEmail, $toName, $subject, $statusMessage, $details = [])
{
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Use constants from config.php if available, fallback to your credentials safely
        $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'jcespejo002@gmail.com';
        $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : 'cshwohpgllkqdtga';

        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;

        // Recipients
        $mail->setFrom($mail->Username, 'Bestlink College No-Reply');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h2 style="color: #333;">Leave Request Update</h2>
            <p>Dear <strong>' . htmlspecialchars($toName) . '</strong>,</p>
            <p>' . $statusMessage . '</p>';

        if (!empty($details)) {
            $body .= '<ul style="background: #f9f9f9; padding: 15px; border-radius: 4px; list-style: none;">';
            foreach ($details as $label => $val) {
                $body .= '<li style="margin-bottom: 8px;"><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($val) . '</li>';
            }
            $body .= '</ul>';
        }

        $body .= '
            <p style="margin-top: 20px; font-size: 12px; color: #777;">This is an automated notification from the Faculty Management System. Please do not reply directly to this email.</p>
        </div>';

        $mail->Body = $body;
        $mail->AltBody = strip_tags($statusMessage);

        return $mail->send();
    } catch (Exception $e) {
        error_log("[Leave Email Error] Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

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

        // Fetch request details along with faculty user info/email
        $stmt = $pdo->prepare("
            SELECT lr.*, fp.first_name, fp.last_name, fp.user_id as profile_user_id,
                   COALESCE(u.email, fp.email) AS faculty_email
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id
            LEFT JOIN users u ON u.id = fp.user_id
            WHERE lr.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $requestId]);
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

        $facultyEmail = $request['faculty_email'] ?? '';
        $facultyName = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: 'Faculty Member';
        $refNo = $request['request_ref'] ?? ('LR-' . $requestId);
        $leaveType = $request['leave_type'] ?? 'Leave';

        if ($action === 'approve') {
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
            $stmt->execute([
                ':approver_id' => $approverId,
                ':id' => $requestId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The leave request could not be approved.');
            }

            // Send Email for Approval
            if (!empty($facultyEmail)) {
                sendLeaveStatusEmail(
                    $facultyEmail,
                    $facultyName,
                    'Leave Request Approved - ' . $refNo,
                    'Your leave request has been <strong>approved</strong> by the Department Head.',
                    [
                        'Reference No' => $refNo,
                        'Leave Type' => $leaveType,
                        'Start Date' => $request['start_date'] ?? '',
                        'End Date' => $request['end_date'] ?? ''
                    ]
                );
            }

            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode('Leave request approved and notification email sent successfully.'));
            exit;

        } elseif ($action === 'notify_faculty') {
            $updateFields = [
                "status = 'Approved'",
                "updated_at = NOW()"
            ];
            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'approved', 'document_required', 'rejected')
            ");
            $stmt->execute([':id' => $requestId]);

        } elseif ($action === 'delete_document') {
            $updateFields = [
                "documents = NULL",
                "status = 'Approved'",
                "updated_at = NOW()"
            ];
            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('approved', 'document_required', 'pending')
            ");
            $stmt->execute([':id' => $requestId]);

        } else {
            // Reject Action
            $updateFields = [
                "status = 'Rejected'",
                "approver_id = :approver_id",
                "approver_at = NOW()"
            ];

            if ($hasApproverComment) {
                $updateFields[] = "approver_comment = :comment";
            }

            $updateFields[] = "updated_at = NOW()";

            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'document_required')
            ");

            $params = [':approver_id' => $approverId, ':id' => $requestId];
            if ($hasApproverComment) {
                $params[':comment'] = $comment;
            }

            $stmt->execute($params);

            // Send Email for Rejection
            if (!empty($facultyEmail)) {
                sendLeaveStatusEmail(
                    $facultyEmail,
                    $facultyName,
                    'Leave Request Rejected - ' . $refNo,
                    'Your leave request has been <strong>rejected</strong>.',
                    [
                        'Reference No' => $refNo,
                        'Leave Type' => $leaveType,
                        'Reason' => $comment ?: 'No reason provided.'
                    ]
                );
            }
        }

        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
        $successText = match ($action) {
            'approve' => 'Leave request approved successfully.',
            'notify_faculty' => 'Faculty has been notified to submit the required document.',
            'delete_document' => 'The uploaded document was removed.',
            default => 'Leave request rejected and notification email sent successfully.',
        };

        header('Location: ' . $redirectUrl . '?success=' . urlencode($successText));
        exit;
    }

    if (isset($_GET['success'])) {
        $formSuccess = trim((string) $_GET['success']);
    }

    $countStmt = $pdo->prepare("SELECT LOWER(status) AS status, COUNT(*) AS cnt FROM faculty_db.leave_requests GROUP BY LOWER(status)");
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

    $sql = "SELECT lr.*, fp.id AS faculty_profile_id, fp.user_id AS faculty_user_id, fp.faculty_id AS faculty_identifier, CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name, 'Faculty Department' AS department, DATEDIFF(lr.end_date, lr.start_date) + 1 AS days FROM faculty_db.leave_requests lr LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id WHERE lr.screening_status = 'Screened' ORDER BY lr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($leaveRequests as $req) {
        $fId = (int) ($req['faculty_profile_id'] ?? 0);
        if ($fId <= 0)
            continue;

        if (!isset($facultyGroupedRequests[$fId])) {
            $facultyGroupedRequests[$fId] = [
                'faculty_profile_id' => $fId,
                'faculty_name' => $req['faculty_name'] ?? 'Unknown Faculty',
                'faculty_identifier' => $req['faculty_identifier'] ?? '',
                'department' => $req['department'] ?? 'Faculty Department',
                'requests' => []
            ];
        }
        $facultyGroupedRequests[$fId]['requests'][] = $req;
    }

} catch (Throwable $e) {
    $formError = $e->getMessage();
    error_log('[leave-request-approval] ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<style>
    #rejectModal {
        z-index: 1060 !important;
    }

    .table th,
    .table td {
        white-space: nowrap !important;
    }

    /* Fix for nested modal stacking order */
    .modal.show {
        background-color: rgba(0, 0, 0, 0.4);
    }

    #confirmActionModal {
        z-index: 1060 !important;
    }

    .modal-backdrop+.modal-backdrop {
        z-index: 1055 !important;
    }
</style>

<?php
if (function_exists('renderBreadcrumbs')) {
    renderBreadcrumbs($breadcrumbs);
}
?>

<!-- Toast Container (Bottom Right) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="actionToast" class="toast align-items-center text-white border-0 shadow" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMessage">
                <!-- Message goes here -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>

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

<!-- Confirmation Action Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature text-primary"></i> Confirm Submission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-muted" id="confirmModalBody">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success px-3" id="confirmModalSubmitBtn">
                    <i class="fas fa-check me-1"></i> Yes, submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Metric Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100"
            style="border-left-color: var(--bs-warning) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-warning fs-4 d-flex align-items-center justify-content-center"><i
                        class="fas fa-clock"></i></div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Pending Requests</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $pendingCount ?></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100"
            style="border-left-color: var(--bs-success) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-success fs-4 d-flex align-items-center justify-content-center"><i
                        class="fas fa-check-circle"></i></div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Approved</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $approvedCount ?></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-4">
        <section class="card border-0 border-start border-4 shadow-sm position-relative h-100"
            style="border-left-color: var(--bs-danger) !important;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-danger fs-4 d-flex align-items-center justify-content-center"><i
                        class="fas fa-times-circle"></i></div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Rejected</h6>
                    <h4 class="mb-0 fw-bold text-body"><?= $rejectedCount ?></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<?php if ($formSuccess !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast(<?= json_encode($formSuccess) ?>, 'success');
        });
    </script>
<?php endif; ?>

<?php if ($formError !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast(<?= json_encode($formError) ?>, 'danger');
        });
    </script>
<?php endif; ?>

<!-- Faculty Leave Accounts Table Card -->
<div class="card border-0 shadow-sm mb-4">
    <div
        class="card-header bg-body-tertiary py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold text-body">
            <i class="fas fa-list-check text-primary me-2"></i> Faculty Leave Accounts
        </h6>
        <div style="width: 280px;">
            <input type="search" id="tableSearchInput" class="form-control form-control-sm"
                placeholder="Search faculty name or reference...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="leaveRequestsTable">
                <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary text-nowrap">
                    <tr>
                        <th class="ps-3 py-3" style="width: 40px;"><input type="checkbox" class="form-check-input"
                                id="selectAll"></th>
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
                                if ($stRaw === 'pending')
                                    $pendingFacultyRequests++;
                                $allRefs[] = strtolower($r['request_ref'] ?? ('lr-' . $r['id']));
                                $allTypes[] = strtolower($r['leave_type'] ?? '');
                                $allStatuses[] = $stRaw;
                            }

                            $encodedGroup = htmlspecialchars(json_encode($group, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="align-middle faculty-row"
                                data-name="<?= htmlspecialchars(strtolower($facultyName), ENT_QUOTES, 'UTF-8') ?>"
                                data-refs="<?= htmlspecialchars(implode(' ', $allRefs), ENT_QUOTES, 'UTF-8') ?>">

                                <td class="ps-3 py-3"><input type="checkbox" class="form-check-input row-select"
                                        value="<?= $fId ?>"></td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold flex-shrink-0"
                                            style="width: 36px; height: 36px;">
                                            <?= htmlspecialchars(strtoupper(substr($facultyName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <strong
                                                class="text-body-emphasis d-block text-nowrap"><?= htmlspecialchars($facultyName, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="d-block text-body-secondary font-monospace text-nowrap">ID:
                                                <?= htmlspecialchars($group['faculty_identifier'] ?: $fId, ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-center"><span
                                        class="fw-bold text-body bg-body-tertiary border rounded px-2 py-1"><?= $totalFacultyRequests ?></span>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if ($pendingFacultyRequests > 0): ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold"><?= $pendingFacultyRequests ?>
                                            Pending</span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-3 py-1 fw-bold">All
                                            Processed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 py-3 text-nowrap">
                                    <button type="button"
                                        class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-2"
                                        onclick='showFacultyRequestsModal(<?= $encodedGroup ?>)'>
                                        <i class="fas fa-eye text-white"></i>
                                        <span>View Leave Profile</span>
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

<!-- Faculty Requests Modal -->
<div class="modal fade" id="facultyRequestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-body mb-0">Leave Requests: <span
                        id="modal-faculty-title-name">-</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold text-body mb-2"><i class="fas fa-chart-pie text-primary me-2"></i> Semester Leave
                        Balance Pool (7 Days Max)</h6>
                    <div class="p-3 border rounded bg-body-tertiary">
                        <div class="d-flex justify-content-between mb-1 small fw-semibold text-body">
                            <span>Total Used</span>
                            <span id="modal-pool-text">0 / 7 Days</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div id="modal-pool-progress" class="progress-bar bg-primary" role="progressbar"
                                style="width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-bordered mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase fw-bold text-body-secondary">
                            <tr>
                                <th>Ref / Date</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="modal-requests-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Reason Input Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Reject
                    Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label for="reject-reason" class="form-label fw-semibold small">Reason for Rejection <span
                        class="text-danger">*</span></label>
                <textarea id="reject-reason" class="form-control" rows="4" placeholder="Enter reason..."></textarea>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="promptRejectConfirmation()">Continue to
                    Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
    const leaveUsageData = <?= json_encode($leaveUsageData) ?>;

    let pendingActionData = { action: '', id: '', comment: '' };

    function submitAction(action, id, comment = '') {
        if (!id) return;
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

    document.getElementById('confirmModalSubmitBtn').addEventListener('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).hide();
        submitAction(pendingActionData.action, pendingActionData.id, pendingActionData.comment);
    });

    function openConfirmModal(action, id, message, btnClass, btnIconText) {
        pendingActionData = { action, id, comment: '' };
        document.getElementById('confirmModalBody').textContent = message;

        const submitBtn = document.getElementById('confirmModalSubmitBtn');
        submitBtn.className = `btn btn-sm ${btnClass} px-3`;
        submitBtn.innerHTML = `<i class="fas ${btnIconText} me-1"></i> Yes, submit`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }

    function approveRequest(id) {
        if (!id) return;
        openConfirmModal('approve', id, 'Are you sure you want to approve this leave request and notify the faculty via email?', 'btn-success', 'fa-check');
    }



    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('actionToast');
        const toastMessage = document.getElementById('toastMessage');

        toastMessage.textContent = message;

        toastEl.className = 'toast align-items-center text-white border-0 shadow';
        if (type === 'success') {
            toastEl.classList.add('bg-success');
        } else if (type === 'danger' || type === 'error') {
            toastEl.classList.add('bg-danger');
        } else if (type === 'warning') {
            toastEl.classList.add('bg-warning', 'text-dark');
        } else {
            toastEl.classList.add('bg-primary');
        }

        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
    }

    // Live Search Filter Implementation
    document.getElementById('tableSearchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#leaveRequestsTable .faculty-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const refs = row.getAttribute('data-refs') || '';
            if (name.includes(query) || refs.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    function showFacultyRequestsModal(group) {
        document.getElementById('modal-faculty-title-name').textContent = group.faculty_name;
        const totalUsed = leaveUsageData[group.faculty_profile_id] || 0;
        const maxLimit = 7;
        const percentage = Math.min(100, Math.round((totalUsed / maxLimit) * 100));

        document.getElementById('modal-pool-text').textContent = `${totalUsed} / ${maxLimit} Days`;
        const progressBar = document.getElementById('modal-pool-progress');
        progressBar.style.width = `${percentage}%`;

        const tbody = document.getElementById('modal-requests-tbody');
        tbody.innerHTML = '';

        if (!group.requests || group.requests.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No requests found.</td></tr>`;
        } else {
            group.requests.forEach(req => {
                const reqId = req.id;
                const ref = req.request_ref || ('LR-' + reqId);
                const statusRaw = (req.status || 'pending').toLowerCase();

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td><strong class="font-monospace text-body">${ref}</strong></td>
                <td><span class="badge bg-primary bg-opacity-10 text-info">${req.leave_type}</span></td>
                <td><span class="badge bg-light text-dark">${req.days} day(s)</span></td>
                <td><small class="text-truncate" style="max-width: 150px; display:inline-block;"><?= htmlspecialchars($req['reason'] ?? '') ?></small></td>
                <td><span class="badge bg-warning-subtle text-warning-emphasis">${statusRaw}</span></td>
                <td class="text-end text-nowrap">
                    ${statusRaw === 'pending' ? `
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="approveRequest(${reqId})" title="Approve"><i class="fas fa-check"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectRequest(${reqId})" title="Reject"><i class="fas fa-times"></i></button>
                    ` : ''}
                </td>
            `;
                tbody.appendChild(tr);
            });
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('facultyRequestsModal')).show();
    }

    function rejectRequest(id) {
        if (!id) return;

        // Hide the faculty requests modal first to prevent 3-tier modal backdrop conflicts
        const facultyModal = bootstrap.Modal.getInstance(document.getElementById('facultyRequestsModal'));
        if (facultyModal) {
            facultyModal.hide();
        }

        document.getElementById('rejectModal').dataset.requestId = id;
        document.getElementById('reject-reason').value = '';

        // Small timeout ensures clean DOM transition between modals
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectModal')).show();
        }, 150);
    }

    function promptRejectConfirmation() {
        const rejectModalEl = document.getElementById('rejectModal');
        const id = rejectModalEl.dataset.requestId;
        const reason = document.getElementById('reject-reason').value.trim();

        if (!id || !reason) {
            alert('Please provide a reason for rejection.');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(rejectModalEl).hide();

        pendingActionData = { action: 'reject', id: id, comment: reason };
        document.getElementById('confirmModalBody').textContent = 'Are you sure you want to reject this leave request and notify the faculty via email?';

        const submitBtn = document.getElementById('confirmModalSubmitBtn');
        submitBtn.className = 'btn btn-sm btn-danger px-3';
        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Yes, reject';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>