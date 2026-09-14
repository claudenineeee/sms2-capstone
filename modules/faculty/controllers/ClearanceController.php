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
        $item = null;
        if ($itemId > 0) {
            $stmt = $db->prepare('SELECT ci.file_path, ci.original_name, cr.faculty_id, fp.designated_department 
                                  FROM clearance_items ci 
                                  JOIN clearance_requests cr ON cr.clearance_id = ci.clearance_id 
                                  JOIN faculty f ON f.faculty_id = cr.faculty_id
                                  LEFT JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no OR fp.user_id = f.external_user_id
                                  WHERE ci.clearance_item_id = ? LIMIT 1');
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if ($item && !empty($item['file_path'])) {
                // SECURITY CHECK: Restrict file download for Department Heads to their assigned department
                if ($isDeptHead && !empty($assignedDepartments) && !empty($item['designated_department']) && !in_array($item['designated_department'], $assignedDepartments, true)) {
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
        $isOwner = ($profileFacultyId !== null && $item && (int) ($item['faculty_id'] ?? 0) === $profileFacultyId);
        $allowed = in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true) || $isOwner || ($profileFacultyId !== null);
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

        $sql = 'SELECT fp.*, cr.clearance_id, cr.overall_status, cr.submitted_at, cr.updated_at FROM faculty_profiles fp LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ? WHERE (fp.position NOT IN ("Department Head", "Dean") OR fp.position IS NULL) AND (fp.profile_status = ? OR fp.profile_status IS NULL)';
        $params = [(int) $term['term_id'], 'Active'];

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
            $status = $row['clearance']['status'] ?? 'Not Submitted';
            $pending += in_array($status, ['Pending Verification', 'Under Review', 'Under Verification', 'For Final Approval', 'For Department Head Approval'], true) ? 1 : 0;
            $actionRequired += in_array($status, ['Action Required', 'With Deficiency'], true) ? 1 : 0;
            $archived += ($row['overall_status'] === 'Cleared' || $status === 'Cleared') ? 1 : 0;
        }
        clearanceApiResponse(['ok' => true, 'profile' => $profile, 'term' => $term, 'offices' => $offices, 'rows' => $rows, 'metrics' => ['pending' => $pending, 'action_required' => $actionRequired, 'archived' => $archived]]);
    }

    if ($action === 'review') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
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

        $allowedNames = facultyClearanceRequirementNames();
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
        $allowedNames = facultyClearanceRequirementNames();
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

    if ($action === 'submit-clearance-form') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));
        $declaration = trim((string) ($_POST['declaration'] ?? 'I hereby acknowledge and agree that I have complied with the rules, regulations, policies, and professional standards of the institution during my period of service.'));
        $intentType = (string) ($_POST['intent_type'] ?? 'renewal');
        if (!in_array($intentType, ['renewal', 'resignation', 'regularization'], true)) {
            $intentType = 'renewal';
        }

        $sigToSave = $signatureData !== '' ? $signatureData : null;

        $db->beginTransaction();
        try {
            $find = $db->prepare('SELECT clearance_id, signature_data FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
            $find->execute([$facultyRecordId, (int) $term['term_id']]);
            $existingReq = $find->fetch();
            $clearanceId = (int) ($existingReq['clearance_id'] ?? 0);
            if (!$clearanceId) {
                $insert = $db->prepare("INSERT INTO clearance_requests (faculty_id, term_id, intent_type, form_submitted, form_submitted_at, form_status, faculty_declaration, signature_data, overall_status) VALUES (?, ?, ?, 1, NOW(), 'Pending Review', ?, ?, 'For Department Head Approval')");
                $insert->execute([$facultyRecordId, (int) $term['term_id'], $intentType, $declaration, $sigToSave]);
                $clearanceId = (int) $db->lastInsertId();
            } else {
                $finalSig = $sigToSave ?? ($existingReq['signature_data'] ?? null);
                $db->prepare("UPDATE clearance_requests SET form_submitted = 1, form_submitted_at = NOW(), form_status = 'Pending Review', faculty_declaration = ?, signature_data = ?, intent_type = ?, overall_status = 'For Department Head Approval', updated_at = NOW() WHERE clearance_id = ?")
                    ->execute([$declaration, $finalSig, $intentType, $clearanceId]);
            }

            // Ensure all 6 clearance offices exist as Missing items
            $officeInsert = $db->prepare('INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, \'Missing\')');
            foreach ($offices as $office) {
                $officeInsert->execute([$clearanceId, (int) $office['clearance_office_id']]);
            }

            $facultyName = facultyClearanceDisplayName($profile);
            facultyClearanceNotify($db, $facultyRecordId, 'Clearance Form Submitted', 'Your Faculty Clearance Form has been submitted and is currently awaiting review and endorsement by your Department Head.', 'Medium');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'New Faculty Clearance Form Submitted', "Faculty {$facultyName} has submitted an official Clearance Agreement Form for your review and endorsement.");
            logActivity('create', 'Submitted faculty clearance form #' . $clearanceId, 'faculty');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        clearanceApiResponse([
            'ok' => true,
            'message' => 'Clearance Agreement Form submitted successfully! It is now pending review and endorsement by your Department Head.',
            'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))
        ]);
    }

    if ($action === 'endorse-clearance-form' || $action === 'review-clearance-form') {
        if (!in_array($role, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean'], true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Permission denied to review clearance agreement forms.'], 403);
        }
        $facultyId = (int) ($_POST['faculty_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? 'approve');
        $remark = trim((string) ($_POST['remark'] ?? ''));

        if ($facultyId <= 0) {
            clearanceApiResponse(['ok' => false, 'error' => 'Invalid faculty member.'], 422);
        }

        $target = $db->prepare('SELECT * FROM faculty_profiles WHERE id = ? LIMIT 1');
        $target->execute([$facultyId]);
        $targetProfile = $target->fetch();
        if (!$targetProfile) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty profile not found.'], 404);
        }

        if ($isDeptHead && !empty($assignedDepartments) && !in_array($targetProfile['designated_department'], $assignedDepartments, true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'Access denied: Cannot review clearance forms for faculty in another department.'], 403);
        }

        $canonicalFacultyId = facultyClearanceFacultyId($db, $facultyId);
        if (!$canonicalFacultyId) {
            clearanceApiResponse(['ok' => false, 'error' => 'Faculty record not found.'], 404);
        }

        $find = $db->prepare('SELECT clearance_id, form_submitted, form_status FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
        $find->execute([$canonicalFacultyId, (int) $term['term_id']]);
        $req = $find->fetch();
        if (!$req || empty($req['form_submitted'])) {
            clearanceApiResponse(['ok' => false, 'error' => 'The faculty member has not submitted their Clearance Agreement Form yet.'], 422);
        }

        $clearanceId = (int) $req['clearance_id'];
        $db->beginTransaction();
        try {
            if ($decision === 'approve' || $decision === 'endorse') {
                $db->prepare("UPDATE clearance_requests SET form_status = 'Approved', form_approved_at = NOW(), form_approved_by = ?, form_remarks = ?, overall_status = 'Under Verification', updated_at = NOW() WHERE clearance_id = ?")
                    ->execute([(int) $userId, $remark ?: 'Clearance Agreement Form approved and endorsed by Department Head.', $clearanceId]);
                
                facultyClearanceNotify($db, $canonicalFacultyId, 'Clearance Agreement Form Endorsed', 'Your Clearance Agreement Form has been reviewed and endorsed by your Department Head. You may now proceed to the Clearance Portal to upload your unit clearance documents.', 'High Priority');
                logActivity('update', "Endorsed clearance form #{$clearanceId} for faculty #{$facultyId}", 'faculty');
                $db->commit();
                clearanceApiResponse(['ok' => true, 'message' => 'Clearance Agreement Form approved and endorsed. Requirement uploads are now unlocked for the faculty member.']);
            } else {
                if ($remark === '') {
                    clearanceApiResponse(['ok' => false, 'error' => 'A remark or explanation is required when returning the clearance agreement form.'], 422);
                }
                $db->prepare("UPDATE clearance_requests SET form_status = 'Rejected', form_submitted = 0, form_remarks = ?, overall_status = 'With Deficiency', updated_at = NOW() WHERE clearance_id = ?")
                    ->execute([$remark, $clearanceId]);
                
                facultyClearanceNotify($db, $canonicalFacultyId, 'Clearance Agreement Form Returned', "Your Clearance Agreement Form was returned by the Department Head. Reason: {$remark}", 'High Priority');
                logActivity('update', "Returned clearance form #{$clearanceId} for faculty #{$facultyId}: {$remark}", 'faculty');
                $db->commit();
                clearanceApiResponse(['ok' => true, 'message' => 'Clearance Agreement Form returned to faculty member for revision.']);
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'archive-clearance') {
        if (!$isDeptHead && !$isAdmin) {
            clearanceApiResponse(['ok' => false, 'error' => 'Permission denied. Only Department Heads and Administrators can archive clearance records.'], 403);
        }
        $targetFacultyId = (int) ($_POST['faculty_id'] ?? 0);
        $targetClearanceId = (int) ($_POST['clearance_id'] ?? 0);

        if (!$targetClearanceId && $targetFacultyId > 0) {
            $fRecId = facultyClearanceFacultyId($db, $targetFacultyId);
            if ($fRecId !== null) {
                $fStmt = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
                $fStmt->execute([$fRecId, (int) $term['term_id']]);
                $targetClearanceId = (int) ($fStmt->fetchColumn() ?: 0);
            }
        }

        if (!$targetClearanceId) {
            clearanceApiResponse(['ok' => false, 'error' => 'Clearance record not found for archiving.'], 404);
        }

        $sigStmt = $db->prepare('SELECT signature_data FROM clearance_requests WHERE clearance_id = ? LIMIT 1');
        $sigStmt->execute([$targetClearanceId]);
        $signatureData = trim((string) ($sigStmt->fetchColumn() ?: ''));
        if ($signatureData === '') {
            clearanceApiResponse(['ok' => false, 'error' => 'Cannot confirm and archive until the faculty has submitted a Faculty Declaration signature.'], 422);
        }

        facultyClearanceArchiveRecord($db, $targetClearanceId);
        $db->prepare("UPDATE clearance_requests SET overall_status = 'Cleared', updated_at = NOW() WHERE clearance_id = ?")
            ->execute([$targetClearanceId]);
        logActivity('archive', "Confirmed faculty declaration and archived clearance record #{$targetClearanceId}", 'faculty');

        clearanceApiResponse([
            'ok' => true,
            'message' => 'Faculty Declaration confirmed. The clearance record has been archived to Completed History.'
        ]);
    }

    if ($action === 'submit-declaration') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));
        if ($signatureData === '') {
            clearanceApiResponse(['ok' => false, 'error' => 'Digital signature is required to complete declaration.'], 422);
        }
        $declaration = trim((string) ($_POST['declaration'] ?? 'I hereby certify that I have completed and submitted the required documents and have returned any school property, records, or other accountable items assigned to me.'));

        $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
        $find->execute([$facultyRecordId, (int) $term['term_id']]);
        $clearanceId = (int) ($find->fetchColumn() ?: 0);
        if (!$clearanceId) {
            clearanceApiResponse(['ok' => false, 'error' => 'Clearance record not found.'], 404);
        }

        $request = facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']);
        $approvedItems = (int) ($request['approved_items'] ?? 0);
        $totalItems = (int) ($request['total_items'] ?? 0);
        if ($totalItems < 1 || $approvedItems < $totalItems) {
            clearanceApiResponse(['ok' => false, 'error' => 'All required office documents must be approved before you can submit your Faculty Declaration.'], 422);
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE clearance_requests SET signature_data = ?, faculty_declaration = ?, overall_status = 'For Department Head Approval', submitted_at = COALESCE(submitted_at, NOW()), updated_at = NOW() WHERE clearance_id = ?")
                ->execute([$signatureData, $declaration, $clearanceId]);

            $facultyName = facultyClearanceDisplayName($profile);
            facultyClearanceNotify($db, $facultyRecordId, 'Faculty Declaration Submitted', 'Your Faculty Declaration has been submitted and is now awaiting review by your Department Head.', 'High Priority');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'Faculty Declaration Submitted for Review', "Faculty {$facultyName} has submitted their Faculty Declaration and digital signature. Please review it in Faculty Clearance.");
            logActivity('update', "Submitted faculty declaration for department head review on clearance #{$clearanceId}", 'faculty');
            $db->commit();
            clearanceApiResponse(['ok' => true, 'message' => 'Faculty Declaration submitted successfully! It has been forwarded to your Department Head for review.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'submit' || $action === 'submit-requirements') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);

        // Check if Clearance Agreement Form has been submitted and approved by Dept Head
        $checkForm = $db->prepare('SELECT clearance_id, form_submitted, form_status FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
        $checkForm->execute([$facultyRecordId, (int) $term['term_id']]);
        $existingForm = $checkForm->fetch();
        if (!$existingForm || empty($existingForm['form_submitted'])) {
            clearanceApiResponse(['ok' => false, 'error' => 'You must submit your Clearance Agreement Form first before submitting portal requirements.'], 422);
        }
        if (($existingForm['form_status'] ?? '') !== 'Approved') {
            clearanceApiResponse(['ok' => false, 'error' => 'Your Clearance Agreement Form is currently awaiting Department Head review and endorsement. You can submit requirements once it is approved.'], 422);
        }

        $officesById = [];
        foreach ($offices as $office) {
            $officesById[(int) $office['clearance_office_id']] = $office;
        }
        $files = facultyClearanceExtractFiles();
        $db->beginTransaction();
        try {
            $clearanceId = (int) $existingForm['clearance_id'];
            $sigData = trim((string) ($_POST['signature_data'] ?? ''));
            if ($sigData !== '') {
                $db->prepare("UPDATE clearance_requests SET signature_data = ?, submitted_at = NOW(), overall_status = 'Under Verification', updated_at = NOW() WHERE clearance_id = ?")->execute([$sigData, $clearanceId]);
            } else {
                $db->prepare("UPDATE clearance_requests SET submitted_at = NOW(), overall_status = 'Under Verification', updated_at = NOW() WHERE clearance_id = ?")->execute([$clearanceId]);
            }
            $officeInsert = $db->prepare('INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, \'Missing\')');
            foreach ($offices as $office) {
                $officeInsert->execute([$clearanceId, (int) $office['clearance_office_id']]);
            }
            $itemQuery = $db->prepare('SELECT clearance_item_id, status, file_path FROM clearance_items WHERE clearance_id = ? AND clearance_office_id = ? LIMIT 1');
            $update = $db->prepare("UPDATE clearance_items SET file_path = ?, original_name = ?, status = 'Pending Review', remarks = NULL, cleared_by_external_id = NULL, cleared_at = NULL WHERE clearance_item_id = ?");
            $uploadedCount = 0;
            foreach ($officesById as $officeId => $office) {
                $file = $files[$officeId] ?? null;
                $itemQuery->execute([$clearanceId, $officeId]);
                $existing = $itemQuery->fetch();
                $hasNewFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !empty($file['tmp_name']);
                if ($hasNewFile && $existing && !empty($existing['file_path']) && ($existing['status'] ?? '') === 'Pending Review') {
                    throw new RuntimeException('The requirement "' . ($office['name'] ?? 'requirement') . '" is currently under review and cannot be replaced until the Department Head has responded.');
                }
                if (!$hasNewFile) {
                    continue;
                }
                $relativePath = facultyClearanceUpload($file, (int) $profile['id'], $officeId);
                $originalName = basename((string) ($file['name'] ?? ''));
                $update->execute([$relativePath, $originalName, (int) $existing['clearance_item_id']]);
                $uploadedCount++;
            }
            facultyClearanceRecalculate($db, $clearanceId);
            facultyClearanceNotify($db, $facultyRecordId, 'Clearance requirements submitted', 'Your clearance requirements were submitted to the Department Head for verification.', 'Medium');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'New clearance requirements submission', 'Faculty submitted clearance requirement documents ready for verification.');
            logActivity('create', 'Submitted faculty clearance requirements #' . $clearanceId, 'faculty');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        clearanceApiResponse(['ok' => true, 'message' => 'Your clearance requirements were submitted successfully for verification.', 'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))]);
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
            'cleared' => 'Cleared',
            'deny' => 'Denied',
            'deficiency' => 'Denied',
            'with_deficiency' => 'Denied',
            'hold' => 'On Hold',
            'resubmit' => 'On Hold',
            'on_hold' => 'On Hold',
        ];
        $isHoldOrDeny = $decision !== 'approve' && $decision !== 'cleared';
        if (!isset($statusMap[$decision]) || ($isHoldOrDeny && $remark === '')) {
            clearanceApiResponse(['ok' => false, 'error' => 'A remark or deficiency explanation is required when flagging an office requirement.'], 422);
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

        $remarkText = ($decision === 'approve' || $decision === 'cleared')
            ? ($remark !== '' ? $remark : 'Requirement verified and cleared.')
            : (in_array($decision, ['deny', 'deficiency', 'with_deficiency'], true) ? '[With Deficiency] ' . $remark : '[On Hold] ' . $remark);
        $update = $db->prepare('UPDATE clearance_items SET status = ?, remarks = ?, cleared_by_external_id = ?, cleared_at = NOW() WHERE clearance_item_id = ?');
        $update->execute([$statusMap[$decision], $remarkText, (string) $userId, $itemId]);
        facultyClearanceRecalculate($db, (int) $itemRow['clearance_id']);
        $label = ($decision === 'approve' || $decision === 'cleared') ? 'cleared' : (in_array($decision, ['deny', 'deficiency', 'with_deficiency'], true) ? 'flagged with deficiency' : 'placed on hold');
        facultyClearanceNotify($db, (int) $itemRow['faculty_id'], 'Clearance requirement reviewed', 'A clearance requirement was ' . $label . '. Remark: ' . $remarkText, ($decision === 'approve' || $decision === 'cleared') ? 'Low' : 'High Priority');
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

    if ($action === 'reset-requirements' || $action === 'reset-clearance') {
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

        // Reset all clearance items to clean initial state
        $db->prepare(
            "UPDATE clearance_items
             SET status = 'Missing', file_path = NULL, original_name = NULL, remarks = NULL, cleared_by_external_id = NULL, cleared_at = NULL
             WHERE clearance_id = ?"
        )->execute([$clearanceId]);

        // Reset clearance request submission timestamp, form submission state, signature, and overall status
        $db->prepare("UPDATE clearance_requests SET overall_status = 'In Progress', form_submitted = 0, form_submitted_at = NULL, faculty_declaration = NULL, signature_data = NULL, submitted_at = NULL, updated_at = NOW() WHERE clearance_id = ?")
            ->execute([$clearanceId]);

        logActivity('update', 'Faculty reset clearance form, signature & requirement files #' . $clearanceId, 'faculty');
        clearanceApiResponse([
            'ok' => true, 
            'message' => 'Clearance form, digital signature, and all uploaded files have been completely reset.',
            'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))
        ]);
    }

    if ($action === 'init-clearance') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }
        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);
        $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
        $find->execute([$facultyRecordId, (int) $term['term_id']]);
        $clearanceId = (int) ($find->fetchColumn() ?: 0);
        if (!$clearanceId) {
            $insert = $db->prepare("INSERT INTO clearance_requests (faculty_id, term_id, intent_type, overall_status, submitted_at) VALUES (?, ?, 'renewal', 'In Progress', NOW())");
            $insert->execute([$facultyRecordId, (int) $term['term_id']]);
            $clearanceId = (int) $db->lastInsertId();
            // Seed all offices as Missing items so the clearance record exists
            $officeInsert = $db->prepare("INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, 'Missing')");
            foreach ($offices as $office) {
                $officeInsert->execute([$clearanceId, (int) $office['clearance_office_id']]);
            }
            facultyClearanceNotify($db, $facultyRecordId, 'Clearance Form Submitted', 'Your faculty clearance form has been submitted and is awaiting Department Head approval.', 'Medium');
            logActivity('create', 'Faculty submitted initial clearance form #' . $clearanceId, 'faculty');
        }
        clearanceApiResponse(['ok' => true, 'message' => 'Clearance form submitted. Awaiting Department Head review.', 'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']))]);
    }

    if ($action === 'upload-item') {
        if (!$profile) {
            clearanceApiResponse(['ok' => false, 'error' => 'No faculty profile is linked to this account.'], 422);
        }

        $officeId = (int) ($_POST['office_id'] ?? 0);
        if ($officeId <= 0) {
            clearanceApiResponse(['ok' => false, 'error' => 'Invalid clearance office.'], 422);
        }

        // Verify this office is one of the allowed clearance sections
        $validOfficeIds = array_column($offices, 'clearance_office_id');
        if (!in_array($officeId, array_map('intval', $validOfficeIds), true)) {
            clearanceApiResponse(['ok' => false, 'error' => 'The specified office is not a recognized clearance section.'], 422);
        }

        $uploadedFile = $_FILES['clearance_file'] ?? null;
        if (!$uploadedFile || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            clearanceApiResponse(['ok' => false, 'error' => 'Please select a file to upload.'], 422);
        }

        $facultyRecordId = facultyClearanceEnsureFacultyRecord($db, $profile);

        $db->beginTransaction();
        try {
            // Get or create the clearance request for this term
            $find = $db->prepare('SELECT clearance_id FROM clearance_requests WHERE faculty_id = ? AND term_id = ? LIMIT 1');
            $find->execute([$facultyRecordId, (int) $term['term_id']]);
            $clearanceId = (int) ($find->fetchColumn() ?: 0);
            if (!$clearanceId) {
                $insert = $db->prepare("INSERT INTO clearance_requests (faculty_id, term_id, intent_type, overall_status, submitted_at) VALUES (?, ?, 'renewal', 'In Progress', NOW())");
                $insert->execute([$facultyRecordId, (int) $term['term_id']]);
                $clearanceId = (int) $db->lastInsertId();
                // Seed all offices as Missing items
                $officeInsert = $db->prepare("INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, 'Missing')");
                foreach ($offices as $office) {
                    $officeInsert->execute([$clearanceId, (int) $office['clearance_office_id']]);
                }
            } else {
                // Ensure item row exists for this office
                $db->prepare("INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, 'Missing')")->execute([$clearanceId, $officeId]);
            }

            // Fetch the existing clearance item
            $itemStmt = $db->prepare('SELECT clearance_item_id, status, file_path FROM clearance_items WHERE clearance_id = ? AND clearance_office_id = ? LIMIT 1');
            $itemStmt->execute([$clearanceId, $officeId]);
            $existingItem = $itemStmt->fetch();

            if (!$existingItem) {
                $db->rollBack();
                clearanceApiResponse(['ok' => false, 'error' => 'Clearance item could not be initialized.'], 500);
            }

            // Block replacement if currently Under Verification
            $existingStatus = (string) ($existingItem['status'] ?? '');
            if (in_array($existingStatus, ['Pending Review', 'Under Verification'], true)) {
                $db->rollBack();
                clearanceApiResponse(['ok' => false, 'error' => 'This requirement is currently under review and cannot be replaced until the responsible office has responded.'], 422);
            }

            // Upload the file
            $relativePath = facultyClearanceUpload($uploadedFile, (int) $profile['id'], $officeId);
            $originalName = basename((string) ($uploadedFile['name'] ?? ''));

            $db->prepare("UPDATE clearance_items SET file_path = ?, original_name = ?, status = 'Pending Review', remarks = NULL, cleared_by_external_id = NULL, cleared_at = NULL WHERE clearance_item_id = ?")
                ->execute([$relativePath, $originalName, (int) $existingItem['clearance_item_id']]);

            facultyClearanceRecalculate($db, $clearanceId);

            $officeName = '';
            foreach ($offices as $off) {
                if ((int) $off['clearance_office_id'] === $officeId) {
                    $officeName = (string) ($off['name'] ?? '');
                    break;
                }
            }

            facultyClearanceNotify($db, $facultyRecordId, 'Clearance Document Uploaded', "A supporting document was uploaded for: {$officeName}.", 'Low');
            facultyClearanceNotifyDepartmentHeads($db, (string) ($profile['designated_department'] ?? ''), 'Clearance Document Uploaded', "Faculty uploaded a document for: {$officeName}. Awaiting office verification.");
            logActivity('create', 'Faculty uploaded clearance document for office #' . $officeId . ' (' . $officeName . ') clearance #' . $clearanceId, 'faculty');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        clearanceApiResponse([
            'ok' => true,
            'message' => 'Document uploaded successfully. It will be reviewed by the responsible office.',
            'clearance' => facultyClearanceJson(facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id'])),
        ]);
    }

    clearanceApiResponse(['ok' => false, 'error' => 'Unknown clearance action.'], 400);
} catch (Throwable $e) {
    error_log('Faculty clearance API error: ' . $e->getMessage());
    clearanceApiResponse(['ok' => false, 'error' => $e->getMessage()], 400);
}