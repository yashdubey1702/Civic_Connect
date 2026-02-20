<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once __DIR__ . '/../config/BhubaneswarDetector.php';


$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

/* =========================
   AUTH CHECK
   ========================= */
if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
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
        SET category = ?, description = ?, latitude = ?, longitude = ?, image_filename = ?, municipality = ?
        WHERE id = ? AND email = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "ssddssis",
        $category,
        $description,
        $lat,
        $lng,
        $imageFilename,
        $ward,
        $reportId,
        $_SESSION['email']
    );
} else {
    $query = "
        UPDATE reports
        SET category = ?, description = ?, latitude = ?, longitude = ?, municipality = ?
        WHERE id = ? AND email = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "ssddsis",
        $category,
        $description,
        $lat,
        $lng,
        $ward,
        $reportId,
        $_SESSION['email']
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
        "message" => "Failed to update report"
    ]);
}

$stmt->close();
