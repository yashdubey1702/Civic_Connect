<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

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