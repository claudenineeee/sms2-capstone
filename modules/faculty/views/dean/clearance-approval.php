<?php
/**
 * SMS 2 - Dean - Clearance Sign-off
 * Dean signs off the Department Clearance office for faculty in their departments.
 * RBAC scoped server-side. Unique card layout grouped by faculty.
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
   HANDLE ACTION — sign off or return a Department Clearance item
   Server-side RBAC re-check before any write.
   ============================================================ */
$actionMessage = '';
$actionType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sign_clearance') {
    if (!$hasDeanDepartments) {
        $actionMessage = 'No departments assigned.';
        $actionType = 'danger';
    } else {
        try {
            $itemId   = (int) ($_POST['item_id'] ?? 0);
            $decision = (string) ($_POST['decision'] ?? '');
            $remarks  = trim((string) ($_POST['remarks'] ?? ''));
            $deptList = implode(',', array_map('intval', $deanDepartments));

            if (!in_array($decision, ['approve','return'], true)) {
                throw new InvalidArgumentException('Invalid decision.');
            }

            $newStatus = $decision === 'approve' ? 'Cleared' : 'Hold';
            $stmt = $pdo->prepare("
                UPDATE faculty_db.clearance_items ci
                INNER JOIN faculty_db.clearance_requests cr ON cr.clearance_id = ci.clearance_id
                INNER JOIN faculty_db.clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
                INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
                SET ci.status = :st,
                    ci.remarks = :cm,
                    ci.cleared_by_external_id = :uid,
                    ci.cleared_at = NOW()
                WHERE ci.clearance_item_id = :id
                  AND f.department_id IN ($deptList)
                  AND co.name LIKE '%Department%'
            ");
            $stmt->execute([
                ':st'  => $newStatus,
                ':cm'  => $remarks,
                ':uid' => (string) $deanUserId,
                ':id'  => $itemId,
            ]);
            if ($stmt->rowCount() === 0) throw new RuntimeException('Item not found or out of scope.');

            $actionMessage = $decision === 'approve' ? 'Clearance signed off.' : 'Clearance returned for revision.';
            $actionType = 'success';

        } catch (Throwable $e) {
            error_log('[clearance-approval] action failed: ' . $e->getMessage());
            $actionMessage = $e->getMessage();
            $actionType = 'danger';
        }
    }
}

/* ============================================================
   LOAD CLEARANCE DATA — scoped to dean's departments
   ============================================================ */
