<?php

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../controllers/faculty-data.php';
require_once __DIR__ . '/../../../../includes/Exception.php';
require_once __DIR__ . '/../../../../includes/PHPMailer.php';
require_once __DIR__ . '/../../../../includes/SMTP.php';

requireAuth();

$pageTitle = 'Leave Request Approval';
$activeModule = 'faculty';
$activePage = 'leave-request-approval';

$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Requests', 'url' => null],
];

$leaveRequests = [];
$totalRequests = 0;
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$documentRequiredCount = 0;
$availableLeaveTypes = [];
$leaveUsageData = [];
$facultyGroupedRequests = [];

$formError = '';
$formSuccess = '';

$ACADEMIC_YEAR = '2026-2027';

/**
 * Human-friendly label for a balance column prefix.
 */
function balanceLabel(string $key): string
{
    return match ($key) {
        'sick_leave' => 'Sick Leave',
        'vacation_leave' => 'Vacation Leave',
        'emergency' => 'Emergency Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'magna_carta' => 'Magna Carta Leave',
        'vawc' => 'VAWC Leave',
        'sabbatical' => 'Sabbatical Leave',
        'admin_vacation' => 'Academic/Vacation Leave',
        'admin_special' => 'Special Leave Privileges',
        'study_leave' => 'Study Leave',
        default => $key,
    };
}

/**
 * Ordered list of balance column prefixes as stored in faculty_db.leave_balances.
 */
function balanceColumnKeys(): array
{
    return [
        'sick_leave',
        'vacation_leave',
        'emergency',
        'maternity',
        'paternity',
        'magna_carta',
        'vawc',
        'sabbatical',
        'admin_vacation',
        'admin_special',
        'study_leave',
    ];
}

/**
 * Email helper — unchanged.
 */
