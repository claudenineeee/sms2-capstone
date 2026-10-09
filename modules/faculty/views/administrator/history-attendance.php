<?php
/**
 * SMS 2 - Faculty Admin Dashboard
 * Attendance History — with Privacy Mode (no PIN)
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

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4" id="attDashboard">

    <!-- ================= Page Header ================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div class="min-w-0">
            <h1 class="h3 fw-bold mb-1 text-body-emphasis">Attendance History</h1>
            <p class="text-body-secondary mb-0">Track faculty presence, punctuality, and leave across the current school year.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-body-tertiary mb-0"
                   for="privacyModeToggle" style="cursor:pointer;font-size:0.82rem;font-weight:600;">
                <input type="checkbox" class="form-check-input mt-0" id="privacyModeToggle" role="switch">
                <i class="fas fa-eye-slash"></i>
                <span class="d-none d-sm-inline">Privacy</span>
            </label>
            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="attExportCsvBtn" type="button">
                <i class="fas fa-file-csv"></i>
                <span class="d-none d-sm-inline">Export CSV</span>
            </button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="attPrintBtn" type="button">
                <i class="fas fa-print"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- ================= KPI Cards ================= -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-6 col-lg-3">
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
            </section>
        </div>
        <div class="col-6 col-lg-3">
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
            </section>
        </div>
        <div class="col-6 col-lg-3">
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
            </section>
        </div>
        <div class="col-6 col-lg-3">
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
            </section>
        </div>
    </div>

    <!-- ================= Filters ================= -->
    <div class="card border shadow-sm mb-4 no-print">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="attFilterYear">School Year</label>
                    <select class="form-select form-select-sm" id="attFilterYear"><option value="all">All years</option></select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-uppercase fw-bold text-body-secondary mb-1" for="attFilterMonth">Month</label>
                    <select class="form-select form-select-sm" id="attFilterMonth">
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
                    <select class="form-select form-select-sm" id="attFilterWeek">
                        <option value="all">All weeks</option>
                        <option value="1">Week 1 · 1–7</option><option value="2">Week 2 · 8–14</option>
                        <option value="3">Week 3 · 15–21</option><option value="4">Week 4 · 22–28</option>
                        <option value="5">Week 5 · 29–31</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-lg-auto">
                    <button class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2" id="attResetFilters" type="button">
                        <i class="fas fa-rotate-left"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Charts ================= -->
    <div class="row g-3 mb-4 no-print">
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
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3 py-3 no-print">
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
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 no-print">
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

<!-- Print-only report -->
<div class="att-print-only" id="attPrintTable" aria-hidden="true"></div>

<style>
    /* ============================================================
       Status badges
       ============================================================ */
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

    /* ============================================================
       Pagination
       ============================================================ */
    #attPagination .page-link {
        min-width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0 0.55rem; font-size: 0.85rem; font-weight: 600;
        border-radius: 9px !important;
    }
    #attPagination .page-item.active .page-link { box-shadow: 0 4px 12px rgba(37,99,235,0.28); }

    /* ============================================================
       Privacy blur (screen toggle)
       ============================================================ */
    .privacy-mode .att-privacy-target {
        filter: blur(5px); -webkit-filter: blur(5px);
        user-select: none; transition: filter 0.15s ease;
    }
    .privacy-mode .att-privacy-target:hover { filter: blur(0); -webkit-filter: blur(0); }

    /* ============================================================
       Responsive
       ============================================================ */
    @media (max-width: 640px) {
        #attDashboard .position-relative[style*="height:260px"] { height: 220px !important; }
    }
    @media (max-width: 400px) {
        #attDashboard { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        #attDashboard h1.h3 { font-size: 1.15rem; }
        .stat-card .card-body { padding: 0.7rem 0.6rem 0.7rem 0.9rem !important; }
        .stat-card h4 { font-size: 1.2rem !important; }
        .stat-card h6 { font-size: 0.62rem !important; letter-spacing: 0.03em !important; }
        .stat-card small { font-size: 0.65rem !important; }
        .stat-card .card-body > div[style*="width:46px"] { width: 36px !important; height: 36px !important; font-size: 1rem !important; }
        #attDashboard .position-relative[style*="height:260px"] { height: 190px !important; }
        #attDashboard .card-title { font-size: 0.95rem; }
        #attTable tbody td,
        #attTable thead th { font-size: 0.72rem; padding: 0.5rem 0.4rem; }
    }
    @media (max-width: 360px) {
        #attTable tbody td,
        #attTable thead th { font-size: 0.68rem; padding: 0.45rem 0.3rem; }
    }

    /* ============================================================
       Print-only report
       ============================================================ */
    .att-print-only { display: none; }

    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print, .breadcrumb, nav, header, footer,
        .sidebar, .sms-sidebar, .sms-navbar,
        .card-footer, .card-header,
        .btn, button, .modal,
        .pagination,
        #privacyModeToggle { display: none !important; }

        #attDashboard { display: none !important; }

        .att-print-only {
            display: block !important;
            padding: 0.4in 0.35in;
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
        }
        .att-print-only .print-header {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .att-print-only .print-header h3 {
            font-size: 16pt; font-weight: 800; margin: 0 0 4px;
        }
        .att-print-only .print-header .print-meta {
            font-size: 9.5pt; color: #333; margin: 0;
        }
        .att-print-only .print-filters {
            font-size: 9pt; color: #333; text-align: center;
            margin-bottom: 12px; font-style: italic;
        }
        .att-print-only .print-kpis {
            display: flex; justify-content: space-between; gap: 8px;
            margin-bottom: 14px; padding: 10px 12px;
            border: 1px solid #999; border-radius: 6px; background: #f8f9fa;
        }
        .att-print-only .print-kpi { flex: 1; text-align: center; }
        .att-print-only .print-kpi-label {
            font-size: 8pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555;
        }
        .att-print-only .print-kpi-value {
            font-size: 14pt; font-weight: 800; color: #000; line-height: 1.2;
        }
        .att-print-only table {
            width: 100%; border-collapse: collapse; font-size: 9.5pt;
        }
        .att-print-only thead th {
            background: #e5e7eb !important; color: #000 !important;
            border: 1px solid #999; padding: 6px 8px;
            text-align: left; font-size: 8.5pt;
            text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700;
        }
        .att-print-only tbody td {
            border: 1px solid #ccc; padding: 5px 8px;
            color: #000 !important; background: #fff !important;
        }
        .att-print-only tbody tr:nth-child(even) td { background: #f9fafb !important; }

        /* Blur on print — always, regardless of the Privacy toggle */
        .att-print-only td.att-print-blur {
            filter: blur(6px) !important;
            -webkit-filter: blur(6px) !important;
            user-select: none !important;
            -webkit-user-select: none !important;
        }

        .att-print-only .print-footer {
            margin-top: 14px; font-size: 8pt; color: #666;
            text-align: center; border-top: 1px solid #ccc; padding-top: 8px;
        }
        .att-print-only thead { display: table-header-group; }
        .att-print-only tbody tr { page-break-inside: avoid; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    /* ============================================================
       Kill browser autofill on navbar search
       — Also removes the native "clear" button so only the
         theme's own × remains visible.
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
            } catch (e) { /* ignore */ }
        });

        function wipe() {
            inputs.forEach(function (input) {
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('autocorrect', 'off');
                input.setAttribute('autocapitalize', 'off');
                input.setAttribute('spellcheck', 'false');

                // Unique non-login name so Chrome stops treating it as a username field
                if (!input.name || input.name === 'q' || input.name === 'search' || input.name === 's') {
                    input.name = 'sms2_global_search_' + Date.now();
                }

                // Change to type="text" to remove the native × clear button.
                // The theme's own × is a separate element outside the input.
                if (input.type === 'search') {
                    input.type = 'text';
                }

                // Wipe any email-looking autofilled value
                if (input.value && input.value.indexOf('@') !== -1) {
                    input.value = '';
                }
            });
        }

        wipe();
        setTimeout(wipe, 300);
        setTimeout(wipe, 1000);

        inputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                if (this.value && this.value.indexOf('@') !== -1) this.value = '';
            });
        });
    })();

    /* ============================================================
       DATA
       ============================================================ */
    var hasChart = (typeof Chart !== 'undefined');
    if (!hasChart) console.warn('[attendance] Chart.js not loaded — charts disabled.');

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

    /* ============================================================
       HELPERS
       ============================================================ */
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

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function attThemeColors() {
        const css = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.getAttribute('data-theme') === 'dark';
        return {
            grid: css.getPropertyValue('--sms-chart-grid').trim() || (isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,33,88,0.06)'),
            text: css.getPropertyValue('--sms-chart-text').trim() || (isDark ? '#94a3b8' : '#64748b'),
            textStrong: isDark ? '#e2e8f0' : '#0f172a',
            doughnutBorder: css.getPropertyValue('--sms-chart-doughnut-border').trim() || (isDark ? '#121c34' : '#ffffff')
        };
    }

    /* ============================================================
       FILTERS
       ============================================================ */
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

    /* ============================================================
       TABLE
       ============================================================ */
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
                    '<td class="fw-semibold att-privacy-target">' + attGetFacultyName(r.faculty_id) + '</td>' +
                    '<td>' + attStatusBadge(r.status) + '</td>' +
                    '<td class="text-nowrap">' + attFormatTime(r.time_in) + '</td>' +
                    '<td class="text-nowrap">' + attFormatTime(r.time_out) + '</td>' +
                    '<td class="text-center">' + (r.hours_rendered != null ? r.hours_rendered : '—') + '</td>' +
                    '<td class="att-privacy-target">' + (r.notes || '—') + '</td>' +
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

    /* ============================================================
       KPIs
       ============================================================ */
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

    /* ============================================================
       CHARTS
       ============================================================ */
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
                            labels: { color: colors.textStrong, boxWidth: 10, boxHeight: 10,
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
        attRenderPrintTable();
    }

    /* ============================================================
       PRINT-ONLY REPORT
       Builds a complete printable report with header, KPI strip,
       all filtered rows, and footer. Faculty + Notes are blurred.
       ============================================================ */
    function attRenderPrintTable() {
        const host = document.getElementById('attPrintTable');
        if (!host) return;

        const total       = attFilteredData.length;
        const present     = attFilteredData.filter(function (r) { return r.status === 'Present'; }).length;
        const late        = attFilteredData.filter(function (r) { return r.status === 'Late'; }).length;
        const absentLeave = attFilteredData.filter(function (r) { return r.status === 'Absent' || r.status === 'On Leave'; }).length;

        const yEl = document.getElementById('attFilterYear');
        const mEl = document.getElementById('attFilterMonth');
        const wEl = document.getElementById('attFilterWeek');
        const filterParts = [];
        if (yEl && yEl.value !== 'all') filterParts.push('Year: ' + yEl.value);
        if (mEl && mEl.value !== 'all') {
            const mn = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            filterParts.push('Month: ' + mn[parseInt(mEl.value, 10) - 1]);
        }
        if (wEl && wEl.value !== 'all') filterParts.push('Week: ' + wEl.value);
        const filterLine = filterParts.length
            ? 'Filters — ' + filterParts.join(' · ')
            : 'No filters applied — showing all records';

        const now = new Date();
        const generatedAt = now.toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        }) + ' · ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        if (total === 0) {
            host.innerHTML = `
                <div class="print-header">
                    <h3>Faculty Attendance History Report</h3>
                    <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
                </div>
                <p class="print-filters">${escapeHtml(filterLine)}</p>
                <p style="text-align:center; font-size:10pt; color:#555; margin-top:40px;">
                    No attendance records match the current filters.
                </p>
                <div class="print-footer">
                    SMS 2 · Faculty Module · Confidential
                </div>`;
            return;
        }

        const rows = attFilteredData.map(function (r) {
            return '<tr>' +
                '<td>' + escapeHtml(attFormatDate(r.attendance_date)) + '</td>' +
                '<td class="att-print-blur">' + escapeHtml(attGetFacultyName(r.faculty_id)) + '</td>' +
                '<td>' + escapeHtml(r.status) + '</td>' +
                '<td>' + escapeHtml(attFormatTime(r.time_in)) + '</td>' +
                '<td>' + escapeHtml(attFormatTime(r.time_out)) + '</td>' +
                '<td style="text-align:center;">' + (r.hours_rendered != null ? r.hours_rendered : '—') + '</td>' +
                '<td class="att-print-blur">' + escapeHtml(r.notes || '—') + '</td>' +
            '</tr>';
        }).join('');

        host.innerHTML = `
            <div class="print-header">
                <h3>Faculty Attendance History Report</h3>
                <p class="print-meta">Generated ${escapeHtml(generatedAt)}</p>
            </div>

            <p class="print-filters">${escapeHtml(filterLine)}</p>

            <div class="print-kpis">
                <div class="print-kpi">
                    <div class="print-kpi-label">Total Records</div>
                    <div class="print-kpi-value">${total.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Present</div>
                    <div class="print-kpi-value">${present.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Late</div>
                    <div class="print-kpi-value">${late.toLocaleString()}</div>
                </div>
                <div class="print-kpi">
                    <div class="print-kpi-label">Absent / Leave</div>
                    <div class="print-kpi-value">${absentLeave.toLocaleString()}</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Faculty</th>
                        <th>Status</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th style="text-align:center;">Hours</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>

            <div class="print-footer">
                SMS 2 · Faculty Module · ${total.toLocaleString()} record${total !== 1 ? 's' : ''} · Confidential — Faculty names &amp; notes blurred
            </div>`;
    }

    /* ============================================================
       CSV
       ============================================================ */
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

    /* ============================================================
       INIT
       ============================================================ */
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

        var printBtn = document.getElementById('attPrintBtn');
        if (printBtn) printBtn.addEventListener('click', function () { window.print(); });

        /* Privacy toggle (screen blur) */
        var pt = document.getElementById('privacyModeToggle');
        var KEY = 'smsAttendancePrivacyMode';
        if (pt) {
            var apply = function (on) { document.body.classList.toggle('privacy-mode', on); };
            try {
                var saved = localStorage.getItem(KEY) === '1';
                pt.checked = saved;
                apply(saved);
            } catch (e) { /* ignore */ }
            pt.addEventListener('change', function () {
                apply(pt.checked);
                try { localStorage.setItem(KEY, pt.checked ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }

        var observer = new MutationObserver(function () { attUpdateCharts(attFilteredData); });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        observer.observe(document.body,            { attributes: true, attributeFilter: ['data-theme'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attInit);
    } else {
        attInit();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>