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

    // Calculate consumed leave days for the current faculty profile
    if ($facultyProfileId > 0) {
        $consumedSql = "
            SELECT COALESCE(SUM(total_days), 0) AS consumed
            FROM faculty_db.leave_requests
            WHERE faculty_id = :faculty_id
              AND status IN ('Pending', 'Approved', 'Finished')
        ";
        
        $consumedStmt = $pdo->prepare($consumedSql);
        $consumedStmt->execute([':faculty_id' => $facultyProfileId]);
        $consumedSemesterDays = (int) $consumedStmt->fetchColumn();
        
        $remainingSemesterDays = max(0, $maxSemesterDays - $consumedSemesterDays);
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

        // Compute requested total days
        $totalDays = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

        // Check if requested days exceed remaining semester balance
        if ($formError === '' && ($consumedSemesterDays + $totalDays > $maxSemesterDays)) {
            $formError = "You have consumed {$consumedSemesterDays} of your {$maxSemesterDays}-day semester limit. This request exceeds your remaining balance.";
        }

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
                        status
                    ) VALUES (
                        :faculty_id,
                        :request_ref,
                        :leave_type,
                        :start_date,
                        :end_date,
                        :total_days,
                        :reason,
                        :documents,
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
                    
                    $consumedSemesterDays += $totalDays;
                    $remainingSemesterDays = max(0, $maxSemesterDays - $consumedSemesterDays);
                } catch (PDOException $e) {
                    $formError = 'Unable to save the leave request: ' . $e->getMessage();
                    error_log('[leave-request] ' . $e->getMessage());
                }
            }
        }
    }

    // Handle follow-up document upload for an existing request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_followup'])) {
        $followupRequestId = (int) ($_POST['followup_request_id'] ?? 0);

        if ($followupRequestId <= 0) {
            $formError = 'Invalid request identifier for follow-up upload.';
        } else {
            $chk = $pdo->prepare('SELECT lr.id, lr.documents, lr.faculty_id, lr.status FROM faculty_db.leave_requests lr WHERE lr.id = :id LIMIT 1');
            $chk->execute([':id' => $followupRequestId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $formError = 'Leave request not found.';
            } else {
                $currentStatus = strtolower(trim((string) ($row['status'] ?? '')));
                if ($currentStatus === 'rejected' || $currentStatus === 'approved') {
                    $formError = 'Cannot upload follow-up for this request status.';
                } else {
                    $fp = $pdo->prepare('SELECT id FROM faculty_db.faculty_profiles WHERE id = :fp_id AND user_id = :user_id LIMIT 1');
                    $fp->execute([':fp_id' => (int) $row['faculty_id'], ':user_id' => $userId]);
                    $owns = (bool) $fp->fetchColumn();

                    if (!$owns) {
                        $formError = 'You are not authorized to modify this request.';
                    }
                }
            }
        }

        if ($formError === '') {
            if (!isset($_FILES['followup_doc']) || $_FILES['followup_doc']['error'] === UPLOAD_ERR_NO_FILE) {
                $formError = 'No file selected for upload.';
            } elseif ($_FILES['followup_doc']['error'] !== UPLOAD_ERR_OK) {
                $formError = 'The uploaded file reported an error.';
            }
        }

        if ($formError === '') {
            $uploadDir = __DIR__ . '/../../uploads/leave_requests';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $orig = basename($_FILES['followup_doc']['name']);
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
            $fileName = time() . '_' . $safe;
            $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($_FILES['followup_doc']['tmp_name'], $target)) {
                $formError = 'Failed to move uploaded file.';
            } else {
                $storedPath = 'modules/faculty/uploads/leave_requests/' . $fileName;

                $existing = $row['documents'] ?? '';
                $docs = [];
                if ($existing !== null && $existing !== '') {
                    $decoded = json_decode($existing, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $docs = $decoded;
                    } else {
                        $docs = [$existing];
                    }
                }

                $docs[] = ['file' => $storedPath, 'uploaded_at' => date('c')];
                $newDocVal = json_encode($docs);

                try {
                    $up = $pdo->prepare('UPDATE faculty_db.leave_requests SET documents = :docs, notification = 0, updated_at = NOW() WHERE id = :id');
                    $up->execute([':docs' => $newDocVal, ':id' => $followupRequestId]);
                    $formSuccess = 'Follow-up document uploaded successfully.';
                } catch (PDOException $e) {
                    $formError = 'Unable to save follow-up document: ' . $e->getMessage();
                    error_log('[leave-request] followup upload error: ' . $e->getMessage());
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

    foreach ($leaveRequests as $request) {
        $status = strtolower(trim((string) ($request['status'] ?? '')));
        $status = str_replace(' ', '_', $status);

        if ($status === 'pending') {
            $pendingCount++;
        } elseif ($status === 'approved') {
            $approvedCount++;
        } elseif ($status === 'rejected') {
            $rejectedCount++;
        } elseif ($status === 'finished') {
            $finishedCount++;
        } elseif ($status === 'document_required') {
            $documentRequiredCount++;
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

} catch (Throwable $e) {
    $formError = 'Database error: ' . $e->getMessage();
    error_log('[leave-request] ' . $e->getMessage());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success" role="alert">
        <?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

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
                    <h4 class="mb-0 fw-bold" style="color: #dc3545;"><?php echo $pendingCount; ?> <small class="text-muted fs-6">requests</small></h4>
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
                            $id = htmlspecialchars($row['request_ref'] ?? ('LR-' . ($row['id'] ?? '')), ENT_QUOTES, 'UTF-8');
                            $type = htmlspecialchars($row['leave_type'] ?? '', ENT_QUOTES, 'UTF-8');
                            $start = htmlspecialchars($row['start_date'] ?? '', ENT_QUOTES, 'UTF-8');
                            $end = htmlspecialchars($row['end_date'] ?? '', ENT_QUOTES, 'UTF-8');
                            $days = (int) ($row['days'] ?? 0);
                            $status = $row['status'] ?? '';
                            $statusText = htmlspecialchars(ucwords(str_replace('_', ' ', strtolower(trim((string) $status)))), ENT_QUOTES, 'UTF-8');
                            $fileDate = isset($row['created_at']) ? htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') : '';
                            
                            $badgeClass = 'bg-secondary';
                            $normalizedStatus = strtolower(trim((string) $status));
                            if ($normalizedStatus === 'pending') $badgeClass = 'bg-warning text-dark';
                            if ($normalizedStatus === 'approved') $badgeClass = 'bg-success';
                            if ($normalizedStatus === 'rejected') $badgeClass = 'bg-danger';
                            if ($normalizedStatus === 'finished') $badgeClass = 'bg-info text-white';
                        ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-primary"><?= $id ?></td>
                            <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2"><?= $type ?></span></td>
                            <td class="small">
                                <i class="far fa-calendar text-muted me-1"></i><?= $start ?>
                                <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i> <?= $end ?>
                            </td>
                            <td><span class="fw-medium"><?= $days ?></span> <span class="text-muted small">d</span></td>
                            <td><span class="badge border rounded-pill px-2.5 py-1.5 <?= $badgeClass ?>"><?= $statusText ?></span></td>
                            <td class="small text-muted"><?= $fileDate ?></td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-light text-primary rounded-circle" title="View Details" onclick='viewDetails(<?= htmlspecialchars(json_encode([
                                    'id' => $id, 'type' => $type, 'start' => $start, 'end' => $end, 'days' => $days, 'status' => $statusText, 'date' => $fileDate, 'reason' => $row['reason'] ?? ''
                                ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                    <i class="fas fa-eye"></i>
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
                <div>
                    <span class="text-muted small d-block mb-1">Reason Provided</span>
                    <p class="mb-0 bg-light p-3 rounded-3 text-dark small" id="modal-reason">-</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(data) {
    document.getElementById('modal-req-id').textContent = data.id;
    document.getElementById('modal-leave-type').textContent = data.type;
    document.getElementById('modal-days').textContent = data.days;
    document.getElementById('modal-reason').textContent = data.reason || 'N/A';
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>