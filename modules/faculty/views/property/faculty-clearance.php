<?php
/** Property Clearance – Faculty clearance tracking portal. */
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
if (!in_array(getCurrentUserRoleKey(), ['property_custodian_office', 'property', 'faculty_admin'], true)) {
    http_response_code(403); exit('Access denied.');
}
$officeLabel  = 'Property Custodian Office';
$officeIcon   = 'fa-boxes-stacked';
$officeColor  = 'secondary';
$targetRequirementName = 'Property Clearance';
$pageTitle    = 'Faculty Clearance Portal – Property';
$activeModule = 'faculty';
$activePage   = 'faculty-clearance';
$breadcrumbs  = [
    ['label' => 'Property Office', 'url' => null],
    ['label' => 'Faculty Clearance', 'url' => null],
];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<?php include __DIR__ . '/../_shared/clearance-portal.php'; ?>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
