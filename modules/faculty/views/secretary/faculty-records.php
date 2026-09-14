<?php
/**
 * Faculty Records
 * Purpose: View and update active faculty information for the Secretary's department only.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

// 1. Fetch base scoped faculty list
$facultyList = getScopedFacultyList();

// 2. Resolve the logged-in Secretary's department
$userDepartment = trim((string) (
    $_SESSION['department_name']
    ?? $_SESSION['user_department']
    ?? $_SESSION['designated_department']
    ?? $_SESSION['department']
    ?? $_SESSION['dept_name']
    ?? $_SESSION['dept']
    ?? $_SESSION['user']['department_name']
    ?? $_SESSION['user']['department']
    ?? ''
));

// Fallback lookup via DB if session department isn't populated
if ($userDepartment === '') {
    $sessionUserId = (int) (
        $_SESSION['user_id']
        ?? $_SESSION['user']['id']
        ?? $_SESSION['id']
        ?? $_SESSION['account_id']
        ?? 0
    );
    $sessionEmail = $_SESSION['user_email']
        ?? $_SESSION['user']['email']
        ?? $_SESSION['email']
        ?? null;

    if ($sessionUserId || $sessionEmail) {
        try {
            $pdo = function_exists('facultyDb') ? facultyDb() : ($conn ?? $db ?? null);
            if ($pdo) {
                $stmtUser = $pdo->prepare("
                    SELECT designated_department, department
                    FROM faculty_db.faculty_profiles
                    WHERE user_id = :uid
                       OR (:email1 IS NOT NULL AND email = :email2)
                    LIMIT 1
                ");
                $stmtUser->execute([
                    'uid'    => $sessionUserId,
                    'email1' => $sessionEmail,
                    'email2' => $sessionEmail
                ]);
                $prof = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($prof) {
                    $userDepartment = trim($prof['designated_department'] ?? $prof['department'] ?? '');
                    if ($userDepartment !== '') {
                        $_SESSION['department_name'] = $userDepartment;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('faculty-records.php department resolution error: ' . $e->getMessage());
        }
    }
}

/**
 * Normalizes department string into uniform codes
 */
function normalizeDepartmentCode(string $dept): string {
    $deptUpper = strtoupper(trim($dept));
    if ($deptUpper === '') return '';

    if (str_contains($deptUpper, 'INFORMATION TECHNOLOGY') || str_contains($deptUpper, 'BSIT') || $deptUpper === 'IT') {
        return 'BSIT';
    }
    if (str_contains($deptUpper, 'BUSINESS ADMINISTRATION') || str_contains($deptUpper, 'BSBA')) {
        return 'BSBA';
    }
    if (str_contains($deptUpper, 'CRIMINOLOGY') || str_contains($deptUpper, 'BS CRIM') || str_contains($deptUpper, 'BSCRIM')) {
        return 'BS CRIM';
    }
    if (str_contains($deptUpper, 'COMPUTER SCIENCE') || str_contains($deptUpper, 'BSCS')) {
        return 'BSCS';
    }
    if (str_contains($deptUpper, 'EDUCATION') || str_contains($deptUpper, 'BSED') || str_contains($deptUpper, 'BEED')) {
        return 'EDUC';
    }
    if (str_contains($deptUpper, 'HOSPITALITY') || str_contains($deptUpper, 'BSHM')) {
        return 'BSHM';
    }

    return preg_replace('/[^A-Z0-9]/', '', $deptUpper);
}

// 3. Strict Filtering: Department Scoping + Exclude Dean/Dept Head + Exclude Pending/Rejected
$targetCode = normalizeDepartmentCode($userDepartment !== '' ? $userDepartment : 'BSIT');

$facultyList = array_values(array_filter($facultyList, function ($f) use ($targetCode) {
    // --- A. Department Filtering ---
    $rawDept = $f['designated_department'] 
        ?? $f['department'] 
        ?? $f['department_name'] 
        ?? $f['dept'] 
        ?? $f['dept_name'] 
        ?? '';
    $recordCode = normalizeDepartmentCode((string)$rawDept);
    if ($recordCode === '' || $targetCode === '' || $recordCode !== $targetCode) {
        return false;
    }

    // --- B. Status Filtering (Exclude Pending, Rejected, Resigned) ---
    $status = strtolower(trim((string) ($f['profile_status'] ?? $f['employment_status'] ?? $f['status'] ?? 'active')));
    if (in_array($status, ['pending approval', 'pending', 'rejected', 'resigned'], true)) {
        return false;
    }

    // --- C. Role Filtering (Exclude Dean & Department Head) ---
    $role = strtolower(trim((string) ($f['role'] ?? $f['position'] ?? $f['user_role'] ?? $f['designation'] ?? '')));
    $email = strtolower(trim((string) ($f['email'] ?? '')));
    $firstName = strtolower(trim((string) ($f['first_name'] ?? '')));
    $lastName = strtolower(trim((string) ($f['last_name'] ?? '')));

    if (
        str_contains($role, 'dean') || 
        str_contains($role, 'department head') || 
        str_contains($role, 'dept head') || 
        str_contains($role, 'head') ||
        str_contains($email, 'dean') ||
        str_contains($email, 'depthead') ||
        str_contains($firstName, 'dean') ||
        str_contains($lastName, 'dean')
    ) {
        return false;
    }

    return true;
}));

