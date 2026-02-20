<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/Auth.php';

session_start();

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

/*
 ============================
 Filters (UI → Backend)
 ============================
*/
$category = $_GET['category'] ?? 'all';
$status   = $_GET['status'] ?? 'all';

/*
 UI uses `ward`
 DB column is still `municipality`
*/
$ward = trim($_GET['ward'] ?? 'all');

/*
 Normalize ward (important)
*/
if ($ward !== 'all' && $ward !== '') {
    $ward = strtoupper($ward); // e.g. w9 → W9
}

/*
 ============================
 Build WHERE clause
 ============================
*/
$where = [];
$bindValues = [];
$bindTypes  = "";

/*
 ============================
 ROLE-BASED ENFORCEMENT
 ============================
*/

/* Citizen → own reports only */
if ($auth->isCitizen()) {
    $where[] = "email = ?";
    $bindValues[] = $_SESSION['email'];
    $bindTypes .= "s";
}

/* Ward Admin → forced ward */
elseif ($auth->isWardAdmin()) {
    $adminWard = strtoupper($auth->getWard());
    $where[] = "municipality = ?";
    $bindValues[] = $adminWard;
    $bindTypes .= "s";
}

/* Municipal Admin → optional ward filter */
elseif ($auth->isMunicipalAdmin() && $ward !== 'all' && $ward !== '') {
    $where[] = "municipality = ?";
    $bindValues[] = $ward;
    $bindTypes .= "s";
}

/*
 Category filter
*/
if ($category !== 'all') {
    $where[] = "category = ?";
    $bindValues[] = $category;
    $bindTypes .= "s";
}

/*
 Status filter
*/
if ($status !== 'all') {
    $where[] = "status = ?";
    $bindValues[] = $status;
    $bindTypes .= "s";
}

/*
 WHERE clause
*/
$whereClause = '';
if (!empty($where)) {
    $whereClause = 'WHERE ' . implode(' AND ', $where);
}

/*
 ============================
 Final SQL
 ============================
*/
$sql = "
    SELECT 
        id,
        latitude,
        longitude,
        category,
        description,
        email,
        status,
        created_at,
        image_filename
    FROM reports
    $whereClause
    ORDER BY created_at DESC
";

$stmt = $db->prepare($sql);

if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

echo json_encode($reports);
