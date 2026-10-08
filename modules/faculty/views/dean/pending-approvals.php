<?php
/**
 * SMS 2 - Dean - Pending Approvals
 * Central approval queue — leaves and clearances only.
 * RBAC scoped to the dean's assigned departments. Server-side only.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';
require_once __DIR__ . '/../../includes/dean_rbac.php';

requireAuth();

/* ============================================================
   DEAN RBAC
   ============================================================ */
$deanUserId          = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);
$deanDepartments     = getDeanAssignedDepartments($deanUserId);
$deanDepartmentNames = getDeanDepartmentNames($deanDepartments);
$hasDeanDepartments  = !empty($deanDepartments);

/* ============================================================
   DATABASE
   ============================================================ */
$pdo = null;
if (function_exists('facultyDb')) { $pdo = facultyDb(); }
if (!$pdo instanceof PDO && function_exists('db')) {
    try { $pdo = db(); } catch (Throwable $e) { $pdo = null; }
}
if (!$pdo instanceof PDO && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
}

/* ============================================================
   HANDLE ACTION (Approve / Reject) — leaves + clearances only
   ============================================================ */
$actionMessage = '';
$actionType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['item_id'], $_POST['item_type'])) {
    if (!$hasDeanDepartments) {
        $actionMessage = 'No departments assigned.';
        $actionType = 'danger';
    } else {
        try {
            $itemId   = (int) $_POST['item_id'];
            $itemType = (string) $_POST['item_type'];
            $decision = (string) ($_POST['decision'] ?? '');
            $remarks  = trim((string) ($_POST['remarks'] ?? ''));
            $deptList = implode(',', array_map('intval', $deanDepartments));

            if (!in_array($decision, ['approve','reject'], true)) {
                throw new InvalidArgumentException('Invalid decision.');
            }
            if (!in_array($itemType, ['leave','clearance'], true)) {
                throw new InvalidArgumentException('Invalid item type.');
            }

            if ($itemType === 'leave') {
                $newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.leave_requests lr
                    INNER JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
                    SET lr.approval_status = :st,
                        lr.approver_comment = :cm,
                        lr.approved_by_external_id = :uid,
                        lr.approved_at = NOW()
                    WHERE lr.id = :id
                      AND f.department_id IN ($deptList)
                ");
                $stmt->execute([
                    ':st'  => $newStatus,
                    ':cm'  => $remarks,
                    ':uid' => (string) $deanUserId,
                    ':id'  => $itemId,
                ]);
                if ($stmt->rowCount() === 0) throw new RuntimeException('Item not found or out of scope.');
                $actionMessage = 'Leave ' . strtolower($newStatus) . ' successfully.';

            } elseif ($itemType === 'clearance') {
                $newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
                $stmt = $pdo->prepare("
                    UPDATE faculty_db.clearance_items ci
                    INNER JOIN faculty_db.clearance_requests cr ON cr.clearance_id = ci.clearance_id
                    INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
                    SET ci.status = :st,
                        ci.remarks = :cm,
                        ci.cleared_by_external_id = :uid,
                        ci.cleared_at = NOW()
                    WHERE ci.clearance_item_id = :id
                      AND f.department_id IN ($deptList)
                ");
                $stmt->execute([
                    ':st'  => $newStatus,
                    ':cm'  => $remarks,
                    ':uid' => (string) $deanUserId,
                    ':id'  => $itemId,
                ]);
                if ($stmt->rowCount() === 0) throw new RuntimeException('Item not found or out of scope.');
                $actionMessage = 'Clearance ' . strtolower($newStatus) . ' successfully.';
            }

            $actionType = 'success';

        } catch (Throwable $e) {
            error_log('[pending-approvals] action failed: ' . $e->getMessage());
            $actionMessage = $e->getMessage();
            $actionType = 'danger';
        }
    }
}

/* ============================================================
   LOAD PENDING QUEUE — leaves + clearances, scoped to dean
   ============================================================ */
$pendingLeaves      = [];
$pendingClearances  = [];
$recentActions      = [];
$loadError          = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* ---- Pending leaves ---- */
        $leafStmt = $pdo->query("
            SELECT
                lr.id, lr.request_ref, lr.faculty_id, lr.leave_type,
                lr.start_date, lr.end_date, lr.total_days, lr.reason,
                lr.approval_status, lr.created_at,
                f.first_name, f.middle_name, f.last_name,
                f.faculty_no, f.department_id,
                d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.leave_requests lr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND lr.approval_status = 'Pending'
            ORDER BY lr.created_at DESC
            LIMIT 200
        ");
        $pendingLeaves = $leafStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Pending clearances (department office only) ---- */
        $clrStmt = $pdo->query("
            SELECT
                ci.clearance_item_id, ci.clearance_id, ci.status,
                ci.remarks, ci.cleared_at,
                co.name AS office_name, co.sequence_order,
                cr.clearance_no, cr.submitted_at,
                f.faculty_id, f.faculty_no, f.first_name, f.middle_name, f.last_name,
                f.department_id,
                d.code AS dept_code, d.name AS dept_name
            FROM faculty_db.clearance_items ci
            INNER JOIN faculty_db.clearance_requests cr ON cr.clearance_id = ci.clearance_id
            INNER JOIN faculty_db.clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
            INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            WHERE f.department_id IN ($deptList)
              AND co.name LIKE '%Department%'
              AND ci.status IN ('Missing', 'Pending')
            ORDER BY cr.submitted_at DESC
            LIMIT 200
        ");
        $pendingClearances = $clrStmt->fetchAll(PDO::FETCH_ASSOC);

        /* ---- Recent actions (last 30 days) — leaves only now ---- */
        $recentStmt = $pdo->query("
            SELECT
                'leave' AS item_type,
                lr.id AS item_id,
                lr.request_ref AS ref_no,
                lr.approval_status AS status,
                lr.approved_at AS acted_at,
                lr.approver_comment AS remarks,
                CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                lr.leave_type AS subject
            FROM faculty_db.leave_requests lr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = lr.faculty_id
            WHERE f.department_id IN ($deptList)
              AND lr.approval_status IN ('Approved','Rejected')
              AND lr.approved_at IS NOT NULL
              AND lr.approved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY lr.approved_at DESC
            LIMIT 30
        ");
        $recentActions = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log('Pending approvals load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Pending Approvals';
$activeModule = 'faculty';
$activePage   = 'pending-approvals';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Pending Approvals', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="paPage">

    <?php if (!$hasDeanDepartments): ?>
        <div class="card border shadow-sm">
            <div class="card-body text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:84px;height:84px;background:rgba(100,116,139,0.10);color:#64748b;font-size:2.2rem;">
                    <i class="fas fa-building-circle-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>
                <p class="text-body-secondary mb-0" style="max-width:420px;margin:0 auto;">
                    Your account doesn't have any department access yet. Contact your administrator
                    to assign you one or more departments.
                </p>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../../../includes/layout-end.php'; exit; ?>
    <?php endif; ?>

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($loadError) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($actionMessage): ?>
        <div class="alert alert-<?= htmlspecialchars($actionType) ?> d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-<?= $actionType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <div><?= htmlspecialchars($actionMessage) ?></div>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Pending Approvals Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-hourglass-half text-primary"></i>
                <span>Pending Approvals</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Leave requests and clearances waiting for your action across the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="exportCsvBtn">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">CSV</span>
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="printBtn">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= KPI Cards (3 cards now) ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-12 col-md-4">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Pending</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">Awaiting action</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-md-4">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-plane-departure"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Leave Requests</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiLeaves">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">Pending endorsement</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-md-4">
            <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Clearances</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiClearances">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Need your sign-off</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <?php if (count($deanDepartments) > 1): ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_dept">Department</label>
                    <select class="form-select form-select-sm" id="f_dept" data-filter="dept">
                        <option value="all">All my departments</option>
                        <?php foreach ($deanDepartments as $deptId): ?>
                            <option value="<?= (int) $deptId ?>">
                                <?= htmlspecialchars($deanDepartmentNames[$deptId] ?? ('Dept #' . $deptId)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_type">Type</label>
                    <select class="form-select form-select-sm" id="f_type" data-filter="type">
                        <option value="all">All types</option>
                        <option value="leave">Leave Requests</option>
                        <option value="clearance">Clearances</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_age">Age</label>
                    <select class="form-select form-select-sm" id="f_age" data-filter="age">
                        <option value="all">All ages</option>
                        <option value="1">Today</option>
                        <option value="7">This week (≤7 days)</option>
                        <option value="30">This month (≤30 days)</option>
                        <option value="old">Stale (>30 days)</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_q">Search</label>
                    <input type="text" class="form-control form-control-sm" id="f_q" data-filter="q" placeholder="Name or ref no." autocomplete="off">
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Worklist (3 tabs now) ================= -->
    <div class="card border shadow-sm overflow-hidden mb-4 no-print">
        <div class="card-header bg-transparent border-bottom p-0">
            <div class="d-flex flex-wrap" role="tablist">
                <button class="pa-tab pa-tab--active" data-tab="all" type="button" role="tab">
                    <i class="fas fa-list-check me-2"></i>All
                    <span class="pa-tab__count" id="tabCountAll">0</span>
                </button>
                <button class="pa-tab" data-tab="leave" type="button" role="tab">
                    <i class="fas fa-plane-departure me-2"></i>Leaves
                    <span class="pa-tab__count" id="tabCountLeave">0</span>
                </button>
                <button class="pa-tab" data-tab="clearance" type="button" role="tab">
                    <i class="fas fa-clipboard-check me-2"></i>Clearances
                    <span class="pa-tab__count" id="tabCountClearance">0</span>
                </button>
            </div>
        </div>

        <div class="pa-queue" id="queueBody"></div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3">
            <div class="small text-body-secondary" id="queueSubtitle">Loading…</div>
            <nav aria-label="Queue pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="queuePager"></ul>
            </nav>
        </div>
    </div>

    <!-- ================= Recent Actions ================= -->
    <div class="card border shadow-sm overflow-hidden no-print">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold">Recent Actions</h5>
                <p class="text-body-secondary small mb-0">What you've processed in the last 30 days</p>
            </div>
            <span class="badge text-bg-light border" id="recentCount">0</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 pa-recent-table">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-body-secondary">Date</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">Type</th>
                        <th class="text-uppercase small fw-bold text-body-secondary d-none d-sm-table-cell">Subject</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Decision</th>
                    </tr>
                </thead>
                <tbody id="recentBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="pa-print-only" id="printReport" aria-hidden="true"></div>

<!-- Confirmation modal -->
<div class="modal fade" id="paActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="paModalTitle">
                    <i class="fas fa-check-circle text-success"></i>
                    <span>Confirm Action</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="process_approval">
                    <input type="hidden" name="item_id" id="paItemId" value="">
                    <input type="hidden" name="item_type" id="paItemType" value="">
                    <input type="hidden" name="decision" id="paDecision" value="">

                    <p class="text-body-secondary mb-3" id="paModalMessage">
                        Are you sure you want to proceed?
                    </p>

                    <label class="form-label small text-uppercase fw-bold text-body-secondary" for="paRemarks">
                        Remarks (optional)
                    </label>
                    <textarea class="form-control" id="paRemarks" name="remarks" rows="3" placeholder="Add notes for the faculty…"></textarea>
                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm rounded-3 px-4 fw-bold" id="paModalSubmit">
                        <i class="fas fa-check me-1"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .pa-tab {
        background: transparent;
        border: none;
        padding: 0.9rem 1.15rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--sms-text-muted);
        border-bottom: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        transition: color 0.15s ease, border-color 0.15s ease;
    }
    .pa-tab:hover { color: var(--sms-text-strong); }
    .pa-tab--active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }
    .pa-tab__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 20px;
        padding: 0 0.4rem;
        border-radius: 999px;
        background: var(--sms-surface-muted);
        color: var(--sms-text-muted);
        font-size: 0.68rem;
        font-weight: 800;
        margin-left: 0.4rem;
    }
    .pa-tab--active .pa-tab__count {
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
    }

    .pa-queue { min-height: 200px; }
    .pa-item {
        display: grid;
        grid-template-columns: 44px 1fr auto;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--sms-border-soft);
        align-items: start;
        transition: background 0.15s ease;
    }
    .pa-item:last-child { border-bottom: none; }
    .pa-item:hover { background: var(--sms-dropdown-hover); }

    .pa-item__icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .pa-item__icon--leave     { background: rgba(16,185,129,0.14); color: #10b981; }
    .pa-item__icon--clearance { background: rgba(245,158,11,0.14); color: #f59e0b; }

    .pa-item__body { min-width: 0; }
    .pa-item__title {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--sms-heading);
        margin: 0 0 0.15rem;
        overflow-wrap: anywhere;
    }
    .pa-item__sub {
        font-size: 0.78rem;
        color: var(--sms-text-muted);
        margin-bottom: 0.45rem;
        overflow-wrap: anywhere;
    }
    .pa-item__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.55rem;
        align-items: center;
    }
    .pa-item__chip {
        display: inline-flex; align-items: center; gap: 0.3rem;
        font-size: 0.7rem; font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: var(--sms-surface-muted);
        color: var(--sms-text-muted);
        border: 1px solid var(--sms-border-soft);
        white-space: nowrap;
    }
    .pa-item__chip--stale {
        background: rgba(239,68,68,0.10);
        color: #dc2626;
        border-color: rgba(239,68,68,0.22);
    }
    .pa-item__chip--fresh {
        background: rgba(16,185,129,0.10);
        color: #059669;
        border-color: rgba(16,185,129,0.22);
    }
    [data-theme="dark"] .pa-item__chip--stale { color: #fca5a5; }
    [data-theme="dark"] .pa-item__chip--fresh { color: #6ee7b7; }

    .pa-item__actions {
        display: flex;
        gap: 0.4rem;
        align-items: center;
        flex-shrink: 0;
        flex-wrap: wrap;
    }

    .pa-btn {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        font-size: 0.78rem; font-weight: 700;
        border-radius: 9px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .pa-btn--approve {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        box-shadow: 0 2px 8px rgba(16,185,129,0.25);
    }
    .pa-btn--approve:hover { transform: translateY(-1px); }
    .pa-btn--reject {
        background: transparent;
        color: #dc2626;
        border-color: rgba(220,38,38,0.3);
    }
    .pa-btn--reject:hover { background: rgba(220,38,38,0.10); }

    .pa-empty {
        padding: 3.5rem 1.5rem;
        text-align: center;
    }
    .pa-empty i { font-size: 2.5rem; opacity: 0.3; display: block; margin-bottom: 0.75rem; color: var(--sms-text-muted); }
    .pa-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.25rem; }
    .pa-empty p { font-size: 0.85rem; color: var(--sms-text-muted); margin: 0 auto; max-width: 360px; }

    .pa-recent-table thead th { white-space: nowrap; background: var(--sms-table-head-bg); }
    .pa-recent-table tbody tr:hover td { background: var(--sms-dropdown-hover); }
    .pa-recent-table tbody td { padding: 0.65rem 0.75rem; font-size: 0.82rem; }
    .pa-recent-table thead th { padding: 0.6rem 0.75rem; font-size: 0.68rem; }

    .privacy-mode .pa-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .pa-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    @media (max-width: 767.98px) {
        .pa-item {
            grid-template-columns: 36px 1fr;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
        }
        .pa-item__icon { width: 36px; height: 36px; font-size: 1rem; border-radius: 10px; }
        .pa-item__actions { grid-column: 2; justify-content: flex-start; padding-top: 0.5rem; }
    }
    @media (max-width: 400px) {
        #paPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #paPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .pa-tab { padding: 0.75rem 0.7rem; font-size: 0.75rem; }
        .pa-tab__count { font-size: 0.62rem; }
        .pa-item { padding: 0.75rem 0.85rem; }
        .pa-item__title { font-size: 0.85rem; }
        .pa-item__sub { font-size: 0.72rem; }
        .pa-item__chip { font-size: 0.65rem; }
        .pa-btn { padding: 0.4rem 0.7rem; font-size: 0.72rem; }
        .pa-recent-table tbody td,
        .pa-recent-table thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
    }
    @media (max-width: 360px) {
        .pa-recent-table tbody td,
        .pa-recent-table thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    .pa-print-only { display: none; }

    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #paPage { display: none !important; }
        .pa-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .pa-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .pa-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .pa-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .pa-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .pa-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .pa-print-only .print-kpi { flex: 1; text-align: center; }
        .pa-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .pa-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .pa-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .pa-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .pa-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .pa-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .pa-print-only td.pa-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .pa-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .pa-print-only thead { display: table-header-group; }
        .pa-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    const LEAVES      = <?= json_encode($pendingLeaves, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const CLEARANCES  = <?= json_encode($pendingClearances, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const RECENT      = <?= json_encode($recentActions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES  = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 15;

    let filters = { dept: 'all', type: 'all', age: 'all', q: '' };
    let activeTab = 'all';
    let currentPage = 1;
    let filteredItems = [];

    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function daysBetween(a, b) {
        if (!a || !b) return 0;
        return Math.round((b - a) / (1000 * 60 * 60 * 24));
    }
    function fullName(r) {
        const mid = r.middle_name ? ' ' + r.middle_name.charAt(0) + '.' : '';
        return (r.first_name + mid + ' ' + r.last_name).trim() || '—';
    }
    function fmtDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }
    function ageMatches(row, dateField) {
        if (filters.age === 'all') return true;
        const d = parseDate(row[dateField]);
        if (!d) return false;
        const daysOld = daysBetween(d, new Date());
        if (filters.age === '1')   return daysOld <= 1;
        if (filters.age === '7')   return daysOld <= 7;
        if (filters.age === '30')  return daysOld <= 30;
        if (filters.age === 'old') return daysOld > 30;
        return true;
    }
    function qMatches(row, hayFields) {
        if (!filters.q) return true;
        const q = filters.q.toLowerCase();
        for (const f of hayFields) {
            const v = String(row[f] || '').toLowerCase();
            if (v.indexOf(q) !== -1) return true;
        }
        return false;
    }

    /* Build unified queue — leaves + clearances only */
    function buildQueue() {
        const items = [];

        LEAVES.forEach(l => {
            items.push({
                type: 'leave',
                id: l.id,
                ref_no: l.request_ref,
                faculty_name: fullName(l),
                faculty_no: l.faculty_no,
                department_id: l.department_id,
                dept_name: l.dept_name || l.dept_code || '—',
                title: 'Leave Request — ' + (l.leave_type || 'Leave'),
                sub: fmtDate(l.start_date) + ' – ' + fmtDate(l.end_date) + ' · ' +
                     (l.total_days || 0) + ' day' + (l.total_days !== 1 ? 's' : ''),
                reason: l.reason,
                submitted: l.created_at,
                ageField: 'created_at',
                raw: l
            });
        });

        CLEARANCES.forEach(c => {
            items.push({
                type: 'clearance',
                id: c.clearance_item_id,
                ref_no: c.clearance_no || 'CLR-' + c.clearance_id,
                faculty_name: fullName(c),
                faculty_no: c.faculty_no,
                department_id: c.department_id,
                dept_name: c.dept_name || c.dept_code || '—',
                title: 'Clearance — ' + (c.office_name || 'Department Clearance'),
                sub: 'Status: ' + (c.status || 'Pending'),
                reason: c.remarks,
                submitted: c.submitted_at,
                ageField: 'submitted_at',
                raw: c
            });
        });

        items.sort((a, b) => {
            const da = parseDate(a.submitted) || new Date(0);
            const db = parseDate(b.submitted) || new Date(0);
            return db - da;
        });

        return items;
    }

    function applyFilters(items) {
        return items.filter(it => {
            if (activeTab !== 'all' && it.type !== activeTab) return false;
            if (!deptMatches(it)) return false;
            if (!ageMatches(it, it.ageField)) return false;
            if (!qMatches(it, ['faculty_name', 'faculty_no', 'ref_no', 'title', 'sub'])) return false;
            return true;
        });
    }

    function renderKPIs(items) {
        document.getElementById('kpiTotal').textContent      = items.length.toLocaleString();
        document.getElementById('kpiLeaves').textContent     = items.filter(i => i.type === 'leave').length.toLocaleString();
        document.getElementById('kpiClearances').textContent = items.filter(i => i.type === 'clearance').length.toLocaleString();

        document.getElementById('tabCountAll').textContent       = items.length;
        document.getElementById('tabCountLeave').textContent     = items.filter(i => i.type === 'leave').length;
        document.getElementById('tabCountClearance').textContent = items.filter(i => i.type === 'clearance').length;
    }

    function renderQueue(items) {
        const host = document.getElementById('queueBody');
        const total = items.length;

        if (total === 0) {
            host.innerHTML = `
                <div class="pa-empty">
                    <i class="fas fa-check-circle"></i>
                    <h6>All caught up</h6>
                    <p>No items match your current filters. You're all clear.</p>
                </div>`;
            document.getElementById('queueSubtitle').textContent = 'Nothing to show';
            document.getElementById('queuePager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, total);
        const page = items.slice(start, end);

        host.innerHTML = page.map(it => {
            const iconClass = 'pa-item__icon--' + it.type;
            const icon = it.type === 'leave' ? 'fa-plane-departure' : 'fa-clipboard-check';
            const submitted = parseDate(it.submitted);
            const daysOld = submitted ? daysBetween(submitted, new Date()) : 0;
            const ageChipClass = daysOld > 30 ? 'pa-item__chip--stale' : (daysOld <= 7 ? 'pa-item__chip--fresh' : '');

            return `
                <div class="pa-item">
                    <div class="pa-item__icon ${iconClass}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="pa-item__body">
                        <div class="pa-item__title">${escapeHtml(it.title)}</div>
                        <div class="pa-item__sub pa-privacy-target">
                            <strong>${escapeHtml(it.faculty_name)}</strong>
                            <span class="text-body-secondary"> · ${escapeHtml(it.faculty_no || '—')} · ${escapeHtml(it.dept_name)}</span>
                        </div>
                        <div class="pa-item__meta">
                            <span class="pa-item__chip"><i class="fas fa-hashtag"></i>${escapeHtml(it.ref_no || '—')}</span>
                            <span class="pa-item__chip">${escapeHtml(it.sub)}</span>
                            <span class="pa-item__chip ${ageChipClass}">
                                <i class="far fa-clock"></i>
                                ${daysOld === 0 ? 'Today' : (daysOld + 'd ago')}
                            </span>
                            ${it.reason ? `<span class="pa-item__chip"><i class="fas fa-comment-dots"></i>${escapeHtml(String(it.reason).substring(0, 60))}</span>` : ''}
                        </div>
                    </div>
                    <div class="pa-item__actions">
                        <button type="button" class="pa-btn pa-btn--approve"
                                data-action="approve"
                                data-id="${it.id}"
                                data-type="${it.type}"
                                data-name="${escapeHtml(it.faculty_name)}">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="button" class="pa-btn pa-btn--reject"
                                data-action="reject"
                                data-id="${it.id}"
                                data-type="${it.type}"
                                data-name="${escapeHtml(it.faculty_name)}">
                            <i class="fas fa-xmark"></i> Reject
                        </button>
                    </div>
                </div>`;
        }).join('');

        document.getElementById('queueSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' item' + (total !== 1 ? 's' : '');
        renderPager(totalPages);

        host.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', () => openActionModal(btn));
        });
    }

    function renderPager(totalPages) {
        const pager = document.getElementById('queuePager');
        pager.innerHTML = '';
        if (totalPages <= 1) return;

        let html = '';
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        const wStart = Math.max(1, currentPage - 2);
        const wEnd = Math.min(totalPages, currentPage + 2);
        for (let p = wStart; p <= wEnd; p++) {
            html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        pager.innerHTML = html;
        pager.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (!p || p < 1 || p > totalPages || p === currentPage) return;
                currentPage = p;
                renderQueue(filteredItems);
            });
        });
    }

    function renderRecent() {
        const tbody = document.getElementById('recentBody');
        document.getElementById('recentCount').textContent = RECENT.length;

        if (RECENT.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-body-secondary">
                <i class="fas fa-history d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                No actions in the last 30 days.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = RECENT.map(r => {
            const typeLabel = { leave: 'Leave', clearance: 'Clearance' }[r.item_type] || r.item_type;
            const statusLower = String(r.status || '').toLowerCase();
            const badge = statusLower === 'approved'
                ? '<span class="badge text-bg-success">Approved</span>'
                : (statusLower === 'rejected' ? '<span class="badge text-bg-danger">Rejected</span>'
                : '<span class="badge text-bg-secondary">' + escapeHtml(r.status) + '</span>');
            return '<tr>' +
                '<td class="text-nowrap">' + escapeHtml(fmtDate(r.acted_at)) + '</td>' +
                '<td class="pa-privacy-target">' + escapeHtml(r.faculty_name || '—') + '</td>' +
                '<td class="d-none d-md-table-cell"><span class="badge text-bg-light border">' + escapeHtml(typeLabel) + '</span></td>' +
                '<td class="d-none d-sm-table-cell">' + escapeHtml(r.subject || '—') + '</td>' +
                '<td>' + badge + '</td>' +
            '</tr>';
        }).join('');
    }

    function renderPrintReport(items) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const filterParts = [];
        if (filters.dept !== 'all') {
            filterParts.push('Department: ' + (DEPT_NAMES[filters.dept] || ('Dept #' + filters.dept)));
        }
        if (activeTab !== 'all') filterParts.push('Type: ' + activeTab);
        if (filters.age !== 'all') filterParts.push('Age: ' + filters.age);
        if (filters.q) filterParts.push('Search: "' + filters.q + '"');
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const rows = items.map((it, idx) => {
            const typeLabel = { leave: 'Leave', clearance: 'Clearance' }[it.type] || it.type;
            const submitted = parseDate(it.submitted);
            const daysOld = submitted ? daysBetween(submitted, new Date()) : 0;
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + escapeHtml(typeLabel) + '</td>' +
                '<td class="pa-print-blur">' + escapeHtml(it.faculty_name) + '</td>' +
                '<td>' + escapeHtml(it.faculty_no || '—') + '</td>' +
                '<td>' + escapeHtml(it.dept_name) + '</td>' +
                '<td>' + escapeHtml(it.ref_no || '—') + '</td>' +
                '<td>' + escapeHtml(it.sub) + '</td>' +
                '<td>' + daysOld + 'd</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Pending Approvals Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total Pending</div>
                    <div class="print-kpi-value">${items.length}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Leaves</div>
                    <div class="print-kpi-value">${items.filter(i => i.type === 'leave').length}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Clearances</div>
                    <div class="print-kpi-value">${items.filter(i => i.type === 'clearance').length}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Type</th><th>Faculty</th><th>Faculty No</th>
                        <th>Department</th><th>Ref No</th><th>Details</th><th>Age</th>
                    </tr>
                </thead>
                <tbody>${rows || '<tr><td colspan="8" style="text-align:center;">No pending items.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${items.length} item${items.length !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    function exportCsv(items) {
        const headers = ['#', 'Type', 'Faculty', 'Faculty No', 'Department', 'Ref No', 'Details', 'Age (days)'];
        const lines = items.map((it, idx) => {
            const typeLabel = { leave: 'Leave', clearance: 'Clearance' }[it.type] || it.type;
            const submitted = parseDate(it.submitted);
            const daysOld = submitted ? daysBetween(submitted, new Date()) : 0;
            return [idx + 1, typeLabel, it.faculty_name, it.faculty_no || '', it.dept_name, it.ref_no || '', it.sub, daysOld];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'pending_approvals_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    let actionModal = null;

    function openActionModal(btn) {
        const action  = btn.dataset.action;
        const itemId  = btn.dataset.id;
        const itemType = btn.dataset.type;
        const name    = btn.dataset.name;

        document.getElementById('paItemId').value   = itemId;
        document.getElementById('paItemType').value = itemType;
        document.getElementById('paDecision').value = action;
        document.getElementById('paRemarks').value  = '';

        const title = document.getElementById('paModalTitle');
        const msgEl = document.getElementById('paModalMessage');
        const submit = document.getElementById('paModalSubmit');
        const label = { leave: 'leave request', clearance: 'clearance' }[itemType] || 'item';

        if (action === 'approve') {
            title.innerHTML = '<i class="fas fa-check-circle text-success"></i><span>Approve ' + label + '</span>';
            msgEl.innerHTML = 'Approve the ' + escapeHtml(label) + ' for <strong>' + escapeHtml(name) + '</strong>?';
            submit.className = 'btn btn-success btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-check me-1"></i> Approve';
        } else {
            title.innerHTML = '<i class="fas fa-times-circle text-danger"></i><span>Reject ' + label + '</span>';
            msgEl.innerHTML = 'Reject the ' + escapeHtml(label) + ' for <strong>' + escapeHtml(name) + '</strong>? Please add a reason in the remarks.';
            submit.className = 'btn btn-danger btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-xmark me-1"></i> Reject';
        }

        if (!actionModal) {
            actionModal = new bootstrap.Modal(document.getElementById('paActionModal'));
        }
        actionModal.show();
    }

    function render() {
        const all = buildQueue();
        filteredItems = applyFilters(all);
        currentPage = 1;
        renderKPIs(all);
        renderQueue(filteredItems);
        renderPrintReport(filteredItems);
        renderRecent();
    }

    function bindFilters() {
        document.querySelectorAll('#paPage [data-filter]').forEach(el => {
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, () => {
                if (evt === 'input') {
                    clearTimeout(el._t);
                    el._t = setTimeout(() => {
                        filters[el.dataset.filter] = el.value;
                        render();
                    }, 200);
                } else {
                    filters[el.dataset.filter] = el.value;
                    render();
                }
            });
        });

        document.querySelectorAll('.pa-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                activeTab = tab.dataset.tab;
                document.querySelectorAll('.pa-tab').forEach(t => t.classList.toggle('pa-tab--active', t === tab));
                render();
            });
        });
    }

    function resetAllFilters() {
        filters = { dept: 'all', type: 'all', age: 'all', q: '' };
        activeTab = 'all';
        document.querySelectorAll('#paPage [data-filter]').forEach(el => {
            el.value = el.tagName === 'SELECT' ? 'all' : '';
        });
        document.querySelectorAll('.pa-tab').forEach(t => t.classList.toggle('pa-tab--active', t.dataset.tab === 'all'));
        render();
    }

    function init() {
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredItems));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanPendingApprovalsPrivacy';
        if (pt) {
            const apply = on => document.body.classList.toggle('privacy-mode', on);
            try {
                const saved = localStorage.getItem(KEY) === '1';
                pt.checked = saved; apply(saved);
            } catch (e) { /* ignore */ }
            pt.addEventListener('change', () => {
                apply(pt.checked);
                try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>