function sendLeaveStatusEmail($toEmail, $toName, $subject, $statusMessage, $details = [], $status = '')
{
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    switch (strtolower((string) $status)) {
        case 'approved':
            $badgeLabel = 'Request Approved';
            break;
        case 'rejected':
            $badgeLabel = 'Request Rejected';
            break;
        case 'document_required':
            $badgeLabel = 'Action Required';
            break;
        default:
            $badgeLabel = 'Leave Request Update';
            break;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'jcespejo002@gmail.com';
        $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : 'cshwohpgllkqdtga';
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;

        $mail->setFrom($mail->Username, 'Bestlink College No-Reply');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $logoUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/images/bestlink.png' : '';
        $logoHtml = $logoUrl !== '' ? "<img src='" . htmlspecialchars($logoUrl) . "' width='40' height='40' alt='Bestlink College of the Philippines' style='display:block; width:40px; height:40px; border-radius:6px;'>" : '';

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>" . htmlspecialchars($subject) . "</title>
            <style>
                body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; }
                table { border-collapse: collapse; }
                img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
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
                                                <div style='color: rgba(255,255,255,0.75); font-size: 11px; font-weight: 600; letter-spacing: 1px; margin-top: 4px; text-transform: uppercase;'>{$badgeLabel}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td class='content-padding' style='padding: 32px; color: #e0e0e0; font-size: 14px; line-height: 1.65;'>
                                    <p style='margin: 0 0 16px 0;'>Dear <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                                    <p style='margin: 0 0 22px 0;'>{$statusMessage}</p>";

        if (!empty($details)) {
            $body .= "
                                    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #1e1e1e; border-radius: 8px; border: 1px solid #2d2d2d; margin-bottom: 22px;'>
                                        <tr>
                                            <td style='padding: 18px 20px;'>
                                                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>";
            $rowIndex = 0;
            foreach ($details as $label => $val) {
                $rowIndex++;
                $padBottom = $rowIndex === count($details) ? '0' : '10px';
                $body .= "
                                                    <tr>
                                                        <td width='130' style='color: #9a9a9a; font-weight: 600; font-size: 12.5px; padding-bottom: {$padBottom}; vertical-align: top;'>" . htmlspecialchars((string) $label) . "</td>
                                                        <td style='color: #ffffff; font-size: 13.5px; font-weight: 600; padding-bottom: {$padBottom}; word-break: break-word;'>" . nl2br(htmlspecialchars((string) $val)) . "</td>
                                                    </tr>";
            }
            $body .= "
                                                </table>
                                            </td>
                                        </tr>
                                    </table>";
        }

        $body .= "
                                    <p style='margin: 0 0 22px 0; color: #9a9a9a; font-size: 12.5px;'>This is an automated notification from the Faculty Leave Management System. Please do not reply directly to this email — for questions, coordinate with your Department Head.</p>

                                    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                        <tr>
                                            <td style='color: #bbbbbb; padding-top: 6px; border-top: 1px solid #2a2a2a;'>
                                                <p style='margin: 14px 0 0 0;'>Regards,</p>
                                                <p style='margin: 4px 0 0 0; font-weight: bold; color: #ffffff;'>Faculty Administration</p>
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

        $mail->Body = $body;
        $mail->AltBody = strip_tags($statusMessage);

        return $mail->send();
    } catch (Exception $e) {
        error_log("[Leave Email Error] Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    $pdo = facultyDb();

    if (!$pdo) {
        throw new RuntimeException('Unable to connect to the faculty database.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            throw new RuntimeException('Invalid leave request.');
        }

        if (!in_array($action, ['approve', 'reject', 'notify_faculty', 'delete_document'], true)) {
            throw new RuntimeException('Invalid leave request action.');
        }

        $approverId = null;
        if (function_exists('getCurrentUserId')) {
            $approverId = (int) getCurrentUserId();
        }
        if ($approverId <= 0) {
            $approverId = (int) ($_SESSION['user_id'] ?? 0);
        }
        if ($approverId <= 0) {
            throw new RuntimeException('Your account could not be identified. Please log in again.');
        }

        if ($action === 'save_signature_data') {
            try {
                $dataUrl = trim((string) ($_POST['signature_data'] ?? ''));
                if ($dataUrl === '') {
                    echo json_encode(['success' => false, 'message' => 'No signature data provided.']);
                    exit;
                }

                if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
                    $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
                    $data = base64_decode($data);
                    if ($data === false) {
                        echo json_encode(['success' => false, 'message' => 'Base64 decode failed.']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid data URL format.']);
                    exit;
                }

                $uploadDir = __DIR__ . '/../../../../uploads/signatures/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = 'sig_' . $approverId . '_' . time() . '.png';
                $filePath = $uploadDir . $fileName;
                file_put_contents($filePath, $data);

                $relativeSignaturePath = 'uploads/signatures/' . $fileName;

                $checkProf = $pdo->prepare("SELECT id FROM faculty_db.faculty_profiles WHERE user_id = :uid1 OR id = :uid2 LIMIT 1");
                $checkProf->execute([':uid1' => $approverId, ':uid2' => $approverId]);
                $exists = $checkProf->fetchColumn();

                if (!$exists) {
                    $insProf = $pdo->prepare("
                        INSERT INTO faculty_db.faculty_profiles (user_id, signature, created_at) 
                        VALUES (:uid, :sig, NOW())
                    ");
                    $insProf->execute([
                        ':uid' => $approverId,
                        ':sig' => $relativeSignaturePath
                    ]);
                } else {
                    $updSig = $pdo->prepare("
                        UPDATE faculty_db.faculty_profiles 
                        SET signature = :sig 
                        WHERE user_id = :uid1 OR id = :uid2
                    ");
                    $updSig->execute([
                        ':sig' => $relativeSignaturePath,
                        ':uid1' => $approverId,
                        ':uid2' => $approverId
                    ]);
                }

                echo json_encode(['success' => true, 'path' => $relativeSignaturePath]);
                exit;
            } catch (Throwable $dbEx) {
                echo json_encode(['success' => false, 'message' => 'DB Error: ' . $dbEx->getMessage()]);
                exit;
            }
        }

        $stmt = $pdo->prepare("
            SELECT lr.*,
                   COALESCE(fp.first_name, fp2.first_name) AS first_name,
                   COALESCE(fp.last_name,  fp2.last_name)  AS last_name,
                   COALESCE(fp.user_id,    fp2.user_id)    AS profile_user_id,
                   COALESCE(u.email, fp.email, fp2.email)  AS faculty_email,
                   f.faculty_id AS faculty_record_id
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty f       ON f.faculty_id = lr.faculty_id
            LEFT JOIN faculty_db.faculty_profiles fp  ON fp.email = f.email
            LEFT JOIN faculty_db.faculty_profiles fp2 ON fp2.id = lr.faculty_id
            LEFT JOIN sms2_db.users u ON u.id = COALESCE(fp.user_id, fp2.user_id)
            WHERE lr.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new RuntimeException('The selected leave request could not be found.');
        }

        if (($request['screening_status'] ?? '') !== 'Screened') {
            throw new RuntimeException('This leave request has not been screened by the Secretary yet.');
        }

        $requestStatus = strtolower(trim((string) ($request['status'] ?? '')));
        $requestStatus = str_replace(' ', '_', $requestStatus);

        if ($action === 'notify_faculty') {
            $allowedStatuses = ['pending', 'approved', 'document_required', 'rejected'];
        } elseif ($action === 'delete_document') {
            $allowedStatuses = ['approved', 'document_required', 'pending'];
        } else {
            $allowedStatuses = ['pending', 'document_required'];
        }

        if (!in_array($requestStatus, $allowedStatuses, true)) {
            $statusLabel = ucfirst(str_replace('_', ' ', $requestStatus));
            throw new RuntimeException('This leave request is currently ' . $statusLabel . ' and cannot be processed with that action.');
        }

        $comment = null;
        if ($action === 'reject') {
            $comment = trim((string) ($_POST['comment'] ?? ''));
            if ($comment === '') {
                throw new RuntimeException('Please provide a reason for rejecting the leave request.');
            }
        }

        $columnStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = 'faculty_db'
              AND TABLE_NAME = 'leave_requests'
              AND COLUMN_NAME = 'approver_comment'
        ");
        $columnStmt->execute();
        $hasApproverComment = (int) $columnStmt->fetchColumn() > 0;

        $notificationColumnStmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = 'faculty_db'
                AND TABLE_NAME = 'leave_requests'
                AND COLUMN_NAME IN ('notification', 'is_notified', 'notified', 'notification_sent', 'notification_seen', 'notification_read', 'is_seen', 'document_notification', 'notify_flag', 'is_alerted', 'notification_status', 'is_notification')
            LIMIT 1
        ");
        $notificationColumnStmt->execute();
        $notificationColumn = $notificationColumnStmt->fetchColumn();

        $facultyEmail = $request['faculty_email'] ?? '';
        $facultyName = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: 'Faculty Member';
        $refNo = $request['request_ref'] ?? ('LR-' . $requestId);
        $leaveType = $request['leave_type'] ?? 'Leave';

        if ($action === 'approve') {
            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET
                    status = 'Approved',
                    approver_id = :approver_id,
                    approver_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'document_required')
            ");
            $stmt->execute([
                ':approver_id' => $approverId,
                ':id' => $requestId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('The leave request could not be approved.');
            }

            if (!empty($facultyEmail)) {
                sendLeaveStatusEmail(
                    $facultyEmail,
                    $facultyName,
                    'Leave Request Approved - ' . $refNo,
                    'Your leave request has been <strong>approved</strong> by the Department Head.',
                    [
                        'Reference No' => $refNo,
                        'Leave Type' => $leaveType,
                        'Start Date' => $request['start_date'] ?? '',
                        'End Date' => $request['end_date'] ?? ''
                    ],
                    'approved'
                );
            }

            $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirectUrl . '?success=' . urlencode('Leave request approved and notification email sent successfully.'));
            exit;

        } elseif ($action === 'notify_faculty') {
            $updateFields = [
                "status = 'Approved'",
                "updated_at = NOW()"
            ];
            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'approved', 'document_required', 'rejected')
            ");
            $stmt->execute([':id' => $requestId]);

        } elseif ($action === 'delete_document') {
            $updateFields = [
                "documents = NULL",
                "status = 'Approved'",
                "updated_at = NOW()"
            ];
            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('approved', 'document_required', 'pending')
            ");
            $stmt->execute([':id' => $requestId]);

        } else {
            // Reject
            $updateFields = [
                "status = 'Rejected'",
                "approver_id = :approver_id",
                "approver_at = NOW()"
            ];

            if ($hasApproverComment) {
                $updateFields[] = "approver_comment = :comment";
            }

            $updateFields[] = "updated_at = NOW()";

            if ($notificationColumn) {
                $updateFields[] = "$notificationColumn = 1";
            }

            $stmt = $pdo->prepare("
                UPDATE faculty_db.leave_requests
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id
                  AND LOWER(status) IN ('pending', 'document_required')
            ");

            $params = [':approver_id' => $approverId, ':id' => $requestId];
            if ($hasApproverComment) {
                $params[':comment'] = $comment;
            }

            $stmt->execute($params);

            if (!empty($facultyEmail)) {
                sendLeaveStatusEmail(
                    $facultyEmail,
                    $facultyName,
                    'Leave Request Rejected - ' . $refNo,
                    'Your leave request has been <strong>rejected</strong>.',
                    [
                        'Reference No' => $refNo,
                        'Leave Type' => $leaveType,
                        'Reason' => $comment ?: 'No reason provided.'
                    ],
                    'rejected'
                );
            }
        }

        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
        $successText = match ($action) {
            'approve' => 'Leave request approved successfully.',
            'notify_faculty' => 'Faculty has been notified to submit the required document.',
            'delete_document' => 'The uploaded document was removed.',
            default => 'Leave request rejected and notification email sent successfully.',
        };

        header('Location: ' . $redirectUrl . '?success=' . urlencode($successText));
        exit;
    }

    if (isset($_GET['success'])) {
        $formSuccess = trim((string) $_GET['success']);
    }

    $countStmt = $pdo->prepare("SELECT LOWER(status) AS status, COUNT(*) AS cnt FROM faculty_db.leave_requests GROUP BY LOWER(status)");
    $countStmt->execute();
    $counts = [];

    while ($r = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $statusKey = (string) ($r['status'] ?? '');
        $counts[$statusKey] = (int) ($r['cnt'] ?? 0);
    }

    $pendingCount = $counts['pending'] ?? 0;
    $approvedCount = $counts['approved'] ?? 0;
    $rejectedCount = $counts['rejected'] ?? 0;
    $documentRequiredCount = $counts['document required'] ?? $counts['document_required'] ?? 0;
    $totalRequests = array_sum($counts);

    $balanceStmt = $pdo->prepare("
        SELECT lb.*
        FROM faculty_db.leave_balances lb
        WHERE lb.academic_year = :yr
    ");
    $balanceStmt->execute([':yr' => $ACADEMIC_YEAR]);
    $allBalances = [];
    foreach ($balanceStmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $allBalances[(int) $b['faculty_id']] = $b;
    }

    $sql = "SELECT lr.*,
                   COALESCE(fp.id, fp2.id) AS faculty_profile_id,
                   COALESCE(fp.user_id, fp2.user_id) AS faculty_user_id,
                   COALESCE(fp.faculty_id, fp2.faculty_id) AS faculty_identifier,
                   COALESCE(
                       NULLIF(CONCAT_WS(' ', fp.first_name, fp.last_name), ' '),
                       NULLIF(CONCAT_WS(' ', fp2.first_name, fp2.last_name), ' ')
                   ) AS faculty_name,
                   'Faculty Department' AS department,
                   DATEDIFF(lr.end_date, lr.start_date) + 1 AS days,
                   f.faculty_id AS faculty_record_id
            FROM faculty_db.leave_requests lr
            LEFT JOIN faculty_db.faculty f       ON f.faculty_id = lr.faculty_id
            LEFT JOIN faculty_db.faculty_profiles fp  ON fp.email = f.email
            LEFT JOIN faculty_db.faculty_profiles fp2 ON fp2.id = lr.faculty_id
            WHERE lr.screening_status = 'Screened'
            ORDER BY lr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($leaveRequests as $req) {
        $fKey = (int) ($req['faculty_record_id'] ?? 0);
        if ($fKey <= 0) {
            $fKey = (int) ($req['faculty_profile_id'] ?? 0);
        }
        if ($fKey <= 0)
            continue;

        if (!isset($facultyGroupedRequests[$fKey])) {
            $facultyGroupedRequests[$fKey] = [
                'faculty_profile_id' => (int) ($req['faculty_profile_id'] ?? 0),
                'faculty_record_id' => $fKey,
                'faculty_name' => $req['faculty_name'] ?? 'Unknown Faculty',
                'faculty_identifier' => $req['faculty_identifier'] ?? '',
                'department' => $req['department'] ?? 'Faculty Department',
                'requests' => [],
                'balance' => $allBalances[$fKey] ?? null,
            ];
        }
        $facultyGroupedRequests[$fKey]['requests'][] = $req;
    }

} catch (Throwable $e) {
    $formError = $e->getMessage();
    error_log('[leave-request-approval] ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
}

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<style>
    #rejectModal {
        z-index: 1060 !important;
    }

    .table th,
    .table td {
        white-space: nowrap !important;
    }

    #signatureCanvas {
        cursor: crosshair;
        background-color: #fff;
        touch-action: none;
    }

    .modal.show {
        background-color: rgba(0, 0, 0, 0.4);
    }

    #confirmActionModal {
        z-index: 1060 !important;
    }

    .modal-backdrop+.modal-backdrop {
        z-index: 1055 !important;
    }

    .balance-mini {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.28rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 650;
        border-radius: 6px;
        line-height: 1;
        border: 1px solid;
    }

    .balance-mini.ok {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        border-color: rgba(16, 185, 129, 0.3);
    }

    .balance-mini.low {
        background: rgba(245, 158, 11, 0.15);
        color: #d97706;
        border-color: rgba(245, 158, 11, 0.3);
    }

    .balance-mini.danger {
        background: rgba(239, 68, 68, 0.15);
        color: #dc2626;
        border-color: rgba(239, 68, 68, 0.3);
    }

    .balance-mini.none {
        background: rgba(148, 163, 184, 0.15);
        color: #64748b;
        border-color: rgba(148, 163, 184, 0.25);
    }

    [data-bs-theme="dark"] .balance-mini.ok,
    body.dark-mode .balance-mini.ok {
        background: rgba(16, 185, 129, 0.22);
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.35);
    }

    [data-bs-theme="dark"] .balance-mini.low,
    body.dark-mode .balance-mini.low {
        background: rgba(245, 158, 11, 0.22);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.35);
    }

    [data-bs-theme="dark"] .balance-mini.danger,
    body.dark-mode .balance-mini.danger {
        background: rgba(239, 68, 68, 0.22);
        color: #f87171;
        border-color: rgba(248, 113, 113, 0.35);
    }

    [data-bs-theme="dark"] .balance-mini.none,
    body.dark-mode .balance-mini.none {
        background: rgba(148, 163, 184, 0.20);
        color: #94a3b8;
        border-color: rgba(148, 163, 184, 0.3);
    }
