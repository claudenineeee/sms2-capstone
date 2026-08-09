<?php
/**
 * SMS 2 - Research Coordinator live assignment views.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/notifications.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['research_coordinator', 'superadmin'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$rcAssignmentKind = $rcAssignmentKind ?? 'adviser';
$rcPageSlug = $rcPageSlug ?? ($rcAssignmentKind === 'panel' ? 'find-contact-panel' : 'find-contact-adviser');

$pageMap = [
    'find-contact-adviser' => ['kind' => 'adviser', 'title' => 'Find/Contact Adviser', 'lead' => 'Adviser Assignment'],
    'adviser-availability' => ['kind' => 'adviser', 'title' => 'Check Adviser Availability', 'lead' => 'Adviser Assignment'],
    'assign-research-adviser' => ['kind' => 'adviser', 'title' => 'Assign Research Adviser', 'lead' => 'Adviser Assignment'],
    'find-contact-panel' => ['kind' => 'panel', 'title' => 'Find/Contact Panel', 'lead' => 'Panel Assignment'],
    'panel-availability' => ['kind' => 'panel', 'title' => 'Check Panel Availability', 'lead' => 'Panel Assignment'],
    'assign-panel-members' => ['kind' => 'panel', 'title' => 'Assign Panel Members', 'lead' => 'Panel Assignment'],
    'manage-assignments' => ['kind' => 'all', 'title' => 'View/Manage Assignments', 'lead' => 'Adviser and Panel Assignment'],
];

if (!isset($pageMap[$rcPageSlug])) {
    $rcPageSlug = 'find-contact-adviser';
}

$pageConfig = $pageMap[$rcPageSlug];
$rcAssignmentKind = $pageConfig['kind'];

function rcAssignmentEnsureSchema(PDO $pdo): void
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'research_groups'")->fetch();
        if ($exists) {
            $pdo->exec("ALTER TABLE research_groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        error_log('CRAD research group collation alignment skipped: ' . $e->getMessage());
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_adviser_assignments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) DEFAULT NULL,
            adviser_name VARCHAR(150) NOT NULL DEFAULT '',
            adviser_email VARCHAR(190) NOT NULL DEFAULT '',
            expertise VARCHAR(255) NOT NULL DEFAULT '',
            availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            assignment_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            notes TEXT DEFAULT NULL,
            assigned_by INT UNSIGNED DEFAULT NULL,
            assigned_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_raa_group (research_group_id),
            KEY idx_raa_proposal (proposal_id),
            KEY idx_raa_group_number (group_number),
            KEY idx_raa_status (assignment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_panel_assignments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) DEFAULT NULL,
            panel_name VARCHAR(150) NOT NULL DEFAULT '',
            panel_email VARCHAR(190) NOT NULL DEFAULT '',
            panel_role VARCHAR(80) NOT NULL DEFAULT '',
            expertise VARCHAR(255) NOT NULL DEFAULT '',
            availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            assignment_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            notes TEXT DEFAULT NULL,
            assigned_by INT UNSIGNED DEFAULT NULL,
            assigned_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_rpa_group (research_group_id),
            KEY idx_rpa_proposal (proposal_id),
            KEY idx_rpa_group_number (group_number),
            KEY idx_rpa_status (assignment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    smsAssignmentNotificationEnsureSentSchema($pdo);
}

function rcAssignmentResetStaleAssignments(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (['research_adviser_assignments', 'research_panel_assignments'] as $table) {
        try {
            $pdo->exec("
                UPDATE {$table} a
                   SET a.assignment_status = 'Pending',
                       a.assigned_by = NULL,
                       a.assigned_at = NULL,
                       a.notification_sent_at = NULL,
                       a.notification_sent_by = NULL,
                       a.updated_at = NOW()
                 WHERE a.assignment_status = 'Assigned'
                   AND NOT EXISTS (
                    SELECT 1
                    FROM research_groups g
                    INNER JOIN research_proposals p ON p.id = g.proposal_id
                    WHERE p.status = 'Approved'
                      AND p.registration_status = 'Registered'
                      AND g.group_number IS NOT NULL
                      AND g.group_number <> ''
                      AND (
                        (a.research_group_id IS NOT NULL AND a.research_group_id = g.id)
                        OR (a.group_number IS NOT NULL AND a.group_number <> '' AND a.group_number = g.group_number)
                        OR (a.proposal_id IS NOT NULL AND a.proposal_id = g.proposal_id)
                        OR (a.proposal_number IS NOT NULL AND a.proposal_number <> '' AND a.proposal_number = p.proposal_number)
                      )
                   )
            ");
        } catch (Throwable $e) {
            error_log('Assignment stale record reset failed for ' . $table . ': ' . $e->getMessage());
        }
    }

    $defenseTables = [
        'research_defense_schedules',
        'research_defense_schedule',
        'research_defenses',
        'defense_schedules',
    ];
    $completedStatuses = array_map([$pdo, 'quote'], [
        'completed',
        'complete',
        'passed',
        'done',
        'finished',
        'closed',
    ]);

    foreach ($defenseTables as $defenseTable) {
        try {
            $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($defenseTable))->fetchColumn();
            if (!$tableExists) {
                continue;
            }

            $columns = $pdo->query("SHOW COLUMNS FROM `{$defenseTable}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $columnSet = array_fill_keys(array_map('strtolower', $columns), true);
            $statusColumn = null;
            foreach (['status', 'defense_status', 'result', 'defense_result', 'outcome'] as $candidate) {
                if (isset($columnSet[$candidate])) {
                    $statusColumn = $candidate;
                    break;
                }
            }
            if (!$statusColumn) {
                continue;
            }

            $matchParts = [];
            if (isset($columnSet['research_group_id'])) {
                $matchParts[] = '(a.research_group_id IS NOT NULL AND a.research_group_id = d.`research_group_id`)';
            }
            if (isset($columnSet['group_number'])) {
                $matchParts[] = '(a.group_number IS NOT NULL AND a.group_number <> "" AND a.group_number = d.`group_number`)';
            }
            if (isset($columnSet['proposal_id'])) {
                $matchParts[] = '(a.proposal_id IS NOT NULL AND a.proposal_id = d.`proposal_id`)';
            }
            if (isset($columnSet['proposal_number'])) {
                $matchParts[] = '(a.proposal_number IS NOT NULL AND a.proposal_number <> "" AND a.proposal_number = d.`proposal_number`)';
            }
            if (!$matchParts) {
                continue;
            }

            $matchesCompletedDefense = "
                EXISTS (
                    SELECT 1
                    FROM `{$defenseTable}` d
                    WHERE LOWER(TRIM(CAST(d.`{$statusColumn}` AS CHAR))) IN (" . implode(',', $completedStatuses) . ")
                      AND (" . implode(' OR ', $matchParts) . ")
                )
            ";

            foreach (['research_adviser_assignments', 'research_panel_assignments'] as $assignmentTable) {
                $pdo->exec("
                    UPDATE {$assignmentTable} a
                       SET a.assignment_status = 'Pending',
                           a.assigned_by = NULL,
                           a.assigned_at = NULL,
                           a.notification_sent_at = NULL,
                           a.notification_sent_by = NULL,
                           a.updated_at = NOW()
                     WHERE a.assignment_status = 'Assigned'
                       AND {$matchesCompletedDefense}
                ");
            }
        } catch (Throwable $e) {
            error_log('Assignment defense completion reset skipped for ' . $defenseTable . ': ' . $e->getMessage());
        }
    }
}

function rcAssignmentRows(PDO $pdo, string $kind): array
{
    $queries = [];

    if ($kind === 'adviser' || $kind === 'all') {
        $queries[] = "
            SELECT
                'adviser' AS assignment_kind,
                a.id AS assignment_id,
                a.adviser_name AS assignee_name,
                a.adviser_email AS assignee_email,
                '' AS assignee_role,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.notes,
                a.assigned_at,
                a.updated_at,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
                COALESCE(NULLIF(g.college_dept, ''), p.college_department) AS college_dept,
                p.proposal_number
             FROM research_adviser_assignments a
             INNER JOIN research_groups g ON g.id = a.research_group_id
                OR CONVERT(g.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
             INNER JOIN research_proposals p ON p.id = COALESCE(a.proposal_id, g.proposal_id)
             WHERE p.status = 'Approved'
               AND p.registration_status = 'Registered'
               AND p.proposal_number IS NOT NULL
               AND g.group_number IS NOT NULL
               AND g.group_number <> ''
        ";
    }

    if ($kind === 'panel' || $kind === 'all') {
        $queries[] = "
            SELECT
                'panel' AS assignment_kind,
                a.id AS assignment_id,
                a.panel_name AS assignee_name,
                a.panel_email AS assignee_email,
                a.panel_role AS assignee_role,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.notes,
                a.assigned_at,
                a.updated_at,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
                COALESCE(NULLIF(g.college_dept, ''), p.college_department) AS college_dept,
                p.proposal_number
             FROM research_panel_assignments a
             INNER JOIN research_groups g ON g.id = a.research_group_id
                OR CONVERT(g.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
             INNER JOIN research_proposals p ON p.id = COALESCE(a.proposal_id, g.proposal_id)
             WHERE p.status = 'Approved'
               AND p.registration_status = 'Registered'
               AND p.proposal_number IS NOT NULL
               AND g.group_number IS NOT NULL
               AND g.group_number <> ''
        ";
    }

    if ($queries === []) {
        return [];
    }

    $stmt = $pdo->query(implode(' UNION ALL ', $queries) . ' ORDER BY updated_at DESC, assignment_id DESC');
    return $stmt->fetchAll() ?: [];
}

function rcAssignmentRequiredExpertise(string $title): string
{
    $topic = strtolower($title);
    $rules = [
        'Artificial Intelligence / Machine Learning' => ['ai', 'artificial intelligence', 'machine learning', 'openai', 'chatgpt', 'neural', 'prediction model'],
        'Systems Development / Software Engineering' => ['system', 'application', 'app ', 'web', 'mobile', 'portal', 'platform', 'software'],
        'Data Analytics / Data Analysis' => ['analytics', 'analysis', 'data', 'dashboard', 'forecast', 'statistics'],
        'Educational Technology' => ['education', 'learning', 'student', 'teacher', 'classroom', 'lms'],
        'Internet of Things / Embedded Systems' => ['iot', 'sensor', 'monitoring', 'arduino', 'embedded'],
        'Research Methods / Validation' => ['assessment', 'evaluation', 'validation', 'effectiveness', 'study'],
        'Business / Entrepreneurship' => ['business', 'marketing', 'enterprise', 'entrepreneur', 'sales'],
        'Community Development / Social Research' => ['community', 'health', 'literacy', 'social', 'barangay'],
    ];

    $matches = [];
    foreach ($rules as $expertise => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($topic, $keyword) !== false) {
                $matches[] = $expertise;
                break;
            }
        }
    }

    return $matches === [] ? 'General Research Methods' : implode(' / ', array_slice(array_unique($matches), 0, 2));
}

function rcAssignmentMatchScore(array $row, string $requiredExpertise): int
{
    $haystack = strtolower(implode(' ', [
        $row['research_title'] ?? '',
        $row['expertise'] ?? '',
        $row['notes'] ?? '',
        $row['college_dept'] ?? '',
    ]));
    $needles = preg_split('/[^a-z0-9]+/i', strtolower($requiredExpertise)) ?: [];
    $needles = array_values(array_unique(array_filter($needles, static fn($word) => strlen($word) > 2)));

    if ($needles === []) {
        return 40;
    }

    $hits = 0;
    foreach ($needles as $needle) {
        if (strpos($haystack, $needle) !== false) {
            $hits++;
        }
    }

    $score = (int) round(($hits / count($needles)) * 85);
    if (strcasecmp((string) ($row['availability_status'] ?? ''), 'Available') === 0) {
        $score += 10;
    }
    if (strcasecmp((string) ($row['assignment_status'] ?? ''), 'Assigned') === 0) {
        $score += 5;
    }

    return max(15, min(100, $score));
}

function rcAssignmentApprovedGroups(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            g.id AS research_group_id,
            g.proposal_id,
            g.proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
            COALESCE(NULLIF(g.college_dept, ''), p.college_department) AS college_dept,
            g.leader_name,
            g.leader_email,
            g.leader_contact,
            g.status AS group_status,
            p.status AS proposal_status,
            p.registration_status,
            COALESCE(g.created_at, p.registered_at, p.approved_at, p.created_at) AS updated_at
         FROM research_groups g
         INNER JOIN research_proposals p ON p.id = g.proposal_id
         WHERE p.status = 'Approved'
           AND p.registration_status = 'Registered'
           AND p.proposal_number IS NOT NULL
           AND g.group_number IS NOT NULL
           AND g.group_number <> ''
         ORDER BY updated_at DESC, g.id DESC
    ");

    $groups = $stmt->fetchAll() ?: [];
    foreach ($groups as &$group) {
        $group['required_expertise'] = rcAssignmentRequiredExpertise((string) ($group['research_title'] ?? ''));
    }
    unset($group);

    return $groups;
}

function rcAssignmentCandidateKey(string $email, string $name): string
{
    $email = strtolower(trim($email));
    if ($email !== '') {
        return 'email:' . $email;
    }

    return 'name:' . strtolower(trim($name));
}

function rcAssignmentCandidatePool(PDO $pdo, string $kind): array
{
    $table = $kind === 'panel' ? 'research_panel_assignments' : 'research_adviser_assignments';
    $nameColumn = $kind === 'panel' ? 'panel_name' : 'adviser_name';
    $emailColumn = $kind === 'panel' ? 'panel_email' : 'adviser_email';
    $roleColumn = $kind === 'panel' ? 'panel_role' : "''";

    $rows = $pdo->query("
        SELECT
            {$nameColumn} AS assignee_name,
            {$emailColumn} AS assignee_email,
            {$roleColumn} AS assignee_role,
            expertise,
            availability_status,
            notes,
            updated_at
        FROM {$table}
        WHERE {$nameColumn} <> ''
        ORDER BY updated_at DESC, id DESC
    ")->fetchAll() ?: [];

    $pool = [];
    foreach ($rows as $row) {
        $key = rcAssignmentCandidateKey((string) ($row['assignee_email'] ?? ''), (string) ($row['assignee_name'] ?? ''));
        if ($key === 'name:' || isset($pool[$key])) {
            continue;
        }
        $pool[$key] = $row;
    }

    return array_values($pool);
}

function rcAssignmentEnsureGroupCandidateRows(PDO $pdo, array $groups): void
{
    if ($groups === []) {
        return;
    }

    $advisers = rcAssignmentCandidatePool($pdo, 'adviser');
    $panels = rcAssignmentCandidatePool($pdo, 'panel');
    $adviserExists = $pdo->prepare("
        SELECT id
        FROM research_adviser_assignments
        WHERE (
                (:email_gate <> '' AND LOWER(TRIM(adviser_email)) = :email_match)
             OR (:name_gate <> '' AND LOWER(TRIM(adviser_name)) = :name_match)
        )
          AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
          )
        LIMIT 1
    ");
    $panelExists = $pdo->prepare("
        SELECT id
        FROM research_panel_assignments
        WHERE (
                (:email_gate <> '' AND LOWER(TRIM(panel_email)) = :email_match)
             OR (:name_gate <> '' AND LOWER(TRIM(panel_name)) = :name_match)
        )
          AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
          )
        LIMIT 1
    ");
    $insertAdviser = $pdo->prepare("
        INSERT INTO research_adviser_assignments
            (research_group_id, proposal_id, proposal_number, group_number, adviser_name, adviser_email, expertise, availability_status, assignment_status, notes, assigned_by, assigned_at, created_at, updated_at, notification_sent_at, notification_sent_by)
        VALUES
            (:research_group_id, :proposal_id, :proposal_number, :group_number, :adviser_name, :adviser_email, :expertise, :availability_status, 'Pending', :notes, NULL, NULL, NOW(), NOW(), NULL, NULL)
    ");
    $insertPanel = $pdo->prepare("
        INSERT INTO research_panel_assignments
            (research_group_id, proposal_id, proposal_number, group_number, panel_name, panel_email, panel_role, expertise, availability_status, assignment_status, notes, assigned_by, assigned_at, created_at, updated_at, notification_sent_at, notification_sent_by)
        VALUES
            (:research_group_id, :proposal_id, :proposal_number, :group_number, :panel_name, :panel_email, :panel_role, :expertise, :availability_status, 'Pending', :notes, NULL, NULL, NOW(), NOW(), NULL, NULL)
    ");

    foreach ($groups as $group) {
        $groupParams = [
            ':research_group_id' => (int) ($group['research_group_id'] ?? 0),
            ':proposal_id' => (int) ($group['proposal_id'] ?? 0),
            ':proposal_number' => (string) ($group['proposal_number'] ?? ''),
            ':group_number' => (string) ($group['group_number'] ?? ''),
        ];

        foreach ($advisers as $adviser) {
            $email = strtolower(trim((string) ($adviser['assignee_email'] ?? '')));
            $name = strtolower(trim((string) ($adviser['assignee_name'] ?? '')));
            $adviserExists->execute([
                ':email_gate' => $email,
                ':email_match' => $email,
                ':name_gate' => $name,
                ':name_match' => $name,
                ':research_group_id' => $groupParams[':research_group_id'],
                ':group_number' => $groupParams[':group_number'],
                ':proposal_id' => $groupParams[':proposal_id'],
            ]);
            if ($adviserExists->fetchColumn()) {
                continue;
            }
            $insertAdviser->execute($groupParams + [
                ':adviser_name' => (string) ($adviser['assignee_name'] ?? ''),
                ':adviser_email' => (string) ($adviser['assignee_email'] ?? ''),
                ':expertise' => (string) (($adviser['expertise'] ?? '') ?: 'General Research Methods'),
                ':availability_status' => (string) (($adviser['availability_status'] ?? '') ?: 'Available'),
                ':notes' => (string) ($adviser['notes'] ?? ''),
            ]);
        }

        foreach ($panels as $panel) {
            $email = strtolower(trim((string) ($panel['assignee_email'] ?? '')));
            $name = strtolower(trim((string) ($panel['assignee_name'] ?? '')));
            $panelExists->execute([
                ':email_gate' => $email,
                ':email_match' => $email,
                ':name_gate' => $name,
                ':name_match' => $name,
                ':research_group_id' => $groupParams[':research_group_id'],
                ':group_number' => $groupParams[':group_number'],
                ':proposal_id' => $groupParams[':proposal_id'],
            ]);
            if ($panelExists->fetchColumn()) {
                continue;
            }
            $insertPanel->execute($groupParams + [
                ':panel_name' => (string) ($panel['assignee_name'] ?? ''),
                ':panel_email' => (string) ($panel['assignee_email'] ?? ''),
                ':panel_role' => (string) (($panel['assignee_role'] ?? '') ?: 'Panel Member'),
                ':expertise' => (string) (($panel['expertise'] ?? '') ?: 'General Research Methods'),
                ':availability_status' => (string) (($panel['availability_status'] ?? '') ?: 'Available'),
                ':notes' => (string) ($panel['notes'] ?? ''),
            ]);
        }
    }
}

function rcAssignmentEnrichRows(array $rows): array
{
    foreach ($rows as &$row) {
        $requiredExpertise = rcAssignmentRequiredExpertise((string) ($row['research_title'] ?? ''));
        $row['required_expertise'] = $requiredExpertise;
        $row['match_score'] = rcAssignmentMatchScore($row, $requiredExpertise);
    }
    unset($row);

    usort($rows, static function (array $a, array $b): int {
        $scoreCompare = ((int) ($b['match_score'] ?? 0)) <=> ((int) ($a['match_score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
    });

    return $rows;
}

function rcAssignmentAddRecipient(array &$recipients, array $recipient): void
{
    $key = smsNotificationRecipientKey($recipient);
    if ($key === 'role:' || isset($recipients[$key])) {
        return;
    }
    $recipients[$key] = $recipient;
}

function rcAssignmentCompletionGroup(PDO $pdo, array $candidate, string $groupNumber): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            g.id AS research_group_id,
            g.proposal_id,
            g.proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
            g.leader_name,
            g.leader_id,
            g.leader_email,
            p.rep_name,
            p.rep_id,
            p.rep_email,
            p.submitted_by_user
         FROM research_groups g
         LEFT JOIN research_proposals p ON p.id = g.proposal_id
         WHERE (:research_group_gate > 0 AND g.id = :research_group_match)
            OR (:group_number_gate <> '' AND g.group_number = :group_number_match)
            OR (:proposal_id_gate > 0 AND g.proposal_id = :proposal_id_match)
         ORDER BY g.id DESC
         LIMIT 1
    ");
    $stmt->execute([
        ':research_group_gate' => (int) ($candidate['research_group_id'] ?? 0),
        ':research_group_match' => (int) ($candidate['research_group_id'] ?? 0),
        ':group_number_gate' => $groupNumber !== '' ? $groupNumber : (string) ($candidate['group_number'] ?? ''),
        ':group_number_match' => $groupNumber !== '' ? $groupNumber : (string) ($candidate['group_number'] ?? ''),
        ':proposal_id_gate' => (int) ($candidate['proposal_id'] ?? 0),
        ':proposal_id_match' => (int) ($candidate['proposal_id'] ?? 0),
    ]);
    $group = $stmt->fetch();
    return $group ?: null;
}

function rcAssignmentGroupWhere(string $alias = 'a'): string
{
    return "(($alias.research_group_id IS NOT NULL AND $alias.research_group_id = :research_group_id)
        OR (:group_number_gate <> '' AND $alias.group_number = :group_number_match)
        OR ($alias.proposal_id IS NOT NULL AND $alias.proposal_id = :proposal_id))";
}

function rcAssignmentAssignedParties(PDO $pdo, array $group): array
{
    $params = [
        ':research_group_id' => (int) ($group['research_group_id'] ?? 0),
        ':group_number_gate' => (string) ($group['group_number'] ?? ''),
        ':group_number_match' => (string) ($group['group_number'] ?? ''),
        ':proposal_id' => (int) ($group['proposal_id'] ?? 0),
    ];

    $adviserStmt = $pdo->prepare("
        SELECT adviser_name, adviser_email
        FROM research_adviser_assignments a
        WHERE assignment_status = 'Assigned' AND " . rcAssignmentGroupWhere('a') . "
        ORDER BY assigned_at DESC, updated_at DESC, id DESC
        LIMIT 1
    ");
    $adviserStmt->execute($params);
    $adviser = $adviserStmt->fetch() ?: null;

    $panelStmt = $pdo->prepare("
        SELECT panel_name, panel_email, panel_role
        FROM research_panel_assignments a
        WHERE assignment_status = 'Assigned' AND " . rcAssignmentGroupWhere('a') . "
        ORDER BY assigned_at ASC, updated_at ASC, id ASC
    ");
    $panelStmt->execute($params);
    $panels = $panelStmt->fetchAll() ?: [];

    return ['adviser' => $adviser, 'panels' => $panels];
}

function rcAssignmentResetOtherRowsForGroup(PDO $pdo, string $table, int $keepId, array $selectedGroup): void
{
    if (!in_array($table, ['research_adviser_assignments', 'research_panel_assignments'], true) || $keepId <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE {$table}
           SET assignment_status = 'Pending',
               assigned_by = NULL,
               assigned_at = NULL,
               notification_sent_at = NULL,
               notification_sent_by = NULL,
               updated_at = NOW()
         WHERE id <> :keep_id
           AND assignment_status = 'Assigned'
           AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
           )
    ");
    $stmt->execute([
        ':keep_id' => $keepId,
        ':research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        ':group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        ':proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
    ]);
}

function rcAssignmentFindCandidateRowForGroup(PDO $pdo, string $kind, array $candidate, array $selectedGroup): ?array
{
    $table = $kind === 'panel' ? 'research_panel_assignments' : 'research_adviser_assignments';
    $nameColumn = $kind === 'panel' ? 'panel_name' : 'adviser_name';
    $emailColumn = $kind === 'panel' ? 'panel_email' : 'adviser_email';
    $email = strtolower(trim((string) ($candidate[$emailColumn] ?? '')));
    $name = strtolower(trim((string) ($candidate[$nameColumn] ?? '')));

    $stmt = $pdo->prepare("
        SELECT *
        FROM {$table}
        WHERE (
                (:email_gate <> '' AND LOWER(TRIM({$emailColumn})) = :email_match)
             OR (:name_gate <> '' AND LOWER(TRIM({$nameColumn})) = :name_match)
        )
          AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
          )
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':email_gate' => $email,
        ':email_match' => $email,
        ':name_gate' => $name,
        ':name_match' => $name,
        ':research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        ':group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        ':proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
    ]);

    $row = $stmt->fetch();
    return $row ?: null;
}

function rcAssignmentProposalStudentRecipients(PDO $pdo, array $group): array
{
    $proposalId = (int) ($group['proposal_id'] ?? 0);
    if ($proposalId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT student_id, student_name, email
            FROM proposal_members
            WHERE proposal_id = :proposal_id
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([':proposal_id' => $proposalId]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Assignment notification student member lookup failed: ' . $e->getMessage());
        return [];
    }
}

function rcAssignmentMaybeSendCompletionNotifications(PDO $pdo, array $candidate, string $groupNumber): void
{
    $group = rcAssignmentCompletionGroup($pdo, $candidate, $groupNumber);
    if (!$group) {
        return;
    }

    $parties = rcAssignmentAssignedParties($pdo, $group);
    if (!$parties['adviser'] || count($parties['panels']) < 1) {
        return;
    }

    smsDeleteStoredCradAssignmentNotifications($pdo);
}

function rcAssignmentPayload(string $kind): array
{
    try {
        $pdo = getCradDatabaseConnection();
        rcAssignmentEnsureSchema($pdo);
        rcAssignmentResetStaleAssignments($pdo);
        $groups = rcAssignmentApprovedGroups($pdo);
        rcAssignmentEnsureGroupCandidateRows($pdo, $groups);
        $rows = rcAssignmentEnrichRows(rcAssignmentRows($pdo, $kind));
    } catch (Throwable $e) {
        error_log('Research Coordinator assignment load failed: ' . $e->getMessage());
        return [
            'ok' => false,
            'error' => 'Failed to load assignment records.',
            'rows' => [],
            'groups' => [],
            'stats' => ['total' => 0, 'pending' => 0, 'available' => 0, 'assigned' => 0],
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    }

    return [
        'ok' => true,
        'rows' => $rows,
        'groups' => $groups,
        'stats' => [
            'total' => count($rows),
            'pending' => count(array_filter($rows, static fn($row) => strcasecmp((string) ($row['assignment_status'] ?? ''), 'Pending') === 0)),
            'available' => count(array_filter($rows, static fn($row) => strcasecmp((string) ($row['availability_status'] ?? ''), 'Available') === 0)),
            'assigned' => count(array_filter($rows, static fn($row) => strcasecmp((string) ($row['assignment_status'] ?? ''), 'Assigned') === 0)),
        ],
        'last_sync' => date('M j, Y g:i:s A'),
    ];
}

function rcAssignmentSave(PDO $pdo, string $kind, int $assignmentId, string $groupNumber, ?int $userId): array
{
    if ($assignmentId <= 0) {
        throw new RuntimeException('Invalid assignment record.');
    }
    if ($groupNumber === '') {
        throw new RuntimeException('Please select an approved research group first.');
    }

    $groupStmt = $pdo->prepare("
        SELECT g.id, g.proposal_id, g.proposal_number, g.group_number
        FROM research_groups g
        INNER JOIN research_proposals p ON p.id = g.proposal_id
        WHERE g.group_number = :group_number
          AND p.status = 'Approved'
          AND p.registration_status = 'Registered'
        LIMIT 1
    ");
    $groupStmt->execute([':group_number' => $groupNumber]);
    $selectedGroup = $groupStmt->fetch();
    if (!$selectedGroup) {
        throw new RuntimeException('Selected research group is not available for assignment.');
    }
    rcAssignmentEnsureGroupCandidateRows($pdo, [[
        'research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        'proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
        'proposal_number' => (string) ($selectedGroup['proposal_number'] ?? ''),
        'group_number' => (string) ($selectedGroup['group_number'] ?? ''),
    ]]);

    $matchesSelectedGroup = static function (array $candidate) use ($selectedGroup): bool {
        return ((int) ($candidate['research_group_id'] ?? 0) > 0 && (int) $candidate['research_group_id'] === (int) $selectedGroup['id'])
            || ((string) ($candidate['group_number'] ?? '') !== '' && (string) $candidate['group_number'] === (string) $selectedGroup['group_number'])
            || ((int) ($candidate['proposal_id'] ?? 0) > 0 && (int) $candidate['proposal_id'] === (int) $selectedGroup['proposal_id']);
    };

    if ($kind === 'panel') {
        $candidateStmt = $pdo->prepare("SELECT * FROM research_panel_assignments WHERE id = :id LIMIT 1");
        $candidateStmt->execute([':id' => $assignmentId]);
        $candidate = $candidateStmt->fetch();
        if (!$candidate) {
            throw new RuntimeException('Panel candidate not found.');
        }
        if (!$matchesSelectedGroup($candidate)) {
            $groupCandidate = rcAssignmentFindCandidateRowForGroup($pdo, 'panel', $candidate, $selectedGroup);
            if (!$groupCandidate) {
                throw new RuntimeException('Panel candidate was not prepared for the selected group.');
            }
            $candidate = $groupCandidate;
            $assignmentId = (int) ($candidate['id'] ?? 0);
        }
        if ($matchesSelectedGroup($candidate) && strcasecmp((string) ($candidate['assignment_status'] ?? ''), 'Assigned') === 0) {
            rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber);
            return ['message' => 'Panel member is already assigned.'];
        }

        if ($matchesSelectedGroup($candidate)) {
            $stmt = $pdo->prepare("
                UPDATE research_panel_assignments
                   SET assignment_status = 'Assigned',
                       assigned_by = :assigned_by,
                       assigned_at = NOW(),
                       notification_sent_at = NULL,
                       notification_sent_by = NULL,
                       updated_at = NOW()
                 WHERE id = :id
            ");
            $stmt->execute([
                ':assigned_by' => $userId,
                ':id' => $assignmentId,
            ]);
        }
        rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber);

        return ['message' => 'Panel member assigned successfully.'];
    }

    $candidateStmt = $pdo->prepare("SELECT * FROM research_adviser_assignments WHERE id = :id LIMIT 1");
    $candidateStmt->execute([':id' => $assignmentId]);
    $candidate = $candidateStmt->fetch();
    if (!$candidate) {
        throw new RuntimeException('Adviser candidate not found.');
    }
    if (!$matchesSelectedGroup($candidate)) {
        $groupCandidate = rcAssignmentFindCandidateRowForGroup($pdo, 'adviser', $candidate, $selectedGroup);
        if (!$groupCandidate) {
            throw new RuntimeException('Adviser candidate was not prepared for the selected group.');
        }
        $candidate = $groupCandidate;
        $assignmentId = (int) ($candidate['id'] ?? 0);
    }
    if ($matchesSelectedGroup($candidate) && strcasecmp((string) ($candidate['assignment_status'] ?? ''), 'Assigned') === 0) {
        rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber);
        return ['message' => 'Research adviser is already assigned.'];
    }

    if ($matchesSelectedGroup($candidate)) {
        rcAssignmentResetOtherRowsForGroup($pdo, 'research_adviser_assignments', $assignmentId, $selectedGroup);
        $stmt = $pdo->prepare("
            UPDATE research_adviser_assignments
               SET assignment_status = 'Assigned',
                   assigned_by = :assigned_by,
                   assigned_at = NOW(),
                   notification_sent_at = NULL,
                   notification_sent_by = NULL,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            ':assigned_by' => $userId,
            ':id' => $assignmentId,
        ]);
    }
    rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber);

    return ['message' => 'Research adviser assigned successfully.'];
}

if (($_POST['ajax'] ?? '') === 'assign') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = getCradDatabaseConnection();
        rcAssignmentEnsureSchema($pdo);
        $result = rcAssignmentSave(
            $pdo,
            $rcAssignmentKind === 'panel' ? 'panel' : 'adviser',
            (int) ($_POST['assignment_id'] ?? 0),
            trim((string) ($_POST['group_number'] ?? '')),
            getCurrentUserId()
        );
        echo json_encode(['ok' => true] + $result + rcAssignmentPayload($rcAssignmentKind));
    } catch (Throwable $e) {
        error_log('Research Coordinator assignment save failed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (($_GET['ajax'] ?? '') === 'assignments') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rcAssignmentPayload($rcAssignmentKind));
    exit;
}

function rcAssignE(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$payload = rcAssignmentPayload($rcAssignmentKind);
$rows = $payload['rows'];
$groups = $payload['groups'];
$stats = $payload['stats'];
$pageTitle = $pageConfig['title'];
$activeModule = 'crad';
$activePage = $rcPageSlug;
$breadcrumbs = [
    ['label' => 'Research Coordinator', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => $pageTitle, 'url' => null],
];

$endpoint = BASE_URL . '/modules/crad/pages/' . $rcPageSlug . '.php?ajax=assignments';
$emptyText = $rcAssignmentKind === 'panel'
    ? 'No panel assignment records yet.'
    : ($rcAssignmentKind === 'adviser' ? 'No adviser assignment records yet.' : 'No adviser or panel assignment records yet.');
$isAvailabilityPage = in_array($rcPageSlug, ['adviser-availability', 'panel-availability'], true);
$isAssignPage = in_array($rcPageSlug, ['assign-research-adviser', 'assign-panel-members'], true);
$isPanelPage = $rcAssignmentKind === 'panel';
$processTitle = $isAssignPage
    ? ($isPanelPage ? 'Assign Panel Members' : 'Assign Research Adviser')
    : ($isAvailabilityPage
    ? ($isPanelPage ? 'Check Availability - Panel' : 'Check Availability - Adviser')
    : 'Contact Based on Expertise');
$processLead = $isAssignPage
    ? 'Save the selected qualified faculty to the approved research group'
    : ($isAvailabilityPage
    ? 'Live availability from existing assignments and saved schedule status'
    : 'Live from approved research groups and ' . $pageConfig['lead'] . ' records');
$processEmpty = $isAssignPage
    ? 'No available assignment candidates for this approved group yet.'
    : ($isAvailabilityPage
    ? 'No availability records for this approved group yet.'
    : 'No matching records for this approved group yet.');
$secondStepIcon = $isAssignPage || $isAvailabilityPage ? 'fa-briefcase' : 'fa-brain';
$secondStepTitle = $isAssignPage || $isAvailabilityPage ? 'Assignments' : 'Expertise';
$secondStepText = $isAssignPage ? 'Choose qualified faculty.' : ($isAvailabilityPage ? 'Review saved workload.' : 'Detect required field.');
$thirdStepIcon = $isAssignPage ? 'fa-hand-pointer' : ($isAvailabilityPage ? 'fa-calendar-check' : 'fa-envelope');
$thirdStepTitle = $isAssignPage ? 'Assign' : ($isAvailabilityPage ? 'Availability' : 'Contact');
$thirdStepText = $isAssignPage ? 'Save actual coordinator action.' : ($isAvailabilityPage ? 'Check existing assignment load.' : 'Pick matching available faculty.');
$assigneeLabel = $isPanelPage ? 'Panel Members' : 'Advisers';
$resultsTitle = $isAssignPage ? 'Ready to Assign' : ($isAvailabilityPage ? 'Availability Results' : 'Recommended ' . $assigneeLabel);

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<style>
.rcas-wrap { display: flex; flex-direction: column; gap: 1rem; }
.rcas-header,
.rcas-stat,
.rcas-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcas-header { display: flex; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; }
.rcas-header h1 { margin: 0; color: var(--sms-heading); font-size: 1.22rem; font-weight: 850; }
.rcas-header p { margin: 0.25rem 0 0; color: var(--sms-text-muted); font-size: 0.86rem; }
.rcas-sync { color: #2563eb; font-size: 0.78rem; font-weight: 850; white-space: nowrap; }
.rcas-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; }
.rcas-stat { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 0.95rem; }
.rcas-stat i { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.12); }
.rcas-stat strong { display: block; color: var(--sms-heading); font-size: 1.25rem; line-height: 1; }
.rcas-stat span { display: block; margin-top: 0.22rem; color: var(--sms-text-muted); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; }
.rcas-card { overflow: hidden; }
.rcas-card-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.9rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-card-head h2 { margin: 0; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; }
.rcas-search { width: min(330px, 100%); min-height: 36px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: 0.45rem 0.7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: 0.84rem; }
.rcas-process { display: grid; grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.25fr); gap: 1rem; padding: 1rem; background: linear-gradient(180deg, rgba(37,99,235,0.035), transparent 42%); }
.rcas-context-panel,
.rcas-results-panel { min-height: 100%; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); }
.rcas-context-panel { padding: 0.9rem; }
.rcas-results-panel { display: flex; flex-direction: column; overflow: hidden; }
.rcas-panel-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.8rem 0.9rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-panel-title h3 { margin: 0; color: var(--sms-heading); font-size: 0.86rem; font-weight: 900; }
.rcas-count { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 26px; padding: 0 0.55rem; border-radius: 999px; color: #1d4ed8; background: rgba(37,99,235,0.12); font-size: 0.75rem; font-weight: 950; }
.rcas-field { display: flex; flex-direction: column; gap: 0.45rem; }
.rcas-field label { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 850; text-transform: uppercase; }
.rcas-select { width: 100%; min-height: 40px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: 0.45rem 0.7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: 0.86rem; }
.rcas-topic { margin-top: 0.75rem; padding: 0.85rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-muted, #f8fafc); }
.rcas-topic strong { display: block; color: var(--sms-heading); font-size: 0.9rem; line-height: 1.35; }
.rcas-topic span { display: block; margin-top: 0.3rem; color: var(--sms-text-muted); font-size: 0.78rem; }
.rcas-flow { display: grid; grid-template-columns: 1fr; gap: 0.55rem; margin-top: 0.85rem; }
.rcas-flow-step { display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.65rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: #fff; }
.rcas-flow-step i { color: #2563eb; margin-right: 0.35rem; }
.rcas-flow-step strong { display: block; color: var(--sms-heading); font-size: 0.78rem; }
.rcas-flow-step span { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.7rem; line-height: 1.35; }
.rcas-match-list { display: grid; gap: 0.7rem; padding: 0.8rem; }
.rcas-match { position: relative; display: grid; grid-template-columns: 1fr auto; gap: 0.9rem; align-items: center; padding: 0.85rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); box-shadow: 0 1px 0 rgba(15,23,42,0.03); }
.rcas-match:hover { border-color: rgba(37,99,235,0.36); box-shadow: 0 8px 20px rgba(15,23,42,0.07); }
.rcas-score { min-width: 66px; padding: 0.45rem 0.55rem; border-radius: 8px; text-align: center; color: #047857; background: #d1fae5; font-weight: 950; }
.rcas-score.busy { color: #b45309; }
.rcas-load { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; border-radius: 999px; color: #334155; background: #e2e8f0; font-size: 0.74rem; font-weight: 900; }
.rcas-score small { display: block; color: var(--sms-text-muted); font-size: 0.62rem; text-transform: uppercase; }
.rcas-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.5rem; }
.rcas-action { display: inline-flex; align-items: center; gap: 0.35rem; min-height: 32px; padding: 0.35rem 0.6rem; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.08); font-size: 0.75rem; font-weight: 850; text-decoration: none; cursor: pointer; }
.rcas-action.primary { color: #fff; border-color: #1d4ed8; background: #1d4ed8; }
.rcas-action.success { color: #fff; border-color: #047857; background: #047857; }
.rcas-action[disabled] { opacity: 0.62; cursor: not-allowed; }
.rcas-notice { display: none; margin: 0 1rem 1rem; padding: 0.7rem 0.85rem; border-radius: 8px; font-size: 0.82rem; font-weight: 800; }
.rcas-notice.show { display: block; }
.rcas-notice.ok { color: #047857; background: #d1fae5; border: 1px solid #a7f3d0; }
.rcas-notice.error { color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; }
.rcas-contact-panel { position: fixed; inset: 0; z-index: 1080; display: grid; place-items: center; padding: 1rem; background: rgba(15,23,42,0.55); }
.rcas-contact-panel[hidden] { display: none; }
.rcas-contact-box { width: min(520px, 100%); border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); box-shadow: 0 22px 55px rgba(15,23,42,0.32); overflow: hidden; }
.rcas-contact-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.95rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-contact-head h3 { margin: 0; color: var(--sms-heading); font-size: 1rem; font-weight: 900; }
.rcas-contact-close { width: 34px; height: 34px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; background: transparent; color: var(--sms-heading); cursor: pointer; }
.rcas-contact-body { display: grid; gap: 0.65rem; padding: 1rem; }
.rcas-contact-row { display: grid; gap: 0.18rem; padding: 0.7rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-muted, #f8fafc); }
.rcas-contact-row span { color: var(--sms-text-muted); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; }
.rcas-contact-row strong,
.rcas-contact-row p { margin: 0; color: var(--sms-heading); font-size: 0.88rem; line-height: 1.45; }
.rcas-contact-footer { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.5rem; padding: 0.9rem 1rem; border-top: 1px solid var(--sms-border, #e2e8f0); }
.rcas-table-wrap { overflow-x: auto; }
.rcas-table { width: 100%; min-width: 1000px; border-collapse: collapse; }
.rcas-table th,
.rcas-table td { padding: 0.82rem 0.9rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); text-align: left; vertical-align: top; }
.rcas-table th { color: var(--sms-text-muted); background: var(--sms-surface-muted, #f8fafc); font-size: 0.72rem; font-weight: 850; text-transform: uppercase; }
.rcas-title { color: var(--sms-heading); font-weight: 850; line-height: 1.35; }
.rcas-match .rcas-title { font-size: 0.95rem; }
.rcas-muted { color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 650; }
.rcas-code,
.rcas-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.74rem; font-weight: 900; }
.rcas-code { color: #1d4ed8; background: rgba(37,99,235,0.12); }
.rcas-badge { color: #92400e; background: #fef3c7; }
.rcas-badge.assigned,
.rcas-badge.available { color: #047857; background: #d1fae5; }
.rcas-badge.unavailable { color: #991b1b; background: #fee2e2; }
.rcas-empty { padding: 2rem 1rem; text-align: center; color: var(--sms-text-muted); font-weight: 750; }
.rcas-error { padding: 0.8rem 1rem; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; color: #991b1b; font-weight: 750; }
[data-theme="dark"] .rcas-header,
[data-theme="dark"] .rcas-stat,
[data-theme="dark"] .rcas-card { background: rgba(15,23,42,0.74); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-card-head,
[data-theme="dark"] .rcas-table th,
[data-theme="dark"] .rcas-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-table th,
[data-theme="dark"] .rcas-search,
[data-theme="dark"] .rcas-select,
[data-theme="dark"] .rcas-topic { background: rgba(148,163,184,0.07); }
[data-theme="dark"] .rcas-context-panel,
[data-theme="dark"] .rcas-results-panel { background: rgba(15,23,42,0.58); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-panel-title { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-flow-step,
[data-theme="dark"] .rcas-match,
[data-theme="dark"] .rcas-contact-box { background: rgba(15,23,42,0.96); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-contact-head,
[data-theme="dark"] .rcas-contact-footer,
[data-theme="dark"] .rcas-contact-row { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-contact-row { background: rgba(148,163,184,0.07); }
@media (max-width: 767.98px) {
    .rcas-header,
    .rcas-card-head { flex-direction: column; align-items: flex-start; }
    .rcas-stats { grid-template-columns: 1fr; }
    .rcas-process,
    .rcas-flow { grid-template-columns: 1fr; }
    .rcas-match { grid-template-columns: 1fr; }
    .rcas-sync,
    .rcas-search { width: 100%; }
}
</style>

<div class="rcas-wrap" data-rcas-endpoint="<?= rcAssignE($endpoint) ?>" data-rcas-mode="<?= $isAssignPage ? 'assign' : ($isAvailabilityPage ? 'availability' : 'contact') ?>">
    <?php if (!$payload['ok']): ?>
        <div class="rcas-error"><i class="fas fa-exclamation-circle me-1"></i><?= rcAssignE((string) $payload['error']) ?></div>
    <?php endif; ?>

    <header class="rcas-header">
        <div>
            <h1><i class="fas fa-user-check me-2"></i><?= rcAssignE($pageTitle) ?></h1>
            <p><?= rcAssignE($pageConfig['lead']) ?> records for approved research groups.</p>
        </div>
        <div class="rcas-sync" id="rcasLastSync">Synced <?= rcAssignE((string) $payload['last_sync']) ?></div>
    </header>

    <div class="rcas-stats">
        <div class="rcas-stat"><i class="fas fa-list"></i><div><strong id="rcasTotal"><?= (int) $stats['total'] ?></strong><span>Total</span></div></div>
        <div class="rcas-stat"><i class="fas fa-clock"></i><div><strong id="rcasPending"><?= (int) $stats['pending'] ?></strong><span>Pending</span></div></div>
        <div class="rcas-stat"><i class="fas fa-calendar-check"></i><div><strong id="rcasAvailable"><?= (int) $stats['available'] ?></strong><span>Available</span></div></div>
        <div class="rcas-stat"><i class="fas fa-check-circle"></i><div><strong id="rcasAssigned"><?= (int) $stats['assigned'] ?></strong><span>Assigned</span></div></div>
    </div>

    <section class="rcas-card">
        <div class="rcas-card-head">
            <h2><?= rcAssignE($processTitle) ?></h2>
        </div>
        <div class="rcas-process">
            <div class="rcas-context-panel">
                <div class="rcas-panel-title">
                    <h3><i class="fas fa-flask me-2"></i>Selected Research</h3>
                </div>
                <div class="rcas-field">
                    <label for="rcasGroupSelect">Approved Research Group</label>
                    <select id="rcasGroupSelect" class="rcas-select"></select>
                </div>
                <div class="rcas-topic">
                    <strong id="rcasTopic">No approved group selected</strong>
                    <span id="rcasRequired">Required expertise will appear here.</span>
                </div>
                <div class="rcas-flow">
                    <div class="rcas-flow-step"><strong><i class="fas fa-flask"></i>Topic</strong><span>Read approved title.</span></div>
                    <div class="rcas-flow-step"><strong><i class="fas <?= rcAssignE($secondStepIcon) ?>"></i><?= rcAssignE($secondStepTitle) ?></strong><span><?= rcAssignE($secondStepText) ?></span></div>
                    <div class="rcas-flow-step"><strong><i class="fas <?= rcAssignE($thirdStepIcon) ?>"></i><?= rcAssignE($thirdStepTitle) ?></strong><span><?= rcAssignE($thirdStepText) ?></span></div>
                </div>
            </div>
            <div class="rcas-results-panel">
                <div class="rcas-panel-title">
                    <h3><i class="fas fa-user-check me-2"></i><?= rcAssignE($resultsTitle) ?></h3>
                    <span class="rcas-count" id="rcasMatchCount">0</span>
                </div>
                <div class="rcas-notice" id="rcasAssignNotice"></div>
                <div class="rcas-match-list" id="rcasMatchList"></div>
                <div class="rcas-empty" id="rcasMatchEmpty" hidden><?= rcAssignE($processEmpty) ?></div>
            </div>
        </div>
    </section>

    <section class="rcas-card">
        <div class="rcas-card-head">
            <h2><?= rcAssignE($pageConfig['lead']) ?></h2>
            <input id="rcasSearch" class="rcas-search" type="search" placeholder="Search group, title, assignee, expertise...">
        </div>
        <div class="rcas-table-wrap">
            <table class="rcas-table">
                <thead>
                    <tr>
                        <th>Research Group / Title</th>
                        <th>Type</th>
                        <th>Assignee</th>
                        <th>Expertise</th>
                        <th>Availability</th>
                        <th>Assignment</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody id="rcasRows"></tbody>
            </table>
        </div>
        <div class="rcas-empty" id="rcasEmpty" hidden><?= rcAssignE($emptyText) ?></div>
    </section>
</div>

<div class="rcas-contact-panel" id="rcasContactPanel" hidden>
    <div class="rcas-contact-box" role="dialog" aria-modal="true" aria-labelledby="rcasContactTitle">
        <div class="rcas-contact-head">
            <h3 id="rcasContactTitle"><i class="fas fa-address-card me-2"></i>Contact Details</h3>
            <button type="button" class="rcas-contact-close" id="rcasContactClose" aria-label="Close contact details">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="rcas-contact-body">
            <div class="rcas-contact-row"><span>Name</span><strong id="rcasContactName"></strong></div>
            <div class="rcas-contact-row"><span>Email</span><strong id="rcasContactEmail"></strong></div>
            <div class="rcas-contact-row"><span>Role</span><strong id="rcasContactRole"></strong></div>
            <div class="rcas-contact-row"><span>Expertise</span><p id="rcasContactExpertise"></p></div>
            <div class="rcas-contact-row"><span>Research Title</span><p id="rcasContactResearch"></p></div>
            <div class="rcas-contact-row"><span>Message Draft</span><p id="rcasContactMessage"></p></div>
        </div>
        <div class="rcas-contact-footer">
            <button type="button" class="rcas-action" id="rcasCopyEmail"><i class="fas fa-copy"></i>Copy Email</button>
            <button type="button" class="rcas-action primary" id="rcasCopyMessage"><i class="fas fa-clipboard"></i>Copy Message</button>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-rcas-endpoint]');
    if (!root) return;

    const endpoint = root.dataset.rcasEndpoint;
    const mode = root.dataset.rcasMode || 'contact';
    const rowsBody = document.getElementById('rcasRows');
    const empty = document.getElementById('rcasEmpty');
    const search = document.getElementById('rcasSearch');
    const lastSync = document.getElementById('rcasLastSync');
    const total = document.getElementById('rcasTotal');
    const pending = document.getElementById('rcasPending');
    const available = document.getElementById('rcasAvailable');
    const assigned = document.getElementById('rcasAssigned');
    const groupSelect = document.getElementById('rcasGroupSelect');
    const topic = document.getElementById('rcasTopic');
    const required = document.getElementById('rcasRequired');
    const matchList = document.getElementById('rcasMatchList');
    const matchEmpty = document.getElementById('rcasMatchEmpty');
    const matchCount = document.getElementById('rcasMatchCount');
    const assignNotice = document.getElementById('rcasAssignNotice');
    const contactPanel = document.getElementById('rcasContactPanel');
    const contactClose = document.getElementById('rcasContactClose');
    const contactName = document.getElementById('rcasContactName');
    const contactEmail = document.getElementById('rcasContactEmail');
    const contactRole = document.getElementById('rcasContactRole');
    const contactExpertise = document.getElementById('rcasContactExpertise');
    const contactResearch = document.getElementById('rcasContactResearch');
    const contactMessage = document.getElementById('rcasContactMessage');
    const copyEmail = document.getElementById('rcasCopyEmail');
    const copyMessage = document.getElementById('rcasCopyMessage');
    let rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let groups = <?= json_encode($groups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let selectedGroup = '';
    let activeContact = null;
    let refreshing = false;
    let refreshTimer = null;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);
    const attr = (value) => esc(value).replace(/`/g, '&#096;');
    const badgeClass = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const buildMessage = (row, group) => [
        `Good day ${row.assignee_name || 'Faculty'},`,
        '',
        'We would like to contact you regarding this approved research group:',
        '',
        `Title: ${group?.research_title || ''}`,
        `Required Expertise: ${group?.required_expertise || ''}`,
        `Group: ${group?.group_number || ''}`,
        '',
        'Thank you.'
    ].join('\n');
    const copyText = async (value) => {
        if (!value) return;
        try {
            await navigator.clipboard.writeText(value);
        } catch (error) {
            const temp = document.createElement('textarea');
            temp.value = value;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            temp.remove();
        }
    };
    const showNotice = (message, type = 'ok') => {
        if (!assignNotice) return;
        assignNotice.textContent = message || '';
        assignNotice.className = `rcas-notice show ${type}`;
        window.setTimeout(() => {
            assignNotice.className = 'rcas-notice';
            assignNotice.textContent = '';
        }, 3500);
    };
    const openContact = (row, group) => {
        activeContact = { row, group, message: buildMessage(row, group) };
        contactName.textContent = row.assignee_name || 'For contact';
        contactEmail.textContent = row.assignee_email || 'No email saved';
        contactRole.textContent = row.assignee_role || String(row.assignment_kind || '').toUpperCase() || 'Faculty';
        contactExpertise.textContent = row.expertise || 'No expertise saved';
        contactResearch.textContent = group?.research_title || row.research_title || 'Untitled research';
        contactMessage.textContent = activeContact.message;
        contactPanel.hidden = false;
    };
    const closeContact = () => {
        contactPanel.hidden = true;
        activeContact = null;
    };
    const formatDate = (value) => {
        if (!value) return 'Not updated';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    };
    const matches = (row, term) => !term || [
        row.assignment_kind, row.group_number, row.group_name, row.research_title,
        row.assignee_name, row.assignee_email, row.assignee_role, row.expertise,
        row.availability_status, row.assignment_status
    ].join(' ').toLowerCase().includes(term);

    const scoreForGroup = (row, group) => {
        const requiredText = `${group?.required_expertise || ''} ${group?.research_title || ''}`.toLowerCase();
        const haystack = `${row.expertise || ''} ${row.notes || ''} ${row.research_title || ''}`.toLowerCase();
        const words = [...new Set(requiredText.split(/[^a-z0-9]+/).filter((word) => word.length > 2))];
        if (!words.length) return Number(row.match_score || 40);

        const hits = words.filter((word) => haystack.includes(word)).length;
        let score = Math.round((hits / words.length) * 85);
        if (String(row.availability_status || '').toLowerCase() === 'available') score += 10;
        if (String(row.assignment_status || '').toLowerCase() === 'assigned') score += 5;
        return Math.max(15, Math.min(100, score));
    };

    const selectedGroupRows = () => rows.filter((row) => {
        const groupNo = String(row.group_number || '');
        const proposalNo = String(row.proposal_number || '');
        return selectedGroup && (groupNo === selectedGroup || proposalNo === selectedGroup);
    });
    const assigneeLoadCount = (row) => rows.filter((item) => (
        String(item.assignment_kind || '').toLowerCase() === String(row.assignment_kind || '').toLowerCase() &&
        String(item.assignee_name || '').toLowerCase() === String(row.assignee_name || '').toLowerCase() &&
        String(item.assignment_status || '').toLowerCase() !== 'cancelled'
    )).length;

    const renderGroups = () => {
        if (!groupSelect) return;
        const previous = selectedGroup || groupSelect.value;
        groupSelect.innerHTML = groups.map((group) => {
            const value = group.group_number || group.proposal_number || String(group.research_group_id || '');
            const label = `${group.group_number || group.proposal_number || 'Research Group'} - ${group.research_title || 'Untitled research'}`;
            return `<option value="${attr(value)}">${esc(label)}</option>`;
        }).join('');

        const values = Array.from(groupSelect.options).map((option) => option.value);
        selectedGroup = values.includes(previous) ? previous : (values[0] || '');
        groupSelect.value = selectedGroup;
    };

    const renderProcess = () => {
        renderGroups();

        const group = groups.find((item) => {
            const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
            return value === selectedGroup;
        });
        const directMatches = selectedGroupRows();
        const sourceRows = directMatches.length ? directMatches : rows;
        const usingSelectedGroupRows = directMatches.length > 0;
        const seenAssignees = new Set();
        const matchesForGroup = sourceRows
            .map((row) => ({ ...row, match_score: scoreForGroup(row, group), selected_group_match: usingSelectedGroupRows }))
            .filter((row) => {
                const key = `${row.assignment_kind || ''}|${row.assignee_name || ''}|${row.assignee_email || ''}`.toLowerCase();
                if (seenAssignees.has(key)) return false;
                seenAssignees.add(key);
                return true;
            })
            .sort((a, b) => Number(b.match_score || 0) - Number(a.match_score || 0));

        if (!group) {
            topic.textContent = 'No approved research group yet';
            required.textContent = 'Approved and registered proposals will appear here automatically.';
            matchList.innerHTML = '';
            if (matchCount) matchCount.textContent = '0';
            matchEmpty.hidden = false;
            return;
        }

        topic.textContent = group.research_title || 'Untitled research';
        required.textContent = mode === 'availability'
            ? `Availability check: ${group.group_number || group.proposal_number || 'Approved group'}`
            : `Required Expertise: ${group.required_expertise || 'General Research Methods'}`;
        matchList.innerHTML = matchesForGroup.map((row) => {
            const email = String(row.assignee_email || '').trim();
            const sourceNote = directMatches.length ? '' : '<div class="rcas-muted">Recommended from assignment database</div>';
            const encoded = attr(JSON.stringify(row));
            const loadCount = assigneeLoadCount(row);
            if (mode === 'assign') {
                const isAssigned = Boolean(row.selected_group_match) && String(row.assignment_status || '').toLowerCase() === 'assigned';
                return `
                    <article class="rcas-match">
                        <div>
                            <div class="rcas-title">${esc(row.assignee_name || 'Faculty')}</div>
                            <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                            <div>${esc(row.expertise || 'No expertise saved')}</div>
                            ${sourceNote}
                            <div class="rcas-actions">
                                <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                                <span class="rcas-load"><i class="fas fa-briefcase"></i>${esc(loadCount)} current record${loadCount === 1 ? '' : 's'}</span>
                                <button type="button" class="rcas-action success" data-assign-id="${esc(row.assignment_id || '')}" ${isAssigned ? 'disabled' : ''}>
                                    <i class="fas fa-hand-pointer"></i>${isAssigned ? 'Assigned' : 'Assign'}
                                </button>
                            </div>
                        </div>
                        <div class="rcas-score">${esc(row.match_score || 0)}%<small>Fit</small></div>
                    </article>
                `;
            }
            if (mode === 'availability') {
                const availabilityText = row.availability_status || 'Pending';
                const isAvailable = String(availabilityText).toLowerCase() === 'available';
                return `
                    <article class="rcas-match">
                        <div>
                            <div class="rcas-title">${esc(row.assignee_name || 'Faculty')}</div>
                            <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                            <div>${esc(row.expertise || 'No expertise saved')}</div>
                            ${sourceNote}
                            <div class="rcas-actions">
                                <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                                <span class="rcas-load"><i class="fas fa-briefcase"></i>${esc(loadCount)} current record${loadCount === 1 ? '' : 's'}</span>
                                <span class="rcas-badge ${badgeClass(row.assignment_status)}">${esc(row.assignment_status || 'Pending')}</span>
                            </div>
                        </div>
                        <div class="rcas-score ${isAvailable ? '' : 'busy'}">${esc(availabilityText)}<small>Status</small></div>
                    </article>
                `;
            }
            return `
                <article class="rcas-match">
                    <div>
                        <div class="rcas-title">${esc(row.assignee_name || 'For contact')}</div>
                        <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                        <div>${esc(row.expertise || 'No expertise saved')}</div>
                        ${sourceNote}
                        <div class="rcas-actions">
                            <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                            <span class="rcas-badge ${badgeClass(row.assignment_status)}">${esc(row.assignment_status || 'Pending')}</span>
                            <button type="button" class="rcas-action primary" data-contact-row="${encoded}"><i class="fas fa-address-card"></i>View Contact</button>
                            ${email ? `<button type="button" class="rcas-action" data-copy-email="${attr(email)}"><i class="fas fa-copy"></i>Copy Email</button>` : ''}
                        </div>
                    </div>
                    <div class="rcas-score">${esc(row.match_score || 0)}%<small>Match</small></div>
                </article>
            `;
        }).join('');
        if (matchCount) matchCount.textContent = String(matchesForGroup.length);
        matchEmpty.hidden = matchesForGroup.length !== 0;
    };

    const render = () => {
        const term = (search?.value || '').trim().toLowerCase();
        const visibleRows = rows.filter((row) => matches(row, term));
        rowsBody.innerHTML = visibleRows.map((row) => `
            <tr>
                <td>
                    <div class="rcas-title">${esc(row.group_name || row.group_number || 'Research Group')}</div>
                    <div>${esc(row.research_title || '')}</div>
                    <div class="rcas-muted">${esc(row.group_number || '')} ${row.proposal_number ? '&middot; ' + esc(row.proposal_number) : ''}</div>
                </td>
                <td><span class="rcas-code">${esc(String(row.assignment_kind || '').toUpperCase())}</span></td>
                <td>
                    <div class="rcas-title">${esc(row.assignee_name || 'For contact')}</div>
                    <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || '')}</div>
                </td>
                <td>
                    <div>${esc(row.expertise || 'Not set')}</div>
                    <div class="rcas-muted">Required: ${esc(row.required_expertise || 'General Research Methods')}</div>
                </td>
                <td><span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span></td>
                <td><span class="rcas-badge ${badgeClass(row.assignment_status)}">${esc(row.assignment_status || 'Pending')}</span></td>
                <td>${esc(formatDate(row.updated_at || row.assigned_at))}</td>
            </tr>
        `).join('');
        empty.hidden = visibleRows.length !== 0;
        renderProcess();
    };

    const refresh = async () => {
        if (refreshing) return;
        refreshing = true;
        try {
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
            if (!res.ok) throw new Error('Failed to sync.');
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to sync.');
            rows = Array.isArray(data.rows) ? data.rows : [];
            groups = Array.isArray(data.groups) ? data.groups : [];
            total.textContent = data.stats?.total ?? rows.length;
            pending.textContent = data.stats?.pending ?? 0;
            available.textContent = data.stats?.available ?? 0;
            assigned.textContent = data.stats?.assigned ?? 0;
            lastSync.textContent = `Synced ${data.last_sync || 'just now'}`;
            render();
        } catch (error) {
            lastSync.textContent = 'Sync paused';
        } finally {
            refreshing = false;
        }
    };

    search?.addEventListener('input', render);
    groupSelect?.addEventListener('change', () => {
        selectedGroup = groupSelect.value;
        renderProcess();
    });
    matchList?.addEventListener('click', (event) => {
        const contactButton = event.target.closest('[data-contact-row]');
        const emailButton = event.target.closest('[data-copy-email]');
        if (contactButton) {
            try {
                openContact(JSON.parse(contactButton.dataset.contactRow || '{}'), groups.find((item) => {
                    const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
                    return value === selectedGroup;
                }));
            } catch (error) {
                return;
            }
        }
        if (emailButton) {
            copyText(emailButton.dataset.copyEmail || '');
        }
    });
    matchList?.addEventListener('click', async (event) => {
        const assignButton = event.target.closest('[data-assign-id]');
        if (!assignButton || mode !== 'assign') return;
        const group = groups.find((item) => {
            const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
            return value === selectedGroup;
        });
        if (!group?.group_number) {
            showNotice('Please select an approved research group first.', 'error');
            return;
        }

        assignButton.disabled = true;
        assignButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Saving';
        try {
            const form = new FormData();
            form.append('ajax', 'assign');
            form.append('assignment_id', assignButton.dataset.assignId || '');
            form.append('group_number', group.group_number || '');
            const res = await fetch(window.location.href, {
                method: 'POST',
                body: form,
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to assign.');
            rows = Array.isArray(data.rows) ? data.rows : rows;
            groups = Array.isArray(data.groups) ? data.groups : groups;
            total.textContent = data.stats?.total ?? rows.length;
            pending.textContent = data.stats?.pending ?? 0;
            available.textContent = data.stats?.available ?? 0;
            assigned.textContent = data.stats?.assigned ?? 0;
            lastSync.textContent = `Synced ${data.last_sync || 'just now'}`;
            showNotice(data.message || 'Assignment saved.', 'ok');
            render();
        } catch (error) {
            showNotice(error.message || 'Failed to assign.', 'error');
            renderProcess();
        }
    });
    contactClose?.addEventListener('click', closeContact);
    contactPanel?.addEventListener('click', (event) => {
        if (event.target === contactPanel) closeContact();
    });
    copyEmail?.addEventListener('click', () => copyText(activeContact?.row?.assignee_email || ''));
    copyMessage?.addEventListener('click', () => copyText(activeContact?.message || ''));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && contactPanel && !contactPanel.hidden) closeContact();
    });
    render();
    refreshTimer = window.setInterval(refresh, 5000);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (refreshTimer) window.clearInterval(refreshTimer);
            refreshTimer = null;
            return;
        }
        if (refreshTimer) window.clearInterval(refreshTimer);
        refresh();
        refreshTimer = window.setInterval(refresh, 5000);
    });
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
