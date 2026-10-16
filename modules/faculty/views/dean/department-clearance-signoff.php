<?php
/**
 * SMS 2 - Dean - Department Clearance Sign-off (Track 1)
 *
 * Implements Track 1 of the College Dean Clearance Workflow:
 *   - DH clears office 5006 (Department Clearance)
 *   - Dean reviews the department checklist / reports / committee work / duties
 *   - Dean either approves (countersignature) or returns to faculty with notes
 *
 * NO SCHEMA CHANGES. Writes to existing tables only:
 *   - clearance_items            (office 5006 status: Cleared → Approved by Dean)
 *   - clearance_signatures       (signatory_type = 'dean')
 *   - clearance_approval_history (office = 'Dean')
 *   - notifications              (faculty + DH awareness)
 *
 * RBAC: Dean only, scoped to assigned departments.
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
   Constants
   ============================================================ */
$DEPT_OFFICE_ID  = 5006;                 // Department Clearance office
$DEAN_OFFICE_LBL = 'Dean';

$currentUserId      = (int) ($_SESSION['user_id'] ?? 0);
$currentUserName    = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Dean'));
$currentUserRoleLbl = (string) ($_SESSION['role_label'] ?? 'Dean');

/* ============================================================
   Database
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
   Helpers
   ============================================================ */