$facultyClearances = [];   // grouped: faculty_id => list of items
$loadError = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        /* Load every clearance request + items for the dean's departments */
        $stmt = $pdo->query("
            SELECT
                cr.clearance_id,
                cr.clearance_no,
                cr.faculty_id,
                cr.term_id,
                cr.intent_type,
                cr.overall_status,
                cr.submitted_at,
                at.academic_year,
                at.semester,
                f.faculty_no, f.first_name, f.middle_name, f.last_name,
                f.department_id,
                d.code AS dept_code, d.name AS dept_name,

                ci.clearance_item_id,
                ci.clearance_office_id,
                ci.status AS item_status,
                ci.remarks AS item_remarks,
                ci.file_path,
                ci.original_name,
                ci.cleared_at,

                co.name AS office_name,
                co.sequence_order
            FROM faculty_db.clearance_requests cr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            LEFT JOIN faculty_db.academic_terms at ON at.term_id = cr.term_id
            LEFT JOIN faculty_db.clearance_items ci ON ci.clearance_id = cr.clearance_id
            LEFT JOIN faculty_db.clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
            WHERE f.department_id IN ($deptList)
            ORDER BY cr.submitted_at DESC, co.sequence_order ASC
            LIMIT 500
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* Group by clearance_id */
        $byClearance = [];
        foreach ($rows as $r) {
            $cid = (int) $r['clearance_id'];
            if (!isset($byClearance[$cid])) {
                $byClearance[$cid] = [
                    'clearance_id'    => $cid,
                    'clearance_no'    => $r['clearance_no'] ?: ('CLR-' . $cid),
                    'faculty_id'      => (int) $r['faculty_id'],
                    'faculty_no'      => $r['faculty_no'] ?? '',
                    'faculty_name'    => trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                    'department_id'   => (int) $r['department_id'],
                    'dept_name'       => $r['dept_name'] ?: ($r['dept_code'] ?: '—'),
                    'academic_year'   => $r['academic_year'] ?: '',
                    'semester'        => $r['semester'] ?: '',
                    'intent_type'     => $r['intent_type'] ?: 'renewal',
                    'overall_status'  => $r['overall_status'] ?: 'In Progress',
                    'submitted_at'    => $r['submitted_at'] ?: '',
                    'items'           => [],
                ];
            }
            if (!empty($r['clearance_item_id'])) {
                $byClearance[$cid]['items'][] = [
                    'clearance_item_id' => (int) $r['clearance_item_id'],
                    'office_name'       => $r['office_name'] ?: 'Office',
                    'office_id'         => (int) $r['clearance_office_id'],
                    'sequence_order'    => (int) $r['sequence_order'],
                    'status'            => $r['item_status'] ?: 'Missing',
                    'remarks'           => $r['item_remarks'] ?: '',
                    'file_path'         => $r['file_path'] ?: '',
                    'original_name'     => $r['original_name'] ?: '',
                    'cleared_at'        => $r['cleared_at'] ?: '',
                ];
            }
        }

        $facultyClearances = array_values($byClearance);

    } catch (Throwable $e) {
        error_log('Clearance approval load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Clearance Sign-off';
$activeModule = 'faculty';
$activePage   = 'clearance-approval';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Clearance Sign-off', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="caPage">

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
        <h4 class="mb-0 fw-bold">Clearance Sign-off Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-clipboard-check text-primary"></i>
                <span>Clearance Sign-off</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Review each faculty's clearance and sign off on the Department Clearance office.
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

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Clearances</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">In your scope</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-lg-3">
            <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Sign-off</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="kpiPending">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#f59e0b;font-size:0.7rem;">Needs your action</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-lg-3">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Signed Off</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiCleared">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">Completed by you</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-lg-3">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#ff4d4d;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(255,77,77,0.14);color:#ff4d4d;">
                        <i class="fas fa-circle-pause"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Blocked</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="kpiBlocked">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Waiting on other offices</small>
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
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_term">Term</label>
                    <select class="form-select form-select-sm" id="f_term" data-filter="term">
                        <option value="all">All terms</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" data-filter="status">
                        <option value="all">All statuses</option>
                        <option value="pending">Pending Sign-off</option>
                        <option value="ready">Ready for Sign-off</option>
                        <option value="blocked">Blocked</option>
                        <option value="cleared">Signed Off</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_q">Search</label>
                    <input type="text" class="form-control form-control-sm" id="f_q" data-filter="q" placeholder="Name or clearance no." autocomplete="off">
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Cards grid ================= -->
    <div class="ca-grid" id="clearanceGrid"></div>

    <div class="card border shadow-sm overflow-hidden no-print">
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-3 py-3">
            <div class="small text-body-secondary" id="gridSubtitle">Loading…</div>
            <nav aria-label="Clearance pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1" id="gridPager"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Print-only report -->
<div class="ca-print-only" id="printReport" aria-hidden="true"></div>

<!-- Action modal -->
<div class="modal fade" id="caActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="caModalTitle">
                    <i class="fas fa-check-circle text-success"></i>
                    <span>Confirm Action</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="sign_clearance">
                    <input type="hidden" name="item_id" id="caItemId" value="">
                    <input type="hidden" name="decision" id="caDecision" value="">

                    <p class="text-body-secondary mb-3" id="caModalMessage"></p>

                    <label class="form-label small text-uppercase fw-bold text-body-secondary" for="caRemarks">
                        Remarks (optional)
                    </label>
                    <textarea class="form-control" id="caRemarks" name="remarks" rows="3" placeholder="Add notes for the faculty…"></textarea>
                </div>
                <div class="modal-footer border-top border-light-subtle py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm rounded-3 px-4 fw-bold" id="caModalSubmit">
                        <i class="fas fa-check me-1"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Clearance grid */
    .ca-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 0.85rem;
        margin-bottom: 0.85rem;
    }
    .ca-card {
        background: var(--sms-surface);
        border: 1px solid var(--sms-glass-border);
        border-radius: 16px;
        box-shadow: var(--sms-shadow-xs);
        padding: 1rem 1.1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .ca-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--sms-shadow-sm);
        border-color: rgba(59,130,246,0.3);
    }
    .ca-card--cleared { border-left: 4px solid #10b981; }
    .ca-card--pending { border-left: 4px solid #f59e0b; }
    .ca-card--blocked { border-left: 4px solid #ef4444; }
    .ca-card--ready   { border-left: 4px solid #3b82f6; }

    .ca-card__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .ca-card__person {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }
    .ca-card__avatar {
        width: 40px; height: 40px;
        border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.82rem;
        background: rgba(59,130,246,0.14);
        color: #3b82f6;
        flex-shrink: 0;
    }
    .ca-card__name {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--sms-heading);
        line-height: 1.25;
        overflow-wrap: anywhere;
    }
    .ca-card__meta {
        font-size: 0.72rem;
        color: var(--sms-text-muted);
    }

    .ca-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.68rem; font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
        flex-shrink: 0;
    }
    .ca-badge--cleared { background: rgba(16,185,129,0.14); color: #059669; border-color: rgba(16,185,129,0.24); }
    .ca-badge--pending { background: rgba(245,158,11,0.16); color: #b45309; border-color: rgba(245,158,11,0.28); }
    .ca-badge--blocked { background: rgba(239,68,68,0.14);  color: #dc2626; border-color: rgba(239,68,68,0.24); }
    .ca-badge--ready   { background: rgba(59,130,246,0.14); color: #2563eb; border-color: rgba(59,130,246,0.24); }
    [data-theme="dark"] .ca-badge--cleared { color: #6ee7b7; }
    [data-theme="dark"] .ca-badge--pending { color: #fcd34d; }
    [data-theme="dark"] .ca-badge--blocked { color: #fca5a5; }
    [data-theme="dark"] .ca-badge--ready   { color: #93c5fd; }

    /* Office progress strip */
    .ca-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.55rem 0;
        border-top: 1px dashed var(--sms-border-soft);
        border-bottom: 1px dashed var(--sms-border-soft);
    }
    .ca-office {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 600;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .ca-office--cleared  { background: rgba(16,185,129,0.12); color: #059669; border-color: rgba(16,185,129,0.22); }
    .ca-office--pending  { background: rgba(59,130,246,0.12); color: #2563eb; border-color: rgba(59,130,246,0.22); }
    .ca-office--missing  { background: rgba(148,163,184,0.14); color: #64748b; border-color: rgba(148,163,184,0.26); }
    .ca-office--hold     { background: rgba(239,68,68,0.12);  color: #dc2626; border-color: rgba(239,68,68,0.22); }
    .ca-office--dept     { background: rgba(139,92,246,0.14); color: #7c3aed; border-color: rgba(139,92,246,0.26); font-weight: 800; }
    [data-theme="dark"] .ca-office--cleared { color: #6ee7b7; }
    [data-theme="dark"] .ca-office--pending { color: #93c5fd; }
    [data-theme="dark"] .ca-office--missing { color: #cbd5e1; }
    [data-theme="dark"] .ca-office--hold    { color: #fca5a5; }
    [data-theme="dark"] .ca-office--dept    { color: #c4b5fd; }

    .ca-card__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .ca-card__note {
        font-size: 0.75rem;
        color: var(--sms-text-muted);
        font-style: italic;
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    /* Action buttons */
    .ca-actions {
        display: flex;
        gap: 0.4rem;
        align-items: center;
        flex-shrink: 0;
    }
    .ca-btn {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        font-size: 0.78rem; font-weight: 700;
        border-radius: 9px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .ca-btn--approve {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        box-shadow: 0 2px 8px rgba(16,185,129,0.25);
    }
    .ca-btn--approve:hover { transform: translateY(-1px); }
    .ca-btn--return {
        background: transparent;
        color: #dc2626;
        border-color: rgba(220,38,38,0.3);
    }
    .ca-btn--return:hover { background: rgba(220,38,38,0.10); }
    .ca-btn--view {
        background: transparent;
        color: var(--sms-text-muted);
        border-color: var(--sms-border);
    }
    .ca-btn--view:hover { color: var(--sms-primary); border-color: var(--sms-primary); }
    .ca-btn--disabled {
        background: var(--sms-surface-muted);
        color: var(--sms-text-faint);
        border-color: var(--sms-border-soft);
        cursor: not-allowed;
        opacity: 0.75;
    }

    .ca-empty {
        grid-column: 1 / -1;
        padding: 3.5rem 1.5rem;
        text-align: center;
    }
    .ca-empty i { font-size: 2.5rem; opacity: 0.3; display: block; margin-bottom: 0.75rem; color: var(--sms-text-muted); }
    .ca-empty h6 { font-weight: 700; color: var(--sms-heading); margin-bottom: 0.25rem; }
    .ca-empty p { font-size: 0.85rem; color: var(--sms-text-muted); margin: 0 auto; max-width: 360px; }

    /* Privacy blur */
    .privacy-mode .ca-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .ca-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* Responsive */
    @media (max-width: 640px) {
        .ca-grid { grid-template-columns: 1fr; gap: 0.75rem; }
        .ca-card { padding: 0.85rem 0.95rem; border-radius: 14px; }
    }
    @media (max-width: 400px) {
        #caPage { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #caPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .ca-card__avatar { width: 34px; height: 34px; font-size: 0.72rem; }
        .ca-card__name { font-size: 0.85rem; }
        .ca-card__meta { font-size: 0.68rem; }
        .ca-office { font-size: 0.62rem; padding: 0.2rem 0.45rem; }
        .ca-btn { padding: 0.4rem 0.7rem; font-size: 0.72rem; }
    }

    /* Print-only */
    .ca-print-only { display: none; }

    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-header, .card-footer, .btn, button,
        .modal, .pagination, #privacyModeToggle { display: none !important; }
        #caPage { display: none !important; }
        .ca-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .ca-print-only .print-header {
            text-align: center; margin-bottom: 14px;
            border-bottom: 2px solid #000; padding-bottom: 10px;
        }
        .ca-print-only .print-header h3 { font-size: 16pt; font-weight: 800; margin: 0 0 4px; }
        .ca-print-only .print-header .print-meta { font-size: 9.5pt; color: #333; margin: 0; }
        .ca-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .ca-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .ca-print-only .print-kpi { flex: 1; text-align: center; }
        .ca-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .ca-print-only .print-kpi-value { font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2; }
        .ca-print-only table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .ca-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .ca-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .ca-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }
        .ca-print-only td.ca-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
        .ca-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .ca-print-only thead { display: table-header-group; }
        .ca-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script>
(function () {
    'use strict';

    const CLEARANCES = <?= json_encode($facultyClearances, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DEPT_NAMES = <?= json_encode($deanDepartmentNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const PAGE_SIZE = 9;

    let filters = { dept: 'all', term: 'all', status: 'all', q: '' };
    let currentPage = 1;
    let filteredRows = [];

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function initials(name) {
        return (name || '').split(' ').filter(Boolean).slice(0, 2).map(n => n.charAt(0).toUpperCase()).join('') || '—';
    }
    function parseDate(str) {
        if (!str) return null;
        const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
    }
    function daysBetween(a, b) {
        if (!a || !b) return 0;
        return Math.round((b - a) / (1000 * 60 * 60 * 24));
    }

    /* ============================================================
       Compute a card's overall status
       - cleared: the Department office is 'Cleared' (dean signed off)
       - blocked: another office has 'Hold' status
       - ready:   all non-dept offices cleared AND dept is not yet signed
       - pending: some non-dept offices still pending
       ============================================================ */
    function computeCardStatus(card) {
        const items = card.items || [];
        const deptItem = items.find(i => /department/i.test(i.office_name));
        const otherItems = items.filter(i => !/department/i.test(i.office_name));

        if (deptItem && String(deptItem.status).toLowerCase() === 'cleared') {
            return 'cleared';
        }
        if (otherItems.some(i => String(i.status).toLowerCase() === 'hold')) {
            return 'blocked';
        }
        const allOthersCleared = otherItems.length > 0 &&
            otherItems.every(i => String(i.status).toLowerCase() === 'cleared');
        if (allOthersCleared && (!deptItem || String(deptItem.status).toLowerCase() !== 'cleared')) {
            return 'ready';
        }
        return 'pending';
    }

    /* ============================================================
       Filters
       ============================================================ */
    function deptMatches(row) {
        if (filters.dept === 'all') return true;
        return String(row.department_id) === String(filters.dept);
    }
    function termMatches(row) {
        if (filters.term === 'all') return true;
        return (row.academic_year + '|' + row.semester) === filters.term;
    }
    function statusMatches(row) {
        if (filters.status === 'all') return true;
        const s = computeCardStatus(row);
        return s === filters.status;
    }
    function qMatches(row) {
        if (!filters.q) return true;
        const q = filters.q.toLowerCase();
        return (
            row.faculty_name.toLowerCase().indexOf(q) !== -1 ||
            (row.faculty_no || '').toLowerCase().indexOf(q) !== -1 ||
            (row.clearance_no || '').toLowerCase().indexOf(q) !== -1
        );
    }

    /* ============================================================
       Render KPIs
       ============================================================ */
    function renderKPIs(rows) {
        let pending = 0, cleared = 0, blocked = 0;
        rows.forEach(r => {
            const s = computeCardStatus(r);
            if (s === 'cleared') cleared++;
            else if (s === 'blocked') blocked++;
            else pending++;   // pending + ready both count as "pending sign-off"
        });
        document.getElementById('kpiTotal').textContent     = rows.length.toLocaleString();
        document.getElementById('kpiPending').textContent   = pending.toLocaleString();
        document.getElementById('kpiCleared').textContent   = cleared.toLocaleString();
        document.getElementById('kpiBlocked').textContent   = blocked.toLocaleString();
    }

    /* ============================================================
       Render cards
       ============================================================ */
    function renderGrid(rows) {
        const host = document.getElementById('clearanceGrid');
        const total = rows.length;

        if (total === 0) {
            host.innerHTML = `
                <div class="ca-empty">
                    <i class="fas fa-clipboard-check"></i>
                    <h6>No clearances in your scope</h6>
                    <p>When faculty in your departments submit their clearance, they'll appear here for your sign-off.</p>
                </div>`;
            document.getElementById('gridSubtitle').textContent = '0 clearances';
            document.getElementById('gridPager').innerHTML = '';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, total);
        const page = rows.slice(start, end);

        host.innerHTML = page.map(card => {
            const status = computeCardStatus(card);
            const statusBadgeClass = 'ca-badge--' + status;
            const statusLabel = {
                cleared: 'Signed Off',
                ready: 'Ready for Sign-off',
                pending: 'Pending',
                blocked: 'Blocked'
            }[status];

            const deptItem = (card.items || []).find(i => /department/i.test(i.office_name));
            const canSign = (status === 'ready' || status === 'pending') && deptItem;

            const officeChips = (card.items || []).map(item => {
                const itemStatus = String(item.status || '').toLowerCase();
                let cls = 'ca-office--missing';
                if (itemStatus === 'cleared') cls = 'ca-office--cleared';
                else if (itemStatus === 'pending') cls = 'ca-office--pending';
                else if (itemStatus === 'hold') cls = 'ca-office--hold';
                if (/department/i.test(item.office_name)) cls += ' ca-office--dept';

                const icon = itemStatus === 'cleared' ? 'fa-check'
                           : itemStatus === 'hold' ? 'fa-xmark'
                           : itemStatus === 'pending' ? 'fa-hourglass-half'
                           : 'fa-circle';
                return `<span class="ca-office ${cls}"><i class="fas ${icon}"></i>${escapeHtml(item.office_name)}</span>`;
            }).join('');

            const submitted = parseDate(card.submitted_at);
            const daysOld = submitted ? daysBetween(submitted, new Date()) : 0;
            const termLabel = (card.academic_year || '—') + ' · ' + (card.semester || '—');

            return `
                <div class="ca-card ca-card--${status}">
                    <div class="ca-card__head">
                        <div class="ca-card__person">
                            <span class="ca-card__avatar">${escapeHtml(initials(card.faculty_name))}</span>
                            <div class="min-w-0">
                                <div class="ca-card__name ca-privacy-target">${escapeHtml(card.faculty_name)}</div>
                                <div class="ca-card__meta">${escapeHtml(card.faculty_no || '—')} · ${escapeHtml(card.dept_name)}</div>
                                <div class="ca-card__meta">${escapeHtml(termLabel)} · ${escapeHtml(card.clearance_no)}</div>
                            </div>
                        </div>
                        <span class="ca-badge ${statusBadgeClass}">${escapeHtml(statusLabel)}</span>
                    </div>

                    <div class="ca-strip">
                        ${officeChips || '<span class="ca-office ca-office--missing"><i class="fas fa-circle"></i>No offices assigned</span>'}
                    </div>

                    <div class="ca-card__footer">
                        <div class="ca-card__note">
                            ${status === 'cleared'
                                ? 'You signed off on ' + (deptItem && deptItem.cleared_at ? escapeHtml(deptItem.cleared_at.split(' ')[0]) : '—')
                                : status === 'blocked'
                                    ? 'Waiting on another office before you can sign off.'
                                    : status === 'ready'
                                        ? 'All offices cleared. Ready for your sign-off.'
                                        : 'Pending ' + (daysOld > 0 ? daysOld + 'd ago' : 'today')}
                        </div>
                        <div class="ca-actions">
                            ${status === 'cleared'
                                ? `<button type="button" class="ca-btn ca-btn--view" data-action="view" data-id="${card.clearance_id}">
                                     <i class="fas fa-eye"></i> View
                                   </button>`
                                : canSign
                                    ? `<button type="button" class="ca-btn ca-btn--return"
                                            data-action="return"
                                            data-id="${deptItem.clearance_item_id}"
                                            data-name="${escapeHtml(card.faculty_name)}">
                                         <i class="fas fa-rotate-left"></i> Return
                                       </button>
                                       <button type="button" class="ca-btn ca-btn--approve"
                                            data-action="approve"
                                            data-id="${deptItem.clearance_item_id}"
                                            data-name="${escapeHtml(card.faculty_name)}">
                                         <i class="fas fa-check"></i> Sign Off
                                       </button>`
                                    : `<button type="button" class="ca-btn ca-btn--disabled" disabled>
                                         <i class="fas fa-lock"></i> Blocked
                                       </button>`
                            }
                        </div>
                    </div>
                </div>`;
        }).join('');

        document.getElementById('gridSubtitle').textContent =
            'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' clearance' + (total !== 1 ? 's' : '');
        renderPager(totalPages);

        host.querySelectorAll('[data-action]').forEach(btn => {
            const action = btn.dataset.action;
            if (action === 'approve' || action === 'return') {
                btn.addEventListener('click', () => openActionModal(btn, action));
            } else if (action === 'view') {
                btn.addEventListener('click', () => {
                    // Could open a detail modal; for now jump nowhere
                });
            }
        });
    }

    function renderPager(totalPages) {
        const pager = document.getElementById('gridPager');
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
                renderGrid(filteredRows);
            });
        });
    }

    /* ============================================================
       Print report
       ============================================================ */
    function renderPrintReport(rows) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const filterParts = [];
        if (filters.dept !== 'all') filterParts.push('Department: ' + (DEPT_NAMES[filters.dept] || ('Dept #' + filters.dept)));
        if (filters.term !== 'all') filterParts.push('Term: ' + filters.term.replace('|', ' · '));
        if (filters.status !== 'all') filterParts.push('Status: ' + filters.status);
        if (filters.q) filterParts.push('Search: "' + filters.q + '"');
        const filterLine = filterParts.length ? 'Filters — ' + filterParts.join(' · ') : 'No filters applied';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const kTotal = rows.length;
        let kCleared = 0, kPending = 0, kBlocked = 0;
        rows.forEach(r => {
            const s = computeCardStatus(r);
            if (s === 'cleared') kCleared++;
            else if (s === 'blocked') kBlocked++;
            else kPending++;
        });

        const bodyRows = rows.map((r, idx) => {
            const status = computeCardStatus(r);
            const deptItem = (r.items || []).find(i => /department/i.test(i.office_name));
            const items = (r.items || []).map(i => i.office_name + ': ' + i.status).join(' | ');
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td class="ca-print-blur">' + escapeHtml(r.faculty_name) + '</td>' +
                '<td>' + escapeHtml(r.faculty_no || '—') + '</td>' +
                '<td>' + escapeHtml(r.dept_name) + '</td>' +
                '<td>' + escapeHtml((r.academic_year || '—') + ' · ' + (r.semester || '—')) + '</td>' +
                '<td>' + escapeHtml(r.clearance_no) + '</td>' +
                '<td>' + escapeHtml(items) + '</td>' +
                '<td>' + escapeHtml(status === 'cleared' ? 'Signed Off' : (status === 'ready' ? 'Ready' : (status === 'blocked' ? 'Blocked' : 'Pending'))) + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Clearance Sign-off Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <p class="print-filters">${escapeHtml(filterLine)}</p>
            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total</div>
                    <div class="print-kpi-value">${kTotal}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Signed Off</div>
                    <div class="print-kpi-value">${kCleared}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Pending</div>
                    <div class="print-kpi-value">${kPending}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Blocked</div>
                    <div class="print-kpi-value">${kBlocked}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Faculty</th><th>Faculty No</th>
                        <th>Department</th><th>Term</th><th>Clearance No</th>
                        <th>Offices</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="8" style="text-align:center;">No clearances in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${kTotal} clearance${kTotal !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
    function exportCsv(rows) {
        const headers = ['#', 'Faculty', 'Faculty No', 'Department', 'Term', 'Clearance No', 'Offices', 'Status'];
        const lines = rows.map((r, idx) => {
            const status = computeCardStatus(r);
            const items = (r.items || []).map(i => i.office_name + ': ' + i.status).join(' | ');
            return [
                idx + 1,
                r.faculty_name,
                r.faculty_no || '',
                r.dept_name,
                (r.academic_year || '—') + ' · ' + (r.semester || '—'),
                r.clearance_no,
                items,
                status === 'cleared' ? 'Signed Off' : (status === 'ready' ? 'Ready' : (status === 'blocked' ? 'Blocked' : 'Pending'))
            ];
        });

        let csv = '\uFEFF' + headers.join(',') + '\n';
        lines.forEach(e => {
            csv += e.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'clearance_signoff_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    /* ============================================================
       Action modal
       ============================================================ */
    let caModal = null;

    function openActionModal(btn, action) {
        const itemId = btn.dataset.id;
        const name = btn.dataset.name;

        document.getElementById('caItemId').value   = itemId;
        document.getElementById('caDecision').value = action;
        document.getElementById('caRemarks').value  = '';

        const title = document.getElementById('caModalTitle');
        const msg = document.getElementById('caModalMessage');
        const submit = document.getElementById('caModalSubmit');

        if (action === 'approve') {
            title.innerHTML = '<i class="fas fa-check-circle text-success"></i><span>Sign Off Clearance</span>';
            msg.innerHTML = 'Sign off the Department Clearance for <strong>' + escapeHtml(name) + '</strong>? This confirms the faculty has no pending academic or departmental responsibilities.';
            submit.className = 'btn btn-success btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-check me-1"></i> Sign Off';
        } else {
            title.innerHTML = '<i class="fas fa-times-circle text-danger"></i><span>Return Clearance</span>';
            msg.innerHTML = 'Return the clearance to <strong>' + escapeHtml(name) + '</strong>? Please note the reason in the remarks field so they know what to fix.';
            submit.className = 'btn btn-danger btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-rotate-left me-1"></i> Return';
        }

        if (!caModal) {
            caModal = new bootstrap.Modal(document.getElementById('caActionModal'));
        }
        caModal.show();
    }

    /* ============================================================
       Filters
       ============================================================ */
    function applyFilters() {
        return CLEARANCES.filter(r =>
            deptMatches(r) && termMatches(r) && statusMatches(r) && qMatches(r)
        );
    }

    function populateTermFilter() {
        const terms = new Set();
        CLEARANCES.forEach(r => {
            if (r.academic_year || r.semester) {
                terms.add((r.academic_year || '—') + '|' + (r.semester || '—'));
            }
        });
        const sel = document.getElementById('f_term');
        if (!sel) return;
        Array.from(terms).sort().reverse().forEach(t => {
            const [y, s] = t.split('|');
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = y + ' · ' + s;
            sel.appendChild(opt);
        });
    }

    function render() {
        filteredRows = applyFilters();
        currentPage = 1;
        renderKPIs(CLEARANCES);
        renderGrid(filteredRows);
        renderPrintReport(filteredRows);
    }

    function bindFilters() {
        document.querySelectorAll('#caPage [data-filter]').forEach(el => {
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
    }

    function resetAllFilters() {
        filters = { dept: 'all', term: 'all', status: 'all', q: '' };
        document.querySelectorAll('#caPage [data-filter]').forEach(el => {
            el.value = el.tagName === 'SELECT' ? 'all' : '';
        });
        render();
    }

    function init() {
        populateTermFilter();
        bindFilters();

        document.getElementById('resetFilters').addEventListener('click', resetAllFilters);
        document.getElementById('exportCsvBtn').addEventListener('click', () => exportCsv(filteredRows));
        document.getElementById('printBtn').addEventListener('click', () => window.print());

        const pt = document.getElementById('privacyModeToggle');
        const KEY = 'smsDeanClearanceApprovalPrivacy';
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