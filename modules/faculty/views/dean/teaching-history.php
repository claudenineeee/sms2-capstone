<?php
/**
 * SMS 2 - Teaching History
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Teaching History';
$activeModule = 'faculty';
$activePage   = 'teaching-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Teaching History', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Teaching History</h1>
    </div>
</div>
<div class="container-fluid my-4 bg-light text-dark p-3 rounded-3">
    <div class="row g-4">
        
        <!-- LEFT COLUMN: Faculty Members List -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card shadow-sm border border-secondary border-opacity-25 bg-white h-100">
                <div class="card-header bg-white border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold fs-6 text-dark">Faculty Members</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Teaching Logs</span>
                </div>
                <div class="card-body p-3 overflow-auto" style="max-height: 650px;">
                    
                    <div class="d-flex flex-column gap-2">
                        
                        <!-- Faculty Card Item 1 -->
                        <div class="faculty-item d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light transition-all">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 14px; min-width: 42px;">
                                    JD
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">John Doe</div>
                                    <div class="text-secondary" style="font-size: 11px;">BSIT • Web Dev, Database</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" 
                                onclick="loadTeachingHistory('FAC-2026-001', 'John Doe', 'BSIT', 'JD', 'bg-primary text-white', 'Head Faculty', '6 Semesters', '14 Courses', '4.85 / 5.0')">
                                View
                            </button>
                        </div>

                        <!-- Faculty Card Item 2 -->
                        <div class="faculty-item d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light transition-all">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 14px; min-width: 42px;">
                                    JS
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">Jane Smith</div>
                                    <div class="text-secondary" style="font-size: 11px;">BSTM • Tourism, Hospitality</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" 
                                onclick="loadTeachingHistory('FAC-2026-002', 'Jane Smith', 'BSTM', 'JS', 'bg-info text-dark', 'Instructor II', '4 Semesters', '9 Courses', '4.72 / 5.0')">
                                View
                            </button>
                        </div>

                        <!-- Faculty Card Item 3 -->
                        <div class="faculty-item d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light transition-all">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 14px; min-width: 42px;">
                                    AM
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">Alan Miller</div>
                                    <div class="text-secondary" style="font-size: 11px;">BSIT • Networking, Cybersecurity</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" 
                                onclick="loadTeachingHistory('FAC-2026-045', 'Alan Miller', 'BSIT', 'AM', 'bg-success text-white', 'Assistant Professor', '8 Semesters', '18 Courses', '4.91 / 5.0')">
                                View
                            </button>
                        </div>

                        <!-- Faculty Card Item 4 -->
                        <div class="faculty-item d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light transition-all">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 14px; min-width: 42px;">
                                    CG
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">Clara Garcia</div>
                                    <div class="text-secondary" style="font-size: 11px;">BSTM • Events Management</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" 
                                onclick="loadTeachingHistory('FAC-2026-088', 'Clara Garcia', 'BSTM', 'CG', 'bg-warning text-dark', 'Instructor I', '2 Semesters', '5 Courses', '4.68 / 5.0')">
                                View
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Detailed Teaching History Display -->
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card shadow-sm border border-secondary border-opacity-25 bg-white h-100">
                
                <!-- Header Panel for Selected Faculty -->
                <div class="card-header bg-white border-bottom border-secondary border-opacity-25 p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div id="targetAvatar" class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                                JD
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark" id="targetName">John Doe</h5>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge border border-secondary border-opacity-25 text-dark bg-light" id="targetDept">BSIT</span>
                                    <span class="text-secondary small" id="targetTitle">Head Faculty</span>
                                    <span class="text-secondary small">• ID: <span id="targetId" class="text-dark fw-semibold">FAC-2026-001</span></span>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary border-opacity-50 text-dark d-flex align-items-center gap-2 bg-light" onclick="exportHistory()">
                            <i class="fas fa-download"></i> Export History PDF
                        </button>
                    </div>
                </div>

                <!-- History Body Section -->
                <div class="card-body p-4">
                    
                    <!-- Quick Stats Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="p-3 rounded-3 border border-secondary border-opacity-25 bg-light text-center">
                                <small class="text-secondary d-block mb-1" style="font-size: 11px;">Total Semesters</small>
                                <span class="h5 fw-bold mb-0 text-dark" id="statSemesters">6 Semesters</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 border border-secondary border-opacity-25 bg-light text-center">
                                <small class="text-secondary d-block mb-1" style="font-size: 11px;">Subjects Handled</small>
                                <span class="h5 fw-bold mb-0 text-primary" id="statSubjects">14 Courses</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 border border-secondary border-opacity-25 bg-light text-center">
                                <small class="text-secondary d-block mb-1" style="font-size: 11px;">Avg. Evaluation</small>
                                <span class="h5 fw-bold mb-0 text-success" id="statEval">4.85 / 5.0</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-secondary small text-uppercase fw-bold mb-3" style="letter-spacing: 0.5px;">Academic Assignments Timeline</h6>

                    <!-- Teaching History Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="historyTable">
                            <thead class="table-light border-bottom border-secondary border-opacity-25 small text-uppercase text-secondary">
                                <tr>
                                    <th>Academic Term</th>
                                    <th>Subject Code & Title</th>
                                    <th>Section</th>
                                    <th>Units</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0" id="historyTableBody">
                                <!-- Dynamic rows loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Sample database records for dynamic loading
    const historyData = {
        'FAC-2026-001': {
            semesters: '6 Semesters', subjects: '14 Courses', eval: '4.85 / 5.0',
            rows: [
                { term: '2nd Sem, AY 2025-2026', title: 'IT 311 - Web Development II', time: 'MWF 08:00 AM - 11:00 AM', sec: 'BSIT 3-A', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' },
                { term: '2nd Sem, AY 2025-2026', title: 'IT 221 - Database Management', time: 'TTH 01:00 PM - 04:00 PM', sec: 'BSIT 2-C', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' },
                { term: '1st Sem, AY 2025-2026', title: 'IT 101 - Introduction to Computing', time: 'MWF 01:00 PM - 04:00 PM', sec: 'BSIT 1-A', units: '3.0', status: 'Completed', statusClass: 'text-muted border-secondary bg-transparent' }
            ]
        },
        'FAC-2026-002': {
            semesters: '4 Semesters', subjects: '9 Courses', eval: '4.70 / 5.0',
            rows: [
                { term: '2nd Sem, AY 2025-2026', title: 'TM 201 - Tourism Principles', time: 'MWF 10:00 AM - 01:00 PM', sec: 'BSTM 2-B', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' },
                { term: '1st Sem, AY 2025-2026', title: 'TM 102 - Hospitality Operations', time: 'TTH 08:00 AM - 11:00 AM', sec: 'BSTM 1-A', units: '3.0', status: 'Completed', statusClass: 'text-muted border-secondary bg-transparent' }
            ]
        },
        'FAC-2026-045': {
            semesters: '8 Semesters', subjects: '18 Courses', eval: '4.92 / 5.0',
            rows: [
                { term: '2nd Sem, AY 2025-2026', title: 'IT 412 - Information Assurance & Security', time: 'TTH 09:00 AM - 12:00 PM', sec: 'BSIT 4-A', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' },
                { term: '2nd Sem, AY 2025-2026', title: 'IT 322 - Network Administration', time: 'MWF 01:00 PM - 04:00 PM', sec: 'BSIT 3-B', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' }
            ]
        }
    };

    function loadTeachingHistory(id, name, dept, initials, avatarBgClass, title) {
        // Update header details
        document.getElementById('targetName').innerText = name;
        document.getElementById('targetId').innerText = id;
        document.getElementById('targetDept').innerText = dept;
        document.getElementById('targetTitle').innerText = title;
        
        const avatarEl = document.getElementById('targetAvatar');
        avatarEl.innerText = initials;
        avatarEl.className = `rounded-circle ${avatarBgClass} d-flex align-items-center justify-content-center fw-bold fs-5`;

        // Update statistics and table rows
        const data = historyData[id] || {
            semesters: '1 Semester', subjects: '2 Courses', eval: '4.50 / 5.0',
            rows: [{ term: '2nd Sem, AY 2025-2026', title: 'GEN 101 - General Education', time: 'MWF 08:00 AM - 11:00 AM', sec: 'SEC 1-A', units: '3.0', status: 'Ongoing', statusClass: 'text-success border-success bg-success' }]
        };

        document.getElementById('statSemesters').innerText = data.semesters;
        document.getElementById('statSubjects').innerText = data.subjects;
        document.getElementById('statEval').innerText = data.eval;

        // Render table rows
        const tableBody = document.getElementById('historyTableBody');
        tableBody.innerHTML = '';

        data.rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.className = 'border-secondary';
            tr.innerHTML = `
                <td class="fw-semibold text-white-50">${r.term}</td>
                <td>
                    <div class="fw-bold text-white">${r.title}</div>
                    <div class="text-muted small">${r.time}</div>
                </td>
                <td><span class="badge border border-secondary text-secondary">${r.sec}</span></td>
                <td>${r.units}</td>
                <td class="text-end">
                    <span class="badge border border-opacity-25 ${r.statusClass} bg-opacity-10">${r.status}</span>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>