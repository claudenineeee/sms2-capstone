<?php
/** Shared faculty clearance queries and workflow rules. */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once ROOT_PATH . '/includes/authentication.php';

function facultyClearanceRequirementDefinitions(): array
{
    return [
        ['Academic Clearance', 'Grade sheets, class records, syllabus, attendance/DTR, pending student academic concerns.'],
        ['Library Clearance', 'No unreturned books/materials, library accountabilities cleared.'],
        ['Financial Clearance', 'No outstanding financial obligations, cash advances/accountabilities settled.'],
        ['Property Clearance', 'School equipment returned, ID/keys/other issued institutional property returned.'],
        ['HR Clearance', 'Required HR documents submitted, contract/employment records, final HR verification.'],
        ['Department Clearance', 'Department reports, assigned duties, committee responsibilities, Department Head verification.'],
    ];
}

function facultyClearanceSections(): array
{
    return [
        'Academic Clearance' => [
            'name' => 'Academic Clearance',
            'office' => 'Academic Affairs / Registrar',
            'icon' => 'fa-graduation-cap',
            'description' => 'Grade sheets, class records, syllabus, attendance/DTR, pending student academic concerns.',
            'items' => [
                'Grade sheets submitted and finalized',
                'Class records & grading sheets completed',
                'Course syllabi & instructional materials submitted',
                'Attendance records & DTR completed',
                'Pending student academic concerns resolved',
            ],
        ],
        'Library Clearance' => [
            'name' => 'Library Clearance',
            'office' => 'University / College Library',
            'icon' => 'fa-book-bookmark',
            'description' => 'No unreturned books/materials, library accountabilities cleared.',
            'items' => [
                'No unreturned books, journals, or media',
                'No outstanding library fines or accountabilities',
                'Borrower status verified & cleared in library system',
            ],
        ],
        'Financial Clearance' => [
            'name' => 'Financial Clearance',
            'office' => 'Accounting & Finance Office',
            'icon' => 'fa-receipt',
            'description' => 'No outstanding financial obligations, cash advances/accountabilities settled.',
            'items' => [
                'No outstanding cash advances or unliquidated balances',
                'Emergency loans & faculty fund accountabilities settled',
                'Official statement of account cleared',
            ],
        ],
        'Property Clearance' => [
            'name' => 'Property Clearance',
            'office' => 'Property & Custodian Office',
            'icon' => 'fa-boxes-stacked',
            'description' => 'School equipment returned, ID/keys/other issued institutional property returned.',
            'items' => [
                'School-issued laptop & equipment returned/accounted for',
                'Facility keys, laboratory tools & apparatus returned',
                'Property accountability & gate passes cleared',
            ],
        ],
        'HR Clearance' => [
            'name' => 'HR Clearance',
            'office' => 'Human Resources (HR)',
            'icon' => 'fa-user-check',
            'description' => 'Required HR documents submitted, contract/employment records, final HR verification.',
            'items' => [
                'Required annual HR documents & PDS/CV submitted',
                'Contract & employment records updated',
                'Final HR sign-off and administrative clearance',
            ],
        ],
        'Department Clearance' => [
            'name' => 'Department Clearance',
            'office' => 'Department Head / Dean',
            'icon' => 'fa-building-columns',
            'description' => 'Department reports, assigned duties, committee responsibilities.',
            'items' => [
                'Departmental reports submitted',
                'Assigned department duties completed',
                'Committee responsibilities fulfilled',
            ],
        ],
    ];
}

function facultyClearanceRequirements(): array
{
    return facultyClearanceSections();
}

function facultyClearanceRequirementNames(): array
{
    return array_column(facultyClearanceRequirementDefinitions(), 0);
}

