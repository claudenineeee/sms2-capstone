<?php
/**
 * SMS 2 - Subject Load Tracker (Dean View)
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';
// Connect to the separate faculty module database where profiles and teaching histories reside[cite: 3]
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getFacultyDatabaseConnection();
} catch (Exception $e) {
    die('<div style="padding: 20px; font-family: sans-serif; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 20px; border-radius: 4px;">'
        . '<h3>Database Connection Error</h3>'
        . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
        . '</div>');
}

// 1. Identify Dean's Department / Scope
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$deanDept = null;

if ($currentUserId) {
    try {
        $stmt = $pdo->prepare("SELECT designated_department FROM faculty_profiles WHERE user_id = :uid OR id = :id LIMIT 1");
        $stmt->execute(['uid' => $currentUserId, 'id' => $currentUserId]);
        $row = $stmt->fetch();
        if ($row) {
            $deanDept = trim($row['designated_department'] ?? '');
        }
    } catch (PDOException $e) {
        $deanDept = null;
    }
}

if (empty($deanDept)) {
    $deanDept = trim($_SESSION['department'] ?? $_SESSION['designated_department'] ?? '');
}

// 2. Fetch Distinct Academic Terms/Years & Semesters dynamically from teaching load history
$academicTerms = [];
try {
    $stmtTerms = $pdo->query("
        SELECT DISTINCT academic_year, semester 
        FROM teaching_load_history 
        WHERE academic_year IS NOT NULL AND semester IS NOT NULL
        ORDER BY academic_year DESC, semester DESC
    ");
    $academicTerms = $stmtTerms->fetchAll();
} catch (PDOException $e) {
    $academicTerms = [];
}

// Default fallback term if none exist in history
$selectedTerm = $_GET['term'] ?? '';
if (empty($selectedTerm) && !empty($academicTerms)) {
    $selectedTerm = $academicTerms[0]['academic_year'] . '-' . $academicTerms[0]['semester'];
} elseif (empty($selectedTerm)) {
    $selectedTerm = '2025-2026-2';
}

// Parse selected term back into components
$termParts = explode('-', $selectedTerm);
$selectedAY = (count($termParts) >= 2) ? $termParts[0] . '-' . $termParts[1] : '2025-2026';
$selectedSem = $termParts[2] ?? '2';

// 3. Fetch Faculty Members strictly restricted by Dean's Designated Department Scope
$facultyMembers = [];
$facultyQuerySql = "
    SELECT fp.id, fp.faculty_id AS profile_faculty_no, fp.first_name, fp.last_name,
           fp.designated_department, fp.position, fp.email,
           f.faculty_id AS real_faculty_id
    FROM faculty_profiles fp
    LEFT JOIN faculty f ON f.faculty_id = (
        SELECT f2.faculty_id
        FROM faculty f2
        WHERE (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email)
           OR f2.faculty_no = fp.faculty_id
        ORDER BY (fp.email IS NOT NULL AND fp.email <> '' AND f2.email = fp.email) DESC
        LIMIT 1
    )
";

if (!empty($deanDept)) {
    $stmt = $pdo->prepare($facultyQuerySql . "
        WHERE LOWER(TRIM(fp.designated_department)) = LOWER(:dept)
        ORDER BY fp.last_name ASC
    ");
    $stmt->execute(['dept' => $deanDept]);
    $facultyMembers = $stmt->fetchAll();
} else {
    // Fallback if no dean department found (though normally restricted)
    $stmt = $pdo->query($facultyQuerySql . " ORDER BY fp.last_name ASC");
    $facultyMembers = $stmt->fetchAll();
}

// 4. Build Dynamic Faculty Loading Data and Analytics
$facultyLoadData = [];
$totalActiveFaculty = count($facultyMembers);
$fullyLoadedCount = 0;
$totalUnassignedUnits = 0;
$maxUnitsLimit = 21; // Standard max load units

foreach ($facultyMembers as $fac) {
    $facId = $fac['id'];
    $realFacultyId = $fac['real_faculty_id'] !== null ? (int)$fac['real_faculty_id'] : null;
    $isLinked = $realFacultyId !== null;

    $assignedSubjects = [];
    $totalAssignedUnits = 0.0;

    if ($isLinked) {
        try {
            $stmtLoad = $pdo->prepare("
                SELECT subject_code, subject_title, units, section, status 
                FROM teaching_load_history
                WHERE faculty_id = :fac_id AND academic_year = :ay AND semester = :sem
            ");
            $stmtLoad->execute(['fac_id' => $realFacultyId, 'ay' => $selectedAY, 'sem' => $selectedSem]);
            $assignedSubjects = $stmtLoad->fetchAll();
        } catch (PDOException $e) {
            $assignedSubjects = [];
        }
    }

    if (empty($assignedSubjects)) {
        try {
            $stmtLoadAlt = $pdo->prepare("
                SELECT subject_code, subject_title, units, section, status 
                FROM teaching_load_history
                WHERE (faculty_id = :fac_id_str OR faculty_no = :fac_no) AND academic_year = :ay AND semester = :sem
            ");
            $stmtLoadAlt->execute([
                'fac_id_str' => $facId,
                'fac_no'     => $fac['profile_faculty_no'] ?? '',
                'ay'         => $selectedAY,
                'sem'        => $selectedSem
            ]);
            $assignedSubjects = $stmtLoadAlt->fetchAll();
        } catch (PDOException $e) {
            $assignedSubjects = [];
        }
    }

    foreach ($assignedSubjects as $subj) {
        $totalAssignedUnits += floatval($subj['units'] ?? 0);
    }

    $isFullyLoaded = ($totalAssignedUnits >= $maxUnitsLimit);
    if ($isFullyLoaded) {
        $fullyLoadedCount++;
    } else {
        $totalUnassignedUnits += max(0, $maxUnitsLimit - $totalAssignedUnits);
    }

    $fullName = 'Prof. ' . $fac['first_name'] . ' ' . $fac['last_name'];
    $initials = strtoupper(substr($fac['first_name'], 0, 1) . substr($fac['last_name'], 0, 1));

    $facultyLoadData[$facId] = [
        'id'             => $facId,
        'name'           => $fullName,
        'initials'       => $initials,
        'department'     => $fac['designated_department'] ?? 'N/A',
        'position'       => $fac['position'] ?? 'Faculty',
        'profile_no'     => $fac['profile_faculty_no'] ?? $facId,
        'total_units'    => $totalAssignedUnits,
        'max_units'      => $maxUnitsLimit,
        'is_fully_loaded'=> $isFullyLoaded,
        'subjects'       => $assignedSubjects
    ];
}

$fullyLoadedPercentage = $totalActiveFaculty > 0 ? round(($fullyLoadedCount / $totalActiveFaculty) * 100) : 0;

$pageTitle    = 'Subject Load Tracker';
$activeModule = 'faculty';
$activePage   = 'subject-load-tracker';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Subject Load Tracker', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

echo '<link rel="stylesheet" href="' . BASE_URL . '/modules/faculty/assets/css/faculty.css">';
?>

<style>
/* Responsive compact typography matching teaching history standards */
@media (max-width: 768px) {
    .faculty-item .fw-bold { font-size: 0.825rem !important; }
    .faculty-item small { font-size: 0.7rem !important; }
    .faculty-item .badge { font-size: 0.65rem !important; padding: 0.2rem 0.4rem !important; }
    .faculty-avatar { width: 32px !important; height: 32px !important; font-size: 11px !important; }
    .page-header h1 { font-size: 1.25rem !important; }
}
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header with Global Academic Term / School Year Filter -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-0 fs-4"><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Subject Load Tracker</h1>
        <small class="text-muted">Dean Department Scope: <strong><?= htmlspecialchars(!empty($deanDept) ? $deanDept : 'All Departments') ?></strong></small>
    </div>
    
    <!-- Academic Term / School Year Filter -->
    <div class="d-flex align-items-center gap-2 bg-body p-2 rounded border border-secondary-subtle shadow-sm">
        <label for="academicTermSelect" class="form-label mb-0 text-body-secondary small fw-bold text-nowrap">
            <i class="fas fa-calendar-alt text-primary me-1"></i> Academic Term:
        </label>
        <select class="form-select form-select-sm bg-body text-body border-secondary-subtle fw-semibold" id="academicTermSelect" style="min-width: 230px;" onchange="changeAcademicTerm(this.value)">
            <?php if (!empty($academicTerms)): ?>
                <?php foreach ($academicTerms as $term): ?>
                    <?php 
                        $termVal = $term['academic_year'] . '-' . $term['semester'];
                        $termLabel = 'A.Y. ' . $term['academic_year'] . ' | ' . $term['semester'] . ' Semester';
                    ?>
                    <option value="<?= htmlspecialchars($termVal) ?>" <?= $selectedTerm === $termVal ? 'selected' : '' ?>><?= htmlspecialchars($termLabel) ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="2025-2026-2" <?= $selectedTerm === '2025-2026-2' ? 'selected' : '' ?>>A.Y. 2025–2026 | 2nd Semester</option>
                <option value="2025-2026-1" <?= $selectedTerm === '2025-2026-1' ? 'selected' : '' ?>>A.Y. 2025–2026 | 1st Semester</option>
            <?php endif; ?>
        </select>
    </div>
