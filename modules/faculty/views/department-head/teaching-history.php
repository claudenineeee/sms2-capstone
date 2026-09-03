<?php
/**
 * SMS 2 - Teaching History
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';
<<<<<<< HEAD
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();
=======
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4

// Establish Database Connection
if (!isset($pdo) || !$pdo) {
    $pdo = $conn ?? $db ?? null;
}

if (!$pdo) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'faculty_db';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
}

// 1. Identify Dept Head's Department
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$deptHeadDept  = null;

if ($currentUserId) {
    try {
<<<<<<< HEAD
        $stmt = $pdo->prepare("SELECT designated_department FROM faculty_db.faculty_profiles WHERE user_id = :uid OR id = :id LIMIT 1");
=======
        $stmt = $pdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = :uid OR id = :id LIMIT 1");
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
        $stmt->execute(['uid' => $currentUserId, 'id' => $currentUserId]);
        $row = $stmt->fetch();
        if ($row) {
            $deptHeadDept = trim($row['designated_department'] ?? '');
        }
    } catch (PDOException $e) {
        $deptHeadDept = null;
    }
}

if (empty($deptHeadDept)) {
    $deptHeadDept = trim($_SESSION['department'] ?? $_SESSION['designated_department'] ?? '');
}

// 2. Fetch Faculty Members from the SAME Department Only
$facultyMembers = [];

$facultyQuerySql = "
    SELECT fp.id, fp.faculty_id AS profile_faculty_no, fp.first_name, fp.last_name,
           fp.designated_department, fp.position, fp.email,
           f.faculty_id AS real_faculty_id
<<<<<<< HEAD
    FROM faculty_db.faculty_profiles fp
    LEFT JOIN faculty_db.faculty f ON f.faculty_id = fp.id
=======
    FROM faculty_profiles fp
    LEFT JOIN faculty f ON f.faculty_id = (
        SELECT f2.faculty_id
        FROM faculty f2
        WHERE (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email)
           OR f2.faculty_no = fp.faculty_id
        ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email) DESC
        LIMIT 1
    )
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
";

if (!empty($deptHeadDept)) {
    $stmt = $pdo->prepare($facultyQuerySql . "
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:dept)
        ORDER BY fp.last_name ASC
    ");
    $stmt->execute(['dept' => $deptHeadDept]);
    $facultyMembers = $stmt->fetchAll();
} else {
    $stmt = $pdo->query($facultyQuerySql . " ORDER BY fp.last_name ASC");
    $facultyMembers = $stmt->fetchAll();
}

// 3. Build Pure Database-Driven Teaching History Records per Faculty
$teachingHistoryDB = [];
$allAcademicYears = [];
$allSemesters = [];

foreach ($facultyMembers as $fac) {
    $facId = $fac['id'];
    $realFacultyId = $fac['real_faculty_id'] !== null ? (int)$fac['real_faculty_id'] : null;
    $isLinked = $realFacultyId !== null;

    $historyRecords = [];

    if ($isLinked) {
        try {
            $stmtHistory = $pdo->prepare("
                SELECT academic_year, semester, subject_code, subject_title, units, section, status, created_at
<<<<<<< HEAD
                FROM faculty_db.teaching_load_history
=======
                FROM teaching_load_history
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                WHERE faculty_id = :fac_id
                ORDER BY academic_year DESC, semester DESC, created_at DESC
            ");
            $stmtHistory->execute(['fac_id' => $realFacultyId]);
            $historyRecords = $stmtHistory->fetchAll();
        } catch (PDOException $e) {
            $historyRecords = [];
        }
    }

    if (empty($historyRecords)) {
        try {
            $stmtHistoryAlt = $pdo->prepare("
                SELECT academic_year, semester, subject_code, subject_title, units, section, status, created_at
<<<<<<< HEAD
                FROM faculty_db.teaching_load_history
=======
                FROM teaching_load_history
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                WHERE faculty_id = :fac_id_str OR faculty_no = :fac_no
                ORDER BY academic_year DESC, semester DESC, created_at DESC
            ");
            $stmtHistoryAlt->execute([
                'fac_id_str' => $facId,
                'fac_no'     => $fac['profile_faculty_no'] ?? ''
            ]);
            $historyRecords = $stmtHistoryAlt->fetchAll();
        } catch (PDOException $e) {
            $historyRecords = [];
        }
    }

    foreach ($historyRecords as $rec) {
        if (!empty($rec['academic_year']) && !in_array($rec['academic_year'], $allAcademicYears)) {
            $allAcademicYears[] = $rec['academic_year'];
        }
        if (!empty($rec['semester']) && !in_array($rec['semester'], $allSemesters)) {
            $allSemesters[] = $rec['semester'];
        }
    }

    $fullName = 'Prof. ' . $fac['first_name'] . ' ' . $fac['last_name'];
    $initials = strtoupper(substr($fac['first_name'], 0, 1) . substr($fac['last_name'], 0, 1));

    $teachingHistoryDB[$facId] = [
        'name'       => $fullName,
        'department' => $fac['designated_department'] ?? 'N/A',
        'position'   => $fac['position'] ?? 'Faculty',
        'initials'   => $initials,
        'isLinked'   => $isLinked,
        'history'    => $historyRecords
    ];
}

rsort($allAcademicYears);
sort($allSemesters);

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

<style>
<<<<<<< HEAD
=======
    /* Custom Scrollbar Styles */
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
<<<<<<< HEAD
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.25); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: rgba(13, 110, 253, 0.6); }
=======
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.25);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(13, 110, 253, 0.6);
    }
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4

    [data-bs-theme="light"] .custom-scrollbar,
    body:not([data-bs-theme="dark"]) .custom-scrollbar {
        scrollbar-color: rgba(13, 110, 253, 0.3) transparent;
    }
    [data-bs-theme="light"] .custom-scrollbar::-webkit-scrollbar-thumb,
    body:not([data-bs-theme="dark"]) .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(13, 110, 253, 0.3);
    }

