<?php
/**
 * SMS 2 - Admin - Clearance Admin Dashboard
 *
 * Implements the Faculty Admin (Administrator) Clearance Workflow.
 * NO SCHEMA CHANGES. Writes to existing tables only:
 *   - clearance_approval_history (office = 'Admin')
 *   - clearance_signatures       (signatory_type = 'admin')
 *   - notifications              (high-priority notices)
 *
 * RBAC: admin / faculty_admin / superadmin. System-wide.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

/* ============================================================
   RBAC — Admin only
   ============================================================ */
$currentRoleKey = strtolower((string) (getCurrentUserRoleKey() ?? ''));
$ADMIN_ROLE_KEYS = ['admin', 'faculty_admin', 'superadmin'];
$isAdmin = in_array($currentRoleKey, $ADMIN_ROLE_KEYS, true);

if (!$isAdmin) {
    http_response_code(403);
    $pageTitle = 'Access Denied';
    require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
    require_once __DIR__ . '/../../../../includes/layout-start.php';
    echo '<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">';
    echo '<div class="card border shadow-sm"><div class="card-body text-center py-5">';
    echo '<div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:84px;height:84px;background:rgba(220,53,69,.10);color:#dc3545;font-size:2.2rem;">';
    echo '<i class="fas fa-lock"></i></div>';
    echo '<h4 class="fw-bold mb-2">Access Denied</h4>';
    echo '<p class="text-body-secondary mb-0">This page is restricted to Faculty Administrators.</p>';
    echo '</div></div></div>';
    require_once __DIR__ . '/../../../../includes/layout-end.php';
    exit;
}

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
   Constants
   ============================================================ */
$GATE_OFFICE_IDS = [5005, 5006, 5007, 5008, 5009, 5010];
$ADMIN_OFFICE_LABEL = 'Admin';

$currentUserId      = (int) ($_SESSION['user_id'] ?? 0);
$currentUserName    = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'));
$currentUserRoleLbl = (string) ($_SESSION['role_label'] ?? ucfirst($currentRoleKey));

/* ============================================================
   Helpers
   ============================================================ */
if (!function_exists('eh')) {
    function eh($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('caFullName')) {
    function caFullName(array $r): string {
        $mid = !empty($r['middle_name']) ? ' ' . strtoupper(substr($r['middle_name'], 0, 1)) . '.' : '';
        return trim(($r['first_name'] ?? '') . $mid . ' ' . ($r['last_name'] ?? '')) ?: '—';
    }
}
if (!function_exists('caInitials')) {
    function caInitials(array $r): string {
        $s = strtoupper(substr((string) ($r['first_name'] ?? ''), 0, 1) . substr((string) ($r['last_name'] ?? ''), 0, 1));
        return $s !== '' ? $s : '—';
    }
}
if (!function_exists('caFmtDt')) {
    function caFmtDt($s): string {
        if (!$s) return '—';
        $d = strtotime((string) $s);
        return $d ? date('M j, Y g:i A', $d) : (string) $s;
    }
}
if (!function_exists('caStatusPill')) {
    function caStatusPill(string $status): string {
        $s = strtolower(trim($status));
        $map = [
            'cleared'   => ['bg-success-subtle text-success border-success-subtle', 'Cleared'],
            'approved'  => ['bg-success-subtle text-success border-success-subtle', 'Approved'],
            'hold'      => ['bg-warning-subtle text-warning border-warning-subtle', 'On Hold'],
            'on hold'   => ['bg-warning-subtle text-warning border-warning-subtle', 'On Hold'],
            'denied'    => ['bg-danger-subtle text-danger border-danger-subtle', 'Denied'],
            'rejected'  => ['bg-danger-subtle text-danger border-danger-subtle', 'Rejected'],
            'missing'   => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'Missing'],
            'pending'   => ['bg-warning-subtle text-warning border-warning-subtle', 'Pending'],
            'progress'  => ['bg-info-subtle text-info border-info-subtle', 'In Progress'],
        ];
        $cls = $map[$s][0] ?? 'bg-secondary-subtle text-secondary border-secondary-subtle';
        $lbl = $map[$s][1] ?? ($status !== '' ? $status : 'Unknown');
        return '<span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold text-nowrap ' . $cls . '" style="font-size:.7rem;">' . eh($lbl) . '</span>';
    }
}

/* ============================================================
   Handle POST actions
   ============================================================ */