</div>

<!-- Subject Load Tracker Section Header & Quick Analytics -->
<div class="row g-3 mb-4 dashboard-stats">
    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card primary border shadow-sm">
            <div class="card-body d-flex align-items-center py-3">
                <div class="stat-icon me-3 text-primary fs-4">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Total Active Faculty</h6>
                    <h4 class="mb-0 fw-bold fs-5"><?= $totalActiveFaculty ?> Professors</h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card success border shadow-sm">
            <div class="card-body d-flex align-items-center py-3">
                <div class="stat-icon me-3 text-success fs-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Fully Loaded Faculty</h6>
                    <h4 class="mb-0 fw-bold fs-5"><?= $fullyLoadedCount ?> / <?= $totalActiveFaculty ?> (<?= $fullyLoadedPercentage ?>%)</h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <section class="card stat-card warning border shadow-sm">
            <div class="card-body d-flex align-items-center py-3">
                <div class="stat-icon me-3 text-warning fs-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold" style="font-size: 0.75rem;">Unassigned Units</h6>
                    <h4 class="mb-0 fw-bold fs-5"><?= $totalUnassignedUnits ?> Units Remaining</h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Main Section Grid -->
<div class="row g-4" id="subject-load-section">
    
    <!-- Left Column: Faculty Directory Loading Overview -->
    <div class="col-xl-5 col-lg-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            
            <!-- Card Header with Department Scope & Search -->
            <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary fw-bold small">
                        <i class="fas fa-users-cog me-2"></i>Faculty Loading Directory
                    </h6>
                    <small class="text-body-secondary" id="visibleFacultyCount" style="font-size: 0.75rem;">Showing <?= count($facultyLoadData) ?> Faculty</small>
                </div>

                <!-- Controls Row: Locked Department Badge / Filter & Search Bar -->
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <input type="text" class="form-control form-control-sm bg-body-tertiary text-body border-secondary-subtle fw-bold" value="Dept: <?= htmlspecialchars(!empty($deanDept) ? $deanDept : 'BSIT') ?>" readonly disabled>
                        <input type="hidden" id="deptFilter" value="<?= htmlspecialchars(!empty($deanDept) ? $deanDept : 'BSIT') ?>">
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="input-group input-group-sm">
                            <input type="text" id="facultySearchInput" onkeyup="filterFaculty()" class="form-control bg-body text-body border-secondary-subtle" placeholder="Search name or ID...">
                            <button class="btn btn-outline-secondary border-secondary-subtle" type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="facultyList">
                    <?php if (!empty($facultyLoadData)): ?>
                        <?php $index = 0; foreach ($facultyLoadData as $fId => $fac): ?>
                            <?php 
                                $unitsText = $fac['total_units'] . ' / ' . $fac['max_units'] . ' Units';
                                $searchString = strtolower($fac['name'] . ' ' . $fac['profile_no'] . ' ' . $fac['department']);
                                $progressWidth = min(100, round(($fac['total_units'] / $fac['max_units']) * 100));
                            ?>
                            <a href="javascript:void(0);" 
                               class="list-group-item list-group-item-action faculty-item bg-transparent text-body px-3 py-2 border-0 border-bottom border-secondary-subtle" 
                               data-dept="<?= htmlspecialchars($fac['department']) ?>"
                               data-search="<?= htmlspecialchars($searchString) ?>"
                               data-faculty-id="<?= htmlspecialchars($fId) ?>"
                               onclick="selectFacultyItem(this)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1;">
                                        <div class="faculty-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0" style="width: 36px; height: 36px; font-size: 12px;">
                                            <?= htmlspecialchars($fac['initials']) ?>
                                        </div>
                                        <div style="min-width: 0; flex: 1;">
                                            <div class="fw-bold mb-0 text-body text-truncate" style="font-size: 0.875rem;"><?= htmlspecialchars($fac['name']) ?></div>
                                            <small class="text-body-secondary text-truncate d-block" style="font-size: 0.75rem;">ID: <?= htmlspecialchars($fac['profile_no']) ?> • <span class="badge bg-secondary text-white border border-secondary px-1" style="font-size: 0.65rem;"><?= htmlspecialchars($fac['department']) ?></span></small>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span class="badge <?= $fac['is_fully_loaded'] ? 'bg-success text-white' : 'bg-warning text-dark' ?> fw-bold px-2 py-1" style="font-size: 0.7rem;"><?= $unitsText ?></span>
                                        <div class="progress mt-1 bg-body-tertiary" style="height: 4px; width: 80px;">
                                            <div class="progress-bar <?= $fac['is_fully_loaded'] ? 'bg-success' : 'bg-warning' ?>" role="progressbar" style="width: <?= $progressWidth ?>%;"></div>
                                        </div>
                                        <small class="<?= $fac['is_fully_loaded'] ? 'text-success' : 'text-warning' ?> fw-semibold mt-1 d-block" style="font-size: 0.65rem;"><?= $fac['is_fully_loaded'] ? 'Full Load' : ($fac['max_units'] - $fac['total_units']) . ' Units Rem.' ?></small>
                                    </div>
                                </div>
                            </a>
                            <?php $index++; endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-user-slash fs-4 mb-2 d-block opacity-50"></i>
                            <span class="small">No faculty members found for this department scope.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Empty State Message when no match is found -->
                <div id="noFacultyMessage" class="text-center py-4 d-none">
                    <i class="fas fa-user-slash text-body-secondary fs-3 mb-2"></i>
                    <p class="text-body-secondary mb-0 small">No faculty members found matching your search.</p>
                </div>
            </div>
            
            <div class="card-footer bg-body-tertiary d-flex flex-column gap-2 py-2 border-top border-secondary-subtle">
                <!-- Pagination Controls -->
                <nav aria-label="Faculty pagination">
                    <ul class="pagination pagination-sm justify-content-center mb-0" id="facultyPagination"></ul>
                </nav>
                <div class="text-center">
                    <small class="text-body-secondary" id="footerFacultyCount" style="font-size: 0.75rem;">Showing <?= count($facultyLoadData) ?> of <?= $totalActiveFaculty ?> Faculty Members</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Subject Load Matrix View -->
    <div class="col-xl-7 col-lg-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3">
                <div>
                    <h6 class="mb-0 text-primary fw-bold small" id="target-faculty-name">
                        <i class="fas fa-book-open me-2"></i>Subject Loading Matrix: <span class="text-body" id="activeFacultyName">-</span>
                    </h6>
                    <small class="text-body-secondary" id="target-faculty-meta" style="font-size: 0.75rem;">Max Allowed: 21 Units | Assigned: 0 Units | Remaining: 21 Units</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary border-secondary-subtle" title="Export Schedule PDF" onclick="window.print()">
                        <i class="fas fa-file-pdf text-danger"></i>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-dark text-body-secondary text-uppercase" style="font-size: 0.7rem;">
                            <tr class="border-bottom border-secondary-subtle">
                                <th class="ps-3" style="width: 25%">Code</th>
                                <th style="width: 55%">Subject Description</th>
                                <th class="text-center" style="width: 20%">Units</th>
                            </tr>
                        </thead>
                        <tbody id="activeFacultySubjectsBody">
                            <!-- Populated dynamically via JS from PHP dataset -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer & Commitment Action -->
            <div class="card-footer bg-body-tertiary border-top border-secondary-subtle d-flex justify-content-between align-items-center py-2">
                <div class="d-flex align-items-center gap-3">
                    <small class="text-body-secondary" style="font-size: 0.75rem;">Total Classes: <strong class="text-body" id="footerTotalClasses">0 Sections</strong></small>
                    <small class="text-body-secondary" style="font-size: 0.75rem;">Total Units: <strong class="text-primary" id="footerTotalUnits">0.0 Units</strong></small>
                </div>
                <button type="button" class="btn btn-primary btn-sm px-3 py-1" style="font-size: 0.75rem;" onclick="commitLoadingUpdates()">
                    <i class="fas fa-save me-1"></i> Commit Loading Updates
                </button>
            </div>

        </div>
    </div>

