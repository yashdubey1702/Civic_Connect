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
if (
    !$auth->isLoggedIn() ||
    (
        !$auth->isAdmin() &&
        !$auth->isMunicipalAdmin() &&
        !$auth->isWardAdmin()
    )
) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access"
    ]);
    exit;
}

/* =========================
   INPUT VALIDATION
   ========================= */
$input = json_decode(file_get_contents('php://input'), true);
$reportId  = (int)($input['id'] ?? 0);
$newStatus = trim($input['status'] ?? '');

$allowedStatuses = ['Reported', 'Acknowledged', 'In Progress', 'Resolved'];

if ($reportId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid report ID or status"
    ]);
    exit;
}

/* =========================
   WARD OWNERSHIP ENFORCEMENT
   ========================= */
if ($auth->isWardAdmin()) {

    $ward = strtoupper($auth->getWard());

    $checkSql = "
        SELECT id 
        FROM reports 
        WHERE id = ? AND municipality = ?
        LIMIT 1
    ";

    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bind_param("is", $reportId, $ward);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows !== 1) {
        echo json_encode([
            "success" => false,
            "message" => "You are not allowed to update this report"
        ]);
        $checkStmt->close();
        exit;
    }

    $checkStmt->close();
}

/* =========================
   UPDATE STATUS
   ========================= */
$query = "UPDATE reports SET status = ? WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("si", $newStatus, $reportId);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Status updated successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update status"
    ]);
}

$stmt->close();