if (!function_exists('eh')) {
    function eh($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('dcsFullName')) {
    function dcsFullName(array $r): string {
        $mid = !empty($r['middle_name']) ? ' ' . strtoupper(substr($r['middle_name'], 0, 1)) . '.' : '';
        return trim(($r['first_name'] ?? '') . $mid . ' ' . ($r['last_name'] ?? '')) ?: '—';
    }
}
if (!function_exists('dcsInitials')) {
    function dcsInitials(array $r): string {
        $s = strtoupper(substr((string) ($r['first_name'] ?? ''), 0, 1) . substr((string) ($r['last_name'] ?? ''), 0, 1));
        return $s !== '' ? $s : '—';
    }
}
if (!function_exists('dcsFmtDt')) {
    function dcsFmtDt($s): string {
        if (!$s) return '—';
        $d = strtotime((string) $s);
        return $d ? date('M j, Y g:i A', $d) : (string) $s;
    }
}
if (!function_exists('dcsStatusPill')) {
    function dcsStatusPill(string $status): string {
        $s = strtolower(trim($status));
        $map = [
            'cleared'           => ['bg-info-subtle text-info border-info-subtle',             'Cleared by DH'],
            'approved'          => ['bg-info-subtle text-info border-info-subtle',             'Approved by DH'],
            'approved by dean'  => ['bg-success-subtle text-success border-success-subtle',    'Approved by Dean'],
            'hold'              => ['bg-warning-subtle text-warning border-warning-subtle',    'On Hold'],
            'on hold'           => ['bg-warning-subtle text-warning border-warning-subtle',    'On Hold'],
            'denied'            => ['bg-danger-subtle text-danger border-danger-subtle',       'Denied'],
            'rejected'          => ['bg-danger-subtle text-danger border-danger-subtle',       'Rejected'],
            'returned'          => ['bg-danger-subtle text-danger border-danger-subtle',       'Returned'],
            'missing'           => ['bg-secondary-subtle text-secondary border-secondary-subtle','Missing'],
            'pending'           => ['bg-warning-subtle text-warning border-warning-subtle',    'Pending'],
        ];
        $cls = $map[$s][0] ?? 'bg-secondary-subtle text-secondary border-secondary-subtle';
        $lbl = $map[$s][1] ?? ($status !== '' ? $status : 'Unknown');
        return '<span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold text-nowrap ' . $cls . '" style="font-size:.7rem;">' . eh($lbl) . '</span>';
    }
}

/* ============================================================
   If the Dean has no departments, short-circuit
   ============================================================ */
if (!$hasDeanDepartments) {
    $pageTitle    = 'Department Clearance Sign-off';
    $activeModule = 'faculty';
    $activePage   = 'department-clearance-signoff';
    $breadcrumbs  = [
        ['label' => 'Dean', 'url' => null],
        ['label' => 'Department Clearance', 'url' => null],
    ];
    require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
    require_once __DIR__ . '/../../../../includes/layout-start.php';
    renderBreadcrumbs($breadcrumbs);
    echo '<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">';
    echo '<div class="card border shadow-sm"><div class="card-body text-center py-5">';
    echo '<div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:84px;height:84px;background:rgba(100,116,139,.10);color:#64748b;font-size:2.2rem;">';
    echo '<i class="fas fa-building-circle-xmark"></i></div>';
    echo '<h4 class="fw-bold mb-2 text-body-emphasis">No departments assigned</h4>';
    echo '<p class="text-body-secondary mb-0 mx-auto" style="max-width:420px;">Your account doesn\'t have any department access yet. Contact your administrator.</p>';
    echo '</div></div></div>';
    require_once __DIR__ . '/../../../../includes/layout-end.php';
    exit;
}

/* ============================================================
   Handle POST actions
   ============================================================ */
$flash = null;
$flashType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    $action      = (string) ($_POST['action'] ?? '');
    $clearanceId = (int) ($_POST['clearance_id'] ?? 0);

    if ($clearanceId > 0) {
        try {
            /* Load clearance + faculty (RBAC: faculty must be in dean's departments) */
            $deptList = implode(',', array_map('intval', $deanDepartments));
            $stmt = $pdo->prepare("
                SELECT cr.*, f.faculty_no, f.first_name, f.middle_name, f.last_name, f.email,
                       f.department_id, d.name AS dept_name
                FROM faculty_db.clearance_requests cr
                INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
                LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
                WHERE cr.clearance_id = ?
                  AND f.department_id IN ($deptList)
                LIMIT 1
            ");
            $stmt->execute([$clearanceId]);
            $cl = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cl) {
                $flash = 'Clearance not found, or it is outside your assigned departments.';
                $flashType = 'danger';
            } else {
                $facultyId   = (int) $cl['faculty_id'];
                $facultyName = dcsFullName($cl);
                $deptName    = $cl['dept_name'] ?? '';

                /* Verify office 5006 is currently cleared by DH (or approved) */
                $itemStmt = $pdo->prepare("
                    SELECT clearance_item_id, status, remarks, cleared_at, cleared_by_external_id
                    FROM faculty_db.clearance_items
                    WHERE clearance_id = ? AND clearance_office_id = ?
                    LIMIT 1
                ");
                $itemStmt->execute([$clearanceId, $DEPT_OFFICE_ID]);
                $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

                $itemStatus    = strtolower((string) ($item['status'] ?? 'missing'));
                $isReady       = in_array($itemStatus, ['cleared', 'approved'], true);
                $alreadySigned = ($itemStatus === 'approved by dean');

                /* -------------------- action: approve -------------------- */
                if ($action === 'approve') {
                    if ($alreadySigned) {
                        $flash = 'This department clearance has already been countersigned.';
                        $flashType = 'warning';
                    } elseif (!$isReady) {
                        $flash = 'Cannot approve — the Department Head has not cleared this item yet.';
                        $flashType = 'warning';
                    } else {
                        $note = trim((string) ($_POST['approval_note'] ?? ''));
                        if ($note === '') {
                            $note = 'Department clearance reviewed and countersigned by the Dean.';
                        }
                        $ref = 'DCA-' . $clearanceId . '-' . date('YmdHis');

                        /* 1. Update clearance_items status to countersigned */
                        $upd = $pdo->prepare("
                            UPDATE faculty_db.clearance_items
                            SET status  = 'Approved by Dean',
                                remarks = CONCAT(COALESCE(remarks, ''), CASE WHEN COALESCE(remarks, '') = '' THEN '' ELSE '\n' END, '[Dean ' . ? . '] ' . ?)
                            WHERE clearance_id = ? AND clearance_office_id = ?
                        ");
                        $upd->execute([$currentUserName, $note, $clearanceId, $DEPT_OFFICE_ID]);

                        /* 2. Insert Dean signature */
                        try {
                            $sig = $pdo->prepare("
                                INSERT INTO faculty_db.clearance_signatures
                                    (clearance_id, faculty_id, office, office_key, signatory_type,
                                     approval_ref, signer_user_id, signer_name, signer_role,
                                     signature_data, remarks, status, signed_at)
                                VALUES (?, ?, 'Department Clearance', 'dean_department_clearance', 'dean',
                                        ?, ?, ?, ?, ?, ?, 'Signed', NOW())
                                ON DUPLICATE KEY UPDATE
                                    signer_user_id = VALUES(signer_user_id),
                                    signer_name    = VALUES(signer_name),
                                    signer_role    = VALUES(signer_role),
                                    remarks        = VALUES(remarks),
                                    status         = 'Signed',
                                    signed_at      = NOW(),
                                    updated_at     = NOW()
                            ");
                            $sig->execute([
                                $clearanceId,
                                $facultyId,
                                $ref,
                                $currentUserId,
                                $currentUserName,
                                $currentUserRoleLbl,
                                'data:text/plain;base64,' . base64_encode($currentUserName . ' — ' . $currentRoleLbl . ' — ' . date('Y-m-d H:i:s')),
                                $note,
                            ]);
                        } catch (Throwable $e) {
                            error_log('Dean department clearance signature failed: ' . $e->getMessage());
                        }

                        /* 3. Audit history */
                        $ins = $pdo->prepare("
                            INSERT INTO faculty_db.clearance_approval_history
                                (clearance_id, requirement_id, office, action,
                                 performed_by_id, performed_by_name, performed_by_role, remarks)
                            VALUES (?, NULL, ?, 'Department Clearance Approved', ?, ?, ?, ?)
                        ");
                        $ins->execute([$clearanceId, $DEAN_OFFICE_LBL, $currentUserId, $currentUserName, $currentUserRoleLbl, $note]);

                        /* 4. Notify faculty */
                        try {
                            $notif = $pdo->prepare("
                                INSERT INTO faculty_db.notifications
                                    (faculty_id, title, message, priority, notification_type, is_read)
                                VALUES (?, ?, ?, 'Medium', 'clearance', 0)
                            ");
                            $notif->execute([
                                $facultyId,
                                'Department Clearance Countersigned',
                                'Your Department Clearance has been reviewed and countersigned by the Dean. Reference: ' . $ref,
                            ]);
                        } catch (Throwable $e) {
                            error_log('Dean department clearance notification failed: ' . $e->getMessage());
                        }

                        $flash = 'Department clearance approved and countersigned. Reference: ' . $ref;
                        $flashType = 'success';
                    }
                }

                /* -------------------- action: return -------------------- */
                elseif ($action === 'return') {
                    $message = trim((string) ($_POST['return_message'] ?? ''));
                    if ($message === '') {
                        $flash = 'Please provide revision notes before returning the record.';
                        $flashType = 'warning';
                    } else {
                        /* 1. Update clearance_items — mark returned */
                        $upd = $pdo->prepare("
                            UPDATE faculty_db.clearance_items
                            SET status  = 'Returned',
                                remarks = CONCAT(COALESCE(remarks, ''), CASE WHEN COALESCE(remarks, '') = '' THEN '' ELSE '\n' END, '[Dean Return ' . ? . '] ' . ?)
                            WHERE clearance_id = ? AND clearance_office_id = ?
                        ");
                        $upd->execute([$currentUserName, $message, $clearanceId, $DEPT_OFFICE_ID]);

                        /* 2. Audit history */
                        $ins = $pdo->prepare("
                            INSERT INTO faculty_db.clearance_approval_history
                                (clearance_id, requirement_id, office, action,
                                 performed_by_id, performed_by_name, performed_by_role, remarks)
                            VALUES (?, NULL, ?, 'Returned by Dean', ?, ?, ?, ?)
                        ");
                        $ins->execute([$clearanceId, $DEAN_OFFICE_LBL, $currentUserId, $currentUserName, $currentUserRoleLbl, $message]);

                        /* 3. Notify faculty (high priority) */
                        try {
                            $notif = $pdo->prepare("
                                INSERT INTO faculty_db.notifications
                                    (faculty_id, title, message, priority, notification_type, is_read)
                                VALUES (?, ?, ?, 'High Priority', 'clearance', 0)
                            ");
                            $notif->execute([
                                $facultyId,
                                'Department Clearance Returned — Revision Required',
                                $message,
                            ]);
                        } catch (Throwable $e) {
                            error_log('Dean department clearance return notification failed: ' . $e->getMessage());
                        }

                        $flash = 'Department clearance returned to faculty with revision notes.';
                        $flashType = 'warning';
                    }
                }

                /* -------------------- action: unreviewed approve guard -------------------- */
                else {
                    $flash = 'Unknown action.';
                    $flashType = 'danger';
                }
            }
        } catch (Throwable $e) {
            error_log('Dean department clearance action failed: ' . $e->getMessage());
            $flash = 'Action failed: ' . $e->getMessage();
            $flashType = 'danger';
        }
    }

    /* PRG */
    $_SESSION['dcs_flash']      = $flash;
    $_SESSION['dcs_flash_type'] = $flashType;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!empty($_SESSION['dcs_flash'])) {
    $flash     = (string) $_SESSION['dcs_flash'];
    $flashType = (string) ($_SESSION['dcs_flash_type'] ?? 'info');
    unset($_SESSION['dcs_flash'], $_SESSION['dcs_flash_type']);
}

/* ============================================================
   Load queue — clearances where office 5006 is Cleared/Approved
   (DH has acted, waiting on Dean) OR already Dean-approved
   ============================================================ */
$queue    = [];
$auditLog = [];
$loadError = null;

if ($pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        $q = $pdo->query("
            SELECT
                cr.clearance_id,
                cr.clearance_no,
                cr.faculty_id,
                cr.term_id,
                cr.intent_type,
                cr.overall_status,
                cr.form_submitted,
                cr.form_submitted_at,
                cr.form_status,
                cr.faculty_declared,
                cr.faculty_declared_at,
                cr.submitted_at,
                f.faculty_no,
                f.first_name,
                f.middle_name,
                f.last_name,
                f.email,
                f.department_id,
                d.code AS dept_code,
                d.name AS dept_name,
                at.academic_year,
                at.semester,
                ci.clearance_item_id,
                ci.status            AS dept_status,
                ci.remarks           AS dept_remarks,
                ci.file_path         AS dept_file_path,
                ci.original_name     AS dept_file_name,
                ci.cleared_at        AS dept_cleared_at,
                ci.cleared_by_external_id AS dept_cleared_by
            FROM faculty_db.clearance_requests cr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            LEFT JOIN faculty_db.academic_terms at ON at.term_id = cr.term_id
            LEFT JOIN faculty_db.clearance_items ci
                   ON ci.clearance_id = cr.clearance_id
                  AND ci.clearance_office_id = 5006
            WHERE f.department_id IN ($deptList)
              AND cr.form_submitted = 1
              AND ci.status IN ('Cleared', 'Approved', 'Approved by Dean', 'Returned')
            ORDER BY COALESCE(ci.cleared_at, cr.updated_at) DESC
        ");
        $queue = $q->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($queue)) {
            $clearanceIds = array_map(fn($r) => (int) $r['clearance_id'], $queue);
            $idList = implode(',', $clearanceIds);

            /* Dean audit history for these clearances */
            $histStmt = $pdo->query("
                SELECT id, clearance_id, office, action, performed_by_id, performed_by_name,
                       performed_by_role, remarks, created_at
                FROM faculty_db.clearance_approval_history
                WHERE clearance_id IN ($idList) AND office = 'Dean'
                ORDER BY created_at DESC, id DESC
            ");
            $histByClearance = [];
            foreach ($histStmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
                $histByClearance[(int) $h['clearance_id']][] = $h;
            }

            /* Full 6-office progress for context */
            $itemsStmt = $pdo->query("
                SELECT clearance_id, clearance_office_id, status, remarks, cleared_at
                FROM faculty_db.clearance_items
                WHERE clearance_id IN ($idList)
            ");
            $itemsByClearance = [];
            foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $itemsByClearance[(int) $it['clearance_id']][] = $it;
            }

            foreach ($queue as &$row) {
                $cid = (int) $row['clearance_id'];
                $row['_history'] = $histByClearance[$cid] ?? [];
                $row['_items']   = $itemsByClearance[$cid] ?? [];

                $st = strtolower((string) $row['dept_status']);
                $row['_is_ready']    = in_array($st, ['cleared', 'approved'], true);
                $row['_is_signed']   = ($st === 'approved by dean');
                $row['_is_returned'] = ($st === 'returned');
            }
            unset($row);
        }

        /* Recent Dean activity on department clearances */
        $logStmt = $pdo->query("
            SELECT h.id, h.clearance_id, h.action, h.remarks, h.performed_by_name,
                   h.performed_by_role, h.created_at,
                   f.first_name, f.last_name, f.faculty_no
            FROM faculty_db.clearance_approval_history h
            LEFT JOIN faculty_db.clearance_requests cr ON cr.clearance_id = h.clearance_id
            LEFT JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            WHERE h.office = 'Dean'
              AND h.action IN ('Department Clearance Approved', 'Returned by Dean')
            ORDER BY h.created_at DESC, h.id DESC
            LIMIT 20
        ");
        $auditLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log('Dean department clearance load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   KPIs
   ============================================================ */
$kpiPending  = 0; // awaiting Dean countersignature
$kpiSigned   = 0; // already countersigned
$kpiReturned = 0; // returned by Dean
foreach ($queue as $row) {
    if ($row['_is_signed'])   $kpiSigned++;
    elseif ($row['_is_returned']) $kpiReturned++;
    elseif ($row['_is_ready'])    $kpiPending++;
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Department Clearance Sign-off';
$activeModule = 'faculty';
$activePage   = 'department-clearance-signoff';
$breadcrumbs  = [
    ['label' => 'Dean', 'url' => null],
    ['label' => 'Department Clearance', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="dcsPage">

    <?php if ($loadError): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><?= eh($loadError) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= eh($flashType) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-circle-info"></i>
            <div><?= eh($flash) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Department Clearance Sign-off</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — internal administrative review.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-building-circle-check text-primary"></i>
                <span>Department Clearance Sign-off</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Review and countersign Department Head clearance for the department<?= count($deanDepartments) > 1 ? 's' : '' ?> you oversee.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0 small fw-semibold"
                   for="privacyModeToggle" style="cursor:pointer;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="printBtn">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-4">
            <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Sign-off</h6>
                        <h4 class="mb-0 fw-bold text-warning"><?= (int) $kpiPending ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-warning" style="font-size:0.7rem;">Needs your action</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-lg-4">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Signed Off</h6>
                        <h4 class="mb-0 fw-bold text-success"><?= (int) $kpiSigned ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-success" style="font-size:0.7rem;">Completed by you</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-4">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#dc3545;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(220,53,69,0.14);color:#dc3545;">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Returned</h6>
                        <h4 class="mb-0 fw-bold text-danger"><?= (int) $kpiReturned ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-danger" style="font-size:0.7rem;">Sent back for revision</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_term">Term</label>
                    <select class="form-select form-select-sm" id="f_term" data-filter="term">
                        <option value="">All terms</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_status">Status</label>
                    <select class="form-select form-select-sm" id="f_status" data-filter="status">
                        <option value="">All statuses</option>
                        <option value="ready">Pending Sign-off</option>
                        <option value="signed">Signed Off</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-4">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_search">Search</label>
                    <input type="text" class="form-control form-control-sm" id="f_search" data-filter="search" placeholder="Name or clearance no…">
                </div>
                <div class="col-12 col-lg-2 ms-lg-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="resetFilters">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Two-Column Layout ================= -->
    <div class="row g-3 align-items-start">

        <!-- LEFT: Queue -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card border shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-bold">Department Queue</h5>
                    <span class="badge text-bg-light border" id="queueCountBadge"><?= count($queue) ?></span>
                </div>
                <div class="card-body p-2 dcs-scroll" style="max-height:640px;overflow-y:auto;">
                    <div id="queueList" class="d-flex flex-column gap-2">
                        <?php if (empty($queue)): ?>
                            <div class="text-center py-5 text-body-secondary">
                                <i class="fas fa-clipboard-check d-block mb-2" style="font-size:1.8rem;opacity:.4;"></i>
                                <span class="small">No department clearances awaiting sign-off.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($queue as $row): ?>
                                <?php
                                    $cid      = (int) $row['clearance_id'];
                                    $name     = dcsFullName($row);
                                    $init     = dcsInitials($row);
                                    $dept     = $row['dept_name'] ?: ($row['dept_code'] ?: '—');
                                    $term     = ($row['academic_year'] ?? '—') . ' · ' . ($row['semester'] ?? '—');
                                    $isReady  = (bool) $row['_is_ready'];
                                    $isSigned = (bool) $row['_is_signed'];
                                    $isRet    = (bool) $row['_is_returned'];

                                    if ($isSigned)      { $stateLbl = 'Signed'; $stateCls = 'bg-success-subtle text-success border-success-subtle'; }
                                    elseif ($isRet)     { $stateLbl = 'Returned'; $stateCls = 'bg-danger-subtle text-danger border-danger-subtle'; }
                                    elseif ($isReady)   { $stateLbl = 'Ready'; $stateCls = 'bg-warning-subtle text-warning border-warning-subtle'; }
                                    else                { $stateLbl = 'Blocked'; $stateCls = 'bg-secondary-subtle text-secondary border-secondary-subtle'; }
                                ?>
                                <div class="dcs-queue-item d-flex align-items-start gap-2 p-2 rounded-3 border bg-body-tertiary"
                                     data-clearance-id="<?= $cid ?>"
                                     data-name="<?= eh(strtolower($name)) ?>"
                                     data-no="<?= eh(strtolower($row['clearance_no'] ?? '')) ?>"
                                     data-term="<?= eh($row['term_id'] ?? '') ?>"
                                     data-status="<?= $isSigned ? 'signed' : ($isRet ? 'returned' : ($isReady ? 'ready' : 'blocked')) ?>"
                                     style="cursor:pointer;">
                                    <div class="dcs-avatar flex-shrink-0"><?= eh($init) ?></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold small text-body-emphasis text-truncate dcs-privacy-target" title="<?= eh($name) ?>"><?= eh($name) ?></div>
                                        <div class="text-body-secondary text-truncate" style="font-size:.7rem;">
                                            <?= eh($row['faculty_no'] ?? '—') ?> · <?= eh($dept) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                            <span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold <?= $stateCls ?>" style="font-size:.62rem;"><?= eh($stateLbl) ?></span>
                                            <span class="text-body-secondary" style="font-size:.62rem;"><?= eh($term) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Detail -->
        <div class="col-12 col-lg-7 col-xl-8">
            <div id="detailPanel" class="card border shadow-sm">
                <div class="card-body text-center py-5 text-body-secondary">
                    <i class="fas fa-hand-pointer d-block mb-2" style="font-size:2rem;opacity:.35;"></i>
                    <h5 class="fw-bold mb-1 text-body-emphasis">Select a record</h5>
                    <p class="mb-0 small">Choose a faculty member on the left to review the department clearance and countersign it.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Recent Dean Activity ================= -->
    <div class="card border shadow-sm mt-4 no-print">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold">Recent Dean Activity</h5>
                <p class="text-body-secondary small mb-0">Latest 20 department clearance actions you performed</p>
            </div>
            <span class="badge text-bg-light border"><?= count($auditLog) ?></span>
        </div>
        <div class="dcs-scroll" style="max-height:360px;overflow-y:auto;">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="min-width:720px;">
                    <thead class="sticky-top">
                        <tr>
                            <th class="text-uppercase small fw-bold text-body-secondary">When</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Action</th>
                            <th class="text-uppercase small fw-bold text-body-secondary d-none d-md-table-cell">By</th>
                            <th class="text-uppercase small fw-bold text-body-secondary d-none d-lg-table-cell">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLog)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-body-secondary">No Dean department clearance actions recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($auditLog as $h): ?>
                                <?php
                                    $hName = trim(($h['first_name'] ?? '') . ' ' . ($h['last_name'] ?? '')) ?: '—';
                                    $act   = (string) $h['action'];
                                    $badge = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                                    if ($act === 'Department Clearance Approved') $badge = 'bg-success-subtle text-success border-success-subtle';
                                    if ($act === 'Returned by Dean')              $badge = 'bg-danger-subtle text-danger border-danger-subtle';
                                ?>
                                <tr>
                                    <td class="small text-nowrap"><?= eh(dcsFmtDt($h['created_at'])) ?></td>
                                    <td class="dcs-privacy-target">
                                        <div class="fw-semibold small"><?= eh($hName) ?></div>
                                        <div class="small text-body-secondary"><?= eh($h['faculty_no'] ?? '—') ?></div>
                                    </td>
                                    <td><span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold <?= $badge ?>" style="font-size:.7rem;"><?= eh($act) ?></span></td>
                                    <td class="small text-body-secondary d-none d-md-table-cell"><?= eh($h['performed_by_name'] ?? '—') ?></td>
                                    <td class="small text-body-secondary d-none d-lg-table-cell" style="max-width:280px;">
                                        <div class="text-truncate" title="<?= eh($h['remarks'] ?? '') ?>"><?= eh($h['remarks'] ?? '—') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detail template -->
<template id="detailTemplate">
    <div class="card-body">
        <!-- Header -->
        <div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
            <div class="dcs-avatar-lg" id="dt-avatar">--</div>
            <div class="flex-grow-1 min-w-0">
                <h5 class="mb-1 fw-bold dcs-privacy-target" id="dt-name">—</h5>
                <div class="text-body-secondary small mb-1">
                    <span id="dt-faculty-no">—</span> · <span id="dt-dept">—</span>
                </div>
                <div class="d-flex flex-wrap gap-2 small text-body-secondary">
                    <span><i class="fas fa-layer-group me-1"></i><span id="dt-term">—</span></span>
                    <span>·</span>
                    <span><i class="fas fa-file-signature me-1"></i><span id="dt-intent">—</span></span>
                </div>
            </div>
            <div class="text-end">
                <span id="dt-state" class="d-inline-block px-2 py-1 rounded-2 border fw-semibold" style="font-size:.72rem;">—</span>
            </div>
        </div>

        <!-- Status alert -->
        <div class="alert d-flex align-items-center gap-2 mb-3" id="dt-alert" role="alert">
            <i class="fas fa-hourglass-half"></i>
            <div id="dt-alert-text">Loading…</div>
        </div>

        <!-- Department clearance detail -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Department Clearance</h6>
        <div class="p-3 rounded-3 border bg-body-tertiary mb-3" id="dt-dept-block"></div>

        <!-- 6-office progress (context) -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Overall Office Progress</h6>
        <div class="row g-2 mb-3" id="dt-offices"></div>

        <!-- Actions -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Actions</h6>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <form method="POST" class="d-inline" id="form-approve">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="clearance_id" value="">
                <input type="hidden" name="approval_note" id="approve-note-input" value="">
                <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" id="btn-approve"
                        data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="fas fa-stamp"></i>Approve &amp; Countersign
                </button>
            </form>
            <button type="button" class="btn btn-danger btn-sm d-inline-flex align-items-center gap-2" id="btn-return"
                    data-bs-toggle="modal" data-bs-target="#returnModal">
                <i class="fas fa-rotate-left"></i>Return with Notes
            </button>
        </div>

        <!-- History -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Dean Action History</h6>
        <div id="dt-history" class="d-flex flex-column gap-2"></div>
    </div>
</template>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="approveForm">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="clearance_id" id="approve-clearance-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-stamp text-success me-2"></i>Approve &amp; Countersign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">
                    You are about to apply the Dean's countersignature on the Department Clearance office.
                    A reference code will be generated and the faculty will be notified.
                </p>
                <label class="form-label small fw-bold text-uppercase">Approval Note (optional)</label>
                <textarea name="approval_note" class="form-control" rows="3" placeholder="Optional — a short note to accompany the countersignature."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Approve &amp; Countersign</button>
            </div>
        </form>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="returnForm">
            <input type="hidden" name="action" value="return">
            <input type="hidden" name="clearance_id" id="return-clearance-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-rotate-left text-danger me-2"></i>Return with Revision Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">
                    The record will be returned to the faculty with your notes. They must re-submit the department clearance item.
                </p>
                <label class="form-label small fw-bold text-uppercase">Revision Notes <span class="text-danger">*</span></label>
                <textarea name="return_message" class="form-control" rows="4" required
                          placeholder="Explain what needs to be corrected or resubmitted."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-2"></i>Return Record</button>
            </div>
        </form>
    </div>
</div>

<style>
    .dcs-scroll { scrollbar-width: thin; scrollbar-color: rgba(13,110,253,.3) transparent; }
    .dcs-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .dcs-scroll::-webkit-scrollbar-track { background: transparent; }
    .dcs-scroll::-webkit-scrollbar-thumb { background-color: rgba(13,110,253,.3); border-radius: 10px; }
    .dcs-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(13,110,253,.6); }

    .dcs-avatar {
        width: 36px; height: 36px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .72rem;
        background: rgba(59,130,246,.14); color: #3b82f6;
        flex-shrink: 0;
    }
    .dcs-avatar-lg {
        width: 52px; height: 52px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1rem;
        background: rgba(59,130,246,.14); color: #3b82f6;
        flex-shrink: 0;
    }

    .dcs-queue-item { transition: border-color .15s ease, background .15s ease, transform .15s ease; }
    .dcs-queue-item:hover { border-color: rgba(59,130,246,.35) !important; transform: translateY(-1px); }
    .dcs-queue-item.dcs-active { border-color: #3b82f6 !important; background: rgba(59,130,246,.08); }

    .privacy-mode .dcs-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter .15s ease;
    }
    .privacy-mode .dcs-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    @media (max-width: 400px) {
        #dcsPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #dcsPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: .7rem .6rem .7rem .9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: .62rem !important; letter-spacing: .03em !important; }
        .stat-card small { font-size: .65rem !important; }
        .dcs-avatar { width: 32px; height: 32px; font-size: .68rem; }
        .dcs-avatar-lg { width: 44px; height: 44px; font-size: .85rem; }
    }

    @media print {
        .no-print, .breadcrumb, nav, header, footer, .btn, button, .modal, .dcs-scroll { display: none !important; }
    }
</style>

<script>
(function () {
    'use strict';

    const QUEUE = <?= json_encode(array_map(function ($r) {
        return [
            'clearance_id'   => (int) $r['clearance_id'],
            'clearance_no'   => $r['clearance_no'] ?? '',
            'faculty_id'     => (int) $r['faculty_id'],
            'faculty_no'     => $r['faculty_no'] ?? '',
            'faculty_name'   => dcsFullName($r),
            'initials'       => dcsInitials($r),
            'department'     => $r['dept_name'] ?: ($r['dept_code'] ?: '—'),
            'term'           => ($r['academic_year'] ?? '—') . ' · ' . ($r['semester'] ?? '—'),
            'intent'         => ucfirst((string) ($r['intent_type'] ?? 'renewal')),
            'term_id'        => (int) ($r['term_id'] ?? 0),
            'dept_status'    => (string) ($r['dept_status'] ?? 'Missing'),
            'dept_remarks'   => (string) ($r['dept_remarks'] ?? ''),
            'dept_file_path' => (string) ($r['dept_file_path'] ?? ''),
            'dept_file_name' => (string) ($r['dept_file_name'] ?? ''),
            'dept_cleared_at'=> (string) ($r['dept_cleared_at'] ?? ''),
            'is_ready'       => (bool) $r['_is_ready'],
            'is_signed'      => (bool) $r['_is_signed'],
            'is_returned'    => (bool) $r['_is_returned'],
            'items'          => array_map(function ($it) {
                return [
                    'office_id' => (int) $it['clearance_office_id'],
                    'status'    => (string) $it['status'],
                ];
            }, $r['_items']),
            'history'        => array_map(function ($h) {
                return [
                    'action'            => (string) $h['action'],
                    'remarks'           => (string) ($h['remarks'] ?? ''),
                    'performed_by_name' => (string) ($h['performed_by_name'] ?? ''),
                    'created_at'        => (string) ($h['created_at'] ?? ''),
                ];
            }, $r['_history']),
        ];
    }, $queue), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const OFFICE_NAMES = {
        5005: 'Academic Clearance',
        5006: 'Department Clearance',
        5007: 'Library Clearance',
        5008: 'Property Clearance',
        5009: 'Financial Clearance',
        5010: 'HR Clearance'
    };

    const QUEUE_MAP = {};
    QUEUE.forEach(q => QUEUE_MAP[String(q.clearance_id)] = q);

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function statusPill(status) {
        const s = String(status || '').toLowerCase();
        const map = {
            'cleared':          ['bg-info-subtle text-info border-info-subtle', 'Cleared by DH'],
            'approved':         ['bg-info-subtle text-info border-info-subtle', 'Approved by DH'],
            'approved by dean': ['bg-success-subtle text-success border-success-subtle', 'Approved by Dean'],
            'returned':         ['bg-danger-subtle text-danger border-danger-subtle', 'Returned'],
            'hold':             ['bg-warning-subtle text-warning border-warning-subtle', 'On Hold'],
            'missing':          ['bg-secondary-subtle text-secondary border-secondary-subtle', 'Missing'],
            'pending':          ['bg-warning-subtle text-warning border-warning-subtle', 'Pending'],
        };
        const v = map[s] || ['bg-secondary-subtle text-secondary border-secondary-subtle', status || 'Unknown'];
        return '<span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold text-nowrap ' + v[0] + '" style="font-size:.7rem;">' + escapeHtml(v[1]) + '</span>';
    }

    const detailPanel = document.getElementById('detailPanel');
    const template = document.getElementById('detailTemplate');

    function selectClearance(cid) {
        const data = QUEUE_MAP[String(cid)];
        if (!data) return;

        document.querySelectorAll('.dcs-queue-item').forEach(el => {
            el.classList.toggle('dcs-active', el.dataset.clearanceId === String(cid));
        });

        detailPanel.innerHTML = '';
        detailPanel.appendChild(template.content.cloneNode(true));

        document.getElementById('dt-avatar').textContent    = data.initials;
        document.getElementById('dt-name').textContent      = data.faculty_name;
        document.getElementById('dt-faculty-no').textContent = data.faculty_no || '—';
        document.getElementById('dt-dept').textContent      = data.department;
        document.getElementById('dt-term').textContent      = data.term;
        document.getElementById('dt-intent').textContent    = data.intent;

        // State badge
        const stateEl = document.getElementById('dt-state');
        if (data.is_signed)        { stateEl.textContent = 'Signed Off';  stateEl.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold bg-success-subtle text-success border-success-subtle" style="font-size:.72rem;"'; }
        else if (data.is_returned) { stateEl.textContent = 'Returned';    stateEl.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold bg-danger-subtle text-danger border-danger-subtle" style="font-size:.72rem;"'; }
        else if (data.is_ready)    { stateEl.textContent = 'Ready';       stateEl.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold bg-warning-subtle text-warning border-warning-subtle" style="font-size:.72rem;"'; }
        else                       { stateEl.textContent = 'Blocked';     stateEl.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold bg-secondary-subtle text-secondary border-secondary-subtle" style="font-size:.72rem;"'; }

        // Alert
        const alertEl = document.getElementById('dt-alert');
        const alertText = document.getElementById('dt-alert-text');
        if (data.is_signed) {
            alertEl.className = 'alert alert-success d-flex align-items-center gap-2 mb-3';
            alertText.innerHTML = '<strong>Already signed off.</strong> This department clearance has been countersigned by the Dean.';
        } else if (data.is_returned) {
            alertEl.className = 'alert alert-danger d-flex align-items-center gap-2 mb-3';
            alertText.innerHTML = '<strong>Returned for revision.</strong> The faculty needs to resubmit the department clearance item.';
        } else if (data.is_ready) {
            alertEl.className = 'alert alert-warning d-flex align-items-center gap-2 mb-3';
            alertText.innerHTML = '<strong>Ready for review.</strong> The Department Head has cleared this item. Review the details and countersign, or return with notes.';
        } else {
            alertEl.className = 'alert alert-secondary d-flex align-items-center gap-2 mb-3';
            alertText.innerHTML = '<strong>Not yet ready.</strong> The Department Head has not cleared this item yet.';
        }

        // Department clearance block
        const deptBlock = document.getElementById('dt-dept-block');
        const fileLink = data.dept_file_path
            ? '<a href="' + escapeHtml(data.dept_file_path) + '" target="_blank" rel="noopener" class="small"><i class="fas fa-paperclip me-1"></i>' + escapeHtml(data.dept_file_name || 'file') + '</a>'
            : '<span class="small text-body-secondary">No file attached</span>';
        deptBlock.innerHTML = `
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                <div class="fw-bold text-body-emphasis">Department Clearance (Office 5006)</div>
                ${statusPill(data.dept_status)}
            </div>
            <div class="small text-body-secondary mb-2">
                <div class="mb-1">${fileLink}</div>
                ${data.dept_cleared_at ? '<div><i class="fas fa-clock me-1"></i>Cleared by DH on ' + escapeHtml(data.dept_cleared_at) + '</div>' : ''}
            </div>
            ${data.dept_remarks ? '<div class="small p-2 rounded-2 bg-body-secondary"><strong>DH remarks:</strong> ' + escapeHtml(data.dept_remarks) + '</div>' : ''}
        `;

        // 6-office grid
        const officeGrid = document.getElementById('dt-offices');
        const officeMap = {};
        data.items.forEach(it => officeMap[it.office_id] = it.status);
        const officeIds = [5005, 5006, 5007, 5008, 5009, 5010];
        officeGrid.innerHTML = officeIds.map(oid => {
            const status = officeMap[oid] || 'Missing';
            const isDept = oid === 5006;
            return `
                <div class="col-12 col-md-6">
                    <div class="p-2 rounded-3 border ${isDept ? 'border-primary' : 'bg-body-tertiary'}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div class="fw-bold small text-body-emphasis">${escapeHtml(OFFICE_NAMES[oid] || ('Office #' + oid))}</div>
                            ${statusPill(status)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Wire hidden ids
        document.querySelector('#form-approve input[name="clearance_id"]').value = cid;
        document.getElementById('approve-clearance-id').value = cid;
        document.getElementById('return-clearance-id').value = cid;

        // Enable/disable actions
        const btnApprove = document.getElementById('btn-approve');
        const btnReturn  = document.getElementById('btn-return');

        if (!data.is_ready) {
            btnApprove.disabled = true; btnApprove.classList.add('disabled');
            btnReturn.disabled  = true; btnReturn.classList.add('disabled');
        } else {
            btnApprove.disabled = false; btnApprove.classList.remove('disabled');
            btnReturn.disabled  = false; btnReturn.classList.remove('disabled');
        }

        // History
        const histEl = document.getElementById('dt-history');
        if (!data.history.length) {
            histEl.innerHTML = '<div class="text-center py-3 text-body-secondary small">No Dean actions on this record yet.</div>';
        } else {
            histEl.innerHTML = data.history.map(h => {
                const badgeMap = {
                    'Department Clearance Approved': 'bg-success-subtle text-success border-success-subtle',
                    'Returned by Dean':              'bg-danger-subtle text-danger border-danger-subtle'
                };
                const cls = badgeMap[h.action] || 'bg-secondary-subtle text-secondary border-secondary-subtle';
                return `
                    <div class="p-2 rounded-3 border bg-body-tertiary">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1 flex-wrap">
                            <span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold ${cls}" style="font-size:.68rem;">${escapeHtml(h.action)}</span>
                            <small class="text-body-secondary">${escapeHtml(h.created_at)}</small>
                        </div>
                        <div class="small text-body-secondary mb-1">
                            <i class="fas fa-user me-1"></i>${escapeHtml(h.performed_by_name || '—')}
                        </div>
                        ${h.remarks ? '<div class="small">' + escapeHtml(h.remarks) + '</div>' : ''}
                    </div>
                `;
            }).join('');
        }

        if (window.innerWidth < 992) {
            detailPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Queue click
    document.getElementById('queueList').addEventListener('click', function (e) {
        const card = e.target.closest('.dcs-queue-item');
        if (!card) return;
        selectClearance(card.dataset.clearanceId);
    });

    // Filters
    function applyFilters() {
        const term   = (document.getElementById('f_term').value || '').toString();
        const status = (document.getElementById('f_status').value || '').toString();
        const search = (document.getElementById('f_search').value || '').toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll('.dcs-queue-item').forEach(el => {
            const elTerm   = el.dataset.term || '';
            const elStatus = el.dataset.status || '';
            const haystack = (el.dataset.name || '') + ' ' + (el.dataset.no || '');
            let ok = true;
            if (term   && elTerm !== term) ok = false;
            if (status && elStatus !== status) ok = false;
            if (search && haystack.indexOf(search) === -1) ok = false;
            el.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });

        document.getElementById('queueCountBadge').textContent = visible;
    }

    document.getElementById('f_status').addEventListener('change', applyFilters);
    document.getElementById('f_search').addEventListener('input', applyFilters);
    document.getElementById('f_term').addEventListener('change', applyFilters);

    document.getElementById('resetFilters').addEventListener('click', function () {
        document.getElementById('f_term').value = '';
        document.getElementById('f_status').value = '';
        document.getElementById('f_search').value = '';
        applyFilters();
    });

    // Term filter options — derive from queue
    (function () {
        const sel = document.getElementById('f_term');
        const seen = {};
        QUEUE.forEach(q => {
            const tid = String(q.term_id || '');
            if (!tid || seen[tid]) return;
            seen[tid] = true;
            const opt = document.createElement('option');
            opt.value = tid;
            opt.textContent = q.term;
            sel.appendChild(opt);
        });
    })();

    // Privacy toggle
    (function () {
        const pt = document.getElementById('privacyModeToggle');
        if (!pt) return;
        const KEY = 'smsDeanDeptClearancePrivacy';
        const apply = on => document.body.classList.toggle('privacy-mode', on);
        try {
            const saved = localStorage.getItem(KEY) === '1';
            pt.checked = saved; apply(saved);
        } catch (e) {}
        pt.addEventListener('change', () => {
            apply(pt.checked);
            try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) {}
        });
    })();

    document.getElementById('printBtn').addEventListener('click', () => window.print());

    // Auto-select first queue item on desktop
    if (QUEUE.length > 0 && window.innerWidth >= 992) {
        selectClearance(QUEUE[0].clearance_id);
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>