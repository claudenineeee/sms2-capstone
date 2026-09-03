<?php

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle = 'Leave Request Screening';
$activeModule = 'faculty';
$activePage = 'leave-request-screening';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Requests', 'url' => null],
];

$leaveRequests = [];
$pendingCount = 0;
$screenedCount = 0;
$returnedCount = 0;
$totalRecords = 0;
$totalPages = 1;
$page = 1;

$formError = '';
$formSuccess = '';

if (isset($_GET['success'])) {
    $formSuccess = (string) $_GET['success'];
}

try {

    $pdo = facultyDb();

    if (!$pdo) {
        throw new RuntimeException('Unable to connect to the faculty database.');
    }

    $restrictedDeptId = function_exists('getRestrictedDepartmentId') ? getRestrictedDepartmentId() : null;
    $restrictedDeptCode = null;

    if ($restrictedDeptId !== null && $restrictedDeptId > 0) {
        $deptCodeStmt = $pdo->prepare("SELECT code FROM faculty_db.departments WHERE department_id = :id LIMIT 1");
        $deptCodeStmt->execute([':id' => $restrictedDeptId]);
        $restrictedDeptCode = $deptCodeStmt->fetchColumn() ?: null;
    }

    /*
     * Process screening decision before any HTML is sent.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = trim((string) ($_POST['action'] ?? ''));
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            throw new RuntimeException('Invalid leave request.');
        }

        if (!in_array($action, ['screen_approve', 'screen_return'], true)) {
            throw new RuntimeException('Invalid screening action.');
        }

        $screenerId = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
        if (!$screenerId) {
            $screenerId = (int) ($_SESSION['user_id'] ?? 0);
        }

        if ($screenerId <= 0) {
            throw new RuntimeException('Your account could not be identified. Please log in again.');
        }

        $stmt = $pdo->prepare("
            SELECT lr.id, lr.screening_status, fp.designated_department
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id
            WHERE lr.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new RuntimeException('The selected leave request could not be found.');
        }

        if ($restrictedDeptCode !== null && $request['designated_department'] !== $restrictedDeptCode) {
            throw new RuntimeException('This leave request does not belong to your department.');
        }

        if (($request['screening_status'] ?? '') !== 'Pending') {
            throw new RuntimeException('This leave request has already been screened.');
        }

        if ($action === 'screen_approve') {
            $signature = trim((string) ($_POST['signature'] ?? ''));

            if ($signature === '' || strpos($signature, 'data:image/') !== 0) {
                throw new RuntimeException('Please draw your signature before signing this leave request.');
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    screening_status = 'Screened',
                    screening_signature = :signature,
                    screened_by_external_id = :screener_id,
                    screened_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
                  AND screening_status = 'Pending'
            ");
            $stmt->bindValue(':signature', $signature, PDO::PARAM_STR);
            $stmt->bindValue(':screener_id', (string) $screenerId, PDO::PARAM_STR);
            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('This leave request could not be signed. It may have already been processed.');
            }

            $redirectMessage = 'Leave request signed and submitted to the Department Head for approval.';

        } else { // screen_return
            $comment = trim((string) ($_POST['comment'] ?? ''));

            if ($comment === '') {
                throw new RuntimeException('Please provide a reason for returning this leave request.');
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    screening_status = 'Returned',
                    screened_by_external_id = :screener_id,
                    screened_at = NOW(),
                    status = 'Document Required',
                    comments = :comment,
                    updated_at = NOW()
                WHERE id = :id
                  AND screening_status = 'Pending'
            ");
            $stmt->bindValue(':screener_id', (string) $screenerId, PDO::PARAM_STR);
            $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
            $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('This leave request could not be returned. It may have already been processed.');
            }

            $redirectMessage = 'Leave request returned to the faculty member for revision.';
        }

        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $redirectUrl . '?success=' . urlencode($redirectMessage));
        exit;
    }

    /*
     * Listing, Filtering & Pagination Logic
     */
    $q = trim((string) ($_GET['q'] ?? ''));
    $filterStatus = trim((string) ($_GET['screening_status'] ?? ''));
    $filterType = trim((string) ($_GET['leave_type'] ?? ''));
    
    $limit = 10;
    $page = max(1, (int)($_GET['page'] ?? 1));

    $where = [];
    $params = [];

    if ($restrictedDeptCode !== null) {
        $where[] = 'fp.designated_department = :dept_code';
        $params[':dept_code'] = $restrictedDeptCode;
    }

    if ($q !== '') {
        $where[] = "(CONCAT_WS(' ', fp.first_name, fp.last_name) LIKE :q_name OR lr.request_ref LIKE :q_ref)";
        $params[':q_name'] = '%' . $q . '%';
        $params[':q_ref'] = '%' . $q . '%';
    }

    if ($filterStatus !== '') {
        $where[] = 'lr.screening_status = :screening_status';
        $params[':screening_status'] = $filterStatus;
    }
    
    if ($filterType !== '') {
        $where[] = 'lr.leave_type = :leave_type';
        $params[':leave_type'] = $filterType;
    }

    $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

    // Aggregated counts for cards
    $statsSql = "SELECT lr.screening_status, COUNT(*) as cnt
                 FROM faculty_db.leave_requests lr
                 LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id
                 $whereClause
                 GROUP BY lr.screening_status";
                 
    $stmtStats = $pdo->prepare($statsSql);
    foreach ($params as $k => $v) {
        $stmtStats->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtStats->execute();
    
    while ($row = $stmtStats->fetch(PDO::FETCH_ASSOC)) {
        $s = $row['screening_status'];
        if ($s === 'Pending') $pendingCount = (int)$row['cnt'];
        elseif ($s === 'Screened') $screenedCount = (int)$row['cnt'];
        elseif ($s === 'Returned') $returnedCount = (int)$row['cnt'];
    }

    // Total records for pagination
    $countSql = "SELECT COUNT(*) FROM faculty_db.leave_requests lr LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id $whereClause";
    $stmtCount = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $stmtCount->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtCount->execute();
    
    $totalRecords = (int) $stmtCount->fetchColumn();
    $totalPages = max(1, ceil($totalRecords / $limit));
    if ($page > $totalPages) {
        $page = $totalPages; 
    }
    $offset = ($page - 1) * $limit;

    // Fetch paginated records
    $sql = "SELECT lr.*, fp.id AS faculty_profile_id, fp.designated_department, CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name, DATEDIFF(lr.end_date, lr.start_date) + 1 AS days
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty_profiles fp ON fp.id = lr.faculty_id
            $whereClause
            ORDER BY CASE WHEN lr.screening_status = 'Pending' THEN 0 ELSE 1 END, lr.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Handle AJAX request responses (Return JSON with updated HTML fragments)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        
        // Render table rows HTML
        ob_start();
        if (empty($leaveRequests)) {
            echo '<tr><td colspan="8" class="text-center text-muted py-4">No leave requests found.</td></tr>';
        } else {
            foreach ($leaveRequests as $r) {
                $screeningStatus = $r['screening_status'] ?? 'Pending';
                $badgeClass = match ($screeningStatus) {
                    'Screened' => 'badge-screened',
                    'Returned' => 'badge-returned',
                    default    => 'badge-pending',
                };
                $hasDocument = trim((string) ($r['documents'] ?? '')) !== '';
                $documentUrl = $hasDocument ? BASE_URL . '/' . ltrim((string) $r['documents'], '/') : '';
                ?>
                <tr>
                    <td class="fw-bold ps-3"><?= htmlspecialchars($r['faculty_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($r['leave_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?> &rarr; <?= htmlspecialchars($r['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="fw-medium"><?= (int) ($r['days'] ?? 0) ?></span></td>
                    <td>
                        <?php if ($hasDocument): ?>
                            <a href="<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"
                               class="status-badge badge-screened text-decoration-none">
                                <i class="fas fa-paperclip"></i>View File
                            </a>
                        <?php else: ?>
                            <span class="status-badge badge-none">None</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($screeningStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end pe-3">
                        <?php if ($screeningStatus === 'Pending'): ?>
                            <button type="button" class="btn btn-sm btn-primary shadow-sm"
                                    onclick="openReviewModal(<?= (int) $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['faculty_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($r['leave_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($r['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($r['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($r['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', <?= $hasDocument ? 'true' : 'false' ?>, '<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-eye me-1"></i>Review
                            </button>
                        <?php elseif ($screeningStatus === 'Screened' && !empty($r['screening_signature'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="viewSignature('<?= htmlspecialchars(addslashes($r['screening_signature']), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-signature me-1"></i>View Signature
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
            }
        }
        $tableHtml = ob_get_clean();

        // Render Pagination HTML
        ob_start();
        if ($totalPages > 1) {
            $urlParams = $_GET;
            unset($urlParams['page'], $urlParams['ajax']);
            $baseUrl = '?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page=';
            ?>
            <span class="text-muted small fw-medium">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?> requests)</span>
            <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link px-3 ajax-page-link" href="<?= $page <= 1 ? '#' : $baseUrl . ($page - 1) ?>" data-page="<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $baseUrl . $i ?>" data-page="<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link px-3 ajax-page-link" href="<?= $page >= $totalPages ? '#' : $baseUrl . ($page + 1) ?>" data-page="<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
            <?php
        }
        $paginationHtml = ob_get_clean();

        echo json_encode([
            'tableHtml' => $tableHtml,
            'paginationHtml' => $paginationHtml,
            'pendingCount' => $pendingCount,
            'screenedCount' => $screenedCount,
            'returnedCount' => $returnedCount,
            'totalRecords' => $totalRecords
        ]);
        exit;
    }

} catch (Throwable $e) {

    $formError = $e->getMessage();

    error_log(
        '[leave-request-screening] ' .
        $e->getMessage() .
        PHP_EOL .
        $e->getTraceAsString()
    );
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 650;
        border-radius: 6px;
        line-height: 1;
        letter-spacing: 0.01em;
        transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    }

    .badge-pending {
        background-color: rgba(245, 158, 11, 0.15) !important;
        color: #d97706 !important;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-screened {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #059669 !important;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge-returned {
        background-color: rgba(239, 68, 68, 0.15) !important;
        color: #dc2626 !important;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge-none {
        background-color: rgba(148, 163, 184, 0.15) !important;
        color: #64748b !important;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    [data-bs-theme="dark"] .badge-pending,
    [data-theme="dark"] .badge-pending,
    body.dark-mode .badge-pending {
        background-color: rgba(245, 158, 11, 0.22) !important;
        color: #fbbf24 !important;
        border-color: rgba(251, 191, 36, 0.35);
    }

    [data-bs-theme="dark"] .badge-screened,
    [data-theme="dark"] .badge-screened,
    body.dark-mode .badge-screened {
        background-color: rgba(16, 185, 129, 0.22) !important;
        color: #34d399 !important;
        border-color: rgba(52, 211, 153, 0.35);
    }

    [data-bs-theme="dark"] .badge-returned,
    [data-theme="dark"] .badge-returned,
    body.dark-mode .badge-returned {
        background-color: rgba(239, 68, 68, 0.22) !important;
        color: #f87171 !important;
        border-color: rgba(248, 113, 113, 0.35);
    }

    [data-bs-theme="dark"] .badge-none,
    [data-theme="dark"] .badge-none,
    body.dark-mode .badge-none {
        background-color: rgba(148, 163, 184, 0.20) !important;
        color: #94a3b8 !important;
        border-color: rgba(148, 163, 184, 0.3);
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-file-signature text-primary me-2"></i>
            Leave Request Screening
        </h1>
        <p class="text-muted mb-0">Review leave applications, then sign and submit to the Department Head for approval.</p>
    </div>
</div>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger rounded-3 mb-4" role="alert">
        <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success rounded-3 mb-4" role="alert">
        <?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <!-- Card 1: Pending Review -->
    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card warning border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-warning fs-4">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Review</h6>
                    <h4 class="mb-0 fw-bold text-warning" id="stat-pending"><?= (int) $pendingCount ?></h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        Awaiting screening
                    </small>
                </div>
            </div>
            <a href="?screening_status=Pending" class="status-filter-card position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" data-status="Pending" style="width: 24px; height: 24px; font-size: 0.7rem; cursor:pointer;" title="Filter Pending">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Card 2: Signed / Sent to Dept. Head -->
    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card success border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-success fs-4">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Signed / Sent to Dept. Head</h6>
                    <h4 class="mb-0 fw-bold text-success" id="stat-screened"><?= (int) $screenedCount ?></h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        Forwarded applications
                    </small>
                </div>
            </div>
            <a href="?screening_status=Screened" class="status-filter-card position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" data-status="Screened" style="width: 24px; height: 24px; font-size: 0.7rem; cursor:pointer;" title="Filter Screened">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Card 3: Returned to Faculty -->
    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card danger border-0 border-start border-4 shadow-sm position-relative h-100" style="border-left-color: #dc3545 !important;">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-danger fs-4">
                    <i class="fas fa-undo"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Returned to Faculty</h6>
                    <h4 class="mb-0 fw-bold text-danger" id="stat-returned"><?= (int) $returnedCount ?></h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        Requires revision
                    </small>
                </div>
            </div>
            <a href="?screening_status=Returned" class="status-filter-card position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" data-status="Returned" style="width: 24px; height: 24px; font-size: 0.7rem; cursor:pointer;" title="Filter Returned">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3 align-items-end" onsubmit="return false;">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" name="q" id="searchInput" class="form-control border-start-0 ps-0 bg-light" placeholder="Faculty name or reference no."
                           value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                </div>
            </div>
            
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Leave Type</label>
                <select name="leave_type" id="filterLeaveType" class="form-select bg-light">
                    <option value="">All Categories</option>
                    <?php 
                        $types = ['Vacation Leave', 'Sick Leave', 'Emergency Leave', 'Study Leave'];
                        foreach ($types as $type): 
                    ?>
                        <option value="<?= $type ?>" <?= $filterType === $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Screening Status</label>
                <select name="screening_status" id="filterScreeningStatus" class="form-select bg-light">
                    <option value="">All</option>
                    <?php foreach (['Pending', 'Screened', 'Returned'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Faculty</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Days</th>
                    <th>Documents</th>
                    <th>Screening Status</th>
                    <th>Filed</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
                <?php if (empty($leaveRequests)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No leave requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaveRequests as $r): ?>
                        <?php
                            $screeningStatus = $r['screening_status'] ?? 'Pending';
                            $badgeClass = match ($screeningStatus) {
                                'Screened' => 'badge-screened',
                                'Returned' => 'badge-returned',
                                default    => 'badge-pending',
                            };
                            $hasDocument = trim((string) ($r['documents'] ?? '')) !== '';
                            $documentUrl = $hasDocument ? BASE_URL . '/' . ltrim((string) $r['documents'], '/') : '';
                        ?>
                        <tr>
                            <td class="fw-bold ps-3"><?= htmlspecialchars($r['faculty_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($r['leave_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?> &rarr; <?= htmlspecialchars($r['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="fw-medium"><?= (int) ($r['days'] ?? 0) ?></span></td>
                            <td>
                                <?php if ($hasDocument): ?>
                                    <a href="<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"
                                       class="status-badge badge-screened text-decoration-none">
                                        <i class="fas fa-paperclip"></i>View File
                                    </a>
                                <?php else: ?>
                                    <span class="status-badge badge-none">None</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($screeningStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-3">
                                <?php if ($screeningStatus === 'Pending'): ?>
                                    <button type="button" class="btn btn-sm btn-primary shadow-sm"
                                            onclick="openReviewModal(<?= (int) $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['faculty_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($r['leave_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($r['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($r['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($r['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', <?= $hasDocument ? 'true' : 'false' ?>, '<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-eye me-1"></i>Review
                                    </button>
                                <?php elseif ($screeningStatus === 'Screened' && !empty($r['screening_signature'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="viewSignature('<?= htmlspecialchars(addslashes($r['screening_signature']), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-signature me-1"></i>View Signature
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination UI Container -->
    <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="paginationContainer">
        <?php if ($totalPages > 1): ?>
            <?php
            $urlParams = $_GET;
            unset($urlParams['page']);
            $baseUrl = '?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page=';
            ?>
            <span class="text-muted small fw-medium">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?> requests)</span>
            <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link px-3 ajax-page-link" href="<?= $page <= 1 ? '#' : $baseUrl . ($page - 1) ?>" data-page="<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $baseUrl . $i ?>" data-page="<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link px-3 ajax-page-link" href="<?= $page >= $totalPages ? '#' : $baseUrl . ($page + 1) ?>" data-page="<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Review Leave Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-4 text-muted small">Faculty</dt>
                    <dd class="col-8 fw-semibold" id="rm-faculty"></dd>
                    
                    <dt class="col-4 text-muted small">Leave Type</dt>
                    <dd class="col-8" id="rm-type"></dd>
                    
                    <dt class="col-4 text-muted small">Duration</dt>
                    <dd class="col-8" id="rm-duration"></dd>
                    
                    <dt class="col-4 text-muted small mt-2">Reason</dt>
                    <dd class="col-8 mt-2 bg-light p-2 rounded small" id="rm-reason"></dd>
                    
                    <dt class="col-4 text-muted small align-self-center">Documents</dt>
                    <dd class="col-8 mb-0" id="rm-documents-wrapper">
                        <span id="rm-documents"></span>
                        <a href="#" id="rm-document-link" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-2 d-none">
                            <i class="fas fa-paperclip me-1"></i>View File
                        </a>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-danger px-3 rounded-pill" onclick="openReturnModal()">
                    <i class="fas fa-undo me-1"></i>Return (No)
                </button>
                <button type="button" class="btn btn-success px-4 rounded-pill" onclick="approveFromModal()">
                    <i class="fas fa-signature me-1"></i>Approve &amp; Sign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Return Leave Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-bold text-muted">Reason for returning to faculty</label>
                <textarea id="return-reason" class="form-control bg-light" rows="3" placeholder="e.g. missing supporting document, incomplete details"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReturn()">Confirm Return</button>
            </div>
        </div>
    </div>
</div>

<!-- Signature Pad Modal -->
<div class="modal fade" id="signatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Sign Leave Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Draw your signature below to certify this leave application and forward it to the Department Head.</p>
                <div class="border rounded-3 overflow-hidden position-relative shadow-sm" id="sig-pad-wrapper" style="touch-action: none; min-height: 180px;">
                    <canvas id="signature-pad" height="180" style="width: 100%; height: 180px; cursor: crosshair; display: block;"></canvas>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="pen-color-picker" class="small text-muted fw-bold mb-0">Pen Color:</label>
                        <div class="position-relative d-inline-block">
                            <input type="color" id="pen-color-picker" class="form-control form-control-color border-0 p-0 rounded-2" 
                                style="width: 32px; height: 32px; cursor: pointer;" 
                                onchange="changePenColor(this.value)" oninput="changePenColor(this.value)">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSignaturePad()">
                        <i class="fas fa-eraser me-1"></i>Clear
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-4" onclick="confirmSignature()">
                    <i class="fas fa-check me-1"></i>Confirm &amp; Sign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Signature Modal -->
<div class="modal fade" id="viewSignatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Screening Signature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center bg-light rounded-bottom">
                <img id="vs-img" src="" alt="Screening signature" class="border rounded-3 bg-white shadow-sm" style="max-width: 100%;">
            </div>
        </div>
    </div>
</div>

<form id="screeningActionForm" method="POST" style="display:none;">
    <input type="hidden" name="action" id="saf-action">
    <input type="hidden" name="request_id" id="saf-request-id">
    <input type="hidden" name="comment" id="saf-comment">
    <input type="hidden" name="signature" id="saf-signature">
</form>

<!-- Custom Confirmation Modal -->
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
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success px-3" id="confirmModalSubmitBtn">
                    <i class="fas fa-check me-1"></i> Yes, submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX-powered Filtering and Search Script
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const leaveTypeSelect = document.getElementById('filterLeaveType');
    const screeningStatusSelect = document.getElementById('filterScreeningStatus');
    
    let searchTimeout = null;

    function fetchFilteredData(page = 1) {
        const query = searchInput.value.trim();
        const leaveType = leaveTypeSelect.value;
        const status = screeningStatusSelect.value;

        const params = new URLSearchParams({
            q: query,
            leave_type: leaveType,
            screening_status: status,
            page: page,
            ajax: 1
        });

        // Update browser URL without reloading page
        const newUrl = '?' + new URLSearchParams({
            q: query,
            leave_type: leaveType,
            screening_status: status,
            page: page
        }).toString();
        window.history.pushState({path: newUrl}, '', newUrl);

        fetch(newUrl + '&ajax=1', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('leaveTableBody').innerHTML = data.tableHtml;
            document.getElementById('paginationContainer').innerHTML = data.paginationHtml;
            document.getElementById('stat-pending').textContent = data.pendingCount;
            document.getElementById('stat-screened').textContent = data.screenedCount;
            document.getElementById('stat-returned').textContent = data.returnedCount;
            
            attachPaginationListeners();
        })
        .catch(error => console.error('Error loading filtered data:', error));
    }

    function attachPaginationListeners() {
        document.querySelectorAll('.ajax-page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page) {
                    fetchFilteredData(page);
                }
            });
        });
    }

    // Input Debounce for Search
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchFilteredData(1);
            }, 400);
        });
    }

    // Dropdown change events
    if (leaveTypeSelect) {
        leaveTypeSelect.addEventListener('change', () => fetchFilteredData(1));
    }
    if (screeningStatusSelect) {
        screeningStatusSelect.addEventListener('change', () => fetchFilteredData(1));
    }

    // Status filter cards
    document.querySelectorAll('.status-filter-card').forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const status = this.getAttribute('data-status');
            screeningStatusSelect.value = status;
            fetchFilteredData(1);
        });
    });

    attachPaginationListeners();
});

let currentReviewId = null;

function openReviewModal(id, faculty, type, startDate, endDate, reason, hasDocument, documentUrl) {
    currentReviewId = id;
    document.getElementById('rm-faculty').textContent = faculty;
    document.getElementById('rm-type').textContent = type;
    document.getElementById('rm-duration').textContent = startDate + ' to ' + endDate;
    document.getElementById('rm-reason').textContent = reason || '(none provided)';

    const docLink = document.getElementById('rm-document-link');
    const docLabel = document.getElementById('rm-documents');

    if (hasDocument && documentUrl) {
        docLabel.textContent = 'Attached';
        docLink.href = documentUrl;
        docLink.classList.remove('d-none');
    } else {
        docLabel.textContent = 'No document attached';
        docLink.href = '#';
        docLink.classList.add('d-none');
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}

function approveFromModal() {
    if (!currentReviewId) return;

    const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
    if (reviewModal) reviewModal.hide();

    setTimeout(function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('signatureModal')).show();
    }, 200);
}

let sigCanvas, sigCtx, sigDrawing = false, sigHasDrawn = false;
let currentPenColor = '#000000';

function isDarkMode() {
    const modalContent = document.querySelector('#signatureModal .modal-content');
    if (modalContent) {
        const bg = window.getComputedStyle(modalContent).backgroundColor;
        const rgb = bg.match(/\d+/g);
        if (rgb && rgb.length >= 3) {
            const brightness = (parseInt(rgb[0]) * 299 + parseInt(rgb[1]) * 587 + parseInt(rgb[2]) * 114) / 1000;
            return brightness < 128;
        }
    }
    return document.documentElement.getAttribute('data-bs-theme') === 'dark' || 
           document.documentElement.dataset.theme === 'dark' ||
           document.body.classList.contains('dark-mode');
}

function getDefaultPenColor() {
    return isDarkMode() ? '#ffffff' : '#000000';
}

function applyThemeToSignaturePad() {
    const wrapper = document.getElementById('sig-pad-wrapper');
    const colorPicker = document.getElementById('pen-color-picker');
    const dark = isDarkMode();

    if (wrapper) {
        wrapper.style.backgroundColor = dark ? '#1b2234' : '#ffffff';
        wrapper.style.borderColor = dark ? '#2b354f' : '#dee2e6';
    }

    currentPenColor = getDefaultPenColor();
    if (colorPicker) {
        colorPicker.value = currentPenColor;
    }
    if (sigCtx) {
        sigCtx.strokeStyle = currentPenColor;
    }
}

function initSignaturePad() {
    sigCanvas = document.getElementById('signature-pad');
    if (!sigCanvas) return;

    const rect = sigCanvas.getBoundingClientRect();
    if (rect.width === 0) return;

    const ratio = window.devicePixelRatio || 1;
    sigCanvas.width = rect.width * ratio;
    sigCanvas.height = rect.height * ratio;

    sigCtx = sigCanvas.getContext('2d');
    sigCtx.scale(ratio, ratio);
    sigCtx.lineWidth = 2.5;
    sigCtx.lineCap = 'round';
    sigCtx.lineJoin = 'round';

    applyThemeToSignaturePad();

    if (!sigCanvas.dataset.listenersAdded) {
        sigCanvas.dataset.listenersAdded = '1';

        function getPos(e) {
            const r = sigCanvas.getBoundingClientRect();
            const point = e.touches ? e.touches[0] : e;
            return {
                x: point.clientX - r.left,
                y: point.clientY - r.top
            };
        }

        function start(e) {
            e.preventDefault();
            sigDrawing = true;
            sigHasDrawn = true;
            const pos = getPos(e);
            sigCtx.beginPath();
            sigCtx.moveTo(pos.x, pos.y);
        }

        function move(e) {
            if (!sigDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            sigCtx.lineTo(pos.x, pos.y);
            sigCtx.stroke();
        }

        function end() {
            sigDrawing = false;
        }

        sigCanvas.addEventListener('mousedown', start);
        sigCanvas.addEventListener('mousemove', move);
        sigCanvas.addEventListener('mouseup', end);
        sigCanvas.addEventListener('mouseleave', end);
        sigCanvas.addEventListener('touchstart', start, { passive: false });
        sigCanvas.addEventListener('touchmove', move, { passive: false });
        sigCanvas.addEventListener('touchend', end);
    }
}

function changePenColor(color) {
    currentPenColor = color;
    if (sigCtx) {
        sigCtx.strokeStyle = color;
    }
}

function clearSignaturePad() {
    sigHasDrawn = false;
    if (sigCtx && sigCanvas) {
        sigCtx.save();
        sigCtx.setTransform(1, 0, 0, 1, 0, 0);
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        sigCtx.restore();
    }
}

document.addEventListener('shown.bs.modal', function (e) {
    if (e.target && e.target.id === 'signatureModal') {
        initSignaturePad();
        clearSignaturePad();
    }
});

let onConfirmCallback = null;

function showConfirmModal(message, onConfirm) {
    document.getElementById('confirmModalBody').textContent = message;
    onConfirmCallback = onConfirm;
    
    const sigModal = bootstrap.Modal.getInstance(document.getElementById('signatureModal'));
    if (sigModal) sigModal.hide();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('confirmModalSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const modalEl = document.getElementById('confirmActionModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            if (typeof onConfirmCallback === 'function') {
                onConfirmCallback();
            }
        });
    }
});

