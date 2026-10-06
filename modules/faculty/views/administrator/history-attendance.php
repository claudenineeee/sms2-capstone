<?php
/**
 * SMS 2 - Faculty Admin Dashboard
 * Attendance History — with per-user print PIN
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$pageTitle    = 'Faculty Admin Dashboard';
$activeModule = 'faculty';
$activePage   = 'attendance-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

// Check if the current user already has a print PIN
$attHasPinSet = false;
try {
    if (function_exists('facultyDb')) {
        $userId = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);
        $attDb  = facultyDb();
        if ($userId && $attDb instanceof PDO) {
            $stmt = $attDb->prepare("SELECT pin_hash FROM user_pins WHERE external_user_id = ? LIMIT 1");
            $stmt->execute([(string) $userId]);
            $attRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $attHasPinSet = !empty($attRow['pin_hash']);
        }
    }
} catch (Throwable $e) { /* silent */ }

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="attDashboard">

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Attendance History</h1>
            <p class="text-body-secondary mb-0">Track faculty presence, punctuality, and leave across the current school year.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="attManagePinBtn" type="button">
                <i class="fas fa-key"></i>
                <span><?= $attHasPinSet ? 'Change PIN' : 'Set Print PIN' ?></span>
            </button>
            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="attExportCsvBtn" type="button">
                <i class="fas fa-file-csv"></i>
                <span>Export CSV</span>
            </button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="attPrintBtn" type="button">
                <i class="fas fa-print"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 att-no-print">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-5 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3"
                         style="width:46px;height:46px;font-size:1.35rem;background:rgba(13,110,253,0.12);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Records</h6>
                        <h4 class="mb-0 fw-bold" style="color:#0d6efd;" id="attKpiTotal">0</h4>
                        <small class="fw-semibold d-block mt-1" style="color:#0d6efd;font-size:0.72rem;">All-time entries</small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded-2 d-flex align-items-center justify-content-center border-secondary-subtle"
                   style="width:28px;height:28px;font-size:0.75rem;" title="View all records">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#10b981;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-5 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3"
                         style="width:46px;height:46px;font-size:1.35rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Present</h6>
                        <h4 class="mb-0 fw-bold" style="color:#10b981;" id="attKpiPresent">0</h4>
                        <small class="fw-semibold d-block mt-1" style="color:#10b981;font-size:0.72rem;">
                            <span id="attKpiPresentPct">0%</span> of total
                        </small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded-2 d-flex align-items-center justify-content-center border-secondary-subtle"
                   style="width:28px;height:28px;font-size:0.75rem;" title="View present">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-5 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3"
                         style="width:46px;height:46px;font-size:1.35rem;background:rgba(245,158,11,0.14);color:#f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Late</h6>
                        <h4 class="mb-0 fw-bold" style="color:#f59e0b;" id="attKpiLate">0</h4>
                        <small class="fw-semibold d-block mt-1" style="color:#f59e0b;font-size:0.72rem;">
                            <span id="attKpiLatePct">0%</span> of total
                        </small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded-2 d-flex align-items-center justify-content-center border-secondary-subtle"
                   style="width:28px;height:28px;font-size:0.75rem;" title="View late">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#ff4d4d;z-index:1;"></div>
                <div class="card-body d-flex align-items-center ps-4 pe-5 py-3">
                    <div class="me-3 d-inline-flex align-items-center justify-content-center rounded-3"
                         style="width:46px;height:46px;font-size:1.35rem;background:rgba(255,77,77,0.14);color:#ff4d4d;">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="text-muted mb-1 small text-uppercase fw-bold">Absent / Leave</h6>
                        <h4 class="mb-0 fw-bold" style="color:#ff4d4d;" id="attKpiAbsentLeave">0</h4>
                        <small class="fw-semibold d-block mt-1" style="color:#ff4d4d;font-size:0.72rem;">
                            <span id="attKpiAbsentLeavePct">0%</span> of total
                        </small>
                    </div>
                </div>
                <a href="#" class="position-absolute top-0 end-0 m-3 text-muted border rounded-2 d-flex align-items-center justify-content-center border-secondary-subtle"
                   style="width:28px;height:28px;font-size:0.75rem;" title="View absent / leave">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 att-no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="attFilterYear">School Year</label>
                    <select class="form-select" id="attFilterYear"><option value="all">All years</option></select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="attFilterMonth">Month</label>
                    <select class="form-select" id="attFilterMonth">
                        <option value="all">All months</option>
                        <option value="1">January</option><option value="2">February</option>
                        <option value="3">March</option><option value="4">April</option>
                        <option value="5">May</option><option value="6">June</option>
                        <option value="7">July</option><option value="8">August</option>
                        <option value="9">September</option><option value="10">October</option>
                        <option value="11">November</option><option value="12">December</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="attFilterWeek">Week</label>
                    <select class="form-select" id="attFilterWeek">
                        <option value="all">All weeks</option>
                        <option value="1">Week 1 · 1–7</option><option value="2">Week 2 · 8–14</option>
                        <option value="3">Week 3 · 15–21</option><option value="4">Week 4 · 22–28</option>
                        <option value="5">Week 5 · 29–31</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2" id="attResetFilters" type="button">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 att-no-print">
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Attendance Breakdown</h5>
                        <span class="badge text-bg-light border" id="attStatusChip">—</span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="attStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold">Monthly Volume</h5>
                        <span class="badge text-bg-light border" id="attMonthChip">—</span>
                    </div>
                    <div class="position-relative w-100" style="height:260px;">
                        <canvas id="attMonthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Data Table ================= -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="d-none d-print-block text-center pt-3 px-3">
            <h4 class="mb-1 fw-bold">Faculty Attendance History Report</h4>
            <p class="small mb-0" id="attPrintFilterInfo"></p>
        </div>
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 att-no-print">
            <div>
                <h5 class="card-title mb-1 fw-bold">Attendance Records</h5>
                <p class="text-body-secondary small mb-0" id="attRecordCount">Loading…</p>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#16a34a;"></span>Present
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#d97706;"></span>Late
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#dc2626;"></span>Absent
                </span>
                <span class="d-inline-flex align-items-center gap-2 small fw-semibold text-body-secondary">
                    <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#0284c7;"></span>On Leave
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="attTable">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-body-secondary">Date</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Faculty</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Status</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Time In</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Time Out</th>
                        <th class="text-uppercase small fw-bold text-body-secondary text-center">Hours</th>
                        <th class="text-uppercase small fw-bold text-body-secondary">Notes</th>
                    </tr>
                </thead>
                <tbody id="attTableBody"></tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 att-no-print">
            <div class="small text-body-secondary">
                Showing <span class="fw-bold text-body-emphasis" id="attPageStart">0</span>–<span class="fw-bold text-body-emphasis" id="attPageEnd">0</span>
                of <span class="fw-bold text-body-emphasis" id="attPageTotal">0</span> records
            </div>
            <nav aria-label="Attendance pagination">
                <ul class="pagination mb-0 gap-1" id="attPagination"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- ================= Print PIN Modal ================= -->
