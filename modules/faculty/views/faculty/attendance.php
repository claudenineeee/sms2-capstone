<?php
/**
 * Attendance
 * Purpose: Manage personal attendance
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Attendance';
$activeModule = 'faculty';
$activePage   = 'attendance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Attendance', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            <i class="fas fa-user-check text-primary me-2"></i>Faculty Attendance
        </h1>
        <p class="text-body-secondary mb-0">Track and manage your daily check-in logs and teaching hours</p>
    </div>    
</div>

<!-- Summary Cards (4-Column Grid) -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-5">
                    <i class="fas fa-percentage"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Attendance Rate</span>
                    <h4 class="fw-bold mb-0">92% <small class="text-muted fs-6">this mo.</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-5">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Days Present</span>
                    <h4 class="fw-bold mb-0">18 <small class="text-muted fs-6">days</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-5">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Late Arrivals</span>
                    <h4 class="fw-bold mb-0">2 <small class="text-muted fs-6">times</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-body p-2">
            <div class="card-body p-2 d-flex align-items-center gap-3">
                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-5">
                    <i class="fas fa-user-times"></i>
                </div>
                <div>
                    <span class="text-body-secondary small d-block fw-medium">Absences</span>
                    <h4 class="fw-bold mb-0">1 <small class="text-muted fs-6">day</small></h4>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Side-by-Side Section: Date Selector & Teacher Subject Attendance -->
<div class="row g-4 mb-4">
    <!-- Left Table: Date Selection with Week/Month Filters -->
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold text-primary text-nowrap">
                    <i class="fas fa-calendar-alt me-2"></i>Attendance Dates
                </h6>
                <div class="d-flex align-items-center">
                    <select class="form-select form-select-sm" style="width: auto;">
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3 text-body-secondary fw-semibold small text-nowrap">Date</th>
                                <th class="text-body-secondary fw-semibold small text-nowrap">Day</th>
                                <th class="text-end pe-3 text-body-secondary fw-semibold small text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dates = [
                                ['date' => 'Aug 01, 2025', 'day' => 'Friday', 'active' => true],
                                ['date' => 'Jul 31, 2025', 'day' => 'Thursday', 'active' => false],
                                ['date' => 'Jul 30, 2025', 'day' => 'Wednesday', 'active' => false],
                                ['date' => 'Jul 29, 2025', 'day' => 'Tuesday', 'active' => false],
                                ['date' => 'Jul 28, 2025', 'day' => 'Monday', 'active' => false],
                                ['date' => 'Jul 25, 2025', 'day' => 'Friday', 'active' => false],
                            ];
                            foreach ($dates as $d): 
                                $activeClass = $d['active'] ? 'table-primary' : '';
                            ?>
                                <tr class="<?= $activeClass ?>" style="cursor: pointer;">
                                    <td class="ps-3 fw-medium small text-nowrap"><?= $d['date'] ?></td>
                                    <td class="small text-body-secondary text-nowrap"><?= $d['day'] ?></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <?php if ($d['active']): ?>
                                            <span class="badge bg-primary rounded-pill">Selected</span>
                                        <?php else: ?>
                                            <button class="btn btn-xs btn-outline-secondary py-0 px-2 style-tiny" style="font-size:0.75rem;">View</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Table: Teacher Class & Subject Attendance Logs -->
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Class Schedule & Status — <span class="text-body-emphasis text-nowrap">Aug 01, 2025</span>
                </h6>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 px-sm-3 py-1 rounded-pill small text-nowrap">
                    <i class="fas fa-check-circle me-1"></i>2 Present / 0 Absent
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-body-secondary fw-semibold small text-nowrap">Time</th>
                                <th class="text-body-secondary fw-semibold small">Subject</th>
                                <th class="text-body-secondary fw-semibold small text-nowrap">Room</th>
                                <th class="pe-3 text-body-secondary fw-semibold small text-end text-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $classes = [
                                ['time' => '08:00 - 09:30 AM', 'code' => 'CS101', 'subject' => 'Intro to Computer Science', 'room' => 'Room 201', 'status' => 'Present'],
                                ['time' => '09:30 - 11:00 AM', 'code' => 'CS401', 'subject' => 'Software Engineering', 'room' => 'Room 203', 'status' => 'Present'],
                                ['time' => '01:00 - 03:00 PM', 'code' => 'CS301', 'subject' => 'Design & Analysis of Algorithms', 'room' => 'Room 301', 'status' => 'Upcoming'],
                            ];
                            foreach ($classes as $c):
                                $statusBadge = match($c['status']) {
                                    'Present' => 'bg-success bg-opacity-10 text-success border-success',
                                    'Absent'  => 'bg-danger bg-opacity-10 text-danger border-danger',
                                    default   => 'bg-secondary bg-opacity-10 text-secondary border-secondary'
                                };
                            ?>
                                <tr>
                                    <td class="ps-3 font-monospace small fw-medium text-body-secondary text-nowrap"><?= $c['time'] ?></td>
                                    <td style="min-width: 160px;">
                                        <div class="fw-bold text-body small text-truncate" style="max-width: 220px;"><?= $c['code'] ?></div>
                                        <div class="text-body-secondary style-tiny text-truncate" style="font-size:0.75rem; max-width: 220px;"><?= $c['subject'] ?></div>
                                    </td>
                                    <td class="small text-nowrap">
                                        <i class="fas fa-map-marker-alt me-1 text-primary"></i><?= $c['room'] ?>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <span class="badge border border-opacity-25 rounded-pill px-2 px-sm-3 py-1 <?= $statusBadge ?>">
                                            <?= $c['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Check-in Modal with Canvas Signature -->
<div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-semibold fs-6">
                    <i class="fas fa-file-signature me-2"></i>Faculty Check-In Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkInForm" onsubmit="handleCheckIn(event)">
                <div class="modal-body p-3 p-sm-4">
                    <div class="text-center mb-3">
                        <span class="text-body-secondary small d-block">Current Time</span>
                        <h2 class="fw-bold text-success mb-0" id="checkInTime">--:--</h2>
                    </div>

                    <div class="alert bg-success bg-opacity-10 border border-success border-opacity-25 text-success rounded-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Logged in as Faculty. Please sign below to verify your check-in.
                    </div>

                    <!-- Signature Pad Area -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small text-body-secondary fw-semibold mb-0">Faculty Confirmation Signature</label>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" onclick="clearSignature()">Clear</button>
                        </div>
                        <div class="border rounded-3 bg-light overflow-hidden position-relative" style="height: 150px;">
                            <canvas id="signatureCanvas" class="w-100 h-100" style="cursor: crosshair; touch-action: none;"></canvas>
                        </div>
                        <input type="hidden" id="signatureData" name="signature" required>
                        <div class="invalid-feedback small">Please provide a signature before confirming.</div>
                    </div>

                    <div class="mb-0">
                        <label for="checkInNotes" class="form-label small text-body-secondary fw-semibold">Notes / Remarks <small class="text-muted fw-normal">(Optional)</small></label>
                        <input type="text" class="form-control form-control-sm" id="checkInNotes" placeholder="e.g., Early arrival for lab setup">
                    </div>
                </div>
                <div class="modal-footer bg-light px-3 px-sm-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Confirm Check In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Check-out Modal -->
<div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-semibold fs-6">
                    <i class="fas fa-sign-out-alt me-2"></i>Faculty Check-Out Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form onsubmit="handleCheckOut(event)">
                <div class="modal-body p-3 p-sm-4">
                    <div class="text-center mb-3">
                        <span class="text-body-secondary small d-block">Current Time</span>
                        <h2 class="fw-bold text-warning-emphasis mb-0" id="checkOutTime">--:--</h2>
                    </div>

                    <div class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 text-warning-emphasis rounded-3 small mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Are you sure you want to log out for the day?
                    </div>
                </div>
                <div class="modal-footer bg-light px-3 px-sm-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">Confirm Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let canvas, ctx;
let isDrawing = false;
let isSigned = false;

function initSignatureCanvas() {
    canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    
    ctx = canvas.getContext('2d');
    
    // Resize canvas internal buffer to fit displayed element size
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    ctx.strokeStyle = "#0d6efd";
    ctx.lineWidth = 2;
    ctx.lineCap = "round";

    // Mouse Events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    // Touch Events for Mobile / Tablets
    canvas.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        startDrawing({ clientX: touch.clientX, clientY: touch.clientY, rect });
    }, { passive: true });

    canvas.addEventListener('touchmove', (e) => {
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        draw({ clientX: touch.clientX, clientY: touch.clientY, rect });
    }, { passive: true });

    canvas.addEventListener('touchend', stopDrawing);
}

function startDrawing(e) {
    isDrawing = true;
    const rect = e.rect || canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!isDrawing) return;
    isSigned = true;
    const rect = e.rect || canvas.getBoundingClientRect();
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
}

function stopDrawing() {
    if (isDrawing) {
        isDrawing = false;
        if (isSigned) {
            document.getElementById('signatureData').value = canvas.toDataURL();
        }
    }
}

function clearSignature() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('signatureData').value = '';
    isSigned = false;
}

function checkIn() {
    const modalEl = document.getElementById('checkInModal');
    const modal = new bootstrap.Modal(modalEl);
    document.getElementById('checkInTime').textContent = new Date().toLocaleTimeString();
    
    modalEl.addEventListener('shown.bs.modal', function () {
        initSignatureCanvas();
        clearSignature();
    }, { once: true });

    modal.show();
}

function checkOut() {
    const modal = new bootstrap.Modal(document.getElementById('checkOutModal'));
    document.getElementById('checkOutTime').textContent = new Date().toLocaleTimeString();
    modal.show();
}

function handleCheckIn(event) {
    event.preventDefault();
    if (!isSigned) {
        alert("Please provide a signature before confirming check-in.");
        return;
    }
    const signatureImageBase64 = document.getElementById('signatureData').value;
    console.log("Submitted Base64 Signature:", signatureImageBase64);
    
    // Insert backend AJAX or form submission here
    bootstrap.Modal.getInstance(document.getElementById('checkInModal')).hide();
}

function handleCheckOut(event) {
    event.preventDefault();
    // Insert backend AJAX or form submission here
    bootstrap.Modal.getInstance(document.getElementById('checkOutModal')).hide();
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>