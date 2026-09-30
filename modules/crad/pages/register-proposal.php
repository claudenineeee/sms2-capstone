<?php
/**
 * SMS 2 - Register Proposal
 * Module: CRAD
 * Lists approved research proposals and registers them with an official number.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

requireAuth();

$pageTitle    = 'Register Proposal';
$activeModule = 'crad';
$activePage   = 'register-proposal';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Register Proposal', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

function rpEnsureRegistrationColumns(PDO $pdo): void
{
    $columns = [
        'proposal_number' => "ALTER TABLE research_proposals ADD proposal_number VARCHAR(30) NULL AFTER ref_code",
        'approved_at' => "ALTER TABLE research_proposals ADD approved_at DATETIME NULL AFTER progress",
        'registered_at' => "ALTER TABLE research_proposals ADD registered_at DATETIME NULL AFTER approved_at",
        'registration_status' => "ALTER TABLE research_proposals ADD registration_status ENUM('Pending','Registered') NOT NULL DEFAULT 'Pending' AFTER registered_at",
    ];

    foreach ($columns as $column => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM research_proposals LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $proposalNumberIndex = $pdo->query("SHOW INDEX FROM research_proposals WHERE Key_name = 'proposal_number'")->fetch();
    if (!$proposalNumberIndex) {
        $pdo->exec("ALTER TABLE research_proposals ADD UNIQUE KEY proposal_number (proposal_number)");
    }
}

function rpBuildProposalNumber(int $proposalId): string
{
    return 'CRD-' . date('Y') . '-' . str_pad((string) $proposalId, 5, '0', STR_PAD_LEFT);
}

$formError = '';
$formSuccess = '';

try {
    $cradPdo = getCradDatabaseConnection();
    rpEnsureRegistrationColumns($cradPdo);
} catch (Throwable $e) {
    error_log('CRAD register setup error: ' . $e->getMessage());
    $formError = 'Failed to prepare proposal registration database. Please check crad_db. (' . htmlspecialchars($e->getMessage()) . ')';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['process'] ?? '') === 'register-approved-proposal')) {
    if (!csrfVerify()) {
        $formError = 'Security check failed. Please try again.';
    } else {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);

        try {
            $cradPdo = getCradDatabaseConnection();
            rpEnsureRegistrationColumns($cradPdo);
            $cradPdo->beginTransaction();

            $stmt = $cradPdo->prepare(
                "SELECT id, research_title, status, progress, proposal_number, registration_status
                 FROM research_proposals
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $proposalId]);
            $proposal = $stmt->fetch();

            if (!$proposal) {
                throw new RuntimeException('Proposal not found.');
            }
            if ($proposal['status'] !== 'Approved' || (int) $proposal['progress'] < 100) {
                throw new RuntimeException('Only approved tracking proposals can be registered.');
            }

            $proposalNumber = $proposal['proposal_number'] ?: rpBuildProposalNumber((int) $proposal['id']);
            $upd = $cradPdo->prepare(
                "UPDATE research_proposals
                 SET proposal_number = :proposal_number,
                     registration_status = 'Registered',
                     registered_at = COALESCE(registered_at, NOW()),
                     updated_at = NOW()
                 WHERE id = :id
                 LIMIT 1"
            );
            $upd->execute([
                ':proposal_number' => $proposalNumber,
                ':id'              => $proposalId,
            ]);

            if (($proposal['registration_status'] ?? 'Pending') !== 'Registered') {
                $log = $cradPdo->prepare(
                    "INSERT INTO proposal_status_logs
                        (proposal_id, old_status, new_status, changed_by, remarks)
                     VALUES
                        (:proposal_id, 'Approved', 'Approved', :changed_by, :remarks)"
                );
                $log->execute([
                    ':proposal_id' => $proposalId,
                    ':changed_by'  => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                    ':remarks'     => 'Registered approved proposal as ' . $proposalNumber,
                ]);
            }

            $cradPdo->commit();

            if (function_exists('logActivity')) {
                logActivity('update', 'Registered approved proposal number:' . $proposalNumber, 'crad');
            }

            $formSuccess = 'Proposal <strong>' . htmlspecialchars($proposalNumber) . '</strong> has been registered and saved to the database.';
        } catch (Throwable $e) {
            if (isset($cradPdo) && $cradPdo instanceof PDO && $cradPdo->inTransaction()) {
                $cradPdo->rollBack();
            }
            error_log('CRAD approved proposal registration error: ' . $e->getMessage());
            $formError = 'Failed to register proposal. ' . htmlspecialchars($e->getMessage());
        }
    }
}

$approvedProposals = [];
try {
    $cradPdo = getCradDatabaseConnection();
    rpEnsureRegistrationColumns($cradPdo);

    $stmt = $cradPdo->query(
        "SELECT id, ref_code, proposal_number, research_title, rep_name,
                college_department, COALESCE(approved_at, updated_at) AS approved_on,
                registered_at, registration_status
         FROM research_proposals
         WHERE status = 'Approved'
           AND progress >= 100
         ORDER BY
           CASE registration_status WHEN 'Pending' THEN 0 ELSE 1 END,
           COALESCE(approved_at, updated_at) DESC,
           id DESC"
    );
    $approvedProposals = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('CRAD approved proposal list error: ' . $e->getMessage());
    if ($formError === '') {
        $formError = 'Failed to load approved proposals. (' . htmlspecialchars($e->getMessage()) . ')';
    }
}

require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
<div class="rp-alert rp-alert-danger" role="alert">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= $formError ?></span>
</div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
<div class="rp-alert rp-alert-success" role="alert">
    <i class="fas fa-check-circle"></i>
    <span><?= $formSuccess ?></span>
</div>
<?php endif; ?>

<style>
.rp-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.rp-alert {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
    border-radius: 12px; font-size: 0.88rem; font-weight: 600;
}
.rp-alert-danger { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
.rp-alert-success { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
.rp-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.25rem 1.4rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #312e81 100%);
    color: #fff;
    box-shadow: var(--sms-shadow-sm);
}
.rp-header h1 { margin: 0; font-size: 1.35rem; font-weight: 800; }
.rp-header p { margin: 0.3rem 0 0; color: #c7d2fe; font-size: 0.86rem; }
.rp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    min-height: 38px; padding: 0.48rem 0.9rem; border-radius: 10px;
    border: 1px solid transparent; font-size: 0.82rem; font-weight: 800;
    text-decoration: none; cursor: pointer; transition: all 0.15s ease; white-space: nowrap;
}
.rp-btn-ghost { color: #e0e7ff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
.rp-btn-ghost:hover { background: rgba(255,255,255,0.18); color: #fff; }
.rp-btn-primary { color: #fff; background: #4f46e5; border-color: #4f46e5; box-shadow: 0 6px 16px rgba(79,70,229,0.28); }
.rp-btn-primary:hover { background: #4338ca; border-color: #4338ca; }
.rp-btn-done { color: #047857; background: #d1fae5; border-color: #a7f3d0; cursor: default; }
.rp-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rp-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rp-card-head h2 {
    margin: 0; font-size: 0.78rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: var(--sms-text-muted);
}
.rp-card-head span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.rp-table-wrap { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.rp-table th,
.rp-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left;
    vertical-align: middle;
}
.rp-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.rp-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.rp-meta { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.rp-number {
    display: inline-flex; align-items: center; gap: 0.38rem;
    padding: 0.28rem 0.62rem; border-radius: 999px;
    color: #4338ca; background: rgba(99,102,241,0.12);
    font-size: 0.76rem; font-weight: 900; letter-spacing: 0.03em;
}
.rp-status {
    display: inline-flex; align-items: center;
    padding: 0.22rem 0.68rem; border-radius: 999px;
    font-size: 0.7rem; font-weight: 900;
}
.rp-status-pending { color: #b45309; background: #fef3c7; }
.rp-status-registered { color: #047857; background: #d1fae5; }
.rp-empty {
    padding: 2rem 1.25rem;
    color: var(--sms-text-muted);
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
}
[data-theme="dark"] .rp-card { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rp-card-head,
[data-theme="dark"] .rp-table th,
[data-theme="dark"] .rp-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rp-table th { background: rgba(148,163,184,0.06); }
@media (max-width: 767.98px) {
    .rp-header { flex-direction: column; align-items: flex-start; }
    .rp-btn { width: 100%; }
}
</style>

<div class="rp-wrap">
    <header class="rp-header">
        <div>
            <h1><i class="fas fa-file-signature me-2"></i>Register Proposal</h1>
            <p>Approved proposals appear here first. Click Register to generate the official proposal number.</p>
        </div>
        <a class="rp-btn rp-btn-ghost" href="<?= BASE_URL ?>/modules/crad/pages/proposal-submission-tracking.php">
            <i class="fas fa-arrow-left"></i> Back to Tracking
        </a>
    </header>

    <section class="rp-card">
        <div class="rp-card-head">
            <h2>Approved Proposals</h2>
            <span><?= count($approvedProposals) ?> record<?= count($approvedProposals) === 1 ? '' : 's' ?></span>
        </div>

        <?php if (empty($approvedProposals)): ?>
            <div class="rp-empty">
                No approved proposals yet. Proposals will appear here after their tracking progress is approved.
            </div>
        <?php else: ?>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Proposal Title</th>
                            <th>Researcher</th>
                            <th>Date Approved</th>
                            <th>Proposal Number</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedProposals as $proposal): ?>
                            <?php
                                $isRegistered = ($proposal['registration_status'] ?? 'Pending') === 'Registered';
                                $approvedDate = $proposal['approved_on'] ?: $proposal['registered_at'];
                            ?>
                            <tr>
                                <td>
                                    <div class="rp-title"><?= htmlspecialchars($proposal['research_title']) ?></div>
                                    <span class="rp-meta"><?= htmlspecialchars($proposal['ref_code']) ?> · <?= htmlspecialchars($proposal['college_department']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($proposal['rep_name']) ?></td>
                                <td><?= $approvedDate ? htmlspecialchars(date('M j, Y', strtotime($approvedDate))) : 'For confirmation' ?></td>
                                <td>
                                    <?php if (!empty($proposal['proposal_number'])): ?>
                                        <span class="rp-number"><i class="fas fa-hashtag"></i><?= htmlspecialchars($proposal['proposal_number']) ?></span>
                                    <?php else: ?>
                                        <span class="rp-meta">Will be generated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rp-status <?= $isRegistered ? 'rp-status-registered' : 'rp-status-pending' ?>">
                                        <?= $isRegistered ? 'Registered' : 'Pending' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isRegistered): ?>
                                        <span class="rp-btn rp-btn-done"><i class="fas fa-check"></i> Registered</span>
                                    <?php else: ?>
                                        <form method="post" action="" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="process" value="register-approved-proposal">
                                            <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                            <button type="submit" class="rp-btn rp-btn-primary">
                                                <i class="fas fa-save"></i> Register
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