// 4. UI Search and Status Dropdown Filters
$searchTerm   = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

if ($searchTerm !== '') {
    $needle = strtolower($searchTerm);
    $facultyList = array_values(array_filter($facultyList, function ($f) use ($needle) {
        $haystack = strtolower(trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? '') . ' ' . ($f['faculty_id'] ?? '')));
        return str_contains($haystack, $needle);
    }));
}

if ($statusFilter !== '') {
    $facultyList = array_values(array_filter($facultyList, function ($f) use ($statusFilter) {
        return strcasecmp((string) ($f['employment_status'] ?? ''), $statusFilter) === 0;
    }));
}

$facultyCount = count($facultyList);

// 5. Pagination
$perPage     = 10;
$facultyPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($facultyPage < 1) { $facultyPage = 1; }
$totalPages  = max(1, (int) ceil($facultyCount / $perPage));
if ($facultyPage > $totalPages) { $facultyPage = $totalPages; }
$offset      = ($facultyPage - 1) * $perPage;
$pagedFacultyList = array_slice($facultyList, $offset, $perPage);

function renderFacultyRows(array $facultyList, string $searchTerm): string {
    ob_start();
    if (empty($facultyList)) {
        ?>
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">
                No active faculty records found<?= $searchTerm !== '' ? ' matching your search.' : ' in your department.' ?>
            </td>
        </tr>
        <?php
    }
    foreach ($facultyList as $f) {
        $fullName    = trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
        $facultyId   = (string) ($f['faculty_id'] ?? '');
        $dept        = (string) ($f['designated_department'] ?? $f['department'] ?? $f['dept'] ?? '—');
        $phone       = (string) ($f['phone'] ?? '—');
        $email       = (string) ($f['email'] ?? '—');
        $status      = (string) ($f['profile_status'] ?? $f['employment_status'] ?? 'Active');
        
        $statusClass = 'badge bg-success';
        if (strcasecmp($status, 'Pending Approval') === 0) {
            $statusClass = 'badge bg-warning text-dark';
        } elseif (strcasecmp($status, 'Rejected') === 0 || strcasecmp($status, 'Resigned') === 0) {
            $statusClass = 'badge bg-danger';
        }

        $initials = implode('', array_slice(array_map(function ($part) {
            return strtoupper(substr($part, 0, 1));
        }, array_filter(explode(' ', $fullName))), 0, 2));
        if ($initials === '') { $initials = 'FA'; }

        $nameEsc   = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $idEsc     = htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8');
        $deptEsc   = htmlspecialchars($dept, ENT_QUOTES, 'UTF-8');
        $phoneEsc  = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $emailEsc  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $statusEsc = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        $initEsc   = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <tr>
            <td class="ps-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:40px; height:40px;">
                        {$initEsc}
                    </div>
                    <div>
                        <div class="fw-bold">{$nameEsc}</div>
                        <div class="text-muted small">ID: {$idEsc}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge border border-primary text-primary fw-medium px-2 py-1">{$deptEsc}</span></td>
            <td>
                <div class="fw-semibold">{$phoneEsc}</div>
                <div class="text-muted small">{$emailEsc}</div>
            </td>
            <td><span class="{$statusClass}">{$statusEsc}</span></td>
            <td class="text-end pe-3">
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn btn-sm btn-outline-primary" title="View Profile" onclick="viewProfile('{$idEsc}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
        HTML;
    }
    return ob_get_clean();
}

function renderFacultyPagination(int $page, int $totalPages): string {
    ob_start();
    ?>
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="#" onclick="event.preventDefault(); fetchFacultyPage(<?= $page - 1 ?>)">Previous</a>
    </li>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
            <a class="page-link" href="#" onclick="event.preventDefault(); fetchFacultyPage(<?= $i ?>)"><?= $i ?></a>
        </li>
    <?php endfor; ?>
    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="#" onclick="event.preventDefault(); fetchFacultyPage(<?= $page + 1 ?>)">Next</a>
    </li>
    <?php
    return ob_get_clean();
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    $start = $facultyCount > 0 ? $offset + 1 : 0;
    $end   = min($offset + $perPage, $facultyCount);
    header('Content-Type: application/json');
    echo json_encode([
        'tbody'      => renderFacultyRows($pagedFacultyList, $searchTerm),
        'pagination' => renderFacultyPagination($facultyPage, $totalPages),
        'count'      => $facultyCount,
        'rangeText'  => "Showing {$start} to {$end} of {$facultyCount} entries",
    ]);
    exit;
}

$pageTitle    = 'Faculty Records';
$activeModule = 'faculty';
$activePage   = 'faculty-records';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Faculty Records', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            <i class="fas fa-id-badge text-primary me-2"></i>Faculty Records
        </h1>
        <p class="text-muted mb-0 small">Manage profile and contact information in your department directory</p>
    </div>
    <div>
        <button class="btn btn-success btn-sm">
            <i class="fas fa-file-excel me-1"></i> Export Directory
        </button>
    </div>
</div>

<div class="container-fluid px-0">
    <div class="row g-4">
        <!-- Left Column: Filter Records Card -->
        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="card-title fw-bold mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i>Filter Records</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" id="facultyFilterForm" onsubmit="event.preventDefault(); fetchFacultyPage(1);">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Search Faculty</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0">
                                    <i class="fas fa-search text-muted small"></i>
                                </span>
                                <input type="text" name="search" id="facultySearchInput" class="form-control border-start-0 bg-body-tertiary" placeholder="Search name or ID..." value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>" oninput="triggerFacultyAutoSearch()">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Employment Status</label>
                            <select name="status" id="facultyStatusSelect" class="form-select bg-body-tertiary">
                                <option value="">All Statuses</option>
                                <?php foreach (['Regular', 'Probationary', 'Part-Time'] as $statusOpt): ?>
                                    <option value="<?= $statusOpt ?>" <?= strcasecmp($statusFilter, $statusOpt) === 0 ? 'selected' : '' ?>><?= $statusOpt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFacultyFilters()">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Faculty Directory Table Card -->
        <div class="col-lg-8 col-xl-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title fw-bold mb-0">
                        <i class="fas fa-users me-2 text-primary"></i>Faculty Directory
                        <span class="badge bg-success ms-2" id="facultyCountBadge"><?= (int) $facultyCount ?> Registered</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Show:</span>
                        <select class="form-select form-select-sm bg-body-tertiary" style="width: auto;">
                            <option>10 per page</option>
                            <option>25 per page</option>
                            <option>50 per page</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Faculty Member</th>
                                    <th>Dept</th>
                                    <th>Contact Information</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="facultyTableBody">
                                <?= renderFacultyRows($pagedFacultyList, $searchTerm) ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted" id="facultyRangeText"><?php
                        $start = $facultyCount > 0 ? $offset + 1 : 0;
                        $end   = min($offset + $perPage, $facultyCount);
                        echo "Showing {$start} to {$end} of {$facultyCount} entries";
                    ?></small>
                    <nav aria-label="Faculty pagination">
                        <ul class="pagination pagination-sm mb-0" id="facultyPagination">
                            <?= renderFacultyPagination($facultyPage, $totalPages) ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW PROFILE MODAL -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-id-card me-2 text-primary"></i>Faculty Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-4 text-center border-end">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold mx-auto mb-3 shadow-sm" style="width:80px; height:80px; font-size:1.8rem;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h5 class="fw-bold mb-1" id="modalName">Faculty Member</h5>
                            <p class="text-muted small mb-3">Instructor</p>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="col-md-8">
                            <h6 class="text-primary border-bottom pb-2 fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Contact Details</h6>
                            <div class="p-3 border rounded bg-body-tertiary">
                                <div class="mb-2"><strong>Faculty ID:</strong> F-001</div>
                                <div class="mb-2"><strong>Email:</strong> faculty@bestlink.edu.ph</div>
                                <div class="mb-2"><strong>Contact:</strong> +63 912 345 6789</div>
                                <div><strong>Department:</strong> <?= htmlspecialchars($userDepartment ?: 'BSIT', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewProfile(id) {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    modal.show();
}

let facultySearchDebounce = null;

function triggerFacultyAutoSearch() {
    clearTimeout(facultySearchDebounce);
    facultySearchDebounce = setTimeout(function() {
        fetchFacultyPage(1);
    }, 300);
}

function fetchFacultyPage(page) {
    if (page < 1) return;

    const search = document.getElementById('facultySearchInput').value;
    const status = document.getElementById('facultyStatusSelect').value;
    const params = new URLSearchParams({ search: search, status: status, page: page });

    fetch('?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('facultyTableBody').innerHTML = data.tbody;
        document.getElementById('facultyPagination').innerHTML = data.pagination;
        document.getElementById('facultyRangeText').textContent = data.rangeText;
        document.getElementById('facultyCountBadge').textContent = data.count + ' Registered';
    })
    .catch(error => console.error('Error updating faculty records:', error));
}

function resetFacultyFilters() {
    document.getElementById('facultySearchInput').value = '';
    document.getElementById('facultyStatusSelect').value = '';
    fetchFacultyPage(1);
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>