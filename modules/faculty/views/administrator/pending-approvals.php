<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/FacultyController.php';

requireAuth();

$pdo = db();
$message = '';
$messageType = 'success';

// Ensure CSRF Token initialized
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Approval / Rejection Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $message = "Invalid security token. Please try again.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            try {
                $pdo->beginTransaction();

                if ($action === 'approve') {
                    // 1. Fetch first_name, last_name, and email directly for the specific owner/user being approved
                    $profileStmt = $pdo->prepare("SELECT first_name, last_name, email FROM faculty_profiles WHERE user_id = :user_id LIMIT 1");
                    $profileStmt->execute([':user_id' => $userId]);
                    $profileData = $profileStmt->fetch(PDO::FETCH_ASSOC);

                    $firstName  = trim($profileData['first_name'] ?? '');
                    $lastName   = trim($profileData['last_name'] ?? '');
                    $facultyEmail = trim($profileData['email'] ?? '');
                    $fullName   = trim($firstName . ' ' . $lastName);

                    // Fallback: If faculty profile missing name or email, check sms2_db.users for this specific user
                    if ($lastName === '' || $facultyEmail === '') {
                        $userStmt = $pdo->prepare("SELECT full_name, email FROM sms2_db.users WHERE id = :user_id LIMIT 1");
                        $userStmt->execute([':user_id' => $userId]);
                        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userData) {
                            if ($fullName === '') {
                                $fullName = trim($userData['full_name'] ?? '');
                            }
                            if ($facultyEmail === '') {
                                $facultyEmail = trim($userData['email'] ?? '');
                            }
                        }
                        $nameParts = array_filter(explode(' ', $fullName));
                        $lastName = !empty($nameParts) ? end($nameParts) : 'User';
                    }

                    // 2. Generate dynamic password (e.g., "Mercer@2026")
                    $defaultPassword = ucfirst(strtolower($lastName)) . '@2026';
                    $hashedPassword  = password_hash($defaultPassword, PASSWORD_DEFAULT);

                    // 3. Activate account, SET full_name, update password_hash, and enforce password change
                    $stmt1 = $pdo->prepare("
                        UPDATE sms2_db.users 
                        SET status = 'active',
                            full_name = :full_name,
                            password_hash = :password_hash,
                            must_change_password = 1 
                        WHERE id = :user_id
                    ");
                    $stmt1->execute([
                        ':full_name'     => $fullName,
                        ':password_hash' => $hashedPassword,
                        ':user_id'       => $userId
                    ]);

                    // 4. Activate faculty profile
                    $stmt2 = $pdo->prepare("UPDATE faculty_profiles SET profile_status = 'Active', request_status = 'approved' WHERE user_id = :user_id");
                    $stmt2->execute([':user_id' => $userId]);

                    // 5. Ensure a bridging faculty record exists.
                    try {
                        $fullProfileStmt = $pdo->prepare("SELECT * FROM faculty_profiles WHERE user_id = :user_id LIMIT 1");
                        $fullProfileStmt->execute([':user_id' => $userId]);
                        $fp = $fullProfileStmt->fetch(PDO::FETCH_ASSOC);

                        if ($fp) {
                            $emailParam = !empty($facultyEmail) ? $facultyEmail : (!empty($fp['email']) ? $fp['email'] : null);

                            $checkFacultyStmt = $pdo->prepare("
                                SELECT faculty_id FROM faculty
                                WHERE (:email_check IS NOT NULL AND email = :email_val)
                                   OR faculty_no = :faculty_no
                                LIMIT 1
                            ");
                            $checkFacultyStmt->execute([
                                ':email_check' => $emailParam,
                                ':email_val'   => $emailParam,
                                ':faculty_no'  => $fp['faculty_id'] ?? '',
                            ]);
                            $existingFacultyId = $checkFacultyStmt->fetchColumn();

                            if (!$existingFacultyId) {
                                $departmentId = null;
                                if (!empty($fp['designated_department'])) {
                                    $deptIdStmt = $pdo->prepare("SELECT department_id FROM departments WHERE code = :code LIMIT 1");
                                    $deptIdStmt->execute([':code' => $fp['designated_department']]);
                                    $departmentId = $deptIdStmt->fetchColumn() ?: null;
                                }

                                $insertFacultyStmt = $pdo->prepare("
                                    INSERT INTO faculty (
                                        faculty_no, external_user_id, first_name, middle_name, last_name, suffix,
                                        birthdate, sex, phone, email, department_id, position,
                                        academic_rank, employment_status, profile_status, overall_rating,
                                        hired_date, contractual_end_date, created_at, updated_at
                                    ) VALUES (
                                        :faculty_no, :external_user_id, :first_name, :middle_name, :last_name, :suffix,
                                        :birthdate, :sex, :phone, :email, :department_id, :position,
                                        'instructor', :employment_status, 'Active', 0.00,
                                        :hired_date, :contractual_end_date, NOW(), NOW()
                                    )
                                ");
                                $insertFacultyStmt->execute([
                                    ':faculty_no'           => $fp['faculty_id'] ?? null,
                                    ':external_user_id'     => (string) $userId,
                                    ':first_name'           => $fp['first_name'] ?? '',
                                    ':middle_name'          => $fp['middle_name'] ?? null,
                                    ':last_name'            => $fp['last_name'] ?? '',
                                    ':suffix'               => $fp['suffix'] ?? null,
                                    ':birthdate'            => !empty($fp['birthdate']) ? $fp['birthdate'] : null,
                                    ':sex'                  => !empty($fp['sex']) ? strtolower($fp['sex']) : null,
                                    ':phone'                => $fp['phone'] ?? null,
                                    ':email'                => $facultyEmail,
                                    ':department_id'        => $departmentId,
                                    ':position'             => $fp['position'] ?? null,
                                    ':employment_status'    => $fp['employment_status'] ?: 'Probationary',
                                    ':hired_date'           => !empty($fp['hired_date']) ? $fp['hired_date'] : null,
                                    ':contractual_end_date' => !empty($fp['contractual_end']) ? $fp['contractual_end'] : (!empty($fp['contractual_end_date']) ? $fp['contractual_end_date'] : null),
                                ]);
                            }
                        }
                    } catch (Throwable $linkError) {
                        error_log('Auto-link faculty record on approval failed for user_id=' . $userId . ': ' . $linkError->getMessage());
                    }

                    $pdo->commit();

                    // 6. Send notification email securely to the exact owner's email address using PHPMailer
                    if (!empty($facultyEmail)) {
                        require_once __DIR__ . '/../../../../includes/Exception.php';
                        require_once __DIR__ . '/../../../../includes/PHPMailer.php';
                        require_once __DIR__ . '/../../../../includes/SMTP.php';

                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'jcespejo002@gmail.com';             
                            $mail->Password   = 'cshwohpgllkqdtga';          
                            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            // Recipients - explicitly addressed to the owner
                            $mail->setFrom('jcespejo002@gmail.com', 'Bestlink College No-Reply');
                            $mail->addAddress($facultyEmail, $fullName);         

                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = "Your Faculty Account Has Been Approved";
                            $logoUrl  = rtrim(BASE_URL, '/') . '/images/bestlink.png';
                            $logoHtml = "<img src='" . htmlspecialchars($logoUrl) . "' width='40' height='40' alt='Bestlink College of the Philippines' style='display:block; width:40px; height:40px; border-radius:6px;'>";
                            
                            $mail->Body = "
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset='UTF-8'>
                                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                                <title>Account Approved</title>
                                <style>
                                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; }
                                    table { border-collapse: collapse; }
                                    @media screen and (max-width: 600px) {
                                        .email-container { width: 100% !important; }
                                        .content-padding { padding: 22px !important; }
                                    }
                                </style>
                            </head>
                            <body style='margin: 0; padding: 0; background-color: #f4f4f4;'>
                                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f4f4f4; padding: 24px 0;'>
                                    <tr>
                                        <td align='center'>
                                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='600' class='email-container' style='background-color: #121212; border-radius: 10px; overflow: hidden; border: 1px solid #333; width: 100%; max-width: 600px;'>
                                                <tr>
                                                    <td style='background-color: #0077b3; height: 4px; line-height: 4px; font-size: 0;'>&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style='background-color: #0093dd; padding: 22px 25px;'>
                                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                                            <tr>
                                                                <td width='46' valign='middle' style='padding-right: 14px;'>{$logoHtml}</td>
                                                                <td valign='middle'>
                                                                    <div style='color: #ffffff; font-size: 15px; font-weight: 700;'>BESTLINK COLLEGE OF THE PHILIPPINES</div>
                                                                    <div style='color: rgba(255,255,255,0.75); font-size: 11px; font-weight: 600; margin-top: 4px; text-transform: uppercase;'>Account Approved</div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class='content-padding' style='padding: 32px; color: #e0e0e0; font-size: 14px; line-height: 1.65;'>
                                                        <p style='margin: 0 0 16px 0;'>Hello " . htmlspecialchars($fullName) . ",</p>
                                                        <p style='margin: 0 0 22px 0;'>Your faculty account has been approved. Use the secure credentials below to log in and update your password.</p>
                                                        
                                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #1e1e1e; border-radius: 8px; border: 1px solid #2d2d2d; margin-bottom: 22px;'>
                                                            <tr>
                                                                <td style='padding: 18px 20px;'>
                                                                    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                                                        <tr>
                                                                            <td width='95' style='color: #9a9a9a; font-weight: 600; font-size: 12.5px; padding-bottom: 10px; vertical-align: top;'>Username</td>
                                                                            <td style='color: #ffffff; font-family: monospace; font-size: 14px; font-weight: bold; padding-bottom: 10px; word-break: break-all;'>" . htmlspecialchars($facultyEmail) . "</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width='95' style='color: #9a9a9a; font-weight: 600; font-size: 12.5px; vertical-align: top;'>Password</td>
                                                                            <td style='color: #ffffff; font-family: monospace; font-size: 14px; font-weight: bold; word-break: break-all;'>" . htmlspecialchars($defaultPassword) . "</td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        
                                                        <p style='margin: 0 0 22px 0; color: #9a9a9a; font-size: 12.5px;'>For security reasons, this is a temporary password and you will be asked to update it upon your first login.</p>
                                                        
                                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                                            <tr>
                                                                <td style='color: #bbbbbb; padding-top: 6px; border-top: 1px solid #2a2a2a;'>
                                                                    <p style='margin: 14px 0 0 0;'>Regards,</p>
                                                                    <p style='margin: 4px 0 0 0; font-weight: bold; color: #ffffff;'>Admin</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style='background-color: #0a0a0a; padding: 14px 25px; text-align: center;'>
                                                        <span style='color: #6b6b6b; font-size: 11px;'>&copy; " . date('Y') . " Bestlink College of the Philippines &middot; Faculty Management System</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </body>
                            </html>
                            ";

                            $mail->send();
                            $message = "Account successfully approved and credentials successfully emailed to owner: " . htmlspecialchars($facultyEmail) . ".";
                            $messageType = "success";
                        } catch (Exception $e) {
                            error_log("PHPMailer Error: " . $mail->ErrorInfo);
                            $message = "Account approved, but email delivery to " . htmlspecialchars($facultyEmail) . " failed. Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
                            $messageType = "warning";
                        }
                    } else {
                        $message = "Account approved successfully, but no valid email address was found on record for this owner.";
                        $messageType = "warning";
                    }

                } elseif ($action === 'reject') {
                    $stmt1 = $pdo->prepare("UPDATE sms2_db.users SET status = 'rejected' WHERE id = :user_id");
                    $stmt1->execute([':user_id' => $userId]);

                    $stmt2 = $pdo->prepare("UPDATE faculty_profiles SET profile_status = 'Rejected', request_status = 'rejected' WHERE user_id = :user_id");
                    $stmt2->execute([':user_id' => $userId]);

                    $pdo->commit();
                    $message = "Account request has been rejected successfully.";
                    $messageType = 'warning';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Approval Processing Error: " . $e->getMessage());
                $message = "An error occurred while updating the account status.";
                $messageType = "danger";
            }
        }
    }
}

