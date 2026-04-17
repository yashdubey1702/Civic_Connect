<?php

session_start();
require_once 'config/database.php';
require_once 'config/mail.php';

$database = new Database();
$db = $database->getConnection();

$email = $_SESSION['reset_email'];

$otp = rand(100000,999999);
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$query = "UPDATE password_resets SET otp=?, expires_at=? WHERE email=?";
$stmt = $db->prepare($query);
$stmt->bind_param("sss",$otp,$expiry,$email);
$stmt->execute();

sendOTP($email,$otp);

header("Location: verify_account.php");

?>