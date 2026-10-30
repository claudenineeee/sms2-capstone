<?php
/**
 * SMS 2 - Admin - Term Rollover Wizard
 *
 * One button per module. Each action is transaction-safe and idempotent.
 * Designed for demonstration: you can click each button individually and
 * watch the data move from live tables → archive/history tables.
 *
 * NOTE: Class schedules will move to an external module via REST API in a
 * later phase. The "Rollover Schedules" card runs locally today; a comment
 * inside marks the swap point for the API integration.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../includes/term-rollover.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

/* RBAC — Admin only */
$currentRoleKey = strtolower((string) (function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : ($_SESSION['role_key'] ?? '')));
$ADMIN_ROLE_KEYS = ['admin', 'faculty_admin', 'superadmin'];
if (!in_array($currentRoleKey, $ADMIN_ROLE_KEYS, true)) {
    http_response_code(403);
    $pageTitle = 'Access Denied';
    require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
    require_once __DIR__ . '/../../../../includes/layout-start.php';
    echo '<div class="container-fluid px-3 px-md-4 px-lg-5 py-4"><div class="card border shadow-sm"><div class="card-body text-center py-5">';
    echo '<div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:84px;height:84px;background:rgba(220,53,69,.10);color:#dc3545;font-size:2.2rem;"><i class="fas fa-lock"></i></div>';
    echo '<h4 class="fw-bold mb-2">Access Denied</h4><p class="text-body-secondary mb-0">This page is restricted to Administrators.</p>';
    echo '</div></div></div>';
    require_once __DIR__ . '/../../../../includes/layout-end.php';
    exit;
}

/* DB */
$pdo = null;
if (function_exists('facultyDb')) { $pdo = facultyDb(); }
if (!$pdo instanceof PDO && function_exists('db')) { try { $pdo = db(); } catch (Throwable $e) { $pdo = null; } }
if (!$pdo instanceof PDO && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) { $pdo = $GLOBALS['pdo']; }

