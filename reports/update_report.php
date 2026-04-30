<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/BhubaneswarDetector.php';


$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

/* =========================
   AUTH CHECK
   ========================= */
if (!isset($_SESSION['email'], $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    echo json_encode(["success" => false, "message" => "Invalid request token"]);
    exit;
}

/* =========================
   INPUT
   ========================= */
$reportId    = intval($_POST['id'] ?? 0);
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$lat         = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng         = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$imageFilename = null;

if (!$reportId || !$category || !$description || $lat === null || $lng === null) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$ownerCheck = $db->prepare("SELECT id FROM reports WHERE id = ? AND user_id = ? LIMIT 1");
$ownerCheck->bind_param("ii", $reportId, $userId);
$ownerCheck->execute();
$ownerCheck->store_result();
if ($ownerCheck->num_rows !== 1) {
    $ownerCheck->close();
    echo json_encode(["success" => false, "message" => "Report was not found or you are not allowed to update it"]);
    exit;
}
$ownerCheck->close();

/* =========================
   LOCATION VALIDATION
   ========================= */
if (!$wardDetector->isWithinBhubaneswar($lat, $lng)) {
    echo json_encode([
        "success" => false,
        "message" => "Updated location must be within Bhubaneswar city limits."
    ]);
    exit;
}

/* =========================
   WARD DETECTION
   ========================= */
$ward = $wardDetector->detectWard($lat, $lng);
if ($ward === null) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to detect ward for the selected location."
    ]);
    exit;
}

/* =========================
   IMAGE UPLOAD (OPTIONAL)
   ========================= */
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode(["success" => false, "message" => "Invalid image file"]);
        exit;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mime = $check['mime'];
    if (!isset($allowedTypes[$mime])) {
        echo json_encode(["success" => false, "message" => "Unsupported image format"]);
        exit;
    }

    $imageFilename = uniqid('report_', true) . '.' . $allowedTypes[$mime];
    $destination = $uploadDir . $imageFilename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        echo json_encode(["success" => false, "message" => "Image upload failed"]);
        exit;
    }
}

/* =========================
   UPDATE QUERY
   ========================= */
if ($imageFilename) {
    $query = "
        UPDATE reports
        SET category = ?, description = ?, latitude = ?, longitude = ?, image_filename = ?, municipality = ?, user_id = ?
        WHERE id = ? AND user_id = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "ssddssiii",
        $category,
        $description,
        $lat,
        $lng,
        $imageFilename,
        $ward,
        $userId,
        $reportId,
        $userId
    );
} else {
    $query = "
        UPDATE reports
        SET category = ?, description = ?, latitude = ?, longitude = ?, municipality = ?, user_id = ?
        WHERE id = ? AND user_id = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "ssddsiii",
        $category,
        $description,
        $lat,
        $lng,
        $ward,
        $userId,
        $reportId,
        $userId
    );
}

/* =========================
   EXECUTE
   ========================= */
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Report updated successfully",
        "ward" => $ward
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Report was not found or you are not allowed to update it"
    ]);
}

$stmt->close();
