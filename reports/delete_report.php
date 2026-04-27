<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection(); // mysqli

// Check if user is logged in
if (!isset($_SESSION['email'], $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}
$csrfToken = $input['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    echo json_encode(["success" => false, "message" => "Invalid request token"]);
    exit;
}

$reportId = $input['id'] ?? null;

if (!$reportId) {
    echo json_encode(["success" => false, "message" => "Invalid report ID"]);
    exit;
}

$query = "DELETE FROM reports WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $reportId, $userId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Report deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
}

$stmt->close();
?>