if (!function_exists('eh')) {
    function eh($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

$flash     = null;
$flashType = 'info';

/* ============================================================
   Handle POST — one action per button
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    $action = (string) ($_POST['action'] ?? '');

    $currentTerm = smsGetCurrentTerm($pdo);

    try {
        switch ($action) {

            case 'create_term': {
                $ay  = trim((string) ($_POST['academic_year'] ?? ''));
                $sem = trim((string) ($_POST['semester'] ?? ''));
                $res = smsCreateAcademicTerm($pdo, $ay, $sem);
                $flash = $res['message'] ?? 'Created term.';
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            case 'rollover_schedules': {
                if (!$currentTerm) { $flash = 'No active term found.'; $flashType = 'warning'; break; }
                $res = smsRolloverClassSchedules($pdo, (int) $currentTerm['term_id']);
                $flash = $res['message'];
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            case 'reset_leave_balances': {
                $targetAy = trim((string) ($_POST['academic_year'] ?? ''));
                if ($targetAy === '' && $currentTerm) $targetAy = (string) $currentTerm['academic_year'];
                if ($targetAy === '') { $flash = 'No academic year specified.'; $flashType = 'warning'; break; }
                $res = smsResetLeaveBalances($pdo, $targetAy);
                $flash = $res['message'];
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            case 'archive_clearances': {
                if (!$currentTerm) { $flash = 'No active term found.'; $flashType = 'warning'; break; }
                $res = smsArchiveClearances($pdo, (int) $currentTerm['term_id']);
                $flash = $res['message'];
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            case 'purge_notifications': {
                $days = max(1, (int) ($_POST['days'] ?? 90));
                $res  = smsPurgeOldNotifications($pdo, $days);
                $flash = $res['message'];
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            case 'activate_term': {
                $newTermId = (int) ($_POST['new_term_id'] ?? 0);
                if ($newTermId <= 0) { $flash = 'Please select a term to activate.'; $flashType = 'warning'; break; }
                $res = smsActivateNewTerm($pdo, $newTermId);
                $flash = $res['message'];
                $flashType = !empty($res['ok']) ? 'success' : 'danger';
                break;
            }

            default:
                $flash = 'Unknown action.';
                $flashType = 'danger';
        }
    } catch (Throwable $e) {
        $flash = 'Unexpected error: ' . $e->getMessage();
        $flashType = 'danger';
    }

    $_SESSION['rollover_flash']      = $flash;
    $_SESSION['rollover_flash_type'] = $flashType;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!empty($_SESSION['rollover_flash'])) {
    $flash     = (string) $_SESSION['rollover_flash'];
    $flashType = (string) ($_SESSION['rollover_flash_type'] ?? 'info');
    unset($_SESSION['rollover_flash'], $_SESSION['rollover_flash_type']);
}

/* ============================================================
   Load current state
   ============================================================ */
$currentTerm = $pdo instanceof PDO ? smsGetCurrentTerm($pdo) : null;
$allTerms    = $pdo instanceof PDO ? smsGetAllTerms($pdo) : [];

$liveCounts = [
    'schedules'      => 0,
    'in_progress_cl' => 0,
    'leave_rows'     => 0,
    'notifications'  => 0,
    'faculty'        => 0,
];

if ($pdo instanceof PDO && $currentTerm) {
    $tid = (int) $currentTerm['term_id'];
    $ay  = (string) $currentTerm['academic_year'];

    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.class_schedules WHERE term_id = ?");
        $s->execute([$tid]);
        $liveCounts['schedules'] = (int) $s->fetchColumn();

        $s = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.clearance_requests WHERE term_id = ? AND overall_status NOT IN ('Cleared','Completed','Archived')");
        $s->execute([$tid]);
        $liveCounts['in_progress_cl'] = (int) $s->fetchColumn();

        $s = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.leave_balances WHERE academic_year = ?");
        $s->execute([$ay]);
        $liveCounts['leave_rows'] = (int) $s->fetchColumn();

        $s = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.notifications");
        $s->execute();
        $liveCounts['notifications'] = (int) $s->fetchColumn();

        $s = $pdo->query("SELECT COUNT(*) FROM faculty_db.faculty WHERE profile_status = 'Active'");
        $liveCounts['faculty'] = (int) $s->fetchColumn();
    } catch (Throwable $e) {
        // Non-fatal
    }
}

/* Page config */
$pageTitle    = 'Term Rollover';
$activeModule = 'faculty';
$activePage   = 'term-rollover';
$breadcrumbs  = [
    ['label' => 'Admin', 'url' => null],
    ['label' => 'Term Rollover', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="trPage">

    <?php if ($flash): ?>
        <div class="alert alert-<?= eh($flashType) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-circle-info"></i>
            <div class="small"><?= eh($flash) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ================= Page Header ================= -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 h3-md text-body fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-arrows-rotate text-primary"></i>
                <span>Term Rollover</span>
            </h1>
            <p class="text-body-secondary small mb-0">
                Run each module's rollover independently. Data moves to history — nothing is deleted.
            </p>
        </div>
    </div>

    <!-- ================= Current Term Banner ================= -->
    <?php if ($currentTerm): ?>
        <div class="card border shadow-sm mb-4">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap py-3">
                <i class="fas fa-calendar-check fs-4 text-body-secondary"></i>
                <div class="flex-grow-1">
                    <div class="text-uppercase small fw-bold text-body-secondary" style="letter-spacing:.05em;">Active Term</div>
                    <div class="fw-bold"><?= eh($currentTerm['academic_year']) ?> · <?= eh($currentTerm['semester']) ?></div>
                </div>
                <span class="badge text-bg-light border">Term ID: <?= (int) $currentTerm['term_id'] ?></span>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 small" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div>No active term. Create one below, then activate it.</div>
        </div>
    <?php endif; ?>

    <!-- ================= Create New Term ================= -->
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-transparent border-bottom d-flex align-items-center gap-2 py-3">
            <i class="fas fa-plus-circle text-primary"></i>
            <h5 class="card-title mb-0 fw-bold">1 · Create New Academic Term</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="create_term">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary">Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" placeholder="2027-2028" pattern="\d{4}-\d{4}" required>
                    <div class="form-text small">Format: YYYY-YYYY</div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary">Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">— Choose —</option>
                        <option>1st Semester</option>
                        <option>2nd Semester</option>
                        <option>Summer</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Create Term
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= Rollover Actions ================= -->
    <div class="row g-3 mb-4">

        <!-- 1. Class Schedules -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
                    <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2 small">
                        <i class="fas fa-calendar-days text-primary"></i>
                        <span>2 · Class Schedules</span>
                    </h5>
                    <span class="badge text-bg-light border"><?= (int) $liveCounts['schedules'] ?> live</span>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small text-body-secondary flex-grow-1 mb-3">
                        Snapshots all <code>class_schedules</code> from the active term into
                        <code>teaching_load_history</code> (one row per faculty), preserving
                        subject count, total units, and total students.
                        Old schedules stay in the database — they're just marked as archived by term.
                    </p>
                    <div class="small text-body-secondary mb-3">
                        <i class="fas fa-plug me-1"></i>
                        <em>Pending integration: will call an external REST API once the schedule module is migrated.</em>
                    </div>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="action" value="rollover_schedules">
                        <button type="submit" class="btn btn-primary w-100" <?= $currentTerm ? '' : 'disabled' ?>>
                            <i class="fas fa-arrows-rotate me-2"></i>Rollover Schedules
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Leave Balances -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
                    <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2 small">
                        <i class="fas fa-plane-departure text-primary"></i>
                        <span>3 · Leave Balances</span>
                    </h5>
                    <span class="badge text-bg-light border"><?= (int) $liveCounts['leave_rows'] ?> rows</span>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small text-body-secondary mb-3">
                        Creates new <code>leave_balances</code> rows for the new academic year.
                        Carries forward the entitlement totals from the current year
                        and zeroes out all <code>*_used</code> columns. The current year's rows
                        stay in the table for history.
                    </p>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="action" value="reset_leave_balances">
                        <label class="form-label small text-uppercase fw-bold text-body-secondary">Target Academic Year</label>
                        <input type="text" name="academic_year" class="form-control mb-2" pattern="\d{4}-\d{4}"
                               value="<?= $currentTerm ? eh($currentTerm['academic_year']) : '' ?>" required>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-rotate-right me-2"></i>Reset Leave Balances
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Clearances -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
                    <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2 small">
                        <i class="fas fa-clipboard-check text-primary"></i>
                        <span>4 · Clearances</span>
                    </h5>
                    <span class="badge text-bg-light border"><?= (int) $liveCounts['in_progress_cl'] ?> in progress</span>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small text-body-secondary flex-grow-1 mb-3">
                        Marks any in-progress clearances from the old term as
                        <code>Archived</code>. Completed clearances are already stored in
                        <code>faculty_clearance_archives</code> — this only handles the leftovers
                        so the new term starts clean.
                    </p>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="action" value="archive_clearances">
                        <button type="submit" class="btn btn-primary w-100" <?= $currentTerm ? '' : 'disabled' ?>>
                            <i class="fas fa-box-archive me-2"></i>Archive Clearances
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 4. Notifications -->
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
                    <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2 small">
                        <i class="fas fa-bell text-primary"></i>
                        <span>5 · Notifications</span>
                    </h5>
                    <span class="badge text-bg-light border"><?= (int) $liveCounts['notifications'] ?> total</span>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small text-body-secondary mb-3">
                        Removes read notifications older than the retention window.
                        Unread notifications are preserved so nothing important is lost.
                    </p>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="action" value="purge_notifications">
                        <label class="form-label small text-uppercase fw-bold text-body-secondary">Retention (days)</label>
                        <input type="number" name="days" class="form-control mb-2" value="90" min="7" max="730">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-broom me-2"></i>Purge Notifications
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 5. Activate New Term -->
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
                    <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2 small">
                        <i class="fas fa-power-off text-primary"></i>
                        <span>6 · Activate New Term</span>
                    </h5>
                    <span class="badge text-bg-light border">Final Step</span>
                </div>
                <div class="card-body">
                    <p class="small text-body-secondary mb-3">
                        Flips the <code>is_current</code> flag from the old term to a new one.
                        After this, all new records (schedules, clearances, etc.) automatically
                        belong to the new term. The old term's data remains untouched.
                    </p>
                    <?php if (count($allTerms) <= 1): ?>
                        <div class="alert alert-info mb-0 small">
                            <i class="fas fa-info-circle me-1"></i>
                            You need at least two terms to activate a new one. Create a term above first.
                        </div>
                    <?php else: ?>
                        <form method="POST" class="row g-3 align-items-end">
                            <input type="hidden" name="action" value="activate_term">
                            <div class="col-12 col-md-8">
                                <label class="form-label small text-uppercase fw-bold text-body-secondary">Select Term to Activate</label>
                                <select name="new_term_id" class="form-select" required>
                                    <option value="">— Choose a term —</option>
                                    <?php foreach ($allTerms as $t): ?>
                                        <?php if (!empty($t['is_current'])) continue; ?>
                                        <option value="<?= (int) $t['term_id'] ?>">
                                            <?= eh($t['academic_year']) ?> · <?= eh($t['semester']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-primary w-100"
                                        onclick="return confirm('Activate this term as the new current term? The old term will remain in the database for history.');">
                                    <i class="fas fa-check me-2"></i>Activate Term
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Term History ================= -->
    <div class="card border shadow-sm">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center gap-2 py-3 flex-wrap">
            <div>
                <h5 class="card-title mb-1 fw-bold small">Academic Term History</h5>
                <p class="text-body-secondary small mb-0">All terms in the system — old terms remain queryable</p>
            </div>
            <span class="badge text-bg-light border"><?= count($allTerms) ?></span>
        </div>
        <div class="tr-scroll" style="max-height:400px;overflow-y:auto;">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="min-width:520px;">
                    <thead class="sticky-top">
                        <tr>
                            <th class="text-uppercase small fw-bold text-body-secondary">Term ID</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Academic Year</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Semester</th>
                            <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allTerms)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-body-secondary small">No terms yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($allTerms as $t): ?>
                                <tr>
                                    <td class="small text-body-secondary"><?= (int) $t['term_id'] ?></td>
                                    <td class="fw-semibold small"><?= eh($t['academic_year']) ?></td>
                                    <td class="small"><?= eh($t['semester']) ?></td>
                                    <td>
                                        <?php if (!empty($t['is_current'])): ?>
                                            <span class="badge text-bg-light border text-success-emphasis">Current</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-light border text-body-secondary">Archived</span>
                                        <?php endif; ?>
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

<style>
    .tr-scroll { scrollbar-width: thin; scrollbar-color: rgba(148,163,184,.35) transparent; }
    .tr-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .tr-scroll::-webkit-scrollbar-track { background: transparent; }
    .tr-scroll::-webkit-scrollbar-thumb { background-color: rgba(148,163,184,.35); border-radius: 10px; }
    .tr-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(148,163,184,.6); }

    @media (max-width: 400px) {
        #trPage { padding-left: .5rem !important; padding-right: .5rem !important; }
        #trPage h1.h4 { font-size: 1.15rem; }
        #trPage .card-body { padding: .75rem !important; }
        #trPage .card-header { padding: .6rem .75rem !important; }
        #trPage .card-title { font-size: .82rem !important; }
        #trPage .btn { font-size: .8rem; padding: .4rem .6rem; }
        #trPage .badge { font-size: .62rem; }
        #trPage .form-label { font-size: .68rem; }
        #trPage .form-control,
        #trPage .form-select { font-size: .82rem; }
        #trPage .table td,
        #trPage .table th { font-size: .72rem; padding: .4rem .5rem; }
    }
</style>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>