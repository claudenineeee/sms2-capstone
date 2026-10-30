<?php
/**
 * My Profile — shared across every faculty-module role (dean, department head,
 * secretary, faculty). Always shows only the logged-in user's OWN record,
 * found via faculty_profiles.user_id = the current session's user_id.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
requireAuth();

require_once __DIR__ . '/../controllers/FacultyController.php';
$facultyController = new FacultyController();

$profile = $facultyController->getMyProfile();

$pageTitle    = 'Faculty Profile';
$activeModule = 'faculty';
$activePage   = 'my-profile';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
require_once __DIR__ . '/../../../includes/nav-icons.php';

$fullName = $profile
    ? trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? '') . ' ' . ($profile['suffix'] ?? ''))
    : getCurrentUserName();
$departmentLabel = $profile ? FacultyController::getDepartmentLabel((string) ($profile['designated_department'] ?? '')) : 'N/A';
$position = $profile['position'] ?? 'Faculty';
$academicRank = $profile['academic_rank'] ?? '';
$facultyId = $profile['faculty_id'] ?? '';
$email = $profile['email'] ?? '';
$phone = $profile['phone'] ?? '';
$specialization = $profile['specialization_assignment'] ?? '';
$educationAttainment = $profile['education_attainment'] ?? '';
$address = $profile['address'] ?? '';
$hiredDate = $profile['hired_date'] ?? '';
$profileStatus = $profile['profile_status'] ?? 'Active';
$employmentStatus = $profile['employment_status'] ?? 'Full-time';
$emergencyName = $profile['emergency_contact_name'] ?? '';
$emergencyPhone = $profile['emergency_contact_phone'] ?? '';
$emergencyRelationship = $profile['emergency_relationship'] ?? '';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<!-- Toast Container (Bottom Right) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="profileToastContainer" style="z-index: 1090;"></div>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if (!$profile): ?>
    <div class="alert alert-warning rounded-3 shadow-sm border-0">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> No faculty profile is linked to your account yet. Please contact your Dean or HR to have your account linked to a faculty record.
    </div>
<?php else: ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-user-circle fs-5"></i>
            </span>
            Faculty Profile
        </h4>
        <p class="text-body-secondary small mb-0">View and update your personal, academic, and contact details</p>
    </div>
    <div class="d-flex align-items-center gap-2 profile-actions">
        <button class="btn btn-primary rounded-pill px-3 fw-medium d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fas fa-user-edit"></i>
            <span class="d-none d-sm-inline">Edit Profile</span>
        </button>
        <button class="btn btn-outline-warning rounded-pill px-3 fw-medium d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fas fa-key"></i>
            <span class="d-none d-sm-inline">Change Password</span>
        </button>
    </div>
</div>

<!-- Primary Profile Overview -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-body-tertiary text-body">
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-4 text-center border-end-lg pe-lg-4">
                <div class="position-relative d-inline-block mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 110px; height: 110px;">
                        <i class="fas fa-user-graduate fa-4x"></i>
                    </div>
                    <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-2 border-white rounded-circle" title="Active Account"></span>
                </div>
                <h4 class="fw-bold mb-1" id="display-fullname"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="text-body-secondary small mb-2 fw-medium"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex justify-content-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                        <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars(ucfirst($profileStatus), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                        <i class="fas fa-clock me-1"></i><?= htmlspecialchars(ucfirst($employmentStatus), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-id-card text-primary me-2"></i>Faculty ID</span>
                            <span class="fw-semibold"><?= htmlspecialchars((string) $facultyId, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-envelope text-primary me-2"></i>Email Address</span>
                            <span class="fw-semibold" id="display-email"><?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-phone text-primary me-2"></i>Contact Number</span>
                            <span class="fw-semibold" id="display-phone"><?= htmlspecialchars($phone ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-building text-primary me-2"></i>Department</span>
                            <span class="fw-semibold"><?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-award text-primary me-2"></i>Academic Rank</span>
                            <span class="fw-semibold"><?= htmlspecialchars($academicRank ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-map-marker-alt text-primary me-2"></i>Address</span>
                            <span class="fw-semibold text-truncate d-block" id="display-address"><?= htmlspecialchars($address ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </div>
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
                    <div class="small">Changing your email also updates your login username, since they're the same value in this system.</div>
                </div>
                <form id="editProfileForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Contact Number</label>
                            <input type="text" name="phone" id="input-phone" class="form-control rounded-3" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <input type="email" name="email" id="input-email" class="form-control rounded-3" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Address</label>
                            <textarea name="address" id="input-address" class="form-control rounded-3" rows="2"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <hr class="my-2 border-light-subtle">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control rounded-3" value="<?= htmlspecialchars($emergencyName, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Emergency Contact Number</label>
                            <input type="text" name="emergency_contact_phone" class="form-control rounded-3" value="<?= htmlspecialchars($emergencyPhone, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Relationship</label>
                            <input type="text" name="emergency_relationship" class="form-control rounded-3" value="<?= htmlspecialchars($emergencyRelationship, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-light-subtle py-3 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveProfileBtn" class="btn btn-primary rounded-pill px-4 fw-medium">Save Changes</button>
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
                <form id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Password</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" class="form-control rounded-3 pe-5" placeholder="Enter current password..." required>
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted toggle-password" style="z-index: 5; text-decoration: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <div class="position-relative">
                            <input type="password" name="new_password" class="form-control rounded-3 pe-5" placeholder="Enter new password..." required>
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted toggle-password" style="z-index: 5; text-decoration: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" class="form-control rounded-3 pe-5" placeholder="Confirm new password..." required>
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted toggle-password" style="z-index: 5; text-decoration: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-light-subtle py-3 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="savePasswordBtn" class="btn btn-warning text-white rounded-pill px-4 fw-medium">Update Password</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* 344px Responsive Fixes */
    @media (max-width: 400px) {
        #page-content, .container-fluid { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        h4.fw-bold { font-size: 1.15rem !important; }
        .profile-actions .btn { padding: 0.4rem 0.65rem !important; font-size: 0.78rem; }
        .card-body.p-4 { padding: 1rem !important; }
        .bg-primary.bg-opacity-10.rounded-circle { width: 80px !important; height: 80px !important; }
        .bg-primary.bg-opacity-10.rounded-circle i { font-size: 2.5rem !important; }
        .badge { font-size: 0.65rem; padding: 0.25rem 0.5rem !important; }
        .p-3.rounded-3.bg-body { padding: 0.6rem !important; }
        .modal-body.p-4 { padding: 1rem !important; }
        .modal-footer { padding: 0.75rem 1rem !important; }
        .modal-footer .btn { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    }
</style>

<script>
// ---------------------------------------------------------
// Toast Notification System (Bottom Right)
// ---------------------------------------------------------
function showProfileToast(message, type = 'success') {
    const container = document.getElementById('profileToastContainer');
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
    const bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
    
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white ${bgClass} border-0 shadow`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 fw-medium">
                <i class="fas ${icon}"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    container.appendChild(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

// ---------------------------------------------------------
// Save Profile (AJAX without reload)
// ---------------------------------------------------------
document.getElementById('saveProfileBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('editProfileForm');
    const formData = new FormData(form);
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';

    try {
        const res = await fetch('<?= BASE_URL ?>/modules/faculty/includes/save-my-profile.php', {
            method: 'POST',
            body: formData,
        });
        
        // 1. Get the raw text first
        const text = await res.text();
        let json;
        
        // 2. Try to parse it safely
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error("PHP returned invalid JSON. Here is what it returned:", text);
            showProfileToast('Server error. Check the console for details.', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }
        
        if (!json.ok) {
            showProfileToast(json.error || 'Could not save changes', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }

        // Update the UI dynamically without a page reload
        const newEmail = document.getElementById('input-email').value;
        const newPhone = document.getElementById('input-phone').value;
        const newAddress = document.getElementById('input-address').value;

        document.getElementById('display-email').textContent = newEmail || 'N/A';
        document.getElementById('display-phone').textContent = newPhone || 'N/A';
        document.getElementById('display-address').textContent = newAddress || 'Not set';

        // Hide modal & show toast
        bootstrap.Modal.getInstance(document.getElementById('editProfileModal'))?.hide();
        showProfileToast('Profile updated successfully.' + (json.email_changed ? ' Login email updated.' : ''), 'success');
        
        // Reset button
        btn.disabled = false;
        btn.innerHTML = originalText;
        
    } catch (err) {
        console.error(err);
        showProfileToast('Network error. Please try again.', 'danger');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// ---------------------------------------------------------
// Change Password (AJAX without reload)
// ---------------------------------------------------------
document.getElementById('savePasswordBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);
    const btn = this;
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...';

    try {
        const res = await fetch('<?= BASE_URL ?>/includes/change-my-password.php', {
            method: 'POST',
            body: formData,
        });
        
        const text = await res.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error("PHP returned non-JSON:", text);
            showProfileToast('Server error. Check console for details.', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }
        
        if (!json.ok) {
            showProfileToast(json.error || 'Could not update password', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }
        
        // Hide modal, reset form, show toast
        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))?.hide();
        form.reset();
        showProfileToast('Password updated successfully.', 'success');
        
    } catch (err) {
        console.error(err);
        showProfileToast('Something went wrong. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// ---------------------------------------------------------
// Toggle Password Visibility
// ---------------------------------------------------------
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>