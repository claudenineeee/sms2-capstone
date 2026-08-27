<?php
/** Shared faculty clearance queries and workflow rules. */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once ROOT_PATH . '/includes/authentication.php';

function facultyClearanceOffices(PDO $db): array
{
    $names = [
        ['Letter of Intent', 'Signed letter stating the faculty member intent.'],
        ['Updated Resume', 'Most recent faculty curriculum vitae or resume.'],
        ['Personal Evaluation', 'Completed personal evaluation document.'],
        ['Summary Evaluation', 'Completed summary evaluation document.'],
    ];

    $insert = $db->prepare('INSERT IGNORE INTO clearance_offices (name, sequence_order) VALUES (?, ?)');
    foreach ($names as $index => [$name]) {
        $insert->execute([$name, $index + 1]);
    }

    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $stmt = $db->prepare('SELECT clearance_office_id, name, sequence_order FROM clearance_offices WHERE name IN (' . $placeholders . ') ORDER BY sequence_order, clearance_office_id');
    $stmt->execute(array_column($names, 0));
    return $stmt->fetchAll();
}

function facultyClearanceProfile(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT * FROM faculty_profiles WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    return $profile ?: null;
}

/**
 * Returns an array of department codes assigned to the active user profile.
 * Fetches directly from junction table `dean_departments` or falls back to `designated_department`.
 */
function facultyClearanceAssignedDepartments(array $profile, PDO $db): array
{
    if (empty($profile)) {
        return [];
    }

    $userId = (int) ($profile['user_id'] ?? 0);

    // 1. Primary Look-up: Fetch assigned departments from the dean_departments junction table
    if ($userId > 0) {
        try {
            $stmt = $db->prepare('SELECT department_code FROM dean_departments WHERE user_id = ?');
            $stmt->execute([$userId]);
            $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($depts)) {
                return array_values(array_filter(array_map('trim', $depts)));
            }
        } catch (Throwable $e) {
            // Table might not exist yet; gracefully fallback to column parsing
            error_log('dean_departments fetch note: ' . $e->getMessage());
        }
    }

    // 2. Secondary Fallback: Parse comma-separated or single string from designated_department column
    $raw = trim((string) ($profile['designated_department'] ?? ''));
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

function facultyClearanceFacultyId(PDO $db, int $profileId): ?int
{
    $stmt = $db->prepare('SELECT faculty_id FROM faculty_profiles WHERE id = ? LIMIT 1');
    $stmt->execute([$profileId]);
    $facultyNo = (string) ($stmt->fetchColumn() ?? '');
    if ($facultyNo === '') {
        return null;
    }
    $stmt = $db->prepare('SELECT faculty_id FROM faculty WHERE faculty_no = ? LIMIT 1');
    $stmt->execute([$facultyNo]);
    $facultyId = $stmt->fetchColumn();
    return $facultyId === false ? null : (int) $facultyId;
}

function facultyClearanceEnsureFacultyRecord(PDO $db, array $profile): int
{
    $existing = facultyClearanceFacultyId($db, (int) $profile['id']);
    if ($existing !== null) {
        return $existing;
    }

    $departmentId = null;
    if (!empty($profile['designated_department'])) {
        $department = $db->prepare('SELECT department_id FROM departments WHERE code = ? OR name = ? LIMIT 1');
        $department->execute([(string) $profile['designated_department'], (string) $profile['designated_department']]);
        $departmentId = $department->fetchColumn();
    }
    $insert = $db->prepare("INSERT INTO faculty (faculty_no, external_user_id, first_name, middle_name, last_name, suffix, email, department_id, position, employment_status, profile_status, hired_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Faculty Professor', 'Probationary', 'Active', ?)");
    $insert->execute([
        (string) $profile['faculty_id'],
        (string) ($profile['user_id'] ?? ''),
        (string) ($profile['first_name'] ?? ''),
        $profile['middle_name'] ?? null,
        (string) ($profile['last_name'] ?? ''),
        $profile['suffix'] ?? null,
        $profile['email'] ?? null,
        $departmentId ?: null,
        $profile['hired_date'] ?: null,
    ]);
    return (int) $db->lastInsertId();
}

function facultyClearanceTerm(PDO $db): ?array
{
    $stmt = $db->query("SELECT * FROM academic_terms WHERE is_current = 1 ORDER BY term_id DESC LIMIT 1");
    $term = $stmt->fetch();
    if ($term) {
        return $term;
    }

    $term = $db->query('SELECT * FROM academic_terms ORDER BY term_id DESC LIMIT 1')->fetch();
    if ($term) {
        return $term;
    }

    $month = (int) date('n');
    $semester = $month >= 6 && $month <= 10 ? '1st Semester' : ($month >= 11 || $month <= 3 ? '2nd Semester' : 'Summer');
    $year = (int) date('Y');
    $academicYear = $month >= 6 ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
    $insert = $db->prepare('INSERT INTO academic_terms (academic_year, semester, is_current) VALUES (?, ?, 1)');
    $insert->execute([$academicYear, $semester]);
    return ['term_id' => (int) $db->lastInsertId(), 'academic_year' => $academicYear, 'semester' => $semester];
}

