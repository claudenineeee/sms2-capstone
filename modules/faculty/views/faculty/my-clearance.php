<?php
/**
 * My Clearance Requirements
 * Purpose: Upload required clearance documents for department review
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'My Clearance';
$activeModule = 'faculty';
$activePage   = 'my-clearance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'My Clearance', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid p-4">

    <!-- Header & Overall Clearance Status -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="fas fa-file-upload text-primary me-2"></i>Faculty Clearance Portal
            </h3>
            <p class="text-body-secondary small mb-0">AS-IS Process: Receive contract notifications, submit intent letters, and complete clearance requirements.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm" id="facultyStatusBadge">
                <i class="fas fa-clock me-1"></i> Action Required: Pending Resubmission
            </span>
        </div>
    </div>

    <!-- Feedback Alert Container -->
    <div id="facultyAlert" class="alert alert-success alert-dismissible fade show d-none mb-4 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i><span id="facultyAlertMessage">Requirement submitted successfully.</span>
        <button type="button" class="btn-close" onclick="dismissFacultyAlert()" aria-label="Close"></button>
    </div>

    <!-- Section 1: RECEIVE NOTICE / NOTIFICATION -->
    <div class="card bg-body-tertiary border shadow-sm mb-4">
        <div class="card-header bg-body-tertiary border-bottom py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-uppercase small text-primary" style="letter-spacing: 0.5px;">
                <i class="fas fa-envelope-open-text me-2"></i>RECEIVE NOTICE / NOTIFICATION
            </span>
            <span class="badge bg-info-subtle text-info border border-info-subtle">Flowchart Stage 1</span>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="fw-bold text-body-secondary text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Contract Expiration Clearance Progress</h6>
                    <h4 class="fw-bold mb-2" id="progressTitle">2 of 4 Requirements Completed</h4>
                    <div class="progress bg-secondary-subtle" style="height: 10px;">
                        <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" id="facultyProgressBar" style="width: 50%;"></div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0 border-start-md">
                    <small class="text-body-secondary d-block">Contract Expiry Date</small>
                    <span class="fw-bold text-danger fs-5"><i class="fas fa-calendar-alt me-1"></i> Oct 20, 2026</span>
                    <small class="text-body-secondary d-block mt-1">Notification sent by Head / HR</small>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW FEATURE: SUBMIT INTENT LETTER TO DEPT HEAD -->
    <div class="card bg-body-tertiary border shadow-sm mb-4">
        <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                <i class="fas fa-paper-plane me-2 text-primary"></i>LETTER OF INTENT SUBMISSION
            </h6>
            <span class="badge bg-secondary-subtle text-body-secondary" id="intentStatusBadge">Status: Not Submitted</span>
        </div>
        <div class="card-body p-4">
            <p class="text-body-secondary small mb-3">
                Prior to clearance completion, please indicate your intention regarding your contract end date and submit your official Intent Letter to the Department Head.
            </p>

            <form id="intentLetterForm" onsubmit="submitIntentLetter(event)">
                <div class="row g-3">
                    <!-- Intent Decision -->
                    <div class="col-md-5">
                        <label for="intentChoice" class="form-label fw-semibold small">Statement of Intent <span class="text-danger">*</span></label>
                        <select class="form-select" id="intentChoice" required>
                            <option value="" selected disabled>-- Select Your Intent --</option>
                            <option value="renewal">I intend to apply for Contract Renewal / Extension</option>
                            <option value="resignation">I do NOT intend to renew (Proceed with Clearance)</option>
                            <option value="regularization">I am applying for Regularization</option>
                        </select>
                    </div>

                    <!-- File Upload -->
                    <div class="col-md-7">
                        <label for="intentFile" class="form-label fw-semibold small">Upload Signed Intent Letter (.pdf, .docx) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="intentFile" accept=".pdf,.doc,.docx" required>
                        
                    </div>

                    <!-- Remarks / Message -->
                    <div class="col-12">
                        <label for="intentRemarks" class="form-label fw-semibold small">Additional Message for Dept. Head <small class="text-body-secondary">(Optional)</small></label>
                        <textarea class="form-control" id="intentRemarks" rows="2" placeholder="Write any notes or comments for your Department Head..."></textarea>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary px-4 py-2" id="btnSubmitIntent">
                        <i class="fas fa-paper-plane me-1"></i> Submit Intent Letter
                    </button>
                </div>
            </form>

            <!-- Display Area after submission -->
            <div id="intentSubmittedView" class="d-none alert alert-success-subtle border border-success-subtle p-3 rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold text-success d-block"><i class="fas fa-check-circle me-1"></i> Intent Letter Submitted</span>
                        <small class="text-body-secondary" id="intentSubmittedMeta">Submitted on Oct 05, 2026 • File: intent_letter.pdf</small>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleEditIntent()">
                        <i class="fas fa-edit me-1"></i> Update Submission
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: SUBMIT CLEARANCE REQUIREMENTS -->
    <div class="card bg-body-tertiary border shadow-sm">
        <div class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                <i class="fas fa-tasks me-2 text-primary"></i>SUBMIT CLEARANCE REQUIREMENTS
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Faculty Swimlane</span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">

                <!-- Requirement 1: Approved -->
                <div class="list-group-item bg-body-tertiary p-4 border-start ">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="fw-bold fs-6">1. Final Grades Submission</div>
                            <small class="text-body-secondary">Submitted on Oct 01, 2026 • File: <code class="text-primary">grades_q1.pdf</code></small>
                        </div>
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
                                <i class="fas fa-check-circle me-1"></i> Verified & Approved
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Requirement 2: Approved -->
                <div class="list-group-item bg-body-tertiary p-4 border-start">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="fw-bold fs-6">2. Department Property Turnover</div>
                            <small class="text-body-secondary">Submitted on Oct 02, 2026 • File: <code class="text-primary">property_receipt.pdf</code></small>
                        </div>
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
                                <i class="fas fa-check-circle me-1"></i> Verified & Approved
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Requirement 3: Missing / Initial Upload -->
                <div class="list-group-item bg-body-tertiary p-4 border-start" id="item3Row">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6 text-primary">3. Library Clearance Slip</div>
                            <small class="text-body-secondary d-block mb-2">Upload official signed clearance from the central library.</small>
                            <div class="input-group input-group-sm max-w-md">
                                <input type="file" id="fileRequirement3" class="form-control" accept=".pdf,.png,.jpg">
                            </div>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <button class="btn btn-primary btn-sm px-4 py-2" onclick="uploadRequirement('item3')">
                                <i class="fas fa-upload me-1"></i> Upload File
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Requirement 4: Pending Resubmission -->
                <div class="list-group-item bg-body-tertiary p-4 border-start border-bottom-0" id="item4Row">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6 text-danger">4. LMS Module Turn-Over</div>
                            <div class="alert alert-danger-subtle text-danger border border-danger-subtle py-2 px-3 mb-2 small rounded">
                                <i class="fas fa-exclamation-triangle me-1"></i> <strong>Dept Head Note:</strong> Previous file was corrupted or incomplete. Please resubmit pending requirement.
                            </div>
                            <div class="input-group input-group-sm max-w-md">
                                <input type="file" id="fileRequirement4" class="form-control" accept=".pdf,.zip">
                            </div>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <button class="btn btn-danger btn-sm px-4 py-2" onclick="uploadRequirement('item4')">
                                <i class="fas fa-sync-alt me-1"></i> Resubmit File
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
/**
 * Submit Intent Letter Function
 */
