<?php
/** Office-specific clearance archive tables and functions */
declare(strict_types=1);

/**
 * Create office-specific archive tables for each clearance office
 * Each office will have its own archive table containing only records relevant to that office
 */
function facultyClearanceCreateOfficeArchiveTables(PDO $db): void
{
    static $ensured = false;
    if ($ensured || $db->inTransaction()) {
        return;
    }
    $ensured = true;

    $offices = [
        'academic' => 'Academic Clearance',
        'department' => 'Department Clearance', 
        'library' => 'Library Clearance',
        'property' => 'Property Clearance',
        'financial' => 'Financial Clearance',
        'hr' => 'HR Clearance'
    ];

    foreach ($offices as $officeKey => $officeName) {
        $tableName = "faculty_clearance_archives_{$officeKey}";
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
          `archive_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `clearance_id` int(10) unsigned NOT NULL,
          `clearance_item_id` int(10) unsigned NOT NULL,
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
          `requirement_name` varchar(100) NOT NULL,
          `requirement_status` varchar(50) NOT NULL,
          `file_name` varchar(255) DEFAULT NULL,
          `file_path` varchar(500) DEFAULT NULL,
          `original_name` varchar(255) DEFAULT NULL,
          `remarks` text DEFAULT NULL,
          `cleared_at` datetime DEFAULT NULL,
          `submitted_at` datetime DEFAULT NULL,
          `reviewed_by` int(10) unsigned DEFAULT NULL,
          `reviewed_by_name` varchar(100) DEFAULT NULL,
          `reviewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`archive_id`),
          KEY `idx_archive_clearance` (`clearance_id`),
          KEY `idx_archive_faculty` (`faculty_id`),
          KEY `idx_archive_term` (`term_id`),
          KEY `idx_archive_item` (`clearance_item_id`),
          KEY `idx_archive_status` (`requirement_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        try {
            $db->exec($sql);
        } catch (Throwable $e) {
            error_log("Failed to create archive table {$tableName}: " . $e->getMessage());
        }
    }
}

/**
 * Archive a specific clearance item for an office
 * This is called when an office approves/denies a specific requirement
 */
function facultyClearanceArchiveOfficeItem(PDO $db, int $clearanceItemId, string $officeKey, int $reviewedById = 0, string $reviewedByName = ''): void
{
    $validOffices = ['academic', 'department', 'library', 'property', 'financial', 'hr'];
    if (!in_array($officeKey, $validOffices, true)) {
        return;
    }

    $tableName = "faculty_clearance_archives_{$officeKey}";
    
    try {
        // Ensure table exists
        facultyClearanceCreateOfficeArchiveTables($db);
        
        // Get the clearance item details with faculty information
        $stmt = $db->prepare('SELECT ci.*, cr.faculty_id, cr.term_id, cr.intent_type, cr.submitted_at,
                                     f.faculty_no, fp.id AS profile_id, fp.first_name, fp.middle_name, fp.last_name, fp.suffix,
                                     fp.email, fp.phone, fp.designated_department, fp.position, fp.academic_rank, fp.tier,
                                     fp.employment_status, fp.contractual_end, at.academic_year, at.semester,
                                     co.name AS requirement_name
                              FROM clearance_items ci
                              JOIN clearance_requests cr ON cr.clearance_id = ci.clearance_id
                              JOIN academic_terms at ON at.term_id = cr.term_id
                              JOIN faculty f ON f.faculty_id = cr.faculty_id
                              LEFT JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                              JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id
                              WHERE ci.clearance_item_id = ? LIMIT 1');
        $stmt->execute([$clearanceItemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return;
        }

        // Check if already archived
        $checkStmt = $db->prepare("SELECT archive_id FROM {$tableName} WHERE clearance_item_id = ? LIMIT 1");
        $checkStmt->execute([$clearanceItemId]);
        if ($checkStmt->fetch()) {
            // Update existing record
            $update = $db->prepare("UPDATE {$tableName}
                SET requirement_status = ?, file_name = ?, file_path = ?, original_name = ?,
                    remarks = ?, cleared_at = ?,
                    reviewed_by = ?, reviewed_by_name = ?, reviewed_at = NOW()
                WHERE clearance_item_id = ?");
            $update->execute([
                $item['status'],
                !empty($item['original_name']) ? $item['original_name'] : ($item['file_path'] ? basename($item['file_path']) : null),
                $item['file_path'],
                $item['original_name'],
                $item['remarks'],
                $item['cleared_at'] ?: date('Y-m-d H:i:s'),
                $reviewedById > 0 ? $reviewedById : null,
                $reviewedByName !== '' ? $reviewedByName : null,
                $clearanceItemId
            ]);
        } else {
            // Insert new archive record
            $insert = $db->prepare("INSERT INTO {$tableName}
                (clearance_id, clearance_item_id, faculty_id, term_id, profile_id, faculty_no, first_name, middle_name, last_name, suffix,
                 email, phone, designated_department, position, academic_rank, tier, employment_status, contractual_end,
                 academic_year, semester, intent_type, requirement_name, requirement_status, file_name, file_path, original_name,
                 remarks, cleared_at, submitted_at, reviewed_by, reviewed_by_name, reviewed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert->execute([
                (int) $item['clearance_id'],          // clearance_id
                (int) $item['clearance_item_id'],     // clearance_item_id
                (int) $item['faculty_id'],            // faculty_id
                (int) $item['term_id'],               // term_id
                !empty($item['profile_id']) ? (int) $item['profile_id'] : null, // profile_id
                $item['faculty_no'] ?? null,          // faculty_no
                $item['first_name'] ?? null,          // first_name
                $item['middle_name'] ?? null,         // middle_name
                $item['last_name'] ?? null,           // last_name
                $item['suffix'] ?? null,              // suffix
                $item['email'] ?? null,               // email
                $item['phone'] ?? null,               // phone
                $item['designated_department'] ?? null, // designated_department
                $item['position'] ?? 'Faculty Professor', // position
                $item['academic_rank'] ?? null,       // academic_rank
                $item['tier'] ?? null,                // tier
                $item['employment_status'] ?? 'Probationary', // employment_status
                !empty($item['contractual_end']) && $item['contractual_end'] !== '0000-00-00' ? $item['contractual_end'] : null, // contractual_end
                $item['academic_year'] ?? null,       // academic_year
                $item['semester'] ?? null,            // semester
                $item['intent_type'] ?? 'renewal',   // intent_type
                $item['requirement_name'],            // requirement_name
                $item['status'],                      // requirement_status
                !empty($item['original_name']) ? $item['original_name'] : ($item['file_path'] ? basename($item['file_path']) : null), // file_name
                $item['file_path'],                   // file_path
                $item['original_name'],               // original_name
                $item['remarks'],                     // remarks
                $item['cleared_at'] ?: date('Y-m-d H:i:s'), // cleared_at
                $item['submitted_at'] ?? date('Y-m-d H:i:s'), // submitted_at
                $reviewedById > 0 ? $reviewedById : null,     // reviewed_by
                $reviewedByName !== '' ? $reviewedByName : null, // reviewed_by_name
                // reviewed_at = NOW() (inline in SQL above)
            ]);

        }
    } catch (Throwable $e) {
        error_log("facultyClearanceArchiveOfficeItem error for {$officeKey}: " . $e->getMessage());
    }
}

/**
 * Get office key from clearance office name
 */
function facultyClearanceGetOfficeKey(string $officeName): string
{
    $officeMap = [
        'Academic Clearance' => 'academic',
        'Department Clearance' => 'department',
        'Library Clearance' => 'library', 
        'Property Clearance' => 'property',
        'Financial Clearance' => 'financial',
        'HR Clearance' => 'hr'
    ];
    
    return $officeMap[$officeName] ?? 'academic';
}

/**
 * Get archived records for a specific office
 */
function facultyClearanceGetOfficeArchives(PDO $db, string $officeKey, array $assignedDepartments = [], bool $canSeeAll = false): array
{
    $validOffices = ['academic', 'department', 'library', 'property', 'financial', 'hr'];
    if (!in_array($officeKey, $validOffices, true)) {
        return [];
    }

    // Always ensure tables exist before querying (they may not have been created yet)
    facultyClearanceCreateOfficeArchiveTables($db);

    $tableName = "faculty_clearance_archives_{$officeKey}";

    $sql = "SELECT * FROM {$tableName} WHERE 1=1";
    $params = [];

    // Apply department filter if restricted
    if (!$canSeeAll && !empty($assignedDepartments)) {
        $placeholders = implode(',', array_fill(0, count($assignedDepartments), '?'));
        $sql .= " AND designated_department IN ({$placeholders})";
        $params = array_merge($params, $assignedDepartments);
    }

    $sql .= ' ORDER BY reviewed_at DESC, archive_id DESC';

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log("Error getting office archives for {$officeKey}: " . $e->getMessage());
        return [];
    }
}


/**
 * Fallback: query cleared items live from clearance_items for offices
 * whose archive table may be empty due to the pre-fix INSERT bug.
 * Returns rows in the same shape as facultyClearanceGetOfficeArchives.
 */
function facultyClearanceLiveOfficeItems(PDO $db, string $officeKey, array $assignedDepartments = [], bool $canSeeAll = false): array
{
    $officeNameMap = [
        'academic'   => 'Academic Clearance',
        'department' => 'Department Clearance',
        'library'    => 'Library Clearance',
        'property'   => 'Property Clearance',
        'financial'  => 'Financial Clearance',
        'hr'         => 'HR Clearance',
    ];
    $officeName = $officeNameMap[$officeKey] ?? null;
    if ($officeName === null) {
        return [];
    }

    $sql = "SELECT
                ci.clearance_item_id AS archive_id,
                ci.clearance_id,
                ci.clearance_item_id,
                cr.faculty_id,
                cr.term_id,
                fp.id AS profile_id,
                fp.faculty_id AS faculty_no,
                fp.first_name, fp.middle_name, fp.last_name, fp.suffix,
                fp.email, fp.phone,
                fp.designated_department, fp.position, fp.academic_rank, fp.tier,
                fp.employment_status, fp.contractual_end,
                at.academic_year, at.semester,
                cr.intent_type,
                co.name AS requirement_name,
                ci.status AS requirement_status,
                ci.original_name AS file_name,
                ci.file_path,
                ci.original_name,
                ci.remarks,
                ci.cleared_at,
                cr.submitted_at,
                ci.cleared_at AS reviewed_at
            FROM clearance_items ci
            JOIN clearance_requests cr  ON cr.clearance_id = ci.clearance_id
            JOIN clearance_offices co   ON co.clearance_office_id = ci.clearance_office_id
            JOIN academic_terms at      ON at.term_id = cr.term_id
            JOIN faculty f              ON f.faculty_id = cr.faculty_id
            LEFT JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
            WHERE co.name = ? AND ci.status = 'Cleared'";
    $params = [$officeName];

    if (!$canSeeAll && !empty($assignedDepartments)) {
        $placeholders = implode(',', array_fill(0, count($assignedDepartments), '?'));
        $sql .= " AND fp.designated_department IN ({$placeholders})";
        $params = array_merge($params, $assignedDepartments);
    }

    $sql .= ' ORDER BY ci.cleared_at DESC, ci.clearance_item_id DESC';

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log("facultyClearanceLiveOfficeItems error for {$officeKey}: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a specific archived record for an office
 */
function facultyClearanceGetOfficeArchiveDetail(PDO $db, string $officeKey, int $archiveId): ?array
{
    $validOffices = ['academic', 'department', 'library', 'property', 'financial', 'hr'];
    if (!in_array($officeKey, $validOffices, true)) {
        return null;
    }

    $tableName = "faculty_clearance_archives_{$officeKey}";
    
    $stmt = $db->prepare("SELECT * FROM {$tableName} WHERE archive_id = ? LIMIT 1");
    $stmt->execute([$archiveId]);
    return $stmt->fetch() ?: null;
}