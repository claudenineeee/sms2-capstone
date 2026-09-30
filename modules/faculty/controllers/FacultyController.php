<?php
/**
 * SMS 2 - Faculty Controller
 * Path: modules/faculty/controllers/FacultyController.php
 */

require_once __DIR__ . '/../models/FacultyModel.php';

if (file_exists(__DIR__ . '/faculty-data.php')) {
    require_once __DIR__ . '/faculty-data.php';
}

class FacultyController
{
    /**
     * Static helper to format department labels across views.
     */
    public static function getDepartmentLabel(string $department): string
    {
        $labels = [
            'BSIT'   => 'Information Technology',
            'BSCE'   => 'Computer Engineering',
            'BSBA'   => 'Business Administration',
            'BSED'   => 'Teacher Education',
            'BSHM'   => 'Hospitality & Tourism',
            'BSCRIM' => 'Criminology & Sciences',
            'BSCrim' => 'Criminology & Sciences',
            'BSEM'   => 'Entrepreneurial Management',
            'BSTM'   => 'Tourism Management',
        ];

        if ($department === '') {
            return 'N/A';
        }

        $normalized = strtoupper(trim($department));
        return $labels[$normalized] ?? $labels[$department] ?? trim($department);
    }

    public function getMyProfile(): ?array
    {
        if (function_exists('getMyFacultyProfile')) {
            return getMyFacultyProfile();
        }
        return null;
    }

    /**
     * Retrieve directory list using role scoping function.
     */
    public function getDirectoryList(): array
    {
        if (function_exists('getScopedFacultyList')) {
            return getScopedFacultyList();
        }
        return [];
    }

    /**
     * Helper to compute age from birthdate string.
     */
    public function computeAge(string $birthdate): int
    {
        if (empty($birthdate)) {
            return 0;
        }

        $birth = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$birth) {
            return 0;
        }

