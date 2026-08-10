<?php
/**
 * Documents
 * Purpose: Manage faculty documents, certificates, and contracts
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Documents';
$activeModule = 'faculty';
$activePage   = 'documents';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Documents', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// Mock Data
$documents = [
    ['id'=>'DOC-001','faculty'=>'Dr. Maria Santos','type'=>'Contract','name'=>'Employment Contract 2024-2026','upload'=>'Jan 15, 2024','expiry'=>'Dec 31, 2026','status'=>'Valid'],
    ['id'=>'DOC-002','faculty'=>'Dr. Maria Santos','type'=>'Certificate','name'=>'PhD Certificate','upload'=>'Jan 15, 2024','expiry'=>'-','status'=>'Valid'],
    ['id'=>'DOC-003','faculty'=>'Prof. Luis Tan','type'=>'Contract','name'=>'Employment Contract 2024-2026','upload'=>'Jan 15, 2024','expiry'=>'Aug 15, 2025','status'=>'Expiring Soon'],
    ['id'=>'DOC-004','faculty'=>'Prof. Luis Tan','type'=>'Certificate','name'=>'Master\'s Degree Certificate','upload'=>'Jan 15, 2024','expiry'=>'-','status'=>'Valid'],
    ['id'=>'DOC-005','faculty'=>'Prof. Katherine Lim','type'=>'Training','name'=>'AI Workshop Certificate','upload'=>'Mar 10, 2025','expiry'=>'Aug 20, 2025','status'=>'Expiring Soon'],
    ['id'=>'DOC-006','faculty'=>'Prof. John Aquino','type'=>'ID','name'=>'Faculty ID Copy','upload'=>'Feb 01, 2025','expiry'=>'-','status'=>'Valid'],
    ['id'=>'DOC-007','faculty'=>'Dr. Ana Reyes','type'=>'Certificate','name'=>'Training Certificate','upload'=>'Apr 05, 2025','expiry'=>'Aug 25, 2025','status'=>'Expiring Soon'],
];

$expiring = [
    ['faculty'=>'Prof. Luis Tan','doc'=>'Contract Renewal','expiry'=>'Aug 15, 2025'],
    ['faculty'=>'Prof. Katherine Lim','doc'=>'Medical Certificate','expiry'=>'Aug 20, 2025'],
    ['faculty'=>'Dr. Ana Reyes','doc'=>'Training Certificate','expiry'=>'Aug 25, 2025'],
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
    
    /* Faculty Avatar Badge Style */
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

    /* Dark-Theme Pill Badges for Document Statuses */
    .badge-status-valid {
        background-color: #0d2822 !important;
        color: #2be49b !important;
        border: 1px solid #14533c !important;
    }

    .badge-status-expiring {
        background-color: #311c08 !important;
        color: #f3a833 !important;
        border: 1px solid #63360b !important;
    }

    .badge-status-expired {
        background-color: #2d1215 !important;
        color: #ff5263 !important;
        border: 1px solid #5a1e24 !important;
    }

    /* Dark-Theme Pill Badges for Document Types */
    .badge-type-contract {
        background-color: #0b1d3a !important;
        color: #4da3ff !important;
        border: 1px solid #163e75 !important;
    }

    .badge-type-certificate {
        background-color: #1a1528 !important;
        color: #b388ff !important;
        border: 1px solid #3d2b5a !important;
    }

    .badge-type-training {
        background-color: #0d2822 !important;
        color: #2be49b !important;
        border: 1px solid #14533c !important;
    }

    .badge-type-id {
        background-color: #22222a !important;
        color: #b0b0cc !important;
        border: 1px solid #3c3c4d !important;
    }

    .expiring-alert-card {
        border-left: 4px solid #f3a833;
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-1">
            <i class="fas fa-folder text-purple me-2"></i>Documents
        </h2>
        <p class="text-muted small mb-0">Manage faculty documents, certificates, and contracts</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i>Upload Document
        </button>
        <button class="btn btn-outline-secondary">
            <i class="fas fa-file-excel me-1 text-success"></i>Export List
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Total Documents</span>
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-file"></i></div>
                </div>
                <h3 class="fw-bold mb-0">156</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Valid</span>
                    <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-check-circle"></i></div>
                </div>
                <h3 class="fw-bold mb-0">142</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Expiring Soon</span>
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-exclamation-circle"></i></div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0">8</h3>
                    <span class="badge bg-warning-subtle text-warning rounded-pill small">Action required</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Expired</span>
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="fas fa-times-circle"></i></div>
                </div>
                <h3 class="fw-bold mb-0">6</h3>
            </div>
        </div>
    </div>
</div>

<!-- Expiring Soon Alert Banner -->
<div class="card border-warning border-0 shadow-sm mb-4">
    <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-3">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Documents Expiring in 30 Days</h6>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php foreach ($expiring as $e): ?>
            <div class="col-md-4">
                <div class="card bg-white border shadow-sm p-3 expiring-alert-card h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="faculty-avatar-badge" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-0 fs-6"><?= $e['faculty'] ?></h6>
                    </div>
                    <p class="small text-muted mb-2"><?= $e['doc'] ?></p>
                    <div>
                        <span class="badge badge-status-expiring rounded-pill px-3 py-1 fw-bold">
                            <i class="fas fa-clock me-1"></i>Expires: <?= $e['expiry'] ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



<!-- Main Section: Table & Chart -->
<div class="row g-4 mb-4">
    <!-- Documents Table Repository -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list text-purple me-2"></i>Document Repository (156)</h6>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto">
                        <option>10 per page</option>
                        <option>25 per page</option>
                        <option>50 per page</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Faculty</th>
                                <th>Type</th>
                                <th>Document Name</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $d): 
                                $statusBadge = match($d['status']) {
                                    'Valid'         => 'badge-status-valid',
                                    'Expiring Soon' => 'badge-status-expiring',
                                    default         => 'badge-status-expired'
                                };

                                $typeBadge = match($d['type']) {
                                    'Contract'    => 'badge-type-contract',
                                    'Certificate' => 'badge-type-certificate',
                                    'Training'    => 'badge-type-training',
                                    default       => 'badge-type-id'
                                };
                            ?>
                            <tr>
                                <td class="ps-3 small font-monospace text-muted"><?= $d['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="faculty-avatar-badge" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <span class="fw-semibold text-dark small"><?= $d['faculty'] ?></span>
                                    </div>
                                </td>
                                <td><span class="badge <?= $typeBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $d['type'] ?></span></td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= $d['name'] ?></div>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Uploaded: <?= $d['upload'] ?></small>
                                </td>
                                <td class="small font-monospace"><?= $d['expiry'] ?></td>
                                <td><span class="badge <?= $statusBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $d['status'] ?></span></td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary border-0" title="View" onclick="viewDocument('<?= $d['id'] ?>')">
                                            <i class="fas fa-eye text-primary"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary border-0" title="Download" onclick="downloadDocument('<?= $d['id'] ?>')">
                                            <i class="fas fa-download text-success"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary border-0" title="Update" onclick="updateDocument('<?= $d['id'] ?>')">
                                            <i class="fas fa-edit text-warning"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary border-0" title="Delete" onclick="deleteDocument('<?= $d['id'] ?>')">
                                            <i class="fas fa-trash text-danger"></i>
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
                <small class="text-muted">Showing 1-7 of 156 documents</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Prev</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Document Status Chart -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-purple me-2"></i>Status Overview</h6>
            </div>
            <div class="card-body">
                <div class="position-relative d-flex justify-content-center mb-3">
                    <canvas id="docStatusChart" style="max-height: 200px;"></canvas>
                </div>
                <hr>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #2be49b;"></i>Valid</span>
                        <span class="fw-bold">142 documents</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #f3a833;"></i>Expiring Soon</span>
                        <span class="fw-bold">8 documents</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle me-2" style="color: #ff5263;"></i>Expired</span>
                        <span class="fw-bold">6 documents</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-upload text-purple me-2"></i>Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Faculty</label>
                    <select class="form-select form-select-sm">
                        <option value="">Select faculty...</option>
                        <option>Dr. Maria Santos</option>
                        <option>Prof. Luis Tan</option>
                        <option>Prof. Katherine Lim</option>
                        <option>Prof. John Aquino</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Type</label>
                    <select class="form-select form-select-sm">
                        <option>Certificate</option>
                        <option>Contract</option>
                        <option>ID</option>
                        <option>Training</option>
                        <option>Others</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Name</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Enter document name...">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">File</label>
                    <input type="file" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Expiry Date (if applicable)</label>
                    <input type="date" class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success"><i class="fas fa-upload me-1"></i>Upload Document</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Document Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-edit text-purple me-2"></i>Update Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Name</label>
                    <input type="text" class="form-control form-control-sm" value="Employment Contract 2024-2026">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Expiry Date</label>
                    <input type="date" class="form-control form-control-sm" value="2026-12-31">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select class="form-select form-select-sm">
                        <option selected>Valid</option>
                        <option>Expiring Soon</option>
                        <option>Expired</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    new Chart(document.getElementById('docStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Valid', 'Expiring Soon', 'Expired'],
            datasets: [{
                data: [142, 8, 6],
                backgroundColor: ['#2be49b', '#f3a833', '#ff5263'],
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

function viewDocument(id) {
    alert('Viewing document: ' + id);
}
function downloadDocument(id) {
    alert('Downloading document: ' + id);
}
function updateDocument(id) {
    const modal = new bootstrap.Modal(document.getElementById('updateModal'));
    modal.show();
}
function deleteDocument(id) {
    if(confirm('Delete document: ' + id + '?')) {
        alert('Document deleted.');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>