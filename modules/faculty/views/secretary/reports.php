<?php
/**
 * Reports
 * Purpose: Generate daily, assignment monitoring, and document status reports
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle    = 'Reports';
$activeModule = 'faculty';
$activePage   = 'reports';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Reports', 'url' => null],
];

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
     * Handle Form Actions (Generate or Delete Report Log)
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'generate_standard' || $action === 'generate_custom') {
            $reportTitle = trim((string) ($_POST['report_title'] ?? 'Generated Report'));
            $reportType = trim((string) ($_POST['report_type'] ?? 'Daily'));
            $outputFormat = trim((string) ($_POST['output_format'] ?? 'PDF'));

            // Map types cleanly to match allowed database ENUM/VARCHAR
            $dbType = match(strtolower($reportType)) {
                'assignment' => 'Assignment',
                'document'   => 'Document',
                default      => 'Daily'
            };

            // Insert matching table columns precisely (report_name, report_type, created_at)
            $logStmt = $pdo->prepare("
                INSERT INTO faculty_db.generated_reports (report_name, report_type, created_at)
                VALUES (:name, :type, NOW())
            ");

            $logStmt->execute([
                ':name'   => $reportTitle,
                ':type'   => $dbType
            ]);

            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode('Report generated successfully.'));
            exit;

        } elseif ($action === 'delete_report') {
            $reportId = (int) ($_POST['report_id'] ?? 0);
            if ($reportId > 0) {
                $delStmt = $pdo->prepare("DELETE FROM faculty_db.generated_reports WHERE report_id = :id LIMIT 1");
                $delStmt->execute([':id' => $reportId]);
            }
            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode('Report log entry removed.'));
            exit;
        }
    }

    /*
     * Search, Filter & Pagination Logic for Generated Reports History
     */
    $q = trim((string) ($_GET['q'] ?? ''));
    $filterType = trim((string) ($_GET['type'] ?? ''));

    $limit = 5;
    $page = max(1, (int) ($_GET['page'] ?? 1));

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "report_name LIKE :q";
        $params[':q'] = '%' . $q . '%';
    }

    if ($filterType !== '') {
        $where[] = "report_type = :report_type";
        $params[':report_type'] = $filterType;
    }

    $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

    // Total records count
    $countSql = "SELECT COUNT(*) FROM faculty_db.generated_reports" . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRecords / $limit));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $limit;

    // Fetch reports history
    $sql = "SELECT * FROM faculty_db.generated_reports" . $whereClause . " ORDER BY report_id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // AJAX Handler for dynamic search/filtering
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        
        ob_start();
        if (empty($reports)) {
            echo '<tr><td colspan="4" class="text-center text-muted py-4">No reports found matching your criteria.</td></tr>';
        } else {
            foreach ($reports as $r) {
                $type = $r['report_type'] ?? 'Daily';
                $createdAt = $r['created_at'] ?? ($r['date_generated'] ?? 'N/A');
                
                $typeBadge = match($type) {
                    'Daily'      => 'bg-white text-primary border border-primary-subtle',
                    'Assignment' => 'bg-white text-success border border-success-subtle',
                    default      => 'bg-white text-warning border border-warning-subtle'
                };
                ?>
                <tr>
                    <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars($r['report_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= $typeBadge ?> rounded-pill px-3 py-1 fw-bold"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small font-monospace text-muted"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary border-0" title="View" onclick="viewReport('<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-eye text-primary"></i>
                            </button>
                            <button class="btn btn-outline-secondary border-0" title="Download" onclick="downloadReport('<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-download text-success"></i>
                            </button>
                            <button class="btn btn-outline-secondary border-0" title="Delete" onclick="deleteReportRecord(<?= (int)$r['report_id'] ?>, '<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
        $tableHtml = ob_get_clean();

        // Pagination HTML
        ob_start();
        if ($totalPages > 1) {
            $urlParams = $_GET;
            unset($urlParams['page'], $urlParams['ajax']);
            $baseUrl = '?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page=';
            ?>
            <small class="text-muted">Showing <?= $totalRecords > 0 ? $offset + 1 : 0 ?>-<?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> reports</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $page <= 1 ? '#' : $baseUrl . ($page - 1) ?>" data-page="<?= $page - 1 ?>">Prev</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                            <a class="page-link ajax-page-link" href="<?= $baseUrl . $i ?>" data-page="<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $page >= $totalPages ? '#' : $baseUrl . ($page + 1) ?>" data-page="<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php
        }
        $paginationHtml = ob_get_clean();

        echo json_encode([
            'tableHtml' => $tableHtml,
            'paginationHtml' => $paginationHtml,
            'totalRecords' => $totalRecords
        ]);
        exit;
    }

} catch (Throwable $e) {
    $formError = $e->getMessage();
    error_log('[reports] ' . $e->getMessage());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-1 fs-5 fs-md-4">
            <i class="fas fa-file-alt text-primary me-2"></i>Reports
        </h2>
        <p class="text-muted small mb-0 fs-7 fs-md-6">Generate daily, assignment monitoring, and document status reports</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary text-truncate" data-bs-toggle="modal" data-bs-target="#customReportModal">
            <i class="fas fa-plus me-1"></i><span class="d-inline d-sm-none">Custom Builder</span><span class="d-none d-sm-inline">Custom Report Builder</span>
        </button>
    </div>
</div>

<?php if ($formError !== ''): ?>
    <div class="alert alert-danger rounded-3 mb-4" role="alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
    <div class="alert alert-success rounded-3 mb-4" role="alert"><?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Report Type Cards -->
<div class="row g-3 mb-4">
    <!-- Daily Reports -->
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border">
            <div class="card-body p-3 p-md-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 fs-5" style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark fs-6">Daily Reports</h6>
                        <small class="text-muted fs-7">Real-time operational summaries</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-primary"></i>Daily Activity Log</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-primary"></i>Daily Leave Summary</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-primary"></i>Daily Document Updates</li>
                    </ul>
                </div>
                <button class="btn btn-outline-primary w-100 fw-semibold text-truncate" onclick="openGenerateModal('Daily', 'Daily Activity Log')">
                    <i class="fas fa-cog me-1"></i>Generate Daily
                </button>
            </div>
        </div>
    </div>

    <!-- Assignment Monitoring Reports -->
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border">
            <div class="card-body p-3 p-md-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-3 fs-5" style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark fs-6">Assignment Monitoring</h6>
                        <small class="text-muted fs-7">Faculty workload & tasks</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-success"></i>Faculty Workload Summary</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-success"></i>Subject Assignment Status</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-success"></i>Teaching Load Distribution</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-success"></i>Unassigned Faculty Report</li>
                    </ul>
                </div>
                <button class="btn btn-outline-success w-100 fw-semibold text-truncate" onclick="openGenerateModal('Assignment', 'Faculty Workload Summary')">
                    <i class="fas fa-cog me-1"></i>Generate Assignment
                </button>
            </div>
        </div>
    </div>

    <!-- Document Reports -->
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border">
            <div class="card-body p-3 p-md-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-3 fs-5" style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark fs-6">Document Reports</h6>
                        <small class="text-muted fs-7">Compliance & status logs</small>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-3 mb-4 flex-grow-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-warning"></i>Document Status Summary</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-warning"></i>Expiring Documents Report</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-warning"></i>Missing Documents Report</li>
                        <li class="d-flex align-items-center gap-2 small text-secondary"><i class="fas fa-check-circle text-warning"></i>Document Audit Trail</li>
                    </ul>
                </div>
                <button class="btn btn-outline-warning text-dark w-100 fw-semibold text-truncate" onclick="openGenerateModal('Document', 'Document Status Summary')">
                    <i class="fas fa-cog me-1"></i>Generate Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report History Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold text-dark fs-6"><i class="fas fa-history text-primary me-2"></i>Generated Reports History</h6>
        <div class="d-flex gap-2 w-100 w-md-auto flex-wrap flex-sm-nowrap">
            <div class="input-group input-group-sm flex-grow-1 flex-md-grow-0" style="width: 100%; max-width: 220px;">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="reportSearch" class="form-control" placeholder="Search reports..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <select id="reportTypeFilter" class="form-select form-select-sm w-auto flex-grow-1 flex-md-grow-0">
                <option value="">All Types</option>
                <option value="Daily" <?= $filterType === 'Daily' ? 'selected' : '' ?>>Daily</option>
                <option value="Assignment" <?= $filterType === 'Assignment' ? 'selected' : '' ?>>Assignment</option>
                <option value="Document" <?= $filterType === 'Document' ? 'selected' : '' ?>>Document</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Report Name</th>
                        <th>Type</th>
                        <th>Date Generated</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No reports found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): 
                            $type = $r['report_type'] ?? 'Daily';
                            $createdAt = $r['created_at'] ?? ($r['date_generated'] ?? 'N/A');

                            $typeBadge = match($type) {
                                'Daily'      => 'bg-white text-primary border border-primary-subtle',
                                'Assignment' => 'bg-white text-success border border-success-subtle',
                                default      => 'bg-white text-warning border border-warning-subtle'
                            };
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-dark text-truncate" style="max-width: 180px;"><?= htmlspecialchars($r['report_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $typeBadge ?> rounded-pill px-2 px-md-3 py-1 fw-bold"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="small font-monospace text-muted text-nowrap"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary border-0" title="View" onclick="viewReport('<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-eye text-primary"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary border-0" title="Download" onclick="downloadReport('<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-download text-success"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary border-0" title="Delete" onclick="deleteReportRecord(<?= (int)$r['report_id'] ?>, '<?= htmlspecialchars(addslashes($r['report_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-trash text-danger"></i>
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
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 flex-wrap gap-2" id="paginationContainer">
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <?php
            $urlParams = $_GET;
            unset($urlParams['page']);
            $baseUrl = '?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page=';
            ?>
            <small class="text-muted">Showing <?= $totalRecords > 0 ? $offset + 1 : 0 ?>-<?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> reports</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $page <= 1 ? '#' : $baseUrl . ($page - 1) ?>" data-page="<?= $page - 1 ?>">Prev</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                            <a class="page-link ajax-page-link" href="<?= $baseUrl . $i ?>" data-page="<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link ajax-page-link" href="<?= $page >= $totalPages ? '#' : $baseUrl . ($page + 1) ?>" data-page="<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php else: ?>
            <small class="text-muted">Showing <?= $totalRecords ?? 0 ?> of <?= $totalRecords ?? 0 ?> reports</small>
            <div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Report Modal -->
<form id="standardReportForm" method="POST">
    <input type="hidden" name="action" value="generate_standard">
    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow bg-body text-body">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title h6 fw-bold"><i class="fas fa-cog text-primary me-2"></i>Generate Standard Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Report Category</label>
                        <select class="form-select form-select-sm bg-body text-body" name="report_type" id="reportTypeCategory" onchange="updateSpecificReports(this.value)">
                            <option value="Daily">Daily Report</option>
                            <option value="Assignment">Assignment Monitoring</option>
                            <option value="Document">Document Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Specific Report Title</label>
                        <select class="form-select form-select-sm bg-body text-body" name="report_title" id="specificReport">
                            <option>Daily Activity Log</option>
                            <option>Daily Leave Summary</option>
                            <option>Daily Document Updates</option>
                        </select>
                    </div>
                    <div class="mb-3" id="dateRangeContainer" style="display: none;">
                        <label class="form-label small fw-semibold">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm bg-body text-body" name="start_date" id="startDate">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm bg-body text-body" name="end_date" id="endDate">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Output Format</label>
                        <select class="form-select form-select-sm bg-body text-body" name="output_format">
                            <option value="PDF">PDF</option>
                            <option value="Excel">Excel</option>
                            <option value="CSV">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-cog me-1"></i>Generate Report</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Custom Report Modal -->
<form id="customReportForm" method="POST">
    <input type="hidden" name="action" value="generate_custom">
    <input type="hidden" name="report_type" value="Custom">
    <div class="modal fade" id="customReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow bg-body text-body">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title h6 fw-bold"><i class="fas fa-sliders-h text-primary me-2"></i>Custom Report Builder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Report Title</label>
                            <input type="text" class="form-control form-control-sm bg-body text-body" name="report_title" placeholder="e.g., Q3 Faculty Workload & Assignment Summary" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Date Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm bg-body text-body" name="custom_start_date">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm bg-body text-body" name="custom_end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block">Data Sources</label>
                        <div class="row g-2 p-3 bg-light dark-mode-bg-subtle rounded-3">
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ds1" name="data_sources[]" value="Assignment Records" checked>
                                    <label class="form-check-label small" for="ds1">Assignment Records</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ds2" name="data_sources[]" value="Teaching Workload" checked>
                                    <label class="form-check-label small" for="ds2">Teaching Workload</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ds3" name="data_sources[]" value="Document Compliance Data">
                                    <label class="form-check-label small" for="ds3">Document Compliance Data</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ds4" name="data_sources[]" value="Faculty Profile Information">
                                    <label class="form-check-label small" for="ds4">Faculty Profile Information</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Department Filter</label>
                            <select class="form-select form-select-sm bg-body text-body" name="department_filter">
                                <option value="">All Departments</option>
                                <option selected>College of Computer Studies</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Faculty Status</label>
                            <select class="form-select form-select-sm bg-body text-body" name="faculty_status">
                                <option value="">All Faculty Types</option>
                                <option>Full-time Faculty</option>
                                <option>Part-time Faculty</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Output Format</label>
                            <select class="form-select form-select-sm bg-body text-body" name="output_format">
                                <option value="PDF">PDF Document (.pdf)</option>
                                <option value="Excel">Excel Spreadsheet (.xlsx)</option>
                                <option value="CSV">CSV Format (.csv)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-magic me-1"></i>Generate Custom Report</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- View Report Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow bg-body text-body">
            <div class="modal-header border-bottom">
                <h5 class="modal-title h6 fw-bold">
                    <i class="fas fa-eye text-primary me-2"></i>Report Preview: <span id="viewReportTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3 text-primary fs-1">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h6 class="fw-semibold text-body" id="viewReportNameDisplay"></h6>
                <p class="text-muted small mb-0">Preview mode is active. You can review or download the generated output below.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-success" onclick="downloadReport(document.getElementById('viewReportTitle').textContent)">
                    <i class="fas fa-download me-1"></i>Download Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Action Form -->
<form id="deleteReportForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_report">
    <input type="hidden" name="report_id" id="del-report-id">
</form>

<script>
function openGenerateModal(category, defaultTitle) {
    const modal = new bootstrap.Modal(document.getElementById('generateModal'));
    document.getElementById('reportTypeCategory').value = category;
    updateSpecificReports(category);
    document.getElementById('specificReport').value = defaultTitle;
    modal.show();
}

function updateSpecificReports(category) {
    const select = document.getElementById('specificReport');
    const dateRangeContainer = document.getElementById('dateRangeContainer');
    
    select.innerHTML = '';
    
    let options = [];
    if (category === 'Daily') {
        options = ['Daily Activity Log', 'Daily Leave Summary', 'Daily Document Updates'];
        if (dateRangeContainer) dateRangeContainer.style.display = 'none'; // Hide date range for daily reports
    } else {
        if (dateRangeContainer) dateRangeContainer.style.display = 'block'; // Show for custom/assignment/document
        
        if (category === 'Assignment') {
            options = ['Faculty Workload Summary', 'Subject Assignment Status', 'Teaching Load Distribution', 'Unassigned Faculty Report'];
        } else if (category === 'Document') {
            options = ['Document Status Summary', 'Expiring Documents Report', 'Missing Documents Report', 'Document Audit Trail'];
        }
    }
    
    options.forEach(opt => {
        const el = document.createElement('option');
        el.value = opt;
        el.textContent = opt;
        select.appendChild(el);
    });
}

function viewReport(name) {
    document.getElementById('viewReportTitle').textContent = name;
    document.getElementById('viewReportNameDisplay').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    modal.show();
}

function downloadReport(name) {
    alert('Downloading report: ' + name);
}

function deleteReportRecord(id, name) {
    if (confirm('Are you sure you want to delete this historical report entry: ' + name + '?')) {
        document.getElementById('del-report-id').value = id;
        document.getElementById('deleteReportForm').submit();
    }
}

// AJAX Live Search and Filtering for Reports History
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('reportSearch');
    const typeFilter = document.getElementById('reportTypeFilter');
    let searchTimeout = null;

    // Initialize date container state on load based on default dropdown value
    updateSpecificReports(document.getElementById('reportTypeCategory').value);

    function fetchReports(page = 1) {
        const q = searchInput.value.trim();
        const type = typeFilter.value;

        const newUrl = '?' + new URLSearchParams({
            q: q,
            type: type,
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
            document.getElementById('reportsTableBody').innerHTML = data.tableHtml;
            document.getElementById('paginationContainer').innerHTML = data.paginationHtml;
            attachPaginationListeners();
        })
        .catch(err => console.error('Error filtering reports:', err));
    }

    function attachPaginationListeners() {
        document.querySelectorAll('.ajax-page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page) {
                    fetchReports(page);
                }
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchReports(1);
            }, 400);
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            fetchReports(1);
        });
    }

    attachPaginationListeners();
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>