// Load pending accounts
$stmt = $pdo->query("
    SELECT fp.*, u.status AS account_status, u.id AS auth_user_id
    FROM faculty_profiles fp
    JOIN sms2_db.users u ON fp.user_id = u.id
    WHERE u.status = 'pending_approval' OR fp.profile_status = 'Pending Approval'
    ORDER BY fp.created_at DESC
");
$pendingFaculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Quick Stats for Dashboard Cards
$totalPending = count($pendingFaculty);

$departments = array_map(function($item) {
    return $item['designated_department'] ?? $item['designated_dept'] ?? '';
}, $pendingFaculty);
$uniqueDepts = count(array_unique(array_filter($departments)));

$fullTimeCount = count(array_filter($pendingFaculty, fn($item) => strtolower($item['employment_status'] ?? '') === 'regular' || strtolower($item['employment_status'] ?? '') === 'active'));
$partTimeCount = $totalPending - $fullTimeCount;

// Page configuration & breadcrumbs
$pageTitle    = 'Pending Approvals';
$activeModule = 'faculty';
$activePage   = 'pending-approvals';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Pending Approvals', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid py-3 px-2 px-md-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h4 h3-md text-body mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-user-clock text-sms-primary me-2"></i>
                <span>Pending Account Approvals</span>
            </h1>
            <p class="text-body-secondary small mb-0">Review and verify faculty accounts requested by Department Heads.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-7 fs-md-6 px-3 py-2 shadow-sm rounded-pill">
                <i class="fas fa-hourglass-half me-1"></i> <?= $totalPending ?> Action Needed
            </span>
        </div>
    </div>

    <!-- Alert Messages Banner -->
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-4 shadow-sm fs-7 border-0 ps-4 py-3 mb-4" role="alert" style="background-color: var(--bs-body-bg); border-left: 4px solid var(--bs-<?= $messageType === 'success' ? 'success' : ($messageType === 'warning' ? 'warning' : 'danger') ?>) !important; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;">
            <div class="d-flex align-items-center">
                <div class="fs-5 me-3 text-<?= $messageType === 'success' ? 'success' : ($messageType === 'warning' ? 'warning' : 'danger') ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1 text-body"><?= $messageType === 'success' ? 'Success' : ($messageType === 'warning' ? 'Notice' : 'Error') ?></h6>
                    <p class="mb-0 text-body-secondary"><?= htmlspecialchars($message) ?></p>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Cards Section -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning fs-4">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Awaiting Review</h6>
                        <h4 class="mb-0 fw-bold"><?= $totalPending ?></h4>
                        <small class="text-warning fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-hourglass-half me-1"></i>Action needed
                        </small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-info fs-4">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Departments</h6>
                        <h4 class="mb-0 fw-bold"><?= $uniqueDepts ?></h4>
                        <small class="text-info fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-layer-group me-1"></i>Active units
                        </small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-success fs-4">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Full-Time Requests</h6>
                        <h4 class="mb-0 fw-bold"><?= $fullTimeCount ?></h4>
                        <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-user-check me-1"></i>Regular staff
                        </small>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary border shadow-sm position-relative h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 text-primary fs-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Part-Time / Other</h6>
                        <h4 class="mb-0 fw-bold"><?= $partTimeCount ?></h4>
                        <small class="text-primary fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-user-clock me-1"></i>Adjunct & non-regular
                        </small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Main Approvals Table Card -->
    <div class="card bg-body-tertiary border border-light-subtle shadow-sm rounded-4">
        <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title text-body mb-0 fw-bold fs-6"><i class="fas fa-list-alt me-2 text-info"></i>Pending Queue</h5>
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body text-body-secondary border-light-subtle">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="pendingSearch" class="form-control bg-body text-body border-light-subtle fs-7 shadow-none" placeholder="Search pending requests..." onkeyup="filterPending()">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-7">
                <thead>
                    <tr class="text-body-secondary border-light-subtle">
                        <th style="width: 60px;">Avatar</th>
                        <th>Faculty ID</th>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Department</th>
                        <th class="d-none d-sm-table-cell">Position</th>
                        <th>Employment</th>
                        <th class="text-end text-sm-center" style="min-width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <?php if (empty($pendingFaculty)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-5">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success d-block opacity-75"></i>
                                <h5 class="fs-6 fw-bold">All caught up!</h5>
                                <p class="mb-0 fs-7">There are no pending account approval requests at this time.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingFaculty as $row): ?>
                            <?php 
                                $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                $deptLabel = FacultyController::getDepartmentLabel((string)($row['designated_department'] ?? $row['designated_dept'] ?? ''));
                                
                                $initials = '';
                                foreach (array_filter(explode(' ', $fullName)) as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                    if (strlen($initials) >= 2) break;
                                }
                                if ($initials === '') $initials = 'NA';
                            ?>
                            <tr>
                                <td>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark fw-bold shadow-sm" 
                                        style="width: 36px; height: 36px; font-size: 13px;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                </td>
                                <td class="fw-bold text-info"><?= htmlspecialchars($row['faculty_id'] ?? '—') ?></td>
                                <td class="fw-semibold text-body"><?= htmlspecialchars($fullName) ?></td>
                                <td class="d-none d-md-table-cell"><span class="badge bg-body-secondary text-body border border-light-subtle"><?= htmlspecialchars($deptLabel) ?></span></td>
                                <td class="d-none d-sm-table-cell"><?= htmlspecialchars($row['position'] ?? '—') ?></td>
                                <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= htmlspecialchars(ucfirst($row['employment_status'] ?? 'Pending')) ?></span></td>
                                <td class="text-end text-sm-center">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end justify-content-sm-center">
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-3 px-2" onclick="inspectRequest(this)"
                                            data-user-id="<?= (int)$row['auth_user_id'] ?>"
                                            data-faculty-id="<?= htmlspecialchars($row['faculty_id'] ?? '') ?>"
                                            data-full-name="<?= htmlspecialchars($fullName) ?>"
                                            data-email="<?= htmlspecialchars($row['email'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($row['phone'] ?? '') ?>"
                                            data-dept="<?= htmlspecialchars($deptLabel) ?>"
                                            data-position="<?= htmlspecialchars($row['position'] ?? '') ?>"
                                            data-employment="<?= htmlspecialchars($row['employment_status'] ?? '') ?>"
                                            data-rank="<?= htmlspecialchars($row['academic_rank'] ?? '') ?>"
                                            data-tier="<?= htmlspecialchars($row['tier'] ?? '') ?>"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-success rounded-3 fw-bold px-2" onclick="openActionModal('approve', '<?= (int)$row['auth_user_id'] ?>', '<?= htmlspecialchars($fullName, ENT_QUOTES) ?>')" title="Approve">
                                            <i class="fas fa-check"></i><span class="d-none d-sm-inline ms-1"> Approve</span>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 fw-bold px-2" onclick="openActionModal('reject', '<?= (int)$row['auth_user_id'] ?>', '<?= htmlspecialchars($fullName, ENT_QUOTES) ?>')" title="Reject">
                                            <i class="fas fa-times"></i><span class="d-none d-sm-inline ms-1"> Reject</span>
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