function facultyClearanceOffices(PDO $db): array
{
    $definitions = facultyClearanceRequirementDefinitions();

    try {
        $cols = $db->query("SHOW COLUMNS FROM clearance_offices LIKE 'description'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE clearance_offices ADD COLUMN description TEXT NULL AFTER name");
        }
        $db->exec("ALTER TABLE clearance_requests MODIFY COLUMN overall_status VARCHAR(50) NOT NULL DEFAULT 'In Progress'");
        $db->exec("ALTER TABLE clearance_items MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Missing'");
        $db->exec("ALTER TABLE clearance_items MODIFY COLUMN remarks TEXT NULL");

        $reqCols = $db->query("SHOW COLUMNS FROM clearance_requests")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('form_submitted', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_submitted TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('form_submitted_at', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_submitted_at DATETIME NULL");
        }
        if (!in_array('form_status', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_status VARCHAR(50) NOT NULL DEFAULT 'Not Submitted'");
        }
        if (!in_array('form_approved_at', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_approved_at DATETIME NULL");
        }
        if (!in_array('form_approved_by', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_approved_by INT UNSIGNED NULL");
        }
        if (!in_array('form_remarks', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN form_remarks TEXT NULL");
        }
        if (!in_array('faculty_declaration', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN faculty_declaration TEXT NULL");
        }
        if (!in_array('signature_data', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN signature_data LONGTEXT NULL");
        }
        $db->exec("ALTER TABLE clearance_requests MODIFY COLUMN submitted_at DATETIME NULL DEFAULT NULL");
    } catch (Throwable $e) {
        // Table or column already adjusted
    }

    facultyClearanceEnsureDigitalApprovalSchema($db);

    $insert = $db->prepare('INSERT INTO clearance_offices (name, description, sequence_order) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE sequence_order = VALUES(sequence_order), description = VALUES(description)');
    foreach ($definitions as $index => [$name, $desc]) {
        try {
            $insert->execute([$name, $desc, $index + 1]);
        } catch (Throwable $e) {
            $db->prepare('INSERT INTO clearance_offices (name, sequence_order) VALUES (?, ?) ON DUPLICATE KEY UPDATE sequence_order = VALUES(sequence_order)')->execute([$name, $index + 1]);
        }
    }

    $names = array_column($definitions, 0);
    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $stmt = $db->prepare('SELECT clearance_office_id, name, sequence_order FROM clearance_offices WHERE name IN (' . $placeholders . ') ORDER BY sequence_order, clearance_office_id');
    $stmt->execute($names);
    $rows = $stmt->fetchAll();

    $descMap = array_column($definitions, 1, 0);
    foreach ($rows as &$r) {
        $r['description'] = $r['description'] ?? ($descMap[$r['name']] ?? '');
    }
    unset($r);

    $orderMap = array_flip($names);
    usort($rows, static function ($a, $b) use ($orderMap) {
        $ordA = $orderMap[$a['name']] ?? 999;
        $ordB = $orderMap[$b['name']] ?? 999;
        return $ordA <=> $ordB;
    });

    return $rows;
}

function facultyClearanceEnsureDigitalApprovalSchema(PDO $db): void
{
    static $ensured = false;
    if ($ensured || $db->inTransaction()) {
        return;
    }
    $ensured = true;

    try {
        // 1. Table for official office-level digital approvals / sign-offs
        $db->exec("CREATE TABLE IF NOT EXISTS `clearance_office_approvals` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `clearance_id` int(10) unsigned NOT NULL,
            `faculty_id` int(10) unsigned NOT NULL,
            `office` varchar(50) NOT NULL,
            `approval_ref` varchar(50) NOT NULL,
            `approver_user_id` int(10) unsigned NOT NULL,
            `approver_name` varchar(150) NOT NULL,
            `approver_role` varchar(100) NOT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'Approved',
            `remarks` text NULL,
            `signature_data` LONGTEXT NULL,
            `approved_at` datetime NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_clearance_office` (`clearance_id`, `office`),
            KEY `idx_approval_faculty` (`faculty_id`),
            KEY `idx_approval_ref` (`approval_ref`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Extend clearance_office_approvals with signature_data if missing
        $approvalCols = $db->query("SHOW COLUMNS FROM clearance_office_approvals")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('signature_data', $approvalCols, true)) {
            $db->exec("ALTER TABLE clearance_office_approvals ADD COLUMN signature_data LONGTEXT NULL AFTER remarks");
        }

        // 2. Table for persistent digital clearance signatures across dept head, clearance-portal, and faculty declaration
        $db->exec("CREATE TABLE IF NOT EXISTS `clearance_signatures` (
            `signature_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `clearance_id` int(10) unsigned NOT NULL,
            `faculty_id` int(10) unsigned NOT NULL,
            `office` varchar(100) NOT NULL,
            `office_key` varchar(50) NOT NULL,
            `signatory_type` enum('faculty','department_head','office_signatory','dean','admin') NOT NULL DEFAULT 'office_signatory',
            `approval_ref` varchar(50) DEFAULT NULL,
            `signer_user_id` int(10) unsigned DEFAULT NULL,
            `signer_name` varchar(150) NOT NULL,
            `signer_role` varchar(100) NOT NULL,
            `signature_data` longtext NOT NULL,
            `remarks` text DEFAULT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'Signed',
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `signed_at` datetime NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`signature_id`),
            UNIQUE KEY `uk_clearance_office_sig` (`clearance_id`, `office_key`),
            KEY `idx_sig_faculty` (`faculty_id`),
            KEY `idx_sig_clearance` (`clearance_id`),
            KEY `idx_sig_signer` (`signer_user_id`),
            KEY `idx_sig_ref` (`approval_ref`),
            KEY `idx_sig_date` (`signed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Sync existing signatures from clearance_office_approvals into clearance_signatures
        try {
            $existingOa = $db->query("SELECT coa.*, cr.faculty_id AS req_faculty_id 
                FROM clearance_office_approvals coa 
                LEFT JOIN clearance_requests cr ON cr.clearance_id = coa.clearance_id
                WHERE coa.signature_data IS NOT NULL AND coa.signature_data != ''")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($existingOa)) {
                $syncStmt = $db->prepare("INSERT INTO clearance_signatures
                    (clearance_id, faculty_id, office, office_key, signatory_type, approval_ref, signer_user_id, signer_name, signer_role, signature_data, remarks, status, signed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        signature_data = VALUES(signature_data),
                        approval_ref = VALUES(approval_ref),
                        signer_name = VALUES(signer_name),
                        signer_role = VALUES(signer_role),
                        signed_at = VALUES(signed_at)");
                foreach ($existingOa as $row) {
                    $cId = (int) $row['clearance_id'];
                    $fId = (int) ($row['faculty_id'] ?: ($row['req_faculty_id'] ?? 0));
                    $off = (string) $row['office'];
                    $offKey = facultyClearanceOfficeKey($off);
                    $sigType = ($offKey === 'department' || in_array(strtolower((string) $row['approver_role']), ['dept_head', 'department_head'], true))
                        ? 'department_head'
                        : 'office_signatory';
                    $syncStmt->execute([
                        $cId,
                        $fId,
                        $off,
                        $offKey,
                        $sigType,
                        $row['approval_ref'] ?? null,
                        $row['approver_user_id'] ? (int) $row['approver_user_id'] : null,
                        $row['approver_name'] ?? 'Authorized Officer',
                        $row['approver_role'] ?? 'Signatory',
                        $row['signature_data'],
                        $row['remarks'] ?? null,
                        $row['status'] ?? 'Approved',
                        $row['approved_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (Throwable $e) { /* ignore */
        }

        // Sync existing faculty signatures from clearance_requests into clearance_signatures
        try {
            $existingReqSigs = $db->query("SELECT cr.clearance_id, cr.faculty_id, cr.signature_data, cr.faculty_declaration, cr.form_submitted_at, cr.submitted_at, fp.first_name, fp.last_name, fp.user_id 
                FROM clearance_requests cr
                LEFT JOIN faculty_profiles fp ON fp.id = cr.faculty_id
                WHERE cr.signature_data IS NOT NULL AND cr.signature_data != ''")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($existingReqSigs)) {
                $syncReqStmt = $db->prepare("INSERT INTO clearance_signatures
                    (clearance_id, faculty_id, office, office_key, signatory_type, approval_ref, signer_user_id, signer_name, signer_role, signature_data, remarks, status, signed_at)
                    VALUES (?, ?, 'Faculty Clearance Agreement', 'faculty', 'faculty', ?, ?, ?, 'faculty', ?, ?, 'Submitted', ?)
                    ON DUPLICATE KEY UPDATE
                        signature_data = VALUES(signature_data),
                        signed_at = VALUES(signed_at)");
                foreach ($existingReqSigs as $rSig) {
                    $cId = (int) $rSig['clearance_id'];
                    $fId = (int) $rSig['faculty_id'];
                    $fName = trim(($rSig['first_name'] ?? '') . ' ' . ($rSig['last_name'] ?? '')) ?: 'Faculty Member';
                    $fUserId = !empty($rSig['user_id']) ? (int) $rSig['user_id'] : null;
                    $ref = 'FAC-' . date('Y') . '-' . sprintf('%05d', $cId);
                    $sAt = $rSig['form_submitted_at'] ?? ($rSig['submitted_at'] ?? date('Y-m-d H:i:s'));
                    $syncReqStmt->execute([
                        $cId,
                        $fId,
                        $ref,
                        $fUserId,
                        $fName,
                        $rSig['signature_data'],
                        $rSig['faculty_declaration'] ?? 'Faculty Declaration and Agreement',
                        $sAt
                    ]);
                }
            }
        } catch (Throwable $e) { /* ignore */
        }

        // 3. Table for persistent clearance audit trail / approval history
        $db->exec("CREATE TABLE IF NOT EXISTS `clearance_approval_history` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `clearance_id` int(10) unsigned NOT NULL,
            `requirement_id` int(10) unsigned DEFAULT NULL,
            `office` varchar(50) NOT NULL,
            `action` varchar(50) NOT NULL,
            `performed_by_id` int(10) unsigned DEFAULT NULL,
            `performed_by_name` varchar(150) NOT NULL,
            `performed_by_role` varchar(100) NOT NULL,
            `remarks` text NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_hist_clearance` (`clearance_id`),
            KEY `idx_hist_office` (`office`),
            KEY `idx_hist_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 3. Extend clearance_requests columns
        $reqCols = $db->query("SHOW COLUMNS FROM clearance_requests")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('clearance_no', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN clearance_no VARCHAR(50) NULL AFTER clearance_id");
            try {
                $db->exec("ALTER TABLE clearance_requests ADD UNIQUE KEY `uk_clearance_no` (`clearance_no`)");
            } catch (Throwable $e) {
            }
        }
        if (!in_array('dh_verified', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN dh_verified TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('dh_verified_at', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN dh_verified_at DATETIME NULL");
        }
        if (!in_array('dh_verified_by', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN dh_verified_by INT(10) UNSIGNED NULL");
        }
        if (!in_array('dh_remarks', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN dh_remarks TEXT NULL");
        }
        if (!in_array('faculty_declared', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN faculty_declared TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('faculty_declared_at', $reqCols, true)) {
            $db->exec("ALTER TABLE clearance_requests ADD COLUMN faculty_declared_at DATETIME NULL");
        }

        // 4. Backfill clearance_no for existing clearance_requests
        $noRows = $db->query("SELECT cr.clearance_id, at.academic_year FROM clearance_requests cr LEFT JOIN academic_terms at ON at.term_id = cr.term_id WHERE cr.clearance_no IS NULL OR cr.clearance_no = '' LIMIT 100")->fetchAll();
        if (!empty($noRows)) {
            $updateNo = $db->prepare("UPDATE clearance_requests SET clearance_no = ? WHERE clearance_id = ?");
            foreach ($noRows as $nr) {
                $year = !empty($nr['academic_year']) ? substr((string) $nr['academic_year'], 0, 4) : date('Y');
                $cNo = 'CLR-' . $year . '-' . str_pad((string) (int) $nr['clearance_id'], 5, '0', STR_PAD_LEFT);
                $updateNo->execute([$cNo, (int) $nr['clearance_id']]);
            }
        }

        // 5. Extend faculty_clearance_archives
        $archCols = $db->query("SHOW COLUMNS FROM faculty_clearance_archives")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('clearance_no', $archCols, true)) {
            $db->exec("ALTER TABLE faculty_clearance_archives ADD COLUMN clearance_no VARCHAR(50) NULL AFTER clearance_id");
        }
        if (!in_array('office_approvals_json', $archCols, true)) {
            $db->exec("ALTER TABLE faculty_clearance_archives ADD COLUMN office_approvals_json LONGTEXT NULL");
        }
        if (!in_array('approval_history_json', $archCols, true)) {
            $db->exec("ALTER TABLE faculty_clearance_archives ADD COLUMN approval_history_json LONGTEXT NULL");
        }
        if (!in_array('dh_verification_json', $archCols, true)) {
            $db->exec("ALTER TABLE faculty_clearance_archives ADD COLUMN dh_verification_json LONGTEXT NULL");
        }
        if (!in_array('declaration_json', $archCols, true)) {
            $db->exec("ALTER TABLE faculty_clearance_archives ADD COLUMN declaration_json LONGTEXT NULL");
        }
    } catch (Throwable $e) {
        error_log('Digital approval schema migration warning: ' . $e->getMessage());
    }
}

function facultyClearanceOfficeKey(string $name): string
{
    $n = strtolower(trim($name));
    if (str_contains($n, 'academic') || str_contains($n, 'registrar'))
        return 'academic';
    if (str_contains($n, 'library'))
        return 'library';
    if (str_contains($n, 'property') || str_contains($n, 'custodian'))
        return 'property';
    if (str_contains($n, 'finan') || str_contains($n, 'accounting'))
        return 'financial';
    if (str_contains($n, 'hr') || str_contains($n, 'human'))
        return 'hr';
    if (str_contains($n, 'department') || str_contains($n, 'head') || str_contains($n, 'dean'))
        return 'department';
    return 'other';
}

function facultyClearanceOfficePrefix(string $officeKey): string
{
    return match ($officeKey) {
        'academic' => 'ACAD',
        'library' => 'LIB',
        'property' => 'PROP',
        'financial' => 'FIN',
        'hr' => 'HR',
        'department' => 'DH',
        default => 'CLR',
    };
}

function facultyClearanceGenerateApprovalRef(string $officeName, int $clearanceId, int $approvalId = 0): string
{
    $key = facultyClearanceOfficeKey($officeName);
    $prefix = facultyClearanceOfficePrefix($key);
    $year = date('Y');
    $seq = $approvalId > 0 ? $approvalId : $clearanceId;
    return sprintf('%s-%s-%05d', $prefix, $year, $seq);
}

function facultyClearanceGenerateNo(int $clearanceId, ?string $academicYear = null): string
{
    $year = !empty($academicYear) ? substr((string) $academicYear, 0, 4) : date('Y');
    return sprintf('CLR-%s-%05d', $year, $clearanceId);
}

function facultyClearanceRecordOfficeApproval(
    PDO $db,
    int $clearanceId,
    int $facultyId,
    string $office,
    int $approverUserId,
    string $approverName,
    string $approverRole,
    string $status = 'Approved',
    ?string $remarks = null,
    ?string $approvalRef = null,
    ?string $signatureData = null
): array {
    facultyClearanceEnsureDigitalApprovalSchema($db);

    if (empty($approvalRef)) {
        $approvalRef = facultyClearanceGenerateApprovalRef($office, $clearanceId);
    }

    $stmt = $db->prepare("INSERT INTO clearance_office_approvals
        (clearance_id, faculty_id, office, approval_ref, approver_user_id, approver_name, approver_role, status, remarks, signature_data, approved_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            approval_ref = VALUES(approval_ref),
            approver_user_id = VALUES(approver_user_id),
            approver_name = VALUES(approver_name),
            approver_role = VALUES(approver_role),
            status = VALUES(status),
            remarks = VALUES(remarks),
            signature_data = VALUES(signature_data),
            approved_at = NOW()");
    $stmt->execute([
        $clearanceId,
        $facultyId,
        $office,
        $approvalRef,
        $approverUserId,
        $approverName,
        $approverRole,
        $status,
        $remarks,
        $signatureData,
    ]);

    // Also record into dedicated clearance_signatures table if signature is provided
    if (!empty($signatureData)) {
        $officeKey = facultyClearanceOfficeKey($office);
        $sigType = ($officeKey === 'department' || in_array(strtolower($approverRole), ['dept_head', 'department_head'], true))
            ? 'department_head'
            : 'office_signatory';
        facultyClearanceRecordSignature(
            $db,
            $clearanceId,
            $facultyId,
            $office,
            $approverUserId,
            $approverName,
            $approverRole,
            $signatureData,
            $sigType,
            $approvalRef,
            $status,
            $remarks
        );
    }

    // Fetch the updated row
    $fetch = $db->prepare("SELECT * FROM clearance_office_approvals WHERE clearance_id = ? AND office = ? LIMIT 1");
    $fetch->execute([$clearanceId, $office]);
    return $fetch->fetch(PDO::FETCH_ASSOC) ?: [
        'clearance_id' => $clearanceId,
        'office' => $office,
        'approval_ref' => $approvalRef,
        'approver_name' => $approverName,
        'approver_role' => $approverRole,
        'status' => $status,
        'approved_at' => date('Y-m-d H:i:s'),
    ];
}

function facultyClearanceRecordSignature(
    PDO $db,
    int $clearanceId,
    int $facultyId,
    string $office,
    int $signerUserId,
    string $signerName,
    string $signerRole,
    string $signatureData,
    string $signatoryType = 'office_signatory',
    ?string $approvalRef = null,
    string $status = 'Signed',
    ?string $remarks = null,
    ?string $ipAddress = null,
    ?string $userAgent = null
): array {
    facultyClearanceEnsureDigitalApprovalSchema($db);

    $officeKey = facultyClearanceOfficeKey($office);
    if (empty($approvalRef)) {
        $approvalRef = facultyClearanceGenerateApprovalRef($office, $clearanceId);
    }
    if ($ipAddress === null && !empty($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = substr((string) $_SERVER['REMOTE_ADDR'], 0, 45);
    }
    if ($userAgent === null && !empty($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500);
    }

    try {
        $stmt = $db->prepare("INSERT INTO clearance_signatures
            (clearance_id, faculty_id, office, office_key, signatory_type, approval_ref, signer_user_id, signer_name, signer_role, signature_data, remarks, status, ip_address, user_agent, signed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                office = VALUES(office),
                signatory_type = VALUES(signatory_type),
                approval_ref = VALUES(approval_ref),
                signer_user_id = VALUES(signer_user_id),
                signer_name = VALUES(signer_name),
                signer_role = VALUES(signer_role),
                signature_data = VALUES(signature_data),
                remarks = VALUES(remarks),
                status = VALUES(status),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                signed_at = NOW(),
                updated_at = NOW()");
        $stmt->execute([
            $clearanceId,
            $facultyId,
            $office,
            $officeKey,
            $signatoryType,
            $approvalRef,
            $signerUserId > 0 ? $signerUserId : null,
            $signerName,
            $signerRole,
            $signatureData,
            $remarks,
            $status,
            $ipAddress,
            $userAgent
        ]);

        $fetch = $db->prepare("SELECT * FROM clearance_signatures WHERE clearance_id = ? AND office_key = ? LIMIT 1");
        $fetch->execute([$clearanceId, $officeKey]);
        return $fetch->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Failed to record clearance signature: ' . $e->getMessage());
        return [];
    }
}

function facultyClearanceLogHistory(
    PDO $db,
    int $clearanceId,
    ?int $requirementId,
    string $office,
    string $action,
    ?int $userId,
    string $userName,
    string $userRole,
    ?string $remarks = null
): void {
    facultyClearanceEnsureDigitalApprovalSchema($db);
    try {
        $stmt = $db->prepare("INSERT INTO clearance_approval_history
            (clearance_id, requirement_id, office, action, performed_by_id, performed_by_name, performed_by_role, remarks, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $clearanceId,
            $requirementId,
            $office,
            $action,
            $userId,
            $userName,
            $userRole,
            $remarks,
        ]);
    } catch (Throwable $e) {
        error_log('Clearance history log error: ' . $e->getMessage());
    }
}

function facultyClearanceGetOfficeApprovals(PDO $db, int $clearanceId): array
{
    facultyClearanceEnsureDigitalApprovalSchema($db);
    try {
        $stmt = $db->prepare("SELECT * FROM clearance_office_approvals WHERE clearance_id = ? ORDER BY approved_at ASC");
        $stmt->execute([$clearanceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $keyed = [];
        foreach ($rows as $r) {
            $keyed[$r['office']] = $r;
            $officeKey = facultyClearanceOfficeKey($r['office']);
            $keyed[$officeKey] = $r;
        }
        return $keyed;
    } catch (Throwable $e) {
        return [];
    }
}

function facultyClearanceGetApprovalHistory(PDO $db, int $clearanceId): array
{
    facultyClearanceEnsureDigitalApprovalSchema($db);
    try {
        $stmt = $db->prepare("SELECT * FROM clearance_approval_history WHERE clearance_id = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([$clearanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
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

    $clearanceId = (int) $request['clearance_id'];
    $offices = facultyClearanceOffices($db);
    $allowedNames = facultyClearanceRequirementNames();

    // Ensure all 6 active clearance offices have rows in clearance_items
    $insertItem = $db->prepare('INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, \'Missing\')');
    foreach ($offices as $office) {
        $insertItem->execute([$clearanceId, (int) $office['clearance_office_id']]);
    }

    $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
    $items = $db->prepare('SELECT ci.*, co.name, co.sequence_order FROM clearance_items ci JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ') ORDER BY co.sequence_order, co.clearance_office_id');
    $items->execute(array_merge([$clearanceId], $allowedNames));
    $request['items'] = $items->fetchAll();
    $total = count($request['items']);
    $approved = count(array_filter($request['items'], static fn(array $item): bool => in_array($item['status'] ?? '', ['Cleared', 'Approved'], true)));
    $submitted = count(array_filter($request['items'], static fn(array $item): bool => !empty($item['file_path'])));
    $request['total_items'] = $total > 0 ? $total : count($allowedNames);
    $request['approved_items'] = $approved;
    $request['submitted_items'] = $submitted;
    $request['progress'] = $total > 0 ? (int) round(($approved / $total) * 100) : 0;

    // Ensure Clearance No
    if (empty($request['clearance_no'])) {
        $cNo = facultyClearanceGenerateNo($clearanceId, $request['academic_year'] ?? null);
        try {
            $db->prepare('UPDATE clearance_requests SET clearance_no = ? WHERE clearance_id = ?')->execute([$cNo, $clearanceId]);
            $request['clearance_no'] = $cNo;
        } catch (Throwable $e) {
        }
    }

    // Attach digital approval records and persistent history
    $request['office_approvals'] = facultyClearanceGetOfficeApprovals($db, $clearanceId);
    $request['approval_history'] = facultyClearanceGetApprovalHistory($db, $clearanceId);

    return $request;
}

function facultyClearanceSequentialStages(): array
{
    return [
        'Academic Clearance',
        'Library Clearance',
        'Financial Clearance',
        'Property Clearance',
        'HR Clearance',
        'Department Clearance',
    ];
}

function facultyClearanceStageProgression(?array $request): array
{
    $order = facultyClearanceSequentialStages();
    $stages = [];

    $itemByName = [];
    if (!empty($request['items'])) {
        foreach ($request['items'] as $it) {
            $name = (string) ($it['name'] ?? ($it['requirement_name'] ?? ''));
            if ($name !== '') {
                $itemByName[$name] = $it;
            }
        }
    }

    $formSubmitted = !empty($request['form_submitted']);
    $formStatus = (string) ($request['form_status'] ?? '');
    $formApproved = ($formSubmitted && $formStatus === 'Approved');

    // Step 1 (Academic Clearance) unlocks only after Clearance Form is approved
    $prevCleared = $formApproved;
    $prevOfficeName = 'Clearance Form';

    foreach ($order as $idx => $name) {
        $item = $itemByName[$name] ?? null;
        $status = (string) ($item['status'] ?? 'Missing');
        $statusNorm = strtolower(trim($status));
        $hasFile = !empty($item['file_path']) || !empty($item['file_name']) || !empty($item['original_name']);
        $isCleared = in_array($statusNorm, ['cleared', 'approved'], true);
        $hasDeficiency = in_array($statusNorm, ['denied', 'hold', 'rejected', 'with deficiency', 'with_deficiency', 'on hold', 'on_hold'], true);

        if (!$hasDeficiency && !empty($item['remarks'])) {
            if (preg_match('/<!--SCOPE_STATE:(.*?)-->/s', (string) $item['remarks'], $mScope)) {
                $sc = json_decode($mScope[1], true);
                if (!empty($sc['failed'])) {
                    $hasDeficiency = true;
                }
            }
        }

        $isUnlocked = $prevCleared;
        $lockReason = '';
        if (!$isUnlocked) {
            $state = 'locked';
            if (!$formApproved) {
                $lockReason = 'Awaiting Department Head approval of Clearance Form to unlock this step.';
            } else {
                $lockReason = 'Complete the previous clearance to unlock this step.';
            }
        } else {
            if ($isCleared) {
                $state = 'cleared';
            } elseif ($hasDeficiency) {
                $state = 'with_issue';
            } elseif ($hasFile || in_array($statusNorm, ['pending review', 'pending verification', 'under verification', 'submitted'], true)) {
                $state = 'in_progress';
            } else {
                $state = 'ready';
            }
        }

        $stages[$name] = [
            'step' => $idx + 1,
            'name' => $name,
            'office_id' => (int) ($item['clearance_office_id'] ?? ($item['office_id'] ?? 0)),
            'item_id' => (int) ($item['clearance_item_id'] ?? ($item['id'] ?? 0)),
            'is_unlocked' => $isUnlocked,
            'state' => $state,
            'lock_reason' => $lockReason,
            'status' => $status,
            'is_cleared' => $isCleared,
            'has_deficiency' => $hasDeficiency,
            'has_file' => $hasFile,
            'file_name' => $item['file_name'] ?? ($item['original_name'] ?? null),
            'remarks' => $item['remarks'] ?? null,
            'cleared_at' => $item['cleared_at'] ?? null,
        ];

        // Next stage unlocks only if current stage is Cleared
        $prevCleared = $isCleared;
        $prevOfficeName = $name;
    }

    return $stages;
}

function facultyClearanceIsOfficeUnlocked(?array $request, string|int $officeIdentifier): bool
{
    $progression = facultyClearanceStageProgression($request);
    if (is_string($officeIdentifier) && isset($progression[$officeIdentifier])) {
        return (bool) $progression[$officeIdentifier]['is_unlocked'];
    }
    foreach ($progression as $st) {
        if ($st['office_id'] > 0 && $st['office_id'] === (int) $officeIdentifier) {
            return (bool) $st['is_unlocked'];
        }
        if (strcasecmp($st['name'], (string) $officeIdentifier) === 0) {
            return (bool) $st['is_unlocked'];
        }
    }
    return false;
}

function facultyClearanceStatus(?array $request): string
{
    if (!$request || empty($request['items'])) {
        return 'Not Submitted';
    }

    $overallStatus = (string) ($request['overall_status'] ?? '');
    if ($overallStatus === 'Completed') {
        return 'Completed';
    }

    $items = $request['items'] ?? [];
    $totalCount = count($items);
    if ($totalCount === 0) {
        return 'Not Submitted';
    }

    $statuses = array_map(static fn($it) => $it['status'] ?? '', $items);
    $hasDeficiency = in_array('Denied', $statuses, true) || in_array('Hold', $statuses, true) || in_array('With Deficiency', $statuses, true) || in_array('On Hold', $statuses, true);
    $clearedCount = count(array_filter($statuses, static fn($s) => in_array($s, ['Cleared', 'Approved'], true)));
    $hasUploads = count(array_filter($items, static fn($it) => !empty($it['file_path']) || in_array($it['status'] ?? '', ['Pending Review', 'Under Verification', 'Cleared', 'Approved'], true))) > 0;
    $formSubmitted = !empty($request['form_submitted']);

    // 1. All 6 office requirements cleared => Clearance completed!
    if ($clearedCount >= 6 && $clearedCount >= $totalCount) {
        return 'Completed';
    }

    // 2. Deficiency if any office flagged issue
    if ($hasDeficiency) {
        return 'With Deficiency';
    }

    $formStatus = (string) ($request['form_status'] ?? '');

    // 3. If Department Head has rejected/returned the agreement form
    if ($formStatus === 'Rejected') {
        return 'Action Required';
    }

    // 4. If agreement form is approved by Department Head, clearance is under verification
    if ($formStatus === 'Approved') {
        if ($clearedCount >= 5) {
            return 'For Final Approval';
        }
        return 'Under Verification';
    }

    // 5. Initial submission is reviewed first by Department Head before proceeding
    if ($formSubmitted || $overallStatus === 'For Department Head Approval' || $formStatus === 'Pending Review') {
        return 'For Department Head Approval';
    }

    // 6. If any uploads exist
    if ($hasUploads) {
        return 'Under Verification';
    }

    return 'Not Submitted';
}

function facultyClearanceItemLabel(array $item): string
{
    $status = (string) ($item['status'] ?? '');
    if ($status === 'Cleared' || $status === 'Approved') {
        return 'Cleared';
    }
    if ($status === 'Denied' || $status === 'Hold' || $status === 'With Deficiency') {
        return 'With Deficiency';
    }
    if ($status === 'On Hold') {
        return 'On Hold';
    }
    if ($status === 'Pending Review' || $status === 'Pending Verification' || $status === 'Under Verification') {
        return 'Under Verification';
    }
    if ($status === 'Missing' || $status === '') {
        return 'Pending Verification';
    }
    return $status;
}

function facultyClearanceCanResubmit(array $item): bool
{
    return in_array($item['status'] ?? '', ['Hold', 'Denied', 'On Hold', 'With Deficiency'], true);
}

function facultyIntentCanUploadAgain(array $item): bool
{
    return in_array($item['status'] ?? '', ['Cleared', 'Hold', 'Denied', 'On Hold', 'With Deficiency'], true);
}

function facultyClearanceJson(?array $request): array
{
    return [
        'clearance_id' => $request ? (int) $request['clearance_id'] : null,
        'clearance_no' => $request['clearance_no'] ?? null,
        'intent_type' => $request['intent_type'] ?? 'renewal',
        'form_submitted' => !empty($request['form_submitted']),
        'form_submitted_at' => $request['form_submitted_at'] ?? null,
        'form_status' => $request['form_status'] ?? (!empty($request['form_submitted']) ? 'Pending Review' : 'Not Submitted'),
        'form_approved_at' => $request['form_approved_at'] ?? null,
        'form_approved_by' => $request['form_approved_by'] ?? null,
        'form_remarks' => $request['form_remarks'] ?? null,
        'faculty_declaration' => $request['faculty_declaration'] ?? null,
        'signature_data' => $request['signature_data'] ?? null,
        'dh_verified' => !empty($request['dh_verified']),
        'dh_verified_at' => $request['dh_verified_at'] ?? null,
        'dh_verified_by' => $request['dh_verified_by'] ?? null,
        'dh_remarks' => $request['dh_remarks'] ?? null,
        'faculty_declared' => !empty($request['faculty_declared']),
        'faculty_declared_at' => $request['faculty_declared_at'] ?? null,
        'office_approvals' => $request['office_approvals'] ?? [],
        'approval_history' => $request['approval_history'] ?? [],
        'overall_status' => $request['overall_status'] ?? null,
        'status' => facultyClearanceStatus($request),
        'stages' => facultyClearanceStageProgression($request),
        'progress' => (int) ($request['progress'] ?? 0),
        'submitted_items' => (int) ($request['submitted_items'] ?? 0),
        'approved_items' => (int) ($request['approved_items'] ?? 0),
        'total_items' => (int) ($request['total_items'] ?? 6),
        'submitted_at' => $request['submitted_at'] ?? null,
        'updated_at' => $request['updated_at'] ?? null,
        'items' => array_values(array_map(static function (array $item): array {
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
        }, $request['items'] ?? [])),
    ];
}

function facultyClearanceRecalculate(PDO $db, int $clearanceId): void
{
    $offices = facultyClearanceOffices($db);
    $allowedNames = facultyClearanceRequirementNames();

    // Ensure all active clearance offices have rows in clearance_items
    $insertItem = $db->prepare('INSERT IGNORE INTO clearance_items (clearance_id, clearance_office_id, status) VALUES (?, ?, \'Missing\')');
    foreach ($offices as $office) {
        $insertItem->execute([$clearanceId, (int) $office['clearance_office_id']]);
    }

    // Fetch clearance request details including verification & declaration flags
    $reqStmt = $db->prepare('SELECT * FROM clearance_requests WHERE clearance_id = ? LIMIT 1');
    $reqStmt->execute([$clearanceId]);
    $req = $reqStmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        return;
    }

    // Fetch all items
    $placeholders = implode(',', array_fill(0, count($allowedNames), '?'));
    $stmt = $db->prepare('SELECT ci.status, co.name FROM clearance_items ci JOIN clearance_offices co ON co.clearance_office_id = ci.clearance_office_id WHERE ci.clearance_id = ? AND co.name IN (' . $placeholders . ')');
    $stmt->execute(array_merge([$clearanceId], $allowedNames));
    $itemRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch office approvals
    $officeApprovals = facultyClearanceGetOfficeApprovals($db, $clearanceId);

    // All 6 offices in sequential order
    $sixOfficeNames = facultyClearanceSequentialStages();

    $hasActionRequired = false;
    $hasDeficiency = false;
    $approvedCount = 0;

    foreach ($itemRows as $row) {
        $st = $row['status'] ?? '';
        $name = $row['name'] ?? '';
        $officeKey = facultyClearanceOfficeKey($name);

        if ($st === 'Action Required') {
            $hasActionRequired = true;
        } elseif (in_array($st, ['Hold', 'Denied', 'With Deficiency', 'On Hold'], true)) {
            $hasDeficiency = true;
        }

        // Check if office is approved either by item status or by digital approval sign-off
        $isOfficeApproved = in_array($st, ['Cleared', 'Approved'], true) || (!empty($officeApprovals[$officeKey]) && $officeApprovals[$officeKey]['status'] === 'Approved');

        if (in_array($name, $sixOfficeNames, true) && $isOfficeApproved) {
            $approvedCount++;
        }
    }

    $allSixOfficesApproved = ($approvedCount >= count($sixOfficeNames));

    if ($hasActionRequired) {
        $overall = 'Action Required';
    } elseif ($hasDeficiency) {
        $overall = 'With Deficiency';
    } elseif ($allSixOfficesApproved) {
        $overall = 'Completed';
    } elseif ($approvedCount > 0) {
        $overall = 'Under Review';
    } else {
        $overall = 'In Progress';
    }

    $update = $db->prepare('UPDATE clearance_requests SET overall_status = ? WHERE clearance_id = ?');
    $update->execute([$overall, $clearanceId]);

    if ($overall === 'Completed' || $overall === 'Cleared') {
        facultyClearanceArchiveRecord($db, $clearanceId);
    }
}


function facultyClearanceArchiveRecord(PDO $db, int $clearanceId): void
{
    try {
        facultyClearanceEnsureDigitalApprovalSchema($db);

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

        $allowedNames = facultyClearanceRequirementNames();
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

        $officeApprovals = facultyClearanceGetOfficeApprovals($db, $clearanceId);
        $approvalHistory = facultyClearanceGetApprovalHistory($db, $clearanceId);
        $dhVerification = [
            'verified' => !empty($rec['dh_verified']),
            'verified_at' => $rec['dh_verified_at'] ?? null,
            'verified_by' => $rec['dh_verified_by'] ?? null,
            'remarks' => $rec['dh_remarks'] ?? null,
        ];
        $declarationData = [
            'declared' => !empty($rec['faculty_declared']) || !empty($rec['signature_data']),
            'declared_at' => $rec['faculty_declared_at'] ?? null,
            'declaration_text' => $rec['faculty_declaration'] ?? null,
            'signature_data' => $rec['signature_data'] ?? null,
        ];

        $officeApprovalsJson = json_encode($officeApprovals, JSON_UNESCAPED_SLASHES);
        $approvalHistoryJson = json_encode($approvalHistory, JSON_UNESCAPED_SLASHES);
        $dhVerificationJson = json_encode($dhVerification, JSON_UNESCAPED_SLASHES);
        $declarationJson = json_encode($declarationData, JSON_UNESCAPED_SLASHES);
        $clearanceNo = $rec['clearance_no'] ?? facultyClearanceGenerateNo($clearanceId, $rec['academic_year'] ?? null);

        $insert = $db->prepare('INSERT INTO faculty_clearance_archives
            (clearance_id, clearance_no, faculty_id, term_id, profile_id, faculty_no, first_name, middle_name, last_name, suffix,
             email, phone, designated_department, position, academic_rank, tier, employment_status, contractual_end,
             academic_year, semester, intent_type, overall_status, items_json, office_approvals_json, approval_history_json,
             dh_verification_json, declaration_json, submitted_at, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $insert->execute([
            $clearanceId,
            $clearanceNo,
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
            'Completed',                              // overall_status
            $itemsJson,                             // items_json
            $officeApprovalsJson,
            $approvalHistoryJson,
            $dhVerificationJson,
            $declarationJson,
            $rec['submitted_at'] ?? date('Y-m-d H:i:s'), // submitted_at
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