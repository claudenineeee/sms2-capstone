<?php
/**
 * SMS 2 - Faculty Model
 * Path: modules/faculty/models/FacultyModel.php
 */

if (!class_exists('FacultyModel')) {

class FacultyModel {
    private $db;

    public function __construct($pdoConnection = null) {
        if ($pdoConnection instanceof \PDO) {
            $this->db = $pdoConnection;
        } elseif (function_exists('facultyDb') && facultyDb() instanceof \PDO) {
            $this->db = facultyDb();
        } elseif (function_exists('db') && db() instanceof \PDO) {
            $this->db = db();
        } else {
            $this->db = null;
        }
    }

    /**
     * Ensure active PDO instance before running queries
     */
    private function ensureDb() {
        if (!$this->db) {
            if (function_exists('facultyDb') && facultyDb() instanceof \PDO) {
                $this->db = facultyDb();
            } elseif (function_exists('db') && db() instanceof \PDO) {
                $this->db = db();
            } else {
                throw new \Exception("Database connection is missing or could not be established.");
            }
        }
    }

    /**
     * Load faculty members for a specific department
     */
    public function getDepartmentMembers($department) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT *, designated_department AS designated_dept 
            FROM faculty_db.faculty_profiles 
            WHERE LOWER(TRIM(designated_department)) = LOWER(TRIM(:dept))
              AND (request_status IS NULL OR request_status = 'approved')
            ORDER BY last_name ASC, first_name ASC
        ");
        $stmt->execute([':dept' => $department]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single faculty profile linked to a user account ID
     */
    public function getProfileByUserId($userId) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT *, designated_department AS designated_dept 
            FROM faculty_db.faculty_profiles 
            WHERE user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fetch a single faculty profile by primary key ID or faculty_id string
     */
    public function getProfileById($id) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT *, designated_department AS designated_dept 
            FROM faculty_db.faculty_profiles 
            WHERE id = :id OR faculty_id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Load all faculty profiles for Directory / Management views
     */
    public function getAllProfiles() {
        $this->ensureDb();
        $stmt = $this->db->query("
            SELECT *, designated_department AS designated_dept 
            FROM faculty_db.faculty_profiles 
            ORDER BY last_name ASC, first_name ASC
        ");
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Load faculty profiles filtered by department
     */
    public function getProfilesByDepartment($deptId) {
        $this->ensureDb();
        
        if (empty($deptId) || $deptId === '1' || $deptId === 1) {
            return $this->getAllProfiles();
        }

        $stmt = $this->db->prepare("
            SELECT *, designated_department AS designated_dept 
            FROM faculty_db.faculty_profiles 
            WHERE LOWER(TRIM(designated_department)) = LOWER(TRIM(:dept))
               OR designated_department = :dept
            ORDER BY last_name ASC, first_name ASC
        ");
        $stmt->execute([':dept' => $deptId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Save or update a faculty profile record
     */
    public function saveProfile($data) {
        $this->ensureDb();
        $department = $data['designated_department'] ?? $data['designated_dept'] ?? null;

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("
                UPDATE faculty_db.faculty_profiles SET
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    suffix = :suffix,
                    sex = :sex,
                    birthdate = :birthdate,
                    phone = :phone,
                    email = :email,
                    designated_department = :designated_department,
                    position = :position,
                    profile_status = :profile_status,
                    request_status = :request_status,
                    user_id = :user_id
                WHERE id = :id
            ");
            return $stmt->execute([
                ':first_name'            => $data['first_name'] ?? null,
                ':middle_name'           => $data['middle_name'] ?? null,
                ':last_name'             => $data['last_name'] ?? null,
                ':suffix'                => $data['suffix'] ?? null,
                ':sex'                   => $data['sex'] ?? null,
                ':birthdate'             => $data['birthdate'] ?? null,
                ':phone'                 => $data['phone'] ?? null,
                ':email'                 => $data['email'] ?? null,
                ':designated_department' => $department,
                ':position'              => $data['position'] ?? null,
                ':profile_status'        => $data['profile_status'] ?? 'Active',
                ':request_status'        => $data['request_status'] ?? 'approved',
                ':user_id'               => $data['user_id'] ?? null,
                ':id'                    => $data['id']
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO faculty_db.faculty_profiles (
                faculty_id, first_name, middle_name, last_name, suffix,
                sex, birthdate, phone, email, designated_department, position,
                profile_status, request_status, user_id
            ) VALUES (
                :faculty_id, :first_name, :middle_name, :last_name, :suffix,
                :sex, :birthdate, :phone, :email, :designated_department, :position,
                :profile_status, :request_status, :user_id
            )
        ");
        return $stmt->execute([
            ':faculty_id'            => $data['faculty_id'] ?? null,
            ':first_name'            => $data['first_name'] ?? null,
            ':middle_name'           => $data['middle_name'] ?? null,
            ':last_name'             => $data['last_name'] ?? null,
            ':suffix'                => $data['suffix'] ?? null,
            ':sex'                   => $data['sex'] ?? null,
            ':birthdate'             => $data['birthdate'] ?? null,
            ':phone'                 => $data['phone'] ?? null,
            ':email'                 => $data['email'] ?? null,
            ':designated_department' => $department,
            ':position'              => $data['position'] ?? null,
            ':profile_status'        => $data['profile_status'] ?? 'Active',
            ':request_status'        => $data['request_status'] ?? 'approved',
            ':user_id'               => $data['user_id'] ?? null,
        ]);
    }

    /**
     * Fetch performance metrics summary for a department
     */
    public function getPerformanceMetrics($deptId) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT e.evaluation_id) AS total_evaluated,
                COALESCE(ROUND(AVG(e.composite_score), 1), 0.0) AS dept_avg,
                COALESCE(SUM(CASE WHEN e.composite_score >= 4.5 THEN 1 ELSE 0 END), 0) AS top_performers
            FROM faculty_db.faculty_profiles fp
            LEFT JOIN evaluations e ON fp.id = e.faculty_id
            WHERE (:dept1 = '1' OR LOWER(TRIM(fp.designated_department)) = LOWER(TRIM(:dept2)))
        ");
        $stmt->execute([
            ':dept1' => (string)$deptId,
            ':dept2' => (string)$deptId
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_evaluated' => 0, 'dept_avg' => '0.0', 'top_performers' => 0];
    }

    /**
     * Fetch top performers (score >= 4.5)
     */
    public function getTopPerformers($deptId) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(fp.first_name, ' ', fp.last_name) AS full_name,
                e.composite_score AS overall
            FROM faculty_db.faculty_profiles fp
            INNER JOIN evaluations e ON fp.id = e.faculty_id
            WHERE e.composite_score >= 4.5
              AND (:dept1 = '1' OR LOWER(TRIM(fp.designated_department)) = LOWER(TRIM(:dept2)))
            ORDER BY e.composite_score DESC
            LIMIT 6
        ");
        $stmt->execute([
            ':dept1' => (string)$deptId,
            ':dept2' => (string)$deptId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch filtered performance list with pagination
     */
    public function getPerformanceList($deptId, $searchName, $ratingRange, $limit, $offset) {
        $this->ensureDb();
        $where = ["(:dept1 = '1' OR LOWER(TRIM(fp.designated_department)) = LOWER(TRIM(:dept2)))"];
        $params = [
            ':dept1' => (string)$deptId,
            ':dept2' => (string)$deptId
        ];

        if (!empty($searchName)) {
            $where[] = "CONCAT_WS(' ', fp.first_name, fp.last_name) LIKE :search";
            $params[':search'] = '%' . $searchName . '%';
        }

        if ($ratingRange === '4.5-5.0') { $where[] = "e.composite_score >= 4.5"; }
        elseif ($ratingRange === '3.5-4.4') { $where[] = "(e.composite_score >= 3.5 AND e.composite_score <= 4.4)"; }
        elseif ($ratingRange === '0.0-3.4') { $where[] = "(e.composite_score < 3.5 OR e.composite_score IS NULL)"; }

        $whereSql = " WHERE " . implode(' AND ', $where);

        $sql = "
            SELECT 
                fp.id AS faculty_profile_id,
                CONCAT(fp.first_name, ' ', fp.last_name) AS full_name,
                e.composite_score AS overall
            FROM faculty_db.faculty_profiles fp
            LEFT JOIN evaluations e ON fp.id = e.faculty_id
            {$whereSql}
            ORDER BY e.composite_score DESC, fp.last_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

}