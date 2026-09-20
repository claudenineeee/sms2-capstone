<?php
/** Office-specific clearance archive tables and functions */
declare(strict_types=1);

/**
 * Create office-specific archive tables for each clearance office
 * Each office will have its own archive table containing only records relevant to that office
 */
function facultyClearanceCreateOfficeArchiveTables(PDO $db): void
{
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
function facultyClearanceArchiveOfficeItem(PDO $db, int $clearanceItemId, string $officeKey): void
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
                    remarks = ?, cleared_at = ?, reviewed_at = NOW()
                WHERE clearance_item_id = ?");
            $update->execute([
                $item['status'],
                !empty($item['original_name']) ? $item['original_name'] : ($item['file_path'] ? basename($item['file_path']) : null),
                $item['file_path'],
                $item['original_name'],
                $item['remarks'],
                $item['cleared_at'] ?: date('Y-m-d H:i:s'),
                $clearanceItemId
            ]);
        } else {
            // Insert new archive record
            $insert = $db->prepare("INSERT INTO {$tableName}
                (clearance_id, clearance_item_id, faculty_id, term_id, profile_id, faculty_no, first_name, middle_name, last_name, suffix,
                 email, phone, designated_department, position, academic_rank, tier, employment_status, contractual_end,
                 academic_year, semester, intent_type, requirement_name, requirement_status, file_name, file_path, original_name, 
                 remarks, cleared_at, submitted_at, reviewed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $insert->execute([
                (int) $item['clearance_id'],
                (int) $item['clearance_item_id'],
                (int) $item['faculty_id'],
                (int) $item['term_id'],
                !empty($item['profile_id']) ? (int) $item['profile_id'] : null,
                $item['faculty_no'] ?? null,
                $item['first_name'] ?? null,
                $item['middle_name'] ?? null,
                $item['last_name'] ?? null,
                $item['suffix'] ?? null,
                $item['email'] ?? null,
                $item['phone'] ?? null,
                $item['designated_department'] ?? null,
                $item['position'] ?? 'Faculty Professor',
                $item['academic_rank'] ?? null,
                $item['tier'] ?? null,
                $item['employment_status'] ?? 'Probationary',
                !empty($item['contractual_end']) && $item['contractual_end'] !== '0000-00-00' ? $item['contractual_end'] : null,
                $item['academic_year'] ?? null,
                $item['semester'] ?? null,
                $item['intent_type'] ?? 'renewal',
                $item['requirement_name'],
                $item['status'],
                !empty($item['original_name']) ? $item['original_name'] : ($item['file_path'] ? basename($item['file_path']) : null),
                $item['file_path'],
                $item['original_name'],
                $item['remarks'],
                $item['cleared_at'] ?: date('Y-m-d H:i:s'),
                $item['submitted_at'] ?? date('Y-m-d H:i:s'),
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

    $tableName = "faculty_clearance_archives_{$officeKey}";
    
    // Check if table exists
    $tableExists = $db->query("SHOW TABLES LIKE '{$tableName}'")->fetch();
    if (!$tableExists) {
        return [];
    }
    
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