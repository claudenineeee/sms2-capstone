<?php
/**
 * SMS 2 - Research Group Number
 * Module: CRAD
 * Register and manage research group numbers assigned to student teams.
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
    ['label' => 'CRAD',              'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Group Number', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';

// ── Departments ───────────────────────────────────────────────────────────────
$departments = [
    'College of Computer Studies',
    'College of Business Administration',
    'College of Education',
    'College of Criminal Justice',
    'College of Hospitality & Tourism Management',
    'College of Nursing and Health Sciences',
];

$academicYears = [
    'A.Y. 2025-2026',
    'A.Y. 2026-2027',
    'A.Y. 2027-2028',
];

// ── Fetch existing groups ─────────────────────────────────────────────────────
$groups   = [];
$nextSeq  = 1;
$totalGroups  = 0;
$totalActive  = 0;
$totalPending = 0;
$totalDone    = 0;

try {
    $cradPdo = getCradDatabaseConnection();

    // Create table if it doesn't exist yet
    $cradPdo->exec("
        CREATE TABLE IF NOT EXISTS research_groups (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_number    VARCHAR(40)  NOT NULL,
            research_title  VARCHAR(255) NOT NULL DEFAULT '',
            college_dept    VARCHAR(120) NOT NULL DEFAULT '',
            adviser         VARCHAR(120) NOT NULL DEFAULT '',
            academic_year   VARCHAR(20)  NOT NULL DEFAULT '',
            leader_name     VARCHAR(120) NOT NULL DEFAULT '',
            leader_id       VARCHAR(40)  NOT NULL DEFAULT '',
            leader_email    VARCHAR(120) NOT NULL DEFAULT '',
            leader_contact  VARCHAR(40)  NOT NULL DEFAULT '',
            status          VARCHAR(40)  NOT NULL DEFAULT 'Pending',
            date_assigned   DATE         NOT NULL,
            created_by      INT UNSIGNED DEFAULT NULL,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $lastRow = $cradPdo->query("SELECT MAX(id) AS max_id FROM research_groups")->fetch();
    $nextSeq = (int)($lastRow['max_id'] ?? 0) + 1;

    $stmt   = $cradPdo->query("SELECT * FROM research_groups ORDER BY date_assigned DESC, id DESC");
    $groups = $stmt->fetchAll();

    foreach ($groups as $g) {
        $totalGroups++;
        match ($g['status']) {
            'Active'     => $totalActive++,
            'Completed'  => $totalDone++,
            default      => $totalPending++,
        };
    }
} catch (Throwable $e) {
    error_log('CRAD research-group-number error: ' . $e->getMessage());
}

$groupNumber = 'RGN-' . date('Y') . '-' . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);

// ── Handle form submission ────────────────────────────────────────────────────
$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['process'] ?? '') === 'register-group-number')) {
    if (!csrfVerify()) {
        $formError = 'Security check failed. Please try again.';
    } else {
        try {
            $cradPdo = getCradDatabaseConnection();

            $lastRow = $cradPdo->query("SELECT MAX(id) AS max_id FROM research_groups")->fetch();
            $seq     = (int)($lastRow['max_id'] ?? 0) + 1;
            $grpNo   = 'RGN-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            $ins = $cradPdo->prepare("
                INSERT INTO research_groups
                    (group_number, research_title, college_dept, adviser,
                     academic_year, leader_name, leader_id, leader_email,
                     leader_contact, status, date_assigned, created_by)
                VALUES
                    (:grp, :title, :dept, :adviser,
                     :ay, :lname, :lid, :lemail,
                     :lcontact, 'Pending', :dated, :uid)
            ");
            $ins->execute([
                ':grp'      => $grpNo,
                ':title'    => trim($_POST['research_title']   ?? ''),
                ':dept'     => trim($_POST['college_dept']     ?? ''),
                ':adviser'  => trim($_POST['adviser']          ?? ''),
                ':ay'       => trim($_POST['academic_year']    ?? ''),
                ':lname'    => trim($_POST['leader_name']      ?? ''),
                ':lid'      => trim($_POST['leader_id']        ?? ''),
                ':lemail'   => trim($_POST['leader_email']     ?? ''),
                ':lcontact' => trim($_POST['leader_contact']   ?? ''),
                ':dated'    => trim($_POST['date_assigned']    ?? date('Y-m-d')),
                ':uid'      => (int)($_SESSION['user_id'] ?? 0) ?: null,
            ]);

            if (function_exists('logActivity')) {
                logActivity('create', 'Registered research group number: ' . $grpNo, 'crad');
            }

            $formSuccess = 'Research group <strong>' . htmlspecialchars($grpNo) . '</strong> has been registered successfully.';
            $groupNumber = 'RGN-' . date('Y') . '-' . str_pad((string)($seq + 1), 4, '0', STR_PAD_LEFT);

            // Refresh list
            $stmt   = $cradPdo->query("SELECT * FROM research_groups ORDER BY date_assigned DESC, id DESC");
            $groups = $stmt->fetchAll();
            $totalGroups  = count($groups);
            $totalActive  = count(array_filter($groups, fn($g) => $g['status'] === 'Active'));
            $totalDone    = count(array_filter($groups, fn($g) => $g['status'] === 'Completed'));
            $totalPending = $totalGroups - $totalActive - $totalDone;

        } catch (Throwable $e) {
            error_log('CRAD register group error: ' . $e->getMessage());
            $formError = 'Failed to register group. Please try again. (' . htmlspecialchars($e->getMessage()) . ')';
        }
    }
}
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
<div style="display:flex;align-items:center;gap:.75rem;padding:.85rem 1.1rem;margin-bottom:1rem;
            border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;
            font-size:.88rem;font-weight:600;" role="alert">
    <i class="fas fa-exclamation-circle" style="font-size:1.1rem;flex-shrink:0;"></i>
    <span><?= $formError ?></span>
</div>
<?php endif; ?>
<?php if ($formSuccess !== ''): ?>
<div style="display:flex;align-items:center;gap:.75rem;padding:.85rem 1.1rem;margin-bottom:1rem;
            border:1px solid #bbf7d0;border-radius:12px;background:#f0fdf4;color:#166534;
            font-size:.88rem;font-weight:600;" role="alert">
    <i class="fas fa-check-circle" style="font-size:1.1rem;flex-shrink:0;"></i>
    <span><?= $formSuccess ?></span>
</div>
<?php endif; ?>

<style>
/* ── Shared wrapper ── */
.rgn-wrap { display:flex; flex-direction:column; gap:1.5rem; }

