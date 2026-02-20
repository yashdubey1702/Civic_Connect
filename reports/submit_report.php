<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/BhubaneswarDetector.php';

/* =========================
   INIT
   ========================= */
$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

/* =========================
   INPUT
   ========================= */
$lat         = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng         = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');

/* 🔒 EMAIL MUST COME FROM SESSION */
$email = $_SESSION['email'] ?? null;

$imageFilename = null;

/* =========================
   AUTH VALIDATION
   ========================= */
if ($email === null) {
    echo json_encode([
        "success" => false,
        "message" => "User not authenticated."
    ]);
    exit;
}

/* =========================
   BASIC VALIDATION
   ========================= */
if (
    $lat === null ||
    $lng === null ||
    !is_numeric($lat) ||
    !is_numeric($lng) ||
    $category === ''
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);
    exit;
}

/* =========================
   BHUBANESWAR BOUNDARY CHECK
   ========================= */
if (!$wardDetector->isWithinBhubaneswar($lat, $lng)) {
    echo json_encode([
        "success" => false,
        "message" => "Reports can only be submitted within Bhubaneswar city limits."
    ]);
    exit;
}

/* =========================
   DETECT WARD
   ========================= */
$ward = $wardDetector->detectWard($lat, $lng);

if ($ward === null) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to detect ward. Please select a valid location."
    ]);
    exit;
}

/* =========================
   IMAGE UPLOAD
   ========================= */
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image file."
        ]);
        exit;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mime = $check['mime'];
    if (!isset($allowedTypes[$mime])) {
        echo json_encode([
            "success" => false,
            "message" => "Unsupported image type."
        ]);
        exit;
    }

    $imageFilename = uniqid('report_', true) . '.' . $allowedTypes[$mime];
    $uploadPath = $uploadDir . $imageFilename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        echo json_encode([
            "success" => false,
            "message" => "Image upload failed."
        ]);
        exit;
    }
}

 
/* =========================
   INSERT REPORT
   ========================= */
$query = "
    INSERT INTO reports
        (latitude, longitude, category, description, email, image_filename, municipality)
    VALUES (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $db->prepare($query);
$stmt->bind_param(
    "ddsssss",
    $lat,
    $lng,
    $category,
    $description,
    $email,
    $imageFilename,
    $ward
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Report submitted successfully.",
        "ward"    => $ward
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save report."
    ]);
}

$stmt->close();
