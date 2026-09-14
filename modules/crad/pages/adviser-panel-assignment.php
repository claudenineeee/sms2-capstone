<?php
/**
 * SMS 2 - Record Adviser/Panel Assignment
 * Module: CRAD
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

function cradOfficialAssignmentRecords(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("
            SELECT
                COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS research_group,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, '') AS research_title,
                (
                    SELECT a.adviser_name
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                    ORDER BY a.assigned_at DESC, a.updated_at DESC, a.id DESC
                    LIMIT 1
                ) AS adviser,
                (
                    SELECT pa.panel_name
                    FROM research_panel_assignments pa
                    WHERE pa.assignment_status = 'Assigned'
                      AND (pa.research_group_id = g.id OR pa.group_number = g.group_number OR pa.proposal_id = g.proposal_id)
                    ORDER BY pa.assigned_at ASC, pa.updated_at ASC, pa.id ASC
                    LIMIT 1
                ) AS panel_1,
                GREATEST(
                    COALESCE((
                        SELECT MAX(a.updated_at)
                        FROM research_adviser_assignments a
                        WHERE a.assignment_status = 'Assigned'
                          AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                    ), '1000-01-01 00:00:00'),
                    COALESCE((
                        SELECT MAX(pa.updated_at)
                        FROM research_panel_assignments pa
                        WHERE pa.assignment_status = 'Assigned'
                          AND (pa.research_group_id = g.id OR pa.group_number = g.group_number OR pa.proposal_id = g.proposal_id)
                    ), '1000-01-01 00:00:00')
                ) AS updated_at
             FROM research_groups g
             LEFT JOIN research_proposals p ON p.id = g.proposal_id
             WHERE EXISTS (
                SELECT 1
                FROM research_adviser_assignments a
                WHERE a.assignment_status = 'Assigned'
                  AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
             )
             AND EXISTS (
                SELECT 1
                FROM research_panel_assignments pa
                WHERE pa.assignment_status = 'Assigned'
                  AND (pa.research_group_id = g.id OR pa.group_number = g.group_number OR pa.proposal_id = g.proposal_id)
             )
             ORDER BY updated_at DESC, g.id DESC
        ");
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Official adviser/panel record load failed: ' . $e->getMessage());
        return [];
    }
}

function cradOfficialAssignmentStats(array $records): array
{
    $withAdviser = 0;
    $withPanel = 0;

    foreach ($records as $record) {
        if (trim((string) ($record['adviser'] ?? '')) !== '') {
            $withAdviser++;
        }
        if (trim((string) ($record['panel_1'] ?? '')) !== '') {
            $withPanel++;
        }
    }

    return [
        'total' => count($records),
        'adviser_assigned' => $withAdviser,
        'panel_assigned' => $withPanel,
        'official_records' => count($records),
    ];
}

if (($_GET['ajax'] ?? '') === 'official-records') {
    requireAuth();
    header('Content-Type: application/json; charset=utf-8');
    $pdo = cradDb();
    $records = $pdo instanceof PDO ? cradOfficialAssignmentRecords($pdo) : [];
    echo json_encode([
        'ok' => true,
        'rows' => $records,
        'stats' => cradOfficialAssignmentStats($records),
        'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
    ]);
    exit;
}

$pageTitle    = 'Record Adviser/Panel Assignment';
$activeModule = 'crad';
$activePage   = 'adviser-panel-assignment';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Record Adviser/Panel Assignment', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
$officialAssignmentPdo = cradDb();
$officialAssignmentRecords = $officialAssignmentPdo instanceof PDO
    ? cradOfficialAssignmentRecords($officialAssignmentPdo)
    : [];
$officialAssignmentStats = cradOfficialAssignmentStats($officialAssignmentRecords);
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
    .record-assignment-page-head {
        margin: 1rem 0;
    }
    .record-assignment-page-head h1 {
        color: var(--sms-heading, #0f172a);
        font-size: 1.35rem;
        font-weight: 800;
        margin: 0;
    }
    .record-assignment-page-head p {
        color: var(--sms-text-muted, #64748b);
        margin: .35rem 0 0;
        max-width: 780px;
    }
    .record-assignment-stats {
        display: grid;
        gap: .85rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 1rem 0;
    }
    .record-assignment-stat {
        align-items: center;
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-xs, 0 4px 12px rgba(15, 23, 42, .06));
        display: flex;
        gap: .85rem;
        padding: .95rem 1rem;
    }
    .record-assignment-stat__icon {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .record-assignment-stat__icon i {
        font-size: 1rem;
    }
    .record-assignment-stat__icon--blue {
        background: #dbeafe;
        color: #2563eb;
    }
    .record-assignment-stat__icon--amber {
        background: #ffedd5;
        color: #d97706;
    }
    .record-assignment-stat__icon--purple {
        background: #ede9fe;
        color: #7c3aed;
    }
    .record-assignment-stat__icon--green {
        background: #d1fae5;
        color: #059669;
    }
    .record-assignment-stat small {
        color: var(--sms-text-muted, #64748b);
        display: block;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .record-assignment-stat strong {
        color: var(--sms-heading, #0f172a);
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: .15rem;
    }
    .record-assignment-tracking {
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #e2e8f0);
        border-radius: 16px;
        box-shadow: var(--sms-shadow-sm, 0 8px 20px rgba(15, 23, 42, .06));
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .record-assignment-tracking__title {
        border-bottom: 1px solid var(--sms-border, #e2e8f0);
        color: var(--sms-text-muted, #64748b);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        padding: 1rem 1.25rem;
        text-transform: uppercase;
    }
    .record-assignment-tracking__controls {
        align-items: center;
        background: var(--sms-surface-muted, #f8fafc);
        border-bottom: 1px solid var(--sms-border, #e2e8f0);
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        padding: .85rem 1.25rem;
    }
    .record-assignment-search {
        align-items: center;
        background: var(--sms-input-bg, #fff);
        border: 1px solid var(--sms-border, #d7e1ef);
        border-radius: 10px;
        display: flex;
        flex: 1 1 200px;
        gap: .5rem;
        min-height: 38px;
        padding: .4rem .75rem;
    }
    .record-assignment-search i {
        color: var(--sms-text-muted, #64748b);
    }
    .record-assignment-search input {
        background: transparent;
        border: 0;
        color: var(--sms-text, #334155);
        font-size: .84rem;
        min-width: 0;
        outline: 0;
        width: 100%;
    }
    .record-assignment-filter {
        background: var(--sms-input-bg, #fff);
        border: 1px solid var(--sms-border, #d7e1ef);
        border-radius: 10px;
        color: var(--sms-text, #334155);
        font-size: .84rem;
        min-height: 38px;
        outline: none;
        padding: .4rem .75rem;
    }
    .official-assignment-record {
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
        margin-top: 1rem;
        overflow: hidden;
    }
    .official-assignment-record__head {
        align-items: center;
        border-bottom: 1px solid var(--sms-border, #dbe4f0);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.15rem;
    }
    .official-assignment-record__head h2 {
        color: var(--sms-heading, #0f172a);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }
    .official-assignment-record__head p {
        color: var(--sms-text-muted, #64748b);
        font-size: .86rem;
        margin: .2rem 0 0;
    }
    .official-assignment-record__sync {
        color: var(--sms-text-muted, #64748b);
        flex: 0 0 auto;
        font-size: .78rem;
        font-weight: 700;
    }
    .official-assignment-record table {
        margin: 0;
    }
    .official-assignment-record th {
        color: var(--sms-text-muted, #64748b);
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .official-assignment-record td {
        color: var(--sms-text, #334155);
        font-size: .9rem;
        vertical-align: middle;
    }
    .official-assignment-record strong {
        color: var(--sms-heading, #0f172a);
        display: block;
        font-weight: 800;
    }
    .official-assignment-record small {
        color: var(--sms-text-muted, #64748b);
        display: block;
        margin-top: .15rem;
    }
    .official-assignment-record__empty {
        color: var(--sms-text-muted, #64748b);
        padding: 1.2rem;
        text-align: center;
    }
    @media (max-width: 1100px) {
        .record-assignment-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 720px) {
        .record-assignment-stats,
        .record-assignment-tracking__controls {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="record-assignment-page-head">
    <h1>Record Adviser/Panel Assignment</h1>
</div>

<div class="record-assignment-stats" data-official-assignment-stats>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--blue">
            <i class="fas fa-layer-group" aria-hidden="true"></i>
        </span>
        <div>
            <small>Total Records</small>
            <strong data-official-stat="total"><?= (int) $officialAssignmentStats['total'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--amber">
            <i class="fas fa-user-check" aria-hidden="true"></i>
        </span>
        <div>
            <small>Adviser Assigned</small>
            <strong data-official-stat="adviser_assigned"><?= (int) $officialAssignmentStats['adviser_assigned'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--purple">
            <i class="fas fa-users" aria-hidden="true"></i>
        </span>
        <div>
            <small>Panel Assigned</small>
            <strong data-official-stat="panel_assigned"><?= (int) $officialAssignmentStats['panel_assigned'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--green">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
        </span>
        <div>
            <small>Official Records</small>
            <strong data-official-stat="official_records"><?= (int) $officialAssignmentStats['official_records'] ?></strong>
        </div>
    </section>
</div>

<section class="record-assignment-tracking">
    <div class="record-assignment-tracking__title">Assignment Record Tracking</div>
    <div class="record-assignment-tracking__controls">
        <label class="record-assignment-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" data-official-assignment-search placeholder="Search by research group, title, adviser, or panel...">
        </label>
        <select class="record-assignment-filter" data-official-assignment-status>
            <option value="all">All Status</option>
            <option value="official">Official Record</option>
        </select>
    </div>
</section>

<section class="official-assignment-record" data-official-assignment-records data-endpoint="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/adviser-panel-assignment.php?ajax=official-records') ?>">
    <div class="official-assignment-record__head">
        <div>
            <h2>Official Adviser/Panel Assignment Record</h2>
        </div>
        <span class="official-assignment-record__sync" data-official-assignment-sync>Syncing...</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Research Group</th>
                    <th>Adviser</th>
                    <th>Panel 1</th>
                </tr>
            </thead>
            <tbody data-official-assignment-rows>
                <?php foreach ($officialAssignmentRecords as $record): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) ($record['research_group'] ?? 'Research Group')) ?></strong>
                            <?php if (!empty($record['research_title'])): ?>
                                <small><?= htmlspecialchars((string) $record['research_title']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($record['adviser'] ?: 'For assignment')) ?></td>
                        <td><?= htmlspecialchars((string) ($record['panel_1'] ?: 'For assignment')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="official-assignment-record__empty" data-official-assignment-empty <?= $officialAssignmentRecords ? 'hidden' : '' ?>>
        Wala pang completed adviser and panel assignment record.
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-official-assignment-records]');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const rowsBody = root.querySelector('[data-official-assignment-rows]');
    const empty = root.querySelector('[data-official-assignment-empty]');
    const sync = root.querySelector('[data-official-assignment-sync]');
    const search = document.querySelector('[data-official-assignment-search]');
    const status = document.querySelector('[data-official-assignment-status]');
    const statNodes = document.querySelectorAll('[data-official-stat]');
    let allRows = [];
    let isRefreshing = false;
    let refreshTimer = null;

    const esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    };

    const renderStats = function (stats) {
        statNodes.forEach(function (node) {
            const key = node.dataset.officialStat;
            node.textContent = stats && Object.prototype.hasOwnProperty.call(stats, key)
                ? String(stats[key])
                : '0';
        });
    };

    const computeStats = function (rows) {
        const list = Array.isArray(rows) ? rows : [];
        return {
            total: list.length,
            adviser_assigned: list.filter(function (row) { return String(row.adviser || '').trim() !== ''; }).length,
            panel_assigned: list.filter(function (row) { return String(row.panel_1 || '').trim() !== ''; }).length,
            official_records: list.length
        };
    };

    const filteredRows = function () {
        const term = search ? search.value.trim().toLowerCase() : '';
        const selectedStatus = status ? status.value : 'all';

        return allRows.filter(function (row) {
            const rowStatus = String(row.status || 'official').toLowerCase();
            const haystack = [
                row.research_group,
                row.research_title,
                row.adviser,
                row.panel_1,
                rowStatus
            ].join(' ').toLowerCase();
            const matchesSearch = term === '' || haystack.indexOf(term) !== -1;
            const matchesStatus = selectedStatus === 'all' || selectedStatus === rowStatus;
            return matchesSearch && matchesStatus;
        });
    };

    const renderRows = function () {
        const list = filteredRows();
        rowsBody.innerHTML = list.map(function (row) {
            return '<tr>' +
                '<td><strong>' + esc(row.research_group || 'Research Group') + '</strong>' +
                    (row.research_title ? '<small>' + esc(row.research_title) + '</small>' : '') +
                '</td>' +
                '<td>' + esc(row.adviser || 'For assignment') + '</td>' +
                '<td>' + esc(row.panel_1 || 'For assignment') + '</td>' +
            '</tr>';
        }).join('');
        if (empty) {
            empty.hidden = list.length !== 0;
            empty.textContent = allRows.length === 0
                ? 'Wala pang completed adviser and panel assignment record.'
                : 'Walang record na tumugma sa search o filter.';
        }
    };

    const refresh = async function () {
        if (isRefreshing) return;
        isRefreshing = true;
        try {
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!res.ok) {
                throw new Error('Request failed');
            }
            const data = await res.json();
            if (!data.ok) return;
            allRows = (Array.isArray(data.rows) ? data.rows : []).map(function (row) {
                return Object.assign({ status: 'official' }, row);
            });
            renderStats(data.stats || computeStats(allRows));
            renderRows();
            if (sync) sync.textContent = 'Synced ' + (data.synced_at || 'just now');
        } catch (error) {
            if (sync) sync.textContent = 'Sync paused';
        } finally {
            isRefreshing = false;
        }
    };

    allRows = Array.from(rowsBody.querySelectorAll('tr')).map(function (row) {
        const cells = row.querySelectorAll('td');
        return {
            research_group: cells[0] ? (cells[0].querySelector('strong')?.textContent || cells[0].textContent || '').trim() : '',
            research_title: cells[0] ? (cells[0].querySelector('small')?.textContent || '').trim() : '',
            adviser: cells[1] ? cells[1].textContent.trim() : '',
            panel_1: cells[2] ? cells[2].textContent.trim() : '',
            status: 'official'
        };
    });

    if (search) search.addEventListener('input', renderRows);
    if (status) status.addEventListener('change', renderRows);

    refresh();
    refreshTimer = window.setInterval(refresh, 5000);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (refreshTimer) window.clearInterval(refreshTimer);
            refreshTimer = null;
            return;
        }
        if (refreshTimer) window.clearInterval(refreshTimer);
        refresh();
        refreshTimer = window.setInterval(refresh, 5000);
    });
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
