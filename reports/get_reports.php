<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/Auth.php';

session_start();

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)($_GET['per_page'] ?? 10));
$offset  = ($page - 1) * $perPage;

$status   = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? 'all';

$ward = trim($_GET['ward'] ?? 'all');

if ($ward !== 'all' && $ward !== '') {
    $ward = strtoupper($ward); // w9 → W9
}

/*
 ============================
 BUILD FILTERS
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
 Search
*/
if ($search !== '') {
    $where[] = "(category LIKE ? OR description LIKE ? OR email LIKE ?)";
    $like = "%{$search}%";
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindTypes .= "sss";
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
 Category filter
*/
if ($category !== 'all') {
    $where[] = "category = ?";
    $bindValues[] = $category;
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
 COUNT TOTAL RECORDS
 ============================
*/
$countSql = "SELECT COUNT(*) AS total FROM reports $whereClause";
$countStmt = $db->prepare($countSql);

if (!empty($bindValues)) {
    $countStmt->bind_param($bindTypes, ...$bindValues);
}

$countStmt->execute();
$totalRecords = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = (int)ceil($totalRecords / $perPage);

/*
 ============================
 FETCH PAGINATED DATA
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
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($sql);

/*
 Bind filters + pagination
*/
$finalBindTypes  = $bindTypes . "ii";
$finalBindValues = array_merge($bindValues, [$perPage, $offset]);

$stmt->bind_param($finalBindTypes, ...$finalBindValues);
$stmt->execute();

$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/*
 ============================
 RESPONSE
 ============================
*/
echo json_encode([
    'reports' => $reports,
    'pagination' => [
        'current_page'  => $page,
        'per_page'      => $perPage,
        'total_records' => $totalRecords,
        'total_pages'   => $totalPages
    ]
]);