<!-- Floating Toast Container for Email Status Alerts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveStatusToast" class="toast align-items-center text-bg-<?= $messageType === 'success' ? 'success' : ($messageType === 'warning' ? 'warning text-dark' : 'danger') ?> border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fs-7 fw-semibold py-3 px-3 d-flex align-items-center gap-2">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle fs-5' : ($messageType === 'warning' ? 'fa-exclamation-triangle fs-5' : 'fa-times-circle fs-5') ?>"></i>
                <span id="toastMessageText"><?= htmlspecialchars($message) ?></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Details Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow bg-body text-body">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6"><i class="fas fa-user-shield text-warning me-2"></i>Review Account Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Faculty ID</label>
                        <div class="fw-bold text-info fs-6" id="modalFacultyId">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Full Name</label>
                        <div class="fw-bold fs-6 text-body" id="modalFullName">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Department</label>
                        <div id="modalDept" class="text-body">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Position</label>
                        <div id="modalPosition" class="text-body">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Academic Rank & Tier</label>
                        <div id="modalRank" class="text-body">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Employment Status</label>
                        <div id="modalEmployment" class="text-body">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Email Address</label>
                        <div id="modalEmail" class="text-body">—</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="text-body-secondary small">Phone Number</label>
                        <div id="modalPhone" class="text-body">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Close</button>
                <div class="d-inline-flex gap-2 w-100 w-sm-auto justify-content-end">
                    <input type="hidden" id="modalUserId" value="">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 fw-bold flex-fill flex-sm-grow-0" onclick="openActionModal('reject', document.getElementById('modalUserId').value, document.getElementById('modalFullName').textContent)">
                        <i class="fas fa-times me-1"></i> Reject Request
                    </button>
                    <button type="button" class="btn btn-success btn-sm rounded-3 fw-bold px-3 flex-fill flex-sm-grow-0" onclick="openActionModal('approve', document.getElementById('modalUserId').value, document.getElementById('modalFullName').textContent)">
                        <i class="fas fa-check me-1"></i> Approve & Activate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Action Confirmation Dialog Modal -->
