<?php
/**
 * Faculty Dashboard
 * Purpose: Personal dashboard for faculty member
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle    = 'Faculty Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

requireAuth();

// Resolve the logged-in account to its faculty record in the faculty database.
$facultyPdo = facultyDb();
$currentUserId = (int) (getCurrentUserId() ?? 0);
$facultyId = 0;
$facultyName = 'Faculty';
$teachingLoad = 0.0;
$classesToday = 0;
$todaySchedule = [];
$dashboardNotifications = [];
$pendingLoadAcknowledgements = 0;
$dashboardTermLabel = '';
$rating = 0;
$ratingLabel = 'No Rating';
$leaveRequests = [];

function smsFacultyDashboardDays(?string $pattern): array
{
    $normalized = strtoupper(preg_replace('/[^A-Z]/i', '', (string) $pattern));
    $days = [];
    foreach ([
        'Mon' => ['MONDAY', 'MON'], 'Tue' => ['TUESDAY', 'TUE'], 'Wed' => ['WEDNESDAY', 'WED'],
        'Thu' => ['THURSDAY', 'THU'], 'Fri' => ['FRIDAY', 'FRI'], 'Sat' => ['SATURDAY', 'SAT'], 'Sun' => ['SUNDAY', 'SUN'],
    ] as $day => $tokens) {
        foreach ($tokens as $token) {
            if (str_contains($normalized, $token)) {
                $days[] = $day;
                $normalized = str_replace($token, '', $normalized);
                break;
            }
        }
    }
    if (str_contains($normalized, 'TTH')) {
        $days[] = 'Tue';
        $days[] = 'Thu';
        $normalized = str_replace('TTH', '', $normalized);
    }
    if (str_contains($normalized, 'TH')) {
        $days[] = 'Thu';
        $normalized = str_replace('TH', '', $normalized);
    }
    foreach (str_split($normalized) as $letter) {
        $day = ['M' => 'Mon', 'T' => 'Tue', 'W' => 'Wed', 'R' => 'Thu', 'F' => 'Fri', 'S' => 'Sat', 'U' => 'Sun'][$letter] ?? null;
        if ($day !== null) {
            $days[] = $day;
        }
    }
    return array_values(array_unique($days));
}

function smsFacultyDashboardTime(?string $time): string
{
    if (!$time) {
        return 'Time not set';
    }
    $timestamp = strtotime($time);
    return $timestamp === false ? $time : date('g:i A', $timestamp);
}

if ($facultyPdo instanceof PDO && $currentUserId > 0) {
    try {
        $profileStmt = $facultyPdo->prepare("SELECT fp.first_name, fp.last_name, f.faculty_id
            FROM faculty_profiles fp
            LEFT JOIN faculty f ON f.faculty_id = (
                SELECT f2.faculty_id FROM faculty f2
                WHERE (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(fp.email) = LOWER(f2.email))
                   OR f2.faculty_no = fp.faculty_id
                ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND LOWER(fp.email) = LOWER(f2.email)) DESC
                LIMIT 1
            )
            WHERE fp.user_id = ? LIMIT 1");
        $profileStmt->execute([$currentUserId]);
        $profileData = $profileStmt->fetch(PDO::FETCH_ASSOC);
        if ($profileData) {
            $facultyId = (int) ($profileData['faculty_id'] ?? 0);
            $facultyName = trim(($profileData['first_name'] ?? '') . ' ' . ($profileData['last_name'] ?? '')) ?: 'Faculty';
        }

        $loadStmt = $facultyPdo->prepare("SELECT fr.total_units, fr.assignments_json,
                fr.finalized_at, t.academic_year, t.semester
            FROM teaching_load_final_records fr
            JOIN teaching_load_requests r ON r.load_request_id = fr.load_request_id
            JOIN academic_terms t ON t.term_id = fr.term_id
            WHERE r.faculty_user_id = ? AND r.workflow_stage = 'completed' AND r.status = 'Approved'
            ORDER BY t.is_current DESC, fr.finalized_at DESC, fr.final_record_id DESC LIMIT 1");
        $loadStmt->execute([$currentUserId]);
        $loadRecord = $loadStmt->fetch(PDO::FETCH_ASSOC);
        if ($loadRecord) {
            $teachingLoad = (float) $loadRecord['total_units'];
            $dashboardTermLabel = $loadRecord['academic_year'] . ' · ' . $loadRecord['semester'];
            $assignments = json_decode((string) $loadRecord['assignments_json'], true);
            $todayKey = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][(int) date('N') - 1];
            foreach (is_array($assignments) ? $assignments : [] as $assignment) {
                if (!in_array($todayKey, smsFacultyDashboardDays($assignment['day_pattern'] ?? null), true)) {
                    continue;
                }
                $assignment['display_time'] = smsFacultyDashboardTime($assignment['time_start'] ?? null)
                    . ' - ' . smsFacultyDashboardTime($assignment['time_end'] ?? null);
                $assignment['display_room'] = trim((string) ($assignment['room'] ?? '')) ?: 'Not set';
                $todaySchedule[] = $assignment;
            }
            usort($todaySchedule, static fn(array $a, array $b): int => strcmp((string) ($a['time_start'] ?? ''), (string) ($b['time_start'] ?? '')));
            $classesToday = count($todaySchedule);
            $dashboardNotifications[] = [
                'title' => 'Teaching load finalized',
                'msg' => 'Your ' . $dashboardTermLabel . ' teaching load has been acknowledged.',
                'time' => date('M j, g:i A', strtotime((string) $loadRecord['finalized_at']) ?: time()),
            ];
        }

        $pendingStmt = $facultyPdo->prepare("SELECT COUNT(*) FROM teaching_load_requests
            WHERE faculty_user_id = ? AND workflow_stage = 'faculty_acknowledgement' AND status = 'Pending'");
        $pendingStmt->execute([$currentUserId]);
        $pendingLoadAcknowledgements = (int) $pendingStmt->fetchColumn();

        if ($facultyId > 0) {
            $ratingStmt = $facultyPdo->prepare('SELECT AVG(composite_score) AS avg_rating
                FROM evaluations WHERE faculty_id = ? AND composite_score IS NOT NULL');
            $ratingStmt->execute([$facultyId]);
            $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($ratingData['avg_rating'])) {
                $rating = round((float) $ratingData['avg_rating'], 2);
                if ($rating >= 4.5) $ratingLabel = 'Excellent';
                elseif ($rating >= 4.0) $ratingLabel = 'Very Good';
                elseif ($rating >= 3.5) $ratingLabel = 'Good';
                elseif ($rating >= 3.0) $ratingLabel = 'Satisfactory';
                else $ratingLabel = 'Needs Improvement';
            }

            $leaveStmt = $facultyPdo->prepare("
            SELECT leave_type, start_date, end_date, status, created_at 
            FROM leave_requests
            WHERE faculty_id = :faculty_id
            ORDER BY created_at DESC 
            LIMIT 5
            ");
            $leaveStmt->execute([':faculty_id' => $facultyId]);
            $rawLeaves = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawLeaves as $lr) {
                $status = $lr['status'] ?? 'Pending';
                $badgeClass = 'badge-status-pending';
                if (strcasecmp($status, 'Approved') === 0) {
                    $badgeClass = 'badge-status-approved';
                } elseif (strcasecmp($status, 'Rejected') === 0) {
                    $badgeClass = 'badge-status-rejected';
                } elseif (strcasecmp($status, 'Returned') === 0) {
                    $badgeClass = 'badge-status-returned';
                }

                $startDate = date('M d, Y', strtotime($lr['start_date']));
                $endDate = !empty($lr['end_date']) && $lr['end_date'] !== $lr['start_date'] ? date('M d, Y', strtotime($lr['end_date'])) : '';
                $dateFormatted = $endDate ? strtoupper($startDate . ' - ' . $endDate) : strtoupper($startDate);

                $leaveRequests[] = [
                    'date' => $dateFormatted,
                    'type' => $lr['leave_type'] ?? 'Leave',
                    'status' => $status,
                    'badge' => $badgeClass,
                ];
                $dashboardNotifications[] = [
                    'title' => 'Leave request ' . strtolower($status),
                    'msg' => ($lr['leave_type'] ?? 'Leave') . ' · ' . $dateFormatted,
                    'time' => date('M j, g:i A', strtotime((string) ($lr['created_at'] ?? 'now')) ?: time()),
                ];
            }
        }

        $notificationStmt = $facultyPdo->prepare('SELECT title, message, created_at
            FROM notifications WHERE faculty_id = ? ORDER BY created_at DESC LIMIT 5');
        $notificationStmt->execute([$facultyId]);
        foreach ($notificationStmt->fetchAll(PDO::FETCH_ASSOC) as $notification) {
            $dashboardNotifications[] = [
                'title' => (string) $notification['title'],
                'msg' => (string) $notification['message'],
                'time' => date('M j, g:i A', strtotime((string) $notification['created_at']) ?: time()),
            ];
        }
        usort($dashboardNotifications, static fn(array $a, array $b): int => strtotime($b['time']) <=> strtotime($a['time']));
        $dashboardNotifications = array_slice($dashboardNotifications, 0, 5);
    } catch (Exception $e) {
        error_log('Faculty dashboard data fetch error: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1"><i class="fas fa-user text-purple me-2"></i>Faculty Dashboard</h1>
        <p class="text-muted mb-0 small">Welcome, Prof. <?= htmlspecialchars($facultyName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-dark btn-sm fw-medium" type="button" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/faculty/my-schedule.php'">
            <i class="fas fa-calendar me-1"></i>My Schedule
        </button>
        <button class="btn btn-outline-primary btn-sm fw-medium" type="button" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/teaching-load-workflow.php'">
            <i class="fas fa-book-open me-1"></i>Teaching Load
        </button>
    </div>
</div>

<?php if ($pendingLoadAcknowledgements > 0): ?>
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
        <span><?= $pendingLoadAcknowledgements ?> teaching load<?= $pendingLoadAcknowledgements === 1 ? ' is' : 's are' ?> waiting for your acknowledgement.</span>
        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/modules/faculty/views/teaching-load-workflow.php">Review load</a>
    </div>
<?php endif; ?>

<div class="container-fluid px-0 py-2">
    <!-- Stat Metric Cards -->
    <div class="row g-3 mb-4">  
        <div class="col-12 col-sm-6 col-xl">
            <section class="card stat-card primary border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-primary fs-4">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Teaching Load</h6>
                        <h4 class="mb-0 fw-bold fs-5"><?= $teachingLoad > 0 ? htmlspecialchars(number_format($teachingLoad, 1), ENT_QUOTES, 'UTF-8') : '—' ?> <small class="text-muted fs-6"><?= $teachingLoad > 0 ? 'units' : '' ?></small></h4>
                        <?php if ($dashboardTermLabel !== ''): ?><small class="text-muted"><?= htmlspecialchars($dashboardTermLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <section class="card stat-card success border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-success fs-4">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Classes Today</h6>
                        <h4 class="mb-0 fw-bold fs-5"><?= $classesToday ?></h4>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <section class="card stat-card warning border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="stat-icon me-3 text-warning fs-4">
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rating</h6>
                        <h4 class="mb-0 fw-bold fs-5"><?= $rating > 0 ? $rating : '—' ?> <small class="text-muted fs-6"><?= $rating > 0 ? '/5.0' : '' ?></small></h4>
                        <?php if ($rating > 0): ?><small class="text-warning"><?= htmlspecialchars($ratingLabel) ?></small><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
  <!-- Main Content Area -->
<div class="row g-4">
    <!-- Schedule & Leave Requests (Left Column) -->
    <div class="col-12 col-lg-8">         
        <!-- Schedule Card -->
        <div class="card border shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="far fa-clock text-primary me-2"></i>Today's Schedule
                </h6>
                <span class="badge custom-badge bg-secondary-subtle text-dark border fw-medium"><?= date('F j, Y') ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($todaySchedule === []): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-calendar-times fs-3 mb-2 text-secondary"></i>
                        <p class="mb-0 small">No finalized classes scheduled for today.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($todaySchedule as $class): ?>
                            <li class="list-group-item d-flex align-items-start justify-content-between gap-3 py-3 px-3">
                                <div>
                                    <strong class="text-primary"><?= htmlspecialchars((string) ($class['code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars((string) ($class['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                    <div class="small text-secondary">
                                        Section <?= htmlspecialchars((string) ($class['section'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        · <?= htmlspecialchars((string) ($class['units'] ?? '0'), ENT_QUOTES, 'UTF-8') ?> units
                                        · Room <?= htmlspecialchars((string) ($class['display_room'] ?? 'Not set'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                    </div>
                                </div>
                                <span class="badge text-bg-primary text-nowrap"><?= htmlspecialchars((string) ($class['display_time'] ?? 'Time not set'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leave Requests Section -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-plane-departure text-primary me-2"></i>Leave Requests
                </h6>
                <button class="btn btn-sm btn-outline-primary fw-medium" type="button" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/faculty/leave-request.php'">
                    <i class="fas fa-plus me-1"></i>Submit Leave
                </button>
            </div>
            <div class="card-body p-0 custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    <?php if (empty($leaveRequests)): ?>
                        <li class="list-group-item text-center text-muted py-4">No leave requests found.</li>
                    <?php else: foreach ($leaveRequests as $lr): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center border border-primary-subtle rounded px-2 py-1 bg-primary-subtle text-primary flex-shrink-0" style="min-width: 60px;">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark small"><?= htmlspecialchars($lr['type']) ?></h6>
                                    <small class="text-secondary d-block"><?= htmlspecialchars($lr['date']) ?></small>
                                </div>
                            </div>
                            <span class="badge <?= $lr['badge'] ?> px-3 py-2 fw-semibold rounded-pill"><?= htmlspecialchars($lr['status']) ?></span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

    </div>

    <!-- Sidebar Stream (Right Column) -->
    <div class="col-12 col-lg-4">

        <!-- Notifications Card -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="far fa-bell text-primary me-2"></i>Recent Notifications
                </h6>
                <button class="btn btn-sm btn-link text-primary text-decoration-none p-0 small" type="button" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/faculty/notifications.php'">
                    View All
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if ($dashboardNotifications === []): ?>
                        <li class="list-group-item p-3 text-center text-muted small">No recent updates.</li>
                    <?php else: foreach ($dashboardNotifications as $notification): ?>
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                                <strong class="small text-dark"><?= htmlspecialchars($notification['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                <span class="text-muted text-nowrap" style="font-size: 0.75rem;"><?= htmlspecialchars($notification['time'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </div>
                            <p class="mb-0 small text-secondary"><?= htmlspecialchars($notification['msg'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

    </div>

</div>
</div>

<style>
    /* Translucent Status Badge Styles matching reference image */
    .badge-status-approved {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #34d399 !important;
        border: 1px solid rgba(52, 211, 153, 0.35) !important;
    }
    
    .badge-status-pending {
        background-color: rgba(245, 158, 11, 0.15) !important;
        color: #fbbf24 !important;
        border: 1px solid rgba(251, 191, 36, 0.35) !important;
    }

    .badge-status-returned {
        background-color: rgba(245, 158, 11, 0.15) !important;
        color: #fbbf24 !important;
        border: 1px solid rgba(251, 191, 36, 0.35) !important;
    }

    .badge-status-rejected {
        background-color: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
        border: 1px solid rgba(248, 113, 113, 0.35) !important;
    }

    .badge-status-finished {
        background-color: rgba(59, 130, 246, 0.15) !important;
        color: #60a5fa !important;
        border: 1px solid rgba(96, 165, 250, 0.35) !important;
    }

    /* Dark Mode Overrides - High Visibility for Date / Subtle Badges */
    [data-bs-theme="dark"] .custom-badge,
    body.dark-mode .custom-badge,
    html[data-theme="dark"] .custom-badge,
    :is([data-bs-theme="dark"], body.dark-mode, html[data-theme="dark"]) .bg-secondary-subtle {
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8f9fa !important;
        border-color: rgba(255, 255, 255, 0.25) !important;
    }

    /* Custom Scrollbar Styles */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.25);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(13, 110, 253, 0.6);
    }

    /* Light Theme Scrollbar Adjustments */
    [data-bs-theme="light"] .custom-scrollbar,
    body:not([data-bs-theme="dark"]) .custom-scrollbar {
        scrollbar-color: rgba(13, 110, 253, 0.3) transparent;
    }
    [data-bs-theme="light"] .custom-scrollbar::-webkit-scrollbar-thumb,
    body:not([data-bs-theme="dark"]) .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(13, 110, 253, 0.3);
    }
</style>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>