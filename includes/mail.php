<?php
/**
 * SMS 2 – Mail helper (PHPMailer + OTP/Password Reset emails)
 */
require_once __DIR__ . '/security.php';

// Correct relative paths: if mail.php is in includes/, load PHPMailer from the same directory
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

/**
 * @return array{ok:bool,error:string}
 */
function smsSendMail(string $to, string $subject, string $htmlBody, string $textBody = ''): array
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email.'];
    }

    $fromEmail = trim((string) sms2_env('SMS2_MAIL_FROM', sms2_env('MAIL_FROM', 'no-reply@sms2.local')));
    $fromName = trim((string) sms2_env(
        'SMS2_MAIL_FROM_NAME',
        sms2_env('MAIL_FROM_NAME', defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'SMS 2')
    ));

    if ($textBody === '') {
        $textBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody)), ENT_QUOTES | ENT_HTML5));
    }

    return smsSendMailSmtp($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
}

/**
 * @return array{ok:bool,error:string}
 */
function smsSendMailSmtp(
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody,
    string $fromEmail,
    string $fromName
): array {
    $host = trim((string) sms2_env('SMS2_SMTP_HOST', sms2_env('SMTP_HOST', '')));
    if ($host === '') {
        return ['ok' => false, 'error' => 'SMTP is not configured.'];
    }

    $port = (int) sms2_env('SMS2_SMTP_PORT', sms2_env('SMTP_PORT', '587'));
    $port = $port > 0 && $port <= 65535 ? $port : 587;
    $encryption = strtolower(trim((string) sms2_env('SMS2_SMTP_ENCRYPTION', sms2_env('SMTP_ENCRYPTION', 'tls'))));
    $auth = in_array(
        strtolower(trim((string) sms2_env('SMS2_SMTP_AUTH', sms2_env('SMTP_AUTH', 'true')))),
        ['1', 'true', 'yes', 'on'],
        true
    );
    $username = (string) sms2_env('SMS2_SMTP_USERNAME', sms2_env('SMTP_USERNAME', ''));
    $password = (string) sms2_env('SMS2_SMTP_PASSWORD', sms2_env('SMTP_PASSWORD', ''));

    if ($auth && ($username === '' || $password === '')) {
        return ['ok' => false, 'error' => 'SMTP authentication is incomplete.'];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = $auth;
        $mail->Username = $username;
        $mail->Password = $password;

        if (in_array($encryption, ['ssl', 'smtps'], true)) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif (in_array($encryption, ['tls', 'starttls'], true)) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        return ['ok' => true, 'error' => ''];
    } catch (\Throwable $e) {
        $msg = 'Mailer Error: ' . ($mail->ErrorInfo ?? $e->getMessage());
        error_log('SMS2 ' . $msg);
        return ['ok' => false, 'error' => $msg];
    }
}

/**
 * Send password-reset link to the account email.
 *
 * @param array<string,mixed> $user
 * @return array{ok:bool,error:string,to:string}
 */
function smsSendPasswordResetEmail(array $user, string $resetUrl, ?string $toOverride = null): array
{
    $to = trim((string) ($toOverride ?? ''));
    if ($to === '') {
        $to = trim((string) ($user['email'] ?? ''));
    }
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'This account has no valid email on file.', 'to' => ''];
    }

    $name = trim((string) ($user['full_name'] ?? 'User'));
    if ($name === '') {
        $name = 'User';
    }

    $subject = (defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'SMS') . ' password reset';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $app = defined('APP_NAME') ? APP_NAME : 'System';
    $inst = defined('INSTITUTION') ? INSTITUTION : 'Bestlink College of the Philippines';

    $html = '<div style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#0f172a;">'
        . '<p>Hi ' . $safeName . ',</p>'
        . '<p>We received a request to reset your password for <strong>' . $app . '</strong>.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 18px;background:#294ecb;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;">Reset your password</a></p>'
        . '<p>Or copy this link into your browser:</p>'
        . '<p style="word-break:break-all;color:#1d4ed8;">' . $safeUrl . '</p>'
        . '<p>This link expires in <strong>1 hour</strong>. If you did not request this, you can ignore this email.</p>'
        . '<p style="color:#64748b;font-size:13px;">' . $inst . ' · ' . $app . '</p>'
        . '</div>';

    $text = "Hi {$name},\n\n"
        . "We received a request to reset your password.\n\n"
        . "Open this link to reset your password (expires in 1 hour):\n{$resetUrl}\n\n"
        . "If you did not request this, ignore this email.\n";

    $result = smsSendMail($to, $subject, $html, $text);
    $result['to'] = $to;
    return $result;
}

/**
 * Email a one-time password (OTP) to the user's account email.
 *
 * @param array<string,mixed> $user
 * @return array{ok:bool,error:string,to:string}
 */
function smsSendOtpEmail(array $user, string $code, string $purposeLabel = 'password change', int $ttlMinutes = 10): array
{
    $to = trim((string) ($user['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'This account has no valid email on file.', 'to' => ''];
    }

    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return ['ok' => false, 'error' => 'Invalid OTP code.', 'to' => $to];
    }

    $name = trim((string) ($user['full_name'] ?? 'User'));
    if ($name === '') {
        $name = 'User';
    }

    $ttlMinutes = max(1, $ttlMinutes);
    $subject = (defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'SMS') . ' verification code';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $safePurpose = htmlspecialchars($purposeLabel, ENT_QUOTES, 'UTF-8');
    $app = defined('APP_NAME') ? APP_NAME : 'System';
    $inst = defined('INSTITUTION') ? INSTITUTION : 'Bestlink College of the Philippines';

    $html = '<div style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#0f172a;">'
        . '<p>Hi ' . $safeName . ',</p>'
        . '<p>Your one-time verification code for <strong>' . $safePurpose . '</strong> on <strong>' . $app . '</strong> is:</p>'
        . '<p style="font-size:28px;font-weight:800;letter-spacing:0.2em;margin:16px 0;">' . $safeCode . '</p>'
        . '<p>This code expires in <strong>' . (int) $ttlMinutes . ' minutes</strong>. Do not share it with anyone.</p>'
        . '<p>If you did not request this, you can ignore this email.</p>'
        . '<p style="color:#64748b;font-size:13px;">' . $inst . ' · ' . $app . '</p>'
        . '</div>';

    $text = "Hi {$name},\n\n"
        . "Your one-time verification code for {$purposeLabel} is: {$code}\n\n"
        . "This code expires in {$ttlMinutes} minutes. Do not share it with anyone.\n";

    $result = smsSendMail($to, $subject, $html, $text);
    $result['to'] = $to;
    return $result;
}