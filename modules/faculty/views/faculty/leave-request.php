<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';

requireAuth();

$pageTitle = 'Leave Request';
$activeModule = 'faculty';
$activePage = 'leave-request';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'Leave Request', 'url' => null],
];

/* =========================================================
   LEAVE BALANCE MANAGER  (DOLE-aligned)
   ========================================================= */
class LeaveBalance
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function forFaculty(int $facultyId, string $academicYear): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM leave_balances
            WHERE faculty_id = :fid AND academic_year = :yr
            LIMIT 1
        ");
        $stmt->execute([':fid' => $facultyId, ':yr' => $academicYear]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->seed($facultyId, $academicYear);
        }

        // Determine the faculty's sex to enforce gender-specific leave rules.
        $check = $this->pdo->prepare("
            SELECT COALESCE(fp.sex, f.sex) AS sex
            FROM faculty f
            LEFT JOIN faculty_profiles fp ON fp.email = f.email
            WHERE f.faculty_id = :fid
            LIMIT 1
        ");
        $check->execute([':fid' => $facultyId]);
        $sexRaw   = $check->fetchColumn();
        $isFemale = (strtoupper(trim((string) $sexRaw)) === 'FEMALE');

        // Self-heal: if the row violates gender rules or is missing base
        // categories, re-seed it.
        $needsRepair =
            (!$isFemale && (int) ($row['maternity_total']   ?? 0) > 0) ||
            (!$isFemale && (int) ($row['magna_carta_total'] ?? 0) > 0) ||
            (!$isFemale && (int) ($row['vawc_total']        ?? 0) > 0) ||
            ($isFemale  && (int) ($row['paternity_total']   ?? 0) > 0) ||
            ((int) ($row['sick_leave_total']     ?? 0) === 0) ||
            ((int) ($row['vacation_leave_total'] ?? 0) === 0);

        if ($needsRepair) {
            return $this->seed($facultyId, $academicYear);
        }

        return $row;
    }

    private function seed(int $facultyId, string $academicYear): array
    {
        $f = $this->pdo->prepare("
            SELECT COALESCE(fp.sex, f.sex)               AS sex,
                   COALESCE(fp.position, f.position)     AS position,
                   COALESCE(fp.hired_date, f.hired_date) AS hired_date,
                   COALESCE(fp.is_solo_parent, 0)        AS is_solo_parent
            FROM faculty f
            LEFT JOIN faculty_profiles fp ON fp.email = f.email
            WHERE f.faculty_id = :fid
            LIMIT 1
        ");
        $f->execute([':fid' => $facultyId]);
        $info = $f->fetch(PDO::FETCH_ASSOC) ?: [];

        $sexRaw   = trim((string) ($info['sex'] ?? ''));
        $sex      = strtoupper($sexRaw);
        $isFemale = ($sex === 'FEMALE');

        $positionRaw = strtolower(trim((string) ($info['position'] ?? '')));
        $isAdministrator = in_array($positionRaw, [
            'department head',
            'department secretary',
            'dean',
        ], true);

        $hiredDate      = $info['hired_date'] ?? null;
        $yearsOfService = 0;
        if (!empty($hiredDate)) {
            $hired = new DateTime($hiredDate);
            $now   = new DateTime();
            $yearsOfService = (int) $hired->diff($now)->y;
        }
        $isSabbaticalEligible = ($yearsOfService >= 6);
        $isSoloParent         = ((int) ($info['is_solo_parent'] ?? 0) === 1);

        // ------- UNIVERSAL LEAVES (all faculty) -------
        $sickTotal     = 15;   // 15 days per academic year
        $vacationTotal = 7;    // covers Proportional Vacation Pay (school breaks)
        $paternityTotal = $isFemale ? 0 : 7;
        $maternityTotal = $isFemale ? ($isSoloParent ? 120 : 105) : 0;
        $magnaCartaTotal = $isFemale ? 60 : 0;
        $vawcTotal       = $isFemale ? 10 : 0;
        $soloParentTotal = $isSoloParent ? 7 : 0;

        // ------- FACULTY ADMINISTRATORS ONLY -------
        $adminVacTotal  = $isAdministrator ? 10 : 0;
        $adminSpecTotal = $isAdministrator ? 3  : 0;

        // ------- CONDITIONAL -------
        $sabbaticalTotal = $isSabbaticalEligible ? 365 : 0;
        $studyTotal      = 0;

        try {
            $ins = $this->pdo->prepare("
                INSERT INTO leave_balances (
                    faculty_id, academic_year,
                    sick_leave_total, vacation_leave_total, emergency_total,
                    maternity_total, paternity_total, solo_parent_total,
                    magna_carta_total, vawc_total,
                    sabbatical_total, admin_vacation_total, admin_special_total,
                    study_leave_total
                ) VALUES (
                    :fid, :yr,
                    :sick, :vac, :emg,
                    :mat, :pat, :solo,
                    :mc, :vawc,
                    :sab, :avac, :aspec,
                    :study
                )
                ON DUPLICATE KEY UPDATE
                    sick_leave_total     = VALUES(sick_leave_total),
                    vacation_leave_total = VALUES(vacation_leave_total),
                    emergency_total      = VALUES(emergency_total),
                    maternity_total      = VALUES(maternity_total),
                    paternity_total      = VALUES(paternity_total),
                    solo_parent_total    = VALUES(solo_parent_total),
                    magna_carta_total    = VALUES(magna_carta_total),
                    vawc_total           = VALUES(vawc_total),
                    sabbatical_total     = VALUES(sabbatical_total),
                    admin_vacation_total = VALUES(admin_vacation_total),
                    admin_special_total  = VALUES(admin_special_total),
                    study_leave_total    = VALUES(study_leave_total)
            ");
            $ins->execute([
                ':fid'   => $facultyId,
                ':yr'    => $academicYear,
                ':sick'  => $sickTotal,
                ':vac'   => $vacationTotal,
                ':emg'   => 0,
                ':mat'   => $maternityTotal,
                ':pat'   => $paternityTotal,
                ':solo'  => $soloParentTotal,
                ':mc'    => $magnaCartaTotal,
                ':vawc'  => $vawcTotal,
                ':sab'   => $sabbaticalTotal,
                ':avac'  => $adminVacTotal,
                ':aspec' => $adminSpecTotal,
                ':study' => $studyTotal,
            ]);
        } catch (Throwable $e) {
            error_log('[LeaveBalance::seed] faculty=' . $facultyId . ' error=' . $e->getMessage());
            throw $e;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM leave_balances
            WHERE faculty_id = :fid AND academic_year = :yr
            LIMIT 1
        ");
        $stmt->execute([':fid' => $facultyId, ':yr' => $academicYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function mapLeaveType(string $leaveType): array
    {
        return match (strtolower(trim($leaveType))) {
            'sick leave'                                    => ['key' => 'sick_leave',     'label' => 'Sick Leave'],
            'vacation leave'                                => ['key' => 'vacation_leave', 'label' => 'Vacation Leave'],
            'emergency leave'                               => ['key' => 'emergency',      'label' => 'Emergency Leave'],
            'maternity leave'                               => ['key' => 'maternity',      'label' => 'Maternity Leave'],
            'paternity leave'                               => ['key' => 'paternity',      'label' => 'Paternity Leave'],
            'solo parent leave'                             => ['key' => 'solo_parent',    'label' => 'Solo Parent Leave'],
            'magna carta leave', 'special leave for women'  => ['key' => 'magna_carta',    'label' => 'Magna Carta Leave'],
            'vawc leave'                                    => ['key' => 'vawc',           'label' => 'VAWC Leave'],
            'sabbatical leave'                              => ['key' => 'sabbatical',     'label' => 'Sabbatical Leave'],
            'academic/vacation leave', 'administrative vacation' => ['key' => 'admin_vacation', 'label' => 'Academic/Vacation Leave'],
            'special leave privileges'                      => ['key' => 'admin_special',  'label' => 'Special Leave Privileges'],
            'study leave'                                   => ['key' => 'study_leave',    'label' => 'Study Leave'],
            default                                         => ['key' => '',               'label' => $leaveType],
        };
    }
}

/* =========================================================
   PAGE STATE
   ========================================================= */
$leaveRequests         = [];
$pendingCount          = 0;
$approvedCount         = 0;
$rejectedCount         = 0;
$finishedCount         = 0;
$documentRequiredCount = 0;

$formError     = '';
$formSuccess   = '';
$alertMessages = [];

$academicYear   = '2026-2027';
$balance        = [];
$activeLeaves   = [];
$totalUsedDays  = 0;
$totalAvailable = 0;

$isFemaleFaculty  = false;
$facultyProfileId = 0;
$userEmail        = '';

try {
    $pdo = facultyDb();

    $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

    if ($userId <= 0) {
        $formError = 'Your user account could not be identified. Please log in again.';
    } else {
        $userData = [];
        try {
            $userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id LIMIT 1");
            $userStmt->execute([':id' => $userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            try {
                $userStmt = $pdo->prepare("SELECT username, email FROM sms2_db.users WHERE id = :id LIMIT 1");
                $userStmt->execute([':id' => $userId]);
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {
                error_log('[leave-request] users lookup failed: ' . $e2->getMessage());
            }
        }

        $userEmail = trim((string) ($userData['email'] ?? ($_SESSION['email'] ?? '')));

        if ($userEmail !== '') {
            $stmt = $pdo->prepare("SELECT faculty_id FROM faculty WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $userEmail]);
            $facultyProfileId = (int) ($stmt->fetchColumn() ?: 0);

            if ($facultyProfileId <= 0) {
                try {
                    $facultyNo = 'FAC-' . date('Y') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
                    $firstName = $userData['username'] ?? ($_SESSION['username'] ?? 'Faculty');
                    $insertFaculty = $pdo->prepare("
                        INSERT INTO faculty (faculty_no, first_name, last_name, email, department_id, position)
                        VALUES (:faculty_no, :first_name, 'Member', :email, 1, 'Faculty Professor')
                    ");
                    $insertFaculty->execute([
                        ':faculty_no' => $facultyNo,
                        ':first_name' => $firstName,
                        ':email'      => $userEmail,
                    ]);
                    $facultyProfileId = (int) $pdo->lastInsertId();
                } catch (Exception $ex) {
                    $formError = 'Your account is not linked to a faculty record and auto-creation failed: ' . $ex->getMessage();
                }
            }
        } else {
            $formError = 'Your account has no email on record. Please contact the administrator.';
        }

        if ($facultyProfileId > 0) {
            $manager = new LeaveBalance($pdo);
            $balance = $manager->forFaculty($facultyProfileId, $academicYear);

            $sexQ = $pdo->prepare("
                SELECT UPPER(COALESCE(fp.sex, f.sex))
                FROM faculty f
                LEFT JOIN faculty_profiles fp ON fp.email = f.email
                WHERE f.faculty_id = :id LIMIT 1
            ");
            $sexQ->execute([':id' => $facultyProfileId]);
            $isFemaleFaculty = (trim((string) $sexQ->fetchColumn()) === 'FEMALE');
        }
    }

    /* ---------------------------------------------------------
       HANDLE: Edit / Resubmit
       --------------------------------------------------------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave'])) {
        $editRequestId = (int) ($_POST['edit_request_id'] ?? 0);
        $leaveType     = trim((string) ($_POST['leave_type'] ?? ''));
        $startDate     = trim((string) ($_POST['start_date'] ?? ''));
        $endDate       = trim((string) ($_POST['end_date']   ?? ''));
        $reason        = trim((string) ($_POST['reason']     ?? ''));

        if ($editRequestId <= 0)                                              $formError = 'Invalid request identifier.';
        if ($formError === '' && $leaveType === '')                           $formError = 'Please select a leave type.';
        if ($formError === '' && ($startDate === '' || $endDate === ''))      $formError = 'Please provide both start and end dates.';
        if ($formError === '' && strtotime($startDate) > strtotime($endDate)) $formError = 'Start date cannot be later than end date.';

        $totalDays = ($startDate && $endDate)
            ? (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1
            : 0;

        if ($formError === '') {
            $chk = $pdo->prepare('SELECT id, documents, faculty_id, status, screening_status, total_days FROM leave_requests WHERE id = :id LIMIT 1');
            $chk->execute([':id' => $editRequestId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $formError = 'Leave request not found.';
            } else {
                $currentStatus    = strtolower(trim((string) ($row['status']           ?? '')));
                $currentScreening = strtolower(trim((string) ($row['screening_status'] ?? '')));
                $isEditable = in_array($currentStatus,    ['document required', 'document_required', 'pending', 'returned'], true)
                           || in_array($currentScreening, ['document required', 'document_required', 'returned'], true);
                if (!$isEditable) {
                    $formError = 'This request can no longer be edited.';
                } elseif ((int) $row['faculty_id'] !== $facultyProfileId) {
                    $formError = 'You are not authorized to modify this request.';
                }
            }
        }

        if ($formError === '') {
            $map = LeaveBalance::mapLeaveType($leaveType);
            $key = $map['key'];
            if ($key !== '' && isset($balance[$key . '_total'])) {
                $total     = (int) $balance[$key . '_total'];
                $used      = (int) $balance[$key . '_used'];
                $remaining = max(0, $total - $used);
                if ($total > 0 && $totalDays > $remaining) {
                    $formError = "Requested {$totalDays} day(s) exceed your remaining {$remaining} day(s) for {$map['label']}.";
                }
            }
        }

        $uploadedName = null;
        if ($formError === '' && isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $formError = 'The supporting document could not be uploaded.';
            } else {
                $uploadDir = __DIR__ . '/../../uploads/leave_requests';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $originalName = basename($_FILES['document']['name']);
                $safeName     = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $fileName     = time() . '_' . $safeName;
                $target       = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                    $uploadedName = 'modules/faculty/uploads/leave_requests/' . $fileName;
                } else {
                    $formError = 'The supporting document could not be saved.';
                }
            }
        }

        if ($formError === '') {
            try {
                $docQ = $pdo->prepare("SELECT documents FROM leave_requests WHERE id = :id LIMIT 1");
                $docQ->execute([':id' => $editRequestId]);
                $existingDoc = (string) ($docQ->fetchColumn() ?: '');
                $finalDoc    = $uploadedName ?: $existingDoc;

                $upSql = "
                    UPDATE leave_requests
                    SET leave_type=:leave_type, start_date=:start_date, end_date=:end_date,
                        total_days=:total_days, reason=:reason, documents=:documents,
                        status='Pending', screening_status='Pending', notification=0, updated_at=NOW()
                    WHERE id = :id
                ";
                $stmtUp = $pdo->prepare($upSql);
                $stmtUp->execute([
                    ':leave_type' => $leaveType,
                    ':start_date' => $startDate,
                    ':end_date'   => $endDate,
                    ':total_days' => $totalDays,
                    ':reason'     => $reason,
                    ':documents'  => $finalDoc,
                    ':id'         => $editRequestId,
                ]);
                $formSuccess = 'Leave request updated and resubmitted successfully.';
            } catch (PDOException $e) {
                $formError = 'Unable to update leave request: ' . $e->getMessage();
                error_log('[leave-request] update error: ' . $e->getMessage());
            }
        }
    }

    /* ---------------------------------------------------------
       HANDLE: New Leave Submission
       --------------------------------------------------------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
        $leaveType = trim((string) ($_POST['leave_type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate   = trim((string) ($_POST['end_date']   ?? ''));
        $reason    = trim((string) ($_POST['reason']     ?? ''));

        if ($formError === '' && $leaveType === '')                           $formError = 'Please select a leave type.';
        if ($formError === '' && ($startDate === '' || $endDate === ''))      $formError = 'Please provide both start and end dates.';
        if ($formError === '' && strtotime($startDate) > strtotime($endDate)) $formError = 'Start date cannot be later than end date.';

        $totalDays = ($startDate && $endDate)
            ? (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1
            : 0;

        if ($formError === '') {
            $map = LeaveBalance::mapLeaveType($leaveType);
            $key = $map['key'];

            if ($key === 'maternity' && !$isFemaleFaculty) {
                $formError = 'Maternity Leave is only available to female faculty.';
            } elseif ($key === 'paternity' && $isFemaleFaculty) {
                $formError = 'Paternity Leave is only available to male faculty.';
            } elseif ($key === 'magna_carta' && !$isFemaleFaculty) {
                $formError = 'Magna Carta Leave is only available to female faculty.';
            } elseif ($key === 'vawc' && !$isFemaleFaculty) {
                $formError = 'VAWC Leave is only available to female faculty.';
            } elseif ($key !== '' && isset($balance[$key . '_total'])) {
                $total     = (int) $balance[$key . '_total'];
                $used      = (int) $balance[$key . '_used'];
                $remaining = max(0, $total - $used);
                if ($total === 0) {
                    $formError = $map['label'] . ' is not available for your position or profile.';
                } elseif ($totalDays > $remaining) {
                    $formError = "Requested {$totalDays} day(s) exceed your remaining {$remaining} day(s) for {$map['label']}.";
                }
            }
        }

        $uploadedName = null;
        if ($formError === '' && isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $formError = 'The supporting document could not be uploaded.';
            } else {
                $uploadDir = __DIR__ . '/../../uploads/leave_requests';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $originalName = basename($_FILES['document']['name']);
                $safeName     = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $fileName     = time() . '_' . $safeName;
                $target       = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                    $uploadedName = 'modules/faculty/uploads/leave_requests/' . $fileName;
                } else {
                    $formError = 'The supporting document could not be saved.';
                }
            }
        }

        if ($formError === '') {
            $requestRef = 'LR-' . date('YmdHis') . '-' . random_int(100, 999);

            try {
                $pdo->beginTransaction();
                $sql = "
                    INSERT INTO leave_requests (
                        faculty_id, request_ref, leave_type,
                        start_date, end_date, total_days, reason,
                        documents, status, screening_status
                    ) VALUES (
                        :faculty_id, :request_ref, :leave_type,
                        :start_date, :end_date, :total_days, :reason,
                        :documents, 'Pending', 'Pending'
                    )
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':faculty_id' => $facultyProfileId,
                    ':request_ref'=> $requestRef,
                    ':leave_type' => $leaveType,
                    ':start_date' => $startDate,
                    ':end_date'   => $endDate,
                    ':total_days' => $totalDays,
                    ':reason'     => $reason,
                    ':documents'  => $uploadedName,
                ]);

                $map = LeaveBalance::mapLeaveType($leaveType);
                if ($map['key'] !== '' && isset($balance[$map['key'] . '_used'])) {
                    $col = $map['key'] . '_used';
                    $upd = $pdo->prepare("
                        UPDATE leave_balances
                        SET {$col} = {$col} + :days, updated_at = NOW()
                        WHERE faculty_id = :fid AND academic_year = :yr
                    ");
                    $upd->execute([
                        ':days' => $totalDays,
                        ':fid'  => $facultyProfileId,
                        ':yr'   => $academicYear,
                    ]);
                }

                $pdo->commit();
                $formSuccess = 'Leave request submitted successfully.';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $formError = 'Unable to save the leave request: ' . $e->getMessage();
                error_log('[leave-request] ' . $e->getMessage());
            }
        }
    }

    /* ---------------------------------------------------------
       LOAD LEAVE REQUESTS
       --------------------------------------------------------- */
    if ($facultyProfileId > 0) {
        $sql = "
            SELECT lr.*,
                fp.faculty_id AS faculty_profile_id,
                CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name,
                DATEDIFF(lr.end_date, lr.start_date) + 1 AS days,
                lr.updated_at AS approval_timestamp
            FROM leave_requests lr
            LEFT JOIN faculty fp ON fp.faculty_id = lr.faculty_id
            WHERE lr.faculty_id = :faculty_profile_id
            ORDER BY lr.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':faculty_profile_id' => $facultyProfileId]);
        $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------------------------------------------------------
       COUNT STATUS TOTALS
       --------------------------------------------------------- */
    foreach ($leaveRequests as $request) {
        $status          = str_replace('_', ' ', strtolower(trim((string) ($request['status']           ?? ''))));
        $screeningStatus = str_replace('_', ' ', strtolower(trim((string) ($request['screening_status'] ?? ''))));

        $isReturned = in_array($status, ['document required', 'returned'], true)
                   || in_array($screeningStatus, ['document required', 'returned'], true);

        if ($isReturned)                $documentRequiredCount++;
        elseif ($status === 'pending')  $pendingCount++;
        elseif ($status === 'approved') $approvedCount++;
        elseif ($status === 'rejected') $rejectedCount++;
        elseif ($status === 'finished') $finishedCount++;

        $notificationFlag = (int) ($request['notification'] ?? 0);
        $documentsValue   = trim((string) ($request['documents'] ?? ''));
        if ($notificationFlag === 1 && $documentsValue === '') {
            $requestRef = trim((string) ($request['request_ref'] ?? ''));
            if ($requestRef === '') $requestRef = 'LR-' . (int) ($request['id'] ?? 0);
            $alertMessages[] = $requestRef . ' document support has been rejected.';
        }
    }

    /* ---------------------------------------------------------
       BUILD ACTIVE LEAVE LIST
       --------------------------------------------------------- */
    $candidates = [
        'sick_leave'     => 'Sick Leave',
        'vacation_leave' => 'Vacation Leave',
        'emergency'      => 'Emergency Leave',
        'maternity'      => 'Maternity Leave',
        'paternity'      => 'Paternity Leave',
        'solo_parent'    => 'Solo Parent Leave',
        'magna_carta'    => 'Magna Carta Leave',
        'vawc'           => 'VAWC Leave',
        'sabbatical'     => 'Sabbatical Leave',
        'admin_vacation' => 'Academic/Vacation Leave',
        'admin_special'  => 'Special Leave Privileges',
        'study_leave'    => 'Study Leave',
    ];

    foreach ($candidates as $key => $label) {
        $total = (int) ($balance[$key . '_total'] ?? 0);
        $used  = (int) ($balance[$key . '_used']  ?? 0);
        if ($total > 0) {
            $activeLeaves[] = [
                'key'     => $key,
                'label'   => $label,
                'total'   => $total,
                'used'    => $used,
                'balance' => max(0, $total - $used),
                'pct'     => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            ];
            $totalAvailable += max(0, $total - $used);
            $totalUsedDays  += $used;
        }
    }
} catch (Throwable $e) {
    $formError = 'Database error: ' . $e->getMessage();
    error_log('[leave-request] ' . $e->getMessage());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<style>
    #new-total-days-hint.text-danger { font-weight: 600; }
    #new-total-days-hint.text-success { font-weight: 500; }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessageBody"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-plane-departure fs-5"></i>
            </span>
            Leave Management
        </h4>
        <p class="text-secondary small mb-0">Submit new leave applications and track your balances for the
            <strong><?= htmlspecialchars($academicYear) ?></strong> academic year.
        </p>
    </div>

    <?php if (!empty($activeLeaves)): ?>
        <button class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium"
                data-bs-toggle="modal" data-bs-target="#newLeaveModal">
            <i class="fas fa-plus fs-6"></i>
            <span>New Request</span>
        </button>
    <?php else: ?>
        <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium" disabled>
            <i class="fas fa-hourglass-half fs-6"></i>
            <span>Setting Up Leave Credits</span>
        </button>
    <?php endif; ?>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card primary border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Leave Credits</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= $totalAvailable + $totalUsedDays ?> <small class="text-muted fs-6">days</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #fd7e14; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #fd7e14;"><i class="fas fa-business-time"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Consumed</h6>
                    <h4 class="mb-0 fw-bold" style="color: #fd7e14;"><?= $totalUsedDays ?> <small class="text-muted fs-6">days used</small></h4>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100" role="button"
                 tabindex="0" data-bs-toggle="modal" data-bs-target="#balanceBreakdownModal" style="cursor: pointer;">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #28a745; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #28a745;"><i class="fas fa-battery-three-quarters"></i></div>
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Remaining Balance</h6>
                    <h4 class="mb-0 fw-bold" style="color: #28a745;"><?= $totalAvailable ?> <small class="text-muted fs-6">days left</small></h4>
                </div>
                <i class="fas fa-chevron-right text-success opacity-50"></i>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: #dc3545; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #dc3545;"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Approval</h6>
                    <h4 class="mb-0 fw-bold" style="color: #dc3545;"><?= $pendingCount + $documentRequiredCount ?> <small class="text-muted fs-6">requests</small></h4>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal: Leave Balance Breakdown -->
<div class="modal fade" id="balanceBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-list-ul text-success"></i>
                    Leave Balance Breakdown
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-2">
                        <?= htmlspecialchars($academicYear) ?>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <?php if (!empty($activeLeaves)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light border-bottom border-light-subtle">
                                <tr>
                                    <th class="ps-4 text-uppercase small text-secondary fw-semibold">Leave Type</th>
                                    <th class="text-end text-uppercase small text-secondary fw-semibold">Total</th>
                                    <th class="text-end text-uppercase small text-secondary fw-semibold">Used</th>
                                    <th class="text-end text-uppercase small text-secondary fw-semibold">Remaining</th>
                                    <th class="pe-4 text-uppercase small text-secondary fw-semibold" style="width: 160px;">Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeLeaves as $lv):
                                    $pct = $lv['pct'];
                                    $barColor = '#28a745';
                                    if ($pct >= 75) $barColor = '#dc3545';
                                    elseif ($pct >= 40) $barColor = '#fd7e14';
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-medium"><?= htmlspecialchars($lv['label']) ?></td>
                                        <td class="text-end text-muted"><?= $lv['total'] ?></td>
                                        <td class="text-end text-muted"><?= $lv['used'] ?></td>
                                        <td class="text-end fw-bold" style="color: <?= $barColor ?>;"><?= $lv['balance'] ?></td>
                                        <td class="pe-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: <?= $pct ?>%; background-color: <?= $barColor ?>;"
                                                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted" style="font-size: 11px; min-width: 40px; text-align: right;">
                                                    <?= $pct ?>%
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-top bg-light">
                                <tr>
                                    <td class="ps-4 fw-bold small text-uppercase">Overall Total</td>
                                    <td class="text-end fw-bold"><?= $totalAvailable + $totalUsedDays ?></td>
                                    <td class="text-end fw-bold"><?= $totalUsedDays ?></td>
                                    <td class="text-end fw-bold text-success"><?= $totalAvailable ?></td>
                                    <td class="pe-4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-inbox fs-2 mb-3 opacity-50"></i>
                        <p class="mb-0 fw-medium">No leave categories available.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Requests Data Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-history text-primary"></i>
            Application Records
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom border-light-subtle">
                    <tr>
                        <th class="ps-4 text-uppercase small text-secondary fw-semibold">ID</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Type</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Duration</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Day(s)</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Status</th>
                        <th class="text-uppercase small text-secondary fw-semibold">Filed Date</th>
                        <th class="pe-4 text-end text-uppercase small text-secondary fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaveRequests)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No leave requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveRequests as $row):
                            $dbId   = (int) ($row['id'] ?? 0);
                            $refId  = htmlspecialchars($row['request_ref'] ?? ('LR-' . $dbId), ENT_QUOTES, 'UTF-8');
                            $type   = htmlspecialchars($row['leave_type']  ?? '', ENT_QUOTES, 'UTF-8');
                            $start  = htmlspecialchars($row['start_date']  ?? '', ENT_QUOTES, 'UTF-8');
                            $end    = htmlspecialchars($row['end_date']    ?? '', ENT_QUOTES, 'UTF-8');
                            $days   = (int) ($row['days'] ?? 0);

                            $status          = $row['status']           ?? '';
                            $screeningStatus = $row['screening_status'] ?? '';
                            $normalizedStatus    = str_replace('_', ' ', strtolower(trim((string) $status)));
                            $normalizedScreening = str_replace('_', ' ', strtolower(trim((string) $screeningStatus)));

                            $isReturned = in_array($normalizedStatus, ['document required', 'returned'], true)
                                       || in_array($normalizedScreening, ['document required', 'returned'], true);

                            if ($isReturned) {
                                $statusText = 'Returned';
                                $badgeClass = 'bg-warning text-dark';
                            } else {
                                $statusText = ucwords(str_replace('_', ' ', strtolower(trim((string) $status))));
                                $badgeClass = 'bg-secondary';
                                if ($normalizedStatus === 'pending')  $badgeClass = 'bg-warning text-dark';
                                if ($normalizedStatus === 'approved') $badgeClass = 'bg-success';
                                if ($normalizedStatus === 'rejected') $badgeClass = 'bg-danger';
                                if ($normalizedStatus === 'finished') $badgeClass = 'bg-info text-white';
                            }

                            $fileDate = isset($row['created_at']) ? htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') : '';
                            $remarks  = $row['return_reason'] ?? $row['secretary_reason'] ?? $row['remarks'] ?? $row['comment'] ?? $row['feedback'] ?? $row['secretary_remarks'] ?? '';
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-primary"><?= $refId ?></td>
                                <td><span class="badge bg-light text-dark border px-2 py-1 rounded-2"><?= $type ?></span></td>
                                <td class="small">
                                    <i class="far fa-calendar text-muted me-1"></i><?= $start ?>
                                    <i class="fas fa-arrow-right text-muted mx-1 fs-7"></i><?= $end ?>
                                </td>
                                <td><span class="fw-medium"><?= $days ?></span> <span class="text-muted small">d</span></td>
                                <td><span class="badge border rounded-pill px-2.5 py-1.5 <?= $badgeClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="small text-muted"><?= $fileDate ?></td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($isReturned): ?>
                                            <button class="btn btn-sm btn-warning rounded-circle" title="Edit & Resubmit" onclick='editDetails(<?= htmlspecialchars(json_encode([
                                                'db_id' => $dbId,
                                                'id' => $refId,
                                                'type' => $type,
                                                'start' => $start,
                                                'end' => $end,
                                                'reason' => $row['reason'] ?? '',
                                                'remarks' => $remarks
                                            ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-light text-primary rounded-circle" title="View Details"
                                            onclick='viewDetails(<?= htmlspecialchars(json_encode([
                                                'db_id' => $dbId,
                                                'id' => $refId,
                                                'type' => $type,
                                                'start' => $start,
                                                'end' => $end,
                                                'days' => $days,
                                                'status' => $statusText,
                                                'date' => $fileDate,
                                                'reason' => $row['reason'] ?? '',
                                                'status_raw' => $isReturned ? 'returned' : $normalizedStatus,
                                                'remarks' => $remarks,
                                                'documents' => $row['documents'] ?? '',
                                                'approval_timestamp' => $row['approval_timestamp'] ?? '',
                                                'approver_signature' => $row['approver_signature'] ?? '',
                                                'download_url' => BASE_URL . '/modules/faculty/reports/print-leave-form.php?id=' . $dbId
                                            ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Leave Request -->
<?php if (!empty($activeLeaves)): ?>
    <div class="modal fade" id="newLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-file-signature text-primary"></i>
                        Apply for Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="leaveRequestForm" method="post" enctype="multipart/form-data" novalidate>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type" id="new-leave-type" class="form-select bg-light" required>
                                <option value="" disabled selected>Select category...</option>
                                <?php foreach ($activeLeaves as $lv): ?>
                                    <option value="<?= htmlspecialchars($lv['label']) ?>">
                                        <?= htmlspecialchars($lv['label']) ?> — <?= $lv['balance'] ?> of <?= $lv['total'] ?> days remaining
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1" id="new-leave-hint"></small>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-medium">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="new-start-date" class="form-control bg-light" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-medium">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="new-end-date" class="form-control bg-light" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control bg-light" rows="3" placeholder="Provide details regarding your request..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Supporting Documents (Optional)</label>
                            <input type="file" name="document" class="form-control bg-light">
                        </div>
                        <div class="p-3 bg-primary bg-opacity-10 rounded-3 d-flex align-items-center gap-2 text-primary small">
                            <i class="fas fa-info-circle fs-6"></i>
                            <span id="new-total-days-hint">Select a leave type and dates to see the impact on your balance.</span>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" name="submit_leave" class="btn btn-primary rounded-pill px-4">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal: Edit / Resubmit -->
<div class="modal fade" id="editLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-warning">
                    <i class="fas fa-edit"></i>
                    Edit & Resubmit Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLeaveRequestForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_request_id" id="edit-req-db-id">
                <div class="modal-body p-4">
                    <div id="edit-remarks-container" class="mb-3 d-none">
                        <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3">
                            <span class="text-danger small fw-bold d-block mb-1">
                                <i class="fas fa-exclamation-circle me-1"></i> Secretary's Reason for Returning:
                            </span>
                            <p class="mb-0 text-dark small fw-medium" id="edit-remarks-text">-</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" id="edit-leave-type" class="form-select bg-light" required>
                            <?php foreach ($activeLeaves as $lv): ?>
                                <option value="<?= htmlspecialchars($lv['label']) ?>"><?= htmlspecialchars($lv['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="edit-start-date" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="edit-end-date" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="edit-reason" class="form-control bg-light" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Replace Supporting Document</label>
                        <input type="file" name="document" class="form-control bg-light">
                        <small class="text-muted d-block mt-1">Leave blank to keep the existing document.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_leave" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">Update & Resubmit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <span class="text-muted small d-block">Reference ID</span>
                    <span class="fw-bold text-dark" id="modal-req-id">-</span>
                </div>

                <div id="modal-remarks-container" class="mb-3 d-none">
                    <span class="text-danger small fw-bold d-block mb-1"><i class="fas fa-exclamation-circle me-1"></i>
                        Secretary Feedback / Remarks</span>
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 text-dark small fw-medium" id="modal-remarks">-</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Leave Category</span>
                        <span class="fw-semibold" id="modal-leave-type">-</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Duration</span>
                        <span class="fw-semibold"><span id="modal-days">-</span> Day(s)</span>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Reason Provided</span>
                    <p class="mb-0 bg-light p-3 rounded-3 text-dark small" id="modal-reason">-</p>
                </div>

                <div id="modal-approval-box" class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 mb-3 d-none">
                    <span class="text-success small fw-bold d-block mb-2">
                        <i class="fas fa-check-circle me-1"></i> Approved by Department Head
                    </span>
                    <div class="row g-2 text-dark small">
                        <div class="col-12">
                            <span class="text-muted">Approved Date & Time:</span>
                            <span class="fw-semibold" id="modal-approval-time">-</span>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block mb-1">Department Head Signature:</span>
                            <div class="bg-white p-2 rounded border text-center">
                                <div id="modal-signature-container">
                                    <span class="text-muted fst-italic">No signature available</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="modal-download-wrapper" class="d-none">
                        <a href="#" id="modal-download-btn" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 fw-bold text-white shadow-sm">
                            <i class="fas fa-download me-1"></i> Download / Print Form
                        </a>
                    </div>

                    <div id="modal-action-wrapper" class="d-none ms-auto">
                        <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark" id="modal-edit-btn">
                            <i class="fas fa-edit me-1"></i> Edit & Resubmit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
    let currentViewData = null;

    const LEAVE_BALANCES = <?= json_encode(array_column($activeLeaves, 'balance', 'label')) ?>;

    function showLeaveToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toastMessageBody');
        const closeBtn = toastEl.querySelector('.btn-close');

        toastEl.className = 'toast align-items-center text-white border-0 shadow-lg';
        toastEl.style.backgroundColor = (type === 'danger' || type === 'error')
            ? '#842029'
            : (type === 'warning' ? '#664d03' : '#0f5132');

        closeBtn.classList.remove('btn-close-white');
        closeBtn.style.filter = 'invert(1) grayscale(100%) brightness(200%)';

        const iconClass = (type === 'danger' || type === 'error')
            ? 'fas fa-exclamation-circle'
            : (type === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-check-circle');

        toastBody.innerHTML = `<i class="${iconClass} fs-5 text-white"></i> <span class="text-white">${message}</span>`;

        new bootstrap.Toast(toastEl, { delay: 5000 }).show();
    }

    function viewDetails(data) {
        currentViewData = data;
        document.getElementById('modal-req-id').textContent    = data.id;
        document.getElementById('modal-leave-type').textContent = data.type;
        document.getElementById('modal-days').textContent      = data.days;
        document.getElementById('modal-reason').textContent    = data.reason || 'N/A';

        const remarksContainer = document.getElementById('modal-remarks-container');
        const remarksEl = document.getElementById('modal-remarks');
        if (data.remarks && data.remarks.trim() !== '') {
            remarksEl.textContent = data.remarks;
            remarksContainer.classList.remove('d-none');
        } else {
            remarksContainer.classList.add('d-none');
        }

        const approvalBox        = document.getElementById('modal-approval-box');
        const downloadWrapper    = document.getElementById('modal-download-wrapper');
        const downloadBtn        = document.getElementById('modal-download-btn');
        const signatureContainer = document.getElementById('modal-signature-container');

        if (data.status_raw === 'approved') {
            approvalBox.classList.remove('d-none');
            document.getElementById('modal-approval-time').textContent = data.approval_timestamp || 'N/A';

            if (data.approver_signature && data.approver_signature.trim() !== '') {
                signatureContainer.innerHTML = `<img src="${BASE_URL}/${data.approver_signature}" alt="Signature" style="max-height: 50px;" class="mx-auto d-block">`;
            } else {
                signatureContainer.innerHTML = `<span class="text-success fw-semibold small">Digitally Approved</span>`;
            }
            downloadBtn.href = data.download_url;
            downloadWrapper.classList.remove('d-none');
        } else {
            approvalBox.classList.add('d-none');
            downloadWrapper.classList.add('d-none');
        }

        const actionWrapper = document.getElementById('modal-action-wrapper');
        if (['document required', 'returned', 'document_required'].includes(data.status_raw)) {
            actionWrapper.classList.remove('d-none');
            document.getElementById('modal-edit-btn').onclick = function () {
                bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
                editDetails(data);
            };
        } else {
            actionWrapper.classList.add('d-none');
        }

        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    function editDetails(data) {
        document.getElementById('edit-req-db-id').value   = data.db_id;
        document.getElementById('edit-leave-type').value  = data.type;
        document.getElementById('edit-start-date').value  = data.start;
        document.getElementById('edit-end-date').value    = data.end;
        document.getElementById('edit-reason').value      = data.reason;

        const editRemarksContainer = document.getElementById('edit-remarks-container');
        const editRemarksText      = document.getElementById('edit-remarks-text');
        if (data.remarks && data.remarks.trim() !== '') {
            editRemarksText.textContent = data.remarks;
            editRemarksContainer.classList.remove('d-none');
        } else {
            editRemarksContainer.classList.add('d-none');
        }

        new bootstrap.Modal(document.getElementById('editLeaveModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('new-leave-type');
        const startInput = document.getElementById('new-start-date');
        const endInput   = document.getElementById('new-end-date');
        const hint       = document.getElementById('new-total-days-hint');
        const typeHint   = document.getElementById('new-leave-hint');

        let leaveFormValid = true;
        let leaveFormError = '';

        function updateHint() {
            leaveFormValid = true;
            leaveFormError = '';

            [startInput, endInput].forEach(el => {
                if (!el) return;
                el.classList.remove('is-invalid', 'is-valid');
            });

            if (!typeSelect) return;

            const label = typeSelect.value;
            const remaining = LEAVE_BALANCES[label] ?? null;

            if (typeHint) {
                typeHint.textContent = remaining !== null
                    ? `You have ${remaining} day(s) remaining for this category.`
                    : '';
            }

            if (!label) {
                if (hint) {
                    hint.textContent = 'Select a leave type and dates to see the impact on your balance.';
                    hint.classList.remove('text-danger', 'text-success', 'fw-semibold');
                    hint.classList.add('text-primary');
                }
                return;
            }

            if (!startInput.value || !endInput.value) {
                if (hint) {
                    hint.textContent = 'Select a leave type and dates to see the impact on your balance.';
                    hint.classList.remove('text-danger', 'text-success', 'fw-semibold');
                    hint.classList.add('text-primary');
                }
                return;
            }

            const s = new Date(startInput.value);
            const e = new Date(endInput.value);

            if (isNaN(s) || isNaN(e) || e < s) {
                leaveFormValid = false;
                leaveFormError = 'End date must be the same as or after the start date.';
                startInput.classList.add('is-invalid');
                endInput.classList.add('is-invalid');
                if (hint) {
                    hint.textContent = leaveFormError;
                    hint.classList.remove('text-primary', 'text-success');
                    hint.classList.add('text-danger', 'fw-semibold');
                }
                return;
            }

            startInput.classList.add('is-valid');
            endInput.classList.add('is-valid');

            const days = Math.floor((e - s) / 86400000) + 1;

            if (remaining === null) {
                if (hint) {
                    hint.textContent = `This request is ${days} day(s).`;
                    hint.classList.remove('text-danger', 'text-success', 'fw-semibold');
                    hint.classList.add('text-primary');
                }
                return;
            }

            if (days > remaining) {
                leaveFormValid = false;
                leaveFormError = `Insufficient ${label} balance: you requested ${days} day(s) but only ${remaining} day(s) remain.`;

                if (hint) {
                    hint.textContent = leaveFormError;
                    hint.classList.remove('text-primary', 'text-success');
                    hint.classList.add('text-danger', 'fw-semibold');
                }
                startInput.classList.remove('is-valid');
                endInput.classList.remove('is-valid');
                startInput.classList.add('is-invalid');
                endInput.classList.add('is-invalid');
                return;
            }

            if (hint) {
                hint.textContent = `This request is ${days} day(s). You will have ${remaining - days} day(s) left after submission.`;
                hint.classList.remove('text-danger', 'text-primary', 'fw-semibold');
                hint.classList.add('text-success');
            }
        }

        if (typeSelect) typeSelect.addEventListener('change', updateHint);
        if (startInput) startInput.addEventListener('change', updateHint);
        if (endInput)   endInput.addEventListener('change', updateHint);

        const leaveForm = document.getElementById('leaveRequestForm');
        if (leaveForm) {
            leaveForm.addEventListener('submit', function (ev) {
                updateHint();
                if (!leaveFormValid) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    showLeaveToast(leaveFormError || 'Please fix the highlighted fields before submitting.', 'danger');
                    return false;
                }
            });
        }

        const phpError   = <?= json_encode($formError) ?>;
        const phpSuccess = <?= json_encode($formSuccess) ?>;

        let message = '';
        let type = 'success';
        if (phpError)        { message = phpError;   type = 'danger'; }
        else if (phpSuccess) { message = phpSuccess; type = 'success'; }

        if (message) showLeaveToast(message, type);
    });

    document.querySelectorAll('[data-bs-target="#balanceBreakdownModal"]').forEach(function (el) {
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                new bootstrap.Modal(document.getElementById('balanceBreakdownModal')).show();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>