<div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-body text-body">
            <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="confirmModalTitle">
                    <i id="confirmModalIcon" class="fas fa-check-circle text-success"></i>
                    <span id="confirmModalHeading">Confirm Action</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 fs-7">
                <p class="mb-0" id="confirmModalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer border-top border-light-subtle py-2 px-4 justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                <form method="post" id="confirmActionForm" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="user_id" id="confirmUserId" value="">
                    <input type="hidden" name="action" id="confirmActionInput" value="">
                    <button type="submit" id="confirmSubmitBtn" class="btn btn-success btn-sm rounded-3 fw-bold px-3">
                        <i class="fas fa-check me-1"></i> Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically trigger Bootstrap Toast popup if a message is returned from server backend
    document.addEventListener('DOMContentLoaded', function() {
        const serverMessage = <?= json_encode($message) ?>;
        if (serverMessage && serverMessage.trim() !== '') {
            const toastEl = document.getElementById('liveStatusToast');
            if (toastEl && window.bootstrap && bootstrap.Toast) {
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();
            }
        }
    });

    function filterPending() {
        const query = document.getElementById('pendingSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#pendingTableBody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function inspectRequest(button) {
        if (!button || !button.dataset) return;
        
        document.getElementById('modalUserId').value = button.dataset.userId || '';
        document.getElementById('modalFacultyId').textContent = button.dataset.facultyId || '—';
        document.getElementById('modalFullName').textContent = button.dataset.fullName || '—';
        document.getElementById('modalDept').textContent = button.dataset.dept || '—';
        document.getElementById('modalPosition').textContent = button.dataset.position || '—';
        document.getElementById('modalRank').textContent = (button.dataset.rank || '') + ' ' + (button.dataset.tier ? '(' + button.dataset.tier + ')' : '');
        document.getElementById('modalEmployment').textContent = button.dataset.employment || '—';
        document.getElementById('modalEmail').textContent = button.dataset.email || '—';
        document.getElementById('modalPhone').textContent = button.dataset.phone || '—';

        const modalEl = document.getElementById('reviewModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function openActionModal(action, userId, userName) {
        document.getElementById('confirmUserId').value = userId;
        document.getElementById('confirmActionInput').value = action;

        const titleEl = document.getElementById('confirmModalHeading');
        const iconEl = document.getElementById('confirmModalIcon');
        const msgEl = document.getElementById('confirmModalMessage');
        const submitBtn = document.getElementById('confirmSubmitBtn');

        if (action === 'approve') {
            titleEl.textContent = 'Approve Account Request';
            iconEl.className = 'fas fa-user-check text-success';
            msgEl.innerHTML = `Are you sure you want to approve the account request for <strong>${escapeHtml(userName || 'this faculty member')}</strong>? This will activate their account, generate a temporary password, and securely email the credentials to the owner.`;
            submitBtn.className = 'btn btn-success btn-sm rounded-3 fw-bold px-3';
            submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Yes, Approve';
        } else {
            titleEl.textContent = 'Reject Account Request';
            iconEl.className = 'fas fa-user-times text-danger';
            msgEl.innerHTML = `Are you sure you want to reject the account request for <strong>${escapeHtml(userName || 'this faculty member')}</strong>?`;
            submitBtn.className = 'btn btn-danger btn-sm rounded-3 fw-bold px-3';
            submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Yes, Reject';
        }

        const reviewModalEl = document.getElementById('reviewModal');
        if (reviewModalEl) {
            const reviewModal = bootstrap.Modal.getInstance(reviewModalEl);
            if (reviewModal) {
                reviewModal.hide();
            }
        }

        const confirmModalEl = document.getElementById('actionConfirmModal');
        if (confirmModalEl && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
        }
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>