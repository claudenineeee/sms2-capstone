<?php
/**
 * SMS 2 - Faculty Admin Dashboard
 * Pure Bootstrap — relies on theme.css for light/dark theming.
 * Charts use gradient fills + theme-aware palettes.
 * Leave trend supports 1W / 1M / 6M / 1Y range switching.
 * Responsive: KPI cards go full-width on phones, charts adapt.
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$pageTitle    = 'Faculty Admin Dashboard';
$activeModule = 'faculty';
$activePage   = 'teaching-load-history';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>