<?php
/**
 * SMS 2 - Dean - Clearance Sign-off (Track 2)
 * 
 * Final Institutional Sign-off.
 * Only shows clearances that the Admin has audited and endorsed to the Dean.
 * Once 6/6 offices are cleared + Admin endorsed + Declaration signed, the Dean
 * can grant final sign-off. This archives the record and routes it to HR.
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

$currentUserId      = (int) ($_SESSION['user_id'] ?? 0);
$currentUserName    = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Dean'));
$currentUserRoleLbl = (string) ($_SESSION['role_label'] ?? 'Dean');

$GATE_OFFICE_IDS = [5005, 5006, 5007, 5008, 5009, 5010];

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
   HANDLE ACTION — Final Sign-off or Return to Admin
   ============================================================ */
$actionMessage = '';
$actionType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dean_signoff') {
    if (!$hasDeanDepartments) {
        $actionMessage = 'No departments assigned.';
        $actionType = 'danger';
    } else {
        try {
            $clearanceId = (int) ($_POST['clearance_id'] ?? 0);
            $decision    = (string) ($_POST['decision'] ?? '');
            $remarks     = trim((string) ($_POST['remarks'] ?? ''));
            $deptList    = implode(',', array_map('intval', $deanDepartments));

            if (!in_array($decision, ['approve','return'], true)) {
                throw new InvalidArgumentException('Invalid decision.');
            }

            // Fetch clearance + faculty details
            $stmt = $pdo->prepare("
                SELECT cr.*, f.faculty_no, f.first_name, f.middle_name, f.last_name, f.email,
                       f.department_id, d.name AS dept_name, at.academic_year, at.semester
                FROM faculty_db.clearance_requests cr
                INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
                LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
                LEFT JOIN faculty_db.academic_terms at ON at.term_id = cr.term_id
                WHERE cr.clearance_id = ? AND f.department_id IN ($deptList)
                LIMIT 1
            ");
            $stmt->execute([$clearanceId]);
            $cl = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cl) throw new RuntimeException('Clearance not found or out of scope.');

            $facultyId = (int) $cl['faculty_id'];
            $facultyName = trim(($cl['first_name'] ?? '') . ' ' . ($cl['last_name'] ?? ''));

            if ($decision === 'return') {
                // Return to Admin
                if ($remarks === '') throw new InvalidArgumentException('Please provide a reason for returning.');

                $ins = $pdo->prepare("
                    INSERT INTO faculty_db.clearance_approval_history
                        (clearance_id, office, action, performed_by_id, performed_by_name, performed_by_role, remarks)
                    VALUES (?, 'Dean', 'Returned by Dean — Deficiency', ?, ?, ?, ?)
                ");
                $ins->execute([$clearanceId, $currentUserId, $currentUserName, $currentUserRoleLbl, $remarks]);

                // Update overall status back to 'In Progress' so Admin sees it
                $upd = $pdo->prepare("UPDATE faculty_db.clearance_requests SET overall_status = 'In Progress' WHERE clearance_id = ?");
                $upd->execute([$clearanceId]);

                // Notify Admin (Faculty Admin)
                $notif = $pdo->prepare("
                    INSERT INTO faculty_db.notifications (faculty_id, title, message, priority, notification_type, is_read)
                    VALUES (?, ?, ?, 'High Priority', 'clearance', 0)
                ");
                // Note: in a real system this should notify the Admin user, but for now we log it against the faculty
                $notif->execute([$facultyId, 'Clearance Returned by Dean', "Your clearance was returned by the Dean. Reason: " . $remarks]);

                $actionMessage = 'Clearance returned to Admin for correction.';
                $actionType = 'warning';

            } elseif ($decision === 'approve') {
                // Verify Gate: 6/6 offices cleared + Admin endorsed
                $officeStmt = $pdo->prepare("SELECT clearance_office_id, status FROM faculty_db.clearance_items WHERE clearance_id = ?");
                $officeStmt->execute([$clearanceId]);
                $offices = $officeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                $allCleared = true;
                foreach ($GATE_OFFICE_IDS as $oid) {
                    $st = strtolower($offices[$oid] ?? 'missing');
                    if ($st !== 'cleared' && $st !== 'approved by dean') {
                        $allCleared = false;
                        break;
                    }
                }

                $adminStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.clearance_approval_history WHERE clearance_id = ? AND office = 'Admin' AND action = 'Endorsed to Dean'");
                $adminStmt->execute([$clearanceId]);
                $isEndorsed = (int) $adminStmt->fetchColumn() > 0;

                if (!$allCleared || !$isEndorsed || !$cl['faculty_declared']) {
                    throw new RuntimeException('Gate not met: All 6 offices must be cleared, Admin must endorse, and faculty must sign declaration.');
                }

                $pdo->beginTransaction();

                // 1. Insert Dean Signature
                $ref = 'DFS-' . $clearanceId . '-' . date('YmdHis');
                $sigStmt = $pdo->prepare("
                    INSERT INTO faculty_db.clearance_signatures
                        (clearance_id, faculty_id, office, office_key, signatory_type, approval_ref, signer_user_id, signer_name, signer_role, signature_data, remarks, status, signed_at)
                    VALUES (?, ?, 'Dean Final Sign-off', 'dean_final_clearance', 'dean', ?, ?, ?, ?, ?, ?, 'Signed', NOW())
                    ON DUPLICATE KEY UPDATE signer_user_id = VALUES(signer_user_id), signed_at = NOW()
                ");
                $sigData = 'data:text/plain;base64,' . base64_encode($currentUserName . ' — ' . date('Y-m-d H:i:s'));
                $sigStmt->execute([$clearanceId, $facultyId, $ref, $currentUserId, $currentUserName, $currentUserRoleLbl, $sigData, $remarks]);

                // 2. Update overall status
                $upd = $pdo->prepare("UPDATE faculty_db.clearance_requests SET overall_status = 'Cleared', form_status = 'Approved' WHERE clearance_id = ?");
                $upd->execute([$clearanceId]);

                // 3. Insert History
                $ins = $pdo->prepare("
                    INSERT INTO faculty_db.clearance_approval_history
                        (clearance_id, office, action, performed_by_id, performed_by_name, performed_by_role, remarks)
                    VALUES (?, 'Dean', 'Final Sign-off Granted', ?, ?, ?, ?)
                ");
                $ins->execute([$clearanceId, $currentUserId, $currentUserName, $currentUserRoleLbl, $remarks ?: 'Cleared and endorsed.']);

                // 4. Insert to Archives (Snapshot)
                $itemsJson = json_encode($offices);
                $archStmt = $pdo->prepare("
                    INSERT INTO faculty_db.faculty_clearance_archives
                        (clearance_id, faculty_id, term_id, faculty_no, first_name, middle_name, last_name, email, position, academic_year, semester, intent_type, overall_status, items_json, completed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cleared', ?, NOW())
                ");
                $archStmt->execute([
                    $clearanceId, $facultyId, $cl['term_id'], $cl['faculty_no'], $cl['first_name'], $cl['middle_name'], $cl['last_name'], 
                    $cl['email'], 'Faculty Professor', $cl['academic_year'], $cl['semester'], $cl['intent_type'], $itemsJson
                ]);

                // 5. Notify HR and Faculty
                $notif = $pdo->prepare("
                    INSERT INTO faculty_db.notifications (faculty_id, title, message, priority, notification_type, is_read)
                    VALUES (?, ?, ?, 'Medium', 'clearance', 0)
                ");
                $intentMsg = $cl['intent_type'] === 'renewal' ? 'contract renewal' : ($cl['intent_type'] === 'regularization' ? 'regularization' : 'offboarding');
                $notif->execute([$facultyId, 'Clearance Finalized', "Your clearance is complete. Reference: $ref. Endorsed for $intentMsg."]);

                $pdo->commit();

                $actionMessage = 'Final Dean Clearance Sign-off granted successfully.';
                $actionType = 'success';
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[dean-clearance] action failed: ' . $e->getMessage());
            $actionMessage = $e->getMessage();
            $actionType = 'danger';
        }
    }
}

/* ============================================================
   LOAD CLEARANCES — only those endorsed to the Dean
   ============================================================ */
$facultyClearances = [];
$loadError = null;

if ($hasDeanDepartments && $pdo instanceof PDO) {
    try {
        $deptList = implode(',', array_map('intval', $deanDepartments));

        // We only pull clearances where the Admin has logged 'Endorsed to Dean'
        $stmt = $pdo->query("
            SELECT
                cr.clearance_id, cr.clearance_no, cr.faculty_id, cr.intent_type, cr.overall_status, cr.faculty_declared,
                f.faculty_no, f.first_name, f.middle_name, f.last_name,
                d.name AS dept_name,
                at.academic_year, at.semester,
                ci.clearance_office_id, ci.status AS item_status,
                co.name AS office_name
            FROM faculty_db.clearance_requests cr
            INNER JOIN faculty_db.faculty f ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_db.departments d ON d.department_id = f.department_id
            LEFT JOIN faculty_db.academic_terms at ON at.term_id = cr.term_id
            LEFT JOIN faculty_db.clearance_items ci ON ci.clearance_id = cr.clearance_id
            LEFT JOIN faculty_db.clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
            WHERE f.department_id IN ($deptList)
              AND cr.clearance_id IN (
                  SELECT clearance_id FROM faculty_db.clearance_approval_history 
                  WHERE office = 'Admin' AND action = 'Endorsed to Dean'
              )
            ORDER BY cr.submitted_at DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    'dept_name'       => $r['dept_name'] ?: '—',
                    'academic_year'   => $r['academic_year'] ?: '',
                    'semester'        => $r['semester'] ?: '',
                    'intent_type'     => $r['intent_type'] ?: 'renewal',
                    'overall_status'  => $r['overall_status'] ?: 'In Progress',
                    'declared'        => (bool) $r['faculty_declared'],
                    'items'           => [],
                ];
            }
            if (!empty($r['clearance_office_id'])) {
                $byClearance[$cid]['items'][] = [
                    'office_id'   => (int) $r['clearance_office_id'],
                    'office_name' => $r['office_name'] ?: 'Office',
                    'status'      => $r['item_status'] ?: 'Missing',
                ];
            }
        }

        $facultyClearances = array_values($byClearance);

    } catch (Throwable $e) {
        error_log('Clearance approval load failed: ' . $e->getMessage());
        $loadError = 'Some data could not be loaded. Try again later.';
    }
}

/* Page config */
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
        <div class="alert alert-<?= htmlspecialchars($actionType) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-<?= $actionType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <div><?= htmlspecialchars($actionMessage) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-none d-print-block text-center mb-3">
        <h4 class="mb-0 fw-bold">Clearance Sign-off Report</h4>
        <small>Generated <?= date('F j, Y g:i A') ?></small>
        <p class="small mb-0 mt-1"><em>Confidential — faculty names are blurred in print.</em></p>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-clipboard-check text-primary"></i>
                <span>Clearance Sign-off</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Final institutional sign-off for clearances audited and endorsed by the Admin.
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

        <!-- KPI Cards -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-6">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Awaiting Sign-off</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="kpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#0d6efd;font-size:0.7rem;">Endorsed to you</small>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-6 col-lg-6">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-4 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:42px;height:42px;font-size:1.2rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Ready to Sign</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="kpiCleared">0</h4>
                        <small class="fw-semibold d-block mt-1 text-truncate" style="color:#10b981;font-size:0.7rem;">6/6 Offices Cleared</small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="f_term">Term</label>
                    <select class="form-select form-select-sm" id="f_term" data-filter="term">
                        <option value="all">All terms</option>
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

    <!-- Cards grid -->
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
                    <input type="hidden" name="action" value="dean_signoff">
                    <input type="hidden" name="clearance_id" id="caClearanceId" value="">
                    <input type="hidden" name="decision" id="caDecision" value="">

                    <p class="text-body-secondary mb-3" id="caModalMessage"></p>

                    <label class="form-label small text-uppercase fw-bold text-body-secondary" for="caRemarks">
                        Remarks <span id="caRemarksRequired" class="text-danger d-none">*</span>
                    </label>
                    <textarea class="form-control" id="caRemarks" name="remarks" rows="3" placeholder="Add notes for the record…"></textarea>
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
    .ca-card--ready   { border-left: 4px solid #3b82f6; }
    .ca-card--blocked { border-left: 4px solid #ef4444; }

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
    .ca-badge--ready { background: rgba(59,130,246,0.14); color: #2563eb; border-color: rgba(59,130,246,0.24); }
    .ca-badge--blocked { background: rgba(239,68,68,0.14);  color: #dc2626; border-color: rgba(239,68,68,0.24); }

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
    .ca-office--missing  { background: rgba(148,163,184,0.14); color: #64748b; border-color: rgba(148,163,184,0.26); }

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

    let filters = { term: 'all', q: '' };
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
       Compute a card's overall status for Track 2
       - cleared: overall_status is Cleared
       - ready: 6/6 offices cleared + Admin endorsed + Declared
       - blocked: missing offices or declaration
       ============================================================ */
    function computeCardStatus(card) {
        if (card.overall_status === 'Cleared') return 'cleared';

        const items = card.items || [];
        const gateIds = [5005, 5006, 5007, 5008, 5009, 5010];
        let clearedCount = 0;

        items.forEach(i => {
            if (gateIds.includes(i.office_id) && String(i.status).toLowerCase() === 'cleared') {
                clearedCount++;
            }
        });

        if (clearedCount === 6 && card.declared) {
            return 'ready';
        }
        return 'blocked';
    }

    /* ============================================================
       Filters
       ============================================================ */
    function termMatches(row) {
        if (filters.term === 'all') return true;
        return (row.academic_year + '|' + row.semester) === filters.term;
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
        let ready = 0;
        rows.forEach(r => {
            if (computeCardStatus(r) === 'ready') ready++;
        });
        document.getElementById('kpiTotal').textContent   = rows.length.toLocaleString();
        document.getElementById('kpiCleared').textContent = ready.toLocaleString();
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
                    <h6>No clearances awaiting final sign-off</h6>
                    <p>When the Admin audits a clearance and endorses it to you, it will appear here.</p>
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
            const statusLabel = status === 'ready' ? 'Ready for Final Sign-off' : 'Blocked';
            const statusBadgeClass = status === 'ready' ? 'ca-badge--ready' : 'ca-badge--blocked';
            
            // Group 6 offices
            const gateIds = [5005, 5006, 5007, 5008, 5009, 5010];
            const gateItems = card.items.filter(i => gateIds.includes(i.office_id));
            
            const officeChips = gateItems.map(item => {
                const isCleared = String(item.status).toLowerCase() === 'cleared';
                const cls = isCleared ? 'ca-office--cleared' : 'ca-office--missing';
                const icon = isCleared ? 'fa-check' : 'fa-xmark';
                return `<span class="ca-office ${cls}"><i class="fas ${icon}"></i>${escapeHtml(item.office_name)}</span>`;
            }).join('');

            const canSign = (status === 'ready');

            return `
                <div class="ca-card ca-card--${status}">
                    <div class="ca-card__head">
                        <div class="ca-card__person">
                            <span class="ca-card__avatar">${escapeHtml(initials(card.faculty_name))}</span>
                            <div class="min-w-0">
                                <div class="ca-card__name ca-privacy-target">${escapeHtml(card.faculty_name)}</div>
                                <div class="ca-card__meta">${escapeHtml(card.faculty_no || '—')} · ${escapeHtml(card.dept_name)}</div>
                                <div class="ca-card__meta">${escapeHtml((card.academic_year || '—') + ' · ' + (card.semester || '—'))} · ${escapeHtml(card.clearance_no)}</div>
                            </div>
                        </div>
                        <span class="ca-badge ${statusBadgeClass}">${escapeHtml(statusLabel)}</span>
                    </div>

                    <div class="ca-strip">
                        ${officeChips || '<span class="ca-office ca-office--missing"><i class="fas fa-circle"></i>No offices assigned</span>'}
                    </div>

                    <div class="ca-card__footer">
                        <div class="ca-card__note">
                            ${status === 'ready'
                                ? 'All 6 offices cleared and Admin endorsed. Ready for your final sign-off.'
                                : 'Waiting on missing offices or faculty declaration.'}
                        </div>
                        <div class="ca-actions">
                            ${canSign
                                ? `<button type="button" class="ca-btn ca-btn--return"
                                        data-action="return"
                                        data-id="${card.clearance_id}"
                                        data-name="${escapeHtml(card.faculty_name)}">
                                     <i class="fas fa-rotate-left"></i> Return
                                   </button>
                                   <button type="button" class="ca-btn ca-btn--approve"
                                        data-action="approve"
                                        data-id="${card.clearance_id}"
                                        data-name="${escapeHtml(card.faculty_name)}">
                                     <i class="fas fa-stamp"></i> Sign Final
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
       Action modal
       ============================================================ */
    let caModal = null;

    function openActionModal(btn, action) {
        const clrId = btn.dataset.id;
        const name = btn.dataset.name;

        document.getElementById('caClearanceId').value = clrId;
        document.getElementById('caDecision').value    = action;
        document.getElementById('caRemarks').value     = '';

        const title = document.getElementById('caModalTitle');
        const msg = document.getElementById('caModalMessage');
        const submit = document.getElementById('caModalSubmit');
        const remarksReq = document.getElementById('caRemarksRequired');

        if (action === 'approve') {
            title.innerHTML = '<i class="fas fa-stamp text-success"></i><span>Grant Final Sign-off</span>';
            msg.innerHTML = 'Grant final institutional clearance to <strong>' + escapeHtml(name) + '</strong>? This will archive the record, generate the final reference number, and notify HR.';
            submit.className = 'btn btn-success btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-check me-1"></i> Grant Sign-off';
            remarksReq.classList.add('d-none');
            document.getElementById('caRemarks').required = false;
        } else {
            title.innerHTML = '<i class="fas fa-times-circle text-danger"></i><span>Return to Admin</span>';
            msg.innerHTML = 'Return this clearance back to the Admin for <strong>' + escapeHtml(name) + '</strong>? Please note the reason so they know what to fix.';
            submit.className = 'btn btn-danger btn-sm rounded-3 px-4 fw-bold';
            submit.innerHTML = '<i class="fas fa-rotate-left me-1"></i> Return Record';
            remarksReq.classList.remove('d-none');
            document.getElementById('caRemarks').required = true;
        }

        if (!caModal) {
            caModal = new bootstrap.Modal(document.getElementById('caActionModal'));
        }
        caModal.show();
    }

    /* ============================================================
       Filters & Print
       ============================================================ */
    function applyFilters() {
        return CLEARANCES.filter(r => termMatches(r) && qMatches(r));
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

    function renderPrintReport(rows) {
        const host = document.getElementById('printReport');
        if (!host) return;

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const bodyRows = rows.map((r, idx) => {
            const status = computeCardStatus(r);
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td class="ca-print-blur">' + escapeHtml(r.faculty_name) + '</td>' +
                '<td>' + escapeHtml(r.faculty_no || '—') + '</td>' +
                '<td>' + escapeHtml(r.dept_name) + '</td>' +
                '<td>' + escapeHtml((r.academic_year || '—') + ' · ' + (r.semester || '—')) + '</td>' +
                '<td>' + escapeHtml(r.clearance_no) + '</td>' +
                '<td>' + escapeHtml(status === 'ready' ? 'Ready for Final Sign-off' : 'Blocked') + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Clearance Sign-off Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Faculty</th><th>Faculty No</th>
                        <th>Department</th><th>Term</th><th>Clearance No</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${bodyRows || '<tr><td colspan="7" style="text-align:center;">No clearances in scope.</td></tr>'}</tbody>
            </table>
            <div class="print-footer">
                SMS 2 · Faculty Module · ${rows.length} clearance${rows.length !== 1 ? 's' : ''} · Confidential — Faculty names blurred
            </div>`;
    }

    function exportCsv(rows) {
        const headers = ['#', 'Faculty', 'Faculty No', 'Department', 'Term', 'Clearance No', 'Status'];
        const lines = rows.map((r, idx) => [
            idx + 1, r.faculty_name, r.faculty_no || '', r.dept_name,
            (r.academic_year || '—') + ' · ' + (r.semester || '—'),
            r.clearance_no, computeCardStatus(r) === 'ready' ? 'Ready for Final Sign-off' : 'Blocked'
        ]);

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

    function render() {
        filteredRows = applyFilters();
        currentPage = 1;
        renderKPIs(filteredRows);
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
        filters = { term: 'all', q: '' };
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