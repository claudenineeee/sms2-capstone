<?php
/**
 * HR Clearance Dashboard
 * Purpose: Overview of HR clearance status and key metrics
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
requireAuth();

$pageTitle    = 'HR Clearance Dashboard';
$activeModule = 'faculty'; // Keep this as 'faculty' or your main module key so the sidebar condition triggers correctly
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'HR Clearance', 'url' => BASE_URL . '/modules/faculty/users/hr/dashboard.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- ADD YOUR CONTENT HERE -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white p-4">
                <h2>Welcome, HR Clearance User</h2>
                <p>This is your HR clearance dashboard and monitoring panel.</p>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../../../../includes/layout-end.php'; 
?>