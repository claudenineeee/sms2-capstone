<?php
/**
 * Dean RBAC helper — shared by every dean page.
 * Source of truth: faculty_profile_department_assignments
 */

if (!function_exists('deanDb')) {
    function deanDb(): ?PDO
    {
        // PATCHED: Direct HostForge connection
        try {
            $host = getenv('DB_HOST')     ?: 'localhost';
            $user = getenv('DB_USERNAME') ?: 'root';
            $pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
            $name = getenv('DB_DATABASE') ?: 'faculty_db';
            $port = (int) (getenv('DB_PORT') ?: 3306);
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (Throwable $e) {
            error_log('Dean RBAC DB connection failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getDeanProfileId')) {
    /**
     * Resolve the dean's faculty_profiles.id from the session user id.
     */
    function getDeanProfileId($userId): ?int
    {
        if (!$userId) return null;
        $db = deanDb();
        if (!$db instanceof PDO) return null;

        try {
            // Path 1: faculty_profiles.user_id (int)
            // REMOVED faculty_db. prefix
            $stmt = $db->prepare("
                SELECT id FROM faculty_profiles
                WHERE user_id = ? LIMIT 1
            ");
            $stmt->execute([(int) $userId]);
            $id = $stmt->fetchColumn();
            if ($id) return (int) $id;

            // Path 2: faculty_profiles.faculty_id matches faculty.faculty_no
            //         where faculty.external_user_id = session user id
            // REMOVED faculty_db. prefixes
            $stmt = $db->prepare("
                SELECT fp.id
                FROM faculty_profiles fp
                INNER JOIN faculty f ON f.faculty_no = fp.faculty_id
                WHERE f.external_user_id = ?
                LIMIT 1
            ");
            $stmt->execute([(string) $userId]);
            $id = $stmt->fetchColumn();
            return $id ? (int) $id : null;
        } catch (Throwable $e) {
            error_log('getDeanProfileId failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getDeanAssignedDepartments')) {
    /**
     * @param  string|int|null $deanUserId
     * @return int[]  array of department_id (ints)
     */
    function getDeanAssignedDepartments($deanUserId): array
    {
        $profileId = getDeanProfileId($deanUserId);
        if (!$profileId) return [];

        $db = deanDb();
        if (!$db instanceof PDO) return [];

        try {
            // REMOVED faculty_db. prefix
            $stmt = $db->prepare("
                SELECT department_id
                FROM faculty_profile_department_assignments
                WHERE faculty_profile_id = ?
            ");
            $stmt->execute([$profileId]);
            return array_values(array_unique(array_filter(array_map(
                'intval', $stmt->fetchAll(PDO::FETCH_COLUMN)
            ))));
        } catch (Throwable $e) {
            error_log('getDeanAssignedDepartments failed: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getDeanDepartmentNames')) {
    function getDeanDepartmentNames(array $deptIds): array
    {
        if (empty($deptIds)) return [];
        $db = deanDb();
        if (!$db instanceof PDO) return [];

        $deptIds = array_values(array_unique(array_map('intval', $deptIds)));
        $list = implode(',', $deptIds);
        if ($list === '') return [];

        try {
            // REMOVED faculty_db. prefix
            $rows = $db->query("
                SELECT department_id, code, name
                FROM departments
                WHERE department_id IN ($list)
                ORDER BY name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $out = [];
            foreach ($rows as $d) {
                $out[(int) $d['department_id']] = $d['name'] ?: $d['code'];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('getDeanDepartmentNames failed: ' . $e->getMessage());
            return [];
        }
    }
}