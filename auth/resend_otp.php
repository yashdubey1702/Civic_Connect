<?php

session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Support/mail.php';
require_once __DIR__ . '/../app/Support/password_reset.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['reset_email'])) {
    header("Location: /town_issues/auth/forget_password.php");
    exit;
}

$email = trim($_SESSION['reset_email']);

$otp = issuePasswordResetOtp($db, $email);
if ($otp !== null) {
    if (sendOTP($email, $otp)) {
        $_SESSION['reset_notice'] = "A new verification code has been sent.";
        $_SESSION['reset_notice_type'] = "success";
    } else {
        $_SESSION['reset_notice'] = "We could not send the email right now. Please try again in a moment.";
        $_SESSION['reset_notice_type'] = "danger";
    }
} else {
    $_SESSION['reset_notice'] = "Please wait a moment before requesting another code.";
    $_SESSION['reset_notice_type'] = "success";
}

header("Location: /town_issues/auth/verify_account.php");
exit;
