<?php
/**
 * Notifications
 * Purpose: View and manage notifications
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Notifications';
$activeModule = 'faculty';
$activePage   = 'notification';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Notifications', 'url' => null],
];

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
        <p class="text-secondary small mb-0">View and manage your recent faculty updates and alerts</p>
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
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
    <!-- Unread -->
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2 border-start border-4 border-primary">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-5">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Unread</span>
                    <h4 class="fw-bold mb-0 text-primary">2 <small class="text-muted fs-6">new alerts</small></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Read -->
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-5">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Read</span>
                    <h4 class="fw-bold mb-0">8 <small class="text-muted fs-6">notifications</small></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- This Week -->
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-5">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">This Week</span>
                    <h4 class="fw-bold mb-0">3 <small class="text-muted fs-6">received</small></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Important -->
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-5">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Important</span>
                    <h4 class="fw-bold mb-0">1 <small class="text-muted fs-6">flagged</small></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications List Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-stream text-primary"></i>
            All Notifications
        </h6>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted d-none d-sm-inline">Display:</span>
            <select class="form-select form-select-sm border-0 bg-light w-auto fw-medium">
                <option>10 per page</option>
                <option>25 per page</option>
                <option>50 per page</option>
            </select>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="list-group list-group-flush border-0">
            <!-- Notification Item 1 (Unread) -->
            <div class="list-group-item p-3 p-md-4 border-bottom bg-primary bg-opacity-10 border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-primary text-white rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-dark">Schedule Change</h6>
                            <span class="badge bg-primary rounded-pill">New</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill">High Priority</span>
                        </div>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>1h ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">CS301 schedule changed to Room 302 starting next week.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="markRead(this)">
                            <i class="fas fa-check me-1"></i>Mark as Read
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 2 (Unread) -->
            <div class="list-group-item p-3 p-md-4 border-bottom bg-primary bg-opacity-10 border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-success text-white rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-dark">Leave Approved</h6>
                            <span class="badge bg-primary rounded-pill">New</span>
                        </div>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>2h ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Your sick leave request for Aug 21-22 has been approved by Dept. Head.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="markRead(this)">
                            <i class="fas fa-check me-1"></i>Mark as Read
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 3 (Read) -->
            <div class="list-group-item p-3 p-md-4 border-bottom border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold text-dark">Performance Update</h6>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>1d ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Your performance evaluation for 2nd Semester 2025 is now available for viewing.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 4 (Read) -->
            <div class="list-group-item p-3 p-md-4 border-bottom border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-semibold text-dark">Meeting Reminder</h6>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill">High Priority</span>
                        </div>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>2d ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Department meeting scheduled for August 5, 2025 at 2:00 PM.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 5 (Read) -->
            <div class="list-group-item p-3 p-md-4 border-bottom border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-secondary bg-opacity-10 text-secondary rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-semibold text-dark">Grade Submission</h6>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill">High Priority</span>
                        </div>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>3d ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Midterm grades submission deadline is August 5, 2025.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 6 (Read) -->
            <div class="list-group-item p-3 p-md-4 border-bottom border-light-subtle d-flex align-items-start gap-3">
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold text-dark">Schedule Published</h6>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>5d ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Final schedule for 1st Semester 2025-2026 has been published.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Item 7 (Read) -->
            <div class="list-group-item p-3 p-md-4 d-flex align-items-start gap-3">
                <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle fs-6 mt-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold text-dark">Document Reminder</h6>
                        <span class="small text-muted"><i class="far fa-clock me-1"></i>1w ago</span>
                    </div>
                    <p class="mb-2 text-secondary small">Please update your profile documents before August 15, 2025.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0" onclick="deleteNotif(this)">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination Footer -->
    <div class="card-footer bg-transparent border-0 py-3 px-4 d-flex justify-content-between align-items-center">
        <span class="small text-muted">Showing 1-7 of 10 notifications</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link border-0" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link rounded-circle mx-1" href="#">1</a></li>
                <li class="page-item"><a class="page-link rounded-circle mx-1" href="#">2</a></li>
                <li class="page-item"><a class="page-link border-0" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<script>
function markRead(btn) {
    const item = btn.closest('.list-group-item');
    item.classList.remove('bg-primary', 'bg-opacity-10');
    const badge = item.querySelector('.badge.bg-primary');
    if (badge) badge.remove();
    btn.remove();
}

function markAllRead() {
    if(confirm('Mark all notifications as read?')) {
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.classList.remove('bg-primary', 'bg-opacity-10');
            const badge = item.querySelector('.badge.bg-primary');
            if (badge) badge.remove();
            const readBtn = item.querySelector('.btn-primary');
            if (readBtn) readBtn.remove();
        });
    }
}

function deleteNotif(btn) {
    if(confirm('Delete this notification?')) {
        const item = btn.closest('.list-group-item');
        item.remove();
    }
}

function clearAll() {
    if(confirm('Clear all notifications? This action cannot be undone.')) {
        document.querySelector('.list-group').innerHTML = '<div class="p-4 text-center text-muted">No notifications available.</div>';
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
