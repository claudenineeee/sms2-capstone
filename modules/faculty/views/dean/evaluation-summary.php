<?php
/**
 * SMS 2 - Evaluation Summary
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Evaluation Summary';
$activeModule = 'faculty';
$activePage   = 'evaluation-summary';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Evaluation Summary', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chalkboard-teacher text-sms-primary me-2"></i>Evaluation Summary</h1>
    </div>
</div>

<div class="container-fluid my-4 bg-light text-dark p-3 rounded-3">
    <div class="row g-4">
        
        <!-- LEFT COLUMN: Faculty Selector -->
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="card shadow-sm border border-secondary border-opacity-25 bg-white h-100">
                <div class="card-header bg-white border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold fs-6 text-dark">Faculty Members</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="facultyCountBadge">2 Members</span>
                </div>
                
                <div class="p-3 border-bottom bg-light">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="facultySearchInput" class="form-control border-start-0 ps-0 bg-white" placeholder="Search by name..." onkeyup="filterFacultyList()">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted d-none" type="button" id="clearSearchBtn" onclick="clearFacultySearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <select id="facultyFilterSelect" class="form-select form-select-sm bg-white" onchange="filterFacultyList()">
                        <option value="ALL" selected>All Subjects / Depts</option>
                        <option value="OJT/Practicum">OJT / Practicum</option>
                        <option value="IT">IT / Web Dev</option>
                    </select>
                </div>

                <div class="card-body p-3 overflow-auto" style="max-height: 700px;">
                    <div class="d-flex flex-column gap-2" id="facultyListContainer">
                        
                        <!-- Faculty 1 -->
                        <div class="faculty-card d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light" 
                             data-name="Juan Dela Cruz" data-subject="OJT/Practicum 1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; font-size: 13px; min-width: 40px;">
                                    JD
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small faculty-name">Juan Dela Cruz</div>
                                    <div class="text-secondary faculty-subject" style="font-size: 11px;">OJT/Practicum 1</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" onclick="selectFaculty('FAC-EG-001')">
                                View
                            </button>
                        </div>

                        <!-- Faculty 2 -->
                        <div class="faculty-card d-flex align-items-center justify-content-between p-2 rounded-3 border border-secondary border-opacity-25 bg-light" 
                             data-name="Jane Smith" data-subject="IT 311 • Web Dev">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; font-size: 13px; min-width: 40px;">
                                    JS
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small faculty-name">Jane Smith</div>
                                    <div class="text-secondary faculty-subject" style="font-size: 11px;">IT 311 • Web Dev</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px;" onclick="selectFaculty('FAC-JS-002')">
                                View
                            </button>
                        </div>

                    </div>

                    <div id="noFacultyFound" class="text-center py-4 text-muted d-none">
                        <i class="fas fa-user-slash fs-4 mb-2 d-block opacity-50"></i>
                        <span class="small">No matching faculty members found.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Evaluation Summary -->
        <div class="col-12 col-lg-8 col-xl-9">
            
            <!-- Header Card -->
            <div class="card bg-white border shadow-sm mb-3">
                <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div id="profAvatar" class="rounded-circle bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 52px; height: 52px; min-width: 52px;">
                            EG
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-dark">Faculty Multi-Source Evaluation Summary</h4>
                                <span class="badge border border-success border-opacity-50 text-success bg-success bg-opacity-10" style="font-size: 10px;">AY 2025-2026</span>
                            </div>
                            <div class="text-muted small mt-1">
                                Instructor: <span class="fw-semibold text-body" id="profName">Juan Dela Cruz</span> | Subject: <span id="profSubject">OJT/Practicum 1</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm bg-white text-dark border-secondary border-opacity-25" style="min-width: 180px;">
                            <option selected>1st Sem, AY 2025-2026</option>
                            <option>2nd Sem, AY 2025-2026</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- EVALUATION SOURCE NAVIGATION TABS -->
            <ul class="nav nav-tabs mb-3 border-bottom" id="evalSourceTabs">
                <li class="nav-item">
                    <button class="nav-link active fw-bold text-dark" onclick="switchEvalTab('all', this)">
                        <i class="fas fa-chart-pie me-1 text-primary"></i> Overall Composite
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-secondary" onclick="switchEvalTab('student', this)">
                        <i class="fas fa-user-graduate me-1 text-info"></i> Student Evaluation (50%)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-secondary" onclick="switchEvalTab('peer', this)">
                        <i class="fas fa-user-friends me-1 text-warning"></i> Peer / Co-Worker (20%)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-secondary" onclick="switchEvalTab('dept', this)">
                        <i class="fas fa-user-tie me-1 text-success"></i> Dept. Class Observation (30%)
                    </button>
                </li>
            </ul>

            <div class="row g-4">
                
                <!-- Score Breakdown Sidebar -->
                <div class="col-12 col-xl-4">
                    
                    <!-- Main Score Summary -->
                    <div class="card bg-white border shadow-sm mb-3">
                        <div class="card-body p-4 text-center">
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.8px;" id="scoreCardTitle">Composite Rating</small>
                            
                            <div class="d-flex align-items-baseline justify-content-center gap-1 my-2">
                                <span class="display-3 fw-bold text-dark" id="profScore">4.82</span>
                                <span class="text-muted fs-5">/ 5.00</span>
                            </div>

                            <div class="d-flex justify-content-center gap-1 text-warning mb-3">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            </div>

                            <div class="p-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25 mb-3">
                                <span class="text-success fw-bold small" id="profRatingLabel">5 - Outstanding</span>
                            </div>

                            <div class="row g-2 pt-2 border-top">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border">
                                        <span class="d-block text-muted" style="font-size: 10px; text-transform: uppercase;">Evaluations</span>
                                        <span class="fw-bold text-dark fs-6" id="profCount">135</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border">
                                        <span class="d-block text-muted" style="font-size: 10px; text-transform: uppercase;">Response Rate</span>
                                        <span class="fw-bold text-primary fs-6" id="profRate">94.2%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Source Breakdown List (Visible on "Overall" view) -->
                    <div class="card bg-white border shadow-sm mb-3" id="sourceBreakdownCard">
                        <div class="card-header bg-white border-bottom py-2">
                            <h6 class="mb-0 fw-bold text-dark small text-uppercase">Evaluation Sources Weight</h6>
                        </div>
                        <div class="card-body p-3" style="font-size: 12px;">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-graduate me-1 text-info"></i> Student (50%)</span>
                                    <span class="fw-bold text-dark" id="scoreStudentWeight">4.82</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-friends me-1 text-warning"></i> Peer (20%)</span>
                                    <span class="fw-bold text-dark" id="scorePeerWeight">4.75</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tie me-1 text-success"></i> Dept Head (30%)</span>
                                    <span class="fw-bold text-dark" id="scoreDeptWeight">4.90</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Rating Scale Legend -->
                    <div class="card bg-white border shadow-sm">
                        <div class="card-header bg-white border-bottom py-2">
                            <h6 class="mb-0 fw-bold text-dark small text-uppercase">Adjectival Rating Scale</h6>
                        </div>
                        <div class="card-body p-3" style="font-size: 12px;">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                                <li class="d-flex justify-content-between"><span class="fw-bold text-success">5 - Outstanding</span> <span>4.50 - 5.00</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-primary">4 - Very Satisfactory</span> <span>3.50 - 4.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-info">3 - Satisfactory</span> <span>2.50 - 3.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-warning">2 - Average</span> <span>1.50 - 2.49</span></li>
                                <li class="d-flex justify-content-between"><span class="fw-bold text-danger">1 - Needs Improvement</span> <span>1.00 - 1.49</span></li>
                            </ul>
                        </div>
                    </div>

                </div>

                <!-- Main Content Area -->
                <div class="col-12 col-xl-8">
                    
                    <!-- Quantitative Criteria Breakdown -->
                    <div class="card bg-white border shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark small text-uppercase" id="criteriaHeaderTitle">PART I. Quantitative Category Summary</h6>
                            <span class="text-muted small">Score (1.00 - 5.00)</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3" id="criteriaContainer">
                                <!-- Dynamic JS Injection -->
                            </div>
                        </div>
                    </div>

                    <!-- Qualitative Feedback -->
                    <div class="card bg-white border shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark small text-uppercase" id="feedbackHeaderTitle">PART II. Qualitative Feedback</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                                    <thead class="table-light text-muted text-uppercase">
                                        <tr>
                                            <th class="ps-3" style="width: 50%;">Commendations / Strong Points</th>
                                            <th style="width: 50%;">Areas for Improvement / Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="commentsTableBody">
                                        <!-- Dynamic JS Injection -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>
</div>

<script>
    let activeFacultyId = 'FAC-EG-001';
    let currentTab = 'all';

    // Faculty DB supporting multi-source evaluations
    const performanceDB = {
        'FAC-EG-001': {
            name: 'Juan Dela Cruz',
            subject: 'OJT/Practicum 1',
            initials: 'JD',
            avatarBg: 'bg-primary bg-opacity-10 text-primary border-primary',
            compositeScore: '4.83',
            compositeRating: '5 - Outstanding',
            totalEvals: '135',
            totalRate: '94.2%',
            sources: {
                student: {
                    score: '4.82',
                    ratingText: '5 - Outstanding',
                    evalCount: '128',
                    responseRate: '92.4%',
                    categories: [
                        { letter: 'A', title: 'Knowledge of Subject Matter', score: '4.88 / 5.0', pct: '97.6%' },
                        { letter: 'B', title: 'Motivation & Teaching Strategy', score: '4.75 / 5.0', pct: '95.0%' },
                        { letter: 'C', title: 'Classroom Management', score: '4.80 / 5.0', pct: '96.0%' },
                        { letter: 'D', title: 'Punctuality & Attendance', score: '4.90 / 5.0', pct: '98.0%' }
                    ],
                    feedback: [
                        { strong: '"Very accommodating during OJT orientations and provides clear guidelines."', improvement: '"None so far, very organized."' },
                        { strong: '"Answers student inquiries promptly."', improvement: '"Could give earlier notice on extensions."' }
                    ]
                },
                peer: {
                    score: '4.75',
                    ratingText: '5 - Outstanding',
                    evalCount: '6',
                    responseRate: '100%',
                    categories: [
                        { letter: 'A', title: 'Collegiality & Teamwork', score: '4.80 / 5.0', pct: '96.0%' },
                        { letter: 'B', title: 'Professional Conduct', score: '4.90 / 5.0', pct: '98.0%' },
                        { letter: 'C', title: 'Contribution to Dept. Goals', score: '4.60 / 5.0', pct: '92.0%' },
                        { letter: 'D', title: 'Sharing of Learning Resources', score: '4.70 / 5.0', pct: '94.0%' }
                    ],
                    feedback: [
                        { strong: '"Great collaborator on curriculum revisions and always helpful to junior faculty."', improvement: '"Can lead more departmental research activities."' }
                    ]
                },
                dept: {
                    score: '4.90',
                    ratingText: '5 - Outstanding',
                    evalCount: '1',
                    responseRate: '100%',
                    categories: [
                        { letter: 'A', title: 'Lesson Plan & Syllabus Alignment', score: '5.00 / 5.0', pct: '100%' },
                        { letter: 'B', title: 'Classroom Delivery & Engagement', score: '4.85 / 5.0', pct: '97.0%' },
                        { letter: 'C', title: 'Use of Instructional Materials', score: '4.90 / 5.0', pct: '98.0%' },
                        { letter: 'D', title: 'Assessment Fairness & Mastery', score: '4.85 / 5.0', pct: '97.0%' }
                    ],
                    feedback: [
                        { strong: '"Exceptional control during class observation. Highly engaged students with clear outcomes."', improvement: '"Maintain current standards of excellence."' }
                    ]
                }
            }
        },
        'FAC-JS-002': {
            name: 'Jane Smith',
            subject: 'IT 311 • Web Development',
            initials: 'JS',
            avatarBg: 'bg-info bg-opacity-10 text-info border-info',
            compositeScore: '4.71',
            compositeRating: '5 - Outstanding',
            totalEvals: '100',
            totalRate: '90.5%',
            sources: {
                student: {
                    score: '4.65',
                    ratingText: '5 - Outstanding',
                    evalCount: '95',
                    responseRate: '89.0%',
                    categories: [
                        { letter: 'A', title: 'Knowledge of Subject Matter', score: '4.90 / 5.0', pct: '98.0%' },
                        { letter: 'B', title: 'Pacing and Hands-on Labs', score: '4.40 / 5.0', pct: '88.0%' }
                    ],
                    feedback: [
                        { strong: '"Mastered web technologies and gives real-world coding examples."', improvement: '"Pacing during live coding can be fast."' }
                    ]
                },
                peer: {
                    score: '4.80',
                    ratingText: '5 - Outstanding',
                    evalCount: '4',
                    responseRate: '100%',
                    categories: [
                        { letter: 'A', title: 'Technical Mentorship', score: '4.90 / 5.0', pct: '98.0%' },
                        { letter: 'B', title: 'Department Collaboration', score: '4.70 / 5.0', pct: '94.0%' }
                    ],
                    feedback: [
                        { strong: '"Helped set up the department laboratory environment effortlessly."', improvement: '"None."' }
                    ]
                },
                dept: {
                    score: '4.75',
                    ratingText: '5 - Outstanding',
                    evalCount: '1',
                    responseRate: '100%',
                    categories: [
                        { letter: 'A', title: 'Classroom Delivery & Tech Integration', score: '4.80 / 5.0', pct: '96.0%' },
                        { letter: 'B', title: 'Course Material Quality', score: '4.70 / 5.0', pct: '94.0%' }
                    ],
                    feedback: [
                        { strong: '"Excellent technical depth during classroom observation session."', improvement: '"Provide additional beginner exercise sheets."' }
                    ]
                }
            }
        }
    };

    function selectFaculty(facId) {
        activeFacultyId = facId;
        renderData();
    }

    function switchEvalTab(tabKey, element) {
        currentTab = tabKey;
        
        // Update active class on tabs
        document.querySelectorAll('#evalSourceTabs .nav-link').forEach(btn => {
            btn.classList.remove('active', 'fw-bold', 'text-dark');
            btn.classList.add('text-secondary');
        });
        element.classList.add('active', 'fw-bold', 'text-dark');
        element.classList.remove('text-secondary');

        renderData();
    }

    function renderData() {
        const fac = performanceDB[activeFacultyId];
        if (!fac) return;

        // Basic Header Info
        document.getElementById('profName').innerText = fac.name;
        document.getElementById('profSubject').innerText = fac.subject;
        
        const avatar = document.getElementById('profAvatar');
        avatar.className = `rounded-circle ${fac.avatarBg} d-flex align-items-center justify-content-center fw-bold fs-4`;
        avatar.innerText = fac.initials;

        // Set Weights Breakdown Display
        document.getElementById('scoreStudentWeight').innerText = fac.sources.student.score;
        document.getElementById('scorePeerWeight').innerText = fac.sources.peer.score;
        document.getElementById('scoreDeptWeight').innerText = fac.sources.dept.score;

        let activeSourceData;
        
        if (currentTab === 'all') {
            document.getElementById('scoreCardTitle').innerText = 'Overall Composite Rating';
            document.getElementById('profScore').innerText = fac.compositeScore;
            document.getElementById('profRatingLabel').innerText = fac.compositeRating;
            document.getElementById('profCount').innerText = fac.totalEvals;
            document.getElementById('profRate').innerText = fac.totalRate;
            document.getElementById('sourceBreakdownCard').classList.remove('d-none');
            
            // For 'All', combine student categories as default display or aggregate
            activeSourceData = fac.sources.student;
            document.getElementById('criteriaHeaderTitle').innerText = 'PART I. Aggregate Student Category Summary';
            document.getElementById('feedbackHeaderTitle').innerText = 'PART II. Combined Qualitative Feedback';
        } else {
            activeSourceData = fac.sources[currentTab];
            document.getElementById('sourceBreakdownCard').classList.add('d-none');
            
            const titles = {
                student: 'Student Evaluation Breakdown',
                peer: 'Peer / Co-Worker Evaluation Breakdown',
                dept: 'Department Classroom Observation Breakdown'
            };
            
            document.getElementById('scoreCardTitle').innerText = titles[currentTab];
            document.getElementById('profScore').innerText = activeSourceData.score;
            document.getElementById('profRatingLabel').innerText = activeSourceData.ratingText;
            document.getElementById('profCount').innerText = activeSourceData.evalCount;
            document.getElementById('profRate').innerText = activeSourceData.responseRate;
            document.getElementById('criteriaHeaderTitle').innerText = `PART I. ${titles[currentTab]}`;
            document.getElementById('feedbackHeaderTitle').innerText = `PART II. ${currentTab.toUpperCase()} Feedback`;
        }

        // Render Criteria Items
        const critContainer = document.getElementById('criteriaContainer');
        critContainer.innerHTML = '';
        activeSourceData.categories.forEach(item => {
            critContainer.innerHTML += `
                <div class="col-12 col-md-6">
                    <div class="p-2.5 rounded bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small" style="font-size: 11px;">${item.letter}. ${item.title}</span>
                            <span class="badge bg-white text-primary border border-secondary border-opacity-25" style="font-size: 10px;">${item.score}</span>
                        </div>
                        <div class="progress bg-white border" style="height: 5px;">
                            <div class="progress-bar bg-primary" style="width: ${item.pct}"></div>
                        </div>
                    </div>
                </div>
            `;
        });

        // Render Feedback Comments
        const commContainer = document.getElementById('commentsTableBody');
        commContainer.innerHTML = '';
        activeSourceData.feedback.forEach(item => {
            commContainer.innerHTML += `
                <tr>
                    <td class="ps-3 text-secondary border-end">${item.strong}</td>
                    <td class="text-secondary">${item.improvement}</td>
                </tr>
            `;
        });
    }

    function filterFacultyList() {
        const query = document.getElementById('facultySearchInput').value.toLowerCase().trim();
        const filterCategory = document.getElementById('facultyFilterSelect').value.toLowerCase();
        const clearBtn = document.getElementById('clearSearchBtn');
        const cards = document.querySelectorAll('.faculty-card');
        const noResultsMsg = document.getElementById('noFacultyFound');
        const countBadge = document.getElementById('facultyCountBadge');

        if (query.length > 0) clearBtn.classList.remove('d-none');
        else clearBtn.classList.add('d-none');

        let visibleCount = 0;
        cards.forEach(card => {
            const name = card.getAttribute('data-name').toLowerCase();
            const subject = card.getAttribute('data-subject').toLowerCase();
            const matchesQuery = name.includes(query) || subject.includes(query);
            const matchesFilter = filterCategory === 'all' || subject.includes(filterCategory);

            if (matchesQuery && matchesFilter) {
                card.classList.remove('d-none');
                card.classList.add('d-flex');
                visibleCount++;
            } else {
                card.classList.remove('d-flex');
                card.classList.add('d-none');
            }
        });

        if (visibleCount === 0) noResultsMsg.classList.remove('d-none');
        else noResultsMsg.classList.add('d-none');

        countBadge.innerText = `${visibleCount} Member${visibleCount !== 1 ? 's' : ''}`;
    }

    function clearFacultySearch() {
        document.getElementById('facultySearchInput').value = '';
        document.getElementById('facultyFilterSelect').value = 'ALL';
        filterFacultyList();
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderData();
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>