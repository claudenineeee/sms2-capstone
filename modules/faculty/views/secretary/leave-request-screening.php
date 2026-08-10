<?php
/**
 * Leave Request Screening
 * Purpose: Screen and verify leave requests before forwarding to Department Head
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Leave Request Screening';
$activeModule = 'faculty';
$activePage   = 'leave-request-screening';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Leave Request Screening', 'url' => null],
];
require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// Mock Data
$requests = [
    ['id'=>'LEAVE-057','faculty'=>'Prof. John Aquino','fac_id'=>'F-004','type'=>'Sick Leave','start'=>'Aug 21','end'=>'Aug 22','days'=>2,'doc'=>'Complete','screening'=>'Pending','date'=>'Today'],
    ['id'=>'LEAVE-058','faculty'=>'Dr. Ana Reyes','fac_id'=>'F-005','type'=>'Vacation Leave','start'=>'Sep 01','end'=>'Sep 05','days'=>5,'doc'=>'Complete','screening'=>'Pending','date'=>'Today'],
    ['id'=>'LEAVE-059','faculty'=>'Prof. Sarah Martinez','fac_id'=>'F-006','type'=>'Emergency Leave','start'=>'Aug 15','end'=>'Aug 16','days'=>2,'doc'=>'Incomplete','screening'=>'Pending','date'=>'Yesterday'],
];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    .stat-card {
        border: none;
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
    }
    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
    }
    
    /* Graduation Cap Avatar Badge Style */
    .faculty-avatar-badge {
        width: 38px;
        height: 38px;
        background: #181e36;
        color: #8b95ff;
        border: 1px solid rgba(139, 149, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 0 10px rgba(139, 149, 255, 0.15);
    }

    /* Dark-Theme Pill Badges */
    .badge-status-complete, .badge-type-vacation {
        background-color: #0d2822 !important;
        color: #2be49b !important;
        border: 1px solid #14533c !important;
    }

    .badge-status-pending, .badge-type-emergency {
        background-color: #311c08 !important;
        color: #f3a833 !important;
        border: 1px solid #63360b !important;
    }

    .badge-type-sick {
        background-color: #0b1d3a !important;
        color: #4da3ff !important;
        border: 1px solid #163e75 !important;
    }

    .badge-status-incomplete, .badge-status-returned {
        background-color: #2d1215 !important;
        color: #ff5263 !important;
        border: 1px solid #5a1e24 !important;
    }
    
    .badge-type-study {
        background-color: #1a1528 !important;
        color: #b388ff !important;
        border: 1px solid #3d2b5a !important;
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-1">
            <i class="fas fa-inbox text-purple me-2"></i>Leave Request Screening
        </h2>
        <p class="text-muted small mb-0">Screen and verify leave requests before forwarding to Department Head.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success" onclick="forwardSelected()">
            <i class="fas fa-paper-plane me-1"></i>Forward to Dept. Head
        </button>
        <button class="btn btn-warning text-dark" onclick="returnSelected()">
            <i class="fas fa-undo me-1"></i>Return for Requirements
        </button>
        <button class="btn btn-outline-secondary">
            <i class="fas fa-file-excel me-1 text-success"></i>Export
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Pending Screening</span>
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0">3</h3>
                    <span class="badge bg-danger-subtle text-danger rounded-pill small">3 new</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Verified Today</span>
                    <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-check-circle"></i></div>
                </div>
                <h3 class="fw-bold mb-0">8</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Returned Today</span>
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="fas fa-undo"></i></div>
                </div>
                <h3 class="fw-bold mb-0">2</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Document Issues</span>
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <h3 class="fw-bold mb-0">1</h3>
            </div>
        </div>
    </div>
</div>



<!-- Main Section: Table & Chart -->
<div class="row g-4 mb-4">
    <!-- Leave Requests Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list text-purple me-2"></i>Pending Leave Requests (3)</h6>
                <span class="badge bg-purple-subtle text-purple">3 Loaded</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                <th>Faculty</th>
                                <th>Leave Type</th>
                                <th>Dates & Duration</th>
                                <th>Documents</th>
                                <th>Screening</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $r): 
                                $docBadge = $r['doc'] === 'Complete' ? 'badge-status-complete' : 'badge-status-incomplete';
                                
                                $typeBadge = match($r['type']) {
                                    'Sick Leave'      => 'badge-type-sick',
                                    'Vacation Leave'  => 'badge-type-vacation',
                                    'Emergency Leave' => 'badge-type-emergency',
                                    default           => 'badge-type-study'
                                };
                            ?>
                            <tr>
                                <td class="ps-3"><input type="checkbox" class="form-check-input row-select"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="faculty-avatar-badge">
                                            <i class="fas fa-user-graduate fs-6"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark"><?= $r['faculty'] ?></div>
                                            <small class="text-muted font-monospace"><?= $r['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge <?= $typeBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $r['type'] ?></span></td>
                                <td>
                                    <div class="small fw-semibold"><?= $r['start'] ?> - <?= $r['end'] ?></div>
                                    <small class="text-muted"><?= $r['days'] ?> day(s)</small>
                                </td>
                                <td><span class="badge <?= $docBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $r['doc'] ?></span></td>
                                <td><span class="badge badge-status-pending rounded-pill px-3 py-1 fw-bold"><?= $r['screening'] ?></span></td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary border-0" title="View Details" onclick="viewDetails('<?= $r['id'] ?>')">
                                            <i class="fas fa-eye text-primary"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary border-0" title="Verify Documents" onclick="verifyDocs('<?= $r['id'] ?>')">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary border-0" title="Return" onclick="returnRequest('<?= $r['id'] ?>')">
                                            <i class="fas fa-undo text-warning"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 mt-auto">
                <small class="text-muted">Showing 1-3 of 3 requests</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Leave Type Distribution Chart -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-purple me-2"></i>Leave Type Distribution</h6>
            </div>
            <div class="card-body">
                <div class="position-relative d-flex justify-content-center mb-3">
                    <canvas id="leaveTypeChart" style="max-height: 200px;"></canvas>
                </div>
                <hr>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #4da3ff;"></i>Sick Leave</span>
                        <span class="fw-bold">8 requests</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #2be49b;"></i>Vacation Leave</span>
                        <span class="fw-bold">5 requests</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #f3a833;"></i>Emergency</span>
                        <span class="fw-bold">3 requests</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #b388ff;"></i>Study Leave</span>
                        <span class="fw-bold">2 requests</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Request Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-file-alt text-purple me-2"></i>Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Request ID</small>
                        <span class="fw-semibold">LEAVE-057</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Submitted</small>
                        <span class="fw-semibold">Today, 9:30 AM</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Faculty</small>
                        <span class="fw-semibold">Prof. John Aquino</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Leave Type</small>
                        <span class="badge badge-type-sick rounded-pill px-3 py-1 fw-bold">Sick Leave</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Start Date</small>
                        <span class="fw-semibold">August 21, 2025</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">End Date</small>
                        <span class="fw-semibold">August 22, 2025</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Total Days</small>
                        <span class="fw-semibold">2 days</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Leave Balance</small>
                        <span class="fw-semibold text-success">10 days remaining</span>
                    </div>
                </div>
                <hr>
                <h6 class="fw-bold small mb-2">Required Documents:</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                        Medical Certificate
                        <span class="badge badge-status-complete rounded-pill px-2 py-1">Uploaded</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                        Leave Application Form
                        <span class="badge badge-status-complete rounded-pill px-2 py-1">Uploaded</span>
                    </li>
                </ul>
                <div class="mb-2">
                    <small class="text-muted d-block">Reason</small>
                    <p class="small text-dark mb-0">Medical appointment for check-up.</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-warning text-dark" onclick="returnRequest()">Return for Requirements</button>
                <button type="button" class="btn btn-sm btn-success" onclick="forwardRequest()">Forward to Dept. Head</button>
            </div>
        </div>
    </div>
</div>

<!-- Document Verification Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-check-circle text-purple me-2"></i>Document Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold small mb-3">Required Documents for <span class="font-monospace">LEAVE-057</span></h6>
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="doc1" checked>
                        <label class="form-check-label small" for="doc1">Medical Certificate</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="doc2" checked>
                        <label class="form-check-label small" for="doc2">Leave Application Form</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="doc3">
                        <label class="form-check-label small" for="doc3">Other Supporting Documents</label>
                    </div>
                </div>
                <div class="alert alert-info py-2 px-3 small border-0 mb-0">
                    <i class="fas fa-info-circle me-1"></i> Leave balance: 10 days remaining. Request: 2 days. Sufficient balance.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" onclick="confirmVerify()">Verify & Forward</button>
            </div>
        </div>
    </div>
</div>

<!-- Return with Comments Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-undo text-warning me-2"></i>Return for Requirements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Return (Required)</label>
                    <textarea class="form-control" rows="4" placeholder="Explain what documents or information are needed..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning text-dark">Return Request</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    new Chart(document.getElementById('leaveTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Sick Leave', 'Vacation Leave', 'Emergency', 'Study Leave'],
            datasets: [{
                data: [8, 5, 3, 2],
                backgroundColor: ['#4da3ff', '#2be49b', '#f3a833', '#b388ff'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });
});

function viewDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}
function verifyDocs(id) {
    const modal = new bootstrap.Modal(document.getElementById('verifyModal'));
    modal.show();
}
function returnRequest(id) {
    const modal = new bootstrap.Modal(document.getElementById('returnModal'));
    modal.show();
}
function forwardRequest() {
    alert('Request forwarded to Department Head.');
    bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
}
function confirmVerify() {
    alert('Documents verified and request forwarded.');
    bootstrap.Modal.getInstance(document.getElementById('verifyModal')).hide();
}
function forwardSelected() {
    const selected = document.querySelectorAll('.row-select:checked').length;
    if(selected === 0) {
        alert('Please select requests to forward.');
        return;
    }
    if(confirm('Forward ' + selected + ' selected requests to Department Head?')) {
        alert('Requests forwarded!');
    }
}
function returnSelected() {
    const selected = document.querySelectorAll('.row-select:checked').length;
    if(selected === 0) {
        alert('Please select requests to return.');
        return;
    }
    const modal = new bootstrap.Modal(document.getElementById('returnModal'));
    modal.show();
}
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>