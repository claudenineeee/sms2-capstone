<?php
/**
 * Notifications
 * Purpose: View and manage dynamic notifications (Attendance, Leave Requests, New Schedules)
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle    = 'Notifications';
$activeModule = 'faculty';
$activePage   = 'notification';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Notifications', 'url' => null],
];

$formError = '';
$formSuccess = '';
$notifications = [];

// Summary metrics variables
$unreadCount = 0;
$readCount = 0;
$thisWeekCount = 0;
$importantCount = 0;

try {
    $pdo = facultyDb();
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $facultyProfileId = 0;
    $userEmail = '';

    if ($userId > 0) {
        $userStmt = $pdo->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
        $userStmt->execute([':id' => $userId]);
        $userEmail = $userStmt->fetchColumn() ?: '';

        $stmt = $pdo->prepare("SELECT faculty_id FROM faculty WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $userEmail]);
        $facultyProfileId = (int) ($stmt->fetchColumn() ?: 0);
    }

    // Fetch dynamic notifications if faculty profile is valid
    if ($facultyProfileId > 0) {
        // 1. Fetch Leave Requests
        $leaveStmt = $pdo->prepare("
            SELECT id, request_ref, leave_type, status, screening_status, reason, created_at, updated_at
            FROM leave_requests
            WHERE faculty_id = :faculty_id
            ORDER BY created_at DESC
        ");
        $leaveStmt->execute([':faculty_id' => $facultyProfileId]);
        $leaveRequests = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($leaveRequests as $lr) {
            $dbId = $lr['id'];
            $notifId = 'leave_' . $dbId;

            $status = strtolower(trim($lr['status'] ?? 'pending'));
            $screening = strtolower(trim($lr['screening_status'] ?? 'pending'));
            $timestamp = $lr['updated_at'] ?? $lr['created_at'];
            $title = "Leave Request Update";
            $message = "Your leave request ({$lr['request_ref']}) status is now: " . ucwords($status);
            $icon = "fas fa-calendar-check";
            $badgeColor = "bg-success";
            $isImportant = false;

            if ($status === 'approved') {
                $title = "Leave Approved";
                $message = "Your {$lr['leave_type']} request has been approved.";
                $icon = "fas fa-check-circle";
            } elseif ($status === 'rejected') {
                $title = "Leave Rejected";
                $message = "Your {$lr['leave_type']} request has been rejected.";
                $icon = "fas fa-times-circle";
                $badgeColor = "bg-danger";
                $isImportant = true;
            } elseif ($status === 'document required' || $status === 'returned' || $screening === 'document required') {
                $title = "Document Required / Returned";
                $message = "Your leave application requires updates or supporting documents.";
                $icon = "fas fa-file-exclamation";
                $badgeColor = "bg-warning text-dark";
                $isImportant = true;
            }

            $timeAgo = timeAgo($timestamp);
            $isThisWeek = (time() - strtotime($timestamp)) <= 604800;

            $notifications[] = [
                'id' => $notifId,
                'title' => $title,
                'message' => $message,
                'time' => $timeAgo,
                'timestamp' => strtotime($timestamp),
                'icon' => $icon,
                'badge_color' => $badgeColor,
                'is_important' => $isImportant,
                'is_this_week' => $isThisWeek,
                'type' => 'Leave'
            ];
        }

        // 2. Fetch Attendance Monitoring Records
        try {
            $attStmt = $pdo->prepare("
                SELECT id, monitoring_date, status, remarks, created_at 
                FROM attendance_monitoring 
                WHERE faculty_id = :faculty_id 
                ORDER BY created_at DESC
            ");
            $attStmt->execute([':faculty_id' => $facultyProfileId]);
            $attendanceRecords = $attStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendanceRecords as $att) {
                $notifId = 'att_' . $att['id'];
                $timestamp = $att['created_at'];
                $attStatus = ucwords($att['status'] ?? 'Recorded');
                $isThisWeek = (time() - strtotime($timestamp)) <= 604800;
                
                $notifications[] = [
                    'id' => $notifId,
                    'title' => "Attendance Monitoring Update",
                    'message' => "Your attendance for {$att['monitoring_date']} was logged as: {$attStatus}.",
                    'time' => timeAgo($timestamp),
                    'timestamp' => strtotime($timestamp),
                    'icon' => "fas fa-clipboard-user",
                    'badge_color' => "bg-info",
                    'is_important' => false,
                    'is_this_week' => $isThisWeek,
                    'type' => 'Attendance'
                ];
            }
        } catch (Exception $ex) {}

        // 3. Fetch New Schedule Announcements / Assignments
        try {
            $schedStmt = $pdo->prepare("
                SELECT id, subject_code, room, day_of_week, start_time, end_time, created_at 
                FROM schedules 
                WHERE faculty_id = :faculty_id 
                ORDER BY created_at DESC
            ");
            $schedStmt->execute([':faculty_id' => $facultyProfileId]);
            $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($schedules as $sch) {
                $notifId = 'sched_' . $sch['id'];
                $timestamp = $sch['created_at'];
                $isThisWeek = (time() - strtotime($timestamp)) <= 604800;
                
                $notifications[] = [
                    'id' => $notifId,
                    'title' => "New Schedule Assigned",
                    'message' => "You have been assigned to teach {$sch['subject_code']} at Room {$sch['room']} ({$sch['day_of_week']} {$sch['start_time']}).",
                    'time' => timeAgo($timestamp),
                    'timestamp' => strtotime($timestamp),
                    'icon' => "fas fa-calendar-alt",
                    'badge_color' => "bg-primary",
                    'is_important' => true,
                    'is_this_week' => $isThisWeek,
                    'type' => 'Schedule'
                ];
            }
        } catch (Exception $ex) {}

        // Sort notifications by timestamp descending
        usort($notifications, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }

} catch (Throwable $e) {
    $formError = 'Error loading notifications: ' . $e->getMessage();
}

// Calculate summary totals safely using loops
foreach ($notifications as $n) {
    if (!empty($n['is_this_week'])) {
        $thisWeekCount++;
    }
    if (!empty($n['is_important'])) {
        $importantCount++;
    }
}

// Pagination setup
$notificationsPerPage = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$totalNotifications = count($notifications);
$totalPages = ceil($totalNotifications / $notificationsPerPage);
$startIndex = ($currentPage - 1) * $notificationsPerPage;
$paginatedNotifications = array_slice($notifications, $startIndex, $notificationsPerPage);

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-bell fs-5"></i>
            </span>
            Notifications
        </h4>
        <p class="text-secondary small mb-0">View and manage your recent faculty updates, attendance records, leave applications, and schedule alerts.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 fw-medium d-flex align-items-center gap-2" onclick="markAllRead()">
            <i class="fas fa-check-double"></i>
            <span>Mark All Read</span>
        </button>
        <button class="btn btn-outline-danger rounded-pill px-3 fw-medium d-flex align-items-center gap-2" onclick="clearAll()">
            <i class="fas fa-trash-alt"></i>
            <span>Clear All</span>
        </button>
    </div>
</div>

<!-- Summary Metrics Bar -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-primary fs-4"><i class="fas fa-envelope-open-text"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Unread Alerts</h6>
                    <h4 class="mb-0 fw-bold" id="metric-unread">0</h4>
                    <small class="text-primary fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-bell me-1"></i>New updates</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #198754; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-success fs-4"><i class="fas fa-check-circle"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Read Notifications</h6>
                    <h4 class="mb-0 fw-bold" id="metric-read">0</h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-check me-1"></i>Viewed items</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0dcaf0; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-info fs-4"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">This Week</h6>
                    <h4 class="mb-0 fw-bold"><?= $thisWeekCount ?></h4>
                    <small class="text-info fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i>Recent alerts</small>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100 bg-white">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #ffc107; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 text-warning fs-4"><i class="fas fa-star"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Important</h6>
                    <h4 class="mb-0 fw-bold"><?= $importantCount ?></h4>
                    <small class="text-warning fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-triangle-exclamation me-1"></i>Flagged items</small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Notifications List Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-stream text-primary"></i>
            All Notifications
        </h6>
    </div>
    
    <div class="card-body p-0">
        <div class="list-group list-group-flush border-0" id="notification-list-container">
            <?php if (empty($paginatedNotifications)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-bell-slash fs-2 mb-3 text-secondary opacity-50"></i>
                    <p class="mb-0 fw-medium">No notifications available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($paginatedNotifications as $n): ?>
                    <?php
                        // Force the same vivid circle color in both light and dark mode
                        $circleStyles = [
                            'bg-success'           => '#198754',
                            'bg-danger'            => '#dc3545',
                            'bg-warning text-dark' => '#ffc107',
                            'bg-warning'           => '#ffc107',
                            'bg-info'              => '#0dcaf0',
                            'bg-primary'           => '#0d6efd',
                        ];
                        $circleBg  = $circleStyles[$n['badge_color']] ?? '#6c757d';
                        // Yellow needs dark glyph; others need white
                        $iconColor = ($circleBg === '#ffc107') ? '#212529' : '#ffffff';
                    ?>
                    <div class="list-group-item px-3 py-2 border-bottom border-light-subtle d-flex align-items-center gap-2 notif-item" data-id="<?= htmlspecialchars($n['id']) ?>">
                        <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px; font-size: 14px; background-color: <?= $circleBg ?>; color: <?= $iconColor ?>;">
                            <i class="<?= htmlspecialchars($n['icon']) ?>" style="color: <?= $iconColor ?>;"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <h6 class="mb-0 fw-semibold text-dark small"><?= htmlspecialchars($n['title']) ?></h6>
                                        <span class="badge bg-primary rounded-pill unread-badge" style="font-size: 11px; display: none;">New</span>
                                        <?php if ($n['is_important']): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill" style="font-size: 11px;">High Priority</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1 text-secondary" style="font-size: 13px;"><?= htmlspecialchars($n['message']) ?></p>
                                </div>
                                <span class="small text-muted flex-shrink-0" style="font-size: 12px; white-space: nowrap;"><i class="far fa-clock me-1"></i><?= htmlspecialchars($n['time']) ?></span>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-primary rounded-pill px-2 mark-read-btn" style="font-size: 12px; padding: 4px 12px; display: none;" onclick="markRead('<?= htmlspecialchars($n['id']) ?>')">
                                    <i class="fas fa-check me-1"></i>Mark Read
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-2 border-0" style="font-size: 12px; padding: 4px 12px;" onclick="deleteNotif('<?= htmlspecialchars($n['id']) ?>')">
                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const userId = <?= $userId ?>;
const readKey = `faculty_read_notifs_${userId}`;
const deletedKey = `faculty_deleted_notifs_${userId}`;
const markAllKey = `faculty_mark_all_${userId}`;
const clearAllKey = `faculty_clear_all_${userId}`;

function getStorage(key, defaultValue = []) {
    try {
        const val = localStorage.getItem(key);
        return val ? JSON.parse(val) : defaultValue;
    } catch(e) { return defaultValue; }
}

function setStorage(key, val) {
    try {
        localStorage.setItem(key, JSON.stringify(val));
    } catch(e) {}
}

document.addEventListener("DOMContentLoaded", function() {
    const allItems = document.querySelectorAll('.notif-item');
    const deleted = getStorage(deletedKey, []);
    const read = getStorage(readKey, []);
    const markAll = localStorage.getItem(markAllKey) === 'true';
    const clearAllState = localStorage.getItem(clearAllKey) === 'true';

    if (clearAllState) {
        document.getElementById('notification-list-container').innerHTML = `
            <div class="p-5 text-center text-muted">
                <i class="fas fa-bell-slash fs-2 mb-3 text-secondary opacity-50"></i>
                <p class="mb-0 fw-medium">No notifications available.</p>
            </div>`;
        document.getElementById('metric-unread').innerText = 0;
        document.getElementById('metric-read').innerText = 0;
        return;
    }

    let unreadCount = 0;
    let readCount = 0;

    allItems.forEach(item => {
        const id = item.getAttribute('data-id');
        
        if (deleted.includes(id)) {
            item.remove();
            return;
        }

        const isRead = markAll || read.includes(id);
        const unreadBadge = item.querySelector('.unread-badge');
        const markReadBtn = item.querySelector('.mark-read-btn');

        if (isRead) {
            item.classList.remove('bg-primary', 'bg-opacity-10');
            if (unreadBadge) unreadBadge.style.display = 'none';
            if (markReadBtn) markReadBtn.style.display = 'none';
            readCount++;
        } else {
            item.classList.add('bg-primary', 'bg-opacity-10');
            if (unreadBadge) unreadBadge.style.display = 'inline-block';
            if (markReadBtn) markReadBtn.style.display = 'inline-block';
            unreadCount++;
        }
    });

    document.getElementById('metric-unread').innerText = unreadCount;
    document.getElementById('metric-read').innerText = readCount;
});

function markRead(id) {
    let read = getStorage(readKey, []);
    if (!read.includes(id)) {
        read.push(id);
        setStorage(readKey, read);
    }
    location.reload();
}

function markAllRead() {
    if(confirm('Mark all notifications as read?')) {
        localStorage.setItem(markAllKey, 'true');
        location.reload();
    }
}

function deleteNotif(id) {
    if(confirm('Delete this notification?')) {
        let deleted = getStorage(deletedKey, []);
        if (!deleted.includes(id)) {
            deleted.push(id);
            setStorage(deletedKey, deleted);
        }
        location.reload();
    }
}

function clearAll() {
    if(confirm('Clear all notifications? This action cannot be undone.')) {
        localStorage.setItem(clearAllKey, 'true');
        location.reload();
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>