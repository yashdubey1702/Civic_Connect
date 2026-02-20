<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/Auth.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

/*
 ============================
 AUTH CHECK
 ============================
*/
if (!$auth->isLoggedIn() || !$auth->isCitizen()) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authorized',
        'reports' => []
    ]);
    exit;
}

/*
 ============================
 FETCH ONLY LOGGED-IN USER REPORTS
 ============================
*/
$query = "
    SELECT 
        id,
        latitude,
        longitude,
        category,
        description,
        status,
        created_at,
        image_filename
    FROM reports
    WHERE email = ?
    ORDER BY created_at DESC
";

$stmt = $db->prepare($query);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();

$result  = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

echo json_encode([
    'success' => true,
    'reports' => $reports
]);