function facultyClearanceRequest(PDO $db, int $facultyId, ?int $termId = null): ?array
{
    $canonicalFacultyId = facultyClearanceFacultyId($db, $facultyId);
    if ($canonicalFacultyId === null) {
        return null;
    }
    if ($termId === null) {
        $term = facultyClearanceTerm($db);
        $termId = $term ? (int) $term['term_id'] : 0;
    }
    if ($termId < 1) {
        return null;
    }

    $stmt = $db->prepare('SELECT cr.*, at.academic_year, at.semester FROM clearance_requests cr JOIN academic_terms at ON at.term_id = cr.term_id WHERE cr.faculty_id = ? AND cr.term_id = ? LIMIT 1');
    $stmt->execute([$canonicalFacultyId, $termId]);
    $request = $stmt->fetch();
    if (!$request) {
        return null;
    }

    $allowedNames = ['Letter of Intent', 'Updated Resume', 'Personal Evaluation', 'Summary Evaluation'];
    $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
    $items = $db->prepare('SELECT ci.*, co.name, co.sequence_order FROM clearance_items ci JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ') ORDER BY co.sequence_order, co.clearance_office_id');
    $items->execute(array_merge([(int) $request['clearance_id']], $allowedNames));
    $request['items'] = $items->fetchAll();
    $total = count($request['items']);
    $approved = count(array_filter($request['items'], static fn(array $item): bool => $item['status'] === 'Cleared'));
    $submitted = count(array_filter($request['items'], static fn(array $item): bool => !empty($item['file_path'])));
    $request['total_items'] = $total;
    $request['approved_items'] = $approved;
    $request['submitted_items'] = $submitted;
    $request['progress'] = $total > 0 ? (int) round(($approved / $total) * 100) : 0;
    return $request;
}

function facultyClearanceStatus(?array $request): string
{
    if (!$request) {
        return 'Not Submitted';
    }
    if (($request['overall_status'] ?? '') === 'Cleared') {
        return 'Completed';
    }
    foreach ($request['items'] ?? [] as $item) {
        if ($item['status'] === 'Hold') {
            return 'Action Required';
        }
    }
    if ((int) ($request['submitted_items'] ?? 0) === 0) {
        return 'Not Started';
    }
    return 'Pending Verification';
}

function facultyClearanceItemLabel(array $item): string
{
    $status = (string) ($item['status'] ?? '');
    if ($status === 'Cleared') {
        return 'Approved';
    }
    if ($status === 'Denied' || $status === 'Hold') {
        return 'Denied';
    }
    if ($status === 'On Hold') {
        return 'On Hold';
    }
    if ($status === 'Pending Review') {
        return 'Pending Review';
    }
    if (empty($item['file_path']) || $status === 'Missing' || $status === '') {
        return 'Missing';
    }
    return $status;
}

function facultyClearanceCanResubmit(array $item): bool
{
    return in_array($item['status'] ?? '', ['Hold', 'Denied', 'On Hold'], true);
}

function facultyIntentCanUploadAgain(array $item): bool
{
    return in_array($item['status'] ?? '', ['Cleared', 'Hold', 'Denied', 'On Hold'], true);
}

function facultyClearanceJson(?array $request): array
{
    return [
        'clearance_id' => $request ? (int) $request['clearance_id'] : null,
        'intent_type' => $request['intent_type'] ?? 'renewal',
        'status' => facultyClearanceStatus($request),
        'progress' => (int) ($request['progress'] ?? 0),
        'submitted_items' => (int) ($request['submitted_items'] ?? 0),
        'approved_items' => (int) ($request['approved_items'] ?? 0),
        'total_items' => (int) ($request['total_items'] ?? 0),
        'submitted_at' => $request['submitted_at'] ?? null,
        'updated_at' => $request['updated_at'] ?? null,
        'items' => array_map(static function (array $item): array {
            return [
                'id' => (int) $item['clearance_item_id'],
                'office_id' => (int) $item['clearance_office_id'],
                'name' => $item['name'],
                'status' => $item['status'],
                'display_status' => facultyClearanceItemLabel($item),
                'can_resubmit' => facultyClearanceCanResubmit($item),
                'file_name' => !empty($item['original_name']) ? $item['original_name'] : ($item['file_path'] ? basename($item['file_path']) : null),
                'original_name' => $item['original_name'] ?? null,
                'remarks' => $item['remarks'],
                'cleared_at' => $item['cleared_at'],
            ];
        }, $request['items'] ?? []),
    ];
}

