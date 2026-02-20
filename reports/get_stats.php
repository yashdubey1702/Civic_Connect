<?php
// reports/get_stats.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/Auth.php';

session_start();

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

/*
 UI sends ?ward=
 DB column is still `municipality`
*/
$ward = trim($_GET['ward'] ?? 'all');

/*
 Normalize ward (CRITICAL)
*/
if ($ward !== 'all' && $ward !== '') {
    $ward = strtoupper($ward); // w9 → W9
}

$whereClause = '';
$bindValues = [];
$bindTypes  = '';

/*
 Municipal admin: force assigned ward
*/
if ($auth->isMunicipalAdmin()) {
    $adminWard = strtoupper($auth->getMunicipality());
    $whereClause = 'WHERE municipality = ?';
    $bindValues[] = $adminWard;
    $bindTypes .= 's';
}
/*
 Admin-selected ward
*/
elseif ($ward !== 'all' && $ward !== '') {
    $whereClause = 'WHERE municipality = ?';
    $bindValues[] = $ward;
    $bindTypes .= 's';
}

$query = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Reported' THEN 1 ELSE 0 END) AS reported,
        SUM(CASE WHEN status = 'Acknowledged' THEN 1 ELSE 0 END) AS acknowledged,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        COUNT(DISTINCT email) AS unique_users
    FROM reports
    $whereClause
";

$stmt = $db->prepare($query);

if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

/*
 Fail-safe response
*/
echo json_encode($stats ?: [
    'total'         => 0,
    'reported'      => 0,
    'acknowledged'  => 0,
    'in_progress'   => 0,
    'resolved'      => 0,
    'unique_users'  => 0
]);
