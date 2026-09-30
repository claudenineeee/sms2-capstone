// modules/faculty/api/faculty-list.php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../models/FacultyModel.php';

header('Content-Type: application/json');

requireAuth(); // reuses your existing session-based login check — no separate token system needed

if (!userCanAccessModule('faculty')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$data = getScopedFacultyList();

echo json_encode([
    'ok'   => true,
    'role' => getCurrentUserRoleKey(),
    'count' => count($data),
    'data' => $data,
]);