function confirmSignature() {
    if (!sigHasDrawn) {
        alert('Please draw your signature first.');
        return;
    }
    if (!currentReviewId) return;

    showConfirmModal('Sign and submit this leave application to the Department Head?', function() {
        const dataUrl = sigCanvas.toDataURL('image/png');

        document.getElementById('saf-action').value = 'screen_approve';
        document.getElementById('saf-request-id').value = currentReviewId;
        document.getElementById('saf-comment').value = '';
        document.getElementById('saf-signature').value = dataUrl;
        document.getElementById('screeningActionForm').submit();
    });
}

function viewSignature(dataUrl) {
    document.getElementById('vs-img').src = dataUrl;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewSignatureModal')).show();
}

function openReturnModal() {
    const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
    if (reviewModal) reviewModal.hide();

    document.getElementById('return-reason').value = '';
    setTimeout(function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('returnModal')).show();
    }, 200);
}

function confirmReturn() {
    const reason = document.getElementById('return-reason').value.trim();
    if (!reason) {
        alert('Please provide a reason for returning this leave request.');
        return;
    }
    if (!currentReviewId) return;

    document.getElementById('saf-action').value = 'screen_return';
    document.getElementById('saf-request-id').value = currentReviewId;
    document.getElementById('saf-comment').value = reason;
    document.getElementById('screeningActionForm').submit();
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>