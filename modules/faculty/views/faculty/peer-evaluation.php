<?php
/**
 * Peer Evaluation Directory
 * Purpose: View and submit peer evaluations
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Peer Evaluation';
$activeModule = 'faculty';
$activePage   = 'peer-evaluation';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Peer Evaluation', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header & Department Context -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0 fw-bold text-body">
            <i class="fas fa-user-check text-primary me-2"></i>Peer Evaluation Directory
        </h1>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
            <i class="fas fa-building me-1"></i> Department: BSIT
        </span>
    </div>
</div>

<!-- Evaluation Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-primary-subtle text-primary rounded-3 me-3 fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Department Peers</h6>
                    <h4 class="mb-0 fw-bold text-body">4 Colleagues</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card bg-body text-body border-secondary-subtle shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-success-subtle text-success rounded-3 me-3 fs-4 d-flex align-items-center justify-content-center">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-body-secondary mb-0 small text-uppercase fw-bold">Completed Ratings</h6>
                    <h4 class="mb-0 fw-bold text-body">2 / 4 Evaluated</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Peers Table Card -->
<div class="card bg-body text-body border-secondary-subtle shadow-sm mb-4">
    <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <h6 class="mb-0 text-primary fw-bold">
                    <i class="fas fa-list-ul me-2"></i>List of Co-Teachers — Department
                </h6>
                <small class="text-body-secondary">Rate your peers for current academic term</small>
            </div>
            <div class="col-12 col-md-6 d-flex gap-2 justify-content-md-end">
                <div class="input-group input-group-sm" style="max-width: 240px;">
                    <input type="text" id="peerSearchInput" class="form-control bg-body text-body border-secondary-subtle" placeholder="Search..." onkeyup="filterPeers()">
                    <button class="btn btn-outline-secondary border-secondary-subtle" type="button"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="peerTable">
                <thead class="bg-body-tertiary text-body-secondary small text-uppercase border-bottom border-secondary-subtle">
                    <tr>
                        <th class="ps-3" style="width: 45%;">Faculty Member</th>
                        <th class="text-end pe-3" style="width: 20%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="peer-row border-bottom border-secondary-subtle" data-status="PENDING" data-search="emerson gelera associate professor">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    EG
                                </div>
                                <div>
                                    <div class="fw-bold text-body">Prof. Emerson Gelera</div>
                                    <small class="text-body-secondary">ID: FAC-2021-004 •</small>
                                </div>
                            </div>
                        </td>                      
                        <td class="text-end pe-3">
                            <button class="btn btn-primary rounded-pill px-3 py-1 shadow-sm d-inline-flex align-items-center justify-content-center" onclick="openEvaluationModal('FAC-2021-004', 'Prof. Emerson Gelera', 'BSIT')" title="Evaluate Now" aria-label="Evaluate Now">
                                <i class="fas fa-star text-white"></i>
                            </button>
                        </td>
                    </tr>

                    <tr class="peer-row border-bottom border-secondary-subtle" data-status="COMPLETED" data-search="jorge lucero assistant professor">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success-subtle text-success fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    JL
                                </div>
                                <div>
                                    <div class="fw-bold text-body">Prof. Jorge Lucero</div>
                                    <small class="text-body-secondary">ID: FAC-2019-012 • </small>
                                </div>
                            </div>
                        </td>                        
                        <td class="text-end pe-3">
                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold text-uppercase border-0">DONE</span>
                        </td>
                    </tr>

                    <tr class="peer-row border-bottom border-secondary-subtle" data-status="PENDING" data-search="ian tiao instructor i">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    IT
                                </div>
                                <div>
                                    <div class="fw-bold text-body">Prof. Ian Tiao</div>
                                    <small class="text-body-secondary">ID: FAC-2023-088 • </small>
                                </div>
                            </div>
                        </td>                      
                        <td class="text-end pe-3">
                            <button class="btn btn-primary rounded-pill px-3 py-1 shadow-sm d-inline-flex align-items-center justify-content-center" onclick="openEvaluationModal('FAC-2021-004', 'Prof. Emerson Gelera', 'BSIT')" title="Evaluate Now" aria-label="Evaluate Now">
                                <i class="fas fa-star text-white"></i>
                            </button>
                        </td>
                    </tr>

                    <tr class="peer-row border-bottom border-secondary-subtle" data-status="COMPLETED" data-search="maria santos assistant professor">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success-subtle text-success fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    MS
                                </div>
                                <div>
                                    <div class="fw-bold text-body">Prof. Maria Santos</div>
                                    <small class="text-body-secondary">ID: FAC-2020-031 •</small> 
                                </div>
                            </div>
                        </td>                      
                        <td class="text-end pe-3">
                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold text-uppercase border-0">DONE</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="noPeersMessage" class="text-center py-5 d-none">
            <i class="fas fa-search text-body-secondary fs-2 mb-2"></i>
            <p class="text-body-secondary mb-0">No department peers found matching your criteria.</p>
        </div>
    </div>

    <!-- Table Footer with Pagination Controls -->
    <div class="card-footer bg-body-tertiary border-top border-secondary-subtle py-2 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <small class="text-body-secondary" id="peerPaginationInfo">Showing 0 entries</small>
        <nav aria-label="Peer Table Pagination">
            <ul class="pagination pagination-sm mb-0" id="peerPagination">
            </ul>
        </nav>
    </div>
</div>

<!-- Peer Evaluation Rating Form Modal (Explicit Scrollbar Enabled) -->
<div class="modal fade" id="evaluateFacultyModal" tabindex="-1" aria-labelledby="evaluateFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-body text-body border-secondary-subtle shadow-lg">           
            <form action="process_peer_evaluation.php" method="POST" id="peerEvaluationForm" class="d-flex flex-column">
                <input type="hidden" name="faculty_id" id="modalFacultyId">
<div class="modal-header bg-body-tertiary border-bottom border-secondary-subtle py-3">
    <div>
        <h5 class="modal-title fw-bold text-primary fs-6 fs-md-5 mb-0" id="evaluateFacultyModalLabel">
            <i class="fas fa-award me-2"></i>Peer Evaluation Rating Form
        </h5>
        <small class="text-body-secondary d-block">Evaluating: <strong class="text-body" id="modalFacultyName">Prof. Emerson Gelera</strong> (<span id="modalFacultyDept">BSIT</span>)</small>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

    <div class="modal-body p-3 p-sm-4" style="max-height: 60vh; overflow-y: scroll;">
        <div class="alert alert-info border-info-subtle bg-info-subtle text-info-emphasis d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-shield-alt fs-5 flex-shrink-0"></i>
            <div class="small">
                Your rating is <strong>completely anonymous</strong> and will be aggregated into the department's peer evaluation report.
            </div>
        </div>

    <div class="table-responsive border rounded border-secondary-subtle mb-3">
        <table class="table table-bordered align-middle mb-0" style="min-width: 540px;">
            <thead class="bg-body-tertiary text-body-secondary small text-uppercase">
                <tr>
                    <th class="sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 45%;">Evaluation Criteria</th>
                    <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">1<br><small class="text-lowercase font-monospace fw-normal">Poor</small></th>
                    <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">2<br><small class="text-lowercase font-monospace fw-normal">Fair</small></th>
                    <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">3<br><small class="text-lowercase font-monospace fw-normal">Good</small></th>
                    <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">4<br><small class="text-lowercase font-monospace fw-normal">V.Good</small></th>
                    <th class="text-center sticky-top bg-body-tertiary text-body border-bottom border-secondary-subtle z-1" style="width: 11%;">5<br><small class="text-lowercase font-monospace fw-normal">Excel</small></th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-body-tertiary">
                    <td colspan="6" class="fw-bold text-uppercase small text-primary py-2 ps-3 border-bottom border-secondary-subtle">
                        <i class="fas fa-user-shield me-1"></i> Section A: Professional Ethics & Workplace Conduct
                    </td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">1. Professional Integrity & Code of Ethics</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Demonstrates honesty, fairness, and strict adherence to institutional policies and academic integrity.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_1" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">2. Punctuality & Attendance</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Consistently attends departmental meetings, submits academic reports, and completes duties on time.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_2" value="5"></td>
                </tr>

                <!-- SECTION B -->
                <tr class="bg-body-tertiary">
                    <td colspan="6" class="fw-bold text-uppercase small text-primary py-2 ps-3 border-bottom border-secondary-subtle">
                        <i class="fas fa-chalkboard-teacher me-1"></i> Section B: Teaching & Instructional Competence
                    </td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">3. Subject Mastery & Pedagogy</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Demonstrates deep domain knowledge and effective, updated teaching methodologies in class.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_3" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">4. Knowledge Sharing & Resource Delivery</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Willingly shares instructional materials, syllabi updates, and teaching strategies with colleagues.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_4" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">5. Fair Student Assessment & Grading</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Applies transparent, fair, and objective grading standards aligned with department benchmarks.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_5" value="5"></td>
                </tr>

                <!-- SECTION C -->
                <tr class="bg-body-tertiary">
                    <td colspan="6" class="fw-bold text-uppercase small text-primary py-2 ps-3 border-bottom border-secondary-subtle">
                        <i class="fas fa-users-cog me-1"></i> Section C: Departmental Collaboration & Interpersonal Relations
                    </td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">6. Active Committee & Department Participation</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Actively contributes ideas and labor to department initiatives, curriculum updates, and events.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_6" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">7. Interpersonal Communication & Collegiality</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Communicates respectfully with colleagues, listens actively, and maintains a constructive attitude.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_7" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">8. Peer Support & Mentorship</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Offers help and constructive guidance to fellow faculty members when needed.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_8" value="5"></td>
                </tr>

                <!-- SECTION D -->
                <tr class="bg-body-tertiary">
                    <td colspan="6" class="fw-bold text-uppercase small text-primary py-2 ps-3 border-bottom border-secondary-subtle">
                        <i class="fas fa-graduation-cap me-1"></i> Section D: Professional Growth & Leadership
                    </td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">9. Professional Development & Innovation</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Engages in research, seminars, or continuous learning to enhance teaching effectiveness.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_9" value="5"></td>
                </tr>
                <tr>
                    <td class="p-2 p-sm-3">
                        <div class="fw-bold text-body small fs-sm-6">10. Problem Solving & Adaptability</div>
                        <small class="text-body-secondary d-block lh-sm fs-7">Handles department challenges professionally and adapts well to institutional changes.</small>
                    </td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="1" required></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="2"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="3"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="4"></td>
                    <td class="text-center align-middle"><input class="form-check-input" type="radio" name="crit_10" value="5"></td>
                </tr>

            </tbody>
        </table>
    </div>

        <!-- Qualitative Feedback -->
        <div class="mt-3">
            <label for="evalRemarks" class="form-label fw-bold text-body small">Constructive Comments / Feedback <small class="text-body-secondary fw-normal">(Optional)</small></label>
            <textarea class="form-control bg-body text-body border-secondary-subtle" id="evalRemarks" name="remarks" rows="3" placeholder="Provide constructive feedback, commendations, or specific areas of excellence..."></textarea>
        </div>
    </div>

        <!-- Modal Footer (Pinned at Bottom) -->
        <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle">
            <button type="button" class="btn btn-outline-secondary border-secondary-subtle" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-paper-plane me-1"></i> Submit Evaluation
            </button>
        </div>
    </form>
    </div>
    </div>
</div>


<script>
// Filter Peers function
function filterPeers() {
    const searchVal = document.getElementById('peerSearchInput').value.toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.peer-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search').toLowerCase();
        const rowStatus = row.getAttribute('data-status');

        const matchesSearch = (searchVal === '' || rowSearch.includes(searchVal));
        const matchesStatus = (statusVal === 'ALL' || rowStatus === statusVal);

        if (matchesSearch && matchesStatus) {
            row.classList.remove('d-none');
            visibleCount++;
        } else {
            row.classList.add('d-none');
        }
    });

    const noMsg = document.getElementById('noPeersMessage');
    if (visibleCount === 0) {
        noMsg.classList.remove('d-none');
    } else {
        noMsg.classList.add('d-none');
    }
}

// Open Evaluation Modal & set data
function openEvaluationModal(id, name, dept) {
    document.getElementById('modalFacultyId').value = id;
    document.getElementById('modalFacultyName').textContent = name;
    document.getElementById('modalFacultyDept').textContent = dept;

    document.getElementById('peerEvaluationForm').reset();

    const modal = new bootstrap.Modal(document.getElementById('evaluateFacultyModal'));
    modal.show();
}

//---------- PAGINATION ---------- 
let currentPeerPage = 1;
const peersPerPage = 5;

function renderPeerTable() {
    const searchInput = document.getElementById('peerSearchInput').value.toLowerCase().trim();
    const rows = Array.from(document.querySelectorAll('#peerTable .peer-row'));
    const noResultsMsg = document.getElementById('noPeersMessage');
    const paginationNav = document.getElementById('peerPagination');
    const paginationInfo = document.getElementById('peerPaginationInfo');

    const filteredRows = rows.filter(row => {
        const searchData = row.getAttribute('data-search') || '';
        const textContent = row.textContent.toLowerCase();
        return searchData.toLowerCase().includes(searchInput) || textContent.includes(searchInput);
    });

    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / peersPerPage) || 1;

    if (currentPeerPage > totalPages) {
        currentPeerPage = totalPages;
    }

    rows.forEach(row => row.classList.add('d-none'));

    if (totalItems > 0) {
        noResultsMsg.classList.add('d-none');
        const start = (currentPeerPage - 1) * peersPerPage;
        const end = start + peersPerPage;

        filteredRows.slice(start, end).forEach(row => row.classList.remove('d-none'));

        const displayStart = start + 1;
        const displayEnd = Math.min(end, totalItems);
        paginationInfo.textContent = `Showing ${displayStart} to ${displayEnd} of ${totalItems} peers`;
    } else {
        noResultsMsg.classList.remove('d-none');
        paginationInfo.textContent = 'Showing 0 entries';
    }
    renderPaginationControls(totalPages, paginationNav);
}

function renderPaginationControls(totalPages, container) {
    container.innerHTML = '';

    if (totalPages <= 1) return;
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPeerPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPeerPage > 1) {
            currentPeerPage--;
            renderPeerTable();
        }
    });
    container.appendChild(prevLi);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPeerPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click', (e) => {
            e.preventDefault();
            currentPeerPage = i;
            renderPeerTable();
        });
        container.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPeerPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPeerPage < totalPages) {
            currentPeerPage++;
            renderPeerTable();
        }
    });
    container.appendChild(nextLi);
}

function filterPeers() {
    currentPeerPage = 1;
    renderPeerTable();
}
document.addEventListener('DOMContentLoaded', renderPeerTable);
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>