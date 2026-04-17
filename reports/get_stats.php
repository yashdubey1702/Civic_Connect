<?php
// reports/get_stats.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/Auth.php';

session_start();

// ----------------------------------
// STEP 0: Initialize database and auth
// ----------------------------------
$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

// ----------------------------------
// STEP 1: Determine ward from session (for municipal admin)
// ----------------------------------
$ward =$_SESSION['ward'];

// if ($auth->isMunicipalAdmin()) {
//     $ward = trim($auth->getWard()); // e.g., "W12"
// }

// Make ward uppercase to match DB
//$ward = $ward;
// echo "ward is $ward";
// ----------------------------------
// STEP 2: Build WHERE clause robustly
// ----------------------------------
$whereClause = '';
$bindValues  = [];
$bindTypes   = '';

if (!empty($ward)) {
    // Compare uppercase trimmed strings
    $whereClause = "WHERE UPPER(TRIM(municipality)) = ?";
    $bindValues[] = $ward;
    $bindTypes .= 's';
}

// ----------------------------------
// STEP 3: Prepare SQL query with status counts
// ----------------------------------
$query = "
SELECT
    COUNT(*) AS total,
    SUM(TRIM(status) = 'Reported') AS reported,
    SUM(TRIM(status) = 'Acknowledged') AS acknowledged,
    SUM(TRIM(status) = 'In Progress') AS in_progress,
    SUM(TRIM(status) = 'Resolved') AS resolved,
    COUNT(DISTINCT email) AS unique_users
FROM reports
$whereClause
";

// ----------------------------------
// STEP 4: Prepare and execute statement
// ----------------------------------
$stmt = $db->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Database prepare failed', 'sql_error' => $db->error]);
    exit;
}

if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// ----------------------------------
// STEP 5: Return JSON (cast to int)
// ----------------------------------
echo json_encode([
    'total'        => (int)($stats['total'] ?? 0),
    'reported'     => (int)($stats['reported'] ?? 0),
    'acknowledged' => (int)($stats['acknowledged'] ?? 0),
    'in_progress'  => (int)($stats['in_progress'] ?? 0),
    'resolved'     => (int)($stats['resolved'] ?? 0),
    'unique_users' => (int)($stats['unique_users'] ?? 0)
]);