function facultyClearanceRecalculate(PDO $db, int $clearanceId): void
{
    $allowedNames = ['Letter of Intent', 'Updated Resume', 'Personal Evaluation', 'Summary Evaluation'];
    $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
    $stmt = $db->prepare('SELECT ci.status FROM clearance_items ci JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ')');
    $stmt->execute(array_merge([$clearanceId], $allowedNames));
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasHold = in_array('Hold', $statuses, true) || in_array('Denied', $statuses, true) || in_array('On Hold', $statuses, true);
    $overall = $statuses && !array_diff($statuses, ['Cleared']) ? 'Cleared' : ($hasHold ? 'With Hold' : 'In Progress');
    $update = $db->prepare('UPDATE clearance_requests SET overall_status = ? WHERE clearance_id = ?');
    $update->execute([$overall, $clearanceId]);

    if ($overall === 'Cleared') {
        facultyClearanceArchiveRecord($db, $clearanceId);
    }
}

function facultyClearanceArchiveRecord(PDO $db, int $clearanceId): void
{
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `faculty_clearance_archives` (
          `archive_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `clearance_id` int(10) unsigned NOT NULL,
          `faculty_id` int(10) unsigned NOT NULL,
          `term_id` int(10) unsigned NOT NULL,
          `profile_id` int(10) unsigned DEFAULT NULL,
          `faculty_no` varchar(50) DEFAULT NULL,
          `first_name` varchar(100) DEFAULT NULL,
          `middle_name` varchar(100) DEFAULT NULL,
          `last_name` varchar(100) DEFAULT NULL,
          `suffix` varchar(20) DEFAULT NULL,
          `email` varchar(150) DEFAULT NULL,
          `phone` varchar(50) DEFAULT NULL,
          `designated_department` varchar(100) DEFAULT NULL,
          `position` varchar(100) DEFAULT NULL,
          `academic_rank` varchar(100) DEFAULT NULL,
          `tier` varchar(50) DEFAULT NULL,
          `employment_status` varchar(50) DEFAULT NULL,
          `contractual_end` date DEFAULT NULL,
          `academic_year` varchar(20) DEFAULT NULL,
          `semester` varchar(50) DEFAULT NULL,
          `intent_type` varchar(50) DEFAULT 'renewal',
          `overall_status` varchar(50) DEFAULT 'Cleared',
          `items_json` longtext DEFAULT NULL,
          `submitted_at` datetime DEFAULT NULL,
          `completed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`archive_id`),
          KEY `idx_fca_faculty` (`faculty_id`),
          KEY `idx_fca_term` (`term_id`),
          KEY `idx_fca_clearance` (`clearance_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $stmt = $db->prepare('SELECT cr.*, at.academic_year, at.semester,
                                     f.faculty_no, fp.id AS profile_id, fp.first_name, fp.middle_name, fp.last_name, fp.suffix,
                                     fp.email, fp.phone, fp.designated_department, fp.position, fp.academic_rank, fp.tier,
                                     fp.employment_status, fp.contractual_end
                              FROM clearance_requests cr
                              JOIN academic_terms at ON at.term_id = cr.term_id
                              JOIN faculty f ON f.faculty_id = cr.faculty_id
                              LEFT JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                              WHERE cr.clearance_id = ? LIMIT 1');
        $stmt->execute([$clearanceId]);
        $rec = $stmt->fetch();
        if (!$rec) {
            return;
        }

        $allowedNames = ['Letter of Intent', 'Updated Resume', 'Personal Evaluation', 'Summary Evaluation'];
        $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
        $itemsStmt = $db->prepare('SELECT ci.*, co.name AS requirement_name, co.sequence_order 
                                  FROM clearance_items ci 
                                  JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id 
                                  WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ') 
                                  ORDER BY co.sequence_order, co.clearance_office_id');
        $itemsStmt->execute(array_merge([$clearanceId], $allowedNames));
        $items = $itemsStmt->fetchAll();

        $itemsList = array_map(static function (array $it): array {
            return [
                'id' => (int) $it['clearance_item_id'],
                'name' => $it['requirement_name'],
                'status' => $it['status'],
                'file_name' => !empty($it['original_name']) ? $it['original_name'] : ($it['file_path'] ? basename($it['file_path']) : null),
                'original_name' => $it['original_name'] ?? null,
                'file_path' => $it['file_path'],
                'remarks' => $it['remarks'],
                'cleared_at' => $it['cleared_at'] ?: date('Y-m-d H:i:s'),
            ];
        }, $items);

        $itemsJson = json_encode($itemsList, JSON_UNESCAPED_SLASHES);

        $insert = $db->prepare('INSERT INTO faculty_clearance_archives
            (clearance_id, faculty_id, term_id, profile_id, faculty_no, first_name, middle_name, last_name, suffix,
             email, phone, designated_department, position, academic_rank, tier, employment_status, contractual_end,
             academic_year, semester, intent_type, overall_status, items_json, submitted_at, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Cleared", ?, ?, NOW())');
        $insert->execute([
            $clearanceId,
            (int) $rec['faculty_id'],
            (int) $rec['term_id'],
            !empty($rec['profile_id']) ? (int) $rec['profile_id'] : null,
            $rec['faculty_no'] ?? null,
            $rec['first_name'] ?? null,
            $rec['middle_name'] ?? null,
            $rec['last_name'] ?? null,
            $rec['suffix'] ?? null,
            $rec['email'] ?? null,
            $rec['phone'] ?? null,
            $rec['designated_department'] ?? null,
            $rec['position'] ?? 'Faculty Professor',
            $rec['academic_rank'] ?? null,
            $rec['tier'] ?? null,
            $rec['employment_status'] ?? 'Probationary',
            !empty($rec['contractual_end']) && $rec['contractual_end'] !== '0000-00-00' ? $rec['contractual_end'] : null,
            $rec['academic_year'] ?? null,
            $rec['semester'] ?? null,
            $rec['intent_type'] ?? 'renewal',
            $itemsJson,
            $rec['submitted_at'] ?? date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('facultyClearanceArchiveRecord error: ' . $e->getMessage());
    }
}

function facultyClearanceNotify(PDO $db, int $facultyId, string $title, string $message, string $priority = 'Medium'): void
{
    $stmt = $db->prepare('INSERT INTO notifications (faculty_id, title, message, priority, notification_type) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$facultyId, substr($title, 0, 150), substr($message, 0, 500), $priority, 'faculty_clearance']);
}

function facultyClearanceNotifyDepartmentHeads(PDO $db, string $department, string $title, string $message): void
{
    $stmt = $db->prepare("SELECT * FROM faculty_profiles WHERE position = 'Department Head' AND designated_department = ? AND profile_status = 'Active'");
    $stmt->execute([$department]);
    foreach ($stmt->fetchAll() as $head) {
        $headFacultyId = facultyClearanceEnsureFacultyRecord($db, $head);
        facultyClearanceNotify($db, $headFacultyId, $title, $message, 'High Priority');
    }
}

function facultyClearanceUpload(array $file, int $facultyId, int $officeId): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('A required file is missing or could not be uploaded.');
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('Each clearance file must be 10 MB or smaller.');
    }

    $allowed = ['pdf'];
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only PDF files are allowed for clearance uploads.');
    }

    $mime = false;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false && !empty($file['tmp_name'])) {
            $mime = finfo_file($finfo, (string) $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mime !== false && $mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
        throw new RuntimeException('Only PDF files are allowed for clearance uploads.');
    }

    $relativeDirectory = 'faculty-clearance/' . $facultyId;
    $directory = ROOT_PATH . '/storage/uploads/' . $relativeDirectory;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('The clearance upload directory could not be created.');
    }
    $storedName = $officeId . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
    $target = $directory . '/' . $storedName;
    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        throw new RuntimeException('The clearance file could not be stored.');
    }
    return $relativeDirectory . '/' . $storedName;
}

function facultyClearanceExtractFiles(): array
{
    $normalized = [];
    if (!isset($_FILES['requirements']) || !is_array($_FILES['requirements'])) {
        return $normalized;
    }
    if (isset($_FILES['requirements']['name']) && is_array($_FILES['requirements']['name'])) {
        foreach ($_FILES['requirements']['name'] as $officeId => $name) {
            $normalized[(int) $officeId] = [
                'name' => (string) $name,
                'type' => (string) ($_FILES['requirements']['type'][$officeId] ?? ''),
                'tmp_name' => (string) ($_FILES['requirements']['tmp_name'][$officeId] ?? ''),
                'error' => (int) ($_FILES['requirements']['error'][$officeId] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($_FILES['requirements']['size'][$officeId] ?? 0),
            ];
        }
    } else {
        foreach ($_FILES['requirements'] as $officeId => $file) {
            if (is_array($file) && isset($file['name'])) {
                $normalized[(int) $officeId] = $file;
            }
        }
    }
    return $normalized;
}

function facultyClearanceDisplayName(array $profile): string
{
    return trim(implode(' ', array_filter([$profile['first_name'] ?? '', $profile['middle_name'] ?? '', $profile['last_name'] ?? '', $profile['suffix'] ?? ''])));
}