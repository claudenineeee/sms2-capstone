<?php
/**
 * Faculty Directory
 * Purpose: View and manage department faculty members
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$pageTitle    = 'Faculty Directory';
$activeModule = 'faculty';
$activePage   = 'faculty-directory';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Department Head', 'url' => BASE_URL . '/modules/faculty/users/head/index.php'],
    ['label' => 'Faculty Directory', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';

require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-users text-purple me-2"></i>Faculty Directory</h1>
        <p class="text-muted mb-0">View and manage department faculty members</p>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100">
    
    <!-- Export Excel Button -->
    <button class="btn btn-sm btn-outline-success flex-fill flex-sm-grow-0 py-2 px-3 d-inline-flex align-items-center justify-content-center">
        <i class="fas fa-file-excel me-1.5"></i>
        <span><span class="d-none d-sm-inline">Export </span>Excel</span>
    </button>

    <!-- Export PDF Button -->
    <button class="btn btn-sm btn-outline-danger flex-fill flex-sm-grow-0 py-2 px-3 d-inline-flex align-items-center justify-content-center">
        <i class="fas fa-file-pdf me-1.5"></i>
        <span><span class="d-none d-sm-inline">Export </span>PDF</span>
    </button>

</div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Search Faculty</label>
                <input type="text" class="form-control" placeholder="Name or ID...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Specialization</label>
                <select class="form-select">
                    <option value="">All</option>
                    <option>Computer Science</option>
                    <option>Information Technology</option>
                    <option>Information Systems</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Employment Status</label>
                <select class="form-select">
                    <option value="">All</option>
                    <option>Active</option>
                    <option>On Leave</option>
                    <option>Probationary</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Rank</label>
                <select class="form-select">
                    <option value="">All</option>
                    <option>Instructor</option>
                    <option>Assistant Professor</option>
                    <option>Associate Professor</option>
                    <option>Professor</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-sms-primary flex-grow-1"><i class="fas fa-search me-1"></i>Search</button>
                    <button class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>Reset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Faculty Table -->
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-list text-purple me-2"></i>Faculty List (18)</h6>
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
                        <th>Faculty ID</th>
                        <th>Photo</th>
                        <th>Full Name</th>
                        <th>Rank</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th>Current Load</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $faculty = [
                        ['id'=>'F-001','name'=>'Dr. Maria Santos','rank'=>'Professor','spec'=>'Computer Science','status'=>'Active','load'=>24,'img'=>'fas fa-user-circle fa-2x text-primary'],
                        ['id'=>'F-002','name'=>'Prof. Luis Tan','rank'=>'Associate Professor','spec'=>'Information Technology','status'=>'Active','load'=>22,'img'=>'fas fa-user-circle fa-2x text-success'],
                        ['id'=>'F-003','name'=>'Prof. Katherine Lim','rank'=>'Assistant Professor','spec'=>'Information Systems','status'=>'Active','load'=>21,'img'=>'fas fa-user-circle fa-2x text-warning'],
                        ['id'=>'F-004','name'=>'Prof. John Aquino','rank'=>'Instructor','spec'=>'Computer Science','status'=>'Active','load'=>18,'img'=>'fas fa-user-circle fa-2x text-info'],
                        ['id'=>'F-005','name'=>'Dr. Ana Reyes','rank'=>'Professor','spec'=>'Information Technology','status'=>'On Leave','load'=>0,'img'=>'fas fa-user-circle fa-2x text-secondary'],
                        ['id'=>'F-006','name'=>'Prof. Sarah Martinez','rank'=>'Assistant Professor','spec'=>'Computer Science','status'=>'Active','load'=>15,'img'=>'fas fa-user-circle fa-2x text-danger'],
                        ['id'=>'F-007','name'=>'Prof. Roberto Villanueva','rank'=>'Instructor','spec'=>'Information Systems','status'=>'Active','load'=>12,'img'=>'fas fa-user-circle fa-2x text-purple'],
                    ];
                    foreach ($faculty as $f) {
                        $statusBadge = $f['status'] === 'Active' ? 'bg-success' : 'bg-warning';
                        echo <<<HTML
                        <tr>
                            <td class="small fw-semibold text-muted">{$f['id']}</td>
                            <td><i class="{$f['img']}"></i></td>
                            <td>{$f['name']}</td>
                            <td>{$f['rank']}</td>
                            <td>{$f['spec']}</td>
                            <td><span class="badge {$statusBadge} rounded-pill">{$f['status']}</span></td>
                            <td>{$f['load']} units</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="View Profile" onclick="viewProfile('{$f['id']}')"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-outline-success" title="View Load" onclick="viewLoad('{$f['id']}')"><i class="fas fa-book"></i></button>
                                    <button class="btn btn-outline-info" title="View Performance" onclick="viewPerformance('{$f['id']}')"><i class="fas fa-chart-line"></i></button>
                                </div>
                            </td>
                        </tr>
                        HTML;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing 1-7 of 18 faculty</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- View Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user text-purple me-2"></i>Faculty Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                        <h5 id="modalName">Dr. Maria Santos</h5>
                        <p class="text-muted mb-0">Professor</p>
                        <span class="badge bg-success rounded-pill">Active</span>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-info-circle text-purple me-2"></i>Personal Information</h6>
                                <table class="table table-sm mb-0">
                                    <tr><td width="40%">Faculty ID:</td><td id="modalId">F-001</td></tr>
                                    <tr><td>Email:</td><td>msantos@bestlink.edu.ph</td></tr>
                                    <tr><td>Contact:</td><td>+63 912 345 6789</td></tr>
                                    <tr><td>Department:</td><td>College of Computer Studies</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-graduation-cap text-purple me-2"></i>Academic Background</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-check text-success me-2"></i>Ph.D. in Computer Science</li>
                                    <li><i class="fas fa-check text-success me-2"></i>M.S. in Information Technology</li>
                                    <li><i class="fas fa-check text-success me-2"></i>B.S. in Computer Science</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sms-primary" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/head/pages/faculty-profile.php'">View Full Profile</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewProfile(id) {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    modal.show();
}
function viewLoad(id) {
    window.location.href = '<?= BASE_URL ?>/modules/faculty/users/head/pages/teaching-load-approval.php';
}
function viewPerformance(id) {
    window.location.href = '<?= BASE_URL ?>/modules/faculty/users/head/pages/faculty-performance.php';
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