<div class="modal fade" id="attPrintPinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3"
                          style="width:36px;height:36px;font-size:1rem;background:rgba(37,99,235,0.12);color:#2563eb;">
                        <i class="fas fa-lock"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Print Authorization</h5>
                        <small class="text-body-secondary">Enter your PIN to continue</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="attPrintPinInput" class="form-label small text-uppercase fw-bold text-body-secondary">Security PIN</label>
                <input type="password" class="form-control form-control-lg text-center fw-bold"
                       id="attPrintPinInput" inputmode="numeric" autocomplete="off"
                       maxlength="6" placeholder="••••" style="letter-spacing:0.5em;">
                <div class="form-text small mt-2">
                    <i class="fas fa-circle-info me-1"></i>
                    Printed copies blur faculty names and notes.
                </div>
                <div class="alert alert-danger mt-3 mb-0 py-2 small d-none" id="attPrintPinError"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="attPrintPinSubmit">
                    <i class="fas fa-print"></i><span>Authorize &amp; Print</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= Manage PIN Modal ================= -->
<div class="modal fade" id="attManagePinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3"
                          style="width:36px;height:36px;font-size:1rem;background:rgba(16,185,129,0.14);color:#10b981;">
                        <i class="fas fa-key"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="attManagePinTitle">
                            <?= $attHasPinSet ? 'Change Print PIN' : 'Set Print PIN' ?>
                        </h5>
                        <small class="text-body-secondary">4–6 digits. Used to authorize printing.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 <?= $attHasPinSet ? '' : 'd-none' ?>" id="attCurrentPinWrap">
                    <label for="attCurrentPinInput" class="form-label small text-uppercase fw-bold text-body-secondary">Current PIN</label>
                    <input type="password" class="form-control text-center fw-bold"
                           id="attCurrentPinInput" inputmode="numeric" autocomplete="off"
                           maxlength="6" placeholder="••••" style="letter-spacing:0.4em;">
                </div>
                <div class="mb-3">
                    <label for="attNewPinInput" class="form-label small text-uppercase fw-bold text-body-secondary">New PIN</label>
                    <input type="password" class="form-control text-center fw-bold"
                           id="attNewPinInput" inputmode="numeric" autocomplete="off"
                           maxlength="6" placeholder="••••" style="letter-spacing:0.4em;">
                </div>
                <div class="mb-0">
                    <label for="attConfirmPinInput" class="form-label small text-uppercase fw-bold text-body-secondary">Confirm New PIN</label>
                    <input type="password" class="form-control text-center fw-bold"
                           id="attConfirmPinInput" inputmode="numeric" autocomplete="off"
                           maxlength="6" placeholder="••••" style="letter-spacing:0.4em;">
                </div>
                <div class="alert alert-danger mt-3 mb-0 py-2 small d-none" id="attManagePinError"></div>
                <div class="alert alert-success mt-3 mb-0 py-2 small d-none" id="attManagePinSuccess"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="attManagePinSubmit">
                    <i class="fas fa-floppy-disk"></i><span>Save PIN</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     Minimal scoped styles
     ========================================================= -->