</style>

<?php
if (function_exists('renderBreadcrumbs')) {
    renderBreadcrumbs($breadcrumbs);
}
?>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="actionToast" class="toast align-items-center text-white border-0 shadow" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-body">
            <i class="fas fa-plane-departure text-primary me-2"></i>
            Leave Request Approval
        </h1>
        <p class="text-body-secondary mb-0 small">
            Review and approve or reject submitted leave requests grouped by faculty account
        </p>
    </div>
</div>

<!-- Confirmation Action Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature text-primary"></i> Confirm Submission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-muted" id="confirmModalBody">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success px-3" id="confirmModalSubmitBtn">
                    <i class="fas fa-check me-1"></i> Yes, submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Pending Requests — Amber -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card warning border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100"
                style="width: 4px; background-color: #f59e0b; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Pending Requests</h6>
                    <h4 class="mb-0 fw-bold" style="color: #f59e0b;"><?= $pendingCount ?></h4>
                    <small class="fw-semibold" style="color: #f59e0b; font-size: 0.75rem;">
                        Awaiting your action
                    </small>
                </div>
            </div>
            <a href="#"
                class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Pending">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Approved — Green -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card success border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100"
                style="width: 4px; background-color: #10b981; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #10b981;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Approved</h6>
                    <h4 class="mb-0 fw-bold" style="color: #10b981;"><?= $approvedCount ?></h4>
                    <small class="fw-semibold" style="color: #10b981; font-size: 0.75rem;">
                        Successfully processed
                    </small>
                </div>
            </div>
            <a href="#"
                class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Approved">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Rejected — Red -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card danger border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100"
                style="width: 4px; background-color: #ff4d4d; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #ff4d4d;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Rejected</h6>
                    <h4 class="mb-0 fw-bold" style="color: #ff4d4d;"><?= $rejectedCount ?></h4>
                    <small class="fw-semibold" style="color: #ff4d4d; font-size: 0.75rem;">
                        Declined applications
                    </small>
                </div>
            </div>
            <a href="#"
                class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                style="width: 24px; height: 24px; font-size: 0.7rem;" title="View Rejected">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>

    <!-- Total Requests — Blue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <section class="card stat-card info border shadow-sm position-relative overflow-hidden h-100">
            <div class="position-absolute top-0 start-0 h-100"
                style="width: 4px; background-color: #0d6efd; z-index: 1;"></div>
            <div class="card-body d-flex align-items-center ps-4">
                <div class="stat-icon me-3 fs-4" style="color: #0d6efd;">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Requests</h6>
                    <h4 class="mb-0 fw-bold" style="color: #0d6efd;"><?= $totalRequests ?></h4>
                    <small class="fw-semibold" style="color: #0d6efd; font-size: 0.75rem;">
                        All leave submissions
                    </small>
                </div>
            </div>
            <a href="#"
                class="position-absolute top-0 end-0 m-3 text-muted border rounded p-1 d-flex align-items-center justify-content-center border-secondary-subtle"
                style="width: 24px; height: 24px; font-size: 0.7rem;" title="View All">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </section>
    </div>