/* ── Header banner ── */
.rgn-header {
    display:flex; align-items:center; justify-content:space-between; gap:1rem;
    padding:1.25rem 1.4rem;
    border-radius:16px;
    background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 50%,#312e81 100%);
    color:#fff; box-shadow:var(--sms-shadow-sm);
}
.rgn-header h1 { margin:0; font-size:1.35rem; font-weight:800; }
.rgn-header p  { margin:.3rem 0 0; color:#c7d2fe; font-size:.86rem; }
.rgn-header-actions { display:flex; gap:.6rem; flex-shrink:0; }
.rgn-btn {
    display:inline-flex; align-items:center; gap:.4rem;
    min-height:40px; padding:.5rem 1rem; border-radius:10px;
    border:1px solid transparent; font-size:.84rem; font-weight:700;
    text-decoration:none; cursor:pointer; transition:all .15s ease;
}
.rgn-btn-ghost   { color:#e0e7ff; background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.25); }
.rgn-btn-ghost:hover { background:rgba(255,255,255,.18); color:#fff; }
.rgn-btn-primary { color:#fff; background:#4f46e5; border-color:#4f46e5; box-shadow:0 6px 16px rgba(79,70,229,.35); }
.rgn-btn-primary:hover { background:#4338ca; border-color:#4338ca; }

/* ── Stat cards ── */
.rgn-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; }
@media(max-width:900px){ .rgn-stats{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:540px){ .rgn-stats{ grid-template-columns:1fr; } }
.rgn-stat {
    display:flex; align-items:center; gap:.9rem;
    padding:1rem 1.2rem; border-radius:14px;
    border:1px solid var(--sms-border,#e2e8f0);
    background:var(--sms-surface-solid,#fff);
    box-shadow:var(--sms-shadow-sm);
}
.rgn-stat-icon {
    width:44px; height:44px; border-radius:12px;
    display:grid; place-items:center; font-size:1.05rem; flex-shrink:0;
}
.rgn-stat-icon.blue   { color:#3b82f6; background:rgba(59,130,246,.12); }
.rgn-stat-icon.green  { color:#22c55e; background:rgba(34,197,94,.12); }
.rgn-stat-icon.amber  { color:#f59e0b; background:rgba(245,158,11,.12); }
.rgn-stat-icon.purple { color:#8b5cf6; background:rgba(139,92,246,.12); }
.rgn-stat-val  { font-size:1.5rem; font-weight:800; color:var(--sms-heading); line-height:1; }
.rgn-stat-lbl  { font-size:.75rem; font-weight:600; color:var(--sms-text-muted); margin-top:.2rem; }

/* ── Card ── */
.rgn-card {
    border:1px solid var(--sms-border,#e2e8f0); border-radius:16px;
    background:var(--sms-surface-solid,#fff); box-shadow:var(--sms-shadow-sm); overflow:hidden;
}
.rgn-card-head {
    padding:1rem 1.25rem .75rem;
    border-bottom:1px solid var(--sms-border,#e2e8f0);
}
.rgn-card-head h2 {
    margin:0; font-size:.72rem; font-weight:800;
    letter-spacing:.07em; text-transform:uppercase; color:var(--sms-text-muted);
}
.rgn-card-body { padding:1.25rem; }

/* ── Group number display ── */
.rgn-number-badge {
    display:flex; align-items:center; gap:.85rem;
    padding:1rem 1.25rem; margin-bottom:1.25rem;
    border:1px dashed #6366f1; border-radius:12px;
    background:rgba(99,102,241,.06);
}
.rgn-number-badge-icon {
    width:44px; height:44px; flex:0 0 auto; border-radius:12px;
    display:grid; place-items:center; font-size:1.1rem;
    color:#4f46e5; background:rgba(99,102,241,.14);
}
.rgn-number-badge-text span {
    display:block; color:var(--sms-text-muted);
    font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
}
.rgn-number-badge-text strong {
    display:block; margin-top:.15rem;
    color:#4f46e5; font-size:1.15rem; font-weight:800; letter-spacing:.02em;
}

/* ── Form grid ── */
.rgn-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem; }
.rgn-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.9rem; }
@media(max-width:640px){ .rgn-grid-2,.rgn-grid-3{ grid-template-columns:1fr; } }
.rgn-field { display:grid; gap:.4rem; margin-bottom:.9rem; }
.rgn-field label {
    color:var(--sms-text-muted); font-size:.72rem; font-weight:700;
    letter-spacing:.04em; text-transform:uppercase;
}
.rgn-field label em { color:#ef4444; font-style:normal; }
.rgn-field input,
.rgn-field select {
    width:100%; min-height:42px; padding:.6rem .8rem;
    border:1px solid var(--sms-border,#d7e1ef); border-radius:10px;
    background:var(--sms-input-bg,#fff); color:var(--sms-text);
    font-size:.88rem; outline:none; transition:border-color .15s,box-shadow .15s;
}
.rgn-field select {
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right .9rem center; padding-right:2.2rem;
}
.rgn-field input:focus,
.rgn-field select:focus  { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.rgn-field input[readonly] {
    background:var(--sms-surface-muted,#f8fafc); color:var(--sms-text-muted);
    font-weight:700; letter-spacing:.03em;
}
.rgn-section-title {
    display:flex; align-items:center; gap:.55rem;
    margin:1.5rem 0 1rem; padding-top:1.25rem;
    border-top:1px solid var(--sms-border,#e2e8f0);
    color:var(--sms-heading); font-size:.95rem; font-weight:800;
}
.rgn-section-title:first-child { margin-top:0; padding-top:0; border-top:none; }
.rgn-section-title span { width:8px; height:8px; border-radius:50%; background:#6366f1; flex-shrink:0; }

/* ── Table ── */
.rgn-table-wrap { overflow-x:auto; }
.rgn-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.rgn-table thead th {
    padding:.7rem 1rem; text-align:left;
    font-size:.7rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:var(--sms-text-muted); border-bottom:1px solid var(--sms-border,#e2e8f0);
    background:var(--sms-surface-muted,#f8fafc); white-space:nowrap;
}
.rgn-table tbody tr { border-bottom:1px solid var(--sms-border,#e2e8f0); transition:background .12s; }
.rgn-table tbody tr:last-child { border-bottom:none; }
.rgn-table tbody tr:hover { background:var(--sms-hover,rgba(99,102,241,.04)); }
.rgn-table td { padding:.75rem 1rem; color:var(--sms-text); vertical-align:middle; }
.rgn-table td strong { display:block; font-weight:700; color:var(--sms-heading); }
.rgn-table td span   { font-size:.78rem; color:var(--sms-text-muted); }
.rgn-badge {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.25rem .65rem; border-radius:20px;
    font-size:.72rem; font-weight:700; white-space:nowrap;
}
.rgn-badge-pending   { color:#92400e; background:#fef3c7; }
.rgn-badge-active    { color:#166534; background:#dcfce7; }
.rgn-badge-completed { color:#1e40af; background:#dbeafe; }
.rgn-empty {
    text-align:center; padding:3rem 1rem;
    color:var(--sms-text-muted); font-size:.9rem;
}
.rgn-empty i { font-size:2.5rem; display:block; margin-bottom:.75rem; opacity:.35; }
</style>

<div class="rgn-wrap">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="rgn-header">
        <div>
            <h1><i class="fas fa-users" style="margin-right:.5rem;opacity:.85;"></i>Research Group Number</h1>
            <p>Register and manage research group numbers assigned to student research teams.</p>
        </div>
        <div class="rgn-header-actions">
            <a href="<?= BASE_URL ?>/modules/crad/index.php" class="rgn-btn rgn-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button type="button" class="rgn-btn rgn-btn-primary" onclick="document.getElementById('rgnFormSection').scrollIntoView({behavior:'smooth'})">
                <i class="fas fa-plus"></i> New Group
            </button>
        </div>
    </div>

    <!-- ── Stat Cards ──────────────────────────────────────────────────────── -->
    <div class="rgn-stats">
        <div class="rgn-stat">
            <div class="rgn-stat-icon blue"><i class="fas fa-users"></i></div>
            <div><div class="rgn-stat-val"><?= $totalGroups ?></div><div class="rgn-stat-lbl">Total Groups</div></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="rgn-stat-val"><?= $totalActive ?></div><div class="rgn-stat-lbl">Active</div></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon amber"><i class="fas fa-clock"></i></div>
            <div><div class="rgn-stat-val"><?= $totalPending ?></div><div class="rgn-stat-lbl">Pending</div></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon purple"><i class="fas fa-flag-checkered"></i></div>
            <div><div class="rgn-stat-val"><?= $totalDone ?></div><div class="rgn-stat-lbl">Completed</div></div>
        </div>
    </div>

    <!-- ── Group List ─────────────────────────────────────────────────────── -->
    <div class="rgn-card">
        <div class="rgn-card-head">
            <h2><i class="fas fa-list" style="margin-right:.4rem;"></i>Registered Research Groups</h2>
        </div>
        <div class="rgn-table-wrap">
            <?php if (empty($groups)): ?>
            <div class="rgn-empty">
                <i class="fas fa-users"></i>
                No research groups registered yet. Use the form below to add the first one.
            </div>
            <?php else: ?>
            <table class="rgn-table">
                <thead>
                    <tr>
                        <th>Group No.</th>
                        <th>Research Title</th>
                        <th>Adviser</th>
                        <th>College / Dept</th>
                        <th>Leader</th>
                        <th>A.Y.</th>
                        <th>Date Assigned</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($groups as $g):
                    $badgeCls = match($g['status']) {
                        'Active'    => 'rgn-badge-active',
                        'Completed' => 'rgn-badge-completed',
                        default     => 'rgn-badge-pending',
                    };
                    $badgeIcon = match($g['status']) {
                        'Active'    => 'fa-check-circle',
                        'Completed' => 'fa-flag-checkered',
                        default     => 'fa-clock',
                    };
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($g['group_number']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($g['research_title']) ?></strong></td>
                        <td><?= htmlspecialchars($g['adviser']) ?></td>
                        <td><?= htmlspecialchars($g['college_dept']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($g['leader_name']) ?></strong>
                            <span><?= htmlspecialchars($g['leader_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($g['academic_year']) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y', strtotime($g['date_assigned']))) ?></td>
                        <td>
                            <span class="rgn-badge <?= $badgeCls ?>">
                                <i class="fas <?= $badgeIcon ?>"></i>
                                <?= htmlspecialchars($g['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Registration Form ─────────────────────────────────────────────── -->
    <div class="rgn-card" id="rgnFormSection">
        <div class="rgn-card-head">
            <h2><i class="fas fa-plus-circle" style="margin-right:.4rem;"></i>Register New Research Group</h2>
        </div>
        <div class="rgn-card-body">
            <form method="post" action="">
                <?= csrfField() ?>
                <input type="hidden" name="process" value="register-group-number">

                <!-- Auto-generated group number -->
                <div class="rgn-number-badge">
                    <div class="rgn-number-badge-icon"><i class="fas fa-hashtag"></i></div>
                    <div class="rgn-number-badge-text">
                        <span>Auto-Generated Group Number</span>
                        <strong><?= htmlspecialchars($groupNumber) ?></strong>
                    </div>
                </div>

                <!-- Research Details -->
                <p class="rgn-section-title"><span></span> Research Details</p>

                <div class="rgn-field">
                    <label for="research_title">Research Title <em>*</em></label>
                    <input type="text" id="research_title" name="research_title"
                           placeholder="Enter full research title" required
                           value="<?= htmlspecialchars($_POST['research_title'] ?? '') ?>">
                </div>

                <div class="rgn-grid-2">
                    <div class="rgn-field">
                        <label for="college_dept">College / Department <em>*</em></label>
                        <select id="college_dept" name="college_dept" required>
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"
                                <?= (($_POST['college_dept'] ?? '') === $dept) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rgn-field">
                        <label for="academic_year">Academic Year <em>*</em></label>
                        <select id="academic_year" name="academic_year" required>
                            <option value="">— Select A.Y. —</option>
                            <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= htmlspecialchars($ay) ?>"
                                <?= (($_POST['academic_year'] ?? '') === $ay) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ay) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="rgn-grid-2">
                    <div class="rgn-field">
                        <label for="adviser">Research Adviser <em>*</em></label>
                        <input type="text" id="adviser" name="adviser"
                               placeholder="Full name of research adviser" required
                               value="<?= htmlspecialchars($_POST['adviser'] ?? '') ?>">
                    </div>
                    <div class="rgn-field">
                        <label for="date_assigned">Date Assigned <em>*</em></label>
                        <input type="date" id="date_assigned" name="date_assigned" required
                               value="<?= htmlspecialchars($_POST['date_assigned'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>

                <!-- Group Leader -->
                <p class="rgn-section-title"><span></span> Group Leader / Representative</p>

                <div class="rgn-grid-2">
                    <div class="rgn-field">
                        <label for="leader_name">Full Name <em>*</em></label>
                        <input type="text" id="leader_name" name="leader_name"
                               placeholder="Group leader's full name" required
                               value="<?= htmlspecialchars($_POST['leader_name'] ?? '') ?>">
                    </div>
                    <div class="rgn-field">
                        <label for="leader_id">Student ID <em>*</em></label>
                        <input type="text" id="leader_id" name="leader_id"
                               placeholder="e.g. 2024-00001" required
                               value="<?= htmlspecialchars($_POST['leader_id'] ?? '') ?>">
                    </div>
                </div>

                <div class="rgn-grid-2">
                    <div class="rgn-field">
                        <label for="leader_email">Email Address</label>
                        <input type="email" id="leader_email" name="leader_email"
                               placeholder="leader@school.edu.ph"
                               value="<?= htmlspecialchars($_POST['leader_email'] ?? '') ?>">
                    </div>
                    <div class="rgn-field">
                        <label for="leader_contact">Contact Number</label>
                        <input type="text" id="leader_contact" name="leader_contact"
                               placeholder="09XXXXXXXXX"
                               value="<?= htmlspecialchars($_POST['leader_contact'] ?? '') ?>">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--sms-border,#e2e8f0);">
                    <button type="reset" class="rgn-btn" style="background:var(--sms-surface-muted,#f8fafc);color:var(--sms-text-muted);border-color:var(--sms-border,#e2e8f0);">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button type="submit" class="rgn-btn rgn-btn-primary">
                        <i class="fas fa-save"></i> Register Group
                    </button>
                </div>

            </form>
        </div>
    </div>

</div><!-- /.rgn-wrap -->

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
