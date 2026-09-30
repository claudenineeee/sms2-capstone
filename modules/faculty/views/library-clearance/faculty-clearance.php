<?php
/** Library Clearance – Faculty clearance tracking portal. */
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
if (!in_array(getCurrentUserRoleKey(), ['library_clearance', 'library', 'faculty_admin'], true)) {
    http_response_code(403); exit('Access denied.');
}
$officeLabel  = 'Library Clearance Office';
$officeIcon   = 'fa-book-open';
$officeColor  = 'warning';
$targetRequirementName = 'Library Clearance';
$pageTitle    = 'Faculty Clearance Portal – Library';
$activeModule = 'faculty';
$activePage   = 'faculty-clearance';
$breadcrumbs  = [
    ['label' => 'Library Office', 'url' => null],
    ['label' => 'Faculty Clearance', 'url' => null],
];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<?php include __DIR__ . '/../_shared/clearance-portal.php'; ?>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