<<<<<<< HEAD
    .faculty-list-scroll { max-height: 480px; overflow-y: auto; }
    .history-table-container { max-height: 420px; overflow-y: auto; }
    .history-table-custom { min-width: 700px; }
    .history-table-custom th { font-size: 12px !important; letter-spacing: 0.5px; white-space: nowrap; }
    .history-table-custom td { font-size: 13.5px !important; padding-top: 12px !important; padding-bottom: 12px !important; }

    @media (min-width: 992px) {
        .col-faculty-left { flex: 0 0 30% !important; max-width: 30% !important; }
        .col-faculty-right { flex: 0 0 70% !important; max-width: 70% !important; }
=======
    .faculty-list-scroll {
        max-height: 480px;
        overflow-y: auto;
    }

    .history-table-container {
        max-height: 420px;
        overflow-y: auto;
    }
    
    .history-table-custom {
        min-width: 700px; /* Forces proper horizontal scroll on mobile instead of crushing columns */
    }

    .history-table-custom th {
        font-size: 12px !important;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    .history-table-custom td {
        font-size: 13.5px !important;
        padding-top: 12px !important;
        padding-bottom: 12px !important;
    }

    /* Updated Layout: 30% Left (Department Faculty) and 70% Right (Teaching History Log) on large screens */
    @media (min-width: 992px) {
        .col-faculty-left {
            flex: 0 0 30% !important;
            max-width: 30% !important;
        }
        .col-faculty-right {
            flex: 0 0 70% !important;
            max-width: 70% !important;
        }
    }

    /* Responsive adjustments for smaller screens */
    @media (max-width: 575.98px) {
        .faculty-name {
            font-size: 12px !important;
        }
        .faculty-subject {
            font-size: 10px !important;
        }
        .faculty-card .btn {
            font-size: 10px !important;
            padding: 2px 8px !important;
        }
        .filter-select-group {
            width: 100% !important;
        }
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
    <div>
        <h1 class="h4 h3-md mb-1"><i class="fas fa-history text-primary me-2"></i>Teaching History</h1>
        <small class="text-muted small">Department faculty teaching logs and load history: <strong><?= htmlspecialchars(!empty($deptHeadDept) ? $deptHeadDept : 'All Departments') ?></strong></small>
    </div>
</div>

<div class="container-fluid my-2 my-sm-4 p-2 p-sm-3 rounded-3">
    <div class="row g-3 g-lg-4">
        
<<<<<<< HEAD
        <!-- LEFT COLUMN: Department Faculty -->
=======
        <!-- LEFT COLUMN: Department Faculty (30% Width on Large Screens) -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
        <div class="col-12 col-lg-4 col-faculty-left">
            <div class="card shadow-sm border border-secondary border-opacity-25 h-100">
                <div class="card-header border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center py-2 py-sm-3">
                    <h5 class="mb-0 fw-bold fs-6 small">Department Faculty</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="facultyCountBadge"><?= count($facultyMembers) ?> Members</span>
                </div>
                
                <div class="p-2 p-sm-3 border-bottom border-secondary border-opacity-25">
                    <div class="input-group input-group-sm mb-0">
<<<<<<< HEAD
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
=======
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                        <input type="text" id="facultySearchInput" class="form-control border-start-0 ps-0 bg-transparent" placeholder="Search faculty..." onkeyup="onSearchInput()">
                    </div>
                </div>

<<<<<<< HEAD
=======
                <!-- Scrollable Faculty Card List with multi-line wrap protection against overflow -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                <div class="card-body p-2 p-sm-3 custom-scrollbar faculty-list-scroll">
                    <div class="d-flex flex-column gap-2" id="facultyListContainer">
                        <?php if (!empty($facultyMembers)): ?>
                            <?php foreach ($facultyMembers as $index => $fac): ?>
                                <?php 
                                    $fId = $fac['id'];
                                    $fName = 'Prof. ' . $fac['first_name'] . ' ' . $fac['last_name'];
                                    $initials = strtoupper(substr($fac['first_name'], 0, 1) . substr($fac['last_name'], 0, 1));
                                ?>
                                <div class="faculty-card d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 gap-2" 
                                     data-name="<?= strtolower($fName) ?>">
                                    <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1;">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0" style="width: 34px; height: 34px; font-size: 11px;">
                                            <?= $initials ?>
                                        </div>
                                        <div style="min-width: 0; flex: 1;">
                                            <div class="fw-bold small text-break faculty-name" style="line-height: 1.2; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= htmlspecialchars($fName) ?>"><?= htmlspecialchars($fName) ?></div>
                                            <div class="text-muted faculty-subject text-truncate" style="font-size: 10.5px;">Dept: <?= htmlspecialchars($fac['designated_department'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 flex-shrink-0" style="font-size: 10.5px;" onclick="selectFaculty('<?= $fId ?>')">
                                        View
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-user-slash fs-4 mb-2 d-block opacity-50"></i>
                                <span class="small">No department faculty found.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

<<<<<<< HEAD
=======
                <!-- Footer Pagination Controls -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                <div class="card-footer border-top border-secondary border-opacity-25 py-2 px-3 d-flex align-items-center justify-content-between bg-transparent" id="facultyPaginationWrapper">
                    <small class="text-muted" style="font-size: 11px;" id="paginationInfo">Showing 1-10</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 faculty-pagination" id="paginationList">
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

<<<<<<< HEAD
        <!-- RIGHT COLUMN: Faculty Teaching History Log -->
        <div class="col-12 col-lg-8 col-faculty-right">
            
=======
        <!-- RIGHT COLUMN: Faculty Teaching History Log (70% Width on Large Screens) -->
        <div class="col-12 col-lg-8 col-faculty-right">
            
            <!-- Header Card -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
            <div class="card border shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex flex-row align-items-start justify-content-between gap-2">
                        <div class="d-flex align-items-start gap-3 w-100" style="min-width: 0;">
                            <div id="profAvatar" class="rounded-circle bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 d-flex align-items-center justify-content-center fw-bold fs-5 flex-shrink-0" style="width: 44px; height: 44px;">
                                --
                            </div>
                            <div class="w-100" style="min-width: 0;">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="mb-0 fw-bold fs-6 fs-sm-5 text-break">Faculty Teaching History Log</h5>
                                    <span class="badge border border-primary border-opacity-50 text-primary bg-primary bg-opacity-10" style="font-size: 10px;" id="profPosition">Faculty</span>
                                </div>
                                <div class="text-muted small">
                                    <span class="d-block d-sm-inline">Instructor: <strong class="text-body text-break" id="profName">-</strong></span>
                                    <span class="d-none d-sm-inline mx-1">|</span>
                                    <span class="d-block d-sm-inline">Department: <strong class="text-body text-break" id="profSubject">-</strong></span>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-sm btn-outline-primary flex-shrink-0 align-self-start" onclick="window.print()" title="Print History">
                            <i class="fas fa-print"></i>
                            <span class="d-none d-sm-inline ms-1">Print</span>
                        </button>
                    </div>
                </div>
            </div>

<<<<<<< HEAD
=======
            <!-- Teaching History Table Card with Filter Controls -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
            <div class="card border shadow-sm">
                <div class="card-header border-bottom border-secondary border-opacity-25 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <h6 class="mb-0 fw-bold small text-uppercase"><i class="fas fa-list-ul me-2 text-primary"></i>Assigned Subjects & Load History</h6>
                    
<<<<<<< HEAD
=======
                    <!-- School Year & Semester Selector Filters -->
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm filter-select-group" style="width: 150px;">
                            <select id="filterAcademicYear" class="form-select form-select-sm bg-transparent" onchange="renderData()">
                                <option value="">All Academic Years</option>
                                <?php foreach ($allAcademicYears as $ay): ?>
                                    <option value="<?= htmlspecialchars($ay) ?>"><?= htmlspecialchars($ay) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group input-group-sm filter-select-group" style="width: 140px;">
                            <select id="filterSemester" class="form-select form-select-sm bg-transparent" onchange="renderData()">
                                <option value="">All Semesters</option>
                                <?php foreach ($allSemesters as $sem): ?>
                                    <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2 py-1 ms-auto ms-md-0" id="totalHistoryCount">0 Records</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive custom-scrollbar history-table-container">
                        <table class="table table-hover align-middle mb-0 history-table-custom">
                            <thead class="table-light text-muted text-uppercase sticky-top">
                                <tr>
                                    <th class="ps-3 py-2">Academic Year / Semester</th>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th class="text-center">Units</th>
                                    <th class="text-center">Section</th>
                                    <th class="pe-3 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
<<<<<<< HEAD
    // Pass the PHP data to JavaScript
    const teachingHistoryDB = <?= json_encode($teachingHistoryDB) ?>;
    
    // CRITICAL FIX: Automatically select the first faculty member if none is selected
=======
    const teachingHistoryDB = <?= json_encode($teachingHistoryDB) ?>;
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
    let activeFacultyId = Object.keys(teachingHistoryDB)[0] || null;

    const itemsPerPage = 10;
    let currentPage = 1;
    let filteredCards = [];

    function selectFaculty(facId) {
        activeFacultyId = facId;
        document.getElementById('filterAcademicYear').value = '';
        document.getElementById('filterSemester').value = '';
        renderData();
    }

    function renderData() {
        if (!activeFacultyId || !teachingHistoryDB[activeFacultyId]) return;
        const fac = teachingHistoryDB[activeFacultyId];

        document.getElementById('profName').innerText = fac.name;
        document.getElementById('profSubject').innerText = fac.department;
        document.getElementById('profPosition').innerText = fac.position;
        
        const avatar = document.getElementById('profAvatar');
        avatar.innerText = fac.initials;

        const selectedAY = document.getElementById('filterAcademicYear').value;
        const selectedSem = document.getElementById('filterSemester').value;

        const filteredHistory = fac.history.filter(item => {
            let matchAY = !selectedAY || item.academic_year === selectedAY;
            let matchSem = !selectedSem || item.semester === selectedSem;
            return matchAY && matchSem;
        });

        const historyTableBody = document.getElementById('historyTableBody');
        historyTableBody.innerHTML = '';

        document.getElementById('totalHistoryCount').innerText = `${filteredHistory.length} Records`;

        if (filteredHistory.length === 0) {
            historyTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No teaching history logs found matching the selected filters.</td></tr>`;
            return;
        }

        filteredHistory.forEach(item => {
            historyTableBody.innerHTML += `
                <tr>
                    <td class="ps-3 fw-semibold text-body text-nowrap">
                        ${escapeHtml(item.academic_year)} <span class="text-muted fw-normal">(${escapeHtml(item.semester)})</span>
                    </td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 text-nowrap" style="font-size: 12px; padding: 5px 8px;">${escapeHtml(item.subject_code)}</span></td>
                    <td class="fw-semibold text-body" style="min-width: 180px;">${escapeHtml(item.subject_title)}</td>
                    <td class="text-center">${escapeHtml(String(item.units))}</td>
                    <td class="text-center text-nowrap">${escapeHtml(item.section)}</td>
                    <td class="pe-3 text-end"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-nowrap" style="font-size: 12px; padding: 5px 8px;">${escapeHtml(item.status || 'Active')}</span></td>
                </tr>
            `;
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function initFacultyPagination() {
        const query = document.getElementById('facultySearchInput').value.toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.faculty-card'));

        filteredCards = allCards.filter(card => {
            const name = card.getAttribute('data-name');
            return name.includes(query);
        });

        document.getElementById('facultyCountBadge').innerText = `${filteredCards.length} Members`;
        
        const totalPages = Math.ceil(filteredCards.length / itemsPerPage) || 1;
        if (currentPage > totalPages) {
            currentPage = 1;
        }

        renderFacultyPage();
    }

    function renderFacultyPage() {
        const allCards = document.querySelectorAll('.faculty-card');
        allCards.forEach(card => {
            card.classList.add('d-none');
            card.classList.remove('d-flex');
        });

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;
        const pageItems = filteredCards.slice(startIdx, endIdx);

        pageItems.forEach(card => {
            card.classList.remove('d-none');
            card.classList.add('d-flex');
        });

        const total = filteredCards.length;
        const startNum = total === 0 ? 0 : startIdx + 1;
        const endNum = Math.min(endIdx, total);
        document.getElementById('paginationInfo').innerText = `${startNum}-${endNum} of ${total}`;

        renderPaginationControls();
    }

    function renderPaginationControls() {
        const totalPages = Math.ceil(filteredCards.length / itemsPerPage) || 1;
        const paginationList = document.getElementById('paginationList');
        paginationList.innerHTML = '';

        if (totalPages <= 1) return;

        paginationList.innerHTML += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="goToPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            paginationList.innerHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="goToPage(${i})">${i}</button>
                </li>
            `;
        }

        paginationList.innerHTML += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="goToPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>
            </li>
        `;
    }

    function goToPage(page) {
        const totalPages = Math.ceil(filteredCards.length / itemsPerPage) || 1;
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderFacultyPage();
    }

    function onSearchInput() {
        currentPage = 1;
        initFacultyPagination();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFacultyPagination();
<<<<<<< HEAD
        
        // CRITICAL FIX: Ensure the first faculty is selected and data is rendered on load
        if (activeFacultyId) {
            renderData();
            
            // Highlight the active card visually
            const firstCard = document.querySelector(`.faculty-card button[onclick="selectFaculty('${activeFacultyId}')"]`);
            if (firstCard) {
                firstCard.closest('.faculty-card').classList.add('border-primary');
            }
        }
=======
        renderData();
>>>>>>> 0c5cd14bf9400247bc1a9cf8f8652084429b82a4
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>