function submitIntentLetter(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('intentFile');
    const intentChoice = document.getElementById('intentChoice').value;
    
    if (fileInput.files.length === 0) {
        alert('Please select an intent letter file to upload.');
        return;
    }

    const fileName = fileInput.files[0].name;

    // Show submitted view
    document.getElementById('intentLetterForm').classList.add('d-none');
    document.getElementById('intentSubmittedView').classList.remove('d-none');
    document.getElementById('intentSubmittedMeta').textContent = `File: ${fileName} | Choice: ${intentChoice.toUpperCase()}`;

    // Update Status Badge
    const badge = document.getElementById('intentStatusBadge');
    badge.className = 'badge bg-success-subtle text-success border border-success-subtle';
    badge.textContent = 'Status: Submitted to Dept. Head';

    showFacultyAlert(`Intent letter "${fileName}" has been sent to your Department Head.`);
}

function toggleEditIntent() {
    document.getElementById('intentLetterForm').classList.remove('d-none');
    document.getElementById('intentSubmittedView').classList.add('d-none');
}

/**
 * Submit Clearance Requirement
 */
function uploadRequirement(itemKey) {
    const fileInput = document.getElementById(`fileRequirement${itemKey === 'item3' ? '3' : '4'}`);
    
    if (!fileInput || fileInput.files.length === 0) {
        alert('Please select a file to upload first.');
        return;
    }

    const fileName = fileInput.files[0].name;

    showFacultyAlert(`Successfully uploaded "${fileName}". Status updated to "Under Review".`);

    document.getElementById('facultyStatusBadge').className = 'badge bg-info text-dark fs-6 px-3 py-2 shadow-sm';
    document.getElementById('facultyStatusBadge').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Under Dept Head Review';
    
    document.getElementById('facultyProgressBar').className = 'progress-bar bg-info progress-bar-striped progress-bar-animated';
    document.getElementById('facultyProgressBar').style.width = '75%';
    document.getElementById('progressTitle').textContent = '3 of 4 Requirements Submitted';
}

function showFacultyAlert(msg) {
    const box = document.getElementById('facultyAlert');
    document.getElementById('facultyAlertMessage').textContent = msg;
    box.classList.remove('d-none');
}

function dismissFacultyAlert() {
    document.getElementById('facultyAlert').classList.add('d-none');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
