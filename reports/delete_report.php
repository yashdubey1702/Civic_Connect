<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection(); // mysqli

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$reportId = $input['id'] ?? null;

if (!$reportId) {
    echo json_encode(["success" => false, "message" => "Invalid report ID"]);
    exit;
}

$query = "DELETE FROM reports WHERE id = ? AND email = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("is", $reportId, $_SESSION['email']);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Report deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
}

$stmt->close();
?>

