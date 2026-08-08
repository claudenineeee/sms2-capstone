<?php
/**
 * SMS 2 - Research Coordinator: View Approved Research
 * Shows registered approved research titles that already have research group numbers.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['research_coordinator', 'superadmin'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

function rcApprovedResearchFetch(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT
            g.id AS group_id,
            g.proposal_id,
            g.proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
            COALESCE(NULLIF(g.college_dept, ''), p.college_department) AS college_dept,
            COALESCE(NULLIF(g.adviser, ''), p.research_adviser) AS adviser,
            COALESCE(NULLIF(g.academic_year, ''), p.academic_year) AS academic_year,
            COALESCE(NULLIF(g.leader_name, ''), p.rep_name) AS leader_name,
            COALESCE(NULLIF(g.leader_id, ''), p.rep_id) AS leader_id,
            COALESCE(NULLIF(g.leader_email, ''), p.rep_email) AS leader_email,
            COALESCE(NULLIF(g.leader_contact, ''), p.rep_contact) AS leader_contact,
            g.status AS group_status,
            g.date_assigned,
            g.created_at AS group_created_at,
            p.status AS proposal_status,
            p.registration_status,
            'Approved' AS display_status,
            p.approved_at,
            p.registered_at
         FROM research_groups g
         INNER JOIN research_proposals p ON p.id = g.proposal_id
         WHERE p.status = 'Approved'
           AND p.registration_status = 'Registered'
           AND p.proposal_number IS NOT NULL
           AND g.group_number IS NOT NULL
           AND g.group_number <> ''
         ORDER BY g.date_assigned DESC, g.id DESC"
    );

    return $stmt->fetchAll() ?: [];
}

function rcApprovedResearchPayload(): array
{
    try {
        $pdo = getCradDatabaseConnection();
        $rows = rcApprovedResearchFetch($pdo);
    } catch (Throwable $e) {
        error_log('Research Coordinator approved research load failed: ' . $e->getMessage());
        return [
            'ok' => false,
            'error' => 'Failed to load approved research records.',
            'rows' => [],
            'stats' => ['total' => 0, 'approved' => 0, 'with_adviser' => 0],
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    }

    $withAdviser = 0;
    foreach ($rows as $row) {
        if (trim((string) ($row['adviser'] ?? '')) !== '') {
            $withAdviser++;
        }
    }

    return [
        'ok' => true,
        'rows' => $rows,
        'stats' => [
            'total' => count($rows),
            'approved' => count(array_filter($rows, static fn($row) => (string) ($row['proposal_status'] ?? '') === 'Approved')),
            'with_adviser' => $withAdviser,
        ],
        'last_sync' => date('M j, Y g:i:s A'),
    ];
}

if (($_GET['ajax'] ?? '') === 'approved-research') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rcApprovedResearchPayload());
    exit;
}

function rcE(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$payload = rcApprovedResearchPayload();
$approvedRows = $payload['rows'];
$stats = $payload['stats'];
$pageTitle = 'View Approved Research';
$activeModule = 'crad';
$activePage = 'approved-research';
$breadcrumbs = [
    ['label' => 'Research Coordinator', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'View Approved Research', 'url' => null],
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<style>
.rcar-wrap { display: flex; flex-direction: column; gap: 1rem; }
.rcar-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcar-header h1 { margin: 0; font-size: 1.25rem; font-weight: 850; color: var(--sms-heading); }
.rcar-header p { margin: 0.25rem 0 0; color: var(--sms-text-muted); font-size: 0.86rem; }
.rcar-sync { color: #2563eb; font-size: 0.78rem; font-weight: 800; white-space: nowrap; }
.rcar-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; }
.rcar-stat {
    display: flex; align-items: center; gap: 0.8rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcar-stat i {
    width: 40px; height: 40px; display: grid; place-items: center;
    border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.12);
}
.rcar-stat strong { display: block; color: var(--sms-heading); font-size: 1.35rem; line-height: 1; }
.rcar-stat span { display: block; margin-top: 0.25rem; color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }
.rcar-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rcar-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rcar-card-head h2 { margin: 0; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; }
.rcar-search {
    width: min(320px, 100%);
    min-height: 36px;
    border: 1px solid var(--sms-border, #d8e2ef);
    border-radius: 8px;
    padding: 0.45rem 0.7rem;
    background: var(--sms-surface-muted, #f8fafc);
    color: var(--sms-heading);
    font-size: 0.84rem;
}
.rcar-table-wrap { overflow-x: auto; }
.rcar-table { width: 100%; min-width: 980px; border-collapse: collapse; }
.rcar-table th,
.rcar-table td {
    padding: 0.82rem 0.9rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left; vertical-align: top;
}
.rcar-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem;
    font-weight: 850;
    text-transform: uppercase;
}
.rcar-title { color: var(--sms-heading); font-weight: 850; line-height: 1.35; }
.rcar-muted { color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 650; }
.rcar-code {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    color: #1d4ed8;
    background: rgba(37,99,235,0.12);
    font-size: 0.74rem;
    font-weight: 900;
}
.rcar-status {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.26rem 0.58rem;
    border-radius: 999px;
    color: #047857;
    background: #d1fae5;
    font-size: 0.74rem;
    font-weight: 850;
}
.rcar-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--sms-text-muted);
    font-weight: 750;
}
.rcar-error {
    margin: 0 0 1rem;
    padding: 0.8rem 1rem;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-weight: 750;
}
[data-theme="dark"] .rcar-header,
[data-theme="dark"] .rcar-stat,
[data-theme="dark"] .rcar-card { background: rgba(15,23,42,0.74); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcar-card-head,
[data-theme="dark"] .rcar-table th,
[data-theme="dark"] .rcar-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcar-table th,
[data-theme="dark"] .rcar-search { background: rgba(148,163,184,0.07); }
@media (max-width: 767.98px) {
    .rcar-header,
    .rcar-card-head { align-items: flex-start; flex-direction: column; }
    .rcar-stats { grid-template-columns: 1fr; }
    .rcar-sync,
    .rcar-search { width: 100%; }
}
</style>

<div class="rcar-wrap" data-rcar-endpoint="<?= rcE(BASE_URL . '/modules/crad/pages/approved-research.php?ajax=approved-research') ?>">
    <?php if (!$payload['ok']): ?>
        <div class="rcar-error">
            <i class="fas fa-exclamation-circle me-1"></i><?= rcE((string) $payload['error']) ?>
        </div>
    <?php endif; ?>

    <header class="rcar-header">
        <div>
            <h1><i class="fas fa-clipboard-check me-2"></i>View Approved Research</h1>
            <p>Approved research groups with official proposal and research group numbers.</p>
        </div>
        <div class="rcar-sync" id="rcarLastSync">Synced <?= rcE((string) $payload['last_sync']) ?></div>
    </header>

    <div class="rcar-stats" aria-label="Approved research summary">
        <div class="rcar-stat">
            <i class="fas fa-layer-group"></i>
            <div><strong id="rcarTotal"><?= (int) $stats['total'] ?></strong><span>Approved Groups</span></div>
        </div>
        <div class="rcar-stat">
            <i class="fas fa-hashtag"></i>
            <div><strong id="rcarApproved"><?= (int) $stats['approved'] ?></strong><span>Approved</span></div>
        </div>
        <div class="rcar-stat">
            <i class="fas fa-user-tie"></i>
            <div><strong id="rcarWithAdviser"><?= (int) $stats['with_adviser'] ?></strong><span>With Adviser</span></div>
        </div>
    </div>

    <section class="rcar-card">
        <div class="rcar-card-head">
            <h2>Approved Research List</h2>
            <input id="rcarSearch" class="rcar-search" type="search" placeholder="Search title, group, proposal, leader...">
        </div>
        <div class="rcar-table-wrap">
            <table class="rcar-table">
                <thead>
                    <tr>
                        <th>Research Group / Title</th>
                        <th>Proposal Number</th>
                        <th>Group Number</th>
                        <th>Leader</th>
                        <th>Adviser</th>
                        <th>Date Assigned</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rcarRows"></tbody>
            </table>
        </div>
        <div class="rcar-empty" id="rcarEmpty" hidden>No approved research with group number yet.</div>
    </section>
</div>

<script>
(() => {
    const root = document.querySelector('[data-rcar-endpoint]');
    if (!root) return;

    const endpoint = root.dataset.rcarEndpoint;
    const rowsBody = document.getElementById('rcarRows');
    const empty = document.getElementById('rcarEmpty');
    const search = document.getElementById('rcarSearch');
    const lastSync = document.getElementById('rcarLastSync');
    const total = document.getElementById('rcarTotal');
    const approved = document.getElementById('rcarApproved');
    const withAdviser = document.getElementById('rcarWithAdviser');
    let rows = <?= json_encode($approvedRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    const formatDate = (value) => {
        if (!value) return 'For coordination';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const rowMatches = (row, term) => {
        if (!term) return true;
        return [
            row.research_title,
            row.group_name,
            row.group_number,
            row.proposal_number,
            row.leader_name,
            row.leader_id,
            row.college_dept,
            row.adviser
        ].join(' ').toLowerCase().includes(term);
    };

    const render = () => {
        const term = (search?.value || '').trim().toLowerCase();
        const visibleRows = rows.filter((row) => rowMatches(row, term));
        rowsBody.innerHTML = visibleRows.map((row) => `
            <tr>
                <td>
                    <div class="rcar-title">${esc(row.group_name || 'Research Group')}</div>
                    <div>${esc(row.research_title || '')}</div>
                    <div class="rcar-muted">${esc(row.college_dept || '')} ${row.academic_year ? '&middot; ' + esc(row.academic_year) : ''}</div>
                </td>
                <td><span class="rcar-code"><i class="fas fa-file-signature"></i>${esc(row.proposal_number || '')}</span></td>
                <td><span class="rcar-code"><i class="fas fa-hashtag"></i>${esc(row.group_number || '')}</span></td>
                <td>
                    <div class="rcar-title">${esc(row.leader_name || '')}</div>
                    <div class="rcar-muted">${esc(row.leader_id || '')}</div>
                    <div class="rcar-muted">${esc(row.leader_email || '')}</div>
                </td>
                <td>${esc(row.adviser || 'For assignment')}</td>
                <td>${esc(formatDate(row.date_assigned || row.group_created_at))}</td>
                <td><span class="rcar-status"><i class="fas fa-check-circle"></i>${esc(row.display_status || row.proposal_status || 'Approved')}</span></td>
            </tr>
        `).join('');
        empty.hidden = visibleRows.length !== 0;
    };

    const refresh = async () => {
        try {
            const res = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to sync.');
            rows = Array.isArray(data.rows) ? data.rows : [];
            total.textContent = data.stats?.total ?? rows.length;
            approved.textContent = data.stats?.approved ?? 0;
            withAdviser.textContent = data.stats?.with_adviser ?? 0;
            lastSync.textContent = `Synced ${data.last_sync || 'just now'}`;
            render();
        } catch (error) {
            lastSync.textContent = 'Sync paused';
        }
    };

    search?.addEventListener('input', render);
    render();
    window.setInterval(refresh, 5000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
