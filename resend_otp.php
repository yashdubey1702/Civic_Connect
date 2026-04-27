<?php

session_start();
require_once 'config/database.php';
require_once 'config/mail.php';
require_once 'config/password_reset.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['reset_email'])) {
    header("Location: forget_password.php");
    exit;
}

$email = trim($_SESSION['reset_email']);

$otp = issuePasswordResetOtp($db, $email);
if ($otp !== null) {
    sendOTP($email, $otp);
}

header("Location: verify_account.php");
exit;

?>
