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
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary rounded-pill px-3 fw-medium d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fas fa-user-edit"></i>
            <span>Edit Profile</span>
        </button>
        <button class="btn btn-outline-warning rounded-pill px-3 fw-medium d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fas fa-key"></i>
            <span>Change Password</span>
        </button>
    </div>
</div>

<div id="profileAlert" class="alert d-none rounded-3" role="alert"></div>

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
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="text-body-secondary small mb-2 fw-medium"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex justify-content-center gap-2 mb-2">
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
                            <span class="fw-semibold"><?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body border border-light-subtle">
                            <span class="text-body-secondary small d-block mb-1"><i class="fas fa-phone text-primary me-2"></i>Contact Number</span>
                            <span class="fw-semibold"><?= htmlspecialchars($phone ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
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
                            <span class="fw-semibold text-truncate d-block"><?= htmlspecialchars($address ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Academic & Emergency Information Grid -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary text-body">
            <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-graduation-cap text-primary fs-5"></i>
                    Academic Profile
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Highest Educational Attainment</span>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($educationAttainment ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></h6>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Field of Specialization</span>
                            <span class="fw-semibold"><?= htmlspecialchars($specialization ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Date Hired</span>
                            <span class="fw-semibold"><?= htmlspecialchars($hiredDate ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary text-body">
            <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-phone-alt text-danger fs-5"></i>
                    Emergency Contact
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Contact Person</span>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($emergencyName ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Relationship</span>
                            <span class="fw-semibold"><?= htmlspecialchars($emergencyRelationship ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-body">
                            <span class="text-body-secondary small d-block mb-1">Emergency Phone</span>
                            <span class="fw-semibold"><?= htmlspecialchars($emergencyPhone ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
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
                            <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Address</label>
                            <textarea name="address" class="form-control rounded-3" rows="2"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
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
                        <input type="password" name="current_password" class="form-control rounded-3" placeholder="Enter current password..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control rounded-3" placeholder="Confirm new password..." required>
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

<script>
function showProfileAlert(message, type) {
    const el = document.getElementById('profileAlert');
    el.textContent = message;
    el.className = 'alert alert-' + type + ' rounded-3';
    el.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('saveProfileBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('editProfileForm');
    const formData = new FormData(form);
    const btn = this;
    btn.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/modules/faculty/includes/save-my-profile.php', {
            method: 'POST',
            body: formData,
        });
        const json = await res.json();
        if (!json.ok) {
            showProfileAlert(json.error || 'Could not save changes', 'danger');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('editProfileModal'))?.hide();
        showProfileAlert('Profile updated successfully.' + (json.email_changed ? ' Your login email was also updated.' : ''), 'success');
        setTimeout(() => window.location.reload(), 1200);
    } catch (err) {
        showProfileAlert('Something went wrong. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
    }
});

document.getElementById('savePasswordBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);
    const btn = this;
    btn.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/modules/faculty/includes/change-my-password.php', {
            method: 'POST',
            body: formData,
        });
        const json = await res.json();
        if (!json.ok) {
            showProfileAlert(json.error || 'Could not update password', 'danger');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))?.hide();
        form.reset();
        showProfileAlert('Password updated successfully.', 'success');
    } catch (err) {
        showProfileAlert('Something went wrong. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
    }
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>