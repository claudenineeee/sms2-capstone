<?php
/**
 * SMS 2 - Faculty Helper Functions & Scoped Queries
 * Path: modules/faculty/controllers/faculty-data.php
 */

if (!function_exists('facultyDb')) {
    function facultyDb(): PDO {
        return db(); // Ensure this returns your PDO connection instance
    }
}

/**
 * Retrieves directory list with LEFT JOIN to include records without users or with NULL user_id.
 */
if (!function_exists('getScopedFacultyList')) {
    function getScopedFacultyList(): array {
        $pdo = function_exists('facultyDb') ? facultyDb() : (function_exists('db') ? db() : null);
        if (!$pdo) {
            return [];
        }

        // Target faculty_profiles and LEFT JOIN users so missing/NULL user_id records aren't filtered out
        $sql = "SELECT fp.*, u.username, u.status AS account_status 
                FROM faculty_db.faculty_profiles fp
                LEFT JOIN sms2_db.users u ON fp.user_id = u.id
                ORDER BY fp.id DESC";

        try {
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
 * Fetch profile for currently logged-in user.
 */
if (!function_exists('getMyFacultyProfile')) {
    function getMyFacultyProfile(): ?array {
        $pdo = function_exists('facultyDb') ? facultyDb() : (function_exists('db') ? db() : null);
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;

        if (!$pdo || $userId <= 0) {
            return null;
        }

        $sql = "SELECT fp.*, u.username, u.status AS account_status 
                FROM faculty_db.faculty_profiles fp
                LEFT JOIN sms2_db.users u ON fp.user_id = u.id
                WHERE fp.user_id = :user_id 
                LIMIT 1";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('getMyFacultyProfile error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getNextFacultySequenceNumber')) {
    function getNextFacultySequenceNumber(PDO $facPdo): int {
        $stmt = $facPdo->query("SELECT MAX(id) AS max_id FROM faculty_db.faculty_profiles");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int) ($row['max_id'] ?? 0)) + 1;
    }
}

if (!function_exists('populateFacultyAccountFields')) {
    function populateFacultyAccountFields(array $profile, int $sequence): array {
        $year = date('Y');
        $seqFormatted = str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
        
        $profile['faculty_id'] = $profile['faculty_id'] ?? "FAC-{$year}-{$seqFormatted}";
        $profile['username']   = $profile['username'] ?? strtolower(($profile['first_name'][0] ?? 'f') . $profile['last_name'] . $seqFormatted);
        
        return $profile;
    }
}

if (!function_exists('buildFacultyPassword')) {
    function buildFacultyPassword(string $lastName): string {
        $cleanLastName = ucfirst(strtolower(preg_replace('/[^a-zA-Z]/', '', $lastName)));
        return ($cleanLastName ?: 'Faculty') . '123!';
    }
}

/**
 * Inserts account into sms2_db.users and RETURNS the generated user_id.
 */
function insertFacultyUser(PDO $pdo, array $profile, string $rawPassword): int {
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    
    // Automatically map position to role_key if role_key isn't set explicitly
    if (empty($profile['role_key'])) {
        $position = strtolower(trim($profile['position'] ?? ''));
        if (str_contains($position, 'monitoring')) {
            $roleKey = 'monitoring_officer';
        } elseif (str_contains($position, 'secretary')) {
            $roleKey = 'secretary';
        } else {
            $roleKey = 'faculty';
        }
    } else {
        $roleKey = $profile['role_key'];
    }

    $sql = "INSERT INTO sms2_db.users 
            (username, email, password_hash, role_key, status, created_at) 
            VALUES 
            (:username, :email, :password_hash, :role_key, :status, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username'      => $profile['email'],
        ':email'         => $profile['email'],
        ':password_hash' => $hashedPassword,
        ':role_key'      => $roleKey,
        ':status'        => $profile['account_status'] ?? 'pending_approval'
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Inserts entry into faculty_db.faculty_profiles.
 */
if (!function_exists('insertFacultyProfile')) {
    function insertFacultyProfile(array $profile): int {
        $pdo = function_exists('facultyDb') ? facultyDb() : db();

        $sql = "INSERT INTO faculty_db.faculty_profiles (
                    user_id, faculty_id, first_name, middle_name, last_name, suffix, 
                    sex, birthdate, age, phone, email, designated_department, 
                    position, hired_date, contractual_end, employment_status, 
                    profile_status, request_status, created_at
                ) VALUES (
                    :user_id, :faculty_id, :first_name, :middle_name, :last_name, :suffix, 
                    :sex, :birthdate, :age, :phone, :email, :designated_department, 
                    :position, :hired_date, :contractual_end, :employment_status, 
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
    function sendFacultyAccountEmail(string $email, string $facultyId, string $username, string $password, string $firstName, string $lastName, string $sex): bool {
        // Include email sending logic / PHPMailer calls here if needed
        return true;
    }
}
/**
 * Loads all faculty profiles college-wide for the Dean directory.
 */
if (!function_exists('loadFacultyProfiles')) {
    function loadFacultyProfiles(): array {
        $pdo = function_exists('facultyDb') ? facultyDb() : (function_exists('db') ? db() : null);
        if (!$pdo) {
            return [];
        }

        $sql = "SELECT fp.*, u.username, u.status AS account_status 
                FROM faculty_db.faculty_profiles fp
                LEFT JOIN sms2_db.users u ON fp.user_id = u.id
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