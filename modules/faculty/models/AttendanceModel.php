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
        // CHANGED: class_attendance_sessions, rooms, and subjects all live in
        // faculty_db (confirmed against faculty_db.sql), same as
        // faculty_profiles below — but they were missing the prefix, so PHP
        // was looking for them in the PDO's default database (sms2_db)
        // instead, which caused "Base table or view not found".
        $stmt = $this->db->prepare("
            SELECT 
                cas.session_id,
                CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                cas.status,
                r.room_code,
                s.code AS subject_code,
                cas.attending_students
            FROM faculty_db.class_attendance_sessions cas
            JOIN faculty_db.faculty_profiles f ON cas.faculty_id = f.id
            LEFT JOIN faculty_db.rooms r ON cas.room_id = r.room_id
            JOIN faculty_db.subjects s ON cas.subject_id = s.subject_id
            WHERE cas.department_id = :dept_id AND cas.session_date = :session_date
            ORDER BY cas.created_at DESC
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // CHANGED: new — powers the Reports & Analytics page's "Past Attendance
    // Logs" panel. Returns a faculty member's real saved sessions
    // (class_attendance_sessions) within a date range, joined to their
    // actual subject/room codes. faculty_id here is faculty_profiles.id —
    // the same value class_attendance_sessions.faculty_id already stores.
    public function getSessionsForFaculty($facultyProfileId, $startDate, $endDate) {
        $this->ensureDb();
        $stmt = $this->db->prepare("
            SELECT 
                cas.session_id,
                cas.session_date,
                s.code AS subject_code,
                r.room_code,
                cas.status,
                cas.attending_students
            FROM faculty_db.class_attendance_sessions cas
            LEFT JOIN faculty_db.subjects s ON cas.subject_id = s.subject_id
            LEFT JOIN faculty_db.rooms r ON cas.room_id = r.room_id
            WHERE cas.faculty_id = :faculty_id
              AND cas.session_date BETWEEN :start_date AND :end_date
            ORDER BY cas.session_date DESC, cas.created_at DESC
        ");
        $stmt->execute([
            ':faculty_id' => $facultyProfileId,
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate daily statistics for the department
    public function getDepartmentStats($deptId, $date) {
        $this->ensureDb();
        // CHANGED: prefixed with faculty_db., same reason as getTodayLogs() above.
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(session_id) AS total_sessions,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_faculty,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_faculty,
                SUM(attending_students) AS total_students
            FROM faculty_db.class_attendance_sessions
            WHERE department_id = :dept_id AND session_date = :session_date
        ");
        $stmt->execute([':dept_id' => $deptId, ':session_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // CHANGED: new — resolves a typed subject code (e.g. "SIA-201") to a real
    // subjects.subject_id, creating the subject row if it doesn't exist yet.
    // subjects.code has a UNIQUE key, so this is safe to call repeatedly.
    public function getOrCreateSubjectId($code, $deptId = null) {
        $this->ensureDb();
        $code = trim((string) $code);
        if ($code === '') {
            return 1; // falls back to the GEN-001 placeholder subject
        }

        $stmt = $this->db->prepare("SELECT subject_id FROM faculty_db.subjects WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $insert = $this->db->prepare("
            INSERT INTO faculty_db.subjects (department_id, code, title)
            VALUES (:dept_id, :code, :title)
        ");
        $insert->execute([
            ':dept_id' => is_numeric($deptId) ? (int) $deptId : null,
            ':code'    => $code,
            ':title'   => $code, // no separate title field on the quick-entry form, so code doubles as title
        ]);
        return (int) $this->db->lastInsertId();
    }

    // CHANGED: new — resolves a typed room code (e.g. "403-B") to a real
    // rooms.room_id, creating the room row if it doesn't exist yet.
    // rooms has a UNIQUE key on (campus_id, room_code), so this is safe to
    // call repeatedly.
    public function getOrCreateRoomId($roomCode, $campusId) {
        $this->ensureDb();
        $roomCode = trim((string) $roomCode);
        if ($roomCode === '') {
            return null; // room_id is nullable — no room typed, no room saved
        }

        $stmt = $this->db->prepare("
            SELECT room_id FROM faculty_db.rooms 
            WHERE campus_id = :campus_id AND room_code = :room_code 
            LIMIT 1
        ");
        $stmt->execute([':campus_id' => $campusId, ':room_code' => $roomCode]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $insert = $this->db->prepare("
            INSERT INTO faculty_db.rooms (campus_id, room_code)
            VALUES (:campus_id, :room_code)
        ");
        $insert->execute([':campus_id' => $campusId, ':room_code' => $roomCode]);
        return (int) $this->db->lastInsertId();
    }

    // CHANGED: new — this is the permanent fix for the recurring
    // "fk_sessions_faculty" foreign key errors. class_attendance_sessions
    // requires a row in `faculty`, but your faculty dropdown is built from
    // `faculty_profiles` — a separate table that the rest of the system
    // doesn't keep in sync automatically. Instead of relying on manually
    // re-running a SQL backfill every time someone new gets approved, this
    // creates the missing `faculty` row automatically, right when it's
    // needed, before the attendance INSERT runs.
    //
    // Matches ONLY on faculty_id (the real primary key = faculty_profiles.id)
    // — never on faculty_no — so a duplicate faculty_profiles.faculty_id
    // business code (e.g. two people both having 'FAC-2026-0006') can never
    // cause this to silently update the wrong person's row.
    public function getOrCreateFacultyRecord($facultyProfileId) {
        $this->ensureDb();
        $facultyProfileId = (int) $facultyProfileId;

        $check = $this->db->prepare("SELECT faculty_id FROM faculty_db.faculty WHERE faculty_id = :id LIMIT 1");
        $check->execute([':id' => $facultyProfileId]);
        if ($check->fetchColumn()) {
            return $facultyProfileId; // already synced, nothing to do
        }

        $profile = $this->db->prepare("SELECT * FROM faculty_db.faculty_profiles WHERE id = :id LIMIT 1");
        $profile->execute([':id' => $facultyProfileId]);
        $fp = $profile->fetch(PDO::FETCH_ASSOC);
        if (!$fp) {
            return null; // no such profile at all — let the caller handle this
        }

        $dept = $this->db->prepare("SELECT department_id FROM faculty_db.departments WHERE code = :code LIMIT 1");
        $dept->execute([':code' => $fp['designated_department'] ?? '']);
        $deptId = $dept->fetchColumn() ?: null;

        $position = ($fp['position'] ?? '') === 'Faculty Secretary' ? 'Faculty Secretary' : 'Faculty Professor';
        $contractualEnd = (!empty($fp['contractual_end_date']) && $fp['contractual_end_date'] !== '0000-00-00')
            ? $fp['contractual_end_date'] : null;
        // Guaranteed-unique even if faculty_profiles.faculty_id is duplicated elsewhere.
        $facultyNo = ($fp['faculty_id'] ?: 'FAC') . '-P' . $facultyProfileId;

        $insert = $this->db->prepare("
            INSERT INTO faculty_db.faculty (
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

    // Save attendance session into database
    public function saveSession($data) {
        $this->ensureDb();

        try {
            $this->db->beginTransaction();

            // CHANGED: prefixed with faculty_db. — this INSERT was the direct
            // cause of "Table 'sms2_db.class_attendance_sessions' doesn't exist".
            // The table is real, it just lives in faculty_db.
            // CHANGED: added room_id to both the column list and VALUES —
            // it was missing entirely before, so every session saved with
            // room_id defaulting to NULL ("N/A" in Recent Logs) no matter
            // what room was typed in the form.
            $stmt = $this->db->prepare("
                INSERT INTO faculty_db.class_attendance_sessions 
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
                ':status'             => $data['status']
            ]);

            // CHANGED: prefixed with faculty_db. — attendance_records lives here too.
            //
            // CHANGED: was a plain INSERT, now an upsert (ON DUPLICATE KEY UPDATE).
            // attendance_records has a UNIQUE KEY on (faculty_id, attendance_date) —
            // it's meant to be ONE daily summary row per faculty, not one row per
            // session. A plain INSERT broke the moment the same faculty got a
            // second room check on the same day (error 1062, key
            // uq_attendance_faculty_date). Per-session history is NOT lost —
            // that's what class_attendance_sessions is for (no unique constraint,
            // every session gets its own row, and that's what feeds "Recent Logs" /
            // "All Records"). This table just keeps getting overwritten with
            // whatever the most recent session's status/signature was that day.
            $stmt2 = $this->db->prepare("
                INSERT INTO faculty_db.attendance_records 
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

    // =====================================================================
    // NEW METHOD ADDED: Fetch attendance records for a specific faculty 
    // member (for "My Attendance" page)
    // =====================================================================
    public function getFacultyAttendanceRecords($facultyProfileId, $limit = 10) {
        $this->ensureDb();
        
        // We fetch from class_attendance_sessions because it holds the daily session logs
        // created by the Monitoring Officer.
        $stmt = $this->db->prepare("
            SELECT 
                cas.session_id,
                cas.session_date,
                cas.time_slot,
                cas.status,
                cas.attending_students,
                s.code AS subject_code,
                r.room_code
            FROM faculty_db.class_attendance_sessions cas
            LEFT JOIN faculty_db.subjects s ON cas.subject_id = s.subject_id
            LEFT JOIN faculty_db.rooms r ON cas.room_id = r.room_id
            WHERE cas.faculty_id = :faculty_id
            ORDER BY cas.session_date DESC, cas.time_slot DESC
            LIMIT :limit
        ");
        
        // Bind parameters (casting limit to int for PDO)
        $stmt->bindParam(':faculty_id', $facultyProfileId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

}