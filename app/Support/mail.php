<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

// Stores the latest mail failure so calling code can show or log a useful reason.
function civicconnect_record_mail_error($message)
{
    $message = trim((string)$message);
    $GLOBALS['civicconnect_last_mail_error'] = $message;

    if ($message !== '') {
        error_log('[CivicConnect Mail] ' . $message);
    }
}

// Returns the latest mail failure from this request.
function civicconnect_last_mail_error()
{
    return $GLOBALS['civicconnect_last_mail_error'] ?? '';
}

// Loads environment variables once for mail configuration.
function civicconnect_load_env_once()
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $envFile = __DIR__ . '/../../.env';

    if (!is_readable($envFile)) {
        return;
    }

    $values = parse_ini_file($envFile, false, INI_SCANNER_RAW);

    if (!is_array($values)) {
        return;
    }

    foreach ($values as $key => $value) {
        putenv($key . '=' . $value);
        $_ENV[(string)$key] = $value;
    }
}

// Reads an environment value with a fallback.
function civicconnect_env($key, $default = '')
{
    civicconnect_load_env_once();

    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

// Applies SMTP settings to a PHPMailer instance.
function configureCivicconnectMailer(PHPMailer $mail)
{
    $username = civicconnect_env('SMTP_USERNAME');
    $password = civicconnect_env('SMTP_PASSWORD');

    if ($username === '' || $password === '') {
        civicconnect_record_mail_error('SMTP_USERNAME and SMTP_PASSWORD must be set in .env.');
        return false;
    }

    $mail->isSMTP();
    $mail->Host = civicconnect_env('SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = civicconnect_env('SMTP_SECURE', 'tls');
    $mail->Port = (int)civicconnect_env('SMTP_PORT', '587');
    $mail->CharSet = 'UTF-8';

    return true;
}

// Sends an OTP email to a user.
function sendOTP($email, $otp)
{
    $mail = new PHPMailer(true);

    try {
        if (!configureCivicconnectMailer($mail)) {
            return false;
        }

        $mail->setFrom(civicconnect_env('SMTP_FROM_EMAIL', civicconnect_env('SMTP_USERNAME')), civicconnect_env('SMTP_FROM_NAME', 'CivicConnect'));
        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = "CivicConnect Password Reset";

        $mail->Body = "
<h3>CivicConnect Password Reset</h3>

<p>Your verification code is:</p>

<h2>$otp</h2>

<p>This code will expire in <b>5 minutes</b>.</p>

<p>If you did not request this password reset, please ignore this email.</p>
";

        $mail->send();

        return true;
    } catch (Throwable $e) {
        civicconnect_record_mail_error($e->getMessage() ?: $mail->ErrorInfo);
        return false;
    }
}

// Sends the public report tracking email.
function sendReportTrackingEmail($email, array $report)
{
    $mail = new PHPMailer(true);

    try {
        if (!configureCivicconnectMailer($mail)) {
            return false;
        }

        $mail->setFrom(civicconnect_env('SMTP_FROM_EMAIL', civicconnect_env('SMTP_USERNAME')), civicconnect_env('SMTP_FROM_NAME', 'CivicConnect'));
        $mail->addAddress($email);
        $mail->isHTML(true);

        $category = htmlspecialchars((string)($report['category'] ?? ''), ENT_QUOTES, 'UTF-8');
        $description = nl2br(htmlspecialchars((string)($report['description'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $ward = htmlspecialchars((string)($report['ward'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars((string)($report['status'] ?? 'Reported'), ENT_QUOTES, 'UTF-8');
        $trackingToken = htmlspecialchars((string)($report['tracking_token'] ?? ''), ENT_QUOTES, 'UTF-8');
        $submittedAt = htmlspecialchars((string)($report['submitted_at'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8');
        $reportId = htmlspecialchars((string)($report['report_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $trackingUrl = htmlspecialchars((string)($report['tracking_url'] ?? ''), ENT_QUOTES, 'UTF-8');

        $mail->Subject = 'CivicConnect Report Submitted - Tracking Token';
        $mail->Body = "
            <h3>CivicConnect Report Submitted</h3>
            <p>Your civic issue report has been submitted successfully.</p>

            <p><strong>Tracking Token:</strong></p>
            <h2 style=\"letter-spacing:1px;\">{$trackingToken}</h2>

            <p>You can use this token on the CivicConnect home page to check the report status without logging in.</p>
            " . ($trackingUrl !== '' ? "<p><a href=\"{$trackingUrl}\">Open CivicConnect status tracker</a></p>" : "") . "

            <h4>Report Details</h4>
            <p><strong>Report ID:</strong> {$reportId}</p>
            <p><strong>Category:</strong> {$category}</p>
            <p><strong>Description:</strong><br>{$description}</p>
            <p><strong>Ward:</strong> {$ward}</p>
            <p><strong>Current Status:</strong> {$status}</p>
            <p><strong>Submitted At:</strong> {$submittedAt}</p>

            <p>Thank you for helping improve civic services in Bhubaneswar.</p>
            <p>Regards,<br>CivicConnect Team<br>Bhubaneswar Municipal Corporation</p>
        ";

        $plainDescription = (string)($report['description'] ?? '');
        $mail->AltBody = "CivicConnect Report Submitted\n\n"
            . "Tracking Token: " . ($report['tracking_token'] ?? '') . "\n"
            . "Use this token on the CivicConnect home page to check the report status without logging in.\n\n"
            . (!empty($report['tracking_url']) ? "Tracker Link: " . $report['tracking_url'] . "\n\n" : "")
            . "Report Details\n"
            . "Report ID: " . ($report['report_id'] ?? '') . "\n"
            . "Category: " . ($report['category'] ?? '') . "\n"
            . "Description: " . $plainDescription . "\n"
            . "Ward: " . ($report['ward'] ?? '') . "\n"
            . "Current Status: " . ($report['status'] ?? 'Reported') . "\n"
            . "Submitted At: " . ($report['submitted_at'] ?? date('Y-m-d H:i:s')) . "\n\n"
            . "CivicConnect Team\nBhubaneswar Municipal Corporation";

        $mail->send();
        return true;
    } catch (Throwable $e) {
        civicconnect_record_mail_error($e->getMessage() ?: $mail->ErrorInfo);
        return false;
    }
}
