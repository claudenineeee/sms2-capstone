<?php
/** Finance Clearance – Faculty clearance tracking portal. */
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
if (!in_array(getCurrentUserRoleKey(), ['finance_office', 'finance', 'faculty_admin'], true)) {
    http_response_code(403); exit('Access denied.');
}
$officeLabel  = 'Finance Clearance Office';
$officeIcon   = 'fa-coins';
$officeColor  = 'success';
$targetRequirementName = 'Financial Clearance';
$pageTitle    = 'Faculty Clearance Portal – Finance';
$activeModule = 'faculty';
$activePage   = 'faculty-clearance';
$breadcrumbs  = [
    ['label' => 'Finance Office', 'url' => null],
    ['label' => 'Faculty Clearance', 'url' => null],
];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<?php include __DIR__ . '/../_shared/clearance-portal.php'; ?>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
