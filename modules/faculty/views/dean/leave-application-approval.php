<?php
/**
 * SMS 2 - Leave Application & Approval
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Leave Application & Approval';
$activeModule = 'faculty';
$activePage   = 'leave-application-approval';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Application & Approval', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div style="flex: 1; min-width: 280px;">
        <h1 class="h2 text-body"><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Leave Application &amp; Approval</h1>
    </div>
</div>

<!-- Overview Stats Row -->
<div class="row g-3 mb-4 dashboard-stats">
    <!-- Card 1: Total Faculty -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Faculty</h6>
                    <h4 class="mb-0 fw-bold">142</h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-arrow-trend-up me-1"></i>+2.5% <span class="text-muted fw-normal">from last month</span>
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Card 2: Active Colleges -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-info fs-4">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Active Colleges</h6>
                    <h4 class="mb-0 fw-bold">8</h4>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">No change</small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Card 3: Classes Active -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-success fs-4">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Classes Active</h6>
                    <h4 class="mb-0 fw-bold">118</h4>
                    <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-check me-1"></i>95.5% <span class="text-muted fw-normal">present rate</span>
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Card 4: Leave Requests -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3 text-warning fs-4">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Leave Requests</h6>
                    <h4 class="mb-0 fw-bold">8</h4>
                    <small class="text-danger fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-arrow-trend-down me-1"></i>-2.5% <span class="text-muted fw-normal">from last month</span>
                    </small>
                </div>
            </div>
            <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Details">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<!-- 3. Charts Analytics Row -->
<div class="row g-4 mb-4">
    <!-- Left Chart: Leave Application Trends -->
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100 w-100%">
            <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-chart-line me-2"></i>Leave Application Trends</h6>
                <select class="form-select form-select-sm bg-body border-secondary-subtle w-auto" style="font-size: 0.75rem;">
                    <option selected>This Term</option>
                    <option>Previous Term</option>
                </select>
            </div>
            <div class="card-body p-3">
                <!-- Anchor Container to block layout collapse bugs -->
                <div style="position: relative; width: 100%; height: 280px; min-height: 280px;">
                    <canvas id="leaveTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Chart: Leave Type Distribution Breakdown -->
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom py-3">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-chart-pie me-2"></i>Leave Categories Breakdown</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <!-- Wrapper Box constrained to absolute dimensions -->
                <div style="position: relative; width: 100%; height: 200px; max-height: 200px; display: flex; justify-content: center;">
                    <canvas id="leaveDistributionChart"></canvas>
                </div>
                <!-- Custom Legend matching text layout -->
                <div class="d-flex gap-3 justify-content-center mt-3 flex-wrap text-muted small w-100" style="font-size: 0.75rem;">
                    <span class="text-nowrap"><i class="fas fa-circle text-primary me-1"></i> Vacation</span>
                    <span class="text-nowrap"><i class="fas fa-circle text-danger me-1"></i> Sick Leave</span>
                    <span class="text-nowrap"><i class="fas fa-circle text-warning me-1"></i> Research</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Form and Records Side-by-Side Content Grid -->
<div class="row g-4 mb-4">
    <!-- Left Column: Leave Applications Fast Review List -->
    <div class="col-12 col-md-6 col-xl-5">
        <div class="card bg-body text-body border-secondary-subtle h-100 shadow-sm">
            <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-id-card-clip me-2"></i>Leave Applications (12)</h6>
                <a href="#" class="text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle" style="width: 24px; height: 24px; font-size: 0.7rem;"><i class="fas fa-arrow-up-right-from-square"></i></a>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <!-- User Item 1 -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-sm-between border-bottom border-secondary-subtle pb-3 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; min-width: 40px; background-image: url('assets/img/avatar1.jpg'); background-size: cover;">WW</div>
                        <div>
                            <h6 class="mb-0 fw-bold small text-body">Wade Warren <span class="text-muted fw-normal" style="font-size: 0.75rem;">(IT Department)</span></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Reason: Personal Leave</small>
                        </div>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 px-2 py-1 small ms-5 ms-sm-0" style="font-size: 0.7rem;">Pending</span>
                </div>
                <!-- User Item 2 -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-sm-between border-bottom border-secondary-subtle pb-3 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; min-width: 40px; background-image: url('assets/img/avatar2.jpg'); background-size: cover;">JB</div>
                        <div>
                            <h6 class="mb-0 fw-bold small text-body">Jerome Bell <span class="text-muted fw-normal" style="font-size: 0.75rem;">(IT Department)</span></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Reason: Sick Leave</small>
                        </div>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 px-2 py-1 small ms-5 ms-sm-0" style="font-size: 0.7rem;">Pending</span>
                </div>
                <!-- User Item 3 -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-sm-between border-bottom border-secondary-subtle pb-3 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; min-width: 40px; background-image: url('assets/img/avatar3.jpg'); background-size: cover;">TW</div>
                        <div>
                            <h6 class="mb-0 fw-bold small text-body">Theresa Webb <span class="text-muted fw-normal" style="font-size: 0.75rem;">(IT Department)</span></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Reason: Family Emergency</small>
                        </div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1 small ms-5 ms-sm-0" style="font-size: 0.7rem;">Approved</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Interactive Approval Actions Grid -->
    <div class="col-12 col-md-6 col-xl-7">
        <div class="card bg-body text-body border-secondary-subtle h-100 shadow-sm">
            <div class="card-header bg-body-tertiary border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-link text-primary fw-bold text-decoration-none px-2 py-1">Requests</button>
                    <button class="btn btn-sm btn-link text-muted text-decoration-none px-2 py-1">My Tasks</button>
                </div>
                <div class="d-flex gap-1">
                    <span class="badge bg-primary text-primary bg-opacity-10 border border-primary-subtle px-2 py-1" style="font-size: 0.7rem; cursor: pointer;">Pending</span>
                    <span class="badge bg-light text-muted border border-secondary-subtle px-2 py-1" style="font-size: 0.7rem; cursor: pointer;">Approved</span>
                </div>
            </div>
            <div class="card-body d-flex flex-column gap-3 overflow-auto" style="max-height: 440px;">
                <!-- Leave Request Block Card 1 -->
                <div class="p-3 rounded border border-secondary-subtle bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary text-white small d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; min-width: 28px;">NK</div>
                            <div>
                                <h6 class="mb-0 fw-bold small text-body">Neha Kumari</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">You have <strong class="text-primary">6.5 leave</strong> units remaining</small>
                            </div>
                        </div>
                        <div class="text-sm-end">
                            <span class="fw-bold small d-block text-body" style="font-size: 0.8rem;">Leave Request</span>
                            <small class="text-muted d-block small" style="font-size: 0.7rem;"><i class="fas fa-circle text-primary me-1" style="font-size: 0.45rem;"></i> Vacation Leave</small>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-muted mb-1 fw-bold" style="font-size: 0.7rem;">From</label>
                            <div class="p-2 border rounded bg-body text-body border-secondary-subtle fw-semibold small" style="font-size: 0.75rem;">23 Sep 2026 / Second Half</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-muted mb-1 fw-bold" style="font-size: 0.7rem;">To</label>
                            <div class="p-2 border rounded bg-body text-body border-secondary-subtle fw-semibold small" style="font-size: 0.75rem;">26 Sep 2026 / First Half</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm px-3 fw-bold" style="font-size: 0.75rem;" type="button">Approve</button>
                        <button class="btn btn-outline-danger btn-sm px-3 border-secondary-subtle fw-bold" style="font-size: 0.75rem;" type="button">Reject</button>
                    </div>
                </div>

                <!-- Leave Request Block Card 2 -->
                <div class="p-3 rounded border border-secondary-subtle bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary text-white small d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; min-width: 28px;">NK</div>
                            <div>
                                <h6 class="mb-0 fw-bold small text-body">Neha Kumari</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">You have <strong class="text-primary">6.5 leave</strong> units remaining</small>
                            </div>
                        </div>
                        <div class="text-sm-end">
                            <span class="fw-bold small d-block text-body" style="font-size: 0.8rem;">Leave Request</span>
                            <small class="text-muted d-block small" style="font-size: 0.7rem;"><i class="fas fa-circle text-primary me-1" style="font-size: 0.45rem;"></i> Sick Leave</small>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-muted mb-1 fw-bold" style="font-size: 0.7rem;">From</label>
                            <div class="p-2 border rounded bg-body text-body border-secondary-subtle fw-semibold small" style="font-size: 0.75rem;">23 Sep 2026 / Second Half</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-muted mb-1 fw-bold" style="font-size: 0.7rem;">To</label>
                            <div class="p-2 border rounded bg-body text-body border-secondary-subtle fw-semibold small" style="font-size: 0.75rem;">26 Sep 2026 / First Half</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm px-3 fw-bold" style="font-size: 0.75rem;" type="button">Approve</button>
                        <button class="btn btn-outline-danger btn-sm px-3 border-secondary-subtle fw-bold" style="font-size: 0.75rem;" type="button">Reject</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared Dark Theme Grid Color Configuration
    const gridColor = 'rgba(255, 255, 255, 0.08)';
    const textColor = '#8a99ad';

    // 1. Leave Trends Chart Setup (Line/Area)
    const ctxTrends = document.getElementById('leaveTrendsChart').getContext('2d');
    new Chart(ctxTrends, {
        type: 'line',
        data: {
            labels: ['Jun 22', 'Jun 29', 'Jul 06', 'Jul 13', 'Jul 20', 'Jul 27'],
            datasets: [{
                label: 'Applications Received',
                data: [5, 14, 8, 22, 12, 8],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100, // Delays recalculation until layout transitions complete perfectly
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } }, beginAtZero: true }
            }
        }
    });

    // 2. Leave Categories Distribution Setup (Doughnut)
    const ctxDist = document.getElementById('leaveDistributionChart').getContext('2d');
    new Chart(ctxDist, {
        type: 'doughnut',
        data: {
            labels: ['Vacation', 'Sick Leave', 'Research'],
            datasets: [{
                data: [55, 30, 15],
                backgroundColor: [
                    '#0d6efd', 
                    '#dc3545', 
                    '#ffc107'  
                ],
                borderWidth: 0,
                weight: 0.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100,
            plugins: { legend: { display: false } },
            cutout: '75%'
        }
    });
});
</script>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>