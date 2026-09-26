<?php
if (!class_exists('AttendanceModel')) {

class AttendanceModel {
    private $db;

    public function __construct($pdoConnection = null) {
        if ($pdoConnection instanceof \PDO) {
            $this->db = $pdoConnection;
        } elseif (function_exists('db') && db() instanceof \PDO) {
            $this->db = db();
        } elseif (function_exists('facultyDb') && facultyDb() instanceof \PDO) {
            $this->db = facultyDb();
        } else {
            $this->db = null;
        }
    }

    private function ensureDb() {
        if (!$this->db) {
            if (function_exists('db') && db() instanceof \PDO) {
                $this->db = db();
            } elseif (function_exists('facultyDb') && facultyDb() instanceof \PDO) {
                $this->db = facultyDb();
            } else {
                throw new \Exception("Database connection is missing or could not be established.");
            }
        }
    }

    public function getFacultyByDepartment($deptId) {
        $this->ensureDb();
        $sql = "SELECT id, faculty_id, first_name, last_name, position 
                FROM faculty_profiles 
                WHERE (LOWER(designated_department) = LOWER(:deptId1) OR designated_department = :deptId2)
                  AND profile_status IN ('Active', 'Approved')
                ORDER BY last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['deptId1' => $deptId, 'deptId2' => $deptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodayLogs($deptId, $date) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                cas.session_id,
                CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                cas.status,
                r.room_code,
                s.code AS subject_code,
                cas.attending_students
            FROM class_attendance_sessions cas
            JOIN faculty_profiles f ON cas.faculty_id = f.id
            LEFT JOIN rooms r ON cas.room_id = r.room_id
            JOIN subjects s ON cas.subject_id = s.subject_id
            WHERE cas.department_id = :dept_id AND cas.session_date = :session_date
            ORDER BY cas.created_at DESC
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch past attendance logs for a specific faculty member (Used by Reports & Analytics page)
     */
    public function getLogsByFaculty($facultyProfileId) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                cas.session_id AS id,
                cas.session_date AS date,
                cas.created_at,
                cas.status,
                cas.attending_students,
                COALESCE(s.code, 'N/A') AS subject_code,
                COALESCE(r.room_code, 'N/A') AS room_code
            FROM class_attendance_sessions cas
            LEFT JOIN subjects s ON cas.subject_id = s.subject_id
            LEFT JOIN rooms r ON cas.room_id = r.room_id
            WHERE cas.faculty_id = :faculty_id
            ORDER BY cas.session_date DESC, cas.created_at DESC
        ");
        $stmt->execute([':faculty_id' => (int)$facultyProfileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSessionsForFacultyIds(array $facultyProfileIds, $startDate, $endDate) {
        $this->ensureDb();
        $ids = array_values(array_filter(array_map('intval', $facultyProfileIds)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "
            SELECT 
                cas.session_id,
                cas.faculty_id,
                cas.session_date,
                s.code AS subject_code,
                r.room_code,
                cas.status,
                cas.attending_students
            FROM class_attendance_sessions cas
            LEFT JOIN subjects s ON cas.subject_id = s.subject_id
            LEFT JOIN rooms r ON cas.room_id = r.room_id
            WHERE cas.faculty_id IN ($placeholders)
              AND cas.session_date BETWEEN ? AND ?
            ORDER BY cas.session_date DESC, cas.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($ids, [$startDate, $endDate]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartmentStats($deptId, $date) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(session_id) AS total_sessions,
                SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) AS present_faculty,
                SUM(CASE WHEN LOWER(status) = 'late' THEN 1 ELSE 0 END) AS late_faculty,
                SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) AS absent_faculty,
                SUM(attending_students) AS total_students
            FROM class_attendance_sessions
            WHERE department_id = :dept_id AND session_date = :session_date
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrCreateSubjectId($code, $deptId = null) {
        $this->ensureDb();
        $code = trim((string) $code);
        if ($code === '') {
            return 1;
        }

        $stmt = $this->db->prepare("SELECT subject_id FROM subjects WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $insert = $this->db->prepare("
            INSERT INTO subjects (department_id, code, title)
            VALUES (:dept_id, :code, :title)
        ");
        $insert->execute([
            ':dept_id' => is_numeric($deptId) ? (int) $deptId : null,
            ':code'    => $code,
            ':title'   => $code,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getOrCreateRoomId($roomCode, $campusId) {
        $this->ensureDb();
        $roomCode = trim((string) $roomCode);
        if ($roomCode === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT room_id FROM rooms 
            WHERE campus_id = :campus_id AND room_code = :room_code 
            LIMIT 1
        ");
        $stmt->execute([':campus_id' => $campusId, ':room_code' => $roomCode]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $insert = $this->db->prepare("
            INSERT INTO rooms (campus_id, room_code)
            VALUES (:campus_id, :room_code)
        ");
        $insert->execute([':campus_id' => $campusId, ':room_code' => $roomCode]);
        return (int) $this->db->lastInsertId();
    }

    public function getOrCreateFacultyRecord($facultyProfileId) {
        $this->ensureDb();
        $facultyProfileId = (int) $facultyProfileId;

        $check = $this->db->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = :id LIMIT 1");
        $check->execute([':id' => $facultyProfileId]);
        if ($check->fetchColumn()) {
            return $facultyProfileId;
        }

        $profile = $this->db->prepare("SELECT * FROM faculty_profiles WHERE id = :id LIMIT 1");
        $profile->execute([':id' => $facultyProfileId]);
        $fp = $profile->fetch(PDO::FETCH_ASSOC);
        if (!$fp) {
            return null;
        }

        $dept = $this->db->prepare("SELECT department_id FROM departments WHERE code = :code LIMIT 1");
        $dept->execute([':code' => $fp['designated_department'] ?? '']);
        $deptId = $dept->fetchColumn() ?: null;

        $position = ($fp['position'] ?? '') === 'Faculty Secretary' ? 'Faculty Secretary' : 'Faculty Professor';
        $contractualEnd = (!empty($fp['contractual_end_date']) && $fp['contractual_end_date'] !== '0000-00-00')
            ? $fp['contractual_end_date'] : null;
        $facultyNo = ($fp['faculty_id'] ?: 'FAC') . '-P' . $facultyProfileId;

        $insert = $this->db->prepare("
            INSERT INTO faculty (
                faculty_id, faculty_no, first_name, middle_name, last_name, suffix,
                birthdate, sex, phone, email, department_id, position,
                is_coordinator, coordinator_type, tier,
                employment_status, profile_status, hired_date, contractual_end_date
            ) VALUES (
                :faculty_id, :faculty_no, :first_name, :middle_name, :last_name, :suffix,
                :birthdate, :sex, :phone, :email, :department_id, :position,
                :is_coordinator, :coordinator_type, :tier,
                :employment_status, :profile_status, :hired_date, :contractual_end_date
            )
        ");
        $insert->execute([
            ':faculty_id'         => $facultyProfileId,
            ':faculty_no'         => $facultyNo,
            ':first_name'         => $fp['first_name'],
            ':middle_name'        => $fp['middle_name'],
            ':last_name'          => $fp['last_name'],
            ':suffix'             => $fp['suffix'],
            ':birthdate'          => $fp['birthdate'],
            ':sex'                => $fp['sex'],
            ':phone'              => $fp['phone'],
            ':email'              => $fp['email'],
            ':department_id'      => $deptId,
            ':position'           => $position,
            ':is_coordinator'     => $fp['is_coordinator'] ?? 0,
            ':coordinator_type'   => $fp['coordinator_type'],
            ':tier'               => $fp['tier'],
            ':employment_status'  => $fp['employment_status'] ?: 'Probationary',
            ':profile_status'     => ($fp['profile_status'] === 'Active') ? 'Active' : 'Active',
            ':hired_date'         => $fp['hired_date'],
            ':contractual_end_date' => $contractualEnd,
        ]);

        return $facultyProfileId;
    }

    public function saveSession($data) {
        $this->ensureDb();

        try {
            $this->db->beginTransaction();

            $statusNormalized = ucfirst(strtolower(trim($data['status'])));

            $stmt = $this->db->prepare("
                INSERT INTO class_attendance_sessions 
                (department_id, campus_id, faculty_id, subject_id, room_id, session_date, time_slot, attending_students, secretary_verifier_name, status)
                VALUES (:dept_id, :campus_id, :faculty_id, :subject_id, :room_id, :session_date, :time_slot, :attending_students, :verifier, :status)
            ");
            $stmt->execute([
                ':dept_id'            => $data['department_id'],
                ':campus_id'          => $data['campus_id'],
                ':faculty_id'         => $data['faculty_id'],
                ':subject_id'         => $data['subject_id'],
                ':room_id'            => $data['room_id'] ?? null,
                ':session_date'       => $data['session_date'],
                ':time_slot'          => $data['time_slot'],
                ':attending_students' => $data['attending_students'],
                ':verifier'           => $data['verifier_name'],
                ':status'             => $statusNormalized
            ]);

            $stmt2 = $this->db->prepare("
                INSERT INTO attendance_records 
                (faculty_id, campus_id, attendance_date, status, signature_data, recorded_by_external_id)
                VALUES (:faculty_id, :campus_id, :attendance_date, :status, :signature, :recorded_by)
                ON DUPLICATE KEY UPDATE
                    campus_id                = VALUES(campus_id),
                    status                   = VALUES(status),
                    signature_data           = VALUES(signature_data),
                    recorded_by_external_id  = VALUES(recorded_by_external_id)
            ");
            $stmt2->execute([
                ':faculty_id'       => $data['faculty_id'],
                ':campus_id'        => $data['campus_id'],
                ':attendance_date'  => $data['session_date'],
                ':status'           => $statusNormalized,
                ':signature'        => $data['signature'] ?? null,
                ':recorded_by'      => $data['user_id']
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}

}