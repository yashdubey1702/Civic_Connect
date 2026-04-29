<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

function sendOTP($email,$otp){

$mail = new PHPMailer(true);

try {

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;

$mail->Username = 'civicconnectsit@gmail.com';
$mail->Password = 'rddcuqhqormwayqg';

$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->CharSet = 'UTF-8';

$mail->setFrom('civicconnectsit@gmail.com','CivicConnect');
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

} catch (Exception $e) {

return false;

}

}

function sendReportTrackingEmail($email, array $report)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'civicconnectsit@gmail.com';
        $mail->Password = 'rddcuqhqormwayqg';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('civicconnectsit@gmail.com', 'CivicConnect');
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
    } catch (Exception $e) {
        return false;
    }
}
