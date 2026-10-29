<?php
/**
 * Teaching Load (legacy entry point)
 * CHANGED: this file no longer contains the workflow. It only sends each signed-in role
 * to its own page, so old links and bookmarks keep working.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';

requireAuth();

$roleKey = strtolower(trim((string) getCurrentUserRoleKey()));
$base = BASE_URL . '/modules/faculty/views';
$target = null;
if (in_array($roleKey, ['program_chair', 'department_head', 'dept_head'], true)) {
    $target = $base . '/department-head/teaching-load-assignment.php';
} elseif (in_array($roleKey, ['dean', 'faculty_admin'], true)) {
    $target = $base . '/dean/teaching-load-approval.php';
} elseif (in_array($roleKey, ['registrar', 'registrar_clearance'], true)) {
    $target = $base . '/registrar/teaching-load-validation.php';
} elseif ($roleKey === 'faculty') {
    $target = $base . '/faculty/teaching-load-acknowledgement.php';
}

if ($target === null) {
    http_response_code(403);
    exit('You do not have access to the teaching-load workflow.');
}

$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
header('Location: ' . $target . ($query !== '' ? '?' . $query : ''));
exit;
