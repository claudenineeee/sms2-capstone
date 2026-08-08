<?php
/**
 * SMS 2 - Research Group Number
 * Module: CRAD
 * Generates research group numbers for registered proposals.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

requireAuth();

$pageTitle    = 'Research Group Number';
$activeModule = 'crad';
$activePage   = 'research-group-number';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Group Number', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

function rgnEnsureSchema(PDO $pdo): void
{
    $proposalColumns = [
        'proposal_number' => "ALTER TABLE research_proposals ADD proposal_number VARCHAR(30) NULL AFTER ref_code",
        'approved_at' => "ALTER TABLE research_proposals ADD approved_at DATETIME NULL AFTER progress",
        'registered_at' => "ALTER TABLE research_proposals ADD registered_at DATETIME NULL AFTER approved_at",
        'registration_status' => "ALTER TABLE research_proposals ADD registration_status ENUM('Pending','Registered') NOT NULL DEFAULT 'Pending' AFTER registered_at",
    ];

    foreach ($proposalColumns as $column => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM research_proposals LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_groups (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            proposal_id     INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number    VARCHAR(40) NOT NULL,
            group_name      VARCHAR(40) NOT NULL DEFAULT '',
            research_title  VARCHAR(255) NOT NULL DEFAULT '',
            college_dept    VARCHAR(120) NOT NULL DEFAULT '',
            adviser         VARCHAR(120) NOT NULL DEFAULT '',
            academic_year   VARCHAR(20) NOT NULL DEFAULT '',
            leader_name     VARCHAR(120) NOT NULL DEFAULT '',
            leader_id       VARCHAR(40) NOT NULL DEFAULT '',
            leader_email    VARCHAR(120) NOT NULL DEFAULT '',
            leader_contact  VARCHAR(40) NOT NULL DEFAULT '',
            status          VARCHAR(40) NOT NULL DEFAULT 'Registered',
            date_assigned   DATE NOT NULL,
            created_by      INT UNSIGNED DEFAULT NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $columns = [
        'proposal_id' => "ALTER TABLE research_groups ADD proposal_id INT UNSIGNED DEFAULT NULL AFTER id",
        'proposal_number' => "ALTER TABLE research_groups ADD proposal_number VARCHAR(30) DEFAULT NULL AFTER proposal_id",
        'group_name' => "ALTER TABLE research_groups ADD group_name VARCHAR(40) NOT NULL DEFAULT '' AFTER group_number",
    ];

    foreach ($columns as $column => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM research_groups LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $indexes = [
        'group_number' => "ALTER TABLE research_groups ADD UNIQUE KEY group_number (group_number)",
        'proposal_id' => "ALTER TABLE research_groups ADD UNIQUE KEY proposal_id (proposal_id)",
        'idx_rg_proposal_number' => "ALTER TABLE research_groups ADD KEY idx_rg_proposal_number (proposal_number)",
    ];

    foreach ($indexes as $name => $sql) {
        $exists = $pdo->query("SHOW INDEX FROM research_groups WHERE Key_name = " . $pdo->quote($name))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }
}

function rgnBuildGroupNumber(int $sequence): string
{
    return 'RG-' . date('Y') . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
}

function rgnBuildGroupName(int $sequence): string
{
    return 'Group ' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
}

$formError = '';
$formSuccess = '';

try {
    $cradPdo = getCradDatabaseConnection();
    rgnEnsureSchema($cradPdo);
} catch (Throwable $e) {
    error_log('CRAD research group setup error: ' . $e->getMessage());
    $formError = 'Failed to prepare research group database. (' . htmlspecialchars($e->getMessage()) . ')';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['process'] ?? '') === 'generate-research-group')) {
    if (!csrfVerify()) {
        $formError = 'Security check failed. Please try again.';
    } else {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);

        try {
            $cradPdo = getCradDatabaseConnection();
            rgnEnsureSchema($cradPdo);
            $cradPdo->beginTransaction();

            $stmt = $cradPdo->prepare(
                "SELECT id, proposal_number, research_title, college_department,
                        research_adviser, academic_year, rep_name, rep_id,
                        rep_email, rep_contact, registration_status
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
            if (($proposal['registration_status'] ?? '') !== 'Registered' || empty($proposal['proposal_number'])) {
                throw new RuntimeException('Only registered proposals can receive a research group number.');
            }

            $existing = $cradPdo->prepare("SELECT group_number FROM research_groups WHERE proposal_id = :id LIMIT 1");
            $existing->execute([':id' => $proposalId]);
            $existingGroup = $existing->fetch();

            if ($existingGroup) {
                $groupNumber = $existingGroup['group_number'];
            } else {
                $lastRow = $cradPdo->query("SELECT MAX(id) AS max_id FROM research_groups")->fetch();
                $seq = (int) ($lastRow['max_id'] ?? 0) + 1;
                $groupNumber = rgnBuildGroupNumber($seq);
                $groupName = rgnBuildGroupName($seq);

                $ins = $cradPdo->prepare(
                    "INSERT INTO research_groups
                        (proposal_id, proposal_number, group_number, group_name,
                         research_title, college_dept, adviser, academic_year,
                         leader_name, leader_id, leader_email, leader_contact,
                         status, date_assigned, created_by)
                     VALUES
                        (:proposal_id, :proposal_number, :group_number, :group_name,
                         :research_title, :college_dept, :adviser, :academic_year,
                         :leader_name, :leader_id, :leader_email, :leader_contact,
                         'Registered', :date_assigned, :created_by)"
                );
                $ins->execute([
                    ':proposal_id'     => $proposalId,
                    ':proposal_number' => $proposal['proposal_number'],
                    ':group_number'    => $groupNumber,
                    ':group_name'      => $groupName,
                    ':research_title'  => $proposal['research_title'],
                    ':college_dept'    => $proposal['college_department'],
                    ':adviser'         => $proposal['research_adviser'],
                    ':academic_year'   => $proposal['academic_year'],
                    ':leader_name'     => $proposal['rep_name'],
                    ':leader_id'       => $proposal['rep_id'],
                    ':leader_email'    => $proposal['rep_email'],
                    ':leader_contact'  => $proposal['rep_contact'],
                    ':date_assigned'   => date('Y-m-d'),
                    ':created_by'      => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                ]);
            }

            $cradPdo->commit();

            if (function_exists('logActivity')) {
                logActivity('create', 'Generated research group number: ' . $groupNumber, 'crad');
            }

            $formSuccess = 'Research group number <strong>' . htmlspecialchars($groupNumber) . '</strong> has been linked to proposal <strong>' . htmlspecialchars($proposal['proposal_number']) . '</strong>.';
        } catch (Throwable $e) {
            if (isset($cradPdo) && $cradPdo instanceof PDO && $cradPdo->inTransaction()) {
                $cradPdo->rollBack();
            }
            error_log('CRAD generate research group error: ' . $e->getMessage());
            $formError = 'Failed to generate research group number. ' . htmlspecialchars($e->getMessage());
        }
    }
}

$registeredProposals = [];
$totalLinked = 0;
$totalPending = 0;

try {
    $cradPdo = getCradDatabaseConnection();
    rgnEnsureSchema($cradPdo);

    $stmt = $cradPdo->query(
        "SELECT p.id, p.proposal_number, p.research_title, p.rep_name,
                p.college_department, p.research_adviser, p.academic_year,
                p.registered_at, g.group_number, g.group_name, g.date_assigned
         FROM research_proposals p
         LEFT JOIN research_groups g ON g.proposal_id = p.id
         WHERE p.registration_status = 'Registered'
           AND p.proposal_number IS NOT NULL
         ORDER BY
           CASE WHEN g.id IS NULL THEN 0 ELSE 1 END,
           p.registered_at DESC,
           p.id DESC"
    );
    $registeredProposals = $stmt->fetchAll();
    $totalLinked = count(array_filter($registeredProposals, fn($p) => !empty($p['group_number'])));
    $totalPending = count($registeredProposals) - $totalLinked;
} catch (Throwable $e) {
    error_log('CRAD research group list error: ' . $e->getMessage());
    if ($formError === '') {
        $formError = 'Failed to load registered proposals. (' . htmlspecialchars($e->getMessage()) . ')';
    }
}

require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
<div class="rgn-alert rgn-alert-danger" role="alert">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= $formError ?></span>
</div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
<div class="rgn-alert rgn-alert-success" role="alert">
    <i class="fas fa-check-circle"></i>
    <span><?= $formSuccess ?></span>
</div>
<?php endif; ?>

<style>
.rgn-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.rgn-alert {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
    border-radius: 12px; font-size: 0.88rem; font-weight: 600;
}
.rgn-alert-danger { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
.rgn-alert-success { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
.rgn-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.25rem 1.4rem;
    border-radius: 16px;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #312e81 100%);
    color: #fff; box-shadow: var(--sms-shadow-sm);
}
.rgn-header h1 { margin: 0; font-size: 1.35rem; font-weight: 800; }
.rgn-header p { margin: 0.3rem 0 0; color: #c7d2fe; font-size: 0.86rem; }
.rgn-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.42rem;
    min-height: 38px; padding: 0.48rem 0.9rem;
    border: 1px solid transparent; border-radius: 10px;
    font-size: 0.82rem; font-weight: 800; text-decoration: none;
    cursor: pointer; white-space: nowrap;
}
.rgn-btn-ghost { color: #e0e7ff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
.rgn-btn-primary { color: #fff; background: #4f46e5; border-color: #4f46e5; box-shadow: 0 6px 16px rgba(79,70,229,0.28); }
.rgn-btn-done { color: #047857; background: #d1fae5; border-color: #a7f3d0; cursor: default; }
.rgn-stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0.85rem; }
.rgn-stat {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.95rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 14px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rgn-stat-icon {
    width: 42px; height: 42px; display: grid; place-items: center;
    border-radius: 12px; flex: 0 0 auto;
}
.rgn-stat-icon.blue { color: #2563eb; background: rgba(37,99,235,0.12); }
.rgn-stat-icon.green { color: #059669; background: rgba(16,185,129,0.12); }
.rgn-stat-icon.amber { color: #d97706; background: rgba(245,158,11,0.14); }
.rgn-stat strong { display: block; color: var(--sms-heading); font-size: 1.3rem; font-weight: 850; }
.rgn-stat span { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
.rgn-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rgn-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rgn-card-head h2 {
    margin: 0; color: var(--sms-text-muted);
    font-size: 0.78rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase;
}
.rgn-card-head span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.rgn-table-wrap { overflow-x: auto; }
.rgn-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.rgn-table th,
.rgn-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left; vertical-align: middle;
}
.rgn-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.rgn-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.rgn-meta { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.rgn-code {
    display: inline-flex; align-items: center; gap: 0.38rem;
    padding: 0.28rem 0.62rem;
    border-radius: 999px;
    color: #4338ca; background: rgba(99,102,241,0.12);
    font-size: 0.76rem; font-weight: 900; letter-spacing: 0.03em;
}
.rgn-link-box {
    display: grid; gap: 0.45rem;
    min-width: 170px;
}
.rgn-link-box span { color: var(--sms-text-muted); font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
.rgn-empty {
    padding: 2rem 1.25rem;
    text-align: center;
    color: var(--sms-text-muted);
    font-size: 0.9rem;
    font-weight: 700;
}
[data-theme="dark"] .rgn-card,
[data-theme="dark"] .rgn-stat { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rgn-card-head,
[data-theme="dark"] .rgn-table th,
[data-theme="dark"] .rgn-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rgn-table th { background: rgba(148,163,184,0.06); }
@media (max-width: 767.98px) {
    .rgn-header { flex-direction: column; align-items: flex-start; }
    .rgn-stats { grid-template-columns: 1fr; }
    .rgn-btn { width: 100%; }
}
</style>

<div class="rgn-wrap">
    <header class="rgn-header">
        <div>
            <h1><i class="fas fa-users me-2"></i>Research Group Number</h1>
            <p>Generate research group numbers only after a proposal has been registered.</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/crad/pages/register-proposal.php" class="rgn-btn rgn-btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to Register Proposal
        </a>
    </header>

    <div class="rgn-stats">
        <div class="rgn-stat">
            <div class="rgn-stat-icon blue"><i class="fas fa-file-signature"></i></div>
            <div><strong><?= count($registeredProposals) ?></strong><span>Registered Proposals</span></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon green"><i class="fas fa-link"></i></div>
            <div><strong><?= $totalLinked ?></strong><span>Linked Groups</span></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon amber"><i class="fas fa-clock"></i></div>
            <div><strong><?= $totalPending ?></strong><span>Waiting</span></div>
        </div>
    </div>

    <section class="rgn-card">
        <div class="rgn-card-head">
            <h2>Registered Proposals</h2>
            <span><?= count($registeredProposals) ?> record<?= count($registeredProposals) === 1 ? '' : 's' ?></span>
        </div>

        <?php if (empty($registeredProposals)): ?>
            <div class="rgn-empty">
                No registered proposals yet. A proposal must be approved and registered before a research group number can be generated.
            </div>
        <?php else: ?>
            <div class="rgn-table-wrap">
                <table class="rgn-table">
                    <thead>
                        <tr>
                            <th>Proposal</th>
                            <th>Researcher</th>
                            <th>Date Registered</th>
                            <th>Generated Link</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredProposals as $proposal): ?>
                            <?php $hasGroup = !empty($proposal['group_number']); ?>
                            <tr>
                                <td>
                                    <div class="rgn-title"><?= htmlspecialchars($proposal['research_title']) ?></div>
                                    <span class="rgn-meta"><?= htmlspecialchars($proposal['college_department']) ?> · <?= htmlspecialchars($proposal['research_adviser']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($proposal['rep_name']) ?></td>
                                <td><?= !empty($proposal['registered_at']) ? htmlspecialchars(date('M j, Y', strtotime($proposal['registered_at']))) : 'Registered' ?></td>
                                <td>
                                    <div class="rgn-link-box">
                                        <span>Proposal Number</span>
                                        <strong class="rgn-code"><?= htmlspecialchars($proposal['proposal_number']) ?></strong>
                                        <?php if ($hasGroup): ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-code"><?= htmlspecialchars($proposal['group_number']) ?></strong>
                                            <span><?= htmlspecialchars($proposal['group_name']) ?></span>
                                        <?php else: ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-meta">Will be generated</strong>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($hasGroup): ?>
                                        <span class="rgn-btn rgn-btn-done"><i class="fas fa-check"></i> Generated</span>
                                    <?php else: ?>
                                        <form method="post" action="" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="process" value="generate-research-group">
                                            <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                            <button type="submit" class="rgn-btn rgn-btn-primary">
                                                <i class="fas fa-hashtag"></i> Generate
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
