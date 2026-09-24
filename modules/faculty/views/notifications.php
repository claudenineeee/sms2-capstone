<?php
/**
 * SMS 2 — Faculty-scoped notification builder
 * Location: modules/faculty/views/notifications.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../controllers/faculty-data.php';

/* =========================================================
   Read / Delete state persistence (session + DB)
   ========================================================= */

if (!function_exists('smsUserNotificationCacheKey')) {
    function smsUserNotificationCacheKey(int $userId, string $bucket): string
    {
        return 'sms_notif_' . $bucket . '_' . $userId;
    }
}

if (!function_exists('smsResolveFacultyId')) {
    function smsResolveFacultyId(int $userId): int
    {
        static $cache = [];
        if (isset($cache[$userId])) return $cache[$userId];

        $pdo = facultyDb();
        if (!$pdo) return $cache[$userId] = 0;

        $u = $pdo->prepare("SELECT email FROM sms2_db.users WHERE id = :id LIMIT 1");
        $u->execute([':id' => $userId]);
        $email = (string)($u->fetchColumn() ?: '');
        if ($email === '') return $cache[$userId] = 0;

        $f = $pdo->prepare("SELECT faculty_id FROM faculty_db.faculty WHERE email = :e LIMIT 1");
        $f->execute([':e' => $email]);
        return $cache[$userId] = (int)($f->fetchColumn() ?: 0);
    }
}

if (!function_exists('smsTimeAgo')) {
    function smsTimeAgo(?string $datetime): string
    {
        if (!$datetime) return '';
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60)     return 'Just now';
        if ($diff < 3600)   return floor($diff / 60) . 'm ago';
        if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $ts);
    }
}

