<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();
$pageTitle = 'Leave Request';
$activeModule = 'faculty';$activePage = 'leave-request';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Leave Request', 'url' => null],
];

$leaveRequests = [];
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$finishedCount = 0;
$documentRequiredCount = 0;

// Semester Leave tracking variables
$maxSemesterDays = 7;
$consumedSemesterDays = 0;
$remainingSemesterDays = 7;

$formError = '';
$formSuccess = '';$alertMessages = [];

try {
    $pdo = facultyDb();

    // Ensure we load faculty profile id for user first
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $facultyProfileId = 0;

    if ($userId <= 0) {
        $formError = 'Your user account could not be identified. Please log in again.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id
            FROM faculty_db.faculty_profiles
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $facultyProfileId = (int) ($stmt->fetchColumn() ?: 0);

        if ($facultyProfileId <= 0) {
            $formError = 'Your account is not linked to a faculty profile.';
        }
    }

    // Handle Edit / Resubmit Leave Request when status is Document Required or Returned
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave'])) {
        $editRequestId = (int) ($_POST['edit_request_id'] ?? 0);
        $leaveType = trim((string) ($_POST['leave_type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($editRequestId <= 0) {
            $formError = 'Invalid request identifier.';
        }

        if ($formError === '' && $leaveType === '') {
            $formError = 'Please select a leave type.';
        }

        if ($formError === '' && ($startDate === '' || $endDate === '')) {
            $formError = 'Please provide both start and end dates.';
        }

        if ($formError === '' && strtotime($startDate) > strtotime($endDate)) {
            $formError = 'Start date cannot be later than end date.';
        }

        $totalDays = (int) (($endDate && $startDate) ? ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1 : 0);

        if ($formError === '') {
            // Verify ownership and status (checking both status and screening_status columns)
            $chk = $pdo->prepare('SELECT id, documents, faculty_id, status, screening_status, total_days FROM faculty_db.leave_requests WHERE id = :id LIMIT 1');
            $chk->execute([':id' => $editRequestId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $formError = 'Leave request not found.';
            } else {
                $currentStatus = strtolower(trim((string) ($row['status'] ?? '')));
                $currentScreening = strtolower(trim((string) ($row['screening_status'] ?? '')));
                
                $isEditable = ($currentStatus === 'document required' || $currentStatus === 'pending' || $currentStatus === 'document_required' || $currentStatus === 'returned' ||
                               $currentScreening === 'document required' || $currentScreening === 'returned' || $currentScreening === 'document_required');

                if (!$isEditable) {
                    $formError = 'This request can no longer be edited.';
                } else {
                    $fp = $pdo->prepare('SELECT id FROM faculty_db.faculty_profiles WHERE id = :fp_id AND user_id = :user_id LIMIT 1');
                    $fp->execute([':fp_id' => (int) $row['faculty_id'], ':user_id' => $userId]);
                    if (!$fp->fetchColumn()) {
                        $formError = 'You are not authorized to modify this request.';
                    }
                }
            }
        }

        if ($formError === '') {
            $uploadedName = $row['documents']; // keep existing if not uploaded

            if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                    $formError = 'The supporting document could not be uploaded.';
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/leave_requests';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $originalName = basename($_FILES['document']['name']);
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $safeName;
                    $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                    if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                        $uploadedName = 'modules/faculty/uploads/leave_requests/' . $fileName;
                    } else {
                        $formError = 'The supporting document could not be saved.';
                    }
                }
            }

            if ($formError === '') {
                try {
                    $upSql = "
                        UPDATE faculty_db.leave_requests 
                        SET leave_type = :leave_type,
                            start_date = :start_date,
                            end_date = :end_date,
                            total_days = :total_days,
                            reason = :reason,
                            documents = :documents,
                            status = 'Pending',
                            screening_status = 'Pending',
                            notification = 0,
                            updated_at = NOW()
                        WHERE id = :id
                    ";
                    $stmtUp = $pdo->prepare($upSql);
                    $stmtUp->execute([
                        ':leave_type' => $leaveType,
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':total_days' => $totalDays,
                        ':reason' => $reason,
                        ':documents' => $uploadedName,
                        ':id' => $editRequestId
                    ]);

                    $formSuccess = 'Leave request updated and resubmitted successfully.';
                } catch (PDOException $e) {
                    $formError = 'Unable to update leave request: ' . $e->getMessage();
                    error_log('[leave-request] update error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {

        $leaveType = trim((string) ($_POST['leave_type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($formError === '' && $leaveType === '') {
            $formError = 'Please select a leave type.';
        }

        if ($formError === '' && ($startDate === '' || $endDate === '')) {
            $formError = 'Please provide both start and end dates.';
        }

        if ($formError === '' && strtotime($startDate) > strtotime($endDate)) {
            $formError = 'Start date cannot be later than end date.';
        }

        $totalDays = (int) (($endDate && $startDate) ? ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1 : 0);

        if ($formError === '') {
            $uploadedName = null;

            if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                    $formError = 'The supporting document could not be uploaded.';
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/leave_requests';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $originalName = basename($_FILES['document']['name']);
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $safeName;
                    $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                    if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                        $uploadedName = 'modules/faculty/uploads/leave_requests/' . $fileName;
                    } else {
                        $formError = 'The supporting document could not be saved.';
                    }
                }
            }

            if ($formError === '') {
                $requestRef = 'LR-' . date('YmdHis') . '-' . random_int(100, 999);

                $sql = "
                    INSERT INTO faculty_db.leave_requests (
                        faculty_id,
                        request_ref,
                        leave_type,
                        start_date,
                        end_date,
                        total_days,
                        reason,
                        documents,
                        status,
                        screening_status
                    ) VALUES (
                        :faculty_id,
                        :request_ref,
                        :leave_type,
                        :start_date,
                        :end_date,
                        :total_days,
                        :reason,
                        :documents,
                        'Pending',
                        'Pending'
                    )
                ";

                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':faculty_id' => $facultyProfileId,
                        ':request_ref' => $requestRef,
                        ':leave_type' => $leaveType,
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':total_days' => $totalDays,
                        ':reason' => $reason,
                        ':documents' => $uploadedName,
                    ]);

                    $formSuccess = 'Leave request submitted successfully.';
                } catch (PDOException $e) {
                    $formError = 'Unable to save the leave request: ' . $e->getMessage();
                    error_log('[leave-request] ' . $e->getMessage());
                }
            }
        }
    }

    if ($facultyProfileId > 0) {
        $sql = "
            SELECT
                lr.*, 
                fp.id AS faculty_profile_id,
                fp.faculty_id AS faculty_identifier,
                CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name,
                DATEDIFF(lr.end_date, lr.start_date) + 1 AS days
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id
            WHERE lr.faculty_id = :faculty_profile_id
            ORDER BY lr.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':faculty_profile_id' => $facultyProfileId]);
        $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recalculate consumed and remaining quota accurately based on active requests
    $consumedSemesterDays = 0;
    foreach ($leaveRequests as $request) {
        $status = strtolower(trim((string) ($request['status'] ?? '')));
        $screeningStatus = strtolower(trim((string) ($request['screening_status'] ?? '')));

        $normalizedStatus = str_replace('_', ' ', strtolower(trim((string) $status)));
        $normalizedScreening = str_replace('_', ' ', strtolower(trim((string) $screeningStatus)));

        $isReturned = ($normalizedStatus === 'document required' || $normalizedStatus === 'returned' || 
                       $normalizedScreening === 'document required' || $normalizedScreening === 'returned' ||
                       $normalizedStatus === 'document_required' || $normalizedScreening === 'document_required');

        if ($isReturned) {
            $documentRequiredCount++;
            $consumedSemesterDays += (int)($request['days'] ?? 0);
        } elseif ($normalizedStatus === 'pending') {
            $pendingCount++;
            $consumedSemesterDays += (int)($request['days'] ?? 0);
        } elseif ($normalizedStatus === 'approved') {
            $approvedCount++;
            $consumedSemesterDays += (int)($request['days'] ?? 0);
        } elseif ($normalizedStatus === 'rejected') {
            $rejectedCount++;
        } elseif ($normalizedStatus === 'finished') {
            $finishedCount++;
            $consumedSemesterDays += (int)($request['days'] ?? 0);
        }

        $notificationFlag = (int) ($request['notification'] ?? 0);
        $documentsValue = trim((string) ($request['documents'] ?? ''));

        if ($notificationFlag === 1 && $documentsValue === '') {
            $requestRef = trim((string) ($request['request_ref'] ?? ''));
            if ($requestRef === '') {
                $requestRef = 'LR-' . (int) ($request['id'] ?? 0);
            }

            $alertMessages[] = $requestRef . ' document support has been rejected.';
        }
    }
    $remainingSemesterDays = max(0, $maxSemesterDays - $consumedSemesterDays);

} catch (Throwable $e) {
    $formError = 'Database error: ' . $e->getMessage();
    error_log('[leave-request] ' . $e->getMessage());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Toast Container (Bottom-End or Top-End) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessageBody">
                <!-- Dynamic text goes here -->
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-plane-departure fs-5"></i>
            </span>
            Leave Management
        </h4>
        <p class="text-secondary small mb-0">Submit new leave applications and track your approval history (7 Days Max Per Semester)</p>
    </div>
    
    <?php if ($remainingSemesterDays > 0): ?>
        <button class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#newLeaveModal">
            <i class="fas fa-plus fs-6"></i>
            <span>New Request</span>
        </button>
    <?php else: ?>
        <button class="btn btn-secondary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium" disabled title="You have exhausted your 7-day leave limit for this semester">
            <i class="fas fa-ban fs-6"></i>
            <span>Limit Reached (0 Days Left)</span>
        </button>
    <?php endif; ?>
</div>

<!-- Summary Cards including Semester Quota Tracker -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Semester Quota</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?php echo $maxSemesterDays; ?> <small class="text-muted fs-6">days limit</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #fd7e14; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #fd7e14;">
                    <i class="fas fa-business-time"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Consumed</h6>
                    <h4 class="mb-0 fw-bold" style="color: #fd7e14;"><?php echo $consumedSemesterDays; ?> <small class="text-muted fs-6">days used</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;">
                    <i class="fas fa-battery-three-quarters"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Remaining Balance</h6>
                    <h4 class="mb-0 fw-bold" style="color: #28a745;"><?php echo $remainingSemesterDays; ?> <small class="text-muted fs-6">days left</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #dc3545;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Approval</h6>
                    <h4 class="mb-0 fw-bold" style="color: #dc3545;"><?php echo $pendingCount + $documentRequiredCount; ?> <small class="text-muted fs-6">requests</small></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<?php if (!empty($alertMessages)): ?>
    <div class="mb-4">
        <?php foreach ($alertMessages as $message): ?>
            <div class="alert alert-warning border-0 shadow-sm auto-dismiss-alert" role="alert" data-auto-dismiss-seconds="3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Requests Data Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-history text-primary"></i>
            Application Records
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom border-light-subtle">
                    <tr>
                        <th class="ps-4 text-uppercase small text-secondary fw-semibold">ID</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Type</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Duration</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Days</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Status</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Filed Date</th>
                        <th class="pe-4 text-end text-uppercase small text-secondary fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaveRequests)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No leave requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveRequests as $row):
                            $dbId = (int) ($row['id'] ?? 0);
                            $id = htmlspecialchars($row['request_ref'] ?? ('LR-' . $dbId), ENT_QUOTES, 'UTF-8');
                            $type = htmlspecialchars($row['leave_type'] ?? '', ENT_QUOTES, 'UTF-8');
                            $start = htmlspecialchars($row['start_date'] ?? '', ENT_QUOTES, 'UTF-8');
                            $end = htmlspecialchars($row['end_date'] ?? '', ENT_QUOTES, 'UTF-8');
                            $days = (int) ($row['days'] ?? 0);
                            
                            $status = $row['status'] ?? '';
                            $screeningStatus = $row['screening_status'] ?? '';
                            
                            $normalizedStatus = str_replace('_', ' ', strtolower(trim((string) $status)));
                            $normalizedScreening = str_replace('_', ' ', strtolower(trim((string) $screeningStatus)));

                            $isReturned = ($normalizedStatus === 'document required' || $normalizedStatus === 'returned' || 
                                           $normalizedScreening === 'document required' || $normalizedScreening === 'returned' ||
                                           $normalizedStatus === 'document_required' || $normalizedScreening === 'document_required');

                            if ($isReturned) {
                                $statusText = 'Returned';
                                $badgeClass = 'bg-warning text-dark';
                            } else {
                                $statusText = ucwords(str_replace('_', ' ', strtolower(trim((string) $status))));
                                $badgeClass = 'bg-secondary';
                                if ($normalizedStatus === 'pending') $badgeClass = 'bg-warning text-dark';
                                if ($normalizedStatus === 'approved') $badgeClass = 'bg-success';
                                if ($normalizedStatus === 'rejected') $badgeClass = 'bg-danger';
                                if ($normalizedStatus === 'finished') $badgeClass = 'bg-info text-white';
                            }
                            
                            $fileDate = isset($row['created_at']) ? htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') : '';
                            
                            // Check all potential database column names for secretary remarks/return reason
                            $remarks = $row['return_reason'] ?? $row['secretary_reason'] ?? $row['remarks'] ?? $row['comment'] ?? $row['feedback'] ?? $row['secretary_remarks'] ?? '';
                        ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-primary"><?= $id ?></td>
                            <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2"><?= $type ?></span></td>
                            <td class="small">
                                <i class="far fa-calendar text-muted me-1"></i><?= $start ?>
                                <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> <?= $end ?>
                            </td>
                            <td><span class="fw-medium"><?= $days ?></span> <span class="text-muted small">d</span></td>
                            <td><span class="badge border rounded-pill px-2.5 py-1.5 <?= $badgeClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="small text-muted"><?= $fileDate ?></td>
                            <td class="pe-4 text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($isReturned): ?>
                                        <button class="btn btn-sm btn-warning rounded-circle" title="Edit & Resubmit Document/Reason" onclick='editDetails(<?= htmlspecialchars(json_encode([
                                            'db_id' => $dbId, 'id' => $id, 'type' => $type, 'start' => $start, 'end' => $end, 'reason' => $row['reason'] ?? '', 'remarks' => $remarks
                                        ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-light text-primary rounded-circle" title="View Details" onclick='viewDetails(<?= htmlspecialchars(json_encode([
                                        'db_id' => $dbId, 'id' => $id, 'type' => $type, 'start' => $start, 'end' => $end, 'days' => $days, 'status' => $statusText, 'date' => $fileDate, 'reason' => $row['reason'] ?? '', 'status_raw' => $isReturned ? 'returned' : $normalizedStatus, 'remarks' => $remarks, 'documents' => $row['documents'] ?? ''
                                    ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
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

<!-- Modal: New Leave Request -->
<?php if ($remainingSemesterDays > 0): ?>
<div class="modal fade" id="newLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature text-primary"></i>
                    Apply for Leave
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leaveRequestForm" method="post" enctype="multipart/form-data">
            <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select bg-light" required>
                            <option value="" disabled selected>Select category...</option>
                            <option>Vacation Leave</option>
                            <option>Sick Leave</option>
                            <option>Emergency Leave</option>
                            <option>Study Leave</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control bg-light" rows="3" placeholder="Provide details regarding your request..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Supporting Documents</label>
                        <input type="file" name="document" class="form-control bg-light">
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 rounded-3 d-flex align-items-center gap-2 text-primary small">
                        <i class="fas fa-info-circle fs-6"></i>
                        <span>You have <strong><?php echo $remainingSemesterDays; ?> available days</strong> left out of your 7-day semester quota.</span>
                    </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Discard</button>
                <button type="submit" name="submit_leave" class="btn btn-primary rounded-pill px-4">Submit Application</button>
            </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Edit / Resubmit Leave Request -->
<div class="modal fade" id="editLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-warning">
                    <i class="fas fa-edit"></i>
                    Edit & Resubmit Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLeaveRequestForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_request_id" id="edit-req-db-id">
                <div class="modal-body p-4">
                    <!-- Secretary's Return Reason Box Added Here -->
                    <div id="edit-remarks-container" class="mb-3 d-none">
                        <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3">
                            <span class="text-danger small fw-bold d-block mb-1">
                                <i class="fas fa-exclamation-circle me-1"></i> Secretary's Reason for Returning:
                            </span>
                            <p class="mb-0 text-dark small fw-medium" id="edit-remarks-text">-</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" id="edit-leave-type" class="form-select bg-light" required>
                            <option>Vacation Leave</option>
                            <option>Sick Leave</option>
                            <option>Emergency Leave</option>
                            <option>Study Leave</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="edit-start-date" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="edit-end-date" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="edit-reason" class="form-control bg-light" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Replace Supporting Document <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control bg-light" required>
                        <small class="text-muted d-block mt-1">Upload your new or corrected supporting document.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_leave" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">Update & Resubmit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <span class="text-muted small d-block">Reference ID</span>
                    <span class="fw-bold text-dark" id="modal-req-id">-</span>
                </div>
                <div id="modal-remarks-container" class="mb-3 d-none">
                    <span class="text-danger small fw-bold d-block mb-1"><i class="fas fa-exclamation-circle me-1"></i> Secretary Feedback / Remarks</span>
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 text-dark small fw-medium" id="modal-remarks">-</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Leave Category</span>
                        <span class="fw-semibold" id="modal-leave-type">-</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Duration</span>
                        <span class="fw-semibold"><span id="modal-days">-</span> Days</span>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Reason Provided</span>
                    <p class="mb-0 bg-light p-3 rounded-3 text-dark small" id="modal-reason">-</p>
                </div>
                <div id="modal-action-wrapper" class="d-none mt-3 text-end">
                    <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark" id="modal-edit-btn">
                        <i class="fas fa-edit me-1"></i> Edit & Resubmit Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentViewData = null;

function viewDetails(data) {
    currentViewData = data;
    document.getElementById('modal-req-id').textContent = data.id;
    document.getElementById('modal-leave-type').textContent = data.type;
    document.getElementById('modal-days').textContent = data.days;
    document.getElementById('modal-reason').textContent = data.reason || 'N/A';
    
    // Secretary Remarks/Feedback
    const remarksContainer = document.getElementById('modal-remarks-container');
    const remarksEl = document.getElementById('modal-remarks');
    if (data.remarks && data.remarks.trim() !== '') {
        remarksEl.textContent = data.remarks;
        remarksContainer.classList.remove('d-none');
    } else {
        remarksContainer.classList.add('d-none');
    }

    const actionWrapper = document.getElementById('modal-action-wrapper');
    if (data.status_raw === 'document required' || data.status_raw === 'returned' || data.status_raw === 'document_required') {
        actionWrapper.classList.remove('d-none');
        document.getElementById('modal-edit-btn').onclick = function() {
            bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
            editDetails(data);
        };
    } else {
        actionWrapper.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function editDetails(data) {
    document.getElementById('edit-req-db-id').value = data.db_id;
    document.getElementById('edit-leave-type').value = data.type;
    document.getElementById('edit-start-date').value = data.start;
    document.getElementById('edit-end-date').value = data.end;
    document.getElementById('edit-reason').value = data.reason;
    
    // Populate Secretary Remarks in Edit Modal
    const editRemarksContainer = document.getElementById('edit-remarks-container');
    const editRemarksText = document.getElementById('edit-remarks-text');
    if (data.remarks && data.remarks.trim() !== '') {
        editRemarksText.textContent = data.remarks;
        editRemarksContainer.classList.remove('d-none');
    } else {
        editRemarksContainer.classList.add('d-none');
    }
    
    new bootstrap.Modal(document.getElementById('editLeaveModal')).show();
}

// TOAST ALERT
document.addEventListener('DOMContentLoaded', function () {
    const phpError = <?= json_encode($formError) ?>;
    const phpSuccess = <?= json_encode($formSuccess) ?>;
    
    let message = '';
    let isError = false;

    if (phpError) {
        message = phpError;
        isError = true;
    } else if (phpSuccess) {
        message = phpSuccess;
        isError = false;
    }

    if (message) {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toastMessageBody');
        const closeBtn = toastEl.querySelector('.btn-close');
        
        // Use solid, high-contrast dark-mode friendly background colors
        if (isError) {
            toastEl.className = 'toast align-items-center text-white border-0 shadow-lg';
            toastEl.style.backgroundColor = '#842029'; // Deep rich red for dark mode
            closeBtn.classList.remove('btn-close-white');
            closeBtn.style.filter = 'invert(1) grayscale(100%) brightness(200%)';
        } else {
            toastEl.className = 'toast align-items-center text-white border-0 shadow-lg';
            toastEl.style.backgroundColor = '#0f5132'; // Deep rich green for dark mode
            closeBtn.classList.remove('btn-close-white');
            closeBtn.style.filter = 'invert(1) grayscale(100%) brightness(200%)';
        }
        
        // Add an icon alongside the message text
        const iconClass = isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
        toastBody.innerHTML = `<i class="${iconClass} fs-5 text-white"></i> <span class="text-white">${message}</span>`;
        
        // Show the toast using Bootstrap API
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    }
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>