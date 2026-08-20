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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-1">
            <i class="fas fa-folder text-primary me-2"></i>Documents
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
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Total Documents</span>
                    <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 fs-5" style="width: 42px; height: 42px;">
                        <i class="fas fa-file"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">156</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Valid</span>
                    <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-3 fs-5" style="width: 42px; height: 42px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">142</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Expiring Soon</span>
                    <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-3 fs-5" style="width: 42px; height: 42px;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0">8</h3>
                    <span class="badge bg-warning-subtle text-warning rounded-pill small">Action required</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Expired</span>
                    <div class="d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-3 fs-5" style="width: 42px; height: 42px;">
                        <i class="fas fa-times-circle"></i>
                    </div>
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
                <div class="card bg-white border border-start-0 border-top-0 border-bottom-0 border-4 border-warning shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle bg-dark text-primary border border-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-0 fs-6"><?= $e['faculty'] ?></h6>
                    </div>
                    <p class="small text-muted mb-2"><?= $e['doc'] ?></p>
                    <div>
                        <span class="badge bg-white text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-bold">
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
                <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Document Repository (156)</h6>
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
                                    'Valid'         => 'bg-white text-success border border-success-subtle',
                                    'Expiring Soon' => 'bg-white text-warning border border-warning-subtle',
                                    default         => 'bg-white text-danger border border-danger-subtle'
                                };

                                $typeBadge = match($d['type']) {
                                    'Contract'    => 'bg-white text-primary border border-primary-subtle',
                                    'Certificate' => 'bg-white text-info border border-info-subtle',
                                    'Training'    => 'bg-white text-success border border-success-subtle',
                                    default       => 'bg-white text-secondary border border-secondary-subtle'
                                };
                            ?>
                            <tr>
                                <td class="ps-3 small font-monospace text-body-secondary"><?= $d['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <span class="fw-semibold small"><?= $d['faculty'] ?></span>
                                    </div>
                                </td>
                                <td><span class="badge <?= $typeBadge ?> rounded-pill px-3 py-1 fw-bold"><?= $d['type'] ?></span></td>
                                <td>
                                    <div class="small fw-semibold"><?= $d['name'] ?></div>
                                    <small class="text-body-secondary d-block" style="font-size: 0.75rem;">Uploaded: <?= $d['upload'] ?></small>
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
                <small class="text-body-secondary">Showing 1-7 of 156 documents</small>
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
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-primary me-2"></i>Status Overview</h6>
            </div>
            <div class="card-body">
                <div class="position-relative d-flex justify-content-center mb-3">
                    <canvas id="docStatusChart" style="max-height: 200px;"></canvas>
                </div>
                <hr>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-success me-2"></i>Valid</span>
                        <span class="fw-bold">142 documents</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-warning me-2"></i>Expiring Soon</span>
                        <span class="fw-bold">8 documents</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-danger me-2"></i>Expired</span>
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
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-upload text-primary me-2"></i>Upload Document</h5>
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
                <h5 class="modal-title h6 fw-bold"><i class="fas fa-edit text-primary me-2"></i>Update Document</h5>
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
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
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