<style>
    .att-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.32rem 0.7rem; font-size: 0.78rem; font-weight: 650;
        line-height: 1; border-radius: 999px; white-space: nowrap;
        border: 1px solid transparent;
    }
    .att-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.85; }
    .att-badge-present { background: rgba(22,163,74,0.12);  color: #16a34a; border-color: rgba(22,163,74,0.22); }
    .att-badge-late    { background: rgba(217,119,6,0.12);  color: #d97706; border-color: rgba(217,119,6,0.22); }
    .att-badge-absent  { background: rgba(220,38,38,0.12);  color: #dc2626; border-color: rgba(220,38,38,0.22); }
    .att-badge-leave   { background: rgba(2,132,199,0.12);  color: #0284c7; border-color: rgba(2,132,199,0.22); }
    [data-theme="dark"] .att-badge-present { background: rgba(52,211,153,0.16); color: #6ee7b7; border-color: rgba(52,211,153,0.24); }
    [data-theme="dark"] .att-badge-late    { background: rgba(251,191,36,0.16); color: #fde68a; border-color: rgba(251,191,36,0.24); }
    [data-theme="dark"] .att-badge-absent  { background: rgba(248,113,113,0.16); color: #fca5a5; border-color: rgba(248,113,113,0.24); }
    [data-theme="dark"] .att-badge-leave   { background: rgba(56,189,248,0.16);  color: #7dd3fc; border-color: rgba(56,189,248,0.24); }

    #attPagination .page-link {
        min-width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0 0.55rem; font-size: 0.85rem; font-weight: 600;
        border-radius: 9px !important;
    }
    #attPagination .page-item.active .page-link { box-shadow: 0 4px 12px rgba(37,99,235,0.28); }

    @media (max-width: 640px) {
        #attDashboard .position-relative[style*="height:260px"] { height: 220px !important; }
    }
    @media (max-width: 380px) {
        #attDashboard .position-relative[style*="height:260px"] { height: 190px !important; }
        #attDashboard .card-title { font-size: 0.95rem; }
    }

    @media print {
        body { background: #fff !important; color: #000 !important; }
        .att-no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar, .btn, button,
        .card-footer, .card-header, .modal { display: none !important; }
        #attDashboard { padding: 0 !important; }
        .container-fluid { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important;
                background: #fff !important; backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important; border-radius: 8px !important; }
        .table { font-size: 10pt; color: #000 !important; }
        .table thead th { background: #f1f5f9 !important; color: #000 !important;
                          border-color: #ccc !important; padding: 0.5rem 0.6rem !important; }
        .table tbody td { color: #000 !important; border-color: #ddd !important;
                          background: transparent !important; padding: 0.5rem 0.6rem !important; }
        .table tbody tr:hover td { background: transparent !important; }
        .att-badge { border: 1px solid #999 !important; background: #f3f4f6 !important;
                     color: #000 !important; padding: 0.2rem 0.5rem !important; }
        .att-badge::before { background: #555 !important; }
        #attTable tbody td:nth-child(2),
        #attTable tbody td:nth-child(7) {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    /* ============================================================
       Kill browser autofill on navbar search (page-scoped patch)
       Runs immediately, then again at 300ms and 1000ms to catch
       Chrome's delayed autofill behaviour.
       ============================================================ */
    (function fixNavbarAutofill() {
        var selectors = [
            'input[type="search"]',
            'input[placeholder*="Search"]',
            'input[placeholder*="search"]',
            'nav input[type="text"]',
            '.navbar input[type="text"]',
            '.sms-navbar input[type="text"]',
            '.sms-navbar input[type="search"]'
        ];

        var inputs = [];
        selectors.forEach(function (sel) {
            try {
                document.querySelectorAll(sel).forEach(function (el) {
                    if (inputs.indexOf(el) === -1) inputs.push(el);
                });
            } catch (e) { /* ignore invalid selectors */ }
        });

        function wipe() {
            inputs.forEach(function (input) {
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('autocorrect', 'off');
                input.setAttribute('autocapitalize', 'off');
                input.setAttribute('spellcheck', 'false');

                // Give it a non-login name so Chrome stops treating it as a username field
                if (!input.name || input.name === 'q' || input.name === 'search' || input.name === 's') {
                    input.name = 'sms2_global_search_' + Date.now();
                }

                // Strongest signal to the browser
                if (input.type === 'text') {
                    input.type = 'search';
                }

                // Wipe any autofilled value that looks like an email
                if (input.value && input.value.indexOf('@') !== -1) {
                    input.value = '';
                }
            });
        }

        wipe();

        // Belt & braces — Chrome autofills after load sometimes
        setTimeout(wipe, 300);
        setTimeout(wipe, 1000);

        // Also wipe on focus just in case
        inputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                if (this.value && this.value.indexOf('@') !== -1) this.value = '';
            });
        });
    })();

    /* ---------- Library guards ---------- */
    var hasChart     = (typeof Chart !== 'undefined');
    var hasBootstrap = (typeof bootstrap !== 'undefined');

    if (!hasChart)     console.warn('[attendance] Chart.js not loaded — charts disabled.');
    if (!hasBootstrap) console.warn('[attendance] Bootstrap JS not loaded — modals disabled.');

    /* ---------- Endpoints ---------- */
    const ATT_SET_PIN_URL    = '<?= BASE_URL ?>/modules/faculty/controllers/SetPrintPin.php';
    const ATT_VERIFY_PIN_URL = '<?= BASE_URL ?>/modules/faculty/controllers/VerifyPrintPin.php';

    /* ---------- Data ---------- */
    const attRawAttendance = [
        { attendance_id: 12, faculty_id: 101, attendance_date: '2026-09-22', time_in: null,       time_out: null,       status: 'Present',  hours_rendered: null, notes: null },
        { attendance_id: 13, faculty_id: 51,  attendance_date: '2026-09-23', time_in: '08:00:00', time_out: '17:00:00', status: 'Present',  hours_rendered: 8.0,  notes: 'Regular day' },
        { attendance_id: 14, faculty_id: 53,  attendance_date: '2026-09-23', time_in: '09:15:00', time_out: '17:00:00', status: 'Late',     hours_rendered: 7.5,  notes: 'Traffic' },
        { attendance_id: 15, faculty_id: 101, attendance_date: '2026-09-24', time_in: null,       time_out: null,       status: 'Absent',   hours_rendered: null, notes: 'Sick leave' },
        { attendance_id: 16, faculty_id: 105, attendance_date: '2026-09-24', time_in: '08:30:00', time_out: '17:15:00', status: 'Present',  hours_rendered: 8.5,  notes: null },
        { attendance_id: 17, faculty_id: 109, attendance_date: '2026-09-25', time_in: null,       time_out: null,       status: 'On Leave', hours_rendered: null, notes: 'Vacation leave' },
        { attendance_id: 18, faculty_id: 110, attendance_date: '2026-09-25', time_in: '08:00:00', time_out: '17:00:00', status: 'Present',  hours_rendered: 8.0,  notes: null },
        { attendance_id: 19, faculty_id: 51,  attendance_date: '2026-08-15', time_in: '07:45:00', time_out: '16:30:00', status: 'Present',  hours_rendered: 7.8,  notes: null },
        { attendance_id: 20, faculty_id: 53,  attendance_date: '2026-08-20', time_in: '09:00:00', time_out: '18:00:00', status: 'Late',     hours_rendered: 8.0,  notes: 'Meeting' }
    ];

    const attFacultyMap = {
        51:  'Jean Claude',
        53:  'Jean Claude Espejo',
        101: 'Light Yagami',
        105: 'Alfred Joseph Alcantara',
        109: 'Sofia Reyes',
        110: 'Jorge Lucero'
    };

    const PAGE_SIZE = 10;

    let attFilteredData = [];
    let attCurrentPage  = 1;
    let attStatusChartInstance = null;
    let attMonthChartInstance  = null;

    /* ---------- Helpers ---------- */
    function attGetFacultyName(id) { return attFacultyMap[id] || ('Faculty #' + id); }

    function attFormatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d)) return dateStr;
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function attFormatTime(timeStr) {
        if (!timeStr) return '—';
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let h = parseInt(parts[0], 10);
        const m = parts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    function attStatusBadge(status) {
        const map = {
            'Present':  'att-badge-present',
            'Late':     'att-badge-late',
            'Absent':   'att-badge-absent',
            'On Leave': 'att-badge-leave'
        };
        return '<span class="att-badge ' + (map[status] || 'att-badge-present') + '">' + status + '</span>';
    }

    function attThemeColors() {
        const css = getComputedStyle(document.documentElement);
        return {
            grid: css.getPropertyValue('--sms-chart-grid').trim() || 'rgba(15,33,88,0.06)',
            text: css.getPropertyValue('--sms-chart-text').trim() || '#64748b',
            doughnutBorder: css.getPropertyValue('--sms-chart-doughnut-border').trim() || '#ffffff'
        };
    }

    /* ---------- Filters ---------- */
    function attPopulateYearFilter() {
        const years = new Set();
        attRawAttendance.forEach(function (r) {
            if (r.attendance_date) years.add(r.attendance_date.substring(0, 4));
        });
        const select = document.getElementById('attFilterYear');
        if (!select) return;
        select.innerHTML = '<option value="all">All years</option>';
        Array.from(years).sort().reverse().forEach(function (y) {
            const opt = document.createElement('option');
            opt.value = y; opt.textContent = y;
            select.appendChild(opt);
        });
    }

    function attApplyFilters() {
        const year  = document.getElementById('attFilterYear').value;
        const month = document.getElementById('attFilterMonth').value;
        const week  = document.getElementById('attFilterWeek').value;

        attFilteredData = attRawAttendance.filter(function (r) {
            if (!r.attendance_date) return false;
            const d = new Date(r.attendance_date + 'T00:00:00');
            if (isNaN(d)) return false;
            const y = String(d.getFullYear());
            const m = String(d.getMonth() + 1);
            const day = d.getDate();

            if (year !== 'all' && y !== year) return false;
            if (month !== 'all' && m !== month) return false;

            if (week !== 'all') {
                const w = parseInt(week, 10);
                let match = false;
                if (w === 1 && day >= 1  && day <= 7)  match = true;
                if (w === 2 && day >= 8  && day <= 14) match = true;
                if (w === 3 && day >= 15 && day <= 21) match = true;
                if (w === 4 && day >= 22 && day <= 28) match = true;
                if (w === 5 && day >= 29)              match = true;
                if (!match) return false;
            }
            return true;
        });

        attFilteredData.sort(function (a, b) {
            return (b.attendance_date || '').localeCompare(a.attendance_date || '');
        });

        attCurrentPage = 1;
        attUpdateDashboard();
    }

    /* ---------- Table ---------- */
    function attRenderTable() {
        const tbody = document.getElementById('attTableBody');
        if (!tbody) return;

        const total      = attFilteredData.length;
        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (attCurrentPage > totalPages) attCurrentPage = totalPages;

        const startIdx = (attCurrentPage - 1) * PAGE_SIZE;
        const endIdx   = Math.min(startIdx + PAGE_SIZE, total);
        const pageData = attFilteredData.slice(startIdx, endIdx);

        if (!total) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-body-secondary">No attendance records match your filters.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function (r) {
                return '<tr>' +
                    '<td class="text-nowrap">' + attFormatDate(r.attendance_date) + '</td>' +
                    '<td class="fw-semibold">' + attGetFacultyName(r.faculty_id) + '</td>' +
                    '<td>' + attStatusBadge(r.status) + '</td>' +
                    '<td class="text-nowrap">' + attFormatTime(r.time_in) + '</td>' +
                    '<td class="text-nowrap">' + attFormatTime(r.time_out) + '</td>' +
                    '<td class="text-center">' + (r.hours_rendered != null ? r.hours_rendered : '—') + '</td>' +
                    '<td>' + (r.notes || '—') + '</td>' +
                    '</tr>';
            }).join('');
        }

        document.getElementById('attPageStart').textContent = total ? (startIdx + 1) : 0;
        document.getElementById('attPageEnd').textContent   = endIdx;
        document.getElementById('attPageTotal').textContent = total;
        document.getElementById('attRecordCount').textContent =
            total + ' record' + (total !== 1 ? 's' : '') +
            (total ? ' · page ' + attCurrentPage + ' of ' + totalPages : '');

        attRenderPagination(totalPages);
        attUpdatePrintSubtitle();
    }

    function attRenderPagination(totalPages) {
        const ul = document.getElementById('attPagination');
        if (!ul) return;
        if (totalPages <= 1) { ul.innerHTML = ''; return; }

        let html = '';
        html += '<li class="page-item ' + (attCurrentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (attCurrentPage - 1) + '" aria-label="Previous">' +
                '<i class="fas fa-chevron-left"></i></a></li>';

        attBuildPageList(attCurrentPage, totalPages).forEach(function (p) {
            if (p === '…') {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            } else {
                html += '<li class="page-item ' + (p === attCurrentPage ? 'active' : '') + '">' +
                        '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
            }
        });

        html += '<li class="page-item ' + (attCurrentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (attCurrentPage + 1) + '" aria-label="Next">' +
                '<i class="fas fa-chevron-right"></i></a></li>';

        ul.innerHTML = html;

        ul.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.getAttribute('data-page'), 10);
                if (!p || p < 1 || p > totalPages || p === attCurrentPage) return;
                attCurrentPage = p;
                attRenderTable();
                const card = document.getElementById('attTable');
                if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function attBuildPageList(current, total) {
        const delta = 1;
        const range = [];
        const out = [];
        let l;
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) range.push(i);
        }
        range.forEach(function (i) {
            if (l) {
                if (i - l === 2) out.push(l + 1);
                else if (i - l !== 1) out.push('…');
            }
            out.push(i);
            l = i;
        });
        return out;
    }

    function attUpdatePrintSubtitle() {
        const year  = document.getElementById('attFilterYear').value;
        const month = document.getElementById('attFilterMonth').value;
        const week  = document.getElementById('attFilterWeek').value;
        const parts = [];
        if (year !== 'all')  parts.push('Year: ' + year);
        if (month !== 'all') {
            const mn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            parts.push('Month: ' + mn[parseInt(month, 10) - 1]);
        }
        if (week !== 'all')  parts.push('Week: ' + week);
        const el = document.getElementById('attPrintFilterInfo');
        if (el) el.textContent = parts.length ? ('Filters: ' + parts.join(' · ')) : 'All records';
    }

    /* ---------- KPIs ---------- */
    function attUpdateKPIs(data) {
        const total       = data.length;
        const present     = data.filter(function (r) { return r.status === 'Present'; }).length;
        const late        = data.filter(function (r) { return r.status === 'Late'; }).length;
        const absentLeave = data.filter(function (r) { return r.status === 'Absent' || r.status === 'On Leave'; }).length;

        document.getElementById('attKpiTotal').textContent       = total;
        document.getElementById('attKpiPresent').textContent     = present;
        document.getElementById('attKpiLate').textContent        = late;
        document.getElementById('attKpiAbsentLeave').textContent = absentLeave;

        const pct = function (n) { return total ? Math.round((n / total) * 100) + '%' : '0%'; };
        document.getElementById('attKpiPresentPct').textContent     = pct(present);
        document.getElementById('attKpiLatePct').textContent        = pct(late);
        document.getElementById('attKpiAbsentLeavePct').textContent = pct(absentLeave);
    }

    /* ---------- Charts ---------- */
    function attUpdateCharts(data) {
        if (!hasChart) return;
        const colors = attThemeColors();

        const counts = { Present: 0, Late: 0, Absent: 0, 'On Leave': 0 };
        data.forEach(function (r) { if (counts[r.status] !== undefined) counts[r.status]++; });

        const statusChip = document.getElementById('attStatusChip');
        if (statusChip) statusChip.textContent = data.length + ' total';

        const statusCanvas = document.getElementById('attStatusChart');
        if (statusCanvas) {
            if (attStatusChartInstance) attStatusChartInstance.destroy();
            attStatusChartInstance = new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Late', 'Absent', 'On Leave'],
                    datasets: [{
                        data: [counts.Present, counts.Late, counts.Absent, counts['On Leave']],
                        backgroundColor: ['#16a34a', '#d97706', '#dc2626', '#0284c7'],
                        borderColor: colors.doughnutBorder,
                        borderWidth: 3,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: colors.text, boxWidth: 10, boxHeight: 10,
                                      usePointStyle: true, pointStyle: 'circle',
                                      padding: 14, font: { size: 12, weight: '600' } }
                        }
                    }
                }
            });
        }

        const monthMap = {};
        data.forEach(function (r) {
            if (!r.attendance_date) return;
            const d = new Date(r.attendance_date + 'T00:00:00');
            if (isNaN(d)) return;
            const key = d.toLocaleString('default', { month: 'short', year: 'numeric' });
            monthMap[key] = (monthMap[key] || 0) + 1;
        });
        const sortedMonths = Object.keys(monthMap).sort(function (a, b) {
            return new Date('01 ' + a) - new Date('01 ' + b);
        });

        const monthChip = document.getElementById('attMonthChip');
        if (monthChip) monthChip.textContent = sortedMonths.length + ' month' + (sortedMonths.length !== 1 ? 's' : '');

        const monthCanvas = document.getElementById('attMonthChart');
        if (monthCanvas) {
            if (attMonthChartInstance) attMonthChartInstance.destroy();
            attMonthChartInstance = new Chart(monthCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: sortedMonths,
                    datasets: [{
                        label: 'Records',
                        data: sortedMonths.map(function (m) { return monthMap[m]; }),
                        backgroundColor: 'rgba(37, 99, 235, 0.75)',
                        hoverBackgroundColor: 'rgba(37, 99, 235, 0.95)',
                        borderRadius: 8, borderSkipped: false, maxBarThickness: 56
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: colors.text, font: { size: 11, weight: '600' } } },
                        y: { beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text, stepSize: 1, font: { size: 11 } } }
                    }
                }
            });
        }
    }

    function attUpdateDashboard() {
        attUpdateKPIs(attFilteredData);
        attRenderTable();
        attUpdateCharts(attFilteredData);
    }

    /* ---------- CSV ---------- */
    function attExportCSV() {
        const headers = ['Date', 'Faculty', 'Status', 'Time In', 'Time Out', 'Hours', 'Notes'];
        const rows = attFilteredData.map(function (r) {
            return [
                r.attendance_date,
                attGetFacultyName(r.faculty_id),
                r.status,
                r.time_in || '',
                r.time_out || '',
                r.hours_rendered != null ? r.hours_rendered : '',
                r.notes || ''
            ];
        });

        const csv = 'data:text/csv;charset=utf-8,'
            + headers.join(',') + '\n'
            + rows.map(function (e) {
                return e.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',');
            }).join('\n');

        const link = document.createElement('a');
        link.setAttribute('href', encodeURI(csv));
        link.setAttribute('download', 'attendance_history_' + new Date().toISOString().slice(0, 10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /* ---------- Print PIN ---------- */
    const attPrintPinModalEl = document.getElementById('attPrintPinModal');
    const attPrintPinInput   = document.getElementById('attPrintPinInput');
    const attPrintPinError   = document.getElementById('attPrintPinError');
    const attPrintPinSubmit  = document.getElementById('attPrintPinSubmit');
    const attPrintPinModal   = hasBootstrap ? new bootstrap.Modal(attPrintPinModalEl) : null;

    document.getElementById('attPrintBtn').addEventListener('click', function () {
        if (!attPrintPinModal) { window.print(); return; }
        attPrintPinInput.value = '';
        attPrintPinError.classList.add('d-none');
        attPrintPinModal.show();
        setTimeout(function () { attPrintPinInput.focus(); }, 300);
    });

    function attVerifyPrintPin() {
        const entered = (attPrintPinInput.value || '').trim();
        if (!entered) return;

        attPrintPinSubmit.disabled = true;

        fetch(ATT_VERIFY_PIN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ pin: entered })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            attPrintPinSubmit.disabled = false;
            if (data.ok) {
                if (attPrintPinModal) attPrintPinModal.hide();
                setTimeout(function () { window.print(); }, 350);
            } else {
                attPrintPinError.textContent = data.error || 'Incorrect PIN. Please try again.';
                attPrintPinError.classList.remove('d-none');
                attPrintPinInput.classList.add('is-invalid');
                attPrintPinInput.value = '';
                attPrintPinInput.focus();
            }
        })
        .catch(function () {
            attPrintPinSubmit.disabled = false;
            attPrintPinError.textContent = 'Server error. Please try again.';
            attPrintPinError.classList.remove('d-none');
        });
    }

    attPrintPinSubmit.addEventListener('click', attVerifyPrintPin);
    attPrintPinInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); attVerifyPrintPin(); }
    });
    attPrintPinInput.addEventListener('input', function () {
        attPrintPinError.classList.add('d-none');
        attPrintPinInput.classList.remove('is-invalid');
    });
    if (attPrintPinModalEl) {
        attPrintPinModalEl.addEventListener('hidden.bs.modal', function () {
            attPrintPinInput.value = '';
            attPrintPinError.classList.add('d-none');
            attPrintPinInput.classList.remove('is-invalid');
        });
    }

    /* ---------- Manage PIN ---------- */
    const attManagePinModalEl = document.getElementById('attManagePinModal');
    const attManagePinModal   = hasBootstrap ? new bootstrap.Modal(attManagePinModalEl) : null;
    const attCurrentPinInput  = document.getElementById('attCurrentPinInput');
    const attNewPinInput      = document.getElementById('attNewPinInput');
    const attConfirmPinInput  = document.getElementById('attConfirmPinInput');
    const attManagePinError   = document.getElementById('attManagePinError');
    const attManagePinSuccess = document.getElementById('attManagePinSuccess');
    const attManagePinSubmit  = document.getElementById('attManagePinSubmit');

    document.getElementById('attManagePinBtn').addEventListener('click', function () {
        if (!attManagePinModal) {
            alert('Bootstrap JS is not loaded — PIN modal unavailable.');
            return;
        }
        attCurrentPinInput.value = '';
        attNewPinInput.value = '';
        attConfirmPinInput.value = '';
        attManagePinError.classList.add('d-none');
        attManagePinSuccess.classList.add('d-none');
        attManagePinModal.show();
    });

    attManagePinSubmit.addEventListener('click', function () {
        attManagePinError.classList.add('d-none');
        attManagePinSuccess.classList.add('d-none');

        const newPin     = (attNewPinInput.value || '').trim();
        const confirmPin = (attConfirmPinInput.value || '').trim();
        const currentPin = (attCurrentPinInput.value || '').trim();

        if (!/^\d{4,6}$/.test(newPin)) {
            attManagePinError.textContent = 'PIN must be 4–6 digits.';
            attManagePinError.classList.remove('d-none');
            return;
        }
        if (newPin !== confirmPin) {
            attManagePinError.textContent = 'PINs do not match.';
            attManagePinError.classList.remove('d-none');
            return;
        }

        attManagePinSubmit.disabled = true;

        fetch(ATT_SET_PIN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ pin: newPin, current_pin: currentPin })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            attManagePinSubmit.disabled = false;
            if (data.ok) {
                attManagePinSuccess.textContent = data.message || 'PIN saved.';
                attManagePinSuccess.classList.remove('d-none');
                const btn = document.getElementById('attManagePinBtn');
                if (btn) btn.querySelector('span').textContent = 'Change PIN';
                const title = document.getElementById('attManagePinTitle');
                if (title) title.textContent = 'Change Print PIN';
                const wrap = document.getElementById('attCurrentPinWrap');
                if (wrap) wrap.classList.remove('d-none');
                setTimeout(function () { if (attManagePinModal) attManagePinModal.hide(); }, 900);
            } else {
                attManagePinError.textContent = data.error || 'Could not save PIN.';
                attManagePinError.classList.remove('d-none');
            }
        })
        .catch(function () {
            attManagePinSubmit.disabled = false;
            attManagePinError.textContent = 'Server error. Please try again.';
            attManagePinError.classList.remove('d-none');
        });
    });

    /* ---------- Init ---------- */
    function attInit() {
        attPopulateYearFilter();

        var yEl = document.getElementById('attFilterYear');
        var mEl = document.getElementById('attFilterMonth');
        var wEl = document.getElementById('attFilterWeek');
        if (yEl) yEl.value = 'all';
        if (mEl) mEl.value = 'all';
        if (wEl) wEl.value = 'all';

        attApplyFilters();

        if (yEl) yEl.addEventListener('change', attApplyFilters);
        if (mEl) mEl.addEventListener('change', attApplyFilters);
        if (wEl) wEl.addEventListener('change', attApplyFilters);

        var resetBtn = document.getElementById('attResetFilters');
        if (resetBtn) resetBtn.addEventListener('click', function () {
            if (yEl) yEl.value = 'all';
            if (mEl) mEl.value = 'all';
            if (wEl) wEl.value = 'all';
            attApplyFilters();
        });

        var csvBtn = document.getElementById('attExportCsvBtn');
        if (csvBtn) csvBtn.addEventListener('click', attExportCSV);

        var observer = new MutationObserver(function () { attUpdateCharts(attFilteredData); });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attInit);
    } else {
        attInit();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>