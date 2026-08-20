<?php
// Ensure session is active before anything runs
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/AttendanceModel.php';

class AttendanceController {
    private $model;

    public function __construct($pdoConnection = null) {
        $this->model = new AttendanceModel($pdoConnection);
    }

    /**
     * Resolve department ID safely across Session, DB, and Request Payload.
     */
    private function resolveDepartmentId($inputPayload = []) {
        if (!empty($_SESSION['user']['department_id'])) return $_SESSION['user']['department_id'];
        if (!empty($_SESSION['user']['department'])) return $_SESSION['user']['department'];
        if (!empty($_SESSION['department_id'])) return $_SESSION['department_id'];
        if (!empty($_SESSION['department'])) return $_SESSION['department'];

        $userId = function_exists('getCurrentUserId') ? getCurrentUserId() : ($_SESSION['user_id'] ?? null);
        if ($userId && function_exists('facultyDb')) {
            try {
                $facPdo = facultyDb();
                if ($facPdo) {
                    $stmt = $facPdo->prepare('SELECT designated_department FROM faculty_profiles WHERE user_id = ? LIMIT 1');
                    $stmt->execute([$userId]);
                    $dept = $stmt->fetchColumn();

                    if (!empty($dept) && strtoupper(trim((string) $dept)) !== 'NONE') {
                        return $dept;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DB lookup error and continue to payload/default fallback
            }
        }

        if (!empty($inputPayload['department_id'])) return $inputPayload['department_id'];
        if (!empty($inputPayload['department'])) return $inputPayload['department'];

        return 1; // Default fallback ID
    }

    /**
     * Render the daily attendance log page
     */
    public function index() {
        $deptId = $this->resolveDepartmentId();

        if (!$deptId) {
            $facultyList = [];
            $recentLogs  = [];
            $stats       = [
                'total_sessions'    => 0,
                'present_faculty'   => 0,
                'absent_faculty'    => 0,
                'total_students'    => 0,
                'expected_students' => 0
            ];
        } else {
            $today = date('Y-m-d');
            $facultyList = $this->model->getFacultyByDepartment($deptId) ?? [];
            $recentLogs  = $this->model->getTodayLogs($deptId, $today) ?? [];
            $stats       = $this->model->getDepartmentStats($deptId, $today) ?? [];
        }

        require_once __DIR__ . '/../views/department-head/daily-attendance-log.php';
    }

    /**
     * Store a new attendance session record via API endpoint
     */
    public function store() {
        // Start output buffering to capture/clear stray PHP warnings
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rawInput = file_get_contents('php://input');
            $input    = json_decode($rawInput, true);

            if (!is_array($input)) {
                ob_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid JSON payload received.'
                ]);
                return;
            }

            $deptId   = $this->resolveDepartmentId($input);
            $campusId = $_SESSION['campus_id'] ?? 1;

            if (!$deptId) {
                ob_clean();
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized: Missing or invalid department assignment for current user.'
                ]);
                return;
            }

            $requiredFields = ['faculty_id', 'time_slot', 'attending_students', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                    ob_clean();
                    http_response_code(422);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Missing or empty required field: {$field}"
                    ]);
                    return;
                }
            }

            $userId = function_exists('getCurrentUserId') ? getCurrentUserId() : ($_SESSION['user_id'] ?? null);

            $payload = [
                'department_id'      => $deptId,
                'campus_id'          => $campusId,
                'faculty_id'         => (int) $input['faculty_id'],
                'subject_id'         => isset($input['subject_id']) ? (int) $input['subject_id'] : 1,
                'session_date'       => date('Y-m-d'),
                'time_slot'          => trim((string) $input['time_slot']),
                'attending_students' => (int) $input['attending_students'],
                'verifier_name'      => $userId ? (string) $userId : 'System',
                'status'             => trim((string) $input['status']),
                'signature'          => $input['signature'] ?? null,
                'user_id'            => $userId,
            ];

            $saved = $this->model->saveSession($payload);

            if (!$saved) {
                ob_clean();
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to save attendance record to database.'
                ]);
                return;
            }

            ob_clean();
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => 'Attendance logged successfully.'
            ]);
        } catch (\Throwable $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
}

if (isset($_GET['action'])) {
    // Attempt multi-path lookup to guarantee config loading
    $possibleConfigs = [
        __DIR__ . '/../../../config/config.php',
        __DIR__ . '/../../../../config/config.php',
        __DIR__ . '/../../config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/sms2-capstone/config/config.php'
    ];

    foreach ($possibleConfigs as $configPath) {
        if (file_exists($configPath)) {
            require_once $configPath;
            break;
        }
    }

    // Resolve active PDO instance from available helper functions or globals
    $pdo = null;
    if (function_exists('db') && db() instanceof \PDO) {
        $pdo = db();
    } elseif (function_exists('facultyDb') && facultyDb() instanceof \PDO) {
        $pdo = facultyDb();
    } elseif (function_exists('getDBConnection') && getDBConnection() instanceof \PDO) {
        $pdo = getDBConnection();
    } elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
        $pdo = $GLOBALS['pdo'];
    }

    $controller = new AttendanceController($pdo);

    if ($_GET['action'] === 'store') {
        $controller->store();
        exit;
    }
}