if (!function_exists('smsMarkNotificationRead')) {
    function smsMarkNotificationRead(int $userId, string $notifId): void
    {
        if ($userId <= 0 || $notifId === '') return;

        $key = smsUserNotificationCacheKey($userId, 'read');
        $read = $_SESSION[$key] ?? [];
        if (!is_array($read)) $read = [];
        if (!in_array($notifId, $read, true)) {
            $read[] = $notifId;
            $_SESSION[$key] = $read;
        }

        if (preg_match('/^notif-(\d+)$/', $notifId, $m)) {
            try {
                $pdo = facultyDb();
                if ($pdo) {
                    $fid = smsResolveFacultyId($userId);
                    $stmt = $pdo->prepare("
                        UPDATE faculty_db.notifications
                        SET is_read = 1
                        WHERE notification_id = :id AND faculty_id = :fid
                    ");
                    $stmt->execute([':id' => (int)$m[1], ':fid' => $fid]);
                }
            } catch (Throwable $e) {
                error_log('[smsMarkNotificationRead] ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('smsMarkAllNotificationsRead')) {
    function smsMarkAllNotificationsRead(int $userId): void
    {
        if ($userId <= 0) return;

        $_SESSION[smsUserNotificationCacheKey($userId, 'read')] = [];

        try {
            $pdo = facultyDb();
            if ($pdo) {
                $fid = smsResolveFacultyId($userId);
                if ($fid > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE faculty_db.notifications
                        SET is_read = 1
                        WHERE faculty_id = :fid AND is_read = 0
                    ");
                    $stmt->execute([':fid' => $fid]);
                }
            }
        } catch (Throwable $e) {
            error_log('[smsMarkAllNotificationsRead] ' . $e->getMessage());
        }
    }
}

if (!function_exists('smsDeleteNotification')) {
    function smsDeleteNotification(int $userId, string $notifId): void
    {
        if ($userId <= 0 || $notifId === '') return;

        $key = smsUserNotificationCacheKey($userId, 'deleted');
        $deleted = $_SESSION[$key] ?? [];
        if (!is_array($deleted)) $deleted = [];
        if (!in_array($notifId, $deleted, true)) {
            $deleted[] = $notifId;
            $_SESSION[$key] = $deleted;
        }

        if (preg_match('/^notif-(\d+)$/', $notifId, $m)) {
            try {
                $pdo = facultyDb();
                if ($pdo) {
                    $fid = smsResolveFacultyId($userId);
                    $stmt = $pdo->prepare("
                        DELETE FROM faculty_db.notifications
                        WHERE notification_id = :id AND faculty_id = :fid
                    ");
                    $stmt->execute([':id' => (int)$m[1], ':fid' => $fid]);
                }
            } catch (Throwable $e) {
                error_log('[smsDeleteNotification] ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('smsDeleteAllNotifications')) {
    function smsDeleteAllNotifications(int $userId): void
    {
        if ($userId <= 0) return;

        $_SESSION[smsUserNotificationCacheKey($userId, 'deleted')] = ['__ALL__'];

        try {
            $pdo = facultyDb();
            if ($pdo) {
                $fid = smsResolveFacultyId($userId);
                if ($fid > 0) {
                    $stmt = $pdo->prepare("DELETE FROM faculty_db.notifications WHERE faculty_id = :fid");
                    $stmt->execute([':fid' => $fid]);
                }
            }
        } catch (Throwable $e) {
            error_log('[smsDeleteAllNotifications] ' . $e->getMessage());
        }
    }
}

if (!function_exists('smsIsNotificationDeleted')) {
    function smsIsNotificationDeleted(int $userId, string $notifId): bool
    {
        $key = smsUserNotificationCacheKey($userId, 'deleted');
        $deleted = $_SESSION[$key] ?? [];
        if (!is_array($deleted)) return false;
        if (in_array('__ALL__', $deleted, true)) return true;
        return in_array($notifId, $deleted, true);
    }
}

if (!function_exists('smsIsNotificationReadInSession')) {
    function smsIsNotificationReadInSession(int $userId, string $notifId): bool
    {
        $key = smsUserNotificationCacheKey($userId, 'read');
        $read = $_SESSION[$key] ?? [];
        return is_array($read) && in_array($notifId, $read, true);
    }
}

/* =========================================================
   POST action handler — called from navbar.php
   ========================================================= */
if (!function_exists('smsHandleNotificationAction')) {
    function smsHandleNotificationAction(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return;

        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === '') return;

        switch ($action) {
            case 'mark_read':
                smsMarkNotificationRead($userId, trim((string)($_POST['notification_id'] ?? '')));
                break;
            case 'mark_all_read':
                smsMarkAllNotificationsRead($userId);
                break;
            case 'delete_one':
                smsDeleteNotification($userId, trim((string)($_POST['notification_id'] ?? '')));
                break;
            case 'delete_all':
                smsDeleteAllNotifications($userId);
                break;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
    }
}

if (!function_exists('smsMarkNotificationFromRequest')) {
    function smsMarkNotificationFromRequest(): void
    {
        $id = (int)($_GET['notification_id'] ?? 0);
        if ($id > 0) {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            smsMarkNotificationRead($userId, 'notif-' . $id);
        }
    }
}

/* =========================================================
   Payload builder — role-routed
   ========================================================= */
if (!function_exists('smsNotificationPayloadForCurrentUser')) {

    function smsNotificationPayloadForCurrentUser(): array
    {
        try {
            $role   = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if ($userId <= 0) return [];

            $items = match ($role) {
                'faculty'                                        => smsNotifsFaculty($userId),
                'secretary'                                      => smsNotifsSecretary($userId),
                'department_head', 'department-head',
                'dept_head', 'depthead'                          => smsNotifsDeptHead($userId),
                'dean'                                           => smsNotifsDean($userId),
                'hr'                                             => smsNotifsHr($userId),
                'superadmin', 'admin'                            => smsNotifsAdmin($userId),
                'monitoring_officer', 'faculty_schedule_officer' => smsNotifsFaculty($userId),
                default                                          => [],
            };

            $items = array_values(array_filter(
                $items,
                fn($it) => !smsIsNotificationDeleted($userId, (string)($it['id'] ?? ''))
            ));

            foreach ($items as &$it) {
                $id = (string)($it['id'] ?? '');
                if ($id !== '' && smsIsNotificationReadInSession($userId, $id)) {
                    $it['is_unread'] = false;
                }
            }
            unset($it);

            return $items;

        } catch (Throwable $e) {
            error_log('[notifications-helper] ' . $e->getMessage());
            return [];
        }
    }

    /* ---------- FACULTY ---------- */
    if (!function_exists('smsNotifsFaculty')) {
        function smsNotifsFaculty(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];

            $fid = smsResolveFacultyId($userId);
            if ($fid <= 0) return [];

            $items = [];

            try {
                $q = $pdo->prepare("
                    SELECT id, leave_type, status, screening_status, updated_at, created_at
                    FROM faculty_db.leave_requests
                    WHERE faculty_id = :fid
                    ORDER BY COALESCE(updated_at, created_at) DESC
                    LIMIT 6
                ");
                $q->execute([':fid' => $fid]);

                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $status = strtolower(str_replace('_', ' ', (string)$r['status']));
                    $scr    = strtolower((string)$r['screening_status']);
                    $ts     = strtotime((string)($r['updated_at'] ?? $r['created_at']));
                    $unread = ($ts && (time() - $ts) < 604800);

                    if (str_contains($status, 'document') || $scr === 'returned') {
                        $label = 'Document Required — ' . $r['leave_type'];
                        $icon  = 'fa-file-circle-exclamation';
                    } elseif ($status === 'approved') {
                        $label = 'Leave Approved — ' . $r['leave_type'];
                        $icon  = 'fa-circle-check';
                    } elseif ($status === 'rejected') {
                        $label = 'Leave Rejected — ' . $r['leave_type'];
                        $icon  = 'fa-circle-xmark';
                    } elseif ($scr === 'screened') {
                        $label = 'Leave Forwarded — ' . $r['leave_type'];
                        $icon  = 'fa-paper-plane';
                    } elseif ($status === 'pending') {
                        $label = 'Leave Submitted — ' . $r['leave_type'];
                        $icon  = 'fa-clock';
                    } else {
                        $label = 'Leave Update — ' . $r['leave_type'];
                        $icon  = 'fa-bell';
                    }

                    $items[] = [
                        'id'        => 'leave-' . (int)$r['id'],
                        'label'     => $label,
                        'preview'   => '',
                        'icon'      => $icon,
                        'url'       => BASE_URL . '/modules/faculty/views/faculty/leave-request.php',
                        'time'      => smsTimeAgo($r['updated_at'] ?? $r['created_at']),
                        'is_unread' => $unread,
                        '_ts'       => $ts ?: 0,
                    ];
                }
            } catch (Throwable $e) {
                error_log('[smsNotifsFaculty/leave] ' . $e->getMessage());
            }

            try {
                $q = $pdo->prepare("
                    SELECT notification_id, title, priority, notification_type, is_read, created_at
                    FROM faculty_db.notifications
                    WHERE faculty_id = :fid
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $q->execute([':fid' => $fid]);

                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $n) {
                    $ts     = strtotime((string)$n['created_at']);
                    $isHigh = (($n['priority'] ?? '') === 'High Priority');

                    $icon = $isHigh
                        ? 'fa-triangle-exclamation'
                        : (match ((string)$n['notification_type']) {
                            'faculty_clearance' => 'fa-clipboard-check',
                            default             => 'fa-bell',
                        });

                    $items[] = [
                        'id'        => 'notif-' . (int)$n['notification_id'],
                        'label'     => (string)$n['title'],
                        'preview'   => '',
                        'icon'      => $icon,
                        'url'       => BASE_URL . '/modules/faculty/views/faculty/my-clearance.php',
                        'time'      => smsTimeAgo($n['created_at']),
                        'is_unread' => ((int)$n['is_read'] === 0),
                        '_ts'       => $ts ?: 0,
                    ];
                }
            } catch (Throwable $e) {
                error_log('[smsNotifsFaculty/notifications] ' . $e->getMessage());
            }

            usort($items, fn($a, $b) => ($b['_ts'] ?? 0) <=> ($a['_ts'] ?? 0));
            foreach ($items as &$it) { unset($it['_ts']); }
            unset($it);

            return $items;
        }
    }

    /* ---------- SECRETARY ---------- */
    if (!function_exists('smsNotifsSecretary')) {
        function smsNotifsSecretary(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];
            $items = [];

            try {
                $q = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.leave_requests WHERE screening_status = 'Pending'");
                $q->execute();
                $pending = (int)$q->fetchColumn();
                if ($pending > 0) {
                    $items[] = [
                        'id'        => 'sec-pending-' . $pending,
                        'label'     => "$pending application" . ($pending === 1 ? '' : 's') . ' pending screening',
                        'preview'   => '', 'icon' => 'fa-file-signature',
                        'url'       => BASE_URL . '/modules/faculty/views/secretary/leave-request-screening.php',
                        'time'      => 'now', 'is_unread' => true,
                    ];
                }
            } catch (Throwable $e) {}

            return $items;
        }
    }

    /* ---------- DEPARTMENT HEAD ---------- */
    if (!function_exists('smsNotifsDeptHead')) {
        function smsNotifsDeptHead(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];
            $items = [];

            try {
                $q = $pdo->prepare("
                    SELECT COUNT(*) FROM faculty_db.leave_requests
                    WHERE screening_status = 'Screened' AND LOWER(status) = 'pending'
                ");
                $q->execute();
                $ready = (int)$q->fetchColumn();
                if ($ready > 0) {
                    $items[] = [
                        'id'        => 'dh-approval-' . $ready,
                        'label'     => "$ready request" . ($ready === 1 ? '' : 's') . ' ready for approval',
                        'preview'   => '', 'icon' => 'fa-file-circle-check',
                        'url'       => BASE_URL . '/modules/faculty/views/department-head/leave-request-approval.php',
                        'time'      => 'now', 'is_unread' => true,
                    ];
                }
            } catch (Throwable $e) {}

            return $items;
        }
    }

    /* ---------- DEAN ---------- */
    if (!function_exists('smsNotifsDean')) {
        function smsNotifsDean(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];
            $items = [];

            try {
                $q = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.faculty WHERE overall_rating > 0 AND overall_rating < 3.50");
                $q->execute();
                $below = (int)$q->fetchColumn();
                if ($below > 0) {
                    $items[] = [
                        'id'        => 'dean-low-' . $below,
                        'label'     => "$below faculty below 3.50",
                        'preview'   => '', 'icon' => 'fa-triangle-exclamation',
                        'url'       => BASE_URL . '/modules/faculty/views/department-head/faculty-performance.php',
                        'time'      => 'now', 'is_unread' => true,
                    ];
                }
            } catch (Throwable $e) {}

            return $items;
        }
    }

    /* ---------- HR ---------- */
    if (!function_exists('smsNotifsHr')) {
        function smsNotifsHr(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];
            $items = [];

            try {
                $q = $pdo->prepare("SELECT COUNT(*) FROM faculty_db.clearance_requests WHERE overall_status != 'Cleared'");
                $q->execute();
                $open = (int)$q->fetchColumn();
                if ($open > 0) {
                    $items[] = [
                        'id'        => 'hr-clearance-' . $open,
                        'label'     => "$open open clearance packet" . ($open === 1 ? '' : 's'),
                        'preview'   => '', 'icon' => 'fa-clipboard-check',
                        'url'       => BASE_URL . '/modules/faculty/views/hr/clearance.php',
                        'time'      => 'now', 'is_unread' => true,
                    ];
                }
            } catch (Throwable $e) {}

            return $items;
        }
    }

    /* ---------- ADMIN ---------- */
    if (!function_exists('smsNotifsAdmin')) {
        function smsNotifsAdmin(int $userId): array
        {
            $pdo = facultyDb();
            if (!$pdo) return [];
            $items = [];

            try {
                $q = $pdo->prepare("SELECT COUNT(*) FROM sms2_db.users WHERE status = 'pending_approval'");
                $q->execute();
                $pending = (int)$q->fetchColumn();
                if ($pending > 0) {
                    $items[] = [
                        'id'        => 'admin-pending-' . $pending,
                        'label'     => "$pending account" . ($pending === 1 ? '' : 's') . ' pending approval',
                        'preview'   => '', 'icon' => 'fa-user-clock',
                        'url'       => BASE_URL . '/account/approvals.php',
                        'time'      => 'now', 'is_unread' => true,
                    ];
                }
            } catch (Throwable $e) {}

            return $items;
        }
    }
}