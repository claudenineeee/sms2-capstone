<?php
/**
 * Shared CRAD assignment notifications.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/crad/config/config.php';

function smsAssignmentNotificationEnsureSentSchema(PDO $crad): void
{
    foreach (['research_adviser_assignments', 'research_panel_assignments'] as $table) {
        try {
            $sentAt = $crad->query("SHOW COLUMNS FROM {$table} LIKE 'notification_sent_at'")->fetch();
            if (!$sentAt) {
                $crad->exec("ALTER TABLE {$table} ADD notification_sent_at DATETIME DEFAULT NULL AFTER updated_at");
            }
            $sentBy = $crad->query("SHOW COLUMNS FROM {$table} LIKE 'notification_sent_by'")->fetch();
            if (!$sentBy) {
                $crad->exec("ALTER TABLE {$table} ADD notification_sent_by INT UNSIGNED DEFAULT NULL AFTER notification_sent_at");
            }
        } catch (Throwable $e) {
            error_log('Assignment notification sent schema check failed for ' . $table . ': ' . $e->getMessage());
        }
    }
}

function smsNotificationsEnsureSchema(?PDO $crad = null): void
{
    return;
}

function smsNotificationRecipientKey(array $recipient): string
{
    $userId = (int) ($recipient['id'] ?? $recipient['user_id'] ?? 0);
    if ($userId > 0) {
        return 'user:' . $userId;
    }

    $email = strtolower(trim((string) ($recipient['email'] ?? '')));
    if ($email !== '') {
        return 'email:' . $email;
    }

    return 'role:' . strtolower(trim((string) ($recipient['role_key'] ?? '')));
}

function smsNotificationAppUrl(string $path): string
{
    $baseUrl = defined('BASE_URL') ? (string) BASE_URL : '';
    if ($baseUrl === '' && (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
        $baseUrl = '/' . basename((string) ROOT_PATH);
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function smsNotificationViewUrl(array $params): string
{
    return smsNotificationAppUrl('/notifications/view.php?' . http_build_query($params));
}

function smsNotificationUrlForRole(string $roleKey, int $notificationId): string
{
    return smsNotificationViewUrl(['id' => $notificationId]);
}

function smsCradInsertNotification(PDO $crad, array $recipient, string $batchKey, string $title, string $body): void
{
    return;
}

function smsCurrentUserNotificationWhere(): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $role = (string) ($_SESSION['user_role_key'] ?? '');
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

    return [
        'sql' => '(recipient_user_id = :user_id
            OR (:email_gate <> "" AND recipient_email = :email_value)
            OR (:role_gate <> "" AND recipient_user_id IS NULL AND recipient_email = "" AND recipient_role = :role_value))',
        'params' => [
            ':user_id' => $userId,
            ':email_gate' => $email,
            ':email_value' => $email,
            ':role_gate' => $role,
            ':role_value' => $role,
        ],
    ];
}

function smsDeleteStoredCradAssignmentNotifications(PDO $crad): void
{
    return;
}

function smsCurrentUserNotifications(int $limit = 8): array
{
    return [];
}

function smsBackfillCradAssignmentNotifications(PDO $crad): void
{
    return;
}

function smsBackfillCradAssignmentNotificationForGroup(PDO $crad, array $group, array $users): void
{
    return;
}

function smsMarkCurrentUserNotificationRead(int $notificationId): void
{
    return;
}

function smsMarkNotificationFromRequest(): void
{
    smsMarkCurrentUserNotificationRead((int) ($_GET['assignment_notification'] ?? 0));
}

function smsCurrentUserNotificationById(int $notificationId): ?array
{
    return null;
}

function smsNotificationBodyDetails(string $body, string $title = ''): array
{
    $lines = preg_split('/\R+/', trim($body)) ?: [];
    $details = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || ($title !== '' && strcasecmp($line, $title) === 0)) {
            continue;
        }
        $parts = explode(':', $line, 2);
        $details[] = [
            'label' => trim($parts[0] ?? 'Detail'),
            'value' => trim($parts[1] ?? $line),
        ];
    }
    return $details;
}

function smsNotificationPreviewText(string $body, int $limit = 64): string
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $body) ?: [])));
    if (isset($lines[0]) && strcasecmp($lines[0], 'Assignment Notification') === 0) {
        array_shift($lines);
    }

    $preview = preg_replace('/\s+/', ' ', implode(' - ', $lines)) ?: '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($preview) > $limit ? mb_substr($preview, 0, $limit - 3) . '...' : $preview;
    }

    return strlen($preview) > $limit ? substr($preview, 0, $limit - 3) . '...' : $preview;
}

function smsRenderCurrentAssignmentNotificationPanel(): void
{
    $notification = smsCurrentUserNotificationById((int) ($_GET['assignment_notification'] ?? 0));
    if (!$notification) {
        return;
    }

    $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $created = strtotime((string) ($notification['created_at'] ?? '')) ?: time();
    $details = smsNotificationBodyDetails((string) ($notification['body'] ?? ''), (string) ($notification['title'] ?? ''));
    ?>
    <style>
        .sms-assignment-notification {
            border: 1px solid #d9e4f2;
            border-left: 4px solid #2454c6;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            margin: 0 0 1rem;
            padding: .85rem 1rem;
        }
        .sms-assignment-notification__head {
            align-items: center;
            display: flex;
            gap: .65rem;
            justify-content: space-between;
            margin-bottom: .7rem;
        }
        .sms-assignment-notification__title {
            align-items: center;
            color: #172033;
            display: flex;
            font-weight: 800;
            gap: .5rem;
            min-width: 0;
        }
        .sms-assignment-notification__time {
            color: #64748b;
            flex: 0 0 auto;
            font-size: .82rem;
            white-space: nowrap;
        }
        .sms-assignment-notification__grid {
            display: grid;
            gap: .55rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .sms-assignment-notification__item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            min-width: 0;
            padding: .55rem .65rem;
        }
        .sms-assignment-notification__label {
            color: #64748b;
            display: block;
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .sms-assignment-notification__value {
            color: #0f172a;
            display: block;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        @media (max-width: 640px) {
            .sms-assignment-notification__head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
    <section class="sms-assignment-notification" aria-label="Assignment notification details">
        <div class="sms-assignment-notification__head">
            <div class="sms-assignment-notification__title">
                <i class="fas <?= $escape($notification['icon'] ?: 'fa-user-check') ?>"></i>
                <span>Status Dashboard: <?= $escape($notification['title'] ?: 'Assignment Notification') ?></span>
            </div>
            <time class="sms-assignment-notification__time"><?= $escape(date('M j, Y h:i A', $created)) ?></time>
        </div>
        <div class="sms-assignment-notification__grid">
            <?php foreach ($details as $detail): ?>
                <div class="sms-assignment-notification__item">
                    <span class="sms-assignment-notification__label"><?= $escape($detail['label']) ?></span>
                    <span class="sms-assignment-notification__value"><?= $escape($detail['value']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function smsCurrentUserCanReceiveAssignmentNotification(PDO $crad, array $row): bool
{
    $role = (string) ($_SESSION['user_role_key'] ?? '');
    if ($role === 'crad_officer') {
        return true;
    }
    if (in_array($role, ['superadmin', 'research_coordinator'], true)) {
        return false;
    }

    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $studentId = strtolower(trim((string) ($_SESSION['student_id'] ?? '')));
    $name = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($role === 'adviser') {
        return $email !== '' && $email === strtolower(trim((string) ($row['adviser_email'] ?? '')));
    }

    if ($role === 'panel') {
        $panelEmails = array_filter(array_map(
            static fn(string $value): string => strtolower(trim($value)),
            explode(',', (string) ($row['panel_emails'] ?? ''))
        ));
        return $email !== '' && in_array($email, $panelEmails, true);
    }

    if ($role !== 'student') {
        return false;
    }

    if ($userId > 0 && $userId === (int) ($row['submitted_by_user'] ?? 0)) {
        return true;
    }
    if ($studentId !== '' && in_array($studentId, [
        strtolower(trim((string) ($row['leader_id'] ?? ''))),
        strtolower(trim((string) ($row['rep_id'] ?? ''))),
    ], true)) {
        return true;
    }
    if ($email !== '' && in_array($email, [
        strtolower(trim((string) ($row['leader_email'] ?? ''))),
        strtolower(trim((string) ($row['rep_email'] ?? ''))),
    ], true)) {
        return true;
    }
    if ($name !== '' && in_array($name, [
        strtolower(trim((string) ($row['leader_name'] ?? ''))),
        strtolower(trim((string) ($row['rep_name'] ?? ''))),
    ], true)) {
        return true;
    }

    try {
        $memberStmt = $crad->prepare("
            SELECT 1
            FROM proposal_members
            WHERE proposal_id = :proposal_id
              AND (
                (:student_id_gate <> '' AND LOWER(TRIM(student_id)) = :student_id_match)
                OR (:email_gate <> '' AND LOWER(TRIM(email)) = :email_match)
                OR (:name_gate <> '' AND LOWER(TRIM(student_name)) = :name_match)
              )
            LIMIT 1
        ");
        $memberStmt->execute([
            ':proposal_id' => (int) ($row['proposal_id'] ?? 0),
            ':student_id_gate' => $studentId,
            ':student_id_match' => $studentId,
            ':email_gate' => $email,
            ':email_match' => $email,
            ':name_gate' => $name,
            ':name_match' => $name,
        ]);
        return (bool) $memberStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Assignment notification member access check failed: ' . $e->getMessage());
        return false;
    }
}

function smsCurrentUserAssignmentNotifications(int $limit = 8): array
{
    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    try {
        smsAssignmentNotificationEnsureSentSchema($crad);
        smsDeleteStoredCradAssignmentNotifications($crad);
        $rows = $crad->query("
            SELECT
                g.id AS research_group_id,
                g.proposal_id,
                g.proposal_number,
                g.group_number,
                g.group_name,
                g.leader_id,
                g.leader_name,
                g.leader_email,
                p.rep_id,
                p.rep_name,
                p.rep_email,
                p.submitted_by_user,
                a.adviser_name,
                a.adviser_email,
                GROUP_CONCAT(pa.panel_name ORDER BY pa.assigned_at ASC, pa.updated_at ASC, pa.id ASC SEPARATOR ', ') AS panel_names,
                GROUP_CONCAT(LOWER(TRIM(pa.panel_email)) ORDER BY pa.assigned_at ASC, pa.updated_at ASC, pa.id ASC SEPARATOR ',') AS panel_emails,
                GREATEST(
                    COALESCE(a.notification_sent_at, '1000-01-01 00:00:00'),
                    COALESCE(MAX(pa.notification_sent_at), '1000-01-01 00:00:00')
                ) AS completed_at
             FROM research_groups g
             INNER JOIN research_proposals p ON p.id = g.proposal_id
             INNER JOIN research_adviser_assignments a
                ON a.assignment_status = 'Assigned'
               AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
             INNER JOIN research_panel_assignments pa
                ON pa.assignment_status = 'Assigned'
               AND (pa.research_group_id = g.id OR pa.group_number = g.group_number OR pa.proposal_id = g.proposal_id)
             WHERE a.notification_sent_at IS NOT NULL
               AND pa.notification_sent_at IS NOT NULL
               AND NOT EXISTS (
                    SELECT 1
                    FROM research_adviser_assignments pending_a
                    WHERE pending_a.assignment_status = 'Assigned'
                      AND pending_a.notification_sent_at IS NULL
                      AND (
                            pending_a.research_group_id = g.id
                         OR pending_a.group_number = g.group_number
                         OR pending_a.proposal_id = g.proposal_id
                      )
               )
               AND NOT EXISTS (
                    SELECT 1
                    FROM research_panel_assignments pending_pa
                    WHERE pending_pa.assignment_status = 'Assigned'
                      AND pending_pa.notification_sent_at IS NULL
                      AND (
                            pending_pa.research_group_id = g.id
                         OR pending_pa.group_number = g.group_number
                         OR pending_pa.proposal_id = g.proposal_id
                      )
               )
             GROUP BY
                g.id, g.proposal_id, g.proposal_number, g.group_number, g.group_name,
                g.leader_id, g.leader_name, g.leader_email,
                p.rep_id, p.rep_name, p.rep_email, p.submitted_by_user,
                a.adviser_name, a.adviser_email, a.notification_sent_at
             ORDER BY completed_at DESC, g.id DESC
             LIMIT 50
        ")->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Live assignment notification load failed: ' . $e->getMessage());
        return [];
    }

    $items = [];
    foreach ($rows as $row) {
        if (!smsCurrentUserCanReceiveAssignmentNotification($crad, $row)) {
            continue;
        }

        $created = strtotime((string) ($row['completed_at'] ?? '')) ?: time();
        $groupLabel = (string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? 'Research Group'));
        $body = "Assignment Notification\n"
            . 'Research Group: ' . $groupLabel . "\n"
            . 'Adviser: ' . (string) ($row['adviser_name'] ?? 'Research Adviser') . "\n"
            . 'Panel Members: ' . (string) ($row['panel_names'] ?? 'Panel Members') . "\n"
            . 'Date: ' . date('M j, Y', $created) . "\n"
            . 'Time: ' . date('g:i A', $created);

        $items[] = [
            'id' => 0,
            'batch_key' => 'live-assignment:' . (string) (($row['group_number'] ?? '') ?: ($row['proposal_number'] ?? $row['proposal_id'] ?? '')),
            'source_group' => (string) ($row['group_number'] ?? ''),
            'source_proposal' => (string) ($row['proposal_number'] ?? ''),
            'icon' => 'fa-user-check',
            'class' => 'text-primary',
            'label' => 'Assignment Notification',
            'body' => $body,
            'preview' => smsNotificationPreviewText($body),
            'status' => 'read',
            'is_unread' => false,
            'time' => date('M j, Y h:i A', $created),
            'url' => smsNotificationViewUrl([
                'type' => 'assignment',
                'group' => (string) ($row['group_number'] ?? ''),
                'proposal' => (string) ($row['proposal_number'] ?? ''),
            ]),
        ];
    }

    return array_slice($items, 0, max(1, min(50, $limit)));
}

function smsCurrentUserAssignmentNotificationDetail(string $groupNumber = '', string $proposalNumber = ''): ?array
{
    $groupNumber = trim($groupNumber);
    $proposalNumber = trim($proposalNumber);
    foreach (smsCurrentUserAssignmentNotifications(50) as $item) {
        if (
            ($groupNumber !== '' && hash_equals($groupNumber, (string) ($item['source_group'] ?? '')))
            || ($proposalNumber !== '' && hash_equals($proposalNumber, (string) ($item['source_proposal'] ?? '')))
        ) {
            $details = smsNotificationBodyDetails((string) ($item['body'] ?? ''), (string) ($item['label'] ?? ''));
            return [
                'title' => (string) ($item['label'] ?? 'Assignment Notification'),
                'badge' => 'Notification',
                'badge_class' => 'secondary',
                'icon' => (string) ($item['icon'] ?? 'fa-user-check'),
                'time' => (string) ($item['time'] ?? ''),
                'time_iso' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format(DateTimeInterface::ATOM),
                'details' => $details,
                'message' => '',
            ];
        }
    }
    return null;
}

function smsNotificationPayloadForCurrentUser(): array
{
    $rows = smsCurrentUserNotifications(8);
    $items = array_map(static function (array $row): array {
        $created = strtotime((string) ($row['created_at'] ?? '')) ?: time();
        $status = (string) ($row['status'] ?? 'read');
        $body = (string) ($row['body'] ?? '');
        return [
            'id' => (int) $row['id'],
            'batch_key' => (string) ($row['batch_key'] ?? ''),
            'icon' => (string) ($row['icon'] ?? 'fa-bell'),
            'class' => 'text-primary',
            'label' => (string) ($row['title'] ?? 'Notification'),
            'body' => $body,
            'preview' => smsNotificationPreviewText($body),
            'status' => $status,
            'is_unread' => $status === 'unread',
            'time' => date('M j, Y h:i A', $created),
            'url' => (string) ($row['url'] ?? '#'),
        ];
    }, $rows);

    return smsNotificationDedupe(array_merge(
        smsCurrentUserAssignmentNotifications(8),
        $items,
        smsStudentResearchStatusNotifications(),
        smsStudentReturnedProposalNotifications()
    ));
}

function smsNotificationDedupe(array $items): array
{
    $seen = [];
    $deduped = [];
    foreach ($items as $item) {
        $batchKey = trim((string) ($item['batch_key'] ?? ''));
        $key = $batchKey !== ''
            ? 'batch:' . strtolower($batchKey)
            : strtolower((string) ($item['label'] ?? '') . '|' . (string) ($item['url'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $deduped[] = $item;
    }
    return $deduped;
}

function smsStudentResearchStatusNotifications(): array
{
    if (($_SESSION['user_role_key'] ?? '') !== 'student') {
        return [];
    }

    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        $stmt = $crad->prepare(
            "SELECT p.proposal_number, p.research_title,
                    g.group_number, g.group_name, g.created_at, g.date_assigned
             FROM research_groups g
             INNER JOIN research_proposals p ON p.id = g.proposal_id
             WHERE g.group_number IS NOT NULL
               AND g.group_number <> ''
               AND (
                    (:student_id_value <> '' AND p.rep_id = :student_id_rep)
                 OR (:student_email_value <> '' AND LOWER(p.rep_email) = :student_email_rep)
                 OR (:student_name_value <> '' AND LOWER(TRIM(p.rep_name)) = :student_name_rep)
                 OR (:user_id_value > 0 AND p.submitted_by_user = :user_id_match)
               )
             ORDER BY g.date_assigned DESC, g.id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':student_id_value' => $studentId,
            ':student_id_rep' => $studentId,
            ':student_email_value' => $studentEmail,
            ':student_email_rep' => $studentEmail,
            ':student_name_value' => $studentName,
            ':student_name_rep' => $studentName,
            ':user_id_value' => $studentUserId,
            ':user_id_match' => $studentUserId,
        ]);
        $group = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('Student research status notification load failed: ' . $e->getMessage());
        return [];
    }

    if (!$group || empty($group['group_number'])) {
        return [];
    }

    $created = !empty($group['created_at'])
        ? strtotime((string) $group['created_at'])
        : (!empty($group['date_assigned']) ? strtotime((string) $group['date_assigned']) : time());

    return [[
        'id' => 0,
        'icon' => 'fa-users',
        'class' => 'text-primary',
        'label' => 'Research Approval Status',
        'body' => 'Research Group: ' . (string) $group['group_number'] . ' - ' . (string) ($group['group_name'] ?? ''),
        'preview' => 'Research Group: ' . (string) $group['group_number'] . ' - ' . (string) ($group['group_name'] ?? ''),
        'status' => 'read',
        'is_unread' => false,
        'time' => date('M j, Y h:i A', $created ?: time()),
        'url' => smsNotificationViewUrl(['type' => 'research_group']),
    ]];
}

function smsStudentReturnedProposalNotifications(): array
{
    if (($_SESSION['user_role_key'] ?? '') !== 'student') {
        return [];
    }

    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        $stmt = $crad->prepare(
            "SELECT ref_code, research_title, notes, updated_at
             FROM research_proposals
             WHERE status = 'Returned'
               AND (
                    (:student_id_value <> '' AND rep_id = :student_id_rep)
                 OR (:student_email_value <> '' AND LOWER(rep_email) = :student_email_rep)
                 OR (:student_name_value <> '' AND LOWER(TRIM(rep_name)) = :student_name_rep)
                 OR (:user_id_value > 0 AND submitted_by_user = :user_id_match)
               )
             ORDER BY updated_at DESC, id DESC
             LIMIT 5"
        );
        $stmt->execute([
            ':student_id_value' => $studentId,
            ':student_id_rep' => $studentId,
            ':student_email_value' => $studentEmail,
            ':student_email_rep' => $studentEmail,
            ':student_name_value' => $studentName,
            ':student_name_rep' => $studentName,
            ':user_id_value' => $studentUserId,
            ':user_id_match' => $studentUserId,
        ]);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Returned proposal notification load failed: ' . $e->getMessage());
        return [];
    }

    return array_map(static function (array $row): array {
        $updated = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
        return [
            'id' => 0,
            'icon' => 'fa-undo',
            'class' => 'text-danger',
            'label' => 'Proposal returned: ' . (string) ($row['ref_code'] ?? 'Research Proposal'),
            'body' => (string) ($row['research_title'] ?? ''),
            'preview' => (string) ($row['research_title'] ?? ''),
            'status' => 'read',
            'is_unread' => false,
            'time' => date('M j, Y h:i A', $updated),
            'url' => smsNotificationViewUrl([
                'type' => 'returned_proposal',
                'ref' => (string) ($row['ref_code'] ?? ''),
            ]),
        ];
    }, $rows);
}