$flash = null;
$flashType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    if (function_exists('csrfVerify') && !csrfVerify()) {
        $flash = 'Security check failed. Please try again.';
        $flashType = 'danger';
    } else {
        $action      = (string) ($_POST['action'] ?? '');
        $clearanceId = (int) ($_POST['clearance_id'] ?? 0);

        if ($clearanceId > 0) {
            try {
                $stmt = $pdo->prepare("
                    SELECT cr.*, f.faculty_no, f.first_name, f.middle_name, f.last_name, f.email,
                           f.department_id, d.name AS dept_name
                    FROM faculty_db.clearance_requests cr
                    INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
                    LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
                    WHERE cr.clearance_id = ? LIMIT 1
                ");
                $stmt->execute([$clearanceId]);
                $cl = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cl) {
                    $flash = 'Clearance request not found.';
                    $flashType = 'danger';
                } else {
                    $facultyId   = (int) $cl['faculty_id'];
                    $facultyName = caFullName($cl);

                    /* Count 6/6 cleared */
                    $officeStmt = $pdo->prepare("
                        SELECT ci.clearance_office_id, ci.status, co.name AS office_name
                        FROM faculty_db.clearance_items ci
                        LEFT JOIN faculty_db.clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
                        WHERE ci.clearance_id = ?
                    ");
                    $officeStmt->execute([$clearanceId]);
                    $officeRows = $officeStmt->fetchAll(PDO::FETCH_ASSOC);

                    $cleared = 0; $totalGate = 0;
                    foreach ($officeRows as $r) {
                        if (!in_array((int) $r['clearance_office_id'], $GATE_OFFICE_IDS, true)) continue;
                        $totalGate++;
                        $st = strtolower((string) $r['status']);
                        if ($st === 'cleared' || $st === 'approved') $cleared++;
                    }
                    $allCleared = ($totalGate > 0 && $cleared === $totalGate);
                    $declared   = (int) ($cl['faculty_declared'] ?? 0) === 1;

                    /* ---------------- action: audit_passed ---------------- */
                    if ($action === 'audit_passed') {
                        if ($allCleared && $declared) {
                            $ins = $pdo->prepare("
                                INSERT INTO faculty_db.clearance_approval_history
                                    (clearance_id, requirement_id, office, action,
                                     performed_by_id, performed_by_name, performed_by_role, remarks)
                                VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
                            ");
                            $ins->execute([
                                $clearanceId, $ADMIN_OFFICE_LABEL, 'Audit Passed',
                                $currentUserId, $currentUserName, $currentUserRoleLbl,
                                'Admin audit stamp — compliance audited. All 6 offices cleared and declaration signed.'
                            ]);

                            $notif = $pdo->prepare("
                                INSERT INTO faculty_db.notifications
                                    (faculty_id, title, message, priority, notification_type, is_read)
                                VALUES (?, ?, ?, 'Medium', 'clearance', 0)
                            ");
                            $notif->execute([
                                $facultyId,
                                'Clearance Audit Passed',
                                'Your faculty clearance has been audited and passed by the Admin. It will now be endorsed to the Dean/VPAA for final sign-off.'
                            ]);

                            $flash = 'Audit compliance passed and faculty notified.';
                            $flashType = 'success';
                        } else {
                            $flash = 'Cannot pass audit — not all 6 offices are cleared, or the faculty declaration is missing.';
                            $flashType = 'warning';
                        }
                    }

                    /* ---------------- action: compliance_notice ---------------- */
                    elseif ($action === 'compliance_notice') {
                        $message = trim((string) ($_POST['message'] ?? ''));
                        if ($message === '') {
                            $message = 'An administrative discrepancy was found in your clearance submission. Please review and act on the flagged items.';
                        }

                        $ins = $pdo->prepare("
                            INSERT INTO faculty_db.clearance_approval_history
                                (clearance_id, requirement_id, office, action,
                                 performed_by_id, performed_by_name, performed_by_role, remarks)
                            VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
                        ");
                        $ins->execute([
                            $clearanceId, $ADMIN_OFFICE_LABEL, 'Compliance Notice',
                            $currentUserId, $currentUserName, $currentUserRoleLbl, $message
                        ]);

                        $notif = $pdo->prepare("
                            INSERT INTO faculty_db.notifications
                                (faculty_id, title, message, priority, notification_type, is_read)
                            VALUES (?, ?, ?, 'High Priority', 'clearance', 0)
                        ");
                        $notif->execute([$facultyId, 'Compliance Notice — Action Required', $message]);

                        $flash = 'Compliance notice issued and faculty notified.';
                        $flashType = 'warning';
                    }

                    /* ---------------- action: expedite_notice ---------------- */
                    elseif ($action === 'expedite_notice') {
                        $targetOffice = trim((string) ($_POST['target_office'] ?? ''));
                        $message = trim((string) ($_POST['message'] ?? ''));
                        if ($message === '') {
                            $message = 'Please expedite the clearance review for this faculty member. The submission has been stalled at your office.';
                        }
                        $remarks = $targetOffice !== '' ? ('[' . $targetOffice . '] ' . $message) : $message;

                        $ins = $pdo->prepare("
                            INSERT INTO faculty_db.clearance_approval_history
                                (clearance_id, requirement_id, office, action,
                                 performed_by_id, performed_by_name, performed_by_role, remarks)
                            VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
                        ");
                        $ins->execute([
                            $clearanceId, $ADMIN_OFFICE_LABEL, 'Expedite Notice',
                            $currentUserId, $currentUserName, $currentUserRoleLbl, $remarks
                        ]);

                        $notif = $pdo->prepare("
                            INSERT INTO faculty_db.notifications
                                (faculty_id, title, message, priority, notification_type, is_read)
                            VALUES (?, ?, ?, 'Medium', 'clearance', 0)
                        ");
                        $notif->execute([
                            $facultyId,
                            'Office Expedite Notice',
                            'An expedite notice was sent to ' . ($targetOffice ?: 'a reviewing office') . ' regarding your pending clearance.'
                        ]);

                        $flash = 'Expedite notice recorded' . ($targetOffice ? ' for ' . $targetOffice : '') . '.';
                        $flashType = 'info';
                    }

                    /* ---------------- action: endorse_to_dean ---------------- */
                    elseif ($action === 'endorse_to_dean') {
                        if (!$allCleared || !$declared) {
                            $flash = 'Cannot endorse — all 6 offices must be cleared and the faculty declaration must be signed.';
                            $flashType = 'warning';
                        } else {
                            $note = trim((string) ($_POST['endorsement_note'] ?? ''));
                            if ($note === '') {
                                $note = 'All 6 offices cleared and faculty declaration signed. Endorsed to the Dean/VPAA for final sign-off.';
                            }

                            $ins = $pdo->prepare("
                                INSERT INTO faculty_db.clearance_approval_history
                                    (clearance_id, requirement_id, office, action,
                                     performed_by_id, performed_by_name, performed_by_role, remarks)
                                VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
                            ");
                            $ins->execute([
                                $clearanceId, $ADMIN_OFFICE_LABEL, 'Endorsed to Dean',
                                $currentUserId, $currentUserName, $currentUserRoleLbl, $note
                            ]);

                            try {
                                $sig = $pdo->prepare("
                                    INSERT INTO faculty_db.clearance_signatures
                                        (clearance_id, faculty_id, office, office_key, signatory_type,
                                         approval_ref, signer_user_id, signer_name, signer_role,
                                         signature_data, remarks, status, signed_at)
                                    VALUES (?, ?, ?, ?, 'admin', ?, ?, ?, ?, ?, ?, 'Signed', NOW())
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
                                    $clearanceId, $facultyId,
                                    'Admin Endorsement', 'admin_endorsement',
                                    'ADM-' . $clearanceId . '-' . time(),
                                    $currentUserId, $currentUserName, $currentUserRoleLbl,
                                    'data:text/plain;base64,' . base64_encode($currentUserName . ' — ' . date('Y-m-d H:i:s')),
                                    $note
                                ]);
                            } catch (Throwable $e) {
                                error_log('Endorsement signature failed: ' . $e->getMessage());
                            }

                            $notif = $pdo->prepare("
                                INSERT INTO faculty_db.notifications
                                    (faculty_id, title, message, priority, notification_type, is_read)
                                VALUES (?, ?, ?, 'Medium', 'clearance', 0)
                            ");
                            $notif->execute([
                                $facultyId,
                                'Clearance Endorsed to Dean',
                                'Your clearance has been endorsed to the Dean/VPAA for final sign-off.'
                            ]);

                            $flash = 'Clearance endorsed to the Dean/VPAA.';
                            $flashType = 'success';
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('Clearance admin action failed: ' . $e->getMessage());
                $flash = 'Action failed: ' . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    $_SESSION['clearance_admin_flash']      = $flash;
    $_SESSION['clearance_admin_flash_type'] = $flashType;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!empty($_SESSION['clearance_admin_flash'])) {
    $flash     = (string) $_SESSION['clearance_admin_flash'];
    $flashType = (string) ($_SESSION['clearance_admin_flash_type'] ?? 'info');
    unset($_SESSION['clearance_admin_flash'], $_SESSION['clearance_admin_flash_type']);
}

/* ============================================================
   Load data
   ============================================================ */
$queue         = [];
$auditLog      = [];
$officeCatalog = [];
$loadError     = null;

if ($pdo instanceof PDO) {
    try {
        $cat = $pdo->query("
            SELECT clearance_office_id, name, sequence_order
            FROM faculty_db.clearance_offices
            WHERE clearance_office_id IN (5005, 5006, 5007, 5008, 5009, 5010)
            ORDER BY sequence_order ASC
        ");
        foreach ($cat->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $officeCatalog[(int) $row['clearance_office_id']] = $row['name'];
        }

        $q = $pdo->query("
            SELECT cr.clearance_id, cr.clearance_no, cr.faculty_id, cr.term_id,
                   cr.intent_type, cr.overall_status, cr.form_submitted, cr.form_submitted_at,
                   cr.form_status, cr.faculty_declared, cr.faculty_declared_at, cr.submitted_at,
                   f.faculty_no, f.first_name, f.middle_name, f.last_name, f.email,
                   f.department_id, d.code AS dept_code, d.name AS dept_name,
                   at.academic_year, at.semester
            FROM faculty_db.clearance_requests cr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            LEFT JOIN faculty_db.academic_terms at ON at.term_id = cr.term_id
            WHERE cr.form_submitted = 1
            ORDER BY COALESCE(cr.submitted_at, cr.form_submitted_at, cr.updated_at) DESC
        ");
        $queue = $q->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($queue)) {
            $clearanceIds = array_map(fn($r) => (int) $r['clearance_id'], $queue);
            $idList = implode(',', $clearanceIds);

            $itemsStmt = $pdo->query("
                SELECT clearance_id, clearance_office_id, status, file_path, original_name,
                       remarks, cleared_at, cleared_by_external_id
                FROM faculty_db.clearance_items
                WHERE clearance_id IN ($idList)
            ");
            $itemsByClearance = [];
            foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $itemsByClearance[(int) $it['clearance_id']][] = $it;
            }

            $histStmt = $pdo->query("
                SELECT id, clearance_id, office, action, performed_by_id, performed_by_name,
                       performed_by_role, remarks, created_at
                FROM faculty_db.clearance_approval_history
                WHERE clearance_id IN ($idList) AND office = 'Admin'
                ORDER BY created_at DESC, id DESC
            ");
            $adminHistByClearance = [];
            foreach ($histStmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
                $adminHistByClearance[(int) $h['clearance_id']][] = $h;
            }

            foreach ($queue as &$row) {
                $cid = (int) $row['clearance_id'];
                $items = $itemsByClearance[$cid] ?? [];

                $gateCleared = 0; $gateTotal = 0; $officeProgress = [];
                foreach ($GATE_OFFICE_IDS as $oid) {
                    $found = null;
                    foreach ($items as $it) {
                        if ((int) $it['clearance_office_id'] === $oid) { $found = $it; break; }
                    }
                    $status  = $found ? (string) $found['status'] : 'Missing';
                    $stLower = strtolower($status);
                    if ($stLower === 'cleared' || $stLower === 'approved') $gateCleared++;
                    $gateTotal++;
                    $officeProgress[$oid] = [
                        'office_id'     => $oid,
                        'office_name'   => $officeCatalog[$oid] ?? ('Office #' . $oid),
                        'status'        => $status,
                        'file_path'     => $found['file_path'] ?? null,
                        'original_name' => $found['original_name'] ?? null,
                        'remarks'       => $found['remarks'] ?? null,
                        'cleared_at'    => $found['cleared_at'] ?? null,
                    ];
                }

                $row['_office_progress'] = $officeProgress;
                $row['_gate_cleared']    = $gateCleared;
                $row['_gate_total']      = $gateTotal;
                $row['_all_cleared']     = ($gateTotal > 0 && $gateCleared === $gateTotal);
                $row['_declared']        = (int) ($row['faculty_declared'] ?? 0) === 1;
                $row['_admin_actions']   = $adminHistByClearance[$cid] ?? [];

                $hasAudit = $hasEndorse = $hasNotice = $hasExpedite = false;
                foreach ($row['_admin_actions'] as $h) {
                    $act = strtolower((string) $h['action']);
                    if ($act === 'audit passed')     $hasAudit    = true;
                    if ($act === 'endorsed to dean') $hasEndorse  = true;
                    if ($act === 'compliance notice')$hasNotice   = true;
                    if ($act === 'expedite notice')  $hasExpedite = true;
                }
                $row['_has_audit']    = $hasAudit;
                $row['_has_endorse']  = $hasEndorse;
                $row['_has_notice']   = $hasNotice;
                $row['_has_expedite'] = $hasExpedite;
            }
            unset($row);
        }

        $logStmt = $pdo->query("
            SELECT h.id, h.clearance_id, h.action, h.remarks, h.performed_by_name,
                   h.performed_by_role, h.created_at,
                   f.first_name, f.last_name, f.faculty_no
            FROM faculty_db.clearance_approval_history h
            LEFT JOIN faculty_db.clearance_requests cr ON cr.clearance_id = h.clearance_id
            LEFT JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            WHERE h.office = 'Admin'
            ORDER BY h.created_at DESC, h.id DESC
            LIMIT 20
        ");
        $auditLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log('Clearance admin dashboard load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* ============================================================
   KPIs
   ============================================================ */
$kpiPending = $kpiPassedAudit = $kpiNotices = $kpiAwaitingEnd = 0;
foreach ($queue as $row) {
    if (!$row['_has_audit'] && !$row['_has_endorse']) $kpiPending++;
    if ($row['_has_audit'] && !$row['_has_endorse'])  $kpiAwaitingEnd++;
    if ($row['_has_audit'])                           $kpiPassedAudit++;
    if ($row['_has_notice'] || $row['_has_expedite']) $kpiNotices++;
}

/* ============================================================
   Page config
   ============================================================ */
$pageTitle    = 'Clearance Admin Dashboard';
$activeModule = 'faculty';
$activePage   = 'clearance-admin-dashboard';
$breadcrumbs  = [
    ['label' => 'Admin', 'url' => null],
    ['label' => 'Clearance Admin Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="caPage">

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
        <h4 class="mb-0 fw-bold">Clearance Admin Dashboard</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — internal administrative review.</em></p>
    </div>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-clipboard-check text-primary"></i>
                <span>Clearance Admin Dashboard</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Examine submitted clearances, audit compliance, issue notices, and endorse to the Dean / VPAA.
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
        <div class="col-6 col-lg-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Review</h6>
                        <h4 class="mb-0 fw-bold text-primary"><?= (int) $kpiPending ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-primary" style="font-size:0.7rem;">Awaiting audit</small>
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
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Passed Audit</h6>
                        <h4 class="mb-0 fw-bold text-success"><?= (int) $kpiPassedAudit ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-success" style="font-size:0.7rem;">Compliance audited</small>
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
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Notices Issued</h6>
                        <h4 class="mb-0 fw-bold text-warning"><?= (int) $kpiNotices ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate text-warning" style="font-size:0.7rem;">Compliance / expedite</small>
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
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Awaiting Endorsement</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;"><?= (int) $kpiAwaitingEnd ?></h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#ff4d4d;font-size:0.7rem;">Ready for Dean</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ================= Two-Column Layout ================= -->
    <div class="row g-3 align-items-start">

        <!-- LEFT: Queue -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card border shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-bold">Clearance Queue</h5>
                    <span class="badge text-bg-light border" id="queueCountBadge"><?= count($queue) ?></span>
                </div>
                <div class="card-body py-3 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body text-body-secondary border-light-subtle">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="queueSearch" class="form-control bg-body text-body border-light-subtle shadow-none"
                               placeholder="Search faculty or clearance no…" autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-2 ca-scroll" style="max-height:600px;overflow-y:auto;">
                    <div id="queueList" class="d-flex flex-column gap-2">
                        <?php if (empty($queue)): ?>
                            <div class="text-center py-5 text-body-secondary">
                                <i class="fas fa-inbox d-block mb-2" style="font-size:1.8rem;opacity:.4;"></i>
                                <span class="small">No submitted clearances yet.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($queue as $row): ?>
                                <?php
                                    $cid         = (int) $row['clearance_id'];
                                    $name        = caFullName($row);
                                    $init        = caInitials($row);
                                    $dept        = $row['dept_name'] ?: ($row['dept_code'] ?: '—');
                                    $terms       = ($row['academic_year'] ?? '—') . ' · ' . ($row['semester'] ?? '—');
                                    $gateCleared = (int) $row['_gate_cleared'];
                                    $gateTotal   = (int) $row['_gate_total'];
                                    $allCleared  = (bool) $row['_all_cleared'];
                                    $declared    = (bool) $row['_declared'];
                                    $hasAudit    = (bool) $row['_has_audit'];
                                    $hasEndorse  = (bool) $row['_has_endorse'];

                                    $stateLbl = 'Pending';
                                    $stateCls = 'bg-warning-subtle text-warning border-warning-subtle';
                                    if ($hasEndorse) { $stateLbl = 'Endorsed'; $stateCls = 'bg-primary-subtle text-primary border-primary-subtle'; }
                                    elseif ($hasAudit) { $stateLbl = 'Audited'; $stateCls = 'bg-success-subtle text-success border-success-subtle'; }
                                ?>
                                <div class="ca-queue-item d-flex align-items-start gap-2 p-2 rounded-3 border bg-body-tertiary"
                                     data-clearance-id="<?= $cid ?>"
                                     data-name="<?= eh(strtolower($name)) ?>"
                                     data-no="<?= eh(strtolower($row['clearance_no'] ?? '')) ?>"
                                     style="cursor:pointer;">
                                    <div class="ca-avatar flex-shrink-0"><?= eh($init) ?></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold small text-body-emphasis text-truncate ca-privacy-target" title="<?= eh($name) ?>"><?= eh($name) ?></div>
                                        <div class="text-body-secondary text-truncate" style="font-size:.7rem;">
                                            <?= eh($row['faculty_no'] ?? '—') ?> · <?= eh($dept) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                            <span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold <?= $stateCls ?>" style="font-size:.62rem;"><?= eh($stateLbl) ?></span>
                                            <span class="text-body-secondary" style="font-size:.62rem;">
                                                <?= $gateCleared ?>/<?= $gateTotal ?> offices
                                            </span>
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
                    <h5 class="fw-bold mb-1 text-body-emphasis">Select a clearance</h5>
                    <p class="mb-0 small">Choose a faculty member on the left to examine their clearance, audit compliance, and issue notices.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Recent Admin Activity ================= -->
    <div class="card border shadow-sm mt-4 no-print">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold">Recent Admin Activity</h5>
                <p class="text-body-secondary small mb-0">Latest 20 actions you performed on clearances</p>
            </div>
            <span class="badge text-bg-light border"><?= count($auditLog) ?></span>
        </div>
        <div class="ca-scroll" style="max-height:360px;overflow-y:auto;">
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
                            <tr><td colspan="5" class="text-center py-4 text-body-secondary">No admin actions recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($auditLog as $h): ?>
                                <?php
                                    $hName = trim(($h['first_name'] ?? '') . ' ' . ($h['last_name'] ?? '')) ?: '—';
                                    $act   = (string) $h['action'];
                                    $badge = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                                    if ($act === 'Audit Passed')      $badge = 'bg-success-subtle text-success border-success-subtle';
                                    if ($act === 'Endorsed to Dean')  $badge = 'bg-primary-subtle text-primary border-primary-subtle';
                                    if ($act === 'Compliance Notice') $badge = 'bg-danger-subtle text-danger border-danger-subtle';
                                    if ($act === 'Expedite Notice')   $badge = 'bg-warning-subtle text-warning border-warning-subtle';
                                ?>
                                <tr>
                                    <td class="small text-nowrap"><?= eh(caFmtDt($h['created_at'])) ?></td>
                                    <td class="ca-privacy-target">
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

<!-- Detail template (rendered client-side) -->
<template id="detailTemplate">
    <div class="card-body">
        <!-- Header -->
        <div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
            <div class="ca-avatar-lg" id="dt-avatar">--</div>
            <div class="flex-grow-1 min-w-0">
                <h5 class="mb-1 fw-bold ca-privacy-target" id="dt-name">—</h5>
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
                <div class="d-flex flex-column gap-1 align-items-end">
                    <span id="dt-audit-state" class="d-inline-block px-2 py-1 rounded-2 border fw-semibold" style="font-size:.7rem;">—</span>
                    <span id="dt-endorse-state" class="d-inline-block px-2 py-1 rounded-2 border fw-semibold" style="font-size:.7rem;">—</span>
                </div>
            </div>
        </div>

        <!-- Gate status -->
        <div class="alert d-flex align-items-center gap-2 mb-3" id="dt-gate-alert" role="alert">
            <i class="fas fa-hourglass-half"></i>
            <div id="dt-gate-text">Checking office progress…</div>
        </div>

        <!-- Office progress grid -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">6-Office Progress</h6>
        <div class="row g-2 mb-3" id="dt-offices"></div>

        <!-- Actions -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Actions</h6>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <form method="POST" class="d-inline" id="form-audit">
                <input type="hidden" name="action" value="audit_passed">
                <input type="hidden" name="clearance_id" value="">
                <button type="submit" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" id="btn-audit">
                    <i class="fas fa-stamp"></i>Approve Audit
                </button>
            </form>
            <button type="button" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-2" id="btn-notice"
                    data-bs-toggle="modal" data-bs-target="#noticeModal">
                <i class="fas fa-triangle-exclamation"></i>Issue Compliance Notice
            </button>
            <button type="button" class="btn btn-info btn-sm d-inline-flex align-items-center gap-2 text-white" id="btn-expedite"
                    data-bs-toggle="modal" data-bs-target="#expediteModal">
                <i class="fas fa-paper-plane"></i>Expedite Office
            </button>
            <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" id="btn-endorse"
                    data-bs-toggle="modal" data-bs-target="#endorseModal">
                <i class="fas fa-arrow-up-right-from-square"></i>Endorse to Dean
            </button>
        </div>

        <!-- Audit history -->
        <h6 class="text-uppercase fw-bold text-body-secondary mb-2" style="font-size:.72rem;letter-spacing:.04em;">Admin Action History</h6>
        <div id="dt-history" class="d-flex flex-column gap-2"></div>
    </div>
</template>

<!-- Notice Modal -->
<div class="modal fade" id="noticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="compliance_notice">
            <input type="hidden" name="clearance_id" id="notice-clearance-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-triangle-exclamation text-warning me-2"></i>Issue Compliance Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">This will send a <strong>high-priority</strong> notice to the faculty. Use it when an administrative discrepancy has been found.</p>
                <label class="form-label small fw-bold text-uppercase">Message</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Describe the discrepancy and what action is required."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-2"></i>Send Notice</button>
            </div>
        </form>
    </div>
</div>

<!-- Expedite Modal -->
<div class="modal fade" id="expediteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="expedite_notice">
            <input type="hidden" name="clearance_id" id="expedite-clearance-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane text-info me-2"></i>Send Office Expedite Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">Use when an office has stalled. The reviewing office will be notified to expedite the clearance.</p>
                <label class="form-label small fw-bold text-uppercase">Target Office</label>
                <select name="target_office" class="form-select mb-3">
                    <option value="">— Choose office —</option>
                    <option>Academic Clearance</option>
                    <option>Department Clearance</option>
                    <option>Library Clearance</option>
                    <option>Property Clearance</option>
                    <option>Financial Clearance</option>
                    <option>HR Clearance</option>
                </select>
                <label class="form-label small fw-bold text-uppercase">Message</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Explain the delay and request expedited action."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white"><i class="fas fa-paper-plane me-2"></i>Send Expedite</button>
            </div>
        </form>
    </div>
</div>

<!-- Endorse Modal -->
<div class="modal fade" id="endorseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="endorse_to_dean">
            <input type="hidden" name="clearance_id" id="endorse-clearance-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-arrow-up-right-from-square text-primary me-2"></i>Endorse to Dean / VPAA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">All 6 offices must be cleared and the faculty declaration signed before endorsement. This action attaches your endorsement note and forwards the clearance to the Dean/VPAA.</p>
                <label class="form-label small fw-bold text-uppercase">Endorsement Note</label>
                <textarea name="endorsement_note" class="form-control" rows="3" placeholder="Optional — a short note to accompany the endorsement."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-2"></i>Endorse to Dean</button>
            </div>
        </form>
    </div>
</div>

<style>
    .ca-scroll { scrollbar-width: thin; scrollbar-color: rgba(13,110,253,.3) transparent; }
    .ca-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .ca-scroll::-webkit-scrollbar-track { background: transparent; }
    .ca-scroll::-webkit-scrollbar-thumb { background-color: rgba(13,110,253,.3); border-radius: 10px; }
    .ca-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(13,110,253,.6); }

    .ca-avatar {
        width: 36px; height: 36px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .72rem;
        background: rgba(59,130,246,.14); color: #3b82f6;
        flex-shrink: 0;
    }
    .ca-avatar-lg {
        width: 52px; height: 52px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1rem;
        background: rgba(59,130,246,.14); color: #3b82f6;
        flex-shrink: 0;
    }

    .ca-queue-item { transition: border-color .15s ease, background .15s ease, transform .15s ease; }
    .ca-queue-item:hover { border-color: rgba(59,130,246,.35) !important; transform: translateY(-1px); }
    .ca-queue-item.ca-active { border-color: #3b82f6 !important; background: rgba(59,130,246,.08); }

    .privacy-mode .ca-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter .15s ease;
    }
    .privacy-mode .ca-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    @media (max-width: 400px) {
        #caPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #caPage h1.h4 { font-size: 1.15rem; }
        .stat-card .card-body { padding: .7rem .6rem .7rem .9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: .62rem !important; letter-spacing: .03em !important; }
        .stat-card small { font-size: .65rem !important; }
        .ca-avatar { width: 32px; height: 32px; font-size: .68rem; }
        .ca-avatar-lg { width: 44px; height: 44px; font-size: .85rem; }
    }

    @media print {
        .no-print, .breadcrumb, nav, header, footer, .btn, button, .modal, .ca-scroll { display: none !important; }
    }
</style>

<script>
(function () {
    'use strict';

    const QUEUE = <?= json_encode(array_map(function ($r) {
        return [
            'clearance_id'     => (int) $r['clearance_id'],
            'clearance_no'     => $r['clearance_no'] ?? '',
            'faculty_id'       => (int) $r['faculty_id'],
            'faculty_no'       => $r['faculty_no'] ?? '',
            'faculty_name'     => caFullName($r),
            'initials'         => caInitials($r),
            'department'       => $r['dept_name'] ?: ($r['dept_code'] ?: '—'),
            'term'             => ($r['academic_year'] ?? '—') . ' · ' . ($r['semester'] ?? '—'),
            'intent'           => ucfirst((string) ($r['intent_type'] ?? 'renewal')),
            'gate_cleared'     => (int) $r['_gate_cleared'],
            'gate_total'       => (int) $r['_gate_total'],
            'all_cleared'      => (bool) $r['_all_cleared'],
            'declared'         => (bool) $r['_declared'],
            'has_audit'        => (bool) $r['_has_audit'],
            'has_endorse'      => (bool) $r['_has_endorse'],
            'has_notice'       => (bool) $r['_has_notice'],
            'has_expedite'     => (bool) $r['_has_expedite'],
            'offices'          => array_values($r['_office_progress']),
            'admin_actions'    => $r['_admin_actions'],
        ];
    }, $queue), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
            'cleared':  ['bg-success-subtle text-success border-success-subtle', 'Cleared'],
            'approved': ['bg-success-subtle text-success border-success-subtle', 'Approved'],
            'hold':     ['bg-warning-subtle text-warning border-warning-subtle', 'On Hold'],
            'on hold':  ['bg-warning-subtle text-warning border-warning-subtle', 'On Hold'],
            'denied':   ['bg-danger-subtle text-danger border-danger-subtle', 'Denied'],
            'rejected': ['bg-danger-subtle text-danger border-danger-subtle', 'Rejected'],
            'missing':  ['bg-secondary-subtle text-secondary border-secondary-subtle', 'Missing'],
            'pending':  ['bg-warning-subtle text-warning border-warning-subtle', 'Pending']
        };
        const v = map[s] || ['bg-secondary-subtle text-secondary border-secondary-subtle', status || 'Unknown'];
        return '<span class="d-inline-block px-2 py-1 rounded-2 border fw-semibold text-nowrap ' + v[0] + '" style="font-size:.68rem;">' + escapeHtml(v[1]) + '</span>';
    }

    const detailPanel = document.getElementById('detailPanel');
    const template = document.getElementById('detailTemplate');

    function selectClearance(cid) {
        const data = QUEUE_MAP[String(cid)];
        if (!data) return;

        document.querySelectorAll('.ca-queue-item').forEach(el => {
            el.classList.toggle('ca-active', el.dataset.clearanceId === String(cid));
        });

        detailPanel.innerHTML = '';
        detailPanel.appendChild(template.content.cloneNode(true));

        document.getElementById('dt-avatar').textContent    = data.initials;
        document.getElementById('dt-name').textContent      = data.faculty_name;
        document.getElementById('dt-faculty-no').textContent = data.faculty_no || '—';
        document.getElementById('dt-dept').textContent      = data.department;
        document.getElementById('dt-term').textContent      = data.term;
        document.getElementById('dt-intent').textContent    = data.intent;

        // State badges
        const auditState = document.getElementById('dt-audit-state');
        auditState.textContent = data.has_audit ? 'Audit Passed' : 'Awaiting Audit';
        auditState.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold ' + (data.has_audit ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle') + '" style="font-size:.7rem;"';

        const endState = document.getElementById('dt-endorse-state');
        endState.textContent = data.has_endorse ? 'Endorsed to Dean' : 'Not Endorsed';
        endState.className = 'd-inline-block px-2 py-1 rounded-2 border fw-semibold ' + (data.has_endorse ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle') + '" style="font-size:.7rem;"';

        // Gate alert
        const gateAlert = document.getElementById('dt-gate-alert');
        const gateText  = document.getElementById('dt-gate-text');
        const gateOk    = data.all_cleared && data.declared;
        if (gateOk) {
            gateAlert.className = 'alert alert-success d-flex align-items-center gap-2 mb-3';
            gateText.innerHTML = '<strong>Ready to endorse.</strong> All ' + data.gate_total + ' offices cleared and the faculty declaration is signed.';
        } else {
            gateAlert.className = 'alert alert-warning d-flex align-items-center gap-2 mb-3';
            const missing = [];
            if (!data.all_cleared) missing.push(data.gate_cleared + '/' + data.gate_total + ' offices cleared');
            if (!data.declared)    missing.push('faculty declaration not signed');
            gateText.innerHTML = '<strong>Not yet eligible for endorsement.</strong> ' + missing.join(' · ') + '.';
        }

        // Offices grid
        const officeGrid = document.getElementById('dt-offices');
        officeGrid.innerHTML = data.offices.map(o => {
            const fileLink = o.file_path
                ? '<a href="' + escapeHtml(o.file_path) + '" target="_blank" rel="noopener" class="small"><i class="fas fa-paperclip me-1"></i>' + escapeHtml(o.original_name || 'file') + '</a>'
                : '<span class="small text-body-secondary">No file</span>';
            return `
                <div class="col-12 col-md-6">
                    <div class="p-2 rounded-3 border bg-body-tertiary">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div class="fw-bold small text-body-emphasis">${escapeHtml(o.office_name)}</div>
                            ${statusPill(o.status)}
                        </div>
                        <div class="mt-1">${fileLink}</div>
                    </div>
                </div>
            `;
        }).join('');

        // Hidden inputs for action forms
        document.querySelector('#form-audit input[name="clearance_id"]').value         = cid;
        document.getElementById('notice-clearance-id').value                            = cid;
        document.getElementById('expedite-clearance-id').value                          = cid;
        document.getElementById('endorse-clearance-id').value                           = cid;

        // Enable/disable buttons
        const btnAudit   = document.getElementById('btn-audit');
        const btnEndorse = document.getElementById('btn-endorse');
        if (data.has_audit)   { btnAudit.disabled = true; btnAudit.classList.add('disabled'); }
        if (data.has_endorse) { btnEndorse.disabled = true; btnEndorse.classList.add('disabled'); }
        if (!gateOk)          { btnEndorse.disabled = true; btnEndorse.classList.add('disabled'); }

        // Admin history
        const histEl = document.getElementById('dt-history');
        if (!data.admin_actions.length) {
            histEl.innerHTML = '<div class="text-center py-3 text-body-secondary small">No admin actions yet.</div>';
        } else {
            histEl.innerHTML = data.admin_actions.map(h => {
                const badgeMap = {
                    'Audit Passed':      'bg-success-subtle text-success border-success-subtle',
                    'Endorsed to Dean':  'bg-primary-subtle text-primary border-primary-subtle',
                    'Compliance Notice': 'bg-danger-subtle text-danger border-danger-subtle',
                    'Expedite Notice':   'bg-warning-subtle text-warning border-warning-subtle'
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

        // Scroll detail into view on mobile
        if (window.innerWidth < 992) {
            detailPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Queue click
    document.getElementById('queueList').addEventListener('click', function (e) {
        const card = e.target.closest('.ca-queue-item');
        if (!card) return;
        selectClearance(card.dataset.clearanceId);
    });

    // Queue search
    (function () {
        const input = document.getElementById('queueSearch');
        if (!input) return;
        input.addEventListener('input', function () {
            const q = input.value.toLowerCase().trim();
            document.querySelectorAll('.ca-queue-item').forEach(el => {
                const hay = (el.dataset.name || '') + ' ' + (el.dataset.no || '');
                el.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    })();

    // Privacy toggle
    (function () {
        const pt = document.getElementById('privacyModeToggle');
        if (!pt) return;
        const KEY = 'smsAdminClearancePrivacy';
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

    // Print
    document.getElementById('printBtn').addEventListener('click', () => window.print());

    // Auto-select first queue item on load (desktop only)
    if (QUEUE.length > 0 && window.innerWidth >= 992) {
        selectClearance(QUEUE[0].clearance_id);
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>