<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
require_once __DIR__ . '/clearance.php';

header('Content-Type: application/json; charset=utf-8');

function clearanceApiResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function clearanceApiDb(): PDO
{
    $db = facultyDb();
    if (!$db) {
        clearanceApiResponse(['ok' => false, 'error' => 'Faculty database is unavailable.'], 503);
    }
    return $db;
}

try {
    $db = clearanceApiDb();
    $userId = (int) getCurrentUserId();
    $profile = facultyClearanceProfile($db, $userId);
    $role = getCurrentUserRoleKey();
    $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'summary');

    // Helper flag & assigned departments check
    $isDeptHead = in_array($role, ['department_head', 'dept_head'], true);
    $assignedDepartments = facultyClearanceAssignedDepartments($profile ?: [], $db);

    if ($action === 'file') {
        $itemId = (int) ($_GET['item_id'] ?? 0);
        $filePath = trim((string) ($_GET['path'] ?? ''));
        $targetRelPath = '';
        if ($itemId > 0) {
            $stmt = $db->prepare('SELECT ci.file_path, ci.original_name, cr.faculty_id, fp.designated_department 
                                  FROM clearance_items ci 
                                  JOIN clearance_requests cr ON cr.clearance_id = ci.clearance_id 
                                  JOIN faculty f ON f.faculty_id = cr.faculty_id
                                  JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                                  WHERE ci.clearance_item_id = ? LIMIT 1');
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if ($item && !empty($item['file_path'])) {
                // SECURITY CHECK: Restrict file download for Department Heads
                if ($isDeptHead && !empty($assignedDepartments) && !in_array($item['designated_department'], $assignedDepartments, true)) {
                    http_response_code(403);
                    exit('Access denied.');
                }
                $targetRelPath = (string) $item['file_path'];
            }
        }
        if ($targetRelPath === '' && $filePath !== '') {
            $targetRelPath = $filePath;
        }
        if ($targetRelPath === '') {
            http_response_code(404);
            exit('File not found.');
        }
        $profileFacultyId = $profile ? facultyClearanceFacultyId($db, (int) $profile['id']) : null;
        $allowed = in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true) || ($profileFacultyId !== null);
        if (!$allowed) {
            http_response_code(403);
            exit('Access denied.');
        }
        $base = realpath(ROOT_PATH . '/storage/uploads/faculty-clearance');
        $path = realpath(ROOT_PATH . '/storage/uploads/' . ltrim($targetRelPath, '/'));
        if (!$base || !$path || strncmp($path, $base, strlen($base)) !== 0 || !is_file($path)) {
            http_response_code(404);
            exit('File not found.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            http_response_code(400);
            exit('Only PDF files are allowed for clearance review.');
        }

        $mime = mime_content_type($path) ?: 'application/pdf';
        if ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
            http_response_code(400);
            exit('Only PDF files are allowed for clearance review.');
        }

        $downloadName = !empty($item['original_name']) ? $item['original_name'] : basename($path);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    if (!$profile && !in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin'], true)) {
        clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
    }

    $offices = facultyClearanceOffices($db);
    $term = facultyClearanceTerm($db);

    if ($action === 'summary') {
        if ($role === 'faculty' || $role === 'faculty_professor' || $role === '') {
            $request = facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']);
            clearanceApiResponse(['ok' => true, 'profile' => $profile, 'term' => $term, 'offices' => $offices, 'clearance' => facultyClearanceJson($request)]);
        }

        $sql = 'SELECT fp.*, cr.clearance_id, cr.overall_status, cr.submitted_at, cr.updated_at FROM faculty_profiles fp LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ? WHERE fp.position = ? AND fp.profile_status = ?';
        $params = [(int) $term['term_id'], 'Faculty Professor', 'Active'];

        // Filter by assigned departments if restricted (HR and Faculty Admin see all)
        if (!empty($assignedDepartments) && !in_array($role, ['hr', 'faculty_admin'], true)) {
            $placeholders = implode(',', array_fill(0, count($assignedDepartments), '?'));
            $sql .= " AND fp.designated_department IN ($placeholders)";
            $params = array_merge($params, $assignedDepartments);
        }

        $sql .= ' ORDER BY cr.updated_at DESC, fp.last_name, fp.first_name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['clearance'] = facultyClearanceJson($row['clearance_id'] ? facultyClearanceRequest($db, (int) $row['id'], (int) $term['term_id']) : null);
            $row['name'] = facultyClearanceDisplayName($row);
            $date = $row['contractual_end'] ?? null;
            $row['days_remaining'] = $date && $date !== '0000-00-00' ? (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400) : null;
        }
        unset($row);
        $pending = $actionRequired = $archived = 0;
        foreach ($rows as $row) {
            $status = $row['clearance']['status'];
            $pending += in_array($status, ['Pending Verification', 'Under Review'], true) ? 1 : 0;
            $actionRequired += $status === 'Action Required' ? 1 : 0;
            $archived += $row['overall_status'] === 'Cleared' ? 1 : 0;
        }
        clearanceApiResponse(['ok' => true, 'profile' => $profile, 'term' => $term, 'offices' => $offices, 'rows' => $rows, 'metrics' => ['pending' => $pending, 'action_required' => $actionRequired, 'archived' => $archived]]);
    }

    if ($action === 'review') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Review access is restricted.'], 403);
        }
        $facultyId = (int) ($_GET['faculty_id'] ?? 0);
        $target = $db->prepare('SELECT * FROM faculty_profiles WHERE id = ? LIMIT 1');
        $target->execute([$facultyId]);
        $targetProfile = $target->fetch();

        if (!$targetProfile) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty profile not found.'], 404);
        }

        // SECURITY CHECK: Department restriction for Department Head
        if ($isDeptHead && !empty($assignedDepartments) && !in_array($targetProfile['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Faculty member is not in your department.'], 403);
        }

        $request = facultyClearanceRequest($db, $facultyId, (int) $term['term_id']);
        clearanceApiResponse(['ok' => true, 'profile' => $targetProfile, 'term' => $term, 'clearance' => facultyClearanceJson($request)]);
    }

    if ($action === 'archives') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Archive access is restricted.'], 403);
        }

        // Query persistent archives table
        $archSql = 'SELECT * FROM faculty_clearance_archives WHERE 1=1';
        $archParams = [];
        if (!empty($assignedDepartments) && !in_array($role, ['hr', 'faculty_admin'], true)) {
            $placeholders = implode(',', array_fill(0, count($assignedDepartments), '?'));
            $archSql .= " AND designated_department IN ($placeholders)";
            $archParams = array_merge($archParams, $assignedDepartments);
        }
        $archSql .= ' ORDER BY completed_at DESC, archive_id DESC';
        $archStmt = $db->prepare($archSql);
        $archStmt->execute($archParams);
        $archRows = $archStmt->fetchAll();

        $records = [];
        $seenClearanceIds = [];

        foreach ($archRows as $a) {
            $items = !empty($a['items_json']) ? json_decode((string) $a['items_json'], true) : [];
            if (!is_array($items)) {
                $items = [];
            }
            $date = $a['contractual_end'] ?? null;
            $daysRemaining = $date && $date !== '0000-00-00' ? (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400) : null;

            $seenClearanceIds[(int) $a['clearance_id']] = true;
            $records[] = [
                'archive_id' => (int) $a['archive_id'],
                'clearance_id' => (int) $a['clearance_id'],
                'faculty_record_id' => (int) $a['faculty_id'],
                'term_id' => (int) $a['term_id'],
                'profile_id' => !empty($a['profile_id']) ? (int) $a['profile_id'] : null,
                'faculty_no' => $a['faculty_no'],
                'name' => facultyClearanceDisplayName($a),
                'first_name' => $a['first_name'],
                'middle_name' => $a['middle_name'],
                'last_name' => $a['last_name'],
                'suffix' => $a['suffix'],
                'designated_department' => $a['designated_department'],
                'position' => $a['position'],
                'academic_rank' => $a['academic_rank'],
                'tier' => $a['tier'],
                'employment_status' => $a['employment_status'] ?: 'Probationary',
                'contractual_end' => $a['contractual_end'],
                'days_remaining' => $daysRemaining,
                'academic_year' => $a['academic_year'],
                'semester' => $a['semester'],
                'intent_type' => $a['intent_type'],
                'overall_status' => $a['overall_status'] ?: 'Cleared',
                'items' => $items,
                'submitted_at' => $a['submitted_at'],
                'updated_at' => $a['completed_at'],
                'completed_at' => $a['completed_at'],
            ];
        }

        // Also check any live clearance_requests that are Cleared and not yet in archives
        $sql = 'SELECT cr.clearance_id, cr.faculty_id AS faculty_record_id, cr.term_id, cr.intent_type, cr.overall_status, cr.submitted_at, cr.updated_at,
                       at.academic_year, at.semester,
                       fp.id AS profile_id, fp.faculty_id AS faculty_no, fp.first_name, fp.middle_name, fp.last_name, fp.suffix,
                       fp.designated_department, fp.position, fp.contractual_end, fp.academic_rank, fp.tier, fp.employment_status
                FROM clearance_requests cr
                JOIN academic_terms at ON at.term_id = cr.term_id
                JOIN faculty f ON f.faculty_id = cr.faculty_id
                JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                WHERE cr.overall_status = "Cleared"';
        $params = [];
        if (!empty($assignedDepartments) && !in_array($role, ['hr', 'faculty_admin'], true)) {
            $placeholders = implode(',', array_fill(0, count($assignedDepartments), '?'));
            $sql .= " AND fp.designated_department IN ($placeholders)";
            $params = array_merge($params, $assignedDepartments);
        }
        $sql .= ' ORDER BY cr.updated_at DESC, cr.clearance_id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $liveRows = $stmt->fetchAll();

        $allowedNames = ['Letter of Intent', 'Updated Resume', 'Personal Evaluation', 'Summary Evaluation'];
        $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));

        foreach ($liveRows as $rec) {
            $cid = (int) $rec['clearance_id'];
            if (isset($seenClearanceIds[$cid])) {
                continue;
            }
            // Auto archive this cleared record
            facultyClearanceArchiveRecord($db, $cid);

            $rec['name'] = facultyClearanceDisplayName($rec);
            $itemsStmt = $db->prepare('SELECT ci.*, co.name AS requirement_name, co.sequence_order 
                                      FROM clearance_items ci 
                                      JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id 
                                      WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ') 
                                      ORDER BY co.sequence_order, co.clearance_office_id');
            $itemsStmt->execute(array_merge([$cid], $allowedNames));
            $items = $itemsStmt->fetchAll();

            $rec['items'] = array_map(static function (array $it): array {
                return [
                    'id' => (int) $it['clearance_item_id'],
                    'name' => $it['requirement_name'],
                    'status' => $it['status'],
                    'file_name' => !empty($it['original_name']) ? $it['original_name'] : ($it['file_path'] ? basename($it['file_path']) : null),
                    'original_name' => $it['original_name'] ?? null,
                    'file_path' => $it['file_path'],
                    'remarks' => $it['remarks'],
                    'cleared_at' => $it['cleared_at'],
                ];
            }, $items);

            $date = $rec['contractual_end'] ?? null;
            $rec['days_remaining'] = $date && $date !== '0000-00-00' ? (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400) : null;
            $records[] = $rec;
        }

        clearanceApiResponse(['ok' => true, 'archives' => $records, 'count' => count($records)]);
    }

    if ($action === 'archive-detail') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Archive access is restricted.'], 403);
        }

        $clearanceId = (int) ($_GET['clearance_id'] ?? 0);
        $archiveId = (int) ($_GET['archive_id'] ?? 0);

        // Check persistent archives first
        $archStmt = $db->prepare('SELECT * FROM faculty_clearance_archives WHERE ' . ($archiveId > 0 ? 'archive_id = ?' : 'clearance_id = ?') . ' ORDER BY archive_id DESC LIMIT 1');
        $archStmt->execute([$archiveId > 0 ? $archiveId : $clearanceId]);
        $a = $archStmt->fetch();

        if ($a) {
            // SECURITY CHECK: Department isolation for Department Head
            if ($isDeptHead && !empty($assignedDepartments) && !in_array($a['designated_department'], $assignedDepartments, true)) {
                clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Record belongs to another department.'], 403);
            }

            $items = !empty($a['items_json']) ? json_decode((string) $a['items_json'], true) : [];
            if (!is_array($items)) {
                $items = [];
            }
            $date = $a['contractual_end'] ?? null;
            $daysRemaining = $date && $date !== '0000-00-00' ? (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400) : null;

            $rec = [
                'archive_id' => (int) $a['archive_id'],
                'clearance_id' => (int) $a['clearance_id'],
                'faculty_record_id' => (int) $a['faculty_id'],
                'term_id' => (int) $a['term_id'],
                'profile_id' => !empty($a['profile_id']) ? (int) $a['profile_id'] : null,
                'faculty_no' => $a['faculty_no'],
                'name' => facultyClearanceDisplayName($a),
                'first_name' => $a['first_name'],
                'middle_name' => $a['middle_name'],
                'last_name' => $a['last_name'],
                'suffix' => $a['suffix'],
                'email' => $a['email'],
                'phone' => $a['phone'],
                'designated_department' => $a['designated_department'],
                'position' => $a['position'],
                'academic_rank' => $a['academic_rank'],
                'tier' => $a['tier'],
                'employment_status' => $a['employment_status'] ?: 'Probationary',
                'contractual_end' => $a['contractual_end'],
                'days_remaining' => $daysRemaining,
                'academic_year' => $a['academic_year'],
                'semester' => $a['semester'],
                'intent_type' => $a['intent_type'],
                'overall_status' => $a['overall_status'] ?: 'Cleared',
                'items' => $items,
                'submitted_at' => $a['submitted_at'],
                'updated_at' => $a['completed_at'],
                'completed_at' => $a['completed_at'],
            ];

            clearanceApiResponse(['ok' => true, 'record' => $rec]);
        }

        // Fallback to clearance_requests if not found in archives
        $sql = 'SELECT cr.clearance_id, cr.faculty_id AS faculty_record_id, cr.term_id, cr.intent_type, cr.overall_status, cr.submitted_at, cr.updated_at,
                       at.academic_year, at.semester,
                       fp.id AS profile_id, fp.faculty_id AS faculty_no, fp.first_name, fp.middle_name, fp.last_name, fp.suffix,
                       fp.designated_department, fp.position, fp.contractual_end, fp.academic_rank, fp.tier, fp.employment_status, fp.email, fp.phone
                FROM clearance_requests cr
                JOIN academic_terms at ON at.term_id = cr.term_id
                JOIN faculty f ON f.faculty_id = cr.faculty_id
                JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                WHERE cr.clearance_id = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([$clearanceId]);
        $rec = $stmt->fetch();
        if (!$rec) {
            clearanceApiResponse(['ok' => false, 'error' => 'Archive record not found.'], 404);
        }

        // SECURITY CHECK: Department isolation for Department Head
        if ($isDeptHead && !empty($assignedDepartments) && !in_array($rec['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Record belongs to another department.'], 403);
        }

        $rec['name'] = facultyClearanceDisplayName($rec);
        $allowedNames = ['Letter of Intent', 'Updated Resume', 'Personal Evaluation', 'Summary Evaluation'];
        $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
        $itemsStmt = $db->prepare('SELECT ci.*, co.name AS requirement_name, co.sequence_order 
                                  FROM clearance_items ci 
                                  JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id 
                                  WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ') 
                                  ORDER BY co.sequence_order, co.clearance_office_id');
        $itemsStmt->execute(array_merge([(int) $rec['clearance_id']], $allowedNames));
        $items = $itemsStmt->fetchAll();

        $rec['items'] = array_map(static function (array $it): array {
            return [
                'id' => (int) $it['clearance_item_id'],
                'name' => $it['requirement_name'],
                'status' => $it['status'],
                'file_name' => !empty($it['original_name']) ? $it['original_name'] : ($it['file_path'] ? basename($it['file_path']) : null),
                'original_name' => $it['original_name'] ?? null,
                'file_path' => $it['file_path'],
                'remarks' => $it['remarks'],
                'cleared_at' => $it['cleared_at'],
            ];
        }, $items);

        $date = $rec['contractual_end'] ?? null;
        $rec['days_remaining'] = $date && $date !== '0000-00-00' ? (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400) : null;

        clearanceApiResponse(['ok' => true, 'record' => $rec]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        clearanceApiResponse(['ok' => false, 'error' => 'Unsupported request.'], 405);
    }

    if ($action === 'submit') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);
        $officesById = [];
        foreach ($offices as $office) {
            $officesById[(int) $office['clearance_office_id']] = $office;
        }
        $files = facultyClearanceExtractFiles();
        $db->beginTransaction();
        try {
            $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
            $find->execute([$facultyRecordId, (int) $term['term_id']]);
            $clearanceId = (int) ($find->fetchColumn() ?: 0);
            if (!$clearanceId) {
                $insert = $db->prepare("INSERT INTO clearance_requests (faculty_id, term_id, intent_type, overall_status, submitted_at) VALUES (?, ?, ?, 'In Progress', NOW())");
                $insert->execute([$facultyRecordId, (int) $term['term_id'], (string) ($_POST['intent_type'] ?? 'renewal')]);
                $clearanceId = (int) $db->lastInsertId();
            } else {
                $db->prepare("UPDATE clearance_requests SET submitted_at = NOW() WHERE clearance_id = ?")->execute([$clearanceId]);
            }
            $officeInsert = $db->prepare('INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, \'Missing\')');
            foreach ($offices as $office) {
                $officeInsert->execute([$clearanceId, (int) $office['clearance_office_id']]);
            }
            $itemQuery = $db->prepare('SELECT clearance_item_id, status, file_path FROM clearance_items WHERE clearance_id = ? AND clearance_office_id = ? LIMIT 1');
            $update = $db->prepare("UPDATE clearance_items SET file_path = ?, original_name = ?, status = 'Pending Review', remarks = NULL, cleared_by_external_id = NULL, cleared_at = NULL WHERE clearance_item_id = ?");
            foreach ($officesById as $officeId => $office) {
                if ($office['name'] === 'Letter of Intent') {
                    continue;
                }
                $file = $files[$officeId] ?? null;
                $itemQuery->execute([$clearanceId, $officeId]);
                $existing = $itemQuery->fetch();
                $hasNewFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !empty($file['tmp_name']);
                if ($hasNewFile && $existing && !empty($existing['file_path']) && ($existing['status'] ?? '') === 'Pending Review') {
                    throw new RuntimeException('This requirement is currently under review and cannot be replaced until the Department Head has responded.');
                }
                if (!$hasNewFile) {
                    if (!$existing || empty($existing['file_path']) || $existing['status'] === 'Missing') {
                        throw new RuntimeException('Please attach a file for: ' . ($office['name'] ?? 'requirement'));
                    }
                    if (facultyClearanceCanResubmit($existing)) {
                        throw new RuntimeException('Please attach a replacement document for the denied requirement: ' . ($office['name'] ?? 'requirement'));
                    }
                    continue;
                }
                $relativePath = facultyClearanceUpload($file, (int) $profile['id'], $officeId);
                $originalName = basename((string) ($file['name'] ?? ''));
                $update->execute([$relativePath, $originalName, (int) $existing['clearance_item_id']]);
            }
            facultyClearanceRecalculate($db, $clearanceId);
            facultyClearanceNotify($db, $facultyRecordId, 'Clearance submitted', 'Your clearance requirements were submitted to the Department Head for verification.', 'Medium');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'New clearance submission', 'A faculty clearance packet is ready for your review.');
            logActivity('create', 'Submitted faculty clearance requirements #' . $clearanceId, 'faculty');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        clearanceApiResponse(['ok' => true, 'message' => 'Your clearance requirements were submitted to the Department Head for review.', 'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))]);
    }

    if ($action === 'intent') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $intentType = (string) ($_POST['intent_type'] ?? '');
        if (!in_array($intentType, ['renewal', 'resignation', 'regularization'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Please select a valid statement of intent.'], 422);
        }
        $file = $_FILES['intent_file'] ?? null;
        if (!$file) {
            clearanceApiResponse(['ok' => false, 'error' => 'Please select your Letter of Intent.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);
        $db->beginTransaction();
        try {
            $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
            $find->execute([$facultyRecordId, (int) $term['term_id']]);
            $clearanceId = (int) ($find->fetchColumn() ?: 0);
            if (!$clearanceId) {
                $insert = $db->prepare("INSERT INTO clearance_requests (faculty_id, term_id, intent_type, overall_status) VALUES (?, ?, ?, 'In Progress')");
                $insert->execute([$facultyRecordId, (int) $term['term_id'], $intentType]);
                $clearanceId = (int) $db->lastInsertId();
            } else {
                $db->prepare('UPDATE clearance_requests SET intent_type = ? WHERE clearance_id = ?')->execute([$intentType, $clearanceId]);
            }
            $office = $db->prepare("SELECT clearance_office_id FROM clearance_offices WHERE name = 'Letter of Intent' LIMIT 1");
            $office->execute();
            $officeId = (int) $office->fetchColumn();
            $db->prepare("INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, 'Missing')")->execute([$clearanceId, $officeId]);
            $item = $db->prepare('SELECT clearance_item_id FROM clearance_items WHERE clearance_id = ? AND clearance_office_id = ? LIMIT 1');
            $item->execute([$clearanceId, $officeId]);
            $itemId = (int) $item->fetchColumn();
            $current = $db->prepare('SELECT status, remarks FROM clearance_items WHERE clearance_item_id = ? LIMIT 1');
            $current->execute([$itemId]);
            $currentItem = $current->fetch();
            if ($currentItem && !empty($currentItem['status']) && !facultyIntentCanUploadAgain($currentItem) && $currentItem['status'] !== 'Missing') {
                clearanceApiResponse(['ok' => false, 'error' => 'Your Letter of Intent cannot be uploaded again while it is pending review or on hold.'], 422);
            }
            $originalName = basename((string) ($file['name'] ?? ''));
            $path = facultyClearanceUpload($file, (int) $profile['id'], $officeId);
            $db->prepare("UPDATE clearance_items SET file_path = ?, original_name = ?, status = 'Pending Review', remarks = ? WHERE clearance_item_id = ?")->execute([$path, $originalName, trim((string) ($_POST['intent_remarks'] ?? '')), $itemId]);
            facultyClearanceRecalculate($db, $clearanceId);
            facultyClearanceNotify($db, $facultyRecordId, 'Letter of Intent submitted', 'Your Letter of Intent was sent to the Department Head for review.', 'Medium');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'New Letter of Intent submission', 'A Letter of Intent is ready for your review.');
            logActivity('create', 'Submitted Letter of Intent for clearance #' . $clearanceId, 'faculty');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        clearanceApiResponse(['ok' => true, 'message' => 'Your Letter of Intent was submitted to the Department Head.', 'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))]);
    }

    if ($action === 'review-item') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Review access is restricted.'], 403);
        }
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $remark = trim((string) ($_POST['remark'] ?? ''));
        $statusMap = [
            'approve' => 'Cleared',
            'deny' => 'Denied',
            'hold' => 'On Hold',
            'resubmit' => 'On Hold',
            'on_hold' => 'On Hold'
        ];
        $isHoldOrDeny = $decision !== 'approve';
        if (!isset($statusMap[$decision]) || ($isHoldOrDeny && $remark === '')) {
            clearanceApiResponse(['ok' => false, 'error' => 'A remark is required when denying or placing a requirement on hold.'], 422);
        }

        // Fetch item details along with department
        $item = $db->prepare('SELECT ci.clearance_id, cr.faculty_id, fp.designated_department 
                              FROM clearance_items ci 
                              JOIN clearance_requests cr ON cr.clearance_id = ci.clearance_id 
                              JOIN faculty f ON f.faculty_id = cr.faculty_id
                              JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                              WHERE ci.clearance_item_id = ? LIMIT 1');
        $item->execute([$itemId]);
        $itemRow = $item->fetch();
        if (!$itemRow) {
            clearanceApiResponse(['ok' => false, 'error' => 'Requirement not found.'], 404);
        }

        // SECURITY CHECK: Department isolation for Department Head
        if ($isDeptHead && !empty($assignedDepartments) && !in_array($itemRow['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Cannot review items for other departments.'], 403);
        }

        $remarkText = $decision === 'approve'
            ? ($remark !== '' ? $remark : 'Requirement approved.')
            : ($decision === 'deny' ? '[Denied] ' . $remark : '[On Hold] ' . $remark);
        $update = $db->prepare('UPDATE clearance_items SET status = ?, remarks = ?, cleared_by_external_id = ?, cleared_at = NOW() WHERE clearance_item_id = ?');
        $update->execute([$statusMap[$decision], $remarkText, (string) $userId, $itemId]);
        facultyClearanceRecalculate($db, (int) $itemRow['clearance_id']);
        $label = $decision === 'approve' ? 'approved' : ($decision === 'deny' ? 'denied' : 'placed on hold');
        facultyClearanceNotify($db, (int) $itemRow['faculty_id'], 'Clearance requirement reviewed', 'A clearance requirement was ' . $label . '. Remark: ' . $remarkText, $decision === 'approve' ? 'Low' : 'High Priority');
        logActivity('update', 'Reviewed clearance item #' . $itemId . ': ' . $label, 'faculty');
        clearanceApiResponse(['ok' => true, 'message' => 'Review result sent to the faculty member.']);
    }

    if ($action === 'renew-contract') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Permission denied to renew contracts.'], 403);
        }
        $facultyId = (int) ($_POST['faculty_id'] ?? 0);
        $newContractEnd = trim((string) ($_POST['new_contract_end'] ?? ''));
        $renewalRemark = trim((string) ($_POST['renewal_remark'] ?? ''));
        $empStatus = trim((string) ($_POST['employment_status'] ?? ''));

        if ($facultyId <= 0) {
            clearanceApiResponse(['ok' => false, 'error' => 'Invalid faculty member ID.'], 422);
        }
        if ($newContractEnd === '' || !strtotime($newContractEnd)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Please select a valid new contract end date.'], 422);
        }

        $target = $db->prepare('SELECT * FROM faculty_profiles WHERE id = ? LIMIT 1');
        $target->execute([$facultyId]);
        $targetProfile = $target->fetch();
        if (!$targetProfile) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty profile not found.'], 404);
        }

        // SECURITY CHECK: Department isolation for Department Head
        if ($isDeptHead && !empty($assignedDepartments) && !in_array($targetProfile['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Cannot renew contract for faculty in another department.'], 403);
        }

        $oldDate = !empty($targetProfile['contractual_end']) && $targetProfile['contractual_end'] !== '0000-00-00'
            ? date('M d, Y', strtotime($targetProfile['contractual_end']))
            : 'Not set';
        $formattedNewDate = date('M d, Y', strtotime($newContractEnd));

        if ($empStatus !== '' && in_array($empStatus, ['Probationary', 'Regular', 'Part-Time'], true)) {
            $db->prepare('UPDATE faculty_profiles SET contractual_end = ?, employment_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$newContractEnd, $empStatus, $facultyId]);
            if (!empty($targetProfile['faculty_id'])) {
                $db->prepare('UPDATE faculty SET employment_status = ?, updated_at = NOW() WHERE faculty_no = ?')
                    ->execute([$empStatus, $targetProfile['faculty_id']]);
            }
        } else {
            $db->prepare('UPDATE faculty_profiles SET contractual_end = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$newContractEnd, $facultyId]);
        }

        $canonicalFacultyId = facultyClearanceFacultyId($db, $facultyId);
        if ($canonicalFacultyId !== null) {
            $db->prepare("UPDATE clearance_requests SET overall_status = 'Cleared', updated_at = NOW() WHERE faculty_id = ? AND term_id = ?")
                ->execute([$canonicalFacultyId, (int) $term['term_id']]);

            $cidStmt = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
            $cidStmt->execute([$canonicalFacultyId, (int) $term['term_id']]);
            $renewClearanceId = (int) $cidStmt->fetchColumn();
            if ($renewClearanceId > 0) {
                facultyClearanceArchiveRecord($db, $renewClearanceId);
            }

            $notifMsg = "Your faculty contract has been renewed until {$formattedNewDate}.";
            if ($empStatus !== '' && $empStatus !== ($targetProfile['employment_status'] ?? '')) {
                $notifMsg .= " Employment status updated to: {$empStatus}.";
            }
            if ($renewalRemark !== '') {
                $notifMsg .= " Remarks from Department Head: {$renewalRemark}";
            }
            facultyClearanceNotify($db, $canonicalFacultyId, 'Faculty Contract Renewed', $notifMsg, 'High Priority');
        }

        $facultyName = facultyClearanceDisplayName($targetProfile);
        logActivity('update', "Renewed contract for faculty #{$facultyId} ({$facultyName}) from {$oldDate} to {$formattedNewDate}" . ($empStatus !== '' ? " (Status: {$empStatus})" : ''), 'faculty');

        clearanceApiResponse([
            'ok' => true,
            'message' => "Contract for {$facultyName} has been successfully renewed until {$formattedNewDate}" . ($empStatus !== '' ? " with status {$empStatus}." : '.'),
            'contractual_end' => $newContractEnd,
            'contractual_end_formatted' => $formattedNewDate,
            'employment_status' => $empStatus ?: ($targetProfile['employment_status'] ?? 'Probationary')
        ]);
    }

    if ($action === 'update-employment-status') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Permission denied to update employment status.'], 403);
        }
        $facultyId = (int) ($_POST['faculty_id'] ?? 0);
        $empStatus = trim((string) ($_POST['employment_status'] ?? ''));
        $statusRemark = trim((string) ($_POST['status_remark'] ?? ''));

        if ($facultyId <= 0) {
            clearanceApiResponse(['ok' => false, 'error' => 'Invalid faculty member ID.'], 422);
        }
        if (!in_array($empStatus, ['Probationary', 'Regular', 'Part-Time'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Please select a valid employment status (Probationary, Regular, or Part-Time).'], 422);
        }

        $target = $db->prepare('SELECT * FROM faculty_profiles WHERE id = ? LIMIT 1');
        $target->execute([$facultyId]);
        $targetProfile = $target->fetch();
        if (!$targetProfile) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty profile not found.'], 404);
        }

        // SECURITY CHECK: Department isolation for Department Head
        if ($isDeptHead && !empty($assignedDepartments) && !in_array($targetProfile['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Cannot update employment status for faculty in another department.'], 403);
        }

        $oldStatus = $targetProfile['employment_status'] ?? 'Probationary';

        $db->prepare('UPDATE faculty_profiles SET employment_status = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$empStatus, $facultyId]);

        if (!empty($targetProfile['faculty_id'])) {
            $db->prepare('UPDATE faculty SET employment_status = ?, updated_at = NOW() WHERE faculty_no = ?')
                ->execute([$empStatus, $targetProfile['faculty_id']]);
        }

        $canonicalFacultyId = facultyClearanceFacultyId($db, $facultyId);
        if ($canonicalFacultyId !== null) {
            $notifMsg = "Your employment status has been updated to {$empStatus}.";
            if ($statusRemark !== '') {
                $notifMsg .= " Remarks: {$statusRemark}";
            }
            facultyClearanceNotify($db, $canonicalFacultyId, 'Employment Status Updated', $notifMsg, $empStatus === 'Regular' ? 'High Priority' : 'Medium');
        }

        $facultyName = facultyClearanceDisplayName($targetProfile);
        logActivity('update', "Updated employment status for faculty #{$facultyId} ({$facultyName}) from {$oldStatus} to {$empStatus}", 'faculty');

        clearanceApiResponse([
            'ok' => true,
            'message' => "Employment status for {$facultyName} has been updated to {$empStatus}.",
            'employment_status' => $empStatus
        ]);
    }

    if ($action === 'reset-requirements') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceFacultyId($db, (int) $profile['id']);
        if ($facultyRecordId === null) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty record not found.'], 404);
        }
        $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
        $find->execute([$facultyRecordId, (int) $term['term_id']]);
        $clearanceId = (int) ($find->fetchColumn() ?: 0);
        if (!$clearanceId) {
            clearanceApiResponse(['ok' => true, 'message' => 'No active clearance to reset.']);
        }
        $checkCleared = $db->prepare("SELECT overall_status FROM clearance_requests WHERE clearance_id = ? LIMIT 1");
        $checkCleared->execute([$clearanceId]);
        if ($checkCleared->fetchColumn() === 'Cleared') {
            facultyClearanceArchiveRecord($db, $clearanceId);
        }
        $db->prepare(
            "UPDATE clearance_items
             SET status = 'Missing', file_path = NULL, remarks = NULL, cleared_by_external_id = NULL, cleared_at = NULL
             WHERE clearance_id = ?
               AND status <> 'Pending Review'"
        )->execute([$clearanceId]);
        $db->prepare("UPDATE clearance_requests SET overall_status = 'In Progress', updated_at = NOW() WHERE clearance_id = ?")
            ->execute([$clearanceId]);
        logActivity('update', 'Faculty reset clearance requirements #' . $clearanceId, 'faculty');
        clearanceApiResponse(['ok' => true, 'message' => 'Clearance requirements have been reset. You can now upload new files.']);
    }

    clearanceApiResponse(['ok' => false, 'error' => 'Unknown clearance action.'], 400);
} catch (Throwable $e) {
    error_log('Faculty clearance API error: ' . $e->getMessage());
    clearanceApiResponse(['ok' => false, 'error' => $e->getMessage()], 400);
}