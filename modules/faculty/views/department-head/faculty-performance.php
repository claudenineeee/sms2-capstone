<?php
/**
 * Faculty Performance
 * Purpose: Monitor and analyze faculty performance evaluations
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Faculty Performance';
$activeModule = 'faculty';
$activePage   = 'faculty-performance';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Profile', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1><i class="fas fa-chart-line text-purple me-2"></i>Faculty Performance</h1>
        <p class="text-muted mb-0">Monitor and analyze faculty performance evaluations</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-info" onclick="compareFaculty()"><i class="fas fa-balance-scale me-1"></i>Compare Faculty</button>
        <button class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i>Export Data</button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(0, 208, 132, 0.15); color: #00d084;">
                        <i class="fas fa-trophy fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Top Performers</h6>
                        <h3 class="mb-0 fw-bold text-white">5</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3); font-size: 0.7rem;">≥ 4.5 Rating</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(47, 120, 255, 0.15); color: #2f78ff;">
                        <i class="fas fa-chart-line fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Dept Average</h6>
                        <h3 class="mb-0 fw-bold text-white">4.3</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3); font-size: 0.7rem;">↑ 0.2</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded me-3" style="background: rgba(0, 200, 255, 0.15); color: #00c8ff;">
                        <i class="fas fa-users fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 small text-uppercase fw-bold" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">Faculty Evaluated</h6>
                        <h3 class="mb-0 fw-bold text-white">18</h3>
                    </div>
                </div>
                <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(0, 200, 255, 0.15); color: #00c8ff; border: 1px solid rgba(0, 200, 255, 0.3); font-size: 0.7rem;">100% Complete</span>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers Card -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
    <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">
            <i class="fas fa-trophy text-warning me-2"></i>Top Performers <span class="small" style="color: #94a3b8;">(Rating ≥ 4.5)</span>
        </h6>
    </div>
    <div class="card-body p-3">
        <!-- Added horizontal scroll container for large datasets -->
        <div class="d-flex flex-nowrap gap-3 overflow-auto pb-2" style="scrollbar-width: thin;">
            
            <!-- Repeatable Item Block -->
            <div class="flex-shrink-0" style="width: 200px;">
                <div class="p-3 text-center rounded h-100" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <i class="fas fa-user-circle fa-2x mb-2" style="color: #00c8ff;"></i>
                    <h6 class="mb-1 text-white text-truncate small font-weight-bold" title="Dr. Maria Santos">Dr. Maria Santos</h6>
                    <h4 class="fw-bold mb-0" style="color: #00d084;">4.6 <i class="fas fa-arrow-up text-success ms-1 fs-6"></i></h4>
                </div>
            </div>

            <div class="flex-shrink-0" style="width: 200px;">
                <div class="p-3 text-center rounded h-100" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <i class="fas fa-user-circle fa-2x mb-2" style="color: #00c8ff;"></i>
                    <h6 class="mb-1 text-white text-truncate small font-weight-bold" title="Prof. Luis Tan">Prof. Luis Tan</h6>
                    <h4 class="fw-bold mb-0" style="color: #00d084;">4.5 <i class="fas fa-arrow-up text-success ms-1 fs-6"></i></h4>
                </div>
            </div>

            <!-- More items will seamlessly scroll to the right -->

        </div>
    </div>
</div>


<!-- Search and Filters -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-uppercase" style="color: #94a3b8; font-size: 0.75rem;">Faculty Name</label>
                <input type="text" class="form-control text-white border-secondary border-opacity-25" style="background-color: rgba(255,255,255,0.05);" placeholder="Search faculty...">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-uppercase" style="color: #94a3b8; font-size: 0.75rem;">Evaluation Period</label>
                <select class="form-select text-white border-secondary border-opacity-25" style="background-color: #0b1329;">
                    <option>2nd Semester 2025</option>
                    <option>1st Semester 2025</option>
                    <option>2nd Semester 2024</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-uppercase" style="color: #94a3b8; font-size: 0.75rem;">Rating Range</label>
                <select class="form-select text-white border-secondary border-opacity-25" style="background-color: #0b1329;">
                    <option value="">All</option>
                    <option>4.5 - 5.0 (Excellent)</option>
                    <option>4.0 - 4.4 (Very Good)</option>
                    <option>3.5 - 3.9 (Good)</option>
                    <option>Below 3.5 (Needs Improvement)</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-uppercase" style="color: #94a3b8; font-size: 0.75rem;">Department</label>
                <select class="form-select text-white border-secondary border-opacity-25" style="background-color: #0b1329;">
                    <option>All Departments</option>
                    <option selected>College of Computer Studies</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-primary flex-grow-1" style="background-color: #2f78ff; border: none;"><i class="fas fa-search me-1"></i>Search</button>
                    <button class="btn btn-outline-secondary" style="color: #94a3b8; border-color: #1b2745;"><i class="fas fa-redo me-1"></i>Reset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Table -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0b1329; border: 1px solid #1b2745 !important; border-radius: 12px;">
    <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 text-white"><i class="fas fa-list text-primary me-2"></i>Faculty Performance List (18)</h6>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm text-white border-secondary border-opacity-25 w-auto" style="background-color: #0b1329;">
                <option>10 per page</option>
                <option>25 per page</option>
                <option>50 per page</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-white" style="border-color: #1b2745;">
                <thead>
                    <tr class="text-uppercase small" style="background-color: rgba(255, 255, 255, 0.02); color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.5px;">
                        <th class="ps-3 py-3">Faculty</th>
                        <th class="py-3">Period</th>
                        <th class="py-3">Overall</th>
                        <th class="py-3">Teaching</th>
                        <th class="py-3">Research</th>
                        <th class="py-3">Service</th>
                        <th class="py-3">Trend</th>
                        <th class="pe-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ps-3"><strong>Dr. Maria Santos</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3);">4.6</span></td>
                        <td>4.7</td>
                        <td>4.5</td>
                        <td>4.6</td>
                        <td><i class="fas fa-arrow-up" style="color: #00d084;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Dr. Maria Santos')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Dr. Maria Santos')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Prof. Luis Tan</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(0, 208, 132, 0.15); color: #00d084; border: 1px solid rgba(0, 208, 132, 0.3);">4.5</span></td>
                        <td>4.6</td>
                        <td>4.4</td>
                        <td>4.5</td>
                        <td><i class="fas fa-arrow-up" style="color: #00d084;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Prof. Luis Tan')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Prof. Luis Tan')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Prof. Katherine Lim</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(47, 120, 255, 0.15); color: #2f78ff; border: 1px solid rgba(47, 120, 255, 0.3);">4.4</span></td>
                        <td>4.5</td>
                        <td>4.3</td>
                        <td>4.4</td>
                        <td><i class="fas fa-arrow-up" style="color: #00d084;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Prof. Katherine Lim')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Prof. Katherine Lim')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Prof. John Aquino</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(47, 120, 255, 0.15); color: #2f78ff; border: 1px solid rgba(47, 120, 255, 0.3);">4.4</span></td>
                        <td>4.5</td>
                        <td>4.2</td>
                        <td>4.5</td>
                        <td><i class="fas fa-minus text-secondary"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Prof. John Aquino')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Prof. John Aquino')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Dr. Ana Reyes</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(47, 120, 255, 0.15); color: #2f78ff; border: 1px solid rgba(47, 120, 255, 0.3);">4.3</span></td>
                        <td>4.4</td>
                        <td>4.1</td>
                        <td>4.4</td>
                        <td><i class="fas fa-arrow-up" style="color: #00d084;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Dr. Ana Reyes')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Dr. Ana Reyes')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Prof. Sarah Martinez</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(255, 152, 0, 0.15); color: #ff9800; border: 1px solid rgba(255, 152, 0, 0.3);">3.9</span></td>
                        <td>4.0</td>
                        <td>3.5</td>
                        <td>4.2</td>
                        <td><i class="fas fa-arrow-down" style="color: #ff5263;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Prof. Sarah Martinez')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Prof. Sarah Martinez')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3"><strong>Prof. Roberto Villanueva</strong></td>
                        <td class="small" style="color: #94a3b8;">2nd Sem 2025</td>
                        <td><span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(255, 152, 0, 0.15); color: #ff9800; border: 1px solid rgba(255, 152, 0, 0.3);">3.8</span></td>
                        <td>3.9</td>
                        <td>3.6</td>
                        <td>3.9</td>
                        <td><i class="fas fa-arrow-down" style="color: #ff5263;"></i></td>
                        <td class="pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="View Details" onclick="viewPerformanceDetails('Prof. Roberto Villanueva')"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-light border-secondary border-opacity-25" title="AI Recommendations" onclick="viewAIRecommendations('Prof. Roberto Villanueva')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer border-top border-secondary border-opacity-25 bg-transparent d-flex justify-content-between align-items-center py-3">
        <small style="color: #94a3b8;">Showing 1-7 of 18 faculty</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" style="background-color: #0b1329; border-color: #1b2745; color: #94a3b8;" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" style="background-color: #2f78ff; border-color: #2f78ff;" href="#">1</a></li>
                <li class="page-item"><a class="page-link" style="background-color: #0b1329; border-color: #1b2745; color: #94a3b8;" href="#">2</a></li>
                <li class="page-item"><a class="page-link" style="background-color: #0b1329; border-color: #1b2745; color: #94a3b8;" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Performance Details Modal -->
<div class="modal fade" id="performanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-white" style="background-color: #0b1329; border: 1px solid #1b2745;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title"><i class="fas fa-chart-line text-primary me-2"></i>Performance Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Dr. Maria Santos - 2nd Semester 2025</h6>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="text-center p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                            <h3 class="mb-1" style="color: #00d084;">4.6</h3>
                            <small style="color: #94a3b8;">Overall</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                            <h3 class="mb-1" style="color: #2f78ff;">4.7</h3>
                            <small style="color: #94a3b8;">Teaching</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                            <h3 class="mb-1" style="color: #ff9800;">4.5</h3>
                            <small style="color: #94a3b8;">Research</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                            <h3 class="mb-1" style="color: #00c8ff;">4.6</h3>
                            <small style="color: #94a3b8;">Service</small>
                        </div>
                    </div>
                </div>
                <h6 class="mt-4 mb-3">Evaluation Comments:</h6>
                <div class="p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <p class="mb-0 small" style="color: #94a3b8;">Excellent teaching performance with consistently high student evaluations. Strong research output with 2 published papers this semester. Active in department service activities.</p>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-outline-secondary text-white border-secondary border-opacity-25" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info text-white" onclick="viewAIRecommendations()">View AI Recommendations</button>
            </div>
        </div>
    </div>
</div>

<!-- AI Recommendations Modal -->
<div class="modal fade" id="aiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-white" style="background-color: #0b1329; border: 1px solid #1b2745;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title"><i class="fas fa-robot text-info me-2"></i>AI-Generated Recommendations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 mb-3 rounded d-flex align-items-center" style="background: rgba(0, 200, 255, 0.1); border: 1px solid rgba(0, 200, 255, 0.3); color: #00c8ff;">
                    <i class="fas fa-info-circle me-2 fs-5"></i>
                    <span class="small">These recommendations are generated by OpenAI GPT-4.1 based on performance data.</span>
                </div>
                <h6>For: Dr. Maria Santos</h6>
                
                <div class="p-3 mb-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <h6 class="card-title mb-2" style="color: #00d084;"><i class="fas fa-check-circle me-2"></i>Strengths</h6>
                    <ul class="mb-0 small" style="color: #94a3b8;">
                        <li>Consistently high student evaluation scores (4.7/5.0)</li>
                        <li>Strong research productivity with quality publications</li>
                        <li>Excellent department service involvement</li>
                    </ul>
                </div>
                
                <div class="p-3 mb-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <h6 class="card-title mb-2" style="color: #ff9800;"><i class="fas fa-lightbulb me-2"></i>Areas for Improvement</h6>
                    <ul class="mb-0 small" style="color: #94a3b8;">
                        <li>Consider mentoring junior faculty in research methods</li>
                        <li>Explore interdisciplinary research opportunities</li>
                        <li>Share teaching best practices in department workshops</li>
                    </ul>
                </div>

                <div class="p-3 rounded" style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid #1b2745;">
                    <h6 class="card-title mb-2" style="color: #2f78ff;"><i class="fas fa-star me-2"></i>Recommended Actions</h6>
                    <ul class="mb-0 small" style="color: #94a3b8;">
                        <li>Nominate for outstanding faculty award</li>
                        <li>Consider for lead researcher role in upcoming projects</li>
                        <li>Invite to present at faculty development sessions</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-outline-secondary text-white border-secondary border-opacity-25" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" style="background-color: #2f78ff; border: none;">Download Report</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPerformanceDetails(faculty) {
    const modal = new bootstrap.Modal(document.getElementById('performanceModal'));
    modal.show();
}
function viewAIRecommendations(faculty) {
    const modal = new bootstrap.Modal(document.getElementById('aiModal'));
    modal.show();
}
function compareFaculty() {
    alert('Select faculty to compare...');
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
