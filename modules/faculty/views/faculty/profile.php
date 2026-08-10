<?php
/**
 * Profile
 * Purpose: View and update personal profile (non-sensitive info only)
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Profile';
$activeModule = 'faculty';
$activePage   = 'profile';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-user-circle fs-5"></i>
            </span>
            Faculty Profile
        </h4>
        <p class="text-secondary small mb-0">View and update your personal, academic, and contact details</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary rounded-pill px-3 fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fas fa-user-edit"></i>
            <span>Edit Profile</span>
        </button>
        <button class="btn btn-outline-warning rounded-pill px-3 fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fas fa-key"></i>
            <span>Change Password</span>
        </button>
    </div>
</div>

<!-- Primary Profile Overview -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <!-- Left Column: Avatar & Headline Info -->
            <div class="col-lg-4 text-center border-end-lg pe-lg-4">
                <div class="position-relative d-inline-block mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 110px; height: 110px;">
                        <i class="fas fa-user-graduate fa-4x"></i>
                    </div>
                    <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-2 border-white rounded-circle" title="Active Account"></span>
                </div>
                <h4 class="fw-bold mb-1 text-dark">Prof. Maria Santos</h4>
                <p class="text-secondary small mb-2 fw-medium">College Professor</p>
                <div class="d-flex justify-content-center gap-2 mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                        <i class="fas fa-check-circle me-1"></i>Active Faculty
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                        <i class="fas fa-clock me-1"></i>Full-time
                    </span>
                </div>
            </div>

            <!-- Right Column: Quick Profile Details Grid -->
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-id-card text-primary me-2"></i>Faculty ID</span>
                            <span class="fw-semibold text-dark">F-001</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-envelope text-primary me-2"></i>Email Address</span>
                            <span class="fw-semibold text-dark">msantos@bestlink.edu.ph</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-phone text-primary me-2"></i>Contact Number</span>
                            <span class="fw-semibold text-dark">+63 912 345 6789</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-building text-primary me-2"></i>Department</span>
                            <span class="fw-semibold text-dark">College of Computer Studies</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-award text-primary me-2"></i>Academic Rank</span>
                            <span class="fw-semibold text-dark">Professor</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light bg-opacity-50 border border-light-subtle">
                            <span class="text-muted small d-block mb-1"><i class="fas fa-map-marker-alt text-primary me-2"></i>Address</span>
                            <span class="fw-semibold text-dark text-truncate d-block">123 Main Street, Quezon City</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Academic & Emergency Information Grid -->
<div class="row g-4 mb-4">
    <!-- Academic Information -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-graduation-cap text-primary fs-5"></i>
                    Academic Profile
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Highest Educational Attainment</span>
                            <h6 class="fw-bold text-dark mb-0">Doctor of Philosophy in Computer Science</h6>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Field of Specialization</span>
                            <span class="fw-semibold text-dark">Artificial Intelligence</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Date Hired</span>
                            <span class="fw-semibold text-dark">Jan 15, 2020</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-phone-alt text-danger fs-5"></i>
                    Emergency Contact
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Contact Person</span>
                            <h6 class="fw-bold text-dark mb-0">Juan Santos</h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Relationship</span>
                            <span class="fw-semibold text-dark">Spouse</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Emergency Phone</span>
                            <span class="fw-semibold text-dark">+63 923 456 7890</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <span class="text-muted small d-block mb-1">Residential Address</span>
                            <span class="fw-semibold text-dark">123 Main Street, Quezon City, Metro Manila</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Documents Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-folder-open text-primary fs-5"></i>
            Faculty Documents
        </h6>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="fas fa-upload me-1"></i>Upload Document
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Document Name</th>
                        <th>Type</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-pdf text-danger fs-5"></i>
                                <span class="fw-semibold text-dark">PhD Certificate</span>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">Certificate</span></td>
                        <td class="text-muted">&mdash;</td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Valid</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border rounded-circle"><i class="fas fa-download text-secondary"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-contract text-primary fs-5"></i>
                                <span class="fw-semibold text-dark">Employment Contract</span>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">Contract</span></td>
                        <td class="text-muted">Dec 31, 2026</td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Valid</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border rounded-circle"><i class="fas fa-download text-secondary"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-id-badge text-info fs-5"></i>
                                <span class="fw-semibold text-dark">Faculty ID Copy</span>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">ID Document</span></td>
                        <td class="text-muted">&mdash;</td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Valid</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border rounded-circle"><i class="fas fa-download text-secondary"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-user-edit text-primary"></i>
                    Edit Profile Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info d-flex align-items-center gap-3 rounded-3 mb-4">
                    <i class="fas fa-info-circle fs-4 flex-shrink-0"></i>
                    <div class="small">You can update contact details, residential address, and emergency contact details. Sensitive info requires HR approval.</div>
                </div>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Contact Number</label>
                            <input type="text" class="form-control rounded-3" value="+63 912 345 6789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <input type="email" class="form-control rounded-3" value="msantos@bestlink.edu.ph">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Address</label>
                            <textarea class="form-control rounded-3" rows="2">123 Main Street, Quezon City, Metro Manila</textarea>
                        </div>
                        <hr class="my-2 border-light-subtle">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Emergency Contact Name</label>
                            <input type="text" class="form-control rounded-3" value="Juan Santos">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Emergency Contact Number</label>
                            <input type="text" class="form-control rounded-3" value="+63 923 456 7890">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Relationship</label>
                            <input type="text" class="form-control rounded-3" value="Spouse">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-light-subtle py-3 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-medium">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-key text-warning"></i>
                    Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Password</label>
                        <input type="password" class="form-control rounded-3" placeholder="Enter current password...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <input type="password" class="form-control rounded-3" placeholder="Enter new password...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <input type="password" class="form-control rounded-3" placeholder="Confirm new password...">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-light-subtle py-3 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning text-white rounded-pill px-4 fw-medium">Update Password</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
