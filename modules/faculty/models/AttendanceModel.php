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

    /**
     * Ensure active PDO instance before running queries
     */
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

    // Fetch faculty members strictly in the officer's/head's department
    public function getFacultyByDepartment($deptId) {
        $this->ensureDb();
        $sql = "SELECT id, faculty_id, first_name, last_name, position 
                FROM faculty_db.faculty_profiles 
                WHERE (LOWER(designated_department) = LOWER(:deptId) OR designated_department = :deptId)
                  AND profile_status IN ('Active', 'Approved')
                ORDER BY last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['deptId' => $deptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch departmental attendance logs for today
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
            JOIN faculty_db.faculty_profiles f ON cas.faculty_id = f.id
            LEFT JOIN rooms r ON cas.room_id = r.room_id
            JOIN subjects s ON cas.subject_id = s.subject_id
            WHERE cas.department_id = :dept_id AND cas.session_date = :session_date
            ORDER BY cas.created_at DESC
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate daily statistics for the department
    public function getDepartmentStats($deptId, $date) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(session_id) AS total_sessions,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_faculty,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_faculty,
                SUM(attending_students) AS total_students
            FROM class_attendance_sessions
            WHERE department_id = :dept_id AND session_date = :session_date
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Save attendance session into database
    public function saveSession($data) {
        $this->ensureDb();

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO class_attendance_sessions 
                (department_id, campus_id, faculty_id, subject_id, session_date, time_slot, attending_students, secretary_verifier_name, status)
                VALUES (:dept_id, :campus_id, :faculty_id, :subject_id, :session_date, :time_slot, :attending_students, :verifier, :status)
            ");
            $stmt->execute([
                ':dept_id'            => $data['department_id'],
                ':campus_id'          => $data['campus_id'],
                ':faculty_id'         => $data['faculty_id'],
                ':subject_id'         => $data['subject_id'],
                ':session_date'       => $data['session_date'],
                ':time_slot'          => $data['time_slot'],
                ':attending_students' => $data['attending_students'],
                ':verifier'           => $data['verifier_name'],
                ':status'             => $data['status']
            ]);

            $stmt2 = $this->db->prepare("
                INSERT INTO attendance_records 
                (faculty_id, campus_id, attendance_date, status, signature_data, recorded_by_external_id)
                VALUES (:faculty_id, :campus_id, :attendance_date, :status, :signature, :recorded_by)
            ");
            $stmt2->execute([
                ':faculty_id'       => $data['faculty_id'],
                ':campus_id'        => $data['campus_id'],
                ':attendance_date'  => $data['session_date'],
                ':status'           => $data['status'],
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