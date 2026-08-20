<?php
// controllers/FacultyPerformanceController.php

require_once __DIR__ . '/../models/FacultyModel.php';

class FacultyPerformanceController {
    private $model;

    public function __construct($pdo = null) {
        $this->model = new FacultyModel($pdo);
    }

    public function handleRequest() {
        // Session Retrieval
        $userDeptId     = $_SESSION['designated_department'] ?? $_SESSION['department_id'] ?? $_SESSION['user_department_id'] ?? '1';
        $headDepartment = $_SESSION['department_name'] ?? $_SESSION['user_department'] ?? 'Your Department';

        // Filters
        $searchName   = trim($_GET['search_name'] ?? '');
        $searchPeriod = trim($_GET['evaluation_period'] ?? '');
        $ratingRange  = trim($_GET['rating_range'] ?? '');

        // Pagination
        $limit = 10;
        $page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) { $page = 1; }

        // Fetch Performance Data
        $summary       = $this->model->getPerformanceMetrics($userDeptId);
        $topPerformers = $this->model->getTopPerformers($userDeptId);
        
        // Calculate pagination based on total department members or evaluated count
        $totalRows  = (int)($summary['total_evaluated'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRows / $limit));
        if ($page > $totalPages) { $page = $totalPages; }
        $offset     = ($page - 1) * $limit;

        $facultyList = $this->model->getPerformanceList($userDeptId, $searchName, $ratingRange, $limit, $offset);

        // Check AJAX Request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            $this->renderAjaxResponse($facultyList, $page, $totalPages);
            exit;
        }

        return [
            'headDepartment' => $headDepartment,
            'searchName'     => $searchName,
            'searchPeriod'   => $searchPeriod,
            'ratingRange'    => $ratingRange,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'facultyList'    => $facultyList,
            'summary'        => $summary,
            'topPerformers'  => $topPerformers
        ];
    }

    private function renderAjaxResponse(array $facultyPerformanceList, int $page, int $totalPages) {
        ob_start();
        ?>
        <tbody id="tableBody">
            <?php if (!empty($facultyPerformanceList)): ?>
                <?php foreach ($facultyPerformanceList as $row): ?>
                    <?php 
                        $current   = $row['overall'] ?? null;
                        $previous  = $row['previous_score'] ?? null;
                        $trendIcon = '<span class="text-muted">—</span>';
                        if ($current !== null && $previous !== null) {
                            if ($current > $previous) { $trendIcon = '<i class="fas fa-arrow-up text-success"></i>'; }
                            elseif ($current < $previous) { $trendIcon = '<i class="fas fa-arrow-down text-danger"></i>'; }
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                        <td>
                            <?php if (!is_null($row['overall'])): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <?= number_format((float)$row['overall'], 1) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?= isset($row['teaching_score']) && !is_null($row['teaching_score']) ? number_format((float)$row['teaching_score'], 1) : '—' ?></td>
                        <td><?= $trendIcon ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" onclick="viewPerformanceDetails('<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>', <?= $row['faculty_profile_id'] ?>)"><i class="fas fa-eye text-primary"></i></button>
                                <button class="btn btn-outline-secondary" onclick="openAiRecommendations('<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>')"><i class="fas fa-robot text-info"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No faculty members found matching your search in this department.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <?php
        $tbodyHtml = ob_get_clean();

        ob_start();
        if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-end mt-3" id="paginationNav">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="#" onclick="fetchPage(<?= $page - 1 ?>); return false;">Previous</a></li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>"><a class="page-link" href="#" onclick="fetchPage(<?= $i ?>); return false;"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>"><a class="page-link" href="#" onclick="fetchPage(<?= $page + 1 ?>); return false;">Next</a></li>
                </ul>
            </nav>
        <?php endif;
        $paginationHtml = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode(['tbody' => $tbodyHtml, 'pagination' => $paginationHtml]);
    }
}