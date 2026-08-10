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
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success px-3" onclick="checkIn()">
            <i class="fas fa-file-signature me-1"></i>Check In
        </button>
        <button class="btn btn-warning px-3" onclick="checkOut()">
            <i class="fas fa-sign-out-alt me-1"></i>Check Out
        </button>
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

<div class="row g-4 mb-4">
    <!-- Today's Attendance Status -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between border-bottom-0">
                <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                    <i class="fas fa-calendar-day me-2 fs-5"></i>Today's Attendance Status
                </h6>
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-medium border border-success border-opacity-25">
                    <i class="fas fa-check-circle me-1"></i>On Time
                </span>
            </div>
            <div class="card-body d-flex align-items-center pt-0">
                <div class="row g-3 w-100">
                    <div class="col-12 col-sm-4">
                        <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small d-block fw-semibold mb-1 text-uppercase tracking-wide" style="font-size: 0.75rem;">Check-in Time</span>
                            <h4 class="text-success fw-bold mb-0">7:55 AM</h4>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 rounded-3 bg-body-tertiary border text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small d-block fw-semibold mb-1 text-uppercase tracking-wide" style="font-size: 0.75rem;">Check-out Time</span>
                            <h4 class="text-muted fw-bold mb-0">--:--</h4>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small d-block fw-semibold mb-1 text-uppercase tracking-wide" style="font-size: 0.75rem;">Current Status</span>
                            <h4 class="text-primary fw-bold mb-0">Present</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Records -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent py-3 border-bottom-0">
                <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                    <i class="fas fa-filter me-2 fs-5"></i>Filter Records
                </h6>
            </div>
            <div class="card-body d-flex align-items-center pt-0">
                <form class="row g-3 align-items-end w-100">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small text-body-secondary fw-semibold mb-1">Start Date</label>
                        <input type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small text-body-secondary fw-semibold mb-1">End Date</label>
                        <input type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-body-secondary fw-semibold mb-1">Month</label>
                        <select class="form-select form-select-sm">
                            <option value="">All</option>
                            <option selected>Aug 2025</option>
                            <option>Jul 2025</option>
                            <option>Jun 2025</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-body-secondary fw-semibold mb-1">Status</label>
                        <select class="form-select form-select-sm">
                            <option value="">All</option>
                            <option>Present</option>
                            <option>Late</option>
                            <option>Absent</option>
                            <option>On Leave</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Filter">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-sm" title="Reset">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Attendance History Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-semibold text-primary">
            <i class="fas fa-history me-2"></i>Attendance Logs
        </h6>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-body-secondary d-none d-sm-inline">Show:</span>
            <select class="form-select form-select-sm w-auto">
                <option>10 rows</option>
                <option>25 rows</option>
                <option>50 rows</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 text-body-secondary fw-semibold small">Date</th>
                        <th class="text-body-secondary fw-semibold small">Check-in</th>
                        <th class="text-body-secondary fw-semibold small">Check-out</th>
                        <th class="text-body-secondary fw-semibold small">Status</th>
                        <th class="text-body-secondary fw-semibold small">Total Hours</th>
                        <th class="text-body-secondary fw-semibold small pe-3">Notes / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $attendance = [
                        ['date'=>'Aug 1, 2025','in'=>'7:55 AM','out'=>'--','status'=>'Present','hours'=>'-','notes'=>'On-time check-in'],
                        ['date'=>'Jul 31, 2025','in'=>'8:00 AM','out'=>'5:00 PM','status'=>'Present','hours'=>'9.0 hrs','notes'=>'Regular day'],
                        ['date'=>'Jul 30, 2025','in'=>'8:15 AM','out'=>'5:10 PM','status'=>'Late','hours'=>'8.9 hrs','notes'=>'Heavy morning traffic'],
                        ['date'=>'Jul 29, 2025','in'=>'7:50 AM','out'=>'5:00 PM','status'=>'Present','hours'=>'9.2 hrs','notes'=>'Regular day'],
                        ['date'=>'Jul 28, 2025','in'=>'-','out'=>'-','status'=>'Absent','hours'=>'0 hrs','notes'=>'Sick leave filed'],
                        ['date'=>'Jul 25, 2025','in'=>'8:00 AM','out'=>'5:00 PM','status'=>'Present','hours'=>'9.0 hrs','notes'=>'Regular day'],
                        ['date'=>'Jul 24, 2025','in'=>'7:55 AM','out'=>'5:05 PM','status'=>'Present','hours'=>'9.2 hrs','notes'=>'Regular day'],
                    ];
                    foreach ($attendance as $a) {
                        $badgeTheme = match($a['status']) {
                            'Present' => 'bg-success bg-opacity-10 text-success border-success',
                            'Late' => 'bg-warning bg-opacity-10 text-warning-emphasis border-warning',
                            'Absent' => 'bg-danger bg-opacity-10 text-danger border-danger',
                            default => 'bg-info bg-opacity-10 text-info border-info'
                        };
                        echo <<<HTML
                        <tr>
                            <td class="ps-3 fw-medium">{$a['date']}</td>
                            <td class="small">{$a['in']}</td>
                            <td class="small">{$a['out']}</td>
                            <td><span class="badge border border-opacity-25 rounded-pill px-3 py-1 {$badgeTheme}">{$a['status']}</span></td>
                            <td class="small text-body-secondary">{$a['hours']}</td>
                            <td class="pe-3 small text-body-secondary">{$a['notes']}</td>
                        </tr>
                        HTML;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-body-secondary">Showing 1 to 7 of 22 entries</small>
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

<!-- Check-in Modal with Canvas Signature -->
<div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-semibold fs-6">
                    <i class="fas fa-file-signature me-2"></i>Faculty Check-In Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkInForm" onsubmit="handleCheckIn(event)">
                <div class="modal-body p-4">
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
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Confirm Check In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Check-out Modal -->
<div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-semibold fs-6">
                    <i class="fas fa-sign-out-alt me-2"></i>Faculty Check-Out Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form onsubmit="handleCheckOut(event)">
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <span class="text-body-secondary small d-block">Current Time</span>
                        <h2 class="fw-bold text-warning-emphasis mb-0" id="checkOutTime">--:--</h2>
                    </div>

                    <div class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 text-warning-emphasis rounded-3 small mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Are you sure you want to log out for the day?
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
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
