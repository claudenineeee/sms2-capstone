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

$leaveRequests = [];$totalBalance = null;
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$finishedCount = 0;
$documentRequiredCount = 0;

$formError = '';
$formSuccess = '';$alertMessages = [];

try {
    $pdo = facultyDb();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {

        $leaveType = trim((string) ($_POST['leave_type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));

        /*
         * leave_requests.faculty_id must reference
         * faculty_profiles.id.
         */
       $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            $formError = 'Your user account could not be identified. Please log in again.';
        } else {
            $stmt = $pdo->prepare("
                SELECT id
                FROM faculty_db.faculty_profiles
                WHERE user_id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            $facultyProfileId = (int) ($stmt->fetchColumn() ?: 0);

            if ($facultyProfileId <= 0) {
                $formError = 'Your account is not linked to a faculty profile.';
            }
        }

        if ($formError === '' && $leaveType === '') {
            $formError = 'Please select a leave type.';
        }

        if ($formError === '' && ($startDate === '' || $endDate === '')) {
            $formError = 'Please provide both start and end dates.';
        }

        if (
            $formError === '' &&
            strtotime($startDate) > strtotime($endDate)
        ) {
            $formError = 'Start date cannot be later than end date.';
        }

        if ($formError === '') {

            $uploadedName = null;

            if (
                isset($_FILES['document']) &&
                $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                    $formError = 'The supporting document could not be uploaded.';
                } else {

                    $uploadDir = __DIR__ . '/../../uploads/leave_requests';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $originalName = basename($_FILES['document']['name']);
                    $safeName = preg_replace(
                        '/[^A-Za-z0-9._-]/',
                        '_',
                        $originalName
                    );

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

                // total_days is NOT NULL on the table with no default —
                // compute it the same way the listing query does
                // (DATEDIFF(end_date, start_date) + 1), inclusive of both endpoints.
                $totalDays = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

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
            // ensure current user owns the faculty_profile linked to the request
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            if ($userId <= 0) {
                $formError = 'Your user account could not be identified. Please log in again.';
            } else {
                $chk = $pdo->prepare('SELECT lr.id, lr.documents, lr.faculty_id FROM faculty_db.leave_requests lr WHERE lr.id = :id LIMIT 1');
                $chk->execute([':id' => $followupRequestId]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $formError = 'Leave request not found.';
                } else {
                    // Prevent follow-up uploads for requests that are already rejected or approved
                    $currentStatus = strtolower(trim((string) ($row['status'] ?? '')));
                    if ($currentStatus === 'rejected' || $currentStatus === 'approved') {
                        $formError = 'Cannot upload follow-up for this request status.';
                    } else {
                    // confirm ownership by matching faculty_profiles.user_id
                    $fp = $pdo->prepare('SELECT id FROM faculty_db.faculty_profiles WHERE id = :fp_id AND user_id = :user_id LIMIT 1');
                    $fp->execute([':fp_id' => (int) $row['faculty_id'], ':user_id' => $userId]);
                    $owns = (bool) $fp->fetchColumn();

                    if (!$owns) {
                        $formError = 'You are not authorized to modify this request.';
                    }
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

                // append to existing documents (JSON array or single value)
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

    // Ensure we only load requests belonging to the current user
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    // If a faculty profile id was determined earlier (during POST flows), reuse it.
    $facultyProfileId = $facultyProfileId ?? 0;

    if ($facultyProfileId <= 0 && $userId > 0) {
        $tmp = $pdo->prepare("SELECT id FROM faculty_db.faculty_profiles WHERE user_id = :user_id LIMIT 1");
        $tmp->execute([':user_id' => $userId]);
        $facultyProfileId = (int) ($tmp->fetchColumn() ?: 0);
    }

    if ($facultyProfileId <= 0) {
        // No linked faculty profile for this user — show no records.
        $leaveRequests = [];
    } else {
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
        <p class="text-secondary small mb-0">Submit new leave applications and track your approval history</p>
    </div>
    <button class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#newLeaveModal">
        <i class="fas fa-plus fs-6"></i>
        <span>New Request</span>
    </button>
</div>

<!-- Summary Cards (4-Column Grid with styled indicator bars) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Requests</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?php echo count($leaveRequests); ?> <small class="text-muted fs-6">entries</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #ffc107;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending</h6>
                    <h4 class="mb-0 fw-bold" style="color: #ffc107;"><?php echo $pendingCount; ?> <small class="text-muted fs-6">awaiting review</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Approved</h6>
                    <h4 class="mb-0 fw-bold" style="color: #28a745;"><?php echo $approvedCount; ?> <small class="text-muted fs-6">requests</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #dc3545;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rejected</h6>
                    <h4 class="mb-0 fw-bold" style="color: #dc3545;"><?php echo $rejectedCount; ?> <small class="text-muted fs-6">requests</small></h4>
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
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted d-none d-sm-inline">Display:</span>
            <select class="form-select form-select-sm border-0 bg-light w-auto fw-medium">
                <option>10 rows</option>
                <option>25 rows</option>
                <option>50 rows</option>
            </select>
        </div>
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
                            $docsRaw = $row['documents'] ?? '';
                            $fileForJson = 'No document attached';
                            if (!empty($docsRaw)) {
                                $decoded = json_decode($docsRaw, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
                                    $last = end($decoded);
                                    if (is_array($last) && isset($last['file'])) {
                                        $fileForJson = $last['file'];
                                    } elseif (is_string($last)) {
                                        $fileForJson = $last;
                                    } else {
                                        $fileForJson = json_encode($last);
                                    }
                                } else {
                                    $fileForJson = $docsRaw;
                                }
                            }
                            $reason = htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8');
                            $fileDate = isset($row['created_at']) ? htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') : '';

                            // Determine badge class
                            $badgeClass = 'bg-secondary';
                            $normalizedStatus = strtolower(trim((string) $status));
                            $normalizedStatus = str_replace(' ', '_', $normalizedStatus);
                            if ($normalizedStatus === 'pending') $badgeClass = 'bg-warning text-dark';
                            if ($normalizedStatus === 'approved') $badgeClass = 'bg-success';
                            if ($normalizedStatus === 'rejected') $badgeClass = 'bg-danger';
                            if ($normalizedStatus === 'finished') $badgeClass = 'bg-info text-white';
                            if ($normalizedStatus === 'document_required') $badgeClass = 'bg-secondary text-white';

                            $dataObj = [
                                'id' => $id,
                                'type' => $type,
                                'start' => $start,
                                'end' => $end,
                                'days' => $days,
                                'status' => $statusText,
                                'date' => $fileDate ?: '',
                                'reason' => $row['reason'] ?? '',
                                'doc' => $fileForJson,
                            ];
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
                                <div class="d-inline-flex align-items-center gap-1 bg-light p-1 rounded-pill border">
                                    <button class="btn btn-sm btn-white text-primary rounded-circle shadow-none px-2 py-1 border-0" title="View Details" onclick='viewDetails(<?= htmlspecialchars(json_encode($dataObj), ENT_QUOTES, 'UTF-8') ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!in_array(strtolower($status), ['rejected', 'finished', 'approved'])): ?>
                                    <button class="btn btn-sm btn-white text-secondary rounded-circle shadow-none px-2 py-1 border-0" title="Upload Follow-up / Supporting Document" onclick="openUploadModal(<?= (int)$row['id'] ?>)">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (strtolower($status) === 'pending'): ?>
                                    <button class="btn btn-sm btn-white text-danger rounded-circle shadow-none px-2 py-1 border-0" title="Cancel Request" onclick="cancelRequest('<?= $id ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 py-3 px-4 d-flex justify-content-between align-items-center">
        <span class="small text-muted">Showing 1 to 5 of 5 entries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link border-0" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link rounded-circle mx-1" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link border-0" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal: New Leave Request -->
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
                        <div class="form-text fs-7">Attach medical certificates or official notes if required.</div>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 rounded-3 d-flex align-items-center gap-2 text-primary small">
                        <i class="fas fa-info-circle fs-6"></i>
                        <span>You currently have <strong>10 available days</strong> in total balance.</span>
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

<!-- Modal: Upload Follow-up Document -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-upload text-primary"></i>
                    Upload Follow-up Document
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followupForm" method="post" enctype="multipart/form-data">
            <div class="modal-body p-4">
                <input type="hidden" name="followup_request_id" id="followup_request_id" value="">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Select file <span class="text-danger">*</span></label>
                    <input type="file" name="followup_doc" class="form-control bg-light" required>
                    <div class="form-text fs-7">Attach additional document for this request.</div>
                </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="upload_followup" class="btn btn-primary rounded-pill px-4">Upload</button>
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
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-primary"></i>
                    Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted small d-block">Reference ID</span>
                            <span class="fw-bold text-dark" id="modal-req-id">-</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Status</span>
                            <span id="modal-status-badge">-</span>
                        </div>
                    </div>
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
                    <div class="col-6">
                        <span class="text-muted small d-block">Start Date</span>
                        <span class="fw-semibold" id="modal-start-date">-</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">End Date</span>
                        <span class="fw-semibold" id="modal-end-date">-</span>
                    </div>
                </div>

                <hr class="my-3 text-muted opacity-25">

                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Reason Provided</span>
                    <p class="mb-0 bg-light p-3 rounded-3 text-dark small" id="modal-reason">-</p>
                </div>

                <div>
                    <span class="text-muted small d-block mb-1">Attached Document</span>
                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 text-truncate" id="modal-doc-container">
                        <i class="fas fa-file-pdf text-danger fs-5"></i>
                        <span class="small fw-medium text-dark text-truncate" id="modal-doc-name">No attachment</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0" id="modal-footer-actions">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(data) {
    document.getElementById('modal-req-id').textContent = data.id;
    document.getElementById('modal-leave-type').textContent = data.type;
    document.getElementById('modal-days').textContent = data.days;
    document.getElementById('modal-start-date').textContent = data.start;
    document.getElementById('modal-end-date').textContent = data.end;
    document.getElementById('modal-reason').textContent = data.reason || 'N/A';
    document.getElementById('modal-doc-name').textContent = data.doc || 'No document attached';

    // Badge Render
    let badgeClass = 'bg-secondary';
    if(data.status === 'Pending') badgeClass = 'bg-warning text-dark';
    if(data.status === 'Approved') badgeClass = 'bg-success';
    if(data.status === 'Rejected') badgeClass = 'bg-danger';

    document.getElementById('modal-status-badge').innerHTML = 
        `<span class="badge ${badgeClass} rounded-pill px-2.5 py-1">${data.status}</span>`;

    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

function cancelRequest(id) {
    if(confirm(`Are you sure you want to cancel request ${id}?`)) {
        alert(`Request ${id} has been successfully cancelled.`);
    }
}

function openUploadModal(id) {
    document.getElementById('followup_request_id').value = id;
    const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
    modal.show();
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auto-dismiss-alert').forEach(function (alertEl) {
        const seconds = Number(alertEl.dataset.autoDismissSeconds || 3);
        setTimeout(function () {
            alertEl.classList.add('fade');
            setTimeout(function () {
                alertEl.remove();
            }, 350);
        }, seconds * 1000);
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>