</div>

<!-- Filter, Pagination & Interactivity Logic JS -->
<script>
    const facultyLoadDB = <?= json_encode($facultyLoadData) ?>;
    const itemsPerPage = 10;
    let currentPage = 1;

    function changeAcademicTerm(termValue) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('term', termValue);
        window.location.href = currentUrl.toString();
    }

    function filterFaculty(targetPage = 1) {
        currentPage = targetPage;
        const searchVal = document.getElementById('facultySearchInput').value.toLowerCase().trim();
        const items = Array.from(document.querySelectorAll('.faculty-item'));
        const noMsg = document.getElementById('noFacultyMessage');
        
        let matchingItems = [];

        items.forEach(item => {
            const itemSearch = item.getAttribute('data-search').toLowerCase();
            const matchesSearch = (searchVal === '' || itemSearch.includes(searchVal));

            if (matchesSearch) {
                matchingItems.push(item);
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });

        const totalMatching = matchingItems.length;
        const totalPages = Math.ceil(totalMatching / itemsPerPage) || 1;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        items.forEach(item => item.style.display = 'none');

        matchingItems.forEach((item, index) => {
            if (index >= startIndex && index < endIndex) {
                item.style.display = 'block';
            }
        });

        if (totalMatching === 0) {
            noMsg.classList.remove('d-none');
        } else {
            noMsg.classList.add('d-none');
        }

        renderPagination(totalPages);

        document.getElementById('visibleFacultyCount').textContent = `Showing ${totalMatching} Faculty`;
        document.getElementById('footerFacultyCount').textContent = `Showing ${Math.min(totalMatching, itemsPerPage)} of ${totalMatching} Faculty Members (Filtered)`;
    }

    function renderPagination(totalPages) {
        const paginationUl = document.getElementById('facultyPagination');
        paginationUl.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous Button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link py-1 px-2" href="javascript:void(0);" onclick="filterFaculty(${currentPage - 1})" style="font-size: 0.75rem;">&laquo;</a>`;
        paginationUl.appendChild(prevLi);

        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${currentPage === i ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link py-1 px-2" href="javascript:void(0);" onclick="filterFaculty(${i})" style="font-size: 0.75rem;">${i}</a>`;
            paginationUl.appendChild(pageLi);
        }

        // Next Button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link py-1 px-2" href="javascript:void(0);" onclick="filterFaculty(${currentPage + 1})" style="font-size: 0.75rem;">&raquo;</a>`;
        paginationUl.appendChild(nextLi);
    }

    function selectFacultyItem(element) {
        document.querySelectorAll('.faculty-item').forEach(el => {
            el.classList.remove('bg-primary', 'bg-opacity-10', 'border-start', 'border-4', 'border-primary');
            el.classList.add('bg-transparent');
        });

        element.classList.remove('bg-transparent');
        element.classList.add('bg-primary', 'bg-opacity-10', 'border-start', 'border-4', 'border-primary');

        const facId = element.getAttribute('data-faculty-id');
        loadFacultyMatrix(facId);
    }

    function loadFacultyMatrix(facId) {
        const fac = facultyLoadDB[facId];
        if (!fac) return;

        document.getElementById('activeFacultyName').textContent = fac.name;
        
        const remainingUnits = Math.max(0, fac.max_units - fac.total_units);
        document.getElementById('target-faculty-meta').textContent = `Max Allowed: ${fac.max_units} Units | Assigned: ${fac.total_units} Units | Remaining: ${remainingUnits} Units`;

        const tbody = document.getElementById('activeFacultySubjectsBody');
        tbody.innerHTML = '';

        if (!fac.subjects || fac.subjects.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4"><i class="fas fa-folder-open d-block fs-4 mb-2 opacity-50"></i>No subject loads assigned for this academic term.</td></tr>`;
        } else {
            fac.subjects.forEach(subj => {
                tbody.innerHTML += `
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="ps-3">
                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.8rem;">${escapeHtml(subj.subject_code)}</span>
                            <span class="badge bg-secondary text-white border border-secondary d-block mt-1 font-monospace" style="font-size: 0.65rem;">Sec: ${escapeHtml(subj.section)}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-body" style="font-size: 0.825rem;">${escapeHtml(subj.subject_title)}</div>
                            <small class="text-body-secondary" style="font-size: 0.7rem;"><i class="fas fa-layer-group me-1"></i>Status: ${escapeHtml(subj.status || 'Active')}</small>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-body" style="font-size: 0.825rem;">${parseFloat(subj.units).toFixed(1)}</span>
                        </td>
                    </tr>
                `;
            });
        }

        document.getElementById('footerTotalClasses').textContent = `${fac.subjects ? fac.subjects.length : 0} Sections`;
        document.getElementById('footerTotalUnits').textContent = `${parseFloat(fac.total_units).toFixed(1)} Units`;
    }

    function commitLoadingUpdates() {
        alert("Faculty teaching load successfully committed.");
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // Initialize first faculty member selection and pagination on page load
    document.addEventListener('DOMContentLoaded', () => {
        filterFaculty(1);
        const firstFacultyItem = document.querySelector('.faculty-item');
        if (firstFacultyItem) {
            firstFacultyItem.classList.add('bg-primary', 'bg-opacity-10', 'border-start', 'border-4', 'border-primary');
            firstFacultyItem.classList.remove('bg-transparent');
            const firstId = firstFacultyItem.getAttribute('data-faculty-id');
            if (firstId) {
                loadFacultyMatrix(firstId);
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>