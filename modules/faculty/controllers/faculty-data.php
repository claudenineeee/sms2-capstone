<?php
/**
 * SMS 2 - Faculty Helper Functions & Scoped Queries
 * Path: modules/faculty/controllers/faculty-data.php
 *
 * DATABASE LAYOUT:
 *   Local (XAMPP): sms2_db (auth) + faculty_db (business)
 *   HostForge:     single database (all tables combined)
 *   We use getenv() to handle both seamlessly.
 */

if (!function_exists('facultyDb')) {
    /**
     * Returns a PDO connection to the database.
     * Reads from HostForge environment variables if available,
     * otherwise falls back to local XAMPP settings.
     */
    function facultyDb(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        // HostForge environment variables with XAMPP fallbacks
        $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
        $user = getenv('DB_USERNAME') ?: (defined('DB_USER') ? DB_USER : 'root');
        $pass = getenv('DB_PASSWORD') ?: (defined('DB_PASS') ? DB_PASS : '');
        $name = getenv('DB_DATABASE') ?: (defined('FACULTY_DB_NAME') ? FACULTY_DB_NAME : 'faculty_db');
        $port = (int)(getenv('DB_PORT') ?: 3306);

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log('[facultyDb] connect failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Canonical Academic Rank -> Tier options.
 */
if (!function_exists('getAcademicRankTiers')) {
    function getAcademicRankTiers(): array {
        return [
            'Instructor' => [
                'Instructor I',
                'Instructor II',
                'Instructor III',
            ],
            'Assistant Professor' => [
                'Assistant Professor I',
                'Assistant Professor II',
                'Assistant Professor III',
                'Assistant Professor IV',
            ],
            'Associate Professor' => [
                'Associate Professor I',
                'Associate Professor II',
                'Associate Professor III',
                'Associate Professor IV',
                'Associate Professor V',
            ],
            'Professor' => [
                'Professor I',
                'Professor II',
                'Professor III',
                'Professor IV',
                'Professor V',
                'Professor VI',
            ],
        ];
    }
}

/**
 * Validate that $tier is one of the allowed tiers for $academicRank.
 */
if (!function_exists('isValidAcademicRankTier')) {
    function isValidAcademicRankTier(string $academicRank, string $tier): bool {
        if ($academicRank === '' && $tier === '') {
            return true;
        }
        $map = getAcademicRankTiers();
        if (!isset($map[$academicRank])) {
            return false;
        }
        if ($tier === '') {
            return true;
        }
        return in_array($tier, $map[$academicRank], true);
    }
}

/**
 * Retrieves directory list, scoped by the logged-in user's role.
 */
if (!function_exists('getScopedFacultyList')) {
    function getScopedFacultyList(): array {
        $pdo = function_exists('facultyDb') ? facultyDb() : null;
        if (!$pdo) {
            return [];
        }

        $userId  = (int) ($_SESSION['user_id'] ?? 0);
        $roleKey = $_SESSION['user_role_key'] ?? '';
        $sessionEmail = trim((string) ($_SESSION['user_email'] ?? $_SESSION['email'] ?? ''));

        try {
            if (in_array($roleKey, ['department_head', 'dept_head', 'secretary'], true) && $userId) {

                /* ---- (a) resolve dept by user_id -------------------- */
                $myDept = '';
                $stmt = $pdo->prepare("
                    SELECT designated_department
                    FROM faculty_profiles
                    WHERE user_id = :uid
                    LIMIT 1
                ");
                $stmt->execute([':uid' => $userId]);
                $val = $stmt->fetchColumn();
                if ($val !== false && $val !== null && trim((string) $val) !== '') {
                    $myDept = trim((string) $val);
                }

                /* ---- (b) resolve by email + repair user_id --------- */
                if ($myDept === '' && $sessionEmail !== '') {
                    $stmt = $pdo->prepare("
                        SELECT id, user_id, designated_department
                        FROM faculty_profiles
                        WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email))
                        LIMIT 1
                    ");
                    $stmt->execute([':email' => $sessionEmail]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['designated_department'])) {
                        $myDept = trim((string) $row['designated_department']);

                        if ((int) ($row['user_id'] ?? 0) !== $userId) {
                            try {
                                $pdo->prepare("UPDATE faculty_profiles SET user_id = :uid WHERE id = :id")
                                    ->execute([':uid' => $userId, ':id' => (int) $row['id']]);
                                error_log("[getScopedFacultyList] repaired user_id for profile {$row['id']} -> {$userId}");
                            } catch (Throwable $e) {
                                error_log('[getScopedFacultyList][repair] ' . $e->getMessage());
                            }
                        }
                    }
                }

                /* ---- (c) bridge via faculty.external_user_id ------- */
                if ($myDept === '' && $userId > 0) {
                    $stmt = $pdo->prepare("
                        SELECT fp.designated_department
                        FROM faculty f
                        INNER JOIN faculty_profiles fp ON fp.email = f.email
                        WHERE f.external_user_id = :uid
                        LIMIT 1
                    ");
                    $stmt->execute([':uid' => (string) $userId]);
                    $val = $stmt->fetchColumn();
                    if ($val !== false && $val !== null && trim((string) $val) !== '') {
                        $myDept = trim((string) $val);
                    }
                }

                if ($myDept === '') {
                    error_log('[getScopedFacultyList] no department resolved for user_id='
                        . $userId . ' email=' . $sessionEmail);
                    return [];
                }

                /* ---- match code OR full name ------------------------ */
                $acceptedDepts = [strtolower(trim($myDept))];
                $deptLookup = $pdo->prepare("
                    SELECT code, name
                    FROM departments
                    WHERE LOWER(TRIM(code)) = LOWER(TRIM(:d))
                       OR LOWER(TRIM(name)) = LOWER(TRIM(:d))
                    LIMIT 1
                ");
                $deptLookup->execute([':d' => $myDept]);
                $dRow = $deptLookup->fetch(PDO::FETCH_ASSOC);
                if ($dRow) {
                    if (!empty($dRow['code'])) $acceptedDepts[] = strtolower(trim($dRow['code']));
                    if (!empty($dRow['name'])) $acceptedDepts[] = strtolower(trim($dRow['name']));
                }
                $acceptedDepts = array_values(array_unique(array_filter($acceptedDepts)));

                $placeholders = implode(',', array_fill(0, count($acceptedDepts), '?'));
                
                // REMOVED: sms2_db. prefix from users
                $sql = "SELECT fp.*, u.username, u.status AS account_status
                        FROM faculty_profiles fp
                        LEFT JOIN users u ON u.id = fp.user_id
                        WHERE LOWER(TRIM(fp.designated_department)) IN ($placeholders)
                        ORDER BY fp.id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($acceptedDepts);
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            /* ---- dean: multi-department view --------------------- */
            if ($roleKey === 'dean' && $userId) {
                $myProfileStmt = $pdo->prepare("
                    SELECT id FROM faculty_profiles WHERE user_id = :uid LIMIT 1
                ");
                $myProfileStmt->execute([':uid' => $userId]);
                $myProfileId = $myProfileStmt->fetchColumn();

                if (!$myProfileId) {
                    return [];
                }

                $deptCodesStmt = $pdo->prepare("
                    SELECT d.code
                    FROM faculty_profile_department_assignments a
                    JOIN departments d ON d.department_id = a.department_id
                    WHERE a.faculty_profile_id = :pid
                ");
                $deptCodesStmt->execute([':pid' => $myProfileId]);
                $deptCodes = $deptCodesStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($deptCodes)) {
                    return [];
                }

                $placeholders = implode(',', array_fill(0, count($deptCodes), '?'));
                
                // REMOVED: sms2_db. prefix from users
                $sql = "SELECT fp.*, u.username, u.status AS account_status
                        FROM faculty_profiles fp
                        LEFT JOIN users u ON u.id = fp.user_id
                        WHERE fp.designated_department IN ($placeholders)
                          AND fp.user_id != ?
                        ORDER BY fp.id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([...$deptCodes, $userId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            /* ---- default: unrestricted --------------------------- */
            // REMOVED: sms2_db. prefix from users
            $sql = "SELECT fp.*, u.username, u.status AS account_status
                    FROM faculty_profiles fp
                    LEFT JOIN users u ON u.id = fp.user_id
                    ORDER BY fp.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log('getScopedFacultyList error: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Fetch profile for currently logged-in user (self-healing).
 */
if (!function_exists('getMyFacultyProfile')) {
    function getMyFacultyProfile(): ?array {
        $pdo = function_exists('facultyDb') ? facultyDb() : null;

        $userId = 0;
        if (!empty($_SESSION['user_id']))         $userId = (int) $_SESSION['user_id'];
        elseif (!empty($_SESSION['id']))          $userId = (int) $_SESSION['id'];
        elseif (!empty($_SESSION['user']['id']))  $userId = (int) $_SESSION['user']['id'];

        $email = '';
        foreach (['user_email', 'email'] as $k) {
            if (!empty($_SESSION[$k])) { $email = trim((string) $_SESSION[$k]); break; }
        }
        if ($email === '' && !empty($_SESSION['user']['email'])) {
            $email = trim((string) $_SESSION['user']['email']);
        }

        if (!$pdo || ($userId <= 0 && $email === '')) {
            return null;
        }

        // (a) by user_id
        if ($userId > 0) {
            $stmt = $pdo->prepare("
                SELECT fp.*, u.username, u.status AS account_status
                FROM faculty_profiles fp
                LEFT JOIN users u ON u.id = fp.user_id
                WHERE fp.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }

        // (b) by email + repair user_id
        if ($email !== '') {
            $stmt = $pdo->prepare("
                SELECT fp.*, u.username, u.status AS account_status
                FROM faculty_profiles fp
                LEFT JOIN users u ON u.id = fp.user_id
                WHERE fp.email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($userId > 0 && (int) ($row['user_id'] ?? 0) !== $userId) {
                    try {
                        $pdo->prepare("UPDATE faculty_profiles SET user_id = :uid WHERE id = :id")
                            ->execute([':uid' => $userId, ':id' => (int) $row['id']]);
                        $row['user_id'] = $userId;
                        error_log("[getMyFacultyProfile] repaired user_id link for profile {$row['id']} -> {$userId}");
                    } catch (Throwable $e) {
                        error_log('[getMyFacultyProfile][repair] ' . $e->getMessage());
                    }
                }
                return $row;
            }
        }

        // (c) via faculty.external_user_id
        if ($userId > 0) {
            $stmt = $pdo->prepare("
                SELECT fp.*, u.username, u.status AS account_status
                FROM faculty f
                INNER JOIN faculty_profiles fp ON fp.email = f.email
                LEFT JOIN users u ON u.id = fp.user_id
                WHERE f.external_user_id = :uid
                LIMIT 1
            ");
            $stmt->execute([':uid' => (string) $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }

        return null;
    }
}

/**
 * Next faculty sequence number.
 */
if (!function_exists('getNextFacultySequenceNumber')) {
    function getNextFacultySequenceNumber(PDO $facPdo): int {
        $stmt = $facPdo->query("SELECT MAX(id) AS max_id FROM faculty_profiles");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int) ($row['max_id'] ?? 0)) + 1;
    }
}

/**
 * Auto-fill faculty_id / username.
 */
if (!function_exists('populateFacultyAccountFields')) {
    function populateFacultyAccountFields(array $profile, int $sequence): array {
        $year = date('Y');
        $seqFormatted = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        $profile['faculty_id'] = $profile['faculty_id']
            ?? "FAC-{$year}-{$seqFormatted}";
        $profile['username'] = $profile['username']
            ?? strtolower(($profile['first_name'][0] ?? 'f') . $profile['last_name'] . $seqFormatted);

        return $profile;
    }
}

/**
 * Default temp password policy.
 */
if (!function_exists('buildFacultyPassword')) {
    function buildFacultyPassword(string $lastName): string {
        $cleanLastName = ucfirst(strtolower(preg_replace('/[^a-zA-Z]/', '', $lastName)));
        return ($cleanLastName ?: 'Faculty') . '123!';
    }
}

/**
 * Insert account into users (AUTH DB) and return the new user_id.
 */
function insertFacultyUser(PDO $pdo, array $profile, string $rawPassword): int {
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    if (empty($profile['role_key'])) {
        $position = strtolower(trim($profile['position'] ?? ''));
        if (str_contains($position, 'dean')) {
            $roleKey = 'dean';
        } elseif (str_contains($position, 'department head')) {
            $roleKey = 'department_head';
        } elseif (str_contains($position, 'monitoring')) {
            $roleKey = 'monitoring_officer';
        } elseif (str_contains($position, 'secretary')) {
            $roleKey = 'secretary';
        } else {
            $roleKey = 'faculty';
        }
    } else {
        $roleKey = $profile['role_key'];
    }

    // REMOVED: sms2_db. prefix from users
    $sql = "INSERT INTO users
            (username, email, password_hash, role_key, status, created_at)
            VALUES
            (:username, :email, :password_hash, :role_key, :status, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username'      => $profile['email'],
        ':email'         => $profile['email'],
        ':password_hash' => $hashedPassword,
        ':role_key'      => $roleKey,
        ':status'        => $profile['account_status'] ?? 'pending_approval',
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Insert row into faculty_profiles (BUSINESS DB).
 */
if (!function_exists('insertFacultyProfile')) {
    function insertFacultyProfile(array $profile): int {
        $pdo = facultyDb();

        $sql = "INSERT INTO faculty_profiles (
                    user_id, faculty_id, first_name, middle_name, last_name, suffix,
                    sex, birthdate, age, phone, email, designated_department,
                    position, academic_rank, tier, hired_date, contractual_end, employment_status,
                    profile_status, request_status, created_at
                ) VALUES (
                    :user_id, :faculty_id, :first_name, :middle_name, :last_name, :suffix,
                    :sex, :birthdate, :age, :phone, :email, :designated_department,
                    :position, :academic_rank, :tier, :hired_date, :contractual_end, :employment_status,
                    :profile_status, :request_status, NOW()
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'               => $profile['user_id'] ?? null,
            ':faculty_id'            => $profile['faculty_id'] ?? null,
            ':first_name'            => $profile['first_name'],
            ':middle_name'           => $profile['middle_name'] ?? null,
            ':last_name'             => $profile['last_name'],
            ':suffix'                => $profile['suffix'] ?? null,
            ':sex'                   => $profile['sex'],
            ':birthdate'             => $profile['birthdate'],
            ':age'                   => $profile['age'] ?? 0,
            ':phone'                 => $profile['phone'] ?? null,
            ':email'                 => $profile['email'],
            ':designated_department' => $profile['designated_department'],
            ':position'              => $profile['position'],
            ':academic_rank'         => !empty($profile['academic_rank']) ? $profile['academic_rank'] : null,
            ':tier'                  => !empty($profile['tier']) ? $profile['tier'] : null,
            ':hired_date'            => $profile['hired_date'],
            ':contractual_end'       => !empty($profile['contractual_end']) ? $profile['contractual_end'] : null,
            ':employment_status'     => $profile['employment_status'],
            ':profile_status'        => $profile['profile_status'] ?? 'Active',
            ':request_status'        => $profile['request_status'] ?? 'approved',
        ]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('sendFacultyAccountEmail')) {
    function sendFacultyAccountEmail(
        string $email,
        string $facultyId,
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        string $sex
    ): bool {
        // Real implementation lives in includes/mail.php.
        return true;
    }
}

/**
 * All faculty profiles college-wide (Dean overview).
 */
if (!function_exists('loadFacultyProfiles')) {
    function loadFacultyProfiles(): array {
        $pdo = facultyDb();
        if (!$pdo) {
            return [];
        }

        // REMOVED: sms2_db. prefix from users
        $sql = "SELECT fp.*, u.username, u.status AS account_status
                FROM faculty_profiles fp
                LEFT JOIN users u ON fp.user_id = u.id
                ORDER BY fp.id DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('loadFacultyProfiles error: ' . $e->getMessage());
            return [];
        }
    }
}