        $today = new DateTime('today');
        $age = $birth->diff($today)->y;
        return $age >= 0 ? $age : 0;
    }
    
    /**
     * Process Faculty/Staff registration POST request by Department Head.
     */
    public function handleAddFaculty(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string) ($_POST['action'] ?? '') !== 'add_faculty') {
            return null;
        }

        $mainPdo = null;
        try {
            if (function_exists('requireCsrf')) {
                requireCsrf((string) ($_POST['csrf_token'] ?? ''));
            }

            $creatorDept = $_SESSION['user']['designated_department'] 
                        ?? $_SESSION['designated_department'] 
                        ?? $_SESSION['department'] 
                        ?? $_SESSION['user']['department_id'] 
                        ?? trim((string) ($_POST['designated_department'] ?? ''));

            if ($creatorDept === '') {
                throw new InvalidArgumentException('Unauthorized: Missing department assignment.');
            }

            $firstName        = trim((string) ($_POST['first_name'] ?? ''));
            $middleName       = trim((string) ($_POST['middle_name'] ?? ''));
            $lastName         = trim((string) ($_POST['last_name'] ?? ''));
            $suffix           = trim((string) ($_POST['suffix'] ?? ''));
            $birthdate        = trim((string) ($_POST['birthdate'] ?? ''));
            $sex              = trim((string) ($_POST['sex'] ?? ''));
            $phone            = trim((string) ($_POST['phone'] ?? ''));
            $email            = strtolower(trim((string) ($_POST['email'] ?? '')));
            $position         = trim((string) ($_POST['position'] ?? 'Faculty Professor'));
            $hiredDate        = trim((string) ($_POST['hired_date'] ?? date('Y-m-d')));
            $contractualEnd   = trim((string) ($_POST['contractual_end'] ?? ''));
            $employmentStatus = trim((string) ($_POST['employment_status'] ?? 'regular'));

            if ($firstName === '' || $lastName === '' || $birthdate === '' || $sex === '' || $email === '') {
                throw new InvalidArgumentException('Please fill in all required fields.');
            }

            $mainPdo = function_exists('db') ? db() : null;
            $facPdo  = function_exists('facultyDb') ? facultyDb() : null;
            if (!$mainPdo || !$facPdo) {
                throw new RuntimeException('Database connection failed.');
            }

            $mainPdo->beginTransaction();
            $sequence = function_exists('getNextFacultySequenceNumber') ? getNextFacultySequenceNumber($facPdo) : 1;

            $profile = [
                'first_name'            => $firstName,
                'middle_name'           => $middleName,
                'last_name'             => $lastName,
                'suffix'                => $suffix,
                'sex'                   => $sex,
                'birthdate'             => $birthdate,
                'age'                   => $this->computeAge($birthdate),
                'phone'                 => $phone,
                'email'                 => $email,
                'designated_department' => $creatorDept,
                'position'              => $position,
                'hired_date'            => $hiredDate,
                'contractual_end'       => $contractualEnd,
                'employment_status'     => $employmentStatus,
                'profile_status'        => 'Pending Approval',
                'request_status'        => 'pending',
            ];
            
            if (function_exists('populateFacultyAccountFields')) {
                $profile = populateFacultyAccountFields($profile, $sequence);
            }

            $rawPassword = function_exists('buildFacultyPassword') ? buildFacultyPassword($profile['last_name'] ?? '') : 'Password123!';

            // Capture inserted user_id from main database
            $userId = insertFacultyUser($mainPdo, $profile, $rawPassword);
            if (!$userId) {
                $userId = (int) $mainPdo->lastInsertId();
            }

            // Assign user_id to profile array before saving profile
            $profile['user_id']      = $userId;
            $profile['raw_password'] = $rawPassword;

            $newProfileId = insertFacultyProfile($profile);

            if (!$newProfileId) {
                $mainPdo->rollBack();
                return [
                    'type'    => 'danger',
                    'message' => 'Failed to create the faculty profile record.'
                ];
            }

            $mainPdo->commit();

            return [
                'type'    => 'success',
                'message' => "Account for {$position} successfully registered under department: {$creatorDept}."
            ];
        } catch (Throwable $e) {
            if ($mainPdo instanceof PDO && $mainPdo->inTransaction()) {
                $mainPdo->rollBack();
            }
            return [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Faculty Profile update POST request.
     */
    public function handleUpdateFaculty(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string) ($_POST['action'] ?? '') !== 'update_faculty') {
            return null;
        }

        $profileId = (int) ($_POST['profile_id'] ?? 0);

        if ($profileId <= 0) {
            return [
                'type'    => 'danger',
                'message' => 'Invalid faculty profile selected.'
            ];
        }

        try {
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $updates = [
                'first_name'            => trim((string) ($_POST['first_name'] ?? '')),
                'middle_name'           => trim((string) ($_POST['middle_name'] ?? '')),
                'last_name'             => trim((string) ($_POST['last_name'] ?? '')),
                'suffix'                => trim((string) ($_POST['suffix'] ?? '')),
                'sex'                   => trim((string) ($_POST['sex'] ?? '')),
                'birthdate'             => $birthdate,
                'age'                   => $this->computeAge($birthdate),
                'phone'                 => trim((string) ($_POST['phone'] ?? '')),
                'email'                 => strtolower(trim((string) ($_POST['email'] ?? ''))),
                'designated_department' => trim((string) ($_POST['designated_department'] ?? '')),
                'position'              => trim((string) ($_POST['position'] ?? '')),
                'academic_rank'         => trim((string) ($_POST['academic_rank'] ?? '')),
                'tier'                  => trim((string) ($_POST['tier'] ?? '')),
                'hired_date'            => trim((string) ($_POST['hired_date'] ?? '')),
                'contractual_end'       => trim((string) ($_POST['contractual_end_date'] ?? '')),
                'employment_status'     => trim((string) ($_POST['employment_status'] ?? '')),
                'profile_status'        => trim((string) ($_POST['profile_status'] ?? '')),
            ];

            if (FacultyModel::update($profileId, $updates)) {
                return [
                    'type'    => 'success',
                    'message' => 'Faculty profile updated successfully.'
                ];
            }

            return [
                'type'    => 'danger',
                'message' => 'Unable to update faculty profile.'
            ];
        } catch (Throwable $e) {
            return [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Department Head registration POST request.
     */
    public function handleAddDepartmentHead(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string) ($_POST['action'] ?? '') !== 'add_department_head') {
            return null;
        }

        $mainPdo = null;
        try {
            if (function_exists('requireCsrf')) {
                requireCsrf((string) ($_POST['csrf_token'] ?? ''));
            }

            $firstName        = trim((string) ($_POST['first_name'] ?? ''));
            $middleName       = trim((string) ($_POST['middle_name'] ?? ''));
            $lastName         = trim((string) ($_POST['last_name'] ?? ''));
            $suffix           = trim((string) ($_POST['suffix'] ?? ''));
            $birthdate        = trim((string) ($_POST['birthdate'] ?? ''));
            $sex              = trim((string) ($_POST['sex'] ?? ''));
            $phone            = trim((string) ($_POST['phone'] ?? ''));
            $email            = strtolower(trim((string) ($_POST['email'] ?? '')));
            $designatedDept   = trim((string) ($_POST['designated_department'] ?? ''));
            $position         = trim((string) ($_POST['position'] ?? 'Department Head'));
            $hiredDate        = trim((string) ($_POST['hired_date'] ?? ''));
            $contractualEnd   = trim((string) ($_POST['contractual_end'] ?? ''));
            $employmentStatus = trim((string) ($_POST['employment_status'] ?? 'regular'));

            if ($firstName === '' || $lastName === '' || $birthdate === '' || $sex === '' || $email === '' || $designatedDept === '' || $hiredDate === '' || $employmentStatus === '') {
                throw new InvalidArgumentException('Please fill in all required fields.');
            }

            $mainPdo = function_exists('db') ? db() : null;
            $facPdo  = function_exists('facultyDb') ? facultyDb() : null;
            if (!$mainPdo || !$facPdo) {
                throw new RuntimeException('Database connection failed.');
            }

            if (!function_exists('insertFacultyUser') || !function_exists('insertFacultyProfile')) {
                throw new RuntimeException('Faculty account helpers are missing. Check modules/faculty/controllers/faculty-data.php exists.');
            }

            $mainPdo->beginTransaction();
            $sequence = function_exists('getNextFacultySequenceNumber') ? getNextFacultySequenceNumber($facPdo) : 1;

            $profile = [
                'first_name'            => $firstName,
                'middle_name'           => $middleName,
                'last_name'             => $lastName,
                'suffix'                => $suffix,
                'sex'                   => $sex,
                'birthdate'             => $birthdate,
                'age'                   => $this->computeAge($birthdate),
                'phone'                 => $phone,
                'email'                 => $email,
                'designated_department' => $designatedDept,
                'position'              => $position,
                'hired_date'            => $hiredDate,
                'contractual_end'       => $contractualEnd,
                'employment_status'     => $employmentStatus,
                'profile_status'        => 'Pending Approval',
                'request_status'        => 'pending',
            ];

            if (function_exists('populateFacultyAccountFields')) {
                $profile = populateFacultyAccountFields($profile, $sequence);
            }

            $rawPassword = function_exists('buildFacultyPassword') ? buildFacultyPassword($profile['last_name'] ?? '') : 'Password123!';

            // Capture inserted user_id from main database
            $userId = insertFacultyUser($mainPdo, $profile, $rawPassword);
            if (!$userId) {
                $userId = (int) $mainPdo->lastInsertId();
            }

            // Assign user_id to profile array before saving profile
            $profile['user_id']      = $userId;
            $profile['raw_password'] = $rawPassword;

            $newProfileId = insertFacultyProfile($profile);

            if (!$newProfileId) {
                $mainPdo->rollBack();
                return [
                    'type'    => 'danger',
                    'message' => 'Failed to create the faculty profile record. No account was created.'
                ];
            }

            $mainPdo->commit();

            if (function_exists('sendFacultyAccountEmail')) {
                sendFacultyAccountEmail(
                    $profile['email'],
                    $profile['faculty_id'] ?? '',
                    $profile['username'] ?? '',
                    $rawPassword,
                    $firstName,
                    $lastName,
                    $sex
                );
            }

            return [
                'type'    => 'success',
                'message' => 'Department Head profile successfully registered. Username: ' . ($profile['username'] ?? '') . ' / Temp password: ' . $rawPassword
            ];
        } catch (Throwable $e) {
            if ($mainPdo instanceof PDO && $mainPdo->inTransaction()) {
                $mainPdo->rollBack();
            }
            return [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Dean registration POST request. A Dean can oversee multiple
     * departments — same account-creation flow as handleAddDepartmentHead(),
     * plus a loop that records every selected department into
     * faculty_db.faculty_profile_department_assignments.
     * See migration_add_dean_support_v2.sql for that table's definition.
     */
    public function handleAddDean(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string) ($_POST['action'] ?? '') !== 'add_dean') {
            return null;
        }

        $mainPdo = null;
        try {
            if (function_exists('requireCsrf')) {
                requireCsrf((string) ($_POST['csrf_token'] ?? ''));
            }

            $firstName        = trim((string) ($_POST['first_name'] ?? ''));
            $middleName       = trim((string) ($_POST['middle_name'] ?? ''));
            $lastName         = trim((string) ($_POST['last_name'] ?? ''));
            $suffix           = trim((string) ($_POST['suffix'] ?? ''));
            $birthdate        = trim((string) ($_POST['birthdate'] ?? ''));
            $sex              = trim((string) ($_POST['sex'] ?? ''));
            $phone            = trim((string) ($_POST['phone'] ?? ''));
            $email            = strtolower(trim((string) ($_POST['email'] ?? '')));
            $departmentIds    = array_filter(array_map('intval', (array) ($_POST['department_ids'] ?? [])));
            $position         = trim((string) ($_POST['position'] ?? 'Dean'));
            $hiredDate        = trim((string) ($_POST['hired_date'] ?? ''));
            $contractualEnd   = trim((string) ($_POST['contractual_end'] ?? ''));
            $employmentStatus = trim((string) ($_POST['employment_status'] ?? 'regular'));

            if ($firstName === '' || $lastName === '' || $birthdate === '' || $sex === '' || $email === '' || empty($departmentIds) || $hiredDate === '' || $employmentStatus === '') {
                throw new InvalidArgumentException('Please fill in all required fields and select at least one department.');
            }

            $mainPdo = function_exists('db') ? db() : null;
            $facPdo  = function_exists('facultyDb') ? facultyDb() : null;
            if (!$mainPdo || !$facPdo) {
                throw new RuntimeException('Database connection failed.');
            }

            if (!function_exists('insertFacultyUser') || !function_exists('insertFacultyProfile')) {
                throw new RuntimeException('Faculty account helpers are missing. Check modules/faculty/controllers/faculty-data.php exists.');
            }

            // Resolve department_id -> code (e.g. 1 -> 'BSIT') for the
            // primary designated_department stored on the profile itself.
            $deptStmt = $facPdo->prepare("SELECT department_id, code FROM faculty_db.departments WHERE department_id IN (" . implode(',', array_fill(0, count($departmentIds), '?')) . ")");
            $deptStmt->execute($departmentIds);
            $deptRows = $deptStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [department_id => code]

            if (empty($deptRows)) {
                throw new InvalidArgumentException('Selected department(s) could not be found.');
            }

            $primaryDeptId   = $departmentIds[0];
            $primaryDeptCode = $deptRows[$primaryDeptId] ?? reset($deptRows);

            $mainPdo->beginTransaction();
            $sequence = function_exists('getNextFacultySequenceNumber') ? getNextFacultySequenceNumber($facPdo) : 1;

            $profile = [
                'first_name'            => $firstName,
                'middle_name'           => $middleName,
                'last_name'             => $lastName,
                'suffix'                => $suffix,
                'sex'                   => $sex,
                'birthdate'             => $birthdate,
                'age'                   => $this->computeAge($birthdate),
                'phone'                 => $phone,
                'email'                 => $email,
                'designated_department' => $primaryDeptCode,
                'position'              => $position,
                'hired_date'            => $hiredDate,
                'contractual_end'       => $contractualEnd,
                'employment_status'     => $employmentStatus,
                'profile_status'        => 'Pending Approval',
                'request_status'        => 'pending',
            ];

            if (function_exists('populateFacultyAccountFields')) {
                $profile = populateFacultyAccountFields($profile, $sequence);
            }

            $rawPassword = function_exists('buildFacultyPassword') ? buildFacultyPassword($profile['last_name'] ?? '') : 'Password123!';

            $userId = insertFacultyUser($mainPdo, $profile, $rawPassword);
            if (!$userId) {
                $userId = (int) $mainPdo->lastInsertId();
            }

            $profile['user_id']      = $userId;
            $profile['raw_password'] = $rawPassword;

            $newProfileId = insertFacultyProfile($profile);

            if (!$newProfileId) {
                $mainPdo->rollBack();
                return [
                    'type'    => 'danger',
                    'message' => 'Failed to create the Dean profile record. No account was created.'
                ];
            }

            // Record every selected department in the pivot table.
            $pivotStmt = $facPdo->prepare("
                INSERT INTO faculty_db.faculty_profile_department_assignments (faculty_profile_id, department_id)
                VALUES (:profile_id, :dept_id)
            ");
            foreach ($departmentIds as $deptId) {
                $pivotStmt->execute([
                    ':profile_id' => $newProfileId,
                    ':dept_id'    => $deptId,
                ]);
            }

            $mainPdo->commit();

            if (function_exists('sendFacultyAccountEmail')) {
                sendFacultyAccountEmail(
                    $profile['email'],
                    $profile['faculty_id'] ?? '',
                    $profile['username'] ?? '',
                    $rawPassword,
                    $firstName,
                    $lastName,
                    $sex
                );
            }

            return [
                'type'    => 'success',
                'message' => 'Dean profile successfully registered over ' . count($departmentIds) . ' department(s). Username: ' . ($profile['username'] ?? '') . ' / Temp password: ' . $rawPassword
            ];
        } catch (Throwable $e) {
            if ($mainPdo instanceof PDO && $mainPdo->inTransaction()) {
                $mainPdo->rollBack();
            }
            return [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }
}