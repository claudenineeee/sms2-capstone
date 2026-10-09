<?php
/**
 * Dean RBAC helper — shared by every dean page.
 * Source of truth: faculty_db.faculty_profile_department_assignments
 */

if (!function_exists('deanDb')) {
    function deanDb(): ?PDO
    {
        if (function_exists('facultyDb')) {
            $db = facultyDb();
            if ($db instanceof PDO) return $db;
        }
        if (function_exists('db')) {
            try { $db = db(); if ($db instanceof PDO) return $db; } catch (Throwable $e) {}
        }
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }
        return null;
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
            $stmt = $db->prepare("
                SELECT id FROM faculty_db.faculty_profiles
                WHERE user_id = ? LIMIT 1
            ");
            $stmt->execute([(int) $userId]);
            $id = $stmt->fetchColumn();
            if ($id) return (int) $id;

            // Path 2: faculty_profiles.faculty_id matches faculty.faculty_no
            //         where faculty.external_user_id = session user id
            $stmt = $db->prepare("
                SELECT fp.id
                FROM faculty_db.faculty_profiles fp
                INNER JOIN faculty_db.faculty f ON f.faculty_no = fp.faculty_id
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
            $stmt = $db->prepare("
                SELECT department_id
                FROM faculty_db.faculty_profile_department_assignments
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
            $rows = $db->query("
                SELECT department_id, code, name
                FROM faculty_db.departments
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