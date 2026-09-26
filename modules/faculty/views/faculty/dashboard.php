<?php
/**
 * Faculty Dashboard
 * Purpose: Personal dashboard for faculty member
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';

$pageTitle    = 'Faculty Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

requireAuth();

// Get current user's faculty_id from database
$pdo = db();
$currentUserId = $_SESSION['user_id'] ?? 0;
$facultyId = null;
$facultyName = 'Faculty';

// Fetch faculty_id and name from faculty_profiles
try {
    $stmt = $pdo->prepare("SELECT fp.id, fp.faculty_id, fp.first_name, fp.last_name FROM faculty_profiles fp WHERE fp.user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $currentUserId]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profileData) {
        $facultyId = $profileData['id']; // Use the internal id for queries
        $facultyName = trim(($profileData['first_name'] ?? '') . ' ' . ($profileData['last_name'] ?? ''));
    }
} catch (Exception $e) {
    error_log('Faculty profile fetch error: ' . $e->getMessage());
}

// Initialize metrics
$teachingLoad = 0;
$classesToday = 0;
$rating = 0;
$ratingLabel = 'No Rating';

// Fetch Teaching Load and Rating from database
if ($facultyId) {
    try {
        // Get current term first (or latest term)
        $termStmt = $pdo->prepare("SELECT term_id FROM academic_terms WHERE status = 'Active' LIMIT 1");
        $termStmt->execute();
        $termData = $termStmt->fetch(PDO::FETCH_ASSOC);
        $currentTermId = $termData['term_id'] ?? null;
        
        if ($currentTermId) {
            // Fetch teaching load for current term
            $loadStmt = $pdo->prepare("SELECT total_units FROM teaching_load_history WHERE faculty_id = :faculty_id AND term_id = :term_id LIMIT 1");
            $loadStmt->execute([':faculty_id' => $facultyId, ':term_id' => $currentTermId]);
            $loadData = $loadStmt->fetch(PDO::FETCH_ASSOC);
            $teachingLoad = $loadData ? (int)$loadData['total_units'] : 0;
        }
        
        // Fetch average rating from evaluations
        $ratingStmt = $pdo->prepare("SELECT AVG(composite_score) as avg_rating FROM evaluations WHERE faculty_id = :faculty_id AND composite_score IS NOT NULL");
        $ratingStmt->execute([':faculty_id' => $facultyId]);
        $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ratingData && $ratingData['avg_rating']) {
            $rating = round($ratingData['avg_rating'], 2);
            if ($rating >= 4.5) $ratingLabel = 'Excellent';
            elseif ($rating >= 4.0) $ratingLabel = 'Very Good';
            elseif ($rating >= 3.5) $ratingLabel = 'Good';
            elseif ($rating >= 3.0) $ratingLabel = 'Satisfactory';
            else $ratingLabel = 'Needs Improvement';
        }
    } catch (Exception $e) {
        error_log('Dashboard metrics fetch error: ' . $e->getMessage());
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
        <p class="text-muted mb-0 small">Welcome, Prof. <?= htmlspecialchars($facultyName) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-dark btn-sm fw-medium" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/faculty/my-schedule.php'">
            <i class="fas fa-calendar me-1"></i>My Schedule
        </button>
    </div>
</div>

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
                        <h4 class="mb-0 fw-bold fs-5"><?= $teachingLoad > 0 ? $teachingLoad : '—' ?> <small class="text-muted fs-6"><?= $teachingLoad > 0 ? 'units' : '' ?></small></h4>
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
                        <h4 class="mb-0 fw-bold fs-5">0</h4>
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
                <span class="badge custom-badge bg-secondary-subtle text-dark border fw-medium">August 1, 2025</span>
            </div>
            <div class="card-body p-4 text-center text-muted">
                <i class="fas fa-calendar-times fs-3 mb-2 text-secondary"></i>
                <p class="mb-0 small">No schedules available for today.</p>
            </div>
        </div>

        <!-- Leave Requests Section -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-plane-departure text-primary me-2"></i>Leave Requests
                </h6>
                <button class="btn btn-sm btn-outline-primary fw-medium" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/views/faculty/leave-request.php'">
                    <i class="fas fa-plus me-1"></i>Submit Leave
                </button>
            </div>
            <div class="card-body p-0 custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    <?php
                    $leaveRequests = [
                        ['date' => 'AUG 21 - 22, 2026', 'type' => 'Sick Leave', 'status' => 'Approved', 'badge' => 'badge-status-approved'],
                        ['date' => 'SEP 10, 2026', 'type' => 'Vacation Leave', 'status' => 'Pending', 'badge' => 'badge-status-pending']
                    ];
                    if (empty($leaveRequests)): ?>
                        <li class="list-group-item text-center text-muted py-4">No leave requests found.</li>
                    <?php else: foreach ($leaveRequests as $lr): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center border border-primary-subtle rounded px-2 py-1 bg-primary-subtle text-primary flex-shrink-0" style="min-width: 60px;">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark small"><?= $lr['type'] ?></h6>
                                    <small class="text-secondary d-block"><?= $lr['date'] ?></small>
                                </div>
                            </div>
                            <span class="badge <?= $lr['badge'] ?> px-3 py-2 fw-semibold rounded-pill"><?= $lr['status'] ?></span>
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
                <button class="btn btn-sm btn-link text-primary text-decoration-none p-0 small" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/faculty/pages/notifications.php'">
                    View All
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php
                    $notifications = [
                        ['title' => 'Schedule Change', 'msg' => 'CS301 schedule changed to Room 302', 'time' => '1h ago'],
                        ['title' => 'Leave Approved', 'msg' => 'Your sick leave request for Aug 21-22 has been approved', 'time' => '2h ago'],
                        ['title' => 'Performance Update', 'msg' => 'Your evaluation for 2nd Semester 2025 is available', 'time' => '1d ago']
                    ];
                    foreach ($notifications as $n): ?>
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small text-dark"><?= $n['title'] ?></strong>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= $n['time'] ?></span>
                            </div>
                            <p class="mb-0 small text-secondary"><?= $n['msg'] ?></p>
                        </li>
                    <?php endforeach; ?>
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