</div>

<?php if ($formSuccess !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast(<?= json_encode($formSuccess) ?>, 'success');
        });
    </script>
<?php endif; ?>

<?php if ($formError !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast(<?= json_encode($formError) ?>, 'danger');
        });
    </script>
<?php endif; ?>

<!-- Faculty Leave Accounts Table Card -->
<div class="card border-0 shadow-sm mb-4">
    <div
        class="card-header bg-body-tertiary py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold text-body">
            <i class="fas fa-list-check text-primary me-2"></i> Faculty Leave Accounts
        </h6>
        <div style="width: 280px;">
            <input type="search" id="tableSearchInput" class="form-control form-control-sm"
                placeholder="Search faculty name or reference...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="leaveRequestsTable">
                <thead class="table-light border-bottom small text-uppercase fw-bold text-body-secondary text-nowrap">
                    <tr>
                        <th class="ps-3 py-3" style="width: 40px;"><input type="checkbox" class="form-check-input"
                                id="selectAll"></th>
                        <th class="py-3">Faculty Member</th>
                        <th class="py-3 text-center">Total Requests</th>
                        <th class="py-3 text-center">Pending Review</th>
                        <th class="text-end pe-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-body">
                    <?php if (empty($facultyGroupedRequests)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-5">
                                <i class="fas fa-inbox fs-3 d-block mb-2 text-body-tertiary"></i>
                                <span>No leave requests found.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($facultyGroupedRequests as $fId => $group): ?>
                            <?php
                            $facultyName = $group['faculty_name'];
                            $requests = $group['requests'];
                            $totalFacultyRequests = count($requests);
                            $pendingFacultyRequests = 0;
                            $allRefs = [];

                            foreach ($requests as $r) {
                                $stRaw = strtolower(trim($r['status'] ?? 'pending'));
                                if ($stRaw === 'pending')
                                    $pendingFacultyRequests++;
                                $allRefs[] = strtolower($r['request_ref'] ?? ('lr-' . $r['id']));
                            }

                            $encodedGroup = htmlspecialchars(json_encode($group, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="align-middle faculty-row"
                                data-name="<?= htmlspecialchars(strtolower($facultyName), ENT_QUOTES, 'UTF-8') ?>"
                                data-refs="<?= htmlspecialchars(implode(' ', $allRefs), ENT_QUOTES, 'UTF-8') ?>">

                                <td class="ps-3 py-3"><input type="checkbox" class="form-check-input row-select"
                                        value="<?= $fId ?>"></td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold flex-shrink-0"
                                            style="width: 36px; height: 36px;">
                                            <?= htmlspecialchars(strtoupper(substr($facultyName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <strong
                                                class="text-body-emphasis d-block text-nowrap"><?= htmlspecialchars($facultyName, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="d-block text-body-secondary font-monospace text-nowrap">ID:
                                                <?= htmlspecialchars($group['faculty_identifier'] ?: $fId, ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-center"><span
                                        class="fw-bold text-body bg-body-tertiary border rounded px-2 py-1"><?= $totalFacultyRequests ?></span>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if ($pendingFacultyRequests > 0): ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold"><?= $pendingFacultyRequests ?>
                                            Pending</span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-3 py-1 fw-bold">All
                                            Processed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 py-3 text-nowrap">
                                    <button type="button"
                                        class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-2"
                                        onclick='showFacultyRequestsModal(<?= $encodedGroup ?>)'>
                                        <i class="fas fa-eye text-white"></i>
                                        <span>View Leave Profile</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Faculty Requests Modal -->
<div class="modal fade" id="facultyRequestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-body mb-0">Leave Requests: <span
                        id="modal-faculty-title-name">-</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold text-body mb-2">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Leave Balance — <?= htmlspecialchars($ACADEMIC_YEAR) ?>
                    </h6>
                    <div id="modal-balance-grid" class="row g-2"></div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-bordered mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase fw-bold text-body-secondary">
                            <tr>
                                <th>Ref / Date</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="modal-requests-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Reason Input Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Reject
                    Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label for="reject-reason" class="form-label fw-semibold small">Reason for Rejection <span
                        class="text-danger">*</span></label>
                <textarea id="reject-reason" class="form-control" rows="4" placeholder="Enter reason..."></textarea>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="promptRejectConfirmation()">Continue to
                    Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
    const ACADEMIC_YEAR = <?= json_encode($ACADEMIC_YEAR) ?>;

    /* Ordered balance column keys, matching the current faculty_db.leave_balances schema */
    const BALANCE_KEYS = [
        'sick_leave', 'vacation_leave', 'emergency',
        'maternity', 'paternity',
        'magna_carta', 'vawc', 'sabbatical',
        'admin_vacation', 'admin_special', 'study_leave'
    ];

    const BALANCE_LABELS = {
        sick_leave: 'Sick Leave',
        vacation_leave: 'Vacation Leave',
        emergency: 'Emergency Leave',
        maternity: 'Maternity Leave',
        paternity: 'Paternity Leave',
        magna_carta: 'Magna Carta Leave',
        vawc: 'VAWC Leave',
        sabbatical: 'Sabbatical Leave',
        admin_vacation: 'Academic/Vacation Leave',
        admin_special: 'Special Leave Privileges',
        study_leave: 'Study Leave'
    };

    let pendingActionData = { action: '', id: '', comment: '' };

    function submitAction(action, id, comment = '') {
        if (!id) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'request_id';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(idInput);

        if (comment !== '') {
            const commentInput = document.createElement('input');
            commentInput.type = 'hidden';
            commentInput.name = 'comment';
            commentInput.value = comment;
            form.appendChild(commentInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    document.getElementById('confirmModalSubmitBtn').addEventListener('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).hide();
        submitAction(pendingActionData.action, pendingActionData.id, pendingActionData.comment);
    });

    function openConfirmModal(action, id, message, btnClass, btnIconText) {
        pendingActionData = { action, id, comment: '' };
        document.getElementById('confirmModalBody').textContent = message;

        const submitBtn = document.getElementById('confirmModalSubmitBtn');
        submitBtn.className = `btn btn-sm ${btnClass} px-3`;
        submitBtn.innerHTML = `<i class="fas ${btnIconText} me-1"></i> Yes, submit`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }

    function approveRequest(id) {
        if (!id) return;
        openConfirmModal('approve', id, 'Are you sure you want to approve this leave request and notify the faculty via email?', 'btn-success', 'fa-check');
    }



    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('actionToast');
        const toastMessage = document.getElementById('toastMessage');

        toastMessage.textContent = message;

        toastEl.className = 'toast align-items-center text-white border-0 shadow';
        if (type === 'success') {
            toastEl.classList.add('bg-success');
        } else if (type === 'danger' || type === 'error') {
            toastEl.classList.add('bg-danger');
        } else if (type === 'warning') {
            toastEl.classList.add('bg-warning', 'text-dark');
        } else {
            toastEl.classList.add('bg-primary');
        }

        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
    }

    // Live Search Filter
    document.getElementById('tableSearchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#leaveRequestsTable .faculty-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const refs = row.getAttribute('data-refs') || '';
            if (name.includes(query) || refs.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    /**
     * Renders a leave-balance grid inside the modal for the selected faculty.
     */
    function renderBalanceGrid(balance) {
        const grid = document.getElementById('modal-balance-grid');
        grid.innerHTML = '';

        if (!balance) {
            grid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-secondary small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    No leave balance record found for this faculty for ${ACADEMIC_YEAR}.
                </div>
            </div>`;
            return;
        }

        const activeCols = BALANCE_KEYS.filter(k => {
            const total = parseInt(balance[k + '_total'] ?? 0, 10);
            return total > 0;
        });

        if (activeCols.length === 0) {
            grid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-secondary small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    No active leave categories for this faculty.
                </div>
            </div>`;
            return;
        }

        activeCols.forEach(key => {
            const total = parseInt(balance[key + '_total'] ?? 0, 10);
            const used = parseInt(balance[key + '_used'] ?? 0, 10);
            const bal = Math.max(0, total - used);
            const pct = total > 0 ? Math.round((used / total) * 100) : 0;

            let cls = 'ok';
            if (bal <= 0) cls = 'danger';
            else if (bal <= 2) cls = 'low';
            if (pct >= 75) cls = 'danger';
            else if (pct >= 40) cls = cls === 'ok' ? 'low' : cls;

            const card = document.createElement('div');
            card.className = 'col-12 col-sm-6 col-lg-4';
            card.innerHTML = `
            <div class="border rounded p-2 bg-body-tertiary h-100">
                <div class="d-flex justify-content-between align-items-baseline mb-1">
                    <small class="fw-semibold text-body-secondary text-truncate" style="max-width: 60%;" title="${BALANCE_LABELS[key]}">
                        ${BALANCE_LABELS[key]}
                    </small>
                    <span class="balance-mini ${cls}">${bal} / ${total}</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: ${pct}%; background-color: ${cls === 'danger' ? '#dc3545' : (cls === 'low' ? '#fd7e14' : '#198754')};"></div>
                </div>
                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                    ${used} used · ${bal} left
                </small>
            </div>`;
            grid.appendChild(card);
        });
    }

    function showFacultyRequestsModal(group) {
        document.getElementById('modal-faculty-title-name').textContent = group.faculty_name;

        renderBalanceGrid(group.balance || null);

        const tbody = document.getElementById('modal-requests-tbody');
        tbody.innerHTML = '';

        if (!group.requests || group.requests.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No requests found.</td></tr>`;
        } else {
            group.requests.forEach(req => {
                const reqId = req.id;
                const ref = req.request_ref || ('LR-' + reqId);
                const statusRaw = (req.status || 'pending').toLowerCase().replace(/\s+/g, '_');

                const statusClass = {
                    pending: 'bg-warning-subtle text-warning-emphasis',
                    approved: 'bg-success-subtle text-success-emphasis',
                    rejected: 'bg-danger-subtle text-danger-emphasis',
                    document_required: 'bg-info-subtle text-info-emphasis',
                    returned: 'bg-danger-subtle text-danger-emphasis'
                }[statusRaw] || 'bg-secondary-subtle text-secondary-emphasis';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td><strong class="font-monospace text-body">${ref}</strong><br><small class="text-muted">${req.created_at || ''}</small></td>
                <td><span class="badge bg-primary bg-opacity-10 text-info">${req.leave_type}</span></td>
                <td><span class="badge bg-light text-dark">${req.days} day(s)</span></td>
                <td><small class="text-truncate d-inline-block" style="max-width: 200px;" title="${(req.reason || '').replace(/"/g, '&quot;')}">${req.reason || ''}</small></td>
                <td><span class="badge ${statusClass}">${statusRaw.replace(/_/g, ' ')}</span></td>
                <td class="text-end text-nowrap">
                    ${(statusRaw === 'pending' || statusRaw === 'document_required') ? `
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="approveRequest(${reqId})" title="Approve"><i class="fas fa-check"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectRequest(${reqId})" title="Reject"><i class="fas fa-times"></i></button>
                    ` : '<span class="text-muted small">—</span>'}
                </td>
            `;
                tbody.appendChild(tr);
            });
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('facultyRequestsModal')).show();
    }

    function rejectRequest(id) {
        if (!id) return;

        const facultyModal = bootstrap.Modal.getInstance(document.getElementById('facultyRequestsModal'));
        if (facultyModal) {
            facultyModal.hide();
        }

        document.getElementById('rejectModal').dataset.requestId = id;
        document.getElementById('reject-reason').value = '';

        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectModal')).show();
        }, 150);
    }

    function promptRejectConfirmation() {
        const rejectModalEl = document.getElementById('rejectModal');
        const id = rejectModalEl.dataset.requestId;
        const reason = document.getElementById('reject-reason').value.trim();

        if (!id || !reason) {
            alert('Please provide a reason for rejection.');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(rejectModalEl).hide();

        pendingActionData = { action: 'reject', id: id, comment: reason };
        document.getElementById('confirmModalBody').textContent = 'Are you sure you want to reject this leave request and notify the faculty via email?';

        const submitBtn = document.getElementById('confirmModalSubmitBtn');
        submitBtn.className = 'btn btn-sm btn-danger px-3';
        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Yes, reject';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>