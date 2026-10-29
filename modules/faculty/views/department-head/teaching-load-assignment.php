<?php
/**
 * Teaching Load Assignment
 * CHANGED: small entry page for this role. All logic lives in includes/teaching-load-controller.php,
 * all markup lives in views/_shared/teaching-load/.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../../config/config.php';

// CHANGED: tell the shared controller which page/roles this is BEFORE including it
$teachingLoadPageUrl = BASE_URL . '/modules/faculty/views/department-head/teaching-load-assignment.php';
$teachingLoadPageRoles = ['program_chair', 'department_head', 'dept_head'];
require_once __DIR__ . '/../../includes/teaching-load-controller.php';

$teachingLoadHeading = 'Teaching Load Assignment';
$teachingLoadSubtitle = 'Create teaching-load assignments and submit them to the Dean.';
$pageTitle = 'Teaching Load Assignment';
$activeModule = 'faculty';
$activePage = 'teaching-load-workflow';
$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Teaching Load Assignment', 'url' => null],
];
require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// CHANGED: partials now live in views/_shared/teaching-load/ (was views/partials/teaching-load/)
require __DIR__ . '/../_shared/teaching-load/styles.php';
renderBreadcrumbs($breadcrumbs);
require __DIR__ . '/../_shared/teaching-load/header.php';
require __DIR__ . '/../_shared/teaching-load/assignment-form.php';
require __DIR__ . '/../_shared/teaching-load/request-queue.php';

require_once __DIR__ . '/../../../../includes/layout-end.php';
