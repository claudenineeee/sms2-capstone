<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php'; // Optional if using a Model class

class UserController {
    private $db;

    public function __construct($pdoConnection) {
        $this->db = $pdoConnection;
    }

    public function store() {
        // Ensure request is POST and user is authenticated
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }

        $creatorDeptId = $_SESSION['department_id'] ?? null;

        if (!$creatorDeptId) {
            $_SESSION['error'] = 'Unauthorized: Department context missing.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        try {
            $this->db->beginTransaction();

            // 1. Central authentication record (sms2_db.users)
           $stmtUser = $this->db->prepare("
                INSERT INTO sms2_db.users (username, email, password_hash, role_key, account_status, must_change_password, created_at)
                VALUES (:username, :email, :password, :role_key, 'pending_approval', 1, NOW())
            ");
            $stmtUser->execute([
                ':username' => trim($_POST['username']),
                ':email'    => trim($_POST['email']),
                ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':role_key' => $_POST['role_key']
            ]);

            $newUserId = $this->db->lastInsertId();

            // 2. Profile record inheriting creator's department (faculty_db.faculty_profiles or faculty)
            $stmtProfile = $this->db->prepare("
                INSERT INTO faculty (user_id, first_name, last_name, department_id, profile_status, created_at)
                VALUES (:user_id, :first_name, :last_name, :dept_id, 'Active', NOW())
            ");
            $stmtProfile->execute([
                ':user_id'    => $newUserId,
                ':first_name' => trim($_POST['first_name']),
                ':last_name'  => trim($_POST['last_name']),
                ':dept_id'    => $creatorDeptId // Auto-inherited
            ]);

            $this->db->commit();
            $_SESSION['success'] = 'Account created and queued for admin approval.';
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Failed to create user: ' . $e->getMessage();
        }

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Action Router
if (isset($_GET['action']) && $_GET['action'] === 'store') {
    $controller = new UserController(facultyDb());
    $controller->store();
}