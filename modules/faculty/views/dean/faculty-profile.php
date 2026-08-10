<?php
/**
 * SMS 2 - Faculty Profile
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Faculty Profile';
$activeModule = 'faculty';
$activePage   = 'faculty-profile';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

echo '<link rel="stylesheet" href="' . BASE_URL . '/modules/faculty/assets/css/faculty.css">';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- 1. Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Faculty Profile</h1>
    </div>
</div>

<!-- Faculty List Section -->
<div class="container-fluid my-4">
    <div class="card shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
    
    <!-- Right-aligned Search Bar & Action Button -->
    <div class="col-12 col-md-4 col-lg-3">  
    <div class="input-group input-group-sm">
        <span class="input-group-text bg-light text-muted border-end-0">🔍</span>
        <input type="text" id="facultySearch" class="form-control border-start-0 ps-0" placeholder="Search faculty..." onkeyup="searchFaculty()">
    </div>
    </div>
</div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;">Photo</th>
                            <th style="width: 140px;">Faculty ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th style="width: 120px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Mock Row 1: Active -->
                        <tr>
                            <td>
                                <!-- Pure HTML/CSS Circular Avatar with Initials -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold shadow-sm" 
                                    style="width: 40px; height: 40px; font-size: 14px; min-width: 40px;">
                                    JD
                                </div>
                            </td>
                            <td class="fw-bold">FAC-2026-001</td>
                            <td>John Doe</td>
                            <td><span class="badge bg-secondary">BSIT</span></td>
                            <td>Head</td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewFaculty('FAC-2026-001')" title="View">👁️</button>
                                    <button class="btn btn-outline-warning" onclick="editFaculty('FAC-2026-001')" title="Edit">✎</button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Mock Row 2: On Leave -->
                        <tr>
                            <td>
                                <!-- Another clean circle placeholder with a different color theme -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info text-dark fw-bold shadow-sm" 
                                    style="width: 40px; height: 40px; font-size: 14px; min-width: 40px;">
                                    JS
                                </div>
                            </td>
                            <td class="fw-bold">FAC-2026-002</td>
                            <td>Jane Smith</td>
                            <td><span class="badge bg-secondary">BSTM</span></td>
                            <td>Teacher</td>
                            <td><span class="badge bg-warning text-dark">On Leave</span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewFaculty('FAC-2026-002')" title="View">👁️</button>
                                    <button class="btn btn-outline-warning" onclick="editFaculty('FAC-2026-002')" title="Edit">✎</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Faculty Modal -->
<div id="facultyModal" class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Faculty Profile Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Photo Column -->
                    <div class="col-md-4 text-center border-end">
                        <img src="assets/images/default-avatar.png" class="img-fluid rounded-circle mb-3 shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="Faculty Avatar">
                        <h4 class="h5 fw-bold mb-1">John Doe</h4>
                        <span class="badge bg-success">Active</span>
                    </div>
                    
                    <!-- Info Column -->
                    <div class="col-md-8">
                        <h6 class="text-primary border-bottom pb-2 fw-bold">Basic Information</h6>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Faculty ID:</div>
                            <div class="col-sm-8">FAC-2026-001</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Department:</div>
                            <div class="col-sm-8">BSIT</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 fw-bold">Position:</div>
                            <div class="col-sm-8">Head</div>
                        </div>

                        <h6 class="text-primary border-bottom pb-2 fw-bold">Contact Information</h6>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Email:</div>
                            <div class="col-sm-8">johndoe@university.edu</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Phone:</div>
                            <div class="col-sm-8">+63 912 345 6789</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 fw-bold">Address:</div>
                            <div class="col-sm-8">Quezon City, Metro Manila</div>
                        </div>

                        <h6 class="text-primary border-bottom pb-2 fw-bold">Teaching Schedule</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                <strong>Monday</strong>
                                <span class="badge bg-light text-dark border">08:00 AM - 12:00 PM</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                <strong>Wednesday</strong>
                                <span class="badge bg-light text-dark border">01:00 PM - 05:00 PM</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit/Add Faculty Modal -->
<div id="facultyFormModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Manage Faculty Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="facultyForm" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <!-- Basic Info Section -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Basic Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" id="firstname" class="form-control" placeholder="e.g. John" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" id="lastname" class="form-control" placeholder="e.g. Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select id="department" class="form-select">
                                    <option value="BSIT">BSIT</option>
                                    <option value="BSTM">BSTM</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position</label>
                                <select id="position" class="form-select">
                                    <option value="head">Head</option>
                                    <option value="sec">Sec</option>
                                    <option value="co-ord">Co-ord</option>
                                    <option value="nstp">NSTP</option>
                                    <option value="teacher">Teacher</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Contact Information</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" class="form-control" rows="2" placeholder="Complete address..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" class="form-control" placeholder="name@university.edu">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" id="phone" class="form-control" placeholder="e.g. 09123456789">
                            </div>
                        </div>
                    </div>

                    <!-- Document Upload Section -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 fw-bold mb-3">Academic Requirements</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="bachelor_degree" class="form-label">Bachelor Degree File</label>
                                <input type="file" id="bachelor_degree" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="resume" class="form-label">Resume File</label>
                                <input type="file" id="resume" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Simple Bootstrap Functionality JS Trigger Scripts -->
<script>
    // Initialize standard Bootstrap triggers programmatically
    function addNewFaculty() {
        let formModal = new bootstrap.Modal(document.getElementById('facultyFormModal'));
        formModal.show();
    }

    function viewFaculty(id) {
        let viewModal = new bootstrap.Modal(document.getElementById('facultyModal'));
        viewModal.show();
    }

    function editFaculty(id) {
        let formModal = new bootstrap.Modal(document.getElementById('facultyFormModal'));
        formModal.show();
    }

    function searchFaculty() {
    const filter = document.getElementById('facultySearch').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        // Displays row if text matches search input, hides it if not
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
    function toggleScheduleRow(checkbox) {
        const row = checkbox.closest('.schedule-row');
        if (!row) return;
        const timeInputs = row.querySelectorAll('input[type="time"]');
        timeInputs.forEach(input => {
            input.disabled = !checkbox.checked;
            if (!checkbox.checked) input.value = '';
        });
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
