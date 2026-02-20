<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/Auth.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

/* =========================
   AUTHORIZATION CHECK
   ========================= */
if (!$auth->isAdmin() && !$auth->isMunicipalAdmin()) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);
    exit;
}

/* =========================
   INPUT
   ========================= */
$input = json_decode(file_get_contents('php://input'), true);
$reportId  = $input['id'] ?? null;
$newStatus = $input['status'] ?? null;

if (!$reportId || !$newStatus) {
    echo json_encode([
        "success" => false,
        "message" => "Missing report ID or status."
    ]);
    exit;
}

/* =========================
   FETCH REPORT
   ========================= */
$query = "SELECT category, description, email FROM reports WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $reportId);
$stmt->execute();

$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    echo json_encode([
        "success" => false,
        "message" => "Report not found."
    ]);
    exit;
}

/* =========================
   EMAIL CHECK
   ========================= */
if (empty($report['email'])) {
    echo json_encode([
        "success" => true,
        "message" => "No email provided. Notification skipped."
    ]);
    exit;
}

/* =========================
   EMAIL CONTENT (Bhubaneswar)
   ========================= */
$to = $report['email'];
$subject = "CivicConnect Bhubaneswar – Update on Your Report";

$message = "
Hello,

The status of your reported civic issue has been updated by Bhubaneswar Municipal Corporation.

Category: {$report['category']}
Description: {$report['description']}
Current Status: {$newStatus}

Thank you for helping improve civic services in Bhubaneswar.

Regards,
CivicConnect Team
Bhubaneswar Municipal Corporation
";

$headers  = "From: noreply@civicconnect-bhubaneswar.in\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

/* =========================
   SEND EMAIL
   ========================= */
if (mail($to, $subject, $message, $headers)) {
    echo json_encode([
        "success" => true,
        "message" => "Email notification sent successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Email sending failed."
    ]);
}
